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

include_once get_lib("org.phpframework.layer.presentation.cms.module.ICMSEntityInstallationHandler");
include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSModuleUtil");
include_once get_lib("org.phpframework.compression.ZipHandler");

class CMSEntityInstallationHandler implements ICMSEntityInstallationHandler {
	protected $entity_path;
	protected $webroot_folder_path;
	protected $blocks_folder_path;
	protected $unzipped_folder_path;
	
	public function __construct($entity_path, $webroot_folder_path, $blocks_folder_path, $unzipped_folder_path = "") {
		$this->entity_path = $entity_path;
		$this->webroot_folder_path = $webroot_folder_path;
		$this->blocks_folder_path = $blocks_folder_path;
		$this->unzipped_folder_path = $unzipped_folder_path;
	}
	
	public static function unzipEntityFile($zipped_file_path, $unzipped_folder_path = null) {
		if (!$unzipped_folder_path) {
			$unzipped_folder_path = self::getTmpFolderPath();
			
			if (!$unzipped_folder_path)
				return false;
		}
		
		if (ZipHandler::unzip($zipped_file_path, $unzipped_folder_path))
			return $unzipped_folder_path;
		
		return null;
	}
	
	/*public static function getUnzippedEntityInfo($unzipped_entity_path) {
		//get the entity info based in the uploaded program.
		$entity_xml_file_path = $unzipped_entity_path . "/page.xml";
		$info = null;
		
		if (file_exists($entity_xml_file_path)) {
			$arr = XMLFileParser::parseXMLFileToArray($entity_xml_file_path);
			$arr = MyXML::complexArrayToBasicArray($arr, array("lower_case_keys" => true, "trim" => true));
			$info = isset($arr["entity"]) ? $arr["entity"] : null;
		}
		
		return $info;
	}*/
	
	public function install() {
		if ($this->unzipped_folder_path && is_dir($this->unzipped_folder_path)) {
			$status = true;
			$file_name = basename($this->unzipped_folder_path) . ".php";
			$file_name = file_exists($this->unzipped_folder_path . $file_name) ? $file_name : "index.php";
			
			if (!CMSModuleUtil::copyFile($this->unzipped_folder_path . $file_name, $this->entity_path))
				$status = false;
			
			if (is_dir($this->unzipped_folder_path . "/webroot") && !CMSModuleUtil::copyFolder($this->unzipped_folder_path . "/webroot", $this->webroot_folder_path))
				$status = false;
			
			if (is_dir($this->unzipped_folder_path . "/block") && !CMSModuleUtil::copyFolder($this->unzipped_folder_path . "/block", $this->blocks_folder_path))
				$status = false;
			
			return $status;
		}
	}
	
	public function uninstall() {
		return CMSModuleUtil::deleteFolder($this->blocks_folder_path) && CMSModuleUtil::deleteFolder($this->webroot_folder_path);
	}
	
	public static function getTmpRootFolderPath() {
		return (defined("TMP_PATH") ? TMP_PATH : sys_get_temp_dir()) . "/entity/";
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
}
?>
