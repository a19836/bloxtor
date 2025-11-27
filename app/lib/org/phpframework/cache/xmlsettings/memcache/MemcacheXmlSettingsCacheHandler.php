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

include_once get_lib("org.phpframework.cache.xmlsettings.XmlSettingsCacheHandler");
include_once get_lib("org.phpframework.memcache.IMemcacheHandler");

class MemcacheXmlSettingsCacheHandler extends XmlSettingsCacheHandler {
	private $MemcacheHandler;
	
	public function setMemcacheHandler(IMemcacheHandler $MemcacheHandler) {$this->MemcacheHandler = $MemcacheHandler;}
	public function getMemcacheHandler() {return $this->MemcacheHandler;}
	
	public function getCache($file_path) {
		if (!empty($this->MemcacheHandler)) {
			$key = CacheHandlerUtil::getFilePathKey($file_path);
			
			$cont = $this->MemcacheHandler->get($key);
			
			if (!empty($cont)) {
				$arr = CacheHandlerUtil::unserializeContent($cont);
			
				return is_array($arr) ? $arr : false;
			}
		}
		return false;
	}
	
	public function setCache($file_path, $data) {
		if (!empty($this->MemcacheHandler) && is_array($data)) {
			$key = CacheHandlerUtil::getFilePathKey($file_path);
			
			$old_data = $this->getCache($file_path);
			$new_data = is_array($old_data) ? array_merge($old_data, $data) : $data;
			
			$cont = CacheHandlerUtil::serializeContent($new_data);
			$ttl = $this->cache_ttl ? $this->cache_ttl + time() : 0;
			
			return $this->MemcacheHandler->set($key, $cont, $ttl);
		}
		return false;
	}
	
	public function isCacheValid($file_path) {
		if (!empty($this->MemcacheHandler)) {
			$key = CacheHandlerUtil::getFilePathKey($file_path);
			
			return $this->MemcacheHandler->get($key) !== false;
		}
		return false;
	}
	
	public function deleteCache($file_path) {
		if (!empty($this->MemcacheHandler)) {
			$key = CacheHandlerUtil::getFilePathKey($file_path);
			
			return $this->MemcacheHandler->delete($key) !== false;
		}
		return false;
	}
}
?>
