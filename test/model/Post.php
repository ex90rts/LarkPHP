<?php
namespace Knock\Model;

use Alcedoo\Model;

class Post extends Model{
public function getEngineType(){
		return 'mysql';
	}
	
    public function getTableName(){
    	return 'Posts';
    }
    
    public function getValidRules(){
    	
    }

    public function query(array $query, $limit=0, $skip=0){
        return $this->engine->find($this->getTableName(), $query, array(), array(), $limit, $skip);
    }
}