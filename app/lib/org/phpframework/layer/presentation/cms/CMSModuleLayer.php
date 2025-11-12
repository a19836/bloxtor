<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.xmlfile.XMLFileParser");
include_once get_lib("org.phpframework.layer.presentation.cms.CMSLayer");
include_once get_lib("org.phpframework.layer.presentation.cms.exception.CMSModuleLayerException");
include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSModuleHandler");
include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSModuleSimulatorHandler");
include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSModuleEnableHandler");
include_once get_lib("org.phpframework.layer.presentation.cms.cache.CMSModuleSettingsCacheHandler");
include_once get_lib("org.phpframework.layer.presentation.cms.CMSFileHandler");

class CMSModuleLayer {
	private $CMSLayer;
	private $CMSModuleSettingsCacheHandler;
	
	private $modules;
	private $modules_folder_path;
	private $modules_webroot_folder_path;
	
	private $is_modules_folder_path_inited = false;
	private $is_modules_webroot_folder_path_inited = false;
	
	public function __construct(CMSLayer $CMSLayer) {
		$this->CMSLayer = $CMSLayer;
		$this->CMSModuleSettingsCacheHandler = new CMSModuleSettingsCacheHandler();
		
		$this->modules = array();
		$this->modules_folder_path = "";
		$this->modules_webroot_folder_path = "";
		
		$this->init();
	}
	
	private function init() {
		if ($this->CMSLayer->isCacheActive()) {
			$CacheLayer = $this->CMSLayer->getCacheLayer()->getCMSModuleCacheLayer();
			$cache_root_path = $CacheLayer->getCachedDirPath();
			
			if ($cache_root_path)
				$this->CMSModuleSettingsCacheHandler->initCacheDirPath($cache_root_path);
		}
		
		/* 2018-02-20
		BEFORE THIS CODE WAS UNCOMMENTED, but this gave errors on installing the phpframework:
			$EVC = $this->CMSLayer->getEVC();
			$this->modules_folder_path = $EVC->getModulesPath($EVC->getCommonProjectName());
			$this->modules_webroot_folder_path = $EVC->getWebrootPath($EVC->getCommonProjectName()) . "module/";
		Please do not inited the $this->modules_folder_path and $this->modules_webroot_folder_path here, otherwise the app/setup.php will give an error when installing the phpframework.
		Now we inited this vars in the getModulesFolderPath and getModulesWebrootFolderPath methods.
		*/
	}
	
	public function setModulesFolderPath($modules_folder_path) { 
		$this->is_modules_folder_path_inited = true;
		
		$this->modules_folder_path = $modules_folder_path . (substr($modules_folder_path, -1) == "/" ? "" : "/");
	}
	
	public function getModulesFolderPath() { 
		if (!$this->is_modules_folder_path_inited)
			$this->setModulesFolderPath( $this->CMSLayer->getEVC()->getModulesPath( $this->CMSLayer->getEVC()->getCommonProjectName() ) );
		
		return $this->modules_folder_path; 
	}
	
	public function setModulesWebrootFolderPath($modules_webroot_folder_path) { 
		$this->is_modules_webroot_folder_path_inited = true;
		
		$this->modules_webroot_folder_path = $modules_webroot_folder_path . (substr($modules_webroot_folder_path, -1) == "/" ? "" : "/");
	}
	
	public function getModulesWebrootFolderPath() { 
		if (!$this->is_modules_webroot_folder_path_inited)
			$this->setModulesWebrootFolderPath( $this->CMSLayer->getEVC()->getWebrootPath( $this->CMSLayer->getEVC()->getCommonProjectName() ) . "module/" );
			
		return $this->modules_webroot_folder_path; 
	}
	
	public function executeModule($module_id, &$settings = false, $cms_settings = false) {
		$has_cache = false;
		$is_cache_active = $this->CMSLayer->isCacheActive();
		$result = null;
		
		if($is_cache_active) {
			$CacheLayer = $this->CMSLayer->getCacheLayer()->getCMSModuleCacheLayer();
			
			if($CacheLayer->isValid($module_id, $settings)) {
				$result = $CacheLayer->get($module_id, $settings);
				$has_cache = $result ? true : false;
			}
		}
		
		if(!$has_cache) {
			$CMSModuleHandler = $this->getModuleObj($module_id);
			$result = null;
			
			if ($CMSModuleHandler) {
				$CMSModuleHandler->setCMSSettings($cms_settings);
				$result = $CMSModuleHandler->execute($settings);
			}
			
			if($is_cache_active)
				$CacheLayer->check($module_id, $settings, $result);
		}
		
		return $result;
	}
	
	public function existsModule($module_id, $only_if_enabled = true) {
		try {
			$CMSModuleHandler = $this->getModuleObj($module_id, $only_if_enabled);
			
			if ($CMSModuleHandler)
				return true;
		}
		catch (Exception $e) {}
		
		return false;
	}
	
