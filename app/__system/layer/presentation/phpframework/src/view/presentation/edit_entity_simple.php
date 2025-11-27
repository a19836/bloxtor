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

include_once $EVC->getUtilPath("CMSPresentationLayerUIHandler");
include_once $EVC->getUtilPath("BreadCrumbsUIHandler");
include_once $EVC->getUtilPath("TourGuideUIHandler");
include_once $EVC->getUtilPath("HeatMapHandler");

$file_path = isset($file_path) ? $file_path : null;
$P = isset($P) ? $P : null;
$db_drivers_options = isset($db_drivers_options) ? $db_drivers_options : null;
$sla_settings = isset($sla_settings) ? $sla_settings : null;
$selected_project_id = isset($selected_project_id) ? $selected_project_id : null;
$file_modified_time = isset($file_modified_time) ? $file_modified_time : null;
$cached_modified_date = isset($cached_modified_date) ? $cached_modified_date : null;

$presentation_brokers = isset($presentation_brokers) ? $presentation_brokers : null;
$business_logic_brokers = isset($business_logic_brokers) ? $business_logic_brokers : null;
$data_access_brokers = isset($data_access_brokers) ? $data_access_brokers : null;
$ibatis_brokers = isset($ibatis_brokers) ? $ibatis_brokers : null;
$hibernate_brokers = isset($hibernate_brokers) ? $hibernate_brokers : null;
$db_brokers = isset($db_brokers) ? $db_brokers : null;

$presentation_brokers_obj = isset($presentation_brokers_obj) ? $presentation_brokers_obj : null;
$business_logic_brokers_obj = isset($business_logic_brokers_obj) ? $business_logic_brokers_obj : null;
$data_access_brokers_obj = isset($data_access_brokers_obj) ? $data_access_brokers_obj : null;
$ibatis_brokers_obj = isset($ibatis_brokers_obj) ? $ibatis_brokers_obj : null;
$hibernate_brokers_obj = isset($hibernate_brokers_obj) ? $hibernate_brokers_obj : null;
$db_brokers_obj = isset($db_brokers_obj) ? $db_brokers_obj : null;

$is_external_template = isset($is_external_template) ? $is_external_template : null;
$available_blocks_list = isset($available_blocks_list) ? $available_blocks_list : null;
$regions_blocks_list = isset($regions_blocks_list) ? $regions_blocks_list : null;
$block_params_values_list = isset($block_params_values_list) ? $block_params_values_list : null;
$blocks_join_points = isset($blocks_join_points) ? $blocks_join_points : null;
$defined_regions_list = isset($defined_regions_list) ? $defined_regions_list : null;
$available_params_values_list = isset($available_params_values_list) ? $available_params_values_list : null;
$available_regions_list = isset($available_regions_list) ? $available_regions_list : null;
$selected_or_default_template = isset($selected_or_default_template) ? $selected_or_default_template : null;
$template_region_blocks = isset($template_region_blocks) ? $template_region_blocks : null;
$template_params_values_list = isset($template_params_values_list) ? $template_params_values_list : null;
$set_template = isset($set_template) ? $set_template : null;
$selected_template = isset($selected_template) ? $selected_template : null;
$available_templates = isset($available_templates) ? $available_templates : null;
$installed_wordpress_folders_name = isset($installed_wordpress_folders_name) ? $installed_wordpress_folders_name : null;
$available_block_params_list = isset($available_block_params_list) ? $available_block_params_list : null;
$includes = isset($includes) ? $includes : null;
$available_params_list = isset($available_params_list) ? $available_params_list : null;
$tasks_contents = isset($tasks_contents) ? $tasks_contents : null;
$db_drivers = isset($db_drivers) ? $db_drivers : null;
$presentation_projects = isset($presentation_projects) ? $presentation_projects : null;
$brokers_db_drivers = isset($brokers_db_drivers) ? $brokers_db_drivers : null;

$template_includes = array_map(function($include) { 
	$inc_path = isset($include["path"]) ? $include["path"] : null;
	$inc_path = PHPUICodeExpressionHandler::getArgumentCode($inc_path, isset($include["path_type"]) ? $include["path_type"] : null);
	return array(
		"path" => $inc_path, 
		"once" => isset($include["once"]) ? $include["once"] : null
	); 
}, $includes);

$filter_by_layout_url_query = LayoutTypeProjectUIHandler::getFilterByLayoutURLQuery($filter_by_layout);

$sla_settings_obj = CMSPresentationLayerJoinPointsUIHandler::convertBlockSettingsArrayToObj($sla_settings);
//echo "<pre>";print_r($sla_settings);echo "</pre>";die();
//echo "<pre>";print_r($sla_settings_obj);echo "</pre>";die();

$confirm_save = !empty($obj_data["code"]) && $cached_modified_date != $file_modified_time;

$page_preview_url = $project_url_prefix . "phpframework/presentation/test_project?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path";
$view_project_url = $project_url_prefix . "phpframework/presentation/view_project?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path";

$manage_ai_action_url = $openai_encryption_key ? $project_url_prefix . "phpframework/ai/manage_ai_action" : null;
$save_url = $project_url_prefix . "phpframework/presentation/save_entity_simple?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path";
$get_block_handler_source_code_url = $project_url_prefix . "phpframework/presentation/get_module_handler_source_code?bean_name=$bean_name&bean_file_name=$bean_file_name&project=$path&block=#block#";
$get_page_block_join_points_html_url = $project_url_prefix . "phpframework/presentation/get_page_block_join_points_html?bean_name=$bean_name&bean_file_name=$bean_file_name&project=$path&block=#block#";

$get_available_blocks_list_url = $project_url_prefix . "phpframework/presentation/get_available_blocks_list?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$path";
$get_block_params_url = $project_url_prefix . "phpframework/presentation/get_block_params?bean_name=$bean_name&bean_file_name=$bean_file_name&project=#project#&block=#block#";
$get_template_regions_samples_url = $project_url_prefix . "phpframework/presentation/get_template_regions_samples?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$selected_project_id/" . $P->settings["presentation_templates_path"] . "#template#.php";
$template_region_info_url = $project_url_prefix . "phpframework/presentation/template_region_info?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$selected_project_id/" . $P->settings["presentation_templates_path"] . "#template#.php&region=#region#";
$template_samples_url = $project_url_prefix . "phpframework/presentation/template_samples?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$selected_project_id/" . $P->settings["presentation_templates_path"] . "#template#.php";
$templates_regions_html_url = $project_url_prefix . "phpframework/presentation/templates_regions_html?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$selected_project_id";
$edit_simple_template_layout_url = $project_url_prefix . "phpframework/presentation/edit_simple_template_layout?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path";

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

