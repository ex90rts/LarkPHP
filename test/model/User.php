<?php
namespace Knock\Model;

use Flexper\Model;

class User extends Model{
    public function getTable(){
    	return 'Users';
    }

    public function query(array $query, $limit=0, $skip=0){
        return $this->engine->find($this->getTable(), $query, array(), array(), $limit, $skip);
    }
}