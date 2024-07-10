<?php
include $EVC->getUtilPath("CMSPresentationLayerUIHandler");
include $EVC->getUtilPath("BreadCrumbsUIHandler");

$view_includes = array_map(function($include) { 
	$inc_path = PHPUICodeExpressionHandler::getArgumentCode($include["path"], $include["path_type"]);
	return array("path" => $inc_path, "once" => $include["once"]); 
}, $includes);

$filter_by_layout_url_query = LayoutTypeProjectUIHandler::getFilterByLayoutURLQuery($filter_by_layout);

$sla_settings_obj = CMSPresentationLayerJoinPointsUIHandler::convertBlockSettingsArrayToObj($sla_settings);
//echo "<pre>";print_r($sla_settings_obj);echo "</pre>";die();

$save_url = $project_url_prefix . "phpframework/presentation/save_view?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path";
$get_block_handler_source_code_url = $project_url_prefix . "phpframework/presentation/get_module_handler_source_code?bean_name=$bean_name&bean_file_name=$bean_file_name&project=$path&block=#block#";
$get_page_block_join_points_html_url = $project_url_prefix . "phpframework/presentation/get_page_block_join_points_html?bean_name=$bean_name&bean_file_name=$bean_file_name&project=$path&block=#block#";

$get_available_blocks_list_url = $project_url_prefix . "phpframework/presentation/get_available_blocks_list?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$path";
$get_block_params_url = $project_url_prefix . "phpframework/presentation/get_block_params?bean_name=$bean_name&bean_file_name=$bean_file_name&project=#project#&block=#block#";
$edit_simple_template_layout_url = $project_url_prefix . "phpframework/presentation/edit_simple_template_layout?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path&template=$selected_template&is_edit_view=1";
$template_region_info_url = $project_url_prefix . "phpframework/presentation/template_region_info?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path&region=#region#";
$templates_regions_html_url = $project_url_prefix . "phpframework/presentation/templates_regions_html?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$selected_project_id";

$choose_bean_layer_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?bean_name=#bean_name#&bean_file_name=#bean_file_name#$filter_by_layout_url_query&path=#path#";
$upload_bean_layer_files_from_file_manager_url = $project_url_prefix . "admin/upload_file?bean_name=#bean_name#&bean_file_name=#bean_file_name#$filter_by_layout_url_query&path=#path#";
$choose_dao_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?item_type=dao&path=#path#";
$choose_lib_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?item_type=lib&path=#path#";
$choose_vendor_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?item_type=vendor&path=#path#";

$get_db_data_url = $project_url_prefix . "db/get_db_data?bean_name=#bean_name#&bean_file_name=#bean_file_name#&type=#type#";
$get_file_properties_url = $project_url_prefix . "phpframework/admin/get_file_properties?bean_name=#bean_name#&bean_file_name=#bean_file_name#&path=#path#&class_name=#class_name#&type=#type#";

$create_page_module_block_url = $project_url_prefix . "phpframework/presentation/create_page_module_block?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path";
$add_block_url = $project_url_prefix . "phpframework/presentation/edit_page_module_block?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$path&module_id=#module_id#&edit_block_type=simple";
$edit_block_url = $project_url_prefix . "phpframework/presentation/edit_block?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&edit_block_type=simple";
$edit_view_url = $project_url_prefix . "phpframework/presentation/edit_view?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#";
$get_module_info_url = $project_url_prefix . "phpframework/presentation/get_module_info?module_id=#module_id#";

$create_db_table_or_attribute_url = $project_url_prefix . "db/edit_broker_table?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$selected_project_id"; //This url is to be called directly from the presentation layer
$create_db_driver_table_or_attribute_url = $project_url_prefix . "db/edit_table?bean_name=#bean_name#&bean_file_name=#bean_file_name#&layer_bean_folder_name=#layer_bean_folder_name#&type=#type#&table=#table#"; //This url is to be called directly with the DB driver bean data
$edit_db_driver_tables_diagram_url = $project_url_prefix . "db/diagram?bean_name=#bean_name#&bean_file_name=#bean_file_name#&layer_bean_folder_name=#layer_bean_folder_name#";

$create_page_presentation_uis_diagram_block_url = $project_url_prefix . "phpframework/presentation/create_page_presentation_uis_diagram_block?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=" . str_replace("/src/", "/src/entity/", $path); //very important replace the /src/ by the /src/entity/ otherwise the create_page_presentation_uis_diagram_block.php will not convert the paths correctly.

