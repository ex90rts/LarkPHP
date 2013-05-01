<?php
namespace Flexper;

use Flexper\Mongo;

abstract class Model{
    
    /**
     * Var for Flexper\Mongo instance
     * @var Flexper\Mongo
     */
    protected $dataEngine;
    
    public function __construct(){
        $engineName = Env::getOption('dataEngine');
        $this->dataEngine = Env::getInstance($engineName);
    }
    
    public function getDataEngine(){
        return $this->dataEngine;
    }
    
}