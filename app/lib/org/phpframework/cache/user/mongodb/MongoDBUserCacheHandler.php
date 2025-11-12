<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.cache.user.UserCacheHandler");
include_once get_lib("org.phpframework.mongodb.IMongoDBHandler");

class MongoDBUserCacheHandler extends UserCacheHandler {
	private $MongoDBHandler;
	
	public function setMongoDBHandler(IMongoDBHandler $MongoDBHandler) {$this->MongoDBHandler = $MongoDBHandler;}
	public function getMongoDBHandler() {return $this->MongoDBHandler;}
	
	public function read($file_name) {
		if (!empty($this->MongoDBHandler)) {
			$colletion_name = $this->getCollectionName();
			$key = $this->getFileKey($file_name);
			
			$data = $this->MongoDBHandler->get($colletion_name, $key);
			
			if (empty($data["expire"]) || $data["expire"] >= time()) {
				$cont = $data["content"];
			
				return !empty($cont) ? $this->unserializeContent($cont) : $cont;
			}
		}
		return false;
	}
	
	public function write($file_name, $data) {
		if (!empty($this->MongoDBHandler) && isset($data)) {
			$colletion_name = $this->getCollectionName();
			$key = $this->getFileKey($file_name);
			
			$cont = !empty($data) ? $this->serializeContent($data) : $data;
			
			return $this->MongoDBHandler->set($colletion_name, $key, array(
				"content" => $cont,
				"expire" => $this->ttl ? $this->ttl + time() : 0
			));
		}
		return false;
	}
	
	public function isValid($file_name) {
		if (!empty($this->MongoDBHandler)) {
			$colletion_name = $this->getCollectionName();
			$key = $this->getFileKey($file_name);
			
			$data = $this->MongoDBHandler->get($colletion_name, $key);
			
			return empty($data["expire"]) || $data["expire"] >= time();
		}
		return false;
	}
	
	public function exists($file_name) {
		if (!empty($this->MongoDBHandler)) {
			$colletion_name = $this->getCollectionName();
			$key = $this->getFileKey($file_name);
			
			$data = $this->MongoDBHandler->get($colletion_name, $key);
			
			return isset( $data );
		}
		return false;
	}
	
	public function delete($file_name) {
		if (!empty($this->MongoDBHandler)) {
			$colletion_name = $this->getCollectionName();
			$key = $this->getFileKey($file_name);
			
			return $this->MongoDBHandler->delete($colletion_name, $key) !== false;
		}
		return false;
	}
	
	protected function getCollectionName() {
		return CacheHandlerUtil::getFilePathKey($this->root_path);
	}
	
	protected function getFileKey($file_name) {
		return CacheHandlerUtil::getFilePathKey($file_name);
	}
}
?>
