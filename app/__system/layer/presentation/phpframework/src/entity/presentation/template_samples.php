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

include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

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
		
		$file_path = $layer_path . $path;
		
		if (file_exists($file_path)) {
			$dir = dirname($path) . "/";
			$pos = strpos($dir, "/src/template/");
			
			if ($pos !== false) {
				$start_pos = $pos + strlen("/src/template/");
				$end_pos = strpos($dir, "/", $start_pos);
				
				if ($end_pos !== false) {
					$selected_template_dir = substr($dir, $start_pos);
					$selected_template_file = strtolower(pathinfo($path, PATHINFO_FILENAME));
					$template_webroot_dir = $PEVC->getWebrootPath() . "template/" . $selected_template_dir;
					//echo "selected_template_dir:$selected_template_dir<br>selected_template_file:$selected_template_file<br>template_webroot_dir:$template_webroot_dir";die();
					
					if (file_exists($template_webroot_dir)) {
						$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config")));
						$PHPVariablesFileHandler->startUserGlobalVariables();
						
						$url_prefix = getProjectUrlPrefix($PEVC, $selected_project_id);
						//echo "url_prefix:$url_prefix";die();
						
						$PHPVariablesFileHandler->endUserGlobalVariables();
						
						$valid_extensions = array("php", "html", "htm");
						$files = scandir($template_webroot_dir);
						//print_r($files);
						
						foreach ($files as $file)
							if ($file != "." && $file != ".." && !is_dir($template_webroot_dir . $file)) {
								$file_name = strtolower(pathinfo($file, PATHINFO_FILENAME));
								$file_extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
								
								$valid = in_array($file_extension, $valid_extensions) && ($file_name == $selected_template_file || preg_match("/^{$selected_template_file}[_\-]([0-9]+)$/u", $file_name)); //'/u' means converts to unicode.
								//echo "file_name:$file_name:$valid<br>";
								
								if ($valid)
									$sample_files[] = $url_prefix . "template/" . $selected_template_dir . $file;
							}
							
						//print_r($sample_files);die();
					}
				}
			}
		}
		else {
			launch_exception(new Exception("File '$path' does not exist!"));
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

function getProjectUrlPrefix($EVC, $selected_project_id) {
	@include $EVC->getConfigPath("config", $selected_project_id); //config file may not exist
	return $project_url_prefix;
}
?>
