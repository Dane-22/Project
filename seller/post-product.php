
<?php 

require('../connection/conn.php');
session_start();

// echo $_SESSION['id']; die();

if (!$_SESSION['id']) {
    echo "<script>";
    echo "window.location.href='../auth-signin.php';";
    echo "</script>";
}

$id = $_SESSION['id'];

$sql = "SELECT 
                * 
        FROM 
                users 
        WHERE 
                id = '$id'";
                
$query  = mysqli_query($db,$sql);
$row    = mysqli_fetch_object($query);

$fname = $row->fname;
$mname = $row->mname;
$lname = $row->lname;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>POST PRODUCT</title>
    
    <!-- Meta Tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- Favicon -->
    <link rel="icon" href="assets/images/dmmmsu.ico" type="image/x-icon">

    <!-- CSS Files -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/user1.css">
 
    
    <!-- JavaScript Libraries -->
    <script src="../assets/js/vendor-all.min.js"></script>
    <script src="../assets/plugins/bootstrap/js/bootstrap.min.js"></script>
    <script src="../assets/js/pcoded.min.js"></script>

    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
    

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

    <!-- [ Main Content ] start -->
<div class="pcoded-main-container">
    <div class="pcoded-wrapper">
        <div class="pcoded-content">
            <div class="pcoded-inner-content">
                <!-- [ breadcrumb ] start -->
                <div class="page-header">
                    <div class="page-block">
                        <div class="row align-items-center">
                            <div class="col-md-12">
                                <ul class="breadcrumb">
                                    <li class="breadcrumb-item"><a href=""><i class="feather icon-home"></i></a></li>
                                    <li class="breadcrumb-item"><a href="javascript:">Post Product</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- [ breadcrumb ] end -->

                <!-- [ Product Posting Form ] start -->
                <div class="page-body">
                    <div class="row">
                        <div class="col-md-8 offset-md-2">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Post a New Product</h5>
                                </div>
                                <div class="card-body">
                                <form id="productForm" action="submit_product.php" method="POST" enctype="multipart/form-data">
                                        <div class="form-group">
                                            <label for="product_name">Product Name</label>
                                            <input type="text" class="form-control" id="product_name" name="product_name" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="product_price">Product Price</label>
                                            <input type="text" class="form-control" id="product_price" name="product_price" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="product_description">Product Description</label>
                                            <textarea class="form-control" id="product_description" name="product_description" rows="4"></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="product_quantity">Product Quantity</label>
                                            <input type="number" class="form-control" id="product_quantity" name="product_quantity" min="1" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="product_image">Product Image</label>
                                            <input type="file" class="form-control-file" id="product_image" name="product_image" accept="image/*" required>
                                        </div>
                                        <button type="button    " class="btn btn-primary">Submit</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
    function submitProduct() {
        // Validate form first
        const form = document.getElementById('productForm');
        const productName = document.getElementById('product_name').value;
        const productPrice = document.getElementById('product_price').value;
        const productQuantity = document.getElementById('product_quantity').value;
        const productImage = document.getElementById('product_image').value;
        
        if (!productName || !productPrice || !productQuantity || !productImage) {
            alert('Please fill in all required fields');
            return;
        }
        
        // Submit the form via AJAX
        const formData = new FormData(form);
        
        fetch('submit_product.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            // Show success message
            alert('Product request has been sent, wait for approval');
            // Optionally reset the form
            form.reset();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while submitting the product');
        });
    }
    </script>

</body>
</html>
