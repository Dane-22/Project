<?php
require_once '../../connection/conn.php';
require_once '../phinry.php';

header('Content-Type: application/json');

$period = isset($_GET['period']) ? $_GET['period'] : 'daily';

// For debugging
error_log("Fetching sales trend for period: $period");

$phinry = new Phinry($db);  
$result = $phinry->getSalesTrend($period);

if ($result['success'] && empty($result['data'])) {
    // If no data, return a default structure
    $result['data'] = [
        [
            'date' => date('Y-m-d'),
            'total' => 0
        ]
    ];
}

echo json_encode($result);
