<?php
$main_app_folder_path = dirname(dirname(dirname(dirname(dirname(dirname(__DIR__))))));
$main_app_folder_path = basename($main_app_folder_path) == "app" ? $main_app_folder_path : dirname($main_app_folder_path); //it measn it is inside of the __system

if (!class_exists("CssAndJSFilesOptimizer")) //Note that this is very important, bc this file is called from the __system files and we have multiple installations of bloxtor with the common/webroot folder shared across that installations (as a symbolic link) then this class may be already included from another installation.
	include_once $main_app_folder_path . "/lib/org/phpframework/util/web/html/CssAndJSFilesOptimizer.php";

$GLOBALS["layout_ui_editor_widgets_files_included"] = array();

function getDefaultWidgetsPriorityFiles() {
	return array(
		"containers", 
		"generic" => array("href", "image", "video", "list", "listitem", "iframe"), 
		"table" => array("table", "thead", "tbody", "tfoot", "tr", "th", "td"), 
		"form" => array("form", "input", "textarea", "select", "checkbox", "radio"), 
		"advanced" => array(
			"combined_html" => array(),
			"include" => array("link", "style", "script"),
			"code" => array("php", "ptl"),
		),
	);
}

function scanWidgets($dir, $options = null) {
	$result = array(); 
	
	if ($dir && is_dir($dir)) {
		$files = scandir($dir);
		
		//sort files
		if ($options && !empty($options["priority_files"])) {
			$priority_files = $options["priority_files"];
			$new_files = array();
			
			foreach ($priority_files as $k => $v) {
				$file_name = is_numeric($k) ? $v : $k;
				
				if ($file_name) {
					$index = array_search($file_name, $files);
					
					if (is_numeric($index)) {
						$new_files[] = $files[$index];
						$files[$index] = null;
					}
				}
			}
			
			$files = array_merge($new_files, $files);
		}
		
		foreach ($files as $idx => $value)
			if ($value && !in_array($value, array(".", ".."))) { 
				if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
					if (file_exists($dir . DIRECTORY_SEPARATOR . $value . DIRECTORY_SEPARATOR . "settings.xml")) 
						$result[$value] = $dir . DIRECTORY_SEPARATOR . $value . DIRECTORY_SEPARATOR . "settings.xml";
					else {
						$sub_options = $options;
						$sub_options["priority_files"] = isset($priority_files[$value]) ? $priority_files[$value] : null;
						
						$sub_result = scanWidgets($dir . DIRECTORY_SEPARATOR . $value, $sub_options); 
						
						if ($sub_result)
							$result[$value] = $sub_result;
					}
				}
				else if (strtolower(substr($value, -4)) == ".xml")
					$result[$value] = $dir . DIRECTORY_SEPARATOR . $value;
			}
		
	}
	
	return $result;
}

function filterWidgets($widgets, $widgets_root_path, $widgets_root_url, $options, $parent_prefix = "") {
	if ($options) {
		$allowed_widgets = $options && !empty($options["allowed_widgets"]) ? $options["allowed_widgets"] : null;
		$avoided_widgets = $options && !empty($options["avoided_widgets"]) ? $options["avoided_widgets"] : null;
		
		if ($avoided_widgets || $allowed_widgets)
			foreach ($widgets as $name => $sub_files) {
				if (is_array($sub_files)) { //is menu group
					$sub_widgets = filterWidgets($sub_files, $widgets_root_path, $widgets_root_url, $options, "$parent_prefix$name/");
					
					if ($sub_widgets)
						$widgets[$name] = $sub_widgets;
					else
						unset($widgets[$name]);
				}
				else {
					$widget = parseWidgetFile($sub_files, $widgets_root_path, $widgets_root_url);
					$tag = isset($widget["tag"]) ? $widget["tag"] : null;
					
					if ($avoided_widgets && in_array($tag, $avoided_widgets))
						unset($widgets[$name]);
					else if ($allowed_widgets && !in_array($tag, $allowed_widgets) && !in_array("$parent_prefix$tag", $allowed_widgets))
						unset($widgets[$name]);
				}
			}
	}
	
	return $widgets;
}

