<?php
namespace Alcedoo;

use Alcedoo\Model\DataList;
use Alcedoo\Mysql\Query;

abstract class Model{

	private $table;
	
	private $mysql;
	
	private $errors = array();

	public function __construct($key=''){
		$this->table = $this->getTableName();
		$this->mysql = Env::getInstance('Alcedoo\Mysql');
		if (is_int($key)){
			$this->findDataByID($key);
		}else if ($key != ''){
			$this->findDataByUID($key);
		}
	}
	
	abstract protected function getTableName();
	
	abstract protected function getValidRules();
	
    private function getProperties(){
    	$class = new \ReflectionClass(get_called_class());
    	$properties = $class->getProperties(\ReflectionProperty::IS_PUBLIC);
    	$result = array();
    	foreach ($properties as $property){
    		$result[] = $property->name;
    	}
    	return $result;
    }

    public function findDataByID($table, $id){
    	$query = new Query();
    	$query->table($table)
    	->where(array(
    			'id' => $id,
    	))
    	->limit(1);
    	return $this->mysql->exec($query);
    }
    
    public function findDataByUID($table, $uid){
    	$query = new Query();
    	$query->table($table)
    	->where(array(
    			'uid' => $uid,
    	))
    	->limit(1);
    	return $this->mysql->exec($query);
    }

    public function findDataByFilter($filter, $sort=array(), $limit){
    	$list = $this->engine->model($this)->findDataByFilter();
    	if ($list){
    		return new DataList($this, $list);
    	}
    	
    	return false;
    }
    
    public function findDataByPage(){
    	
    }
    
    public function loadData(Array $data){
    	$properties = $this->getProperties();
    	foreach ($properties as $property){
    		if (isset($data[$property])){
    			$this->$property = $data[$property];
    		}
    	}
    }

    public function validate(){
    	$valid = true;
    	$properties = $this->getProperties();
    	$rules = $this->getValidRules();
    	foreach ($rules as $property=>$value){
    		foreach ($value as $key=>$rule){
    			if (is_int($key)){
	    			switch ($rule){
	    				case 'required':
	    					if (empty($this->$property)){
	    						$valid = false;
	    						$this->errors[] = "Model property {$property} is required";
	    					}
	    					break;
	    				case 'int':
	    					if (!is_int($this->$property)){
	    						$valid = false;
	    						$this->errors[] = "Model property {$property} must be an integer";
	    					}
	    					break;
	    				case 'numeric':
	    					if (!is_numeric($this->$property)){
	    						$valid = false;
	    						$this->errors[] = "Model property {$property} must be a number";
	    					}
	    					break;
	    				case 'bool':
	    					if (!is_bool($this->$property)){
	    						$valid = false;
	    						$this->errors[] = "Model property {$property} must be a boolean value";
	    					}
	    					break;
	    			}
    			}else{
    				if ($key == 'regex'){
    					if (!preg_match($value, $this->$property)){
    						$valid = false;
    						$this->errors[] = "Model property {$property} value:[{$this->$property}] can not pass regex check";
    					}
    				}elseif ($key == 'enum' && is_array($value)){
    					if (!in_array($this->$property, $value, true)){
    						$valid = false;
    						$this->errors[] = "Model property {$property} value:[{$this->$property}] not in the num list:[".implode('|', $value)."]";
    					}
    				}
    			}
    		}
    	}
    	
    	return $valid;
    }
	
	public function insert($table, $record){
		$query = new Query(array('insertId'=>true));
		$query->table($table)->insert($record);
		$res = $this->mysql->exec($query);
		if (is_int($res)){
			return $res;
		}
		
		return false;
	}
	
	public function update($table, $where, $record){
		$query = new Query(array('affectedRows'=>true));
		$query->table($table)->where($where)->update($record);
		return $this->mysql->exec($query);
	}
	
	public function delete($table, $where){
		$query = new Query();
		$query->table($table)->where($where)->delete();
		return $this->mysql->exec($query);
	}
    
    public function errors(){
    	return $this->errors;
    }
}