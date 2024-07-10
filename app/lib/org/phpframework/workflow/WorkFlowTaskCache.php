<?php
include_once get_lib("org.phpframework.cache.xmlsettings.filesystem.FileSystemXmlSettingsCacheHandler");
include_once get_lib("org.phpframework.cache.CacheHandlerUtil");

class WorkFlowTaskCache extends FileSystemXmlSettingsCacheHandler {
	/*private*/ const CACHE_DIR_NAME = "workflow/__system/";
	/*private*/ const LOADED_TASKS_FILE_NAME = "loaded_tasks";
	/*private*/ const LOADED_TASKS_INCLUDES_FILE_NAME = "loaded_tasks_includes";
	/*private*/ const LOADED_TASKS_SETTINGS_FILE_NAME = "loaded_tasks_settings";
	/*private*/ const LOADED_TASKS_CONTAINERS_FILE_NAME = "loaded_tasks_containers";
	
	protected $cache_root_path;
	protected $is_active = false;
	
	/******* START: Loaded Tasks *******/
	public function cachedLoadedTasksExists($tasks_id) {
		$file_path = $this->getCachedFilePath(self::LOADED_TASKS_FILE_NAME . "_" . $tasks_id);
		
		if($file_path && $this->isCacheValid($file_path)) {
			$this->prepareFilePath($file_path);
			return file_exists($file_path) && file_get_contents($file_path);
		}
		return false;
	}
	
	public function getCachedLoadedTasks($tasks_id) {
		$file_path = $this->getCachedFilePath(self::LOADED_TASKS_FILE_NAME . "_" . $tasks_id);
		return $this->getCache($file_path);
	}
	
	public function setCachedLoadedTasks($tasks_id, $data) {
		$file_path = $this->getCachedFilePath(self::LOADED_TASKS_FILE_NAME . "_" . $tasks_id);
		if($file_path) {
			return $this->setCache($file_path, $data);
		}
		return true;
	}
	/******* END: Loaded Tasks *******/
	
	/******* START: Loaded Tasks Includes *******/
	public function cachedLoadedTasksIncludesExists($tasks_id) {
		$file_path = $this->getCachedFilePath(self::LOADED_TASKS_INCLUDES_FILE_NAME . "_" . $tasks_id);
	
		if($file_path && $this->isCacheValid($file_path)) {
			$this->prepareFilePath($file_path);
			return file_exists($file_path) && file_get_contents($file_path);
		}
		return false;
	}
	
	public function getCachedLoadedTasksIncludes($tasks_id) {
		$file_path = $this->getCachedFilePath(self::LOADED_TASKS_INCLUDES_FILE_NAME . "_" . $tasks_id);
		return $this->getCache($file_path);
	}
	
	public function setCachedLoadedTasksIncludes($tasks_id, $data) {
		$file_path = $this->getCachedFilePath(self::LOADED_TASKS_INCLUDES_FILE_NAME . "_" . $tasks_id);
		if($file_path) {
			return $this->setCache($file_path, $data);
		}
		return true;
	}
	/******* END: Loaded Tasks Includes *******/
	
	/******* START: Loaded Tasks Settings *******/
	public function cachedLoadedTasksSettingsExists($tasks_id) {
		$file_path = $this->getCachedFilePath(self::LOADED_TASKS_SETTINGS_FILE_NAME . "_" . $tasks_id);
		
		if($file_path && $this->isCacheValid($file_path)) {
			$this->prepareFilePath($file_path);
			return file_exists($file_path) && file_get_contents($file_path);
		}
		return false;
	}
	
	public function getCachedLoadedTasksSettings($tasks_id) {
		$file_path = $this->getCachedFilePath(self::LOADED_TASKS_SETTINGS_FILE_NAME . "_" . $tasks_id);
		return $this->getCache($file_path);
	}
	
	public function setCachedLoadedTasksSettings($tasks_id, $data) {
		$file_path = $this->getCachedFilePath(self::LOADED_TASKS_SETTINGS_FILE_NAME . "_" . $tasks_id);
		if($file_path) {
			return $this->setCache($file_path, $data);
		}
		return true;
	}
	/******* END: Loaded Tasks Settings *******/
	
	/******* START: Loaded Tasks Containers *******/
	public function cachedTasksContainersExists($tasks_id) {
		$file_path = $this->getCachedFilePath(self::LOADED_TASKS_CONTAINERS_FILE_NAME . "_" . $tasks_id);
		
		if($file_path && $this->isCacheValid($file_path)) {
			$this->prepareFilePath($file_path);
			return file_exists($file_path) && file_get_contents($file_path);
		}
		return false;
	}
	
	public function getCachedTasksContainers($tasks_id) {
		$file_path = $this->getCachedFilePath(self::LOADED_TASKS_CONTAINERS_FILE_NAME . "_" . $tasks_id);
		return $this->getCache($file_path);
	}
	
	public function setCachedTasksContainers($tasks_id, $data) {
		$file_path = $this->getCachedFilePath(self::LOADED_TASKS_CONTAINERS_FILE_NAME . "_" . $tasks_id);
		if($file_path) {
			return $this->setCache($file_path, $data);
		}
		return true;
	}
	/******* END: Loaded Tasks Containers *******/
	
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
	
	public function getCachedFilePath($file_path) {
		if($this->cache_root_path && $file_path) {
			return $this->cache_root_path . $file_path;
		}
		return false;
	}
	
	public function getCachedId($tasks_folder_paths) {
		return md5(serialize($tasks_folder_paths));
	}
	
	public function flushCache() {
		return CacheHandlerUtil::deleteFolder($this->cache_root_path);
	}
	/******* END: COMMON *******/
}
?>
