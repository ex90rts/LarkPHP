<?php
namespace Flexper;

use Flexper\Exception\CloneNotAllowedException;
use Flexper\Exception\PathNotFoundException;

class Response{
    /**
     * Var for holding singleton instance of Flexper\Response
     * @var Flexper\Response
     */
    private static $_instance = null;
    
    /**
     * Current response charset, default value from Flexper\Env::options
     * @var string
     */
    private $_charset;
    
    /**
     * Var for templete output
     */
    private $_data;
    
    /**
     * Construct function
     */
    private function __construct(){
        $this->_charset = Env::getOption('charset');
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
     * Set any undefined class var to $_data as an output data source
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
        if (isset($this->_data[$name])){
            return $this->_data[$name];
        }
        return null;
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
     * Set Session Value
     * @param string $key
     * @param mixed $value
     */
    public function setSession($key, $value){
        Env::startSession();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Output data as plain text
     * @param string $text
     */
    public function text($text){
        header('Content-Type: text; charset=' . $this->_charset);
        header('Cache-Control: no-cache');
        echo $text;
    }
    
    /**
     * Output data as json string
     * @param array $data
     */
    public function json(array $data){
        header('Content-Type: text/json; charset=' . $this->_charset);
        header('Cache-Control: no-cache');
        echo json_encode($data);
    }
    
    /**
     * Output data as javascript
     * @param string $script
     */
    public function script($script){
        header('Content-Type: text/html; charset=' . $this->_charset);
        header('Cache-Control: no-cache');
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
        header('Content-Type: text/html; charset=' . $this->_charset);
        header('Cache-Control: no-cache');
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
        header('Content-Type: text/html; charset=' . $this->_charset);
        header('Cache-Control: no-cache');
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
    }
    
    /**
     * Output $_data via template file
     * @param string $template
     */
    public function template($template){
        $project = Env::getOption('project');
        $projectPath = Env::getOption('projectPath');
        $templateFile = $projectPath . DIRECTORY_SEPARATOR . $project . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . $template;
        if (!file_exists($templateFile)){
            throw new PathNotFoundException(sprintf('path %s not found', $templateFile));
        }
        
        require_once $templateFile;
    }
}