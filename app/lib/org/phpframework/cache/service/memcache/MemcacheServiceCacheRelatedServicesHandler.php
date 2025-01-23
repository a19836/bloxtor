<?php
include_once get_lib("org.phpframework.cache.CacheHandlerUtil");
include_once get_lib("org.phpframework.cache.service.ServiceCacheRelatedServicesHandler");

class MemcacheServiceCacheRelatedServicesHandler extends ServiceCacheRelatedServicesHandler {
	/*protected*/ const MEMCACHE_KEY_NAMES_WITH_THE_OTHER_MEMCACHE_KEYS_FILE_NAME = "asdakl3kl24234jk23l";
	
	public function __construct($CacheHandler) {
		$this->CacheHandler = $CacheHandler;
	}
	
	/*
		1. Based in the $service_related_keys_to_delete, loop them and for each
		2. add the new service $key to each $service_related_keys_to_delete item.
	*/
	public function addServiceToRelatedKeysToDelete($prefix, $key, $service_related_keys_to_delete, $type = false) {
		$status = false;
		
		if ($key) {
			$status = true;
			
			$t = $service_related_keys_to_delete ? count($service_related_keys_to_delete) : 0;
			for ($i = 0; $i < $t; $i++) {
				$item = $service_related_keys_to_delete[$i];
				$item_key = isset($item["key"]) ? $item["key"] : null;
				$item_type = isset($item["type"]) ? $item["type"] : null;
			
				if ($item_type == "regexp" || $item_type == "regex" || $item_type == "start" || $item_type == "begin" || $item_type == "prefix" || $item_type == "middle" || $item_type == "end" || $item_type == "finish" || $item_type == "suffix") {
					$dir_path = $this->getServiceRuleToDeletePath($prefix, $type, $item_type, $item_key);
					
					$info = $this->getFilePathKey($dir_path, $key);
					$key_file_path = isset($info["file_path"]) ? $info["file_path"] : null;
				
					if (!$key_file_path) {
						if (!$this->registerKey($dir_path, $key, isset($info["free_file_paths"]) ? $info["free_file_paths"] : null)) {
							$status = false;
						}
					}
				}
			}
		}
		return $status;
	}
	
	/*
		1. ADD $key TO A GENERIC "TABLE". ALL KEYS WILL BE IN THIS GENERIC "TABLE", SO WE CAN EXECUTE THE delete_mode==2
	*/
	public function addServiceKeyToDelete($prefix, $key, $type = false) {
		$status = false;
		
		if($key) {
			$status = true;
			
			$dir_path = $this->getGenericKeysDirPath($prefix, $type);
			
			$info = $this->getFilePathKey($dir_path, $key);
			$key_file_path = isset($info["file_path"]) ? $info["file_path"] : null;
			
			if(!$key_file_path)
				$this->registerKey($dir_path, $key, isset($info["free_file_paths"]) ? $info["free_file_paths"] : null);
		}
		return $status;
	}
	
	public function delete($prefix, $key, $type, $key_type, $original_key) {
		$dir_path = $this->getServiceRuleToDeletePath($prefix, $type, $key_type, $original_key);
	
		return $this->deleteRelatedServicesKeys($prefix, $key, $type, $key_type, $dir_path);
	}
	
	public function deleteBySearchingInAllTheCreatedCachedItems($prefix, $key, $type, $key_type) {
		$dir_path = $this->getGenericKeysDirPath($prefix, $type);
		//echo "\nMemcacheServiceCacheRelatedServicesHandler->deleteBySearchingInAllTheCreatedCachedItems:dir_path:$dir_path\n";
		
		return $this->deleteRelatedServicesKeys($prefix, $key, $type, $key_type, $dir_path);
	}
	
	/*
		1. Based in the "dir_path" memcache key, get the memcache value. This value will be an array with all the correspondent memcache keys which contain the array of items.
		2. loop the value/array and for each item (new memcache key), gets the correspondent memcache value, which will be an array with service items keys.
		3. checks if the value is an array and if it is, loop it
		4. for each element, cheks if checkIfKeyTypeMatchValue, this is, checks if the TYPE (prefix, suffix, regex...) of the $key is in the $arr_key.
		5. If it is, remove the correspondent $arr_key service cached data.
	*/
	/*
	 * TODO: create threads support. This means for each group of 5 files, launch a new thread and delete the correspondent services.
	 */
	protected function deleteRelatedServicesKeys($prefix, $key, $type, $key_type, $dir_path) {
		$status = true;
		
		$ns = $this->getFileNS($dir_path);
		
		$data = $this->CacheHandler->getMemcacheHandler()->nsGet($ns, self::MEMCACHE_KEY_NAMES_WITH_THE_OTHER_MEMCACHE_KEYS_FILE_NAME);
		$data = CacheHandlerUtil::unserializeContent($data);
		
		if (is_array($data)) {
			foreach ($data as $memcache_key_with_items) {
				$arr = $this->CacheHandler->getMemcacheHandler()->nsGet($ns, $memcache_key_with_items);
				$arr = CacheHandlerUtil::unserializeContent($arr);
				
				if(is_array($arr)) {
				//print_r($arr);
					$arr_keys = array_keys($arr);
					$t = count($arr_keys);
					for($i = 0; $i < $t; $i++) {
						$arr_key = $arr_keys[$i];
						
						if(CacheHandlerUtil::checkIfKeyTypeMatchValue($arr_key, $key, $key_type)) {
							$service_file_path = $this->CacheHandler->getServicePath($prefix, $arr_key, $type);
							$service_ns = $this->CacheHandler->getFileNS($prefix, $type);
							
							//echo "\n<br>$service_ns, $service_file_path";
							if(!$this->CacheHandler->getMemcacheHandler()->nsDelete($service_ns, $service_file_path)) {
								$status = false;
							}
						}
					}
				}
			}
		}
		return $status;
	}
	
