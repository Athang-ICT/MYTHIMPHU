<?php
/**
 * Helper -- TabsHelper
 * chophel@athang.com
 * 2022
 */
namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Acl\Model\AclTable;
use Interop\Container\ContainerInterface;

class TabsHelper extends AbstractHelper
{	
	protected $aclTable;
	private $_container;
	 
	public function __construct(AclTable $aclTable, ContainerInterface $container)
	{
		$this->aclTable = $aclTable;
		$this->_container = $container;
	}
	
	public function __invoke($action=NULL,$id=NULL)
	{  
		$routeMatch = $this->_container->get('Application')->getMvcEvent()->getRouteMatch();
		$routeName = $routeMatch->getMatchedRouteName();
		$arr = explode('/', $routeName);
		$routeName = $arr[0];
		$routeAction = $routeMatch->getParam('action');	
		$routeParamID = $routeMatch->getParam('id');
		$routeResource = $this->aclTable->getColumn(array('route'=>$routeName),'resource');
		$acl_id = $this->aclTable->getColumn(array('route'=>$routeName, 'resource' => $routeResource, 'action'=>$routeAction),'id');
		$user_role= $this->view->identity()->role;	
		$highestRole = $this->aclTable->getHighestRole();
		if($action != NULL):
			$tabs ="";
			$tabs.= "<ul class='nav nav-tabs padding-18' id='myTab'>";
			for($i=0;$i<sizeof($action);$i++):
				$acl_permission = $this->aclTable->renderTabs(array('id' => $action[$i]), $user_role,$highestRole);
				if(sizeof($acl_permission)>0):
					foreach($acl_permission as $row);
					$class = ($row['route']== $routeName && $row['acl_id']==$acl_id)?'active':'';
					$tabs.="<li class='".$class."'>
								<a title='".$row['menu']."' href='".$this->view->url($row['route'], array('action' => $row['action'], 'id'=> $id))."'>
									<i class='blue ace-icon ".$row['icon']." bigger-120'></i><span class='hidden-xs'>
								".$row['menu']."</span></a></li>";
				endif;
			endfor;
			$tabs.="</ul><div class='space-6'></div>";
			return $tabs;
		endif;
	} 	
}
