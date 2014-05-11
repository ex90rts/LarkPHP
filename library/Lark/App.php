<?php
namespace Lark;

/**
 * 应用启动类
 * 
 * 包含应用的环境变量初始化、Session初始化、应用调试、应用性能Profile、默认类
 * 加载器定义、默认错误和异常处理定义、全局单例对象管理、应用启动等功能处理。本类
 * 完全是一个静态类，不需要初始化实例，应用中可随时使用App::method()来调用
 * 
 * @author samoay
 * @property string $codename 框架的开发代号，同时也是根命名空间
 * @property string $version 框架版本号，升级兼容判断使用
 * @property boolean $cmdmode 当前是否运行在cli命令行模式下的标记
 * @property string $sessionId 当前会话的ID，用作登录判断、API token的生成等
 * @property array $_options 应用基础配置
 * @property array $_instancePool 单例库，用于在脚本内缓存单例类实例
 * @property array $_urlMapper 请求地址与控制器之间的路由对照表
 * @property array $_profileInfo 存储页面性能分析的相关数据
 * @property array $_dbQueries 当前进程执行过的数据库查询 
 * @see Lark\Router
 *
 */
class App{
	/**
	 * 框架纪元开始时间ISO格式，主要用于减小Unix Timestamp的数值
	 * @var string
	 */
	const START_DATETIME  = '2013-01-01 00:00:00';
	
	/**
	 * 框架纪元开始时间的时间戳格式，主要用于减小Unix Timestamp的数值
	 * @var int
	 */
	const START_TIMESTAMP = 1356969600;
	
	/**
	 * 当前运行环境之开发环境，不同的开发环境在日志和错误处理上有区别
	 * @var string
	 */
	const ENV_DEVELOPMENT = 'dev';
	
	/**
	 * 当前运行环境之线上环境，不同的开发环境在日志和错误处理上有区别
	 * @var string
	 */
	const ENV_PRODUCTION  = 'pro';
	
	/**
	 * 框架的开发代号，同时也是根命名空间
	 * 
	 * @access public,static
	 * @var string
	 */
    public static $codename = 'Lark';

    /**
     * 框架版本号，升级兼容判断使用
     * 
     * @access public,static
     * @var string
     */
    public static $version = '0.0.1';

    /**
     * 当前是否运行在cli命令行模式下的标记
     * 
     * @access public,static
     * @var boolean
     */
    public static $cmdmode = false;
    
    /**
     * 当前会话的ID，用作登录判断、API token的生成等
     * 
     * @access public,static
     * @var string
     */
    public static $sessionId = '';
    
	/**
	 * 应用基础配置
	 * 
	 * @access private,static
	 * @var array
	 */
	private static $_options;

	/**
	 * 单例库，用于在脚本内缓存单例类实例
	 * 
	 * @access private,static
	 * @var array
	 */
	private static $_instancesPool;

	/**
	 * 请求地址与控制器之间的路由对照表，默认规则为 controller/action/id 的模式，即
	 * 控制器->动作->主键，在应用初始化时可以使用self::addUrlMapper来自定义，自定义
	 * 的规则总在们默认规则之前。匹配规则时按照从前往后的方向匹配，一旦匹配到即不再继续
	 * @var array
	 */
	private static $_urlmapper = array(
		array('/^(\/)?(?<controller>\w+)\/(?<action>\w+)\/?(?<id>\d+)?/'),
	);
	
	/**
	 * 存储页面性能分析的相关数据
	 * 
	 * @access private,static
	 * @var array
	 */
	private static $_profileInfo = array();
	
	/**
	 * 当前进程执行过的数据库查询
	 * @var array
	 */
	private static $_dbQueries = array();
	
