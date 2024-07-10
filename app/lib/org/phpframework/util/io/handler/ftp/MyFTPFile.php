<?
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
