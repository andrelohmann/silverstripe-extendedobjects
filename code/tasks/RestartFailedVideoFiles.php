<?php
/**
 * Restart processing of all failed video files
 *
 * @package framework
 * @subpackage filesystem
 */
class RestartFailedVideoFiles extends BuildTask {

	protected $title = 'Restart processing of all failed video files';

	protected $description = 'Restart processing of all failed VideoFile objects';

	/**
	 * Check that the user has appropriate permissions to execute this task
	 */
	public function init() {
		if(!Director::is_cli() && !Director::isDev() && !Permission::check('ADMIN')) {
			return Security::permissionFailure();
		}

		parent::init();
	}

	/**
	 * Clear out the image manipulation cache
	 * @param SS_HTTPRequest $request
	 */
	public function run($request) {
		$failedFiles = 0;
		$Videos = VideoFile::get()->filter(array('ProcessingStatus' => 'error'));

		foreach($Videos as $vid){
			
			$failedFiles++;
			
			$vid->ProcessingStatus = 'new';
			$vid->write();
			
			$vid->onAfterLoad();
			
			sleep(5);
		}

		echo "$failedFiles failed VideoFile objects have reinitiated the processing.";
	}

}
