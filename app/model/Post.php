<?php
namespace Foo\Model;

use Lark\Model;

class Post extends Model{
	
	protected function getFields(){
		return array(
			'id' => array(
				'name' => 'ID',
				'type' => TYPE_INT,
			),
			'title' => array(
				'name' => '标题',
			),
			'tags' => array(
				'name' => '标签',
			),
			'content' => array(
				'name' => '内容',
			),
			'created' => array(
				'name' => '创建时间',
				'type' => TYPE_DATETIME,
			),
			'updated' => array(
				'name' => '更新时间',
				'type' => TYPE_DATETIME,
			)
		);
	}

    public function query(array $query, $limit=0, $skip=0){
        return $this->engine->find($this->getTableName(), $query, array(), array(), $limit, $skip);
    }
}