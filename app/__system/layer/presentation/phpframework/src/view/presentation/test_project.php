<?php
include $EVC->getUtilPath("BreadCrumbsUIHandler");

function getVarsHtml($type) {
	$vars = isset($_POST[$type . '_vars']) ? $_POST[$type . '_vars'] : null;
	$vars_html = "";
	
	if ($vars)
		foreach ($vars as $i => $var)
			if (!empty($var["name"]) || !empty($var["value"])) {
				$var_name = isset($var["name"]) ? $var["name"] : null;
				$var_value = isset($var["value"]) ? $var["value"] : null;
				
				$vars_html .= getVarHtml($type, $i, $var_name, $var_value);
			}
	
	$html = '<div class="vars ' . $type . '_vars">
		<label>' . ucfirst($type) . ' Variables:</label>
		<table>
			<thead>
				<tr>
					<th class="name">Variable Name</th>
					<th class="value">Variable Name</th>
					<th class="actions">
						<i class="icon add" onClick="addVar(this, \'' . $type . '\');">Add</i>
					</th>
				</tr>
			</thead>
			<tbody index_prefix="' . $type . '_vars">
				<tr class="no_vars"' . ($vars_html ? ' style="display:none;"' : '') . '><td colspan="3">No vars...</td></tr>
				' . $vars_html . '
			</tbody>
		</table>
	</div>';

	return $html;
}
function getVarHtml($type, $index, $name, $value) {
	return '<tr>
		<td class="name"><input type="text" name="' . $type . '_vars[' . $index . '][name]" placeHolder="write here your ' . $type . ' var name" value="' . $name . '" /></td>
		<td class="value"><input type="text" name="' . $type . '_vars[' . $index . '][value]" placeHolder="write here your ' . $type . ' var value" value="' . $value . '" /></td>
		<td class="actions"><i class="icon delete" onClick="removeVar(this)"></i></td>
	</tr>';
}

$head = '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icons CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add PHP CODE CSS -->
<link rel="stylesheet" href="http://jplpinto.localhost/__system/css/edit_php_code.css" type="text/css" charset="utf-8" />

<!-- Add Local CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/test_project.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/test_project.js"></script>

<script>
	var vars_html = \'' . addcslashes(str_replace("\n", "", getVarHtml("#type#", "#index#", "#name#", "#value#")), "'") . '\';
</script>';

$main_content = '
<div class="top_bar' . ($popup ? " in_popup" : "") . '">
	<header>
		<div class="title" title="' . $path . '">Test Page: <div class="breadcrumbs">' . BreadCrumbsUIHandler::getFilePathBreadCrumbsItemsHtml($path, null, true) . '</div></div>
		<ul>
			<li class="toggle_settings" data-title="Toggle Settings"><a onClick="toggleSettings(this)"><i class="icon maximize"></i></a></li>
		</ul>
	</header>
</div>
<div class="test_project' . ($popup ? " in_popup" : "") . '">
	<form method="post">
		' . getVarsHtml("get") . getVarsHtml("post") . '
		
		<div class="buttons">
			<input type="submit" name="test" value="Test"/>
		</div>
	</form>

	<iframe src="' . $view_project_url . '"></iframe>
</div>';
?>
