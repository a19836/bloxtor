<?php
include_once get_lib("org.phpframework.cache.service.ServiceCacheHandler");
include_once get_lib("org.phpframework.cache.service.memcache.MemcacheServiceCacheRelatedServicesHandler");
include_once get_lib("org.phpframework.memcache.IMemcacheHandler");

class MemcacheServiceCacheHandler extends ServiceCacheHandler {
	private $CacheRelatedServicesHandler;
	private $MemcacheHandler;
	
	public function __construct() {
		$this->CacheRelatedServicesHandler = new MemcacheServiceCacheRelatedServicesHandler($this);
	}
	
	public function setMemcacheHandler(IMemcacheHandler $MemcacheHandler) {$this->MemcacheHandler = $MemcacheHandler;}
	public function getMemcacheHandler() {return $this->MemcacheHandler;}
	
	public function create($prefix, $key, $result, $type = false) {
		$status = false;
		
		if($key && !empty($this->MemcacheHandler)) {
			$file_path = $key;
			$ns = $this->getFileNS($prefix, $type);
			
			$cont = $this->prepareContentToInsert($result, $type);
			
			$data = serialize(array(
				"content" => $cont,
				"created_date" => time(),
			));
			
			$status = $this->MemcacheHandler->nsSet($ns, $file_path, $cont);
		}
		return $status;
	}
	
	public function addServiceToRelatedKeysToDelete($prefix, $key, $service_related_keys_to_delete, $type = false) {
		if($key) {
			//echo "<br>\naddServiceToRelatedKeysToDelete: $prefix, $key, $service_related_keys_to_delete, $type";
			$this->CacheRelatedServicesHandler->addServiceKeyToDelete($prefix, $key, $type);
			
			return $this->CacheRelatedServicesHandler->addServiceToRelatedKeysToDelete($prefix, $key, $service_related_keys_to_delete, $type);
		}
		return false;
	}
	
	public function checkServiceToRelatedKeysToDelete($prefix, $key, $service_related_keys_to_delete, $type = false) {
		return $this->addServiceToRelatedKeysToDelete($prefix, $key, $service_related_keys_to_delete, $type);
	}
	
	public function deleteAll($prefix, $type = false) {
		if (!empty($this->MemcacheHandler)) {
			$ns = $this->getFileNS($prefix, $type);
		
			return $this->MemcacheHandler->nsFlush($ns);
		}
		return false;
	}
	
	public function delete($prefix, $key, $settings = array()) {
		if (!empty($this->MemcacheHandler)) {
			$type = isset($settings["cache_type"]) ? $settings["cache_type"] : null;
			$key_type = isset($settings["key_type"]) ? $settings["key_type"] : null;
			$original_key = isset($settings["original_key"]) ? $settings["original_key"] : null;
			$delete_mode = isset($settings["delete_mode"]) ? $settings["delete_mode"] : null;
			
			if($delete_mode == 2) {//Gets all the items according with the $key_type (prefix, regex, etc...) and then delete that items.
				return $this->CacheRelatedServicesHandler->deleteBySearchingInAllTheCreatedCachedItems($prefix, $key, $type, $key_type);
			}
			elseif($delete_mode == 3) {//Gets all the related keys for $key and for each related key returned, gets the correspondent key type (prefix, regex, etc...), gets the correspondent items and then delete them.
				return $this->CacheRelatedServicesHandler->delete($prefix, $key, $type, $key_type, $original_key);
			}
			else {
				$file_path = $key;
				$ns = $this->getFileNS($prefix, $type);
				
				return $this->MemcacheHandler->nsDelete($ns, $file_path);
			}
		}
		return false;
	}
	
	public function get($prefix, $key, $type = false) {
		if($key && !empty($this->MemcacheHandler)) {
			$file_path = $key;
			$ns = $this->getFileNS($prefix, $type);
			
			$data = $this->MemcacheHandler->nsGet($ns, $file_path);
			$data = !empty($data) ? unserialize($data) : false;
			
			$content = isset($data["content"]) ? $data["content"] : false;
		
			if($content) {
				return $this->prepareContentFromGet($content, $type);
			}
		}
		return false;
	}
	
	public function isValid($prefix, $key, $ttl = false, $type = false) {
		if($key && !empty($this->MemcacheHandler)) {
			if(!$ttl) {
				$ttl = $this->default_ttl;
			}
			
			if(is_numeric($ttl) && $ttl > 0) {
				$file_path = $key;
				$ns = $this->getFileNS($prefix, $type);
				
				$data = $this->MemcacheHandler->nsGet($ns, $file_path);
				$data = !empty($data) ? unserialize($data) : false;
				
				if (isset($data["created_date"]))
					return empty($data["created_date"]) || $data["created_date"] + $ttl >= time();
			}
		}
		return false;
	}
	
	public function getFileNS($prefix, $type) {
		$dir_path = $this->getServiceDirPath($prefix, $type);
		
		return CacheHandlerUtil::getFilePathKey($dir_path);
	}
}
?>
