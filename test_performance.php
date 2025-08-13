<?php
/**
 * Скрипт тестирования производительности системы
 */

require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🧪 Тестирование производительности системы</h1>\n";

// Тест производительности Alpha скрипта
echo "<h2>Тест Alpha скрипта (10 итераций)</h2>\n";
$alphaTimes = [];
$alphaSuccess = 0;

for ($i = 0; $i < 10; $i++) {
    $start = microtime(true);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/alpha.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $end = microtime(true);
    $time = round(($end - $start) * 1000, 2);
    $alphaTimes[] = $time;
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if ($data && $data['status'] === 'success') {
            $alphaSuccess++;
        }
    }
    
    echo "Итерация " . ($i + 1) . ": {$time}ms\n";
}

$avgAlphaTime = array_sum($alphaTimes) / count($alphaTimes);
echo "Среднее время Alpha: " . round($avgAlphaTime, 2) . "ms\n";
echo "Успешных выполнений: $alphaSuccess/10\n";

// Тест производительности Gamma скрипта
echo "<h2>Тест Gamma скрипта (5 итераций)</h2>\n";
$gammaTimes = [];

for ($i = 0; $i < 5; $i++) {
    $start = microtime(true);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/gamma.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $end = microtime(true);
    $time = round(($end - $start) * 1000, 2);
    $gammaTimes[] = $time;
    
    echo "Итерация " . ($i + 1) . ": {$time}ms\n";
}

$avgGammaTime = array_sum($gammaTimes) / count($gammaTimes);
echo "Среднее время Gamma: " . round($avgGammaTime, 2) . "ms\n";

// Тест Beta скрипта с небольшим количеством итераций
echo "<h2>Тест Beta скрипта (50 итераций)</h2>\n";
$start = microtime(true);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/beta.php?n=50');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$end = microtime(true);
$betaTime = round($end - $start, 2);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data && $data['status'] === 'completed') {
        echo "Время выполнения Beta (50 итераций): {$betaTime}с\n";
        echo "Успешных запросов: " . $data['success_count'] . "\n";
        echo "Ошибок: " . $data['error_count'] . "\n";
        echo "Запросов в секунду: " . $data['requests_per_second'] . "\n";
    }
}

// Тест подключения к базе данных
echo "<h2>Тест подключения к базе данных</h2>\n";
$pdo = getDbConnection();
if ($pdo) {
    $start = microtime(true);
    
    // Простой запрос
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
    $count = $stmt->fetchColumn();
    
    $end = microtime(true);
    $dbTime = round(($end - $start) * 1000, 2);
    
    echo "Время выполнения запроса: {$dbTime}ms\n";
    echo "Количество заказов в БД: $count\n";
}

// Тест Redis
echo "<h2>Тест Redis</h2>\n";
$redis = getRedisConnection();
if ($redis) {
    $start = microtime(true);
    
    // Тест записи
    $redis->set('test_key', 'test_value');
    $value = $redis->get('test_key');
    $redis->del('test_key');
    
    $end = microtime(true);
    $redisTime = round(($end - $start) * 1000, 2);
    
    echo "Время операции Redis: {$redisTime}ms\n";
    echo "Redis работает: " . ($value === 'test_value' ? 'Да' : 'Нет') . "\n";
}

echo "<h2>📊 Итоговая статистика</h2>\n";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
echo "<tr><th>Компонент</th><th>Среднее время</th><th>Статус</th></tr>\n";
echo "<tr><td>Alpha скрипт</td><td>" . round($avgAlphaTime, 2) . "ms</td><td>" . ($alphaSuccess >= 8 ? '✅ Хорошо' : '⚠️ Требует внимания') . "</td></tr>\n";
echo "<tr><td>Gamma скрипт</td><td>" . round($avgGammaTime, 2) . "ms</td><td>" . ($avgGammaTime < 100 ? '✅ Отлично' : '⚠️ Медленно') . "</td></tr>\n";
echo "<tr><td>Beta скрипт (50 итераций)</td><td>{$betaTime}с</td><td>" . ($betaTime < 10 ? '✅ Быстро' : '⚠️ Медленно') . "</td></tr>\n";
echo "<tr><td>База данных</td><td>{$dbTime}ms</td><td>" . ($dbTime < 50 ? '✅ Быстро' : '⚠️ Медленно') . "</td></tr>\n";
echo "<tr><td>Redis</td><td>{$redisTime}ms</td><td>" . ($redisTime < 10 ? '✅ Быстро' : '⚠️ Медленно') . "</td></tr>\n";
echo "</table>\n";

echo "<h2>💡 Рекомендации по оптимизации</h2>\n";
if ($avgAlphaTime > 1000) {
    echo "<p>⚠️ Alpha скрипт работает медленно. Рекомендуется:</p>\n";
    echo "<ul><li>Оптимизировать запросы к базе данных</li><li>Увеличить лимиты памяти PHP</li></ul>\n";
}

if ($avgGammaTime > 100) {
    echo "<p>⚠️ Gamma скрипт работает медленно. Рекомендуется:</p>\n";
    echo "<ul><li>Добавить индексы в базу данных</li><li>Оптимизировать SQL запросы</li></ul>\n";
}

if ($dbTime > 50) {
    echo "<p>⚠️ База данных работает медленно. Рекомендуется:</p>\n";
    echo "<ul><li>Проверить индексы</li><li>Оптимизировать настройки PostgreSQL</li></ul>\n";
}

echo "<p><a href='index.html'>← Вернуться к главной странице</a></p>\n";
?>
