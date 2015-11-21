<?php

/**
 * GoogleMaps Variable
 * @package geoform
 */
class GoogleMaps {
    /**
     * @var string $API_KEY
     */
    static protected $API_KEY = null;
    
    public static function setApiKey($key){
        self::$API_KEY = $key;
    }
    
    public static function getApiKey(){
        return self::$API_KEY;
    }
}
