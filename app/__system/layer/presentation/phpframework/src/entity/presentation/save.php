<?php
include_once get_lib("org.phpframework.util.MyArray");
include_once $EVC->getUtilPath("SequentialLogicalActivitySettingsCodeCreator");
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$path = $_GET["path"];
$file_modified_time = $_GET["file_modified_time"];

$path = str_replace("../", "", $path);//for security reasons

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $path) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
	
	if ($PEVC) {
		$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config")));
		$PHPVariablesFileHandler->startUserGlobalVariables();

		$P = $PEVC->getPresentationLayer();
		$layer_path = $P->getLayerPathSetting();
		$selected_project_id = $P->getSelectedPresentationId();
		$default_extension = $P->getPresentationFileExtension();
		$file_path = trim($layer_path . $path);//it should be a file. Not a folder.

		if (!is_dir($file_path)) {
			$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($file_path, "layer", "access");
			UserAuthenticationHandler::checkUsersMaxNum($UserAuthenticationHandler);
			UserAuthenticationHandler::checkActionsMaxNum($UserAuthenticationHandler);
			
			$folder_path = dirname($file_path);
			if (!is_dir($folder_path))
				mkdir($folder_path, 0755, true);
			
			$file_was_changed = file_exists($file_path) && $file_modified_time && $file_modified_time < filemtime($file_path);
			
			$object = $_POST["object"] ? $_POST["object"] : array();
			
			switch ($file_type) {
				case "save_entity_advanced":
				case "save_view":
				case "save_template":
				case "save_block_advanced":
				case "save_util":
					$code = $object["code"];
					$status = save($PEVC, $file_type, $file_path, $file_was_changed, $code);
					
					//remove resource cache if exists
					if (($file_type == "save_entity_advanced" || $file_type == "save_template") && is_array($status) && $status["status"] === true) {
						$UserCacheHandler = $PEVC->getPresentationLayer()->getPHPFrameWork()->getObject("UserCacheHandler");
						
						if ($UserCacheHandler) {
							if ($file_type == "save_template") {
								$caches_folder = $UserCacheHandler->getRootPath() . "resource_controller/$selected_project_id/";
								CacheHandlerUtil::deleteFolder($caches_folder);
								
								$cache_key = "html_parser/$selected_project_id/template_props_" . md5($file_path);
								$UserCacheHandler->delete($cache_key);
							}
							else {
								$entity_code = substr($path, strpos($path, "/src/entity/") + strlen("/src/entity/"));
								$cache_key = "resource_controller/$selected_project_id/" . str_replace("/", "_", $entity_code); //delete with php extension
								$UserCacheHandler->delete($cache_key);
								
								$entity_code = pathinfo($entity_code, PATHINFO_FILENAME); //get code without php extension
								$cache_key = "html_parser/$selected_project_id/entities_props_" . md5($entity_code) . "_";
								$UserCacheHandler->delete($cache_key, "prefix");
							}
						}
					}
					break;
				
				case "save_config":
					$code = $object["code"];
					$is_config_file = substr($file_path, -22) == "/src/config/config.php";
					
					//Be sure that these variables exist!
					if (!$is_config_file || validateProjectWebrootUrls($PEVC, $code, $error_message)) 
						$status = save($PEVC, $file_type, $file_path, $file_was_changed, $code);
					else
						$status = "You must create the '\$project_url_prefix' and '\$project_common_url_prefix' variables with the correspondent urls!";
					break;
				
				case "save_entity_simple":
					if ($object["sla_settings"] && !$object["sla_settings_code"])
						$object["sla_settings_code"] = SequentialLogicalActivitySettingsCodeCreator::getActionsCode($webroot_cache_folder_path, $webroot_cache_folder_url, $object["sla_settings"], "\t");
					
					$code = CMSPresentationLayerHandler::createEntityCode($object, $selected_project_id, $default_extension);
					$status = save($PEVC, $file_type, $file_path, $file_was_changed, $code);
					
					if (is_array($status) && $status["status"] === true) {
						$SysUserCacheHandler = $PHPFrameWork->getObject("UserCacheHandler"); //$PHPFrameWork is the same than $EVC->getPresentationLayer()->getPHPFrameWork(); //Use EVC instead of PEVC, bc is relative to the __system admin panel
						CMSPresentationLayerHandler::cacheEntitySaveActionTime($PEVC, $SysUserCacheHandler, $cms_page_cache_path_prefix, $file_path, true, $workflow_paths_id, $bean_name);
						
						//remove resource cache if exists
						$UserCacheHandler = $PEVC->getPresentationLayer()->getPHPFrameWork()->getObject("UserCacheHandler");
						
						if ($UserCacheHandler) {
							$entity_code = substr($path, strpos($path, "/src/entity/") + strlen("/src/entity/"));
							$cache_key = "resource_controller/$selected_project_id/" . str_replace("/", "_", $entity_code); //delete with php extension
							$UserCacheHandler->delete($cache_key);
							
							$entity_code = pathinfo($entity_code, PATHINFO_FILENAME); //get code without php extension
							$cache_key = "html_parser/$selected_project_id/entities_props_" . md5($entity_code) . "_";
							$UserCacheHandler->delete($cache_key, "prefix");
						}
					}
					break;
				
				case "save_project_global_variables_advanced":
				case "save_project_global_variables_simple":
				case "save_project_default_template":
					$original_code = file_get_contents($file_path);
					$status = true;
					
					if ($file_type == "save_project_global_variables_advanced")
						$code = $object["code"];
					else if ($file_type == "save_project_default_template") {
						//Remove reserved code from $obj_data["code"]
						$find = '$presentation_id = substr($project_path, strlen($layer_path), -1);';
						$pos = strpos($original_code, $find) + strlen($find);
						$code_aux = "<?php\n" . trim(substr($original_code, $pos)); //trim is very important here, otherwise the isSimpleVarsContent will be false bc of a space char in the beginning...
						$code_aux = str_replace("<?php\n?>", "", $code_aux);
						
						//Remove comments in order to compare with the vars' code
						$is_code_valid = PHPVariablesFileHandler::isSimpleVarsContent($code_aux);
						
						if ($is_code_valid) {
							$vars = PHPVariablesFileHandler::getVarsFromContent($code_aux);
							$vars["project_default_template"] = $object["project_default_template"];
							$code = PHPVariablesFileHandler::getVarsCode($vars);
						}
						else
							$status = false;
					}
					else {
						$vars_name = $object["vars_name"];
						$vars_value = $object["vars_value"];
						
						$global_variables = array();
						
						if ($vars_name) {
							$t = count($vars_name);
							for($i = 0; $i < $t; $i++) {
								$var_name = $vars_name[$i];
								$var_value = $vars_value[$i];
								
								if ($var_name == "log_level" && !strlen($var_value))
									continue 1;
								
								if ($var_value != "__DEFAULT__") {
									$var_value_lower = strtolower($var_value);
									
									if ($var_value_lower == "true")
										$global_variables[$var_name] = true;
									else if ($var_value_lower == "false")
										$global_variables[$var_name] = false;
									else if ($var_value_lower == "null")
										$global_variables[$var_name] = null;
									else
										$global_variables[$var_name] = $var_value;
								}
							}
						}
						
						$code = PHPVariablesFileHandler::getVarsCode($global_variables);
					}
					
					if ($status) {
						//Add reserved code, this is, add the code for the $presentation_id variable
						$find = '$presentation_id = substr($project_path, strlen($layer_path), -1);';
						$pos = strpos($original_code, $find) + strlen($find);
						$reserved_code = trim(substr($original_code, strlen("<?php"), $pos - strlen("<?php")));
						
						if (!$reserved_code)
							$reserved_code = '//Do not change any of these variables bc when a project is created, I\'m changing this code based in str_replace.
	$project_path = dirname(dirname(str_replace(DIRECTORY_SEPARATOR, "/", __DIR__))) . "/";
	$layer_path = dirname($project_path) . "/";

	//Note that this project could be inside of folders and sub-folders
	$presentation_id = substr($project_path, strlen($layer_path), -1);';
						
						$code = "<?php\n" . $reserved_code . "\n?>" . $code;
						$code = str_replace("?><?", "", str_replace("?><?php", "", $code));
						//$code = "<?php\n" . $reserved_code . "\n\n" . trim(substr($code, strlen("<?php")));
						//error_log($code, 3, "/tmp/tmp.log");
						
						$status = save($PEVC, $file_type, $file_path, $file_was_changed, $code);
					}
					break;
				
				case "save_page_module_block":
				case "save_block_simple":
					if ($file_type == "save_page_module_block") {
						$new_path = str_replace("//", "/", "$selected_project_id/" . $P->settings["presentation_blocks_path"] . "/");
						$entity_path_prefix = str_replace("//", "/", "$selected_project_id/" . $P->settings["presentation_entities_path"] . "/");
						$entity_path_pos = strpos($path, $entity_path_prefix);
						
						if ($entity_path_pos === 0) {
							$new_path .= substr($path, strlen($entity_path_prefix));
							$new_path = $new_path ? dirname($new_path) . "/" : "";
						}
						
						//sets file name with the name of the module
						$file_path = trim($layer_path . $new_path) . trim(strtolower(str_replace("/", "_", $object["module_id"]))) . ".php";
						
						//check if file folder exists, and if not create it
						$folder_path = dirname($file_path);
						if (!is_dir($folder_path))
							mkdir($folder_path, 0755, true);
						
						//checks if already exists and creates new a name
						CMSPresentationLayerHandler::configureUniqueFileId($file_path);
						
						$file_was_changed = file_exists($file_path) && $file_modified_time && $file_modified_time < filemtime($file_path);
					}
					
					$code = CMSPresentationLayerHandler::createBlockCode($object);
					
					$status = save($PEVC, $file_type, $file_path, $file_was_changed, $code);
					
					if ($file_type == "save_page_module_block" && is_array($status) && $status["status"] === true) 
						$status["block_id"] = substr(str_replace($PEVC->getBlocksPath(), "", $file_path), 0, (strlen($default_extension) + 1) * -1);
					
					break;
			}
			
			if ($status && is_array($status) && $status["status"] === true)
				$UserAuthenticationHandler->incrementUsedActionsTotal();
		}
		
		$PHPVariablesFileHandler->endUserGlobalVariables();
	}
}

