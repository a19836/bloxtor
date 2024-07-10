<?php
$get_bkp = $_GET;
unset($get_bkp["creation_step"]);
$query_string = http_build_query($get_bkp);

$top_bar_title = "Create new Project";

if (!$creation_step) { //show edit_project_details so the user can insert the name of the project
	$call_edit_project_details = true;
	
	if ($refresh_page_without_creation_step) {
		$call_edit_project_details = false;
		
		$main_content = '<script>
			document.location = \'?' . $query_string . '\';
		</script>';
	}
	else if ($_POST && $status) {
		$call_edit_project_details = false;
		
		//This code was copied and updated from the view/presentation/edit_project_details.php
		$msg = $extra_message ? $extra_message : ""; //only add this message if the there are extra messages, otherwise the system will show a successfully message in creation_step 2
		
		$on_success_js_func_opts = $on_success_js_func ? array(
			"layer_bean_folder_name" => $layer_bean_folder_name,
			"new_filter_by_layout" => $new_filter_by_layout,
			"new_bean_name" => $bean_name,
			"new_bean_file_name" => $bean_file_name,
			"new_project" => $path
		) : null;
		
		//go to next creation step via post
		$project_post_data = array(
			"on_success_js_func" => $on_success_js_func ? $on_success_js_func : "refreshLastNodeParentChilds", //refreshLastNodeParentChilds bc of the admin_menu
			"on_success_js_func_opts" => $on_success_js_func_opts,
			"msg" => $msg
		);
		
		unset($get_bkp["step"]); //just in case
		$get_bkp["bean_name"] = $bean_name; //set bean_name just in case the edit_project_details changed it
		$get_bkp["bean_file_name"] = $bean_file_name; //set bean_file_name just in case the edit_project_details changed it
		$get_bkp["path"] = $path; //set new path because the edit_project_details changed it with new project path
		$get_bkp["filter_by_layout"] = $new_filter_by_layout; //because of the install_program
		$query_string = http_build_query($get_bkp);
		
		$main_content = '<script>
			' . ($msg ? 'alert("' . str_replace('"', '', $msg) . '");' : '') . '
			postToUrl(\'?' . $query_string . '&creation_step=' . ($get_store_programs_url ? 1 : 2) . '\', ' . json_encode($project_post_data) . ');
		</script>';
	}
	
	if ($call_edit_project_details) {
		//call edit_project_details
		include_once $EVC->getViewPath("presentation/edit_project_details");
		
		$project_exists = ($_POST && $is_previous_existent_project) || (!$_POST && $is_existent_project);
		
		$main_content = '<div class="top_bar create_project_top_bar popup_with_iframe_left_popup_close popup_with_iframe_popup_close_button' . ($popup ? ' in_popup' : '') . '">
			<header>
				<div class="title" title="' . $top_bar_title . '">' . $top_bar_title . '</div>
				<ul>
					<li class="continue button" data-title="' . ($project_exists ? "Save project" : "Create project") . '"><a class="active" href="javascript:void(0)" onClick="createProject(this)">Continue</a></li>
					<li class="cancel button" data-title="Cancel"><a class="active" href="javascript:void(0)"' . ($is_existent_project ? ' onClick="cancel()' : '') . '">Cancel</a></li>
				</ul>
			</header>
		</div>' . $main_content;
	}
}
else {
	$project_post_data = array(
		"on_success_js_func" => $on_success_js_func,
		"on_success_js_func_opts" => $on_success_js_func_opts,
		"msg" => $msg
	);
	
	if ($creation_step == 3) { //show list with programs in our store so the user can install them
		//call install program
		include_once $EVC->getViewPath("admin/install_program");
		
		if (!$step) {
			$main_content = '<div class="top_bar create_project_top_bar popup_with_iframe_left_popup_close popup_with_iframe_popup_close_button install_program_step_0' . ($popup ? ' in_popup' : '') . '">
				<header>
					<div class="title" title="' . $top_bar_title . '">' . $top_bar_title . '</div>
					<ul>
						<li class="continue button" data-title="Continue to next step after selecting a program"><a href="javascript:void(0)" onClick="chooseProgram(this, \'?' . $query_string . '&creation_step=3\', project_post_data)">Continue</a></li>
						<li class="back button" data-title="Choose an empty project instead"><a class="active" href="javascript:void(0)" onClick="postToUrl(\'?' . $query_string . '&creation_step=1\', project_post_data)">Back</a></li>
						<li class="cancel button" data-title="Close"><a class="active" href="javascript:void(0)" onClick="cancel()">Close</a></li>
					</ul>
				</header>
			</div>
			<div class="title">Please choose the type of project you wish to create:</div>
			<div class="sub_title"><a href="javascript:void(0)" onClick="toggleLocalUpload(this)">Show Advanced Features</a></div>
			' . $main_content . '
			<script>initInstallPrograms();</script>';
		}
		else {
			$main_content = '
			<div class="top_bar create_project_top_bar popup_with_iframe_left_popup_close popup_with_iframe_popup_close_button' . ($popup ? ' in_popup' : '') . '">
				<header>
					<div class="title" title="' . $top_bar_title . '">' . $top_bar_title . '</div>
					<ul>
					' . ($step < 3 || $next_step_html ? '
						<li class="continue button" data-title="Continue to next installation step"><a class="active" href="javascript:void(0)" onClick="installProgramStep(this, project_post_data)">Continue</a></li>
					' : '
						<li class="continue button" data-title="Continue to the final step"><a class="active" href="javascript:void(0)" onClick="postToUrl(\'?' . $query_string . '&creation_step=2\', project_post_data)">Continue</a></li>
					') . '
						<li class="back button" data-title="Back is not possible anymore"><a href="javascript:void(0)">Back</a></li>
						<li class="cancel button" data-title="Close"><a class="active" href="javascript:void(0)" onClick="cancel()">Close</a></li>
					</ul>
				</header>
			</div>' . $main_content;
			
			if ($is_last_step_successfull)
				$status_message = "";
		}
	}
	else if ($creation_step == 2) { //show success message and call on_success_js_func
		$admin_home_project_page_url = $project_url_prefix . "admin/admin_home_project?filter_by_layout=$filter_by_layout";
		
		$head = '
		<!-- Add Fontawsome Icons CSS -->
		<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

		<!-- Add Icons CSS -->
		<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

		<!-- Add Layout CSS file -->
		<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />';

		$main_content = '
		<div class="top_bar create_project_top_bar' . ($popup ? ' in_popup' : '') . '">
			<header>
				<div class="title" title="' . $top_bar_title . '">' . $top_bar_title . '</div>
				<ul>
					<li class="continue button" data-title="Go to your Project Dashboard"><a class="active" href="javascript:void(0)" onClick="goToProjectDashboard(\'' . $admin_home_project_page_url . '\')">Finish</a></li>
				</ul>
			</header>
		</div>
		<div class="message">
			<div class="title">Your project was created successfully!</div>
			' . ($msg ? '<div class="info">' . $msg . '</div>' : '') . '
			<div class="sentence_1">Please click in the button below to start creating pages for your application</div>
			<div class="sentence_2">Happy development...</div>
			<button onClick="goToProjectDashboard(\'' . $admin_home_project_page_url . '\')">Go to your Project Dashboard</button>
		</div>';
		
		if ($msg)
			$status_message = $msg . ($status_message ? "<br/>$status_message" : "");
	}
	else if ($creation_step == 1) { //show blank project or based in a program
		$head = '
		<!-- Add Fontawsome Icons CSS -->
		<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

		<!-- Add Icons CSS -->
		<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

		<!-- Add Layout CSS file -->
		<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />';

		$main_content = '
		<div class="top_bar create_project_top_bar popup_with_iframe_left_popup_close popup_with_iframe_popup_close_button' . ($popup ? ' in_popup' : '') . '">
			<header>
				<div class="title" title="' . $top_bar_title . '">' . $top_bar_title . '</div>
				<ul>
					<li class="back button" data-title="Back to edit project details"><a class="active" href="javascript:void(0)" onClick="goToUrl(\'?' . $query_string . '&creation_step=0\')">Back</a></li>
					<li class="cancel button" data-title="Close"><a class="active" href="javascript:void(0)" onClick="cancel()">Close</a></li>
				</ul>
			</header>
		</div>
		<div class="title">How do you want to build your project?</div>
		<div class="project_type">
			<div class="title">For citizen-developers</div>
			<div class="description">Create your application from scratch by designing your own logic and data models.</div>
			<button onClick="$(this).parent().addClass(\'selected\');postToUrl(\'?' . $query_string . '&creation_step=2\', project_post_data)">Empty Project</button>
		</div>
		<div class="project_type">
			<div class="title">For no-coders</div>
			<div class="description">Accelerate your development process by starting with a pre-built and customizable program.</div>
			<button onClick="$(this).parent().addClass(\'selected\');postToUrl(\'?' . $query_string . '&creation_step=3\', project_post_data)">Browse Programs</button>
		</div>';
	}
}

$head .= '
<!-- Add Local CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/create_project.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/create_project.js"></script>

<script>
var on_success_js_func_name = "' . $on_success_js_func . '";
var on_success_js_func_opts = ' . json_encode($on_success_js_func_opts) . ';
var popup = ' . ($popup ? "true" : "false") . ';
var project_post_data = ' . json_encode($project_post_data) . ';
var project_exists = ' . ($project_exists ? "true" : "false") . ';
</script>';

$main_content = '
<div class="create_project changing_to_step">
	<div class="creation_step creation_step_' . $creation_step . '">
		' . $main_content . '
	</div>
	
	<div class="loading"></div>
</div>';
?>
