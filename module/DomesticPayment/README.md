<?php
/**
 * RMA Domestic Payment API Integration - Quick Start Guide
 * 
 * This guide helps you get started with the RMA Domestic Payment API integration
 */

# RMA Domestic Payment API Integration - Setup Guide

## Overview
This module integrates the RMA (Royal Monetary Authority) Domestic Payment API into your Laminas application.

## Features
- Step 1: Transaction Authorization
- Step 2: Account Inquiry
- Step 3: Debit Processing
- Transaction tracking and status management
- JWT authentication with RMA API

## Installation Steps

### 1. Enable the Module
Add `DomesticPayment` to your `config/modules.config.php`:

```php
return [
    'Laminas\Mvc\Plugin\FlashMessenger',
    'Laminas\Mvc\Plugin\Identity',
    'Laminas\Mvc\Plugin\Prg',
    'Laminas\Navigation',
    'Laminas\Paginator',
    'Acl',
    'Administration',
    'Auth',
    'Application',
    'News',
    'DomesticPayment',  // Add this line
];
```

### 2. Create Database Table
Execute the SQL from `module/DomesticPayment/sql/payment_transactions.sql`:

```sql
CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `merchant_id` VARCHAR(50) NOT NULL,
  `payment_desc` VARCHAR(255) NOT NULL,
  `txn_amount` DECIMAL(15, 2) NOT NULL,
  `remitter_acc_no` VARCHAR(50),
  `remitter_bank_id` VARCHAR(50),
  `bfs_txn_id` VARCHAR(100) UNIQUE NOT NULL,
  `status` ENUM('pending', 'authorized', 'inquired', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
  `remitter_otp` VARCHAR(10),
  `response_code` VARCHAR(20),
  `response_message` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `notes` TEXT,
  KEY `idx_bfs_txn_id` (`bfs_txn_id`),
  KEY `idx_merchant_id` (`merchant_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3. Configure API Credentials
Add configuration to `config/autoload/local.php`:

```php
return [
    // ... existing config ...
    
    'rma_payment' => [
        'api_url' => 'http://141.148.209.255:8083/api/v1',
        'jwt_token' => 'YOUR_JWT_TOKEN_HERE',  // Obtain from RMA /auth/login
        'merchant_id' => 'YOUR_MERCHANT_ID',
    ],
];
```

### 4. Get JWT Token
To get a JWT token, call the RMA authentication endpoint:

```bash
curl -X POST http://141.148.209.255:8083/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "your-email@example.com",
    "password": "your-password"
  }'
```

Response will contain a `token` field.

## API Endpoints

### 1. Authorize Transaction (Step 1)
**POST** `/api/payment/authorize`

Request body:
```json
{
  "merchantId": "MERCHANT001",
  "paymentDesc": "Bill Payment for Invoice #123",
  "txnAmount": "1000.00"
}
```

Response:
```json
{
  "status": "success",
  "message": "Transaction authorized successfully",
  "txnId": 1,
  "bfsTxnId": "TXN20231225001"
}
```

### 2. Account Inquiry (Step 2)
**POST** `/api/payment/inquire`

Request body:
```json
{
  "remitterAccNo": "1234567890",
  "remitterBankId": "BNB001",
  "bfsTxnId": "TXN20231225001"
}
```

Response:
```json
{
  "status": "success",
  "message": "Account inquiry successful",
  "bfsTxnId": "TXN20231225001"
}
```

### 3. Debit Transaction (Step 3)
**POST** `/api/payment/debit`

Request body:
```json
{
  "remitterOtp": "123456",
  "bfsTxnId": "TXN20231225001"
}
```

Response:
```json
{
  "status": "success",
  "message": "Payment processed successfully",
  "bfsTxnId": "TXN20231225001",
  "txnId": "TX123456"
}
```

### 4. Get Transaction Status
**GET** `/api/payment/status/TXN20231225001`

Response:
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "merchantId": "MERCHANT001",
    "paymentDesc": "Bill Payment for Invoice #123",
    "txnAmount": "1000.00",
    "status": "completed",
    "bfsTxnId": "TXN20231225001",
    "responseMessage": "Success",
    "createdAt": "2023-12-25 10:30:00",
    "updatedAt": "2023-12-25 10:35:00"
  }
}
```

### 5. List All Transactions
**GET** `/api/payment`

Response:
```json
{
  "status": "success",
  "total": 5,
  "data": [
    { /* transaction objects */ }
  ]
}
```

## Usage Example

### PHP Client Example
```php
<?php
// Get the service from container
$rmaPaymentService = $container->get('DomesticPayment\Service\RmaPaymentService');

// Step 1: Authorize
$authResponse = $rmaPaymentService->authorizeTransaction(
    'MERCHANT001',
    'Payment for Service',
    '5000.00'
);
$bfsTxnId = $authResponse['bfs_bfsTxnId'];

// Step 2: Account Inquiry
$inquiryResponse = $rmaPaymentService->accountInquiry(
    '1234567890',
    'BNB001',
    $bfsTxnId
);

// Step 3: Debit
$debitResponse = $rmaPaymentService->debitTransaction(
    '123456',  // OTP
    $bfsTxnId
);

if ($debitResponse['status'] === 'success') {
    echo "Payment successful!";
}
?>
```

### JavaScript Frontend Example
```javascript
// Step 1: Authorize
fetch('/api/payment/authorize', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    merchantId: 'MERCHANT001',
    paymentDesc: 'Payment Description',
    txnAmount: '1000.00'
  })
})
.then(res => res.json())
.then(data => {
  console.log('Authorized:', data.bfsTxnId);
  const bfsTxnId = data.bfsTxnId;
  
  // Step 2: Account Inquiry
  return fetch('/api/payment/inquire', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      remitterAccNo: '1234567890',
      remitterBankId: 'BNB001',
      bfsTxnId: bfsTxnId
    })
  });
})
.then(res => res.json())
.then(data => {
  console.log('Inquiry complete:', data.message);
  
  // Step 3: Get OTP from user and debit
  const otp = prompt('Enter OTP:');
  return fetch('/api/payment/debit', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      remitterOtp: otp,
      bfsTxnId: data.bfsTxnId
    })
  });
})
.then(res => res.json())
.then(data => {
  if (data.status === 'success') {
    console.log('Payment successful!', data);
  } else {
    console.error('Payment failed:', data.error);
  }
});
```

## Transaction Statuses

- **pending**: Transaction created but not yet authorized
- **authorized**: Step 1 completed - merchant authorized
- **inquired**: Step 2 completed - account validated
- **completed**: Step 3 completed - payment successful
- **failed**: Payment failed at any step
- **cancelled**: Transaction cancelled by user or system

## Security Notes

1. Store JWT tokens securely
2. Use HTTPS for all API communications
3. Validate all user inputs
4. Implement rate limiting for API endpoints
5. Log all payment transactions
6. Never expose sensitive data in logs

## Troubleshooting

### JWT Token Expired
Get a new token by calling `/auth/login` again.

### Authorization Failed
Check:
- Merchant ID is correct
- JWT token is valid
- API endpoint URL is reachable

### Account Inquiry Failed
Check:
- Remitter account number is valid
- Bank ID matches the account
- Account has sufficient balance

## Support
For API documentation and support, visit: http://141.148.209.255:8083/api/v1/swagger-ui/index.html
