<?php
include_once $EVC->getUtilPath("WorkFlowDataAccessHandler");
include_once $EVC->getUtilPath("WorkFlowDBHandler");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");
include_once $EVC->getUtilPath("FlushCacheHandler");
include_once $EVC->getUtilPath("LayoutTypeProjectHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$path = $_GET["path"];
$hbn_obj_id = $_GET["obj"];//this is only used to create automatically a specific object.
$filter_by_layout = $_GET["filter_by_layout"];

$path = str_replace("../", "", $path);//for security reasons
$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons

$PHPVariablesFileHandler = new PHPVariablesFileHandler($user_global_variables_file_path);
$PHPVariablesFileHandler->startUserGlobalVariables();

$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);

$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);

if ($obj && is_a($obj, "DataAccessLayer")) {	
	$layer_path = $obj->getLayerPathSetting();
	$folder_path = $layer_path . $path;//it can be a folder or a xml file
	
	$LayoutTypeProjectHandler = new LayoutTypeProjectHandler($UserAuthenticationHandler, $user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name);
	
	if ($_POST["step_2"]) {
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
		UserAuthenticationHandler::checkUsersMaxNum($UserAuthenticationHandler);
		UserAuthenticationHandler::checkActionsMaxNum($UserAuthenticationHandler);
		
		//echo "<pre>";print_r($_POST);die();
		$db_broker = $_POST["db_broker"];
		$db_driver = $_POST["db_driver"];
		$type = $_POST["type"];
		$selected_tables = $_POST["st"];
		$selected_tables_alias = $_POST["sta"];
		$overwrite = $_POST["overwrite"];
		$with_maps = $_POST["with_maps"] == "true" || $_POST["with_maps"] == "1";
		$json = $_POST["json"];
		
		$selected_tables = is_array($selected_tables) ? $selected_tables : array();
		
		if ($path && !is_file($folder_path) && !is_dir($folder_path))
			mkdir($folder_path, 0755, true);
		
		$statuses = array();
		
		if ($path && file_exists($folder_path)) {
			$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($folder_path, "layer", "access");
			$UserAuthenticationHandler->incrementUsedActionsTotal();
			
			//check if $folder_path belongs to filter_by_layout and if not, add it.
			$LayoutTypeProjectHandler->createLayoutTypePermissionsForFilePathAndLayoutTypeName($filter_by_layout, $folder_path);
			
			if (is_dir($folder_path))
				$folder_path .= substr($folder_path, strlen($folder_path) - 1) == "/" ? "" : "/";
			
			$WorkFlowDataAccessHandler = new WorkFlowDataAccessHandler();
			
			if ($type == "diagram") {//TRYING TO GET THE DB TABLES FROM THE TASK FLOW
				$tasks_file_path = WorkFlowTasksFileHandler::getDBDiagramTaskFilePath($workflow_paths_id, "db_diagram", $db_driver);
				$WorkFlowDataAccessHandler->setTasksFilePath($tasks_file_path);
			}
			else {//TRYING TO GET THE DB TABLES DIRECTLY FROM DB
				$tables = $obj->getBroker($db_broker)->getFunction("listTables", null, array("db_driver" => $db_driver));
				$tables_data = array();
				$t = count($tables);
				for ($i = 0; $i < $t; $i++) {
					$table = $tables[$i];
		
					if (!empty($table)) {
						$attrs = $obj->getBroker($db_broker)->getFunction("listTableFields", $table["name"], array("db_driver" => $db_driver));
						$fks = $obj->getBroker($db_broker)->getFunction("listForeignKeys", $table["name"], array("db_driver" => $db_driver));
			
						$tables_data[ $table["name"] ] = array($attrs, $fks, $table);
					}
				}
				
				$tasks = WorkFlowDBHandler::getUpdateTaskDBDiagramFromTablesData($tables_data);
				$WorkFlowDataAccessHandler->setTasks($tasks);
			}
			
			if ($selected_tables_alias) {
				$tasks = $WorkFlowDataAccessHandler->getTasks($tasks);
				
				foreach ($selected_tables_alias as $table_name => $table_alias) {
					$table_alias = trim($table_alias);
					$task_table_name = WorkFlowDBHandler::getTableTaskRealNameFromTasks($tasks["tasks"], $table_name);
					
					if ($table_alias && $tasks["tasks"][$task_table_name])
						$tasks["tasks"][$task_table_name]["alias"] = trim($table_alias);
				}
				
				$WorkFlowDataAccessHandler->setTasks($tasks);
			}
			
			$t = count($selected_tables);
			for ($i = 0; $i < $t; $i++) {
				$table_name = $selected_tables[$i];
				$table_alias = $selected_tables_alias[$table_name];
				
				if (is_file($folder_path))
					$file_path = $folder_path;
				else
					$file_path = empty($hbn_obj_id) ? ($table_alias ? "${folder_path}$table_alias.xml" : "${folder_path}$table_name.xml") : "${folder_path}$hbn_obj_id.xml";
				
				if ($overwrite && file_exists($file_path))
					unlink($file_path);
				else if (!$overwrite)
					while (file_exists($file_path)) {
						$path_info = pathinfo($file_path);
						$file_path = $path_info["dirname"] . "/" . $path_info["filename"] . "_" . rand(0, 100) . "." . $path_info["extension"];
					}
				
				if ($obj->getType() == "hibernate")
					$status = $WorkFlowDataAccessHandler->createHibernateObjectFromDBTaskFlow($table_name, $file_path, $overwrite, $hbn_obj_id, $with_maps);
				else
					$status = $WorkFlowDataAccessHandler->createTableQueriesFromDBTaskFlow($table_name, $file_path, $overwrite, $with_maps);
				
				$statuses[] = array(substr($file_path, strlen($layer_path)), $table_name, $status);
			}
			
			//delete cache bc of the previously cached ibatis rules
			FlushCacheHandler::flushCache($EVC, $webroot_cache_folder_path, $webroot_cache_folder_url, $workflow_paths_id, $user_global_variables_file_path, $user_beans_folder_path, $css_and_js_optimizer_webroot_cache_folder_path, $deployments_temp_folder_path); //flush cache
		}
		
		if ($json) {
			echo json_encode($statuses);
			die();
		}
	}
	else if ($_POST["step_1"]) {
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

		$db_broker = $_POST["db_broker"];
		$db_driver = $_POST["db_driver"];
		$type = $_POST["type"];
		
		if ($db_driver) {
			if ($type == "diagram") {//TRYING TO GET THE DB TABLES FROM THE TASK FLOW
				$tasks_file_path = WorkFlowTasksFileHandler::getDBDiagramTaskFilePath($workflow_paths_id, "db_diagram", $db_driver);
				$WorkFlowDataAccessHandler = new WorkFlowDataAccessHandler();
				$WorkFlowDataAccessHandler->setTasksFilePath($tasks_file_path);
				
				$tasks = $WorkFlowDataAccessHandler->getTasks();
				$tables_name = $tasks["tasks"] ? array_keys($tasks["tasks"]) : array();
				//print_r($tables_name);
			}
			else {//TRYING TO GET THE DB TABLES DIRECTLY FROM DB
				if ($db_broker && $db_driver) {
					$tables = $obj->getBroker($db_broker)->getFunction("listTables", null, array("db_driver" => $db_driver));
					$tables_name = array();
					if ($tables)
						foreach ($tables as $table) {
							$tables_name[] = $table["name"];
						}
				}
			}
		}
	}
	else {
		$brokers = $obj->getBrokers();
		$db_drivers = array();
		
		$selected_db_broker = $selected_db_driver = null;
		
		if ($brokers) {
			foreach ($brokers as $broker_name => $broker) {
				$db_drivers[$broker_name] = WorkFlowBeansFileHandler::getBrokersDBDrivers($user_global_variables_file_path, $user_beans_folder_path, array($broker_name => $broker), true);
				
				$LayoutTypeProjectHandler->filterLayerBrokersDBDriversPropsFromLayoutName($db_drivers[$broker_name], $filter_by_layout); //filter db_drivers by $filter_by_layout
				
				if (empty($selected_db_driver)) {
					$selected_db_broker = $broker_name;
					$selected_db_driver = $db_drivers[$broker_name][0];
				}
			}
		}
	}
}

$PHPVariablesFileHandler->endUserGlobalVariables();
?>
