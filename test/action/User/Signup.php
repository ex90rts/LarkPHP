<?php
namespace Knock\Action\User;

use Flexper\Env;
use Flexper\Action;

ini_set('display_errors', 1);
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

    function checkPermissions (){

    }

    function execute (){
        echo "Username: ".$this->request->username."<br />";
        echo "Password: ".$this->request->password."<br />";
        $this->model('UserModel')->insert(array('username'=>$this->request->username, 'password'=>$this->request->password));
    }

    function finish (){

    }
}