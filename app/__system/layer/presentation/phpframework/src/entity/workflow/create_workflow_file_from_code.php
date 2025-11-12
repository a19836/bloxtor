<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once $EVC->getUtilPath("WorkFlowTasksFileHandler");
include get_lib("org.phpframework.workflow.WorkFlowTaskCodeParser");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$path = isset($_GET["path"]) ? $_GET["path"] : null;
$path_extra = isset($_GET["path_extra"]) ? $_GET["path_extra"] : null;

$path = str_replace("../", "", $path);//for security reasons
$path_extra = str_replace("../", "", $path_extra);//for security reasons

$status = false;

if (isset($_POST)) {
	$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

	$code = htmlspecialchars_decode( file_get_contents("php://input"), ENT_NOQUOTES); 
	/*The ENT_NOQUOTES will avoid converting the &quot; to ". If this is not here and if we have some form settings with PTL code like: 
		$form_settings = array("ptl" => array("code" => "<ptl:echo str_replace('\"', '&quot;', \$var_aux_910) />"));
	...it will give a php error, because it will convert &quot; into ", which will be:
		$form_settings = array("ptl" => array("code" => "<ptl:echo str_replace('\"', '"', \$var_aux_910) />"));
	Note that " is not escaped. It should be:
		$form_settings = array("ptl" => array("code" => "<ptl:echo str_replace('\"', '\"', \$var_aux_910) />"));
	
	This ENT_NOQUOTES option was added in 2018-01-09, and I did not tested it for other cases
	*/
	
	$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
	$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
	$WorkFlowTaskHandler->addTasksFoldersPath($code_workflow_editor_user_tasks_folders_path);
	
	$loaded_tasks_settings_cache_id = isset($_GET["loaded_tasks_settings_cache_id"]) ? $_GET["loaded_tasks_settings_cache_id"] : null;
	$loaded_tasks_settings = $WorkFlowTaskHandler->getCachedLoadedTasksSettings($loaded_tasks_settings_cache_id); //Do not use getLoadedTasksSettings bc we want to get the loaded tasks settings with the corespondent $loaded_tasks_settings_cache_id
	
	if ($loaded_tasks_settings) {
		$allowed_tasks_tag = array();
		foreach ($loaded_tasks_settings as $group_id => $group_tasks) 
			foreach ($group_tasks as $task_type => $task_settings) 
				$allowed_tasks_tag[] = isset($task_settings["tag"]) ? $task_settings["tag"] : null;
		
		if ($allowed_tasks_tag) 
			$WorkFlowTaskHandler->setAllowedTaskTags($allowed_tasks_tag);
	}
	
	$WorkFlowTaskHandler->initWorkFlowTasks();

	$WorkFlowTaskCodeParser = new WorkFlowTaskCodeParser($WorkFlowTaskHandler);
	$xml = $WorkFlowTaskCodeParser->getParsedCodeAsXml($code);
	
	$task_file_path = WorkFlowTasksFileHandler::getTaskFilePathByPath($workflow_paths_id, $path, $path_extra);
	$folder = dirname($task_file_path);
			
	if (is_dir($folder) || mkdir($folder, 0775, true))
		if (file_put_contents($task_file_path, $xml) > 0) 
			$status = true;
}
?>
