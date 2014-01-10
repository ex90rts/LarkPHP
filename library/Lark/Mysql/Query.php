<?php
namespace Lark\Mysql;

class Query{
	/**
	 * Query verb types
	 */
    const ACT_SELECT = 'SELECT';
    const ACT_INSERT = 'INSERT';
    const ACT_UPDATE = 'UPDATE';
    const ACT_DELETE = 'DELETE';
    
    /**
     * Query options defination
     */
    const OPT_INSERTID = 'insertId';
    const OPT_AFFECTEDROWS = 'affectedRows';

    /**
     * Query options
     * @var array
     */
    private $_options = array();

    /**
     * Current query tables in string
     * @var string
     */
    private $_table = '';

    /**
     * Current query tables in array
     * @var array
     */
    private $_tables = array();

    /**
     * Hash key for scatter data
     * @var string
     */
    private $_hashKey = '';

    /**
     * Current query verb
     * @var string
     */
    private $_action = '';

    /**
     * Assoc array for insert or update query
     * @var array
     */
    private $_record = '';

    /**
     * Fields need to return after query
     * @var string
     */
    private $_fields = '';

    /**
     * Where condition part of query
     * @var string
     */
    private $_where = '1';

    /**
     * Qrder by part of query
     * @var string
     */
    private $_order = '';

    /**
     * Group by part of query
     * @var string
     */
    private $_group = '';

    /**
     * Having part of query
     * @var string
     */
    private $_having = '';

    /**
     * Query result number limit
     * @var int
     */
    private $_limit = null;

    /**
     * Query result pagenation offset
     * @var int
     */
    private $_offset = null;

    /**
     * Construct function
     *
     * @param array $options
     */
    public function __construct(array $options=array()){
        $this->_options = $options;
    }
    
    /**
     * Convinient method for chained calling
     * 
     * @param array $options
     * @return Query
     */
    public static function init(array $options=array()){
    	$class = __CLASS__;
    	return new $class($options);
    }

    /**
     * Magic method for get class private variables
     * @param string $name
     * @return string|multitype:|NULL
     */
    public function __get($name){
        if ($name=='action'){
            return $this->_action;
        }elseif($name=='tables'){
            return $this->_tables;
        }elseif($name=='hashKey'){
            return $this->_hashKey;
        }else{
            if (isset($this->_options[$name])){
                return $this->_options[$name];
            }
        }
        return null;
    }
    
    /**
     * Set query options
     * @param string $name
     * @param mixed $value
     */
    public function option($name, $value){
    	$this->_options[$name] = $value;
    	return $this;
    }

    /**
     * Set current query table
     *
     * @param unknown_type $table
     * @return \Lark\Mysql\Query
     */
    public function table($table){
        if (is_array($table)){
            $temp = array();
            foreach ($table as $tbname=>$alias){
                $temp[] = "@{$tbname} AS {$alias}";
                $this->_tables[] = $tbname;
            }
            $this->_table = implode(',', $temp);
        }else{
            $this->_table = "@$table";
            $this->_tables[] = $table;
        }

        return $this;
    }

    /**
     * Set current record hash key
     *
     * @param int/string $hashKey
     * @return \Lark\Mysql\Query
     */
    public function hash($hashKey=false){
    	if ($hashKey===false){
    		return $this;
    	}
    	
    	if (is_numeric($hashKey)){
        	$this->_hashKey = $hashKey;
    	}else{
    		$this->_hashKey = crc32($hashKey);
    	}
        return $this;
    }

    /**
     * Set action as Insert, set the insert record data
     *
     * @param array $record
     * @return \Lark\Mysql\Query
     */
    public function insert(array $record){
        $this->_action = self::ACT_INSERT;
        $fvs = array();
        foreach ($record as $field=>$value){
            $fvs[] = "{$field}='{$value}'";
        }
        $this->_record = implode(',', $fvs);

        return $this;
    }

    /**
     * Set action as select, set the fields need to be in return record
     * @param array $fields
     * @return \Lark\Mysql\Query
     */
    public function select(array $fields=array()){
        $this->_action = self::ACT_SELECT;
        if (empty($fields)){
            $this->_fields = '*';
        }else{
            $temp = array();
            foreach ($fields as $key=>$value){
                if (is_string($key)){
                    $temp[] = "{$key} AS {$value}";
                }else{
                    $temp[] = $value;
                }
            }
            $this->_fields = implode(',', $temp);
        }
        return $this;
    }

