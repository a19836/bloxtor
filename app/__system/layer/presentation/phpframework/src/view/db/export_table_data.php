<?
include $EVC->getViewPath("dataaccess/edit_query");

$export_type = isset($export_type) ? $export_type : null;
$doc_name = isset($doc_name) ? $doc_name : null;

$head .= '
<!-- Add Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/db/export_table_data.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/db/export_table_data.js"></script>
';

$form_html = '
<form class="export_form" method="post">
	<input type="hidden" name="sql"/>
	
	<select name="export_type">
		<option value="txt">in Text Format - Tab delimiter</option>
		<option value="csv"' . ($export_type == "csv" ? " selected" : "") . '>in CSV Format - Comma Separated</option>
		<option value="xls"' . ($export_type == "xls" ? " selected" : "") . '>to Excel</option>
	</select>
	
	<input type="text" name="doc_name" value="' . $doc_name . '" placeHolder="Name"/>
</form>';

$main_content .= '
<script>
//change title
$(".top_bar .title").html("Export <span class=\"export_type\"></span> to document <span class=\"doc_name\"></span>, the table \'' . $table . '\' in DB: \'' . $bean_name . '\'");

//remove edit_query fields
var data_access_obj = $(".data_access_obj");
var relationship = data_access_obj.find(" > .relationships > .rels > .relationship");
relationship.find(".rel_type, .rel_name, .parameter_class_id, .parameter_map_id, .result_class_id, .result_map_id").hide();

//add new field:
var html = \'' . str_replace("\n", "", addcslashes($form_html, "'")) . '\';
relationship.prepend(html);

//remove handlers from edit_query just in case
save_data_access_object_url = remove_data_access_object_url = null;

//change save function to export function
var top_bar_dropdown = $(".top_bar ul").first();
top_bar_dropdown.find(".save, .toggle_ids, .sub_menu").remove();
top_bar_dropdown.append(\'<li class="export" title="Export"><a onclick="exportTable()"><i class="icon continue"></i> Export</a></li>\');

saveQueryObject = function() {
	exportTable();
};
</script>';
?>
