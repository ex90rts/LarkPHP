<?php
namespace Knock\Action;

use Flexper\Action;

class Index extends Action{
	function execute(){
		$response = $this->response;
		$response->tab = 'HOME';
		$response->template('index.php');
	}
}