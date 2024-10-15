<?php
include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSModuleUtil");
include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSTemplateInstallationHandler");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");
include_once $EVC->getUtilPath("LayoutTypeProjectHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");
UserAuthenticationHandler::checkUsersMaxNum($UserAuthenticationHandler);

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$item_type = isset($_GET["item_type"]) ? $_GET["item_type"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$action = isset($_GET["action"]) ? $_GET["action"] : null;
$extra = isset($_GET["extra"]) ? trim($_GET["extra"]) : null;
$folder_type = isset($_GET["folder_type"]) ? $_GET["folder_type"] : null;
$filter_by_layout = isset($_GET["filter_by_layout"]) ? $_GET["filter_by_layout"] : null;

$path = str_replace("../", "", $path);//for security reasons
$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons

$root_path = getRootPath($bean_name, $bean_file_name, $item_type, $path, $user_beans_folder_path, $user_global_variables_file_path, $obj);
$status = false;

if ($root_path) {
	$orig_path = $path;
	$path = $root_path . $path;
	$layer_object_id = $item_type == "dao" ? "vendor/dao/$orig_path" : ($item_type == "vendor" || $item_type == "other" ? "$item_type/$orig_path" : ($item_type == "test_unit" ? "vendor/testunit/$orig_path" : $path));
	
	if ($path && (file_exists($path) || $action == "create_folder" || $action == "create_file" || $action == "remove" || $action == "paste_and_remove") ) {
		$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_object_id, "layer", "access");
		
		$LayoutTypeProjectHandler = new LayoutTypeProjectHandler($UserAuthenticationHandler, $user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name);
		
		switch($action) {
			case "create_folder":
				$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
				
				if ($extra) {
					$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("$layer_object_id/$extra", "layer", "access");
					
					if (!file_exists($path))
						mkdir($path, 0755, true);
					
					if (file_exists($path)) {
						$dest = "$path/$extra";
						
						if ($item_type == "presentation" && $folder_type == "project") {//create project
							if (isProjectCreationAllowed($EVC, $user_global_variables_file_path, $user_beans_folder_path)) {
								//Note that the $extra could contain sub-folders but there is no problem bc the system is already prepared for this.
								$status = CMSModuleUtil::copyFolder($EVC->getPresentationLayer()->getLayerPathSetting() . "empty/", $dest);
								
								if ($status) {
									//Preparing init files
									$status = prepareProjectsInitFiles($root_path, "", $dest, $user_beans_folder_path, $user_global_variables_file_path, $bean_file_name, $bean_name, $LayoutTypeProjectHandler);
									
									/*$orig_path = dirname($orig_path . "/$extra");
									$orig_path = str_replace("//", "/", $orig_path);
									$orig_path = substr($orig_path, 0, 1) == "/" ? substr($orig_path, 1) : $orig_path;
									$orig_path = substr($orig_path, -1) == "/" ? substr($orig_path, 0, -1) : $orig_path;
									$parts = explode("/", $orig_path);
									
									if ($parts && $orig_path) { //only if $orig_path has some value, otherwise stays with the default.
										$prefix = $suffix = "";
										for ($i = 0; $i < count($parts); $i++) {
											$prefix .= "dirname(";
											$suffix .= ")";
										}
										
										if (empty($obj->settings["presentation_configs_path"]))
											launch_exception(new Exception("'PresentationLayer->settings[presentation_configs_path]' cannot be undefined!"));
										
										if (empty($obj->settings["presentation_webroot_path"]))
											launch_exception(new Exception("'PresentationLayer->settings[presentation_webroot_path]' cannot be undefined!"));
										
										//fix dirs for init.php
										$init_path = $path . "$extra/" . $obj->settings["presentation_configs_path"] . "init.php";
										$contents = file_get_contents($init_path);
										//change code to include correct parent init.php
										$contents = str_replace('include dirname(dirname(dirname(__DIR__))) . "/init.php";', 'include ' . $prefix . 'dirname(dirname(dirname(__DIR__)))' . $suffix . ' . "/init.php";', $contents);
										$status_1 = file_put_contents($init_path, $contents) !== false;
										
										//fix dirs for pre_init_config.php
										$pre_init_config_path = $path . "$extra/" . $obj->settings["presentation_configs_path"] . "pre_init_config.php";
										$contents = file_get_contents($pre_init_config_path);
										//change layer_path and automatically the presentation id will be correct
										$contents = str_replace('$layer_path = dirname($project_path)', '$layer_path = ' . $prefix . 'dirname($project_path)' . $suffix, $contents);
										$status_2 = file_put_contents($pre_init_config_path, $contents) !== false;
										
										//fix dirs for script.php
										$script_path = $path . "$extra/" . $obj->settings["presentation_webroot_path"] . "script.php";
										$contents = file_get_contents($script_path);
										//change layer_path and automatically the presentation id will be correct
										$contents = str_replace('include dirname(dirname(__DIR__))', 'include ' . $prefix . 'dirname(dirname(__DIR__))' . $suffix, $contents);
										$status_3 = file_put_contents($script_path, $contents) !== false;
										
										
										$status = $status_1 && $status_2 && $status_3;
									}*/
									
									//if path is a project folder, create layout_type and permissions for this project
									if ($status && is_dir($dest) && $LayoutTypeProjectHandler->isPathAPresentationProjectPath($dest))
										$status = prepareProjectCreationIfApply($LayoutTypeProjectHandler, $dest);
								}
							}
							else {
								//php -r '$string="Projects creation not allowed! You exceed the maximum number of projects that your licence allows."; for($i=0; $i < strlen($string); $i++) echo dechex(ord($string[$i]));echo "\n";'
								$enc_msg = "50726f6a65637473206372656174696f6e206e6f7420616c6c6f7765642120596f752065786365656420746865206d6178696d756d206e756d626572206f662070726f6a65637473207468617420796f7572206c6963656e636520616c6c6f77732e";
								$status = "";
								
								for ($i = 0, $l = strlen($enc_msg); $i < $l; $i += 2)
									$status .= chr( hexdec($enc_msg[$i] . ($i+1 < $l ? $enc_msg[$i+1] : "") ) );
							}
						}
						else if (!file_exists($dest)) {
							$status = mkdir($dest, 0755, true);
							
							//create correspondent permission item for the the selected layout type
							if (is_dir($dest) && $filter_by_layout && ($item_type == "businesslogic" || $item_type == "ibatis" || $item_type == "hibernate" || ($item_type == "presentation" && $folder_type == "project_folder"))) //only do this if there is no parent with permission
								$status = $LayoutTypeProjectHandler->createLayoutTypePermissionsForFilePathAndLayoutTypeName($filter_by_layout, $dest);
						}
					}
					//else already exists
				}
				
				break;
			case "create_file":
				$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
				
				if ($extra) {
					$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("$layer_object_id/$extra", "layer", "access");
					
					$path_info = pathinfo($extra);
					if (empty($path_info["extension"])) {
						if ($item_type == "ibatis" || $item_type == "hibernate")
							$extra .= ".xml";
						else if ($item_type == "businesslogic" || $item_type == "presentation")
							$extra .= ".php";
					}
					
					$allowed = true;
					
					//if filter_by_layout is active (which means that is inside of the Low-Code or Citizen-Dev UI), the user can only create files for the paths that belong to the selected project.
					if ($filter_by_layout && ($item_type == "businesslogic" || $item_type == "ibatis" || $item_type == "hibernate")) {
						$UserAuthenticationHandler->loadLayoutPermissions($filter_by_layout, UserAuthenticationHandler::$LAYOUTS_TYPE_FROM_PROJECT_ID);
						
						if (!$UserAuthenticationHandler->isLayoutInnerFilePermissionAllowed($path, $filter_by_layout, "layer", UserAuthenticationHandler::$PERMISSION_BELONG_NAME, false, false))
							$allowed = false;
					}
					
					if ($allowed) {
						if (!file_exists($path))
							mkdir($path, 0755, true);
						
						if (file_exists($path)) {
							$status = file_exists("$path/$extra") || file_put_contents("$path/$extra", "") !== false;
							
							//add template to template.xml
							if ($status && !is_dir("$path/$extra") && pathinfo($extra, PATHINFO_EXTENSION) == "php" && isPresentationTemplateFile($obj, $item_type, $root_path, "$path/$extra") && !addPresentationTemplateLayoutToXml($obj, $item_type, $root_path, "$path/$extra"))
								$status = false;
						}
					}
					else {
						header((!empty($_SERVER["SERVER_PROTOCOL"]) ? $_SERVER["SERVER_PROTOCOL"] : "HTTP/1.0") . " 405 Not Allowed");
						$status = "You are not allowed to create a file inside of this folder, because it does NOT belong to the selected project!";
					}
				}
				
				break;
			case "rename":
				$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
				
				if ($extra) {
					$UserAuthenticationHandler->checkInnerFilePermissionAuthentication(dirname($layer_object_id) . "/$extra", "layer", "access");
					
					//prepare dst
					$is_obj_class = $item_type == "businesslogic" || $item_type == "test_unit" || $item_type == "dao" || ($item_type == "presentation" && strpos($path, "/util/") !== false);
					
					if ($is_obj_class && !is_dir($path))
						$extra = preg_replace("/\s+/", "", $extra); //replaces white spaces
					
					if (basename($path) == $extra) 
						$status = true;
					else { 
						//project_common is not editable
						if (isPresentationProjectCommon($obj, $item_type, $root_path, $path))
							$status = "You cannot rename the Common project!";
						else if (isLayerDefaultFolder($obj, $item_type, $root_path, $path))
							$status = "You cannot rename this folder because is a default folder!";
						else if (isLayerDefaultFile($obj, $item_type, $root_path, $path))
							$status = "You cannot rename this file because is a default file!";
						else {
							$dst = dirname($path) . "/$extra";
							
							//prepare class rename if apply
							$is_class_rename = $is_obj_class && !is_dir($path) && basename($path) != basename($dst);
							$class_data = $is_class_rename ? PHPCodePrintingHandler::getClassOfFile($path) : null;
							
							//prepare dst folder
							$dst_folder = dirname($dst);
							
							$allowed = true;
							
							//if filter_by_layout is active (which means that is inside of the Low-Code or Citizen-Dev UI), the user can only create files for the paths that belong to the selected project.
							if ($filter_by_layout && ($item_type == "businesslogic" || $item_type == "ibatis" || $item_type == "hibernate")) {
								$UserAuthenticationHandler->loadLayoutPermissions($filter_by_layout, UserAuthenticationHandler::$LAYOUTS_TYPE_FROM_PROJECT_ID);
								
								if (!$UserAuthenticationHandler->isLayoutInnerFilePermissionAllowed($dst_folder != dirname($path) ? $dst_folder : $path, $filter_by_layout, "layer", UserAuthenticationHandler::$PERMISSION_BELONG_NAME, false, false))
									$allowed = false;
							}
							
							if ($allowed) {
								if (!is_dir($dst_folder))
									mkdir($dst_folder, 0755, true);
								
								//rename file
								$status = !file_exists($dst) && is_dir($dst_folder) ? rename($path, $dst) : $dst == $path;
								
								if ($status) {
									//rename class
									if ($is_class_rename && $class_data) {
										$class_data_name = isset($class_data["name"]) ? $class_data["name"] : null;
										$class_data_namespace = isset($class_data["namespace"]) ? $class_data["namespace"] : null;
										
										$src_class_name = PHPCodePrintingHandler::prepareClassNameWithNameSpace($class_data_name, $class_data_namespace);
										$dst_class_name = PHPCodePrintingHandler::prepareClassNameWithNameSpace(pathinfo($dst, PATHINFO_FILENAME), $class_data_namespace);
										
										$status = PHPCodePrintingHandler::renameClassFromFile($dst, $src_class_name, $dst_class_name);
									}
									
									if ($item_type == "presentation") { 
										//Preparing init files
										if (is_dir($dst))
											$status = prepareProjectsInitFiles($root_path, $path, $dst, $user_beans_folder_path, $user_global_variables_file_path, $bean_file_name, $bean_name, $LayoutTypeProjectHandler);
										
										//update diagram if apply
										updateDiagramWorkflowFile($action, $bean_name, $workflow_paths_id, $root_path, $path, $dst);
										
										//if path is a project folder and rename was successful, update correspondent layout_type.
										if (is_dir($dst)) {
											//Don't change $status bc layout_type may not exist (bc maybe the user removed it previously on purpose)!
											if ($LayoutTypeProjectHandler->isPathAPresentationProjectPath($dst))
												$LayoutTypeProjectHandler->renameLayoutFromProjectPath($path, $dst); 
											else if ($LayoutTypeProjectHandler->isPathAPresentationProjectFolderPath($dst))
												$LayoutTypeProjectHandler->renameLayoutFromProjectFolderPath($path, $dst);
										}
										
										//rename template layout from template.xml
										if ($status && !is_dir($dst) && pathinfo($dst, PATHINFO_EXTENSION) == "php" && isPresentationTemplateFile($obj, $item_type, $root_path, $dst) && (!addPresentationTemplateLayoutToXml($obj, $item_type, $root_path, $dst) || !removePresentationTemplateLayoutToXml($obj, $item_type, $root_path, $path)))
											$status = false;
									}
									
									//rename correspondent permission items
									if ($item_type == "presentation" || $item_type == "businesslogic" || $item_type == "ibatis" || $item_type == "hibernate")
										$LayoutTypeProjectHandler->renameLayoutTypePermissionsForFilePath($path, $dst); //Don't change $status bc may not be any permission items
								}
							}
							else {
								header((!empty($_SERVER["SERVER_PROTOCOL"]) ? $_SERVER["SERVER_PROTOCOL"] : "HTTP/1.0") . " 405 Not Allowed");
								$status = "You are not allowed to rename a file inside of this folder, because it does NOT belong to the selected project!";
							}
						}
					}
				}
				
				break;
			case "remove":
				$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "delete");
				
				if ($path != $root_path) {
					$allowed = true;
					
					$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($path, "layer", "access");
					
					//if filter_by_layout is active (which means that is inside of the Low-Code or Citizen-Dev UI), the user can only create files for the paths that belong to the selected project.
					if ($filter_by_layout && ($item_type == "businesslogic" || $item_type == "ibatis" || $item_type == "hibernate")) {
						$UserAuthenticationHandler->loadLayoutPermissions($filter_by_layout, UserAuthenticationHandler::$LAYOUTS_TYPE_FROM_PROJECT_ID);
						
						if (!$UserAuthenticationHandler->isLayoutInnerFilePermissionAllowed($path, $filter_by_layout, "layer", UserAuthenticationHandler::$PERMISSION_BELONG_NAME, false, false))
							$allowed = false;
					}
					
					if ($allowed) {
						if (is_dir($path)) {
							//project_common is not removable
							if (isPresentationProjectCommon($obj, $item_type, $root_path, $path))
								$status = "You cannot remove the Common project!";
							else if (isLayerDefaultFolder($obj, $item_type, $root_path, $path))
								$status = "You cannot remove this folder because is a default folder!";
							else if (isLayerDefaultFile($obj, $item_type, $root_path, $path))
								$status = "You cannot remove this file because is a default file!";
							else {
								if ($item_type == "presentation") {
									//remove diagram if apply
									removeDiagramWorkflowFile($action, $bean_name, $workflow_paths_id, $root_path, $path);
									
									//if path is a project folder, delete correspondent layout_type and correspondent permissions.
									//Don't change $status bc layout_type may not exist (bc maybe the user removed it previously on purpose)!
									if ($LayoutTypeProjectHandler->isPathAPresentationProjectPath($path))
										$LayoutTypeProjectHandler->removeLayoutFromProjectPath($path); 
									else if ($LayoutTypeProjectHandler->isPathAPresentationProjectFolderPath($path))
										$LayoutTypeProjectHandler->removeLayoutFromProjectFolderPath($path);
								}
								
								//remove correspondent permission items
								if ($item_type == "presentation" || $item_type == "businesslogic" || $item_type == "ibatis" || $item_type == "hibernate")
									$LayoutTypeProjectHandler->removeLayoutTypePermissionsForFilePath($path); //Don't change $status bc may not be any permission items
								
								$status = CMSModuleUtil::deleteFolder($path);
							}
						}
						else {
							$status = !file_exists($path) || unlink($path);
							
							//remove template from template.xml
							if ($status && pathinfo($path, PATHINFO_EXTENSION) == "php" && isPresentationTemplateFile($obj, $item_type, $root_path, $path) && !removePresentationTemplateLayoutToXml($obj, $item_type, $root_path, $path))
								$status = false;
						}
					}
					else {
						header((!empty($_SERVER["SERVER_PROTOCOL"]) ? $_SERVER["SERVER_PROTOCOL"] : "HTTP/1.0") . " 405 Not Allowed");
						$status = "You are not allowed to remove a file inside of this folder, because it does NOT belong to the selected project!";
					}
				}
				
				break;
			case "zip":
				$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
				
				$dest = $extra ? "$root_path/$extra/" : dirname($path);
				
				if ($dest) {
					$file_name = basename($path);
					$allowed = true;
					
					$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("$dest/$file_name.zip", "layer", "access");
					
					//if filter_by_layout is active (which means that is inside of the Low-Code or Citizen-Dev UI), the user can only create files for the paths that belong to the selected project.
					if ($filter_by_layout && ($item_type == "businesslogic" || $item_type == "ibatis" || $item_type == "hibernate")) {
						$UserAuthenticationHandler->loadLayoutPermissions($filter_by_layout, UserAuthenticationHandler::$LAYOUTS_TYPE_FROM_PROJECT_ID);
						
						if (!$UserAuthenticationHandler->isLayoutInnerFilePermissionAllowed($dest, $filter_by_layout, "layer", UserAuthenticationHandler::$PERMISSION_BELONG_NAME, false, false))
							$allowed = false;
					}
					
					if ($allowed) {
						if (!is_dir($dest))
							mkdir($dest, 0755, true);
						
						include_once get_lib("org.phpframework.compression.ZipHandler");
						
						$dest = "$dest/$file_name.zip";
						$status = ZipHandler::zip($path, $dest);
					}
					else {
						header((!empty($_SERVER["SERVER_PROTOCOL"]) ? $_SERVER["SERVER_PROTOCOL"] : "HTTP/1.0") . " 405 Not Allowed");
						$status = "You are not allowed to zip a file inside of this folder, because it does NOT belong to the selected project!";
					}
				}
				break;
			case "unzip":
				$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
				UserAuthenticationHandler::checkActionsMaxNum($UserAuthenticationHandler);
				
				$dest = $extra ? "$root_path/$extra/" : dirname($path);
				
				if ($dest && strtolower(pathinfo($path, PATHINFO_EXTENSION)) == "zip") {
					$allowed = true;
					
					$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($dest, "layer", "access");
					
					//if filter_by_layout is active (which means that is inside of the Low-Code or Citizen-Dev UI), the user can only create files for the paths that belong to the selected project.
					if ($filter_by_layout && ($item_type == "businesslogic" || $item_type == "ibatis" || $item_type == "hibernate")) {
						$UserAuthenticationHandler->loadLayoutPermissions($filter_by_layout, UserAuthenticationHandler::$LAYOUTS_TYPE_FROM_PROJECT_ID);
						
						if (!$UserAuthenticationHandler->isLayoutInnerFilePermissionAllowed($dest, $filter_by_layout, "layer", UserAuthenticationHandler::$PERMISSION_BELONG_NAME, false, false))
							$allowed = false;
					}
					
					if ($allowed) {
						if (!is_dir($dest))
							mkdir($dest, 0755, true);
						
						$old_sub_files = is_dir($dest) ? array_diff(scandir($dest), array('..', '.')) : array();
						
						include_once get_lib("org.phpframework.compression.ZipHandler");
						
						$status = ZipHandler::unzip($path, $dest);
						
						if ($status) {
							$UserAuthenticationHandler->incrementUsedActionsTotal();
							
							//if path is a project folder, create layout_type and permissions for this project
							if ($item_type == "presentation" && is_dir($dest)) {
								$new_sub_files = array_diff(scandir($dest), array('..', '.'));
								$diff_sub_files = array_diff($new_sub_files, $old_sub_files);
								
								foreach ($diff_sub_files as $sub_file) {
									$sub_file_dest = "$dest/$sub_file";
									
									//Don't change $status bc layout_type may not exist (bc maybe the user removed it previously on purpose)!
									if ($LayoutTypeProjectHandler->isPathAPresentationProjectPath($sub_file_dest))
										prepareProjectCreationIfApply($LayoutTypeProjectHandler, $sub_file_dest);
									else if ($LayoutTypeProjectHandler->isPathAPresentationProjectFolderPath($sub_file_dest))
										prepareProjectFolderCreationIfApply($LayoutTypeProjectHandler, $sub_file_dest);
								}
							}
						}
					}
					else {
						header((!empty($_SERVER["SERVER_PROTOCOL"]) ? $_SERVER["SERVER_PROTOCOL"] : "HTTP/1.0") . " 405 Not Allowed");
						$status = "You are not allowed to unzip a file inside of this folder, because it does NOT belong to the selected project!";
					}
				}
				break;
			case "upload":
				$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
				UserAuthenticationHandler::checkActionsMaxNum($UserAuthenticationHandler);
				
				if (is_dir($path) && !empty($_FILES["file"]) && isset($_FILES['file']['name'])) {
					$file_name = basename($_FILES['file']['name']);
					$allowed = true;
					
					$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("$layer_object_id/$file_name", "layer", "access");
					
					//if filter_by_layout is active (which means that is inside of the Low-Code or Citizen-Dev UI), the user can only create files for the paths that belong to the selected project.
					if ($filter_by_layout && ($item_type == "businesslogic" || $item_type == "ibatis" || $item_type == "hibernate")) {
						$UserAuthenticationHandler->loadLayoutPermissions($filter_by_layout, UserAuthenticationHandler::$LAYOUTS_TYPE_FROM_PROJECT_ID);
						
						if (!$UserAuthenticationHandler->isLayoutInnerFilePermissionAllowed($path, $filter_by_layout, "layer", UserAuthenticationHandler::$PERMISSION_BELONG_NAME, false, false))
							$allowed = false;
					}
					
					if ($allowed) {
						$status = move_uploaded_file( $_FILES['file']['tmp_name'], $path . "/" . $file_name);
						
						if (!$status) {
							header((!empty($_SERVER["SERVER_PROTOCOL"]) ? $_SERVER["SERVER_PROTOCOL"] : "HTTP/1.0") . " 500 Internal Server Error");
							$status = "Internal Server Error";
						}
						else
							$UserAuthenticationHandler->incrementUsedActionsTotal();
					}
					else {
						header((!empty($_SERVER["SERVER_PROTOCOL"]) ? $_SERVER["SERVER_PROTOCOL"] : "HTTP/1.0") . " 405 Not Allowed");
						$status = "You are not allowed to upload a file inside of this folder, because it does NOT belong to the selected project!";
					}
				}
				break;
			case "paste":
			case "paste_and_remove":
				$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
				
				if ($action == "paste_and_remove")
					$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "delete");
				
				$extra = explode(",", str_replace(array("[", "]"), "", $extra));
				
				if (count($extra) >= 4) {
					$bn = isset($extra[0]) ? $extra[0] : null;//bean_name
					$bfn = isset($extra[1]) ? $extra[1] : null;//bean_file_name
					$fp = isset($extra[2]) ? $extra[2] : null;//file_path
					$it = isset($extra[3]) ? $extra[3] : null;//item_type
					
					$fp = str_replace("../", "", $fp);//for security reasons
					$rp = getRootPath($bn, $bfn, $it, $fp, $user_beans_folder_path, $user_global_variables_file_path);
					
					$src = $rp . $fp;
					$dst = $path . "/" . basename($src);
					
					//prepare dst folder
					$dst_folder = dirname($dst) . "/";
					
					$allowed = true;
					
					$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($dst_folder, "layer", "access");
					
					//if filter_by_layout is active (which means that is inside of the Low-Code or Citizen-Dev UI), the user can only create files for the paths that belong to the selected project.
					if ($filter_by_layout && ($item_type == "businesslogic" || $item_type == "ibatis" || $item_type == "hibernate")) {
						$UserAuthenticationHandler->loadLayoutPermissions($filter_by_layout, UserAuthenticationHandler::$LAYOUTS_TYPE_FROM_PROJECT_ID);
						
						if (!$UserAuthenticationHandler->isLayoutInnerFilePermissionAllowed($dst_folder, $filter_by_layout, "layer", UserAuthenticationHandler::$PERMISSION_BELONG_NAME, false, false))
							$allowed = false;
					}
					
					if ($allowed) {
						if (!is_dir($dst_folder))
							mkdir($dst_folder, 0755, true);
						
						if (is_dir($dst_folder)) {
							if ($action == "paste_and_remove" && isPresentationProjectCommon($obj, $item_type, $root_path, $src))
								$status = "You cannot cut the Common project!";
							else if ($action == "paste_and_remove" && isLayerDefaultFolder($obj, $item_type, $root_path, $src))
								$status = "You cannot cut this folder because is a default folder!";
							else if ($action == "paste_and_remove" && isLayerDefaultFile($obj, $item_type, $root_path, $src))
								$status = "You cannot cut this file because is a default file!";
							else {
								$path_info = pathinfo($src);
								$idx = 1;
								while (file_exists($dst)) {
									$dst = $path . "/" . $path_info["filename"] . "_" . $idx . (!empty($path_info["extension"]) ? "." . $path_info["extension"] : "");
									$idx++;
								}
								
								if ($action == "paste_and_remove")
									$status = rename($src, $dst);
								else
									$status = is_dir($src) ? WorkFlowBeansFolderHandler::copyFolder($src . "/", $dst . "/") : copy($src, $dst);
								
								//Rename service name according with file name.
								if ($status) {
									$is_obj_class = $item_type == "businesslogic" || $item_type == "test_unit" || $item_type == "dao" || ($item_type == "presentation" && strpos($path, "/util/") !== false);
									
									if ($is_obj_class && !is_dir($src) && basename($src) != basename($dst)) {
										$src_classes = PHPCodePrintingHandler::getPHPClassesFromFile($action == "paste_and_remove" ? $dst : $src);
										unset($src_classes[0]);
										
										if ($src_classes) {
											$idx--;
											
											foreach ($src_classes as $cn => $c) {
												$c_name = isset($c["name"]) ? $c["name"] : null;
												$c_namespace = isset($c["namespace"]) ? $c["namespace"] : null;
												
												$src_class_name = PHPCodePrintingHandler::prepareClassNameWithNameSpace($c_name, $c_namespace);
												$dst_class_name = PHPCodePrintingHandler::prepareClassNameWithNameSpace($c_name . "_$idx", $c_namespace);
												
												if (!PHPCodePrintingHandler::renameClassFromFile($dst, $src_class_name, $dst_class_name))
													$status = false;
											}
										}
										//else do nothing bc it could be a file with only functions or with something else
									}
									
									if ($item_type == "presentation") { 
										//Preparing init files
										if (is_dir($dst))
											$status = prepareProjectsInitFiles($root_path, $src, $dst, $user_beans_folder_path, $user_global_variables_file_path, $bean_file_name, $bean_name, $LayoutTypeProjectHandler);
										
										//update diagram if apply
										updateDiagramWorkflowFile($action, $bean_name, $workflow_paths_id, $root_path, $src, $dst);
										
										//if path is a project folder and moved/copied was successful, update correspondent layout_type.
										if (is_dir($dst)) {
											//Don't change $status bc layout_type may not exist (bc maybe the user removed it previously on purpose)!
											
											if ($LayoutTypeProjectHandler->isPathAPresentationProjectPath($dst)) {
												if ($action == "paste_and_remove") //cut
													$LayoutTypeProjectHandler->renameLayoutFromProjectPath($src, $dst);
												else //copy
													prepareProjectCreationIfApply($LayoutTypeProjectHandler, $dst);
											}
											else if ($LayoutTypeProjectHandler->isPathAPresentationProjectFolderPath($dst)) {
												if ($action == "paste_and_remove") //cut
													$LayoutTypeProjectHandler->renameLayoutFromProjectFolderPath($src, $dst);
												else //copy
													prepareProjectFolderCreationIfApply($LayoutTypeProjectHandler, $dst);
											}
										}
									}
									
									//rename correspondent permission items if cut action
									if ($action == "paste_and_remove" && ($item_type == "presentation" || $item_type == "businesslogic" || $item_type == "ibatis" || $item_type == "hibernate"))
										$LayoutTypeProjectHandler->renameLayoutTypePermissionsForFilePath($src, $dst); //Don't change $status bc may not be any permission items
									
									//delete file if cut action
									if ($action == "paste_and_remove" && $src != $root_path)
										$status = is_dir($src) ? CMSModuleUtil::deleteFolder($src) : !file_exists($src) || unlink($src);
								}
							}
						}
					}
					else {
						header((!empty($_SERVER["SERVER_PROTOCOL"]) ? $_SERVER["SERVER_PROTOCOL"] : "HTTP/1.0") . " 405 Not Allowed");
						$status = "You are not allowed to copy a file inside of this folder, because it does NOT belong to the selected project!";
					}
				}
				break;
		}
		
		debug_log("[Execute action '$action' for path '$path' and extra '" . (is_array($extra) ? print_r($extra, 1) : $extra) . "'] status: " . (is_array($status) ? print_r($status, 1) : $status), "info");
	}
}