$create_page_presentation_uis_diagram_block_url = $project_url_prefix . "phpframework/presentation/create_page_presentation_uis_diagram_block?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$path";

$create_entity_code_url = $project_url_prefix . "phpframework/presentation/create_entity_code?project=$selected_project_id&default_extension=" . $P->getPresentationFileExtension();
$get_available_projects_props_url = $project_url_prefix . "phpframework/presentation/get_available_projects_props?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$path";
$get_available_templates_props_url = $project_url_prefix . "phpframework/presentation/get_available_templates_props?bean_name=$bean_name&bean_file_name=$bean_file_name&path=#path#";
$get_available_templates_list_url = $project_url_prefix . "phpframework/presentation/get_available_templates_list?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path";
$get_template_regions_and_params_url = $project_url_prefix . "phpframework/presentation/get_template_regions_and_params?bean_name=$bean_name&bean_file_name=$bean_file_name&project=$selected_project_id&template=#template#";
$install_template_url = $project_url_prefix . "phpframework/presentation/install_template?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$selected_project_id/src/template/";
$edit_template_file_url = $project_url_prefix . "phpframework/presentation/edit_template?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&edit_template_type=simple";
$edit_webroot_file_url = $project_url_prefix . "phpframework/admin/edit_raw_file?bean_name=$bean_name&bean_file_name=$bean_file_name&item_type=presentation&path=#path#&popup=1";
$create_webroot_file_url = $project_url_prefix . "phpframework/admin/manage_file?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$selected_project_id/webroot/#folder#&action=create_file&item_type=presentation&extra=#file_name#";
$project_global_variables_url = $project_url_prefix . "phpframework/presentation/edit_project_global_variables?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$selected_project_id/src/config/pre_init_config.php";

$head = isset($sla_head) ? $sla_head : null;
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

<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/edit_entity_simple.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/edit_entity_simple.js"></script>

<!-- Add Join Point CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/module_join_points.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/module_join_points.js"></script>

<!-- Add Choose AvailableTemplate CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/choose_available_template.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/choose_available_template.js"></script>

<script>
' . WorkFlowBrokersSelectedDBVarsHandler::printSelectedDBVarsJavascriptCode($project_url_prefix, $bean_name, $bean_file_name, isset($selected_db_vars) ? $selected_db_vars : null) . '
var entity_path = "' . $path . '";
var layer_type = "pres";
var file_modified_time = ' . (isset($file_modified_time) ? $file_modified_time : "null") . '; //for version control

var page_preview_url = \'' . $page_preview_url . '\';
var view_project_url = \'' . $view_project_url . '\';
var manage_ai_action_url = \'' . $manage_ai_action_url . '\';
var save_object_url = \'' . $save_url . '\';
var get_block_handler_source_code_url = \'' . $get_block_handler_source_code_url . '\';
var get_page_block_join_points_html_url = \'' . $get_page_block_join_points_html_url . '\';
var get_template_regions_samples_url = \'' . $get_template_regions_samples_url . '\';
var template_region_info_url = \'' . $template_region_info_url . '\';
var template_samples_url = \'' . $template_samples_url . '\';
var templates_regions_html_url = \'' . $templates_regions_html_url . '\'; //used in widget: src/view/presentation/common_editor_widget/template_region/import_region_html.xml

var create_page_module_block_url = \'' . $create_page_module_block_url . '\';
var add_block_url = \'' . $add_block_url . '\';
var edit_block_url = \'' . $edit_block_url . '\';
var edit_view_url = \'' . $edit_view_url . '\';
var get_module_info_url = \'' . $get_module_info_url . '\';

var create_db_table_or_attribute_url = \'' . $create_db_table_or_attribute_url . '\';
var create_db_driver_table_or_attribute_url = \'' . $create_db_driver_table_or_attribute_url . '\';
var edit_db_driver_tables_diagram_url = \'' . $edit_db_driver_tables_diagram_url . '\';

var get_available_projects_props_url = \'' . $get_available_projects_props_url . '\';
var get_template_regions_and_params_url = \'' . $get_template_regions_and_params_url . '\';
var get_available_templates_props_url = \'' . $get_available_templates_props_url . '\';
var get_available_templates_list_url = \'' . $get_available_templates_list_url . '\';
var install_template_url = \'' . $install_template_url . '\';
var edit_template_file_url = \'' . $edit_template_file_url . '\';
var edit_webroot_file_url = \'' . $edit_webroot_file_url . '\';
var create_webroot_file_url = \'' . $create_webroot_file_url . '\';
var project_global_variables_url = \'' . $project_global_variables_url . '\';

var layer_default_template = \'' . (isset($layer_default_template) ? $layer_default_template : null) . '\';
var common_project_name = \'' . $PEVC->getCommonProjectName() . '\';
var selected_project_url_prefix = \'' . (isset($selected_project_url_prefix) ? $selected_project_url_prefix : null) . '\';
var selected_project_common_url_prefix = \'' . (isset($selected_project_common_url_prefix) ? $selected_project_common_url_prefix : null) . '\';

var show_templates_only = ' . (!empty($_GET["show_templates_only"]) ? 1 : 0) . '; //This is set when we switch the entity advanced ui to the simple ui.

var confirm_save = ' . ($confirm_save ? 'true' : 'false') . ';

var sla_settings_obj = ' . (isset($sla_settings_obj) ? json_encode($sla_settings_obj) : "null") . ';
var brokers_db_drivers = ' . (isset($brokers_db_drivers) ? json_encode($brokers_db_drivers) : "null") . ';
var access_activity_id = ' . (isset($access_activity_id) && is_numeric($access_activity_id) ? $access_activity_id : "null") . ';
';

