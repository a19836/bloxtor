<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.sqlmap.SQLMapClientCache");

class HibernateClientCache extends SQLMapClientCache {
	/*private*/ const CACHE_DIR_NAME = "__system/hibernate/";
	/*private*/ const PHP_CLASS_SUFFIX_PATH = "phpclasses/";
	
	/******* START: PHP Class *******/
	public function cachedPHPClassExists($class_name) {
		$file_path = $this->getCachedPHPClassPath($class_name);
		if($file_path && $this->isCachePHPClassValid($file_path)) { //Do not use $this->isCacheValid bc it will add a suffix to the file_path
			return true;
		}
		return false;
	}
	
	public function setCachedPHPClass($class_name, $data) {
		$file_path = $this->getCachedPHPClassPath($class_name);
		if($file_path) {
			if(($file = fopen($file_path, "w"))) {
				$status = fputs($file, $data);
				fclose($file);
	
				return $status === false ? false : true;
			}
		}
		return false;
	}
	/******* END: PHP Class *******/
	
	/******* START: COMMON *******/
	public function initCacheDirPath($dir_path) {
		if(!$this->cache_root_path) {
			if($dir_path) {
				CacheHandlerUtil::configureFolderPath($dir_path);
				$dir_path .= self::CACHE_DIR_NAME;
				if(CacheHandlerUtil::preparePath($dir_path)) {
					CacheHandlerUtil::configureFolderPath($dir_path);
					$this->cache_root_path = $dir_path;
					
					CacheHandlerUtil::preparePath($this->cache_root_path .  self::PHP_CLASS_SUFFIX_PATH);
				}
			}
		}
	}
	
	public function getCachedPHPClassPath($class_name) {
		if($this->cache_root_path && $class_name) {
			return $this->cache_root_path . self::PHP_CLASS_SUFFIX_PATH . $class_name . ".php";
		}
		return false;
	}
	
	public function isCachePHPClassValid($file_path) {
		//DO NOT ADD THE $this->prepareFilePath($file_path), otherwise it will add a suffix to the file_path
		
		if($file_path && file_exists($file_path))
			return filemtime($file_path) + $this->cache_ttl < time() ? false : true;
		return false;
	}
	/******* END: COMMON *******/
}
?>
