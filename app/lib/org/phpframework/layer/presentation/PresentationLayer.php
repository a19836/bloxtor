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

class PresentationLayer extends Layer {
	private $selected_presentation_id;
	private $selected_presentation_settings;
	
	public function __construct($settings = array()) {
		parent::__construct($settings);
	}
	
	/********* SELECTED PRESENTATION SETTINGS ********/
	public function setSelectedPresentationId($selected_presentation_id) { 
		$this->selected_presentation_id = $selected_presentation_id; 
		$this->selected_presentation_settings = $this->getPresentationSettings($selected_presentation_id);
	}
	public function getSelectedPresentationId() { return $this->selected_presentation_id; }
	
	public function getSelectedPresentationSettings() { return $this->selected_presentation_settings; }
	public function getSelectedPresentationSetting($setting_name) { return isset($this->selected_presentation_settings[$setting_name]) ? $this->selected_presentation_settings[$setting_name] : null; }
	
	
	/********* MODULES ********/
	public function getModulePath($module_id) {
		return parent::getModulePathGeneric($module_id, $this->getModulesFilePathSetting(), $this->getLayerPathSetting());
	}
	
	/********* PRESENTATION SETTINGS ********/
	public function getLayerPathSetting() {
		if (empty($this->settings["presentations_path"]))
			launch_exception(new LayerException(2, "PresentationLayer->settings[presentations_path]"));
		
		return $this->settings["presentations_path"];
	}
	
	public function getModulesFilePathSetting() {
		if (empty($this->settings["presentations_modules_file_path"]))
			launch_exception(new LayerException(2, "PresentationLayer->settings[presentations_modules_file_path]"));
		
		return $this->settings["presentations_modules_file_path"];
	}
	
	public function getCommonProjectName() {
		if (empty($this->settings["presentation_common_project_name"]))
			launch_exception(new LayerException(2, "PresentationLayer->settings[presentation_common_project_name]"));
		
		return $this->settings["presentation_common_project_name"];
	}
	
	public function getPresentationPath($module_id) {
		return $this->getModulePath($module_id);
	}
	
	public function getPresentationSettings($module_id) {
		if (empty($this->settings["presentation_configs_path"]))
			launch_exception(new LayerException(2, "PresentationLayer->settings[presentation_configs_path]"));
		else if (empty($this->settings["presentation_utils_path"]))
			launch_exception(new LayerException(2, "PresentationLayer->settings[presentation_utils_path]"));
		else if (empty($this->settings["presentation_controllers_path"]))
			launch_exception(new LayerException(2, "PresentationLayer->settings[presentation_controllers_path]"));
		else if (empty($this->settings["presentation_entities_path"]))
			launch_exception(new LayerException(2, "PresentationLayer->settings[presentation_entities_path]"));
		else if (empty($this->settings["presentation_views_path"]))
			launch_exception(new LayerException(2, "PresentationLayer->settings[presentation_views_path]"));
		else if (empty($this->settings["presentation_templates_path"]))
			launch_exception(new LayerException(2, "PresentationLayer->settings[presentation_templates_path]"));
		else if (empty($this->settings["presentation_blocks_path"]))
			launch_exception(new LayerException(2, "PresentationLayer->settings[presentation_blocks_path]"));
		else if (empty($this->settings["presentation_modules_path"]))
			launch_exception(new LayerException(2, "PresentationLayer->settings[presentation_modules_path]"));
		else if (empty($this->settings["presentation_webroot_path"]))
			launch_exception(new LayerException(2, "PresentationLayer->settings[presentation_webroot_path]"));
		
		$presentation_path = $this->getPresentationPath($module_id);
		
		$presentation_settings = array();
		$presentation_settings["presentation_id"] = $module_id;
		$presentation_settings["presentation_path"] = $presentation_path;
		$presentation_settings["presentation_relative_path"] = strstr($presentation_path, $this->getLayerPathSetting());
		$presentation_settings["presentation_dir_name"] = basename($presentation_settings["presentation_relative_path"]);
		
		$presentation_settings["presentation_configs_path"] = $presentation_path . $this->settings["presentation_configs_path"];
		$presentation_settings["presentation_utils_path"] = $presentation_path . $this->settings["presentation_utils_path"];
		$presentation_settings["presentation_controllers_path"] = $presentation_path . $this->settings["presentation_controllers_path"];
		$presentation_settings["presentation_entities_path"] = $presentation_path . $this->settings["presentation_entities_path"];
		$presentation_settings["presentation_views_path"] = $presentation_path . $this->settings["presentation_views_path"];
		$presentation_settings["presentation_templates_path"] = $presentation_path . $this->settings["presentation_templates_path"];
		$presentation_settings["presentation_blocks_path"] = $presentation_path . $this->settings["presentation_blocks_path"];
		$presentation_settings["presentation_modules_path"] = $presentation_path . $this->settings["presentation_modules_path"];
		$presentation_settings["presentation_webroot_path"] = $presentation_path . $this->settings["presentation_webroot_path"];
		
		$presentation_settings["presentation_files_extension"] = isset($this->settings["presentation_files_extension"]) ? $this->settings["presentation_files_extension"] : null;
		
		return $presentation_settings;
	}
	
