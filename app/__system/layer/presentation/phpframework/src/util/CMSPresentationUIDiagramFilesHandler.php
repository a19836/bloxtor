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

include $EVC->getUtilPath("CMSPresentationUIAutomaticFilesHandler");
include $EVC->getUtilPath("CMSPresentationFormSettingsUIHandler");

class CMSPresentationUIDiagramFilesHandler {
	
	private static $auth_validation_block_id = null;
	private static $access_id = null;
	private static $object_type_page_id = null;
	private static $logout_block_id = null;
	private static $logout_page_id = null;
	private static $login_block_id = null;
	private static $login_page_id = null;
	private static $register_block_id = null;
	private static $register_page_id = null;
	private static $forgot_credentials_block_id = null;
	private static $forgot_credentials_page_id = null;
	private static $edit_profile_block_id = null;
	private static $edit_profile_page_id = null;
	private static $list_and_edit_users_block_id = null;
	private static $list_and_edit_users_page_id = null;
	private static $list_users_block_id = null;
	private static $list_users_page_id = null;
	private static $edit_user_block_id = null;
	private static $edit_user_page_id = null;
	private static $add_user_block_id = null;
	private static $add_user_page_id = null;
	
	public static function getAuthPageAndBlockIds() {
		return array(
			"auth_validation_block_id" => self::$auth_validation_block_id,
			"access_id" => self::$access_id,
			"object_type_page_id" => self::$object_type_page_id,
			"logout_block_id" => self::$logout_block_id,
			"logout_page_id" => self::$logout_page_id,
			"login_block_id" => self::$login_block_id,
			"login_page_id" => self::$login_page_id,
			"register_block_id" => self::$register_block_id,
			"register_page_id" => self::$register_page_id,
			"forgot_credentials_block_id" => self::$forgot_credentials_block_id,
			"forgot_credentials_page_id" => self::$forgot_credentials_page_id,
			"edit_profile_block_id" => self::$edit_profile_block_id,
			"edit_profile_page_id" => self::$edit_profile_page_id,
			"list_and_edit_users_block_id" => self::$list_and_edit_users_block_id,
			"list_and_edit_users_page_id" => self::$list_and_edit_users_page_id,
			"list_users_block_id" => self::$list_users_block_id,
			"list_users_page_id" => self::$list_users_page_id,
			"edit_user_block_id" => self::$edit_user_block_id,
			"edit_user_page_id" => self::$edit_user_page_id,
			"add_user_block_id" => self::$add_user_block_id,
			"add_user_page_id" => self::$add_user_page_id,
		);
	}
	
	public static function addUserAccessControlToVarsFile($PEVC, $vars_file_id, $list_and_edit_users, $vars) {
		$file_path = $PEVC->getConfigPath($vars_file_id);
		$dir_path = dirname($file_path);
		$allowed_list_and_edit_users_types = "";
		
		if (is_array($list_and_edit_users)) {
			$str = "";
			
			foreach ($list_and_edit_users as $ut) {
				$ut = trim($ut);
				
				if ($ut && is_numeric($ut))
					$str .= ($str ? "," : "") . $ut;
			}
			
			$allowed_list_and_edit_users_types = $str;
		}
		else
			$allowed_list_and_edit_users_types = trim($list_and_edit_users);
		
		$long_code = '<?php
$allowed_list_and_edit_users_types = array(' . $allowed_list_and_edit_users_types . ');';

		if ($vars)
			$long_code .= '

if (empty($GLOBALS["UserSessionActivitiesHandler"])) {
	@include_once $EVC->getUtilPath("user_session_activities_handler", $EVC->getCommonProjectName());
	
	if (function_exists("initUserSessionActivitiesHandler"))
		initUserSessionActivitiesHandler($EVC);
}

if (!empty($GLOBALS["UserSessionActivitiesHandler"]) && $allowed_list_and_edit_users_types) {
	$logged_user_data = $GLOBALS["UserSessionActivitiesHandler"]->getUserData();
	$logged_user_type_ids = $logged_user_data && isset($logged_user_data["user_type_ids"]) ? $logged_user_data["user_type_ids"] : null;
	$allowed = false;
	
	if (is_array($logged_user_type_ids))
		foreach ($logged_user_type_ids as $ut_id)
			if (in_array($ut_id, $allowed_list_and_edit_users_types)) {
				$allowed = true;
				break;
			}
	
	if (!$allowed) 
		$' . implode(' = $', $vars) . ' = null;
}';
		
		$long_code .= '
?>';
		
		if (file_exists($file_path)) {
			$contents = trim(file_get_contents($file_path));
			
			//replace previous code
			$contents = preg_replace('/\$allowed_list_and_edit_users_types(\s*)=(\s*)([^;]*)(\s*);/i', '', $contents);
			$contents = preg_replace('/if\s*\(\s*!\s*\$GLOBALS\s*\[\s*("|\')UserSessionActivitiesHandler("|\')\s*\]\s*\)\s*\{\s*@?(include_once|include)\s*\$EVC\s*\->\s*getUtilPath\s*\(\s*("|\')user_session_activities_handler("|\')\s*,\s*\$EVC\s*\->\s*getCommonProjectName\s*\(\s*\)\s*\)\s*;\s*@?initUserSessionActivitiesHandler\s*\(\s*\$EVC\s*\)\s*;\s*\}/i', '', $contents);
			$contents = preg_replace('/if\s*\(\s*empty\(\s*\$GLOBALS\s*\[\s*("|\')UserSessionActivitiesHandler("|\')\s*\]\s*\)\s*\)\s*\{\s*@?(include_once|include)\s*\$EVC\s*\->\s*getUtilPath\s*\(\s*("|\')user_session_activities_handler("|\')\s*,\s*\$EVC\s*\->\s*getCommonProjectName\s*\(\s*\)\s*\)\s*;\s*if\s*\(\s*function_exists\s*\(\s*("|\')initUserSessionActivitiesHandler("|\')\s*\)\s*\)\s*@?initUserSessionActivitiesHandler\s*\(\s*\$EVC\s*\)\s*;\s*\}/i', '', $contents);
			
			$contents = preg_replace('/if\s*\(\s*\$GLOBALS\[\s*("|\')UserSessionActivitiesHandler("|\')\s*\]\s*&&\s*\$allowed_list_and_edit_users_types\s*\)\s*\n*\s*\{\s*\n*\s*\$logged_user_data\s*=\s*\$GLOBALS\[\s*("|\')UserSessionActivitiesHandler("|\')\s*\]\-\>getUserData\(\)\s*;\s*\n*\s*\$logged_user_type_ids\s*=\s*\$logged_user_data(\s*&&\s*isset\s*\(\s*\$logged_user_data\[\s*("|\')user_type_ids("|\')\s*\]\s*\))?\s*\?\s*\$logged_user_data\s*\[\s*("|\')user_type_ids("|\')\s*\]\s*\:\s*null\s*;\s*\n*\s*\$allowed\s*=\s*false\s*;\s*\n*if\s*\(\s*is_array\s*\(\s*\$logged_user_type_ids\s*\)\s*\)\s*\n*\s*foreach\s*\(\s*\$logged_user_type_ids\s*as\s*\$ut_id\s*\)\s*\n*\s*if\s*\(\s*in_array\s*\(\s*\$ut_id\s*,\s*\$allowed_list_and_edit_users_types\s*\)\s*\)\s*\{\s*\n*\$allowed\s*=\s*true\s*;\s*\n*\s*break\s*;\s*\n*\s*\}\s*\n*\s*if\s*\(\s*\!\s*\$allowed\s*\)\s*\n*\s*\}\s*\n*/i', '', $contents); //VERY CAREFULL WITH THIS REGEX!!!
			$contents = preg_replace('/\$[\w\$=\s]+\s*=\s*null\s*;\s*/iu', '', $contents); //'/u' means with accents and ç too. '/u' converts unicode to accents chars.
			
			$contents .= $long_code;
			$contents = str_replace(array("?><?php", "?><?", "\r"), "", $contents);
			$contents = preg_replace("/\?>\s*<\?php/", "", $contents);
			$contents = preg_replace("/\?>\s*<\?/", "", $contents);
			$contents = preg_replace("/\n+/", "\n", preg_replace("/\s*\n\s*/", "\n", $contents)); //remove double end lines
		}
		else {
			$contents = $long_code;
			
			if (!is_dir($dir_path))
				mkdir($dir_path, 0755, true);
		}
		
		//echo $contents;
		return is_dir($dir_path) && file_put_contents($file_path, $contents) !== false;
	}
	
	public static function addVarsFile($PEVC, $vars_file_id, $vars) {
		$code = '';
		
		if ($vars) 
			foreach ($vars as $var_name => $var_value)
				$code .= '$' . $var_name . ' = "' . addcslashes($var_value, '"') . '";' . "\n";
		
		$code = '<?php
' . trim($code) . '
?>';
		
		$file_path = $PEVC->getConfigPath($vars_file_id);
		$dir_path = dirname($file_path);
		
		if (file_exists($file_path)) {
			$contents = trim(file_get_contents($file_path));
			
			if ($vars) 
				foreach ($vars as $var_name => $var_value) {
					do {
						preg_match('/(\$' . $var_name . ')(\s*)=(\s*)/iu', $contents, $matches, PREG_OFFSET_CAPTURE); //'/u' means with accents and ç too. '/u' converts unicode to accents chars.
						
						if (!empty($matches[0])) {
							$m = $matches[0];
							$name_equal = $m[0];
							$offset = $m[1];
							
							$odq = $osq = false;
							$l = strlen($contents);
							$start_offset = $offset + strlen($name_equal);
							$end_offset = $l;
							
							for ($j = $start_offset; $j < $l; $j++) {
								$char = $contents[$j];
								
								if ($char == '"' && !$osq && !TextSanitizer::isCharEscaped($contents, $j))
									$odq = !$odq;
								else if ($char == "'" && !$odq && !TextSanitizer::isCharEscaped($contents, $j))
									$osq = !$osq;
								else if ($char == ";" && !$osq && !$odq) {
									$end_offset = $j + 1;
									
									if (substr($contents, $end_offset, 1) == "\n")
										$end_offset++;
									
									break;
								}
							}
							
							$to_search = substr($contents, $offset, $end_offset - $offset);
							$contents = str_replace($to_search, "", $contents);
						}
					}
					while ($matches && !empty($matches[0]));
				}
			
			$contents .= $code;
			$contents = str_replace(array("?><?php", "?><?", "\r"), "", $contents);
			$contents = preg_replace("/\?>\s*<\?php/", "", $contents);
			$contents = preg_replace("/\?>\s*<\?/", "", $contents);
			$contents = preg_replace("/\n+/", "\n", preg_replace("/\s*\n\s*/", "\n", $contents)); //remove double end lines
		}
		else {
			$contents = $code;
			
			if (!is_dir($dir_path))
				mkdir($dir_path, 0755, true);
		}
		
		//echo $contents;
		return is_dir($dir_path) && file_put_contents($file_path, $contents) !== false;
	}
	
	public static function createPageFile($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $webroot_cache_folder_path, $webroot_cache_folder_url, $bean_name, $bean_file_name, $path, $relative_path, $overwrite, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $authentication_list_and_edit_users, $authenticated_template, $non_authenticated_template, $template, &$table_statuses, &$js_funcs, &$js_code, $tasks, $task, $parent_task = false, $file_name_folder = false, &$permissions = false, &$settings_php_codes_list = false) {
		$file_name = self::getTaskLabelFileName($task);
		$task_tag = isset($task["tag"]) ? $task["tag"] : null;
		$files_to_create = isset($task["properties"]["files_to_create"]) ? $task["properties"]["files_to_create"] : null;
		$page_settings = isset($task["properties"]["page_settings"]) ? $task["properties"]["page_settings"] : null;
		$authentication = null;
		$authentication_files_relative_folder_path = trim($authentication_files_relative_folder_path);
		
		if ($authentication_files_relative_folder_path && substr($authentication_files_relative_folder_path, -1) != "/")
			$authentication_files_relative_folder_path .= "/";
		
		$authentication = !empty($task["properties"]["authentication_type"]) ? array(
			"authentication_type" => isset($task["properties"]["authentication_type"]) ? $task["properties"]["authentication_type"] : null,
			"authentication_users" => isset($task["properties"]["authentication_users"]) ? $task["properties"]["authentication_users"] : null,
		) : null;
		
		if ($task_tag == "page") {
			$form_settings = self::getPageTaskUIFormSettings($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $webroot_cache_folder_path, $webroot_cache_folder_url, $bean_name, $bean_file_name, $path, $relative_path, $overwrite, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $authentication_list_and_edit_users, $authenticated_template, $non_authenticated_template, $template, $table_statuses, $js_funcs, $js_code, $tasks, $task, $permissions, $settings_php_codes_list);
		}
		else
			$form_settings = self::getPanelTaskUIFormSettings($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $webroot_cache_folder_path, $webroot_cache_folder_url, $bean_name, $bean_file_name, $path, $relative_path, $overwrite, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $authentication_list_and_edit_users, $authenticated_template, $non_authenticated_template, $template, $table_statuses, $js_funcs, $js_code, $tasks, $task, $parent_task, $file_name_folder . $file_name . "/", false, $authentication, $permissions, $settings_php_codes_list);
		
		if (!$form_settings)
			return false;
		
		//prepare js functions, adding the generic javascript code, but only if page is main file, bc if not main page it means that the page will be included in another main page
		if ($parent_task) {
			$js_code .= ($js_code && !empty($form_settings["js"]) ? "\n" : "") . (isset($form_settings["js"]) ? $form_settings["js"] : "");
			$form_settings["js"] = "";
		}
		else {
			$js = self::getGenericJSFunctions($js_funcs);
			if ($js)
				$form_settings["js"] = $js . "\n" . $js_code . "\n" . (isset($form_settings["js"]) ? $form_settings["js"] : "");
		}
		
		//save to file
		$status = self::createFile($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $relative_path . $file_name_folder . $file_name, $overwrite, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $authentication_list_and_edit_users, $authenticated_template, $non_authenticated_template, $template, $table_statuses, $form_settings, isset($task["id"]) ? $task["id"] : null, $files_to_create, "local", $authentication, $permissions, $page_settings, $settings_php_codes_list);
		//print_r($table_statuses[$task["id"]]);die();
		
		return $status;
	}

	private static function getPageTaskUIFormSettings($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $webroot_cache_folder_path, $webroot_cache_folder_url, $bean_name, $bean_file_name, $path, $relative_path, $overwrite, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $authentication_list_and_edit_users, $authenticated_template, $non_authenticated_template, $template, &$table_statuses, &$js_funcs, &$js_code, $tasks, $task, &$permissions, &$settings_php_codes_list) {
		$file_name = self::getTaskLabelFileName($task);
		$properties = isset($task["properties"]) ? $task["properties"] : null;
		$join_type = isset($properties["join_type"]) ? $properties["join_type"] : null; //join tabs or list
		$links = isset($properties["links"]) ? $properties["links"] : null;
		$pre_form_settings = isset($properties["pre_form_settings"]) ? $properties["pre_form_settings"] : null;
		$pos_form_settings = isset($properties["pos_form_settings"]) ? $properties["pos_form_settings"] : null;
		$task_connections = isset($task["exits"]["default_exit"]) ? $task["exits"]["default_exit"] : null;
		$task_connections = !empty($task_connections[0]) ? $task_connections : array($task_connections);
		$inner_tasks = isset($task["tasks"]) ? $task["tasks"] : null;
		$authentication = !empty($task["properties"]["authentication_type"]) ? array(
			"authentication_type" => isset($task["properties"]["authentication_type"]) ? $task["properties"]["authentication_type"] : null,
			"authentication_users" => isset($task["properties"]["authentication_users"]) ? $task["properties"]["authentication_users"] : null,
		) : null;
		
		$links = self::prepareLinks($links);
		$is_join_tabs = $join_type == "tabs";
		
		$form_settings = array(
			"actions" => array(),
			"css" => "",
			"js" => "",
		);
		
		//prepare pre form_settings
		if ($pre_form_settings) {
			if (!empty($pre_form_settings["actions"])) {
				$pre_form_settings["actions"] = isset($pre_form_settings["actions"]["action_type"]) || isset($pre_form_settings["actions"]["action_value"]) ? $pre_form_settings["actions"] : array($pre_form_settings["actions"]);
				
				$form_settings["actions"] = $pre_form_settings["actions"];
			}
			
			if (!empty($pre_form_settings["css"]))
				$form_settings["css"] = $pre_form_settings["css"];
			
			if (!empty($pre_form_settings["js"]))
				$form_settings["js"] = $pre_form_settings["js"];
		}
		
		//prepare inner tasks
		if ($inner_tasks) {
			$output_var_name = $is_join_tabs ? "tabs_tasks_html" : false;
			
			foreach ($inner_tasks as $inner_task_id => $inner_task) {
				$ifs = self::getPanelTaskUIFormSettings($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $webroot_cache_folder_path, $webroot_cache_folder_url, $bean_name, $bean_file_name, $path, $relative_path, $overwrite, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $authentication_list_and_edit_users, $authenticated_template, $non_authenticated_template, $template, $table_statuses, $js_funcs, $js_code, $tasks, $inner_task, $task, $file_name . "/", $output_var_name, $authentication, $permissions, $settings_php_codes_list);
				
				if (!$ifs)
					return false;
				
				$form_settings["actions"] = array_merge($form_settings["actions"], $ifs["actions"]);
				$form_settings["css"] .= !empty($ifs["css"]) ? "\n\n" . $ifs["css"] : "";
				$form_settings["js"] .= !empty($ifs["js"]) ? "\n\n" . $ifs["js"] : "";
			}
			
			//prepare join tabs
			if ($is_join_tabs) {
				$html = '<div class="tabs">
	<ul>';
				$tabs_content_html = '';
				
				$i = 0;
				foreach ($inner_tasks as $inner_task_id => $inner_task) {
					$label = isset($inner_task["label"]) ? ucwords(strtolower(str_replace("_", " ", $inner_task["label"]))) : "";
					
					$html .= '
		<li><a href="#tab_content_' . $inner_task_id . '">' . $label . '</a></li>';
					
					$tabs_content_html .= '
	<div id="tab_content_' . $inner_task_id . '" class="tab_content">
		<ptl:echo @\$' . $output_var_name . '[' . $i . ']/>
	</div>';
					
					$i++;
				}
				
				$html .= '
	</ul>
	' . $tabs_content_html . '
</div>';
				
				$form_settings["actions"][] = array(
					"result_var_name" => "",
					"action_type" => "html",
					"condition_type" => "execute_always",
					"condition_value" => "",
					"action_value" => array(
						"ptl" => array(
							"code" => $html
						)
					)
				);
			}
		}
		
		//prepare links
		$html_links = '';
		
		if ($links)
			foreach ($links as $link) {
				$html_links .= '
		<li class="link link-free ' . (isset($link["class"]) ? $link["class"] : "") . '">
			' . (isset($link["previous_html"]) ? $link["previous_html"] : "") . '
			<a href="' . (isset($link["url"]) ? $link["url"] : "") . '" title="' . (isset($link["title"]) ? $link["title"] : "") . '"' . (!empty($link["target"]) ? 'target="' . $link["target"] . '"' : '') . '>' . (isset($link["value"]) ? $link["value"] : "") . '</a>
			' . (isset($link["next_html"]) ? $link["next_html"] : "") . '
		</li>';
			}
		
		//prepare connections
		if ($task_connections)
			for ($i = 0; $i < count($task_connections); $i++) {
				$task_connection = $task_connections[$i];
				$connection_label = isset($task_connection["label"]) ? $task_connection["label"] : null;
				$connection_type = isset($task_connection["properties"]["connection_type"]) ? $task_connection["properties"]["connection_type"] : null;
				$connection_title = isset($task_connection["properties"]["connection_title"]) ? $task_connection["properties"]["connection_title"] : null;
				$connection_class = isset($task_connection["properties"]["connection_class"]) ? $task_connection["properties"]["connection_class"] : null;
				$connection_target = isset($task_connection["properties"]["connection_target"]) ? $task_connection["properties"]["connection_target"] : null;
				$target_task_id = isset($task_connection["task_id"]) ? $task_connection["task_id"] : null;
				$target_task = self::getTaskByTaskId($tasks, $target_task_id);
				$target_task_tag = isset($target_task["tag"]) ? $target_task["tag"] : null;
				
				if ($target_task && $target_task_tag != "page")
					$target_task = self::getTaskPageParentTask($tasks, $target_task); //get page parent task first
				
				if ($target_task) {
					$target_task_url = '{$project_url_prefix}' . $relative_path . self::getTaskLabelFileName($target_task);
					$target_task_label = isset($target_task["label"]) ? ucwords(strtolower(str_replace("_", " ", $target_task["label"]))) : "";
					
					$connection_label = $connection_label ? $connection_label : $target_task_label;
					$connection_title = $connection_title ? $connection_title : $connection_label;
					
					if ($connection_type == "popup") { //get link and create popup with an iframe with this link
						$js_funcs["iframe_popup"] = true;
						
						$html_links .= '
		<li class="link link-page-connection ' . $connection_class . '">
			<a href="javascript:void(0)" onClick="return openIframePopup(this, \'' . $target_task_url . '\')" title="' . $connection_title . '">' . $connection_label . '</a>
		</li>';
					}
					else if ($connection_type == "parent") { //get link and change parent location with this link
						$js_funcs["parent"] = true;
						
						$html_links .= '
		<li class="link link-page-connection ' . $connection_class . '">
			<a href="javascript:void(0)" onClick="return openParentLocation(this, \'' . $target_task_url . '\')" title="' . $connection_title . '">' . $connection_label . '</a>
		</li>';
					}
					else 
						$html_links .= '
		<li class="link link-page-connection ' . $connection_class . '">
			<a href="' . $target_task_url . '" title="' . $connection_title . '"' . ($connection_target ? 'target="' . $connection_target . '"' : '') . '>' . $connection_label . '</a>
		</li>';
				}
			}
		
		if ($html_links) 
			$form_settings["actions"][] = array(
				"result_var_name" => "",
				"action_type" => "html",
				"condition_type" => "execute_always",
				"condition_value" => "",
				"action_value" => array(
					"ptl" => array(
						"code" => '<ul class="links">' . $html_links . "\n</ul>"
					)
				)
			);
		
		//prepare pos form_settings
		if ($pos_form_settings) {
			if (!empty($pos_form_settings["actions"])) {
				$pos_form_settings["actions"] = isset($pos_form_settings["actions"]["action_type"]) || isset($pos_form_settings["actions"]["action_value"]) ? $pos_form_settings["actions"] : array($pos_form_settings["actions"]);
				
				$form_settings["actions"] = array_merge($form_settings["actions"], $pos_form_settings["actions"]);
			}
			
			if (!empty($pos_form_settings["css"]))
				$form_settings["css"] .= "\n\n" . $pos_form_settings["css"];
			
			if (!empty($pos_form_settings["js"]))
				$form_settings["js"] .= "\n\n" . $pos_form_settings["js"];
		}
		
		return $form_settings;
	}
	
