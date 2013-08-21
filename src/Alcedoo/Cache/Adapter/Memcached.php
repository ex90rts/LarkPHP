<?php
namespace Alcedoo\Cache\Adapter;

use Alcedoo\Cache\AdapterInterface;
use Alcedoo\Util;

class Memcached implements AdapterInterface{
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
     * Check if this cache service is support on current server
     * @return boolean
     */
    public static function isSupport(){
        return class_exists('\Memcached', false);
    }

    /**
     * Construct the cache service
     * @param string $adapterName
     * @throws \Exception
     */
    public function __construct (array $config){
        if (!isset($config['servers'])){
            throw new \Exception('Memcached servers missed in configuration');
        }

        $this->_cache = new \Memcached();
        $this->_cache->addServers($config['servers']);

        $compression = false;
        if (isset($config['compression'])){
            $compression = !!$config['compression'];
        }
        $this->_cache->setOption(Memcached::OPT_COMPRESSION, $compression);
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
	    $expire = empty($expire) ? 0 : $this->_now+$expire;
    	return $this->_cache->add($key, $value, $expire);
	}

	/**
	 * Set a cache key/value pair
	 * @param string $key
	 * @param mixed $value
	 * @param int $expire
	 */
	public function set ($key, $value, $expire = 0){
	    $expire = empty($expire) ? 0 : $this->_now+$expire;
	    return $this->_cache->set($key, $value, $expire);
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
        return $this->_cache->delete($key);
    }

    /**
     * Increase an int cache value by $num
     * @param string $key
     * @param int $num
     * @throws \Exception
     */
    public function increment($key, $num=1){
        return $this->_cache->increment($key, $num);
    }

    /**
     * Decrease a int cache value by $num
     * @param string $key
     * @param int $num
     * @throws \Exception
     */
    public function decrement($key, $num=1){
        return $this->_cache->decrement($key, $num);
    }

    /**
     * Set multi cache items at the same time
     * @param array $items
     * @param int $expire
     */
	public function setMulti ($items, $expire=0){
	    $expire = empty($expire) ? 0 : $this->_now+$expire;
		return $this->_cache->setMulti($items, $expire);
	}

	/**
	 * Get multi cache items at the same time
	 * @param array $keys
	 * @return mixed
	 */
	public function getMulti ($keys){
	    return $this->_cache->getMulti($keys);
	}
}