<?php
namespace Lark;

use Lark\Exception\PathNotFoundException;

class Response{
	/**
	 * Constants for action result
	 * @var string
	 */
	const RET_SUCC = 'succ';
	const RET_FAIL = 'fail';
	
    /**
     * Current response charset, default value from Lark\App::options
     * @var string
     */
    private $_charset;
    
    /**
     * Action result
     */
    private $_ret = self::RET_SUCC;
    
    /**
     * Action errors
     * 
     * @var array
     */
    private $_errors = array();
    
    /**
     * Var for templete output
     */
    private $_data = array();
    
    /**
     * Global variables for template out of action
     * @var array
     */
    private $_global = array();
    
    /**
     * Debug info need to send with response
     */
    private $_debuginfo;
    
    /**
     * Current controller name passed by Request
     * @var string
     */
    private $_controller;
    
    /**
     * Current action name passed by Request
     * @var string
     */
    private $_action;
    
    /**
     * Current visitor's language locale
     * @var string
     */
    private $_locale = 'en-US';
    
    /**
     * The csrf token for next request
     * @var unknown
     */
    private $_csrf_token = '';
    
    /**
     * Responder for localize the languages
     * @var mixed
     */
    private $_localizer = false;
    
    /**
     * Construct function
     */
    public function __construct(){
        $this->_charset = App::getOption('charset');
    }
    
    /**
     * Set any undefined property to $_data as an output data source
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value){
        $this->_data[$name] = $value;
    }
    
    /**
     * Get any undefined class var value from $_data
     * @param string $name
     * @return mixed
     */
    public function __get($name){
        if ($name=='charset'){
            return $this->_charset;
        }
        if ($name=='csrf_token'){
        	return $this->_csrf_token;
        }
        if (isset($this->_data[$name])){
            return $this->_data[$name];
        }
        return null;
    }
    
    /**
     * Push debug info into $_debuginfo
     */
    public function addDebugInfo($key, $value){
    	$this->_debuginfo[$key] = $value;
    }
    
    /**
     * Send out debuginfo with response via HTTP header
     */
    private function sendDebugInfo(){
    	if (!empty($this->_debuginfo)){
    		foreach ($this->_debuginfo as $key=>$value){
    			$plainValue = $value;
    			if (is_array($value)){
    				$plainValue = json_encode($value);
    			}
    			if (is_object($value)){
    				$plainValue = 'Object('. get_class($value) .')';
    			}
    			if (!is_string($plainValue) && !is_numeric($value) && !is_bool($value)){
    				$plainValue = '-Unknown Variable-';
    			}
    			$this->setHeader('X-' . App::$codename . '-Debug:' . $key . '|' . $plainValue);
    		}
    	}
    }
    
    /**
     * Create and return current response's csrf protect token
     * @return string csrf token
     */
    public function getCsrfToken(){
    	if (App::$sessionId && $this->_csrf_token==''){
    		$recreate = true;
    		if (isset($_SESSION['csrf_token'])){
    			$csrf_token = $_SESSION['csrf_token'];
    			if (substr($csrf_token, 0, strpos($csrf_token, '|')) == App::$sessionId){
    				$recreate = false;
    			}
    		}
    		 
    		if ($recreate){
    			$csrf_token = App::$sessionId . '|' . substr(hash('sha256', App::$sessionId . Util::randomString()), 0, 32);
    			$_SESSION['csrf_token'] = $csrf_token;
    		}
    		 
    		$this->_csrf_token = base64_encode($csrf_token);
    	}
    	
    	return $this->_csrf_token;
    }
    
    /**
     * Replace the default charset of current response if needed
     * This function must be invoked before any output function
     * @param string $charset
     */
    public function setCharset($charset){
        $this->_charset = $charset;
    }
    
    /**
     * Pass current controller name from Request
     * @param string $controller
     */
    public function setController($controller){
    	$this->_controller = $controller;
    }
    
    /**
     * Return current controller name
     * @return string
     */
    public function getController(){
    	return $this->_controller;
    }
    
