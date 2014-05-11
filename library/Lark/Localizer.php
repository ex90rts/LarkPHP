<?php
namespace Lark;

/**
 * 语言包本地化基类
 * 
 * 实现语言包根据locale读取、缓存和输出，是一个抽象类，应用中必须有子类实现具体的缓存和读取方法
 * 
 * @property array/Model $source 语言包数据源，根据子类实现而定，可能是数组或者Model实例
 * @property string $sourceType 数据源类型，目前支持文件或Model
 * @property array $caches 脚本缓存，避免第二次读取同一个语言包时再从数据源读取
 * @author samoay
 *
 */
abstract class Localizer{
	/**
	 * @var string 数据源类型-文件
	 */
	const TYPE_FILE  = 'FILE';
	
	/**
	 * @var string 数据源类型-数据模型，从数据库读取
	 */
	const TYPE_MODEL = 'MODEL';
	
	/**
	 * @var array/Model 语言包数据源，根据子类实现而定，可能是数组或者Model实例
	 */
	protected $source;
	
	/**
	 * @var array/Model 数据源类型，目前支持文件或Model
	 */
	protected $sourceType;
	
	/**
	 * @var array 脚本缓存，避免第二次读取同一个语言包时再从数据源读取
	 */
	private $caches = array();
	
	/**
	 * 构造方法，传入数据源和源类型
	 * 
	 * @param mixed $source 语言包数据源
	 * @param string $type 数据源类型
	 */
	function __construct($source, $sourceType=self::TYPE_MODEL){
		$this->source = $source;
		$this->sourceType = $sourceType;
	}
	
	/**
	 * 存入脚本缓存，供子类调用
	 * 
	 * @param string $key 语言包键
	 * @param string $locale 当前语言
	 * @param string $result 语言包内容
	 */
	protected function saveCache($key, $locale, $result){
		$key = "{$key}-{$locale}";
		$this->caches[$key] = $result;
	}
	
	/**
	 * 读取脚本缓存，供子类调用
	 * 
	 * @param string $key 语言包键
	 * @param string $locale 当前语言
	 */
	protected function readCache($key, $locale){
		$key = "{$key}-{$locale}";
		if (isset($this->caches[$key])){
			return $this->caches[$key];
		}
		
		return false;
	}
	
	/**
	 * 输出指定locale的语言包内容，为虚拟方法，需要子类实现
	 * 
	 * @param string $key 语言包键
	 * @param string $locale 当前语言
	 * @param array $replacements 语言包中需要替换的键值关联数组
	 */
	abstract function say($key, $locale, $replacements=array());
}