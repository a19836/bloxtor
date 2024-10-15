<?php
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$db_broker = isset($_GET["db_broker"]) ? $_GET["db_broker"] : null;
$db_driver = isset($_GET["db_driver"]) ? $_GET["db_driver"] : null;
//$db_type = isset($_GET["db_type"]) ? $_GET["db_type"] : null; //deprecated
$db_table = isset($_GET["db_table"]) ? $_GET["db_table"] : null;

$path = str_replace("../", "", $path);//for security reasons
$ok = false;

if ($bean_name && $bean_file_name && $db_driver) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
	
	if ($obj && is_a($obj, "Layer")) {
		//prepare db_broker with correspondent broker_obj
		$broker_obj = $obj->getBroker($db_broker);
		
		if (!$db_broker && $broker_obj) { //if no $db_broker, it means that the returned $broker_obj is the default broker for the current Layer. So we need to find what is the right broker_name and assign it to $db_broker
			$brokers = $obj->getBrokers();
			
			if ($brokers)
				foreach($brokers as $broker_name => $broker) 
					if ($broker == $broker_obj) {
						$db_broker = $broker_name; //replace by the correct 
						break;
					}
		}
		
		//get db broker for correspondent driver
		if ($db_broker && $broker_obj) {
			$broker_name = WorkFlowBeansFileHandler::getBrokersLocalDBBrokerNameForChildBrokerDBDriver($user_global_variables_file_path, $user_beans_folder_path, array($db_broker => $broker_obj), $db_driver, $found_broker_obj, $found_broker_props);
			
			if ($broker_name && $found_broker_props && $found_broker_obj) {
				$db_layer_props = WorkFlowBeansFileHandler::getLocalBeanLayerFromBroker($user_global_variables_file_path, $user_beans_folder_path, $found_broker_obj);
				$db_layer_bean_name = isset($db_layer_props[0]) ? $db_layer_props[0] : null;
				$db_layer_bean_file_name = isset($db_layer_props[1]) ? $db_layer_props[1] : null;
				$db_layer_obj = isset($db_layer_props[2]) ? $db_layer_props[2] : null;
				
				if ($db_layer_obj) {
					$db_layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerObjFolderName($db_layer_obj);
					
					$db_driver_props = WorkFlowBeansFileHandler::getLayerDBDriverProps($user_global_variables_file_path, $user_beans_folder_path, $db_layer_obj, $db_driver);
					
					if ($db_driver_props) {
						$ok = true;
						
						$_GET["layer_bean_folder_name"] = $db_layer_bean_folder_name;
						$_GET["bean_name"] = isset($db_driver_props[2]) ? $db_driver_props[2] : null;
						$_GET["bean_file_name"] = isset($db_driver_props[1]) ? $db_driver_props[1] : null;
						//$_GET["type"] = $db_type; //deprecated
						$_GET["table"] = !empty($_GET["table"]) ? $_GET["table"] : $db_table; //Note that when the edit_table creates a new tables, it refreshes the page with the new table in the url
						
						unset($_GET["path"]);
						unset($_GET["db_broker"]);
						unset($_GET["db_driver"]);
						unset($_GET["db_type"]);
						unset($_GET["db_table"]);
						
						/*echo "<pre>get_class:".get_class($db_layer_obj);
						echo "<br>db_layer_bean_folder_name:$db_layer_bean_folder_name<br>";print_r($db_driver_props);
						print_r($_GET);
						die();*/
					}
				}
			}
		}
	}
	
	if ($ok) {
		if (!empty($_POST))
			$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write"); //needs this, otherwise gives unauthorized page
		
		include $EVC->getEntityPath("db/edit_table");
	}
	else
		$error_message = "Invalid DB Broker or Driver";
}
else
	$error_message = "Invalid params";
?>
