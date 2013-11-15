<?php
namespace Alcedoo;

use Alcedoo\Env;
use Alcedoo\Exception\PathNotFoundException;

class Router{
    /**
     * Defaunlt ruouter of the framework
     * @throws PathNotFoundException
     */
    public static function routeAction(){
        $request = Env::getInstance('Alcedoo\Request');
        $response = Env::getInstance('Alcedoo\Response');

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
    	$request = Env::getInstance('Alcedoo\Request');
    	$response = Env::getInstance('Alcedoo\Response');
    	
    	//controller//action//id
    	try{
    		$uri = $request->getServer('REQUEST_URI');
    		$parts = explode('?', $uri);
    		$path = trim($parts[0], '/');
    		$pathParts = explode('/', $path);
    		$controller = !empty($pathParts[0]) ? $pathParts[0] : 'Index';
    		$action = isset($pathParts[1]) ? $pathParts[1] : 'View';
    		$partsUcfirst = array(
    				Env::getOption('namespace'),
    				'Controller',
    				ucfirst($controller),
    		);
    		$controllerClass = implode('\\', $partsUcfirst);
    		$controller = new $controllerClass($request, $response);
    		$controller->beforeAction();
    		$controller->$action();
    		$controller->afterAction();
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