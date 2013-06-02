<?php
namespace Knock\Action\User;

use Flexper\Action;

class Login extends Action{
	function execute(){
		$this->response->setSession('name', 'Samoay');
		$this->response->setSession('group', 'user');
		$this->response->setSession('permissions', array('User_Get'));

		$this->response->text('Login Succed');
		sleep(5);
		$this->redirect();
	}

	function redirect(){
		$this->response->redirect('goto', '/test/index.php?action=user/get');
	}
}