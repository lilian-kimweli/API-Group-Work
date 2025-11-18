<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';
require_once '../classes/Database.php';
require_once '../classes/Cart.php';
require_once '../mpesa/functions.php';

// Check if user is logged in
Auth::requireAuth();

$db = new Database();
$conn = $db->getConnection();
$cart = new Cart();

// Redirect if cart is empty
if ($cart->isEmpty()) {
    header('Location: cart.php');
    exit();
}

// FIX: Get cart items with validation
$cart_items = $cart->getItems();
if (!is_array($cart_items)) {
    $cart_items = [];
}

$cart_total = $cart->getTotal();

// Handle checkout form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $shipping_address = $_POST['shipping_address'];
    $payment_method = $_POST['payment_method'];
    $customer_notes = $_POST['customer_notes'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';
    
    // Validate phone number for M-Pesa
    if ($payment_method == 'M-Pesa' && empty($phone_number)) {
        $error = "Phone number is required for M-Pesa payments.";
    } else {
        try {
            // Start transaction
            $conn->beginTransaction();
            
            // Create order
            $stmt = $conn->prepare("
                INSERT INTO orders (customer_id, total_amount, shipping_address, payment_method, customer_notes, phone_number, payment_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $payment_status = ($payment_method == 'Cash') ? 'pending' : 'pending';
            
            $stmt->execute([
                $_SESSION['user_id'],
                $cart_total,
                $shipping_address,
                $payment_method,
                $customer_notes,
                $phone_number,
                $payment_status
            ]);
            
            $order_id = $conn->lastInsertId();
            
            // Add order items - with validation
            foreach ($cart_items as $product_id => $item) {
                // Skip invalid items
                if (!is_array($item) || !isset($item['price']) || !isset($item['quantity'])) {
                    continue;
                }
                
                $subtotal = $item['price'] * $item['quantity'];
                
                $stmt = $conn->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $order_id,
                    $product_id,
                    $item['quantity'],
                    $item['price'],
                    $subtotal
                ]);
            }
            
               if ($payment_method == 'M-Pesa') {
    $total_amount = $cart_total + 200; // Real amount with shipping
    
    // FIX: Round to whole number and ensure minimum 1
    $total_amount = round($total_amount);
    $total_amount = max(1, $total_amount); // At least 1 KSH
    
    $account_ref = "Order #" . $order_id;
    
    $mpesa_result = initiateSTKPush($phone_number, $total_amount, $account_ref);
    
                // Initiate M-Pesa payment
                $mpesa_result = initiateSTKPush($phone_number, $total_amount, $account_ref);
                
                if (isSTKPushSuccessful($mpesa_result)) {
                    // STK Push initiated successfully
                    updateOrderPaymentStatus($order_id, 'processing', $mpesa_result);
                    
                    // Store order ID in session for callback handling
                    $_SESSION['pending_order'] = $order_id;
                    
                    // Commit transaction
                    $conn->commit();
                    
                    // Clear cart
                    $cart->clear();
                    
                    // Redirect to pending payment page
                    $_SESSION['mpesa_pending'] = "M-Pesa payment prompt sent to your phone. Please complete the payment to confirm your order.";
                    header('Location: payment_pending.php?order_id=' . $order_id);
                    exit();
                } else {
                    // M-Pesa failed
                    $error_msg = getSTKPushErrorMessage($mpesa_result);
                    throw new Exception("M-Pesa payment failed: " . $error_msg);
                }
            } else {
                // For cash payments, just commit
                $conn->commit();
                
                // Clear cart
                $cart->clear();
                
                // Redirect to success page
                $_SESSION['order_success'] = "Order #$order_id placed successfully! Payment will be collected on delivery.";
                header('Location: orders.php');
                exit();
            }
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollBack();
            $error = "Failed to process order: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Bluebell Inventory</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .checkout-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }
        
        .checkout-form {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .order-summary {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
            height: fit-content;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .order-total {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-top: 2px solid #ecf0f1;
            font-weight: bold;
            font-size: 1.2em;
            color: #27ae60;
        }
        
        .checkout-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            width: 100%;
            margin-top: 20px;
            transition: background 0.3s;
        }
        
        .checkout-btn:hover {
            background: #219652;
        }
        
        .payment-method {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .payment-option {
            border: 2px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-option:hover {
            border-color: #3498db;
        }
        
        .payment-option.selected {
            border-color: #27ae60;
            background: #f8fff8;
        }
        
        .payment-option input {
            display: none;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
        }
        
        .phone-field {
            background: #f8fff8;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #27ae60;
            margin-top: 10px;
        }
        
        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }
            
            .order-summary {
                position: static;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container">
            <h1>Bluebell Inventory System</h1>
            <?php include 'nav.php'; ?>
        </div>
    </div>

    <div class="container">
        <h2>🛒 Checkout</h2>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="checkout-container">
            <!-- Checkout Form -->
            <div class="checkout-form">
                <h3>Shipping Information</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="shipping_address">Shipping Address *</label>
                        <textarea 
                            id="shipping_address" 
                            name="shipping_address" 
                            placeholder="Enter your complete shipping address..." 
                            required
                        ></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Payment Method *</label>
                        <div class="payment-method">
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="M-Pesa" checked>
                                <div>📱 M-Pesa</div>
                            </label>
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="Cash">
                                <div>💵 Cash on Delivery</div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group" id="phone_number_group">
                        <div class="phone-field">
                            <label for="phone_number">M-Pesa Phone Number *</label>
                            <input 
                                type="text" 
                                id="phone_number" 
                                name="phone_number" 
                                placeholder="2547XXXXXXXX" 
                                value=""
                                required
                            >
                            <small style="color: #666; display: block; margin-top: 5px;">
                                Enter your M-Pesa registered phone number (format: 2547XXXXXXXX)
                            </small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="customer_notes">Order Notes (Optional)</label>
                        <textarea 
                            id="customer_notes" 
                            name="customer_notes" 
                            placeholder="Any special instructions for your order..."
                        ></textarea>
                    </div>
                    
                    <button type="submit" class="checkout-btn">Place Order</button>
                </form>
            </div>
            
            <!-- Order Summary -->
            <div class="order-summary">
                <h3>Order Summary</h3>
                <?php foreach ($cart_items as $product_id => $item): ?>
                    <?php 
                    // FIX: Validate each item before displaying
                    if (!is_array($item) || !isset($item['name']) || !isset($item['price']) || !isset($item['quantity'])) {
                        // Skip invalid items
                        continue;
                    }
                    ?>
                <div class="order-item">
                    <div>
                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                        <div style="color: #7f8c8d; font-size: 0.9em;">
                            Qty: <?php echo $item['quantity']; ?> ×   KSh <?php echo number_format($item['price'], 2); ?>
                        </div>
                    </div>
                    <div>KSh <?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                </div>
                <?php endforeach; ?>
                
                <div class="order-item">
                    <div>Subtotal</div>
                    <div>KSh <?php echo number_format($cart_total, 2); ?></div>
                </div>
                
                <div class="order-item">
                    <div>Shipping</div>
                    <div>KSh 200.00</div>
                </div>
                
                <div class="order-total">
                    <div>Total</div>
                    <div>KSh <?php echo number_format($cart_total + 200, 2); ?></div>
                </div>
                
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <strong>📦 Delivery Information:</strong>
                    <p style="margin: 10px 0 0 0; color: #7f8c8d;">
                        Standard delivery: 2-3 business days<br>
                        Free shipping on orders over KSh 5,000
                    </p>
                </div>
                
                <div style="margin-top: 15px; padding: 15px; background: #fff3cd; border-radius: 5px; border-left: 4px solid #ffc107;">
                    <strong>💡 Payment Information:</strong>
                    <p style="margin: 10px 0 0 0; color: #856404;">
                        <strong>M-Pesa:</strong> You'll receive a payment prompt on your phone<br>
                        <strong>Cash:</strong> Pay when your order is delivered
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Payment method selection
        document.querySelectorAll('.payment-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove selected class from all options
                document.querySelectorAll('.payment-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                // Add selected class to clicked option
                this.classList.add('selected');
                // Check the radio button
                this.querySelector('input').checked = true;
                
                // Show/hide phone number field for M-Pesa
                const phoneGroup = document.getElementById('phone_number_group');
                const phoneInput = document.getElementById('phone_number');
                
                if (this.querySelector('input').value === 'M-Pesa') {
                    phoneGroup.style.display = 'block';
                    phoneInput.required = true;
                } else {
                    phoneGroup.style.display = 'none';
                    phoneInput.required = false;
                }
            });
        });

        // Auto-select first payment option and trigger change
        document.querySelector('.payment-option').classList.add('selected');
        
        // Initialize phone field visibility based on selected payment method
        document.addEventListener('DOMContentLoaded', function() {
            const selectedPayment = document.querySelector('input[name="payment_method"]:checked');
            const phoneGroup = document.getElementById('phone_number_group');
            const phoneInput = document.getElementById('phone_number');
            
            if (selectedPayment && selectedPayment.value === 'M-Pesa') {
                phoneGroup.style.display = 'block';
                phoneInput.required = true;
            } else {
                phoneGroup.style.display = 'none';
                phoneInput.required = false;
            }
        });
    </script>
</body>
</html>