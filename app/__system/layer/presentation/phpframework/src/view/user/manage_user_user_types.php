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
			<div class="title">Manage User User Types</div>
		</header>
	</div>
	
	<div class="user_user_types_list">
	<table>
		<tr>
			<th class="table_header user_id">User Id</th>
			<th class="table_header user_type_id">User Type Id</th>
			<th class="table_header created_date">Created Date</th>
			<th class="table_header modified_date">Modified Date</th>
			<th class="table_header buttons">
				<a class="icon add" href="' . $project_url_prefix . 'user/edit_user_user_type" title="Add">Add</i></a>
			</th>
		</tr>';

$t = count($user_user_types);
for ($i = 0; $i < $t; $i++) {
	$user_user_type = $user_user_types[$i];
	
	$main_content .= '
	<tr>
		<td class="user_id">' . $users[ $user_user_type["user_id"] ] . '</td>
		<td class="user_type_id">' . $user_types[ $user_user_type["user_type_id"] ] . '</td>
		<td class="created_date">' . $user_user_type["created_date"] . '</td>
		<td class="modified_date">' . $user_user_type["modified_date"] . '</td>
		<td class="buttons">
			<a class="icon edit" href="' . $project_url_prefix . 'user/edit_user_user_type?user_id=' . $user_user_type["user_id"] . '&user_type_id=' . $user_user_type["user_type_id"] . '" title="Edit">Edit</a>
		</td>
	</tr>';
}

$main_content .= '</table>
	</div>
</div>';
?>
