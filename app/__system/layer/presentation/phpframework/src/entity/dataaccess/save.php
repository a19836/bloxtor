<?php
include_once $EVC->getUtilPath("WorkFlowDataAccessHandler");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$hbn_obj_id = isset($_GET["obj"]) ? $_GET["obj"] : null;
$relationship_type = isset($_GET["relationship_type"]) ? $_GET["relationship_type"] : null;

$path = str_replace("../", "", $path);//for security reasons

$is_import_file = $relationship_type == "import";
$queries_ids = isset($queries_ids) ? $queries_ids : null; //$queries_ids is init in the remove_hbn_query.php file

$PHPVariablesFileHandler = new PHPVariablesFileHandler($user_global_variables_file_path);
$PHPVariablesFileHandler->startUserGlobalVariables();

$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);

if ($obj && is_a($obj, "DataAccessLayer") && !empty($_POST)) {
	$object = isset($_POST["object"]) ? $_POST["object"] : null;
	$overwrite = isset($_POST["overwrite"]) ? $_POST["overwrite"] : null;
	
	$layer_path = $obj->getLayerPathSetting();
	$file_path = trim($layer_path . $path);//it should be a file. Not a folder.
	
	if ($path) {
		$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($file_path, "layer", "access");
		UserAuthenticationHandler::checkUsersMaxNum($UserAuthenticationHandler);
		UserAuthenticationHandler::checkActionsMaxNum($UserAuthenticationHandler);
		
		$folder_path = substr($file_path, strlen($file_path) - 1) == "/" ? $file_path : dirname($file_path);
		if (!is_dir($folder_path)) {
			mkdir($folder_path, 0755, true);
		}
		
		$object = MyXML::convertChildsToAttributesInBasicArray($object, array("lower_case_keys" => true));
		$object = MyXML::basicArrayToComplexArray($object, array("lower_case_keys" => true));
		//print_r($object);die();
		
		$WorkFlowDataAccessHandler = new WorkFlowDataAccessHandler();
		
		if ($obj->getType() == "hibernate") {
			switch ($file_type) {
				case "save_obj": 
					$status = $WorkFlowDataAccessHandler->createHibernateObjectFromObjectData($file_path, $object, $overwrite, $hbn_obj_id);
					break;
				case "save_query": 
				case "save_relationship": 
				case "save_map": 
					//$queries_ids is init in the remove_hbn_query.php file, otherwise it is re-init inside of the function WorkFlowDataAccessHandler->createHibernateQueriesFromObjectData(...) || createTableQueriesFromObjectData(...).
					if ($is_import_file) {
						$status = $WorkFlowDataAccessHandler->createTableQueriesFromObjectData($file_path, $object, $overwrite, $queries_ids, true);
					}
					else {
						$status = $WorkFlowDataAccessHandler->createHibernateQueriesFromObjectData($file_path, $hbn_obj_id, $object, $queries_ids, $relationship_type);
					}
					break;
				case "save_includes": 
					$status = $WorkFlowDataAccessHandler->createIncludesFromObjectData($file_path, $object, $obj->getType());
					break;
			}
			
		}
		else {
			switch ($file_type) {
				case "save_query": 
				case "save_map": 
					//$queries_ids is init in the remove_hbn_query.php file, otherwise it is re-init inside of the function WorkFlowDataAccessHandler->createTableQueriesFromObjectData(...).
					$status = $WorkFlowDataAccessHandler->createTableQueriesFromObjectData($file_path, $object, $overwrite, $queries_ids, $is_import_file);
					break;
				case "save_includes": 
					$status = $WorkFlowDataAccessHandler->createIncludesFromObjectData($file_path, $object, $obj->getType());
					break;
			}
		}
		
		if (!empty($status)) {
			$UserAuthenticationHandler->incrementUsedActionsTotal();
			
			//delete caches
			$cache_path = $obj->getCacheLayer()->getCachedDirPath() . "/" . (is_a($obj, "IbatisDataAccessLayer") ? IBatisClientCache::CACHE_DIR_NAME : HibernateClientCache::CACHE_DIR_NAME);
			CacheHandlerUtil::deleteFolder($cache_path, false);
		}
	}
}

$PHPVariablesFileHandler->endUserGlobalVariables();

echo isset($status) ? $status : null;
die();
?>
