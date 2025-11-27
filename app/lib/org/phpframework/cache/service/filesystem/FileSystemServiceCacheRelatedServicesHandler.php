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

include_once get_lib("org.phpframework.cache.CacheHandlerUtil");
include_once get_lib("org.phpframework.cache.service.ServiceCacheRelatedServicesHandler");

class FileSystemServiceCacheRelatedServicesHandler extends ServiceCacheRelatedServicesHandler {
	/*protected*/ const MAXIMUM_REGISTRATION_ATTEMPTS = 5;
	/*protected*/ const RELATED_SERVICE_REGISTRATION_STATUS_FOLDER_NAME = "__status";
	/*protected*/ const SERVICE_MAIN_ERROR_FILE_NAME = "__error";
	
	public function __construct($CacheHandler) {
		$this->CacheHandler = $CacheHandler;
	}
	
	/*
	1. loop the $service_related_keys_to_delete array
	2. for each element get the related folder
		$dir_path = .../cache/.../select_item/PHP/__related/
	3. then call the getFilePathKey function to get the $file_path
	4. if the correspondent service does NOT exist yet, call the registerKey function and add a new record for the  in the service key.
	
	However only try to do this loop if the registrationStatus did NOT exceed the MAXIMUM_REGISTRATION_ATTEMPTS
	*/
	public function addServiceToRelatedKeysToDelete($prefix, $key, $service_related_keys_to_delete, $type = false) {
		$status = false;
		
		//echo "\nprefix:$prefix\nkey:$key\ntype:$type\nservice_related_keys_to_delete:";print_r($service_related_keys_to_delete);
		if ($key) {
			$registration_status = $this->getRegistrationKeyStatus($prefix, $key, $type);
			if ($registration_status <= self::MAXIMUM_REGISTRATION_ATTEMPTS) {
				if ($registration_status) {
					$this->setRegistrationKeyStatus($prefix, $key, $type);
				}
			
				$status = true;
				$t = $service_related_keys_to_delete ? count($service_related_keys_to_delete) : 0;
				for ($i = 0; $i < $t; $i++) {
					$item = $service_related_keys_to_delete[$i];
					$item_key = isset($item["key"]) ? $item["key"] : null;
					$item_type = isset($item["type"]) ? $item["type"] : null;
					
					if ($item_type == "regexp" || $item_type == "regex" || $item_type == "start" || $item_type == "begin" || $item_type == "prefix" || $item_type == "middle" || $item_type == "end" || $item_type == "finish" || $item_type == "suffix") {
						$dir_path = $this->getServiceRuleToDeletePath($prefix, $type, $item_type, $item_key);
						//echo "\naddServiceToRelatedKeysToDelete:dir_path:$dir_path\n";
						
						$info = $this->getFilePathKey($dir_path, $key);
						$key_file_path = isset($info["file_path"]) ? $info["file_path"] : null;
					
						if (!$key_file_path) {
							if (!$this->registerKey($dir_path, $key, isset($info["free_file_paths"]) ? $info["free_file_paths"] : null)) {
								$status = false;
						
								++$registration_status;
								$this->setRegistrationKeyStatus($prefix, $key, $type, $registration_status);
								
								if ($registration_status >= self::MAXIMUM_REGISTRATION_ATTEMPTS)
									$this->createServiceMainError($prefix, $type);
							}
						}
					}
				}
			}
		}
		return $status;
	}
	
	/*
	$prefix = select_item
	$key = select_item_id-1_mysql 
	$type = PHP
	$key_type = PREFIX
	$original_key = select_item_id-
	
	$dir_path = .../cache/..../select_item/PHP/__related/prefix/
	*/
	public function delete($prefix, $key, $type, $key_type, $original_key) {
		$dir_path = $this->getServiceRuleToDeletePath($prefix, $type, $key_type, $original_key);
	
		return $this->deleteRelatedServicesKeys($prefix, $key, $type, $key_type, $dir_path);
	}
	
