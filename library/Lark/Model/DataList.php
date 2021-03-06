<?php
namespace Lark\Model;

class DataList implements \Iterator{
	
	private $array = array();
	
	private $position = 0;
	
	public function __construct($modelName, array $list, $hashKey=false){
		foreach ($list as $row){
			$model = new $modelName(0, $hashKey);
			$model->loadData($row);
			$this->array[] = $model;
		}
	}
	
	/* (non-PHPdoc)
	 * @see Iterator::current()
	 */
	public function current() {
		// TODO Auto-generated method stub
		$current = null;
		
		$pos = $this->position;
		if (isset($this->array[$pos])){
			$current = $this->array[$pos];
		}
		
		return $current;
	}

	/* (non-PHPdoc)
	 * @see Iterator::next()
	 */
	public function next() {
		// TODO Auto-generated method stub
		$next = null;
		
		$this->position++;
		
		if (isset($this->array[$this->position])){
			$next = $this->array[$this->position];
		}
		
		return $next;
	}

	/* (non-PHPdoc)
	 * @see Iterator::key()
	 */
	public function key() {
		// TODO Auto-generated method stub
		return $this->position;
	}

	/* (non-PHPdoc)
	 * @see Iterator::valid()
	 */
	public function valid() {
		// TODO Auto-generated method stub
		return isset($this->array[$this->position]);
	}

	/* (non-PHPdoc)
	 * @see Iterator::rewind()
	 */
	public function rewind() {
		// TODO Auto-generated method stub
		$this->position = 0;
	}
}