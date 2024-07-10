<?
include_once get_lib("org.phpframework.util.io.handler.file.MyFileHandler");

class MyFile extends MyFileHandler {
	
	public function __construct($file_name = false) {
		parent::__construct($file_name);
	}
	
	/*
	 * upload: uploads file
	 *
	 * file_details = $_FILES[file_name]
	 */
	public function upload($file_details, $upload_dir, $new_name = false) {
		$FILES["tmp_file_to_upload"] = $file_details;
		$file_size = isset($file_details['size']) ? $file_details['size'] : null;
		$file_name = isset($file_details['name']) ? $file_details['name'] : null;
		$file_src = isset($file_details['tmp_name']) ? $file_details['tmp_name'] : null;
		
		if($upload_dir && substr($upload_dir, strlen($upload_dir) - 1) != "/")
			$upload_dir .= "/";
		
		$file_dest = $upload_dir . ($new_name ? $new_name : $file_name);
		
		$status = is_uploaded_file($file_src) ? move_uploaded_file($file_src, $file_dest) : copy($file_src, $file_dest);
		if($status && file_exists($file_src)) 
			unlink($file_src);
			
		return $status;
	}
	
	/*
	 * create: creates new file
	 */
	public function create($cont) {
		if($this->file_name && ($file = fopen($this->file_name,"w"))) {
			$status = fputs($file, $cont);
			$status = $status === false ? false : true;
			fclose($file);
		}
		return $status;
	}
	
	/*
	 * edit: edits file
	 */
	public function edit($cont) {
		if(file_exists($this->file_name)) {
			return $this->create($cont);
		}
		return false;
	}
	
	/*
	 * get: gets file content
	 */
	public function get() {
		return $this->read();
	}
	
	/*
	 * delete: deletes file
	 */
	public function delete() {
		if($this->exists()) {
			return unlink($this->file_name);
		}
		return true;
	}
	
	/*
	 * copy: copy file
	 */
	public function copy($dest) {
		if($this->exists() && $dest && $this->file_name != $dest) {
			return copy($this->file_name, $dest);
		}
		return null;
	}
	
	/*
	 * read: gets file content
	 */
	public function read() {
		if($this->file_name && file_exists($this->file_name)) {
			$cont = file_get_contents($this->file_name);
		}
		return $cont;
	}
	
	/*
	 * parse: gets file content and returns an array with each line.
	 */
	public function parse() {
		$cont = array();
		if($this->file_name && file_exists($this->file_name) && $file = fopen($this->file_name,"r")) {
			while(!feof($file))
				$cont[] = fgets($file);
			fclose($file);
		}
		return $cont;
	}
	
	/*
	 * readAndWrite: adds content to a file
	 */
	public function readAndWrite($cont) {
		$old_cont = $this->read();
		$old_cont .= $cont;
		return $this->create($old_cont);
	}
	
	/*
	 * setFileName: sets file name
	 */
	public function setFileName($file_name) {
		$this->file_name = $file_name;
	}
}
?>
