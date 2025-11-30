<?php
require_once 'config.php';

$conn = getDBConnection();
$result = $conn->query("SELECT * FROM food_items WHERE available = TRUE ORDER BY category, name");

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
    
    $food_id = intval($_POST['food_id']);
    $quantity = intval($_POST['quantity']);
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$food_id])) {
        $_SESSION['cart'][$food_id] += $quantity;
    } else {
        $_SESSION['cart'][$food_id] = $quantity;
    }
    
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canteen Ordering System</title>
    <style>
        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #a8e063 0%, #56ab2f 100%); /* Green eco gradient */
    min-height: 100vh;
}

/* NAVBAR */
.navbar {
    background: rgba(255, 255, 255, 0.95);
    padding: 1rem 2rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.navbar h1 {
    color: #3c8d40; /* Eco green bold */
    font-size: 1.8rem;
}

.navbar-links {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.navbar-links a, .btn {
    text-decoration: none;
    color: #fff;
    background: #56ab2f; /* Eco button */
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
    font-size: 0.95rem;
}

.navbar-links a:hover, .btn:hover {
    background: #3c8d40; /* darker eco green */
    transform: translateY(-2px);
}

.cart-badge {
    background: #2ecc71; /* fresh green */
    color: white;
    border-radius: 50%;
    padding: 2px 8px;
    font-size: 0.8rem;
    margin-left: 5px;
}

/* CONTAINER */
.container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 2rem;
}

/* WELCOME */
.welcome-banner {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.welcome-banner h2 {
    color: #2f5233;
}

.welcome-banner p {
    color: #5e846b;
}

/* FOOD GRID */
.food-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

/* FOOD CARD */
.food-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s;
}

.food-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

/* FOOD IMAGE COLOR */
.food-image {
    width: 100%;
    height: 180px;
    background: linear-gradient(135deg, #7bc043 0%, #2eb82e 100%); /* soft green gradient */
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 3rem;
}

/* CONTENT */
.food-content {
    padding: 1.5rem;
}

.food-category {
    background: #e5f7ea;
    color: #2e8b57; /* green */
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    display: inline-block;
    margin-bottom: 0.5rem;
}

.food-name {
    font-size: 1.3rem;
    color: #2f5233;
    margin: 0.5rem 0;
}

.food-description {
    color: #6b8f71;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

.food-price {
    font-size: 1.5rem;
    color: #3c8d40;
    font-weight: bold;
    margin-bottom: 1rem;
}

/* ORDER FORM */
.order-form {
    display: flex;
    gap: 0.5rem;
}

.quantity-input {
    width: 60px;
    padding: 0.5rem;
    border: 2px solid #d8e8d2;
    border-radius: 8px;
    font-size: 1rem;
}

.add-to-cart-btn {
    flex: 1;
    background: #56ab2f;
    color: white;
    border: none;
    padding: 0.6rem;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.95rem;
    transition: all 0.3s;
}

.add-to-cart-btn:hover {
    background: #3c8d40;
}

.user-info {
    color: white;
    margin-right: 1rem;
}

    </style>
</head>
<body>
    <nav class="navbar">
        <h1>🍽️ Canteen Ordering System</h1>
        <div class="navbar-links">
            <?php if (isLoggedIn()): ?>
                <span class="user-info">Hello, <?php echo htmlspecialchars($_SESSION['full_name']); ?> (<?php echo ucfirst($_SESSION['role']); ?>)</span>
                <?php if (hasRole('admin')): ?>
                    <a href="admin.php">Admin Panel</a>
                <?php else: ?>
                    <a href="cart.php">
                        Cart 
                        <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                            <span class="cart-badge"><?php echo array_sum($_SESSION['cart']); ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="my_orders.php">My Orders</a>
                <?php endif; ?>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-banner">
            <h2>Welcome to Our Canteen! 🎉</h2>
            <p>Browse our delicious menu items. <?php echo !isLoggedIn() ? 'Please login to place an order.' : 'Add items to your cart and place your order!'; ?></p>
        </div>

        <div class="food-grid">
            <?php while ($food = $result->fetch_assoc()): ?>
                <div class="food-card">
                    <div class="food-image">🍔</div>
                    <div class="food-content">
                        <span class="food-category"><?php echo htmlspecialchars($food['category']); ?></span>
                        <h3 class="food-name"><?php echo htmlspecialchars($food['name']); ?></h3>
                        <p class="food-description"><?php echo htmlspecialchars($food['description']); ?></p>
                        <div class="food-price">$<?php echo number_format($food['price'], 2); ?></div>
                        
                        <?php if (isLoggedIn() && !hasRole('admin')): ?>
                            <form method="POST" class="order-form">
                                <input type="hidden" name="food_id" value="<?php echo $food['id']; ?>">
                                <input type="number" name="quantity" class="quantity-input" value="1" min="1" max="99">
                                <button type="submit" name="add_to_cart" class="add-to-cart-btn">Add to Cart</button>
                            </form>
                        <?php elseif (!isLoggedIn()): ?>
                            <button class="add-to-cart-btn" onclick="location.href='login.php'">Login to Order</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>