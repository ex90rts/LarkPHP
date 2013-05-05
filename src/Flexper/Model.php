<?php
namespace Flexper;

use Flexper\Mongo;
use Flexper\Mysql;
use Flexper\Mysql\Query;

abstract class Model{

	const ENGINE_MYSQL = '\Flexper\Mysql';

	const ENGINE_MONGO = '\Flexper\Mongo';

    /**
     * Var for Flexper Data Engine instance
     * @var $dataEngine
     */
    protected $engine;

    private $_engineType;

    public function __construct(){
    	$engineType = $this->getEngine();
var_dump($engineType);
    	$this->_engineType = $engineType;

        $this->engine = Env::getInstance($engineType);
    }

    abstract function getEngine(){
        return Env::getOption('dataEngine');
    }

    abstract function getTable();

    public function insert(array $record){
    	if ($this->_engineName==self::ENGINE_MONGO){
    		return $this->engine->insert($this->getTable(), $record);
    	}elseif ($this->_engineName==self::ENGINE_MYSQL){
			$query = new Query();
			$query->table($this->getTable())
				->insert($record);
			return $this->engine->exec($query);
    	}
    }

    public function update(array $where, array $newRecord){
    	if ($this->_engineName==self::ENGINE_MONGO){
    		return $this->engine->insert($this->getTable(), $where, $newRecord);
    	}elseif ($this->_engineName==self::ENGINE_MYSQL){
    		$query = new Query();
    		$query->table($this->getTable())
    		->update($newRecord)
    		->where($where);
    		return $this->engine->exec($query);
    	}
    }

    public function delete(array $where, $limit=false){
    	if ($this->_engineName==self::ENGINE_MONGO){
    		return $this->engine->remove($this->getTable(), $where);
    	}elseif ($this->_engineName==self::ENGINE_MYSQL){
    		$query = new Query();
    		$query->table($this->getTable())
    		->delete()
    		->where($where);
    		if ($limit){
    			$query->limit($limit);
    		}
    		return $this->engine->exec($query);
    	}
    }
}