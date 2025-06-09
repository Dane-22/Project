<?php 
require('../connection/conn.php');
require('function.php');
require('seller.php');

session_start();

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'I') {
    header("Location: ../auth/session-destroy.php");

    exit;
}

// Check if the user is logged in

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/session-destroy.php");
    exit;
}

$id = $_SESSION['id']; // Logged-in user's ID

// Get current or selected month/year
$currentMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$currentYear = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Initialize seller class
$seller = new Seller($db);

// Fetch data for logged-in user only
$salesData = $seller->getSalesByMonthYear($currentMonth, $currentYear, $id);
$salesTrend = $seller->getDailySalesTrend($currentMonth, $currentYear, $id);
$filteredSales = $seller->getFilteredSales($currentMonth, $currentYear, '', $id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>DMMMSU ATBI MARKETPLACE - Sales Dashboard</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../assets/images/dmmmsu.ico">
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/user1.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
    <style>
        .pcoded-main-container {
            margin-top: 70px; /* Add top margin to prevent header overlap */
            padding: 20px;
            position: relative;
            min-height: calc(100vh - 70px);
        }
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            color: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
        }
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .status-completed { background-color: #28a745; color: white; }
        .status-out-for-delivery { background-color: #17a2b8; color: white; }
        .filter-container {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .page-wrapper {
            padding: 20px;
        }
        .pcoded-content {
            padding-top: 0;
        }
        /* Ensure proper spacing for the entire content area */
        .main-body {
            margin-top: 0;
        }
    </style>
</head>
<body>
<!-- [Pre-loader and navigation includes...] -->
<?php include('../includes/seller_navigation.php'); ?>
<?php include('../includes/seller_header.php'); ?>

<div class="pcoded-main-container">
    <div class="pcoded-wrapper">
        <div class="pcoded-content">
            <div class="pcoded-inner-content">
                <div class="main-body">
                    <div class="page-wrapper">
                        <!-- Sales Summary Cards -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="stat-card bg-primary">
                                    <h5>Total Sales Amount</h5>
                                    <div class="stat-value">₱<?= number_format($salesData['stats']['total'] ?? 0, 2) ?></div>
                                    <small><?= date('F Y', mktime(0, 0, 0, $currentMonth, 1, $currentYear)) ?></small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-card bg-success">
                                    <h5>Average Daily Sales</h5>
                                    <div class="stat-value">₱<?= number_format($salesData['stats']['average_order_value'] ?? 0, 2) ?></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-card bg-info">
                                    <h5>Total Orders</h5>
                                    <div class="stat-value"><?= $salesData['stats']['total_orders'] ?? 0 ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Month/Year Filter -->
                        <div class="filter-container">
                            <form method="get" class="form-inline">
                                <div class="form-group mr-3">
                                    <select name="month" class="form-control">
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <option value="<?= sprintf('%02d', $m) ?>" <?= $currentMonth == $m ? 'selected' : '' ?>>
                                                <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="form-group mr-3">
                                    <select name="year" class="form-control">
                                        <?php for ($y = date('Y'); $y >= date('Y')-5; $y--): ?>
                                            <option value="<?= $y ?>" <?= $currentYear == $y ? 'selected' : '' ?>>
                                                <?= $y ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </form>
                        </div>

                        <!-- Sales Trend Chart -->
                        <div class="chart-container">
                            <h4>Daily Sales Trend - <?= date('F Y', mktime(0, 0, 0, $currentMonth, 1, $currentYear)) ?></h4>
                            <canvas id="salesChart" height="100"></canvas>
                        </div>

                        <!-- Sales Table -->
                        <div class="card">
                            <div class="card-header">
                                <h4>Monthly Sales Overview - <?= date('F Y', mktime(0, 0, 0, $currentMonth, 1, $currentYear)) ?></h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                <table id="salesTable" class="table table-hover">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Payment Method</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filteredSales as $sale): ?>
                    <tr>
                        <td><?= $sale['order_id'] ?></td>
                        <td><?= htmlspecialchars($sale['name']) ?></td>
                        <td><?= htmlspecialchars($sale['product_name']) ?></td>
                        <td><?= $sale['quantity'] ?></td>
                        <td>₱<?= number_format($sale['price'], 2) ?></td>
                        <td>₱<?= number_format($sale['total_item_price'], 2) ?></td>
                        <td><?= date('M d, Y', strtotime($sale['created_at'])) ?></td>
                        <td>
                            <span class="status-badge status-<?= str_replace(' ', '-', strtolower($sale['status'])) ?>">
                                <?= $sale['status'] ?>
                            </span>
                        </td>
                        <td><?= $sale['payment_method'] ?? 'Cash' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#salesTable').DataTable({
        order: [[6, 'desc']], // Sort by date descending
        dom: '<"top"f>rt<"bottom"lip><"clear">'
    });

    // Sales Chart
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [<?php
                $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    echo "'" . date('M j', mktime(0, 0, 0, $currentMonth, $day, $currentYear)) . "',";
                }
            ?>],
            datasets: [{
                label: 'Daily Sales (₱)',
                data: [<?php
                    for ($day = 1; $day <= $daysInMonth; $day++) {
                        $date = date('Y-m-d', mktime(0, 0, 0, $currentMonth, $day, $currentYear));
                        echo isset($salesTrend[$date]) ? $salesTrend[$date] : '0';
                        echo ',';
                    }
                ?>],
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderWidth: 2,
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value;
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '₱' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            }
        }
    });
});
</script>
</body>
</html>