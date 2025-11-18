<?php
// Navigation for all pages
?>
<div class="nav-links">
    <span style="color: white;">Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?></span>
    <span style="color: white; margin-left: 10px;">(<?php echo htmlspecialchars(ucfirst($_SESSION['role'] ?? 'guest')); ?>)</span>
    
    <a href="dashboard.php" class="nav-btn">Dashboard</a>
    <a href="products.php" class="nav-btn">Products</a>
    
    <?php 
    // Safe cart count - handle corrupted cart data
    $item_count = 0;
    if (isset($_SESSION['cart'])) {
        if (is_array($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                if (is_array($item) && isset($item['quantity'])) {
                    $item_count += $item['quantity'];
                } elseif (is_numeric($item)) {
                    // Handle old cart structure
                    $item_count += $item;
                }
            }
        }
    }
    ?>
    <a href="cart.php" class="nav-btn">Cart (<?php echo $item_count; ?>)</a>
    
    <?php if (Auth::hasAnyRole(['manager', 'admin'])): ?>
        <a href="add_product.php" class="nav-btn">Add Product</a>
        <a href="inventory.php" class="nav-btn">Inventory</a>
        <a href="admin_orders.php" class="nav-btn">Manage Orders</a>
    <?php endif; ?>
    
    <?php if (Auth::hasRole('admin')): ?>
        <a href="categories.php" class="nav-btn">Categories</a>
        <a href="user_management.php" class="nav-btn">Users</a>
    <?php endif; ?>
    
    <a href="logout.php" class="nav-btn">Logout</a>
</div>