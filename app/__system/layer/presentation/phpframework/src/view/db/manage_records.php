<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.util.text.TextValidator");
include_once get_lib("org.phpframework.util.MimeTypeHandler");

$table_fields = isset($table_fields) ? $table_fields : null;
$table_fields_types = isset($table_fields_types) ? $table_fields_types : null;
$pks = isset($pks) ? $pks : null;
$fks = isset($fks) ? $fks : null;
$extra_fks = isset($extra_fks) ? $extra_fks : null;

$manage_records_url = $project_url_prefix . "phpframework/db/manage_records?layer_bean_folder_name=$layer_bean_folder_name&bean_name=$bean_name&bean_file_name=$bean_file_name&table=#table#&db_type=$db_type";
$manage_record_url = $project_url_prefix . "phpframework/db/manage_record?layer_bean_folder_name=$layer_bean_folder_name&bean_name=$bean_name&bean_file_name=$bean_file_name&table=$table&db_type=$db_type";
$manage_record_action_url = $project_url_prefix . "phpframework/db/manage_record_action?layer_bean_folder_name=$layer_bean_folder_name&bean_name=$bean_name&bean_file_name=$bean_file_name&table=$table";

$head = '
<!-- TimePicker -->
<link rel="stylesheet" media="all" type="text/css" href="' . $project_common_url_prefix . 'vendor/jquerytimepickeraddon/dist/jquery-ui-timepicker-addon.min.css" />
<script src="' . $project_common_url_prefix . 'vendor/jquerytimepickeraddon/dist/jquery-ui-timepicker-addon.min.js" type="text/javascript"></script>
<script src="' . $project_common_url_prefix . 'vendor/jquerytimepickeraddon/dist/i18n/jquery-ui-timepicker-addon-i18n.min.js" type="text/javascript"></script>
<script src="' . $project_common_url_prefix . 'vendor/jquerytimepickeraddon/dist/jquery-ui-sliderAccess.js" type="text/javascript"></script>
<script src="' . $project_common_url_prefix . 'vendor/jquerytimepickeraddon/dist/i18n/jquery-ui-timepicker-pt.js" type="text/javascript"></script>

<!-- Modernizr  -->
<script src="' . $project_common_url_prefix . 'vendor/modernizr/modernizr.min.js"></script>

<!-- Add DataTables Plugin -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/jquerydatatables/media/css/jquery.dataTables.min.css" charset="utf-8" />
<script src="' . $project_common_url_prefix . 'vendor/jquerydatatables/media/js/jquery.dataTables.min.js"></script>

<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icons CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/db/manage_records.css" charset="utf-8" />
<script src="' . $project_url_prefix . 'js/db/manage_records.js"></script>

<script>
var manage_record_url = \'' . $manage_record_url . '\';
var manage_record_action_url = \'' . $manage_record_action_url . '\';

var table_fields_types = ' . json_encode($table_fields_types) . ';
var table_fks = ' . json_encode($fks) . ';
var table_extra_fks = ' . json_encode($extra_fks) . ';
var new_row_html = \'' . addcslashes(str_replace("\n", "", getRowHtml("#idx#", $table_fields, $table_fields_types, $pks, $fks, $extra_fks, $manage_records_url)), "\\'") . '\';
var new_condition_html = \'' . addcslashes(str_replace("\n", "", getConditionHtml("#field_name#")), "\\'") . '\';
</script>';

$back_icon = isset($_SERVER["HTTP_REFERER"]) && strpos($_SERVER["HTTP_REFERER"], "/db/manage_records?") !== false ? '<li class="back" data-title="Back Page"><a class="icon go_back" onClick="goBackPage(this)">Go Back</a></li>' : ''; //only if is manage_records page

$main_content = '
<div class="top_bar' . ($popup ? " in_popup" : "") . '">
	<header>
		<div class="title">
			Manage Records for table: \'' . $table . '\' in DB: \'' . $bean_name . '\'
			<select class="db_type" onChange="onDBTypeChange(this)">
				<option value="db">From DB Server</option>
				<option value="diagram"' . ($db_type == "diagram" ? ' selected' : '') . '>From DB Diagram</option>
			</select>
		</div>
		<ul>
			' . $back_icon . '
			<li class="refresh" data-title="Refresh Page"><a class="icon refresh" onClick="refreshPage(this)">Refresh</a></li>
		</ul>
	</header>
</div>

<div class="manage_records with_top_bar_section' . ($popup ? " in_popup" : "") . '">';

if (empty($table_exists))
	$main_content .= '<div class="error">Table does not exist!</tr>';
else if (empty($table_fields))
	$main_content .= '<div class="error">Table fields do not exist!</tr>';
