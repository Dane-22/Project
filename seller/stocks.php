<?php
require('../connection/conn.php');  // Database connection
ob_start(); // Start output buffering
session_start();
// Include seller's navigation and header

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    echo "<script>";
    echo "window.location.href='../../auth-signin.php';"; // Redirect to sign-in if not logged in
    echo "</script>";
    exit;
}

$id = $_SESSION['id']; // Get the user ID from the session

// Fetch user details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $db->error);
}
$stmt->bind_param("i", $id);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}
$result = $stmt->get_result();
$row = $result->fetch_object();
$fname = htmlspecialchars($row->fname);
$mname = htmlspecialchars($row->mname);
$lname = htmlspecialchars($row->lname);

// Handle form submission for editing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
    $product_id = $_POST['product_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    
    // Handle image upload if a new image is provided
    if ($_FILES['image']['size'] > 0) {
        $target_dir = "../uploads/";
        $target_file = $target_dir . basename($_FILES["image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Check if image file is a actual image
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if($check !== false) {
            // Generate unique filename
            $new_filename = uniqid() . '.' . $imageFileType;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_path = $target_file;
            } else {
                echo "<script>alert('Sorry, there was an error uploading your file.');</script>";
                $image_path = $_POST['existing_image'];
            }
        } else {
            echo "<script>alert('File is not an image.');</script>";
            $image_path = $_POST['existing_image'];
        }
    } else {
        $image_path = $_POST['existing_image'];
    }
    
    // Update the product in the database
    $update_sql = "UPDATE products SET name = ?, product_description = ?, price = ?, product_quantity = ?, image = ? WHERE id = ? AND user_id = ?";
    $update_stmt = $db->prepare($update_sql);
    if (!$update_stmt) {
        die("Prepare failed: " . $db->error);
    }
    $update_stmt->bind_param("ssdisii", $name, $description, $price, $quantity, $image_path, $product_id, $id);
    
    if ($update_stmt->execute()) {
        echo "<script>alert('Product updated successfully!'); window.location.href = 'stocks.php';</script>";
    } else {
        echo "<script>alert('Error updating product: " . $update_stmt->error . "');</script>";
    }
}

// Fetch the user's submitted products
$sql = "SELECT * FROM products WHERE user_id = ? ORDER BY id DESC";
$stmt = $db->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $db->error);
}
$stmt->bind_param("i", $id);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}
$product_result = $stmt->get_result();

