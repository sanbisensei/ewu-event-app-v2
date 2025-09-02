<?php
// includes/db.php - update these values if your XAMPP uses a different user/password
$DB_HOST = 'localhost';
$DB_NAME = 'ewu_event';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    die("DB Connection failed: " . $e->getMessage());
}
?>
