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

include_once get_lib("org.phpframework.util.web.html.pagination.PaginationLayout");
include_once $EVC->getUtilPath("WorkFlowDataAccessHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$layer_bean_folder_name = isset($_GET["layer_bean_folder_name"]) ? $_GET["layer_bean_folder_name"] : null;
$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$table = isset($_GET["table"]) ? $_GET["table"] : null;
$db_type = !empty($_GET["db_type"]) ? $_GET["db_type"] : "diagram";
$popup = isset($_GET["popup"]) ? $_GET["popup"] : null;

if ($bean_name && $table) {
	$layer_object_id = LAYER_PATH . "$layer_bean_folder_name/$bean_name";
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_object_id, "layer", "access");
	
	$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_file_path);
	$DBDriver = $WorkFlowDBHandler->getBeanObject($bean_file_name, $bean_name);
	
	$existent_tables = $DBDriver->listTables();
	$table_exists = $DBDriver->isTableInNamesList($existent_tables, $table);
	
	if ($table_exists) {
		$table_fields = $DBDriver->listTableFields($table);
		
		//if table exists and has attributes gets indexes
		if ($table_fields) {
			if (!empty($_POST["delete"])) {
				$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
				
				$constraint_name = isset($_POST["constraint_name"]) ? $_POST["constraint_name"] : null;
				
				if (!$constraint_name)
					$error_message = "Undefined constraint name to delete.";
				else {
					$constraint_type = isset($_POST["constraint_type"]) ? strtolower($_POST["constraint_type"]) : null;
					
					if ($constraint_type == "primary key")
						$sql = $DBDriver->getDropTablePrimaryKeysStatement($table);
					else if ($constraint_type == "foreign key")
						$sql = $DBDriver->getDropTableForeignConstraintStatement($table, $constraint_name);
					else
						$sql = $DBDriver->getDropTableIndexStatement($table, $constraint_name);
					
					//echo $sql;die();
					$status = $DBDriver->setSQL($sql);
					
					if ($status)
						$status_message = "Index with constraint '$constraint_name' deleted successfully!";
					else
						$error_message = "Error: Index with constraint '$constraint_name' could NOT be deleted.";
				}
			}
			
			$sql = $DBDriver->getTableIndexesStatement($table);
			$results = $DBDriver->getSQL($sql);
			//echo "<pre>";print_r($results);die();
			$indexes_fields = !empty($results[0]) && is_array($results[0]) ? array_keys($results[0]) : array();
		}
	}
}
?>
