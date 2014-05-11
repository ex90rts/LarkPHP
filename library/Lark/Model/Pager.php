<?php
namespace Lark\Model;

use Lark\Model;
use Lark\Template;
use Lark\Util;

class Pager{
	/**
	 * Basic styles
	 * @var string
	 */
	const STYLE_GROUP  = 'group';
	const STYLE_NEARBY = 'nearby';
	
	/**
	 * Lark data model instance
	 * @var Lark\Model
	 */
	private $model;
	
	private $pk = 'page';
	
	private $page = 1;
	
	private $url = '';
	
	private $query = '';
	
	private $size = 15;
	
	private $countParams = array();
	
	private $selectParams = array();
	
	private $ajax = false;
	
	private $template = 'default';
	
	private $style = self::STYLE_GROUP;
	
	private $classes = 'ui pagination';
	
	private $onepage = 'hide';
	
	private $count = 0;
	
	private $maxpage = 1;
	
	private $near = 5;
	
	private $endpoint = true;
	
	private $groupnum = 10;
	
	private $groupmax = 1;
	
	private $groupidx = 1;
	
	private $recordType = 'array';
	
	private $records = array();
	
	public function __construct(Model $model, $options=array()){
		$this->model    = $model;
		
		$this->pk       = isset($options['pk']) ? $options['pk'] : 'page';
		$this->page     = (isset($_GET[$this->pk]) && is_numeric($_GET[$this->pk])) ? intval($_GET[$this->pk]) : 1;
		
		$query = '';
		if (!empty($options['url'])){
			list($url, $query) = explode("?", $options['url']);
		}else{
			$uriparts = explode("?", $_SERVER['REQUEST_URI']);
			$url = $uriparts[0];
			if (isset($uriparts[1])){
			    $query = $uriparts[1];
			}
		}
		$this->url = $url;
		parse_str($query, $this->query);
		
		$this->size     = isset($options['size']) ? $options['size'] : 15;
		$this->near     = isset($options['near']) ? $options['near'] : 5;
		$this->endpoint = isset($options['endpoint']) ? $options['endpoint'] : true;
		$this->groupnum = isset($options['groupnum']) ? $options['groupnum'] : 10;
		$this->template = isset($options['template']) ? $options['template'] : 'default';
		$this->style    = isset($options['style']) ? $options['style'] : self::STYLE_GROUP;
		$this->classes  = isset($options['classes']) ? $options['classes'] : 'ui pagination';
		$this->onepage  = isset($options['onepage']) ? $options['onepage'] : 'hide';
		
		if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			$this->ajax = true;
		}
		if (isset($options['ajax'])){
			$this->ajax = true;
		}
	}
	
	public function setPk($pk){
		$this->pk = $pk;
	}
	
	public function setPage($page){
		$this->page = $page;
	}
	
	public function setUrl($fullurl){
		list($url, $query) = explode("?", $fullurl);
		$this->url = $url;
		parse_str($query, $this->query);
	}
	
	public function setSize($size){
		$this->size = $size;
	}
	
	public function setNear($near){
		$this->near = $near;
	}
	
	public function setEndpoint($endpoint){
		$this->endpoint = $endpoint;
	}
	
	public function setGroupnum($groupnum){
		$this->groupnum = $groupnum;
	}
	
	public function setStyle($style){
		$this->style = $style;
	}
	
	public function setTemplate($template){
		$this->template = $template;
	}
	
	public function setClasses($classes){
		$this->classes = $classes;
	}
	
	public function setOnepage($onepage){
		$this->onepage = $onepage;
	}
	
	public function setCountFields($fields){
		$this->countParams['fields'] = $fields;
	}
	
	public function setSelectFields($fields){
		$this->selectParams['fields'] = $fields;
	}
	
	public function setWhere($where){
		$this->countParams['where'] = $where;
		$this->selectParams['where'] = $where;
	}
	
	public function setGroup($group){
		$this->countParams['group'] = $group;
		$this->selectParams['group'] = $group;
	}
	
	public function setOrder($order){
		$this->selectParams['order'] = $order;
	}
	
	public function setHaving($having){
		$this->countParams['having'] = $having;
		$this->selectParams['having'] = $having;
	}
	
	public function setRecordType($type){
		$this->recordType = $type;
	}
	
	public function makeLink($page){
		$this->query[$this->pk] = $page;
		return Util::inputFilter($this->url) . "?" . http_build_query($this->query);
	}
	
	public function getRecords(){
		return $this->records;
	}
	
	public function paging($page=null){
		if (is_numeric($page)){
			$this->page = $page;
		}
		
		$count = $this->model->countData($this->countParams);
		if ($count){
			$this->count = intval($count);
		}
		
		$maxpage = ceil($this->count / $this->size);
		$this->maxpage = $maxpage>0 ? $maxpage : 1;
		if ($this->page > $this->maxpage){
			$this->page = $this->maxpage;
		}
		
		if ($this->style == self::STYLE_GROUP){
			$this->groupmax = ceil($this->maxpage / $this->groupnum);
			$this->groupidx = ceil($this->page / $this->groupnum);
		}
		
		$isArray = $this->recordType == 'array' ? true : false;
		
		$offset = ($this->page - 1)*$this->size;
		$this->selectParams['offset'] = $offset;
		$this->selectParams['limit'] = $this->size;
		$this->records = $this->model->findAllFullaware($this->selectParams, $isArray);
		
		$result = array(
			'count' => $this->count,
			'maxpage' => $this->maxpage,
			'size' => $this->size,
			'groupnum' => $this->groupnum,
			'groupmax' => $this->groupmax,
			'groupidx' => $this->groupidx,
			'style' => $this->style,
			'page' => $this->page,
			'near' => $this->near,
		);
		
		if (!$this->ajax){
			do{
				if ($this->onepage=='hide' && $this->maxpage<2){
					$result = '';
					break;
				}
				
				if ($this->template!='default'){
					$template = new Template($this->template);
					$template->assign('pager', $this);
					$template->batchAssign($result);
					$result = $template->render();
				}else{
					$result = "<ul class=\"{$this->classes}\">\r\n";
					if ($this->style == self::STYLE_GROUP){
						if ($this->groupmax > 1){
							$disabled  = $this->groupidx==1 ? "disabled" : "";
							$firstLink = $this->groupidx==1 ? "#" : $this->makeLink(1);
							$prevLink  = $this->groupidx==1 ? "#" : $this->makeLink(($this->groupidx-2)*$this->groupnum + 1);
							$result   .= "<li class=\"nav first margin-right-10 {$disabled}\"><a href=\"{$firstLink}\" class=\"link\"><i class=\"icon grey double-arrow left size-16x13\"></i></a></li>\r\n";
							$result   .= "<li class=\"nav prev {$disabled}\"><a href=\"{$prevLink}\" class=\"link\"><i class=\"icon grey arrow left size-9x13\"></i></a></li>\r\n";
						}
						$firstPage = ($this->groupidx - 1) * $this->groupnum + 1;
						$lastPage  = $this->groupidx * $this->groupnum;
						if ($lastPage > $this->maxpage){
							$lastPage = $this->maxpage;
						}
						for ($page=$firstPage; $page<=$lastPage; $page++){
							$selected = $page==$this->page ? "selected" : "";
							$pageLink = $page==$this->page ? "#" : $this->makeLink($page);
							$result  .= "<li class=\"page {$selected}\"><a href=\"{$pageLink}\" class=\"link\">{$page}</a></li>";
						}
						if ($this->groupmax > 1){
							$disabled  = $this->groupidx==$this->groupmax ? "disabled" : "";
							$nextLink  = $this->groupidx==$this->groupmax ? "#" : $this->makeLink($this->groupidx * $this->groupnum + 1);
							$lastLink  = $this->groupidx==$this->groupmax ? "#" : $this->makeLink(($this->groupmax-1)*$this->groupnum + 1);
							$result   .= "<li class=\"nav next {$disabled}\"><a href=\"{$nextLink}\" class=\"link\"><i class=\"icon grey arrow right size-9x13\"></i></a></li>\r\n";
							$result   .= "<li class=\"nav last margin-left-10 {$disabled}\"><a href=\"{$lastLink}\" class=\"link\"><i class=\"icon grey double-arrow right size-16x13\"></i></a></li>\r\n";
						}
					}else{
						$disabled  = $this->page==1 ? "disabled" : "";
						$firstLink = $this->page==1 ? "#" : $this->makeLink(1);
						$prevLink  = $this->page==1 ? "#" : $this->makeLink($this->page-1);
						$result   .= "<li class=\"nav first margin-right-10 {$disabled}\"><a href=\"{$firstLink}\" class=\"link\"><i class=\"icon grey double-arrow left size-16x13\"></i></a></li>\r\n";
						$result   .= "<li class=\"nav prev {$disabled}\"><a href=\"{$prevLink}\" class=\"link\"><i class=\"icon grey arrow left size-9x13\"></i></a></li>\r\n";
						
						$firstPage = $this->page - $this->near;
						if ($firstPage < 1){
							$firstPage = 1;
						}
						$lastPage = $this->page + $this->near;
						if ($lastPage > $this->maxpage){
							$lastPage = $this->maxpage;
						}
						for ($page=$firstPage; $page<=$lastPage; $page++){
							$selected = $page==$this->page ? "selected" : "";
							$pageLink = $page==$this->page ? "#" : $this->makeLink($page);
							$result  .= "<li class=\"page {$selected}\"><a href=\"{$pageLink}\" class=\"link\">{$page}</a></li>";
						}
						
						$disabled  = $this->page==$this->maxpage ? "disabled" : "";
						$nextLink  = $this->page==$this->maxpage ? "#" : $this->makeLink($this->page + 1);
						$lastLink  = $this->page==$this->maxpage ? "#" : $this->makeLink($this->maxpage);
						$result   .= "<li class=\"nav next {$disabled}\"><a href=\"{$nextLink}\" class=\"link\"><i class=\"icon grey arrow right size-9x13\"></i></a></li>\r\n";
						$result   .= "<li class=\"nav last margin-left-10 {$disabled}\"><a href=\"{$lastLink}\" class=\"link\"><i class=\"icon grey double-arrow right size-16x13\"></i></a></li>\r\n";
					}
					$result .= "</ul>";
				}
			}while (false);
		}
		
		return $result;
	}
}