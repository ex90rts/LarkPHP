<?php
namespace Knock\Model;

use Flexper\Model;

class Comment extends Model{
	static $engineType = 'mysql';
	
	protected $test = 'test';
	
	public $id;
	public $uid;
	public $username;
	public $email;
	public $title;
	public $content;
	public $created;
	
	protected $rules = array(
		'id' => array('required', 'int'),
		'uid' => array('required'),
		'username' => array('required'),
		'content' => array('required'),
	);
}