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

include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;

$files = CMSPresentationLayerHandler::getPresentationLayersProjectsFiles($user_global_variables_file_path, $user_beans_folder_path);
//echo "<pre>";print_r($files);die();

if ($files) {
	if ($bean_name && empty($files[$bean_name]))
		$bean_name = $bean_file_name = null;
	
	if (!$bean_name) {
		$bean_name = array_keys($files)[0];
		$bean_file_name = isset($files[$bean_name]["bean_file_name"]) ? $files[$bean_name]["bean_file_name"] : null;
	}
	else if (!$bean_file_name)
		$bean_file_name = $files[$bean_name]["bean_file_name"];
	else if (isset($files[$bean_name]["bean_file_name"]) && $bean_file_name != $files[$bean_name]["bean_file_name"])
		$bean_file_name = $files[$bean_name]["bean_file_name"];
}

if ($bean_name && $bean_file_name) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, null);
	
	if ($PEVC) {
		$P = $PEVC->getPresentationLayer();
		
		$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config")));
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		$layer_path = $P->getLayerPathSetting();
		$htaccess_path = $layer_path . ".htaccess";
		
		//save default project
		if (!empty($_POST) && !empty($_POST["default_project"])) {
			$contents = file_get_contents($htaccess_path);
			
			//'\w' means all words with '_' and 'u' means with accents and ç too. '/u' converts unicode to accents chars.
			$contents = preg_replace("/(RewriteRule\s*\\^\\$\s*)([\w\-\+\/]+)(\\/webroot\\/)/u", "$1" . $_POST["default_project"] . "$3", $contents);
			$contents = preg_replace("/(RewriteRule\s*\\(\\.\\*\\)\s*)([\w\-\+\/]+)(\\/webroot\\/\\$1)/u", "$1" . $_POST["default_project"] . "$3", $contents);
			
			$save_message = file_put_contents($htaccess_path, $contents) !== false ? "Saved sucessfully" : "Error trying to save default project! Please try again...";
		}
		
		//get default project
		$default_project = WorkFlowBeansFolderHandler::getPresentationLayerDefaultproject($htaccess_path);
		
		//get default layer
		$default_layer = WorkFlowBeansFolderHandler::getDefaultLayerFolder(LAYER_PATH . ".htaccess");
		
		$bean_folder = WorkFlowBeansFileHandler::getLayerBeanFolderName($user_beans_folder_path . $bean_file_name, $bean_name, $user_global_variables_file_path);
	}
}
?>
