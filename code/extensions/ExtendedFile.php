<?php
 
/**
 * extended_image/code/ExtendedFile.php
 * 
 * Can create a new File from given Location
 * 
 */
 
class ExtendedFile extends DataExtension {

    /**
	 * Save an file passed from a form post into this object.
	 * 
	 * @param $tmpFile array Indexed array that PHP generated for every file it uploads.
	 * @param $folderPath string Folder path relative to /assets
	 * @return Boolean|string Either success or error-message.
	 */
	public function load($tmpFile, $folderPath = false) {
				
		if(!$folderPath) $folderPath = Config::inst()->get('Upload', 'uploads_folder');
		
		// @TODO This puts a HUGE limitation on files especially when lots
		// have been uploaded.
		$base = Director::baseFolder();
		$parentFolder = Folder::find_or_make($folderPath);

		// Generate default filename
        $fileArray = explode('/', $tmpFile);
        $fileName = $fileArray[(count($fileArray)-1)];
		$nameFilter = FileNameFilter::create();
		$file = $nameFilter->filter($fileName);
		$fileName = basename($file);

		$relativeFilePath = ASSETS_DIR . "/" . $folderPath . "/$fileName";
		
		// if filename already exists, version the filename (e.g. test.gif to test1.gif)
		while(file_exists("$base/$relativeFilePath")) {
			$i = isset($i) ? ($i+1) : 2;
			$oldFilePath = $relativeFilePath;
			// make sure archives retain valid extensions
			if(substr($relativeFilePath, strlen($relativeFilePath) - strlen('.tar.gz')) == '.tar.gz' ||
				substr($relativeFilePath, strlen($relativeFilePath) - strlen('.tar.bz2')) == '.tar.bz2') {
					$relativeFilePath = preg_replace('/[0-9]*(\.tar\.[^.]+$)/', $i . '\\1', $relativeFilePath);
			} else if (strpos($relativeFilePath, '.') !== false) {
				$relativeFilePath = preg_replace('/[0-9]*(\.[^.]+$)/', $i . '\\1', $relativeFilePath);
			} else if (strpos($relativeFilePath, '_') !== false) {
				$relativeFilePath = preg_replace('/_([^_]+$)/', '_'.$i, $relativeFilePath);
			} else {
				$relativeFilePath .= '_'.$i;
			}
			if($oldFilePath == $relativeFilePath && $i > 2) {
				user_error("Couldn't fix $relativeFilePath with $i tries", E_USER_ERROR);
			}
		}
		
		if(file_exists($tmpFile) && copy($tmpFile, $base."/".$relativeFilePath)) {
			$this->owner->ParentID = $parentFolder->ID;
			// This is to prevent it from trying to rename the file
			$this->owner->Name = basename($relativeFilePath);
			$this->owner->write();
			return true;
		} else {
			return false;
		}
	}
}