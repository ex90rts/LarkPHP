<?php
namespace Lark;

use Lark\Util;
use Lark\Exception\CloneNotAllowedException;
use Lark\Exception\PathNotFoundException;
use Lark\Exception\WrongParamException;
use Lark\Exception\WrongLogTypeException;

class Logger{
	/**
	 * Var for holding singleton instance of this class
	 * @var object
	 */
    private static $_instance;
    
    /**
     * Var for holding base dir of log files
     * @var string
     */
    private $_logDir;
    
    /**
     * Var for holding temp log content before write to file
     * @var Array
     */
    private $_logContent;
    
    /**
     * Var for holding available log types
     * @var Array
     */
    private $_logTypes;
    
    /**
     * Var for caching current yyyy-mm-dd date str in script
     * @var string
     */
    private $_logDate;
    
    /**
     * Construct function
     * 
     * @throws PathNotFoundException
     */
    private function __construct(){
        $logDir = App::getOption('logDir');
        if (!is_dir($logDir) && !mkdir($logDir)){
            throw new PathNotFoundException(sprintf('path is %s', $logDir));
        }
        
        $this->_logDir = $logDir;
        
        $logTypes = App::getOption('logTypes');
        if (!is_array($logTypes) || empty($logTypes)){
            $logTypes = array('debug');
        }
        
        $this->_logTypes = $logTypes;
        
        $this->_logDate = date('Y-m-d', Util::getNow());
    }
    
    /**
     * Method to return the singleton instance of Logger
     * @return Object
     */
    public static function getInstance(){
        if (!self::$_instance){
            $class = __CLASS__;
            self::$_instance = new $class();
        }
        return self::$_instance;
    }
    
    /**
     * Block the clone method
     * 
     * @throws CloneNotAllowedException
     */
    public function __clone(){
        throw new CloneNotAllowedException(sprintf('class name %s', __CLASS__));
    }
    
    /**
     * Append partial log content to an exist log type
     * 
     * @param string $type
     * @param string $content
     */
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
    
    /**
     * Write the log to log files
     * 
     * @param string $type
     * @param string $content
     * @throws PathNotFoundException
     * @return boolean
     */
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
        
        $logText = '[' . date("Y-m-d H:i:s", Util::getNow()) . '] ' . implode(' ', $logTexts) ."\r\n";
        
        return error_log($logText, 3, $logPath);
    }
    
    /**
     * Overide __call magic method
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