	/*
	1. For each file inside of the $dir_path, cheks if it is a folder. If it is call the same function again, otherwise:
	2. gets the content of the file, unserialize it and loop the correspondent array.
	3. For each item:
		3.1 checks if the item "select_item_id-1_mysql" is IN each item, this is, checkIfKeyTypeMatchValue
		3.2 if it matches gets the item file path
		3.3  then delete the file for the item.
	
	 * TODO: create threads support. This means for each group of 5 files, launch a new thread and delete the correspondent services.
	 */
	protected function deleteRelatedServicesKeys($prefix, $key, $type, $key_type, $dir_path) {
		$status = true;
		
		if ($dir_path && is_dir($dir_path) && ($dir = opendir($dir_path)) ) {
			while (($file = readdir($dir)) !== false) {
				if ($file != "." && $file != "..") {
					$file_path = $dir_path . $file;
					if (is_dir($file_path)) {
						if (!$this->deleteRelatedServicesKeys($prefix, $key, $type, $key_type, $file_path . "/")) {
							$status = false;
						}
					}
					else {
						$cont = @file_get_contents($file_path);//maybe the file was delete by another thread, so we need to add the @ so it doesn't give error.
						$arr = CacheHandlerUtil::unserializeContent($cont);
						
						if (is_array($arr)) {
							$arr_keys = array_keys($arr);
							$t = count($arr_keys);
							for ($i = 0; $i < $t; $i++) {
								$arr_key = $arr_keys[$i];
						
								if (CacheHandlerUtil::checkIfKeyTypeMatchValue($arr_key, $key, $key_type)) {
									$service_file_path = $this->CacheHandler->getServicePath($prefix, $arr_key, $type);
									if (!$this->CacheHandler->getCacheFileHandler()->setFileValidation($service_file_path, 1)) {
										$status = false;
									}
								}
							}
						}
					}
				}
			}
			closedir($dir);
		}
		return $status;
	}
	
	/*
	Based in a $dir_path, loops all the sub_files and for each,
		gets the content,
		unserialize the content
		and checks if the array already contains the $key item.
		If a file contains the key, return that file, otherwise returns an array with potential files where that $key can be inserted.
		
	However the files have a MAXIMUM_ITEMS_PER_FILE, which means that if a file already exceed that limit, the loop will try to find the next file.
	If 
	*/
	protected static function getFilePathKey($dir_path, $key) {
		$key_file_path = false;
		$free_file_paths = array();
		
		if ($dir_path && is_dir($dir_path) && ($dir = opendir($dir_path)) ) {
			while (($file = readdir($dir)) !== false) {
				if ($file != "." && $file != "..") {
					$file_path = $dir_path . $file;
					if (is_dir($file_path)) {
						$result = self::getFilePathKey($file_path . "/", $key);
						
						$key_file_path = isset($result["file_path"]) ? $result["file_path"] : null;
						$free_file_paths = array_merge($free_file_paths, isset($result["free_file_paths"]) ? $result["free_file_paths"] : null);
					}
					else {
						$cont = @file_get_contents($file_path);//maybe the file was delete by another thread, so we need to add the @ so it doesn't give error.
						$arr = CacheHandlerUtil::unserializeContent($cont);
						
						if (is_array($arr) && isset($arr[$key]))
							$key_file_path = $file_path;
						else if (!$arr || count($arr) < self::MAXIMUM_ITEMS_PER_FILE) 
							$free_file_paths[] = $file_path;
					}
					
					if ($key_file_path)
						break;
				}
			}
			closedir($dir);
		}
		return array("file_path" => $key_file_path, "free_file_paths" => $free_file_paths);
	}
	
