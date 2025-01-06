<?php
include_once $EVC->getUtilPath("WorkFlowTasksFileHandler");
include_once get_lib("org.phpframework.workflow.WorkFlowTaskHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$path = isset($_GET["path"]) ? $_GET["path"] : null;
$path_extra = isset($_GET["path_extra"]) ? $_GET["path_extra"] : null;

$path = str_replace("../", "", $path);//for security reasons
$path_extra = str_replace("../", "", $path_extra);//for security reasons

$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
$WorkFlowTaskHandler->addTasksFoldersPath($code_workflow_editor_user_tasks_folders_path);
$WorkFlowTaskHandler->initWorkFlowTasks();

$task_file_path = WorkFlowTasksFileHandler::getTaskFilePathByPath($workflow_paths_id, $path, $path_extra);

if ($task_file_path && file_exists($task_file_path)) {
	//$memory_usage_before = memory_get_usage();
	
	$loops = $WorkFlowTaskHandler->getLoopsTasksFromFile($task_file_path);
	$code = $WorkFlowTaskHandler->parseFile($task_file_path, $loops);
	
	if (isset($code)) {
		$obj = array("code" => $code);
		
		if (!empty($loops)) {
			$t = count($loops);
			for ($i = 0; $i < $t; $i++) {
				$loop = $loops[$i];
				$is_loop_allowed = isset($loop[2]) ? $loop[2] : null;
			
				if (!$is_loop_allowed)
					$obj["error"]["infinit_loop"][] = array(
						"source_task_id" => isset($loop[0]) ? $loop[0] : null,
						"target_task_id" => isset($loop[1]) ? $loop[1] : null
					);
			}
		}
	}
	
	/*$memory_usage_after = memory_get_usage();
	$memory_usage_after = memory_get_peak_usage();
	$size = $memory_usage_after - $memory_usage_before;
	$unit = array('b','kb','mb','gb','tb','pb');
	$exponent = floor( log($size, 1024) );
	$mem = @round($size / pow(1024, $exponent), 2) . ' ' . $unit[$exponent];
	error_log("Used memory in create_code_from_workflow_file script:".$mem."\n\n", 3, $GLOBALS["log_file_path"]);*/
}
?>
