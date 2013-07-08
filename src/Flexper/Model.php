<?php
namespace Flexper;

use Flexper\Model\DataHandler;
use Flexper\Model\DataList;

abstract class Model{

	const ENGINE_MYSQL = 'mysql';

	const ENGINE_MONGO = 'mongo';
	
	private $engine;
	
	private $errors = array();

	public function __construct($key=''){
		$this->engine = DataHandler::factory($this->getEngineType());
		if (is_int($key)){
			$this->findDataByID($key);
		}else if ($key != ''){
			$this->findDataByUID($key);
		}
	}
	
	abstract protected function getEngineType();
	
	abstract protected function getTableName();
	
	abstract protected function getValidRules();
	
    private function getProperties(){
    	$class = new \ReflectionClass(get_called_class());
    	$properties = $class->getProperties(\ReflectionProperty::IS_PUBLIC);
    	$result = array();
    	foreach ($properties as $property){
    		$result[] = $property->name;
    	}
    	return $result;
    }

    private function findDataByID($id){
    	$data = $this->engine->model($this)->findDataByID($id);
    	if ($data){
    		$this->loadData($data);
    	}
    }
    
    private function findDataByUID($uid){
    	$data = $this->engine->model($this)->findDataByUID($uid);
    	if ($data){
    		$this->loadData($data);
    	}
    }

    public function findDataByFilter($filter, $sort=array(), $limit){
    	$list = $this->engine->model($this)->findDataByFilter();
    	if ($list){
    		return new DataList($this, $list);
    	}
    	
    	return false;
    }
    
    public function findDataByPage(){
    	
    }
    
    public function loadData(Array $data){
    	$properties = $this->getProperties();
    	foreach ($properties as $property){
    		if (isset($data[$property])){
    			$this->$property = $data[$property];
    		}
    	}
    }

    public function validate(){
    	$valid = true;
    	$properties = $this->getProperties();
    	$rules = $this->getValidRules();
    	foreach ($rules as $property=>$value){
    		foreach ($value as $key=>$rule){
    			if (is_int($key)){
	    			switch ($rule){
	    				case 'required':
	    					if (empty($this->$property)){
	    						$valid = false;
	    						$this->errors[] = "Model property {$property} is required";
	    					}
	    					break;
	    				case 'int':
	    					if (!is_int($this->$property)){
	    						$valid = false;
	    						$this->errors[] = "Model property {$property} must be an integer";
	    					}
	    					break;
	    				case 'numeric':
	    					if (!is_numeric($this->$property)){
	    						$valid = false;
	    						$this->errors[] = "Model property {$property} must be a number";
	    					}
	    					break;
	    				case 'bool':
	    					if (!is_bool($this->$property)){
	    						$valid = false;
	    						$this->errors[] = "Model property {$property} must be a boolean value";
	    					}
	    					break;
	    			}
    			}else{
    				if ($key == 'regex'){
    					if (!preg_match($value, $this->$property)){
    						$valid = false;
    						$this->errors[] = "Model property {$property} value:[{$this->$property}] can not pass regex check";
    					}
    				}elseif ($key == 'enum' && is_array($value)){
    					if (!in_array($this->$property, $value, true)){
    						$valid = false;
    						$this->errors[] = "Model property {$property} value:[{$this->$property}] not in the num list:[".implode('|', $value)."]";
    					}
    				}
    			}
    		}
    	}
    	
    	return $valid;
    }
    
    public function insert(){
    	$this->engine->model($this)->insert();
    }

    public function update(){
    	$this->engine->model($this)->update();
    }

    public function delete(){
    	$this->engine->model($this)->delete();
    }
    
    public function errors(){
    	return $this->errors;
    }
}