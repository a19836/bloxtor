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

class CMSModuleSettingsCacheHandler extends FileSystemXmlSettingsCacheHandler {
	/*private*/ const CACHE_DIR_NAME = "cms/module_layer/";
	/*private*/ const LOADED_MODULES_FILE_NAME = "loaded_modules";
	
	protected $cache_root_path;
	protected $is_active = false;
	
	/******* START: Loaded Modules *******/
	public function cachedLoadedModulesExists($modules_path_id) {
		$file_path = $this->getCachedFilePath(self::LOADED_MODULES_FILE_NAME . "_" . $modules_path_id);
		
		if($file_path && $this->isCacheValid($file_path)) {
			$this->prepareFilePath($file_path);
			return file_exists($file_path) && file_get_contents($file_path);
		}
		return false;
	}
	
	public function getCachedLoadedModules($modules_path_id) {
		$file_path = $this->getCachedFilePath(self::LOADED_MODULES_FILE_NAME . "_" . $modules_path_id);
		return $this->getCache($file_path);
	}
	
	public function setCachedLoadedModules($modules_path_id, $data) {
		$file_path = $this->getCachedFilePath(self::LOADED_MODULES_FILE_NAME . "_" . $modules_path_id);
		if($file_path) {
			return $this->setCache($file_path, $data);
		}
		return true;
	}
	/******* END: Loaded Modules *******/
	
	/******* START: COMMON *******/
	public function initCacheDirPath($dir_path) {
		if(!$this->cache_root_path) {
			if($dir_path) {
				CacheHandlerUtil::configureFolderPath($dir_path);
				$dir_path .= self::CACHE_DIR_NAME;
				
				if(CacheHandlerUtil::preparePath($dir_path)) {
					CacheHandlerUtil::configureFolderPath($dir_path);
					$this->cache_root_path = $dir_path;
					
					$this->is_active = true;
				}
			}
		}
	}
	
	public function isActive() {
		return $this->is_active;
	}
	
	public function getCachedId($file_path) {
		return md5(serialize($file_path));
	}
	
	public function getCachedFilePath($file_path) {
		if($this->cache_root_path && $file_path) {
			return $this->cache_root_path . $file_path;
		}
		return false;
	}
	
	public function getCacheRootPath() {
		return $this->cache_root_path;
	}
	/******* END: COMMON *******/
}
?>
