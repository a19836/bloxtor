<?php
include_once $EVC->getUtilPath("WorkFlowDataAccessHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$layer_bean_folder_name = $_GET["layer_bean_folder_name"];
$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$table = $_GET["table"];
$db_type = $_GET["db_type"] ? $_GET["db_type"] : "diagram";
$action = $_GET["action"];
$popup = $_GET["popup"];

if ($bean_name && $table) {
	$layer_object_id = LAYER_PATH . "$layer_bean_folder_name/$bean_name";
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_object_id, "layer", "access");
	
	$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_file_path);
	$DBDriver = $WorkFlowDBHandler->getBeanObject($bean_file_name, $bean_name);
	
	$existent_tables = $DBDriver->listTables();
	$table_exists = $DBDriver->isTableInNamesList($existent_tables, $table);
	
	if ($table_exists) {
		$table_fields = $DBDriver->listTableFields($table);
		
		//if table exists and has attributes gets results
		if ($table_fields) {
			//prepare tables and fields according with db type
			$WorkFlowDataAccessHandler = new WorkFlowDataAccessHandler();
			$exists = false;
			
			if ($db_type == "diagram") {//TRYING TO GET THE DB TABLES FROM THE TASK FLOW
				$tasks_file_path = WorkFlowTasksFileHandler::getDBDiagramTaskFilePath($workflow_paths_id, "db_diagram", $bean_name);
				$WorkFlowDataAccessHandler->setTasksFilePath($tasks_file_path);
				
				$tasks_tables = $WorkFlowDataAccessHandler->getTasksAsTables();
				$task_table_name = $DBDriver->getTableInNamesList(array_keys($tasks_tables), $table);
				
				$exists = $task_table_name && $tasks_tables[$task_table_name];
			}
			
			//prepare tables and fields according with db type if doesn't exists in diagram or if db_type is "db"
			if (!$exists) {
				$fks = $DBDriver->listForeignKeys($table);
				$tables_data = array(
					$table => array($table_fields, $fks)
				);
				
				$t = count($fks);
				for ($i = 0; $i < $t; $i++) {
					$fk_table = $fks[$i]["parent_table"];
					$attrs = $DBDriver->listTableFields($fk_table);
					$tables_data[$fk_table] = array($attrs, null);
				}
				
				$tasks = WorkFlowDBHandler::getUpdateTaskDBDiagramFromTablesData($tables_data);
				$WorkFlowDataAccessHandler->setTasks($tasks);
				$tasks_tables = $WorkFlowDataAccessHandler->getTasksAsTables();
				$task_table_name = $DBDriver->getTableInNamesList(array_keys($tasks_tables), $table);
			}
			
			$tasks_tables = $WorkFlowDataAccessHandler->getTasksAsTables();
			$task_table_name = $DBDriver->getTableInNamesList(array_keys($tasks_tables), $table);
			
			//update table_fields according with db type
			if ($task_table_name && $tasks_tables[$task_table_name])
				$table_fields = $tasks_tables[$task_table_name];
			//echo "<pre>";print_r($table_fields);die();
			
			//prepare pks
			$pks = array();
			foreach ($table_fields as $field_name => $field)
				if ($field["primary_key"]) 
					$pks[] = $field_name;
			
			//prepare conditions
			$conditions = $_GET["conditions"];
			if ($conditions)
				foreach ($conditions as $field_name => $field_value)
					if (!$table_fields[$field_name])
						unset($conditions[$field_name]);
			
			//prepare results
			if ($conditions) {
				$results = $DBDriver->findObjects($table, null, $conditions);
				$results = $results[0];
				//echo "<pre>";print_r($results);die();
			}
			
			//prepare table_fields_types
			$table_fields_types = array();
			$numeric_types = $DBDriver->getDBColumnNumericTypes();
			$date_types = $DBDriver->getDBColumnDateTypes();
			$text_types = $DBDriver->getDBColumnTextTypes();
			$blob_types = $DBDriver->getDBColumnBlobTypes();
			$boolean_types = $DBDriver->getDBColumnBooleanTypes();
			
			foreach ($table_fields as $field_name => $field) {
				$field_type = $field["type"];
				$options = array();
				
				//prepare options if apply
				if ($field["fk"] && $field["fk"][0]) {
					$fk = WorkFlowDataAccessHandler::getTableAttributeFKTable($field["fk"], $tasks_tables);
					$fk_table = $fk["table"];
					$fk_attribute = $fk["attribute"];
					
					$fk_count = $DBDriver->countObjects($fk_table);
					
					if ($fk_count < 1000) { //for performance issues only allow this feature if not more than x records in the DB.
						$fk_table_attributes = $tasks_tables[$fk_table];
						$title_attr = WorkFlowDataAccessHandler::getTableAttrTitle($fk_table_attributes, $fk_table); //if there is no name, title or label, sets $fk_attribute
						$title_attr = $title_attr ? $title_attr : $fk_attribute; //set $title_attr to $fk_attr if not exist. In this case the getAllOptions will simply return the a list with key/value pair like: 'primary key/primary key'.
						
						$fk_results = $DBDriver->findObjects($fk_table, array($fk_attribute, $title_attr), null);
						
						if ($fk_results) {
							if ($field["null"])
								$options[""] = "";
							
							foreach ($fk_results as $fk_result)
								$options[ $fk_result[$fk_attribute] ] = ($title_attr != $fk_attribute ? $fk_result[$fk_attribute] . " - " : "") . $fk_result[$title_attr];
						}
					}
				}
				
				//set html input type
				if ($options) {
					$table_fields_types[$field_name] = array(
						"type" => "select", 
						"options" => $options
					);
				}
				else if (in_array($field_type, $boolean_types) || (($field_type == "smallint" || $field_type == "tinyint") && $field["length"] == 1))
					$table_fields_types[$field_name] = "checkbox";
				else if (in_array($field_type, $numeric_types))
					$table_fields_types[$field_name] = "number";
				else if (in_array($field_type, $date_types)) {
					if ($field_type == "date")
						$table_fields_types[$field_name] = "date";
					else if ($field_type == "datetime" || $field_type == "timestamp")
						$table_fields_types[$field_name] = "datetime";
					else if ($field_type == "time")
						$table_fields_types[$field_name] = "time";
					else
						$table_fields_types[$field_name] = "text";
				}
				else if (in_array($field_type, $text_types) && preg_match("/text/i", $field_type))
					$table_fields_types[$field_name] = "textarea";
				else if (in_array($field_type, $blob_types) && preg_match("/blob/i", $field_type))
					$table_fields_types[$field_name] = "file";
				else
					$table_fields_types[$field_name] = "text";
			}
		}
	}
}
?>
