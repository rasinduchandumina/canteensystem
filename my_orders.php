<?php
require_once 'config.php';
requireLogin();

if (hasRole('admin')) {
    header('Location: admin.php');
    exit();
}

$conn = getDBConnection();

// Get user's orders
$orders = $conn->query("
    SELECT * FROM orders 
    WHERE user_id = {$_SESSION['user_id']} 
    ORDER BY created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Canteen System</title>
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
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar h1 {
            color: #667eea;
            font-size: 1.8rem;
        }
        
        .navbar a {
            text-decoration: none;
            color: #fff;
            background: #667eea;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            transition: all 0.3s;
            margin-left: 0.5rem;
        }
        
        .navbar a:hover {
            background: #5568d3;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .page-header h2 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .empty-state {
            background: white;
            padding: 3rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .empty-state-icon {
            font-size: 5rem;
            margin-bottom: 1rem;
        }
        
        .orders-list {
            display: grid;
            gap: 1.5rem;
        }
        
        .order-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .order-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-code {
            font-size: 1.8rem;
            font-weight: bold;
            font-family: 'Courier New', monospace;
            letter-spacing: 0.2rem;
        }
        
        .order-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .order-body {
            padding: 1.5rem;
        }
        
        .order-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .meta-item {
            color: #666;
        }
        
        .meta-item strong {
            color: #333;
        }
        
        .order-items {
            background: #f9f9f9;
            padding: 1rem;
            border-radius: 8px;
        }
        
        .order-items h4 {
            color: #667eea;
            margin-bottom: 0.8rem;
        }
        
        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .item-row:last-child {
            border-bottom: none;
        }
        
        .order-total {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #667eea;
            display: flex;
            justify-content: space-between;
            font-size: 1.3rem;
            font-weight: bold;
            color: #667eea;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📦 My Orders</h1>
        <div>
            <a href="index.php">← Back to Menu</a>
            <a href="cart.php">Cart</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h2>Order History</h2>
            <p>View all your past and current orders</p>
        </div>

        <?php if ($orders->num_rows === 0): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📦</div>
                <h2>No orders yet</h2>
                <p>Start ordering delicious food from our canteen!</p>
                <br>
                <a href="index.php" style="display: inline-block; padding: 0.8rem 2rem; background: #667eea; color: white; text-decoration: none; border-radius: 8px;">Browse Menu</a>
            </div>
        <?php else: ?>
            <div class="orders-list">
                <?php while ($order = $orders->fetch_assoc()): ?>
                    <?php
                    // Get order items
                    $order_items = $conn->query("
                        SELECT oi.*, f.name 
                        FROM order_items oi 
                        JOIN food_items f ON oi.food_item_id = f.id 
                        WHERE oi.order_id = {$order['id']}
                    ");
                    ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-code">#<?php echo $order['order_code']; ?></div>
                            <div class="order-status status-<?php echo $order['status']; ?>">
                                <?php 
                                    if ($order['status'] === 'pending') {
                                        echo '⏳ Pending';
                                    } elseif ($order['status'] === 'completed') {
                                        echo '✅ Completed';
                                    } else {
                                        echo '❌ Cancelled';
                                    }
                                ?>
                            </div>
                        </div>
                        
                        <div class="order-body">
                            <div class="order-meta">
                                <div class="meta-item">
                                    <strong>Order Date:</strong><br>
                                    <?php echo date('F d, Y', strtotime($order['created_at'])); ?>
                                </div>
                                <div class="meta-item">
                                    <strong>Order Time:</strong><br>
                                    <?php echo date('h:i A', strtotime($order['created_at'])); ?>
                                </div>
                                <div class="meta-item">
                                    <strong>Total Amount:</strong><br>
                                    $<?php echo number_format($order['total_amount'], 2); ?>
                                </div>
                            </div>
                            
                            <div class="order-items">
                                <h4>Order Items</h4>
                                <?php while ($item = $order_items->fetch_assoc()): ?>
                                    <div class="item-row">
                                        <span><?php echo $item['quantity']; ?>x <?php echo htmlspecialchars($item['name']); ?></span>
                                        <span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                    </div>
                                <?php endwhile; ?>
                                
                                <div class="order-total">
                                    <span>Total:</span>
                                    <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                                </div>
                            </div>
                            
                            <?php if ($order['status'] === 'pending'): ?>
                                <div style="margin-top: 1rem; padding: 1rem; background: #fff3cd; border-radius: 8px; color: #856404;">
                                    <strong>💡 Tip:</strong> Show order code <strong><?php echo $order['order_code']; ?></strong> at the canteen counter to collect your order.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>