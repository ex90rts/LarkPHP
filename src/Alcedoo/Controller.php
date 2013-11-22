<?php
namespace Alcedoo;

use Alcedoo\Request as Request;
use Alcedoo\Response as Response;

abstract class Controller{
	
	const USER_GROUP_GUEST = 0;
	const USER_GROUP_GRANT = 1;
	
	/**
	 * Var for Alcedoo\Request instance
	 * @var Alcedoo\Request
	 */
	protected $request;
	
	/**
	 * Var for Alcedoo\Response instance
	 * @var Alcedoo\Response
	 */
	protected $response;
	
	/**
	 * If current request is ajax
	 * @var unknown
	 */
	protected $isAjax = false;
	
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
	 * Rules need to validate
	 * @var Array
	 */
	protected $rules = array();
	
	/**
	 * Rules need to check permission
	 * @var Array
	 */
	//guest, login, groups
	protected $permissions = array(
		
	);
	
	/**
	 * Construct function
	 * 
	 * @param Request $request
	 * @param Response $response
	 */
	public function __construct(Request $request, Response $response){
		$this->request = $request;
		$this->response = $response;
		$this->logger = null;
		$this->models = array();
	}
	
	public function beforeAction(){
		
	}
	
	public function afterAction(){
		
	}
}