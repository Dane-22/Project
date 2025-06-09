<?php
require_once '../../connection/conn.php';
require_once '../function.php';

session_start();

try {
    // Check if user is logged in
    if (!isset($_SESSION['id'])) {
        throw new Exception('Unauthorized access');
    }

    $owner_id = $_SESSION['id']; // Get owner_id from session

    // Get month and year from request, default to current if not provided
    $month = isset($_POST['month']) ? $_POST['month'] : date('m');
    $year = isset($_POST['year']) ? $_POST['year'] : date('Y');
    $day = isset($_POST['day']) ? $_POST['day'] : null;

  // Validate month, year, and day
  if (!is_numeric($month) || !is_numeric($year) || 
  $month < 1 || $month > 12 || 
  strlen($year) !== 4 || 
  ($day !== null && (!is_numeric($day) || $day < 1 || $day > 31))) {
  throw new Exception('Invalid month, year, or day format');
}

    // Get orders for the selected month and year
    $query = " 
        SELECT 
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
            oi.owner_id = ?
            AND MONTH(o.order_date) = ? 
            AND YEAR(o.order_date) = ?";
             // Add day filter if provided
    if ($day !== null) {
        $query .= " AND DAY(o.order_date) = ?";
    }
    $query .= " ORDER BY o.order_date DESC";
    
    $stmt = $db->prepare($query);
    if ($day !== null) {  
    $stmt->bind_param('siii', $owner_id, $month, $year, $day);
} else {
    $stmt->bind_param('sii', $owner_id, $month, $year);
}

    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = array();
    $total = 0; // Initialize total
    
    while ($row = $result->fetch_assoc()) {
        $order = array(
            'name'              => htmlspecialchars($row['name']),
            'product_name'      => htmlspecialchars($row['product_name']),
            'price'             => floatval($row['price']),
            'quantity'          => intval($row['quantity']),    
            'total_item_price'  => floatval($row['total_item_price']),
            'order_date'        => $row['order_date'],
            'order_status'      => htmlspecialchars($row['order_status'])
        );
        $orders[] = $order;
        $total += $order['total_item_price']; // Calculate total
    }

    // Prepare response array
    $response = array(
        'success' => true,
        'total' => number_format($total, 2, '.', ''),
        'sales' => $orders
    );

    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;

} catch (Exception $e) {
    // Handle any errors
    header('Content-Type: application/json');
    $error_response = array(
        'success' => false,
        'error' => $e->getMessage(),
        'total' => '0.00',
        'sales' => array()
    );
    echo json_encode($error_response);
    exit;
}