<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

// Get comprehensive stats
$totalEvents = $pdo->query("SELECT COUNT(*) FROM Events")->fetchColumn();
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM Customers")->fetchColumn();
$totalVenues = $pdo->query("SELECT COUNT(*) FROM Venues")->fetchColumn();
$totalBookings = $pdo->query("SELECT COUNT(*) FROM Bookings")->fetchColumn();
$totalSponsors = $pdo->query("SELECT COUNT(*) FROM Sponsors")->fetchColumn();
$totalRevenue = $pdo->query("SELECT SUM(total_cost) FROM Bookings")->fetchColumn() ?: 0;
$totalFunding = $pdo->query("SELECT SUM(sponsor_funding) FROM Sponsors")->fetchColumn() ?: 0;

// Recent events
$recentEvents = $pdo->query("
    SELECT e.*, v.venue_name, 
           CASE 
               WHEN e.event_date < CURDATE() THEN 'Completed'
               WHEN e.event_date = CURDATE() THEN 'Today'
               ELSE 'Upcoming'
           END as status
    FROM Events e 
    LEFT JOIN Venues v ON e.venue_id = v.venue_id 
    ORDER BY e.event_date DESC 
    LIMIT 5
")->fetchAll();

// Recent bookings
$recentBookings = $pdo->query("
    SELECT b.*, e.event_name, c.customer_name 
    FROM Bookings b 
    LEFT JOIN Events e ON b.event_id = e.event_id 
    LEFT JOIN Customers c ON b.customer_id = c.customer_id 
    ORDER BY b.booking_date DESC 
    LIMIT 5
")->fetchAll();

// Upcoming events this week
$upcomingThisWeek = $pdo->query("
    SELECT COUNT(*) 
    FROM Events 
    WHERE event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
")->fetchColumn();

// Equipment status summary
$equipmentStats = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM Logistics 
    GROUP BY status
")->fetchAll();
?>
<!doctype html>
<html><head><meta charset="utf-8"><link rel="stylesheet" href="../assets/style.css"><title>Admin Dashboard</title></head><body>
<div class="container">
  <div class="nav">
    <a class="badge" href="events.php">Events</a>
    <a class="badge" href="venues.php">Venues</a>
    <a class="badge" href="meals.php">Meals</a>
    <a class="badge" href="bookings.php">Bookings</a>
    <a class="badge" href="sponsors.php">Sponsors</a>
    <a class="badge" href="logistics.php">Logistics</a>
    <a class="badge" href="cashflow.php">Financial Reports</a>
    <a class="badge" href="../logout.php">Logout</a>
  </div>
  
  <h1>Admin Dashboard</h1>
  <p>Welcome, <?=htmlspecialchars($_SESSION['manager_id'])?></p>

  <div class="grid">
    <div class="card">
      <h3>System Overview</h3>
      <div style="background:#1f2937;padding:12px;border-radius:8px;">
        <p>Total Events: <strong><?=$totalEvents?></strong></p>
        <p>Total Customers: <strong><?=$totalCustomers?></strong></p>
        <p>Total Venues: <strong><?=$totalVenues?></strong></p>
        <p>Total Bookings: <strong><?=$totalBookings?></strong></p>
        <p>Total Sponsors: <strong><?=$totalSponsors?></strong></p>
      </div>
    </div>

    <div class="card">
      <h3>Financial Summary</h3>
      <div style="background:#064e3b;padding:12px;border-radius:8px;">
        <p>Total Revenue: <strong style="color:#10b981;">৳<?=number_format($totalRevenue, 2)?></strong></p>
        <p>Sponsor Funding: <strong style="color:#3b82f6;">৳<?=number_format($totalFunding, 2)?></strong></p>
        <p>Events This Week: <strong style="color:#f59e0b;"><?=$upcomingThisWeek?></strong></p>
      </div>
    </div>

    <div class="card">
      <h3>Quick Actions</h3>
      <a href="events.php" style="display:block;margin:8px 0;color:#93c5fd;text-decoration:none;padding:8px;background:#1f2937;border-radius:6px;">Manage Events</a>
      <a href="venues.php" style="display:block;margin:8px 0;color:#93c5fd;text-decoration:none;padding:8px;background:#1f2937;border-radius:6px;">Manage Venues</a>
      <a href="bookings.php" style="display:block;margin:8px 0;color:#93c5fd;text-decoration:none;padding:8px;background:#1f2937;border-radius:6px;">View Bookings</a>
      <a href="sponsors.php" style="display:block;margin:8px 0;color:#93c5fd;text-decoration:none;padding:8px;background:#1f2937;border-radius:6px;">Manage Sponsors</a>
      <a href="cashflow.php" style="display:block;margin:8px 0;color:#93c5fd;text-decoration:none;padding:8px;background:#1f2937;border-radius:6px;">Financial Reports</a>
    </div>

    <?php if(!empty($equipmentStats)): ?>
    <div class="card">
      <h3>Equipment Status</h3>
      <?php foreach($equipmentStats as $eq): ?>
        <div style="display:flex;justify-content:space-between;padding:4px 0;">
          <span><?=htmlspecialchars($eq['status'])?></span>
          <strong><?=$eq['count']?> items</strong>
        </div>
      <?php endforeach; ?>
      <a href="logistics.php" style="color:#93c5fd;font-size:14px;">Manage Equipment →</a>
    </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3>Recent Events</h3>
    <?php if(empty($recentEvents)): ?>
      <p>No events found. <a href="events.php">Create your first event</a>!</p>
    <?php else: ?>
    <table class="table">
      <tr><th>ID</th><th>Name</th><th>Date</th><th>Venue</th><th>Status</th></tr>
      <?php foreach($recentEvents as $e): ?>
      <tr>
        <td><?=htmlspecialchars($e['event_id'])?></td>
        <td><?=htmlspecialchars($e['event_name'])?></td>
        <td><?=htmlspecialchars($e['event_date'])?></td>
        <td><?=htmlspecialchars($e['venue_name'] ?: 'TBA')?></td>
        <td>
          <?php if($e['status'] === 'Upcoming'): ?>
            <span style="color:#10b981;">Upcoming</span>
          <?php elseif($e['status'] === 'Today'): ?>
            <span style="color:#f59e0b;">Today</span>
          <?php else: ?>
            <span style="color:#6b7280;">Completed</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3>Recent Bookings</h3>
    <?php if(empty($recentBookings)): ?>
      <p>No bookings found. Customers will appear here when they book events.</p>
    <?php else: ?>
    <table class="table">
      <tr><th>Booking ID</th><th>Event</th><th>Customer</th><th>Total</th><th>Date</th></tr>
      <?php foreach($recentBookings as $b): ?>
      <tr>
        <td><?=htmlspecialchars($b['booking_id'])?></td>
        <td><?=htmlspecialchars($b['event_name'])?></td>
        <td><?=htmlspecialchars($b['customer_name'] ?: $b['customer_id'])?></td>
        <td>৳<?=htmlspecialchars($b['total_cost'])?></td>
        <td><?=htmlspecialchars($b['booking_date'])?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
</div>
</body></html>