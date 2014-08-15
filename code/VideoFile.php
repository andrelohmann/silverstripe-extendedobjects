<?php

/**
 * Represents an Image
 *
 * @package framework
 * @subpackage filesystem
 */
class VideoFile extends File {
    
        private static $previews = 10; // No of preview Images that should be made
	
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
        
        public static function set_amount_preview_images($amount){
            self::$previews = $amount;
        }
        
        public static function get_amount_preview_images(){
            return self::$previews;
        }
        
        public function PreviewThumbnail(){
            if($this->ProcessingStatus == 'finished' && $Image = $this->PreviewImage()->ID)
                return $this->PreviewImage()->setWidth(50);
            else
                return '(No Image)';
        }
        
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
		'avi','flv','m4v','mov','mp4','mpeg','mpg','ogv','webm','wmv'
	);
        
        public function CMSThumbnail() {
                if($this->PreviewImageID) return $this->PreviewImage()->getFormattedImage('CMSThumbnail');
                return parent::CMSThumbnail();
	}
        
        public function Thumbnail($width, $height) {
                if($this->PreviewImageID) return $this->PreviewImage()->SetRatioSize($width, $height)->getURL();
                return $this->Icon();
	}
        
        public function Resolution(){
            return $this->Width."x".$this->Height;
        }
	
	/**
	 * Return an XHTML img tag for this Image,
	 * or NULL if the image file doesn't exist on the filesystem.
	 * 
	 * @return string
	 */
	/*public function getTag() {
		if(file_exists(Director::baseFolder() . '/' . $this->Filename)) {
			$url = $this->getURL();
			$title = ($this->Title) ? $this->Title : $this->Filename;
			if($this->Title) {
				$title = Convert::raw2att($this->Title);
			} else {
				if(preg_match("/([^\/]*)\.[a-zA-Z0-9]{1,6}$/", $title, $matches)) {
					$title = Convert::raw2att($matches[1]);
				}
			}
			return "<img src=\"$url\" alt=\"$title\" />";
		}
	}*/
	
	/**
	 * Return an XHTML img tag for this Image.
	 * 
	 * @return string
	 */
	/*public function forTemplate() {
		return $this->getTag();
	}*/
	
	protected function onBeforeDelete() {
		parent::onBeforeDelete();
                
                if($this->PreviewImageID) $this->PreviewImage()->delete();

		foreach($this->TimelineImages() as $Image){
                    $TimelineImagesFolder = $Image->Parent();
                    $Image->delete();
                }
                
                $TimelineImagesFolder->delete();
	}
        
        // can not be deleted or edited while processing
        /*public function canDelete($member = null) {
            if($this->ProcessingStatus != 'processing'){
                return parent::canDelete($member);
            }
            return false;
        }
        
        public function canEdit($member = null) {
            if($this->ProcessingStatus != 'processing'){
                return parent::canDelete($member);
            }
            return false;
        }*/
        
        public function onAfterLoad(){
            // http://www.davenewson.com/dev/methods-for-asynchronous-processes-in-php
            $cmd = "nohup php ".FRAMEWORK_PATH."/cli-script.php VideoFileDataExtractionTask VideoFileID=".$this->ID." >> ".TEMP_FOLDER."/VideoFilesTaskLog.log & echo $!";
            $pid = shell_exec($cmd);
        }
        
        // process the Video
        public function process($LogFile = false){
            
            if(!$LogFile) $LogFile = TEMP_FOLDER.'/VideoFileProcessing-ID-'.$this->ID.'-'.md5($this->getRelativePath()).'.log';
            
            if($this->ProcessingStatus == 'new'){
                $this->ProcessingStatus = 'processing';
                $this->write();
                
                $Message = "[LOGTIME: ".date("Y-m-d H:i:s")."]\nProcessing for File ".$this->getRelativePath()." started\n\n";
                // Write the contents to the logfile, 
                // using the FILE_APPEND flag to append the content to the end of the file
                // and the LOCK_EX flag to prevent anyone else writing to the file at the same time
                file_put_contents($LogFile, $Message, FILE_APPEND | LOCK_EX);
                
                // Movie Object
                $mov = new ffmpeg_movie($this->getFullPath());
                
                // read data
                if($this->processVideoInformation($mov, $LogFile)){
                    // private Information returned no error
                    $this->extractTimelineImages($mov, $LogFile);
                }
            }else{
                $Message = "[LOGTIME: ".date("Y-m-d H:i:s")."]\nFile allready processed\n";
                file_put_contents($LogFile, $Message, FILE_APPEND | LOCK_EX);
            }
        }
        
        private function processVideoInformation($mov, $LogFile){
            
            try{
                $Message = "[LOGTIME: ".date("Y-m-d H:i:s")."]\nExtracted Information:\n";
                $Message.= sprintf("file name = %s\n", $mov->getFileName());
                $Message.= sprintf("duration = %s seconds\n", $mov->getDuration());
                $Message.= sprintf("frame count = %s\n", $mov->getFrameCount());
                $Message.= sprintf("frame rate = %0.3f fps\n", $mov->getFrameRate());
                $Message.= sprintf("get bit rate = %d\n", $mov->getBitRate());
                $Message.= sprintf("has audio = %s\n", $mov->hasAudio() == 0 ? 'No' : 'Yes');
                
                if ($mov->hasAudio()) {
                    $Message.= sprintf("get audio stream id= %s\n", $mov->getAudioStreamId());
                    $Message.= sprintf("get audio codec = %s\n", $mov->getAudioCodec());
                    $Message.= sprintf("get audio bit rate = %d\n", $mov->getAudioBitRate());
                    $Message.= sprintf("get audio sample rate = %d \n", $mov->getAudioSampleRate());
                    $Message.= sprintf("get audio channels = %s\n", $mov->getAudioChannels());
                }
                
                $Message.= sprintf("has video = %s\n", $mov->hasVideo() == 0 ? 'No' : 'Yes');
                
                if ($mov->hasVideo()) {
                    $Message.= sprintf("frame height = %d pixels\n", $mov->getFrameHeight());
                    $Message.= sprintf("frame width = %d pixels\n", $mov->getFrameWidth());
                    $Message.= sprintf("get video codec = %s\n", $mov->getVideoCodec());
                    $Message.= sprintf("get video bit rate = %d\n", $mov->getVideoBitRate());
                    $Message.= sprintf("get pixel format = %s\n", $mov->getPixelFormat());
                    $Message.= sprintf("get pixel aspect ratio = %s\n", $mov->getPixelAspectRatio());
                }
                
                $Message.= "\n";
                
                // Log the next message
                file_put_contents($LogFile, $Message, FILE_APPEND | LOCK_EX);
                
                $this->Duration = gmdate("H:i:s", $mov->getDuration());
                $this->Width = $mov->getFrameWidth();
                $this->Height = $mov->getFrameHeight();
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
        
        private function extractTimelineImages($mov, $LogFile){
            // generate an array of timestamps on where to extract a file
            $timestamps = array();
            // get the first frame
            $timestamps[] = '00:00:00'; // H:i:s
            
            // calculate total playtime in seconds devided by (no of previews - 1)
            $stepsize = $mov->getDuration() / (Config::inst()->get('VideoFile', 'previews') - 1);
            for($i = 1; $i<10; $i++){
                $timestamps[] = gmdate("H:i:s", ($stepsize * $i));
            }
            
            $Message = "[LOGTIME: ".date("Y-m-d H:i:s")."]\nStart TimelineImage extraction:\n\n";
            file_put_contents($LogFile, $Message, FILE_APPEND | LOCK_EX);
            
            try{
                
                $sizes = array();
                
                foreach($timestamps as $stamp){
                    if($size = $this->extractTimelineImage($LogFile, $stamp)){
                        $sizes[$size['Size']] = $size['ID'];
                    }
                }
                krsort($sizes);
                $this->PreviewImageID = array_shift($sizes);
                
                $this->ProcessingStatus = 'finished';
                $this->write();
                
                $Message = "[LOGTIME: ".date("Y-m-d H:i:s")."]\nProcessing for File ".$this->getRelativePath()." finished\n\n";
                file_put_contents($LogFile, $Message, FILE_APPEND | LOCK_EX);
                
                return true;
            } catch (Exception $ex) {
                
                $Message = "[LOGTIME: ".date("Y-m-d H:i:s")."]\nERROR ON - Timeline Image extraction:\n\n";
                file_put_contents($LogFile, $Message, FILE_APPEND | LOCK_EX);
                // log exception
                file_put_contents($LogFile, $e->getMessage(), FILE_APPEND | LOCK_EX);
                
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
                $cmd = "ffmpeg -ss ".$timestamp." -i ".$this->getFullPath()." -vframes 1 ".$tmpImage." >> ".$LogFile;
                $pid = shell_exec($cmd);

                // prepare File path
                $path = $this->getRelativePath();
                if (substr($path, 0, strlen(ASSETS_DIR."/")) == ASSETS_DIR."/"){
                    $path = substr($path, strlen(ASSETS_DIR."/"));
                }
                $path = substr($path, 0, strlen("/".$this->Name)*-1);

                $TimelineImage = new VideoImage();
                if($TimelineImage->load($tmpImage, $path."/VideoFilePreviewImages-".md5($this->getRelativePath()))){
                    $TimelineImage->Playtime = $timestamp;
                    $TimelineImage->write();
                    
                    $this->TimelineImages()->add($TimelineImage);
                    
                    $Message = "[LOGTIME: ".date("Y-m-d H:i:s")."]\nAdded Timeline Image:\n";
                    $Message.= $TimelineImage->getRelativePath()."\n\n";
                    
                    file_put_contents($LogFile, $Message, FILE_APPEND | LOCK_EX);
                }else{
                    $Message = "[LOGTIME: ".date("Y-m-d H:i:s")."]\nERROR ON - Timeline Image extraction:\n\n";
                    $Message.= "File not loaded: ".$tmpImage;
                    file_put_contents($LogFile, $Message, FILE_APPEND | LOCK_EX);
                
                    $this->ProcessingStatus = 'error';
                    $this->write();
                    
                    return false;
                }
                
                $return = array('ID' => $TimelineImage->ID, 'Size' => filesize($tmpImage));
                // tmp File löschen
                unlink($tmpImage);
                
                return $return;
                
            } catch (Exception $ex) {
                
                $Message = "[LOGTIME: ".date("Y-m-d H:i:s")."]\nERROR ON - Timeline Image extraction:\n\n";
                $Message.= "File: ".$tmpImage;
                file_put_contents($LogFile, $Message, FILE_APPEND | LOCK_EX);
                // log exception
                file_put_contents($LogFile, $e->getMessage(), FILE_APPEND | LOCK_EX);
                
                $this->ProcessingStatus = 'error';
                $this->write();
                
                return false;
            }
        }
}