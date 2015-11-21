<?php
/**
 * GeoFunctions
 * 
 * Includes Helpful Functions for 
 * 
 * @package geoform
 */
class GeoFunctions {
    
    public static $Latitude = 'Latitude';
    public static $Longditude = 'Longditude';
    
    /**
     * errechnet, wieviel Grad Länge bei einem gegebenen Grad Breite = 1KM entsprechen
     * 
     */
    public static function calcOneOnLng($geo_lat, $scale = 'km'){
        switch(strtolower($scale)){
            case 'miles':
                $earth = 3960;
            break;

            case 'km':
            default:
                $earth = 6371;
            break;
        }
        
        //Point 1 cords
        $lat = deg2rad($geo_lat);
        $long1 = deg2rad(0);
        $long2 = deg2rad(1);
        
        //Haversine Formula
        $dlong = $long2 - $long1;
        $sinlong = sin($dlong / 2);
        
        $a = cos($lat) * cos($lat) * ($sinlong * $sinlong);
        $c = 2 * asin(min(1, sqrt($a)));
        
        return (1/($earth * $c));
    }
    
    public static function getDistance($lat1, $long1, $lat2, $long2, $scale = 'km'){
        switch(strtolower($scale)){
            case 'miles':
                $earth = 3960;
            break;

            case 'km':
            default:
                $earth = 6371;
            break;
        }

        //Point 1 cords
        $lat1 = deg2rad($lat1);
        $long1 = deg2rad($long1);
        //Point 2 cords
        $lat2 = deg2rad($lat2);
        $long2 = deg2rad($long2);

        //Haversine Formula
        $dlong = $long2 - $long1;
        $dlat = $lat2 - $lat1;
        $sinlat = sin($dlat / 2);
        $sinlong = sin($dlong / 2);

        $a = ($sinlat * $sinlat) + cos($lat1) * cos($lat2) * ($sinlong * $sinlong);

        $b = 2 * asin(min(1, sqrt($a)));

        return ($earth * $b);
    }

    public static function getSquare($geo_lat, $geo_lng, $radius, $scale = 'km'){
        // damit wir auch auf der sicheren Seite sind
        $radius = $radius + 1;
        switch(strtolower($scale)){
            case 'miles':
                $one = 0.01446863119016716418;
            break;

            case 'km':
            default:
                $one = 0.0089932160592;
            break;
        }

        $adToLat = $one * $radius;
        // über 90 oder unter -90 grad
        if(($geo_lat + $adToLat) > 90){
            // das ganze ist überm Nordpol, lng kann auf -+180 gesetzt werden
            $lng_left = -180;
            $lng_right = 180;
            $lat_up = 90;
            $lat_down = $geo_lat - $adToLat;
        }elseif(($geo_lat - $adToLat) < -90){
            // überm Südpol
            $lng_left = -180;
            $lng_right = 180;
            $lat_up = $geo_lat + $adToLat;
            $lat_down = -90;
        }else{
            // berechnung kann los gehen
            $lat_up = $geo_lat + $adToLat;
            $lat_down = $geo_lat - $adToLat;
            if(abs($lat_down) > $lat_up){
                $adToLng = self::calcOneOnLng($lat_down, $scale) * $radius;
            }else{
                $adToLng = self::calcOneOnLng($lat_up, $scale) * $radius;
            }
            // jetzt noch das 180 Grad Problem
            if((($geo_lng - $adToLng) < -180) || (($geo_lng + $adToLng) > 180)){
                if(($total = ($geo_lng - $adToLng)) < -180){
                    $lng_left = (180 - abs($total + 180));
                }else{
                    $lng_left = $geo_lng - $adToLng;
                }

                if(($total = ($geo_lng + $adToLng)) > 180){
                    $lng_right = (-180 + abs($total - 180));
                }else{
                    $lng_right = $geo_lng + $adToLng;
                }
            }else{
                $lng_left = $geo_lng - $adToLng;
                $lng_right = $geo_lng + $adToLng;
            }
        }
        return array('UpperLeft' => array('Latitude' => $lat_up, 'Longditude' => $lng_left), 'LowerRight' => array('Latitude' => $lat_down, 'Longditude' => $lng_right));
    }

    public static function getSQLSquare($geo_lat, $geo_lng, $radius, $scale = 'km'){
        $square = self::getSquare($geo_lat, $geo_lng, $radius, $scale);
        if($square['UpperLeft']['Longditude'] < $square['LowerRight']['Longditude']){
            return '\''.$square['LowerRight']['Latitude'].'\' < '.self::$Latitude.' AND '.self::$Latitude.' < \''.$square['UpperLeft']['Latitude'].'\' AND \''.$square['UpperLeft']['Longditude'].'\' < '.self::$Longditude.' AND '.self::$Longditude.' < \''.$square['LowerRight']['Longditude'].'\'';
        }else{
            return '\''.$square['LowerRight']['Latitude'].'\' < '.self::$Latitude.' AND '.self::$Latitude.' < \''.$square['UpperLeft']['Latitude'].'\' AND (\''.$square['UpperLeft']['Longditude'].'\' < '.self::$Longditude.' OR '.self::$Longditude.' < \''.$square['LowerRight']['Longditude'].'\')';
        }
    }
    
    public static function getSQLSquareByBox($square = array('UpperLeft' => array('Latitude' => false, 'Longditude' => false), 'LowerRight' => array('Latitude' => false, 'Longditude' => false))){
        if($square['UpperLeft']['Longditude'] < $square['LowerRight']['Longditude']){
            return '\''.$square['LowerRight']['Latitude'].'\' < '.self::$Latitude.' AND '.self::$Latitude.' < \''.$square['UpperLeft']['Latitude'].'\' AND \''.$square['UpperLeft']['Longditude'].'\' < '.self::$Longditude.' AND '.self::$Longditude.' < \''.$square['LowerRight']['Longditude'].'\'';
        }else{
            return '\''.$square['LowerRight']['Latitude'].'\' < '.self::$Latitude.' AND '.self::$Latitude.' < \''.$square['UpperLeft']['Latitude'].'\' AND (\''.$square['UpperLeft']['Longditude'].'\' < '.self::$Longditude.' OR '.self::$Longditude.' < \''.$square['LowerRight']['Longditude'].'\')';
        }
    }
    
    public static function checkLatLng($geo_lat, $geo_lng){
        if(((-90 <= $geo_lat) && ($geo_lat <= 90)) && ((-180 <= $geo_lng) && ($geo_lng <= 180))){
            return true;
        }else{
            return false;
        }
    }

    public static function getMiddle($UpperLeft = array('Latitude' => false, 'Longditude' => false), $LowerRight = array('Latitude' => false, 'Longditude' => false)){
        $Middle['Latitude'] = ($LowerRight['Latitude'] + (($UpperLeft['Latitude'] - $LowerRight['Latitude']) / 2));
        $Middle['Longditude'] = ($UpperLeft['Longditude'] + (($LowreRight['Longditude'] - $UpperLeft['Longditude']) / 2));
        return $Middle;
    }
}