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
    
    // Изменяем тип поля id на BIGSERIAL
    echo "<h3>Изменение типа поля id на BIGSERIAL...</h3>\n";
    $sql = "ALTER TABLE orders ALTER COLUMN id TYPE BIGINT";
    $pdo->exec($sql);
    echo "✅ Тип поля id изменен на BIGINT\n";
    
    // Пересоздаем последовательность
    echo "<h3>Пересоздание последовательности...</h3>\n";
    $sql = "CREATE SEQUENCE IF NOT EXISTS orders_id_seq_new START WITH " . ($result['max_id'] + 1);
    $pdo->exec($sql);
    echo "✅ Новая последовательность создана\n";
    
    // Привязываем новую последовательность к полю
    $sql = "ALTER TABLE orders ALTER COLUMN id SET DEFAULT nextval('orders_id_seq_new')";
    $pdo->exec($sql);
    echo "✅ Последовательность привязана к полю id\n";
    
    // Проверяем результат
    echo "<h3>Проверка результата...</h3>\n";
    $stmt = $pdo->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'orders' AND column_name = 'id'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>\n";
    echo "<tr><th>Колонка</th><th>Тип</th></tr>\n";
    echo "<tr><td>{$column['column_name']}</td><td>{$column['data_type']}</td></tr>\n";
    echo "</table>\n";
    
    echo "<h2>🎉 Переполнение ID исправлено!</h2>\n";
    echo "<p>Теперь поле id может хранить значения до 9,223,372,036,854,775,807</p>\n";
    echo "<p><a href='index.html'>Вернуться к системе</a></p>\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}
?>
