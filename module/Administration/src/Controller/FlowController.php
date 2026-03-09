<?php
namespace Administration\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Authentication\AuthenticationService;
use Laminas\Mvc\MvcEvent;
use Interop\Container\ContainerInterface;
use Administration\Model as Administration;
use Acl\Model as Acl;

class FlowController extends AbstractActionController
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
	protected $_safedataObj;
    protected $_connection;
    protected $_permissionObj;
    
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
	 * index Action of MasterController
	 */
    public function indexAction()
    {  
    	$this->init(); 
		
        return new ViewModel([
			'title' => 'Administration Menu',
		]);
	}
	/**
	 *  flow action
	 */
	public function flowAction()
	{
		$this->init();
		$flowTable = $this->getDefinedTable(Administration\FlowTable::class)->getAll();
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($flowTable));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'            => 'Work Flow',
			'paginator'        => $paginator,
			'page'             => $page,
			'processObj'       => $this->getDefinedTable(Acl\ProcessTable::class),
			'roleObj'          => $this->getDefinedTable(Acl\RolesTable::class),
		)); 
	}
	/**
	 * addflow action
	 */
	public function addflowAction()
	{
		$this->init();
		$page = $this->_id;
		if($this->getRequest()->isPost()){
            $form = $this->getRequest()->getPost();
			$max_code = $this->getDefinedTable(Administration\DistrictTable::class)->getMax('district_code');
			$max_code = $max_code+1;
			$num_length = strlen((string)$max_code);
			if($num_length == 1){$max_code = '0'.$max_code;}
            $data = array(  
				'flow'          => $form['flow'],
				'process'       => $form['process'],
				'role'          => $form['role'],
				'description'   => $form['description'],
				'status'        => $form['status'],
				'author'        => $this->_author,
				'created'       => $this->_created,
				'modified'      => $this->_modified,
            );
            $data = $this->_safedataObj->rteSafe($data);
			$this->_connection->beginTransaction();
            $result = $this->getDefinedTable(Administration\FlowTable::class)->save($data);
            if($result):
				$this->_connection->commit();
                $this->flashMessenger()->addMessage("success^ successfully added new work flow");
            else:
				$this->_connection->rollback();
                $this->flashMessenger()->addMessage("error^ Failed to add new work flow");
            endif;
			return $this->redirect()->toRoute('flow/paginator', array('action'=>'flow','page'=>$this->_id, 'id'=>'0'));
        }		
		$ViewModel = new ViewModel([
				'title'     => 'Add Work Flow',
				'page'      => $page,
				'processes' => $this->getDefinedTable(Acl\ProcessTable::class)->getAll(),
				'roles'     => $this->getDefinedTable(Acl\RolesTable::class)->getAllExcept([1,99,100]),
		]);
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 * editflow action
	 */
	public function editflowAction()
	{
		$this->init();
		$id = $this->_id;
		$array_id = explode("_", $id);
		$flow_id = $array_id[0];
		$page = (sizeof($array_id)>1)?$array_id[1]:'';
		
		if($this->getRequest()->isPost()){
            $form = $this->getRequest()->getPost();
            $data = array(  
				'id'            => $form['flow_id'],
				'flow'          => $form['flow'],
				'process'       => $form['process'],
				'role'          => $form['role'],
				'description'   => $form['description'],
				'status'        => $form['status'],
				'author'        => $this->_author,
				'modified'      => $this->_modified,
            );
            $data = $this->_safedataObj->rteSafe($data);
			$this->_connection->beginTransaction();
            $result = $this->getDefinedTable(Administration\FlowTable::class)->save($data);
            if($result > 0):
				$this->_connection->commit();
                $this->flashMessenger()->addMessage("success^ successfully edited work flow.");
            else:
				$this->_connection->rollback();
                $this->flashMessenger()->addMessage("error^ Failed to edit work flow.");
            endif;
			return $this->redirect()->toRoute('flow/paginator', array('action'=>'flow','page'=>$this->_id, 'id'=>'0'));
        }		
		$ViewModel = new ViewModel([
				'title'     => 'Edit Work Flow',
				'page'      => $page,
				'flows'     => $this->getDefinedTable(Administration\FlowTable::class)->get($flow_id),
				'processes' => $this->getDefinedTable(Acl\ProcessTable::class)->getAll(),
				'roles'     => $this->getDefinedTable(Acl\RolesTable::class)->getAllExcept([1,99,100]),
		]);
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 * Flow Privilege Action
	 */
	public function flowprivilegeAction()
	{
		$this->init();
		$id = $this->_id;
		$array_id = explode("_", $id);
		$flow_id = $array_id[0];
		$page = (sizeof($array_id)>1)?$array_id[1]:'';

		$privilegeTable = $this->getDefinedTable(Administration\FlowPrivilegeTable::class)->get(array('flow'=>$flow_id));
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($privilegeTable));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(10);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'         => 'Flow Privilege',
			'paginator'     => $paginator,
			'page'          => $page,
			'flows'         => $this->getDefinedTable(Administration\FlowTable::class)->get($flow_id),
			'flowactionObj' => $this->getDefinedTable(Administration\FlowActionTable::class),  
			'roleObj'       => $this->getDefinedTable(Acl\RolesTable::class), 
			'processObj'    => $this->getDefinedTable(Acl\ProcessTable::class), 
		)); 
	}
	/**
	 * add flow privilege Action
	 */
    public function addflowprivilegeAction()
    {
		$this->init();
		$id = $this->_id;
		$array_id = explode("_", $id);
		$flow_id = $array_id[0];
		$page = (sizeof($array_id)>1)?$array_id[1]:'';

		if($this->getRequest()->isPost()){
            $form = $this->getRequest()->getPost();
			$button = "";
			$count = 0;
			if(isset($form['action']) && is_array($form['action'])){
				foreach($form['action'] as $btn_action):
					$button .= ($count != sizeof($form['action'])-1)?$btn_action."|": $btn_action;
					$count++;
				endforeach;
			}
			$notification = "";
			$count_route = 0;
			if(isset($form['route_notification_to']) && is_array($form['route_notification_to'])){
				foreach($form['route_notification_to'] as $route_notification):
					$notification .= ($count_route != sizeof($form['route_notification_to'])-1)? $route_notification."|": $route_notification;
					$count_route++;
				endforeach;
			}
			$data = array(	
					'flow'                  => $form['flow_id'],
					'action_performed'      => $form['action_performed'],
					'status_changed_to'     => $form['status_changed_to'],
					'route_to_role'         => $form['route_to_role'],
					'action'                => $button,
					'description'           => $form['description'],
					'route_notification_to' => $notification,
					'notification'          => $form['notification'],
					'email_notify'          => $form['email_notify'],
					'author'                => $this->_author,
					'created'               => $this->_created,
					'modified'              => $this->_modified,
			);
			$result = $this->getDefinedTable(Administration\FlowPrivilegeTable::class)->save($data);
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully added new flow privilege."); 	             
			else:
				$this->flashMessenger()->addMessage("error^ Failed to add new flow privilege.");	 	             
			endif;
			return $this->redirect()->toRoute('flow/paginator', array('action' => 'flowprivilege', 'page'=>$this->_id, 'id'=>$form['flow_id']));
		}
		$ViewModel = new ViewModel([
			'title'        => 'Add Flow Privilege',
			'page'         => $page,
			'flow_id'      => $flow_id,
			'statuses'     => $this->getDefinedTable(Acl\StatusTable::class)->getAll(),
			'actions'      => $this->getDefinedTable(Administration\FlowActionTable::class)->getAll(),
			'roles'        => $this->getDefinedTable(Acl\RolesTable::class)->getAllExcept([1,99,100]),
		]); 
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 * edit flow privilege Action
	 */
    public function editflowprivilegeAction()
    {
		$this->init();
		$id = $this->_id;
		$array_id = explode("_", $id);
		$flow_id = $array_id[0];
		$privilege_id = (sizeof($array_id)>1)?$array_id[1]:'';
		$page = (sizeof($array_id)>2)?$array_id[2]:'';

		if($this->getRequest()->isPost()){
            $form = $this->getRequest()->getPost();
			$button = "";
			$count = 0;
			if(isset($form['action']) && is_array($form['action'])){
				foreach($form['action'] as $btn_action):
					$button .= ($count != sizeof($form['action'])-1)?$btn_action."|":$btn_action;
					$count++;
				endforeach;
			}
			$notification = "";
			$count_route = 0;
			if(isset($form['route_notification_to']) && is_array($form['route_notification_to'])){
				foreach($form['route_notification_to'] as $route_notification):
					$notification .= ($count_route != sizeof($form['route_notification_to'])-1)?$route_notification."|":$route_notification;
					$count_route++;
				endforeach;
			}
			$data = array(	
					'id'                    => $form['privilege_id'],
					'flow'                  => $form['flow_id'],
					'action_performed'      => $form['action_performed'],
					'status_changed_to'     => $form['status_changed_to'],
					'route_to_role'         => $form['route_to_role'],
					'action'                => $button,
					'description'           => $form['description'],
					'route_notification_to' => $notification,
					'notification'          => $form['notification'],
					'email_notify'          => $form['email_notify'],
					'author'                => $this->_author,
					'modified'              => $this->_modified,
			);
			$result = $this->getDefinedTable(Administration\FlowPrivilegeTable::class)->save($data);
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully edited flow privilege."); 	             
			else:
				$this->flashMessenger()->addMessage("error^ Failed to edit flow privilege.");	 	             
			endif;
			return $this->redirect()->toRoute('flow/paginator', array('action' => 'flowprivilege', 'page'=>$this->_id, 'id'=>$form['flow_id']));
		}
		$ViewModel = new ViewModel([
			'title'        => 'Edit Flow Privilege',
			'page'         => $page,
			'privileges'   => $this->getDefinedTable(Administration\FlowPrivilegeTable::class)->get($privilege_id),
			'statuses'     => $this->getDefinedTable(Acl\StatusTable::class)->getAll(),
			'actions'      => $this->getDefinedTable(Administration\FlowActionTable::class)->getAll(),
			'roles'        => $this->getDefinedTable(Acl\RolesTable::class)->getAllExcept([1,99,100]),
		]); 
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 * remove flow privilege Action
	 */
    public function removeflowprivilegeAction()
    {
		$this->init();
		$id = $this->_id;
		$array_id = explode("_", $id);
		$flow_id = $array_id[0];
		$privilege_id = (sizeof($array_id)>1)?$array_id[1]:'';
		$page = (sizeof($array_id)>2)?$array_id[2]:'';

		if($this->getRequest()->isPost()){
            $form = $this->getRequest()->getPost();
			$result = $this->getDefinedTable(Administration\FlowPrivilegeTable::class)->remove($form['privilege_id']);
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully removed flow privilege."); 	             
			else:
				$this->flashMessenger()->addMessage("error^ Failed to remove flow privilege.");	 	             
			endif;
			return $this->redirect()->toRoute('flow/paginator', array('action' => 'flowprivilege', 'page'=>$this->_id, 'id'=>$form['flow_id']));
		}
		$ViewModel = new ViewModel([
			'title'        => 'Remove Flow Privilege',
			'page'         => $page,
			'privileges'   => $this->getDefinedTable(Administration\FlowPrivilegeTable::class)->get($privilege_id),
			'statusObj'    => $this->getDefinedTable(Acl\StatusTable::class),
			'actionObj'    => $this->getDefinedTable(Administration\FlowActionTable::class),
			'roleObj'      => $this->getDefinedTable(Acl\RolesTable::class),
		]); 
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**  
	 * flowaction action
	 */
	public function flowactionAction()
	{	
		$this->init();

		$actionTable = $this->getDefinedTable(Administration\FlowActionTable::class)->getAll();
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($actionTable));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'         => 'Flow Action',
			'paginator'     => $paginator,
			'page'          => $page,
		)); 
	} 
	/**
	 *  AddFlowAction action
	 */
	public function addflowactionAction()
	{
		$this->init();
		$page = $this->_id;
		if($this->getRequest()->isPost()){
			$form = $this->getRequest()->getPost();
			$button = "";
			foreach($form['class'] as $btn_class):
				$button .= " ".$btn_class;
			endforeach;
			$data = array(	
					'action'       => $form['action'],
					'description'  => $form['description'],
					'class'        => $button,
					'icon'         => $form['icon'],
					'status'       => $form['status'],
					'author'       => $this->_author,
					'created'      => $this->_created,
					'modified'     => $this->_modified,
			);
			$data = $this->_safedataObj->rteSafe($data);
			$result = $this->getDefinedTable(Administration\FlowActionTable::class)->save($data);	
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully added new flow action.");
			else:
				$this->flashMessenger()->addMessage("error^ Failed to add new flow action.");
			endif;
			return $this->redirect()->toRoute('flow/paginator',array('action'=>'flowaction','page'=>$this->_id, 'id'=>'0'));
		}
		$ViewModel = new ViewModel(array(
				'title'        => 'Add Flow Action',
				'page'         => $page,
				'elements'     => $this->getDefinedTable(Acl\ButtonTable::class)->getAll(),
				'icongroupObj' => $this->getDefinedTable(Acl\IcongroupTable::class),
				'iconObj'      => $this->getDefinedTable(Acl\IconTable::class),
		));		 
		$ViewModel->setTerminal(True);
		return $ViewModel;	
	}
	/**
	 * Get the icons via icon group
	 */
	public function geticongroupchangeAction()
	{	
		$form = $this->getRequest()->getPost();
		$group = $form['group'];
		$icons = $this->getDefinedTable(Acl\IconTable::class)->getColumnValue('i.icon_group',$group);
		
		$icon = "<option value='0' selected>None</option>";
		foreach($icons as $row1):
			$icon.="<option value='".$row1['icon']."'>".$row1['icon']."</option>";
		endforeach;
		
		echo json_encode(array(
				'icon' => $icon,
		));
		exit;
	}
	/**
	 *  EditFlowAction action
	 */
	public function editflowactionAction()
	{
		$this->init();
		$id = $this->_id;
		$array_id = explode("_", $id);
		$flowaction_id = $array_id[0];
		$page = (sizeof($array_id)>1)?$array_id[1]:'';
		if($this->getRequest()->isPost()){
			$form = $this->getRequest()->getPost();
			$button = "";
			foreach($form['class'] as $btn_class):
				$button .= " ".$btn_class;
			endforeach;
			$data = array(	
					'id'           => $form['flowaction_id'],
					'action'       => $form['action'],
					'description'  => $form['description'],
					'class'        => $button,
					'icon'         => $form['icon'],
					'status'       => $form['status'],
					'author'       => $this->_author,
					'modified'     => $this->_modified,
			);
			$data = $this->_safedataObj->rteSafe($data);
			$result = $this->getDefinedTable(Administration\FlowActionTable::class)->save($data);	
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully edited flow action.");
			else:
				$this->flashMessenger()->addMessage("error^ Failed to edited flow action.");
			endif;
			return $this->redirect()->toRoute('flow/paginator',array('action'=>'flowaction','page'=>$this->_id, 'id'=>'0'));
		}
		$ViewModel = new ViewModel(array(
				'title'        => 'Edit Flow Action',
				'page'         => $page,
				'flowactions'  => $this->getDefinedTable(Administration\FlowActionTable::class)->get($flowaction_id),
				'elements'     => $this->getDefinedTable(Acl\ButtonTable::class)->getAll(),
				'icongroupObj' => $this->getDefinedTable(Acl\IcongroupTable::class),
				'iconObj'      => $this->getDefinedTable(Acl\IconTable::class),
		));		 
		$ViewModel->setTerminal(True);
		return $ViewModel;	
	}
}
