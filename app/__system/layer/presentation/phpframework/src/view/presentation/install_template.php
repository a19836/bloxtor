<?php
//NOTE: IF YOU MAKE	ANY CHANGES IN THIS FILE, PLEASE BE SURE THAT THE create_project.php COVERS THAT CHANGES AND DOESN'T BREAK ITS LOGIC.

include $EVC->getUtilPath("BreadCrumbsUIHandler");

$head = '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icon CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/install_template.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/install_template.js"></script>

<script>
var get_store_templates_url = "' . $project_url_prefix . "phpframework/admin/get_store_type_content?type=templates" . '"; //This is a global var
var is_popup = ' . ($popup ? 1 : 0) . ';
var is_zip_file = ' . ($_FILES["zip_file"] ? 1 : 0) . ';
</script>';

$main_content = '
	<div class="top_bar' . ($popup ? " in_popup" : "") . '">
		<header>
			<div class="title" title="' . $path . '">Install New Theme Template in ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($selected_project, $P) . '</div>
			<ul>
				<li class="view_project" data-title="Click here to install a template in a different project"><a onClick="toggleLayerAndProject()"><i class="icon view"></i> Change Project</a></li>
				<li class="continue" data-title="Install Template Now"><a onClick="installTemplate(this)"><i class="icon continue"></i> Install Template Now</a></li>
			</ul>
		</header>
	</div>';

if ($_POST) {
	if (!$status) {
		$error_message = $error_message ? $error_message : "There was an error trying to install this template. Please try again...";
		
		if ($messages) {
			$main_content .= '<ul class="messages">';
			foreach ($messages as $project_name => $msgs) {
				if ($msgs) {
					$main_content .= '<li><label>' . ucfirst($project_name) . ' project\'s installation:</label><ul>';
					foreach ($msgs as $msg) {
						$main_content .= '<li class="' . $msg["type"] . '">' . $msg["msg"] . '</li>';
					}
					$main_content .= '</ul></li>';
				}
			}
			$main_content .= '</ul>';
		}
	}
	else {
		$status_message = 'Template successfully installed!';
		
		$on_success_js_func = $on_success_js_func ? $on_success_js_func : "refreshAndShowLastNodeChilds";
		$main_content .= "<script>if (typeof window.parent.$on_success_js_func == 'function') window.parent.$on_success_js_func();</script>";
	}
}

$main_content .= '<div class="install_template">
	<div class="layer' . (count($layers_projects) == 1 ? ' unique_layer hidden' : '') . '">
		<label>Install in Layer: </label>
		<select onChange="onChangeLayer(this)">';

foreach ($layers_projects as $bn => $layer)
	$main_content .= '<option' . ($bean_name == $bn ? ' selected' : '') . ' value="' . $bn . '">' . $layer["item_label"] . '</option>';

$main_content .= '</select>
	</div>';

foreach ($layers_projects as $bn => $layer) {
	$projects = $layer["projects"];
	
	$main_content .= '
	<div id="project_' . $bn . '" class="project' . ($bean_name == $bn && $selected_project && $projects[$selected_project] ? ' hidden' : '') . '">
		<label>Install in Project: </label>
		<select onChange="onChangeProject(this)">';
		
	if ($projects) {
		$previous_folder = null;
		
		foreach ($projects as $project_name => $project) {
			//if ($project["item_type"] != "project_common") { //common project is allow too, bc I can have templates to be shared across multiple projects.
				$project_folder = dirname($project_name);
				$project_folder = $project_folder == "." ? "" : $project_folder;
				
				if ($project_folder && $project_folder != $previous_folder) {
					$main_content .= '<option disabled>' . str_repeat("&nbsp;&nbsp;&nbsp;", substr_count($project_folder, '/')) . basename($project_folder) . '</option>';
					
					$previous_folder = $project_folder;
				}
				
				$main_content .= '<option' . ($bean_name == $bn && $selected_project == $project_name ? ' selected' : '') . ' value="' . $project_name . '">' . str_repeat("&nbsp;&nbsp;&nbsp;", substr_count($project_name, '/')) . basename($project_name) . '</option>';
			//}
		} 
	}
	
	$main_content .= '
		</select>
	</div>';
}

$main_content .= '
	<ul>
		' . ($get_store_templates_url ? '<li><a href="#store">Store Templates</a></li>' : '') . '
		<li><a href="#local">Upload Local Template</a></li>
	</ul>
	<div id="local" class="file_upload">
		<div class="title">Install a local template from your computer (.zip file)</div>';

foreach ($layers_projects as $bn => $layer) {
	$bfn = $layer["bean_file_name"];
	$projects = $layer["projects"];
	$query_str = $_SERVER["QUERY_STRING"];
	$query_str = preg_replace("/(^|&)(bean_name|bean_file_name)=[^&]*/", "", $query_str);
	
	$main_content .= '
		<form id="form_' . $bn . '" class="hidden" action="?bean_name=' . $bn . '&bean_file_name=' . $bfn . $query_str . '" method="post" enctype="multipart/form-data">
			<input type="hidden" name="project" value="" />
			
			<input class="upload_file" type="file" name="zip_file">
		</form>';
}

$main_content .= '
		' . ($templates_download_page_url ? '<div class="go_to_templates_download_page">To download templates to your local computer, please click <a href="' . $templates_download_page_url . '" target="download_templates">here</a></div>' : '') . '
	
	</div>
	
	' . ($get_store_templates_url ? '
	<div id="store" class="install_store_template">
		<div class="title">Install a template from our store</div>
		<div class="search_template">
			<i class="icon search active"></i>
			<input placeHolder="Search" onKeyUp="searchTemplates(this)" />
			<i class="icon close" onClick="resetSearchTemplates(this)"></i>
		</div>
		<ul>
			<li class="loading">Loading templates from store...</li>
		</ul>
	</div>' : '') . '
</div>';
?>
