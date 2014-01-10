<?php
namespace Lark;

use Lark\App;
use Lark\Exception\PathNotFoundException;

class Template{
	
	/**
	 * Template base path
	 * 
	 * @var array
	 */
	private $_basePath = '';
	
	/**
	 * Template data holder
	 * 
	 * @var array
	 */
	private $_data = array();
	
	/**
	 * Template file path
	 * @var string
	 */
	private $_templateFile = '';
	
	/**
	 * 
	 * @var string
	 */
	private $_staticHost = '';
	
	/**
	 * Current controller name passed by Request
	 * @var string
	 */
	private $_controller;
	
	/**
	 * Current action name passed by Request
	 * @var string
	 */
	private $_action;
	
	/**
	 * Constructor
	 */
	public function __construct($template, $options=array()){
		if (isset($options['basePath'])){
			$this->_basePath = $options['basePath'];
		}else{
			$this->_basePath = App::getOption('approot') . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR;
		}
		
		$templateFile = $this->_basePath . $template;
		if (!file_exists($templateFile)){
			throw new PathNotFoundException(sprintf('template file path %s not found', $templateFile));
		}
		
		$this->_staticHost = App::getOption('staticHost') ? App::getOption('staticHost') : '/';
		
		$this->_templateFile = $templateFile;
	}
	
	/**
	 * Get any undefined class var value from $_data
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name){
		if (isset($this->_data[$name])){
			return $this->_data[$name];
		}
		return null;
	}
	
    /**
     * Pass current controller name from Request
     * @param string $controller
     */
    public function setController($controller){
    	$this->_controller = $controller;
    }
    
    /**
     * Return current controller name
     * @return string
     */
    public function getController(){
    	return $this->_controller;
    }
    
    /**
     * Pass current action name from Request
     * @param string $controller
     */
    public function setAction($action){
    	$this->_action = $action;
    }
    
    /**
     * Return current action name
     * @return string
     */
    public function getAction(){
    	return $this->_action;
    }
	
	/**
	 * Assign template data
	 * 
	 * @param string $key
	 * @param mixed $value
	 */
	public function assign($key, $value){
		$this->_data[$key] = $value;
	}
	
	/**
	 * Batch assign template data
	 * 
	 * @param array $data
	 */
	public function batchAssign($data){
		$this->_data = array_merge($this->_data, $data);
	}
	
	/**
	 * Format and outout timestamp
	 * 
	 * @param mixed $time
	 * @param string $format
	 */
	public function date($time, $format = 'Y-m-d H:i:s'){
		if (!is_numeric($time)){
			$time = strtotime($time);
		}
		
		echo date($format, $time);
	}
	
	/**
	 * Condition output
	 * 
	 * @param expresion $condition
	 * @param mixed $trueStr Output string when condition is true
	 * @param mixed $falseStr Output string when condition is false
	 */
	public function ifecho($condition, $trueStr, $falseStr=''){
		if ($condition){
			echo $trueStr;
		}else{
			echo $falseStr;
		}
	}
	
	/**
	 * Convinence method for option selected attribute 
	 * @param unknown $condition
	 */
	public function ifselected($condition){
		$this->ifecho($condition, 'selected="selected"');
	}
	
	/**
	 * Convinence method for option required attribute
	 * @param unknown $condition
	 */
	public function ifrequired($condition){
		$this->ifecho($condition, 'required="required"');
	}
	
	/**
	 * Convinence method for checkbox checked attribute
	 * @param unknown $condition
	 */
	public function ifchecked($condition){
		$this->ifecho($condition, 'checked');
	}
	
	/**
	 * Convinence method for checkbox checked attribute
	 * @param unknown $condition
	 */
	public function ifdisabled($condition){
		$this->ifecho($condition, 'disabled="disabled"');
	}
	
	/**
	 * Shorten number
	 * @param number $num
	 */
	public function numShorten($num, $step='k'){
		if ($step=='k'){
			$stepNumber = 1000;
		}else if($step=='w'){
			$stepNumber = 10000;
		}
		
		$num = intval($num);
		if ($num>=$stepNumber){
			if ($num % $stepNumber){
				$k = $num/$stepNumber;
			}else{
				$k = round($num/$stepNumber, 1);
			}
			echo $k . $step;
		}else{
			echo $num;
		}
	}
	
	/**
	 * Output select element
	 * 
	 * @param array $options
	 * @param array $attributes
	 * @param mixed $select
	 */
	public function select($options, $attributes, $selected){
		$attrs = '';
		foreach ($attributes as $key=>$val){
			$attrs .= "{$key}=\"{$val}\" ";
		}
		$html = "<select {$attrs}>";
		foreach ($options as $value=>$option){
			$html .= "<option value='{$value}'";
			if ($value == $selected){
				$html .= ' selected="selected"';
			}
			$html .= ">{$option}</option>";
		}
		$html = "</select>";
		echo $html;
	}
	
	/**
	 * Echo out asset host
	 */
	public function loadAsset($file, $version=''){
		$fileUrl = $this->_staticHost . $file;
		if ($version){
			$fileUrl = $fileUrl . '?v=' . $version;
		}
		echo $fileUrl;
	}
	
	/**
	 * Load javascript file
	 * 
	 * @param string $file
	 * @param string $version
	 */
	public function loadJs($file, $version=''){
		$fileUrl = $this->_staticHost . $file;
		if ($version){
			$fileUrl = $fileUrl . '?v=' . $version;
		}
		echo "<script src=\"{$fileUrl}\"></script>";
	}
	
	/**
	 * Load css file
	 * 
	 * @param string $file
	 * @param string $version
	 */
	public function loadCss($file, $version=''){
		$fileUrl = $this->_staticHost . $file;
		if ($version){
			$fileUrl = $fileUrl . '?v=' . $version;
		}
		echo "<link rel=\"stylesheet\" href=\"{$fileUrl}\">";
	}
	
	/**
	 * Replace system require method 
	 * @param string $file
	 */
	public function requirefile($file){
		$requireFile = $this->_basePath . $file;
		if (!file_exists($requireFile)){
			throw new PathNotFoundException(sprintf('required template file path %s not found', $requireFile));
		}
		
		require $requireFile;
	}
	
	/**
	 * Return rendered html result but not output it
	 * 
	 * @return string
	 */
	public function render(){
		ob_start();
		require $this->_templateFile;
		return ob_get_clean();
	}
	
	/**
	 * Display rendered html to client
	 *
	 * @return string
	 */
	public function display(){
		ob_start();
		require $this->_templateFile;
		ob_flush();
	}
}