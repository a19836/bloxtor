<?php
include $EVC->getUtilPath("WorkFlowUIHandler");
include $EVC->getUtilPath("BreadCrumbsUIHandler");

$obj_data = isset($obj_data) ? $obj_data : null;
$db_drivers = isset($db_drivers) ? $db_drivers : null;
$selected_db_broker = isset($selected_db_broker) ? $selected_db_broker : null;
$selected_db_driver = isset($selected_db_driver) ? $selected_db_driver : null;
$selected_type = isset($selected_type) ? $selected_type : null;
$selected_table = isset($selected_table) ? $selected_table : null;
$selected_tables_name = isset($selected_tables_name) ? $selected_tables_name : null;
$selected_table_attrs = isset($selected_table_attrs) ? $selected_table_attrs : null;
$map_php_types = isset($map_php_types) ? $map_php_types : null;
$map_db_types = isset($map_db_types) ? $map_db_types : null;
$item_type = isset($item_type) ? $item_type : null;
$is_hbn_obj_equal_to_file_name = isset($is_hbn_obj_equal_to_file_name) ? $is_hbn_obj_equal_to_file_name : null;
$file_path = isset($file_path) ? $file_path : null;
$obj = isset($obj) ? $obj : null;
$WorkFlowTaskHandler = isset($WorkFlowTaskHandler) ? $WorkFlowTaskHandler : null;

$filter_by_layout_url_query = LayoutTypeProjectUIHandler::getFilterByLayoutURLQuery($filter_by_layout);
$is_obj_valid = $obj_data || !$hbn_obj_id;

$head = "";
$main_content = "";

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

<!-- Add Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/dataaccess/edit_hbn_obj.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/dataaccess/edit_hbn_obj.js"></script>';