function getMenuWidgetsHTML($widgets, $widgets_root_path, $widgets_root_url, $webroot_cache_folder_path = "", $webroot_cache_folder_url = "") {
	if ($widgets) {
		$widgets_html = $groups_html = '';
		$menu_widgets_css_files = array();
		$menu_widgets_js_files = array();
		
		//prepare widgets html
		foreach ($widgets as $name => $sub_files) {
			if (is_array($sub_files)) { //is menu group
				$group_class = "group-" . strtolower(str_replace(array(" ", "_"), "-", $name));
				
				$groups_html .= '
				<li class="group ' . $group_class . ' group-open">
					<div class="group-title"><i class="zmdi zmdi-caret-down toggle"></i>' . ucwords(str_replace("_", " ", $name)) . '</div>
					<ul>';
				
				$groups_html .= getMenuWidgetsHTML($sub_files, $widgets_root_path, $widgets_root_url, $webroot_cache_folder_path, $webroot_cache_folder_url);
				
				$groups_html .= '</ul>
				</li>';
			}
			else //is menu widget
				$widgets_html .= getMenuWidgetHTML($sub_files, $widgets_root_path, $widgets_root_url, $menu_widgets_css_files, $menu_widgets_js_files); //In this case the $sub_files var corresponds to the file_path
		}
		
		//prepare widgets css and js files
		//remove repeated files
		$menu_widgets_css_files = array_unique($menu_widgets_css_files); 
		$menu_widgets_js_files = array_unique($menu_widgets_js_files); 
		$wcfp = $webroot_cache_folder_path ? $webroot_cache_folder_path . "/files/" : null;
		$wcfu = $webroot_cache_folder_url ? $webroot_cache_folder_url . "/files/" : null;
		
		$CssAndJSFilesOptimizer = new CssAndJSFilesOptimizer($wcfp, $wcfu);
		$head = $CssAndJSFilesOptimizer->getCssAndJSFilesHtml($menu_widgets_css_files, $menu_widgets_js_files);
		
		return $head . $widgets_html . $groups_html;
	}
}

function parseWidgetFile($file_path, $widgets_root_path, $widgets_root_url) {
	if (file_exists($file_path)) {
		$widget_folder_path = substr(dirname($file_path), strlen($widgets_root_path)) . DIRECTORY_SEPARATOR;
		$widget_folder_path = substr($widget_folder_path, 0, 1) == "/" ? substr($widget_folder_path, 1) : $widget_folder_path;
		$widget_root_url = $widgets_root_url . $widget_folder_path;
		
		$content = file_get_contents($file_path);
		$content = str_replace("#widget_webroot_url#", $widget_root_url, $content); //replace url
		$widget = XMLFileParser::parseXMLContentToArray($content, false, $file_path, false, false);
		$widget = MyXML::complexArrayToBasicArray($widget);
		$widget = isset($widget["widget"]) ? $widget["widget"] : null;
		
		//If the menu_widget xml node is empty but has attributes, the MyXML::complexArrayToBasicArray will merge the attributes in to the node without the @ property. So we must prevent this case, so the widget be prepared for the getMenuWidgetHTML method
		if (isset($widget["menu_widget"]) && is_array($widget["menu_widget"]) && !array_key_exists("@", $widget["menu_widget"]))
			$widget["menu_widget"] = array("@" => $widget["menu_widget"]);
		
		//If the template_widget xml node is empty but has attributes, the MyXML::complexArrayToBasicArray will merge the attributes in to the node without the @ property. So we must prevent this case, so the widget be prepared for the getMenuWidgetHTML method
		if (isset($widget["template_widget"]) && is_array($widget["template_widget"]) && !array_key_exists("@", $widget["template_widget"]))
			$widget["template_widget"] = array("@" => $widget["template_widget"]);
	}
	
	return isset($widget) ? $widget : null;
}

