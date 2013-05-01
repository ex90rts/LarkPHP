<?php
namespace Knock\Action\User;

use Flexper\Util;

use Flexper\Mysql\Query;

use Flexper\Uniqid;
use Flexper\Env;
use Flexper\Action;

class Get extends Action{
    function permission(){
        return true;
    }
    
    function execute (){
        $request = $this->request;
        $response = $this->response;
        
        $query = array();
        if (!empty($request->username)){
            $query['username'] = $request->username;
        }
        $limit = 5;
        $page = 1;
        if (!empty($request->page)){
            $page = $request->page;
        }
        $skip = ($page-1)*$limit;
        $data = $this->model('User')->query($query, $limit, $skip);
        
        //$response->users = $data;
        //$response->template('users.php');
        
        $query1 = new Query();
        $query1->table('JokeContent')
        	->hash(10015)
        	->insert(array(
        		'jokeId' => 10015,
        		'content' => 'wowowow',
        		'createTime' => Util::getNow(),
        	));
        echo $query1."\r\n";
        $mysql = Env::getInstance('Flexper\Mysql');
        $res = $mysql->exec($query1);
        var_dump($res);
        
        $this->logger()->appDebug(array('api'=>'user/get', 'page'=>$page));
        $this->logger()->logDebug("test");
    }
    
    function redirect(){
        $this->response->redirect('goto', '/test/index.php?action=user/login');
    }
}