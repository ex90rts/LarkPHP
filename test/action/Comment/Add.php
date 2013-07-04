<?php
namespace Knock\Action\Comment;

use Flexper\Action;
use Knock\Model\Comment;
use Flexper\Uniqid;

class Add extends Action{
	function execute(){
		$request = $this->request;
		$response = $this->response;
		
		$uniqid = new Uniqid();
		
		$comment = new Comment();
		if ($request->type == 'a'){
			$comment->uid = $uniqid->create('13');
			$comment->title = 'New comment';
			$comment->validate();
		}else{
			$data = array(
				'uid' => '178324932493',
				'title' => 'New comment',
			);
			$comment->loadData($data);
		}
		$comment->validate();
	}
}