<?php
namespace Knock\Action\Post;

use Flexper\Action;

class Newpost extends Action{
	function execute(){
		$response = $this->response;
		$response->template('post/newpost.php');
	}
}