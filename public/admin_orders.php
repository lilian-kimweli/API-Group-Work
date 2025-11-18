<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';
require_once '../classes/Database.php';

// Only managers and admins can access this
Auth::requireAnyRole(['manager', 'admin']);

$db = new Database();
$conn = $db->getConnection();

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    if ($stmt->execute([$new_status, $order_id])) {
        $message = "✅ Order status updated successfully!";
    } else {
        $error = "❌ Failed to update order status.";
    }
}

// Get all orders with customer information
$sql = "
    SELECT o.*, 
           u.username as customer_name,
           u.email as customer_email,
           COUNT(oi.id) as item_count,
           SUM(oi.quantity) as total_quantity
    FROM orders o 
    LEFT JOIN users u ON o.customer_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    GROUP BY o.id 
    ORDER BY o.order_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get order statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_revenue,
        AVG(total_amount) as avg_order_value,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
        COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_orders,
        COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders
    FROM orders
")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Bluebell Inventory</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .orders-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .order-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            background: #f9f9f9;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .order-status {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce7ff; color: #004085; }
        .status-shipped { background: #d1ecf1; color: #0c5460; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .detail-item {
            background: white;
            padding: 10px;
            border-radius: 4px;
        }
        
        .detail-label {
            font-size: 0.8rem;
            color: #7f8c8d;
            display: block;
        }
        
        .detail-value {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .status-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .filter-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .filter-group {
            display: inline-block;
            margin-right: 15px;
            margin-bottom: 10px;
        }
        
        .no-orders {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
        
        .message {
            background: #e7f3e7;
            color: #2d662d;
            border: 1px solid #b2d8b2;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 6px;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container">
            <h1>Bluebell Inventory System</h1>
            <?php include 'nav.php'; ?>
        </div>
    </div>

    <div class="container">
        <div class="orders-container">
            <h2>📦 Manage Customer Orders</h2>

            <?php if (!empty($message)): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Order Statistics -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_orders'] ?? 0; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">KSh <?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">KSh <?php echo number_format($stats['avg_order_value'] ?? 0, 2); ?></div>
                    <div class="stat-label">Average Order</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['pending_orders'] ?? 0; ?></div>
                    <div class="stat-label">Pending Orders</div>
                </div>
            </div>

            <!-- Orders List -->
            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <h3>No orders found</h3>
                    <p>No customers have placed orders yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <strong>Order #<?php echo $order['id']; ?></strong>
                            <div style="color: #7f8c8d; font-size: 0.9rem;">
                                Customer: <?php echo htmlspecialchars($order['customer_name']); ?> (<?php echo htmlspecialchars($order['customer_email']); ?>)<br>
                                Placed on: <?php echo date('F j, Y g:i A', strtotime($order['order_date'])); ?>
                            </div>
                        </div>
                        <div class="order-status status-<?php echo $order['status'] ?? 'pending'; ?>">
                            <?php echo ucfirst($order['status'] ?? 'pending'); ?>
                        </div>
                    </div>
                    
                    <div class="order-details">
                        <div class="detail-item">
                            <span class="detail-label">Items</span>
                            <span class="detail-value"><?php echo $order['item_count'] ?? 0; ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Total Quantity</span>
                            <span class="detail-value"><?php echo $order['total_quantity'] ?? 0; ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Order Total</span>
                            <span class="detail-value">KSh <?php echo number_format($order['total_amount'] ?? 0, 2); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Payment</span>
                            <span class="detail-value"><?php echo ucfirst($order['payment_status'] ?? 'pending'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Payment Method</span>
                            <span class="detail-value"><?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                    
                    <?php if (!empty($order['shipping_address'])): ?>
                        <div style="margin-top: 10px; padding: 10px; background: white; border-radius: 4px;">
                            <strong>Shipping Address:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="action-buttons">
                        <form method="POST" class="status-form">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <select name="status" style="padding: 6px; border-radius: 4px; border: 1px solid #ccc;">
                                <option value="pending" <?php echo ($order['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo ($order['status'] == 'processing') ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo ($order['status'] == 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?php echo ($order['status'] == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo ($order['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            <button type="submit" name="update_status" class="btn-secondary" style="border: none; cursor: pointer;">Update Status</button>
                        </form>
                        
                        <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn-secondary">View Details</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>