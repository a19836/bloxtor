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

include $EVC->getUtilPath("WorkFlowUIHandler");

$WorkFlowUIHandler = new WorkFlowUIHandler($WorkFlowTaskHandler, $project_url_prefix, $project_common_url_prefix, $external_libs_url_prefix, $user_global_variables_file_path, $webroot_cache_folder_path, $webroot_cache_folder_url);

$head = $WorkFlowUIHandler->getHeader();
$head .= $WorkFlowUIHandler->getJS($get_workflow_file_path, $set_workflow_file_path, array("is_droppable_connection" => true, "add_default_start_task" => true, "resizable_task_properties" => true, "resizable_connection_properties" => true));
$head .= '<style type="text/css">
	.taskflowchart .workflow_menu .dropdown {margin:0; padding:0;}
	.taskflowchart .workflow_menu .dropdown li a {font-size:14px !important;}
	
	.taskflowchart .tasks_flow #content_with_only_if {width:100%; height:50%; background-color:#FF0000;}
	.taskflowchart .tasks_flow #content_with_only_switch {width:100%; height:50%; background-color:#00FFFF;}
	
	.taskflowchart .tasks_flow .task.logic_task .short_actions {right:-25px;}
</style>';

$main_content = $WorkFlowUIHandler->getContent();

/*
TODO:
- escape (addcslashes($value, '\\"')) values in each task of the: public function printCode($tasks) 
- design UI for each task
*/
?>
