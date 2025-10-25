<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Cart.php';
require_once '../classes/Order.php';
require_once '../classes/OrderManager.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$cart = new Cart();
$cart_items = $cart->getItems();
$cart_total = $cart->getTotal();
$item_count = $cart->getItemCount();

// Redirect if cart is empty
if ($cart->isEmpty()) {
    header('Location: cart.php');
    exit;
}

$error = '';
$success = '';

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = trim($_POST['shipping_address']);
    
    if (empty($shipping_address)) {
        $error = 'Shipping address is required!';
    } else {
        try {
            // Create order
            $order = new Order($_SESSION['user_id'], $cart_total, 'pending', $shipping_address);
            $orderManager = new OrderManager();
            $order_id = $orderManager->createOrder($order, $cart_items);
            
            // Clear cart and show success
            $cart->clear();
            $success = "Order #$order_id placed successfully! Thank you for your purchase.";
            
        } catch (Exception $e) {
            $error = "Order failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - BlubellSeek</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .checkout-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }
        .checkout-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .checkout-section h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #2c3e50;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        .order-summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 10px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        .order-total {
            display: flex;
            justify-content: space-between;
            font-size: 1.2em;
            font-weight: bold;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #34495e;
        }
        .btn-place-order {
            background: #27ae60;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            width: 100%;
            margin-top: 20px;
        }
        .btn-place-order:hover {
            background: #219652;
        }
        .error {
            color: #e74c3c;
            background: #ffe6e6;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .success {
            color: #27ae60;
            background: #e6ffe6;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
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
        <h2>Checkout</h2>
        
        <?php if ($success): ?>
            <div class="success">
                <h3><?php echo htmlspecialchars($success); ?></h3>
                <p>Your order has been processed successfully.</p>
                <div style="margin-top: 20px;">
                    <a href="orders.php" class="btn" style="background: #3498db;">View Your Orders</a>
                    <a href="products.php" class="btn" style="background: #27ae60;">Continue Shopping</a>
                </div>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="checkout-container">
                <!-- Shipping Information -->
                <div class="checkout-section">
                    <h3>Shipping Information</h3>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="shipping_address">Shipping Address:</label>
                            <textarea id="shipping_address" name="shipping_address" placeholder="Enter your complete shipping address..." required><?php echo isset($_POST['shipping_address']) ? htmlspecialchars($_POST['shipping_address']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="customer_name">Customer Name:</label>
                            <input type="text" id="customer_name" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" disabled>
                        </div>

                        <button type="submit" class="btn-place-order">Place Order</button>
                    </form>
                </div>

                <!-- Order Summary -->
                <div class="checkout-section">
                    <h3>Order Summary</h3>
                    <div>
                        <?php foreach ($cart_items as $product_id => $item): ?>
                        <div class="order-summary-item">
                            <div>
                                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                <div style="color: #7f8c8d; font-size: 0.9em;">
                                    Qty: <?php echo $item['quantity']; ?> × $<?php echo number_format($item['price'], 2); ?>
                                </div>
                            </div>
                            <div>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="order-total">
                            <span>Total Amount:</span>
                            <span style="color: #27ae60;">$<?php echo number_format($cart_total, 2); ?></span>
                        </div>
                    </div>
                    
                    <div style="margin-top: 20px; text-align: center;">
                        <a href="cart.php" class="btn" style="background: #7f8c8d;">← Back to Cart</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>