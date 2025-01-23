<?php
include_once get_lib("org.phpframework.cache.xmlsettings.XmlSettingsCacheHandler");

class FileSystemXmlSettingsCacheHandler extends XmlSettingsCacheHandler {
	
	public function getCache($file_path) {
		$this->prepareFilePath($file_path);
		
		if($file_path && file_exists($file_path)) {
			$cont = @file_get_contents($file_path);//maybe the file was delete by another thread, so we need to add the @ so it doesn't give error.
			
			if (!empty($cont)) {
				$arr = CacheHandlerUtil::unserializeContent($cont);
			
				return is_array($arr) ? $arr : false;
			}
		}
		return false;
	}
	
	public function setCache($file_path, $data, $renew_data = false) {
		$orig_file_path = $file_path;
		$this->prepareFilePath($file_path); //This adds a suffix to the path.
		
		if($file_path && file_exists(dirname($file_path))) {
			if(is_array($data)) {
				if (!$renew_data) {
					$old_data = $this->getCache($orig_file_path);
					$new_data = is_array($old_data) ? array_merge($old_data, $data) : $data;
				}
				else
					$new_data = $data;
				
				if(($file = fopen($file_path, "w"))) {
					$cont = CacheHandlerUtil::serializeContent($new_data);
					$status = fputs($file, $cont);
					fclose($file);
			
					return $status === false ? false : true;
				}
			}
		}
		return false;
	}
	
	public function isCacheValid($file_path) {
		$this->prepareFilePath($file_path);
		
		if($file_path && file_exists($file_path))
			return filemtime($file_path) + $this->cache_ttl < time() ? false : true;
		return false;
	}
	
	public function deleteCache($file_path) {
		$this->prepareFilePath($file_path);
		
		if($file_path && file_exists($file_path))
			return unlink($file_path);
		return false;
	}
}
?>
