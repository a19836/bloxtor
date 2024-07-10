<?php
include_once get_lib("org.phpframework.cache.CacheHandlerUtil");

abstract class ServiceCacheRelatedServicesHandler {
	/*protected*/ const MAXIMUM_ITEMS_PER_FILE = 10000;
	/*protected*/ const RELATED_SERVICES_FOLDER_NAME = "__related";
	
	protected $CacheHandler;
	
	abstract public function addServiceToRelatedKeysToDelete($prefix, $key, $service_related_keys_to_delete, $type = false);
	abstract public function delete($prefix, $key, $type, $key_type, $original_key);
	
	public function getServiceRuleToDeletePath($prefix, $type, $rule_type, $rule_key) {
		return $this->getServiceRuleToDeleteDirPath($prefix, $type) . $this->getServiceRuleToDeleteRelativePath($rule_type, $rule_key);
	}
	
	protected function getServiceRuleToDeleteDirPath($prefix, $type) {
		return $this->CacheHandler->getServiceDirPath($prefix, $type) . self::RELATED_SERVICES_FOLDER_NAME . "/";
	}
	
	protected function getServiceRuleToDeleteRelativePath($rule_type, $rule_key) {
		$rule_type = CacheHandlerUtil::getCorrectKeyType($rule_type);
		
		return strtolower($rule_type) . "/" . md5($rule_key) . "/";
	}
	
	public function getCacheHandler() {return $this->CacheHandler;}
}
?>
