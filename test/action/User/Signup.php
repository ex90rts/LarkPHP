<?php
namespace Knock\Action\User;

use Flexper\Env;
use Flexper\Action;
use Flexper\Constants;

class Signup extends Action{

	function init(){
		$this->rules = array(
			array(VALID_REQUIRED, 'username', 'password'),
			array(VALID_REGEX, '/^\w{5,20}$/', 'username'),
			array(VALID_REGEX, '/^*{6,16}$/'. 'password'),
		);
	}

    function execute (){
        echo "Username: ".$this->request->username."<br />";
        echo "Password: ".$this->request->password."<br />";
        $this->model('User')->insert(array('username'=>$this->request->username, 'password'=>$this->request->password));
    }

    function finish (){

    }
}