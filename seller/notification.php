<?php 
session_start();
require('../connection/conn.php');
include('../includes/seller_navigation.php');
include('../includes/seller_header.php'); // Include the header

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    echo "<script>";
    echo "window.location.href='../auth-signin.php';";
    echo "</script>";
    exit();
}

$id = $_SESSION['id'];

// Fetch user data
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$query = $stmt->get_result();
$row = $query->fetch_object();

$fname = $row->fname;
$mname = $row->mname;
$lname = $row->lname;

// Get search query
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$base_sql = "SELECT * FROM mes WHERE user_id = ?";
$params = [$id];
$types = "i";

if (!empty($search_query)) {
    $search_term = "%$search_query%";
    $base_sql .= " AND (product_name LIKE ? OR message LIKE ? OR status LIKE ? OR product_description LIKE ?)";
    array_push($params, $search_term, $search_term, $search_term, $search_term);
    $types .= "ssss";
}

// Add sorting
$base_sql .= " ORDER BY created_at DESC";

// Prepare and execute the query
$stmt = $db->prepare($base_sql);
if (!$stmt) {
    error_log("SQL Prepare Error: " . $db->error);
    die("Database query error.");
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Debugging: Log the SQL query and parameters
error_log("Executed SQL: " . $base_sql);
error_log("Parameters: " . json_encode($params));

// Check if there are results
if ($result->num_rows > 0) {
    // Fetch data for display
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $notifications = [];
    error_log("No notifications found for user ID: " . $id);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>DMMMSU ATBI MARKETPLACE</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="icon" href="assets/images/dmmmsu.ico " type="image/x-icon">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/user1.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        .pcoded-main-container {
            margin-top: 70px;
            padding: 20px;
            position: relative;
            min-height: calc(100vh - 70px);
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            color: white;
        }
        .status-approved { background-color: #28a745; }
        .status-pending { background-color: #ffc107; }
        .status-rejected { background-color: #dc3545; }
        .filter-section {
            margin-bottom: 20px;
        }
        .filter-row {
            margin-bottom: 15px;
        }
        .table td {
            vertical -align: middle;
        }
    </style>
</head>
<body>
<?php
include('../includes/seller_navigation.php');
include('../includes/seller_header.php'); ?>
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>

    <div class="pcoded-main-container">
        <div class="pcoded-wrapper">
            <div class="pcoded-content">
                <div class="pcoded-inner-content">
                    <div class="main-body">
                        <div class="page-wrapper">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5>Notifications</h5>
                                        </div>
                                        <div class="card-body">
                                             <!-- Search Section -->
                                        <div class="filter-section">
                                            <form method="GET" action="">
                                                <div class="row filter-row">
                                                    <div class="col-md-10">
                                                        <input type="text" name="search" id="search" class="form-control"
                                                               placeholder="Search notifications by message, product name, status, etc..."
                                                               value="<?= htmlspecialchars($search_query) ?>">
                                                    </div>
                                                    <div class="col-md-2 text-right">
                                                        <button type="submit" class="btn btn-primary btn-block">
                                                            <i class="fas fa-search"></i> Search
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>

                                            <!-- Notifications Table -->
                                            <?php if (!empty($notifications)): ?>
                                                <div class="table-responsive">
                                                    <table class="table table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>ID</th>
                                                                <th>Message</th>
                                                                <th>Product Name</th>
                                                                <th>Price</th>
                                                                <th>Description</th>
                                                                <th>Quantity</th>
                                                                <th>Date</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($notifications as $row): ?>
                                                                <tr>
                                                                    <td><?= htmlspecialchars($row["id"]) ?></td>
                                                                    <td><?= htmlspecialchars($row["message"]) ?></td>
                                                                    <td><?= htmlspecialchars($row["product_name"]) ?></td>
                                                                    <td>₱<?= number_format($row["product_price"], 2) ?></td>
                                                                    <td><?= htmlspecialchars($row["product_description"]) ?></td>
                                                                    <td><?= htmlspecialchars($row["product_quantity"]) ?></td>
                                                                    <td><?= date('M d, Y H:i', strtotime($row["created_at"])) ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php else: ?>
                                                <div class="alert alert-info">No notifications found.</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/vendor-all.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.min.js"></script>
<script src="../assets/js/pcoded.min.js"></script>
</body>
</html>