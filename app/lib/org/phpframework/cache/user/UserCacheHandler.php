<?php
include_once get_lib("org.phpframework.cache.CacheHandlerUtil");
include_once get_lib("org.phpframework.cache.user.IUserCacheHandler");

abstract class UserCacheHandler implements IUserCacheHandler {
	protected $root_path;
	protected $ttl;
	protected $serialize;
	
	/*protected*/ const DEFAULT_TTL = 30758400;
	
	public function config($ttl = false, $serialize = true) {
		$this->ttl = $ttl ? $ttl : self::DEFAULT_TTL;
		$this->serialize = $serialize;
	}
	
	public function setRootPath($root_path) {
		CacheHandlerUtil::configureFolderPath($root_path);
		
		$this->root_path = $root_path;
	}
	public function getRootPath() {return $this->root_path;}
	
	public function serializeContent($content) {
		return $this->serialize ? serialize($content) : $content;
	}
	
	public function unserializeContent($content) {
		return $this->serialize ? unserialize($content) : $content;
	}
	
	protected function prepareFilePath(&$file_path) {
		$file_path = CacheHandlerUtil::getCacheFilePath($file_path);
	}
}
?>
