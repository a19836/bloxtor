<?php
include_once get_lib("org.phpframework.cache.CacheHandlerUtil");
include_once get_lib("org.phpframework.cache.xmlsettings.IXmlSettingsCacheHandler");

abstract class XmlSettingsCacheHandler implements IXmlSettingsCacheHandler {
	protected $cache_ttl = 30758400;//in secunds ==> 1 year
	
	public function setCacheTTL($cache_ttl) {$this->cache_ttl = $cache_ttl;}
	public function getCacheTTL() {return $this->cache_ttl;}
	
	protected function prepareFilePath(&$file_path) {
		$file_path = CacheHandlerUtil::getCacheFilePath($file_path);
	}
}
?>
