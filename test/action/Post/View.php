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
		$query->table('Posts')->select();
		$article = $mysql->exec($query);print_r($article);die;
		$response->article = $article;
		$response->htmlContent = Markdown::defaultTransform($article['content']);
		$response->template('post/view.php');
	}
}