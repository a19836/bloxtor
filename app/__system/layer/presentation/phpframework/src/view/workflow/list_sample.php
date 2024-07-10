<?php
include $EVC->getUtilPath("WorkFlowUIHandler");

$WorkFlowUIHandler = new WorkFlowUIHandler($WorkFlowTaskHandler, $project_url_prefix, $project_common_url_prefix, $external_libs_url_prefix, $user_global_variables_file_path, $webroot_cache_folder_path, $webroot_cache_folder_url);

$head = $WorkFlowUIHandler->getHeader();
$head .= $WorkFlowUIHandler->getJS($get_workflow_file_path, $set_workflow_file_path, array("is_droppable_connection" => true, "add_default_start_task" => true, "resizable_task_properties" => true, "resizable_connection_properties" => true));
$head .= '<style type="text/css">
	.tasks_flow #content_with_only_if {width:100%; height:50%; background-color:#FF0000;}
	.tasks_flow #content_with_only_switch {width:100%; height:50%; background-color:#00FFFF;}
</style>';

$main_content = $WorkFlowUIHandler->getContent();

/*
TODO:
- escape (addcslashes($value, '\\"')) values in each task of the: public function printCode($tasks) 
- design UI for each task
*/
?>
