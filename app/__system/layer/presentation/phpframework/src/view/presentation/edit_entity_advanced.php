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

include $EVC->getUtilPath("WorkFlowUIHandler");
include $EVC->getUtilPath("WorkFlowPresentationHandler");
include $EVC->getUtilPath("BreadCrumbsUIHandler");

$file_path = isset($file_path) ? $file_path : null;
$P = isset($P) ? $P : null;
$db_drivers_options = isset($db_drivers_options) ? $db_drivers_options : null;
$brokers_db_drivers = isset($brokers_db_drivers) ? $brokers_db_drivers : null;

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

$view_file_path = isset($view_file_path) ? $view_file_path : null;

$design_editor = isset($_GET["design_editor"]) ? $_GET["design_editor"] : null;

$filter_by_layout_url_query = LayoutTypeProjectUIHandler::getFilterByLayoutURLQuery($filter_by_layout);

$WorkFlowUIHandler = new WorkFlowUIHandler($WorkFlowTaskHandler, $project_url_prefix, $project_common_url_prefix, $external_libs_url_prefix, $user_global_variables_file_path, $webroot_cache_folder_path, $webroot_cache_folder_url);
$WorkFlowUIHandler->setTasksGroupsByTag(array(
	"Logic" => array("definevar", "setvar", "setarray", "setdate", "ns", "createfunction", "createclass", "setobjectproperty", "createclassobject", "callobjectmethod", "callfunction", "addheader", "if", "switch", "loop", "foreach", "includefile", "echo", "code", "break", "return", "exit", "validator", "upload", "geturlcontents", "getbeanobject", "sendemail", "debuglog"),
	"Connectors" => array("restconnector", "soapconnector"),
	"Exception" => array("trycatchexception", "throwexception", "printexception"),
	"DB" => array("getdbdriver", "setquerydata", "getquerydata", "dbdaoaction", "callibatisquery", "callhibernateobject", "callhibernatemethod"),
	"Layers" => array("callbusinesslogic", "callpresentationlayerwebservice", "setpresentationview", "addpresentationview", "setpresentationtemplate", "resetregionblockjoinpoints"),
	"HTML" => array("inlinehtml", "createform"),
	"CMS" => array("setblockparams", "settemplateregionblockparam", "includeblock", "addregionhtml", "addregionblock", "rendertemplateregion", "settemplateparam", "gettemplateparam"),
));
$WorkFlowUIHandler->addFoldersTasksToTasksGroups($code_workflow_editor_user_tasks_folders_path);

$view_project_url = $project_url_prefix . "phpframework/presentation/view_project?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path";
$manage_ai_action_url = $openai_encryption_key ? $project_url_prefix . "phpframework/ai/manage_ai_action" : null;
$save_url = $project_url_prefix . "phpframework/presentation/save_entity_advanced?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path";

$path_extra = hash('crc32b', "$bean_file_name/$bean_name/$path");
$get_workflow_tasks_id = "presentation_workflow&path_extra=_$path_extra";
$get_tmp_workflow_tasks_id = "presentation_workflow_tmp&path_extra=_${path_extra}_" . rand(0, 1000);

$set_workflow_file_url = $project_url_prefix . "workflow/set_workflow_file?path=${get_workflow_tasks_id}";
$get_workflow_file_url = $project_url_prefix . "workflow/get_workflow_file?path=${get_workflow_tasks_id}";
$create_workflow_file_from_code_url = $project_url_prefix . "workflow/create_workflow_file_from_code?path=${get_tmp_workflow_tasks_id}&loaded_tasks_settings_cache_id=" . $WorkFlowTaskHandler->getLoadedTasksSettingsCacheId();
$get_tmp_workflow_file_url = $project_url_prefix . "workflow/get_workflow_file?path=${get_tmp_workflow_tasks_id}";
$create_code_from_workflow_file_url = $project_url_prefix . "workflow/create_code_from_workflow_file?path=${get_tmp_workflow_tasks_id}";
$set_tmp_workflow_file_url = $project_url_prefix . "workflow/set_workflow_file?path=${get_tmp_workflow_tasks_id}";