	/*
		1. Based in the "dir_path" memcache key, get the memcache value. This value will be an array with all the correspondent memcache keys which contain the array of items.
		2. loop the value/array and for each item (new memcache key), gets the correspondent memcache value, which will be an array with service items keys.
		3. checks if the value is an array and if it is, loop it
		4. for each element, cheks if key exists and if it does:
			$key_file_path = $file_path;
		5. otherwise for each element checks if count($arr) < self::MAXIMUM_ITEMS_PER_FILE, and if it does:
			$free_file_paths[] = $file_path;
		6. if $key_file_path is set and exists, do break;
	*/
	protected function getFilePathKey($dir_path, $key) {
		$key_file_path = false;
		$free_file_paths = array();
		
		$ns = $this->getFileNS($dir_path);
		
		$data = $this->CacheHandler->getMemcacheHandler()->nsGet($ns, self::MEMCACHE_KEY_NAMES_WITH_THE_OTHER_MEMCACHE_KEYS_FILE_NAME);
		$data = CacheHandlerUtil::unserializeContent($data);
		
		if (is_array($data)) {
			foreach ($data as $memcache_key_with_items) {
				$arr = $this->CacheHandler->getMemcacheHandler()->nsGet($ns, $memcache_key_with_items);
				$arr = CacheHandlerUtil::unserializeContent($arr);
				
				if(is_array($arr) && isset($arr[$key]))
					$key_file_path = $memcache_key_with_items;
				elseif(!$arr || count($arr) < self::MAXIMUM_ITEMS_PER_FILE)
					$free_file_paths[] = $memcache_key_with_items;
				
				if($key_file_path)
					break;
			}
		}
		return array("file_path" => $key_file_path, "free_file_paths" => $free_file_paths);
	}
	
	/*
		1. Checks if exists any file in the $free_file_paths which contains less than MAXIMUM_ITEMS_PER_FILE and if yes, add the $key item.
		2. Otherwise create new file inside of the $dir_path
	*/
	protected function registerKey($dir_path, $key, $free_file_paths) {
		$registered = false;
	
		$ns = $this->getFileNS($dir_path);
		
		$t = $free_file_paths ? count($free_file_paths) : 0;
		for($i = 0; $i < $t; $i++) {
			$free_file_path = $free_file_paths[$i];
			
			$arr = $this->CacheHandler->getMemcacheHandler()->nsGet($ns, $free_file_path);
			$arr = CacheHandlerUtil::unserializeContent($arr);
			
			if (!is_array($arr)) 
				$arr = array();
			
			if(count($arr) < self::MAXIMUM_ITEMS_PER_FILE) {
				$arr[$key] = true;
				$cont = CacheHandlerUtil::serializeContent($arr);
				
				//echo "<br>$ns, $free_file_path";
				if ($this->CacheHandler->getMemcacheHandler()->nsSet($ns, $free_file_path, $cont)) {
					$registered = true;
					break;
				}
			}
		}
			
		/*
		* Create new file
		*/
		if(!$registered) {
			$new_file_name = uniqid();
			
			$data = $this->CacheHandler->getMemcacheHandler()->nsGet($ns, self::MEMCACHE_KEY_NAMES_WITH_THE_OTHER_MEMCACHE_KEYS_FILE_NAME);
			$data = CacheHandlerUtil::unserializeContent($data);
			
			if (!is_array($data)) {
				$data = array();
			}
			
			$data[] = $new_file_name;
			$cont = CacheHandlerUtil::serializeContent($data);
			
			if ($this->CacheHandler->getMemcacheHandler()->nsSet($ns, self::MEMCACHE_KEY_NAMES_WITH_THE_OTHER_MEMCACHE_KEYS_FILE_NAME, $cont)) {
				$arr = array($key => true);
				$cont = CacheHandlerUtil:serializeContent($arr);
				
				//echo "<br>$ns, $new_file_name";
				if ($this->CacheHandler->getMemcacheHandler()->nsSet($ns, $new_file_name, $cont)) {
					$registered = true;
				}
			}
		}	
	
		return $registered;
	}
	
	/* ---------------------------------------- XX ---------------------------------------- */
	
	private function getFileNS($dir_path) {
		return CacheHandlerUtil::getFilePathKey($dir_path);
	}
	
	//DIR PATH WITH ALL THE SERVICES KEYS
	private function getGenericKeysDirPath($prefix, $type) {
		$dir_path = $this->getServiceRuleToDeletePath($prefix, $type, "equal", "all");
		
		return $dir_path;
	}
}
?>
