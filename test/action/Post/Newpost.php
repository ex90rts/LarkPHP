<?php
namespace Knock\Action\Post;

use Flexper\Action;
use Flexper\Env;
use Flexper\Constants;
use Flexper\Util;
use Flexper\Mysql\Query;
use Flexper\Uniqid;

class Newpost extends Action{
	function execute(){
		$request = $this->request;
		$response = $this->response;
		
		if (empty($request->title) || empty($request->content)){
			$response = $this->response;
			$response->tab = 'LOGIN';
			$response->template('post/newpost.php');
		}else{
			$title = $request->title;
			$tags = $request->tags;
			$content = $request->content;
			
			$mysql = Env::getInstance('\Flexper\Mysql');
			$mysql->transaction();
			
			$uniqid = new Uniqid();
			$postUid = $uniqid->create(Constants::UNIQID_TYPE_POST);
			
			$tags = str_replace(array('，', ';', '；', ' '), ',', $tags);
			$tagsArray = explode(',', $tags);
			
			$record = array(
				'uid' => $postUid,
				'title' => $title,
				'content' => $content,
				'created' => Util::getNow(),
			);
			
			$query = new Query(array('insertId'=>true));
			$query = $query->table('Posts')->insert($record);
			$res = $mysql->exec($query);
			if (!$res){
				$mysql->rollback();
				echo "insert post failed";
				return;
			}
			
			$mysql->commit();
			
			foreach ($tagsArray as $tag){
				$tagUid = $uniqid->create(Constants::UNIQID_TYPE_TAG);
				$record = array(
					'uid' => $tagUid,
					'tag' => $tag,
				);
				$query = new Query();
				$query->table('Tags')->insert($record);
				$mysql->exec($query);
				
				$record = array(
					'postUid' => $postUid,
					'tagUid' => $tagUid,
				);
				$query = new Query();
				$query->table('Tagconnects')->insert($record);
				$mysql->exec($query);
			}
			
			$this->response->redirect('goto', "/test/index.php?action=post/view&uid={$postUid}");
		}
	}
}