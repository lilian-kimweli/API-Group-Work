<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';
require_once '../classes/Database.php';

// Check if user is logged in
Auth::requireAuth();

$db = new Database();
$conn = $db->getConnection();

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

<<<<<<< HEAD
// Brand filter
if (!empty($_GET['brand'])) {
    $sql .= " AND p.brand_id = ?";
    $params[] = $_GET['brand'];
}
=======

$productManager = new ProductManager();
$products = $productManager->getAllProducts();
>>>>>>> a8d3cad25a695025eeea249ef2e6fdaedc265aea

$sql .= " ORDER BY p.id DESC";

// Prepare and execute the query with filters
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$result = $stmt;

// Handle add to cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
    require_once '../classes/Cart.php';
    
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'] ?? 1;
    
    // Get product details for cart
    $stmt = $conn->prepare("SELECT name, unit_price FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        // Use Cart class instead of direct session manipulation
        $cart = new Cart();
        $cart->addItem($product_id, $product['name'], $product['unit_price'], $quantity);
        $message = "✅ Item added to cart successfully!";
    } else {
        $error = "❌ Product not found!";
    }
}

// Get categories and brands for filter dropdowns - FIXED QUERIES
$categories_result = $conn->query("SELECT * FROM categories ORDER BY name");
$brands_result = $conn->query("SELECT * FROM brands ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Bluebell Inventory</title>
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
button {
    background: #27ae60;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
}
button:hover {
    background: #219652;
}
.btn-secondary {
    background: #3498db;
}
.btn-secondary:hover {
    background: #2980b9;
}
.message {
    background: #e7f3e7;
    color: #2d662d;
    border: 1px solid #b2d8b2;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 6px;
}
.product-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
}
.quantity-input {
    width: 60px;
    padding: 5px;
    border: 1px solid #ccc;
    border-radius: 4px;
}
.filters {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.filter-group {
    display: inline-block;
    margin-right: 15px;
}

/* ===== RESPONSIVE STYLES ===== */

/* Tablet Screens (768px and below) */
@media (max-width: 768px) {
    main {
        padding: 15px;
        margin: 10px;
    }
    
    .filters {
        padding: 12px;
    }
    
    .filter-group {
        display: block;
        margin-right: 0;
        margin-bottom: 10px;
        width: 100%;
    }
    
    .filter-group select {
        width: 100%;
        margin-top: 5px;
    }
    
    /* Make table scrollable horizontally */
    table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
        font-size: 14px;
    }
    
    th, td {
        padding: 8px 10px;
    }
    
    .product-image {
        width: 60px;
        height: 60px;
    }
    
    .quantity-input {
        width: 50px;
    }
    
    /* Stack action buttons vertically */
    td:last-child {
        min-width: 150px;
    }
    
    td:last-child form {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    
    td:last-child .btn-secondary {
        margin-top: 5px;
    }
}

/* Mobile Screens (480px and below) */
@media (max-width: 480px) {
    main {
        padding: 10px;
        margin: 5px;
    }
    
    h2 {
        font-size: 1.3rem;
        text-align: center;
    }
    
    .filters h3 {
        font-size: 1.1rem;
        text-align: center;
    }
    
    table {
        font-size: 12px;
    }
    
    th, td {
        padding: 6px 8px;
    }
    
    .product-image {
        width: 50px;
        height: 50px;
    }
    
    button {
        padding: 6px 10px;
        font-size: 12px;
    }
    
    .quantity-input {
        width: 45px;
        padding: 4px;
        font-size: 12px;
    }
    
    /* Hide less important columns on very small screens */
    td:nth-child(4), /* Brand */
    td:nth-child(5), /* Color */
    td:nth-child(6) { /* Material */
        display: none;
    }
    
    th:nth-child(4),
    th:nth-child(5),
    th:nth-child(6) {
        display: none;
    }
}

/* Very Small Screens (360px and below) */
@media (max-width: 360px) {
    table {
        font-size: 11px;
    }
    
    th, td {
        padding: 4px 6px;
    }
    
    .product-image {
        width: 40px;
        height: 40px;
    }
    
    button {
        padding: 4px 8px;
        font-size: 11px;
    }
    
    .quantity-input {
        width: 40px;
    }
    
    /* Show only essential columns */
    td:nth-child(3) { /* Category */
        display: none;
    }
    
    th:nth-child(3) {
        display: none;
    }
}

/* Ensure buttons are always visible */
button, .btn-secondary {
    color: white !important;
    font-weight: 600;
    text-shadow: 0 1px 1px rgba(0,0,0,0.3);
}

/* Print Styles */
@media print {
    .filters, button, .quantity-input {
        display: none;
    }
    
    main {
        box-shadow: none;
        margin: 0;
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
        <h2>🛍️ Clothing Products</h2>

        <?php if (!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- Filters Section -->
        <div class="filters">
            <h3>🔍 Filter Products</h3>
            <form method="GET" action="">
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
                <div class="filter-group">
                    <label>Brand:</label>
                    <select name="brand">
                        <option value="">All Brands</option>
                        <?php
                        // Use the fixed brands query
                        while ($brand = $brands_result->fetch(PDO::FETCH_ASSOC)) {
                            $selected = ($_GET['brand'] ?? '') == $brand['id'] ? 'selected' : '';
                            echo "<option value='{$brand['id']}' $selected>{$brand['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <button type="submit" class="btn-secondary">Apply Filters</button>
                <a href="products.php" class="btn-secondary" style="background: #7f8c8d;">Clear Filters</a>
            </form>
        </div>

        <table>
            <tr>
                <th>Image</th>
                <th>Product Name</th>
                <th>Category</th>
                <th>Brand</th>
                <th>Color</th>
                <th>Material</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
            <tr>
                <td>
    <?php 
    $image_url = $row['image_url'] ?? '';
    $has_image = !empty($image_url);
    ?>
    
    <?php if ($has_image): ?>
        <img src="<?php echo htmlspecialchars($image_url); ?>" 
             alt="<?php echo htmlspecialchars($row['name']); ?>"
             class="product-image"
             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
        <div class="image-fallback" style="display: none;">
            Image<br>Not Found
        </div>
    <?php else: ?>
        <div class="image-fallback">
            No Image
        </div>
    <?php endif; ?>
</td>

                <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                <td><?php echo htmlspecialchars($row['category_name'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($row['brand_name'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($row['color_name'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($row['material_name'] ?? 'N/A'); ?></td>
                <td><?php echo $row['on_hand_quantity']; ?> units</td>
                <td>
                    <?php if (Auth::isCustomer()): ?>
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                        <input type="number" name="quantity" value="1" min="1" max="<?php echo $row['on_hand_quantity']; ?>" class="quantity-input">
                        <button type="submit" name="add_to_cart">Add to Cart</button>
                    </form>
                    <?php endif; ?>
                    
                    <?php if (Auth::hasAnyRole(['manager', 'admin'])): ?>
                    <a href="edit_product.php?id=<?php echo $row['id']; ?>" class="btn-secondary" style="padding: 6px 10px; text-decoration: none;">Edit</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>

        <?php if ($result->rowCount() === 0): ?>
            <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                <h3>No products found</h3>
                <p>Try adjusting your filters or check back later.</p>
                <?php if (Auth::hasAnyRole(['manager', 'admin'])): ?>
                    <p><a href="add_product.php" class="btn-secondary">Add your first product</a></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
