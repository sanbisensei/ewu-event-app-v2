<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireClient();
$customer_id = $_SESSION['customer_id'];
$msg = '';
$error = '';

// Handle feedback submission
if(isset($_POST['submit_feedback'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO Feedback (event_id, customer_id, recommendation, review, rating) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['event_id'],
            $customer_id,
            $_POST['recommendation'],
            $_POST['review'],
            $_POST['rating']
        ]);
        $msg = "Feedback submitted successfully!";
    } catch(PDOException $e) {
        $error = "Error submitting feedback: " . $e->getMessage();
    }
}

// Get events I've booked that I can review (completed events)
$reviewableEvents = $pdo->prepare("
    SELECT DISTINCT e.event_id, e.event_name, e.event_date, e.event_type, v.venue_name
    FROM Bookings b 
    LEFT JOIN Events e ON b.event_id = e.event_id 
    LEFT JOIN Venues v ON e.venue_id = v.venue_id 
    WHERE b.customer_id = ? AND e.event_date <= CURDATE()
    ORDER BY e.event_date DESC
");
$reviewableEvents->execute([$customer_id]);
$reviewableEvents = $reviewableEvents->fetchAll();

// Get my previous feedback
$myFeedback = $pdo->prepare("
    SELECT f.*, e.event_name, e.event_date 
    FROM Feedback f 
    LEFT JOIN Events e ON f.event_id = e.event_id 
    WHERE f.customer_id = ? 
    ORDER BY f.feedback_id DESC
");
$myFeedback->execute([$customer_id]);
$myFeedback = $myFeedback->fetchAll();

// Get specific event if event_id is provided
$selected_event = null;
if(isset($_GET['event_id'])) {
    $stmt = $pdo->prepare("
        SELECT e.*, v.venue_name 
        FROM Events e 
        LEFT JOIN Venues v ON e.venue_id = v.venue_id 
        WHERE e.event_id = ? AND e.event_date <= CURDATE()
    ");
    $stmt->execute([$_GET['event_id']]);
    $selected_event = $stmt->fetch();
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><link rel="stylesheet" href="../assets/style.css"><title>Feedback</title></head><body>
<div class="container">
 <div class="nav">
  <a class="badge" href="dashboard.php">Dashboard</a>
  <a class="badge" href="book_event.php">Book Events</a>
  <a class="badge" href="my_bookings.php">My Bookings</a>
  <a class="badge" href="payment.php">Payments</a>
  <a class="badge" href="profile.php">Profile</a>
  <a class="badge" href="../logout.php">Logout</a>
</div>
  
  <h1>Event Feedback & Reviews</h1>
  <?php if($msg): ?><div class="success"><?=htmlspecialchars($msg)?></div><?php endif; ?>
  <?php if($error): ?><div class="error"><?=htmlspecialchars($error)?></div><?php endif; ?>

  <?php if($selected_event): ?>
  <div class="card" style="border: 2px solid #059669;">
    <h3>⭐ Review: <?=htmlspecialchars($selected_event['event_name'])?></h3>
    <div style="background:#064e3b;padding:12px;border-radius:8px;margin:10px 0;">
      <p><strong>Event:</strong> <?=htmlspecialchars($selected_event['event_name'])?></p>
      <p><strong>Date:</strong> <?=htmlspecialchars($selected_event['event_date'])?></p>
      <p><strong>Venue:</strong> <?=htmlspecialchars($selected_event['venue_name'] ?: 'N/A')?></p>
    </div>
    
    <form method="post" class="row">
      <input type="hidden" name="event_id" value="<?=htmlspecialchars($selected_event['event_id'])?>">
      
      <label>Overall Rating</label>
      <select name="rating" required>
        <option value="">-- Select Rating --</option>
        <option value="5">⭐⭐⭐⭐⭐ Excellent (5)</option>
        <option value="4">⭐⭐⭐⭐ Good (4)</option>
        <option value="3">⭐⭐⭐ Average (3)</option>
        <option value="2">⭐⭐ Poor (2)</option>
        <option value="1">⭐ Very Poor (1)</option>
      </select>
      
      <label>Your Review</label>
      <textarea name="review" rows="4" placeholder="Tell us about your experience..." required></textarea>
      
      <label>Recommendation/Suggestion</label>
      <input name="recommendation" placeholder="Any suggestions for improvement?" />
      
      <button name="submit_feedback" style="background:#059669;font-size:16px;">Submit Review</button>
    </form>
  </div>
  <?php endif; ?>

  <div class="grid">
    <div class="card">
      <h3>🎉 Events You Can Review</h3>
      <?php if(empty($reviewableEvents)): ?>
        <p>No completed events to review yet. <a href="book_event.php">Book an event</a> to leave feedback after attending!</p>
      <?php else: ?>
        <?php foreach($reviewableEvents as $e): ?>
        <div style="border:1px solid #374151;border-radius:8px;padding:12px;margin:8px 0;">
          <h4 style="margin:0 0 8px 0;"><?=htmlspecialchars($e['event_name'])?></h4>
          <p style="margin:4px 0;"><strong>Type:</strong> <?=htmlspecialchars($e['event_type'])?></p>
          <p style="margin:4px 0;"><strong>Date:</strong> <?=htmlspecialchars($e['event_date'])?></p>
          <p style="margin:4px 0;"><strong>Venue:</strong> <?=htmlspecialchars($e['venue_name'] ?: 'N/A')?></p>
          
          <?php
          // Check if already reviewed
          $hasReviewed = $pdo->prepare("SELECT feedback_id FROM Feedback WHERE event_id = ? AND customer_id = ?");
          $hasReviewed->execute([$e['event_id'], $customer_id]);
          $hasReviewed = $hasReviewed->fetch();
          ?>
          
          <?php if($hasReviewed): ?>
            <span style="color:#10b981;font-weight:bold;">✓ Already Reviewed</span>
          <?php else: ?>
            <a href="?event_id=<?=htmlspecialchars($e['event_id'])?>" 
               style="background:#059669;color:white;padding:6px 12px;border-radius:4px;text-decoration:none;display:inline-block;margin-top:8px;">
               Leave Review
            </a>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="card">
      <h3>📝 Quick Feedback</h3>
      <form method="post" class="row">
        <label>Select Event</label>
        <select name="event_id" required>
          <option value="">-- Choose Event to Review --</option>
          <?php foreach($reviewableEvents as $e): ?>
            <?php
            // Check if already reviewed
            $hasReviewed = $pdo->prepare("SELECT feedback_id FROM Feedback WHERE event_id = ? AND customer_id = ?");
            $hasReviewed->execute([$e['event_id'], $customer_id]);
            if(!$hasReviewed->fetch()):
            ?>
            <option value="<?=htmlspecialchars($e['event_id'])?>"><?=htmlspecialchars($e['event_name'].' - '.$e['event_date'])?></option>
            <?php endif; ?>
          <?php endforeach; ?>
        </select>
        
        <label>Rating</label>
        <select name="rating" required>
          <option value="">-- Rate Event --</option>
          <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
          <option value="4">⭐⭐⭐⭐ Good</option>
          <option value="3">⭐⭐⭐ Average</option>
          <option value="2">⭐⭐ Poor</option>
          <option value="1">⭐ Very Poor</option>
        </select>
        
        <label>Review</label>
        <textarea name="review" rows="3" placeholder="Your experience..." required></textarea>
        
        <label>Suggestion</label>
        <input name="recommendation" placeholder="How can we improve?" />
        
        <button name="submit_feedback">Submit Feedback</button>
      </form>
    </div>
  </div>

  <?php if(!empty($myFeedback)): ?>
  <div class="card">
    <h3>📝 My Previous Reviews</h3>
    <table class="table">
      <tr><th>Event</th><th>Date</th><th>Rating</th><th>Review</th><th>Suggestion</th></tr>
      <?php foreach($myFeedback as $f): ?>
      <tr>
        <td><?=htmlspecialchars($f['event_name'])?></td>
        <td><?=htmlspecialchars($f['event_date'])?></td>
        <td>
          <?php 
          $stars = str_repeat('⭐', $f['rating']);
          echo htmlspecialchars($stars . ' (' . $f['rating'] . '/5)');
          ?>
        </td>
        <td><?=htmlspecialchars($f['review'])?></td>
        <td><?=htmlspecialchars($f['recommendation'] ?: 'No suggestion')?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
  <?php endif; ?>
</div>
</body></html>