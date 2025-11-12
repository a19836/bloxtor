<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.util.MyArray");
include_once get_lib("org.phpframework.layer.presentation.cms.CMSFileHandler");
include_once get_lib("org.phpframework.layer.presentation.cms.CMSExternalTemplateLayer");
include_once get_lib("org.phpframework.workflow.WorkFlowTask");
include_once get_lib("org.phpframework.cms.wordpress.WordPressUrlsParser");
include_once get_lib("org.phpframework.phpscript.PHPUICodeExpressionHandler");
include_once $EVC->getUtilPath("AdminMenuHandler");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");
include_once $EVC->getUtilPath("CMSPresentationLayerJoinPointsUIHandler");

class CMSPresentationLayerHandler {
	
	public static function configureUniqueFileId(&$file_id, $prefix = "", $suffix = "") {
		$file_id = trim($file_id);
		
		while (substr($file_id, -1) == "/")
			$file_id = substr($file_id, 0, -1);
		
		if (strlen($file_id)) {
			$file_name = $file_id;
			$dirname = "";
			$dirname_pos = strrpos($file_id, "/");
			
			if ($dirname_pos !== false) {
				$dirname = substr($file_id, 0, $dirname_pos + 1);
				$file_name = substr($file_id, $dirname_pos + 1);
			}
			
			$path_info = pathinfo($file_name);
			$extension = !empty($path_info["extension"]) ? "." . $path_info["extension"] : "";
			$fn = $path_info["filename"];
			$pos = strrpos($fn, "_");
			$file_name = $pos !== false && is_numeric(substr($fn, $pos + 1)) ? substr($fn, 0, $pos) : $fn;
			
			$count = 0;
			while(file_exists("$prefix$file_id$suffix")) {
				$count++; //$count = rand(0, 10000)
				$file_id = $dirname . $file_name . "_" . $count . $extension;
			}
		}
	}
	
	public static function getFilePathId($PEVC, $file_path) {
		$layer_path = $PEVC->getPresentationLayer()->getLayerPathSetting();
		$fp = str_replace($layer_path, "", $file_path);
		$fp = substr($fp, 0, -4); //removing extension
		$file_id = "file_" . HashCode::getHashCodePositive($fp);
		
		return $file_id;
	}
	
	public static function isEntityFileHardCoded($EVC, $UserCacheHandler, $cms_page_cache_path_prefix, $file_path, $check_ui_diagram = false, $workflow_paths_id = null, $bean_name = null) {
		if (file_exists($file_path)) {
			$code = file_get_contents($file_path);
			
			//PREPARING FILES VALIDATION:
			$cached_modified_date = self::getCachedEntitySaveActionTime($UserCacheHandler, $cms_page_cache_path_prefix, $file_path);
			$modified_date = filemtime($file_path);
			$hard_coded = $code && (!$cached_modified_date || $cached_modified_date != $modified_date);
			
			//echo "cached_modified_date:$cached_modified_date\n";
			//echo "modified_date:$modified_date\n";
			//echo "hard_coded:$hard_coded\n";
			
			//TODO: find da better way to find if entity is hard coded, bc if someone deletes the cache, we lost this timings. Maybe we should save this to another folder that should not be the deleted...
			
			//recheck hard_code theough the diagram xml file if exists
			if ($hard_coded && $check_ui_diagram && $workflow_paths_id && $bean_name) {
				$P = $EVC->getPresentationLayer();
				$layer_path = $P->getLayerPathSetting();
				$extension = $P->getPresentationFileExtension();
				$relative_folder_path = str_replace($layer_path, "", dirname($file_path)); //example: test/src/entity/automatic_diagram/activity/
				$relative_folder_path .= substr($relative_folder_path, -1) == "/" ? "" : "/"; //$relative_folder_path must end with /
				$file_name = pathinfo($file_path, PATHINFO_FILENAME);
				
				$workflow_path = WorkFlowTasksFileHandler::getTaskFilePathByPath($workflow_paths_id, "presentation_ui", "_{$bean_name}_" . md5($relative_folder_path));
				
				$WorkFlowTasksFileHandler = new WorkFlowTasksFileHandler($workflow_path);
				$WorkFlowTasksFileHandler->init();
				$tasks = $WorkFlowTasksFileHandler->getWorkflowData();
				
				if ($tasks && !empty($tasks["tasks"])) //just in case the workflow_path does not exist
					foreach ($tasks["tasks"] as $task) {
						$task_file_name = isset($task["properties"]["file_name"]) ? $task["properties"]["file_name"] : null;
						
						if ($task_file_name == $file_name) {
							$file_id = self::getFilePathId($EVC, $file_path); //file_1824044789
							$task_modified_date = isset($task["properties"]["created_files"][$file_id]) ? $task["properties"]["created_files"][$file_id] : null;
							
							if ($task_modified_date == $modified_date)
								$hard_coded = false;
							
							break;
						}
					}
			}
			
			return $hard_coded;
		}
		return false;
	}
	
	public static function cacheEntitySaveActionTime($EVC, $UserCacheHandler, $cms_page_cache_path_prefix, $file_path, $save_to_ui_diagram = false, $workflow_paths_id = null, $bean_name = null) {
		$UserCacheHandler->config(false, false);
		$modified_date = filemtime($file_path);
		$cache_id = md5($cms_page_cache_path_prefix . str_replace(APP_PATH, "", $file_path));
		
		$UserCacheHandler->write($cache_id, $modified_date);
		
		//saves the new date in the diagram xml file if exists
		if ($save_to_ui_diagram && $workflow_paths_id && $bean_name) {
			$P = $EVC->getPresentationLayer();
			$layer_path = $P->getLayerPathSetting();
			$extension = $P->getPresentationFileExtension();
			$relative_folder_path = str_replace($layer_path, "", dirname($file_path)); //example: test/src/entity/automatic_diagram/activity/
			$relative_folder_path .= substr($relative_folder_path, -1) == "/" ? "" : "/"; //$relative_folder_path must end with /
			$file_name = pathinfo($file_path, PATHINFO_FILENAME);
			
			$workflow_path = WorkFlowTasksFileHandler::getTaskFilePathByPath($workflow_paths_id, "presentation_ui", "_{$bean_name}_" . md5($relative_folder_path));
			
			$WorkFlowTasksFileHandler = new WorkFlowTasksFileHandler($workflow_path);
			$WorkFlowTasksFileHandler->init();
			$tasks = $WorkFlowTasksFileHandler->getWorkflowData();
			$exists = false;
			
			if ($tasks && !empty($tasks["tasks"])) //just in case the workflow_path does not exist
				foreach ($tasks["tasks"] as $task_id => $task) {
					$task_file_name = isset($task["properties"]["file_name"]) ? $task["properties"]["file_name"] : null;
					
					if ($task_file_name == $file_name) {
						$exists = true;
						$file_id = self::getFilePathId($EVC, $file_path); //file_1824044789
						
						if (empty($task["properties"]["created_files"]))
							$tasks["tasks"][$task_id]["properties"]["created_files"] = array();
						
						$tasks["tasks"][$task_id]["properties"]["created_files"][$file_id] = $modified_date;
						break;
					}
				}
			
			if ($exists)
				WorkFlowTasksFileHandler::createTasksFile($workflow_path, $tasks);
		}
	}
	
	public static function getCachedEntitySaveActionTime($UserCacheHandler, $cms_page_cache_path_prefix, $file_path) {
		$UserCacheHandler->config(false, false);
		$cache_id = md5($cms_page_cache_path_prefix . str_replace(APP_PATH, "", $file_path));
		
		return $UserCacheHandler->read($cache_id);
	}
	
	public static function getPresentationLayerProjectUrl($user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name, $selected_project_id) {
		$url = null;
		
		$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
		$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $selected_project_id);
		
		//backup original vars
		$original_presentation_id = isset($GLOBALS["presentation_id"]) ? $GLOBALS["presentation_id"] : null;
		
		//set new project vars
		$GLOBALS["presentation_id"] = $selected_project_id;
		$PEVC->getPresentationLayer()->setSelectedPresentationId($selected_project_id);
		$EVC = $PEVC;
		@include $EVC->getConfigPath("config", $selected_project_id); //config file may not exist
		
		if (!empty($project_url_prefix)) {
			$url = $project_url_prefix;
		}
		
		//reset the original vars
		$GLOBALS["presentation_id"] = $original_presentation_id;
		