	private static function getPanelTaskUIFormSettings($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $webroot_cache_folder_path, $webroot_cache_folder_url, $bean_name, $bean_file_name, $path, $relative_path, $overwrite, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $authentication_list_and_edit_users, $authenticated_template, $non_authenticated_template, $template, &$table_statuses, &$js_funcs, &$js_code, $tasks, $task, $parent_task, $file_name_folder, $output_var_name = false, $authentication = null, &$permissions = false, &$settings_php_codes_list = false) {
		$task_id = isset($task["id"]) ? $task["id"] : null;
		$task_tag = isset($task["tag"]) ? $task["tag"] : null;
		$properties = isset($task["properties"]) ? $task["properties"] : null;
		$choose_db_table = isset($properties["choose_db_table"]) ? $properties["choose_db_table"] : null;
		$db_table = isset($choose_db_table["db_table"]) ? $choose_db_table["db_table"] : null;
		$db_driver = isset($choose_db_table["db_driver"]) ? $choose_db_table["db_driver"] : null;
		$db_type = isset($choose_db_table["db_type"]) ? $choose_db_table["db_type"] : null;
		$include_db_driver = isset($choose_db_table["include_db_driver"]) ? $choose_db_table["include_db_driver"] : null;
		$attributes = isset($properties["attributes"]) ? $properties["attributes"] : null;
		$actions = isset($properties["action"]) ? $properties["action"] : null;
		$links = isset($properties["links"]) ? $properties["links"] : null;
		$pre_form_settings = isset($properties["pre_form_settings"]) ? $properties["pre_form_settings"] : null;
		$pos_form_settings = isset($properties["pos_form_settings"]) ? $properties["pos_form_settings"] : null;
		$brokers_services_and_rules = isset($properties["brokers_services_and_rules"]) ? $properties["brokers_services_and_rules"] : null;
		$pagination = isset($properties["pagination"]) ? $properties["pagination"] : null;
		$files_to_create = isset($properties["files_to_create"]) ? $properties["files_to_create"] : null;
		$inner_tasks = isset($task["tasks"]) ? $task["tasks"] : null;
		$task_connections = isset($task["exits"]["default_exit"]) ? $task["exits"]["default_exit"] : null;
		$task_connections = !empty($task_connections[0]) ? $task_connections : array($task_connections);
		$users_perms = isset($properties["users_perms"]) ? $properties["users_perms"] : null;
		
		//prepare permissions
		$permissions = array();
		if (is_array($users_perms)) {
			if (array_key_exists("user_type_id", $users_perms) || array_key_exists("activity_id", $users_perms))
				$users_perms = array($users_perms);
			
			foreach ($users_perms as $idx => $user_perm)
				if (isset($user_perm["user_type_id"]) && is_numeric($user_perm["user_type_id"]) && isset($user_perm["activity_id"]) && is_numeric($user_perm["activity_id"]))
					$permissions[] = $user_perm;
		}
		
		$links = self::prepareLinks($links);
		$tables_alias = array(
			$db_table => isset($choose_db_table["db_table_alias"]) ? $choose_db_table["db_table_alias"] : null
		);
		
		$is_parent_task_panel = $parent_task && isset($parent_task["tag"]) && ($parent_task["tag"] == "listing" || $parent_task["tag"] == "form" || $parent_task["tag"] == "view");
		$is_parent_task_same_table = self::isSameTable($task, $parent_task);
		
		if ($task_tag == "listing") {
			$listing_type = isset($properties["listing_type"]) ? $properties["listing_type"] : null;
			$panel_type = $listing_type == "tree" ? "list_form" : ($listing_type == "multi_form" ? "multiple_form" : "list_table");
		}
		else //if ($task_tag == "form" || $task_tag == "view")
			$panel_type = $is_parent_task_panel && !$is_parent_task_same_table ? "multiple_form" : "single_form";
		
		$file_name_folder .= self::getTaskLabelFileName($task) . "/";
		
		//prepare pagination
		if (($panel_type == "list_table" || $panel_type == "list_form") && $pagination && !empty($pagination["active"])) {
			if ($is_parent_task_panel) {
				$pagination["on_click_js_func"] = "loadEmbededPageWithNewNavigation";
				$js_funcs["ajax_navigation"] = true;
			}
			else if ($output_var_name) { //it means this table will appear inside of a tab, so the pagination should be via ajax
				$pagination["on_click_js_func"] = "loadTabPageWithNewNavigation";
				$js_funcs["ajax_tab_navigation"] = true;
			}
			//else doesn't do anything. Leave pagination as it is to be added to the $settings.
		}
		else if ($panel_type == "multiple_form") { //multiple form. Pagination always present by default
			if ($is_parent_task_panel) {
				$pagination = array("on_click_js_func" => "loadEmbededPageWithNewNavigation");
				$js_funcs["ajax_navigation"] = true;
			}
			else if ($output_var_name) { //it means this table will appear inside of a tab, so the pagination should be via ajax
				$pagination["on_click_js_func"] = "loadTabPageWithNewNavigation";
				$js_funcs["ajax_tab_navigation"] = true;
			}
		}
		else
			$pagination = array("active" => false);
		
		//prepare attributes
		$attributes_by_name = array();
		
		if ($attributes) //$attributes may be an array with numeric keys
			foreach ($attributes as $k => $attribute) {
				$attribute_name = !empty($attribute["name"]) ? $attribute["name"] : $k;
				$attributes_by_name[$attribute_name] = $attribute;
			}
		
		$attributes = $attributes_by_name;
		
		//prepare settings
		$settings[$db_table] = array(
			"panel_type" => $panel_type,
			"panel_id" => $task_id,
			"panel_class" => "task-panel " . $task_id . (!empty($properties["interface_class"]) ? " " . $properties["interface_class"] : ""),
			"panel_previous_html" => isset($properties["interface_previous_html"]) ? $properties["interface_previous_html"] : null,
			"panel_next_html" => isset($properties["interface_next_html"]) ? $properties["interface_next_html"] : null,
			"form_type" => "settings", //"settings" or "ptl"
			"attributes" => array_keys($attributes),
			"actions" => array(
				"links" => $links,
				"attributes_settings" => $attributes,
			),
			"pagination" => $pagination,
			"conditions" => array(),
		);
		
		//prepare conditions
		if (!empty($choose_db_table["db_table_conditions"])) {
			if (isset($choose_db_table["db_table_conditions"]["attribute"]) || isset($choose_db_table["db_table_conditions"]["value"]))
				$choose_db_table["db_table_conditions"] = array($choose_db_table["db_table_conditions"]);
		
			foreach ($choose_db_table["db_table_conditions"] as $idx => $ca)
				$settings[$db_table]["conditions"][] = array(
					"column" => $ca["attribute"],
					"table" => $choose_db_table["db_table_parent"] ? $choose_db_table["db_table_parent"] : $db_table, //no need for this part bc the $ca["attribute"] already contains the correspondent table name. This is just in case the $ca["attribute"] don't have a table name.
					"value" => $ca["value"],
				);
		}
		
		//prepare table parent
		if (!empty($choose_db_table["db_table_parent"]))
			$settings[$db_table]["table_parent"] = $choose_db_table["db_table_parent"]; //no need of the db_table_parent_alias. The db_table_parent is only needed to find the brokers services and rules automatically
		
		//check if service or rule is valid and if not unset this item.
		if ($brokers_services_and_rules)
			foreach ($brokers_services_and_rules as $bsr_type => $bsr) {
				$invalid = false;
				$brokers_layer_type = isset($bsr["brokers_layer_type"]) ? $bsr["brokers_layer_type"] : null;
				
				switch ($brokers_layer_type) {
					case "callbusinesslogic":
					case "callibatisquery":
					case "callhibernatemethod":
						$invalid = empty($bsr["service_id"]);
						break;
					case "getquerydata":
					case "setquerydata":
						$invalid = empty($bsr["sql"]);
						break;
				}
				
				if ($invalid)
					unset($brokers_services_and_rules[$bsr_type]);
			}
		
		$settings[$db_table]["brokers"] = $brokers_services_and_rules;
		
		//for the single_form panel, force get action. The other don't need bc if the update or delete exists, the get will be initialized anyway!
		$only_insert = (!empty($brokers_services_and_rules["insert"]) || !empty($settings[$db_table]["actions"]["insert"])) 
					&& empty($brokers_services_and_rules["update"]) 
					&& empty($brokers_services_and_rules["delete"]) 
					&& empty($settings[$db_table]["actions"]["update"]) 
					&& empty($settings[$db_table]["actions"]["delete"]); //This covers the case where we simply want a form to add a new object without any get. Which means that the get will only be created, if there is an update, delete or view action, this is, if there is not an unique insert action...
		if ($panel_type == "single_form" && empty($brokers_services_and_rules["get"]) && !$only_insert)
			$settings[$db_table]["actions"]["get"] = array("action" => "get");
		
		//if is_parent_task_panel and brokers_services_and_rules[get_all/count] does not exist, create correspondent conditions so the CMSPresentationFormSettingsUIHandler::getFormSettings can create the right sql query based in the db table fks. Do the samething if the task is a relationship page with a parent table.
		//Note that the pks of the current table ($db_table) will be passed through the url query-string via $_GET.
		if ($panel_type != "single_form" && empty($brokers_services_and_rules["get_all"])) {
			if ($is_parent_task_panel) {
				$parent_task_choose_db_table_db_table = isset($parent_task["properties"]["choose_db_table"]["db_table"]) ? $parent_task["properties"]["choose_db_table"]["db_table"] : null;
				
				//only if parent table is different than current table, we set the $settings[$db_table]["table_parent"] so the CMSPresentationFormSettingsUIHandler can then create the proper sql with the correspondent inner join between tables.
				if ($parent_task_choose_db_table_db_table != $db_table) 
					$settings[$db_table]["table_parent"] = $parent_task_choose_db_table_db_table;
				
				$conditions = self::getGetParentChildTableConditions($user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $bean_name, $bean_file_name, $db_driver, $db_type, $parent_task_choose_db_table_db_table, $db_table);
				$settings[$db_table]["conditions"] = array_merge($settings[$db_table]["conditions"], $conditions);
			}
			else if (!empty($brokers_services_and_rules["parents_get_all"]) && !empty($brokers_services_and_rules["parents_count"])) { //in case of relationships panels
				$settings[$db_table]["brokers"]["get_all"] = $brokers_services_and_rules["parents_get_all"];
				$settings[$db_table]["brokers"]["count"] = $brokers_services_and_rules["parents_count"];
				//print_r($settings);die();
			}
			else if (!empty($choose_db_table["db_table_parent"])) {
				$conditions = self::getGetParentChildTableConditions($user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $bean_name, $bean_file_name, $db_driver, $db_type, $choose_db_table["db_table_parent"], $db_table);
				$settings[$db_table]["conditions"] = array_merge($settings[$db_table]["conditions"], $conditions);
				//print_r($settings);die();
			}
		}
		
		//prepare single/multiple insert/update/delete action if exist
		self::prepareActionSettings($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $webroot_cache_folder_path, $webroot_cache_folder_url, $bean_name, $bean_file_name, $path, $relative_path, $overwrite, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $authentication_list_and_edit_users, $authenticated_template, $non_authenticated_template, $table_statuses, $file_name_folder, $settings, $task_id, $files_to_create, $choose_db_table, $tables_alias, $db_table, $brokers_services_and_rules, $actions, "single_", "insert", $is_parent_task_panel, $panel_type, $output_var_name, $authentication, $permissions);
		
		self::prepareActionSettings($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $webroot_cache_folder_path, $webroot_cache_folder_url, $bean_name, $bean_file_name, $path, $relative_path, $overwrite, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $authentication_list_and_edit_users, $authenticated_template, $non_authenticated_template, $table_statuses, $file_name_folder, $settings, $task_id, $files_to_create, $choose_db_table, $tables_alias, $db_table, $brokers_services_and_rules, $actions, "single_", "update", $is_parent_task_panel, $panel_type, $output_var_name, $authentication, $permissions);
		
		self::prepareActionSettings($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $webroot_cache_folder_path, $webroot_cache_folder_url, $bean_name, $bean_file_name, $path, $relative_path, $overwrite, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $authentication_list_and_edit_users, $authenticated_template, $non_authenticated_template, $table_statuses, $file_name_folder, $settings, $task_id, $files_to_create, $choose_db_table, $tables_alias, $db_table, $brokers_services_and_rules, $actions, "single_", "delete", $is_parent_task_panel, $panel_type, $output_var_name, $authentication, $permissions);
		
		self::prepareActionSettings($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $webroot_cache_folder_path, $webroot_cache_folder_url, $bean_name, $bean_file_name, $path, $relative_path, $overwrite, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $authentication_list_and_edit_users, $authenticated_template, $non_authenticated_template, $table_statuses, $file_name_folder, $settings, $task_id, $files_to_create, $choose_db_table, $tables_alias, $db_table, $brokers_services_and_rules, $actions, "multiple_", "insert", $is_parent_task_panel, $panel_type, $output_var_name, $authentication, $permissions);
		
		self::prepareActionSettings($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $webroot_cache_folder_path, $webroot_cache_folder_url, $bean_name, $bean_file_name, $path, $relative_path, $overwrite, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $authentication_list_and_edit_users, $authenticated_template, $non_authenticated_template, $table_statuses, $file_name_folder, $settings, $task_id, $files_to_create, $choose_db_table, $tables_alias, $db_table, $brokers_services_and_rules, $actions, "multiple_", "update", $is_parent_task_panel, $panel_type, $output_var_name, $authentication, $permissions);
		
		self::prepareActionSettings($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $webroot_cache_folder_path, $webroot_cache_folder_url, $bean_name, $bean_file_name, $path, $relative_path, $overwrite, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $authentication_list_and_edit_users, $authenticated_template, $non_authenticated_template, $table_statuses, $file_name_folder, $settings, $task_id, $files_to_create, $choose_db_table, $tables_alias, $db_table, $brokers_services_and_rules, $actions, "multiple_", "delete", $is_parent_task_panel, $panel_type, $output_var_name, $authentication, $permissions);
		
		if ($inner_tasks) {
			$inner_template = "ajax";
			
			foreach ($inner_tasks as $inner_task_id => $inner_task) {
				//prepare links to open inner task
				$inner_task_id = isset($inner_task["id"]) ? $inner_task["id"] : null; //same than panel_class
				$inner_task_label = isset($inner_task["label"]) ? $inner_task["label"] : null;
				$inner_task_properties = isset($inner_task["properties"]) ? $inner_task["properties"] : null;
				$parent_link_value = isset($inner_task_properties["parent_link_value"]) ? $inner_task_properties["parent_link_value"] : null;
				$parent_link_title = isset($inner_task_properties["parent_link_title"]) ? $inner_task_properties["parent_link_title"] : null;
				$interface_type = isset($inner_task_properties["interface_type"]) ? $inner_task_properties["interface_type"] : null;
				
				$inner_task_file_url = '{$project_url_prefix}' . $relative_path . $file_name_folder . self::getTaskLabelFileName($inner_task);
				$on_click = $interface_type == "popup" ? "return openPopup(this, '$inner_task_file_url')" : "return openEmbed(this, '$inner_task_file_url')";
				
				$js_funcs[$interface_type] = true;
				
				$settings[$db_table]["actions"]["links"][] = array(
					"url" => "javascript:void(0)",
					"value" => strlen($parent_link_value) ? $parent_link_value : $inner_task_label,
					"title" => strlen($parent_link_title) ? $parent_link_title : $inner_task_label,
					"class" => isset($inner_task_properties["parent_link_class"]) ? $inner_task_properties["parent_link_class"] : null,
					"extra_attributes" => array(
						array("name" => "onClick", "value" => $on_click)
					),
					"previous_html" => isset($inner_task_properties["parent_link_previous_html"]) ? $inner_task_properties["parent_link_previous_html"] : null,
					"next_html" => isset($inner_task_properties["parent_link_next_html"]) ? $inner_task_properties["parent_link_next_html"] : null,
				);
				
				if ($authentication) {
					$inner_task["properties"]["authentication_type"] = isset($authentication["authentication_type"]) ? $authentication["authentication_type"] : null;
					$inner_task["properties"]["authentication_users"] = isset($authentication["authentication_users"]) ? $authentication["authentication_users"] : null;
				}
				
				//create inner tasks files
				if (!self::createPageFile($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $webroot_cache_folder_path, $webroot_cache_folder_url, $bean_name, $bean_file_name, $path, $relative_path, $overwrite, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $authentication_list_and_edit_users, $authenticated_template, $non_authenticated_template, $inner_template, $table_statuses, $js_funcs, $js_code, $tasks, $inner_task, $task, $file_name_folder, $permissions, $settings_php_codes_list))
					return false;
			}
		}
		
		if ($task_connections) 
			for ($i = 0; $i < count($task_connections); $i++) {
				$task_connection = $task_connections[$i];
				$connection_label = isset($task_connection["label"]) ? $task_connection["label"] : null;
				$connection_type = isset($task_connection["properties"]["connection_type"]) ? $task_connection["properties"]["connection_type"] : null;
				$connection_title = isset($task_connection["properties"]["connection_title"]) ? $task_connection["properties"]["connection_title"] : null;
				$connection_class = isset($task_connection["properties"]["connection_class"]) ? $task_connection["properties"]["connection_class"] : null;
				$connection_target = isset($task_connection["properties"]["connection_target"]) ? $task_connection["properties"]["connection_target"] : null;
				$target_task_id = isset($task_connection["task_id"]) ? $task_connection["task_id"] : null;
				$target_task = self::getTaskByTaskId($tasks, $target_task_id);
				$target_task_tag = isset($target_task["tag"]) ? $target_task["tag"] : null;
				
				if ($target_task && $target_task_tag != "page")
					$target_task = self::getTaskPageParentTask($tasks, $target_task); //get page parent task first
				
				if ($target_task) {
					$target_task_url = '{$project_url_prefix}' . $relative_path . self::getTaskLabelFileName($target_task);
					$target_task_label = isset($target_task["label"]) ? ucwords(strtolower(str_replace("_", " ", $target_task["label"]))) : "";
					
					$connection_label = $connection_label ? $connection_label : $target_task_label;
					$connection_title = $connection_title ? $connection_title : $connection_label;
					
					if ($connection_type == "popup") { //get link and create popup with an iframe with this link
						$js_funcs["iframe_popup"] = true;
					
						$settings[$db_table]["actions"]["links"][] = array(
							"url" => "javascript:void(0)",
							"value" => $connection_label,
							"title" => $connection_title,
							"class" => $connection_class,
							"extra_attributes" => array(
								array("name" => "onClick", "value" => "return openIframePopup(this, '$target_task_url')")
							),
						);
					}
					else if ($connection_type == "parent") { //get link and change parent location with this link
						$js_funcs["parent"] = true;
						
						$settings[$db_table]["actions"]["links"][] = array(
							"url" => "javascript:void(0)",
							"value" => $connection_label,
							"title" => $connection_title,
							"class" => $connection_class,
							"extra_attributes" => array(
								array("name" => "onClick", "value" => "return openParentLocation(this, '$target_task_url')")
							),
						);
					}
					else
						$settings[$db_table]["actions"]["links"][] = array(
							"url" => $target_task_url,
							"value" => $connection_label,
							"title" => $connection_title,
							"class" => $connection_class,
							"target" => $connection_target,
						);
				}
			}
		
		$default_dal_broker = self::getDefaultDBDataAccessBroker($PEVC, $user_global_variables_file_path, $user_beans_folder_path, $db_driver);
		$default_db_broker = self::getDefaultDBBroker($PEVC, $user_global_variables_file_path, $user_beans_folder_path, $db_driver);
		$include_db_broker = self::getIncludeDBBroker($PEVC, $user_global_variables_file_path, $user_beans_folder_path, $include_db_driver);
		
		$form_settings = CMSPresentationFormSettingsUIHandler::getFormSettings($user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $webroot_cache_folder_path, $webroot_cache_folder_url, $bean_name, $bean_file_name, $path, null, $default_dal_broker, $default_db_broker, $include_db_broker, $db_driver, $include_db_driver, $db_type, $tables_alias, $settings, false, $js_code, $output_var_name, $permissions, $settings_php_codes_list);
		
		//prepare pre form_settings
		if ($pre_form_settings) {
			if (!empty($pre_form_settings["actions"])) {
				$pre_form_settings["actions"] = isset($pre_form_settings["actions"]["action_type"]) || isset($pre_form_settings["actions"]["action_value"]) ? $pre_form_settings["actions"] : array($pre_form_settings["actions"]);
				
				$form_settings["actions"] = array_merge($pre_form_settings["actions"], $form_settings["actions"]);
			}
			
			if (!empty($pre_form_settings["css"]))
				$form_settings["css"] = $pre_form_settings["css"] . (!empty($form_settings["css"]) ? "\n\n" . $form_settings["css"] : "");
			
			if (!empty($pre_form_settings["js"]))
				$form_settings["js"] = $pre_form_settings["js"] . (!empty($form_settings["js"]) ? "\n\n" . $form_settings["js"] : "");
		}
		
		//prepare pos form_settings
		if ($pos_form_settings) {
			if (!empty($pos_form_settings["actions"])) {
				$pos_form_settings["actions"] = isset($pos_form_settings["actions"]["action_type"]) || isset($pos_form_settings["actions"]["action_value"]) ? $pos_form_settings["actions"] : array($pos_form_settings["actions"]);
				
				$form_settings["actions"] = array_merge($form_settings["actions"], $pos_form_settings["actions"]);
			}
			
			if (!empty($pos_form_settings["css"]))
				$form_settings["css"] .= "\n\n" . $pos_form_settings["css"];
			
			if (!empty($pos_form_settings["js"]))
				$form_settings["js"] .= "\n\n" . $pos_form_settings["js"];
		}
		
		//put all actions in a group so we can have the inner tasks and the main tasks in groups, otherwise the code will be confused
		$form_settings["actions"] = array(
			array(
				"result_var_name" => "",
				"action_type" => "group",
				"condition_type" => "execute_always",
				"condition_value" => "",
				"action_value" => array(
					"group_name" => "",
					"actions" => isset($form_settings["actions"]) ? $form_settings["actions"] : null,
				),
			)
		);
		
		return $form_settings;
	}

