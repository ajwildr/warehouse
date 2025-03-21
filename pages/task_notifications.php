<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Worker') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch unread task notifications
$query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result();

// Mark notifications as "Read"
$update_query = "UPDATE notifications SET status = 'Read' WHERE user_id = ? AND status = 'Unread'";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("i", $user_id);
$update_stmt->execute();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Task Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2 class="mb-4">Task Notifications</h2>
        <div class="card">
            <div class="card-body">
                <?php if ($notifications->num_rows > 0): ?>
                    <ul class="list-group">
                        <?php while ($notification = $notifications->fetch_assoc()): ?>
                            <li class="list-group-item">
                                <?= htmlspecialchars($notification['message']) ?>
                                <small class="text-muted d-block"><?= $notification['created_at'] ?></small>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted">No new task notifications.</p>
                <?php endif; ?>
            </div>
        </div>
        <a href="worker_dashboard.php" class="btn btn-primary mt-3">Back to Dashboard</a>
    </div>
</body>
</html>