	/**
	 * 应用启动并传入应用基础配置，调用此方法后应用即可开始接受请求并进行处理。方法中$default数组规定了
	 * 框架可以零配置启动运行的默认配置，然后才会使用传入的参数$options中的配置条目进行替换。同时在开发
	 * 还支持环境下还支持在请求的参数中加入名为Lark的参数数组来替换配置进行调试
	 * 
	 * ## 应用基础配置说明
	 * 
	 * - approot 应用根目录，此目录必须包含一个可供访问的默认页，默认页中进行此项初始化
	 * - environment 当前是开发环境还是线上环境
	 * - profile 是否开启性能监测
	 * - namespace 应用默认命名空间（不是框架的，框架由静态变量$codename决定）
	 * - platform 当前平台，可通过环境变量LARK_PLATFORM来定义，不同的平台可以有不同的配置
	 * - libPath 框架类库根路径，本框架代码和第三方框架verndor目录均在此路径下
	 * - logDir 应用日志的存储路径
	 * - logTypes 支持的日志类型定义，每个类型的日志文件将存储在logDir下面对应的子目录中
	 * - configDir 应用配置文件放置的目录
	 * - timezone 应用位于的时区
	 * - charset 应用的输出文件编码
	 * - router 默认的路由分析类
	 * - autoloader 默认的PHP类自动加载器
	 * - errorHandler 默认的错误处理方法
	 * - shutdownFunction 默认的推出异常处理方法
	 * - errorReporting 错误报告级别
	 * - sessionStart 应用启动是是否自动初始化Session
	 * - modelCache 是否开启model层主键cache
	 * - defController 从urlMapper未对照出任何控制器的情况下使用的默认控制器
	 * - defAction 从urlMapper未对照出任何动作情况下执行的默认动作
	 * - errController 默认的逻辑错误处理控制器
	 * - errAction 默认的逻辑错误处理动作
	 * - templateDir 模板文件目录
	 * - staticHost 静态资源文件HOST，默认使用当前域名，路径从根目录算起，可通过环境变量LARK_STATIC自定义
	 * 
	 * @access public,static
	 * @param array $options
	 * @return NULL
	 */
	public static function init(array $options=array()){
		self::$cmdmode = php_sapi_name()=='cli';
		
		//some default options to keep this framework without any outer options
		$upperCodename = strtoupper(self::$codename);
		$default = array(
			'approot'          => dirname(__FILE__).'/../../app',
			'environment'      => self::ENV_PRODUCTION,
			'profile'          => false,
			'namespace'        => 'Foo',
			'platform'         => !empty($_SERVER["{$upperCodename}_PLATFORM"]) ? $_SERVER["{$upperCodename}_PLATFORM"] : 'foo',
			'libPath'          => dirname(__FILE__).'/../',
			'logDir'           => sys_get_temp_dir() . DIRECTORY_SEPARATOR . strtolower(self::$codename) . '_logs',
			'logTypes'         => array('action', 'error', 'repair', 'debug', 'exception', 'profile'),
			'configDir'        => 'config',
			'timezone'         => 'Asia/Shanghai',
			'charset'          => 'UTF-8',
			'router'           => array('Lark\Router', 'routeController'),
			'autoloader'       => array(__CLASS__, 'defaultAutoloader'),
			'errorHandler'     => array(__CLASS__, 'defaultErrorHandler'),
			'shutdownFunction' => array(__CLASS__, 'defaultShutdownFunction'),
			'errorReporting'   => E_ALL ^ E_NOTICE,
			'sessionStart'     => true,
			'modelCache'       => true,
			'defController'    => 'index',
			'defAction'        => 'view',
			'errController'    => 'error',
			'errAction'        => 'error',
			'templateDir'      => 'template',
			'staticHost'       => !empty($_SERVER["{$upperCodename}_STATIC"]) ? $_SERVER["{$upperCodename}_STATIC"] : '/',
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
	 * 读取应用完整配置
	 * 
	 * @access public,static
	 * @return array
	 */
	public static function getOptions(){
		return self::$_options;
	}
	
	/**
	 * 读取应用某项配置值
	 * 
	 * @access public,static
	 * @param string $optionName 配置项目KEY
	 * @return mixed 如果找到了配置项则返回配置项值，否则返回NULL
	 */
	public static function getOption($optionName){
	    if (isset(self::$_options[$optionName])){
	        return self::$_options[$optionName];
	    }else{
	        return null;
	    }
	}
	
	/**
	 * 读取性能检测数据
	 * 
	 * @access public,static
	 * @return mixed 如果配置开启了profile则返回数组，否则返回false
	 */
	public static function getProfileInfo(){
		if (self::getOption('profile')){
			return self::$_profileInfo;
		}
		return false;
	}
	
	/**
	 * 保存当前进程执行过的查询SQL
	 * 
	 * @access public,static
	 * @param string $sql
	 * @return null
	 */
	public static function addDbQueries($sql){
		self::$_dbQueries[] = $sql;
	}
	
	/**
	 * 添加路由分析使用的urlMapper，新添加的mapper将放到老mapper的前面优先匹配
	 * 
	 * @access public,static
	 * @return null
	 */
	public static function addUrlMapper($mappers){
		self::$_urlmapper = array_merge($mappers, self::$_urlmapper);
	}
	
	/**
	 * 返回目前已经定义的全部urlMapper
	 * 
	 * @access public,static
	 * @return array
	 */
	public static function getUrlMapper(){
		return self::$_urlmapper;
	}

	/**
	 * 初始化访问会话，通过$sessionId类变量来确定一次请求中只会启动一次Sesssion
	 */
	public static function startSession(){
	    if (!self::$sessionId){
	        $sessid = isset($_REQUEST['sessid']) ? Util::inputFilter($_REQUEST['sessid']) : '';
	        if (!empty($sessid)){
	            self::$sessionId = session_id($sessid);
	            session_start();
	        }else{
	            session_start();
	            self::$sessionId = session_id();
	        }
	    }
	}

	/**
	 * 框架默认的类自动加载器，加载器的路径查找依赖于应用配置中的libPath、namespace、approot三个项目
	 * 
	 * @param string $className
	 * @throws \Exception
	 */
	public static function defaultAutoloader($className){
	    $classParts = explode('\\', $className);
	    if (count($classParts)<2){
	        throw new \Exception(sprintf('class name parse failed when try to autoload class named %s', $className));
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
	        throw new \Exception(sprintf('path %s not found when try to autoload class named %s', $filePath, $className));
	    }
	}

	/**
	 * 默认的错误处理方法，默认会记录到error类型日志中。这个方法是发生错误后的一个
	 * 回调方法，已经在init中进行了注册，不能在其它地方显示调用
	 */
	public static function defaultErrorHandler($errno, $errstr, $errfile='', $errline=-1, array $errcontext=array()){
		$error = array(
			'from' => 'error',
			'type' => $errno,
			'message' => $errstr,
			'file' => $errfile,
			'line' => $errline,
		);
		$logger = self::getInstance('Logger');
		$logger->log('error', $error);
	}

	/**
	 * 默认的进程结束错误捕捉方法，默认会记录到error类型日志中。这个方法是发生错误后的一个
	 * 回调方法，已经在init中进行了注册，不能在其它地方显示调用
	 */
	public static function defaultShutdownFunction(){
		$lastError = error_get_last();
		if ($lastError){
			$lastError = array('from'=>'shutdown') + $lastError;
			
			$logger = self::getInstance('Logger');
			$logger->log('error', $lastError);
		}
	}
	
	/**
	 * 单件实例管理器，相当于一个简单的工厂方法，所有单例模式类的实例均从这个方法获取，目前
	 * 包含：Logger、Mysql、Cache、Config
	 * 
	 * @param string $className
	 * @example App::getInstance('Logger)->logDebug('hello');
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
	 * 调用配置的Router执行当前请求，处理最上层的运行异常，进行性能Profile处理
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
	        //temporarily remove notifiaction profile
	        if (strpos($_SERVER['REQUEST_URI'], '/api/notify/newest')!==false){
	            return;
	        }
	        
	    	$endTime = microtime(true);
	    	self::$_profileInfo = array(
	    		'requestInfo' => $_SERVER['REQUEST_URI'],
	    		'executeTime' => $endTime - $startTime,
	    		'memoryUsage' => memory_get_usage(),
	    		'memoryPeakUsage' => memory_get_peak_usage(),
	    	);
	    	
	    	$logger = self::getInstance('Logger');
	    	$logger->log('profile', self::$_profileInfo);
	    	if (self::$_dbQueries){
	    		$logQuery = '';
	    		foreach (self::$_dbQueries as $query){
	    			$logQuery .= PHP_EOL . "\t" . $query;
	    		}
	    		$logger->log('profile', array('executedQueries'=>$logQuery));
	    	}
	    	$logger->log('profile', Logger::EMPTY_LINE);
	    	
	    	self::$_profileInfo['executedQueries'] = self::$_dbQueries;
	    }
	}
}