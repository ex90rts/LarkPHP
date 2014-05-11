<?php
namespace Lark;

use Lark\Request;
use Lark\Visitor;

/**
 * 访客权限控制类
 * 
 * 访客权限控制，在每个控制器动作执行前进行检查，检查结果分为AC_DENY和AC_GRANT两种，
 * AC_DENY则拒绝访问，AC_GRANT则准许访问。在每个控制器类中需要定义权限控制规则，称为
 * access rules，为一个数字索引数组，验证将按照数组自然顺序进行，一旦匹配上规则则停止
 * 继续检查，返回当前规则第一的检查结果：该规则的第一个值。单个规则的格式，第一个值是
 * 该规则匹配成功后的控制结果，后面则是具体的匹配项目，这些具体匹配项目必须全部满足才
 * 为匹配该项规则。当验证结果为AC_DENY时，会自动调用Controller的accessDenied方法，
 * 作为拒绝访问的逻辑处理，为AC_GRANT时则会开始执行请求的具体动作。具体的匹配项目包含
 * 以下几种：
 * <ul>
 * 	<li>verbs: 匹配HTTP动作类型，如GET、POST、DELETE、PUT等，规则内容为数组，动作字符串全大写</li>
 *  <li>roles: 匹配访客对象Visitor的角色属性，选项见Visitor类的ROLE_*常量定义</li>
 *  <li>labels: 匹配访客对象Visitor的权限标签，实现更细粒度的权限控制，内容是完全自定义的字符串标示</li>
 *  <li>actions: 匹配控制器的动作，内容为当前控制器中可用的动作方法名</li>
 *  <li>users: 直接匹配访客对象Visitor的唯一ID</li>
 *  <li>ips: 匹配访客客户端IP，用于控制一些测试需要的特殊动作，或者屏蔽指定IP</li>
 * <ul>
 * 
 * 上述控制规则在控制器中通过accessRules方法定义返回，具体格式为一个数字索引数组，举例如下：
 * 
 * array(
 *     //第一条验证规则
 *     array(Access::GRANT, //本规则匹配成功后的控制结果
 *         'actions' => array('view', 'add'), //匹配view或add动作
 *         'roles' => Visitor::ROLE_LOGIN, //匹配Visitor的ROLE_LOGIN角色
 *     ),
 *     //第二条验证规则
 *     array(Access::AC_GRANT,
 *         'actions' => array('load'),
 *         'roles' => Visitor::ROLE_GUEST,
 *     ),
 *     //第三条验证规则
 *     array(Access::AC_GRANT,
 *         'actions' => array('load'),
 *         'ips' => array(12.4.56.35), //只有客户端IP为12.4.56.35时才允许访问
 *     ),
 *     //第四条验证规则，如果前面规则都没匹配，则默认拒绝访问
 *     array(Access::AC_DENY),
 * )   
 *   
 * @author samoay
 * @see Lark\Visitor
 * @see Lark\Controller
 *
 */
class Access{
	/**
	 * 常量：拒绝访问
	 * @var string
	 */
	const AC_DENY = 'DENY';
	
	/**
	 * 常量：授权访问
	 * @var string
	 */
	const AC_GRANT = 'GRANT';
	
	/**
	 * 当前请求携带的访客对象
	 * @var Lark\Visitor
	 */
	private $visitor;
	
	/**
	 * 当前控制器定义的访问权限规则
	 * @var Array
	 * @see Lark\Controller::accessRules
	 */
	private $rules;
	
	/**
	 * 当前请求对象封装，便于读取请求的HTTP方法、action动作、客户端IP
	 * @var Lark\Request
	 */
	private $request;
	
	/**
	 * 构造方法，传入访问权限规则、访客对象和请求对象，以便后续进行规则验证
	 * 
	 * @param array $accessRules 访问权限规则
	 * @param Lark\Visitor $visitor 访客对象
	 * @param Lark\Request $request 请求对象
	 */
	public function __construct(Array $accessRules = array(), Visitor $visitor = null, Request $request){
		$this->rules   = $accessRules;
		$this->visitor = $visitor;
		$this->request = $request;
	}
	
	/**
	 * 具体的访问权限验证，默认结果为AC_DENY，也就是说如果没有定义任何规则或者没有匹配到任何
	 * 规则时，将拒绝访问
	 * 
	 * @return boolean 验证结果为AC_GRANT时返回true，为AC_DENY时返回false
	 */
	public function filter(){
		$result = self::AC_DENY;
		
		foreach ($this->rules as $rule){
			if (!empty($rule['verbs']) && is_array($rule['verbs'])){
				if (!in_array($this->request->method, $rule['verbs'])){
					continue;
				}
			}
			
			if (!empty($rule['roles']) && is_int($rule['roles'])){
				if ($this->visitor->role != $rule['roles']){
					continue;
				}
			}
			
			if (!empty($rule['labels']) && is_array($rule['labels'])){
				$ownlabels = $this->visitor->labels;
				$intersect = array_intersect($ownlabels, $rule['labels']);
				if (!is_array($intersect) || count($intersect)==0){
					continue;
				}
			}
			
			if (!empty($rule['actions']) && is_array($rule['actions'])){
				if (!in_array($this->request->action, $rule['actions'])){
					continue;
				}
			}
			
			if (!empty($rule['users']) && is_array($rule['users'])){
				if (!in_array($this->visitor->id, $rule['users'])){
					continue;
				}
			}
			
			if (!empty($rule['ips']) && is_array($rule['ips'])){
				if (!in_array($this->request->getIP(), $rule['ips'])){
					continue;
				}
			}
			
			$result = $rule[0];
			break;
		}
		
		return $result == self::AC_GRANT ? true : false;
	}
}