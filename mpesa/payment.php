<?php
// Include the functions - FIXED PATH
require_once 'functions.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['phone'])) {
    $phone = $_POST['phone'];
    $amount = $_POST['amount'];
    $accountRef = $_POST['account_ref'] ?? 'Test Payment';
    
    // Initiate STK Push
    $result = initiateSTKPush($phone, $amount, $accountRef);
    
    // Log the attempt
    logTransaction(array(
        'phone' => $phone,
        'amount' => $amount,
        'result' => $result
    ));
    
    // Display result
    if (isSTKPushSuccessful($result)) {
        $message = "✅ STK Push sent successfully! Check your phone for prompt.";
    } else {
        $error_msg = getSTKPushErrorMessage($result);
        $message = "❌ Error: " . $error_msg;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pay with M-Pesa</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 500px; margin: 50px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="number"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <h2>💳 Pay with M-Pesa</h2>
    
    <?php if (isset($message)): ?>
        <div class="message <?php echo isset($result) && isSTKPushSuccessful($result) ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Phone Number:</label>
            <input type="text" name="phone" value="254708374149" required placeholder="2547...">
        </div>
        
        <div class="form-group">
            <label>Amount (KES):</label>
            <input type="number" name="amount" value="1" min="1" required>
        </div>

        
        
        <button type="submit">Pay with M-Pesa</button>
    </form>
    
    
</html>