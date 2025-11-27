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

include $EVC->getUtilPath("UserAuthenticationUIHandler");
include $EVC->getUtilPath("WorkFlowPresentationHandler");

$choose_bean_layer_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?bean_name=#bean_name#&bean_file_name=#bean_file_name#&path=#path#";
$upload_bean_layer_files_from_file_manager_url = $project_url_prefix . "admin/upload_file?bean_name=#bean_name#&bean_file_name=#bean_file_name#&path=#path#";
$get_file_properties_url = $project_url_prefix . "phpframework/admin/get_file_properties?bean_name=#bean_name#&bean_file_name=#bean_file_name#&path=#path#&class_name=#class_name#&type=#type#";

$head = '
<!-- Add MD5 JS File -->
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquery/js/jquery.md5.js"></script>

<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icons CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add MyTree main JS and CSS files -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/jquerymytree/css/style.min.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquerymytree/js/mytree.js"></script>

<!-- Add FileManager JS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/file_manager.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/file_manager.js"></script>

<!-- Add Layout CSS and JS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/layout.js"></script>

<!-- Add Local CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/user/user.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/user/user.js"></script>

<script>
var get_layout_type_permissions_url = \'' . $project_url_prefix . 'user/get_layout_type_permissions?layout_type_id=#layout_type_id#\';
';
$head .= WorkFlowPresentationHandler::getPresentationBrokersHtml($presentation_brokers, $choose_bean_layer_files_from_file_manager_url, $get_file_properties_url, $upload_bean_layer_files_from_file_manager_url);
$head .= WorkFlowPresentationHandler::getBusinessLogicBrokersHtml($business_logic_brokers, $choose_bean_layer_files_from_file_manager_url, $get_file_properties_url);
$head .= WorkFlowPresentationHandler::getDataAccessBrokersHtml($data_access_brokers, $choose_bean_layer_files_from_file_manager_url);
$head .= '
	var permissions = ' . json_encode($permissions) . ';
	var permission_belong_name = "' . UserAuthenticationHandler::$PERMISSION_BELONG_NAME . '";
	var permission_referenced_name = "' . UserAuthenticationHandler::$PERMISSION_REFERENCED_NAME . '";
	var layer_object_type_id = ' . $layer_object_type_id . ';
	var loaded_layout_type_permissions = {};
	
	$(function() {
		$(".layout_type_permissions_content").tabs();
		
		prepareFileTreeCheckbox( $(".layout_type_permissions_content input[type=checkbox]") );
		
		updateLayoutTypePermissions( $(".layout_type select[name=layout_type_id]")[0] );
	});
</script>';

$main_content = '
<div id="menu">' . UserAuthenticationUIHandler::getMenu($UserAuthenticationHandler, $project_url_prefix, $entity) . '</div>
<div id="content">
	<div class="top_bar">
		<header>
			<div class="title">Manage Layout Type Permissions</div>
			<ul>
				<li class="save" data-title="Save"><a onClick="submitForm(this)"><i class="icon save"></i> Save</a></li>
			</ul>
		</header>
	</div>
	
	<div class="layout_type_permissions_list">
		<form method="post" onSubmit="return saveLayoutTypePermissions();">
			<div class="layout_type">
				<label>Layout Type: </label>
				<select name="type_id" onChange="onChangeLayoutType(this)">';

foreach ($available_types as $tid => $tname)
	$main_content .= '<option value="' . $tid . '" ' . ($type_id == $tid ? ' selected' : '') . '>' . $tname . '</option>';

$main_content .= '	</select>
				<select name="layout_type_id" onChange="updateLayoutTypePermissions(this)">';

if ($type_id == 0) {
	$is_single_presentation_layer = count($presentation_projects_by_folders) == 1;
	
	foreach ($presentation_projects_by_folders as $layer_label => $projs) {
		if (!$is_single_presentation_layer)
			$main_content .= '<optgroup label="' . $layer_label . '">';
		
		$main_content .= getProjectsHtml($projs, $layout_type_id);
		
		if (!$is_single_presentation_layer)
			$main_content .= '</optgroup>';
	}
}

foreach ($layout_types as $lname => $lid)
	$main_content .= '<option value="' . $lid . '" ' . ($layout_type_id == $lid ? ' selected' : '') . '>' . $lname . '</option>';

