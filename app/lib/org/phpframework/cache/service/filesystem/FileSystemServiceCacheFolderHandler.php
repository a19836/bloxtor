<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.cache.CacheHandlerUtil");

class FileSystemServiceCacheFolderHandler {
	/*public*/ const FOLDER_CONTROLLER_FILE_NAME = ".folder_settings";
	/*public*/ const FOLDER_SIZE_FILE_NAME = ".folder_size";
	/*private*/ const MAXIMUM_FOLDER_FILES_NUMBER_ALLOWED = 30000;
	
	private $folder_total_num_manager_active;
	private $maximum_size;
	private $CacheFileHandler;
	
	public function __construct($CacheFileHandler, $maximum_size = false, $folder_total_num_manager_active = false) {
		$this->maximum_size = $maximum_size;
		$this->folder_total_num_manager_active = $folder_total_num_manager_active;
		
		$this->CacheFileHandler = $CacheFileHandler;
	}
	
	public function updateFilesTotal($dir_path, $inc = 1) {
		if($this->folder_total_num_manager_active) {
			$folder_settings = $this->getFolderSettings($dir_path);
			$inc = (isset($folder_settings["total"]) ? $folder_settings["total"] : 0) + $inc;
			$folder_settings["total"] = $inc > 0 ? $inc : 0;
			
			if(empty($folder_settings["type"]))
				$folder_settings["type"] = "file";
			
			return $this->setFolderSettings($dir_path, $folder_settings);
		}
		return true;
	}
	
	public function deleteFolder($dir_path) {
		if($this->CacheFileHandler->exists($dir_path))
			return CacheHandlerUtil::deleteFolder($dir_path);
		return true;
	}
	
	public function getFolderPath($dir_path, $force_folder_total_num_manager_active = false) {
		$dir_path_aux = false;
	
		CacheHandlerUtil::configureFolderPath($dir_path);
		
		if($this->folder_total_num_manager_active || $force_folder_total_num_manager_active) {
			$folder_settings = $this->getFolderSettings($dir_path);
			
			if (
				(empty($folder_settings["type"]) || $folder_settings["type"] != "folder") && 
				(empty($folder_settings["total"]) || $folder_settings["total"] < self::MAXIMUM_FOLDER_FILES_NUMBER_ALLOWED)
			)
				$dir_path_aux = $dir_path;
			else {
				$folders = $this->getFolders($dir_path);
				for($i = count($folders) - 1; $i >= 0; --$i) {
					$folder = $folders[$i];
					$folder_path = $dir_path . $folder . "/";
				
					$sub_folder_settings = $this->getFolderSettings($folder_path);
					if (
						(empty($sub_folder_settings["type"]) || $sub_folder_settings["type"] != "folder") && 
						(empty($sub_folder_settings["total"]) || $sub_folder_settings["total"] < self::MAXIMUM_FOLDER_FILES_NUMBER_ALLOWED)
					) {
						$dir_path_aux = $folder_path;
						break;
					}
				}
			
				if(!$dir_path_aux) {
					$this->checkFolderFiles($dir_path);
			
					$dir_path_aux = $this->getNewFolderPath($dir_path);
				}
			}
		}
		else {
			$dir_path_aux = $dir_path;
		}
		
		return $dir_path_aux && CacheHandlerUtil::preparePath($dir_path_aux) ? $dir_path_aux : false;
	}
	
	public function checkFolderFiles($dir_path, $folder_settings = false) {
		if (!$folder_settings)
			$folder_settings = $this->getFolderSettings($dir_path);
		
		if (isset($folder_settings["total"]) && $folder_settings["total"] >= self::MAXIMUM_FOLDER_FILES_NUMBER_ALLOWED) {
			$new_dir_path = $this->getNewFolderPath($dir_path, $folder_settings);
			
			if ($new_dir_path) {
				CacheHandlerUtil::configureFolderPath($dir_path);
				
				$folder_settings = $this->getFolderSettings($dir_path);
				$total = $folder_settings["total"];
				
				$new_folder_settings = $this->getFolderSettings($new_dir_path);
				$new_dir_name = basename($new_dir_path);
				$new_total = 0;
				
				/* START: COPY FILES TO NEW FOLDER */
				$files = $this->getFiles($dir_path);
				$t = count($files);
				for ($i = 0; $i < $t; $i++) {
					$file = $files[$i];
					
					if ($new_dir_name != $file && $file != self::FOLDER_CONTROLLER_FILE_NAME && $file != self::FOLDER_SIZE_FILE_NAME) {
						$file_path = $dir_path . $file;
						$new_file_path = $new_dir_path . $file;
						
						if ($this->CacheFileHandler->exists($file_path)) {
							if (is_dir($file_path)) {
								if (rename($file_path, $new_file_path)) {
									--$total;
									++$new_total;
								}
							}
							else {
								if (copy($file_path, $new_file_path)) {
									if (unlink($file_path)) {
										--$total;
									}
									++$new_total;
								}
							}
						}
					}
				}
				/* END: COPY FILES TO NEW FOLDER */
				
				/* START: SAVE NEW FOLDER SETTINDS */
				if ($this->maximum_size) {
					if ($this->CacheFileHandler->exists($dir_path . self::FOLDER_SIZE_FILE_NAME) && $this->CacheFileHandler->exists($new_dir_path)) {
						copy($dir_path . self::FOLDER_SIZE_FILE_NAME, $new_dir_path . self::FOLDER_SIZE_FILE_NAME);
					}
				}
				
				$new_folder_settings["type"] = isset($folder_settings["type"]) ? $folder_settings["type"] : null;
				$new_folder_settings["total"] = $new_total;
				$this->setFolderSettings($new_dir_path, $new_folder_settings);
				
				$folder_settings["type"] = "folder";
				$folder_settings["total"] = $total > 0 ? $total : 1;
				$this->setFolderSettings($dir_path, $folder_settings);
				/* END: SAVE NEW FOLDER SETTINDS */
			}
		}
		
		return true;
	}
	
