<?php
include $EVC->getUtilPath("WorkFlowUIHandler");
include $EVC->getUtilPath("BreadCrumbsUIHandler");

$filter_by_layout_url_query = LayoutTypeProjectUIHandler::getFilterByLayoutURLQuery($filter_by_layout);
$is_obj_valid = $obj_data || !$map_id;

if ($is_obj_valid) {
	$get_layer_sub_files_url = $project_url_prefix . "admin/get_sub_files?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#";

	$WorkFlowUIHandler = new WorkFlowUIHandler($WorkFlowTaskHandler, $project_url_prefix, $project_common_url_prefix, $external_libs_url_prefix, $user_global_variables_file_path, $webroot_cache_folder_path, $webroot_cache_folder_url);
	$WorkFlowQueryHandler = new WorkFlowQueryHandler($WorkFlowUIHandler, $project_url_prefix, $project_common_url_prefix, $db_drivers, $selected_db_broker, $selected_db_driver, $selected_type, $selected_table, $selected_tables_name, $selected_table_attrs, $map_php_types, $map_db_types);

	//PREPARING HEADER
	$head = $WorkFlowUIHandler->getHeader();
	$head .= LayoutTypeProjectUIHandler::getHeader();
}

$head .= '
<!-- Add MyTree main JS and CSS files -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/jquerymytree/css/style.min.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquerymytree/js/mytree.js"></script>

<!-- Add FileManager JS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/file_manager.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/file_manager.js"></script>

<!-- Add Layout CSS and JS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/layout.js"></script>

<!-- Edit QUERY JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/dataaccess/edit_query.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/dataaccess/edit_query.js"></script>

<!-- Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/dataaccess/edit_map.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/dataaccess/edit_map.js"></script>';

if ($is_obj_valid) {
	$head .= $WorkFlowQueryHandler->getHeader();
	$head .= $WorkFlowQueryHandler->getDataAccessJavascript($bean_name, $bean_file_name, $path, $item_type, $hbn_obj_id, $get_layer_sub_files_url);
	$head .= '<script>
	var save_data_access_object_url = \'' . $project_url_prefix . 'phpframework/dataaccess/save_map?bean_name=' . $bean_name . '&bean_file_name=' . $bean_file_name . '&path=' . $path . '&item_type=' . $item_type . '&obj=' . $hbn_obj_id . '&relationship_type=' . $relationship_type . '\';
	var remove_data_access_object_url = \'' . $project_url_prefix . 'phpframework/dataaccess/remove_map?bean_name=' . $bean_name . '&bean_file_name=' . $bean_file_name . '&path=' . $path . '&item_type=' . $item_type . '&obj=' . $hbn_obj_id . '&relationship_type=' . $relationship_type . '&map=#obj_id#&query_type=' . $query_type . '\';
	var old_obj_id = \'' . $map_id . '\';
	</script>';

	//PREPARING GLOBAL WORKFLOW
	$main_content = $WorkFlowQueryHandler->getGlobalTaskFlowChar();

	//PREPARING MAIN CONTENT FOR MAP OBJECT
	$main_content .= '<div class="edit_map">
		<div class="top_bar' . ($popup ? " in_popup" : "") . '">
			<header>
				<div class="title" title="' . $path . '">
					' . ($map_id ? "Edit" : "Add") . ' ' . ($query_type == "parameter_map" ? "Parameter" : "Result") . ' Map: <span class="map_name"></span> in ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($is_hbn_obj_equal_to_file_name ? dirname($file_path) . "/$hbn_obj_id" : $file_path, $obj, $is_hbn_obj_equal_to_file_name) . '
				</div>
				
				<ul>
					<li class="save" data-title="Save Map"><a onClick="saveMapObject()"><i class="icon save"></i> Save</a></li>
					<li class="sub_menu" onClick="openSubmenu(this)">
						<i class="icon sub_menu"></i>
						<ul>
							<li class="update_automatically" title="Create Map Automatically"><a onClick="createSingleMapParameterOrResultMapAutomatically()"><i class="icon update_automatically"></i> Create Map Automatically</a></li>
							<li class="separator"></li>
							<li class="full_screen" title="Maximize/Minimize Editor Screen"><a onClick="toggleFullScreen(this)"><i class="icon full_screen"></i> Maximize Editor Screen</a></li>
							<li class="separator"></li>
							<li class="save" title="Save Map"><a onClick="saveMapObject()"><i class="icon save"></i> Save</a></li>
						</ul>
					</li>
				</ul>
			</header>
		</div>';

	//PREPARING POPUPS
	//	PREPARING CHOOSE TABLE OR ATTRIBUTE
	$main_content .= $WorkFlowQueryHandler->getChooseQueryTableOrAttributeHtml("choose_db_table_or_attribute");
	
	//	PREPARING CHOOSE OBJ TYPE FROM FILE MANAGER
	$main_content .= $WorkFlowQueryHandler->getChooseDAOObjectFromFileManagerHtml("choose_dao_object_from_file_manager");
	
	//PREPARING PARAMETER MAP
	$obj_html = $query_type == "parameter_map" ? $WorkFlowQueryHandler->getParameterMapHTML("map", $obj_data, $map_php_types, $map_db_types) : $WorkFlowQueryHandler->getResultMapHTML("map", $obj_data, $map_php_types, $map_db_types);
	
	$main_content .= '
<div class="data_access_obj with_top_bar_section">	
	<div class="relationships">
		<div class="' . ($query_type == "parameter_map" ? 'parameters_maps' : 'results_maps') .' map">
			<div class="' . ($query_type == "parameter_map" ? 'parameters' : 'results') .' map">
				' . $obj_html . '
			</div>
		</div>
	</div>
</div>';
}
else {
	$title = ($query_id ? "Edit" : "Add") . ' ' . ($query_type == "parameter_map" ? "Parameter" : "Result")  . " Map";
	
	$main_content = '
		<div class="top_bar' . ($popup ? " in_popup" : "") . '">
			<header>
				<div class="title" title="' . $path . '">' . $title . '</div>
			</header>
		</div>';
	
	$main_content .= '<div class="error">Error: The system couldn\'t detect the selected object. Please refresh and try again...</div>';
}

//$head = $CssAndJSFilesOptimizer->prepareHtmlWithOptimizedCssAndJSFiles($head, false);
?>
