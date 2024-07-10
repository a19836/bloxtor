<?php
include_once $EVC->getUtilPath("WorkFlowDBHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$layer_bean_folder_name = $_GET["layer_bean_folder_name"];
$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$path = $_GET["path"];
$path_extra = $_GET["path_extra"];

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
else if ($tasks && $tasks["tasks"]) { //must change the task_ids bc if table contains the schema, it will mess the diagram bc the system will try to create a new task with an ID "schema.table" and then the javasceript will try to get #schema.table, which doesn't exists, bc ".table" will be a class. DO NOT CHANGE THE getUpdateTaskDBDiagram directly bc this is used in other places and must be with the right table name with schema, if exists
	foreach ($tasks["tasks"] as $task_id => $task) 
		if (strpos($task_id, ".") !== false) {
			unset($tasks["tasks"][$task_id]);
			
			$task_id = str_replace(".", "_", $task_id);
			$task["id"] = $task_id;
			$tasks["tasks"][$task_id] = $task;
		}
}
?>
