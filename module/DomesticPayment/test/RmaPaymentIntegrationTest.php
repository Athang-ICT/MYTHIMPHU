<?php
declare(strict_types=1);

namespace DomesticPayment\Test;

use DomesticPayment\Service\RmaPaymentService;
use Laminas\Http\Client;

/**
 * RMA Domestic Payment API Integration Test
 * 
 * Usage:
 * 1. Copy this file to your test directory
 * 2. Update credentials below
 * 3. Run the tests
 */

class RmaPaymentIntegrationTest
{
    private string $apiUrl = 'http://141.148.209.255:8083/api/v1';
    private string $email = 'test@example.com';
    private string $password = 'password';
    private string $merchantId = 'MERCHANT001';
    private ?string $jwtToken = null;

    public function run(): void
    {
        echo "=== RMA Domestic Payment API Integration Test ===\n\n";

        // Test 1: Get JWT Token
        if (!$this->testAuthentication()) {
            echo "Failed to authenticate. Please check your credentials.\n";
            return;
        }

        // Test 2: Authorize Transaction
        $authResult = $this->testAuthorization();
        if (!$authResult) {
            echo "Authorization test failed.\n";
            return;
        }

        echo "\n✓ All tests passed!\n";
    }

    private function testAuthentication(): bool
    {
        echo "Test 1: Authentication\n";
        echo "-----------------------\n";

        try {
            $client = new Client();
            $client->setUri($this->apiUrl . '/auth/login');
            $client->setMethod('POST');
            $client->getRequest()->getHeaders()
                ->addHeaderLine('Content-Type', 'application/json');

            $payload = json_encode([
                'email' => $this->email,
                'password' => $this->password,
            ]);

            $client->setRawBody($payload);
            $response = $client->send();

            $body = json_decode($response->getBody(), true);

            if ($response->getStatusCode() === 200 && isset($body['token'])) {
                $this->jwtToken = $body['token'];
                echo "✓ Authentication successful\n";
                echo "  Token: " . substr($this->jwtToken, 0, 50) . "...\n\n";
                return true;
            } else {
                echo "✗ Authentication failed\n";
                echo "  Status: " . $response->getStatusCode() . "\n";
                echo "  Response: " . $response->getBody() . "\n\n";
                return false;
            }
        } catch (\Exception $e) {
            echo "✗ Error: " . $e->getMessage() . "\n\n";
            return false;
        }
    }

    private function testAuthorization(): bool
    {
        echo "Test 2: Authorization\n";
        echo "---------------------\n";

        if (!$this->jwtToken) {
            echo "✗ No JWT token available\n\n";
            return false;
        }

        try {
            $client = new Client();
            $client->setUri($this->apiUrl . '/payment/authorise-request');
            $client->setMethod('POST');
            $client->getRequest()->getHeaders()
                ->addHeaderLine('Content-Type', 'application/json')
                ->addHeaderLine('Authorization', 'Bearer ' . $this->jwtToken);

            $payload = json_encode([
                'bfs_merchantId' => $this->merchantId,
                'bfs_paymentDesc' => 'Test Payment',
                'txnAmount' => '1000.00',
            ]);

            $client->setRawBody($payload);
            $response = $client->send();

            $body = json_decode($response->getBody(), true);

            if ($response->getStatusCode() === 200) {
                echo "✓ Authorization successful\n";
                echo "  BFS Transaction ID: " . ($body['bfs_bfsTxnId'] ?? 'N/A') . "\n";
                echo "  Response Code: " . ($body['bfs_responseCode'] ?? 'N/A') . "\n";
                echo "  Message: " . ($body['message'] ?? 'N/A') . "\n\n";
                return true;
            } else {
                echo "✗ Authorization failed\n";
                echo "  Status: " . $response->getStatusCode() . "\n";
                echo "  Response: " . $response->getBody() . "\n\n";
                return false;
            }
        } catch (\Exception $e) {
            echo "✗ Error: " . $e->getMessage() . "\n\n";
            return false;
        }
    }

    /**
     * Get the JWT token for use in configuration
     */
    public function getToken(): ?string
    {
        return $this->jwtToken;
    }

    /**
     * Set credentials before running tests
     */
    public function setCredentials(
        string $email,
        string $password,
        string $merchantId,
        ?string $apiUrl = null
    ): void {
        $this->email = $email;
        $this->password = $password;
        $this->merchantId = $merchantId;
        if ($apiUrl) {
            $this->apiUrl = $apiUrl;
        }
    }
}

// Run tests
if (php_sapi_name() === 'cli') {
    $tester = new RmaPaymentIntegrationTest();
    
    // TODO: Update with your credentials
    $tester->setCredentials(
        'your-email@example.com',
        'your-password',
        'YOUR_MERCHANT_ID'
    );
    
    $tester->run();
}
