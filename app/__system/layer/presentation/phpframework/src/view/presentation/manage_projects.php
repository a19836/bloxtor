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

$files = isset($files) ? $files : null;
$default_layer = isset($default_layer) ? $default_layer : null;
$default_project = isset($default_project) ? $default_project : null;
$bean_folder = isset($bean_folder) ? $bean_folder : null;

$admin_home_project_page_url = $project_url_prefix . "admin/admin_home_project?filter_by_layout=#filter_by_layout#";

$head = '
<!-- Add FileManager JS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/file_manager.css" type="text/css" charset="utf-8" />

<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icon CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/manage_projects.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/manage_projects.js"></script>

<script>
var admin_home_project_page_url = \'' . $admin_home_project_page_url . '\';
</script>';

$is_single_presentation_layer = count($files) == 1;

$main_content = '
<div class="top_bar">
	<header>
		<div class="title">
			Manage Projects' . ($is_single_presentation_layer ? '' : ' in the presentation layer') . ': 
			<select class="layer' . ($is_single_presentation_layer ? ' hidden' : '') . '" onChange="showProjectsLayer(this)">';

if (is_array($files))
	foreach ($files as $bn => $layer_props) {
		$layer_bean_file_name = isset($layer_props["bean_file_name"]) ? $layer_props["bean_file_name"] : null;
		$layer_item_label = isset($layer_props["item_label"]) ? $layer_props["item_label"] : null;
		
		$main_content .= '<option' . ($bn == $bean_name && $layer_bean_file_name == $bean_file_name ? " selected" : "") . ' bean_name="' . $bn . '" bean_file_name="' . $layer_bean_file_name . '">' . $layer_item_label . '</option>';
	}
	
$main_content .= '		
			</select>
			<span class="info' . ($is_single_presentation_layer ? ' hidden' : '') . '">' . ($default_layer == $bean_folder ? 'This is the default layer' : 'This is NOT the default layer') . '</span>
		</div>
		<ul>
			<li class="save" data-title="Save"><a onClick="submitForm(this)"><i class="icon save"></i> Save</a>
		</ul>
	</header>
</div>
<div class="projects_list with_top_bar_section">';

$exists = false;

