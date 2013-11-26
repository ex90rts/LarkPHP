<?php
namespace Alcedoo;

use Alcedoo\Model;

class UserEntry{
	/**
	 * Declare following property private to prevent value change except init new instacne
	 */
	private $uniqid;
	
	private $role;
	
	private $groups;
	
	private $model;
	
	public function __construct($uniqid = '', $role = null, $groups = array(), Model $model=null){
		$this->uniqid = $uniqid == '' ? uniqid() : $uniqid;
		$this->role = $role == null ? ROLE_GUEST : $role;
		$this->groups = $groups;
		$this->model = $model;
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
		if (!is_null($this->model)){
			if (isset($this->model->$property)){
				return $this->model->$property;
			}
		}
		return null;
	}
}