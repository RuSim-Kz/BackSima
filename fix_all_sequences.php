<?php
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔧 Исправление всех последовательностей orders</h1>\n";

try {
    $pdo = getDbConnection();
    
    // Находим все последовательности
    $stmt = $pdo->query("SELECT sequence_name FROM information_schema.sequences WHERE sequence_name LIKE '%orders%'");
    $sequences = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Найденные последовательности:</h3>\n";
    foreach ($sequences as $seq) {
        echo "📋 $seq\n";
    }
    
    // Получаем максимальный ID
    $maxId = $pdo->query("SELECT MAX(id) FROM orders")->fetchColumn();
    $nextId = $maxId + 1;
    
    echo "<h3>Исправление последовательностей:</h3>\n";
    echo "📊 Максимальный ID: $maxId\n";
    echo "📊 Следующий ID: $nextId\n";
    
    // Исправляем каждую последовательность
    foreach ($sequences as $seq) {
        echo "🔧 Исправляем $seq...\n";
        $pdo->exec("SELECT setval('$seq', $nextId, false)");
        echo "✅ $seq исправлена\n";
    }
    
    // Проверяем текущую последовательность по умолчанию
    $stmt = $pdo->query("SELECT column_default FROM information_schema.columns WHERE table_name = 'orders' AND column_name = 'id'");
    $default = $stmt->fetchColumn();
    echo "📋 Текущий DEFAULT для id: $default\n";
    
    // Тестируем вставку
    echo "<h3>Тестирование вставки...</h3>\n";
    $testStmt = $pdo->prepare("INSERT INTO orders (product_id, quantity, total_price, customer_email, purchase_time) VALUES (1, 1, 100.00, 'test@example.com', NOW())");
    $testStmt->execute();
    $newId = $pdo->lastInsertId();
    echo "✅ Тестовая вставка успешна, новый ID: $newId\n";
    
    // Удаляем тестовый заказ
    $pdo->exec("DELETE FROM orders WHERE id = $newId");
    echo "✅ Тестовый заказ удален\n";
    
    echo "<h2>🎉 Все последовательности исправлены!</h2>\n";
    echo "<p><a href='index.html'>Вернуться к системе</a></p>\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}
?>
