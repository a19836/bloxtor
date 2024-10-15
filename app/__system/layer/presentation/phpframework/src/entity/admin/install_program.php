<?php
include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSProgramInstallationHandler");
include_once get_lib("org.phpframework.util.web.MyCurl");
include_once $EVC->getUtilPath("WorkFlowTestUnitHandler");
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");
include_once $EVC->getUtilPath("LayoutTypeProjectHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$filter_by_layout = isset($_GET["filter_by_layout"]) ? $_GET["filter_by_layout"] : null;
$popup = isset($_GET["popup"]) ? $_GET["popup"] : null;

$step = isset($_POST["step"]) ? $_POST["step"] : null;

$path = str_replace("../", "", $path);//for security reasons
$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons

if ($bean_name && $bean_file_name) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
	
	if ($PEVC) {
		$P = $PEVC->getPresentationLayer();
		$selected_project_id = $P->getSelectedPresentationId();
		
		$layer_object_id = LAYER_PATH . WorkFlowBeansFileHandler::getLayerObjFolderName($P) . "/" . $path; //note that path can be a project folder coming when we create a new project through the presentation/create_project.php
		$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_object_id, "layer", "access");
	}
	else {
		launch_exception(new Exception("Bean layer doesn't exists!"));
		die();
	}
}

