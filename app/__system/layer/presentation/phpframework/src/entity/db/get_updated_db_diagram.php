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
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$path_extra = isset($_GET["path_extra"]) ? $_GET["path_extra"] : null;

$path = str_replace("../", "", $path);//for security reasons
$path_extra = str_replace("../", "", $path_extra);//for security reasons

$layer_object_id = LAYER_PATH . "$layer_bean_folder_name/$bean_name";
$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_object_id, "layer", "access");

$tasks_file_path = WorkFlowTasksFileHandler::getTaskFilePathByPath($workflow_paths_id, $path, $path_extra);

$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_file_path);
$tasks = $WorkFlowDBHandler->getUpdateTaskDBDiagram($bean_file_name, $bean_name, $tasks_file_path);

$error = $WorkFlowDBHandler->getError();

if (!empty($error)) {
	$tasks = false;
	echo $error;
}
else if ($tasks && !empty($tasks["tasks"])) { //must change the task_ids bc if table contains the schema, it will mess the diagram bc the system will try to create a new task with an ID "schema.table" and then the javasceript will try to get #schema.table, which doesn't exists, bc ".table" will be a class. DO NOT CHANGE THE getUpdateTaskDBDiagram directly bc this is used in other places and must be with the right table name with schema, if exists
	foreach ($tasks["tasks"] as $task_id => $task) 
		if (strpos($task_id, ".") !== false) {
			unset($tasks["tasks"][$task_id]);
			
			$task_id = str_replace(".", "_", $task_id);
			$task["id"] = $task_id;
			$tasks["tasks"][$task_id] = $task;
		}
}
?>
