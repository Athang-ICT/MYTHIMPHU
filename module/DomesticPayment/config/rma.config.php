<?php
/**
 * RMA Domestic Payment API Integration Configuration
 * 
 * Add this configuration to your config/autoload/local.php or global configuration
 */

return [
    'rma_payment' => [
        // RMA API endpoint URL
        'api_url' => 'https://apipg.athang.com:8080/api/v1',
        
        // JWT Token for API authentication
        // You need to obtain this from RMA by calling /auth/login endpoint
        'jwt_token' => env('RMA_JWT_TOKEN', ''),
        
        // Merchant ID assigned by RMA
        'merchant_id' => env('RMA_MERCHANT_ID', ''),
    ],
];
