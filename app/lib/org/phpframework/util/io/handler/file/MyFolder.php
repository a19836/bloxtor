<?
include_once get_lib("org.phpframework.util.io.handler.file.MyFileHandler");

class MyFolder extends MyFileHandler {
	public $invalid_files;
	
	const EXEC_ALLOWED = false;
	
	public function __construct($file_name = false, $settings = array()) {
		parent::__construct($file_name);
		$this->configureFileName();
		
		$this->invalid_files = $this->getInvalidFiles();
		if(isset($settings["invalid_files"]) && is_array($settings["invalid_files"]))
			$this->invalid_files = array_merge($this->invalid_files, $settings["invalid_files"]);
	}
	
	/*
	 * create: creates folder
	 */
	public function create() {
		if($this->file_name) {
			return $this->exists() ? true : mkdir($this->file_name);
		}
		return false;
	}
	
	/*
	 * getFiles: gets files from a directory
	 */
	public function getFiles() {
		$files = array();
	
		if (is_dir($this->file_name) && ($dir = opendir($this->file_name)) ) {
			while( ($file = readdir($dir)) !== false)
				if($this->isValid($file)) {
					$type = $this->getType($this->file_name . $file);
					
					$files[$file] = array(
						"path" => $this->file_name . $file, 
						"name" => $file, 
						"type" => $type["id"], 
						"type_desc" => $type["desc"]
					);
				}
			closedir($dir);
		}
		
		return $files;
	}
	
	/*
	 * getFilesRecursevly: gets files recursevly from a directory and all his sub-directories
	 */
	public function getFilesRecursevly() {
		$files = $this->getFiles();
		$folder_type = $this->getFolderType();
		
		$keys = array_keys($files);
		$t = count($keys);
		for($i = 0; $i < $t; $i++) {
			$file = $files[ $keys[$i] ];
			if($file["type"] == $folder_type["id"]) {
				$sub_folder = new MyFolder($file["path"]);
				$sub_files = $sub_folder->getFilesRecursevly();
				$files[ $keys[$i] ]["files"] = $sub_files;
			}
		}
		
		return $files;
	}
	
	/*
	 * getFilesCount: ts the files number from a directory
	 */
	public function getFilesCount() {
		$files = $this->getFiles();
		$keys = array_keys($files);
		return count($keys);
	}
	
	/*
	 * delete: deletes folder
	 */
	public function delete() {
		if($this->exists()) {
			if(self::EXEC_ALLOWED && function_exists("exec")) { //maybe exec function was disabled in the php.ini
				exec("rm -r ".$this->file_name, $output);
				return count($output) == 0 ? true : false;
			}
			else {
				$status = true;
			
				if (is_dir($this->file_name) && ($dir = opendir($this->file_name)) ) {
					$folder_type = $this->getFolderType();
			
					while( ($file = readdir($dir)) !== false)
						if($file != "." && $file != "..") {
							$sub_file_name = $this->file_name . $file;
							$type = $this->getType($sub_file_name);
							if($type["id"] == $folder_type["id"]) {
								$sub_folder = new MyFolder($sub_file_name);
								if(!$sub_folder->delete())
									$status = false;
							}
							else if(!unlink($sub_file_name))
								$status = false;
						}
					closedir($dir);
				}
		
				if($status)
					return rmdir($this->file_name);
			}
		}
		else
			return true;
		
		return false;
	}
	
	/*
	 * copy: copy folder
	 */
	public function copy($dest) {
		if($this->exists() && $dest && $this->file_name != $dest) {
			if(self::EXEC_ALLOWED && function_exists("exec")) { //maybe exec function was disabled in the php.ini
				exec("cp -p -r ".$this->file_name." {$dest}", $output);
				return count($output) == 0 ? true : false;
			}
			else {
				$dest_folder = new MyFolder($dest);
				if($dest_folder->exists() || (!$dest_folder->exists() && $dest_folder->create())) {
					$folder_type = $this->getFolderType();
					$status = true;
				
					$files = $this->getFiles();
					$keys = array_keys($files);
					$t = count($keys);
					for($i = 0; $i < $t && $status; $i++) {
						$file = $files[ $keys[$i] ];
						$dest_sub_file_name = $dest . $file["name"];
						if($file["type"] == $folder_type["id"]) {
							$dest_sub_folder = new MyFolder($dest_sub_file_name);
							if(!$dest_sub_folder->exists() && !$dest_sub_folder->create())
								$status = false;
						
							$src_sub_folder = new MyFolder($file["path"]);
							if($status && !$src_sub_folder->copy($dest_sub_folder->file_name))
								$status = false;
						}
						else {
							$sub_file = new MyFile($file["path"]);
							if(!$sub_file->copy($dest_sub_file_name))
								$status = false;
						}
					}
					return $status;
				}
			}
		}
		return false;
	}
	
	/*
	 * configureFileName: configures file name
	 */
	private function configureFileName() {
		if(!empty($this->file_name) && substr($this->file_name,strlen($this->file_name)-1) != "/")
			$this->file_name .= "/";
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
