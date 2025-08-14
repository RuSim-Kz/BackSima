<?php
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔧 Исправление последовательности orders_id_seq</h1>\n";

try {
    $pdo = getDbConnection();
    
    // Получаем максимальный ID
    $maxId = $pdo->query("SELECT MAX(id) FROM orders")->fetchColumn();
    $nextId = $maxId + 1;
    
    echo "📊 Максимальный ID в таблице: $maxId\n";
    echo "📊 Следующий ожидаемый ID: $nextId\n";
    
    // Устанавливаем последовательность на правильное значение
    $pdo->exec("SELECT setval('orders_id_seq', $nextId, false)");
    
    // Проверяем результат
    $stmt = $pdo->query("SELECT last_value, is_called FROM orders_id_seq");
    $seqInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "✅ Последовательность установлена на: " . $seqInfo['last_value'] . "\n";
    
    // Тестируем вставку
    echo "<h3>Тестирование вставки...</h3>\n";
    $testStmt = $pdo->prepare("INSERT INTO orders (product_id, quantity, total_price, customer_email, purchase_time) VALUES (1, 1, 100.00, 'test@example.com', NOW())");
    $testStmt->execute();
    $newId = $pdo->lastInsertId();
    echo "✅ Тестовая вставка успешна, новый ID: $newId\n";
    
    // Удаляем тестовый заказ
    $pdo->exec("DELETE FROM orders WHERE id = $newId");
    echo "✅ Тестовый заказ удален\n";
    
    echo "<h2>🎉 Последовательность исправлена!</h2>\n";
    echo "<p><a href='index.html'>Вернуться к системе</a></p>\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}
?>
