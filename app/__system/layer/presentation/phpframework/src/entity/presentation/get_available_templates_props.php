<?php
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$path = $_GET["path"];

$path = str_replace("../", "", $path);//for security reasons

if ($path) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
	
	if ($PEVC) {
		$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config")));
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		$P = $PEVC->getPresentationLayer();
		$default_extension = "." . $P->getPresentationFileExtension();
		
		//PREPARING AVAILABLE TEMPLATES
		$available_templates = CMSPresentationLayerHandler::getAvailableTemplatesList($PEVC, $default_extension);
		$available_templates = array_keys($available_templates);
		//echo "<pre>";print_r($available_templates);die();
		
		$available_templates_props = CMSPresentationLayerHandler::getAvailableTemplatesProps($PEVC, $P->getSelectedPresentationId(), $available_templates);
		//echo "<pre>";print_r($available_templates_props);die();
		
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