$head .= isset($sla_js_head) ? $sla_js_head : null;
$head .= WorkFlowPresentationHandler::getPresentationBrokersHtml($presentation_brokers, $choose_bean_layer_files_from_file_manager_url, $get_file_properties_url, $upload_bean_layer_files_from_file_manager_url);
$head .= WorkFlowPresentationHandler::getBusinessLogicBrokersHtml($business_logic_brokers, $choose_bean_layer_files_from_file_manager_url, $get_file_properties_url);
$head .= WorkFlowPresentationHandler::getDataAccessBrokersHtml($data_access_brokers, $choose_bean_layer_files_from_file_manager_url);
$head .= WorkFlowPresentationHandler::getDaoLibAndVendorBrokersHtml($choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, $get_file_properties_url);
$head .= '</script>';

$head .= CMSPresentationLayerUIHandler::getHeader($project_url_prefix, $project_common_url_prefix, $get_available_blocks_list_url, $get_block_params_url, $create_entity_code_url, $available_blocks_list, $regions_blocks_list, $block_params_values_list, $template_includes, $blocks_join_points, $template_params_values_list, $selected_project_id, false, $head, $defined_regions_list, $available_params_values_list);
$head .= LayoutTypeProjectUIHandler::getHeader();
$head .= HeatMapHandler::getHtml($project_url_prefix);

$query_string = isset($_SERVER["QUERY_STRING"]) ? preg_replace("/dont_save_cookie=([^&])*/", "", str_replace(array("&edit_entity_type=advanced", "&edit_entity_type=simple"), "", $_SERVER["QUERY_STRING"])) : "";

$pos = strpos($file_path, "/src/entity/") + strlen("/src/entity/");
$entity_prefix = substr($file_path, 0, $pos);
$entity_code = substr($file_path, $pos);

$main_content = '
	<div class="top_bar' . ($is_external_template ? " is_external_template" : "") . '">
		<header>
			<div class="title" title="' . $path . '">Edit Page (Visual Workspace)<span class="advanced_option"> <span class="template_fields"></span> at</span>: <div class="breadcrumbs">' . BreadCrumbsUIHandler::getFilePathBreadCrumbsItemsHtml($entity_prefix, $P, false, false, "advanced_option") . BreadCrumbsUIHandler::getFilePathBreadCrumbsItemsHtml($entity_code, null, true) . '</div></div>
			<ul>
				<li class="view_project_page" data-title="Preview Project Page"><a onClick="previewWithDelay()"><i class="icon view"></i></a></li>
				<li class="save" data-title="Save Page"><a onClick="saveEntityWithDelay()"><i class="icon save"></i> Save</a></li>
				
				<li class="sub_menu" onclick="openSubmenu(this)">
					<i class="icon sub_menu"></i>
					<ul>
						<li class="advanced_editor" title="Switch to Code Workspace"><a href="?' . $query_string . '&edit_entity_type=advanced"><i class="icon show_advanced_ui"></i> Switch to Code Workspace</a></li>
						<li class="separator"></li>
						<li class="flip_layout_ui_panels" title="Flip Layout UI Panels"><a onClick="flipCodeLayoutUIEditorPanelsSide(this)"><i class="icon flip_layout_ui_panels"></i> Flip Layout UI Panels</a></li>
						<li class="separator"></li>
						<li class="toggle_advanced_options" title="Toggle Advanced Features"><a onClick="toggleAdvancedOptions()"><i class="icon toggle_ids"></i> <span>Show Advanced Features</span> <input type="checkbox"/></a></li>
						<li class="toggle_main_settings" title="Toggle Main Settings"><a onClick="toggleSettingsPanel(this)"><i class="icon toggle_ids"></i> <span>Show Main Settings</span> <input type="checkbox"/></a></li>
						<li class="update_layout_from_settings" title="Update Main Settings to Layout UI"><a onClick="updateLayoutFromSettings( $(\'.entity_obj\'), true )"><i class="icon update_layout_from_settings"></i> Update Main Settings to Layout Area</a></li>
						<li class="separator"></li>
						<li class="choose_template" title="Switch Theme Template"><a onClick="onChooseAvailableTemplate(true)"><i class="icon choose_template"></i> Switch Theme Template</a></li>
						<li class="view_template_samples" title="View Chosen Template Samples"><a onClick="openTemplateSamples()"><i class="icon view_template_samples"></i> View Chosen Template Samples</a></li>
						<li class="preview" title="Preview & Test Page"><a onClick="testAndPreviewWithDelay()"><i class="icon preview_file"></i> Preview & Test Page</a></li>
						<li class="separator"></li>
						<li class="full_screen" title="Maximize/Minimize Editor Screen"><a onClick="toggleFullScreen(this)"><i class="icon full_screen"></i> Maximize Editor Screen</a></li>
						<li class="separator"></li>
						<li class="beautify" title="Disable Html beautify on save"><a onClick="toggleCodeEditorHtmlBeautify(this)"><i class="icon save"></i> Disable Html Beautify on Save</a></li>
						<li class="save" title="Save Page"><a onClick="saveEntityWithDelay()"><i class="icon save"></i> Save</a></li>
						<li class="save_preview" title="Save & Preview Page"><a onClick="saveAndPreview();"><i class="icon save_preview_file"></i> Save & Preview Page</a></li>
					</ul>
				</li>
			</ul>
		</header>
	</div>';