	public function getModuleObj($module_id, $only_if_enabled = true) {
		$module_id = substr($module_id, -1) == "/" ? substr($module_id, 0, -1) : $module_id;
		$folder_path = $this->getModulesFolderPath() . $module_id . "/";
		$file_path = CMSModuleHandler::getCMSModuleHandlerImplFilePath($folder_path);
		$class = "CMSModule\\" . str_replace("/", "\\", str_replace(" ", "_", trim($module_id))) . "\\CMSModuleHandlerImpl";
		
		try {
			if (!class_exists($class)) {
				if (file_exists($file_path)) 
					include_once $file_path;
				else 
					launch_exception(new CMSModuleLayerException(5, array($module_id, $file_path) ));
			}
			
			eval('$CMSModuleHandler = new ' . $class . '();');
			
			if ($CMSModuleHandler) {
				if (is_a($CMSModuleHandler, "CMSModuleHandler")) {
					$enable_file_path = CMSModuleEnableHandler::getModuleEnabledFilePath($folder_path);
					
					if (@include($enable_file_path)) {
						if ($CMSModuleHandler->isEnabled()) {
							$CMSModuleHandler->setEVC( $this->CMSLayer->getEVC() );
							$CMSModuleHandler->setModuleId($module_id); //set the correct module id already correctly parsed
							return $CMSModuleHandler;
						}
					}
					
					launch_exception(new CMSModuleLayerException(5, array($module_id, $file_path)));
				}
				else
					launch_exception(new CMSModuleLayerException(2, $file_path));
			}
			else
				launch_exception(new CMSModuleLayerException(3, $file_path));
		}
		catch (Exception $e) {
			launch_exception($e);
		}
		
		return null;
	}
	
	public function getModuleSimulatorObj($module_id, $only_if_enabled = true) {
		$module_id = substr($module_id, -1) == "/" ? substr($module_id, 0, -1) : $module_id;
		$folder_path = $this->getModulesFolderPath() . $module_id . "/";
		$file_path = CMSModuleSimulatorHandler::getCMSModuleSimulatorHandlerImplFilePath($folder_path);
		$class = "CMSModule\\" . str_replace("/", "\\", str_replace(" ", "_", trim($module_id))) . "\\CMSModuleSimulatorHandlerImpl";
		//echo "file_path:$file_path";die();
		
		try {
			if (!class_exists($class) && file_exists($file_path)) 
				include_once $file_path;
			
			if (class_exists($class)) {
				eval('$CMSModuleSimulatorHandler = new ' . $class . '();');
				
				if ($CMSModuleSimulatorHandler) {
					if (is_a($CMSModuleSimulatorHandler, "CMSModuleSimulatorHandler"))
						return $CMSModuleSimulatorHandler;
					else
						launch_exception(new CMSModuleLayerException(7, $file_path));
				}
				else
					launch_exception(new CMSModuleLayerException(8, $file_path));
			}
		}
		catch (Exception $e) {
			launch_exception($e);
		}
		
		return null;

	}
	
	public function loadModules($modules_webroot_url) {
		$modules_folder_path = $this->getModulesFolderPath();
		
		if ($modules_folder_path && is_dir($modules_folder_path)) {
			$modules_folder_path .= substr($modules_folder_path, strlen($modules_folder_path) - 1, 1) == "/" ? "" : "/";
			
			$modules_webroot_folder_path = $this->getModulesWebrootFolderPath();
			$modules_webroot_folder_path .= substr($modules_webroot_folder_path, strlen($modules_webroot_folder_path) - 1, 1) == "/" ? "" : "/";
			
			$modules_folder_path_id = $this->CMSModuleSettingsCacheHandler->getCachedId( array($modules_webroot_url, $modules_folder_path) );
			
			if ($this->CMSModuleSettingsCacheHandler->isActive() && $this->CMSModuleSettingsCacheHandler->cachedLoadedModulesExists($modules_folder_path_id)) 
				$modules = $this->CMSModuleSettingsCacheHandler->getCachedLoadedModules($modules_folder_path_id);
			
			if (empty($modules)) {
				$modules = $this->getModulesFromPath($modules_webroot_url, $modules_folder_path, $modules_folder_path, $modules_webroot_folder_path);
				
				if ($this->CMSModuleSettingsCacheHandler->isActive()) 
					$this->CMSModuleSettingsCacheHandler->setCachedLoadedModules($modules_folder_path_id, $modules);
			}
			
			//echo "<pre>";print_r($modules);die();
			$this->modules = $modules;
			return true;
		}
		
		launch_exception(new CMSModuleLayerException(1, $modules_folder_path));
		return false;
	}
	
