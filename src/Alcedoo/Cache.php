<?php
namespace Alcedoo;

use Alcedoo\Exception\CacheServiceNotSupportException;
use Alcedoo\Exception\WrongParamException;

class Cache{
    /**
     * Instance of the cache adapter
     * @var Object
     */
    private $_adapter = null;

    /**
     * PHP in script cache
     * @var array
     */
    private $_cache = array();

    /**
     * Construct the cache service
     * @param string $adapterName
     * @throws \Exception
     */
    public function __construct($adapterName){
        $configName = strtolower($adapterName);
        $adapterName = 'Alcedoo\Cache\Adapter\\'.$adapterName;
        if (!call_user_func(array($adapterName, 'isSupport'))){
            throw new CacheServiceNotSupportException(sprintf('adapter name %s', $adapterName));
        }

        $adapter = null;
        $config = Env::getInstance('Alcedoo\Config');
        if (isset($config->$configName)){
        	$adapter = new $adapterName($config->$configName);
        }else{
            $adapter = new $adapterName();
        }
        $this->_adapter = $adapter;
    }

    /**
     * Add a cache key/value pair
     * @param string $key
     * @param mixed $value
     * @param int $expire
     * @return boolean
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
     * Set a cache key/value pair
     * @param string $key
     * @param mixed $value
     * @param int $expire
     */
    public function set($key, $value, $expire=null){
        $res = $this->_adapter->set($key, $value, $expire);
        if ($res){
        	$this->_cache[$key] = $value;
        }

        return $res;
    }

    /**
     * Get a cache item via key
     * @param string $key
     * @return mixed:
     */
    public function get($key){
        if (isset($this->_cache[$key])){
            return $this->_cache[$key];
        }
        return $this->_adapter->get($key);
    }

    /**
     * Delete a cache via key
     * @param string $key
     */
    public function delete($key){
        if (isset($this->_cache[$key])){
            unset($this->_cache[$key]);
        }
        return $this->_adapter->delete($key);
    }

    /**
     * Increase an int cache value by $num
     * @param string $key
     * @param int $num
     * @throws \Exception
     */
    public function increment($key, $num=1){
        if (!is_int($num)){
            throw new WrongParamException("increment number must be a integer, [{$num}] given");
        }

        $res = $this->_adapter->increase($key, $num);
        if ($res && isset($this->_cache[$key]) && is_numeric($this->_cache[$key])){
        	$this->_cache[$key] = $this->_cache[$key] + $num;
        }

        return $res;
    }

    /**
     * Decrease a int cache value by $num
     * @param string $key
     * @param int $num
     * @throws \Exception
     */
    public function decrement($key, $num=1){
        if (!is_int($num)){
            throw new WrongParamException("decrement number must be a integer, [{$num}] given");
        }

        $res = $this->_adapter->decrease($key, $num);
        if ($res && isset($this->_cache[$key]) && is_numeric($this->_cache[$key])){
        	$this->_cache[$key] = $this->_cache[$key] - $num;
        }

        return $res;
    }

    /**
     * Set multi cache items at the same time
     * @param array $items
     * @param int $expire
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
     * Get multi cache items at the same time
     * @param array $keys
     * @return Ambigous <multitype:, multitype:multitype: >
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
}