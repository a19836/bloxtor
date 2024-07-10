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

$task_file_path = WorkFlowTasksFileHandler::getTaskFilePathByPath($workflow_paths_id, $_GET["path"], $_GET["path_extra"]);

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
				$is_loop_allowed = $loop[2];
			
				if (!$is_loop_allowed)
					$obj_settings["error"]["infinit_loop"][] = array("source_task_id" => $loop[0], "target_task_id" => $loop[1]);
			}
		}
	}
}

function convertResultsIntoTasks($items) {
	$tasks = array();
	
	foreach ($items as $item) {
		$task = $item["code"];
		
		if (isset($task["inner"]))
			$task["inner"] = convertResultsIntoTasks($task["inner"]);
		
		if (isset($task["next"]))
			$task["next"] = convertResultsIntoTasks($task["next"]);
		
		if (!$task["inner"])
			unset($task["inner"]);
		
		if (!$task["next"])
			unset($task["next"]);
		
		$tasks[] = $task;
	}
	
	return $tasks;
}

function convertTasksIntoSettingsActions($tasks) {
	$actions = array();
	
	foreach ($tasks as $task) {
		$item = $task["properties"];
		
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
