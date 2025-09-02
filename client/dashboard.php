<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireClient();
$customer_id = $_SESSION['customer_id'];

// Get customer info
$customer = $pdo->prepare("SELECT * FROM Customers WHERE customer_id = ?");
$customer->execute([$customer_id]);
$customer = $customer->fetch();

// Get upcoming events with full details
$events = $pdo->query("
    SELECT e.*, v.venue_name, v.venue_capacity, m.meal_name, m.meal_cost,
           (v.venue_cost + (m.meal_cost * e.guest_count)) as estimated_total
    FROM Events e 
    LEFT JOIN Venues v ON e.venue_id = v.venue_id 
    LEFT JOIN Meals m ON e.meal_id = m.meal_id 
    WHERE e.event_date >= CURDATE()
    ORDER BY e.event_date ASC
")->fetchAll();

// Get my bookings
$myBookings = $pdo->prepare("
    SELECT b.*, e.event_name, e.event_date, e.event_type, v.venue_name 
    FROM Bookings b 
    LEFT JOIN Events e ON b.event_id = e.event_id 
    LEFT JOIN Venues v ON e.venue_id = v.venue_id 
    WHERE b.customer_id = ? 
    ORDER BY e.event_date DESC
");
$myBookings->execute([$customer_id]);
$myBookings = $myBookings->fetchAll();

// Get my guests
$myGuests = $pdo->prepare("
    SELECT g.*, e.event_name, e.event_date 
    FROM Guests g 
    LEFT JOIN Events e ON g.event_id = e.event_id 
    WHERE g.customer_id = ? 
    ORDER BY e.event_date DESC
");
$myGuests->execute([$customer_id]);
$myGuests = $myGuests->fetchAll();
?>
<!doctype html>
<html><head><meta charset="utf-8"><link rel="stylesheet" href="../assets/style.css"><title>Client Dashboard</title></head><body>
<div class="container">
  <div class="nav">
  <a class="badge" href="profile.php">Profile</a>
  <a class="badge" href="book_event.php">Book Events</a>
  <a class="badge" href="my_bookings.php">My Bookings</a>
  <a class="badge" href="payment.php">Payments</a>
  <a class="badge" href="feedback.php">Feedback</a>
  <a class="badge" href="../logout.php">Logout</a>
</div>
  
  <h1>Welcome, <?=htmlspecialchars($customer['customer_name'] ?: $customer_id)?></h1>

  <div class="grid">
    <div class="card">
      <h3>Quick Stats</h3>
      <p>My Bookings: <strong><?=count($myBookings)?></strong></p>
      <p>My Guests: <strong><?=count($myGuests)?></strong></p>
      <p>Available Events: <strong><?=count($events)?></strong></p>
    </div>

    <div class="card">
      <h3>Quick Actions</h3>
      <a href="book_event.php" style="display:block;margin:8px 0;color:#93c5fd;text-decoration:none;padding:8px;background:#1f2937;border-radius:6px;">📅 Book New Event</a>
      <a href="my_bookings.php" style="display:block;margin:8px 0;color:#93c5fd;text-decoration:none;padding:8px;background:#1f2937;border-radius:6px;">📋 View My Bookings</a>
      <a href="feedback.php" style="display:block;margin:8px 0;color:#93c5fd;text-decoration:none;padding:8px;background:#1f2937;border-radius:6px;">⭐ Leave Feedback</a>
    </div>
  </div>

  <div class="card">
    <h3>Upcoming Events</h3>
    <?php if(empty($events)): ?>
      <p>No upcoming events available.</p>
    <?php else: ?>
    <table class="table">
      <tr><th>Event</th><th>Type</th><th>Date</th><th>Venue</th><th>Capacity</th><th>Ticket Price</th><th>Action</th></tr>
      <?php foreach($events as $e): ?>
      <tr>
        <td>
          <strong><?=htmlspecialchars($e['event_name'])?></strong><br>
          <small>ID: <?=htmlspecialchars($e['event_id'])?></small>
        </td>
        <td><?=htmlspecialchars($e['event_type'])?></td>
        <td><?=htmlspecialchars($e['event_date'])?></td>
        <td><?=htmlspecialchars($e['venue_name'] ?: 'TBA')?></td>
        <td><?=htmlspecialchars($e['venue_capacity'] ?: 'N/A')?></td>
        <td>৳<?=htmlspecialchars($e['ticket_cost'])?></td>
        <td>
          <a href="book_event.php?event_id=<?=htmlspecialchars($e['event_id'])?>" 
             style="background:#16a34a;color:white;padding:4px 8px;border-radius:4px;text-decoration:none;">Book Now</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>

  <?php if(!empty($myBookings)): ?>
  <div class="card">
    <h3>My Recent Bookings</h3>
    <table class="table">
      <tr><th>Booking ID</th><th>Event</th><th>Date</th><th>Total Cost</th><th>Status</th></tr>
      <?php foreach(array_slice($myBookings, 0, 3) as $b): ?>
      <tr>
        <td><?=htmlspecialchars($b['booking_id'])?></td>
        <td><?=htmlspecialchars($b['event_name'])?></td>
        <td><?=htmlspecialchars($b['event_date'])?></td>
        <td>৳<?=htmlspecialchars($b['total_cost'])?></td>
        <td>
          <?php if($b['event_date'] >= date('Y-m-d')): ?>
            <span style="color:#10b981">Upcoming</span>
          <?php else: ?>
            <span style="color:#6b7280">Completed</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    <p><a href="my_bookings.php">View all bookings →</a></p>
  </div>
  <?php endif; ?>
</div>
</body></html>