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

include_once $EVC->getUtilPath("WorkFlowDBHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$layer_bean_folder_name = isset($_GET["layer_bean_folder_name"]) ? $_GET["layer_bean_folder_name"] : null;
$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$popup = isset($_GET["popup"]) ? $_GET["popup"] : null;

if ($bean_name) {
	$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_file_path);
	
	$layer_object_id = LAYER_PATH . "$layer_bean_folder_name/$bean_name";
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_object_id, "layer", "access");
	
	if (!empty($_POST)) {
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
		
		$sql = isset($_POST["sql"]) ? $_POST["sql"] : null;
		
		if ($sql) {
			$DBDriver = $WorkFlowDBHandler->getBeanObject($bean_file_name, $bean_name);
			$status = $DBDriver->setData($sql);
		}
	}
	else {
		$tasks_file_path = WorkFlowTasksFileHandler::getDBDiagramTaskFilePath($workflow_paths_id, "db_diagram", $bean_name);
		
		$sql = $WorkFlowDBHandler->getTaskDBDiagramSql($bean_file_name, $bean_name, $tasks_file_path);
	}
}
?>
