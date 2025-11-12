<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.layer.presentation.cms.module.ICMSModuleEnableHandler");
include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSModuleUtil");
include_once get_lib("org.phpframework.layer.presentation.cms.cache.CMSModuleSettingsCacheHandler");

class CMSModuleEnableHandler implements ICMSModuleEnableHandler {
	protected $PresentationLayer;
	protected $module_id;
	
	protected $presentation_module_path;
	
	public function __construct($PresentationLayer, $module_id) {
		$this->PresentationLayer = $PresentationLayer;
		$this->module_id = $module_id;
		
		$layer_path = $PresentationLayer->getLayerPathSetting();
		
		if (empty($PresentationLayer->settings["presentation_modules_path"]))
			launch_exception(new Exception("\$PresentationLayer->settings[presentation_modules_path] cannot be empty!"));
		
		$this->presentation_module_path = $layer_path . $PresentationLayer->getCommonProjectName() . "/" . $PresentationLayer->settings["presentation_modules_path"] . $module_id;
	}
	
	public static function createCMSModuleEnableHandlerObject($PresentationLayer, $module_id, $system_presentation_settings_module_path) {
		$CMSModuleEnableHandler = null;
		
		try {
			if (file_exists($system_presentation_settings_module_path . "/CMSModuleEnableHandlerImpl.php")) {
				$class = 'CMSModule\\' . str_replace("/", "\\", str_replace(" ", "_", trim($module_id))) . '\CMSModuleEnableHandlerImpl';
		
				if (!class_exists($class))
					include_once $system_presentation_settings_module_path . "/CMSModuleEnableHandlerImpl.php";
				
				eval ('$CMSModuleEnableHandler = new ' . $class . '($PresentationLayer, $module_id);');
			}
			else
				$CMSModuleEnableHandler = new CMSModuleEnableHandler($PresentationLayer, $module_id);
		}
		catch (Exception $e) {
			launch_exception($e);
		}
		
		return $CMSModuleEnableHandler;
	}
	
	public function enable() {
		if (!is_dir($this->presentation_module_path)) 
			launch_exception(new Exception("Module path doesn't exist: " . $this->presentation_module_path));
		
		return file_put_contents( self::getModuleEnabledFilePath($this->presentation_module_path), '<?php $CMSModuleHandler->enable(); ?>') !== false;
	}
	
	public function disable() {
		$enabled_file_path = self::getModuleEnabledFilePath($this->presentation_module_path);
		
		return !file_exists($enabled_file_path) || file_put_contents($enabled_file_path, '<?php $CMSModuleHandler->disable(); ?>') !== false;
	}
	
	public function freeModuleCache() {
		if ($this->PresentationLayer->isCacheActive()) {
			$cache_root_path = $this->PresentationLayer->getModuleCachedLayerDirPath();
		
			if ($cache_root_path) {
				$cache_root_path = $cache_root_path . CMSModuleSettingsCacheHandler::CACHE_DIR_NAME;
				
				return CMSModuleUtil::deleteFolder($cache_root_path);
			}
		}
	}
	
	public static function getModuleEnabledFilePath($presentation_module_path) {
		return "$presentation_module_path/enable.php";
	}
	
	public static function isModuleEnabled($presentation_module_path) {
		$enabled_file_path = self::getModuleEnabledFilePath($presentation_module_path);
		
		return file_exists($enabled_file_path) && strpos(file_get_contents($enabled_file_path), '$CMSModuleHandler->enable();') !== false;
	}
}
?>
