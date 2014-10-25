<?php

/* 
 * Extract 
 */
class AudioFileDataExtractionTask extends CliController {
    
    /**
     * Overload this method to contain the task logic.
     */
    public function process() {
        if(isset($_REQUEST['AudioFileID']) && $AudioFile = AudioFile::get()->byID($_REQUEST['AudioFileID'])){
            
            $AudioFile->process();
        }
    }
}

