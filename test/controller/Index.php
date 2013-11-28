<?php
namespace Knock\Controller;

use Lark\Controller;
use Knock\Model\User;
use Lark\Mysql\Query;
use Lark\Env;
use Lark\Request;
use Lark\Response;

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
				'groups' => array('Manager', 'Guest'),
			),
			array(AC_GRANT,
				'verbs' => array('POST'),
				'groups' => array('Manager'),
			),
			array(AC_GRANT,
				'roles' => array(ROLE_GUEST),
			),
		);
	}
	
	public function view(Request $req, Response $res){
		$user = new User();
		
		$user = new User();
		$list = $user->findDataByFilter();
		
		$res->vardump($list);
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
	
	public function test(){
		$query = Query::init()->table('Users')->select();
		$mysql = Env::getInstance('Mysql');
		$res = $mysql->exec($query);
		$this->res->json($res);
	}
	
	public function pure(){
		if (!$this->conn){
			$this->conn = mysqli_connect('127.0.0.1', 'root', '123456', 'blog', '3306');
		}
		$query = mysqli_query($this->conn, "select * from Users");
		while($row = mysqli_fetch_assoc($query)){
			var_dump($row);
		}
	}
	
	public function nothing(){
		echo 'nothing';
	}
}