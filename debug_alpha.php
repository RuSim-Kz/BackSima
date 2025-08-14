<?php
require_once 'config.php';

header('Content-Type: application/json');

// Получаем соединения с БД и Redis
$pdo = getDbConnection();
$redis = getRedisConnection();

if (!$pdo || !$redis) {
    http_response_code(500);
    echo json_encode(['error' => 'Database or Redis connection failed']);
    exit;
}

try {
    // Проверяем текущее состояние последовательности
    $stmt = $pdo->query("SELECT last_value, is_called FROM orders_id_seq");
    $seqInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $maxId = $pdo->query("SELECT MAX(id) FROM orders")->fetchColumn();
    
    $debug = [
        'sequence_last_value' => $seqInfo['last_value'],
        'sequence_is_called' => $seqInfo['is_called'],
        'max_order_id' => $maxId,
        'next_expected_id' => $maxId + 1
    ];
    
    // Пробуем вставить тестовый заказ
    $testStmt = $pdo->prepare("INSERT INTO orders (product_id, quantity, total_price, customer_email, purchase_time) VALUES (1, 1, 100.00, 'debug@example.com', NOW())");
    $testStmt->execute();
    $newId = $pdo->lastInsertId();
    
    $debug['new_order_id'] = $newId;
    
    // Удаляем тестовый заказ
    $pdo->exec("DELETE FROM orders WHERE id = $newId");
    
    echo json_encode([
        'status' => 'success',
        'debug_info' => $debug,
        'message' => 'Debug completed successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'debug_info' => $debug ?? null
    ]);
}
?>
