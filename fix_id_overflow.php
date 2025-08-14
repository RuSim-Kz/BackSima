<?php
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔧 Исправление переполнения ID в таблице orders</h1>\n";

try {
    $pdo = getDbConnection();
    if (!$pdo) {
        echo "❌ Не удалось подключиться к базе данных\n";
        exit;
    }
    
    echo "✅ Подключение к базе данных успешно\n";
    
    // Проверяем текущий максимальный ID
    echo "<h3>Проверка текущего состояния...</h3>\n";
    $stmt = $pdo->query("SELECT MAX(id) as max_id, COUNT(*) as total_orders FROM orders");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "📊 Максимальный ID: " . $result['max_id'] . "\n";
    echo "📊 Всего заказов: " . $result['total_orders'] . "\n";
    
    // Проверяем текущий тип поля
    $stmt = $pdo->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'orders' AND column_name = 'id'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "📊 Текущий тип поля id: " . $column['data_type'] . "\n";
    
    if ($column['data_type'] === 'bigint') {
        echo "✅ Поле id уже имеет тип BIGINT\n";
    } else {
        // Изменяем тип поля id на BIGINT
        echo "<h3>Изменение типа поля id на BIGINT...</h3>\n";
        $sql = "ALTER TABLE orders ALTER COLUMN id TYPE BIGINT";
        $pdo->exec($sql);
        echo "✅ Тип поля id изменен на BIGINT\n";
    }
    
    // Сначала отвязываем последовательность от поля
    echo "<h3>Отвязка старой последовательности...</h3>\n";
    $pdo->exec("ALTER TABLE orders ALTER COLUMN id DROP DEFAULT");
    echo "✅ Последовательность отвязана от поля id\n";
    
    // Удаляем старую последовательность если она существует
    echo "<h3>Удаление старой последовательности...</h3>\n";
    $pdo->exec("DROP SEQUENCE IF EXISTS orders_id_seq");
    echo "✅ Старая последовательность удалена\n";
    
    // Создаем новую последовательность
    echo "<h3>Создание новой последовательности...</h3>\n";
    $nextId = $result['max_id'] + 1;
    $sql = "CREATE SEQUENCE orders_id_seq START WITH $nextId";
    $pdo->exec($sql);
    echo "✅ Новая последовательность создана с начальным значением: $nextId\n";
    
    // Привязываем новую последовательность к полю
    $sql = "ALTER TABLE orders ALTER COLUMN id SET DEFAULT nextval('orders_id_seq')";
    $pdo->exec($sql);
    echo "✅ Последовательность привязана к полю id\n";
    
    // Устанавливаем текущее значение последовательности
    $sql = "SELECT setval('orders_id_seq', $nextId, false)";
    $pdo->exec($sql);
    echo "✅ Текущее значение последовательности установлено: $nextId\n";
    
    // Проверяем результат
    echo "<h3>Проверка результата...</h3>\n";
    $stmt = $pdo->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'orders' AND column_name = 'id'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>\n";
    echo "<tr><th>Колонка</th><th>Тип</th></tr>\n";
    echo "<tr><td>{$column['column_name']}</td><td>{$column['data_type']}</td></tr>\n";
    echo "</table>\n";
    
    // Тестируем вставку нового заказа
    echo "<h3>Тестирование вставки...</h3>\n";
    try {
        $testStmt = $pdo->prepare("INSERT INTO orders (product_id, quantity, total_price, customer_email, purchase_time) VALUES (1, 1, 100.00, 'test@example.com', NOW())");
        $testStmt->execute();
        $testId = $pdo->lastInsertId();
        echo "✅ Тестовая вставка успешна, новый ID: $testId\n";
        
        // Удаляем тестовый заказ
        $pdo->exec("DELETE FROM orders WHERE id = $testId");
        echo "✅ Тестовый заказ удален\n";
    } catch (Exception $e) {
        echo "❌ Ошибка тестовой вставки: " . $e->getMessage() . "\n";
    }
    
    echo "<h2>🎉 Переполнение ID исправлено!</h2>\n";
    echo "<p>Теперь поле id может хранить значения до 9,223,372,036,854,775,807</p>\n";
    echo "<p><a href='index.html'>Вернуться к системе</a></p>\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}
?>
