<?php

class ExtendedDate extends DataExtension {
    
    /**
     * Gets the time difference, but always returns it in a certain format
     * @param string $format The format, could be one of these: 
     * 'seconds', 'minutes', 'hours', 'days', 'months', 'years'.
     * 
     * @return string
     */
    public function DiffIn($format = 'default'){
        if($this->owner->value){
            $now = new DateTime();
            $timestamp = strtotime($this->owner->value);
            $value = new DateTime('@'.$timestamp);
            $diff = $value->diff($now);
            
            switch($format) {
                case "seconds":
                    return abs($value->getTimestamp() - $now->getTimestamp());
                break;
            
                case "minutes":
                    return abs(round(($value->getTimestamp() - $now->getTimestamp())/60));
                break;
             
                case "hours":
                    return abs(round(($value->getTimestamp() - $now->getTimestamp())/60/60));
                break;
            
                case "days":
                    return abs(round(($value->getTimestamp() - $now->getTimestamp())/60/60/24));
                break;
                
                case "months":
                    return $diff->format('%m') + ($diff->format('%y')*12);
                break;
                
                case "years":
                    return $diff->format('%y');
                break;
                
                default:
                    return $diff;
                break;
            }
        }
    }
    
    /**
     * Gets the time difference, but always returns it in a certain format
     * @param string $format The format, could be one of these: 
     * 'seconds', 'minutes', 'hours', 'days', 'months', 'years'.
     * 
     * @return string
     */
    public function AddSeconds($seconds){
        if($this->owner->value){
            $timestamp = strtotime($this->owner->value);
            $this->owner->setValue($timestamp + $seconds);
            return $this->owner->value;
        }
    }
}