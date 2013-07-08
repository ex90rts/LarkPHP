<?php
namespace Flexper\Model\Adapter;

use Flexper\Model;
use Flexper\Env;
use Flexper;

class Mongo{
	private $mongo;
	
	public function __construct(Model $model){
		$this->mongo = Env::getInstance('Flexper\Mongo');
	}
	
	public function findDataByID($collection, $id){
		$query = array(
			'_id'=>$id
		);
		return $this->mongo->findOne($collection, $query);
	}
	
	public function findDataByUID($collection, $uid){
		$query = array(
			'uid'=>$uid
		);
		return $this->mongo->findOne($collection, $query);
	}
	
	public function insert($collection, $record){
		$record['_id'] = Flexper\Mongo::MongoId();
		$res = $this->mongo->insert($collection, $record);
		if ($res){
			$res = $record['_id'];
		}
		
		return $res;
	}
	
	public function update($collection, $query, $record){
		return $this->mongo->update($collection, $query, $record);
	}
	
	public function delete($collection, $query){
		return $this->mongo->remove($collection, $query);
	}
}