$main_content .= '	</select>
			</div>
			
			<div class="layout_type_permissions_content">
				<ul class="tabs">
					<li><a href="#belonging_to_layout">Belonging to Layout</a></li>
					<li><a href="#referenced_in_layout">Referenced in Layout</a></li>
				</ul>
				
				<div id="belonging_to_layout">
					<ul>
				' . getLayersHtml($layers, $layers_props, $layers_object_id, $layers_label, $layer_object_id_prefix, $choose_bean_layer_files_from_file_manager_url, $layer_object_type_id, isset($permissions[UserAuthenticationHandler::$PERMISSION_BELONG_NAME]) ? $permissions[UserAuthenticationHandler::$PERMISSION_BELONG_NAME] : null, "removeAllThatIsFolderFromTree") . '
					</ul>
				</div>
				
				<div id="referenced_in_layout">
					<ul>
				' . getLayersHtml($layers_to_be_referenced, $layers_props, $layers_object_id, $layers_label, $layer_object_id_prefix, $choose_bean_layer_files_from_file_manager_url, $layer_object_type_id, isset($permissions[UserAuthenticationHandler::$PERMISSION_REFERENCED_NAME]) ? $permissions[UserAuthenticationHandler::$PERMISSION_REFERENCED_NAME] : null, "removeAllThatCannotBeReferencedFromTree") . '
					</ul>
				</div>
				
				<div class="loaded_permissions_by_objects hidden"></div>
			</div>
			<div class="buttons">
				<div class="submit_button">
					<input type="submit" name="save" value="Save" />
				</div>
			</div>
		</form>
	</div>
</div>';
		
function getProjectsHtml($projs, $layout_type_id, $prefix = "") {
	$html = "";
	
	if (is_array($projs))
		foreach ($projs as $proj_name => $proj_id) {
			if (is_array($proj_id))
				$html .= '<option disabled>' . $prefix . $proj_name . '</option>' . getProjectsHtml($proj_id, $layout_type_id, $prefix . "&nbsp;&nbsp;&nbsp;");
			else
				$html .= '<option value="' . $proj_id . '" ' . ($layout_type_id == $proj_id ? ' selected' : '') . '>' . $prefix . $proj_name . '</option>';
		}
	
	return $html;
}

function getLayersHtml($layers, $layers_props, $layers_object_id, $layers_label, $layer_object_id_prefix, $choose_bean_layer_files_from_file_manager_url, $object_type_id, $permission_id, $tree_ajax_callback_after) {
	$html = '';
	
	foreach ($layers as $layer_type_name => $layer_type) {
		$html .= '<li id="file_tree_' . $permission_id . '_' . $layer_type_name . '" class="mytree">
					<label><i class="icon main_node main_node_' . $layer_type_name . '"></i> ' . strtoupper(str_replace("_", " ", $layer_type_name)) . '</label>
					<ul>';
		
		if ($layer_type)
			foreach ($layer_type as $layer_name => $layer) {
				$layer_props = isset($layers_props[$layer_type_name][$layer_name]) ? $layers_props[$layer_type_name][$layer_name] : null;
				$object_id = "$layer_object_id_prefix/" . (isset($layers_object_id[$layer_type_name][$layer_name]) ? $layers_object_id[$layer_type_name][$layer_name] : null);
				
				$html .= '<li data-jstree=\'{"icon":"main_node_' . (isset($layer_props["item_type"]) ? $layer_props["item_type"] : "") . '"}\'>
							<label>
								<input type="checkbox" name="permissions_by_objects[' . $object_type_id . '][' . $object_id . '][]" value="' . $permission_id . '" />
								' . (isset($layers_label[$layer_type_name][$layer_name]) ? $layers_label[$layer_type_name][$layer_name] : "") . '
							</label>';
				
				if ($layer_type_name == "db_layers") {
					$html .= '<ul>';
					
					foreach ($layer as $folder_name => $folder) {
						$object_id = "$layer_object_id_prefix/" . (isset($layers_object_id[$layer_type_name][$layer_name]) ? $layers_object_id[$layer_type_name][$layer_name] : "") . "/$folder_name";
						
						$html .= '<li data-jstree=\'{"icon":"db_driver"}\'>
										<label>
											<input type="checkbox" name="permissions_by_objects[' . $object_type_id . '][' . $object_id . '][]" value="' . $permission_id . '" />
											' . $folder_name . '
										</label>
									</li>';
					}
					
					$html .= '</ul>';
				}
				else {
					$url = $choose_bean_layer_files_from_file_manager_url;
					$url = str_replace("#bean_name#", isset($layer_props["bean_name"]) ? $layer_props["bean_name"] : null, $url);
					$url = str_replace("#bean_file_name#", isset($layer_props["bean_file_name"]) ? $layer_props["bean_file_name"] : null, $url);
					$url = str_replace("#path#", "", $url);
					
					$html .= '<ul url="' . $url . '" object_id_prefix="' . $object_id . '"></ul>';
				}
				
				$html .= '</li>';
			}
		
		$html .= '	</ul>
					<script>				
						var layerFromFileManagerTree_' . $permission_id . '_' . $layer_type_name . ' = new MyTree({
							multiple_selection : true,
							toggle_children_on_click : true,
							ajax_callback_before : prepareLayerNodes1,
							ajax_callback_after : ' . $tree_ajax_callback_after . ',
							on_select_callback : toggleFileTreeCheckbox,
						});
						layerFromFileManagerTree_' . $permission_id . '_' . $layer_type_name . '.init("file_tree_' . $permission_id . '_' . $layer_type_name . '");
					</script>
				</li>';
	}
	
	return $html;
}
?>
