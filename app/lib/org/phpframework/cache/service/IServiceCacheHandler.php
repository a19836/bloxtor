<?php
interface IServiceCacheHandler {
	public function create($prefix, $key, $result, $type = false);
	public function addServiceToRelatedKeysToDelete($prefix, $key, $service_related_keys_to_delete, $type = false);
	public function checkServiceToRelatedKeysToDelete($prefix, $key, $service_related_keys_to_delete, $type = false);
	public function deleteAll($prefix, $type = false);
	public function delete($prefix, $key, $settings = array());
	public function get($prefix, $key, $type = false);
	public function isValid($prefix, $key, $ttl = false, $type = false);
}
?>
