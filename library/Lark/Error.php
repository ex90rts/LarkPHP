<?php
namespace Lark;

class Error{
	
	/**
	 * Error levels defination
	 */
	const LEVEL_GLOBAL     = 'LEVEL_GLOBAL';
	const LEVEL_CONTROLLER = 'LEVEL_CONTROLLER';
	const LEVEL_MODEL      = 'LEVEL_MODEL';
	const LEVEL_ACTION     = 'LEVEL_ACTION';
	
	/*
	 * Error types defination
	 */
	const ERR_ACCESS_DENY    = 'ERR_ACCESS_DENY';
	const ERR_ACCESS_BAN     = 'ERR_ACCESS_BAN';
	const ERR_FEATURE_DENY   = 'ERR_FEATURE_DENY';
	const ERR_EMPTY          = 'ERR_EMPTY';
	const ERR_PARAMS         = 'ERR_PARAMS';
	const ERR_TAKEN          = 'ERR_TAKEN';
	const ERR_USED           = 'ERR_USED';
	const ERR_TOO_SMALL      = 'ERR_TOO_SMALL';
	const ERR_TOO_BIG        = 'ERR_TOO_BIG';
	const ERR_TOO_SHORT      = 'ERR_TOO_SHORT';
	const ERR_TOO_LONG       = 'ERR_TOO_LONG';
	const ERR_FORMAT         = 'ERR_FORMAT';
	const ERR_NOT_ACCEPT     = 'ERR_NOT_ACCEPT';
	const ERR_NOT_INT        = 'ERR_NOT_INT';
	const ERR_NOT_NUM        = 'ERR_NOT_NUM';
	const ERR_NOT_BOOL       = 'ERR_NOT_BOOL';
	const ERR_NOT_DATE       = 'ERR_NOT_DATE';
	const ERR_SIZE_TOO_SMALL = 'ERR_SIZE_TOO_SMALL';
	const ERR_SIZE_TOO_BIG   = 'ERR_SIZE_TOO_BIG';
	const ERR_DIME_TOO_SMALL = 'ERR_DIME_TOO_SMALL';
	const ERR_DIME_TOO_BIG   = 'ERR_DIME_TOO_BIG';
	const ERR_NOT_EXIST      = 'ERR_NOT_EXIST';
	const ERR_EXPIRED        = 'ERR_EXPIRED';
	const ERR_TIMES_EXCEED   = 'ERR_TIMES_EXCEED';
	const ERR_NUMBER_EXCEED  = 'ERR_NUMBER_EXCEED';
	const ERR_ALREADY_DONE   = 'ERR_ALREADY_DONE';
	const ERR_NOT_MATCH      = 'ERR_NOT_MATCH';
	const ERR_UNKNOWN        = 'ERR_UNKNOWN';
	const ERR_CANNOT_DELETE  = 'ERR_CANNOT_DELETE';
	
	/**
	 * Error level
	 * 
	 * @var string
	 */
	private $level = self::LEVEL_GLOBAL;
	
	/**
	 * Error type
	 * 
	 * @var string
	 */
	private $type = self::ERR_UNKNOWN;
	
	/**
	 * Key to get the data of this error related
	 * 
	 * @var string
	 */
	private $key = '';
	
	/**
	 * Associated data
	 * 
	 * @var string
	 */
	private $assoc = array();
	
	/**
	 * Project error configuration
	 * 
	 * @var array
	 */
	private $config = array();
	
	public function __construct($level, $type, $key='', $assoc=array()){
		$this->level = $level;
		$this->type  = $type;
		$this->key   = $key;
		$this->assoc = $assoc;
	}
	
	public function getLevel(){
		return $this->level;
	}
	
	public function getType(){
		return $this->type;
	}
	
	public function getKey(){
		return $this->key;
	}
	
	public function getAssoc(){
		return $this->assoc;
	}
	
	public function toArray(){
		return array(
			'level' => $this->level,
			'type'  => $this->type,
			'key'   => $this->key,
			'assoc' => $this->assoc,
		);
	}
	
	public function __toString(){
		$string = "{$this->level}:{$this->type}:{$this->key}";
		if ($this->assoc){
			$string .= ":".implode(",", $this->assoc);
		}
		return $string;
	}
}
