<?php
namespace Lark;

/**
 * 逻辑错误类
 * 
 * 用于封装应用中可能产生的逻辑错误（并不是运行时错误或服务器端错误），多应用在
 * 请求数据验证、请求结果与预期不合、权限控制等环节。类归纳定义了30种不同的错误
 * 类型，暂时未提供错误类型扩展的接口，为在定义中的可以使用ERR_UNKNOWN代替
 * 
 * 已经定义的错误类型如下：
 * - ERR_ACCESS_DENY    = 'ERR_ACCESS_DENY';
 * - ERR_ACCESS_BAN     = 'ERR_ACCESS_BAN';
 * - ERR_FEATURE_DENY   = 'ERR_FEATURE_DENY';
 * - ERR_EMPTY          = 'ERR_EMPTY';
 * - ERR_FORMAT         = 'ERR_FORMAT';
 * - ERR_PARAMS         = 'ERR_PARAMS';
 * - ERR_TAKEN          = 'ERR_TAKEN';
 * - ERR_USED           = 'ERR_USED';
 * - ERR_TOO_SMALL      = 'ERR_TOO_SMALL';
 * - ERR_TOO_BIG        = 'ERR_TOO_BIG';
 * - ERR_TOO_SHORT      = 'ERR_TOO_SHORT';
 * - ERR_TOO_LONG       = 'ERR_TOO_LONG';
 * - ERR_NOT_ACCEPT     = 'ERR_NOT_ACCEPT';
 * - ERR_NOT_INT        = 'ERR_NOT_INT';
 * - ERR_NOT_NUM        = 'ERR_NOT_NUM';
 * - ERR_NOT_BOOL       = 'ERR_NOT_BOOL';
 * - ERR_NOT_DATE       = 'ERR_NOT_DATE';
 * - ERR_SIZE_TOO_SMALL = 'ERR_SIZE_TOO_SMALL';
 * - ERR_SIZE_TOO_BIG   = 'ERR_SIZE_TOO_BIG';
 * - ERR_DIME_TOO_SMALL = 'ERR_DIME_TOO_SMALL';
 * - ERR_DIME_TOO_BIG   = 'ERR_DIME_TOO_BIG';
 * - ERR_NOT_EXIST      = 'ERR_NOT_EXIST';
 * - ERR_EXPIRED        = 'ERR_EXPIRED';
 * - ERR_TIMES_EXCEED   = 'ERR_TIMES_EXCEED';
 * - ERR_NUMBER_EXCEED  = 'ERR_NUMBER_EXCEED';
 * - ERR_ALREADY_DONE   = 'ERR_ALREADY_DONE';
 * - ERR_NOT_MATCH      = 'ERR_NOT_MATCH';
 * - ERR_UNKNOWN        = 'ERR_UNKNOWN';
 * - ERR_CANNOT_DELETE  = 'ERR_CANNOT_DELETE';
 * 
 * @property string $level 错误级别
 * @property string $type 错误类型
 * @property string $key 错误关键字
 * @property array $assoc 错误关联数据
 * @author samoay
 *
 */
class Error{
	
	/**
	 * @var string 错误级别-全局，一般服务器无法处理的错误归入此层级
	 */
	const LEVEL_GLOBAL     = 'LEVEL_GLOBAL';
	
	/**
	 * @var string 错误级别-控制器，一般在控制器中产生错误但是又没有Model直接关联是使用
	 */
	const LEVEL_CONTROLLER = 'LEVEL_CONTROLLER';
	
	/**
	 * @var string 错误级别-模型，有Model关联的错误
	 */
	const LEVEL_MODEL      = 'LEVEL_MODEL';
	
	/**
	 * @var string 错误级别-动作
	 */
	const LEVEL_ACTION     = 'LEVEL_ACTION';
	
	/**
	 * @var string 错误类型-无权访问
	 */
	const ERR_ACCESS_DENY    = 'ERR_ACCESS_DENY';
	
	/**
	 * @var string 错误类型-禁止使用
	 */
	const ERR_ACCESS_BAN     = 'ERR_ACCESS_BAN';
	
	/**
	 * @var string 错误类型-无此功能
	 */
	const ERR_FEATURE_DENY   = 'ERR_FEATURE_DENY';
	
	/**
	 * @var string 错误类型-数据不能为空
	 */
	const ERR_EMPTY          = 'ERR_EMPTY';
	
	/**
	 * @var string 错误类型-参数错误
	 */
	const ERR_PARAMS         = 'ERR_PARAMS';
	
	/**
	 * @var string 错误类型-对应数据已经被占用，针对值必须保证唯一的数据
	 */
	const ERR_TAKEN          = 'ERR_TAKEN';
	
	/**
	 * @var string 错误类型-对应数据已经使用过了，针对只能使用一次的数据
	 */
	const ERR_USED           = 'ERR_USED';
	
	/**
	 * @var string 错误类型-对应数据数值过小
	 */
	const ERR_TOO_SMALL      = 'ERR_TOO_SMALL';
	
	/**
	 * @var string 错误类型-对应数据数值过大
	 */
	const ERR_TOO_BIG        = 'ERR_TOO_BIG';
	
	/**
	 * @var string 错误类型-对应数据长度过短
	 */
	const ERR_TOO_SHORT      = 'ERR_TOO_SHORT';
	
