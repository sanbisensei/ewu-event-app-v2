<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? 'client';
    
    if ($role === 'admin') {
        $manager_id = $_POST['manager_id'] ?? '';
        $manager_pass = $_POST['manager_pass'] ?? '';
        
        $stmt = $pdo->prepare("SELECT * FROM Managers WHERE manager_id = ?");
        $stmt->execute([$manager_id]);
        $row = $stmt->fetch();
        
        if ($row) {
            // Check password (handle both hashed and plain text for backward compatibility)
            $passwordValid = false;
            if(password_verify($manager_pass, $row['manager_pass'])) {
                $passwordValid = true;
            } elseif($manager_pass === $row['manager_pass']) {
                $passwordValid = true; // Backward compatibility
            }
            
            if($passwordValid) {
                $_SESSION['role'] = 'admin';
                $_SESSION['manager_id'] = $manager_id;
                header("Location: admin/dashboard.php");
                exit;
            } else {
                $error = "Invalid admin credentials";
            }
        } else {
            $error = "Invalid admin credentials";
        }
        
    } else {
        $customer_id = $_POST['customer_id'] ?? '';
        $customer_pass = $_POST['customer_pass'] ?? '';
        
        $stmt = $pdo->prepare("SELECT * FROM Customers WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $row = $stmt->fetch();
        
        if ($row) {
            // Check password (handle both hashed and plain text for backward compatibility)
            $passwordValid = false;
            if(password_verify($customer_pass, $row['customer_pass'])) {
                $passwordValid = true;
            } elseif($customer_pass === $row['customer_pass']) {
                $passwordValid = true; // Backward compatibility
            }
            
            if($passwordValid) {
                $_SESSION['role'] = 'client';
                $_SESSION['customer_id'] = $customer_id;
                header("Location: client/dashboard.php");
                exit;
            } else {
                $error = "Invalid client credentials";
            }
        } else {
            $error = "Invalid client credentials";
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>EWU Event - Login</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
  <h1>🎉 EWU Event Management</h1>
  <p style="text-align:center;color:#94a3b8;margin-bottom:24px;">Your premier event booking and management platform</p>
  
  <?php if($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="grid">
    <div class="card">
      <h2>🔐 Admin Login</h2>
      <p style="color:#94a3b8;margin-bottom:16px;">For event managers and administrators</p>
      <form method="post">
        <input type="hidden" name="role" value="admin">
        <label>Manager ID</label>
        <input name="manager_id" required placeholder="e.g. M001">
        <label>Password</label>
        <input type="password" name="manager_pass" required>
        <button type="submit" style="background:#dc2626;">Login as Admin</button>
      </form>
    </div>

    <div class="card">
      <h2>👤 Student Login</h2>
      <p style="color:#94a3b8;margin-bottom:16px;">For students booking events</p>
      <form method="post">
        <input type="hidden" name="role" value="client">
        <label>Student ID</label>
        <input name="customer_id" required placeholder="e.g. C001">
        <label>Password</label>
        <input type="password" name="customer_pass" required>
        <button type="submit" style="background:#059669;">Login as Client</button>
      </form>
      <p style="margin-top:16px;">No account? <a href="client/profile.php?action=register" style="color:#3b82f6;">Register here</a></p>
    </div>
  </div>

  <div class="card" style="margin-top:24px;text-align:center;">
    <h3>🌟 Welcome to EWU Event Management</h3>
    <div style="background:#1f2937;padding:16px;border-radius:8px;margin:16px 0;">
      <p><strong>For Clients:</strong> Book events, manage guests, make payments, and leave feedback</p>
      <p><strong>For Admins:</strong> Manage events, venues, meals, bookings, and view analytics</p>
    </div>
    
    <div style="display:flex;justify-content:center;gap:16px;flex-wrap:wrap;margin-top:16px;">
      <div style="background:#064e3b;padding:12px;border-radius:8px;flex:1;min-width:200px;">
        <h4>✨ Features</h4>
        <ul style="text-align:left;margin:8px 0;padding-left:20px;">
          <li>Event booking system</li>
          <li>Guest management</li>
          <li>Payment processing</li>
          <li>Feedback & reviews</li>
        </ul>
      </div>
      
      <div style="background:#1e1b4b;padding:12px;border-radius:8px;flex:1;min-width:200px;">
        <h4>🎯 Event Types</h4>
        <ul style="text-align:left;margin:8px 0;padding-left:20px;">
          <li>Weddings & Celebrations</li>
          <li>Corporate Events</li>
          <li>Seminars & Workshops</li>
          <li>Entertainment Shows</li>
        </ul>
      </div>
    </div>
  </div>
</div>
</body>
</html>
