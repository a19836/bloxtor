<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once $EVC->getUtilPath("WorkFlowDBHandler");
include_once $EVC->getUtilPath("WorkFlowDataAccessHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$layer_bean_folder_name = isset($_GET["layer_bean_folder_name"]) ? $_GET["layer_bean_folder_name"] : null;
$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$type = isset($_GET["type"]) ? $_GET["type"] : null;
$item_type = isset($_GET["item_type"]) ? $_GET["item_type"] : null;
$table = isset($_GET["table"]) ? str_replace("/", "", $_GET["table"]) : null;

$layer_object_id = LAYER_PATH . "$layer_bean_folder_name/$bean_name";
$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_object_id, "layer", "access");

/*$UserCacheHandler = $PHPFrameWork->getObject("UserCacheHandler");
$UserCacheHandler->config(false, true);

$cached_file_name = "admin_menu_layers_" . md5($bean_file_name . "_" . $bean_name . "_" . $table);

if ($UserCacheHandler->isValid($cached_file_name)) {
	$sub_files = $UserCacheHandler->read($cached_file_name);
}

if (empty($layers)) {*/
	$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_file_path);
	$tasks_file_path = WorkFlowTasksFileHandler::getDBDiagramTaskFilePath($workflow_paths_id, "db_diagram", $bean_name);
	
	$db_data = array(
		"properties" => array(
		    "bean_file_name" => $bean_file_name,
		    "bean_name" => $bean_name,
		)
	);
	
	//get views, procedures, functions, events or triggers
	if (in_array($item_type, array("db_views", "db_procedures", "db_functions", "db_events", "db_triggers"))) { 
		$DBDriver = $WorkFlowDBHandler->getBeanObject($bean_file_name, $bean_name);
		
		switch($item_type) {
			case "db_views": $db_objects = $DBDriver->listViews(); break;
			case "db_procedures": $db_objects = $DBDriver->listProcedures(); break;
			case "db_functions": $db_objects = $DBDriver->listFunctions(); break;
			case "db_events": $db_objects = $DBDriver->listEvents(); break;
			case "db_triggers": $db_objects = $DBDriver->listTriggers(); break;
		}
		
		if (!empty($db_objects)) {
			$t = count($db_objects);
			for ($i = 0; $i < $t; $i++) {
				$db_object_name = isset($db_objects[$i]["name"]) ? $db_objects[$i]["name"] : null;
				
				$db_data[ $db_object_name ] = array("properties" => array(
					"bean_file_name" => $bean_file_name,
			 		"bean_name" => $bean_name,
					"item_type" => substr($item_type, 0, -1), //singular type: remove 's' at the end
					"item_class" => "",
					"name" => $db_object_name,
				));
			}
		}
	}
	else if ($table) { //get table attribtues
		$db_data["properties"]["item_type"] = "table";
		
		if ($type == "diagram") {//TRYING TO GET THE DB TABLES FROM THE TASK FLOW
			$WorkFlowDataAccessHandler = new WorkFlowDataAccessHandler();
			$WorkFlowDataAccessHandler->setTasksFilePath($tasks_file_path);
			
			$tables = $WorkFlowDataAccessHandler->getTasksAsTables();
			$attrs = WorkFlowDBHandler::getTableFromTables($tables, $table);
			//$attrs = $tables[$table]; //DEPRECATED, bc $table or $tables can have schema, so we will use WorkFlowDBHandler::getTableFromTables instead 
		}
		else {
			$attrs = $WorkFlowDBHandler->getDBTableAttributes($bean_file_name, $bean_name, $table);
			
			//add available types for correspondent driver
			$DBDriver = $WorkFlowDBHandler->getBeanObject($bean_file_name, $bean_name);
			
			$db_data["properties"]["db_data"] = array(
				"column_types" => $DBDriver->getDBColumnTypes(),
				"column_simple_types" => $DBDriver->getDBColumnSimpleTypes(),
				"column_mandatory_length_types" => $DBDriver->getDBColumnMandatoryLengthTypes(),
				"column_types_ignored_props" => $DBDriver->getDBColumnTypesIgnoredProps(),
				"column_types_hidden_props" => $DBDriver->getDBColumnTypesHiddenProps(),
			);
		}
		
		if (is_array($attrs))
			foreach ($attrs as $name => $attr) {
				$attr_menu = $attr;
				
				$attr["bean_file_name"] = $bean_file_name;
				$attr["bean_name"] = $bean_name;
				$attr["item_id"] = 
AdminMenuHandler::getItemId("$bean_file_name/$bean_name/$table/$name");
				$attr["item_type"] = "attribute";
				$attr["item_menu"] = $attr_menu;
				$attr["table"] = $table;
				
				if (!empty($attr["primary_key"]))
					$attr["item_class"] = "primary_key";
				
				$db_data[$name] = array("properties" => $attr);
			}
	}
	else { //get tables
		$db_data["properties"]["item_type"] = "dbdriver";
		
		//get reserved_db_table_names
		$reserved_db_table_names_res = $UserAuthenticationHandler->getAllReservedDBTableNames();
		$reserved_db_table_names = array();
		
		if ($reserved_db_table_names_res)
			foreach ($reserved_db_table_names_res as $item)
				if (!empty($item["name"]))
					$reserved_db_table_names[] = $item["name"];
		
		//TRYING TO GET THE DB TABLES FROM THE TASK FLOW
		if ($type == "diagram") {
			$WorkFlowDataAccessHandler = new WorkFlowDataAccessHandler();
			$WorkFlowDataAccessHandler->setTasksFilePath($tasks_file_path);
			
			$tasks = $WorkFlowDataAccessHandler->getTasks();
			
			if (!empty($tasks["tasks"]))
				foreach ($tasks["tasks"] as $table_name => $task) 
					$db_data[ $table_name ] = array("properties" => array(
						"bean_file_name" => $bean_file_name,
			    		"bean_name" => $bean_name,
						"item_type" => "table",
						"item_class" => in_array($table_name, $reserved_db_table_names) ? "module_table" : "", //add module_table class if this table belongs to a module, so in the DB layer tree, these tables only show if the advanced settings is active.
						"name" => $table_name,
					));
		}
		else { //get db tables from db server
			$tables = $WorkFlowDBHandler->getDBTables($bean_file_name, $bean_name);
			
			$t = count($tables);
			for ($i = 0; $i < $t; $i++) {
				$table_name = isset($tables[$i]["name"]) ? $tables[$i]["name"] : null;
				
				$db_data[ $table_name ] = array("properties" => array(
					"bean_file_name" => $bean_file_name,
		    		"bean_name" => $bean_name,
					"item_type" => "table",
					"item_class" => in_array($table_name, $reserved_db_table_names) ? "module_table" : "", //add module_table class if this table belongs to a module, so in the DB layer tree, these tables only show if the advanced settings is active.
					"name" => $table_name,
				));
			}
		}
	}
		
	$error = $WorkFlowDBHandler->getError();
	
	if (!empty($error)) {
		$db_data = false;
		echo $error;
	}
	else {
		//$UserCacheHandler->write($cached_file_name, $db_data);
	}
//}

//print_r($db_data);die();
?>
