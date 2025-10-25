<?php
session_start();
require_once '../config/database.php';
require_once '../classes/OrderManager.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$orderManager = new OrderManager();
$order = $orderManager->getOrderDetails($_GET['id']);

// Check if order exists and belongs to current user
if (!$order || $order['customer_id'] != $_SESSION['user_id']) {
    header('Location: orders.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $order['id']; ?> - BlubellSeek</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .order-detail {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 20px;
        }
        .order-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .info-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
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
        .order-items {
            margin-top: 30px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .items-table th,
        .items-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        .items-table th {
            background: #34495e;
            color: white;
        }
        .order-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border-left: 4px solid #3498db;
        }
        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9em;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        .status-shipped {
            background: #d1edff;
            color: #0c5460;
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
                <a href="orders.php" class="nav-btn">My Orders</a>
                <a href="logout.php" class="nav-btn">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="order-header">
            <h2>Order #<?php echo $order['id']; ?></h2>
            <div>
                <a href="orders.php" class="btn" style="background: #7f8c8d;">← Back to Orders</a>
            </div>
        </div>

        <div class="order-detail">
            <div class="order-info">
                <div class="info-section">
                    <h3>Order Information</h3>
                    <div class="info-item">
                        <span class="info-label">Order Date:</span>
                        <span><?php echo date('F j, Y g:i A', strtotime($order['order_date'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Order Status:</span>
                        <span class="status-badge status-<?php echo $order['status']; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Customer:</span>
                        <span><?php echo htmlspecialchars($order['customer_name']); ?></span>
                    </div>
                </div>

                <div class="info-section">
                    <h3>Shipping Information</h3>
                    <div class="info-item">
                        <span class="info-label">Shipping Address:</span>
                        <span><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></span>
                    </div>
                </div>
            </div>

            <div class="order-items">
                <h3>Order Items</h3>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order['items'] as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td>$<?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="order-summary">
                    <div style="display: flex; justify-content: space-between; font-size: 1.2em; font-weight: bold;">
                        <span>Total Amount:</span>
                        <span style="color: #27ae60;">$<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>