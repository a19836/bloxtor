<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

include_once get_lib("org.phpframework.layer.Layer");

class DBLayer extends Layer {
	
	public function __construct($settings = array()) {
		parent::__construct($settings);
	}
	
	public function getLayerPathSetting() {
		if (empty($this->settings["dbl_path"]))
			launch_exception(new Exception("'DBLayer->settings[dbl_path]' variable cannot be empty!"));
		
		return $this->settings["dbl_path"];
	}
	
	/*************************** QUERY ***********************************/
	
	public function getDBDriversName() {
		$keys = $this->getBrokers();
		
		return is_array($keys) ? array_keys($keys) : array();
	}
	
	public function getFunction($function_name, $parameters = false, $options = false) {
		debug_log_function("DBLayer->getFunction", array($function_name, $parameters, $options));
		
		$function_name_lower = strtolower($function_name);
		$is_cache_active = $this->isCacheActive();
		
		if ($is_cache_active && empty($options["no_cache"]) && $this->getCacheLayer()->isValid(1, $function_name_lower, $parameters, $options))
			return $this->getCacheLayer()->get(1, $function_name_lower, $parameters, $options);
		
		$bn = isset($options["db_driver"]) ? $options["db_driver"] : null;
		$result = $this->getBroker($bn)->getFunction($function_name, $parameters, $options);
			
		if ($is_cache_active)
			$this->getCacheLayer()->check(1, $function_name_lower, $parameters, $result, $options);
		
		return $result;
	}
	
	public function getData($sql, $options = false) {
		debug_log_function("DBLayer->getData", array($sql, $options));
		
		$is_cache_active = $this->isCacheActive();
		
		if ($is_cache_active && empty($options["no_cache"]) && $this->getCacheLayer()->isValid(1, "getData", $sql, $options))
			return $this->getCacheLayer()->get(1, "getData", $sql, $options);
		
		$bn = isset($options["db_driver"]) ? $options["db_driver"] : null;
		$result = $this->getBroker($bn)->getData($sql, $options);
			
		if ($is_cache_active)
			$this->getCacheLayer()->check(1, "getData", $sql, $result, $options);
		
		return $result;
	}
	
	public function setData($sql, $options = false) {
		debug_log_function("DBLayer->setData", array($sql, $options));
		
		$is_cache_active = $this->isCacheActive();
		
		if ($is_cache_active && empty($options["no_cache"]) && $this->getCacheLayer()->isValid(1, "setData", $sql, $options))
			return $this->getCacheLayer()->get(1, "setData", $sql, $options);
		
		$bn = isset($options["db_driver"]) ? $options["db_driver"] : null;
		$result = $this->getBroker($bn)->setData($sql, $options);
		
		if ($is_cache_active)
			$this->getCacheLayer()->check(1, "setData", $sql, $result, $options);
		
		return $result;
	}
	
	public function getSQL($sql, $options = false) {
		debug_log_function("DBLayer->getSQL", array($sql, $options));
		
		$is_cache_active = $this->isCacheActive();
		
		if ($is_cache_active && empty($options["no_cache"]) && $this->getCacheLayer()->isValid(1, "getSQL", $sql, $options))
			return $this->getCacheLayer()->get(1, "getSQL", $sql, $options);
		
		$bn = isset($options["db_driver"]) ? $options["db_driver"] : null;
		$result = $this->getBroker($bn)->getSQL($sql, $options);
			
		if ($is_cache_active)
			$this->getCacheLayer()->check(1, "getSQL", $sql, $result, $options);
		
		return $result;
	}
	
	public function setSQL($sql, $options = false) {
		debug_log_function("DBLayer->setSQL", array($sql, $options));
		
		$is_cache_active = $this->isCacheActive();
		
		if ($is_cache_active && empty($options["no_cache"]) && $this->getCacheLayer()->isValid(1, "setSQL", $sql, $options))
			return $this->getCacheLayer()->get(1, "setSQL", $sql, $options);
		
		$bn = isset($options["db_driver"]) ? $options["db_driver"] : null;
		$result = $this->getBroker($bn)->setSQL($sql, $options);
			
		if ($is_cache_active)
			$this->getCacheLayer()->check(1, "setSQL", $sql, $result, $options);
		
		return $result;
	}
	
	public function getInsertedId($options = false) {
		debug_log_function("DBLayer->getInsertedId", array($options));
		
		//It doesn't make sense to cache the getInsertedId function
		/*$is_cache_active = $this->isCacheActive();
		
		if ($is_cache_active && empty($options["no_cache"]) && $this->getCacheLayer()->isValid(1, "getInsertedId", null, $options))
			return $this->getCacheLayer()->get(1, "getInsertedId", null, $options);
		*/
	
		$bn = isset($options["db_driver"]) ? $options["db_driver"] : null;
		$result = $this->getBroker($bn)->getInsertedId($options);
			
		/*if ($is_cache_active)
			$this->getCacheLayer()->check(1, "getInsertedId", null, $result, $options);
		*/
		return $result;
	}
	
