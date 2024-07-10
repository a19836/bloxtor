<?php
include_once $EVC->getUtilPath("LayoutTypeProjectHandler");

class WorkFlowPresentationHandler {
	
	public static function getHeader($project_url_prefix, $project_common_url_prefix, $WorkFlowUIHandler = false, $set_workflow_file_url = false, $include_html_editor = false, $icons_and_edit_code_already_included = false) {
		$html = '
			<!-- Add MyTree main JS and CSS files -->
			<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/jquerymytree/css/style.min.css" type="text/css" charset="utf-8" />
			<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquerymytree/js/mytree.js"></script>

			<!-- Add FileManager JS file -->
			<link rel="stylesheet" href="' . $project_url_prefix . 'css/file_manager.css" type="text/css" charset="utf-8" />
			<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/file_manager.js"></script>
		';
		
		$html .= $WorkFlowUIHandler ? $WorkFlowUIHandler->getHeader(array("tasks_css_and_js" => true, "icons_and_edit_code_already_included" => $icons_and_edit_code_already_included)) : "";
		
		if (strpos($html, 'vendor/jsbeautify/js/lib/beautify.js') === false)
			$html = '
				<!-- Add Html/CSS/JS Beautify code -->
				<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jsbeautify/js/lib/beautify.js"></script>
				<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jsbeautify/js/lib/beautify-css.js"></script>
				<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'lib/myhtmlbeautify/MyHtmlBeautify.js"></script>
			' . $html;
				
		if (strpos($html, 'vendor/mycodebeautifier/js/MyCodeBeautifier.js') === false)
			$html = '
				<!-- Add Code Beautifier -->
				<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/mycodebeautifier/js/MyCodeBeautifier.js"></script>' . $html;
				
		if (strpos($html, 'vendor/acecodeeditor/src-min-noconflict/ace.js') === false)
			$html = '		
				<!-- Add Code Editor JS files -->
				<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/acecodeeditor/src-min-noconflict/ace.js"></script>
				<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/acecodeeditor/src-min-noconflict/ext-language_tools.js"></script>' . $html;
		
		if (strpos($html, 'vendor/jquery/js/jquery.md5.js') === false)
			$html = '
				<!-- Add MD5 JS Files -->
				<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquery/js/jquery.md5.js"></script>' . $html;
		
		if ($set_workflow_file_url) {
			$html .= '<script>
				taskFlowChartObj.TaskFile.set_tasks_file_url = \'' . $set_workflow_file_url . '\';
				</script>';
		}
		
		if (!$icons_and_edit_code_already_included) {
			if (!$WorkFlowUIHandler)
				$html .= '
				<!-- Add Fontawsome Icons CSS -->
				<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">
				
				<!-- Add Icons CSS file -->
				<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />
				
				<!-- Add Layout JS file -->
				<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/layout.js"></script>
				';
			
			$html .= '
			<!-- Add Edit PHP Code JS and CSS files -->
			<link rel="stylesheet" href="' . $project_url_prefix . 'css/edit_php_code.css" type="text/css" charset="utf-8" />
			<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/edit_php_code.js"></script>
			
			<!-- Add CodeHighLight CSS and JS -->
			<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/codehighlight/styles/default.css" type="text/css" charset="utf-8" />
			<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/codehighlight/highlight.pack.js"></script>
			';
		}
		
		if ($WorkFlowUIHandler) {
			//for the php workflow diagrams - this needs to be after the edit_php_code.js
			$html .= '
			<!-- add default function to reset the top positon of the tasksflow panels, if with_top_bar class exists -->
			<script>
				taskFlowChartObj.setTaskFlowChartObjOption("on_resize_panels_function", onResizeTaskFlowChartPanels);
			</script>
			';
			
			$html .= $WorkFlowUIHandler->getJS(false, false, array("is_droppable_connection" => true, "add_default_start_task" => true, "resizable_task_properties" => true, "resizable_connection_properties" => true));
		}
		
		//include CKEDITOR to be used in the app/lib/org/phpframework/workflow/task/programming/inlinehtml/webroot/js/WorkFlowTask.js, if the LayoutUIEditor is not included. 
		//Note that this case should never happem, bc the LayoutUIEditor is always included.
		if ($include_html_editor) {
			$EVC = $GLOBALS["EVC"];
			$js_file = $EVC->getWebrootPath($EVC->getCommonProjectName()) . "vendor/ckeditor/ckeditor.js";
			
			if (file_exists($js_file))
				$html .= '
			<!-- Add CKEditor JS Files  -->
			<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/ckeditor/ckeditor.js"></script>';
		}
		
		return $html;
	}
	
	public static function getFileManagerTreePopupHeader($project_url_prefix) {
		//preparing file manager urls
		$edit_file_manager_hbn_obj_url = $project_url_prefix . "phpframework/dataaccess/edit_hbn_obj?bean_name=#bean_name#&bean_file_name=#bean_file_name#&filter_by_layout=#filter_by_layout#&item_type=hibernate&path=#path#&obj=#obj#";
		$edit_file_manager_query_url = $project_url_prefix . "phpframework/dataaccess/edit_query?bean_name=#bean_name#&bean_file_name=#bean_file_name#&filter_by_layout=#filter_by_layout#&item_type=#item_type#&path=#path#&obj=#obj#&query_id=#query_id#&query_type=#query_type#&relationship_type=#relationship_type#";
		
		$edit_file_manager_service_obj_url = $project_url_prefix . "phpframework/businesslogic/edit_service?bean_name=#bean_name#&bean_file_name=#bean_file_name#&filter_by_layout=#filter_by_layout#&path=#path#&service=#service#";
		$save_file_manager_service_obj_url = $project_url_prefix . "phpframework/businesslogic/save_service?bean_name=#bean_name#&bean_file_name=#bean_file_name#&filter_by_layout=#filter_by_layout#&path=#path#";
		$edit_file_manager_service_method_url = $project_url_prefix . "phpframework/businesslogic/edit_method?bean_name=#bean_name#&bean_file_name=#bean_file_name#&filter_by_layout=#filter_by_layout#&path=#path#&service=#service#&method=#method#";
		$save_file_manager_service_method_url = $project_url_prefix . "phpframework/businesslogic/save_method?bean_name=#bean_name#&bean_file_name=#bean_file_name#&filter_by_layout=#filter_by_layout#&path=#path#&class=#service#";
		$edit_file_manager_service_function_url = $project_url_prefix . "phpframework/businesslogic/edit_function?bean_name=#bean_name#&bean_file_name=#bean_file_name#&filter_by_layout=#filter_by_layout#&path=#path#&function=#function#";
		$save_file_manager_service_function_url = $project_url_prefix . "phpframework/businesslogic/save_function?bean_name=#bean_name#&bean_file_name=#bean_file_name#&filter_by_layout=#filter_by_layout#&path=#path#";
		
		$edit_file_manager_class_url = $project_url_prefix . "phpframework/admin/edit_file_class?bean_name=#bean_name#&bean_file_name=#bean_file_name#&filter_by_layout=#filter_by_layout#&item_type=#item_type#&path=#path#&class=#class#";
		$save_file_manager_class_url = $project_url_prefix . "phpframework/admin/save_file_class?bean_name=#bean_name#&bean_file_name=#bean_file_name#&filter_by_layout=#filter_by_layout#&item_type=#item_type#&path=#path#";
		$edit_file_manager_class_method_url = $project_url_prefix . "phpframework/admin/edit_file_class_method?bean_name=#bean_name#&bean_file_name=#bean_file_name#&filter_by_layout=#filter_by_layout#&item_type=#item_type#&path=#path#&class=#class#&method=#method#";
		$save_file_manager_class_method_url = $project_url_prefix . "phpframework/admin/save_file_class_method?bean_name=#bean_name#&bean_file_name=#bean_file_name#&filter_by_layout=#filter_by_layout#&item_type=#item_type#&path=#path#&class=#class#";
		$edit_file_manager_function_url = $project_url_prefix . "phpframework/admin/edit_file_function?bean_name=#bean_name#&bean_file_name=#bean_file_name#&filter_by_layout=#filter_by_layout#&item_type=#item_type#&path=#path#&function=#function#";
		$save_file_manager_function_url = $project_url_prefix . "phpframework/admin/save_file_function?bean_name=#bean_name#&bean_file_name=#bean_file_name#&filter_by_layout=#filter_by_layout#&item_type=#item_type#&path=#path#";
		
		$html = '<script>
		//preparing file manager urls
		var edit_file_manager_hbn_obj_url = \'' . $edit_file_manager_hbn_obj_url . '\';
		var edit_file_manager_query_url = \'' . $edit_file_manager_query_url . '\';
		
		var edit_file_manager_service_obj_url = \'' . $edit_file_manager_service_obj_url . '\';
		var save_file_manager_service_obj_url = \'' . $save_file_manager_service_obj_url . '\';
		var edit_file_manager_service_method_url = \'' . $edit_file_manager_service_method_url . '\';
		var save_file_manager_service_method_url = \'' . $save_file_manager_service_method_url . '\';
		var edit_file_manager_service_function_url = \'' . $edit_file_manager_service_function_url . '\';
		var save_file_manager_service_function_url = \'' . $save_file_manager_service_function_url . '\';
		
		var edit_file_manager_class_url = \'' . $edit_file_manager_class_url . '\';
		var save_file_manager_class_url = \'' . $save_file_manager_class_url . '\';
		var edit_file_manager_class_method_url = \'' . $edit_file_manager_class_method_url . '\';
		var save_file_manager_class_method_url = \'' . $save_file_manager_class_method_url . '\';
		var edit_file_manager_function_url = \'' . $edit_file_manager_function_url . '\';
		var save_file_manager_function_url = \'' . $save_file_manager_function_url . '\';
		</script>';
		
		return $html;
	}
	
