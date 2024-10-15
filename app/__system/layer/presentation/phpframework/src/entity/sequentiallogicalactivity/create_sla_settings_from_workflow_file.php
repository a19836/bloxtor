<?php
include_once $EVC->getUtilPath("WorkFlowTasksFileHandler");
include_once get_lib("org.phpframework.workflow.WorkFlowTaskHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$common_project_name = $EVC->getCommonProjectName();

$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
$sla_tasks_folder_path = $EVC->getViewsPath() . "sequentiallogicalactivity/tasks/";
$WorkFlowTaskHandler->addTasksFolderPath($sla_tasks_folder_path);
$WorkFlowTaskHandler->initWorkFlowTasks();

$path = isset($_GET["path"]) ? $_GET["path"] : null;
$path_extra = isset($_GET["path_extra"]) ? $_GET["path_extra"] : null;

$path = str_replace("../", "", $path);//for security reasons
$path_extra = str_replace("../", "", $path_extra);//for security reasons

$task_file_path = WorkFlowTasksFileHandler::getTaskFilePathByPath($workflow_paths_id, $path, $path_extra);

$obj_settings = null;

if ($task_file_path && file_exists($task_file_path)) {
	$loops = $WorkFlowTaskHandler->getLoopsTasksFromFile($task_file_path);
	$res = $WorkFlowTaskHandler->parseFile($task_file_path, $loops, array("return_obj" => true));
	
	if (isset($res)) {
		$tasks = convertResultsIntoTasks($res);
		$actions = convertTasksIntoSettingsActions($tasks);
		$obj_settings = array("actions" => $actions);
		
		if (!empty($loops)) {
			$t = count($loops);
			for ($i = 0; $i < $t; $i++) {
				$loop = $loops[$i];
				$is_loop_allowed = isset($loop[2]) ? $loop[2] : null;
			
				if (!$is_loop_allowed)
					$obj_settings["error"]["infinit_loop"][] = array(
						"source_task_id" => isset($loop[0]) ? $loop[0] : null, 
						"target_task_id" => isset($loop[1]) ? $loop[1] : null
					);
			}
		}
	}
}

function convertResultsIntoTasks($items) {
	$tasks = array();
	
	foreach ($items as $item) {
		$task = isset($item["code"]) ? $item["code"] : null;
		
		if (isset($task["inner"]))
			$task["inner"] = convertResultsIntoTasks($task["inner"]);
		
		if (isset($task["next"]))
			$task["next"] = convertResultsIntoTasks($task["next"]);
		
		if (empty($task["inner"]))
			unset($task["inner"]);
		
		if (empty($task["next"]))
			unset($task["next"]);
		
		$tasks[] = $task;
	}
	
	return $tasks;
}

function convertTasksIntoSettingsActions($tasks) {
	$actions = array();
	
	foreach ($tasks as $task) {
		$item = isset($task["properties"]) ? $task["properties"] : null;
		
		if (isset($task["inner"]))
			$item["action_value"]["actions"] = convertTasksIntoSettingsActions($task["inner"]);
		
		$actions[] = $item;
		
		if (isset($task["next"])) {
			$actions_aux = convertTasksIntoSettingsActions($task["next"]);
			$actions = array_merge($actions, $actions_aux);
		}
	}
	
	return $actions;
}
?>