if (!empty($obj_data)) {
	$code_exists = isset($obj_data["code"]) && !empty(trim($obj_data["code"]));
	
	$main_content .= '
	<script>	
	var code_exists = ' . ($code_exists ? 1 : 0) . ';
	</script>';
	
	//echo "<pre>";print_r($templates);print_r($includes);print_r($available_block_params_list);print_r($available_regions_list);print_r($available_blocks_list);print_r($regions_blocks_list);echo "</pre>";
	
	if (!empty($hard_coded))
		$main_content .='<script>
		var invalid_msg = \'Before continue, please check if this page was changed manually via the code workspace. If you continue editing this page through here, you may loose some data, previously added.<br/>In this case, we recommend you to edit this page through the "<a href="?' . $query_string . '&edit_entity_type=advanced">Advanced Editor</a>" instead.\';
	</script>';
	
	//prepare file manager popups
	$main_content .= WorkFlowPresentationHandler::getChooseFromFileManagerPopupHtml($bean_name, $bean_file_name, $choose_bean_layer_files_from_file_manager_url, $choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, $db_brokers, $data_access_brokers, $ibatis_brokers, $hibernate_brokers, $business_logic_brokers, $presentation_brokers);
	$main_content .= CMSPresentationLayerUIHandler::getChoosePresentationIncludeFromFileManagerPopupHtml($bean_name, $bean_file_name, $choose_bean_layer_files_from_file_manager_url, $choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, $presentation_brokers);
	
	//prepare ui_menu_widgets_html
	$webroot_path = $EVC->getWebrootPath();
	$ui_menu_widgets_html = WorkFlowPresentationHandler::getUIEditorWidgetsHtml($webroot_path, $project_url_prefix, $webroot_cache_folder_path, $webroot_cache_folder_url);
	$ui_menu_widgets_html .= WorkFlowPresentationHandler::getExtraUIEditorWidgetsHtml($webroot_path, $EVC->getViewsPath() . "presentation/common_editor_widget/", $webroot_cache_folder_path, $webroot_cache_folder_url);
	$ui_menu_widgets_html .= WorkFlowPresentationHandler::getUserUIEditorWidgetsHtml($webroot_path, $layout_ui_editor_user_widget_folders_path, $webroot_cache_folder_path, $webroot_cache_folder_url);
	
	$ui_menu_widgets_html .= WorkFlowPresentationHandler::getExtraUIEditorWidgetsHtml($webroot_path, $EVC->getViewsPath() . "presentation/view_editor_widget/", $webroot_cache_folder_path, $webroot_cache_folder_url);
	$ui_menu_widgets_html .= WorkFlowPresentationHandler::getExtraUIEditorWidgetsHtml($webroot_path, $EVC->getViewsPath() . "presentation/page_and_template_editor_widget/", $webroot_cache_folder_path, $webroot_cache_folder_url);
	
	//prepare template_region_block_html_editor_popup
	$main_content .= CMSPresentationLayerUIHandler::getTemplateRegionBlockHtmlEditorPopupHtml($ui_menu_widgets_html);
	
	//prepare edit_simple_template_layout_data
	//echo "<pre>";print_r($available_regions_list);print_r($regions_blocks_list);print_r($template_params_values_list);die();
	$template_region_blocks = array_map(function($n) { return ""; }, array_flip($available_regions_list)); //sets regions with default value: ""
	if ($regions_blocks_list)
		foreach ($regions_blocks_list as $rbl) {
			$rbl_region = isset($rbl[0]) ? $rbl[0] : null;
			
			if (!isset($template_region_blocks[$rbl_region]) || !is_array($template_region_blocks[$rbl_region]))
				$template_region_blocks[$rbl_region] = array();
			
			$template_region_blocks[$rbl_region][] = $rbl;
		}
	
	$edit_simple_template_layout_data = array(
		"template" => $selected_or_default_template,
		"template_regions" => $template_region_blocks,
		"template_params" => $template_params_values_list,
		"template_includes" => $template_includes,
		"is_external_template" => $is_external_template,
		"external_template_params" => isset($set_template["template_params"]) ? $set_template["template_params"] : null,
	);
	
	$main_content .= '
		<script>
			var edit_simple_template_layout_url = \'' . $edit_simple_template_layout_url . '\';
			var edit_simple_template_layout_data = ' . json_encode($edit_simple_template_layout_data) . ';
		</script>';
	
	//prepare choose project template popup
	$main_content .= '<div id="choose_project_template_url_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
		<div class="title">Choose a Template</div>
		<ul class="mytree">
			<li>
				<label>' . (isset($presentation_brokers[0][0]) ? $presentation_brokers[0][0] : "") . '</label>
				<ul url="' . str_replace("#bean_name#", $bean_name, str_replace("#bean_file_name#", $bean_file_name, $choose_bean_layer_files_from_file_manager_url)) . '"></ul>
			</li>
		</ul>
		<div class="button">
			<input type="button" value="Update" onClick="MyFancyPopup.settings.updateFunction(this)" />
		</div>
	</div>';
	
	//prepare entity html
	$main_content .= '
	<div class="entity_obj inactive' . ($is_external_template ? ' is_external_template' : '') . '">
		' . getTemplatesHtml($set_template, $selected_template, $available_templates, $installed_wordpress_folders_name) . '
		
		<div class="regions_blocks_includes_settings_overlay"></div>
		<div class="entity_template_settings regions_blocks_includes_settings collapsed" id="entity_template_settings">
			<div class="settings_header">
				Main Settings
				<div class="icon maximize" onClick="toggleSettingsPanel(this)" title="Toggle Settings">Toggle</i></div>
			</div>
			
			<ul class="tabs tabs_transparent tabs_right">
				<li><a href="#design_settings">Design</a></li>
				<li><a href="#content_settings">Content</a></li>
				<li><a href="#resource_settings">Resources</a></li>
				<li><a href="#advanced_settings">Advanced</a></li>
			</ul>
			
			<div id="design_settings" class="design_settings">
				<div class="current_template_file">To edit the current template please click <a href="javascript:void(0
)" onClick="editCurrentTemplateFile(this)">here</a></div>
				
				<div class="css_files">
					<label>CSS Files: <span class="icon add" onClick="addWebrootFile(this, \'css\', false, addPageWebrootFile, removePageWebrootFile)" title="Add css file">Add</span></label>
					<ul>
						<li class="empty_files">No files detected...</li>
					</ul>
				</div>
				
				<div class="js_files">
					<label>Javascript Files: <span class="icon add" onClick="addWebrootFile(this, \'js\', false, addPageWebrootFile, removePageWebrootFile)" title="Add js file">Add</span></label>
					<ul>
						<li class="empty_files">No files detected...</li>
					</ul>
				</div>
			</div>
			
			<div id="content_settings" class="content_settings">
				' . CMSPresentationLayerUIHandler::getRegionsBlocksAndIncludesHtml($selected_or_default_template, $available_regions_list, $regions_blocks_list, $available_blocks_list, $available_block_params_list, $block_params_values_list, $includes, $available_params_list, $template_params_values_list, $defined_regions_list, $available_params_values_list) . '
			</div>
			
			<div id="resource_settings" class="resource_settings">
				' . SequentialLogicalActivityUIHandler::getSLAHtml($EVC, $PEVC, $project_url_prefix, $project_common_url_prefix, $layout_ui_editor_user_widget_folders_path, $webroot_cache_folder_path, $webroot_cache_folder_url, $tasks_contents, $db_drivers, $presentation_projects, $WorkFlowUIHandler, array(
					"save_func" => "saveEntity",
				)) . '
			</div>
			
			<div id="advanced_settings" class="advanced_settings">
				' . getAdvancedSettingsHtml($EVC, isset($advanced_settings) ? $advanced_settings : null) . '
			</div>
		</div>
		<div class="code_layout_ui_editor">
			' . WorkFlowPresentationHandler::getCodeEditorHtml("", array("save_func" => "saveEntity", "show_pretty_print" => false), $ui_menu_widgets_html, $user_global_variables_file_path, $user_beans_folder_path, $PEVC, $UserAuthenticationHandler, $bean_name, $bean_file_name, $brokers_db_drivers, $choose_bean_layer_files_from_file_manager_url, $get_db_data_url, $create_page_presentation_uis_diagram_block_url, "chooseCodeLayoutUIEditorModuleBlockFromFileManagerTreeRightContainer", false, array(
				'layout_ui_editor_class' => 'sidebar_options_left',
				'layout_ui_editor_html' => '
					<div class="template-widgets">
						<iframe class="template-widgets-droppable" edit_simple_template_layout_url="' . $edit_simple_template_layout_url . '"></iframe>
					</div>'
			)) . '
		</div>
	</div>
	
	<div class="current_entity_code hidden">' . (isset($obj_data["code"]) ? str_replace(">", "&gt;", str_replace("<", "&lt;", $obj_data["code"])) : "") . '</div>';
	
	$main_content .= TourGuideUIHandler::getHtml($entity, $project_url_prefix, $project_common_url_prefix, $online_tutorials_url_prefix, array(
		"css" => ':host {
			--tourguide-tooltip-width:400px;
		}'
	));
}
else
	$main_content .= '<div class="error">Error: The system couldn\'t detect the selected file. Please refresh and try again...</div>';

