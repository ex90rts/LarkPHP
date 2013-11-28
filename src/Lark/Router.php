<?php
namespace Lark;

use Lark\Env;
use Lark\Request;
use Lark\Response;
use Lark\Exception\PathNotFoundException;

class Router{
    /**
     * Defaunlt ruouter of the framework
     * @throws PathNotFoundException
     */
    public static function routeAction(){
        $request = new Request();
        $response = new Response();

        try{
            $parts = explode('/', $request->action);
            $partsUcfirst = array(
            	Env::getOption('namespace'),
            	'Action',
            );
            foreach ($parts as $part){
                $partsUcfirst[] = ucfirst($part);
            }
            $actionClass = implode('\\', $partsUcfirst);
            $action = new $actionClass($request, $response);
            $action->init();
            $action->validate();
	        $res = $action->checkPermissions();
	        if ($res){
	        	$action->execute();
	        }else{
	            $action->redirect(REDIRECT_PERMISSION);
	        }
	        $action->finish();
        }catch(Exception $e){
            echo '<pre>';
            echo 'Lark Defined Exception:'."\r\n";
            print_r($e);
            echo '</pre>';
        }catch(\Exception $e) {
            echo '<pre>';
            echo 'Upper Level Exception:'."\r\n";
            print_r($e);
            echo '</pre>';
        }
    }
    
    public static function routeController(){
    	$req = new Request();
        $res = new Response();
    	
    	//controller//action//id
    	try{
    		$parts = array(
    			Env::getOption('namespace'),
    			'Controller',
    			ucfirst($req->controller),
    		);
    		$class = implode('\\', $parts);
    		$action = ucfirst($req->action);
		
    		$controller = new $class($req, $res);
    		$controller->beforeAction();
    		$controller->executeAction($action);
    		$controller->afterAction();
    	}catch(Exception $e){
            echo '<pre>';
            echo 'Lark Defined Exception:'."\r\n";
            print_r($e);
            echo '</pre>';
        }catch(\Exception $e) {
            echo '<pre>';
            echo 'System Level Exception:'."\r\n";
            print_r($e);
            echo '</pre>';
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