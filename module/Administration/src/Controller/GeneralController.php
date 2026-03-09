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

class GeneralController extends AbstractActionController
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
	 * index Action of General Controller
	 */
    public function indexAction()
    {  
    	$this->init(); 
		
        return new ViewModel([
			'title' => 'Administration Menu',
		]);
	}
	/**
	 *  district action
	 */
	public function districtAction()
	{
		$this->init();
		$districtTable = $this->getDefinedTable(Administration\DistrictTable::class)->getAll();
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($districtTable));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'            => 'District',
			'paginator'        => $paginator,
			'page'             => $page,
		)); 
	}
	/**
	 * adddistrict action
	 */
	public function adddistrictAction()
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
				'district_code'      => $max_code,
				'district'           => $form['district'],
				'districtDz'         => $form['districtDz'],
				'status'             => $form['status'],
				'author'             => $this->_author,
				'created'            => $this->_created,
				'modified'           => $this->_modified,
            );
            $data = $this->_safedataObj->rteSafe($data);
			$this->_connection->beginTransaction();
            $result = $this->getDefinedTable(Administration\DistrictTable::class)->save($data);
            if($result):
				$this->_connection->commit();
                $this->flashMessenger()->addMessage("success^ successfully added new district");
            else:
				$this->_connection->rollback();
                $this->flashMessenger()->addMessage("error^ Failed to add new district");
            endif;
			return $this->redirect()->toRoute('general/paginator', array('action'=>'district','page'=>$this->_id, 'id'=>'0'));
        }		
		$ViewModel = new ViewModel([
				'title' => 'Add District',
				'page'  => $page,
		]);
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 * editdistrict action
	 */
	public function editdistrictAction()
	{
		$this->init();
		$id = $this->_id;
		$array_id = !empty($id) ? explode("_", $id) : [];
		$district_id = isset($array_id[0]) ? $array_id[0] : 0;
		$page = (sizeof($array_id)>1)?$array_id[1]:'';
		
		if($this->getRequest()->isPost()){
            $form = $this->getRequest()->getPost();
            $data = array(  
				'id'                 => $form['district_id'],
				'district'           => $form['district'],
				'districtDz'         => $form['districtDz'],
				'status'             => $form['status'],
				'author'             => $this->_author,
				'modified'           => $this->_modified,
            );
            $data = $this->_safedataObj->rteSafe($data);
			$this->_connection->beginTransaction();
            $result = $this->getDefinedTable(Administration\DistrictTable::class)->save($data);
            if($result > 0):
				$this->_connection->commit();
                $this->flashMessenger()->addMessage("success^ successfully edited district");
            else:
				$this->_connection->rollback();
                $this->flashMessenger()->addMessage("error^ Failed to edit district");
            endif;
			return $this->redirect()->toRoute('general/paginator', array('action'=>'district','page'=>$this->_id, 'id'=>'0'));
        }		
		$ViewModel = new ViewModel([
				'title'    => 'Edit District',
				'page'     => $page,
				'districts'=> $this->getDefinedTable(Administration\DistrictTable::class)->get($district_id),
		]);
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**  
	 * Block action
	 */
	public function blockAction()
	{	
		$this->init();
		$district_id = (isset($this->_id))? $this->_id:'-1';
		$blockTable = $this->getDefinedTable(Administration\BlockTable::class)->getColumnValue('district',$district_id);
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($blockTable));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'         => 'Block',
			'paginator'     => $paginator,
			'district_id'   => $district_id,
			'page'          => $page,
			'districtObj'   => $this->getDefinedTable(Administration\DistrictTable::class),
		)); 
	} 
	/**
	 *  Add Block action
	 */
	public function addblockAction()
	{
		$this->init();
		$page = $this->_id;
		if($this->getRequest()->isPost()){
			$form = $this->getRequest()->getPost();
			$max_code = $this->getDefinedTable(Administration\BlockTable::class)->getMax('block_code');
			$max_code = $max_code+1;
			$num_length = strlen((string)$max_code);
			switch($num_length):
				case 1:
					$max_code = '00'.$max_code;
					break;
				case 2:
					$max_code = '0'.$max_code;
					break;
				default:
					$max_code = $max_code;
					break;
			endswitch;
			$data = array(	
					'district'     => $form['district'],
					'block_code'   => $max_code,
					'block'        => $form['block'],
					'blockDz'      => $form['blockDz'],
					'status'       => $form['status'],
					'author'       => $this->_author,
					'created'      => $this->_created,
					'modified'     => $this->_modified,
			);
			$data = $this->_safedataObj->rteSafe($data);
			$result = $this->getDefinedTable(Administration\BlockTable::class)->save($data);	
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully added new block.");
			else:
				$this->flashMessenger()->addMessage("error^ Failed to add new block.");
			endif;
			return $this->redirect()->toRoute('general/paginator',array('action'=>'block','page'=>$this->_id, 'id'=>$form['district']));
						 
		}
		$ViewModel = new ViewModel(array(
				'title'      => 'Add Block',
				'page'       => $page,
				'districts'  => $this->getDefinedTable(Administration\DistrictTable::class)->get(array('status'=>'1')),	
		));		 
		$ViewModel->setTerminal(True);
		return $ViewModel;	
	}
	/**
	 *  Edit Block action
	 */
	public function editblockAction()
	{
		$this->init();
		$id = $this->_id;
		$array_id = !empty($id) ? explode("_", $id) : [];
		$block_id = isset($array_id[0]) ? $array_id[0] : 0;
		$page = (sizeof($array_id)>1)?$array_id[1]:'';
		if($this->getRequest()->isPost()){
			$form = $this->getRequest()->getPost();
			$data = array(	
				'id'           => $form['block_id'],
				'district'     => $form['district'],
				'block'        => $form['block'],
				'blockDz'      => $form['blockDz'],
				'status'       => $form['status'],
				'author'       => $this->_author,
				'modified'     => $this->_modified,
			);
			$data = $this->_safedataObj->rteSafe($data);
			$result = $this->getDefinedTable(Administration\BlockTable::class)->save($data);	
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully edited block.");
			else:
				$this->flashMessenger()->addMessage("error^ Failed to edited block.");
			endif;
			return $this->redirect()->toRoute('general/paginator',array('action'=>'block','page'=>$this->_id, 'id'=>$form['district']));
		}
		$ViewModel = new ViewModel(array(
				'title'      => 'Edit Block',
				'page'       => $page,
				'districts'  => $this->getDefinedTable(Administration\DistrictTable::class)->get(array('status'=>'1')),	
				'blocks'     => $this->getDefinedTable(Administration\BlockTable::class)->get($block_id),
		));		 
		$ViewModel->setTerminal(True);
		return $ViewModel;	
	}
	/**  
	 * Village action
	 */
	public function villageAction()
	{	
		$this->init();
		$array_id = !empty($this->_id) ? explode("_", $this->_id) : [];
		$district = (sizeof($array_id)>1)?$array_id[0]:'-1';
		$block = (sizeof($array_id)>1)?$array_id[1]:'-1';
		if($this->getRequest()->isPost())
		{
			$form      = $this->getRequest()->getPost();
			$district  = $form['district'];
			$block     = $form['block'];
			
		}else{
			$district = $district;
			$block = $block;
		
		}
		$data = array(
			'district'  => $district,
			'block'     => $block,
		);
		$villageTable = $this->getDefinedTable(Administration\VillageTable::class)->getReport($data);
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($villageTable));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'         => 'Village',
			'paginator'     => $paginator,
			'data'      	=> $data,
			'page'          => $page,
			'districtObj'   => $this->getDefinedTable(Administration\DistrictTable::class),
			'blockObj'      => $this->getDefinedTable(Administration\BlockTable::class),
		)); 
	} 
	/**
	 *  Add Village action
	 */
	public function addvillageAction()
	{
		$this->init();
		$page = $this->_id;
		if($this->getRequest()->isPost()){
			$form = $this->getRequest()->getPost();
			$max_code = $this->getDefinedTable(Administration\VillageTable::class)->getMax('village_code');
			$max_code = $max_code+1;
			$num_length = strlen((string)$max_code);
			switch($num_length):
				case 1:
					$max_code = '000'.$max_code;
					break;
				case 2:
					$max_code = '00'.$max_code;
					break;
				case 3:
					$max_code = '0'.$max_code;
					break;
				default:
					$max_code = $max_code;
					break;
			endswitch;
			$data = array(	
					'block'          => $form['block'],
					'village_code'   => $max_code,
					'village'        => $form['village'],
					'villageDz'      => $form['villageDz'],
					'status'         => $form['status'],
					'author'         => $this->_author,
					'created'        => $this->_created,
					'modified'       => $this->_modified,
			);
			$data = $this->_safedataObj->rteSafe($data);
			$result = $this->getDefinedTable(Administration\VillageTable::class)->save($data);	
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully added new village.");
			else:
				$this->flashMessenger()->addMessage("error^ Failed to add new village.");
			endif;
			return $this->redirect()->toRoute('general/paginator',array('action'=>'village','page'=>$this->_id, 'id'=>$form['block']));
						 
		}
		$ViewModel = new ViewModel(array(
				'title'      => 'Add Village',
				'page'       => $page,
				'blocks'     => $this->getDefinedTable(Administration\BlockTable::class)->get(array('status'=>'1')),	
		));		 
		$ViewModel->setTerminal(True);
		return $ViewModel;	
	}
	/**
	 *  Edit Village action
	 */
	public function editvillageAction()
	{
		$this->init();
		$id = $this->_id;
		$array_id = !empty($id) ? explode("_", $id) : [];
		$village_id = isset($array_id[0]) ? $array_id[0] : 0;
		$page = (sizeof($array_id)>1)?$array_id[1]:'';
		if($this->getRequest()->isPost()){
			$form = $this->getRequest()->getPost();
			$data = array(	
					'id'             => $form['village_id'],
					'block'          => $form['block'],
					'village'        => $form['village'],
					'villageDz'      => $form['villageDz'],
					'status'         => $form['status'],
					'author'         => $this->_author,
					'modified'       => $this->_modified,
			);
			$data = $this->_safedataObj->rteSafe($data);
			$result = $this->getDefinedTable(Administration\VillageTable::class)->save($data);	
			if($result):
				$this->flashMessenger()->addMessage("success^ Successfully edited village.");
			else:
				$this->flashMessenger()->addMessage("error^ Failed to edit village.");
			endif;
			return $this->redirect()->toRoute('general/paginator',array('action'=>'village','page'=>$this->_id, 'id'=>$form['block']));
						 
		}
		$ViewModel = new ViewModel(array(
				'title'      => 'Edit Village',
				'page'       => $page,
				'blocks'     => $this->getDefinedTable(Administration\BlockTable::class)->get(array('status'=>'1')),	
				'villages'   => $this->getDefinedTable(Administration\VillageTable::class)->get($village_id),
		));		 
		$ViewModel->setTerminal(True);
		return $ViewModel;	
	}
	/**
	 *  bank action
	 */
	public function bankAction()
	{
		$this->init();
		$bankTable = $this->getDefinedTable(Administration\BankTable::class)->getAll();
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($bankTable));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'            => 'Bank',
			'paginator'        => $paginator,
			'page'             => $page,
		)); 
	}
	/**
	 * add bank action
	 */
	public function addbankAction()
	{
		$this->init();
		$page = $this->_id;
		if($this->getRequest()->isPost()){
            $form = $this->getRequest()->getPost();
            $data = array(  
				'bank_code'      => $form['bank_code'],
				'bank'           => $form['bank'],
				'bankDz'         => $form['bankDz'],
				'status'         => $form['status'],
				'author'         => $this->_author,
				'created'        => $this->_created,
				'modified'       => $this->_modified,
            );
            $data = $this->_safedataObj->rteSafe($data);
			$this->_connection->beginTransaction();
            $result = $this->getDefinedTable(Administration\BankTable::class)->save($data);
            if($result):
				$this->_connection->commit();
                $this->flashMessenger()->addMessage("success^ successfully added new bank");
            else:
				$this->_connection->rollback();
                $this->flashMessenger()->addMessage("error^ Failed to add new bank");
            endif;
			return $this->redirect()->toRoute('general/paginator', array('action'=>'bank','page'=>$this->_id, 'id'=>'0'));
        }		
		$ViewModel = new ViewModel([
				'title' => 'Add Bank',
				'page'  => $page,
		]);
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 * editbank action
	 */
	public function editbankAction()
	{
		$this->init();
		$id = $this->_id;
		$array_id = explode("_", $id);
		$bank_id = $array_id[0];
		$page = (sizeof($array_id)>1)?$array_id[1]:'';
		
		if($this->getRequest()->isPost()){
            $form = $this->getRequest()->getPost();
            $data = array(  
				'id'             => $form['bank_id'],
				'bank_code'      => $form['bank_code'],
				'bank'           => $form['bank'],
				'bankDz'         => $form['bankDz'],
				'status'         => $form['status'],
				'author'         => $this->_author,
				'modified'       => $this->_modified,
            );
            $data = $this->_safedataObj->rteSafe($data);
			$this->_connection->beginTransaction();
            $result = $this->getDefinedTable(Administration\BankTable::class)->save($data);
            if($result > 0):
				$this->_connection->commit();
                $this->flashMessenger()->addMessage("success^ successfully edited bank.");
            else:
				$this->_connection->rollback();
                $this->flashMessenger()->addMessage("error^ Failed to edit bank.");
            endif;
			return $this->redirect()->toRoute('general/paginator', array('action'=>'bank','page'=>$this->_id, 'id'=>'0'));
        }		
		$ViewModel = new ViewModel([
				'title'    => 'Edit Bank',
				'page'     => $page,
				'banks'    => $this->getDefinedTable(Administration\BankTable::class)->get($bank_id),
		]);
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
	/**
	 *  country action
	 */
	public function countryAction()
	{
		$this->init();
		$country = $this->getDefinedTable(Administration\CountryTable::class)->getAll();
		$paginator = new \Laminas\Paginator\Paginator(new \Laminas\Paginator\Adapter\ArrayAdapter($country));
			
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page);
		$paginator->setItemCountPerPage(20);
		$paginator->setPageRange(8);
        return new ViewModel(array(
			'title'            => 'Country',
			'paginator'        => $paginator,
			'page'             => $page,
		)); 
	}
}