    /**
     * Pass current action name from Request
     * @param string $controller
     */
    public function setAction($action){
    	$this->_action = $action;
    }
    
    /**
     * Return current action name
     * @return string
     */
    public function getAction(){
    	return $this->_action;
    }
    
    /**
     * Set current locale
     *
     * @param string $locale
     */
    public function setLocale($locale){
    	$this->_locale = $locale;
    }
    
    /**
     * Set current localizer
     *
     * @param Lark\Localizer $localizer
     */
    public function setLocalizer(Localizer $localizer){
    	$this->_localizer = $localizer;
    }
    
    /**
     * Set action response result as success
     * 
     * @param string $ret
     */
    public function setRetSucc(){
    	$this->_ret = self::RET_SUCC;
    }
    
    /**
     * Set action response result as failed
     *
     * @param string $ret
     */
    public function setRetFail(){
    	$this->_ret = self::RET_FAIL;
    }
    
    /**
     * Set action response errors
     * 
     * @param array $errors
     */
    public function setErrors($errors){
    	if (is_array($errors)){
    		foreach ($errors as $error){
    			$this->_errors[] = $error->toArray();
    		}
    	}elseif ($errors instanceof Error){
    		$this->_errors[] = $errors->toArray();
    	}
    }
    
    /**
     * Set a raw HTTP header
     * 
     * @param string $string
     * @param boolean $replace
     * @param int $http_response_code
     */
    public function setHeader($string, $replace = true, $http_response_code = null){
    	if (!headers_sent($filename, $linenum)){
    		header($string, $replace, $http_response_code);
    	}else{
    		trigger_error("headers already sent in file $filename on line $linenum", E_USER_WARNING);
    	}
    }
    
    /**
     * Disable browser side cache
     */
    public function noBrowserCache(){
    	if (!headers_sent($filename, $linenum)){
	    	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	    	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	    	header("Cache-Control: no-store, no-cache, must-revalidate");
	    	header("Cache-Control: post-check=0, pre-check=0", false);
	    	header("Pragma: no-cache");
    	}else{
    		trigger_error("headers already sent in file $filename on line $linenum", E_USER_WARNING);
    	}
    }
    
    /**
     * Set Session Value
     * @param string $key
     * @param mixed $value
     */
    public function setSession($key, $value){
        $_SESSION[$key] = $value;
    }
    
    /**
     * Set or delete a cookie
     * 
     * @name string cookie name
     * @value string cookie value
     * @expire int cookie live time start from set in seconds, could be negative if you want to delete a cookie
     * @path string cookie path
     * @domain string cookie domain
     * @secure boolean is cookie under https
     * @httpOnly boolean is cookie http only
     */
    public function setCookie($name, $value = '', $expire = 0, $path = '/', $domain = null, $secure = false, $httpOnly = false){
    	if ($expire != 0){
    		$expire = Util::getNow() + $expire;
    	}
    	setcookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
    }
    
    /**
     * Set or delete a raw content cookie
     *
     * @name string cookie name
     * @value string cookie value
     * @expire int cookie live time start from set in seconds, could be negative if you want to delete a cookie
     * @path string cookie path
     * @domain string cookie domain
     * @secure boolean is cookie under https
     * @httpOnly boolean is cookie http only
     */
    public function setRawCookie($name, $value = '', $expire = 0, $path = '/', $domain = null, $secure = false, $httpOnly = false){
    	if ($expire != 0){
    		$expire = Util::getNow() + $expire;
    	}
    	setrawcookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
    }
    
    /**
     * Assign global variables
     */
    public function assignGlobal($name, $value){
    	$this->_global[$name] = $value;
    }
    
    /**
     * Assign data to response
     * 
     * @param string $name
     * @param mixed $value
     */
    public function assign($name, $value){
    	$this->_data[$name] = $value;
    }
    
    /**
     * Assign multi data to response the same time
     * 
     * @param array $data
     */
    public function assignData(array $data){
    	foreach ($data as $k=>$v){
    		$this->_data[$k] = $v;
    	}
    }
    
