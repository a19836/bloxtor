<?php
include_once $EVC->getUtilPath("SequentialLogicalActivityResourceCreator");
include_once $EVC->getUtilPath("FlushCacheHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$path = $_GET["path"];
$filter_by_layout = $_GET["filter_by_layout"];

$path = str_replace("../", "", $path);//for security reasons
$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons

$status = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $path) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
	
	if ($PEVC) {
		$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config")));
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		$P = $PEVC->getPresentationLayer();
		$layer_path = $P->getLayerPathSetting();
		$selected_project_id = $P->getSelectedPresentationId();
		
		$file_path = $layer_path . $path;
		
		if (file_exists($file_path)) {
			$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($file_path, "layer", "access");
			UserAuthenticationHandler::checkUsersMaxNum($UserAuthenticationHandler);
			
			$action_type = $_POST["action_type"];
			$resource_name = $_POST["resource_name"];
			$resource_data = $_POST["resource_data"];
			$db_broker = $_POST["db_broker"];
			$db_driver = $_POST["db_driver"];
			$db_type = $_POST["db_type"];
			$db_table = $_POST["db_table"];
			$db_table_alias = $_POST["db_table_alias"];
			$no_cache = $_POST["no_cache"];
			$permissions = $_POST["permissions"];
			
			$folder_path = $PEVC->getUtilsPath() . "resource";
			
			if (!is_dir($folder_path))
				mkdir($folder_path, 0755, true);
			
			if (is_dir($folder_path)) {
				$selected_db_driver = $db_driver ? $db_driver : $GLOBALS["default_db_driver"];
				$selected_db_table = $db_table;
				$selected_db_table_alias = $db_table_alias;
				
				//if get_all_options but there is no resource_data, stays with default table and table_alias
				if ($action_type == "get_all_options" && $resource_data) {
					if (array_key_exists("table", $resource_data) && $resource_data["table"]) {
						$selected_db_table = $resource_data["table"];
						$selected_db_table_alias = $resource_data["table_alias"];
					}
					else if (is_array($resource_data[0]) && $resource_data[0]["table"]) {
						$selected_db_table = $resource_data[0]["table"];
						$selected_db_table_alias = $resource_data[0]["table_alias"];
					}
				}
				
				if ($selected_db_table) {
					//$action_type = "get_all"; //only for testing
					
					$SequentialLogicalActivityResourceCreator = new SequentialLogicalActivityResourceCreator($EVC, $PEVC, $UserAuthenticationHandler, $workflow_paths_id, $webroot_cache_folder_path, $webroot_cache_folder_url, $user_global_variables_file_path, $user_beans_folder_path, $project_url_prefix, $filter_by_layout, $bean_name, $bean_file_name, $path, $db_broker, $selected_db_driver, $db_type, $selected_db_table, $selected_db_table_alias, $no_cache);
					
					//create util method if does not exist
					$action_file_method_exists = $SequentialLogicalActivityResourceCreator->createUtilMethod($action_type, $resource_data, $error_message);
					$flush_cache = $SequentialLogicalActivityResourceCreator->isFlushCache();
					
					//start resource action
					if (!$error_message && $action_file_method_exists) {
						$status = true;
						$actions = $SequentialLogicalActivityResourceCreator->getSLAResourceActions($action_type, $resource_name, $resource_data, $permissions);
					}
					
					//DEPRECATED - Do not flush cache here, bc this file is called asynchronously and multiple times, and if we clena the cache everytime, the system needs to search all the systems files for the the existent business logic services and data access rules for this action. Note that the SequentialLogicalActivityResourceCreator caches this results, so we should NOT delete the cache here, otherwise we are overloading the system and make it more slow.
					//delete cache bc of the previously cached business logic services
					//if ($flush_cache)
					//	FlushCacheHandler::flushCache($EVC, $webroot_cache_folder_path, $webroot_cache_folder_url, $workflow_paths_id, $user_global_variables_file_path, $user_beans_folder_path, $css_and_js_optimizer_webroot_cache_folder_path, $deployments_temp_folder_path); //flush cache
				}
				else {
					launch_exception(new Exception("No db table selected!"));
					die();
				}
			}
			else {
				launch_exception(new Exception("Resource folder not created!"));
				die();
			}
		}
		else {
			launch_exception(new Exception("File Not Found: " . $path));
			die();
		}
		
		$PHPVariablesFileHandler->endUserGlobalVariables();
	}
	else {
		launch_exception(new Exception("PEVC doesn't exists!"));
		die();
	}
}
else if (!$path) {
	launch_exception(new Exception("Undefined path!"));
	die();
}

?>
