<?php

/* 
 * Extract 
 */
class VideoFileDataExtractionTask extends CliController {
    
    /**
     * Overload this method to contain the task logic.
     */
    public function process() {
        if(isset($_REQUEST['VideoFileID']) && $VideoFile = VideoFile::get()->byID($_REQUEST['VideoFileID'])){
            
            $VideoFile->process();
        }
    }
}

