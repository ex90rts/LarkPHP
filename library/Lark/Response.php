<?php
namespace Lark;

use Lark\Exception\PathNotFoundException;

class Response{
    /**
     * Current response charset, default value from Lark\Env::options
     * @var string
     */
    private $_charset;
    
    /**
     * Var for templete output
     */
    private $_data;
    
    /**
     * Debug info need to send with response
     */
    private $_debuginfo;
    
    /**
     * Construct function
     */
    public function __construct(){
        $this->_charset = Env::getOption('charset');
    }
    
    /**
     * Set any undefined property to $_data as an output data source
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value){
    	if (in_array($name, array('_charset', '_data', '_debuginfo'), true)){
    		trigger_error("can not set response property with reserved key {$name}", E_USER_WARNING);
    	}
    	
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
    			$this->setHeader('X-' . Env::$codename . '-Debug:' . $key . '|' . $plainValue);
    		}
    	}
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
     * Set Session Value
     * @param string $key
     * @param mixed $value
     */
    public function setSession($key, $value){
        Env::startSession();
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
    public function setCookie($name, $value = null, $expire = 0, $path = null, $domain = null, $secure = false, $httpOnly = false){
    	$expire = time() + $expire;
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
    public function setRawCookie($name, $value = null, $expire = 0, $path = null, $domain = null, $secure = false, $httpOnly = false){
    	$expire = time() + $expire;
    	setrawcookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
    }
    
    /**
     * Output data as plain text
     * @param string $text
     */
    public function text($text){
        $this->setHeader('Content-Type: text; charset=' . $this->_charset);
        $this->setHeader('Cache-Control: no-cache');
        $this->sendDebugInfo();
        echo $text;
    }
    
    /**
     * Output data as json string
     * @param array $data
     */
    public function json(array $data){
        $this->setHeader('Content-Type: text/json; charset=' . $this->_charset);
        $this->setHeader('Cache-Control: no-cache');
        $this->sendDebugInfo();
        echo json_encode($data);
    }
    
    /**
     * Output data as javascript
     * @param string $script
     */
    public function script($script){
        $this->setHeader('Content-Type: text/html; charset=' . $this->_charset);
        $this->setHeader('Cache-Control: no-cache');
        $this->sendDebugInfo();
        echo '<script type="text/javascript">';
        echo $script;
        echo '</script>';
    }
    
    /**
     * Convinient function for redirect location
     * @param string $target
     * @param string $url
     */
    public function redirect($target, $url=''){
        if ($target=='goback'){
            $this->script('history.go(-1);');
        }elseif ($target=='gotop'){
            $this->script('top.location.href="'.$url.'";');
        }elseif ($target=='goto'){
            $this->script('location.href="'.$url.'";');
        }
    }
    
    /**
     * Outout data using print_r for debugging
     * @param array $data
     */
    public function printr($data){
        $this->setHeader('Content-Type: text/html; charset=' . $this->_charset);
        $this->setHeader('Cache-Control: no-cache');
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
        $this->setHeader('Cache-Control: no-cache');
        $this->sendDebugInfo();
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
    }
    
    /**
     * Render $_data via HTML template
     * @param string $template
     */
    public function render($template){
    	$this->sendDebugInfo();
    	
        $project = Env::getOption('project');
        $projectPath = Env::getOption('projectPath');
        $templateFile = $projectPath . DIRECTORY_SEPARATOR . $project . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . $template;
        if (!file_exists($templateFile)){
            throw new PathNotFoundException(sprintf('path %s not found', $templateFile));
        }
        
        ob_start();
        require $templateFile;
        ob_flush();
    }
}