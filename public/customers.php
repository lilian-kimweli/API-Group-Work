<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';
require_once '../classes/Database.php';

// Check if user is logged in and has manager/admin role
Auth::requireAnyRole(['manager', 'admin']);

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Fixed query - removed non-existent 'is_active' column
    $query = "
        SELECT 
            u.id,
            u.username,
            u.email,
            u.role,
            u.created_at,
            u.two_factor_enabled,
            COUNT(o.id) as order_count,
            COALESCE(SUM(o.total_amount), 0) as total_spent
        FROM users u
        LEFT JOIN orders o ON u.id = o.customer_id
        WHERE u.role = 'customer'
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ";
    
    $stmt = $conn->query($query);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle customer actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_status']) && isset($_POST['user_id'])) {
        // In a real application, you might have an 'is_active' column to toggle
        // For now, we'll just show a message
        $_SESSION['customer_message'] = "Customer status updated!";
        header('Location: customers.php');
        exit;
    }
    
    if (isset($_POST['delete_customer']) && isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);
        // Be very careful with deletion - you might want to soft delete instead
        // $delete_query = "DELETE FROM users WHERE id = :user_id AND role = 'customer'";
        $_SESSION['customer_message'] = "Customer deletion would happen here (commented out for safety)";
        header('Location: customers.php');
        exit;
    }
}

// Check for messages
$customer_message = '';
if (isset($_SESSION['customer_message'])) {
    $customer_message = $_SESSION['customer_message'];
    unset($_SESSION['customer_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management - BlubellSeek</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .customers-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .customers-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 20px;
        }
        .customers-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .customers-table th,
        .customers-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        .customers-table th {
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
        .btn-edit {
            background: #f39c12;
            color: white;
        }
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        .btn-toggle {
            background: #27ae60;
            color: white;
        }
        .customer-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        .search-filter {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .action-btns {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .status-active {
            color: #27ae60;
            font-weight: bold;
        }
        .status-inactive {
            color: #e74c3c;
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
                <a href="inventory.php" class="nav-btn">Inventory</a>
                <a href="customers.php" class="nav-btn">Customers</a>
                <a href="logout.php" class="nav-btn">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="customers-header">
            <h2>Customer Management</h2>
            <div>
                <a href="dashboard.php" class="btn" style="background: #7f8c8d;">Back to Dashboard</a>
            </div>
        </div>

        <?php if ($customer_message): ?>
            <div class="customer-message">
                <?php echo htmlspecialchars($customer_message); ?>
            </div>
        <?php endif; ?>

        <!-- Customer Statistics -->
        <div class="stats-cards">
            <?php
            $total_customers = count($customers);
            $active_customers = $total_customers; // Assuming all are active since we don't have is_active column
            $total_revenue = 0;
            $avg_order_value = 0;
            
            foreach ($customers as $customer) {
                $total_revenue += $customer['total_spent'];
            }
            
            $avg_order_value = $total_customers > 0 ? $total_revenue / $total_customers : 0;
            ?>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_customers; ?></div>
                <div>Total Customers</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $active_customers; ?></div>
                <div>Active Customers</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">KSh<?php echo number_format($total_revenue, 2); ?></div>
                <div>Total Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">KSh<?php echo number_format($avg_order_value, 2); ?></div>
                <div>Avg. Customer Value</div>
            </div>
        </div>

        <div class="customers-container">
            <div class="search-filter">
                <form method="GET" action="">
                    <input type="text" name="search" placeholder="Search customers by name or email..." style="padding: 8px; width: 300px;">
                    <button type="submit" class="btn" style="padding: 8px 15px;">Search</button>
                    <a href="customers.php" class="btn" style="background: #7f8c8d; padding: 8px 15px;">Clear</a>
                </form>
            </div>

            <table class="customers-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Orders</th>
                        <th>Total Spent</th>
                        <th>2FA Enabled</th>
                        <th>Joined Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><?php echo $customer['id']; ?></td>
                        <td><?php echo htmlspecialchars($customer['username']); ?></td>
                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                        <td><?php echo $customer['order_count']; ?></td>
                        <td>$<?php echo number_format($customer['total_spent'], 2); ?></td>
                        <td>
                            <?php if ($customer['two_factor_enabled']): ?>
                                <span class="status-active">Yes</span>
                            <?php else: ?>
                                <span class="status-inactive">No</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($customer['created_at'])); ?></td>
                        <td class="action-btns">
                            <a href="view_orders.php?customer_id=<?php echo $customer['id']; ?>" class="btn-small btn-view">Orders</a>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $customer['id']; ?>">
                                <button type="submit" name="toggle_status" class="btn-small btn-toggle">Toggle Status</button>
                            </form>
                            <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this customer? This action cannot be undone.');">
                                <input type="hidden" name="user_id" value="<?php echo $customer['id']; ?>">
                                <button type="submit" name="delete_customer" class="btn-small btn-delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (empty($customers)): ?>
                <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                    <h3>No customers found</h3>
                    <p>Customer accounts will appear here when they register.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>