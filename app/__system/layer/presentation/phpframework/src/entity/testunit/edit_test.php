<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.workflow.WorkFlowTaskHandler");
include_once $EVC->getUtilPath("WorkFlowTestUnitHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");
UserAuthenticationHandler::checkUsersMaxNum($UserAuthenticationHandler);

$path = isset($_GET["path"]) ? $_GET["path"] : null;
$file_name = isset($_GET["file_name"]) ? $_GET["file_name"] : null;

$path = str_replace("../", "", $path);//for security reasons

$file_path = TEST_UNIT_PATH . $path;

if ($path && file_exists($file_path)) {
	$file_modified_time = filemtime($file_path);
	
	//PREPARING FILE CODE
	$class_name = pathinfo($path, PATHINFO_FILENAME);
	
	$obj_data = PHPCodePrintingHandler::getFunctionFromFile($file_path, "execute", $class_name);
	
	if ($obj_data) {
		$obj_data["code"] = PHPCodePrintingHandler::getFunctionCodeFromFile($file_path, "execute", $class_name);
		$obj_data["code"] = str_replace(chr(194) . chr(160), ' ', $obj_data["code"]);
	}
	
	//PREPARING BROKERS
	$layer_brokers_settings = WorkFlowTestUnitHandler::getAllLayersBrokersSettings($user_global_variables_file_path, $user_beans_folder_path);
	//print_r($layer_brokers_settings);die();
	
	$db_driver_brokers = $layer_brokers_settings["db_driver_brokers"];
	$db_driver_brokers_obj = $layer_brokers_settings["db_driver_brokers_obj"];

	$db_brokers = $layer_brokers_settings["db_brokers"];
	$db_brokers_obj = $layer_brokers_settings["db_brokers_obj"];

	$data_access_brokers = $layer_brokers_settings["data_access_brokers"];
	$data_access_brokers_obj = $layer_brokers_settings["data_access_brokers_obj"];

	$ibatis_brokers = $layer_brokers_settings["ibatis_brokers"];
	$ibatis_brokers_obj = $layer_brokers_settings["ibatis_brokers_obj"];

	$hibernate_brokers = $layer_brokers_settings["hibernate_brokers"];
	$hibernate_brokers_obj = $layer_brokers_settings["hibernate_brokers_obj"];
	
	$business_logic_brokers = $layer_brokers_settings["business_logic_brokers"];
	$business_logic_brokers_obj = $layer_brokers_settings["business_logic_brokers_obj"];
	
	$presentation_brokers = $layer_brokers_settings["presentation_brokers"];
	$presentation_brokers_obj = $layer_brokers_settings["presentation_brokers_obj"];
	
	$presentation_evc_brokers = $layer_brokers_settings["presentation_evc_brokers"];
	$presentation_evc_brokers_obj = $layer_brokers_settings["presentation_evc_brokers_obj"];
	
	$presentation_evc_template_brokers = $layer_brokers_settings["presentation_evc_brokers"];
	$presentation_evc_template_brokers_obj = $layer_brokers_settings["presentation_evc_brokers_obj"];
	
	$available_projects = $layer_brokers_settings["available_projects"];
	
	//PREPARING getbeanobject
	$phpframeworks_options = array("default" => '$GLOBALS["PHPFrameWork"]');
	$bean_names_options = array_keys($GLOBALS["PHPFrameWork"]->getObjects());
	
	//PREPARING brokers db drivers
	$db_drivers_options = array_keys($db_driver_brokers_obj);
	
	//PREPARING $layers_projects_urls
	$layers_projects_urls = getLayerProjectsUrls($user_global_variables_file_path, $user_beans_folder_path, $presentation_brokers);
	
	//PREPARING WORKFLOW TASKS
	$allowed_tasks_tag = array(
		"definevar", "setvar", "setarray", "setdate", "setobjectproperty", "createclassobject", "callobjectmethod", "callfunction", "if", "switch", "loop", "foreach", "includefile", "echo", "code", "break", "return", "exit", "validator", "upload", "geturlcontents", "restconnector", "soapconnector", "getbeanobject", "sendemail", "debuglog", 
		"trycatchexception", "throwexception", "printexception"
	);
	
	if ($db_brokers_obj || $data_access_brokers_obj) {
		$allowed_tasks_tag[] = "setquerydata";
		$allowed_tasks_tag[] = "getquerydata";
		$allowed_tasks_tag[] = "dbdaoaction";
	}
	
	if ($db_brokers_obj)
		$allowed_tasks_tag[] = "getdbdriver";
	
	if ($ibatis_brokers_obj) 
		$allowed_tasks_tag[] = "callibatisquery";
	
	if ($hibernate_brokers_obj) {
		$allowed_tasks_tag[] = "callhibernateobject";
		$allowed_tasks_tag[] = "callhibernatemethod";
	}

	if ($business_logic_brokers_obj) 
		$allowed_tasks_tag[] = "callbusinesslogic";
	
	if ($presentation_brokers_obj) {
		$aux = array(
			"addheader", 
			/*"callpresentationlayerwebservice", */"setpresentationview", "addpresentationview", "setpresentationtemplate", //callpresentationlayerwebservice must be executed inside of a presentation layer, it must be disabled here!
			"inlinehtml", "createform"
		);
		$allowed_tasks_tag = array_merge($allowed_tasks_tag, $aux);
	}
	
	if ($presentation_evc_brokers_obj) {
		$aux = array(
			"setblockparams", "settemplateregionblockparam", "includeblock", "addregionhtml", "addregionblock", "rendertemplateregion", "settemplateparam", "gettemplateparam",
		);
		$allowed_tasks_tag = array_merge($allowed_tasks_tag, $aux);
	}
	
	$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
	$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
	$WorkFlowTaskHandler->setAllowedTaskTags($allowed_tasks_tag);
	$WorkFlowTaskHandler->addTasksFoldersPath($code_workflow_editor_user_tasks_folders_path);
	$WorkFlowTaskHandler->addAllowedTaskTagsFromFolders($code_workflow_editor_user_tasks_folders_path);
	
	//PREPARING DOC-BLOCK COMMENTS
	$comments = !empty($obj_data["doc_comments"]) ? implode("\n", $obj_data["doc_comments"]) : "";
	$DocBlockParser = new DocBlockParser();
	$DocBlockParser->ofComment($comments);
	$objects = $DocBlockParser->getObjects();
	$method_comments = $DocBlockParser->getDescription();
	$enabled = !empty($objects["enabled"][0]);
	$global_variables_files_path = isset($objects["global_variables_files_path"]) ? $objects["global_variables_files_path"] : null;
	$depends = isset($objects["depends"]) ? $objects["depends"] : null;
	//echo "<pre>";print_r($objects);die();
}
else {
	launch_exception(new Exception("File Not Found: " . $path));
	die();
}

function getLayerProjectsUrls($user_global_variables_file_path, $user_beans_folder_path, $presentation_brokers) {
	$layers_projects_urls = array();
	
	if ($presentation_brokers)
		foreach ($presentation_brokers as $presentation_broker) {
			$bean_file_name = isset($presentation_broker[1]) ? $presentation_broker[1] : null;
			$bean_name = isset($presentation_broker[2]) ? $presentation_broker[2] : null;
			
			$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
			$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name);
			$projects = $PEVC->getProjectsId();
			
			if ($projects)
				foreach ($projects as $project) {
					$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $project);
					
					$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config")));
					$PHPVariablesFileHandler->startUserGlobalVariables();
					
					$layers_projects_urls[$bean_name][$project] = getProjectUrlPrefix($PEVC, $project);
					
					$PHPVariablesFileHandler->endUserGlobalVariables();
				}
		}
	
	return $layers_projects_urls;
}

function getProjectUrlPrefix($EVC, $selected_project_id) {
	@include $EVC->getConfigPath("config", $selected_project_id); //config file may not exist
	return $project_url_prefix;
}
?>
