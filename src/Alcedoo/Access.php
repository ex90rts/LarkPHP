<?php
namespace Alcedoo;

use Alcedoo\Request;

class Access{
	
	/**
	 * User roles defination
	 */
	const ROLE_GUEST = 'GUEST';
	const ROLE_USER  = 'USER';
	const ROLE_ADMIN = 'ADMIN';
	
	/**
	 * Auth type defination
	 */
	const AUTH_CREATE = 'CREATE';
	const AUTH_READ   = 'READ';
	const AUTH_UPDATE = 'UPDATE';
	const AUTH_DELETE = 'DELETE';
	const AUTH_ACTION = 'ACTION';
	
	public function __construct(Request $request){
		
	}
}