	/*
	Based in a list of files ($free_file_paths returned from the getFilePathKey function), loop this array and for each item:
		gets the content
		unserialize the content
		and if the array did NOT exceed the MAXIMUM_ITEMS_PER_FILE, add the $key to the array
		then save the array again to the file.
	If all the files already exceed the limit, creates a new file and adds the $key to the file.
	*/
	protected function registerKey($dir_path, $key, $free_file_paths) {
		$registered = false;
		
		$t = $free_file_paths ? count($free_file_paths) : 0;
		for ($i = 0; $i < $t; $i++) {
			$free_file_path = $free_file_paths[$i];
			
			if (file_exists($free_file_path)) {
				$cont = @file_get_contents($free_file_path);//maybe the file was delete by another thread, so we need to add the @ so it doesn't give error.
				$arr = CacheHandlerUtil::unserializeContent($cont);
				
				if (!is_array($arr))
					$arr = array();
				
				if (count($arr) < self::MAXIMUM_ITEMS_PER_FILE) {
					if ($fp = fopen($free_file_path, "r+")) {
						
						$max_num_of_times = 5;
						do {
							$can_write = flock($fp, LOCK_EX);
							if (!$can_write) {
								--$max_num_of_times;
							
								// If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
								usleep(round( rand(0, 100) * 1000 ));
							}
						} 
						while (!$can_write && $max_num_of_times > 0);
						
						//file was locked so now we can store information
						if ($can_write) {
							$cont = @file_get_contents($free_file_path);//maybe the file was delete by another thread, so we need to add the @ so it doesn't give error.
							$arr = CacheHandlerUtil::unserializeContent($cont);
							
							if (is_array($arr) && count($arr) < self::MAXIMUM_ITEMS_PER_FILE) {
								$arr[$key] = true;
								$cont = CacheHandlerUtil::serializeContent($arr);
								
								$fp2 = fopen($free_file_path, "w");
								if ($fp2) {
									$status = fwrite($fp2, $cont);
									$status = $status === false ? false : true;
									if ($status) {
										$registered = true;
									}
									fclose($fp2);
								}
							}
							flock($fp, LOCK_UN);
						}
						fclose($fp);
					}
				}
			}
			
			if($registered) {
				break;
			}
		}
		
		/*
		* Create new file
		*/
		if (!$registered) {
			$CacheFolderHandler = $this->CacheHandler->getCacheFileHandler()->getCacheFolderHandler();
			$new_dir_path = $CacheFolderHandler->getFolderPath($dir_path, true);
			$new_file_name = $new_dir_path . uniqid();
			
			if ($fp = fopen($new_file_name, "w")) {
				$arr = array($key => true);
				$cont = CacheHandlerUtil::serializeContent($arr);
			
				$status = fwrite($fp, $cont);
				$status = $status === false ? false : true;
				
				if ($status) {
					$registered = true;
				}
				fclose($fp);
			}
			$CacheFolderHandler->checkFolderFiles($new_dir_path);
		}
		
		return $registered;
	}
	
	/* ---------------------------------------- XX ---------------------------------------- */
	
	protected function createServiceMainError($prefix, $type = false) {
		$service_main_error_dir_path = $this->CacheHandler->getServiceDirPath($prefix, $type);
		
		if($this->CacheHandler->getCacheFileHandler()->exists($service_main_error_dir_path)) {
			$file_path = $service_main_error_dir_path . self::SERVICE_MAIN_ERROR_FILE_NAME;
			return $this->CacheHandler->getCacheFileHandler()->write($file_path, 1);
		}
		return true;
	}
	
	protected function setRegistrationKeyStatus($prefix, $key, $type, $error_exists = false) {
		$status_file_path = $this->getRegistrationKeyStatusFilePath($prefix, $key, $type);
		
		$exists = $this->CacheHandler->getCacheFileHandler()->exists($status_file_path);
		$continue = (!$exists && $error_exists) || ($exists && $this->CacheHandler->getCacheFileHandler()->getContent($status_file_path) != $error_exists);
		if($continue) {
			if(CacheHandlerUtil::preparePath(dirname($status_file_path)))
				return $this->CacheHandler->getCacheFileHandler()->write($status_file_path, $error_exists);
			return false;
		}
		return true;
	}
	
	public function getRegistrationKeyStatus($prefix, $key, $type) {
		$status_file_path = $this->getRegistrationKeyStatusFilePath($prefix, $key, $type);
		$cont = null;
		
		if($this->CacheHandler->getCacheFileHandler()->exists($status_file_path)) {
			$cont = $this->CacheHandler->getCacheFileHandler()->getContent($status_file_path);
		}
		return $cont;
	}
	
	protected function getRegistrationKeyStatusFilePath($prefix, $key, $type) {
		$service_file_path = $this->CacheHandler->getServicePath($prefix, $key, $type);
	
		$dir_path = dirname($service_file_path);
		$file_name = basename($service_file_path);
		
		return $dir_path . "/" . self::RELATED_SERVICE_REGISTRATION_STATUS_FOLDER_NAME . "/" . hash("md4", $file_name);
	}
}
?>
