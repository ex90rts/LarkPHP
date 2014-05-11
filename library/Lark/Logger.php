<?php
namespace Lark;

use Lark\Util;
use Lark\Exception\CloneNotAllowedException;
use Lark\Exception\PathNotFoundException;
use Lark\Exception\WrongParamException;
use Lark\Exception\WrongLogTypeException;

/**
 * 日志处理类
 * 
 * 用于记录文本日志
 * 
 * @property Logger $_instance 单例实例
 * @property string $_logDir 日志保存目录
 * @property array $_logContent 日志内容缓存
 * @property array $_logTypes 日志类型
 * @property string $_logDate 日期字符串缓存
 * @author samoay
 *
 */
class Logger{
	/**
	 * @var string 空行内容
	 */
	const EMPTY_LINE = 'EMPTY_LINE';
	
	/**
	 * @access private
	 * @var Logger 单例模式实例
	 */
    private static $_instance;
    
    /**
     * @access private
     * @var string 日志保存目录
     */
    private $_logDir;
    
    /**
     * @access private
     * @var Array 日志内容缓存
     */
    private $_logContent;
    
    /**
     * @access private
     * @var Array 日志类型
     */
    private $_logTypes;
    
    /**
     * @access private
     * @var string 日期字符串缓存，用于组成日志文件名
     */
    private $_logDate;
    
    /**
     * 构造方法，读取配置并初始化日志基础信息
     * 
     * @throws PathNotFoundException 定义的日志保存目录不存在时抛出
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
     * 单例模式返回Logger实例
     * 
     * @access public, static
     * @return Logger
     */
    public static function getInstance(){
        if (!self::$_instance){
            $class = __CLASS__;
            self::$_instance = new $class();
        }
        return self::$_instance;
    }
    
    /**
     * 单例模式禁止实例被clone
     * 
     * @throws CloneNotAllowedException
     */
    public function __clone(){
        throw new CloneNotAllowedException(sprintf('class name %s', __CLASS__));
    }
    
    /**
     * 向已有的类型附加日志内容，这样在同一个进程中对同一种日志类型可以减少文件写入次数
     * 
     * @param string $type 日志类型
     * @param string $content 附加的日志内容
     */
    public function append($type, $content){
        if (!is_array($content)){
            $content = array('info'=>$content);
        }
        
        $current = $content;
        if (isset($this->_logContent[$type])){
            $former = $this->_logContent[$type];
            $current = array_merge($former, $content);
        }
        
        $this->_logContent[$type] = $current;
    }
    
    /**
     * 写入日志
     * 
     * @param string $type 日志类型
     * @param string $content 日志内容
     * @throws PathNotFoundException 记录的日志类型子目录不存在时抛出
     * @return boolean 日志写入是否成功
     */
    public function log($type, $content){
        $this->append($type, $content);
        
        $logTypeDir = $this->_logDir . DIRECTORY_SEPARATOR . $type;
        if (!is_dir($logTypeDir) && !mkdir($logTypeDir)){
            throw new PathNotFoundException(sprintf('path is %s', $logTypeDir));
        }
        
        $logPath = $logTypeDir . DIRECTORY_SEPARATOR . $this->_logDate . '.log';
        
        //just log an empty line
        if ($content==self::EMPTY_LINE){
        	error_log(PHP_EOL, 3, $logPath);
        	return;
        }
        
        $contents = $this->_logContent[$type];
        
        $logTexts = array();
        foreach ($contents as $key=>$value){
            if (!is_scalar($value)){
            	if (is_resource($value)){
            		$value = "Resource type ". get_resource_type($value) .", ". strval($value);
            	}else{
                	$value = serialize($value);
            	}
            }
            $logTexts[] = "[{$key}:{$value}]";
        }
        
        $this->_logContent[$type] = array();
        
        $logText = '[' . date("Y-m-d H:i:s", Util::getNow()) . '] ' . implode(' ', $logTexts) . PHP_EOL;
        
        return error_log($logText, 3, $logPath);
    }
    
    /**
     * 不同日志类型日志写入魔术方法，和App的基础配置logTypes项目关联
     * 
     * @see App
     * @param string $name 方法名
     * @param array $arguments 参数
     * @throws Exception\WrongParamException 参数错误异常，解析的方法名对应的日志类型未定义时抛出
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