<?php
require('../../connection/conn.php');
require('../../function.php');

session_start();
if (!isset($_SESSION['id'])) {
    die(json_encode(['error' => 'Unauthorized access']));
}

$owner_id = $_SESSION['id'];

// Get filter parameters
$month = isset($_GET['month']) ? $_GET['month'] : null;
$day = isset($_GET['day']) ? $_GET['day'] : null;
$year = isset($_GET['year']) ? $_GET['year'] : null;

// Build the query
$query = "SELECT 
            o.name, 
            p.name AS product_name,
            oi.price,
            oi.quantity,
            oi.total_item_price,
            o.order_date, 
            o.order_status
        FROM 
            orders1 as o
        INNER JOIN 
            order_items as oi 
                ON 
                    o.order_id = oi.order_id              
        INNER JOIN 
            products as p 
                ON 
                    oi.product_id = p.id
        WHERE 
            p.owner_id = ?";

$params = [$owner_id];
$types = "s";

// Add date filters
if ($year) {
    $query .= " AND YEAR(o.order_date) = ?";
    $params[] = $year;
    $types .= "i";
}

if ($month) {
    $query .= " AND MONTH(o.order_date) = ?";
    $params[] = $month;
    $types .= "i";
}

if ($day) {
    $query .= " AND DAY(o.order_date) = ?";
    $params[] = $day;
    $types .= "i";
}

// For server-side processing
$start = isset($_GET['start']) ? intval($_GET['start']) : 0;
$length = isset($_GET['length']) ? intval($_GET['length']) : 10;
$search = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';

// Add search filter
if ($search) {
    $query .= " AND (p.name LIKE ? OR o.name LIKE ? OR o.order_status LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

// Get total records count
$countQuery = "SELECT COUNT(*) as total FROM ($query) as subquery";
$stmt = $db->prepare($countQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$totalRecords = $stmt->get_result()->fetch_assoc()['total'];

// Add sorting and pagination
$orderColumn = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 5; // Default to date ordered
$orderDir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'desc';
$columns = ['name', 'product_name', 'price', 'quantity', 'total_item_price', 'order_date', 'order_status'];

$query .= " ORDER BY {$columns[$orderColumn]} {$orderDir} LIMIT ?, ?";
$params[] = $start;
$params[] = $length;
$types .= "ii";

// Execute main query
$stmt = $db->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// Calculate total sales amount for the filtered results
$totalAmountQuery = "SELECT SUM(oi.total_item_price) as total_amount 
                     FROM order_items oi
                     JOIN orders1 o ON oi.order_id = o.order_id
                     JOIN products p ON oi.product_id = p.id
                     WHERE p.owner_id = ?";

$whereConditions = [];
$totalParams = [$owner_id];
$totalTypes = "i";

if ($year) {
    $whereConditions[] = "YEAR(o.order_date) = ?";
    $totalParams[] = $year;
    $totalTypes .= "i";
}

if ($month) {
    $whereConditions[] = "MONTH(o.order_date) = ?";
    $totalParams[] = $month;
    $totalTypes .= "i";
}

if ($day) {
    $whereConditions[] = "DAY(o.order_date) = ?";
    $totalParams[] = $day;
    $totalTypes .= "i";
}

if (count($whereConditions) > 0) {
    $totalAmountQuery .= " AND " . implode(" AND ", $whereConditions);
}

$stmt = $db->prepare($totalAmountQuery);
$stmt->bind_param($totalTypes, ...$totalParams);
$stmt->execute();
$totalAmount = $stmt->get_result()->fetch_assoc()['total_amount'] ?? 0;

// Return JSON response
echo json_encode([
    'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
    'recordsTotal' => $totalRecords,
    'recordsFiltered' => $totalRecords,
    'data' => $data,
    'totalAmount' => number_format($totalAmount, 2)
]);