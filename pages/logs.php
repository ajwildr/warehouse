<?php
session_start();
require '../includes/db_connect.php';

// Check if the user is logged in and has the Accounting role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Accounting') {
    header("Location: ../login.php");
    exit;
}

// Fetch stock transactions with product details
$sql = "
    SELECT 
        s.stock_id,
        p.name AS product_name,
        s.quantity,
        s.transaction_type,
        s.created_at,
        p.current AS current_stock,
        p.min_limit,
        p.max_limit
    FROM stock s
    INNER JOIN products p ON s.product_id = p.product_id
    ORDER BY s.created_at DESC
";
$result = $conn->query($sql);

// Fetch summary statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_transactions,
        SUM(CASE WHEN transaction_type = 'Incoming' THEN quantity ELSE 0 END) as total_incoming,
        SUM(CASE WHEN transaction_type = 'Outgoing' THEN quantity ELSE 0 END) as total_outgoing,
        COUNT(DISTINCT product_id) as products_moved
    FROM stock
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Fetch daily transaction totals for the chart
$daily_transactions_query = "
    SELECT 
        DATE(created_at) as date,
        SUM(CASE WHEN transaction_type = 'Incoming' THEN quantity ELSE 0 END) as incoming,
        SUM(CASE WHEN transaction_type = 'Outgoing' THEN quantity ELSE 0 END) as outgoing
    FROM stock
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
";
$daily_result = $conn->query($daily_transactions_query);
$chart_data = [];
while ($row = $daily_result->fetch_assoc()) {
    $chart_data[] = $row;
}

// Fetch top products by movement
$top_products_query = "
    SELECT 
        p.name AS product_name,
        SUM(CASE WHEN s.transaction_type = 'Incoming' THEN s.quantity ELSE 0 END) as incoming,
        SUM(CASE WHEN s.transaction_type = 'Outgoing' THEN s.quantity ELSE 0 END) as outgoing
    FROM stock s
    INNER JOIN products p ON s.product_id = p.product_id
    WHERE s.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY s.product_id
    ORDER BY (SUM(s.quantity)) DESC
    LIMIT 10
";
$top_products_result = $conn->query($top_products_query);
$product_movement_data = [];
while ($row = $top_products_result->fetch_assoc()) {
    $product_movement_data[] = $row;
}

// Fetch stock status distribution - simplified to only low stock and optimal
$stock_status_query = "
    SELECT 
        SUM(CASE WHEN current >= min_limit THEN 1 ELSE 0 END) as optimal,
        SUM(CASE WHEN current < min_limit THEN 1 ELSE 0 END) as low_stock
    FROM products
