<?php
namespace Alcedoo;

use Alcedoo\Request as Request;
use Alcedoo\Response as Response;
use Alcedoo\Access;
use Alcedoo\Exception\AccessDeniedException;

abstract class Controller{
	
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
	 * Can user access this time
	 * @var boolean
	 */
	protected $access = false;
	
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
	 * User access filter, default event when beforeAction
	 */
	protected function filterAccess(){
		$access = new Access($this->accessRules(), $this->userEntry(), $this->req);
		$this->access = $access->filter();
	}
	
	/**
	 * Defualt action when access denied
	 */
	protected function accessDenied(){
		throw new AccessDeniedException("Access denied when try to execute {$this->req->controller}::{$this->req->action}");
	}
	
	/**
	 * Things need to do(Or Events need to perform) before invoke controller action
	 */
	public function beforeAction(){
		$this->filterAccess();
	}
	
	/**
	 * Execute the action router found
	 * 
	 * @param string $action
	 */
	public function executeAction($action){
		if ($this->access){
			$this->$action($this->req, $this->res);
		}else{
			$this->accessDenied();
		}
	}
	
	/**
	 * Things need to do(Or Events need to perform) after invoke controller action
	 */
	public function afterAction(){
		
	}
}