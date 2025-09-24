<?php
//NOTE: IF YOU MAKE	ANY CHANGES IN THIS FILE, PLEASE BE SURE THAT THE create_project.php COVERS THAT CHANGES AND DOESN'T BREAK ITS LOGIC.

include_once get_lib("org.phpframework.util.web.html.HtmlFormHandler");
include_once $EVC->getUtilPath("AdminMenuUIHandler");
include_once $EVC->getUtilPath("WorkFlowPresentationHandler");

$file_path = isset($file_path) ? $file_path : null;
$P = isset($P) ? $P : null;
$project_layout_type_id = isset($project_layout_type_id) ? $project_layout_type_id : null;
$presentation_brokers = isset($presentation_brokers) ? $presentation_brokers : null;

$manage_project_url = $project_url_prefix . "phpframework/presentation/manage_file?bean_name=#bean_name#&bean_file_name=#bean_file_name#&action=#action#&item_type=presentation&extra=#extra#&path=#path#&folder_type=project";

$choose_bean_layer_files_from_file_manager_url = $project_url_prefix . "phpframework/admin/get_sub_files?bean_name=#bean_name#&bean_file_name=#bean_file_name#&path=#path#";
$upload_bean_layer_files_from_file_manager_url = $project_url_prefix . "phpframework/admin/upload_file?bean_name=#bean_name#&bean_file_name=#bean_file_name#&path=#path#";
$get_file_properties_url = $project_url_prefix . "phpframework/admin/get_file_properties?bean_name=#bean_name#&bean_file_name=#bean_file_name#&path=#path#&class_name=#class_name#&type=#type#";
$manage_layout_type_permissions_url = $project_url_prefix . "phpframework/user/manage_layout_type_permissions?layout_type_id=$project_layout_type_id";

$head = AdminMenuUIHandler::getHeader($project_url_prefix, $project_common_url_prefix);
$head .= '
<!-- Add PHP CODE CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/edit_php_code.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/edit_php_code.js"></script>

<!-- Add ADMIN MENU JS -->
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/admin/admin_menu.js"></script>

<!-- Add Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/edit_project_details.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/edit_project_details.js"></script>
';

$head .= '<script>';
$head .= WorkFlowPresentationHandler::getPresentationBrokersHtml($presentation_brokers, $choose_bean_layer_files_from_file_manager_url, $get_file_properties_url, $upload_bean_layer_files_from_file_manager_url);
$head .= '
var bean_name = "' . $bean_name . '";
var bean_file_name = "' . $bean_file_name . '";
var manage_project_url = \'' . $manage_project_url . '\';
var is_popup = ' . ($popup ? "true" : "false") . ';
</script>';

if (empty($is_existent_project) || (!empty($_POST) && empty($status)))
	$head .= '
	<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/db_driver_connection_props.js"></script>
	<script>
		var drivers_encodings = ' . (isset($drivers_encodings) ? json_encode($drivers_encodings) : "null") . ';
		var drivers_extensions = ' . (isset($drivers_extensions) ? json_encode($drivers_extensions) : "null") . ';
		var drivers_ignore_connection_options = ' . (isset($drivers_ignore_connection_options) ? json_encode($drivers_ignore_connection_options) : "null") . ';
		var drivers_ignore_connection_options_by_extension = ' . (isset($drivers_ignore_connection_options_by_extension) ? json_encode($drivers_ignore_connection_options_by_extension) : "null") . ';
	</script>';

$main_content = '';

