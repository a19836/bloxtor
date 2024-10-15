<?php
class UserAuthenticationUIHandler {
	
	public static function getMenu($UserAuthenticationHandler, $project_url_prefix, $page_code = null) {
		$username = isset($UserAuthenticationHandler->auth["user_data"]["username"]) ? $UserAuthenticationHandler->auth["user_data"]["username"] : null;
		
		return '
		<ul>
			<li class="current_user">Current User: "' . $username . '"</li>
			<li class="manage_menu_item' . ($page_code == "user/manage_users" ? ' active' : '') . '"><a href="' . $project_url_prefix . 'user/manage_users">Manage Users</a></li>
			<!--li' . ($page_code == "user/edit_user" ? ' class="active"' : '') . '><a href="' . $project_url_prefix . 'user/edit_user">Add User</a></li-->
			
			<li class="manage_menu_item' . ($page_code == "user/manage_user_types" ? ' active' : '') . '"><a href="' . $project_url_prefix . 'user/manage_user_types">Manage User Types</a></li>
			<!--li' . ($page_code == "user/edit_user_type" ? ' class="active"' : '') . '><a href="' . $project_url_prefix . 'user/edit_user_type">Add User Type</a></li-->
			
			<li class="manage_menu_item' . ($page_code == "user/manage_object_types" ? ' active' : '') . '"><a href="' . $project_url_prefix . 'user/manage_object_types">Manage Object Types</a></li>
			<!--li' . ($page_code == "user/edit_object_type" ? ' class="active"' : '') . '><a href="' . $project_url_prefix . 'user/edit_object_type">Add Object Type</a></li-->
			
			<li class="manage_menu_item' . ($page_code == "user/manage_user_user_types" ? ' active' : '') . '"><a href="' . $project_url_prefix . 'user/manage_user_user_types">Manage User User Types</a></li>
			<!--li' . ($page_code == "user/edit_user_user_type" ? ' class="active"' : '') . '><a href="' . $project_url_prefix . 'user/edit_user_user_type">Add User User Type</a></li-->
			
			<li class="manage_menu_item' . ($page_code == "user/manage_permissions" ? ' active' : '') . '"><a href="' . $project_url_prefix . 'user/manage_permissions">Manage Permissions</a></li>
			<!--li' . ($page_code == "user/edit_permission" ? ' class="active"' : '') . '><a href="' . $project_url_prefix . 'user/edit_permission">Add Permission</a></li-->
			
			<li class="manage_menu_item manage_user_type_permissions' . ($page_code == "user/manage_user_type_permissions" ? ' active' : '') . '"><a href="' . $project_url_prefix . 'user/manage_user_type_permissions">Manage User Type Permissions</a></li>
			
			<li class="manage_menu_item manage_layout_types' . ($page_code == "user/manage_layout_types" ? ' active' : '') . '"><a href="' . $project_url_prefix . 'user/manage_layout_types">Manage Layout Types</a></li>
			<!--li' . ($page_code == "user/edit_layout_type" ? ' class="active"' : '') . '><a href="' . $project_url_prefix . 'user/edit_layout_type">Add Layout Type</a></li-->
			<li class="manage_menu_item manage_layout_type_permissions' . ($page_code == "user/manage_layout_type_permissions" ? ' active' : '') . '"><a href="' . $project_url_prefix . 'user/manage_layout_type_permissions">Manage Layout Type Permissions</a></li>
			
			<li class="manage_menu_item manage_reserved_db_table_names' . ($page_code == "user/manage_reserved_db_table_names" ? ' active' : '') . '"><a href="' . $project_url_prefix . 'user/manage_reserved_db_table_names">Manage Reserved DB Table Name</a></li>
			<!--li' . ($page_code == "user/edit_reserved_db_table_name" ? ' class="active"' : '') . '><a href="' . $project_url_prefix . 'user/edit_reserved_db_table_name">Add Reserved DB Table Name</a></li-->
			
			<li class="manage_menu_item manage_login_controls' . ($page_code == "user/manage_login_controls" ? ' active' : '') . '"><a href="' . $project_url_prefix . 'user/manage_login_controls">Manage Login Controls</a></li>
			
			' . ($UserAuthenticationHandler->isLocalDB() ? '<li class="manage_menu_item change_db_keys' . ($page_code == "user/change_db_keys" ? ' active' : '') . '"><a href="' . $project_url_prefix . 'user/change_db_keys">Change DB Keys</a></li>' : '') . '
			
			<li class="manage_menu_item change_auth_settings' . ($page_code == "user/change_auth_settings" ? ' active' : '') . '"><a href="' . $project_url_prefix . 'user/change_auth_settings">Change Auth Settings</a></li>
		</ul>';
	}
}
?>
