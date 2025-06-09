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
} else {
    echo "User  not found.";
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the form data
    $username = mysqli_real_escape_string($db, $_POST['username']);
    $email = mysqli_real_escape_string($db, $_POST['email']);
    $firstname = mysqli_real_escape_string($db, $_POST['firstname']);
    $middlename = mysqli_real_escape_string($db, $_POST['middlename']);
    $lastname = mysqli_real_escape_string($db, $_POST['lastname']);
    $password = $_POST['password'];

    // Prepare the SQL update statement
    $sql = "UPDATE users SET uname='$username', email='$email', fname='$firstname', mname='$middlename', lname='$lastname'";

    // If a new password is provided, hash it and include it in the update
    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql .= ", password='$hashedPassword'";
    }

    $sql .= " WHERE id='$id'";

    // Execute the update query
    if (mysqli_query($db, $sql)) {
        // Redirect to the profile page after successful update
        header("Location: profile.php");
        exit();
    } else {
        echo "Error updating profile: " . mysqli_error($db);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="assets/css/../style.css">
    <style>
        /* Reset some default styles */
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }

        /* Profile wrapper styling */
        .profile-wrapper {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* Heading styling */
        h2 {
            text-align: center;
            color: #333;
        }

        /* Form label styling */
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }

        /* Input field styling */
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }

        /* Input field focus effect */
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #007bff;
            outline: none;
        }

        /* Button styling */
        button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        /* Button hover effect */
        button:hover {
            background-color: #0056b3;
        }

        /* Back button styling */
        .btn-back {
            background-color: #6c757d; /* Grey color */
            margin-top: 10px; /* Add some space above the back button */
        }

        /* Back button hover effect */
        .btn-back:hover {
            background-color: # 5a6268; /* Darker grey on hover */
        }
    </style>
</head>
<body>
    <div class="profile-wrapper">
        <h2>Edit Profile</h2>
        <form action="edit-profile.php" method="POST">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user->uname); ?>" required />

            <label for="email">Email:</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user->email); ?>" required />

            <label for="firstname">First Name:</label>
            <input type="text" name="firstname" id="firstname" value="<?php echo htmlspecialchars($user->fname); ?>" required />

            <label for="middlename">Middle Name:</label>
            <input type="text" name="middlename" id="middlename" value="<?php echo htmlspecialchars($user->mname); ?>" />

            <label for="lastname">Last Name:</label>
            <input type="text" name="lastname" id="lastname" value="<?php echo htmlspecialchars($user->lname); ?>" required />

            <!-- <label for="password">New Password:</label>
            <input type="password" name="password" id="password" placeholder="Leave blank to keep current password" /> -->

            <button type="submit">Update Profile</button>
            <a href="profile.php" class="btn btn-back">Back</a>
        </form>
    </div>
</body>
</html>