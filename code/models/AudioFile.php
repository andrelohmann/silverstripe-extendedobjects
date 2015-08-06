<?php

/**
 * Represents an Image
 *
 * @package framework
 * @subpackage filesystem
 */
class AudioFile extends File {
	
	private static $db = array(
        'ProcessingStatus' => "Enum(array('new','processing','error','finished'))",
        'Duration' => 'Time'
    );
        
    private static $defaults = array(
		'ProcessingStatus' => 'new'
    );
        
    public function isProcessed(){
		return ($this->ProcessingStatus == 'finished');
    }
    
        /**
	 * @config
	 * @var array List of allowed file extensions, enforced through {@link validate()}.
	 * 
	 * Note: if you modify this, you should also change a configuration file in the assets directory.
	 * Otherwise, the files will be able to be uploaded but they won't be able to be served by the
	 * webserver.
	 * 
	 *  - If you are running Apahce you will need to change assets/.htaccess
	 *  - If you are running IIS you will need to change assets/web.config 
	 *
	 * Instructions for the change you need to make are included in a comment in the config file.
	 */
	private static $allowed_extensions = array(
		'mp3','ogg'
	);
        
    public function onAfterLoad(){
		// http://www.davenewson.com/dev/methods-for-asynchronous-processes-in-php
        $cmd = "nohup php ".FRAMEWORK_PATH."/cli-script.php AudioFileDataExtractionTask AudioFileID=".$this->ID." >> ".TEMP_FOLDER."/AudioFilesTaskLog.log & echo $!";
        $pid = shell_exec($cmd);
    }
        
    // process the Video
    public function process($LogFile = false, $runAfterProcess = true){
            
		if(!$LogFile) $LogFile = TEMP_FOLDER.'/AudioFileProcessing-ID-'.$this->ID.'-'.md5($this->getRelativePath()).'.log';
            
        if($this->ProcessingStatus == 'new'){
			$this->ProcessingStatus = 'processing';
            $this->write();
                
            $Message = "[LOGTIME: ".date("Y-m-d H:i:s")."]\nProcessing for File ".$this->getRelativePath()." started\n\n";
            // Write the contents to the logfile, 
            // using the FILE_APPEND flag to append the content to the end of the file
            // and the LOCK_EX flag to prevent anyone else writing to the file at the same time
            file_put_contents($LogFile, $Message, FILE_APPEND | LOCK_EX);
                
            // Audio Object
            $ffprobe = FFMpeg\FFProbe::create();
            $audio = $ffprobe->format($this->getFullPath());
                
            // read data
            $return = $this->processAudioInformation($audio, $LogFile);
			
			if($return && $runAfterProcess && $this->ProcessingStatus = 'finished') $this->onAfterProcess();
			
			return $return;
        }else{
			$Message = "[LOGTIME: ".date("Y-m-d H:i:s")."]\nFile allready processed\n";
            file_put_contents($LogFile, $Message, FILE_APPEND | LOCK_EX);
			return false;
        }
    }
        
    private function processAudioInformation($audio, $LogFile){
            
		try{
			$Message = "[LOGTIME: ".date("Y-m-d H:i:s")."]\nExtracted Information:\n";
            $Message.= sprintf("file name = %s\n", $audio->get("filename"));
            $Message.= sprintf("size = %s\n", $audio->get("size"));
            $Message.= sprintf("duration = %s seconds\n", $audio->get("duration"));
            $Message.= sprintf("bit rate = %d\n", $audio->get("bit_rate"));
            $Message.= sprintf("format name = %s\n", $audio->get("format_name"));
            $Message.= sprintf("format long name = %s\n", $audio->get("format_long_name"));
                
            $Message.= "\n";
                
            // Log the next message
            file_put_contents($LogFile, $Message, FILE_APPEND | LOCK_EX);
                
            $this->Duration = gmdate("H:i:s", $audio->get('duration'));
            //$this->Width = $mov->getFrameWidth();
            //$this->Height = $mov->getFrameHeight();
                
            $this->ProcessingStatus = 'finished';
            $this->write();
                
            return true;
        
		}catch(Exception $e){
        
			$Message = "[LOGTIME: ".date("Y-m-d H:i:s")."]\nERROR ON - Extracted Information:\n\n";
            file_put_contents($LogFile, $Message, FILE_APPEND | LOCK_EX);
            // log exception
            file_put_contents($LogFile, $e->getMessage(), FILE_APPEND | LOCK_EX);
                
            $this->ProcessingStatus = 'error';
            $this->write();
             
            return false;
        }
    }
	
	protected function onAfterProcess() {
		$this->extend('onAfterProcess');
	}
}