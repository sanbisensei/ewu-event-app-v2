<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireClient();
$customer_id = $_SESSION['customer_id'];
$msg = '';
$error = '';

// Handle payment processing
if(isset($_POST['process_payment'])) {
    try {
        $booking_id = $_POST['booking_id'];
        $payment_method = $_POST['payment_method'];
        
        // Get booking details
        $booking = $pdo->prepare("
            SELECT b.*, e.event_name, e.ticket_cost, v.venue_cost, m.meal_cost 
            FROM Bookings b 
            LEFT JOIN Events e ON b.event_id = e.event_id 
            LEFT JOIN Venues v ON e.venue_id = v.venue_id 
            LEFT JOIN Meals m ON e.meal_id = m.meal_id 
            WHERE b.booking_id = ? AND b.customer_id = ?
        ");
        $booking->execute([$booking_id, $customer_id]);
        $booking = $booking->fetch();
        
        if(!$booking) {
            $error = "Booking not found";
        } else {
            // Generate payment ID
            $payment_id = 'P' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            
            // Check if payment ID already exists
            while(true) {
                $check = $pdo->prepare("SELECT payment_id FROM Cashflow WHERE payment_id = ?");
                $check->execute([$payment_id]);
                if(!$check->fetch()) break;
                $payment_id = 'P' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            }
            
            // Calculate costs
            $food_cost = $booking['meal_cost'] ?: 0;
            $venue_cost = $booking['venue_cost'] ?: 0;
            $ticket_earning = $booking['total_cost'];
            $sponsor_funding = 0; // Default to 0 for client payments
            
            // Insert into cashflow
            $stmt = $pdo->prepare("INSERT INTO Cashflow (payment_id, customer_id, food_cost, venue_cost, ticket_earning, sponsor_funding, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$payment_id, $customer_id, $food_cost, $venue_cost, $ticket_earning, $sponsor_funding, $payment_method]);
            
            $msg = "Payment processed successfully! Payment ID: $payment_id";
        }
    } catch(PDOException $e) {
        $error = "Payment failed: " . $e->getMessage();
    }
}

// Get unpaid bookings
$unpaidBookings = $pdo->prepare("
    SELECT b.*, e.event_name, e.event_date, e.event_type, v.venue_name,
           CASE WHEN cf.payment_id IS NULL THEN 'Unpaid' ELSE 'Paid' END as payment_status
    FROM Bookings b 
    LEFT JOIN Events e ON b.event_id = e.event_id 
    LEFT JOIN Venues v ON e.venue_id = v.venue_id 
    LEFT JOIN Cashflow cf ON cf.customer_id = b.customer_id AND cf.ticket_earning = b.total_cost
    WHERE b.customer_id = ? AND e.event_date >= CURDATE()
    ORDER BY e.event_date ASC
");
$unpaidBookings->execute([$customer_id]);
$unpaidBookings = $unpaidBookings->fetchAll();

// Get payment history
$paymentHistory = $pdo->prepare("
    SELECT cf.*, b.booking_id, e.event_name, e.event_date 
    FROM Cashflow cf 
    LEFT JOIN Bookings b ON b.customer_id = cf.customer_id AND b.total_cost = cf.ticket_earning
    LEFT JOIN Events e ON b.event_id = e.event_id 
    WHERE cf.customer_id = ? 
    ORDER BY cf.payment_id DESC
");
$paymentHistory->execute([$customer_id]);
$paymentHistory = $paymentHistory->fetchAll();
?>
<!doctype html>
<html><head><meta charset="utf-8"><link rel="stylesheet" href="../assets/style.css"><title>Payments</title></head><body>
<div class="container">
  <div class="nav">
    <a class="badge" href="dashboard.php">Dashboard</a>
    <a class="badge" href="book_event.php">Book Events</a>
    <a class="badge" href="my_bookings.php">My Bookings</a>
    <a class="badge" href="feedback.php">Feedback</a>
    <a class="badge" href="../logout.php">Logout</a>
  </div>
  
  <h1>💳 Payments</h1>
  <?php if($msg): ?><div class="success"><?=htmlspecialchars($msg)?></div><?php endif; ?>
  <?php if($error): ?><div class="error"><?=htmlspecialchars($error)?></div><?php endif; ?>

  <div class="card">
    <h3>💰 Pending Payments</h3>
    <?php if(empty($unpaidBookings)): ?>
      <p>No pending payments. All bookings are up to date!</p>
    <?php else: ?>
    <table class="table">
      <tr><th>Booking</th><th>Event</th><th>Date</th><th>Amount</th><th>Status</th><th>Action</th></tr>
      <?php foreach($unpaidBookings as $b): ?>
      <tr>
        <td><?=htmlspecialchars($b['booking_id'])?></td>
        <td>
          <strong><?=htmlspecialchars($b['event_name'])?></strong><br>
          <small><?=htmlspecialchars($b['event_type'])?></small>
        </td>
        <td><?=htmlspecialchars($b['event_date'])?></td>
        <td>৳<?=htmlspecialchars($b['total_cost'])?></td>
        <td>
          <?php if($b['payment_status'] === 'Paid'): ?>
            <span style="color:#10b981;">✓ Paid</span>
          <?php else: ?>
            <span style="color:#f59e0b;">⏳ Pending</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if($b['payment_status'] === 'Unpaid'): ?>
            <form method="post" style="display:inline;">
              <input type="hidden" name="booking_id" value="<?=htmlspecialchars($b['booking_id'])?>">
              <select name="payment_method" required style="margin:0;padding:4px;width:auto;">
                <option value="">Payment Method</option>
                <option value="Bkash">Bkash</option>
                <option value="Nagad">Nagad</option>
                <option value="Rocket">Rocket</option>
                <option value="Bank Transfer">Bank Transfer</option>
                <option value="Cash">Cash</option>
                <option value="Card">Credit Card</option>
              </select>
              <button name="process_payment" style="background:#16a34a;padding:4px 8px;margin-left:4px;">Pay Now</button>
            </form>
          <?php else: ?>
            <span style="color:#10b981;">Completed</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>

  <?php if(!empty($paymentHistory)): ?>
  <div class="card">
    <h3>💳 Payment History</h3>
    <table class="table">
      <tr><th>Payment ID</th><th>Event</th><th>Date</th><th>Amount</th><th>Method</th><th>Details</th></tr>
      <?php foreach($paymentHistory as $p): ?>
      <tr>
        <td><?=htmlspecialchars($p['payment_id'])?></td>
        <td><?=htmlspecialchars($p['event_name'] ?: 'N/A')?></td>
        <td><?=htmlspecialchars($p['event_date'] ?: 'N/A')?></td>
        <td>৳<?=htmlspecialchars($p['ticket_earning'])?></td>
        <td><?=htmlspecialchars($p['payment_method'])?></td>
        <td>
          <small>
            Food: ৳<?=htmlspecialchars($p['food_cost'])?><br>
            Venue: ৳<?=htmlspecialchars($p['venue_cost'])?>
          </small>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
  <?php endif; ?>

  <div class="card">
    <h3>💡 Payment Information</h3>
    <div style="background:#1f2937;padding:12px;border-radius:8px;">
      <h4>Accepted Payment Methods:</h4>
      <ul style="margin:8px 0;padding-left:20px;">
        <li>📱 <strong>Mobile Banking:</strong> Bkash, Nagad, Rocket</li>
        <li>🏦 <strong>Bank Transfer:</strong> Direct bank account transfer</li>
        <li>💳 <strong>Credit/Debit Card:</strong> Visa, Mastercard</li>
        <li>💵 <strong>Cash:</strong> Pay at venue</li>
      </ul>
      <p style="margin:8px 0;"><strong>Note:</strong> Payment confirmation may take 24-48 hours for bank transfers.</p>
    </div>
  </div>
</div>
</body></html>