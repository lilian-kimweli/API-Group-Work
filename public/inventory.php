<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';
require_once '../classes/Database.php';

// Only managers and admins can access inventory
Auth::requireAnyRole(['manager', 'admin']);

$db = new Database();
$conn = $db->getConnection();

// Handle inventory actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_stock'])) {
        $product_id = $_POST['product_id'];
        $new_quantity = $_POST['quantity'];
        
        $sql = "UPDATE products SET on_hand_quantity = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt->execute([$new_quantity, $product_id])) {
            $message = "✅ Stock updated successfully!";
        } else {
            $error = "❌ Failed to update stock.";
        }
    }
    
    if (isset($_POST['toggle_status'])) {
        $product_id = $_POST['product_id'];
        $current_status = $_POST['current_status'];
        $new_status = $current_status ? 0 : 1;
        
        $sql = "UPDATE products SET is_active = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt->execute([$new_status, $product_id])) {
            $message = "✅ Product status updated!";
        } else {
            $error = "❌ Failed to update product status.";
        }
    }
}

// Build the SQL query with filters
$sql = "SELECT p.*, 
               c.name as category_name,
               b.name as brand_name,
               cl.name as color_name,
               m.name as material_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN brands b ON p.brand_id = b.id
        LEFT JOIN colors cl ON p.color_id = cl.id
        LEFT JOIN materials m ON p.material_id = m.id
        WHERE 1=1";

$params = [];

// Category filter
if (!empty($_GET['category'])) {
    $sql .= " AND p.category_id = ?";
    $params[] = $_GET['category'];
}

// Stock status filter
if (!empty($_GET['stock_status'])) {
    if ($_GET['stock_status'] == 'low') {
        $sql .= " AND p.on_hand_quantity < 10 AND p.on_hand_quantity > 0";
    } elseif ($_GET['stock_status'] == 'out') {
        $sql .= " AND p.on_hand_quantity = 0";
    } elseif ($_GET['stock_status'] == 'healthy') {
        $sql .= " AND p.on_hand_quantity >= 10";
    }
}

// Status filter
if (!empty($_GET['status'])) {
    if ($_GET['status'] == 'active') {
        $sql .= " AND p.is_active = 1";
    } elseif ($_GET['status'] == 'inactive') {
        $sql .= " AND p.is_active = 0";
    }
}

$sql .= " ORDER BY p.on_hand_quantity ASC, p.name ASC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$result = $stmt;

// Get inventory statistics - REMOVED is_active condition since column doesn't exist
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_products,
        SUM(on_hand_quantity) as total_stock,
        SUM(unit_price * on_hand_quantity) as total_value,
        AVG(unit_price) as avg_price
    FROM products 
")->fetch(PDO::FETCH_ASSOC);

