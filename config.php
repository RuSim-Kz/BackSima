<?php
// Конфигурация базы данных PostgreSQL
define('DB_HOST', 'dpg-d2939549c44c73d31nag-a.oregon-postgres.render.com');
define('DB_NAME', 'test_system_nk7f');
define('DB_USER', 'postgres21');
define('DB_PASS', 'ExbWfL41NPH23KALJ0c5HZpfSafXDt5D');
define('DB_PORT', '5432');

// Конфигурация Redis
define('REDIS_HOST', 'red-d2ehnguuk2gs73bemil0');
define('REDIS_PORT', 6379);
define('REDIS_PASSWORD', null);

// Настройки приложения
define('LOCK_TIMEOUT', 5); // Время блокировки в секундах
define('DEFAULT_ITERATIONS', 1000); // Количество итераций по умолчанию

// Функция для подключения к PostgreSQL
function getDbConnection() {
    try {
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return false;
    }
}

// Функция для подключения к Redis
function getRedisConnection() {
    try {
        $redis = new Redis();
        $redis->connect(REDIS_HOST, REDIS_PORT);
        if (REDIS_PASSWORD) {
            $redis->auth(REDIS_PASSWORD);
        }
        return $redis;
    } catch (Exception $e) {
        error_log("Redis connection failed: " . $e->getMessage());
        return false;
    }
}

// Функция для логирования
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] $message");
}
?>
