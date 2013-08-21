<?php
namespace Knock\Model;

use Alcedoo\Model;

class Comment extends Model{
	public $id;
	public $uid;
	public $username;
	public $email;
	public $title;
	public $content;
	public $created;
	
	protected function getEngineType(){
		return parent::ENGINE_MYSQL;
	}
	
	protected function getTableName(){
		return "Comments";
	}
	
	protected function getValidRules(){
		return array(
			'id' => array('required', 'int'),
			'uid' => array('required'),
			'username' => array('required'),
			'content' => array('required'),
		);
	}
}