	public static function getUIEditorWidgetsHtml($webroot_path, $project_url_prefix, $webroot_cache_folder_path, $webroot_cache_folder_url, $options = null) {
		$layout_ui_editor_path = $webroot_path . "lib/jquerylayoutuieditor/";
		include_once $layout_ui_editor_path . "util.php";
		
		$cache_suffix = "jquerylayoutuieditor/widget/";
		$widgets_webroot_cache_folder_path = $webroot_cache_folder_path . $cache_suffix;
		$widgets_webroot_cache_folder_url = $webroot_cache_folder_url . $cache_suffix;
		$widgets_root_path = $layout_ui_editor_path . "widget/";
		$widgets_root_url = $project_url_prefix . "lib/jquerylayoutuieditor/widget/";
		$widgets = scanWidgets($widgets_root_path, array(
			"priority_files" => getDefaultWidgetsPriorityFiles()
		));
		$widgets = filterWidgets($widgets, $widgets_root_path, $widgets_root_url, $options);
		$menu_widgets_html .= getMenuWidgetsHTML($widgets, $widgets_root_path, $widgets_root_url, $widgets_webroot_cache_folder_path, $widgets_webroot_cache_folder_url);
		
		return $menu_widgets_html;
	}
	
	public static function getExtraUIEditorWidgetsHtml($webroot_path, $extra_widgets_root_path, $webroot_cache_folder_path, $webroot_cache_folder_url, $options = null) {
		$layout_ui_editor_path = $webroot_path . "lib/jquerylayoutuieditor/";
		include_once $layout_ui_editor_path . "util.php";
		
		//add region block and region param widgets
		$cache_suffix = "jquerylayoutuieditor/widget/";
		$widgets_webroot_cache_folder_path = $webroot_cache_folder_path . $cache_suffix;
		$widgets_webroot_cache_folder_url = $webroot_cache_folder_url . $cache_suffix;
		$root_cache_folder_path = $webroot_cache_folder_path . $cache_suffix;
		$wrp_id = basename($extra_widgets_root_path);
		$widgets_root_url = "$webroot_cache_folder_url$cache_suffix$wrp_id/";
		
		$widgets = scanWidgets($extra_widgets_root_path);
		$widgets = filterWidgets($widgets, $extra_widgets_root_path, $widgets_root_url, $options);
		self::cacheUIEditorWidgetsWebrootFolder($widgets, "$root_cache_folder_path$wrp_id/");
		$menu_widgets_html = getMenuWidgetsHTML($widgets, $extra_widgets_root_path, $widgets_root_url, $widgets_webroot_cache_folder_path, $widgets_webroot_cache_folder_url);
		
		return $menu_widgets_html;
	}
	
	//The getUserUIEditorWidgetsHtml is different from getExtraUIEditorWidgetsHtml bc it copies the webroot folder from the $template_widgets_root_path to $webroot_cache_folder_path
	public static function getUserUIEditorWidgetsHtml($webroot_path, $widgets_root_path, $webroot_cache_folder_path, $webroot_cache_folder_url, $options = null) {
		$menu_widgets_html = "";
		
		if ($widgets_root_path) {
			$layout_ui_editor_path = $webroot_path . "lib/jquerylayoutuieditor/";
			include_once $layout_ui_editor_path . "util.php";
			
			$cache_suffix = "jquerylayoutuieditor/widget/";
			$widgets_webroot_cache_folder_path = $webroot_cache_folder_path . $cache_suffix;
			$widgets_webroot_cache_folder_url = $webroot_cache_folder_url . $cache_suffix;
			$root_cache_folder_path = $webroot_cache_folder_path . $cache_suffix;
			$widgets_root_path = is_array($widgets_root_path) ? $widgets_root_path : array($widgets_root_path);
			
			foreach ($widgets_root_path as $wrp) 
				if (file_exists($wrp)) { //file_exists is very important bc if file doesn't exists, the realpath will return "/" but bc of the basedir in the php.ini we will get a php error bc the "/" folder is not allowed (bc of security reasons).
					$wrp = str_replace("//", "/", trim( realpath($wrp) ));
					$wrp .= substr($wrp, strlen($wrp) - 1) == "/" ? "" : "/";
					$wrp_id = hash("crc32b", $wrp);
					$widgets_root_url = "$webroot_cache_folder_url$cache_suffix$wrp_id/";
					
					$widgets = scanWidgets($wrp);
					$widgets = filterWidgets($widgets, $wrp, $widgets_root_url, $options);
					self::cacheUIEditorWidgetsWebrootFolder($widgets, "$root_cache_folder_path$wrp_id/");
					$menu_widgets_html = getMenuWidgetsHTML($widgets, $wrp, $widgets_root_url, $widgets_webroot_cache_folder_path, $widgets_webroot_cache_folder_url);
				}
		}
		
		return $menu_widgets_html;
	}
	
	private static function cacheUIEditorWidgetsWebrootFolder($widgets, $cache_folder_path) {
		$status = true;
		
		//Only if cache does not exists yet, otherwise is too overloaded. We only need to do this once. After it be cahced, we don't need to run this again, until someone deletes the cache.
		if ($widgets && !is_dir($cache_folder_path)) 
			foreach ($widgets as $name => $sub_files) {
				if (is_array($sub_files)) //is menu group
					self::cacheUIEditorWidgetsWebrootFolder($sub_files, $cache_folder_path . "$name/");
				else { //is menu widget.
					$widget_webroot_folder_path = dirname($sub_files) . "/webroot/";
					
					if (file_exists($widget_webroot_folder_path) && !self::copyFolder($widget_webroot_folder_path, $cache_folder_path))
						$status = false;
				}
			}
		
		return $status;
	}
	
	private static function copyFolder($src, $dst) {
		if ($src && $dst && is_dir($src)) {
			if (!is_dir($dst)) 
				@mkdir($dst, 0755, true);
			
			if (is_dir($dst)) {
				$status = true;
				$files = scandir($src);
				
				if ($files)
					foreach ($files as $file)
						if ($file != '.' && $file != '..') { 
							if (is_dir("$src/$file")) { 
								if (!self::copyFolder("$src/$file", "$dst/$file"))
									$status = false;
							} 
							else if (!copy("$src/$file", "$dst/$file"))
								$status = false;
						}
				
				return $status; 
			}
		}
	}
	
