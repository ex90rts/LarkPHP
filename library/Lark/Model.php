<?php
namespace Lark;

use Lark\Mysql\Query;
use Lark\Model\DataList;
use Lark\Exception\ModelHashKeyException;

/**
 * 数据模型基类
 * 
 * 数据模型基类，封装MySQL数据记录到PHP对象，并提供数据验证和读取的便利方法，提供高速缓存的
 * 支持。框架中任何数据的读取都应该通过数据模型来完成，以免对数据结构和安全性造成破坏。模型基类
 * 默认以primary key为键建立了redis/memcache缓存，请尽量在业务中使用默认的构造方法传入主键
 * ID来读取数据，这样能够更加高效地使用到缓存而不是直接读取数据库。在其它基类未提供的方式但是
 * 读取非常频繁的情况下，可以在具体的模型实现中自己维护缓存数据，基类暴露了$_cache给子类调用。
 * 
 * 基类通过以下方法实现了模型的基本构造和操作:
 * - getTable 基类必须实现此方法，用于返回模型对应的MySQL数据库表名（不包括分表索引部分）
 * - getFields 基类必须实现此方法，用于返回模型对应的MySQL数据表的字段和字段格式，以及字段值的校验规则
 * - hashField 如果模型对应的MySQL数据表进行了分表处理，则必须传入此字段，以标明通过哪个字段值进行hash分表
 * - hasField 供检查模型是否包含某个字段
 * - errors 返回模型数据校验过程中产生的逻辑错误，为Error对象数组
 * - validate 结合getFields返回的校验规则对数据进行校验
 * - save 持久化数据到数据库，新数据则调用insert，老数据则调用update
 * - insert 插入新数据到数据库
 * - update 更新数据到数据库
 * - delete 删除数据库中的记录
 * 
 * 基类通过以下方法扩展了模型操作数据与数据库数据之间在格式上的透明化：
 * - beforeSave 保存之前，对于字段类型是序列化数组的，判断有没有序列化，如果没有先序列化
 * - afterSave 保存之后，将序列化过的字段反序列化，供脚本中使用
 * - afterLoad 从数据库中读取出来后，反序列化被序列化保存的字段
 * - saveTemp/readTemp 在beforeSave和afterSave过程中作为脚本缓存使用，避免不必要的序列化操作
 * 
 * 基类提供了以下方法作为数据读取的便利方法
 * - findOne*** 查找一条满足条件的记录便利方法，查找到后回默认将数据load到当前对象中
 * - findAll*** 查找多条满足条件的记录便利方法，查找到后回默认返回一个Mysql\Datalist对象供遍历使用
 * - execQuery 直接执行一个Mysql\Query，这个方法最大限度提供了编写sql的自由性，在find***方法不能满足需要时使用
 * 
 * @property Mysql $_mysql Mysql对象实例
 * @property string $_table 数据表名（不包括分表索引部分）
 * @property string $_hashkey 供分表hash的字段值
 * @property array $_fields 字段配置
 * @property array $_data 实际保存当前模型数据的数组，模型属性值（字段值）均通过__get的魔术方法从$_data中读取
 * @property array $_temp 供saveTemp/readTemp方法使用的脚本缓存数组
 * @property boolean $_loaded 数据是否载入了当前模型对象
 * @property array $_changes 数据载入模型对象后发生修改的属性键值，为save/update操作准备数据
 * @property array $_errors 数据校验产生的Error错误数组
 * @property Cache $_cache Cache对象实例
 * @property string $id 当前数据的主键值，没有load的时候为0，load之后为具体数据主键字段值
 * @author samoay
 *
 */
abstract class Model{
	
	/**
	 * @var string 字段类型-整数
	 */
	const TYPE_INT = 'INT';
	
	/**
	 * @var string 字段类型-数字
	 */
	const TYPE_NUMBER = 'NUMBER';
	
	/**
	 * @var string 字段类型-字符串
	 */
	const TYPE_STRING = 'STRING';
	
	/**
	 * @var string 字段类型-布尔型
	 */
	const TYPE_BOOL = 'BOOL';
	
	/**
	 * @var string 字段类型-日期时间型
	 */
	const TYPE_DATETIME = 'DATETIME';
	
	/**
	 * @var string 字段类型-数组序列化
	 */
	const TYPE_SERIALIZED = 'SERIALIZED';
	
	/**
	 * @var string 字段类型-JSON
	 */
	const TYPE_JSON = 'JSON';
	
