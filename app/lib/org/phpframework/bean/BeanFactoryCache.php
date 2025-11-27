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

include_once get_lib("org.phpframework.cache.xmlsettings.filesystem.FileSystemXmlSettingsCacheHandler");

class BeanFactoryCache extends FileSystemXmlSettingsCacheHandler {
	private $cache_dir_name = "__system/beans/";
	
	private $cache_root_path;
	
	private $is_active = false;
	
	public function __construct() {
		
	}
	
	/******* START: File Path *******/
	public function cachedFileExists($file_path) {
		$file_path = $this->getCacheFilePath($file_path);
		if($file_path && $this->isCacheValid($file_path)) {
			$arr = $this->getCache($file_path);
			return $arr ? true : false;
		}
		return false;
	}
	
	public function getCachedFile($file_path) {
		$file_path = $this->getCacheFilePath($file_path);
		return $this->getCache($file_path);
	}
	
	public function setCachedFile($file_path, $data, $renew_data = false) {
		$file_path = $this->getCacheFilePath($file_path);
		if($file_path) {
			return $this->setCache($file_path, $data, $renew_data);
		}
		return true;
	}
	/******* END: File Path *******/
	
	/******* START: COMMON *******/
	public function initCacheDirPath($dir_path) {
		if(!$this->cache_root_path) {
			if($dir_path) {
				CacheHandlerUtil::configureFolderPath($dir_path);
				$dir_path .= $this->cache_dir_name;
				if(CacheHandlerUtil::preparePath($dir_path)) {
					CacheHandlerUtil::configureFolderPath($dir_path);
					$this->cache_root_path = $dir_path;
					
					$this->is_active = true;
				}
			}
		}
		else {
			$this->is_active = true;
		}
	}
	
	public function getCacheFilePath($file_path) {
		if($this->cache_root_path && $file_path) {
			return $this->cache_root_path . hash("md4", $file_path);
		}
		return false;
	}
	
	public function isActive() {
		return $this->is_active;
	}
	/******* END: COMMON *******/
}
?>