//$head = $CssAndJSFilesOptimizer->prepareHtmlWithOptimizedCssAndJSFiles($head, false);

function getAdvancedSettingsHtml($EVC, $advanced_settings) {
	$is_parse_html = !empty($advanced_settings["parse_full_html"]) || !empty($advanced_settings["parse_regions_html"]);
	$style_display_none = $is_parse_html ? '' : ' style="display:none;"';
	$disabled = $is_parse_html ? '' : ' disabled';
	$execute_sla = isset($advanced_settings["execute_sla"]) ? $advanced_settings["execute_sla"] : null;
	$parse_hash_tags = isset($advanced_settings["parse_hash_tags"]) ? $advanced_settings["parse_hash_tags"] : null;
	$parse_ptl = isset($advanced_settings["parse_ptl"]) ? $advanced_settings["parse_ptl"] : null;
	$add_my_js_lib = isset($advanced_settings["add_my_js_lib"]) ? $advanced_settings["add_my_js_lib"] : null;
	$add_widget_resource_lib = isset($advanced_settings["add_widget_resource_lib"]) ? $advanced_settings["add_widget_resource_lib"] : null;
	$filter_by_permission = isset($advanced_settings["filter_by_permission"]) ? $advanced_settings["filter_by_permission"] : null;
	$include_blocks_when_calling_resources = isset($advanced_settings["include_blocks_when_calling_resources"]) ? $advanced_settings["include_blocks_when_calling_resources"] : null;
	$init_user_data = isset($advanced_settings["init_user_data"]) ? $advanced_settings["init_user_data"] : null;
	
	
	$html = '
	<div class="parser">
		<label>Parser:</label>
		<span class="info">If active, parses the generated html according with the options below.</span>
		
		<div class="parse_html">
			<label>Parse Html: </label>
			<select name="parse_html" onChange="onChangeParseHtml(this)" title="If active the system will parse the html and add or filter elements according with the options defined below.">
				<option value="0"' . (!$is_parse_html ? ' selected' : '') . '>No</option>
				<option value="1"' . (!empty($advanced_settings["parse_full_html"]) ? ' selected' : '') . ' title="Parse Full Page Output Html including the template html. Basically parses the html before it is sent to the client browser.">Parse Full Page Output Html</option>
				<option value="2"' . (!empty($advanced_settings["parse_regions_html"]) && empty($advanced_settings["parse_full_html"]) ? ' selected' : '') . '>Only Parse Page Regions Html</option>
			</select>
			<span class="info">If active the system will parse the html and add or filter elements according with the options defined below.</span>
		</div>
		
		<div class="execute_sla"' . $style_display_none . '>
			<label>Execute SLA Resources: </label>
			<select name="execute_sla" title="\'Auto\': means that the system will try to find if the html needs to be parsed. If active the system will execute the resources and save them, so we can use them later on through the hash-tags, PTL or directly in the html..."' . $disabled . '>
				<option value="0"' . ($execute_sla === false ? ' selected' : '') . '>No</option>
				<option value="1"' . ($execute_sla === true ? ' selected' : '') . '>Yes</option>
				<option value=""' . ($execute_sla === null ? ' selected' : '') . '>Auto</option>
			</select>
			<span class="info">If active the system will execute the resources and save them, so we can use them later on through the hash-tags, PTL or directly in the html...<br/>\'Auto\': means that the system will try to find if the html needs to be parsed.</span>
		</div>
		
		<div class="parse_hash_tags"' . $style_display_none . '>
			<label>Parse Hash Tags: </label>
			<select name="parse_hash_tags" title="\'Auto\': means that the system will try to find if the html needs to be parsed. If active the system will replace all hash-tags with the correspondent data from the resources."' . $disabled . '>
				<option value="0"' . ($parse_hash_tags === false ? ' selected' : '') . '>No</option>
				<option value="1"' . ($parse_hash_tags === true ? ' selected' : '') . '>Yes</option>
				<option value=""' . ($parse_hash_tags === null ? ' selected' : '') . '>Auto</option>
			</select>
			<span class="info">If active the system will replace all hash-tags with the correspondent data from the resources.<br/>\'Auto\': means that the system will try to find if the html needs to be parsed.</span>
		</div>
		
		<div class="parse_ptl"' . $style_display_none . '>
			<label>Parse PTL: </label>
			<select name="parse_ptl" title="\'Auto\': means that the system will try to find if the html needs to be parsed. If active the system will convert the PTL code into HTML."' . $disabled . '>
				<option value="0"' . ($parse_ptl === false ? ' selected' : '') . '>No</option>
				<option value="1"' . ($parse_ptl === true ? ' selected' : '') . '>Yes</option>
				<option value=""' . ($parse_ptl === null ? ' selected' : '') . '>Auto</option>
			</select>
			<span class="info">If active the system will convert the PTL code into HTML.<br/>\'Auto\': means that the system will try to find if the html needs to be parsed.</span>
		</div>
		
		<div class="add_my_js_lib"' . $style_display_none . '>
			<label>Add Form JS Lib: </label>
			<select name="add_my_js_lib" title="\'Auto\': means that the system will try to find if the html needs to be parsed. If active the system will add the MyJSLib.js file."' . $disabled . '>
				<option value="0"' . ($add_my_js_lib === false ? ' selected' : '') . '>No</option>
				<option value="1"' . ($add_my_js_lib === true ? ' selected' : '') . '>Yes</option>
				<option value=""' . ($add_my_js_lib === null ? ' selected' : '') . '>Auto</option>
			</select>
			<span class="info">If active the system will add the MyJSLib.js file.<br/>\'Auto\': means that the system will try to find if the html needs to be parsed.</span>
		</div>
		
		<div class="add_widget_resource_lib"' . $style_display_none . '>
			<label>Add Widget Resource JS and CSS Lib: </label>
			<select name="add_widget_resource_lib" title="\'Auto\': means that the system will try to find if the html needs to be parsed. If active the system will add the MyWidgetResourceLib.js and MyWidgetResourceLib.css files."' . $disabled . '>
				<option value="0"' . ($add_widget_resource_lib === false ? ' selected' : '') . '>No</option>
				<option value="1"' . ($add_widget_resource_lib === true ? ' selected' : '') . '>Yes</option>
				<option value=""' . ($add_widget_resource_lib === null ? ' selected' : '') . '>Auto</option>
			</select>
			<span class="info">If active the system will add the MyWidgetResourceLib.js and MyWidgetResourceLib.css files.<br/>\'Auto\': means that the system will try to find if the html needs to be parsed.</span>
		</div>
		
		<div class="filter_by_permission"' . $style_display_none . '>
			<label>Filter by Permission: </label>
			<select name="filter_by_permission" title="\'Auto\': means that the system will try to find if the html needs to be parsed. If active the system will filter (show, hide or remove) the html elements according with the permission defined in each element."' . $disabled . '>
				<option value="0"' . ($filter_by_permission === false ? ' selected' : '') . '>No</option>
				<option value="1"' . ($filter_by_permission === true ? ' selected' : '') . '>Yes</option>
				<option value=""' . ($filter_by_permission === null ? ' selected' : '') . '>Auto</option>
			</select>
			<span class="info">If active the system will filter (show, hide or remove) the html elements according with the permission defined in each element.<br/>\'Auto\': means that the system will try to find if the html needs to be parsed.</span>
		</div>
		
		<div class="include_blocks_when_calling_resources"' . $style_display_none . '>
			<label>Include Blocks when calling Resources: </label>
			<select name="include_blocks_when_calling_resources" title="If active the system will include and execute the blocks when a resource gets called."' . $disabled . '>
				<option value="0"' . (!$include_blocks_when_calling_resources ? ' selected' : '') . '>No</option>
				<option value="1"' . ($include_blocks_when_calling_resources === true ? ' selected' : '') . '>Yes</option>
			</select>
			<span class="info">If active the system will include and execute the blocks when a resource gets called.</span>
		</div>
		
		<div class="init_user_data"' . $style_display_none . '>
			<label>Init User Data: </label>
			<select name="init_user_data" title="If active the system will get the logged user data."' . $disabled . '>
				<option value="0"' . (!$init_user_data ? ' selected' : '') . '>No</option>
				<option value="1"' . ($init_user_data === true ? ' selected' : '') . '>Yes</option>
				<option value=""' . ($init_user_data === null ? ' selected' : '') . '>Auto</option>
			</select>
			<span class="info">If active the system will get the logged user data. You should disable this setting if the correspondent DB tables of the user module were not installed in this project DB.</span>
		</div>
		
		<div class="maximum_usage_memory"' . $style_display_none . '>
			<label>Maximum Usage Memory: </label>
			<input name="maximum_usage_memory" placeHolder="empty for default: ' . $EVC->getCMSLayer()->getCMSPagePropertyLayer()->getMaximumUsageMemory() . ' bytes" value="' . (isset($advanced_settings["maximum_usage_memory"]) ? $advanced_settings["maximum_usage_memory"] : "") . '"' . $disabled . ' />
			<span class="info">If the page html and resources exceed this maximum, the parser will NOT run!</span>
		</div>
		
		<div class="maximum_buffer_chunk_size"' . $style_display_none . '>
			<label>Maximum Buffer Chunk Size: </label>
			<input name="maximum_buffer_chunk_size" placeHolder="empty for default: ' . $EVC->getCMSLayer()->getCMSPagePropertyLayer()->getMaximumBufferChunkSize() . ' bytes" value="' . (isset($advanced_settings["maximum_buffer_chunk_size"]) ? $advanced_settings["maximum_buffer_chunk_size"] : "") . '"' . $disabled . ' />
			<span class="info">Parses the html in chunks and flushes this chunks to the client browser, so server memory doesn\'t get overloaded! <br/>Note that the "Parse Hash Tags", "Parse PTL" and "Filter by Permission" options will not run if the html is parsed in chunks! Which means you may need to increase the chunk size if your data is too big...</span>
		</div>
	</div>
	
	<div class="cache">
		<label>Cache:</label>
		<span class="info">If active, caches this page so the client request be faster. Be careful caching dynamic pages...</span>
		<div class="todo">Comming soon... (TODO: sync this UI with the backend code to get this properties and create the correspondent cache. Add correspondent cache to the "resource.php" controller too, this is, any cache define here must be replicated for the resource url too.<br/>
		Note that the cache feature is already implemented. The only we need to do is to create the UI to interact with the correspondent XML files.)</div>
		
		<div class="cache_page">
			<label>Cache Page: </label>
			<input type="checkbox" name="cache_page" value="1" onChange="onChangeCacheOption(this)"' . (!empty($advanced_settings["cache_page"]) ? ' checked' : '') . ' />
			<input name="cache_page_ttl" placeHolder="TTL in secs" value="' . (isset($advanced_settings["cache_page_ttl"]) ? $advanced_settings["cache_page_ttl"] : "") . '"' . (!empty($advanced_settings["cache_page"]) ? '' : ' disabled') . ' />
		</div>
		
		<div class="cache_dispatcher">
			<label>Cache Dispatcher: </label>
			<input type="checkbox" name="cache_dispatcher" value="1" onChange="onChangeCacheOption(this)"' . (!empty($advanced_settings["cache_page"]) ? ' checked' : '') . ' />
			<input name="cache_dispatcher_ttl" placeHolder="TTL in secs" value="' . (isset($advanced_settings["cache_dispatcher_ttl"]) ? $advanced_settings["cache_dispatcher_ttl"] : "") . '"' . (!empty($advanced_settings["cache_page"]) ? '' : ' disabled') . ' />
		</div>
	</div>
	
	<div class="routers">
		<label>Routers:</label>
		<span class="info">Assign different urls to this page.</span>
		
		<div class="todo">Comming soon... (TODO: sync this UI with the backend code to get this properties and create the correspondent routers. Add correspondent routers to the "resource.php" controller too, this is, any router define here must be replicated for the resource url too.<br/>
		Note that the router feature is already implemented. The only we need to do is to create the UI to interact with the correspondent XML files.)</div>
	</div>';
	
	return $html;
}

