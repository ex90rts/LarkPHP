<?php
namespace Knock\Model;

use Alcedoo\Model;


class User extends Model{
	
	protected function getTable(){
		return "Users";
	}
	
	protected function getFields(){
		return array(
			'id' => array(
				'name' => 'ID',
				'type' => TYPE_INT,
			),
			'username' => array(
				'name' => '用户名',
			),
			'password' => array(
				'name' => '评论内容',
			),
		);
	}
}