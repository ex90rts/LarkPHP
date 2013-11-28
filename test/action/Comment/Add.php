<?php
namespace Knock\Action\Comment;

use Lark\Action;
use Knock\Model\Comment;
use Lark\Uniqid;

class Add extends Action{
	function execute(){
		$request = $this->request;
		$response = $this->response;
		
		$uniqid = new Uniqid();
		echo "<pre>";
		$comment = new Comment();
		if ($request->type == 'a'){
			$comment->uid = $uniqid->create('13');
			$comment->title = 'New comment';
		}else{
			$data = array(
				'uid' => '178324932493',
				'title' => 'New comment',
			);
			$comment->loadData($data);
		}
		if (!$comment->validate()){
			print_r($comment->errors());
		}
		var_dump($comment);
		echo "</pre>";
	}
}