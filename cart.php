<?php
require_once 'config.php';
requireLogin();

if (hasRole('admin')) {
    header('Location: admin.php');
    exit();
}

$conn = getDBConnection();

// Handle cart updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        $food_id = intval($_POST['food_id']);
        $quantity = intval($_POST['quantity']);
        
        if ($quantity > 0) {
            $_SESSION['cart'][$food_id] = $quantity;
        } else {
            unset($_SESSION['cart'][$food_id]);
        }
    } elseif (isset($_POST['remove_item'])) {
        $food_id = intval($_POST['food_id']);
        unset($_SESSION['cart'][$food_id]);
    } elseif (isset($_POST['place_order'])) {
        if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
            $total = 0;
            $order_code = generateOrderCode($conn);
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Create order
                $stmt = $conn->prepare("INSERT INTO orders (user_id, order_code, total_amount) VALUES (?, ?, ?)");
                $stmt->bind_param("isd", $_SESSION['user_id'], $order_code, $total);
                $stmt->execute();
                $order_id = $conn->insert_id;
                
                // Add order items
                foreach ($_SESSION['cart'] as $food_id => $quantity) {
                    $result = $conn->query("SELECT price FROM food_items WHERE id = $food_id");
                    $food = $result->fetch_assoc();
                    $price = $food['price'];
                    $total += $price * $quantity;
                    
                    $stmt = $conn->prepare("INSERT INTO order_items (order_id, food_item_id, quantity, price) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiid", $order_id, $food_id, $quantity, $price);
                    $stmt->execute();
                }
                
                // Update total amount
                $conn->query("UPDATE orders SET total_amount = $total WHERE id = $order_id");
                
                $conn->commit();
                
                // Clear cart
                unset($_SESSION['cart']);
                
                header("Location: order_success.php?code=$order_code");
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Order failed. Please try again.";
            }
        }
    }
    
    header('Location: cart.php');
    exit();
}

// Get cart items
$cart_items = [];
$total = 0;

if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
    $ids = implode(',', array_keys($_SESSION['cart']));
    $result = $conn->query("SELECT * FROM food_items WHERE id IN ($ids)");
    
    while ($food = $result->fetch_assoc()) {
        $food['quantity'] = $_SESSION['cart'][$food['id']];
        $food['subtotal'] = $food['price'] * $food['quantity'];
        $total += $food['subtotal'];
        $cart_items[] = $food;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Canteen System</title>
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
        }
        
        .navbar a:hover {
            background: #5568d3;
        }
        
        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .cart-header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .cart-header h2 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .empty-cart {
            background: white;
            padding: 3rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .empty-cart-icon {
            font-size: 5rem;
            margin-bottom: 1rem;
        }
        
        .cart-item {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-name {
            font-size: 1.2rem;
            color: #333;
            margin-bottom: 0.3rem;
        }
        
        .item-price {
            color: #667eea;
            font-weight: bold;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .quantity-input {
            width: 60px;
            padding: 0.5rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-align: center;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .btn-update {
            background: #667eea;
            color: white;
        }
        
        .btn-remove {
            background: #ff4757;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        .cart-summary {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-top: 2rem;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .summary-total {
            border-top: 2px solid #e0e0e0;
            padding-top: 1rem;
            margin-top: 1rem;
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .btn-order {
            width: 100%;
            padding: 1rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1rem;
            transition: all 0.3s;
        }
        
        .btn-order:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>🛒 Shopping Cart</h1>
        <a href="index.php">← Continue Shopping</a>
    </nav>

    <div class="container">
        <div class="cart-header">
            <h2>Your Cart</h2>
            <p><?php echo count($cart_items); ?> item(s) in your cart</p>
        </div>

        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <div class="empty-cart-icon">🛒</div>
                <h2>Your cart is empty</h2>
                <p>Add some delicious items to get started!</p>
                <br>
                <a href="index.php" class="btn btn-update">Browse Menu</a>
            </div>
        <?php else: ?>
            <?php foreach ($cart_items as $item): ?>
                <div class="cart-item">
                    <div class="item-info">
                        <h3 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                        <p class="item-price">$<?php echo number_format($item['price'], 2); ?> each</p>
                        <p style="color: #666;">Subtotal: $<?php echo number_format($item['subtotal'], 2); ?></p>
                    </div>
                    <div class="quantity-control">
                        <form method="POST" style="display: flex; gap: 0.5rem; align-items: center;">
                            <input type="hidden" name="food_id" value="<?php echo $item['id']; ?>">
                            <input type="number" name="quantity" class="quantity-input" value="<?php echo $item['quantity']; ?>" min="1" max="99">
                            <button type="submit" name="update_cart" class="btn btn-update">Update</button>
                        </form>
                        <form method="POST">
                            <input type="hidden" name="food_id" value="<?php echo $item['id']; ?>">
                            <button type="submit" name="remove_item" class="btn btn-remove">Remove</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="cart-summary">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>$<?php echo number_format($total, 2); ?></span>
                </div>
                <div class="summary-row summary-total">
                    <span>Total:</span>
                    <span>$<?php echo number_format($total, 2); ?></span>
                </div>
                <form method="POST">
                    <button type="submit" name="place_order" class="btn-order">Place Order</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>