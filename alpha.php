<?php
require_once 'config.php';

header('Content-Type: application/json');

// Получаем соединения с БД и Redis
$pdo = getDbConnection();
$redis = getRedisConnection();

if (!$pdo || !$redis) {
    http_response_code(500);
    echo json_encode(['error' => 'Database or Redis connection failed']);
    exit;
}

// Ключ блокировки для защиты от повторного запуска
$lockKey = 'alpha_script_lock';
$lockValue = uniqid();

try {
    // Пауза для демонстрации эффекта
    sleep(1);
    
    // Попытка установить блокировку только для критической секции
    $lockAcquired = $redis->set($lockKey, $lockValue, ['NX', 'EX' => 2]);
    
    if (!$lockAcquired) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Script is already running',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    logMessage("Alpha script started - Lock acquired");
    
    // Получаем случайный продукт
    $stmt = $pdo->query("SELECT id, name, price FROM products ORDER BY RANDOM() LIMIT 1");
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception("No products found in database");
    }
    
    // Генерируем случайное количество товара
    $quantity = rand(1, 5);
    $totalPrice = $product['price'] * $quantity;
    
    // Создаем заказ
    $stmt = $pdo->prepare("
        INSERT INTO orders (product_id, quantity, total_price, customer_email, purchase_time) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    $customerEmail = 'customer' . rand(1, 1000) . '@example.com';
    $stmt->execute([$product['id'], $quantity, $totalPrice, $customerEmail]);
    
    $orderId = $pdo->lastInsertId();
    
    // Обновляем статистику
    $stmt = $pdo->prepare("
        INSERT INTO statistics (category_id, products_sold, total_revenue, last_updated)
        SELECT 
            p.category_id,
            COALESCE(s.products_sold, 0) + ?,
            COALESCE(s.total_revenue, 0) + ?,
            NOW()
        FROM products p
        LEFT JOIN statistics s ON s.category_id = p.category_id
        WHERE p.id = ?
        ON CONFLICT (category_id) 
        DO UPDATE SET 
            products_sold = statistics.products_sold + EXCLUDED.products_sold,
            total_revenue = statistics.total_revenue + EXCLUDED.total_revenue,
            last_updated = NOW()
    ");
    
    $stmt->execute([$quantity, $totalPrice, $product['id']]);
    
    $result = [
        'status' => 'success',
        'message' => 'Order created successfully',
        'order_id' => $orderId,
        'product' => $product['name'],
        'quantity' => $quantity,
        'total_price' => $totalPrice,
        'customer_email' => $customerEmail,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    logMessage("Alpha script completed - Order created: " . $orderId);
    
} catch (Exception $e) {
    $result = [
        'status' => 'error',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    logMessage("Alpha script error: " . $e->getMessage());
} finally {
    // Освобождаем блокировку только если она принадлежит нам
    if ($redis->get($lockKey) === $lockValue) {
        $redis->del($lockKey);
        logMessage("Alpha script - Lock released");
    }
}

echo json_encode($result);
?>
