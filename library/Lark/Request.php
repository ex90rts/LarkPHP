<?php
namespace Lark;

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
    
    //public $id = 0;
    
    public $ip = null;
    
    public $ajax = false;
    
    public $cmdmode = false;
    
    /**
     * Construct function, assign $_GET, $_POST  and $_FILES data by default
     */ 
    public function __construct(){
    	$this->cmdmode = App::$cmdmode;
    	
    	if ($this->cmdmode){
    		$this->method = 'cli';
    	}else{
    		$this->method = $_SERVER["REQUEST_METHOD"];
    		
    		$this->_get = $_GET;
    		
	    	if ($this->method == self::METHOD_POST){
	    		if ($_SERVER['HTTP_CONTENT_TYPE']=='json' || $_SERVER['HTTP_CONTENT_TYPE']=='application/json'){
	    			$postdata = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : file_get_contents("php://input");
	    			if ($postdata){
	    				$this->_post = @json_decode($postdata, true);
	    			}
	    		}else{
	    			$this->_post = $_POST;
	    		}
	    	}
	    	
	    	$this->_files = $_FILES;
    	}
  	
    	if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    		$this->ajax = true;
    	}
    }
    
    /**
     * Set any undefined class var value to $_get
     * @param string $name
     * @return mixed
     */
	public function __set($name, $value){
    	if (in_array($name, array('_get', '_post', '_files', 'method', 'controller', 'action', 'ip', 'ajax', 'cmdmode'), true)){
    		trigger_error("can not set response property with reserved key {$name}", E_USER_WARNING);
    	}
    	
        $this->_get[$name] = $value;
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
            return $this->_files[$name];
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
            return isset($_SERVER[$key]) ? $_SERVER[$key] : null;
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
            return isset($_COOKIE[$key]) ? $_COOKIE[$key] : null;
        }else{
            return $_COOKIE;
        }
    }
    
    /**
     * Get request HTTP header
     * 
     * @param string $name
     */
    public function getHeader($name){
    	 $headerName = "HTTP_{$name}";
    	 if (isset($_SERVER[$headerName])){
    	 	return $_SERVER[$headerName];
    	 }else{
    	 	return false;
    	 }
    }
    
    /**
     * Get request's client IP
     * 
     * @return string ipv4 address
     */
    public function getIP(){
    	if (!$this->ip){
    		$this->ip = $_SERVER['REMOTE_ADDR'];
    	}
    	 
    	return $this->ip;
    }
    
    /**
     * Read Session Value
     * 
     * @param string $key
     * @return mixed
     */
    public function getSession($key=null){
        if (is_string($key)){
            return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
        }else{
            return $_SESSION;
        }
    }
    
    /**
     * Return the whole query string data
     */
    public function getQuery(){
    	return $this->_get;
    }
    
    /**
     * Return the whole post data
     */
    public function getPost(){
    	return $this->_post;
    }
}