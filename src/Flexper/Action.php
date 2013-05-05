<?php
namespace Flexper;

use Flexper\Request;
use Flexper\Response;
use Flexper\Exception\ActionValidationException;

abstract class Action{
	/**
	 * Constants for Action validate rules
	 */
	const VALID_REQUIRED = 'valid_required';
    const VALID_INT = 'valid_int';
    const VALID_NUMBER = 'valid_number';
    const VALID_ARRAY = 'valid_array';
    const VALID_REGEX = 'valid_regex';

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
     * Rules need to validate
     * @var array
     */
    protected $rules = array();

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
    public function init(){
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
    function validate(){
		if (!empty($this->rules)){
			foreach ($this->rules as $rule){
				$type = array_shift($rule);
				switch ($type){
					case VALID_REQUIRED:
						foreach ($rule as $field){
							if (empty($this->request->$field)){
								throw new ActionValidationException("field $field is required, empty given");
							}
						}
						break;
					case VALID_INT:
						foreach ($rule as $field){
							if (!is_int($this->request->$field)){
								throw new ActionValidationException("field $field need to be int, {$this->request->$field} given");
							}
						}
						break;
					case VALID_NUMBER:
						foreach ($rule as $field){
							if (!is_int($this->request->$field)){
								throw new ActionValidationException("field $field need to be number, {$this->request->$field} given");
							}
						}
						break;
					case VALID_ARRAY:
						foreach ($rule as $field){
							if (!is_array($this->request->$field)){
								throw new ActionValidationException("field $field need to be array, {$this->request->$field} given");
							}
						}
						break;
					case VALID_REGEX:
						$regex = array_shift($rule);
						foreach ($rule as $field){
							if (!preg_match($regex, $this->request->$field)){
								throw new ActionValidationException("field $field is not match with regex:$regex, {$this->request->$field} given");
							}
						}
						break;
				}
			}
		}
    }

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