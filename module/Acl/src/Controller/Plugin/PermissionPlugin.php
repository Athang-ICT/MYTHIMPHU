<?php
/**
 * Plugin -- Permission Plugin
 * chophel@athang.com
 */
namespace Acl\Controller\Plugin;
 
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Db\Adapter\Adapter;    
use Laminas\Authentication\AuthenticationService;
use Laminas\Mvc\MvcEvent;
use Laminas\Http\Response;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Predicate\In;
use Laminas\Db\Sql\Predicate\NotIn;
use Interop\Container\ContainerInterface;

class PermissionPlugin extends AbstractPlugin{
	protected $_container;
	protected $dbAdapter;
	
	public function __construct(ContainerInterface $container)
    {
        $this->_container = $container;
    }
	/**
	 * Permission Access
	 */
	public function permission(MvcEvent $e, $permission=NULL){
		$auth = new AuthenticationService();
		/** Get Database Adapter **/
		$this->dbAdapter = $this->_container->get('Laminas\Db\Adapter\Adapter');
		if ($auth->hasIdentity()) {
			$login_id = $auth->getIdentity()->id;
			$login_role_array = explode(',',$auth->getIdentity()->role);
			$login_role = (sizeof($login_role_array)>0)?$auth->getIdentity()->role:0;
			$admin_location = explode(',',$auth->getIdentity()->admin_location??'');
			$admin_activity = explode(',',$auth->getIdentity()->admin_activity??'');
		}else{
			$login_id = 0;
			$login_role = 1;
			$login_role_array = array($login_role);
			$admin_location = array(0);	
			$admin_activity = array(0);	
		}
		
		/** Get Highest Role **/
		$sql = new Sql($this->dbAdapter);
		$select = $sql->select('sys_roles')->columns(['role' => new \Laminas\Db\Sql\Expression('MAX(id)')]);
		$statement = $sql->prepareStatementForSqlObject($select);
		$hrole = $statement->execute();
		foreach($hrole as $hrow);
		$highest_role = $hrow['role'];
		
		/** Get Currently Accessed Resources **/
		$controller = $e->getTarget();
		$controllerClass = get_class($controller);
		$moduleName = strtolower(substr($controllerClass, 0, strpos($controllerClass, '\\')));
		
		$routeMatch = $e->getRouteMatch();
		$actionName = strtolower($routeMatch->getParam('action', 'not-found'));	/** get the action name **/
		
		$controllerName = $routeMatch->getParam('controller', 'not-found');	/** get the controller name **/
		$controllerName = explode("\\", $controllerName);
		$controllerName = strtolower(array_pop($controllerName));
		$controllerName = substr($controllerName, 0, -10);
		
		$routeName = $routeMatch->getMatchedRouteName();
		$routeName = (strpos($routeName, '/') !== false)?substr($routeName, 0, strpos($routeName, "/")):$routeName;
		
		$routeParamID = $routeMatch->getParam('id');
		$routeParamID = !empty($routeParamID) ? explode('_', $routeParamID) : [];
		$id = isset($routeParamID[0]) ? (int) $routeParamID[0] : 0;
		
		$sql = new Sql($this->dbAdapter);
		// Get the module resource ID first
		$moduleSql = new Sql($this->dbAdapter);
		$moduleSelect = $moduleSql->select('sys_modules')->where(['module' => $moduleName]);
		$moduleStatement = $moduleSql->prepareStatementForSqlObject($moduleSelect);
		$moduleResult = $moduleStatement->execute();
		$moduleRow = null;
		$resourceId = null;
		foreach($moduleResult as $moduleRow);
		if($moduleRow) {
			$resourceId = $moduleRow['id'];
		}
		// (debug logging removed)
		
		$select = $sql->select('sys_acl')
			->where([
				'route' => $routeName,
				'controller' => $controllerName,
				'action' => $actionName,
				'resource' => $resourceId
			]);
		$statement = $sql->prepareStatementForSqlObject($select);
		$acldetails = $statement->execute();
		$arow = null;
		foreach($acldetails as $arow);
		// (debug logging removed)
		if(!$arow):
			$response = $e -> getResponse();
				@file_put_contents($logFile, date('c') . " | permission() set 404 -> ACL not found for resource lookup\n", FILE_APPEND);
				$response -> getHeaders() -> addHeaderLine('Location', $e -> getRequest() -> getBaseUrl() . '/404');
				$response -> setStatusCode(404);
			return array();
		endif;
		$acl_id = $arow['id'];
		if($arow['system']==0):
		$process_id = $arow['process'];
		$location_permit = '-1';
		$activity_permit = '-1';
		$onlyifcreator_permit = '-1';
		$status_permit = '-1';
		if(!in_array($highest_role,$login_role_array)){
			if($process_id>0):
				$sql = new Sql($this->dbAdapter);
				$select = $sql->select('sys_process')->where(['id' => $process_id]);
				$statement = $sql->prepareStatementForSqlObject($select);
				$processdetails = $statement->execute();
				foreach($processdetails as $prow);
				$table_name = $prow['table_name'];
				// process_id and target table resolved
				
				$sql = new Sql($this->dbAdapter);
				$select = $sql->select('sys_role_process')
					->where(['process' => $process_id, 'role' => explode(',', $login_role)]);
				$statement = $sql->prepareStatementForSqlObject($select);
				$roleprocess = $statement->execute();
				// roleprocess query executed
				if($id != 0): /** start -- if id!=0 **/
					$sql = new Sql($this->dbAdapter);
					$select = $sql->select($table_name)->where(['id' => $id]);
					$statement = $sql->prepareStatementForSqlObject($select);
					$records = $statement->execute();
					// records for id queried
					if(sizeof($records)>0):
						if(sizeof($roleprocess)>0):
							$location_column = array();
							$activity_column = array();
							$onlyifcreator_column = array();
							$permission_column = array();
							$status_column = array();
							foreach($roleprocess as $rprow):
								array_push($location_column,$rprow['location']);
								array_push($activity_column,$rprow['activity']);
								array_push($onlyifcreator_column,$rprow['only_if_creator']);
								array_push($status_column,$rprow['status']);
								array_push($permission_column,$rprow['permission_level']);
							endforeach;
							$location_permit = (in_array("0",$location_column))?'-1':$admin_location;
							$activity_permit = (in_array("0",$activity_column))?'-1':$admin_activity;
							$onlyifcreator_permit = (in_array("0",$onlyifcreator_column))?'-1':$login_id;
							$status_permit = (in_array("0",$status_column))?'-1':$permission_column;
							
							$column_array = array();
							if($location_permit != '-1'): array_push($column_array,'location');endif;
							if($activity_permit != '-1'): array_push($column_array,'activity');endif;
							if($onlyifcreator_permit != '-1'): array_push($column_array,'author');endif;
							if($status_permit != '-1'): array_push($column_array,'status');endif;
							$column = '';
							for($i=0;$i<sizeof($column_array);$i++):
								$column .= $column_array[$i];
								$column .= ($i != sizeof($column_array)-1)?", ":"";
							endfor;
						if(sizeof($column_array)>0):
							$sql = new Sql($this->dbAdapter);
							$select = $sql->select($table_name)->columns($column_array)->where(['id' => $id]);
							$statement = $sql->prepareStatementForSqlObject($select);
							$columndetails = $statement->execute();
								if(sizeof($columndetails)>0):
									foreach($columndetails as $crow);
									$check_array = array();
									if($location_permit != '-1'): $check = (in_array($crow['location'],$location_permit))?'1':'0'; array_push($check_array,$check);endif;
									if($activity_permit != '-1'): $check = (in_array($crow['activity'],$activity_permit))?'1':'0'; array_push($check_array,$check);endif;
									if($onlyifcreator_permit != '-1'): $check = (in_array($crow['author'],$onlyifcreator_permit))?'1':'0'; array_push($check_array,$check);endif;
									if($status_permit != '-1'): $check = (in_array($crow['status'],$status_permit))?'1':'0'; array_push($check_array,$check);endif;
									$data = array(
										'location_permit'      => $location_permit,
										'activity_permit'      => $activity_permit,
										'onlyifcreator_permit' => $onlyifcreator_permit,
										'status_permit'        => $status_permit,
										'column_data'          => $crow,
									);
									if(in_array('0',$check_array)):
										$response = $e -> getResponse();
										$response -> getHeaders() -> addHeaderLine('Location', $e -> getRequest() -> getBaseUrl() . '/404');
										$response -> setStatusCode(404);
										return array();
									endif;
								else:
									$permission = array(
											'location_permit' => $location_permit,
											'activity_permit' => $activity_permit,
											'onlyifcreator_permit' => $onlyifcreator_permit,
											'status_permit' => $status_permit,
									);
									return $permission;
								endif;
							else:
								$permission = array(
										'location_permit' => $location_permit,
										'activity_permit' => $activity_permit,
										'onlyifcreator_permit' => $onlyifcreator_permit,
										'status_permit' => $status_permit,
								);
								return $permission;
							endif;
						else:
							$permission = array(
									'location_permit' => $location_permit,
									'activity_permit' => $activity_permit,
									'onlyifcreator_permit' => $onlyifcreator_permit,
									'status_permit' => $status_permit,
							);
							return $permission;
						endif;
					else:
						$response = $e -> getResponse();
						$response -> getHeaders() -> addHeaderLine('Location', $e -> getRequest() -> getBaseUrl() . '/404');
						$response -> setStatusCode(404);
						return array();
					endif;
				else: /** end -- if id!=0 / start -- id == 0 **/
					if(sizeof($roleprocess)>0):
						$location_column = array();
						$activity_column = array();
						$onlyifcreator_column = array();
						$permission_column = array();
						$status_column = array();
						foreach($roleprocess as $rprow):
							array_push($location_column,$rprow['location']);
							array_push($activity_column,$rprow['activity']);
							array_push($onlyifcreator_column,$rprow['only_if_creator']);
							array_push($status_column,$rprow['status']);
							array_push($permission_column,$rprow['permission_level']);
						endforeach;
						$location_permit = (in_array("0",$location_column))?'-1':$admin_location;
						$activity_permit = (in_array("0",$activity_column))?'-1':$admin_activity;
						$onlyifcreator_permit = (in_array("0",$onlyifcreator_column))?'-1':$login_id;
						$status_permit = (in_array("0",$status_column))?'-1':$permission_column;
						$permission = array(
								'location_permit' => $location_permit,
								'activity_permit' => $activity_permit,
								'onlyifcreator_permit' => $onlyifcreator_permit,
								'status_permit' => $status_permit,
						);
						return $permission;
					else:
						$permission = array(
								'location_permit' => $location_permit,
								'activity_permit' => $activity_permit,
								'onlyifcreator_permit' => $onlyifcreator_permit,
								'status_permit' => $status_permit,
						);
						return $permission;
					endif;
				endif;/** end -- if id==0 **/
			else:
				$permission = array(
						'location_permit' => $location_permit,
						'activity_permit' => $activity_permit,
						'onlyifcreator_permit' => $onlyifcreator_permit,
						'status_permit' => $status_permit,
				);
				return $permission;
			endif;
		}else{ /** start -- System Administrator Access **/
			if($process_id>0):
				$sql = new Sql($this->dbAdapter);
				$select = $sql->select('sys_process')->where(['id' => $process_id]);
				$statement = $sql->prepareStatementForSqlObject($select);
				$processdetails = $statement->execute();
				foreach($processdetails as $prow);
				$table_name = $prow['table_name'];
				if($id != 0):
					$sql = new Sql($this->dbAdapter);
					$select = $sql->select($table_name)->where(['id' => $id]);
					$statement = $sql->prepareStatementForSqlObject($select);
					$records = $statement->execute();
					if(sizeof($records)<=0):
						$response = $e -> getResponse();
						$response -> getHeaders() -> addHeaderLine('Location', $e -> getRequest() -> getBaseUrl() . '/404');
						$response -> setStatusCode(404);
						return array();
					endif;
				else:
					$permission = array(
							'location_permit' => $location_permit,
							'activity_permit' => $activity_permit,
							'onlyifcreator_permit' => $onlyifcreator_permit,
							'status_permit' => $status_permit,
					);
					return $permission;
				endif;
			else:
				$permission = array(
						'location_permit' => $location_permit,
						'activity_permit' => $activity_permit,
						'onlyifcreator_permit' => $onlyifcreator_permit,
						'status_permit' => $status_permit,
				);
				return $permission;
			endif;
		} /** end -- System Administrator Access **/
		endif;
	}
	/**
	 * GET ALL
	 * PERMITTED ROLES - USER MANAGEMENT
	 */
	public function getrole(){
		$auth = new AuthenticationService();
		if ($auth->hasIdentity()) {
			$login_id = $auth->getIdentity()->id;
		
			$login_role_array = explode(',',$auth->getIdentity()->role);
			$login_role = (sizeof($login_role_array)>0)?$auth->getIdentity()->role:0;
		}else{
			$login_id = 0;
			$login_role = 1;
		}
		/** Get Database Adapter **/
		$this->dbAdapter = $this->_container->get('Laminas\Db\Adapter\Adapter');
		/** Get Highest Role **/
		$sql = new Sql($this->dbAdapter);
		$select = $sql->select('sys_roles')->columns(['role' => new \Laminas\Db\Sql\Expression('MAX(id)')]);
		$statement = $sql->prepareStatementForSqlObject($select);
		$hrole = $statement->execute();
        foreach($hrole as $hrow);
		$highest_role = $hrow['role'];

		if(!in_array($highest_role,$login_role_array)){
			$sql = new Sql($this->dbAdapter);
			$select = $sql->select('sys_roles')
				->where(['status' => '1']);
			$roleIds = [1];
			if($highest_role) {
				$roleIds[] = $highest_role;
			}
			$select->where(new NotIn('id', $roleIds));
		}else{
			$sql = new Sql($this->dbAdapter);
			$select = $sql->select('sys_roles')
				->where(['status' => '1'])
				->where(new NotIn('id', [1]));
		}
		$rolelist = array();
		$statement = $sql->prepareStatementForSqlObject($select);
		$roles = $statement->execute(); 
		foreach($roles as $role):
			array_push($rolelist, $role);
		endforeach;
		return $rolelist;
	}
	/**
	 * GET ALL
	 * PERMITTED REGION
	 */
	public function getregion(){
		$auth = new AuthenticationService();
		if ($auth->hasIdentity()) {
			$login_role_array = explode(',',$auth->getIdentity()->role);
			$admin_location_array = explode(',',$auth->getIdentity()->admin_location);
			$admin_location = (sizeof($admin_location_array)>0 && !empty($admin_location_array))?$auth->getIdentity()->admin_location:0;
		}else{
			$login_role_array = array(0);
			$admin_location = 0;
		}
		/** Get Database Adapter **/
		$this->dbAdapter = $this->_container->get('Laminas\Db\Adapter\Adapter');
		/** Get Highest Role **/
		$sql = new Sql($this->dbAdapter);
		$select = $sql->select('sys_roles')->columns(['role' => new \Laminas\Db\Sql\Expression('MAX(id)')]);
		$statement = $sql->prepareStatementForSqlObject($select);
		$hrole = $statement->execute();
        foreach($hrole as $hrow);
		$highest_role = $hrow['role'];
		
		$regionlist = array();
		$sql = new Sql($this->dbAdapter);
		$select = $sql->select('adm_region')->where(['status' => '1']);
		$statement = $sql->prepareStatementForSqlObject($select);
		$regions = $statement->execute(); 
		foreach($regions as $region):
			array_push($regionlist, $region);
		endforeach;
		
		return $regionlist;
	}
	/**
	 * GET ALL & GET
	 * PERMITTED LOCATION
	 */
	public function getlocation($region_data = 0){
		$auth = new AuthenticationService();
		if ($auth->hasIdentity()) {
			$login_role_array = explode(',',$auth->getIdentity()->role);
			$admin_location_array = explode(',',$auth->getIdentity()->admin_location);
			$admin_location = (sizeof($admin_location_array)>0 && !empty($admin_location_array))?$auth->getIdentity()->admin_location:0;
		}else{
			$login_role_array = array(0);
			$admin_location = 0;
		}
		//get Database Adapter
		$this->dbAdapter = $this->_container->get('Laminas\Db\Adapter\Adapter');
		/** Get Highest Role **/
		$sql = new Sql($this->dbAdapter);
		$select = $sql->select('sys_roles')->columns(['role' => new \Laminas\Db\Sql\Expression('MAX(id)')]);
		$statement = $sql->prepareStatementForSqlObject($select);
		$hrole = $statement->execute();
        foreach($hrole as $hrow);
		$highest_role = $hrow['role'];

		$locationlist = array();
		$sql = new Sql($this->dbAdapter);
		$select = $sql->select('adm_location')->where(['status' => '1']);
		if($region_data != 0) {
			$select->where(['region' => $region_data]);
		}
		$statement = $sql->prepareStatementForSqlObject($select);
		$locations = $statement->execute(); 
		foreach($locations as $location):
			array_push($locationlist, $location);
		endforeach;
        return $locationlist;
	}
	/**
	 * COUNT
	 * PERMITTED LOCATION COUNT
	 */
	public function getlocationCount($region_data = 0, $user_location = 0){
		$auth = new AuthenticationService();
		if ($auth->hasIdentity()) {
			$login_role_array = explode(',',$auth->getIdentity()->role);
			$admin_location_array = explode(',',$auth->getIdentity()->admin_location);
			$admin_location = (sizeof($admin_location_array)>0 && !empty($admin_location_array))?$auth->getIdentity()->admin_location:0;
		}else{
			$login_role_array = array(0);
			$admin_location = 0;
		}
		//get Database Adapter
		$this->dbAdapter = $this->_container->get('Laminas\Db\Adapter\Adapter');
		/** Get Highest Role **/
		$sql = new Sql($this->dbAdapter);
		$select = $sql->select('sys_roles')->columns(['role' => new \Laminas\Db\Sql\Expression('MAX(id)')]);
		$statement = $sql->prepareStatementForSqlObject($select);
		$hrole = $statement->execute();
        foreach($hrole as $hrow);
		$highest_role = $hrow['role'];

		$sql = new Sql($this->dbAdapter);
		$select = $sql->select('adm_location')
			->columns(['count' => new \Laminas\Db\Sql\Expression('COUNT(*)')])
			->where(['status' => '1']);
		if($region_data != 0) {
			$select->where(['region' => $region_data]);
		}
		if($user_location != 0) {
			$locationIds = array_map('intval', explode(',', $user_location));
			$select->where(new In('id', $locationIds));
		}
		$statement = $sql->prepareStatementForSqlObject($select);
		$locations = $statement->execute(); 
		foreach($locations as $location);
		$locationcount = $location['count'];
		
		return $locationcount;
	}
	/**
	 * GET ALL
	 * PERMITTED ACTIVITY
	 */
	public function getactivity(){
		$auth = new AuthenticationService();
		if ($auth->hasIdentity()) {
			$login_role_array = explode(',',$auth->getIdentity()->role);
			$admin_activity_array = explode(',',$auth->getIdentity()->admin_activity);
			$admin_activity = (sizeof($admin_activity_array)>0 && !empty($admin_activity_array))?$auth->getIdentity()->admin_activity:0;
		}else{
			$login_role_array = array(0);
			$admin_activity = 0;
		}
		//get Database Adapter
		$this->dbAdapter = $this->_container->get('Laminas\Db\Adapter\Adapter');
		/** Get Highest Role **/
		$sql = new Sql($this->dbAdapter);
		$select = $sql->select('sys_roles')->columns(['role' => new \Laminas\Db\Sql\Expression('MAX(id)')]);
		$statement = $sql->prepareStatementForSqlObject($select);
		$hrole = $statement->execute();
        foreach($hrole as $hrow);
		$highest_role = $hrow['role'];

		$activitylist = array();
		$sql = new Sql($this->dbAdapter);
		$select = $sql->select('adm_activity')->where(['status' => '1']);
		$statement = $sql->prepareStatementForSqlObject($select);
		$activities = $statement->execute(); 
		foreach($activities as $activity):
			array_push($activitylist, $activity);
		endforeach;
		
		return $activitylist;
	}
	/**
	 * COUNT
	 * PERMITTED ACTIVITY COUNT
	 */
	public function getactivityCount(){
		$auth = new AuthenticationService();
		if ($auth->hasIdentity()) {
			$login_role_array = explode(',',$auth->getIdentity()->role);
			$admin_activity_array = explode(',',$auth->getIdentity()->admin_activity);
			$admin_activity = (sizeof($admin_activity_array)>0 && !empty($admin_activity_array))?$auth->getIdentity()->admin_activity:0;
		}else{
			$login_role_array = array(0);
			$admin_activity = 0;
		}
		//get Database Adapter
		$this->dbAdapter = $this->_container->get('Laminas\Db\Adapter\Adapter');
		/** Get Highest Role **/
		$sql = new Sql($this->dbAdapter);
		$select = $sql->select('sys_roles')->columns(['role' => new \Laminas\Db\Sql\Expression('MAX(id)')]);
		$statement = $sql->prepareStatementForSqlObject($select);
		$hrole = $statement->execute();
        foreach($hrole as $hrow);
		$highest_role = $hrow['role'];

		$sql = new Sql($this->dbAdapter);
		$select = $sql->select('adm_activity')
			->columns(['count' => new \Laminas\Db\Sql\Expression('COUNT(*)')])
			->where(['status' => '1']);
		$statement = $sql->prepareStatementForSqlObject($select);
		$activities = $statement->execute(); 
		foreach($activities as $activity);
		$activitycount = $activity['count'];
		
		return $activitycount;
	}
}
