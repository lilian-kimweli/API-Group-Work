<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';
require_once '../classes/Database.php';

// Only admins can access
Auth::requireRole('admin');

$db = new Database();
$conn = $db->getConnection();

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_category'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $parent_id = $_POST['parent_id'] ?: null;
        
        $stmt = $conn->prepare("INSERT INTO categories (name, description, parent_id) VALUES (?, ?, ?)");
        if ($stmt->execute([$name, $description, $parent_id])) {
            $message = "✅ Category added successfully!";
        } else {
            $error = "❌ Failed to add category.";
        }
    }
    
    if (isset($_POST['update_category'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        
        $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
        if ($stmt->execute([$name, $description, $id])) {
            $message = "✅ Category updated successfully!";
        } else {
            $error = "❌ Failed to update category.";
        }
    }
    
    if (isset($_POST['delete_category'])) {
        $id = $_POST['id'];
        
        // Check if category has products
        $check = $conn->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $check->execute([$id]);
        $product_count = $check->fetchColumn();
        
        if ($product_count > 0) {
            $error = "❌ Cannot delete category with products. Move products first.";
        } else {
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = "✅ Category deleted successfully!";
            } else {
                $error = "❌ Failed to delete category.";
            }
        }
    }
}

// Get all categories
$categories = $conn->query("
    SELECT c.*, 
           p.name as parent_name,
           COUNT(pr.id) as product_count
    FROM categories c
    LEFT JOIN categories p ON c.parent_id = p.id
    LEFT JOIN products pr ON c.id = pr.category_id
    GROUP BY c.id
    ORDER BY c.parent_id IS NULL DESC, c.name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Get parent categories for dropdown
$parent_categories = $conn->query("
    SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management - Bluebell Inventory</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .categories-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        
        .category-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .category-list {
            margin-top: 20px;
        }
        
        .category-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            background: #f9f9f9;
        }
        
        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .subcategories {
            margin-left: 30px;
            margin-top: 10px;
            border-left: 3px solid #3498db;
            padding-left: 15px;
        }
        
        .product-count {
            background: #3498db;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .no-categories {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>Bluebell Inventory System</h1>
            <?php include 'nav.php'; ?>
        </div>
    </div>

    <div class="container">
        <div class="categories-container">
            <h2>🏷️ Category Management</h2>

            <?php if (!empty($message)): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Add Category Form -->
            <div class="category-form">
                <h3>Add New Category</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name">Category Name *</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="parent_id">Parent Category</label>
                        <select id="parent_id" name="parent_id">
                            <option value="">-- Main Category --</option>
                            <?php foreach ($parent_categories as $parent): ?>
                                <option value="<?php echo $parent['id']; ?>"><?php echo htmlspecialchars($parent['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" name="add_category" class="btn-primary">Add Category</button>
                </form>
            </div>

            <!-- Categories List -->
            <div class="category-list">
                <h3>Existing Categories</h3>
                
                <?php if (empty($categories)): ?>
                    <div class="no-categories">
                        <h3>No categories found</h3>
                        <p>Add your first category above.</p>
                    </div>
                <?php else: ?>
                    <?php
                    // Organize categories by parent
                    $organized_categories = [];
                    foreach ($categories as $category) {
                        if ($category['parent_id'] === null) {
                            $organized_categories[$category['id']] = $category;
                            $organized_categories[$category['id']]['children'] = [];
                        }
                    }
                    
                    foreach ($categories as $category) {
                        if ($category['parent_id'] !== null) {
                            $organized_categories[$category['parent_id']]['children'][] = $category;
                        }
                    }
                    ?>
                    
                    <?php foreach ($organized_categories as $category): ?>
                    <div class="category-item">
                        <div class="category-header">
                            <div>
                                <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                <span class="product-count"><?php echo $category['product_count']; ?> products</span>
                            </div>
                            <div>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                    <button type="submit" name="delete_category" class="btn-danger btn-sm" 
                                            onclick="return confirm('Delete this category?')">Delete</button>
                                </form>
                            </div>
                        </div>
                        
                        <?php if (!empty($category['description'])): ?>
                            <p><?php echo htmlspecialchars($category['description']); ?></p>
                        <?php endif; ?>
                        
                        <!-- Subcategories -->
                        <?php if (!empty($category['children'])): ?>
                            <div class="subcategories">
                                <strong>Subcategories:</strong>
                                <?php foreach ($category['children'] as $subcategory): ?>
                                <div class="category-item">
                                    <div class="category-header">
                                        <div>
                                            <?php echo htmlspecialchars($subcategory['name']); ?>
                                            <span class="product-count"><?php echo $subcategory['product_count']; ?> products</span>
                                        </div>
                                        <div>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $subcategory['id']; ?>">
                                                <button type="submit" name="delete_category" class="btn-danger btn-sm" 
                                                        onclick="return confirm('Delete this subcategory?')">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($subcategory['description'])): ?>
                                        <p><?php echo htmlspecialchars($subcategory['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>