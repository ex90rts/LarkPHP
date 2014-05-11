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
        $encoding = 'utf-8';
    	return mb_strlen($title, $encoding) > $len ? mb_substr($title, 0, $len, $encoding) . $pad : $title;
    }
    
    /**
     * Filter any input data with filter_var* to prevent xss hacking
     * 
     * @param mixed $value
     * @return mixed
     */
    public static function inputFilter($value){
    	if (is_array($value)){
    		return filter_var_array($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    	}else{
    		return filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    	}
    }
    
    /**
     * Send request with CURL
     * 
     * @param string $url
     * @param array $data
     * @param string $contentType
     * @return string|mixed
     */
    public static function makeRequest($url, $post=array(), $contentType='text/plain'){
        if(empty($url)){
            return false;
        }
    
        $ch = curl_init();
    
        curl_setopt($ch, CURLOPT_URL, $url);
        
        if (!empty($post)){
            if (is_array($post)){
                curl_setopt($ch, CURLOPT_POST, count($post));
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
            }else{
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: {$contentType}"));
            }
        }
        
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
        $result = curl_exec($ch);
        curl_close($ch);
        
        return $result;
    }
}