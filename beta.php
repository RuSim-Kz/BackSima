<?php
require_once 'config.php';

header('Content-Type: application/json');

// Получаем количество итераций из параметра
$iterations = isset($_GET['n']) ? (int)$_GET['n'] : DEFAULT_ITERATIONS;

// Ограничиваем количество итераций для безопасности
if ($iterations > 10000) {
    $iterations = 10000;
}

if ($iterations < 1) {
    $iterations = 1;
}

$startTime = microtime(true);
$results = [];
$successCount = 0;
$errorCount = 0;

// Функция для выполнения HTTP запроса
function makeRequest($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'response' => $response,
        'http_code' => $httpCode,
        'error' => $error
    ];
}

// Получаем базовый URL для alpha.php
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$baseUrl = $protocol . '://' . $host . dirname($_SERVER['REQUEST_URI']);
$alphaUrl = $baseUrl . '/alpha.php';

logMessage("Beta script started - Running $iterations iterations");

// Запускаем запросы одновременно
$multiHandle = curl_multi_init();
$handles = [];

// Создаем множественные cURL запросы
for ($i = 0; $i < $iterations; $i++) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $alphaUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    curl_multi_add_handle($multiHandle, $ch);
    $handles[] = $ch;
}

// Выполняем все запросы
$running = null;
do {
    curl_multi_exec($multiHandle, $running);
    curl_multi_select($multiHandle);
} while ($running > 0);

// Собираем результаты
foreach ($handles as $i => $ch) {
    $response = curl_multi_getcontent($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    if ($httpCode === 200 && !$error) {
        $data = json_decode($response, true);
        if ($data && isset($data['status']) && $data['status'] === 'success') {
            $successCount++;
            $results[] = [
                'iteration' => $i + 1,
                'status' => 'success',
                'order_id' => $data['order_id'] ?? null,
                'message' => $data['message'] ?? 'Success'
            ];
        } else {
            $errorCount++;
            $results[] = [
                'iteration' => $i + 1,
                'status' => 'error',
                'message' => $data['message'] ?? 'Unknown error'
            ];
        }
    } else {
        $errorCount++;
        $results[] = [
            'iteration' => $i + 1,
            'status' => 'error',
            'message' => $error ?: "HTTP Error: $httpCode"
        ];
    }
    
    curl_multi_remove_handle($multiHandle, $ch);
    curl_close($ch);
}

curl_multi_close($multiHandle);

$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);

$result = [
    'status' => 'completed',
    'iterations_requested' => $iterations,
    'iterations_completed' => count($results),
    'success_count' => $successCount,
    'error_count' => $errorCount,
    'execution_time_seconds' => $executionTime,
    'requests_per_second' => round(count($results) / $executionTime, 2),
    'timestamp' => date('Y-m-d H:i:s'),
    'results' => array_slice($results, 0, 10) // Показываем только первые 10 результатов
];

logMessage("Beta script completed - Success: $successCount, Errors: $errorCount, Time: {$executionTime}s");

echo json_encode($result, JSON_PRETTY_PRINT);
?>
