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
	 * Template global data holder
	 * 
	 * @var array
	 */
	private $_global = array();
	
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
	 * Current visitor's language locale
	 * @var string
	 */
	private $_locale = 'en-US';
	
	/**
	 * Responder for localize the languages
	 * @var mixed
	 */
	private $_localizer = false;
	
	/**
	 * Errors
	 * 
	 * @var array
	 */
	private $_errors = array();
	
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
     * Set current locale
     * 
     * @param string $locale
     */
    public function setLocale($locale){
    	$this->_locale = $locale;
    }
    
    /**
     * Set current localizer
     * 
     * @param Lark\Localizer $localizer
     */
    public function setLocalizer(Localizer $localizer){
    	$this->_localizer = $localizer;
    }
    
    /**
     * Set output errors
     * 
     * @param array $errors
     */
    public function setErrors($errors){
        $this->_errors = $errors;
    }
    
    /**
     * Get errors
     * 
     * @return array
     */
    public function getErrors(){
        return $this->_errors;
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
	 * Assign global template data
	 * 
	 * @param array $data
	 */
	public function assignGlobal($data){
		$this->_global = array_merge($this->_global, $data);
	}
	
	/**
	 * Read global template data
	 */
	public function readGlobal($key){
		if (isset($this->_global[$key])){
			return $this->_global[$key];
		}else{
			return false;
		}
	}
	
	/**
	 * Echo out localized language content
	 * 
	 * @param string $key language key
	 */
	public function localize($key, $replacement=array()){
		if (!$this->_localizer){
			echo "{{no localizer}}";
			return;
		}
		
		$content = $this->_localizer->say($key, $this->_locale, $replacement);
		if ($content){
			echo $content;
		}else{
			echo $key;
		}
	}
	
	/**
	 * Return localized language content
	 * 
	 * @param string $key language key
	 */
	public function getLocalize($key, $replacement=array()){
		if (!$this->_localizer){
			return "{{no localizer}}";
		}
		
		$content = $this->_localizer->say($key, $this->_locale, $replacement);
		if ($content){
			return $content;
		}else{
			return $key;
		}
	}
	
	/**
	 * Echo out localized language content via input
	 * @param unknown $content
	 */
	public function locale($content){
		if (isset($content[$this->_locale])){
			echo $content[$this->_locale];
		}else{
			echo "{{$this->_locale}}";
		}
	}

	/**
	 * Return localized language content via input
	 * @param unknown $content
	 */
	public function getLocale($content){
		if (isset($content[$this->_locale])){
			return $content[$this->_locale];
		}else{
			return "{{$this->_locale}}";
		}
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
		
		if ($time < 86400){
		    echo '';
		}else{
		  echo date($format, $time);
		}
	}

	/**
	 * Format and outout timestamp
	 * 
	 * @param mixed $time
	 * @param string $format
	 */
	public function getDate($time, $format = 'Y-m-d H:i:s'){
		if (!is_numeric($time)){
			$time = strtotime($time);
		}
		
		if ($time < 86400){
		  return '';
		}else{
		  return date($format, $time);
		}
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
	 * Output pure html code
	 */
	public function echoHtml($html){
		echo htmlspecialchars_decode($html);
	}
	
	/**
	 * Shorten number
	 * @param number $num
	 */
	/*jjjjjjjjjjjjjjjj
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
				$k = round($num/$stepNumber, 1);
			}else{
				$k = round($num/$stepNumber, 1);
			}
			echo $k . $step;
		}else{
			echo $num;
		}
	}
    */

    /**
	 * Shorten number
	 * @param number $num
	 * modified by lawyu
	 */
	public function numShorten($num, $step='k'){
        $num = intval($num);
        if ($num > 9999) $step = 'w';
        if ($step=='k'){
            $stepNumber = 1000;
        }else if($step=='w'){
            $stepNumber = 10000;
        }

        if ($num>=$stepNumber){
            if ($num % $stepNumber){
                $k = $num/$stepNumber;
                $k = round($num/$stepNumber, 1);
            }else{
                //$k = round($num/$stepNumber, 1);
            }
            echo $k . $step;
        }else{
            echo $num;
        }
    }

	/**
	 * Output rounded number
	 * @param number $num
	 * @param number $dime
	 */
	public function numRound($num, $precision=1, $limit=false){
		$round = round($num, $precision);
		if ($limit && $round>$limit){
			$round = $limit;
		}
		
		echo $round;
	}
	
	/**
	 * Return rounded number
	 * @param number $num
	 * @param number $dime
	 */
	public function getNumRound($num, $precision=1, $limit=false){
		$round = round($num, $precision);
		if ($limit && $round>$limit){
			$round = $limit;
		}
		
		return $round;
	}
	
	/**
	 * Truncate and output string
	 * 
	 * @param unknown $text
	 * @param unknown $length
	 */
	public function truncate($text, $len, $pad='...'){
	    echo Util::mbcutString($text, $len, $pad);
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
	 * Output sort filed, styled with Glyphicons ICON
	 */
	public function sortField($field, $sort, $curOrder, $query, $label, $showIcon=true){
		$nextOrder = 'DESC';
		$icon = 'glyphicon glyphicon-sort';
		if ($field==$sort){
			if ($curOrder == 'ASC'){
				$nextOrder = 'DESC';
				$icon = 'glyphicon glyphicon-sort-by-attributes';
			}elseif($curOrder == 'DESC'){
				$nextOrder = 'ASC';
				$icon = 'glyphicon glyphicon-sort-by-attributes-alt';
			}
		}
		$href = '?'.http_build_query($query)."&sort={$field}&order={$nextOrder}";
		if ($showIcon){
			echo "<a href=\"{$href}\">{$label}<i class=\"{$icon} small\"></i></a>";
		}else{
			echo "<a href=\"{$href}\">{$label}</a>";
		}
	}
	
	/**
	 * Echo out asset host
	 */
	public function loadAsset($fileUrl, $version=''){
		if (!preg_match('/^https?:\/\//i', $fileUrl)){
			$fileUrl = $this->_staticHost . $fileUrl;
		}
		
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
	public function loadJs($fileUrl, $version=''){
		if (!preg_match('/^https?:\/\//i', $fileUrl)){
			$fileUrl = $this->_staticHost . $fileUrl;
		}
		
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
	public function loadCss($fileUrl, $version=''){
		if (!preg_match('/^https?:\/\//i', $fileUrl)){
			$fileUrl = $this->_staticHost . $fileUrl;
		}
		
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