function getMenuWidgetHTML($file_path, $widgets_root_path, $widgets_root_url, &$menu_widgets_css_files, &$menu_widgets_js_files) {
	$widget = parseWidgetFile($file_path, $widgets_root_path, $widgets_root_url);
	$tag = isset($widget["tag"]) ? trim(str_replace(" ", "", $widget["tag"])) : null;
	
	if ($tag) {
		$label = !empty($widget["label"]) ? $widget["label"] : ucwords(trim(str_replace("_", " ", $tag)));
		$settings = !empty($widget["settings"]) ? $widget["settings"] : array();
		$callbacks = !empty($settings["callback"]) ? $settings["callback"] : array();
		$menu_title = $label;
		$menu_attrs = "";
		
		//Preparing menu widget and correspondent callbacks
		$menu_class = " menu-widget-$tag " . (isset($settings["menu_class"]) ? $settings["menu_class"] : null);
		
		if (isset($widget["menu_widget"]) && is_array($widget["menu_widget"])) {
			$menu_attributes = isset($widget["menu_widget"]["@"]) ? $widget["menu_widget"]["@"] : null;
			$menu_html = isset($widget["menu_widget"]["value"]) ? $widget["menu_widget"]["value"] : null;
			
			if ($menu_attributes) {
				if (!empty($menu_attributes["class"])) {
					$menu_class .= " " . $menu_attributes["class"];
					unset($menu_attributes["class"]);
				}
				
				if (!empty($menu_attributes["title"])) {
					$menu_title .= $menu_attributes["title"];
					unset($menu_attributes["title"]);
				}
			}
			
			$menu_attrs .= convertWidgetXmlAttributesToHtmlAttributes($menu_attributes);
		}
		else
			$menu_html = isset($widget["menu_widget"]) ? $widget["menu_widget"] : null;
		
		if (!$menu_html)
			$menu_html = '<span>' . $label . '</span>';
		
		$menu_attrs .= !empty($settings["create_widget_class"]) ? ' data-create-widget-class="' . $settings["create_widget_class"] . '"' : '';
		$menu_attrs .= !empty($settings["create_widget_func"]) ? ' data-create-widget-func="' . $settings["create_widget_func"] . '"' : '';
		$menu_attrs .= !empty($settings["template_header_class"]) ? ' data-template-header-class="' . $settings["template_header_class"] . '"' : '';
		$menu_attrs .= !empty($settings["menu_settings_class"]) ? ' data-menu-settings-class="' . $settings["menu_settings_class"] . '"' : '';
		$menu_attrs .= !empty($settings["menu_layer_class"]) ? ' data-menu-layer-class="' . $settings["menu_layer_class"] . '"' : '';
		
		$menu_attrs .= !empty($callbacks["on_open_widget_header_func"]) ? ' data-on-open-widget-header-func="' . $callbacks["on_open_widget_header_func"] . '"' : '';
		$menu_attrs .= !empty($callbacks["on_close_widget_header_func"]) ? ' data-on-close-widget-header-func="' . $callbacks["on_close_widget_header_func"] . '"' : '';
		$menu_attrs .= !empty($callbacks["on_open_droppable_header_func"]) ? ' data-on-open-droppable-header-func="' . $callbacks["on_open_droppable_header_func"] . '"' : '';
		$menu_attrs .= !empty($callbacks["on_close_droppable_header_func"]) ? ' data-on-close-droppable-header-func="' . $callbacks["on_close_droppable_header_func"] . '"' : '';
		
		$menu_attrs .= !empty($callbacks["on_drag_start_func"]) ? ' data-on-drag-start-func="' . $callbacks["on_drag_start_func"] . '"' : '';
		$menu_attrs .= !empty($callbacks["on_drag_helper_func"]) ? ' data-on-drag-helper-func="' . $callbacks["on_drag_helper_func"] . '"' : '';
		$menu_attrs .= !empty($callbacks["on_drag_stop_func"]) ? ' data-on-drag-stop-func="' . $callbacks["on_drag_stop_func"] . '"' : '';
		$menu_attrs .= !empty($callbacks["on_parse_template_widget_html_func"]) ? ' data-on-parse-template-widget-html-func="' . $callbacks["on_parse_template_widget_html_func"] . '"' : '';
		$menu_attrs .= !empty($callbacks["on_clean_template_widget_html_func"]) ? ' data-on-clean-template-widget-html-func="' . $callbacks["on_clean_template_widget_html_func"] . '"' : '';
		$menu_attrs .= !empty($callbacks["on_clone_menu_widget_func"]) ? ' data-on-clone-menu-widget-func="' . $callbacks["on_clone_menu_widget_func"] . '"' : '';
		$menu_attrs .= !empty($callbacks["on_create_template_widget_func"]) ? ' data-on-create-template-widget-func="' . $callbacks["on_create_template_widget_func"] . '"' : '';
		
		$resizable = isset($settings["resizable"]) ? $settings["resizable"] : null;
		$menu_attrs .= $resizable != "" && $resizable != "0" && strtolower($resizable) != "false" ? ' data-resizable="1"' : '';
		
		$absolute_position = isset($settings["absolute_position"]) ? $settings["absolute_position"] : null;
		$menu_attrs .= $absolute_position != "" && $absolute_position != "0" && strtolower($absolute_position) != "false" ? ' data-absolute-position="1"' : '';
		
		//Preparing template widget
		$template_node_name = !empty($settings["template_node_name"]) ? $settings["template_node_name"] : "div";
		$template_class = " template-widget-$tag " . (isset($settings["template_class"]) ? $settings["template_class"] : "");
		$template_attrs = "";
		
		if (isset($widget["template_widget"]) && is_array($widget["template_widget"])) {
			$template_attributes = isset($widget["template_widget"]["@"]) ? $widget["template_widget"]["@"] : null;
			$template_html = isset($widget["template_widget"]["value"]) ? $widget["template_widget"]["value"] : null;
			
			if ($template_attributes && !empty($template_attributes["class"])) {
				$template_class .= " " . $template_attributes["class"];
				unset($template_attributes["class"]);
			}
			
			$template_attrs .= convertWidgetXmlAttributesToHtmlAttributes($template_attributes);
		}
		else
			$template_html = isset($widget["template_widget"]) ? $widget["template_widget"] : null;
		
		//Preparing widget properties and correspondent callbacks
		$properties_attrs = "";
		$properties_attrs .= !empty($callbacks["on_open_properties_func"]) ? ' data-on-open-properties-func="' . $callbacks["on_open_properties_func"] . '"' : '';
		$properties_attrs .= !empty($callbacks["on_close_properties_func"]) ? ' data-on-close-properties-func="' . $callbacks["on_close_properties_func"] . '"' : '';
		$properties_attrs .= !empty($callbacks["on_before_save_properties_func"]) ? ' data-on-before-save-open-properties-func="' . $callbacks["on_before_save_properties_func"] . '"' : '';
		$properties_attrs .= !empty($callbacks["on_after_save_properties_func"]) ? ' data-on-after-save-open-properties-func="' . $callbacks["on_after_save_properties_func"] . '"' : '';
		
		//Preparing widget files
		$files = isset($widget["files"]) ? $widget["files"] : null;
		
		if ($files) {
			$widget_abs_folder_path = dirname($file_path) . "/";
			$widget_folder_path = substr($widget_abs_folder_path, strlen($widgets_root_path));
			$widget_folder_path = substr($widget_folder_path, 0, 1) == "/" ? substr($widget_folder_path, 1) : $widget_folder_path;
			$widget_root_url = $widgets_root_url . $widget_folder_path;
			
			if (!empty($files["css"])) {
				if (!is_array($files["css"]))
					$files["css"] = array($files["css"]);
				
				foreach ($files["css"] as $file_css) 
					if ($file_css && !in_array($widget_root_url . $file_css, $GLOBALS["layout_ui_editor_widgets_files_included"])) {
						$GLOBALS["layout_ui_editor_widgets_files_included"][] = $widget_root_url . $file_css;
						$menu_widgets_css_files[ realpath($widget_abs_folder_path . $file_css) ] = $widget_root_url . $file_css;
					}
			}
			
			if (!empty($files["js"])) {
				if (!is_array($files["js"]))
					$files["js"] = array($files["js"]);
				
				foreach ($files["js"] as $file_js)
					if ($file_js && !in_array($widget_root_url . $file_js, $GLOBALS["layout_ui_editor_widgets_files_included"])) {
						$GLOBALS["layout_ui_editor_widgets_files_included"][] = $widget_root_url . $file_js;
						$menu_widgets_js_files[ realpath($widget_abs_folder_path . $file_js) ] = $widget_root_url . $file_js;
				}
			}
		}
		
		//Preparing widget html
		$html = '<li class="draggable menu-widget' . $menu_class . '" data-tag="' . $tag . '" title="' . $menu_title . '"' . $menu_attrs . '>'
			. $menu_html
			. '<' . $template_node_name . ' class="template-widget' . $template_class . '"' . $template_attrs . '>' . $template_html . '</' . $template_node_name . '>'
			. '<div class="properties"' . $properties_attrs . '>' . (isset($widget["properties"]) ? $widget["properties"] : null) . '</div>'
			. (!empty($widget["menu_css"]) ? '<style>' . $widget["menu_css"] . '</style>' : '')
			. (!empty($widget["menu_js"]) ? '<script>' . $widget["menu_js"] . '</script>' : '')
			. (!empty($widget["template_css"]) ? '<div class="template-css">' . $widget["template_css"] . '</div>' : '')
			. (!empty($widget["template_js"]) ? '<div class="template-js">' . $widget["template_js"] . '</div>' : '')
		. '</li>';
		
		return $html;
	}
	else {
		echo "There is a widget without any TAG! All widgets must have a TAG! Error in file: $file_path!";
		die();
	}
}

function convertWidgetXmlAttributesToHtmlAttributes($attributes) {
	$html = "";
	
	if ($attributes) 
		foreach ($attributes as $k => $v)
			$html .= ' ' . $k . '="' . addcslashes($v, '\\"') . '"';
	
	return $html;
}
?>