function getRootPath($bean_name, $bean_file_name, $item_type, $path, $user_beans_folder_path, $user_global_variables_file_path, &$obj = false) {
	$root_path = null;
	
	if ($item_type == "dao")
		$root_path = DAO_PATH;
	else if ($item_type == "vendor")
		$root_path = VENDOR_PATH;
	else if ($item_type == "test_unit")
		$root_path = TEST_UNIT_PATH;
	else if ($item_type == "other")
		$root_path = OTHER_PATH;
	else {
		$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	
		if ($item_type != "presentation") 
			$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
		else {
			$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
			$obj = $PEVC ? $PEVC->getPresentationLayer() : null;
		}
		//echo "($item_type) obj class:".($obj ? get_class($obj) : "NO obj")."\n<br>";
		
		if ($obj)
			$root_path = $obj->getLayerPathSetting();
	}
	
	return $root_path;
}

//Checks if project creation doesn't exceed the maximum number of allowed projects according with the licence.
function isProjectCreationAllowed($EVC, $user_global_variables_file_path, $user_beans_folder_path) {
	$projs_max_num = substr(LA_REGEX, strpos(LA_REGEX, "]") + 1);
	if (!is_numeric($projs_max_num))
		return false;
	else if ($projs_max_num == -1)
		return true;
	
	include_once $EVC->getUtilPath("CMSPresentationLayerHandler");
	$files = CMSPresentationLayerHandler::getPresentationLayersProjectsFiles($user_global_variables_file_path, $user_beans_folder_path, "webroot", false, 0);
	
	$projs_count = 0;
	if ($files)
		foreach ($files as $file)
			if (!empty($file["projects"])) {
				$projs_count += count($file["projects"]);
				
				//bc of the common project that doesn't count
				if (array_key_exists("common", $file["projects"]))
					$projs_count--;
			}
	
	return $projs_count < $projs_max_num; //must be smaller bc we will create a new project which will sum +1 to the $projs_count.
}

