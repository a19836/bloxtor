<?php
include get_lib("org.phpframework.cache.service.filesystem.FileSystemServiceCacheFolderHandler");
include_once get_lib("org.phpframework.cache.CacheHandlerUtil");

class FileSystemServiceCacheFileHandler {
	/*private*/ const VALIDATION_FOLDER_NAME = ".validation";
	
	private $CacheFolderHandler;
	private $CacheHandler;
	
	public function __construct($CacheHandler, $maximum_size, $folder_total_num_manager_active = false) {
		$this->CacheHandler = $CacheHandler;
		
		$this->CacheFolderHandler = new FileSystemServiceCacheFolderHandler($this, $maximum_size, $folder_total_num_manager_active);
	}
	
	public function write($file_path, $cont) {
		if(($file = fopen($file_path, "w"))) {
			$status = fputs($file, $cont);
			$status = $status === false ? false : true;
			fclose($file);
		}
		return $status;
	}
	
	public function create($file_path, $cont = "") {
		$status = false;
		
		$current_size = $this->CacheFolderHandler->getFolderSize($this->CacheHandler->getRootPath());
		$maximum_size = $this->CacheFolderHandler->getMaximumSize();
		$cont_size = strlen($cont);
		$size_status = !$maximum_size || ($maximum_size && $maximum_size >= $current_size + $cont_size);
		
		if($size_status) {
			$dir_path = dirname($file_path);
			$new_file_path = $this->getPath($file_path);
		
			if($new_file_path && $this->exists($new_file_path)) {
				$file_path = $new_file_path;
				$exists = true;
			}
			else {
				$file_path = $this->CacheFolderHandler->getFolderPath($dir_path) . basename($file_path);
				$dir_path = dirname($file_path);
			}
	
			if($file_path) {
				$old_size = $exists ? filesize($file_path) : 0;
				
				$status = $this->write($file_path, $cont);
				if($status) {
					if(!$exists) {
						$this->CacheFolderHandler->updateFilesTotal($dir_path);
					}
					$new_size = $cont_size - $old_size;
					$this->CacheFolderHandler->setFolderSize($this->CacheHandler->getRootPath(), $dir_path, $new_size);
					$this->setFileValidation($file_path);
				}
			}
		}
		return $status;
	}
	
	public function deleteFolder($dir_path) {
		$size = $this->CacheFolderHandler->getFolderSize($dir_path);
		
		if($this->CacheFolderHandler->deleteFolder($dir_path)) {
			$inc = $size * -1;
			$this->CacheFolderHandler->setFolderSize($this->CacheHandler->getRootPath(), dirname($dir_path), $inc);
		}
	}
	
	public function delete($files_to_delete) {
		$status = true;
		
		if($files_to_delete) {
			$folders_to_update = array();
			$t = count($files_to_delete);
			for($i = 0; $i < $t; $i++) {
				$file_to_delete = $files_to_delete[$i];
				if($this->exists($file_to_delete)) {
					$file_size = filesize($file_to_delete);
					if(unlink($file_to_delete)) {
						$dir_path = dirname($file_to_delete);
						
						$folders_to_update[$dir_path]["number"] = (isset($folders_to_update[$dir_path]["number"]) ? $folders_to_update[$dir_path]["number"] : 0) + 1;
						$folders_to_update[$dir_path]["size"] = (isset($folders_to_update[$dir_path]["size"]) ? $folders_to_update[$dir_path]["size"] : 0) + $file_size;
					}
					else {
						$status = false;
					}
				}
			}
			
			$folders_to_update_keys = array_keys($folders_to_update);
			$t = count($folders_to_update_keys);
			for($i = 0; $i < $t; $i++) {
				$dir_path = $folders_to_update_keys[$i];
				if(is_numeric($folders_to_update[$dir_path]["number"])) {
					$inc = $folders_to_update[$dir_path]["number"] * -1;
					$this->CacheFolderHandler->updateFilesTotal($dir_path, $inc);
				}
				if(is_numeric($folders_to_update[$dir_path]["size"])) {
					$inc = $folders_to_update[$dir_path]["size"] * -1;
					$this->CacheFolderHandler->setFolderSize($this->CacheHandler->getRootPath(), $dir_path, $inc);
				}
			}
		}
		return $status;
	}
	
	public function getContent($file_path) {
		return @file_get_contents($file_path);//maybe the file was delete by another thread, so we need to add the @ so it doesn't give error.
	}
	
	public function get($file_path) {
		$file_path = $this->getPath($file_path);
		if($file_path) {
			return $this->getContent($file_path);
		}
		return false;
	}
	
	public function getPath($file_path) {
		if(!$this->exists($file_path) && $this->folder_manager_active) {
			$dir_path = dirname($file_path);
			$file_name = basename($file_path);
			
			$found_files = $this->search($dir_path, $file_name, false, 1);
			$file_path = count($found_files) && $found_files[0] ? $found_files[0] : false;
		}
		return $file_path;
	}
	
