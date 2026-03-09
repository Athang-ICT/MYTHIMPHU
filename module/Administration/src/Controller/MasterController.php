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

class MasterController extends AbstractActionController
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
	 *  location type action
	 */
	public function locationtypeAction()
	{
		$this->init();
		$locationtypeTable = $this->getDefinedTable(Administration\LocationTypeTable::class)->getAll();
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($locationtypeTable));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'            => 'Location Type',
			'paginator'        => $paginator,
			'page'             => $page,
		)); 
	}
	/**
	 * add location type
	 */
    public function addlocationtypeAction()
    {
		$this->init();
		$page = $this->_id;
		if($this->getRequest()->isPost()){
            $form = $this->getRequest()->getPost();
			$data = array(
				'location_type'  => $form['location_type'],
				'description'    => $form['description'],
				'sales'          => $form['sales'],
				'status'         => $form['status'],
				'author'         => $this->_author,
				'created'        => $this->_created,
				'modified'       => $this->_modified
			);
			$result = $this->getDefinedTable(Administration\LocationTypeTable::class)->save($data);
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully added new location type."); 	             
			else:
				$this->flashMessenger()->addMessage("error^ Failed to add new location type.");	 	             
			endif;
			return $this->redirect()->toRoute('setmaster/paginator', array('action' => 'locationtype', 'page'=>$this->_id, 'id'=>'0'));
		}
		$ViewModel = new ViewModel([
			'title'        => 'Add New Location Type',
			'page'         => $page,
		]); 
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 *  editlocationtype Action
	 **/
	public function editlocationtypeAction()
	{
	    $this->init();
		$id = $this->_id;
		$array_id = !empty($id) ? explode("_", $id) : [];
		$locationtype_id = isset($array_id[0]) ? $array_id[0] : 0;
		$page = (sizeof($array_id)>1)?$array_id[1]:'';
		if($this->getRequest()->isPost()){
			$form = $this->getRequest()->getPost();
			$data = array(
				'id'             => $form['locationtype_id'],
				'location_type'  => $form['location_type'],
				'sales'          => $form['sales'],
				'description'    => $form['description'],
				'status'         => $form['status'],
				'author'         => $this->_author,
				'modified'       => $this->_modified
			);
			$result = $this->getDefinedTable(Administration\LocationTypeTable::class)->save($data);
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully edited location type."); 	             
			else:
				$this->flashMessenger()->addMessage("error^ Failed to edit location type.");	 	             
			endif;
			return $this->redirect()->toRoute('setmaster/paginator',array('action'=>'locationtype','page'=>$this->_id, 'id'=>'0'));
		}
		$ViewModel = new ViewModel(array(
				'title'         => 'Edit Location Type',
				'page'          => $page,
				'locationtypes' => $this->getDefinedTable(Administration\LocationTypeTable::class)->get($locationtype_id),
		));		 
		$ViewModel->setTerminal(True);
		return $ViewModel;	
	}
	/**
	 *  region action
	 */
	public function regionAction()
	{
		$this->init();
		$regionTable = $this->getDefinedTable(Administration\RegionTable::class)->getAll();
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($regionTable));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'            => 'Region',
			'paginator'        => $paginator,
			'page'             => $page,
			'departmentObj'	=> $this->getDefinedTable(Administration\DepartmentTable::class),
		));
	}
	/**
	 * addregion action
	 */
	public function addregionAction()
	{
		$this->init();
		$page = $this->_id;
		if($this->getRequest()->isPost()){
            $form = $this->getRequest()->getPost();
			$data = array(
				'region'         => $form['region'],
				'department'         => $form['department'],
				'status'         => $form['status'],
				'author'         => $this->_author,
				'created'        => $this->_created,
				'modified'       => $this->_modified
			);
			$result = $this->getDefinedTable(Administration\RegionTable::class)->save($data);
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully added new region."); 	             
			else:
				$this->flashMessenger()->addMessage("error^ Failed to add new region.");	 	             
			endif;
			return $this->redirect()->toRoute('setmaster/paginator', array('action' => 'region', 'page'=>$this->_id, 'id'=>'0'));
		}
		$ViewModel = new ViewModel([
			'title'        => 'Add New Region',
			'page'         => $page,
			'department'	=> $this->getDefinedTable(Administration\DepartmentTable::class)->getAll(),
		]); 
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 * regioneditAction
	 **/
	public function editregionAction()
	{
		$this->init();
		$id = $this->_id;
		$array_id = !empty($id) ? explode("_", $id) : [];
		$region_id = isset($array_id[0]) ? $array_id[0] : 0;
		$page = (sizeof($array_id)>1)?$array_id[1]:'';
		if($this->getRequest()->isPost()){
			$form = $this->getRequest()->getPost();
			$data = array(
				'id'             => $form['region_id'],
				'region'         => $form['region'],
				'department'         => $form['department'],
				'status'         => $form['status'],
				'author'         => $this->_author,
				'modified'       => $this->_modified
			);
			$result = $this->getDefinedTable(Administration\RegionTable::class)->save($data);
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully edited region."); 	             
			else:
				$this->flashMessenger()->addMessage("error^ Failed to edit region.");	 	             
			endif;
			return $this->redirect()->toRoute('setmaster/paginator',array('action'=>'region','page'=>$this->_id, 'id'=>'0'));
		}
		$ViewModel = new ViewModel(array(
				'title'         => 'Edit Region',
				'page'          => $page,
				'regions'       => $this->getDefinedTable(Administration\RegionTable::class)->get($region_id),
				'department'	=> $this->getDefinedTable(Administration\DepartmentTable::class)->getAll(),
		));		 
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**  
	 * Location action
	 */
	public function locationAction()
	{	
		$this->init();
		$array_id = !empty($this->_id) ? explode("_", $this->_id) : [];
		$region = (sizeof($array_id)>1)?$array_id[0]:'-1';
		$location_type = (sizeof($array_id)>1)?$array_id[1]:'-1';
		if($this->getRequest()->isPost())
		{
			$form      		 = $this->getRequest()->getPost();
			$region          = $form['region'];
			$location_type   = $form['location_type'];
			
		}else{
			$region = $region;
			$location_type = $location_type;
		
		}
		$data = array(
			'region'  	    => $region,
			'location_type' => $location_type,
		);
		$locationTable = $this->getDefinedTable(Administration\LocationTable::class)->getReport($data);
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($locationTable));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'           => 'Location',
			'paginator'       => $paginator,
			'data'            => $data,
			'page'            => $page,
			'regionObj'       => $this->getDefinedTable(Administration\RegionTable::class),
			'locationtypeObj' => $this->getDefinedTable(Administration\LocationTypeTable::class),
			'districtObj'     => $this->getDefinedTable(Administration\DistrictTable::class),
			'locationObj'     => $this->getDefinedTable(Administration\LocationTable::class),
		)); 
	} 
	/**
	 *  addlocation action
	 */
	public function addlocationAction()
	{
		$this->init();
		$page = $this->_id;
		if($this->getRequest()->isPost()){
			$form = $this->getRequest()->getPost();
			$max_code = $this->getDefinedTable(Administration\LocationTable::class)->getMax('location_code');
			$max_code = $max_code+1;
			$num_length = strlen((string)$max_code);
			if($num_length == 1){$max_code = '0'.$max_code;}
			$data = array(
					'region'         => $form['region'],
					'location_type'  => $form['location_type'],
					'location'       => $form['location'],
					'prefix'         => $form['prefix'],
					'location_code'  => $max_code,
					'district'       => $form['district'],
					'coordinates'    => $form['coordinates'],
					'status'         => $form['status'],
					'status'         => $form['status'],
					'author'         => $this->_author,
					'created'        => $this->_created,
					'modified'       => $this->_modified,
			);
			$data = $this->_safedataObj->rteSafe($data);
			$result = $this->getDefinedTable(Administration\LocationTable::class)->save($data);	
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully added new location.");
			else:
				$this->flashMessenger()->addMessage("error^ Failed to add new location.");
			endif;
			return $this->redirect()->toRoute('setmaster/paginator',array('action'=>'location','page'=>$this->_id, 'id'=>$form['region'].'_'.$form['location_type']));
		}
		$ViewModel = new ViewModel(array(
				'title'         => 'Add Location',
				'page'          => $page,
				'regions'       => $this->getDefinedTable(Administration\RegionTable::class)->get(array('status'=>'1')),	
				'locationtypes' => $this->getDefinedTable(Administration\LocationTypeTable::class)->get(array('status'=>'1')),	
				'districts'     => $this->getDefinedTable(Administration\DistrictTable::class)->get(array('status'=>'1')),	
		));		 
		$ViewModel->setTerminal(True);
		return $ViewModel;	
	}
	/**
	 *  editlocation action
	 */
	public function editlocationAction()
	{
		$this->init();
		$id = $this->_id;
		$array_id = !empty($id) ? explode("_", $id) : [];
		$location_id = isset($array_id[0]) ? $array_id[0] : 0;
		$page = (sizeof($array_id)>1)?$array_id[1]:'';
		if($this->getRequest()->isPost()){
			$form = $this->getRequest()->getPost();
			$data = array(	
					'id'             => $form['location_id'],
					'region'         => $form['region'],
					'location_type'  => $form['location_type'],
					'location'       => $form['location'],
					'prefix'         => $form['prefix'],
					'district'       => $form['district'],
					'coordinates'    => $form['coordinates'],
					'status'         => $form['status'],
					'author'         => $this->_author,
					'modified'       => $this->_modified,
			);
			$data = $this->_safedataObj->rteSafe($data);
			$result = $this->getDefinedTable(Administration\LocationTable::class)->save($data);	
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully edited location.");
			else:
				$this->flashMessenger()->addMessage("error^ Failed to edit location.");
			endif;
			return $this->redirect()->toRoute('setmaster/paginator',array('action'=>'location','page'=>$this->_id, 'id'=>$form['region'].'_'.$form['location_type']));
		}
		$ViewModel = new ViewModel(array(
				'title'         => 'Edit Location',
				'page'          => $page,
				'regions'       => $this->getDefinedTable(Administration\RegionTable::class)->get(array('status'=>'1')),	
				'locationtypes' => $this->getDefinedTable(Administration\LocationTypeTable::class)->get(array('status'=>'1')),	
				'districts'     => $this->getDefinedTable(Administration\DistrictTable::class)->get(array('status'=>'1')),	
				'locations'		=> $this->getDefinedTable(Administration\LocationTable::class)->get($location_id),
		));		 
		$ViewModel->setTerminal(True);
		return $ViewModel;	
	}
	/**
	 *  department action
	 */
	public function departmentAction()
	{
		$this->init();
		$departmentTable = $this->getDefinedTable(Administration\DepartmentTable::class)->getAll();
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($departmentTable));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'            => 'Department',
			'paginator'        => $paginator,
			'page'             => $page,
		));
	}
	/**
	 * adddepartment action
	 */
	public function adddepartmentAction()
	{
		$this->init();
		$page = $this->_id;
		if($this->getRequest()->isPost()){
            $form = $this->getRequest()->getPost();
			$data = array(
				'department'     => $form['department'],
				'prefix'         => $form['prefix'],
				'status'         => $form['status'],
				'author'         => $this->_author,
				'created'        => $this->_created,
				'modified'       => $this->_modified
			);
			$result = $this->getDefinedTable(Administration\DepartmentTable::class)->save($data);
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully added new department."); 	             
			else:
				$this->flashMessenger()->addMessage("error^ Failed to add new department.");	 	             
			endif;
			return $this->redirect()->toRoute('setmaster/paginator', array('action' => 'department', 'page'=>$this->_id, 'id'=>'0'));
		}
		$ViewModel = new ViewModel([
			'title'        => 'Add New Department',
			'page'         => $page,
		]); 
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 * editdepartmentAction
	 **/
	public function editdepartmentAction()
	{
		$this->init();
		$id = $this->_id;
		$array_id = !empty($id) ? explode("_", $id) : [];
		$department_id = isset($array_id[0]) ? $array_id[0] : 0;
		$page = (sizeof($array_id)>1)?$array_id[1]:'';
		if($this->getRequest()->isPost()){
			$form = $this->getRequest()->getPost();
			$data = array(
				'id'             => $form['department_id'],
				'department'     => $form['department'],
				'prefix'         => $form['prefix'],
				'status'         => $form['status'],
				'author'         => $this->_author,
				'modified'       => $this->_modified
			);
			$result = $this->getDefinedTable(Administration\DepartmentTable::class)->save($data);
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully edited department."); 	             
			else:
				$this->flashMessenger()->addMessage("error^ Failed to edit department.");	 	             
			endif;
			return $this->redirect()->toRoute('setmaster/paginator',array('action'=>'department','page'=>$this->_id, 'id'=>'0'));
		}
		$ViewModel = new ViewModel(array(
				'title'         => 'Edit Department',
				'page'          => $page,
				'departments'   => $this->getDefinedTable(Administration\DepartmentTable::class)->get($department_id),
		));		 
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 *  activity action
	 */
	public function activityAction()
	{
		$this->init();
		$department_id = (isset($this->_id))? $this->_id:'-1';
		$activityTable = $this->getDefinedTable(Administration\ActivityTable::class)->getColumnValue('department',$department_id);
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($activityTable));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'            => 'Activity',
			'paginator'        => $paginator,
			'department_id'    => $department_id,
			'page'             => $page,
			'departmentObj'    => $this->getDefinedTable(Administration\DepartmentTable::class),
		));
	}
	/**
	 * addactivity action
	 **/
	public function addactivityAction()
	{
		$this->init();
		$page = $this->_id;
		if($this->getRequest()->isPost()){
            $form = $this->getRequest()->getPost();
			$data = array(
				'activity'       => $form['activity'],
				'department'     => $form['department'],
				'prefix'         => $form['prefix'],
				'status'         => $form['status'],
				'author'         => $this->_author,
				'created'        => $this->_created,
				'modified'       => $this->_modified
			);
			$result = $this->getDefinedTable(Administration\ActivityTable::class)->save($data);
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully added new activity."); 	             
			else:
				$this->flashMessenger()->addMessage("error^ Failed to add new activity.");	 	             
			endif;
			return $this->redirect()->toRoute('setmaster/paginator', array('action' => 'activity', 'page'=>$this->_id, 'id'=>$form['department']));
		}
		$ViewModel = new ViewModel([
			'title'        => 'Add New Activity',
			'page'         => $page,
			'departments'  => $this->getDefinedTable(Administration\DepartmentTable::class)->get(array('status'=>'1')),
		]); 
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	
	/**
	 * editactivity Action
	 */
	public function editactivityAction()
	{
		$this->init();
		$id = $this->_id;
		$array_id = !empty($id) ? explode("_", $id) : [];
		$activity_id = isset($array_id[0]) ? $array_id[0] : 0;
		$page = (sizeof($array_id)>1)?$array_id[1]:'';
		if($this->getRequest()->isPost()){
			$form = $this->getRequest()->getPost();
			$data = array(
				'id'             => $form['activity_id'],
				'activity'       => $form['activity'],
				'department'     => $form['department'],
				'prefix'         => $form['prefix'],
				'status'         => $form['status'],
				'author'         => $this->_author,
				'modified'       => $this->_modified
			);
			$result = $this->getDefinedTable(Administration\ActivityTable::class)->save($data);
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully edited activity."); 	             
			else:
				$this->flashMessenger()->addMessage("error^ Failed to edit activity.");	 	             
			endif;
			return $this->redirect()->toRoute('setmaster/paginator',array('action'=>'activity','page'=>$this->_id, 'id'=>$form['department']));
		}
		$ViewModel = new ViewModel(array(
				'title'         => 'Edit Activity',
				'page'          => $page,
				'departments'   => $this->getDefinedTable(Administration\DepartmentTable::class)->get(array('status'=>'1')),
				'activities'    => $this->getDefinedTable(Administration\ActivityTable::class)->get($activity_id),
		));		 
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 *  section action
	 */
	public function sectionAction()
	{
		$this->init();
		$division_id = (isset($this->_id))? $this->_id:'-1';
		$sectionTable = $this->getDefinedTable(Administration\SectionTable::class)->getColumnValue('division',$division_id);
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($sectionTable));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'            => 'Section',
			'paginator'        => $paginator,
			'division_id'    => $division_id,
			'page'             => $page,
			'divisionObj'    => $this->getDefinedTable(Administration\ActivityTable::class),
		));
	}
	/**
	 * addsection action
	 **/
	public function addsectionAction()
	{
		$this->init();
		$page = $this->_id;
		if($this->getRequest()->isPost()){
            $form = $this->getRequest()->getPost();
			$data = array(
				'section'       => $form['section'],
				'division'       => $form['division'],
				'prefix'         => $form['prefix'],
				'status'         => $form['status'],
				'author'         => $this->_author,
				'created'        => $this->_created,
				'modified'       => $this->_modified
			);
			$result = $this->getDefinedTable(Administration\SectionTable::class)->save($data);
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully added new section."); 	             
			else:
				$this->flashMessenger()->addMessage("error^ Failed to add new section.");	 	             
			endif;
			return $this->redirect()->toRoute('setmaster/paginator', array('action' => 'section', 'page'=>$this->_id, 'id'=>$form['division']));
		}
		$ViewModel = new ViewModel([
			'title'        => 'Add New Section',
			'page'         => $page,
			'departments'  => $this->getDefinedTable(Administration\DepartmentTable::class)->get(array('status'=>'1')),
		]); 
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	
	/**
	 * editsection Action
	 */
	public function editsectionAction()
	{
		$this->init();
		$id = $this->_id;
		$array_id = !empty($id) ? explode("_", $id) : [];
		$activity_id = isset($array_id[0]) ? $array_id[0] : 0;
		$page = (sizeof($array_id)>1)?$array_id[1]:'';
		if($this->getRequest()->isPost()){
			$form = $this->getRequest()->getPost();
			$data = array(
				'id'             => $form['section_id'],
				'section'       => $form['section'],
				'division'       => $form['division'],
				'prefix'         => $form['prefix'],
				'status'         => $form['status'],
				'author'         => $this->_author,
				'created'        => $this->_created,
				'modified'       => $this->_modified
			);
			$result = $this->getDefinedTable(Administration\SectionTable::class)->save($data);
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully edited section."); 	             
			else:
				$this->flashMessenger()->addMessage("error^ Failed to edit section.");	 	             
			endif;
			return $this->redirect()->toRoute('setmaster/paginator',array('action'=>'section','page'=>$this->_id, 'id'=>$form['division']));
		}
		$ViewModel = new ViewModel(array(
				'title'         => 'Edit section',
				'page'          => $page,
				'division'   => $this->getDefinedTable(Administration\ActivityTable::class)->get(array('status'=>'1')),
				'activities'    => $this->getDefinedTable(Administration\SectionTable::class)->get($activity_id),
		));		 
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 * Get section
	 */
	public function getsectionAction()
	{		
		$form = $this->getRequest()->getPost();
		$department_id = $form['department_id'];
		$department = $this->getDefinedTable(Administration\ActivityTable::class)->get(array('department'=>$department_id));
		
		$division = "<option value=''></option>";
		foreach($department as $departments):
			$division.="<option value='".$departments['id']."'>".$departments['activity']."</option>";
		endforeach;
			echo json_encode(array(
				'division' => $division,
		));
		exit;
	}
}
