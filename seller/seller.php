<?php
class Seller {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Get sales data by joining orders1 and order_items
     */
    public function getSalesByMonthYear($month, $year, $owner_id) {
        try {
            $query = "SELECT 
                COALESCE(SUM(oi.total_item_price), 0) as total,
                COUNT(DISTINCT o.order_id) as total_orders,
                AVG(oi.total_item_price) as average_order_value,
                MAX(oi.total_item_price) as highest_order,
                MIN(oi.total_item_price) as lowest_order,
                COUNT(CASE WHEN oi.status = 'completed' THEN 1 END) as completed_orders,
                COUNT(CASE WHEN oi.status = 'out for delivery' THEN 1 END) as pending_orders,
                COALESCE(SUM(CASE WHEN oi.status = 'completed' THEN oi.total_item_price ELSE 0 END), 0) as completed_total,
                COALESCE(SUM(CASE WHEN oi.status = 'out for delivery' THEN oi.total_item_price ELSE 0 END), 0) as pending_total
            FROM orders1 o
            JOIN order_items oi ON o.order_id = oi.order_id
            WHERE MONTH(o.created_at) = ? 
            AND YEAR(o.created_at) = ? 
            AND oi.owner_id = ?
            AND oi.status IN('completed', 'out for delivery')";

            $stmt = $this->db->prepare($query);
            $stmt->bind_param('iii', $month, $year, $owner_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return [
                'success' => true,
                'stats' => $result->fetch_assoc()
            ];

        } catch (Exception $e) {
            error_log("Error in getSalesByMonthYear: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get filtered sales by joining tables
     */
    public function getFilteredSales($month, $year, $day, $owner_id) {
        try {
            $query = "SELECT 
                o.order_id,
                o.name,
                p.name as product_name,
                oi.quantity,
                oi.price,
                oi.total_item_price,
                oi.status,
                o.created_at,
                o.payment_method
            FROM orders1 o
            JOIN order_items oi ON o.order_id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE MONTH(o.created_at) = ? 
            AND YEAR(o.created_at) = ? 
            AND oi.owner_id = ?
            AND oi.status IN('completed', 'out for delivery')";

            $params = [$month, $year, $owner_id];
            
            if (!empty($day)) {
                $query .= " AND DAY(o.created_at) = ?";
                $params[] = $day;
            }

            $query .= " ORDER BY o.created_at DESC";

            $stmt = $this->db->prepare($query);
            $stmt->bind_param(str_repeat('i', count($params)), ...$params);
            $stmt->execute();
            $result = $stmt->get_result();

            $sales = [];
            while ($row = $result->fetch_assoc()) {
                $sales[] = $row;
            }

            return $sales;

        } catch (Exception $e) {
            error_log("Error in getFilteredSales: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get daily sales trend from joined tables
     */
    public function getDailySalesTrend($month, $year, $owner_id) {
        try {
            $query = "SELECT 
                DATE(o.created_at) as date,
                COALESCE(SUM(oi.total_item_price), 0) as total_sales
            FROM orders1 o
            JOIN order_items oi ON o.order_id = oi.order_id
            WHERE MONTH(o.created_at) = ? 
            AND YEAR(o.created_at) = ? 
            AND oi.owner_id = ?
            AND oi.status IN('completed', 'out for delivery')
            GROUP BY DATE(o.created_at)";

            $stmt = $this->db->prepare($query);
            $stmt->bind_param('iii', $month, $year, $owner_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $salesData = [];
            while ($row = $result->fetch_assoc()) {
                $salesData[$row['date']] = (float)$row['total_sales'];
            }

            // Fill in missing dates
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $completeData = [];
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $completeData[$date] = $salesData[$date] ?? 0;
            }

            return $completeData;

        } catch (Exception $e) {
            error_log("Error in getDailySalesTrend: " . $e->getMessage());
            return [];
        }
    }
}
?>