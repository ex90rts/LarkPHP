<?php
namespace Knock\Model;

use Lark\Model;

/**
 * User model
 * 
 * @author jinhong
 *
 * @property string $username
 * @property string $password
 * @property datetime $created
 * 
 */
class User extends Model{	
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
			'created' => array(
				'name' => '创建时间',
				'type' => TYPE_DATETIME,
			)
		);
	}
}