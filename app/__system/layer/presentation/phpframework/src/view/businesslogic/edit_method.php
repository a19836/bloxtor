<?php
$ft = str_replace("edit_file_", "", $file_type);
$path_extra = hash('crc32b', "$bean_file_name/$bean_name/$path/$class_id/" . ($ft == "class_method" ? $method_id : $function_id) );
$get_workflow_tasks_id = "business_logic_workflow&path_extra=_$path_extra";
$get_tmp_workflow_tasks_id = "business_logic_workflow_tmp&path_extra=_${path_extra}_" . rand(0, 1000);

include $EVC->getViewPath("admin/edit_file_class_method");

$head .= '
<link rel="stylesheet" href="' . $project_url_prefix . 'css/businesslogic/edit_method.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/businesslogic/edit_method.js"></script>
<script>
	if (is_obj_valid) {
		save_object_url = save_object_url.replace("/admin/save_file_' . ($ft == "class_method" ? "class_method" : "function") . '?", "/businesslogic/save_' . ($ft == "class_method" ? "method" : "function") . '?");
	}
</script>
';

$show_business_logic_service_first = (!$method_id && $ft == "class_method") || (!$function_id && $ft == "function");
$is_bl_service_select_html = '<select class="is_business_logic_service advanced_settings" onChange="hideOrShowIsBusinessLogicService(this);">
				<option value="1"' . ($obj_data["is_business_logic_service"] || $show_business_logic_service_first ? " selected" : "") . '>Is business logic service</option>
				<option value="0"' . ($obj_data["is_business_logic_service"] || $show_business_logic_service_first ? "" : " selected") . '>Is regular function</option>
			</select>';

$toggle_advanced_settings_html = '<li class="toggle_advanced_settings" title="Toggle Advanced Settings"><a onClick="toggleBLAdvancedSettings()"><i class="icon toggle_ids"></i> <span>Show Advanced Settings</span> <input type="checkbox"/></a></li>';

$main_content .= '
<script>
var toggle_advanced_settings_html = \'' . str_replace("'", "\\'", str_replace("\n", "", $toggle_advanced_settings_html)) . '\';
	
if (is_obj_valid) {
	$(".top_bar .title input").after(\'' . str_replace("'", "\\'", str_replace("\n", "", $is_bl_service_select_html)) . '\');
}
</script>';
?>