	/**
	 * @var string 数据库中主键字段的字段名
	 */
	const PRIMARY_KEY = 'id';
	
	/**
	 * @var string 高速缓存的时间（供设定缓存时使用便利）-非常短 5秒
	 */
	const CACHE_SECOND  = 5;
	
	/**
	 * @var string 高速缓存的时间（供设定缓存时使用便利）-短 1小时
	 */
	const CACHE_SHORT   = 3600;
	
	/**
	 * @var string 高速缓存的时间（供设定缓存时使用便利）-一般 24小时
	 */
	const CACHE_NORMAL  = 86400;
	
	/**
	 * @var string 高速缓存的时间（供设定缓存时使用便利）-长 30天
	 */
	const CACHE_LONG    = 2592000;
	
	/**
	 * @var Mysql Mysql对象实例
	 */
	protected $_mysql;
	
	/**
	 * @var string 数据表名（不包括分表索引部分）
	 */
	protected $_table;
	
	/**
	 * @var number 供分表hash的字段值
	 */
	protected $_hashkey = false;
	
	/**
	 * @var array 字段配置
	 */
	protected $_fields;
	
	/**
	 * @var array 实际保存当前模型数据的数组，模型属性值（字段值）均通过__get的魔术方法从$_data中读取
	 */
	private $_data = array();
	
	/**
	 * @var array 供saveTemp/readTemp方法使用的脚本缓存数组
	 */
	private $_temp = array();
	
	/**
	 * @var boolean 数据是否载入了当前模型对象
	 */
	private $_loaded = false;
	
	/**
	 * @var array 数据载入模型对象后发生修改的属性键值，为save/update操作准备数据
	 */
	protected $_changes = array();
	
	/**
	 * @var array 数据校验产生的Error错误数组
	 */
	protected $_errors = array();
	
	/**
	 * @var Cache Cache对象实例
	 */
	protected $_cache = null;
	
	/**
	 * @var int 当前数据的主键值，没有load的时候为0，load之后为具体数据主键字段值
	 */
	public $id = 0;
	
	/**
	 * 构造方法，从App单例池中取出Mysql和Cache对象；传入主键值和hash字段值；从子类读取表明
	 * 和字段配置；校验hash字段值；如果传入的主键值大于0则自动从数据库载入该条目数据
	 * 
	 * @param number $pk 主键值，默认值0
	 * @param number $hashkey hash字段值，可选，但是当表分表后为必选
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
	 * 设定模型属性值（即字段值）的魔术方法
	 * 
	 * @param string $name 字段名
	 * @param mixed $value 字段值
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
	 * 获取模型属性值（即字段值）的魔术方法
	 * 
	 * @param string $name 字段名
	 */
	public function __get($name){
		if (isset($this->_data[$name])){
			return $this->_data[$name];
		}
		
		return null;
	}
	
	/**
	 * 将模型数据作为字符串输出的魔术方法，调试使用
	 * 
	 * @return string
	 */
	public function __toString(){
		return "Model:" . get_called_class() ."({$this->id})\r\n". print_r($this->_data, true);
	}
	
	/**
	 * 供子类实现，返回数据表名（不含分表索引值），比如表User被分为10个表，getTable依然
	 * 只返回User，而不是User0/User9，分表索引值将在Query时通过hashkey自动加上
	 * 
	 * @access protected
	 * @return string 数据表名
	 */
	abstract protected function getTable();
	
	/**
	 * 供子类实现，返回字段配置数组，格式如下：
	 * 
	 * array(
	 *     'field' => arrray(
	 *         'name' => '字段名描述',
	 *         'type' => '字段类型, 必选为TYPE_*常量之一，默认TYPE_STRING',
	 *         'required' => '是否必选，默认true',
	 *         'default' => '默认值',
	 *         'minval' => '最小值，必须为数字',
	 *         'maxval' => '最大值，必须为数字',
	 *         'minlen' => '最短值，必须为数字',
	 *         'maxlen' => '最长值，必须为数字',
	 *         'filter' => '用于filter_var方法的过滤器',
	 *         'enum' => '枚举值，字段值只有在枚举的一种才合法',
	 *         'regex' => '正则表达式验证',
	 *     ),
	 * )
	 * 上述field必须与数据库的字段值一一对应，name为必选，其它均可选。
	 * 
	 * @access protected
	 * @return array 字段配置数组
	 */
	abstract protected function getFields();
	
