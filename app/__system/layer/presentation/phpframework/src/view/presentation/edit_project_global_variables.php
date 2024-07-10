<?php
include $EVC->getUtilPath("WorkFlowUIHandler");
include $EVC->getUtilPath("WorkFlowPresentationHandler");
include $EVC->getUtilPath("BreadCrumbsUIHandler");

$filter_by_layout_url_query = LayoutTypeProjectUIHandler::getFilterByLayoutURLQuery($filter_by_layout);

$WorkFlowUIHandler = new WorkFlowUIHandler($WorkFlowTaskHandler, $project_url_prefix, $project_common_url_prefix, $external_libs_url_prefix, $user_global_variables_file_path, $webroot_cache_folder_path, $webroot_cache_folder_url);
$WorkFlowUIHandler->setTasksGroupsByTag(array(
	"Logic" => array("definevar", "setvar", "setarray", "setdate", "ns", "createfunction", "createclass", "setobjectproperty", "createclassobject", "callobjectmethod", "callfunction", "if", "switch", "loop", "foreach", "includefile", "echo", "code", "break", "return", "exit", "validator"),
	"Exception" => array("trycatchexception", "throwexception", "printexception"),
));
//$WorkFlowUIHandler->addFoldersTasksToTasksGroups($code_workflow_editor_user_tasks_folders_path);

$save_advanced_url = $project_url_prefix . 'phpframework/presentation/save_project_global_variables_advanced?bean_name=' . $bean_name . '&bean_file_name=' . $bean_file_name . '&path=' . $path;
$save_simple_url = $project_url_prefix . 'phpframework/presentation/save_project_global_variables_simple?bean_name=' . $bean_name . '&bean_file_name=' . $bean_file_name . '&path=' . $path;

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

$get_file_properties_url = $project_url_prefix . "phpframework/admin/get_file_properties?bean_name=#bean_name#&bean_file_name=#bean_file_name#&path=#path#&class_name=#class_name#&type=#type#";
$edit_task_source_url = $project_url_prefix . "phpframework/admin/edit_task_source?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$path";

$install_template_url = $project_url_prefix . "phpframework/presentation/install_template?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=$selected_project_id/src/template/";
$get_available_templates_props_url = $project_url_prefix . "phpframework/presentation/get_available_templates_props?bean_name=$bean_name&bean_file_name=$bean_file_name&path=#path#";

$head = WorkFlowPresentationHandler::getHeader($project_url_prefix, $project_common_url_prefix, $WorkFlowUIHandler, $set_workflow_file_url);
$head .= WorkFlowPresentationHandler::getFileManagerTreePopupHeader($project_url_prefix);
$head .= LayoutTypeProjectUIHandler::getHeader();
$head .= '
<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/edit_project_global_variables.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/edit_project_global_variables.js"></script>

<!-- Add Choose AvailableTemplate CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/choose_available_template.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/choose_available_template.js"></script>

<script>
' . WorkFlowBrokersSelectedDBVarsHandler::printSelectedDBVarsJavascriptCode($project_url_prefix, $bean_name, $bean_file_name, $selected_db_vars) . '
var layer_type = "pres";
var selected_project_id = "' . $selected_project_id . '";
var file_modified_time = ' . $file_modified_time . '; //for version control
var current_relative_file_path = "' . $path . '";
var global_var_html = \'' . addcslashes(str_replace("\n", "", getGlobalVarHtml("", "")), "\\'") . '\';
var is_code_valid= ' . ($is_code_valid ? 1 : 0) . ';

var get_workflow_file_url = \'' . $get_workflow_file_url . '\';
var save_object_advanced_url = \'' . $save_advanced_url . '\';
var save_object_simple_url = \'' . $save_simple_url . '\';
var create_workflow_file_from_code_url = \'' . $create_workflow_file_from_code_url . '\';
var get_tmp_workflow_file_url = \'' . $get_tmp_workflow_file_url . '\';
var create_code_from_workflow_file_url = \'' . $create_code_from_workflow_file_url . '\';
var set_tmp_workflow_file_url = \'' . $set_tmp_workflow_file_url . '\';

var edit_task_source_url = \'' . $edit_task_source_url . '\';

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

var install_template_url = \'' . $install_template_url . '\';
var get_available_templates_props_url = \'' . $get_available_templates_props_url . '\';
var available_templates_props = ' . json_encode($available_templates_props) . ';
';

$head .= WorkFlowPresentationHandler::getPresentationBrokersHtml($presentation_brokers, $choose_bean_layer_files_from_file_manager_url, $get_file_properties_url, $upload_bean_layer_files_from_file_manager_url); //bc of the find variables in the workflow
$head .= WorkFlowPresentationHandler::getDaoLibAndVendorBrokersHtml($choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, $get_file_properties_url);
$head .= '</script>';

$main_content = WorkFlowPresentationHandler::getChooseFromFileManagerPopupHtml($bean_name, $bean_file_name, $choose_bean_layer_files_from_file_manager_url, $choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, null, null, null, null, null, null);

$main_content .= '
<div class="top_bar">
	<header>
		<div class="title" title="' . $path . '">Project Global Variables for project: ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($layer_path . $selected_project_id, $P) . '</div>
		<ul>
			<li class="save" data-title="Save"><a onClick="saveGlobalVariables()"><i class="icon save"></i> Save</a></li>
		</ul>
	</header>
