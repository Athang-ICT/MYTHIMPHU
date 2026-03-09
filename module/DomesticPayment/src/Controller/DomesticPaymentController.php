<?php
declare(strict_types=1);

namespace DomesticPayment\Controller;

use DomesticPayment\Service\RmaPaymentService;
use DomesticPayment\Model\PaymentTransaction;
use DomesticPayment\Model\PaymentTransactionTable;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use DateTime;
use Exception;

class DomesticPaymentController extends AbstractRestfulController
{
    private RmaPaymentService $rmaPaymentService;
    private PaymentTransactionTable $paymentTransactionTable;

    public function __construct(
        RmaPaymentService $rmaPaymentService,
        PaymentTransactionTable $paymentTransactionTable
    ) {
        $this->rmaPaymentService = $rmaPaymentService;
        $this->paymentTransactionTable = $paymentTransactionTable;
    }

    /**
     * Step 1: Initialize Payment - Authorization
     * POST /api/payment/authorize
     */
    public function authorizeAction()
    {
        if (!$this->getRequest()->isPost()) {
            return new JsonModel(['error' => 'POST request required']);
        }

        try {
            $data = $this->getRequest()->getContent();
            $params = json_decode($data, true);

            // Validate required parameters
            $required = ['merchantId', 'paymentDesc', 'txnAmount'];
            foreach ($required as $param) {
                if (empty($params[$param])) {
                    return new JsonModel([
                        'error' => "Missing required parameter: $param",
                        'status' => 'failed',
                    ], 400);
                }
            }

            // Call RMA API for authorization
            $authResponse = $this->rmaPaymentService->authorizeTransaction(
                $params['merchantId'],
                $params['paymentDesc'],
                $params['txnAmount']
            );

            if (!$authResponse || !isset($authResponse['status']) || $authResponse['status'] === 'FAILED') {
                $errorMessage = $authResponse['message'] ?? $authResponse['error'] ?? 'Authorization failed';
                return new JsonModel([
                    'error' => $errorMessage,
                    'status' => 'failed',
                    'response' => $authResponse,
                ], 400);
            }

            // Store transaction in database
            $transaction = new PaymentTransaction();
            $transaction->setMerchantId($params['merchantId'])
                ->setPaymentDesc($params['paymentDesc'])
                ->setTxnAmount($params['txnAmount'])
                ->setBfsTxnId($authResponse['bfs_bfsTxnId'])
                ->setStatus('authorized')
                ->setResponseCode($authResponse['bfs_responseCode'] ?? '')
                ->setResponseMessage($authResponse['message'] ?? '')
                ->setRemitterAccNo('')
                ->setRemitterBankId('')
                ->setRemitterOtp('')
                ->setCreatedAt(new DateTime())
                ->setUpdatedAt(new DateTime());

            $txnId = $this->paymentTransactionTable->savePaymentTransaction($transaction);

            return new JsonModel([
                'status' => 'success',
                'message' => 'Transaction authorized successfully',
                'txnId' => $txnId,
                'bfsTxnId' => $authResponse['bfs_bfsTxnId'],
            ]);

        } catch (Exception $e) {
            return new JsonModel([
                'error' => $e->getMessage(),
                'status' => 'failed',
            ], 500);
        }
    }

    /**
     * Step 2: Account Inquiry
     * POST /api/payment/inquire
     */
    public function inquireAction()
    {
        if (!$this->getRequest()->isPost()) {
            return new JsonModel(['error' => 'POST request required']);
        }

        try {
            $data = $this->getRequest()->getContent();
            $params = json_decode($data, true);

            // Validate required parameters
            $required = ['remitterAccNo', 'remitterBankId', 'bfsTxnId'];
            foreach ($required as $param) {
                if (empty($params[$param])) {
                    return new JsonModel([
                        'error' => "Missing required parameter: $param",
                        'status' => 'failed',
                    ], 400);
                }
            }

            // Get existing transaction
            $transaction = $this->paymentTransactionTable->getByBfsTxnId($params['bfsTxnId']);
            if (!$transaction) {
                return new JsonModel([
                    'error' => 'Transaction not found',
                    'status' => 'failed',
                ], 404);
            }

            // Call RMA API for account inquiry
            $inquiryResponse = $this->rmaPaymentService->accountInquiry(
                $params['remitterAccNo'],
                $params['remitterBankId'],
                $params['bfsTxnId']
            );

            if (!$inquiryResponse || !isset($inquiryResponse['status']) || $inquiryResponse['status'] === 'FAILED') {
                return new JsonModel([
                    'error' => $inquiryResponse['message'] ?? 'Account inquiry failed',
                    'status' => 'failed',
                    'response' => $inquiryResponse,
                ], 400);
            }

            // Update transaction
            $transaction->setRemitterAccNo($params['remitterAccNo'])
                ->setRemitterBankId($params['remitterBankId'])
                ->setStatus('inquired')
                ->setResponseCode($inquiryResponse['bfs_responseCode'] ?? '')
                ->setResponseMessage($inquiryResponse['message'] ?? '')
                ->setUpdatedAt(new DateTime());

            $this->paymentTransactionTable->savePaymentTransaction($transaction);

            return new JsonModel([
                'status' => 'success',
                'message' => 'Account inquiry successful',
                'bfsTxnId' => $params['bfsTxnId'],
            ]);

        } catch (Exception $e) {
            return new JsonModel([
                'error' => $e->getMessage(),
                'status' => 'failed',
            ], 500);
        }
    }

