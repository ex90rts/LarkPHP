<?php
namespace Lark;

use Lark\Request;
use Lark\Response;
use Lark\Access;
use Lark\Visitor;
use Lark\Exception\AccessDeniedException;
use Lark\Exception\ActionNotFoundException;

/**
 * 控制器基类
 * 
 * 控制器基类，定义了控制器基础方法和执行模板。可以直接由最终控制器继承，但是更加建
 * 议先继承编写一个中间层级的控制器，编写一些公用的处理，如前端控制器，后台访问控制器
 * 
 * @property Request $req 请求封装类
 * @property Response $res 请求返回封装类
 * @property boolean $ajax 是否为ajax请求
 * @property boolean $cmdmode 请求是否为cli命令行模式
 * @property Logger $logger 日志处理类实例
 * @property Config $config 配置读取类实例
 * @property Access $access 访客对象是否有权限访问当前控制器和动作
 * @property Visitor $visitor 访客对象封装类实例
 * @author samoay
 *
 */
abstract class Controller{
	
	/**
	 * 请求封装类
	 * 
	 * @access protected
	 * @var Request
	 */
	protected $req;
	
	/**
	 * 请求返回封装类
	 * 
	 * @access protected
	 * @var Response
	 */
	protected $res;
	
	/**
	 * 是否为ajax请求，默认为false
	 * 
	 * @access protected
	 * @var boolean
	 */
	protected $ajax = false;
	
	/**
	 * 请求是否为cli命令行模式
	 * 
	 * @access protected
	 * @var boolean
	 */
	protected $cmdmode = false;
	
	/**
	 * 日志处理类实例
	 * 
	 * @access protected
	 * @var Logger
	 */
	protected $logger;
	
	/**
	 * 配置读取类实例
	 * 
	 * @access protected
	 * @var Config
	 */
	protected $config;

	/**
	 * 访客对象是否有权限访问当前控制器和动作，默认false
	 * 
	 * @access protected
	 * @var boolean
	 */
	protected $access = false;
	
	/**
	 * 访客对象封装类实例
	 * 
	 * @access protected
	 * @var Visitor
	 */
	protected $visitor = null;
	
	/**
	 * 构造方法，传入Request和Response对象，获取Logger和Config对象实例
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
	 * 供子类覆写定义访问权限控制的方法，如果子类不覆写则返回空数组
	 * 
	 * @access protected
	 * @see Access
	 * @return array
	 */
	protected function accessRules(){
		return array();
	}
	
	/**
	 * 供子类覆写构造访客对象实例的方法，如果子类不覆写则返回空的访客对象
	 * 
	 * @access protected
	 * @see Visitor
	 * @return Visitor
	 */
	protected function visitor(){
		$this->visitor = new Visitor();
		return $this->visitor;
	}

	/**
	 * 供子类覆写的无权限访问处理方法，如果子类不覆写则抛出AccessDeniedException异常供上层捕获处理
	 * 
	 * @access protected
	 * @throws AccessDeniedException 当访客无权限访问时抛出此异常
	 */
	protected function accessDenied(){
		throw new AccessDeniedException("Access denied when try to execute {$this->req->controller}::{$this->req->action}");
	}
		
	/**
	 * 初始化Access对象并进行访问权限判断的模板方法
	 * 
	 * @access protected
	 */
	private function filterAccess(){
		$access = new Access($this->accessRules(), $this->visitor(), $this->req);
		$this->access = $access->filter();
	}
	
	/**
	 * 在控制器执行任何动作前需要做的前置处理
	 * 
	 * @access protected
	 */
	protected function beforeAction(){
		
	}
	
	/**
	 * 执行具体控制器动作的模板方法
	 * 
	 * @access protected
	 * @throws ActionNotFoundException 当当前控制器没有被请求动作时抛出
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
	 * 在控制器执行任何动作后需要做的后置处理
	 */
	protected function afterAction(){
		
	}
}