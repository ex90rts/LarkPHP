<?php
namespace Alcedoo;

use Alcedoo\Request;
use Alcedoo\UserEntry;

/**
 * User access filter, every defined rules will checked equally, any rule hitted will be a yes
 * All result will be combined at last to decide the final result
 * 
 * The access rules sample
 *  array(
		array(Access::AC_GRANT,
			'actions' => array('View', 'Test'),
			'groups' => array('Operator', 'Designer'),
		),
		array(Access::AC_GRANT,
			'verbs' => array('POST'),
			'groups' => array('Manager'),
		),
		array(Access::AC_GRANT,
			'verbs' => array('POST'),
			'groups' => array('Manager'),
		),
		array(Access::AC_GRANT,
			'roles' => array('ADMIN'),
			'groups' => array('Manager'),
		),
		array(Access::AC_DENY,
			'users' => array('*'),
		),
	);
 * @author samoay
 *
 */
class Access{
	
	/**
	 * Current request user entry
	 * @var UserEntry
	 */
	private $user;
	
	/**
	 * Current controller's access rules
	 * @var Array
	 */
	private $rules;
	
	/**
	 * Current request
	 * @var Request
	 */
	private $request;
	
	/**
	 * Contruction method
	 * 
	 * @param array $accessRules
	 * @param UserEntry $user
	 * @param Request $request
	 */
	public function __construct(Array $accessRules = array(), UserEntry $user = null, Request $request){
		$this->rules = $accessRules;
		$this->user = $user;
		$this->request = $request;
	}
	
	/**
	 * Access filter method
	 * 
	 * @return boolean return true if grant, false if denied
	 */
	public function filter(){
		var_dump($this->user);
		$result = AC_DENY;
		foreach ($this->rules as $rule){
			$type = $rule[0];
			$state = true;
			if (!empty($rule['verbs']) && is_array($rule['verbs'])){
				$state &= in_array($this->request->method, $rule['verbs'], true);
			}
			if ( !empty($rule['cruds']) && is_array($rule['cruds'])){
				$state &= in_array($this->request->crud, $rule['cruds'], true);
			}
			if (!empty($rule['roles']) && is_array($rule['roles'])){
				$state &= in_array($this->user->role, $rule['roles'], true);
			}
			if (!empty($rule['groups']) && is_array($rule['groups'])){
				$state &= (count(array_merge($rule['groups'], $this->user->groups)) < (count($rule['groups'])+count($this->user->groups)));
			}
			if (!empty($rule['actions']) && is_array($rule['actions'])){
				$state &= in_array($this->request->action, $rule['actions'], true);
			}
			if (!empty($rule['users']) && is_array($rule['users'])){
				$state &= in_array($this->user->uniqid, $rule['users'], true);
			}
			if (!empty($rule['ips']) && is_array($rule['ips'])){
				$state &= in_array($this->request->ip, $rule['ips'], true);
			}
			
			if ($type == AC_GRANT){
				$result = $state ? AC_GRANT : AC_DENY;
			}else{
				$result = $state ? AC_DENY : AC_GRANT;
			}
		}
		
		return $result == AC_GRANT ? true : false;
	}
}