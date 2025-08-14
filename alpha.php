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
    // Попытка установить блокировку для защиты от повторного запуска
    $lockAcquired = $redis->set($lockKey, $lockValue, ['NX', 'EX' => 3]);
    
    if (!$lockAcquired) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Script is already running',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    logMessage("Alpha script started - Lock acquired");
    
    // Пауза для демонстрации эффекта
    sleep(1);
    
    // Получаем случайный продукт
    $stmt = $pdo->query("SELECT id, name, price FROM products ORDER BY RANDOM() LIMIT 1");
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception("No products found in database");
    }
    
    // Генерируем случайное количество товара
    $quantity = rand(1, 5);
    $totalPrice = $product['price'] * $quantity;
    
    // Создаем заказ с обработкой переполнения ID
    try {
        // Логируем состояние перед вставкой
        $maxId = $pdo->query("SELECT MAX(id) FROM orders")->fetchColumn();
        $stmt = $pdo->query("SELECT last_value, is_called FROM orders_id_seq");
        $seqInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("ALPHA DEBUG: Max ID = $maxId, Sequence last_value = " . $seqInfo['last_value'] . ", is_called = " . $seqInfo['is_called']);
        
        $stmt = $pdo->prepare("
            INSERT INTO orders (product_id, quantity, total_price, customer_email, purchase_time) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $customerEmail = 'customer' . rand(1, 1000) . '@example.com';
        $stmt->execute([$product['id'], $quantity, $totalPrice, $customerEmail]);
        
        $orderId = $pdo->lastInsertId();
        error_log("ALPHA DEBUG: Order created successfully with ID = $orderId");
    } catch (Exception $e) {
        error_log("ALPHA DEBUG: Exception caught: " . $e->getMessage());
        // Если произошло переполнение ID, автоматически исправляем
        if (strpos($e->getMessage(), 'integer out of range') !== false) {
            error_log("ALPHA DEBUG: Integer overflow detected, starting auto-fix");
            logMessage("Auto-fixing ID overflow in orders table");
            
            // Изменяем тип поля id на BIGINT
            $pdo->exec("ALTER TABLE orders ALTER COLUMN id TYPE BIGINT");
            
            // Сначала отвязываем последовательность от поля
            $pdo->exec("ALTER TABLE orders ALTER COLUMN id DROP DEFAULT");
            
            // Удаляем старую последовательность
            $pdo->exec("DROP SEQUENCE IF EXISTS orders_id_seq");
            
            // Создаем новую последовательность
            $maxId = $pdo->query("SELECT MAX(id) FROM orders")->fetchColumn();
            $nextId = $maxId + 1;
            $pdo->exec("CREATE SEQUENCE orders_id_seq START WITH $nextId");
            $pdo->exec("ALTER TABLE orders ALTER COLUMN id SET DEFAULT nextval('orders_id_seq')");
            $pdo->exec("SELECT setval('orders_id_seq', $nextId, false)");
            
            // Повторяем вставку
            $stmt->execute([$product['id'], $quantity, $totalPrice, $customerEmail]);
            $orderId = $pdo->lastInsertId();
            
            logMessage("ID overflow fixed automatically, new order created: " . $orderId);
        } else {
            throw $e; // Если другая ошибка, пробрасываем дальше
        }
    }
    
    // Обновляем статистику с обработкой переполнения
    try {
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
    } catch (Exception $e) {
        // Если произошло переполнение total_revenue, автоматически исправляем
        if (strpos($e->getMessage(), 'numeric field overflow') !== false) {
            logMessage("Auto-fixing total_revenue overflow in statistics table");
            
            // Изменяем размер поля total_revenue
            $pdo->exec("ALTER TABLE statistics ALTER COLUMN total_revenue TYPE DECIMAL(20,2)");
            
            // Повторяем обновление статистики
            $stmt->execute([$quantity, $totalPrice, $product['id']]);
            
            logMessage("Total_revenue overflow fixed automatically");
        } else {
            throw $e; // Если другая ошибка, пробрасываем дальше
        }
    }
    
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
