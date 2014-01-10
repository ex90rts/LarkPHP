<?php
namespace Lark;

use Lark\Mysql\Query;
use Lark\Model\DataList;
use Lark\Exception\ModelHashKeyException;

abstract class Model{
	
	/**
	 * Model field data type
	 */
	const TYPE_INT = 'INT';
	const TYPE_NUMBER = 'NUMBER';
	const TYPE_STRING = 'STRING';
	const TYPE_BOOL = 'BOOL';
	const TYPE_DATETIME = 'DATETIME';
	const TYPE_SERIALIZED = 'SERIALIZED';
	const TYPE_JSON = 'JSON';
	
	/*MySQL primary key name*/
	const PRIMARY_KEY = 'id';
	
	/*Primary cache expire time*/
	const CACHE_SECOND  = 5;
	const CACHE_SHORT   = 3600;
	const CACHE_NORMAL  = 86400;
	const CACHE_LONG    = 2592000;
	
	/**
	 * Holding the mysql instance
	 * 
	 * @var Lark\Mysql
	 */
	private $_mysql;
	
	/**
	 * table name of this model
	 * 
	 * @var string
	 */
	private $_table;
	
	/**
	 * Hash key for multi tables
	 * @var number
	 */
	private $_hashkey = false;
	
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
	 * temp data for holding data before and after saving events
	 *
	 * @var array
	 */
	private $_temp = array();
	
	/**
	 * stat of the data of the model instance was loaded or not
	 * 
	 * @var boolean
	 */
	private $_loaded = false;
	
	/**
	 * Record data changes after loaded
	 * 
	 * @var array
	 */
	private $_changes = array();
	
	/**
	 * validation errors holder after validate method invoked
	 * 
	 * @var array
	 */
	private $_errors = array();
	
