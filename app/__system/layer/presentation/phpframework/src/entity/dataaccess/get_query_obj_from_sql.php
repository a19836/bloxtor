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

include_once get_lib("org.phpframework.db.DB");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$item_type = isset($_GET["item_type"]) ? $_GET["item_type"] : null;
$db_broker = isset($_GET["db_broker"]) ? $_GET["db_broker"] : null;
$db_driver = isset($_GET["db_driver"]) ? $_GET["db_driver"] : null;

$sql = isset($_POST["sql"]) ? $_POST["sql"] : null;

if ($sql) {
	$path = str_replace("../", "", $path);//for security reasons
	
	//remove comments from sql
	$sql = preg_replace('/^--.*$/m', '', $sql); //regex to remove all comments that starts with "--" in each line. "m" regex flag is the multi line flag.
	//echo $sql;die();
	
	//convert sql to obj
	$PHPVariablesFileHandler = new PHPVariablesFileHandler($user_global_variables_file_path);
	$PHPVariablesFileHandler->startUserGlobalVariables();

	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	
	if ($item_type != "presentation")
		$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
	else
		$obj = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);

	if ($obj) {
		if (is_a($obj, "DB"))
			$data = $obj->convertSQLToObject($sql);
		else {
			$broker = $obj->getBroker($db_broker);
			
			if (is_a($broker, "IDBBrokerClient") || is_a($broker, "IDataAccessBrokerClient"))
				$data = $broker->getFunction("convertSQLToObject", $sql, array("db_driver" => $db_driver));
			else {
				$layers = WorkFlowBeansFileHandler::getLocalBeanLayersFromBrokers($user_global_variables_file_path, $user_beans_folder_path, $obj->getBrokers(), true);
				
				foreach ($layers as $layer_bean_name => $layer_obj)
					if (is_a($layer_obj, "DBLayer") || is_a($layer_obj, "DataAccessLayer")) {
						$data = $layer_obj->getFunction("convertSQLToObject", $sql, array("db_driver" => $db_driver));
						break;
					}
			}
		}
	}
	else
		$data = DB::convertDefaultSQLToObject($sql);
}
?>