    /**
     * Set action as update, set the record to update
     * @param array $record
     * @return \Lark\Mysql\Query
     */
    public function update(array $record){
        $this->_action = self::ACT_UPDATE;
        $fvs = array();
        foreach ($record as $field=>$value){
            $fvs[] = "{$field}='{$value}'";
        }
        $this->_record = implode(',', $fvs);

        return $this;
    }

    /**
     * Set action as delete
     * @return \Lark\Mysql\Query
     */
    public function delete(){
        $this->_action = self::ACT_DELETE;
        return $this;
    }

    /**
     * Set current query limti
     * @param int $limit
     * @return \Lark\Mysql\Query
     */
    public function limit($limit){
        $this->_limit = $limit;
        return $this;
    }

    /**
     * Set current query offset
     * @param int $offset
     * @return \Lark\Mysql\Query
     */
    public function offset($offset){
        $this->_offset = $offset;
        return $this;
    }

    /**
     * Set current query order
     * @param array $order
     * @return \Lark\Mysql\Query
     */
    public function order(array $order=array()){
    	if (!empty($order)){
	        $temp = array();
	        foreach ($order as $field=>$sort){
	            $temp[] = "{$field} {$sort}";
	        }
	        $this->_order = implode(',', $temp);
    	}
    	
        return $this;
    }

    /**
     * Set current query conditions
     * @return \Lark\Mysql\Query
     */
    public function where(/**/){
        $former = $this->_where;
        $where = '1';

        $arguments = func_get_args();
        if (!empty($arguments)){
            $orParts = array();
        	foreach ($arguments as $arg){
        		if (empty($arg)){
        			continue;
        		}
        		
        	    $andParts = array();
        	    foreach ($arg as $key=>$value){
        	        $op = '=';
        	        if (is_array($value)){
        	            $op = $value[0];
        	            $value = $value[1];
        	        }
        	        if (strtolower($op)=='in'){
        	            $andParts[] = "{$key} IN({$value})";
        	        }else{
        	        	$andParts[] = "{$key} {$op} '{$value}'";
        	        }
        	    }
        	    $orParts[] = implode(' AND ', $andParts);
        	}
        	if (!empty($orParts)){
            	$where = '('. implode(') OR (', $orParts) .')';
        	}
        }
        if (!empty($former) && $former!='1' && $where!='1'){
            $where = "({$former}) AND {$where}";
        }
        $this->_where = $where;

        return $this;
    }

    /**
     * Set current query group field
     * @param string $group
     * @return \Lark\Mysql\Query
     */
    public function group($group){
        $this->_group = $group;
        return $this;
    }

    /**
     * Set current having field
     * @param string $having
     * @return \Lark\Mysql\Query
     */
    public function having($having){
        $this->_having = $having;
        return $this;
    }

    /**
     * Make the sql string for mysql execute
     * @throws \Exception
     * @return string
     */
    public function makeSql(){
        if (empty($this->_action)){
            throw new \Exception('please set the action at first');
        }

        $sql = '';
        switch ($this->_action){
        	case self::ACT_INSERT:
        	    $sql = "INSERT INTO {$this->_table} SET {$this->_record}";
        	    break;
        	case self::ACT_SELECT:
        	    $sql = "SELECT {$this->_fields} FROM {$this->_table} WHERE {$this->_where}";
        	    if (!empty($this->_group)){
        	        $sql .= " GROUP BY {$this->_group}";
        	    }
        	    if (!empty($this->_having)){
        	        $sql .= " HAVING {$this->_having}";
        	    }
        	    if (!empty($this->_order)){
        	        $sql .= " ORDER BY {$this->_order}";
        	    }
        	    if (!empty($this->_limit)){
        	    	$sql .= " LIMIT";
        	        if (!empty($this->_offset)){
        	            $sql .= " {$this->_offset},";
        	        }
        	        $sql .= " {$this->_limit}";
        	    }
        	    break;
        	case self::ACT_UPDATE:
        	    $sql = "UPDATE {$this->_table} SET {$this->_record} WHERE {$this->_where}";
        	    if (!empty($this->_limit)){
        	        $sql .= " LIMIT {$this->_limit}";
        	    }
        	    break;
        	case self::ACT_DELETE:
        	    $sql = "DELETE FROM {$this->_table} WHERE {$this->_where}";
        	    if (!empty($this->_limit)){
        	        $sql .= " LIMIT {$this->_limit}";
        	    }
        	    break;
        	default:
        	    throw new \Exception('mysql query action not support');
        }

        return $sql;
    }

    /**
     * Magic method for output the string format sql of current query
     * @return string
     */
    public function __toString(){
        return __CLASS__ . '::Query[' . $this->makeSql() . ']';
    }
}