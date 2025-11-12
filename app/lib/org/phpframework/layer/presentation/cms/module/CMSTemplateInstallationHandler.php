<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.layer.presentation.cms.module.ICMSTemplateInstallationHandler");
include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSModuleUtil");
include_once get_lib("org.phpframework.compression.ZipHandler");

class CMSTemplateInstallationHandler implements ICMSTemplateInstallationHandler {
	protected $template_folder_path;
	protected $webroot_folder_path;
	protected $unzipped_folder_path;
	
	protected $reserved_files;
	
	public function __construct($template_folder_path, $webroot_folder_path, $unzipped_folder_path = "") {
		$this->template_folder_path = $template_folder_path;
		$this->webroot_folder_path = $webroot_folder_path;
		$this->unzipped_folder_path = $unzipped_folder_path;
		
		$this->reserved_files = array();
	}
	
	public static function unzipTemplateFile($zipped_file_path, $unzipped_folder_path = null) {
		if (!$unzipped_folder_path) {
			$unzipped_folder_path = self::getTmpFolderPath();
			
			if (!$unzipped_folder_path)
				return false;
		}
		
		if (ZipHandler::unzip($zipped_file_path, $unzipped_folder_path))
			return $unzipped_folder_path;
		
		return null;
	}
	
	public static function getUnzippedTemplateInfo($unzipped_template_path) {
		//get the template info based in the uploaded program.
		$template_xml_file_path = $unzipped_template_path . "/template.xml";
		$info = null;
		
		if (file_exists($template_xml_file_path)) {
			$arr = XMLFileParser::parseXMLFileToArray($template_xml_file_path);
			$arr = MyXML::complexArrayToBasicArray($arr, array("lower_case_keys" => true, "trim" => true));
			$info = isset($arr["template"]) ? $arr["template"] : null;
		}
		
		return $info;
	}
	
	public function install() {
		if ($this->unzipped_folder_path && is_dir($this->unzipped_folder_path)) {
			$status = true;
			
			if (CMSModuleUtil::copyFolder($this->unzipped_folder_path, $this->template_folder_path)) {
				if (is_dir($this->unzipped_folder_path . "/webroot")) {
					if (!CMSModuleUtil::deleteFolder($this->template_folder_path . "/webroot"))
						$status = false;
					
					if (!CMSModuleUtil::copyFolder($this->unzipped_folder_path . "/webroot", $this->webroot_folder_path))
						$status = false;
				}
			}
			else
				$status = false;
			
			//DEPRECATED - do not delete template.xml,bc we need it to show only the template layouts and not the other php files
			//delete file template.xml
			//if (file_exists($this->template_folder_path . "/template.xml"))
			//	@unlink($this->template_folder_path . "/template.xml");
			
			//delete file modules_sub_templates.ser that is created by the CommonModuleUI::prepareSettingsWithSelectedTemplateModuleHtml method.
			if (file_exists($this->template_folder_path . "/modules_sub_templates.ser"))
				@unlink($this->template_folder_path . "/modules_sub_templates.ser");
			
			return $status;
		}
	}
	
	public function uninstall() {
		$reserved_files = $this->getReservedFiles();
		
		return CMSModuleUtil::deleteFolder($this->template_folder_path, $reserved_files) && CMSModuleUtil::deleteFolder($this->webroot_folder_path, $reserved_files);
	}
	
	public function addLayoutToTemplateXml($layout_name, $return_if_file_not_exists = false) {
		$template_xml_file_path = $this->template_folder_path . "template.xml";
		
		if (file_exists($template_xml_file_path)) {
			$arr = XMLFileParser::parseXMLFileToArray($template_xml_file_path);
			$arr = MyXML::complexArrayToBasicArray($arr, array("lower_case_keys" => true, "trim" => true));
			
			if (!isset($arr["template"]["layouts"]["layout"]))
				$arr["template"]["layouts"]["layout"] = $layout_name;
			else if (is_array($arr["template"]["layouts"]["layout"])) {
				if (!in_array($layout_name, $arr["template"]["layouts"]["layout"]))
					$arr["template"]["layouts"]["layout"][] = $layout_name;
			}
			else if ($arr["template"]["layouts"]["layout"] != $layout_name)
				$arr["template"]["layouts"]["layout"] = array($arr["template"]["layouts"]["layout"], $layout_name);
				
			$arr = MyXML::basicArrayToComplexArray($arr, array("lower_case_keys" => true, "trim" => true));
			$MyXMLArray = new MyXMLArray($arr);
			$xml = $MyXMLArray->toXML(array("lower_case_keys" => true));
			$xml = '<?xml version="1.0" encoding="UTF-8"?>' . str_replace("&amp;", "&", $xml);
			
			return file_put_contents($template_xml_file_path, $xml) !== false;
		}
		
		return $return_if_file_not_exists;
	}
	
