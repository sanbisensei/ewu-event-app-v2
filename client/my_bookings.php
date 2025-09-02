<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireClient();
$customer_id = $_SESSION['customer_id'];
$msg = '';
$error = '';

// Handle booking cancellation
if(isset($_POST['cancel_booking'])) {
    try {
        $booking_id = $_POST['booking_id'];
        
        // Check if booking belongs to this customer and event is in future
        $check = $pdo->prepare("
            SELECT b.*, e.event_date 
            FROM Bookings b 
            LEFT JOIN Events e ON b.event_id = e.event_id 
            WHERE b.booking_id = ? AND b.customer_id = ?
        ");
        $check->execute([$booking_id, $customer_id]);
        $booking = $check->fetch();
        
        if(!$booking) {
            $error = "Booking not found or doesn't belong to you";
        } elseif($booking['event_date'] < date('Y-m-d')) {
            $error = "Cannot cancel past events";
        } else {
            // Delete the booking
            $stmt = $pdo->prepare("DELETE FROM Bookings WHERE booking_id = ? AND customer_id = ?");
            $stmt->execute([$booking_id, $customer_id]);
            $msg = "Booking cancelled successfully";
        }
    } catch(PDOException $e) {
        $error = "Error cancelling booking: " . $e->getMessage();
    }
}

// Get all my bookings with event details
$myBookings = $pdo->prepare("
    SELECT b.*, e.event_name, e.event_date, e.event_type, e.ticket_cost, 
           v.venue_name, m.meal_name,
           CASE 
               WHEN e.event_date < CURDATE() THEN 'Completed'
               WHEN e.event_date = CURDATE() THEN 'Today'
               ELSE 'Upcoming'
           END as status
    FROM Bookings b 
    LEFT JOIN Events e ON b.event_id = e.event_id 
    LEFT JOIN Venues v ON e.venue_id = v.venue_id 
    LEFT JOIN Meals m ON e.meal_id = m.meal_id 
    WHERE b.customer_id = ? 
    ORDER BY e.event_date DESC
");
$myBookings->execute([$customer_id]);
$myBookings = $myBookings->fetchAll();

// Get total spent
$totalSpent = $pdo->prepare("SELECT SUM(total_cost) as total FROM Bookings WHERE customer_id = ?");
$totalSpent->execute([$customer_id]);
$totalSpent = $totalSpent->fetchColumn() ?: 0;

// Get my guests count
$guestCount = $pdo->prepare("SELECT COUNT(*) FROM Guests WHERE customer_id = ?");
$guestCount->execute([$customer_id]);
$guestCount = $guestCount->fetchColumn();
?>
<!doctype html>
<html><head><meta charset="utf-8"><link rel="stylesheet" href="../assets/style.css"><title>My Bookings</title></head><body>
<div class="container">
  <div class="nav">
  <a class="badge" href="dashboard.php">Dashboard</a>
  <a class="badge" href="book_event.php">Book Events</a>
  <a class="badge" href="profile.php">Profile</a>
  <a class="badge" href="payment.php">Payments</a>
  <a class="badge" href="feedback.php">Feedback</a>
  <a class="badge" href="../logout.php">Logout</a>
</div>
  
  <h1>My Bookings</h1>
  <?php if($msg): ?><div class="success"><?=htmlspecialchars($msg)?></div><?php endif; ?>
  <?php if($error): ?><div class="error"><?=htmlspecialchars($error)?></div><?php endif; ?>

  <div class="grid">
    <div class="card">
      <h3>📊 My Stats</h3>
      <p>Total Bookings: <strong><?=count($myBookings)?></strong></p>
      <p>Total Spent: <strong>৳<?=number_format($totalSpent, 2)?></strong></p>
      <p>Guests Added: <strong><?=$guestCount?></strong></p>
    </div>

    <div class="card">
      <h3>🎯 Quick Actions</h3>
      <a href="book_event.php" style="display:block;margin:8px 0;color:#93c5fd;text-decoration:none;padding:8px;background:#1f2937;border-radius:6px;">📅 Book New Event</a>
      <a href="feedback.php" style="display:block;margin:8px 0;color:#93c5fd;text-decoration:none;padding:8px;background:#1f2937;border-radius:6px;">⭐ Leave Feedback</a>
    </div>
  </div>

  <div class="card">
    <h3>📋 All My Bookings</h3>
    <?php if(empty($myBookings)): ?>
      <p>You haven't booked any events yet. <a href="book_event.php">Book your first event!</a></p>
    <?php else: ?>
    <table class="table">
      <tr><th>Booking ID</th><th>Event</th><th>Type</th><th>Date</th><th>Venue</th><th>Total Cost</th><th>Status</th><th>Actions</th></tr>
      <?php foreach($myBookings as $b): ?>
      <tr>
        <td><?=htmlspecialchars($b['booking_id'])?></td>
        <td>
          <strong><?=htmlspecialchars($b['event_name'])?></strong><br>
          <small><?=htmlspecialchars($b['event_id'])?></small>
        </td>
        <td><?=htmlspecialchars($b['event_type'])?></td>
        <td><?=htmlspecialchars($b['event_date'])?></td>
        <td><?=htmlspecialchars($b['venue_name'] ?: 'TBA')?></td>
        <td>৳<?=htmlspecialchars($b['total_cost'])?></td>
        <td>
          <?php if($b['status'] === 'Upcoming'): ?>
            <span style="color:#10b981;font-weight:bold;">Upcoming</span>
          <?php elseif($b['status'] === 'Today'): ?>
            <span style="color:#f59e0b;font-weight:bold;">Today</span>
          <?php else: ?>
            <span style="color:#6b7280;">Completed</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if($b['status'] === 'Upcoming'): ?>
            <form method="post" style="display:inline" onsubmit="return confirm('Cancel this booking? This cannot be undone.')">
              <input type="hidden" name="booking_id" value="<?=htmlspecialchars($b['booking_id'])?>">
              <button name="cancel_booking" style="background:#dc2626;padding:4px 8px;">Cancel</button>
            </form>
          <?php elseif($b['status'] === 'Completed'): ?>
            <a href="feedback.php?event_id=<?=htmlspecialchars($b['event_id'])?>" 
               style="background:#059669;color:white;padding:4px 8px;border-radius:4px;text-decoration:none;">Review</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>

  <?php if(!empty($myguests)): ?>
  <div class="card">
    <h3>👥 My Guests</h3>
    <table class="table">
      <tr><th>Guest Name</th><th>Contact</th><th>Event</th><th>Event Date</th></tr>
      <?php foreach($myguests as $g): ?>
      <tr>
        <td><?=htmlspecialchars($g['guest_name'])?></td>
        <td><?=htmlspecialchars($g['guest_contact'])?></td>
        <td><?=htmlspecialchars($g['event_name'])?></td>
        <td><?=htmlspecialchars($g['event_date'])?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
  <?php endif; ?>
</div>
</body></html>