<?php
namespace Lark;

class Visitor{
	/**
	 * User basic roles
	 */
	const ROLE_GUEST = 0;
	const ROLE_TOKEN = 1;
	const ROLE_LOGIN = 2;
	
	/**
	 * Declare following property private to prevent value change except init new instacne
	 */
	private $id;
	
	/**
	 * Main access controll property, will return Error::ERR_ACCESS_DENY Error if not match
	 * @var Visitor::ROLE_*
	 */
	private $role;
	
	/**
	 * Sub access controll property, will return Error::ERR_FEATURE_DENY Error if not match
	 * @var array
	 */
	private $labels;
	
	/**
	 * User language locale
	 * @var string
	 */
	private $locale;
	
	/**
	 * Attached extra user data
	 * @var array
	 */
	private $data;
	
	public function __construct($id=0, $role=Visitor::ROLE_GUEST, $labels=array(), $locale='', array $data=array()){
		$this->id     = $id;
		$this->role   = $role;
		$this->labels = $labels;
		$this->locale = $locale;
		$this->data   = $data;
	}
	
	public function setRole($role){
		$this->role = $role;
	}
	
	public function setLabels($labels){
		$this->labels = $labels;
	}
	
	public function setLocale($locale){
		$this->locale = $locale;
	}
	
	public function setData($data){
		$this->data = array_merge($this->data, $data);
	}
	
	/**
	 * Easy access of private properties, because we just prevent write but not read
	 * @param unknown $property
	 * @return boolean
	 */
	public function __get($property){
		if (isset($this->$property)){
			return $this->$property;
		}
		if (!is_null($this->data)){
			if (isset($this->data[$property])){
				return $this->data[$property];
			}
		}
		return null;
	}
}