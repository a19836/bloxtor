<?php
include_once get_lib("org.phpframework.db.DBFileImporter");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$layer_bean_folder_name = $_GET["layer_bean_folder_name"];
$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$type = $_GET["type"];
$table = str_replace("/", "", $_GET["table"]);

$PHPVariablesFileHandler = new PHPVariablesFileHandler($user_global_variables_file_path);
$PHPVariablesFileHandler->startUserGlobalVariables();

$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);

$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);

if ($obj && is_a($obj, "DB")) {
	$layer_object_id = LAYER_PATH . "$layer_bean_folder_name/$bean_name";
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_object_id, "layer", "access");
	
	$table_attrs = $obj->listTableFields($table);
	$table_attrs = array_keys($table_attrs);
	
	if ($_POST) {
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
		
		$file = $_FILES["file"];
		$file_type = $_POST["file_type"];
		$rows_delimiter = $_POST["rows_delimiter"];
		$columns_delimiter = $_POST["columns_delimiter"];
		$enclosed_by = $_POST["enclosed_by"];
		$ignore_rows_number = trim($_POST["ignore_rows_number"]);
		$insert_ignore = trim($_POST["insert_ignore"]);
		$update_existent = trim($_POST["update_existent"]);
		$force = trim($_POST["force"]);
		$columns_attributes = $_POST["columns_attributes"];
		$uploaded_file_path = TMP_PATH . $file["name"];
		
		if ($file["tmp_name"]) {
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
