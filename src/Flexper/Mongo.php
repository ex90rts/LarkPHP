<?php
namespace Flexper;

use Flexper\Exception\MissingConfigException;
use Flexper\Exception\CloneNotAllowedException;
use Flexper\Exception\MongoException;

class Mongo{

    private static $_instance;

    private $_mongo;

    private $_db;

    private $_collections;

 	private $_cursors;

    private function __construct(){
        $config = Env::getInstance('Flexper\Config');
        if (!isset($config->mongo)){
        	throw new MissingConfigException('missing mongo config');
        }

        $this->_mongo = new \Mongo($config->mongo['server']);

        if (isset($config->mongo['db'])){
            $this->_db = $this->_mongo->selectDB($config->mongo['db']);
        }
    }

    public static function getInstance(){
        if (!self::$_instance){
        	$class = __CLASS__;
        	self::$_instance = new $class();
        }

        return self::$_instance;
    }

    public function __clone(){
        throw new CloneNotAllowedException(sprintf('class name %s', __CLASS__));
    }

    private function getCollection($collection){
        if (!isset($this->_collections[$collection])){
        	$this->_collections[$collection] = $this->_mongo->selectCollection($this->_db, $collection);
        }

        return $this->_collections[$collection];
    }

    private function getCursor($collection, array $query, array $fields=array()){
        $cursorKey = md5($collection.serialize($query).serialize($fields));
        if ($this->_cursors[$cursorKey]){
            $cursor = $this->_cursors[$cursorKey];
        }else{
            $cursor = $this->getCollection($collection)->find($query, $fields);
        }

        return $cursor;
    }

    public static function MongoId(){
        return new \MongoId();
    }

    public function count($collection, array $query, $limit=0, $skip=0){
        return $this->getCollection($collection)->count($query, $limit, $skip);
    }

    public function find($collection, array $query=array(), array $fields=array(), array $sort=array(), $limit=0, $skip=0){
        $result = array();
        $cursor = $this->getCursor($collection, $query);
        if ($cursor instanceof \MongoCursor){
            if ($skip>0){
                $cursor->skip($skip);
            }
            if ($limit>0){
                $cursor->limit($limit);
            }
            if (!empty($sort)){
                $cursor->sort($sort);
            }

            $result['count'] = $cursor->count();

            $records = array();
            foreach ($cursor as $row){
                $records[] = $row;
            }

            $result['records'] = $records;

            return $result;
        }else{
            throw new MongoException('perform Mongo find failed');
        }
    }

    public function findOne($collection, array $query=array(), array $fields=array()){
        return $this->getCollection($collection)->findOne($query, $fields);
    }

    public function insert($collection, array $record, array $options=array()){
        return $this->getCollection($collection)->insert($record, $options);
    }

    public function remove($collection, array $query=array(), array $options=array()){
        return $this->getCollection($collection)->remove($query, $options);
    }

    public function save($collection, array $record, array $options=array()){
        return $this->getCollection($collection)->save($record, $options);
    }

    public function update($collection, array $query, array $record, array $options=array()){
        return $this->getCollection($collection)->update($query, $record, $options);
    }
}