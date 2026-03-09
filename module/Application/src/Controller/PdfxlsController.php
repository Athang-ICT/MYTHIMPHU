<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Authentication\AuthenticationService;

class PdfxlsController extends AbstractActionController
{
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
    
	/**
	 * Zend Default TableGateway
	 * Table name as the parameter
	 * returns obj
	 */
	public function getDefaultTable($table)
	{
		$this->_table = new TableGateway($table, $this->getServiceLocator()->get('Laminas\Db\Adapter\Adapter'));
		return $this->_table;
	}

   /**
    * User defined Model
    * Table name as the parameter
    * returns obj
    */
    public function getDefinedTable($table)
    {
    	$sm = $this->getServiceLocator();
    	$this->_table = $sm->get($table);
    	return $this->_table;
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
			$this->_config = $this->getServiceLocator()->get('Config');
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
			$this->_highest_role = $this->getDefinedTable("Acl\RolesTable")->getMax($column='id');  
		}
		if(!isset($this->_lowest_role)){
			$this->_lowest_role = $this->getDefinedTable("Acl\RolesTable")->getMin($column='id'); 
		}
		if(!isset($this->_author)){
			$this->_author = $this->_user->id;  
		}
		
		$this->_id = $this->params()->fromRoute('id');
		
		$this->_created = date('Y-m-d H:i:s');
		$this->_modified = date('Y-m-d H:i:s');
		
		$fileManagerDir = $this->_config['file_manager']['dir'];
	
		if(!is_dir($fileManagerDir)) {
			mkdir($fileManagerDir, 0777);
		}			
	
		$this->_dir =realpath($fileManagerDir);
		
	}  
    /**
     * pdf Action
     */
    public function pdfAction()
    {		
		$dom='No document';
		$request = $this->getRequest();
		if($request->isPost()):
			$data = $request->getPost();
		//	echo"<pre>"; print_r($data); exit; 
			$this->sessionArray()->addContent('report', $data);
		endif;
		$viewModel = new ViewModel(array(
				'author' => $this->_author
		));
		$viewModel->setTerminal(True);
		return $viewModel;		
	}
	/**
	 * pdf print without header and footer
	**/
	public function plainpdfAction()
	{
		$dom='No document';
		$request = $this->getRequest();
		if($request->isPost()):
			$data = $request->getPost();
		//	echo"<pre>"; print_r($data); exit; 
			$this->sessionArray()->addContent('report', $data);
		endif;
		$viewModel = new ViewModel(array(
				'author' => $this->_author
		));
		$viewModel->setTerminal(True);
		return $viewModel;		
	}
	/**
	 * payrecipt Action
	 */
	public function payreciptAction()
	{
		$this->init();
		//echo $this->_id;exit;
        list($employee, $year, $month,$location, $region) = explode('&', $this->_id);
     
		if(is_numeric($this->_id)):		
			$payroll = $this->getDefinedTable('Hr\PayrollTable')->get($this->_id);
		else:			
			//retriving params form id
		     if($employee > 0){				
			    $payrolls = $this->getDefinedTable('Hr\PayrollTable')->get(array('pr.employee' => $employee, 'month' => $month, 'year' => $year));
			 }
			 elseif( $location > 0 ){				
			    $payrolls = $this->getDefinedTable('Hr\PayrollTable')->get(array('his.location' => $location, 'month' => $month, 'year' => $year));
			 }
			 elseif($region > 0){				
                $payrolls = $this->getDefinedTable('Hr\PayrollTable')->get(array('l.region' => $region, 'month' => $month, 'year' => $year));
			 }
			 else{						
			    $payrolls = $this->getDefinedTable('Hr\PayrollTable')->get(array('month' => $month, 'year' => $year));
			 }		
			 
			//$payroll = $this->getDefinedTable('Hr\PayrollTable')->getPayroll($this->_id);
		endif;	
      
		$ViewModel = new ViewModel(array(
				'title'         => 'Salary Slip Details',			

				'emphistoryObj' => $this->getDefinedTable('Hr\EmpHistoryTable'),
				'payheadObj'    => $this->getDefinedTable('Hr\PayheadTable'),
				'payroll'       => $payrolls,
				'paydetailObj'  => $this->getDefinedTable('Hr\PaydetailTable'),
				'paystructureObj' => $this->getDefinedTable('Hr\PaystructureTable'),
		));
		$ViewModel->setTerminal(True);
		return $ViewModel;
	}
}