else {
	//prepare pagination html
	$PaginationLayout->show_x_pages_at_once = 5;
	$data = $PaginationLayout->data;
	$data["style"] = "bootstrap1";
	$pagination_html = $PaginationLayout->designWithStyle(1, $data);
	
	$head .= '<style>' . $PaginationLayout->getBootstrap1Css() . '</style>';
	
	//prepare conditions
	$main_content .= '<div class="conditions">
		<label>
			Conditions: 
			<select>';
	
	foreach ($table_fields as $field_name => $field) {
		$label = ucwords(str_replace(array("_", "-"), " ", strtolower($field_name)));
		$main_content .= '<option value="' . $field_name . '">' . $label . '</option>';
	}
	
	$main_content .= '			
			</select>
			<span class="icon add" title="Add new condition to search" onClick="addCondition(this)">Add</span>
			<input type="button" value="Reset" onClick="resetCondition(this)">
			<input type="button" value="Search" onClick="searchCondition(this)">
		</label>
		<ul>';
	
	if (!empty($conditions))
		foreach ($conditions as $field_name => $field_value)
			$main_content .= getConditionHtml($field_name, $field_value, isset($conditions_operators[$field_name]) ? $conditions_operators[$field_name] : null);
	
	$main_content .= '	
		</ul>
	</div>';
	
	//prepare main content
	$main_content .= '
	<div class="top_pagination">' . $pagination_html . '</div>
	
	<form method="post" onSubmit="return MyJSLib.FormHandler.formCheck(this);" enctype="multipart/form-data">
		<div class="responsive_table">
			<table class="display compact">
				<thead>
					<tr>
						<th class="table_header select_item">
							<input type="checkbox" onClick="toggleAll(this)" />
						</th>';
	
	foreach ($table_fields as $field_name => $field) {
		$label = ucwords(str_replace(array("_", "-"), " ", strtolower($field_name)));
		$sorts_field_name = isset($sorts[$field_name]) ? $sorts[$field_name] : null;
		
		$main_content .= '	<th class="table_header" attr_name="' . $field_name . '">
							' . $label . '
							<span class="icon sort' . ($sorts_field_name ? " sort_" . $sorts_field_name : "") . '" title="' . ($sorts_field_name ? "Sorted " . $sorts_field_name: "Not sorted") . '" onClick="sortRecords(this)">Sort</span>
							' . ($sorts_field_name ? '<span class="icon unsort" title="Unsort" onClick="unsortRecords(this)">Unsort</span>' : '') . '
						</th>';
	}
		
	$main_content .= '
						<th class="table_header actions">
							<span class="icon add" onClick="insertNewRecord(this)">Add</span>
						</th>';
	
	if ($fks || $extra_fks)
		$main_content .= '	<th class="table_header fks"></th>';
		
	$main_content .= '	</tr>
				</thead>
				<tbody>';
	
	if (empty($results))
		$main_content .= '<tr><td class="empty" colspan="' . ($table_fields ? count($table_fields) + 2 + ($fks || $extra_fks ? 1 : 0) : 0) . '">Empty results...</tr>';
	else {
		$t = count($results);
		for ($i = 0; $i < $t; $i++)
			$main_content .= getRowHtml($i, $table_fields, $table_fields_types, $pks, $fks, $extra_fks, $manage_records_url, $results[$i]);
	}
	
	$main_content .= '</tbody>
			</table>
		</div>
		
		<div class="total">Total of records: ' . $count . '</div>
		
		<div class="buttons">
			<input class="delete" type="submit" name="delete" value="Delete" data-confirmation="1" data-confirmation-message="Do you wish to delete this Item?">
		</div>
	</form>
	<div class="bottom_pagination">' . $pagination_html . '</div>';
}

$main_content .= '</div>';

function getConditionHtml($field_name, $field_value = null, $operator = null) {
	$label = ucwords(str_replace(array("_", "-"), " ", strtolower($field_name)));
	
	return '
	<li>
		<label>' . $label . '</label>
		<select>
			<option value="="' . ($operator == '=' ? ' selected' : '') . '>equal</option>
			<option value="!="' . ($operator == '!=' ? ' selected' : '') . '>different</option>
			<option value="&gt;"' . ($operator == '>' ? ' selected' : '') . '>bigger</option>
			<option value="&lt;"' . ($operator == '<' ? ' selected' : '') . '>smaller</option>
			<option value="&gt;="' . ($operator == '>=' ? ' selected' : '') . '>bigger or equal</option>
			<option value="&lt;="' . ($operator == '<=' ? ' selected' : '') . '>smaller or equal</option>
			<option value="like"' . ($operator == 'like' ? ' selected' : '') . '>contains</option>
			<option value="not like"' . ($operator == 'not like' ? ' selected' : '') . '>not contains</option>
			<option value="in"' . ($operator == 'in' ? ' selected' : '') . '>in ("," delimiter)</option>
			<option value="not in"' . ($operator == 'not in' ? ' selected' : '') . '>not in ("," delimiter)</option>
			<option value="is"' . ($operator == 'is' ? ' selected' : '') . '>is null|true|false</option>
			<option value="not is"' . ($operator == 'is not' ? ' selected' : '') . '>not is null|true|false</option>
		</select>
		<input type="text" name="' . $field_name . '" value="' . $field_value . '" />
		<span class="icon delete" onClick="deleteCondition(this)" title="Delete">Remove</span>
	</li>';
}