function isPresentationProjectCommon($obj, $item_type, $root_path, $path) {
	if ($path && $item_type == "presentation") {
		$path = preg_replace("/\/+$/", "", $path); //remove end / if exists
		
		return $root_path . $obj->getCommonProjectName() == $path;
	}
	
	return false;
}

function isLayerDefaultFolder($obj, $item_type, $root_path, $path) {
	if ($path && ($item_type == "businesslogic" || $item_type == "ibatis" || $item_type == "hibernate")) {
		$path = preg_replace("/\/+$/", "", $path); //remove end / if exists
		$path = substr($path, strlen($root_path));
		
		$common_module_name = $item_type == "businesslogic" && !empty($obj->settings["business_logic_modules_common_name"]) ? $obj->settings["business_logic_modules_common_name"] : "common";
		
		return in_array($path, array($common_module_name, "module", "program", "resource"));
	}
	
	return false;
}

function isLayerDefaultFile($obj, $item_type, $root_path, $path) {
	if ($path && $item_type == "presentation") {
		$path = substr($path, strlen($root_path));
		$pos = strpos($path, "/src/");
		
		if ($pos > 0) {
			$project_id = substr($path, 0, $pos);
			$is_common_project = isPresentationProjectCommon($obj, $item_type, $root_path, $root_path . $project_id);
			
			//check if not the common project and if is a default template
			$pos = strpos($path, "/src/template/");
			
			if (!$is_common_project && $pos > 0) {
				$path = substr($path, $pos + strlen("/src/template/"));
				$default_templates = array("empty.php", "ajax.php", "blank.php", "default.php");
				
				return in_array($path, $default_templates);
			}
		}
	}
	
	return false;
}

