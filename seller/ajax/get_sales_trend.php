<?php
require('../../connection/conn.php'); // Include database connection
require('../seller.php'); // Include Seller class

session_start();

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Validate input parameters
if (!isset($_POST['month']) || !isset($_POST['year'])) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$month = $_POST['month'];
$year = $_POST['year'];
$owner_id = $_SESSION['id']; // Get owner_id from session

// Validate month and year
if (!preg_match("/^(0[1-9]|1[0-2])$/", $month) || !preg_match("/^\d{4}$/", $year)) {
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

// Initialize Seller class
$seller = new Seller($db);

// Get daily sales data for the month
$query = "SELECT 
    DATE(o.created_at) as date,
    SUM(oi.price * oi.quantity) as total
FROM orders1 o
JOIN order_items oi ON o.order_id = oi.order_id
WHERE MONTH(o.created_at) = ? 
AND YEAR(o.created_at) = ? 
AND oi.owner_id = ?
AND o.order_status IN('Out for Delivery', 'Completed')
GROUP BY DATE(o.created_at)
ORDER BY date ASC";

$stmt = $db->prepare($query);
if ($stmt === false) {
    echo json_encode(['error' => 'Failed to prepare statement: ' . $db->error]);
    exit;
}

$stmt->bind_param('ssi', $month, $year, $owner_id); // Bind parameters
$stmt->execute();
$result = $stmt->get_result();

if ($result === false) {
    echo json_encode(['error' => 'Failed to execute query: ' . $stmt->error]);
    exit;
}

$salesData = array();
while ($row = $result->fetch_assoc()) {
    $salesData[$row['date']] = floatval($row['total']);
}

// Fill in missing days with zero
$firstDay = sprintf("%04d-%02d-01", $year, $month);
$lastDay = date('Y-m-t', strtotime($firstDay));
$currentDay = $firstDay;

while ($currentDay <= $lastDay) {
    if (!isset($salesData[$currentDay])) {
        $salesData[$currentDay] = 0;
    }
    $currentDay = date('Y-m-d', strtotime($currentDay . ' +1 day'));
}

// Sort by date
ksort($salesData);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($salesData);