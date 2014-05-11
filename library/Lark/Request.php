<?php
namespace Lark;

class Request{
	
	/**
	 * HTTP method defination
	 * 
	 */
	const METHOD_GET     = 'GET';
	const METHOD_POST    = 'POST';
    const METHOD_PUT     = 'PUT';
    const METHOD_DELETE  = 'DELETE';
    const METHOD_OPTIONS = 'OPTIONS';
	
    private $_get;
    
    private $_post;
    
    public $method;
    
    public $controller;
    
    public $action;
    
    public $ip = null;
    
    public $ajax = false;
    
    public $cmdmode = false;
    
    /**
     * Construct method, do these following things: 
     *     <ul>
     * 	       <li>assign $_GET, $_POST data to instance properties</li>
     *         <li>check the running mode is CLI or HTTP</li> 
     *         <li>check request method is GET or POST/PUT/DELETE</li>
     *         <li>assign RAW post data as post property if needed</li>
     *         <li>check if is requested by ajax</li>
     *     </ul>
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
        $this->_get[$name] = $value;
    }
    
    /**
     * Possible to get the front side value by read a instance attribte
     * 
     * @deprecated please using ->get() or ->post() method instead
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
        return false;
    }
    
    /**
     * Read $_GET variable value after sanitized
     *
     * @param string $name
     * @param string $default Only support string like value
     * @return mixed
     */
    public function get($name='', $default=''){
    	$value = $this->_get;
    	if ($name!=''){
    		$value = '';
    		if (isset($this->_get[$name])){
    			$value = $this->_get[$name];
    		}
    	}
    	 
    	$value = Util::inputFilter($value);
    	if (!$value && $default){
    		$value = $default;
    	}
    	
    	return $value;
    }
    
    /**
     * Read $_POST variable value after sanitized
     *
     * @param string $name
     * @param string $default Only support string like value
     * @return mixed
     */
    public function post($name='', $default=''){
    	$value = $this->_post;
    	if ($name!=''){
    		$value = '';
    		if (isset($this->_post[$name])){
    			$value = $this->_post[$name];
    		}
    	}
    	 
    	$value = Util::inputFilter($value);
    	if ($value==='' && $default!==''){
    		$value = $default;
    	}
    	
    	return $value;
    }
    
    /**
     * Read $_GET or $_POST variable value after sanitized depend on current request method
     * 
     * @param string $name
     * @param string $default Only support string like value
     * @return mixed
     */
    public function fetch($name='', $default=''){
    	if ($name==''){
    		$get = $this->get();
    		$post = $this->post();
    		return array(
    			'GET' => $get,
    			'POST' => $post,
    		);
    	}
    	
    	if (isset($this->_get[$name])){
    		return $this->get($name, $default);
    	}elseif (isset($this->_post[$name])){
    		return $this->post($name, $default);
    	}
    	
    	return null;
    }
    
    /**
     * Read $_FIELS variable value
     *
     * @param string $name
     * @return mixed
     */
    public function files($name){
    	$value = false;
    	if (isset($_FILES[$name])){
    		$value = $_FILES[$name];
    	}
    	
    	return $value;
    }
    
    /**
     * Read Server Env Value
     *
     * @param string $key
     * @return mixed
     */
    public function getServer($key=null){
        if (is_string($key)){
            return isset($_SERVER[$key]) ? Util::inputFilter($_SERVER[$key]) : null;
        }else{
            return Util::inputFilter($_SERVER);
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
            return isset($_COOKIE[$key]) ? Util::inputFilter($_COOKIE[$key]) : null;
        }else{
            return Util::inputFilter($_COOKIE);
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
    	 	return Util::inputFilter($_SERVER[$headerName]);
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
    		$this->ip = Util::inputFilter($_SERVER['REMOTE_ADDR']);
    	}
    	 
    	return $this->ip;
    }
    
    /**
     * Get referer URL
     */
    public function getReferer(){
    	$referer = '/';
    	if (isset($_SERVER['HTTP_REFERER'])){
    		$referer = Util::inputFilter($_SERVER['HTTP_REFERER']);
    	}
    	
    	return $referer;
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
            return Util::inputFilter($_SESSION);
        }
    }
}