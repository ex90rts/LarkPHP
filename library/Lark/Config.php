<?php
namespace Lark;

use Lark\App;
use Lark\Exception\CloneNotAllowedException;

/**
 * 配置读取类
 * 
 * 用于框架和应用任意配置管理维护，配置根据App基本配置中的approot、configDir和platform
 * 项目来定位配置内容，并根据配置文件的名称读取到对应的配置数组中供随时使用。其中platform
 * 用于在不同的运行环境做出不同的配置，例如platform名称为test，则可以在configDir建立名
 * 为test的子目录，则test目录下的配置文件会替换configDir下面同名的配置。举例如下：
 * 
 * - config
 * - config/user.php
 * - config/mysql.php
 * - config/test/mysql.php
 * 
 * 当platform=test时上述配置将读取为array('user'=>array(config/user.php), 'mysql'=>array(config/test/mysql.php))
 * 
 * @property Config $_instance 单例实例存储变量
 * @property array $_config 配置缓存
 * @author jinhong
 *
 */
class Config{
    /**
     * 单例实例存储变量
     * @var Config
     */
    private static $_instance = null;
    
    /**
     * 配置缓存
     * @var Array
     */
    private $_config;
    
    /**
     * 构造方法，将根据基础配置读取全部配置文件到配置数组以备随时读取
     * 
     * @access private
     */
    private function __construct(){
    	$configPath = App::getOption('approot') . DIRECTORY_SEPARATOR . App::getOption('configDir');
    	
    	$platform = App::getOption('platform');
    	$platformConfigPath = $configPath . DIRECTORY_SEPARATOR . $platform;
    	
    	$configs = scandir($configPath);
    	$platformConfigs = array();
    	if (file_exists($platformConfigPath)){
    	    $platformConfigs = scandir($platformConfigPath);
    	}
    	
    	//read common configuration
    	foreach ($configs as $file){
    	    $configFile = $configPath . DIRECTORY_SEPARATOR . $file;
    	    if (!is_file($configFile)){
    	        continue;
    	    }
    	    
    	    $config = array();
    	    $nameParts = explode('.', $file, 2);
    	    if (isset($nameParts[1]) && $nameParts[1]=='php'){
    	        require_once $configFile;
    	        $this->_config[$nameParts[0]] = $config;
    	    }
    	}
    	//overwrite platform special configuration
    	foreach ($platformConfigs as $file){
    	    $configFile = $platformConfigPath . DIRECTORY_SEPARATOR . $file;
    	    if (!is_file($configFile)){
    	        continue;
    	    }
    	    
    	    $config = array();
    	    $nameParts = explode('.', $file, 2);
    	    if (isset($nameParts[1]) && $nameParts[1]=='php'){
    	        require_once $configFile;
    	        $this->_config[$nameParts[0]] = $config;
    	    }
    	}
    }
    
    /**
     * 单例模式返回类实例
     * 
     * @access public,static
     * @return Config
     */
    public static function getInstance(){
        if (!self::$_instance){
            $class = __CLASS__;
            self::$_instance = new $class();
        }
        return self::$_instance;
    }
    
    /**
     * 单例模式禁止调用clone方法
     * 
     * @throws Exception\CloneNotAllowedException
     */
    public function __clone(){
        throw new CloneNotAllowedException(sprintf('class name %s', __CLASS__));
    }
    
    /**
     * 读取任意已经读取到缓存中的配置项目的魔术方法
     * 
     * @param string $name 配置名称
     * @return mixed 存在配置则返回内容数组，否则返回NULL
     */
    public function __get($name){
        if (isset($this->_config[$name])){
            return $this->_config[$name];
        }else{
            return null;
        }
    }
    
    /**
     * 判断指定名称的配置项目是否存在的魔术方法
     * 
     * @param string $name
     * @return boolean 配置项目是否有定义
     */
    public function __isset($name){
        return isset($this->_config[$name]);
    }
}