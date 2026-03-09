<?php
namespace News\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Authentication\AuthenticationService;
use Laminas\Mvc\MvcEvent;
use Interop\Container\ContainerInterface;
use Laminas\Http\Headers;
use Laminas\Http\Response;
use Laminas\Session\Container;
use DateTime;
use News\Model as News;
use Acl\Model as Acl;
use Administration\Model as Administration;
use DomesticPayment\Service\RmaPaymentService;
use DomesticPayment\Model\PaymentTransaction;
use DomesticPayment\Model\PaymentTransactionTable;
class NewspaperController extends AbstractActionController
{
	private $_container;
	protected $_table; 		// database table 
    protected $_user; 		// user detail
    protected $_login_id; 	// logined user id
    protected $_login_role; // logined user role
    protected $_author; 	// logined user id
    protected $_created; 	// current date to be used as created dated
    protected $_modified; 	// current date to be used as modified date
    protected $_config; 	// configuration details
    protected $_dir; 		// default file directory
    protected $_id; 		// route parameter id, usally used by crude
    protected $_auth; 		// checking authentication
    protected $_highest_role;// highest user role
    protected $_lowest_role;// loweset user role
	protected $_safedataObj; // safedata controller plugin
	protected $_connection; // database connection
	protected $_maxSize;
    protected $_fileExts;
    private $rmaPaymentService;
    private $paymentTransactionTable;
    
	public function __construct(ContainerInterface $container)
    {
        $this->_container = $container;
    }
	/**
	 * Laminas Default TableGateway
	 * Table name as the parameter
	 * returns obj
	 */
	public function getDefaultTable($table)
	{
		$this->_table = new TableGateway($table, $this->_container->get('Laminas\Db\Adapter\Adapter'));
		return $this->_table;
	}

   /**
    * User defined Model
    * Table name as the parameter
    * returns obj
    */
    public function getDefinedTable($table)
    {
        $definedTable = $this->_container->get($table);
        return $definedTable;
    }
	
