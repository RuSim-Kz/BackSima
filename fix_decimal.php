<?php
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔧 Исправление размера поля total_revenue</h1>\n";

try {
    $pdo = getDbConnection();
    if (!$pdo) {
        echo "❌ Не удалось подключиться к базе данных\n";
        exit;
    }
    
    echo "✅ Подключение к базе данных успешно\n";
    
    // Изменяем размер поля total_revenue
    echo "<h3>Изменение размера поля total_revenue...</h3>\n";
    $sql = "ALTER TABLE statistics ALTER COLUMN total_revenue TYPE DECIMAL(15,2)";
    $pdo->exec($sql);
    echo "✅ Размер поля total_revenue изменен на DECIMAL(15,2)\n";
    
    // Проверяем структуру
    echo "<h3>Проверка структуры таблицы...</h3>\n";
    $stmt = $pdo->query("SELECT column_name, data_type, numeric_precision, numeric_scale FROM information_schema.columns WHERE table_name = 'statistics' AND column_name = 'total_revenue'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>\n";
    echo "<tr><th>Колонка</th><th>Тип</th><th>Точность</th><th>Масштаб</th></tr>\n";
    echo "<tr><td>{$column['column_name']}</td><td>{$column['data_type']}</td><td>{$column['numeric_precision']}</td><td>{$column['numeric_scale']}</td></tr>\n";
    echo "</table>\n";
    
    echo "<h2>🎉 Размер поля исправлен!</h2>\n";
    echo "<p>Теперь поле total_revenue может хранить значения до 999,999,999,999,999.99</p>\n";
    echo "<p><a href='index.html'>Вернуться к системе</a></p>\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}
?>
