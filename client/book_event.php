<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireClient();
$customer_id = $_SESSION['customer_id'];
$msg = '';
$error = '';

// Handle event booking
if(isset($_POST['book_event'])) {
    try {
        $event_id = $_POST['event_id'];
        $tickets = (int)$_POST['tickets'];
        
        // Get event details
        $event = $pdo->prepare("SELECT * FROM Events WHERE event_id = ?");
        $event->execute([$event_id]);
        $event = $event->fetch();
        
        if(!$event) {
            $error = "Event not found";
        } else {
            // Calculate total cost
            $total_cost = $event['ticket_cost'] * $tickets;
            
            // Generate booking ID
            $booking_id = 'B' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            
            // Check if booking ID already exists
            while(true) {
                $check = $pdo->prepare("SELECT booking_id FROM Bookings WHERE booking_id = ?");
                $check->execute([$booking_id]);
                if(!$check->fetch()) break;
                $booking_id = 'B' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            }
            
            // Create booking
            $stmt = $pdo->prepare("INSERT INTO Bookings (booking_id, booking_date, event_id, customer_id, total_cost) VALUES (?, CURDATE(), ?, ?, ?)");
            $stmt->execute([$booking_id, $event_id, $customer_id, $total_cost]);
            
            $msg = "Event booked successfully! Booking ID: $booking_id. Total: ৳$total_cost";
        }
    } catch(PDOException $e) {
        $error = "Booking failed: " . $e->getMessage();
    }
}

// Handle adding guests
if(isset($_POST['add_guest'])){
    try {
        // Generate guest ID
        $guest_id = 'G' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        // Check if guest ID already exists
        while(true) {
            $check = $pdo->prepare("SELECT guest_id FROM Guests WHERE guest_id = ?");
            $check->execute([$guest_id]);
            if(!$check->fetch()) break;
            $guest_id = 'G' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        }
        
        $stmt = $pdo->prepare("INSERT INTO Guests (guest_id, guest_name, guest_contact, event_id, customer_id) VALUES (?,?,?,?,?)");
        $stmt->execute([$guest_id, $_POST['guest_name'], $_POST['guest_contact'], $_POST['event_id'], $customer_id]);
        $msg = "Guest added successfully";
    } catch(PDOException $e) {
        $error = "Failed to add guest: " . $e->getMessage();
    }
}

