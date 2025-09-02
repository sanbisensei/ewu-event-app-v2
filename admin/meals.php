<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$msg='';
if(isset($_POST['create'])){
  $stmt=$pdo->prepare("INSERT INTO Meals (meal_id,meal_name,meal_type,meal_cost,catering_company) VALUES (?,?,?,?,?)");
  $stmt->execute([$_POST['meal_id'],$_POST['meal_name'],$_POST['meal_type'],$_POST['meal_cost'],$_POST['catering_company']]);
  $msg='Created';
}
if(isset($_POST['delete'])){
  $stmt=$pdo->prepare("DELETE FROM Meals WHERE meal_id=?");
  $stmt->execute([$_POST['meal_id']]);
  $msg='Deleted';
}
$rows=$pdo->query("SELECT * FROM Meals ORDER BY meal_id")->fetchAll();
?>
<!doctype html>
<html><head><meta charset="utf-8"><link rel="stylesheet" href="../assets/style.css"><title>Meals</title></head><body>
<div class="container">
  <div class="nav">
  <a class="badge" href="dashboard.php">Dashboard</a>
  <a class="badge" href="events.php">Events</a>
  <a class="badge" href="venues.php">Venues</a>
  <a class="badge" href="bookings.php">Bookings</a>
  <a class="badge" href="sponsors.php">Sponsors</a>
  <a class="badge" href="logistics.php">Logistics</a>
  <a class="badge" href="cashflow.php">Financial Reports</a>
  <a class="badge" href="../logout.php">Logout</a>
</div>
  <h1>Meals</h1>
  <?php if($msg): ?><div class="success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <div class="card">
    <h3>Create Meal</h3>
    <form method="post" class="row">
      <label>ID</label><input name="meal_id" required />
      <label>Name</label><input name="meal_name" required />
      <label>Type</label><input name="meal_type" />
      <label>Cost</label><input name="meal_cost" type="number" step="0.01" />
      <label>Company</label><input name="catering_company" />
      <button name="create">Create</button>
    </form>
  </div>

  <div class="card">
    <h3>All Meals</h3>
    <table class="table">
      <tr><th>ID</th><th>Name</th><th>Type</th><th>Cost</th><th>Company</th><th>Actions</th></tr>
      <?php foreach($rows as $r): ?>
      <tr>
        <td><?=htmlspecialchars($r['meal_id'])?></td>
        <td><?=htmlspecialchars($r['meal_name'])?></td>
        <td><?=htmlspecialchars($r['meal_type'])?></td>
        <td><?=htmlspecialchars($r['meal_cost'])?></td>
        <td><?=htmlspecialchars($r['catering_company'])?></td>
        <td>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete?')">
            <input type="hidden" name="meal_id" value="<?=htmlspecialchars($r['meal_id'])?>"/>
            <button name="delete">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>
</body></html>
