<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.layer.cache.CacheLayer");

class BusinessLogicCacheLayer extends CacheLayer {
	
	public function initBeanObjs($module_id) {
		$this->Layer->initBeanObjs($module_id);
		$this->bean_objs = $this->Layer->getBeanObjs();
		$this->bean_objs["vars"] = isset($this->bean_objs["vars"]) && is_array($this->bean_objs["vars"]) ? $this->bean_objs["vars"] : array();
		$this->bean_objs["vars"] = array_merge($this->bean_objs["vars"], $this->settings);
	}
	
	public function getModulePath($module_id) {
		return $this->Layer->getModulePath($module_id);
	}
	
	public function initModuleCache($module_id) {
		if(isset($this->modules_cache[$module_id]))
			return true;
		
		$this->Layer->initModuleServices($module_id);
		if($this->Layer->getErrorHandler()->ok()) {
			$module_path = $this->getModulePath($module_id);
			
			if (empty($this->settings["business_logic_cache_file_name"]))
				launch_exception(new CacheLayerException(4, "BusinessLogicCacheLayer->settings[business_logic_cache_file_name]"));
			
			$cache_file_path = $module_path . $this->settings["business_logic_cache_file_name"];
			
			if($cache_file_path && file_exists($cache_file_path)) {
				$this->initBeanObjs($module_id);
				
				if($this->Layer->getModuleCacheLayer()->cachedModuleSettingsExists($module_id)) {
					 $module_settings = $this->Layer->getModuleCacheLayer()->getCachedModuleSettings($module_id);
					 
					 $this->modules_cache[$module_id] = isset($module_settings["modules_cache"]) ? $module_settings["modules_cache"] : null;
					 $this->keys[$module_id] = isset($module_settings["keys"]) ? $module_settings["keys"] : null;
					 $this->service_related_keys_to_delete[$module_id] = isset($module_settings["service_related_keys_to_delete"]) ? $module_settings["service_related_keys_to_delete"] : null;
				}
				else {
					$this->modules_cache[$module_id] = $this->parseCacheFile($module_id, $cache_file_path);
					
					$this->prepareModulesCache($module_id);
					
					$module_settings = array();
					$module_settings["modules_cache"] = isset($this->modules_cache[$module_id]) ? $this->modules_cache[$module_id] : null;
					$module_settings["keys"] = isset($this->keys[$module_id]) ? $this->keys[$module_id] : null;
					$module_settings["service_related_keys_to_delete"] = isset($this->service_related_keys_to_delete[$module_id]) ? $this->service_related_keys_to_delete[$module_id] : null;
					
					$this->Layer->getModuleCacheLayer()->setCachedModuleSettings($module_id, $module_settings);
				}
				
				return true;
			}
		}
		
		return false;
	}
	
	public function getModuleCacheObj($module_id, $service_id, $data) {
		$module = isset($this->modules_cache[$module_id]) ? $this->modules_cache[$module_id] : null;
		$services = isset($module["services"]) ? $module["services"] : null;
		
		if (isset($services[$service_id])) {
			$service = isset($services[$service_id]) ? $services[$service_id] : null;
			$constructor = isset($service["cache_handler"]) ? $service["cache_handler"] : null;
			
			if ($constructor) {
				$obj = false;
				
				if (!empty($module["objects"][$constructor]))
					$obj = $module["objects"][$constructor];
				else {
					if (!empty($this->modules_cache[$module_id]["bean_factory"]))
						$BeanFactory = $this->modules_cache[$module_id]["bean_factory"];
					else {
						$this->initBeanObjs($module_id);
						
						$BeanFactory = new BeanFactory();
						$BeanFactory->addObjects($this->bean_objs);
						$BeanFactory->init(array(
							"settings" => isset($module["beans"]) ? $module["beans"] : null
						));
						
						$this->modules_cache[$module_id]["bean_factory"] = $BeanFactory;
					}
					
					$BeanFactory->setCacheRootPath($this->getCachedDirPath());
					$obj = $BeanFactory->getObject($constructor);
					
					if (!$obj) {
						$BeanFactory->initObject($constructor, false);
						$this->modules_cache[$module_id]["bean_factory"] = $BeanFactory;
						$obj = $BeanFactory->getObject($constructor);
						
						if (!$obj)
							$obj = $this->Layer->getModuleConstructorObj($module_id, $constructor, null, $data); //cache_handler does not have any namespace bc it doesn't make sense bc the cache_handler obj is only defined in the xml beans (cache.xml and cache_handler.xml)
					}
					
					$this->modules_cache[$module_id]["objects"][$constructor] = $obj;
				}
				
				if($obj)
					return $obj;
				else 
					launch_exception(new CacheLayerException(2, $module_id . "::" . $service_id . "::" . $constructor));
			}
			else
				launch_exception(new CacheLayerException(1, $module_id . "::" . $service_id));
		}
		
		return false;
	}
	
	public function getCachedDirPath() {
		if (empty($this->settings["business_logic_cache_path"]))
			launch_exception(new CacheLayerException(4, "BusinessLogicCacheLayer->settings[business_logic_cache_path]"));
		
		return $this->settings["business_logic_cache_path"];
	}
}
?>