function isPresentationTemplateFile($obj, $item_type, $root_path, $path) {
	if ($path && $item_type == "presentation") {
		if (empty($obj->settings["presentation_templates_path"]))
			launch_exception(new Exception("'PresentationLayer->settings[presentation_templates_path]' cannot be undefined!"));
		
		return strpos($path, $root_path . $obj->getSelectedPresentationId() . "/" . $obj->settings["presentation_templates_path"]) !== false;
	}
	
	return false;
}

//remove new layout from template.xml, if file exists
function addPresentationTemplateLayoutToXml($obj, $item_type, $root_path, $path) {
	if ($path && $item_type == "presentation") {
		if (empty($obj->settings["presentation_templates_path"]))
			launch_exception(new Exception("'PresentationLayer->settings[presentation_templates_path]' cannot be undefined!"));
		
		$root_template_folder_path = $root_path . $obj->getSelectedPresentationId() . "/" . $obj->settings["presentation_templates_path"];
		
		//confirm if is a template
		if (substr($path, 0, strlen($root_template_folder_path)) == $root_template_folder_path) {
			$template_id = substr($path, strlen($root_template_folder_path));
			$template_parts = explode("/", $template_id);
			$layout_name = pathinfo(array_pop($template_parts), PATHINFO_FILENAME);
			$template_xml_file_path = $root_template_folder_path . "template.xml";
			
			$template_parts = array_values(array_filter($template_parts, function($value) {
				return is_numeric($value) || !empty($value);
			})); //remove all empty values when we have double "/"
			
			for ($i = 0, $t = count($template_parts); $i < $t; $i++) {
				$template_part = $template_parts[$i];
				$template_xml_file_path = dirname($template_xml_file_path) . "/$template_part/template.xml";
				
				if (file_exists($template_xml_file_path)) {
					$layout_path = implode("/", array_slice($template_parts, $i + 1));
					$layout_path .= ($layout_path ? "/" : "") . $layout_name;
					$template_folder_path = dirname($template_xml_file_path) . "/";
					
					$CMSTemplateInstallationHandler = new CMSTemplateInstallationHandler($template_folder_path, null, null);
					return $CMSTemplateInstallationHandler->addLayoutToTemplateXml($layout_path, true);
				}
			}
			
			return true;
		}
	}
	
	return false;
}

