<?php
namespace Lark;

use Lark\App;
use Lark\Request;
use Lark\Response;
use Lark\Exception\PathNotFoundException;

class Router{
    /**
     * Defaunlt ruouter of the framework
     * @throws PathNotFoundException
     */
    public static function routeAction(){
        $req = new Request();
        $res = new Response();

        try{
            $parts = explode('/', $request->action);
            $partsUcfirst = array(
            	App::getOption('namespace'),
            	'Action',
            );
            foreach ($parts as $part){
                $partsUcfirst[] = ucfirst($part);
            }
            $actionClass = implode('\\', $partsUcfirst);
            $action = new $actionClass($req, $res);
            $action->init();
            $action->validate();
	        $access = $action->checkPermissions();
	        if ($access){
	        	$action->execute();
	        }else{
	            $action->redirect(REDIRECT_PERMISSION);
	        }
	        $action->finish();
        }catch(\Exception $e) {
            $res->printr($e);
        }
    }
    
    public static function routeController(){
    	$cmdmode = App::$cmdmode;
    	$urlmapper = App::getUrlMapper();
	
    	$req = new Request();
        $res = new Response();
  	
        $classParts = array(
        	App::getOption('namespace'),
        	'Controller',
        );
        
        $matchs = array();
        if ($cmdmode){
        	$args = $_SERVER['argv'];
        	array_shift($args);
        	
        	$uri = $args ? array_shift($args) : '';
        	
        	foreach ($args as $arg){
        		list($name, $value) = explode("=", $arg);
        		$req->$name = $value;
        	}
        }else{
        	$uri = $_SERVER['REQUEST_URI'];
        	if (strpos($uri, 'favicon.ico')!==false){
        		die;
        	}
        }

        foreach ($urlmapper as $mapper){
        	if (preg_match($mapper[0], $uri, $matchs)){
        		if (isset($mapper[1]) && $mapper[1]){
        			$matchs = array_merge($mapper[1], $matchs);
        		}
        		break;
        	}
        }

        $req->controller = !empty($matchs['controller']) ? $matchs['controller'] : App::getOption('defController');
        $req->action = !empty($matchs['action']) ? $matchs['action'] : App::getOption('defAction');
        if (!empty($matchs['id'])){
        	$req->id = $matchs['id'];
        }
        
        $res->setController($req->controller);
        $res->setAction($req->action);
        
        if (!empty($matchs['category'])){
        	array_push($classParts, ucfirst($matchs['category']));
        }
        array_push($classParts, ucfirst($req->controller));

        $class = implode('\\', $classParts);
        
    	try{
    		$controller = new $class($req, $res);
    		$controller->executeAction();
    	}catch(Exception $e){
    		$req->controller = App::getOption('errController');
    		$req->action = App::getOption('errAction');
    		$res->exception = $e;
    		
    		$class = App::getOption('namespace').'\Controller\\'.ucfirst($req->controller);
    		$controller = new $class($req, $res);
    		$controller->executeAction();
        }catch(\Exception $e) {
            throw $e;
        }
    }

    public static function getPathInfo() {
        $uri = $_SERVER['REQUEST_URI'];
        $flag = '.php';
        $protalFilePos = strpos($uri, $flag);
        $paramPos = strpos($uri, '?');

        if ($protalFilePos === false) {
            $protalFilePos = 0;
        } else {
            $protalFilePos += strlen($flag);
        }

        if ($paramPos === false) {
            $paramPos = strlen($uri);
        } else {
            $paramPos = $paramPos - $protalFilePos;
        }
        $uri = substr($uri, $protalFilePos, $paramPos);
        return rtrim($uri, '/');
    }
}