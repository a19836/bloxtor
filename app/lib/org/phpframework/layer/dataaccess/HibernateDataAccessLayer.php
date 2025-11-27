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

include_once get_lib("org.phpframework.layer.dataaccess.DataAccessLayer");

class HibernateDataAccessLayer extends DataAccessLayer {
	
	/*
	$xxx->callObject("TEST", "ItemObjNotRegistered")); //modules.xml has an alias TEST => test
	$xxx->callObject("test", "ItemObjNotRegistered"));
	$xxx->callObject("test/item_subitem.xml", "ItemObjNotRegistered"));
	$xxx->callObject("test.item_subitem.xml", "ItemObjNotRegistered"));
	$xxx->callObject("test/item_subitem", "ItemObjNotRegistered")); //default_extension will be added. item is a xml file.
	$xxx->callObject("test.item_subitem", "ItemObjNotRegistered")); //default_extension will be added. item is a xml file.
	*/
	public function callObject($module_id, $service_id, $options = false) {
		debug_log_function("HibernateDataAccessLayer->callObject", array($module_id, $service_id, $options));
		
		$this->initModuleServices($module_id);
		
		if($this->getErrorHandler()->ok()) {
			$result = $this->callService($module_id, $service_id, $options);
			
			if($this->getErrorHandler()->ok()) {
				return $result;
			}
		}
		return false;
	}
	
	public function getObjectProps($module_id, $service_id, $options = false) {
		$props = array();
		
		$this->initModuleServices($module_id);
		
		if ($this->getErrorHandler()->ok()) {
			$module = isset($this->modules[$module_id]) ? $this->modules[$module_id] : null;
			$module_path = isset($this->modules_path[$module_id]) ? $this->modules_path[$module_id] : null;
			
			$props["module"] = $module;
			$props["module_path"] = $module_path;
			
			if (isset($module[$service_id])) {
				$service = $module[$service_id];
				$file_name = isset($service[0]) ? $service[0] : null;
				$file_service_id = isset($service[1]) ? $service[1] : null;
				$file_type = isset($service[2]) ? $service[2] : null;
				
				$obj_path = $module_path . ($file_type != "file" ? "/" . $file_name : "");
				$obj_name = $file_service_id;
				
				$props["service"] = $service;
				$props["obj_path"] = $obj_path;
				$props["obj_name"] = $obj_name;
			}
		}
		
		return $props;
	}
	
	private function callService($module_id, $service_id, $options) {
		$module = $this->modules[$module_id];
		$module_path = $this->modules_path[$module_id];
		
		if(isset($module[$service_id])) {
			$service = $module[$service_id];
			$file_name = isset($service[0]) ? $service[0] : null;
			$file_service_id = isset($service[1]) ? $service[1] : null;
			$file_type = isset($service[2]) ? $service[2] : null;
			
			$obj_path = $module_path . ($file_type != "file" ? "/" . $file_name : "");
			$obj_name = $file_service_id;
			
			if($obj_path && file_exists($obj_path)) {
				$SQLClient = $this->getSQLClient($options);
				
				if($this->isCacheActive()) {
					$this->getCacheLayer()->initModuleCache($module_id);
					$SQLClient->setCacheRootPath($this->getCacheLayer()->getCachedDirPath());
				}
				else {
					$SQLClient->setCacheRootPath(false);
				}
				
				$SQLClient->loadXML($obj_path);
				return $SQLClient->getHbnObj($obj_name, $module_id, $service_id, $options);
			}
			launch_exception(new DataAccessLayerException(1, $obj_path));
			return false;
		}
		launch_exception(new DataAccessLayerException(2, $module_id . "::" . $service_id));
		return false;
	}
	
	public function setCacheLayer($CacheLayer) {
		$this->getSQLClient()->setCacheLayer($CacheLayer);
		parent::setCacheLayer($CacheLayer);
	}
	
	protected function getRegexToGrepDataAccessFilesAndGetNodeIds() {
		return "/<(class)([^>]*)([ ]+)name=([\"]?)([\w\-\+&#;\s\.]+)([\"]?)/iu"; //'\w' means all words with '_' and '/u' means with accents and ç too. And &#; bc the query may contain accents in unicode which will be someting like &#222;
	}
}
?>