	public function getNewFolderPath($dir_path, $folder_settings = false) {
		CacheHandlerUtil::configureFolderPath($dir_path);
		
		if (!$folder_settings)
			$folder_settings = $this->getFolderSettings($dir_path);
		
		do {
			$new_dir_path = $dir_path . uniqid() . "/";
			$exists = $this->CacheFileHandler->exists($new_dir_path);
		}
		while($exists);
		
		if (CacheHandlerUtil::preparePath($new_dir_path)) {
			$folder_settings["total"] = (isset($folder_settings["total"]) ? $folder_settings["total"] : 0) + 1;
			$this->setFolderSettings($dir_path, $folder_settings);
			
			$new_folder_settings = array();
			$new_folder_settings["total"] = 0;
			$new_folder_settings["type"] = "file";
			$this->setFolderSettings($new_dir_path, $new_folder_settings);
			
			return $new_dir_path;
		}
		
		return false;
	}
	
	public function getFolderSize($dir_path) {
		if ($this->maximum_size) {
			CacheHandlerUtil::configureFolderPath($dir_path);
		
			$folder_size_file_path = $dir_path . self::FOLDER_SIZE_FILE_NAME;
			if ($this->CacheFileHandler->exists($folder_size_file_path)) {
				$cont = trim($this->CacheFileHandler->get($folder_size_file_path));
				return is_numeric($cont) ? $cont : 0;
			}
		}
		return 0;
	}
	
	public function setFolderSize($root_path, $dir_path, $size) {
		if($this->maximum_size && is_numeric($size) && $size != 0) {
			CacheHandlerUtil::configureFolderPath($root_path);
			CacheHandlerUtil::configureFolderPath($dir_path);
		
			$status = true;
			do {
				if (!$this->CacheFileHandler->exists($dir_path))
					break;
			
				$folder_size = $this->getFolderSize($dir_path);
				$new_size = $folder_size + $size;
				$new_size = $new_size >= 0 ? $new_size : 0;
			
				if (!$this->CacheFileHandler->write($dir_path . self::FOLDER_SIZE_FILE_NAME, $new_size))
					$status = false;
				
				if ($dir_path == $root_path)
					break;
				
				$dir_path = dirname($dir_path) . "/";
			} 
			while($dir_path && $dir_path != "/" && $dir_path != "." && $dir_path != "..");
		
			return $status;
		}
		return true;
	}
	
	public function getFolderSettings($dir_path) {
		CacheHandlerUtil::configureFolderPath($dir_path);
		
		$folder_controller_file_path = $dir_path . self::FOLDER_CONTROLLER_FILE_NAME;
		$folder_settings = null;
		
		if ($this->CacheFileHandler->exists($folder_controller_file_path)) {
			$cont = $this->CacheFileHandler->get($folder_controller_file_path);
			$folder_settings = CacheHandlerUtil::unserializeContent($cont);
		}
		return is_array($folder_settings) ? $folder_settings : array();
	}
	
	public function setFolderSettings($dir_path, $folder_settings = array()) {
		CacheHandlerUtil::configureFolderPath($dir_path);
		
		$cont = CacheHandlerUtil::serializeContent($folder_settings);
		return $this->CacheFileHandler->write($dir_path . self::FOLDER_CONTROLLER_FILE_NAME, $cont);
	}
	
	public function getFiles($dir_path) {
		$files = array();
		
		if ($dir_path && is_dir($dir_path) && ($dir = opendir($dir_path)) ) {
			while (($file = readdir($dir)) !== false) {
				if ($file != "." && $file != "..") {
					$files[] = $file;
				}
			}
			closedir($dir);
		}
		return $files;
	}
	
	public function getFolders($dir_path) {
		$folders = array();
		
		if ($dir_path && is_dir($dir_path) && ($dir = opendir($dir_path)) ) {
			while (($file = readdir($dir)) !== false) {
				if ($file != "." && $file != ".." && is_dir($dir_path . $file)) {
					$folders[] = $file;
				}
			}
			closedir($dir);
		}
		return $folders;
	}
	
	public function setMaximumSize($maximum_size) {$this->maximum_size = $maximum_size;}
	public function getMaximumSize() {return $this->maximum_size;}
	
	public function setFolderTotalNumManagerActive($folder_total_num_manager_active) {$this->folder_total_num_manager_active = $folder_total_num_manager_active;}
	public function getFolderTotalNumManagerActive() {return $this->folder_total_num_manager_active;}
	
	public function getCacheFileHandler() {return $this->CacheFileHandler;}
}
?>
