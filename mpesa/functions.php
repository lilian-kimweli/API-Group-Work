<?php
// mpesa/functions.php - M-Pesa API Functions
define('CONSUMER_KEY', 'GmcimGFcOMZ1cmGaru6N7RsVFZDM4gQCn3qfrVwzAQs3kzt3'); 
define('CONSUMER_SECRET', 'FrexIZAdN61m7uaQjEqVMVa9WKkAwOwwnXdajgSBwW19L7AIBgEhDiVViakV6AwH'); // Your consumer secret  
define('SHORTCODE', '174379'); // Sandbox shortcode
<<<<<<< HEAD
define('PASSKEY', '35NISyKDpijvtvA7aNHfdDcORAZ_2oQZvgC7x9qVHb4JpP9nw');
define('CALLBACK_URL', 'https://roosevelt-postdiagnostic-richie.ngrok-free.dev/callback.php');
=======
define('PASSKEY', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919');
define('CALLBACK_URL', 'https://epixylous-hurtable-rosalee.ngrok.io/callback.php');

>>>>>>> a8d3cad25a695025eeea249ef2e6fdaedc265aea

// Generate access token
function getAccessToken() {
    $credentials = base64_encode(CONSUMER_KEY . ':' .CONSUMER_SECRET);
    
    $ch = curl_init('https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    return $result['access_token'] ?? null;
}

// Generate timestamp
function getTimestamp() {
    return date('YmdHis');
}

// Generate password
function generatePassword($timestamp) {
    return base64_encode(SHORTCODE . PASSKEY . $timestamp);
}

// Initiate STK Push
function initiateSTKPush($phone, $amount, $accountRef = 'Order Payment') {
    $access_token = getAccessToken();
    
    if (!$access_token) {
        return ['error' => 'Failed to get access token'];
    }
    
    $phone = formatPhoneNumber($phone);
    $timestamp = getTimestamp();
    $password = generatePassword($timestamp);
    
    $payload = [
        'BusinessShortCode' => SHORTCODE,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $amount,
        'PartyA' => $phone,
        'PartyB' => SHORTCODE,
        'PhoneNumber' => $phone,
        'CallBackURL' => CALLBACK_URL,
        'AccountReference' => $accountRef,
        'TransactionDesc' => 'Payment for goods'
    ];
    
    $ch = curl_init('https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// Format phone number
function formatPhoneNumber($phone) {
    $phone = preg_replace('/\D/', '', $phone);
    
    if (strlen($phone) == 9 && substr($phone, 0, 1) == '7') {
        return '254' . $phone;
    }
    
    if (strlen($phone) == 10 && substr($phone, 0, 1) == '0') {
        return '254' . substr($phone, 1);
    }
    
    return $phone;
}

// Check if STK Push was successful
function isSTKPushSuccessful($result) {
    return isset($result['ResponseCode']) && $result['ResponseCode'] == '0';
}

// Get STK Push error message
function getSTKPushErrorMessage($result) {
    if (isset($result['errorMessage'])) {
        return $result['errorMessage'];
    }
    
    if (isset($result['ResponseDescription'])) {
        return $result['ResponseDescription'];
    }
    
    return 'Unknown error occurred';
}

// Log transactions
function logTransaction($data, $filename = 'mpesa_transactions.log') {
    $log = "[" . date('Y-m-d H:i:s') . "] " . print_r($data, true) . "\n";
    file_put_contents($filename, $log, FILE_APPEND | LOCK_EX);
}

// Update order payment status
function updateOrderPaymentStatus($order_id, $status, $transaction_data = null) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            UPDATE orders 
            SET payment_status = ?, 
                mpesa_response = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $mpesa_response = $transaction_data ? json_encode($transaction_data) : null;
        
        return $stmt->execute([$status, $mpesa_response, $order_id]);
    } catch (Exception $e) {
        logTransaction("Error updating order payment: " . $e->getMessage());
        return false;
    }
}
?>