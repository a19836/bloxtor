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

include_once get_lib("org.phpframework.db.DBFileImporter");
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
	
	$table_attrs = $obj->listTableFields($table);
	$table_attrs = array_keys($table_attrs);
	
	if (!empty($_POST)) {
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
		
		$file = isset($_FILES["file"]) ? $_FILES["file"] : null;
		$file_type = isset($_POST["file_type"]) ? $_POST["file_type"] : null;
		$rows_delimiter = isset($_POST["rows_delimiter"]) ? $_POST["rows_delimiter"] : null;
		$columns_delimiter = isset($_POST["columns_delimiter"]) ? $_POST["columns_delimiter"] : null;
		$enclosed_by = isset($_POST["enclosed_by"]) ? $_POST["enclosed_by"] : null;
		$ignore_rows_number = isset($_POST["ignore_rows_number"]) ? trim($_POST["ignore_rows_number"]) : null;
		$insert_ignore = isset($_POST["insert_ignore"]) ? trim($_POST["insert_ignore"]) : null;
		$update_existent = isset($_POST["update_existent"]) ? trim($_POST["update_existent"]) : null;
		$force = isset($_POST["force"]) ? trim($_POST["force"]) : null;
		$columns_attributes = isset($_POST["columns_attributes"]) ? $_POST["columns_attributes"] : null;
		$uploaded_file_path = isset($file["name"]) ? TMP_PATH . $file["name"] : null;
		
		if (!empty($file["tmp_name"])) {
			if (move_uploaded_file($file["tmp_name"], $uploaded_file_path)) {
				if ($file_type == "csv") {
					$rows_delimiter = "\n";
					$columns_delimiter = ",";
					$enclosed_by = '"';
				}
				
				$DBFileImporter = new DBFileImporter($obj);
				$DBFileImporter->setOptions(array(
					"rows_delimiter" => $rows_delimiter ? $rows_delimiter : "\n",
					"columns_delimiter" => $columns_delimiter ? $columns_delimiter : "\t",
					"enclosed_by" => $enclosed_by ? $enclosed_by : '"',
					"ignore_rows_number" => is_numeric($columns_delimiter) ? $ignore_rows_number : 1,
					"insert_ignore" => $insert_ignore,
					"update_existent" => $update_existent,
				));
				
				if ($DBFileImporter->importFile($uploaded_file_path, $table, $columns_attributes, $force)) 
					$status_message = "File dumped successfully to DB!";
				else {
					$errors = $DBFileImporter->getErrors();
					$error_message = "Error: File not imported!";
				}
			}
			
			if (file_exists($uploaded_file_path))
				unlink($uploaded_file_path);
		}
		else
			$error_message = "Please upload a file to be imported...";
	}
	else {
		$ignore_rows_number = 1;
		$columns_attributes = $table_attrs;
	}
}
else 
	$error_message = "Error: Bean object is not a DBDriver!";

$PHPVariablesFileHandler->endUserGlobalVariables();
?>
