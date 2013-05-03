<?php
namespace Knock\Action\User;

use Flexper\Env;
use Flexper\Action;

error_reporting(E_ALL);

class Signup extends Action{

    function validate (){
        if (!isset($this->request->username)){
            throw new \Exception('user name can not be empty');
        }
        if (!isset($this->request->password)){
            throw new \Exception('password can not be empty');
        }
    }

    function execute (){
        echo "Username: ".$this->request->username."<br />";
        echo "Password: ".$this->request->password."<br />";
        $this->model('User')->insert(array('username'=>$this->request->username, 'password'=>$this->request->password));
    }

    function finish (){

    }
}