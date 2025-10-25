<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Cart.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$cart = new Cart();
$cart_items = $cart->getItems();
$cart_total = $cart->getTotal();
$item_count = $cart->getItemCount();

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_quantity']) && isset($_POST['quantities'])) {
        // Update quantities
        foreach ($_POST['quantities'] as $product_id => $quantity) {
            $cart->updateQuantity($product_id, intval($quantity));
        }
        $_SESSION['cart_message'] = "Cart updated successfully!";
    } elseif (isset($_POST['remove_item']) && isset($_POST['product_id'])) {
        // Remove single item
        $product_id = $_POST['product_id'];
        $cart->removeItem($product_id);
        $_SESSION['cart_message'] = "Item removed from cart!";
    } elseif (isset($_POST['clear_cart'])) {
        // Clear entire cart
        $cart->clear();
        $_SESSION['cart_message'] = "Cart cleared successfully!";
    }
    
    // Refresh cart data
    header('Location: cart.php');
    exit;
}

// Check for messages
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
    <title>Shopping Cart - BlubellSeek</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .cart-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 20px;
        }
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .cart-table th,
        .cart-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        .cart-table th {
            background: #34495e;
            color: white;
        }
        .quantity-input {
            width: 70px;
            padding: 8px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .cart-summary {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        .cart-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .btn-update {
            background: #3498db;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-remove {
            background: #e74c3c;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-clear {
            background: #95a5a6;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-checkout {
            background: #27ae60;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
        }
        .btn-continue {
            background: #3498db;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
        }
        .empty-cart {
            text-align: center;
            padding: 60px 40px;
            color: #7f8c8d;
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
        .total-amount {
            font-size: 1.4em;
            color: #27ae60;
            font-weight: bold;
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
                <a href="cart.php" class="nav-btn">Cart (<?php echo $item_count; ?>)</a>
                <a href="logout.php" class="nav-btn">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="cart-header">
            <h2>Shopping Cart</h2>
            <div style="color: #2c3e50; font-size: 1.1em;">
                <strong>Items in cart: <?php echo $item_count; ?></strong>
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

        <div class="cart-container">
            <?php if ($cart->isEmpty()): ?>
                <div class="empty-cart">
                    <h3>Your cart is empty</h3>
                    <p>Browse our products and add some items to your cart!</p>
                    <a href="products.php" class="btn-continue">Continue Shopping</a>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Unit Price</th>
                                <th>Quantity</th>
                                <th>Total Price</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $product_id => $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <input type="number" 
                                           name="quantities[<?php echo $product_id; ?>]" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="1" 
                                           max="99" 
                                           class="quantity-input">
                                </td>
                                <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                <td>
                                    <button type="submit" name="remove_item" value="1" class="btn-remove"
                                            onclick="document.getElementById('remove_product_id').value='<?php echo $product_id; ?>'">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <input type="hidden" name="product_id" id="remove_product_id" value="">
                    
                    <div class="cart-actions">
                        <button type="submit" name="update_quantity" value="1" class="btn-update">Update Quantities</button>
                        <button type="submit" name="clear_cart" value="1" class="btn-clear" 
                                onclick="return confirm('Are you sure you want to clear your entire cart?')">
                            
                            Clear Entire Cart
                        </button>
                    </div>
                </form>

                <div class="cart-summary">
                    <h3>Order Summary</h3>
                    <div style="display: flex; justify-content: space-between; margin-top: 15px; padding: 10px 0;">
                        <strong>Total Items:</strong>
                        <span><?php echo $item_count; ?> items</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 10px; padding: 15px 0; border-top: 1px solid #ddd;">
                        <strong>Total Amount:</strong>
                        <span class="total-amount">$<?php echo number_format($cart_total, 2); ?></span>
                    </div>
                    
                    <div style="margin-top: 25px; text-align: center;">
                        <a href="checkout.php" class="btn-checkout">Proceed to Checkout</a>
                        <a href="products.php" class="btn-continue" style="margin-left: 15px;">Continue Shopping</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
