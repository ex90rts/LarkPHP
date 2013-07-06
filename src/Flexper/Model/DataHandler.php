<?php
namespace Flexper\Model;

use Flexper\Env;

class DataHandler{
	static function factory($engineType){
		switch ($engineType){
			case 'mysql':
				return Env::getInstance('\Flexper\Mysql');
				break;
			case 'mongo':
				return Env::getInstance('\Flexper\Mongo');
				break;
			default:
				return Env::getInstance('\Flexper\Mysql');
				break;
		}
	}
}