<?php
require_once 'config.php';
requireAdmin();

$conn = getDBConnection();

// Handle order completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_order'])) {
    $order_code = $_POST['order_code'];
    $stmt = $conn->prepare("UPDATE orders SET status = 'completed' WHERE order_code = ?");
    $stmt->bind_param("s", $order_code);
    $stmt->execute();
    $stmt->close();
    header('Location: admin.php?tab=orders');
    exit();
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = intval($_POST['user_id']);
    $conn->query("DELETE FROM users WHERE id = $user_id");
    header('Location: admin.php?tab=users');
    exit();
}

// Handle food item add/edit/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_food'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = floatval($_POST['price']);
    $category = $_POST['category'];
    
    $stmt = $conn->prepare("INSERT INTO food_items (name, description, price, category) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssds", $name, $description, $price, $category);
    $stmt->execute();
    $stmt->close();
    header('Location: admin.php?tab=food');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_food'])) {
    $food_id = intval($_POST['food_id']);
    $conn->query("DELETE FROM food_items WHERE id = $food_id");
    header('Location: admin.php?tab=food');
    exit();
}

$active_tab = $_GET['tab'] ?? 'orders';

// Get pending orders
$pending_orders = $conn->query("
    SELECT o.*, u.full_name, u.role 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.status = 'pending' 
    ORDER BY o.created_at DESC
");

// Get all users
$users = $conn->query("SELECT * FROM users ORDER BY role, full_name");

// Get all food items
$food_items = $conn->query("SELECT * FROM food_items ORDER BY category, name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Canteen System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #163a05ff;
        }
        
        .navbar {
            background: #288d00ff;
            padding: 1rem 2rem;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar h1 {
            font-size: 1.8rem;
        }
        
        .navbar a {
            text-decoration: none;
            color: #667eea;
            background: white;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .navbar a:hover {
            background: #f0f0f0ff;
        }
        
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .tabs {
            background: white;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .tab {
            padding: 0.8rem 1.5rem;
            border: none;
            background: #1a550cff;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .tab.active {
            background: #48ff00ff;
            color: white;
        }
        
        .tab:hover {
            background: #48ff00ff;
            color: white;
        }
        
        .content-section {
            display: none;
        }
        
        .content-section.active {
            display: block;
        }
        
        .card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .card h2 {
            color: #333;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #48ff00ff;
        }
        
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .order-card {
            background: #f9f9f9ff;
            padding: 1.5rem;
            border-radius: 10px;
            border-left: 4px solid #48ff00ff;
        }
        
        .order-code {
            font-size: 2rem;
            font-weight: bold;
            color: #6648ff00ff7eea;
            margin-bottom: 0.5rem;
        }
        
        .order-details {
            margin: 1rem 0;
            color: #666;
        }
        
        .order-details p {
            margin: 0.3rem 0;
        }
        
        .btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .btn-complete {
            background: #2ecc71;
            color: white;
            width: 100%;
            margin-top: 1rem;
        }
        
        .btn-complete:hover {
            background: #27ae60;
        }
        
        .btn-delete {
            background: #ff4757;
            color: white;
        }
        
        .btn-delete:hover {
            background: #e84343;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        th {
            background: #f5f5f5;
            color: #48ff00ff;
            font-weight: 600;
        }
        
        tr:hover {
            background: #f9f9f9;
        }
        
        .badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .badge-admin {
            background: #ff4757;
            color: white;
        }
        
        .badge-teacher {
            background: #48ff00ff;
            color: white;
        }
        
        .badge-student {
            background: #2ecc71;
            color: white;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #059624ff;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn-primary {
            background: #48ff00ff;
            color: white;
            padding: 0.8rem 2rem;
        }
        
        .btn-primary:hover {
            background: #48ff00ff;
        }
        
        .search-box {
            margin-bottom: 1.5rem;
        }
        
        .search-box input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #b90505ff;
            border-radius: 8px;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>⚙️ Admin Panel</h1>
        <div>
            <span style="margin-right: 1rem;">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            <a href="index.php">← Back to Menu</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="tabs">
            <button class="tab <?php echo $active_tab === 'orders' ? 'active' : ''; ?>" onclick="location.href='?tab=orders'">📋 Orders</button>
            <button class="tab <?php echo $active_tab === 'users' ? 'active' : ''; ?>" onclick="location.href='?tab=users'">👥 Users</button>
            <button class="tab <?php echo $active_tab === 'food' ? 'active' : ''; ?>" onclick="location.href='?tab=food'">🍔 Food Items</button>
        </div>

        <!-- Orders Tab -->
        <div class="content-section <?php echo $active_tab === 'orders' ? 'active' : ''; ?>">
            <div class="card">
                <h2>Pending Orders</h2>
                <div class="search-box">
                    <input type="text" id="orderSearch" placeholder="Search by order code..." onkeyup="searchOrders()">
                </div>
                
                <?php if ($pending_orders->num_rows === 0): ?>
                    <p style="text-align: center; color: #666; padding: 2rem;">No pending orders</p>
                <?php else: ?>
                    <div class="orders-grid">
                        <?php while ($order = $pending_orders->fetch_assoc()): ?>
                            <?php
                            // Get order items
                            $order_items = $conn->query("
                                SELECT oi.*, f.name 
                                FROM order_items oi 
                                JOIN food_items f ON oi.food_item_id = f.id 
                                WHERE oi.order_id = {$order['id']}
                            ");
                            ?>
                            <div class="order-card" data-code="<?php echo $order['order_code']; ?>">
                                <div class="order-code">#<?php echo $order['order_code']; ?></div>
                                <div class="order-details">
                                    <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['full_name']); ?></p>
                                    <p><strong>Role:</strong> <span class="badge badge-<?php echo $order['role']; ?>"><?php echo ucfirst($order['role']); ?></span></p>
                                    <p><strong>Time:</strong> <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
                                    <p><strong>Total:</strong> $<?php echo number_format($order['total_amount'], 2); ?></p>
                                    
                                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #ddd;">
                                        <strong>Items:</strong>
                                        <ul style="margin-top: 0.5rem; padding-left: 1.5rem;">
                                            <?php while ($item = $order_items->fetch_assoc()): ?>
                                                <li><?php echo $item['quantity']; ?>x <?php echo htmlspecialchars($item['name']); ?></li>
                                            <?php endwhile; ?>
                                        </ul>
                                    </div>
                                </div>
                                
                                <form method="POST">
                                    <input type="hidden" name="order_code" value="<?php echo $order['order_code']; ?>">
                                    <button type="submit" name="complete_order" class="btn btn-complete">✓ Mark as Complete</button>
                                </form>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Users Tab -->
        <div class="content-section <?php echo $active_tab === 'users' ? 'active' : ''; ?>">
            <div class="card">
                <h2>All Users</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><span class="badge badge-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="delete_user" class="btn btn-delete" onclick="return confirm('Delete this user?')">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Food Items Tab -->
        <div class="content-section <?php echo $active_tab === 'food' ? 'active' : ''; ?>">
            <div class="card">
                <h2>Add New Food Item</h2>
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" required>
                        </div>
                        <div class="form-group">
                            <label>Price</label>
                            <input type="number" step="0.01" name="price" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" required>
                            <option value="Main Course">Main Course</option>
                            <option value="Sides">Sides</option>
                            <option value="Salad">Salad</option>
                            <option value="Dessert">Dessert</option>
                            <option value="Beverages">Beverages</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" required></textarea>
                    </div>
                    <button type="submit" name="add_food" class="btn btn-primary">Add Food Item</button>
                </form>
            </div>

            <div class="card">
                <h2>All Food Items</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($food = $food_items->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $food['id']; ?></td>
                                <td><?php echo htmlspecialchars($food['name']); ?></td>
                                <td><?php echo htmlspecialchars($food['category']); ?></td>
                                <td>$<?php echo number_format($food['price'], 2); ?></td>
                                <td><?php echo $food['available'] ? '✓ Available' : '✗ Unavailable'; ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="food_id" value="<?php echo $food['id']; ?>">
                                        <button type="submit" name="delete_food" class="btn btn-delete" onclick="return confirm('Delete this item?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function searchOrders() {
            const input = document.getElementById('orderSearch');
            const filter = input.value.toUpperCase();
            const cards = document.getElementsByClassName('order-card');
            
            for (let i = 0; i < cards.length; i++) {
                const code = cards[i].getAttribute('data-code');
                if (code.toUpperCase().indexOf(filter) > -1) {
                    cards[i].style.display = "";
                } else {
                    cards[i].style.display = "none";
                }
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>