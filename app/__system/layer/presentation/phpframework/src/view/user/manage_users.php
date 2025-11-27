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
			<div class="title">Manage Users</div>
		</header>
	</div>
	
	<div class="users_list">
	<table>
		<tr>
			<th class="table_header user_id">User Id</th>
			<th class="table_header username">Username</th>
			<th class="table_header name">Name</th>
			<th class="table_header created_date">Created Date</th>
			<th class="table_header modified_date">Modified Date</th>
			<th class="table_header buttons">
				<a class="icon add" href="' . $project_url_prefix . 'user/edit_user" title="Add">Add</a>
			</th>
		</tr>';

$t = count($users);
for ($i = 0; $i < $t; $i++) {
	$user = $users[$i];
	
	$main_content .= '<tr>
		<td class="user_id">' . (isset($user["user_id"]) ? $user["user_id"] : "") . '</td>
		<td class="username">' . (isset($user["username"]) ? $user["username"] : "") . '</td>
		<td class="name">' . (isset($user["name"]) ? $user["name"] : "") . '</td>
		<td class="created_date">' . (isset($user["created_date"]) ? $user["created_date"] : "") . '</td>
		<td class="modified_date">' . (isset($user["modified_date"]) ? $user["modified_date"] : "") . '</td>
		<td class="buttons">
			<a class="icon edit" href="' . $project_url_prefix . 'user/edit_user?user_id=' . (isset($user["user_id"]) ? $user["user_id"] : "") . '" title="Edit">Edit</a>
		</td>
	</tr>';
}

$main_content .= '</table>
	</div>
</div>';
?>