		return $url;
	}
	
	public static function getPresentationLayerProjectLogo($user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name, $selected_project_id) {
		$favicon_url = null;
		$favicon_path = null;
		
		$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
		$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $selected_project_id);
		
		//backup original vars
		$original_presentation_id = isset($GLOBALS["presentation_id"]) ? $GLOBALS["presentation_id"] : null;
		
		//set new project vars
		$GLOBALS["presentation_id"] = $selected_project_id;
		$PEVC->getPresentationLayer()->setSelectedPresentationId($selected_project_id);
		$EVC = $PEVC;
		@include $EVC->getConfigPath("config", $selected_project_id); //config file may not exist
		
		if (!empty($project_url_prefix)) {
			//get project icon
			$webroot_path = $PEVC->getWebrootPath();
			$folders = array(
				$webroot_path => "", 
				$webroot_path . "img/" => "img/"
			);
			
			$images_available_names = array("favicon", "icon", "logo");
			$images_available_extensions = array("ico", "gif", "png", "jpg", "jpeg", "tif", "tiff", "bmp", "webp", "svg", "psd", "raw", "heif", "bat");
			//echo "<pre>";print_r($folders);
			
			foreach ($folders as $folder_path => $folder_url)
				if ($folder_path && is_dir($folder_path)) {
					$files = array_diff(scandir($folder_path), array('..', '.'));
					
					foreach ($files as $file) {
						$name = strtolower(pathinfo($file, PATHINFO_FILENAME));
						$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
						
						if (in_array($name, $images_available_names) && in_array($extension, $images_available_extensions)) {
							$favicon_url = "$project_url_prefix$folder_url$file";
							$favicon_path = "$folder_path$file";
							break 2;
						}
						else if (in_array($file, $images_available_names) && is_file("$folder_path/$file") && !is_dir("$folder_path/$file")) {
							$favicon_url = "$project_url_prefix$folder_url$file";
							$favicon_path = "$folder_path$file";
							break 2;
						}
					}
					
					if ($favicon_url)
						break 1;
				}
		}
		
		//reset the original vars
		$GLOBALS["presentation_id"] = $original_presentation_id;
		
		return array($favicon_url, $favicon_path);
	}
	
	public static function getPresentationLayerProjectDescription($user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name, $selected_project_id) {
		$description = "";
		
		$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
		$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $selected_project_id);
		
		$webroot_path = $PEVC->getWebrootPath();
		$file_path = $webroot_path . "humans.txt";
		
		if ($webroot_path && is_dir($webroot_path) && file_exists($file_path))
			$description = file_get_contents($file_path);
		
		return $description;
	}
	
	public static function getPresentationLayersProjectsFiles($user_global_variables_file_path, $user_beans_folder_path, $type = false, $only_folders = false, $recursive_level = -1, $include_empty_project_folders = false, $filter_by_parent_folder = null, $with_project_details = false) {
		$layers = AdminMenuHandler::getLayers($user_global_variables_file_path);
		
		$presentation_layers = isset($layers["presentation_layers"]) ? $layers["presentation_layers"] : null;
		$files = array();
		
		if (is_array($presentation_layers)) {
			if ($filter_by_parent_folder) {
				$filter_by_parent_folder .= "/";
				$filter_by_parent_folder = preg_replace("/\/+/", "/", $filter_by_parent_folder); //remove duplicated "/"
				$filter_by_parent_folder = preg_replace("/^\/+/", "/", $filter_by_parent_folder); //remove first "/"
			}
			
			foreach ($presentation_layers as $bean_name => $bean_file_name) {
				$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
				$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
				$item_label = WorkFlowBeansFileHandler::getLayerNameFromBeanObject($bean_name, $obj);
				$projects = self::getPresentationLayerProjectsFiles($user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name, $type, $only_folders, $recursive_level, $include_empty_project_folders, $with_project_details);
				
				if ($projects && $filter_by_parent_folder) 
					foreach ($projects as $project_name => $project_props)
						if (substr($project_name, 0, strlen($filter_by_parent_folder)) != $filter_by_parent_folder)
							unset($projects[$project_name]);
				
				$files[$bean_name] = array(
					"bean_file_name" => $bean_file_name,
					"item_label" => $item_label,
					"projects" => $projects,
				);
			}
			//echo "<pre>";print_r($files);die();
		}
	
		return $files;
	}
	
	public static function getPresentationLayerProjectsFiles($user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name, $type = false, $only_folders = false, $recursive_level = -1, $include_empty_project_folders = false, $with_project_details = false) {
		$files = array();
		
		$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
		$obj = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name);
		
		if ($obj) {
			$P = $obj->getPresentationLayer();
			$layer_path = $P->getLayerPathSetting();
			$common_project_name = $P->getCommonProjectName();
			
			$prefix_path = self::getPresentationLayerPrefixPath($P, $type);
			
			if (is_dir($layer_path)) {
				$project_folders = array();
				$projects = $obj->getProjectsId("", $project_folders);
				asort($projects);
				
				$prefix_path .= $prefix_path && substr($prefix_path, strlen($prefix_path) - 1) != "/" ? "/" : "";
				
				if ($include_empty_project_folders && $project_folders) {
					asort($project_folders);
					
					foreach ($project_folders as $project_name) {
						$project_path = $layer_path . $project_name;
						
						$files[$project_name] = array(
							"path" => $project_path,
							"element_type_path" => $project_name . "/" . $prefix_path,
							"item_type" => "project_folder",
						);
					}
				}
				
				foreach ($projects as $project_name)
					$files[$project_name] = self::getPresentationLayerProjectFiles($user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name, $layer_path, $project_name, $prefix_path, $only_folders, $recursive_level, $with_project_details, $common_project_name);
			}
		}
		
		//echo "<pre>";print_r($files);die();
		return $files;
	}
	
	public static function getPresentationLayerProjectFiles($user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name, $layer_path, $project_name, $prefix_path = false, $only_folders = false, $recursive_level = -1, $with_project_details = false, $common_project_name = false) {
		$project_path = $layer_path . $project_name;
		
		if (!$common_project_name) {
			$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
			$obj = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name);
			
			if ($obj) {
				$P = $obj->getPresentationLayer();
				$common_project_name = $P->getCommonProjectName();
			}
		}
		
		$project = array(
			"path" => $project_path,
			"element_type_path" => $project_name . "/" . $prefix_path,
			"files" => $prefix_path ? self::getFolderFilesTree($layer_path, $project_path . "/" . $prefix_path, $only_folders, $recursive_level) : null,
			"item_type" => $common_project_name != $project_name ? "project" : "project_common",
		);
		
		if ($with_project_details) {
			$url = self::getPresentationLayerProjectUrl($user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name, $project_name);
			$logo = self::getPresentationLayerProjectLogo($user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name, $project_name);
			$description = self::getPresentationLayerProjectDescription($user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name, $project_name);
			
			$project["url"] = $url;
			$project["logo_url"] = $logo[0];
			$project["logo_path"] = $logo[1];
			$project["description"] = $description;
		}
		
		return $project;
	}
	
	public static function getPresentationLayerPrefixPath($Layer, $type) {
		switch ($type) {
			case "entity": return isset($Layer->settings["presentation_entities_path"]) ? $Layer->settings["presentation_entities_path"] : null;
			case "view": return isset($Layer->settings["presentation_views_path"]) ? $Layer->settings["presentation_views_path"] : null;
			case "template": return isset($Layer->settings["presentation_templates_path"]) ? $Layer->settings["presentation_templates_path"] : null;
			case "util": return isset($Layer->settings["presentation_utils_path"]) ? $Layer->settings["presentation_utils_path"] : null;
			case "block": return isset($Layer->settings["presentation_blocks_path"]) ? $Layer->settings["presentation_blocks_path"] : null;
			case "webroot": return isset($Layer->settings["presentation_webroot_path"]) ? $Layer->settings["presentation_webroot_path"] : null;
			case "config": return isset($Layer->settings["presentation_configs_path"]) ? $Layer->settings["presentation_configs_path"] : null;
		}
		return "";
	}

	public static function getFolderFilesTree($main_folder_path, $folder_path, $only_folders = false, $recursive_level = -1) {
		$files = array();
		
		$rl = $recursive_level > 0 ? $recursive_level - 1 : $recursive_level;
		
		if (is_dir($folder_path) && ($dir = opendir($folder_path)) ) {
			while( ($file = readdir($dir)) !== false) {
				if (substr($file, 0, 1) != ".") {
					$fp = $folder_path . $file;
				
					$path = str_replace($main_folder_path, "", $fp);
					
					if (is_dir($fp)) {
						$sub_files = null;
						if ($recursive_level > 0 || $recursive_level == -1)
							$sub_files = self::getFolderFilesTree($main_folder_path, $fp . "/", $only_folders, $rl);
						
						$files[$file] = array(
							"name" => $file,
							"type" => "folder",
							"sub_files" => $sub_files,
							"path" => $path,
						);
					}
					else if (!$only_folders) {
						$path_info = pathinfo($file);
						$extension = isset($path_info["extension"]) ? $path_info["extension"] : null;
						
						if ($extension == "php")
							$file_type = "php_file";
						else if ($extension == "css")
							$file_type = "css_file";
						else if ($extension == "js")
							$file_type = "js_file";
						else if ($extension == "zip")
							$file_type = "zip_file";
						else if (function_exists("exif_imagetype") && exif_imagetype($fp)) 
							$file_type = "img_file";
						else
							$file_type = "undefined_file";
						
						$files[$file] = array(
							"name" => isset($path_info["filename"]) ? $path_info["filename"]: null,
							"type" => $file_type,
							"path" => $path,
						);
					}
				}
			}
			
			closedir($dir);
		}
		
		ksort($files);
		
		return $files;
	}
	
	public static function getFolderFilesList($main_folder_path, $folder_path, $only_folders = false) {
		$files = array();
	
		if (is_dir($folder_path) && ($dir = opendir($folder_path)) ) {
			while( ($file = readdir($dir)) !== false) {
				if (substr($file, 0, 1) != ".") {
					$fp = $folder_path . $file;
				
					$path = str_replace($main_folder_path, "", $fp);
					
					if (is_dir($fp)) {
						$files[$path] = array(
							"type" => "folder",
							"name" => $file,
						);
						
						$sub_files = self::getFolderFilesList($main_folder_path, $fp . "/", $only_folders);
						$files = array_merge($files, $sub_files);
					}
					else if (!$only_folders) {
						$path_info = pathinfo($file);
						
						$files[$path] = array(
							"type" => "file",
							"name" => isset($path_info["filename"]) ? $path_info["filename"]: null,
						);
					}
				}
			}
			
			closedir($dir);
		}
	
		return $files;
	}
	
	public static function getAvailableTemplatesList($PEVC, $default_extension) {
		$templates_path = $PEVC->getTemplatesPath();
		$available_extensions = array("php", "html", "htm");
		$files = self::getFolderFilesList($templates_path, $templates_path);
		ksort($files);
		//echo "<pre>";print_r($files);die();
		
		//filter and sort files according with the templates.xml in each template
		$template_prefixes_to_filter = array();
		$sorted_files = array();
		
		foreach ($files as $file_path => $file) {
			if (isset($file["type"]) && $file["type"] == "folder" && file_exists($templates_path . $file_path . "/template.xml")) { //Note that the main folders will be always first than the sub-files
				//parse template.xml
				$arr = XMLFileParser::parseXMLFileToArray($templates_path . $file_path . "/template.xml");
				$arr = MyXML::complexArrayToBasicArray($arr, array("lower_case_keys" => true, "trim" => true));
				$template_props = isset($arr["template"]) ? $arr["template"] : null;
				
				if ($template_props && !empty($template_props["layouts"]["layout"])) {
					//save template folder path
					$folder_path = $file_path . "/";
					$layouts = is_array($template_props["layouts"]["layout"]) ? $template_props["layouts"]["layout"] : array($template_props["layouts"]["layout"]);
					$layouts_paths = array();
					
					foreach ($layouts as $idx => $layout) {
						if (array_key_exists($folder_path . $layout, $files))
							$layouts_paths[] = $folder_path . $layout;
						else if (array_key_exists($folder_path . $layout . $default_extension, $files))
							$layouts_paths[] = $folder_path . $layout . $default_extension;
					}
					
					$template_prefixes_to_filter[$folder_path] = $layouts_paths;
				}
			}
			else {
				$add = true;
				
				foreach ($template_prefixes_to_filter as $folder_path => $layouts_paths)
					if (substr($file_path, 0, strlen($folder_path)) == $folder_path) {
						$add = false;
						
						if (in_array($file_path, $layouts_paths) && !isset($sorted_files[$file_path]))
							foreach ($layouts_paths as $layout_path)
								$sorted_files[$layout_path] = isset($files[$layout_path]) ? $files[$layout_path] : null;
						
						break 1;
					}
				
				if ($add && !isset($sorted_files[$file_path]))
					$sorted_files[$file_path] = $file;
			}
		}
		
		$files = $sorted_files;
		//echo "<pre>";print_r($template_prefixes_to_filter);die();
		//echo "<pre>";print_r($files);die();
		
		$templates = array();
		foreach ($files as $file_path => $file) {
			$file_type = isset($file["type"]) ? $file["type"] : null;
			
			if ($file_type != "folder") {
				$is_reserved = !in_array(pathinfo($file_path, PATHINFO_EXTENSION), $available_extensions);
				
				//ignore the region folder bc is a reserved folder that contains the samples html code for the template regions
				if (strpos($file_path, "/region/") !== false || strpos($file_path, "/module/") !== false) {
					$first_slash = strpos($file_path, "/");
					$first_folder = substr($file_path, $first_slash + 1, strpos($file_path, "/", $first_slash + 1) - $first_slash - 1);
					
					if ($first_folder == "region" || $first_folder == "module") //check if region folder is in the root path of the template, otherwise allow it
						$is_reserved = true;
				}
				
				if (!$is_reserved) {
					$fp = substr($file_path, strlen($file_path) - strlen($default_extension)) == $default_extension ? substr($file_path, 0, strlen($file_path) - strlen($default_extension)) : $file_path;
					
					$file["path"] = $file_path;
					
					$templates[$fp] = $file;
				}
			}
		}
		
		//sort default templates first
		$templates_to_show_first = array("empty", "ajax", "blank");
		$default_templates = array();
		for ($i = 0; $i < count($templates_to_show_first); $i++) {
			$fp = $templates_to_show_first[$i];
			
			if (!empty($templates[$fp])) {
				$default_templates[$fp] = $templates[$fp];
				unset($templates[$fp]);
			}
		}
		$templates = array_merge($default_templates, $templates);
		//echo "<pre>";print_r($templates);die();
		
		return $templates;
	}
	
	public static function getAvailableTemplatesProps($PEVC, $selected_project_id, $available_templates) {
		$templates = array();
		
		$EVC = $PEVC;
		@include $EVC->getConfigPath("config", $selected_project_id); //config file may not exist
		$proj_url_prefix = preg_replace("/\/+$/", "", $project_url_prefix);
		
		$webroot_path = $PEVC->getWebrootPath() . "/template/";
		$html_available_extensions = array("php", "html", "htm");
		$images_available_extensions = array("gif", "png", "jpg", "jpeg", "tif", "tiff", "bmp", "webp", "svg", "psd", "raw", "heif", "bat");
		
		for ($i = 0; $i < count($available_templates); $i++) {
			$fp = $available_templates[$i];
			$file_name = pathInfo($fp, PATHINFO_FILENAME);
			$templates[$fp] = array(
				"label" => ucwords(strtolower(str_replace(array("-", "_"), " ", $file_name))),
				"file" => $fp . ".php",
				"logo" => null,
				"demo" => null,
			);
			
			for ($j = 0; $j < count($html_available_extensions); $j++) {
				$path = $webroot_path . $fp . "." . $html_available_extensions[$j];
				
				if (file_exists($path)) {
					$templates[$fp]["demo"] = $proj_url_prefix . "/template/" . $fp . "." . $html_available_extensions[$j];
					break;
				}
			}
			
			for ($j = 0; $j < count($images_available_extensions); $j++) {
				$path = $webroot_path . $fp . "." . $images_available_extensions[$j];
				
				if (file_exists($path)) {
					$templates[$fp]["logo"] = $proj_url_prefix . "/template/" . $fp . "." . $images_available_extensions[$j];
					break;
				}
			}
		}
		
		//echo "<pre>";print_r($templates);die();
		return $templates;
	}
	
	public static function initBlocksListThroughRegionBlocks($PEVC, $regions_blocks_list, $selected_project_id) {
		$blocks_list = array();
		
		if ($regions_blocks_list)
			foreach ($regions_blocks_list as $region_block) {
				$type = isset($region_block[3]) ? $region_block[3] : null;
				$is_block = $type == 2 || $type == 3;
				
				if ($is_block) { //is block
					$region = isset($region_block[0]) ? $region_block[0] : null;
					$block = isset($region_block[1]) ? $region_block[1] : null;
					$proj = isset($region_block[2]) ? $region_block[2] : null;
					
					$region = substr($region, 0, 1) == '"' ? str_replace('"', '', $region) : $region;
					$block = substr($block, 0, 1) == '"' ? str_replace('"', '', $block) : $block;
					$proj = substr($proj, 0, 1) == '"' ? str_replace('"', '', $proj) : $proj;
					$proj = $proj ? $proj : $selected_project_id;
					
					if ($proj)
						$blocks_list[$proj][] = $block;
				}
			}
		
		return $blocks_list;
	}
	
	public static function getAvailableBlocksList($PEVC, $selected_project_name) {
		$available_blocks_list = array();
		
		$P = $PEVC->getPresentationLayer();
		$layer_path = $P->getLayerPathSetting();
		$default_extension = "." . $P->getPresentationFileExtension();
		$common_project_name = $P->getCommonProjectName();
		
		$projects = $PEVC->getProjectsId();
		
		if (count($projects) < 2 || $projects[0] != $selected_project_name || $projects[1] != $common_project_name) {
			$projects = array_flip($projects);
			unset($projects[$selected_project_name]);
			unset($projects[$common_project_name]);
			
			$projects = array_merge(array($selected_project_name, $common_project_name), array_flip($projects));
		}
		
		if (empty($P->settings["presentation_blocks_path"])) //used in edit_entity_advanced.php
			launch_exception(new Exception("'PresentationLayer->settings[presentation_blocks_path]' cannot be undefined!"));
		
		$t = count($projects);
		for ($i = 0; $i < $t; $i++) {
			$project_name = $projects[$i];
			$project_path = $layer_path . $project_name;
			
			$blocks_path = $project_path . "/" . $P->settings["presentation_blocks_path"];
			$available_blocks = self::getFolderFilesList($blocks_path, $blocks_path);
			ksort($available_blocks);
			
			foreach ($available_blocks as $fp => $file) {
				$file_type = isset($file["type"]) ? $file["type"] : null;
				
				if ($file_type != "folder")
					$available_blocks_list[$project_name][] = substr($fp, strlen($fp) - strlen($default_extension)) == $default_extension ? substr($fp, 0, strlen($fp) - strlen($default_extension)) : $fp;
			}
		}
		
		//echo "<pre>";print_r($available_blocks_list);die();
		return $available_blocks_list;
	}
	
	public static function getAvailableBlockParams($PEVC, $regions_blocks, $available_blocks_list) {
		$available_block_params = array();
		
		$P = $PEVC->getPresentationLayer();
		$layer_path = $P->getLayerPathSetting();
		$default_extension = "." . $P->getPresentationFileExtension();
		$selected_project_id = $P->getSelectedPresentationId();
		
		if ($regions_blocks) {
			if (empty($P->settings["presentation_blocks_path"])) //used in edit_entity_advanced.php
				launch_exception(new Exception("'PresentationLayer->settings[presentation_blocks_path]' cannot be undefined!"));
			
			$t = count($regions_blocks);
			for ($i = 0; $i < $t; $i++) {
				$region_block = $regions_blocks[$i];
				$type = isset($region_block["type"]) ? $region_block["type"] : null;
				$is_block = $type == 2 || $type == 3;
				
				if ($is_block && isset($region_block["block_type"]) && $region_block["block_type"] == "string") {
					$block_id = isset($region_block["block"]) ? $region_block["block"] : null;
					$project = isset($region_block["block_project"]) ? $region_block["block_project"] : null;
					$project = $project ? $project : $selected_project_id;
					
					if (empty($available_blocks_list[$project]) || !in_array($block_id, $available_blocks_list[$project])) {
						$project = self::getBlockProject($block_id, $available_blocks_list);
						$project = $project ? $project : $selected_project_id;
					}
					
					$block_path = $layer_path . "$project/" . $P->settings["presentation_blocks_path"] . "$block_id$default_extension";
					
					if (file_exists($block_path)) {
						$region = PHPUICodeExpressionHandler::getArgumentCode(isset($region_block["region"]) ? $region_block["region"] : null, isset($region_block["region_type"]) ? $region_block["region_type"] : null);
						$block = PHPUICodeExpressionHandler::getArgumentCode(isset($region_block["block"]) ? $region_block["block"] : null, isset($region_block["block_type"]) ? $region_block["block_type"] : null);
					
						$available_block_params[ $region ][ $block ] = CMSFileHandler::getFileBlockParams($block_path);
					}
				}
			}
		}
		
		return $available_block_params;
	}
	
	public static function getBlockProject($block_id, $available_blocks_list) {
		if ($available_blocks_list)
			foreach ($available_blocks_list as $project_name => $blocks) 
				if (in_array($block_id, $blocks)) 
					return $project_name;
		return null;
	}
	
	public static function getAvailableBlockParamsList($available_block_params) {
		$available_block_params_list = array();
		
		foreach ($available_block_params as $region => $region_block_params)
			foreach ($region_block_params as $block => $params) 
				if ($params) {
					$t = count($params);
					for ($i = 0; $i < $t; $i++) {
						$p = $params[$i];
					
						$available_block_params_list[$region][$block][] = PHPUICodeExpressionHandler::getArgumentCode(isset($p["param"]) ? $p["param"] : null, isset($p["param_type"]) ? $p["param_type"] : null);
					}
				}
		
		return $available_block_params_list;
	}
	
	public static function getBlockParamsValuesList($block_params_values) {
		$block_params_values_list = array();
		
		if ($block_params_values) {
			$t = count($block_params_values);
			for ($i = 0; $i < $t; $i++) {
				$bpv = $block_params_values[$i];
				
				$region = PHPUICodeExpressionHandler::getArgumentCode(isset($bpv["region"]) ? $bpv["region"] : null, isset($bpv["region_type"]) ? $bpv["region_type"] : null);
				$block = PHPUICodeExpressionHandler::getArgumentCode(isset($bpv["block"]) ? $bpv["block"] : null, isset($bpv["block_type"]) ? $bpv["block_type"] : null);
				$param = PHPUICodeExpressionHandler::getArgumentCode(isset($bpv["param"]) ? $bpv["param"] : null, isset($bpv["param_type"]) ? $bpv["param_type"] : null);
				$index = isset($bpv["region_block_index"]) && is_numeric($bpv["region_block_index"]) ? $bpv["region_block_index"] : 0;
				
				$block_params_values_list[$region][$block][$index][$param] = PHPUICodeExpressionHandler::getArgumentCode(isset($bpv["value"]) ? $bpv["value"] : null, isset($bpv["value_type"]) ? $bpv["value_type"] : null);
			}
		}
		
		return $block_params_values_list;
	}
	
	public static function getBlockParamsList($PEVC, $regions_blocks, $code, $available_blocks_list) {
		$available_block_params = self::getAvailableBlockParams($PEVC, $regions_blocks, $available_blocks_list);
		$available_block_params_list = self::getAvailableBlockParamsList($available_block_params);
		
		$block_params_values = CMSFileHandler::getRegionBlockParamsValues($code);
		$block_params_values_list = self::getBlockParamsValuesList($block_params_values);
		
		return array($available_block_params_list, $block_params_values_list);
	}
	
	public static function getAvailableRegionsList($template_path, $selected_project_id, $only_not_init_region = false) {
		$code = CMSFileHandler::getFileContents($template_path);
		return self::getAvailableRegionsListFromCode($code, $selected_project_id, $only_not_init_region);
	}
	
	public static function getAvailableRegionsListFromCode($code, $selected_project_id, $only_not_init_region = false) {
		$available_regions_list = array();
		
		$template_regions_blocks = CMSFileHandler::getRegionsBlocks($code);
		$template_regions_blocks_list = self::getRegionsBlocksList($template_regions_blocks, $selected_project_id);
		$template_regions = CMSFileHandler::getRegions($code);
		
		$trbl = array();
		foreach ($template_regions_blocks_list as $rb) {
			$k = isset($rb[0]) ? $rb[0] : null;
			$trbl[$k] = true;
		}
		
		$t = count($template_regions);
		for ($i = 0; $i < $t; $i++) {
			$str = $template_regions[$i];
	
			$region = PHPUICodeExpressionHandler::getArgumentCode(isset($str["region"]) ? $str["region"] : null, isset($str["region_type"]) ? $str["region_type"] : null);
			
			if (!$only_not_init_region || !isset($trbl[$region]))
				$available_regions_list[] = $region;
		}
		
		return array_values( array_unique($available_regions_list) );
	}
	
	public static function getRegionsBlocksList($regions_blocks, $selected_project_id) {
		$regions_blocks_list = array();
		
		if ($regions_blocks) {
			$t = count($regions_blocks);
			for ($i = 0; $i < $t; $i++) {
				$region_block = $regions_blocks[$i];
				$type = isset($region_block["type"]) ? $region_block["type"] : null;
				
				$r = PHPUICodeExpressionHandler::getArgumentCode(isset($region_block["region"]) ? $region_block["region"] : null, isset($region_block["region_type"]) ? $region_block["region_type"] : null);
				$b = PHPUICodeExpressionHandler::getArgumentCode(isset($region_block["block"]) ? $region_block["block"] : null, isset($region_block["block_type"]) ? $region_block["block_type"] : null);
				$bp = $region_block["block_project"] ? PHPUICodeExpressionHandler::getArgumentCode(isset($region_block["block_project"]) ? $region_block["block_project"] : null, isset($region_block["block_project_type"]) ? $region_block["block_project_type"] : null) : '"' . $selected_project_id . '"';
				
				$regions_blocks_list[] = array($r, $b, $bp, $type);
			}
		}
		
		return $regions_blocks_list;
	}
	
	public static function getAvailableTemplateParamsList($file_path, $only_not_init_params = false) {
		$code = CMSFileHandler::getFileContents($file_path);
		return self::getAvailableTemplateParamsListFromCode($code, $only_not_init_params);
	}
	
	public static function getAvailableTemplateParamsListFromCode($code, $only_not_init_params = false) {
		$available_params_list = array();
		$available_params_values_list = self::getAvailableTemplateParamsValuesList($code);
		
		$params = CMSFileHandler::getParams($code);
		
		$t = count($params);
		for ($i = 0; $i < $t; $i++) {
			$p = $params[$i];
			
			$name = PHPUICodeExpressionHandler::getArgumentCode(isset($p["param"]) ? $p["param"] : null, isset($p["param_type"]) ? $p["param_type"] : null);
			
			if (!$only_not_init_params || !isset($available_params_values_list[$name]))
				$available_params_list[] = $name;
		}
		
		return array( array_values( array_unique($available_params_list) ), $available_params_values_list);
	}
	
	public static function getAvailableTemplateParamsValuesList($code) {
		$available_params_values_list = array();
		
		$params = CMSFileHandler::getParamsValues($code);
		
		$t = count($params);
		for ($i = 0; $i < $t; $i++) {
			$p = $params[$i];
			
			$name = PHPUICodeExpressionHandler::getArgumentCode(isset($p["param"]) ? $p["param"] : null, isset($p["param_type"]) ? $p["param_type"] : null);
			$value = PHPUICodeExpressionHandler::getArgumentCode(isset($p["value"]) ? $p["value"] : null, isset($p["value_type"]) ? $p["value_type"] : null);
			
			$available_params_values_list[$name] = $value;
		}
		//echo "<pre>";print_r($params);print_r($available_params_values_list);die();
		
		return $available_params_values_list;
	}
	
	public static function getWordPressInstallationsFoldersName($EVC) {
		$common_project_name = $EVC->getCommonProjectName();
		$wordpress_installation_root_folder = $EVC->getWebrootPath($common_project_name) . WordPressUrlsParser::WORDPRESS_FOLDER_PREFIX . "/";
		
		$files = file_exists($wordpress_installation_root_folder) ? array_diff(scandir($wordpress_installation_root_folder), array('..', '.')) : array();
		
		if ($files)
			foreach ($files as $idx => $file) 
				if (!is_dir($wordpress_installation_root_folder . $file))
					unset($files[$idx]);
		
		return array_values($files);
	}
	
	public static function getFileCodeSetTemplate($code) {
		$templates = CMSFileHandler::getTemplates($code);
		
		if (!empty($templates[0])) {
			$template_code = isset($templates[0]["template"]) ? $templates[0]["template"] : null;
			$template_params = isset($templates[0]["template_params"]) ? $templates[0]["template_params"] : null;
			
			if (isset($templates[0]["template_params_type"]) && $templates[0]["template_params_type"] == "array" && is_array($template_params))
				$template_params = CMSPresentationLayerJoinPointsUIHandler::convertBlockSettingsArrayToObj($template_params);
			
			return array(
				"template_code" => $template_code,
				"template_params" => $template_params,
			);
		}
	}
	
	public static function isSetTemplateParamsValid($EVC, $set_template) {
		if (!$set_template)
			return false;
		
		$selected_template = isset($set_template["template_code"]) ? $set_template["template_code"] : null;
		$selected_template_params = isset($set_template["template_params"]) ? $set_template["template_params"] : null;
		
		if (is_array($selected_template_params)) {
			$selected_template_param_type = isset($selected_template_params["type"]) ? $selected_template_params["type"] : null;
			$selected_template_param_project_id = isset($selected_template_params["project_id"]) ? $selected_template_params["project_id"] : null;
		}
		
		if (isset($selected_template_params) && ( //if selected_template_params exists
			($selected_template && $selected_template != "parse_php_code") || //and if is different than parse_php_code
			(!empty($selected_template_param_type) && !empty($selected_template_param_type["value"]) && (!isset($selected_template_param_type["value_type"]) || $selected_template_param_type["value_type"] != 'string')) || //or if selected_template_param_type is not a string. If not a string we cannot know if type is correct! This check is done in other methods, but this method makes sure the selected_template_param_type is a string, so we can use this in other methods!
			(!empty($selected_template_param_type) && !empty($selected_template_param_type["value"]) && (
				empty($selected_template_param_project_id) || //or if selected_template_param_project_id is empty
				empty($selected_template_param_project_id["value"]) || //or if selected_template_param_project_id is empty
				(isset($selected_template_param_project_id["value_type"]) && $selected_template_param_project_id["value_type"] == "string" && $selected_template_param_project_id["value"] != $EVC->getCommonProjectName()) || //or if selected_template_param_project_id is not equal to common
				((!isset($selected_template_param_project_id["value_type"]) || $selected_template_param_project_id["value_type"] != "string") && preg_replace("/\s+/", "", $selected_template_param_project_id["value"]) != '$EVC->getCommonProjectName()') //or if selected_template_param_project_id is not equal to $PEVC->getCommonProjectName()
			))
		) )
			return false;
		
		return true;
	}
	
	public static function isSetTemplateExternalTemplate($EVC, $set_template) {
		$selected_template = isset($set_template["template_code"]) ? $set_template["template_code"] : null;
		$set_template_params = isset($set_template["template_params"]) ? $set_template["template_params"] : null;
		$set_template_param_project_id = is_array($set_template_params) && isset($set_template_params["project_id"]) ? $set_template_params["project_id"] : null;
		
		return $selected_template == "parse_php_code" && $set_template_param_project_id && isset($set_template_param_project_id["value"]) && (
			(isset($set_template_param_project_id["value_type"]) && $set_template_param_project_id["value_type"] == "string" && $set_template_param_project_id["value"] == $EVC->getCommonProjectName()) || //or if set_template_param_project_id is not equal to common
			((!isset($set_template_param_project_id["value_type"]) || $set_template_param_project_id["value_type"] != "string") && preg_replace("/\s+/", "", $set_template_param_project_id["value"]) == '$EVC->getCommonProjectName()') //or if set_template_param_project_id is not equal to $EVC->getCommonProjectName()
		);
	}
	
	public static function getSetTemplateCode($EVC, $is_external_template, $template, $set_template_params, $template_includes) {
		$code = null;
		
		if ($is_external_template) {
			//prepare external vars
			$before_defined_vars = get_defined_vars();
			include $EVC->getConfigPath("config");
			$after_defined_vars = get_defined_vars();
			$external_vars = array_diff_key($after_defined_vars, $before_defined_vars);
			$external_vars["EVC"] = $EVC;
			unset($external_vars["before_defined_vars"]);
			
			//convert $set_template_params into real array
			$set_template_params_arr_code = '<?php return ' . WorkFlowTask::getArrayString($set_template_params) . '; ?>';
			PHPScriptHandler::parseContent($set_template_params_arr_code, $external_vars, $return_values);
			$template_params = isset($return_values[0]) ? $return_values[0] : null;
			//print_r($template_params);die();
			
			//include external files
			include_once $EVC->getUtilPath("include_text_translator_handler", $EVC->getCommonProjectName());
			
			if ($template_includes)
				foreach ($template_includes as $include) 
					if (!empty($include["path"]))
						eval("include" . (!empty($include["once"]) ? "_once" : "") . " " . $include["path"] . ";");
			
			//set the CMSExternalTemplateLayer to replica dynamically the regions and blocks from the project template.
			if ($template_params && isset($template_params["type"]) && $template_params["type"] == "project")
				$template_params["add_template_xml_regions_and_params"] = true;
			
			//get template code
			$code = CMSExternalTemplateLayer::getTemplateCode($EVC, $template_params, $external_vars);
			//echo $code;die();
		}
		else if ($template) {
			$template_path = $EVC->getTemplatePath($template);
			$code = CMSFileHandler::getFileContents($template_path);
		}
		
		return $code;
	}
	
	public static function getFileAddBlockJoinPointsListByBlock($block_path) {
		$block_join_points = CMSFileHandler::getFileAddBlockJoinPoints($block_path);
		//echo "<pre>";print_r($block_join_points);die();
		return self::getAddBlockJoinPointsListByBlock($block_join_points);
	}
	
	public static function getAddBlockJoinPointsListByBlock($block_join_points) {
		$jps = array();
		
		if (is_array($block_join_points)) {
			foreach ($block_join_points as $block_join_point) {
				$block = PHPUICodeExpressionHandler::getArgumentCode(isset($block_join_point["block"]) ? $block_join_point["block"] : null, isset($block_join_point["block_type"]) ? $block_join_point["block_type"] : null);
				
				$jps[$block][] = $block_join_point;
			}
		}
		
		return $jps;
	}
	
	public static function getFileAddRegionBlockJoinPointsListByBlock($block_path) {
		$block_join_points = CMSFileHandler::getFileAddRegionBlockJoinPoints($block_path);
		return self::getAddRegionBlockJoinPointsListByBlock($block_join_points);
	}
	
	public static function getAddRegionBlockJoinPointsListByBlock($block_join_points) {
		$jps = array();
		
		if (is_array($block_join_points))
			foreach ($block_join_points as $block_join_point) {
				$region = PHPUICodeExpressionHandler::getArgumentCode(isset($block_join_point["region"]) ? $block_join_point["region"] : null, isset($block_join_point["region_type"]) ? $block_join_point["region_type"] : null);
				$block = PHPUICodeExpressionHandler::getArgumentCode(isset($block_join_point["block"]) ? $block_join_point["block"] : null, isset($block_join_point["block_type"]) ? $block_join_point["block_type"] : null);
				$index = isset($block_join_point["region_block_index"]) && is_numeric($block_join_point["region_block_index"]) ? $block_join_point["region_block_index"] : 0;
				
				$jps[$region][$block][$index][] = $block_join_point;
			}
		
		return $jps;
	}
	
	public static function getFileBlockLocalJoinPointsListByBlock($block_path) {
		$jps = array();
		
		$block_join_points = CMSFileHandler::getFileBlockLocalJoinPoints($block_path);
		
		if (is_array($block_join_points))
			foreach ($block_join_points as $block_join_point) {
				$block = PHPUICodeExpressionHandler::getArgumentCode(isset($block_join_point["block"]) ? $block_join_point["block"] : null, isset($block_join_point["block_type"]) ? $block_join_point["block_type"] : null);
				
				$jps[$block][] = $block_join_point;
			}
		
		return $jps;
	}
	
	public static function getFilePageProperties($entity_path) {
		$props = array();
		
		$file_props = CMSFileHandler::getFilePageProperties($entity_path);
		
		if (is_array($file_props))
			foreach ($file_props as $file_prop) {
				$name = isset($file_prop["prop_name"]) ? $file_prop["prop_name"] : null;
				$prop_value = isset($file_prop["prop_value"]) ? $file_prop["prop_value"] : null;
				$prop_value_type = isset($file_prop["prop_value_type"]) ? $file_prop["prop_value_type"] : null;
				
				if ($prop_value === null && $prop_value_type === null) //means that there are no param value
					$value = PHPUICodeExpressionHandler::getArgumentCode(isset($file_prop["prop_default_value"]) ? $file_prop["prop_default_value"] : null, isset($file_prop["prop_default_value_type"]) ? $file_prop["prop_default_value_type"] : null);
				else
					$value = PHPUICodeExpressionHandler::getArgumentCode($prop_value, $prop_value_type);
				
				//note that $value will always be a string, due to the getArgumentCode method
				$v = $value !== null ? strtolower($value) : "";
				
				$props[$name] = !strlen($v) || $v == "null" ? null : ($v == "false" || $v == "0" || $v == '"0"' || $v == '""' || $v == "''" ? false : (is_numeric($v) ? $v : true));
			}
		
		return $props;
	}
	
	public static function createCMSLayerCodeForIncludes($includes) {
		$code = "";
		
		if ($includes) {
			$t = count($includes);
			for ($i = 0; $i < $t; $i++) {
				$include = $includes[$i];
			
				if (!empty($include["path"])) {
					$code .= 'include' . (!empty($include["once"]) ? '_once' : '') . ' ' . PHPUICodeExpressionHandler::getArgumentCode($include["path"], isset($include["path_type"]) ? $include["path_type"] : null) . ';' . "\n";
				}
			}
			
			$code = $code ? "//Includes\n$code" : "";
		}
		
		return $code;
	}
	
	public static function createCMSLayerCodeForRegionsBLocks($selected_project_id, $default_extension, $regions_blocks, $regions_blocks_params = false, $regions_blocks_join_points = false) {
		$code = "";
		
		if ($regions_blocks) {
			$t = count($regions_blocks);
			for ($i = 0; $i < $t; $i++) {
				$region_block = $regions_blocks[$i];
				
				if (!empty($region_block["region"]) && !empty($region_block["block"])) {
					$rb_type = isset($region_block["type"]) ? $region_block["type"] : null;
					$rb_region = isset($region_block["region"]) ? $region_block["region"] : null;
					$rb_region_type = isset($region_block["region_type"]) ? $region_block["region_type"] : null;
					$rb_block = isset($region_block["block"]) ? $region_block["block"] : null;
					$rb_block_type = isset($region_block["block_type"]) ? $region_block["block_type"] : null;
					$rb_block_project = isset($region_block["block_project"]) ? $region_block["block_project"] : null;
					
					$region_id = PHPUICodeExpressionHandler::getArgumentCode($rb_region, $rb_region_type);
					
					$code .= "\n";
					
					//Note that this addRegionHtml($region_id, $block_id) must be called before the 'include $EVC->getBlockPath("block_id");', otherwise the $CMSBlockLayer->stopBlockRegions($block_id) won't work because it doesn't know the regions for the correspondent block.
					if ($rb_type == 1) { //if html
						$block_type = PHPUICodeExpressionHandler::getValueType($rb_block, array("empty_string_type" => "string", "non_set_type" => "string"));
						$html = PHPUICodeExpressionHandler::getArgumentCode($rb_block, $block_type);
						$code .= '$EVC->getCMSLayer()->getCMSTemplateLayer()->addRegionHtml(' . $region_id . ', ' . $html . ');' . "\n";
					}
					else if ($rb_type == 2 || $rb_type == 3) { //if block
						$block_id = PHPUICodeExpressionHandler::getArgumentCode($rb_block, $rb_block_type);
						$block_project_type = PHPUICodeExpressionHandler::getValueType($rb_block_project, array("empty_string_type" => "string", "non_set_type" => "string"));
						$block_project = $block_project_type == "" && $rb_block_project ? str_replace('"', '', $rb_block_project) : $rb_block_project; //The block_project from the edit_simple_template_layout contains quotes in some cases when coming from the .
						$bp = !$block_project || $block_project == $selected_project_id ? "" : ", \"$block_project\"";
						
						$exists = false;
						
						if (!empty($regions_blocks_params[$i])) {
							$t2 = count($regions_blocks_params[$i]);
							for ($j = 0; $j < $t2; $j++) {
								$region_block_param = $regions_blocks_params[$i][$j];
								$rbp_region = isset($region_block_param["region"]) ? $region_block_param["region"] : null;
								$rbp_region_type = isset($region_block_param["region_type"]) ? $region_block_param["region_type"] : null;
								$rbp_block = isset($region_block_param["block"]) ? $region_block_param["block"] : null;
								$rbp_block_type = isset($region_block_param["block_type"]) ? $region_block_param["block_type"] : null;
								$rbp_name = isset($region_block_param["name"]) ? $region_block_param["name"] : null;
								$rbp_name_type = isset($region_block_param["name_type"]) ? $region_block_param["name_type"] : null;
								$rbp_value = isset($region_block_param["value"]) ? $region_block_param["value"] : null;
								$rbp_value_type = isset($region_block_param["value_type"]) ? $region_block_param["value_type"] : null;
								
								if (PHPUICodeExpressionHandler::getArgumentCode($rbp_region, $rbp_region_type) == $region_id && PHPUICodeExpressionHandler::getArgumentCode($rbp_block, $rbp_block_type) == $block_id && $rbp_name && strlen($rbp_value)) {
							
									$code .= '$region_block_local_variables[' . $region_id . '][' . $block_id . '][' . PHPUICodeExpressionHandler::getArgumentCode($rbp_name, $rbp_name_type) . '] = ' . PHPUICodeExpressionHandler::getArgumentCode($rbp_value, $rbp_value_type) . ';' . "\n";
							
									$exists = true;
								}
							}
						}
						
						if ($exists)
							$code .= '$block_local_variables = $region_block_local_variables[' . $region_id . '][' . $block_id . '];' . "\n";
						else 
							$code .= '$block_local_variables = array();' . "\n";
						
						$regions_blocks_join_point = isset($regions_blocks_join_points[$i]) ? $regions_blocks_join_points[$i] : null;
						$code .= self::createCMSLayerCodeForRegionBLockJoinPoints($region_id, $block_id, $regions_blocks_join_point);
						$code .= '$EVC->getCMSLayer()->getCMSTemplateLayer()->addRegionBlock(' . $region_id . ', ' . $block_id . $bp . ');' . "\n";
						
						//This code must be executed after the addRegionHtml($region_id, $block_id), otherwise the $CMSBlockLayer->stopBlockRegions($block_id) won't work correctly.
						$code .= 'include $EVC->getBlockPath(' . $block_id . $bp . ');' . "\n";
					}
					else if ($rb_type == 4 || $rb_type == 5) { //if view
						$block_id = PHPUICodeExpressionHandler::getArgumentCode($rb_block, $rb_block_type);
						$block_project_type = PHPUICodeExpressionHandler::getValueType($rb_block_project, array("empty_string_type" => "string", "non_set_type" => "string"));
						$block_project = $block_project_type == "" && $rb_block_project ? str_replace('"', '', $rb_block_project) : $rb_block_project; //The block_project from the edit_simple_template_layout contains quotes in some cases when coming from the .
						$bp = !$block_project || $block_project == $selected_project_id ? "" : ", \"$block_project\"";
						
						$code .= 'include $EVC->getCMSLayer()->getCMSTemplateLayer()->includeRegionViewPathOutput(' . $region_id . ', ' . $block_id . $bp . ');' . "\n";
					}
				}
			}
			
			$code = $code ? "//Regions-Blocks:$code" : "";
		}
		
		return $code;
	}
	
	private static function createCMSLayerCodeForRegionBLockJoinPoints($region_id, $block_id, $regions_blocks_join_points) {
		$code = '$EVC->getCMSLayer()->getCMSJoinPointLayer()->resetRegionBlockJoinPoints(' . $region_id . ', ' . $block_id . ');
';
		
		//echo "<pre>";print_r($regions_blocks_join_points);die();
		
		if (is_array($regions_blocks_join_points)) {
			foreach ($regions_blocks_join_points as $region_block_join_points) {
				$rb_region = isset($region_block_join_points["region"]) ? $region_block_join_points["region"] : null;
				$rb_region_type = isset($region_block_join_points["region_type"]) ? $region_block_join_points["region_type"] : null;
				$rb_block = isset($region_block_join_points["block"]) ? $region_block_join_points["block"] : null;
				$rb_block_type = isset($region_block_join_points["block_type"]) ? $region_block_join_points["block_type"] : null;
				
				$r_id = PHPUICodeExpressionHandler::getArgumentCode($rb_region, $rb_region_type);
				$b_id = PHPUICodeExpressionHandler::getArgumentCode($rb_block, $rb_block_type);
				
				if ($r_id == $region_id && $b_id == $block_id && !empty($region_block_join_points["join_points"]))
					foreach ($region_block_join_points["join_points"] as $join_point_name => $join_point) {
						foreach ($join_point as $idx => $item) {
							if (is_numeric($idx)) {//just in case
								$code .= '$block_join_point_properties = ' . self::getJoinPointPropertiesCode($item) . ';
$EVC->getCMSLayer()->getCMSJoinPointLayer()->addRegionBlockJoinPoint(' . $region_id . ', ' . $block_id . ', "' . $join_point_name . '", $block_join_point_properties);
';
							}
						}
					}
					
					break;
			}
		}
		
		return $code;
	}
	
	public static function createCMSLayerCodeForTemplateParams($template_params) {
		$code = "";
	
		if ($template_params) {
			$t = count($template_params);
			for ($i = 0; $i < $t; $i++) {
				$p = $template_params[$i];
				
				if (!empty($p["param"]) && isset($p["value"]) && strlen($p["value"])) {
					$param = PHPUICodeExpressionHandler::getArgumentCode($p["param"], isset($p["param_type"]) ? $p["param_type"] : null);
					$value = PHPUICodeExpressionHandler::getArgumentCode($p["value"], isset($p["value_type"]) ? $p["value_type"] : null);
					
					$code .= '$EVC->getCMSLayer()->getCMSTemplateLayer()->setParam(' . $param . ', ' . $value . ');' . "\n";
				}
			}
			
			$code = $code ? "//Template params:\n$code" : "";
		}
		
		return $code;
	}
	
	public static function createCMSLayerCodeForTemplates($templates) {
		$code = "";
		
		if ($templates) {
			$t = count($templates);
			for ($i = 0; $i < $t; $i++) {
				$template = $templates[$i];
				
				if (!empty($template["template"])) {
					$template_type = isset($template["template_type"]) ? $template["template_type"] : null;
					$template_args = isset($template["template_args"]) ? $template["template_args"] : null;
					$template_args_code = self::createArrayCode($template_args);
					$template_args_code = $template_args_code ? ", $template_args_code" : "";
					
					$code .= '$EVC->setTemplate(' . PHPUICodeExpressionHandler::getArgumentCode($template["template"], $template_type) . $template_args_code . ');' . "\n";
				}
			}
			
			$code = $code ? "//Templates:\n$code" : "";
		}
		
		return $code;
	}
	
	public static function createCMSLayerCodeForPagePropertiesCode($page_properties) {
		$code = "";
		
		if ($page_properties) {
			$class = new ReflectionClass("CMSPagePropertyLayer");
			$class_methods = $class->getMethods();
			$available_page_properties = array();
			
			foreach ($class_methods as $ReflectionMethod) {
				$method_name = $ReflectionMethod->getName();
				
				if (substr($method_name, 0, 3) == "set") {
					$params = $ReflectionMethod->getParameters();
					$ReflectionParameter = isset($params[0]) ? $params[0] : null;
					
					$available_page_properties[ $ReflectionParameter->getName() ] = $method_name;
				}
			}
			
			foreach ($page_properties as $k => $v) {
				if ($k == "parse_html" && $v) { //$v can be: 0, 1 or 2. If 0, do not add it to the code, bc is the default value in CMSPagePropertyLayer.
					if ($v === 1 || $v === "1")
						$code .= "\$EVC->getCMSLayer()->getCMSPagePropertyLayer()->setParseFullHtml(true);\n";
					else if ($v === 2 || $v === "2")
						$code .= "\$EVC->getCMSLayer()->getCMSPagePropertyLayer()->setParseRegionsHtml(true);\n";
				}
				else if (!empty($available_page_properties[$k]) && $v !== "") { //$v can be: 0, 1 or empty string (this is: ""). if $v === "", it means "auto" flag which is the default, so we should not add it to the code, bc "auto" is the default value in CMSPagePropertyLayer. This is, only add property which are 1 (true) or 0 (false).
					$method_name = $available_page_properties[$k];
					$v = $v === 0 || $v === "0" ? "false" : ($v === 1 || $v === "1" ? "true" : $v);
					$code .= "\$EVC->getCMSLayer()->getCMSPagePropertyLayer()->$method_name($v);\n";
				}
			}
			
			if ($code)
				$code = "//PAGE PROPERTIES:\n" . $code;
		}
		
		return $code;
	}
	
	public static function createCMSLayerCodeForSequentialLogicalActivitySettingsCode($sla_settings_code) {
		$code = "";
		
		if ($sla_settings_code) {
			$code = "//SLA ACTIONS SETTINGS:\n";
			$code .= "\$EVC->getCMSLayer()->getCMSSequentialLogicalActivityLayer()->addSequentialLogicalActivities($sla_settings_code);\n";
		}
		
		return $code;
	}
	
	public static function checkIfEntityCodeContainsSimpleUISettings($code, $selected_template, $includes, $regions_blocks, $selected_project_id) {
		if ($code) {
			if ($selected_template) {
				$templates = CMSFileHandler::getTemplates($code);
				$template = isset($templates[0]["template"]) ? $templates[0]["template"] : null;
				$template_type = isset($templates[0]["template_type"]) ? $templates[0]["template_type"] : null;
				
				$to_check = '$EVC->setTemplate(' . PHPUICodeExpressionHandler::getArgumentCode($template, $template_type);
				
				if (strpos($code, $to_check) === false)
					return false;
			}
			
			if ($includes) {
				$to_check = "";
				
				$t = count($includes);
				for ($i = 0; $i < $t; $i++) {
					$include = $includes[$i];
				
					if (!empty($include["path"])) {
						$to_check .= 'include' . (!empty($include["once"]) ? '_once' : '') . ' ' . PHPUICodeExpressionHandler::getArgumentCode($include["path"], isset($include["path_type"]) ? $include["path_type"] : null) . ';' . "\n";
					}
				}
				
				if ($to_check && strpos($code, $to_check) === false)
					return false;
			}
			
			if ($regions_blocks) {
				$t = count($regions_blocks);
				for ($i = 0; $i < $t; $i++) {
					$region_block = $regions_blocks[$i];
					
					if (!empty($region_block["region"]) && !empty($region_block["block"])) {
						$rb_type = $region_block["type"];
						$rb_region = isset($region_block["region"]) ? $region_block["region"] : null;
						$rb_region_type = isset($region_block["region_type"]) ? $region_block["region_type"] : null;
						$rb_block = isset($region_block["block"]) ? $region_block["block"] : null;
						$rb_block_type = isset($region_block["block_type"]) ? $region_block["block_type"] : null;
						$rb_block_project = isset($region_block["block_project"]) ? $region_block["block_project"] : null;
						
						$region_id = PHPUICodeExpressionHandler::getArgumentCode($rb_region, $rb_region_type);
						$to_check = null;
						
						if ($rb_type == 1) { //if html
							$block_type = PHPUICodeExpressionHandler::getValueType($rb_block, array("empty_string_type" => "string", "non_set_type" => "string"));
							$html = PHPUICodeExpressionHandler::getArgumentCode($rb_block, $block_type);
							$to_check = '$EVC->getCMSLayer()->getCMSTemplateLayer()->addRegionHtml(' . $region_id . ', ' . $html . ');' . "\n";
						}
						else if ($rb_type == 2 || $rb_type == 3) { //if block
							$block_id = PHPUICodeExpressionHandler::getArgumentCode($rb_block, $rb_block_type);
							$block_project = $rb_block_project;
							$bp = !$block_project || $block_project == $selected_project_id ? "" : ", \"$block_project\"";
							
							$to_check = '$EVC->getCMSLayer()->getCMSTemplateLayer()->addRegionBlock(' . $region_id . ', ' . $block_id . $bp . ');' . "\n";
							$to_check .= 'include $EVC->getBlockPath(' . $block_id . $bp . ');' . "\n";
						}
						else if ($rb_type == 4 || $rb_type == 5) { //if view
							$block_id = PHPUICodeExpressionHandler::getArgumentCode($rb_block, $rb_block_type);
							$block_project = $rb_block_project;
							$bp = !$block_project || $block_project == $selected_project_id ? "" : ", \"$block_project\"";
							
							$to_check = 'include $EVC->getCMSLayer()->getCMSTemplateLayer()->includeRegionViewPathOutput(' . $region_id . ', ' . $block_id . $bp . ');' . "\n";
						}
						
						if (strpos($code, $to_check) === false)
							return false;
					}
				}
			}
		}
		
		return true;
	}
	
	public static function createEntityCode($object, $selected_project_id, $default_extension) {
		$regions_blocks_join_points = isset($object["regions_blocks_join_points"]) ? $object["regions_blocks_join_points"] : null;//get this before the arrKeysToLowerCase because there are keys that we want to maintain case sensitive
		
		MyArray::arrKeysToLowerCase($object, true);//I belieev this is not necessary anymore, but confirm before delete this line.
		
		$templates = isset($object["templates"]) ? $object["templates"] : null;
		$regions_blocks = isset($object["regions_blocks"]) ? $object["regions_blocks"] : null;
		$other_regions_blocks = isset($object["other_regions_blocks"]) ? $object["other_regions_blocks"] : null;
		$regions_blocks_params = isset($object["regions_blocks_params"]) ? $object["regions_blocks_params"] : null;
		$includes = isset($object["includes"]) ? $object["includes"] : null;
		$template_params = isset($object["template_params"]) ? $object["template_params"] : null;
		$other_template_params = isset($object["other_template_params"]) ? $object["other_template_params"] : null;
		$advanced_settings = isset($object["advanced_settings"]) ? $object["advanced_settings"] : null;
		$sla_settings_code = isset($object["sla_settings_code"]) ? $object["sla_settings_code"] : null;
		
		$includes_code = self::createCMSLayerCodeForIncludes($includes);
		$page_properties_code = self::createCMSLayerCodeForPagePropertiesCode($advanced_settings);
		$sla_code = self::createCMSLayerCodeForSequentialLogicalActivitySettingsCode($sla_settings_code);
		$templates_code = self::createCMSLayerCodeForTemplates($templates);
		$tp_code = self::createCMSLayerCodeForTemplateParams($template_params);
		$otp_code = str_replace("//Template params:", "//Other Template params:", self::createCMSLayerCodeForTemplateParams($other_template_params));
		$rb_code = self::createCMSLayerCodeForRegionsBLocks($selected_project_id, $default_extension, $regions_blocks, $regions_blocks_params, $regions_blocks_join_points);
		$orb_code = str_replace("//Regions-Blocks:", "//Other Regions-Blocks:", self::createCMSLayerCodeForRegionsBLocks($selected_project_id, $default_extension, $other_regions_blocks, $regions_blocks_params, $regions_blocks_join_points));
		
		$ec = $includes_code;
		$ec .= trim($page_properties_code) ? "\n" . $page_properties_code : "";
		$ec .= trim($sla_code) ? "\n" . $sla_code : ""; //must be before the region-blocks happened, bc the "resource.php" controller will stop the php script when the CMSSequentialLogicalActivityLayer->addSequentialLogicalActivities method gets called. Basically the idea of the "resource.php" controller is to only execute a resource and nothing else, so we need to ignore all the includes for the region-block files.
		//Then we can add the following code:
		$ec .= trim($templates_code) ? "\n" . $templates_code : ""; //must be before the blocks get called bc the blocks can use the template to get the html template for each module.
		$ec .= trim($tp_code) ? "\n" . $tp_code : "";
		$ec .= trim($otp_code) ? "\n" . $otp_code : "";
		$ec .= trim($rb_code) ? "\n" . $rb_code : "";
		$ec .= trim($orb_code) ? "\n" . $orb_code : "";
		
		return trim($ec) ? "<?php \n$ec?>" : "";
	}
	
	public static function checkIfBlockCodeContainsSimpleUISettings($code, $module_id) {
		if ($code) {
			$to_check = '$block_id = $EVC->getCMSLayer()->getCMSBlockLayer()->getBlockIdFromFilePath(__FILE__);';
			if (strpos($code, $to_check) === false)
				return false;
			
			$to_check = '$block_settings[$block_id] = ';
			if (strpos($code, $to_check) === false)
				return false;
			
			$to_check = '$EVC->getCMSLayer()->getCMSBlockLayer()->createBlock("' . $module_id . '", $block_id, $block_settings[$block_id]);';
			if (strpos($code, $to_check) === false)
				return false;
		}
		
		return true;
	}
	
	public static function createBlockCode($obj) {
		$module_id = isset($obj["module_id"]) ? $obj["module_id"] : null;
		$code = isset($obj["settings"]) ? self::createArrayCode($obj["settings"], "") : (isset($obj["code"]) ? $obj["code"] : "");
		$code = trim($code);
		
		$join_points = isset($obj["join_points"]) ? self::createBlockJoinPointsCode($obj["join_points"]) : "";
		
		return '<?php
$block_id = $EVC->getCMSLayer()->getCMSBlockLayer()->getBlockIdFromFilePath(__FILE__);//must be the same than this file name.

$block_settings[$block_id] = ' . ($code ? $code : "null") . ';
' . $join_points . '
$EVC->getCMSLayer()->getCMSBlockLayer()->createBlock("' . $module_id . '", $block_id, $block_settings[$block_id]);
?>';
	}
	
	private static function createBlockJoinPointsCode($join_points) {
		$code = '';
		
		//echo "<pre>";print_r($join_points);die();
		
		if (is_array($join_points)) {
			foreach ($join_points as $join_point_name => $join_point) {
				if (!empty($join_point["active"])) {
					if ($join_point["active"] == 2)
						$code .= '
$block_local_join_points[$block_id]["' . $join_point_name . '"] = 1;
';
						
					foreach ($join_point as $idx => $item) {
						if (is_numeric($idx)) {//excludes the active key
							$code .= '
$block_join_point_properties = ' . self::getJoinPointPropertiesCode($item) . ';
$EVC->getCMSLayer()->getCMSJoinPointLayer()->addBlockJoinPoint($block_id, "' . $join_point_name . '", $block_join_point_properties);
';
						}
					}
				}
			}
		}
		
		return $code;
	}
	
	//This is called in src/entity/presentation/get_page_block_simulated_html.php
	public static function getJoinPointPropertiesCode($item) {
		//Replace $input by \$input, because the $input var needs to be escaped becuase is an internal variable of the join points.
		if (!empty($item["method_args"])) {
			//echo"<pre>";print_r($item["method_args"]);die();
			
			foreach ($item["method_args"] as $i => $method_arg) {
				$method_arg_value = isset($method_arg["value"]) ? $method_arg["value"] : null;
				
				//This code is very important, because the $method_arg_value will only be used in the JoinPointHandler.php, in the eval() function, so it must be inside of ".
				$is_outside_variable = substr($method_arg_value, 0, 1) == '$' && substr($method_arg_value, 0, 6) != '$input';
				$is_outside_variable = !$is_outside_variable ? substr($method_arg_value, 0, 2) == '@$' && substr($method_arg_value, 0, 7) != '@$input' : $is_outside_variable;
				
				if (!$is_outside_variable && (substr($method_arg_value, 0, 1) != '"' || substr($method_arg_value, -1, 1) != '"')) {
					$method_arg_value = '"' . addcslashes($method_arg_value, '"') . '"';//do not add the slashes like: addcslashes($method_arg_value, '\\"') otherwise it will create weird php code. 
				}
				
				$item["method_args"][$i]["value"] = preg_replace_callback('/(.*)(\$[\w]+)(.*)/u', function($matches) { //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars.
						if ($matches[2] == '$input' && substr($matches[1], -1, 1) != '\\') //previous char
							$matches[2] = '\\' . $matches[2];
					
						return $matches[1] . $matches[2] . $matches[3];
					}, $method_arg_value);
			}
		}
		
		return self::createArrayCode($item, "");
	}
	
	private static function createArrayCode($arr, $prefix = "") {
		$code = '';
		
		if (is_array($arr)) {
			$code = "array(\n";
			
			$are_all_numeric_sequential_keys = true;
			$i = 0;
			foreach ($arr as $key => $val) {
				if (!is_numeric($key) || $key != $i) { //2020-11-18 JP: Very important the $key to be the same that the $i bc we may want to save the numeric keys if they are not sequential. Example: if $arr is equal to array(2 => "something"), we want to save the key 2! This happens with the widgets control options in wordpress. 
					$are_all_numeric_sequential_keys = false;
					break;
				}
				$i++;
			}
			
			foreach ($arr as $key => $val) {
				$code .= $prefix . "\t";
				
				if (!$are_all_numeric_sequential_keys) {
					$key = substr($key, 0, 1) == '$' || substr($key, 0, 2) == '@$' || strpos($key, '"') !== false || strpos($key, "'") !== false ? $key : '"' . $key . '"';
					$code .= $key . " => ";
				}
				
				if (is_array($val))
					$code .= self::createArrayCode($val, $prefix . "\t");
				else {
					/*$type = substr($val, 0, 1) == '$' || substr($val, 0, 2) == '@$' ? "variable" : (strpos($val, '"') !== false || strpos($val, "'") !== false ? "" : gettype($val));
					
					switch(strtolower($type)) {
						case 'string':
							$code .= '"' . $val . '"';
							break;
						case 'null':
							$code .= "null";
							break;
						default :
							$code .= $val;
					}
					*/
					$type = PHPUICodeExpressionHandler::getValueType($val, array("empty_string_type" => "string"));
					$code .= PHPUICodeExpressionHandler::getArgumentCode($val, $type);
				}
				
				$code .= ",\n";
			}
			
			$code .= $prefix . ")";
		}
		
		return $code;
	}
}
?>
