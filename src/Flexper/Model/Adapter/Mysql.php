<?php
namespace Flexper\Model\Adapter;

use Flexper\Mysql\Query;
use Flexper\Env;
use Flexper\Model;

class Mysql{
	private $table;
	
	private $mysql;
	
	public function __construct(Model $model){
		$this->table = $model->getTableName();
		$this->mysql = Env::getInstance('Flexper\Mysql');
	}
	
	public function findDataByID($table, $id){
		$query = new Query();
		$query->table($table)
			  ->where(array(
			  		'id' => $id,
				))
			  ->limit(1);
		return $this->mysql->exec($query);
	}
	
	public function findDataByUID($table, $uid){
		$query = new Query();
		$query->table($table)
		->where(array(
				'uid' => $uid,
		))
		->limit(1);
		return $this->mysql->exec($query);
	}
	
	public function insert($table, $record){
		$query = new Query(array('insertId'=>true));
		$query->table($table)->insert($record);
		$res = $this->mysql->exec($query);
		if (is_int($res)){
			return $res;
		}
		
		return false;
	}
	
	public function update($table, $where, $record){
		$query = new Query(array('affectedRows'=>true));
		$query->table($table)->where($where)->update($record);
		return $this->mysql->exec($query);
	}
	
	public function delete($table, $where){
		$query = new Query();
		$query->table($table)->where($where)->delete();
		return $this->mysql->exec($query);
	}
}