<?php
include_once $EVC->getUtilPath("WorkFlowDBHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$layer_bean_folder_name = isset($_GET["layer_bean_folder_name"]) ? $_GET["layer_bean_folder_name"] : null;
$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$table = isset($_GET["table"]) ? $_GET["table"] : null;
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
			
			$data = $DBDriver->convertSQLToObject($sql);
			$is_select_sql = $data && isset($data["type"]) && $data["type"] == "select";
			
			try {
				if ($is_select_sql)
					$results = $DBDriver->getData($sql);
				else
					$results = $DBDriver->setData($sql);
			}
			catch(Exception $e) {
				$exception_message = isset($e->problem) ? $e->problem : null;
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
