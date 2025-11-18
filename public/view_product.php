<?php
session_start();
require_once '../config/database.php';
require_once '../classes/ProductManager.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: products.php');
    exit;
}

$productManager = new ProductManager();
$product = $productManager->getProductById($_GET['id']);

if (!$product) {
    header('Location: products.php');
    exit;
}

// Calculate profit margin
$profit_margin = (($product['unit_price'] - $product['unit_cost']) / $product['unit_price']) * 100;

// Check for cart messages
$cart_message = '';
if (isset($_SESSION['cart_message'])) {
    $cart_message = $_SESSION['cart_message'];
    unset($_SESSION['cart_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - BlubellSeek</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .product-detail {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 20px;
        }
        .product-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        .info-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        .info-section h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .info-item {
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
        }
        .info-label {
            font-weight: bold;
            color: #34495e;
        }
        .stock-low {
            color: #e74c3c;
            font-weight: bold;
        }
        .stock-good {
            color: #27ae60;
            font-weight: bold;
        }
        .profit-positive {
            color: #27ae60;
            font-weight: bold;
        }
        .profit-negative {
            color: #e74c3c;
            font-weight: bold;
        }
        .action-section {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #27ae60;
        }
        .cart-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>BlubellSeek Inventory System</h1>
            <div class="nav-links">
                <span style="color: white;">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="dashboard.php" class="nav-btn">Dashboard</a>
                <a href="products.php" class="nav-btn">Products</a>
                <a href="cart.php" class="nav-btn">Cart (
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
                <a href="logout.php" class="nav-btn">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="product-header">
            <h2><?php echo htmlspecialchars($product['name']); ?></h2>
            <div>
                <a href="products.php" class="btn" style="background: #7f8c8d;">Back to Products</a>
            </div>
        </div>

        <?php if ($cart_message): ?>
            <div class="cart-message">
                <?php echo htmlspecialchars($cart_message); ?>
            </div>
        <?php endif; ?>

        <div class="product-detail">
            <div class="product-info">
                <div class="info-section">
                    <h3>Product Details</h3>
                    <div class="info-item">
                        <span class="info-label">Product ID:</span>
                        <span>#<?php echo htmlspecialchars($product['id']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Name:</span>
                        <span><?php echo htmlspecialchars($product['name']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Description:</span>
                        <span><?php echo htmlspecialchars($product['description'] ?: 'No description available'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Production Line:</span>
                        <span><?php echo htmlspecialchars($product['production_line']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Style:</span>
                        <span><?php echo htmlspecialchars($product['style']); ?></span>
                    </div>
                    <!-- Add this where you want the main product image to appear -->
<div style="text-align: center; margin: 20px 0;">
    <?php 
    $image_url = $product['image_url'] ?? '';
    $has_image = !empty($image_url);
    ?>
    
    <?php if ($has_image): ?>
        <img src="<?php echo htmlspecialchars($image_url); ?>" 
             alt="<?php echo htmlspecialchars($product['name']); ?>"
             class="product-detail-image"
             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
        <div class="image-fallback" style="display: none; width: 200px; height: 200px; margin: 0 auto;">
            Product Image<br>Not Available
        </div>
    <?php else: ?>
        <div class="image-fallback" style="width: 200px; height: 200px; margin: 0 auto;">
            No Product Image
        </div>
    <?php endif; ?>
</div>
                </div>

                <div class="info-section">
                    <h3>Specifications</h3>
                    <div class="info-item">
                        <span class="info-label">Lot/Color:</span>
                        <span><?php echo htmlspecialchars($product['lot']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Size:</span>
                        <span><?php echo htmlspecialchars($product['size']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Current Stock:</span>
                        <span class="<?php echo $product['on_hand_quantity'] < 10 ? 'stock-low' : 'stock-good'; ?>">
                            <?php echo htmlspecialchars($product['on_hand_quantity']); ?> units
                        </span>
                    </div>
                </div>

                <div class="info-section">
                    <h3>Pricing Information</h3>
                    <div class="info-item">
                        <span class="info-label">Unit Cost:</span>
                        <span>$<?php echo htmlspecialchars($product['unit_cost']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Unit Price:</span>
                        <span>$<?php echo htmlspecialchars($product['unit_price']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Profit Margin:</span>
                        <span class="<?php echo $profit_margin > 0 ? 'profit-positive' : 'profit-negative'; ?>">
                            <?php echo number_format($profit_margin, 2); ?>%
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Profit per Unit:</span>
                        <span class="<?php echo ($product['unit_price'] - $product['unit_cost']) > 0 ? 'profit-positive' : 'profit-negative'; ?>">
                            $<?php echo number_format($product['unit_price'] - $product['unit_cost'], 2); ?>
                        </span>
                    </div>
                </div>

                <div class="info-section">
                    <h3>Inventory Value</h3>
                    <div class="info-item">
                        <span class="info-label">Total Cost Value:</span>
                        <span>$<?php echo number_format($product['on_hand_quantity'] * $product['unit_cost'], 2); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Total Retail Value:</span>
                        <span>$<?php echo number_format($product['on_hand_quantity'] * $product['unit_price'], 2); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Potential Profit:</span>
                        <span class="<?php echo (($product['unit_price'] - $product['unit_cost']) * $product['on_hand_quantity']) > 0 ? 'profit-positive' : 'profit-negative'; ?>">
                            $<?php echo number_format(($product['unit_price'] - $product['unit_cost']) * $product['on_hand_quantity'], 2); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Add to Cart Section -->
            <div class="action-section">
                <h3>Purchase This Product</h3>
                <form method="POST" action="add_to_cart.php">
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <div>
                            <label for="quantity" style="font-weight: bold;">Quantity:</label>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['on_hand_quantity']; ?>" style="padding: 8px; width: 80px;">
                        </div>
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
                        <input type="hidden" name="price" value="<?php echo $product['unit_price']; ?>">
                        <button type="submit" class="btn" style="background: #27ae60;">Add to Cart</button>
                    </div>
                    <?php if ($product['on_hand_quantity'] < 10): ?>
                        <p style="color: #e74c3c; margin-top: 10px; font-style: italic;">
                            Low stock! Only <?php echo $product['on_hand_quantity']; ?> units available.
                        </p>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
