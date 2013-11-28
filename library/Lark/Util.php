<?php
namespace Lark;

/**
 * Class to gather all common util functions together
 * @author samoay
 *
 */
class Util{
    /**
     * Static var to hold current unix timestamp in script
     * @var unix_timestamp
     */
    private static $_now;
    
    /**
     * Static var to hold current client IP in script
     * @var string
     */
    private static $_clientIP;
    
    /**
     * Get current system unix timestamp
     * @return \Lark\unknown_type
     */
    public static function getNow(){
        if (empty(self::$_now)) {
            self::$_now = isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time();
        }
        
        return self::$_now;
    }
    
    /**
     * Get current client IP address
     * @return string
     */
    public static function getClientIP(){
    	if (empty(self::$_clientIP)){
    		self::$_clientIP = $_SERVER['REMOTE_ADDR'];
    	}
    	
    	return self::$_clientIP;
    }
}