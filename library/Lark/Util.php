<?php
namespace Lark;

/**
 * Class to gather all common util functions together
 * @author samoay
 *
 */
class Util{
    /**
     * Static var to hold current client IP in script
     * @var string
     */
    private static $_clientIP;
    
    /**
     * Get current system unix timestamp
     * @return int
     */
    public static function getNow(){
        //return isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time();
        return time();
    }
    
    /**
     * Get current system time string formated as Y-m-d H:i:s
     * @return datetime
     */
    public static function getDatetime(){
    	$now = self::getNow();
    	return date("Y-m-d H:i:s", $now);
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
    
    /**
     * Create random string in length $len
     * 
     * @param int $len the length of the string
     * @param boolean $readable if we need to exclude letters like 0,o,l,1 which might ambiguous when read
     */
    public static function randomString($len=5, $readable=false, $upper=false){
    	$seeds = "0123456789abcdefghijklmnopqrstuvwxyz";
    	if ($readable){
    		$seeds = "23456789abcdefghijkmnpqrstuvwxyz";
    	}
    	$str = '';
    	$wcd = strlen($seeds)-1;
    	for ($i=0; $i<$len; $i++){
    		$rand = mt_rand(0, $wcd);
    		$str .= substr($seeds, $rand, 1);
    	}
    	
    	return $upper ? strtoupper($str) : $str;
    }
    
    /**
     * Create random numeric string in length $len
     *
     * @param int $len the length of the string
     */
    public static function randomNumeric($len=5){
    	$seeds = "0123456789";
    	
    	$str = '';
    	$wcd = strlen($seeds)-1;
    	for ($i=0; $i<$len; $i++){
    		$rand = mt_rand(0, $wcd);
    		$str .= substr($seeds, $rand, 1);
    	}
    	 
    	return $str;
    }
    
    /**
     * Cut string with mb)substr
     * 
     * @param string $title
     * @param int $len
     * @param string $pad
     * @return string
     */
    public static function mbcutString($title, $len, $pad='...'){
    	return mb_strlen($title) > $len ? mb_substr($title, 0, $len) . $pad : $title;
    }
}