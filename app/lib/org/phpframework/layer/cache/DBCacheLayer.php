<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.layer.cache.CacheLayer");

class DBCacheLayer extends CacheLayer {

	public function initBeanObjs() {
		if(!$this->bean_objs) {
			$this->bean_objs = $this->Layer->getPHPFrameWork()->getObjects();
			$this->bean_objs["vars"] = isset($this->bean_objs["vars"]) && is_array($this->bean_objs["vars"]) ? $this->bean_objs["vars"] : array();
			$this->bean_objs["vars"] = array_merge($this->bean_objs["vars"], $this->Layer->settings, $this->settings);
		}
	}
	
	public function getModulePath($module_id) {
		return $this->Layer->getLayerPathSetting();
	}
	
	public function initModuleCache($module_id) {
//echo "<br>\n".get_class($this)."::initModuleCache($module_id)";
		if (isset($this->modules_cache[$module_id]))
			return true;
//echo "<br>\nLOADING: ".get_class($this)."::initModuleCache($module_id)";
				
		$module_path = $this->getModulePath($module_id);
		
		if (empty($this->settings["dbl_cache_file_name"]))
			launch_exception(new CacheLayerException(4, "DBCacheLayer->settings[dbl_cache_file_name]"));
		
		$cache_file_path = $module_path . $this->settings["dbl_cache_file_name"];
		
		if ($cache_file_path && file_exists($cache_file_path)) {
			$this->initBeanObjs();
			
			if ($this->Layer->getModuleCacheLayer()->cachedModuleSettingsExists($module_id)) {
				 $module_settings = $this->Layer->getModuleCacheLayer()->getCachedModuleSettings($module_id);
				 
				 $this->modules_cache[$module_id] = isset($module_settings["modules_cache"]) ? $module_settings["modules_cache"] : null;
				 $this->keys[$module_id] = isset($module_settings["keys"]) ? $module_settings["keys"] : null;
				 $this->service_related_keys_to_delete[$module_id] = isset($module_settings["service_related_keys_to_delete"]) ? $module_settings["service_related_keys_to_delete"] : null;
			}
			else {
				$this->modules_cache[$module_id] = $this->parseCacheFile($module_id, $cache_file_path);
				
				$this->prepareModulesCache($module_id);
				
				$module_settings = array();
				$module_settings["modules_cache"] = $this->modules_cache[$module_id];
				$module_settings["keys"] = isset($this->keys[$module_id]) ? $this->keys[$module_id] : null;
				$module_settings["service_related_keys_to_delete"] = isset($this->service_related_keys_to_delete[$module_id]) ? $this->service_related_keys_to_delete[$module_id] : null;
				
				$this->Layer->getModuleCacheLayer()->setCachedModuleSettings($module_id, $module_settings);
			}
			return true;
		}
		return false;
	}
	
	public function getModuleCacheObj($module_id, $service_id, $data) {
		$module = isset($this->modules_cache[$module_id]) ? $this->modules_cache[$module_id] : null;
		$services = isset($module["services"]) ? $module["services"] : null;
		
		if (isset($services[$service_id])) {
			$service = $services[$service_id];
			$constructor = isset($service["cache_handler"]) ? $service["cache_handler"] : null;
			
			if ($constructor) {
				$obj = false;
				
				if (!empty($module["objects"][$constructor]))
					$obj = $module["objects"][$constructor];
				else {
					if (!empty($this->modules_cache[$module_id]["bean_factory"]))
						$BeanFactory = $this->modules_cache[$module_id]["bean_factory"];
					else {
						$this->initBeanObjs();
					
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
		if (empty($this->settings["dbl_cache_path"]))
			launch_exception(new CacheLayerException(4, "DBCacheLayer->settings[dbl_cache_path]"));
		
		return $this->settings["dbl_cache_path"];
	}
}
?>