if ($is_obj_valid) {
	$head .= $WorkFlowQueryHandler->getHeader();
	$head .= $WorkFlowQueryHandler->getDataAccessJavascript($bean_name, $bean_file_name, $path, $item_type, $hbn_obj_id, $get_layer_sub_files_url);
	$head .= '<script>
	var save_data_access_object_url = \'' . $project_url_prefix . 'phpframework/dataaccess/save_hbn_obj?bean_name=' . $bean_name . '&bean_file_name=' . $bean_file_name . '&path=' . $path . '&item_type=' . $item_type . '&obj=' . $hbn_obj_id . '\';
	var remove_data_access_object_url = \'' . $project_url_prefix . 'phpframework/dataaccess/remove_hbn_obj?bean_name=' . $bean_name . '&bean_file_name=' . $bean_file_name . '&path=' . $path . '&item_type=' . $item_type . '&obj=#obj_id#\';
	var old_obj_id = \'' . $hbn_obj_id . '\';

	var new_id_html = \'' . str_replace("'", "\\'", str_replace("\n", "", getIdHTML())) .'\';
	var auto_increment_db_attributes_types = ' . json_encode(DB::getAllColumnAutoIncrementTypes()) . ';
	</script>';

	//PREPARING GLOBAL WORKFLOW
	$main_content = $WorkFlowQueryHandler->getGlobalTaskFlowChar();

	//PREPARING MAIN CONTENT FOR HIBERNATE OBJECT
	$main_content .= '<div class="edit_hbn_obj">
		<div class="top_bar' . ($popup ? " in_popup" : "") . '">
			<header>
				<div class="title" title="' . $path . '">
					' . ($hbn_obj_id ? "Edit" : "Add") . ' Hibernate Object <span class="query_name"></span> in ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($is_hbn_obj_equal_to_file_name ? dirname($file_path) : $file_path, $obj, $is_hbn_obj_equal_to_file_name) . '
				</div>
				<ul>
					<li class="save" data-title="Save Query"><a onClick="saveHibernateObject()"><i class="icon save"></i> Save</a></li>
					<li class="sub_menu" onClick="openSubmenu(this)">
						<i class="icon sub_menu"></i>
						<ul>
							<li class="update_automatically" title="Update Automatically"><a onClick="createHibernateObjectAutomatically(this)"><i class="icon update_automatically"></i> Create Automatically</a></li>
							<li class="separator"></li>
							<li class="toggle_advanced_settings" title="Toggle Advanced Settings"><a onClick="toggleHbnObjAdvancedSettings(this)"><i class="icon toggle_ids"></i> <span>Show Advanced Settings</span> <input type="checkbox"/></a></li>
							<li class="separator"></li>
							<li class="full_screen" title="Maximize/Minimize Editor Screen"><a onClick="toggleFullScreen(this)"><i class="icon full_screen"></i> Maximize Editor Screen</a></li>
							<li class="separator"></li>
							<li class="save" title="Save Query"><a onClick="saveHibernateObject()"><i class="icon save"></i> Save</a></li>
						</ul>
					</li>
				</ul>
			</header>
		</div>';

	$name = WorkFlowDataAccessHandler::getNodeValue($obj_data, "name");
	$table = WorkFlowDataAccessHandler::getNodeValue($obj_data, "table");//I already go this on the entity
	$extends = WorkFlowDataAccessHandler::getNodeValue($obj_data, "extends");
	
	//PREPARING POPUPS
	//	PREPARING CHOOSE TABLE OR ATTRIBUTE
	$main_content .= $WorkFlowQueryHandler->getChooseQueryTableOrAttributeHtml("choose_db_table_or_attribute");
	
	//	PREPARING CHOOSE INCLUDE FROM FILE MANAGER
	$main_content .= $WorkFlowQueryHandler->getChooseIncludeFromFileManagerHtml($get_layer_sub_files_url, "choose_include_from_file_manager");
	
	//	PREPARING CHOOSE OBJ TYPE FROM FILE MANAGER
	$main_content .= $WorkFlowQueryHandler->getChooseDAOObjectFromFileManagerHtml("choose_dao_object_from_file_manager");
	
	//	PREPARING CHOOSE RESULT/PARAMETER MAP ID
	$main_content .= $WorkFlowQueryHandler->getChooseAvailableMapIdHtml("choose_map_id");
	
	//PREPARING MAIN ATTRIBUTES
	$main_content .= '
<div class="data_access_obj with_top_bar_section">
	<div class="name">
		<label>Name: </label>
		<input type="text" name="name" value="' . $name . '" placeHolder="Obj Name" onFocus="disableTemporaryAutoSaveOnInputFocus(this)" onBlur="undoDisableTemporaryAutoSaveOnInputBlur(this)" />
	</div>
	<div class="table">
		<label>Table: </label>
		<input type="text" name="table" value="' . $table . '" />
		<span class="icon search" onClick="getHbnObjTableFromDB(this)" title="Get table from DB">Search</span>
	</div>
	
	<div class="extends">
		<label>Extends: </label>
		<select name="extends">
			<option></option>';
	
	if (!empty($hbn_class_objs)) {
		$t = count($hbn_class_objs);
		for ($i = 0; $i < $t; $i++) 
			$main_content .= '<option ' . ($hbn_class_objs[$i] == $extends ? 'selected' : '') . '>' . $hbn_class_objs[$i] . '</option>';
		
		if ($extends && !in_array($extends, $hbn_class_objs)) 
			$main_content .= '<option selected>' . $extends . '</option>';
	}
	
	$main_content .= '
		</select>
		<span class="icon search" onClick="getExtendedClassFromFileManager(this)" title="Get from File Manager">Search</span>
	</div>';
	
	//PREPARING IDS
	$main_content .= '
	<div class="ids">
		<label>Primary Keys Settings:</label>
		<span class="icon add" onClick="addNewId(this)">Add</span>
		<span class="icon update_automatically" onClick="createHibernateObjectIdsAutomatically(this)" title="Create Ids Automatically">Create Ids Automatically</span>
		<div class="fields">';

	$ids = array();
	if (!empty($obj_data["childs"]["id"])) {
		$t = count($obj_data["childs"]["id"]);
		for ($i = 0; $i < $t; $i++) {
			$attr_name = isset($obj_data["childs"]["id"][$i]["@"]["column"]) ? $obj_data["childs"]["id"][$i]["@"]["column"] : null;
			$generator = WorkFlowDataAccessHandler::getNodeValue(isset($obj_data["childs"]["id"][$i]) ? $obj_data["childs"]["id"][$i] : null, "generator", "type");
			
			$main_content .= getIdHTML($attr_name, $generator);
		}
	}
	$main_content .= '
		</div>
	</div>';

	//OPEN TABS
	$main_content .= '
	<div id="tabs" class="advanced_settings">
		 <ul class="tabs tabs_transparent">
			<li><a href="#tabs-2">Parameter Map</a></li>
			<li><a href="#tabs-3">Result Map</a></li>
			<li><a id="relationship_tab" href="#tabs-4">Relationships</a></li>
			<li><a id="query_tab" href="#tabs-5" onClick="initQueriesTab(this)">Queries</a></li>
			<li><a href="#tabs-1">Includes</a></li>
		</ul>
	';
	
	//PREPARING INCLUDES
	$imports = isset($obj_data["childs"]["import"]) ? $obj_data["childs"]["import"] : null;
	$main_content .= '<div id="tabs-1">' . $WorkFlowQueryHandler->getInludeHTMLBlock($imports) . '</div>';
	
	//PREPARING PARAMETER MAP
	$parameter_type = isset($obj_data["childs"]["parameter_map"]) ? "map" : "class";
	$parameter_map = isset($obj_data["childs"]["parameter_map"][0]) ? $obj_data["childs"]["parameter_map"][0] : null;
	$parameter_class = WorkFlowDataAccessHandler::getNodeValue($obj_data, "parameter_class");
	
	$main_content .= '
		<div id="tabs-2">
			<div class="parameters map">
				<div class="description">
					The purpose of a "Parameters Map/Class" is to convert and validate an input data object. This is:<br/>
					- let\'s say that a specific method receives an argument, which is an object with a "name", "age" and "country" attributes. Something like: {"name" => "...", "age" => "...", "country" => "..."}. <br/>
					- but the real input object passed to this method only contains the attributes "n", "a" and "c". Something like: {"n" => "David", "a" => "35", "c" => "Portugal"}. <br/>
					<br/>
					So we can create a "Parameters Map/Class" to convert this input object to the right one, transforming the attribute "n" to "name", "a" to "age" and "c" to "country". This is, to something like: {"name" => "David", "age" => "35", "country" => "Portugal"}<br/>
					Additionally we can refer that the "age" attribute is a numeric field, and the system will check and convert the correspondent value to that type.
				</div>
				<div class="type">
					<label>Parameters type:</label>
					<select name="parameter_type" onChange="onChangeParameterType(this)">
						<option value="class" ' . ($parameter_type == "class" ? 'selected' : '') . '>Class</option>
						<option value="map" ' . ($parameter_type == "map" ? 'selected' : '') . '>Map</option>
					</select>
				</div>
				' . getParameterClassHTML($parameter_type, $parameter_class) . '
				' . $WorkFlowQueryHandler->getParameterMapHTML($parameter_type, $parameter_map, $map_php_types, $map_db_types) . '
			</div>
		</div>';
	
	//PREPARING RESULT MAP
	$result_type = isset($obj_data["childs"]["result_map"]) ? "map" : "class";
	$result_map = isset($obj_data["childs"]["result_map"][0]) ? $obj_data["childs"]["result_map"][0] : null;
	$result_class = WorkFlowDataAccessHandler::getNodeValue($obj_data, "result_class");
	
	$main_content .= '
		<div id="tabs-3">
			<div class="results map">
				<div class="description">
					The purpose of a "Result Map/Class" is to convert and validate an output data object. This is:<br/>
					- let\'s say that a specific method returns a result, which is an object with a "name", "age" and "country" attributes. Something like: {"name" => "David", "age" => "35", "country" => "Portugal"}. <br/>
					- but the real output object that we would like to return should contain the attributes "n", "a" and "c". Something like: {"n" => "...", "a" => "...", "c" => "..."}. <br/>
					<br/>
					So we can create a "Result Map/Class" to convert this result to the right output object, transforming the attribute "name" to "n", "age" to "a" and "country" to "c". This is, to something like: {"n" => "David", "a" => "35", "c" => "Portugal"}<br/>
					Additionally we can refer that the "a" attribute is a numeric field, and the system will check and convert the correspondent value to that type.
				</div>
				<div class="type">
					<label>Result type:</label>
					<select name="result_type" onChange="onChangeResultType(this)">
						<option value="class" ' . ($result_type == "class" ? 'selected' : '') . '>Class</option>
						<option value="map" ' . ($result_type == "map" ? 'selected' : '') . '>Map</option>
					</select>
				</div>
				' . getResultClassHTML($result_type, $result_class) . '
				' . $WorkFlowQueryHandler->getResultMapHTML($result_type, $result_map, $map_php_types, $map_db_types) . '
			</div>
		</div>';
	
	//PREPARING RELATIONSHIPS
	$relationships = isset($obj_data["childs"]["relationships"][0]["childs"]) ? $obj_data["childs"]["relationships"][0]["childs"] : null;
	$relationships_settings = array(
		"init_ui" => true,
		"init_workflow" => false,
	);
	
	$main_content .= '
		<div id="tabs-4" class="hbn_obj_relationships">
			' . $WorkFlowQueryHandler->getDataAccessObjHtml($relationships, true, $relationships_settings) . '
		</div>';
		
	//PREPARING QUERIES
	$queries = isset($obj_data["childs"]["queries"][0]["childs"]) ? $obj_data["childs"]["queries"][0]["childs"] : null;
	$queries_settings = array(
		"init_ui" => true,
		"init_workflow" => false,
	);
	
	$main_content .= '
		<div id="tabs-5" class="hbn_obj_queries">
			' . $WorkFlowQueryHandler->getDataAccessObjHtml($queries, false, $queries_settings) . '
		</div>';
		
	//CLOSE TABS AND MAIN DIV
	$main_content .= '
	</div>
</div>';
}
else {
	$title = ($hbn_obj_id ? "Edit" : "Add") . " Hibernate Object";
	
	$main_content = '
		<div class="top_bar' . ($popup ? " in_popup" : "") . '">
			<header>
				<div class="title" title="' . $path . '">' . $title . '</div>
			</header>
		</div>';
	
	$main_content .= '<div class="error">Error: The system couldn\'t detect the selected object. Please refresh and try again...</div>';
}