//remove new layout from template.xml, if file exists
function removePresentationTemplateLayoutToXml($obj, $item_type, $root_path, $path) {
	if ($path && $item_type == "presentation") {
		if (empty($obj->settings["presentation_templates_path"]))
			launch_exception(new Exception("'PresentationLayer->settings[presentation_templates_path]' cannot be undefined!"));
		
		$root_template_folder_path = $root_path . $obj->getSelectedPresentationId() . "/" . $obj->settings["presentation_templates_path"];
		
		//confirm if is a template
		if (substr($path, 0, strlen($root_template_folder_path)) == $root_template_folder_path) {
			$template_id = substr($path, strlen($root_template_folder_path));
			$template_parts = explode("/", $template_id);
			$layout_name = pathinfo(array_pop($template_parts), PATHINFO_FILENAME);
			$template_xml_file_path = $root_template_folder_path . "template.xml";
			
			$template_parts = array_values(array_filter($template_parts, function($value) {
				return is_numeric($value) || !empty($value);
			})); //remove all empty values when we have double "/"
			
			for ($i = 0, $t = count($template_parts); $i < $t; $i++) {
				$template_part = $template_parts[$i];
				$template_xml_file_path = dirname($template_xml_file_path) . "/$template_part/template.xml";
				
				if (file_exists($template_xml_file_path)) {
					$layout_path = implode("/", array_slice($template_parts, $i + 1));
					$layout_path .= ($layout_path ? "/" : "") . $layout_name;
					$template_folder_path = dirname($template_xml_file_path) . "/";
					
					$CMSTemplateInstallationHandler = new CMSTemplateInstallationHandler($template_folder_path, null, null);
					return $CMSTemplateInstallationHandler->removeLayoutFromTemplateXml($layout_path, true);
				}
			}
			
			return true;
		}
	}
	
	return false;
}

