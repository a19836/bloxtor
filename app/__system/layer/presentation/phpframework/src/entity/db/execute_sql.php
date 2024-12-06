<?php
include_once $EVC->getUtilPath("WorkFlowDBHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$layer_bean_folder_name = isset($_GET["layer_bean_folder_name"]) ? $_GET["layer_bean_folder_name"] : null;
$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$item_type = isset($_GET["item_type"]) ? $_GET["item_type"] : null;
$table = isset($_GET["table"]) ? $_GET["table"] : null;
$object = isset($_GET["object"]) ? $_GET["object"] : null;
$popup = isset($_GET["popup"]) ? $_GET["popup"] : null;
$sql = isset($_GET["sql"]) ? $_GET["sql"] : null;

if ($bean_name) {
	$layer_object_id = LAYER_PATH . "$layer_bean_folder_name/$bean_name";
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_object_id, "layer", "access");
	
	$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_file_path);
	
	if (!empty($_POST)) {
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
		
		$sql = isset($_POST["sql"]) ? $_POST["sql"] : null;
		
		if ($sql) {
			$DBDriver = $WorkFlowDBHandler->getBeanObject($bean_file_name, $bean_name);
			
			$sql_aux = DB::removeSQLComments($sql);
			$data = $DBDriver->convertSQLToObject($sql_aux);
			$query_type = $data && isset($data["type"]) ? $data["type"] : null;
			$is_select_sql = $query_type == "select";
			$options = $is_select_sql || $query_type == "insert" || $query_type == "update" || $query_type == "delete" ? array("remove_comments" => true) : null;
			
			try {
				if ($is_select_sql)
					$results = $DBDriver->getData($sql, $options);
				else
					$results = $DBDriver->setData($sql, $options);
			}
			catch(Exception $e) {
				$exception_message = isset($e->problem) ? $e->problem : null;
			}
		}
	}
	else if ($sql) { //split sql in multiple lines
		$DBDriver = $WorkFlowDBHandler->getBeanObject($bean_file_name, $bean_name);
		$sqls = $DBDriver->splitSQL($sql);
		$sql = "";
		
		if ($sqls)
			foreach ($sqls as $statement)
				$sql .= preg_replace("/;$/", "", trim($statement)) . ";\n"; //Do not remove the space before the ; because if we have this sql "DELIMITER ;", it will convert it to "DELIMITER;" which will not be recognized.
	}
	else if (in_array($item_type, array("db_view", "db_procedure", "db_function", "db_event", "db_trigger")) && $object) {
		$DBDriver = $WorkFlowDBHandler->getBeanObject($bean_file_name, $bean_name);
		
		switch($item_type) {
			case "db_view": 
				$drop_sql = $DBDriver->getDropViewStatement($object);
				$show_sql = $DBDriver->getShowCreateViewStatement($object);
				break;
			case "db_procedure": 
				$drop_sql = $DBDriver->getDropProcedureStatement($object); 
				$show_sql = $DBDriver->getShowCreateProcedureStatement($object);
				break;
			case "db_function": 
				$drop_sql = $DBDriver->getDropFunctionStatement($object); 
				$show_sql = $DBDriver->getShowCreateFunctionStatement($object);
				break;
			case "db_event": 
				$drop_sql = $DBDriver->getDropEventStatement($object); 
				$show_sql = $DBDriver->getShowCreateEventStatement($object);
				break;
			case "db_trigger": 
				$drop_sql = $DBDriver->getDropTriggerStatement($object); 
				$show_sql = $DBDriver->getShowCreateTriggerStatement($object);
				break;
		}
		
		if ($drop_sql)
			$sql = "-- drop " . str_replace("_", " ", $item_type) . "\n" . $drop_sql;
		
		if ($show_sql) {
			try {
				$results = $DBDriver->getSQL($show_sql);
				
				if ($results && is_array($results[0]))
					foreach ($results[0] as $column_key => $column_value)
						if (preg_match("/^(Create View|Create Procedure|Create Function|Create Event|Create Trigger|SQL Original Statement)$/i", $column_key)) {
							$sql .= "\n\n-- create " . str_replace("_", " ", $item_type) . "\n" . $column_value;
							break;
						}
			}
			catch(Exception $e) {
				$error_message = isset($e->problem) ? $e->problem : null;
			}
		}
	}
	else if ($table) {
		$sql = "select * from $table;";
		$table_attrs = $WorkFlowDBHandler->getDBTableAttributes($bean_file_name, $bean_name, $table);
		//echo "<pre>";print_r($table_attrs);die();
	}
}
else
	$sql = "";
?>
