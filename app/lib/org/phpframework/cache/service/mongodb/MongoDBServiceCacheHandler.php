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

include_once get_lib("org.phpframework.cache.service.ServiceCacheHandler");
include_once get_lib("org.phpframework.mongodb.IMongoDBHandler");

class MongoDBServiceCacheHandler extends ServiceCacheHandler {
	private $MongoDBHandler;
	
	public function setMongoDBHandler(IMongoDBHandler $MongoDBHandler) {$this->MongoDBHandler = $MongoDBHandler;}
	public function getMongoDBHandler() {return $this->MongoDBHandler;}
	
	public function create($prefix, $key, $result, $type = false) {
		$status = false;
		
		if($key && !empty($this->MongoDBHandler)) {
			$file_path = $key;
			$collection_name = $this->getCollectionName($prefix, $type);
			
			$cont = $this->prepareContentToInsert($result, $type);
			
			$data = CacheHandlerUtil::serializeContent(array(
				"content" => $cont,
				"created_date" => time(),
			));
			
			$status = $this->MongoDBHandler->set($collection_name, $file_path, $cont);
		}
		return $status;
	}
	
	public function addServiceToRelatedKeysToDelete($prefix, $key, $service_related_keys_to_delete, $type = false) {
		if($key) {
			//we do NOT use this logic for Mongo because we can use regex directly.
			return true;
		}
		return false;
	}
	
	public function checkServiceToRelatedKeysToDelete($prefix, $key, $service_related_keys_to_delete, $type = false) {
		//we do NOT use this logic for Mongo because we can use regex directly.
		return true;
	}
	
	public function deleteAll($prefix, $type = false) {
		if (!empty($this->MongoDBHandler)) {
			$collection_name = $this->getCollectionName($prefix, $type);
			
			return $this->MongoDBHandler->deleteCollection($collection_name);
		}
		return false;
	}
	
	public function delete($prefix, $key, $settings = array()) {
		if (!empty($this->MongoDBHandler)) {
			$found_files = array();
		
			$type = isset($settings["cache_type"]) ? $settings["cache_type"] : null;
			$key_type = isset($settings["key_type"]) ? $settings["key_type"] : null;
			$original_key = isset($settings["original_key"]) ? $settings["original_key"] : null;
			$delete_mode = isset($settings["delete_mode"]) ? $settings["delete_mode"] : null;
			
			$collection_name = $this->getCollectionName($prefix, $type);
			
			if($delete_mode == 2) {//Gets all the items according with the $key_type (prefix, regex, etc...) and then delete that items.
				$regex = CacheHandlerUtil::getRegexFromKeyType($key, $key_type);
				
				return $this->MongoDBHandler->deleteByRegex($collection_name, $regex);
			}
			elseif($delete_mode == 3) {//Gets all the related keys for $key and for each related key returned, gets the correspondent key type (prefix, regex, etc...), gets the correspondent items and then delete them.
				//we do NOT use this logic for Mongo because we can use regex directly.
				$regex = CacheHandlerUtil::getRegexFromKeyType($key, $key_type);
				
				return $this->MongoDBHandler->deleteByRegex($collection_name, $regex);
			}
			else {
				$file_path = $key;
				
				return $this->MongoDBHandler->delete($collection_name, $file_path);
			}
		}
		return false;
	}
	
	public function get($prefix, $key, $type = false) {
		if($key && !empty($this->MongoDBHandler)) {
			$file_path = $key;
			$collection_name = $this->getCollectionName($prefix, $type);
			
			$data = $this->MongoDBHandler->get($collection_name, $file_path);
			$data = CacheHandlerUtil::unserializeContent($data);
			
			$content = isset($data["content"]) ? $data["content"] : false;
		
			if($content) {
				return $this->prepareContentFromGet($content, $type);
			}
		}
		return false;
	}
	
	public function isValid($prefix, $key, $ttl = false, $type = false) {
		if($key && !empty($this->MongoDBHandler)) {
			if(!$ttl) {
				$ttl = $this->default_ttl;
			}
			
			if(is_numeric($ttl) && $ttl > 0) {
				$file_path = $key;
				$collection_name = $this->getCollectionName($prefix, $type);
				
				$data = $this->MongoDBHandler->get($collection_name, $file_path);
				$data = CacheHandlerUtil::unserializeContent($data);
				
				if (isset($data["created_date"])) {
					return empty($data["created_date"]) || $data["created_date"] + $ttl >= time();
				}
			}
		}
		return false;
	}
	
	private function getCollectionName($prefix, $type) {
		$dir_path = $this->getServiceDirPath($prefix, $type);
		
		return CacheHandlerUtil::getFilePathKey($dir_path);
	}
}
?>
