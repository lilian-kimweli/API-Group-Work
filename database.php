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
        
    }
    
} catch(PDOException $exception) {
    echo "<p style='color: red;'>Error: " . $exception->getMessage() . "</p>";
}
?>