function getTemplatesHtml($set_template, $selected_template, $available_templates, $installed_wordpress_folders_name) {
	$selected_template_params_type = $selected_template_params_external_template = $selected_template_params_external_project_id = $selected_template_params_keep_original_project_url_prefix = $selected_template_params_wordpress_installation_name = $selected_template_params_url = $selected_template_params_cache_ttl = null;
	
	$wordpress_stable = version_compare(PHP_VERSION, '7.2', '<=');
	
	if (isset($set_template["template_params"]) && is_array($set_template["template_params"])) {
		$selected_template_params_type = isset($set_template["template_params"]["type"]) && is_array($set_template["template_params"]["type"]) && isset($set_template["template_params"]["type"]["value"]) ? $set_template["template_params"]["type"]["value"] : null; //used in UI
		
		if ($selected_template_params_type == 'project') {
			$selected_template_params_external_template = isset($set_template["template_params"]["template_id"]) && is_array($set_template["template_params"]["template_id"]) && isset($set_template["template_params"]["template_id"]["value"]) ? $set_template["template_params"]["template_id"]["value"] : null;
			$selected_template_params_external_project_id = isset($set_template["template_params"]["external_project_id"]) && is_array($set_template["template_params"]["external_project_id"]) && isset($set_template["template_params"]["external_project_id"]["value"]) ? $set_template["template_params"]["external_project_id"]["value"] : null;
			$selected_template_params_keep_original_project_url_prefix = isset($set_template["template_params"]["keep_original_project_url_prefix"]) && is_array($set_template["template_params"]["keep_original_project_url_prefix"]) && isset($set_template["template_params"]["keep_original_project_url_prefix"]["value"]) ? $set_template["template_params"]["keep_original_project_url_prefix"]["value"] : null;
		}
		else if ($selected_template_params_type == 'block') {
			$selected_template_params_external_template = isset($set_template["template_params"]["block_id"]) && is_array($set_template["template_params"]["block_id"]) && isset($set_template["template_params"]["block_id"]["value"]) ? $set_template["template_params"]["block_id"]["value"] : null;
			$selected_template_params_external_project_id = isset($set_template["template_params"]["external_project_id"]) && is_array($set_template["template_params"]["external_project_id"]) && isset($set_template["template_params"]["external_project_id"]["value"]) ? $set_template["template_params"]["external_project_id"]["value"] : null;
		}
		else if ($selected_template_params_type == 'wordpress_template') {
			$selected_template_params_external_template = isset($set_template["template_params"]["url_query"]) && is_array($set_template["template_params"]["url_query"]) && isset($set_template["template_params"]["url_query"]["value"]) ? $set_template["template_params"]["url_query"]["value"] : null;
			$selected_template_params_wordpress_installation_name = isset($set_template["template_params"]["wordpress_installation_name"]) && is_array($set_template["template_params"]["wordpress_installation_name"]) && isset($set_template["template_params"]["wordpress_installation_name"]["value"]) ? $set_template["template_params"]["wordpress_installation_name"]["value"] : null;
		}
		else if ($selected_template_params_type == 'url') {
			$selected_template_params_url = isset($set_template["template_params"]["url"]) && is_array($set_template["template_params"]["url"]) && isset($set_template["template_params"]["url"]["value"]) ? $set_template["template_params"]["url"]["value"] : null;
		}
		
		$selected_template_params_cache_ttl = isset($set_template["template_params"]["cache_ttl"]) && is_array($set_template["template_params"]["cache_ttl"]) && isset($set_template["template_params"]["cache_ttl"]["value"]) ? $set_template["template_params"]["cache_ttl"]["value"] : null;
	}
	
	$html = '
	<div class="template advanced_option">
		<label>with</label>
		<select name="template_genre" onChange="onChangeTemplateGenre(this)">
			<option value="">Internal Template</option>
			<option value="external_template"' . ($selected_template_params_type ? ' selected' : '') . '>External Template</option>
		</select>
		
		<select name="template" onChange="onChangeTemplate(this)" ' . ($selected_template_params_type ? ' style="display:none"' : ' title="' . str_replace('"', '&quot;', $selected_template) . '"') . '>
			<option value="">-- DEFAULT --</option>';

	foreach ($available_templates as $fp)
		$html .= '<option value="' . $fp . '"' . ($fp == $selected_template ? ' selected' : '') . '>' . $fp . '</option>';

	$html .= '
		</select>
		
		<span class="icon search" onClick="onChooseAvailableTemplate(true)" Title="Choose a template">Search</span>
		<span class="icon dropup_arrow external_template_params_toggle_btn" onClick="toggleExternalTemplateParams(this)"></span>
	</div>
	
	<div class="external_template_params advanced_option"' . (!$selected_template_params_type ? ' style="display:none"' : '') . '>
		<div class="external_template_type">
			<label>External Template Type:</label>
			<select name="type" onChange="onChangeExternalTemplateType(this)">
				<option value="">-- Please choose a type --</option>
				<option value="project"' . ($selected_template_params_type == 'project' ? ' selected' : '') . '>Template from another project</option>
				<option value="block"' . ($selected_template_params_type == 'block' ? ' selected' : '') . '>Template from a Block</option>
				<option value="url"' . ($selected_template_params_type == 'url' ? ' selected' : '') . '>Template from an URL</option>
				<option value="wordpress_template"' . ($selected_template_params_type == 'wordpress_template' ? ' selected' : '') . (!$wordpress_stable ? ' title="Our current version of WordPress only works with PHP versions 5.6 until 7.2. If you continue, WordPress can be unstable..."' : '') . '>WordPress Template' . (!$wordpress_stable ? ' - DEPRECATED - only for PHP5.6 to PHP7.2' : '') . '</option>
			</select>
		</div>
		
		<div class="template_id project_param"' . ($selected_template_params_type == 'project' ? '' : ' style="display:none"') . '>
			<label>Template:</label>
			<input name="template_id" value="' . ($selected_template_params_type == 'project' ? $selected_template_params_external_template : '') . '" onBlur="onBlurExternalTemplate(this)" />
			<span class="icon search" onClick="onChooseProjectTemplate(this)" Title="Choose a template">Search</span>
		</div>
		
		<div class="block_id block_param"' . ($selected_template_params_type == 'block' ? '' : ' style="display:none"') . '>
			<label>Block:</label>
			<input name="block_id" value="' . ($selected_template_params_type == 'block' ? $selected_template_params_external_template : '') . '" onBlur="onBlurExternalTemplate(this)" />
			<span class="icon search" onClick="onChooseBlockTemplate(this)" Title="Choose a block">Search</span>
		</div>
		
		<div class="external_project_id project_param block_param"' . ($selected_template_params_type == 'project' || $selected_template_params_type == 'block' ? '' : ' style="display:none"') . '>
			<label>Project:</label>
			<input name="external_project_id" value="' . ($selected_template_params_type == 'project' || $selected_template_params_type == 'block' ? $selected_template_params_external_project_id : '') . '" onBlur="onBlurExternalTemplate(this)" />
		</div>
		
		<div class="keep_original_project_url_prefix project_param"' . ($selected_template_params_type == 'project' ? '' : ' style="display:none"') . '>
			<label>Keep Original Project URL Prefix:</label>
			<input type="checkbox" name="keep_original_project_url_prefix" value="1" ' . ($selected_template_params_type == 'project' && $selected_template_params_keep_original_project_url_prefix ? ' checked' : '') . ' onChange="onBlurExternalTemplate(this)" />
			<div class="info">Select this checkbox if you wish to load the template with the files (css, js, images and links) from the selected project.</div>
		</div>
		
		<div class="url_query wordpress_template_param"' . ($selected_template_params_type == 'wordpress_template' ? '' : ' style="display:none"') . '>
			<label>Url Query:</label>
			<input name="url_query" value="' . ($selected_template_params_type == 'wordpress_template' ? $selected_template_params_external_template : '') . '" onBlur="onBlurExternalTemplate(this)" />
			<div class="info">Please write the URL Query of the WordPress page you wish to fetch.<br/>Note that the URL Query must be the relative URI from the WordPress page url.</div>
		</div>
		
		<div class="wordpress_installation_name wordpress_template_param"' . ($selected_template_params_type == 'wordpress_template' ? '' : ' style="display:none"') . '>
			<label>WordPress Installation:</label>
			<select name="wordpress_installation_name" onChange="onBlurExternalTemplate(this)">
				<option value="">-- Default DB Driver folder name if exists --</option>';
		
		foreach ($installed_wordpress_folders_name as $name)
			$html .= '<option value="' . $name . '"' . ($selected_template_params_wordpress_installation_name == $name ? ' selected' : '') . '>' . ucwords(str_replace("_", " ", $name)) . '</option>';
		
		if ($selected_template_params_wordpress_installation_name && !in_array($selected_template_params_wordpress_installation_name, $installed_wordpress_folders_name))
			$html .= '<option value="' . $selected_template_params_wordpress_installation_name . '" selected>' . $selected_template_params_wordpress_installation_name . ' - INVALID VALUE</option>';
		
		$html .= '
			</select>
		</div>
		
		<div class="url url_param"' . ($selected_template_params_type == 'url' ? '' : ' style="display:none"') . '>
			<label>URL:</label>
			<input type="text" name="url" value="' . ($selected_template_params_type == 'url' ? $selected_template_params_url : '') . '" onBlur="onBlurExternalTemplate(this)" />
			<span class="icon search" onClick="onPresentationIncludePageUrlTaskChooseFile(this)" Title="Choose a page url">Search</span>
		</div>
		
		<div class="cache_ttl project_param block_param wordpress_template_param url_param"' . ($selected_template_params_type ? '' : ' style="display:none"') . '>
			<label>Cache TTL (in minutes):</label>
			<input name="cache_ttl" value="' . $selected_template_params_cache_ttl . '" />
			<div class="info">Please write the minutes that this template should be cached, this is, everytime that the end-user calls this pages, the system will request and build this external template and this process may take some time. If you cache it, the next time the end-user requests this same page, it will be much faster. <br/>
			Note that this "External Template Cache" is not related with this "Page\'s Cache". The "Pages\' Cache" are a different type of cache!</div>
		</div>
	</div>';
	
	return $html;
}
?>
