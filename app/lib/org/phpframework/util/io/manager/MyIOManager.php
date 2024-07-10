<?php
include_once get_lib("org.phpframework.util.io.manager.IMyIOManager");

abstract class MyIOManager implements IMyIOManager {
	public $options;
	private $root_path;
	
	/*
	 * getMyIOHandler: gets MyIOHandler
	 */
	abstract public function getMyIOHandler();
	
	/*
	 * setOptions: sets options list
	 */
	public function setOptions($options) {$this->options = $options;}
	/*
	 * getOptions: gets options list
	 */
	public function getOptions() {return $this->options;}
	
	/*
	 * setOption: sets option item
	 */
	public function setOption($option, $value) {$this->options[$option] = $value;}
	/*
	 * getOption: gets option item
	 */
	public function getOption($option) {return isset($this->options[$option]) ? $this->options[$option] : null;}
	
	/*
	 * getRootPath: gets root path
	 */
	public function getRootPath() {
		return $this->root_path;
	}
	/*
	 * setRootPath: sets root path
	 * The slash (/) should be added to the root_path variable, even if the root_path is empty!!!
	 */
	public function setRootPath($root_path, $is_free = false) {
		$root_path = self::removeDuplicates($root_path);
		if(!$is_free) {
			$root_path = substr($root_path, strlen($root_path) - 1) != "/" ? $root_path . "/" : $root_path;
		}
		$this->root_path = $root_path;
	}
	
	/*
	 * configurePath: configures path
	 * $free in case of a absolute path and already configured. 
	 * This is being used to the unknowns files.
	 */
	public static function configurePath($path, $free = false) {
		if(!$free) {
			$path = preg_replace("/[^\w\-\.\/]*/iu", "", $path); //'\w' means all words with '_' and '/u' means with accents and ç too.
			$path = self::removeDuplicates($path);
			$path = substr($path, strlen($path) - 1) != "/" ? $path . "/" : $path;
			
			return $path;
		}
		return $path;
	}
	
	/*
	 * configureName: configures file name
	 */
	public static function configureName($name) {
		return preg_replace("/[^\w\-\.]*/i", "", trim($name)); //'\w' means all words with '_' and '/u' means with accents and ç too.
	}

	/*
	 * removeDuplicates: removes path duplicates
	 */
	public static function removeDuplicates($path) {
		return preg_replace('/([\/]+)/', '/', $path);
	}
	
	/*
	 * prepareFiles: prepare files list
	 */
	public function prepareFiles($files) {
		$keys = array_keys($files);
		$t = count($keys);
		for($i = 0; $i < $t; $i++) {
			$key = $keys[$i];
			$path = isset($files[$key]["path"]) ? $files[$key]["path"] : null;
			$files[$key]["path"] = substr($path, strlen($this->root_path));
		}
		return $files;
	}
	
	/*
	 * getFileNameExtension: gets file name extension
	 */
	public function getFileNameExtension($name) {
		return $this->getMyIOHandler()->getFileExtension($name);
	}
	
	/*
	 * checkType: checks file type
	 */
	public function checkType($name) {
		$status = true;
		
		$file_type_allowed = isset($this->options["file_type_allowed"]) ? $this->options["file_type_allowed"] : null;
		
		if($file_type_allowed) {
			$type = $this->getMyIOHandler()->getType($name);
			
			if(is_array($file_type_allowed)) {
				$t = count($file_type_allowed);
				for($i = 0; $i < $t; $i++) {
					if($file_type_allowed[$i] == 2) {
						return true;
					}
					elseif($type["id"] == $file_type_allowed[$i]) {
						return true;
					}
				}
				return false;
			}
			else {
				return $file_type_allowed == 2 || $type["id"] == $file_type_allowed ? true : false;
			}
		}
		return $status;
	}
	
	/*
	 * checkMimeType: checks file mime type
	 */
	public function checkMimeType($mime_type) {
		$status = true;
		
		$file_type_allowed = isset($this->options["file_type_allowed"]) ? $this->options["file_type_allowed"] : null;
		
		if($file_type_allowed) {
			$MyIOHandler = $this->getMyIOHandler();
			
			if(is_array($file_type_allowed)) {
				$t = count($file_type_allowed);
				for($i = 0; $i < $t; $i++) {
					if($file_type_allowed[$i] == 2) {
						return true;
					}
					elseif($MyIOHandler->getFileExtensionByMimeType($mime_type, $file_type_allowed[$i])) {
						return true;
					}
				}
				return false;
			}
			else {
				return $file_type_allowed == 2 || $MyIOHandler->getFileExtensionByMimeType($mime_type, $file_type_allowed) ? true : false;
			}
		}
		return $status;
	}
	
	/*
	 * createPath: checks if the path exists and if not try to create it
	 */
	public function createPath($path, $settings = array()) {
		$dir_path = dirname($path) == "." ? "" : dirname($path);
		$name = basename($path);
	
		$status = true;
		if(($dir_path || $name) && !$this->exists($dir_path, $name)) {
			$status = $this->createPath($dir_path, $settings);
			if($status) {
				$status = $this->add(1, $dir_path, $name, $settings);
			}
		}
		return $status;
	}
}
?>
