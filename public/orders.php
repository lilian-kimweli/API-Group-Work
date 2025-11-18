<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';
require_once '../classes/Database.php'; // Add this line

// Check if user is logged in
Auth::requireAuth();

$db = new Database();
$conn = $db->getConnection();

$orders = [];
$error = null;

try {
    // Get orders for current user
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("
        SELECT o.*, 
               COUNT(oi.id) as item_count,
               SUM(oi.quantity) as total_quantity
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id 
        WHERE o.customer_id = ? 
        GROUP BY o.id 
        ORDER BY o.order_date DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Unable to load orders at the moment. Please try again later.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Bluebell Inventory</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .orders-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin: 20px 0;
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
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .detail-item {
            background: white;
            padding: 8px;
            border-radius: 4px;
            text-align: center;
        }
        
        .detail-label {
            font-size: 0.8rem;
            color: #7f8c8d;
        }
        
        .detail-value {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .no-orders, .error-message {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            border-radius: 8px;
        }
        
        .btn-secondary {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-top: 15px;
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
            <h2>📦 My Orders</h2>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <h3>⚠️ System Notice</h3>
                    <p><?php echo $error; ?></p>
                    <a href="products.php" class="btn-secondary">Continue Shopping</a>
                </div>
            
            <?php elseif (empty($orders)): ?>
                <div class="no-orders">
                    <h3>🎉 No orders yet</h3>
                    <p>You haven't placed any orders yet. Start shopping to see your orders here!</p>
                    <a href="products.php" class="btn-secondary">Start Shopping</a>
                </div>
            
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <strong>Order #<?php echo $order['id']; ?></strong>
                            <div style="color: #7f8c8d; font-size: 0.9rem;">
                                Placed on: <?php echo date('F j, Y g:i A', strtotime($order['order_date'])); ?>
                            </div>
                        </div>
                        <div class="order-status status-<?php echo $order['status'] ?? 'pending'; ?>">
                            <?php echo ucfirst($order['status'] ?? 'pending'); ?>
                        </div>
                    </div>
                    
                    <div class="order-details">
                        <div class="detail-item">
                            <div class="detail-label">Items</div>
                            <div class="detail-value"><?php echo $order['item_count'] ?? 0; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Quantity</div>
                            <div class="detail-value"><?php echo $order['total_quantity'] ?? 0; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Total</div>
                            <div class="detail-value">KSh <?php echo number_format($order['total_amount'] ?? 0, 2); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Payment</div>
                            <div class="detail-value"><?php echo ucfirst($order['payment_status'] ?? 'pending'); ?></div>
                        </div>
                    </div>
                    
                    <?php if (!empty($order['shipping_address'])): ?>
                        <div style="margin-top: 10px; padding: 10px; background: white; border-radius: 4px;">
                            <strong>Shipping to:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
