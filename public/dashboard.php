<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Database.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Get user info
$username = $_SESSION['username'];
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BlubellSeek</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard-header {
            background: #34495e;
            color: white;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        .welcome-message {
            background: #27ae60;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .dashboard-menu {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .menu-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #3498db;
        }
        .menu-card h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .menu-card p {
            color: #7f8c8d;
            margin-bottom: 20px;
        }
        .menu-btn {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
        }
        .menu-btn:hover {
            background: #2980b9;
        }
        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-top: 20px;
        }
        .logout-btn:hover {
            background: #c0392b;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>BlubellSeek Inventory System</h1>
        </div>
    </div>
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

    <div class="container">
        <div class="welcome-message">
            <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
            <p>Role: <?php echo htmlspecialchars(ucfirst($role)); ?></p>
        </div>

        <div class="dashboard-menu">
            <div class="menu-card">
                <h3> Product Catalog</h3>
                <p>Browse and manage all products</p>
                <a href="products.php" class="menu-btn">View Products</a>
            </div>

            <div class="menu-card">
                <h3> Customer Information</h3>
                <p>Manage customer data and relationships</p>
                <a href="customers.php" class="menu-btn">View Customers</a>
            </div>

            <div class="menu-card">
                <h3> Inventory</h3>
                <p>Check current inventory levels</p>
                <a href="inventory.php" class="menu-btn">View Inventory</a>
            </div>
            <div class="menu-card">
                <h3>My Orders</h3>
                <p>View your order history and track shipments</p>
                <a href="orders.php" class="menu-btn">View Orders</a>
            </div>

            <?php if ($role === 'admin' || $role === 'manager'): ?>
            <div class="menu-card">
                <h3> Admin Panel</h3>
                <p>Administrative functions</p>
                <a href="admin.php" class="menu-btn">Admin Access</a>
            </div>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
</body>
</html>