<?php
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$entities = isset($_GET["entities"]) ? explode(",", $_GET["entities"]) : array();

$path = str_replace("../", "", $path);//for security reasons

if ($path) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
	
	if ($PEVC) {
		$P = $PEVC->getPresentationLayer();
		
		$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config")));
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		$layer_path = $P->getLayerPathSetting();
		
		$obj = array();
		
		if ($entities) 
			foreach ($entities as $entity) {
				$entity = trim($entity);
				
				if ($entity) {
					$file_path = "$layer_path$path" . (substr($path, -1) == "/" ? "": "/") . "$entity.php";
					$UserCacheHandler = $PHPFrameWork->getObject("UserCacheHandler"); //$PHPFrameWork is the same than $EVC->getPresentationLayer()->getPHPFrameWork(); //Use EVC instead of PEVC, bc is relative to the __system admin panel
					$obj[$entity] = CMSPresentationLayerHandler::isEntityFileHardCoded($PEVC, $UserCacheHandler, $cms_page_cache_path_prefix, $file_path, true, $workflow_paths_id, $bean_name);
				}
			}
		
		$PHPVariablesFileHandler->endUserGlobalVariables();
	}
	else {
		launch_exception(new Exception("PEVC doesn't exists!"));
		die();
	}
}
else {
	launch_exception(new Exception("Undefined path!"));
	die();
}
?>
