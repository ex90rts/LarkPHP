<?php
namespace Lark;

use Lark\Request as Request;
use Lark\Response as Response;
use Lark\Access;
use Lark\Visitor;
use Lark\Exception\AccessDeniedException;
use Lark\Exception\ActionNotFoundException;

abstract class Controller{
	
	/**
	 * Var for Lark\Request instance
	 * @var Lark\Request
	 */
	protected $req;
	
	/**
	 * Var for Lark\Response instance
	 * @var Lark\Response
	 */
	protected $res;
	
	/**
	 * If current request is ajax
	 * @var boolean
	 */
	protected $ajax = false;
	
	/**
	 * If current request is come from command mode
	 * @var boolean
	 */
	protected $cmdmode = false;
	
	/**
	 * Var for Lark\Logger instance
	 * @var Lark\Logger
	 */
	protected $logger;
	
	/**
	 * Var for Lark\Config instance
	 * @var Lark\Config
	 */
	protected $config;

	/**
	 * Can user access this time
	 * @var boolean
	 */
	protected $access = false;
	
	/**
	 * Current client user entry
	 * @var Visitor
	 */
	protected $visitor = null;
	
	/**
	 * Construct function
	 * 
	 * @param Request $request
	 * @param Response $response
	 */
	public function __construct(Request $request, Response $response){
		$this->req     = $request;
		$this->res     = $response;
		$this->ajax    = $request->ajax;
		$this->cmdmode = $request->cmdmode;
		$this->logger  = App::getInstance('Logger');
		$this->config  = App::getInstance('Config');
	}
	
	/**
	 * Return current controller's access rules
	 * @return multitype:
	 */
	protected function accessRules(){
		return array();
	}
	
	/**
	 * Return current visitor instance
	 */
	protected function visitor(){
		$this->visitor = new Visitor();
		return $this->visitor;
	}

	/**
	 * Defualt action when access denied
	 */
	protected function accessDenied(){
		throw new AccessDeniedException("Access denied when try to execute {$this->req->controller}::{$this->req->action}");
	}
		
	/**
	 * User access filter, default event when beforeAction
	 */
	private function filterAccess(){
		$access = new Access($this->accessRules(), $this->visitor(), $this->req);
		$this->access = $access->filter();
	}
	
	/**
	 * Things need to do(Or Events need to perform) before invoke controller action
	 */
	protected function beforeAction(){
		
	}
	
	/**
	 * Execute the action router found
	 * 
	 * @param string $action
	 */
	public function executeAction(){
		$action = $this->req->action;
		if ($action == __FUNCTION__ || !method_exists($this, $action)){
			throw new ActionNotFoundException("Action not found when try to execute {$this->req->controller}::{$this->req->action}");
		}
		
		$reflection = new \ReflectionMethod($this, $action);
		if (!$reflection->isPublic()) {
			throw new ActionNotFoundException("Action not found when try to execute {$this->req->controller}::{$this->req->action}");
		}
		
		$this->filterAccess();
		$this->beforeAction();
		if ($this->access){
			$this->$action($this->req, $this->res);
		}else{
			$this->accessDenied();
		}
		$this->afterAction();
	}
	
	/**
	 * Things need to do(Or Events need to perform) after invoke controller action
	 */
	protected function afterAction(){
		
	}
	
	/**
	 * The default action for all controllers under HTTP request mode
	 * Developers should overide this default method
	 */
	public function view(){
		echo "It's working!";
	}
	
	/**
	 * The default action for all controllers under cli mode
	 * Developers should overide this default method
	 */
	public function cmdview(){
		echo "It's working under cli mode!\n";
	}
}