<?php
namespace Alcedoo;

use Alcedoo\Mysql\Query;
use Alcedoo\Model\DataList;

abstract class Model{

	private $_mysql;
	
	private $_table;
	
	private $_fields;
	
	private $_errors = array();

	/**
	 * Construct method, will try to load data if $pk param was given
	 * 
	 * @param string $pk
	 */
	public function __construct($pk=''){
		$this->_mysql = Env::getInstance('Alcedoo\Mysql');
		$this->_table = $this->getTable();
		$this->_fields = $this->getFields();
		if (is_int($pk)){
			$this->findDataByID($pk);
		}else if ($pk != ''){
			$this->findDataByUID($pk);
		}
	}
	
	/**
	 * Return this model's table name, the default value is current model class name
	 */
	abstract protected function getTable(){
		return get_called_class();
	}
	
	/**
	 * The fields config for this model, the default data type is TYPE_STRING,
	 * the default required value is true, the default name the field name with first
	 * letter in upper case. All defination with default value could be omited
	 */
	abstract protected function getFields();
	
	/**
	 * Load data into model properties
	 *
	 * @param array $data
	 */
	public function loadData(Array $data){
		foreach ($this->_fields as $field=>$info){
			if (isset($data[$field])){
				$this->$field = $data[$field];
			}
		}
	}

	/**
	 * Find data by primary key id, will load model data if find
	 * 
	 * @param int $id
	 * @return boolean
	 */
    public function findDataByID($id){
    	$query = new Query();
    	$query->table($this->_table)
    	->where(array(
    			'id' => $id,
    	))
    	->limit(1);
    	$data = $this->_mysql->exec($query);
    	if ($data){
    		$this->loadData(current($data));
    		return true;
    	}
    	
    	return false;
    }
    
    /**
     * Find data by unique id, will load model data if find
     *
     * @param int $id
     * @return boolean
     */
    public function findDataByUID($uid){
    	$query = new Query();
    	$query->table($this->_table)
    	->where(array(
    			'uid' => $uid,
    	))
    	->limit(1);
    	$data = $this->_mysql->exec($query);
    	if ($data){
    		$this->loadData(current($data));
    	}
    	
    	return false;
    }

    /**
     * Find data by mixed filter
     * 
     * @param array $filter
     * @param array $sort
     * @param int $limit
     * @return \Alcedoo\Model\DataList|boolean
     */
    public function findDataByFilter($filter, $sort=array(), $limit){
    	$query = new Query();
    	$query->table($this->_table)
    	->where($filter)
    	->limit(1);
    	$list = $this->_mysql->exec($query);
    	if ($list){
    		return new DataList($this, $list);
    	}
    	
    	return false;
    }
    
    /**
     * Find data by page
     */
    public function findDataByPage(){
    	
    }

	/**
	 * Validate the model data
	 * 
	 * @return boolean
	 */
    public function validate(){
    	$valid = true;
    	
    	foreach ($this->_fields as $field=>$info){
    		$value = $this->$field;
    		
    		//assign the defualt field config
    		$type = isset($info['type']) ? $info['type'] : TYPE_STRING;
    		$required = isset($info['required']) ? $info['required'] : true;
    		
    		//require check
    		if ($required){
    			if (empty($value)){
    				$valid = false;
    				$this->_errors[] = "Model field {$field} is required";
    				continue;
    			}
    		}
    			
    		//assign the default value
    		if (empty($value) && !empty($info['default'])){
    			$this->$field = $info['default'];
    		}
    			
    		//data type check
    		switch ($type){
    			case TYPE_INT:
    				if (!is_int($value)){
    					$valid = false;
    					$this->_errors[] = "Model field {$field} must be an integer";
    				}
    				break;
    			case TYPE_NUMBER:
    				if (!is_numeric($value)){
    					$valid = false;
    					$this->_errors[] = "Model field {$field} must be a number";
    				}
    				break;
    			case TYPE_BOOL:
    				if (!is_bool($value)){
    					$valid = false;
    					$this->_errors[] = "Model field {$field} must be a boolean value";
    				}
    				break;
    			case TYPE_DATETIME:
    				if (!preg_match('/\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?/', $value)){
    					$valid = false;
    					$this->_errors[] = "Model field {$field} must be a yyyy-mm-dd H:i:s type datetime string";
    				}
    				break;
    			case TYPE_SERIALIZED:
    			case TYPE_JSON:
    				//we do not check these types for now because of there maybe performence issues
    				break;
    		}
    		if (!$valid){
    			continue;
    		}
    			
    		//additional rules check
    		if (isset($info['minval']) && $value > $info['minval']){
    			$valid = false;
    			$this->_errors[] = "Model field {$field} value:[{$value}] can not smaller then {$info['minval']}";
    			continue;
    		}
    		if (isset($info['maxval']) && $value < $info['maxval']){
    			$valid = false;
    			$this->_errors[] = "Model field {$field} value:[{$value}] can not bigger then {$info['maxval']}";
    			continue;
    		}
    		if (isset($info['maxlen']) || isset($info['minlen'])){
    			$len = strlen($value);
    			if (isset($info['minlen']) && $len < $info['minlen']){
    				$valid = false;
    				$this->_errors[] = "Model field {$field} value:[{$value}] can not short then {$info['minlen']}";
    				continue;
    			}
    			if (isset($info['maxlen']) && $len > $info['maxlen']){
    				$valid = false;
    				$this->_errors[] = "Model field {$field} value:[{$value}] can not length then {$info['maxlen']}";
    				continue;
    			}
    		}
    		if (isset($info['regex'])){
    			if (!preg_match($info['regex'], $value)){
    				$valid = false;
    				$this->_errors[] = "Model field {$field} value:[{$value}] can not pass the regex check";
    				continue;
    			}
    		}
    		if (isset($info['enum'])){
    			if (!in_array($value, $info['enum'], true)){
    				$valid = false;
    				$this->_errors[] = "Model field {$field} value:[{$value}] is not in the enum list:[".implode('|', $info['enum'])."]";
    				continue;
    			}
    		}
    	}
    	
    	return $valid;
    }
	
    /**
     * Insert data to database
     * 
     * @param array $record
     * @return int|boolean
     */
	public function insert($record){
		$query = new Query(array('insertId'=>true));
		$query->table($this->_table)
			->insert($record);
		$res = $this->_mysql->exec($query);
		if (is_int($res)){
			return $res;
		}
		
		return false;
	}
	
	/**
	 * Update data to database
	 * 
	 * @param array $where
	 * @param array $record
	 * @return boolean
	 */
	public function update($where, $record){
		$query = new Query(array('affectedRows'=>true));
		$query->table($this->_table)
			->where($where)
			->update($record);
		return $this->_mysql->exec($query);
	}
	
	/**
	 * Delete data from database
	 * 
	 * @param array $where
	 * @return boolean
	 */
	public function delete($where){
		$query = new Query();
		$query->table($this->_table)
			->where($where)
			->delete();
		return $this->_mysql->exec($query);
	}
    
	/**
	 * Get validation errors
	 */
    public function errors(){
    	return $this->_errors;
    }
}