<?php
namespace Lark;

use Lark\Exception\NoAutoloaderDefinedException;

/**
 * Global App Environment Manager
 * 
 * @author samoay
 *
 */
class App{
	/*Base envirament constants*/
	const START_DATETIME  = '2013-01-01 00:00:00';
	const START_TIMESTAMP = 1356969600;
	const ENV_DEVELOPMENT = 'dev';
	const ENV_PRODUCTION  = 'pro';
	
	/**
	 * The name of this framework
	 * @var string
	 */
    public static $codename = 'Lark';

    /**
     * Current version of this framework
     * @var string
     */
    public static $version = '0.0.1';

    /**
     * Flag if current is under cli mode
     * @var boolean
     */
    public static $cmdmode = false;
    
	/**
	 * Framework base configuration
	 * @var array
	 */
	private static $_options;

	/**
	 * Pool of singleton instance of common object like cache handler/logger
	 * @var array
	 */
	private static $_instancesPool;

	/**
	 * Remember if session already started
	 * @var boolean
	 */
	private static $_sessionStarted = false;
	
	/**
	 * URL mapper holder
	 * @var array
	 */
	private static $_urlmapper = array(
		array('/^(\/)?(?<controller>\w+)\/(?<action>\w+)\/?(?<id>\d+)?/'),
	);
	
	/**
	 * Record main workflow profile info
	 * @var array
	 */
	private static $_profileInfo = array();
	
	/**
	 * All executed database queries
	 * @var array
	 */
	private static $_dbQueries = array();
	
	/**
	 * Initialize function, init the framework options
	 * @param array $options
	 */
	public static function init(array $options=array()){
		self::$cmdmode = php_sapi_name()=='cli';
		
		//some default options to keep this framework without any outer options
		$upperCodename = strtoupper(self::$codename);
		$default = array(
			'approot'          => dirname(__FILE__).'/../../app',
			'environment'      => self::ENV_PRODUCTION,
			'profile'          => false,
			'namespace'        => 'Larktest',
			'platform'         => !empty($_SERVER["{$upperCodename}_PLATFORM"]) ? $_SERVER["{$upperCodename}_PLATFORM"] : 'foo',
			'libPath'          => dirname(__FILE__).'/../',
			'logDir'           => sys_get_temp_dir() . DIRECTORY_SEPARATOR . strtolower(self::$codename) . '_logs',
			'logTypes'         => array('action', 'error', 'repair', 'debug', 'exception'),
			'configDir'        => 'config',
			'timezone'         => 'Asia/Shanghai',
			'charset'          => 'UTF-8',
			'router'           => array('Lark\Router', 'routeController'),
			'autoloader'       => array(__CLASS__, 'defaultAutoloader'),
			'errorHandler'     => array(__CLASS__, 'defaultErrorHandler'),
			'shutdownFunction' => array(__CLASS__, 'defaultShutdownFunction'),
			'errorReporting'   => E_ALL & ~E_NOTICE,
			'sessionStart'     => true,
			'modelCache'       => true,
			'defController'    => 'index',
			'defAction'        => 'view',
			'errController'    => 'error',
			'errAction'        => 'error',
			'templateDir'      => 'template',
			'staticHost'       => '/',
		);

		$requestOptions = isset($_REQUEST['Lark']) ? $_REQUEST['Lark'] : array();
		foreach($options as $key=>$value){
		    $default[$key] = $value;
		    if (isset($requestOptions[$key])){
		        $default[$key] = $requestOptions[$key];
		    }
		}
		if ($default['environment'] == self::ENV_DEVELOPMENT){
			foreach ($requestOptions as $key=>$value){
				$default[$key] = $requestOptions[$key];
			}
		}
		
		error_reporting($default['errorReporting']);

		$default['approot'] = realpath($default['approot']);
		$default['libPath'] = realpath($default['libPath']);

		self::$_options = $default;

		set_error_handler(self::$_options['errorHandler'], $default['errorReporting']);
		register_shutdown_function(self::$_options['shutdownFunction']);
		spl_autoload_register(self::$_options['autoloader']);

		if (!empty(self::$_options['timezone'])){
		    date_default_timezone_set(self::$_options['timezone']);
		}
		if (self::$_options['sessionStart']){
			self::startSession();
		}
	}

