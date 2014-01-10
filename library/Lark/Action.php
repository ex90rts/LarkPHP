<?php
namespace Lark;

use Lark\Request;
use Lark\Response;
use Lark\Exception\ActionValidationException;

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
     * Var for Lark\Request instance
     * @var Lark\Request
     */
    protected $request;

    /**
     * Var for Lark\Response instance
     * @var Lark\Response
     */
    protected $response;

    /**
     * Var for Lark|Logger instance
     * @var Lark\Logger
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
     * @param Lark\Request $request
     * @param Lark\Response $response
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
            App::startSession();
        }
    }

    /**
     * Convinient method to get database model
     * @param String $modelName
     */
    final function model($modelName){
        $modelName = App::getOption('namespace').'\Model\\'.$modelName;
        if (!isset($this->models[$modelName])){
            $this->models[$modelName] = new $modelName();
        }

        return $this->models[$modelName];
    }

    /**
     * Convinient method to get Logger instance
     * @return instance of Lark\Logger
     */
    final function logger(){
        if (empty($this->logger)){
        	$this->logger = App::getInstance('Logger');
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
					case self::VALID_REQUIRED:
						foreach ($rule as $field){
							if (empty($this->request->$field)){
								throw new ActionValidationException("field $field is required, empty given");
							}
						}
						break;
					case self::VALID_INT:
						foreach ($rule as $field){
							if (!is_int($this->request->$field)){
								throw new ActionValidationException("field $field need to be int, {$this->request->$field} given");
							}
						}
						break;
					case self::VALID_NUMBER:
						foreach ($rule as $field){
							if (!is_int($this->request->$field)){
								throw new ActionValidationException("field $field need to be number, {$this->request->$field} given");
							}
						}
						break;
					case self::VALID_ARRAY:
						foreach ($rule as $field){
							if (!is_array($this->request->$field)){
								throw new ActionValidationException("field $field need to be array, {$this->request->$field} given");
							}
						}
						break;
					case self::VALID_REGEX:
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
        	    if (!in_array($code, $hasPermissions, true)){
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