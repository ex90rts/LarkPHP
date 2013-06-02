<?php
namespace Knock\Action\Post;

use Flexper\Action;

class Preview extends Action{
	function execute(){
		$textData = $this->request->data;
	}
}