</div>
<div class="global_vars_obj with_top_bar_tab">
	<ul class="tabs tabs_transparent tabs_right tabs_icons">
		<li id="form_global_vars_tab" title="Variables Editor"><a href="#form_global_vars" onClick="onClickGlobalVariablesSimpleFormTab(this);return false;"><i class="icon form_global_vars_tab"></i> Variables Editor</a></li>
		<li id="code_editor_tab" title="Code Editor"><a href="#code" onClick="onClickGlobalVariablesCodeEditorTab(this);return false;"><i class="icon code_editor_tab"></i> Code Editor</a></li>
		<li id="tasks_flow_tab" title="Workflow Editor"><a href="#ui" onClick="onClickGlobalVariablesTaskWorkflowTab(this);return false;"><i class="icon tasks_flow_tab"></i> Workflow Editor</a></li>
	</ul>
	
	<div id="code">
		<div class="code_menu top_bar_menu" onClick="openSubmenu(this)">
			' . WorkFlowPresentationHandler::getCodeEditorMenuHtml(array("save_func" => "saveGlobalVariables")) . '
		</div>
		<textarea>' . htmlspecialchars($obj_data["code"], ENT_NOQUOTES) . '</textarea>
	</div>

	<div id="ui">' . WorkFlowPresentationHandler::getTaskFlowContentHtml($WorkFlowUIHandler, array("save_func" => "saveGlobalVariables")) . '</div>
	
	<div id="form_global_vars">
		<div class="code_menu top_bar_menu" onClick="openSubmenu(this)">
			<ul>
				<li class="full_screen" title="Maximize/Minimize Editor Screen"><a onClick="toggleFullScreen(this)"><i class="icon full_screen"></i> Maximize Editor Screen</a></li>
				<li class="separator"></li>
				<li class="save" title="Save Project Global Variables"><a onClick="saveGlobalVariables()"><i class="icon save"></i> Save</a></li>
			</ul>
		</div>
		<table class="vars">
			<tr>
				<th class="var_name">Variable Name</th>
				<th class="var_value">Variable Value</th>
				<th class="buttons">
					<span class="icon add" onClick="return addNewVariable(this);" title="Add">Add</span>
				</th>
				<th class="var_desc"></th>
			</tr>';

if (is_array($vars)) 
	foreach ($vars as $name => $value) 
		$main_content .= getGlobalVarHtml($name, $value, !in_array($name, $reserved_vars));

$main_content .= '
		</table>
	</div>
</div>
<div class="big_white_panel"></div>
<div class="hide_show_header minimize" onClick="toggleHeader(this)" title="Minimize/Maximize"></div>';

//$head = $CssAndJSFilesOptimizer->prepareHtmlWithOptimizedCssAndJSFiles($head, false);


function getGlobalVarHtml($var_name, $var_value, $is_name_editable = true) {
	$html = '
	<tr>
		<td class="var_name">';
	
	if ($is_name_editable)
		$html .= '<input type="text" class="var_name" name="vars_name[]" value="' . $var_name . '" allownull="false" validationtype="Variable Name" validationregex="/^([\w\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u024F\u1EBD\u1EBC]+)$/g" validationmessage="Invalid variable name." onBlur="onBlurSimpleFormGlobalVarInput(this)" />'; //Do not use '/^([\p{L}\w]+)$/gu' bc it does not work in IE.
	else
		$html .= '<label>' . $var_name . ':</label>
		<input type="hidden" class="var_name" name="vars_name[]" value="' . $var_name . '" />';
	
	$html .= '</td>
		<td class="var_value">';
	
	if (is_array($var_value)) {
		$items = $var_value["items"];
		$value = $var_value["value"];
		$force_raw_keys = $var_value["force_raw_keys"];
		$exists = false;
		
		$html .= '<select class="var_value" name="vars_value[]" allownull="true" onChange="onChangeSimpleFormGlobalVarSelect(this)">';
		$options_html = '';
		
		if (is_array($items)) {
			$is_assoc = $force_raw_keys || array_keys($items) !== range(0, count($items) - 1);
			
			foreach ($items as $k => $v) {
				$selected = isset($value) && ( ($is_assoc && "$k" == "$value") || (!$is_assoc && "$v" == "$value") );
				
				$options_html .= '<option value="' . ($is_assoc ? $k : $v) . '"' . ($selected ? ' selected' : '') . '>' . $v . '</option>';
				
				if ($selected) 
					$exists = true;
			}
		}
		
		$html .= '<option value="__DEFAULT__"' . (!$exists && $value == "__DEFAULT__" ? ' selected' : '') . '>-- default --</option>';
		$html .= $options_html;
		
		if (!$exists && strlen($value) && $value != "__DEFAULT__")
			$html .= '<option selected>' . $value . '</option>';
		
		$html .= '</select>';
	}
	else {
		if ($var_value === true)
			$var_value = "true";
		else if ($var_value === false)
			$var_value = "false";
		else if ($var_value === null)
			$var_value = "null";
		
		$html .= '<input type="text" class="var_value" name="vars_value[]" value="' . str_replace('"', '&quot;', $var_value) . '" allownull="true" onBlur="onBlurSimpleFormGlobalVarInput(this)" />';
	}
	
	$html .= '</td>
		<td class="buttons">
			<span class="icon delete" onClick="$(this).parent().parent().remove();" title="Remove">Remove</span>';
	
	if ($var_name == "project_default_template")
		$html .= '<span class="icon search" onClick="onChooseAvailableTemplate(this)">Search</span>';
	
	$html .= '
		</td>
		<td class="var_desc">
			' . ($var_name ? '(' . ucwords(str_replace("_", " ", $var_name)) . ')' : '') . '
		</td>
	</tr>';
	
	return $html;
}
?>