// Get categories for filter dropdown - FIXED QUERY
$categories_result = $conn->query("SELECT * FROM categories ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Bluebell Inventory</title>
    <link rel="stylesheet" href="style.css">
<style>

    body {
        font-family: Arial, sans-serif;
        background: #f5f5f5;
        margin: 0;
        padding: 0;
    }
main {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
    background: white;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }

    th, td {
        border: 1px solid #ccc;
        padding: 12px;
        text-align: left;
    }

    th {
        background: #3498db;
        color: white;
    }

    tr:nth-child(even) {
        background: #f9f9f9;
    }

    tr.low-stock {
        background: #fff3cd !important;
        color: #856404 !important;
    }

    tr.out-of-stock {
        background: #f8d7da !important;
        color: #721c24 !important;
    }

    /* Button Styles */
    button {
        background: #27ae60;
        color: white !important;
        border: none;
        padding: 8px 12px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
    }

    button:hover {
        opacity: 0.9;
    }

    .btn-danger {
        background: #e74c3c !important;
    }

    .btn-warning {
        background: #f39c12 !important;
    }

    .btn-secondary {
        background: #3498db !important;
        color: white !important;
        text-decoration: none;
        padding: 8px 12px;
        border-radius: 5px;
        display: inline-block;
        font-weight: 600;
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

    .quantity-input {
        width: 70px;
        padding: 5px;
        border: 1px solid #ccc;
        border-radius: 4px;
        text-align: center;
    }

    .status-active {
        color: #27ae60 !important;
        font-weight: bold;
    }

    .status-inactive {
        color: #e74c3c !important;
        font-weight: bold;
    }

    .search-filters {
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

    .filter-group label {
        font-weight: bold;
        margin-right: 5px;
    }

    .action-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }

    .product-image {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 4px;
    }

    /* Quick actions styling */
    .quick-actions {
        margin-top: 30px;
        text-align: center;
    }

    .quick-actions h3 {
        margin-bottom: 15px;
        color: #2c3e50;
    }

    .action-links {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
    }

    /* Make sure table text is always readable */
    table td {
        color: #333 !important;
        background: inherit !important;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
         main {
        padding: 15px;
        margin: 10px;
    }
        table {
            display: block;
            overflow-x: auto;
            white-space: nowrap;
        }
        
        .stats-container {
            grid-template-columns: 1fr;
        }
        
        .filter-group {
            display: block;
            margin-right: 0;
            margin-bottom: 10px;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        th, td {
            padding: 8px;
            font-size: 14px;
        }
    }
</style>
</head>
<body>
      <div class="header">
        <div class="container">
            <h1>Bluebell Inventory System</h1>
        </div>
    </div>

    <?php include 'nav.php'; ?>


        <main>
            <h2>📊 Inventory Management</h2>

            <?php if (!empty($message)): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Inventory Statistics -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_products'] ?? 0; ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_stock'] ?? 0; ?></div>
                    <div class="stat-label">Total Items in Stock</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">KSh<?php echo number_format($stats['total_value'] ?? 0, 2); ?></div>
                    <div class="stat-label">Total Inventory Value</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">KSh<?php echo number_format($stats['avg_price'] ?? 0, 2); ?></div>
                    <div class="stat-label">Average Price</div>
                </div>
            </div>

          <div class="filter-group">
    <label>Category:</label>
    <select name="category">
        <option value="">All Categories</option>
        <?php
        // Use the fixed categories query
        while ($cat = $categories_result->fetch(PDO::FETCH_ASSOC)) {
            $selected = ($_GET['category'] ?? '') == $cat['id'] ? 'selected' : '';
            echo "<option value='{$cat['id']}' $selected>{$cat['name']}</option>";
        }
        ?>
    </select>
</div>
                            ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Stock Status:</label>
                        <select name="stock_status">
                            <option value="">All Items</option>
                            <option value="low" <?php echo ($_GET['stock_status'] ?? '') == 'low' ? 'selected' : ''; ?>>Low Stock (< 10)</option>
                            <option value="out" <?php echo ($_GET['stock_status'] ?? '') == 'out' ? 'selected' : ''; ?>>Out of Stock</option>
                            <option value="healthy" <?php echo ($_GET['stock_status'] ?? '') == 'healthy' ? 'selected' : ''; ?>>Healthy Stock</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Status:</label>
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="active" <?php echo ($_GET['status'] ?? '') == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($_GET['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="btn-secondary" style="border: none; cursor: pointer;">Apply Filters</button>
                        <a href="inventory.php" class="btn-secondary" style="background: #7f8c8d;">Clear Filters</a>
                    </div>
                </form>
            </div>

            <!-- Inventory Table -->
            <table>
                <tr>
                    <th>Image</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Brand</th>
                    <th>Color</th>
                    <th>Price</th>
                    <th>Stock Level</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): 
                    $stock_class = '';
                    if ($row['on_hand_quantity'] == 0) {
                        $stock_class = 'out-of-stock';
                    } elseif ($row['on_hand_quantity'] < 10) {
                        $stock_class = 'low-stock';
                    }
                ?>
                <tr class="<?php echo $stock_class; ?>">
                    <td>
    <?php 
    $image_url = $row['image_url'] ?? '';
    $has_image = !empty($image_url);
    ?>
    
    <?php if ($has_image): ?>
        <img src="<?php echo htmlspecialchars($image_url); ?>" 
             alt="<?php echo htmlspecialchars($row['name']); ?>"
             class="inventory-image"
             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
        <div class="image-fallback" style="display: none; width: 60px; height: 60px;">
            Image<br>Error
        </div>
    <?php else: ?>
        <div class="image-fallback" style="width: 60px; height: 60px;">
            No Image
        </div>
    <?php endif; ?>
</td>
                    <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['category_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['brand_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['color_name'] ?? 'N/A'); ?></td>
                    <td><strong>KSh <?php echo number_format($row['unit_price'], 2); ?></strong></td>
                    <td>
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                            <input type="number" name="quantity" value="<?php echo $row['on_hand_quantity']; ?>" 
                                   min="0" class="quantity-input">
                            <button type="submit" name="update_stock">Update</button>
                        </form>
                    </td>
                    <td>
                        <?php if ($row['is_active']): ?>
                            <span class="status-active">Active</span>
                        <?php else: ?>
                            <span class="status-inactive">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="current_status" value="<?php echo $row['is_active']; ?>">
                                <button type="submit" name="toggle_status" class="<?php echo $row['is_active'] ? 'btn-warning' : 'btn-secondary'; ?>">
                                    <?php echo $row['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                            <a href="edit_product.php?id=<?php echo $row['id']; ?>" class="btn-secondary">Edit</a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>

            <?php if ($stmt->rowCount() === 0): ?>
                <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                    <h3>No products found in inventory</h3>
                    <p>Try adjusting your filters or add some products to get started.</p>
                    <a href="add_product.php" class="btn-secondary" style="padding: 10px 20px;">Add New Product</a>
                </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3>Quick Actions</h3>
                <div class="action-links">
                    <a href="add_product.php" class="btn-secondary">➕ Add New Product</a>
                    <a href="products.php" class="btn-secondary">🛍️ View Products Page</a>
                    <a href="categories.php" class="btn-secondary">📂 Manage Categories</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>