if (!$step) {
	if (!empty($P)) {
		//prepare brokers db drivers
		$db_drivers_props = WorkFlowBeansFileHandler::getLayerDBDrivers($user_global_variables_file_path, $user_beans_folder_path, $P, true);
		$db_drivers_names = array_keys($db_drivers_props);
		//echo "<pre>";print_r($db_drivers_names);die();	
	}
}
else if (!empty($_POST)) {
	$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
	
	$LayoutTypeProjectHandler = new LayoutTypeProjectHandler($UserAuthenticationHandler, $user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name);
	
	if ($step >= 3) { //copy program files to the selected layers and execute install. The install can insert data into the selected DB drivers, dependent of the program install class.
		$post_data = isset($_POST["post_data"]) ? json_decode($_POST["post_data"], true) : null;
		$db_drivers =  isset($post_data["db_drivers"]) ? $post_data["db_drivers"] : null;
		$layers =  isset($post_data["layers"]) ? $post_data["layers"] : null;
		$program_name =  isset($post_data["program_name"]) ? $post_data["program_name"] : null;
		$program_label =  isset($post_data["program_label"]) ? $post_data["program_label"] : null;
		$program_with_db =  isset($post_data["program_with_db"]) ? $post_data["program_with_db"] : null;
		$extra_settings = array_diff_key($post_data, array("db_drivers" => 0, "layers" => 0, "program_name" => 0, "continue" => 0, "step" => 0)); //includes overwrite, so do not add: "overwrite" => 0
		//echo "<pre>";print_r($post_data);die();
		//echo "<pre>";print_r($extra_settings);die();
		
		$program_path = CMSProgramInstallationHandler::getTmpRootFolderPath() . $program_name . "/";
		
		if ($step == 3 && !is_dir($program_path))
			$error_message = "Please upload your zip file again...<br>To go back to the upload please click <a href='?" . (isset($_SERVER["QUERY_STRING"]) ? $_SERVER["QUERY_STRING"] : "") . "'>here</a>";
		else if (!$layers && !$db_drivers)
			$error_message = "Error: No Layers or DB Drivers selected!";
		else {
			//prepare layers objects and db drivers objects
			if (!empty($P)) {
				$layer_brokers_settings = WorkFlowBeansFileHandler::getLayerBrokersSettings($user_global_variables_file_path, $user_beans_folder_path, $P->getBrokers());
				$layer_brokers_settings["presentation_brokers"] = array(
					array(
						WorkFlowBeansConverter::getBrokerNameFromRawLabel(WorkFlowBeansFileHandler::getLayerNameFromBeanObject($bean_name, $P)), 
						$bean_file_name, 
						$bean_name
					)
				);
				$layer_brokers_settings["presentation_evc_brokers"] = isset($layer_brokers_settings["presentation_brokers"]) ? $layer_brokers_settings["presentation_brokers"] : null;
			}
			else
				$layer_brokers_settings = WorkFlowTestUnitHandler::getAllLayersBrokersSettings($user_global_variables_file_path, $user_beans_folder_path);
			
			$layer_beans_settings = array();
			$layer_objs = array();
			$layers_brokers_settings = array();
			$db_driver_objs = array();
			$vendors = array();
			$projects = array();
			$projects_evcs = array();
			
			if ($layers) {
				$brokers_db_drivers = array();
				$pre_init_configs = array();
				
				$presentation_evc_brokers = isset($layer_brokers_settings["presentation_evc_brokers"]) ? $layer_brokers_settings["presentation_evc_brokers"] : null;
				$presentation_evc_brokers_by_broker_name = array();
				
				foreach ($presentation_evc_brokers as $bl)
					if (isset($bl[0]))
						$presentation_evc_brokers_by_broker_name[ $bl[0] ] = $bl;
				
				//prepare layers objects
				foreach ($layers as $layer_type => $items) {
					if ($layer_type == "vendor")
						$vendors = array_keys($items);
					else {
						foreach ($items as $broker_name => $layer_props) {
							$brokers_settings = array();
							
							switch ($layer_type) {
								case "ibatis": $brokers_settings = isset($layer_brokers_settings["ibatis_brokers"]) ? $layer_brokers_settings["ibatis_brokers"] : null; break;
								case "hibernate": $brokers_settings = isset($layer_brokers_settings["hibernate_brokers"]) ? $layer_brokers_settings["hibernate_brokers"] : null; break;
								case "businesslogic": $brokers_settings = isset($layer_brokers_settings["business_logic_brokers"]) ? $layer_brokers_settings["business_logic_brokers"] : null; break;
								case "presentation": $brokers_settings = isset($layer_brokers_settings["presentation_brokers"]) ? $layer_brokers_settings["presentation_brokers"] : null; break;
							}
							
							if ($brokers_settings) {
								foreach ($brokers_settings as $bl)
									if (isset($bl[0]) && $bl[0] == $broker_name) {
										$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bl[1], $user_global_variables_file_path);
										$layer_obj = $WorkFlowBeansFileHandler->getBeanObject($bl[2]);
										$layer_brokers_db_drivers = WorkFlowBeansFileHandler::getLayerDBDrivers($user_global_variables_file_path, $user_beans_folder_path, $layer_obj, true);
										
										$layer_beans_settings[$broker_name] = $bl;
										$layer_objs[$broker_name] = $layer_obj;
										$brokers_db_drivers = array_merge($brokers_db_drivers, $layer_brokers_db_drivers);
										
										$layers_brokers_settings[$broker_name] = WorkFlowBeansFileHandler::getLayerBrokersSettings($user_global_variables_file_path, $user_beans_folder_path, $layer_obj->getBrokers());
										$layers_brokers_settings[$broker_name]["db_drivers_brokers"] = $layer_brokers_db_drivers;
										
										if ($layer_type == "presentation") {
											foreach ($layer_props as $project => $project_props) {
												$projects[$broker_name][] = $project;
												$pre_init_configs[] = $layer_objs[$broker_name]->getLayerPathSetting() . "$project/src/config/pre_init_config.php";
												
												$evc_broker_settings = isset($presentation_evc_brokers_by_broker_name[$broker_name]) ? $presentation_evc_brokers_by_broker_name[$broker_name] : null;
												
												if ($evc_broker_settings) {
													$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $evc_broker_settings[1], $user_global_variables_file_path);
													$projects_evcs[$broker_name][$project] = $WorkFlowBeansFileHandler->getEVCBeanObject($evc_broker_settings[2], $project);
												}
											}
										}
										
										break;
									}
							}
						}
					}
				}
				
				//prepare db_drivers objects
				if ($db_drivers && $pre_init_configs)
					foreach ($pre_init_configs as $pre_init_config)
						foreach ($brokers_db_drivers as $broker_name => $bl) 
							if (in_array($broker_name, $db_drivers)) {
								$DBDriverWorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bl[1], array($user_global_variables_file_path, $pre_init_config));
								$DBDriverWorkFlowBeansFileHandler->init();
								$db_settings = $DBDriverWorkFlowBeansFileHandler->getDBSettings($bl[2]);
								
								$db_settings_id = md5(serialize($db_settings));
								
								if (empty($db_driver_objs[ $db_settings_id ]))
									$db_driver_objs[ $db_settings_id ] = $DBDriverWorkFlowBeansFileHandler->getBeanObject($bl[2]);
							}
			}
			else if ($db_drivers) { //prepare db_drivers objects, bc there could be db driver selected without layers selected.
				$db_brokers = isset($layer_brokers_settings["db_brokers"]) ? $layer_brokers_settings["db_brokers"] : null;
				
				if ($db_brokers)
					foreach ($db_brokers as $bl) {
						$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bl[1], $user_global_variables_file_path);
						$layer_obj = $WorkFlowBeansFileHandler->getBeanObject($bl[2]);
						$layer_db_drivers = $layer_obj->getBrokers();
						
						if ($layer_db_drivers)
							foreach ($layer_db_drivers as $db_driver_name => $db_driver_obj)
								if (in_array($db_driver_name, $db_drivers))
									$db_driver_objs[$db_driver_name] = $db_driver_obj;
					}
			}
			
			$db_driver_objs = array_values($db_driver_objs);
			
			//call program installation
			$CMSProgramInstallationHandler = CMSProgramInstallationHandler::createCMSProgramInstallationHandlerObject($EVC, $user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $layer_beans_settings, $layer_objs, $db_driver_objs, $layers_brokers_settings, $vendors, $projects, $projects_evcs, $program_name, $program_path, $post_data, $UserAuthenticationHandler);
			
			$status = false;
			$messages = array();
			$step_post_data = null;
			
			if ($CMSProgramInstallationHandler) {
				if ($step == 3)
					$status = $CMSProgramInstallationHandler->install($extra_settings);
				else {
					$step_post_data = isset($_POST) ? $_POST : null;
					unset($step_post_data["post_data"]);
					unset($step_post_data["step"]);
					unset($step_post_data["continue"]);
					
					$status = $CMSProgramInstallationHandler->installStep($step - 3, $extra_settings, $step_post_data);
				}
				
				$messages = $CMSProgramInstallationHandler->getMessages();
			}
			
			if ($status) {
				$next_step_html = $CMSProgramInstallationHandler->getStepHtml($step - 2, $extra_settings, $step_post_data); //$step - 3 + 1 == $step - 2
				$next_step = $step + 1;
				
				//only if there are no more steps
				if (!$next_step_html) {
					//delete unzipped program path folder
					CacheHandlerUtil::deleteFolder($program_path);
					
					$status_message = "Program '" . ($program_label ? $program_label : $program_name) . "' installed successfully!";
					
					if (empty($db_driver_objs) && $program_with_db)
						$messages[] = "Please note that this program uses a database, but NO databases were selected, which means that the corresponding program's data was not loaded in any database!";
					
					//add program permission to the correspondent project layout
					if (!$LayoutTypeProjectHandler->createLayoutTypePermissionsForProgramInLayersFromProjectPath($projects, $layer_objs, $program_name))
						$messages[] = "There was an error adding the program permission for the selected projects layout types.";
				}
			}
			else {
				$error_message = "There were some errors to install this program. Please try again...";
				$errors = $CMSProgramInstallationHandler ? $CMSProgramInstallationHandler->getErrors() : null;
				
				if ($errors && !empty($errors["files"])) {
					$errors_files = array();
					
					foreach ($errors["files"] as $src_path => $dst_path) {
						if (is_numeric($src_path))
							$errors_files[$src_path] = substr($dst_path, strlen(LAYER_PATH));
						else
							$errors_files[ substr($src_path, strlen($program_path)) ] = substr($dst_path, strlen(LAYER_PATH));
					}
					
					$errors["files"] = $errors_files;
				}
			}
		}
	}
	else if ($step == 2) { //show files that will be copied to the selected layers
		$db_drivers = isset($_POST["db_drivers"]) ? $_POST["db_drivers"] : null;
		$layers = isset($_POST["layers"]) ? $_POST["layers"] : null;
		$program_name = isset($_POST["program_name"]) ? $_POST["program_name"] : null;
		$program_label = isset($_POST["program_label"]) ? $_POST["program_label"] : null;
		$program_with_db = isset($_POST["program_with_db"]) ? $_POST["program_with_db"] : null;
		$overwrite = isset($_POST["overwrite"]) ? $_POST["overwrite"] : null; //used in the UI html
		//echo "<pre>";print_r($_POST);die();
		
		$program_path = CMSProgramInstallationHandler::getTmpRootFolderPath() . $program_name . "/";
		
		if (!is_dir($program_path))
			$error_message = "Please upload your zip file again...<br>To go back to the upload please click <a href='?" . $_SERVER["QUERY_STRING"] . "'>here</a>";
		else if (!$layers && !$db_drivers)
			$error_message = "Error: No Layers or DB Drivers selected!";
		else {
			$all_files = array();
			
			//checks which files already exists
			if ($layers)
				foreach ($layers as $layer_type => $items) {
					if ($layer_type == "vendor") {
						foreach ($items as $file_name => $file_props) {
							if (is_dir("$program_path$layer_type/$file_name")) {
								$suffix = in_array(strtolower($file_name), array("testunit", "dao")) ? "$program_name/" : "";
								$all_files[$file_name] = checkForExistentFiles("$program_path$layer_type/$file_name/", VENDOR_PATH . "$file_name/$suffix", $suffix);
							}
							else
								$all_files[$file_name] = file_exists(VENDOR_PATH . $file_name);
						}
					}
					else
						foreach ($items as $broker_name => $layer_props) {
							$layer_folder_name = WorkFlowBeansConverter::getFileNameFromRawLabel($broker_name);
							$layer_path = LAYER_PATH . "$layer_folder_name/";
							
							if (is_dir($program_path . $layer_type)) { 
								if ($layer_type == "presentation") {
									$all_files[$broker_name] = array();
									$pres_sub_files = array_diff(scandir($program_path . $layer_type), array('..', '.'));
									
									foreach ($layer_props as $project => $project_props) {
										$all_files[$broker_name][$project] = array();
										
										foreach ($pres_sub_files as $pres_sub_file) {
											$suffix = in_array(strtolower($pres_sub_file), array("entity", "view", "block", "util")) ? "$program_name/" : "";
											$project_layer_path = "$layer_path$project/" . (strtolower($pres_sub_file) == "webroot" ? "" : "src/") . "$pres_sub_file/$suffix";
											//echo "$project_layer_path<br>";
											//echo "$pres_sub_file/$suffix<br>";
											
											$all_files[$broker_name][$project] = array_merge($all_files[$broker_name][$project], checkForExistentFiles("$program_path$layer_type/$pres_sub_file/", $project_layer_path, "$pres_sub_file/$suffix"));
										}
									}
								}
								else
									$all_files[$broker_name] = checkForExistentFiles("$program_path$layer_type/", "{$layer_path}program/$program_name/", "program/$program_name/");
							}
						}
				}
			
			//echo "<pre>";print_r($all_files);die();
		}
	}
	else if ($step == 1) { //show available layers and db drivers so the user can select what he wants
		if (!empty($_FILES["program_file"]) || (isset($_POST["program_url"]) && trim($_POST["program_url"]))) {
			$is_program_url = empty($_FILES["program_file"]) && isset($_POST["program_url"]) && trim($_POST["program_url"]);
			
			//download program_url
			if ($is_program_url) {
				$program_url = isset($_POST["program_url"]) ? $_POST["program_url"] : null;
				//echo "<pre>program_url:$program_url\n";die();
				
				$downloaded_file = MyCurl::downloadFile($program_url, $fp);
				
				if ($downloaded_file && isset($downloaded_file["type"]) && stripos($downloaded_file["type"], "zip") !== false)
					$_FILES["program_file"] = $downloaded_file;
			}
			
			//install program file
			if (!empty($_FILES["program_file"]) && isset($_FILES["program_file"]["name"]) && trim($_FILES["program_file"]["name"])) {
				$program_file = $_FILES["program_file"];
				$name = $program_file["name"];
				
				$programs_temp_folder_path = CMSProgramInstallationHandler::getTmpRootFolderPath();
				$zipped_file_path = $programs_temp_folder_path . $name;
				$extension = strtolower( pathinfo($name, PATHINFO_EXTENSION) );
				$dest_file_path = substr($zipped_file_path, 0, -4) . "/";
				$program_name = basename($dest_file_path); //used in the UI html
				
				if ($extension != "zip")
					$error_message = "Error: File '$name' must be a zip file!";
				else if (!is_dir($programs_temp_folder_path) && !mkdir($programs_temp_folder_path, 0755, true))
					$error_message = "Error: trying to create tmp folder to upload '$name' file!";
				else {
					$continue = $is_program_url ? rename($program_file["tmp_name"], $zipped_file_path) : move_uploaded_file($program_file["tmp_name"], $zipped_file_path);
					
					if ($continue) {
						//Delete folder in case it exists before, bc we are uploading a new zip and we dont want the old zip files.
						CacheHandlerUtil::deleteFolder($dest_file_path);
						
						if (CMSProgramInstallationHandler::unzipProgramFile($zipped_file_path, $dest_file_path)) {
							//get xml info
							$info = CMSProgramInstallationHandler::getUnzippedProgramInfo($dest_file_path);
							$program_with_db = false;
							
							if ($info) {
								$program_with_db = isset($info["with_db"]) ? $info["with_db"] : null;
								
								//set new program id
								if (!empty($info["tag"]) && $program_name != $info["tag"]) {
									$program_name = $info["tag"];
									$new_dest_file_path = dirname($dest_file_path) . "/$program_name/";
									
									if (file_exists($new_dest_file_path))
										CacheHandlerUtil::deleteFolder($new_dest_file_path);
									
									if (!file_exists($new_dest_file_path) && rename($dest_file_path, $new_dest_file_path))
										$dest_file_path = $new_dest_file_path;
									else 
										$error_message = "Error: Could not rename unzipped folder with new program id '$program_name';";
								}
							}
							
							if (empty($error_message)) {
								$program_settings = CMSProgramInstallationHandler::getUnzippedProgramSettingsHtml($program_name, $dest_file_path);
								$default_db_driver = null;
								
								//get layer brokers settings
								if (!empty($P)) {
									$brokers_db_drivers = WorkFlowBeansFileHandler::getLayerDBDrivers($user_global_variables_file_path, $user_beans_folder_path, $P, true);
									$LayoutTypeProjectHandler->filterLayerBrokersDBDriversPropsFromLayoutName($brokers_db_drivers, $filter_by_layout); //filter db_drivers by $filter_by_layout
									//echo "<pre>brokers_db_drivers:";print_r($brokers_db_drivers);die();
									
									$layer_brokers_settings = WorkFlowBeansFileHandler::getLayerBrokersSettings($user_global_variables_file_path, $user_beans_folder_path, $P->getBrokers());
									
									$layer_brokers_settings["presentation_brokers"][] = array(
										WorkFlowBeansConverter::getBrokerNameFromRawLabel(WorkFlowBeansFileHandler::getLayerNameFromBeanObject($bean_name, $P)), 
										$bean_file_name, 
										$bean_name
									);
									
									$selected_project_id = $P->getSelectedPresentationId();
									
									//get default db driver
									$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config")));
									$PHPVariablesFileHandler->startUserGlobalVariables();
									
									$default_db_driver = isset($GLOBALS["default_db_driver"]) ? $GLOBALS["default_db_driver"] : null;
									
									$PHPVariablesFileHandler->endUserGlobalVariables();
								}
								else {
									$layer_brokers_settings = WorkFlowTestUnitHandler::getAllLayersBrokersSettings($user_global_variables_file_path, $user_beans_folder_path);
									//echo "<pre>layer_brokers_settings:";print_r($layer_brokers_settings);die();
									
									//filter db_drivers by $filter_by_layout
									$all_db_driver_brokers = isset($layer_brokers_settings["db_driver_brokers"]) ? $layer_brokers_settings["db_driver_brokers"] : null;
									//echo "<pre>all_db_driver_brokers:";print_r($all_db_driver_brokers);die();
									$db_driver_brokers_filtered = array();
									foreach ($all_db_driver_brokers as $db_driver_props)
										if (isset($db_driver_props[0]))
											$db_driver_brokers_filtered[ $db_driver_props[0] ] = $db_driver_props;
									
									$LayoutTypeProjectHandler->filterLayerBrokersDBDriversPropsFromLayoutName($db_driver_brokers_filtered, $filter_by_layout); 
									//echo "<pre>db_driver_brokers_filtered:";print_r($db_driver_brokers_filtered);
									
									//prepare layers db drivers
									$db_brokers = isset($layer_brokers_settings["db_brokers"]) ? $layer_brokers_settings["db_brokers"] : null;
									$brokers_db_drivers = array();
									
									if ($db_brokers)
										foreach ($db_brokers as $bl) {
											$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bl[1], $user_global_variables_file_path);
											$DBData = $WorkFlowBeansFileHandler->getBeanObject($bl[2]);
											$db_drivers = $DBData->getDBDriversName();
											
											if ($db_drivers)
												foreach ($db_drivers as $db_driver_name)
													if (!empty($db_driver_brokers_filtered[$db_driver_name]))
														$brokers_db_drivers[$db_driver_name] = array();
										}
								}
								
								$brokers_db_drivers = $brokers_db_drivers ? array_keys($brokers_db_drivers) : array();
								//echo "<pre>brokers_db_drivers:";print_r($brokers_db_drivers);die();
								
								//prepare layer brokers settings
								$files = array_diff(scandir($dest_file_path), array('..', '.'));
								
								foreach ($files as $file) {
									$fl = strtolower($file);
									
									if (is_dir($dest_file_path . $file))
										switch ($fl) {
											case "ibatis":
												$ibatis_brokers = isset($layer_brokers_settings["ibatis_brokers"]) ? $layer_brokers_settings["ibatis_brokers"] : null;
												break;
											case "hibernate":
												$hibernate_brokers = isset($layer_brokers_settings["hibernate_brokers"]) ? $layer_brokers_settings["hibernate_brokers"] : null;
												break;
											case "businesslogic":
												$business_logic_brokers = isset($layer_brokers_settings["business_logic_brokers"]) ? $layer_brokers_settings["business_logic_brokers"] : null;
												break;
											case "presentation":
												$presentation_brokers = isset($layer_brokers_settings["presentation_brokers"]) ? $layer_brokers_settings["presentation_brokers"] : null;
												
												if (!empty($P) && isset($presentation_brokers[0][2]))
													$presentation_projects = array(
														$presentation_brokers[0][2] => array(
															"projects" => array(
																$selected_project_id => array()
															)
														)
													);
												else {
													$presentation_projects = CMSPresentationLayerHandler::getPresentationLayersProjectsFiles($user_global_variables_file_path, $user_beans_folder_path);
													$LayoutTypeProjectHandler->filterPresentationLayersProjectsByUserAndLayoutPermissions($presentation_projects, $filter_by_layout, UserAuthenticationHandler::$PERMISSION_BELONG_NAME);
												}
												
												//echo "<pre>";print_r($presentation_projects);die();
												break;
											case "vendor":
												$vendor_brokers = array_diff(scandir($dest_file_path . $file), array('..', '.'));;
												break;
										}
								}
								
								if (empty($ibatis_brokers) && empty($hibernate_brokers) && empty($business_logic_brokers) && empty($presentation_brokers))
									$error_message = "Error: Program does not have the correct structure!";
							}
							
							if (!empty($error_message))
								CacheHandlerUtil::deleteFolder($dest_file_path);
						}
						else
							$error_message = "Error: could not unzip uploaded file. Please try again...";
						
						unlink($zipped_file_path);
					}
					else 
						$error_message = "Error: Could not upload file. Please try again...";
				}
				
				if (!empty($error_message))
					$step = null;
			}
			
			if ($is_program_url && $fp)
				fclose($fp);
		}
		else 
			$error_message = "Error: Please upload the file with the program you wish to install.";
	}
}

function checkForExistentFiles($program_folder_path, $dest_folder_path, $dest_folder_name) {
	$all_files = array();
	$program_files = array_diff(scandir($program_folder_path), array('..', '.'));
	
	foreach ($program_files as $file) {
		$all_files["$dest_folder_name$file"] = false;
		
		if (file_exists("$dest_folder_path$file")) {
			if (is_dir($program_folder_path . $file)) {
				$all_files = array_merge($all_files, checkForExistentFiles("$program_folder_path$file/", "$dest_folder_path$file/", "$dest_folder_name$file/"));
				
				$all_files["$dest_folder_name$file"] = true;
			}
			else
				$all_files["$dest_folder_name$file"] = true;
		}
	}
	
	return $all_files;
}

?>
