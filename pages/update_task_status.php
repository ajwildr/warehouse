<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Worker') {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['task_id'])) {
    $task_id = $_POST['task_id'];
    $worker_id = $_SESSION['user_id'];

    $query = "UPDATE tasks SET status='Completed' WHERE task_id=? AND assigned_to=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $task_id, $worker_id);

    if ($stmt->execute()) {
        echo "<script>alert('Task marked as completed!'); window.location='worker_dashboard.php';</script>";
    } else {
        echo "Error updating task status: " . $stmt->error;
    }
}
?>