	/**
	 * @var string 错误类型-对应数据长度过长
	 */
	const ERR_TOO_LONG       = 'ERR_TOO_LONG';
	
	/**
	 * @var string 错误类型-对应数据格式错误
	 */
	const ERR_FORMAT         = 'ERR_FORMAT';
	
	/**
	 * @var string 错误类型-对应数据值不在规定范围内，针对可枚举的数据
	 */
	const ERR_NOT_ACCEPT     = 'ERR_NOT_ACCEPT';
	
	/**
	 * @var string 错误类型-对应数据不是整数
	 */
	const ERR_NOT_INT        = 'ERR_NOT_INT';
	
	/**
	 * @var string 错误类型-对应数据不是数字
	 */
	const ERR_NOT_NUM        = 'ERR_NOT_NUM';
	
	/**
	 * @var string 错误类型-对应数据不是布尔型
	 */
	const ERR_NOT_BOOL       = 'ERR_NOT_BOOL';
	
	/**
	 * @var string 错误类型-对应数据不是有效日期
	 */
	const ERR_NOT_DATE       = 'ERR_NOT_DATE';
	
	/**
	 * @var string 错误类型-对应文件数据太小
	 */
	const ERR_SIZE_TOO_SMALL = 'ERR_SIZE_TOO_SMALL';
	
	/**
	 * @var string 错误类型-对应文件数据太大
	 */
	const ERR_SIZE_TOO_BIG   = 'ERR_SIZE_TOO_BIG';
	
	/**
	 * @var string 错误类型-对应图片分比率太小
	 */
	const ERR_DIME_TOO_SMALL = 'ERR_DIME_TOO_SMALL';
	
	/**
	 * @var string 错误类型-对应图片分比率太大
	 */
	const ERR_DIME_TOO_BIG   = 'ERR_DIME_TOO_BIG';
	
	/**
	 * @var string 错误类型-对应数据不存在
	 */
	const ERR_NOT_EXIST      = 'ERR_NOT_EXIST';
	
	/**
	 * @var string 错误类型-对应数据已过期
	 */
	const ERR_EXPIRED        = 'ERR_EXPIRED';
	
	/**
	 * @var string 错误类型-操作次数超过限制
	 */
	const ERR_TIMES_EXCEED   = 'ERR_TIMES_EXCEED';
	
	/**
	 * @var string 错误类型-数值超过限制
	 */
	const ERR_NUMBER_EXCEED  = 'ERR_NUMBER_EXCEED';
	
	/**
	 * @var string 错误类型-操作重复，针对只能进行一次的操作
	 */
	const ERR_ALREADY_DONE   = 'ERR_ALREADY_DONE';
	
	/**
	 * @var string 错误类型-数据不匹配，针对连个数据之间比较
	 */
	const ERR_NOT_MATCH      = 'ERR_NOT_MATCH';
	
	/**
	 * @var string 错误类型-未知错误
	 */
	const ERR_UNKNOWN        = 'ERR_UNKNOWN';
	
	/**
	 * @var string 错误类型-数据不能被删除
	 */
	const ERR_CANNOT_DELETE  = 'ERR_CANNOT_DELETE';
	
	/**
	 * @access private
	 * @var string 错误级别，默认为LEVEL_GLOBAL
	 */
	private $level = self::LEVEL_GLOBAL;
	
	/**
	 * @access private
	 * @var string 错误类型，默认为ERR_UNKNOWN
	 */
	private $type = self::ERR_UNKNOWN;
	
	/**
	 * @access private 
	 * @var string 错误关键字，用于标示错误和关联提示
	 */
	private $key = '';
	
	/**
	 * @access private
	 * @var string 错误关联数据，用于调试或错误提示
	 */
	private $assoc = array();
	
	/**
	 * 构造方法，用于生成新的逻辑错误实例
	 * 
	 * @param string $level
	 * @param string $type
	 * @param string $key
	 * @param array $assoc
	 */
	public function __construct($level, $type, $key='', $assoc=array()){
		$this->level = $level;
		$this->type  = $type;
		$this->key   = $key;
		$this->assoc = $assoc;
	}
	
	/**
	 * 返回错误级别
	 * 
	 * @access public
	 * @return string
	 */
	public function getLevel(){
		return $this->level;
	}
	
	/**
	 * 返回错误类型
	 * 
	 * @access public
	 * @return string
	 */
	public function getType(){
		return $this->type;
	}
	
	/**
	 * 返回错误关键字
	 * 
	 * @access public
	 * @return string
	 */
	public function getKey(){
		return $this->key;
	}
	
	/**
	 * 返回错误关联数据
	 * 
	 * @access public
	 * @return string
	 */
	public function getAssoc(){
		return $this->assoc;
	}
	
	/**
	 * 将错误对象格式化为关联数组并返回
	 * 
	 * @access public
	 * @return array
	 */
	public function toArray(){
		return array(
			'level' => $this->level,
			'type'  => $this->type,
			'key'   => $this->key,
			'assoc' => $this->assoc,
		);
	}
	
	/**
	 * 将错误对象转行为字符串的魔术方法
	 * 
	 * @access public
	 * @return string
	 */
	public function __toString(){
		$string = "{$this->level}:{$this->type}:{$this->key}";
		if ($this->assoc){
			$string .= ":".implode(",", $this->assoc);
		}
		return $string;
	}
}
