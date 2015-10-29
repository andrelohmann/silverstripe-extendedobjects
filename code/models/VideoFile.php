<?php

/**
 * Represents an Image
 *
 * @package framework
 * @subpackage filesystem
 */
class VideoFile extends File {
    
    private static $amount_preview_images = 10; // No of preview Images that should be made
	
	private static $db = array(
		'ProcessingStatus' => "Enum(array('new','processing','error','finished'))",
        'Width' => 'Int',
        'Height' => 'Int',
        'Duration' => 'Time'
    );
        
    private static $defaults = array(
		'ProcessingStatus' => 'new'
    );
        
    private static $has_one = array(
		'PreviewImage' => 'SecureImage'
    );
        
    private static $has_many = array(
		'TimelineImages' => 'VideoImage'
    );
	
	protected $log_file = null;
	
	public function DurationSeconds(){
		$h = $this->obj('Duration')->Format('G');
		$m = (int)$this->obj('Duration')->Format('i');
		$s = (int)$this->obj('Duration')->Format('s');
		return $s + (60 * $m) + (60 * 60 * $h);
	}
        
    public function PreviewThumbnail(){
		if($this->ProcessingStatus == 'finished' && $this->PreviewImageID > 0)
			return $this->PreviewImage()->setWidth(50);
        else
			return '(No Image)';
    }
        
    public function IsProcessed(){
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
		'avi','flv','m4v','mov','mp4','mpeg','mpg','ogv','webm','wmv'
	);
        
    public function CMSThumbnail() {
		if($this->PreviewImageID > 0 && $Img = $this->PreviewImage()) return $this->PreviewImage()->getFormattedImage('CMSThumbnail');
		return parent::CMSThumbnail();
	}
        
    /*public function Thumbnail($width, $height) {
		//if($this->PreviewImageID > 0){
		//	$ResizedPreview = $this->PreviewImage()->SetRatioSize($width, $height);
		//	return $ResizedPreview->getURL();
		//}
        return $this->Icon();
	}
	
	public function getThumbnail($width, $height){
		return $this->Thumbnail($width, $height);
	}*/
        
    public function Resolution(){
		return $this->Width."x".$this->Height;
    }
	
	protected function onBeforeDelete(){
		parent::onBeforeDelete();
		
		if($this->PreviewImageID > 0) $this->PreviewImage()->delete();

		foreach($this->TimelineImages() as $Image){
			$TimelineImagesFolder = $Image->Parent();
            $Image->delete();
        }
		
		$TimelineImagesFolder->delete();
	}
	
	public function onAfterLoad(){
		// http://www.davenewson.com/dev/methods-for-asynchronous-processes-in-php
        $cmd = "nohup php ".FRAMEWORK_PATH."/cli-script.php VideoFileDataExtractionTask VideoFileID=".$this->ID." >> ".TEMP_FOLDER."/VideoFilesTaskLog.log & echo $!";
        $pid = shell_exec($cmd);
    }
	
	protected function getLogFile(){
		if(!$this->log_file){
			$this->log_file = TEMP_FOLDER.'/VideoFileProcessing-ID-'.$this->ID.'-'.md5($this->getRelativePath()).'.log';
		}
		return $this->log_file;
	}
	
	protected function appendLog($LogFile, $message, $extraString = false){
		$Message = "[LOGTIME: ".date("Y-m-d H:i:s")."]\n".$message."\n\n";
		if($extraString) $Message.= $extraString."\n\n";
		// Write the contents to the logfile, 
        // using the FILE_APPEND flag to append the content to the end of the file
        // and the LOCK_EX flag to prevent anyone else writing to the file at the same time
        file_put_contents($LogFile, $Message, FILE_APPEND | LOCK_EX);
	}
	
	// process the Video
    public function process($LogFile = false, $runAfterProcess = true){
            
		if(!$LogFile) $LogFile = $this->getLogFile();
            
        if($this->ProcessingStatus == 'new'){
			$this->ProcessingStatus = 'processing';
            $this->write();
                
            $this->appendLog($LogFile, "Processing for File ".$this->getRelativePath()." started");
            
			try{
				// Movie Object
				$ffprobe = FFMpeg\FFProbe::create();
				$mov = $ffprobe->format($this->getFullPath());
				//$ffmpeg = FFMpeg\FFMpeg::create();
				//$video = $ffmpeg->open($this->getFullPath());
				//$video->frame(FFMpeg\Coordinate\TimeCode::fromSeconds(10))->save($this->getFullPath().'.jpg');
			} catch (Exception $ex) {
				$Message = "ERROR ON - FFProbe:";
				$this->appendLog($LogFile, $Message, $e->getMessage());
                
				$this->ProcessingStatus = 'error';
				$this->write();
                
				return false;
			}
                
            // read data
            if($this->processVideoInformation($mov, $LogFile)){
				// private Information returned no error
                $result = $this->extractTimelineImages($mov, $LogFile);
				
				if($result && $runAfterProcess && $this->ProcessingStatus == 'finished') $this->onAfterProcess();
				
				return $result;
            }else{
				return false;
			}
        }else{
			$this->appendLog($LogFile, "File allready processed");
			return false; 
        }
    }
        