	private static function prepareActionSettings($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $webroot_cache_folder_path, $webroot_cache_folder_url, $bean_name, $bean_file_name, $path, $relative_path, $overwrite, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $authentication_list_and_edit_users, $authenticated_template, $non_authenticated_template, &$table_statuses, $file_name_folder, &$settings, $task_id, $files_to_create, $choose_db_table, $tables_alias, $db_table, $brokers_services_and_rules, $actions, $action_prefix, $action_type, $is_parent_task_panel, $panel_type, $output_var_name, $authentication, $permissions) {
		$action_name = $action_prefix . $action_type;
		
		if (!empty($actions[$action_name])) {
			$action_ajax_prefix_url = '{$project_url_prefix}' . $relative_path . substr($file_name_folder, 0, -1) . "_";
			$is_panel_list = $panel_type == "list_table" || $panel_type == "list_form";
			$is_multiple = $action_prefix == "multiple_";
			$confirmation_message = isset($actions[$action_name . "_confirmation_message"]) ? $actions[$action_name . "_confirmation_message"] : null;
			$ok_msg_message = isset($actions[$action_name . "_ok_msg_message"]) ? $actions[$action_name . "_ok_msg_message"] : null;
			$ok_msg_redirect_url = isset($actions[$action_name . "_ok_msg_redirect_url"]) ? $actions[$action_name . "_ok_msg_redirect_url"] : null;
			$error_msg_message = isset($actions[$action_name . "_error_msg_message"]) ? $actions[$action_name . "_error_msg_message"] : null;
			$error_msg_redirect_url = isset($actions[$action_name . "_error_msg_redirect_url"]) ? $actions[$action_name . "_error_msg_redirect_url"] : null;
			
			if ($is_multiple && ($action_type == "insert" || $action_type == "update") && !empty($actions["multiple_insert"]) && !empty($actions["multiple_update"]))
				$action_name = "multiple_insert_update";
			
			if ($is_parent_task_panel || $output_var_name || ($is_panel_list && $action_prefix == "single_")) {
				$settings[$db_table]["actions"][$action_name] = array(
					"action" => $action_name,
					"action_type" => "ajax_on_click",
					"ajax_url" => $action_ajax_prefix_url . $action_name,
					"confirmation_message" => $confirmation_message, //only applies if action is delete
					"ok_msg_message" => $ok_msg_message,
					"ok_msg_redirect_url" => $ok_msg_redirect_url,
					"error_msg_message" => $error_msg_message,
					"error_msg_redirect_url" => $error_msg_redirect_url,
				);
				
				//create ajax insert action file: $action_ajax_prefix_url . "delete"
				self::createAjaxActionFile($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $webroot_cache_folder_path, $webroot_cache_folder_url, $bean_name, $bean_file_name, $path, $relative_path, $overwrite, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $authentication_list_and_edit_users, $authenticated_template, $non_authenticated_template, $table_statuses, $file_name_folder, $settings, $task_id, $files_to_create, $choose_db_table, $tables_alias, $db_table, $brokers_services_and_rules, $actions, $action_prefix, $action_type, $action_name, $is_multiple, $authentication, $permissions);
			}
			else if ($panel_type == "multiple_form" && $action_name == "single_insert")
				$settings[$db_table]["actions"][$action_name] = array(
					"action" => $action_name,
					"action_type" => $is_parent_task_panel || $output_var_name ? "ajax_on_click" : "", //2020-01-22: we add the ajax action only if is an inner content. If it is the main content we think the default actio is more user-friendly, but we can simply add the ajax_on_click for all cases. It simply depends the user opinion... I prefer as it is!
					"ajax_url" => $is_parent_task_panel || $output_var_name ? $action_ajax_prefix_url . $action_name : null,
					"ok_msg_message" => $ok_msg_message,
					"ok_msg_redirect_url" => $ok_msg_redirect_url,
					"error_msg_message" => $error_msg_message,
					"error_msg_redirect_url" => $error_msg_redirect_url,
				);
			else //if (!$brokers_services_and_rules[$action_type] || ($is_panel_list && $is_multiple) || !isset($settings[$db_table]["actions"][$action_name]))
				$settings[$db_table]["actions"][$action_name] = array(
					"action" => $action_name,
					"action_type" => "", //"" == default inline action, this is, non ajax.
					"confirmation_message" => $confirmation_message, //only applies if action is delete
					"ok_msg_message" => $ok_msg_message,
					"ok_msg_redirect_url" => $ok_msg_redirect_url,
					"error_msg_message" => $error_msg_message,
					"error_msg_redirect_url" => $error_msg_redirect_url,
				);
		}
	}

	private static function createAjaxActionFile($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $webroot_cache_folder_path, $webroot_cache_folder_url, $bean_name, $bean_file_name, $path, $relative_path, $overwrite, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $authentication_list_and_edit_users, $authenticated_template, $non_authenticated_template, &$table_statuses, $file_name_folder, $settings, $task_id, $files_to_create, $choose_db_table, $tables_alias, $db_table, $brokers_services_and_rules, $actions, $action_prefix, $action_type, $action_name, $is_multiple, $authentication, $permissions) {
		//create ajax action file: $action_ajax_prefix_url . $action_type
		$settings_aux = array(
			$db_table => array(
				"form_type" => isset($settings["form_type"]) ? $settings["form_type"] : null,
				"brokers" => array(),
				"actions" => array(),
			)
		);
		
		if ($action_name == "multiple_insert_update") {
			$action_name = "multiple_insert_update";
			$action_types = array($action_type, $action_type == "insert" ? "update" : "insert");
		}
		else
			$action_types = array($action_type);
		
		foreach ($action_types as $at) {
			if (empty($brokers_services_and_rules[$at]))
				$settings_aux[$db_table]["actions"][$action_name] = array(
					"action" => $action_name,
					"action_type" => "", //"" == default inline action, thi is, non ajax.
				);
			else {
				$settings_aux[$db_table]["brokers"][$at] = $brokers_services_and_rules[$at];
				
				if ($at == "update")
					$settings_aux[$db_table]["brokers"]["update_pks"] = isset($brokers_services_and_rules["update_pks"]) ? $brokers_services_and_rules["update_pks"] : null;
				else if ($at != "insert")
					$settings_aux[$db_table]["brokers"]["get"] = isset($brokers_services_and_rules["get"]) ? $brokers_services_and_rules["get"] : null;
			}
		}
		
		if ($is_multiple)
			$settings_aux[$db_table]["actions"][$action_name] = array(
				"action" => $action_name,
				"action_type" => "", //"" == default inline action, thi is, non ajax.
			);
		
		$db_driver = isset($choose_db_table["db_driver"]) ? $choose_db_table["db_driver"] : null;
		$db_type = isset($choose_db_table["db_type"]) ? $choose_db_table["db_type"] : null;
		$include_db_driver = isset($choose_db_table["include_db_driver"]) ? $choose_db_table["include_db_driver"] : null;
		
		$default_dal_broker = self::getDefaultDBDataAccessBroker($PEVC, $user_global_variables_file_path, $user_beans_folder_path, $db_driver);
		$default_db_broker = self::getDefaultDBBroker($PEVC, $user_global_variables_file_path, $user_beans_folder_path, $db_driver);
		$include_db_broker = self::getIncludeDBBroker($PEVC, $user_global_variables_file_path, $user_beans_folder_path, $include_db_driver);
		
		$afs = CMSPresentationFormSettingsUIHandler::getFormSettings($user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $webroot_cache_folder_path, $webroot_cache_folder_url, $bean_name, $bean_file_name, $path, null, $default_dal_broker, $default_db_broker, $include_db_broker, $db_driver, $include_db_driver, $db_type, $tables_alias, $settings_aux, true, "", false, $permissions, $settings_php_codes_list);
		
		self::createFile($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $relative_path . substr($file_name_folder, 0, -1) . "_" . $action_name, $overwrite, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $authentication_list_and_edit_users, $authenticated_template, $non_authenticated_template, "ajax", $table_statuses, $afs, $task_id, $files_to_create, "ajax", $authentication, $permissions, null, $settings_php_codes_list);
	}
	
	private static function getDefaultDBBroker($PEVC, $user_global_variables_file_path, $user_beans_folder_path, $db_driver) {
		$brokers = $PEVC->getPresentationLayer()->getBrokers();
		
		//getting default db broker
		if ($db_driver) {
			$db_broker = WorkFlowBeansFileHandler::getBrokersLocalDBBrokerNameForChildBrokerDBDriver($user_global_variables_file_path, $user_beans_folder_path, $brokers, $db_driver);
			//Do not add here $GLOBALS["default_db_broker"] bc it will be already passed automatically through the RESTClientBroker
			
			if ($db_broker)
				return $db_broker;
		}
	}
	
	private static function getIncludeDBBroker($PEVC, $user_global_variables_file_path, $user_beans_folder_path, $include_db_driver) {
		if ($include_db_driver)
			return true;
		
		//check if exists other db broker and if not, do not return $db_broker, bc is the only one
		$brokers = $PEVC->getPresentationLayer()->getBrokers();
		$layer_brokers_settings = WorkFlowBeansFileHandler::getLayerBrokersSettings($user_global_variables_file_path, $user_beans_folder_path, $brokers);
		
		return isset($layer_brokers_settings["db_brokers"]) && count($layer_brokers_settings["db_brokers"]) > 1;
	}
	
	private static function getDefaultDBDataAccessBroker($PEVC, $user_global_variables_file_path, $user_beans_folder_path, $db_driver) {
		$brokers = $PEVC->getPresentationLayer()->getBrokers();
		
		//getting default db broker from forcing approach
		if ($brokers)
			foreach ($brokers as $broker_name => $broker)
				if (is_a($broker, "IDataAccessBrokerClient"))
					return $broker_name;
		
		return null;
	}

