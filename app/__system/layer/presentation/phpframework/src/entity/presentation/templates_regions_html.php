<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null; //template path
$popup = isset($_GET["popup"]) ? $_GET["popup"] : null;

$path = str_replace("../", "", $path);//for security reasons
$sample_files = array();

if ($path) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
	
	if ($PEVC) {
		$P = $PEVC->getPresentationLayer();
		$layer_path = $P->getLayerPathSetting();
		$selected_project_id = $P->getSelectedPresentationId();
		$default_extension = "." . $P->getPresentationFileExtension();
		$templates_folder = $PEVC->getTemplatesPath();
		
		$available_templates = CMSPresentationLayerHandler::getAvailableTemplatesList($PEVC, $default_extension);
		$available_templates = array_keys($available_templates);
		//print_r($available_templates);die();
		
		$available_templates_regions = array();
		
		foreach ($available_templates as $at) {
			$at_folder = dirname($at) . "/";
			$at_file_name = basename($at);
			$at_regions_folder = $templates_folder . $at_folder . "region/$at_file_name/";
			
			if (file_exists($at_regions_folder)) {
				$files = scandir($at_regions_folder);
				$regions = array();
				
				foreach ($files as $file) 
					if ($file != "." && $file != ".." && is_dir($at_regions_folder . $file)) {
						$region_path = $at_regions_folder . $file . "/";
						$region_name = pathinfo($file, PATHINFO_FILENAME);
						$sub_files = scandir($region_path);
						$region_samples = array();
						
						foreach ($sub_files as $sub_file) 
							if ($sub_file != "." && $sub_file != ".." && !is_dir($region_path . $sub_file)) {
								$html = file_get_contents($region_path . $sub_file);
								
								$region_samples[ pathinfo($sub_file, PATHINFO_FILENAME) ] = array(
									"sample_path" => substr($region_path . $sub_file, strlen($layer_path)),
									"template_path" => substr($PEVC->getTemplatePath($at), strlen($layer_path)),
									"html" => $html,
								);
							}
						
						$regions[$region_name] = $region_samples;
					}
				
				if ($regions)
					$available_templates_regions[$at] = $regions;
			}
		}
		
		//print_r($available_templates_regions);die();
	}
	else {
		launch_exception(new Exception("PEVC doesn't exists!"));
		die();
	}
}
else if (!$path) {
	launch_exception(new Exception("Undefined path!"));
	die();
}
?>
