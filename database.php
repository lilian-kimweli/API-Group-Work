<?php
include_once 'config.php';

echo "<h2>Blue Bell Inventory - Database Setup</h2>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if($conn) {
        // Create production_lines table
        $conn->exec("CREATE TABLE IF NOT EXISTS production_lines (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            classification VARCHAR(50)
        )");
        
        echo "<p>✓ Production lines table created</p>";
        
        // Create styles table
        $conn->exec("CREATE TABLE IF NOT EXISTS styles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            production_line_id INT
        )");
        
        echo "<p>✓ Styles table created</p>";
      
        // Create lots table
$conn->exec("CREATE TABLE IF NOT EXISTS lots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL
)");
echo "<p>✓ Lots table created</p>";

// Create sizes table
$conn->exec("CREATE TABLE IF NOT EXISTS sizes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(50)
)");
echo "<p>✓ Sizes table created</p>";

// Create customers table
$conn->exec("CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    address TEXT,
    telephone VARCHAR(20)
)");
echo "<p>✓ Customers table created</p>";
    }
    
} catch(PDOException $exception) {
    echo "<p style='color: red;'>Error: " . $exception->getMessage() . "</p>";
}
?>