	private function getModulesFromPath($modules_webroot_url, $folder_path, $modules_path, $modules_webroot_folder_path) {
		$modules = array();
		
		if (($dir = opendir($folder_path)) ) {
			while( ($file = readdir($dir)) !== false) {
				if (substr($file, 0, 1) != ".") {
					$fp = $folder_path . $file;
					
					if (is_dir($fp)) {
						$module_handler_file_path = CMSModuleHandler::getCMSModuleHandlerImplFilePath($fp);
						$module_xml_file_path = $fp . "/module.xml";
					
						if (file_exists($module_handler_file_path) || file_exists($module_xml_file_path)) {
							$module_id = str_replace($modules_path, "", $fp);
							
							if (file_exists($module_xml_file_path)) {
								$arr = XMLFileParser::parseXMLFileToArray($module_xml_file_path);
								$arr = MyXML::complexArrayToBasicArray($arr, array("lower_case_keys" => true, "trim" => true));
								$arr = isset($arr["module"]) ? $arr["module"] : null;
							}
							else 
								$arr = array();
							
							$label = isset($arr["label"]) ? (is_array($arr["label"]) ? $arr["label"][0] : $arr["label"]) : null;
							$label = $label ? $label : $module_id;
							$description = isset($arr["description"]) ? (is_array($arr["description"]) ? $arr["description"][0] : $arr["description"]) : null;
							$load_module_settings_func = isset($arr["load_module_settings_js_function"]) ? (is_array($arr["load_module_settings_js_function"]) ? $arr["load_module_settings_js_function"][0] : $arr["load_module_settings_js_function"]) : null;
							$is_reserved_module = isset($arr["is_reserved_module"]) && ($arr["is_reserved_module"] == "true" || $arr["is_reserved_module"] == "1");
							$is_hidden_module = isset($arr["is_hidden_module"]) && ($arr["is_hidden_module"] == "true" || $arr["is_hidden_module"] == "1");
							
							$pos = strpos($module_id, "/");
							$group_module_id = $pos > 0 ? substr($module_id, 0, $pos) : $module_id;
							$admin_path = $modules_path . $group_module_id . "/admin/";
							$module_handler_html_file_path = $fp . "/CMSModuleSettingsHtml.php";
							
							//Get Join Points from CMSModuleHandlerImpl.php
							$join_points = CMSFileHandler::getFileIncludeJoinPoints($module_handler_file_path);
							
							$module = array(
								"id" => $module_id,
								"is_reserved_module" => $is_reserved_module,
								"is_hidden_module" => $is_hidden_module,
								"path" => $fp . "/",
								"module_handler_impl_file_path" => file_exists($module_handler_file_path) ? $module_handler_file_path : null,
								"module_handler_html_file_path" => file_exists($module_handler_html_file_path) ? $module_handler_html_file_path : null,
								"webroot_path" => $modules_webroot_folder_path . $module_id . "/",
								"webroot_url" => $modules_webroot_url . $module_id . "/",
								"label" => $label,
								"description" => $description,
								"images" => array(),
								"load_module_settings_js_function" => $load_module_settings_func,
								"group_id" => $group_module_id,
								"admin_path" => file_exists($admin_path . "index.php") ? $admin_path : null,
								"join_points" => $join_points,
							);
						
							$images = isset($arr["image"]) ? (!$arr["image"] || is_array($arr["image"]) ? $arr["image"] : array($arr["image"])) : null;
							$t = $images ? count($images) : 0;
							for ($i = 0; $i < $t; $i++) {
								$img_path = $images[$i];
							
								if (file_exists($module["webroot_path"] . $img_path)) {
									$module["images"][] = array(
										"path" => $module["webroot_path"] . $img_path,
										"url" => $module["webroot_url"] . $img_path,
									);
								}
							}
							
							$modules[ $module_id ] = $module;
						}
						else {
							$sub_modules = $this->getModulesFromPath($modules_webroot_url, $fp . "/", $modules_path, $modules_webroot_folder_path);
							$modules =  array_merge($modules, $sub_modules);
						}
					}
				}
			}
			
			closedir($dir);
		}
		
		return $modules;
	}
	
	public function getModuleHtml($module, $external_vars = null) {
		if ($module && !empty($module["module_handler_html_file_path"]) && file_exists($module["module_handler_html_file_path"])) {
			$content = file_get_contents($module["module_handler_html_file_path"]);
			$ext = pathinfo($module["module_handler_html_file_path"], PATHINFO_EXTENSION);
		
			if (strtolower($ext) == "php") {
				$vars = array("module" => $module, "EVC" => $this->CMSLayer->getEVC());
				$external_vars = $external_vars ? array_merge($external_vars, $vars) : $vars;
				$return_values = array();
				$content = PHPScriptHandler::parseContent($content, $external_vars, $return_values);
			}
		
			return $content;
		}
	}
	
	public function getLoadedModules() { return $this->modules; }
	
	public function getLoadedModule($module_id) { 
		return isset($this->modules[$module_id]) ? $this->modules[$module_id] : null;
	}
	
	public function freeModuleCache() {
		if ($this->CMSModuleSettingsCacheHandler->isActive()) {
			$cache_root_path = $this->CMSModuleSettingsCacheHandler->getCacheRootPath();
			
			if ($cache_root_path) 
				return CacheHandlerUtil::deleteFolder($cache_root_path, false);
		}
	}
}
?>
