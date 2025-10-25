<?php
session_start();
require_once '../config/database.php';
require_once '../classes/ProductManager.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$productManager = new ProductManager();
$products = $productManager->getAllProducts();

// Check for cart messages
$cart_message = '';
$cart_error = '';
if (isset($_SESSION['cart_message'])) {
    $cart_message = $_SESSION['cart_message'];
    unset($_SESSION['cart_message']);
}
if (isset($_SESSION['cart_error'])) {
    $cart_error = $_SESSION['cart_error'];
    unset($_SESSION['cart_error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - BlubellSeek</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .products-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .products-table th,
        .products-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        .products-table th {
            background: #34495e;
            color: white;
            font-weight: bold;
        }
        .products-table tr:hover {
            background: #f8f9fa;
        }
        .stock-low {
            color: #e74c3c;
            font-weight: bold;
        }
        .stock-good {
            color: #27ae60;
        }
        .action-btns {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn-small {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
            text-align: center;
        }
        .btn-view {
            background: #3498db;
            color: white;
        }
        .btn-edit {
            background: #f39c12;
            color: white;
        }
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        .btn-cart {
            background: #27ae60;
            color: white;
        }
        .cart-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        .cart-error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        .action-btns form {
            display: inline;
            margin: 0;
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
        <div class="products-header">
            <h2>Product Catalog</h2>
            <div>
                <a href="dashboard.php" class="btn" style="background: #7f8c8d;">Back to Dashboard</a>
                <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager'): ?>
                    <a href="add_product.php" class="btn" style="background: #27ae60;">Add New Product</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($cart_message): ?>
            <div class="cart-message">
                <?php echo htmlspecialchars($cart_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($cart_error): ?>
            <div class="cart-error">
                <?php echo htmlspecialchars($cart_error); ?>
            </div>
        <?php endif; ?>

        <div class="product-list">
            <table class="products-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product Name</th>
                        <th>Production Line</th>
                        <th>Style</th>
                        <th>Lot</th>
                        <th>Size</th>
                        <th>Unit Cost</th>
                        <th>Unit Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['id']); ?></td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars($product['production_line']); ?></td>
                        <td><?php echo htmlspecialchars($product['style']); ?></td>
                        <td><?php echo htmlspecialchars($product['lot']); ?></td>
                        <td><?php echo htmlspecialchars($product['size']); ?></td>
                        <td>$<?php echo htmlspecialchars($product['unit_cost']); ?></td>
                        <td>$<?php echo htmlspecialchars($product['unit_price']); ?></td>
                        <td class="<?php echo $product['on_hand_quantity'] < 10 ? 'stock-low' : 'stock-good'; ?>">
                            <?php echo htmlspecialchars($product['on_hand_quantity']); ?>
                        </td>
                        <td class="action-btns">
                            <a href="view_product.php?id=<?php echo $product['id']; ?>" class="btn-small btn-view">View</a>
                            
                            <!-- Add to Cart Button -->
                            <form method="POST" action="add_to_cart.php">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
                                <input type="hidden" name="price" value="<?php echo $product['unit_price']; ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="btn-small btn-cart">Add to Cart</button>
                            </form>
                            
                            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager'): ?>
                                <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn-small btn-edit">Edit</a>
                                <a href="delete_product.php?id=<?php echo $product['id']; ?>" class="btn-small btn-delete" onclick="return confirm('Are you sure?')">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (empty($products)): ?>
                <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                    <h3>No products found</h3>
                    <p>Add some products to get started!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>