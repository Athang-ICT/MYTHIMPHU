<?php
namespace Auth\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Interop\Container\ContainerInterface;
use Laminas\Authentication\AuthenticationService;
use Laminas\Db\Adapter\Adapter as DbAdapter;
use Laminas\Authentication\Adapter\DbTable\CredentialTreatmentAdapter as AuthAdapter;
use Laminas\Session\Container;
use Laminas\Authentication\Result;
use Laminas\Mvc\MvcEvent;
use Auth\Form\AuthForm;
use Administration\Model as Administration;
use News\Model as News;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\Smtp;
use Laminas\Mail\Transport\SmtpOptions;
use DomesticPayment\Service\RmaPaymentService;
use DomesticPayment\Model\PaymentTransactionTable;
use DateTime;
use Laminas\Json\Json;
use Laminas\Http\Client;
use Laminas\Http\Request;
use Auth\Service\SSOService;
use Laminas\Session\SessionManager;
use Administration\Model\UsersTable;

class AuthController extends AbstractActionController
{
	private $container;
    private $dbAdapter;
    protected $_password;
    private $rmaPaymentService;
    private $paymentTransactionTable;
    private $ssoService;
    private $session;
    private $config;
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->dbAdapter = $this->container->get(DbAdapter::class);
        $this->ssoService = new SSOService($container->get('config'));
        $this->session = $this->ssoService->getSession();
        $this->config = $container->get('config');
        if (isset($this->session->config)) {
            $this->config = $this->session->config;
        } else {
            $this->config = $this->ssoService->initializeConfig($this->config);
        }
    }

    public function getDefinedTable($table)
    {
        $definedTable = $this->container->get($table);
        return $definedTable;
    }
    public function indexAction()
    {   
        $auth = new AuthenticationService();
		if($auth->hasIdentity()):
			return $this->redirect()->toRoute('home');
		else:
			//return $this->redirect()->toRoute('auth', array('action' =>'login'));
            return $this->redirect()->toRoute('application', array('action' =>'portal'));
		endif;
		
        return new ViewModel([
        	'title' => 'Login'
        ]);
    }
    /** 
     * Login 
     */
    public function loginAction()
    {
		$messages = null;
		$auth = new AuthenticationService();
        if($auth->hasIdentity() && $this->params()->fromRoute('id') != "NoKeepAlive"):
			 return $this->redirect()->toRoute('home');
        endif;
        // Check if this is an SSO authorization request (OIDC parameters in URL)
        $clientId = $this->params()->fromQuery('client_id');
        if ($clientId) {
            // If SSO parameters are present in the query string, redirect to SSO login
            $sso_login_url = $this->ssoService->getSSOLoginURL();
            if (!empty($sso_login_url)) {
                return $this->redirect()->toUrl($sso_login_url);
            }
        }
        if ($this->getRequest()->isPost()) 
		{
			$data = $this->getRequest()->getPost();
            $staticSalt = $this->password()->getStaticSalt();// Get Static Salt using Password Plugin
            if(filter_var($data['username'], FILTER_VALIDATE_EMAIL)):
                $identitycolumn = "email";
            else:
                $identitycolumn = "mobile";
            endif;
            $authAdapter = new AuthAdapter($this->dbAdapter,
                                           'sys_users', // there is a method setTableName to do the same
                                           $identitycolumn, // there is a method setIdentityColumn to do the same
                                           'password', // there is a method setCredentialColumn to do the same
                                           "SHA1(CONCAT('$staticSalt', ?, salt))" // setCredentialTreatment(parametrized string) 'MD5(?)'
                                          );            
            $authAdapter
                    ->setIdentity($data['username'])
                    ->setCredential($data['password'])
                ;
            $authService = new AuthenticationService();
            $result = $authService->authenticate($authAdapter);
            //echo"<pre>"; print_r($result); exit;
            switch ($result->getCode()) 
			{
                case Result::FAILURE_IDENTITY_NOT_FOUND:
                    // nonexistent identity
                    $this->flashMessenger()->addMessage("error^ A record with the supplied identity (username) could not be found.");
                    break;

                case Result::FAILURE_CREDENTIAL_INVALID:
                    // invalid credential
                    $this->flashMessenger()->addMessage("info^ Please check Caps Lock key is activated on your computer.");
                    $this->flashMessenger()->addMessage("error^ Supplied credential (password) is invalid, Please try again.");
                    break;

                case Result::SUCCESS:
                    $storage = $authService->getStorage();
                    $storage->write($authAdapter->getResultRowObject());
                    $role = $this->identity()->role;
                    $time = 1209600; // 14 days 1209600/3600 = 336 hours => 336/24 = 14 days
                    if ($data['rememberme']) {
                        $sessionManager = new \Laminas\Session\SessionManager();
                        $sessionManager->rememberMe($time);
                    }
                    $id = $this->identity()->id; 
                    $login = $this->getDefinedTable(Administration\UsersTable::class)->getColumn($id, $column='logins');
                    
                    $data = array(
                            'id'         => $id,
                            'last_login' => date('Y-m-d H:i:s'),
                            'last_accessed_ip' => !empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : ( !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'] ),
                            'logins' => $login + 1
                    ); 
                    $this->getDefinedTable(Administration\UsersTable::class)->save($data);
					//check whether user is block
					$status = $this->getDefinedTable(Administration\UsersTable::class)->getColumn($id, $column='status');
					if($status == "9"){
					   return $this->redirect()->toRoute('auth', array('action' => 'logout', 'id'=>'1'));	
					}
                    $this->flashMessenger()->addMessage("info^ Welcome,</br>You have successfully logged in!");
                    return $this->redirect()->toRoute('home');
                break;
                default:
                    //other failure--- currently silent
                break;  
            }
            return $this->redirect()->toRoute('auth', array('action' => 'login'));
            
			if ( $this->params()->fromRoute('id') == "NoKeepAlive" ):
				$auth = new AuthenticationService();
				$auth->clearIdentity();
				$sessionManager = new \Laminas\Session\SessionManager();
				$sessionManager->forgetMe();
				$this->flashMessenger()->addMessage('warning^Your session has expired, please login again.');
			endif;
        }
        $sso_login_url = $this->ssoService->getSSOLoginURL();
        $ViewModel = new ViewModel(array(
			'title' => 'Log into System',
            'sso_login_url' => $sso_login_url
		));
		$ViewModel->setTerminal(false);
		return $ViewModel;
    }
    /**
     * registration Action with RMA Payment Verification
     * 
     * Flow:
     * 1. Display registration form with bank account fields
     * 2. User submits form with account details
     * 3. Authorize transaction with RMA
     * 4. User enters OTP
     * 5. Verify OTP and debit
     * 6. On success: Create user account and redirect to login
     * 7. On failure: Show error and return to form
     */
    public function registrationAction()
    {
        $request = $this->getRequest();
        $session = new Container('registration');
        // Persist newsId in session so it survives redirects between steps
        $newsId = $this->params()->fromQuery('id', $session->newsId ?? null);
        if ($newsId === null) {
            $newsId = $this->params()->fromRoute('id');
        }
        if ($newsId !== null) {
            $session->newsId = $newsId;
        }
        // Get step from POST if available, otherwise from query string
        if ($request->isPost()) {
            $data = $request->getPost();
            $step = $data['step'] ?? $this->params()->fromQuery('step', '1');
        } else {
            $step = $this->params()->fromQuery('step', '1');
        }
        if ($request->isPost()) {
            // Debug logging
            error_log("Registration POST - Step: " . $step);
            error_log("Post data step: " . ($data['step'] ?? 'not set'));
            error_log("Session bfsTxnId: " . ($session->bfsTxnId ?? 'not set'));

            // STEP 1: Initial Registration Form Submission
            if ($step == '1' || empty($session->bfsTxnId)) {
                error_log("Going to handlePaymentAuthorization");
                return $this->handlePaymentAuthorization($data, $newsId, $session);
            }
            
            // STEP 2: OTP Verification and Debit
            if ($step == '2' && !empty($session->bfsTxnId)) {
                error_log("Going to handlePaymentDebit");
                return $this->handlePaymentDebit($data, $session);
            }
        }
        
        $viewModel = new ViewModel([
            'title'         => 'Register New Account',
            'newsId'        => $session->newsId ?? $newsId,
            'step'          => $step,
            'districts'     => $this->getDefinedTable(Administration\DistrictTable::class)->getAll(),
            'session'       => $session,
            'banks'         => $this->getDefinedTable(Administration\BankTable::class)->getAll(),
        ]);
        return $viewModel;
    }

    /**
     * Handle Step 1: Payment Authorization
     * Validates user input and initiates RMA payment authorization
     */
    private function handlePaymentAuthorization($data, $newsId, Container $session)
    {
        try {
            // Validate registration data
            $validationError = $this->validateRegistrationData($data);
            if ($validationError) {
                $this->flashMessenger()->addMessage($validationError);
                return $this->redirect()->toRoute('auth', ['action' => 'registration', 'id' => $newsId]);
            }

            // Check if user already exists
            $existingUser = $this->getDefinedTable(Administration\UsersTable::class)
                             ->get(['cid' => $data['cid']]);
            if ($existingUser) {
                $this->flashMessenger()->addMessage("warning^You are already Registered. Please login.");
                return $this->redirect()->toRoute('auth', ['action' => 'login']);
            }

            // Authorize transaction with RMA
            $config = $this->container->get('config');
            $merchantId = $config['rma_api']['merchant_id'] ?? 'MERCHANT001';
            
            // Log payment request details
            error_log("Registration Payment Authorization Request:");
            error_log("Merchant ID: " . $merchantId);
            error_log("User: {$data['name']} ({$data['cid']})");
            error_log("Amount: 100.00");
            
            $paymentDesc = "Registration Payment - {$data['name']} ({$data['cid']})";
            $authResponse = $this->rmaPaymentService->authorizeTransaction(
                $merchantId,
                $paymentDesc,
                '1.00'  // Registration fee amount
            );

            // Log full response for debugging
            error_log("RMA Authorization Response: " . json_encode($authResponse));

            if (!$authResponse || ($authResponse['status'] ?? '') === 'FAILED') {
                $errorMsg = $authResponse['message'] ?? 'Payment authorization failed';
                $errorDetails = isset($authResponse['bfs_responseCode']) ? " (Code: {$authResponse['bfs_responseCode']})" : "";
                
                error_log("Payment authorization FAILED: {$errorMsg}{$errorDetails}");
                
                $this->flashMessenger()->addMessage("error^Payment authorization failed: {$errorMsg}{$errorDetails}. Please verify the merchant ID is correct in the configuration.");
                return $this->redirect()->toRoute('auth', ['action' => 'registration']);
            }

            // Store transaction info in session
            $session->bfsTxnId = $authResponse['bfs_bfsTxnId'];
            $session->paymentDesc = $paymentDesc;
            $session->regData = [
                'name'       => $data['name'],
                'cid'        => $data['cid'],
                'email'      => $data['email'],
                'phone'      => $data['phone'],
                'dzongkhag'  => $data['Dzongkhag'] ?? '',
                'accountNo'  => $data['account_number'],
                'bankId'     => $data['bank_id'],
                'newsId'     => $session->newsId ?? $newsId,
            ];

            // Save payment transaction to database
            error_log("=== PAYMENT TRANSACTION CID DEBUG ===");
            error_log("CID from form data: " . ($data['cid'] ?? 'NOT SET'));
            error_log("Full form data: " . json_encode($data->toArray()));
            
            $transaction = new \DomesticPayment\Model\PaymentTransaction();
            $transaction->setMerchantId($merchantId)
                ->setPaymentDesc($paymentDesc)
                ->setTxnAmount('1.00')
                ->setBfsTxnId($authResponse['bfs_bfsTxnId'])
                ->setStatus('authorized')
                ->setResponseCode($authResponse['bfs_responseCode'] ?? '')
                ->setResponseMessage($authResponse['message'] ?? '')
                ->setRemitterAccNo('')
                ->setRemitterBankId('')
                ->setRemitterOtp('')
                ->setRemitterCid($data['cid']) // Store user CID as identifier
                ->setCreatedAt(new DateTime())
                ->setUpdatedAt(new DateTime());

            error_log("CID being saved to transaction: " . $transaction->getRemitterCid());
            $txnId = $this->paymentTransactionTable->savePaymentTransaction($transaction);
            error_log("Transaction saved with ID: " . $txnId);
            $session->txnId = $txnId; // Store transaction ID in session
            
            error_log("Payment transaction saved with ID: " . $txnId);

            // Proceed to account inquiry (only show user-facing messages after inquiry)
            return $this->handleAccountInquiry($data, $session);

        } catch (\Exception $e) {
            $this->flashMessenger()->addMessage("error^Payment error: " . $e->getMessage());
            return $this->redirect()->toRoute('auth', ['action' => 'registration']);
        }
    }

    /**
     * Handle Account Inquiry (Step 2)
     */
    private function handleAccountInquiry($data, Container $session)
    {
        try {
            // Verify account with RMA
            $inquiryResponse = $this->rmaPaymentService->accountInquiry(
                $data['account_number'],
                $data['bank_id'],
                $session->bfsTxnId
            );

            error_log("Account Inquiry Full Response: " . json_encode($inquiryResponse));
            
            // Map API fields (supports snake_case and camelCase)
            $respCode = $inquiryResponse['bfs_responseCode']
                ?? $inquiryResponse['bfs_response_code']
                ?? $inquiryResponse['response_code']
                ?? null;
            $respDesc = $inquiryResponse['bfs_responseDesc']
                ?? $inquiryResponse['bfs_response_message']
                ?? $inquiryResponse['message']
                ?? null;
            $enquiryStatus = $inquiryResponse['account_enquiry_status']
                ?? $inquiryResponse['account_inquiry_status']
                ?? null;

            error_log("Account Inquiry - Code: {$respCode}, Desc: {$respDesc}, Status: {$enquiryStatus}");

            $codeOk = ($respCode === '00');
            $statusOk = ($enquiryStatus && stripos($enquiryStatus, 'SUCCESS') !== false);

            if (!$inquiryResponse || (!($codeOk || $statusOk))) {
                $displayMsg = $respDesc ?: ($inquiryResponse['message'] ?? 'Account verification failed');
                if ($respCode) {
                    $displayMsg .= " (Code: {$respCode})";
                }
                error_log("Account Inquiry BLOCKED - Account verification failed. Code: {$respCode}, Desc: {$respDesc}, Status: {$enquiryStatus}");
                $this->flashMessenger()->addMessage("error^Account verification failed: {$displayMsg}. Please verify your bank account details.");
                return $this->redirect()->toRoute('auth', ['action' => 'registration']);
            }

            error_log("Account Inquiry PASSED - Proceeding to OTP");

            // Update payment transaction with account details
            if (!empty($session->txnId)) {
                $transaction = $this->paymentTransactionTable->getPaymentTransaction($session->txnId);
                if ($transaction) {
                    $transaction->setRemitterAccNo($data['account_number'])
                        ->setRemitterBankId($data['bank_id'])
                        ->setStatus('inquired')
                        ->setResponseCode($respCode ?? '')
                        ->setResponseMessage(($respDesc ?: $enquiryStatus) ?? '')
                        ->setUpdatedAt(new DateTime());
                    
                    $this->paymentTransactionTable->savePaymentTransaction($transaction);
                    error_log("Payment transaction updated after inquiry - Status: inquired");
                }
            }

            $this->flashMessenger()->addMessage("success^Account verified! Please check your phone for OTP.");
            
            // Redirect to OTP verification step
            return $this->redirect()->toRoute('auth', [
                'action' => 'registration',
                'id' => $session->regData['newsId']
            ], ['query' => ['step' => '2']]);

        } catch (\Exception $e) {
            $this->flashMessenger()->addMessage("error^Account inquiry error: " . $e->getMessage());
            return $this->redirect()->toRoute('auth', ['action' => 'registration']);
        }
    }

    /**
     * Handle Step 3: Payment Debit with OTP
     * Completes payment and creates user account if successful
     */
    private function handlePaymentDebit($data, Container $session)
    {
        try {
            if (empty($session->bfsTxnId)) {
                $this->flashMessenger()->addMessage("error^Session expired. Please start registration again.");
                return $this->redirect()->toRoute('auth', ['action' => 'registration']);
            }

            if (empty($data['otp'])) {
                $this->flashMessenger()->addMessage("error^OTP is required.");
                return $this->redirect()->toRoute('auth', [
                    'action' => 'registration',
                    'id' => $session->newsId ?? ($session->regData['newsId'] ?? null)
                ], ['query' => ['step' => '2']]);
            }

            // Process debit with OTP
            error_log("Processing debit - OTP: " . $data['otp'] . ", TxnId: " . $session->bfsTxnId);
            
            $debitResponse = $this->rmaPaymentService->debitTransaction(
                $data['otp'],
                $session->bfsTxnId
            );

            error_log("Debit Response: " . json_encode($debitResponse));

            // Map fields (snake_case vs camelCase) and determine success
            $debitRespCode = $debitResponse['debit_response_code']
                ?? $debitResponse['bfs_responseCode']
                ?? $debitResponse['bfs_response_code']
                ?? null;
            $debitRespMsg = $debitResponse['bfs_responseMessage']
                ?? $debitResponse['bfs_response_message']
                ?? $debitResponse['bfs_responseDesc']
                ?? $debitResponse['message']
                ?? null;
            $accountDebitStatus = $debitResponse['account_debit_status'] ?? null;
            $debitStatus = isset($debitResponse['status']) ? strtoupper((string)$debitResponse['status']) : null;
            $debitOk = ($debitRespCode === '00')
                || ($debitStatus === 'SUCCESS')
                || ($accountDebitStatus && stripos($accountDebitStatus, 'success') !== false);

            if (!$debitResponse || !$debitOk) {
                $errorMsg = $debitRespMsg ?? $accountDebitStatus ?? ($debitResponse['message'] ?? 'Payment failed');
                $errorCode = $debitRespCode ?? '';
                
                error_log("Debit FAILED: {$errorMsg} (Code: {$errorCode})");
                
                $displayMsg = $errorMsg;
                if ($errorCode) {
                    $displayMsg .= " (Error Code: {$errorCode})";
                }
                
                $this->flashMessenger()->addMessage("error^Payment failed: {$displayMsg}");
                return $this->redirect()->toRoute('auth', [
                    'action' => 'registration',
                    'id' => $session->newsId ?? ($session->regData['newsId'] ?? null)
                ], ['query' => ['step' => '2']]);
            }
            
            error_log("Debit SUCCESS! TxnId: " . ($debitResponse['txnId'] ?? 'N/A'));

            // Update payment transaction with final status
            if (!empty($session->txnId)) {
                $transaction = $this->paymentTransactionTable->getPaymentTransaction($session->txnId);
                if ($transaction) {
                    $transaction->setRemitterOtp($data['otp'])
                        ->setStatus('completed')
                        ->setResponseCode($debitRespCode ?? '')
                        ->setResponseMessage(($debitRespMsg ?? $accountDebitStatus ?? 'Success'))
                        ->setUpdatedAt(new DateTime());
                    
                    $this->paymentTransactionTable->savePaymentTransaction($transaction);
                    error_log("Payment transaction completed and saved");
                }
            }

            // Payment successful! Now create user account
            return $this->completeRegistration($session);

        } catch (\Exception $e) {
            $this->flashMessenger()->addMessage("error^Payment debit error: " . $e->getMessage());
            return $this->redirect()->toRoute('auth', ['action' => 'registration']);
        }
    }

    /**
     * Complete Registration: Create user account after successful payment
     */
    private function completeRegistration(Container $session)
    {
        try {
            $regData = $session->regData;
            if (empty($regData['newsId']) && isset($session->newsId)) {
                $regData['newsId'] = $session->newsId;
                $session->regData = $regData; // keep session in sync
            }
            
            error_log("=== COMPLETE REGISTRATION DEBUG ===");
            error_log("User data: " . json_encode($regData));

            // Generate a random password
            $staticSalt = $this->password()->getStaticSalt();
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $randomPassword = '';
            for ($i = 0; $i < 8; $i++) {
                $randomPassword .= $characters[random_int(0, strlen($characters) - 1)];
            }
            
            $dynamicSalt = bin2hex(random_bytes(4));
            $hashedPassword = sha1($staticSalt . $randomPassword . $dynamicSalt);
            
            error_log("Generated password: " . $randomPassword);

            // Create user account
            $userData = [
                'name'         => $regData['name'],
                'cid'          => $regData['cid'],
                'email'        => $regData['email'],
                'mobile'       => $regData['phone'],
                'role'         => 2,
                'region'       => 1,
                'location'     => $regData['dzongkhag'],
                'password'     => $hashedPassword,
                'salt'         => $dynamicSalt,
                'status'       => 1,
                'created'      => date('Y-m-d H:i:s'),
                'modified'     => date('Y-m-d H:i:s')
            ];

            error_log("checking for news id..." . json_encode($regData));
            $userId = $this->getDefinedTable(Administration\UsersTable::class)->save($userData);
            error_log("User ID created: " . $userId);

            if ($userId > 0) {
                // Create subscription if newsId is provided
                if (!empty($regData['newsId'])) {
                    error_log("Creating subscription for newsId: " . $regData['newsId']);
                    $start = date('Y-m-d H:i:s');
                    $end   = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($start)));
                    
                    $subscribeData = [
                        'user'            => $userId,
                        'news_type'       => $regData['newsId'],
                        'sub_start_date'  => $start,
                        'sub_end_date'    => $end,
                        'status'          => 1,
                        'author'          => 1,
                        'created'         => date('Y-m-d H:i:s'),
                        'modified'        => date('Y-m-d H:i:s')
                    ];

                    $this->getDefinedTable(News\SubcribeTable::class)->save($subscribeData);
                    error_log("Subscription created successfully");
                }
                
                // Send credentials email
                $message = "
                    <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                        <h2 style='color: #0f766e;'>Welcome to MyThimphu!</h2>
                        <p>Dear {$regData['name']},</p>
                        <p>Your registration has been completed successfully. Below are your login credentials:</p>
                        <div style='background-color: #f3f4f6; padding: 15px; border-left: 4px solid #0f766e; margin: 20px 0;'>
                            <p style='margin: 5px 0;'><strong>Username:</strong> {$regData['email']}</p>
                            <p style='margin: 5px 0;'><strong>Password:</strong> {$randomPassword}</p>
                        </div>
                        <p>Please keep these credentials safe.</p>
                        <br>
                        <p>Best regards,<br>MyThimphu Team</p>
                    </div>
                ";
                $mail = array(
                    'email'    => $regData['email'],
                    'name'     => $regData['name'],
                    'subject'  => 'MyThimphu - Registration Successful',
                    'message'  => $message,
                    'cc_array' => [],
                );
                error_log("Sending credentials email to: " . $regData['email']);
                try {
                    $this->EmailPlugin()->sendmail($mail);
                    error_log("Email sent successfully!");
                } catch (\Exception $e) {
                    error_log("Email send failed: " . $e->getMessage());
                }

                // Clear session
                $this->clearRegistrationSession($session);
                
                error_log("Registration completed! Redirecting to login...");
                $this->flashMessenger()->addMessage("success^Registration and payment successful! Your login credentials have been sent to your email.");
                return $this->redirect()->toRoute('auth', ['action' => 'login']);

            } else {
                error_log("ERROR: Failed to create user - userId is 0 or false");
                $this->flashMessenger()->addMessage("error^Failed to create user account.");
                return $this->redirect()->toRoute('auth', ['action' => 'registration']);
            }

        } catch (\Exception $e) {
            error_log("EXCEPTION in completeRegistration: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->flashMessenger()->addMessage("error^Registration error: " . $e->getMessage());
            return $this->redirect()->toRoute('auth', ['action' => 'registration']);
        }
    }
    /**
     * Validate registration form data
     */
    private function validateRegistrationData($data)
    {
        if (empty($data['name'])) return "error^Name is required.";
        if (empty($data['cid'])) return "error^Citizenship ID is required.";
        if (empty($data['email'])) return "error^Email is required.";
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) return "error^Invalid email format.";
        if (empty($data['phone'])) return "error^Phone number is required.";
        if (empty($data['account_number'])) return "error^Bank account number is required.";
        if (empty($data['bank_id'])) return "error^Bank ID is required.";
        
        return null;
    }
    /**
     * Clear registration session
     */
    private function clearRegistrationSession(Container $session)
    {
        unset($session->bfsTxnId);
        unset($session->paymentDesc);
        unset($session->regData);
    }
    /**
     * Verify the code sent for 2FA
     * duration: 5 minutes
     * redirect to home on success
     * redirect to login on failure
     */
    public function verify2faAction()
    {
        $session = new Container('2fa');
        $auth = new AuthenticationService();

        // Session expired or no code
        if (empty($session->userId)) {
            $this->flashMessenger()->addMessage("error^Session expired, please login again.");
            return $this->redirect()->toRoute('auth', ['action' => 'login']);
        }

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            if (time() > $session->expires) {
                $this->flashMessenger()->addMessage("error^2FA code expired, please login again.");
                $this->clear2faSession();
                return $this->redirect()->toRoute('auth', ['action' => 'login']);
            }
            if ($data['twofa_code'] == $session->code) {
                // Read userId BEFORE clearing session
                $userId = $session->userId;
                // Load user from DB (your table returns array)
                $user = $this->getDefinedTable(Administration\UsersTable::class)->get($userId);
                $userData = $user[0];
                // Convert array to object for proper authentication
                $userObject = (object) $userData;
                // Write user object to auth storage
                $auth->getStorage()->write($userObject);
                // Update login info
                $updateData = [
                    'id' => $userData['id'],
                    'last_login' => date('Y-m-d H:i:s'),
                    'last_accessed_ip' => $_SERVER['REMOTE_ADDR'],
                    'logins' => $userData['logins'] + 1,
                    'modified' => date('Y-m-d H:i:s')
                ];
                // echo"<pre>"; print_r($updateData); exit;
                $this->getDefinedTable(Administration\UsersTable::class)->save($updateData);
                
                // Clear only the 2fa session, not the entire session
                $this->clear2faSession();
                
                $this->flashMessenger()->addMessage("info^Login successful!");
                return $this->redirect()->toRoute('home');

            } else {
                $this->flashMessenger()->addMessage("error^Invalid 2FA code, try again.");
                return $this->redirect()->toRoute('auth', ['action' => 'verify-2fa']);
            }
        }
        return new ViewModel(['title' => 'Enter 2FA Code']);
    }
    /**
     * Clear 2FA session container safely
     */
    private function clear2faSession()
    {
        $session = new Container('2fa');
        unset($session->userId);
        unset($session->user);
        unset($session->code);
        unset($session->expires);
    }
    /**
     * Logout
     */
    public function logoutAction()
    {
        if (!$this->identity()) {
            $this->flashMessenger()->addMessage("warning^Your session has already expired. Login to proceed.");
            return $this->redirect()->toRoute('auth', ['action' => 'login']);
        }

        $auth = new AuthenticationService();
        $msg = $this->params()->fromRoute('id');
        $id = $this->identity()->id;

        // Log last_logout timestamp
        $this->getDefinedTable(Administration\UsersTable::class)->save([
            'id' => $id,
            'last_logout' => date('Y-m-d H:i:s')
        ]);

        // Optional: store identity for audit or logs
        if ($auth->hasIdentity()) {
            $identity = $auth->getIdentity();
        }

        // Clear identity and session
        $auth->clearIdentity();
        $sessionManager = new \Laminas\Session\SessionManager();
        $sessionManager->forgetMe();
        $this->session->getManager()->getStorage()->clear();

        //  Check if the session was created via SSO
        if (isset($identity) && $identity->is_sso_user) {

            try {
                // Optional: get post-logout callback
                $postLogoutRedirectUri = $this->url()->fromRoute('logout-callback', [], ['force_canonical' => true]);

                // Get SSO logout URL
                $logoutUrl = $this->ssoService->getLogoutUrl($postLogoutRedirectUri);

                // Destroy session entirely
                $this->session->getManager()->destroy();

                return $this->redirect()->toUrl($logoutUrl);
            } catch (\Exception $e) {
                // Fallback if SSO logout fails
                $this->flashMessenger()->addMessage("danger^SSO logout failed: " . $e->getMessage());
            }
        }
        // If not SSO or SSO failed, show normal logout message
        if ($msg == "1") {
            $this->flashMessenger()->addMessage('warning^You cannot use the system as you are blocked. Contact the administrator.');
        } else {
            $this->flashMessenger()->addMessage('info^You have successfully logged out!');
        }

        return $this->redirect()->toRoute('auth', ['action' => 'login']);
    }
    /**
     * state send back from SSO provider needed to be same as state in the session
     * SSO Callback Action
	 */
    public function callbackAction()
    {
        $code  = $this->params()->fromQuery('code');
        $state = $this->params()->fromQuery('state');
        $error = $this->params()->fromQuery('error');

        error_log('[SSO] Callback hit code_present=' . (! empty($code) ? 'yes' : 'no') . ' state=' . ($state ?? 'none') . ' session_state=' . ($this->session->state ?? 'none') . ' session_id=' . session_id());

        if ($error) {
            return new ViewModel([
                'error' => 'SSO Authentication failed: ' . $error,
            ]);
        }
        // Debug: dump all session variables
        error_log('[SSO] Session dump: ' . print_r(iterator_to_array($this->session->getIterator()), true));

        if (!$state || $state !== $this->session->state) {
            error_log('[SSO] Callback state mismatch incoming=' . ($state ?? 'none') . ' session=' . ($this->session->state ?? 'none') . ' session_id=' . session_id());
            return new ViewModel([
                'error' => 'Invalid state parameter. Possible CSRF attack.',
            ]);
        }

        $codeVerifier = $this->session->code_verifier ?? null;
        if (!$codeVerifier) {
            throw new \Exception('Code verifier not found in session');
        }
        try {
            $tokens = $this->ssoService->exchangeCodeForToken($code, $codeVerifier);
            if (!isset($tokens['access_token'])) {
                throw new \Exception('Access token not found in response');
            }
            $this->ssoService->storeTokens($tokens);
            $jwkSet = $this->ssoService->getJwkEndpointContent($this->config['openid_config']['jwks_uri']);
            $jwt = $this->session->offsetGet($this->config['session_keys']['access_token']);
            $jwk = $jwkSet['keys'][0];
            $pem = $this->ssoService->jwkToPem($jwk);

            if (!$this->ssoService->verifyJwtSignature($jwt, $pem)) {
                throw new \Exception('JWT signature verification failed.');
            }

            // Fetch user info using the access token  
            $userData = $this->ssoService->getUserInfo($tokens['access_token']);
            $userData = $this->completeUserData($userData);
        

            //  Save user in session
            $this->session->user = $userData;
            $this->session->authenticated = true;
            $this->session->access_token = $tokens['access_token'];
            $this->session->is_sso_user = true;

            // The line unset($this->session->state); removes the state property from the session object associated with the current user. I
            unset($this->session->state);
            
            // AuthenticationService login logic
            $authService = new \Laminas\Authentication\AuthenticationService();
            $authStorage = $authService->getStorage();
            $authStorage->write((object) $userData); // Convert array to object

            $userId = $userData['id'] ?? intval($userData['sub']);
            if ($userId) {
            
                // Log login time and IP
                $ipAddress = $_SERVER['HTTP_CLIENT_IP']
                            ?? $_SERVER['HTTP_X_FORWARDED_FOR']
                            ?? $_SERVER['REMOTE_ADDR'];

                $usersTable = $this->getDefinedTable(UsersTable::class);
                $loginCount = $usersTable->getColumn($userId, 'logins');
                $usersTable->save([
                    'id' => $userId,
                    'last_login' => date('Y-m-d H:i:s'),
                    'last_accessed_ip' => $ipAddress,
                    'logins' => $loginCount + 1,
                ]);
                $status = $usersTable->getColumn($userId, 'status');
                if ($status == "9") {
                    return $this->redirect()->toRoute('auth', ['action' => 'logout', 'id' => '1']);
                }
            }

            $this->flashMessenger()->addMessage("info^ Welcome,</br>You have successfully logged in!");

            return $this->redirect()->toRoute('home');

        } catch (\Exception $e) {
            return new ViewModel([
                'error' => 'Authentication failed: ' . $e->getMessage(),
            ]);
        }
    }
     // logout methods:
    public function SSOlogoutAction()
    {
        try {
            // Redirect URI that the SSO provider should call after logout
            $postLogoutRedirectUri = $this->url()->fromRoute('logout-callback', [], ['force_canonical' => true]);
    
            // Get the end_session_endpoint from OpenID config
            $logoutUrl = $this->ssoService->getLogoutUrl($postLogoutRedirectUri);
    
            return $this->redirect()->toUrl($logoutUrl);
    
        } catch (\Exception $e) {
            return new ViewModel([
                'error' => 'Failed to initiate SSO logout: ' . $e->getMessage(),
            ]);
        }
    }

    public function logoutCallbackAction()
    {
        // Redundant safety clear for app session
        $this->session->getManager()->getStorage()->clear();
        $this->session->getManager()->destroy();

        // Flash message or redirect
        $this->flashMessenger()->addSuccessMessage('You have been logged out successfully.');

        return $this->redirect()->toRoute('home', [], ['query' => ['logout' => 'success']]);
    }
    public function statusAction()
    {
        return new ViewModel([
            'authenticated' => $this->session->authenticated ?? false,
            'user'          => $this->session->user ?? null,
        ]);
    }

    public function completeUserData($userData)
    {
        $userData = [
            'id'                 => intval($userData['sub']),
            'name'               => $userData['name'],
            'preferred_username' => $userData['preferred_username'],
            'email'              => $userData['email'],
        ];

        try {
            //NOTE: getting all information from table due to discrepancy of data from getting sso only
            $userData['name'] = $this->getDefinedTable(UsersTable::class)->getColumn($userData['id'], $column = 'name');
            $userData['role'] = $this->getDefinedTable(UsersTable::class)->getColumn($userData['id'], $column='role');
            $userData['location'] = $this->getDefinedTable(UsersTable::class)->getColumn($userData['id'], $column = 'location');
            $userData['location_type'] = $this->getDefinedTable(UsersTable::class)->getColumn($userData['id'], $column = 'location_type');
            $userData['mobile'] = $this->getDefinedTable(UsersTable::class)->getColumn($userData['id'], $column = 'mobile');
            $userData['login_method'] = 'sso';
            $userData['is_sso_user'] = true;
            return $userData;
        } catch (\Exception $e) {
          
            $this->flashMessenger()->addErrorMessage('Failed to get user role: ' . $e->getMessage());
            return null;
        }
    }
    /**
	 * forgotpwd
	 */
    public function forgotpwdAction()
    {
        $captcha = new AuthForm();
        if ($this->getRequest()->isPost()) {
            $form = $this->getRequest()->getPost();
            $captcha->setData($form);
            if ($captcha->isValid()) {
                $userDtls = $this->getDefinedTable(Administration\UsersTable::class)->get(array('email' => $form['email']));
                if(sizeof($userDtls) == 0){
                    $this->flashMessenger()->addMessage('error^ This email is not registered with any of the users in the system.');
                    return $this->redirect()->toRoute('auth', array('action' => 'forgotpwd'));
                }else{
                    foreach ($userDtls as $row);
					$email = $row['email']; $name = $row['name'];
					$expiry_time = date("Y-m-d H:i:s", strtotime('+12 hours'));
					$recovery_stamp = rtrim(strtr(base64_encode($row['email']."***".$expiry_time), '+/', '-_'), '=');
					
                    $recovery_link = "<div style='font-family: Arial, sans-serif; line-height: 19px; color: #444444; font-size: 13px; text-align: center;'>
						<a href='https://erp.bhutanpost.bt/public/auth/amendpwd/".$recovery_stamp."' style='color: #ffffff; text-decoration: none; margin: 0px; text-align: center; vertical-align: baseline; border: 4px solid #1e7e34; padding: 4px 9px; font-size: 15px; line-height: 21px; background-color: #218838;'>&nbsp; Reset Password &nbsp;</a>
					</div>";
					
                    $notify_msg = "You have requested for password recovery. Please click on password recovery link below to reset your password: <br><br>".$recovery_link.
									"<br>This link will expire in 12 hours and can be used only once.<br><br>If you do not want to change your password and did not request this, please ignore and delete this message.";
                    $mail = array(
                        'email'    => $row['email'],
                        'name'     => $row['name'],
                        'subject'  => 'BhutanPost-ERP: Password Recovery', 
                        'message'  => $notify_msg,
                        'cc_array' => [],
                    );
                    $this->EmailPlugin()->sendmail($mail);
					$this->flashMessenger()->addMessage("success^ Your password reset link will be sent to your registered email, i.e. ".$row['email'].". Please check in the spam folder if you can't find in the inbox. Thank You.");
					return $this->redirect()->toRoute('auth', array('action' => 'forgotpwd'));
                }
            }else{
                $this->flashMessenger()->addMessage("warning^ Captcha is invalid. Try again.");
                return $this->redirect()->toRoute('auth', array('action' => 'forgotpwd'));
            }
        }
        $ViewModel = new ViewModel(array('title' => 'Forgot Password','captcha'=>$captcha));
        $ViewModel->setTerminal(false);
        return $ViewModel;
    }
    /**
     * amendpwd Action -- link from email
     */
    public function amendpwdAction()
    {	
		$recovery_dtl = $this->params()->fromRoute('id');
		$decoded_dtl = base64_decode(str_pad(strtr($recovery_dtl, '-_', '+/'), 4 - ((strlen($recovery_dtl) % 4) ?: 4), '=', STR_PAD_RIGHT));
		$array_dtl = explode("***", $decoded_dtl);
		$email = (sizeof($array_dtl)>1)?$array_dtl[0]:'0';
		$expiry_time = (sizeof($array_dtl)>1)?$array_dtl[1]:'0';
		$userDtls = $this->getDefinedTable(Administration\UsersTable::class)->get(array('email' => $email));
		
        if($this->getRequest()->isPost()) {
            $form = $this->getRequest()->getPost();
			$staticSalt = $this->password()->getStaticSalt();
			$user_dtls = $this->getDefinedTable(Administration\UsersTable::class)->get(array('email' => $form['recovery_id']));	
			if(sizeof($user_dtls) == 1):
				foreach($user_dtls as $user_dtl);
				if($user_dtl['email'] == $form['recovery_id']):
					if($form['new_password'] == $form['confirm_password']):
						$dynamicSalt = $this->password()->generateDynamicSalt();
						$password = $this->password()->encryptPassword(
                            $staticSalt,
                            $form['new_password'],
                            $dynamicSalt
						);
						$data = array(
                            'id'		=> $user_dtl['id'],
                            'password'	=> $password,
                            'salt'		=> $dynamicSalt,
						);
						$result = $this->getDefinedTable(Administration\UsersTable::class)->save($data);
						if($result > 0):	
							$this->flashMessenger()->addMessage("success^ Successfully updated user password.");
						else:
							$this->flashMessenger()->addMessage("error^ Failed to update user password.");
						endif;
					else:
						$this->flashMessenger()->addMessage("error^ New Password and Confirmed Password doesn't match.");
					endif;
				else:
					$this->flashMessenger()->addMessage("error^ The entered email and the recovery details doesn't match.");
				endif;
			else:
				$this->flashMessenger()->addMessage("error^ The user with following recovery details doesn't exist anymore in the system.");
			endif;
			return $this->redirect()->toRoute('auth', array('action' => 'login'));
        }
		if($expiry_time < date('Y-m-d H:i:s')){
			$this->flashMessenger()->addMessage('error^ This password recovery link has already expired.');
			return $this->redirect()->toRoute('auth', array('action' => 'login'));
		}else{
			if(sizeof($userDtls) == 0){
				$this->flashMessenger()->addMessage('error^ This email is no more associated with any of the users in the system.');
				return $this->redirect()->toRoute('auth', array('action' => 'login'));
			}else{
				foreach ($userDtls as $row);
				$email = $row['email'];
				$ViewModel = new ViewModel(array('title' => 'Amend Password','email' => $email,));
				$ViewModel->setTerminal(false);
				return $ViewModel;
			}
		}
		return $this->redirect()->toRoute('auth', array('action' => 'login'));
    }
}
