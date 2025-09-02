<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$msg = '';
$error = '';

if(isset($_POST['create'])){
    try {
        $stmt = $pdo->prepare("INSERT INTO Sponsors (sponsor_id, sponsor_address, sponsor_funding, event_id) VALUES (?,?,?,?)");
        $stmt->execute([
            $_POST['sponsor_id'],
            $_POST['sponsor_address'], 
            $_POST['sponsor_funding'],
            $_POST['event_id']
        ]);
        $msg = 'Sponsor created successfully';
    } catch(PDOException $e) {
        if($e->getCode() == 23000) {
            $error = 'Error: Sponsor ID already exists or Event ID not found';
        } else {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

if(isset($_POST['delete'])){
    try {
        $stmt = $pdo->prepare("DELETE FROM Sponsors WHERE sponsor_id = ?");
        $stmt->execute([$_POST['sponsor_id']]);
        $msg = 'Sponsor deleted successfully';
    } catch(PDOException $e) {
        $error = 'Error deleting sponsor: ' . $e->getMessage();
    }
}

// Get all events for dropdown
$events = $pdo->query("SELECT event_id, event_name, event_date FROM Events ORDER BY event_date DESC")->fetchAll();

// Get all sponsors with event details
$sponsors = $pdo->query("
    SELECT s.*, e.event_name, e.event_date, e.event_type 
    FROM Sponsors s 
    LEFT JOIN Events e ON s.event_id = e.event_id 
    ORDER BY s.sponsor_id
")->fetchAll();

// Calculate total sponsorship funding
$totalFunding = $pdo->query("SELECT SUM(sponsor_funding) FROM Sponsors")->fetchColumn() ?: 0;
?>
<!doctype html>
<html><head><meta charset="utf-8"><link rel="stylesheet" href="../assets/style.css"><title>Admin - Sponsors</title></head><body>
<div class="container">
  <div class="nav">
    <a class="badge" href="dashboard.php">Dashboard</a>
    <a class="badge" href="events.php">Events</a>
    <a class="badge" href="venues.php">Venues</a>
    <a class="badge" href="meals.php">Meals</a>
    <a class="badge" href="bookings.php">Bookings</a>
    <a class="badge" href="logistics.php">Logistics</a>
    <a class="badge" href="cashflow.php">Financial Reports</a>
    <a class="badge" href="../logout.php">Logout</a>
  </div>
  
  <h1>💰 Sponsors Management</h1>
  <?php if($msg): ?><div class="success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="grid">
    <div class="card">
      <h3>📊 Sponsorship Statistics</h3>
      <p>Total Sponsors: <strong><?=count($sponsors)?></strong></p>
      <p>Total Funding: <strong>৳<?=number_format($totalFunding, 2)?></strong></p>
      <p>Average Funding: <strong>৳<?=count($sponsors) > 0 ? number_format($totalFunding/count($sponsors), 2) : '0.00'?></strong></p>
    </div>

    <div class="card">
      <h3>➕ Add New Sponsor</h3>
      <form method="post" class="row">
        <label>Sponsor ID</label>
        <input name="sponsor_id" required placeholder="e.g. S021" />
        
        <label>Sponsor Address</label>
        <textarea name="sponsor_address" rows="2" placeholder="Company address"></textarea>
        
        <label>Funding Amount</label>
        <input type="number" step="0.01" name="sponsor_funding" required placeholder="0.00" />
        
        <label>Assign to Event</label>
        <select name="event_id" required>
          <option value="">-- Select Event --</option>
          <?php foreach($events as $e): ?>
            <option value="<?=htmlspecialchars($e['event_id'])?>">
              <?=htmlspecialchars($e['event_id'].' - '.$e['event_name'].' ('.$e['event_date'].')')?>
            </option>
          <?php endforeach; ?>
        </select>
        
        <button name="create" style="background:#059669;">Add Sponsor</button>
      </form>
    </div>
  </div>

  <div class="card">
    <h3>💼 All Sponsors</h3>
    <?php if(empty($sponsors)): ?>
      <p>No sponsors found. Add the first sponsor above!</p>
    <?php else: ?>
    <table class="table">
      <tr><th>Sponsor ID</th><th>Address</th><th>Funding</th><th>Event</th><th>Event Date</th><th>Actions</th></tr>
      <?php foreach($sponsors as $s): ?>
      <tr>
        <td><?=htmlspecialchars($s['sponsor_id'])?></td>
        <td><?=htmlspecialchars($s['sponsor_address'])?></td>
        <td>৳<?=htmlspecialchars($s['sponsor_funding'])?></td>
        <td>
          <strong><?=htmlspecialchars($s['event_name'] ?: 'Event not found')?></strong><br>
          <small><?=htmlspecialchars($s['event_id'])?> - <?=htmlspecialchars($s['event_type'] ?: '')?></small>
        </td>
        <td><?=htmlspecialchars($s['event_date'] ?: 'N/A')?></td>
        <td>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete this sponsor?')">
            <input type="hidden" name="sponsor_id" value="<?=htmlspecialchars($s['sponsor_id'])?>"/>
            <button name="delete" style="background:#dc2626;padding:4px 8px;">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3>📈 Sponsorship by Event</h3>
    <?php 
    $eventFunding = $pdo->query("
        SELECT e.event_id, e.event_name, e.event_date, 
               COUNT(s.sponsor_id) as sponsor_count,
               COALESCE(SUM(s.sponsor_funding), 0) as total_funding
        FROM Events e 
        LEFT JOIN Sponsors s ON e.event_id = s.event_id 
        GROUP BY e.event_id, e.event_name, e.event_date 
        HAVING sponsor_count > 0
        ORDER BY total_funding DESC
    ")->fetchAll();
    ?>
    
    <?php if(empty($eventFunding)): ?>
      <p>No events have sponsors yet.</p>
    <?php else: ?>
    <table class="table">
      <tr><th>Event</th><th>Date</th><th>Sponsors</th><th>Total Funding</th></tr>
      <?php foreach($eventFunding as $ef): ?>
      <tr>
        <td>
          <strong><?=htmlspecialchars($ef['event_name'])?></strong><br>
          <small><?=htmlspecialchars($ef['event_id'])?></small>
        </td>
        <td><?=htmlspecialchars($ef['event_date'])?></td>
        <td><?=htmlspecialchars($ef['sponsor_count'])?></td>
        <td>৳<?=number_format($ef['total_funding'], 2)?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
</div>
</body></html>