	public function getPresentationFileExtension() {
		$extension = $this->getSelectedPresentationSetting("presentation_files_extension");
		
		return isset($extension) && $extension ? $extension : "php";
	}
	
	/********* PAGE ********/
	public function getPagePath($file_code) {
		return $this->getSelectedPresentationSetting("presentation_path") . $file_code . "." . $this->getPresentationFileExtension();
	}
	
	public function callPage($EVC_OR_PRESENTATION_OBJ, $url, $page_code, $page_path, $parameters = array(), $external_vars = array(), $includes = array(), $includes_once = array()) {
		debug_log_function("PresentatioLayer->callPage", array($page_code, $page_path, $parameters));
		
		//This is to check if callPage is working.
		if ($url == "_is_presentation_callpage") {
			echo 1; 
			die();
		}
		
		$has_cache = false;
		$html = null;
		
		$is_cache_active = $this->isCacheActive();
		if ($is_cache_active) {
			$CacheLayer = $this->getCacheLayer();
			
			//Set headers if exists, independent if service cache exists or not
			$headers = $CacheLayer->getHeaders($this->selected_presentation_id, $url);
		
			if ($headers && !headers_sent()) {
				$parts = explode("\n", $headers);
				foreach ($parts as $part)
					header( trim($part) );
			}
			
			if ($CacheLayer->isValid($this->selected_presentation_id, $url, $parameters)) {
				$html = $CacheLayer->get($this->selected_presentation_id, $url, $parameters);
				$has_cache = $html ? true : false;
			}
		}
		
		if (!$has_cache) {
			$PVTOCACHE = array($page_code, $parameters);
			
			eval("\$".$this->getPHPFrameWorkObjName()." = \$this->getPHPFrameWork();");
			$EVC = $EVC_OR_PRESENTATION_OBJ;
			$GLOBALS["EVC"] = $EVC;
			
			if (is_array($external_vars))
				foreach($external_vars as $var_name => $var_value)
					${$var_name} = $var_value;
			
			ob_start(null, 0);
			
			if ($includes) {
				$t = count($includes);
				for($i = 0; $i < $t; $i++)
					include $includes[$i];
			}
			
			if ($includes_once) {
				$t = count($includes_once);
				for($i = 0; $i < $t; $i++)
					include $includes_once[$i];
			}
			
			include $page_path;
			$html = ob_get_contents();
			ob_end_clean();
			
			if ($is_cache_active)
				$CacheLayer->check($this->selected_presentation_id, $url, $PVTOCACHE[1], $html);
		}
		
		return $html;
	}
}
?>
