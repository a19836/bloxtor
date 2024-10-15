<?php
include_once get_lib("org.phpframework.workflow.WorkFlowTaskHandler");
include_once $EVC->getUtilPath("WorkFlowDataAccessHandler");
include_once $EVC->getUtilPath("WorkFlowDBHandler");
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");
include_once $EVC->getUtilPath("CMSPresentationUIAutomaticFilesHandler");
include_once $EVC->getUtilPath("LayoutTypeProjectHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$filter_by_layout = isset($_GET["filter_by_layout"]) ? $_GET["filter_by_layout"] : null;

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
			
			if (!empty($_POST["step_3"])) {
				$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
				
				//Preparing table alias
				$selected_tables_alias = isset($_POST["sta"]) ? json_decode($_POST["sta"], true) : null;
				//echo "<pre>";print_r($selected_tables_alias);die();
				if ($selected_tables_alias) 
					foreach ($selected_tables_alias as $table_name => $table_alias)
						$selected_tables_alias[$table_name] = strtolower(str_replace(array("-", " "), "_", $table_alias));
				
				$statuses = isset($_POST["statuses"]) ? json_decode($_POST["statuses"], true) : null;
				$statuses = $statuses ? $statuses : array();
				//print_r($statuses);die();
			}
			else if (!empty($_POST["step_2"])) {
				$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
				
				//PREPARING TABLES
				$db_driver = isset($_POST["db_driver"]) ? $_POST["db_driver"] : null;
				$include_db_driver = isset($_POST["include_db_driver"]) ? $_POST["include_db_driver"] : null;
				$db_layer = isset($_POST["db_layer"]) ? $_POST["db_layer"] : null;
				$db_layer_file = isset($_POST["db_layer_file"]) ? $_POST["db_layer_file"] : null;
				$type = isset($_POST["type"]) ? $_POST["type"] : null;
				$authenticated_template = isset($_POST["authenticated_template"]) ? $_POST["authenticated_template"] : null;
				$non_authenticated_template = isset($_POST["non_authenticated_template"]) ? $_POST["non_authenticated_template"] : null;
				$force_user_action = isset($_POST["force_user_action"]) ? $_POST["force_user_action"] : null;
				$selected_tables = isset($_POST["st"]) ? $_POST["st"] : null;
				$selected_tables_alias = isset($_POST["sta"]) ? $_POST["sta"] : null;
				$overwrite = isset($_POST["overwrite"]) ? $_POST["overwrite"] : null;
				$with_items_list_ui = isset($_POST["with_items_list_ui"]) ? $_POST["with_items_list_ui"] : null;
				$with_view_item_ui = isset($_POST["with_view_item_ui"]) ? $_POST["with_view_item_ui"] : null;
				$with_insert_item_form_ui = isset($_POST["with_insert_item_form_ui"]) ? $_POST["with_insert_item_form_ui"] : null;
				$with_update_item_form_ui = isset($_POST["with_update_item_form_ui"]) ? $_POST["with_update_item_form_ui"] : null;
				$with_fks_ui = isset($_POST["with_fks_ui"]) ? $_POST["with_fks_ui"] : null;
				$active_brokers = isset($_POST["active_brokers"]) ? $_POST["active_brokers"] : null;
				$active_brokers_folder = isset($_POST["active_brokers_folder"]) ? $_POST["active_brokers_folder"] : null;
				$users_perms = isset($_POST["users_perms"]) ? $_POST["users_perms"] : null;
				$users_perms_folder = isset($_POST["users_perms_folder"]) ? $_POST["users_perms_folder"] : null;
				$list_and_edit_users = isset($_POST["list_and_edit_users"]) ? $_POST["list_and_edit_users"] : null;
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
					$db_driver_props = isset($db_drivers[$db_driver]) ? $db_drivers[$db_driver] : null;
					$db_driver_bean_file_name = isset($db_driver_props[1]) ? $db_driver_props[1] : null;
					
					if ($db_driver_bean_file_name) {
						$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_file_path);
						$tasks = $WorkFlowDBHandler->getUpdateTaskDBDiagram($db_driver_bean_file_name, $db_driver);
						$WorkFlowDataAccessHandler->setTasks($tasks);
						$tasks = $WorkFlowDataAccessHandler->getTasks();
					}
				}
				//echo "<pre>";print_r($tasks);die();
				
				$tasks = isset($tasks["tasks"]) ? $tasks["tasks"] : null;
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
								$fks = isset($attr_props["fk"]) ? $attr_props["fk"] : null;
								
								if ($fks) {
									$t2 = count($fks);
									for ($j = 0; $j < $t2; $j++) {
										$fk_table = isset($fks[$j]["table"]) ? $fks[$j]["table"] : null;
								
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
					if (empty($active_brokers[$broker_name]))
						unset($brokers[$broker_name]);
				
				$layer_brokers_settings = WorkFlowBeansFileHandler::getLayerBrokersSettings($user_global_variables_file_path, $user_beans_folder_path, $brokers, '$EVC->getBroker');
				//echo "<pre>";print_r($layer_brokers_settings);die();
				
				$presentation_brokers = array();
				$presentation_brokers[] = array(WorkFlowBeansFileHandler::getLayerNameFromBeanObject($bean_name, $P) . " (Self)", $bean_file_name, $bean_name);
				$presentation_brokers_obj = array("default" => '$EVC->getPresentationLayer()');
				
				$business_logic_brokers = isset($layer_brokers_settings["business_logic_brokers"]) ? $layer_brokers_settings["business_logic_brokers"] : null;
				$business_logic_brokers_obj = isset($layer_brokers_settings["business_logic_brokers_obj"]) ? $layer_brokers_settings["business_logic_brokers_obj"] : null;
				
				$data_access_brokers = isset($layer_brokers_settings["data_access_brokers"]) ? $layer_brokers_settings["data_access_brokers"] : null;
				$data_access_brokers_obj = isset($layer_brokers_settings["data_access_brokers_obj"]) ? $layer_brokers_settings["data_access_brokers_obj"] : null;
	
				$ibatis_brokers = isset($layer_brokers_settings["ibatis_brokers"]) ? $layer_brokers_settings["ibatis_brokers"] : null;
				$ibatis_brokers_obj = isset($layer_brokers_settings["ibatis_brokers_obj"]) ? $layer_brokers_settings["ibatis_brokers_obj"] : null;
	
				$hibernate_brokers = isset($layer_brokers_settings["hibernate_brokers"]) ? $layer_brokers_settings["hibernate_brokers"] : null;
				$hibernate_brokers_obj = isset($layer_brokers_settings["hibernate_brokers_obj"]) ? $layer_brokers_settings["hibernate_brokers_obj"] : null;
				
				$db_brokers = isset($layer_brokers_settings["db_brokers"]) ? $layer_brokers_settings["db_brokers"] : null;
				$db_brokers_obj = isset($layer_brokers_settings["db_brokers_obj"]) ? $layer_brokers_settings["db_brokers_obj"] : null;
				
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
			else if (!empty($_POST["step_1"])) {
				$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
				
				$db_driver = isset($_POST["db_driver"]) ? $_POST["db_driver"] : null;
				$include_db_driver = isset($_POST["include_db_driver"]) ? $_POST["include_db_driver"] : null;
				$db_layer = isset($_POST["db_layer"]) ? $_POST["db_layer"] : null;
				$db_layer_file = isset($_POST["db_layer_file"]) ? $_POST["db_layer_file"] : null;
				$type = isset($_POST["type"]) ? $_POST["type"] : null;
				$authenticated_template = isset($_POST["authenticated_template"]) ? $_POST["authenticated_template"] : null;
				$force_user_action = isset($_POST["force_user_action"]) ? $_POST["force_user_action"] : null;
				//echo "<pre>";print_r($_POST);die();
				
				//prepare brokers
				$brokers = $P->getBrokers();
				
				if ($db_driver) {
					if ($type == "diagram") {//TRYING TO GET THE DB TABLES FROM THE TASK FLOW
						$tasks_file_path = WorkFlowTasksFileHandler::getDBDiagramTaskFilePath($workflow_paths_id, "db_diagram", $db_driver);
						$WorkFlowDataAccessHandler = new WorkFlowDataAccessHandler();
						$WorkFlowDataAccessHandler->setTasksFilePath($tasks_file_path);
				
						$tasks = $WorkFlowDataAccessHandler->getTasks();
						$tables_name = isset($tasks["tasks"]) ? array_keys($tasks["tasks"]) : array();
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
									if (isset($table["name"]))
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
				$broker_path_to_filter = null;
				
				if ($filter_by_layout) {
					$layer_folder_name = WorkFlowBeansFileHandler::getLayerObjFolderName($P);
					$broker_path_to_filter = substr($filter_by_layout, strlen($layer_folder_name) + 1) . "/";
				}
				else if ($path)
					$broker_path_to_filter = $P->getSelectedPresentationId() . "/";
			}
			else {
				//get default db driver
				$default_db_driver = isset($GLOBALS["default_db_driver"]) ? $GLOBALS["default_db_driver"] : null;
				$db_layer = $db_layer_file = $include_db_driver = null;
				
				//prepare selected db driver
				if ($db_drivers) {
					$selected_db_driver = !empty($db_drivers[$default_db_driver]) ? $default_db_driver : key($db_drivers);
					$selected_db_driver_props = $db_drivers[$selected_db_driver];
					
					if ($selected_db_driver_props) {
						$db_layer = isset($selected_db_driver_props[2]) ? $selected_db_driver_props[2] : null;
						$db_layer_file = isset($selected_db_driver_props[1]) ? $selected_db_driver_props[1] : null;
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
