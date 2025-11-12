<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSModuleInstallationHandler");
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");
include_once $EVC->getUtilPath("LayoutTypeProjectHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$filter_by_layout = isset($_GET["filter_by_layout"]) ? $_GET["filter_by_layout"] : null; //optional
$popup = isset($_GET["popup"]) ? $_GET["popup"] : null;

$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons

if ($bean_name && $bean_file_name) {
	$projects = CMSPresentationLayerHandler::getPresentationLayerProjectsFiles($user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name, "config", false, 0);
	
	$LayoutTypeProjectHandler = new LayoutTypeProjectHandler($UserAuthenticationHandler, $user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name);
$LayoutTypeProjectHandler->filterPresentationLayerProjectsByUserAndLayoutPermissions($projects, $filter_by_layout, UserAuthenticationHandler::$PERMISSION_BELONG_NAME, array(
		"do_not_filter_by_layout" => array(
			"bean_name" => $bean_name,
			"bean_file_name" => $bean_file_name
		)
	));
	
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$P = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
	
	$available_db_drivers = WorkFlowBeansFileHandler::getLayerDBDrivers($user_global_variables_file_path, $user_beans_folder_path, $P, true);
	$LayoutTypeProjectHandler->filterLayerBrokersDBDriversPropsFromLayoutName($available_db_drivers, $filter_by_layout); //filter db_drivers by $filter_by_layout
	//echo "<pre>";print_r($available_db_drivers);die();
	
	if (!empty($_POST) && (!empty($_FILES["zip_file"]) || !empty($_POST["zip_url"]))) {
		//echo "<pre>";print_r($_POST);print_r($_FILES);die();
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
		
		$status = true;
		$messages = array();
		
		//preparing presentation layer paths
		$PresentationLayer = $EVC->getPresentationLayer();
		$module_prefix_path = $PresentationLayer->getLayerPathSetting() . $PresentationLayer->getCommonProjectName() . "/";
		
		if (empty($PresentationLayer->settings["presentation_modules_path"]))
			launch_exception(new Exception("'PresentationLayer->settings[presentation_modules_path]' cannot be undefined!"));
		
		if (empty($PresentationLayer->settings["presentation_webroot_path"]))
			launch_exception(new Exception("'PresentationLayer->settings[presentation_webroot_path]' cannot be undefined!"));
		
		$system_presentation_settings_path = $module_prefix_path . $PresentationLayer->settings["presentation_modules_path"];
		$system_presentation_settings_webroot_path = $module_prefix_path . $PresentationLayer->settings["presentation_webroot_path"] . "module/";
		
		//preparing selected project settings
		$selected_project = isset($_POST["project"]) ? $_POST["project"] : null;
		$selected_db_driver = isset($_POST["db_driver"]) ? $_POST["db_driver"] : null;
		
		$pre_init_configs = array();
		foreach ($projects as $project_name => $project) {
			if (empty($selected_project) || $project_name == $selected_project) {
				if (!empty($project["files"]["pre_init_config.php"]) || !empty($project["files"]["pre_init_config"])) {
					$fp = !empty($project["files"]["pre_init_config.php"]) ? $project["files"]["pre_init_config.php"] : $project["files"]["pre_init_config"];
					$file_path = isset($fp["path"]) ? substr($project["path"], 0, - strlen($project_name)) . $fp["path"] : "";
					$pre_init_configs[$project_name] = $file_path;
				}
			}
		}
		
		//This is very important bc in case there is no project, we must still let the modules get installed!
		if (!$pre_init_configs)
			$pre_init_configs[] = false;
		//echo "<pre>";print_r($pre_init_configs);die();
		
		//download zip_url
		$files_to_close = array();
		$zip_urls_tmp_names = array();
		
		if (!empty($_POST["zip_url"])) {
			$index = !empty($_FILES["zip_file"]) && !empty($_FILES["zip_file"]["name"]) ? max(array_keys($_FILES["zip_file"]["name"])) + 1 : 0;
			//echo "<pre>_FILES[zip_file]";print_r($_FILES["zip_file"]);
			
			foreach ($_POST["zip_url"] as $zip_url) 
				if (trim($zip_url)) {
					//echo "<pre>zip_url:$zip_url\n";die();
					$downloaded_file = MyCurl::downloadFile($zip_url, $fp);
					
					if ($fp)
						$files_to_close[] = $fp;
					//echo "<br>downloaded_file($index):";print_r($downloaded_file);
					
					if ($downloaded_file && isset($downloaded_file["type"]) && stripos($downloaded_file["type"], "zip") !== false && !empty($downloaded_file["tmp_name"])) {
						if (empty($_FILES["zip_file"]))
							$_FILES["zip_file"] = array();
						
						$_FILES["zip_file"]["name"][$index] = isset($downloaded_file["name"]) ? $downloaded_file["name"] : null;
						$_FILES["zip_file"]["type"][$index] = isset($downloaded_file["type"]) ? $downloaded_file["type"] : null;
						$_FILES["zip_file"]["tmp_name"][$index] = $downloaded_file["tmp_name"];
						$_FILES["zip_file"]["error"][$index] = isset($downloaded_file["error"]) ? $downloaded_file["error"] : null;
						$_FILES["zip_file"]["size"][$index] = isset($downloaded_file["size"]) ? $downloaded_file["size"] : null;
						
						$zip_urls_tmp_names[ $downloaded_file["tmp_name"] ] = true;
						$index++;
					}
					else {
						$status = false;
						$error_message = "Error: Could not upload file. Please try again...";
					}
				}
			
			//echo "<pre>";print_r($_POST);print_r($_FILES);die();
		}
		
		//install modules
		if (!empty($_FILES["zip_file"])) {
			//validate $_FILES["zip_file"]
			$exists = false;
			
			if (!empty($_FILES["zip_file"]["name"]))
				foreach ($_FILES["zip_file"]["name"] as $i => $name)
					if (trim($name)) {
						$exists = true;
						break;
					}
			
			if (!$exists) {
				$status = false;
				$error_message = "Error: File cannot be empty!";
			}
			else {
				//reorder $_FILES["zip_file"]
				ksort($_FILES["zip_file"]["name"]);
				isset($_FILES["zip_file"]["type"]) && is_array($_FILES["zip_file"]["type"]) && ksort($_FILES["zip_file"]["type"]);
				isset($_FILES["zip_file"]["tmp_name"]) && is_array($_FILES["zip_file"]["tmp_name"]) && ksort($_FILES["zip_file"]["tmp_name"]);
				isset($_FILES["zip_file"]["error"]) && is_array($_FILES["zip_file"]["error"]) && ksort($_FILES["zip_file"]["error"]);
				isset($_FILES["zip_file"]["size"]) && is_array($_FILES["zip_file"]["size"]) && ksort($_FILES["zip_file"]["size"]);
				//echo "<pre>";print_r($_POST);print_r($_FILES);die();
				
				//reorder files according with modules dependencies 
				$right_order = array("translator", "common", "object", "tag", "attachment", "user", "action", "zip", "comment");
				$new_files = array();
				
				foreach ($right_order as $file_name)
					foreach ($_FILES["zip_file"]["name"] as $i => $name) {
						if (pathinfo($name, PATHINFO_FILENAME) == $file_name) {
							$new_files["zip_file"]["name"][] = $name;
							$new_files["zip_file"]["type"][] = isset($_FILES["zip_file"]["type"][$i]) ? $_FILES["zip_file"]["type"][$i] : null;
							$new_files["zip_file"]["tmp_name"][] = isset($_FILES["zip_file"]["tmp_name"][$i]) ? $_FILES["zip_file"]["tmp_name"][$i] : null;
							$new_files["zip_file"]["error"][] = isset($_FILES["zip_file"]["error"][$i]) ? $_FILES["zip_file"]["error"][$i] : null;
							$new_files["zip_file"]["size"][] = isset($_FILES["zip_file"]["size"][$i]) ? $_FILES["zip_file"]["size"][$i] : null;
							
							break;
						}
					}
				
				foreach ($_FILES["zip_file"]["name"] as $i => $name) {
					if (!in_array(pathinfo($name, PATHINFO_FILENAME), $right_order)) {
						$new_files["zip_file"]["name"][] = $name;
						$new_files["zip_file"]["type"][] = isset($_FILES["zip_file"]["type"][$i]) ? $_FILES["zip_file"]["type"][$i] : null;
						$new_files["zip_file"]["tmp_name"][] = isset($_FILES["zip_file"]["tmp_name"][$i]) ? $_FILES["zip_file"]["tmp_name"][$i] : null;
						$new_files["zip_file"]["error"][] = isset($_FILES["zip_file"]["error"][$i]) ? $_FILES["zip_file"]["error"][$i] : null;
						$new_files["zip_file"]["size"][] = isset($_FILES["zip_file"]["size"][$i]) ? $_FILES["zip_file"]["size"][$i] : null;
					}
				}
				//echo "<pre>";print_r($new_files);die();
				
				//install modules
				foreach ($new_files["zip_file"]["name"] as $i => $name)
					if (trim($name)) {
						$zip_file = array(
							"name" => $name,
							"type" => $new_files["zip_file"]["type"][$i],
							"tmp_name" => $new_files["zip_file"]["tmp_name"][$i],
							"error" => $new_files["zip_file"]["error"][$i],
							"size" => $new_files["zip_file"]["size"][$i],
						);
						//echo "<pre>";print_r($zip_file);die();
						
						$modules_temp_folder_path = CMSModuleInstallationHandler::getTmpRootFolderPath();
						$zipped_file_path = $modules_temp_folder_path . $name;
						$dest_file_path = substr($zipped_file_path, 0, -4) . "/";
						$extension = strtolower( pathinfo($name, PATHINFO_EXTENSION) );
						$module_id = pathinfo($name, PATHINFO_FILENAME);
						
						if ($extension != "zip") {
							$status = false;
							$messages[$module_id]["all"][] = array("msg" => "STATUS: FALSE: File '$name' must be a zip file!", "type" => "alert");
						}
						else if (!is_dir($modules_temp_folder_path) && !mkdir($modules_temp_folder_path, 0755, true))
							$error_message = "Error: trying to create tmp folder to upload '$name' file!";
						else {
							$is_zip_url = !empty($zip_urls_tmp_names[ $zip_file["tmp_name"] ]);
							$continue = $is_zip_url ? rename($zip_file["tmp_name"], $zipped_file_path) : move_uploaded_file($zip_file["tmp_name"], $zipped_file_path);
							//echo "<br>is_zip_url:$is_zip_url|<br>$continue|";print_r($zip_file);print_r($zip_urls_tmp_names);die();
							
							if ($continue) {
								//Delete folder in case it exists before, bc we are uploading a new zip and we dont want the old zip files.
								CMSModuleUtil::deleteFolder($dest_file_path);
								
								//unzip
								$unzipped_module_path = CMSModuleInstallationHandler::unzipModuleFile($zipped_file_path, $dest_file_path); //unzipped_module_path is the same than dest_file_path if unzip successfully
								
								if ($unzipped_module_path) {
									//get module info
									$info = CMSModuleInstallationHandler::getUnzippedModuleSettings($unzipped_module_path);
									
									//set new module id
									if ($info && !empty($info["tag"]) && $module_id != $info["tag"])
										$module_id = $info["tag"];
									
									//install module
									$system_presentation_settings_module_path = $system_presentation_settings_path . $module_id;
									$system_presentation_settings_webroot_module_path = $system_presentation_settings_webroot_path . $module_id;
									
									$used_drivers = array();
									
									foreach ($pre_init_configs as $project_name => $pre_init_config) {
										$user_global_variables_file_paths = $pre_init_config ? array($user_global_variables_file_path, $pre_init_config) : $user_global_variables_file_path;
										$PHPVariablesFileHandler = new PHPVariablesFileHandler($user_global_variables_file_paths);
										$PHPVariablesFileHandler->startUserGlobalVariables();
										
										//only get the layers that the $bean_name has access to
										$layers = WorkFlowBeansFileHandler::getLocalBeanLayersFromBrokers($user_global_variables_file_paths, $user_beans_folder_path, $P->getBrokers(), true);
										$layers[$bean_name] = $P;
										//echo "<pre>";print_r($layers);die();
										
										$CMSModuleInstallationHandler = CMSModuleInstallationHandler::createCMSModuleInstallationHandlerObject($layers, $module_id, $system_presentation_settings_module_path, $system_presentation_settings_webroot_module_path, $unzipped_module_path, $selected_db_driver, $UserAuthenticationHandler);
										$CMSModuleInstallationHandler->setUsedDBDrivers($used_drivers);
										$data_access_layer_detected = $CMSModuleInstallationHandler->detectedLayerByClass("DataAccessLayer");
										
										try {
											$s = $CMSModuleInstallationHandler->install();
											
											if ($s) {
												if ($CMSModuleInstallationHandler->existsDataToInstallToDBs() && !$CMSModuleInstallationHandler->areAllDBDriversUsed())
													$messages[$module_id][$project_name][] = array("msg" => "STATUS: OK, BUT NO DB DRIVERS EXECUTED BC THEY WERE ALREADY EXECUTED BEFORE!", "type" => "alert");
												/*else
													$messages[$module_id][$project_name][] = array("msg" => "STATUS: OK", "type" => "ok");*/
											}
											else {
												$status = false;
												$messages[$module_id][$project_name][] = array("msg" => "STATUS: FALSE", "type" => "error");
											}
											
											if (!$data_access_layer_detected)
												$messages[$module_id][$project_name][] = array("msg" => "NO DATA ACCESS LAYERS DETECTED, WHICH MEANS THAT IF THIS MODULE CONTAINS ANY XML FILES WITH SQL QUERIES, THEY WEREN'T COPIED!", "type" => "alert");
											
											if (!empty($messages[$module_id][$project_name]))
												$messages[$module_id][$project_name][] = array("msg" => "GLOBAL SELECTED DB DRIVER: " . (isset($GLOBALS["default_db_driver"]) ? $GLOBALS["default_db_driver"] : null), "type" => "info");
										}
										catch(Exception $e) {
											//set message
											$status = false;
											$messages[$module_id][$project_name][] = array("msg" => "STATUS: FALSE", "type" => "error");
											
											if (!$data_access_layer_detected)
												$messages[$module_id][$project_name][] = array("msg" => "NO DATA ACCESS LAYERS DETECTED, WHICH MEANS THAT IF THIS MODULE CONTAINS ANY XML FILES WITH SQL QUERIES, THEY WEREN'T COPIED!", "type" => "alert");
											
											$messages[$module_id][$project_name][] = array("msg" => "GLOBAL SELECTED DB DRIVER: " . (isset($GLOBALS["default_db_driver"]) ? $GLOBALS["default_db_driver"] : null), "type" => "info");
											$messages[$module_id][$project_name][] = array("msg" => "ERROR MESSAGE: \n" . trim($e->getMessage()), "type" => "exception");
											
											if (!empty($e->problem))
												$messages[$module_id][$project_name][] = array("msg" => "\n" . trim($e->problem), "type" => "exception");
											
											//set ErrorHandler to OK again, so we can call the LayoutTypeProjectHandler->createLayoutTypePermissionsForModuleInLayersFromProjectPath method
											global $GlobalErrorHandler;
											
											if (!$GlobalErrorHandler->ok())
												$GlobalErrorHandler->start();
										}
										
										$module_installation_messages = $CMSModuleInstallationHandler->getMessages();
										if ($module_installation_messages)
											$messages[$module_id][$project_name][] = array("msg" => implode("\n", $module_installation_messages), "type" => "info");
										
										$used_drivers = array_merge($used_drivers, $CMSModuleInstallationHandler->getUsedDBDrivers()); //gets the used db_drivers from this module so it can pass to the next iteration item. Like this if there is a repeated driver the module installer can detected and doesn't need to run the sql queries again...
										
										//add module permission to the correspondent project layout
										if ($CMSModuleInstallationHandler->isModuleInstalled()) {
											if (!$LayoutTypeProjectHandler->createLayoutTypePermissionsForModuleInLayersFromProjectPath($projects[$project_name]["path"], $layers, $module_id))
												$messages[$module_id][$project_name][] = array("msg" => "Could not add the module permission for the selected projects layout types.", "type" => "info");
										}
										
										//freeing cache
										$CMSModuleInstallationHandler->freeModuleCache();
										
										$CMSModuleLayer = $EVC->getCMSLayer()->getCMSModuleLayer();
										$CMSModuleLayer->freeModuleCache();
										
										$PHPVariablesFileHandler->endUserGlobalVariables();
									}
									
									CMSModuleUtil::deleteFolder($unzipped_module_path); //unzipped_module_path is the same than dest_file_path
								}
								
								unlink($zipped_file_path);
							}
							else {
								$status = false;
								$messages[$module_id]["all"][] = array("msg" => "STATUS: FALSE: File '$name' not uploaded!", "type" => "error");
							}
						}
					}
			}
		}
		else 
			$error_message = "Error: Could not upload file. Please try again...";
		
		if ($files_to_close)
			foreach ($files_to_close as $fp)
				fclose($fp);
	}
	else if ($filter_by_layout) { //get default project and db driver for filter_by_layout
		$layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerBeanFolderName($user_beans_folder_path . $bean_file_name, $bean_name, $user_global_variables_file_path);
		
		$selected_project = substr($filter_by_layout, strlen($layer_bean_folder_name) + 1);
		
		$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $selected_project);
		$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config")));
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		$selected_db_driver = isset($GLOBALS["default_db_driver"]) ? $GLOBALS["default_db_driver"] : null;
		
		$PHPVariablesFileHandler->endUserGlobalVariables();
	}
}
?>
