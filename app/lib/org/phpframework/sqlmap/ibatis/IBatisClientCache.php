<?php
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
