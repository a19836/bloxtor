<?php
$var_html = '<tr>
		<td class="var_name"><input type="text" class="var_name" name="vars_name[]" value="#var_name#" allownull="false" validationtype="Variable Name" validationregex="/^([\w\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u024F\u1EBD\u1EBC]+)$/g" validationmessage="Invalid variable name." /></td>
		<td class="var_value"><input type="text" class="var_value" name="vars_value[]" value="#var_value#" allownull="true" /></td>
		<td class="buttons"><a class="icon delete" onClick="$(this.parentNode.parentNode).remove();">REMOVE</a></td>
	</tr>'; //Do not use '/^([\p{L}\w]+)$/gu' bc it does not work in IE.

$head = '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icons CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" />

<!-- Add Layout CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" />

<script>
if (parent) {
	parent.workflow_global_variables = ' . json_encode($vars) . ';
}

function addNewVariable() {
	$(".global_vars .vars").append(\'' . addcslashes(str_replace(array("#var_name#", "#var_value#", "\n"), "", $var_html), "\\'") . '\');
}
function onSubmitButtonClick(elm) {
	elm = $(elm);
	
	var on_click = elm.attr("onClick");
	elm.addClass("loading").removeAttr("onClick");
	
	elm.parent().closest(".top_bar").parent().find(".global_vars form input.save[type=submit]").click();
}
</script>
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layer/list_global_vars.css" type="text/css" charset="utf-8" />';

$main_content = '
<div class="top_bar' . ($popup ? " in_popup" : "") . '">
	<header>
		<div class="title">Global Variables <a class="icon add" href="javascript:void(0)" onClick="return addNewVariable();" title="Add new variable">Add</a></div>
		<ul>
			<li class="save" data-title="Save"><a onclick="onSubmitButtonClick(this);"><i class="icon continue"></i> Save</a></li>
		</ul>
	</header>
</div>

<div class="global_vars">';

if (is_array($vars)) {
	$main_content .= '
	<form method="post" onSubmit="return MyJSLib.FormHandler.formCheck(this);">
		<table class="vars">
			<tr>
				<th class="var_name">Variable Name</th>
				<th class="var_value">Variable Value</th>
				<th class="buttons"></th>
			</tr>';
	
	foreach ($vars as $name => $value)
		$main_content .= str_replace("#var_value#", str_replace('"', '&quot;', $value), str_replace("#var_name#", $name, $var_html));
	
	$main_content .= '
		</table>
		
		<input class="save" type="submit" name="save" value="Save" confirmation="1" />
	</form>';
}

$main_content .= '</div>';
?>
