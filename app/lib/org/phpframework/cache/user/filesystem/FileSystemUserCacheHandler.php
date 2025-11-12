<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.cache.user.UserCacheHandler");

class FileSystemUserCacheHandler extends UserCacheHandler {
	
	public function read($file_name) {
		if($this->isValid($file_name)) {
			$file_path = $this->root_path . $file_name;
			$this->prepareFilePath($file_path);
			
			$cont = @file_get_contents($file_path);//maybe the file was delete by another thread, so we need to add the @ so it doesn't give error.
			
			return !empty($cont) ? $this->unserializeContent($cont) : $cont;
		}
		return false;
	}
	
	public function write($file_name, $data) {
		$file_path = $this->root_path . $file_name;
		$this->prepareFilePath($file_path);
		
		if($file_name && CacheHandlerUtil::preparePath(dirname($file_path)) && isset($data)) {
			$cont = $this->serializeContent($data);
			
			return file_put_contents($file_path, $cont) !== false;
		}
		return false;
	}
	
	public function isValid($file_name) {
		$file_path = $this->root_path . $file_name;
		$this->prepareFilePath($file_path);
		
		if($this->root_path && $file_name && file_exists($file_path))
			return filemtime($file_path) + $this->ttl < time() ? false : true;
		return false;
	}
	
	public function exists($file_name) {
		$file_path = $this->root_path . $file_name;
		$this->prepareFilePath($file_path);
		
		return $this->root_path && $file_name && file_exists($file_path);
	}
	
	public function delete($file_name, $search_type = null) {
		$file_path = $this->root_path . $file_name;
		$this->prepareFilePath($file_path);
		
		if($this->root_path && $file_name) {
			$search_type = CacheHandlerUtil::getCorrectKeyType($search_type);
			
			if (!$search_type && file_exists($file_path))
				return unlink($file_path);
			else if ($search_type) {
				$folder_path = dirname($file_path);
				$status = true;
				
				if (is_dir($folder_path)) {
					$files = array_diff(scandir($folder_path), array('..', '.'));
					
					if ($files) {
						$search_name = basename($file_name); //use file_name instead of $file_path bc the file_path already contains the file extension.
						//error_log("to search ($search_type): $folder_path/$search_name\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
						
						foreach ($files as $file) {
							$file_check = pathinfo($file, PATHINFO_FILENAME); //get name without extension
							//error_log("to check: $folder_path/$file_check\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
							
							if (CacheHandlerUtil::checkIfKeyTypeMatchValue($file_check, $search_name, $search_type) && !unlink("$folder_path/$file"))
								$status = false;
						}
					}
				}
				
				return $status;
			}
		}
		
		return false;
	}
}
?>
