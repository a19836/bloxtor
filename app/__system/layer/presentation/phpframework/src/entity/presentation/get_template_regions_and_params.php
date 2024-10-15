<?php
include_once $EVC->getUtilPath("PHPVariablesFileHandler");
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$project = isset($_GET["project"]) ? $_GET["project"] : null;
$template = isset($_GET["template"]) ? $_GET["template"] : null;
$is_external_template = isset($_GET["is_external_template"]) ? $_GET["is_external_template"] : null;
$external_template_params = isset($_GET["external_template_params"]) ? json_decode($_GET["external_template_params"], true) : null;
$template_includes = isset($_GET["template_includes"]) ? json_decode($_GET["template_includes"], true) : null;

if ($project && $template) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $project);

	if ($PEVC) {
		$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config")));
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		//PREPARING TEMPLATE AVAILABLE REGIONS
		$template_contents = CMSPresentationLayerHandler::getSetTemplateCode($PEVC, $is_external_template, $template, $external_template_params, $template_includes);
		
		$available_regions_list = CMSPresentationLayerHandler::getAvailableRegionsListFromCode($template_contents, $project, false); //show regions even if they are already defined in the template.
		$undefined_regions_list = CMSPresentationLayerHandler::getAvailableRegionsListFromCode($template_contents, $project, true);
		$defined_regions_list = array_values(array_diff($available_regions_list, $undefined_regions_list));
		$params_list = CMSPresentationLayerHandler::getAvailableTemplateParamsListFromCode($template_contents, true);
		$available_params_list = isset($params_list[0]) ? $params_list[0] : null;
		$available_params_values_list = isset($params_list[1]) ? $params_list[1] : null;
		
		$obj = array(
			"regions" => $available_regions_list,
			"defined_regions" => $defined_regions_list,
			"params" => $available_params_list,
			"params_values" => $available_params_values_list,
		);
		
		header_remove(); //remove all header in case exists any header with Location
		
		if (substr("" . http_response_code(), 0, 1) != "2") //if is not a 200 code or 2 hundred and something...
			http_response_code(200); //header_remove does not remove the HTTP headers, so we need to overwrite this header in case it was changed by the template! A real example is when we call a block with wordpress module and the wordpress returns a page not found with the 404 Not Found code. So we need to overwrite this header with: 200 OK.
	
		$PHPVariablesFileHandler->endUserGlobalVariables();
	}
}
?>