	/**
	 * Lark\Cache instance holder
	 *
	 * @var Lark\Cache
	 */
	protected $_cache = null;
	
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
	public function __construct($pk=0, $hashkey=false){
		$this->_mysql   = App::getInstance('Mysql');
		$this->_table   = $this->getTable();
		$this->_hashkey = $hashkey;
		$this->_fields  = $this->getFields();
		
		if ($this->hashField() && $hashkey===false){
			throw new ModelHashKeyException("hashkey required in Model {$this->_table} by field {$this->hashField()}");
		}
		
		$isCache = App::getOption('modelCache');
		if ($isCache){
			$this->_cache = App::getInstance('Cache');
		}
		
		if ($pk && is_numeric($pk)){
			$this->findDataByID($pk);
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
			
			if ($this->_loaded){
				$this->_changes[$name] = $value;
			}
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
	abstract protected function getTable();
	
	/**
	 * The fields config for this model, the default data type is TYPE_STRING,
	 * the default required value is true, the default name the field name with first
	 * letter in upper case. All defination with default value could be omited
	 */
	abstract protected function getFields();
	
	/**
	 * Return table hash field
	 * @return NULL\string
	 */
	protected function hashField(){
		return null;
	}
	
	/**
	 * Any events need to perform before save to database, only will be invoked if
	 * you are using Model:save() method to save current model data
	 */
	protected function beforeSave(){}
	
	/**
	 * Any events need to perform after save to database, only will be invoked if
	 * you are using Model:save() method to save current model data
	 */
	protected function afterSave(){}
	
	/**
	 * Any events need to perform after load data to model instance
	 */
	protected function afterLoad(){}
	
	/**
	 * Return cache key, random prefixed to avoid conflict with user named keys
	 */
	private function getCacheKey($id=0){
		if ($this->_cache && ($id>0 || $this->id>0)){
			$hashkey = $this->_hashkey===false ? '' : $this->_hashkey . '_';
			return 't32v4fsmodel_' . $this->_table . '_' . $hashkey . ($id ?: $this->id);
		}else{
			return false;
		}
	}
	
	/**
	 * Save current data to cache
	 */
	private function saveCache($id=0){
		$key = $this->getCacheKey($id);
		if ($key){
			return $this->_cache->set($key, $this->getData(), self::CACHE_SHORT);
		}
		
		return false;
	}
	
	/**
	 * Read current data from cache
	 */
	private function readCache($id=0){
		$key = $this->getCacheKey($id);
		if ($key){
			return $this->_cache->get($key);
		}
		
		return false;
	}
	
	private function deleteCache($id=0){
		$key = $this->getCacheKey($id=0);
		if ($key){
			return $this->_cache->delete($key);
		}
		
		return false;
	}
	
	/**
	 * Helper method for save original data during beforeSave and afterSave
	 * @param string $key
	 * @param mixed $value
	 */
	protected function saveTemp($key, $value){
		$this->_temp[$key] = $value;
	}
	
	/**
	 * Helper method for read original data during beforeSave and afterSave
	 * @param string $key
	 */
	protected function readTemp($key){
		if (!isset($this->_temp[$key])){
			return false;
		}
		
		return $this->_temp[$key];
	}
	
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
		
		if (isset($data[Model::PRIMARY_KEY])){
			$this->id = $data[Model::PRIMARY_KEY];
		}
		
		$this->afterLoad();
		
		$this->_changes = array();
		$this->_loaded = true;
	}
	
	/**
	 * Return current model hash key
	 * @return int/string
	 */
	public function getHashKey(){
		return $this->_hashkey;
	}
	
	/**
	 * Return model data as an array if loaded, otherwise return null
	 */
	public function getData(){
		if ($this->_loaded){
			$this->_data[self::PRIMARY_KEY] = $this->id;
			return $this->_data;
		}else{
			return null;
		}
	}

	/**
	 * Find data by primary key id, will load model data if find
	 * 
	 * @param int $id
	 * @return boolean
	 */
    public function findDataByID($id){
    	$cached = $this->readCache($id);
    	if (!$cached){
	    	$query = Query::init()
	    		->table($this->_table)
	    		->hash($this->_hashkey)
	    		->select()
	    		->where(array('id' => $id))
	    		->limit(1);
	    	$data = $this->_mysql->exec($query);
	    	if ($data){
	    		$this->loadData(current($data));
	    		$this->saveCache();
	    		return $this;
	    	}
    	}else{
    		$this->loadData($cached);
    		return $this;
    	}
    	
    	return false;
    }
    
    /**
     * Find data by unique id, will load model data if find
     *
     * @param int $id
     * @return boolean
     */
    public function findDataByUniqid($uniqid){
    	$query = Query::init()
    		->table($this->_table)
    		->hash($this->_hashkey)
    		->select()
    		->where(array(
    			'uniqid' => $uniqid,
    		))
    		->limit(1);
    	$data = $this->_mysql->exec($query);
    	if ($data){
    		$this->loadData(current($data));
    		$this->saveCache();
    		return $this;
    	}
    	
    	return false;
    }
    
    /**
     * Count total records with where condition
     * 
     * @param array $where
     * @param array $group
     */
    public function countData($params=array()){
    	$fields = isset($params['fields']) ? $params['fields'] : array();
    	$where  = isset($params['where'])  ? $params['where']  : array();
    	$group  = isset($params['group'])  ? $params['group']  : array();
    	$having = isset($params['having']) ? $params['having'] : array();
    	
    	$fields['COUNT(*)'] = 'num';
    	
    	$query = Query::init()
    		->table($this->_table)
    		->hash($this->_hashkey)
    		->select($fields)
    		->where($where)
    		->group($group)
    		->having($having);
    	$result = $this->_mysql->exec($query);
    	if ($result){
    		return current($result)['num'];
    	}
    	
    	return 0;
    }

    /**
     * Just find all records
     * 
     * @param boolean $arr
     * @return Ambigous <\Lark\Model\DataList, unknown>|boolean
     */
    public function findAll($arr=false){
    	$query = Query::init()
	    	->table($this->_table)
	    	->hash($this->_hashkey)
	    	->select();
    	$list = $this->_mysql->exec($query);
    	if ($list){
    		return $arr ? $list : new DataList(get_called_class(), $list, $this->_hashkey);
    	}
    	 
    	return false;
    }
    
    /**
     * Find data by mixed filter
     * 
     * @param array $params Params for make Lark\Query
     *     <ul>
     *     <li><b>fields</b>: the fields need to select</li>
     *     <li><b>_hashkey</b>: hash key for table scatter</li>
     *     <li><b>where</b>: where condition</li>
     *     <li><b>order</b>: data order by</li>
     *     <li><b>group</b>: data group by</li>
     *     <li><b>having</b>: having condition when grouping</li>
     *     <li><b>offset</b>: offset index</li>
     *     <li><b>limit</b>: limit record count</li>
     *     </ul>
     * @param array $arr Do we need to return as array, Lark\Mysql\DataList by default
     * @return \Lark\Model\DataList|boolean
     */
    public function findAllFullaware($params=array(), $arr=false){
    	$fields = isset($params['fields']) ? $params['fields'] : array();
    	$where  = isset($params['where'])  ? $params['where']  : array();
    	$order  = isset($params['order'])  ? $params['order']  : array();
    	$limit  = isset($params['limit'])  ? $params['limit'] : '';
    	
    	$query = Query::init()
    		->table($this->_table)
    		->hash($this->_hashkey)
    		->select($fields)
    		->where($where)
    		->order($order);
    	
    	if (isset($params['group'])){
    		$query = $query->group($params['group']);
    	}
    	if (isset($params['having'])){
    		$query = $query->having($params['having']);
    	}
    	if (is_numeric($limit)){
    		$offset = isset($params['offset']) ? $params['offset'] : 0;
    		$query = $query->offset($offset)->limit($limit);
    	}
    	
    	$list = $this->_mysql->exec($query);
    	if ($list){
    		return $arr ? $list : new DataList(get_called_class(), $list, $this->_hashkey);
    	}
    	
    	return false;
    }
    
    /**
     * Find all record just with where condition
     * 
     * @param array $where
     * @param string $arr
     * @return Ambigous <\Lark\Model\DataList, unknown>|boolean
     */
    public function findAllWhere($where, $arr=false){
    	$query = Query::init()
    		->table($this->_table)
    		->hash($this->_hashkey)
    		->select()
    		->where($where);
    	$list = $this->_mysql->exec($query);
    	if ($list){
    		return $arr ? $list : new DataList(get_called_class(), $list, $this->_hashkey);
    	}
    	
    	return false;
    }
    
    /**
     * Find all record just with sort by
     *
     * @param array $sort
     * @param boolean $arr
     * @return Ambigous <\Lark\Model\DataList, unknown>|boolean
     */
    public function findAllSort($sort, $arr=false){
    	$query = Query::init()
	    	->table($this->_table)
	    	->hash($this->_hashkey)
	    	->select()
	    	->order($sort);
    	$list = $this->_mysql->exec($query);
    	if ($list){
    		return $arr ? $list : new DataList(get_called_class(), $list, $this->_hashkey);
    	}
    	 
    	return false;
    }
    
    /**
     * Find all record just with limit
     * 
     * @param int $limit
     */
    public function findAllLimited($limit, $arr=false){
    	$query = Query::init()
	    	->table($this->_table)
	    	->hash($this->_hashkey)
	    	->select()
	    	->limit($limit);
    	$list = $this->_mysql->exec($query);
    	if ($list){
    		return $arr ? $list : new DataList(get_called_class(), $list, $this->_hashkey);
    	}
    	
    	return false;
    }
    
    /**
     * Find all record with where condition and sort by
     *
     * @param array $where
     * @param array $sort
     * @param boolean $arr
     * @return Ambigous <\Lark\Model\DataList, unknown>|boolean
     */
    public function findAllWhereSort($where, $sort, $arr=false){
    	$query = Query::init()
	    	->table($this->_table)
	    	->hash($this->_hashkey)
	    	->select()
	    	->where($where)
	    	->order($sort);
    	$list = $this->_mysql->exec($query);
    	if ($list){
    		return $arr ? $list : new DataList(get_called_class(), $list, $this->_hashkey);
    	}
    	
    	return false;
    }
    
    /**
     * Find all record just with where condition limited
     *
     * @param array $where
     * @param int $limit
     * @param boolean $arr
     * @return Ambigous <\Lark\Model\DataList, unknown>|boolean
     */
    public function findAllWhereLimited($where, $limit, $arr=false){
    	$query = Query::init()
	    	->table($this->_table)
	    	->hash($this->_hashkey)
	    	->select()
	    	->where($where)
	    	->limit($limit);
    	$list = $this->_mysql->exec($query);
    	if ($list){
    		return $arr ? $list : new DataList(get_called_class(), $list, $this->_hashkey);
    	}
    	 
    	return false;
    }
    
    /**
     * Find all record just with sort by
     *
     * @param array $sort
     * @param int $limit
     * @param boolean $arr
     * @return Ambigous <\Lark\Model\DataList, unknown>|boolean
     */
    public function findAllSortLimited($sort, $limit, $arr=false){
    	$query = Query::init()
	    	->table($this->_table)
	    	->hash($this->_hashkey)
	    	->select()
	    	->order($sort)
	    	->limit($limit);
    	$list = $this->_mysql->exec($query);
    	if ($list){
    		return $arr ? $list : new DataList(get_called_class(), $list, $this->_hashkey);
    	}
    
    	return false;
    }
    
    /**
     * Find all record with where condition and sort by
     *
     * @param array $where
     * @param array $sort
     * @param int $limit
     * @param boolean $arr
     * @return Ambigous <\Lark\Model\DataList, unknown>|boolean
     */
    public function findAllWhereSortLimited($where, $sort, $limit, $arr=false){
    	$query = Query::init()
	    	->table($this->_table)
	    	->hash($this->_hashkey)
	    	->select()
	    	->where($where)
	    	->order($sort)
	    	->limit($limit);
    	$list = $this->_mysql->exec($query);
    	if ($list){
    		return $arr ? $list : new DataList(get_called_class(), $list, $this->_hashkey);
    	}
    	 
    	return false;
    }
    
    /**
     * Find just one data record by filter
     */
    public function findOneFullaware($params=array(), $arr=false){
    	$params['limit'] = 1;
    	
    	$data = $this->findAllFullaware($params, $arr);
    	if ($data){
    		$this->loadData(current($data));
    		return $this;
    	}
    	 
    	return false;
    }
    
    /**
     * Find one record just with where condition
     * 
     * @param array $where
     * @return \Lark\Model|boolean
     */
    public function findOneWhere($where){
    	$query = Query::init()
    		->table($this->_table)
    		->hash($this->_hashkey)
    		->select()
    		->where($where)
    		->limit(1);
    	$data = $this->_mysql->exec($query);
    	if ($data){
    		$this->loadData(current($data));
    		return $this;
    	}
    	 
    	return false;
    }
    
    /**
     * Find one record just with sort by
     *
     * @param array $sort
     * @return \Lark\Model|boolean
     */
    public function findOneSort($sort){
    	$query = Query::init()
	    	->table($this->_table)
	    	->hash($this->_hashkey)
	    	->select()
	    	->order($sort)
	    	->limit(1);
    	$data = $this->_mysql->exec($query);
    	if ($data){
    		$this->loadData(current($data));
    		return $this;
    	}
    }
    
    /**
     * Find one record with where condition and sort by
     *
     * @param array $where
     * @param array $sort
     * @return \Lark\Model|boolean
     */
    public function findOneWhereSort($where, $sort){
    	$query = Query::init()
    	->table($this->_table)
    	->hash($this->_hashkey)
    	->select()
    	->where($where)
    	->order($sort)
    	->limit(1);
    	$data = $this->_mysql->exec($query);
    	if ($data){
    		$this->loadData(current($data));
    		return $this;
    	}
    }
    
    /**
     * Find data by page
     */
    public function findDataByPage($pageHolder){
    	$filter = array();
    	$sort = array();
    	$offset = 0;
    	$limit = 10;
    	
    	$query = Query::init()
    		->table($this->_table)
    		->select()
    		->order($sort)
    		->offset($offset)
    		->limit($limit);
    	
    }

    private function _validate($table, $field, $rule, $value=null){
    	$valid = true;
    	$error = null;
    	do{
    		//special check of primary key
    		if ($field == 'id'){
    			if (isset($this->id) && preg_match('/^[0-9]+\d*$/', $this->id)!=1){
    				$valid = false;
    				$error = new Error(Error::LEVEL_MODEL, Error::ERR_NOT_INT, "{$table}.{$field}");
    			}
    			break;
    		}
    		 
    		if ($value==null && isset($this->_data[$field])){
    			$value = $this->_data[$field];
    		}
    		
    		//assign the default value, we assume that the default value is always valid
    		if (empty($value) && isset($rule['default'])){
    			$this->_data[$field] = $rule['default'];
    			break;
    		}
    		 
    		//require check
    		$required = isset($rule['required']) ? $rule['required'] : true;
    		if ($required && empty($value)){
    			$valid = false;
    			$error = new Error(Error::LEVEL_MODEL, Error::ERR_EMPTY, "{$table}.{$field}");
    			break;
    		}
    	
    		//filed not required and don't have default value, and have no value no need to valid
    		if (!$required && empty($value)){
    			break;
    		}
    		
    		//data type check
    		$type = isset($rule['type']) ? $rule['type'] : Model::TYPE_STRING;
    		switch ($type){
    			case Model::TYPE_INT:
    				if (preg_match('/^(-)?\d+$/', $value)!=1){
    					$valid = false;
    					$error = new Error(Error::LEVEL_MODEL, Error::ERR_NOT_INT, "{$table}.{$field}");
    				}
    				break;
    			case Model::TYPE_NUMBER:
    				if (!is_numeric($value)){
    					$valid = false;
    					$error = new Error(Error::LEVEL_MODEL, Error::ERR_NOT_NUM, "{$table}.{$field}");
    				}
    				break;
    			case Model::TYPE_BOOL:
    				if (!is_bool($value)){
    					$valid = false;
    					$error = new Error(Error::LEVEL_MODEL, Error::ERR_NOT_BOOL, "{$table}.{$field}");
    				}
    				break;
    			case Model::TYPE_DATETIME:
    				if (!preg_match('/\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?/', $value)){
    					$valid = false;
    					$error = new Error(Error::LEVEL_MODEL, Error::ERR_NOT_DATE, "{$table}.{$field}");
    				}
    				break;
    			case Model::TYPE_SERIALIZED:
    			case Model::TYPE_JSON:
		    		if ($value === false || !is_array($value)){
		    			$valid = false;
		    			$error = new Error(Error::LEVEL_MODEL, Error::ERR_FORMAT, "{$table}.{$field}");
		    		}
    				break;
    		}
    		if (!$valid) break;
    		 
    		//additional rules check
    		if (isset($rule['minval']) && $value > $rule['minval']){
    			$valid = false;
    			$error = new Error(Error::LEVEL_MODEL, Error::ERR_TOO_SMALL, "{$table}.{$field}", array($rule['minval']));
    			break;
    		}
    		if (isset($rule['maxval']) && $value < $rule['maxval']){
    			$valid = false;
    			$error = new Error(Error::LEVEL_MODEL, Error::ERR_TOO_BIG, "{$table}.{$field}", array($rule['maxval']));
    			break;
    		}
    		if (isset($rule['maxlen']) || isset($rule['minlen'])){
    			$len = mb_strlen($value, 'UTF-8');
    			if (isset($rule['minlen']) && $len < $rule['minlen']){
    				$valid = false;
    				$error = new Error(Error::LEVEL_MODEL, Error::ERR_TOO_SHORT, "{$table}.{$field}", array($rule['minlen']));
    				break;
    			}
    			if (isset($rule['maxlen']) && $len > $rule['maxlen']){
    				$valid = false;
    				$error = new Error(Error::LEVEL_MODEL, Error::ERR_TOO_LONG, "{$table}.{$field}", array($rule['maxlen']));
    				break;
    			}
    		}
    		if (isset($rule['filter']) && filter_var($value, $rule['filter'])===false){
    			$valid = false;
    			$error = new Error(Error::LEVEL_MODEL, Error::ERR_FORMAT, "{$table}.{$field}");
    			break;
    		}
    		if (isset($rule['regex']) && !preg_match($rule['regex'], $value)){
    			$valid = false;
    			$error = new Error(Error::LEVEL_MODEL, Error::ERR_FORMAT, "{$table}.{$field}");
    			break;
    		}
    		if (isset($rule['enum']) && !in_array($value, $rule['enum'])){
    			$valid = false;
    			$error = new Error(Error::LEVEL_MODEL, Error::ERR_NOT_ACCEPT, "{$table}.{$field}");
    			break;
    		}
    	}while (false);
    	
    	return $valid ? true : $error;
    }
    
	/**
	 * Validate the model data
	 * 
	 * @return boolean
	 */
    public function validate(){
    	$valid = true;
    	$table = strtolower($this->getTable());
    	foreach ($this->_fields as $field=>$rule){
    		$_valid = $this->_validate($table, $field, $rule);
    		if ($_valid !== true){
    			$this->_errors[] = $_valid;
    			$_valid = false;
    		}
    		
    		$valid = $valid && $_valid;
    	}
    	
    	return $valid;
    }
	
    /**
     * Valid model field respectively
     * @param string $field
     * @param mixed $value
     * @return Ambigous <boolean, \Lark\Error>
     */
    public function validateField($field, $value){
    	$table = strtolower($this->getTable());
    	
    	if (!isset($this->_fields[$field])){
    		return new Error(Error::LEVEL_MODEL, Error::ERR_NOT_ACCEPT, "{$table}.{$field}");
    	}
    	
    	$rule = $this->_fields[$field];
    	
    	return $this->_validate($table, $field, $rule, $value);
    }
    
    /**
     * Save current model data to database
     * 
     * @param boolean $idaf if need to return insertId or affectedRows, default true
     * @return boolean
     */
    public function save($idaf=true){
    	$this->beforeSave();
    	
    	$data = $this->_data;
    	if ($this->id > 0){
    		if (count($this->_changes)){
    			$data = $this->_changes;
    		}
    		
    		$res = $this->update(array('id'=>$this->id), $data, $idaf);
    	}else{
    		$res = $this->insert($data, $idaf);
    	}
    	
    	if ($res){
    		$this->saveCache();
    	}
    	
    	$this->afterSave();
    	
    	return $res;
    }
    
    /**
     * Insert data to database
     * 
     * @param array $record
     * @param boolean $id if need to return insertId, default true
     * @return int|boolean
     */
	public function insert($record, $id=true){
		$query = Query::init()
			->option(Query::OPT_INSERTID, $id)
			->table($this->_table)
			->hash($this->_hashkey)
			->insert($record);
		$res = $this->_mysql->exec($query);
		if (is_int($res)){
			$this->id = $res;
			$this->_loaded = true;
			return $res;
		}
		
		return false;
	}
	
	/**
	 * Update data to database
	 * 
	 * @param array $where
	 * @param array $record
	 * @param boolean $af if need to return affectedRows, default true
	 * @return boolean
	 */
	public function update($where, $record, $af=true){
		$query = Query::init()
			->option(Query::OPT_AFFECTEDROWS, $af)
			->table($this->_table)
			->hash($this->_hashkey)
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
	public function delete($where=array(), $strict=false){
		if (empty($where)){
			$where = array('id'=>$this->id);
		}
		$query = Query::init()
			->option(Query::OPT_AFFECTEDROWS, $strict)
			->table($this->_table)
			->hash($this->_hashkey)
			->where($where)
			->delete();
		$res = $this->_mysql->exec($query);
		if ($res){
			$this->deleteCache();
			$this->id = 0;
			$this->_data = array();
		}
		
		return $res;
	}
    
	/**
	 * Get validation errors
	 */
    public function errors(){
    	return $this->_errors;
    }
}