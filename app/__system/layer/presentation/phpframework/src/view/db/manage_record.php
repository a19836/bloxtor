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

include_once get_lib("org.phpframework.util.text.TextValidator");
include_once get_lib("org.phpframework.util.MimeTypeHandler");

$manage_record_action_url = $project_url_prefix . "phpframework/db/manage_record_action?layer_bean_folder_name=$layer_bean_folder_name&bean_name=$bean_name&bean_file_name=$bean_file_name&table=$table";

$head = '
<!-- Add MD5 JS File -->
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquery/js/jquery.md5.js"></script>

<!-- TimePicker -->
<link rel="stylesheet" media="all" type="text/css" href="' . $project_common_url_prefix . 'vendor/jquerytimepickeraddon/dist/jquery-ui-timepicker-addon.min.css" />
<script src="' . $project_common_url_prefix . 'vendor/jquerytimepickeraddon/dist/jquery-ui-timepicker-addon.min.js" type="text/javascript"></script>
<script src="' . $project_common_url_prefix . 'vendor/jquerytimepickeraddon/dist/i18n/jquery-ui-timepicker-addon-i18n.min.js" type="text/javascript"></script>
<script src="' . $project_common_url_prefix . 'vendor/jquerytimepickeraddon/dist/jquery-ui-sliderAccess.js" type="text/javascript"></script>
<script src="' . $project_common_url_prefix . 'vendor/jquerytimepickeraddon/dist/i18n/jquery-ui-timepicker-pt.js" type="text/javascript"></script>
';

$exists_tinymce = file_exists($EVC->getWebrootPath($EVC->getCommonProjectName()) . "vendor/tinymce/js/tinymce/tinymce.min.js");

if ($exists_tinymce)
	$head .= '
<!-- Tinymce -->
<script src="' . $project_common_url_prefix . 'vendor/tinymce/js/tinymce/tinymce.min.js" type="text/javascript"></script>
<script src="' . $project_common_url_prefix . 'vendor/tinymce/js/tinymce/jquery.tinymce.min.js" type="text/javascript"></script>
';

$head .= '
<!-- Modernizr  -->
<script src="' . $project_common_url_prefix . 'vendor/modernizr/modernizr.min.js"></script>

<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Icons CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/db/manage_record.css" charset="utf-8" />
<script src="' . $project_url_prefix . 'js/db/manage_record.js"></script>

<script>
var manage_record_action_url = \'' . $manage_record_action_url . '\';
var is_popup = ' . ($popup ? 1 : 0) . ';
</script>';

$main_content = '
<div class="manage_record' . ($popup ? ' in_popup' : '') . '">
	<div class="top_bar' . ($popup ? ' in_popup' : '') . '">
		<header>
			<div class="title">' . (!empty($results) ? "Edit" : "Add") . ' Record for table: \'' . $table . '\' in DB: \'' . $bean_name . '\'</div>
			<ul>';
				

if ($action == "insert")
	$main_content .= '<li class="save" data-title="Add"><a onClick="addRecord(this)"><i class="icon save"></i> Save</a></li>';
else
	$main_content .= '
					<li class="delete" data-title="Delete"><a onClick="deleteRecord(this)"><i class="icon delete"></i> Delete</a></li>
					<li class="save" data-title="Save"><a onClick="saveRecord(this)"><i class="icon save"></i> Save</a></li>';

$main_content .= '	
			</ul>
		</header>
	</div>';

if (empty($table_exists))
	$main_content .= '<div class="error">Table does not exist!</div>';
else if (empty($table_fields))
	$main_content .= '<div class="error">Table fields do not exist!</tr>';
else if (empty($results) && $action != "insert")
	$main_content .= '<div class="error">Record does not exists!</div>';
else {
	foreach ($pks as $field_name)
		$main_content .= '<input type="hidden" name="' . $field_name . '" value="' . (isset($results[$field_name]) ? $results[$field_name] : "") . '" />';
	
	$main_content .= '<table>';
	foreach ($table_fields as $field_name => $field) {
		$field_value = isset($results[$field_name]) ? $results[$field_name] : null;
		$is_binary = false;
		$label = ucwords(str_replace(array("_", "-"), " ", strtolower($field_name)));
		$field_html_type = isset($table_fields_types[$field_name]) ? $table_fields_types[$field_name] : null;
		$field_html_type = $field_html_type ? $field_html_type : "text";
		
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
		
		$main_content .= '
				<tr>
					<th>' . $label . ': </th>
					<td>';
		
		if ($is_binary && $field_html_type != "file")
			$main_content .= $field_value;
		else {
			$options = null;
			
			if (is_array($field_html_type)) {
				$options = isset($field_html_type["options"]) ? $field_html_type["options"] : null;
				$field_html_type = isset($field_html_type["type"]) ? $field_html_type["type"] : null;
				$field_html_type = $options ? $field_html_type : "text";
			}
			
			if ($field_html_type == "textarea")
				$main_content .= '<textarea name="' . $field_name . '">' . $field_value . '</textarea>';
			else if ($field_html_type == "file") {
				$main_content .= '<div class="file_content">' . $field_value . '</div>';
				$main_content .= '<input type="file" name="' . $field_name . '" />';
			}
			else if ($field_html_type == "select") {
				$main_content .= '<select name="' . $field_name . '">';
				$value_exists = false;
				
				if (is_array($options))
					foreach($options as $v => $l) {
						$main_content .= '<option value="' . $v . '"' . ($v == $field_value ? ' selected' : '') . '>' . $l . '</option>';
						
						if ($v == $field_value)
							$value_exists = true;
					}
				
				if (!$value_exists)
					$main_content .= '<option value="' . $field_value . '" selected>' . $field_value . '</option>';
				
				$main_content .= '</select>';
			}
			else if ($field_html_type == "checkbox" || $field_html_type == "radio")
				$main_content .= '<input type="' . $field_html_type . '" name="' . $field_name . '" value="1"' . ($field_value == 1 ? ' checked' : '') . '/>';
			else 
				$main_content .= '<input type="' . $field_html_type . '" name="' . $field_name . '" value="' . $field_value . '"/>';
		}
		
		$main_content .= '</td>
		</tr>';
	}
	
	$main_content .= '</table>';
}

$main_content .= '</div>';
?>
