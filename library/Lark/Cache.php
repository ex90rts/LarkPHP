<?php
namespace Lark;

use Lark\Exception\CacheServiceNotSupportException;
use Lark\Exception\WrongParamException;

/**
 * 数据缓存层处理类
 * 
 * 负责PHP和数据库之间的高速缓存，目前支持文件、Memcached和Redis 3中底层缓存服务。
 * 本缓存类为单例类，必须使用App::getInstance来获取实例
 * 
 * @property Cache\AdapterInterface $_adapter 具体的缓存服务适配类
 * @property array $_cache 脚本缓存，在同一个进程中将优先使用
 * @property Cache $instance 用于单例模式保存当前实例
 * @author jinhong
 * @see Cache\AdapterInterface
 *
 */
class Cache{
    /**
     * 具体的缓存服务适配类
     * 
     * @var Cache\AdapterInterface
     */
    private $_adapter = null;

    /**
     * 脚本缓存，在同一个进程中将优先使用，如果在同一个进程中二次读取时将
     * 不需要连接底层的缓存服务
     * 
     * @var array
     */
    private $_cache = array();
    
    /**
     * 用于单例模式保存当前实例
     * 
     * @var Cache
     */
    private static $_instance = null;

    /**
     * 构造方法，需要传入具体的适配器名称，并会读取相应cache配置
     * 
     * @param string $adapterName
     * @throws \Exception
     */
    private function __construct($adapterName=''){
    	$cacheConf = App::getInstance('Config')->cache;
    	if (!$cacheConf){
    		throw new \Exception('Cache configuration missed, please setup cache.php under *app*/config folder');
    	}
    	
    	if ($adapterName==''){
    		$adapterName = $cacheConf['adapter'];
    	}
    	$configName = strtolower($adapterName);
    	
        $adapterName = 'Lark\Cache\Adapter\\'.ucfirst($adapterName);
        if (!call_user_func(array($adapterName, 'isSupport'))){
            throw new CacheServiceNotSupportException(sprintf('adapter name %s', $adapterName));
        }

        $adapter = null;
        if (isset($cacheConf[$configName])){
        	$adapter = new $adapterName($cacheConf[$configName]);
        }else{
            $adapter = new $adapterName();
        }
        $this->_adapter = $adapter;
    }
    
    /**
     * 单例模式初始化
     * 
     * @return Cache
     */
    public static function getInstance(){
    	if (!self::$_instance){
    		$class = __CLASS__;
    		self::$_instance = new $class();
    	}
    	return self::$_instance;
    }

    /**
     * 添加缓存减值，如果已经存在该键则返回false
     * 
     * @param string $key 缓存键
     * @param mixed $value 缓存值
     * @param int $expire 过期时间
     * @return boolean 是否添加成功
     */
    public function add($key, $value, $expire=null){
        if (isset($this->_cache[$key])){
            return false;
        }

        $res = $this->_adapter->add($key, $value, $expire);
        if ($res){
        	$this->_cache[$key] = $value;
        }

        return $res;
    }

    /**
     * 设定一个缓存减值，不判断是否已经存在该键
     * 
     * @param string $key 缓存键
     * @param mixed $value 缓存值
     * @param int $expire 过期时间
     * @return boolean 是否设定成功
     */
    public function set($key, $value, $expire=null){
        $res = $this->_adapter->set($key, $value, $expire);
        if ($res){
        	$this->_cache[$key] = $value;
        }

        return $res;
    }

    /**
     * 通过键获取缓存值
     * 
     * @param string $key 缓存键
     * @return mixed 对应的缓存值或false
     */
    public function get($key){
        if (isset($this->_cache[$key])){
            return $this->_cache[$key];
        }
        return $this->_adapter->get($key);
    }

    /**
     * 删除一个缓存键
     * 
     * @param string $key 缓存键
     * @return boolean 是否删除成功
     */
    public function delete($key){
        if (isset($this->_cache[$key])){
            unset($this->_cache[$key]);
        }
        return $this->_adapter->delete($key);
    }

    /**
     * 缓存值递增，默认增加1
     * 
     * @param string $key 缓存键
     * @param int $num 增加数值
     * @throws \Exception $num参数不是整数
     * @return boolean 是否增加成功
     */
    public function increment($key, $num=1){
        if (!is_int($num)){
            throw new WrongParamException("increment number must be a integer, [{$num}] given");
        }

        $res = $this->_adapter->increment($key, $num);
        if ($res && isset($this->_cache[$key]) && is_numeric($this->_cache[$key])){
        	$this->_cache[$key] = $this->_cache[$key] + $num;
        }

        return $res;
    }

    /**
     * 缓存值递减，默认减少1
     * 
     * @param string $key 缓存键
     * @param int $num 减少数值
     * @throws \Exception $num参数不是整数
     * @return boolean 是否减少成功
     */
    public function decrement($key, $num=1){
        if (!is_int($num)){
            throw new WrongParamException("decrement number must be a integer, [{$num}] given");
        }

        $res = $this->_adapter->decrement($key, $num);
        if ($res && isset($this->_cache[$key]) && is_numeric($this->_cache[$key])){
        	$this->_cache[$key] = $this->_cache[$key] - $num;
        }

        return $res;
    }

    /**
     * 同时设定多个键值对
     * 
     * @param array $items 键值对关联数组，数组键即缓存键
     * @param int $expire 过期时间
     * @return boolean 是否设定成功
     */
    public function setMulti($items=array(), $expire=null){
        $res = $this->_adapter->setMulti($items, $expire);
        if ($res){
        	foreach($items as $key=>$value){
        		$this->_cache[$key] = $value;
        	}
        }

        return $res;
    }

    /**
     * 同时获取多个缓存键
     * 
     * @param array $keys 缓存键数组
     * @return mixed
     */
    public function getMulti($keys=array()){
        $foundKeys = array();
        $foundItems = array();
        foreach ($keys as $key){
            if (isset($this->_cache[$key])){
                $foundKeys[] = $key;
                $foundItems[$key] = $this->_cache[$key];
            }
        }

        $missedKeys = array_diff($keys, $foundKeys);
        $cachedItems = $this->_adapter->getMulti($missedKeys);
        if ($cachedItems){
            $foundItems = array_merge($foundItems, $cachedItems);
        }

        return $foundItems;
    }
    
    /**
     * 返回当前底层缓存服务的状态值，供监控使用
     */
    public function getStatus(){
    	return $this->_adapter->getStatus();
    }
}