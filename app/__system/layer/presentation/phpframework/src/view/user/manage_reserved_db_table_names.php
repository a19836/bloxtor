<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
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
			<div class="title">Manage Reserved DB Table Names</div>
		</header>
	</div>
	
	<div class="reserved_db_table_names_list">
	<table>
		<tr>
			<th class="table_header reserved_db_table_name_id">Id</th>
			<th class="table_header name">Name</th>
			<th class="table_header created_date">Created Date</th>
			<th class="table_header modified_date">Modified Date</th>
			<th class="table_header buttons">
				<a class="icon add" href="' . $project_url_prefix . 'user/edit_reserved_db_table_name" title="Add">Add</a>
			</th>
		</tr>';

$t = count($reserved_db_table_names);
for ($i = 0; $i < $t; $i++) {
	$reserved_db_table_name = $reserved_db_table_names[$i];
	
	$main_content .= '<tr>
		<td class="reserved_db_table_name_id">' . (isset($reserved_db_table_name["reserved_db_table_name_id"]) ? $reserved_db_table_name["reserved_db_table_name_id"] : "") . '</td>
		<td class="name">' . (isset($reserved_db_table_name["name"]) ? $reserved_db_table_name["name"] : "") . '</td>
		<td class="created_date">' . (isset($reserved_db_table_name["created_date"]) ? $reserved_db_table_name["created_date"] : "") . '</td>
		<td class="modified_date">' . (isset($reserved_db_table_name["modified_date"]) ? $reserved_db_table_name["modified_date"] : "") . '</td>
		<td class="buttons">
			<a class="icon edit" href="' . $project_url_prefix . 'user/edit_reserved_db_table_name?reserved_db_table_name_id=' . (isset($reserved_db_table_name["reserved_db_table_name_id"]) ? $reserved_db_table_name["reserved_db_table_name_id"] : "") . '" title="Edit">Edit</a>
		</td>
	</tr>';
}

$main_content .= '</table>
	</div>
</div>';
?>