	public function search($dir_path, $regexp, $search_type = false, $limit = false, $recursivity = true) {
		$found_files = array();
		
		if($regexp) {
			CacheHandlerUtil::configureFolderPath($dir_path);
			//echo "$dir_path, $regexp, $search_type, $limit, $recursivity\n";
			
			$files = $this->CacheFolderHandler->getFiles($dir_path);
			for($i = count($files) - 1; $i >= 0; --$i) {
				$file = $files[$i];
				if($this->isFileNameValid($file)) {
					$sub_file_path = $dir_path . $file;
					if(!is_dir($sub_file_path)) {
						//echo "$file, $regexp, $search_type\n";
						
						$exists = false;
						if($search_type == "regexp" || $search_type == "regex" || $search_type == "start" || $search_type == "begin" || $search_type == "prefix" || $search_type == "middle" || $search_type == "end" || $search_type == "finish" || $search_type == "suffix") {
							//echo "checkIfKeyTypeMatchValue\n";
							$exists = CacheHandlerUtil::checkIfKeyTypeMatchValue($file, $regexp, $search_type);
						}
						else if($file == $regexp) {
							$exists = true;
						}
						
						if($exists) {
							$found_files[] = $sub_file_path;
						
							if($limit) {
								--$limit;
								if($limit <= 0) {
									break;
								}
							}
						}
					}
				}
			}
		
			if($limit === false || (is_numeric($limit) && $limit > 0)) {
				if($recursivity) {
					$folders = $this->CacheFolderHandler->getFolders($dir_path);
					//echo "folders:";print_r($folders);
					for($i = count($folders) - 1; $i >= 0; --$i) {
						$folder = $folders[$i];
						if($this->isFileNameValid($folder)) {
							$folder_path = $dir_path . $folder;
							$sub_found_files = $this->search($folder_path, $regexp, $search_type, $limit, false);
							$found_files = array_merge($found_files, $sub_found_files);
				
							if($limit) {
								$limit -= count($sub_found_files);
								if($limit <= 0) {
									break;
								}
							}
						}
					}
			
					if($limit === false || (is_numeric($limit) && $limit > 0)) {
						for($i = count($folders) - 1; $i >= 0; --$i) {
							$folder = $folders[$i];
							if($this->isFileNameValid($folder)) {
								$folder_path = $dir_path . $folder . "/";
				
								$sub_folders = $this->CacheFolderHandler->getFolders($folder_path);
								for($j = count($sub_folders) - 1; $j >= 0; --$j) {
									$sub_folder = $sub_folders[$j];
									$sub_folder_path = $folder_path . $sub_folder . "/";
					
									$sub_found_files = $this->search($sub_folder_path, $regexp, $search_type, $limit);
									$found_files = array_merge($found_files, $sub_found_files);
					
									if($limit) {
										$limit -= count($sub_found_files);
										if($limit <= 0) {
											$i = 0;
											break;
										}
									}
								}
							}
						}
					}
				}
			}
		}
		return $found_files;
	}
	
	public function getFileMTime($file_path) {
		return $file_path ? filemtime($file_path) : 0;
	}
	
	public function exists($file_path) {
		return $file_path && file_exists($file_path);
	}
	
	public function isValid($file_path) {
		$invalid_file_path = $this->getInvalidFilePath($file_path);
		
		if($this->exists($invalid_file_path)) {
			$cont = $this->getContent($invalid_file_path);
		}
		return $cont == 1 ? false : true;
	}
	
	public function setFileValidation($file_path, $is_valid = "") {
		$invalid_file_path = $this->getInvalidFilePath($file_path);
		
		$exists = $this->exists($invalid_file_path);
		$continue = (!$exists && $is_valid) || ($exists && $this->getContent($invalid_file_path) != $is_valid);
		if($continue) {
			if(CacheHandlerUtil::preparePath(dirname($invalid_file_path))) {
				return $this->write($invalid_file_path, $is_valid);
			}
			return false;
		}
		return true;
	}
	
	private function getInvalidFilePath($file_path) {
		$dir_path = dirname($file_path);
		$file_name = basename($file_path);
		
		return $dir_path . "/" . self::VALIDATION_FOLDER_NAME . "/" . hash("md4", $file_name);
	}
	
	private function isFileNameValid($file_name) {
		return substr($file_name, 0, 1) != "." && $file_name != FileSystemServiceCacheFolderHandler::FOLDER_CONTROLLER_FILE_NAME && $file_name != FileSystemServiceCacheFolderHandler::FOLDER_SIZE_FILE_NAME;
	}
	
	public function getCacheFolderHandler() {return $this->CacheFolderHandler;}
	
	public function getCacheHandler() {return $this->CacheHandler;}
}
?>
