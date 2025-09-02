<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$msg = '';
$error = '';

if(isset($_POST['create'])){
    try {
        // Validate that event_id and customer_id exist
        $event_id = $_POST['event_id'];
        $customer_id = $_POST['customer_id'];
        
        // Check if event exists
        $eventCheck = $pdo->prepare("SELECT event_id FROM Events WHERE event_id = ?");
        $eventCheck->execute([$event_id]);
        if(!$eventCheck->fetch()) {
            throw new Exception("Event ID '$event_id' does not exist");
        }
        
        // Check if customer exists
        $customerCheck = $pdo->prepare("SELECT customer_id FROM Customers WHERE customer_id = ?");
        $customerCheck->execute([$customer_id]);
        if(!$customerCheck->fetch()) {
            throw new Exception("Customer ID '$customer_id' does not exist");
        }
        
        // Generate booking ID if not provided
        $booking_id = $_POST['booking_id'];
        if(empty($booking_id)) {
            $booking_id = 'B' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            
            // Ensure unique booking ID
            while(true) {
                $check = $pdo->prepare("SELECT booking_id FROM Bookings WHERE booking_id = ?");
                $check->execute([$booking_id]);
                if(!$check->fetch()) break;
                $booking_id = 'B' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO Bookings (booking_id, booking_date, event_id, customer_id, total_cost) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $booking_id,
            $_POST['booking_date'],
            $event_id,
            $customer_id,
            $_POST['total_cost']
        ]);
        $msg = 'Booking created successfully with ID: ' . $booking_id;
    } catch(PDOException $e) {
        if($e->getCode() == 23000) {
            $error = 'Database constraint error: Please ensure Event ID and Customer ID exist, or use the dropdowns below.';
        } else {
            $error = 'Database error: ' . $e->getMessage();
        }
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle booking deletion
if(isset($_POST['delete'])){
    try {
        $stmt = $pdo->prepare("DELETE FROM Bookings WHERE booking_id = ?");
        $stmt->execute([$_POST['booking_id']]);
        $msg = 'Booking deleted successfully';
    } catch(PDOException $e) {
        $error = 'Error deleting booking: ' . $e->getMessage();
    }
}

// Get available events and customers for dropdowns
$events = $pdo->query("SELECT event_id, event_name, event_date, ticket_cost FROM Events ORDER BY event_date ASC")->fetchAll();
$customers = $pdo->query("SELECT customer_id, customer_name FROM Customers ORDER BY customer_name ASC")->fetchAll();

// Get all bookings with details
$rows = $pdo->query("
    SELECT b.*, e.event_name, e.event_date, e.ticket_cost, c.customer_name,
           CASE 
               WHEN e.event_date < CURDATE() THEN 'Completed'
               WHEN e.event_date = CURDATE() THEN 'Today'
               ELSE 'Upcoming'
           END as status
    FROM Bookings b 
    LEFT JOIN Events e ON b.event_id = e.event_id 
    LEFT JOIN Customers c ON b.customer_id = c.customer_id 
    ORDER BY b.booking_date DESC
")->fetchAll();

// Get booking statistics
$totalBookings = count($rows);
$totalRevenue = $pdo->query("SELECT SUM(total_cost) FROM Bookings")->fetchColumn() ?: 0;
$upcomingBookings = $pdo->query("SELECT COUNT(*) FROM Bookings b LEFT JOIN Events e ON b.event_id = e.event_id WHERE e.event_date >= CURDATE()")->fetchColumn();
?>
<!doctype html>
<html><head><meta charset="utf-8"><link rel="stylesheet" href="../assets/style.css"><title>Admin - Bookings</title></head><body>
<div class="container">
  <div class="nav">
  <a class="badge" href="dashboard.php">Dashboard</a>
  <a class="badge" href="events.php">Events</a>
  <a class="badge" href="venues.php">Venues</a>
  <a class="badge" href="meals.php">Meals</a>
  <a class="badge" href="sponsors.php">Sponsors</a>
  <a class="badge" href="logistics.php">Logistics</a>
  <a class="badge" href="cashflow.php">Financial Reports</a>
  <a class="badge" href="../logout.php">Logout</a>
</div>
  
  <h1>📋 Bookings Management</h1>
  <?php if($msg): ?><div class="success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="grid">
    <div class="card">
      <h3>📊 Booking Statistics</h3>
      <p>Total Bookings: <strong><?=$totalBookings?></strong></p>
      <p>Total Revenue: <strong>৳<?=number_format($totalRevenue, 2)?></strong></p>
      <p>Upcoming Bookings: <strong><?=$upcomingBookings?></strong></p>
    </div>

    <div class="card">
      <h3>➕ Create New Booking</h3>
      <form method="post" class="row">
        <label>Booking ID (optional)</label>
        <input name="booking_id" placeholder="Leave blank for auto-generation" />
        
        <label>Booking Date</label>
        <input type="date" name="booking_date" value="<?=date('Y-m-d')?>" required />
        
        <label>Select Event</label>
        <select name="event_id" required onchange="updateCost()">
          <option value="">-- Choose Event --</option>
          <?php foreach($events as $e): ?>
            <option value="<?=htmlspecialchars($e['event_id'])?>" 
                    data-cost="<?=htmlspecialchars($e['ticket_cost'])?>">
              <?=htmlspecialchars($e['event_id'].' - '.$e['event_name'].' ('.$e['event_date'].') - ৳'.$e['ticket_cost'])?>
            </option>
          <?php endforeach; ?>
        </select>
        
        <label>Select Customer</label>
        <select name="customer_id" required>
          <option value="">-- Choose Customer --</option>
          <?php foreach($customers as $c): ?>
            <option value="<?=htmlspecialchars($c['customer_id'])?>">
              <?=htmlspecialchars($c['customer_id'].' - '.$c['customer_name'])?>
            </option>
          <?php endforeach; ?>
        </select>
        
        <label>Number of Tickets</label>
        <input type="number" name="tickets" min="1" value="1" onchange="updateCost()" />
        
        <label>Total Cost</label>
        <input type="number" step="0.01" name="total_cost" id="totalCost" required />
        
        <button name="create" style="background:#059669;">Create Booking</button>
      </form>
    </div>
  </div>

  <div class="card">
    <h3>📋 All Bookings</h3>
    <?php if(empty($rows)): ?>
      <p>No bookings found. Create the first booking above!</p>
    <?php else: ?>
    <table class="table">
      <tr><th>Booking ID</th><th>Date</th><th>Event</th><th>Customer</th><th>Total</th><th>Status</th><th>Actions</th></tr>
      <?php foreach($rows as $r): ?>
      <tr>
        <td><?=htmlspecialchars($r['booking_id'])?></td>
        <td><?=htmlspecialchars($r['booking_date'])?></td>
        <td>
          <strong><?=htmlspecialchars($r['event_name'])?></strong><br>
          <small><?=htmlspecialchars($r['event_id'])?> - <?=htmlspecialchars($r['event_date'])?></small>
        </td>
        <td>
          <strong><?=htmlspecialchars($r['customer_name'])?></strong><br>
          <small><?=htmlspecialchars($r['customer_id'])?></small>
        </td>
        <td>৳<?=htmlspecialchars($r['total_cost'])?></td>
        <td>
          <?php if($r['status'] === 'Upcoming'): ?>
            <span style="color:#10b981;font-weight:bold;">Upcoming</span>
          <?php elseif($r['status'] === 'Today'): ?>
            <span style="color:#f59e0b;font-weight:bold;">Today</span>
          <?php else: ?>
            <span style="color:#6b7280;">Completed</span>
          <?php endif; ?>
        </td>
        <td>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete this booking? This will also remove associated guests.')">
            <input type="hidden" name="booking_id" value="<?=htmlspecialchars($r['booking_id'])?>"/>
            <button name="delete" style="background:#dc2626;padding:4px 8px;">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
</div>

<script>
function updateCost() {
    const eventSelect = document.querySelector('select[name="event_id"]');
    const ticketsInput = document.querySelector('input[name="tickets"]');
    const totalCostInput = document.getElementById('totalCost');
    
    if(eventSelect.value && ticketsInput.value) {
        const selectedOption = eventSelect.options[eventSelect.selectedIndex];
        const ticketCost = parseFloat(selectedOption.dataset.cost || 0);
        const tickets = parseInt(ticketsInput.value || 1);
        const total = ticketCost * tickets;
        totalCostInput.value = total.toFixed(2);
    }
}
</script>
</body></html>