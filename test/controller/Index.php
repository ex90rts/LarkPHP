<?php
namespace Knock\Controller;

use Alcedoo\Controller;
use Knock\Model\User;
use Alcedoo\Mysql\Query;
use Alcedoo\Env;

class Index extends Controller{
	public $conn;
	
	protected function accessRules(){
		return array(
			array(AC_GRANT,
				'actions' => array('View', 'Test'),
				'groups' => array('Operator', 'Designer'),
			),
			array(AC_GRANT,
				'verbs' => array('POST'),
				'groups' => array('Manager'),
			),
			array(AC_GRANT,
				'verbs' => array('POST'),
				'groups' => array('Manager'),
			),
			array(AC_GRANT,
				'roles' => array('ADMIN'),
				'groups' => array('Manager'),
			),
			array(AC_DENY,
				'roles' => array(ROLE_GUEST),
			),
		);
	}
	
	public function actionView(){
		$user = new User();
		
		$this->res->addDebugInfo('name', $user);
		$this->res->printr($_SERVER);
		die;
		$user = new User();
		$list = $user->findDataByFilter();
		
		foreach ($list as $item){
			echo $item->username.'<br />';
			echo $item->password.'<br />';
			echo $item->created.'<br />';
		}
		/*
		$user->username = 'viki';
		$user->password = '123456';
		$user->created = date('Y-m-d H:i:s');
		if ($user->validate()){
			$user->save();
		}else{
			print_r($user->errors());
		}
		*/
		//$user->delete();
		echo $user;
		echo "this is the index:view action";
	}
	
	public function actionTest(){
		$query = new Query();
		$query->table('Users')->select();
		$mysql = Env::getInstance('Alcedoo\Mysql');
		$res = $mysql->exec($query);
		var_dump($res);
		echo "this is the test view";
	}
	
	public function actionPure(){
		if (!$this->conn){
			$this->conn = mysqli_connect('127.0.0.1', 'root', '123456', 'blog', '3306');
		}
		$query = mysqli_query($this->conn, "select * from Users");
		while($row = mysqli_fetch_assoc($query)){
			var_dump($row);
		}
	}
	
	public function ActionNothing(){
		echo 'nothing';
	}
}