<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - Bluebell Inventory</title>
    <link rel="stylesheet" href="style.css">
   
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>Bluebell Inventory System</h1>
        </div>
    </div>

    <div class="container">
        <div class="error-icon">🚫</div>
        <h2>Access Denied</h2>
        <p>You don't have permission to access this page.</p>
        <p><strong>Your role:</strong> <?php echo htmlspecialchars($_SESSION['role'] ?? 'Guest'); ?></p>
        <p>Please contact an administrator if you believe this is an error.</p>
        
        <div style="margin-top: 30px;">
            <a href="dashboard.php" class="btn">Go to Dashboard</a>
            <a href="logout.php" class="btn" style="background: #7f8c8d;">Logout</a>
        </div>
    </div>
</body>
</html>