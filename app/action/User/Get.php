<?php
namespace Knock\Action\User;

use Lark\Util;

use Lark\Mysql\Query;

use Lark\Uniqid;
use Lark\Env;
use Lark\Action;

class Get extends Action{
	var $Samoay;
	
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

        $response->users = $data;
        $response->template('users.php');

        $this->logger()->appDebug(array('api'=>'user/get', 'page'=>$page));
        $this->logger()->logDebug("test log");
    }

    function redirect(){
        $this->response->redirect('goto', '/test/index.php?action=user/login');
    }
}