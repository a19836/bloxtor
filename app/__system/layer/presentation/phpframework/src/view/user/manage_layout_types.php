<?php
include $EVC->getUtilPath("UserAuthenticationUIHandler");

$head = '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icons CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/user/user.css" type="text/css" charset="utf-8" />
';

$main_content = '
<div id="menu">' . UserAuthenticationUIHandler::getMenu($UserAuthenticationHandler, $project_url_prefix, $entity) . '</div>
<div id="content">
	<div class="top_bar">
		<header>
			<div class="title">Manage Layout Types</div>
		</header>
	</div>
	
	<div class="layout_types_list">
	<table>
		<tr>
			<th class="table_header layout_type_id">Id</th>
			<th class="table_header type_id">Type</th>
			<th class="table_header name">Name</th>
			<th class="table_header created_date">Created Date</th>
			<th class="table_header modified_date">Modified Date</th>
			<th class="table_header buttons">
				<a class="icon add" href="' . $project_url_prefix . 'user/edit_layout_type" title="Add">Add</a>
			</th>
		</tr>';

$t = count($layout_types);
for ($i = 0; $i < $t; $i++) {
	$layout_type = $layout_types[$i];
	$type = $available_types[ $layout_type["type_id"] ];
	
	$main_content .= '<tr>
		<td class="layout_type_id">' . $layout_type["layout_type_id"] . '</td>
		<td class="type_id">' . ($type ? $type : $layout_type["type_id"]) . '</td>
		<td class="name">' . $layout_type["name"] . '</td>
		<td class="created_date">' . $layout_type["created_date"] . '</td>
		<td class="modified_date">' . $layout_type["modified_date"] . '</td>
		<td class="buttons">
			<a class="icon edit" href="' . $project_url_prefix . 'user/edit_layout_type?layout_type_id=' . $layout_type["layout_type_id"] . '" title="Edit">Edit</a>
		</td>
	</tr>';
}

$main_content .= '</table>
	</div>
</div>';
?>
