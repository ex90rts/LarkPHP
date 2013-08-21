<?php
namespace Knock\Action;

use Alcedoo\Env;
use Alcedoo\Action;
use Alcedoo\Mysql\Query;
use Michelf\Markdown;

class Index extends Action{
	function execute(){
		$response = $this->response;
		$response->tab = 'HOME';
		
		$mysql = Env::getInstance('\Alcedoo\Mysql');
		$query = new Query();
		$query->table('Posts')->select()->order(array('id'=>'DESC'))->limit(10);
		$posts = $mysql->exec($query);
		
		$list = array();
		foreach ($posts as $post){
			$article = array();
			$article['id'] = $post['id'];
			$article['uid'] = $post['uid'];
			$article['title'] = $post['title'];
			$article['htmlContent'] = Markdown::defaultTransform($post['content']);
			$article['created'] = $post['created'];
				
			$tags = array();
			$query = new Query();
			$query->table('Tagconnects')->select()->where(array('postUid'=>$post['uid']));
			$connects = $mysql->exec($query);
			if ($connects){
				foreach ($connects as $connect){
					$query = new Query();
					$query->table('Tags')->select()->where(array('uid'=>$connect['tagUid']));
					$tag = $mysql->exec($query);
					if ($tag){
						$tags[] = current($tag);
					}
				}
			}
			$article['tags'] = $tags;
			
			$list[] = $article;
		}
		
		$response->list = $list;
		
		$response->template('index.php');
	}
}