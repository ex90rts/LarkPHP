<?php
namespace Flexper\Model;

use Flexper\Mysql;
use Flexper\Mongo;

class DataHandler{
	static function factory($engineType){
		switch ($engineType){
			case 'mysql':
				return new Mysql();
				break;
			case 'mongo':
				return new Mongo();
				break;
			default:
				return new Mysql();
				break;
		}
	}
}