<?php
include_once $EVC->getUtilPath("WorkFlowDBHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$layer_bean_folder_name = $_GET["layer_bean_folder_name"];
$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$table = $_GET["table"];
$popup = $_GET["popup"];
$sql = $_GET["sql"];

if ($bean_name) {
	$layer_object_id = LAYER_PATH . "$layer_bean_folder_name/$bean_name";
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_object_id, "layer", "access");
	
	$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_file_path);
	
	if ($_POST) {
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
		
		$sql = $_POST["sql"];
		
		if ($sql) {
			$DBDriver = $WorkFlowDBHandler->getBeanObject($bean_file_name, $bean_name);
			
			$data = $DBDriver->convertSQLToObject($sql);
			$is_select_sql = $data && $data["type"] == "select";
			
			try {
				if ($is_select_sql)
					$results = $DBDriver->getData($sql);
				else
					$results = $DBDriver->setData($sql);
			}
			catch(Exception $e) {
				$exception_message = $e->problem;
			}
		}
	}
	else if ($table && !$sql)
		$sql = "select * from $table;";
	else if ($sql) { //split sql in multiple lines
		$DBDriver = $WorkFlowDBHandler->getBeanObject($bean_file_name, $bean_name);
		$sqls = $DBDriver->splitSQL($sql);
		$sql = "";
		
		if ($sqls)
			foreach ($sqls as $statement)
				$sql .= preg_replace("/;$/", "", trim($statement)) . ";\n"; //Do not remove the space before the ; because if we have this sql "DELIMITER ;", it will convert it to "DELIMITER;" which will not be recognized.
	}
}
else
	$sql = "";
?>
