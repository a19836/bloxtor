<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

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