$create_entity_code_url = $project_url_prefix . "phpframework/presentation/create_entity_code?project=$selected_project_id&default_extension=" . $P->getPresentationFileExtension();
$template_preview_html_url = $project_url_prefix . "phpframework/presentation/template_preview?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path";

$edit_webroot_file_url = $project_url_prefix . "phpframework/admin/edit_raw_file?bean_name=$bean_name&bean_file_name=$bean_file_name&item_type=presentation&path=#path#&popup=1";
$create_webroot_file_url = $project_url_prefix . "phpframework/admin/manage_file?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$selected_project_id/webroot/#folder#&action=create_file&item_type=presentation&extra=#file_name#";

$head = $sla_head;
$head .= '
<!-- Add Layout CSS and JS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />
';
$head .= WorkFlowPresentationHandler::getHeader($project_url_prefix, $project_common_url_prefix, $WorkFlowUIHandler, $set_workflow_file_url); //load createform js files and load all files for the LayoutUIEditor
$head .= '
<!-- Add local Responsive Iframe CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/responsive_iframe.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/responsive_iframe.js"></script>

<!-- Add Sequential Logical Activities CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/sequentiallogicalactivity/sla.css" type="text/css" charset="utf-8" />
<script type="text/javascript" src="' . $project_url_prefix . 'js/sequentiallogicalactivity/sla.js"></script>

<!-- Add local CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/edit_page_and_template.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/edit_page_and_template.js"></script>

<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/edit_template_simple.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/edit_template_simple.js"></script>

<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/edit_view_simple.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/edit_view_simple.js"></script>

<!-- Add Join Point CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/module_join_points.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/module_join_points.js"></script>

<script>
' . WorkFlowBrokersSelectedDBVarsHandler::printSelectedDBVarsJavascriptCode($project_url_prefix, $bean_name, $bean_file_name, $selected_db_vars) . '
var layer_type = "pres";
var file_modified_time = ' . $file_modified_time . '; //for version control

var save_object_url = \'' . $save_url . '\';
var get_block_handler_source_code_url = \'' . $get_block_handler_source_code_url . '\';
var get_page_block_join_points_html_url = \'' . $get_page_block_join_points_html_url . '\';
var template_region_info_url = \'' . $template_region_info_url . '\';
var templates_regions_html_url = \'' . $templates_regions_html_url . '\'; //used in widget: src/view/presentation/common_editor_widget/template_region/import_region_html.xml

var create_page_module_block_url = \'' . $create_page_module_block_url . '\';
var add_block_url = \'' . $add_block_url . '\';
var edit_block_url = \'' . $edit_block_url . '\';
var edit_view_url = \'' . $edit_view_url . '\';
var get_module_info_url = \'' . $get_module_info_url . '\';

var create_db_table_or_attribute_url = \'' . $create_db_table_or_attribute_url . '\';
var create_db_driver_table_or_attribute_url = \'' . $create_db_driver_table_or_attribute_url . '\';
var edit_db_driver_tables_diagram_url = \'' . $edit_db_driver_tables_diagram_url . '\';

var edit_webroot_file_url = \'' . $edit_webroot_file_url . '\';
var create_webroot_file_url = \'' . $create_webroot_file_url . '\';

var layer_default_template = \'' . $selected_template . '\';
var common_project_name = \'' . $PEVC->getCommonProjectName() . '\';
var selected_project_url_prefix = \'' . $selected_project_url_prefix . '\';
var selected_project_common_url_prefix = \'' . $selected_project_common_url_prefix . '\';

var sla_settings_obj = ' . json_encode($sla_settings_obj) . ';
var brokers_db_drivers = ' . json_encode($brokers_db_drivers) . ';
var access_activity_id = ' . (is_numeric($access_activity_id) ? $access_activity_id : "null") . ';
';

$head .= $sla_js_head;
$head .= WorkFlowPresentationHandler::getPresentationBrokersHtml($presentation_brokers, $choose_bean_layer_files_from_file_manager_url, $get_file_properties_url, $upload_bean_layer_files_from_file_manager_url);
$head .= WorkFlowPresentationHandler::getBusinessLogicBrokersHtml($business_logic_brokers, $choose_bean_layer_files_from_file_manager_url, $get_file_properties_url);
$head .= WorkFlowPresentationHandler::getDataAccessBrokersHtml($data_access_brokers, $choose_bean_layer_files_from_file_manager_url);
$head .= WorkFlowPresentationHandler::getDaoLibAndVendorBrokersHtml($choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, $get_file_properties_url);
$head .= '</script>';