    private function processVideoInformation($mov, $LogFile){
            
		try{
			$Message = "Extracted Information:\n";
            $Message.= sprintf("file name = %s\n", $mov->get("filename"));
            $Message.= sprintf("size = %s\n", $mov->get("size"));
            $Message.= sprintf("duration = %s seconds\n", $mov->get("duration"));
            $Message.= sprintf("bit rate = %d\n", $mov->get("bit_rate"));
            $Message.= sprintf("format name = %s\n", $mov->get("format_name"));
            $Message.= sprintf("format long name = %s\n", $mov->get("format_long_name"));
                
            $Message.= "\n";
			$this->appendLog($LogFile, $Message);
                
            $this->Duration = gmdate("H:i:s", $mov->get('duration'));
            //$this->Width = $mov->getFrameWidth();
            //$this->Height = $mov->getFrameHeight();
            $this->write();
                
            return true;
        }catch(Exception $e){
			$Message = "ERROR ON - Extracted Information:";
			$this->appendLog($LogFile, $Message, $e->getMessage());
                
            $this->ProcessingStatus = 'error';
            $this->write();
                
            return false;
        }
    }
        
    private function extractTimelineImages($mov, $LogFile){
		// generate an array of timestamps on where to extract a file
        $timestamps = array();
        // get the first frame
        $timestamps[] = '00:00:00'; // H:i:s
            
        // calculate total playtime in seconds devided by (no of previews - 1)
        $stepsize = $mov->get('duration') / (Config::inst()->get('VideoFile', 'amount_preview_images') - 1);
        for($i = 1; $i<Config::inst()->get('VideoFile', 'amount_preview_images'); $i++){
            $timestamps[] = gmdate("H:i:s", ($stepsize * $i));
        }
            
        $Message = "Start TimelineImage extraction:";
		$this->appendLog($LogFile, $Message);
        
		try{
			// Old Algorithm calculated by sizes
			/*$sizes = array();
                
            foreach($timestamps as $stamp){
				if($size = $this->extractTimelineImage($LogFile, $stamp)){
					$sizes[$size['Size']] = $size['ID'];
				}
            }
            krsort($sizes);
            $this->PreviewImageID = array_shift($sizes);*/
			
			// New Algorithm calculated by biggest face
			$sizes = array();
			$faces = array();
			
			foreach($timestamps as $stamp){
				if($img = $this->extractTimelineImage($LogFile, $stamp)){
					
					$sizes[$img['Size']] = $img['ID'];
					
					if(isset($img['Face']['w'])) $faces[$img['Face']['w']] = $img['ID'];
				}
            }
			
			if(count($faces) > 0){
				krsort($faces);
				$this->PreviewImageID = array_shift($faces);
			}else{
				krsort($sizes);
				$this->PreviewImageID = array_shift($sizes);
			}
                
            $PreviewImage = $this->PreviewImage();
            $this->Width = $PreviewImage->getWidth();
            $this->Height = $PreviewImage->getHeight();
                
            $this->ProcessingStatus = 'finished';
            $this->write();
                
            $Message = "Processing for File ".$this->getRelativePath()." finished";
			$this->appendLog($LogFile, $Message);
                
            return true;
        } catch (Exception $ex) {
                
            $Message = "ERROR ON - Timeline Image extraction:";
			$this->appendLog($LogFile, $Message, $e->getMessage());
                
            $this->ProcessingStatus = 'error';
            $this->write();
                
            return false;
        }
    }
	
	private function extractTimelineImage($LogFile, $timestamp){
            
        try{
            $tmpImage = TEMP_FOLDER."/VideoFilePreviewImage-".md5($this->getRelativePath())."-".implode('-', explode(':',$timestamp)).".png";
            // FFMPEG Command
            // https://trac.ffmpeg.org/wiki/Seeking%20with%20FFmpeg
            // @TODO ffmpeg output should be written to logfile too
            $cmd = "avconv -ss ".$timestamp." -i ".$this->getFullPath()." -vframes 1 ".$tmpImage." >> ".$LogFile;
            $pid = shell_exec($cmd);

            // prepare File path
            $path = $this->getRelativePath();
            if(substr($path, 0, strlen(ASSETS_DIR."/")) == ASSETS_DIR."/"){
                $path = substr($path, strlen(ASSETS_DIR."/"));
            }
            $path = substr($path, 0, strlen("/".$this->Name)*-1);

            $TimelineImage = new VideoImage();
            if($TimelineImage->load($tmpImage, $path."/VideoFilePreviewImages-".md5($this->getRelativePath()))){
                $TimelineImage->Playtime = $timestamp;
                $TimelineImage->write();
                    
                $this->TimelineImages()->add($TimelineImage);
                    
                $Message = "Added Timeline Image:\n";
                $Message.= $TimelineImage->getRelativePath();
				$this->appendLog($LogFile, $Message);
            }else{
                $Message = "ERROR ON - Timeline Image extraction:\n\n";
                $Message.= "File not loaded: ".$tmpImage;
				$this->appendLog($LogFile, $Message);
                
                $this->ProcessingStatus = 'error';
                $this->write();
                    
                return false;
            }
                
            $return = array('ID' => $TimelineImage->ID, 'Size' => filesize($tmpImage), 'Face' => $TimelineImage->DetectFace());
            // tmp File löschen
            unlink($tmpImage);
                
            return $return;
                
        }catch(Exception $ex) {
                
            $Message = "ERROR ON - Timeline Image extraction:\n\n";
            $Message.= "File: ".$tmpImage;
            $this->appendLog($LogFile, $Message, $e->getMessage());
                
            $this->ProcessingStatus = 'error';
            $this->write();
                
            return false;
        }
    }
	
	protected function onAfterProcess() {
		$this->extend('onAfterProcess');
	}
}