//$head = $CssAndJSFilesOptimizer->prepareHtmlWithOptimizedCssAndJSFiles($head, false);


function getParameterClassHTML($parameter_type, $parameter_class, $allow_remove = false) {
	return '
	<div class="class" ' . ($parameter_type == "class" ? 'style="display:block;"' : 'style="display:none;"') . '>
		<label>Parameter Class:</label>
		<input type="text" name="parameter_class" value="' . $parameter_class . '" />
		<span class="icon search" onClick="getParameterClassFromFileManager(this)" title="Get class from File Manager">Search</span>
		<span class="icon delete" onClick="$(this).parent().remove();" ' . ($allow_remove ? '' : 'style="display:none;"') . '>Remove</span>
	</div>';
}

function getResultClassHTML($result_type, $result_class, $allow_remove = false) {
	return '
	<div class="class" ' . ($result_type == "class" ? 'style="display:block;"' : 'style="display:none;"') . '>
		<label>Result Class:</label>
		<input type="text" name="result_class" value="' . $result_class . '" />
		<span class="icon search" onClick="getResultClassFromFileManager(this)" title="Get class from File Manager">Search</span>
		<span class="icon delete" onClick="$(this).parent().remove();" ' . ($allow_remove ? '' : 'style="display:none;"') . '>Remove</span>
	</div>';
}

function getIdHTML($attr_name = false, $generator = false) {
	return '<div class="id">
		<label class="attr_name">Attribute Name:</label>
		<input class="attr_name" type="text" name="id_columns[]" value="' . $attr_name . '" />
		<span class="icon search" onClick="getTableAttributeFromDB(this, \'input.attr_name\')" title="Get attribute from DB">Search</span>
		<label class="generator">Generator Type:</label>
		<select class="generator">
			<option value="">-- Default --</option>
			<option ' . (strtolower($generator) == "increment" ? 'selected' : '') . '>increment</option>
		</select>
		<span class="icon delete" onClick="$(this).parent().remove();">Remove</span>
	</div>';
}
?>
