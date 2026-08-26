<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_appointment'])) {
    $user_id = $_SESSION['user_id'];
    $vet_salon_id = $_POST['vet_salon_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];

    try {
        $stmt = $conn->prepare("INSERT INTO appointments (user_id, vet_salon_id, date, time, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([$user_id, $vet_salon_id, $date, $time]);

        header("Location: user/dashboard.php?msg=Appointment booked successfully!");
        exit;
    } catch (PDOException $e) {
        die("Error booking appointment: " . $e->getMessage());
    }
} else {
    header("Location: vets.php");
    exit;
}
?>