	private static function getGenericJSFunctions($js_funcs) {
		//only add this openPopup, openIframePopup and openEmbed functions if there is any code in the inner tasks that apply
		$js = '';
		
		if (!empty($js_funcs["ajax_navigation"]))
			$js .= '
function loadEmbededPageWithNewNavigation(page_attr_name, page_num, type, panel_id, elm) {
	var p = $(elm).parent().closest(".embeded-inner-task");
	
	if (p[0]) {
		var url = p.attr("data-url");
		
		if (url) {
			eval("url = decodeURI(url).replace(/" + page_attr_name + "=[^&]*/gi, \'\');");
			url += (url.indexOf("?") == -1 ? "?" : "&") + page_attr_name + "=" + page_num;
			url = url.replace(/[&]+/g, "&");
			p.attr("data-url", url);
			
			prepareUrlContent(p, url, function(html) {
				if (p.is("tr"))
					p.children("td").first().html(html);
				else if (p.hasClass("myfancypopup")) {
					p.contents(":not(.popup_close)").remove(); //do not remove close button
					p.append(html);
				}
				else
					p.html(html);
				
				if (typeof onNewHtml == "function")
					onNewHtml(elm, p);
			});
		}
	}
	else
		alert("Error: trying to find .embeded-inner-task element. Please check the function: loadEmbededPageWithNewNavigation.");
	
	return false;
}
';
		
		if (!empty($js_funcs["ajax_tab_navigation"]))
			$js .= '
function loadTabPageWithNewNavigation(page_attr_name, page_num, type, panel_id, elm) {
	var p = $(elm).parent().closest(".task-panel");
	
	if (p[0]) {
		var url = document.location.toString();
		eval("url = decodeURI(url).replace(/" + page_attr_name + "=[^&]*/gi, \'\');");
		url += (url.indexOf("?") == -1 ? "?" : "&") + page_attr_name + "=" + page_num;
		url = url.replace(/[&]+/g, "&");
		
		prepareUrlContent(p, url, function(html) {
			var task_id = p.attr("id");
			var task_html = $(html).find("#" + task_id).html();
			p.html(task_html);
			
			if (typeof onNewHtml == "function")
				onNewHtml(elm, p);
		});
	}
	else
		alert("Error: trying to find .embeded-inner-task element. Please check the function: loadTabPageWithNewNavigation.");
	
	return false;
}
';
		
		if (!empty($js_funcs["embeded"]))
			$js .= '
function openEmbed(elm, url) {
	elm = $(elm);
	var p = elm.parent().closest("tr, li, form, .task-panel");
	
	if (p.is(".task-panel"))
		p = p.parent();
	
	if (p.is("form")) //check if form even if is the .task-panel parent
		p = p.parent();
	
	if (p[0]) {
		var query_string = elm.attr("data-query-string");
		if (query_string)
			url += (url.indexOf("?") != -1 ? "&" : "?") + query_string;
		
		var previous_loaded_elm = p.is("tr") ? p.next(".embeded-inner-task") : p.children(".embeded-inner-task");
		
		if (!previous_loaded_elm[0]) {
			if (p.is("tr")) {
				previous_loaded_elm = $(\'<tr class="embeded-inner-task"><td colspan="\' + p.children().length + \'"></td></tr>\');
				previous_loaded_elm.insertAfter(p);
			}
			else {
				previous_loaded_elm = $(\'<div class="embeded-inner-task"><div>\');
				p.append(previous_loaded_elm);
			}
		}
		
		//if (previous_loaded_elm[0].hasAttribute("data-url"))
		//	previous_loaded_elm.toggle();
		if (previous_loaded_elm[0].hasAttribute("data-url") && previous_loaded_elm.css("display") != "none")
			previous_loaded_elm.hide();
		else {
			previous_loaded_elm.show().attr("data-url", url);
			
			if (previous_loaded_elm.is("tr"))
				previous_loaded_elm.children("td").first().html("");
			else
				previous_loaded_elm.html("");
			
			prepareUrlContent(elm, url, function(html) {
				if (previous_loaded_elm.is("tr"))
					previous_loaded_elm.children("td").first().html(html);
				else
					previous_loaded_elm.html(html);
				
				if (typeof onNewHtml == "function")
					onNewHtml(elm, previous_loaded_elm);
			});
		}
	}
	else 
		alert("Error: trying to find parent in openEmbed function. Please check the function: openEmbed.");
	
	return false;
}
';
		
		if (!empty($js_funcs["popup"]))
			$js .= '
function openPopup(elm, url) {
	elm = $(elm);
	var p = $("body");
	var query_string = elm.attr("data-query-string");
	
	if (query_string)
		url += (url.indexOf("?") != -1 ? "&" : "?") + query_string;
	
	if (typeof MyFancyPopupClass == "function") {
		var popup_elm = p.children(".myfancypopup.embeded-inner-task");
		
		if (!popup_elm[0]) {
			popup_elm = $(\'<div class="myfancypopup embeded-inner-task"><div>\');
			p.append(popup_elm);
		}
		
		//if (popup_elm.attr("data-url") != url) {
			popup_elm.html("");
			popup_elm.attr("data-url", url);
			
			prepareUrlContent(elm, url, function(html) {
				popup_elm.append(html);
				
				if (typeof onNewHtml == "function")
					onNewHtml(elm, popup_elm);
			});
		//}
		
		var popup = new MyFancyPopupClass();
		popup.init({
			elementToShow: popup_elm,
		});
		popup.showPopup();
	}
	else { //bootstrap modal
		var modal = p.find(" > .modal > .modal-dialog > .modal-content.embeded-inner-task").parent().closest(".modal");
		
		if (!modal[0]) {
			modal = \'<div class="modal" tabindex="-1" role="dialog">\'
					+ \'	<div class="modal-dialog modal-lg" role="document">\'
					+ \'		<div class="modal-content embeded-inner-task">\'
	 				+ \'	    </div>\'
					+ \'	  </div>\'
					+ \'	</div>\';
			modal = $(modal);
			p.append(modal);
		}
		
		var modal_content = modal.find(" > .modal-dialog > .modal-content.embeded-inner-task");
		
		//if (modal_content.attr("data-url") != url) {
			modal_content.html("");
			modal_content.attr("data-url", url);
			
			prepareUrlContent(elm, url, function(html) {
				modal_content.html(html);
				
				if (typeof onNewHtml == "function")
					onNewHtml(elm, modal_content);
			});
		//}
		
		if (typeof modal.modal == "function") {
			modal.modal();
			modal.modal("show");
		}
		else
			alert("Please include in your template the bootstrap.js or the jquery.myfancybox.js");
	}
	
	return false;
}
';
		
		if (!empty($js_funcs["iframe_popup"]))
			$js .= '
function openIframePopup(elm, url) {
	elm = $(elm);
	var p = $("body");
	var query_string = elm.attr("data-query-string");
	
	if (query_string)
		url += (url.indexOf("?") != -1 ? "&" : "?") + query_string;
	
	if (typeof MyFancyPopupClass == "function") {
		var popup_elm = p.children(".myfancypopup.iframe-inner-task");
		
		if (!popup_elm[0]) {
			popup_elm = $(\'<div class="myfancypopup iframe-inner-task"><div>\');
			p.append(popup_elm);
		}
		
		//if (popup_elm.children("iframe").attr("src") != url)
			popup_elm.html(\'<iframe src="\' + url + \'"></iframe>\');
		
		var popup = new MyFancyPopupClass();
		popup.init({
			elementToShow: popup_elm,
			type: "iframe",
		});
		popup.showPopup();
	}
	else { //bootstrap modal
		var modal = p.find(" > .modal > .modal-dialog > .modal-content.iframe-inner-task").parent().closest(".modal");
		
		if (!modal[0]) {
			modal = \'<div class="modal" tabindex="-1" role="dialog">\'
					+ \'	<div class="modal-dialog modal-lg" role="document">\'
					+ \'		<div class="modal-content iframe-inner-task">\'
	 				+ \'	    </div>\'
					+ \'	  </div>\'
					+ \'	</div>\';
			modal = $(modal);
			p.append(modal);
		}
		
		var modal_content = modal.find(" > .modal-dialog > .modal-content.iframe-inner-task");
		
		//if (modal_content.children("iframe").attr("src") != url)
			modal_content.html(\'<iframe src="\' + url + \'"></iframe>\');
		
		if (typeof modal == "function") {
			modal.modal();
			modal.modal("show");
		}
		else
			alert("Please include in your template the bootstrap.js or the jquery.myfancybox.js");
	}
	
	return false;
}
';
		
		if (!empty($js_funcs["parent"]))
			$js .= '
function openParentLocation(elm, url) {
	elm = $(elm);
	var query_string = elm.attr("data-query-string");
	
	if (query_string)
		url += (url.indexOf("?") != -1 ? "&" : "?") + query_string;
	
	if (window.top)
		window.top.location = url;
	else
		window.location = url;
		
	return false;
}
';
		
		if (!empty($js_funcs["embeded"]) || !empty($js_funcs["popup"]) || !empty($js_funcs["ajax_navigation"]) || !empty($js_funcs["ajax_tab_navigation"])) 
			$js .= '
function prepareUrlContent(elm, url, handler) {
	elm = $(elm);
	
	//execute ajax request to get the html from url and them call handler
	$.ajax({
		type : "get",
		url : url,
		dataType : "text",
		success : function(html, text_status, jqXHR) {
			if (typeof handler == "function")
				handler(html);
			else
				alert("Invalid handler in prepareUrlContent function. Please check your javascript code!");
		},
		error : function() {
			alert("Error: trying to get url.");
		},
	});
}
';
		
		return $js;
	}
	
	/* UTILS FUNCTIONS */

	private static function getTaskByTaskId($tasks, $selected_task_id) {
		if (!empty($tasks[$selected_task_id]))
			return $tasks[$selected_task_id];
		
		foreach ($tasks as $task_id => $task)
			if (!empty($task["tasks"])) {
				$t = self::getTaskByTaskId($task["tasks"], $selected_task_id);
				
				if ($t)
					return $t;
			}
		
		return null;
	}

	private static function getTaskPageParentTask($tasks, $target_task, $parents = array()) {
		$target_task_id = isset($target_task["id"]) ? $target_task["id"] : null;
		
		if (!empty($tasks[$target_task_id])) {
			foreach ($parents as $p)
				if (isset($p["tag"]) && $p["tag"] == "page")
					return $p;
			return null;
		}
		
		foreach ($tasks as $task_id => $task)
			if (!empty($task["tasks"])) {
				$ps = $parents; //copy $parents to another var
				$ps[] = $task;
				
				$t = self::getTaskPageParentTask($task["tasks"], $target_task, $ps);
				
				if ($t)
					return $t;
			}
		
		return null;
	}
	
	//used in create_presentation_uis_files_automatically.php too
	public static function getLabelFileName($label) {
		$name = TextSanitizer::normalizeAccents($label);
		$name = strtolower(str_replace(array(" ", "-"), "_", $name));
		
		return $name;
	}

	private static function getTaskLabelFileName($task) {
		return isset($task["label"]) ? self::getLabelFileName($task["label"]) : "";
	}

	private static function isSameTable($task_1, $task_2, $stricted = false) {
		$task_1_db_driver = isset($task_1["properties"]["choose_db_table"]["db_driver"]) ? $task_1["properties"]["choose_db_table"]["db_driver"] : null;
		$task_2_db_driver = isset($task_2["properties"]["choose_db_table"]["db_driver"]) ? $task_2["properties"]["choose_db_table"]["db_driver"] : null;
		
		$task_1_db_type = isset($task_1["properties"]["choose_db_table"]["db_type"]) ? $task_1["properties"]["choose_db_table"]["db_type"] : null;
		$task_2_db_type = isset($task_2["properties"]["choose_db_table"]["db_type"]) ? $task_2["properties"]["choose_db_table"]["db_type"] : null;
		
		$task_1_db_table = isset($task_1["properties"]["choose_db_table"]["db_table"]) ? $task_1["properties"]["choose_db_table"]["db_table"] : null;
		$task_2_db_table = isset($task_2["properties"]["choose_db_table"]["db_table"]) ? $task_2["properties"]["choose_db_table"]["db_table"] : null;
		
		return $task_1 && $task_2 && 
			!empty($task_1["properties"]) && 
			!empty($task_2["properties"]) && 
			!empty($task_1["properties"]["choose_db_table"]) && 
			!empty($task_2["properties"]["choose_db_table"]) && 
			$task_1_db_driver == $task_2_db_driver && 
			(!$stricted || $task_1_db_type == $task_2_db_type) && 
			$task_1_db_table == $task_2_db_table;
	}

	private static function prepareLinks(&$links) {
		if ($links && (isset($links["url"]) || isset($links["class"]) || isset($links["value"]) || isset($links["title"])))
			$links = array($links);
		
		return $links;
	}

	private static function getGetParentChildTableConditions($user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $bean_name, $bean_file_name, $db_driver, $db_type, $parent_table_name, $child_table_name) {
		$conditions = array();
		$WorkFlowDataAccessHandler = new WorkFlowDataAccessHandler();
		
		if ($db_type == "diagram") {//TRYING TO GET THE DB TABLES FROM THE TASK FLOW
			$tasks_file_path = WorkFlowTasksFileHandler::getDBDiagramTaskFilePath($workflow_paths_id, "db_diagram", $db_driver);
			$WorkFlowDataAccessHandler->setTasksFilePath($tasks_file_path);
		}
		else {//TRYING TO GET THE DB TABLES DIRECTLY FROM DB
			$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
			$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
			
			$db_layer_file = $obj && is_a($obj, "Layer") ? WorkFlowBeansFileHandler::getLayerDBDriverProps($user_global_variables_file_path, $user_beans_folder_path, $obj, $db_driver) : null;
			$db_layer_file = $db_layer_file && isset($db_layer_file[1]) ? $db_layer_file[1] : null;
			
			if ($db_layer_file) {
				$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_file_path);
				$tasks = $WorkFlowDBHandler->getUpdateTaskDBDiagram($db_layer_file, $db_driver);
				$WorkFlowDataAccessHandler->setTasks($tasks);
			}
		}
		
		$tables = $WorkFlowDataAccessHandler->getTasksAsTables();
		$parent_table_attrs = WorkFlowDBHandler::getTableFromTables($tables, $parent_table_name);
		$child_table_attrs = WorkFlowDBHandler::getTableFromTables($tables, $child_table_name);
		
		if ($parent_table_attrs && $child_table_attrs) {
			/*$foreign_keys = $WorkFlowDataAccessHandler->getForeignKeys();
			$foreign_keys = $foreign_keys[$parent_table_name];
			
			if ($foreign_keys)
				for ($i = 0; $i < count($foreign_keys); $i++) {
					$foreign_key = $foreign_keys[$i];
					
					if ($foreign_key["child_table"] == $child_table_name || $foreign_key["parent_table"] == $child_table_name) {
						$fks = $foreign_key["keys"];
						
						foreach ($fks as $fk) {
							$key = $foreign_key["child_table"] == $child_table_name ? "child" : "parent";
							$fk_name = $fk[$key];
							
							$key = $foreign_key["child_table"] == $parent_table_name ? "child" : "parent";
							$pk_name = $fk[$key];
							
							$conditions[] = array(
								"column" => $fk_name,
								"table" => $child_table_name,
								"value" => "{\$_GET[\"$pk_name\"]}",
							);
						}
					}
				}
			*/
			
			foreach ($parent_table_attrs as $attr)
				if (!empty($attr["primary_key"])) {
					$attr_name = isset($attr["name"]) ? $attr["name"] : null;
					
					$conditions[] = array(
						"column" => $attr_name,
						"table" => $parent_table_name,
						"value" => "{\$_GET[\"$attr_name\"]}",
					);
				}
		}
		
		return $conditions;
	}

	private static function createFile($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $file_path, $overwrite, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $authentication_list_and_edit_users, $authenticated_template, $non_authenticated_template, $template, &$table_statuses, $form_settings, $task_id, $files_to_create, $file_type, $authentication = null, $permissions = null, $page_settings = null, $settings_php_codes_list = null) {
		$P = $PEVC->getPresentationLayer();
		$layer_path = $P->getLayerPathSetting();
		$selected_project_id = $P->getSelectedPresentationId();
		$extension = $P->getPresentationFileExtension();
		
		$page_id = $file_path;
		$block_id = $page_id;
		
		if (!$overwrite) {
			CMSPresentationLayerHandler::configureUniqueFileId($page_id, $PEVC->getEntitiesPath(), "." . $extension);
			CMSPresentationLayerHandler::configureUniqueFileId($block_id, $PEVC->getBlocksPath(), "." . $extension);
		}
		
		$page_path = $PEVC->getEntityPath($page_id);
		$block_path = $PEVC->getBlockPath($block_id);
		
		$entity_code_id = str_replace($layer_path, "", $page_path);
		$entity_code_id = substr($entity_code_id, 0, strlen($entity_code_id) - (strlen($extension) + 1));
		$block_code_id = str_replace($layer_path, "", $block_path);
		$block_code_id = substr($block_code_id, 0, strlen($block_code_id) - (strlen($extension) + 1));
		
		//prepare page_settings
		$entity_settings = array(
			"includes" => array(),
			"regions_blocks" => array(),
			"template_params" => array(
				"Page Title" => CMSPresentationFormSettingsUIHandler::getName(basename($file_path)) //Page title will be overwrited bellow if exists any correspondent template_param...
			),
			"template" => $template,
		);
		
		if ($page_settings && !empty($page_settings["regions_blocks"])) {
			if (isset($page_settings["regions_blocks"]["region"]) || isset($page_settings["regions_blocks"]["block"]) || isset($page_settings["regions_blocks"]["project"]))
				$page_settings["regions_blocks"] = array($page_settings["regions_blocks"]);
			
			foreach ($page_settings["regions_blocks"] as $rb) {
				$region = isset($rb["region"]) ? $rb["region"] : null;
				$block = isset($rb["block"]) ? $rb["block"] : null;
				$project = isset($rb["project"]) ? $rb["project"] : null;
				$project = $project == $selected_project_id ? null : $project;
				
				if ($region && $block)
					$entity_settings["regions_blocks"][] = array("region" => $region, "block" => $block, "project" => $project);
			}
		}
		
		//prepare includes
		if ($page_settings && !empty($page_settings["includes"])) {
			if (isset($page_settings["includes"]["path"]) || isset($page_settings["includes"]["once"])) 
				$page_settings["includes"] = array($page_settings["includes"]);
			
			$entity_settings["includes"] = $page_settings["includes"];
		}
		
		if ($vars_file_include_code) {
			$exists = false;
			foreach ($entity_settings["includes"] as $inc)
				if (isset($inc["path"]) && strpos($inc["path"], $vars_file_include_code) !== false) {
					$exists = true;
					break;
				}
			
			if (!$exists)
				$entity_settings["includes"][] = array("path" => $vars_file_include_code, "once" => 1);
		}
		
		if ($permissions || $authentication) {
			$exists = false;
			foreach ($entity_settings["includes"] as $inc) 
				if (isset($inc["path"]) && strpos($inc["path"], "user/include_user_session_activities_handler") !== false) {
					$exists = true;
					break;
				}
			
			if (!$exists)
				$entity_settings["includes"][] = array("path" => '$EVC->getModulePath("user/include_user_session_activities_handler", "' . $PEVC->getCommonProjectName() . '")');
		}
		
		//prepare template params
		if ($page_settings && !empty($page_settings["template_params"])) {
			if (isset($page_settings["template_params"]["name"]) || isset($page_settings["template_params"]["value"])) 
				$page_settings["template_params"] = array($page_settings["template_params"]);
			
			foreach ($page_settings["template_params"] as $tp) 
				if (!empty($tp["name"]))
					$entity_settings["template_params"][ $tp["name"] ] = $tp["value"];
		}
		
		//must execute at the end bc it uses the $entity_settings
		if ($authentication && isset($authentication["authentication_type"]) && $authentication["authentication_type"] == "authenticated") {
			$authentication_users = $authentication["authentication_users"];
			
			if (is_array($authentication_users) && array_key_exists("user_type_id", $authentication_users))
				$authentication_users = array($authentication_users);
			
			if (!self::prepareAuthenticationBlocksId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $authentication_users, $entity_settings, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $authentication_list_and_edit_users, $authenticated_template, $non_authenticated_template, $task_id, $files_to_create, $table_statuses, $page_id, $block_id))
				return false;
		}
		
		//echo "\n$page_id\n";print_r($entity_settings);
		
		//create files
		switch ($files_creation_type) {
			case 2: 
				$table_statuses[$task_id][$page_path] = array(
					"file_id" => CMSPresentationLayerHandler::getFilePathId($PEVC, $page_path),
					"type" => $file_type,
				);
				
				if (file_exists($page_path)) {
					$table_statuses[$task_id][$page_path]["created_time"] = filectime($page_path);
					$table_statuses[$task_id][$page_path]["modified_time"] = filemtime($page_path);
					$table_statuses[$task_id][$page_path]["hard_coded"] = CMSPresentationLayerHandler::isEntityFileHardCoded($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $page_path);
				}
				
				$table_statuses[$task_id][$block_path] = array(
					"file_id" => CMSPresentationLayerHandler::getFilePathId($PEVC, $block_path),
					"type" => $file_type,
				);
				
				if (file_exists($block_path)) {
					$table_statuses[$task_id][$block_path]["created_time"] = filectime($block_path);
					$table_statuses[$task_id][$block_path]["modified_time"] = filemtime($block_path);
				}
				
				break;
			
			case 3: 
				$create_page = !$files_to_create || !isset($files_to_create[$entity_code_id]) || $files_to_create[$entity_code_id];
				$create_block = !$files_to_create || !isset($files_to_create[$block_code_id]) || $files_to_create[$block_code_id];
				
				if ($create_block) {
					$block_code = CMSPresentationUIAutomaticFilesHandler::getFormSettingsBlockCode($form_settings, array("form_settings_php_codes_list" => $settings_php_codes_list));
					$table_statuses[$task_id][$block_path] = array(
						"old_code" => file_exists($block_path) ? file_get_contents($block_path) : "",
						"new_code" => $block_code,
					);
				}
				
				if ($create_page) {
					$exists = false;
					foreach ($entity_settings["regions_blocks"] as $rb) {
						$rb_block = isset($rb["block"]) ? $rb["block"] : null;
						
						if ($rb_block == $block_id && empty($rb["project"])) {
							$exists = true;
							break;
						}
					}
					
					if (!$exists)
						$entity_settings["regions_blocks"][] = array("region" => "Content", "block" => $block_id);
					
					$entity_code = CMSPresentationUIAutomaticFilesHandler::getEntityCode($entity_settings);
					$table_statuses[$task_id][$page_path] = array(
						"old_code" => file_exists($page_path) ? file_get_contents($page_path) : "",
						"new_code" => $entity_code,
					);
				}
				
				break;
				
			default: //save to files
				$task_statuses = array();
				$create_page = !$files_to_create || !isset($files_to_create[$entity_code_id]) || $files_to_create[$entity_code_id];
				$create_block = !$files_to_create || !isset($files_to_create[$block_code_id]) || $files_to_create[$block_code_id];
				
				if ($create_block) {
					$block_code = CMSPresentationUIAutomaticFilesHandler::getFormSettingsBlockCode($form_settings, array("form_settings_php_codes_list" => $settings_php_codes_list));
					CMSPresentationUIAutomaticFilesHandler::saveBlockCode($PEVC, $block_id, $block_code, $overwrite, $task_statuses);
					$block_path = $PEVC->getBlockPath($block_id); //block_id may change in the saveBlockCode
				}
				
				if ($create_page) {
					$exists = false;
					foreach ($entity_settings["regions_blocks"] as $rb) {
						$rb_block = isset($rb["block"]) ? $rb["block"] : null;
						
						if ($rb_block == $block_id && empty($rb["project"])) {
							$exists = true;
							break;
						}
					}
					
					if (!$exists)
						$entity_settings["regions_blocks"][] = array("region" => "Content", "block" => $block_id);
					
					CMSPresentationUIAutomaticFilesHandler::createAndSaveEntityCode($PEVC, $page_id, $entity_settings, $overwrite, $task_statuses);
					$page_path = $PEVC->getEntityPath($page_id); //page_id may change in the createAndSaveEntityCode
					
					//caching save action for page
					self::preparePageSaveActionTime($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $page_id, $task_statuses);
					
					//saving access permission for page
					if (!empty($authentication_users))
						self::addPageUsersAccessPermission($PEVC, $authentication_users, $page_id);
					
					//saving other permissions for page
					if ($permissions)
						self::addPageUsersPermission($PEVC, $permissions, $page_id);
				}
				
				$table_statuses[$task_id][$page_path] = (!isset($task_statuses[$page_path]) && file_exists($page_path)) || $task_statuses[$page_path] ? array(
					"file_id" => CMSPresentationLayerHandler::getFilePathId($PEVC, $page_path), 
					"created_time" => filectime($page_path),
					"modified_time" => filemtime($page_path), 
					"status" => true,
				) : false;
				$table_statuses[$task_id][$block_path] = (!isset($task_statuses[$block_path]) && file_exists($block_path)) || $task_statuses[$block_path] ? array(
					"file_id" => CMSPresentationLayerHandler::getFilePathId($PEVC, $block_path), 
					"created_time" => filectime($block_path),
					"modified_time" => filemtime($block_path), 
					"status" => true,
				) : false;
		}
		
		return true;
	}
	
	/* AUTHENTICATION FUNCTIONS */
	
	private static function prepareAuthenticationBlocksId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $authentication_users, &$page_settings, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $authentication_list_and_edit_users, $authenticated_template, $non_authenticated_template, $task_id, $files_to_create, &$table_statuses, $page_id, $block_id) {
		$exists_public_access = false;
		if ($authentication_users)
			foreach ($authentication_users as $idx => $authentication_user)
				if (isset($authentication_user["user_type_id"]) && $authentication_user["user_type_id"] == UserUtil::PUBLIC_USER_TYPE_ID) {
					$exists_public_access = true;
					break;
				}
		
		if (!$exists_public_access) {
			//prepare activity_id for "access". 
			if (!self::$access_id)
				self::$access_id = CMSPresentationUIAutomaticFilesHandler::getActivityIdByName($PEVC, "access");
			
			//prepare object_type_id for "page"
			if (!self::$object_type_page_id) 
				self::$object_type_page_id = CMSPresentationUIAutomaticFilesHandler::getObjectTypeIdByName($PEVC, "page");
			
			if (!self::$access_id || !self::$object_type_page_id)
				return false;
			
			//load page and blocks for auth
			self::getOrCreateRegisterPageId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $non_authenticated_template, $task_id, $files_to_create, $table_statuses);
			self::getOrCreateForgotCredentialsPageId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $non_authenticated_template, $task_id, $files_to_create, $table_statuses);
			self::getOrCreateLoginPageId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $non_authenticated_template, $task_id, $files_to_create, $table_statuses);
			self::getOrCreateLogoutPageId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $non_authenticated_template, $task_id, $files_to_create, $table_statuses);
			self::getOrCreateValidateLoggedUserActivityBlockId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $non_authenticated_template, $task_id, $files_to_create, $table_statuses, self::$access_id, self::$object_type_page_id);
			
			if (!self::$auth_validation_block_id)
				return false;
			
			//add self::$auth_validation_block_id to page_settings
			$exists_block = $extra_head_region = false;
			foreach ($page_settings["regions_blocks"] as $rb) {
				$rb_region = isset($rb["region"]) ? $rb["region"] : null;
				$rb_block = isset($rb["block"]) ? $rb["block"] : null;
				
				if (strtolower($rb_region) == "head")
					$extra_head_region = $rb_region;
				
				if ($rb_block == self::$auth_validation_block_id && empty($rb["project"]))
					$exists_block = true;
			}
			
			if (!$exists_block)
				array_unshift($page_settings["regions_blocks"], array("region" => $extra_head_region ? $extra_head_region : "Content", "block" => self::$auth_validation_block_id));
			