if (!empty($_POST)) {
	if (empty($status)) { //This should never happen, bc the javascript already takes care of this and it only submits the form if project is successfull created.
		$error_message = (!empty($extra_message) ? $extra_message . "<br/>" : "") . (!empty($error_message) ? $error_message : "There was an error trying to " . (!empty($is_rename_project) ? "rename" : "create") . " project. Please try again...");
	}
	else {
		$status_message = (!empty($extra_message) ? $extra_message . "<br/>" : "") . "Project " . (!empty($is_rename_project) ? "renamed" : (!empty($_POST["is_existent_project"]) ? "saved" : "created")) . " successfully!";
		$on_success_js_func_opts = null;
		
		if ($on_success_js_func) {
			$layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerObjFolderName( $PEVC->getPresentationLayer() ); //get layer_bean_folder_name
			
			$old_filter_by_layout = "$layer_bean_folder_name/" . (isset($_POST["old_project_folder"]) && trim($_POST["old_project_folder"]) ? trim($_POST["old_project_folder"]) . "/" : "") . (isset($_POST["old_name"]) ? trim($_POST["old_name"]) : "");
			$old_filter_by_layout = preg_replace("/[\/]+/", "/", $old_filter_by_layout); //remove duplicates /
			$old_filter_by_layout = preg_replace("/[\/]+$/", "", $old_filter_by_layout); //remove end /
			
			$new_filter_by_layout = "$layer_bean_folder_name/$path"; //$path already has duplicates and end / removed
			
			$on_success_js_func_opts = array(
				"is_rename_project" => isset($is_rename_project) ? $is_rename_project : null,
				"layer_bean_folder_name" => $layer_bean_folder_name,
				"old_filter_by_layout" => $old_filter_by_layout,
				"new_filter_by_layout" => $new_filter_by_layout,
				"new_bean_name" => $bean_name,
				"new_bean_file_name" => $bean_file_name,
				"new_project" => $path
			);
		}
		
		$on_success_js_func = $on_success_js_func ? $on_success_js_func : "refreshLastNodeParentChilds"; //refreshLastNodeParentChilds bc of the admin_menu
		$on_success_js_func_opts = $on_success_js_func_opts ? json_encode($on_success_js_func_opts) : "";
		$on_success_message = str_replace(array("<br/>", "\n"), "\\n", str_replace("'", "\\'", $status_message));
		
		$main_content .= "
		<script>
			" . (in_array($on_success_js_func, array("onSuccessfullAddProject", "onSuccessfullPopupAction")) ? "alert('$status_message');" : "") . "
			
			if (typeof window.parent.$on_success_js_func == 'function')
				window.parent.$on_success_js_func($on_success_js_func_opts);
			else if (typeof window.parent.parent.$on_success_js_func == 'function') //could be inside of the admin_home_project.php which is inside of the admin_advanced.php
				window.parent.parent.$on_success_js_func($on_success_js_func_opts);
		</script>";
	}
}

$main_content .= '
<div class="top_bar' . ($popup ? " in_popup" : "") . '">
	<header>
		<div class="title">' . (!empty($is_existent_project) ? 'Edit' : 'Create') . ' Project</div>
		<ul>
			<li class="save" data-title="Save Project"><a onclick="submitForm(this)"><i class="icon save"></i> Save Project</a>
		</ul>
	</header>
</div>
<div class="edit_project_details' . (isset($layers_projects) && count($layers_projects) == 1 ? ' single_presentation_layer' : '') . (!empty($is_existent_project) ? ' existent_project' : '') . '">';

//prepare choose project template popup
$main_content .= '
	<div id="choose_project_folder_url_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
		<div class="title">Choose a Folder</div>
		<div class="broker" style="display:none;">
			<label>Broker:</label>
			<select onChange="updateLayerUrlFileManager(this)">'; //We left the onChange but it doesn't matter bc this field is hidden and only sets the default bean_name from the GET url

if (isset($presentation_brokers)) {
	$t = count($presentation_brokers);
	for ($i = 0; $i < $t; $i++) {
		$b = $presentation_brokers[$i];
		$b_broker_name = isset($b[0]) ? $b[0] : null;
		$b_bean_file_name = isset($b[1]) ? $b[1] : null;
		$b_bean_name = isset($b[2]) ? $b[2] : null;
		
		$main_content .= '<option bean_file_name="' . $b_bean_file_name . '" bean_name="' . $b_bean_name . '" value="' . $b_broker_name . '"' . ($b_bean_name == $bean_name && $b_bean_file_name == $bean_file_name ? " selected" : "") . '>' . $b_broker_name . '</option>';
	}
}

