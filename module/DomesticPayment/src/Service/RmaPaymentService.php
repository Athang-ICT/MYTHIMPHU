<?php
declare(strict_types=1);

namespace DomesticPayment\Service;

use Exception;
use Laminas\Http\Client;
use Laminas\Http\Request;
use Laminas\Json\Json;
use Laminas\Log\LoggerInterface;

class RmaPaymentService
{
    private string $apiUrl;
    private string $jwtToken;
    private Client $httpClient;
    private ?LoggerInterface $logger;

    public function __construct(
        string $apiUrl,
        string $jwtToken,
        Client $httpClient,
        ?LoggerInterface $logger = null
    ) {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->jwtToken = $jwtToken;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * Step 1: Authorize Transaction
     * Performs transaction authorisation to check if merchant is valid
     *
     * @param string $merchantId Merchant ID
     * @param string $paymentDesc Payment Description
     * @param string $txnAmount Transaction Amount
     * @return array|null Authorization response with bfs_bfsTxnId
     * @throws Exception
     */
    public function authorizeTransaction(
        string $merchantId,
        string $paymentDesc,
        string $txnAmount
    ): ?array {
        $endpoint = '/domesticpg/authorise-request';
        
        $payload = [
            'bfs_paymentDesc' => 'mythimphu.com',
            'txnAmount' => $txnAmount,
        ];

        return $this->sendRequest($endpoint, $payload, 'Authorize Transaction');
    }

    /**
     * Step 2: Account Inquiry
     * Validates customer account and checks eligibility before debit
     *
     * @param string $remitterAccNo Remitter Account Number
     * @param string $remitterBankId Remitter Bank ID
     * @param string $bfsTxnId Transaction ID from authorization response
     * @return array|null Account inquiry response
     * @throws Exception
     */
    public function accountInquiry(
        string $remitterAccNo,
        string $remitterBankId,
        string $bfsTxnId
    ): ?array {
        $endpoint = '/domesticpg/account-inquiry';
        
        $payload = [
            'bfs_remitterAccNo' => $remitterAccNo,
            'bfs_remitterBankId' => $remitterBankId,
            'bfs_bfsTxnId' => $bfsTxnId,
        ];

        // Log exact payload for debugging
        error_log("=== Account Inquiry Request Payload ===");
        error_log("Account Number: " . $remitterAccNo);
        error_log("Bank ID: " . $remitterBankId);
        error_log("BFS Transaction ID: " . $bfsTxnId);
        error_log("Full Payload JSON: " . json_encode($payload));
        error_log("=======================================");

        return $this->sendRequest($endpoint, $payload, 'Account Inquiry');
    }

    /**
     * Step 3: Debit Transaction
     * Debits customer account after successful account inquiry
     *
     * @param string $remitterOtp OTP from customer
     * @param string $bfsTxnId Transaction ID from previous steps
     * @return array|null Debit response with transaction confirmation
     * @throws Exception
     */
    public function debitTransaction(
        string $remitterOtp,
        string $bfsTxnId
    ): ?array {
        $endpoint = '/domesticpg/debit-request';
        
        $payload = [
            'bfs_remitterOtp' => $remitterOtp,
            'bfs_bfsTxnId' => $bfsTxnId,
        ];

        return $this->sendRequest($endpoint, $payload, 'Debit Transaction');
    }

    /**
     * Send HTTP request to RMA API
     *
     * @param string $endpoint API endpoint
     * @param array $payload Request payload
     * @param string $action Action name for logging
     * @return array|null Response data or null on failure
     * @throws Exception
     */
    private function sendRequest(string $endpoint, array $payload, string $action): ?array
    {
        try {
            $url = $this->apiUrl . $endpoint;

            $this->httpClient->reset();
            $this->httpClient->setUri($url);
            $this->httpClient->setMethod(Request::METHOD_POST);
            
            // Set headers
            $headers = $this->httpClient->getRequest()->getHeaders();
            $headers->addHeaderLine('Content-Type', 'application/json');
            $headers->addHeaderLine('Accept', 'application/json');
            // Only add Authorization header if a token is provided
            if (!empty($this->jwtToken)) {
                $headers->addHeaderLine('Authorization', 'Bearer ' . $this->jwtToken);
            }

            // Set request body
            $this->httpClient->setRawBody(Json::encode($payload));

            // Debug: Log request details
            error_log("RMA API Request - $action");
            error_log("URL: $url");
            error_log("Payload: " . Json::encode($payload));
            error_log("JWT Token Length: " . strlen($this->jwtToken));

            $response = $this->httpClient->send();

            $statusCode = $response->getStatusCode();
            $body = $response->getBody();

            // Debug: Log response
            error_log("RMA API Response - $action");
            error_log("Status Code: $statusCode");
            error_log("Response Body: $body");

            // Log request details
            if ($this->logger) {
                $this->logger->info("RMA API $action - Status: $statusCode", [
                    'endpoint' => $endpoint,
                    'payload' => $payload,
                    'response' => $body,
                ]);
            }

            if ($statusCode === 200 || $statusCode === 201) {
                $decoded = Json::decode($body, Json::TYPE_ARRAY);
                return $decoded;
            } else {
                // Retry once on transient server errors
                if ($statusCode >= 500 && $statusCode < 600) {
                    if ($this->logger) {
                        $this->logger->warn("RMA API $action - transient error $statusCode, retrying once");
                    }
                    // simple retry once
                    $response = $this->httpClient->send();
                    $statusCode = $response->getStatusCode();
                    $body = $response->getBody();
                    error_log("RMA API Response (retry) - $action");
                    error_log("Status Code: $statusCode");
                    error_log("Response Body: $body");
                    if ($statusCode === 200 || $statusCode === 201) {
                        return Json::decode($body, Json::TYPE_ARRAY);
                    }
                }
                // Return error response instead of throwing
                $errorResponse = [
                    'status' => 'failed',
                    'message' => "API returned status $statusCode",
                    'error' => $body,
                    'statusCode' => $statusCode
                ];
                
                // Try to decode error body
                try {
                    $decodedError = Json::decode($body, Json::TYPE_ARRAY);
                    if (isset($decodedError['message'])) {
                        $errorResponse['message'] = $decodedError['message'];
                    }
                    $errorResponse['details'] = $decodedError;
                } catch (Exception $e) {
                    // Body is not JSON
                }
                
                return $errorResponse;
            }
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->err("RMA API $action error: " . $e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Get the JWT token
     */
    public function getToken(): string
    {
        return $this->jwtToken;
    }

    /**
     * Set a new JWT token (useful for token refresh)
     */
    public function setToken(string $token): self
    {
        $this->jwtToken = $token;
        return $this;
    }
}