			//create edit_profile page
			$already_exists_edit_profile_page_id = !empty(self::$edit_profile_page_id);
			self::getOrCreateEditProfilePageId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $authenticated_template, $task_id, $files_to_create, $table_statuses, $page_settings, $block_id); //If we have register and forgot credentials, we must have a page to edit the profile too. This page will be used outside the CMSPresentationUIDiagramFilesHandler class and can be accessable through the CMSPresentationUIDiagramFilesHandler::getAuthPageAndBlockIds(); This code must be the last one bc it uses the $page_settings and self::$auth_validation_block_id initialized by the getOrCreateValidateLoggedUserActivityBlockId method.
			
			//create list and edit user types page
			if ($authentication_list_and_edit_users) {
				$authentication_list_and_edit_users = is_array($authentication_list_and_edit_users) ? $authentication_list_and_edit_users : explode(",", $authentication_list_and_edit_users);
				
				$authentication_le_users = array();
				foreach ($authentication_list_and_edit_users as $user_type_id)
					if ($user_type_id)
						$authentication_le_users[] = array("user_type_id" => $user_type_id); 
				
				$already_exists_list_and_edit_users_page_id = !empty(self::$list_and_edit_users_page_id);
				$already_exists_list_users_page_id = !empty(self::$list_users_page_id);
				$already_exists_edit_user_page_id = !empty(self::$edit_user_page_id);
				$already_exists_add_user_page_id = !empty(self::$add_user_page_id);
				
				//If we have register and forgot credentials, we must have a page to list and edit the user types too. This page will be used outside the CMSPresentationUIDiagramFilesHandler class and can be accessable through the CMSPresentationUIDiagramFilesHandler::getAuthPageAndBlockIds(); This code must be the last one bc it uses the $page_settings and self::$auth_validation_block_id initialized by the getOrCreateValidateLoggedUserActivityBlockId method.
				self::getOrCreateListAndEditUsersPageId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $authenticated_template, $task_id, $files_to_create, $table_statuses, $page_settings, $block_id); 
				
				self::getOrCreateListUsersPageId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $authenticated_template, $task_id, $files_to_create, $table_statuses, $page_settings, $block_id); 
				
				self::getOrCreateEditUserPageId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $authenticated_template, $task_id, $files_to_create, $table_statuses, $page_settings, $block_id);  
				
				self::getOrCreateAddUserPageId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $authenticated_template, $task_id, $files_to_create, $table_statuses, $page_settings, $block_id); 
			}
			
			//if file should be created
			if (!$files_creation_type || $files_creation_type == 1) {
				if (self::$edit_profile_page_id && !self::addPageUsersAccessPermission($PEVC, $authentication_users, self::$edit_profile_page_id, !$already_exists_edit_profile_page_id))
					return false;
				
				if (!empty($authentication_le_users)) {
					if (self::$list_and_edit_users_page_id && !self::addPageUsersAccessPermission($PEVC, $authentication_le_users, self::$list_and_edit_users_page_id, empty($already_exists_list_and_edit_users_page_id)))
						return false;
					
					if (self::$list_users_page_id && !self::addPageUsersAccessPermission($PEVC, $authentication_le_users, self::$list_users_page_id, empty($already_exists_list_users_page_id)))
						return false;
					
					if (self::$edit_user_page_id && !self::addPageUsersAccessPermission($PEVC, $authentication_le_users, self::$edit_user_page_id, empty($already_exists_edit_user_page_id)))
						return false;
					
					if (self::$add_user_page_id && !self::addPageUsersAccessPermission($PEVC, $authentication_le_users, self::$add_user_page_id, empty($already_exists_add_user_page_id)))
						return false;
				}
			}
		}
		
		return true;
	}
	
	private static function addPageUsersAccessPermission($PEVC, $authentication_users, $page_id, $remove_old_perms = true) {
		if ($page_id) {
			//delete or add user object activity based in $authentication_users
			$fp = str_replace(APP_PATH, "", $PEVC->getEntityPath($page_id));
			$page_object_id = HashCode::getHashCodePositive($fp); //create hash code to check in the mu_user_type_activity table
			$status = !$remove_old_perms || CMSPresentationUIAutomaticFilesHandler::deleteUserTypeActivityObjects($PEVC, self::$access_id, self::$object_type_page_id, $page_object_id);
			
			if ($authentication_users) {
				$user_types_with_access_perm = array();
				
				//if (!$remove_old_perms) {
					$utas = CMSPresentationUIAutomaticFilesHandler::getUserTypeActivityObjectsByObject($PEVC, self::$object_type_page_id, $page_object_id);
					if ($utas)
						foreach ($utas as $uta)
							if (isset($uta["activity_id"]) && $uta["activity_id"] == self::$access_id)
								$user_types_with_access_perm[] = isset($uta["user_type_id"]) ? $uta["user_type_id"] : null;
				//}
				
				foreach ($authentication_users as $idx => $authentication_user)
					if (isset($authentication_user["user_type_id"]) && !in_array($authentication_user["user_type_id"], $user_types_with_access_perm) && !CMSPresentationUIAutomaticFilesHandler::insertUserTypeActivityObject($PEVC, $authentication_user["user_type_id"], self::$access_id, self::$object_type_page_id, $page_object_id))
						$status = false;
			}
			
			//delete cache otherwise the old user perms will still be in cache.
			CMSPresentationUIAutomaticFilesHandler::removeAllUserTypeActivitySessionsCache($PEVC);
			
			return $status;
		}
	}
	
	private static function addPageUsersPermission($PEVC, $permissions, $page_id) {
		if ($page_id) {
			$fp = str_replace(APP_PATH, "", $PEVC->getEntityPath($page_id));
			$page_object_id = HashCode::getHashCodePositive($fp); //create hash code to check in the mu_user_type_activity table
			
			$status = true;
			
			//delete all pervious permissions for page (with the exception of the access permission)
			$all_activities = CMSPresentationUIAutomaticFilesHandler::getAvailableActivities($PEVC);
			if ($all_activities) 
				foreach ($all_activities as $activity_id => $activity_name)
					if ($activity_id != self::$access_id)
						if (!CMSPresentationUIAutomaticFilesHandler::deleteUserTypeActivityObjects($PEVC, $activity_id, self::$object_type_page_id, $page_object_id))
							$status = false;
			
			$user_types_ativities = array();
			$utas = CMSPresentationUIAutomaticFilesHandler::getUserTypeActivityObjectsByObject($PEVC, self::$object_type_page_id, $page_object_id);
			
			if ($utas)
				foreach ($utas as $uta)
					$user_types_ativities[] = (isset($uta["activity_id"]) ? $uta["activity_id"] : "") . "_" . (isset($uta["user_type_id"]) ? $uta["user_type_id"] : "");
			
			//add the correspondent permissions
			if ($permissions)
				foreach ($permissions as $idx => $permission)
					if (isset($permission["user_type_id"]) && is_numeric($permission["user_type_id"]) && isset($permission["activity_id"]) && is_numeric($permission["activity_id"]) && !in_array($permission["activity_id"] . "_" . $permission["user_type_id"], $user_types_ativities)) //Note that the access permission may already exists, so we only want to add the permissions that don't exist yet.
						if (!CMSPresentationUIAutomaticFilesHandler::insertUserTypeActivityObject($PEVC, $permission["user_type_id"], $permission["activity_id"], self::$object_type_page_id, $page_object_id))
							$status = false;
			
			//delete cache otherwise the old user perms will still be in cache.
			CMSPresentationUIAutomaticFilesHandler::removeAllUserTypeActivitySessionsCache($PEVC);
			
			return $status;
		}
	}
	
	private static function getValidateLoggedUserActivityBlockId($PEVC, $SystemUserCacheHandler, $folder_path, $activity_id, $object_type_id, $object_id) {
		if ($folder_path && is_dir($folder_path)) {
			$files = scandir($folder_path);
			$folder_path .= substr($folder_path, -1) == "/" ? "" : "/";
			
			if ($files)
				foreach ($files as $file) 
					if (substr($file, -4) == ".php") {
						$contents = file_get_contents("$folder_path$file");
						
						//checks if is a validate_user_activity module with the correspondent activity_id, object_type_id and object_value, this is:
						//- checks if exists the string: '->createBlock("user/validate_user_activity",'
						//- checks if exists the string: '"activity_id" => "1"',
						//- checks if exists the string: '"object_type_id" => "1"',
						//- checks if exists the string: '"object_id" => $entity_path,
						//'/u' means with accents and ç too. '/u' converts unicode to accents chars.
						$exists = preg_match('/->( *)createBlock( *)\(( *)("|\')user\/validate_user_activity("|\')( *),( *)/', $contents) &&
							preg_match('/"activity_id"( *)=>( *)("|\'|)' . $activity_id . '("|\'|)( *)(,|\n|\)| )/u', $contents) &&
							preg_match('/"object_type_id"( *)=>( *)("|\'|)' . $object_type_id . '("|\'|)( *)(,|\n|\)| )/u', $contents) &&
							preg_match('/"object_id"( *)=>( *)("|\'|)' . str_replace('$', '\$', $object_id) . '("|\'|)( *)(,|\n|\)| )/u', $contents);
						
						if ($exists) {
							$blocks_path = $PEVC->getBlocksPath();
							$blocks_path .= substr($blocks_path, -1) == "/" ? "" : "/";
							$blocks_path = substr("$folder_path$file", strlen($blocks_path), -4);
							$blocks_path = preg_replace("/^\/+/", "", $blocks_path);
							
							return $blocks_path;
						}
					}
					else if ($file != "." && $file != ".." && is_dir("$folder_path$file")) {
						$block_id = self::getValidateLoggedUserActivityBlockId($PEVC, $SystemUserCacheHandler, "$folder_path$file/", $activity_id, $object_type_id, $object_id);
						
						if ($block_id) 
							return $block_id;
					}
		}
		
		return false;
	}
	
	private static function getOrCreateValidateLoggedUserActivityBlockId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $template, $task_id, $files_to_create, &$table_statuses, $activity_id, $object_type_id) {
		$task_statuses = array();
		
		if (!self::$auth_validation_block_id) {
			$object_page_value = '$entity_path';
			$blocks_path = $PEVC->getBlocksPath() . $authentication_files_relative_folder_path;
			$block_id = self::getValidateLoggedUserActivityBlockId($PEVC, $SystemUserCacheHandler, $blocks_path, $activity_id, $object_type_id, $object_page_value);
			
			if (!$block_id) { //create block
				$block_id = $authentication_files_relative_folder_path . "_validate_access";
				$block_id = preg_replace("/^\/+/", "", $block_id);
				
				do {
					$exists = file_exists($PEVC->getBlockPath($block_id));
					if ($exists)
						$block_id .= "_" . rand(0, 10000);
				}
				while($exists);
				
				$logout_page_id = self::getOrCreateLogoutPageId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $template, $task_id, $files_to_create, $table_statuses);
				
				if (!$files_creation_type || $files_creation_type == 1) {
					$block_code = '<?php
$block_id = $EVC->getCMSLayer()->getCMSBlockLayer()->getBlockIdFromFilePath(__FILE__);//must be the same than this file name.

$block_settings[$block_id] = array(
	"activity_id" => "' . $activity_id . '",
	"object_type_id" => "' . $object_type_id . '",
	"object_id" => ' . $object_page_value . ',
	"object_id_page_level" => "",
	"group" => "",
	"group_page_level" => "",
	"validation_condition_type" => "",
	"validation_condition" => "",
	"validation_action" => "do_nothing",
	"validation_message" => "",
	"validation_redirect" => "",
	"validation_blocks_execution" => "do_nothing",
	"non_validation_action" => "' . ($logout_page_id ? "alert_message_and_redirect" : "alert_message_and_die") . '",
	"non_validation_message" => "You do not have permission to access to this page. Please login with another user with enough permissions.",
	"non_validation_redirect" => "' . ($logout_page_id ? '{$project_url_prefix}' . $logout_page_id . "?hide=1" : "") . '",
	"non_validation_blocks_execution" => "do_nothing",
	"style_type" => "",
	"block_class" => "validate_access",
	"css" => "",
	"js" => "",
);

$EVC->getCMSLayer()->getCMSBlockLayer()->createBlock("user/validate_user_activity", $block_id, $block_settings[$block_id]);
?>';
					
					$status = CMSPresentationUIAutomaticFilesHandler::saveBlockCode($PEVC, $block_id, $block_code, true, $task_statuses);
				}
			}
			
			self::$auth_validation_block_id = $block_id;
		}
		
		self::prepareBlockTableStatus($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $task_id, $table_statuses, self::$auth_validation_block_id, $task_statuses);
		
		return self::$auth_validation_block_id;
	}
	
	private static function getOrCreateLogoutPageId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $template, $task_id, $files_to_create, &$table_statuses) {
		$task_statuses = array();
		
		if (!self::$logout_block_id) {
			$blocks_path = $PEVC->getBlocksPath() . $authentication_files_relative_folder_path;
			$block_id = self::getBlockIdByModuleId($PEVC, $blocks_path, "user/logout");
			
			if (!$block_id) { //create block
				$block_id = $authentication_files_relative_folder_path . "_logout";
				$block_id = preg_replace("/^\/+/", "", $block_id);
				
				if (!$files_creation_type || $files_creation_type == 1) {
					$js = '';
					$login_page_id = self::getOrCreateLoginPageId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $template, $task_id, $files_to_create, $table_statuses);
					
					if ($login_page_id)
						$js = 'var hide = \\"{$_GET[\'hide\']}\\";

if (hide == \\"1\\")
    document.location = \'{$project_url_prefix}' . $login_page_id . '\';
else {
    window.onload = function() {
	   setTimeout(function() {
		  document.location = \'{$project_url_prefix}' . $login_page_id . '\';
	   }, 5000);    
    };
}';
					
					$block_code = '<?php
$block_id = $EVC->getCMSLayer()->getCMSBlockLayer()->getBlockIdFromFilePath(__FILE__);//must be the same than this file name.

$block_settings[$block_id] = array(
	"style_type" => "template",
	"validation_action" => "' . ($login_page_id ? "show_message" : "show_message_and_redirect") . '",
	"validation_message" => "You are now logged out. <br>To go back to your login page please click <a href=\'{$project_url_prefix}' . $login_page_id . '\'>here</a>",
	"validation_class" => "logout",
	"validation_redirect" => "' . ($login_page_id ? '{$project_url_prefix}' . $login_page_id : "") . '",
	"validation_ttl" => ' . ($login_page_id ? '(!empty($_GET["hide"]) ? 0 : 5) . ""' : '""') . ',
	"validation_blocks_execution" => "do_nothing",
	"non_validation_action" => "",
	"non_validation_message" => "",
	"non_validation_class" => "logout",
	"non_validation_redirect" => "",
	"non_validation_blocks_execution" => "do_nothing",
	"domain" => "",
	"client_id" => "",
	"css" => "",
	"js" => "' . $js . '",
);

$EVC->getCMSLayer()->getCMSBlockLayer()->createBlock("user/logout", $block_id, $block_settings[$block_id]);
?>';
					
					$status = CMSPresentationUIAutomaticFilesHandler::saveBlockCode($PEVC, $block_id, $block_code, true, $task_statuses);
				}
			}
			
			self::$logout_block_id = $block_id;
		}
		
		if (!self::$logout_page_id && self::$logout_block_id) {
			$entities_path = $PEVC->getEntitiesPath() . $authentication_files_relative_folder_path;
			$page_id = self::getPageIdByBlockId($PEVC, $entities_path, self::$logout_block_id);
			
			if (!$page_id) { //create page
				$page_id = $authentication_files_relative_folder_path . "_logout";
				$page_id = preg_replace("/^\/+/", "", $page_id);
				
				if (!$files_creation_type || $files_creation_type == 1) {
					CMSPresentationUIAutomaticFilesHandler::createAndSaveEntityCode($PEVC, $page_id, array(
						"includes" => array(
							array("path" => $vars_file_include_code, "once" => 1),
						),
						"regions_blocks" => array(
							array("region" => "Content", "block" => self::$logout_block_id)
						),
						"template_params" => array(
							"Page Title" => "Logout"
						),
						"template" => $template,
					), false, $task_statuses);
					
					self::preparePageSaveActionTime($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $page_id, $task_statuses);
				}
			}
			
			self::$logout_page_id = $page_id;
		}
		
		self::prepareBlockTableStatus($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $task_id, $table_statuses, self::$logout_block_id, $task_statuses);
		self::preparePageTableStatus($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $task_id, $table_statuses, self::$logout_page_id, $task_statuses);
		
		return self::$logout_page_id;
	}
	
	private static function getOrCreateLoginPageId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $template, $task_id, $files_to_create, &$table_statuses) {
		$task_statuses = array();
		
		if (!self::$login_block_id) {
			$blocks_path = $PEVC->getBlocksPath() . $authentication_files_relative_folder_path;
			$block_id = self::getBlockIdByModuleId($PEVC, $blocks_path, "user/login");
			
			if (!$block_id) { //create block
				$block_id = $authentication_files_relative_folder_path . "_login";
				$block_id = preg_replace("/^\/+/", "", $block_id);
				
				if (!$files_creation_type || $files_creation_type == 1) {
					//get register page
					$register_page_id = self::getOrCreateRegisterPageId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $template, $task_id, $files_to_create, $table_statuses);
					$register_page_url = $register_page_id ? '{$project_url_prefix}' . $register_page_id : '';
					
					//get forgot_credentials page
					$forgot_credentials_page_id = self::getOrCreateForgotCredentialsPageId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $template, $task_id, $files_to_create, $table_statuses);
					$forgot_credentials_page_url = $forgot_credentials_page_id ? '{$project_url_prefix}' . $forgot_credentials_page_id : '';
					
					//get page to redirect
					$redirect_page_id = $authentication_files_relative_folder_path;
					
					//index file may not exists, so we must find the next path
					if (!file_exists($PEVC->getEntityPath($authentication_files_relative_folder_path . "index")) && is_array($files_to_create)) {
						$found = false;
						
						foreach ($files_to_create as $file => $to_create) {
							$seaches = array("/src/entity/", "/src/block/");
							
							foreach ($seaches as $search) {
								$to_search = $search . $authentication_files_relative_folder_path;
								$pos = strpos($file, $to_search);
								
								if ($pos !== false) {
									$redirect_page_id .= substr($file, $pos + strlen($to_search));
									$found = true;
									break 2;
								}
							}
						}
						
						if (!$found) {
							$entities_path = $PEVC->getEntitiesPath() . $authentication_files_relative_folder_path;
							$file = self::getHomepageIdFromFolder($entities_path);
							
							if ($file)
								$redirect_page_id .= $file;
						}
					}
					//echo "redirect_page_id:$redirect_page_id";die();
					
					$redirect_page_url = '{$project_url_prefix}' . $redirect_page_id;
					
					//prepare block code
					$block_code = '<?php
$block_id = $EVC->getCMSLayer()->getCMSBlockLayer()->getBlockIdFromFilePath(__FILE__);//must be the same than this file name.

$block_settings[$block_id] = array(
	"maximum_login_attempts_to_block_user" => 5,
	"show_captcha" => 1,
	"maximum_login_attempts_to_show_captcha" => 3,
	"redirect_page_url" => "' . $redirect_page_url . '",
	"forgot_credentials_page_url" => "' . $forgot_credentials_page_url . '",
	"register_page_url" => "' . $register_page_url . '",
	"style_type" => "template",
	"block_class" => "login",
	"css" => "",
	"js" => "",
	"do_not_encrypt_password" => 1,
	"show_username" => 1,
	"show_password" => 1,
	"username_default_value" => "",
	"password_default_value" => "",
	"register_attribute_label" => "Register?",
	"forgot_credentials_attribute_label" => "Forgot Credentials?",
	"fields" => array(
		"username" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "username",
				"label" => array(
					"type" => "label",
					"value" => "Username: ",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "text",
					"class" => "",
					"place_holder" => "your username",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "Username cannot be undefined.",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"password" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "password",
				"label" => array(
					"type" => "label",
					"value" => "Password: ",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "password",
					"class" => "",
					"place_holder" => "your password",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "Password cannot be undefined.",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
	),
);

$EVC->getCMSLayer()->getCMSBlockLayer()->createBlock("user/login", $block_id, $block_settings[$block_id]);
?>';
					
					$status = CMSPresentationUIAutomaticFilesHandler::saveBlockCode($PEVC, $block_id, $block_code, true, $task_statuses);
				}
			}
			
			self::$login_block_id = $block_id;
		}
		
		if (!self::$login_page_id && self::$login_block_id) {
			$entities_path = $PEVC->getEntitiesPath() . $authentication_files_relative_folder_path;
			$page_id = self::getPageIdByBlockId($PEVC, $entities_path, self::$login_block_id);
			
			if (!$page_id) { //create page
				$page_id = $authentication_files_relative_folder_path . "_login";
				$page_id = preg_replace("/^\/+/", "", $page_id);
				
				if (!$files_creation_type || $files_creation_type == 1) {
					CMSPresentationUIAutomaticFilesHandler::createAndSaveEntityCode($PEVC, $page_id, array(
						"includes" => array(
							array("path" => $vars_file_include_code, "once" => 1),
						),
						"regions_blocks" => array(
							array("region" => "Content", "block" => self::$login_block_id)
						),
						"template_params" => array(
							"Page Title" => "Login"
						),
						"template" => $template,
					), false, $task_statuses);
					
					self::preparePageSaveActionTime($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $page_id, $task_statuses);
				}
			}
			
			self::$login_page_id = $page_id;
		}
		
		self::prepareBlockTableStatus($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $task_id, $table_statuses, self::$login_block_id, $task_statuses);
		self::preparePageTableStatus($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $task_id, $table_statuses, self::$login_page_id, $task_statuses);
		
		return self::$login_page_id;
	}
	
	private static function getHomepageIdFromFolder($path) {
		$files = array_diff(scandir($path), array('..', '.'));
		
		foreach ($files as $file) {
			$fc = substr($file, 0, 1);
			
			if ($fc != "_" && $fc != "." && preg_match("/\.php[0-9]*$/i", $file) && !is_dir("$path$file"))
				return pathinfo($file, PATHINFO_FILENAME);
		}
		
		foreach ($files as $file) {
			if (is_dir("$path$file")) {
				$f = self::getHomepageIdFromFolder("$path$file/");
				
				if ($f)
					return "$file/$f";
			}
		}
		
		return null;
	}
	
	private static function getOrCreateRegisterPageId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $template, $task_id, $files_to_create, &$table_statuses) {
		$task_statuses = array();
		
		if (!self::$register_block_id) {
			$blocks_path = $PEVC->getBlocksPath() . $authentication_files_relative_folder_path;
			$block_id = self::getBlockIdByModuleId($PEVC, $blocks_path, "user/register");
			
			if (!$block_id) { //create block
				$block_id = $authentication_files_relative_folder_path . "_register";
				$block_id = preg_replace("/^\/+/", "", $block_id);
				
				if (!$files_creation_type || $files_creation_type == 1) {
					$security_question_options_code = self::getSecurityQuestionOptionsCode();
					
					$block_code = '<?php
$block_id = $EVC->getCMSLayer()->getCMSBlockLayer()->getBlockIdFromFilePath(__FILE__);//must be the same than this file name.

$block_settings[$block_id] = array(
	"user_type_id" => "' . UserUtil::REGULAR_USER_TYPE_ID . '",
	"redirect_page_url" => "",
	"do_not_encrypt_password" => 1,
	"style_type" => "template",
	"block_class" => "register",
	"css" => "",
	"js" => "",
	"show_username" => "1",
	"username_default_value" => "",
	"fields" => array(
		"username" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "username",
				"label" => array(
					"type" => "label",
					"value" => "Username: ",
				),
				"input" => array(
					"type" => "text",
					"allow_null" => "",
					"place_holder" => "your username",
				)
			),
		),
		"password" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "password",
				"label" => array(
					"type" => "label",
					"value" => "Password: ",
				),
				"input" => array(
					"type" => "password",
					"allow_null" => "",
					"place_holder" => "your password",
				)
			),
		),
		"name" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "name",
				"label" => array(
					"type" => "label",
					"value" => "Name: ",
				),
				"input" => array(
					"type" => "text",
					"allow_null" => "",
					"place_holder" => "your name",
				)
			),
		),
		"email" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "email",
				"label" => array(
					"type" => "label",
					"value" => "Email: ",
				),
				"input" => array(
					"type" => "text",
					"allow_null" => 1,
					"place_holder" => "your email",
				)
			),
		),
		"security_question_1" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "security_question_1",
				"label" => array(
					"type" => "label",
					"value" => "Security Question 1: ",
				),
				"input" => array(
					"type" => "select",
					"options" => ' . $security_question_options_code . ',
				)
			),
		),
		"security_answer_1" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "security_answer_1",
				"label" => array(
					"type" => "label",
					"value" => "Security Answer 1: ",
				),
				"input" => array(
					"type" => "text",
					"allow_null" => "",
				)
			),
		),
		"security_question_2" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "security_question_2",
				"label" => array(
					"type" => "label",
					"value" => "Security Question 2: ",
				),
				"input" => array(
					"type" => "select",
					"options" => ' . $security_question_options_code . ',
				)
			),
		),
		"security_answer_2" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "security_answer_2",
				"label" => array(
					"type" => "label",
					"value" => "Security Answer 2: ",
				),
				"input" => array(
					"type" => "text",
					"allow_null" => "",
				)
			),
		),
		"security_question_3" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "security_question_3",
				"label" => array(
					"type" => "label",
					"value" => "Security Question 3: ",
				),
				"input" => array(
					"type" => "select",
					"options" => ' . $security_question_options_code . ',
				)
			),
		),
		"security_answer_3" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "security_answer_3",
				"label" => array(
					"type" => "label",
					"value" => "Security Answer 3: ",
				),
				"input" => array(
					"type" => "text",
					"allow_null" => "",
				)
			),
		),
	),
	"show_password" => 1,
	"password_default_value" => "",
	"show_name" => 1,
	"name_default_value" => "",
	"show_email" => 1,
	"email_default_value" => "",
	"show_security_question_1" => 1,
	"security_question_1_default_value" => "",
	"show_security_answer_1" => 1,
	"security_answer_1_default_value" => "",
	"show_security_question_2" => 1,
	"security_question_2_default_value" => "",
	"show_security_answer_2" => 1,
	"security_answer_2_default_value" => "",
	"show_security_question_3" => 1,
	"security_question_3_default_value" => "",
	"show_security_answer_3" => 1,
	"security_answer_3_default_value" => "",
	"user_environments" => array(),
	"object_to_objects" => array(),
);

