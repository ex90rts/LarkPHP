<?php
namespace Alcedoo;

class Request{
	
	/**
	 * HTTP method defination
	 * 
	 */
	const METHOD_GET    = 'GET';
	const METHOD_POST   = 'POST';
    const METHOD_PUT    = 'PUT';
    const METHOD_DELETE = 'DELETE';
	
    private $_get;
    
    private $_post;
    
    private $_files;
    
    public $method;
    
    public $controller;
    
    public $action;
    
    public $ajax = false;
    
    /**
     * Construct function, assign $_GET, $_POST  and $_FILES data by default
     */ 
    public function __construct(){
        $this->_get = $_GET;
        $this->_post = $_POST;
        $this->_files = $_FILES;
        
        $mainPath = explode('?', $_SERVER['REQUEST_URI']);
        $pathParts = explode('/', trim($mainPath[0], '/'));
        
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->controller = !empty($pathParts[0]) ? $pathParts[0] : 'Index';
        $this->action = isset($pathParts[1]) ? $pathParts[1] : 'View';
        
        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        	$this->ajax = true;
        }
    }
    
    /**
     * Possible to get the front side value by read a instance attribte
     * @param string $name
     * @throws \Exception
     * @return mixed
     */
    public function __get($name){
        if (in_array($name, array('_get', '_post', '_cookie', '_session', '_server'))){
            throw new \Exception('not allowd to get reserved attributes');
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