$choose_bean_layer_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?bean_name=#bean_name#&bean_file_name=#bean_file_name#$filter_by_layout_url_query&path=#path#";
$upload_bean_layer_files_from_file_manager_url = $project_url_prefix . "admin/upload_file?bean_name=#bean_name#&bean_file_name=#bean_file_name#$filter_by_layout_url_query&path=#path#";
$choose_dao_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?item_type=dao&path=#path#";
$choose_lib_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?item_type=lib&path=#path#";
$choose_vendor_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?item_type=vendor&path=#path#";

$get_db_data_url = $project_url_prefix . "db/get_db_data?bean_name=#bean_name#&bean_file_name=#bean_file_name#&type=#type#";

$modules_path = $EVC->getCommonProjectName() . "/" . $EVC->getPresentationLayer()->settings["presentation_modules_path"];
$get_file_properties_url = $project_url_prefix . "phpframework/admin/get_file_properties?bean_name=#bean_name#&bean_file_name=#bean_file_name#&path=#path#&class_name=#class_name#&type=#type#";
$get_query_properties_url = $project_url_prefix . "phpframework/dataaccess/get_query_properties?bean_name=#bean_name#&bean_file_name=#bean_file_name#&db_driver=#db_driver#&db_type=#db_type#&path=#path#&query_type=#query_type#&query=#query#&obj=#obj#&relationship_type=#relationship_type#";
$get_business_logic_properties_url = $project_url_prefix . "phpframework/businesslogic/get_business_logic_properties?bean_name=#bean_name#&bean_file_name=#bean_file_name#&path=#path#&service=#service#";
$get_broker_db_drivers_url = $project_url_prefix . "phpframework/db/get_broker_db_drivers?bean_name=$bean_name&bean_file_name=$bean_file_name&broker=#broker#&item_type=presentation";
$get_broker_db_data_url = $project_url_prefix . "phpframework/dataaccess/get_broker_db_data?bean_name=$bean_name&bean_file_name=$bean_file_name";
$edit_task_source_url = $project_url_prefix . "phpframework/admin/edit_task_source?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$path";

$create_page_presentation_uis_diagram_block_url = $project_url_prefix . "phpframework/presentation/create_page_presentation_uis_diagram_block?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path";

$create_page_module_block_url = $project_url_prefix . "phpframework/presentation/create_page_module_block?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path";
$add_block_url = $project_url_prefix . "phpframework/presentation/edit_page_module_block?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path&module_id=#module_id#&edit_block_type=simple";
$edit_block_url = $project_url_prefix . "phpframework/presentation/edit_block?bean_name=$bean_name&bean_file_name=$bean_file_name&path=#path#&edit_block_type=simple";
$get_module_info_url = $project_url_prefix . "phpframework/presentation/get_module_info?module_id=#module_id#";

$create_db_driver_table_or_attribute_url = $project_url_prefix . "db/edit_table?bean_name=#bean_name#&bean_file_name=#bean_file_name#&layer_bean_folder_name=#layer_bean_folder_name#&type=#type#&table=#table#"; //This url is to be called directly with the DB driver bean data
$edit_db_driver_tables_diagram_url = $project_url_prefix . "db/diagram?bean_name=#bean_name#&bean_file_name=#bean_file_name#&layer_bean_folder_name=#layer_bean_folder_name#";

$templates_regions_html_url = $project_url_prefix . "phpframework/presentation/templates_regions_html?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path"; //used in widget: src/view/presentation/common_editor_widget/template_region/import_region_html.xml which is used in the Layout_ui_editor from the taskflowchart->inlinehtml and createform tasks.

$edit_view_file_url = $project_url_prefix . "phpframework/presentation/edit_view?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$view_file_path";
$add_view_file_url = $project_url_prefix . "phpframework/presentation/save_view?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$view_file_path";

$head = WorkFlowPresentationHandler::getHeader($project_url_prefix, $project_common_url_prefix, $WorkFlowUIHandler, $set_workflow_file_url, true);
$head .= WorkFlowPresentationHandler::getFileManagerTreePopupHeader($project_url_prefix);
$head .= LayoutTypeProjectUIHandler::getHeader();
$head .= '
<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add PHPJS Functions -->
<script type="text/javascript" src="' . $project_common_url_prefix . 'vendor/phpjs/functions/strings/stripslashes.js"></script>
<script type="text/javascript" src="' . $project_common_url_prefix . 'vendor/phpjs/functions/strings/addcslashes.js"></script>

