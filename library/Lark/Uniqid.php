<?php
namespace Lark;

use Lark\Cache;
use Lark\Exception\UniqidDbengineNotSupportException;
use Lark\Exception\MissingConfigException;
use Lark\Exception\CreateUniqidFailedException;
use Lark\Exception\UniqidTypeUndefinedException;

class Uniqid{
    private static $_isInited = false;
    
    private static $_typeCode;
    
    private static $_typeDeci = 2;
    
    private static $_timeDeci = 9;
    
    private static $_randDeci = 5;

    private static $_dbengine;
    
    private static $_supportDbengines = array('mongo', 'mysql');
    
    private static $_cache = null;
    
    private static function init(){
        if (!self::$_isInited){
	        $config = App::getInstance('Config')->uniqid;
	        if (!isset($config)){
	            throw new MissingConfigException('missing uniqid config');
	        }
	        
	        self::$_typeCode = $config['typeCode'];
	        if (isset($config['typeDeci'])){
	        	self::$_typeDeci = $config['typeDeci'];
	        }
	        if (isset($config['timeDeci'])){
	        	self::$_timeDeci = $config['timeDeci'];
	        }
	        if (isset($config['randDeci'])){
	        	self::$_randDeci = $config['randDeci'];
	        }
	        
	        if (isset($config['cache']) && $config['cache']){
	        	self::$_cache = Cache::getInstance();
	        }
	        
	        if (!in_array($config['dbengine'], self::$_supportDbengines)){
	            throw new UniqidDbengineNotSupportException(sprintf('dbengine only support %s, %s given', implode('/', self::$_supportDbengines), $config['dbengine']));
	        }
	        
	        $className = 'Lark\Uniqid\Dbengine\\' . ucfirst($config['dbengine']);
	        $realClass = new \ReflectionClass($className);
	        self::$_dbengine = $realClass->newInstance($config['table']);
	        
	        self::$_isInited = true;
        }
    }
    
    private static function checkType($type){
        if (!isset(self::$_typeCode[$type])){
            throw new UniqidTypeUndefinedException(sprintf('only support types %s, %s given', implode('/', array_keys(self::$_typeCode)), $type));
        }
    }
    
    public static function create($type){
        self::init();
        self::checkType($type);
        $typeCode = self::$_typeCode[$type];
        $timeCode = strval(time()-App::START_TIMESTAMP);
        $timeCode = substr(str_pad($timeCode, self::$_timeDeci, '0', STR_PAD_LEFT), -self::$_timeDeci);
        
        if (self::$_cache){
        	$ckey = "uniqid_rand_index_{$timeCode}";
        	$randIndex = self::$_cache->get($ckey);
        	if ($randIndex){
        		$randIndex = $randIndex+1;
        	}else{
        		$randIndex = 1;
        	}
        	self::$_cache->set($ckey, $randIndex, 100);
        	$randCode = mt_rand(0, 9) . substr(str_pad($randIndex, self::$_randDeci-1, '0', STR_PAD_LEFT), -(self::$_randDeci-1));
        }else{
        	$randCode = mt_rand(pow(10,self::$_randDeci-1), pow(10, self::$_randDeci)-1);
        }
        
        $id = $typeCode . $timeCode . $randCode;
        if (!self::$_dbengine->insert($typeCode, $id)){
            throw new CreateUniqidFailedException(sprintf('create uniqueid %s failed', $id));
        }
        
        return $id;
    }
    
    public static function createRandCode(){
    	self::init();
    	$ckey = 'uniqid_rand_index';
    	$cached = self::$_cache->get($ckey);

    }
    
    public static function getTypeCode($id){
        self::init();
        return substr($id, 0, self::$_typeDeci);
    }
    
    public static function getTimeCode($id){
        self::init();
        return substr($id, self::$_typeDeci, self::$_timeDeci);
    }
    
    public static function getTimestamp($id){
        self::init();
        $timeCode = self::getTimeCode($id);
        return intval($timeCode)+App::START_TIMESTAMP;
    }
    
    public static function hasId($id){
        self::init();
        return self::$_dbengine->hasId($id);
    }
    
    public static function getTypeCount($type){
        self::init();
        self::checkType($type);
        return self::$_dbengine->getTypeCount(self::$_typeCode[$type]);
    }
}