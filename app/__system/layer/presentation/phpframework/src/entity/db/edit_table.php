<?php
include_once get_lib("org.phpframework.workflow.WorkFlowTaskHandler");
include_once $EVC->getUtilPath("WorkFlowDBHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$layer_bean_folder_name = isset($_GET["layer_bean_folder_name"]) ? $_GET["layer_bean_folder_name"] : null;
$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
//$type = isset($_GET["type"]) ? $_GET["type"] : null; //deprecated
$table = isset($_GET["table"]) ? str_replace("/", "", $_GET["table"]) : null;
$with_advanced_options = isset($_GET["with_advanced_options"]) ? $_GET["with_advanced_options"] : null;
$on_success_js_func = isset($_GET["on_success_js_func"]) ? $_GET["on_success_js_func"] : null;
$popup = isset($_GET["popup"]) ? $_GET["popup"] : null;

$WorkFlowTaskHandler = new WorkFlowTaskHandler($webroot_cache_folder_path, $webroot_cache_folder_url);
$WorkFlowTaskHandler->setCacheRootPath(LAYER_CACHE_PATH);
$WorkFlowTaskHandler->setAllowedTaskTypes(array("table"));

$PHPVariablesFileHandler = new PHPVariablesFileHandler($user_global_variables_file_path);
$PHPVariablesFileHandler->startUserGlobalVariables();

$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);

