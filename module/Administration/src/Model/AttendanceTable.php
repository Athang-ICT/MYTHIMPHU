<?php
namespace Administration\Model;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Expression;

class AttendanceTable extends AbstractTableGateway //implements AdapterAwareInterface
{
	protected $table = 'sys_attendance'; //tablename
	
	public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

	/**
	 * Return All records of table
	 * @return Array
	 */
	public function getAll()
	{
	    $adapter = $this->adapter;
	    $sql = new Sql($adapter);
	    $select = $sql->select();
	    $select->from($this->table)
				->order('id desc');
	    
		$selectString = $sql->getSqlStringForSqlObject($select);
	    $results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
	    return $results;
	}
	
    /**
	 * Return records of given id
	 * @param Int $id
	 * @return Array
	 */
	public function get($param)
	{  
        $where = ( is_array($param) )? $param: array('id' => $param);
		$adapter = $this->adapter;
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from($this->table)
		       ->where($where)
			   ->order('id desc' );
		
		$selectString = $sql->getSqlStringForSqlObject($select);
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
		return $results;
	}
	
	/**
     * Return column value of given where condition | id
     * @param Int|array $parma
     * @param String $column
     * @return String | Int
     */
    public function getColumn($param, $column)
    {         
		$where = ( is_array($param) )? $param: array('id' => $param);
		$fetch = array($column);
		$adapter = $this->adapter;       
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from($this->table);
		$select->columns($fetch);
		$select->where($where);

		$selectString = $sql->getSqlStringForSqlObject($select);
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();          
	   $columns='';
	   foreach ($results as $result):
		  $columns =  $result[$column];
	   endforeach; 
	  
	   return $columns;       
    }
    /**
	 * Save record
	 * @param String $array
	 * @return Int
	 */
	public function save($data)
	{
	    
		if ( !is_array($data) ) $data = $data->toArray();
		$id = isset($data['id']) ? (int)$data['id'] : 0;
		
		if ( $id > 0 )
		{
			$result = ($this->update($data, array('id'=>$id)))?$id:0;
		} else {
			$this->insert($data);
			$result = $this->getLastInsertValue(); 
		}	    	    
		return $result;	 	     
	}

	/**
     *  Return Boolean
     *  @param int $id
     *  @return true | false
     */
	public function remove($id)
	{
		return $this->delete(array('id' => $id));
	}
	/**
	 * Return Min value of the column
	 * @param Array $where
	 * @param String $column
	 * @return String | Int
	 */
	public function getMin($column,$where = NULL)
	{
		$adapter = $this->adapter;
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from($this->table);
		$select->columns(array(
				'min' => new Expression('MIN('.$column.')')
		));
		if($where!=NULL){
			$select->where($where);
		}
		$selectString = $sql->getSqlStringForSqlObject($select);
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
	
		foreach ($results as $result):
		$column =  $result['min'];
		endforeach;
	
		return $column;
	}
	
	
	/**
	 * Return max value of the column
	 * @param Array $where
	 * @param String $column
	 * @return String | Int
	 */
	public function getMax($column, $where = NULL)
	{
		$adapter = $this->adapter;
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from($this->table);
		$select->columns(array(
				'max' => new Expression('MAX('.$column.')')
		));
		if($where!=NULL){
			$select->where($where);
		}
		$selectString = $sql->getSqlStringForSqlObject($select);
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
	
		foreach ($results as $result):
		$column =  $result['max'];
		endforeach;
	
		return $column;
	}
	/**
	 * Return records of given year and month
	 * @param Int $id
	 * @return Array
	 */
	public function getDateWise($column,$year,$month,$day,$user)
	{	
		$adapter = $this->adapter;
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from($this->table)
                 ->columns(array(
					'id', 'user', 'entry', 'exit', 'date', 'location', 'late_reason', 'early_reason', 'ip_address1', 
					'ip_address2', 'status',
					'year' => new Expression('YEAR('.$column.')'),
					'month' => new Expression('MONTH('.$column.')'),
					'day' => new Expression('DAY('.$column.')'),
			   ))->having(array('year' => $year,'day'=>$day))
			   ->where(array('user'=>$user))
			   ->order(array('id DESC'));
		   
			if($month != '-1'){
				$select->having(array('month' => $month));
			}
			
		$selectString = $sql->getSqlStringForSqlObject($select);
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
		//print_r($results);exit;
		return $results;
	}
	/**
	* check particular row is present in the table 
	* with given column and its value
	* MODIFIED FOR FROM VALIDATION (REMOTE VALIDATOR) 
	*/
	public function checkAvailability($column, $value)
	{
		$column = $column; $value = $value;
		$resultSet = $this->select(function(Select $select) use ($column, $value){
		$select->where(array($column => $value));
		});
		$resultSet = $resultSet->toArray();
		return (sizeof($resultSet)>0)?false:true;
	}
	
