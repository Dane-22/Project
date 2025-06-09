<?php
require '../connection/conn.php';

// Get the order ID from the URL
$order_id = $_GET['order_id'];

// Get the updated order data from the form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $updated_data = array(
        'name' => $_POST['name'],
        'total_price' => $_POST['total_price'],
        'payment_method' => $_POST['payment_method'],
        'order_status' => $_POST['order_status']
    );

    // Validate the updated data
    if (empty($updated_data['name']) || empty($updated_data['total_price']) || empty($updated_data['payment_method']) || empty($updated_data['order_status'])) {
        $error = 'Please fill in all fields.';
    } else {
        // Update the order in the database
        $sql = "UPDATE orders1 SET name = ?, total_price = ?, payment_method = ?, order_status = ? WHERE order_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('sssss', $updated_data['name'], $updated_data['total_price'], $updated_data['payment_method'], $updated_data['order_status'], $order_id);
        $stmt->execute();

        // Check if the update was successful
        if ($stmt->affected_rows == 1) {
            $success = 'Order updated successfully.';
        } else {
            $error = 'Error updating order.';
        }
    }
}

// Get the current order data from the database
$sql = "SELECT * FROM orders1 WHERE order_id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param('s', $order_id);
$stmt->execute();
$result = $stmt->get_result();
$current_order = $result->fetch_assoc();

// Display the update form
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Update Order</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h1>Update Order</h1>
        <?php if (isset($error)) : ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (isset($success)) : ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form action="" method="post">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo $current_order['name']; ?>">
            </div>
            <div class="form-group">
                <label for="total_price">Total Price:</label>
                <input type="number" class="form-control" id="total_price" name="total_price" value="<?php echo $current_order['total_price']; ?>">
            </div>
            <div class="form-group">
                <label for="payment_method">Payment Method:</label>
                <input type="text" class="form-control" id="payment_method" name="payment_method" value="<?php echo $current_order['payment_method']; ?>">
            </div>
            <div class="form-group">
                <label for="order_status">Order Status:</label>
                <input type="text" class="form-control" id="order_status" name="order_status" value="<?php echo $current_order['order_status']; ?>">
            </div>
            <button type="submit" class="btn btn-primary">Update Order</button>
        </form>
    </div>
</body>
</html>