<?php
namespace Knock\Action\User;

use Lark\Env;
use Lark\Action;
use Lark\Constants;

class Password extends Action{

	function init(){
		$this->rules = array(
			array(parent::VALID_REQUIRED, 'username', 'password'),
			array(parent::VALID_REGEX, '/^\w{5,20}$/', 'username'),
			array(parent::VALID_REGEX, '/^\w{6,16}$/', 'password'),
		);
	}

    function execute (){
        echo "Username: ".$this->request->username."<br />";
        echo "Password: ".$this->request->password."<br />";
        $this->model('User')->update(array('username'=>$this->request->username), array('password'=>$this->request->password));
    }

    function finish (){

    }
}