	public function insertObject($table_name, $attributes, $options = false) {
    		debug_log_function("DBLayer->insertObject", array($table_name, $attributes, $options));
		
		$bn = isset($options["db_driver"]) ? $options["db_driver"] : null;
		return $this->getBroker($bn)->insertObject($table_name, $attributes, $options);
	}
	
	public function updateObject($table_name, $attributes, $conditions = false, $options = false) {
    		debug_log_function("DBLayer->updateObject", array($table_name, $attributes, $conditions, $options));
		
		$bn = isset($options["db_driver"]) ? $options["db_driver"] : null;
		return $this->getBroker($bn)->updateObject($table_name, $attributes, $conditions, $options);
	}
	
	public function deleteObject($table_name, $conditions = false, $options = false) {
    		debug_log_function("DBLayer->deleteObject", array($table_name, $conditions, $options));
		
		$bn = isset($options["db_driver"]) ? $options["db_driver"] : null;
		return $this->getBroker($bn)->deleteObject($table_name, $conditions, $options);
	}
	
	public function findObjects($table_name, $attributes = false, $conditions = false, $options = false) {
    		debug_log_function("DBLayer->findObjects", array($table_name, $attributes, $conditions, $options));
		
		$is_cache_active = $this->isCacheActive();
		
		if ($is_cache_active && empty($options["no_cache"]) && $this->getCacheLayer()->isValid(1, "findObjects", null, $options))
			return $this->getCacheLayer()->get(1, "findObjects", null, $options);
	
		$bn = isset($options["db_driver"]) ? $options["db_driver"] : null;
		$result = $this->getBroker($bn)->findObjects($table_name, $attributes, $conditions, $options);
			
		if ($is_cache_active)
			$this->getCacheLayer()->check(1, "findObjects", null, $result, $options);
		
		return $result;
	}
	
	public function countObjects($table_name, $conditions = false, $options = false) {
    		debug_log_function("DBLayer->countObjects", array($table_name, $conditions, $options));
		
		$is_cache_active = $this->isCacheActive();
		
		if ($is_cache_active && empty($options["no_cache"]) && $this->getCacheLayer()->isValid(1, "countObjects", null, $options))
			return $this->getCacheLayer()->get(1, "countObjects", null, $options);
	
		$bn = isset($options["db_driver"]) ? $options["db_driver"] : null;
		$result = $this->getBroker($bn)->countObjects($table_name, $conditions, $options);
			
		if ($is_cache_active)
			$this->getCacheLayer()->check(1, "countObjects", null, $result, $options);
		
		return $result;
	}
	
	public function findRelationshipObjects($table_name, $rel_elm, $parent_conditions = false, $options = false) {
    		debug_log_function("DBLayer->findRelationshipObjects", array($table_name, $rel_elm, $parent_conditions, $options));
		
		$is_cache_active = $this->isCacheActive();
		
		if ($is_cache_active && empty($options["no_cache"]) && $this->getCacheLayer()->isValid(1, "findRelationshipObjects", null, $options))
			return $this->getCacheLayer()->get(1, "findRelationshipObjects", null, $options);
	
		$bn = isset($options["db_driver"]) ? $options["db_driver"] : null;
		$result = $this->getBroker($bn)->findRelationshipObjects($table_name, $rel_elm, $parent_conditions, $options);
			
		if ($is_cache_active)
			$this->getCacheLayer()->check(1, "findRelationshipObjects", null, $result, $options);
		
		return $result;
	}
	
	public function countRelationshipObjects($table_name, $rel_elm, $parent_conditions = false, $options = false) {
    		debug_log_function("DBLayer->countRelationshipObjects", array($table_name, $rel_elm, $parent_conditions, $options));
		
		$is_cache_active = $this->isCacheActive();
		
		if ($is_cache_active && empty($options["no_cache"]) && $this->getCacheLayer()->isValid(1, "countRelationshipObjects", null, $options))
			return $this->getCacheLayer()->get(1, "countRelationshipObjects", null, $options);
		
		$bn = isset($options["db_driver"]) ? $options["db_driver"] : null;
		$result = $this->getBroker($bn)->countRelationshipObjects($table_name, $rel_elm, $parent_conditions, $options);
			
		if ($is_cache_active)
			$this->getCacheLayer()->check(1, "countRelationshipObjects", null, $result, $options);
		
		return $result;
	}
	
	public function findObjectsColumnMax($table_name, $attribute_name, $options = false) {
    		debug_log_function("DBLayer->findObjectsColumnMax", array($table_name, $attribute_name, $options));
		
		$is_cache_active = $this->isCacheActive();
		
		if ($is_cache_active && empty($options["no_cache"]) && $this->getCacheLayer()->isValid(1, "findObjectsColumnMax", null, $options))
			return $this->getCacheLayer()->get(1, "findObjectsColumnMax", null, $options);
	
		$bn = isset($options["db_driver"]) ? $options["db_driver"] : null;
		$result = $this->getBroker($bn)->findObjectsColumnMax($table_name, $attribute_name, $options);
		
		if ($is_cache_active)
			$this->getCacheLayer()->check(1, "findObjectsColumnMax", null, $result, $options);
		
		return $result;
	}
}
?>
