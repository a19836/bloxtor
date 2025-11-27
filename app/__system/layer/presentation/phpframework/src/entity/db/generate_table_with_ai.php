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

include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");
include_once $EVC->getUtilPath("OpenAIActionHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$layer_bean_folder_name = isset($_GET["layer_bean_folder_name"]) ? $_GET["layer_bean_folder_name"] : null;
$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$on_success_js_func = isset($_GET["on_success_js_func"]) ? $_GET["on_success_js_func"] : null;

$PHPVariablesFileHandler = new PHPVariablesFileHandler($user_global_variables_file_path);
$PHPVariablesFileHandler->startUserGlobalVariables();

$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);

if ($obj && is_a($obj, "DB") && $layer_bean_folder_name) {
	$layer_object_id = LAYER_PATH . "$layer_bean_folder_name/$bean_name";
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_object_id, "layer", "access");
	
	if (!$openai_encryption_key)
		$error_message = "Artificial Intelligence is disabled. To enable it, please add your OpenAI Key in the 'Manage Permissions/Users' panel.";
	else if (!empty($_POST)) {
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
		
		if (!empty($_POST["table_name"]) && !empty($_POST["instructions"])) {
			$db_driver_type = $obj->getLabel();
			$res = OpenAIActionHandler::generateTableCreationSQL($openai_encryption_key, $_POST["instructions"], $_POST["table_name"], $db_driver_type);
			$sql = isset($res["sql"]) ? $res["sql"] : null;
			//echo $sql; die();
			
			$status = $obj->setSQL($sql);
			
			if (!$status)
				debug_log("Could not execute auto generated sql from AI: " . $sql, "error");
		}
		else if (empty($_POST["table_name"]))
			$status = "Table name cannot be undefined";
		else
			$status = "Table instructions cannot be undefined";
		
		echo $status;
		die();
	}
}
else 
	$error_message = "Error: Bean object is not a DBDriver!";

$PHPVariablesFileHandler->endUserGlobalVariables();
?>
