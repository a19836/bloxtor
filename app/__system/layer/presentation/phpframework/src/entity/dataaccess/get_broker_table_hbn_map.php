<?php
include_once get_lib("org.phpframework.util.xml.MyXML");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");
include_once $EVC->getUtilPath("WorkFlowDataAccessHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

if ($_POST) {
	$bean_name = $_GET["bean_name"];
	$bean_file_name = $_GET["bean_file_name"];

	$db_broker = $_POST["db_broker"];
	$db_driver = $_POST["db_driver"];
	$type = $_POST["type"];
	$db_table = $_POST["db_table"];
	$map_type = $_POST["map_type"];

	$PHPVariablesFileHandler = new PHPVariablesFileHandler($user_global_variables_file_path);
	$PHPVariablesFileHandler->startUserGlobalVariables();

	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	
	$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
	
	if ($obj && is_a($obj, "DataAccessLayer") && $db_table) {
		if ($type == "diagram") {
			$tasks_file_path = WorkFlowTasksFileHandler::getDBDiagramTaskFilePath($workflow_paths_id, "db_diagram", $db_driver);
			
			$WorkFlowDataAccessHandler = new WorkFlowDataAccessHandler();
			$WorkFlowDataAccessHandler->setTasksFilePath($tasks_file_path);
			$tasks = $WorkFlowDataAccessHandler->getTasks();
			
			$table_attr_names = $tasks["tasks"][$db_table]["properties"]["table_attr_names"];
			$table_attr_types = $tasks["tasks"][$db_table]["properties"]["table_attr_types"];
		}
		else {
			$fields = $obj->getBroker($db_broker)->getFunction("listTableFields", $db_table, array("db_driver" => $db_driver));
		
			if ($fields) {
				$table_attr_names = array();
				$table_attr_types = array();
		
				foreach ($fields as $field) {
					$table_attr_names[] = $field["name"];
					$table_attr_types[] = $field["type"];
				}
			}
		}
		
		if ($table_attr_names && $table_attr_types) {
			if ($map_type == "parameter") 
				$xml = WorkFlowDataAccessHandler::getTableParameterMap($table_attr_names, $table_attr_types);
			else 
				$xml = WorkFlowDataAccessHandler::getTableResultMap($table_attr_names, $table_attr_types);
			
			if ($xml) {
				$MyXML = new MyXML("<main_node>$xml</main_node>");
				$arr = $MyXML->toArray();
				$new_arr = $MyXML->complexArrayToBasicArray($arr);

				$items = $new_arr["main_node"];
				//print_r($items);
			}
		}
	}

	$PHPVariablesFileHandler->endUserGlobalVariables();
}
?>
