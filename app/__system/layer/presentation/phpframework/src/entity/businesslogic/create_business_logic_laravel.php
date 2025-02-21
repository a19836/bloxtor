<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

include_once $EVC->getEntityPath("cms/laravel/create_laravel_project");

if ($obj && is_a($obj, "BusinessLogicLayer") && !empty($_POST["step_1"]) && !empty($status)) {
	$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
	
	//add Service file
	$common_service_file_path = isset($obj->settings["business_logic_modules_service_common_file_path"]) ? $obj->settings["business_logic_modules_service_common_file_path"] : null;
	
	if (!LaravelInstallationHandler::createLaravelServiceFile($project_folder_path, $common_service_file_path))
		$status = false;
}
?>
