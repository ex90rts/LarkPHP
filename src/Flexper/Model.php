<?php
namespace Flexper;

use Flexper\Mongo;

abstract class Model{

    /**
     * Var for Flexper Data Engine instance
     * @var $dataEngine
     */
    protected $dataEngine;

    public function __construct(){
        $engineName = Env::getOption('dataEngine');
        $this->dataEngine = Env::getInstance($engineName);
    }

    public function getDataEngine(){
        return $this->dataEngine;
    }

    abstract function getTable();

    public function insert(array $record){
    	return $this->dataEngine->insert($this->getTable(), $record);
    }

    public function update(array $query, array $newRecord){
    	return $this->dataEngine->update($this->getTable(), $query, $newRecord);
    }

    public function delete(array $query){
    	return $this->dataEngine->remove($this->getTable(), $query);
    }

    public function query(array $query, $limit=0, $skip=0){
    	return $this->dataEngine->find($this->table, $query, array(), array(), $limit, $skip);
    }
}