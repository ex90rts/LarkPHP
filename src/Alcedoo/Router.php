<?php
namespace Alcedoo;

use Alcedoo\Env;
use Alcedoo\Request;
use Alcedoo\Response;
use Alcedoo\Exception\PathNotFoundException;

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
        }catch(AlcedooException $e){
            echo '<pre>';
            echo 'Alcedoo Defined Exception:'."\r\n";
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
    	$request = new Request();
        $response = new Response();
    	
    	//controller//action//id
    	try{
    		$partsUcfirst = array(
    			Env::getOption('namespace'),
    			'Controller',
    			ucfirst($request->controller),
    		);
    		$class = implode('\\', $partsUcfirst);
    		$action = "action".ucfirst($request->action);
		
    		$controller = new $class($request, $response);
    		if ($controller->beforeAction()){
    			$controller->$action();
    		}
    		$controller->afterAction();
    	}catch(AlcedooException $e){
            echo '<pre>';
            echo 'Alcedoo Defined Exception:'."\r\n";
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