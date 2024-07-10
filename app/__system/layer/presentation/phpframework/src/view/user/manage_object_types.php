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
			<div class="title">Manage Object Types</div>
		</header>
	</div>
	
	<div class="object_types_list">
	<table>
		<tr>
			<th class="table_header object_type_id">Id</th>
			<th class="table_header name">Name</th>
			<th class="table_header created_date">Created Date</th>
			<th class="table_header modified_date">Modified Date</th>
			<th class="table_header buttons">
				<a class="icon add" href="' . $project_url_prefix . 'user/edit_object_type" title="Add">Add</a>
			</th>
		</tr>';

$t = count($object_types);
for ($i = 0; $i < $t; $i++) {
	$object_type = $object_types[$i];
	
	$main_content .= '<tr>
		<td class="object_type_id">' . $object_type["object_type_id"] . '</td>
		<td class="name">' . $object_type["name"] . '</td>
		<td class="created_date">' . $object_type["created_date"] . '</td>
		<td class="modified_date">' . $object_type["modified_date"] . '</td>
		<td class="buttons">
			<a class="icon edit" href="' . $project_url_prefix . 'user/edit_object_type?object_type_id=' . $object_type["object_type_id"] . '" title="Edit">Edit</a>
		</td>
	</tr>';
}

$main_content .= '</table>
	</div>
</div>';
?>
