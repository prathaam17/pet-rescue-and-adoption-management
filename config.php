<?php
// Database Credentials
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "pet_rescue_db";

try {
    // connect without db first to create it if needed (optional, but good for setup)
    // For now, standard connection
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . " <br>Please ensure XAMPP MySQL is running and database 'pet_rescue_db' exists.");
}

// Start Session globally
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL configuration
define('BASE_URL', 'http://localhost/pet_rescue/');
?>