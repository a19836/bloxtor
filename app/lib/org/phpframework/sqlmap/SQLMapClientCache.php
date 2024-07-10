<?php
include_once get_lib("org.phpframework.cache.xmlsettings.filesystem.FileSystemXmlSettingsCacheHandler");

class SQLMapClientCache extends FileSystemXmlSettingsCacheHandler {	
	protected $cache_root_path;
	
	/******* START: XML Elm *******/
	public function cachedXMLElmExists($file_path) {
		$file_path = $this->getCachedFilePath($file_path);
		if($file_path && $this->isCacheValid($file_path)) {
			$arr = $this->getCache($file_path);
			return $arr ? true : false;
		}
		return false;
	}
	
	public function getCachedXMLElm($file_path) {
		$file_path = $this->getCachedFilePath($file_path);
		return $this->getCache($file_path);
	}
	
	public function setCachedXMLElm($file_path, $data) {
		$file_path = $this->getCachedFilePath($file_path);
		if($file_path) {
			return $this->setCache($file_path, $data);
		}
		return true;
	}
	
	public function deleteCachedXMLElm($file_path) {
		$file_path = $this->getCachedFilePath($file_path);
		if($file_path) {
			return $this->deleteCache($file_path);
		}
		return true;
	}
	/******* END: XML Elm *******/
	
	/******* START: COMMON *******/
	public function getCachedFilePath($file_path) {
		if($this->cache_root_path && $file_path) {
			return $this->cache_root_path . hash("md4", $file_path);
		}
		return false;
	}
	/******* END: COMMON *******/
}
?>
