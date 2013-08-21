<?php
namespace Knock\Action\User;

use Alcedoo\Action;

class Logout extends Action{
    function execute(){
        $this->response->setSession('name', null);
        $this->response->setSession('group', null);
        $this->response->setSession('permissions', null);
        
        $this->response->text('Logout Succed');
    }
}