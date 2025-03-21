<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Manager') {
    header("Location: login.php");
    exit;
}

$manager_id = $_SESSION['user_id'];
$category = $_SESSION['assigned_category'];

// Fetch workers in the same category
$query = "SELECT user_id, username FROM users WHERE role='Worker' AND assigned_category=?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $category);
$stmt->execute();
$workers = $stmt->get_result();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $assigned_to = $_POST['worker_id'];
    $description = $_POST['description'];

    $query = "INSERT INTO tasks (assigned_by, assigned_to, category, description) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiss", $manager_id, $assigned_to, $category, $description);
    
    if ($stmt->execute()) {
        // Insert a notification for the assigned worker
        $notification_msg = "New task assigned: " . $description;
        $notif_query = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
        $notif_stmt = $conn->prepare($notif_query);
        $notif_stmt->bind_param("is", $assigned_to, $notification_msg);
        $notif_stmt->execute();

        echo "<script>alert('Task assigned successfully!'); window.location='manager_dashboard.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Assign Task</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 500px;
            margin-top: 50px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .btn-primary {
            width: 100%;
        }
    </style>
</head>
<body>

<div class="container">
    <h2 class="text-center">Assign Task</h2>
    <form method="post">
        <div class="mb-3">
            <label class="form-label">Select Worker</label>
            <select name="worker_id" class="form-select" required>
                <option value="">-- Select Worker --</option>
                <?php while ($worker = $workers->fetch_assoc()): ?>
                    <option value="<?= $worker['user_id'] ?>"><?= htmlspecialchars($worker['username']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Task Description</label>
            <textarea name="description" class="form-control" rows="4" placeholder="Enter task details..." required></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Assign Task</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
