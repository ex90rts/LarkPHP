<?php
namespace Knock\Action\Post;

use Flexper\Env;
use Flexper\Action;
use Michelf\Markdown;
use Flexper\Mysql\Query;

class View extends Action{
	function execute(){
		$request = $this->request;
		$response = $this->response;
		
		$uid = $request->uid;
		$mysql = Env::getInstance('\Flexper\Mysql');
		$query = new Query();
		$query->table('Posts')->select()->where(array('uid'=>$request->uid));
		$article = $mysql->exec($query);
		if ($article){
			$article = $article[0];
			$response->id = $article['id'];
			$response->title = $article['title'];
			$response->htmlContent = Markdown::defaultTransform($article['content']);
			
			$tags = array();
			$query = new Query();
			$query->table('Tagconnects')->select()->where(array('postUid'=>$request->uid));
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
			$response->tags = $tags;
			
			$response->template('post/view.php');
		}else{
			echo "Post not found";
		}
	}
}