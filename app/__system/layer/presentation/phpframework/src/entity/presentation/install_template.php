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

//NOTE: IF YOU MAKE	ANY CHANGES IN THIS FILE, PLEASE BE SURE THAT THE create_project.php COVERS THAT CHANGES AND DOESN'T BREAK ITS LOGIC.

include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSTemplateInstallationHandler");
include_once get_lib("org.phpframework.util.web.MyCurl");
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");
include_once $EVC->getUtilPath("LayoutTypeProjectHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = !empty($_POST) ? (isset($_POST["project"]) ? $_POST["project"] : null) : (isset($_GET["path"]) ? $_GET["path"] : null);
$filter_by_layout = isset($_GET["filter_by_layout"]) ? $_GET["filter_by_layout"] : null; //optional
$on_success_js_func = isset($_GET["on_success_js_func"]) ? $_GET["on_success_js_func"] : null; //used by the choose_available_template.js
$popup = isset($_GET["popup"]) ? $_GET["popup"] : null;

$path = str_replace("../", "", $path);//for security reasons
$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons

$layers_projects = CMSPresentationLayerHandler::getPresentationLayersProjectsFiles($user_global_variables_file_path, $user_beans_folder_path, false, true, 0);
//echo "<pre>";print_r($layers_projects);die();

if ($bean_name && $bean_file_name && $path) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);

	if ($PEVC) {
		$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config")));
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		$P = $PEVC->getPresentationLayer();
		$selected_project = $P->getSelectedPresentationId();
		
		if (!empty($_POST["project"]) && (!empty($_FILES["zip_file"]) || (isset($_POST["zip_url"]) && trim($_POST["zip_url"])))) {
			$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
			$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($PEVC->getTemplatesPath(), "layer", "access");
			
			$is_zip_url = empty($_FILES["zip_file"]) && isset($_POST["zip_url"]) && trim($_POST["zip_url"]);
			
			//download zip_url
			if ($is_zip_url) {
				$zip_url = isset($_POST["zip_url"]) ? $_POST["zip_url"] : null;
				//echo "<pre>zip_url:$zip_url\n";die();
				
				$downloaded_file = MyCurl::downloadFile($zip_url, $fp);
				
				if ($downloaded_file && isset($downloaded_file["type"]) && stripos($downloaded_file["type"], "zip") !== false)
					$_FILES["zip_file"] = $downloaded_file;
			}
			
			//install zip file
			if (!empty($_FILES["zip_file"]) && isset($_FILES["zip_file"]["name"]) && trim($_FILES["zip_file"]["name"]) && !empty($_FILES["zip_file"]["tmp_name"])) {
				$name = $_FILES["zip_file"]["name"];
				
				//echo "<pre>";print_r($_FILES["zip_file"]);die();
				$templates_temp_folder_path = CMSTemplateInstallationHandler::getTmpRootFolderPath();
				$zipped_file_path = $templates_temp_folder_path . $name;
				$dest_file_path = substr($zipped_file_path, 0, -4) . "/";
				$extension = strtolower( pathinfo($name, PATHINFO_EXTENSION) );
				$template_id = pathinfo($name, PATHINFO_FILENAME);
				
				if ($extension != "zip") {
					$status = false;
					$messages["all"][] = array("msg" => "STATUS: FALSE: File '$name' must be a zip file!", "type" => "alert");
				}
				else if (!is_dir($templates_temp_folder_path) && !mkdir($templates_temp_folder_path, 0755, true))
					$error_message = "Error: trying to create tmp folder to upload '$name' file!";
				else {
					$continue = $is_zip_url ? rename($_FILES["zip_file"]["tmp_name"], $zipped_file_path) : move_uploaded_file($_FILES["zip_file"]["tmp_name"], $zipped_file_path);
					
					if ($continue) {
						//Delete folder in case it exists before, bc we are uploading a new zip and we dont want the old zip files.
						CacheHandlerUtil::deleteFolder($dest_file_path);
						
						//unzip
						$unzipped_folder_path = CMSTemplateInstallationHandler::unzipTemplateFile($zipped_file_path, $dest_file_path); //unzipped_module_path is the same than dest_file_path if unzip successfully
						
						if ($unzipped_folder_path) {
							//get template info
							$info = CMSTemplateInstallationHandler::getUnzippedTemplateInfo($unzipped_folder_path);
							
							//set new template id
							if ($info && !empty($info["tag"]) && $template_id != $info["tag"])
								$template_id = $info["tag"];
							
							//install template
							$template_folder_path = $PEVC->getTemplatesPath() . $template_id;
							$webroot_folder_path = $PEVC->getWebrootPath() . "template/$template_id";
						
							$CMSTemplateInstallationHandler = new CMSTemplateInstallationHandler($template_folder_path, $webroot_folder_path, $unzipped_folder_path);
						
							try {
								if ($CMSTemplateInstallationHandler->install()) {
									if ($selected_project != $PEVC->getCommonProjectName() || $CMSTemplateInstallationHandler->prepareInstalledCommonTemplate())
										$status = true;
								}
							}
							catch(Exception $e) {
								$status = false;
								$messages[$path][] = array("msg" => "STATUS: FALSE", "type" => "error");
								$messages[$path][] = array("msg" => "ERROR MESSAGE: " . $e->getMessage(), "type" => "exception");
								
								if (!empty($e->problem))
									$messages[$path][] = array("msg" => $e->problem, "type" => "exception");
							}
							
							CacheHandlerUtil::deleteFolder($unzipped_folder_path); //unzipped_module_path is the same than dest_file_path
						}
						
						unlink($zipped_file_path);
					}
					else 
						$error_message = "Error: Could not upload file. Please try again...";
				}
			}
			else 
				$error_message = "Error: Could not upload file. Please try again...";
			
			if ($is_zip_url && $fp)
				fclose($fp);
		}
		
		$PHPVariablesFileHandler->endUserGlobalVariables();
		
		//filter layers projects
		$LayoutTypeProjectHandler = new LayoutTypeProjectHandler($UserAuthenticationHandler, $user_global_variables_file_path, $user_beans_folder_path);
		$LayoutTypeProjectHandler->filterPresentationLayersProjectsByUserAndLayoutPermissions($layers_projects, $filter_by_layout, UserAuthenticationHandler::$PERMISSION_BELONG_NAME, array(
			"do_not_filter_by_layout" => array(
				"bean_name" => $bean_name,
				"bean_file_name" => $bean_file_name,
				"project" => $selected_project
			)
		));
		//echo "<pre>";print_r($layers_projects);die();
	}
}
?>