	public static function getPresentationBrokersHtml($presentation_brokers, $choose_bean_layer_files_from_file_manager_url, $get_file_properties_url, $upload_bean_layer_files_from_file_manager_url = false) {
		$html = '';
		
		if ($presentation_brokers) {
			$t = count($presentation_brokers);
			for ($i = 0; $i < $t; $i++) {
				$b = $presentation_brokers[$i];
				
				if ($b[2]) {
					$get_sub_files_url = str_replace("#bean_file_name#", $b[1], str_replace("#bean_name#", $b[2], $choose_bean_layer_files_from_file_manager_url)) . '&item_type=presentation&folder_type=#folder_type#';
					$upload_url = $upload_bean_layer_files_from_file_manager_url ? str_replace("#bean_file_name#", $b[1], str_replace("#bean_name#", $b[2], $upload_bean_layer_files_from_file_manager_url)) . '&item_type=presentation' : "";
					
					$html .= 'main_layers_properties.' . $b[2] . ' = {ui: {
						folder: {
							get_sub_files_url: "' . $get_sub_files_url . '",
							attributes: {
								folder_path: "#path#",
								upload_url: "' . $upload_url . '",
							},
						},
						file: {
							attributes: {
								file_path: "#path#",
								bean_name: "' . $b[2] . '",
								get_file_properties_url: "' . str_replace("#bean_file_name#", $b[1], str_replace("#bean_name#", $b[2], $get_file_properties_url)) . '"
							},
						},
					}};
			
					main_layers_properties.' . $b[2] . '.ui["project_folder"] = main_layers_properties.' . $b[2] . '.ui["folder"];
					main_layers_properties.' . $b[2] . '.ui["entities_folder"] = main_layers_properties.' . $b[2] . '.ui["folder"];
					main_layers_properties.' . $b[2] . '.ui["views_folder"] = main_layers_properties.' . $b[2] . '.ui["folder"];
					main_layers_properties.' . $b[2] . '.ui["templates_folder"] = main_layers_properties.' . $b[2] . '.ui["folder"];
					main_layers_properties.' . $b[2] . '.ui["utils_folder"] = main_layers_properties.' . $b[2] . '.ui["folder"];
					main_layers_properties.' . $b[2] . '.ui["configs_folder"] = main_layers_properties.' . $b[2] . '.ui["folder"];
					main_layers_properties.' . $b[2] . '.ui["blocks_folder"] = main_layers_properties.' . $b[2] . '.ui["folder"];
					main_layers_properties.' . $b[2] . '.ui["cms_common"] = main_layers_properties.' . $b[2] . '.ui["folder"]; //used in deployment/index.php
					main_layers_properties.' . $b[2] . '.ui["cms_module"] = main_layers_properties.' . $b[2] . '.ui["folder"];
					main_layers_properties.' . $b[2] . '.ui["cms_program"] = main_layers_properties.' . $b[2] . '.ui["folder"];
					main_layers_properties.' . $b[2] . '.ui["cms_resource"] = main_layers_properties.' . $b[2] . '.ui["folder"];
					main_layers_properties.' . $b[2] . '.ui["cms_folder"] = main_layers_properties.' . $b[2] . '.ui["folder"];
					main_layers_properties.' . $b[2] . '.ui["wordpress_folder"] = main_layers_properties.' . $b[2] . '.ui["folder"];
					main_layers_properties.' . $b[2] . '.ui["wordpress_installation_folder"] = main_layers_properties.' . $b[2] . '.ui["folder"];
					main_layers_properties.' . $b[2] . '.ui["module_folder"] = main_layers_properties.' . $b[2] . '.ui["folder"];
					main_layers_properties.' . $b[2] . '.ui["controllers_folder"] = main_layers_properties.' . $b[2] . '.ui["folder"];
					main_layers_properties.' . $b[2] . '.ui["webroot_folder"] = main_layers_properties.' . $b[2] . '.ui["folder"];
					main_layers_properties.' . $b[2] . '.ui["caches_folder"] = main_layers_properties.' . $b[2] . '.ui["folder"];
					main_layers_properties.' . $b[2] . '.ui["routers_folder"] = main_layers_properties.' . $b[2] . '.ui["folder"];
					main_layers_properties.' . $b[2] . '.ui["dispatchers_folder"] = main_layers_properties.' . $b[2] . '.ui["folder"];
					
					main_layers_properties.' . $b[2] . '.ui["entity_file"] = main_layers_properties.' . $b[2] . '.ui["file"];
					main_layers_properties.' . $b[2] . '.ui["view_file"] = main_layers_properties.' . $b[2] . '.ui["file"];
					main_layers_properties.' . $b[2] . '.ui["template_file"] = main_layers_properties.' . $b[2] . '.ui["file"];
					main_layers_properties.' . $b[2] . '.ui["template_folder"] = main_layers_properties.' . $b[2] . '.ui["folder"];
					main_layers_properties.' . $b[2] . '.ui["util_file"] = main_layers_properties.' . $b[2] . '.ui["file"];
					main_layers_properties.' . $b[2] . '.ui["config_file"] = main_layers_properties.' . $b[2] . '.ui["file"];
					main_layers_properties.' . $b[2] . '.ui["block_file"] = main_layers_properties.' . $b[2] . '.ui["file"];
					main_layers_properties.' . $b[2] . '.ui["module_file"] = main_layers_properties.' . $b[2] . '.ui["file"];
					main_layers_properties.' . $b[2] . '.ui["controller_file"] = main_layers_properties.' . $b[2] . '.ui["file"];
					main_layers_properties.' . $b[2] . '.ui["js_file"] = main_layers_properties.' . $b[2] . '.ui["file"];
					main_layers_properties.' . $b[2] . '.ui["css_file"] = main_layers_properties.' . $b[2] . '.ui["file"];
					main_layers_properties.' . $b[2] . '.ui["img_file"] = main_layers_properties.' . $b[2] . '.ui["file"];
					main_layers_properties.' . $b[2] . '.ui["undefined_file"] = main_layers_properties.' . $b[2] . '.ui["file"];
					main_layers_properties.' . $b[2] . '.ui["cache_file"] = main_layers_properties.' . $b[2] . '.ui["file"];
					main_layers_properties.' . $b[2] . '.ui["router_file"] = main_layers_properties.' . $b[2] . '.ui["file"];
					main_layers_properties.' . $b[2] . '.ui["dispatcher_file"] = main_layers_properties.' . $b[2] . '.ui["file"];
					main_layers_properties.' . $b[2] . '.ui["class"] = main_layers_properties.' . $b[2] . '.ui["file"];
					
					main_layers_properties.' . $b[2] . '.ui["project"] = {
						attributes: {
							project_path: "#path#",
						}
					};
					main_layers_properties.' . $b[2] . '.ui["project_common"] = main_layers_properties.' . $b[2] . '.ui["project"];
					
					main_layers_properties.' . $b[2] . '.ui["referenced_folder"] = {
						get_sub_files_url: "' . $get_sub_files_url . '",
					};
					';
				}
			}
		}
		
		return $html;
	}
	
	public static function getBusinessLogicBrokersHtml($business_logic_brokers, $choose_bean_layer_files_from_file_manager_url, $get_file_properties_url) {
		$html = '';
		
		if ($business_logic_brokers) {
			$t = count($business_logic_brokers);
			for ($i = 0; $i < $t; $i++) {
				$b = $business_logic_brokers[$i];
				
				if ($b[2]) {
					$get_sub_files_url = str_replace("#bean_file_name#", $b[1], str_replace("#bean_name#", $b[2], $choose_bean_layer_files_from_file_manager_url));
					
					$html .= 'main_layers_properties.' . $b[2] . ' = {ui: {
						folder: {
							get_sub_files_url: "' . $get_sub_files_url . '",
						},
						cms_common: {
							get_sub_files_url: "' . $get_sub_files_url . '",
						},
						cms_module: {
							get_sub_files_url: "' . $get_sub_files_url . '",
						},
						cms_program: {
							get_sub_files_url: "' . $get_sub_files_url . '",
						},
						cms_resource: {
							get_sub_files_url: "' . $get_sub_files_url . '",
						},
						file: {
							attributes: {
								file_path: "#path#",
								bean_name: "' . $b[2] . '",
								get_file_properties_url: "' . str_replace("#bean_file_name#", $b[1], str_replace("#bean_name#", $b[2], $get_file_properties_url)) . '"
							}
						},
						service: {
							attributes: {
								file_path: "#path#",
								bean_name: "' . $b[2] . '",
								get_file_properties_url: "' . str_replace("#bean_file_name#", $b[1], str_replace("#bean_name#", $b[2], $get_file_properties_url)) . '"
							}
						},
						referenced_folder: {
							get_sub_files_url: "' . $get_sub_files_url . '",
						},
					}};';
				}
			}
		}
			
		return $html;
	}
	
	public static function getDaoLibAndVendorBrokersHtml($choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, $get_file_properties_url) {
		$choose_test_unit_files_from_file_manager_url = str_replace("=dao", "=test_unit", $choose_dao_files_from_file_manager_url);
		$get_dao_sub_files_url = str_replace("#bean_file_name#", "", str_replace("#bean_name#", "dao", $get_file_properties_url));
		
		return '
			main_layers_properties.dao = {ui: {
				folder: {
					get_sub_files_url: "' . $choose_dao_files_from_file_manager_url . '",
				},
				cms_common: {
					get_sub_files_url: "' . $choose_dao_files_from_file_manager_url . '",
				},
				cms_module: {
					get_sub_files_url: "' . $choose_dao_files_from_file_manager_url . '",
				},
				cms_program: {
					get_sub_files_url: "' . $choose_dao_files_from_file_manager_url . '",
				},
				cms_resource: {
					get_sub_files_url: "' . $choose_dao_files_from_file_manager_url . '",
				},
				file: {
					attributes: {
						file_path: "#path#",
						bean_name: "dao",
						get_file_properties_url: "' . $get_dao_sub_files_url . '"
					}
				},
				objtype: {
					attributes: {
						file_path: "#path#",
						bean_name: "dao",
						get_file_properties_url: "' . $get_dao_sub_files_url . '"
					}
				},
				hibernatemodel: {
					attributes: {
						file_path: "#path#",
						bean_name: "dao",
						get_file_properties_url: "' . $get_dao_sub_files_url . '"
					}
				},
			}};
			main_layers_properties.lib = {ui: {
				folder: {
					get_sub_files_url: "' . $choose_lib_files_from_file_manager_url . '",
				},
				file: {
					attributes: {
						file_path: "#path#",
						bean_name: "lib",
						get_file_properties_url: "' . str_replace("#bean_file_name#", "", str_replace("#bean_name#", "lib", $get_file_properties_url)) . '"
					}
				},
			}};
			main_layers_properties.vendor = {ui: {
				folder: {
					get_sub_files_url: "' . $choose_vendor_files_from_file_manager_url . '",
				},
				file: {
					attributes: {
						file_path: "#path#",
						bean_name: "vendor",
						get_file_properties_url: "' . str_replace("#bean_file_name#", "", str_replace("#bean_name#", "vendor", $get_file_properties_url)) . '"
					}
				},
				code_workflow_editor: {
					get_sub_files_url: "' . $choose_vendor_files_from_file_manager_url . '",
				},
				code_workflow_editor_task: {
					get_sub_files_url: "' . $choose_vendor_files_from_file_manager_url . '",
				},
				layout_ui_editor: {
					get_sub_files_url: "' . $choose_vendor_files_from_file_manager_url . '",
				},
				layout_ui_editor_widget: {
					get_sub_files_url: "' . $choose_vendor_files_from_file_manager_url . '",
				},
				dao: {
					get_sub_files_url: "' . $choose_dao_files_from_file_manager_url . '",
				},
				test_unit: {
					get_sub_files_url: "' . $choose_test_unit_files_from_file_manager_url . '",
				},
			}};
			main_layers_properties.test_unit = {ui: {
				folder: {
					get_sub_files_url: "' . $choose_test_unit_files_from_file_manager_url . '",
					attributes: {
						file_path: "#path#", //used in src/testunit/index.php
					}
				},
				file: {
					attributes: {
						file_path: "#path#",
						bean_name: "test_unit",
						get_file_properties_url: "' . str_replace("#bean_file_name#", "", str_replace("#bean_name#", "test_unit", $get_file_properties_url)) . '"
					}
				},
				test_unit_obj: {
					attributes: {
						file_path: "#path#",
						bean_name: "test_unit",
						get_file_properties_url: "' . str_replace("#bean_file_name#", "", str_replace("#bean_name#", "test_unit", $get_file_properties_url)) . '"
					}
				},
			}};
		';
	}
	
	public static function getDataAccessBrokersHtml($data_access_brokers, $choose_bean_layer_files_from_file_manager_url) {
		$html = '';
		
		if ($data_access_brokers) {
			$t = count($data_access_brokers);
			for ($i = 0; $i < $t; $i++) {
				$b = $data_access_brokers[$i];
				
				if ($b[2]) {
					$get_sub_files_url = str_replace("#bean_file_name#", $b[1], str_replace("#bean_name#", $b[2], $choose_bean_layer_files_from_file_manager_url));
					
					$html .= 'main_layers_properties.' . $b[2] . ' = {ui: {
						folder: {
							get_sub_files_url: "' . $get_sub_files_url . '",
						},
						cms_common: {
							get_sub_files_url: "' . $get_sub_files_url . '",
						},
						cms_module: {
							get_sub_files_url: "' . $get_sub_files_url . '",
						},
						cms_program: {
							get_sub_files_url: "' . $get_sub_files_url . '",
						},
						cms_resource: {
							get_sub_files_url: "' . $get_sub_files_url . '",
						},
						file: {
							attributes: {
								file_path: "#path#"
							}
						},
						obj: {
							attributes: {
								file_path: "#path#"
							}
						},
						query: {
							attributes: {
								file_path: "#path#",
								query_type: "#query_type#",
								relationship_type: "#relationship_type#",
								hbn_obj_id: "#hbn_obj_id#",
							}
						},
						relationship: {
							attributes: {
								file_path: "#path#",
								query_type: "#query_type#",
								relationship_type: "#relationship_type#",
								hbn_obj_id: "#hbn_obj_id#",
							}
						},
						hbn_native: {
							attributes: {
								file_path: "#path#",
								query_type: "#query_type#",
								relationship_type: "#relationship_type#",
								hbn_obj_id: "#hbn_obj_id#",
							}
						},
						referenced_folder: {
							get_sub_files_url: "' . $get_sub_files_url . '",
						},
					}};';
				}
			}
		}
		
		return $html;
	}
	
	public static function getChooseFromFileManagerPopupHtml($bean_name, $bean_file_name, $choose_bean_layer_files_from_file_manager_url, $choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, $db_brokers, $data_access_brokers, $ibatis_brokers, $hibernate_brokers, $business_logic_brokers, $presentation_brokers) {
		$html = "";
		
		if ($bean_name && $bean_file_name)
			$html = self::getChooseFromFileManagerPopupHtmlByLayer($bean_name, $bean_file_name, $choose_bean_layer_files_from_file_manager_url, $choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, $db_brokers, $data_access_brokers, $ibatis_brokers, $hibernate_brokers, $business_logic_brokers, $presentation_brokers);
		else
			$html = self::getChooseFromFileManagerPopupHtmlForAllLayers($choose_bean_layer_files_from_file_manager_url, $choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, $business_logic_brokers, $presentation_brokers);
		
		if (isset($db_brokers) || isset($data_access_brokers)) {
			$arr = $db_brokers ? $db_brokers : $data_access_brokers;
			$t = count($arr);
			
			$html .='<div id="choose_db_driver_table" class="myfancypopup with_title">
				<div class="title">Choose a Table</div>
				<div class="broker' . ($t == 1 ? " single_broker" : "") . '">
					<label>Broker:</label>
					<select onChange="updateDBDriverOnBrokerNameChange(this)">';
			
			for ($i = 0; $i < $t; $i++) {
				$b = $arr[$i];
				$html .= '<option bean_file_name="' . $b[1] . '" bean_name="' . $b[2] . '" value="' . $b[0] . '">' . $b[0] . ($b[2] ? '' : ' (Rest)') . '</option>'; //bean_file_name and bean_name are used by the testunits and in onChooseDBTableAndAttributes and updateDBTablesOnBrokerDBDriverChange functions
			}
			$html .= '
					</select>
				</div>
				<div class="db_driver">
					<label>DB Driver:</label>
					<select onChange="updateDBTablesOnBrokerDBDriverChange(this)"></select>
				</div>
				<div class="type">
					<label>Type:</label>
					<select onChange="updateDBTablesOnBrokerDBDriverChange(this)">
						<option value="db">From DB Server</option>
						<option value="diagram">From DB Diagram</option>
					</select>
				</div>
				<div class="db_table">
					<label>DB Table:</label>
					<select></select>
					<span class="icon refresh" onClick="refreshDBTablesOnBrokerDBDriverChange(this)"></span>
				</div>
				<div class="button">
					<input type="button" value="Update" onClick="MyFancyPopup.settings.updateFunction(this)" />
				</div>
			</div>';
		}
		
		if (isset($data_access_brokers)) {
			$t = count($data_access_brokers);
			
			$html .='<div id="choose_db_driver" class="myfancypopup with_title">
				<div class="title">Choose a Database Driver</div>
				<div class="broker' . ($t == 1 ? " single_broker" : "") . '">
					<label>Data Access Broker:</label>
					<select onChange="updateDBDriverOnBrokerNameChange(this)">';
			
			for ($i = 0; $i < $t; $i++) {
				$b = $data_access_brokers[$i];
				$html .= '<option bean_file_name="' . $b[1] . '" bean_name="' . $b[2] . '" value="' . $b[0] . '">' . $b[0] . ($b[2] ? '' : ' (Rest)') . '</option>'; //bean_file_name and bean_name are used by the testunits
			}
			$html .= '
					</select>
				</div>
				<div class="db_driver">
					<label>DB Driver:</label>
					<select></select>
				</div>
				<div class="button">
					<input type="button" value="Update" onClick="MyFancyPopup.settings.updateFunction(this)" />
				</div>
			</div>';
		}
		
		if (isset($ibatis_brokers)) {
			$t = count($ibatis_brokers);
			
			$html .='<div id="choose_query_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
				<div class="title">Choose a Query</div>
				<div class="broker' . ($t == 1 ? " single_broker" : "") . '">
					<label>Broker:</label>
					<select onChange="updateDBDriverOnBrokerNameChange(this);updateLayerUrlFileManager(this)">';
					
			for ($i = 0; $i < $t; $i++) {
				$b = $ibatis_brokers[$i];
				$html .= '<option bean_file_name="' . $b[1] . '" bean_name="' . $b[2] . '" value="' . $b[0] . '">' . $b[0] . ($b[2] ? '' : ' (Rest)') . '</option>';
			}
			$html .= '
					</select>
				</div>
				<div class="db_driver">
					<label>DB Driver:</label>
					<select></select>
				</div>
				<div class="type hidden"> <!-- Aparentely any choice we made will get the same result, so the type does not need to be here! -->
					<label>Type:</label>
					<select name="type">
						<option value="db">From DB Server</option>
						<option value="diagram">From DB Diagram</option>
					</select>
					<span class="icon info" title="When we get the parameters for a query we need to know if the attribtutes/parameters will have quotes or no-quotes, this is, if are \'string\' or \'code\'! In order to do this we need to get the db attributes type for each parameter and then check if the attributes type are quotes or non-quotes, this is, bigint is a no-quotes, but varchar is a quotes type. So we can get the db attributes type from the \'DB Server\' or from the \'DB Diagram\'" onClick="alert(this.getAttribute(\'title\'))"></span>
				</div>
				<ul class="mytree">
					<li>
						<label>Root</label>
						<ul layer_url="' . $choose_bean_layer_files_from_file_manager_url . '"></ul>
					</li>
				</ul>
				<div class="button">
					<input type="button" value="Update" onClick="MyFancyPopup.settings.updateFunction(this)" />
				</div>
			</div>';
		}
		
		if (isset($hibernate_brokers)) {
			$t = count($hibernate_brokers);
			
			$html .='<div id="choose_hibernate_object_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
				<div class="title">Choose a Hibernate Object</div>
				<div class="broker' . ($t == 1 ? " single_broker" : "") . '">
					<label>Broker:</label>
					<select onChange="updateDBDriverOnBrokerNameChange(this);updateLayerUrlFileManager(this)">';
			
			for ($i = 0; $i < $t; $i++) {
				$b = $hibernate_brokers[$i];
				$html .= '<option bean_file_name="' . $b[1] . '" bean_name="' . $b[2] . '" value="' . $b[0] . '">' . $b[0] . ($b[2] ? '' : ' (Rest)') . '</option>';
			}
			$html .= '
					</select>
				</div>
				<div class="db_driver">
					<label>DB Driver:</label>
					<select></select>
				</div>
				<ul class="mytree">
					<li>
						<label>Root</label>
						<ul layer_url="' . $choose_bean_layer_files_from_file_manager_url . '"></ul>
					</li>
				</ul>
				<div class="button">
					<input type="button" value="Update" onClick="MyFancyPopup.settings.updateFunction(this)" />
				</div>
			</div>
			
			<div id="choose_hibernate_object_method_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
				<div class="title">Choose a Hibernate Object Method</div>
				<div class="broker' . ($t == 1 ? " single_broker" : "") . '">
					<label>Broker:</label>
					<select onChange="updateDBDriverOnBrokerNameChange(this);updateLayerUrlFileManager(this)">';
			
			for ($i = 0; $i < $t; $i++) {
				$b = $hibernate_brokers[$i];
				$html .= '<option bean_file_name="' . $b[1] . '" bean_name="' . $b[2] . '" value="' . $b[0] . '">' . $b[0] . ($b[2] ? '' : ' (Rest)') . '</option>';
			}
			$html .= '
					</select>
				</div>
				<div class="db_driver">
					<label>DB Driver:</label>
					<select></select>
				</div>
				<div class="type hidden"> <!-- Aparentely any choice we made will get the same result, so the type does not need to be here! -->
					<label>Type:</label>
					<select name="type">
						<option value="db">From DB Server</option>
						<option value="diagram">From DB Diagram</option>
					</select>
					<span class="icon info" title="When we get the parameters for a method or query we need to know if the attribtutes/parameters will have quotes or no-quotes, this is, if are \'string\' or \'code\'! In order to do this we need to get the db attributes type for each parameter and then check if the attributes type are quotes or non-quotes, this is, bigint is a no-quotes, but varchar is a quotes type. So we can get the db attributes type from the \'DB Server\' or from the \'DB Diagram\'" onClick="alert(this.getAttribute(\'title\'))"></span>
				</div>
				<ul class="mytree">
					<li>
						<label>Root</label>
						<ul layer_url="' . $choose_bean_layer_files_from_file_manager_url . '"></ul>
					</li>
				</ul>
				<div class="button">
					<input type="button" value="Update" onClick="MyFancyPopup.settings.updateFunction(this)" />
				</div>
			</div>';
		}
		
		if (isset($business_logic_brokers)) {
			$t = count($business_logic_brokers);
			
			$html .='<div id="choose_business_logic_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
				<div class="title">Choose a Business Logic Service</div>
				<div class="broker' . ($t == 1 ? " single_broker" : "") . '">
					<label>Broker:</label>
					<select onChange="updateLayerUrlFileManager(this)">';
			
			for ($i = 0; $i < $t; $i++) {
				$b = $business_logic_brokers[$i];
				$html .= '<option bean_file_name="' . $b[1] . '" bean_name="' . $b[2] . '" value="' . $b[0] . '">' . $b[0] . ($b[2] ? '' : ' (Rest)') . '</option>';
			}
			$html .= '
					</select>
				</div>
				<ul class="mytree">
					<li>
						<label>Root</label>
						<ul layer_url="' . $choose_bean_layer_files_from_file_manager_url . '"></ul>
					</li>
				</ul>
				<div class="businesslogic">
					<label>Business Logic Service:</label>
					<select></select>
				</div>
				<div class="button">
					<input type="button" value="Update" onClick="MyFancyPopup.settings.updateFunction(this)" />
				</div>
			</div>';
		}
		
		if (isset($presentation_brokers)) {
			$t = count($presentation_brokers);
			
			$html .='<div id="choose_presentation_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
				<div class="title">Choose a Page</div>
				<div class="broker' . ($t == 1 ? " single_broker" : "") . '">
					<label>Broker:</label>
					<select onChange="updateLayerUrlFileManager(this)">';
			
			for ($i = 0; $i < $t; $i++) {
				$b = $presentation_brokers[$i];
				$html .= '<option bean_file_name="' . $b[1] . '" bean_name="' . $b[2] . '" value="' . $b[0] . '">' . $b[0] . ($b[2] ? '' : ' (Rest)') . '</option>';
			}
			$html .= '
					</select>
				</div>
				<ul class="mytree">
					<li>
						<label>Root</label>
						<ul layer_url="' . $choose_bean_layer_files_from_file_manager_url . '"></ul>
					</li>
				</ul>
				<div class="button">
					<input type="button" value="Update" onClick="MyFancyPopup.settings.updateFunction(this)" />
				</div>
			</div>';
			
			$html .= self::getChoosePresentationPageUrlFromFileManagerPopupHtml($choose_bean_layer_files_from_file_manager_url, $presentation_brokers);
		}
		
		return $html;
	}
	
	private static function getChooseFromFileManagerPopupHtmlByLayer($bean_name, $bean_file_name, $choose_bean_layer_files_from_file_manager_url, $choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, $db_brokers, $data_access_brokers, $ibatis_brokers, $hibernate_brokers, $business_logic_brokers, $presentation_brokers) {
		$bean_label = self::getBeanLabelFromChooseFromFileManagerPopupHtml($bean_name, $bean_file_name, $business_logic_brokers, $data_access_brokers, $ibatis_brokers, $hibernate_brokers, $presentation_brokers);
		
		$my_tree_html = '
			<ul class="mytree">
				<li>
					<label>' . $bean_label . '</label>
					<ul url="' . str_replace("#bean_file_name#", $bean_file_name, str_replace("#bean_name#", $bean_name, $choose_bean_layer_files_from_file_manager_url)) . '"></ul>
				</li>
				<!--li>
					<label>DAO</label>
					<ul url="' . $choose_dao_files_from_file_manager_url . '"></ul>
				</li-->
				<li>
					<label>LIB</label>
					<ul url="' . $choose_lib_files_from_file_manager_url . '"></ul>
				</li>
				<li>
					<label>VENDOR</label>
					<ul url="' . $choose_vendor_files_from_file_manager_url . '"></ul>
				</li>
			</ul>';
	
		$html = self::getChoosePropertyVariableFromFileManagerPopupHtml($my_tree_html) . '
			
			<div id="choose_method_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
				<div class="title">Choose a Method</div>
				' . $my_tree_html . '
				<div class="method">
					<label>Method:</label>
					<select></select>
				</div>
				<div class="button">
					<input type="button" value="Update" onClick="MyFancyPopup.settings.updateFunction(this)" />
				</div>
			</div>
	
			<div id="choose_function_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
				<div class="title">Choose a Function</div>
				' . $my_tree_html . '
				<div class="function">
					<label>Function:</label>
					<select></select>
				</div>
				<div class="button">
					<input type="button" value="Update" onClick="MyFancyPopup.settings.updateFunction(this)" />
				</div>
			</div>
	
			<div id="choose_file_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
				<div class="title">Choose a File</div>
				' . $my_tree_html . '
				<div class="button">
					<input type="button" value="Update" onClick="MyFancyPopup.settings.updateFunction(this)" />
				</div>
			</div>
	
			<div id="choose_folder_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
				<div class="title">Choose a Folder</div>
				' . $my_tree_html . '
				<div class="button">
					<input type="button" value="Update" onClick="MyFancyPopup.settings.updateFunction(this)" />
				</div>
			</div>
	
			<div id="choose_block_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
				<div class="title">Choose a Block</div>
				<ul class="mytree">
					<li>
						<label>' . $bean_label . '</label>
						<ul url="' . str_replace("#bean_file_name#", $bean_file_name, str_replace("#bean_name#", $bean_name, $choose_bean_layer_files_from_file_manager_url)) . '"></ul>
					</li>
				</ul>
				<div class="button">
					<input type="button" value="Update" onClick="MyFancyPopup.settings.updateFunction(this)" />
				</div>
			</div>
	
			<div id="choose_view_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
				<div class="title">Choose a View</div>
				<ul class="mytree">
					<li>
						<label>' . $bean_label . '</label>
						<ul url="' . str_replace("#bean_file_name#", $bean_file_name, str_replace("#bean_name#", $bean_name, $choose_bean_layer_files_from_file_manager_url)) . '"></ul>
					</li>
				</ul>
				<div class="button">
					<input type="button" value="Update" onClick="MyFancyPopup.settings.updateFunction(this)" />
				</div>
			</div>';
		
		return $html;
	}
	
	private static function getChooseFromFileManagerPopupHtmlForAllLayers($choose_bean_layer_files_from_file_manager_url, $choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, $business_logic_brokers, $presentation_brokers) {
		$my_tree_html = '
			<ul class="mytree">';
		
		if (isset($business_logic_brokers))
			foreach ($business_logic_brokers as $b) {
				$my_tree_html .= '
				<li>
					<label>' . $b[0] . ($b[2] ? '' : ' (Rest)') . '</label>
					<ul url="' . str_replace("#bean_file_name#", $b[1], str_replace("#bean_name#", $b[2], $choose_bean_layer_files_from_file_manager_url)) . '"></ul>
				</li>';
			}
		
		if (isset($presentation_brokers))
			foreach ($presentation_brokers as $b) {
				$my_tree_html .= '
				<li>
					<label>' . $b[0] . ($b[2] ? '' : ' (Rest)') . '</label>
					<ul url="' . str_replace("#bean_file_name#", $b[1], str_replace("#bean_name#", $b[2], $choose_bean_layer_files_from_file_manager_url)) . '"></ul>
				</li>';
			}
		
		$my_tree_html .= '
				<!--li>
					<label>DAO</label>
					<ul url="' . $choose_dao_files_from_file_manager_url . '"></ul>
				</li-->
				<li>
					<label>LIB</label>
					<ul url="' . $choose_lib_files_from_file_manager_url . '"></ul>
				</li>
				<li>
					<label>VENDOR</label>
					<ul url="' . $choose_vendor_files_from_file_manager_url . '"></ul>
				</li>
			</ul>';
	
		$html = self::getChoosePropertyVariableFromFileManagerPopupHtml($my_tree_html) . '
			
			<div id="choose_method_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
				<div class="title">Choose a Method</div>
				' . $my_tree_html . '
				<div class="method">
					<label>Method:</label>
					<select></select>
				</div>
				<div class="button">
					<input type="button" value="Update" onClick="MyFancyPopup.settings.updateFunction(this)" />
				</div>
			</div>
	
			<div id="choose_function_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
				<div class="title">Choose a Function</div>
				' . $my_tree_html . '
				<div class="function">
					<label>Function:</label>
					<select></select>
				</div>
				<div class="button">
					<input type="button" value="Update" onClick="MyFancyPopup.settings.updateFunction(this)" />
				</div>
			</div>
	
			<div id="choose_file_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
				<div class="title">Choose a File</div>
				' . $my_tree_html . '
				<div class="button">
					<input type="button" value="Update" onClick="MyFancyPopup.settings.updateFunction(this)" />
				</div>
			</div>
	
			<div id="choose_folder_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
				<div class="title">Choose a Folder</div>
				' . $my_tree_html . '
				<div class="button">
					<input type="button" value="Update" onClick="MyFancyPopup.settings.updateFunction(this)" />
				</div>
			</div>
	
			<div id="choose_block_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
				<div class="title">Choose a Block</div>
				<ul class="mytree">';
		
		if (isset($presentation_brokers))
			foreach ($presentation_brokers as $b) {
				$html .= '
					<li>
						<label>' . $b[0] . ($b[2] ? '' : ' (Rest)') . '</label>
						<ul url="' . str_replace("#bean_file_name#", $b[1], str_replace("#bean_name#", $b[2], $choose_bean_layer_files_from_file_manager_url)) . '"></ul>
					</li>';
			}
		
		$html .= '
				</ul>
				<div class="button">
					<input type="button" value="Update" onClick="MyFancyPopup.settings.updateFunction(this)" />
				</div>
			</div>
	
			<div id="choose_view_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
				<div class="title">Choose a View</div>
				<ul class="mytree">';
		
		if (isset($presentation_brokers))
			foreach ($presentation_brokers as $b) {
				$html .= '
					<li>
						<label>' . $b[0] . ($b[2] ? '' : ' (Rest)') . '</label>
						<ul url="' . str_replace("#bean_file_name#", $b[1], str_replace("#bean_name#", $b[2], $choose_bean_layer_files_from_file_manager_url)) . '"></ul>
					</li>';
			}
		
		$html .= '
				</ul>
				<div class="button">
					<input type="button" value="Update" onClick="MyFancyPopup.settings.updateFunction(this)" />
				</div>
			</div>';
		
		return $html;
	}
	
	private static function getChoosePropertyVariableFromFileManagerPopupHtml($my_tree_html) {
		return '<div id="choose_property_variable_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
				<div class="title">Choose a Variable</div>
				<div class="type">
					<label>Variable Type:</label>
					<select onChange="onChangePropertyVariableType(this)">
						<option value="new_var">New Variable</option>
						<option value="class_prop_var">Class Property Variable</option>
						<option value="existent_var">Existent Variable</option>
					</select>
				</div>
				<div class="variable_type new_var">
					<div class="scope">
						<label>Scope:</label>
						<select onChange="onChangePropertyVariableScope(this)">
							<option value="">Local variable</option>
							<option value="_GET">Variable from URL</option>
							<option value="_POST">Variable from POST form</option>
							<option value="_REQUEST">Variable from REQUEST form</option>
							<option value="_FILES">Variable from Files</option>
							<option value="_COOKIE">Variable from Cookies</option>
							<option value="_ENV">Environment variable</option>
							<option value="_SERVER">Server variable</option>
							<option value="_SESSION">Session variable</option>
							<option value="GLOBALS">Global variable</option>
							<option value="GLOBALS[logged_user_id]" is_final_var title="Note that you need to call previously the User Atuhentication Module, in order to use this variable properly." style="display:none">Logged User Id</option>
						</select>
					</div>
					<div class="name">
						<label>Name:</label>
						<input />
						<span class="icon add" onClick="addNewVarSubGroupToProgrammingTaskChooseCreatedVariablePopup(this)" title="Add a sub group">Add</span>
						<ul class="sub_group"></ul>
					</div>
				</div>
				<div class="variable_type class_prop_var" style="display:none">
					' . $my_tree_html . '
					<div class="property">
						<label>Property:</label>
						<select></select>
					</div>
				</div>
				<div class="variable_type existent_var" style="display:none">
					<div class="variable">
						<label>Variable:</label>
						<select></select>
					</div>
				</div>
				
				<div class="variable_settings new_var existent_var">
					<div class="convert_to_hash_tag">
						<label>Convert to HashTag:</label>
						<input type="checkbox" value="1" />
					</div>
				</div>
				
				<div class="button">
					<input type="button" value="Update" onClick="MyFancyPopup.settings.updateFunction(this)" />
				</div>
			</div>';
	}
	
	private static function getBeanLabelFromChooseFromFileManagerPopupHtml($bean_name, $bean_file_name, $business_logic_brokers, $data_access_brokers, $ibatis_brokers, $hibernate_brokers, $presentation_brokers) {
		$types_brokers = array($business_logic_brokers, $data_access_brokers, $ibatis_brokers, $hibernate_brokers, $presentation_brokers);
		
		foreach ($types_brokers as $type_brokers)
			if ($type_brokers)
				foreach ($type_brokers as $broker_props) {
					$broker_name = $broker_props[0];
					$broker_bean_file_name = $broker_props[1];
					$broker_bean_name = $broker_props[2];
					
					if ($broker_bean_file_name == $bean_file_name && $broker_bean_name == $bean_name)
						return $broker_name;
				}
		
		return $bean_name;
	}
	
	private static function getChoosePresentationPageUrlFromFileManagerPopupHtml($choose_bean_layer_files_from_file_manager_url, $presentation_brokers) {
		$html = '';
		
		if (isset($presentation_brokers)) {
			$t = count($presentation_brokers);
			
			$html .='<div id="choose_page_url_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
				<div class="title">Choose a Page</div>
				<div class="broker' . ($t == 1 ? " single_broker" : "") . '">
					<label>Broker:</label>
					<select onChange="updateLayerUrlFileManager(this)">';
			
			for ($i = 0; $i < $t; $i++) {
				$b = $presentation_brokers[$i];
				$html .= '<option bean_file_name="' . $b[1] . '" bean_name="' . $b[2] . '" value="' . $b[0] . '">' . $b[0] . ($b[2] ? '' : ' (Rest)') . '</option>';
			}
			$html .= '
					</select>
				</div>
				<ul class="mytree">
					<li>
						<label>Root</label>
						<ul layer_url="' . $choose_bean_layer_files_from_file_manager_url . '"></ul>
					</li>
				</ul>
				<div class="query_string_attributes">
					<label>Query String: <span class="icon add" onClick="addUrlQueryStringAttribute(this)">Add</span></label>
					<ul>
						<li class="empty_query_string_attributes">No query attributes yet...</li>
					</ul>
				</div>
				<div class="button">
					<input type="button" value="Update" onClick="IncludePageUrlFancyPopup.settings.updateFunction(this)" />
				</div>
			</div>
			
			<div id="choose_image_url_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
				<div class="title">Choose an Image</div>
				<div class="broker' . ($t == 1 ? " single_broker" : "") . '">
					<label>Broker:</label>
					<select onChange="updateLayerUrlFileManager(this)">';
			
			for ($i = 0; $i < $t; $i++) {
				$b = $presentation_brokers[$i];
				$html .= '<option bean_file_name="' . $b[1] . '" bean_name="' . $b[2] . '" value="' . $b[0] . '">' . $b[0] . ($b[2] ? '' : ' (Rest)') . '</option>';
			}
			$html .= '
					</select>
				</div>
				<ul class="mytree">
					<li>
						<label>Root</label>
						<ul layer_url="' . $choose_bean_layer_files_from_file_manager_url . '"></ul>
					</li>
				</ul>
				<div class="button">
					<input type="button" value="Update" onClick="MyFancyPopup.settings.updateFunction(this)" />
				</div>
			</div>
			
			<div id="choose_webroot_file_url_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
				<div class="title">Choose a File</div>
				<div class="broker' . ($t == 1 ? " single_broker" : "") . '">
					<label>Broker:</label>
					<select onChange="updateLayerUrlFileManager(this)">';
			
			for ($i = 0; $i < $t; $i++) {
				$b = $presentation_brokers[$i];
				$html .= '<option bean_file_name="' . $b[1] . '" bean_name="' . $b[2] . '" value="' . $b[0] . '">' . $b[0] . ($b[2] ? '' : ' (Rest)') . '</option>';
			}
			$html .= '
					</select>
				</div>
				<ul class="mytree">
					<li>
						<label>Root</label>
						<ul layer_url="' . $choose_bean_layer_files_from_file_manager_url . '"></ul>
					</li>
				</ul>
				<div class="button">
					<input type="button" value="Update" onClick="MyFancyPopup.settings.updateFunction(this)" />
				</div>
			</div>';
		}
		
		return $html;
	}
	
	public static function getCodeEditorMenuHtml($options) {
		$show_pretty_print = $options["show_pretty_print"] ? $options["show_pretty_print"] : true;
		
		$generate_tasks_flow_from_code_label = $options["generate_tasks_flow_from_code_label"] ? $options["generate_tasks_flow_from_code_label"] : "Generate Diagram from Code";
		$generate_tasks_flow_from_code_func = $options["generate_tasks_flow_from_code_func"] ? $options["generate_tasks_flow_from_code_func"] : "generateTasksFlowFromCode";
		
		$generate_code_from_tasks_flow_label = $options["generate_code_from_tasks_flow_label"] ? $options["generate_code_from_tasks_flow_label"] : "Generate Code From Diagram";
		$generate_code_from_tasks_flow_func = $options["generate_code_from_tasks_flow_func"] ? $options["generate_code_from_tasks_flow_func"] : "generateCodeFromTasksFlow";
		
		return '<ul>
			<li class="editor_settings" title="Open Editor Setings"><a onClick="openEditorSettings()"><i class="icon settings"></i> Open Editor Setings</a></li>
			' . ($show_pretty_print ? '<li class="pretty_print" title="Pretty Print Code"><a onClick="prettyPrintCode()"><i class="icon pretty_print"></i> Pretty Print Code</a></li>' : '') . '
			<li class="set_word_wrap" title="Set Word Wrap"><a onClick="setWordWrap(this)" wrap="0"><i class="icon word_wrap"></i> Word Wrap</i></a></li>
			<li class="separator"></li>
			<li class="generate_tasks_flow_from_code" title="' . $generate_tasks_flow_from_code_label . '"><a onClick="' . $generate_tasks_flow_from_code_func . '(true, {force: true});return false;"><i class="icon generate_tasks_flow_from_code"></i> ' . $generate_tasks_flow_from_code_label . '</a></li>
			<li class="generate_code_from_tasks_flow" title="' . $generate_code_from_tasks_flow_label . '"><a onClick="' . $generate_code_from_tasks_flow_func . '(true, {force: true});return false;"><i class="icon generate_code_from_tasks_flow"></i> ' . $generate_code_from_tasks_flow_label . '</a></li>
			<li class="separator"></li>
			<li class="editor_full_screen" title="Maximize/Minimize Editor Screen"><a onClick="toggleCodeEditorFullScreen(this)"><i class="icon full_screen"></i> Maximize Editor Screen</a></li>
			<li class="separator"></li>
			<li class="auto_save_activation" title="Is Auto Save Active" onClick="toggleAutoSaveCheckbox(this, onTogglePHPCodeAutoSave)"><i class="icon auto_save_activation"></i> <span>Enable Auto Save</span> <input type="checkbox" value="1" /></li>
			<li class="auto_convert_activation" title="Is Auto Convert Active" onClick="toggleAutoConvertCheckbox(this, onTogglePHPCodeAutoConvert)"><i class="icon auto_convert_activation"></i> <span>Enable Auto Convert</span> <input type="checkbox" value="1" /></li>
			<li class="save" title="Save"><a onClick="' . $options["save_func"] . '()"><i class="icon save"></i> Save</a></li>
		</ul>';
	}
	
	public static function getCodeEditorHtml($code, $menu_options, $ui_menu_widgets_html, $user_global_variables_file_path, $user_beans_folder_path, $PEVC, $UserAuthenticationHandler, $bean_name, $bean_file_name, $db_drivers, $choose_bean_layer_files_from_file_manager_url, $get_db_data_url, $create_page_presentation_uis_diagram_block_url, $js_tree_obj_name, $is_full_source = false, $options = null) {
		//echo "<pre>";print_r($_COOKIE);die();
		$reverse_class = $_COOKIE["main_navigator_side"] == "main_navigator_reverse" || $_COOKIE["admin_type"] == "simple" ? "" : "reverse";
		$layout_ui_editor_html = $options["layout_ui_editor_html"];
		$layout_ui_editor_class = $options["layout_ui_editor_class"];
		
		$html = '
			<div class="code_menu" onClick="openSubmenu(this)">
				' . self::getCodeEditorMenuHtml($menu_options) . '
			</div>
			<div class="layout-ui-editor ' . $reverse_class . ' ' . $layout_ui_editor_class . ' fixed-side-properties hide-template-widgets-options">
				<textarea' . ($is_full_source ? ' class="full-source"' : '') . '>' . htmlspecialchars($code, ENT_NOQUOTES) . '</textarea>
				
				' . $layout_ui_editor_html . '
				
				<div class="layout-ui-menu-widgets-backup hidden">
					' . $ui_menu_widgets_html . '
				</div>
			</div>
			<div id="layout_ui_editor_right_container" class="layout_ui_editor_right_container">
				' . self::getTabContentTemplateLayoutTreeHtml($user_global_variables_file_path, $user_beans_folder_path, $PEVC, $UserAuthenticationHandler, $bean_name, $bean_file_name, $choose_bean_layer_files_from_file_manager_url, $js_tree_obj_name) .
				self::getTabContentTemplateLayoutDBDriversTreeHtml($user_global_variables_file_path, $user_beans_folder_path, $PEVC, $UserAuthenticationHandler, $bean_name, $bean_file_name, $db_drivers, $choose_bean_layer_files_from_file_manager_url, $get_db_data_url, $js_tree_obj_name) . '
			</div>
			
			<div id="choose_layout_ui_editor_module_block_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
				<div class="title">Choose</div>
				' . self::getTabContentTemplateLayoutTreeHtml($user_global_variables_file_path, $user_beans_folder_path, $PEVC, $UserAuthenticationHandler, $bean_name, $bean_file_name, $choose_bean_layer_files_from_file_manager_url, "chooseCodeLayoutUIEditorModuleBlockFromFileManagerTree") . '
				
				<div class="button">
					<input type="button" value="Update" onClick="CodeLayoutUIEditorFancyPopup.settings.updateFunction(this)" />
				</div>
			</div>
			
			<div class="myfancypopup db_table_uis_diagram_block with_iframe_title" create_page_presentation_uis_diagram_block_url="' . $create_page_presentation_uis_diagram_block_url . '">
				<iframe></iframe>
			</div>';
		
		return $html;
	}
	
	public static function getTabContentTemplateLayoutTreeHtml($user_global_variables_file_path, $user_beans_folder_path, $EVC, $UserAuthenticationHandler, $bean_name, $bean_file_name, $choose_bean_layer_files_from_file_manager_url, $js_tree_obj_name) {
		$P = $EVC->getPresentationLayer();
		$modules_path = $EVC->getCommonProjectName() . "/" . $P->settings["presentation_modules_path"];
		
		$html = '<script>
			//clones module_folder properties, bc is the same object that the ui["folder"] properties, this is, is the reference object of the ui["folder"] object.
			main_layers_properties.' . $bean_name . '.ui["module_folder"] = JSON.parse(JSON.stringify(main_layers_properties.' . $bean_name . '.ui["module_folder"])); 
			
			//adds the new changes only to the ui["module_folder"] properties
			main_layers_properties.' . $bean_name . '.ui["module_folder"]["attributes"] = {
				folder_path: "#path#",
				module_info_func_name: "showModuleInfoWithSmartPosition",
			};
		</script>
		<ul class="mytree">
			<li data-jstree="{\'icon\':\'cms_module\'}">
				<label>Modules <i class="icon refresh" onClick="refreshFileManagerPopupTreeNodeFromInnerIcon(this, ' . $js_tree_obj_name . ')">Refresh</i></label>
				<ul url="' . str_replace("#path#", $modules_path, str_replace("#bean_file_name#", $bean_file_name, str_replace("#bean_name#", $bean_name, $choose_bean_layer_files_from_file_manager_url))) . '&item_type=presentation&folder_type=module"></ul>
			</li>
			<li data-jstree="{\'icon\':\'blocks_folder\'}">
				<label>Blocks <i class="icon refresh" onClick="refreshFileManagerPopupTreeNodeFromInnerIcon(this, ' . $js_tree_obj_name . ')">Refresh</i></label>
				<ul url="' . str_replace("#bean_file_name#", $bean_file_name, str_replace("#bean_name#", $bean_name, $choose_bean_layer_files_from_file_manager_url)) . '"></ul>
			</li>
		</ul>';
		
		return $html;
	}
	
	public static function getTabContentTemplateLayoutDBDriversTreeHtml($user_global_variables_file_path, $user_beans_folder_path, $EVC, $UserAuthenticationHandler, $bean_name, $bean_file_name, $db_drivers, $choose_bean_layer_files_from_file_manager_url, $get_db_data_url, $js_tree_obj_name) {
		$P = $EVC->getPresentationLayer();
		
		if (!$db_drivers) {
			$db_drivers = WorkFlowBeansFileHandler::getLayerDBDrivers($user_global_variables_file_path, $user_beans_folder_path, $P, true);
		
			//filter db_drivers by $filter_by_layout
			$LayoutTypeProjectHandler = new LayoutTypeProjectHandler($UserAuthenticationHandler, $user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name);
			$LayoutTypeProjectHandler->filterLayerBrokersDBDriversPropsBasedInUrl($db_drivers, $choose_bean_layer_files_from_file_manager_url);
			//echo "<pre>";print_r($db_drivers);die();
		}
		
		//prepare db_driver tree attributes
		if ($db_drivers) {
			$html = '<script>';
		
			foreach ($db_drivers as $db_driver_name => $db_driver_props)
				if ($db_driver_props) //only show local db drivers. The rest db drivers will be ignored.
					$html .= '
			main_layers_properties["' . $db_driver_props[2] . '"] = {
				ui: {
					table: {
						attributes: {
							table: "#name#",
						},
						get_sub_files_url: "' . str_replace("#type#", "", str_replace("#bean_file_name#", $db_driver_props[1], str_replace("#bean_name#", $db_driver_props[2], $get_db_data_url))) . '&table=#table#"
					},
				},
			};';
			
			$html .= '
			</script>
			<ul class="mytree db_drivers_tree">';
			
			$brokers_by_db_driver_name = array();
			$brokers_folder_name = array();
			
			foreach ($db_drivers as $db_driver_name => $db_driver_props) {
				if ($db_driver_props) { //only show local db drivers. The rest db drivers will be ignored.
					//find correspondent db broker folder name
					$broker_name = $brokers_by_db_driver_name[$db_driver_name];
					
					if (!$broker_name) {
						$broker_name = WorkFlowBeansFileHandler::getLayerLocalDBBrokerNameForChildBrokerDBDriver($user_global_variables_file_path, $user_beans_folder_path, $P, $db_driver_name, $found_broker_obj, $found_broker_props);
						
						if (is_a($found_broker_obj, "IDBBrokerClient") && $found_broker_props) {
							$broker_layer_props = WorkFlowBeansFileHandler::getLocalBeanLayerFromBroker($user_global_variables_file_path, $user_beans_folder_path, $found_broker_obj);
							
							if ($broker_layer_props) {
								$broker_folder_name = WorkFlowBeansFileHandler::getLayerObjFolderName($broker_layer_props[2]);
								
								//cache broker folder name for next time
								$brokers_folder_name[$broker_name] = $broker_folder_name;
								$broker_db_drivers = $found_broker_obj->getDBDriversName();
								
								foreach ($broker_db_drivers as $broker_db_driver_name)
									$brokers_by_db_driver_name[$broker_db_driver_name] = $broker_name;
							}
						}
					}
					else
						$broker_folder_name = $brokers_folder_name[$broker_name];
					
					//print db driver
					$html .= '
						<li data-jstree="{\'icon\':\'db_driver\'}" db_driver_name="' . $db_driver_name . '" db_driver_bean_name="' . $db_driver_props[2] . '" db_driver_bean_file_name="' . $db_driver_props[1] . '" db_driver_type="db" db_driver_broker="' . $broker_name . '" db_driver_broker_folder="' . $broker_folder_name . '">
							<label>' . ucwords(str_replace("_", " ", $db_driver_name)) . '</label>
							<ul url="' . str_replace("#type#", "", str_replace("#bean_file_name#", $db_driver_props[1], str_replace("#bean_name#", $db_driver_props[2], $get_db_data_url))) . '"></ul>
						</li>';
				}
				else
					$html .= '
						<li data-jstree="{\'icon\':\'db_driver\'}" db_driver_name="' . $db_driver_name . '">
							<label>' . ucwords(str_replace("_", " ", $db_driver_name)) . ' (Rest)</label>
						</li>';
			}
			
			$html .= '</ul>';
		}
		
		return $html;
	}
	
	public static function getTaskFlowContentHtml($WorkFlowUIHandler, $options) {
		$generate_tasks_flow_from_code_label = $options["generate_tasks_flow_from_code_label"] ? $options["generate_tasks_flow_from_code_label"] : "Generate Diagram from Code";
		$generate_tasks_flow_from_code_func = $options["generate_tasks_flow_from_code_func"] ? $options["generate_tasks_flow_from_code_func"] : "generateTasksFlowFromCode";
		
		$generate_code_from_tasks_flow_label = $options["generate_code_from_tasks_flow_label"] ? $options["generate_code_from_tasks_flow_label"] : "Generate Code From Diagram";
		$generate_code_from_tasks_flow_func = $options["generate_code_from_tasks_flow_func"] ? $options["generate_code_from_tasks_flow_func"] : "generateCodeFromTasksFlow";
		
		$menus = array(
			"Sort Tasks" => array(
				"class" => "sort_tasks",
				"html" => '<a onClick="sortWorkflowTask();return false;"><i class="icon sort"></i> Sort Tasks</a>',
				"childs" => array(
					"Sort Type 1" => array(
						"class" => "sort_tasks", 
						"click" => "sortWorkflowTask(1);return false;"
					),
					"Sort Type 2" => array(
						"class" => "sort_tasks", 
						"click" => "sortWorkflowTask(2);return false;"
					),
					"Sort Type 3" => array(
						"class" => "sort_tasks", 
						"click" => "sortWorkflowTask(3);return false;"
					),
					"Sort Type 4" => array(
						"class" => "sort_tasks", 
						"click" => "sortWorkflowTask(4);return false;"
					),
				)
			),
			1 => array(
				"class" => "separator",
				"title" => " ", 
				"html" => " ", 
			),
			"Flush Cache" => array(
				"class" => "flush_cache", 
				"html" => '<a onClick="flushCache();return false;"><i class="icon flush_cache"></i> Flush Cache</a>',
			),
			"Empty Diagram" => array(
				"class" => "empty_diagram", 
				"html" => '<a onClick="emptyDiagam();return false;"><i class="icon empty_diagram"></i> Empty Diagram</a>',
			),
			2 => array(
				"class" => "separator",
				"title" => " ", 
				"html" => " ", 
			),
			"Zoom In" => array(
				"class" => "zoom_in", 
				"html" => '<a onClick="zoomInDiagram(this);return false;"><i class="icon zoom_in"></i> Zoom In</a>',
			),
			"Zoom Out" => array(
				"class" => "zoom_out", 
				"html" => '<a onClick="zoomOutDiagram(this);return false;"><i class="icon zoom_out"></i> Zoom Out</a>',
			),
			"Zoom" => array(
				"class" => "zoom", 
				"html" => '
				<a onClick="zoomEventPropagationDiagram(this);return false;"><i class="icon zoom"></i> <input type="range" min="0.5" max="1.5" step=".02" value="1" onInput="zoomDiagram(this);return false;" /> <span>100%</span></a>',
			),
			"Zoom Reset" => array(
				"class" => "zoom_reset", 
				"html" => '<a onClick="zoomResetDiagram(this);return false;"><i class="icon zoom_reset"></i> Zoom Reset</a>',
			),
			3 => array(
				"class" => "separator",
				"title" => " ", 
				"html" => " ", 
			),
			$generate_tasks_flow_from_code_label => array(
				"class" => "generate_tasks_flow_from_code", 
				"html" => '<a onClick="' . $generate_tasks_flow_from_code_func . '(true, {force: true});return false;"><i class="icon generate_tasks_flow_from_code"></i> ' . $generate_tasks_flow_from_code_label . '</a>',
			),
			$generate_code_from_tasks_flow_label => array(
				"class" => "generate_code_from_tasks_flow", 
				"html" => '<a onClick="' . $generate_code_from_tasks_flow_func . '(true, {force: true});return false;"><i class="icon generate_code_from_tasks_flow"></i> ' . $generate_code_from_tasks_flow_label . '</a>',
			),
			4 => array(
				"class" => "separator",
				"title" => " ", 
				"html" => " ", 
			),
			"Flip Panels Side" => array(
				"class" => "flip_tasks_flow_panels_side", 
				"html" => '<a onClick="flipTasksFlowPanelsSide(this);return false;"><i class="icon flip_tasks_flow_panels_side"></i> Flip Panels Side</a>',
			),
			"Maximize/Minimize Editor Screen" => array(
				"class" => "tasks_flow_full_screen", 
				"html" => '<a onClick="toggleTaskFlowFullScreen(this);return false;"><i class="icon full_screen"></i> Maximize Editor Screen</a>',
			),
			5 => array(
				"class" => "separator",
				"title" => " ", 
				"html" => " ", 
			),
			"Auto Save On" => array(
				"class" => "auto_save_activation", 
				"title" => "Is Auto Save Active", 
				"html" => '<a onClick="toggleAutoSaveCheckbox(this, onTogglePHPCodeAutoSave)"><i class="icon auto_save_activation"></i> <span>Enable Auto Save</span> <input type="checkbox" value="1" /></a>'
			),
			"Auto Convert On" => array(
				"class" => "auto_convert_activation", 
				"title" => "Is Auto Convert Active", 
				"html" => '<a onClick="toggleAutoConvertCheckbox(this, onTogglePHPCodeAutoConvert)"><i class="icon auto_convert_activation"></i> <span>Enable Auto Convert</span> <input type="checkbox" value="1" /></a>'
			),
			"Save" => array(
				"class" => "save", 
				"html" => '<a onClick="' . $options["save_func"] . '();return false;"><i class="icon save"></i> Save</a>',
			),
		);
		$WorkFlowUIHandler->setMenus($menus);
	
		return $WorkFlowUIHandler->getContent();
	}
	
	public static function validateHtmlTagsBeforeConvertingToCodeTags($code) {
		$valid = true;
		
		//check if there is any php code inside of the style and script tags
		preg_match_all("/(<style|<\/style|<script|<\/script|<\?|\?>)/i", $code, $matches);
		$matches = $matches[0];
		
		$open_php = $open_css = $open_js = false;
		
		$t = count($matches);
		for ($i = 0; $i < $t; $i++) {
			$m = $matches[$i];
			
			if (strpos($m, "<?") !== false) {
				$open_php = true;
				
				if ($open_css || $open_js) {
					$valid = false;
					break;
				}
			}
			else if (strpos($m, "?>") !== false) {
				$open_php = false;
			}
			else if (strpos($m, "<style") !== false) {
				$open_css = true;
				
				if ($open_php || $open_js) {
					$valid = false;
					break;
				}
			}
			else if (strpos($m, "</style") !== false) {
				$open_css = false;
			}
			else if (strpos($m, "<script") !== false) {
				$open_js = true;
				
				if ($open_css || $open_php) {
					$valid = false;
					break;
				}
			}
			else if (strpos($m, "</script") !== false) {
				$open_js = false;
			}
		}
		
		//checks if there is any php code inside of html tags
		if ($valid) {
			//gets php tags inside of attributes //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars.
			preg_match("/<([\w]+)([^>]*)(<\?)(.+)(\?>)([^>]*)>/u", $code, $matches); //html tags don't have accents
			$valid = count($matches) == 0;
		}
		
		return $valid;
	}
	
	public static function convertHtmlTagsToCodeTags($code) {
		//replace php tags inside of attributes //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars.
		$code = preg_replace("/<([\w]+)([^>]*)(<\?)(.+)(\?>)([^>]*)>/u", "<$1$2&lt; ?$4? &gt;$6>", $code); //html tags don't have accents
		
		$code = preg_replace("/<script\s+([^>]*)src=(\"|')([^\"']+)(\"|')([^>]*)>(.*)<\/script>/iu", '<pre><code class="language-html">&lt; script $1src=$2$3$4$5&gt;$6&lt; /script&gt;</code></pre>', $code);//replace script tags which have src attributes.
	
		$code = str_replace("?>", '</code></pre>', str_replace(array("<? ", "<?php "), '<pre><code class="language-php">', str_replace("<?=", '<pre><code class="language-php">echo ', $code)));
		$code = preg_replace("/<\/style>/i", '</code></pre>', preg_replace("/<style([^>]*)>/i", '<pre><code class="language-css">', $code));
		$code = preg_replace("/<\/script>/i", '</code></pre>', preg_replace("/<script([^>]*)>/i", '<pre><code class="language-javascript">', $code));
	
		return $code;
	}
	
	public static function convertCodeTagsToHtmlTags($html) {
		$html = preg_replace("/<pre>\s+<code\s+/i", "<pre><code ", $html);
		
		$html = preg_replace('/<pre><code\s+class="language-html">(&lt;|<)(\s*)script\s+([^>]*)src=("|\')([^"\']+)("|\')([^>]*)(&gt;|>)(.*)(&lt;|<)(\s*)\/script(\s*)(&gt;|>)<\/code><\/pre>/iu', '<script $3src=$4$5$6$7>$9</script>', $html);//replace script tags which have src attributes. 
		
		$html = preg_replace("/<([\w]+)([^>]*)((&lt;|<)(\s*)\?)(.+)(\?(\s*)(&gt;|>))([^>]*)>/u", "<$1$2<?$6?>$10>", $html);//replace php tags inside of attributes. '/u' means with accents and ç too. '/u' converts unicode to accents chars.
		
		$html = preg_replace('/<pre><code\s+class="language-php">(.*)<\/code><\/pre>/iu', "<? $1 ?>", $html); //'/u' means with accents and ç too. '/u' converts unicode to accents chars.
		$html = preg_replace('/<pre><code\s+class="language-css">(.*)<\/code><\/pre>/iu', "<style>\n$1\n</style>", $html); //'/u' means with accents and ç too. '/u' converts unicode to accents chars.
		$html = preg_replace('/<pre><code\s+class="language-javascript">(.*)<\/code><\/pre>/iu', "<script>\n$1\n</script>", $html); //'/u' means with accents and ç too. '/u' converts unicode to accents chars.
		
		$html = preg_replace('/<pre><code\s+class="language-php">([^<]+)/iu', "<? $1 ?>", $html); //'/u' means with accents and ç too. '/u' converts unicode to accents chars.
		$html = preg_replace('/<pre><code\s+class="language-css">([^<]+)/iu', "<style>\n$1\n</style>", $html); //'/u' means with accents and ç too. '/u' converts unicode to accents chars.
		$html = preg_replace('/<pre><code\s+class="language-javascript">([^<]+)/iu', "<script>\n$1\n</script>", $html); //'/u' means with accents and ç too. '/u' converts unicode to accents chars.
		$html = str_replace('</code></pre>', "", $html);
		
		$html = str_replace('&gt;', ">", $html);
		$html = str_replace('&lt;', "<", $html);
		
		return $html;
	}

	public static function getHtmlTagProps($code, $tag_name, $options = false) {
		$props = array(
			"html_attributes" => "",
			"inline_code" => "",
		);
	
		$start_pos = stripos($code, "<$tag_name>");
	
		if ($start_pos !== false) {
			$html_attributes = "";
			$end_pos = $start_pos + strlen($tag_name) + 1;
		}
		else {
			$tag_length = strlen($tag_name);
			$start_pos = stripos($code, "<$tag_name ");
			
			//check if there is any tab after the tag name
			if ($start_pos === false) {
				preg_match("/<$tag_name\s+/", $code, $matches, PREG_OFFSET_CAPTURE);
				$start_pos = $matches ? $matches[0][1] : false;
			}
			
			//check if there is any php code after the tag name
			if ($start_pos === false) {
				$start_pos = stripos($code, "<$tag_name<?"); //note that we can have something like '<body<?= 1;...'
				
				if ($start_pos !== false) {
					$php_pos = stripos($code, "?>", $start_pos + $tag_length + 3);
					$php_pos = $php_pos !== false ? $php_pos : strlen($code);
					$tag_length = ($php_pos - $start_pos) - 1;
				}
			}
			
			//if no tag name return empty
			if ($start_pos === false) {
				$html_attributes = "";
				$end_pos = false;
			}
			else {
				$attrs_start_pos = $start_pos + $tag_length + 2;
				$end_pos = strpos($code, ">", $attrs_start_pos);
				
				$php_tag_open_pos = strpos($code, "<?", $attrs_start_pos);
				if ($php_tag_open_pos !== false && $php_tag_open_pos < $end_pos) {
					$php_tag_close_pos = strpos($code, "?>", $php_tag_open_pos + 2);
					$end_pos = $php_tag_close_pos !== false ? strpos($code, ">", $php_tag_close_pos + 2) : false;
				}
				
				$html_attributes = $end_pos !== false && $end_pos > $attrs_start_pos ? substr($code, $attrs_start_pos, $end_pos - $attrs_start_pos) : "";
				$props["html_attributes"] = $html_attributes;
			}
		}
	
		if ($options["get_inline_code"]) {
			if ($start_pos !== false && $end_pos !== false) {
				$close_pos = strripos($code, "</$tag_name>", $end_pos + 1);
				
				//check if close tag has white space or php code after the tag name
				if ($close_pos === false) {
					preg_match("/<\/$tag_name(\s|<)/", $code, $matches, PREG_OFFSET_CAPTURE); //\s: white space; '<' is for <?...
					$close_pos = $matches ? $matches[0][1] : false;
				}
				
				if ($close_pos !== false) {
					$inline_code = substr($code, $end_pos + 1, $close_pos - $end_pos - 1);
			
					$props["inline_code"] = $inline_code;
				}
			}
		}
	
		return $props;
	}
}
?>
