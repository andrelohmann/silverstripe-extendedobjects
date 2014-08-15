<?php
 
/**
 * extended_image/code/VideoImage.php
 * 
 * VideoImages are related to VideoFiles
 * 
 * After a VideFile gets uploaded the VideoImage will be created
 * 
 */
 
class VideoImage extends SecureImage {
    
        /**
         * 
         */
        private static $db = array(
            'Playtime' => 'Time'
        );
        
        private static $has_one = array(
            'VideoFile' => 'VideoFile'
        );
    
}