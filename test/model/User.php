<?php
namespace Knock\Model;

use Flexper\Model;

class User extends Model{
    
    private $table = 'Users';
    
    public function insert(array $record){
        return $this->getDataEngine()->insert($this->table, $record);
    }
    
    public function update(array $query, array $newRecord){
        return $this->getDataEngine()->update($this->table, $query, $newRecord);
    }
    
    public function delete(array $query){
        return $this->getDataEngine()->remove($this->table, $query);
    }
    
    public function query(array $query, $limit=0, $skip=0){
        return $this->getDataEngine()->find($this->table, $query, array(), array(), $limit, $skip);
    }
    
    
}