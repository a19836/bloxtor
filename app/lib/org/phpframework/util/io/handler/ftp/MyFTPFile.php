<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

include_once get_lib("org.phpframework.util.io.handler.ftp.MyFTPHandler");

class MyFTPFile extends MyFTPHandler {
	
	public function __construct($host, $username, $password, $port = false, $file_name = false, $settings = array()) {
		parent::__construct($host, $username, $password, $port, $file_name, $settings);
	}
	
	/*
	 * upload: uploads file
	 *
	 * file_details = $_FILES[file_name]
	 */
	public function upload($file_details, $upload_dir, $new_name = false) {
		//TODO
	}
	
	/*
	 * create: creates new file
	 */
	public function create($cont) {
		//TODO
	}
	
	/*
	 * edit: edits file
	 */
	public function edit($cont) {
		//TODO
	}
	
	/*
	 * get: gets file content
	 */
	public function get() {
		//TODO
	}
	
	/*
	 * delete: deletes file
	 */
	public function delete() {
		//TODO
	}
	
	/*
	 * copy: copy file
	 */
	public function copy($dest) {
		//TODO
	}
	
	/*
	 * setFileName: sets file name
	 */
	public function setFileName($file_name) {
		$this->file_name = $file_name;
	}
}
?>
