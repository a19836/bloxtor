<?php
include $EVC->getUtilPath("BreadCrumbsUIHandler");

$head = '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icons CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/admin/install_program.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/admin/install_program.js"></script>

<script>
var modules_admin_panel_url = \'' . $project_url_prefix . 'phpframework/admin/manage_modules?filter_by_layout=' . $filter_by_layout . '&popup=1\';
</script>';

$main_content = '<div class="install_program">';

$title = "Program Installation" . ($bean_name && $bean_file_name ? ' in ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($selected_project_id, $P) : '');

if ($step >= 3) {
	
	$main_content .= '<div class="step_3">
		<div class="top_bar' . ($popup ? ' in_popup' : '') . '">
			<header>
				<div class="title" title="' . $path . '">' . $title . '</div>
				<ul style="display:none;">
					<li class="continue" data-title="Continue"><a onClick="submitForm(this);"><i class="icon continue"></i> Continue</a></li>
				</ul>
			</header>
		</div>
		
		<div class="program_info">
			<div class="program_name">Installing program: "<span>' . ($program_label ? $program_label : $program_name) . '</span>"</div>
		</div>';
	
	if ($errors) {
		$main_content .= '<label class="error_title">There were some erros installing this program, this is:</label>
		<ul class="errors_list">';
		
		$dbs_errors = $errors["dbs"];
		$files_errors = $errors["files"];
		unset($errors["files"]);
		unset($errors["dbs"]);
		
		foreach($errors as $k => $v)
			if (is_string($v))
				$main_content .= '<li>' . $v . '</li>';
		
		foreach($dbs_errors as $k => $v)
			if (is_string($v))
				$main_content .= '<li>' . htmlentities($v) . '</li>';
		
		if ($files_errors) {
			$main_content .= '<li>The following files could not be copied:</li>
			<ul>';
			
			foreach($files_errors as $src_path => $dst_path)
				$main_content .= '<li>' . (is_numeric($src_path) ? "" : $src_path . ' => ') . $dst_path . '</li>';
			
			$main_content .= '</ul>';
		}
		
		$main_content .= '</ul>';
	}
	else if ($error_message) {
		$main_content .= '<label class="error">' . $error_message . '</label>';
	}
	else if ($next_step_html) {
		$main_content .= '
		<form method="post" enctype="multipart/form-data">
			<input type="hidden" name="step" value="' . $next_step . '" />
			<textarea class="hidden" name="post_data">' . json_encode($post_data) . '</textarea>
			
			' . $next_step_html . '
			
			<input type="hidden" name="continue" value="Continue" />
		</form>
		<script>
			$(".top_bar > header > ul").show();
		</script>';
	}
	else {
		$main_content .= '<label class="ok">' . $status_message . '</label>
		<script>
			$(".program_info").hide();
		</script>
		
		<div class="users_permissions">
			<label>Users Permissions Settings</label>
			<span class="info">
				In case this program have authenticated pages and you wish to add some users permissions, please follow the steps bellow:
				<ol>
					<li>Open the "<a href="javascript:void(0)" onClick="openUsersManagementAdminPanelPopup(this)">Manage Modules Admin Panel</a>"</li>
					<li>Expand the "User Module" by clicking in the "expand" button <span class="icon maximize"></span></li>
					<li>Then click in the "settings" button <span class="icon settings"></span> at the right side of any sub-module of the "User Module"</li>
					<li>The "User Module Admin Panel" will be opened...</li>
					<li>Then choose the correspondent project and edit the users permissions...</li>
				</ol>
			</span>
			
			<div class="users_management_admin_panel_popup with_iframe_title myfancypopup">
				<iframe></iframe>
			</div>
			
			<script>
				//Refreshing blocks folder in main tree of the admin advanced panel
				if (window.parent && window.parent.parent && typeof window.parent.parent.refreshAndShowLastNodeChilds == "function" && window.parent.parent.mytree && window.parent.parent.mytree.tree_elm) {
					window.parent.parent.refreshAndShowLastNodeChilds();
					
					var project_folder_id = window.parent.parent.$("#" + window.parent.parent.last_selected_node_id).parent().closest("li[data-jstree=\'{\"icon\":\"project\"}\']").attr("id");
					window.parent.parent.refreshAndShowNodeChildsByNodeId(project_folder_id);
				}
			</script>
		</div>';
	}
	
	if ($messages)
		$main_content .= '<label class="error_title">Important messages:</label>
		<ul class="messages_list">
			<li>'  . implode("</li><li>", $messages) . '</li>
		</ul>';
	
	$main_content .= '</div>';
}
else if ($step == 2) {
	$main_content .= '<div class="step_2">
		<div class="top_bar' . ($popup ? ' in_popup' : '') . '">
			<header>
				<div class="title" title="' . $path . '">' . $title . '</div>
				<ul>
					<li class="continue" data-title="Continue"><a onClick="submitForm(this);"><i class="icon continue"></i> Continue</a></li>
				</ul>
			</header>
		</div>
		
		<div class="program_info">
			<div class="program_name">Installing program: "<span>' . ($program_label ? $program_label : $program_name) . '</span>"</div>
		</div>';
	
	if ($db_drivers)
		$main_content .= '<div class="db_drivers"><label>The DBs where the program will be installed: </label><ul><li>' . implode("</li><li>", $db_drivers) . '</li></ul></div>';
	
	$main_content .= '<div class="layers">
		<label>The files from the uploaded file will be copied to the following folders:</label>
		<ul>';
	
	if ($layers) {
		foreach ($layers as $layer_type => $items) {
			$layer_label = "";
			
			switch ($layer_type) {
				case "ibatis": $layer_label = "Data Access - Ibatis Layers"; break;
				case "hibernate": $layer_label = "Data Access - Hibernate Layers"; break;
				case "businesslogic": $layer_label = "Business Logic Layers"; break;
				case "presentation": $layer_label = "Presentation Layers"; break;
				case "vendor": $layer_label = "Vendors"; break;
			}
			
			$main_content .= '<li>' . $layer_label . ':
			<ul>';
			
			foreach ($items as $broker_name => $layer_props) {
				$layer_files = $all_files[$broker_name];
				
				if ($layer_type == "vendor" && !is_array($layer_files)) {
					$file_exists = $layer_files;
					$extra = $file_exists ? ($overwrite ? " (Already exists and will be replaced!)" : " (Already exists and will be backed-up!)") : "";
					$main_content .= '<li class="' . ($file_exists ? 'file_exists' : 'file_ok') . '">' . $broker_name . $extra . '</li>';
				}
				else if (is_array($layer_files) && $layer_type == "presentation") {
					$main_content .= '<li class="broker"><label>' . ucwords($broker_name) . ':</label>
					<ul>';
					
					foreach ($layer_files as $project => $project_files) {
						$main_content .= '<li class="project"><label>' . ucwords($project) . ':</label>
						<table>';
						
						foreach ($project_files as $file_path => $file_exists) {
							$is_config = strpos($file_path, "config/") === 0;
							$extra = $file_exists ? ($overwrite && !$is_config ? "(Already exists and will be replaced!)" : "(Already exists and will be " . ($is_config ? "merged" : "backed-up") . "!)") : "";
							$pretty_file_path = substr($file_path, -4) == ".php" ? substr($file_path, 0, -4) : $file_path;
							$main_content .= '<tr class="' . ($file_exists ? 'file_exists' : 'file_ok') . '"><td>' . $pretty_file_path . '</td><td>' . ($file_exists ? "EXISTS" : "") . '</td><td>' . $extra . '</td></tr>';
						}
						
						$main_content .= '</table></li>';
					}
					
					$main_content .= '</ul></li>';
				}
				else if (is_array($layer_files)) {
					$main_content .= '<li class="broker"><label>' . ucwords($broker_name) . ':</label>
					<table>';
					
					foreach ($layer_files as $file_path => $file_exists) {
						$extra = $file_exists ? ($overwrite ? "(Already exists and will be replaced!)" : "(Already exists and will be backed-up!)") : "";
						$pretty_file_path = substr($file_path, -4) == ".php" ? substr($file_path, 0, -4) : $file_path;
						$main_content .= '<tr class="' . ($file_exists ? 'file_exists' : 'file_ok') . '"><td>' . $pretty_file_path . '</td><td>' . ($file_exists ? "EXISTS" : "") . '</td><td>' . $extra . '</td></tr>';
					}
					
					$main_content .= '</table></li>';
				}
			}
			
			$main_content .= '</ul></li>';
		}
	}
	else
		$main_content .= '<li>No layers selected to copy files...</li>';
	
	$main_content .= '
			</ul>
		</div>
		
		<form method="post" enctype="multipart/form-data">
			<input type="hidden" name="step" value="3" />
			<textarea class="hidden" name="post_data">' . json_encode($_POST) . '</textarea>
			
			<input type="hidden" name="continue" value="Continue" />
		</form>
	</div>';
}
else if ($step == 1) {
	$main_content .= '
	<div class="step_1">
		<div class="top_bar' . ($popup ? ' in_popup' : '') . '">
			<header>
				<div class="title" title="' . $path . '">' . $title . '</div>
				<ul>
					<li class="continue" data-title="Continue"><a onClick="submitForm(this);"><i class="icon continue"></i> Continue</a></li>
				</ul>
			</header>
		</div>
		
		<div class="program_info">
			<div class="program_name">Installing program: "<span>' . ($info && $info["label"] ? $info["label"] : $program_name) . '</span>"</div>
			<div class="program_description">' . ($info && $info["description"] ? str_replace("\n", "<br/>", $info["description"]) : "") . '</div>
			<div class="program_with_db">' . ($program_with_db ? '<span class="icon db"></span> This program uses database' : '') . '</div>
		</div>';
	
	$main_content .= '
		<form method="post" enctype="multipart/form-data">
			<input type="hidden" name="step" value="2" />
			<input type="hidden" name="program_name" value="' . $program_name . '" />
			<input type="hidden" name="program_label" value="' . str_replace('"', '', $info["label"]) . '" />
			<input type="hidden" name="program_with_db" value="' . $program_with_db . '" />
			';
	
	if ($brokers_db_drivers && $program_with_db) {
		$main_content .= '
			<div class="db_drivers">
				<label>This program uses a database, that is, if you want the data from this program to be loaded, choose a database below where you want to install it:</label>
				<ul>';
		
		$first_item_checked = count($brokers_db_drivers) != 1;
		
		foreach ($brokers_db_drivers as $bl) {
			$checked = false;
			
			if ($P)
				$checked = $default_db_driver && $default_db_driver == $bl;
			else if (!$first_item_checked && $project_name != $EVC->getCommonProjectName()) {
				$first_item_checked = true;
				$checked = true;
			}
			
			$main_content .= '<li><input type="checkbox" name="db_drivers[]" value="' . $bl . '"' . ($checked ? ' checked' : '') . '/> ' . ucwords($bl) . '</li>';
		}
		
		$main_content .= '</ul>
			</div>';
	}
	
	$main_content .= '
			<div class="layers">
				<label>Please choose the Layers where you wish to install your program:</label>
				<ul>';
	
	if ($ibatis_brokers) {
		$main_content .= '<li>Data Access - Ibatis Layers:
		<ul>';
		
		foreach ($ibatis_brokers as $bl)
			$main_content .= '<li><input type="checkbox" name="layers[ibatis][' . $bl[0] . '][active]" value="1" checked/> ' . ucwords($bl[0]) . '</li>';
		
		$main_content .= '</ul></li>';
	}
	
	if ($hibernate_brokers) {
		$main_content .= '<li>Data Access - Hibernate Layers:
		<ul>';
		
		foreach ($hibernate_brokers as $bl)
			$main_content .= '<li><input type="checkbox" name="layers[hibernate][' . $bl[0] . '][active]" value="1" checked/> ' . ucwords($bl[0]) . '</li>';
		
		$main_content .= '</ul></li>';
	}
	
	if ($business_logic_brokers) {
		$main_content .= '<li>Business Logic Layers:
		<ul>';
		
		foreach ($business_logic_brokers as $bl)
			$main_content .= '<li><input type="checkbox" name="layers[businesslogic][' . $bl[0] . '][active]" value="1" checked/> ' . ucwords($bl[0]) . '</li>';
		
		$main_content .= '</ul></li>';
	}
	
	if ($presentation_brokers) {
		$main_content .= '<li>Presentation Layers:
		<ul>';
		
		foreach ($presentation_brokers as $bl) {
			$projects = $presentation_projects[ $bl[2] ]["projects"];
			$main_content .= '<li>' . ucwords($bl[0]) . ':
			<ul>';
			
			$first_item_checked = count($projects) != 2;
			
			if ($projects)
				foreach ($projects as $project_name => $project_props) {
					$checked = false;
					
					if ($P)
						$checked = $selected_project_id && $selected_project_id == $project_name;
					else if (!$first_item_checked && $project_name != $EVC->getCommonProjectName()) {
						$first_item_checked = true;
						$checked = true;
					}
					
					$main_content .= '<li><input type="checkbox" name="layers[presentation][' . $bl[0] . '][' . $project_name . '][active]" value="1"' . ($checked ? ' checked' : '') . '/> ' . ucwords($project_name) . '</li>';
				}
				
			$main_content .= '</ul>
			</li>';
		}
		
		$main_content .= '</ul></li>';
	}
	
	if ($vendor_brokers) {
		$main_content .= '<li>Vendor Files:
		<ul>';
		
		foreach ($vendor_brokers as $bl)
			$main_content .= '<li><input type="checkbox" name="layers[vendor][' . $bl . '][active]" value="1" checked/> ' . $bl . '</li>';
		
		$main_content .= '</ul></li>';
	}
	
	$main_content .= '
				</ul>
			</div>
			
			<div class="overwrite">
				<input type="checkbox" name="overwrite" value="1" checked/> Please check this box to overwrite the existent files...
			</div>
			
			' . ($program_settings ? '<div class="program_settings"><label>Other Program Settings:</label>' . $program_settings . '</div>' : '') . '
			
			<input type="hidden" name="continue" value="Continue" />
		</form>
	</div>';
}
else {
	$head .= '
	<script>
		var list_programs_with_dbs = ' . (!empty($db_drivers_names) ? "true" : "false") . ';
	</script>';
	
	$main_content .= '
	<div class="step_0">
		<div class="top_bar' . ($popup ? ' in_popup' : '') . '">
			<header>
				<div class="title" title="' . $path . '">' . $title . '</div>
				<ul>
					<li class="info" data-title="Info"><a onClick="$(\'.install_program .step_0\').toggleClass(\'show_info\');"><i class="icon info"></i> Info</a></li>
					<li class="continue" data-title="Continue with Installation"><a onClick="submitForm(this, checkUploadedFiles);"><i class="icon continue"></i> Continue with Installation</a></li>
				</ul>
			</header>
		</div>
		
		<script>
			var get_store_programs_url = "' . $project_url_prefix . "phpframework/admin/get_store_type_content?type=programs" . '";
		</script>
		
		<div class="install_program_step_0_with_tabs">
			<ul>
				' . ($get_store_programs_url ? '<li><a href="#store">Store Programs</a></li>' : '') . '
				<li><a href="#local">Upload Local Program</a></li>
			</ul>
			
			<div id="local" class="file_upload">
				<div class="title">Install a local program from your computer (.zip file)</div>
				
				<form method="post" enctype="multipart/form-data">
					<input type="hidden" name="step" value="1" />
					<input type="hidden" name="continue" value="Continue" />
					
					<input class="upload_file" type="file" name="program_file" />
				</form>
				
				' . ($programs_download_page_url ? '<div class="go_to_programs_download_page">To download programs to your local computer, please click <a href="' . $programs_download_page_url . '" target="download_programs">here</a></div>' : '') . '
			</div>';
	
	if ($get_store_programs_url)
		$main_content .= '
			<div id="store" class="install_store_program">
				<div class="title">Install a program from our store</div>
				<div class="search_program">
					<i class="icon search active"></i>
					<input placeHolder="Search" onKeyUp="searchPrograms(this)" />
					<i class="icon close" onClick="resetSearchPrograms(this)"></i>
				</div>
				<ul>
					<li class="loading">Loading programs from store...</li>
				</ul>
			</div>
			<script>
				$(function () {
					initInstallStoreProgram();
				});
			</script>';
	
	$main_content .= '
		</div>	
		<script>
			$(function () {
				$(".install_program .step_0 .install_program_step_0_with_tabs").tabs();
			});
		</script>
		
		<div class="info">
			<div class="warning">
				Note that in case of have Layers remotely installed, this is, Layers that are not locally installed and are remotely accessable, and if you wish to access this program from these Layers, you must then, install this program individually in that Layers too...
			</div>
		</div>
	</div>';
}

$main_content .= '</div>';
?>
