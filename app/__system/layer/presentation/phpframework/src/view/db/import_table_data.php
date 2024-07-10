<?
$head = '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icons CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/db/import_table_data.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/db/import_table_data.js"></script>

<script>
var column_head_html = \'' . str_replace("'", "\\'", str_replace("\n", "", getColumnHeadHtml("#column_index#"))) . '\';
var column_attributes_html = \'' . str_replace("'", "\\'", str_replace("\n", "", getColumnAttributeHtml($table_attrs))) . '\';
</script>';

$main_content = '
<div class="top_bar">
	<header>
		<div class="title">Import Data into Table \'' . $table . '\' in DB: \'' . $bean_name . '\'</div>
		<ul>
			<li class="import" data-title="Import"><a onClick="submitForm(this)"><i class="icon continue"></i> Import</a></li>
		</ul>
	</header>
</div>

<div class="import_table_data with_top_bar_section">';

if ($error_message)
	$main_content .= '<div class="error">' . $error_message . ($errors ? '<br/>Please see errors bellow...' : '') . '</div>';

$main_content .= '
	<form method="post" enctype="multipart/form-data">
		<div class="file_type">
			<label>File Type: </label>
			<select name="file_type" onChange="onChangeFileType(this)">
				<option value="txt">Text File</option>
				<option value="csv"' . ($file_type == "csv" ? " selected" : "") . '>CSV File</option>
				<!--option value="xls"' . ($file_type == "xls" ? " selected" : "") . '>Excel File</option-->
			</select>
		</div>
		
		<div class="file">
			<label>File: </label>
			<input type="file" name="file" />
		</div>
		
		<div class="rows_delimiter"' . ($file_type == "csv" || $file_type == "xls" ? ' style="display:none;"' : "") . '>
			<label>Rows Delimiter: </label>
			<input type="text" name="rows_delimiter" value="' . $rows_delimiter . '" placeHolder="Default is: end-line or \\n" />
		</div>
		
		<div class="columns_delimiter"' . ($file_type == "csv" || $file_type == "xls" ? ' style="display:none;"' : "") . '>
			<label>Columns Delimiter: </label>
			<input type="text" name="columns_delimiter" value="' . $columns_delimiter . '" placeHolder="Default is: Tab or \\t" />
		</div>
		
		<div class="enclosed_by"' . ($file_type == "csv" || $file_type == "xls" ? ' style="display:none;"' : "") . '>
			<label>Columns Enclosed By: </label>
			<input type="text" name="enclosed_by" value="' . $enclosed_by . '" placeHolder="Default is: &quot;" />
		</div>
		
		<div class="ignore_rows_number">
			<label>Number of first rows to ignore: </label>
			<input type="text" name="ignore_rows_number" value="' . $ignore_rows_number . '" />
		</div>
		
		<div class="insert_ignore">
			<input type="checkbox" name="insert_ignore" value="1" onClick="activateCheckBox(this)"' . ($insert_ignore ? " checked" : "") . ($update_existent ? " disabled" : "") . ' />
			<label>Only inserts new records</label>
			<div class="info">If this is checked, the system will not insert the repteaded records.</div>
		</div>
		
		<div class="update_existent">
			<input type="checkbox" name="update_existent" value="1" onClick="activateCheckBox(this)"' . ($update_existent ? " checked" : "") . ($insert_ignore ? " disabled" : "") . ' />
			<label>Update existent records</label>
			<div class="info">If this is checked, the system will update the repteaded records.</div>
		</div>
		
		<div class="force">
			<input type="checkbox" name="force" value="1"' . ($force ? " checked" : "") . ' />
			<label>Forces rows until the end.</label>
			<div class="info">If this is checked, the system will not stop on the first error and try to insert into the DB all rows until the end of the file.</div>
		</div>
		
		<div class="columns_attributes">
			<label>Columns to Attributes: <span class="icon add" onClick="addNewColumn(this)" title="Add Column">Add</span></label>
		</div>
		
		<table class="columns_attributes_table">
			<thead>
				<tr>';

	if (is_array($columns_attributes)) 
		foreach ($columns_attributes as $idx => $attr_name)
			$main_content .= getColumnHeadHtml($idx + 1);

	$main_content .= '
				</tr>
			</thead>
			<tbody class="fields">
				<tr>';

	if (is_array($columns_attributes)) 
		foreach ($columns_attributes as $idx => $attr_name)
			$main_content .= getColumnAttributeHtml($table_attrs, $attr_name);

	$main_content .= '
				</tr>
			</tbody>
		</table>
	</form>';

if ($errors)
	$main_content .= '<div class="errors">
		<label>Errors:</label>
		<ul>
			<li>' . implode('</li><li>', $errors) . '</li>
		</ul>
	</div>';

$main_content .= '
</div>';

function getColumnHeadHtml($column_index) {
	return '<th class="column table_header"><span class="label">Column ' . $column_index . '</span><span class="icon delete" onClick="removeColumn(this)" title="Remove Column">Remove</span></th>';
}

function getColumnAttributeHtml($table_attrs, $selected_attr_name = null) {
	$html = '
	<td class="column">
		<select name="columns_attributes[]">
			<option value="">-- none --</option>';
	
	if ($table_attrs)
		foreach ($table_attrs as $attr_name)
			$html .= '
			<option' . ($attr_name == $selected_attr_name ? ' selected' : '') . '>' . $attr_name . '</option>';
	
	$html .= '
		</select>
	</td>';
	
	return $html;
}
?>
