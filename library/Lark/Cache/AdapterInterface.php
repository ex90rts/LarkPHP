<?php
namespace Lark\Cache;

/**
 * 缓存处理类适配器接口，缓存适配器必须遵守本接口规范
 * 
 * @author jinhong
 *
 */
interface AdapterInterface{
    /**
     * 该适配器底层服务是否被支持
     */
    public static function isSupport();     
    
    /**
     * 构造方法，需要传入具体的适配器名称，并会读取相应cache配置
     *
     * @param string $adapterName
     * @throws \Exception
     */
    public function __construct(array $configs);
    
    /**
     * 添加缓存减值，如果已经存在该键则返回false
     *
     * @param string $key 缓存键
     * @param mixed $value 缓存值
     * @param int $expire 过期时间
     * @return boolean 是否添加成功
     */
    public function add($key, $value, $expire=0);
    
    /**
     * 设定一个缓存减值，不判断是否已经存在该键
     *
     * @param string $key 缓存键
     * @param mixed $value 缓存值
     * @param int $expire 过期时间
     * @return boolean 是否设定成功
     */
    public function set($key, $value, $expire=0);
    
    /**
     * 通过键获取缓存值
     *
     * @param string $key 缓存键
     * @return mixed 对应的缓存值或false
     */
    public function get($key);
    
    /**
     * 删除一个缓存键
     *
     * @param string $key 缓存键
     * @return boolean 是否删除成功
     */
    public function delete($key);
    
    /**
     * 缓存值递增，默认增加1
     *
     * @param string $key 缓存键
     * @param int $num 增加数值
     * @throws \Exception $num参数不是整数
     * @return boolean 是否增加成功
     */
    public function increment($key, $num=1);
    
    /**
     * 缓存值递减，默认减少1
     *
     * @param string $key 缓存键
     * @param int $num 减少数值
     * @throws \Exception $num参数不是整数
     * @return boolean 是否减少成功
     */
    public function decrement($key, $num=1);
    
    /**
     * 同时设定多个键值对
     *
     * @param array $items 键值对关联数组，数组键即缓存键
     * @param int $expire 过期时间
     * @return boolean 是否设定成功
     */
    public function setMulti($items, $expire=0);
    
    /**
     * 同时获取多个缓存键
     *
     * @param array $keys 缓存键数组
     * @return mixed
     */
    public function getMulti($keys);
    
    /**
     * 返回当前底层缓存服务的状态值，供监控使用
     */
    public function getStatus();
}