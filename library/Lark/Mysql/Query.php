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
    private $_record = array();

    /**
     * Fields need to return after query
     * @var string
     */
    private $_fields = array();

    /**
     * Where condition part of query
     * @var string
     */
    private $_where = array();

    /**
     * Qrder by part of query
     * @var string
     */
    private $_order = array();

    /**
     * Group by part of query
     * @var string
     */
    private $_group = array();

    /**
     * Having part of query
     * @var string
     */
    private $_having = array();

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
                $temp[] = "`@{$tbname}` AS {$alias}";
                $this->_tables[] = $tbname;
            }
            $this->_table = implode(',', $temp);
        }else{
            $this->_table = "`@$table`";
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
        $this->_record = $record;

        return $this;
    }

    /**
     * Set action as select, set the fields need to be in return record
     * @param array $fields
     * @return \Lark\Mysql\Query
     */
    public function select(array $fields=array()){
        $this->_action = self::ACT_SELECT;
        $this->_fields = $fields;
        
        return $this;
    }

    /**
     * Set action as update, set the record to update
     * @param array $record
     * @return \Lark\Mysql\Query
     */
    public function update(array $record){
        $this->_action = self::ACT_UPDATE;
        $this->_record = $record;

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
    	if (!is_numeric($limit)){
    		$this->_limit = 1;
    	}else{
        	$this->_limit = $limit;
    	}        
        return $this;
    }

    /**
     * Set current query offset
     * @param int $offset
     * @return \Lark\Mysql\Query
     */
    public function offset($offset){
    	if (!is_numeric($offset)){
    		$this->_offset = 0;
    	}else{
    		$this->_offset = $offset;
    	}
        
        return $this;
    }

    /**
     * Set current query order
     * @param array $order
     * @return \Lark\Mysql\Query
     */
    public function order(array $order=array()){
    	$this->_order = $order;
    	
        return $this;
    }

    /**
     * Set current query conditions, multi items of one param will treat as AND parts, 
     * different params will treat as OR parts, invoke ->where() multi times will be
     * treated as AND parts again between each invoke result
     * 
     * @return \Lark\Mysql\Query
     */
    public function where(/**/){
    	$this->_where[] = func_get_args();
        
        return $this;
    }

    /**
     * Set current query group field
     * @param string $group
     * @return \Lark\Mysql\Query
     */
    public function group(array $group){
        $this->_group = $group;
        
        return $this;
    }

    /**
     * Set current having field, multi items of one param will treat as AND parts, 
     * different params will treat as OR parts, invoke ->where() multi times will be
     * treated as AND parts again between each invoke result
     * 
     * @param string $having
     * @return \Lark\Mysql\Query
     */
    public function having(/**/){
        $this->_having[] = func_get_args();
        
        return $this;
    }

    /**
     * Apply mysqli_real_escape_string to prevent injection
     * @param mysqli $link
     * @param string $str
     */
    private function escapeString($link, $str){
    	return mysqli_real_escape_string($link, $str);
    }
    
    /**
     * Prepare array fields to sql string part
     * @param array $fields
     * @return string
     */
    private function parseFields($fields){
    	$result = "*";
    	if (!empty($fields)){
    		$temp = array();
    		foreach ($fields as $key=>$value){
    			if (is_string($key)){
    				$temp[] = "{$key} AS {$value}";
    			}else{
    				$temp[] = "{$value}";
    			}
    		}
    		$result = implode(',', $temp);
    	}
    	
    	return $result;
    }
    
    /**
     * Prepare array record to escaped sql string part
     * @param mysqli $link
     * @param array $record
     * @return string
     */
    private function parseRecord($link, $record){
    	$fvs = array();
    	foreach ($record as $field=>$value){
    		if (!is_scalar($value) && !is_null($value)){
    			continue;
    		}
    		
    		if (is_numeric($value) || is_null($value)){
    			$fvs[] = "`{$field}`={$value}";
    		}else{
    			$fvs[] = "`{$field}`='". $this->escapeString($link, $value) ."'";
    		}
    	}
    	
    	return implode(',', $fvs);
    }
    
    /**
     * Prepare array conditions to escaped sql string part
     * @param mysqli $link
     * @param array $conditions
     * @return string
     */
    private function parseConditions($link, $conditions){
    	if (empty($conditions)){
    		return false;
    	}
    	
    	//AND
    	$andParts = array();
    	foreach ($conditions as $andItems){
    		//OR
    		$orParts = array();
    		foreach ($andItems as $orItems){
	    		//Fields, AND
	    		$parts = array();
	    		foreach ($orItems as $key=>$value){
		    		$op = '=';
		    		if (is_array($value)){
		    			$op = $value[0];
		    			$value = $value[1];
		    			if (isset($value[2])){
		    				$value2 = $value[2];
		    			}
		    		}
		    		if (strtoupper($op)=='IN'){
		    			if (is_string($value)){
		    				$value = explode(",", $value);
		    			}
		    			$temp = array();
		    			foreach ($value as $item){
		    				if (!is_scalar($value) && !is_null($value)){
		    					continue;
		    				}
		    				if (!is_numeric($value) && !is_null($value)){
		    					$temp[] = "'". $this->escapeString($link, $item) . "'";
		    				}else{
		    					$temp[] = $item;
		    				}
		    			}
		    			$parts[] = "`{$key}` IN(". implode(",", $temp) .")";
		    		}elseif(strtoupper($op)=='LIKE'){
		    			if (!is_string($value)){
		    				continue;
		    			}
		    			
		    			$parts[] = "`{$key}` LIKE '". $this->escapeString($link, $value) ."'";
		    		}elseif(strtoupper($op)=='BETWEEN' && isset($value2)){
		    			if (!is_scalar($value)){
		    				continue;
		    			}
		    			
		    			if (!is_numeric($value)){
		    				$value = "'". $this->escapeString($link, $value) . "'";
		    			}
		    			if (!is_numeric($value2)){
		    				$value = "'". $this->escapeString($link, $value2) . "'";
		    			}
		    			
		    			$parts[] = "`{$key}` BETWEEN {$value} AND {$value2}";
		    		}else{
		    			if (!is_scalar($value) && !is_null($value)){
		    				continue;
		    			}
		    			if (!is_numeric($value) && !is_null($value)){
		    				$value = "'". $this->escapeString($link, $value) . "'";
		    			}
		    			$parts[] = "`{$key}` {$op} {$value}";
		    		}
	    		}
	    		
	    		if (count($parts)){
	    			$orParts[] = '('. implode(') AND (', $parts) .')';
	    		}
    		}
    		
    		if (count($orParts)){
    			$andParts[] = '('. implode(') OR (', $orParts) .')';
    		}
    	}
    	
    	if (count($andParts)){
    		return implode(" AND ", $andParts);
    	}else{
    		return false;
    	}
    }
    
    /**
     * Make the sql string for mysql execute
     * @throws \Exception
     * @return string
     */
    public function makeSql($link=false){
        if (empty($this->_action)){
            throw new \Exception('please set the action at first');
        }

        $sql = '';
        switch ($this->_action){
        	case self::ACT_INSERT:
        	    $sql = "INSERT INTO {$this->_table} SET " . $this->parseRecord($link, $this->_record);
        	    break;
        	case self::ACT_SELECT:
        	    $sql = "SELECT ". $this->parseFields($this->_fields) ." FROM {$this->_table}";
        	    $where = $this->parseConditions($link, $this->_where);
        	    if ($where){
        	    	$sql .= " WHERE {$where}";
        	    }
        	    
        	    if (!empty($this->_group)){
        	        $sql .= " GROUP BY " . implode(",", $this->_group);
        	    }
        	    
        	    $having = $this->parseConditions($link, $this->_having);
        	    if ($having){
        	        $sql .= " HAVING {$having}";
        	    }
        	    
        	    if (!empty($this->_order)){
        	    	$order = array();
        	    	foreach ($this->_order as $field=>$sort){
        	    		$sort = strtoupper($sort);
        	    		if ($sort!='ASC' && $sort!='DESC'){
        	    			$sort = 'ASC';
        	    		}
        	    		$order[] = "`{$field}` {$sort}";
        	    	}
        	        $sql .= " ORDER BY " . implode(",", $order);
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
        	    $sql = "UPDATE {$this->_table} SET ". $this->parseRecord($link, $this->_record);
        	    
        	    $where = $this->parseConditions($link, $this->_where);
        	    if ($where){
        	    	$sql .= " WHERE {$where}";
        	    }
        	    
        	    if (!empty($this->_limit)){
        	        $sql .= " LIMIT {$this->_limit}";
        	    }
        	    break;
        	case self::ACT_DELETE:
        	    $sql = "DELETE FROM {$this->_table}";
        	    
        	    $where = $this->parseConditions($link, $this->_where);
        	    if ($where){
        	    	$sql .= " WHERE {$where}";
        	    }
        	    
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