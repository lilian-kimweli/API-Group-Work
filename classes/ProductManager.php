<?php
require_once 'Database.php';

class ProductManager {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Get all products with related data
    public function getAllProducts() {
        $query = "SELECT p.*, pl.name as production_line, s.name as style, l.name as lot, sz.description as size 
                  FROM products p
                  LEFT JOIN production_lines pl ON p.production_line_id = pl.id
                  LEFT JOIN styles s ON p.style_id = s.id
                  LEFT JOIN lots l ON p.lot_id = l.id
                  LEFT JOIN sizes sz ON p.size_id = sz.id
                  ORDER BY p.name";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get product by ID
    public function getProductById($id) {
        $query = "SELECT p.*, pl.name as production_line, s.name as style, l.name as lot, sz.description as size 
                  FROM products p
                  LEFT JOIN production_lines pl ON p.production_line_id = pl.id
                  LEFT JOIN styles s ON p.style_id = s.id
                  LEFT JOIN lots l ON p.lot_id = l.id
                  LEFT JOIN sizes sz ON p.size_id = sz.id
                  WHERE p.id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    
    // Get products by production line
    public function getProductsByProductionLine($production_line_id) {
        $query = "SELECT p.*, pl.name as production_line, s.name as style, l.name as lot, sz.description as size 
                  FROM products p
                  LEFT JOIN production_lines pl ON p.production_line_id = pl.id
                  LEFT JOIN styles s ON p.style_id = s.id
                  LEFT JOIN lots l ON p.lot_id = l.id
                  LEFT JOIN sizes sz ON p.size_id = sz.id
                  WHERE p.production_line_id = :production_line_id
                  ORDER BY p.name";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':production_line_id', $production_line_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Create new product
    public function createProduct(Product $product) {
        $query = "INSERT INTO products (name, production_line_id, style_id, lot_id, size_id, unit_cost, unit_price, on_hand_quantity, description) 
                  VALUES (:name, :production_line_id, :style_id, :lot_id, :size_id, :unit_cost, :unit_price, :on_hand_quantity, :description)";
        
        $stmt = $this->db->prepare($query);
        $productData = $product->toArray();
        
        return $stmt->execute($productData);
    }

    // Update product
    public function updateProduct(Product $product) {
        $query = "UPDATE products SET 
                  name = :name, 
                  production_line_id = :production_line_id, 
                  style_id = :style_id, 
                  lot_id = :lot_id, 
                  size_id = :size_id, 
                  unit_cost = :unit_cost, 
                  unit_price = :unit_price, 
                  on_hand_quantity = :on_hand_quantity, 
                  description = :description 
                  WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        $productData = $product->toArray();
        $productData['id'] = $product->getId();
        
        return $stmt->execute($productData);
    }

    // Delete product
    public function deleteProduct($id) {
        $query = "DELETE FROM products WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    // Get low inventory products (less than 10 items)
    public function getLowInventoryProducts() {
        $query = "SELECT p.*, pl.name as production_line 
                  FROM products p
                  LEFT JOIN production_lines pl ON p.production_line_id = pl.id
                  WHERE p.on_hand_quantity < 10
                  ORDER BY p.on_hand_quantity ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get top selling products (you can implement this with transaction data later)
    public function getTopSellingProducts($limit = 5) {
        $query = "SELECT p.*, pl.name as production_line 
                  FROM products p
                  LEFT JOIN production_lines pl ON p.production_line_id = pl.id
                  ORDER BY p.on_hand_quantity DESC 
                  LIMIT :limit";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
