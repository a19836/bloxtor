<?
include_once get_lib("org.phpframework.db.DB");
include $EVC->getUtilPath("WorkFlowUIHandler");

$with_advanced_options = isset($with_advanced_options) ? $with_advanced_options : null;
$table_exists = isset($table_exists) ? $table_exists : null;
$e = isset($e) ? $e : null;
$action = isset($action) ? $action : null;
$data = isset($data) ? $data : null;

//get task table workflow settings
$WorkFlowUIHandler = new WorkFlowUIHandler($WorkFlowTaskHandler, $project_url_prefix, $project_common_url_prefix, $external_libs_url_prefix, $user_global_variables_file_path, $webroot_cache_folder_path, $webroot_cache_folder_url);
$tasks_settings = $WorkFlowTaskHandler->getLoadedTasksSettings();

$task_contents = array();

foreach ($tasks_settings as $group_id => $group_tasks)
	foreach ($group_tasks as $task_type => $task_settings)
		if (is_array($task_settings))
			$task_contents = isset($task_settings["task_properties_html"]) ? $task_settings["task_properties_html"] : null;

//prepare DBTableTaskPropertyObj properties 
$charsets = $obj ? $obj->listTableCharsets() : array();
$collations = $obj ? $obj->listTableCollations() : array();
$storage_engines = $obj ? $obj->listStorageEngines() : array();
$column_charsets = $obj ? $obj->listColumnCharsets() : array();
$column_collations = $obj ? $obj->listColumnCollations() : array();
$column_column_types = $obj ? $obj->getDBColumnTypes() : DB::getAllColumnTypesByType();
$column_column_simple_types = $obj ? $obj->getDBColumnSimpleTypes() : DB::getAllColumnSimpleTypesByType();
$column_numeric_types = $obj ? $obj->getDBColumnNumericTypes() : DB::getAllSharedColumnNumericTypes();
$column_mandatory_length_types = $obj ? $obj->getDBColumnMandatoryLengthTypes() : DB::getAllSharedColumnMandatoryLengthTypes();
$column_types_ignored_props = $obj ? $obj->getDBColumnTypesIgnoredProps() : DB::getAllSharedColumnTypesIgnoredProps();
$column_types_hidden_props = $obj ? $obj->getDBColumnTypesHiddenProps() : DB::getAllSharedColumnTypesHiddenProps();
$allow_modify_table_encoding = $obj ? $obj->allowModifyTableEncoding() : false;
$allow_modify_table_storage_engine = $obj ? $obj->allowModifyTableStorageEngine() : false;

$charsets = is_array($charsets) ? $charsets : array();
$collations = is_array($collations) ? $collations : array();
$storage_engines = is_array($storage_engines) ? $storage_engines : array();
$column_charsets = is_array($column_charsets) ? $column_charsets : array();
$column_collations = is_array($column_collations) ? $column_collations : array();
$column_column_types = is_array($column_column_types) ? $column_column_types : array();
$column_column_simple_types = is_array($column_column_simple_types) ? $column_column_simple_types : array();
$column_numeric_types = is_array($column_numeric_types) ? $column_numeric_types : array();
$column_mandatory_length_types = is_array($column_mandatory_length_types) ? $column_mandatory_length_types : array();
$column_types_ignored_props = is_array($column_types_ignored_props) ? $column_types_ignored_props : array();

$manage_records_url = $project_url_prefix . "db/manage_records?layer_bean_folder_name=$layer_bean_folder_name&bean_name=$bean_name&bean_file_name=$bean_file_name&table=$table";
$execute_sql_url = $project_url_prefix . "db/execute_sql?layer_bean_folder_name=$layer_bean_folder_name&bean_name=$bean_name&bean_file_name=$bean_file_name&table=$table";