if (is_array($files))
	foreach ($files as $bn => $layer_props) {
		$layer_bean_file_name = isset($layer_props["bean_file_name"]) ? $layer_props["bean_file_name"] : null;
		
		if ($bn == $bean_name && $layer_bean_file_name == $bean_file_name) {
			$exists = true;
			$projects = isset($layer_props["projects"]) ? $layer_props["projects"] : null;
			
			$add_url = $project_url_prefix . "phpframework/presentation/create_project?bean_name=$bean_name&bean_file_name=$bean_file_name&path=#path#&popup=1&on_success_js_func=onSuccessfullAddProject";
			$edit_url = $project_url_prefix . "phpframework/presentation/edit_project_details?bean_name=$bean_name&bean_file_name=$bean_file_name&path=#path#&popup=1&on_success_js_func=onSuccessfullEditProject";
			$remove_url = $project_url_prefix . "phpframework/presentation/manage_file?bean_name=$bean_name&bean_file_name=$bean_file_name&action=remove&item_type=presentation&path=#path#";
			$edit_global_vars_url = $project_url_prefix . "phpframework/presentation/edit_project_global_variables?bean_name=$bean_name&bean_file_name=$bean_file_name&path=#project#src/config/pre_init_config.php";
			$edit_config_url = $project_url_prefix . "phpframework/presentation/edit_config?bean_name=$bean_name&bean_file_name=$bean_file_name&path=#project#src/config/config.php";
			$edit_init_url = $project_url_prefix . "phpframework/presentation/edit_init?bean_name=$bean_name&bean_file_name=$bean_file_name&item_type=presentation&path=#project#src/config/init.php";
			$manage_references_url = $project_url_prefix . "phpframework/presentation/manage_references?bean_name=$bean_name&bean_file_name=$bean_file_name&path=#project#";
			
			$view_project_url = $project_url_prefix . "phpframework/presentation/view_project?bean_name=$bean_name&bean_file_name=$bean_file_name&path=#project#";
			
			$projects_options_html = "";
			$default_project_exists = false;
			$table_html = "";
			
			if (is_array($projects)) {
				$previous_folder = null;
				
				foreach ($projects as $project_name => $project_props) {
					//prepare projects_options_html
					if ($default_project == $project_name)
						$default_project_exists = true;
					
					$project_folder = dirname($project_name);
					$project_folder = $project_folder == "." ? "" : $project_folder;
					
					if ($project_folder && $project_folder != $previous_folder) {
						$projects_options_html .= '<option disabled>' . str_repeat("&nbsp;&nbsp;&nbsp;", substr_count($project_folder, '/')) . basename($project_folder) . '</option>';
						
						$previous_folder = $project_folder;
					}
					
					$projects_options_html .= '<option' . ($default_project == $project_name ? " selected" : "") . ' value="' . $project_name . '">' . str_repeat("&nbsp;&nbsp;&nbsp;", substr_count($project_name, '/')) . basename($project_name) . '</option>';
					
					//prepare table_html
					$project_element_type_path = isset($project_props["element_type_path"]) ? $project_props["element_type_path"] : "";
					$project_item_type = isset($project_props["item_type"]) ? $project_props["item_type"] : null;
					
					$table_html .= '
					<tr>
						<td class="project">' . $project_name . '</td>
						<td class="path">' . $project_element_type_path . '</td>
						<td class="buttons">';
						
					if ($project_item_type != "project_common") {
						$table_html .= '<span class="icon edit" onClick="editProject(this, \'' . str_replace("#path#", $project_name, $edit_url) . '\');" title="Click here to edit the project details"></span>
							<span class="icon edit_project_global_variables" onClick="goToUrl(this, \'' . str_replace("#project#", $project_element_type_path, $edit_global_vars_url) . '\');" title="Click here to edit the project global variables"></span>
							<span class="icon edit_config" onClick="goToUrl(this, \'' . str_replace("#project#", $project_element_type_path, $edit_config_url) . '\');" title="Click here to edit the project config file"></span>
							<span class="icon edit_init" onClick="goToUrl(this, \'' . str_replace("#project#", $project_element_type_path, $edit_init_url) . '\');" title="Click here to edit the project init file"></span>
							<span class="icon manage_references" onClick="goToUrl(this, \'' . str_replace("#project#", $project_element_type_path, $manage_references_url) . '\');" title="Click here to manage the references for this project"></span>
							<span class="icon view" onClick="openWindow(this, \'' . str_replace("#project#", $project_element_type_path, $view_project_url) . '\', \'project\');" title="Click here to view project"></span>
							<span class="icon delete" onClick="deleteProject(this, \'' . str_replace("#path#", $project_element_type_path, $remove_url) . '\')" title="Click here to delete this project permanently"></span>';
					}
					
					$table_html .= '</td>
					</tr>';
				}
			}
			
			if ($default_project && !$default_project_exists)
				$projects_options_html .= '<option value="' . $default_project . '" selected>' . $default_project . ' - PROJECT DOES NOT EXIST!</option>';
			
			$main_content .= '
			<form method="post">
				<div class="default_project">
					<label>Layer Default Project: </label>
					<select name="default_project">' . $projects_options_html . '</select>
				</div>
			</form>
				
			<div class="layer_projects">	
			<table>
				<tr>
					<th class="table_header project">Project</th>
					<th class="table_header path">Path</th>
					<th class="table_header buttons">
						<span class="icon add" onClick="addProject(this, \'' . $add_url . '\', ' . ($get_store_programs_url ? "true" : "false") . ');" title="Click here to add a new project">Add</span>
					</th>
				</tr>
				' . $table_html . '
			</table>
			</div>';
			
			if (!empty($save_message))
				$main_content .= '<script>
					$(function () {
						StatusMessageHandler.showMessage(\'' . $save_message . '\');
					});
				</script>';
		}
	}

if (!$files)
	$main_content .= '<div class="error">No available layers</div>';
else if (!$exists)
	$main_content .= '<div class="error">No projects for this layer</div>';

$main_content .= '</div>';
?>
