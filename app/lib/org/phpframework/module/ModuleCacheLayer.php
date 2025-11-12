<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.cache.xmlsettings.filesystem.FileSystemXmlSettingsCacheHandler");

class ModuleCacheLayer extends FileSystemXmlSettingsCacheHandler {
	/*private*/ const CACHE_MODULES_PATH_RELATIVE_FILE_PATH = "__system/modules/modules";
	/*private*/ const CACHE_MODULES_DATA_RELATIVE_DIR_PATH = "__system/modules/data/";
	/*private*/ const CACHE_MODULES_SETTINGS_RELATIVE_DIR_PATH = "__system/modules/cache_settings/";
	/*private*/ const CACHE_MODULES_ROUTERS_RELATIVE_DIR_PATH = "__system/modules/routers/";
	
	private $cache_root_path;
	private $cache_modules_root_path;
	private $cache_modules_path_root_path;
	private $cache_modules_settings_root_path;
	private $cache_modules_routers_root_path;
	
	private $Layer;
	
	public function __construct($Layer) {
		$this->Layer = $Layer;
	}
	
	public function getLayer() { return $this->Layer; }
	
	/******* START: Modules Path *******/
	public function cachedModulesPathExists() {
		$file_path = $this->getModulesPathCacheFilePath();
		if ($file_path && $this->isCacheValid($file_path))
			return true;
		return false;
	}
	
	public function getCachedModulesPath() {
		$file_path = $this->getModulesPathCacheFilePath();
		return $this->getCache($file_path);
	}
	
	public function setCachedModulesPath($modules_path_data) {
		$file_path = $this->getModulesPathCacheFilePath();
		if ($file_path)
			return $this->setCache($file_path, $modules_path_data);
		return true;
	}
	/******* END: Modules Path *******/
	
	/******* START: Modules *******/
	public function cachedModuleExists($module_id) {
		$file_path = $this->getModuleCacheFilePath($module_id);
		if ($file_path && $this->isCacheValid($file_path)) {
			$arr = $this->getCache($file_path);
			return $arr ? true : false;
		}
		return false;
	}
	
	public function getCachedModule($module_id) {
		$file_path = $this->getModuleCacheFilePath($module_id);
		return $this->getCache($file_path);
	}
	
	public function setCachedModule($module_id, $module_data) {
		$file_path = $this->getModuleCacheFilePath($module_id);
		if ($file_path) {
			return $this->setCache($file_path, $module_data);
		}
		return true;
	}
	/******* END: Modules *******/
	
	/******* START: Modules Cache Settings *******/
	public function cachedModuleSettingsExists($module_id) {
		$file_path = $this->getModuleCacheSettingsFilePath($module_id);
		if ($file_path && $this->isCacheValid($file_path)) {
			$arr = $this->getCache($file_path);
			return $arr ? true : false;
		}
		return false;
	}
	
	public function getCachedModuleSettings($module_id) {
		$file_path = $this->getModuleCacheSettingsFilePath($module_id);
		return $this->getCache($file_path);
	}
	
	public function setCachedModuleSettings($module_id, $module_data) {
		$file_path = $this->getModuleCacheSettingsFilePath($module_id);
		if ($file_path)
			return $this->setCache($file_path, $module_data);
		return true;
	}
	/******* END: Modules Cache Settings *******/
	
	/******* START: Modules Cache Routers *******/
	public function cachedModuleRoutersExists($module_id) {
		$file_path = $this->getModuleCacheRoutersFilePath($module_id);
		if ($file_path && $this->isCacheValid($file_path)) {
			$arr = $this->getCache($file_path);
			return $arr ? true : false;
		}
		return false;
	}
	
	public function getCachedModuleRouters($module_id) {
		$file_path = $this->getModuleCacheRoutersFilePath($module_id);
		return $this->getCache($file_path);
	}
	
	public function setCachedModuleRouters($module_id, $module_data) {
		$file_path = $this->getModuleCacheRoutersFilePath($module_id);
		if ($file_path)
			return $this->setCache($file_path, $module_data);
		return true;
	}
	/******* END: Modules Cache Routers *******/
	
	
	/******* START: COMMON *******/
	private function initCacheDirPath() {
		if (!$this->cache_root_path) {
			$dir_path = $this->getLayer()->getModuleCachedLayerDirPath();
			
			if ($dir_path) {
				CacheHandlerUtil::configureFolderPath($dir_path);
				$this->cache_root_path = $dir_path;
			}
		}
	}
	
	private function getModulesPathCacheFilePath() {
		if (!$this->cache_modules_path_root_path) {
			$this->initCacheDirPath();
			
			if ($this->cache_root_path) {
				$file_path = $this->cache_root_path . self::CACHE_MODULES_PATH_RELATIVE_FILE_PATH;
				
				if (CacheHandlerUtil::preparePath(dirname($file_path))) {
					$this->cache_modules_path_root_path = $file_path;
					return $this->cache_modules_path_root_path;
				}
			}
			return false;
		}
		return $this->cache_modules_path_root_path;
	}
	
	private function getModuleCacheFilePath($module_id) {
		if (!$this->cache_modules_root_path) {
			$this->initCacheDirPath();
			
			if ($this->cache_root_path) {
				$dir_path = $this->cache_root_path . self::CACHE_MODULES_DATA_RELATIVE_DIR_PATH;
				
				if (CacheHandlerUtil::preparePath($dir_path)) {
					CacheHandlerUtil::configureFolderPath($dir_path);
					$this->cache_modules_root_path = $dir_path;
					return $this->cache_modules_root_path . $module_id;
				}
			}
			return false;
		}
		return $this->cache_modules_root_path . $module_id;
	}
	
	private function getModuleCacheSettingsFilePath($module_id) {
		if (!$this->cache_modules_settings_root_path) {
			$this->initCacheDirPath();
			
			if ($this->cache_root_path) {
				$dir_path = $this->cache_root_path . self::CACHE_MODULES_SETTINGS_RELATIVE_DIR_PATH;
				
				if (CacheHandlerUtil::preparePath($dir_path)) {
					CacheHandlerUtil::configureFolderPath($dir_path);
					$this->cache_modules_settings_root_path = $dir_path;
					return $this->cache_modules_settings_root_path . $module_id;
				}
			}
			return false;
		}
		return $this->cache_modules_settings_root_path . $module_id;
	}
	
	private function getModuleCacheRoutersFilePath($module_id) {
		if (!$this->cache_modules_routers_root_path) {
			$this->initCacheDirPath();
			
			if ($this->cache_root_path) {
				$dir_path = $this->cache_root_path . self::CACHE_MODULES_ROUTERS_RELATIVE_DIR_PATH;
				
				if (CacheHandlerUtil::preparePath($dir_path)) {
					CacheHandlerUtil::configureFolderPath($dir_path);
					$this->cache_modules_routers_root_path = $dir_path;
					return $this->cache_modules_routers_root_path . $module_id;
				}
			}
			return false;
		}
		return $this->cache_modules_routers_root_path . $module_id;
	}
	/******* END: COMMON *******/
}
?>
