<?php
namespace Alcedoo;

use Alcedoo\Exception\CloneNotAllowedException;

class Request{
    
    private static $_instance = null;
    
    private $_get;
    
    private $_post;
    
    private $_files;
    
    /**
     * Construct function, parse $_GET, $_POST  and $_FILES data by default
     */
    private function __construct(){
        $this->_get = $_GET;
        $this->_post = $_POST;
        $this->_files = $_FILES;
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
     * Possible to get the front side value by read a instance attribte
     * @param string $name
     * @throws \Exception
     * @return mixed
     */
    public function __get($name){
        if (in_array($name, array('_instance', '_get', '_post', '_cookie', '_session', '_server'))){
            throw new \Exception('not allowd to get resolved attributes');
        }
        if (isset($this->_get[$name])){
            return $this->_get[$name];
        }
        if (isset($this->_post[$name])){
            return $this->_post[$name];
        }
        if (isset($this->_files[$name])){
            return $this->files[$name];
        }
        return null;
    }
    
    /**
     * Override method to check a var if setted in GET/POST array
     * 
     * @param String $name
     * @return boolean
     */
    public function __isset($name){
        if (isset($this->_get[$name])){
            return true;
        }
        if (isset($this->_post[$name])){
            return true;
        }
        if (isset($this->_files[$name])){
            return true;
        }
        return false;
    }
    
    /**
     * Read Server Env Value
     *
     * @param string $key
     * @return mixed
     */
    public function getServer($key=null){
        if (is_string($key)){
            return $_SERVER[$key];
        }else{
            return $_SERVER;
        }
    }
    
    /**
     * Read Session Value
     *
     * @param string $key
     * @return mixed
     */
    public function getCookie($key=null){
        if (is_string($key)){
            return $_COOKIE[$key];
        }else{
            return $_COOKIE;
        }
    }
    
    /**
     * Read Session Value
     * 
     * @param string $key
     * @return mixed
     */
    public function getSession($key=null){
        Env::startSession();
        if (is_string($key)){
            return $_SESSION[$key];
        }else{
            return $_SESSION;
        }
    }
}