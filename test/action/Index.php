<?php
namespace Knock\Action;

use Flexper\Action;

class Index extends Action{
	function execute(){
		$textData = $this->request->data;
	}
}