$head .= CMSPresentationLayerUIHandler::getHeader($project_url_prefix, $project_common_url_prefix, $get_available_blocks_list_url, $get_block_params_url, $create_entity_code_url, $available_blocks_list, $regions_blocks_list, $block_params_values_list, $view_includes, $blocks_join_points, $view_params_values_list, $selected_project_id, false, $head);
$head .= LayoutTypeProjectUIHandler::getHeader();

$query_string = preg_replace("/dont_save_cookie=([^&])*/", "", str_replace(array("&edit_view_type=advanced", "&edit_view_type=simple"), "", $_SERVER["QUERY_STRING"]));

$pos = strpos($file_path, "/src/view/") + strlen("/src/view/");
$view_prefix = substr($file_path, 0, $pos);
$view_code = substr($file_path, $pos);

$main_content = '
	<div class="top_bar' . ($popup ? " in_popup" : "") . '">
		<header>
			<div class="title" title="' . $path . '">Edit View (Visual Workspace): <div class="breadcrumbs">' . BreadCrumbsUIHandler::getFilePathBreadCrumbsItemsHtml($view_prefix, $P, false, false, "advanced_option") . BreadCrumbsUIHandler::getFilePathBreadCrumbsItemsHtml($view_code, null, true). '</div></div>
			<ul>
				<li class="save" data-title="Save View"><a onClick="saveViewWithDelay()"><i class="icon save"></i> Save</a></li>
				
				<li class="sub_menu" onclick="openSubmenu(this)">
					<i class="icon sub_menu"></i>
					<ul>
						<li class="show_advanced_ui" title="Switch to Code Workspace"><a href="?' . $query_string . '&edit_view_type=advanced"><i class="icon show_advanced_ui"></i> Switch to Code Workspace</a></li>
						<li class="separator"></li>
						<li class="flip_layout_ui_panels" title="Flip Layout UI Panels"><a onClick="flipCodeLayoutUIEditorPanelsSide(this)"><i class="icon flip_layout_ui_panels"></i> Flip Layout UI Panels</a></li>
						<li class="separator"></li>
						<li class="toggle_advanced_options" title="Toggle Advanced Features"><a onClick="toggleAdvancedOptions()"><i class="icon toggle_ids"></i> <span>Show Advanced Features</span> <input type="checkbox"/></a></li>
						<li class="toggle_main_settings" title="Toggle Main Settings"><a onClick="toggleSettingsPanel(this)"><i class="icon toggle_ids"></i> <span>Show Main Settings</span> <input type="checkbox"/></a></li>
						<li class="update_layout_from_settings" title="Update Layout UI from Main Settings Panel"><a onClick="updateCodeEditorLayoutFromSettings( $(\'.view_obj\'), true, true )"><i class="icon update_layout_from_settings"></i> Update Layout Area from Main Settings</a></li>
						<li class="update_automatically" title="Update Main Settings Panel from the Layout UI"><a onClick="updateCodeEditorSettingsFromLayout( $(\'.view_obj\') );"><i class="icon update_automatically"></i> Update Main Settings from Layout Area</a></li>
						<li class="separator"></li>
						<li class="full_screen" title="Maximize/Minimize Editor Screen"><a onClick="toggleFullScreen(this)"><i class="icon full_screen"></i> Maximize Editor Screen</a></li>
						<li class="separator"></li>
						<li class="beautify" title="Disable Html beautify on save"><a onClick="toggleCodeEditorHtmlBeautify(this)"><i class="icon save"></i> Disable Html Beautify on Save</a></li>
						<li class="save" title="Save View"><a onClick="saveViewWithDelay()"><i class="icon save"></i> Save</a></li>
					</ul>
				</li>
			</ul>
		</header>
	</div>';

