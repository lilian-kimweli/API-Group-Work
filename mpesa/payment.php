<?php
session_start();
require_once 'functions.php'; // your existing functions
require_once 'callback.php';  // for any callbacks, if needed

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user orders from the database
$orders = getUserOrders($user_id); // function from functions.php
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payments</title>
    <link rel="stylesheet" href="styles.css"> <!-- optional -->
</head>
<body>
    <h1>Make a Payment</h1>

    <?php if (empty($orders)) : ?>
        <p>You have no orders to pay for.</p>
    <?php else : ?>
        <table border="1" cellpadding="10">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Item</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order) : ?>
                    <tr>
                        <td><?= htmlspecialchars($order['order_id']); ?></td>
                        <td><?= htmlspecialchars($order['item_name']); ?></td>
                        <td>$<?= number_format($order['amount'], 2); ?></td>
                        <td><?= htmlspecialchars($order['status']); ?></td>
                        <td>
                            <?php if ($order['status'] === 'Pending') : ?>
                                <form method="POST" action="payments.php">
                                    <input type="hidden" name="order_id" value="<?= $order['order_id']; ?>">
                                    <button type="submit" name="pay_now">Pay Now</button>
                                </form>
                            <?php else : ?>
                                Paid
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

<?php
// Handle payment form submission
if (isset($_POST['pay_now'])) {
    $order_id = $_POST['order_id'];

    // Here you can integrate a real payment gateway API
    $payment_success = processPayment($user_id, $order_id); // function from callback.php or functions.php

    if ($payment_success) {
        // Update order status
        markOrderPaid($order_id); // function in functions.php
        echo "<p style='color:green;'>Payment successful for Order #$order_id!</p>";
        echo "<script>setTimeout(()=>{window.location='payments.php'},2000)</script>"; // reload page
    } else {
        echo "<p style='color:red;'>Payment failed. Try again.</p>";
    }
}
?>

<<<<<<< HEAD
</body>
</html>
=======
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
>>>>>>> a8d3cad25a695025eeea249ef2e6fdaedc265aea
