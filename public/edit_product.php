<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';
require_once '../classes/Database.php';

// Only managers and admins can edit products
Auth::requireAnyRole(['manager', 'admin']);

$db = new Database();
$conn = $db->getConnection();

// Get product ID from URL
if (!isset($_GET['id'])) {
    header('Location: inventory.php');
    exit;
}

$product_id = $_GET['id'];

// Fetch product data
$sql = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found!");
}

// Check if is_active column exists in products table
$check_column = $conn->query("SHOW COLUMNS FROM products LIKE 'is_active'")->fetch();
$has_is_active = $check_column !== false;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $brand_id = $_POST['brand_id'];
    $color_id = $_POST['color_id'];
    $material_id = $_POST['material_id'];
    $unit_price = $_POST['unit_price'];
    $on_hand_quantity = $_POST['on_hand_quantity'];
    $description = $_POST['description'];
    $gender = $_POST['gender'];
    $season = $_POST['season'];
    $image_url = $_POST['image_url'];
    
    // Only include is_active if the column exists
    if ($has_is_active) {
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $sql = "UPDATE products SET 
                name = ?, category_id = ?, brand_id = ?, color_id = ?, material_id = ?,
                unit_price = ?, on_hand_quantity = ?, description = ?, gender = ?, 
                season = ?, image_url = ?, is_active = ?
                WHERE id = ?";
        $params = [$name, $category_id, $brand_id, $color_id, $material_id, $unit_price, $on_hand_quantity, $description, $gender, $season, $image_url, $is_active, $product_id];
    } else {
        $sql = "UPDATE products SET 
                name = ?, category_id = ?, brand_id = ?, color_id = ?, material_id = ?,
                unit_price = ?, on_hand_quantity = ?, description = ?, gender = ?, 
                season = ?, image_url = ?
                WHERE id = ?";
        $params = [$name, $category_id, $brand_id, $color_id, $material_id, $unit_price, $on_hand_quantity, $description, $gender, $season, $image_url, $product_id];
    }
    
    $stmt = $conn->prepare($sql);
    if ($stmt->execute($params)) {
        $success = "✅ Product updated successfully!";
        // Refresh product data
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error = "❌ Error updating product: " . implode(", ", $stmt->errorInfo());
    }
}

// Fetch dropdown options - FIXED CATEGORIES QUERY
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$brands = $conn->query("SELECT * FROM brands ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$colors = $conn->query("SELECT * FROM colors ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$materials = $conn->query("SELECT * FROM materials ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Bluebell Inventory</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
        main { padding: 20px; max-width: 800px; margin: 0 auto; }
        .form-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #2c3e50; }
        .form-group input, .form-group select, .form-group textarea { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            font-size: 16px;
            box-sizing: border-box;
        }
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }
        button { 
            background: #3498db; 
            color: white; 
            padding: 12px 30px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 16px;
            margin-right: 10px;
        }
        .btn-danger { background: #e74c3c; }
        button:hover { opacity: 0.9; }
        .message { 
            background: #e7f3e7; 
            color: #2d662d; 
            border: 1px solid #b2d8b2; 
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 6px;
        }
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb; 
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 6px;
        }
        @media (max-width: 768px) {
            .form-row { flex-direction: column; }
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
        <div class="form-container">
            <h2>✏️ Edit Product: <?php echo htmlspecialchars($product['name']); ?></h2>

            <?php if (!empty($success)): ?>
                <div class="message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Product Name:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id">Category:</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $product['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="brand_id">Brand:</label>
                        <select id="brand_id" name="brand_id" required>
                            <option value="">Select Brand</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo $brand['id']; ?>" <?php echo $product['brand_id'] == $brand['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($brand['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="color_id">Color:</label>
                        <select id="color_id" name="color_id" required>
                            <option value="">Select Color</option>
                            <?php foreach ($colors as $color): ?>
                                <option value="<?php echo $color['id']; ?>" <?php echo $product['color_id'] == $color['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($color['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="material_id">Material:</label>
                        <select id="material_id" name="material_id" required>
                            <option value="">Select Material</option>
                            <?php foreach ($materials as $material): ?>
                                <option value="<?php echo $material['id']; ?>" <?php echo $product['material_id'] == $material['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($material['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="gender">Gender:</label>
                        <select id="gender" name="gender" required>
                            <option value="unisex" <?php echo ($product['gender'] ?? '') == 'unisex' ? 'selected' : ''; ?>>Unisex</option>
                            <option value="men" <?php echo ($product['gender'] ?? '') == 'men' ? 'selected' : ''; ?>>Men</option>
                            <option value="women" <?php echo ($product['gender'] ?? '') == 'women' ? 'selected' : ''; ?>>Women</option>
                            <option value="kids" <?php echo ($product['gender'] ?? '') == 'kids' ? 'selected' : ''; ?>>Kids</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="season">Season:</label>
                        <select id="season" name="season" required>
                            <option value="all" <?php echo ($product['season'] ?? '') == 'all' ? 'selected' : ''; ?>>All Season</option>
                            <option value="spring" <?php echo ($product['season'] ?? '') == 'spring' ? 'selected' : ''; ?>>Spring</option>
                            <option value="summer" <?php echo ($product['season'] ?? '') == 'summer' ? 'selected' : ''; ?>>Summer</option>
                            <option value="fall" <?php echo ($product['season'] ?? '') == 'fall' ? 'selected' : ''; ?>>Fall</option>
                            <option value="winter" <?php echo ($product['season'] ?? '') == 'winter' ? 'selected' : ''; ?>>Winter</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="unit_price">Price (KSh):</label>
                        <input type="number" id="unit_price" name="unit_price" step="0.01" min="0" 
                               value="<?php echo $product['unit_price']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="on_hand_quantity">Stock Quantity:</label>
                        <input type="number" id="on_hand_quantity" name="on_hand_quantity" min="0" 
                               value="<?php echo $product['on_hand_quantity']; ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="image_url">Image URL (optional):</label>
                    <input type="url" id="image_url" name="image_url" value="<?php echo htmlspecialchars($product['image_url'] ?? ''); ?>">
                </div>

                <?php if ($has_is_active): ?>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" value="1" <?php echo ($product['is_active'] ?? 0) ? 'checked' : ''; ?>>
                        Product is active
                    </label>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                </div>

                <button type="submit">Update Product</button>
                <a href="inventory.php" class="btn-danger" style="padding: 12px 30px; text-decoration: none; display: inline-block;">Cancel</a>
            </form>
        </div>
    </main>
</body>
</html>