<!-- Add local CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/edit_page_and_template.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/edit_page_and_template.js"></script>

<!-- Add Join Point CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/module_join_points.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/module_join_points.js"></script>

<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/edit_entity_advanced.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/edit_entity_advanced.js"></script>

<script>
' . WorkFlowBrokersSelectedDBVarsHandler::printSelectedDBVarsJavascriptCode($project_url_prefix, $bean_name, $bean_file_name, isset($selected_db_vars) ? $selected_db_vars : null) . '
var layer_type = "pres";
var selected_project_id = "' . (isset($selected_project_id) ? $selected_project_id : "") . '";
var file_modified_time = ' . (isset($file_modified_time) ? $file_modified_time : "null") . '; //for version control
var design_editor = ' . ($design_editor ? "true" : "false") . ';

var get_workflow_file_url = \'' . $get_workflow_file_url . '\';
var manage_ai_action_url = \'' . $manage_ai_action_url . '\';
var save_object_url = \'' . $save_url . '\';
var create_workflow_file_from_code_url = \'' . $create_workflow_file_from_code_url . '\';
var get_tmp_workflow_file_url = \'' . $get_tmp_workflow_file_url . '\';
var create_code_from_workflow_file_url = \'' . $create_code_from_workflow_file_url . '\';
var set_tmp_workflow_file_url = \'' . $set_tmp_workflow_file_url . '\';
var get_query_properties_url = \'' . $get_query_properties_url . '\';
var get_business_logic_properties_url = \'' . $get_business_logic_properties_url . '\';
var get_broker_db_drivers_url = \'' . $get_broker_db_drivers_url . '\';
var get_broker_db_data_url = \'' . $get_broker_db_data_url . '\';
var edit_task_source_url = \'' . $edit_task_source_url . '\';

var create_page_module_block_url = \'' . $create_page_module_block_url . '\';
var add_block_url = \'' . $add_block_url . '\';
var edit_block_url = \'' . $edit_block_url . '\';
var get_module_info_url = \'' . $get_module_info_url . '\';

var create_db_driver_table_or_attribute_url = \'' . $create_db_driver_table_or_attribute_url . '\';
var edit_db_driver_tables_diagram_url = \'' . $edit_db_driver_tables_diagram_url . '\';

var templates_regions_html_url = \'' . $templates_regions_html_url . '\'; //used in widget: src/view/presentation/common_editor_widget/template_region/import_region_html.xml and in the Layout_ui_editor from the taskflowchart->inlinehtml and createform tasks.

var brokers_db_drivers = ' . json_encode($brokers_db_drivers) . ';

ProgrammingTaskUtil.on_programming_task_edit_source_callback = onProgrammingTaskEditSource;
ProgrammingTaskUtil.on_programming_task_choose_created_variable_callback = onProgrammingTaskChooseCreatedVariable;
ProgrammingTaskUtil.on_programming_task_choose_object_property_callback = onProgrammingTaskChooseObjectProperty;
ProgrammingTaskUtil.on_programming_task_choose_object_method_callback = onProgrammingTaskChooseObjectMethod;
ProgrammingTaskUtil.on_programming_task_choose_function_callback = onProgrammingTaskChooseFunction;
ProgrammingTaskUtil.on_programming_task_choose_class_name_callback = onProgrammingTaskChooseClassName;
ProgrammingTaskUtil.on_programming_task_choose_file_path_callback = onIncludeFileTaskChooseFile;
ProgrammingTaskUtil.on_programming_task_choose_folder_path_callback = onIncludeFolderTaskChooseFile;
ProgrammingTaskUtil.on_programming_task_choose_page_url_callback = onIncludePageUrlTaskChooseFile;
ProgrammingTaskUtil.on_programming_task_choose_image_url_callback = onIncludeImageUrlTaskChooseFile;
ProgrammingTaskUtil.on_programming_task_choose_webroot_file_url_callback = onIncludeWebrootFileUrlTaskChooseFile;