	public function removeLayoutFromTemplateXml($layout_name, $return_if_file_not_exists = false) {
		$template_xml_file_path = $this->template_folder_path . "template.xml";
		
		if (file_exists($template_xml_file_path)) {
			$arr = XMLFileParser::parseXMLFileToArray($template_xml_file_path);
			$arr = MyXML::complexArrayToBasicArray($arr, array("lower_case_keys" => true, "trim" => true));
			
			if (!isset($arr["template"]["layouts"]["layout"]))
				return true;
			else if (is_array($arr["template"]["layouts"]["layout"])) {
				if (in_array($layout_name, $arr["template"]["layouts"]["layout"])) {
					$key = array_search($layout_name, $arr["template"]["layouts"]["layout"]);
					array_splice($arr["template"]["layouts"]["layout"], $key, 1);
					
					if (count($arr["template"]["layouts"]["layout"]) == 0)
						unset($arr["template"]["layouts"]["layout"]);
				}
				else
					return true;
			}
			else if ($arr["template"]["layouts"]["layout"] == $layout_name)
				unset($arr["template"]["layouts"]["layout"]);
				
			$arr = MyXML::basicArrayToComplexArray($arr, array("lower_case_keys" => true, "trim" => true));
			$MyXMLArray = new MyXMLArray($arr);
			$xml = $MyXMLArray->toXML(array("lower_case_keys" => true));
			$xml = '<?xml version="1.0" encoding="UTF-8"?>' . str_replace("&amp;", "&", $xml);
			
			return file_put_contents($template_xml_file_path, $xml) !== false;
		}
		
		return $return_if_file_not_exists;
	}
	
	//check if template is getting installed in the common project, then replace all $original_project_url_prefix by $project_common_url_prefix
	public function prepareInstalledCommonTemplate() {
		return self::prepareInstalledCommonTemplateFolder($this->template_folder_path);
	}
	
	public static function getTmpRootFolderPath() {
		return (defined("TMP_PATH") ? TMP_PATH : sys_get_temp_dir()) . "/template/";
	}
	
	public static function getTmpFolderPath($default_name = null) {
		$root_path = self::getTmpRootFolderPath();
		$tmp_path = $default_name ? $root_path . $default_name : tempnam($root_path, "");
		
		if (file_exists($tmp_path))
			unlink($tmp_path); 
		
		@mkdir($tmp_path, 0755);
		
		if (is_dir($tmp_path))
			return $tmp_path . "/";
	}
	
	/* PROTECTED */
	//replace all $original_project_url_prefix by $project_common_url_prefix in all php files
	protected static function prepareInstalledCommonTemplateFolder($path) {
		$status = true;
		$files = array_diff(scandir($path), array('..', '.'));
		
		foreach ($files as $file) {
			$f = "$path/$file";
			
			if (is_dir($f)) {
				if (!self::prepareInstalledCommonTemplateFolder($f))
					$status = false;
			}
			else if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) == "php") {
				$code = file_get_contents($f);
				$code = str_replace('$original_project_url_prefix', '$project_common_url_prefix', $code);
				
				if (file_put_contents($f, $code) === false)
					$status = false;
			}
		}
		
		return $status;
	}
	
	protected function getReservedFiles() {
		$reserved_files = array();
		
		if ($this->reserved_files)
			foreach ($this->reserved_files as $file)
				$reserved_files[] = file_exists($file) ? realpath($file) : $file; //file_exists is very important bc if file doesn't exists, the realpath will return "/" but bc of the basedir in the php.ini we will get a php error bc the "/" folder is not allowed (bc of security reasons).
		
		return $reserved_files;
	}
}
?>