$head = '
<!-- Add ACE Editor JS files -->
<script src="' . $project_common_url_prefix . 'vendor/acecodeeditor/src-min-noconflict/ace.js"></script>
<script src="' . $project_common_url_prefix . 'vendor/acecodeeditor/src-min-noconflict/ext-language_tools.js"></script>
';
$head .= $WorkFlowUIHandler->getHeader();
$head .= '
<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/layout.js"></script>

<!-- Add Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/db/edit_table.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/db/edit_table.js"></script>

<script>
DBTableTaskPropertyObj.column_types = ' . json_encode($column_column_types) . ';
DBTableTaskPropertyObj.column_simple_types = ' . json_encode($column_column_simple_types) . ';
DBTableTaskPropertyObj.column_numeric_types = ' . json_encode($column_numeric_types) . ';
DBTableTaskPropertyObj.column_mandatory_length_types = ' . json_encode($column_mandatory_length_types) . ';
DBTableTaskPropertyObj.column_types_ignored_props = ' . json_encode($column_types_ignored_props) . ';
DBTableTaskPropertyObj.column_types_hidden_props = ' . json_encode($column_types_hidden_props) . ';
DBTableTaskPropertyObj.table_charsets = ' . json_encode($charsets) . ';
DBTableTaskPropertyObj.table_collations = ' . json_encode($collations) . ';
DBTableTaskPropertyObj.table_storage_engines = ' . json_encode($storage_engines) . ';
DBTableTaskPropertyObj.column_charsets = ' . json_encode($column_charsets) . ';
DBTableTaskPropertyObj.column_collations = ' . json_encode($column_collations) . ';
DBTableTaskPropertyObj.allow_modify_table_encoding = ' . ($allow_modify_table_encoding ? "true" : "false") . ';
DBTableTaskPropertyObj.allow_modify_table_storage_engine = ' . ($allow_modify_table_storage_engine ? "true" : "false") . ';

DBTableTaskPropertyObj.on_update_simple_attributes_html_with_table_attributes_callback = onUpdateSimpleAttributesHtmlWithTableAttributes;
DBTableTaskPropertyObj.on_update_table_attributes_html_with_simple_attributes_callback = onUpdateTableAttributesHtmlWithSimpleAttributes;

var task_property_values = ' . json_encode($data) . ';

var step = ' . (!empty($step) ? $step : 0) . ';
var with_advanced_options = ' . ($with_advanced_options ? $with_advanced_options : 0) . ';
</script>';

$show_sub_menu = empty($table && !$table_exists) && empty($action == "delete" && $e === true);
$main_content = '
<div class="top_bar' . ($with_advanced_options ? " with_advanced_options" : "") . ($popup ? " in_popup" : "") . '">
	<header>
		<div class="title">' . ($table ? 'Edit Table \'' . $table . '\'' : 'Add Table') . ' in DB: \'' . $bean_name . '\'</div>
		' . ($show_sub_menu ? '<ul>
			<li class="sub_menu" onclick="openSubmenu(this)">
				<i class="icon sub_menu"></i>
				<ul>
					<li class="toggle_advanced_options' . ($with_advanced_options ? " active" : "") . '" title="Toggle Advanced Features"><a onClick="toggleAdvancedOptions()"><i class="icon toggle_advanced_options"></i> <span>' . ($with_advanced_options ? "Hide" : "Show") . ' Advanced Features</span> <input type="checkbox"' . ($with_advanced_options ? " checked" : "") . '/></a></li>
					' . ($table_exists ? '<li class="separator"></li>
					<li class="manage_records" title="Manage Records"><a onClick="goToTablePopup(\'' . $manage_records_url . '\', event)"><i class="icon edit"></i> Manage Records</a></li>
					<li class="execute_sql" title="Execute SQL"><a onClick="goToTablePopup(\'' . $execute_sql_url . '\', event)"><i class="icon edit"></i> Execute SQL</a></li>' : '') . '
				</ul>
			</li>
		</ul>' : '') . '
	</header>
</div>

<div class="edit_table' . ($with_advanced_options ? " with_advanced_options" : "") . '">';

