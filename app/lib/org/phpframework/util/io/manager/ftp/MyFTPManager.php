<?php
include_once get_lib("org.phpframework.util.io.handler.ftp.MyFTPFolder");
include_once get_lib("org.phpframework.util.io.handler.ftp.MyFTPFile");

include_once get_lib("org.phpframework.util.io.manager.MyIOManager");

class MyFTPManager extends MyIOManager {
	private $MyFTPFolder;
	private $MyFTPFile;
	
	public function __construct($host, $username, $password, $port = false, $settings = array()) {
		$this->MyFTPFolder = new MyFTPFolder($host, $username, $password, $port, "", $settings);
		$this->MyFTPFile = new MyFTPFile($host, $username, $password, $port, "", $settings);
	}
	
	/****************** START: FTP CONNECTION FUNCTIONS *********************/
	
	/*
	 * connect: connects to a FTP server
	 */
	public function connect($host, $username, $password, $port = false, $is_passive_mode = false) {
		$this->MyFTPFile->connect($host, $username, $password, $port, $is_passive_mode);
	}
	
	/*
	 * close: closes FTP connection
	 */
	public function close() {
		return $this->MyFTPFile->close();
	}
	
	/*
	 * isConnected: checks if the connection is still active
	 */
	public function isConnected() {
		return $this->MyFTPFile->isConnected();
	}
	
	/****************** END: FTP CONNECTION FUNCTIONS *********************/
	
	/*
	 * add: creates new file
	 */
	public function add($type, $dir_path, $name, $settings = array()) {
		//TODO
	}
	
	/*
	 * edit: edits file content
	 */
	public function edit($dir_path, $name, $settings = array()) {
		//TODO
	}

	/*
	 * delete: deletes file
	 */
	public function delete($type, $dir_path, $name) {
		//TODO
	}
	
	/*
	 * copy: copy file
	 */
	public function copy($type, $src_dir_path, $src_name, $dest_dir_path, $settings = array()) {
		//TODO
	}
	
	/*
	 * move: moves file
	 */
	public function move($type, $src_dir_path, $src_name, $dest_dir_path, $settings = array()) {
		//TODO
	}
	
	/*
	 * rename: renames file
	 */
	public function rename($dir_path, $ori_name, $new_name, $settings = array()) {
		//TODO
	}
	
	/*
	 * getFile: gets file content
	 */
	public function getFile($dir_path, $name) {
		//TODO
	}
	
	/*
	 * getFileInfo: gets file info
	 */
	public function getFileInfo($dir_path, $name) {
		//TODO
	}

	/*
	 * getFiles: gets files from a folder
	 */
	public function getFiles($dir_path) {
		//TODO
		
		$files = array();
		
		$result = array();
		$result["files"] = $this->prepareFiles($files);
		$result["is_truncate"] = $is_truncate;
		$result["last_marker"] = $is_truncate ? $last_marker : "";
		return $result;
	}

	/*
	 * getFilesCount: gets files number from a folder
	 */
	public function getFilesCount($dir_path) {
		//TODO
	}

	/*
	 * upload: uploads file
	 */
	public function upload($file_details, $dir_path, $new_name, $settings = array()) {
		//TODO
	}

	/*
	 * exists: checks if file exists
	 */
	public function exists($dir_path, $name) {
		//TODO
	}
	
	/*
	 * getMyIOHandler: gets MyIOHandler
	 */
	public function getMyIOHandler() {
		return $this->MyFTPFile;
	}	
}
?>