if ($obj_data) {
	$code = $obj_data["code"];
	
	$doc_type_props = WorkFlowPresentationHandler::getHtmlTagProps($code, "!DOCTYPE");
	$html_props = WorkFlowPresentationHandler::getHtmlTagProps($code, "html");
	$head_props = WorkFlowPresentationHandler::getHtmlTagProps($code, "head", array("get_inline_code" => true));
	$body_props = WorkFlowPresentationHandler::getHtmlTagProps($code, "body", array("get_inline_code" => true));
	$code_exists = !empty(trim($code));
	$non_standard_code = $code_exists && !$html_props["inline_code"] && !$head_props["inline_code"] && !$body_props["inline_code"];
	
	//$is_body_code_valid = WorkFlowPresentationHandler::validateHtmlTagsBeforeConvertingToCodeTags($body_props["inline_code"]);//It should be always false because the CKEDITOR has a problem and deforms the view code. DO NOT USE THE CKEDITOR TO EDIT VIEWS!!!
	//$body_props["inline_code"] = $is_body_code_valid ? WorkFlowPresentationHandler::convertHtmlTagsToCodeTags($body_props["inline_code"]) : $body_props["inline_code"]; //NO NEED FOR THIS ANYMORE, BC WE ARE USING A PERSONALIZED LAYOUT UI AND WE ARE NOT USING ANYMORE THE CKEDITOR. 
	$is_code_valid = !$code_exists || $html_props["inline_code"] || $html_props["html_attributes"] || $head_props["inline_code"] || $head_props["html_attributes"] || $body_props["inline_code"] || $body_props["html_attributes"];//NOW IS ALWAYS TRUE BECAUSE WE HAVE A PERSONALIZED LAYOUT UI AND WE ARE NOT USING ANYMORE THE CKEDITOR. However if the code is a non standard html code, without body and head tags, then we should alert the user and so, the code should be considered as invalid.
	
	//if non standard html file, then sets the body with the html. Execute this after the $is_code_valid var, otherwise the statement: '$body_props["inline_code"] = $code', will mess the code above.
	if ($non_standard_code)
		$body_props["inline_code"] = $code;
	
	if (!$is_code_valid) {
		$msg = $non_standard_code ? "Note that this code is a non standard html code." : "Note that some of the main tags (html, head or body) have attributes or dynamic code inside.";
		$main_content .= '<script>
		var invalid_msg = \'' . $msg . ' If you continue editing this file through this editor, it may loose some data.<br/>We recommend you to edit this file through the "<a href="?' . $query_string . '&edit_view_type=advanced">Advanced Editor</a>" instead, just in case...\';
		</script>';
	}
	
	$main_content .='<div class="invalid view_loaded_with_errors hidden">View was loaded with some javascript errors.<br/>We recommend you to edit this file through the "<a href="?' . $query_string . '&edit_entity_type=advanced">Advanced Editor</a>" instead, just in case...<span class="icon close" onClick="$(this).parent().hide();"></span></div>';
	
	//prepare file manager popups
	$main_content .= WorkFlowPresentationHandler::getChooseFromFileManagerPopupHtml($bean_name, $bean_file_name, $choose_bean_layer_files_from_file_manager_url, $choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, $db_brokers, $data_access_brokers, $ibatis_brokers, $hibernate_brokers, $business_logic_brokers, $presentation_brokers);
	$main_content .= CMSPresentationLayerUIHandler::getChoosePresentationIncludeFromFileManagerPopupHtml($bean_name, $bean_file_name, $choose_bean_layer_files_from_file_manager_url, $choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, $presentation_brokers);
	
	//prepare ui_menu_widgets_html
	$webroot_path = $EVC->getWebrootPath();
	$ui_menu_widgets_html = WorkFlowPresentationHandler::getUIEditorWidgetsHtml($webroot_path, $project_url_prefix, $webroot_cache_folder_path, $webroot_cache_folder_url);
	$ui_menu_widgets_html .= WorkFlowPresentationHandler::getExtraUIEditorWidgetsHtml($webroot_path, $EVC->getViewsPath() . "presentation/common_editor_widget/", $webroot_cache_folder_path, $webroot_cache_folder_url);
	$ui_menu_widgets_html .= WorkFlowPresentationHandler::getUserUIEditorWidgetsHtml($webroot_path, $layout_ui_editor_user_widget_folders_path, $webroot_cache_folder_path, $webroot_cache_folder_url);
	
	$template_region_block_html_editor_ui_menu_widgets_html = $ui_menu_widgets_html; //view_editor_widget and template_editor_widget cannot be included in this editor
	
	$ui_menu_widgets_html .= WorkFlowPresentationHandler::getExtraUIEditorWidgetsHtml($webroot_path, $EVC->getViewsPath() . "presentation/view_editor_widget/", $webroot_cache_folder_path, $webroot_cache_folder_url);
	$ui_menu_widgets_html .= WorkFlowPresentationHandler::getExtraUIEditorWidgetsHtml($webroot_path, $EVC->getViewsPath() . "presentation/template_editor_widget/", $webroot_cache_folder_path, $webroot_cache_folder_url);
	$ui_menu_widgets_html .= WorkFlowPresentationHandler::getExtraUIEditorWidgetsHtml($webroot_path, $EVC->getViewsPath() . "presentation/page_and_template_editor_widget/", $webroot_cache_folder_path, $webroot_cache_folder_url);
	
	//prepare template_region_block_html_editor_popup
	$main_content .= CMSPresentationLayerUIHandler::getTemplateRegionBlockHtmlEditorPopupHtml($template_region_block_html_editor_ui_menu_widgets_html);
	
	//prepare edit_simple_template_layout_data
	//print_r($available_regions_list);print_r($regions_blocks_list);print_r($view_params_values_list);die();
	$view_region_blocks = array_map(function($n) { return ""; }, array_flip($available_regions_list)); //sets regions with default value: ""
	if ($regions_blocks_list)
		foreach ($regions_blocks_list as $rbl) {
			if (!is_array($view_region_blocks[ $rbl[0] ]))
				$view_region_blocks[ $rbl[0] ] = array();
			
			$view_region_blocks[ $rbl[0] ][] = $rbl;
		}
	
	$edit_simple_template_layout_data = array(
		"template_regions" => $view_region_blocks,
		"template_params" => $view_params_values_list,
		"template_includes" => $view_includes
	);
	
	$main_content .= '
		<script>
			var edit_simple_template_layout_url = \'' . $edit_simple_template_layout_url . '\';
			var edit_simple_template_layout_data = ' . json_encode($edit_simple_template_layout_data) . ';
			
			var code_exists = ' . ($code_exists ? 1 : 0) . ';
		</script>';
	
	//prepare view html
	$main_content .= '
	<div class="template_obj view_obj with_top_bar_tab inactive' . ($popup ? " in_popup" : "") . '">
		<div class="regions_blocks_includes_settings_overlay"></div>
		<div class="code_editor_settings regions_blocks_includes_settings collapsed" id="code_editor_settings">
			<div class="settings_header">
				Main Settings
				<div class="icon maximize" onClick="toggleSettingsPanel(this)">Toggle</div>
			</div>
			
			<!-- This is now a menu in the top_bar -->
			<!--a class="update_automatically" href="javascript:void(0)" onClick="updateRegionsFromBodyEditor();" title="Update regions from the Body-Code-Editor above">
				<i class="icon update_automatically"></i>
				Update settings from Body-Code-Editor
			</a-->
			
			<ul class="tabs tabs_transparent tabs_right">
				<li><a href="#content_settings">Content</a></li>
				<li><a href="#resource_settings">Resources</a></li>
			</ul>
			
			<div id="content_settings" class="content_settings">
				' . CMSPresentationLayerUIHandler::getRegionsBlocksAndIncludesHtml($selected_template, $available_regions_list, $regions_blocks_list, $available_blocks_list, $available_block_params_list, $block_params_values_list, $includes, $available_params_list, $view_params_values_list) . '
			</div>
			
			<div id="resource_settings" class="resource_settings">
				' . SequentialLogicalActivityUIHandler::getSLAHtml($EVC, $project_url_prefix, $project_common_url_prefix, $layout_ui_editor_user_widget_folders_path, $webroot_cache_folder_path, $webroot_cache_folder_url, $tasks_contents, $db_drivers, $presentation_projects, $WorkFlowUIHandler, array(
					"save_func" => "saveView",
				)) . '
			</div>
		</div>
		<div class="code_layout_ui_editor">
			' . WorkFlowPresentationHandler::getCodeEditorHtml("", array("save_func" => "saveView", "show_pretty_print" => false), $ui_menu_widgets_html, $user_global_variables_file_path, $user_beans_folder_path, $PEVC, $UserAuthenticationHandler, $bean_name, $bean_file_name, $brokers_db_drivers, $choose_bean_layer_files_from_file_manager_url, $get_db_data_url, $create_page_presentation_uis_diagram_block_url, "chooseCodeLayoutUIEditorModuleBlockFromFileManagerTreeRightContainer", false, array(
				'layout_ui_editor_class' => 'sidebar_options_left',
				'layout_ui_editor_html' => '
					<div class="template-widgets">
						<iframe class="template-widgets-droppable" edit_simple_template_layout_url="' . $edit_simple_template_layout_url . '"></iframe>
					</div>'
			)) . '
		</div>
	</div>';
}
else
	$main_content .= '<div class="error">Error: The system couldn\'t detect the selected file. Please refresh and try again...</div>';

//$head = $CssAndJSFilesOptimizer->prepareHtmlWithOptimizedCssAndJSFiles($head, false);
?>
