<?php
namespace Flexper;

use Flexper\Request;
use Flexper\Response;

abstract class Action{
    /**
     * Var for Flexper\Request instance
     * @var Flexper\Request
     */
    protected $request;
    
    /**
     * Var for Flexper\Response instance
     * @var Flexper\Response
     */
    protected $response;
    
    /**
     * Var for Flexper|Logger instance
     * @var Flexper\Logger
     */
    protected $logger;
    
    /**
     * Var for cache Model instances in php script
     * @var array
     */
    protected $models;
    
    /**
     * Construct function
     * @param Flexper\Request $request
     * @param Flexper\Response $response
     */
    public function __construct(Request $request, Response $response){
        $this->request = $request;
        $this->response = $response;
        $this->logger = null;
        $this->models = array();
    }
    
    /**
     * Do global initialize things for Action
     */
    public function initialize(){
        if ($this->permission()){
            Env::startSession();
        }
    }
    
    /**
     * Convinient method to get database model
     * @param String $modelName
     */
    final function model($modelName){
        $modelName = Env::getOption('namespace').'\Model\\'.$modelName;
        if (!isset($this->models[$modelName])){
            $this->models[$modelName] = new $modelName();
        }
        
        return $this->models[$modelName];
    }
    
    /**
     * Convinient method to get Logger instance
     * @return instance of Flexper\Logger
     */
    final function logger(){
        if (empty($this->logger)){
        	$this->logger = Env::getInstance('Flexper\Logger');
    	}
    	return $this->logger;
    }
    
    /**
     * Validation current action input
     */
    function validate(){}
    
    /**
     * Return a boolean value about is this action need to check permission
     * Child Class can override this method
     */
    function permission(){
        return false;
    }
    
    /**
     * Return a neat permission code for each action
     */
	function getPermissionCode(){
        $parts = explode('\\', get_class($this));
        $parts = array_slice($parts, 2);
        return implode('_', $parts);
    }
    
    /**
     * Check access permissions for current action
     */
    function checkPermissions(){
        $isCheck = $this->permission();
        if ($isCheck){
        	$code = $this->getPermissionCode();
        	if ($code){
        	    $hasPermissions = $this->request->getSession('permissions');
        	    if (!is_array($hasPermissions)){
        	        return false;
        	    }
        	    if (!in_array($code, $hasPermissions)){
        	        return false;
        	    }
        	}
        }
        return true;
    }
            
    /**
     * Execute current action
     */
    abstract function execute();
    
    /**
     * Redirect to other action when needed
     */
    function redirect(){}

    /**
     * Finish current action
     */
    function finish(){}
}