// Check if an delete request is made
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    // First check if the product has 0 quantity
    $check_sql = "SELECT product_quantity FROM products WHERE id = ? AND user_id = ?";
    $check_stmt = $db->prepare($check_sql);
    if (!$check_stmt) {
        die("Prepare failed: " . $db->error);
    }
    $check_stmt->bind_param("ii", $delete_id, $id);
    if (!$check_stmt->execute()) {
        die("Execute failed: " . $check_stmt->error);
    }
    $check_result = $check_stmt->get_result();
    $product_data = $check_result->fetch_assoc();
    
    if ($product_data['product_quantity'] == 0) {
        echo "<script>";
        echo "alert('Cannot delete product with 0 quantity!');";
        echo "window.location.href = 'stocks.php';";
        echo "</script>";
    } else {
        // Prepare SQL query to delete the product by its ID
        $delete_sql = "DELETE FROM products WHERE id = ? AND user_id = ?";
        $delete_stmt = $db->prepare($delete_sql);

        if (!$delete_stmt) {
            die("Prepare failed: " . $db->error);
        }

        $delete_stmt->bind_param("ii", $delete_id, $id); // Bind the delete ID and user ID

        if ($delete_stmt->execute()) {
            echo "<script>";
            echo "alert('Product deleted successfully!');";
            echo "window.location.href = 'stocks.php';"; // Redirect to the products page
            echo "</script>";
        } else {
            die("Execute failed: " . $delete_stmt->error);
        }
    }
}   
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>DMMMSU ATBI MARKETPLACE</title>
    
    <!-- Meta Tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- Favicon -->
    <link rel="icon" href="assets/images/dmmmsu.ico" type="image/x-icon">

    <!-- CSS Files -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/user1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">

    <!-- JavaScript Libraries -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/raphael/2.3.0/raphael.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/vendor-all.min.js"></script>
    <script src="../assets/js/pcoded.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <style>
        .pcoded-main-container {
            margin-top: 70px;
            padding: 20px;
            position: relative;
            min-height: calc(100vh - 70px);
        }
        
        /* Description cell styles */
        .description-cell {
            max-width: 300px;
            white-space: normal;
            word-wrap: break-word;
        }
        
        .short-description {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .full-description {
            display: none;
            white-space: normal;
        }
        
        .read-more-btn {
            color: #007bff;
            cursor: pointer;
            font-size: 12px;
            display: inline-block;
            margin-top: 5px;
        }
        
        .read-more-btn:hover {
            text-decoration: underline;
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        
        .action-buttons {
            white-space: nowrap;
        }
        
        @media (max-width: 768px) {
            .description-cell {
                max-width: 200px;
            }
            
            .product-image {
                width: 40px;
                height: 40px;
            }
        }
        
        /* Table styling */
        table.dataTable {
            width: 100% !important;
            margin: 0 auto;
            clear: both;
            border-collapse: collapse;
        }
        
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px 10px;
        }
    </style>
</head>
<body>
<!-- [Pre-loader and navigation includes...] -->
<?php include('../includes/seller_navigation.php'); ?>
<?php include('../includes/seller_header.php'); ?>

    <!-- [ Pre-loader ] start -->
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>
    <!-- [ Pre-loader ] End -->

    <!-- [ Main Content ] start -->
    <div class="pcoded-main-container">
        <div class="pcoded-wrapper">
            <div class="pcoded-content">
                <div class="pcoded-inner-content">
                    <div class="main-body">
                        <div class="page-wrapper">
                            <h1>My Products</h1>

                            <!-- Product Table Wrapper -->
                            <div class="table-responsive">
                                <table id="products-table" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Product Name</th>
                                            <th>Description</th>
                                            <th>Price</th>
                                            <th>Image</th>
                                            <th>Quantity</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($product_result->num_rows > 0) {
                                            while ($product_row = $product_result->fetch_assoc()) {
                                                $product_price = floatval($product_row["price"]);
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($product_row["name"]) . "</td>";
                                                echo '<td class="description-cell">';
                                                echo '<div class="short-description">' . htmlspecialchars($product_row["product_description"]) . '</div>';
                                                echo '<div class="full-description">' . htmlspecialchars($product_row["product_description"]) . '</div>';
                                                if (strlen($product_row["product_description"]) > 100) {
                                                    echo '<span class="read-more-btn">Read more</span>';
                                                }
                                                echo "</td>";
                                                echo "<td>₱" . number_format($product_price, 2) . "</td>";
                                                echo "<td><img src='" . htmlspecialchars($product_row["image"]) . "' alt='Product Image' class='product-image'></td>";
                                                echo "<td>" . htmlspecialchars($product_row["product_quantity"]) . "</td>";
                                                echo "<td class='action-buttons'>";
                                                
                                                // Edit button that triggers modal
                                                echo "<button class='btn btn-info btn-sm' data-toggle='modal' data-target='#editModal".$product_row['id']."'>Edit</button> ";
                                                
                                                // Delete button (disabled if quantity is 0)
                                                if ($product_row["product_quantity"] == 0) {
                                                    echo "<button class='btn btn-secondary btn-sm' disabled>Cannot Delete</button>";
                                                } else {
                                                    echo "<a href='?delete_id=" . $product_row['id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to delete this product?\");'>Delete</a>";
                                                }
                                                
                                                echo "</td>";
                                                echo "</tr>";
                                                
                                                // Edit Modal for each product
                                                echo '
                                                <div class="modal fade" id="editModal'.$product_row['id'].'" tabindex="-1" role="dialog" aria-labelledby="editModalLabel'.$product_row['id'].'" aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="editModalLabel'.$product_row['id'].'">Edit Product</h5>
                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <form method="POST" action="" enctype="multipart/form-data">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="product_id" value="'.$product_row['id'].'">
                                                                    <input type="hidden" name="existing_image" value="'.$product_row['image'].'">
                                                                    
                                                                    <div class="form-group">
                                                                        <label for="name">Product Name</label>
                                                                        <input type="text" class="form-control" id="name" name="name" value="'.htmlspecialchars($product_row['name']).'" required>
                                                                    </div>
                                                                    
                                                                    <div class="form-group">
                                                                        <label for="description">Description</label>
                                                                        <textarea class="form-control" id="description" name="description" rows="3" required>'.htmlspecialchars($product_row['product_description']).'</textarea>
                                                                    </div>
                                                                    
                                                                    <div class="form-group">
                                                                        <label for="price">Price</label>
                                                                        <input type="number" step="0.01" class="form-control" id="price" name="price" value="'.htmlspecialchars($product_row['price']).'" required>
                                                                    </div>
                                                                    
                                                                    <div class="form-group">
                                                                        <label for="quantity">Quantity</label>
                                                                        <input type="number" class="form-control" id="quantity" name="quantity" value="'.htmlspecialchars($product_row['product_quantity']).'" required>
                                                                    </div>
                                                                    
                                                                    <div class="form-group">
                                                                        <label for="image">Product Image</label>
                                                                        <input type="file" class="form-control-file" id="image" name="image">
                                                                        <small class="form-text text-muted">Leave blank to keep current image</small>
                                                                        <img src="'.$product_row['image'].'" alt="Current Image" class="product-image" style="margin-top: 10px;">
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                                    <button type="submit" name="update_product" class="btn btn-primary">Save changes</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                                ';
                                            }
                                        } else {
                                            echo "<tr><td colspan='6' class='text-center'>No products submitted.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- End of Product Table Wrapper -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#products-table').DataTable({
                "responsive": true,
                "pageLength": 10,
                "language": {
                    "search": "Search products:",
                    "lengthMenu": "Show _MENU_ entries per page",
                    "zeroRecords": "No matching products found",
                    "info": "Showing _START_ to _END_ of _TOTAL_ products",
                    "infoEmpty": "Showing 0 to 0 of 0 products",
                    "infoFiltered": "(filtered from _MAX_ total products)",
                    "paginate": {
                        "previous": "<i class='fas fa-chevron-left'></i>",
                        "next": "<i class='fas fa-chevron-right'></i>"
                    }
                }
            });
            
            // Read more/less functionality
            $(document).on('click', '.read-more-btn', function() {
                var $parent = $(this).closest('.description-cell');
                $parent.find('.short-description').toggle();
                $parent.find('.full-description').toggle();
                
                if ($(this).text() === "Read more") {
                    $(this).text("Read less");
                } else {
                    $(this).text("Read more");
                }
            });
        });
    </script>
</body>
</html>