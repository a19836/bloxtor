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

class AdminMenuUIHandler {
	
	public static function getHeader($project_url_prefix, $project_common_url_prefix) {
		return '
<!-- Add Jquery Tap-Hold Event JS file -->
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquerytaphold/taphold.js"></script>

<!-- Add MyTree main JS and CSS files -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/jquerymytree/css/style.min.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquerymytree/js/mytree.js"></script>

<!-- Add ContextMenu main JS and CSS files -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/jquerymycontextmenu/css/style.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquerymycontextmenu/js/jquery.mycontextmenu.js"></script>

<!-- Add FileManager JS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/file_manager.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/file_manager.js"></script>

<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Icons CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS and JS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/layout.js"></script>

<!-- Add Admin Menu JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/admin/admin_menu.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/admin/admin_menu.js"></script>
';
	}
	
	public static function getContextMenus($exists_db_drivers, $get_store_programs_url, $is_user_module_installed = false) {
		$vendor_frameworks_menus = 
	'<li class="line_break" vendor_framework="laravel"></li>
	<li class="laravel_preview" vendor_framework="laravel"><a onClick="return goToNew(this, \'laravel_preview_url\', event)">Preview Laravel Project</a></li>
	<li class="laravel_terminal" vendor_framework="laravel"><a onClick="return goTo(this, \'laravel_terminal_url\', event)"> Laravel Terminal</a></li>';
		
		return '
<div id="selected_menu_properties" class="myfancypopup with_title">
	<div class="title">Properties</div>
	<p class="content"></p>
</div>

<ul id="item_context_menu" class="mycontextmenu">
	<li class="edit"><a onClick="return goTo(this, \'edit_url\', event)">Edit</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return removeItem(this, \'remove_url\')">Remove</a></li>
	<li class="line_break"></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="undefined_file_context_menu" class="mycontextmenu">
	<li class="edit_raw_file"><a onClick="return goTo(this, \'edit_raw_file_url\', event)">Edit File</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\')">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\')" allow_upper_case="1">Rename File</a></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename_name\')" allow_upper_case="1">Rename Name</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download File</a></li>
	<li class="diff_file"><a onClick="return goTo(this, \'diff_file_url\', event)">Diff File</a></li>
	<li class="line_break"></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="zip_file_context_menu" class="mycontextmenu">
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\')">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\')">Rename File</a></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename_name\')">Rename Name</a></li>
	<li class="unzip"><a onClick="return manageFile(this, \'unzip_url\', \'unzip\')">Unzip</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download File</a></li>
	<li class="line_break"></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="lib_group_context_menu" class="mycontextmenu">
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
</ul>

<ul id="main_lib_group_context_menu" class="mycontextmenu">
	<li class="manage_docbook"><a onClick="return goTo(this, \'manage_docbook_url\', event)">Manage Docbook</a></li>
</ul>

<ul id="lib_file_context_menu" class="mycontextmenu">
	<li class="view_docbook"><a onClick="return goTo(this, \'view_docbook_url\', event)">View Docbook</a></li>
	<li class="view_code"><a onClick="return goTo(this, \'view_code_url\', event)">View Code</a></li>
	<li class="line_break"></li>
	<li class="diff_file"><a onClick="return goTo(this, \'diff_file_url\', event)">Diff File</a></li>
	<li class="line_break"></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="main_dao_group_context_menu" class="mycontextmenu">
	<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\')">Add Group</a></li>
	<li class="hbnt_obj"><a onClick="return manageFile(this, \'create_dao_hibernate_model_url\', \'create_file\', triggerFileNodeAfterCreateFile)" allow_upper_case="1">Add Hibernate DAO Object</a></li>
	<li class="objt_obj"><a onClick="return manageFile(this, \'create_dao_obj_type_url\', \'create_file\', triggerFileNodeAfterCreateFile)" allow_upper_case="1">Add ObjectType DAO Object</a></li>
	<li class="line_break"></li>
	<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
	<li class="line_break"></li>
	<li class="upload"><a onClick="return goTo(this, \'upload_url\', event)">Upload Files</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
</ul>

<ul id="dao_group_context_menu" class="mycontextmenu">
	<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\')">Add Folder</a></li>
	<li class="hbnt_obj"><a onClick="return manageFile(this, \'create_dao_hibernate_model_url\', \'create_file\', triggerFileNodeAfterCreateFile)" allow_upper_case="1">Add Hibernate DAO Object</a></li>
	<li class="objt_obj"><a onClick="return manageFile(this, \'create_dao_obj_type_url\', \'create_file\', triggerFileNodeAfterCreateFile)" allow_upper_case="1">Add ObjectType DAO Object</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\')">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\')">Rename</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="upload"><a onClick="return goTo(this, \'upload_url\', event)">Upload Files</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="dao_file_context_menu" class="mycontextmenu">
	<li class="edit_raw_file"><a onClick="return goTo(this, \'edit_raw_file_url\', event)">Edit File</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\')">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\')" allow_upper_case="1">Rename File</a></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename_name\')" allow_upper_case="1">Rename Name</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download File</a></li>
	<li class="diff_file"><a onClick="return goTo(this, \'diff_file_url\', event)">Diff File</a></li>
	<li class="line_break"></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="main_db_group_context_menu" class="mycontextmenu">
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
</ul>

<ul id="db_driver_context_menu" class="mycontextmenu">
	<li class="edit"><a onClick="return goTo(this, \'edit_url\', event)">Edit</a></li>
	<li class="line_break"></li>
	<li class="execute_sql"><a onClick="return goTo(this, \'execute_sql_url\', event)">Execute SQL</a></li>
	<li class="db_dump"><a onClick="return goTo(this, \'db_dump_url\', event)">DB Dump</a></li>
	<li class="phpmyadmin"><a onClick="return goTo(this, \'phpmyadmin_url\', event)">PHP My Admin</a></li>
	<li class="line_break"></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="db_driver_tables_context_menu" class="mycontextmenu">
	<li class="add_auto_table"><a onClick="return manageDBTableAction(this, \'add_auto_table_url\', \'add_table\')">Add Table Automatically</a></li>
	<li class="add_manual_table"><a onClick="return goTo(this, \'add_manual_table_url\', event)">Add Table Manually</a></li>
	<li class="add_table_with_ai"><a onClick="return goTo(this, \'add_table_with_ai_url\', event)">Add Table with AI</a></li>
	<li class="line_break"></li>
	<li class="edit_diagram"><a onClick="return goTo(this, \'edit_diagram_url\', event)">Edit Tables Diagram</a></li>
	<li class="line_break"></li>
	<li class="create_diagram_sql"><a onClick="return goTo(this, \'create_diagram_sql_url\', event)">Create Diagram\'s SQL</a></li>
	<li class="execute_sql"><a onClick="return goTo(this, \'execute_sql_url\', event)">Execute SQL</a></li>
	<li class="db_dump"><a onClick="return goTo(this, \'db_dump_url\', event)">DB Dump</a></li>
	<li class="phpmyadmin"><a onClick="return goTo(this, \'phpmyadmin_url\', event)">PHP My Admin</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
</ul>

<ul id="db_driver_objects_context_menu" class="mycontextmenu">
	<li class="execute_sql"><a onClick="return goTo(this, \'execute_sql_url\', event)">Execute SQL</a></li>
	<li class="phpmyadmin"><a onClick="return goTo(this, \'phpmyadmin_url\', event)">PHP My Admin</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
</ul>

<ul id="db_driver_object_context_menu" class="mycontextmenu">
	<li class="edit"><a onClick="return goTo(this, \'edit_url\', event)">Edit</a></li>
</ul>

<ul id="db_driver_table_context_menu" class="mycontextmenu">
	<li class="add_attribute"><a onClick="return manageDBTableAction(this, \'add_attribute_url\', \'add_attribute\')">Add Attribute</a></li>
	<li class="line_break"></li>
	<li class="edit"><a onClick="return goTo(this, \'edit_url\', event)">Edit</a></li>
	<li class="line_break"></li>
	<li class="manage_records"><a onClick="return goTo(this, \'manage_records_url\', event)">Manage Records</a></li>
	<li class="manage_indexes"><a onClick="return goTo(this, \'manage_indexes_url\', event)">Manage Indexes</a></li>
	<li class="line_break"></li>
	<li class="execute_sql"><a onClick="return goTo(this, \'execute_sql_url\', event)">Execute SQL</a></li>
	<li class="import_data"><a onClick="return goTo(this, \'import_data_url\', event)">Import Data</a></li>
	<li class="export_data"><a onClick="return goTo(this, \'export_data_url\', event)">Export Data</a></li>
	<li class="db_dump"><a onClick="return goTo(this, \'db_dump_url\', event)">DB Dump</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageDBTableAction(this, \'remove_url\', \'remove_table\')">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageDBTableAction(this, \'rename_url\', \'rename_table\')">Rename</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
</ul>

<ul id="db_driver_table_attribute_context_menu" class="mycontextmenu">
	<li class="primary_key"><a onClick="return manageDBTableAction(this, \'set_property_url\', \'set_primary_key\')">Primary Key</a></li>
	<li class="null"><a onClick="return manageDBTableAction(this, \'set_property_url\', \'set_null\')">Null</a></li>
	<li class="type"><a href="javascript:void(0)">
		<select>
			<option disabled>-- Choose Type --</option>
		</select>
		<input placeHolder="length" />
		<span class="hidden" onClick="return manageDBTableAction(this.parentNode, \'set_property_url\', \'set_type\')"></span>
	</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageDBTableAction(this, \'remove_url\', \'remove_attribute\')">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageDBTableAction(this, \'rename_url\', \'rename_attribute\')">Rename</a></li>
	<li class="line_break"></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="db_diagram_context_menu" class="mycontextmenu">
	<li class="edit"><a onClick="return goTo(this, \'edit_url\', event)">Edit</a></li>
	<li class="line_break"></li>
	<li class="create_sql"><a onClick="return goTo(this, \'create_sql_url\', event)">Create Diagram\'s SQL</a></li>
	<li class="line_break"></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="main_ibatis_group_context_menu" class="mycontextmenu">
	<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\')">Add Group</a></li>
	<li class="line_break"></li>
	<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
	<li class="line_break"></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
</ul>

<ul id="ibatis_group_context_menu" class="mycontextmenu">
	<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\')">Add Folder</a></li>
	<li class="create_file"><a onClick="return manageFile(this, \'create_url\', \'create_file\')">Add File</a></li>
	<li class="line_break"></li>
	<li class="create_automatically"><a onClick="return goTo(this, \'create_automatically_url\', event)">Create Queries Automatically</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\')">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\')">Rename</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="upload"><a onClick="return goTo(this, \'upload_url\', event)">Upload Files</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="ibatis_group_common_context_menu" class="mycontextmenu">
	<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\')">Add Folder</a></li>
	<li class="create_file"><a onClick="return manageFile(this, \'create_url\', \'create_file\')">Add File</a></li>
	<li class="line_break"></li>
	<li class="create_automatically"><a onClick="return goTo(this, \'create_automatically_url\', event)">Create Queries Automatically</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\')">Rename</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="upload"><a onClick="return goTo(this, \'upload_url\', event)">Upload Files</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="ibatis_file_context_menu" class="mycontextmenu">
	<li class="query"><a onClick="return goTo(this, \'add_query_url\', event)">Add Query</a></li>
	<li class="parameter_map"><a onClick="return goTo(this, \'add_parameter_map_url\', event)">Add Parameter Map</a></li>
	<li class="result_map"><a onClick="return goTo(this, \'add_result_map_url\', event)">Add Result Map</a></li>
	<li class="line_break"></li>
	<li class="edit_raw_file"><a onClick="return goTo(this, \'edit_raw_file_url\', event)">Edit File</a></li>
	<li class="line_break"></li>
	<li class="manage_includes"><a onClick="return goTo(this, \'manage_includes_url\', event)">Manage Includes</a></li>
	<li class="line_break"></li>
	<li class="create_automatically"><a onClick="return goTo(this, \'create_automatically_url\', event)">Create Queries Automatically</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\')">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\')">Rename File</a></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename_name\')">Rename Name</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download File</a></li>
	<li class="diff_file"><a onClick="return goTo(this, \'diff_file_url\', event)">Diff File</a></li>
	<li class="line_break"></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="main_hibernate_group_context_menu" class="mycontextmenu">
	<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\')">Add Group</a></li>
	<li class="line_break"></li>
	<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
	<li class="line_break"></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
</ul>

<ul id="hibernate_group_context_menu" class="mycontextmenu">
	<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\')">Add Folder</a></li>
	<li class="create_file"><a onClick="return manageFile(this, \'create_url\', \'create_file\')">Add File</a></li>
	<li class="line_break"></li>
	<li class="obj"><a onClick="return goTo(this, \'add_obj_url\', event)">Add Object Manually</a></li>
	<li class="create_automatically"><a onClick="return goTo(this, \'create_automatically_url\', event)">Add Objects Automatically</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\')">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\')">Rename</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="upload"><a onClick="return goTo(this, \'upload_url\', event)">Upload Files</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="hibernate_group_common_context_menu" class="mycontextmenu">
	<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\')">Add Folder</a></li>
	<li class="create_file"><a onClick="return manageFile(this, \'create_url\', \'create_file\')">Add File</a></li>
	<li class="line_break"></li>
	<li class="obj"><a onClick="return goTo(this, \'add_obj_url\', event)">Add Object Manually</a></li>
	<li class="create_automatically"><a onClick="return goTo(this, \'create_automatically_url\', event)">Add Objects Automatically</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\')">Rename</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="upload"><a onClick="return goTo(this, \'upload_url\', event)">Upload Files</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="hibernate_file_context_menu" class="mycontextmenu">
	<li class="obj"><a onClick="return goTo(this, \'add_obj_url\', event)">Add Object</a></li>
	<li class="line_break"></li>
	<li class="edit_raw_file"><a onClick="return goTo(this, \'edit_raw_file_url\', event)">Edit File</a></li>
	<li class="line_break"></li>
	<li class="manage_includes"><a onClick="return goTo(this, \'manage_includes_url\', event)">Manage Includes</a></li>
	<li class="line_break"></li>
	<li class="create_automatically"><a onClick="return goTo(this, \'create_automatically_url\', event)">Add Objects Automatically</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\')">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\')">Rename File</a></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename_name\')">Rename Name</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download File</a></li>
	<li class="diff_file"><a onClick="return goTo(this, \'diff_file_url\', event)">Diff File</a></li>
	<li class="line_break"></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="hibernate_import_context_menu" class="mycontextmenu">
	<li class="query"><a onClick="return goTo(this, \'add_query_url\', event)">Add Query</a></li>
	<li class="relationship"><a onClick="return goTo(this, \'add_relationship_url\', event)">Add Relationship</a></li>
	<li class="parameter_map"><a onClick="return goTo(this, \'add_parameter_map_url\', event)">Add Parameter Map</a></li>
	<li class="result_map"><a onClick="return goTo(this, \'add_result_map_url\', event)">Add Result Map</a></li>
	<li class="line_break"></li>
	<li class="edit_raw_file"><a onClick="return goTo(this, \'edit_raw_file_url\', event)">Edit File</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\')">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\')">Rename</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download File</a></li>
	<li class="line_break"></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="hibernate_object_context_menu" class="mycontextmenu">
	<li class="query"><a onClick="return goTo(this, \'add_query_url\', event)">Add Query</a></li>
	<li class="relationship"><a onClick="return goTo(this, \'add_relationship_url\', event)">Add Relationship</a></li>
	<li class="parameter_map"><a onClick="return goTo(this, \'add_parameter_map_url\', event)">Add Parameter Map</a></li>
	<li class="result_map"><a onClick="return goTo(this, \'add_result_map_url\', event)">Add Result Map</a></li>
	<li class="line_break"></li>
	<li class="edit"><a onClick="return goTo(this, \'edit_url\', event)">Edit</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return removeItem(this, \'remove_url\')">Remove</a></li>
	<li class="line_break"></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="main_business_logic_group_context_menu" class="mycontextmenu">
	<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\')">Add Group</a></li>
	<li class="line_break"></li>
	<li class="create_automatically"><a onClick="return goTo(this, \'create_automatically_url\', event)">Add Services Automatically</a></li>
	<li class="line_break"></li>
	<li class="create_laravel"><a onClick="return goTo(this, \'create_laravel_url\', event)">Add Laravel Project</a></li>
	<li class="line_break"></li>
	<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
	<li class="line_break"></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
</ul>

<ul id="business_logic_group_context_menu" class="mycontextmenu">
	<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\')">Add Folder</a></li>
	<li class="create_file"><a onClick="return manageFile(this, \'create_url\', \'create_file\')" allow_upper_case="1">Add File</a></li>
	<li class="line_break"></li>
	<!--li class="service_obj"><a onClick="return goTo(this, \'add_service_obj_url\', event)">Add Service Object Manually</a></li-->
	<li class="service_obj"><a onClick="return createClassObjectOrMethodOrFunction(this, \'save_service_obj_url\', \'edit_service_obj_url\', \'service_object\', null, event)">Add Service Object Manually</a></li>
	<!--li class="service_function"><a onClick="return goTo(this, \'add_service_func_url\', event)">Add Service Function Manually</a></li-->
	<li class="service_function"><a onClick="return createClassObjectOrMethodOrFunction(this, \'save_service_func_url\', \'edit_service_func_url\', \'function\', null, event)">Add Service Function Manually</a></li>
	<li class="create_automatically"><a onClick="return goTo(this, \'create_automatically_url\', event)">Add Services Automatically</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\')">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\')">Rename</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="upload"><a onClick="return goTo(this, \'upload_url\', event)">Upload Files</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
	' . $vendor_frameworks_menus . '
</ul>

<ul id="business_logic_group_common_context_menu" class="mycontextmenu">
	<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\')">Add Folder</a></li>
	<li class="create_file"><a onClick="return manageFile(this, \'create_url\', \'create_file\')" allow_upper_case="1">Add File</a></li>
	<li class="line_break"></li>
	<!--li class="service_obj"><a onClick="return goTo(this, \'add_service_obj_url\', event)">Add Service Object Manually</a></li-->
	<li class="service_obj"><a onClick="return createClassObjectOrMethodOrFunction(this, \'save_service_obj_url\', \'edit_service_obj_url\', \'service_object\', null, event)">Add Service Object Manually</a></li>
	<!--li class="service_function"><a onClick="return goTo(this, \'add_service_func_url\', event)">Add Service Function Manually</a></li-->
	<li class="service_function"><a onClick="return createClassObjectOrMethodOrFunction(this, \'save_service_func_url\', \'edit_service_func_url\', \'function\', null, event)">Add Service Function Manually</a></li>
	<li class="create_automatically"><a onClick="return goTo(this, \'create_automatically_url\', event)">Add Services Automatically</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\')">Rename</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="upload"><a onClick="return goTo(this, \'upload_url\', event)">Upload Files</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="business_logic_file_context_menu" class="mycontextmenu">
	<!--li class="service_obj"><a onClick="return goTo(this, \'add_service_obj_url\', event)">Add Service Object Manually</a></li-->
	<li class="service_obj"><a onClick="return createClassObjectOrMethodOrFunction(this, \'save_service_obj_url\', \'edit_service_obj_url\', \'service_object\', null, event)">Add Service Object Manually</a></li>
	<!--li class="service_function"><a onClick="return goTo(this, \'add_service_func_url\', event)">Add Service Function Manually</a></li-->
	<li class="service_function"><a onClick="return createClassObjectOrMethodOrFunction(this, \'save_service_func_url\', \'edit_service_func_url\', \'function\', null, event)">Add Service Function Manually</a></li>
	<li class="toggle_all_children"><a onClick="return toggleAllChildren(this)">Toggle Hidden Objects/Functions</a></li>
	<li class="line_break"></li>
	<li class="edit_raw_file"><a onClick="return goTo(this, \'edit_raw_file_url\', event)">Edit File</a></li>
	<li class="line_break"></li>
	<li class="manage_includes"><a onClick="return goTo(this, \'manage_includes_url\', event)">Manage Includes</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\')">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\')" allow_upper_case="1">Rename File</a></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename_name\')" allow_upper_case="1">Rename Name</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download File</a></li>
	<li class="diff_file"><a onClick="return goTo(this, \'diff_file_url\', event)">Diff File</a></li>
	<li class="line_break"></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="business_logic_object_context_menu" class="mycontextmenu">
	<!--li class="service_method"><a onClick="return goTo(this, \'add_service_method_url\', event)">Add Service Method</a></li-->
	<li class="service_method"><a onClick="return createClassObjectOrMethodOrFunction(this, \'save_service_method_url\', \'edit_service_method_url\', \'service_method\', null, event)">Add Service Method</a></li>
	<li class="toggle_all_children"><a onClick="return toggleAllChildren(this)">Toggle Hidden Methods</a></li>
	<li class="line_break"></li>
	<li class="edit"><a onClick="return goTo(this, \'edit_url\', event)">Edit Visually</a></li>
	<li class="edit_raw_file"><a onClick="return goTo(this, \'edit_raw_file_url\', event)">Edit Code</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return removeBusinessLogicObject(this, \'remove_url\')">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageBusinessLogicObject(this, \'rename_url\', \'rename\')" allow_upper_case="1">Rename File</a></li>
	<!--li class="rename"><a onClick="return manageBusinessLogicObject(this, \'rename_url\', \'rename_name\')" allow_upper_case="1">Rename Name</a></li-->
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download File</a></li>
	<li class="diff_file"><a onClick="return goTo(this, \'diff_file_url\', event)">Diff File</a></li>
	<li class="line_break"></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="main_presentation_group_context_menu" class="mycontextmenu">
	<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\')">Add Folder</a></li>
	<li class="add_project"><a onClick="return goToPopup(this, \'create_project_url\', event, \'with_iframe_title edit_project_details_popup' . ($get_store_programs_url ? " big" : "") . '\', onSuccessfullAddProject)">Add New Project</a></li>
	<li class="line_break"></li>
	<li class="manage_wordpress"><a onClick="return goTo(this, \'manage_wordpress_url\', event)">Manage WordPress</a></li>
	<li class="line_break"></li>
	<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
	<li class="line_break"></li>
	<li class="upload"><a onClick="return goTo(this, \'upload_url\', event)">Upload Zipped Project</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
</ul>

<ul id="presentation_project_group_context_menu" class="mycontextmenu">
	<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\')">Add Folder</a></li>
	<li class="create_file"><a onClick="return manageFile(this, \'create_url\', \'create_file\', triggerFileNodeAfterCreateFile)">Add File</a></li>
	<li class="add_project"><a onClick="return goToPopup(this, \'create_project_url\', event, \'with_iframe_title edit_project_details_popup' . ($get_store_programs_url ? " big" : "") . '\', onSuccessfullAddProject)">Add New Project</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\')">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\', renameProject)">Rename</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="upload"><a onClick="return goTo(this, \'upload_url\', event)">Upload Zipped Project</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="presentation_project_common_context_menu" class="mycontextmenu">
	<li class="edit_config"><a onClick="return goTo(this, \'edit_config_url\', event)">Edit Config</a></li>
	<li class="line_break"></li>
	<li class="manage_wordpress"><a onClick="return goTo(this, \'manage_wordpress_url\', event)">Manage WordPress</a></li>
	<li class="line_break"></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
</ul>

<ul id="presentation_project_common_wordpress_group_context_menu" class="mycontextmenu">
	<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\', managePresentationFile)">Add Folder</a></li>
	<li class="create_file"><a onClick="return manageFile(this, \'create_url\', \'create_file\', [managePresentationFile, triggerFileNodeAfterCreateFile])">Add File</a></li>
	<li class="line_break"></li>
	<li class="manage_wordpress"><a onClick="return goTo(this, \'manage_wordpress_url\', event)">Manage WordPress</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\', managePresentationFile)">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\', managePresentationFile)">Rename</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="upload"><a onClick="return goTo(this, \'upload_url\', event)">Upload Files</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="presentation_project_context_menu" class="mycontextmenu">
	<li class="edit"><a onClick="return goToPopup(this, \'edit_url\', event, \'with_iframe_title edit_project_details_popup\', onSuccessfullEditProject)">Edit Project Details</a></li>
	<li class="edit_project_global_variables"><a onClick="return goTo(this, \'edit_project_global_variables_url\', event)">Edit Project Global Settings</a></li>
	<li class="edit_config"><a onClick="return goTo(this, \'edit_config_url\', event)">Edit Config</a></li>
	<li class="edit_init"><a onClick="return goTo(this, \'edit_init_url\', event)">Edit Init - Advanced</a></li>
	<li class="line_break"></li>
	' . ($is_user_module_installed ? '<li class="manage_users"><a onClick="return goToPopup(this, \'manage_users_url\', event, \'with_iframe_title\')">Manage Users</a></li>' : '') . '
	<li class="manage_references"><a onClick="return goToPopup(this, \'manage_references_url\', event, \'with_iframe_title\', refreshLastNodeParentChilds)">Manage References</a></li>
	<li class="manage_wordpress"><a onClick="return goTo(this, \'manage_wordpress_url\', event)">Manage WordPress</a></li>
	<li class="line_break"></li>
	<li class="install_program"><a onClick="return goTo(this, \'install_program_url\', event)">Install Program</a></li>
	<li class="line_break"></li>
	<li class="view_project"><a onClick="return openWindow(this, \'view_project_url\', \'project\')">Preview Project</a></li>
	<li class="test_project"><a onClick="return openWindow(this, \'test_project_url\', \'project\')">Test Project</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\', onSuccessfullRemoveProject)">Remove Project</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\', renameProject)">Rename</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
</ul>

<ul id="presentation_group_context_menu" class="mycontextmenu">
	<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\', managePresentationFile)">Add Folder</a></li>
	<li class="create_file"><a onClick="return manageFile(this, \'create_url\', \'create_file\', [managePresentationFile, triggerFileNodeAfterCreateFile])">Add File</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\', managePresentationFile)">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\', managePresentationFile)">Rename</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="upload"><a onClick="return goTo(this, \'upload_url\', event)">Upload Files</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
	' . $vendor_frameworks_menus . '
</ul>

<ul id="presentation_evc_group_context_menu" class="mycontextmenu">
	<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\', managePresentationFile)">Add Folder</a></li>
	<li class="create_file"><a onClick="return manageFile(this, \'create_url\', \'create_file\', [managePresentationFile, triggerFileNodeAfterCreateFile])">Add File</a></li>
	<li class="line_break"></li>
	<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
	<li class="line_break"></li>
	<li class="upload"><a onClick="return goTo(this, \'upload_url\', event)">Upload Files</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
</ul>

<ul id="presentation_main_templates_group_context_menu" class="mycontextmenu">
	<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\', managePresentationFile)">Add Folder</a></li>
	<li class="create_file"><a onClick="return manageFile(this, \'create_url\', \'create_file\', [managePresentationFile, triggerFileNodeAfterCreateFile])">Add Template</a></li>
	<li class="line_break"></li>
	<li class="install_template"><a onClick="return goTo(this, \'install_template_url\', event)">Install New Template</a></li>
	<li class="convert_template"><a onClick="return goTo(this, \'convert_template_url\', event)">Convert Url to Template</a></li>
	<li class="generate_template_with_ai"><a onClick="return goTo(this, \'generate_template_with_ai_url\', event)">Generate Template with AI</a></li>
	<li class="line_break"></li>
	<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
	<li class="line_break"></li>
	<li class="upload"><a onClick="return goTo(this, \'upload_url\', event)">Upload Files</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
</ul>

<ul id="presentation_main_pages_group_context_menu" class="mycontextmenu">
	<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\', managePresentationFile)">Add Folder</a></li>
	<li class="create_file"><a onClick="return manageFile(this, \'create_url\', \'create_file\', [managePresentationFile, triggerFileNodeAfterCreatePage])">Add Page</a></li>
	' . ($exists_db_drivers ? '<li class="line_break"></li>
	<li class="create_automatically"><a onClick="return goTo(this, \'create_automatically_url\', event)">Create UIs Automatically</a></li>
	<li class="create_uis_diagram"><a onClick="return goTo(this, \'create_uis_diagram_url\', event)">Folder UIs Diagram</a></li>' : '') . '
	<li class="line_break"></li>
	<li class="view_project"><a onClick="return openWindow(this, \'view_project_url\', \'project\')">Preview Project</a></li>
	<li class="test_project"><a onClick="return openWindow(this, \'test_project_url\', \'project\')">Test Project</a></li>
	<li class="line_break"></li>
	<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
	<li class="line_break"></li>
	<li class="upload"><a onClick="return goTo(this, \'upload_url\', event)">Upload Files</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
</ul>

<ul id="presentation_pages_group_context_menu" class="mycontextmenu">
	<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\', managePresentationFile)">Add Folder</a></li>
	<li class="create_file"><a onClick="return manageFile(this, \'create_url\', \'create_file\', [managePresentationFile, triggerFileNodeAfterCreatePage])">Add Page</a></li>
	' . ($exists_db_drivers ? '<li class="line_break"></li>
	<li class="create_automatically"><a onClick="return goTo(this, \'create_automatically_url\', event)">Create UIs Automatically</a></li>
	<li class="create_uis_diagram"><a onClick="return goTo(this, \'create_uis_diagram_url\', event)">Folder UIs Diagram</a></li>' : '') . '
	<li class="line_break"></li>
	<li class="view_project"><a onClick="return openWindow(this, \'view_project_url\', \'project\')">Preview Folder</a></li>
	<li class="test_project"><a onClick="return openWindow(this, \'test_project_url\', \'project\')">Test Folder</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\', managePresentationFile)">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\', managePresentationFile)">Rename</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="upload"><a onClick="return goTo(this, \'upload_url\', event)">Upload Files</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="presentation_main_utils_group_context_menu" class="mycontextmenu">
	<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\', managePresentationFile)">Add Folder</a></li>
	<li class="create_file"><a onClick="return manageFile(this, \'create_url\', \'create_file\', [managePresentationFile, triggerFileNodeAfterCreateFile])" allow_upper_case="1">Add File</a></li>
	<li class="line_break"></li>
	<!--li class="class_obj"><a onClick="return goTo(this, \'add_class_obj_url\', event)">Add Class</a></li-->
	<li class="class_obj"><a onClick="return createClassObjectOrMethodOrFunction(this, \'save_class_obj_url\', \'edit_class_obj_url\', \'class_object\', null, event)">Add Class</a></li>
	<!--li class="class_function"><a onClick="return goTo(this, \'add_class_func_url\', event)">Add Function</a></li-->
	<li class="class_function"><a onClick="return createClassObjectOrMethodOrFunction(this, \'save_class_func_url\', \'edit_class_func_url\', \'function\', null, event)">Add Function</a></li>
	<li class="line_break"></li>
	<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
	<li class="line_break"></li>
	<li class="upload"><a onClick="return goTo(this, \'upload_url\', event)">Upload Files</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
</ul>

<ul id="presentation_utils_group_context_menu" class="mycontextmenu">
	<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\', managePresentationFile)">Add Folder</a></li>
	<li class="create_file"><a onClick="return manageFile(this, \'create_url\', \'create_file\', [managePresentationFile, triggerFileNodeAfterCreateFile])" allow_upper_case="1">Add File</a></li>
	<li class="line_break"></li>
	<!--li class="class_obj"><a onClick="return goTo(this, \'add_class_obj_url\', event)">Add Class</a></li-->
	<li class="class_obj"><a onClick="return createClassObjectOrMethodOrFunction(this, \'save_class_obj_url\', \'edit_class_obj_url\', \'class_object\', null, event)">Add Class</a></li>
	<!--li class="class_function"><a onClick="return goTo(this, \'add_class_func_url\', event)">Add Function</a></li-->
	<li class="class_function"><a onClick="return createClassObjectOrMethodOrFunction(this, \'save_class_func_url\', \'edit_class_func_url\', \'function\', null, event)">Add Function</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\', managePresentationFile)">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\', managePresentationFile)">Rename</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="upload"><a onClick="return goTo(this, \'upload_url\', event)">Upload Files</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="presentation_file_context_menu" class="mycontextmenu">
	<li class="edit"><a onClick="return goTo(this, \'edit_url\', event)">Edit</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\', managePresentationFile)">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\', managePresentationFile)">Rename File</a></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename_name\', managePresentationFile)">Rename Name</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download File</a></li>
	<li class="diff_file"><a onClick="return goTo(this, \'diff_file_url\', event)">Diff File</a></li>
	<li class="line_break"></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="presentation_webroot_folder_context_menu" class="mycontextmenu">
	<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\', managePresentationFile)">Add Folder</a></li>
	<li class="create_file"><a onClick="return manageFile(this, \'create_url\', \'create_file\', [managePresentationFile, triggerFileNodeAfterCreateFile])">Add File</a></li>
	<li class="line_break"></li>
	<li class="create_laravel"><a onClick="return goTo(this, \'create_laravel_url\', event)">Add Laravel Project</a></li>
	<li class="line_break"></li>
	<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
	<li class="line_break"></li>
	<li class="upload"><a onClick="return goTo(this, \'upload_url\', event)">Upload Files</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
</ul>

<ul id="presentation_webroot_file_context_menu" class="mycontextmenu">
	<li class="edit_raw_file"><a onClick="return goTo(this, \'edit_raw_file_url\', event)">Edit File</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\', managePresentationFile)">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\', managePresentationFile)">Rename File</a></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename_name\', managePresentationFile)">Rename Name</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download File</a></li>
	<li class="open_file"><a onClick="return openWindow(this, \'open_url\', \'file\')">Open File</a></li>
	<li class="diff_file"><a onClick="return goTo(this, \'diff_file_url\', event)">Diff File</a></li>
	<li class="line_break"></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="presentation_page_file_context_menu" class="mycontextmenu">
	<li class="edit"><a onClick="return goTo(this, \'edit_url\', event)">Edit Visually</a></li>
	<li class="edit_raw_file"><a onClick="return goTo(this, \'edit_raw_file_url\', event)">Edit Code</a></li>
	<li class="line_break"></li>
	<li class="view_project"><a onClick="return openWindow(this, \'view_project_url\', \'project\')">Preview Page</a></li>
	<li class="test_project"><a onClick="return openWindow(this, \'test_project_url\', \'project\')">Test Page</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\', managePresentationFile)">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\', managePresentationFile)">Rename File</a></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename_name\', managePresentationFile)">Rename Name</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download File</a></li>
	<li class="diff_file"><a onClick="return goTo(this, \'diff_file_url\', event)">Diff File</a></li>
	<li class="line_break"></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="presentation_view_file_context_menu" class="mycontextmenu">
	<li class="edit"><a onClick="return goTo(this, \'edit_url\', event)">Edit Visually</a></li>
	<li class="edit_raw_file"><a onClick="return goTo(this, \'edit_raw_file_url\', event)">Edit Code</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\', managePresentationFile)">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\', managePresentationFile)">Rename File</a></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename_name\', managePresentationFile)">Rename Name</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download File</a></li>
	<li class="diff_file"><a onClick="return goTo(this, \'diff_file_url\', event)">Diff File</a></li>
	<li class="line_break"></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="presentation_template_file_context_menu" class="mycontextmenu">
	<li class="edit"><a onClick="return goTo(this, \'edit_url\', event)">Edit Visually</a></li>
	<li class="edit_raw_file"><a onClick="return goTo(this, \'edit_raw_file_url\', event)">Edit Code</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\', managePresentationFile)">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\', managePresentationFile)">Rename File</a></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename_name\', managePresentationFile)">Rename Name</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download File</a></li>
	<li class="diff_file"><a onClick="return goTo(this, \'diff_file_url\', event)">Diff File</a></li>
	<li class="line_break"></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="presentation_block_file_context_menu" class="mycontextmenu">
	<li class="edit"><a onClick="return goTo(this, \'edit_url\', event)">Edit Visually</a></li>
	<li class="edit_raw_file"><a onClick="return goTo(this, \'edit_raw_file_url\', event)">Edit Code</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\', managePresentationFile)">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\', managePresentationFile)">Rename File</a></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename_name\', managePresentationFile)">Rename Name</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download File</a></li>
	<li class="diff_file"><a onClick="return goTo(this, \'diff_file_url\', event)">Diff File</a></li>
	<li class="line_break"></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="presentation_util_file_context_menu" class="mycontextmenu">
	<!--li class="class_obj"><a onClick="return goTo(this, \'add_class_obj_url\', event)">Add Class</a></li-->
	<li class="class_obj"><a onClick="return createClassObjectOrMethodOrFunction(this, \'save_class_obj_url\', \'edit_class_obj_url\', \'class_object\', null, event)">Add Class</a></li>
	<!--li class="class_function"><a onClick="return goTo(this, \'add_class_func_url\', event)">Add Function</a></li-->
	<li class="class_function"><a onClick="return createClassObjectOrMethodOrFunction(this, \'save_class_func_url\', \'edit_class_func_url\', \'function\', null, event)">Add Function</a></li>
	<li class="toggle_all_children"><a onClick="return toggleAllChildren(this)">Toggle Hidden Classes/Functions</a></li>
	<li class="line_break"></li>
	<li class="edit_raw_file"><a onClick="return goTo(this, \'edit_raw_file_url\', event)">Edit</a></li>
	<li class="line_break"></li>
	<li class="manage_includes"><a onClick="return goTo(this, \'manage_includes_url\', event)">Manage Includes</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\', managePresentationFile)">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\', managePresentationFile)" allow_upper_case="1">Rename File</a></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename_name\', managePresentationFile)" allow_upper_case="1">Rename Name</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download File</a></li>
	<li class="diff_file"><a onClick="return goTo(this, \'diff_file_url\', event)">Diff File</a></li>
	<li class="line_break"></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="presentation_util_object_context_menu" class="mycontextmenu">
	<!--li class="class_method"><a onClick="return goTo(this, \'add_class_method_url\', event)">Add Class Method</a></li-->
	<li class="class_method"><a onClick="return createClassObjectOrMethodOrFunction(this, \'save_class_method_url\', \'edit_class_method_url\', \'class_method\', null, event)" static="1">Add Class Method</a></li>
	<li class="toggle_all_children"><a onClick="return toggleAllChildren(this)">Toggle Hidden Methods</a></li>
	<li class="line_break"></li>
	<li class="edit"><a onClick="return goTo(this, \'edit_url\', event)">Edit Visually</a></li>
	<!--li class="edit_raw_file"><a onClick="return goTo(this, \'edit_raw_file_url\', event)">Edit Code</a></li-->
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\')">Remove</a></li>
	<li class="line_break"></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="presentation_cache_file_context_menu" class="mycontextmenu">
	<li class="edit_raw_file"><a onClick="return goTo(this, \'edit_raw_file_url\', event)">Edit</a></li>
	<li class="line_break"></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download File</a></li>
	<li class="line_break"></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="presentation_cache_group_context_menu" class="mycontextmenu">
	<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\')">Add Group</a></li>
	<li class="create_file"><a onClick="return manageFile(this, \'create_url\', \'create_file\', triggerFileNodeAfterCreateFile)">Add File</a></li>
	<li class="line_break"></li>
	<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
	<li class="line_break"></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="cms_module_context_menu" class="mycontextmenu">
	<li class="manage_modules"><a onClick="return goTo(this, \'manage_modules_url\', event)">Manage Modules</a></li>
	<li class="line_break"></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
</ul>

<ul id="main_vendor_group_context_menu" class="mycontextmenu">
	<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\')">Add Group</a></li>
	<li class="create_file"><a onClick="return manageFile(this, \'create_url\', \'create_file\', triggerFileNodeAfterCreateFile)">Add File</a></li>
	<li class="line_break"></li>
	<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
	<li class="line_break"></li>
	<li class="upload"><a onClick="return goTo(this, \'upload_url\', event)">Upload Files</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
</ul>

<ul id="vendor_group_context_menu" class="mycontextmenu">
	<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\')">Add Folder</a></li>
	<li class="create_file"><a onClick="return manageFile(this, \'create_url\', \'create_file\', triggerFileNodeAfterCreateFile)">Add File</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\')">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\')">Rename</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="upload"><a onClick="return goTo(this, \'upload_url\', event)">Upload Files</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="vendor_file_context_menu" class="mycontextmenu">
	<li class="edit_raw_file"><a onClick="return goTo(this, \'edit_raw_file_url\', event)">Edit File</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\')">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\')">Rename File</a></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename_name\')">Rename Name</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download File</a></li>
	<li class="diff_file"><a onClick="return goTo(this, \'diff_file_url\', event)">Diff File</a></li>
	<li class="line_break"></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="main_test_unit_group_context_menu" class="mycontextmenu">
	<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\')">Add Group</a></li>
	<li class="test_unit_obj"><a onClick="return manageFile(this, \'create_test_unit_obj_url\', \'create_file\', triggerFileNodeAfterCreateFile)">Add Test-Unit</a></li>
	<li class="line_break"></li>
	<li class="manage_test_units"><a onClick="return goTo(this, \'manage_test_units_url\', event)">Manage Test-Units</a></li>
	<li class="line_break"></li>
	<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
	<li class="line_break"></li>
	<li class="upload"><a onClick="return goTo(this, \'upload_url\', event)">Upload Files</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
</ul>

<ul id="test_unit_group_context_menu" class="mycontextmenu">
	<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\')">Add Folder</a></li>
	<li class="test_unit_obj"><a onClick="return manageFile(this, \'create_test_unit_obj_url\', \'create_file\', triggerFileNodeAfterCreateFile)">Add Test-Unit</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\')">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\')">Rename</a></li>
	<li class="zip"><a onClick="return manageFile(this, \'zip_url\', \'zip\')">Zip</a></li>
	<li class="upload"><a onClick="return goTo(this, \'upload_url\', event)">Upload Files</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="test_unit_obj_context_menu" class="mycontextmenu">
	<li class="edit"><a onClick="return goTo(this, \'edit_url\', event)">Edit Visually</a></li>
	<li class="edit_raw_file"><a onClick="return goTo(this, \'edit_raw_file_url\', event)">Edit Code</a></li>
	<li class="line_break"></li>
	<li class="cut"><a onClick="return cutFile(this)">Cut</a></li>
	<li class="copy"><a onClick="return copyFile(this)">Copy</a></li>
	<li class="line_break"></li>
	<li class="remove"><a onClick="return manageFile(this, \'remove_url\', \'remove\')">Remove</a></li>
	<li class="line_break"></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename\')" allow_upper_case="1">Rename File</a></li>
	<li class="rename"><a onClick="return manageFile(this, \'rename_url\', \'rename_name\')" allow_upper_case="1">Rename Name</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download File</a></li>
	<li class="diff_file"><a onClick="return goTo(this, \'diff_file_url\', event)">Diff File</a></li>
	<li class="line_break"></li>
	<li class="properties"><a onClick="return showProperties(this)">Properties</a></li>
</ul>

<ul id="main_other_group_context_menu" class="mycontextmenu">
	<li class="create_folder"><a onClick="return manageFile(this, \'create_url\', \'create_folder\')">Add Group</a></li>
	<li class="create_file"><a onClick="return manageFile(this, \'create_url\', \'create_file\', triggerFileNodeAfterCreateFile)">Add File</a></li>
	<li class="line_break"></li>
	<li class="paste"><a onClick="return manageFile(this, \'paste_url\', \'paste\')">Paste</a></li>
	<li class="line_break"></li>
	<li class="upload"><a onClick="return goTo(this, \'upload_url\', event)">Upload Files</a></li>
	<li class="download"><a onClick="return goToNew(this, \'download_url\', event)">Download Folder</a></li>
	<li class="line_break"></li>
	<li class="refresh"><a onClick="return refresh(this)">Refresh</a></li>
</ul>';
	}
	
	public static function getInlineIconsByContextMenus() {
		return array(
			"item_context_menu" => array("edit", "remove"),
			"undefined_file_context_menu" => array("edit_raw_file", "remove"),
			"zip_file_context_menu" => array("unzip", "remove"),
			"main_dao_group_context_menu" => array("create_folder"),
			"dao_group_context_menu" => array("create_folder", "remove"),
			"dao_file_context_menu" => array("edit_raw_file", "remove"),
			"main_db_group_context_menu" => array(),
			"db_driver_context_menu" => array("edit"),
			"db_driver_tables_context_menu" => array("add_table", "edit_diagram"),
			"db_driver_table_context_menu" => array("edit", "manage_records"),
			"db_diagram_context_menu" => array("edit"),
			"main_ibatis_group_context_menu" => array("create_folder"),
			"ibatis_group_context_menu" => array("create_folder", "create_file", "remove"),
			"ibatis_group_common_context_menu" => array("create_folder", "create_file", "remove"),
			"ibatis_file_context_menu" => array("edit_raw_file", "remove"),
			"main_hibernate_group_context_menu" => array("create_folder"),
			"hibernate_group_context_menu" => array("create_folder", "create_file", "remove"),
			"hibernate_group_common_context_menu" => array("create_folder", "create_file", "remove"),
			"hibernate_file_context_menu" => array("edit_raw_file", "remove"),
			"hibernate_import_context_menu" => array("edit_raw_file", "remove"),
			"hibernate_object_context_menu" => array("edit", "remove"),
			"main_business_logic_group_context_menu" => array("create_folder"),
			"business_logic_group_context_menu" => array("create_folder", "create_file", "remove"),
			"business_logic_group_common_context_menu" => array("create_folder", "create_file", "remove"),
			"business_logic_file_context_menu" => array("edit_raw_file", "remove"),
			"business_logic_object_context_menu" => array("edit", "service_method", "remove"),
			"main_presentation_group_context_menu" => array("create_folder"),
			"presentation_project_group_context_menu" => array("create_folder", "create_file", "remove"),
			"presentation_project_common_context_menu" => array(),
			"presentation_project_common_wordpress_group_context_menu" => array("create_folder", "create_file", "remove"),
			"presentation_project_context_menu" => array("remove"),
			"presentation_group_context_menu" => array("create_folder", "create_file", "remove"),
			"presentation_evc_group_context_menu" => array("create_folder", "create_file", "paste"),
			"presentation_main_templates_group_context_menu" => array("create_folder", "create_file", "install_template", "convert_template", "generate_template_with_ai", "paste"),
			"presentation_main_pages_group_context_menu" => array("create_folder", "create_file", "create_automatically", "create_uis_diagram", "view_project", "paste"),
			"presentation_pages_group_context_menu" => array("view_project", "create_folder", "create_file", "remove"),
			"presentation_file_context_menu" => array("edit", "remove"),
			"presentation_page_file_context_menu" => array("view_project", "edit", "remove"),
			"presentation_block_file_context_menu" => array("edit", "remove"),
			"presentation_main_utils_group_context_menu" => array("create_folder", "create_file", "class_obj", "class_function", "paste"),
			"presentation_utils_group_context_menu" => array("create_folder", "create_file", "class_obj", "class_function", "paste"),
			"presentation_util_file_context_menu" => array("edit_raw_file", "remove"),
			"presentation_util_object_context_menu" => array("edit", "remove"),
			"presentation_cache_file_context_menu" => array("edit_raw_file"),
			"presentation_cache_group_context_menu" => array("create_folder", "create_file"),
			"cms_module_context_menu" => array(),
			"main_vendor_group_context_menu" => array("create_folder", "create_file"),
			"vendor_group_context_menu" => array("create_folder", "create_file", "remove"),
			"vendor_file_context_menu" => array("edit_raw_file", "remove"),
			"main_test_unit_group_context_menu" => array("create_folder", "test_unit_obj"),
			"test_unit_group_context_menu" => array("create_folder", "test_unit_obj", "remove"),
			"test_unit_obj_context_menu" => array("edit", "remove"),
			"main_other_group_context_menu" => array("create_folder", "create_file"),
		);
	}
	
	public static function getLayersGroup($layer_type, $layers_group, &$main_layers_properties, $project_url_prefix, $filter_by_layout = false, $filter_by_layout_permission = false) {
		$html = "";
		
		if (!empty($layers_group)) {
			$html .= '
			<li class="' . strtolower($layer_type) . ' jstree-open" data-jstree=\'{"icon":"main_node main_node_' . strtolower($layer_type) . '"}\'>
				<label>' . self::getLayerLabel($layer_type) . '</label>
				<ul>';

		
			foreach ($layers_group as $layer_name => $layer)
				$html .= self::getLayer($layer_name, $layer, $main_layers_properties, $project_url_prefix, $filter_by_layout, $filter_by_layout_permission);

			$html .= '
				</ul>
			</li>';
		}
		
		return $html;
	}

	public static function getLayer($layer_name, $layer, &$main_layers_properties, $project_url_prefix, $filter_by_layout = false, $filter_by_layout_permission = false, $selected_db_driver = false) {
		$html = "";
		
		if ($layer_name != "properties") {
			self::updateMainLayersProperties($layer_name, $layer, $main_layers_properties, $project_url_prefix, $filter_by_layout, $filter_by_layout_permission, $selected_db_driver);
			//echo "<pre>";print_r($main_layers_properties);die();
			
			$properties = isset($main_layers_properties[$layer_name]) ? $main_layers_properties[$layer_name] : null;
			//if ($layer_name=="Condo"){echo "<pre>";print_r($properties);die();}
			$item_path = isset($properties["path"]) ? $properties["path"] : null;
			$item_type = isset($properties["item_type"]) ? $properties["item_type"] : null;
			$layer_type = strtolower($item_type);
			$class = ($item_type == "db_driver" ? "" : "main_node_") . $layer_type;
			
			$html .= self::getNode($layer_name, $layer, $properties, $item_path, $class);
		}
		return $html;
	}

	public static function updateMainLayersProperties($layer_name, $layer, &$main_layers_properties, $project_url_prefix, $filter_by_layout = false, $filter_by_layout_permission = false, $selected_db_driver = false) {
		if ($layer_name != "properties" && $layer_name != "aliases") {
			$properties = isset($layer["properties"]) ? $layer["properties"] : null;
			$item_type = isset($properties["item_type"]) ? $properties["item_type"] : null;
			$bean_name = isset($properties["bean_name"]) ? $properties["bean_name"] : null;
			$bean_file_name = isset($properties["bean_file_name"]) ? $properties["bean_file_name"] : null;
			$has_automatic_ui = isset($properties["automatic_ui"]) ? $properties["automatic_ui"] : null; //only for presentation and business logic layers
			
			$bean_name = $item_type == "dao" ? "dao" : ($item_type == "vendor" ? "vendor" : ($item_type == "other" ? "other" : ($item_type == "lib" ? "lib" : $bean_name)));
			
			$filter_by_layout_permission = $filter_by_layout_permission ? $filter_by_layout_permission : "belong";
			$filter_by_layout_url_query_with_permission = $filter_by_layout ? "&filter_by_layout=$filter_by_layout&filter_by_layout_permission=$filter_by_layout_permission" : "";
			$filter_by_layout_url_query = $filter_by_layout ? "&filter_by_layout=$filter_by_layout" : "";
			$filter_by_layout_url_query .= $selected_db_driver ? "&selected_db_driver=$selected_db_driver" : "";
			
			$li_props = array();
			
			$li_props["folder"]["get_sub_files_url"] = $project_url_prefix . "admin/get_sub_files?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query_with_permission&path=#path#&item_type=$item_type&vendor_framework=#vendor_framework#";
			$li_props["folder"]["attributes"]["rename_url"] = $project_url_prefix . "admin/manage_file?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query_with_permission&path=#path#&action=#action#&item_type=$item_type&extra=#extra#";
			$li_props["folder"]["attributes"]["remove_url"] = $project_url_prefix . "admin/manage_file?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query_with_permission&path=#path#&action=#action#&item_type=$item_type";
			$li_props["folder"]["attributes"]["create_url"] = $li_props["folder"]["attributes"]["rename_url"];
			$li_props["folder"]["attributes"]["upload_url"] = $project_url_prefix . "admin/upload_file?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query_with_permission&path=#path#&item_type=$item_type";
			$li_props["folder"]["attributes"]["download_url"] = $project_url_prefix . "admin/download_file?bean_name=$bean_name&bean_file_name=$bean_file_name&path=#path#&item_type=$item_type";
			$li_props["folder"]["attributes"]["zip_url"] = $li_props["folder"]["attributes"]["rename_url"];
			$li_props["folder"]["attributes"]["copy_url"] = "[$bean_name,$bean_file_name,#path#,$item_type]";
			$li_props["folder"]["attributes"]["cut_url"] = $li_props["folder"]["attributes"]["copy_url"];
			$li_props["folder"]["attributes"]["paste_url"] = $li_props["folder"]["attributes"]["rename_url"];
			
			$li_props["file"]["attributes"]["onClick"] = 'return goTo(this, \'edit_raw_file_url\', event)';
			$li_props["file"]["attributes"]["rename_url"] = $li_props["folder"]["attributes"]["rename_url"];
			$li_props["file"]["attributes"]["remove_url"] = $li_props["folder"]["attributes"]["remove_url"];
			$li_props["file"]["attributes"]["create_url"] = $li_props["folder"]["attributes"]["create_url"];
			$li_props["file"]["attributes"]["edit_raw_file_url"] = $project_url_prefix . "phpframework/admin/edit_raw_file?bean_name=$bean_name&bean_file_name=$bean_file_name&item_type=$item_type&path=#path#";
			$li_props["file"]["attributes"]["copy_url"] = $li_props["folder"]["attributes"]["copy_url"];
			$li_props["file"]["attributes"]["cut_url"] = $li_props["file"]["attributes"]["copy_url"];
			$li_props["file"]["attributes"]["download_url"] = $li_props["folder"]["attributes"]["download_url"];
			$li_props["file"]["attributes"]["zip_url"] = $li_props["folder"]["attributes"]["zip_url"];
			$li_props["file"]["attributes"]["diff_file_url"] = $project_url_prefix . "diff/?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&item_type=$item_type";
			
			$li_props["undefined_file"] = $li_props["file"];
			
			$li_props["zip_file"] = $li_props["file"];
			$li_props["zip_file"]["attributes"]["unzip_url"] = $li_props["file"]["attributes"]["rename_url"];
			
			$li_props["import"]["attributes"]["onClick"] = 'return goTo(this, \'edit_raw_file_url\', event)';
			$li_props["import"]["attributes"]["rename_url"] = $li_props["file"]["attributes"]["rename_url"];
			$li_props["import"]["attributes"]["remove_url"] = $li_props["file"]["attributes"]["remove_url"];
			$li_props["import"]["attributes"]["edit_raw_file_url"] = $li_props["file"]["attributes"]["edit_raw_file_url"];
			$li_props["import"]["attributes"]["copy_url"] = $li_props["file"]["attributes"]["copy_url"];
			$li_props["import"]["attributes"]["cut_url"] = $li_props["import"]["attributes"]["copy_url"];
			$li_props["import"]["attributes"]["download_url"] = $li_props["folder"]["attributes"]["download_url"];
			$li_props["import"]["attributes"]["zip_url"] = $li_props["folder"]["attributes"]["zip_url"];
			
			$li_props[$item_type]["attributes"]["create_url"] = $li_props["folder"]["attributes"]["create_url"];
			$li_props[$item_type]["attributes"]["upload_url"] = $li_props["folder"]["attributes"]["upload_url"];
			$li_props[$item_type]["attributes"]["paste_url"] = $li_props["folder"]["attributes"]["paste_url"];
			$li_props[$item_type]["attributes"]["download_url"] = $li_props["folder"]["attributes"]["download_url"];
			$li_props[$item_type]["attributes"]["zip_url"] = $li_props["folder"]["attributes"]["zip_url"];
			$li_props[$item_type]["get_sub_files_url"] = $li_props["folder"]["get_sub_files_url"];
			
			$li_props["cms_module"] = $li_props["folder"];
			$li_props["cms_module"]["get_sub_files_url"] .= "&folder_type=module";
			$li_props["cms_module"]["attributes"]["manage_modules_url"] = $project_url_prefix . "phpframework/admin/manage_modules?bean_name=$bean_name&bean_file_name=$bean_file_name";
			
			$li_props["reserved_file"]["attributes"]["onClick"] = 'return goTo(this, \'view_url\', event)';
			$li_props["reserved_file"]["attributes"]["view_url"] = $project_url_prefix . "phpframework/admin/view_file?bean_name=$bean_name&bean_file_name=$bean_file_name&item_type=$item_type&path=#path#";
			$li_props["reserved_file"]["attributes"]["download_url"] = $li_props["folder"]["attributes"]["download_url"];
			$li_props["reserved_file"]["attributes"]["zip_url"] = $li_props["folder"]["attributes"]["zip_url"];
			$li_props["reserved_file"]["attributes"]["diff_file_url"] = $li_props["file"]["attributes"]["diff_file_url"];
			
			if ($item_type == "db" || $item_type == "db_driver") {
				$layer_bean_folder_name = isset($layer["properties"]["layer_bean_folder_name"]) ? $layer["properties"]["layer_bean_folder_name"] : null;
				
				$li_props["db_tables"]["get_sub_files_url"] = $project_url_prefix . "db/get_db_data?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#"; //to be called inside of each DB
				$li_props["db_tables"]["attributes"] = array(
					"edit_url" => $project_url_prefix . "db/set_db_settings?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#",
					"add_auto_table_url" => $project_url_prefix . "db/manage_table_action?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#&action=#action#&extra=#extra#",
					"add_manual_table_url" => $project_url_prefix . "db/edit_table?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#&on_success_js_func=refreshAndShowLastNodeChilds",
					"add_table_with_ai_url" => $project_url_prefix . "db/generate_table_with_ai?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#&on_success_js_func=refreshAndShowLastNodeChilds",
					"db_dump_url" => $project_url_prefix . "db/db_dump?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#",
					"execute_sql_url" => $project_url_prefix . "db/execute_sql?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#",
					"edit_diagram_url" => $project_url_prefix . "db/diagram?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#",
					"create_diagram_sql_url" => $project_url_prefix . "db/create_diagram_sql?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#",
					"phpmyadmin_url" => $project_url_prefix . "db/phpmyadmin?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#",
					"onClick" => 'return goTo(this, \'edit_diagram_url\', event)'
				);
				
				$li_props["db_views"]["get_sub_files_url"] = $li_props["db_tables"]["get_sub_files_url"] . "&item_type=#item_type#";
				$li_props["db_views"]["attributes"] = array(
					"execute_sql_url" => $project_url_prefix . "db/execute_sql?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#",
					"phpmyadmin_url" => $project_url_prefix . "db/phpmyadmin?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#",
				);
				$li_props["db_procedures"] = $li_props["db_views"];
				$li_props["db_functions"] = $li_props["db_views"];
				$li_props["db_events"] = $li_props["db_views"];
				$li_props["db_triggers"] = $li_props["db_views"];
				
				$li_props["db_driver"] = $li_props["db_tables"];
				$li_props["db_driver"]["attributes"]["onClick"] = 'return goTo(this, \'edit_url\', event)';
				
				/*$li_props["db_diagram"]["attributes"] = array(
					"onClick" => 'return goTo(this, \'edit_url\', event)',
					"edit_url" => $project_url_prefix . "db/diagram?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#",
					"create_sql_url" => $project_url_prefix . "db/create_diagram_sql?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#",
				);*/
				
				$li_props["table"]["get_sub_files_url"] = $project_url_prefix . "db/get_db_data?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#&table=#table#";
				$li_props["table"]["attributes"]["onClick"] = 'return goTo(this, \'edit_url\', event)';
				$li_props["table"]["attributes"]["edit_url"] = $project_url_prefix . "db/edit_table?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#&table=#table#";
				$li_props["table"]["attributes"]["rename_url"] = $project_url_prefix . "db/manage_table_action?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#&action=#action#&table=#table#&extra=#extra#";
				$li_props["table"]["attributes"]["remove_url"] = $project_url_prefix . "db/manage_table_action?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#&action=#action#&table=#table#";
				$li_props["table"]["attributes"]["add_attribute_url"] = $project_url_prefix . "db/manage_table_action?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#&action=#action#&table=#table#&extra=#extra#";
				$li_props["table"]["attributes"]["add_fk_attribute_url"] = $project_url_prefix . "db/manage_table_action?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#&action=#action#&table=#table#&fk_table=#fk_table#&fk_attribute=#fk_attribute#&previous_attribute=#previous_attribute#&next_attribute=#next_attribute#&attribute_index=#attribute_index#";
				$li_props["table"]["attributes"]["import_data_url"] = $project_url_prefix . "db/import_table_data?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#&table=#table#";
				$li_props["table"]["attributes"]["export_data_url"] = $project_url_prefix . "db/export_table_data?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#&table=#table#";
				$li_props["table"]["attributes"]["db_dump_url"] = $project_url_prefix . "db/db_dump?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#&table=#table#";
				$li_props["table"]["attributes"]["execute_sql_url"] = $project_url_prefix . "db/execute_sql?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#&table=#table#";
				$li_props["table"]["attributes"]["manage_records_url"] = $project_url_prefix . "db/manage_records?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#&table=#table#";
				$li_props["table"]["attributes"]["manage_indexes_url"] = $project_url_prefix . "db/manage_indexes?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#&table=#table#";
				$li_props["table"]["attributes"]["bean_name"] = "#bean_name#";
				$li_props["table"]["attributes"]["bean_file_name"] = "#bean_file_name#";
				$li_props["table"]["attributes"]["table_name"] = "#table#";
				
				$li_props["attribute"]["attributes"]["onClick"] = 'return manageDBTableAction(this, \'rename_url\', \'rename_attribute\')';
				$li_props["attribute"]["attributes"]["remove_url"] = $project_url_prefix . "db/manage_table_action?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#&action=#action#&table=#table#&attribute=#attribute#";
				$li_props["attribute"]["attributes"]["rename_url"] = $project_url_prefix . "db/manage_table_action?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#&action=#action#&table=#table#&attribute=#attribute#&extra=#extra#";
				$li_props["attribute"]["attributes"]["sort_url"] = $project_url_prefix . "db/manage_table_action?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#&action=#action#&table=#table#&attribute=#attribute#&previous_attribute=#previous_attribute#&next_attribute=#next_attribute#&attribute_index=#attribute_index#";
				$li_props["attribute"]["attributes"]["set_property_url"] = $project_url_prefix . "db/manage_table_action?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#&action=#action#&table=#table#&attribute=#attribute#&properties=#properties#";
				$li_props["attribute"]["attributes"]["execute_sql_url"] = $project_url_prefix . "db/execute_sql?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#";
				$li_props["attribute"]["attributes"]["table_name"] = "#table#";
				$li_props["attribute"]["attributes"]["attribute_name"] = "#attribute#";
				
				$li_props["db_view"]["attributes"]["onClick"] = 'return goTo(this, \'edit_url\', event)';
				$li_props["db_view"]["attributes"]["edit_url"] = $project_url_prefix . "db/execute_sql?layer_bean_folder_name=$layer_bean_folder_name&bean_name=#bean_name#&bean_file_name=#bean_file_name#&item_type=#item_type#&object=#object#";
				
				$li_props["db_procedure"] = $li_props["db_view"];
				$li_props["db_function"] = $li_props["db_view"];
				$li_props["db_event"] = $li_props["db_view"];
				$li_props["db_trigger"] = $li_props["db_view"];
			}
			else if ($item_type == "lib") {
				$li_props[$item_type]["attributes"]["manage_docbook_url"] = $project_url_prefix . "docbook/";
				
				$li_props["file"]["attributes"]["onClick"] = 'return goTo(this, \'view_docbook_url\', event)';
				$li_props["file"]["attributes"]["view_docbook_url"] = $project_url_prefix . "docbook/file_docbook?path=lib/#path#";
				$li_props["file"]["attributes"]["view_code_url"] = $project_url_prefix . "docbook/file_code?path=lib/#path#";
			}
			else if ($item_type == "vendor") {
				$li_props["dao"] = array();
				$li_props["dao"]["get_sub_files_url"] = $project_url_prefix . "admin/get_sub_files?bean_name=dao&bean_file_name=&path=#path#&item_type=dao";
				$li_props["dao"]["attributes"]["rename_url"] = $project_url_prefix . "admin/manage_file?bean_name=dao&bean_file_name=&path=#path#&action=#action#&item_type=dao&extra=#extra#";
				$li_props["dao"]["attributes"]["remove_url"] = $project_url_prefix . "admin/manage_file?bean_name=dao&bean_file_name=&path=#path#&action=#action#&item_type=dao";
				$li_props["dao"]["attributes"]["create_url"] = $li_props["dao"]["attributes"]["rename_url"];
				$li_props["dao"]["attributes"]["upload_url"] = $project_url_prefix . "admin/upload_file?bean_name=dao&bean_file_name=&path=#path#&item_type=dao";
				$li_props["dao"]["attributes"]["download_url"] = $project_url_prefix . "admin/download_file?bean_name=dao&bean_file_name=&path=#path#&item_type=dao";
				$li_props["dao"]["attributes"]["zip_url"] = $li_props["dao"]["attributes"]["rename_url"];
				$li_props["dao"]["attributes"]["copy_url"] = "[dao,,#path#,dao]";
				$li_props["dao"]["attributes"]["cut_url"] = $li_props["dao"]["attributes"]["copy_url"];
				$li_props["dao"]["attributes"]["paste_url"] = $li_props["dao"]["attributes"]["rename_url"];
				$li_props["dao"]["attributes"]["create_dao_hibernate_model_url"] = $project_url_prefix . "phpframework/dao/create_file?type=hibernatemodel&path=#path#&file_name=#extra#";
				$li_props["dao"]["attributes"]["create_dao_obj_type_url"] = $project_url_prefix . "phpframework/dao/create_file?type=objtype&path=#path#&file_name=#extra#";
				
				$li_props["code_workflow_editor"] = $li_props["folder"];
				$li_props["code_workflow_editor_task"] = $li_props["folder"];
				$li_props["layout_ui_editor"] = $li_props["folder"];
				$li_props["layout_ui_editor_widget"] = $li_props["folder"];
				
				$li_props["test_unit"] = array();
				$li_props["test_unit"]["get_sub_files_url"] = $project_url_prefix . "admin/get_sub_files?bean_name=test_unit&bean_file_name=&path=#path#&item_type=test_unit";
				$li_props["test_unit"]["attributes"]["rename_url"] = $project_url_prefix . "admin/manage_file?bean_name=test_unit&bean_file_name=&path=#path#&action=#action#&item_type=test_unit&extra=#extra#";
				$li_props["test_unit"]["attributes"]["remove_url"] = $project_url_prefix . "admin/manage_file?bean_name=test_unit&bean_file_name=&path=#path#&action=#action#&item_type=test_unit";
				$li_props["test_unit"]["attributes"]["create_url"] = $li_props["test_unit"]["attributes"]["rename_url"];
				$li_props["test_unit"]["attributes"]["upload_url"] = $project_url_prefix . "admin/upload_file?bean_name=test_unit&bean_file_name=&path=#path#&item_type=test_unit";
				$li_props["test_unit"]["attributes"]["download_url"] = $project_url_prefix . "admin/download_file?bean_name=test_unit&bean_file_name=&path=#path#&item_type=test_unit";
				$li_props["test_unit"]["attributes"]["zip_url"] = $li_props["test_unit"]["attributes"]["rename_url"];
				$li_props["test_unit"]["attributes"]["copy_url"] = "[test_unit,,#path#,test_unit]";
				$li_props["test_unit"]["attributes"]["cut_url"] = $li_props["test_unit"]["attributes"]["copy_url"];
				$li_props["test_unit"]["attributes"]["paste_url"] = $li_props["test_unit"]["attributes"]["rename_url"];
				$li_props["test_unit"]["attributes"]["create_test_unit_obj_url"] = $project_url_prefix . "phpframework/testunit/create_test?path=#path#&file_name=#extra#";
				$li_props["test_unit"]["attributes"]["manage_test_units_url"] = $project_url_prefix . "phpframework/testunit/";
			}
			else if ($item_type == "dao") {
				$li_props["folder"]["attributes"]["create_dao_hibernate_model_url"] = $project_url_prefix . "phpframework/dao/create_file?type=hibernatemodel&path=#path#&file_name=#extra#";
				$li_props["folder"]["attributes"]["create_dao_obj_type_url"] = $project_url_prefix . "phpframework/dao/create_file?type=objtype&path=#path#&file_name=#extra#";
				
				$li_props[$item_type]["attributes"]["create_dao_hibernate_model_url"] = $li_props["folder"]["attributes"]["create_dao_hibernate_model_url"];
				$li_props[$item_type]["attributes"]["create_dao_obj_type_url"] = $li_props["folder"]["attributes"]["create_dao_obj_type_url"];
				
				$li_props["objtype"]["attributes"] = $li_props["file"]["attributes"];
				$li_props["hibernatemodel"]["attributes"] = $li_props["file"]["attributes"];
			}
			else if ($item_type == "test_unit") {
				$li_props["folder"]["attributes"]["create_test_unit_obj_url"] = $project_url_prefix . "phpframework/testunit/create_test?path=#path#&file_name=#extra#";
				
				$li_props[$item_type]["attributes"]["create_test_unit_obj_url"] = $li_props["folder"]["attributes"]["create_test_unit_obj_url"];
				$li_props[$item_type]["attributes"]["manage_test_units_url"] = $project_url_prefix . "phpframework/testunit/";
				
				$li_props["test_unit_obj"]["attributes"] = $li_props["file"]["attributes"];
				$li_props["test_unit_obj"]["attributes"]["onClick"] = 'return goTo(this, \'edit_url\', event)';
				$li_props["test_unit_obj"]["attributes"]["edit_url"] = $project_url_prefix . "phpframework/testunit/edit_test?path=#path#";
			}
			else if ($item_type == "ibatis" || $item_type == "hibernate") {
				$li_props[$item_type]["attributes"]["create_automatically_url"] = $project_url_prefix . "phpframework/dataaccess/create_data_access_objs_automatically?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&item_type=$item_type&path=#path#";
				
				$li_props["file"]["attributes"]["create_automatically_url"] = $li_props[$item_type]["attributes"]["create_automatically_url"];
				$li_props["file"]["attributes"]["edit_raw_file_url"] = $project_url_prefix . "phpframework/dataaccess/edit_file?bean_name=$bean_name&bean_file_name=$bean_file_name&item_type=$item_type&path=#path#";
				$li_props["file"]["attributes"]["add_obj_url"] = $project_url_prefix . "phpframework/dataaccess/edit_hbn_obj?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&item_type=$item_type&path=#path#";
				$li_props["file"]["attributes"]["add_query_url"] = $project_url_prefix . "phpframework/dataaccess/edit_query?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&item_type=$item_type&path=#path#";
				$li_props["file"]["attributes"]["add_result_map_url"] = $project_url_prefix . "phpframework/dataaccess/edit_map?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&item_type=$item_type&path=#path#&query_type=result_map";
				$li_props["file"]["attributes"]["add_parameter_map_url"] = $project_url_prefix . "phpframework/dataaccess/edit_map?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&item_type=$item_type&path=#path#&query_type=parameter_map";
				$li_props["file"]["attributes"]["manage_includes_url"] = $project_url_prefix . "phpframework/dataaccess/edit_includes?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&item_type=$item_type&path=#path#";
				
				$li_props["folder"]["attributes"]["create_automatically_url"] = $li_props[$item_type]["attributes"]["create_automatically_url"];
				$li_props["folder"]["attributes"]["add_obj_url"] = $li_props["file"]["attributes"]["add_obj_url"];
				
				$li_props["obj"]["attributes"]["onClick"] = 'return goTo(this, \'edit_url\', event)';
				$li_props["obj"]["attributes"]["edit_url"] = $project_url_prefix . "phpframework/dataaccess/edit_hbn_obj?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&item_type=$item_type&path=#path#&obj=#node_id#";
				$li_props["obj"]["attributes"]["remove_url"] = $project_url_prefix . "phpframework/dataaccess/remove_hbn_obj?bean_name=$bean_name&bean_file_name=$bean_file_name&path=#path#&item_type=$item_type&obj=#node_id#";
				$li_props["obj"]["attributes"]["add_query_url"] = $li_props["file"]["attributes"]["add_query_url"] . "&obj=#hbn_obj_id#";
				$li_props["obj"]["attributes"]["add_relationship_url"] = $project_url_prefix . "phpframework/dataaccess/edit_relationship?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&item_type=$item_type&path=#path#&obj=#hbn_obj_id#";
				$li_props["obj"]["attributes"]["add_result_map_url"] = $li_props["file"]["attributes"]["add_result_map_url"] . "&obj=#hbn_obj_id#";
				$li_props["obj"]["attributes"]["add_parameter_map_url"] = $li_props["file"]["attributes"]["add_parameter_map_url"] . "&obj=#hbn_obj_id#";
				$li_props["obj"]["attributes"]["diff_file_url"] = $li_props["file"]["attributes"]["diff_file_url"];
				
				$li_props["import"]["attributes"]["add_query_url"] = $li_props["file"]["attributes"]["add_query_url"] . "&relationship_type=import";
				$li_props["import"]["attributes"]["add_relationship_url"] = $project_url_prefix . "phpframework/dataaccess/edit_relationship?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&item_type=$item_type&path=#path#&relationship_type=import";
				$li_props["import"]["attributes"]["add_result_map_url"] = $li_props["file"]["attributes"]["add_result_map_url"] . "&relationship_type=import";
				$li_props["import"]["attributes"]["add_parameter_map_url"] = $li_props["file"]["attributes"]["add_parameter_map_url"] . "&relationship_type=import";
				$li_props["import"]["attributes"]["diff_file_url"] = $li_props["file"]["attributes"]["diff_file_url"];
				
				$li_props["query"]["attributes"]["onClick"] = 'return goTo(this, \'edit_url\', event)';
				$li_props["query"]["attributes"]["edit_url"] = $project_url_prefix . "phpframework/dataaccess/edit_query?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&item_type=$item_type&path=#path#&obj=#hbn_obj_id#&query_id=#node_id#&query_type=#query_type#&relationship_type=#relationship_type#";
				$li_props["query"]["attributes"]["remove_url"] = $project_url_prefix . "phpframework/dataaccess/remove_query?bean_name=$bean_name&bean_file_name=$bean_file_name&item_type=$item_type&path=#path#&obj=#hbn_obj_id#&query_id=#node_id#&query_type=#query_type#&relationship_type=#relationship_type#";
				
				$li_props["relationship"]["attributes"]["onClick"] = 'return goTo(this, \'edit_url\', event)';
				$li_props["relationship"]["attributes"]["edit_url"] = $project_url_prefix . "phpframework/dataaccess/edit_relationship?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&item_type=$item_type&path=#path#&obj=#hbn_obj_id#&query_id=#node_id#&query_type=#query_type#&relationship_type=#relationship_type#";
				$li_props["relationship"]["attributes"]["remove_url"] = $project_url_prefix . "phpframework/dataaccess/remove_relationship?bean_name=$bean_name&bean_file_name=$bean_file_name&item_type=$item_type&path=#path#&obj=#hbn_obj_id#&query_id=#node_id#&query_type=#query_type#&relationship_type=#relationship_type#";
				
				$li_props["map"]["attributes"]["onClick"] = 'return goTo(this, \'edit_url\', event)';
				$li_props["map"]["attributes"]["edit_url"] = $project_url_prefix . "phpframework/dataaccess/edit_map?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&item_type=$item_type&path=#path#&obj=#hbn_obj_id#&map=#node_id#&query_type=#query_type#&relationship_type=#relationship_type#";
				$li_props["map"]["attributes"]["remove_url"] = $project_url_prefix . "phpframework/dataaccess/remove_map?bean_name=$bean_name&bean_file_name=$bean_file_name&item_type=$item_type&path=#path#&obj=#hbn_obj_id#&map=#node_id#&query_type=#query_type#&relationship_type=#relationship_type#";
			}
			else if ($item_type == "businesslogic") {
				$li_props[$item_type]["attributes"]["create_laravel_url"] = $project_url_prefix . "phpframework/businesslogic/create_business_logic_laravel?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#";
				$li_props[$item_type]["attributes"]["create_automatically_url"] = $has_automatic_ui ? $project_url_prefix . "phpframework/businesslogic/create_business_logic_objs_automatically?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#" : "";
				
				$li_props["folder"]["attributes"]["add_service_obj_url"] = $project_url_prefix . "phpframework/businesslogic/edit_service?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#";
				$li_props["folder"]["attributes"]["save_service_obj_url"] = $project_url_prefix . "phpframework/businesslogic/save_service?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#";
				$li_props["folder"]["attributes"]["edit_service_obj_url"] = $project_url_prefix . "phpframework/businesslogic/edit_service?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path##extra#.php&service=#extra#";
				$li_props["folder"]["attributes"]["add_service_func_url"] = $project_url_prefix . "phpframework/businesslogic/edit_function?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#";
				$li_props["folder"]["attributes"]["save_service_func_url"] = $project_url_prefix . "phpframework/businesslogic/save_function?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#";
				$li_props["folder"]["attributes"]["edit_service_func_url"] = $project_url_prefix . "phpframework/businesslogic/edit_function?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#functions.php&function=#extra#";
				$li_props["folder"]["attributes"]["create_automatically_url"] = $li_props[$item_type]["attributes"]["create_automatically_url"];
				$li_props["folder"]["attributes"]["laravel_preview_url"] = $project_url_prefix . "phpframework/cms/laravel/preview_laravel_project?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#";
				$li_props["folder"]["attributes"]["laravel_terminal_url"] = $project_url_prefix . "phpframework/cms/laravel/terminal_laravel_project?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#";
				
				$li_props["file"]["attributes"]["edit_raw_file_url"] = $project_url_prefix . "phpframework/businesslogic/edit_file?bean_name=$bean_name&bean_file_name=$bean_file_name&item_type=$item_type&path=#path#";
				$li_props["file"]["attributes"]["add_service_obj_url"] = $li_props["folder"]["attributes"]["add_service_obj_url"];
				$li_props["file"]["attributes"]["save_service_obj_url"] = $li_props["folder"]["attributes"]["save_service_obj_url"];
				$li_props["file"]["attributes"]["edit_service_obj_url"] = $project_url_prefix . "phpframework/businesslogic/edit_service?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&service=#extra#";
				$li_props["file"]["attributes"]["add_service_func_url"] = $li_props["folder"]["attributes"]["add_service_func_url"];
				$li_props["file"]["attributes"]["save_service_func_url"] = $li_props["folder"]["attributes"]["save_service_func_url"];
				$li_props["file"]["attributes"]["edit_service_func_url"] = $project_url_prefix . "phpframework/businesslogic/edit_function?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&function=#extra#";
				$li_props["file"]["attributes"]["manage_includes_url"] = $project_url_prefix . "phpframework/businesslogic/edit_includes?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#";
				
				$li_props["service"]["attributes"]["onClick"] = 'return goTo(this, \'edit_url\', event)';
				$li_props["service"]["attributes"]["edit_url"] = $project_url_prefix . "phpframework/businesslogic/edit_service?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&service=#service#"; 
				$li_props["service"]["attributes"]["rename_url"] = $li_props["file"]["attributes"]["rename_url"];
				$li_props["service"]["attributes"]["remove_url"] = $project_url_prefix . "phpframework/businesslogic/remove_service?bean_name=$bean_name&bean_file_name=$bean_file_name&path=#path#&service=#service#";
				$li_props["service"]["attributes"]["add_service_method_url"] = $project_url_prefix . "phpframework/businesslogic/edit_method?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&service=#service#";
				$li_props["service"]["attributes"]["save_service_method_url"] = $project_url_prefix . "phpframework/businesslogic/save_method?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&class=#service#";
				$li_props["service"]["attributes"]["edit_service_method_url"] = $project_url_prefix . "phpframework/businesslogic/edit_method?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&service=#service#&method=#extra#";
				$li_props["service"]["attributes"]["edit_raw_file_url"] = $li_props["file"]["attributes"]["edit_raw_file_url"];
				$li_props["service"]["attributes"]["copy_url"] = $li_props["file"]["attributes"]["copy_url"];
				$li_props["service"]["attributes"]["cut_url"] = $li_props["service"]["attributes"]["copy_url"];
				$li_props["service"]["attributes"]["diff_file_url"] = $li_props["file"]["attributes"]["diff_file_url"];
				
				$li_props["method"]["attributes"]["onClick"] = 'return goTo(this, \'edit_url\', event)';
				$li_props["method"]["attributes"]["edit_url"] = $project_url_prefix . "phpframework/businesslogic/edit_method?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&service=#service#&method=#method#";
				$li_props["method"]["attributes"]["remove_url"] = $project_url_prefix . "phpframework/businesslogic/remove_method?bean_name=$bean_name&bean_file_name=$bean_file_name&path=#path#&service=#service#&method=#method#";
				
				$li_props["function"]["attributes"]["onClick"] = 'return goTo(this, \'edit_url\', event)';
				$li_props["function"]["attributes"]["edit_url"] = $project_url_prefix . "phpframework/businesslogic/edit_function?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&function=#method#";
				$li_props["function"]["attributes"]["remove_url"] = $project_url_prefix . "phpframework/businesslogic/remove_function?bean_name=$bean_name&bean_file_name=$bean_file_name&path=#path#&function=#method#";
			}
			else if ($item_type == "presentation") {
				$li_props[$item_type]["attributes"]["create_url"] = str_replace("/admin/manage_file?", "/phpframework/presentation/manage_file?", $li_props["folder"]["attributes"]["create_url"]) . "&folder_type=project_folder";
				//$li_props[$item_type]["attributes"]["create_project_url"] = $li_props[$item_type]["attributes"]["create_url"] . "&folder_type=project";
				$li_props[$item_type]["attributes"]["create_project_url"] = $project_url_prefix . "phpframework/presentation/create_project?bean_name=$bean_name&bean_file_name=$bean_file_name&path=#path#&popup=1&on_success_js_func=onSuccessfullPopupAction";
				$li_props[$item_type]["attributes"]["manage_wordpress_url"] = $project_url_prefix . "phpframework/cms/wordpress/manage?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#";
				
				$li_props["cms_folder"] = $li_props["folder"];
				
				$li_props["wordpress_folder"] = $li_props["folder"];
				$li_props["wordpress_folder"]["attributes"]["manage_wordpress_url"] = $li_props[$item_type]["attributes"]["manage_wordpress_url"];
				
				$li_props["wordpress_installation_folder"] = $li_props["folder"];
				$li_props["wordpress_installation_folder"]["attributes"]["manage_wordpress_url"] = $li_props[$item_type]["attributes"]["manage_wordpress_url"];
				
				$li_props["project_folder"] = $li_props["folder"];
				$li_props["project_folder"]["attributes"]["rename_url"] = str_replace("/admin/manage_file?", "/phpframework/presentation/manage_file?", $li_props["folder"]["attributes"]["rename_url"]);
				$li_props["project_folder"]["attributes"]["remove_url"] = str_replace("/admin/manage_file?", "/phpframework/presentation/manage_file?", $li_props["folder"]["attributes"]["remove_url"]);
				$li_props["project_folder"]["attributes"]["create_url"] = $li_props[$item_type]["attributes"]["create_url"];
				$li_props["project_folder"]["attributes"]["create_project_url"] = $li_props[$item_type]["attributes"]["create_project_url"];
				$li_props["project_folder"]["attributes"]["upload_url"] = str_replace("/admin/manage_file?", "/phpframework/presentation/manage_file?", $li_props["folder"]["attributes"]["upload_url"]);
				$li_props["project_folder"]["attributes"]["copy_url"] = str_replace("/admin/manage_file?", "/phpframework/presentation/manage_file?", $li_props["folder"]["attributes"]["copy_url"]);
				$li_props["project_folder"]["attributes"]["cut_url"] = str_replace("/admin/manage_file?", "/phpframework/presentation/manage_file?", $li_props["folder"]["attributes"]["cut_url"]);
				$li_props["project_folder"]["attributes"]["paste_url"] = str_replace("/admin/manage_file?", "/phpframework/presentation/manage_file?", $li_props["folder"]["attributes"]["paste_url"]);
				$li_props["project_folder"]["get_sub_files_url"] .= "&folder_type=project_folder";
				
				$li_props["project"]["attributes"]["edit_url"] = $project_url_prefix . "phpframework/presentation/edit_project_details?bean_name=$bean_name&bean_file_name=$bean_file_name&path=#path#&popup=1&on_success_js_func=onSuccessfullEditProject";
				$li_props["project"]["attributes"]["rename_url"] = str_replace("/admin/manage_file?", "/phpframework/presentation/manage_file?", $li_props["folder"]["attributes"]["rename_url"]);
				$li_props["project"]["attributes"]["remove_url"] = str_replace("/admin/manage_file?", "/phpframework/presentation/manage_file?", $li_props["folder"]["attributes"]["remove_url"]);
				$li_props["project"]["attributes"]["download_url"] = $li_props["folder"]["attributes"]["download_url"];
				$li_props["project"]["attributes"]["zip_url"] = $li_props["project"]["attributes"]["rename_url"];
				$li_props["project"]["attributes"]["edit_project_global_variables_url"] = $project_url_prefix . "phpframework/presentation/edit_project_global_variables?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#src/config/pre_init_config.php";
				$li_props["project"]["attributes"]["edit_config_url"] = $project_url_prefix . "phpframework/presentation/edit_config?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#src/config/config.php";
				$li_props["project"]["attributes"]["edit_init_url"] = $project_url_prefix . "phpframework/presentation/edit_init?bean_name=$bean_name&bean_file_name=$bean_file_name&item_type=$item_type&path=#path#src/config/init.php";
				$li_props["project"]["attributes"]["manage_users_url"] = $project_url_prefix . "phpframework/module/user/admin/index?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&popup=1";
				$li_props["project"]["attributes"]["manage_references_url"] = $project_url_prefix . "phpframework/presentation/manage_references?bean_name=$bean_name&bean_file_name=$bean_file_name&path=#path#&popup=1&on_success_js_func=onSuccessfullPopupAction";
				$li_props["project"]["attributes"]["manage_wordpress_url"] = $li_props[$item_type]["attributes"]["manage_wordpress_url"];
				$li_props["project"]["attributes"]["install_program_url"] = $project_url_prefix . "phpframework/admin/install_program?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#";
				$li_props["project"]["attributes"]["view_project_url"] = $project_url_prefix . "phpframework/presentation/view_project?bean_name=$bean_name&bean_file_name=$bean_file_name&path=#path#";
				$li_props["project"]["attributes"]["test_project_url"] = $project_url_prefix . "phpframework/presentation/test_project?bean_name=$bean_name&bean_file_name=$bean_file_name&path=#path#";
				$li_props["project"]["attributes"]["project_path"] = "#path#"; //This is needed when we drag and drop a file/method/function inside of a project to a workflow diagram, because it will call the edit_php_code.js:getNodeProjectPath method.
				$li_props["project"]["get_sub_files_url"] = $li_props["folder"]["get_sub_files_url"] . "&folder_type=project";
				
				$li_props["project_common"] = $li_props["project"];
				
				$li_props["folder"]["get_sub_files_url"] .= "&folder_type=#folder_type#";
				
				$li_props["file"]["attributes"]["onClick"] = 'return goTo(this, \'edit_url\', event)';
				$li_props["file"]["attributes"]["edit_url"] = $li_props["file"]["attributes"]["edit_raw_file_url"] . "&folder_type=#folder_type#";
				$li_props["file"]["attributes"]["open_url"] = $project_url_prefix . "phpframework/presentation/open_file?bean_name=$bean_name&bean_file_name=$bean_file_name&path=#path#";
				
				$li_props["undefined_file"]["attributes"]["open_url"] = $li_props["file"]["attributes"]["open_url"];
				
				$li_props["entity_file"]["attributes"] = $li_props["file"]["attributes"];
				$li_props["entity_file"]["attributes"]["onClick"] = 'return goTo(this, \'click_url\', event)';
				$li_props["entity_file"]["attributes"]["click_url"] = $project_url_prefix . "phpframework/presentation/edit_entity?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#";
				$li_props["entity_file"]["attributes"]["edit_url"] = $li_props["entity_file"]["attributes"]["click_url"] . "&edit_entity_type=simple&dont_save_cookie=1";
				$li_props["entity_file"]["attributes"]["edit_raw_file_url"] = $li_props["entity_file"]["attributes"]["click_url"] . "&edit_entity_type=advanced&dont_save_cookie=1";
				$li_props["entity_file"]["attributes"]["add_url"] = $project_url_prefix . "phpframework/presentation/create_entity?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&popup=1&on_success_js_func=onSuccessfullPopupAction";
				$li_props["entity_file"]["attributes"]["view_project_url"] = $li_props["project"]["attributes"]["view_project_url"];
				$li_props["entity_file"]["attributes"]["test_project_url"] = $li_props["project"]["attributes"]["test_project_url"];
				
				$li_props["view_file"]["attributes"] = $li_props["file"]["attributes"];
				$li_props["view_file"]["attributes"]["onClick"] = 'return goTo(this, \'click_url\', event)';
				$li_props["view_file"]["attributes"]["click_url"] = $project_url_prefix . "phpframework/presentation/edit_view?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#";
				$li_props["view_file"]["attributes"]["edit_url"] = $li_props["view_file"]["attributes"]["click_url"] . "&edit_view_type=simple&dont_save_cookie=1";
				$li_props["view_file"]["attributes"]["edit_raw_file_url"] = $li_props["view_file"]["attributes"]["click_url"] . "&edit_view_type=advanced&dont_save_cookie=1";
				
				$li_props["template_file"]["attributes"] = $li_props["file"]["attributes"];
				$li_props["template_file"]["attributes"]["onClick"] = 'return goTo(this, \'click_url\', event)';
				$li_props["template_file"]["attributes"]["click_url"] = $project_url_prefix . "phpframework/presentation/edit_template?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#";
				$li_props["template_file"]["attributes"]["edit_url"] = $li_props["template_file"]["attributes"]["click_url"] . "&edit_template_type=simple&dont_save_cookie=1";
				$li_props["template_file"]["attributes"]["edit_raw_file_url"] = $li_props["template_file"]["attributes"]["click_url"] . "&edit_template_type=advanced&dont_save_cookie=1";
				
				$li_props["util_file"]["attributes"] = $li_props["file"]["attributes"];
				$li_props["util_file"]["attributes"]["edit_url"] = $project_url_prefix . "phpframework/presentation/edit_util?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#";
				$li_props["util_file"]["attributes"]["edit_raw_file_url"] = $li_props["util_file"]["attributes"]["edit_url"];
				$li_props["util_file"]["attributes"]["add_class_obj_url"] = $project_url_prefix . "phpframework/admin/edit_file_class?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&item_type=presentation";
				$li_props["util_file"]["attributes"]["save_class_obj_url"] = $project_url_prefix . "phpframework/admin/save_file_class?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&item_type=presentation";
				$li_props["util_file"]["attributes"]["edit_class_obj_url"] = $project_url_prefix . "phpframework/admin/edit_file_class?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&class=#extra#&item_type=presentation";
				$li_props["util_file"]["attributes"]["add_class_func_url"] = $project_url_prefix . "phpframework/admin/edit_file_function?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&item_type=presentation";
				$li_props["util_file"]["attributes"]["save_class_func_url"] = $project_url_prefix . "phpframework/admin/save_file_function?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&item_type=presentation";
				$li_props["util_file"]["attributes"]["edit_class_func_url"] = $project_url_prefix . "phpframework/admin/edit_file_function?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&function=#extra#&item_type=presentation";
				$li_props["util_file"]["attributes"]["manage_includes_url"] = $project_url_prefix . "phpframework/admin/edit_file_includes?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&item_type=$item_type&path=#path#&item_type=presentation";
				
				$li_props["config_file"]["attributes"] = $li_props["file"]["attributes"];
				$li_props["config_file"]["attributes"]["edit_url"] = $project_url_prefix . "phpframework/presentation/edit_config?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#";
				
				$li_props["controller_file"]["attributes"] = $li_props["file"]["attributes"];
				$li_props["css_file"]["attributes"] = $li_props["file"]["attributes"];
				$li_props["js_file"]["attributes"] = $li_props["file"]["attributes"];
				$li_props["img_file"]["attributes"] = $li_props["file"]["attributes"];
				
				$li_props["block_file"]["attributes"] = $li_props["file"]["attributes"];
				$li_props["block_file"]["attributes"]["onClick"] = 'return goTo(this, \'click_url\', event)';
				$li_props["block_file"]["attributes"]["click_url"] = $project_url_prefix . "phpframework/presentation/edit_block?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#";
				$li_props["block_file"]["attributes"]["edit_url"] = $li_props["block_file"]["attributes"]["click_url"] . "&edit_block_type=simple&dont_save_cookie=1";
				$li_props["block_file"]["attributes"]["edit_raw_file_url"] = $li_props["block_file"]["attributes"]["click_url"] . "&edit_block_type=advanced&dont_save_cookie=1";
				
				$li_props["module_file"] = $li_props["undefined_file"];
				$li_props["module_folder"] = $li_props["folder"];
				$li_props["module_folder"]["get_sub_files_url"] .= "&folder_type=module";
				
				$li_props["entities_folder"] = $li_props["views_folder"] = $li_props["templates_folder"] = $li_props["utils_folder"] = $li_props["configs_folder"] = $li_props["webroot_folder"] = $li_props["controllers_folder"] = $li_props["blocks_folder"] = $li_props["folder"];
				
				$li_props["templates_folder"]["attributes"]["install_template_url"] = $project_url_prefix . "phpframework/presentation/install_template?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#";
				$li_props["templates_folder"]["attributes"]["convert_template_url"] = $project_url_prefix . "phpframework/presentation/convert_url_to_template?bean_name=$bean_name&bean_file_name=$bean_file_name&path=#path#";
				$li_props["templates_folder"]["attributes"]["generate_template_with_ai_url"] = $project_url_prefix . "phpframework/presentation/generate_template_with_ai?bean_name=$bean_name&bean_file_name=$bean_file_name&path=#path#";
				
				$li_props["template_folder"] = $li_props["folder"];
				$li_props["template_folder"]["attributes"]["download_url"] .= "&folder_type=template_folder";
				
				$li_props["entities_folder"]["attributes"]["create_automatically_url"] = $has_automatic_ui ? $project_url_prefix . "phpframework/presentation/create_presentation_uis_automatically?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#" : "";
				$li_props["entities_folder"]["attributes"]["create_uis_diagram_url"] = $has_automatic_ui ? $project_url_prefix . "phpframework/presentation/create_presentation_uis_diagram?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#" : "";
				$li_props["entities_folder"]["attributes"]["view_project_url"] = $li_props["project"]["attributes"]["view_project_url"];
				$li_props["entities_folder"]["attributes"]["test_project_url"] = $li_props["project"]["attributes"]["test_project_url"];
				$li_props["entities_folder"]["attributes"]["project_with_auto_view"] = "0"; //This will be set in the file_manager.js with the real value
				
				$li_props["webroot_folder"]["attributes"]["create_laravel_url"] = $project_url_prefix . "phpframework/cms/laravel/create_laravel_project?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#";
				
				//bc of the folders and sub_folder inside of the entities or webroot
				$li_props["folder"]["attributes"]["create_automatically_url"] = $li_props["entities_folder"]["attributes"]["create_automatically_url"];
				$li_props["folder"]["attributes"]["create_uis_diagram_url"] = $li_props["entities_folder"]["attributes"]["create_uis_diagram_url"];
				$li_props["folder"]["attributes"]["view_project_url"] = $li_props["project"]["attributes"]["view_project_url"];
				$li_props["folder"]["attributes"]["test_project_url"] = $li_props["project"]["attributes"]["test_project_url"];
				$li_props["folder"]["attributes"]["laravel_preview_url"] = $project_url_prefix . "phpframework/cms/laravel/preview_laravel_project?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#";
				$li_props["folder"]["attributes"]["laravel_terminal_url"] = $project_url_prefix . "phpframework/cms/laravel/terminal_laravel_project?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#";
				
				$li_props["utils_folder"]["attributes"]["add_class_obj_url"] = $project_url_prefix . "phpframework/admin/edit_file_class?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&item_type=presentation";
				$li_props["utils_folder"]["attributes"]["save_class_obj_url"] = $project_url_prefix . "phpframework/admin/save_file_class?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&item_type=presentation";
				$li_props["utils_folder"]["attributes"]["add_class_func_url"] = $project_url_prefix . "phpframework/admin/edit_file_function?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&item_type=presentation";
				$li_props["utils_folder"]["attributes"]["save_class_func_url"] = $project_url_prefix . "phpframework/admin/save_file_function?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&item_type=presentation";
				
				//bc of the folders and sub_folder inside of the utils
				$li_props["folder"]["attributes"]["add_class_obj_url"] = $li_props["utils_folder"]["attributes"]["add_class_obj_url"];
				$li_props["folder"]["attributes"]["save_class_obj_url"] = $li_props["utils_folder"]["attributes"]["save_class_obj_url"];
				$li_props["folder"]["attributes"]["add_class_func_url"] = $li_props["utils_folder"]["attributes"]["add_class_func_url"];
				$li_props["folder"]["attributes"]["save_class_func_url"] = $li_props["utils_folder"]["attributes"]["save_class_func_url"];
				
				$li_props["cache_file"]["attributes"]["onClick"] = 'return goTo(this, \'edit_raw_file_url\', event)';
				$li_props["cache_file"]["attributes"]["edit_raw_file_url"] = $li_props["file"]["attributes"]["edit_raw_file_url"] . "&create_dependencies=1";
				$li_props["cache_file"]["attributes"]["download_url"] = $li_props["file"]["attributes"]["download_url"];
				$li_props["cache_file"]["attributes"]["zip_url"] = $li_props["file"]["attributes"]["zip_url"];
				$li_props["cache_file"]["attributes"]["diff_file_url"] = $li_props["file"]["attributes"]["diff_file_url"];
				
				$li_props["caches_folder"]["get_sub_files_url"] = $li_props["folder"]["get_sub_files_url"];
				$li_props["caches_folder"]["attributes"]["create_url"] = $li_props["folder"]["attributes"]["create_url"];
				$li_props["caches_folder"]["attributes"]["upload_url"] = $li_props["folder"]["attributes"]["upload_url"];
				$li_props["caches_folder"]["attributes"]["download_url"] = $li_props["folder"]["attributes"]["download_url"];
				$li_props["caches_folder"]["attributes"]["paste_url"] = $li_props["folder"]["attributes"]["paste_url"];
				
				//set file utils classes, methods ad functions items
				$li_props["class"]["attributes"]["onClick"] = 'return goTo(this, \'edit_url\', event)';
				$li_props["class"]["attributes"]["edit_url"] = $project_url_prefix . "phpframework/admin/edit_file_class?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&item_type=presentation&class=#class#";
				$li_props["class"]["attributes"]["edit_raw_file_url"] = $li_props["util_file"]["attributes"]["edit_raw_file_url"];
				$li_props["class"]["attributes"]["remove_url"] = $project_url_prefix . "phpframework/admin/remove_file_class?bean_name=$bean_name&bean_file_name=$bean_file_name&path=#path#&item_type=presentation&class=#class#";
				$li_props["class"]["attributes"]["add_class_method_url"] = $project_url_prefix . "phpframework/admin/edit_file_class_method?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&item_type=presentation&class=#class#&static=1";
				$li_props["class"]["attributes"]["save_class_method_url"] = $project_url_prefix . "phpframework/admin/save_file_class_method?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&item_type=presentation&class=#class#";
				$li_props["class"]["attributes"]["edit_class_method_url"] = $project_url_prefix . "phpframework/admin/edit_file_class_method?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&item_type=presentation&class=#class#&method=#extra#";
				$li_props["class"]["attributes"]["diff_file_url"] = $li_props["file"]["attributes"]["diff_file_url"];
				
				$li_props["method"]["attributes"]["onClick"] = 'return goTo(this, \'edit_url\', event)';
				$li_props["method"]["attributes"]["edit_url"] = $project_url_prefix . "phpframework/admin/edit_file_class_method?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&item_type=presentation&class=#class#&method=#method#";
				$li_props["method"]["attributes"]["remove_url"] = $project_url_prefix . "phpframework/admin/remove_file_class_method?bean_name=$bean_name&bean_file_name=$bean_file_name&path=#path#&item_type=presentation&class=#class#&method=#method#";
				
				$li_props["function"]["attributes"]["onClick"] = 'return goTo(this, \'edit_url\', event)';
				$li_props["function"]["attributes"]["edit_url"] = $project_url_prefix . "phpframework/admin/edit_file_function?bean_name=$bean_name&bean_file_name=$bean_file_name$filter_by_layout_url_query&path=#path#&item_type=presentation&function=#method#";
				$li_props["function"]["attributes"]["remove_url"] = $project_url_prefix . "phpframework/admin/remove_file_function?bean_name=$bean_name&bean_file_name=$bean_file_name&path=#path#&item_type=presentation&function=#method#";
			}
			
			$li_props["cms_common"] = $li_props["folder"];
			$li_props["cms_program"] = $li_props["folder"];
			$li_props["cms_resource"] = $li_props["folder"];
			
			$properties["ui"] = $li_props;
			
			$layer_id = $layer_name;
			$main_layers_properties[$layer_id] = $properties;
			
			//load the db drivers properties
			if ($item_type == "db") {
				if (is_array($layer)) 
					foreach ($layer as $node_id => $node) 
						$main_layers_properties[$node_id] = $properties;
			}
			//prepare dao and test_unit inside of vendor
			else if ($item_type == "vendor") {
				$dao_layer = array(
					"properties" => array(
						"item_type" => "dao",
						"bean_name" => "dao",
					),
				);
				self::updateMainLayersProperties("dao", $dao_layer, $main_layers_properties, $project_url_prefix);
				
				$test_unit_layer = array(
					"properties" => array(
						"item_type" => "test_unit",
						"bean_name" => "test_unit",
					),
				);
				self::updateMainLayersProperties("test_unit", $test_unit_layer, $main_layers_properties, $project_url_prefix);
			}
		}
	}

	public static function getLayerLabel($name, $properties = false) {
		if (!empty($properties["item_label"]))
			$name = $properties["item_label"];
		else if (!isset($properties["item_type"]))
			$name = ucfirst(strtolower(str_replace("_", " ", $name)));
		
		$title = isset($properties["item_title"]) ? $properties["item_title"] : null;
		
		return '<label' . ($title ? ' title="' . str_replace('"', "&quot;", $title) . '"' : '') . '>' . $name . '</label>';
	}

	public static function getSubNodes($nodes, $main_layer_properties = false, $parent_path = false) {
		$html = '';
		
		if (is_array($nodes))
			foreach ($nodes as $node_id => $node)
				if ($node_id != "properties" && $node_id != "aliases")
					$html .= self::getNode($node_id, $node, $main_layer_properties, $parent_path);
		
		return $html;
	}
	
	public static function getNode($node_id, $node, $main_layer_properties = false, $parent_path = false, $class = false) {
		//echo "<pre>$node_id";print_r($node);print_r($main_layer_properties);die();
		//echo "node_id:$node_id<br>";
		$html = "";
		
		$node_id = trim($node_id);
		$properties = isset($node["properties"]) ? $node["properties"] : null;
		//print_r($node);
		
		$item_type = isset($properties["item_type"]) ? $properties["item_type"] : null;
		$item_id = isset($properties["item_id"]) ? $properties["item_id"] : null;
		$item_class = isset($properties["item_class"]) ? $properties["item_class"] : null;
		$item_menu = isset($properties["item_menu"]) ? $properties["item_menu"] : null;
		
		//if ($item_type=="db_tables"){echo"<pre>";print_r($properties);print_r($main_layer_properties["ui"][$item_type]);die();}
		
		//PREPARE FILE PATH
		$file_path = isset($properties["path"]) ? $properties["path"] : null;
		if (empty($file_path) && ($item_type == "folder" || $item_type == "file" || $item_type == "import")) 
			$file_path .= $parent_path . $node_id . ($item_type == "folder" ? "/" : "");
		
		//START LI HTML
		$li_class = trim($class . " " . $item_class);
		$li_icon = $class ? $class : self::getIcon($properties);
		
		$html .= '<li ' . ($li_class ? 'class="' . $li_class . '"' : '') . ' data-jstree=\'{"icon":"' . $li_icon . '"}\'><a';
		
		//PREPARE ATTRIBUTES
		if (!empty($item_id) && !empty($item_menu)) 
			$html .= ' properties_id="' . $item_id . '"';
		
		$folder_type = isset($properties["folder_type"]) ? $properties["folder_type"] : null;
		$vendor_framework = isset($properties["vendor_framework"]) ? $properties["vendor_framework"] : null;
		
		if (!empty($main_layer_properties["ui"][$item_type]["attributes"])) {
			foreach ($main_layer_properties["ui"][$item_type]["attributes"] as $attr_name => $attr_value) {
				$attr_value = str_replace("#path#", $file_path, $attr_value);
				$attr_value = str_replace("#folder_type#", $folder_type, $attr_value);
				$attr_value = str_replace("#vendor_framework#", $vendor_framework, $attr_value);
				
				if ($item_type == "db_driver" || $item_type == "db_diagram" || $item_type == "db_tables" || $item_type == "db_views" || $item_type == "db_procedures" || $item_type == "db_functions" || $item_type == "db_events" || $item_type == "db_triggers") {
					$bean_name = !empty($properties["bean_name"]) ? $properties["bean_name"] : "";
					$bean_file_name = !empty($properties["bean_file_name"]) ? $properties["bean_file_name"] : "";
					
					$attr_value = str_replace("#bean_name#", $bean_name, $attr_value);
					$attr_value = str_replace("#bean_file_name#", $bean_file_name, $attr_value);
				}
				else if ($item_type == "obj" || $item_type == "query" || $item_type == "relationship" || $item_type == "hbn_native" || $item_type == "map" || $item_type == "import") {
					$hbn_obj_id = !empty($properties["hbn_obj_id"]) ? $properties["hbn_obj_id"] : "";
					$hbn_obj_id = $item_type == "query" || $item_type == "relationship" || $item_type == "hbn_native" || $item_type == "map" || $item_type == "import" ? $hbn_obj_id : $node_id;
					$query_type = !empty($properties["query_type"]) ? $properties["query_type"] : "";
					$relationship_type = !empty($properties["relationship_type"]) ? $properties["relationship_type"] : "";
					
					$attr_value = str_replace("#hbn_obj_id#", $hbn_obj_id, $attr_value);
					$attr_value = str_replace("#query_type#", $query_type, $attr_value);
					$attr_value = str_replace("#relationship_type#", $relationship_type, $attr_value);
					$attr_value = str_replace("#node_id#", $node_id, $attr_value);
				}
				else if ($item_type == "service" || $item_type == "class" || $item_type == "method" || $item_type == "function") {
					$service_id = $item_type == "method" || !empty($properties["service"]) ? $properties["service"] : $node_id;
					$service_id = $item_type == "method" || $service_id != "" ? $service_id : $node_id;
								
					$class_id = !empty($properties["class"]) ? $properties["class"] : "";
					$class_id = $item_type == "method" || $class_id != "" ? $class_id : $node_id;
					
					$attr_value = str_replace("#service#", $service_id, $attr_value);
					$attr_value = str_replace("#class#", $node_id, $attr_value);
					$attr_value = str_replace("#method#", $node_id, $attr_value);
				}
				else if ($item_type == "table" || $item_type == "attribute") {
					$bean_name = !empty($properties["bean_name"]) ? $properties["bean_name"] : "";
					$bean_file_name = !empty($properties["bean_file_name"]) ? $properties["bean_file_name"] : "";
					$name = !empty($properties["name"]) ? $properties["name"] : "";
					
					$attr_value = str_replace("#bean_name#", $bean_name, $attr_value);
					$attr_value = str_replace("#bean_file_name#", $bean_file_name, $attr_value);
					$attr_value = str_replace("#name#", $name, $attr_value);
					$attr_value = str_replace("#table#", $name, $attr_value);
				}
				else if ($item_type == "db_view" || $item_type == "db_procedure" || $item_type == "db_function" || $item_type == "db_event" || $item_type == "db_trigger") {
					$bean_name = !empty($properties["bean_name"]) ? $properties["bean_name"] : "";
					$bean_file_name = !empty($properties["bean_file_name"]) ? $properties["bean_file_name"] : "";
					$name = !empty($properties["name"]) ? $properties["name"] : "";
					
					$attr_value = str_replace("#bean_name#", $bean_name, $attr_value);
					$attr_value = str_replace("#bean_file_name#", $bean_file_name, $attr_value);
					$attr_value = str_replace("#item_type#", $item_type, $attr_value);
					$attr_value = str_replace("#object#", $name, $attr_value);
				}
				else if ($item_type == "entities_folder" && $attr_name == "project_with_auto_view")
					$attr_value = !empty($properties["project_with_auto_view"]) ? $properties["project_with_auto_view"] : "0";
				
				$html .= " $attr_name=\"$attr_value\"";
			}
		}
		
		$url = false;
		if (isset($main_layer_properties["ui"][$item_type]["get_sub_files_url"])) {
			$url = $main_layer_properties["ui"][$item_type]["get_sub_files_url"];
			$url = str_replace("#path#", $file_path, $url);
			$url = str_replace("#folder_type#", $folder_type, $url);
			$url = str_replace("#vendor_framework#", $vendor_framework, $url);
		
			if ($item_type == "db_driver" || $item_type == "db_diagram" || $item_type == "db_tables" || $item_type == "db_views" || $item_type == "db_procedures" || $item_type == "db_functions" || $item_type == "db_events" || $item_type == "db_triggers" || $item_type == "table") {
				$bean_name = !empty($properties["bean_name"]) ? $properties["bean_name"] : "";
				$bean_file_name = !empty($properties["bean_file_name"]) ? $properties["bean_file_name"] : "";
				
				$url = str_replace("#bean_name#", $bean_name, $url);
				$url = str_replace("#bean_file_name#", $bean_file_name, $url);
				$url = str_replace("#item_type#", $item_type, $url);
				$url = str_replace("#table#", $node_id, $url);
			}
		}
		
		//PREPARE LABEL
		$html .= '>' . self::getLayerLabel($node_id, $properties) . "</a>\n";
		
		//PREPARE SUB-NODES
		$sub_nodes = self::getSubNodes($node, $main_layer_properties, $file_path);
		
		$html .= '<ul ' . (!empty($url) ? 'url="' . $url . '"' : '') . '>' . $sub_nodes . '</ul>' . "\n";
		$html .= '</li>' . self::getMenu($properties) . "\n";
		
		return $html;
	}

	public static function getIcon($properties) {
		return !empty($properties["item_type"]) ? strtolower($properties["item_type"]) : "";
	}

	public static function getMenu($properties) {
		if (!empty($properties["item_id"]) && !empty($properties["item_menu"]))
			return '<script>
				menu_item_properties.' . $properties["item_id"] . ' = ' . json_encode($properties["item_menu"]) . ';
			</script>';
	}
}
?>
