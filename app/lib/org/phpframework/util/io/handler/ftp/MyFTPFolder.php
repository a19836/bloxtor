<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.util.io.handler.ftp.MyFTPHandler");

class MyFTPFolder extends MyFTPHandler {
	public $invalid_files;
	
	public function __construct($host, $username, $password, $port = false, $file_name = false, $settings = array()) {
		parent::__construct($host, $username, $password, $port, $file_name, $settings);
		$this->configureFileName();
		
		$this->invalid_files = $this->getInvalidFiles();
		if(isset($settings["invalid_files"]) && is_array($settings["invalid_files"]))
			$this->invalid_files = array_merge($this->invalid_files, $settings["invalid_files"]);
	}
	
	/*
	 * create: creates folder
	 */
	public function create() {
		//TODO
	}
	
	/*
	 * getFiles: gets files from a directory
	 */
	public function getFiles() {
		//TODO
	}
	
	/*
	 * getFilesRecursevly: gets files recursevly from a directory and all his sub-directories
	 */
	public function getFilesRecursevly() {
		//TODO
	}
	
	/*
	 * getFilesCount: ts the files number from a directory
	 */
	public function getFilesCount() {
		//TODO
	}
	
	/*
	 * delete: deletes folder
	 */
	public function delete() {
		//TODO
	}
	
	/*
	 * copy: copy folder
	 */
	public function copy($dest) {
		//TODO
	}
	
	/*
	 * configureFileName: configures file name
	 */
	private function configureFileName() {
		//TODO
	}
	
	/*
	 * isVali: checks if the file is valid
	 */
	private function isValid($file_name) {
		$file_name = basename($file_name);
		
		return array_search($file_name, $this->invalid_files) === false ? true : false;
	}
	
	/*
	 * setFileName: sets file name
	 */
	public function setFileName($file_name) {
		$this->file_name = $file_name;
		$this->configureFileName();
	}
}
?>
