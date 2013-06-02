<?php
namespace Knock\Action\User;

use Flexper\Action;

class Login extends Action{
	function execute(){
		$request = $this->request;
		$response = $this->response;
		
		if (empty($request->username) || empty($request->password)){
			$response->tab = 'LOGIN';
			$response->template('user/login.php');
		}else{
			$username = $request->username;
			$password = $request->password;
			if ($username=='samoay' && strtoupper(md5($password))=='BD00F8FDBC613A64A28459E63A6849CA'){
				$this->response->setSession('name', 'Samoay');
				$this->response->setSession('group', 'user');
				$this->response->setSession('permissions', array('User_Get'));
				
				$this->response->text('Login Succed');
				$this->response->redirect('goto', '/test/index.php?action=post/newpost');
			}else{
				$this->response->redirect('goto', '/test/index.php?action=user/login');
			}
		}
	}

	function redirect(){
		
	}
}