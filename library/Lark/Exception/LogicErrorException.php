<?php
namespace Lark\Exception;

use Lark\App;
use Lark\Exception;

class LogicErrorException extends Exception{
	private $errors = array();
	
	public function __construct($errors){
		if (is_array($errors)){
			array_push($this->errors, $errors);
		}else{
			$this->errors[] = $errors;
		}
		
		$message = App::$codename . " application logic error";
		parent::__construct($message, 400);
	}
	
	public function getErrors(){
		return $this->errors;
	}
}