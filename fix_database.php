<?php
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔧 Исправление структуры базы данных</h1>\n";

try {
    $pdo = getDbConnection();
    if (!$pdo) {
        echo "❌ Не удалось подключиться к базе данных\n";
        exit;
    }
    
    echo "✅ Подключение к базе данных успешно\n";
    
    // Удаляем старую таблицу statistics
    echo "<h3>Удаление старой таблицы statistics...</h3>\n";
    $pdo->exec("DROP TABLE IF EXISTS statistics CASCADE");
    echo "✅ Старая таблица statistics удалена\n";
    
    // Создаем новую таблицу statistics
    echo "<h3>Создание новой таблицы statistics...</h3>\n";
    $sql = "
    CREATE TABLE statistics (
        id SERIAL PRIMARY KEY,
        category_id INTEGER REFERENCES categories(id),
        products_sold INTEGER DEFAULT 0,
        total_revenue DECIMAL(10,2) DEFAULT 0.00,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(category_id)
    );
    ";
    $pdo->exec($sql);
    echo "✅ Новая таблица statistics создана\n";
    
    // Проверяем структуру
    echo "<h3>Проверка структуры таблицы...</h3>\n";
    $stmt = $pdo->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'statistics' ORDER BY ordinal_position");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>\n";
    echo "<tr><th>Колонка</th><th>Тип</th></tr>\n";
    foreach ($columns as $column) {
        echo "<tr><td>{$column['column_name']}</td><td>{$column['data_type']}</td></tr>\n";
    }
    echo "</table>\n";
    
    echo "<h2>🎉 Структура базы данных исправлена!</h2>\n";
    echo "<p><a href='index.html'>Вернуться к системе</a></p>\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}
?>
