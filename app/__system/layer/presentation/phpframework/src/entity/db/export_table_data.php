<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

include_once get_lib("org.phpframework.workflow.WorkFlowTaskHandler");
include_once get_lib("org.phpframework.db.DBFileExporter");
include_once $EVC->getUtilPath("WorkFlowQueryHandler");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$layer_bean_folder_name = isset($_GET["layer_bean_folder_name"]) ? $_GET["layer_bean_folder_name"] : null;
$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$type = isset($_GET["type"]) ? $_GET["type"] : null;
$table = isset($_GET["table"]) ? str_replace("/", "", $_GET["table"]) : null;

$PHPVariablesFileHandler = new PHPVariablesFileHandler($user_global_variables_file_path);
$PHPVariablesFileHandler->startUserGlobalVariables();

$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);

$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);

if ($obj && is_a($obj, "DB")) {
	$layer_object_id = LAYER_PATH . "$layer_bean_folder_name/$bean_name";
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_object_id, "layer", "access");
	
	$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
	$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
	$WorkFlowTaskHandler->setAllowedTaskTypes(array("query"));
	
	$sql = "select * from $table;";
	
	if (!empty($_POST)) {
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
		
		$sql = isset($_POST["sql"]) ? $_POST["sql"] : null;
		$export_type = isset($_POST["export_type"]) ? $_POST["export_type"] : null;
		$doc_name = isset($_POST["doc_name"]) ? $_POST["doc_name"] : null;
		
		if (!$sql) 
			$error_message = "Please write a select sql statement.";
		else {
			$doc_name = $doc_name ? $doc_name : "{$table}_export";
			
			$DBFileExporter = new DBFileExporter($obj);
			$DBFileExporter->setOptions(array( //all the following options are optional:
				"export_type" => $export_type, //default is txt 
			));
			$status = true;
			
			try {
				if (!$DBFileExporter->exportFile($sql, $doc_name)) 
					$status = false;
			}
			catch (Exception $e) {
				$status = false;
			}
			
			if ($status) 
					$status_message = "File dumped successfully from DB!";
			else {
				$errors = $DBFileExporter->getErrors();
				$error_message = "Error: File not exported!";
				
				if ($errors)
					$error_message .= '<br/><br/><div style="text-align:left;">
						<label style="font-weight:bold;">Errors:</label>
						<ul>
							<li>' . implode('</li><li>', array_map(fn($v) => nl2br($v, false), $errors)) . '</li>
						</ul>
					</div>';
			}
		}
	}
	
	//because we are calling the view/edit_query in the view/export_table_data.php, we need to have this vars initialized
	$db_driver_borker_name = WorkFlowBeansConverter::getBrokerNameFromRawLabel($bean_name);
	$db_drivers = array($layer_bean_folder_name => array($db_driver_borker_name));
	
	$rel_type = "select";
	$selected_db_broker = $layer_bean_folder_name;
	$selected_db_driver = $db_driver_borker_name;
	$selected_type = "db";
	
	$selected_tables = $obj->listTables();
	$selected_tables_name = array();
	if ($selected_tables)
		foreach ($selected_tables as $selected_table)
			$selected_tables_name[] = isset($selected_table["name"]) ? $selected_table["name"] : null;
	
	$selected_table_exists = $obj->isTableInNamesList($selected_tables_name, $table);
	$selected_table = $selected_table_exists ? $table : ($selected_tables_name ? $selected_tables_name[0] : null);
	$selected_table_attrs = $obj->listTableFields($selected_table);
	$selected_table_attrs = array_keys($selected_table_attrs);
}

$PHPVariablesFileHandler->endUserGlobalVariables();
?>
