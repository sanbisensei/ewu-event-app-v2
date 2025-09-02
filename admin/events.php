<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$msg='';
$error='';

if(isset($_POST['create'])){
  try {
    // Validate that venue_id and meal_id exist (allow NULL)
    $venue_id = !empty($_POST['venue_id']) ? $_POST['venue_id'] : null;
    $meal_id = !empty($_POST['meal_id']) ? $_POST['meal_id'] : null;
    
    $stmt=$pdo->prepare("INSERT INTO Events (event_id,event_name,event_type,event_date,venue_id,meal_id,guest_count,ticket_cost) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([
      $_POST['event_id'],
      $_POST['event_name'],
      $_POST['event_type'],
      $_POST['event_date'],
      $venue_id,
      $meal_id,
      $_POST['guest_count'],
      $_POST['ticket_cost']
    ]);
    $msg='Event created successfully';
  } catch(PDOException $e) {
    if($e->getCode() == 23000) {
      $error = 'Foreign key error: Please ensure the Venue ID and Meal ID exist, or leave them blank.';
    } else {
      $error = 'Error: ' . $e->getMessage();
    }
  }
}

if(isset($_POST['delete'])){
  try {
    $stmt=$pdo->prepare("DELETE FROM Events WHERE event_id=?");
    $stmt->execute([$_POST['event_id']]);
    $msg='Event deleted successfully';
  } catch(PDOException $e) {
    $error = 'Error deleting event: ' . $e->getMessage();
  }
}

// Get available venues and meals for dropdowns
$venues = $pdo->query("SELECT venue_id, venue_name FROM Venues ORDER BY venue_name")->fetchAll();
$meals = $pdo->query("SELECT meal_id, meal_name FROM Meals ORDER BY meal_name")->fetchAll();

$rows=$pdo->query("SELECT e.*, v.venue_name, m.meal_name FROM Events e LEFT JOIN Venues v ON e.venue_id=v.venue_id LEFT JOIN Meals m ON e.meal_id=m.meal_id ORDER BY e.event_date DESC")->fetchAll();
?>
<!doctype html>
<html><head><meta charset="utf-8"><link rel="stylesheet" href="../assets/style.css"><title>Events</title></head><body>
<div class="container">
  <div class="nav">
  <a class="badge" href="dashboard.php">Dashboard</a>
  <a class="badge" href="venues.php">Venues</a>
  <a class="badge" href="meals.php">Meals</a>
  <a class="badge" href="bookings.php">Bookings</a>
  <a class="badge" href="sponsors.php">Sponsors</a>
  <a class="badge" href="logistics.php">Logistics</a>
  <a class="badge" href="cashflow.php">Financial Reports</a>
  <a class="badge" href="../logout.php">Logout</a>
</div>
  <h1>Events</h1>
  <?php if($msg): ?><div class="success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  
  <div class="card">
    <h3>Create Event</h3>
    <form method="post" class="row">
      <label>Event ID</label><input name="event_id" required placeholder="e.g. EV02" />
      <label>Event Name</label><input name="event_name" required placeholder="e.g. Annual Conference" />
      
      <label>Event Type</label>
      <select name="event_type" required>
        <option value="ESPORTS">ESPORTS</option>
        <option value="PRESENTATION">PRESENTATION</option>
        <option value="PRIZE GIVING">PRIZE GIVING</option>
        <option value="MEETING">Meeting</option>
        <option value="SEMINAR">Seminar</option>
        <option value="WORKSHOP">Workshop</option>
        <option value="ENTERTAINMENT">ENTERTAINMENT</option>
      </select>
      
      <label>Event Date</label><input type="date" name="event_date" required />
      
      <label>Venue</label>
      <select name="venue_id">
        <option value="">-- Select Venue (Optional) --</option>
        <?php foreach($venues as $v): ?>
          <option value="<?=htmlspecialchars($v['venue_id'])?>"><?=htmlspecialchars($v['venue_id'].' - '.$v['venue_name'])?></option>
        <?php endforeach; ?>
      </select>
      
      <label>Meal Package</label>
      <select name="meal_id">
        <option value="">-- Select Meal (Optional) --</option>
        <?php foreach($meals as $m): ?>
          <option value="<?=htmlspecialchars($m['meal_id'])?>"><?=htmlspecialchars($m['meal_id'].' - '.$m['meal_name'])?></option>
        <?php endforeach; ?>
      </select>
      
      <label>Guest Count</label><input type="number" name="guest_count" min="0" />
      <label>Ticket Cost</label><input type="number" step="0.01" name="ticket_cost" min="0" />
      <button name="create">Create Event</button>
    </form>
  </div>

  <div class="card">
    <h3>All Events</h3>
    <?php if(empty($rows)): ?>
      <p>No events found. Create your first event above!</p>
    <?php else: ?>
    <table class="table">
      <tr><th>ID</th><th>Name</th><th>Type</th><th>Date</th><th>Venue</th><th>Meal</th><th>Guests</th><th>Ticket</th><th>Actions</th></tr>
      <?php foreach($rows as $r): ?>
      <tr>
        <td><?=htmlspecialchars($r['event_id'])?></td>
        <td><?=htmlspecialchars($r['event_name'])?></td>
        <td><?=htmlspecialchars($r['event_type'])?></td>
        <td><?=htmlspecialchars($r['event_date'])?></td>
        <td><?=htmlspecialchars($r['venue_name'] ?: 'No venue')?></td>
        <td><?=htmlspecialchars($r['meal_name'] ?: 'No meal')?></td>
        <td><?=htmlspecialchars($r['guest_count'])?></td>
        <td><?=htmlspecialchars($r['ticket_cost'])?></td>
        <td>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete this event?')">
            <input type="hidden" name="event_id" value="<?=htmlspecialchars($r['event_id'])?>"/>
            <button name="delete" style="background:#dc2626">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
</div>
</body></html>