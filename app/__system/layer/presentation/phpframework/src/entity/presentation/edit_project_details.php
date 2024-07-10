<?php
//NOTE: IF YOU MAKE	ANY CHANGES IN THIS FILE, PLEASE BE SURE THAT THE create_project.php COVERS THAT CHANGES AND DOESN'T BREAK ITS LOGIC.

include_once get_lib("org.phpframework.workflow.WorkFlowTaskHandler");
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");
include_once $EVC->getUtilPath("LayoutTypeProjectHandler");
include_once $EVC->getUtilPath("WorkFlowDBHandler");
include_once $EVC->getUtilPath("FlushCacheHandler");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$path = $_GET["path"];
$popup = $_GET["popup"]; //optional
$on_success_js_func = $_GET["on_success_js_func"]; //used by the choose_available_template.js

$path = str_replace("../", "", $path);//for security reasons

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

//get projects with logos
$layers_projects = CMSPresentationLayerHandler::getPresentationLayersProjectsFiles($user_global_variables_file_path, $user_beans_folder_path, false, false, -1, false, null, true);
$LayoutTypeProjectHandler = new LayoutTypeProjectHandler($UserAuthenticationHandler, $user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name);
$LayoutTypeProjectHandler->filterPresentationLayersProjectsByUserAndLayoutPermissions($layers_projects, $filter_by_layout);
//echo "<pre>";print_r($layers_projects);die();

//prepare presentation brokers
$presentation_brokers = array();

if ($layers_projects)
	foreach ($layers_projects as $bn => $layer_props) {
		$layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerBeanFolderName($user_beans_folder_path . $layer_props["bean_file_name"], $bn, $user_global_variables_file_path);
		$presentation_brokers[] = array($layer_bean_folder_name, $layer_props["bean_file_name"], $bn);
	}
//echo "<pre>";print_r($presentation_brokers);die();

//prepare project data
if ($_POST && trim($_POST["name"])) { 
	$project = trim($_POST["old_name"]) ? trim($_POST["old_name"]) : trim($_POST["name"]);
	$project_folder = trim($_POST["old_project_folder"]);
	
	$path = ($project_folder ? $project_folder . "/" : "") . $project;
	
	if ($_POST["is_existent_project"]) { //is rename project
		$is_rename_project = trim($_POST["project_folder"]) != trim($_POST["old_project_folder"]) || trim($_POST["name"]) != trim($_POST["old_name"]);
		
		if ($is_rename_project)
			$path = (trim($_POST["project_folder"]) ? trim($_POST["project_folder"]) . "/" : "") . trim($_POST["name"]); //prepare path with new project name, that was already renamed via ajax request.
	}
	else //is create new project
		$path = (trim($_POST["project_folder"]) ? trim($_POST["project_folder"]) . "/" : "") . trim($_POST["name"]); //prepare path with new project name, that was already created via ajax request.
}

$path = preg_replace("/[\/]+/", "/", $path); //remove duplicates /
$path = preg_replace("/^[\/]+/", "", $path); //remove start /
$path = preg_replace("/[\/]+$/", "", $path); //remove end /

$project_props = $layers_projects && $layers_projects[$bean_name] && $layers_projects[$bean_name]["projects"] && $layers_projects[$bean_name]["projects"][$path] ? $layers_projects[$bean_name]["projects"][$path] : null;
$is_existent_project = $project_props && $project_props["item_type"] != "project_folder";
//echo "<pre>path:$path\nis_existent_project:$is_existent_project";print_r(array_keys($layers_projects[$bean_name]["projects"]));print_r($project_props);die();
//echo"is_existent_project:$is_existent_project";die();

//get PEVC
$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $layers_projects[$bean_name]["bean_file_name"], $user_global_variables_file_path);
$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path); //$path already contains the new value if renamed happened before

$P = $PEVC->getPresentationLayer();

//check if presentation is linked to any db_brokers 
$layer_brokers_settings = WorkFlowBeansFileHandler::getLayerBrokersSettings($user_global_variables_file_path, $user_beans_folder_path, $P->getBrokers(), '$EVC->getBroker');
$db_brokers_exist = !empty($layer_brokers_settings["db_brokers"]);

