<?php
namespace Knock\Action\User;

use Flexper\Action;

class Login extends Action{
	function execute(){
		if (empty($_POST['LoginForm'])){
			$response = $this->response;
			$response->tab = 'LOGIN';
			$response->template('user/login.php');
		}else{
			$this->response->setSession('name', 'Samoay');
			$this->response->setSession('group', 'user');
			$this->response->setSession('permissions', array('User_Get'));
	
			$this->response->text('Login Succed');
			sleep(5);
			$this->redirect();
		}
	}

	function redirect(){
		$this->response->redirect('goto', '/test/index.php?action=user/get');
	}
}