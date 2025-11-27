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

include_once $EVC->getUtilPath("WorkFlowBeansFolderHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$WorkFlowBeansFolderHandler = new WorkFlowBeansFolderHandler($user_beans_folder_path, $user_global_variables_file_path, $user_global_settings_file_path);

$default_project_name = $WorkFlowBeansFolderHandler->getSetupDefaultProjectName();

if (!empty($_POST["project_name"])) {
	$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

	$status = true;	
	
	if ($_POST["project_name"])
		$_POST["project_name"] = str_replace(" ", "_", strtolower($_POST["project_name"]));
	
	$_POST["project_name"] = empty($_POST["project_name"]) ? $default_project_name : $_POST["project_name"];
	
	if ($WorkFlowBeansFolderHandler->setSetupProjectName($_POST["project_name"])) {
		if ($WorkFlowBeansFolderHandler->createDefaultFiles()) {
			header("location: ?step=3");
			echo '<script>window.location = "?step=3"</script>';
			die();
		}
		else {
			$error_message = "There was an error trying to save the default project name. Please try again...";
		}
	}
	else {
		$error_message = "There was an error trying to save the default project name. Please try again...";
	}
}

$project_name = !empty($_POST["project_name"]) ? $_POST["project_name"] : $WorkFlowBeansFolderHandler->getSetupProjectName();
?>
