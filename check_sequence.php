<?php
require_once 'config.php';

header('Content-Type: application/json');

$pdo = getDbConnection();

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

try {
    // Проверяем состояние ДО вставки
    $stmt = $pdo->query("SELECT last_value, is_called FROM orders_id_seq");
    $seqBefore = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $maxId = $pdo->query("SELECT MAX(id) FROM orders")->fetchColumn();
    
    $debug = [
        'before_insert' => [
            'sequence_last_value' => $seqBefore['last_value'],
            'sequence_is_called' => $seqBefore['is_called'],
            'max_order_id' => $maxId,
            'next_expected_id' => $maxId + 1
        ]
    ];
    
    // Пробуем получить следующее значение последовательности
    $stmt = $pdo->query("SELECT nextval('orders_id_seq')");
    $nextVal = $stmt->fetchColumn();
    
    $debug['nextval_result'] = $nextVal;
    
    // Проверяем состояние ПОСЛЕ получения nextval
    $stmt = $pdo->query("SELECT last_value, is_called FROM orders_id_seq");
    $seqAfter = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $debug['after_nextval'] = [
        'sequence_last_value' => $seqAfter['last_value'],
        'sequence_is_called' => $seqAfter['is_called']
    ];
    
    // Сбрасываем последовательность обратно
    $pdo->exec("SELECT setval('orders_id_seq', " . ($maxId + 1) . ", false)");
    
    echo json_encode([
        'status' => 'success',
        'debug_info' => $debug,
        'message' => 'Sequence check completed'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'debug_info' => $debug ?? null
    ]);
}
?>
