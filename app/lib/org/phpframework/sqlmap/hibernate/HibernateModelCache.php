<?php
include_once get_lib("org.phpframework.cache.xmlsettings.filesystem.FileSystemXmlSettingsCacheHandler");

class HibernateModelCache extends FileSystemXmlSettingsCacheHandler {
	/*private*/ const CACHE_DIR_NAME = "__system/hibernate/sql/";
	
	private $cache_root_path;
	
	public function __construct() {
		
	}
	
	/******* START: SQL STATEMENT *******/
	public static function getCachedSQLName($prefix, $parameters) {
		$keys = is_array($parameters) ? array_keys($parameters) : array();
		sort($keys);
		$keys = serialize($keys);
		
		return "{$prefix}_".hash("md4", $keys).".sql";
	}
	
	public function cachedSQLExists($file_name) {
		$file_path = $this->getCachedSQLPath($file_name);
		if($file_path && $this->isCacheSQLValid($file_path)) { //Do not use $this->isCacheValid bc it will add a suffix to the file_path
			return $this->getCachedSQL($file_name) ? true : false;
		}
		return false;
	}
	
	public function getCachedSQL($file_name) {
		$file_path = $this->getCachedSQLPath($file_name);
		if($file_path && file_exists($file_path)) {
			return file_get_contents($file_path);
		}
		return false;
	}
	
	public function setCachedSQL($file_name, $data) {
		$file_path = $this->getCachedSQLPath($file_name);
		if($file_path) {
			if(($file = fopen($file_path, "w"))) {
				$status = fputs($file, $data);
				fclose($file);
	
				return $status === false ? false : true;
			}
		}
		return false;
	}
	/******* END: SQL *******/
	
	/******* START: COMMON *******/
	public function initCacheDirPath($dir_path) {
		if(!$this->cache_root_path) {
			if($dir_path) {
				CacheHandlerUtil::configureFolderPath($dir_path);
				$dir_path .= self::CACHE_DIR_NAME;
				if(CacheHandlerUtil::preparePath($dir_path)) {
					CacheHandlerUtil::configureFolderPath($dir_path);
					$this->cache_root_path = $dir_path;
				}
			}
		}
	}
	
	public function getCachedSQLPath($file_path) {
		if($this->cache_root_path && $file_path) {
			return $this->cache_root_path . $file_path;
		}
		return false;
	}
	
	public function isCacheSQLValid($file_path) {
		//DO NOT ADD THE $this->prepareFilePath($file_path), otherwise it will add a suffix to the file_path
		
		if($file_path && file_exists($file_path))
			return filemtime($file_path) + $this->cache_ttl < time() ? false : true;
		return false;
	}
	/******* END: COMMON *******/
}
?>
