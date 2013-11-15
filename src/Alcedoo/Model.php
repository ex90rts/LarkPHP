<?php
namespace Alcedoo;

use Alcedoo\Mysql\Query;
use Alcedoo\Model\DataList;

abstract class Model{
	/**
	 * Holding the mysql instance
	 * 
	 * @var Alcedoo\Mysql
	 */
	private $_mysql;
	
	/**
	 * table name of this model
	 * 
	 * @var string
	 */
	private $_table;
	
	/**
	 * user defined fields and validation rules of this model
	 * 
	 * @var array
	 */
	private $_fields;
	
	/**
	 * user defined field data of this model
	 * 
	 * @var array
	 */
	private $_data = array();
	
	/**
	 * stat of the data of the model instance was loaded or not
	 * 
	 * @var boolean
	 */
	private $_loaded = false;
	
	/**
	 * validation errors holder after validate method invoked
	 * 
	 * @var array
	 */
	private $_errors = array();
	
	/**
	 * primary key holder
	 * 
	 * @var int
	 */
	public $id = 0;

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
	 * Set field data to this model, only setted fields allowed
	 * 
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value){
		if (isset($this->_fields[$name])){
			$this->_data[$name] = $value;
		}
	}
	
	/**
	 * Read field data from this model
	 * 
	 * @param string $name
	 */
	public function __get($name){
		if (isset($this->_data[$name])){
			return $this->_data[$name];
		}
		
		return null;
	}
	
	/**
	 * Use print_r to ouput current model data
	 * 
	 * @return mixed
	 */
	public function __toString(){
		return "Model:" . get_called_class() ."({$this->id})\r\n". print_r($this->_data, true);
	}
	
	/**
	 * Return this model's table name, the default value is current model class name
	 */
	protected function getTable(){
		$calledClass = get_called_class();
		$nameParts = explode('\\', $calledClass);
		return end($nameParts) . 's';
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
				$this->_data[$field] = $data[$field];
			}
		}
		if (isset($data[PRIMARY_KEY])){
			$this->id = $data[PRIMARY_KEY];
		}
		
		$this->_loaded = true;
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
    	->select()
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
    	->select()
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
     * @param int $limit default limit is 1000
     * @return \Alcedoo\Model\DataList|boolean
     */
    public function findDataByFilter($filter=array(), $sort=array(), $limit=1000){
    	$query = new Query();
    	$query->table($this->_table)
    	->select()
    	->where($filter)
    	->limit($limit);echo $query;
    	$list = $this->_mysql->exec($query);
    	if ($list){
    		return new DataList(get_called_class(), $list);
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
    		//special check of primary key
    		if ($field == 'id'){
    			if (isset($this->id) && !is_int($this->id)){
    				$this->_errors[] = "Model primary key {$field} must be an integer";
    			}
    			continue;
    		}
    		
    		$value = $this->_data[$field];
    		
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
    			$this->_data[$field] = $info['default'];
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
     * Save current model data to database
     * 
     * @return boolean
     */
    public function save(){
    	$data = $this->_data;
    	if ($this->id > 0){
    		$res = $this->update(array('id'=>$this->id), $data);
    	}else{
    		$res = $this->insert($data);
    	}
    	
    	return $res;
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
			$this->id = $res;
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
	public function delete($where=array()){
		if (empty($where)){
			$where = array('id'=>$this->id);
		}
		$query = new Query();
		$query->table($this->_table)
			->where($where)
			->delete();
		$this->id = 0;
		$this->_data = array();
		return $this->_mysql->exec($query);
	}
    
	/**
	 * Get validation errors
	 */
    public function errors(){
    	return $this->_errors;
    }
}