FunctionUtilObj.on_function_task_edit_method_code_callback = onFunctionTaskEditMethodCode;
FunctionUtilObj.set_tmp_workflow_file_url = set_tmp_workflow_file_url;
FunctionUtilObj.get_tmp_workflow_file_url = get_tmp_workflow_file_url;
FunctionUtilObj.create_code_from_workflow_file_url = create_code_from_workflow_file_url;
FunctionUtilObj.create_workflow_file_from_code_url = create_workflow_file_from_code_url;

IncludeBlockTaskPropertyObj.on_choose_file_callback = onIncludeBlockTaskChooseFile;
callPresentationLayerWebServiceTaskPropertyObj.on_choose_page_callback = onPresentationTaskChoosePage;
callPresentationLayerWebServiceTaskPropertyObj.brokers_options = ' . json_encode($presentation_brokers_obj) . ';

SetPresentationTemplateTaskPropertyObj.brokers_options = ' . json_encode(array("default" => '$EVC')) . ';
SetPresentationViewTaskPropertyObj.brokers_options = ' . json_encode(array("default" => '$EVC')) . ';
AddPresentationViewTaskPropertyObj.brokers_options = ' . json_encode(array("default" => '$EVC')) . ';
GetTemplateParamTaskPropertyObj.brokers_options = ' . json_encode(array("default" => '$EVC->getCMSLayer()->getCMSTemplateLayer()')) . ';
SetTemplateParamTaskPropertyObj.brokers_options = ' . json_encode(array("default" => '$EVC->getCMSLayer()->getCMSTemplateLayer()')) . ';
RenderTemplateRegionTaskPropertyObj.brokers_options = ' . json_encode(array("default" => '$EVC->getCMSLayer()->getCMSTemplateLayer()')) . ';
AddRegionBlockTaskPropertyObj.brokers_options = ' . json_encode(array("default" => '$EVC->getCMSLayer()->getCMSTemplateLayer()')) . ';
ResetRegionBlockJoinPointsTaskPropertyObj.brokers_options = ' . json_encode(array("default" => '$EVC->getCMSLayer()->getCMSJoinPointLayer()')) . ';
AddRegionHtmlTaskPropertyObj.brokers_options = ' . json_encode(array("default" => '$EVC->getCMSLayer()->getCMSTemplateLayer()')) . ';
IncludeBlockTaskPropertyObj.brokers_options = ' . json_encode(array("default" => '$EVC')) . ';
IncludeBlockTaskPropertyObj.projects_options = ' . (isset($available_projects) ? json_encode($available_projects) : "null") . ';
GetBeanObjectTaskPropertyObj.phpframeworks_options = ' . json_encode($phpframeworks_options) . ';
GetBeanObjectTaskPropertyObj.bean_names_options = ' . (isset($bean_names_options) ? json_encode($bean_names_options) : "null") . ';

CreateFormTaskPropertyObj.editor_ready_func = initLayoutUIEditorWidgetResourceOptions;
CreateFormTaskPropertyObj.layout_ui_editor_menu_widgets_elm_selector = \'.ui-menu-widgets-backup\';
InlineHTMLTaskPropertyObj.editor_ready_func = initLayoutUIEditorWidgetResourceOptions;
InlineHTMLTaskPropertyObj.layout_ui_editor_menu_widgets_elm_selector = \'.ui-menu-widgets-backup\';

if (typeof LayerOptionsUtilObj != "undefined" && LayerOptionsUtilObj)
	LayerOptionsUtilObj.on_choose_db_driver_callback = onChooseDBDriver;

if (typeof CallBusinessLogicTaskPropertyObj != "undefined" && CallBusinessLogicTaskPropertyObj) {
	CallBusinessLogicTaskPropertyObj.on_choose_business_logic_callback = onBusinessLogicTaskChooseBusinessLogic;
	CallBusinessLogicTaskPropertyObj.brokers_options = ' . json_encode($business_logic_brokers_obj) . ';
}

if (typeof CallIbatisQueryTaskPropertyObj != "undefined" && CallIbatisQueryTaskPropertyObj) {
	CallIbatisQueryTaskPropertyObj.on_choose_query_callback = onChooseIbatisQuery;
	CallIbatisQueryTaskPropertyObj.brokers_options = ' . json_encode($ibatis_brokers_obj) . ';
}