	/**
	 * 返回分表的hash字段名
	 * 
	 * @access protected
	 * @return NULL\string hash字段名
	 */
	protected function hashField(){
		return null;
	}
	
	/**
	 * 在保存数据到数据库之前的前置操作，目前主要用来处理序列化数据类型，使之在控制层使用更加透明
	 * 
	 * @access protected
	 */
	protected function beforeSave(){}
	
	/**
	 * 在保存数据到数据库之后的后置操作，目前主要用来处理序列化数据类型，使之在控制层使用更加透明
	 * 
	 * @access protected
	 */
	protected function afterSave(){}
	
	/**
	 * 在数据加载到模型对象之后的操作，目前主要用来处理序列化数据类型，使之在控制层使用更加透明
	 * 
	 * @access protected
	 */
	protected function afterLoad(){}
	
	/**
	 * 专门给计数类型字段提供的更新方法，即 filed=filed+n 这样的更新以在并发情况下提高数据准确性。
	 * 注意：这个操作只有在调用save()方法后才会将数据写入数据库
	 * 
	 * @access public
	 * @param string $field 字段名
	 * @param string $value 更新数据，如  +1, -1, +10等
	 */
	public function tamperChanges($field, $value){
		if (isset($this->_fields[$field])){
			$this->_changes[$field] = $value;
		}
	}
	
	/**
	 * 返回Model层默认主键cache的key
	 * 
	 * @access private
	 * @param int $id 主键字段值
	 * @return string/false 主键值有效返回字符串key，否则返回false
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
	 * 保存Model层默认主键cache的值
	 * 
	 * @access private
	 * @param int $id 主键字段值
	 * @return boolean cache设定结果
	 */
	private function saveCache($id=0){
		$key = $this->getCacheKey($id);
		if ($key){
			return $this->_cache->set($key, $this->getData(), self::CACHE_SHORT);
		}
		
		return false;
	}
	
	/**
	 * 读取Model层默认主键cache的值
	 * 
	 * @access private
	 * @param int $id 主键字段值
	 * @return boolean cache删除结果
	 */
	private function readCache($id=0){
		$key = $this->getCacheKey($id);
		if ($key){
			return $this->_cache->get($key);
		}
		
		return false;
	}
	
	/**
	 * 删除Model层默认主键cache的值
	 * 
	 * @param number $id 主键字段值
	 * @return boolean
	 */
	private function deleteCache($id=0){
		$key = $this->getCacheKey($id=0);
		if ($key){
			return $this->_cache->delete($key);
		}
		
		return false;
	}
	
	/**
	 * 返回当前model加载数据后被修改的属性
	 * 
	 * @access public
	 * @param string $key 字段名，可选，不传入则返回所有被修改的字段
	 * @return mixed
	 */
	public function readChanges($key=''){
		if ($key==''){
			return $this->_changes;
		}
		
		if (isset($this->_changes[$key])){
			return $this->_changes[$key];
		}
		
		return null;
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
	 * Unload all loaded data
	 */
	public function unloadData(){
		$this->id = 0;
		$this->_data = array();
		$this->_changes = array();
		$this->_loaded = false;
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
     * Execute a query and return result without change current model
     * @param Query $query
     */
    public function execQuery(Query $query){
        $query->table($this->_table)->hash($this->_hashkey);
        return $this->_mysql->exec($query);
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
    	    if ($arr){
    	        return current($data);
    	    }else{
    		    $this->loadData(current($data));
    		    return $this;
    	    }
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
    	
    	return false;
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
    	
    	return false;
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

    /**
     * Check if the filed is valid in this model
     * 
     * @param string $field
     * @param boolean
     */
    public function hasField($field){
    	return isset($this->_fields[$field]);
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
    		 
    		if ($value===null && isset($this->_data[$field])){
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
    				$error = new Error(Error::LEVEL_MODEL, Error::ERR_TOO_SHORT, "{$table}.{$field}", array($rule['minlen'], $len));
    				break;
    			}
    			if (isset($rule['maxlen']) && $len > $rule['maxlen']){
    				$valid = false;
    				$error = new Error(Error::LEVEL_MODEL, Error::ERR_TOO_LONG, "{$table}.{$field}", array($rule['maxlen'], $len));
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
    		$this->deleteCache($this->id);
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
    
    /**
     * Get executed queries, for debug
     */
    public function getQueries(){
        return $this->_mysql->getQueries();
    }
}