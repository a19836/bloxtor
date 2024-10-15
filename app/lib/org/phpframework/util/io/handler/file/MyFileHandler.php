<?
include_once get_lib("org.phpframework.util.io.handler.MyIOHandler");

class MyFileHandler extends MyIOHandler {
	public $file_name;
	
	public function __construct($file_name = false) {
		$this->file_name = $file_name;
	}
	
	/*
	 * getType: gets file type
	 */
	public function getType($file_path) {
		$types = $this->getFileTypes();
		$file_name = basename($file_path);
		
		if (is_dir($file_path))
			return isset($types["folder"]) ? $types["folder"] : null;
		
		$type = self::getFileType($file_name);
		return isset($types[$type]) ? $types[$type] : null;
	}
	
	/*
	 * rename: renames file
	 */
	public function rename($new_name) {
		if($this->file_name) {
			$new_file_name = dirname($this->file_name);
			$new_file_name .=  $new_file_name ? "/" . $new_name : $new_name;
			return rename($this->file_name, $new_file_name);
		}
		return false;
	}
	
	/*
	 * exists: checks if a file exists
	 */
	public function exists() {
		return $this->file_name && file_exists($this->file_name);
	}
	
	/*
	 * getInfo: gets file info
	 */
	public function getInfo() {
		$info = array();
		if($this->exists()) {
			$type = $this->getType($this->file_name);
			
			$info = array();
			$info["path"] = $this->file_name;
			$info["name"] = basename($this->file_name);
			$info["type"] = isset($type["id"]) ? $type["id"] : null;
			$info["type_desc"] = isset($type["desc"]) ? $type["desc"] : null;
			
			if($info["type"] != 1) {
				$extension = pathinfo($this->file_name, PATHINFO_EXTENSION);
				$info["extension"] = $extension;
				$info["mime_type"] = $this->getFileMimeTypeByExtension($info["extension"]);
			}
		}
		return $info;
	}
}
?>