if (typeof CallHibernateObjectTaskPropertyObj != "undefined" && CallHibernateObjectTaskPropertyObj) {
	CallHibernateObjectTaskPropertyObj.on_choose_hibernate_object_callback = onChooseHibernateObject;
	CallHibernateObjectTaskPropertyObj.brokers_options = ' . json_encode($hibernate_brokers_obj) . ';
}

if (typeof CallHibernateMethodTaskPropertyObj != "undefined" && CallHibernateMethodTaskPropertyObj) {
	CallHibernateMethodTaskPropertyObj.on_choose_hibernate_object_method_callback = onChooseHibernateObjectMethod;
	CallHibernateMethodTaskPropertyObj.brokers_options = ' . json_encode($hibernate_brokers_obj) . ';
}

if (typeof GetQueryDataTaskPropertyObj != "undefined" && GetQueryDataTaskPropertyObj) {
	GetQueryDataTaskPropertyObj.brokers_options = ' . json_encode(array_merge($db_brokers_obj, $data_access_brokers_obj)) . ';
}

if (typeof SetQueryDataTaskPropertyObj != "undefined" && SetQueryDataTaskPropertyObj) {
	SetQueryDataTaskPropertyObj.brokers_options = ' . json_encode(array_merge($db_brokers_obj, $data_access_brokers_obj)) . ';
}

if (typeof DBDAOActionTaskPropertyObj != "undefined" && DBDAOActionTaskPropertyObj){
	DBDAOActionTaskPropertyObj.on_choose_table_callback = onChooseDBTableAndAttributes;
	DBDAOActionTaskPropertyObj.brokers_options = ' . json_encode(array_merge($db_brokers_obj, $data_access_brokers_obj)) . ';
}

if (typeof GetDBDriverTaskPropertyObj != "undefined" && GetDBDriverTaskPropertyObj) {
	GetDBDriverTaskPropertyObj.brokers_options = ' . json_encode($db_brokers_obj) . ';
	GetDBDriverTaskPropertyObj.db_drivers_options = ' . json_encode($db_drivers_options) . ';
}
';

$head .= WorkFlowPresentationHandler::getPresentationBrokersHtml($presentation_brokers, $choose_bean_layer_files_from_file_manager_url, $get_file_properties_url, $upload_bean_layer_files_from_file_manager_url);
$head .= WorkFlowPresentationHandler::getBusinessLogicBrokersHtml($business_logic_brokers, $choose_bean_layer_files_from_file_manager_url, $get_file_properties_url);
$head .= WorkFlowPresentationHandler::getDaoLibAndVendorBrokersHtml($choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, $get_file_properties_url);
$head .= WorkFlowPresentationHandler::getDataAccessBrokersHtml($data_access_brokers, $choose_bean_layer_files_from_file_manager_url);
$head .= '</script>';

$query_string = isset($_SERVER["QUERY_STRING"]) ? preg_replace("/dont_save_cookie=([^&])*/", "", str_replace(array("&edit_entity_type=advanced", "&edit_entity_type=simple"), "", $_SERVER["QUERY_STRING"])) : "";
//echo "query_string:$query_string";die();
//$project_with_auto_view = $view_file_exists = true; //for test only

$main_content = '
	<div class="top_bar ' . (!empty($project_with_auto_view) ? "project_with_auto_view" . (!empty($view_file_exists) ? " view_file_exists" : "") : "") . '">
		<header>
			<div class="title" title="' . $path . '">Edit Page (Code Workspace): ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($file_path, $P) . '</div>
			<ul>
				<li class="show_simple_ui button" data-title="Switch to Visual Workspace"><a href="?' . $query_string . '&edit_entity_type=simple&show_templates_only=1"><i class="icon show_simple_ui"></i> Switch to Visual Workspace</a></li>
				<li class="view_project_page" data-title="View project page"><a href="' . $view_project_url . '" target="project"><i class="icon view"></i> View project page</a></li>
				<li class="save" data-title="Save Entity"><a onClick="saveEntity()"><i class="icon save"></i> Save</a></li>
			</ul>
		</header>
	</div>';

