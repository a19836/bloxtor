<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once $EVC->getUtilPath("WorkFlowQueryHandler");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");
include_once get_lib("org.phpframework.workflow.WorkFlowTaskHandler");

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
			try {
				$data = $obj->getData($sql);
				//echo "<pre>";print_r($data);die();
			
				//set header
				$doc_name = $doc_name ? $doc_name : "{$table}_export";
				$content_type = $export_type == "xls" ? "application/vnd.ms-excel" : ($export_type == "csv" ? "text/csv" : "text/plain");
				header("Content-Type: $content_type");
				header('Content-Disposition: attachment; filename="' . $doc_name . '.' . $export_type . '"');
				
				//set output
				$str = "";
				
				if ($data && is_array($data)) {
					$columns = isset($data["fields"]) ? $data["fields"] : null;
					$columns_length = count($columns);
					$results = isset($data["result"]) ? $data["result"] : null;
					
					$rows_delimiter = "\n";
					$columns_delimiter = "\t";
					$enclosed_by = "";
					
					if ($export_type == "csv") {
						$columns_delimiter = ",";
						$enclosed_by = '"';
						
						$str .= "sep=$columns_delimiter$rows_delimiter"; //Alguns programas, como o Microsoft Excel 2010, requerem ainda um indicador "sep=" na primeira linha do arquivo, apontando o caráter de separação.
					}
					
					//prepare columns
					for ($i = 0; $i < $columns_length; $i++)
						$str .= ($i > 0 ? $columns_delimiter : "") . $enclosed_by . addcslashes($columns[$i]->name, $columns_delimiter . $enclosed_by . "\\") . $enclosed_by;
					
					//prepare rows
					if ($str && is_array($results)) {
						$str .= $rows_delimiter;
						
						foreach ($results as $row)
							if (is_array($row)) {
								for ($i = 0; $i < $columns_length; $i++)
									$str .= ($i > 0 ? $columns_delimiter : "") . $enclosed_by . addcslashes($row[ $columns[$i]->name ], $columns_delimiter . $enclosed_by . "\\") . $enclosed_by;
								
								$str .= $rows_delimiter;
							}
					}
				}
				
				echo $str;
				die();
			}
			catch(Exception $e) {
				throw $e;
			}
		}
	}
	
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
