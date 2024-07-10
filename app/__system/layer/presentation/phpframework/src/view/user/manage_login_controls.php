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
			<div class="title">Manage Login Controls</div>
		</header>
	</div>
	
	<div class="login_controls_list">
	<table>
		<tr>
			<th class="table_header username">Username</th>
			<th class="table_header session_id">Session Id</th>
			<th class="table_header failed_login_attempts">Failed Login Attempts</th>
			<th class="table_header failed_login_time">Failed Login Time</th>
			<th class="table_header login_expired_time">Login Expired Time</th>
			<th class="table_header created_date">Created Date</th>
			<th class="table_header modified_date">Modified Date</th>
			<th class="table_header buttons"></th>
		</tr>';

$t = count($login_controls);
for ($i = 0; $i < $t; $i++) {
	$login_control = $login_controls[$i];
	
	$main_content .= '<tr>
		<td class="username">' . $login_control["username"] . '</td>
		<td class="session_id">' . $login_control["session_id"] . '</td>
		<td class="failed_login_attempts">' . $login_control["failed_login_attempts"] . '</td>
		<td class="failed_login_time">' . $login_control["failed_login_time"] . '</td>
		<td class="login_expired_time">' . date("Y-m-d H:i:s", $login_control["login_expired_time"]) . '</td>
		<td class="created_date">' . $login_control["created_date"] . '</td>
		<td class="modified_date">' . $login_control["modified_date"] . '</td>
		<td class="buttons">
			<a class="icon edit" href="' . $project_url_prefix . 'user/edit_login_control?username=' . $login_control["username"] . '" title="Edit">Edit</a>
		</td>
	</tr>';
}

$main_content .= '</table>
	</div>
</div>';
?>