	/**
	 * Return all options at the same time
	 * @return array
	 */
	public static function getOptions(){
		return self::$_options;
	}
	
	/**
	 * Return the value of a special option
	 * @param string $optionName
	 * @return NULL
	 */
	public static function getOption($optionName){
	    if (isset(self::$_options[$optionName])){
	        return self::$_options[$optionName];
	    }else{
	        return null;
	    }
	}
	
	/**
	 * Return profile info if profile is enabled, or false
	 */
	public static function getProfileInfo(){
		if (self::getOption('profile')){
			return self::$_profileInfo;
		}
		return false;
	}
	
	/**
	 * Save executed database queries
	 * @param string $sql
	 */
	public static function addDbQueries($sql){
		self::$_dbQueries[] = $sql;
	}
	
	/**
	 * Add application URL mapper rule
	 */
	public static function addUrlMapper($mappers){
		self::$_urlmapper = array_merge($mappers, self::$_urlmapper);
	}
	
	/**
	 * Get all application URL mapper rules
	 */
	public static function getUrlMapper(){
		return self::$_urlmapper;
	}

	/**
	 * Start Session method for ensure session will only be started once
	 */
	public static function startSession(){
	    if (!self::$_sessionStarted){
	        session_start();
	        self::$_sessionStarted = true;
	    }
	}

	/**
	 * Default autoloader for framework
	 * @param string $className
	 * @throws \Exception
	 */
	public static function defaultAutoloader($className){
	    $classParts = explode('\\', $className);
	    if (count($classParts)<2){
	        throw new NoAutoloaderDefinedException(sprintf('try to autoload class named %s', $className));
	    }

	    $baseNamespace = array_shift($classParts);
	    $subNamespace = array_shift($classParts);
	    if ($baseNamespace===self::$codename){
	        $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $className);
	        $filePath = self::getOption('libPath') . DIRECTORY_SEPARATOR . $classPath . '.php';
	    }elseif($baseNamespace===self::getOption('namespace')){
	        $classPath = implode('/', $classParts);
	        $filePath = self::getOption('approot') . DIRECTORY_SEPARATOR . strtolower($subNamespace) . DIRECTORY_SEPARATOR .$classPath . '.php';
	    }else{
	    	$classPath = str_replace('\\', DIRECTORY_SEPARATOR, $className);
	        $filePath = self::getOption('libPath') . DIRECTORY_SEPARATOR . $classPath . '.php';
	    }

	    if (file_exists($filePath)){
	        require $filePath;
	    }else{
	        throw new \Exception(sprintf('path %s not found', $filePath));
	    }
	}

	/**
	 * Default error handler for framework
	 */
	public static function defaultErrorHandler(){
		/*
	    echo "<pre>";
	    print_r(debug_backtrace());
	    echo "</pre>";
	    */
	}

	/**
	 * Default shutdown function for framework
	 */
	public static function defaultShutdownFunction(){
		/*
		$lastError = error_get_last();
		if ($lastError){
			echo "<pre>";
			print_r($lastError);
			echo "</pre>";
		}
		*/
	}
	
	/**
	 * Return the singleton instance of a common object from the instance pool
	 * @param string $className
	 * @return mixed
	 */
	public static function getInstance($className){
		$fullClassName = self::$codename . '\\' . $className;
	    if (empty(self::$_instancesPool[$fullClassName])){
	        $instance = $fullClassName::getInstance();
	        self::$_instancesPool[$fullClassName] = $instance;
	        return $instance;
	    }else{
	        return self::$_instancesPool[$fullClassName];
	    }
	}

	/**
	 * Execute the request
	 */
	public static function execute(){
		if (self::getOption('profile')){
			$startTime = microtime(true);
		}
		
		try{
	    	call_user_func(self::getOption('router'));
		}catch (\Exception $e){
			header('HTTP/1.1 500 Server Error');
			if (self::getOption('environment')==self::ENV_DEVELOPMENT){
				var_dump($e);
			}else{
				exit('Server Error');
			}
		}
	    
	    if (self::getOption('profile')){
	    	$endTime = microtime(true);
	    	self::$_profileInfo = array(
	    		'executeTime' => $endTime - $startTime,
	    		'memoryUsage' => memory_get_usage(),
	    		'memoryPeakUsage' => memory_get_peak_usage(),
	    		'executedQueries' => self::$_dbQueries,
	    	);
	    }
	}
}