<?php
// ==========================================================
// Database configuration — works for both XAMPP and WAMP with
// their default MySQL settings (root user, empty password).
// If you set a custom MySQL root password in WAMP, update
// $DB_PASS below to match it.
// ==========================================================
session_start();

$DB_HOST = "localhost";
$DB_NAME = "land_acquisition_db";
$DB_USER = "root";
$DB_PASS = "";   // default WAMP/XAMPP MySQL password is empty

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Helper: require login
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit;
    }
}

// Helper: require admin role
function require_admin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: index.php");
        exit;
    }
}
?>
