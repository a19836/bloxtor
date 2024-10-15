<?php
include_once $EVC->getUtilPath("CMSDeploymentHandler");

class WorkFlowDeploymentHandler {
	
	public static function validateTemplate($server_name, $template_id, $workflow_paths_id, $licence_data, &$error_message = null) {
		$status = false;
		
		if ($server_name && is_numeric($template_id)) {
			$status = true;
			
			$workflow_path = WorkFlowTasksFileHandler::getTaskFilePathByPath($workflow_paths_id, "deployment");
			$WorkFlowTasksFileHandler = new WorkFlowTasksFileHandler($workflow_path);
			$WorkFlowTasksFileHandler->init();
			$deployment_tasks = $WorkFlowTasksFileHandler->getWorkflowData();
			
			$workflow_path = WorkFlowTasksFileHandler::getTaskFilePathByPath($workflow_paths_id, "layer");
			$WorkFlowTasksFileHandler = new WorkFlowTasksFileHandler($workflow_path);
			$WorkFlowTasksFileHandler->init();
			$layer_tasks = $WorkFlowTasksFileHandler->getWorkflowData();
			
			$layer_tasks_by_label = CMSDeploymentHandler::getTasksByLabel($layer_tasks);
			
			//echo "<pre>";
			//print_r($layer_tasks);
			//print_r($deployment_tasks);
			//print_r($layer_tasks_by_label);
			
			$server_template = CMSDeploymentHandler::getServerTaskTemplate($deployment_tasks, $server_name, $template_id);
			//print_r($server_template);
			
			if ($server_template) {
				$template_properties = isset($server_template["properties"]) ? $server_template["properties"] : null;
				$template_tasks = isset($template_properties["task"]) ? $template_properties["task"] : null;
				$template_actions = isset($template_properties["actions"]) ? $template_properties["actions"] : null;
				
				//checks if server template licence data is validated accordingly with the current licence
				$error_messages = array();
				if (!CMSDeploymentHandler::validateServerTemplateLicenceData($server_template, $licence_data, $error_messages)) {
					$error_message = implode("\n" , $error_messages);
					$status = false;
				}
				
				//checks if there is any active tasks that are not in layers diagram.
				if ($status && $template_tasks)
					foreach ($template_tasks as $task) {
						$task_label = isset($task["label"]) ? $task["label"] : null;
						$task_props = isset($task["properties"]) ? $task["properties"] : null;
						
						if ($task_props && !empty($task_props["active"])) {
							$task = $layer_tasks_by_label[$task_label];
							
							if (!$task) {
								$error_message = "Error: '$task_label' Task does not exists anymore!";
								$status = false;
								break;
							}
						}
					}
					
				//checks if files in actions exists
				if ($status && $template_actions) {
					$is_assoc = array_keys($template_actions) !== range(0, count($template_actions) - 1);
					
					if ($is_assoc)
						$template_actions = array($template_actions);
					
					foreach ($template_actions as $idx => $template_action)
						foreach ($template_action as $action_type => $action) 
							if (($action_type == "run_test_units" || $action_type == "copy_files") && (!isset($action["active"]) || $action["active"])) {
								$files = isset($action["files"]) ? (is_array($action["files"]) ? $action["files"] : array($action["files"])) : null;
								
								if ($files)
									foreach ($files as $idy => $file) 
										if ($file) {
											$file_path = ($action_type == "run_test_units" ? TEST_UNIT_PATH : CMS_PATH) . $file;
											
											if (!file_exists($file_path)) {
												$error_message = "Error: Selected file for action '" . ucwords(str_replace("_", " ", $action_type)) . "' does not exists! File path: $file";
												$status = false;
												break;
											}
										}
							}
				}
			}
			else { 
				$error_message = "Error: Template '$template_id' in '$server_name' server does not exists or not saved yet!";
				$status = false;
			}
		}
		
		return $status;
	}
}
?>