$EVC->getCMSLayer()->getCMSBlockLayer()->createBlock("user/register", $block_id, $block_settings[$block_id]);
?>';
					
					$status = CMSPresentationUIAutomaticFilesHandler::saveBlockCode($PEVC, $block_id, $block_code, true, $task_statuses);
				}
			}
			
			self::$register_block_id = $block_id;
		}
		
		if (!self::$register_page_id && self::$register_block_id) {
			$entities_path = $PEVC->getEntitiesPath() . $authentication_files_relative_folder_path;
			$page_id = self::getPageIdByBlockId($PEVC, $entities_path, self::$register_block_id);
			
			if (!$page_id) { //create page
				$page_id = $authentication_files_relative_folder_path . "_register";
				$page_id = preg_replace("/^\/+/", "", $page_id);
				
				if (!$files_creation_type || $files_creation_type == 1) {
					CMSPresentationUIAutomaticFilesHandler::createAndSaveEntityCode($PEVC, $page_id, array(
						"includes" => array(
							array("path" => $vars_file_include_code, "once" => 1),
						),
						"regions_blocks" => array(
							array("region" => "Content", "block" => self::$register_block_id)
						),
						"template_params" => array(
							"Page Title" => "Register"
						),
						"template" => $template,
					), false, $task_statuses);
					
					self::preparePageSaveActionTime($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $page_id, $task_statuses);
				}
			}
			
			self::$register_page_id = $page_id;
		}
		
		self::prepareBlockTableStatus($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $task_id, $table_statuses, self::$register_block_id, $task_statuses);
		self::preparePageTableStatus($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $task_id, $table_statuses, self::$register_page_id, $task_statuses);
		
		return self::$register_page_id;
	}
	
	private static function getOrCreateForgotCredentialsPageId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $template, $task_id, $files_to_create, &$table_statuses) {
		$task_statuses = array();
		
		if (!self::$forgot_credentials_block_id) {
			$blocks_path = $PEVC->getBlocksPath() . $authentication_files_relative_folder_path;
			$block_id = self::getBlockIdByModuleId($PEVC, $blocks_path, "user/forgot_credentials");
			
			if (!$block_id) { //create block
				$block_id = $authentication_files_relative_folder_path . "_forgot_credentials";
				$block_id = preg_replace("/^\/+/", "", $block_id);
				
				if (!$files_creation_type || $files_creation_type == 1) {
					$block_code = '<?php
$block_id = $EVC->getCMSLayer()->getCMSBlockLayer()->getBlockIdFromFilePath(__FILE__);//must be the same than this file name.

$block_settings[$block_id] = array(
	"show_recover_username_through_email" => 0,
	"show_recover_username_through_email_and_security_questions" => 0,
	"show_recover_password_through_email" => 0,
	"show_recover_password_through_security_questions" => 1,
	"admin_email" => "",
	"smtp_host" => "",
	"smtp_port" => "",
	"smtp_secure" => "",
	"smtp_user" => "",
	"smtp_pass" => "",
	"redirect_page_url" => "",
	"username_attribute_label" => "Username",
	"password_attribute_label" => "Password",
	"do_not_encrypt_password" => 1,
	"user_environments" => array(),
	"style_type" => "template",
	"block_class" => "forgot_credentials",
	"css" => "",
	"js" => "",
);

$EVC->getCMSLayer()->getCMSBlockLayer()->createBlock("user/forgot_credentials", $block_id, $block_settings[$block_id]);
?>';
					
					$status = CMSPresentationUIAutomaticFilesHandler::saveBlockCode($PEVC, $block_id, $block_code, true, $task_statuses);
				}
			}
			
			self::$forgot_credentials_block_id = $block_id;
		}
		
		if (!self::$forgot_credentials_page_id && self::$forgot_credentials_block_id) {
			$entities_path = $PEVC->getEntitiesPath() . $authentication_files_relative_folder_path;
			$page_id = self::getPageIdByBlockId($PEVC, $entities_path, self::$forgot_credentials_block_id);
			
			if (!$page_id) { //create page
				$page_id = $authentication_files_relative_folder_path . "_forgot_credentials";
				$page_id = preg_replace("/^\/+/", "", $page_id);
				
				if (!$files_creation_type || $files_creation_type == 1) {
					CMSPresentationUIAutomaticFilesHandler::createAndSaveEntityCode($PEVC, $page_id, array(
						"includes" => array(
							array("path" => $vars_file_include_code, "once" => 1),
						),
						"regions_blocks" => array(
							array("region" => "Content", "block" => self::$forgot_credentials_block_id)
						),
						"template_params" => array(
							"Page Title" => "Forgot Credentials"
						),
						"template" => $template,
					), false, $task_statuses);
					
					self::preparePageSaveActionTime($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $page_id, $task_statuses);
				}
			}
			
			self::$forgot_credentials_page_id = $page_id;
		}
		
		self::prepareBlockTableStatus($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $task_id, $table_statuses, self::$forgot_credentials_block_id, $task_statuses);
		self::preparePageTableStatus($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $task_id, $table_statuses, self::$forgot_credentials_page_id, $task_statuses);
		
		return self::$forgot_credentials_page_id;
	}
	
	private static function getOrCreateEditProfilePageId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $template, $task_id, $files_to_create, &$table_statuses, $parent_page_settings, $parent_block_id) {
		$task_statuses = array();
		
		if (!self::$edit_profile_block_id) {
			$blocks_path = $PEVC->getBlocksPath() . $authentication_files_relative_folder_path;
			$block_id = self::getBlockIdByModuleId($PEVC, $blocks_path, "user/edit_profile");
			
			if (!$block_id) { //create block
				$block_id = $authentication_files_relative_folder_path . "_edit_profile";
				$block_id = preg_replace("/^\/+/", "", $block_id);
				
				if (!$files_creation_type || $files_creation_type == 1) {
					$security_question_options_code = self::getSecurityQuestionOptionsCode();
					
					$block_code = '<?php
$block_id = $EVC->getCMSLayer()->getCMSBlockLayer()->getBlockIdFromFilePath(__FILE__);//must be the same than this file name.

$block_settings[$block_id] = array(
	"do_not_encrypt_password" => 1,
	"show_username" => "1",
	"username_default_value" => "",
	"fields" => array(
		"username" => array(
			"field" => array(
				"class" => "username",
				"label" => array(
					"value" => "Username: ",
				),
				"input" => array(
					"place_holder" => "your username",
					"href" => "",
					"target" => "",
					"src" => "",
					"extra_html" => "",
					"confirmed" => "",
					"confirmmessage" => "",
					"checkallownull" => "",
					"checkmessage" => "",
					"checktype" => "",
					"checktyperegex" => "",
					"checkminlen" => "",
					"checkmaxlen" => "",
					"checkminvalue" => "",
					"checkmaxvalue" => "",
					"checkminwords" => "",
					"checkmaxwords" => "",
				),
			)
		),
		"current_password" => array(
			"field" => array(
				"class" => "current_password",
				"label" => array(
					"value" => "Current Password: ",
				),
				"input" => array(
					"place_holder" => "your current password",
					"href" => "",
					"target" => "",
					"src" => "",
					"extra_html" => "",
					"confirmed" => "",
					"confirmmessage" => "",
					"checkallownull" => "",
					"checkmessage" => "",
					"checktype" => "",
					"checktyperegex" => "",
					"checkminlen" => "",
					"checkmaxlen" => "",
					"checkminvalue" => "",
					"checkmaxvalue" => "",
					"checkminwords" => "",
					"checkmaxwords" => "",
				)
			),
		),
		"password" => array(
			"field" => array(
				"class" => "password",
				"label" => array(
					"value" => "Password: ",
				),
				"input" => array(
					"place_holder" => "your new password",
					"allow_null" => 1,
					"href" => "",
					"target" => "",
					"src" => "",
					"extra_html" => "",
					"confirmed" => "",
					"confirmmessage" => "",
					"checkallownull" => "",
					"checkmessage" => "",
					"checktype" => "",
					"checktyperegex" => "",
					"checkminlen" => "",
					"checkmaxlen" => "",
					"checkminvalue" => "",
					"checkmaxvalue" => "",
					"checkminwords" => "",
					"checkmaxwords" => "",
				)
			),
		),
		"name" => array(
			"field" => array(
				"class" => "name",
				"label" => array(
					"value" => "Name: ",
				),
				"input" => array(
					"place_holder" => "your name",
					"href" => "",
					"target" => "",
					"src" => "",
					"extra_html" => "",
					"confirmed" => "",
					"confirmmessage" => "",
					"checkallownull" => "",
					"checkmessage" => "",
					"checktype" => "",
					"checktyperegex" => "",
					"checkminlen" => "",
					"checkmaxlen" => "",
					"checkminvalue" => "",
					"checkmaxvalue" => "",
					"checkminwords" => "",
					"checkmaxwords" => "",
				)
			),
		),
		"email" => array(
			"field" => array(
				"class" => "email",
				"label" => array(
					"value" => "Email: ",
				),
				"input" => array(
					"place_holder" => "your email",
					"allow_null" => 1,
					"href" => "",
					"target" => "",
					"src" => "",
					"extra_html" => "",
					"confirmed" => "",
					"confirmmessage" => "",
					"checkallownull" => "",
					"checkmessage" => "",
					"checktype" => "",
					"checktyperegex" => "",
					"checkminlen" => "",
					"checkmaxlen" => "",
					"checkminvalue" => "",
					"checkmaxvalue" => "",
					"checkminwords" => "",
					"checkmaxwords" => "",
				)
			),
		),
		"security_question_1" => array(
			"field" => array(
				"class" => "security_question_1",
				"label" => array(
					"value" => "Security Question 1: ",
				),
				"input" => array(
					"href" => "",
					"target" => "",
					"src" => "",
					"extra_html" => "",
					"options" => ' . $security_question_options_code . ',
					"confirmed" => "",
					"confirmmessage" => "",
					"checkallownull" => "",
					"checkmessage" => "",
					"checktype" => "",
					"checktyperegex" => "",
					"checkminlen" => "",
					"checkmaxlen" => "",
					"checkminvalue" => "",
					"checkmaxvalue" => "",
					"checkminwords" => "",
					"checkmaxwords" => "",
				)
			),
		),
		"security_answer_1" => array(
			"field" => array(
				"class" => "security_answer_1",
				"label" => array(
					"value" => "Security Answer 1: ",
				),
				"input" => array(
					"href" => "",
					"target" => "",
					"src" => "",
					"extra_html" => "",
					"confirmed" => "",
					"confirmmessage" => "",
					"checkallownull" => "",
					"checkmessage" => "",
					"checktype" => "",
					"checktyperegex" => "",
					"checkminlen" => "",
					"checkmaxlen" => "",
					"checkminvalue" => "",
					"checkmaxvalue" => "",
					"checkminwords" => "",
					"checkmaxwords" => "",
				)
			),
		),
		"security_question_2" => array(
			"field" => array(
				"class" => "security_question_2",
				"label" => array(
					"value" => "Security Question 2: ",
				),
				"input" => array(
					"href" => "",
					"target" => "",
					"src" => "",
					"extra_html" => "",
					"options" => ' . $security_question_options_code . ',
					"confirmed" => "",
					"confirmmessage" => "",
					"checkallownull" => "",
					"checkmessage" => "",
					"checktype" => "",
					"checktyperegex" => "",
					"checkminlen" => "",
					"checkmaxlen" => "",
					"checkminvalue" => "",
					"checkmaxvalue" => "",
					"checkminwords" => "",
					"checkmaxwords" => "",
				)
			),
		),
		"security_answer_2" => array(
			"field" => array(
				"class" => "security_answer_2",
				"label" => array(
					"value" => "Security Answer 2: ",
				),
				"input" => array(
					"href" => "",
					"target" => "",
					"src" => "",
					"extra_html" => "",
					"confirmed" => "",
					"confirmmessage" => "",
					"checkallownull" => "",
					"checkmessage" => "",
					"checktype" => "",
					"checktyperegex" => "",
					"checkminlen" => "",
					"checkmaxlen" => "",
					"checkminvalue" => "",
					"checkmaxvalue" => "",
					"checkminwords" => "",
					"checkmaxwords" => "",
				)
			),
		),
		"security_question_3" => array(
			"field" => array(
				"class" => "security_question_3",
				"label" => array(
					"value" => "Security Question 3: ",
				),
				"input" => array(
					"href" => "",
					"target" => "",
					"src" => "",
					"extra_html" => "",
					"options" => ' . $security_question_options_code . ',
					"confirmed" => "",
					"confirmmessage" => "",
					"checkallownull" => "",
					"checkmessage" => "",
					"checktype" => "",
					"checktyperegex" => "",
					"checkminlen" => "",
					"checkmaxlen" => "",
					"checkminvalue" => "",
					"checkmaxvalue" => "",
					"checkminwords" => "",
					"checkmaxwords" => "",
				)
			),
		),
		"security_answer_3" => array(
			"field" => array(
				"class" => "security_answer_3",
				"label" => array(
					"value" => "Security Answer 3: ",
				),
				"input" => array(
					"href" => "",
					"target" => "",
					"src" => "",
					"extra_html" => "",
					"confirmed" => "",
					"confirmmessage" => "",
					"checkallownull" => "",
					"checkmessage" => "",
					"checktype" => "",
					"checktyperegex" => "",
					"checkminlen" => "",
					"checkmaxlen" => "",
					"checkminvalue" => "",
					"checkmaxvalue" => "",
					"checkminwords" => "",
					"checkmaxwords" => "",
				)
			),
		),
	),
	"show_current_password" => "1",
	"current_password_default_value" => "",
	"show_password" => "1",
	"password_default_value" => "",
	"show_name" => "1",
	"name_default_value" => "",
	"show_email" => "1",
	"email_default_value" => "",
	"show_security_question_1" => "1",
	"security_question_1_default_value" => "",
	"show_security_answer_1" => "1",
	"security_answer_1_default_value" => "",
	"show_security_question_2" => "1",
	"security_question_2_default_value" => "",
	"show_security_answer_2" => "1",
	"security_answer_2_default_value" => "",
	"show_security_question_3" => "1",
	"security_question_3_default_value" => "",
	"show_security_answer_3" => "1",
	"security_answer_3_default_value" => "",
	"allow_update" => "1",
	"on_update_ok_message" => "",
	"on_update_ok_action" => "show_message",
	"on_update_ok_redirect_url" => "",
	"on_update_error_message" => "",
	"on_update_error_action" => "show_message",
	"on_update_error_redirect_url" => "",
	"on_undefined_object_ok_message" => "",
	"on_undefined_object_ok_action" => "",
	"on_undefined_object_ok_redirect_url" => "",
	"on_undefined_object_error_message" => "",
	"on_undefined_object_error_action" => "show_message_and_stop",
	"on_undefined_object_error_redirect_url" => "",
	"style_type" => "template",
	"block_class" => "edit_profile",
	"css" => "",
	"js" => "",
);

$EVC->getCMSLayer()->getCMSBlockLayer()->createBlock("user/edit_profile", $block_id, $block_settings[$block_id]);
?>';
					
					$status = CMSPresentationUIAutomaticFilesHandler::saveBlockCode($PEVC, $block_id, $block_code, true, $task_statuses);
				}
			}
			
			self::$edit_profile_block_id = $block_id;
		}
		
		if (!self::$edit_profile_page_id && self::$edit_profile_block_id) {
			$entities_path = $PEVC->getEntitiesPath() . $authentication_files_relative_folder_path;
			$page_id = self::getPageIdByBlockId($PEVC, $entities_path, self::$edit_profile_block_id);
			
			if (!$page_id) { //create page
				$page_id = $authentication_files_relative_folder_path . "_edit_profile";
				$page_id = preg_replace("/^\/+/", "", $page_id);
				
				if (!$files_creation_type || $files_creation_type == 1) {
					$page_settings = $parent_page_settings;
					
					if ($vars_file_include_code) {
						$exists = false;
						foreach ($page_settings["includes"] as $inc)
							if (isset($inc["path"]) && strpos($inc["path"], $vars_file_include_code) !== false) {
								$exists = true;
								break;
							}
						
						if (!$exists)
							$page_settings["includes"][] = array("path" => $vars_file_include_code, "once" => 1);
					}
					
					//delete parent block id from regions
					foreach ($page_settings["regions_blocks"] as $k => $rb) {
						$rb_block = isset($rb["block"]) ? $rb["block"] : null;
						
						if ($rb_block == $parent_block_id && empty($rb["project"]))
							unset($page_settings["regions_blocks"][$k]);
					}
					
					$page_settings["template_params"]["Page Title"] = "Edit Profile";
					$page_settings["regions_blocks"][] = array("region" => "Content", "block" => self::$edit_profile_block_id);
					$page_settings["template"] = $template;
					
					CMSPresentationUIAutomaticFilesHandler::createAndSaveEntityCode($PEVC, $page_id, $page_settings, false, $task_statuses);
					
					self::preparePageSaveActionTime($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $page_id, $task_statuses);
				}
			}
			
			self::$edit_profile_page_id = $page_id;
		}
		
		self::prepareBlockTableStatus($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $task_id, $table_statuses, self::$edit_profile_block_id, $task_statuses);
		self::preparePageTableStatus($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $task_id, $table_statuses, self::$edit_profile_page_id, $task_statuses);
		
		return self::$edit_profile_page_id;
	}
	
	private static function getOrCreateListAndEditUsersPageId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $template, $task_id, $files_to_create, &$table_statuses, $parent_page_settings, $parent_block_id) {
		$task_statuses = array();
		
		if (!self::$list_and_edit_users_block_id) {
			$blocks_path = $PEVC->getBlocksPath() . $authentication_files_relative_folder_path;
			$block_id = self::getBlockIdByModuleId($PEVC, $blocks_path, "user/list_and_edit_users_with_user_types");
			
			if (!$block_id) { //create block
				$block_id = $authentication_files_relative_folder_path . "_list_and_edit_users";
				$block_id = preg_replace("/^\/+/", "", $block_id);
				
				if (!$files_creation_type || $files_creation_type == 1) {
					$security_question_options_code = self::getSecurityQuestionOptionsCode();
					
					$block_code = '<?php
$block_id = $EVC->getCMSLayer()->getCMSBlockLayer()->getBlockIdFromFilePath(__FILE__);//must be the same than this file name.

$block_settings[$block_id] = array(
	"query_type" => "all_users",
	"object_type_id" => 1,
	"object_id" => "",
	"group" => "",
	"user_type_id" => 2,
	"do_not_encrypt_password" => 1,
	"style_type" => "template",
	"block_class" => "list_and_edit_users",
	"table_class" => "table-bordered table table-striped table-hover table-sm",
	"rows_class" => "",
	"css" => "",
	"js" => "window.addEventListener(\\"load\\", function() {
    $(\\".module_list.module_list_and_edit_users .list_items .list_container\\").addClass(\\"table-responsive\\");
});

function onListItemFieldKeyPress(elm) {
	\$(elm).parent().closest(\\"tr\\").find(\\" > .selected_item > input\\").prop(\\"checked\\", true);
}

function onListAddUser(elm) {
	if (!new_user_html) {
		alert(\\"Insert action not allowed!\\");
		return false;
	}
	
	var table = \$(elm).parent().closest(\\"table\\");
	var tbody = table.children(\\"tbody\\")[0] ? table.children(\\"tbody\\") : table;
	var new_index = 0;
	
	var inputs = tbody.find(\\"input, textarea, select\\");
	\$.each(inputs, function(idx, input) {
		if ((\\"\\" + input.name).substr(0, 6) == \\"users[\\") {
			var input_index = parseInt(input.name.substr(6, input.name.indexOf(\\"]\\") - 6));
			
			if (input_index > new_index)
				new_index = input_index;
		}
	});
	new_index++;
	
	var new_item = \$(new_user_html.replace(/#idx#/g, new_index)); //new_user_html is a variable that will be created automatically with the correspondent html.
	
	tbody.append(new_item);
	
	if (typeof onNewHtml == \\"function\\") 
		onNewHtml(elm, new_item);
	
	return new_item;
}

function onListRemoveNewUser(elm) {
	if (confirm(\\"Do you wish to remove this user?\\"))
		\$(elm).parent().closest(\\"tr\\").remove();
}

function onSaveMultipleUsers(btn) {
	var tbody = \$(btn).parent().closest(\\".list_items\\").find(\\" > .list_container > table > tbody\\");
	prepareSelectedUsersForAction(tbody);
	
	return true;
}

function onDeleteMultipleUsers(btn, msg) {
	if (!msg || confirm(msg)) {
		var tbody = \$(btn).parent().closest(\\".list_items\\").find(\\" > .list_container > table > tbody\\");
		prepareSelectedUsersForAction(tbody);
		return true;
	}
	
	return false;
}

function prepareSelectedUsersForAction(tbody) {
	if (tbody[0]) {
		var trs = tbody.children(\\"tr\\");
		
		\$.each(trs, function(idx, tr) {
			tr = \$(tr);
			var is_selected = tr.find(\\"td.selected_item input[type=checkbox]\\").is(\\":checked\\");
			var inputs = tr.find(\\"td:not(.selected_item)\\").find(\\"input, select, textarea\\");
			
			\$.each(inputs, function(idy, input) {
				input = \$(input);
				
				if (is_selected) {
					if (input[0].hasAttribute(\\"orig-data-allow-null\\"))
						input.attr(\\"data-allow-null\\", input.attr(\\"orig-data-allow-null\\"));
					
					if (input[0].hasAttribute(\"orig-data-validation-type\"))
						input.attr(\\"data-validation-type\\", input.attr(\\"orig-data-validation-type\\"));
				}
				else {
					if (input[0].hasAttribute(\\"data-allow-null\\")) {
						input.attr(\\"orig-data-allow-null\\", input.attr(\\"data-allow-null\\"));
						input.removeAttr(\\"data-allow-null\\");
					}
					
					if (input[0].hasAttribute(\\"data-validation-type\\")) {
						input.attr(\\"orig-data-validation-type\\", input.attr(\\"data-validation-type\\"));
						input.removeAttr(\\"data-validation-type\\");
					}
				}
			});
		});
	}
}
	 	",
	"fields" => array(
		"selected_item" => array(
			"field" => array(
				"class" => "selected_item",
				"label" => array(
					"type" => "label",
					"value" => "",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "<span class=\\"glyphicon glyphicon-plus icon add\\" onClick=\\"onListAddUser(this)\\">Add</span>",
				),
				"input" => array(
					"type" => "checkbox",
					"class" => "",
					"value" => "",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => 1,
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"user_type_ids" => array(
			"field" => array(
				"class" => "user_type_ids",
				"label" => array(
					"type" => "label",
					"value" => "User Types",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "select",
					"class" => "",
					"value" => "#[\\$idx][user_type_ids]#",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"extra_attributes" => array(' . /*'
						array(
							"name" => "multiple",
							"value" => "multiple",
						),' . */'
						array(
							"name" => "onChange",
							"value" => "onListItemFieldKeyPress(this)",
						),
					),
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => 1,
					"allow_javascript" => "",
					"validation_label" => "User Types",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"user_id" => array(
			"field" => array(
				"class" => "user_id",
				"label" => array(
					"type" => "label",
					"value" => "User Id",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "label",
					"class" => "",
					"value" => "#[\\$idx][user_id]#",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "User Id",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"username" => array(
			"field" => array(
				"class" => "username",
				"label" => array(
					"type" => "label",
					"value" => "Username",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "text",
					"class" => "",
					"value" => "#[\\$idx][username]#",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"extra_attributes" => array(
						array(
							"name" => "onKeyPress",
							"value" => "onListItemFieldKeyPress(this)",
						),
						array(
							"name" => "onChange",
							"value" => "onListItemFieldKeyPress(this)",
						),
					),
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "Username",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"password" => array(
			"field" => array(
				"class" => "password",
				"label" => array(
					"type" => "label",
					"value" => "Password",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "password",
					"class" => "",
					"value" => "",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"extra_attributes" => array(
						array(
							"name" => "onKeyPress",
							"value" => "onListItemFieldKeyPress(this)",
						),
						array(
							"name" => "onChange",
							"value" => "onListItemFieldKeyPress(this)",
						),
					),
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => 1,
					"allow_javascript" => "",
					"validation_label" => "Password",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"name" => array(
			"field" => array(
				"class" => "name",
				"label" => array(
					"type" => "label",
					"value" => "Name",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "text",
					"class" => "",
					"value" => "#[\\$idx][name]#",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"extra_attributes" => array(
						array(
							"name" => "onKeyPress",
							"value" => "onListItemFieldKeyPress(this)",
						),
						array(
							"name" => "onChange",
							"value" => "onListItemFieldKeyPress(this)",
						),
					),
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "Name",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"email" => array(
			"field" => array(
				"class" => "email",
				"label" => array(
					"type" => "label",
					"value" => "Email",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "email",
					"class" => "",
					"value" => "#[\\$idx][email]#",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"extra_attributes" => array(
						array(
							"name" => "onKeyPress",
							"value" => "onListItemFieldKeyPress(this)",
						),
						array(
							"name" => "onChange",
							"value" => "onListItemFieldKeyPress(this)",
						),
					),
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => 1,
					"allow_javascript" => "",
					"validation_label" => "Email",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"security_question_1" => array(
			"field" => array(
				"class" => "security_question_1",
				"label" => array(
					"type" => "label",
					"value" => "Security Question 1",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "select",
					"class" => "",
					"value" => "#[\\$idx][security_question_1]#",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"options" => ' . $security_question_options_code . ',
					"extra_attributes" => array(
						array(
							"name" => "onChange",
							"value" => "onListItemFieldKeyPress(this)",
						),
					),
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => 1,
					"allow_javascript" => "",
					"validation_label" => "Security Question 1",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"security_answer_1" => array(
			"field" => array(
				"class" => "security_answer_1",
				"label" => array(
					"type" => "label",
					"value" => "Security Answer 1",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "text",
					"class" => "",
					"value" => "#[\\$idx][security_answer_1]#",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"extra_attributes" => array(
						array(
							"name" => "onKeyPress",
							"value" => "onListItemFieldKeyPress(this)",
						),
						array(
							"name" => "onChange",
							"value" => "onListItemFieldKeyPress(this)",
						),
					),
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => 1,
					"allow_javascript" => "",
					"validation_label" => "Security Answer 1",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"security_question_2" => array(
			"field" => array(
				"class" => "security_question_2",
				"label" => array(
					"type" => "label",
					"value" => "Security Question 2",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "select",
					"class" => "",
					"value" => "#[\\$idx][security_question_2]#",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"options" => ' . $security_question_options_code . ',
					"extra_attributes" => array(
						array(
							"name" => "onChange",
							"value" => "onListItemFieldKeyPress(this)",
						),
					),
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => 1,
					"allow_javascript" => "",
					"validation_label" => "Security Question 2",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"security_answer_2" => array(
			"field" => array(
				"class" => "security_answer_2",
				"label" => array(
					"type" => "label",
					"value" => "Security Answer 2",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "text",
					"class" => "",
					"value" => "#[\\$idx][security_answer_2]#",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"extra_attributes" => array(
						array(
							"name" => "onKeyPress",
							"value" => "onListItemFieldKeyPress(this)",
						),
						array(
							"name" => "onChange",
							"value" => "onListItemFieldKeyPress(this)",
						),
					),
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => 1,
					"allow_javascript" => "",
					"validation_label" => "Security Answer 2",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"security_question_3" => array(
			"field" => array(
				"class" => "security_question_3",
				"label" => array(
					"type" => "label",
					"value" => "Security Question 3",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "select",
					"class" => "",
					"value" => "#[\\$idx][security_question_3]#",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"options" => ' . $security_question_options_code . ',
					"extra_attributes" => array(
						array(
							"name" => "onChange",
							"value" => "onListItemFieldKeyPress(this)",
						),
					),
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => 1,
					"allow_javascript" => "",
					"validation_label" => "Security Question 3",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"security_answer_3" => array(
			"field" => array(
				"class" => "security_answer_3",
				"label" => array(
					"type" => "label",
					"value" => "Security Answer 3",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "text",
					"class" => "",
					"value" => "#[\\$idx][security_answer_3]#",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"extra_attributes" => array(
						array(
							"name" => "onKeyPress",
							"value" => "onListItemFieldKeyPress(this)",
						),
						array(
							"name" => "onChange",
							"value" => "onListItemFieldKeyPress(this)",
						),
					),
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => 1,
					"allow_javascript" => "",
					"validation_label" => "Security Answer 3",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"created_date" => array(
			"field" => array(
				"class" => "created_date",
				"label" => array(
					"type" => "label",
					"value" => "Created Date",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "datetime",
					"class" => "",
					"value" => "#[\\$idx][created_date]#",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"extra_attributes" => array(
						array(
							"name" => "onKeyPress",
							"value" => "onListItemFieldKeyPress(this)",
						),
						array(
							"name" => "onChange",
							"value" => "onListItemFieldKeyPress(this)",
						),
					),
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => 1,
					"allow_javascript" => "",
					"validation_label" => "Created Date",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"modified_date" => array(
			"field" => array(
				"class" => "modified_date",
				"label" => array(
					"type" => "label",
					"value" => "Modified Date",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "datetime",
					"class" => "",
					"value" => "#[\\$idx][modified_date]#",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"extra_attributes" => array(
						array(
							"name" => "onKeyPress",
							"value" => "onListItemFieldKeyPress(this)",
						),
						array(
							"name" => "onChange",
							"value" => "onListItemFieldKeyPress(this)",
						),
					),
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => 1,
					"allow_javascript" => "",
					"validation_label" => "Modified Date",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
	),
	"show_user_id" => 1,
	"user_id_search_value" => "",
	"show_username" => 1,
	"username_search_value" => "",
	"show_password" => 0,
	"password_search_value" => "",
	"show_name" => 1,
	"name_search_value" => "",
	"show_email" => 1,
	"email_search_value" => "",
	"show_security_question_1" => 0,
	"security_question_1_search_value" => "",
	"show_security_answer_1" => 0,
	"security_answer_1_search_value" => "",
	"show_security_question_2" => 0,
	"security_question_2_search_value" => "",
	"show_security_answer_2" => 0,
	"security_answer_2_search_value" => "",
	"show_security_question_3" => 0,
	"security_question_3_search_value" => "",
	"show_security_answer_3" => 0,
	"security_answer_3_search_value" => "",
	"show_created_date" => 0,
	"created_date_search_value" => "",
	"show_modified_date" => 0,
	"modified_date_search_value" => "",
	"show_user_type_ids" => 1,
	"user_type_ids_search_value" => "",
	"allow_insertion" => 1,
	"on_insert_ok_message" => "Users saved successfully.",
	"on_insert_ok_action" => "show_message",
	"on_insert_ok_redirect_url" => "",
	"on_insert_error_message" => "Error: Users not saved successfully!",
	"on_insert_error_action" => "show_message",
	"on_insert_error_redirect_url" => "",
	"buttons" => array(
		"insert" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "button_save submit_button",
				"label" => array(
					"type" => "label",
					"value" => "",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "submit",
					"class" => "",
					"value" => "Add",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"extra_attributes" => array(
						array(
							"name" => "onClick",
							"value" => "return onSaveMultipleUsers(this);",
						),
					),
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => 1,
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"update" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "button_save submit_button",
				"label" => array(
					"type" => "label",
					"value" => "",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "submit",
					"class" => "",
					"value" => "Save",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"extra_attributes" => array(
						array(
							"name" => "onClick",
							"value" => "return onSaveMultipleUsers(this);",
						),
					),
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => 1,
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"delete" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "button_delete submit_button",
				"label" => array(
					"type" => "label",
					"value" => "",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "submit",
					"class" => "",
					"value" => "Delete",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"extra_attributes" => array(
						array(
							"name" => "onClick",
							"value" => "return onDeleteMultipleUsers(this, \'" . translateProjectText($EVC, \'Do you wish to delete this item?\') . "\');",
						),
					),
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => 1,
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
	),
	"allow_update" => 1,
	"on_update_ok_message" => "Users saved successfully.",
	"on_update_ok_action" => "show_message",
	"on_update_ok_redirect_url" => "",
	"on_update_error_message" => "Error: Users not saved successfully!",
	"on_update_error_action" => "show_message",
	"on_update_error_redirect_url" => "",
	"allow_deletion" => 1,
	"on_delete_ok_message" => "Users deleted successfully.",
	"on_delete_ok_action" => "show_message",
	"on_delete_ok_redirect_url" => "",
	"on_delete_error_message" => "Error: Users not deleted successfully!",
	"on_delete_error_action" => "show_message",
	"on_delete_error_redirect_url" => "",
);

$EVC->getCMSLayer()->getCMSBlockLayer()->createBlock("user/list_and_edit_users_with_user_types", $block_id, $block_settings[$block_id]);
?>';
					
					$status = CMSPresentationUIAutomaticFilesHandler::saveBlockCode($PEVC, $block_id, $block_code, true, $task_statuses);
				}
			}
			
			self::$list_and_edit_users_block_id = $block_id;
		}
		
		if (!self::$list_and_edit_users_page_id && self::$list_and_edit_users_block_id) {
			$entities_path = $PEVC->getEntitiesPath() . $authentication_files_relative_folder_path;
			$page_id = self::getPageIdByBlockId($PEVC, $entities_path, self::$list_and_edit_users_block_id);
			
			if (!$page_id) { //create page
				$page_id = $authentication_files_relative_folder_path . "_list_and_edit_users";
				$page_id = preg_replace("/^\/+/", "", $page_id);
				
				if (!$files_creation_type || $files_creation_type == 1) {
					$page_settings = $parent_page_settings;
					
					if ($vars_file_include_code) {
						$exists = false;
						foreach ($page_settings["includes"] as $inc)
							if (isset($inc["path"]) && strpos($inc["path"], $vars_file_include_code) !== false) {
								$exists = true;
								break;
							}
						
						if (!$exists)
							$page_settings["includes"][] = array("path" => $vars_file_include_code, "once" => 1);
					}
					
					//delete parent block id from regions
					foreach ($page_settings["regions_blocks"] as $k => $rb) {
						$rb_block = isset($rb["block"]) ? $rb["block"] : null;
						
						if ($rb_block == $parent_block_id && empty($rb["project"]))
							unset($page_settings["regions_blocks"][$k]);
					}
					
					$page_settings["template_params"]["Page Title"] = "List and Edit Users";
					$page_settings["regions_blocks"][] = array("region" => "Content", "block" => self::$list_and_edit_users_block_id);
					$page_settings["template"] = $template;
					
					CMSPresentationUIAutomaticFilesHandler::createAndSaveEntityCode($PEVC, $page_id, $page_settings, false, $task_statuses);
					
					self::preparePageSaveActionTime($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $page_id, $task_statuses);
				}
			}
			
			self::$list_and_edit_users_page_id = $page_id;
		}
		
		self::prepareBlockTableStatus($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $task_id, $table_statuses, self::$list_and_edit_users_block_id, $task_statuses);
		self::preparePageTableStatus($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $task_id, $table_statuses, self::$list_and_edit_users_page_id, $task_statuses);
		
		return self::$list_and_edit_users_page_id;
	}
	
	private static function getOrCreateListUsersPageId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $template, $task_id, $files_to_create, &$table_statuses, $parent_page_settings, $parent_block_id) {
		$task_statuses = array();
		
		if (!self::$list_users_block_id) {
			$blocks_path = $PEVC->getBlocksPath() . $authentication_files_relative_folder_path;
			$block_id = self::getBlockIdByModuleId($PEVC, $blocks_path, "user/list_users");
			
			if (!$block_id) { //create block
				$block_id = $authentication_files_relative_folder_path . "_list_users";
				$block_id = preg_replace("/^\/+/", "", $block_id);
				
				if (!$files_creation_type || $files_creation_type == 1) {
					$edit_user_page_id = self::getOrCreateEditUserPageId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $template, $task_id, $files_to_create, $table_statuses, $parent_page_settings, $parent_block_id);
					$edit_user_page_url = $edit_user_page_id ? '{$project_url_prefix}' . $edit_user_page_id : '';
					
					$add_user_page_id = self::getOrCreateAddUserPageId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $template, $task_id, $files_to_create, $table_statuses, $parent_page_settings, $parent_block_id);
					$add_user_page_url = $add_user_page_id ? '{$project_url_prefix}' . $add_user_page_id : '';
					
					$block_code = '<?php
$block_id = $EVC->getCMSLayer()->getCMSBlockLayer()->getBlockIdFromFilePath(__FILE__);//must be the same than this file name.

$block_settings[$block_id] = array(
	"query_type" => "all_users",
	"object_type_id" => 1,
	"object_id" => "",
	"group" => "",
	"user_type_id" => 2,
	"style_type" => "template",
	"block_class" => "list_users",
	"table_class" => "table-bordered table table-striped table-hover table-sm",
	"rows_class" => "",
	"css" => "",
	"js" => "window.addEventListener(\\"load\\", function() {
    var list_items = $(\\".module_list.module_list_users .list_items\\").first();
    ' . ($add_user_page_url ? 'list_items.prepend(\'<div class=\\"buttons mb-4 text-right\\"><div class=\\"button\\"><a class=\\"btn btn-primary\\" href=\\"' . $add_user_page_url . '\\">Add User</a></div></div>\');' : '') . '
    list_items.children(\\".list_container\\").addClass(\\"table-responsive\\");
});",
	"show_user_id" => 1,
	"user_id_search_value" => "",
	"fields" => array(
		"user_id" => array(
			"field" => array(
				"class" => "user_id",
				"label" => array(
					"type" => "label",
					"value" => "User Id",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "label",
					"class" => "",
					"value" => "#[\\$idx][user_id]#",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"username" => array(
			"field" => array(
				"class" => "username",
				"label" => array(
					"type" => "label",
					"value" => "Username",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "label",
					"class" => "",
					"value" => "#[\\$idx][username]#",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"password" => array(
			"field" => array(
				"class" => "password",
				"label" => array(
					"type" => "label",
					"value" => "Password",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "label",
					"class" => "",
					"value" => "#[\\$idx][password]#",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"name" => array(
			"field" => array(
				"class" => "name",
				"label" => array(
					"type" => "label",
					"value" => "Name",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "label",
					"class" => "",
					"value" => "#[\\$idx][name]#",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"email" => array(
			"field" => array(
				"class" => "email",
				"label" => array(
					"type" => "label",
					"value" => "Email",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "label",
					"class" => "",
					"value" => "#[\\$idx][email]#",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => 1,
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"security_question_1" => array(
			"field" => array(
				"class" => "security_question_1",
				"label" => array(
					"type" => "label",
					"value" => "Security Question 1",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "label",
					"class" => "",
					"value" => "#[\\$idx][security_question_1]#",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"security_answer_1" => array(
			"field" => array(
				"class" => "security_answer_1",
				"label" => array(
					"type" => "label",
					"value" => "Security Answer 1",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "label",
					"class" => "",
					"value" => "#[\\$idx][security_answer_1]#",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"security_question_2" => array(
			"field" => array(
				"class" => "security_question_2",
				"label" => array(
					"type" => "label",
					"value" => "Security Question 2",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "label",
					"class" => "",
					"value" => "#[\\$idx][security_question_2]#",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"security_answer_2" => array(
			"field" => array(
				"class" => "security_answer_2",
				"label" => array(
					"type" => "label",
					"value" => "Security Answer 2",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "label",
					"class" => "",
					"value" => "#[\\$idx][security_answer_2]#",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"security_question_3" => array(
			"field" => array(
				"class" => "security_question_3",
				"label" => array(
					"type" => "label",
					"value" => "Security Question 3",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "label",
					"class" => "",
					"value" => "#[\\$idx][security_question_3]#",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"security_answer_3" => array(
			"field" => array(
				"class" => "security_answer_3",
				"label" => array(
					"type" => "label",
					"value" => "Security Answer 3",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "label",
					"class" => "",
					"value" => "#[\\$idx][security_answer_3]#",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"created_date" => array(
			"field" => array(
				"class" => "created_date",
				"label" => array(
					"type" => "label",
					"value" => "Created Date",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "label",
					"class" => "",
					"value" => "#[\\$idx][created_date]#",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"modified_date" => array(
			"field" => array(
				"class" => "modified_date",
				"label" => array(
					"type" => "label",
					"value" => "Modified Date",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "label",
					"class" => "",
					"value" => "#[\$idx][modified_date]#",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
	),
	"show_username" => 1,
	"username_search_value" => "",
	"show_password" => 0,
	"password_search_value" => "",
	"show_name" => 1,
	"name_search_value" => "",
	"show_email" => 1,
	"email_search_value" => "",
	"show_security_question_1" => 0,
	"security_question_1_search_value" => "",
	"show_security_answer_1" => 0,
	"security_answer_1_search_value" => "",
	"show_security_question_2" => 0,
	"security_question_2_search_value" => "",
	"show_security_answer_2" => 0,
	"security_answer_2_search_value" => "",
	"show_security_question_3" => 0,
	"security_question_3_search_value" => "",
	"show_security_answer_3" => 0,
	"security_answer_3_search_value" => "",
	"show_created_date" => 0,
	"created_date_search_value" => "",
	"show_modified_date" => 0,
	"modified_date_search_value" => "",
	"show_edit_button" => 1,
	"edit_page_url" => "' . $edit_user_page_url . '",
	"show_delete_button" => "",
);

$EVC->getCMSLayer()->getCMSBlockLayer()->createBlock("user/list_users", $block_id, $block_settings[$block_id]);
?>';
					
					$status = CMSPresentationUIAutomaticFilesHandler::saveBlockCode($PEVC, $block_id, $block_code, true, $task_statuses);
				}
			}
			
			self::$list_users_block_id = $block_id;
		}
		
		if (!self::$list_users_page_id && self::$list_users_block_id) {
			$entities_path = $PEVC->getEntitiesPath() . $authentication_files_relative_folder_path;
			$page_id = self::getPageIdByBlockId($PEVC, $entities_path, self::$list_users_block_id);
			
			if (!$page_id) { //create page
				$page_id = $authentication_files_relative_folder_path . "_list_users";
				$page_id = preg_replace("/^\/+/", "", $page_id);
				
				if (!$files_creation_type || $files_creation_type == 1) {
					$page_settings = $parent_page_settings;
					
					if ($vars_file_include_code) {
						$exists = false;
						foreach ($page_settings["includes"] as $inc)
							if (isset($inc["path"]) && strpos($inc["path"], $vars_file_include_code) !== false) {
								$exists = true;
								break;
							}
						
						if (!$exists)
							$page_settings["includes"][] = array("path" => $vars_file_include_code, "once" => 1);
					}
					
					//delete parent block id from regions
					foreach ($page_settings["regions_blocks"] as $k => $rb) {
						$rb_block = isset($rb["block"]) ? $rb["block"] : null;
						
						if ($rb_block == $parent_block_id && empty($rb["project"]))
							unset($page_settings["regions_blocks"][$k]);
					}
					
					$page_settings["template_params"]["Page Title"] = "List Users";
					$page_settings["regions_blocks"][] = array("region" => "Content", "block" => self::$list_users_block_id);
					$page_settings["template"] = $template;
					
					CMSPresentationUIAutomaticFilesHandler::createAndSaveEntityCode($PEVC, $page_id, $page_settings, false, $task_statuses);
					
					self::preparePageSaveActionTime($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $page_id, $task_statuses);
				}
			}
			
			self::$list_users_page_id = $page_id;
		}
		
		self::prepareBlockTableStatus($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $task_id, $table_statuses, self::$list_users_block_id, $task_statuses);
		self::preparePageTableStatus($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $task_id, $table_statuses, self::$list_users_page_id, $task_statuses);
		
		return self::$list_users_page_id;
	}
	
	private static function getOrCreateEditUserPageId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $template, $task_id, $files_to_create, &$table_statuses, $parent_page_settings, $parent_block_id) {
		$task_statuses = array();
		
		if (!self::$edit_user_block_id) {
			$blocks_path = $PEVC->getBlocksPath() . $authentication_files_relative_folder_path;
			$block_id = self::getBlockIdByModuleId($PEVC, $blocks_path, "user/edit_user", "/\"allow_update\"(\s*)=>(\s*)([1-9]+|true)/i");
			
			if (!$block_id) { //create block
				$block_id = $authentication_files_relative_folder_path . "_edit_user";
				$block_id = preg_replace("/^\/+/", "", $block_id);
				
				if (!$files_creation_type || $files_creation_type == 1) {
					$security_question_options_code = self::getSecurityQuestionOptionsCode();
					
					$block_code = '<?php
$block_id = $EVC->getCMSLayer()->getCMSBlockLayer()->getBlockIdFromFilePath(__FILE__);//must be the same than this file name.

$block_settings[$block_id] = array(
	"do_not_encrypt_password" => 1,
	"style_type" => "template",
	"block_class" => "edit_user",
	"css" => "",
	"js" => "window.addEventListener(\\"load\\", function() {
    var user_id_elm = $(\\".module_edit.module_edit_user .form_field.user_id.form-group\\");
    user_id_elm.children(\\"label\\").addClass(\\"col-12 col-sm-4 col-lg-2\\");
    user_id_elm.children(\\"span\\").addClass(\\"col-12 col-sm-8 col-lg-10 form-control\\");
});",
	"show_user_id" => 1,
	"user_id_default_value" => "",
	"fields" => array(
		"user_id" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "user_id",
				"label" => array(
					"type" => "label",
					"value" => "User Id: ",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "text",
					"class" => "",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "bigint",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"user_type_id" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "user_type_id",
				"label" => array(
					"type" => "label",
					"value" => "User Type Id: ",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "text",
					"class" => "",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "bigint",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"username" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "username",
				"label" => array(
					"type" => "label",
					"value" => "Username: ",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "text",
					"class" => "",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"password" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "password",
				"label" => array(
					"type" => "label",
					"value" => "Password: ",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "password",
					"class" => "",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => 1,
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"name" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "name",
				"label" => array(
					"type" => "label",
					"value" => "Name: ",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "text",
					"class" => "",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"email" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "email",
				"label" => array(
					"type" => "label",
					"value" => "Email: ",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "text",
					"class" => "",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => 1,
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "Invalid Email format.",
					"validation_type" => "email",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"security_question_1" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "security_question_1",
				"label" => array(
					"type" => "label",
					"value" => "Security Question 1: ",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "select",
					"class" => "",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"options" => ' . $security_question_options_code . ',
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"security_answer_1" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "security_answer_1",
				"label" => array(
					"type" => "label",
					"value" => "Security Answer 1: ",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "text",
					"class" => "",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"security_question_2" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "security_question_2",
				"label" => array(
					"type" => "label",
					"value" => "Security Question 2: ",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "select",
					"class" => "",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"options" => ' . $security_question_options_code . ',
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"security_answer_2" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "security_answer_2",
				"label" => array(
					"type" => "label",
					"value" => "Security Answer 2: ",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "text",
					"class" => "",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"security_question_3" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "security_question_3",
				"label" => array(
					"type" => "label",
					"value" => "Security Question 3: ",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "select",
					"class" => "",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"options" => ' . $security_question_options_code . ',
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"security_answer_3" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "security_answer_3",
				"label" => array(
					"type" => "label",
					"value" => "Security Answer 3: ",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "text",
					"class" => "",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
	),
	"show_user_type_id" => 1,
	"user_type_id_default_value" => "",
	"show_username" => 1,
	"username_default_value" => "",
	"show_password" => 1,
	"password_default_value" => "",
	"show_name" => 1,
	"name_default_value" => "",
	"show_email" => 1,
	"email_default_value" => "",
	"show_security_question_1" => 1,
	"security_question_1_default_value" => "",
	"show_security_answer_1" => 1,
	"security_answer_1_default_value" => "",
	"show_security_question_2" => 1,
	"security_question_2_default_value" => "",
	"show_security_answer_2" => 1,
	"security_answer_2_default_value" => "",
	"show_security_question_3" => 1,
	"security_question_3_default_value" => "",
	"show_security_answer_3" => 1,
	"security_answer_3_default_value" => "",
	"allow_view" => 1,
	"allow_insertion" => 0,
	"on_insert_ok_message" => "Users saved successfully.",
	"on_insert_ok_action" => "show_message_and_stop",
	"on_insert_ok_redirect_url" => "",
	"on_insert_error_message" => "Error: Users not saved successfully!",
	"on_insert_error_action" => "show_message",
	"on_insert_error_redirect_url" => "",
	"buttons" => array(
		"insert" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "button_save submit_button",
				"label" => array(
					"type" => "label",
					"value" => "",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "submit",
					"class" => "",
					"value" => "Add",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => 1,
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"update" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "button_save submit_button",
				"label" => array(
					"type" => "label",
					"value" => "",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "submit",
					"class" => "",
					"value" => "Save",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => 1,
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"delete" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "button_delete submit_button",
				"label" => array(
					"type" => "label",
					"value" => "",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "submit",
					"class" => "",
					"value" => "Delete",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"extra_attributes" => array(
						array(
							"name" => "onClick",
							"value" => "return confirm(\'" . translateProjectText($EVC, \'Do you wish to delete this item?\') . "\');",
						),
					),
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => 1,
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
	),
	"allow_update" => 1,
	"on_update_ok_message" => "Users saved successfully.",
	"on_update_ok_action" => "show_message",
	"on_update_ok_redirect_url" => "",
	"on_update_error_message" => "Error: Users not saved successfully!",
	"on_update_error_action" => "show_message",
	"on_update_error_redirect_url" => "",
	"allow_deletion" => 1,
	"on_delete_ok_message" => "Users deleted successfully.",
	"on_delete_ok_action" => "show_message_and_stop",
	"on_delete_ok_redirect_url" => "",
	"on_delete_error_message" => "Error: Users not deleted successfully!",
	"on_delete_error_action" => "show_message",
	"on_delete_error_redirect_url" => "",
	"on_undefined_object_ok_message" => "",
	"on_undefined_object_ok_action" => "",
	"on_undefined_object_ok_redirect_url" => "",
	"on_undefined_object_error_message" => "",
	"on_undefined_object_error_action" => "show_message_and_stop",
	"on_undefined_object_error_redirect_url" => "",
	"user_environments" => array(
		"",
	),
	"object_to_objects" => array(
		array(
			"object_type_id" => "",
			"object_id" => "",
			"group" => "",
		),
	),
);

$EVC->getCMSLayer()->getCMSBlockLayer()->createBlock("user/edit_user", $block_id, $block_settings[$block_id]);
?>';
					
					$status = CMSPresentationUIAutomaticFilesHandler::saveBlockCode($PEVC, $block_id, $block_code, true, $task_statuses);
				}
			}
			
			self::$edit_user_block_id = $block_id;
		}
		
		if (!self::$edit_user_page_id && self::$edit_user_block_id) {
			$entities_path = $PEVC->getEntitiesPath() . $authentication_files_relative_folder_path;
			$page_id = self::getPageIdByBlockId($PEVC, $entities_path, self::$edit_user_block_id);
			
			if (!$page_id) { //create page
				$page_id = $authentication_files_relative_folder_path . "_edit_user";
				$page_id = preg_replace("/^\/+/", "", $page_id);
				
				if (!$files_creation_type || $files_creation_type == 1) {
					$page_settings = $parent_page_settings;
					
					if ($vars_file_include_code) {
						$exists = false;
						foreach ($page_settings["includes"] as $inc)
							if (isset($inc["path"]) && strpos($inc["path"], $vars_file_include_code) !== false) {
								$exists = true;
								break;
							}
						
						if (!$exists)
							$page_settings["includes"][] = array("path" => $vars_file_include_code, "once" => 1);
					}
					
					//delete parent block id from regions
					foreach ($page_settings["regions_blocks"] as $k => $rb) {
						$rb_block = isset($rb["block"]) ? $rb["block"] : null;
						
						if ($rb_block == $parent_block_id && empty($rb["project"]))
							unset($page_settings["regions_blocks"][$k]);
					}
					
					$page_settings["template_params"]["Page Title"] = "Edit User";
					$page_settings["regions_blocks"][] = array("region" => "Content", "block" => self::$edit_user_block_id);
					$page_settings["template"] = $template;
					
					CMSPresentationUIAutomaticFilesHandler::createAndSaveEntityCode($PEVC, $page_id, $page_settings, false, $task_statuses);
					
					self::preparePageSaveActionTime($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $page_id, $task_statuses);
				}
			}
			
			self::$edit_user_page_id = $page_id;
		}
		
		self::prepareBlockTableStatus($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $task_id, $table_statuses, self::$edit_user_block_id, $task_statuses);
		self::preparePageTableStatus($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $task_id, $table_statuses, self::$edit_user_page_id, $task_statuses);
		
		return self::$edit_user_page_id;
	}
	
	private static function getOrCreateAddUserPageId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $template, $task_id, $files_to_create, &$table_statuses, $parent_page_settings, $parent_block_id) {
		$task_statuses = array();
		
		if (!self::$add_user_block_id) {
			$blocks_path = $PEVC->getBlocksPath() . $authentication_files_relative_folder_path;
			$block_id = self::getBlockIdByModuleId($PEVC, $blocks_path, "user/add_user", "/\"allow_insertion\"(\s*)=>(\s*)([1-9]+|true)/i");
			
			if (!$block_id) { //create block
				$block_id = $authentication_files_relative_folder_path . "_add_user";
				$block_id = preg_replace("/^\/+/", "", $block_id);
				
				if (!$files_creation_type || $files_creation_type == 1) {
					$security_question_options_code = self::getSecurityQuestionOptionsCode();
					
					/* DO NOT CALL THE getOrCreateListUsersPageId HERE OTHERWISE WE WILL HAVE AN INFINITY LOOP
					 * 	$list_users_page_id = self::getOrCreateListUsersPageId($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $vars_file_include_code, $authentication_files_relative_folder_path, $template, $task_id, $files_to_create, $table_statuses, $parent_page_settings, $parent_block_id);
					 *	$list_users_page_url = $list_users_page_id ? '{$project_url_prefix}' . $list_users_page_id : '';
					 * then add this var to the "on_insert_ok_redirect_url" attribute. => DO NOT DO THIS OTHERWISE WE WILL HAVE AN INFINITY LOOP
					 */
					
					$block_code = '<?php
$block_id = $EVC->getCMSLayer()->getCMSBlockLayer()->getBlockIdFromFilePath(__FILE__);//must be the same than this file name.

$block_settings[$block_id] = array(
	"do_not_encrypt_password" => 1,
	"style_type" => "template",
	"block_class" => "add_user",
	"css" => "",
	"js" => "",
	"show_user_id" => 0,
	"user_id_default_value" => "",
	"fields" => array(
		"user_id" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "user_id",
				"label" => array(
					"type" => "label",
					"value" => "User Id: ",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "text",
					"class" => "",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "bigint",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"user_type_id" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "user_type_id",
				"label" => array(
					"type" => "label",
					"value" => "User Type Id: ",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "text",
					"class" => "",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "bigint",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"username" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "username",
				"label" => array(
					"type" => "label",
					"value" => "Username: ",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "text",
					"class" => "",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"password" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "password",
				"label" => array(
					"type" => "label",
					"value" => "Password: ",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "password",
					"class" => "",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => 1,
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"name" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "name",
				"label" => array(
					"type" => "label",
					"value" => "Name: ",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "text",
					"class" => "",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"email" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "email",
				"label" => array(
					"type" => "label",
					"value" => "Email: ",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "text",
					"class" => "",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => 1,
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "Invalid Email format.",
					"validation_type" => "email",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"security_question_1" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "security_question_1",
				"label" => array(
					"type" => "label",
					"value" => "Security Question 1: ",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "select",
					"class" => "",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"options" => ' . $security_question_options_code . ',
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"security_answer_1" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "security_answer_1",
				"label" => array(
					"type" => "label",
					"value" => "Security Answer 1: ",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "text",
					"class" => "",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"security_question_2" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "security_question_2",
				"label" => array(
					"type" => "label",
					"value" => "Security Question 2: ",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "select",
					"class" => "",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"options" => ' . $security_question_options_code . ',
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"security_answer_2" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "security_answer_2",
				"label" => array(
					"type" => "label",
					"value" => "Security Answer 2: ",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "text",
					"class" => "",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"security_question_3" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "security_question_3",
				"label" => array(
					"type" => "label",
					"value" => "Security Question 3: ",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "select",
					"class" => "",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"options" => ' . $security_question_options_code . ',
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"security_answer_3" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "security_answer_3",
				"label" => array(
					"type" => "label",
					"value" => "Security Answer 3: ",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "text",
					"class" => "",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => "",
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
	),
	"show_user_type_id" => 1,
	"user_type_id_default_value" => "",
	"show_username" => 1,
	"username_default_value" => "",
	"show_password" => 1,
	"password_default_value" => "",
	"show_name" => 1,
	"name_default_value" => "",
	"show_email" => 1,
	"email_default_value" => "",
	"show_security_question_1" => 1,
	"security_question_1_default_value" => "",
	"show_security_answer_1" => 1,
	"security_answer_1_default_value" => "",
	"show_security_question_2" => 1,
	"security_question_2_default_value" => "",
	"show_security_answer_2" => 1,
	"security_answer_2_default_value" => "",
	"show_security_question_3" => 1,
	"security_question_3_default_value" => "",
	"show_security_answer_3" => 1,
	"security_answer_3_default_value" => "",
	"allow_view" => 1,
	"allow_insertion" => 1,
	"on_insert_ok_message" => "Users saved successfully.",
	"on_insert_ok_action" => "show_message_and_stop",
	"on_insert_ok_redirect_url" => "",
	"on_insert_error_message" => "Error: Users not saved successfully!",
	"on_insert_error_action" => "show_message",
	"on_insert_error_redirect_url" => "",
	"buttons" => array(
		"insert" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "button_save submit_button",
				"label" => array(
					"type" => "label",
					"value" => "",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "submit",
					"class" => "",
					"value" => "Add",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => 1,
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"update" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "button_save submit_button",
				"label" => array(
					"type" => "label",
					"value" => "",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "submit",
					"class" => "",
					"value" => "Save",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => 1,
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
		"delete" => array(
			"field" => array(
				"disable_field_group" => "",
				"class" => "button_delete submit_button",
				"label" => array(
					"type" => "label",
					"value" => "",
					"class" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
				),
				"input" => array(
					"type" => "submit",
					"class" => "",
					"value" => "Delete",
					"place_holder" => "",
					"href" => "",
					"target" => "",
					"src" => "",
					"title" => "",
					"previous_html" => "",
					"next_html" => "",
					"extra_attributes" => array(
						array(
							"name" => "onClick",
							"value" => "return confirm(\'" . translateProjectText($EVC, \'Do you wish to delete this item?\') . "\');",
						),
					),
					"confirmation" => "",
					"confirmation_message" => "",
					"allow_null" => 1,
					"allow_javascript" => "",
					"validation_label" => "",
					"validation_message" => "",
					"validation_type" => "",
					"validation_regex" => "",
					"validation_func" => "",
					"min_length" => "",
					"max_length" => "",
					"min_value" => "",
					"max_value" => "",
					"min_words" => "",
					"max_words" => "",
				),
			),
		),
	),
	"allow_update" => 0,
	"on_update_ok_message" => "Users saved successfully.",
	"on_update_ok_action" => "show_message",
	"on_update_ok_redirect_url" => "",
	"on_update_error_message" => "Error: Users not saved successfully!",
	"on_update_error_action" => "show_message",
	"on_update_error_redirect_url" => "",
	"allow_deletion" => 0,
	"on_delete_ok_message" => "Users deleted successfully.",
	"on_delete_ok_action" => "show_message_and_stop",
	"on_delete_ok_redirect_url" => "",
	"on_delete_error_message" => "Error: Users not deleted successfully!",
	"on_delete_error_action" => "show_message",
	"on_delete_error_redirect_url" => "",
	"on_undefined_object_ok_message" => "",
	"on_undefined_object_ok_action" => "",
	"on_undefined_object_ok_redirect_url" => "",
	"on_undefined_object_error_message" => "",
	"on_undefined_object_error_action" => "show_message_and_stop",
	"on_undefined_object_error_redirect_url" => "",
	"user_environments" => array(
		"",
	),
	"object_to_objects" => array(
		array(
			"object_type_id" => "",
			"object_id" => "",
			"group" => "",
		),
	),
);

$EVC->getCMSLayer()->getCMSBlockLayer()->createBlock("user/edit_user", $block_id, $block_settings[$block_id]);
?>';
					
					$status = CMSPresentationUIAutomaticFilesHandler::saveBlockCode($PEVC, $block_id, $block_code, true, $task_statuses);
				}
			}
			
			self::$add_user_block_id = $block_id;
		}
		
		if (!self::$add_user_page_id && self::$add_user_block_id) {
			$entities_path = $PEVC->getEntitiesPath() . $authentication_files_relative_folder_path;
			$page_id = self::getPageIdByBlockId($PEVC, $entities_path, self::$add_user_block_id);
			
			if (!$page_id) { //create page
				$page_id = $authentication_files_relative_folder_path . "_add_user";
				$page_id = preg_replace("/^\/+/", "", $page_id);
				
				if (!$files_creation_type || $files_creation_type == 1) {
					$page_settings = $parent_page_settings;
					
					if ($vars_file_include_code) {
						$exists = false;
						foreach ($page_settings["includes"] as $inc)
							if (isset($inc["path"]) && strpos($inc["path"], $vars_file_include_code) !== false) {
								$exists = true;
								break;
							}
						
						if (!$exists)
							$page_settings["includes"][] = array("path" => $vars_file_include_code, "once" => 1);
					}
					
					//delete parent block id from regions
					foreach ($page_settings["regions_blocks"] as $k => $rb) {
						$rb_block = isset($rb["block"]) ? $rb["block"] : null;
						
						if ($rb_block == $parent_block_id && empty($rb["project"]))
							unset($page_settings["regions_blocks"][$k]);
					}
					
					$page_settings["template_params"]["Page Title"] = "Add User";
					$page_settings["regions_blocks"][] = array("region" => "Content", "block" => self::$add_user_block_id);
					$page_settings["template"] = $template;
					
					CMSPresentationUIAutomaticFilesHandler::createAndSaveEntityCode($PEVC, $page_id, $page_settings, false, $task_statuses);
					
					self::preparePageSaveActionTime($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $page_id, $task_statuses);
				}
			}
			
			self::$add_user_page_id = $page_id;
		}
		
		self::prepareBlockTableStatus($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $task_id, $table_statuses, self::$add_user_block_id, $task_statuses);
		self::preparePageTableStatus($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $task_id, $table_statuses, self::$add_user_page_id, $task_statuses);
		
		return self::$add_user_page_id;
	}
	
	private static function getSecurityQuestionOptionsCode() {
		return '
						array(
							array(
								"value" => "What is the first name of the person you first kissed?",
								"label" => "What is the first name of the person you first kissed?",
							),
							array(
								"value" => "What is the last name of the teacher who gave you your first failing grade?",
								"label" => "What is the last name of the teacher who gave you your first failing grade?",
							),
							array(
								"value" => "What is the name of the place your wedding reception was held?",
								"label" => "What is the name of the place your wedding reception was held?",
							),
							array(
								"value" => "What was the name of your elementary / primary school?",
								"label" => "What was the name of your elementary / primary school?",
							),
							array(
								"value" => "In what city or town does your nearest sibling live?",
								"label" => "In what city or town does your nearest sibling live?",
							),
							array(
								"value" => "What time of the day were you born? (hh:mm)",
								"label" => "What time of the day were you born? (hh:mm)",
							),
						)';
	}
	
	private static function prepareBlockTableStatus($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $task_id, &$table_statuses, $block_id, $task_statuses = null) {
		if ($block_id) {
			$block_path = $PEVC->getBlockPath($block_id);
			
			if ($files_creation_type == 2) 
				$table_statuses[$task_id][$block_path] = file_exists($block_path) ? array(
					"file_id" => CMSPresentationLayerHandler::getFilePathId($PEVC, $block_path),
					"created_time" => filectime($block_path),
					"modified_time" => filemtime($block_path),
					"type" => "reserved",
				) : array("type" => "reserved");
			else if ($files_creation_type == 3) 
				$table_statuses[$task_id][$block_path] = array(
					"old_code" => file_exists($block_path) ? file_get_contents($block_path) : "",
					"new_code" => "", //reserved files don't file new codes in UI, so I don't need to be concern about this.
				);
			else if (!$files_creation_type || $files_creation_type == 1)
				$table_statuses[$task_id][$block_path] = file_exists($block_path) && (!$task_statuses || !isset($task_statuses[$block_path]) || $task_statuses[$block_path]) ? array(
					"file_id" => CMSPresentationLayerHandler::getFilePathId($PEVC, $block_path), 
					"created_time" => filectime($block_path),
					"modified_time" => filemtime($block_path), 
					"status" => true,
				) : false;
		}
	}
	
	private static function preparePageTableStatus($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $files_creation_type, $task_id, &$table_statuses, $page_id, $task_statuses = null) {
		if ($page_id) {
			$page_path = $PEVC->getEntityPath($page_id);
			
			if ($files_creation_type == 2) 
				$table_statuses[$task_id][$page_path] = file_exists($page_path) ? array(
					"file_id" => CMSPresentationLayerHandler::getFilePathId($PEVC, $page_path),
					"created_time" => filectime($page_path),
					"modified_time" => filemtime($page_path),
					"type" => "reserved",
					"hard_coded" => CMSPresentationLayerHandler::isEntityFileHardCoded($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $page_path),
				) : array(
					"type" => "reserved",
					"allow_non_authenticated_file" => $page_id == self::$logout_page_id || $page_id == self::$login_page_id || $page_id == self::$register_page_id || $page_id == self::$forgot_credentials_page_id,
				);
			else if ($files_creation_type == 3) 
				$table_statuses[$task_id][$page_path] = array(
					"old_code" => file_exists($page_path) ? file_get_contents($page_path) : "",
					"new_code" => "", //reserved files don't file new codes in UI, so I don't need to be concern about this.
				);
			else if (!$files_creation_type || $files_creation_type == 1) 
				$table_statuses[$task_id][$page_path] = file_exists($page_path) && (!$task_statuses || !isset($task_statuses[$page_path]) || $task_statuses[$page_path]) ? array(
					"file_id" => CMSPresentationLayerHandler::getFilePathId($PEVC, $page_path),
					"created_time" => filectime($page_path), 
					"modified_time" => filemtime($page_path), 
					"status" => true,
				) : false;
		}
	}
	
	private static function preparePageSaveActionTime($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $page_id, $task_statuses) {
		$page_path = $PEVC->getEntityPath($page_id); //page_id may change in the createAndSaveEntityCode
		
		if (!empty($task_statuses[$page_path])) {
			$P = $PEVC->getPresentationLayer();
			$layer_path = $P->getLayerPathSetting();
			$selected_project_id = $P->getSelectedPresentationId();
			
			$relative_page_path = str_replace($layer_path, "", $page_path);
			
			if (strpos($relative_page_path, "$selected_project_id/src/entity/") === 0) 
				CMSPresentationLayerHandler::cacheEntitySaveActionTime($PEVC, $SystemUserCacheHandler, $cms_page_cache_path_prefix, $page_path);
		}
	}
	
	private static function getBlockIdByModuleId($PEVC, $folder_path, $module_id, $extra_regexes = false) {
		if ($folder_path && is_dir($folder_path)) {
			$files = scandir($folder_path);
			$folder_path .= substr($folder_path, -1) == "/" ? "" : "/";
			
			if ($files)
				foreach ($files as $file) 
					if (substr($file, -4) == ".php") {
						$contents = file_get_contents("$folder_path$file");
						
						//checks if there is a logout module, this is, checks if exists the string: '->createBlock("user/logout",'
						$exists = preg_match('/->( *)createBlock( *)\(( *)("|\')' . str_replace("/", "\\/", $module_id) . '("|\')( *),( *)/u', $contents); //'/u' means with accents and ç too. '/u' converts unicode to accents chars.
						
						if ($exists && $extra_regexes) {
							$extra_regexes = is_array($extra_regexes) ? $extra_regexes : array($extra_regexes);
							
							foreach ($extra_regexes as $extra_regex)
								if (!preg_match($extra_regex, $contents))
									$exists = false;
						}
						
						if ($exists) {
							$blocks_path = $PEVC->getBlocksPath();
							$blocks_path .= substr($blocks_path, -1) == "/" ? "" : "/";
							$blocks_path = substr("$folder_path$file", strlen($blocks_path), -4);
							$blocks_path = preg_replace("/^\/+/", "", $blocks_path);
							
							return $blocks_path;
						}
					}
					else if ($file != "." && $file != ".." && is_dir("$folder_path$file")) {
						$block_id = self::getBlockIdByModuleId($PEVC, "$folder_path$file/", $module_id);
						
						if ($block_id) 
							return $block_id;
					}
		}
		
		return false;
	}
	
	private static function getPageIdByBlockId($PEVC, $folder_path, $block_id) {
		if ($folder_path && is_dir($folder_path)) {
			$files = scandir($folder_path);
			$folder_path .= substr($folder_path, -1) == "/" ? "" : "/";
			
			if ($files)
				foreach ($files as $file) 
					if (substr($file, -4) == ".php") {
						$contents = file_get_contents("$folder_path$file");
						
						//checks if there is a logout module, this is, checks if exists the string: '->createBlock("user/logout",'
						$exists = preg_match('/(include|include_once|require|require_once)( *)\$EVC( *)->( *)getBlockPath( *)\(( *)"' . str_replace("/", "\\/", $block_id) . '"( *)\)( *)/u', $contents); //'/u' means with accents and ç too. '/u' converts unicode to accents chars.
						
						if ($exists) {
							$entities_path = $PEVC->getEntitiesPath();
							$entities_path .= substr($entities_path, -1) == "/" ? "" : "/";
							$entities_path = substr("$folder_path$file", strlen($entities_path), -4);
							$entities_path = preg_replace("/^\/+/", "", $entities_path);
							
							return $entities_path;
						}
					}
					else if ($file != "." && $file != ".." && is_dir("$folder_path$file")) {
						$entity_id = self::getPageIdByBlockId($PEVC, "$folder_path$file/", $block_id);
						
						if ($entity_id) 
							return $entity_id;
					}
		}
		
		return false;
	}
}
?>
