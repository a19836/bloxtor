<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.sqlmap.SQLMapClientCache");

class IBatisClientCache extends SQLMapClientCache {
	/*private*/ const CACHE_DIR_NAME = "__system/ibatis/";
	
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
	/******* END: COMMON *******/
}
?>
