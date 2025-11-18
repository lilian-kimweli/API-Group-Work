<?php
// classes/helpers.php

class Helpers {
    
    // Get currency symbol and format
    public static function formatCurrency($amount, $currency = 'KES') {
        $symbols = [
            'KES' => 'KSh ',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£'
        ];
        
        $symbol = $symbols[$currency] ?? 'KSh ';
        
        // Format with 2 decimal places and thousands separator
        return $symbol . number_format($amount, 2);
    }
    
    // Get current currency from settings (you'll need to implement settings storage)
    public static function getCurrentCurrency() {
        // For now, return KES as default
        // Later, you can fetch this from your settings table
        return 'KES';
    }
}
?>