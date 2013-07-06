<?php
namespace Flexper;

use Flexper\Model\DataHandler;
use Flexper\Exception\ModelValidationException;

abstract class Model{

	const ENGINE_MYSQL = 'mysql';

	const ENGINE_MONGO = 'mongo';

    private function getProperties(){
    	$class = new \ReflectionClass(get_called_class());
    	$properties = $class->getProperties(ReflectionProperty::IS_PUBLIC);
    	return $properties;
    }

    public function __construct($key=''){
    	$this->engine = DataHandler::factory(static::$engineType);
    	if (is_int($key)){
    		$this->findDataByID($key);
    	}else if ($key != ''){
    		$this->findDataByUID($key);
    	}
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

    public function loadData(Array $data){
    	$properties = $this->getProperties();
    	print_r($properties);
    	foreach ($properties as $property){
    		if (isset($data[$property])){
    			$this->$property = $data[$property];
    		}
    	}
    }

    public function validate(){
    	$properties = $this->getProperties();
    	print_r($properties);
    	
    	$rules = $this->rules;
    	
    	print_r($rules);
    	
    	foreach ($rules as $property=>$value){
    		foreach ($value as $key=>$rule){
    			if (is_int($key)){
	    			switch ($rule){
	    				case 'required':
	    					if (empty($this->$property)){
	    						throw new ModelValidationException("Model property {$property} is required");
	    					}
	    					break;
	    				case 'int':
	    					if (!is_int($this->$property)){
	    						throw new ModelValidationException("Model property {$property} must be an integer");
	    					}
	    					break;
	    				case 'numeric':
	    					if (!is_numeric($this->$property)){
	    						throw new ModelValidationException("Model property {$property} must be a number");
	    					}
	    					break;
	    				case 'bool':
	    					if (!is_bool($this->$property)){
	    						throw new ModelValidationException("Model property {$property} must be a boolean value");
	    					}
	    					break;
	    			}
    			}else{
    				if ($key == 'regex'){
    					if (!preg_match($value, $this->$property)){
    						throw new ModelValidationException("Model property {$property} value:[{$this->$property}] can not pass regex check");
    					}
    				}elseif ($key == 'enum' && is_array($value)){
    					if (!in_array($this->$property, $value, true)){
    						throw new ModelValidationException("Model property {$property} value:[{$this->$property}] not in the num list:[".implode('|', $value)."]");
    					}
    				}
    			}
    		}
    	}
    }
    
    private function perpare(){
    	
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
}