	/**
	* initial set up
	* general variables are defined here
	*/
	public function init()
	{
		$this->_auth = new AuthenticationService;
		if(!$this->_auth->hasIdentity()):
			$this->flashMessenger()->addMessage('error^ You dont have right to access this page!');
   	        $this->redirect()->toRoute('auth', array('action' => 'login'));
		endif;
		if(!isset($this->_config)) {
			$this->_config = $this->_container->get('Config');
		}
		if(!isset($this->_user)) {
		    $this->_user = $this->identity();
		}
		if(!isset($this->_login_id)){
			$this->_login_id = $this->_user->id;  
		}
		if(!isset($this->_login_role)){
			$this->_login_role = $this->_user->role;  
		}
		if(!isset($this->_highest_role)){
			$this->_highest_role = $this->getDefinedTable(Acl\RolesTable::class)->getMax($column='id');  
		}
		if(!isset($this->_lowest_role)){
			$this->_lowest_role = $this->getDefinedTable(Acl\RolesTable::class)->getMin($column='id'); 
		}
		if(!isset($this->_author)){
			$this->_author = $this->_user->id;  
		}
		
		$this->_id = $this->params()->fromRoute('id');
		
		$this->_created = date('Y-m-d H:i:s');
		$this->_modified = date('Y-m-d H:i:s');
		
		$this->_safedataObj = $this->safedata();
		$this->_connection = $this->_container->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
		
		//$this->_permissionObj =  $this->PermissionPlugin();
		//$this->_permissionObj->permission($this->getEvent());	
	}
	/**
	 * PDF Action of MasterController
	 */
    public function pdfnewsAction()
    {  
    	$this->init();
		try {
			$sub = $this->getDefinedTable(News\SubcribeTable::class)
					->get(['user' => $this->_author]);
		} catch (\Exception $e) {
			$sub = null;
		}
		// Determine active subscription using DATE ONLY (inclusive): today <= sub_end_date
		$hasActive = false;
		$activeNewsType = null;
		$activeEndDate = null;
		$today = date('Y-m-d');
		if ($sub) {
			$rows = is_array($sub) ? $sub : [$sub];
			foreach ($rows as $row) {
				$endRaw = is_array($row) ? ($row['sub_end_date'] ?? null) : (isset($row->sub_end_date) ? $row->sub_end_date : null);
				// Normalize to 'YYYY-MM-DD' if value contains time
				$endDate = $endRaw ? substr((string)$endRaw, 0, 10) : null;
				// Active when no end date or today is before/equal to end date
				if (!$endDate || $today <= $endDate) {
					$hasActive = true;
					$activeNewsType = is_array($row) ? ($row['news_type'] ?? null) : (isset($row->news_type) ? $row->news_type : null);
					$activeEndDate = $endDate;
					break;
				}
			}
		}
		// Allow highest role (admin) to bypass; others require active subscription
		if (!$hasActive && $this->_login_role < 10) {
			$view = new ViewModel([
				'message' => 'Your subscription is inactive or expired. Please subscribe to view this page.',
			]);
			$view->setTemplate('partial/not-subscribed');
			return $view;
		}
		// Fetch news by active subscription type; admins without subscription see all
		if ($activeNewsType) {
			$news = $this->getDefinedTable(News\NewsTable::class)->get(['news_type' => $activeNewsType]);
		} else {
			$news = $this->getDefinedTable(News\NewsTable::class)->getAll();
		}
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($news));
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
		// Build expiry notice if within 7 days (inclusive) of end date
		$expiryNotice = null;
		$subID = null;
		if ($activeEndDate) {
			$dToday = \DateTime::createFromFormat('Y-m-d', $today);
			$dEnd   = \DateTime::createFromFormat('Y-m-d', $activeEndDate);
			if ($dToday && $dEnd) {
				$daysLeft = (int)$dToday->diff($dEnd)->format('%r%a');
				if ($daysLeft >= 0 && $daysLeft <= 7) {
					$expiryNotice = "Your subscription expires in {$daysLeft} day" . ($daysLeft === 1 ? '' : 's') . " (on {$activeEndDate}). Please renew to maintain access.";
				}
			}
		}
		// Extract subscription ID for renewal button
		if ($sub) {
			$subID = is_array($sub) ? ($sub[0]['id'] ?? null) : ($sub->id ?? null);
		}
        return new ViewModel(array(
			'title'            => 'News',
			'paginator'        => $paginator,
			'page'             => $page,
			'author'           => $this->_author,
			'subcribtions'     => $this->getDefinedTable(News\SubcribeTable::class)->get(array('user'=>$this->_author)),
			'newstypeObj'      => $this->getDefinedTable(News\NewsChannelTable::class),
			'expiryNotice'     => $expiryNotice,
			'subID'            => $subID ?? null,
		)); 
	}

	/**
	 * Renew Subscription with Payment
	 */
	public function renewsubscriptionAction()
	{
		$this->init();
		$session = new Container('renewal');
		$request = $this->getRequest();

		// Initialize payment services
		if (!$this->rmaPaymentService) {
			$this->rmaPaymentService = $this->_container->get(RmaPaymentService::class);
		}
		if (!$this->paymentTransactionTable) {
			$this->paymentTransactionTable = $this->_container->get(PaymentTransactionTable::class);
		}

		// Get user's current subscription
		$subscription = $this->getDefinedTable(News\SubcribeTable::class)->get(['user' => $this->_author]);
		if (!$subscription) {
			$this->flashMessenger()->addMessage('error^No active subscription found to renew.');
			return $this->redirect()->toRoute('newspaper', ['action' => 'pdfnews']);
		}

		// Handle Step 2: OTP Verification and Debit
		if ($request->isPost() && $request->getPost('step') === '2') {
			return $this->handleRenewalDebit($request->getPost(), $session, $subscription);
		}

		// Handle Step 1: Payment Authorization and Account Inquiry
		if ($request->isPost()) {
			return $this->handleRenewalPayment($request->getPost(), $session, $subscription);
		}

		// Show renewal form
		$banks = $this->getDefinedTable(Administration\BankTable::class)->getAll();
		return new ViewModel([
			'title' => 'Renew Subscription',
			'subscription' => $subscription,
			'banks' => $banks,
			'step' => $session->step ?? '1',
			'session' => $session,
		]);
	}

	/**
	 * Handle Renewal Payment Authorization and Account Inquiry
	 */
	private function handleRenewalPayment($data, Container $session, $subscription)
	{
		try {
			// Validate input
			if (empty($data['account_number']) || empty($data['bank_id'])) {
				$this->flashMessenger()->addMessage('error^Bank account details are required.');
				return $this->redirect()->toRoute('newspaper', ['action' => 'renewsubscription']);
			}

			$config = $this->_container->get('config');
			$merchantId = trim((string)($config['rma_api']['merchant_id'] ?? ''));

			// Authorize payment
			error_log('Renewal Payment Authorization - User: ' . $this->_author);
			$paymentDesc = 'Subscription Renewal - User ' . $this->_author;
			$authResponse = $this->rmaPaymentService->authorizeTransaction(
				$merchantId,
				$paymentDesc,
				'1.00' // Renewal fee
			);

			error_log('RMA Authorization Response: ' . json_encode($authResponse));

			$authStatus = strtoupper((string)($authResponse['status'] ?? $authResponse['authorisation_status'] ?? ''));
			$authCode = $authResponse['bfs_responseCode']
				?? $authResponse['bfs_response_code']
				?? null;

			if (!$authResponse || $authStatus === 'FAILED' || ($authCode !== null && $authCode !== '00') || empty($authResponse['bfs_bfsTxnId'])) {
				$errorMsg = $authResponse['bfs_responseMessage']
					?? $authResponse['bfs_response_message']
					?? $authResponse['message']
					?? 'Payment authorization failed';
				$errorSuffix = $authCode ? ' (Code: ' . $authCode . ')' : '';
				error_log('Payment authorization FAILED: ' . $errorMsg);
				$this->flashMessenger()->addMessage('error^Payment authorization failed: ' . $errorMsg . $errorSuffix);
				return $this->redirect()->toRoute('newspaper', ['action' => 'renewsubscription']);
			}

			// Store in session
			$session->bfsTxnId = $authResponse['bfs_bfsTxnId'];
			$session->paymentDesc = $paymentDesc;
			$session->accountNo = $data['account_number'];
			$session->bankId = $data['bank_id'];
			$session->subscriptionId = is_array($subscription) ? $subscription[0]['id'] : $subscription->id;

			// Save payment transaction
			$transaction = new PaymentTransaction();
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
				->setCreatedAt(new DateTime())
				->setUpdatedAt(new DateTime());

			$txnId = $this->paymentTransactionTable->savePaymentTransaction($transaction);
			$session->txnId = $txnId;

			$this->flashMessenger()->addMessage('info^Payment authorized. Please verify your bank account and enter OTP.');

			// Account Inquiry
			return $this->handleRenewalAccountInquiry($data, $session);

		} catch (\Exception $e) {
			error_log('Renewal Payment Error: ' . $e->getMessage());
			$this->flashMessenger()->addMessage('error^Payment error: ' . $e->getMessage());
			return $this->redirect()->toRoute('newspaper', ['action' => 'renewsubscription']);
		}
	}

	/**
	 * Handle Renewal Account Inquiry
	 */
	private function handleRenewalAccountInquiry($data, Container $session)
	{
		try {
			$inquiryResponse = $this->rmaPaymentService->accountInquiry(
				$data['account_number'],
				$data['bank_id'],
				$session->bfsTxnId
			);

			error_log('Account Inquiry Response: ' . json_encode($inquiryResponse));

			// Map API fields
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

			$codeOk = ($respCode === '00');
			$statusOk = ($enquiryStatus && stripos($enquiryStatus, 'SUCCESS') !== false);

			if (!$inquiryResponse || (!($codeOk || $statusOk))) {
				$displayMsg = $respDesc ?: ($inquiryResponse['message'] ?? 'Account verification failed');
				if ($respCode) {
					$displayMsg .= " (Code: {$respCode})";
				}
				error_log('Account Inquiry FAILED: ' . $displayMsg);
				$this->flashMessenger()->addMessage('error^Account verification failed: ' . $displayMsg);
				return $this->redirect()->toRoute('newspaper', ['action' => 'renewsubscription']);
			}

			// Update transaction
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
				}
			}

			$this->flashMessenger()->addMessage('success^Account verified! Please check your phone for OTP.');
			$session->step = '2';

			return $this->redirect()->toRoute('newspaper', ['action' => 'renewsubscription']);

		} catch (\Exception $e) {
			error_log('Account Inquiry Error: ' . $e->getMessage());
			$this->flashMessenger()->addMessage('error^Account inquiry error: ' . $e->getMessage());
			return $this->redirect()->toRoute('newspaper', ['action' => 'renewsubscription']);
		}
	}

	/**
	 * Handle Renewal Payment Debit with OTP
	 */
	private function handleRenewalDebit($data, Container $session, $subscription)
	{
		try {
			if (empty($session->bfsTxnId)) {
				$this->flashMessenger()->addMessage('error^Session expired. Please start again.');
				return $this->redirect()->toRoute('newspaper', ['action' => 'renewsubscription']);
			}

			if (empty($data['otp'])) {
				$this->flashMessenger()->addMessage('error^OTP is required.');
				$session->step = '2';
				return $this->redirect()->toRoute('newspaper', ['action' => 'renewsubscription']);
			}

			// Process debit
			$debitResponse = $this->rmaPaymentService->debitTransaction(
				$data['otp'],
				$session->bfsTxnId
			);

			error_log('Debit Response: ' . json_encode($debitResponse));

			// Map fields
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
				$errorMsg = $debitRespMsg ?? $accountDebitStatus ?? 'Payment failed';
				if ($debitRespCode) {
					$errorMsg .= " (Error Code: {$debitRespCode})";
				}
				error_log('Debit FAILED: ' . $errorMsg);
				$this->flashMessenger()->addMessage('error^Payment failed: ' . $errorMsg);
				$session->step = '2';
				return $this->redirect()->toRoute('newspaper', ['action' => 'renewsubscription']);
			}

			// Update payment transaction
			if (!empty($session->txnId)) {
				$transaction = $this->paymentTransactionTable->getPaymentTransaction($session->txnId);
				if ($transaction) {
					$transaction->setRemitterOtp($data['otp'])
						->setStatus('completed')
						->setResponseCode($debitRespCode ?? '')
						->setResponseMessage(($debitRespMsg ?? $accountDebitStatus ?? 'Success'))
						->setUpdatedAt(new DateTime());

					$this->paymentTransactionTable->savePaymentTransaction($transaction);
				}
			}

			// Update subscription end date (+1 year from current end date)
			$subRow = is_array($subscription) ? $subscription[0] : $subscription;
			$currentEndDate = is_array($subRow) ? $subRow['sub_end_date'] : $subRow->sub_end_date;
			$currentEndNormalized = substr((string)$currentEndDate, 0, 10);
			$today = date('Y-m-d');

			// Extend from whichever is later: today or current end date
			$baseDate = ($currentEndNormalized >= $today) ? $currentEndNormalized : $today;
			$newEndDate = date('Y-m-d', strtotime($baseDate . ' +1 year'));

			$subscriptionId = is_array($subRow) ? $subRow['id'] : $subRow->id;
			$updateData = [
				'id' => $subscriptionId,
				'sub_end_date' => $newEndDate,
				'modified' => date('Y-m-d H:i:s'),
			];

			$this->getDefinedTable(News\SubcribeTable::class)->save($updateData);

			error_log('Subscription renewed successfully - New end date: ' . $newEndDate);

			// Clear session
			unset($session->bfsTxnId);
			unset($session->paymentDesc);
			unset($session->accountNo);
			unset($session->bankId);
			unset($session->txnId);
			unset($session->step);
			unset($session->subscriptionId);

			$this->flashMessenger()->addMessage('success^Subscription renewed successfully! Your new expiry date is ' . $newEndDate . '.');
			return $this->redirect()->toRoute('newspaper', ['action' => 'pdfnews']);

		} catch (\Exception $e) {
			error_log('Renewal Debit Error: ' . $e->getMessage());
			$this->flashMessenger()->addMessage('error^Payment debit error: ' . $e->getMessage());
			return $this->redirect()->toRoute('newspaper', ['action' => 'renewsubscription']);
		}
	}

	/**
	 * Render News PDF Action
	 */
	public function rendernewsAction()
	{
		$this->init();
	    $news = $this->getDefinedTable(News\NewsTable::class)->get(['id' => $this->_id]);
		if (!$news) {
			return $this->notFoundAction();
		}
		$filePath = realpath(getcwd() . '/public/upload/news/' . $news[0]['pdf']);
        //echo '<pre>';print_r($filePath); exit;
        if (!file_exists($filePath)) {
            return $this->notFoundAction();
        }
        $response = new Response();
        $response->getHeaders()
            ->addHeaderLine('Content-Type', 'application/pdf')
            ->addHeaderLine('Content-Disposition', 'inline; filename="file.pdf"')
            ->addHeaderLine('Content-Length', filesize($filePath));
        $response->setContent(file_get_contents($filePath));
        return $response;
	}
	/**
	 * Render News PDF Action
	 */
	public function aireaderAction()
	{
		$this->init();
		$news = $this->getDefinedTable(News\NewsTable::class)->getAll();
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($news));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
		try {
			$sub = $this->getDefinedTable(News\SubcribeTable::class)
						->get(['user' => $this->_author]);
		} catch (\Exception $e) {
			$sub = null;
		}
		if (!$sub && $this->_login_role < $this->_highest_role) {
			$view = new ViewModel([
				'message' => 'You are not subscribed to view this page.',
			]);
			$view->setTemplate('partial/not-subscribed');
			return $view;
		}
        return new ViewModel(array(
			'title'            => 'News',
			'paginator'        => $paginator,
			'page'             => $page,
			'author'           => $this->_author,
			'subcribtions'     => $this->getDefinedTable(News\SubcribeTable::class)->get(array('user'=>$this->_author)),
			'newstypeObj'      => $this->getDefinedTable(News\NewsChannelTable::class),
		)); 
	}

	/**
	 * Add News
	 */
	public function addnewsAction()
	{
		$this->init();

		$fileManagerDir = $this->_config['file_manager']['public_dir'];
		$this->_maxSize = $this->_config['file_manager']['maxSize'];

		// PDF ONLY
		$allowedExts  = ['pdf'];
		$allowedMime  = ['application/pdf'];

		if (!is_dir($fileManagerDir)) {
			mkdir($fileManagerDir, 0777, true);
		}

		$this->_dir = realpath($fileManagerDir);
		$request = $this->getRequest();

		if ($request->isPost()) {

			$data = array_merge_recursive(
				$request->getPost()->toArray(),
				$request->getFiles()->toArray()
			);

			if (!isset($data['pdf-file']) || empty($data['pdf-file']['name'])) {
				$this->flashMessenger()->addMessage("error^ Please select a PDF file");
				return $this->redirect()->toUrl(
					$this->getRequest()->getHeader('Referer')->getUri()
				);
			}
			$file = $data['pdf-file'];
			$ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
			$mime = mime_content_type($file['tmp_name']);
			$folder_name = 'news';
			// Validate PDF
			if (!in_array($ext, $allowedExts, true) || !in_array($mime, $allowedMime, true)) {
				$this->flashMessenger()->addMessage("error^ Only PDF files are allowed");

			} elseif ($file['size'] > $this->_maxSize) {
				$this->flashMessenger()->addMessage(
					"error^ File size too large. Maximum allowed is {$this->_maxSize} KB"
				);
			} elseif ($file['error'] !== UPLOAD_ERR_OK) {
				$this->flashMessenger()->addMessage("error^ File upload error");
			} else {
				// Create folder if not exists
				$uploadDir = $this->_dir . DIRECTORY_SEPARATOR . $folder_name;
				if (!is_dir($uploadDir)) {
					mkdir($uploadDir, 0777, true);
				}
				// Generate secure filename
				$file_name = date('ym') . '_' . uniqid() . '.pdf';
				$upload_path = $uploadDir . DIRECTORY_SEPARATOR . $file_name;

				if (move_uploaded_file($file['tmp_name'], $upload_path)) {
					$insertData = [
						'news_type'    => $request->getPost('news_type'),
						'title'        => $data['title'],
						'description'  => $data['description'],
						'new_updated'  => date('Y-m-d'),
						'pdf'          => $file_name,
						'status'       => 1,
						'author'       => $this->_author,
						'created'      => $this->_created,
						'modified'     => $this->_modified
					];
					$this->getDefinedTable(News\NewsTable::class)->save($insertData);
					$this->flashMessenger()->addMessage("success^ PDF uploaded successfully");
				} else {
					$this->flashMessenger()->addMessage("error^ Failed to move uploaded file");
				}
			}
			return $this->redirect()->toRoute('newspaper', array('action' => 'pdfnews'));
		}
		return new ViewModel([
			'title'    => 'Add News',
			'newtypes' => $this->getDefinedTable(News\NewsChannelTable::class)->getAll(),
		]);
	}
	/**
	 * Delete News Action
	 */
	public function removenewsAction()
	{
		$this->init();

		if ($this->getRequest()->isPost()) {
			$newsTable = $this->getDefinedTable(News\NewsTable::class);
			// Get news record
			$news = $newsTable->get($this->_id);
            //echo '<pre>';print_r($news); exit;
			if ($news) {
				// Build PDF path
				$fileManagerDir = $this->_config['file_manager']['public_dir'];
				$pdfPath = realpath($fileManagerDir)
					. DIRECTORY_SEPARATOR
					. 'news'
					. DIRECTORY_SEPARATOR
					. $news[0]['pdf'];

				// Delete PDF file if exists
				if (!empty($news[0]['pdf']) && file_exists($pdfPath)) {
					unlink($pdfPath);
				}

				// Delete DB record
				$result = $newsTable->remove($this->_id);

				if ($result > 0) {
					$this->flashMessenger()->addMessage(
						"success^ Successfully deleted News Paper"
					);
				} else {
					$this->flashMessenger()->addMessage(
						"notice^ Failed to delete News Paper"
					);
				}

			} else {
				$this->flashMessenger()->addMessage(
					"notice^ News record not found"
				);
			}

			return $this->redirect()->toUrl(
				$this->getRequest()->getHeader('Referer')->getUri()
			);
		}

		$viewModel = new ViewModel([
			'title' => 'Remove News Paper',
			'news'  => $this->getDefinedTable(News\NewsTable::class)->get($this->_id),
		]);

		$viewModel->setTerminal(true);
		return $viewModel;
	}

}
