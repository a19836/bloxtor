<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.cache.xmlsettings.XmlSettingsCacheHandler");
include_once get_lib("org.phpframework.mongodb.IMongoDBHandler");

class MongoDBXmlSettingsCacheHandler extends XmlSettingsCacheHandler {
	private $MongoDBHandler;
	
	public function setMongoDBHandler(IMongoDBHandler $MongoDBHandler) {$this->MongoDBHandler = $MongoDBHandler;}
	public function getMongoDBHandler() {return $this->MongoDBHandler;}
	
	public function getCache($file_path) {
		if (!empty($this->MongoDBHandler)) {
			$colletion_name = $this->getCollectionName($file_path);
			$key = $this->getFileKey($file_path);
			
			$data = $this->MongoDBHandler->get($colletion_name, $key);
			
			if (empty($data["expire"]) || $data["expire"] >= time()) {
				$cont = $data["content"];
			
				if (!empty($cont)) {
					$arr = CacheHandlerUtil::unserializeContent($cont);
			
					return is_array($arr) ? $arr : false;
				}
			}
		}
		return false;
	}
	
	public function setCache($file_path, $data) {
		if (!empty($this->MongoDBHandler) && is_array($data)) {
			$colletion_name = $this->getCollectionName($file_path);
			$key = $this->getFileKey($file_path);
			
			$old_data = $this->getCache($file_path);
			$new_data = is_array($old_data) ? array_merge($old_data, $data) : $data;
			
			$cont = CacheHandlerUtil::serializeContent($new_data);
			
			return $this->MongoDBHandler->set($colletion_name, $key, array(
				"content" => $cont,
				"expire" => $this->cache_ttl ? $this->cache_ttl + time() : 0
			));
		}
		return false;
	}
	
	public function isCacheValid($file_path) {
		if (!empty($this->MongoDBHandler)) {
			$colletion_name = $this->getCollectionName($file_path);
			$key = $this->getFileKey($file_path);
			
			$data = $this->MongoDBHandler->get($colletion_name, $key);
			
			return empty($data["expire"]) || $data["expire"] >= time();
		}
		return false;
	}
	
	public function deleteCache($file_path) {
		if (!empty($this->MongoDBHandler)) {
			$colletion_name = $this->getCollectionName($file_path);
			$key = $this->getFileKey($file_path);
			
			return $this->MongoDBHandler->delete($colletion_name, $key) !== false;
		}
		return false;
	}
	
	protected function getCollectionName($file_path) {
		return CacheHandlerUtil::getFilePathKey( dirname($file_path) );
	}
	
	protected function getFileKey($file_path) {
		return CacheHandlerUtil::getFilePathKey( basename($file_path) );
	}
}
?>
