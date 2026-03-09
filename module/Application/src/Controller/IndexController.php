<?php
/**
 * chophel@athang.com
 * @see       https://github.com/laminas/laminas-mvc-skeleton for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc-skeleton/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc-skeleton/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Interop\Container\ContainerInterface;
use Laminas\Authentication\AuthenticationService;
use Laminas\Http\Response;
use Acl\Model as Acl;
use Awpb\Model as Awpb;
use News\Model as News;
use Administration\Model as Administration;
use Laminas\Http\Client;
use Laminas\Http\Request;
use Auth\Service\SSOService;
use Laminas\Session\Container;

class IndexController extends AbstractActionController
{
    private   $_container;
	protected $_table; 		// database table 
    protected $_user; 		// user detail
    protected $_login_id; 	// logined user id
    protected $_login_role; // logined user role
	protected $_login_location; // logined user location
    protected $_author; 	// logined user id
    protected $_created; 	// current date to be used as created dated
    protected $_modified; 	// current date to be used as modified date
    protected $_config; 	// configuration details
    protected $_dir; 		// default file directory
    protected $_id; 		// route parameter id, usally used by crude
    protected $_auth; 		// checking authentication
    protected $_highest_role;// highest user role
    protected $_lowest_role;// loweset user role

    public function __construct(ContainerInterface $container)
    {
        $this->_container = $container;
    }
	/**
	 * Zend Default TableGateway
	 * Table name as the parameter
	 * returns obj
	 */
	public function getDefaultTable($table)
	{
		$this->_table = new TableGateway($table, $this->_container->get('Laminas\Db\Adapter\Adapter'));
		return $this->_table;
	}
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
		$action = $this->params()->fromRoute('action');
		if ($action === 'panel4') {
			return; // skip login check
		}

		$this->_auth = new AuthenticationService;
		if(!$this->_auth->hasIdentity()):
			$this->flashMessenger()->addMessage('error^ You dont have right to access this page!');
			return $this->redirect()->toRoute('application', array('action' => 'portal'));
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
		if(!isset($this->_login_location)){
			$this->_login_location = $this->_user->location; 
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
	}

    public function indexAction()
    {
		$resp = $this->init();
		if ($resp instanceof Response) {
			return $resp;
		}
		//echo '<pre>';print_r($this->_login_role);exit;
		switch($this->_login_role){
			case 2:
				case 3:
					case 4: return $this->redirect()->toRoute('application', array('action'=>'panel2'));
					break;
			case 5:
				case 6:
					case 7:
						case 8: return $this->redirect()->toRoute('application', array('action'=>'panel1'));
						break;
			case 99:
				case 100: return $this->redirect()->toRoute('application', array('action'=>'panel1'));
						break;
			default: return $this->redirect()->toRoute('application', array('action'=>'panel1'));
					break;
		}
    }
	/**
	 * Admin Panel -- Administrator & System Manager
	 */
	public function panel1Action()
	{
		$resp = $this->init();
		if ($resp instanceof Response) {
			return $resp;
		}
		return new ViewModel(array(
			'title'             => 'Admin Dashbord',
            'menus'             => $this->getDefinedTable(Acl\AclTable::class)->renderDashboard($this->_user,$this->_highest_role),
			'logintraffics'     => $this->getDefinedTable(Acl\LoginsTable::class)->getLoginTraffic(),
			'districtObj'       => $this->getDefinedTable(Administration\DistrictTable::class), 
			'locationObj'       => $this->getDefinedTable(Administration\LocationTable::class), 
		));
	}
	/**
	 * Admin Panel -- Administrator & System Manager
	 */
	public function panel2Action()
	{
		$resp = $this->init();
		if ($resp instanceof Response) {
			return $resp;
		}
		return new ViewModel(array(
			'title'             => 'My Dashboard',
			'author'            => $this->_author,
			'userObj'           => $this->getDefinedTable(Administration\UsersTable::class), 
		));
	}
	/**
	 * M&E Panel 
	 */
	public function panel3Action()
	{
		$resp = $this->init();
		if ($resp instanceof Response) {
			return $resp;
		}
		return new ViewModel(array(
			'title'             => 'Dashboard-Panel-3',
		));
	}
	/**
	 * User Panel
	 */
	public function panel4Action()
	{
		$resp = $this->init();
		if ($resp instanceof Response) {
			return $resp;
		}
		return new ViewModel(array(
			'title'             => 'Dashboard-Panel-4',
		));
	}
	/**
	 * Web Portal -- 
	 */
	public function portalAction()
	{
		//$this->init(); // portal is accessible without login
		$this->layout('layout/portal');
		
		$newsChannels = $this->getDefinedTable(News\NewsChannelTable::class)->getAll();
		$newsTable = $this->getDefinedTable(News\NewsTable::class);
		
		// Get latest news for each channel type
		$latestNewsByChannel = array();
		foreach($newsChannels as $channel) {
			$latestNews = $newsTable->getLatestByChannel($channel['id'], 1);
			if(!empty($latestNews)) {
				$latestNewsByChannel[$channel['id']] = $latestNews[0];
			}
		}
		
		// Get all news items for search
		$allNews = $newsTable->getAll();
		
		return new ViewModel(array(
			'title'             => 'My Dashboard',
			'news'              => $newsChannels,
			'latestNewsByChannel' => $latestNewsByChannel,
			'allNews'           => $allNews,
		));
	}
	/**
	 * Activity Log
	 */
	public function activitylogAction()
	{
		$resp = $this->init();
		if ($resp instanceof Response) {
			return $resp;
		}
		
		$id = $this->params()->fromRoute('id');	
		$params = explode("-", $id);
		$process = $params['0'];
		$process_id = $params['1'];		
		$activitylogs = $this->getDefinedTable(Acl\ActivityLogTable::class)->get(array('process'=>$process, 'process_id'=>$process_id));		
		
		$viewModel =  new ViewModel(array(
				'title'        => 'Activity Logs',
				'activitylogs' => $activitylogs,
				'usersObj'     => $this->getDefinedTable(Administration\UsersTable::class),
				'roleObj'      => $this->getDefinedTable(Acl\RolesTable::class),
		));
		$viewModel->setTerminal('false');
		return $viewModel;
	}
	/**
	 * Documentation
	 * User Manual
	 */
	public function documentationAction()
	{
		$resp = $this->init();
		if ($resp instanceof Response) {
			return $resp;
		}
		return new ViewModel(array(
			'title' => 'Documentation',
		));
	}
	/**
	 *  DITT API
	 */
	public function censusAction()
	{
		$resp = $this->init();
		if ($resp instanceof Response) {
			return $resp;
		}
		
		if(!$this->_auth->hasIdentity()):
			$this->flashMessenger()->addMessage('error^ You dont have right to access this page!');
         	$this->redirect()->toRoute('auth', array('action' => 'login'));
		endif;
		$census_records = array();
		$family_records = array();
		if($this->getRequest()->isPost()){	
			$form = $this->getRequest()->getPost();
			
			$cid_no = $form['cidnumber'];
		    $url = $this->_config['ditt_api_census'];
			$census_url = $url."citizenAPI/index.php";
			
			$data = array(
				'cid' => $cid_no,
			);
			
			$census_records = $this->ApiPlugin()->sendApiData($census_url,$data);
			
			foreach($census_records as $census_record);
			//echo "<pre>";print_r($census_record);
			
			$url = $this->_config['ditt_api_census'];
			$houseHoldNum = $census_record['householdNo'];
		    $family_url = $url."familyAPI/index.php";
			$family_data = array(
				'house_hold_no' => $houseHoldNum,
			);
			
			$family_records = $this->ApiPlugin()->sendApiData($family_url,$family_data);
			//echo "<pre>";print_r($family_records);exit;
		}
		return new ViewModel(array(
			'title'	=> 'Check Census',
			'censusDetails' => $census_records,
			'familyDetails' => $family_records,
		));
	}
	public function allnotificationAction(){
        $this->init();
        //$params = explode("-", $this->_id);
        // if(sizeof($params) ==2 ){
        //     $flag = $this->getDefinedTable(NotifyTable::class)->getColumn($params['1'], 'flag');
        //     if($flag == "0") {
        //         $notify = array('id' => $params['1'], 'flag'=>'1');
        //            $this->getDefinedTable(NotifyTable::class)->save($notify);
        //     }
        // }
        if(!$this->identity()){
              $this->flashMessenger()->addMessage("notice^ Please Login to view notification");
              return $this->redirect()->toRoute('auth', array('action' => 'login'));
        }
        $viewModel =  new ViewModel(array(
            'notifyObj'          => $this->getDefinedTable(Acl\NotifyTable::class),
            'notificationsObj'   => $this->getDefinedTable(Acl\NotificationTable::class),
            'userID'             => $this->_author,
            'userObj'            => $this->getDefinedTable(Administration\UsersTable::class),
        ));
        $viewModel->setTerminal(false);
        return  $viewModel;
    }

	/**
	 * Dashboard -- End Users-SSO
	 */
	public function dashboardAction()
	{ 
		
		return new ViewModel(array(
			'title'             => 'Dashboard',
			'invObj'            => $this->getDefinedTable(Heritage\HeritageTable::class),
			'districtObj'      	=> $this->getDefinedTable(Administration\DistrictTable::class),
			'categoryObj'       => $this->getDefinedTable(Heritage\CategoryTable::class),
			'identityObj'       => $this->getDefinedTable(Heritage\IdentityTable::class),
			'levelObj'          => $this->getDefinedTable(Heritage\LevelTable::class),
			'sso_login_url' => $this->ssoService->getSSOLoginURL(),
		));
	}
}
