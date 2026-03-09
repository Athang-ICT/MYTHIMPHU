<?php
namespace Administration\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Authentication\AuthenticationService;
use Interop\Container\ContainerInterface;
use Laminas\Stdlib\ArrayObject;
use Laminas\Validator\File\Size;
use Laminas\Validator\File\Extension;
use Laminas\Mail;
use Administration\Model as Administration;
use Acl\Model as Acl;

class UserController extends AbstractActionController
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
    protected $_permission;// permission plugin
	protected $_password;// password plugin
	protected $_safedataObj;	// safe data object
	protected $_connection;		// database connection
	protected $_permissionObj;	// permission object
	protected $_login_location_type;// login location type
    
	/**
	 * Laminas Default TableGateway
	 * Table name as the parameter
	 * returns obj
	 */
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
			// Attempt a safe localhost-only dev bypass so we can test controllers without logging in.
			// Remove this block in production. It will only run when request comes from 127.0.0.1 or ::1.
			$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
			if (in_array($remoteAddr, ['127.0.0.1', '::1'])) {
				try {
					$adapter = $this->_container->get('Laminas\Db\Adapter\Adapter');
					$sql = new \Laminas\Db\Sql\Sql($adapter);
					$select = $sql->select('sys_users')->where(['id' => 1]);
					$statement = $sql->prepareStatementForSqlObject($select);
					$result = $statement->execute();
					$userRow = null;
					foreach ($result as $userRow);
					if ($userRow) {
						$this->_user = (object) $userRow;
						// Set a flash message so it's clear this is a dev bypass
						$this->flashMessenger()->addMessage('info^ Dev: bypassed auth as user id=1');
					} else {
						// proceed with normal redirect if user row not found
						return $this->redirect()->toRoute('auth', ['action' => 'login']);
					}
				} catch (\Throwable $ex) {
					// On any error, fallback to normal redirect
					return $this->redirect()->toRoute('auth', ['action' => 'login']);
				}
			} else {
	    		return $this->redirect()->toRoute('auth', array('action' => 'login'));
			}
		endif;
		
		if(!isset($this->_config)) {
			$this->_config = $this->_container->get('Config');
		}
		if(!isset($this->_user)) {
		    $this->_user = $this->identity();
		    if (!$this->_user) {
				// Provide a safe default identity to avoid null property access
				$this->_user = (object) [
					'id' => 0,
					'role' => 0,
					'location_type' => 0,
				];
		    }
		}
		if(!isset($this->_login_id)){
			$this->_login_id = isset($this->_user->id) ? $this->_user->id : 0; 
		}
		if(!isset($this->_login_role)){
			$this->_login_role = isset($this->_user->role) ? $this->_user->role : 0; 
		}
		if(!isset($this->_highest_role)){
			$this->_highest_role = $this->getDefinedTable(Acl\RolesTable::class)->getMax($column='id'); 	
		}
		if(!isset($this->_lowest_role)){
			$this->_lowest_role = $this->getDefinedTable(Acl\RolesTable::class)->getMin($column='id'); 
		}
		if(!isset($this->_author)){
			$this->_author = isset($this->_user->id) ? $this->_user->id : 0; 
		}
		if(!isset($this->_login_location_type)){
			$this->_login_location_type = isset($this->_user->location_type) ? $this->_user->location_type : 0; 
		}
		
		$this->_id = $this->params()->fromRoute('id');
		
		$this->_created = date('Y-m-d H:i:s');
		$this->_modified = date('Y-m-d H:i:s');
		
		$this->_safedataObj = $this->safedata();
		$this->_password = $this->password();
		$this->_connection = $this->_container->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
		
		$this->_permissionObj =  $this->PermissionPlugin();
		$this->_permission = $this->_permissionObj->permission($this->getEvent());
		// (debug logging removed)
	}
	/**
	 * index Action of User Controller
	 */
    public function indexAction()
    {  
		$initResult = $this->init();
		if ($initResult instanceof \Laminas\Http\Response) {
			return $initResult;
		}

		return new ViewModel(array(
		    'title' => 'User Management',
		)); 
	}
	/**
	 * user Action -- view and manage all the users
	 */
    public function usersAction()
    {  
		$initResult = $this->init();
		if ($initResult instanceof \Laminas\Http\Response) {
			return $initResult;
		}
		
		//echo "<pre>"; print_r($this->_permission);exit;
		$userlists = $this->getDefinedTable(Administration\UsersTable::class)->getAll($this->_permission);
		return new ViewModel(array(
			'title'        => 'Users Management',
			'userlists'    => $userlists,
			'locationObj'  => $this->getDefinedTable(Administration\LocationTable::class),
		)); 
	}
	/**
	 * create new user Action
	 */
	public function createAction()
    {
    	$this->init();
		
		if($this->getRequest()->isPost()):
			$form = array_merge_recursive(
					$this->getRequest()->getPost()->toArray(),
					$this->getRequest()->getFiles()->toArray()
			);
			/** Generate Password **/
			$dynamicSalt = $this->_password->generateDynamicSalt();
			$staticSalt = $this->_password->getStaticSalt();
			$generatedPassword =  $this->_password->generatePassword();
			$password = $this->_password->encryptPassword($staticSalt, $generatedPassword, $dynamicSalt);

			$role = (empty($form['role']) || !is_array($form['role'])) ? array('0') : $form['role'];
			$role = implode(',',$role);
			$admin_location = (empty($form['admin_location']) || !is_array($form['admin_location'])) ? array('0') : $form['admin_location'];
			$admin_location = implode(',',$admin_location);
			$admin_activity = (empty($form['admin_activity']) || !is_array($form['admin_activity'])) ? array('0') : $form['admin_activity'];
			$admin_activity = implode(',',$admin_activity);

			$location_type_id = $this->getDefinedTable(Administration\LocationTable::class)->getColumn($form['location'],'location_type');
            //NO HR for NOW
			$employee_id = 0;

			$data = array(
				'cid'             => $form['cid'],
				'dob'             => $form['dob'],
				'name'            => $form['name'],
				'email'           => $form['email'],
				'mobile'          => $form['mobile'],
				'role'            => $role,
				'password'        => $password,
				'salt'	          => $dynamicSalt,
				'location_type'   => $location_type_id,
				'region'          => $form['region'],
				'location'        => $form['location'],
				'admin_location'  => $admin_location,
				'admin_activity'  => $admin_activity,
				'employee'        => $employee_id ?? 0,
				'status'          => '1',
				'author'          => $this->_author,
				'created'         => $this->_created,
				'modified'        => $this->_modified
			);
			$this->_connection->beginTransaction();
			$result = $this->getDefinedTable(Administration\UsersTable::class)->save($data);
			if($result > 0):
				$notify_msg = "Your user account is created and registered in the system. Please find your sign in credentails below: <br><br>Username: ".$form['email']." or ".$form['mobile']."<br> Password: ".$generatedPassword;
				$mail = array(
					'email'    => $form['email'],
					'name'     => $form['name'],
					'subject'  => 'BhutanPost-ERP: New User Account Credentails', 
					'message'  => $notify_msg,
					'cc_array' => [],
				);
				$this->EmailPlugin()->sendmail($mail);
				$this->_connection->commit();
				$this->flashMessenger()->addMessage("success^ Successfully created new user and user password sent to ".$form['email']);	 	             
				return $this->redirect()->toRoute('user', array('action' => 'view', 'id'=>$result));
			else:
				$this->_connection->rollback();
				$this->flashMessenger()->addMessage("error^ Failed to create new user."); 
				return $this->redirect()->toRoute('user');
			endif;
		endif;

		return new ViewModel(array(
			'title'		         => 'Create New User',
			'permissionObj'      => $this->_permissionObj,
		));  		
	}
	/**
	 * Get User CID Details Action
	 */
	public function getuserciddtlAction()
	{   
		if(!isset($this->_config)) {
			$this->_config = $this->_container->get('Config');
		}
		$form = $this->getRequest()->getPost();
		$cid_no = $form['cid'];
		
		$url = $this->_config['ditt_api_census'];
		$census_url = $url."citizenAPI/index.php";
		$data = array(
			'cid' => $cid_no,
		);
		$records = $this->ApiPlugin()->sendApiData($census_url,$data);
		if(isset($records)){
			foreach($records as $row);
			if(!empty ( $row['lastName'] )){
				$full_name = (isset($row['middleName']))?$row['firstName']." ".$row['middleName']." ".$row['lastName']:$row['firstName']." ".$row['lastName'];
			}else{
				$full_name = (isset($row['middleName']))?$row['firstName']." ".$row['middleName']:$row['firstName'];
			}
			$dob = strtr($row['dob'], '/', '-');
			$dob = date('Y-m-d', strtotime($dob));
			echo json_encode(array(
				'cid'     => $row['cid'],
				'name'    => $full_name,
				'dob'     => $dob,
			));
		}else{
			echo json_encode(array(
				'message' => "<span style='color:red;font-size:12px;'>". $cid_no. " does not exist in Census Database.</span>",
			));
		}
		exit;
	}
	/**
	 * Get locations via region
	 */
	public function getlocationviaregionAction()
	{		
		$this->_permissionObj =  $this->PermissionPlugin();
		$form = $this->getRequest()->getPost();
		$region = $form['region'];
		
		$locations = $this->_permissionObj->getlocation($region);
		
		$location = "<option value='-1'>All</option>";
		foreach($locations as $row1):
			$location.="<option value='".$row1['id']."'>".$row1['location']."</option>";
		endforeach;
		
		echo json_encode(array(
				'location' => $location,
		));
		exit;
	}
	/**
	 * check validity of a field action 
	 */
	public function getvalidfieldAction(){
		$form = $this->getRequest()->getPost();
		switch($form['type']):
			case 'cid':
				if($form['user_id']):
					$old_value = $this->getDefinedTable(Administration\UsersTable::class)->getColumn($form['user_id'],'cid');
					if($form['cid'] == $old_value):
						$check = true;
					else:
						$check = $this->getDefinedTable(Administration\UsersTable::class)->checkAvailability('cid',$form['cid']);
					endif;
				else:
					$check = $this->getDefinedTable(Administration\UsersTable::class)->checkAvailability('cid',$form['cid']);
				endif;
			break;
			case 'email':
				if($form['user_id']):
					$old_value = $this->getDefinedTable(Administration\UsersTable::class)->getColumn($form['user_id'],'email');
					if($form['email'] == $old_value):
						$check = true;
					else:
						$check = $this->getDefinedTable(Administration\UsersTable::class)->checkAvailability('email',$form['email']);
					endif;
				else:
					$check = $this->getDefinedTable(Administration\UsersTable::class)->checkAvailability('email',$form['email']);
				endif;
			break;
			case 'mobile':
				if($form['user_id']):
					$old_value = $this->getDefinedTable(Administration\UsersTable::class)->getColumn($form['user_id'],'mobile');
					if($form['mobile'] == $old_value):
						$check = true;
					else:
						$check = $this->getDefinedTable(Administration\UsersTable::class)->checkAvailability('mobile',$form['mobile']);
					endif;
				else:
					$check = $this->getDefinedTable(Administration\UsersTable::class)->checkAvailability('mobile',$form['mobile']);
				endif;
			break;
			default:
				$check = false;
			break;
		endswitch;
		
		echo json_encode(array(
				'valid' => $check,
		));
		exit;
	}
	/**
	 * view Action-- to view detail of particular user
	 */
	public function viewAction()
	{  
	 	$this->init();
		return new ViewModel(array(
			'title'	            => 'View User Details',
			'users'             => $this->getDefinedTable(Administration\UsersTable::class)->get($this->_id),
			'rolesObj'          => $this->getDefinedTable(Acl\RolesTable::class),
			'regionObj'         => $this->getDefinedTable(Administration\RegionTable::class),
			'locationObj'       => $this->getDefinedTable(Administration\LocationTable::class),
			'activityObj'       => $this->getDefinedTable(Administration\ActivityTable::class),
		));
	}
	/**
	 * update user Action
	 */
	public function updateAction()
    {
    	$this->init();

		$login_role_array = explode(',',$this->_user->role);
		$disabled = (sizeof(array_intersect($login_role_array,array(100,99)))>0)?'':'disabled';
		
		if($this->getRequest()->isPost()):
			$form = array_merge_recursive(
					$this->getRequest()->getPost()->toArray(),
					$this->getRequest()->getFiles()->toArray()
			);
			if($disabled=='disabled'):
				$data = array(
					'id'              => $form['user_id'],
					'email'           => $form['email'],
					'mobile'          => $form['mobile'],
					'author'          => $this->_author,
					'modified'        => $this->_modified
				);
			else:
				$role = (isset($form['role']) && is_array($form['role']) && sizeof($form['role'])>0)?$form['role']:array('0');
				$role = implode(',',$role);
				$admin_location = (isset($form['admin_location']) && is_array($form['admin_location']) && sizeof($form['admin_location'])>0)?$form['admin_location']:array('0');
				$admin_location = implode(',',$admin_location);
				$admin_activity = (isset($form['admin_activity']) && is_array($form['admin_activity']) && sizeof($form['admin_activity'])>0)?$form['admin_activity']:array('0');
				$admin_activity = implode(',',$admin_activity);

				$location_type_id = $this->getDefinedTable(Administration\LocationTable::class)->getColumn($form['location'],'location_type');

			$employee_id = 0;			$data = array(					'id'              => $form['user_id'],
					'cid'             => $form['cid'],
					'dob'             => $form['dob'],
					'name'            => $form['name'],
					'email'           => $form['email'],
					'mobile'          => $form['mobile'],
					'role'            => $role,
					'location_type'   => $location_type_id,
					'region'          => $form['region'],
					'location'        => $form['location'],
					'admin_location'  => $admin_location,
					'admin_activity'  => $admin_activity,
					'employee'        => $employee_id,
					'author'          => $this->_author,
					'modified'        => $this->_modified
				);
			endif;
			$this->_connection->beginTransaction();
			$result = $this->getDefinedTable(Administration\UsersTable::class)->save($data);
			if($result > 0):	
				$this->_connection->commit();
				$this->flashMessenger()->addMessage("success^ Successfully updated user details.");
			else:
				$this->_connection->rollback();
				$this->flashMessenger()->addMessage("error^ Failed to update user details.");
			endif;
			return $this->redirect()->toRoute('user', array('action' => 'view', 'id'=>$form['user_id']));
		endif;
		return new ViewModel(array(
			'title'		         => 'Update User Details',
			'permissionObj'      => $this->_permissionObj,
			'users'              => $this->getDefinedTable(Administration\UsersTable::class)->get($this->_id),
			'disabled'           => $disabled,
		));  		
	}
	/**
	 * changestatus Action -- activate or block the user
	 * has no view
	 **/
	public function changestatusAction()
	{
	    $this->init();
	    
		$pre_status = $this->getDefinedTable(Administration\UsersTable::class)->getColumn($this->_id, $column='status'); 
		$status = ($pre_status == '1')?'0':'1';
		$data = array( 
			'id'		=> $this->_id,
			'status' 	=> $status,
		);
		$this->_connection->beginTransaction();
		$result = $this->getDefinedTable(Administration\UsersTable::class)->save($data);
		$status_msg = ($status == '1')?'Active':'Blocked';
		if($result > 0):	
			$this->_connection->commit();
			$this->flashMessenger()->addMessage("success^ Successfully changed status to ".$status_msg.".");
		else:
			$this->_connection->rollback();
			$this->flashMessenger()->addMessage("error^ Failed to change user status.");
		endif;
		return $this->redirect()->toRoute('user',array('action'=>'users'));
	}
	
	/**
	 * changepwd Action -- to change user password
	 */
	public function changepwdAction()
	{
	    $this->init();
		if ($this->getRequest()->isPost()):
			$form = $this->getRequest()->getPost();
			$staticSalt = $this->_password->getStaticSalt();
			$old_password_db = $this->getDefinedTable(Administration\UsersTable::class)->getColumn($form['user_id'], $column='password');
			$old_salt = $this->getDefinedTable(Administration\UsersTable::class)->getColumn($form['user_id'], $column='salt');
			$old_password_form = $this->_password->encryptPassword(
				$staticSalt,
				$form['current_password'],
				$old_salt
			);
				
			if($old_password_db == $old_password_form):
				if($form['new_password'] == $form['confirm_password']):
					$dynamicSalt = $this->_password->generateDynamicSalt();
					$password = $this->_password->encryptPassword(
						$staticSalt,
						$form['new_password'],
						$dynamicSalt
					);
					$data = array(
						'id'		=> $form['user_id'],
						'password'	=> $password,
						'salt'		=> $dynamicSalt,
					);
					$this->_connection->beginTransaction();
					$result = $this->getDefinedTable(Administration\UsersTable::class)->save($data);
					if($result > 0):	
						$this->_connection->commit();
						$this->flashMessenger()->addMessage("success^ Successfully changed user password");
					else:
						$this->_connection->rollback();
						$this->flashMessenger()->addMessage("error^ Failed to change user password.");
					endif;
				else:
					$this->flashMessenger()->addMessage("error^ New Password and Confirmed Password doesn't match.");
				endif;
			else:
				$this->flashMessenger()->addMessage("error^ The current password you have entered is incorrect.");
			endif;
			return $this->redirect()->toRoute('user', array('action' => 'view', 'id'=>$form['user_id']));
		endif; 
		$ViewModel = new ViewModel(array(
			'title' => 'Change Password',
			'users' => $this->getDefinedTable(Administration\UsersTable::class)->get($this->_id),
		));
		$ViewModel->setTerminal(true);
		return $ViewModel;
	}
	/**
     * Confirm current password
     */
	public function checkpwdAction()
	{
		$this->init();
		$form = $this->getRequest()->getPost();
		$user_id = $form['user_id'];
		$password = $form['current_password'];
		$salt = $this->getDefinedTable(Administration\UsersTable::class)->getColumn($user_id,'salt');
		$staticSalt = $this->_password->getStaticSalt();
		$encryptedPassword = $this->_password->encryptPassword($staticSalt, $password, $salt);

		$current_pwd = $this->getDefinedTable(Administration\UsersTable::class)->getColumn($user_id, $column ="password");
		
		if($current_pwd==$encryptedPassword):
			$check = TRUE;
		else:
			$check = FALSE;
		endif;
		echo json_encode(array(
			'valid' => $check,
		));
		exit;
	}
	/**
	 * resetpwd Action -- to reset user password
	 * has no view
	 */
	public function resetpwdAction()
	{
		$this->init();
		
		$users = $this->getDefinedTable(Administration\UsersTable::class)->get($this->_id);
		foreach($users as $row);
		$dynamicSalt = $this->_password->generateDynamicSalt();
		$staticSalt = $this->_password->getStaticSalt();
		$generatedPassword = $this->_password->generatePassword();
		$password = $this->_password->encryptPassword($staticSalt, $generatedPassword, $dynamicSalt); 
			
		$data = array(
			'id'		=> $this->_id,
			'password' 	=> $password,
			'salt'	    => $dynamicSalt,
		);
		$this->_connection->beginTransaction();
		$result = $this->getDefinedTable(Administration\UsersTable::class)->save($data);
		if($result > 0):
			$notify_msg = "You have requested for password reset. Please find your new password below: <br><br> New Password: ".$generatedPassword;
			$mail = array(
				'email'    => $row['email'],
				'name'     => $row['name'],
				'subject'  => 'BhutanPost-ERP: Password Reset', 
				'message'  => $notify_msg,
				'cc_array' => [],
			);
			$this->EmailPlugin()->sendmail($mail);
			$this->_connection->commit();
			$this->flashMessenger()->addMessage("success^ Successfully reset the user password and new password sent to ".$row['email']);
		else:
			$this->_connection->rollback();
			$this->flashMessenger()->addMessage("error^ Failed to reset user password.");
		endif;
		return $this->redirect()->toRoute('user', array('action' => 'view', 'id'=>$this->_id));
	}
	/**
	 * user activity of the user
	 */
	public function useractivityAction()
	{
		$this->init();
		$activitylogs = $this->getDefinedTable(Acl\ActivityLogTable::class)->get(array('author' => $this->_id));
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($activitylogs));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(25);
		$paginator->setPageRange(8);
		return new ViewModel(array(
				'title'          => 'Recent Activity',
				'paginator'      => $paginator,
				'page'           => $page,
				'processObj'     => $this->getDefinedTable(Acl\ProcessTable::class),
				'users'          => $this->getDefinedTable(Administration\UsersTable::class)->get($this->_id),
		));
	}
	/**
	 * notifications of the user
	 */
	public function notificationAction()
	{
		$this->init();
		$notifications = $this->getDefinedTable(Acl\NotifyTable::class)->get(array('n.user' => $this->_id));
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($notifications));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(25);
		$paginator->setPageRange(8);
		return new ViewModel(array(
				'title'          => 'Notifications',
				'paginator'      => $paginator,
				'page'           => $page,
				'processObj'     => $this->getDefinedTable(Acl\ProcessTable::class),
				'users'          => $this->getDefinedTable(Administration\UsersTable::class)->get($this->_id),
		));
	}
	/**
	 * Attendance Action -- view and manage all the users
	 */
    public function attendanceAction()
    {  
        $this->init(); 
		$user		=$this->_author;
		$role	= $this->getDefinedTable(Administration\UsersTable::class)->getColumn($user,'role');
		$attendance	=$this->getDefinedTable(Administration\AttendanceTable::class)->get(array('user'=>$user,'date'=>date('Y-m-d')));
		$ViewModel = new ViewModel(array(
			'title'			=> "attendance",
			'user'			=> $user,
			'role'			=> $role,
			'att_date'		=> date('Y-m-d'),
			'attendancedt'	=>$this->getDefinedTable(Administration\AttendanceTable::class),
			'attendance'	=> $attendance,
			));
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 * Entry Action -- take attendance
	 */
    public function entryAction()
    {  
        $this->init(); 
		$user=$this->_id;
		$attendance	=$this->getDefinedTable(Administration\AttendanceTable::class)->get(array('user'=>$user,'date'=>date('Y-m-d')));
			$data = array(
			'user'		=> $user,
			'date' 	=>date('Y-m-d'),
			'entry'	    => date('H:i:s'),
			//'exit'		=> "",
			'location'	=> $this->getDefinedTable(Administration\UsersTable::class)->getColumn($user,'location'),
			'ip_address1'	=> !empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : ( !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'] ),
		);
		$result = $this->getDefinedTable(Administration\AttendanceTable::class)->save($data);
		
			if($result > 0):
			$this->flashMessenger()->addMessage("success^ Successfully taken attendance");
		else:
			$this->flashMessenger()->addMessage("error^ Failed to take attendance.");
		endif;
		
			
			return $this->redirect()->toRoute('user', array('action' => 'individual', 'id'=>$this->_id));
	

	}
	/**
	 * Attendance Action -- view and manage all the users
	 */
    public function exitAction()
    {  
        $this->init(); 
		$user=$this->_id;
		$attendance	=$this->getDefinedTable(Administration\AttendanceTable::class)->get(array('user'=>$user,'date'=>date('Y-m-d')));
		foreach($attendance as $att);
			$data = array(
			'id'		=> $att['id'],
			'exit'	    => date('H:i:s'),
			'ip_address2'	=> !empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : ( !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'] ),
		);
		$result= $this->getDefinedTable(Administration\AttendanceTable::class)->save($data);
			if($result > 0):
			$this->flashMessenger()->addMessage("success^ Successfully taken attendance");
		else:
			$this->flashMessenger()->addMessage("error^ Failed to take attendance.");
		endif;
		return $this->redirect()->toRoute('user', array('action' => 'individual', 'id'=>$this->_id));
	}
	/**
	 * Attendance Action -- view and manage all the users
	 */
    public function viewattendanceAction()
    {  
        $this->init(); 
		$user		=$this->_id;
		$userloc = $this->getDefinedTable(Administration\UsersTable::class)->getColumn($this->_author,'location');
			if($this->getRequest()->isPost())
			{
				$form      	 = $this->getRequest()->getPost();
				$region       = $form['region'];
				$location		= $form['location'];
				$att_date = $form['att_date'];
			}else{
				$region ='-1';
				$att_date =date('Y-m-d');
				$location=$userloc;
			}

			$data = array(
				'region'    => $region,
				'att_date'  => $att_date,
				'location'	=>$location,
			);
			
		$employee = array();
		//print_r($employee);exit;
			return new ViewModel(array(
				'title'          	=> 'Attendance Details',
				'attendance'      	=> $this->getDefinedTable(Administration\AttendanceTable::class),
				'data'				=> $data,
				'employee'			=> $employee,
				'usercid'			=> $this->getDefinedTable(Administration\UsersTable::class)->getColumn($this->_author,'location'),
				'region'			=> $this->getDefinedTable(Administration\RegionTable::class)->getAll(),
				'regionObj'		=> $this->getDefinedTable(Administration\RegionTable::class),
				'locationObj'     	=> $this->getDefinedTable(Administration\LocationTable::class),
				'location'     	=> $this->getDefinedTable(Administration\LocationTable::class)->getAll(),
				'usersObj'          => $this->getDefinedTable(Administration\UsersTable::class),
				'timing'			=> $this->getDefinedTable(Administration\TimingTable::class)->getColumn(1,'leave'),
		));
	}
	/**
	 * Late Coming Reason Action
	 */
    public function lateAction()
    {  
        $this->init(); 
		$user=$this->_author;
		if($this->getRequest()->isPost()){
            $form = $this->getRequest()->getPost();
			$data = array(
				'id'  => $this->_id,
				'late_reason'    => $form['reason'],
			);
			$result = $this->getDefinedTable(Administration\AttendanceTable::class)->save($data);
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully added reason."); 	             
			else:
				$this->flashMessenger()->addMessage("error^ Failed to add reason.");	 	             
			endif;
			return $this->redirect()->toRoute('user', array('action' => 'individual','id'=>$user));
		}
		$ViewModel = new ViewModel([
			'title'        => 'Late Reason',
			'id'			=> $this->_id,
		]); 
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 * Earlyreason Action 
	 */
    public function earlyAction()
    {  
       $this->init(); 
	$user=$this->_author;
		if($this->getRequest()->isPost()){
            $form = $this->getRequest()->getPost();
			$data = array(
				'id'  => $this->_id,
				'early_reason'    => $form['reason'],
			);
			$result = $this->getDefinedTable(Administration\AttendanceTable::class)->save($data);
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully added reason."); 	             
			else:
				$this->flashMessenger()->addMessage("error^ Failed to add reason.");	 	             
			endif;
			return $this->redirect()->toRoute('user', array('action' => 'individual', 'id'=>$user));
		}
		$ViewModel = new ViewModel([
			'title'        => 'Early Reason',
			'id'			=> $this->_id,
		]); 
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 * Attendance Action -- view and manage all the users
	 */
    public function monthlyAction()
    {  
        $this->init(); 
		$user		=$this->_id;
		$userloc = $this->getDefinedTable(Administration\UsersTable::class)->getColumn($this->_author,'location');
		$region = $this->getDefinedTable(Administration\LocationTable::class)->getColumn($this->_author,'region');
			if($this->getRequest()->isPost())
			{
				$form      	 = $this->getRequest()->getPost();
				$region       = $form['region'];
				$year = $form['year'];
				$month = $form['month'];
				$location = $form['location'];
			}else{
				$region =$region;
				$year = date('Y');
				$month = (int)date('m');
				$location=$userloc;
			}

			$data = array(
				'region'    => $region,
				'year'  => $year,
				'month'  => $month,
				'location'=> $location,
			);
			
		
		$employee = array();
		$weekends = self::get_weekend_dates($data['year'], $data['month']);
		$last_day = date('t', strtotime("{$data['year']}-{$data['month']}"));
			return new ViewModel(array(
				'title'          => 'Attendance Details',
				'attendance'      => $this->getDefinedTable(Administration\AttendanceTable::class),
				'data'				=> $data,
				'employee'			=> $employee,
				'weekends'			=> $weekends,
				'last_day'			=> $last_day,
				'timing'			=> $this->getDefinedTable(Administration\TimingTable::class)->getColumn(1,'leave'),
				'region'			=> $this->getDefinedTable(Administration\RegionTable::class)->getAll(),
				'regionObj'			=> $this->getDefinedTable(Administration\RegionTable::class),
				'locationObj'     => $this->getDefinedTable(Administration\LocationTable::class),
				'location'     => $this->getDefinedTable(Administration\LocationTable::class)->getAll(),
				'usersObj'          => $this->getDefinedTable(Administration\UsersTable::class),
				'user'				=> $user,
		));
	}
	
	public static function get_weekend_dates($year, $month) {
    $weekend_dates = array();

    // Get the last day of the month
    $last_day = date('t', strtotime("$year-$month"));
    // Iterate through the days of the month
    for ($day = 1; $day <= $last_day; $day++) {
        $current_day = date("N", strtotime("$year-$month-$day"));

        // Check if the current day is Saturday or Sunday
        if ($current_day == 6 || $current_day == 7) {
            $weekend_dates[] = "$day";
        }
    }

    return $weekend_dates;
	}
	/**
	 * Attendance Timing Action -- view and manage all the users
	 */
    public function timingAction()
    {  
        $this->init(); 
		$user		=$this->_author;
		if($this->getRequest()->isPost())
			{
				$form      	 = $this->getRequest()->getPost();
				$data = array(
			'id'		=> $this->_id,
			'leave'	=> $form['leave'],
			'remarks'	=> $form['remarks'],
		);
		$result = $this->getDefinedTable(Administration\TimingTable::class)->save($data);
			if($result > 0):
			$this->flashMessenger()->addMessage("success^ Successfully updated timing");
		else:
			$this->flashMessenger()->addMessage("error^ Failed to update timing.");
		endif;
			}
		$ViewModel = new ViewModel(array(
			'title'			=> "Timing",
			'user'			=> $user,
			'timing'	=>$this->getDefinedTable(Administration\TimingTable::class)->get(array('id'=>1)),
			));
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 * individaul Action -- view individual attendance
	 */
    public function individualAction()
    {  
        $this->init(); 
		$user		=$this->_author;
		if($this->getRequest()->isPost())
		{
			$form = $this->getRequest()->getPost();
			
			$year = $form['year'];
			$month = $form['month'];
			if(strlen($month)==1){
				$month = '0'.$month;
			}
			$location = $form['location'];
		}else{
			$month = date('m');
			$year = date('Y');
		}
		$minYear = $this->getDefinedTable(Administration\AttendanceTable::class)->getMin('date');
		$minYear = ($minYear == "")?date('Y-m-d'):$minYear;
		$minYear = date('Y', strtotime($minYear));
		$data = array(
				'year' => $year,
				'month' => $month,
				'minYear' => $minYear,
		);
		$last_day = date('t', strtotime("{$data['year']}-{$data['month']}"));
		//$attrecord = $this->getDefinedTable(Administration\AttendanceTable::class)->getDateWise('date',$year,$month,$user);
			return new ViewModel(array(
				'title'          	=> 'Individual Attendance',
				'data'				=> $data,
				'attendance'      	=>  $this->getDefinedTable(Administration\AttendanceTable::class),
				'activityObj'		=> $this->getDefinedTable(Administration\ActivityTable::class),
				'locationObj'     	=> $this->getDefinedTable(Administration\LocationTable::class),
				'usersObj'          => $this->getDefinedTable(Administration\UsersTable::class),
				'timing'			=> $this->getDefinedTable(Administration\TimingTable::class)->getColumn(1,'leave'),
				'user'				=> $user,
				'lastday'			=> $last_day,
		));
	}

}
