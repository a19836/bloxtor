<?php
include $EVC->getUtilPath("BreadCrumbsUIHandler");

$head = '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icon CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Layout UI Editor - Add Html/CSS/JS Beautify code -->
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jsbeautify/js/lib/beautify.js"></script>
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jsbeautify/js/lib/beautify-css.js"></script>
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'lib/myhtmlbeautify/MyHtmlBeautify.js"></script>

<!-- Add Local CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/db/generate_table_with_ai.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/db/generate_table_with_ai.js"></script>

<script>
function onSuccessTableGeneration() {';

if ($on_success_js_func)
	$head .= "
	if (typeof window.parent.$on_success_js_func == 'function')
		window.parent.$on_success_js_func();";
else
	$head .= "
	if (typeof window.parent.refreshLastNodeParentChilds == 'function' && window.parent.last_selected_node_id && window.parent.$('#' + window.parent.last_selected_node_id + ' > a > i.table').length > 0)
		window.parent.refreshLastNodeParentChilds();
	else if (typeof window.parent.refreshAndShowNodeChilds == 'function' && window.parent.last_selected_node_id && window.parent.$('#' + window.parent.last_selected_node_id + ' > a > i.attribute').length > 0)
		window.parent.refreshAndShowNodeChilds( window.parent.$('#' + window.parent.last_selected_node_id).parent().parent().parent().parent() );
	else if (typeof window.parent.refreshAndShowLastNodeChilds == 'function')
		window.parent.refreshAndShowLastNodeChilds();";

$head .= "
}
</script>";

$main_content = '<div class="generate_table_with_ai">
	<div class="top_bar">
		<header>
			<div class="title">
				Generate Table through AI 
				<input type="text" name="table_name" placeHolder="Table name" />
			</div>
			<ul>
				<li class="save" data-title="Generate Table"><a onClick="saveTable()"><i class="icon save"></i> Generate Table</a></li>
			</ul>
		</header>
	</div>';

$main_content .= '
	<div class="instructions">
		<label>Please write in natural language what table do you wish to create:</label>
		<textarea maxlength="1000" placeHolder="Eg:
Table to contain my products. A product contains a name, description , price, photo and some notes."></textarea>
		<div class="info">1000 characters maximum</div>
	</div>
</div>';
?>
