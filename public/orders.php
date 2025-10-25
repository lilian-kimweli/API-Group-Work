<?php
session_start();
require_once '../config/database.php';
require_once '../classes/OrderManager.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$orderManager = new OrderManager();
$orders = $orderManager->getOrdersByCustomer($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - BlubellSeek</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .orders-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .orders-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 20px;
        }
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        .orders-table th,
        .orders-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        .orders-table th {
            background: #34495e;
            color: white;
        }
        .orders-table tr:hover {
            background: #f8f9fa;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: bold;
        }
        .status-completed {
            background: #d1edff;
            color: #0c5460;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: bold;
        }
        .status-shipped {
            background: #d4edda;
            color: #155724;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: bold;
        }
        .btn-view {
            background: #3498db;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
        }
        .empty-orders {
            text-align: center;
            padding: 60px 40px;
            color: #7f8c8d;
        }
        .order-count {
            color: #2c3e50;
            font-size: 1.1em;
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
        <div class="orders-header">
            <h2>My Orders</h2>
            <div class="order-count">
                <strong>Total Orders: <?php echo count($orders); ?></strong>
            </div>
        </div>

        <div class="orders-container">
            <?php if (empty($orders)): ?>
                <div class="empty-orders">
                    <h3>No orders found</h3>
                    <p>You haven't placed any orders yet.</p>
                    <a href="products.php" class="btn" style="display: inline-block; width: auto; margin-top: 20px;">Start Shopping</a>
                </div>
            <?php else: ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Order Date</th>
                            <th>Items</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></td>
                            <td><?php echo htmlspecialchars($order['item_count']); ?> items</td>
                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="status-<?php echo htmlspecialchars($order['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn-view">View Details</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>