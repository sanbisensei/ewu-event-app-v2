<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

// Get financial statistics
$totalRevenue = $pdo->query("SELECT SUM(ticket_earning) FROM Cashflow")->fetchColumn() ?: 0;
$totalExpenses = $pdo->query("SELECT SUM(food_cost + venue_cost) FROM Cashflow")->fetchColumn() ?: 0;
$totalSponsorship = $pdo->query("SELECT SUM(sponsor_funding) FROM Sponsors")->fetchColumn() ?: 0;
$netProfit = $totalRevenue + $totalSponsorship - $totalExpenses;

// Get payment method breakdown
$paymentMethods = $pdo->query("
    SELECT payment_method, COUNT(*) as count, SUM(ticket_earning) as total 
    FROM Cashflow 
    GROUP BY payment_method 
    ORDER BY total DESC
")->fetchAll();

// Get recent transactions
$recentTransactions = $pdo->query("
    SELECT cf.*, c.customer_name, b.booking_id, e.event_name 
    FROM Cashflow cf 
    LEFT JOIN Customers c ON cf.customer_id = c.customer_id 
    LEFT JOIN Bookings b ON b.customer_id = cf.customer_id AND b.total_cost = cf.ticket_earning
    LEFT JOIN Events e ON b.event_id = e.event_id 
    ORDER BY cf.payment_id DESC 
    LIMIT 10
")->fetchAll();

// Get all cashflow records
$allCashflow = $pdo->query("
    SELECT cf.*, c.customer_name, b.booking_id, e.event_name, e.event_date,
           COALESCE(s.total_sponsor_funding, 0) as actual_sponsor_funding
    FROM Cashflow cf 
    LEFT JOIN Customers c ON cf.customer_id = c.customer_id 
    LEFT JOIN Bookings b ON b.customer_id = cf.customer_id AND b.total_cost = cf.ticket_earning
    LEFT JOIN Events e ON b.event_id = e.event_id 
    LEFT JOIN (
        SELECT event_id, SUM(sponsor_funding) as total_sponsor_funding 
        FROM Sponsors 
        GROUP BY event_id
    ) s ON e.event_id = s.event_id
    ORDER BY cf.payment_id DESC
")->fetchAll();

// Get monthly revenue trend (last 6 months)
$monthlyRevenue = $pdo->query("
    SELECT 
        DATE_FORMAT(b.booking_date, '%Y-%m') as month,
        SUM(b.total_cost) as revenue,
        COUNT(*) as bookings
    FROM Bookings b 
    WHERE b.booking_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(b.booking_date, '%Y-%m')
    ORDER BY month DESC
")->fetchAll();
?>
<!doctype html>
<html><head><meta charset="utf-8"><link rel="stylesheet" href="../assets/style.css"><title>Admin - Financial Reports</title></head><body>
<div class="container">
  <div class="nav">
    <a class="badge" href="dashboard.php">Dashboard</a>
    <a class="badge" href="events.php">Events</a>
    <a class="badge" href="venues.php">Venues</a>
    <a class="badge" href="meals.php">Meals</a>
    <a class="badge" href="bookings.php">Bookings</a>
    <a class="badge" href="sponsors.php">Sponsors</a>
    <a class="badge" href="logistics.php">Logistics</a>
    <a class="badge" href="../logout.php">Logout</a>
  </div>
  
  <h1>💰 Financial Reports & Cashflow</h1>

  <div class="grid">
    <div class="card">
      <h3>📊 Financial Overview</h3>
      <div style="background:#1f2937;padding:12px;border-radius:8px;">
        <p>Total Revenue: <strong style="color:#10b981;">৳<?=number_format($totalRevenue, 2)?></strong></p>
        <p>Total Expenses: <strong style="color:#dc2626;">৳<?=number_format($totalExpenses, 2)?></strong></p>
        <p>Sponsorship: <strong style="color:#3b82f6;">৳<?=number_format($totalSponsorship, 2)?></strong></p>
        <hr style="border:1px solid #374151;margin:8px 0;">
        <p>Net Profit: <strong style="color:<?=$netProfit >= 0 ? '#10b981' : '#dc2626'?>;">৳<?=number_format($netProfit, 2)?></strong></p>
      </div>
    </div>

    <div class="card">
      <h3>💳 Payment Methods</h3>
      <?php if(empty($paymentMethods)): ?>
        <p>No payment data available.</p>
      <?php else: ?>
        <?php foreach($paymentMethods as $pm): ?>
        <div style="display:flex;justify-content:space-between;padding:8px;background:#1f2937;border-radius:4px;margin:4px 0;">
          <span><?=htmlspecialchars($pm['payment_method'])?></span>
          <span>
            <strong><?=$pm['count']?> payments</strong> | 
            <strong>৳<?=number_format($pm['total'], 2)?></strong>
          </span>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <?php if(!empty($monthlyRevenue)): ?>
  <div class="card">
    <h3>📈 Monthly Revenue Trend</h3>
    <table class="table">
      <tr><th>Month</th><th>Bookings</th><th>Revenue</th></tr>
      <?php foreach($monthlyRevenue as $mr): ?>
      <tr>
        <td><?=htmlspecialchars($mr['month'])?></td>
        <td><?=htmlspecialchars($mr['bookings'])?></td>
        <td>৳<?=number_format($mr['revenue'], 2)?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
  <?php endif; ?>

  <div class="card">
    <h3>💸 Recent Transactions</h3>
    <?php if(empty($recentTransactions)): ?>
      <p>No transactions found.</p>
    <?php else: ?>
    <table class="table">
      <tr><th>Payment ID</th><th>Customer</th><th>Event</th><th>Amount</th><th>Method</th><th>Details</th></tr>
      <?php foreach($recentTransactions as $rt): ?>
      <tr>
        <td><?=htmlspecialchars($rt['payment_id'])?></td>
        <td>
          <strong><?=htmlspecialchars($rt['customer_name'] ?: 'Unknown')?></strong><br>
          <small><?=htmlspecialchars($rt['customer_id'])?></small>
        </td>
        <td>
          <strong><?=htmlspecialchars($rt['event_name'] ?: 'N/A')?></strong><br>
          <small><?=htmlspecialchars($rt['booking_id'] ?: 'No booking')?></small>
        </td>
        <td>৳<?=htmlspecialchars($rt['ticket_earning'])?></td>
        <td><?=htmlspecialchars($rt['payment_method'])?></td>
        <td>
          <small>
            Food: ৳<?=htmlspecialchars($rt['food_cost'])?><br>
            Venue: ৳<?=htmlspecialchars($rt['venue_cost'])?><br>
            Sponsor: ৳<?=htmlspecialchars($rt['sponsor_funding'])?>
          </small>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3>📋 All Cashflow Records</h3>
    <?php if(empty($allCashflow)): ?>
      <p>No cashflow records found.</p>
    <?php else: ?>
    <div style="max-height:400px;overflow-y:auto;">
      <table class="table">
        <tr><th>Payment ID</th><th>Customer</th><th>Event</th><th>Date</th><th>Revenue</th><th>Expenses</th><th>Sponsorship</th><th>Method</th></tr>
        <?php foreach($allCashflow as $cf): ?>
        <tr>
          <td><?=htmlspecialchars($cf['payment_id'])?></td>
          <td><?=htmlspecialchars($cf['customer_name'] ?: $cf['customer_id'])?></td>
          <td><?=htmlspecialchars($cf['event_name'] ?: 'N/A')?></td>
          <td><?=htmlspecialchars($cf['event_date'] ?: 'N/A')?></td>
          <td style="color:#10b981;">৳<?=htmlspecialchars($cf['ticket_earning'])?></td>
          <td style="color:#dc2626;">৳<?=number_format($cf['food_cost'] + $cf['venue_cost'], 2)?></td>
          <td style="color:#3b82f6;">৳<?=number_format($cf['actual_sponsor_funding'], 2)?></td>
          <td><?=htmlspecialchars($cf['payment_method'])?></td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>
</body></html>