echo json_encode($status);
die();

function save($PEVC, $file_type, $file_path, $file_was_changed, $code) {
	if ($file_was_changed) 
		$status = array(
			"status" => "CHANGED", 
			"old_code" => file_get_contents($file_path),
			"new_code" => $code,
		);
	else {
		$status = PHPScriptHandler::isValidPHPContents($code, $output) ? file_put_contents($file_path, $code) !== false : false;
		
		if ($status) {
			clearstatcache(true, $file_path); //very important otherwise the filemtime will contain the old modified time.
			
			$status = array(
				"status" => true,
				"modified_time" => filemtime($file_path),
			);
		}
		else if ($output)
			$status = $output;
	}
	
	return $status;
}

function validateProjectWebrootUrls($EVC, $code, &$error_message) {
	try {
		$code = trim($code);
		
		if ($code) {
			$code = substr($code, 0, 5) == "<?php" ? substr($code, 5) : (substr($code, 0, 3) == "<?" ? substr($code, 3) : $code);
			$code = substr($code, -2) == "?>" ? substr($code, 0, -2) : $code;
			
			eval($code);
		}
	}
	catch(Exception $e) {
		$error_message = $e->getMessage();
	}
	
	return isset($project_url_prefix) && isset($project_common_url_prefix);
}
?>
