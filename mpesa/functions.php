<?php
// functions.php - M-Pesa Helper Functions


define('CONSUMER_KEY', 'GmcimGFcOMZ1cmGaru6N7RsVFZDM4gQCn3qfrVwzAQs3kzt3'); 
define('CONSUMER_SECRET', 'FrexIZAdN61m7uaQjEqVMVa9WKkAwOwwnXdajgSBwW19L7AIBgEhDiVViakV6AwH'); // Your consumer secret  
define('SHORTCODE', '174379'); // Sandbox shortcode
define('PASSKEY', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919');
define('CALLBACK_URL', 'https://localhost/bluebell/mpesa/callback.php'); 

/**
 * Get Access Token from M-Pesa API
 */
function getAccessToken() {
    $url = "https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials";
    
    $credentials = base64_encode(CONSUMER_KEY . ":" . CONSUMER_SECRET);
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        "Authorization: Basic " . $credentials,
        "Content-Type: application/json"
    ));
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($curl);
    $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($status_code != 200) {
        return array('error' => 'Failed to get access token. Status: ' . $status_code);
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['access_token'])) {
        return $data['access_token'];
    } else {
        return array('error' => 'No access token in response: ' . $response);
    }
}

/**
 * Initiate STK Push (Lipa Na M-Pesa)
 */
function initiateSTKPush($phone, $amount, $accountReference = "Test Payment", $transactionDesc = "Payment for services") {
    // Clean phone number (remove spaces, ensure 254 format)
    $phone = cleanPhoneNumber($phone);
    
    // Get access token
    $access_token = getAccessToken();
    
    if (isset($access_token['error'])) {
        return array('error' => $access_token['error']);
    }
    
    // Generate timestamp and password
    $timestamp = date("YmdHis");
    $password = base64_encode(SHORTCODE . PASSKEY . $timestamp);
    
    // STK Push payload
    $curl_post_data = array(
        'BusinessShortCode' => SHORTCODE,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $amount,
        'PartyA' => $phone,
        'PartyB' => SHORTCODE,
        'PhoneNumber' => $phone,
        'CallBackURL' => CALLBACK_URL,
        'AccountReference' => $accountReference,
        'TransactionDesc' => $transactionDesc
    );
    
    $url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ));
    
    $data_string = json_encode($curl_post_data);
    
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($curl);
    $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    return json_decode($response, true);
}

/**
 * Clean and format phone number
 */
function cleanPhoneNumber($phone) {
    // Remove any non-digit characters
    $phone = preg_replace('/\D/', '', $phone);
    
    // Convert to 254 format if it starts with 0 or 7
    if (substr($phone, 0, 1) == '0') {
        $phone = '254' . substr($phone, 1);
    } elseif (substr($phone, 0, 1) == '7' && strlen($phone) == 9) {
        $phone = '254' . $phone;
    }
    
    return $phone;
}

/**
 * Log M-Pesa transactions
 */
function logTransaction($data, $filename = 'mpesa_transactions.log') {
    $log_entry = "[" . date('Y-m-d H:i:s') . "] " . json_encode($data) . "\n";
    file_put_contents($filename, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Check if STK Push was successful
 */
function isSTKPushSuccessful($response) {
    return (isset($response['ResponseCode']) && $response['ResponseCode'] == '0');
}

/**
 * Get error message from STK Push response
 */
function getSTKPushErrorMessage($response) {
    if (isset($response['errorMessage'])) {
        return $response['errorMessage'];
    } elseif (isset($response['ResponseDescription'])) {
        return $response['ResponseDescription'];
    } else {
        return 'Unknown error occurred';
    }
}
?>