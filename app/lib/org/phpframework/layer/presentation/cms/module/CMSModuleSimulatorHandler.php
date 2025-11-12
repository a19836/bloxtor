<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.layer.presentation.cms.module.ICMSModuleSimulatorHandler");

abstract class CMSModuleSimulatorHandler implements ICMSModuleSimulatorHandler {
	private $CMSModuleHandler;
	
	public function setCMSModuleHandler($CMSModuleHandler) { $this->CMSModuleHandler = $CMSModuleHandler; }
	public function getCMSModuleHandler() { return $this->CMSModuleHandler; }
	
	public static function getCMSModuleSimulatorHandlerImplFilePath($presentation_module_path) {
		return "$presentation_module_path/CMSModuleSimulatorHandlerImpl.php";
	}
	
	//This function reutrns an empty html by default, but it can be overwriten by the each module...
	public function simulate(&$settings = false, &$editable_settings = false) {
		return "";
	}
	
	public function simulateEditFormFields(&$settings = false, &$editable_settings = false) {
		$s = $settings;
		$editable_settings = array(
			"elements" => array()
		);
		
		if ($s && !empty($s["fields"]) && is_array($s["fields"]))
			foreach ($s["fields"] as $k => $aux)
				$editable_settings["elements"][".module_edit .form_fields > .form_field.$k > label"] = "fields/$k/field/label/value";
		
		return $this->getCMSModuleHandler()->execute($s);
	}
	
	public function simulateListFormFields(&$settings = false, &$editable_settings = false) {
		$s = $settings;
		$editable_settings = array(
			"elements" => array()
		);
		
		if ($s && !empty($s["fields"]) && is_array($s["fields"]))
			foreach ($s["fields"] as $k => $aux)
				$editable_settings["elements"][".module_list .list_items > .list_container > table.list_table > thead > tr > th.list_column.$k > label"] = "fields/$k/field/label/value";
		
		return $this->getCMSModuleHandler()->execute($s);
	}
}
?>
