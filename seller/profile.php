<?php
session_start();
require('../connection/conn.php'); // Include database connection

// Check if the user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: auth-signin.php");
    exit();
}

// Fetch user data
$id = $_SESSION['id']; // Get the user ID from the session
$sql = "SELECT * FROM users WHERE id = '$id'";
$query = mysqli_query($db, $sql);

// Check if the query was successful
if (!$query) {
    echo "Database query failed: " . mysqli_error($db);
    exit();
}

// Check if a user was found
if (mysqli_num_rows($query) === 1) {
    $user = mysqli_fetch_object($query);
    $fname = htmlspecialchars($user->fname);
    $mname = htmlspecialchars($user->mname);
    $lname = htmlspecialchars($user->lname);
} else {
    // Default guest details or leave empty
    $fname = "Guest";
    $mname = "";
    $lname = "";
}

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

function updateCartCount() {
    return count($_SESSION['cart']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Add some basic styling */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .profile-wrapper {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .profile-header {
            display: flex;
            align-items: center;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .profile-header img {
            border-radius: 50%;
            width: 100px;
            height: 100px;
            object-fit: cover;
            margin-right: 20px;
        }
        .profile-header h2 {
            margin: 0;
        }
        .profile-content {
            padding: 10px;
        }
        .profile-content p {
            margin: 10px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            color: #fff;
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 16px;
            cursor: pointer;
            text-align: center;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="profile-wrapper">
        <div class="profile-header">
            <img src="assets/images/user-avatar.png" alt="User  Avatar"> <!-- Placeholder for user profile picture -->
            <div>
                <h2><?php echo htmlspecialchars($user->uname); ?></h2>
                <p>Email: <?php echo htmlspecialchars($user->email); ?></p>
                <!-- <p>Role: <?php echo htmlspecialchars($user->role); ?></p> -->
                <!-- <p>Member since: <?php echo date('F j, Y', strtotime($user->created_at)); ?></p> -->
            </div>
        </div>
        <div class="profile-content">
            <a href="edit-profile.php" class="btn">Edit Profile</a>
            <!-- <a href="order-history.php" class="btn">Order History</a> -->
            <a href="../session_destroy.php" class="btn btn-secondary">Logout</a>
            <a href="dashboard.php" class="btn btn-secondary">Back</a>
        </div>
    </div>
    <script src="assets/js/vendor-all.min.js"></script>
    <script src="assets/plugins/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>