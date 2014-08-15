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
                    return $diff->format('%s');
                break;
            
                case "minutes":
                    return $diff->format('%i');
                break;
             
                case "hours":
                    return $diff->format('%h');
                break;
            
                case "days":
                    return $diff->format('%d');
                break;
                
                case "months":
                    return $diff->format('%m');
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