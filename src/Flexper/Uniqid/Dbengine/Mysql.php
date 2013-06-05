<?php
namespace Flexper\Uniqid\Dbengine;

use Flexper\Mysql\Query;
use Flexper\Env;
use Flexper\Util;
use Flexper\Uniqid\DbengineInterface;

class Mysql implements DbengineInterface{
    
    private $_mysql;
    
    private $_table;
    
	public function __construct($table){
	    $this->_mysql = Env::getInstance('\Flexper\Mysql');
	    $this->_table = $table;
	}
    
	public function insert ($type, $id){
        $record = array(
        	'type'=> $type,
        	'id'  => $id,
        	'time'=> Util::getNow(),
        );
        $query = new Query();
        $query->table($this->_table)->insert($record);
        return $this->_mysql->exec($query);
    }
    
    public function hasId ($id){
        $query = new Query();
        $query->table($this->_table)
        	->select()
        	->where(array('id' => $id))
        	->limit(1);
        $result = $this->_mysql->exec($query);
        if ($result){
            return true;
        }
        return false;
    }

	public function getTypeCount ($type){
        $query = new Query();
        $query->table($this->_table)
        	->select(array('COUNT(*)'=>'ct'))
        	->where(array('type' => $type))
        	->limit(1);
        $result = $this->_mysql->exec($query);
        if (!$result){
            $count = 0;
        }else{
            $count = $result[0]['ct'];
        }
        return $count;
    }
}