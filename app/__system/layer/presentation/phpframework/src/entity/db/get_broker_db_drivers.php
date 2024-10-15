<?php
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$broker = isset($_GET["broker"]) ? $_GET["broker"] : null;
$item_type = isset($_GET["item_type"]) ? $_GET["item_type"] : null;

$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);

if ($obj && is_a($obj, "Layer")) {
	if ($item_type == "db" || $item_type == "ibatis" || $item_type == "hibernate")
		$db_drivers = WorkFlowBeansFileHandler::getLayerDBDrivers($user_global_variables_file_path, $user_beans_folder_path, $obj, true);
	else {
		$b = $obj->getBroker($broker);
		$db_drivers = $broker ? WorkFlowBeansFileHandler::getBrokersDBDrivers($user_global_variables_file_path, $user_beans_folder_path, array($broker => $b), true) : array();
	}
	
	/*$PHPVariablesFileHandler = new PHPVariablesFileHandler($user_global_variables_file_path);
	$PHPVariablesFileHandler->startUserGlobalVariables();
	
	if ($item_type == "db") 
		$db_drivers = $obj->getDBDriversName();
	else if ($item_type == "ibatis" || $item_type == "hibernate")
		$db_drivers = $obj->getBrokersDBDriversName();
	else {
		$broker = $obj->getBroker($broker);
		$db_drivers = $broker ? $broker->getBrokersDBDriversName() : array();
	}
	
	$PHPVariablesFileHandler->endUserGlobalVariables();*/
	
	//Filter db_Drivers according with permissions in the user management.
	if ($db_drivers)
		foreach ($db_drivers as $db_driver_name => $db_driver_props) {
			if ($item_type == "db" || $item_type == "ibatis" || $item_type == "hibernate")
				$found_broker_name = WorkFlowBeansFileHandler::getLayerLocalDBBrokerNameForChildBrokerDBDriver($user_global_variables_file_path, $user_beans_folder_path, $obj, $db_driver_name, $found_broker_obj, $found_broker_props);
			else
				$found_broker_name = WorkFlowBeansFileHandler::getBrokersLocalDBBrokerNameForChildBrokerDBDriver($user_global_variables_file_path, $user_beans_folder_path, array($broker => $b), $db_driver_name, $found_broker_obj, $found_broker_props);
			
			if ($found_broker_props) {
				$layer_props = WorkFlowBeansFileHandler::getLocalBeanLayerFromBroker($user_global_variables_file_path, $user_beans_folder_path, $found_broker_obj);
				$layer_obj = isset($layer_props[2]) ? $layer_props[2] : null;
				$layer_object_id = LAYER_PATH . WorkFlowBeansFileHandler::getLayerObjFolderName($layer_obj) . "/" . $db_driver_props[2];
				if (!$UserAuthenticationHandler->isInnerFilePermissionAllowed($layer_object_id, "layer", "access"))
					unset($db_drivers[$db_driver_name]);
			}
		}
}
?>
