<?php
namespace Flexper;

/**
 * Class to unsion common util functions together
 * @author samoay
 *
 */
class Util{
    /**
     * Static var to save current unix timestamp in script
     * @var unknown_type
     */
    private static $_now;
    
    /**
     * Get current system unix timestamp
     * @return \Flexper\unknown_type
     */
    public static function getNow(){
        if (empty(self::$_now)) {
            self::$_now = isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time();
        }
        
        return self::$_now;
    }
    
}