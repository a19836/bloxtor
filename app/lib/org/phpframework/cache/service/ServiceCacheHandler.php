<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.cache.CacheHandlerUtil");
include_once get_lib("org.phpframework.cache.service.IServiceCacheHandler");

abstract class ServiceCacheHandler implements IServiceCacheHandler {
	protected $root_path;
	protected $default_ttl = 9999999999;//IN SECUNDS
	protected $default_type = "php";
	
	public function getServicePath($prefix, $key, $type = false) {
		return $this->getServiceDirPath($prefix, $type) . $this->getServiceRelativePath($key);
	}
	
	public function getServiceDirPath($prefix, $type = false) {
		if(!$type) 
			$type = $this->default_type;
		
		//check if prefix is a complex path (multiple sub-directories) and if it is make it a simple one.
		if ($prefix) {
			$pos = strpos($prefix, "/");
			
			if ($pos > 0 && $pos < strlen($prefix)) {
				$first = $others = "";
				$parts = explode("/", $prefix);
				
				foreach ($parts as $part)
					if (trim($part)) {
						if (!$first)
							$first = trim($part);
						else
							$others .= "_" . trim($part);
					}
				
				$prefix = $first . ($others ? "_" . md5($others) : "");
			}
		}
		
		CacheHandlerUtil::configureFolderPath($prefix);
		
		$root_path = $this->getRootPath();

		if (empty($root_path)) { //TODO: don't let empty root path. Maybe launch an exception here or not...
			echo "TODO/TESTING: ROOT PATH IS UNDEFINED in app/lib/org/phpframework/cache/service/ServiceCacheHandler.php:43";
			echo "\nprefix$prefix\ntype:$type\n";
			die();
		}
		
		return $root_path . $prefix . $type . "/";
	}
	
	private function getServiceRelativePath($key) {
		return $key ? CacheHandlerUtil::getCacheFilePath( $this->getHashedServiceKeyPath($key) ) : null;
	}
	
	private function getHashedServiceKeyPath($key) {
		$hash = hash("md4", $key);
		$hash = substr($hash, strlen($hash) - 15);
		
		//$path = substr($hash, 0, 3) . "/" . substr($hash, 3, 3) . "/" . substr($hash, 6, 3) . "/" . substr($hash, 9, 3) . "/" . substr($hash, 12, 3) . "/";
		$path = substr($hash, 0, 1) . "/" . substr($hash, 1, 1) . "/" . substr($hash, 2, 1) . "/";
		
		return $path . $key;
	}
	
	public function setRootPath($root_path) {
		$root_path = preg_replace('/([\/]+)/', '/', $root_path);
		$root_path .= substr($root_path, strlen($root_path) - 1) != "/" ? "/" : "";
		
		$this->root_path = $root_path;
	}
	public function getRootPath() {return $this->root_path;}
	
	public function setDefaultTTL($default_ttl) {$this->default_ttl = $default_ttl;}
	public function getDefaultTTL() {return $this->default_ttl;}
	
	public function setDefaultType($default_type) {$this->default_type = $default_type;}
	public function getDefaultType() {return $this->default_type;}
	
	protected function prepareContentToInsert($result, $type) {
		if(!$type) {
			$type = $this->default_type;
		}
		
		switch(strtolower($type)) {
			case "text":
				$cont = $result;
				break;
			case "php": 
				$cont = CacheHandlerUtil::serializeContent($result);
				break;
			
			default: $cont = $result;
		}
		
		return $cont;
	}
	
	protected function prepareContentFromGet($result, $type) {
		if(!$type) {
			$type = $this->default_type;
		}
		
		switch(strtolower($type)) {
			case "text":
				$cont = $result;
				break;
			case "php": 
				$cont = CacheHandlerUtil::unserializeContent($result);
				break;
			
			default: $cont = $result;
		}
		
		return $cont;
	}
}
?>
