<?php
namespace Lark\Cache;

interface AdapterInterface{
    public static function isSupport();     
    
    public function __construct(array $configs);
    
    public function add($key, $value, $expire=0);
    
    public function set($key, $value, $expire=0);
    
    public function get($key);
    
    public function delete($key);
    
    public function increment($key, $num=1);
    
    public function decrement($key, $num=1);
    
    public function setMulti($items, $expire=0);
    
    public function getMulti($keys);
}