$main_content .= '
			</select>
		</div>
		<ul class="mytree">
			<li>
				<label>Root</label>
				<ul layer_url="' . $choose_bean_layer_files_from_file_manager_url . '"></ul>
			</li>
		</ul>
		<div class="button">
			<input type="button" value="Update" onClick="MyFancyPopup.settings.updateFunction(this)" />
		</div>
	</div>
	
	<form method="post" enctype="multipart/form-data" onSubmit="return addProject(this);" project_created="' . (!empty($is_existent_project) ? 1 : 0) . '">
		<input type="hidden" name="is_existent_project" value="' . (!empty($is_existent_project) ? 1 : 0) . '" />
		<input type="hidden" name="is_previous_project_creation_with_errors" value="' . (!empty($_POST) && empty($status) ? 1 : 0) . '" />
		
		<div class="left_content">
			' . (!empty($project_image) ? '<img src="' . $project_image . '" alt="No Image" onClick="$(this).parent().children(\'input[type=file]\').trigger(\'click\')" />' : '<div class="no_logo" onClick="$(this).parent().children(\'input[type=file]\').trigger(\'click\')"></div>') . '
			
			<label>Change logo:</label>
			<input type="file" name="image" />
			
			<div class="project_folder advanced_option" title="Create your project inside of an existent or new folder...">
				<label>Assign this project to a folder?</label>
				<input type="hidden" name="old_project_folder" value="' . (isset($old_project_folder) ? $old_project_folder : null) . '" />
				<input name="project_folder" placeHolder="Type folder name" value="' . (isset($project_folder) ? $project_folder : null) . '" autocomplete="new-password" />
				<span class="icon search" onClick="onChooseProjectFolder(this)"></span>
			</div>
		</div>
		<div class="right_content">
			<div class="name" title="Please write your new project\'s folder name">
				<label>Name your project:</label>
				<input type="hidden" name="old_name" value="' . (isset($old_project) ? $old_project : null) . '" />
				<input name="name" placeHolder="Type a name" value="' . (isset($project) ? $project : null) . '" required autocomplete="new-password" />
				
				<div class="auto_normalize">
					<input type="checkbox" checked /> Normalize name automatically
				</div>
			</div>
			<div class="description">
				<label>Description:</label>
				<textarea name="description" placeHolder="Type some description">' . (isset($project_description) ? $project_description : null) . '</textarea>
			</div>';

if (!empty($db_brokers_exist)) {
	if (empty($is_existent_project) || (!empty($_POST) && empty($status))) {
		$main_content .= '
			<div class="project_db_driver advanced_option" title="If you wish this project to access a DB, please activate this option and fill the DB details below...">
				<label>Want to assign a default DB?</label>
				<select name="project_db_driver" onChange="onChangeProjectWithDB(this)">
					<option value="0" title="Allow this project to connect with all the DB defined">-- default --</option>
					<option value="1"' . (isset($project_db_driver) && is_numeric($project_db_driver) && intval($project_db_driver) === 1 ? ' checked' : '') . '>New DB - User Defined</option>
					<option value="" disabled></option>';
	
		if (!empty($db_drivers_names)) {
			$main_content .= '<optgroup label="Existent DBs">';
			
			foreach ($db_drivers_names as $db_driver_name)
				$main_content .= '<option value="' . $db_driver_name . '">' . $db_driver_name . '</option>';
			
			$main_content .= '</optgroup>';
		}
	
		$main_content .= '
				</select>
			</div>';
	}
	else {
		$main_content .= '
			<div class="project_db_driver advanced_option" title="If you wish to manage the DBs that this project has access to, please click in the link below...">
				<label>Do you wish to assign different DBs?</label>
				<a href="javascript:void(0);" onClick="goToManageLayoutTypePermissions(this)" url="' . $manage_layout_type_permissions_url . '">Manage this project DBs</a>
			</div>';
		
		if (!empty($db_drivers_names) && !empty($default_db_driver))
			$main_content .= '<div class="project_default_db_driver advanced_option">The default DB driver defined is "<span>' . $default_db_driver . '</span>"</div>';
	}
}

if (empty($db_brokers_exist))
	$main_content .= '<div class="no_db_drivers advanced_option">Note that there are no DBs connected to this project, which means you can only use it to build static pages.</div>';
else if (empty($db_drivers_names))
	$main_content .= '<div class="no_db_drivers advanced_option">Note that there are no DBs connected to this project. Please assign a New DB, otherwise you can only use this project to build static pages.</div>';