if ($obj && is_a($obj, "DB") && $layer_bean_folder_name) {
	$layer_object_id = LAYER_PATH . "$layer_bean_folder_name/$bean_name";
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_object_id, "layer", "access");
	
	$available_tables = $obj->listTables();
	$table_exists = $obj->isTableInNamesList($available_tables, $table);
	$table_name = $obj->getTableInNamesList($available_tables, $table);
	$table_data = null;
	
	$t = count($available_tables);
	for ($i = 0; $i < $t; $i++)
		if (isset($available_tables[$i]["name"]) && $available_tables[$i]["name"] == $table_name) {
			$table_data = $available_tables[$i];
			break;
		}
	
	if (!$table_data)
		$table_exists = false;
	
	if (!$table || $table_exists) {
		$table_attrs = $table_exists ? $obj->listTableFields($table) : array(); //could be a new table
		//echo "<pre>";print_r($table_attrs);die();
		$table_attrs = array_values($table_attrs);
		
		if (!empty($_POST)) {
			$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
			
			$step = isset($_POST["step"]) ? $_POST["step"] : null;
			
			if ($step >= 2) {
				$with_advanced_options = isset($_POST["with_advanced_options"]) ? $_POST["with_advanced_options"] : null;
				$action = isset($_POST["action"]) ? $_POST["action"] : null;
				$sql_statements = isset($_POST["sql_statements"]) ? $_POST["sql_statements"] : null;
				$data = isset($_POST["data"]) ? json_decode($_POST["data"], true) : null;
				$errors = array();
				
				if ($sql_statements)
					foreach ($sql_statements as $idx => $sql)
						if (!$sql)
							unset($sql_statements[$idx]);
				
				if (!$sql_statements) 
					$error_message = "No sql to execute!";
				else {
					foreach ($sql_statements as $sql) {
						$e = $obj->setData($sql);
					
						if ($e !== true)
							$errors[] = (is_a($e, "Exception") ? $e->getMessage() . "\n\n" : "") . $sql;
					}
					
					//update correspondent in diagram if it exists for table: $data["table_name"]
					if (($action == "delete" && $table) || !empty($data["table_name"])) {
						$tasks_file_path = WorkFlowTasksFileHandler::getDBDiagramTaskFilePath($workflow_paths_id, "db_diagram", $bean_name);
						
						//only update diagram if diagram settings is to sync with server
						$diagram_settings = WorkFlowDBHandler::getTaskDBDiagramSettings($tasks_file_path);
						
						if (!empty($diagram_settings["sync_with_db_server"]) || !array_key_exists("sync_with_db_server", $diagram_settings)) {
							$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_file_path);
							
							if ($action == "delete")
								$WorkFlowDBHandler->removeFileTasksDBDiagramTables($tasks_file_path, $table);
							else {
								if ($action == "update" && $table != $data["table_name"])
									$WorkFlowDBHandler->renameFileTasksDBDiagramTables($tasks_file_path, array($table => $data["table_name"]));
								
								$WorkFlowDBHandler->updateFileTasksDBDiagramTablesFromServer($bean_file_name, $bean_name, $tasks_file_path, $data["table_name"]);
							}
						}
					}
					
					if ($errors)
						$error_message = "There were some errors trying to $action this table.";
					else if ($action == "delete") {
						if ($on_success_js_func)
							$script = "
							if (typeof window.parent.$on_success_js_func == 'function')
								window.parent.$on_success_js_func();";
						else
							$script = "
							if (typeof window.parent.refreshLastNodeParentChilds == 'function' && window.parent.last_selected_node_id && window.parent.$('#' + window.parent.last_selected_node_id + ' > a > i.table').length > 0)
								window.parent.refreshLastNodeParentChilds();
							else if (typeof window.parent.refreshAndShowNodeChilds == 'function' && window.parent.last_selected_node_id && window.parent.$('#' + window.parent.last_selected_node_id + ' > a > i.attribute').length > 0)
								window.parent.refreshAndShowNodeChilds( window.parent.$('#' + window.parent.last_selected_node_id).parent().parent().parent().parent() );
							else if (typeof window.parent.refreshAndShowLastNodeChilds == 'function')
								window.parent.refreshAndShowLastNodeChilds();";
						
						$status_message = "Table '$table' deleted successfully!
						<script>$script</script>";
					}
					else {
						$msg = "Table was $action" . ($action == "add" ? "e" : "") . "d successfully!\\nThis Page will now be refreshed so you can confirm if your changes were really made in the DB...";
						$table_name = isset($data["table_name"]) ? $data["table_name"] : null;
						
						if ($on_success_js_func)
							$script = "
							if (typeof window.parent.$on_success_js_func == 'function')
								window.parent.$on_success_js_func();";
						else if ($action == "update" && $table != $table_name)
							$script = "
							if (typeof window.parent.refreshLastNodeParentChilds == 'function' && window.parent.last_selected_node_id && window.parent.$('#' + window.parent.last_selected_node_id + ' > a > i.table').length > 0)
								window.parent.refreshLastNodeParentChilds();
							else if (typeof window.parent.refreshAndShowNodeChilds == 'function' && window.parent.last_selected_node_id && window.parent.$('#' + window.parent.last_selected_node_id + ' > a > i.attribute').length > 0)
								window.parent.refreshAndShowNodeChilds( window.parent.$('#' + window.parent.last_selected_node_id).parent().parent().parent().parent() );
							else if (typeof window.parent.refreshLastNodeChilds == 'function')
								window.parent.refreshLastNodeChilds();";
						else
							$script = "
							if (typeof window.parent.refreshAndShowNodeChilds == 'function' && window.parent.last_selected_node_id && window.parent.$('#' + window.parent.last_selected_node_id + ' > a > i.attribute').length > 0)
								window.parent.refreshAndShowNodeChilds( window.parent.$('#' + window.parent.last_selected_node_id).parent().parent() );
							else if (typeof window.parent.refreshAndShowLastNodeChilds == 'function')
								window.parent.refreshAndShowLastNodeChilds();";
						
						$status_message = str_replace('\n', "<br>", $msg) . "<script>
							$script
							
							alert('$msg');
							document.location = ('' + document.location).replace(/&table=([^#&]*)/g, '') + '&table=" . $table_name . ($with_advanced_options ? "&with_advanced_options=1" : "") . "';
						</script>"; //refresh page
					}
				}
			}
			else if ($step == 1) {
				$data = isset($_POST["data"]) ? json_decode($_POST["data"], true) : null;
				$with_advanced_options = isset($_POST["with_advanced_options"]) ? $_POST["with_advanced_options"] : null;
				$action = !empty($_POST["add"]) ? "add" : (!empty($_POST["update"]) ? "update" : "delete");
				
				//echo "<pre>";print_r($_POST);die();
				//echo "<pre>";print_r($data);die();
				//echo "<pre>";print_r($data["attributes"]);die();
				
				$sql_statements = array();
				$sql_statements_labels = array();
				
				if (!empty($_POST["delete"])) {
					$sql_statements[] = $obj->getDropTableStatement($table, $obj->getOptions());
					$sql_statements_labels[] = "Drop table $table";
				}
				else if (!empty($_POST["add"])) {
					$sql_statements[] = $obj->getCreateTableStatement($data, $obj->getOptions());
					$sql_statements_labels[] = "Create table " . $data["table_name"];
				}
				else if (!empty($_POST["update"])) {
					$old_table_data = array(
						"table_charset" => isset($table_data["charset"]) ? $table_data["charset"] : null,
						"table_collation" => isset($table_data["collation"]) ? $table_data["collation"] : null,
						"table_storage_engine" => isset($table_data["engine"]) ? $table_data["engine"] : null
					);
					
					$table_name = isset($data["table_name"]) ? $data["table_name"] : null;
					$attributes = isset($data["attributes"]) ? $data["attributes"] : null;
					$statements = WorkFlowDBHandler::getTableUpdateSQLStatements($obj, $table, $table_attrs, $attributes, $table_name, $old_table_data, $data);
					$sql_statements = isset($statements["sql_statements"]) ? $statements["sql_statements"] : null;
					$sql_statements_labels = isset($statements["sql_statements_labels"]) ? $statements["sql_statements_labels"] : null;
					
					if ($sql_statements && !$data["table_charset"] && $data["table_collation"])
						$error_message = "Possible Error: charset definition missing!";
				}
				
				if (empty($sql_statements))
					$status_message = "No changes to be made!";
			}
		}
		else {
			$data = array(
				"table_name" => $table,
				"table_charset" => isset($table_data["charset"]) ? $table_data["charset"] : null,
				"table_collation" => isset($table_data["collation"]) ? $table_data["collation"] : null,
				"table_storage_engine" => isset($table_data["engine"]) ? $table_data["engine"] : null,
				"attributes" => $table_attrs
			);
		}
		
		if (!empty($data) && !empty($data["attributes"])) {
			foreach ($data["attributes"] as $idx => $attr)
				foreach ($attr as $k => $v) {
					$data["table_attr_" . $k . "s"][$idx] = $v;
					
					if ($k == "default")
						$data["table_attr_has_" . $k . "s"][$idx] = isset($attr["has_default"]) ? $attr["has_default"] : isset($v);//In the beggining the $data["attributes"][$idx]["default"] can have a specific value or be null. So we need to use the "isset" if there is no POST. DO NOT USE 'strlen($v) > 0' because the default value van be an empty string for varchar types.
				}
			
			//echo "<pre>";print_r($data["attributes"]);die();
			unset($data["attributes"]);
		}
		//echo "<pre>";print_r($data);die();
	}
}
else 
	$error_message = "Error: Bean object is not a DBDriver!";

$PHPVariablesFileHandler->endUserGlobalVariables();
?>
