<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$action = $_GET['action'] ?? '';
$msg = '';
$error = '';

// Handle registration
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check if customer ID already exists
        $check = $pdo->prepare("SELECT customer_id FROM Customers WHERE customer_id = ?");
        $check->execute([$_POST['customer_id']]);
        if($check->fetch()) {
            $error = "Customer ID already exists. Please choose a different ID.";
        } else {
            // Hash password for better security
            $hashedPass = password_hash($_POST['customer_pass'], PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO Customers (customer_id, customer_pass, customer_name, customer_address, customer_contact) VALUES (?,?,?,?,?)");
            $stmt->execute([
                $_POST['customer_id'], 
                $hashedPass, // Use hashed password
                $_POST['customer_name'], 
                $_POST['customer_address'], 
                $_POST['customer_contact']
            ]);
            $msg = "Registration successful! You can now login.";
        }
    } catch(PDOException $e) {
        $error = "Registration failed: " . $e->getMessage();
    }
}

session_start();
if(isset($_SESSION['role']) && $_SESSION['role']==='client'){
    $customer_id = $_SESSION['customer_id'];
    
    // Handle profile update
    if($_SERVER['REQUEST_METHOD']==='POST' && $_GET['action']==='update'){
        try {
            $stmt = $pdo->prepare("UPDATE Customers SET customer_name=?, customer_address=?, customer_contact=? WHERE customer_id=?");
            $stmt->execute([$_POST['customer_name'], $_POST['customer_address'], $_POST['customer_contact'], $customer_id]);
            $msg = "Profile updated successfully";
        } catch(PDOException $e) {
            $error = "Update failed: " . $e->getMessage();
        }
    }
    
    // Handle password change
    if($_SERVER['REQUEST_METHOD']==='POST' && $_GET['action']==='change_password'){
        try {
            $current = $_POST['current_password'];
            $new = $_POST['new_password'];
            $confirm = $_POST['confirm_password'];
            
            // Get current password
            $user = $pdo->prepare("SELECT customer_pass FROM Customers WHERE customer_id = ?");
            $user->execute([$customer_id]);
            $user = $user->fetch();
            
            // Verify current password (handle both hashed and plain text for backward compatibility)
            $currentValid = false;
            if(password_verify($current, $user['customer_pass'])) {
                $currentValid = true;
            } elseif($current === $user['customer_pass']) {
                $currentValid = true; // Backward compatibility
            }
            
            if(!$currentValid) {
                $error = "Current password is incorrect";
            } elseif($new !== $confirm) {
                $error = "New passwords don't match";
            } elseif(strlen($new) < 3) {
                $error = "Password must be at least 3 characters";
            } else {
                $hashedNew = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE Customers SET customer_pass = ? WHERE customer_id = ?");
                $stmt->execute([$hashedNew, $customer_id]);
                $msg = "Password changed successfully";
            }
        } catch(PDOException $e) {
            $error = "Password change failed: " . $e->getMessage();
        }
    }
    
    // Get user data
    $me = $pdo->prepare("SELECT * FROM Customers WHERE customer_id=?");
    $me->execute([$customer_id]);
    $me = $me->fetch();
    
    // Get user statistics
    $stats = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT b.booking_id) as total_bookings,
            COALESCE(SUM(b.total_cost), 0) as total_spent,
            COUNT(DISTINCT g.guest_id) as total_guests,
            COUNT(DISTINCT f.feedback_id) as total_reviews
        FROM Customers c
        LEFT JOIN Bookings b ON c.customer_id = b.customer_id
        LEFT JOIN Guests g ON c.customer_id = g.customer_id
        LEFT JOIN Feedback f ON c.customer_id = f.customer_id
        WHERE c.customer_id = ?
    ");
    $stats->execute([$customer_id]);
    $stats = $stats->fetch();
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><link rel="stylesheet" href="../assets/style.css"><title>Profile</title></head><body>
<div class="container">
  <div class="nav">
    <a class="badge" href="../index.php">Home</a>
    <?php if(isset($_SESSION['role']) && $_SESSION['role']==='client'): ?>
      <a class="badge" href="dashboard.php">Dashboard</a>
      <a class="badge" href="my_bookings.php">Bookings</a>
    <?php endif; ?>
    <a class="badge" href="../logout.php">Logout</a>
  </div>

  <?php if($msg): ?><div class="success"><?=htmlspecialchars($msg)?></div><?php endif; ?>
  <?php if($error): ?><div class="error"><?=htmlspecialchars($error)?></div><?php endif; ?>

  <?php if($action==='register' && (!isset($_SESSION['role']) || $_SESSION['role']!=='client')): ?>
    <div class="card">
      <h1>🆕 Create Account</h1>
      <form method="post" class="row">
        <label>Customer ID *</label>
        <input name="customer_id" required placeholder="e.g. C003" pattern="C[0-9]+" title="Format: C001, C002, etc."/>
        
        <label>Password *</label>
        <input type="password" name="customer_pass" required minlength="3" />
        
        <label>Full Name</label>
        <input name="customer_name" placeholder="Your full name" />
        
        <label>Address</label>
        <textarea name="customer_address" rows="2" placeholder="Your address"></textarea>
        
        <label>Contact Number</label>
        <input name="customer_contact" placeholder="Phone number" />
        
        <button style="background:#059669;font-size:16px;">Create Account</button>
      </form>
      <p style="margin-top:16px;">Already have an account? <a href="../index.php">Login here</a></p>
    </div>
    
  <?php elseif(isset($me)): ?>
    <div class="grid">
      <div class="card">
        <h1>👤 My Profile</h1>
        <div style="background:#1f2937;padding:12px;border-radius:8px;margin:10px 0;">
          <p><strong>Customer ID:</strong> <?=htmlspecialchars($me['customer_id'])?></p>
          <p><strong>Name:</strong> <?=htmlspecialchars($me['customer_name'] ?: 'Not set')?></p>
          <p><strong>Contact:</strong> <?=htmlspecialchars($me['customer_contact'] ?: 'Not set')?></p>
          <p><strong>Address:</strong> <?=htmlspecialchars($me['customer_address'] ?: 'Not set')?></p>
        </div>
        
        <form method="post" class="row" action="?action=update">
          <label>Full Name</label>
          <input name="customer_name" value="<?=htmlspecialchars($me['customer_name'])?>" />
          
          <label>Address</label>
          <textarea name="customer_address" rows="2"><?=htmlspecialchars($me['customer_address'])?></textarea>
          
          <label>Contact Number</label>
          <input name="customer_contact" value="<?=htmlspecialchars($me['customer_contact'])?>" />
          
          <button>Update Profile</button>
        </form>
      </div>

      <div class="card">
        <h3>📊 Account Statistics</h3>
        <div style="background:#1f2937;padding:12px;border-radius:8px;">
          <p>Total Bookings: <strong><?=$stats['total_bookings']?></strong></p>
          <p>Total Spent: <strong>৳<?=number_format($stats['total_spent'], 2)?></strong></p>
          <p>Guests Added: <strong><?=$stats['total_guests']?></strong></p>
          <p>Reviews Given: <strong><?=$stats['total_reviews']?></strong></p>
        </div>
        
        <h4 style="margin-top:16px;">🔐 Change Password</h4>
        <form method="post" action="?action=change_password" class="row">
          <label>Current Password</label>
          <input type="password" name="current_password" required />
          
          <label>New Password</label>
          <input type="password" name="new_password" required minlength="3" />
          
          <label>Confirm New Password</label>
          <input type="password" name="confirm_password" required />
          
          <button style="background:#dc2626;">Change Password</button>
        </form>
      </div>
    </div>
    
  <?php else: ?>
    <div class="card">
      <h1>Access Profile</h1>
      <p>Please <a href="../index.php">login</a> to view your profile or <a href="profile.php?action=register">create an account</a>.</p>
    </div>
  <?php endif; ?>
</div>
</body></html>