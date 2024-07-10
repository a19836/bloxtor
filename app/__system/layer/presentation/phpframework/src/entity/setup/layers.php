<?php
include_once $EVC->getUtilPath("WorkFlowDBHandler");
include_once $EVC->getUtilPath("FlushCacheHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$hide_setup = $_GET["hide_setup"];
$hide_cancel_btn = $_GET["hide_cancel_btn"];
$hide_beginner_btn = $_GET["hide_beginner_btn"];
$strict_connections_to_one_level = $_GET["strict_connections_to_one_level"];

include $EVC->getEntityPath("/layer/diagram");

$tasks_file_path = $workflow_paths_id[$workflow_path_id];

if ($_POST["create_layers_workflow"]) {	
	$tasks_folders = $_POST["tasks_folders"];
	$tasks_labels = $_POST["tasks_labels"];
	
	$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
	
	if (file_exists($tasks_file_path)) {
		$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_file_path);
		
		//PREPARE GLOBAL VARIABLES
		$post_global_vars = array("default_db_driver" => "");
		
		//Only creates these global variables if is a new file, otherwise these variables and others already exist and we MUST NOT overwrite them!
		if ((file_exists($user_global_variables_file_path) && filesize($user_global_variables_file_path) > 0) || PHPVariablesFileHandler::saveVarsToFile($user_global_variables_file_path, $post_global_vars, true)) {
			//check if DB credentials are valid here, before delete or create beans
			if ($WorkFlowDBHandler->areTasksDBDriverSettingsValid($tasks_file_path, true, true, $invalid_task_label)) {
				//PREPARE FOLDERS AND BEANS
				$WorkFlowBeansConverter = new WorkFlowBeansConverter($tasks_file_path, $user_beans_folder_path, $user_global_variables_file_path, $user_global_settings_file_path);
				$WorkFlowBeansConverter->init();
				
				if (!$WorkFlowBeansConverter->removeDeprecatedProjectLayouts($UserAuthenticationHandler, $tasks_folders)) //must run before the createBeans method
					$extra_message = "Additionally there was an error trying to remove the deprecated project layout types. Please try again...";
				
				if ($WorkFlowBeansConverter->createBeans($UserAuthenticationHandler, array("tasks_folders" => $tasks_folders, "tasks_labels" => $tasks_labels))) {
					//add layout type if it comes from setup
					$continue = true;
					
					if (!$WorkFlowBeansConverter->createSetupDefaultProjectLayouts($UserAuthenticationHandler)) {
						$continue = false;
						$error_message = "Error trying to create the default project layout types. Please try again...";
					}
					
					//flush cache - must be after the code: '$WorkFlowBeansConverter->createSetupDefaultProjectLayouts()'
					FlushCacheHandler::flushCache($EVC, $webroot_cache_folder_path, $webroot_cache_folder_url, $workflow_paths_id, $user_global_variables_file_path, $user_beans_folder_path, $css_and_js_optimizer_webroot_cache_folder_path, $deployments_temp_folder_path);
					
					//check if db drivers are valid
					if ($WorkFlowDBHandler->areTasksDBDriverBeanValid($tasks_file_path, true, true, $invalid_task_label)) {
						$msg = "Layers created successfully.";
						$extra_attributes_files_to_check = $WorkFlowBeansConverter->renameExtraAttributesFiles(array("tasks_folders" => $tasks_folders), $extra_attributes_files_changed); //find extra_attributes_settings.php files in the modules folder that belong to DB Drivers with new names.
						
						$deprecated_folders = $WorkFlowBeansConverter->getDeprecatedLayerFolders(); //find old files that are not used anymore
						$wordpress_installations_to_check = $WorkFlowBeansConverter->getWordPressInstallationsWithoutDBDrivers(); //find wordpress installation from db drivers that have new names.
						
						if ($extra_attributes_files_to_check)
							$msg .= "\\n\\nHowever there are some extra attributes files in the modules folder that correspondent to the changed DB Drivers and that could NOT be renamed with the new DB Driver name.\\nPlease rename the following files manually: '" . implode("', '", $extra_attributes_files_to_check) . "'.\\n";
						
						if ($extra_attributes_files_changed)
							$msg .= "\\n\\nNote that some of extra attributes files were changed in Layers, but were not updated in the projects files. This means you need to update the correspondent project files manually.\\n";
						
						if ($wordpress_installations_to_check)
							$msg .= "\\n\\nThe system detected some WordPress installations with different DB credentials than the credentials saved in the current DB Drivers.\\nPlease check the following WordPress installations: '" . implode("', '", $wordpress_installations_to_check) . "'.\\n";
						
						if ($deprecated_folders)
							$msg .= "\\n\\nHowever there are some deprecated folders in your LAYERS directory, this is, probably these folders correspond to some deleted layers.\\nPlease talk with your sysadmin to remove them permanently.\\n\\nDEPRECATED FOLDERS: '" . implode("','", $deprecated_folders) . "'\\n";
						
						if ($extra_message)
							$msg .= "\\n\\n" . $extra_message . "\\n";
						
						if ($continue) {
							if ($is_inside_of_iframe)
								echo '<script>
									alert("' . $msg . '\nCMS will now be reloaded...\n\nNote that if you created any new layer, you must now set the proper permissions in the \'User Management\' panel.");
									
									var url = window.top.location;
									url = ("" + url);
									url = url.indexOf("#") != -1 ? url.substr(0, url.indexOf("#")) : url;
									window.top.location = url;
									//window.parent.location = url;
								</script>';
							else {
								header("location: ?step=4");
								echo '<script>
									alert("' . $msg . '\nCMS will now be reloaded...");
									window.location = "?step=4";
								</script>';
							}
						
							die();
						}
						//else the $error_message was already set previously
					}
					else
						$error_message = "DataBase connection issue for task: '$invalid_task_label'. " . $WorkFlowDBHandler->getError();
				}
				else
					$error_message = "Error trying to create some folders. Please try again or talk with the system administrator.";
			}
			else
				$error_message = "DataBase settings are wrong for task: '$invalid_task_label'. " . $WorkFlowDBHandler->getError();
		}
		else
			$error_message = "There was an error saving the DB settings. Please try again...";
	}
	else
		$error_message = "Error trying to read file path: '$tasks_file_path'.";
}
else {
	$WorkFlowTasksFileHandler = new WorkFlowTasksFileHandler($tasks_file_path);
	$WorkFlowTasksFileHandler->init();
	
	$tasks = $WorkFlowTasksFileHandler->getWorkflowData();
	$tasks_folders = array();
	$tasks_labels = array();
	
	if ($tasks["tasks"])
		foreach ($tasks["tasks"] as $task) {
			$tasks_folders[ $task["id"] ] = WorkFlowBeansConverter::getVariableNameFromRawLabel($task["label"]);
			$tasks_labels[ $task["id"] ] = $task["label"];
		}
}
?>
