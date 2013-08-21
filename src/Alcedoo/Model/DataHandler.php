<?php
namespace Alcedoo\Model;

use Alcedoo\Env;

class DataHandler{
	static function factory($engineType){
		switch ($engineType){
			case 'mysql':
				return Env::getInstance('\Alcedoo\Mysql');
				break;
			case 'mongo':
				return Env::getInstance('\Alcedoo\Mongo');
				break;
			default:
				return Env::getInstance('\Alcedoo\Mysql');
				break;
		}
	}
}