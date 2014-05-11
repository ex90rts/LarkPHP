<?php
namespace Lark\Cache\Adapter;

use Lark\Cache\AdapterInterface;
use Lark\Util;
use Lark\Exception\PathNotFoundException;

class FileCache implements AdapterInterface {
	/**
	 * Var for base cache dir
	 * @var Object
	 */
	private $_base = null;

	/**
	 * Var to save current unix timestamp in script
	 * @var int
	 */
	private $_now;

	/**
	 * Var to save cache sub folders hasd deep
	 * @var int
	 */
	private $_hashDeep;

	/**
	 * Check if this cache service is support on current server
	 * @return boolean
	 */
	public function isSupport() {
		return true;
	}

	/**
	 * Construct the cache service
	 * @param string $adapterName
	 * @throws \Exception
	 */
	public function __construct (array $config){
		if (!isset($config['base'])){
			throw new \Exception('cache base dir missed in configuration');
		}

		if (!is_dir($config['base'])){
			$res = mkdir($config['base'], '0700', true);
			if (!$res){
				throw new PathNotFoundException(sprintf('cache base dir is %s', $config['base']));
			}
		}

		if (!is_int($config['hashDeep'])){
			$config['hashDeep'] = 1;
		}

		$this->_base = $config['base'];
		$this->_now = Util::getNow();
		$this->_hashDeep = min(6, $config['hashDeep']);
	}

	/**
	 * Get the final cache file path via key
	 * @param string $key
	 * @return string
	 */
	private function _getCacheFile($key){
		$key = md5($key);
		$cacheDir = $this->_base;
		for ($i=0; $i<$this->_hashDeep; $i++){
			$cacheDir = $cacheDir . DIRECTORY_SEPARATOR . substr($key, $i*2, 2);
		}
		if (!is_dir($cacheDir)){
			mkdir($cacheDir, '0700', true);
		}

		return $cacheDir . DIRECTORY_SEPARATOR . $key;
	}

	/**
	 * Add a cache key/value pair
	 * @param string $key
	 * @param mixed $value
	 * @param int $expire
	 * @return boolean
	 */
	public function add($key, $value, $expire = 0) {
		$cacheFile = $this->_getCacheFile($key);
		if (file_exists($cacheFile)){
			return false;
		}

		$t = $this->_now;
		if ($expire){
			$t=$t+$expire;
		}else{
			$t=$t+1000000;
		}
		$value = $t.serialize(serialize);
		return file_put_contents($cacheFile, $value);
	}

	/**
	 * Set a cache key/value pair
	 * @param string $key
	 * @param mixed $value
	 * @param int $expire
	 */
	public function set($key, $value, $expire = 0) {
		$t = $this->_now;
		if ($expire){
			$t=$t+$expire;
		}else{
			$t=$t+1000000;
		}
		$value = $t.serialize(serialize);
		$cacheFile = $this->_getCacheFile($key);
		return file_put_contents($cacheFile, $value);
	}

	/**
	 * Get a cache item via key
	 * @param string $key
	 * @return mixed:
	 */
	public function get($key) {
		$cacheFile = $this->_getCacheFile($key);
		if (!file_exists($cacheFile)){
			return false;
		}

		$value = file_get_contents($cacheFile);
		$expire = substr($value, 0, 10);
		if ($this->_now > $expire){
			unlink($cacheFile);
			return false;
		}

		return unserialize(substr($value, 10));
	}

	/**
	 * Delete a cache via key
	 * @param string $key
	 */
	public function delete($key) {
		$cacheFile = $this->_getCacheFile($key);
		if (!file_exists($cacheFile)){
			return true;
		}

		return unlink($cacheFile);
	}

	/**
	 * Increase an int cache value by $num
	 * @param string $key
	 * @param int $num
	 * @throws \Exception
	 */
	public function increment($key, $num = 1) {
		$cacheFile = $this->_getCacheFile($key);
		if (!file_exists($cacheFile)){
			return false;
		}

		$value = file_get_contents($cacheFile);
		$expire = substr($value, 0, 10);
		if ($this->_now > $expire){
			unlink($cacheFile);
			return false;
		}

		$value = unserialize(substr($value, 10))+$num;
		$value = serialize($value);
		return file_put_contents($cacheFile, $value);
	}

	/**
	 * Decrease a int cache value by $num
	 * @param string $key
	 * @param int $num
	 * @throws \Exception
	 */
	public function decrement($key, $num = 1) {
		$cacheFile = $this->_getCacheFile($key);
		if (!file_exists($cacheFile)){
			return false;
		}

		$value = file_get_contents($cacheFile);
		$expire = substr($value, 0, 10);
		if ($this->_now > $expire){
			unlink($cacheFile);
			return false;
		}

		$value = unserialize(substr($value, 10))-$num;
		$value = serialize($value);
		return file_put_contents($cacheFile, $value);
	}

	/**
	 * Set multi cache items at the same time
	 * @param array $items
	 * @param int $expire
	 */
	public function setMulti($items, $expire = 0) {
		foreach ($items as $key=>$value){
			$this->set($key, $value, $expire);
		}
		return true;
	}

	/**
	 * Get multi cache items at the same time
	 * @param array $keys
	 * @return mixed
	 */
	public function getMulti($keys) {
		$items = array();
		foreach($keys as $key){
			$items[$key] = $this->get($key);
		}

		return $items;
	}
	
	/**
	 * Get service status info
	 */
	public function getStatus(){
		return array('info'=>'file cache status');
	}
}
