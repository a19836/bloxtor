<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

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
