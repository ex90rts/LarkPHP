<?php
namespace Alcedoo;

use Alcedoo\Request as Request;
use Alcedoo\Response as Response;
use Alcedoo\Access;

abstract class Controller{
	
	const USER_GROUP_GUEST = 0;
	const USER_GROUP_GRANT = 1;
	
	/**
	 * Var for Alcedoo\Request instance
	 * @var Alcedoo\Request
	 */
	protected $req;
	
	/**
	 * Var for Alcedoo\Response instance
	 * @var Alcedoo\Response
	 */
	protected $res;
	
	/**
	 * If current request is ajax
	 * @var boolean
	 */
	protected $ajax = false;
	
	/**
	 * Var for Alcedoo|Logger instance
	 * @var Alcedoo\Logger
	 */
	protected $logger;
	
	/**
	 * Var for cache Model instances in php script
	 * @var Array
	 */
	protected $models;
	
	/**
	 * Can user access this time
	 * @var boolean
	 */
	protected $access = true;
	
	/**
	 * Construct function
	 * 
	 * @param Request $request
	 * @param Response $response
	 */
	public function __construct(Request $request, Response $response){
		$this->req    = $request;
		$this->res    = $response;
		$this->ajax   = $request->ajax;
		$this->logger = Env::getInstance('Logger');
		$this->models = array();
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
	 * Return current controller's access rules
	 * @return multitype:
	 */
	protected function accessRules(){
		return array();
	}
	
	/**
	 * Return current userentry instance
	 */
	protected function userEntry(){
		return new UserEntry();
	}
	
	/**
	 * User access filter
	 */
	protected function filterAccess(){
		$access = new Access($this->accessRules(), $this->userEntry(), $this->req);
		$this->access = $access->filter();
	}
	
	/**
	 * Things need to do before invoke controller action,
	 * such as get current user and check access
	 */
	public function beforeAction(){
		$this->filterAccess();
		return $this->access;
	}
	
	/**
	 * Things need to do after invoke controller action
	 */
	public function afterAction(){
		
	}
}