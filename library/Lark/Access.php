<?php
namespace Lark;

use Lark\Request;
use Lark\Visitor;

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
	 * Access check result
	 */
	const AC_DENY = 'DENY';
	const AC_GRANT = 'GRANT';
	
	/**
	 * Current request visitor
	 * @var Visitor
	 */
	private $visitor;
	
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
	 * @param Visitor $visitor
	 * @param Request $request
	 */
	public function __construct(Array $accessRules = array(), Visitor $visitor = null, Request $request){
		$this->rules   = $accessRules;
		$this->visitor = $visitor;
		$this->request = $request;
	}
	
	/**
	 * Access filter method
	 * 
	 * @return boolean return true if grant, false if denied
	 */
	public function filter(){
		$result = self::AC_DENY;
		
		foreach ($this->rules as $rule){
			if (!empty($rule['verbs']) && is_array($rule['verbs'])){
				if (!in_array($this->request->method, $rule['verbs'])){
					continue;
				}
			}
			
			if (!empty($rule['roles']) && is_int($rule['roles'])){
				if ($this->visitor->role != $rule['roles']){
					continue;
				}
			}
			
			if (!empty($rule['labels']) && is_array($rule['labels'])){
				$ownlabels = array_keys($this->visitor->labels);
				$intersect = array_intersect($ownlabels, $rule['labels']);
				if (!is_array($intersect) || count($intersect)==0){
					continue;
				}
			}
			
			if (!empty($rule['actions']) && is_array($rule['actions'])){
				if (!in_array($this->request->action, $rule['actions'])){
					continue;
				}
			}
			
			if (!empty($rule['users']) && is_array($rule['users'])){
				if (!in_array($this->visitor->id, $rule['users'])){
					continue;
				}
			}
			
			if (!empty($rule['ips']) && is_array($rule['ips'])){
				if (!in_array($this->request->getIP(), $rule['ips'])){
					continue;
				}
			}
			
			$result = $rule[0];
			break;
		}
		
		return $result == self::AC_GRANT ? true : false;
	}
}