    /**
     * Output data as plain text
     * @param string $text
     */
    public function text($text, $noheader=false){
    	if ($noheader==false){
        	$this->setHeader('Content-Type: text; charset=' . $this->_charset);
        	$this->noBrowserCache();
        	$this->sendDebugInfo();
    	}
        echo $text;
    }
    
    /**
     * Output data as json string
     * @param array $data
     */
    public function json(array $data=array()){
        $this->setHeader('Content-Type: text/json; charset=' . $this->_charset);
        if ($this->_ret == 'succ'){
        	$this->setHeader('HTTP/1.1 200 Status OK');
        }else{
        	$this->setHeader('HTTP/1.1 400 Logic Error');
        }
        $this->noBrowserCache();
        $this->sendDebugInfo();
        if (empty($data)){
        	if ($this->_ret == 'succ'){
	        	$buffer = array(
	        		'ret' => $this->_ret,
	        		'data' => $this->_data,
	        	);
        	}else{
        		$buffer = array(
        			'ret' => $this->_ret,
        			'err' => $this->_errors,
        			'data' => $this->_data,
        		);
        	}
        	echo json_encode($buffer);
        }else{
        	echo json_encode($data);
        }
    }
    
    /**
     * Convinient function for redirect location
     * @param string $target
     * @param string $url
     */
    public function redirect($target, $url=''){
    	if ($target=='goback'){
    		$this->setHeader("Location: {$_SERVER['HTTP_REFERER']}");
    	}elseif ($target=='goto'){
    		$this->setHeader("Location: {$url}");
    	}elseif ($target=='gotop'){
    		$this->script('top.location.href="'.$url.'";');
    	}
    }
    
    /**
     * Output data as javascript
     * @param string $script
     */
    public function script($script){
        $this->setHeader('Content-Type: text/html; charset=' . $this->_charset);
        $this->noBrowserCache();
        $this->sendDebugInfo();
        echo "\n<script>\n{$script}\n</script>\n";
    }
    
    /**
     * Outout data using print_r for debugging
     * @param array $data
     */
    public function printr($data){
        $this->setHeader('Content-Type: text/html; charset=' . $this->_charset);
        $this->noBrowserCache();
        $this->sendDebugInfo();
        echo '<pre>';
        if (is_array($data)){
        	print_r($data);
        }else{
            var_dump($data);
        }
        echo '</pre>';
    }
    
    /**
     * Outout data using var_dump for debugging
     * @param mixed $data
     */
    public function vardump($data){
        $this->setHeader('Content-Type: text/html; charset=' . $this->_charset);
        $this->noBrowserCache();
        $this->sendDebugInfo();
        var_dump($data);
    }
    
    /**
     * Return html content after rendered
     * 
     * @param string $template
     * @throws PathNotFoundException
     * @return string
     */
    public function render($template){
    	$template = new Template($template);
    	$template->setController($this->_controller);
    	$template->setAction($this->_action);
    	$template->setLocale($this->_locale);
    	$template->setErrors($this->_errors);
    
        if ($this->_localizer){
        	$template->setLocalizer($this->_localizer);
        }
        
        $this->assignGlobal('csrf_token', $this->getCsrfToken());
        
        $template->assignGlobal($this->_global);
    	$template->batchAssign($this->_data);
    	return $template->render();
    }
    
    /**
     * Display rendered HTML template
     * @param string $template
     */
    public function display($template){
    	$this->sendDebugInfo();
    	
        $template = new Template($template);
        $template->setController($this->_controller);
        $template->setAction($this->_action);
        $template->setLocale($this->_locale);
        $template->setErrors($this->_errors);
        
        if ($this->_localizer){
        	$template->setLocalizer($this->_localizer);
        }
        
        $this->assignGlobal('csrf_token', $this->getCsrfToken());
        
        $template->assignGlobal($this->_global);
    	$template->batchAssign($this->_data);
    	$template->display();
    }
}