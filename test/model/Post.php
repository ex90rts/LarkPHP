<?php
namespace Knock\Model;

use Flexper\Model;

class Post extends Model{
    public function getTable(){
    	return 'Posts';
    }

    public function query(array $query, $limit=0, $skip=0){
        return $this->engine->find($this->getTable(), $query, array(), array(), $limit, $skip);
    }
}