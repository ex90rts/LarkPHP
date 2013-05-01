<?php
namespace Flexper\Mysql;

class Query{
    const ACT_SELECT = 'SELECT';
    
    const ACT_INSERT = 'INSERT';
    
    const ACT_UPDATE = 'UPDATE';
    
    const ACT_DELETE = 'DELETE';
    
    private $_options = array();
    
    private $_table = '';
    
    private $_tables = array();
    
    private $_hashKey = '';
    
    private $_action = '';
    
    private $_query = '';
    
    private $_record = '';
    
    private $_fields = '';
    
    private $_where = '1';
    
    private $_order = '';
    
    private $_group = '';
    
    private $_having = '';
    
    private $_limit = null;
    
    private $_offset = null;
    
    public function __construct(array $options=array()){
        $this->_options = $options;
    }
    
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
    
    public function hash($hashKey){
        $this->_hashKey = $hashKey;
        return $this;
    }
    
    public function insert(array $record){
        $this->_action = self::ACT_INSERT;
        $fvs = array();
        foreach ($record as $field=>$value){
            $fvs[] = "{$field}='{$value}'";
        }
        $this->_record = implode(',', $fvs);
        
        return $this;
    }
    
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
    
    public function update(array $record){
        $this->_action = self::ACT_UPDATE;
        $fvs = array();
        foreach ($record as $field=>$value){
            $fvs[] = "{$field}='{$value}'";
        }
        $this->_record = implode(',', $fvs);
        
        return $this;
    }
    
    public function delete(){
        $this->_action = self::ACT_DELETE;
        return $this;
    }
    
    public function limit($limit){
        $this->_limit = $limit;
        return $this;
    }
    
    public function offset($offset){
        $this->_offset = $offset;
        return $this;
    }
    
    public function order(array $order){
        $temp = array();
        foreach ($order as $field=>$sort){
            $temp[] = "{$field} {$sort}";
        }
        $this->_order = implode(',', $temp);
        
        return $this;
    }
    
    public function where(/**/){
        $former = $this->_where;
        $where = '1';
        
        $arguments = func_get_args();
        if (!empty($arguments)){
            $orParts = array();
        	foreach ($arguments as $arg){
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
        	        	$andParts[] = "{$key}{$op}'{$value}'";
        	        }
        	    }
        	    $orParts[] = implode(' AND ', $andParts);
        	}
            $where = '('. implode(') OR (', $orParts) .')';
        }
        if (!empty($former) && $former!='1' && $where!='1'){
            $where = "({$former}) AND {$where}";
        }
        $this->_where = $where;
        
        return $this;
    }
    
    public function group($group){
        $this->_group = $group;
        return $this;
    }
    
    public function having($having){
        $this->_having = $having;
        return $this;
    }
    
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
        	        $sql .= " LIMIT {$this->_limit}";
        	        if (!empty($this->_offset)){
        	            $sql .= ",{$this->_offset}";
        	        }
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
    
    public function __toString(){
        return __CLASS__ . '::Query[' . $this->makeSql() . ']';
    }
}