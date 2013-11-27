<?php
namespace Alcedoo;

use Alcedoo\Env;
use Alcedoo\Exception\CloneNotAllowedException;

class Config{
    /**
     * Instance var for singleton instance
     * @var Object
     */
    private static $_instance = null;
    
    /**
     * Private var for all configuration
     * @var Array
     */
    private $_config;
    
    /**
     * Construct function, read all configuration by default
     */
    private function __construct(){
    	$projectPath = Env::getOption('projectPath');
    	$project = Env::getOption('project');
    	$configDir = Env::getOption('configDir');
    	$configPath = $projectPath . DIRECTORY_SEPARATOR . $project . DIRECTORY_SEPARATOR . $configDir;
    	
    	$platform = Env::getOption('platform');
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
     * Method to return the singleton instance
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
     * @throws \Exception
     */
    public function __clone(){
        throw new CloneNotAllowedException(sprintf('class name %s', __CLASS__));
    }
    
    /**
     * Read any config bundle if exists
     * @param string $name
     * @return mixed/NULL
     */
    public function __get($name){
        if (isset($this->_config[$name])){
            return $this->_config[$name];
        }else{
            return null;
        }
    }
    
    public function __isset($name){
        return isset($this->_config[$name]);
    }
}