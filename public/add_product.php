<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';
require_once '../classes/Database.php';

// Only managers and admins can add products
Auth::requireAnyRole(['manager', 'admin']);

$db = new Database();
$conn = $db->getConnection();

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
    $sku = $_POST['sku'];
    $image_url = $_POST['image_url'];

    // Use NULL for size_id and style_id since they might not be in your table yet
    $sql = "INSERT INTO products (name, category_id, brand_id, color_id, material_id, unit_price, on_hand_quantity, description, gender, season, sku, image_url) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if ($stmt->execute([$name, $category_id, $brand_id, $color_id, $material_id, $unit_price, $on_hand_quantity, $description, $gender, $season, $sku, $image_url])) {
        $success = "✅ Product added successfully!";
    } else {
        $error = "Error adding product: " . implode(", ", $stmt->errorInfo());
    }
}

// Fetch dropdown options
$categories = $conn->query("SELECT * FROM categories WHERE parent_id IS NOT NULL ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$brands = $conn->query("SELECT * FROM brands ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$colors = $conn->query("SELECT * FROM colors ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$materials = $conn->query("SELECT * FROM materials ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Bluebell Inventory</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
        main { padding: 20px; max-width: 800px; margin: 0 auto; }
        .form-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 10px; font-weight: bold; color: #2c3e50; }
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
            background: #27ae60; 
            color: white; 
            padding: 12px 30px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 16px;
            width: 100%;
        }
        button:hover { background: #219652; }
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
            <h2>👕 Add New Clothing Product</h2>

            <?php if (!empty($success)): ?>
                <div class="message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Product Name:</label>
                    <input type="text" id="name" name="name" placeholder="e.g., Men's Cotton T-Shirt" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id">Category:</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="brand_id">Brand:</label>
                        <select id="brand_id" name="brand_id" required>
                            <option value="">Select Brand</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo $brand['id']; ?>"><?php echo $brand['name']; ?></option>
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
                                <option value="<?php echo $color['id']; ?>"><?php echo $color['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="material_id">Material:</label>
                        <select id="material_id" name="material_id" required>
                            <option value="">Select Material</option>
                            <?php foreach ($materials as $material): ?>
                                <option value="<?php echo $material['id']; ?>"><?php echo $material['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="gender">Gender:</label>
                        <select id="gender" name="gender" required>
                            <option value="unisex">Unisex</option>
                            <option value="men">Men</option>
                            <option value="women">Women</option>
                            <option value="kids">Kids</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="season">Season:</label>
                        <select id="season" name="season" required>
                            <option value="all">All Season</option>
                            <option value="spring">Spring</option>
                            <option value="summer">Summer</option>
                            <option value="fall">Fall</option>
                            <option value="winter">Winter</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="unit_price">Price ($):</label>
                        <input type="number" id="unit_price" name="unit_price" step="0.01" min="0" placeholder="29.99" required>
                    </div>
                    <div class="form-group">
                        <label for="on_hand_quantity">Stock Quantity:</label>
                        <input type="number" id="on_hand_quantity" name="on_hand_quantity" min="0" placeholder="50" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="sku">SKU (Stock Keeping Unit):</label>
                    <input type="text" id="sku" name="sku" placeholder="NIK-TS-BLK-M" required>
                </div>

                <div class="form-group">
                    <label for="image_url">Image URL (optional):</label>
                    <input type="url" id="image_url" name="image_url" placeholder="https://example.com/image.jpg">
                </div>

                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" rows="4" placeholder="Product description..."></textarea>
                </div>

                <button type="submit">Add Product</button>
            </form>
        </div>
    </main>
</body>
</html>