";
$stock_status_result = $conn->query($stock_status_query);
$stock_status_data = $stock_status_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Transaction Logs - CloudWare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
            --light-bg: #f8f9fa;
            --dark-bg: #343a40;
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .container {
            max-width: 1400px;
            padding: 2rem;
        }

        .card {
            border-radius: 1rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 2rem;
        }

        .stat-card {
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .table-container {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .back-button {
            text-decoration: none;
            color: var(--primary-color);
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }

        .back-button:hover {
            color: var(--accent-color);
            transform: translateX(-5px);
        }

        .back-button i {
            margin-right: 0.5rem;
            font-size: 1.2em;
        }

        .transaction-badge {
            font-size: 0.9em;
            padding: 0.5em 0.8em;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="accounting_dashboard.php" class="back-button">
            <i class="bi bi-arrow-left-circle-fill"></i>
            Back to Dashboard
        </a>

        <h1 class="mb-4">Stock Transaction Logs</h1>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Transactions</h5>
                        <h2><?= number_format($stats['total_transactions'] ?? 0 ) ?></h2>
                        <p class="mb-0">Last 30 days</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Incoming</h5>
                        <h2><?= number_format($stats['total_incoming'] ?? 0 ) ?></h2>
                        <p class="mb-0">Units received</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-danger text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Outgoing</h5>
                        <h2><?= number_format($stats['total_outgoing'] ?? 0 ) ?></h2>
                        <p class="mb-0">Units dispatched</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Products Moved</h5>
                        <h2><?= number_format($stats['products_moved'] ?? 0 ) ?></h2>
                        <p class="mb-0">Unique products</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction Chart -->
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title mb-4">Stock Movement Trends</h4>
                <div id="stockChart" style="height: 300px;"></div>
            </div>
        </div>

        <!-- Additional Chart - Top Products -->
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title mb-4">Top Products by Movement</h4>
                <div id="productMovementChart" style="height: 350px;"></div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">Recent Transactions</h4>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary btn-sm" onclick="exportToCSV()">
                        <i class="bi bi-download me-2"></i>Export to CSV
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Product Name</th>
                            <th>Quantity</th>
                            <th>Transaction Type</th>
                            <th>Current Stock</th>
                            <th>Stock Status</th>
                            <th>Date & Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['stock_id']) ?></td>
                                    <td><?= htmlspecialchars($row['product_name']) ?></td>
                                    <td>
                                        <span class="badge transaction-badge bg-<?= $row['transaction_type'] == 'Incoming' ? 'success' : 'danger' ?>">
                                            <?= $row['transaction_type'] == 'Incoming' ? '+' : '-' ?><?= htmlspecialchars($row['quantity']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $row['transaction_type'] == 'Incoming' ? 'success' : 'danger' ?>">
                                            <?= htmlspecialchars($row['transaction_type']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($row['current_stock']) ?></td>
                                    <td>
                                        <?php
                                        if ($row['current_stock'] <= $row['min_limit']) {
                                            echo '<span class="badge bg-danger">Low Stock</span>';
                                        } else {
                                            echo '<span class="badge bg-success">Optimal</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?= date('M d, Y H:i', strtotime($row['created_at'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="bi bi-inbox text-muted d-block mb-2" style="font-size: 2rem;"></i>
                                    No stock transactions found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Hidden data for charts -->
    <script type="application/json" id="chartData">
        <?= json_encode($chart_data) ?>
    </script>
    <script type="application/json" id="productMovementData">
        <?= json_encode($product_movement_data) ?>
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        // Initialize charts on document load
        document.addEventListener('DOMContentLoaded', function() {
            // Parse data from hidden elements
            const chartData = JSON.parse(document.getElementById('chartData').textContent);
            const productMovementData = JSON.parse(document.getElementById('productMovementData').textContent);
            
            // 1. MAIN STOCK MOVEMENT CHART
            const stockChartOptions = {
                series: [{
                    name: 'Incoming',
                    data: chartData.map(item => ({ x: new Date(item.date).getTime(), y: parseInt(item.incoming) }))
                }, {
                    name: 'Outgoing',
                    data: chartData.map(item => ({ x: new Date(item.date).getTime(), y: parseInt(item.outgoing) }))
                }],
                chart: {
                    type: 'area',
                    height: 300,
                    toolbar: {
                        show: true
                    },
                    animations: {
                        enabled: true
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: [3, 3, 4],
                    dashArray: [0, 0, 5]
                },
                colors: ['#2ecc71', '#e74c3c'],
                fill: {
                    type: ['gradient', 'gradient', 'solid'],
                    gradient: {
                        shade: 'light',
                        type: 'vertical',
                        opacityFrom: 0.7,
                        opacityTo: 0.2
                    }
                },
                xaxis: {
                    type: 'datetime',
                    labels: {
                        format: 'MMM dd'
                    },
                    title: {
                        text: 'Date'
                    }
                },
                yaxis: {
                    title: {
                        text: 'Units'
                    }
                },
                legend: {
                    position: 'top'
                },
                tooltip: {
                    shared: true,
                    y: {
                        formatter: function(value) {
                            return Math.abs(value) + ' units';
                        }
                    }
                }
            };
            
            const stockChart = new ApexCharts(document.querySelector("#stockChart"), stockChartOptions);
            stockChart.render();
            
            // 2. TOP PRODUCTS BY MOVEMENT CHART
            const productChartOptions = {
                series: [{
                    name: 'Incoming',
                    data: productMovementData.map(item => parseInt(item.incoming))
                }, {
                    name: 'Outgoing',
                    data: productMovementData.map(item => parseInt(item.outgoing))
                }],
                chart: {
                    type: 'bar',
                    height: 350,
                    stacked: false
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '55%',
                        borderRadius: 2
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    show: true,
                    width: 2,
                    colors: ['transparent']
                },
                colors: ['#2ecc71', '#e74c3c'],
                xaxis: {
                    categories: productMovementData.map(item => item.product_name),
                    labels: {
                        rotate: -45,
                        rotateAlways: true,
                        maxHeight: 60
                    }
                },
                yaxis: {
                    title: {
                        text: 'Units'
                    }
                },
                fill: {
                    opacity: 1
                },
                legend: {
                    position: 'top'
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val + " units";
                        }
                    }
                }
            };

            const productChart = new ApexCharts(document.querySelector("#productMovementChart"), productChartOptions);
            productChart.render();
        });

        // Export to CSV function
        function exportToCSV() {
            const table = document.querySelector('table');
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (const row of rows) {
                const cols = row.querySelectorAll('td,th');
                const rowArray = [];
                for (const col of cols) {
                    rowArray.push('"' + col.innerText.replace(/"/g, '""') + '"');
                }
                csv.push(rowArray.join(','));
            }

            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.setAttribute('download', 'stock_transactions.csv');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>