//check if presentation is linked to any db_brokers
if ($db_brokers_exist) {
	//prepare brokers db drivers
	$db_drivers_props = WorkFlowBeansFileHandler::getLayerDBDrivers($user_global_variables_file_path, $user_beans_folder_path, $P, true);
	$db_drivers_names = array_keys($db_drivers_props);
	//echo "<pre>db_drivers_names:";print_r($db_drivers_names);die();
}

if ($is_existent_project) {
	prepareProjectPaths($path, $project_folder, $project); //is rename change the $project_folder and $project with correspondent values from $path
	
	$old_project_folder = $project_folder;
	$old_project = $project;
	
	$project_description = $project_props["description"];
	$project_image = $project_props["logo_url"];
	//echo "<pre>";print_r($project_props);die();
	
	$project_layout_type_data = $LayoutTypeProjectHandler->getLayoutFromProjectPath($project_props["path"]);
	$project_layout_type_id = $project_layout_type_data ? $project_layout_type_data["layout_type_id"] : null;
	
	//Note that when the code is here it's bc already created the project. The project is created through ajax via $add_project_url var.
	if ($_POST) {
		if (!trim($_POST["name"]))
			$error_message = "Project name cannot be empty";
		else {
			//echo "<pre>";print_r($_POST);print_r($_FILES);die();
			$is_previous_existent_project = $_POST["is_existent_project"];
			$is_previous_project_creation_with_errors = $_POST["is_previous_project_creation_with_errors"];
			$project = trim($_POST["name"]);
			$project_description = $_POST["description"];
			$project_folder = trim($_POST["project_folder"]);
			
			$project_folder = $project_folder == "." ? "" : $project_folder;
			$project_path = ($project_folder ? $project_folder . "/" : "") . $project;
			
			prepareProjectPaths($project_path, $project_folder, $project);
			
			$status = true;
			
			if ($is_rename_project) {
				$status = is_dir($project_props["path"]);
				
				if (!$status)
					$error_message = "Project could not be moved to the new folder. Maybe there is already a project with the same name in this new folder.";
			}
			
			if ($status) {
				//save project description
				$webroot_path = $PEVC->getWebrootPath();
				$file_path = $webroot_path . "humans.txt";
				$status = file_put_contents($file_path, $project_description) !== false;
				//echo "webroot_path:$webroot_path\n";die();
				
				//save project logo
				if ($status && $_FILES["image"]["name"]) {
					$dst_path = $project_props["logo_path"];
					$dst_path = $dst_path ? $dst_path : $webroot_path . "favicon.ico";
					
					if (move_uploaded_file($_FILES["image"]["tmp_name"], $dst_path))
						$project_image = $project_props["logo_path"] ? $project_props["logo_url"] : $project_props["url"] . "favicon.ico";
					else
						$status = false;
				}
				
				//if is create new project or if previous creation gave an error and user is trying to save project again.
				if ($status && (!$is_previous_existent_project || $is_previous_project_creation_with_errors)) {
					$project_db_driver = $_POST["project_db_driver"];
					$db_details = $_POST["db_details"];
					
					//create new DB
					if (is_numeric($project_db_driver) && intval($project_db_driver) === 1 && $db_details)
						$project_db_driver = createNewDB($EVC, $PEVC, $workflow_paths_id, $user_global_variables_file_path, $user_beans_folder_path, $user_global_settings_file_path, $webroot_cache_folder_path, $webroot_cache_folder_url, $css_and_js_optimizer_webroot_cache_folder_path, $deployments_temp_folder_path, $db_details, $error_message, $extra_message);
					
					//assign DB to project
					if ($project_db_driver && !is_numeric($project_db_driver)) {
						//assign project permissions to DB Driver
						if (!$LayoutTypeProjectHandler->createLayoutTypePermissionsForDBDriverFromProjectPath($project_props["path"], $project_db_driver))
							$error_message = "Could not assign DB Driver '$project_db_driver' to project.";
						
						//set default db driver in project config file
						if ($project_db_driver) {
							$pre_init_config_path = $PEVC->getConfigPath("pre_init_config");
							
							if (file_exists($pre_init_config_path)) {
								$contents = file_get_contents($pre_init_config_path);
								$replacement = '$default_db_driver = "' . $project_db_driver . '";';
								
								if (preg_match('/\$default_db_driver\s*=/', $contents))
									$contents = preg_replace('/\$default_db_driver\s*=\s*([^;]+);/', $replacement, $contents);
								else {
									$pos = strpos($contents, "?>");
									
									if ($pos !== false)
										$contents = substr($contents, 0, $pos) . $replacement . "\n" . substr($contents, $pos);
									else
										$contents .= "<?php\n" . $replacement . "\n?>";
								}
								
								if (file_put_contents($pre_init_config_path, $contents) === false)
									$error_message = "Could not save default DB Driver to project.";
							}
							else
								$error_message = "config/pre_init_config.php file does NOT exists! Something weird went wrong. Please fix this before you continue...";
						}
					}
						
					if ($error_message)
						$status = false;
				}
			}
		}
	}
	
	//if is not a new project, get default driver name
	if (!$_POST || $is_previous_existent_project) { 
		//SET USER GLOBAL VARIABLES
		$pre_init_config_path = $PEVC->getConfigPath("pre_init_config");
		$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $pre_init_config_path));
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		$default_db_driver = $GLOBALS["default_db_driver"];
		
		//ROLLBACK TO ORIGINAL GLOBAL VARIABLES
		$PHPVariablesFileHandler->endUserGlobalVariables();
	}
}
else {
	$project_folder = $path;
	$old_project_folder = $project_folder;
	
	if ($_POST) 
		$status = false;
	
	//check if presentation is linked to any db_brokers
	if ($db_brokers_exist) {
		//preparing db types
		$all_driver_labels = DB::getAllDriverLabelsByType();
		$available_db_types = array();
		//echo "<pre>all_driver_labels:";print_r($all_driver_labels);die();
		
		foreach ($all_driver_labels as $type => $label)
			$available_db_types[] = array("value" => $type, "label" => "$label DataBase");
		
		//preparing db extensions
		$drivers_extensions = DB::getAllExtensionsByType();
		$available_extensions_options = array();

		if ($data["db_type"] && is_array($drivers_extensions[ $data["db_type"] ]))
			foreach ($drivers_extensions[ $data["db_type"] ] as $idx => $enc)
				$available_extensions_options[] = array("value" => $enc, "label" => $enc . ($idx == 0 ? " - Default" : ""));

		if ($data["db_extension"] && (!$drivers_extensions[ $data["db_type"] ] || !in_array($data["db_extension"], $drivers_extensions[ $data["db_type"] ])))
			$available_extensions_options[] = array("value" => $data["db_extension"], "label" => $data["db_extension"] . " - DEPRECATED");

		//preparing db encodings
		$drivers_encodings = DB::getAllDBCharsetsByType();
		$available_encodings_options = array(array("value" => "", "label" => "-- Default --"));

		if ($data["db_type"] && is_array($drivers_encodings[ $data["db_type"] ]))
			foreach ($drivers_encodings[ $data["db_type"] ] as $enc => $label)
				$available_encodings_options[] = array("value" => $enc, "label" => $label);

		if ($data["db_encoding"] && (!$drivers_encodings[ $data["db_type"] ] || !array_key_exists($data["db_encoding"], $drivers_encodings[ $data["db_type"] ])))
			$available_encodings_options[] = array("value" => $data["db_encoding"], "label" => $data["db_encoding"] . " - DEPRECATED");

		//preparing ignore db options
		$drivers_ignore_connection_options = DB::getAllIgnoreConnectionOptionsByType();
		$drivers_ignore_connection_options_by_extension = DB::getAllIgnoreConnectionOptionsByExtensionAndType();
	}
}

