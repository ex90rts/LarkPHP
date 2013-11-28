<?php
namespace Lark;

class Template{
	/**
	 * Format and outout timestamp
	 * 
	 * @param unknown $time
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
}