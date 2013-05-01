<?php
namespace Flexper;

use Flexper\Util;
use Flexper\Exception\CloneNotAllowedException;
use Flexper\Exception\PathNotFoundException;
use Flexper\Exception\WrongParamException;
use Flexper\Exception\WrongLogTypeException;

class Logger{
    private static $_instance;
    
    private $_logDir;
    
    private $_logContent;
    
    private $_logTypes;
    
    private $_logDate;
    
    private function __construct(){
        $logDir = Env::getOption('logDir');
        if (!is_dir($logDir) && !mkdir($logDir)){
            throw new PathNotFoundException(sprintf('path is %s', $logDir));
        }
        
        $this->_logDir = $logDir;
        
        $logTypes = Env::getOption('logTypes');
        if (!is_array($logTypes) || empty($logTypes)){
            $logTypes = array('debug');
        }
        
        $this->_logTypes = $logTypes;
        
        $this->_logDate = date('Y-m-d', Util::getNow());
    }
    
    public static function getInstance(){
        if (!self::$_instance){
            $class = __CLASS__;
            self::$_instance = new $class();
        }
        return self::$_instance;
    }
    
    public function __clone(){
        throw new CloneNotAllowedException(sprintf('class name %s', __CLASS__));
    }
    
    public function append($type, $content){
        if (!is_array($content)){
            $content = array('info'=>$content);
        }
        
        $former = $this->_logContent[$type];
        $current = $content;
        if (!empty($former)){
            $current = array_merge($former, $content);
        }
        
        $this->_logContent[$type] = $current;
    }
    
    public function log($type, $content){
        $this->append($type, $content);
        
        $logTypeDir = $this->_logDir . DIRECTORY_SEPARATOR . $type;
        if (!is_dir($logTypeDir) && !mkdir($logTypeDir)){
            throw new PathNotFoundException(sprintf('path is %s', $logTypeDir));
        }
        
        $logPath = $logTypeDir . DIRECTORY_SEPARATOR . $this->_logDate . '.log';
        
        $contents = $this->_logContent[$type];
        $logTexts = array();
        foreach ($contents as $key=>$value){
            if (!is_string($value)){
                $value = serialize($value);
            }
            $logTexts[] = "[{$key}:{$value}]";
        }
        
        $this->_logContent[$type] = array();
        
        $logText = '[' . date("Y-m-d H:i:s", Util::getNow()) . '] ' . implode(' ', $logTexts) . "\r\n";
        return error_log($logText, 3, $logPath);
    }
    
    /**
     * Override __call magic method
     * 
     * @param string $name
     * @param array $arguments
     * @throws \Exception
     */
    public function __call($name, $arguments){
        $func = substr($name, 0, 3);
        $type = strtolower(substr($name, 3));
        if ($func!=='log' && $func!=='app'){
            throw new WrongParamException(sprintf('wrong method name, only support log/app, %s given', $func));
        }
        
        if (!in_array($type, $this->_logTypes)){
            throw new WrongLogTypeException(sprintf('only support %s, %s given', implode('/', $this->_logTypes), $type));
        }
        
        if (count($arguments)!=1){
            throw new WrongParamException('wrong arguments count in Logger::__call');
        }
        
        if ($func==='log'){
            $this->log($type, $arguments[0]);
        }else{
            $this->append($type, $arguments[0]);
        }
    }
}