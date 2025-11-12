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
$region = isset($_GET["region"]) ? $_GET["region"] : null;
$sample_path = isset($_GET["sample_path"]) ? $_GET["sample_path"] : null;

$path = str_replace("../", "", $path);//for security reasons

if ($path) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
	
	if ($PEVC) {
		$P = $PEVC->getPresentationLayer();
		$layer_path = $P->getLayerPathSetting();
		$selected_project_id = $P->getSelectedPresentationId();
		
		$template_file_path = $layer_path . $path;
		
		if (file_exists($template_file_path)) {
			$html = file_get_contents($template_file_path);
			
			if ($sample_path) {
				$sample_file_path = $layer_path . $sample_path;
				
				if (!file_exists($sample_file_path)) {
					launch_exception(new Exception("Sample file '$sample_path' doesn't exists!"));
					die();
				}
				
				//get the real region case sensitive
				$available_regions_list = CMSPresentationLayerHandler::getAvailableRegionsList($template_file_path, $selected_project_id, true);
				if ($available_regions_list)
					foreach ($available_regions_list as $r)
						if (strtolower($r) == '"' . strtolower($region) . '"') {
							$region = substr($r, 1, -1);
							break;
						}
				
				$html = '<?php 
					$external_vars = get_defined_vars();
					
					//Regions-Blocks:
					$html_from_file = file_get_contents("' . $sample_file_path . '");
					$html_from_file = PHPScriptHandler::parseContent($html_from_file, $external_vars);
					$EVC->getCMSLayer()->getCMSTemplateLayer()->addRegionHtml("' . $region . '", \'<div class="selected_region_sample">\' . $html_from_file . \'</div>\');
				?>' . $html;
				//echo "<textarea>$html</textarea>";die();
			}
			
			$html = getProjectTemplateHtml($PEVC, $user_global_variables_file_path, $html);
		}
		else {
			launch_exception(new Exception("Template path '$path' doesn't exists!"));
			die();
		}
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

function getProjectTemplateHtml($EVC, $user_global_variables_file_path, $html) {
	//set some default vars from the index controller that might be used in the template html
	$entity = null;
	
	$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $EVC->getConfigPath("pre_init_config")));
	$PHPVariablesFileHandler->startUserGlobalVariables();
	
	include $EVC->getConfigPath("config");
	include_once $EVC->getUtilPath("include_text_translator_handler", $EVC->getCommonProjectName());
	
	//error_log($html . "\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
	//echo $html;die();
	
	if (!isset($original_project_url_prefix))
		$original_project_url_prefix = $project_url_prefix;
	
	if (!isset($original_project_common_url_prefix))
		$original_project_common_url_prefix = $project_common_url_prefix;
	
	//saves html to temp file to be executed as php
	$fhandle = tmpfile();
	$md = stream_get_meta_data($fhandle);
	$tmp_file_path = isset($md['uri']) ? $md['uri'] : null;
	
	$pieces = str_split($html, 1024 * 4);
	foreach ($pieces as $piece)
		fwrite($fhandle, $piece, strlen($piece));
	
	$error_reporting = error_reporting();
	
	//executes php
	ob_start(null, 0);
	error_reporting(0); //disable errors
	include $tmp_file_path;
	error_reporting($error_reporting);
	$html = ob_get_contents();
	ob_end_clean();
	
	//closes and removes temp file
	fclose($fhandle); 
	
	header_remove(); //remove all header in case exists any header with Location
	
	$PHPVariablesFileHandler->endUserGlobalVariables();
	
	return $html;
}
?>
