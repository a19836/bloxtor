<?php
include_once $EVC->getUtilPath("WorkFlowDataAccessHandler");
include_once $EVC->getUtilPath("WorkFlowDBHandler");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");
include_once get_lib("org.phpframework.util.MyArray");
include_once $EVC->getUtilPath("FlushCacheHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;

$PHPVariablesFileHandler = new PHPVariablesFileHandler($user_global_variables_file_path);
$PHPVariablesFileHandler->startUserGlobalVariables();

$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);

if ($obj && is_a($obj, "HibernateDataAccessLayer")) {	
	$db_broker = isset($_POST["db_broker"]) ? $_POST["db_broker"] : null;
	$db_driver = isset($_POST["db_driver"]) ? $_POST["db_driver"] : null;
	$type = isset($_POST["type"]) ? $_POST["type"] : null;
	$selected_tables = isset($_POST["st"]) ? $_POST["st"] : null;
	$with_maps = isset($_POST["with_maps"]) && ($_POST["with_maps"] == "true" || $_POST["with_maps"] == "1");
	$rel_type = isset($_POST["rel_type"]) ? $_POST["rel_type"] : null;
	
	$selected_tables = $selected_tables ? $selected_tables : array();
	
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

			if (!empty($table) && isset($table["name"])) {
				$attrs = $obj->getBroker($db_broker)->getFunction("listTableFields", $table["name"], array("db_driver" => $db_driver));
				$fks = $obj->getBroker($db_broker)->getFunction("listForeignKeys", $table["name"], array("db_driver" => $db_driver));
	
				$tables_data[ $table["name"] ] = array($attrs, $fks, $table);
			}
		}
		
		$tasks = WorkFlowDBHandler::getUpdateTaskDBDiagramFromTablesData($tables_data);
		$WorkFlowDataAccessHandler->setTasks($tasks);
	}
	
	$results = array();
	
	$t = count($selected_tables);
	for ($i = 0; $i < $t; $i++) {
		$table_name = $selected_tables[$i];
		
		if ($rel_type == "relationships") {
			$arr = $WorkFlowDataAccessHandler->getHibernateObjectArrayFromDBTaskFlow($table_name, false, $with_maps);
			$relationships = isset($arr["class"][0]["childs"]["relationships"][0]["childs"]) ? $arr["class"][0]["childs"]["relationships"][0]["childs"] : null;
		}
		else {
			$arr = $WorkFlowDataAccessHandler->getQueryObjectsArrayFromDBTaskFlow($table_name, $with_maps);
			$relationships = isset($arr["queries"][0]["childs"]) ? $arr["queries"][0]["childs"] : null;
		}
		
		if ($relationships) {
			$relationships = MyXML::complexArrayToBasicArray($relationships, array("convert_attributes_to_childs" => true));
			MyArray::arrKeysToLowerCase($relationships, true);
			//print_r($relationships);die();
			
			$results[$table_name] = $relationships;
		}
	}
	
	//delete cache bc of the previously cached hibernate rules
	FlushCacheHandler::flushCache($EVC, $webroot_cache_folder_path, $webroot_cache_folder_url, $workflow_paths_id, $user_global_variables_file_path, $user_beans_folder_path, $css_and_js_optimizer_webroot_cache_folder_path, $deployments_temp_folder_path); //flush cache
}

$PHPVariablesFileHandler->endUserGlobalVariables();
?>
