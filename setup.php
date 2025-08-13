<?php
/**
 * Скрипт автоматической настройки системы
 * Проверяет все зависимости и создает необходимые таблицы
 */

echo "<h1>🔧 Настройка системы управления заказами</h1>\n";

// Проверка PHP версии
echo "<h2>Проверка PHP</h2>\n";
echo "Версия PHP: " . PHP_VERSION . "\n";
if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    echo "✅ PHP версия подходит\n";
} else {
    echo "❌ Требуется PHP 7.4 или выше\n";
    exit(1);
}

// Проверка расширений PHP
echo "<h2>Проверка расширений PHP</h2>\n";
$required_extensions = ['pdo', 'pdo_pgsql', 'redis', 'curl', 'json'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ Расширение $ext установлено\n";
    } else {
        echo "❌ Расширение $ext не установлено\n";
    }
}

// Проверка подключения к PostgreSQL
echo "<h2>Проверка подключения к PostgreSQL</h2>\n";
require_once 'config.php';

try {
    $pdo = getDbConnection();
    if ($pdo) {
        echo "✅ Подключение к PostgreSQL успешно\n";
        
        // Проверяем существование таблиц
        $tables = ['categories', 'products', 'orders', 'statistics'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = '$table')");
            $exists = $stmt->fetchColumn();
            if ($exists) {
                echo "✅ Таблица $table существует\n";
            } else {
                echo "❌ Таблица $table не найдена\n";
            }
        }
        
        // Создаем таблицы если их нет
        echo "<h3>Создание таблиц</h3>\n";
        $sql = file_get_contents('database.sql');
        $pdo->exec($sql);
        echo "✅ Таблицы созданы/обновлены\n";
        
    } else {
        echo "❌ Не удалось подключиться к PostgreSQL\n";
        echo "Проверьте настройки в config.php\n";
    }
} catch (Exception $e) {
    echo "❌ Ошибка подключения к PostgreSQL: " . $e->getMessage() . "\n";
}

// Проверка подключения к Redis
echo "<h2>Проверка подключения к Redis</h2>\n";
try {
    $redis = getRedisConnection();
    if ($redis) {
        echo "✅ Подключение к Redis успешно\n";
        
        // Тестируем запись и чтение
        $testKey = 'setup_test_' . time();
        $testValue = 'test_value';
        $redis->set($testKey, $testValue);
        $readValue = $redis->get($testKey);
        $redis->del($testKey);
        
        if ($readValue === $testValue) {
            echo "✅ Redis работает корректно\n";
        } else {
            echo "❌ Проблема с чтением/записью в Redis\n";
        }
    } else {
        echo "❌ Не удалось подключиться к Redis\n";
        echo "Убедитесь, что Redis запущен на localhost:6379\n";
    }
} catch (Exception $e) {
    echo "❌ Ошибка подключения к Redis: " . $e->getMessage() . "\n";
}

// Тестирование скриптов
echo "<h2>Тестирование скриптов</h2>\n";

// Тест Alpha скрипта
echo "<h3>Тест Alpha скрипта</h3>\n";
try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/alpha.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['status'])) {
            echo "✅ Alpha скрипт работает: " . $data['status'] . "\n";
        } else {
            echo "❌ Alpha скрипт вернул неверный формат данных\n";
        }
    } else {
        echo "❌ Alpha скрипт вернул HTTP код: $httpCode\n";
    }
} catch (Exception $e) {
    echo "❌ Ошибка тестирования Alpha скрипта: " . $e->getMessage() . "\n";
}

// Тест Gamma скрипта
echo "<h3>Тест Gamma скрипта</h3>\n";
try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/gamma.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['status'])) {
            echo "✅ Gamma скрипт работает: " . $data['status'] . "\n";
            if (isset($data['data']['total_orders_analyzed'])) {
                echo "📊 Проанализировано заказов: " . $data['data']['total_orders_analyzed'] . "\n";
            }
        } else {
            echo "❌ Gamma скрипт вернул неверный формат данных\n";
        }
    } else {
        echo "❌ Gamma скрипт вернул HTTP код: $httpCode\n";
    }
} catch (Exception $e) {
    echo "❌ Ошибка тестирования Gamma скрипта: " . $e->getMessage() . "\n";
}

echo "<h2>🎉 Настройка завершена!</h2>\n";
echo "<p>Теперь вы можете открыть <a href='index.html'>index.html</a> для использования системы.</p>\n";

// Рекомендации
echo "<h2>📋 Рекомендации</h2>\n";
echo "<ul>\n";
echo "<li>Убедитесь, что все файлы имеют правильные права доступа</li>\n";
echo "<li>Проверьте настройки безопасности веб-сервера</li>\n";
echo "<li>Настройте регулярное резервное копирование базы данных</li>\n";
echo "<li>Мониторьте логи для выявления проблем</li>\n";
echo "</ul>\n";
?>