$main_content .= '
		</div>';

if (!empty($db_brokers_exist) && !empty($db_drivers_names) && !empty($is_existent_project) && empty($default_db_driver))
	$main_content .= '<div class="no_project_default_db_driver advanced_option">This project doesn\'t have any default database driver defined, which means the system will use the first connected database, this is, the database driver "<span>' . (isset($db_drivers_names[0]) ? $db_drivers_names[0] : null) . '</span>"</div>';

if (empty($is_existent_project) || (!empty($_POST) && empty($status))) {
	$form_elements_settings = array(
		0 => array(
			"field" => array(
				"class" => "form_field setup_input form_field_db db_type",
				"label" => array(
					"value" => "DataBase Type: ",
				),
				"input" => array(
					"type" => "select",
					"name" => "db_details[type]",
					"value" => "#type#",
					"options" => isset($available_db_types) ? $available_db_types : null, 
					"extra_attributes" => array(
						array("name" => "onChange", "value" => "onChangeDBType(this)")
					),
				)
			)
		),
		1 => array(
			"field" => array(
				"class" => "form_field setup_input form_field_db db_extension",
				"label" => array(
					"value" => "Connection Type: ",
				),
				"input" => array(
					"type" => "select",
					"name" => "db_details[extension]",
					"value" => "#extension#",
					"options" => isset($available_extensions_options) ? $available_extensions_options : null,
					"extra_attributes" => array(
						array("name" => "onChange", "value" => "onChangeDBExtension(this)")
					),
				)
			)
		),
		2 => array(
			"field" => array(
				"class" => "form_field setup_input form_field_db db_host",
				"label" => array(
					"value" => "Host: ",
				),
				"input" => array(
					"type" => "text",
					"name" => "db_details[host]",
					"value" => "#host#",
				)
			)
		),
		3 => array(
			"field" => array(
				"class" => "form_field setup_input form_field_db db_name",
				"label" => array(
					"value" => "DataBase name: ",
				),
				"input" => array(
					"type" => "text",
					"name" => "db_details[db_name]",
					"value" => "#db_name#",
				)
			)
		),
		4 => array(
			"field" => array(
				"class" => "form_field setup_input form_field_db db_username",
				"label" => array(
					"value" => "Username: ",
				),
				"input" => array(
					"type" => "text",
					"name" => "db_details[username]",
					"value" => "#username#",
					"extra_attributes" => array(
						array("name" => "autocomplete", "value" => "new-password")
					),
				)
			)
		),
		5 => array(
			"field" => array(
				"class" => "form_field setup_input form_field_db db_password",
				"label" => array(
					"value" => "Password: ",
				),
				"input" => array(
					"type" => "password",
					"name" => "db_details[password]",
					"value" => "#password#",
					"next_html" => '<span class="icon switch toggle_password" onclick="toggleDBPasswordField(this)"></span>' . (!empty($db_settings_variables["password"]) ? '<span>...with the global value: "***"</span>' : ''),
					"extra_attributes" => array(
						array("name" => "autocomplete", "value" => "new-password")
					),
				)
			)
		),
		6 => array(
			"field" => array(
				"class" => "form_field form_field_db show_advanced_db_options",
				"input" => array(
					"type" => "label",
					"value" => '<a href="javascript:void(0);" onClick="toggleDBAdvancedOptions(this)">Show Advanced DB Options</a>',
				)
			)
		),
		7 => array(
			"field" => array(
				"class" => "form_field setup_input form_field_db form_field_db_advanced db_port",
				"label" => array(
					"value" => "Port: ",
				),
				"input" => array(
					"type" => "text",
					"name" => "db_details[port]",
					"value" => "#port#",
				)
			)
		),
		8 => array(
			"field" => array(
				"class" => "form_field setup_input form_field_db form_field_db_advanced db_persistent",
				"label" => array(
					"value" => "Persistent: ",
				),
				"input" => array(
					"type" => "checkbox",
					"name" => "db_details[persistent]",
					"value" => "#persistent#",
					"extra_attributes" => array(
						"checked" => !empty($_POST) && empty($_POST["db_details"]["persistent"]) ? "" : "checked"
					)
				)
			)
		),
		9 => array(
			"field" => array(
				"class" => "form_field setup_input form_field_db form_field_db_advanced db_new_link",
				"label" => array(
					"value" => "New Link: ",
				),
				"input" => array(
					"type" => "checkbox",
					"name" => "db_details[new_link]",
					"value" => "#new_link#",
				)
			)
		),
		10 => array(
			"field" => array(
				"class" => "form_field setup_input form_field_db form_field_db_advanced db_reconnect",
				"label" => array(
					"value" => "Reconnect: ",
				),
				"input" => array(
					"type" => "checkbox",
					"name" => "db_details[reconnect]",
					"value" => "#reconnect#",
					"title" => "Automatically reconnect if connection becomes stale."
				)
			)
		),
		11 => array(
			"field" => array(
				"class" => "form_field setup_input form_field_db form_field_db_advanced db_encoding",
				"label" => array(
					"value" => "Encoding: ",
				),
				"input" => array(
					"type" => "select",
					"name" => "db_details[encoding]",
					"value" => "#encoding#",
					"options" => isset($available_encodings_options) ? $available_encodings_options : null
				)
			)
		),
		12 => array(
			"field" => array(
				"class" => "form_field setup_input form_field_db form_field_db_advanced db_schema",
				"label" => array(
					"value" => "Schema: ",
				),
				"input" => array(
					"type" => "text",
					"name" => "db_details[schema]",
					"value" => "#schema#",
				)
			)
		),
		13 => array(
			"field" => array(
				"class" => "form_field setup_input form_field_db form_field_db_advanced db_odbc_data_source",
				"label" => array(
					"value" => "ODBC Data Source: ",
				),
				"input" => array(
					"type" => "text",
					"name" => "db_details[odbc_data_source]",
					"value" => "#odbc_data_source#",
					"title" => "A Data Source Name (DSN) is the logical name that is used by Open Database Connectivity (ODBC) to refer to the driver and other information that is required to access data from a data source. Data sources are usually defined in /etc/odbc.ini",
				)
			)
		),
		14 => array(
			"field" => array(
				"class" => "form_field setup_input form_field_db form_field_db_advanced db_odbc_driver",
				"label" => array(
					"value" => "ODBC Driver: ",
				),
				"input" => array(
					"type" => "text",
					"name" => "db_details[odbc_driver]",
					"value" => "#odbc_driver#",
					"title" => "Is the file path of the installed driver that connects to a data-base from ODBC protocol. Or the name of an ODBC instance that was defined in /etc/odbcinst.ini",
				)
			)
		),
		15 => array(
			"field" => array(
				"class" => "form_field setup_input form_field_db form_field_db_advanced db_extra_dsn",
				"label" => array(
					"value" => "Extra DSN: ",
				),
				"input" => array(
					"type" => "text",
					"name" => "db_details[extra_dsn]",
					"value" => "#extra_dsn#",
					"title" => "Other DSN attributes. Each attribute must be splitted by comma.",
				)
			)
		),
		16 => array(
			"field" => array(
				"class" => "form_field setup_input form_field_db form_field_db_advanced db_extra_settings",
				"label" => array(
					"value" => "Extra Settings: ",
				),
				"input" => array(
					"type" => "text",
					"name" => "db_details[extra_settings]",
					"value" => "#extra_settings#",
					"title" => "Other settings attributes. Each setting must be splitted by & as a url query string.",
				)
			)
		)
	);
	
	$HtmlFormHandler = new HtmlFormHandler();
	$main_content .= '
		<div class="db_details advanced_option" title="DB Details to assign to this project"' . (isset($project_db_driver) && is_numeric($project_db_driver) && intval($project_db_driver) === 1 ? '' : ' style="display:none;"') . '>
			<div class="form_fields">
				' . $HtmlFormHandler->createElements($form_elements_settings, isset($db_details) ? $db_details : null) . '
			</div>
		</div>';
}

$main_content .= '
		<div class="toggle_advanced_options" onClick="toggleAdvancedOptions(this)">Show Advanced Mode</div>
		<div class="buttons">
			<input type="submit" name="save" value="' . (!empty($is_existent_project) ? "Save" : "Add") . ' Project" />
		</div>
	</form>
</div>';
?>