	/**
	 * Return Count value of the column
	 * @param Array $where
	 * @return String | Int
	 */
	public function getCount($where = NULL)
	{
		$adapter = $this->adapter;
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from($this->table)
			->columns(array('count' => new Expression('COUNT(*)')));
		
		if($where != NULL):
			$select->where($where);
		endif;
		
		$selectString = $sql->getSqlStringForSqlObject($select);
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
		
		foreach($results as $row);		
		return $row['count'];
	}

	/**
	 * Return Count value of the column
	 * @param Array $where
	 * @param String $column
	 * @return String | Int
	 */
	public function getSum($column, $where = NULL)
	{
		$adapter = $this->adapter;
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from($this->table);
		$select->columns(array(
				'sum' => new Expression('SUM('.$column.')')
		));
		if($where!=NULL){
			$select->where($where);
		}
		$selectString = $sql->getSqlStringForSqlObject($select);
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
	
		foreach ($results as $result):
			$column =  $result['sum'];
		endforeach;
	
		return $column;
	}
	/**
	 * Return id's|columns'value  which is not present in given array
	 * @param Array $param
	 * @param String column
	 * @return Array
	 */
	public function getNotIn($param, $column='employee', $where=NULL)
	{
		$param = ( is_array($param) )? $param: array($param);
		$where = (is_array($column)) ? $column: $where;
		//$column = (is_array($column)) ? 'employee' : $column;
		$adapter = $this->adapter;
		$sql = new Sql($adapter);
		$select = new Select();
		$select->from($this->table)
		//->columns(array('employee'))
		->where->notIn($column, $param);
			$select->where(array('status'=>1));
		
	  $selectString = $sql->getSqlStringForSqlObject($select);
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
		
		return $results;
	}
	/**
	 * Return records of given id
	 * @param Int $id
	 * @return Array
	 */
	public function getAtt($param)
	{  
        $where = ( is_array($param) )? $param: array('a.id' => $param);
		$adapter = $this->adapter;
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from(array('a'=>$this->table))
				->join(array('u'=>'sys_users'),'a.user=u.id', array('user_id' => 'id'))
				->join(array('e'=>'hr_employee'),'u.employee=e.id', array('emp_id' => 'id'))
		       ->where($where);
			
		$selectString = $sql->getSqlStringForSqlObject($select);
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
		return $results;
	}
	/**
	 * Return records of given id
	 * @param Int $id
	 * @return Array
	 */
	public function getAttbyDay($param,$year,$month,$day)
	{  
		$att_date=date("$year-$month-$day");
        $where = ( is_array($param) )? $param: array('a.id' => $param);
		$adapter = $this->adapter;
		$sql = new Sql($adapter);
		$select = $sql->select();
		$select->from(array('a'=>$this->table))
				->join(array('u'=>'sys_users'),'a.user=u.id', array('user_id' => 'id'))
				->join(array('e'=>'hr_employee'),'u.employee=e.id', array('emp_id' => 'id'))
		       ->where($where);
			  $select->where(array('a.date'=>$att_date));
		$selectString = $sql->getSqlStringForSqlObject($select);
		$results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->toArray();
		return $results;
	}
	
}

