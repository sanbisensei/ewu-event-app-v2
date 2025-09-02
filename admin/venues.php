<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$msg='';
if(isset($_POST['create'])){
  $stmt=$pdo->prepare("INSERT INTO Venues (venue_id,venue_name,venue_capacity,manager_id,venue_cost,house,road,city) VALUES (?,?,?,?,?,?,?,?)");
  $stmt->execute([$_POST['venue_id'],$_POST['venue_name'],$_POST['venue_capacity'],$_POST['manager_id'],$_POST['venue_cost'],$_POST['house'],$_POST['road'],$_POST['city']]);
  $msg='Created';
}
if(isset($_POST['delete'])){
  $stmt=$pdo->prepare("DELETE FROM Venues WHERE venue_id=?");
  $stmt->execute([$_POST['venue_id']]);
  $msg='Deleted';
}
$rows=$pdo->query("SELECT * FROM Venues ORDER BY venue_id")->fetchAll();
?>
<!doctype html>
<html><head><meta charset="utf-8"><link rel="stylesheet" href="../assets/style.css"><title>Venues</title></head><body>
<div class="container">
  <div class="nav">
  <a class="badge" href="dashboard.php">Dashboard</a>
  <a class="badge" href="events.php">Events</a>
  <a class="badge" href="meals.php">Meals</a>
  <a class="badge" href="bookings.php">Bookings</a>
  <a class="badge" href="sponsors.php">Sponsors</a>
  <a class="badge" href="logistics.php">Logistics</a>
  <a class="badge" href="cashflow.php">Financial Reports</a>
  <a class="badge" href="../logout.php">Logout</a>
</div>
  <h1>Venues</h1>
  <?php if($msg): ?><div class="success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <div class="card">
    <h3>Create Venue</h3>
    <form method="post" class="row">
      <label>ID</label><input name="venue_id" required />
      <label>Name</label><input name="venue_name" required />
      <label>Capacity</label><input name="venue_capacity" type="number" />
      <label>Manager ID</label><input name="manager_id" />
      <label>Cost</label><input name="venue_cost" type="number" step="0.01" />
      <label>House</label><input name="house" />
      <label>Road</label><input name="road" />
      <label>City</label><input name="city" />
      <button name="create">Create</button>
    </form>
  </div>

  <div class="card">
    <h3>All Venues</h3>
    <table class="table">
      <tr><th>ID</th><th>Name</th><th>Cap</th><th>Manager</th><th>Cost</th><th>Actions</th></tr>
      <?php foreach($rows as $r): ?>
      <tr>
        <td><?=htmlspecialchars($r['venue_id'])?></td>
        <td><?=htmlspecialchars($r['venue_name'])?></td>
        <td><?=htmlspecialchars($r['venue_capacity'])?></td>
        <td><?=htmlspecialchars($r['manager_id'])?></td>
        <td><?=htmlspecialchars($r['venue_cost'])?></td>
        <td>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete?')">
            <input type="hidden" name="venue_id" value="<?=htmlspecialchars($r['venue_id'])?>"/>
            <button name="delete">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>
</body></html>