function updateDiagramWorkflowFile($action, $bean_name, $workflow_paths_id, $layer_path, $src_path, $dst_path) {
	$status = true;
	$src_path = trim($src_path);
	$dst_path = trim($dst_path);
	
	if ($src_path && $dst_path && $dst_path != $src_path && is_dir($dst_path)) {
		$src_path = preg_replace("/[\/]+/", "/", $src_path);
		$dst_path .= substr($dst_path, -1) == "/" ? "" : "/"; //must have the "/" at the end otherwise the $workflow_path will not be correct.
		$dst_path = preg_replace("/[\/]+/", "/", $dst_path);
		
		if (substr($src_path, 0, strlen($layer_path)) == $layer_path && strpos($src_path, "/src/entity/") !== false) {
			$src_path .= substr($src_path, -1) == "/" ? "" : "/"; //must have the "/" at the end otherwise the $workflow_path will not be correct.
			$src_relative = str_replace($layer_path, "", $src_path);
			
			$src_workflow_path = WorkFlowTasksFileHandler::getTaskFilePathByPath($workflow_paths_id, "presentation_ui", "_{$bean_name}_" . md5($src_relative));
			
			if (file_exists($src_workflow_path)) {
				$dst_relative = str_replace($layer_path, "", $dst_path);
				$dst_workflow_path = WorkFlowTasksFileHandler::getTaskFilePathByPath($workflow_paths_id, "presentation_ui", "_{$bean_name}_" . md5($dst_relative));
				
				if ($dst_workflow_path) {
					if (($action == "rename" || $action == "paste_and_remove")) { 
						if (file_exists($dst_workflow_path))
							unlink($dst_workflow_path);
						
						if (!rename($src_workflow_path, $dst_workflow_path))
							$status = false;
					}
					else if ($action == "paste" && !copy($src_workflow_path, $dst_workflow_path))
						$status = false;
				}
			}
			
			$sub_files = scandir($dst_path);
			if ($sub_files)
				foreach ($sub_files as $sub_file)
					if ($sub_file != "." && $sub_file != ".." && is_dir("$dst_path$sub_file") && !updateDiagramWorkflowFile($action, $bean_name, $workflow_paths_id, $layer_path, "$src_path$sub_file/", "$dst_path$sub_file/"))
						$status = false;
		}
	}
	
	return $status;
}

