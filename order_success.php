<?php
require_once 'config.php';
requireLogin();

$order_code = $_GET['code'] ?? '';

if (empty($order_code)) {
    header('Location: index.php');
    exit();
}

$conn = getDBConnection();
$stmt = $conn->prepare("SELECT o.*, u.full_name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.order_code = ? AND o.user_id = ?");
$stmt->bind_param("si", $order_code, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$order = $result->fetch_assoc();

// Get order items
$order_items = $conn->query("
    SELECT oi.*, f.name 
    FROM order_items oi 
    JOIN food_items f ON oi.food_item_id = f.id 
    WHERE oi.order_id = {$order['id']}
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - Canteen System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }
        
        .success-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 600px;
            padding: 3rem;
            text-align: center;
        }
        
        .success-icon {
            font-size: 5rem;
            margin-bottom: 1rem;
            animation: bounce 1s ease;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        h1 {
            color: #2ecc71;
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .order-code-display {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin: 2rem 0;
        }
        
        .order-code-display h2 {
            font-size: 1rem;
            margin-bottom: 0.5rem;
            opacity: 0.9;
        }
        
        .order-code-display .code {
            font-size: 3rem;
            font-weight: bold;
            letter-spacing: 0.5rem;
            font-family: 'Courier New', monospace;
        }
        
        .order-details {
            text-align: left;
            margin: 2rem 0;
            padding: 1.5rem;
            background: #f9f9f9;
            border-radius: 10px;
        }
        
        .order-details h3 {
            color: #667eea;
            margin-bottom: 1rem;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .total {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #667eea;
            font-size: 1.3rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .instructions {
            background: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 8px;
            margin: 1.5rem 0;
            text-align: left;
        }
        
        .instructions h4 {
            margin-bottom: 0.5rem;
        }
        
        .btn {
            display: inline-block;
            padding: 1rem 2rem;
            margin: 0.5rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">✅</div>
        <h1>Order Placed Successfully!</h1>
        <p>Thank you for your order, <?php echo htmlspecialchars($order['full_name']); ?>!</p>
        
        <div class="order-code-display">
            <h2>Your Order Code</h2>
            <div class="code"><?php echo $order_code; ?></div>
        </div>
        
        <div class="instructions">
            <h4>📝 Important Instructions:</h4>
            <ul style="margin-left: 1.5rem;">
                <li>Please save your order code: <strong><?php echo $order_code; ?></strong></li>
                <li>Show this code at the canteen counter when collecting your order</li>
                <li>You can check your order status in "My Orders" section</li>
            </ul>
        </div>
        
        <div class="order-details">
            <h3>Order Summary</h3>
            <?php while ($item = $order_items->fetch_assoc()): ?>
                <div class="order-item">
                    <span><?php echo $item['quantity']; ?>x <?php echo htmlspecialchars($item['name']); ?></span>
                    <span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                </div>
            <?php endwhile; ?>
            
            <div class="order-item total">
                <span>Total Amount:</span>
                <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
        </div>
        
        <div>
            <a href="my_orders.php" class="btn">View My Orders</a>
            <a href="index.php" class="btn btn-secondary">Back to Menu</a>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>