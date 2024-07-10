<?php
include_once get_lib("org.phpframework.db.DB");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$path = $_GET["path"];
$db_broker = $_GET["db_broker"];
$db_driver = $_GET["db_driver"];

$data = $_POST["obj"];

if ($data) {
	$path = str_replace("../", "", $path);//for security reasons

	$PHPVariablesFileHandler = new PHPVariablesFileHandler($user_global_variables_file_path);
	$PHPVariablesFileHandler->startUserGlobalVariables();

	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);

	if ($item_type != "presentation")
		$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
	else
		$obj = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);

	if ($obj) {
		if (is_a($obj, "DB"))
			$sql = $obj->convertObjectToSQL($data);
		else {
			$broker = $obj->getBroker($db_broker);
			
			if (is_a($broker, "IDBBrokerClient") || is_a($broker, "IDataAccessBrokerClient"))
				$sql = $broker->getFunction("convertObjectToSQL", array($data), array("db_driver" => $db_driver));
			else {
				$layers = WorkFlowBeansFileHandler::getLocalBeanLayersFromBrokers($user_global_variables_file_paths, $user_beans_folder_path, $obj->getBrokers(), true);
				
				foreach ($layers as $layer_bean_name => $layer_obj)
					if (is_a($layer_obj, "DBLayer") || is_a($layer_obj, "DataAccessLayer")) {
						$sql = $layer_obj->getFunction("convertObjectToSQL", array($data), array("db_driver" => $db_driver));
						break;
					}
			}
		}
	}
	else
		$sql = DB::convertObjectToDefaultSQL($data);
}
?>