if (!empty($obj_data)) {
	//prepare file manager popups
	$main_content .= WorkFlowPresentationHandler::getChooseFromFileManagerPopupHtml($bean_name, $bean_file_name, $choose_bean_layer_files_from_file_manager_url, $choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, $db_brokers, $data_access_brokers, $ibatis_brokers, $hibernate_brokers, $business_logic_brokers, $presentation_brokers);
	
	//prepare ui_menu_widgets_html
	$webroot_path = $EVC->getWebrootPath();
	$ui_menu_widgets_html = WorkFlowPresentationHandler::getUIEditorWidgetsHtml($webroot_path, $project_url_prefix, $webroot_cache_folder_path, $webroot_cache_folder_url);
	$ui_menu_widgets_html .= WorkFlowPresentationHandler::getExtraUIEditorWidgetsHtml($webroot_path, $EVC->getViewsPath() . "presentation/view_editor_widget/", $webroot_cache_folder_path, $webroot_cache_folder_url);
	$ui_menu_widgets_html .= WorkFlowPresentationHandler::getExtraUIEditorWidgetsHtml($webroot_path, $EVC->getViewsPath() . "presentation/common_editor_widget/", $webroot_cache_folder_path, $webroot_cache_folder_url);
	$ui_menu_widgets_html .= WorkFlowPresentationHandler::getUserUIEditorWidgetsHtml($webroot_path, $layout_ui_editor_user_widget_folders_path, $webroot_cache_folder_path, $webroot_cache_folder_url);
	
	//prepare entity html
	$view_file_tab = '';
	if (!empty($project_with_auto_view))
		$view_file_tab = '<li id="view_tab" title="View"' . (empty($view_file_exists) ? ' class="hidden"' : '') . '><a href="#view" onClick="onClickViewTab(this, \'' . $edit_view_file_url . '\');return false;"><i class="icon view_tab"></i> View</a></li>
		' . (empty($view_file_exists) ? '<li id="add_view_tab" title="Add View"><a href="#view" onClick="onClickNewViewTab(this, \'' . $add_view_file_url . '\');return false;"><i class="icon add"></i><i class="icon view_tab"></i> View</a></li>' : '');
	
	$main_content .= '
	<div class="entity_obj with_top_bar_tab ' . (!empty($project_with_auto_view) ? "project_with_auto_view" . (!empty($view_file_exists) ? " view_file_exists" : "") : "") . '">
		<ul class="tabs tabs_transparent tabs_right tabs_icons">
			<li id="visual_editor_tab" title="Design Editor"><a href="#code" onClick="onClickEntityLayoutEditorUIVisualTab(this);return false;"><i class="icon visual_editor_tab"></i> Design Editor</a></li>
			<li id="code_editor_tab" title="Code Editor"><a href="#code" onClick="onClickEntityLayoutEditorUICodeTab(this);return false;"><i class="icon code_editor_tab"></i> Code Editor</a></li>
			<li id="tasks_flow_tab" title="Diagram Editor"><a href="#ui" onClick="onClickEntityLayoutEditorUITaskWorkflowTab(this);return false;"><i class="icon tasks_flow_tab"></i> Diagram Editor</a></li>
			' . $view_file_tab . '
		</ul>
		
		<div id="code" class="code_layout_ui_editor">
			' . WorkFlowPresentationHandler::getCodeEditorHtml(isset($obj_data["code"]) ? $obj_data["code"] : null, array("save_func" => "saveEntity"), $ui_menu_widgets_html, $user_global_variables_file_path, $user_beans_folder_path, $PEVC, $UserAuthenticationHandler, $bean_name, $bean_file_name, $brokers_db_drivers, $choose_bean_layer_files_from_file_manager_url, $get_db_data_url, $create_page_presentation_uis_diagram_block_url, "chooseCodeLayoutUIEditorModuleBlockFromFileManagerTreeRightContainer", true) . '
		</div>
	
		<div id="ui">' . WorkFlowPresentationHandler::getTaskFlowContentHtml($WorkFlowUIHandler, array("save_func" => "saveEntity")) . '</div>
		
		<div class="ui-menu-widgets-backup hidden">
			' . $ui_menu_widgets_html . '
		</div>
		
		<div id="view"></div>
	</div>
	<div class="big_white_panel"></div>';
}
else 
	$main_content .= '<div class="error">Error: The system couldn\'t detect the selected file. Please refresh and try again...</div>';

//$head = $CssAndJSFilesOptimizer->prepareHtmlWithOptimizedCssAndJSFiles($head, false);
?>
