<?php 
    session_start();
    require('../connection/conn.php');


    // Enable error reporting for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Check database connection
    if ($db->connect_error) {
        die("Connection failed: " . $db->connect_error);
    }

    if (!$_SESSION['id']) {
        echo "<script>";
        echo "window.location.href='../../auth-signin.php';";
        echo "</script>";
    }

    $id = $_SESSION['id'];

    $sql = "SELECT * FROM users WHERE id = '$id'";
    $query  = mysqli_query($db, $sql);
    $row    = mysqli_fetch_object($query);

    $fname = $row->fname;
    $mname = $row->mname;
    $lname = $row->lname;

    // Handle status update for individual order items
    if (isset($_POST['update_status'])) {
        $order_item_id = filter_var($_POST['order_item_id'], FILTER_SANITIZE_NUMBER_INT);
        $new_status = htmlspecialchars($_POST['item_status'], ENT_QUOTES, 'UTF-8');
        
        // Validate status values
        $valid_statuses = ['Pending', 'Out for Delivery', 'Completed', 'Cancelled'];
        if (!in_array($new_status, $valid_statuses)) {
            echo "<script>alert('Invalid status value provided.'); window.location.href = window.location.href;</script>";
            exit;
        }
        
        try {
            // Begin transaction
            $db->begin_transaction();
            
            // Use prepared statement for security
            $update_sql = "UPDATE order_items SET status = ? WHERE order_item_id = ?";
            $stmt = $db->prepare($update_sql);
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $db->error);
            }
            
            $stmt->bind_param('si', $new_status, $order_item_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            // Check if any rows were affected
            if ($stmt->affected_rows === 0) {
                throw new Exception("No order item found with ID: " . $order_item_id);
            }
            
            $stmt->close();
            $db->commit();
            
            // Log the status change
            $log_sql = "INSERT INTO order_status_log (order_item_id, status, changed_at) VALUES (?, ?, NOW())";
            $log_stmt = $db->prepare($log_sql);
            $log_stmt->bind_param('is', $order_item_id, $new_status);
            $log_stmt->execute();
            $log_stmt->close();
            
            echo "<script>
                alert('Order item status updated successfully!');
                window.location.href = window.location.href;
            </script>";
            
        } catch (Exception $e) {
            $db->rollback();
            echo "<script>
                alert('Failed to update order status: " . $e->getMessage() . "');
                window.location.href = window.location.href;
            </script>";
        }
    }

    // Handle order item deletion
    if (isset($_POST['delete_order_item'])) {
        $order_item_id_to_delete = $_POST['order_item_id_to_delete'];
        
        // Start transaction
        $db->begin_transaction();
        
        try {
            // Delete the order item
            $delete_sql = "DELETE FROM order_items WHERE order_item_id = ?";
            $stmt = $db->prepare($delete_sql);
            $stmt->bind_param('i', $order_item_id_to_delete);
            $stmt->execute();
            
            $db->commit();
            echo "<script>alert('Order item deleted successfully!'); window.location.href = window.location.href;</script>";
        } catch (Exception $e) {
            $db->rollback();
            echo "<script>alert('Failed to delete order item: " . $e->getMessage() . "');</script>";
        }
    }

    // Get filter and search parameters
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
    $search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
    $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
    $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

    // Build base SQL query with filters
    $base_sql = "SELECT 
                    o.order_id,
                    o.name,
                    o.payment_method,
                    o.order_date,
                    -- o.order_status as order_overall_status,
                                    o.street_address,
                o.region,
                o.city,
                o.state,
                o.postal_code,
                    o.proof_of_payment,
                    oi.order_item_id,
                    oi.quantity,
                    oi.price as unit_price,
                    oi.status as item_status,
                    p.name AS product_name,
                    p.user_id AS owner_id
            FROM orders1 o
            JOIN order_items oi 
            ON
                    o.order_id = oi.order_id
                JOIN products p 
                ON oi.product_id = p.id
                WHERE 
                    oi.owner_id = ?";

    // Initialize params with the seller's ID
    $params = [$id];
    $types = 'i'; // 'i' for integer

    // Add filters to the query
    $where_clauses = [];

    if (!empty($status_filter)) {
        $where_clauses[] = "oi.status = ?";
        $params[] = $status_filter;
        $types .= 's';
    }

    if (!empty($search_query)) {
        $search_term = "%$search_query%";
        $where_clauses[] = "(o.name LIKE ? OR o.order_id LIKE ? OR p.name LIKE ?)";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= 'sss';
    }

    if (!empty($date_from)) {
        $where_clauses[] = "o.order_date >= ?";
        $params[] = $date_from;
        $types .= 's';
    }

    if (!empty($date_to)) {
        $where_clauses[] = "o.order_date <= ?";
        $params[] = $date_to . ' 23:59:59'; // Include the entire day
        $types .= 's';
    }

    if (!empty($where_clauses)) {
        $base_sql .= " AND " . implode(" AND ", $where_clauses);
    }

    // Pagination variables
    $limit = 10;  // Number of orders per page
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    // Count total order items for pagination (with the same filters)
    $count_sql = "SELECT 
                    COUNT(oi.order_item_id) as total
            FROM orders1 o 
            JOIN order_items oi 
            ON
                    o.order_id = oi.order_id 
            JOIN products p 
            ON 
                    oi.product_id = p.id
            WHERE 
                    oi.owner_id = ?";

    if (!empty($where_clauses)) {
        $count_sql .= " AND " . implode(" AND ", $where_clauses);
    }

    $stmt_count = $db->prepare($count_sql);
    $stmt_count->bind_param($types, ...$params);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $total_orders = $result_count->fetch_assoc()['total'];
    $stmt_count->close();

    // Calculate total pages
    $total_pages = ceil($total_orders / $limit);

    // Add sorting and pagination to the main query
    $sql_orders = $base_sql . " ORDER BY oi.order_item_id DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';

    // Prepare and execute the main query
    $stmt_orders = $db->prepare($sql_orders);
    $stmt_orders->bind_param($types, ...$params);
    $stmt_orders->execute();
    $result_orders = $stmt_orders->get_result();

    function displayOrdersTable($result) {
        if ($result->num_rows > 0) {
            echo '<div class="table-responsive">';
            echo '<table class="table table-striped table-hover">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>Order ID</th>';
            echo '<th>Item ID</th>';
            echo '<th>Customer Name</th>';
            echo '<th>Product Name</th>';
            echo '<th>Quantity</th>';
            echo '<th>Unit Price</th>';
            echo '<th>Subtotal</th>';
            echo '<th>Payment Method</th>';
            echo '<th>Order Date</th>';
            echo '<th>Street Address</th>';
        echo '<th>Region</th>';
        echo '<th>City</th>';
        echo '<th>State</th>';
        echo '<th>Postal Code</th>';
            echo '<th>Order Status</th>';
            echo '<th>Item Status</th>';
            echo '<th>Proof of Payment</th>';
            echo '<th>Actions</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            while ($row = $result->fetch_assoc()) {
                $subtotal = $row['quantity'] * $row['unit_price'];
                $proof_of_payment = $row['proof_of_payment'];
                $proof_image_path = '../uploads/' . $proof_of_payment;
                
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['order_id']) . '</td>';
                echo '<td>' . htmlspecialchars($row['order_item_id']) . '</td>';
                echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['product_name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['quantity']) . '</td>';
                echo '<td>₱' . number_format($row['unit_price'], 2) . '</td>';
                echo '<td>₱' . number_format($subtotal, 2) . '</td>';
                echo '<td>' . htmlspecialchars($row['payment_method']) . '</td>';
                echo '<td>' . htmlspecialchars($row['order_date']) . '</td>';
                echo '<td>' . htmlspecialchars($row['street_address']) . '</td>';
                echo '<td>' . htmlspecialchars($row['region']) . '</td>';
                echo '<td>' . htmlspecialchars($row['city']) . '</td>';
                echo '<td>' . htmlspecialchars($row['state']) . '</td>';
                echo '<td>' . htmlspecialchars($row['postal_code']) . '</td>';
                // echo '<td>' . htmlspecialchars($row['order_overall_status']) . '</td>';
                
                // Item status with update form
                echo '<td>';
                echo '<form method="POST" class="status-form">';
                echo '<input type="hidden" name="order_item_id" value="' . $row['order_item_id'] . '">';
                echo '<select name="item_status" class="form-control status-select">';
                $statuses = ['Pending', 'Out for Delivery', 'Completed', 'Cancelled'];
                foreach ($statuses as $status) {
                    $selected = (strtolower($row['item_status']) == strtolower($status)) ? 'selected' : '';
                    echo '<option value="' . $status . '" ' . $selected . '>' . $status . '</option>';
                }
                echo '</select>';
                echo '<button type="submit" name="update_status" class="btn btn-primary btn-sm mt-1">';
                echo '<i class="fas fa-sync-alt"></i> Update';
                echo '</button>';
                echo '</form>';
                echo '</td>';
                
                echo '<td class="text-center">';
                if (!empty($proof_of_payment) && file_exists($proof_image_path)) {
                    echo '<a href="javascript:void(0);" class="proof-image" data-image="' . htmlspecialchars($proof_image_path) . '">';
                    echo '<img src="' . htmlspecialchars($proof_image_path) . '" alt="Proof of Payment" style="width: 50px; height: auto; cursor: pointer;">';
                    echo '</a>';
                } else {
                    echo '<span class="text-muted">No proof</span>';
                }
                echo '</td>';
                
                echo '<td>';
                echo '<form method="POST" class="delete-form" onsubmit="return confirm(\'Are you sure you want to delete this order item? This action cannot be undone.\')">';
                echo '<input type="hidden" name="order_item_id_to_delete" value="' . $row['order_item_id'] . '">';
                echo '<button type="submit" name="delete_order_item" class="btn btn-danger btn-sm">';
                echo '<i class="fas fa-trash"></i> Delete';
                echo '</button>';
                echo '</form>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
            echo '</div>';
        } else {
            echo '<div class="alert alert-info">No orders found matching your criteria.</div>';
        }
    }

    // Display pagination with filters
    function displayPagination($total_pages, $current_page, $status_filter, $search_query, $date_from, $date_to) {
        if ($total_pages > 1) {
            echo '<nav aria-label="Page navigation">';
            echo '<ul class="pagination justify-content-center">';
            
            // Build query string for pagination links
            $query_params = [];
            if (!empty($status_filter)) $query_params['status'] = $status_filter;
            if (!empty($search_query)) $query_params['search'] = $search_query;
            if (!empty($date_from)) $query_params['date_from'] = $date_from;
            if (!empty($date_to)) $query_params['date_to'] = $date_to;
            $query_string = !empty($query_params) ? '&' . http_build_query($query_params) : '';
            
            // Previous button
            if ($current_page > 1) {
                echo '<li class="page-item">';
                echo '<a class="page-link" href="?page=' . ($current_page - 1) . $query_string . '" aria-label="Previous">';
                echo '<span aria-hidden="true">&laquo;</span>';
                echo '</a></li>';
            }
            
            // Page numbers
            for ($i = 1; $i <= $total_pages; $i++) {
                echo '<li class="page-item ' . ($i == $current_page ? 'active' : '') . '">';
                echo '<a class="page-link" href="?page=' . $i . $query_string . '">' . $i . '</a></li>';
            }
            
            // Next button
            if ($current_page < $total_pages) {
                echo '<li class="page-item">';
                echo '<a class="page-link" href="?page=' . ($current_page + 1) . $query_string . '" aria-label="Next">';
                echo '<span aria-hidden="true">&raquo;</span>';
                echo '</a></li>';
            }
            
            echo '</ul></nav>';
        }
    }
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <title>Active Orders - DMMMSU ATBI MARKETPLACE</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" href="../assets/images/dmmmsu.ico">
        <link rel="stylesheet" href="../assets/css/style.css">
        <link rel="stylesheet" href="../assets/css/user1.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        
        <!-- Required JavaScript files -->
        <script src="../assets/js/vendor-all.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
        <script src="../assets/plugins/bootstrap/js/bootstrap.min.js"></script>
        <script src="../assets/js/pcoded.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>

        <style>
            .pcoded-navbar {
                z-index: 1030;
            }
            
            @media (max-width: 991px) {
                .pcoded-navbar {
                    position: fixed;
                    left: -264px;
                    top: 0;
                    height: 100vh;
                    min-height: 100vh;
                    transition: all 0.3s ease-in-out;
                }
                
                .pcoded-navbar.mob-open {
                    left: 0;
                }
                
                .pcoded-navbar .mobile-menu {
                    display: block;
                }
            }

            .pcoded-main-container {
                margin-top: 70px;
                padding: 20px;
                position: relative;
                min-height: calc(100vh - 70px);
            }

            /* Add overlay styles */
            .nav-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1029;
            }

            .nav-overlay.show {
                display: block;
            }

            /* Rest of your existing styles */
            .table-responsive {
                overflow-x: auto;
            }
            .status-select {
                width: auto;
                display: inline-block;
            }
            .proof-image img {
                transition: transform 0.3s ease;
            }
            .proof-image img:hover {
                transform: scale(1.1);
            }
            .custom-modal {
                display: none;
                position: fixed;
                z-index: 1031;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.9);
            }
            .modal-content {
                margin: auto;
                display: block;
                width: 80%;
                max-width: 700px;
                position: relative;
                top: 50%;
                transform: translateY(-50%);
            }
            .close-btn {
                position: absolute;
                right: 15px;
                top: 15px;
                color: #f1f1f1;
                font-size: 40px;
                font-weight: bold;
                cursor: pointer;
            }
            .filter-section {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
            }
            .filter-row {
                margin-bottom: 10px;
            }
            .reset-btn {
                margin-left: 10px;
            }
        </style>
    </head>

    <body>
        <!-- [ Pre-loader ] start -->
        <div class="loader-bg">
            <div class="loader-track">
                <div class="loader-fill"></div>
            </div>
        </div>
        <!-- [ Pre-loader ] End -->

        <?php include('../includes/seller_navigation.php'); ?>
        <?php include('../includes/seller_header.php'); ?>

        <!-- Add overlay div -->
        <div class="nav-overlay"></div>

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
                                                <h5>Active Orders</h5>
                                            </div>
                                            <div class="card-body">
                                                <!-- Filter and Search Section -->
                                                <div class="filter-section">
                                                    <form method="GET" action="">
                                                        <div class="row filter-row">
                                                            <div class="col-md-3">
                                                                <label for="status">Status:</label>
                                                                <select name="status" id="status" class="form-control">
                                                                    <option value="">All Statuses</option>
                                                                    <option value="Pending" <?= $status_filter == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                                    <option value="Out for Delivery" <?= $status_filter == 'Out for Delivery' ? 'selected' : '' ?>>Out for Delivery</option>
                                                                    <option value="Completed" <?= $status_filter == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                                                    <option value="Cancelled" <?= $status_filter == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label for="search">Search:</label>
                                                                <input type="text" name="search" id="search" class="form-control" placeholder="Order ID, Customer, or Product" value="<?= htmlspecialchars($search_query) ?>">
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label for="date_from">Date From:</label>
                                                                <input type="text" name="date_from" id="date_from" class="form-control datepicker" placeholder="Select start date" value="<?= htmlspecialchars($date_from) ?>">
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label for="date_to">Date To:</label>
                                                                <input type="text" name="date_to" id="date_to" class="form-control datepicker" placeholder="Select end date" value="<?= htmlspecialchars($date_to) ?>">
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-12 text-right">
                                                                <button type="submit" class="btn btn-primary">
                                                                    <i class="fas fa-filter"></i> Apply Filters
                                                                </button>
                                                                <a href="?" class="btn btn-secondary reset-btn">
                                                                    <i class="fas fa-sync-alt"></i> Reset
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                                
                                                <?php 
                                                displayOrdersTable($result_orders);
                                                displayPagination($total_pages, $page, $status_filter, $search_query, $date_from, $date_to);
                                                ?>
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

        <!-- Modal for displaying proof of payment -->
        <div id="proofModal" class="custom-modal">
            <span class="close-btn">&times;</span>
            <img class="modal-content" id="modalImage">
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize date pickers
            flatpickr(".datepicker", {
                dateFormat: "Y-m-d",
                allowInput: true
            });

            // Mobile menu functionality
            const mobileMenu = document.querySelector('.mobile-menu');
            const navbar = document.querySelector('.pcoded-navbar');
            const overlay = document.querySelector('.nav-overlay');

            if (mobileMenu) {
                mobileMenu.addEventListener('click', function(e) {
                    e.preventDefault();
                    navbar.classList.toggle('mob-open');
                    overlay.classList.toggle('show');
                    document.body.style.overflow = navbar.classList.contains('mob-open') ? 'hidden' : '';
                });
            }

            // Close menu when clicking overlay
            if (overlay) {
                overlay.addEventListener('click', function() {
                    navbar.classList.remove('mob-open');
                    overlay.classList.remove('show');
                    document.body.style.overflow = '';
                });
            }

            // Close menu when clicking outside
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 991) {
                    if (!navbar.contains(e.target) && !mobileMenu.contains(e.target)) {
                        navbar.classList.remove('mob-open');
                        overlay.classList.remove('show');
                        document.body.style.overflow = '';
                    }
                }
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 991) {
                    navbar.classList.remove('mob-open');
                    overlay.classList.remove('show');
                    document.body.style.overflow = '';
                }
            });

            // Modal functionality
            const modal = document.getElementById('proofModal');
            const modalImg = document.getElementById('modalImage');
            const closeBtn = document.getElementsByClassName('close-btn')[0];
            
            // Open modal
            document.querySelectorAll('.proof-image').forEach(function(element) {
                element.onclick = function() {
                    modal.style.display = 'block';
                    modalImg.src = this.dataset.image;
                }
            });
            
            // Close modal
            if (closeBtn) {
                closeBtn.onclick = function() {
                    modal.style.display = 'none';
                }
            }
            
            // Close modal when clicking outside
            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }
        });
        </script>
    </body>
    </html>