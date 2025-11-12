<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.cache.service.ServiceCacheHandler");
include get_lib("org.phpframework.cache.service.filesystem.FileSystemServiceCacheFileHandler");
include get_lib("org.phpframework.cache.service.filesystem.FileSystemServiceCacheRelatedServicesHandler");

class FileSystemServiceCacheHandler extends ServiceCacheHandler {
	private $CacheFileHandler;
	private $CacheRelatedServicesHandler;
	
	public function __construct($maximum_size = false, $folder_total_num_manager_active = false) {
		$this->CacheFileHandler = new FileSystemServiceCacheFileHandler($this, $maximum_size, $folder_total_num_manager_active);
		$this->CacheRelatedServicesHandler = new FileSystemServiceCacheRelatedServicesHandler($this);
	}
	
	/*
	Simply creates the cache file for correspondent service.
	*/
	public function create($prefix, $key, $result, $type = false) {
		$status = false;
		
		if($key) {
			$file_path = $this->getServicePath($prefix, $key, $type);
			
			$cont = $this->prepareContentToInsert($result, $type);
			
			$status = $this->CacheFileHandler->create($file_path, $cont);
		}
		return $status;
	}
	
	/*
	Calls $this->CacheRelatedServicesHandler->addServiceToRelatedKeysToDelete.
	*/
	public function addServiceToRelatedKeysToDelete($prefix, $key, $service_related_keys_to_delete, $type = false) {
		if($key) {
			return $this->CacheRelatedServicesHandler->addServiceToRelatedKeysToDelete($prefix, $key, $service_related_keys_to_delete, $type);
		}
		return false;
	}
	
	/*
	Calls $this->CacheRelatedServicesHandler->addServiceToRelatedKeysToDelete if the getRegistrationKeyStatus is still valid.
	*/
	public function checkServiceToRelatedKeysToDelete($prefix, $key, $service_related_keys_to_delete, $type = false) {
		if($this->CacheRelatedServicesHandler->getRegistrationKeyStatus($prefix, $key, $type)) {
			return $this->addServiceToRelatedKeysToDelete($prefix, $key, $service_related_keys_to_delete, $type);
		}
		return true;
	}
	
	/*
	Delete All the cache for the correspondent prefix.
	*/
	public function deleteAll($prefix, $type = false) {
		$dir_path = $this->getServiceDirPath($prefix, $type);
		//echo "<br>dir_path:$dir_path<br>";
		
		return $this->CacheFileHandler->deleteFolder($dir_path);
	}
	
	/*
	Delete the cache according with delete method. 
	The delete methods are explained bellow;
		$prefix = select_item;
		$key = select_item_id-1_mysql;
		$type = PHP;
		$key_type = PREFIX;
		$original_key = select_item_id-;
		$delete_mode = 2;
	*/
	public function delete($prefix, $key, $settings = array()) {
		$type = isset($settings["cache_type"]) ? $settings["cache_type"] : null;
		$key_type = isset($settings["key_type"]) ? $settings["key_type"] : null;
		$original_key = isset($settings["original_key"]) ? $settings["original_key"] : null;
		$delete_mode = isset($settings["delete_mode"]) ? $settings["delete_mode"] : null;
		
		$found_files = null;
		
		//echo "<br>\nprefix:$prefix\n<br>key:$key\n<br>settings:";print_r($settings);
		//echo "<br>\ngetServiceRuleToDeletePath:".$this->CacheRelatedServicesHandler->getServiceRuleToDeletePath($prefix, $type, $key_type, $original_key);
		
		if($delete_mode == 2) {//Gets all the items according with the $key_type (prefix, regex, etc...) and then delete that items.
			$file_path = $this->getServiceDirPath($prefix, $type);
			//echo "<br>file_path:$file_path<br>";
			
			//echo "$file_path, $key, $key_type";
			$found_files = $this->CacheFileHandler->search($file_path, $key, $key_type);
			//echo "<pre>";print_r($found_files);echo "</pre>";
			
			return $this->CacheFileHandler->delete($found_files);
		}
		elseif($delete_mode == 3) {//Gets all the related keys for $key and for each related key returned, gets the correspondent key type (prefix, regex, etc...), gets the correspondent items and then delete them.
			return $this->CacheRelatedServicesHandler->delete($prefix, $key, $type, $key_type, $original_key);
		}
		else {
			$file_path = $this->getServicePath($prefix, $key, $type);
			$file_path = $this->CacheFileHandler->getPath($file_path);
			
			if ($file_path) {
				$found_files = array($file_path);
			}
			
			return $this->CacheFileHandler->delete($found_files);
		}
	}
	
	public function get($prefix, $key, $type = false) {
		if($key) {
			$file_path = $this->getServicePath($prefix, $key, $type);
			
			$content = $this->CacheFileHandler->get($file_path);
			
			if($content) {
				return $this->prepareContentFromGet($content, $type);
			}
		}
		return false;
	}
	
	public function isValid($prefix, $key, $ttl = false, $type = false) {
		if($key) {
			if(!$ttl) {
				$ttl = $this->default_ttl;
			}
			
			if(is_numeric($ttl) && $ttl > 0) {
				$file_path = $this->getServicePath($prefix, $key, $type);
				$file_path = $this->CacheFileHandler->getPath($file_path);
				if($file_path && $this->CacheFileHandler->exists($file_path)) {
					$file_m_time = $this->CacheFileHandler->getFileMTime($file_path);
					$status = $file_m_time + $ttl < time() ? false : true;
					if($status) {
						return $this->CacheFileHandler->isValid($file_path);
					}
				}
			}
		}
		return false;
	}
	
	public function getCacheFileHandler() {return $this->CacheFileHandler;}
}
?>
