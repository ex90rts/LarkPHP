<?php
namespace Knock\Action\Post;

use Flexper\Action;

class Newpost extends Action{
	function execute(){
		$request = $this->request;
		$response = $this->response;
		
		if (empty($request->title) || empty($request->content)){
			$response = $this->response;
			$response->tab = 'LOGIN';
			$response->template('post/newpost.php');
		}else{
			$title = $request->title;
			$tags = $request->tags;
			$content = $request->content;
			
			$record = array(
				'title' => $title,
				'tags' => $tags,
				'content' => $content,
			);
			
		}
	}
}