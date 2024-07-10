<?php
include_once get_lib("org.phpframework.workflow.WorkFlowTaskHandler");
include_once $EVC->getUtilPath("WorkFlowDataAccessHandler");
include_once $EVC->getUtilPath("WorkFlowDBHandler");
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");
include_once $EVC->getUtilPath("CMSPresentationUIAutomaticFilesHandler");
include_once $EVC->getUtilPath("LayoutTypeProjectHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$path = $_GET["path"];
$filter_by_layout = $_GET["filter_by_layout"];

$path = str_replace("../", "", $path);//for security reasons
$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons

if ($path) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
	
	if ($PEVC) {
		$P = $PEVC->getPresentationLayer();
		
		$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config")));
		$PHPVariablesFileHandler->startUserGlobalVariables();

		$layer_path = $P->getLayerPathSetting();
		$folder_path = $layer_path . $path;//it should be a folder.

		if (is_dir($folder_path)) {
			$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($folder_path, "layer", "access");
			
			//get available db drivers
			$db_drivers = WorkFlowBeansFileHandler::getLayerDBDrivers($user_global_variables_file_path, $user_beans_folder_path, $P, true);
			
			$LayoutTypeProjectHandler = new LayoutTypeProjectHandler($UserAuthenticationHandler, $user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name);
			$LayoutTypeProjectHandler->filterLayerBrokersDBDriversPropsFromLayoutName($db_drivers, $filter_by_layout); //filter db_drivers by $filter_by_layout
			
			if ($_POST["step_3"]) {
				$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
				
				//Preparing table alias
				$selected_tables_alias = json_decode($_POST["sta"], true);
				//echo "<pre>";print_r($selected_tables_alias);die();
				if ($selected_tables_alias) 
					foreach ($selected_tables_alias as $table_name => $table_alias)
						$selected_tables_alias[$table_name] = strtolower(str_replace(array("-", " "), "_", $table_alias));
				
				$statuses = json_decode($_POST["statuses"], true);
				$statuses = $statuses ? $statuses : array();
				//print_r($statuses);die();
			}
			else if ($_POST["step_2"]) {
				$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
				
				//PREPARING TABLES
				$db_driver = $_POST["db_driver"];
				$include_db_driver = $_POST["include_db_driver"];
				$db_layer = $_POST["db_layer"];
				$db_layer_file = $_POST["db_layer_file"];
				$type = $_POST["type"];
				$authenticated_template = $_POST["authenticated_template"];
				$non_authenticated_template = $_POST["non_authenticated_template"];
				$force_user_action = $_POST["force_user_action"];
				$selected_tables = $_POST["st"];
				$selected_tables_alias = $_POST["sta"];
				$overwrite = $_POST["overwrite"];
				$with_items_list_ui = $_POST["with_items_list_ui"];
				$with_view_item_ui = $_POST["with_view_item_ui"];
				$with_insert_item_form_ui = $_POST["with_insert_item_form_ui"];
				$with_update_item_form_ui = $_POST["with_update_item_form_ui"];
				$with_fks_ui = $_POST["with_fks_ui"];
				$active_brokers = $_POST["active_brokers"];
				$active_brokers_folder = $_POST["active_brokers_folder"];
				$users_perms = $_POST["users_perms"];
				$users_perms_folder = $_POST["users_perms_folder"];
				$list_and_edit_users = $_POST["list_and_edit_users"];
				//echo "<pre>";print_r($active_brokers);die();
				//echo "<pre>";print_r($users_perms);die();
				
				$selected_tables = is_array($selected_tables) ? $selected_tables : array();
				
				$WorkFlowDataAccessHandler = new WorkFlowDataAccessHandler();
				
				if ($type == "diagram") {//TRYING TO GET THE DB TABLES FROM THE TASK FLOW
					$tasks_file_path = WorkFlowTasksFileHandler::getDBDiagramTaskFilePath($workflow_paths_id, "db_diagram", $db_driver);
					$WorkFlowDataAccessHandler->setTasksFilePath($tasks_file_path);
					$tasks = $WorkFlowDataAccessHandler->getTasks();
				}
				else {//TRYING TO GET THE DB TABLES DIRECTLY FROM DB
					$db_driver_props = $db_drivers[$db_driver];
					$db_driver_bean_file_name = $db_driver_props[1];
					
					if ($db_driver_bean_file_name) {
						$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_file_path);
						$tasks = $WorkFlowDBHandler->getUpdateTaskDBDiagram($db_driver_bean_file_name, $db_driver);
						$WorkFlowDataAccessHandler->setTasks($tasks);
						$tasks = $WorkFlowDataAccessHandler->getTasks();
					}
				}
				//echo "<pre>";print_r($tasks);die();
				
				$tasks = $tasks["tasks"];
				$foreign_keys = $WorkFlowDataAccessHandler->getForeignKeys();
				//echo "<pre>";print_r($foreign_keys);die();
				
				$tables = $WorkFlowDataAccessHandler->getTasksAsTables();
				//echo "<pre>";print_r($tables);die();
				
				//Preparing table alias
				if ($selected_tables_alias) 
					foreach ($selected_tables_alias as $table_name => $table_alias)
						$selected_tables_alias[$table_name] = strtolower(str_replace(array("-", " "), "_", $table_alias));
				
				//Adding fks tables to $selected_tables if not yet present
				if ($with_fks_ui) {
					$t = count($selected_tables);
					for ($i = 0; $i < $t; $i++) {
						$table_name = $selected_tables[$i];
						$table = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
					
						if ($table) {
							foreach ($table as $attr_name => $attr_props) {
								$fks = $attr_props["fk"];
								if ($fks) {
									$t2 = count($fks);
									for ($j = 0; $j < $t2; $j++) {
										$fk_table = $fks[$j]["table"];
								
										if (!in_array($fk_table, $selected_tables)) {
											$selected_tables[] = $fk_table;
											$t++;
										}
									}
								}
							}
						}
					}
				}
				//echo "<pre>";print_r($selected_tables);die();
				
				//PREPARING BROKERS
				$brokers = $P->getBrokers();
				foreach ($brokers as $broker_name => $broker)
					if (!$active_brokers[$broker_name])
						unset($brokers[$broker_name]);
				
				$layer_brokers_settings = WorkFlowBeansFileHandler::getLayerBrokersSettings($user_global_variables_file_path, $user_beans_folder_path, $brokers, '$EVC->getBroker');
				//echo "<pre>";print_r($layer_brokers_settings);die();
				
				$presentation_brokers = array();
				$presentation_brokers[] = array(WorkFlowBeansFileHandler::getLayerNameFromBeanObject($bean_name, $P) . " (Self)", $bean_file_name, $bean_name);
				$presentation_brokers_obj = array("default" => '$EVC->getPresentationLayer()');
				
				$business_logic_brokers = $layer_brokers_settings["business_logic_brokers"];
				$business_logic_brokers_obj = $layer_brokers_settings["business_logic_brokers_obj"];
				
				$data_access_brokers = $layer_brokers_settings["data_access_brokers"];
				$data_access_brokers_obj = $layer_brokers_settings["data_access_brokers_obj"];
	
				$ibatis_brokers = $layer_brokers_settings["ibatis_brokers"];
				$ibatis_brokers_obj = $layer_brokers_settings["ibatis_brokers_obj"];
	
				$hibernate_brokers = $layer_brokers_settings["hibernate_brokers"];
				$hibernate_brokers_obj = $layer_brokers_settings["hibernate_brokers_obj"];
				
				$db_brokers = $layer_brokers_settings["db_brokers"];
				$db_brokers_obj = $layer_brokers_settings["db_brokers_obj"];
				
				//PREPARING TASKS
				$allowed_tasks = array();
				if ($business_logic_brokers)
					$allowed_tasks[] = "callbusinesslogic";
				
				if ($ibatis_brokers)
					$allowed_tasks[] = "callibatisquery";
				
				if ($hibernate_brokers) 
					$allowed_tasks[] = "callhibernatemethod";
				
				if ($db_brokers_obj || $ibatis_brokers) {
					$allowed_tasks[] = "getquerydata";
					$allowed_tasks[] = "setquerydata";
				}
				
				$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
				$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
				$WorkFlowTaskHandler->setAllowedTaskTags($allowed_tasks);
			}
			else if ($_POST["step_1"]) {
				$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
				
				$db_driver = $_POST["db_driver"];
				$include_db_driver = $_POST["include_db_driver"];
				$db_layer = $_POST["db_layer"];
				$db_layer_file = $_POST["db_layer_file"];
				$type = $_POST["type"];
				$authenticated_template = $_POST["authenticated_template"];
				$force_user_action = $_POST["force_user_action"];
				//echo "<pre>";print_r($_POST);die();
				
				//prepare brokers
				$brokers = $P->getBrokers();
				
				if ($db_driver) {
					if ($type == "diagram") {//TRYING TO GET THE DB TABLES FROM THE TASK FLOW
						$tasks_file_path = WorkFlowTasksFileHandler::getDBDiagramTaskFilePath($workflow_paths_id, "db_diagram", $db_driver);
						$WorkFlowDataAccessHandler = new WorkFlowDataAccessHandler();
						$WorkFlowDataAccessHandler->setTasksFilePath($tasks_file_path);
				
						$tasks = $WorkFlowDataAccessHandler->getTasks();
						$tables_name = array_keys($tasks["tasks"]);
						//print_r($tables_name);
					}
					else {//TRYING TO GET THE DB TABLES DIRECTLY FROM DB
						$db_driver_props = $db_drivers[$db_driver];
						$db_driver_bean_file_name = $db_driver_props[1];
						$db_driver_bean_name = $db_driver_props[2];
						
						if ($db_driver && $db_driver_bean_name && $db_driver_bean_file_name) {
							$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_file_path);
							$tables = $WorkFlowDBHandler->getDBTables($db_driver_bean_file_name, $db_driver_bean_name);
							$tables_name = array();
							
							if ($tables)
								foreach ($tables as $table)
									$tables_name[] = $table["name"];
						}
					}
				}
				
				//Prepare available user types and activities
				$user_module_installed_and_enabled = CMSPresentationUIAutomaticFilesHandler::isUserModuleInstalled($PEVC);
				
				if ($user_module_installed_and_enabled) {
					//set available user types and activities
					$available_user_types = CMSPresentationUIAutomaticFilesHandler::getAvailableUserTypes($PEVC);
					
					$available_activities = array();
					$all_activities = CMSPresentationUIAutomaticFilesHandler::getAvailableActivities($PEVC);
					$activities_count = 0;
					
					if ($all_activities) 
						foreach ($all_activities as $activity_id => $activity_name)
							switch ($activity_id) {
								case UserUtil::ACCESS_ACTIVITY_ID:
								case UserUtil::WRITE_ACTIVITY_ID:
								case UserUtil::DELETE_ACTIVITY_ID:
									$available_activities[$activity_id] = $activity_name;
									$activities_count++;
									break;
							}
					
					if ($activities_count < 3) { //must be 3 permissions
						CMSPresentationUIAutomaticFilesHandler::reinsertReservedActivities($PEVC);
						$available_activities = null;
					}
				}
				
				//get available templates
				$available_templates = CMSPresentationLayerHandler::getAvailableTemplatesList($PEVC, "." . $P->getPresentationFileExtension());
				$available_templates = array_keys($available_templates);
				
				//prepare broker_path_to_filter
				if ($filter_by_layout) {
					$layer_folder_name = WorkFlowBeansFileHandler::getLayerObjFolderName($P);
					$broker_path_to_filter = substr($filter_by_layout, strlen($layer_folder_name) + 1) . "/";
				}
				else if ($path)
					$broker_path_to_filter = $P->getSelectedPresentationId() . "/";
			}
			else {
				//get default db driver
				$default_db_driver = $GLOBALS["default_db_driver"];
				
				//prepare selected db driver
				if ($db_drivers) {
					$selected_db_driver = $db_drivers[$default_db_driver] ? $default_db_driver : key($db_drivers);
					$selected_db_driver_props = $db_drivers[$selected_db_driver];
					
					if ($selected_db_driver_props) {
						$db_layer = $selected_db_driver_props[2];
						$db_layer_file = $selected_db_driver_props[1];
					}
					
					$include_db_driver = $selected_db_driver != $default_db_driver;
				}
				
				//get available templates
				$available_templates = CMSPresentationLayerHandler::getAvailableTemplatesList($PEVC, "." . $P->getPresentationFileExtension());
				$available_templates = array_keys($available_templates);
			}
		}
		
		$PHPVariablesFileHandler->endUserGlobalVariables();
	}
}
?>
