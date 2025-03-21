<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Worker') {
    header("Location: login.php");
    exit;
}

$worker_id = $_SESSION['user_id'];

// Fetch racks assigned to the worker's category
$query = "SELECT r.rack_location, p.name AS product_name
          FROM rack r
          LEFT JOIN products p ON r.product_id = p.product_id";

$stmt = $conn->prepare($query);
$stmt->execute();
$racks = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rack Overview</title>
    <link rel="stylesheet" href="../assets/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f4f7fc;
            font-family: 'Arial', sans-serif;
        }
        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 0 15px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c3e50;
        }
        .search-bar {
            margin-bottom: 20px;
            position: relative;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        .search-bar input {
            width: 100%;
            padding: 12px 40px;
            border: 2px solid #007bff;
            border-radius: 30px;
            outline: none;
            font-size: 1rem;
        }
        .search-bar i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #007bff;
            font-size: 1.2rem;
        }
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }
        .rack-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .rack-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }
        .rack-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #007bff;
            margin-bottom: 10px;
        }
        .rack-details {
            font-size: 1rem;
            color: #555;
        }
        .back-btn {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }
        .back-btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

<div class="container">
    <a href="worker_dashboard.php" class="back-btn">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>
    
    <div class="header">
        <h1>Warehouse Rack Overview</h1>
    </div>
    
    <div class="search-bar">
        <i class="bi bi-search"></i>
        <input type="text" id="search" class="form-control" placeholder="Search by rack location or product...">
    </div>
    
    <div class="card-grid" id="rackCardGrid">
        <?php while ($row = $racks->fetch_assoc()): ?>
            <div class="rack-card">
                <h2 class="rack-title"><?= htmlspecialchars($row['rack_location']) ?></h2>
                <p class="rack-details">
                    <i class="bi bi-box-seam"></i> <?= htmlspecialchars($row['product_name'] ?? 'Unassigned') ?>
                </p>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<script>
    document.getElementById("search").addEventListener("keyup", function() {
        let filter = this.value.toLowerCase();
        let cards = document.querySelectorAll(".rack-card");
        
        cards.forEach(card => {
            let location = card.querySelector(".rack-title").textContent.toLowerCase();
            let product = card.querySelector(".rack-details").textContent.toLowerCase();
            
            if (location.includes(filter) || product.includes(filter)) {
                card.style.display = "";
            } else {
                card.style.display = "none";
            }
        });
    });
</script>

</body>
</html>
