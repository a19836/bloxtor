<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.cache.user.UserCacheHandler");
include_once get_lib("org.phpframework.memcache.IMemcacheHandler");

class MemcacheUserCacheHandler extends UserCacheHandler {
	private $MemcacheHandler;
	
	public function setMemcacheHandler(IMemcacheHandler $MemcacheHandler) {$this->MemcacheHandler = $MemcacheHandler;}
	public function getMemcacheHandler() {return $this->MemcacheHandler;}
	
	public function read($file_name) {
		if (!empty($this->MemcacheHandler)) {
			$key = CacheHandlerUtil::getFilePathKey($this->root_path . $file_name);
			
			$cont = $this->MemcacheHandler->get($key);
			
			return !empty($cont) ? $this->unserializeContent($cont) : $cont;
		}
		return false;
	}
	
	public function write($file_name, $data) {
		if (!empty($this->MemcacheHandler) && isset($data)) {
			$key = CacheHandlerUtil::getFilePathKey($this->root_path . $file_name);
			
			$cont = !empty($data) ? $this->serializeContent($data) : $data;
			$ttl = $this->ttl ? $this->ttl + time() : 0;
			
			return $this->MemcacheHandler->set($key, $cont, $ttl);
		}
		return false;
	}
	
	public function isValid($file_name) {
		if (!empty($this->MemcacheHandler)) {
			$key = CacheHandlerUtil::getFilePathKey($this->root_path . $file_name);
			
			$data = $this->MemcacheHandler->get($key);
			
			return $data !== false;
		}
		return false;
	}
	
	public function exists($file_name) {
		if (!empty($this->MemcacheHandler)) {
			$key = CacheHandlerUtil::getFilePathKey($this->root_path . $file_name);
			
			$data = $this->MemcacheHandler->get($key);
			
			return $data !== false;
		}
		return false;
	}
	
	public function delete($file_name) {
		if (!empty($this->MemcacheHandler)) {
			$key = CacheHandlerUtil::getFilePathKey($this->root_path . $file_name);
			
			return $this->MemcacheHandler->delete($key) !== false;
		}
		return false;
	}
}
?>