// Get specific event if event_id is provided
$selected_event = null;
if(isset($_GET['event_id'])) {
    $stmt = $pdo->prepare("
        SELECT e.*, v.venue_name, v.venue_capacity, m.meal_name, m.meal_cost 
        FROM Events e 
        LEFT JOIN Venues v ON e.venue_id = v.venue_id 
        LEFT JOIN Meals m ON e.meal_id = m.meal_id 
        WHERE e.event_id = ?
    ");
    $stmt->execute([$_GET['event_id']]);
    $selected_event = $stmt->fetch();
}

// Get all upcoming events
$events = $pdo->query("
    SELECT e.*, v.venue_name, v.venue_capacity, m.meal_name 
    FROM Events e 
    LEFT JOIN Venues v ON e.venue_id = v.venue_id 
    LEFT JOIN Meals m ON e.meal_id = m.meal_id 
    WHERE e.event_date >= CURDATE()
    ORDER BY e.event_date ASC
")->fetchAll();

// Get my guests for this page
$myguests = $pdo->prepare("
    SELECT g.*, e.event_name, e.event_date 
    FROM Guests g 
    LEFT JOIN Events e ON g.event_id = e.event_id 
    WHERE g.customer_id = ? 
    ORDER BY e.event_date DESC
");
$myguests->execute([$customer_id]);
$myguests = $myguests->fetchAll();
?>
<!doctype html>
<html><head><meta charset="utf-8"><link rel="stylesheet" href="../assets/style.css"><title>Book Events</title></head><body>
<div class="container">
  <div class="nav">
  <a class="badge" href="dashboard.php">Dashboard</a>
  <a class="badge" href="profile.php">Profile</a>
  <a class="badge" href="my_bookings.php">My Bookings</a>
  <a class="badge" href="payment.php">Payments</a>
  <a class="badge" href="feedback.php">Feedback</a>
  <a class="badge" href="../logout.php">Logout</a>
</div>
  <h1>Book Events & Manage Guests</h1>
  <?php if($msg): ?><div class="success"><?=htmlspecialchars($msg)?></div><?php endif; ?>
  <?php if($error): ?><div class="error"><?=htmlspecialchars($error)?></div><?php endif; ?>

  <?php if($selected_event): ?>
  <div class="card" style="border: 2px solid #059669;">
    <h3>📅 Booking: <?=htmlspecialchars($selected_event['event_name'])?></h3>
    <div style="background:#064e3b;padding:12px;border-radius:8px;margin:10px 0;">
      <p><strong>Event:</strong> <?=htmlspecialchars($selected_event['event_name'])?></p>
      <p><strong>Type:</strong> <?=htmlspecialchars($selected_event['event_type'])?></p>
      <p><strong>Date:</strong> <?=htmlspecialchars($selected_event['event_date'])?></p>
      <p><strong>Venue:</strong> <?=htmlspecialchars($selected_event['venue_name'] ?: 'TBA')?></p>
      <p><strong>Capacity:</strong> <?=htmlspecialchars($selected_event['venue_capacity'] ?: 'N/A')?></p>
      <p><strong>Meal:</strong> <?=htmlspecialchars($selected_event['meal_name'] ?: 'No meal included')?></p>
      <p><strong>Price per ticket:</strong> ৳<?=htmlspecialchars($selected_event['ticket_cost'])?></p>
    </div>
    
    <form method="post" class="row">
      <input type="hidden" name="event_id" value="<?=htmlspecialchars($selected_event['event_id'])?>">
      <label>Number of Tickets</label>
      <input type="number" name="tickets" min="1" max="10" value="1" required 
             onchange="updateTotal(this.value, <?=$selected_event['ticket_cost']?>)">
      <div style="margin:10px 0;font-size:18px;font-weight:bold;">
        Total Cost: ৳<span id="totalCost"><?=$selected_event['ticket_cost']?></span>
      </div>
      <button name="book_event" style="background:#059669;font-size:16px;">🎫 Book This Event</button>
    </form>
  </div>
  <?php endif; ?>

  <div class="grid">
    <div class="card">
      <h3>🎉 Available Events</h3>
      <?php if(empty($events)): ?>
        <p>No upcoming events available.</p>
      <?php else: ?>
        <?php foreach($events as $e): ?>
        <div style="border:1px solid #374151;border-radius:8px;padding:12px;margin:8px 0;">
          <h4 style="margin:0 0 8px 0;"><?=htmlspecialchars($e['event_name'])?></h4>
          <p style="margin:4px 0;"><strong>Type:</strong> <?=htmlspecialchars($e['event_type'])?></p>
          <p style="margin:4px 0;"><strong>Date:</strong> <?=htmlspecialchars($e['event_date'])?></p>
          <p style="margin:4px 0;"><strong>Venue:</strong> <?=htmlspecialchars($e['venue_name'] ?: 'TBA')?></p>
          <p style="margin:4px 0;"><strong>Price:</strong> ৳<?=htmlspecialchars($e['ticket_cost'])?></p>
          <a href="?event_id=<?=htmlspecialchars($e['event_id'])?>" 
             style="background:#2563eb;color:white;padding:6px 12px;border-radius:4px;text-decoration:none;display:inline-block;margin-top:8px;">
             Select to Book
          </a>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="card">
      <h3>👥 Add Guest to Event</h3>
      <form method="post" class="row">
        <label>Guest Name</label><input name="guest_name" required />
        <label>Guest Contact</label><input name="guest_contact" placeholder="Phone number" />
        <label>Select Event</label>
        <select name="event_id" required>
          <option value="">-- Choose Event --</option>
          <?php foreach($events as $e): ?>
            <option value="<?=htmlspecialchars($e['event_id'])?>"><?=htmlspecialchars($e['event_name'].' - '.$e['event_date'])?></option>
          <?php endforeach; ?>
        </select>
        <button name="add_guest">Add Guest</button>
      </form>
    </div>
  </div>

  <?php if(!empty($myguests)): ?>
  <div class="card">
    <h3>👥 My Guests</h3>
    <table class="table">
      <tr><th>Name</th><th>Contact</th><th>Event</th><th>Event Date</th></tr>
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

<script>
function updateTotal(tickets, pricePerTicket) {
    const total = tickets * pricePerTicket;
    document.getElementById('totalCost').textContent = total;
}
</script>
</body></html>