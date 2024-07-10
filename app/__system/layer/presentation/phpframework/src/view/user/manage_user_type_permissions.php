<?php
include $EVC->getUtilPath("UserAuthenticationUIHandler");

$head = '
<!-- Add MD5 JS File -->
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquery/js/jquery.md5.js"></script>

<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icons CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add FileManager JS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/file_manager.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/user/user.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/user/user.js"></script>

<script>
var get_user_type_permissions_url = \'' . $project_url_prefix . 'user/get_user_type_permissions?user_type_id=#user_type_id#\';
</script>
';

$main_content = '
<div id="menu">' . UserAuthenticationUIHandler::getMenu($UserAuthenticationHandler, $project_url_prefix, $entity) . '</div>
<div id="content">
	<div class="top_bar">
		<header>
			<div class="title">Manage User Type Permissions</div>
			<ul>
				<li class="save" data-title="Save"><a onClick="submitForm(this)"><i class="icon save"></i> Save</a></li>
			</ul>
		</header>
	</div>
	
	<div class="user_type_permissions_list">
	<form method="post" onSubmit="return saveUserTypePermissions();">
		<div class="user_type">
			<label>User Type: </label>
			<select name="user_type_id" onChange="updateUserTypePermissions(this)">';

foreach ($user_types as $name => $id) {
	$main_content .= '<option value="' . $id . '"' . ($user_type_id == $id ? ' selected' : '') . '>' . $name . '</option>';
}

$main_content .= '	</select>
		</div>
		
		<div class="user_type_permissions_content">
			<ul class="tabs">
				<li><a href="#pages_permissions_list">Pages Permissions</a></li>
				<li><a href="#layers_permissions_list">Layers Permissions</a></li>
				<li><a href="#admin_uis_permissions_list">Workspaces Permissions</a></li>
			</ul>
			
			<div id="pages_permissions_list">
				<table object_type_id="' . $page_object_type_id . '">
					<tr>
						<th class="table_header object_id">Pages</th>';

foreach ($permissions as $permission_name => $permission_id) {
	$main_content .= '		<th class="table_header user_type_permission user_type_permission_' . $permission_id . '">' . $permission_name . ' <input type="checkbox" onClick="toggleAllPermissions(this, \'user_type_permission_' . $permission_id . '\')" /></th>';
}
			
$main_content .= '		</tr>';

foreach ($pages as $page => $available_page_permissions) {
	$main_content .= '	<tr>
						<td class="object_id">' . $page . '</td>';
	
	foreach ($permissions as $permission_name => $permission_id) {
		$main_content .= '	<td class="user_type_permission user_type_permission_' . $permission_id . '" permission_id="' . $permission_id . '">';
		
		if ($available_page_permissions[$permission_name]) {
			$main_content .= '<input type="checkbox" name="permissions_by_objects[' . $page_object_type_id . '][' . $page . '][]" value="' . $permission_id . '" />';
		}
		
		$main_content .= '	</td>';
	}
	
	$main_content .= '	</tr>';
}

$main_content .= '	</table>
			</div>
			
			<div id="layers_permissions_list">
				<table class="mytree" object_type_id="' . $layer_object_type_id . '">
					<tr>
						<th class="table_header object_id">Layers</th>
						<th class="table_header user_type_permission user_type_permission_' . $permissions["access"] . '">access<input type="checkbox" onClick="toggleAllPermissions(this, \'user_type_permission_' . $permissions["access"] . '\')" /></th>
					</tr>';

foreach ($layers as $layer_type_name => $layer_type) {
	$main_content .= '
					<tr>
						<td class="main_node"><i class="icon main_node main_node_' . $layer_type_name . '"></i> ' . strtoupper(str_replace("_", " ", $layer_type_name)) . '</td>
						<td></td>
					</tr>';
	
	if ($layer_type)
		foreach ($layer_type as $layer_name => $layer) {
			$layer_props = $layers_props[$layer_type_name][$layer_name];
			
			if ($layer_type_name == "vendors" || $layer_type_name == "others")
				$object_id = $layer_name;
			else
				$object_id = "$layer_object_id_prefix/" . $layers_object_id[$layer_type_name][$layer_name];
			
			$main_content .= '
					<tr>
						<td class="object_id" object_id="' . $object_id . '"><i class="icon main_node_' . $layer_props["item_type"] . '"></i> ' . $layers_label[$layer_type_name][$layer_name] . '</td>
						<td class="user_type_permission user_type_permission_' . $permissions["access"] . '" permission_id="' . $permissions["access"] . '">
							<input type="checkbox" name="permissions_by_objects[' . $layer_object_type_id . '][' . $object_id . '][]" value="' . $permissions["access"] . '" default_value="0" />
							<span class="icon toggle" onClick="toggleLayerPermissionVisibility(this)" title="Set/Unset Permission">Toggle</span>
						</td>
					</tr>';
			
			foreach ($layer as $folder_name => $folder) {
				$object_id = "$layer_object_id_prefix/" . $layers_object_id[$layer_type_name][$layer_name] . "/$folder_name";
				$indentation = str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", count(explode("/", $folder_name)));
				
				$icon_class = $layer_type_name == "db_layers" ? "db_driver" : "project";
				
				$main_content .= '
					<tr>
						<td class="object_id" object_id="' . $object_id . '">' . $indentation . '<i class="icon ' . $icon_class . '"></i>' . $folder_name . '</td>
						<td class="user_type_permission user_type_permission_' . $permissions["access"] . '" permission_id="' . $permissions["access"] . '">
							<input type="checkbox" name="permissions_by_objects[' . $layer_object_type_id . '][' . $object_id . '][]" value="' . $permissions["access"] . '" default_value="0" />
							<span class="icon toggle" onClick="toggleLayerPermissionVisibility(this)" title="Set/Unset Permission">Toggle</span>
						</td>
					</tr>';
			}
		}
}

$main_content .= '	</table>
			</div>
			
			<div id="admin_uis_permissions_list">
				<table object_type_id="' . $admin_ui_object_type_id . '">
					<tr>
						<th class="table_header object_id">Workspaces</th>
						<th class="table_header user_type_permission user_type_permission_' . $permissions["access"] . '">access<input type="checkbox" onClick="toggleAllPermissions(this, \'user_type_permission_' . $permissions["access"] . '\')" /></th>
					</tr>';

foreach ($admin_uis as $object_id => $label)
	$main_content .= '	<tr>
						<td class="object_id" object_id="' . $object_id . '">' . $label . '</td>
						<td class="user_type_permission user_type_permission_' . $permissions["access"] . '" permission_id="' . $permissions["access"] . '">
							<input type="checkbox" name="permissions_by_objects[' . $admin_ui_object_type_id . '][' . $object_id . '][]" value="' . $permissions["access"] . '" />
						</td>
					</tr>';
					
$main_content .= '	</table>
			</div>
		</div>
		<div class="buttons">
			<div class="submit_button">
				<input type="submit" name="save" value="Save" />
			</div>
		</div>
	</form>
	</div>
</div>
<script>
	$(".user_type_permissions_content").tabs();
	
	updateUserTypePermissions( $(".user_type select")[0] );
</script>';
?>