function getRowHtml($i, $table_fields, $table_fields_types, $pks = null, $fks = null, $extra_fks = null, $manage_records_url = null, $row = null) {
	$table_fields = $table_fields ? $table_fields : array();
	$table_fields_types = $table_fields_types ? $table_fields_types : array();
	$pks = $pks ? $pks : array();
	$row = $row ? $row : array();
	
	$html = '<tr>
		<td class="select_item">
			<input type="checkbox" name="selected_rows[]" value="' . $i . '" title="To remove this record, select this box and then click in the delete button below." />';
	
	foreach ($pks as $field_name)
		$html .= '<input type="hidden" name="selected_pks[' . $i . '][' . $field_name . ']" value="' . (isset($row[$field_name]) ? $row[$field_name] : "") . '" />';
	
	$html .= '
		</td>';
	
	foreach ($table_fields as $field_name => $field) {
		$field_value = isset($row[$field_name]) ? $row[$field_name] : null;
		$is_binary = false;
		$field_html_type = isset($table_fields_types[$field_name]) ? $table_fields_types[$field_name] : null;
		
		//prepare results data, converting binary to base64 or images
		if (TextValidator::isBinary($field_value)) {
			$is_binary = true;
			$finfo = new finfo(FILEINFO_MIME_TYPE);
			$mime_type = $finfo->buffer($field_value);
			
			if (MimeTypeHandler::isImageMimeType($mime_type))
				$field_value = "<img src=\"data:$mime_type;base64, " . base64_encode($field_value) . "\" />";
			else if (!MimeTypeHandler::isTextMimeType($mime_type))
				$field_value = "<a onClick=\"downloadFile(this, '$field_name')\">Download File</a>";
		}
		
		if (!$is_binary && is_array($field_html_type) && !empty($field_html_type["options"]) && array_key_exists($field_value, $field_html_type["options"]))
			$html .= '<td attr_name="' . str_replace('"', "&quot;", $field_name) . '" attr_value="' . str_replace('"', "&quot;", $field_value) . '">' . (isset($field_html_type["options"][$field_value]) ? $field_html_type["options"][$field_value] : "") . '</td>';
		else
			$html .= '<td' . ($is_binary && $field_html_type != "file" ? ' class="binary"' : '') . ' attr_name="' . str_replace('"', "&quot;", $field_name) . '">' . ($field_html_type == "file" ? '<div class="file_content">' . $field_value . '</div>' : $field_value) . '</td>';
	}
	
	$html .= '
		<td class="actions">
			<span class="icon delete" title="Delete" onClick="deleteRow(this)">Remove</span>
			<span class="icon switch" title="Make it editable" onClick="toggleRow(this)">Make it editable</span>
			<span class="icon save" title="Save" onClick="saveRow(this)">Save</span>
			<span class="icon undo undo_toggle" title="Discard changes and make it readonly" onClick="toggleRow(this)">Make it readonly</span>
			<span class="icon edit" title="Edit form" onClick="editRow(this)">Edit</span>
		</td>';
	
	if (($fks || $extra_fks) && $manage_records_url) {
		$html .= '<td class="fks">';
		
		if ($fks)
			foreach ($fks as $fk_table => $fk_attributes) {
				$fk_label = ucwords(str_replace(array("_", "-"), " ", strtolower($fk_table)));
				$fk_label = substr($fk_label, -1) == "y" ? substr($fk_label, 0, -1) . "ies" : $fk_label . "s";
				$fk_url = str_replace("#table#", $fk_table, $manage_records_url);
				
				foreach ($fk_attributes as $fk_attribute => $attribute)
					$fk_url .= "&conditions[$fk_attribute]=" . (isset($row[$attribute]) ? $row[$attribute] : "");
				
				$html .= '<a fk_table="' . $fk_table . '" href="' . $fk_url . '">' . $fk_label . '</a>';
			}
		
		if ($extra_fks)
			foreach ($extra_fks as $fk_table => $fk_attributes) {
				$fk_label = ucwords(str_replace(array("_", "-"), " ", strtolower($fk_table)));
				$fk_label = substr($fk_label, -1) == "y" ? substr($fk_label, 0, -1) . "ies" : $fk_label . "s";
				$fk_label .= $fks && $fks[$fk_table] ? " 2" : "";
				$fk_url = str_replace("#table#", $fk_table, $manage_records_url);
				
				foreach ($fk_attributes as $fk_attribute => $attribute)
					$fk_url .= "&conditions[$fk_attribute]=" . (isset($row[$attribute]) ? $row[$attribute] : "");
				
				$html .= '<a fk_table="' . $fk_table . '" href="' . $fk_url . '">' . $fk_label . '</a>';
			}
		
		$html .= '</td>';
	}
	
	$html .= '
	</tr>';
	
	return $html;
}
?>