function removeDiagramWorkflowFile($action, $bean_name, $workflow_paths_id, $layer_path, $folder_path) {
	$status = true;
	$folder_path = trim($folder_path);
	
	if ($folder_path && is_dir($folder_path)) {
		$folder_path = preg_replace("/[\/]+/", "/", $folder_path);
		
		if (substr($folder_path, 0, strlen($layer_path)) == $layer_path && strpos($folder_path, "/src/entity/") !== false) {
			$folder_path .= substr($folder_path, -1) == "/" ? "" : "/"; //must have the "/" at the end otherwise the $workflow_path will not be correct.
			$src_relative = str_replace($layer_path, "", $folder_path);
			
			$src_workflow_path = WorkFlowTasksFileHandler::getTaskFilePathByPath($workflow_paths_id, "presentation_ui", "_{$bean_name}_" . md5($src_relative));
			
			if (file_exists($src_workflow_path) && !unlink($src_workflow_path)) 
				$status = false;
			
			$sub_files = scandir($folder_path);
			if ($sub_files)
				foreach ($sub_files as $sub_file)
					if ($sub_file != "." && $sub_file != ".." && is_dir("$folder_path$sub_file") && !removeDiagramWorkflowFile($action, $bean_name, $workflow_paths_id, $layer_path, "$folder_path$sub_file/"))
						$status = false;
		}
	}
	
	return $status;
}

function prepareProjectCreationIfApply($LayoutTypeProjectHandler, $project_path, $create_layers_project_folder_if_not_exists = true) {
	//if path is a project, create layout_type and permissions for this project
	return $LayoutTypeProjectHandler->isPathAPresentationProjectPath($project_path) && $LayoutTypeProjectHandler->createNewLayoutFromProjectPath($project_path, $create_layers_project_folder_if_not_exists);
}

function prepareProjectFolderCreationIfApply($LayoutTypeProjectHandler, $project_path, $create_layers_project_folder_if_not_exists = true) {
	//if path is a project, create layout_type and permissions for this project
	return $LayoutTypeProjectHandler->isPathAPresentationProjectFolderPath($project_path) && $LayoutTypeProjectHandler->createNewLayoutFromProjectFolderPath($project_path, $create_layers_project_folder_if_not_exists);
}

