<?php
namespace Lark\Uniqid\Dbengine;

use Lark\Util;
use Lark\Env;
use Lark\Uniqid\DbengineInterface;

class Mongo implements DbengineInterface{
	
    private $_mongo;
    
    private $_collection;
    
    public function __construct($collection){
        $this->_mongo = Env::getInstance('Mongo');
        $this->_collection = $collection;
    }
    
    public function insert ($type, $id){
        $record = array(
        	'_id' => $this->_mongo->MongoId(),
        	'type'=> $type,
        	'id'  => $id,
        	'time'=> Util::getNow(),
        );
        return $this->_mongo->insert($this->_collection, $record);
    }
    
    public function hasId ($id){
        $query = array(
        	'id' => $id,
        );
        if ($this->_mongo->findOne($this->_collection, $query)){
            return true;
        }
        return false;
    }

	public function getTypeCount ($type){
        $query = array(
        	'type' => $type,
        );
        $count = $this->_mongo->count($this->_collection, $query);
        if (!is_numeric($count)){
            $count = 0;
        }
        return $count;
    }
}