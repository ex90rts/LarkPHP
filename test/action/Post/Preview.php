<?php
namespace Knock\Action\Post;

use Lark\Action;
use Michelf\Markdown;

class Preview extends Action{
	function execute(){
		$textData = $this->request->data;
		$htmlData = Markdown::defaultTransform($textData);
		$this->response->text($htmlData);
	}
}