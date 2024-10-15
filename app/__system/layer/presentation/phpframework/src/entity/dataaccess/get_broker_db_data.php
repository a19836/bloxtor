<?php
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");
include_once $EVC->getUtilPath("WorkFlowDBHandler");
include_once $EVC->getUtilPath("WorkFlowDataAccessHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

if (!empty($_POST)) {
	$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
	$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
	$global_default_db_driver_broker = isset($_GET["global_default_db_driver_broker"]) ? $_GET["global_default_db_driver_broker"] : null; //in case of form module in the presentation layers
	
	$db_broker = isset($_POST["db_broker"]) ? $_POST["db_broker"] : null; //if bean name is a presentation layer, the db_broker becomes a dal_broker
	$db_driver = isset($_POST["db_driver"]) ? $_POST["db_driver"] : null;
	$type = isset($_POST["type"]) ? $_POST["type"] : null;
	$db_table = isset($_POST["db_table"]) ? $_POST["db_table"] : null;
	$detailed_info = isset($_POST["detailed_info"]) ? $_POST["detailed_info"] : null;
	
	$user_global_variables_files_path = array($user_global_variables_file_path);
	$PHPVariablesFileHandler = new PHPVariablesFileHandler($user_global_variables_files_path);
	$PHPVariablesFileHandler->startUserGlobalVariables();

	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_files_path);
	$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
	
	if ($obj && (is_a($obj, "Layer") || is_a($obj, "DB"))) {
		$tables = $db_driver_props = $items = $fks = null;
		
		if ($type == "diagram") {
			$tasks_file_path = WorkFlowTasksFileHandler::getDBDiagramTaskFilePath($workflow_paths_id, "db_diagram", $db_driver);
			$WorkFlowDataAccessHandler = new WorkFlowDataAccessHandler();
			$WorkFlowDataAccessHandler->setTasksFilePath($tasks_file_path);
			
			$tables = $WorkFlowDataAccessHandler->getTasksAsTables();
		}
		else if (!$db_broker || 
			(!is_a($obj, "DataAccessLayer") && !is_a($obj, "DBLayer") && !is_a($obj, "DB") && !$obj->getBroker($db_broker, true))
		) {
			$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_files_path);
			$db_driver_props = WorkFlowBeansFileHandler::getLayerDBDriverProps($user_global_variables_files_path, $user_beans_folder_path, $obj, $db_driver);
		}
		
		$db_options = array("db_driver" => $db_driver);
		
		if (!is_a($obj, "DataAccessLayer") && !is_a($obj, "DBLayer") && !is_a($obj, "DB")) { //in case of form module
			$db_driver_db_broker = WorkFlowBeansFileHandler::getLayerLocalDBBrokerNameForChildBrokerDBDriver($user_global_variables_file_path, $user_beans_folder_path, $obj, $db_driver);
			$db_options["db_broker"] = $db_driver_db_broker ? $db_driver_db_broker : $global_default_db_driver_broker;
		}
		
		if ($db_table) {
			if ($type == "diagram") {
				$attrs = WorkFlowDBHandler::getTableFromTables($tables, $db_table);
				//$attrs = $tables[$table]; //DEPRECATED, bc $table or $tables can have schema, so we will use WorkFlowDBHandler::getTableFromTables instead 
				
				if (!$detailed_info)
					$items = $attrs ? array_keys($attrs) : null;
				else
					$items = $attrs; //This already contains the foreign keys!
			}
			else {
				if (is_a($obj, "DB"))
					$items = $obj->listTableFields($db_table);
				else if (is_a($obj, "DBLayer"))
					$items = $obj->getFunction("listTableFields", $db_table, $db_options);
				else if ($db_broker && $obj->getBroker($db_broker, true))
					$items = $obj->getBroker($db_broker)->getFunction("listTableFields", $db_table, $db_options);
				else if ($db_driver_props)
					$items = $WorkFlowDBHandler->getDBTableAttributes($db_driver_props[1], $db_driver_props[2], $db_table);
				
				if (!$detailed_info) 
					$items = is_array($items) ? array_keys($items) : array();
				else { //prepare table foreign keys
					if (is_a($obj, "DB"))
						$fks = $obj->listForeignKeys($db_table);
					else if (is_a($obj, "DBLayer"))
						$fks = $obj->getFunction("listForeignKeys", $db_table, $db_options);
					else if ($db_broker && $obj->getBroker($db_broker, true))
						$fks = $obj->getBroker($db_broker)->getFunction("listForeignKeys", $db_table, $db_options);
					else if ($db_driver_props) {
						$DBDriver = $WorkFlowDBHandler->getBeanObject($db_driver_props[1], $db_driver_props[2]);
						
						if ($DBDriver)
							$fks = $DBDriver->listForeignKeys($db_table);
					}
					
					if (is_array($fks))
						foreach ($fks as $fk)
							if (!empty($fk)) {
								$child_column = isset($fk["child_column"]) ? $fk["child_column"] : null;
								
								if ($child_column && $items[$child_column]) {
									if (empty($items[$child_column]["fk"]))
										$items[$child_column]["fk"] = array();
									
									$items[$child_column]["fk"][] = array(
										"attribute" => isset($fk["parent_column"]) ? $fk["parent_column"] : null,
										"table" => isset($fk["parent_table"]) ? $fk["parent_table"] : null
									);
								}
							}
				}
			}
		}
		else {
			if ($type == "diagram")
				$items = array_keys($tables);
			else {
				if (is_a($obj, "DB"))
					$tables = $obj->listTables();
				else if (is_a($obj, "DBLayer"))
					$tables = $obj->getFunction("listTables", null, $db_options);
				else if ($db_broker && $obj->getBroker($db_broker, true))
					$tables = $obj->getBroker($db_broker)->getFunction("listTables", null, $db_options);
				else if ($db_driver_props)
					$tables = $WorkFlowDBHandler->getDBTables($db_driver_props[1], $db_driver_props[2]);
				
				$items = array();
				if ($tables)
					foreach ($tables as $table) 
						$items[] = isset($table["name"]) ? $table["name"] : null;
			}
		}
	}

	$PHPVariablesFileHandler->endUserGlobalVariables();
}
?>
