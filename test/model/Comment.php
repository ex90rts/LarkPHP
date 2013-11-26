<?php
namespace Knock\Model;

use Alcedoo\Model;

/**
 * The following properties are the avaliable fields in this model, use @property comment for code hinting
 * 
 * @property string $username
 * @author jinhong
 *
 */
class Comment extends Model{
	
	protected function getTable(){
		return "Comments";
	}
	
	protected function getFields(){
		return array(
			'id' => array(
				'name' => 'ID',
				'type' => TYPE_INT,
			),
			'userid' => array(
				'name' => '用户ID', 
				'type' => TYPE_INT,
			),
			'username' => array(
				'name' => '用户名', 
			),
			'content' => array(
				'name' => '评论内容',
			),
			'created' => array(
				'name' => '创建时间',
				'type' => TYPE_DATETIME,
			),
		);
	}
	
	
}