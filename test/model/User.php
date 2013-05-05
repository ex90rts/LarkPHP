<?php
namespace Knock\Model;

use Flexper\Model;

class User extends Model{

    static function getEngine(){
		return parent::ENGINE_MONGO;
    }

    static public function getTable(){
    	return 'Users';
    }

    public function query(array $query, $limit=0, $skip=0){
        return $this->getDataEngine()->find($this->table, $query, array(), array(), $limit, $skip);
    }
}