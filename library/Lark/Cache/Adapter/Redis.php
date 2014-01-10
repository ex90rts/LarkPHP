<?php
namespace Lark\Cache\Adapter;

use Lark\Cache\AdapterInterface;
use Lark\Util;

class Redis implements AdapterInterface{
    /**
     * Var for save current cache instance
     * @var Object
     */
    private $_cache = null;

    /**
     * Var to save current unix timestamp in script
     * @var int
     */
    private $_now;
    
    /**
     * Global key prefix
     * @var string
     */
    private $_prefix;

    /**
     * Check if this cache service is support on current server
     * @return boolean
     */
    public static function isSupport(){
        return class_exists('\Redis', false);
    }

    /**
     * Construct the cache service
     * @param string $adapterName
     * @throws \Exception
     */
    public function __construct (array $config){
        if (!isset($config['server']) || !isset($config['port'])){
            throw new \Exception('Redis servers missed in configuration');
        }
        
        $this->_cache = new \Redis();
        try{
	        if ($config['persistent']){
	        	$conn = $this->_cache->pconnect($config['server'], $config['port'], $config['timeout']);
	        }else{
	        	$conn = $this->_cache->connect($config['server'], $config['port'], $config['timeout']);
	        }
        }catch (\Exception $e){
        	throw $e;
        }
        
        if (!$conn){
        	throw new \Exception("Redis server went away");
        }
        
        if ($config['password']){
        	$this->_cache->auth($config['password']);
        }
        
        $this->_cache->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
        
        if (!empty($config['prefix'])){
        	$this->_prefix = $config['prefix'];
        }
        
        $this->_now = Util::getNow();
    }

    /**
     * Add a cache key/value pair
     * @param string $key
     * @param mixed $value
     * @param int $expire
     * @return boolean
     */
	public function add ($key, $value, $expire = 0){
		if ($this->_cache->exists($key)){
			return false;
		}
	    if ($expire == 0){
	    	return $this->_cache->set($key, $value);
	    }
    	return $this->_cache->setex($key, $expire, $value);
	}

	/**
	 * Set a cache key/value pair
	 * @param string $key
	 * @param mixed $value
	 * @param int $expire
	 */
	public function set ($key, $value, $expire = 0){
	    if ($expire == 0){
	    	return $this->_cache->set($key, $value);
	    }
    	return $this->_cache->setex($key, $expire, $value);
	}

	/**
	 * Get a cache item via key
	 * @param string $key
	 * @return mixed:
	 */
	public function get ($key){
        return $this->_cache->get($key);
	}

	/**
	 * Delete a cache via key
	 * @param string $key
	 */
	public function delete ($key){
        return $this->_cache->delete($key) > 0;
    }

    /**
     * Increase an int cache value by $num
     * @param string $key
     * @param int $num
     * @throws \Exception
     */
    public function increment($key, $num=1){
    	$base = $this->_cache->get($key);
    	if (!$base){
    		$base = 0;
    	}
    	return $this->_cache->set($key, $base+$num);
    }

    /**
     * Decrease a int cache value by $num
     * @param string $key
     * @param int $num
     * @throws \Exception
     */
    public function decrement($key, $num=1){
    	$base = $this->_cache->get($key);
    	if (!$base){
    		$base = 0;
    	}
    	return $this->_cache->set($key, $base-$num);
    }

    /**
     * Set multi cache items at the same time
     * @param array $items
     * @param int $expire
     */
	public function setMulti ($items, $expire=0){
	    if ($expire == 0 ){
	    	return $this->_cache->mset($items);
	    }else{
	    	foreach ($items as $key=>$value){
	    		$this->_cache->setex($key, $expire, $value);
	    	}
	    	return true;
	    }
	}

	/**
	 * Get multi cache items at the same time
	 * @param array $keys
	 * @return mixed
	 */
	public function getMulti ($keys){
	    $values = $this->_cache->mGet($keys);
	    if (is_array($values)){
	    	return array_combine($keys, $values);
	    }
	    return false;
	}
}