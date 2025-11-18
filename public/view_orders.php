<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';
require_once '../classes/OrderManager.php';
require_once '../classes/UserManager.php';

// Check if user is logged in and has manager/admin role
Auth::requireAnyRole(['manager', 'admin']);

if (!isset($_GET['customer_id'])) {
    header('Location: customers.php');
    exit;
}

$customer_id = intval($_GET['customer_id']);
$orderManager = new OrderManager();
$userManager = new UserManager();

// Get customer info
$customer = $userManager->getUserById($customer_id);
if (!$customer || $customer['role'] != 'customer') {
    header('Location: customers.php');
    exit;
}

// Get customer orders
$orders = $orderManager->getOrdersByCustomer($customer_id);

// Helper function to safely display data
function safeDisplay($data) {
    if ($data === null || $data === '') {
        return 'N/A';
    }
    return htmlspecialchars($data);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Orders - BlubellSeek</title>
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
        .customer-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #3498db;
        }
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
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
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #3498db;
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #2c3e50;
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
        .status-pending {
            background: #fff3cd;
            color: #856404;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: bold;
        }
        .status-completed {
            background: #d4edda;
            color: #155724;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: bold;
        }
        .status-shipped {
            background: #d1edff;
            color: #0c5460;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: bold;
        }
        .empty-orders {
            text-align: center;
            padding: 60px 40px;
            color: #7f8c8d;
        }
        .customer-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 15px;
        }
        .detail-item {
            margin-bottom: 10px;
        }
        .detail-label {
            font-weight: bold;
            color: #34495e;
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
                <a href="customers.php" class="nav-btn">Customers</a>
                <a href="orders.php" class="nav-btn">All Orders</a>
                <a href="logout.php" class="nav-btn">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="orders-header">
            <h2>Customer Orders</h2>
            <div>
                <a href="customers.php" class="btn" style="background: #7f8c8d;">← Back to Customers</a>
            </div>
        </div>

        <!-- Customer Information -->
        <div class="customer-info">
            <h3>Customer Information</h3>
            <div class="customer-details">
                <div>
                    <div class="detail-item">
                        <span class="detail-label">Customer ID:</span>
                        <span>#<?php echo $customer['id']; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Username:</span>
                        <span><?php echo safeDisplay($customer['username']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Email:</span>
                        <span><?php echo safeDisplay($customer['email']); ?></span>
                    </div>
                </div>
                <div>
                    <div class="detail-item">
                        <span class="detail-label">Member Since:</span>
                        <span><?php echo date('F j, Y', strtotime($customer['created_at'])); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">2FA Enabled:</span>
                        <span><?php echo $customer['two_factor_enabled'] ? 'Yes' : 'No'; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Total Orders:</span>
                        <span><?php echo count($orders); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Statistics -->
        <div class="stats-cards">
            <?php
            $total_orders = count($orders);
            $total_revenue = 0;
            $pending_orders = 0;
            $completed_orders = 0;
            
            foreach ($orders as $order) {
                $total_revenue += $order['total_amount'];
                if ($order['status'] === 'pending') {
                    $pending_orders++;
                } elseif ($order['status'] === 'completed') {
                    $completed_orders++;
                }
            }
            
            $avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;
            ?>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_orders; ?></div>
                <div>Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">$<?php echo number_format($total_revenue, 2); ?></div>
                <div>Total Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">$<?php echo number_format($avg_order_value, 2); ?></div>
                <div>Average Order Value</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $completed_orders; ?></div>
                <div>Completed Orders</div>
            </div>
        </div>

        <div class="orders-container">
            <h3>Order History</h3>

            <?php if (empty($orders)): ?>
                <div class="empty-orders">
                    <h3>No orders found</h3>
                    <p>This customer hasn't placed any orders yet.</p>
                    <a href="customers.php" class="btn" style="display: inline-block; width: auto; margin-top: 20px;">Back to Customers</a>
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
                            <th>Shipping Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></td>
                            <td><?php echo $order['item_count']; ?> items</td>
                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                $address = $order['shipping_address'];
                                if (strlen($address) > 50) {
                                    echo safeDisplay(substr($address, 0, 50)) . '...';
                                } else {
                                    echo safeDisplay($address);
                                }
                                ?>
                            </td>
                            <td>
                                <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn-small btn-view">View Details</a>
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