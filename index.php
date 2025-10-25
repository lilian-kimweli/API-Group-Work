<?php
require_once 'config/database.php';
require_once 'classes/Database.php';

// Test database connection and get products
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get products inside the try block where $conn is available
    $stmt = $conn->query("SELECT name, unit_price, on_hand_quantity FROM products LIMIT 3");
    $products = $stmt->fetchAll();
    
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BlubellSeek - Inventory Management</title>
    <link rel="stylesheet" href="public/style.css">
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>BlubellSeek Inventory System</h1>
           <div class="header">
    <div class="container">
        <h1>BlubellSeek Inventory System</h1>
        <div class="nav-links">
            <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                <span style="color: white;">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="public/dashboard.php" class="nav-btn">Dashboard</a>
                <a href="public/products.php" class="nav-btn">Products</a>
                <a href="public/cart.php" class="nav-btn">Cart (
                    <?php 
                    if (isset($_SESSION['cart'])) {
                        $item_count = 0;
                        foreach ($_SESSION['cart'] as $item) {
                            $item_count += $item['quantity'];
                        }
                        echo $item_count;
                    } else {
                        echo '0';
                    }
                    ?>
                )</a>
                <a href="public/logout.php" class="nav-btn">Logout</a>
            <?php else: ?>
                <a href="public/login.php" class="nav-btn">Login</a>
                <a href="public/register.php" class="nav-btn">Register</a>
            <?php endif; ?>
        </div>
    </div>
</div>
        </div>
    </div>
    
    <div class="container">
        <div class='product-list'>
            <h2>Welcome to BlubellSeek Inventory</h2>
            <p>Manage your apparel inventory efficiently</p>
            
            <div class="quick-stats">
                <h3>Featured Products</h3>
                <?php
                // Display products - now $products is available
                echo "<ul>";
                foreach ($products as $product) {
                    echo "<li><strong>{$product['name']}</strong> - \${$product['unit_price']} (In stock: {$product['on_hand_quantity']})</li>";
                }
                echo "</ul>";
                ?>
            </div>
        </div>
    </div>

    <script src="public/script.js"></script>
</body>
</html>