if ($table && !$table_exists)
	$main_content .= '<div class="error">Table does not exists!</div>';
else if ($action == "delete" && $e === true) 
	$main_content .= '<div class="message">Table deleted successfully!</div>';
else if ($obj) {
	//$allow_sort = $obj->allowTableAttributeSorting() && (!$table || empty($data["table_attr_names"])); //if no attributes, then allow sort attributes
	$allow_sort = $obj->allowTableAttributeSorting();
	
	$main_content .= '
	<h3 class="table_settings_header">Table Settings <a class="icon refresh" href="javascript:void(0);" onClick="document.location=document.location+\'\';" title="Refresh">Refresh</a></h3>
	<div class="table_settings' . ($allow_sort ? " allow_sort" : "") . '">
		<div class="selected_task_properties">
		' . $task_contents . '
		</div>
		
		<form method="post">
			<input type="hidden" name="step" value="1"/>
			<input type="hidden" name="with_advanced_options" value="' . ($with_advanced_options ? 1 : 0) . '"/>
			<textarea class="hidden" name="data">' . json_encode($data) . '</textarea>
			
			<div class="save_button">
				' . ($table ? '<input class="delete" type="submit" name="delete" value="Delete" onClick="return onDeleteButton(this);" />
				<input type="submit" name="update" value="Update" onClick="return onSaveButton(this);" />' : '<input type="submit" name="add" value="Add" onClick="return onSaveButton(this);" />') . '
			</div>
		</form>
	</div>
	
	<h3 class="table_sql_statements_header">Table SQLs</h3>
	<div class="table_sql_statements">
		<form method="post">
			<input type="hidden" name="step" value="2"/>
			<input type="hidden" name="with_advanced_options" value="' . ($with_advanced_options ? 1 : 0) . '"/>
			<input type="hidden" name="action" value="' . $action . '"/>
			<textarea class="hidden" name="data">' . json_encode($data) . '</textarea>
			';
		
	if (!empty($sql_statements)) {
		foreach ($sql_statements as $idx => $sql)
			$main_content .= '<div class="sql_statement">
				<label>' . (isset($sql_statements_labels[$idx]) ? $sql_statements_labels[$idx] : "") . '</label>
				<textarea class="hidden" name="sql_statements[]">' . htmlspecialchars($sql, ENT_NOQUOTES) . '</textarea>
				<textarea class="editor">' . htmlspecialchars($sql, ENT_NOQUOTES) . '</textarea>
			</div>';
		
		$main_content .= '		
			<div class="save_button">
				<input class="back" type="button" name="back" value="Back" onClick="return onBackButton(this, 0);" />
				<input class="execute" type="submit" name="execute" value="Execute" onClick="return onExecuteButton(this);" />
			</div>';
	}
	else
		$main_content .= '<div>' . (isset($status_message) ? $status_message : "") . '</div>		
			<div class="save_button">
				<input class="back" type="button" name="back" value="Back" onClick="return onBackButton(this, 0);" />
			</div>';
	
	$main_content .= '
		</form>
	</div>
	
	<h3 class="table_errors_header">Execution Status</h3>
	<div class="table_errors">';
	
	if (!empty($error_message))
		$main_content .= '<div class="error">' . $error_message . (!empty($errors) ? '<br/>Please see errors bellow...' : '') . '</div>';
	else
		$main_content .= '<div>SQL executed successfully!</div>'; //Should not show bc we are always refreshing this page on success.
	
	if (!empty($errors))
		$main_content .= '<div class="errors">
			<label>Errors:</label>
			<ul>
				<li>' . implode('</li><li>', $errors) . '</li>
			</ul>
		</div>';
	
	$main_content .= '
		<div class="save_button">
			<input class="back" type="button" name="back" value="Back" onClick="return onBackButton(this, 1);" />
		</div>
	</div>';
}

$main_content .= '
</div>';
?>