function prepareProjectPaths($path, &$project_folder, &$project) {
	$path = preg_replace("/[\/]+/", "/", $path); //remove duplicates /
	$path = preg_replace("/^[\/]+/", "", $path); //remove start /
	$path = preg_replace("/[\/]+$/", "", $path); //remove end /
	
	$project_folder = $path ? dirname($path) : "";
	$project_folder = $project_folder == "." ? "" : $project_folder;
	$project_folder = preg_replace("/^[\/]+/", "", $project_folder); //remove start /
	$project_folder = preg_replace("/[\/]+$/", "", $project_folder); //remove end /
	
	$project = basename($path);
}

function createNewDB($EVC, $PEVC, $workflow_paths_id, $user_global_variables_file_path, $user_beans_folder_path, $user_global_settings_file_path, $webroot_cache_folder_path, $webroot_cache_folder_url, $css_and_js_optimizer_webroot_cache_folder_path, $deployments_temp_folder_path, $db_details, &$error_message, &$extra_message) {
	$db_details["db_name"] = trim($db_details["db_name"]);
	$db_details["host"] = trim($db_details["host"]);
	$project_db_driver = null;
	
	if ($db_details["db_name"]) {
		//create new DB
		$tasks_file_path = $workflow_paths_id["layer"];
		
		if ($tasks_file_path && file_exists($tasks_file_path)) {
			//get db broker
			$P = $PEVC->getPresentationLayer();
			$layer_brokers_settings = WorkFlowBeansFileHandler::getLayerBrokersSettings($user_global_variables_file_path, $user_beans_folder_path, $P->getBrokers());
			$db_broker = $layer_brokers_settings["db_brokers"][0];
			$db_broker_name = $db_broker[0];
			
			if ($db_broker_name) {
				//get db_driver task type
				$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
				$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
				$WorkFlowTaskHandler->setAllowedTaskFolders(array("layer/"));
				$WorkFlowTaskHandler->setAllowedTaskTags(array("dbdriver"));
				$WorkFlowTaskHandler->initWorkFlowTasks();
				$tasks_settings = $WorkFlowTaskHandler->getTasksByTag("dbdriver");
				$db_driver_task_type = $tasks_settings[0]["type"];
				
				if ($db_driver_task_type) {
					//get all layer tasks
					$WorkFlowTasksFileHandler = new WorkFlowTasksFileHandler($tasks_file_path);
					$WorkFlowTasksFileHandler->init();
					$tasks = $WorkFlowTasksFileHandler->getWorkflowData();
					
					if ($tasks["tasks"]) {
						$task_layer_tags = WorkFlowTasksFileHandler::getTaskLayerTags();
						
						//get all tasks ids and check if DB is already created
						$tasks_ids = array();
						$db_driver_task = null;
						
						foreach ($tasks["tasks"] as $task) {
							$tasks_ids[] = $task["task_id"];
							
							if ($task["tag"] == $task_layer_tags["dbdriver"]) {
								$task_properties = $task["properties"];
								
								if ($task_properties["type"] == $db_details["type"] && $task_properties["extension"] == $db_details["extension"] && $task_properties["host"] == $db_details["host"] && $task_properties["port"] == $db_details["port"] && $task_properties["db_name"] == $db_details["db_name"])
									$db_driver_task = $task;
							}
						}
						
						$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_file_path);
						
						//get new db driver task id
						if ($db_driver_task) {
							$project_db_driver = WorkFlowBeansConverter::getBrokerNameFromRawLabel($db_driver_task["label"]);
							
							$extra_message = "The user-defined DB Driver already exists with the name '" . $db_driver_task["label"] . "', so the systems used this DB Driver instead.";
						}
						//check if DB credentials are valid here, before recreate beans
						else if (!$WorkFlowDBHandler->isDBDriverSettingsValid($db_details, true)) {
							$error_message = "User-defined DB not created, because credentials are not valid. " . str_replace($db_driver_task["properties"]["db_password"], "***",$WorkFlowDBHandler->getError());
						}
						else {
							$new_db_driver_task_id = $db_details["db_name"];
							
							if (in_array($new_db_driver_task_id, $tasks_ids) && $db_details["host"])
								$new_db_driver_task_id = $db_details["host"] . "_" . $db_details["db_name"];
							
							while (in_array($new_db_driver_task_id, $tasks_ids))
								$new_db_driver_task_id .= "_" . rand(0, 1000);
							
							//prepare new db driver task
							$db_driver_task_props = $db_details;
							$db_driver_task_props["active"] = 1;
							
							$db_driver_task = array(
								"id" => $new_db_driver_task_id,
								"label" => $db_details["db_name"],
								"tag" => $task_layer_tags["dbdriver"],
								"type" => $db_driver_task_type,
								"properties" => $db_driver_task_props
							);
							
							//connect db driver task to db data task
							$db_task = null;
							
							foreach ($tasks["tasks"] as &$task) 
								if ($task["tag"] == $task_layer_tags["db"] && $task["properties"]["active"]) {
									$task_broker_name = WorkFlowBeansConverter::getBrokerNameFromRawLabel($task["label"]);
									
									if ($task_broker_name == $db_broker_name) {
										$db_task = $task;
										
										//connect db driver task to db data task
										$exits = isset($task["exits"]["layer_exit"][0]) ? $task["exits"]["layer_exit"] : array($task["exits"]["layer_exit"]);
										$exits[] = array(
											"task_id" => $db_driver_task["id"],
											
											//No need for the following ones: The Layer diagram will take care of this, if not defined!
											//"color" => "#31498f",
											//"overlay" => "Forward Arrow",
											//"type" => "Straight",
										);
										
										$task["exits"]["layer_exit"] = $exits;
										
										//prepare db driver task offsets
										$db_driver_task["width"] = $task["width"];
										$db_driver_task["height"] = $task["height"];
										$db_driver_task["offset_left"] = $task["offset_left"] + 10;
										$db_driver_task["offset_top"] = $task["offset_top"] + 170;
									
										//exit loop
										break;
									}
								}
							
							//unset task bc of the reference above in the foreach
							unset($task);
							
							//update layers diagram
							if ($db_task) {
								//add new db driver task to tasks
								$tasks["tasks"][ $db_driver_task["id"] ] = $db_driver_task;
								
								//save new tasks to file
								$save_status = WorkFlowTasksFileHandler::createTasksFile($tasks_file_path, $tasks);
								
								if ($save_status) {
									//prepare beans
									$WorkFlowBeansConverter = new WorkFlowBeansConverter($tasks_file_path, $user_beans_folder_path, $user_global_variables_file_path, $user_global_settings_file_path);
									$WorkFlowBeansConverter->init();
									$beans_status = $WorkFlowBeansConverter->recreateBean($db_driver_task["id"]) && $WorkFlowBeansConverter->recreateBean($db_task["id"]); //db_driver_task must be first
									
									if ($beans_status) {
										//flush cache
										FlushCacheHandler::flushCache($EVC, $webroot_cache_folder_path, $webroot_cache_folder_url, $workflow_paths_id, $user_global_variables_file_path, $user_beans_folder_path, $css_and_js_optimizer_webroot_cache_folder_path, $deployments_temp_folder_path);
										
										//set project_db_driver
										$project_db_driver = WorkFlowBeansConverter::getBrokerNameFromRawLabel($db_driver_task["label"]);
										
										//check if DB Driver properties are correct and try to create DB instance if not exists yet
										$valid = $WorkFlowDBHandler->isTaskDBDriverBeanValid($db_driver_task, true);
										//echo "<pre>valid:$valid";print_r($db_driver_task);die();
										
										if (!$valid)
											$error_message = "DB credentials are not valid. " . str_replace($db_driver_task["properties"]["db_password"], "***",$WorkFlowDBHandler->getError());
										else
											$extra_message = "Your new database was successfully created, but without any default tables. If you already have some modules installed in this framework and you want to include them in this database, you need to reinstall them for this new database, so that the system will create the corresponding tables. You should do this before installing any programs as they may use some modules.";
									}
									else
										$error_message = "Could not update beans file.";
								}
								else
									$error_message = "Could not save the new db driver task in the layers diagram file.";
							}
							else
								$error_message = "No db broker in the layers diagram.";
						}
					}
					else
						$error_message = "No db broker in the layers diagram.";
				}
				else
					$error_message = "No db driver type detected.";
			}
			else
				$error_message = "No db broker connected with the presentation layer.";
		}
		else
			$error_message = "Layer diagram file path does not exists. file path: '$tasks_file_path'.";
	}
	else
		$error_message = "DB name cannot be empty.";
	
	return $project_db_driver;
}
?>
