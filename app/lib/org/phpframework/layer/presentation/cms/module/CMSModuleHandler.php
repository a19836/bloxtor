<?php
include_once get_lib("org.phpframework.layer.presentation.cms.module.ICMSModuleHandler");

abstract class CMSModuleHandler implements ICMSModuleHandler {
	private $EVC;
	private $module_id;
	private $cms_settings;
	private $enabled = false;
	
	public function setEVC($EVC) { $this->EVC = $EVC; }
	public function getEVC() { return $this->EVC; }
	
	public function setModuleId($module_id) { $this->module_id = $module_id; }
	public function getModuleId() { return $this->module_id; }
	
	public function setCMSSettings($cms_settings) { $this->cms_settings = $cms_settings; }
	public function getCMSSettings() { return $this->cms_settings; }
	public function getCMSSetting($name) { return is_array($this->cms_settings) && isset($this->cms_settings[$name]) ? $this->cms_settings[$name] : null; }
	
	public function enable() { $this->enabled = true; }
	public function disable() { $this->enabled = false; }
	public function isEnabled() { return $this->enabled; }
	
	public static function getCMSModuleHandlerImplFilePath($presentation_module_path) {
		return "$presentation_module_path/CMSModuleHandlerImpl.php";
	}
}
?>
