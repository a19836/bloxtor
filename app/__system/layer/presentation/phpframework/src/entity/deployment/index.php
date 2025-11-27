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

include_once get_lib("org.phpframework.workflow.WorkFlowTaskHandler");
include_once $EVC->getUtilPath("WorkFlowTestUnitHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");
UserAuthenticationHandler::checkUsersMaxNum($UserAuthenticationHandler);

//PREPARING WORKFLOW
$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
$WorkFlowTaskHandler->setAllowedTaskFolders(array("deployment/", "layer/"));

$containers = array(
	"layer_presentations" => array("presentation"),
	"layer_bls" => array("businesslogic"),
	"layer_dals" => array("dataaccess"),
	"layer_dbs" => array("db"), 
	"layer_drivers" => array("dbdriver"), 
);
$SubWorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
$SubWorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
$SubWorkFlowTaskHandler->setAllowedTaskFolders(array("layer/"));
$SubWorkFlowTaskHandler->setTasksContainers($containers);

$workflow_path_id = "deployment";

//PREPARING BROKERS
$layer_brokers_settings = WorkFlowTestUnitHandler::getAllLayersBrokersSettings($user_global_variables_file_path, $user_beans_folder_path);
//print_r($layer_brokers_settings);die();

$db_brokers = isset($layer_brokers_settings["db_brokers"]) ? $layer_brokers_settings["db_brokers"] : null;
$ibatis_brokers = isset($layer_brokers_settings["ibatis_brokers"]) ? $layer_brokers_settings["ibatis_brokers"] : null;
$hibernate_brokers = isset($layer_brokers_settings["hibernate_brokers"]) ? $layer_brokers_settings["hibernate_brokers"] : null;
$data_access_brokers = isset($layer_brokers_settings["data_access_brokers"]) ? $layer_brokers_settings["data_access_brokers"] : null;
$business_logic_brokers = isset($layer_brokers_settings["business_logic_brokers"]) ? $layer_brokers_settings["business_logic_brokers"] : null;
$presentation_brokers = isset($layer_brokers_settings["presentation_brokers"]) ? $layer_brokers_settings["presentation_brokers"] : null;

//PREPARING BEAN FOLDERS
$beans_folders_name = array(
	"dao" => substr(DAO_PATH, strlen(CMS_PATH)),
	"lib" => substr(LIB_PATH, strlen(CMS_PATH)),
	"vendor" => substr(VENDOR_PATH, strlen(CMS_PATH)),
	"test_unit" => substr(TEST_UNIT_PATH, strlen(CMS_PATH)),
);

$relative_layers_path = substr(LAYER_PATH, strlen(CMS_PATH));

foreach ($layer_brokers_settings as $k => $layer_brokers)
	if ($k == "data_access_brokers" || $k == "business_logic_brokers" || $k == "presentation_brokers") {
		$t = count($layer_brokers);
		
		for ($i = 0; $i < $t; $i++) {
			$l = $layer_brokers[$i];
			$bean_file_name = isset($l[1]) ? $l[1] : null;
			$bean_name = isset($l[2]) ? $l[2] : null;
			$bean_folder_name = WorkFlowBeansFileHandler::getLayerBeanFolderName($user_beans_folder_path . $bean_file_name, $bean_name, $user_global_variables_file_path);
			
			if ($bean_folder_name)
				$beans_folders_name[$bean_name] = $relative_layers_path . $bean_folder_name . "/";
		}
	}

//PREPARING OBFUSCATE OPTIONS
$method = PHPCodePrintingHandler::getFunctionCodeFromFile($EVC->getUtilPath("CMSObfuscatePHPFilesHandler"), "getDefaultFilesSettings", "CMSObfuscatePHPFilesHandler");
$show_php_obfuscation_option = !empty($method[0]);

$method = PHPCodePrintingHandler::getFunctionCodeFromFile($EVC->getUtilPath("CMSObfuscateJSFilesHandler"), "getDefaultFilesSettings", "CMSObfuscateJSFilesHandler");
$show_js_obfuscation_option = !empty($method[0]);

//PREPARING LICENCE OPTIONS
$li = $EVC->getPresentationLayer()->getPHPFrameWork()->getLicenceInfo();
$projects_max_expiration_date = isset($li["ped"]) ? $li["ped"] : null;
$sysadmin_max_expiration_date = isset($li["sed"]) ? $li["sed"] : null;
$projects_max_num = isset($li["pmn"]) ? $li["pmn"] : null;
$users_max_num = isset($li["umn"]) ? $li["umn"] : null;
$end_users_max_num = isset($li["eumn"]) ? $li["eumn"] : null;
$actions_max_num = isset($li["amn"]) ? $li["amn"] : null;
$allowed_paths = isset($li["ap"]) ? $li["ap"] : null;
$allowed_domains = isset($li["ad"]) ? $li["ad"] : null;
$check_allowed_domains_port = isset($li["cadp"]) ? $li["cadp"] : null;
$allowed_sysadmin_migration = isset($li["asm"]) ? $li["asm"] : null;

if ($projects_max_num > 0)
	$projects_max_num--; //decrease 1 project bc the $projects_max_num_allowed contains the common project
?>