function prepareProjectsInitFiles($root_path, $src, $dst, $user_beans_folder_path, $user_global_variables_file_path, $bean_file_name, $bean_name, $LayoutTypeProjectHandler) {
	$status = true;
	
	if ($dst && is_dir($dst)) {
		//remove duplicates, start and end slashes
		$src = "/" . preg_replace("/^\//", "", preg_replace("/\/$/", "", str_replace("//", "/", $src) )) . "/";
		$dst = "/" . preg_replace("/^\//", "", preg_replace("/\/$/", "", str_replace("//", "/", $dst) )) . "/";
		
		if ($src != $dst) {
			//Preparing init file:
			$root_path = "/" . preg_replace("/^\//", "", preg_replace("/\/$/", "", str_replace("//", "/", $root_path) )) . "/";
			
			$relative_src = $src ? substr($src, strlen($root_path)) : "";
			$src_folder = dirname($relative_src);
			$src_folder = preg_replace("/^\//", "", $src_folder);
			$src_folder = $src_folder == "." ? "" : $src_folder;
			$src_folder_parts = explode("/", $src_folder);
			
			$relative_dst = substr($dst, strlen($root_path));
			$dst_folder = dirname($relative_dst);
			$dst_folder = preg_replace("/^\//", "", $dst_folder);
			$dst_folder = $dst_folder == "." ? "" : $dst_folder;
			$dst_folder_parts = explode("/", $dst_folder);
			
			//updates inner files if:
			//- (!$src_folder && $dst_folder): if is a new project
			//- ($src_folder && !$dst_folder): if project changed to root folder
			//- count($src_folder_parts) != count($dst_folder_parts): if proejct changed to another folder
			//otherwise stays with the default.
			$update_inner_files = (!$src_folder && $dst_folder) || ($src_folder && !$dst_folder) || count($src_folder_parts) != count($dst_folder_parts);
			
			if ($update_inner_files) {
				//get all projects for this folder and call this method again
				$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
				$obj = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name);
				
				if ($obj) {
					$P = $obj->getPresentationLayer();
					$folder_type = $LayoutTypeProjectHandler->isPathAPresentationProjectPath($dst) ? "project" : ($LayoutTypeProjectHandler->isPathAPresentationProjectFolderPath($dst) ? "project_folder" : null);
					
					if ($folder_type == "project") {
						$prefix = "";
						$suffix = "";
						
						for ($i = 0; $i < count($dst_folder_parts); $i++) {
							$prefix .= "dirname(";
							$suffix .= ")";
						}
						
						if (empty($P->settings["presentation_configs_path"]))
							launch_exception(new Exception("'PresentationLayer->settings[presentation_configs_path]' cannot be undefined!"));
						
						if (empty($P->settings["presentation_webroot_path"]))
							launch_exception(new Exception("'PresentationLayer->settings[presentation_webroot_path]' cannot be undefined!"));
						
						//fix dirs for init.php
						$init_path = $dst . $P->settings["presentation_configs_path"] . "init.php";
						$contents = file_get_contents($init_path);
						//change code to include correct parent init.php
						//$contents = str_replace('include dirname(dirname(dirname(__DIR__))) . "/init.php";', 'include ' . $prefix . 'dirname(dirname(dirname(__DIR__)))' . $suffix . ' . "/init.php";', $contents);
						$contents = preg_replace('/include\s*(dirname\()+__DIR__(\))+\s*\.\s*"\/init\.php";/', 'include ' . $prefix . 'dirname(dirname(dirname(__DIR__)))' . $suffix . ' . "/init.php";', $contents);
						$status_1 = file_put_contents($init_path, $contents) !== false;
						
						//fix dirs for pre_init_config.php
						$pre_init_config_path = $dst . $P->settings["presentation_configs_path"] . "pre_init_config.php";
						$contents = file_get_contents($pre_init_config_path);
						//change layer_path and automatically the presentation id will be correct
						//$contents = str_replace('$layer_path = dirname($project_path)', '$layer_path = ' . $prefix . 'dirname($project_path)' . $suffix, $contents);
						$contents = preg_replace('/\$layer_path\s*=\s*(dirname\()+\$project_path(\))+/', '$layer_path = ' . $prefix . 'dirname($project_path)' . $suffix, $contents);
						$status_2 = file_put_contents($pre_init_config_path, $contents) !== false;
						
						//fix dirs for script.php
						$script_path = $dst . $P->settings["presentation_webroot_path"] . "script.php";
						$contents = file_get_contents($script_path);
						//change layer_path and automatically the presentation id will be correct
						//$contents = str_replace('include dirname(dirname(__DIR__))', 'include ' . $prefix . 'dirname(dirname(__DIR__))' . $suffix, $contents);
						$contents = preg_replace('/include\s*(dirname\()+__DIR__(\))+/', 'include ' . $prefix . 'dirname(dirname(__DIR__))' . $suffix, $contents);
						$status_3 = file_put_contents($script_path, $contents) !== false;
						
						$status = $status_1 && $status_2 && $status_3;
					}
					else if ($folder_type == "project_folder") {
						$project_folders = array();
						$projects = $obj->getProjectsId($relative_dst);
						//echo "projects:";print_r($projects);
						
						foreach ($projects as $project) {
							$project_src = $root_path . str_replace($relative_dst, $relative_src, $project);
							$project_dst = $root_path . $project;
							//echo "project_src:".str_replace($root_path, "", $project_src)."\n";
							//echo "project_dst:".str_replace($root_path, "", $project_dst)."\n";
							
							if (!prepareProjectsInitFiles($root_path, $project_src, $project_dst, $user_beans_folder_path, $user_global_variables_file_path, $bean_file_name, $bean_name, $LayoutTypeProjectHandler))
								$status = false;
						}
					}
				}
			}
		}
	}
	
	return $status;
}
?>
