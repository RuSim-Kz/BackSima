<?php
require_once 'config.php';

header('Content-Type: application/json');

// Получаем соединение с БД
$pdo = getDbConnection();

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

try {
    $startTime = microtime(true);
    
    // Оптимизированный запрос для получения статистики по последним 100 заказам
    $query = "
        WITH last_100_orders AS (
            SELECT 
                o.id,
                o.purchase_time,
                o.quantity,
                o.total_price,
                p.category_id,
                c.name as category_name
            FROM orders o
            JOIN products p ON o.product_id = p.id
            JOIN categories c ON p.category_id = c.id
            ORDER BY o.purchase_time DESC
            LIMIT 100
        ),
        time_range AS (
            SELECT 
                MIN(purchase_time) as first_order_time,
                MAX(purchase_time) as last_order_time,
                COUNT(*) as total_orders
            FROM last_100_orders
        ),
        category_stats AS (
            SELECT 
                category_id,
                category_name,
                SUM(quantity) as products_sold,
                SUM(total_price) as total_revenue,
                COUNT(*) as order_count
            FROM last_100_orders
            GROUP BY category_id, category_name
            ORDER BY products_sold DESC
        )
        SELECT 
            tr.first_order_time,
            tr.last_order_time,
            tr.total_orders,
            EXTRACT(EPOCH FROM (tr.last_order_time - tr.first_order_time)) as time_diff_seconds,
            COALESCE(
                json_agg(
                    json_build_object(
                        'category_id', cs.category_id,
                        'category_name', cs.category_name,
                        'products_sold', cs.products_sold,
                        'total_revenue', cs.total_revenue,
                        'order_count', cs.order_count
                    ) ORDER BY cs.products_sold DESC
                ) FILTER (WHERE cs.category_id IS NOT NULL),
                '[]'::json
            ) as category_statistics
        FROM time_range tr
        LEFT JOIN category_stats cs ON true
        GROUP BY tr.first_order_time, tr.last_order_time, tr.total_orders
    ";
    
    $stmt = $pdo->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        throw new Exception("No orders found in database");
    }
    
    $executionTime = round((microtime(true) - $startTime) * 1000, 2); // в миллисекундах
    
    // Форматируем время
    $timeDiff = (int)$result['time_diff_seconds'];
    $hours = floor($timeDiff / 3600);
    $minutes = floor(($timeDiff % 3600) / 60);
    $seconds = $timeDiff % 60;
    
    $formattedTimeDiff = sprintf(
        "%02d:%02d:%02d",
        $hours,
        $minutes,
        $seconds
    );
    
    $categoryStats = json_decode($result['category_statistics'], true);
    
    // Вычисляем общую статистику
    $totalProductsSold = 0;
    $totalRevenue = 0;
    foreach ($categoryStats as $stat) {
        $totalProductsSold += $stat['products_sold'];
        $totalRevenue += $stat['total_revenue'];
    }
    
    $response = [
        'status' => 'success',
        'timestamp' => date('Y-m-d H:i:s'),
        'execution_time_ms' => $executionTime,
        'data' => [
            'total_orders_analyzed' => (int)$result['total_orders'],
            'time_period' => [
                'first_order' => $result['first_order_time'],
                'last_order' => $result['last_order_time'],
                'duration' => $formattedTimeDiff,
                'duration_seconds' => $timeDiff
            ],
            'summary' => [
                'total_products_sold' => $totalProductsSold,
                'total_revenue' => round($totalRevenue, 2),
                'average_order_value' => $result['total_orders'] > 0 ? round($totalRevenue / $result['total_orders'], 2) : 0
            ],
            'category_statistics' => $categoryStats
        ]
    ];
    
    logMessage("Gamma script completed - Execution time: {$executionTime}ms");
    
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    logMessage("Gamma script error: " . $e->getMessage());
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