    /**
     * Step 3: Process Debit
     * POST /api/payment/debit
     */
    public function debitAction()
    {
        if (!$this->getRequest()->isPost()) {
            return new JsonModel(['error' => 'POST request required']);
        }

        try {
            $data = $this->getRequest()->getContent();
            $params = json_decode($data, true);

            // Validate required parameters
            $required = ['remitterOtp', 'bfsTxnId'];
            foreach ($required as $param) {
                if (empty($params[$param])) {
                    return new JsonModel([
                        'error' => "Missing required parameter: $param",
                        'status' => 'failed',
                    ], 400);
                }
            }

            // Get existing transaction
            $transaction = $this->paymentTransactionTable->getByBfsTxnId($params['bfsTxnId']);
            if (!$transaction) {
                return new JsonModel([
                    'error' => 'Transaction not found',
                    'status' => 'failed',
                ], 404);
            }

            // Call RMA API for debit
            $debitResponse = $this->rmaPaymentService->debitTransaction(
                $params['remitterOtp'],
                $params['bfsTxnId']
            );

            if (!$debitResponse || !isset($debitResponse['status']) || $debitResponse['status'] === 'FAILED') {
                // Update transaction with failure
                $transaction->setStatus('failed')
                    ->setResponseCode($debitResponse['bfs_responseCode'] ?? '')
                    ->setResponseMessage($debitResponse['message'] ?? 'Debit failed')
                    ->setUpdatedAt(new DateTime());

                $this->paymentTransactionTable->savePaymentTransaction($transaction);

                return new JsonModel([
                    'error' => $debitResponse['message'] ?? 'Debit transaction failed',
                    'status' => 'failed',
                    'response' => $debitResponse,
                ], 400);
            }

            // Update transaction with success
            $transaction->setRemitterOtp($params['remitterOtp'])
                ->setStatus('completed')
                ->setResponseCode($debitResponse['bfs_responseCode'] ?? '')
                ->setResponseMessage($debitResponse['message'] ?? 'Success')
                ->setUpdatedAt(new DateTime());

            $this->paymentTransactionTable->savePaymentTransaction($transaction);

            return new JsonModel([
                'status' => 'success',
                'message' => 'Payment processed successfully',
                'bfsTxnId' => $params['bfsTxnId'],
                'txnId' => $debitResponse['txnId'] ?? null,
            ]);

        } catch (Exception $e) {
            return new JsonModel([
                'error' => $e->getMessage(),
                'status' => 'failed',
            ], 500);
        }
    }

    /**
     * Get Transaction Status
     * GET /api/payment/status/:id
     */
    public function statusAction()
    {
        try {
            $bfsTxnId = $this->params('id');
            if (!$bfsTxnId) {
                return new JsonModel(['error' => 'Transaction ID required'], 400);
            }

            $transaction = $this->paymentTransactionTable->getByBfsTxnId($bfsTxnId);
            if (!$transaction) {
                return new JsonModel(['error' => 'Transaction not found'], 404);
            }

            return new JsonModel([
                'status' => 'success',
                'data' => $transaction->toArray(),
            ]);

        } catch (Exception $e) {
            return new JsonModel([
                'error' => $e->getMessage(),
                'status' => 'failed',
            ], 500);
        }
    }

    /**
     * List all transactions
     * GET /api/payment
     */
    public function getListAction()
    {
        try {
            $transactions = $this->paymentTransactionTable->fetchAll();
            $data = [];
            foreach ($transactions as $txn) {
                $data[] = $txn->toArray();
            }

            return new JsonModel([
                'status' => 'success',
                'total' => count($data),
                'data' => $data,
            ]);

        } catch (Exception $e) {
            return new JsonModel([
                'error' => $e->getMessage(),
                'status' => 'failed',
            ], 500);
        }
    }
    
    /**
     * Test RMA API Configuration
     * GET /payment/test-config
     */
    public function testConfigAction()
    {
        try {
            $config = $this->getEvent()->getApplication()->getServiceManager()->get('config');
            $rmaConfig = $config['rma_api'] ?? [];
            
            return new JsonModel([
                'status' => 'success',
                'config' => [
                    'base_url' => $rmaConfig['base_url'] ?? 'NOT SET',
                    'jwt_configured' => !empty($rmaConfig['jwt_secret']),
                    'jwt_length' => isset($rmaConfig['jwt_secret']) ? strlen($rmaConfig['jwt_secret']) : 0,
                ],
            ]);
        } catch (Exception $e) {
            return new JsonModel([
                'error' => $e->getMessage(),
                'status' => 'failed',
            ], 500);
        }
    }
}
