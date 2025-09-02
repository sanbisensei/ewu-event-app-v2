<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$msg = '';
$error = '';

if(isset($_POST['create'])){
    try {
        $stmt = $pdo->prepare("INSERT INTO Logistics (venue_id, object_type, quantity, status) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity), status=VALUES(status)");
        $stmt->execute([
            $_POST['venue_id'],
            $_POST['object_type'],
            $_POST['quantity'],
            $_POST['status']
        ]);
        $msg = 'Equipment added/updated successfully';
    } catch(PDOException $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

if(isset($_POST['delete'])){
    try {
        $stmt = $pdo->prepare("DELETE FROM Logistics WHERE venue_id = ? AND object_type = ?");
        $stmt->execute([$_POST['venue_id'], $_POST['object_type']]);
        $msg = 'Equipment deleted successfully';
    } catch(PDOException $e) {
        $error = 'Error deleting equipment: ' . $e->getMessage();
    }
}

if(isset($_POST['update_status'])){
    try {
        $stmt = $pdo->prepare("UPDATE Logistics SET status = ? WHERE venue_id = ? AND object_type = ?");
        $stmt->execute([$_POST['new_status'], $_POST['venue_id'], $_POST['object_type']]);
        $msg = 'Equipment status updated successfully';
    } catch(PDOException $e) {
        $error = 'Error updating status: ' . $e->getMessage();
    }
}

// Get venues for dropdown
$venues = $pdo->query("SELECT venue_id, venue_name FROM Venues ORDER BY venue_name")->fetchAll();

// Get all logistics with venue details
$logistics = $pdo->query("
    SELECT l.*, v.venue_name 
    FROM Logistics l 
    LEFT JOIN Venues v ON l.venue_id = v.venue_id 
    ORDER BY v.venue_name, l.object_type
")->fetchAll();

// Get equipment statistics
$totalEquipment = count($logistics);
$availableCount = count(array_filter($logistics, function($l) { return $l['status'] === 'Available'; }));
$inUseCount = count(array_filter($logistics, function($l) { return $l['status'] === 'In Use'; }));
?>
<!doctype html>
<html><head><meta charset="utf-8"><link rel="stylesheet" href="../assets/style.css"><title>Admin - Logistics</title></head><body>
<div class="container">
  <div class="nav">
    <a class="badge" href="dashboard.php">Dashboard</a>
    <a class="badge" href="events.php">Events</a>
    <a class="badge" href="venues.php">Venues</a>
    <a class="badge" href="meals.php">Meals</a>
    <a class="badge" href="bookings.php">Bookings</a>
    <a class="badge" href="sponsors.php">Sponsors</a>
    <a class="badge" href="cashflow.php">Financial Reports</a>
    <a class="badge" href="../logout.php">Logout</a>
  </div>
  
  <h1>🔧 Logistics & Equipment Management</h1>
  <?php if($msg): ?><div class="success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="grid">
    <div class="card">
      <h3>📊 Equipment Overview</h3>
      <p>Total Equipment: <strong><?=$totalEquipment?></strong></p>
      <p>Available: <strong style="color:#10b981;"><?=$availableCount?></strong></p>
      <p>In Use: <strong style="color:#f59e0b;"><?=$inUseCount?></strong></p>
    </div>

    <div class="card">
      <h3>➕ Add Equipment</h3>
      <form method="post" class="row">
        <label>Venue</label>
        <select name="venue_id" required>
          <option value="">-- Select Venue --</option>
          <?php foreach($venues as $v): ?>
            <option value="<?=htmlspecialchars($v['venue_id'])?>"><?=htmlspecialchars($v['venue_id'].' - '.$v['venue_name'])?></option>
          <?php endforeach; ?>
        </select>
        
        <label>Equipment Type</label>
        <input name="object_type" required placeholder="e.g. Microphones, Chairs, Projector" />
        
        <label>Quantity</label>
        <input type="number" name="quantity" min="1" required />
        
        <label>Status</label>
        <select name="status" required>
          <option value="Available">Available</option>
          <option value="In Use">In Use</option>
          <option value="Maintenance">Under Maintenance</option>
          <option value="Damaged">Damaged</option>
        </select>
        
        <button name="create" style="background:#059669;">Add Equipment</button>
      </form>
    </div>
  </div>

  <div class="card">
    <h3>🏢 Equipment by Venue</h3>
    <?php if(empty($logistics)): ?>
      <p>No equipment registered. Add equipment above!</p>
    <?php else: ?>
    <table class="table">
      <tr><th>Venue</th><th>Equipment</th><th>Quantity</th><th>Status</th><th>Actions</th></tr>
      <?php foreach($logistics as $l): ?>
      <tr>
        <td>
          <strong><?=htmlspecialchars($l['venue_name'] ?: 'Unknown Venue')?></strong><br>
          <small><?=htmlspecialchars($l['venue_id'])?></small>
        </td>
        <td><?=htmlspecialchars($l['object_type'])?></td>
        <td><?=htmlspecialchars($l['quantity'])?></td>
        <td>
          <?php if($l['status'] === 'Available'): ?>
            <span style="color:#10b981;font-weight:bold;">✅ Available</span>
          <?php elseif($l['status'] === 'In Use'): ?>
            <span style="color:#f59e0b;font-weight:bold;">🟡 In Use</span>
          <?php elseif($l['status'] === 'Maintenance'): ?>
            <span style="color:#8b5cf6;font-weight:bold;">🔧 Maintenance</span>
          <?php else: ?>
            <span style="color:#dc2626;font-weight:bold;">❌ <?=htmlspecialchars($l['status'])?></span>
          <?php endif; ?>
        </td>
        <td>
          <div style="display:flex;gap:4px;flex-wrap:wrap;">
            <form method="post" style="display:inline;">
              <input type="hidden" name="venue_id" value="<?=htmlspecialchars($l['venue_id'])?>">
              <input type="hidden" name="object_type" value="<?=htmlspecialchars($l['object_type'])?>">
              <select name="new_status" style="padding:2px;margin:0;width:auto;" onchange="this.form.submit()">
                <option value="">Change Status</option>
                <option value="Available">Available</option>
                <option value="In Use">In Use</option>
                <option value="Maintenance">Maintenance</option>
                <option value="Damaged">Damaged</option>
              </select>
              <button name="update_status" style="display:none;"></button>
            </form>
            
            <form method="post" style="display:inline" onsubmit="return confirm('Delete this equipment?')">
              <input type="hidden" name="venue_id" value="<?=htmlspecialchars($l['venue_id'])?>">
              <input type="hidden" name="object_type" value="<?=htmlspecialchars($l['object_type'])?>">
              <button name="delete" style="background:#dc2626;padding:4px 8px;">Delete</button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>

  <?php if(!empty($venues)): ?>
  <div class="card">
    <h3>🏢 Equipment Summary by Venue</h3>
    <?php foreach($venues as $venue): ?>
      <?php 
      $venueEquipment = array_filter($logistics, function($l) use ($venue) { 
          return $l['venue_id'] === $venue['venue_id']; 
      });
      ?>
      
      <?php if(!empty($venueEquipment)): ?>
      <div style="border:1px solid #374151;border-radius:8px;padding:12px;margin:8px 0;">
        <h4 style="margin:0 0 8px 0;"><?=htmlspecialchars($venue['venue_name'])?> (<?=htmlspecialchars($venue['venue_id'])?>)</h4>
        <div style="display:flex;gap:16px;flex-wrap:wrap;">
          <?php foreach($venueEquipment as $eq): ?>
            <div style="background:#1f2937;padding:8px;border-radius:4px;">
              <strong><?=htmlspecialchars($eq['object_type'])?></strong><br>
              <small>Qty: <?=htmlspecialchars($eq['quantity'])?> | <?=htmlspecialchars($eq['status'])?></small>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
</body></html>