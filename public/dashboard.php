<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/Auth.php';

// FIX: Reset cart if it's corrupted
if (isset($_SESSION['cart']) && !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Check if user is logged in using our new Auth class
Auth::requireAuth();

// Get user info
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Safe cart count
$item_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        if (is_array($item) && isset($item['quantity'])) {
            $item_count += $item['quantity'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Bluebell Inventory</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>
    <div class="header">
        <div class="container">
            <h1>Bluebell Inventory System</h1>
        </div>
    </div>
    
    <div class="nav-links">
        <span style="color: white;">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
        <a href="dashboard.php" class="nav-btn">Dashboard</a>
        <a href="products.php" class="nav-btn">Products</a>
        <a href="cart.php" class="nav-btn">Cart (<?php echo $item_count; ?>)</a>
        <a href="logout.php" class="nav-btn">Logout</a>
    </div>

    <div class="container">
        <div class="welcome-message">
            <h2>Welcome, <?php echo htmlspecialchars($username); ?>! 
                <span class="role-badge"><?php echo htmlspecialchars(ucfirst($role)); ?></span>
            </h2>
            <p>You have successfully logged into Bluebell Clothing Inventory System</p>
        </div>

        <!-- Common Features for All Roles -->
        <div class="role-section">
            <h3>🛍️ Shopping Features</h3>
            <div class="dashboard-menu">
                <div class="menu-card customer-card">
                    <h3>📦 Product Catalog</h3>
                    <p>Browse our clothing collection</p>
                    <a href="products.php" class="menu-btn">View Products</a>
                </div>

                <div class="menu-card customer-card">
                    <h3>🛒 Shopping Cart</h3>
                    <p>View your selected items</p>
                    <a href="cart.php" class="menu-btn">View Cart</a>
                </div>

                <div class="menu-card customer-card">
                    <h3>📋 My Orders</h3>
                    <p>View your order history</p>
                    <a href="orders.php" class="menu-btn">View Orders</a>
                </div>
            </div>
        </div>

        <!-- Manager & Admin Features -->
        <?php if (Auth::hasAnyRole(['manager', 'admin'])): ?>
        <div class="role-section">
            <h3>👔 Management Tools</h3>
            <div class="dashboard-menu">
                <div class="menu-card manager-card">
                    <h3>📊 Inventory Management</h3>
                    <p>Manage clothing stock and levels</p>
                    <a href="inventory.php" class="menu-btn manager-btn">Manage Inventory</a>
                </div>

                <div class="menu-card manager-card">
                    <h3>📦 Manage Orders</h3>
                    <p>View and manage all customer orders</p>
                    <a href="admin_orders.php" class="menu-btn manager-btn">Manage Orders</a>
                </div>

                <div class="menu-card manager-card">
                    <h3>👥 Customer Management</h3>
                    <p>View and manage customer data</p>
                    <a href="customers.php" class="menu-btn manager-btn">View Customers</a>
                </div>

                <div class="menu-card manager-card">
                    <h3>📈 Reports</h3>
                    <p>View sales and inventory reports</p>
                    <a href="reports.php" class="menu-btn manager-btn">View Reports</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Admin Only Features -->
        <?php if (Auth::hasRole('admin')): ?>
        <div class="role-section">
            <h3>⚙️ Administrator Tools</h3>
            <div class="dashboard-menu">
                <div class="menu-card admin-card">
                    <h3>👤 User Management</h3>
                    <p>Manage all system users</p>
                    <a href="user_management.php" class="menu-btn admin-btn">Manage Users</a>
                </div>

                <div class="menu-card admin-card">
                    <h3>🏷️ Category Management</h3>
                    <p>Manage clothing categories</p>
                    <a href="categories.php" class="menu-btn admin-btn">Manage Categories</a>
                </div>

                <div class="menu-card admin-card">
                    <h3>⚙️ System Settings</h3>
                    <p>Configure system preferences</p>
                    <a href="admin_settings.php" class="menu-btn admin-btn">System Settings</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 30px;">
            <a href="setup_2fa.php" class="menu-btn" style="background: #9b59b6;">🔒 Setup 2FA</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
</body>
</html>