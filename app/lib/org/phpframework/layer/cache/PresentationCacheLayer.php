<?php
include_once get_lib("org.phpframework.layer.cache.CacheLayer");

class PresentationCacheLayer extends CacheLayer {
	
	public function initBeanObjs($module_id) {
		if(!$this->bean_objs) {
			$this->bean_objs = $this->Layer->getPHPFrameWork()->getObjects();
			$this->bean_objs["vars"] = isset($this->bean_objs["vars"]) && is_array($this->bean_objs["vars"]) ? $this->bean_objs["vars"] : array();
			$this->bean_objs["vars"] = array_merge($this->bean_objs["vars"], $this->Layer->settings, $this->settings);
		}
		else
			$this->bean_objs["vars"] = isset($this->bean_objs["vars"]) && is_array($this->bean_objs["vars"]) ? $this->bean_objs["vars"] : array();
		
		$presentation_settings = $this->Layer->getPresentationSettings($module_id);
		$presentation_settings["current_presentation_id"] = isset($presentation_settings["presentation_id"]) ? $presentation_settings["presentation_id"] : null;
		$this->bean_objs["vars"] = array_merge($this->bean_objs["vars"], $presentation_settings);
	}
	
	public function getModulePath($module_id) {
		return $this->Layer->getModulePath($module_id);
	}
	
	public function initModuleCache($module_id) {
		if (isset($this->modules_cache[$module_id]))
			return true;
	
		$presentation_settings = $this->Layer->getPresentationSettings($module_id);
		
		if (empty($presentation_settings["presentation_path"]))
			launch_exception(new CacheLayerException(4, "\$presentation_settings[presentation_path]"));
		
		if (empty($this->settings["presentation_caches_path"]))
			launch_exception(new CacheLayerException(4, "PresentationCacheLayer->settings[presentation_caches_path]"));
		
		if (empty($this->settings["presentations_cache_file_name"]))
			launch_exception(new CacheLayerException(4, "PresentationCacheLayer->settings[presentations_cache_file_name]"));
		
		$cache_file_path = $presentation_settings["presentation_path"] . $this->settings["presentation_caches_path"] . $this->settings["presentations_cache_file_name"];
		
		if ($cache_file_path && file_exists($cache_file_path)) {
			$this->initBeanObjs($module_id);
		
			if ($this->Layer->getModuleCacheLayer()->cachedModuleSettingsExists($module_id)) {
				 $module_settings = $this->Layer->getModuleCacheLayer()->getCachedModuleSettings($module_id);
				 
				 $this->modules_cache[$module_id] = isset($module_settings["modules_cache"]) ? $module_settings["modules_cache"] : null;
				 $this->keys[$module_id] = isset($module_settings["keys"]) ? $module_settings["keys"] : null;
				 $this->service_related_keys_to_delete[$module_id] = isset($module_settings["service_related_keys_to_delete"]) ? $module_settings["service_related_keys_to_delete"] : null;
			}
			else {
				$this->modules_cache[$module_id] = $this->parseCacheFile($module_id, $cache_file_path);
				
				$this->prepareModulesCache($module_id);
				
				$services = isset($this->modules_cache[$module_id]["services"]) ? $this->modules_cache[$module_id]["services"] : null;
				$service_keys = array_keys($services);
				$t = count($service_keys);
				
				for ($i = 0; $i < $t; $i++) {
					$key = $service_keys[$i];
					if (!empty($services[$key]["presentation_id"]))
						$this->modules_cache[$module_id]["services"][$key]["module_id"] = $services[$key]["presentation_id"];
				
					if (!empty($services[$key]["to_delete"])) {
						$t2 = count($services[$key]["to_delete"]);
						
						for ($j = 0; $j < $t2; $j++) {
							if (empty($services[$key]["to_delete"][$j]["module_id"]))
								$this->modules_cache[$module_id]["services"][$key]["to_delete"][$j]["module_id"] = isset($services[$key]["to_delete"][$j]["presentation_id"]) ? $services[$key]["to_delete"][$j]["presentation_id"] : null;
						}
					}
				}
				
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
		if (empty($this->settings["presentations_cache_path"]))
			launch_exception(new CacheLayerException(4, "PresentationCacheLayer->settings[presentations_cache_path]"));
		
		return $this->settings["presentations_cache_path"];
	}
}
?>
