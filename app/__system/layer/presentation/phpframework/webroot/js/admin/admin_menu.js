/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

var file_to_copy_or_cut = null;
var copy_or_cut_action = null;
var copy_or_cut_tree_node_id = null;
var navigator_droppables_active = true;

var ToolsFancyPopup = new MyFancyPopupClass();
var ProjectsFancyPopup = new MyFancyPopupClass();
var DBTableTaskOptionsFancyPopup = new MyFancyPopupClass();
var LogConsoleFancyPopup = new MyFancyPopupClass();

$(function() {
	$(document).keyup( function( e ) {
		//on escape key, disable all draggable events
		if (e.which=== 27 || e.keyCode === 27) {
			var ui_obj_helper = $(".ui-draggable-dragging");
			ui_obj_helper.data("escape_key_pressed", true).trigger('mouseup');
			
			//reset escape_key_pressed, just in case
			setTimeout(function() {
				if (ui_obj_helper.parent()[0] && ui_obj_helper.data("escape_key_pressed"))
					ui_obj_helper.data("escape_key_pressed", null);
			}, 2000);
		}
	});
});

function initFileTreeMenu() {
	//prepare menu tree
	mytree.init("file_tree");
	
	$("#file_tree").removeClass("hidden");
	
	initContextMenus();
}

function initContextMenus() {
	var file_tree = $("#file_tree");
	
	var obj = null;
	
	obj = file_tree.find(".db_layers li.main_node_db");
	addLiContextMenu(obj.children("a").addClass("link"), "main_db_group_context_menu", {callback: onDBContextMenu});
	initDBContextMenu(obj);//This covers the scenario where the DB_DRIVER node is inside of the ".db_layers li.main_node_db" and ".db_layers" node
	
	obj = file_tree.find(".data_access_layers li.main_node_ibatis");
	addLiContextMenu(obj.children("a").addClass("link"), "main_ibatis_group_context_menu", {callback: onIbatisContextMenu});
	initIbatisContextMenu(obj);
	
	obj = file_tree.find(".data_access_layers li.main_node_hibernate");
	addLiContextMenu(obj.children("a").addClass("link"), "main_hibernate_group_context_menu", {callback: onHibernateContextMenu});
	initHibernateContextMenu(obj);
	
	obj = file_tree.find(".business_logic_layers li.main_node_businesslogic");
	addLiContextMenu(obj.children("a").addClass("link"), "main_business_logic_group_context_menu", {callback: onContextContextMenu});
	initContextContextMenu(obj);
	
	obj = file_tree.find(".presentation_layers li.main_node_presentation");
	addLiContextMenu(obj.children("a").addClass("link"), "main_presentation_group_context_menu", {callback: onPresentationContextMenu});
	initPresentationContextMenu(obj);
	
	obj = file_tree.find("li.main_node_lib");
	addLiContextMenu(obj.children("a").addClass("link"), "main_lib_group_context_menu", {callback: onLibContextMenu});
	initLibContextMenu(obj);
	
	obj = file_tree.find("li.main_node_dao");
	addLiContextMenu(obj.children("a").addClass("link"), "main_dao_group_context_menu", {callback: onDaoContextMenu});
	initDaoContextMenu(obj);
	
	obj = file_tree.find("li.main_node_vendor");
	addLiContextMenu(obj.children("a").addClass("link"), "main_vendor_group_context_menu", {callback: onVendorContextMenu});
	initVendorContextMenu(obj);
	
	obj = file_tree.find("li.main_node_test_unit");
	addLiContextMenu(obj.children("a").addClass("link"), "main_test_unit_group_context_menu", {callback: onTestUnitContextMenu});
	initTestUnitContextMenu(obj);
	
	obj = file_tree.find("li.main_node_other");
	addLiContextMenu(obj.children("a").addClass("link"), "main_other_group_context_menu", {callback: onVendorContextMenu});
	initOtherContextMenu(obj);
	
	prepareParentChildsEventToHideContextMenu(file_tree);
	addSubMenuIconToParentChildsWithContextMenu(file_tree);
	prepareParentChildsEventOnClick(file_tree);
	
	//var selected_menu_properties = $("#selected_menu_properties");
}

function initDBContextMenu(elm, request_data) {
	var dbs_driver = elm.find("li i.db_driver");
	var dbs_diagram = elm.find("li i.db_diagram");
	var dbs_tables = elm.find("li i.db_tables");
	var dbs_views = elm.find("li i.db_views");
	var dbs_procedures = elm.find("li i.db_procedures");
	var dbs_functions = elm.find("li i.db_functions");
	var dbs_events = elm.find("li i.db_events");
	var dbs_triggers = elm.find("li i.db_triggers");
	var tables = elm.find("li i.table");
	var attributes = elm.find("li i.attribute");
	var views = elm.find("li i.db_view");
	var procedures = elm.find("li i.db_procedure");
	var functions = elm.find("li i.db_function");
	var events = elm.find("li i.db_event");
	var triggers = elm.find("li i.db_trigger");
	
	var db_data = $.isPlainObject(request_data) && $.isPlainObject(request_data["properties"]) && request_data["properties"].hasOwnProperty("db_data") ? request_data["properties"]["db_data"] : null;
	
	dbs_driver.parent().addClass("link");
	dbs_diagram.parent().addClass("link");
	dbs_tables.parent().addClass("link");
	dbs_views.parent().addClass("link");
	dbs_procedures.parent().addClass("link");
	dbs_functions.parent().addClass("link");
	dbs_events.parent().addClass("link");
	dbs_triggers.parent().addClass("link");
	tables.parent().addClass("link");
	attributes.parent().addClass("link");
	views.parent().addClass("link");
	procedures.parent().addClass("link");
	functions.parent().addClass("link");
	events.parent().addClass("link");
	triggers.parent().addClass("link");
	
	addLiContextMenu(dbs_driver.parent(), "db_driver_context_menu", {callback: onDBContextMenu});
	addLiContextMenu(dbs_diagram.parent(), "db_diagram_context_menu", {callback: onDBContextMenu});
	addLiContextMenu(dbs_tables.parent(), "db_driver_tables_context_menu", {callback: onDBContextMenu});
	addLiContextMenu(dbs_views.parent(), "db_driver_objects_context_menu", {callback: onDBContextMenu});
	addLiContextMenu(dbs_procedures.parent(), "db_driver_objects_context_menu", {callback: onDBContextMenu});
	addLiContextMenu(dbs_functions.parent(), "db_driver_objects_context_menu", {callback: onDBContextMenu});
	addLiContextMenu(dbs_events.parent(), "db_driver_objects_context_menu", {callback: onDBContextMenu});
	addLiContextMenu(dbs_triggers.parent(), "db_driver_objects_context_menu", {callback: onDBContextMenu});
	addLiContextMenu(tables.parent(), "db_driver_table_context_menu", {callback: onDBContextMenu});
	addLiContextMenu(attributes.parent(), "db_driver_table_attribute_context_menu", {callback: onDBContextMenu, db_data: db_data});
	addLiContextMenu(views.parent(), "db_driver_object_context_menu", {callback: onDBContextMenu});
	addLiContextMenu(procedures.parent(), "db_driver_object_context_menu", {callback: onDBContextMenu});
	addLiContextMenu(functions.parent(), "db_driver_object_context_menu", {callback: onDBContextMenu});
	addLiContextMenu(events.parent(), "db_driver_object_context_menu", {callback: onDBContextMenu});
	addLiContextMenu(triggers.parent(), "db_driver_object_context_menu", {callback: onDBContextMenu});
}

function initIbatisContextMenu(elm, request_data) {
	var folders = elm.find("li i.folder");
	var files = elm.find("li i.file");
	var queries = elm.find("li i.query");
	var maps = elm.find("li i.map");
	var undefined_files = elm.find("li i.undefined_file");
	var cms_commons_folder = elm.find("li i.cms_common");
	var cms_modules_folder = elm.find("li i.cms_module");
	var cms_programs_folder = elm.find("li i.cms_program");
	var cms_resources_folder = elm.find("li i.cms_resource");
	var zip_files = elm.find("li i.zip_file");
	
	folders.parent().addClass("link");
	files.parent().addClass("link");
	queries.parent().addClass("link");
	maps.parent().addClass("link");
	undefined_files.parent().addClass("link");
	cms_commons_folder.parent().addClass("link");
	cms_modules_folder.parent().addClass("link");
	cms_programs_folder.parent().addClass("link");
	cms_resources_folder.parent().addClass("link");
	zip_files.parent().addClass("link");

	addLiContextMenu(folders.parent(), "ibatis_group_context_menu", {callback: onIbatisContextMenu});
	addLiContextMenu(files.parent(), "ibatis_file_context_menu", {callback: onIbatisContextMenu});
	addLiContextMenu(queries.parent(), "item_context_menu", {callback: onIbatisContextMenu});
	addLiContextMenu(maps.parent(), "item_context_menu", {callback: onIbatisContextMenu});
	addLiContextMenu(undefined_files.parent(), "undefined_file_context_menu", {callback: onIbatisContextMenu});
	addLiContextMenu(cms_commons_folder.parent(), "ibatis_group_common_context_menu", {callback: onIbatisContextMenu});
	addLiContextMenu(cms_modules_folder.parent(), "cms_module_context_menu", {callback: onIbatisContextMenu});
	addLiContextMenu(cms_programs_folder.parent(), "ibatis_group_context_menu", {callback: onIbatisContextMenu});
	addLiContextMenu(cms_resources_folder.parent(), "ibatis_group_context_menu", {callback: onIbatisContextMenu});
	addLiContextMenu(zip_files.parent(), "zip_file_context_menu", {callback: onIbatisContextMenu});
}

function initHibernateContextMenu(elm, request_data) {
	var folders = elm.find("li i.folder");
	var files = elm.find("li i.file");
	var imports = elm.find("li i.import");
	var objs = elm.find("li i.obj");
	var queries = elm.find("li i.query");
	var relationships = elm.find("li i.relationship");
	var maps = elm.find("li i.map");
	var undefined_files = elm.find("li i.undefined_file");
	var cms_commons_folder = elm.find("li i.cms_common");
	var cms_modules_folder = elm.find("li i.cms_module");
	var cms_programs_folder = elm.find("li i.cms_program");
	var cms_resources_folder = elm.find("li i.cms_resource");
	var zip_files = elm.find("li i.zip_file");
	
	folders.parent().addClass("link");
	files.parent().addClass("link");
	imports.parent().addClass("link");
	objs.parent().addClass("link");
	queries.parent().addClass("link");
	relationships.parent().addClass("link");
	maps.parent().addClass("link");
	undefined_files.parent().addClass("link");
	cms_commons_folder.parent().addClass("link");
	cms_modules_folder.parent().addClass("link");
	cms_programs_folder.parent().addClass("link");
	cms_resources_folder.parent().addClass("link");
	zip_files.parent().addClass("link");

	addLiContextMenu(folders.parent(), "hibernate_group_context_menu", {callback: onHibernateContextMenu});
	addLiContextMenu(files.parent(), "hibernate_file_context_menu", {callback: onHibernateContextMenu});
	addLiContextMenu(imports.parent(), "hibernate_import_context_menu", {callback: onHibernateContextMenu});
	addLiContextMenu(objs.parent(), "hibernate_object_context_menu", {callback: onHibernateContextMenu});
	addLiContextMenu(queries.parent(), "item_context_menu", {callback: onHibernateContextMenu});
	addLiContextMenu(relationships.parent(), "item_context_menu", {callback: onHibernateContextMenu});
	addLiContextMenu(maps.parent(), "item_context_menu", {callback: onHibernateContextMenu});
	addLiContextMenu(undefined_files.parent(), "undefined_file_context_menu", {callback: onHibernateContextMenu});
	addLiContextMenu(cms_commons_folder.parent(), "hibernate_group_common_context_menu", {callback: onHibernateContextMenu});
	addLiContextMenu(cms_modules_folder.parent(), "cms_module_context_menu", {callback: onHibernateContextMenu});
	addLiContextMenu(cms_programs_folder.parent(), "hibernate_group_context_menu", {callback: onHibernateContextMenu});
	addLiContextMenu(cms_resources_folder.parent(), "hibernate_group_context_menu", {callback: onHibernateContextMenu});
	addLiContextMenu(zip_files.parent(), "zip_file_context_menu", {callback: onHibernateContextMenu});
	
	//Remove hbn_native nodes
	elm.find("li i.hbn_native").each(function(idx, node) {
		$(node).parent().parent().remove();
	});
}

function initLibContextMenu(elm, request_data) {
	var folders = elm.find("li i.folder");
	var files = elm.find("li i.file");
	var zip_files = elm.find("li i.zip_file");
	
	folders.parent().addClass("link");
	files.parent().addClass("link");
	zip_files.parent().addClass("link");

	addLiContextMenu(folders.parent(), "lib_group_context_menu", {callback: onLibContextMenu});
	addLiContextMenu(files.parent(), "lib_file_context_menu", {callback: onLibContextMenu});
	addLiContextMenu(zip_files.parent(), "zip_file_context_menu", {callback: onLibContextMenu});
}

function initDaoContextMenu(elm, request_data) {
	var folders = elm.find("li i.folder");
	var files = elm.find("li i.file");
	var objs_type = elm.find("li i.objtype");
	var objs_hibernate = elm.find("li i.hibernatemodel");
	var cms_commons_folder = elm.find("li i.cms_common");
	var cms_modules_folder = elm.find("li i.cms_module");
	var cms_programs_folder = elm.find("li i.cms_program");
	var cms_resources_folder = elm.find("li i.cms_resource");
	var zip_files = elm.find("li i.zip_file");
	
	folders.parent().addClass("link");
	files.parent().addClass("link");
	objs_type.parent().addClass("link");
	objs_hibernate.parent().addClass("link");
	cms_commons_folder.parent().addClass("link");
	cms_modules_folder.parent().addClass("link");
	cms_programs_folder.parent().addClass("link");
	cms_resources_folder.parent().addClass("link");
	zip_files.parent().addClass("link");

	addLiContextMenu(folders.parent(), "dao_group_context_menu", {callback: onDaoContextMenu});
	addLiContextMenu(files.parent(), "undefined_file_context_menu", {callback: onDaoContextMenu});
	addLiContextMenu(objs_type.parent(), "dao_file_context_menu", {callback: onDaoContextMenu});
	addLiContextMenu(objs_hibernate.parent(), "dao_file_context_menu", {callback: onDaoContextMenu});
	addLiContextMenu(cms_commons_folder.parent(), "dao_group_context_menu", {callback: onDaoContextMenu});
	addLiContextMenu(cms_modules_folder.parent(), "cms_module_context_menu", {callback: onDaoContextMenu});
	addLiContextMenu(cms_programs_folder.parent(), "dao_group_context_menu", {callback: onDaoContextMenu});
	addLiContextMenu(cms_resources_folder.parent(), "dao_group_context_menu", {callback: onDaoContextMenu});
	addLiContextMenu(zip_files.parent(), "zip_file_context_menu", {callback: onDaoContextMenu});
}

function initVendorContextMenu(elm, request_data) {
	var folders = elm.find("li i.folder");
	var files = elm.find("li i.file");
	var daos = elm.find("li i.dao");
	var code_workflow_editor = elm.find("li i.code_workflow_editor");
	var code_workflow_editor_tasks = elm.find("li i.code_workflow_editor_task");
	var layout_ui_editor = elm.find("li i.layout_ui_editor");
	var layout_ui_editor_widgets = elm.find("li i.layout_ui_editor_widget");
	var test_unit = elm.find("li i.test_unit");
	var zip_files = elm.find("li i.zip_file");
	
	folders.parent().addClass("link");
	files.parent().addClass("link");
	daos.parent().addClass("link");
	code_workflow_editor.parent().addClass("link");
	code_workflow_editor_tasks.parent().addClass("link");
	layout_ui_editor.parent().addClass("link");
	layout_ui_editor_widgets.parent().addClass("link");
	test_unit.parent().addClass("link");
	zip_files.parent().addClass("link");
	
	addLiContextMenu(folders.parent(), "vendor_group_context_menu", {callback: onVendorContextMenu});
	addLiContextMenu(files.parent(), "vendor_file_context_menu", {callback: onVendorContextMenu});
	addLiContextMenu(daos.parent(), "main_dao_group_context_menu", {callback: onDaoContextMenu});
	addLiContextMenu(code_workflow_editor.parent(), "main_vendor_group_context_menu", {callback: onVendorContextMenu});
	addLiContextMenu(code_workflow_editor_tasks.parent(), "main_vendor_group_context_menu", {callback: onVendorContextMenu});
	addLiContextMenu(layout_ui_editor.parent(), "main_vendor_group_context_menu", {callback: onVendorContextMenu});
	addLiContextMenu(layout_ui_editor_widgets.parent(), "main_vendor_group_context_menu", {callback: onVendorContextMenu});
	addLiContextMenu(test_unit.parent(), "main_test_unit_group_context_menu", {callback: onTestUnitContextMenu});
	addLiContextMenu(zip_files.parent(), "zip_file_context_menu", {callback: onVendorContextMenu});
}

function initTestUnitContextMenu(elm, request_data) {
	var folders = elm.find("li i.folder");
	var files = elm.find("li i.file");
	var test_unit_objs = elm.find("li i.test_unit_obj");
	var zip_files = elm.find("li i.zip_file");
	
	folders.parent().addClass("link");
	files.parent().addClass("link");
	test_unit_objs.parent().addClass("link");
	zip_files.parent().addClass("link");

	addLiContextMenu(folders.parent(), "test_unit_group_context_menu", {callback: onTestUnitContextMenu});
	addLiContextMenu(files.parent(), "undefined_file_context_menu", {callback: onTestUnitContextMenu});
	addLiContextMenu(test_unit_objs.parent(), "test_unit_obj_context_menu", {callback: onTestUnitContextMenu});
	addLiContextMenu(zip_files.parent(), "zip_file_context_menu", {callback: onTestUnitContextMenu});
}

function initOtherContextMenu(elm, request_data) {
	var folders = elm.find("li i.folder");
	var files = elm.find("li i.file");
	var zip_files = elm.find("li i.zip_file");
	
	folders.parent().addClass("link");
	files.parent().addClass("link");
	zip_files.parent().addClass("link");
	
	addLiContextMenu(folders.parent(), "vendor_group_context_menu", {callback: onVendorContextMenu});
	addLiContextMenu(files.parent(), "vendor_file_context_menu", {callback: onVendorContextMenu});
	addLiContextMenu(zip_files.parent(), "zip_file_context_menu", {callback: onVendorContextMenu});
}

function initContextContextMenu(elm, request_data) { //business logic
	var folders = elm.find("li i.folder");
	var files = elm.find("li i.file");
	var objs = elm.find("li i.service");
	var methods = elm.find("li i.method");
	var functions = elm.find("li i.function");
	var undefined_files = elm.find("li i.undefined_file");
	var cms_commons_folder = elm.find("li i.cms_common");
	var cms_modules_folder = elm.find("li i.cms_module");
	var cms_programs_folder = elm.find("li i.cms_program");
	var cms_resources_folder = elm.find("li i.cms_resource");
	var zip_files = elm.find("li i.zip_file");
	
	folders.parent().addClass("link");
	files.parent().addClass("link");
	objs.parent().addClass("link");
	methods.parent().addClass("link");
	functions.parent().addClass("link");
	undefined_files.parent().addClass("link");
	cms_commons_folder.parent().addClass("link");
	cms_modules_folder.parent().addClass("link");
	cms_programs_folder.parent().addClass("link");
	cms_resources_folder.parent().addClass("link");
	zip_files.parent().addClass("link");
	
	var vendor_frameworks_by_item_id = getVendorFrameworksByItemId(request_data);
	
	addLiContextMenu(folders.parent(), "business_logic_group_context_menu", {callback: onContextContextMenu, vendor_frameworks_by_item_id: vendor_frameworks_by_item_id});
	addLiContextMenu(files.parent(), "business_logic_file_context_menu", {callback: onContextContextMenu});
	addLiContextMenu(objs.parent(), "business_logic_object_context_menu", {callback: onContextContextMenu});
	addLiContextMenu(methods.parent(), "item_context_menu", {callback: onContextContextMenu});
	addLiContextMenu(functions.parent(), "item_context_menu", {callback: onContextContextMenu});
	addLiContextMenu(undefined_files.parent(), "undefined_file_context_menu", {callback: onContextContextMenu});
	addLiContextMenu(cms_commons_folder.parent(), "business_logic_group_common_context_menu", {callback: onContextContextMenu});
	addLiContextMenu(cms_modules_folder.parent(), "cms_module_context_menu", {callback: onContextContextMenu});
	addLiContextMenu(cms_programs_folder.parent(), "business_logic_group_context_menu", {callback: onContextContextMenu});
	addLiContextMenu(cms_resources_folder.parent(), "business_logic_group_context_menu", {callback: onContextContextMenu});
	addLiContextMenu(zip_files.parent(), "zip_file_context_menu", {callback: onContextContextMenu});
}

function initPresentationContextMenu(elm, request_data) {
	var is_entity_sub_folders = elm.parents("li").children("a").children("i.entities_folder").length > 0; //elm.parent().closest('[data-jstree=\'{"icon":"entities_folder"}\']').length > 0;
	var is_util_sub_folders = elm.parents("li").children("a").children("i.utils_folder").length > 0; //elm.parent().closest('[data-jstree=\'{"icon":"utils_folder"}\']').length > 0;
	var is_webroot_sub_folders = elm.parents("li").children("a").children("i.webroot_folder").length > 0; //elm.parent().closest('[data-jstree=\'{"icon":"webroot_folder"}\']').length > 0;
	
	var projects_common = elm.find("li i.project_common");
	var project_folders = elm.find("li i.project_folder");
	var projects = elm.find("li i.project");
	var folders = is_entity_sub_folders || is_util_sub_folders || is_webroot_sub_folders ? null : elm.find("li i.folder");
	var files = elm.find("li i.file");
	var entity_files = elm.find("li i.entity_file");
	var entities_folder = elm.find("li i.entities_folder");
	var entities_sub_folders = is_entity_sub_folders ? elm.find("li i.folder") : null;
	var view_files = elm.find("li i.view_file");
	var views_folder = elm.find("li i.views_folder");
	var template_files = elm.find("li i.template_file");
	var template_folders = elm.find("li i.template_folder");
	var templates_folder = elm.find("li i.templates_folder");
	var util_files = elm.find("li i.util_file");
	var utils_folder = elm.find("li i.utils_folder");
	var utils_sub_folders = is_util_sub_folders ? elm.find("li i.folder") : null;
	var config_files = elm.find("li i.config_file");
	var configs_folder = elm.find("li i.configs_folder");
	var controller_files = elm.find("li i.controller_file");
	var controllers_folder = elm.find("li i.controllers_folder");
	var webroot_folder = elm.find("li i.webroot_folder");
	var webroot_sub_folders = is_webroot_sub_folders ? elm.find("li i.folder") : null;
	var webroot_files = elm.find("li i.webroot_file");
	var css_files = elm.find("li i.css_file");
	var js_files = elm.find("li i.js_file");
	var zip_files = elm.find("li i.zip_file");
	var img_files = elm.find("li i.img_file");
	var undefined_files = elm.find("li i.undefined_file");
	var block_files = elm.find("li i.block_file");
	var blocks_folder = elm.find("li i.blocks_folder");
	var cms_modules_folder = elm.find("li i.cms_module");
	var cmses_folder = elm.find("li i.cms_folder");
	var wordpresses_folder = elm.find("li i.wordpress_folder");
	var wordpress_installations_folder = elm.find("li i.wordpress_installation_folder");
	var module_folders = elm.find("li i.module_folder");
	var module_files = elm.find("li i.module_file");
	var cache_files = elm.find("li i.cache_file");
	var caches_folder = elm.find("li i.caches_folder");
	var objs = elm.find("li i.class");
	var methods = elm.find("li i.method");
	var functions = elm.find("li i.function");
	
	projects_common.parent().addClass("link");
	project_folders.parent().addClass("link");
	projects.parent().addClass("link");
	folders && folders.parent().addClass("link");
	files.parent().addClass("link");
	entity_files.parent().addClass("link");
	entities_folder.parent().addClass("link");
	is_entity_sub_folders && entities_sub_folders.parent().addClass("link");
	view_files.parent().addClass("link");
	views_folder.parent().addClass("link");
	template_files.parent().addClass("link");
	template_folders.parent().addClass("link");
	templates_folder.parent().addClass("link");
	util_files.parent().addClass("link");
	utils_folder.parent().addClass("link");
	utils_sub_folders && utils_sub_folders.parent().addClass("link");
	config_files.parent().addClass("link");
	configs_folder.parent().addClass("link");
	controller_files.parent().addClass("link");
	controllers_folder.parent().addClass("link");
	webroot_folder.parent().addClass("link");
	is_webroot_sub_folders && webroot_sub_folders.parent().addClass("link");
	webroot_files.parent().addClass("link");
	css_files.parent().addClass("link");
	js_files.parent().addClass("link");
	zip_files.parent().addClass("link");
	img_files.parent().addClass("link");
	undefined_files.parent().addClass("link");
	block_files.parent().addClass("link");
	blocks_folder.parent().addClass("link");
	cms_modules_folder.parent().addClass("link");
	cmses_folder.parent().addClass("link");
	wordpresses_folder.parent().addClass("link");
	wordpress_installations_folder.parent().addClass("link");
	module_folders.parent().addClass("link");
	module_files.parent().addClass("link");
	cache_files.parent().addClass("link");
	caches_folder.parent().addClass("link");
	objs.parent().addClass("link");
	methods.parent().addClass("link");
	functions.parent().addClass("link");
	
	var vendor_frameworks_by_item_id = getVendorFrameworksByItemId(request_data);
	
	addLiContextMenu(projects_common.parent(), "presentation_project_common_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(project_folders.parent(), "presentation_project_group_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(projects.parent(), "presentation_project_context_menu", {callback: onPresentationContextMenu});
	folders && addLiContextMenu(folders.parent(), "presentation_group_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(files.parent(), "presentation_file_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(entity_files.parent(), "presentation_page_file_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(entities_folder.parent(), "presentation_main_pages_group_context_menu", {callback: onPresentationContextMenu});
	is_entity_sub_folders && addLiContextMenu(entities_sub_folders.parent(), "presentation_pages_group_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(view_files.parent(), "presentation_view_file_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(views_folder.parent(), "presentation_evc_group_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(template_files.parent(), "presentation_template_file_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(template_folders.parent(), "presentation_group_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(templates_folder.parent(), "presentation_main_templates_group_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(util_files.parent(), "presentation_util_file_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(utils_folder.parent(), "presentation_main_utils_group_context_menu", {callback: onPresentationContextMenu});
	utils_sub_folders && addLiContextMenu(utils_sub_folders.parent(), "presentation_utils_group_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(config_files.parent(), "presentation_file_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(configs_folder.parent(), "presentation_evc_group_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(controller_files.parent(), "presentation_file_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(controllers_folder.parent(), "presentation_evc_group_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(webroot_folder.parent(), "presentation_webroot_folder_context_menu", {callback: onPresentationContextMenu});
	is_webroot_sub_folders && addLiContextMenu(webroot_sub_folders.parent(), "presentation_group_context_menu", {callback: onPresentationContextMenu, vendor_frameworks_by_item_id: vendor_frameworks_by_item_id});
	addLiContextMenu(webroot_files.parent(), "presentation_webroot_file_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(css_files.parent(), "presentation_webroot_file_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(js_files.parent(), "presentation_webroot_file_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(zip_files.parent(), "zip_file_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(img_files.parent(), "presentation_webroot_file_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(undefined_files.parent(), "presentation_webroot_file_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(block_files.parent(), "presentation_block_file_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(blocks_folder.parent(), "presentation_evc_group_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(cms_modules_folder.parent(), "cms_module_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(cmses_folder.parent(), "presentation_group_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(wordpresses_folder.parent(), "presentation_project_common_wordpress_group_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(wordpress_installations_folder.parent(), "presentation_project_common_wordpress_group_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(module_folders.parent(), "presentation_group_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(module_files.parent(), "undefined_file_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(cache_files.parent(), "presentation_cache_file_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(caches_folder.parent(), "presentation_cache_group_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(objs.parent(), "presentation_util_object_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(methods.parent(), "item_context_menu", {callback: onPresentationContextMenu});
	addLiContextMenu(functions.parent(), "item_context_menu", {callback: onPresentationContextMenu});
}

function addLiContextMenu(target, context_menu_id, options) {
	target.addcontextmenu(context_menu_id, options);
	
	//this will be used in the presentation/list.js
	target.data("context_menu_id", context_menu_id);
	target.data("context_menu_options", options);
}

function onDBContextMenu(target, contextmenu, originalEvent) {
	var a = $(originalEvent.target.parentNode);
	
	contextmenu.find(".add_auto_table a").attr("add_auto_table_url", a.attr("add_auto_table_url"));
	contextmenu.find(".add_manual_table a").attr("add_manual_table_url", a.attr("add_manual_table_url"));
	contextmenu.find(".add_table_with_ai a").attr("add_table_with_ai_url", a.attr("add_table_with_ai_url"));
	contextmenu.find(".add_attribute a").attr("add_attribute_url", a.attr("add_attribute_url"));
	contextmenu.find(".rename a").attr("rename_url", a.attr("rename_url"));
	contextmenu.find(".remove a").attr("remove_url", a.attr("remove_url"));
	contextmenu.find(".db_dump a").attr("db_dump_url", a.attr("db_dump_url"));
	contextmenu.find(".phpmyadmin a").attr("phpmyadmin_url", a.attr("phpmyadmin_url"));
	contextmenu.find(".manage_records a").attr("manage_records_url", a.attr("manage_records_url"));
	contextmenu.find(".manage_indexes a").attr("manage_indexes_url", a.attr("manage_indexes_url"));
	contextmenu.find(".edit_diagram a").attr("edit_diagram_url", a.attr("edit_diagram_url"));
	contextmenu.find(".create_diagram_sql a").attr("create_diagram_sql_url", a.attr("create_diagram_sql_url"));
	contextmenu.find(".create_sql a").attr("create_sql_url", a.attr("create_sql_url"));
	contextmenu.find(".import_data a").attr("import_data_url", a.attr("import_data_url"));
	contextmenu.find(".export_data a").attr("export_data_url", a.attr("export_data_url"));
	contextmenu.find(".primary_key a, .null a, .type a").attr("set_property_url", a.attr("set_property_url"));
	
	contextmenu.find("a").attr("execute_sql_url", a.attr("execute_sql_url")); //very important bc the manageDBTableAction method uses this attribute, so we must have this attribute set in all the menu items.
	
	//toggle phpmyadmin
	var show_phpmyadmin = a.parent().hasClass("db_driver_mysql");
	
	if (show_phpmyadmin)
		contextmenu.find(".phpmyadmin").show();
	else
		contextmenu.find(".phpmyadmin").hide();
	
	//If is DB table attribute, prepare correspondent menus
	if (a.children("i.attribute")) {
		var li = a.parent();
		var properties_id = a.attr("properties_id");
		
		if (properties_id && menu_item_properties.hasOwnProperty(properties_id) && menu_item_properties[properties_id]) {
			var attribute_properties = menu_item_properties[properties_id];
			
			if (attribute_properties) {
				//activate primary key menu
				if (attribute_properties["primary_key"]) 
					contextmenu.find(".primary_key").addClass("checked");
				else
					contextmenu.find(".primary_key").removeClass("checked");
				
				//activate null menu
				if (attribute_properties["null"])
					contextmenu.find(".null").addClass("checked");
				else
					contextmenu.find(".null").removeClass("checked");
				
				//prepare length input
				var input = contextmenu.find(".type a input");
				input.val(attribute_properties["length"]);
				
				//get correspondent DB Driver types and set contextmenu with these types.
				var context_menu_options = target.data("context_menu_options");
				var db_data = context_menu_options["db_data"];
				var column_types = null, column_simple_types = null, column_mandatory_length_types = null, column_types_ignored_props = null;
				
				if ($.isPlainObject(db_data)) {
					column_types = db_data["column_types"];
					column_simple_types = db_data["column_simple_types"];
					column_mandatory_length_types = db_data["column_mandatory_length_types"];
					column_types_ignored_props = db_data["column_types_ignored_props"];
				}
				
				var select = contextmenu.find(".type a select");
				select.find("option:not([disabled])").remove();
				select.find(".dynamic").remove();
				
				var select_checker = function(set_mandatory_length) {
					var type = select.val();
					var length = input.val();
					var simple_props = null;
					
					if (type && column_simple_types && $.isPlainObject(column_simple_types) && column_simple_types.hasOwnProperty(type)) {
						simple_props = column_simple_types[type];
						
						if (simple_props["type"]) {
							type = simple_props["type"];
							
							if ($.isArray(type)) {
								var original_type = select.find("option:selected").attr("original_type");
								
								//check if original type previous set belongs to the any simple types and if yes, stays with the original value, bc it was not changed. This is very important, bc if we have an attribute with a native type, which was converted to a simple type, when we convert it to the native type again, we must stay with original value. Otherwise we are changing automatically the types of the attributes without the consent of the user. The original type is is very important!!!
								if (original_type && $.inArray(original_type, type) != -1)
									type = original_type;
								else
									type = type[0];
							}
						}
					}
					
					select.data("simple_props", simple_props);
					
					var hide_length = $.isPlainObject(column_types_ignored_props) && column_types_ignored_props.hasOwnProperty(type) && $.isArray(column_types_ignored_props[type]) && $.inArray("length", column_types_ignored_props[type]) != -1;
					
					if (hide_length) {
						input.attr("disabled", "disabled");
						input.val("");
					}
					else {
						input.removeAttr("disabled");
						
						if (set_mandatory_length) {
							//if length field is empty
							if (length.replace(/\s+/g, "") == "") {
								var mandatory_length = $.isPlainObject(simple_props) && simple_props.hasOwnProperty("length") ? simple_props["length"] : (
									$.isPlainObject(column_mandatory_length_types) && column_mandatory_length_types.hasOwnProperty(type) ? column_mandatory_length_types[type] : null
								);
								
								if ($.isNumeric(mandatory_length) || mandatory_length)
									input.val(mandatory_length);
							}
							//or if the previous primitive type is equal to the new primitive type and if the type is a simple type with length.
							/*else if (type == attribute_properties["type"] && $.isPlainObject(simple_props) && simple_props.hasOwnProperty("length") && ($.isNumeric(simple_props["length"]) || simple_props["length"]))
								input.val(simple_props["length"]);*/
							//or if the type is a simple type with length.
							else if ($.isPlainObject(simple_props) && simple_props.hasOwnProperty("length") && ($.isNumeric(simple_props["length"]) || simple_props["length"]))
								input.val(simple_props["length"]);
							
							//console.log(attribute_properties);
							//console.log(type);
							//console.log(simple_props);
						}
					}
				};
				
				//convert type into simple type if apply
				var current_attribute_type = attribute_properties["type"];
				var original_attribute_type = attribute_properties["type"];
				
				if (column_simple_types && $.isPlainObject(column_simple_types))
					for (var simple_type in column_simple_types) {
						var simple_props = column_simple_types[simple_type];
						
						if ($.isPlainObject(simple_props)) {
							var is_simple_type = true;
							
							for (var prop_name in simple_props) 
								if (prop_name != "label") {
									var props_value = simple_props[prop_name];
									
									if (!$.isArray(props_value)) //if prop_name == "type" then the props_value maight be an array
										props_value = [props_value];
									
									var sub_exists = false;
									
									for (var j = 0; j < props_value.length; j++) {
										if (prop_name == "name") { //prop_name=="name" is a different property that will check if name contains the searching string.
											if (!attribute_properties[prop_name]) {
												sub_exists = true;
												break;
											}
											else if ($.isArray(attribute_properties[prop_name])) {
												for (var w = 0; w < attribute_properties[prop_name].length; w++) {
													var n = attribute_properties[prop_name][w];
													
													if (("" + n).toLowerCase().indexOf( props_value[j].toLowerCase() ) != -1) {
														sub_exists = true;
														w = attribute_properties[prop_name].length;
													}
												}
												
												if (sub_exists = true)
													break;
											}
											else if (("" + attribute_properties[prop_name]).toLowerCase().indexOf( props_value[j].toLowerCase() ) != -1) {
												sub_exists = true;
												break;
											}
											
										}
										else if (props_value[j] == attribute_properties[prop_name] || (!props_value[j] && !attribute_properties[prop_name])) { //if both values are false (null or empty string or 0), then the values are the same
											sub_exists = true;
											break;
										}
									}
									
									if (!sub_exists) {
										is_simple_type = false;
										break;
									}
								}
							
							if (is_simple_type) {
								current_attribute_type = simple_type;
								break;
							}
						}
					}
				
				//prepare types html
				var exists = false;
				var types = '<option class="dynamic" disabled></option>';
				
				if (column_simple_types && $.isPlainObject(column_simple_types)) {
					types += '<optgroup class="dynamic" label="Simple Types">';
					
					$.each(column_simple_types, function(type_id, type_props) {
						var type_label = type_props["label"] ? type_props["label"] : type_id;
						var title = type_label + ":";
						var original_type_html = original_attribute_type && $.isArray(type_props["type"]) && $.inArray(original_attribute_type, type_props["type"]) != -1 ? ' original_type="' + original_attribute_type + '"' : '';
						
						for (var k in type_props)
							if (k != "label")
								title += "\n- " + k + ": " + type_props[k];
						
						types += '<option value="' + type_id + '"' + (current_attribute_type == type_id ? " selected" : "") + ' title="' + title + '"' + original_type_html + '>' + type_label + '</option>';
					});
					
					types += '</optgroup>';
					
					if (column_simple_types.hasOwnProperty(current_attribute_type))
						exists = true;
				}
				
				if (column_types && $.isPlainObject(column_types)) {
					types += '<option class="dynamic" disabled></option>'
						  + '<optgroup class="dynamic" label="Native Types">';
					
					$.each(column_types, function(type_id, type_label) {
						types += '<option value="' + type_id + '"' + (current_attribute_type == type_id ? " selected" : "") + '>' + type_label + '</option>';
					});
					
					types += '</optgroup>';
					
					if (column_types.hasOwnProperty(current_attribute_type))
						exists = true;
				}
				
				if (!exists)
					types += '<option class="dynamic" disabled></option>'
						  + '<option value="' + current_attribute_type + '" selected>' + current_attribute_type + '</option>';
				
				select.append(types);
				select_checker(false);
				
				//prepare type and length fields events bc the firefox runs the mouseleave event when we open the select boxes.
				if (select.data("is_inited") != 1) {
					select.data("is_inited", 1);
					var span = select.parent().children("span");
					var timeout_id = null;
					var key_up_timeout_id = null;
					var change_timeout_id = null;
					
					var execute_event = function(secs, activate_mouseleave) {
						change_timeout_id && clearTimeout(change_timeout_id);
						key_up_timeout_id && clearTimeout(key_up_timeout_id);
						timeout_id && clearTimeout(timeout_id);
						
						timeout_id = setTimeout(function() {
							var type = select.val();
							var length = input.val();
							
							if (type != attribute_properties["type"] || length != attribute_properties["length"])
								span.trigger("click");
							
							if (activate_mouseleave)
								contextmenu.bind('mouseleave', function(e) {
									MyContextMenu.hideContextMenu(contextmenu);
								});
						}, secs);
					};
					
					select.bind("change", function(e) {
						//console.log("select change");
						select_checker(true);
						
						change_timeout_id && clearTimeout(change_timeout_id);
						
						change_timeout_id = setTimeout(function() {
							select.trigger("blur");
						}, 500);
					});
					select.bind("focus", function(e) {
						//console.log("select focus");
						contextmenu.unbind('mouseleave');
						timeout_id && clearTimeout(timeout_id);
						change_timeout_id && clearTimeout(change_timeout_id);
						key_up_timeout_id && clearTimeout(key_up_timeout_id);
					});
					select.bind("blur", function(e) {
						//console.log("select blur");
						execute_event(500, true);
					});
					
					input.bind("focus", function(e) {
						//console.log("input focus");
						contextmenu.unbind('mouseleave');
						timeout_id && clearTimeout(timeout_id);
						change_timeout_id && clearTimeout(change_timeout_id);
						key_up_timeout_id && clearTimeout(key_up_timeout_id);
					});
					input.bind("blur", function(e) {
						//console.log("input blur");
						execute_event(500, true);
					});
					input.bind("keyup", function(e) { //TOOD: This is wrong
						//console.log("input keyup");
						key_up_timeout_id && clearTimeout(key_up_timeout_id);
						
						key_up_timeout_id = setTimeout(function() {
							input.trigger("blur");
						}, 1500);
					});
				}
			}
		}
	}
	
	return onContextMenu(target, contextmenu, originalEvent);
}

function onIbatisContextMenu(target, contextmenu, originalEvent) {
	var a = $(originalEvent.target.parentNode);
	
	contextmenu.find(".create_automatically a").attr("create_automatically_url", a.attr("create_automatically_url"));
	contextmenu.find(".query a").attr("add_query_url", a.attr("add_query_url"));
	contextmenu.find(".parameter_map a").attr("add_parameter_map_url", a.attr("add_parameter_map_url"));
	contextmenu.find(".result_map a").attr("add_result_map_url", a.attr("add_result_map_url"));
	contextmenu.find(".manage_includes a").attr("manage_includes_url", a.attr("manage_includes_url"));
	
	return onContextMenu(target, contextmenu, originalEvent);
}

function onHibernateContextMenu(target, contextmenu, originalEvent) {
	var a = $(originalEvent.target.parentNode);
	
	contextmenu.find(".create_automatically a").attr("create_automatically_url", a.attr("create_automatically_url"));
	contextmenu.find(".obj a").attr("add_obj_url", a.attr("add_obj_url"));
	contextmenu.find(".query a").attr("add_query_url", a.attr("add_query_url"));
	contextmenu.find(".relationship a").attr("add_relationship_url", a.attr("add_relationship_url"));
	contextmenu.find(".parameter_map a").attr("add_parameter_map_url", a.attr("add_parameter_map_url"));
	contextmenu.find(".result_map a").attr("add_result_map_url", a.attr("add_result_map_url"));
	contextmenu.find(".manage_includes a").attr("manage_includes_url", a.attr("manage_includes_url"));
	
	return onContextMenu(target, contextmenu, originalEvent);
}

function onContextContextMenu(target, contextmenu, originalEvent) {
	var a = $(originalEvent.target.parentNode);
	var create_laravel_url = a.attr("create_laravel_url");
	var create_automatically_url = a.attr("create_automatically_url");
	
	if (create_laravel_url)
		contextmenu.children(".create_laravel").show();
	else
		contextmenu.children(".create_laravel").hide().prev(".line_break").hide();
	
	if (create_automatically_url)
		contextmenu.children(".create_automatically").show();
	else
		contextmenu.children(".create_automatically").hide().prev(".line_break").hide();
	
	contextmenu.find(".create_laravel a").attr("create_laravel_url", create_laravel_url);
	contextmenu.find(".laravel_preview a").attr("laravel_preview_url", a.attr("laravel_preview_url"));
	contextmenu.find(".laravel_terminal a").attr("laravel_terminal_url", a.attr("laravel_terminal_url"));
	contextmenu.find(".create_automatically a").attr("create_automatically_url", create_automatically_url);
	contextmenu.find(".service_obj a").attr("add_service_obj_url", a.attr("add_service_obj_url"));
	contextmenu.find(".service_obj a").attr("save_service_obj_url", a.attr("save_service_obj_url"));
	contextmenu.find(".service_obj a").attr("edit_service_obj_url", a.attr("edit_service_obj_url"));
	contextmenu.find(".service_function a").attr("add_service_func_url", a.attr("add_service_func_url"));
	contextmenu.find(".service_function a").attr("save_service_func_url", a.attr("save_service_func_url"));
	contextmenu.find(".service_function a").attr("edit_service_func_url", a.attr("edit_service_func_url"));
	contextmenu.find(".service_method a").attr("add_service_method_url", a.attr("add_service_method_url"));
	contextmenu.find(".service_method a").attr("save_service_method_url", a.attr("save_service_method_url"));
	contextmenu.find(".service_method a").attr("edit_service_method_url", a.attr("edit_service_method_url"));
	contextmenu.find(".manage_includes a").attr("manage_includes_url", a.attr("manage_includes_url"));
	
	onVendorFrameworkContextMenu(target, contextmenu, originalEvent);
	
	return onContextMenu(target, contextmenu, originalEvent);
}

function onPresentationContextMenu(target, contextmenu, originalEvent) {
	var a = $(originalEvent.target.parentNode);
	var create_laravel_url = a.attr("create_laravel_url");
	var create_automatically_url = a.attr("create_automatically_url");
	var create_uis_diagram_url = a.attr("create_uis_diagram_url");
	var inside_webroot = a.parent().closest('[data-jstree=\'{"icon":"webroot_folder"}\'], .mytree').is('[data-jstree=\'{"icon":"webroot_folder"}\']');
	
	if (create_laravel_url)
		contextmenu.children(".create_laravel").show();
	else
		contextmenu.children(".create_laravel").hide().prev(".line_break").hide();
	
	if (create_automatically_url)
		contextmenu.children(".create_automatically").show();
	else
		contextmenu.children(".create_automatically").hide();
	
	if (create_uis_diagram_url)
		contextmenu.children(".create_uis_diagram").show();
	else
		contextmenu.children(".create_uis_diagram").hide();
	
	if (!create_automatically_url && !create_uis_diagram_url)
		contextmenu.children(".create_automatically").prev(".line_break").hide();
		
	if (inside_webroot)
		contextmenu.children(".open_file").show();
	else
		contextmenu.children(".open_file").hide();
	
	contextmenu.find(".manage_wordpress a").attr("manage_wordpress_url", a.attr("manage_wordpress_url"));
	
	contextmenu.find(".create_laravel a").attr("create_laravel_url", create_laravel_url);
	contextmenu.find(".laravel_preview a").attr("laravel_preview_url", a.attr("laravel_preview_url"));
	contextmenu.find(".laravel_terminal a").attr("laravel_terminal_url", a.attr("laravel_terminal_url"));
	contextmenu.find(".create_automatically a").attr("create_automatically_url", create_automatically_url);
	contextmenu.find(".create_uis_diagram a").attr("create_uis_diagram_url", create_uis_diagram_url);
	contextmenu.find(".install_template a").attr("install_template_url", a.attr("install_template_url"));
	contextmenu.find(".convert_template a").attr("convert_template_url", a.attr("convert_template_url"));
	contextmenu.find(".generate_template_with_ai a").attr("generate_template_with_ai_url", a.attr("generate_template_with_ai_url"));
	contextmenu.find(".add_project a").attr("create_project_url", a.attr("create_project_url"));
	contextmenu.find(".edit_project_global_variables a").attr("edit_project_global_variables_url", a.attr("edit_project_global_variables_url"));
	contextmenu.find(".edit_config a").attr("edit_config_url", a.attr("edit_config_url"));
	contextmenu.find(".edit_init a").attr("edit_init_url", a.attr("edit_init_url"));
	contextmenu.find(".manage_users a").attr("manage_users_url", a.attr("manage_users_url"));
	contextmenu.find(".manage_references a").attr("manage_references_url", a.attr("manage_references_url"));
	contextmenu.find(".open_file a").attr("open_url", a.attr("open_url"));
	contextmenu.find(".view_project a").attr("view_project_url", a.attr("view_project_url"));
	contextmenu.find(".test_project a").attr("test_project_url", a.attr("test_project_url"));
	contextmenu.find(".install_program a").attr("install_program_url", a.attr("install_program_url"));
	
	contextmenu.find(".class_obj a").attr("add_class_obj_url", a.attr("add_class_obj_url"));
	contextmenu.find(".class_obj a").attr("save_class_obj_url", a.attr("save_class_obj_url"));
	contextmenu.find(".class_obj a").attr("edit_class_obj_url", a.attr("edit_class_obj_url"));
	contextmenu.find(".class_function a").attr("add_class_func_url", a.attr("add_class_func_url"));
	contextmenu.find(".class_function a").attr("save_class_func_url", a.attr("save_class_func_url"));
	contextmenu.find(".class_function a").attr("edit_class_func_url", a.attr("edit_class_func_url"));
	contextmenu.find(".class_method a").attr("add_class_method_url", a.attr("add_class_method_url"));
	contextmenu.find(".class_method a").attr("save_class_method_url", a.attr("save_class_method_url"));
	contextmenu.find(".class_method a").attr("edit_class_method_url", a.attr("edit_class_method_url"));
	contextmenu.find(".manage_includes a").attr("manage_includes_url", a.attr("manage_includes_url"));
	
	onVendorFrameworkContextMenu(target, contextmenu, originalEvent);
	
	return onContextMenu(target, contextmenu, originalEvent);
}

function onLibContextMenu(target, contextmenu, originalEvent) {
	var a = $(originalEvent.target.parentNode);
	
	contextmenu.find(".manage_docbook a").attr("manage_docbook_url", a.attr("manage_docbook_url"));
	contextmenu.find(".view_docbook a").attr("view_docbook_url", a.attr("view_docbook_url"));
	contextmenu.find(".view_code a").attr("view_code_url", a.attr("view_code_url"));
	
	return onContextMenu(target, contextmenu, originalEvent);
}

function onDaoContextMenu(target, contextmenu, originalEvent) {
	var a = $(originalEvent.target.parentNode);
	
	contextmenu.find(".hbnt_obj a").attr("create_dao_hibernate_model_url", a.attr("create_dao_hibernate_model_url"));
	contextmenu.find(".objt_obj a").attr("create_dao_obj_type_url", a.attr("create_dao_obj_type_url"));
	
	return onContextMenu(target, contextmenu, originalEvent);
}

function onVendorContextMenu(target, contextmenu, originalEvent) {
	var a = $(originalEvent.target.parentNode);
	
	return onContextMenu(target, contextmenu, originalEvent);
}

function onTestUnitContextMenu(target, contextmenu, originalEvent) {
	var a = $(originalEvent.target.parentNode);
	
	contextmenu.find(".test_unit_obj a").attr("create_test_unit_obj_url", a.attr("create_test_unit_obj_url"));
	contextmenu.find(".manage_test_units a").attr("manage_test_units_url", a.attr("manage_test_units_url"));
	
	return onContextMenu(target, contextmenu, originalEvent);
}

function onVendorFrameworkContextMenu(target, contextmenu, originalEvent) {
	//toggle vendor framework menus
	var a = $(originalEvent.target.parentNode);
	var context_menu_options = target.data("context_menu_options");
	var vendor_frameworks_by_item_id = $.isPlainObject(context_menu_options) ? context_menu_options["vendor_frameworks_by_item_id"] : null;
	var properties_id= a.attr("properties_id");
	var vendor_framework = properties_id && $.isPlainObject(vendor_frameworks_by_item_id) ? vendor_frameworks_by_item_id[properties_id] : "";
	
	if (vendor_framework)
		contextmenu.attr("vendor_framework", vendor_framework)
	else
		contextmenu.removeAttr("vendor_framework");
}

function onContextMenu(target, contextmenu, originalEvent) {
	//console.log(target);
	//console.log(contextmenu);
	//console.log(originalEvent);
	
	if (originalEvent.preventDefault) originalEvent.preventDefault(); 
	else originalEvent.returnValue = false;
	
	var a = $(originalEvent.target.parentNode);
	
	contextmenu.find(".edit a, .edit_new a").attr("edit_url", a.attr("edit_url"));
	contextmenu.find(".edit_raw_file a, .edit_raw_file_new a").attr("edit_raw_file_url", a.attr("edit_raw_file_url"));
	contextmenu.find(".rename a").attr("rename_url", a.attr("rename_url"));
	contextmenu.find(".remove a").attr("remove_url", a.attr("remove_url"));
	contextmenu.find(".create_folder a").attr("create_url", a.attr("create_url"));
	contextmenu.find(".create_file a").attr("create_url", a.attr("create_url"));
	contextmenu.find(".upload a").attr("upload_url", a.attr("upload_url"));
	contextmenu.find(".download a").attr("download_url", a.attr("download_url"));
	contextmenu.find(".copy a").attr("copy_url", a.attr("copy_url"));
	contextmenu.find(".cut a").attr("cut_url", a.attr("cut_url"));
	contextmenu.find(".paste a").attr("paste_url", a.attr("paste_url"));
	contextmenu.find(".diff_file a").attr("diff_file_url", a.attr("diff_file_url"));
	contextmenu.find(".manage_modules a").attr("manage_modules_url", a.attr("manage_modules_url"));
	contextmenu.find(".zip a").attr("zip_url", a.attr("zip_url"));
	contextmenu.find(".unzip a").attr("unzip_url", a.attr("unzip_url"));
	
	var properties_id = a.attr("properties_id");
	var properties_menu_item = contextmenu.find(".properties");
	var properties_prev_menu_item = properties_menu_item.prev("li");
	var properties_next_menu_item = properties_menu_item.next("li");
	var is_properties_prev_menu_item_separator = properties_prev_menu_item.is(".line_break") && (properties_next_menu_item.length == 0 || properties_next_menu_item.is(".line_break"));
	
	if (properties_id) {
		properties_menu_item.children("a").attr("properties_id", properties_id);
		properties_menu_item.show();
		
		if (is_properties_prev_menu_item_separator)
			properties_prev_menu_item.show();
	}
	else {
		properties_menu_item.hide();
		
		if (is_properties_prev_menu_item_separator)
			properties_prev_menu_item.hide();
	}
	
	mytree.deselectAll();
	
	var new_target_id = originalEvent.target.parentNode.parentNode.id;
	
	if (new_target_id) {
		contextmenu.attr("last_selected_node_id", new_target_id);
		mytree.selectNode(new_target_id);
		return true;
	}
	
	return false;
}

function getVendorFrameworksByItemId(request_data) {
	var items = {};
	
	if ($.isPlainObject(request_data))
		for (var file_key in request_data) {
			var file_data = request_data[file_key];
			
			if ($.isPlainObject(file_data) && $.isPlainObject(file_data["properties"]) && file_data["properties"].hasOwnProperty("vendor_framework") && file_data["properties"]["vendor_framework"]) {
				var item_id = file_data["properties"]["item_id"];
				var vendor_framework = file_data["properties"]["vendor_framework"];
				
				items[item_id] = vendor_framework;
			}
		}
	
	return items;
}

//Any change in this method should be done too in the initDBTablesSorting method
function initFilesDragAndDrop(elm) {
	var iframe = $("#right_panel > iframe");
	
	if (iframe[0]) {
		var scroll_parent = elm.parent().closest(".scroll");
		var iframe_win = iframe[0].contentWindow;
		var iframe_doc = iframe_win ? iframe_win.document : null;
		var iframe_offset = iframe.offset();
		var iframe_droppable_elm = null;
		var iframe_droppable_over_class = "drop_hover dragging_task task_droppable_over";
		var available_iframe_droppables_selectors = ".droppable, .tasks_flow, .connector_overlay_add_icon"; //".droppable" is related with the LayoutUIEditor, ".tasks_flow" is related with workflows and ".connector_overlay_add_icon" is related with Logic workflows.
		var PtlLayoutUIEditor = null;
		var is_main_navigator_reverse = $("body").hasClass("main_navigator_reverse");
		var is_in_right_panel = false;
		
		var folders_selector = "i.folder, i.template_folder";
		var files_selector = "i.file, i.objtype, i.hibernatemodel, i.config_file, i.controller_file, i.entity_file, i.view_file, i.template_file, i.util_file, i.block_file, i.module_file, i.undefined_file, i.js_file, i.css_file, i.img_file, i.zip_file";
		var droppables_selector = "i.folder, i.entities_folder, i.views_folder, i.templates_folder, i.template_folder, i.utils_folder, i.webroot_folder, i.modules_folder, i.configs_folder, i.cms_common, i.cms_module, i.cms_program, i.cms_resource";
		var draggables_selector = folders_selector + ", " + files_selector + ", .query, .relationship, .obj, .class, .method, .function";
		
		var left_panel_droppable_handler = function(event, ui_obj) {
			var file_li = $(this);
			var is_file_li_ul = file_li.is("ul") && file_li.parent().is("li");
			
			if (is_file_li_ul)
				file_li = file_li.parent();
			
			var file_li_a = file_li.children("a");
			var item = ui_obj.draggable;
			var a = item.children("a");
			
			file_li.removeClass("drop_hover");
			file_li.parents().removeClass("drop_hover");
			
			if (item.is(".jstree-node")) { //be sure that the draggable item is a jstree node, bc it can be the "div#hide_panel" from the left navigator.
				if (a.children(files_selector + ", " + folders_selector).length == 0)
					StatusMessageHandler.showError("Sorry, droppable not allowed...");
				else if (file_li_a.children(droppables_selector).length == 0)
					StatusMessageHandler.showError("Sorry, droppable not allowed...");
				else if (a.attr("id") != file_li_a.attr("id")) { //if file is not it-self
					var originalEvent = event || window.event;
					var is_ctrl_key = originalEvent && (originalEvent.ctrlKey || originalEvent.keyCode == 65);
					var action = is_ctrl_key ? "copy" : "cut";
					
					copy_or_cut_tree_node_id = item.attr("id");
					copy_or_cut_action = action;
					file_to_copy_or_cut = a.attr(action == "cut" ? "cut_url" : "copy_url");
					
					var dummy_menu = $('<ul last_selected_node_id="' + file_li.attr("id") + '"><li><a paste_url="' + file_li_a.attr("paste_url") + '"></a></li></ul>'); //emulate the menu item
					var a = dummy_menu.find("li a");
					
					manageFile(a[0], 'paste_url', 'paste', function() {
						dummy_menu.remove();
					});
				}
			}
			//else //shows the item which could be the div#hide_panel from the left navigator.
			//	console.log(item);
			
			//do not add "return false" otherwise the draggable will stop working for next iteractions
		};
		
		var right_panel_droppable_handler = function(event, ui_obj, tree_node, helper_clone) {
			//console.log(event);
			//console.log(ui_obj);
			
			var j_iframe_droppable_elm = $(iframe_droppable_elm);
			var li = ui_obj.helper;
			var li_a = li.children("a");
			
			if (li.is(".jstree-node")) { //be sure that the draggable item is a jstree node, bc it can be the "div#hide_panel" from the left navigator.
				//if dragged item is a table
				if (li_a.children("i.query, i.relationship").length > 0) { //ibatis query => create callibatisquery or callhibernatemethod task
					//check if query belongs to a hibernate obj
					var parent_li = li.parent().parent();
					var is_relationship = li_a.children("i.relationship").length > 0;
					var is_hbn_obj = is_relationship || parent_li.children("a").children("i.obj").length > 0;
					var func = is_hbn_obj ? iframe_win.CallHibernateMethodTaskPropertyObj : iframe_win.CallIbatisQueryTaskPropertyObj;
					var task_tag = is_hbn_obj ? "callhibernatemethod" : "callibatisquery";
					
					if (typeof func == "object") {
						if (iframe_droppable_elm) {
							var tasks_menu = j_iframe_droppable_elm.parent().closest(".taskflowchart").children(".tasks_menu");
							var task_menu = tasks_menu.find(".task.task_menu.task_" + task_tag);
							var task_type = task_menu.attr("type");
							
							if (task_type) {
								var edit_url = li_a.attr("edit_url");
								var bean_name = getParameterByName(edit_url, "bean_name");
								var bean_ui_props = bean_name && iframe_win.main_layers_properties && iframe_win.main_layers_properties.hasOwnProperty(bean_name) && iframe_win.main_layers_properties[bean_name].hasOwnProperty("ui") ? main_layers_properties[bean_name]["ui"] : null;
								
								if (bean_ui_props) {
									var query_type = getParameterByName(edit_url, "query_type");
									
									if (is_relationship || $.inArray(query_type, ["insert", "update", "delete", "procedure", "select"]) != -1) {
										var hbn_obj = getParameterByName(edit_url, "obj");
										var query_id = getParameterByName(edit_url, "query_id");
										var task_label = (query_type == "select" ? "Get" : "Set") + " query " + (hbn_obj ? hbn_obj + "." : "") + query_id;
										
										onChooseWorkflowTask(event, iframe_droppable_elm, iframe_win, iframe_offset, task_type, task_label, function(task_id) {
											if (is_hbn_obj)
												onChooseWorkflowCallHibernateMethodTask(iframe_win, li, task_id);
											else
												onChooseWorkflowCallIbatisQueryTask(iframe_win, li, task_id);
										});
									}
									else
										iframe_win.taskFlowChartObj.StatusMessage.showError("This diagram doesn't allow the drop action for this element.");
								}
								else
									iframe_win.taskFlowChartObj.StatusMessage.showError("This diagram doesn't allow the drop action for this element.");
							}
							else
								iframe_win.taskFlowChartObj.StatusMessage.showError("This diagram doesn't allow the drop action for this element.");
						}
						else
							iframe_win.taskFlowChartObj.StatusMessage.showError("Please drop element inside of diagram");
					}
					else
						StatusMessageHandler.showError("Sorry, droppable not allowed...");
				}
				else if (li_a.children("i.obj").length > 0) { //hibernate obj => create callhibernateobject task
					if (typeof iframe_win.CallHibernateObjectTaskPropertyObj == "object") {
						if (iframe_droppable_elm) {
							var tasks_menu = j_iframe_droppable_elm.parent().closest(".taskflowchart").children(".tasks_menu");
							var task_menu = tasks_menu.find(".task.task_menu.task_callhibernateobject");
							var task_type = task_menu.attr("type");
							
							if (task_type) {
								var edit_url = li_a.attr("edit_url");
								var bean_name = getParameterByName(edit_url, "bean_name");
								var bean_ui_props = bean_name && iframe_win.main_layers_properties && iframe_win.main_layers_properties.hasOwnProperty(bean_name) && iframe_win.main_layers_properties[bean_name].hasOwnProperty("ui") ? main_layers_properties[bean_name]["ui"] : null;
								
								if (bean_ui_props) {
									var hbn_obj = getParameterByName(edit_url, "obj");
									var task_label = "Get hibernate obj " + hbn_obj;
									
									onChooseWorkflowTask(event, iframe_droppable_elm, iframe_win, iframe_offset, task_type, task_label, function(task_id) {
										onChooseWorkflowCallHibernateObjectTask(iframe_win, li, task_id);
									});
								}
								else
									iframe_win.taskFlowChartObj.StatusMessage.showError("This diagram doesn't allow the drop action for this element.");
							}
							else
								iframe_win.taskFlowChartObj.StatusMessage.showError("This diagram doesn't allow the drop action for this element.");
						}
						else
							iframe_win.taskFlowChartObj.StatusMessage.showError("Please drop element inside of diagram");
					}
					else
						StatusMessageHandler.showError("Sorry, droppable not allowed...");
				}
				else if (li_a.children("i.class").length > 0) { //hibernate obj => create callhibernateobject task
					if (typeof iframe_win.CreateClassObjectTaskPropertyObj == "object") {
						if (iframe_droppable_elm) {
							var tasks_menu = j_iframe_droppable_elm.parent().closest(".taskflowchart").children(".tasks_menu");
							var task_menu = tasks_menu.find(".task.task_menu.task_createclassobject");
							var task_type = task_menu.attr("type");
							
							if (task_type) {
								var edit_url = li_a.attr("edit_url");
								var bean_name = getParameterByName(edit_url, "bean_name");
								var bean_ui_props = bean_name && iframe_win.main_layers_properties && iframe_win.main_layers_properties.hasOwnProperty(bean_name) && iframe_win.main_layers_properties[bean_name].hasOwnProperty("ui") ? main_layers_properties[bean_name]["ui"] : null;
								
								if (bean_ui_props) {
									var class_obj = getParameterByName(edit_url, "class");
									var task_label = "Create class obj " + class_obj;
									
									onChooseWorkflowTask(event, iframe_droppable_elm, iframe_win, iframe_offset, task_type, task_label, function(task_id) {
										onChooseWorkflowCreateClassObjectTask(iframe_win, li, task_id);
									});
								}
								else
									iframe_win.taskFlowChartObj.StatusMessage.showError("This diagram doesn't allow the drop action for this element.");
							}
							else
								iframe_win.taskFlowChartObj.StatusMessage.showError("This diagram doesn't allow the drop action for this element.");
						}
						else
							iframe_win.taskFlowChartObj.StatusMessage.showError("Please drop element inside of diagram");
					}
					else
						StatusMessageHandler.showError("Sorry, droppable not allowed...");
				}
				else if (li_a.children("i.method").length > 0) { //util method or business logic service
					var is_bl = li.parent().closest(".main_node_businesslogic").length > 0;
					var func = is_bl ? iframe_win.CallBusinessLogicTaskPropertyObj : iframe_win.CallObjectMethodTaskPropertyObj;
					var task_tag = is_bl ? "callbusinesslogic" : "callobjectmethod";
					
					if (typeof func == "object") {
						if (iframe_droppable_elm) {
							var tasks_menu = j_iframe_droppable_elm.parent().closest(".taskflowchart").children(".tasks_menu");
							var task_menu = tasks_menu.find(".task.task_menu.task_" + task_tag);
							var task_type = task_menu.attr("type");
							
							if (task_type) {
								var edit_url = li_a.attr("edit_url");
								var bean_name = getParameterByName(edit_url, "bean_name");
								var bean_ui_props = bean_name && iframe_win.main_layers_properties && iframe_win.main_layers_properties.hasOwnProperty(bean_name) && iframe_win.main_layers_properties[bean_name].hasOwnProperty("ui") ? main_layers_properties[bean_name]["ui"] : null;
								
								if (bean_ui_props) {
									var obj_class = getParameterByName(edit_url, is_bl ? "service" : "class");
									var method = getParameterByName(edit_url, "method");
									var task_label = "Call " + (is_bl ? "service " : "") + obj_class + "." + method;
									
									onChooseWorkflowTask(event, iframe_droppable_elm, iframe_win, iframe_offset, task_type, task_label, function(task_id) {
										if (is_bl)
											onChooseWorkflowCallBusinessLogicTask(iframe_win, li, task_id);
										else
											onChooseWorkflowCallObjectMethodTask(iframe_win, li, task_id);
									});
								}
								else
									iframe_win.taskFlowChartObj.StatusMessage.showError("This diagram doesn't allow the drop action for this element.");
							}
							else
								iframe_win.taskFlowChartObj.StatusMessage.showError("This diagram doesn't allow the drop action for this element.");
						}
						else
							iframe_win.taskFlowChartObj.StatusMessage.showError("Please drop element inside of diagram");
					}
					else
						StatusMessageHandler.showError("Sorry, droppable not allowed...");
				}
				else if (li_a.children("i.function").length > 0) { //function or business logic service
					var is_bl = li.parent().closest(".main_node_businesslogic").length > 0;
					var func = is_bl ? iframe_win.CallBusinessLogicTaskPropertyObj : iframe_win.CallFunctionTaskPropertyObj;
					var task_tag = is_bl ? "callbusinesslogic" : "callfunction";
					
					if (typeof func == "object") {
						if (iframe_droppable_elm) {
							var tasks_menu = j_iframe_droppable_elm.parent().closest(".taskflowchart").children(".tasks_menu");
							var task_menu = tasks_menu.find(".task.task_menu.task_" + task_tag);
							var task_type = task_menu.attr("type");
							
							if (task_type) {
								var edit_url = li_a.attr("edit_url");
								var bean_name = getParameterByName(edit_url, "bean_name");
								var bean_ui_props = bean_name && iframe_win.main_layers_properties && iframe_win.main_layers_properties.hasOwnProperty(bean_name) && iframe_win.main_layers_properties[bean_name].hasOwnProperty("ui") ? main_layers_properties[bean_name]["ui"] : null;
								
								if (bean_ui_props) {
									var func_name = getParameterByName(edit_url, "function");
									var task_label = "Call " + (is_bl ? "service " : "") + func_name;
									
									onChooseWorkflowTask(event, iframe_droppable_elm, iframe_win, iframe_offset, task_type, task_label, function(task_id) {
										if (is_bl)
											onChooseWorkflowCallBusinessLogicTask(iframe_win, li, task_id);
										else
											onChooseWorkflowCallFunctionTask(iframe_win, li, task_id);
									});
								}
								else
									iframe_win.taskFlowChartObj.StatusMessage.showError("This diagram doesn't allow the drop action for this element.");
							}
							else
								iframe_win.taskFlowChartObj.StatusMessage.showError("This diagram doesn't allow the drop action for this element.");
						}
						else
							iframe_win.taskFlowChartObj.StatusMessage.showError("Please drop element inside of diagram");
					}
					else
						StatusMessageHandler.showError("Sorry, droppable not allowed...");
				}
				else if (li_a.children("i.file, i.objtype, i.hibernatemodel, i.config_file, i.controller_file, i.entity_file, i.view_file, i.template_file, i.util_file, i.block_file, i.module_file").length > 0) { //file => create includefile task
					//check if file has a php extension
					
					if (typeof iframe_win.IncludeFileTaskPropertyObj == "object") {
						if (iframe_droppable_elm) {
							var tasks_menu = j_iframe_droppable_elm.parent().closest(".taskflowchart").children(".tasks_menu");
							var task_menu = tasks_menu.find(".task.task_menu.task_includefile");
							var task_type = task_menu.attr("type");
							
							if (task_type) {
								var edit_url = li_a[0].hasAttribute("edit_url") ? li_a.attr("edit_url") : li_a.attr("edit_raw_file_url");
								var bean_name = getParameterByName(edit_url, "bean_name");
								var bean_ui_props = bean_name && iframe_win.main_layers_properties && iframe_win.main_layers_properties.hasOwnProperty(bean_name) && iframe_win.main_layers_properties[bean_name].hasOwnProperty("ui") ? main_layers_properties[bean_name]["ui"] : null;
								
								if (bean_ui_props) {
									var path = getParameterByName(edit_url, "path");
									var is_php = path.match(/\.php$/i);
									
									if (is_php) {
										var iframe_url = iframe_doc.location;
										var iframe_bean_name = getParameterByName(iframe_url, "bean_name");
										var is_same_layer = bean_name == iframe_bean_name || iframe_bean_name == "test_unit" || !iframe_bean_name || $.inArray(bean_name, ["dao", "lib", "vendor", "test_unit"]) != -1; //if iframe_bean_name is empty, it means is in the edit test unit page.
										
										if (is_same_layer) {
											var task_label = "Include " + path;
											
											onChooseWorkflowTask(event, iframe_droppable_elm, iframe_win, iframe_offset, task_type, task_label, function(task_id) {
												onChooseWorkflowIncludeFileTask(iframe_win, li, task_id);
											});
										}
										else
											iframe_win.taskFlowChartObj.StatusMessage.showError("This diagram doesn't allow the drop action for files that are not in the same layer.");
									}
									else
										iframe_win.taskFlowChartObj.StatusMessage.showError("This diagram doesn't allow the drop action for non php files.");
								}
								else
									iframe_win.taskFlowChartObj.StatusMessage.showError("This diagram doesn't allow the drop action for this element.");
							}
							else
								iframe_win.taskFlowChartObj.StatusMessage.showError("This diagram doesn't allow the drop action for this element.");
						}
						else
							iframe_win.taskFlowChartObj.StatusMessage.showError("Please drop element inside of diagram");
					}
					else if (li_a.children("i.block_file").length > 0) { //file => create block widget in LayoutUIEditor
						//check if droppable is a LayoutUIEditor
						if (PtlLayoutUIEditor && typeof iframe_win.updateCodeLayoutUIEditorModuleBlockWidgetWithBlockId == "function") {
							if (iframe_droppable_elm) { //if iframe_droppable_elm exists, it means it has the class: .droppable"
								//create widget and append it to iframe_droppable_elm
								var widget = $("<div></div>");
								j_iframe_droppable_elm.append(widget);
								
								//add widget in the right place and disable classes in LayoutUIEditor's droppable
								var new_event = {
									clientX: event.clientX - iframe_offset.left,
									clientY: event.clientY - iframe_offset.top,
								};
								PtlLayoutUIEditor.onWidgetDraggingStop(new_event, helper_clone, widget);
								
								//prepare widget props
								var edit_url = li_a[0].hasAttribute("edit_url") ? li_a.attr("edit_url") : li_a.attr("edit_raw_file_url");
								var path = getParameterByName(edit_url, "path");
								var pos = path.indexOf("/src/block/");
						    		var project = path.substr(0, pos);
						    		var block = path.substr(pos + "/src/block/".length);
						    		block = block.substr(0, block.length - 4);
								
								iframe_win.updateCodeLayoutUIEditorModuleBlockWidgetWithBlockId(widget, block, project);
							}
							else
								PtlLayoutUIEditor.showError("Please drop element inside of a droppable element in the design area.");
						}
					}
					else
						StatusMessageHandler.showError("Sorry, droppable not allowed...");
				}
				else
					StatusMessageHandler.showError("Sorry, droppable not allowed...");
			}
		};
		
		var getIframeElementFromPoint = function(inner_iframe, x, y, helper, helper_clone) {
			var inner_iframe_win = inner_iframe.contentWindow;
			var inner_iframe_doc = inner_iframe_win ? inner_iframe_win.document : null;
			var inner_iframe_offset = $(inner_iframe).offset();
			var inner_iframe_droppable_elm = null;
			
			if (inner_iframe_doc) {
				//hide helpers
				var helper_visible = helper.css("display") != "none";
				var helper_clone_visible = helper_clone.css("display") != "none";
				
				if (helper_visible)
					helper.hide();
				
				if (helper_clone_visible)
					helper_clone.hide();
				
				//get droppable element
				var inner_iframe_event_x = x - inner_iframe_offset.left;
				var inner_iframe_event_y = y - inner_iframe_offset.top;
				
				var inner_iframe_droppable_elm = inner_iframe_doc.elementFromPoint(inner_iframe_event_x, inner_iframe_event_y);
				
				if (inner_iframe_droppable_elm && inner_iframe_droppable_elm.nodeName && inner_iframe_droppable_elm.nodeName.toUpperCase() == "IFRAME")
					inner_iframe_droppable_elm = getIframeElementFromPoint(inner_iframe_droppable_elm, inner_iframe_event_x, inner_iframe_event_y, helper, helper_clone);
				
				//show helpers
				if (helper_visible)
					helper.show();
				
				if (helper_clone_visible)
					helper_clone.show();
			}
			
			return inner_iframe_droppable_elm;
		};
		
		var folders_lis = elm.find("li > a > i").filter(droppables_selector).parent().parent();
		var files_lis = elm.find("li > a > i").filter(draggables_selector).parent().parent();
		
		folders_lis.droppable({
			greedy: true,
			over: function(event, ui_obj) {
				if (navigator_droppables_active)
					$(this).addClass("drop_hover");
			},
			out: function(event, ui_obj) {
				$(this).removeClass("drop_hover");
			},
			drop: left_panel_droppable_handler,
		});
		
		files_lis.draggable({
			//settings for the iframe droppable
			iframeFix:true,
			iframeScroll: true,
			scroll: true,
			scrollSensitivity: 20,
			
			//others settings
		    	items: "li.jstree-node",
			//containment: elm, //we can drag the tables to the DB Diagram or to LayoutUIEditor in edit_entity_simple and edit_template_simple.
			//appendTo: elm, //disable to allow copy attribute accross different tables.
			handle: "> a.jstree-anchor > i.jstree-icon",
			revert: true,
			cursor: "crosshair",
		     tolerance: "pointer",
			grid: [5, 5],
			//axis: "y", //we can drag the tables to the DB Diagram or LayoutUIEditor in edit_entity_simple and edit_template_simple.
			
			//handlers
			helper: function() {
				var clone = $(this).clone();
				clone.addClass("sortable_helper");
				clone.children("a").removeClass("jstree-hovered jstree-clicked");
				clone.children("ul").remove();
				clone.children(".sub_menu").remove();
				
				return clone;
			},
			start: function(event, ui_obj) {
				var helper_clone = ui_obj.helper.clone();
				$("body").append(helper_clone);
				
				iframe_win = iframe[0].contentWindow;
				iframe_doc = iframe_win ? iframe_win.document : null;
				iframe_offset = iframe.offset();
				PtlLayoutUIEditor = typeof iframe[0].contentWindow.$ != "undefined" ? iframe[0].contentWindow.$(".code_layout_ui_editor .layout-ui-editor").data("LayoutUIEditor") : null;
				
				is_in_right_panel = false;
			},
			drag: function(event, ui_obj) {
				//prepare scroll_parent element when the dragged element will be out of the left panel and dropped in the right panel to the DB diagram or to edit_entity_simple and edit_template_simple files.
				var helper = ui_obj.helper;
				helper.show();
				
				var left_edge = scroll_parent.offset().left;
				var right_edge = left_edge + scroll_parent.width();
				var is_in_edge = is_main_navigator_reverse ? false : (helper.offset().left + helper.width()) > (right_edge - 20);
				
				if (is_in_edge && scroll_parent.hasClass("scroll")) {
					var st = scroll_parent.scrollTop();
					var sl = scroll_parent.scrollLeft();
					
					scroll_parent.data("mt", scroll_parent.css("margin-top"));
					scroll_parent.data("ml", scroll_parent.css("margin-left"));
					scroll_parent.data("st", st);
					scroll_parent.data("sl", sl);
					
					scroll_parent.removeClass("scroll");
					scroll_parent.css("margin-top", "-" + st + "px");
					scroll_parent.css("margin-left", "-" + sl + "px");
					
					helper.css("margin-top", st + "px");
					helper.css("margin-left", sl + "px");
				}
				else if (!is_in_edge && !scroll_parent.hasClass("scroll")) {
					scroll_parent.addClass("scroll");
					scroll_parent.css("margin-top", scroll_parent.data("mt"));
					scroll_parent.css("margin-left", scroll_parent.data("ml"));
					scroll_parent.scrollTop( scroll_parent.data("st") );
					scroll_parent.scrollLeft( scroll_parent.data("sl") );
					
					helper.css("margin-top", "");
					helper.css("margin-left", "");
				}
				
				//prepare helper_clone
				var helper_clone = $("body").children(".sortable_helper");
				is_in_right_panel = event.clientX > iframe.offset().left && event.clientX < iframe.offset().left + iframe.width();
				
				helper_clone.offset({
					top: event.clientY,
					left: event.clientX,
				});
				
				if (is_in_right_panel) {
					//get droppable
					var new_iframe_droppable_elm = getIframeElementFromPoint(iframe[0], event.clientX, event.clientY, helper, helper_clone);
					
					//hide helper from left panel and show the one from the right panel
					helper_clone.show();
					helper.hide();
					
					//get real droppable based in class
					if (new_iframe_droppable_elm)
						new_iframe_droppable_elm = new_iframe_droppable_elm.closest(available_iframe_droppables_selectors);
					
					//remove from old iframe_droppable_elm
					if (iframe_droppable_elm && new_iframe_droppable_elm != iframe_droppable_elm)
						$(iframe_droppable_elm).removeClass(iframe_droppable_over_class); 
					
					//set new iframe_droppable_elm
					iframe_droppable_elm = new_iframe_droppable_elm;
					
					//prepare PtlLayoutUIEditor
					if (PtlLayoutUIEditor) {
						var new_event = {
							clientX: event.clientX - iframe_offset.left,
							clientY: event.clientY - iframe_offset.top,
						};
						PtlLayoutUIEditor.onWidgetDraggingDrag(new_event, helper_clone, null);
					}
					else if (iframe_droppable_elm) //prepare droppable over class
						$(iframe_droppable_elm).addClass(iframe_droppable_over_class);
				}
				else {
					helper_clone.hide();
					//helper.show(); //no need bc I already show it above
					
					//prepare PtlLayoutUIEditor
					if (PtlLayoutUIEditor) {
						var new_event = {
							clientX: -1,
							clientY: -1,
						};
						PtlLayoutUIEditor.onWidgetDraggingDrag(new_event, helper_clone, null);
					}
					else if (iframe_droppable_elm) //remove droppable over class
						$(iframe_droppable_elm).removeClass(iframe_droppable_over_class);
				}
			},
			stop: function(event, ui_obj) {
				var helper = ui_obj.helper;
				var helper_clone = $("body").children(".sortable_helper");
				
				var drag_cancelled = helper.data("escape_key_pressed");
				helper.data("escape_key_pressed", null);
				
				helper.show();
				//helper_clone.hide(); //Do not hide helper_clone bc right_panel_droppable_handler will use its position in PtlLayoutUIEditor
				
				//prepare scroll_parent and call stop handler
				if (!scroll_parent.hasClass("scroll")) {
					scroll_parent.addClass("scroll");
					scroll_parent.css("margin-top", scroll_parent.data("mt"));
					scroll_parent.css("margin-left", scroll_parent.data("ml"));
					scroll_parent.scrollTop( scroll_parent.data("st") );
					scroll_parent.scrollLeft( scroll_parent.data("sl") );
				}
				
				if (iframe_droppable_elm) //remove droppable over class
					$(iframe_droppable_elm).removeClass(iframe_droppable_over_class);
				
				if (!drag_cancelled) {
					if (is_in_right_panel) 
						right_panel_droppable_handler(event, ui_obj, this, helper_clone);
					
					//disable classes in LayoutUIEditor's droppable, just in case the right_panel_droppable_handler did NOT do it already
					if (PtlLayoutUIEditor) {
						var new_event = {
							clientX: event.clientX - iframe_offset.left,
							clientY: event.clientY - iframe_offset.top,
						};
						PtlLayoutUIEditor.onWidgetDraggingStop(new_event, helper_clone, null);
					}
				}
				
				helper.remove();
				helper_clone.remove();
				
				//do not add "return false" otherwise the draggable will stop working for next iteractions
			},
		});
		
		files_lis.find(" > a > i").addClass("allow_move");
	}
}

//Any change in this method should be done too in the initFilesDragAndDrop method
function initDBTablesSorting(elm) {
	var iframe = $("#right_panel > iframe");
	
	if (iframe[0]) {
		var scroll_parent = elm.parent().closest(".scroll");
		var iframe_win = iframe[0].contentWindow;
		var iframe_doc = iframe_win ? iframe_win.document : null;
		var iframe_offset = iframe.offset();
		var iframe_droppable_elm = null;
		var iframe_droppable_over_class = "drop_hover dragging_task task_droppable_over";
		var current_table_droppable_elm = null;
		var available_iframe_droppables_selectors = ".droppable, .tasks_flow, .connector_overlay_add_icon"; //".droppable" is related with the LayoutUIEditor, ".tasks_flow" is related with workflows and ".connector_overlay_add_icon" is related with Logic workflows.
		var PtlLayoutUIEditor = null;
		var is_main_navigator_reverse = $("body").hasClass("main_navigator_reverse");
		var is_in_right_panel = false;
		
		var left_panel_droppable_handler = function(event, ui_obj, fk_table_li) {
			var fk_table_li = $(fk_table_li);
			var is_fk_table_li_ul = fk_table_li.is("ul") && fk_table_li.parent().is("li");
			
			if (is_fk_table_li_ul)
				fk_table_li = fk_table_li.parent();
			
			var fk_table_li_a = fk_table_li.children("a");
			var item = ui_obj.draggable;
			var a = item.children("a");
			
			fk_table_li.removeClass("drop_hover");
			fk_table_li.parents().removeClass("drop_hover");
			
			if (item.is(".jstree-node")) { //be sure that the draggable item is a jstree node, bc it can be the "div#hide_panel" from the left navigator.
				if (fk_table_li_a.children("i.table").length == 1) { 
					var is_same_table = a.attr("table_name") == fk_table_li_a.attr("table_name"); //if table is it-self
					
					if (!is_same_table && a.children("i.attribute").length == 1) {
						item.data("droppable_table_node", fk_table_li[0]);
						
						if (is_fk_table_li_ul)
							item.data("is_droppable_table_ul", true);
					}
					else if (a.children("i.table").length == 1) {
						var data = {
							attribute_table: a.attr("table_name"),
						};
						var callback = function(a, attr_name, action, new_name, url, tree_node_id_to_be_updated) {
							refreshAndShowNodeChildsByNodeId( fk_table_li.attr("id") ); //refresh all table's attributes
						};
						
						//copy attribute to another table, adding it as a foreign key
						manageDBTableAction(fk_table_li.children("a")[0], "add_fk_attribute_url", "add_fk_attribute", function(a, attr_name, action, new_name, url, tree_node_id_to_be_updated) {
							if (fk_table_li.hasClass("jstree-open")) {
								//add clone attribute
								var pk = item.find(" > ul > li.primary_key");
								
								if (pk.length > 0) {
									var clone = pk.clone();
									clone.removeClass("primary_key");
									
									fk_table_li.children("ul").append(clone);
								}
							}
							
							callback(a, attr_name, action, new_name, url, tree_node_id_to_be_updated);
						}, callback, data);
					}
				}
			}
			
			//do not add "return false" otherwise the draggable will stop working for next iteractions
		};
		
		var right_panel_droppable_handler = function(event, ui_obj, tree_node, helper_clone) {
			//console.log(event);
			//console.log(ui_obj);
			
			var j_iframe_droppable_elm = $(iframe_droppable_elm);
			var li = ui_obj.helper;
			var li_a = li.children("a");
			
			if (li.is(".jstree-node")) { //be sure that the draggable item is a jstree node, bc it can be the "div#hide_panel" from the left navigator.
				//if dragged item is a table
				if (li_a.children("i.table").length > 0 && li_a.attr("table_name")) {
					var table_name = li_a.attr("table_name");
					var bean_name = li_a.attr("bean_name");
					var db_driver = getIframeBeanDBDriver(iframe_win, bean_name);
					
					//check if droppable is a LayoutUIEditor
					if (PtlLayoutUIEditor && typeof iframe_win.onChooseCodeLayoutUIEditorDBTableWidgetOptions == "function") {
						if (iframe_droppable_elm) { //if iframe_droppable_elm exists, it means it has the class: .droppable"
							//create widget and append it to iframe_droppable_elm
							var widget = $("<div></div>");
							j_iframe_droppable_elm.append(widget);
							
							//add widget in the right place and disable classes in LayoutUIEditor's droppable
							var new_event = {
								clientX: event.clientX - iframe_offset.left,
								clientY: event.clientY - iframe_offset.top,
							};
							PtlLayoutUIEditor.onWidgetDraggingStop(new_event, helper_clone, widget);
							
							var widget_group = j_iframe_droppable_elm.closest("[data-widget-group-list], [data-widget-group-form]");
							var inside_of_widget_group = widget_group.length > 0;
							
							//get db broker
							var db_broker = null;
							
							//prepare widget props
							var opts = {
								hide_db_broker: true,
								hide_db_driver: true,
								hide_type: true,
								find_best_type: true,
								hide_db_table: true,
								hide_table_alias: true,
								hide_widget_type: true,
								widget_type: "html"
							};
							
							if (inside_of_widget_group)
								iframe_win.onReplaceCodeLayoutUIEditorDBTableWidgetOptions(db_broker, db_driver, "db", table_name, widget, opts);
							else 
								iframe_win.onChooseCodeLayoutUIEditorDBTableWidgetOptions(db_broker, db_driver, "db", table_name, widget, opts);
						}
						else
							PtlLayoutUIEditor.showError("Please drop element inside of a droppable element in the design area.");
					}
					//check if droppable is a DB Diagram
					else if (typeof iframe_win.addExistentTable == "function") {
						if (iframe_droppable_elm) { //if iframe_droppable_elm exists, it means it has the class: .tasks_flow"
							var tasks_flow_offset = j_iframe_droppable_elm.offset();
							var tasks_flow_event_x = event.clientX - iframe_offset.left - tasks_flow_offset.left;
							var tasks_flow_event_y = event.clientY - iframe_offset.top - tasks_flow_offset.top;
							
							//add table to diagram
							iframe_win.addExistentTable(table_name, {
								top: tasks_flow_event_y,
								left: tasks_flow_event_x,
							});
						}
						else
							iframe_win.taskFlowChartObj.StatusMessage.showError("Please drop element inside of diagram");
					}
					//check if droppable is Logic Diagram
					else if (typeof iframe_win.DBDAOActionTaskPropertyObj == "object") {
						if (iframe_droppable_elm) {
							var tasks_menu = j_iframe_droppable_elm.parent().closest(".taskflowchart").children(".tasks_menu");
							var task_menu = tasks_menu.find(".task.task_menu.task_dbdaoaction");
							var task_type = task_menu.attr("type");
							
							if (task_type) {
								var url = $(tree_node).children("ul").attr("url");
								
								//show popup with possible actions
								onChooseWorkflowDBTableTaskOptions(event, iframe_droppable_elm, iframe_win, iframe_offset, db_driver, table_name, task_type, url);
							}
							else
								iframe_win.taskFlowChartObj.StatusMessage.showError("This diagram doesn't allow the drop action for this element.");
						}
						else
							iframe_win.taskFlowChartObj.StatusMessage.showError("Please drop element inside of diagram");
					}
					else {
						StatusMessageHandler.showError("Sorry, droppable not allowed...");
						//console.log("Sorry, droppable not allowed..");
					}
				}
				else {
					StatusMessageHandler.showError("Sorry, droppable not allowed...");
					//console.log("orry, droppable not allowed..");
				}
			}
		};
		
		var getIframeElementFromPoint = function(inner_iframe, x, y, helper, helper_clone) {
			var inner_iframe_win = inner_iframe.contentWindow;
			var inner_iframe_doc = inner_iframe_win ? inner_iframe_win.document : null;
			var inner_iframe_offset = $(inner_iframe).offset();
			var inner_iframe_droppable_elm = null;
			
			if (inner_iframe_doc) {
				//hide helpers
				var helper_visible = helper.css("display") != "none";
				var helper_clone_visible = helper_clone.css("display") != "none";
				
				if (helper_visible)
					helper.hide();
				
				if (helper_clone_visible)
					helper_clone.hide();
				
				//get droppable element
				var inner_iframe_event_x = x - inner_iframe_offset.left;
				var inner_iframe_event_y = y - inner_iframe_offset.top;
				
				var inner_iframe_droppable_elm = inner_iframe_doc.elementFromPoint(inner_iframe_event_x, inner_iframe_event_y);
				
				if (inner_iframe_droppable_elm && inner_iframe_droppable_elm.nodeName && inner_iframe_droppable_elm.nodeName.toUpperCase() == "IFRAME")
					inner_iframe_droppable_elm = getIframeElementFromPoint(inner_iframe_droppable_elm, inner_iframe_event_x, inner_iframe_event_y, helper, helper_clone);
				
				//show helpers
				if (helper_visible)
					helper.show();
				
				if (helper_clone_visible)
					helper_clone.show();
			}
			
			return inner_iframe_droppable_elm;
		};
		
		var getSameTableElementFromPoint = function(current_table_elm, x, y, helper, helper_clone) {
			var table_droppable_elm = null;
			
			if (current_table_elm) {
				current_table_elm = $(current_table_elm);
				
				//hide helpers
				var helper_visible = helper.css("display") != "none";
				var helper_clone_visible = helper_clone.css("display") != "none";
				
				if (helper_visible)
					helper.hide();
				
				if (helper_clone_visible)
					helper_clone.hide();
				
				//get droppable element
				table_droppable_elm = document.elementFromPoint(x, y);
				
				if (table_droppable_elm) {
					table_droppable_elm = $(table_droppable_elm).closest("li[id='" + current_table_elm.attr("id") + "']"); //only if is the same table, otherwise it will fall into the droppable function
					
					if (!table_droppable_elm[0])
						table_droppable_elm = null;
				}
				
				//show helpers
				if (helper_visible)
					helper.show();
				
				if (helper_clone_visible)
					helper_clone.show();
			}
			
			return table_droppable_elm;
		};
		
		var lis = elm.find(" > li > a > .table").parent().parent();
		
		lis.droppable({
			greedy: true,
			over: function(event, ui_obj) {
				if (navigator_droppables_active)
					$(this).addClass("drop_hover");
			},
			out: function(event, ui_obj) {
				$(this).removeClass("drop_hover");
			},
			drop: function(event, ui_obj) {
				left_panel_droppable_handler(event, ui_obj, this);
			}
		})
		.draggable({
			//settings for the iframe droppable
			iframeFix:true,
			iframeScroll: true,
			scroll: true,
			scrollSensitivity: 20,
			
			//others settings
		    	items: "li.jstree-node.jstree-leaf",
			//containment: elm, //we can drag the tables to the DB Diagram or to LayoutUIEditor in edit_entity_simple and edit_template_simple.
			//appendTo: elm, //disable to allow copy attribute accross different tables.
			handle: "> a.jstree-anchor > i.jstree-icon.table",
			revert: true,
			cursor: "crosshair",
		     tolerance: "pointer",
			grid: [5, 5],
			//axis: "y", //we can drag the tables to the DB Diagram or LayoutUIEditor in edit_entity_simple and edit_template_simple.
			
			//handlers
			helper: function() {
				var clone = $(this).clone();
				clone.addClass("sortable_helper");
				clone.children("a").removeClass("jstree-hovered jstree-clicked");
				clone.children("ul").remove();
				clone.children(".sub_menu").remove();
				
				return clone;
			},
			start: function(event, ui_obj) {
				var helper_clone = ui_obj.helper.clone();
				$("body").append(helper_clone);
				
				iframe_win = iframe[0].contentWindow;
				iframe_doc = iframe_win ? iframe_win.document : null;
				iframe_offset = iframe.offset();
				PtlLayoutUIEditor = typeof iframe[0].contentWindow.$ != "undefined" ? iframe[0].contentWindow.$(".code_layout_ui_editor .layout-ui-editor").data("LayoutUIEditor") : null;
				
				is_in_right_panel = false;
			},
			drag: function(event, ui_obj) {
				//prepare scroll_parent element when the dragged element will be out of the left panel and dropped in the right panel to the DB diagram or to edit_entity_simple and edit_template_simple files.
				var helper = ui_obj.helper;
				helper.show();
				
				var left_edge = scroll_parent.offset().left;
				var right_edge = left_edge + scroll_parent.width();
				var is_in_edge = is_main_navigator_reverse ? false : (helper.offset().left + helper.width()) > (right_edge - 20);
				
				if (is_in_edge && scroll_parent.hasClass("scroll")) {
					var st = scroll_parent.scrollTop();
					var sl = scroll_parent.scrollLeft();
					
					scroll_parent.data("mt", scroll_parent.css("margin-top"));
					scroll_parent.data("ml", scroll_parent.css("margin-left"));
					scroll_parent.data("st", st);
					scroll_parent.data("sl", sl);
					
					scroll_parent.removeClass("scroll");
					scroll_parent.css("margin-top", "-" + st + "px");
					scroll_parent.css("margin-left", "-" + sl + "px");
					
					helper.css("margin-top", st + "px");
					helper.css("margin-left", sl + "px");
				}
				else if (!is_in_edge && !scroll_parent.hasClass("scroll")) {
					scroll_parent.addClass("scroll");
					scroll_parent.css("margin-top", scroll_parent.data("mt"));
					scroll_parent.css("margin-left", scroll_parent.data("ml"));
					scroll_parent.scrollTop( scroll_parent.data("st") );
					scroll_parent.scrollLeft( scroll_parent.data("sl") );
					
					helper.css("margin-top", "");
					helper.css("margin-left", "");
				}
				
				//prepare helper_clone
				var helper_clone = $("body").children(".sortable_helper");
				is_in_right_panel = event.clientX > iframe.offset().left && event.clientX < iframe.offset().left + iframe.width();
				
				helper_clone.offset({
					top: event.clientY,
					left: event.clientX,
				});
				
				if (is_in_right_panel) {
					//get droppable
					var new_iframe_droppable_elm = getIframeElementFromPoint(iframe[0], event.clientX, event.clientY, helper, helper_clone);
					
					//hide helper from left panel and show the one from the right panel
					helper_clone.show();
					helper.hide();
					
					//get real droppable based in class
					if (new_iframe_droppable_elm)
						new_iframe_droppable_elm = new_iframe_droppable_elm.closest(available_iframe_droppables_selectors);
					
					//remove from old iframe_droppable_elm
					if (iframe_droppable_elm && new_iframe_droppable_elm != iframe_droppable_elm)
						$(iframe_droppable_elm).removeClass(iframe_droppable_over_class); 
					
					//set new iframe_droppable_elm
					iframe_droppable_elm = new_iframe_droppable_elm;
					
					//prepare PtlLayoutUIEditor
					if (PtlLayoutUIEditor) {
						var new_event = {
							clientX: event.clientX - iframe_offset.left,
							clientY: event.clientY - iframe_offset.top,
						};
						PtlLayoutUIEditor.onWidgetDraggingDrag(new_event, helper_clone, null);
					}
					else if (iframe_droppable_elm) //prepare droppable over class
						$(iframe_droppable_elm).addClass(iframe_droppable_over_class);
				}
				else {
					helper_clone.hide();
					//helper.show(); //no need bc I already show it above
					
					//prepare PtlLayoutUIEditor
					if (PtlLayoutUIEditor) {
						var new_event = {
							clientX: -1,
							clientY: -1,
						};
						PtlLayoutUIEditor.onWidgetDraggingDrag(new_event, helper_clone, null);
					}
					else if (iframe_droppable_elm) //remove droppable over class
						$(iframe_droppable_elm).removeClass(iframe_droppable_over_class);
					
					//check if table was dropped into the same table, which means we need to create a sub attribute pointing to the primary key
					if (current_table_droppable_elm) {
						current_table_droppable_elm.removeClass("drop_hover");
						current_table_droppable_elm = null;
					}
					
					var table_droppable_elm = getSameTableElementFromPoint(this, event.clientX, event.clientY, helper, helper_clone);
					
					if (table_droppable_elm) {
						if (navigator_droppables_active)
							table_droppable_elm.addClass("drop_hover");
						
						current_table_droppable_elm = table_droppable_elm;
					}
				}
			},
			stop: function(event, ui_obj) {
				var helper = ui_obj.helper;
				var helper_clone = $("body").children(".sortable_helper");
				
				var drag_cancelled = helper.data("escape_key_pressed");
				helper.data("escape_key_pressed", null);
				
				helper.show();
				//helper_clone.hide(); //Do not hide helper_clone bc right_panel_droppable_handler will use its position in PtlLayoutUIEditor
				
				//prepare scroll_parent and call stop handler
				if (!scroll_parent.hasClass("scroll")) {
					scroll_parent.addClass("scroll");
					scroll_parent.css("margin-top", scroll_parent.data("mt"));
					scroll_parent.css("margin-left", scroll_parent.data("ml"));
					scroll_parent.scrollTop( scroll_parent.data("st") );
					scroll_parent.scrollLeft( scroll_parent.data("sl") );
				}
				
				if (iframe_droppable_elm) //remove droppable over class
					$(iframe_droppable_elm).removeClass(iframe_droppable_over_class);
				
				if (!drag_cancelled) {
					if (is_in_right_panel) 
						right_panel_droppable_handler(event, ui_obj, this, helper_clone);
					else if (current_table_droppable_elm) { //check if table was dropped into the same table, which means we need to create a sub attribute pointing to the primary key
						current_table_droppable_elm.removeClass("drop_hover");
						ui_obj.draggable = helper; //replicate droppable behaviour
						left_panel_droppable_handler(event, ui_obj, current_table_droppable_elm[0]);
					}
					
					//disable classes in LayoutUIEditor's droppable, just in case the right_panel_droppable_handler did NOT do it already
					if (PtlLayoutUIEditor) {
						var new_event = {
							clientX: event.clientX - iframe_offset.left,
							clientY: event.clientY - iframe_offset.top,
						};
						PtlLayoutUIEditor.onWidgetDraggingStop(new_event, helper_clone, null);
					}
				}
				
				helper.remove();
				helper_clone.remove();
				
				//do not add "return false" otherwise the draggable will stop working for next iteractions
			},
		});
		
		//ignore if inner ul, bc the initDBTableAttributesSorting method already takes care of this
		lis.children("ul").droppable({
			greedy: true,
			over: function(event, ui_obj) {
				if (navigator_droppables_active)
					$(this).parent().addClass("drop_hover");
			},
			out: function(event, ui_obj) {
				$(this).parent().removeClass("drop_hover");
			},
			drop: function(event, ui_obj) {
				left_panel_droppable_handler(event, ui_obj, this);
			}
		});
	}
}

function getIframeBeanDBDriver(iframe_win, bean_name) {
	var db_driver = bean_name ? bean_name.toLowerCase() : "";
	
	//get db driver in iframe based in the bean name.
	if (bean_name && $.isPlainObject(iframe_win.brokers_db_drivers))
		for (var db_driver_name in iframe_win.brokers_db_drivers) {
			var db_driver_props = iframe_win.brokers_db_drivers[db_driver_name];
			
			if ($.isArray(db_driver_props) && db_driver_props[2] == bean_name) {
				db_driver = db_driver_name;
				break;
			}
		}
	
	return db_driver;
}

function onChooseWorkflowTask(event, iframe_droppable_elm, iframe_win, iframe_offset, task_type, task_label, on_success_func) {
	var task_id = null;
	var taskFlowChartObj = iframe_win.taskFlowChartObj;
	var j_iframe_droppable_elm = $(iframe_droppable_elm);
	
	//preparing droppable if is ".connector_overlay_add_icon"
	if (j_iframe_droppable_elm.hasClass("connector_overlay_add_icon")) {
		var droppable_connection = taskFlowChartObj.TaskFlow.getOverlayConnectionId(j_iframe_droppable_elm);
		
		if (droppable_connection)
			task_id = taskFlowChartObj.ContextMenu.addTaskByTypeToConnection(task_type, droppable_connection);
	}
	//preparing droppable if is ".tasks_flow"
	else {
		var tasks_flow_offset = j_iframe_droppable_elm.offset();
		var tasks_flow_event_x = event.clientX - iframe_offset.left - tasks_flow_offset.left;
		var tasks_flow_event_y = event.clientY - iframe_offset.top - tasks_flow_offset.top;
		
		task_id = taskFlowChartObj.ContextMenu.addTaskByType(task_type, {
			top: tasks_flow_event_y,
			left: tasks_flow_event_x,
		});
	}
	
	//preparing task properties according with dragged and dropped table
	if (task_id) {
		//set task label
		var label_obj = {label: task_label};
		
		taskFlowChartObj.TaskFlow.setTaskLabelByTaskId(task_id, label_obj); //set {label: table_name}, so the TaskFlow.setTaskLabel method ignores the prompt and adds the default label or an auto generated label.
		
		//open properties
		taskFlowChartObj.Property.showTaskProperties(task_id);
		
		if (typeof on_success_func == "function")
			on_success_func(task_id);
	}
	
	iframe_win.taskFlowChartObj.getMyFancyPopupObj().hideLoading();
}

//Note that this logic was taken from edit_php_code.js:chooseIncludeFile
function onChooseWorkflowIncludeFileTask(iframe_win, file_tree_item, task_id) {
	//get method props
	var file_tree_item_a = file_tree_item.children("a");
	var edit_url = file_tree_item_a[0].hasAttribute("edit_url") ? file_tree_item_a.attr("edit_url") : file_tree_item_a.attr("edit_raw_file_url");
	var bean_name = getParameterByName(edit_url, "bean_name");
	var file_path = getParameterByName(edit_url, "path");
	
	//preparing task properties according with dragged and dropped table
	var taskFlowChartObj = iframe_win.taskFlowChartObj;
	var selected_task_properties = iframe_win.$("#" + taskFlowChartObj.Property.selected_task_properties_id);
	var task_html_elm = selected_task_properties.find(".include_file_task_html");
	
	//set include path
	var include_path = typeof iframe_win.getNodeIncludePath == "function" ? iframe_win.getNodeIncludePath(file_tree_item, file_path, bean_name) : null;
	
	if (include_path) {
		task_html_elm.find(".file_path input").val(include_path);
		task_html_elm.find(".type select").val("");
		task_html_elm.find(".once input").prop("checked", true).attr("checked", "");
	}
	
	//save properties
	taskFlowChartObj.Property.saveTaskProperties();
	
	//load again task
	taskFlowChartObj.Property.showTaskProperties(task_id);
}

//Note that this logic was taken from edit_php_code.js:chooseObjectMethod
function onChooseWorkflowCallObjectMethodTask(iframe_win, file_tree_item, task_id) {
	//get method props
	var file_tree_item_a = file_tree_item.children("a");
	var edit_url = file_tree_item_a.attr("edit_url");
	var bean_name = getParameterByName(edit_url, "bean_name");
	var file_path = getParameterByName(edit_url, "path");
	var obj_class = getParameterByName(edit_url, "class");
	var method = getParameterByName(edit_url, "method");
	
	//preparing task properties according with dragged and dropped table
	var taskFlowChartObj = iframe_win.taskFlowChartObj;
	var selected_task_properties = iframe_win.$("#" + taskFlowChartObj.Property.selected_task_properties_id);
	var task_html_elm = selected_task_properties.find(".call_object_method_task_html");
	
	//set include path
	var include_path = typeof iframe_win.getNodeIncludePath == "function" ? iframe_win.getNodeIncludePath(file_tree_item, file_path, bean_name) : null;
	
	if (include_path) {
		task_html_elm.find(".include_file input[name=include_file_path]").val(include_path);
		task_html_elm.find(".include_file select[name=include_file_path_type]").val("");
		task_html_elm.find(".include_file input[name=include_once]").prop("checked", true).attr("checked", "");
	}
	
	//set method props
	task_html_elm.find(".method_obj_name input").val(obj_class);
	task_html_elm.find(".method_name input").val(method);
	
	var bean_ui_props = bean_name && iframe_win.main_layers_properties && iframe_win.main_layers_properties.hasOwnProperty(bean_name) && iframe_win.main_layers_properties[bean_name].hasOwnProperty("ui") ? iframe_win.main_layers_properties[bean_name]["ui"] : {};
	var get_file_properties_url = bean_ui_props.hasOwnProperty("file") && bean_ui_props["file"].hasOwnProperty("attributes") && bean_ui_props["file"]["attributes"] ? bean_ui_props["file"]["attributes"]["get_file_properties_url"] : null;
	
	var class_methods = get_file_properties_url && typeof iframe_win.getClassMethods == "function" ? iframe_win.getClassMethods(get_file_properties_url, file_path, obj_class) : null;
	var method_static = false;
	
	if (class_methods)
		for (var i = 0; i < class_methods.length; i++)
			if (class_methods[i]["name"] == method) {
				method_static = class_methods[i]["static"];
				break;
			}
	
	if (method_static)
		task_html_elm.find(".method_static input[type=checkbox]").prop("checked", true).attr("checked", "");
	else
		task_html_elm.find(".method_static input[type=checkbox]").prop("checked", false).removeAttr("checked");
	
	if (get_file_properties_url && typeof iframe_win.getMethodArguments == "function")
		if (iframe_win.auto_convert || confirm("Do you wish to update automatically this method arguments?")) {
			var args = iframe_win.getMethodArguments(get_file_properties_url, file_path, obj_class, method);
			iframe_win.ProgrammingTaskUtil.setArgs(args, task_html_elm.find(".method_args .args"));
		}
	
	//save properties
	taskFlowChartObj.Property.saveTaskProperties();
	
	//load again task
	taskFlowChartObj.Property.showTaskProperties(task_id);
}

//Note that this logic was taken from edit_php_code.js:chooseFunction
function onChooseWorkflowCallFunctionTask(iframe_win, file_tree_item, task_id) {
	//get method props
	var file_tree_item_a = file_tree_item.children("a");
	var edit_url = file_tree_item_a.attr("edit_url");
	var bean_name = getParameterByName(edit_url, "bean_name");
	var file_path = getParameterByName(edit_url, "path");
	var func_name = getParameterByName(edit_url, "function");
	
	//preparing task properties according with dragged and dropped table
	var taskFlowChartObj = iframe_win.taskFlowChartObj;
	var selected_task_properties = iframe_win.$("#" + taskFlowChartObj.Property.selected_task_properties_id);
	var task_html_elm = selected_task_properties.find(".call_function_task_html");
	
	//set include path
	var include_path = typeof iframe_win.getNodeIncludePath == "function" ? iframe_win.getNodeIncludePath(file_tree_item, file_path, bean_name) : null;
	
	if (include_path) {
		task_html_elm.find(".include_file input[name=include_file_path]").val(include_path);
		task_html_elm.find(".include_file select[name=include_file_path_type]").val("");
		task_html_elm.find(".include_file input[name=include_once]").prop("checked", true).attr("checked", "");
	}
	
	//set function props
	task_html_elm.find(".func_name input").val(func_name);
	
	var bean_ui_props = bean_name && iframe_win.main_layers_properties && iframe_win.main_layers_properties.hasOwnProperty(bean_name) && iframe_win.main_layers_properties[bean_name].hasOwnProperty("ui") ? iframe_win.main_layers_properties[bean_name]["ui"] : {};
	var get_file_properties_url = bean_ui_props.hasOwnProperty("file") && bean_ui_props["file"].hasOwnProperty("attributes") && bean_ui_props["file"]["attributes"] ? bean_ui_props["file"]["attributes"]["get_file_properties_url"] : null;
	
	if (get_file_properties_url && typeof iframe_win.getFunctionArguments == "function")
		if (iframe_win.auto_convert || confirm("Do you wish to update automatically this function arguments?")) {
			var args = iframe_win.getFunctionArguments(get_file_properties_url, file_path, func_name);
			iframe_win.ProgrammingTaskUtil.setArgs(args, task_html_elm.find(".func_args .args"));
		}
	
	//save properties
	taskFlowChartObj.Property.saveTaskProperties();
	
	//load again task
	taskFlowChartObj.Property.showTaskProperties(task_id);
}

//Note that this logic was taken from edit_php_code.js:chooseBusinessLogic
function onChooseWorkflowCallBusinessLogicTask(iframe_win, file_tree_item, task_id) {
	//get method props
	var file_tree_item_a = file_tree_item.children("a");
	var edit_url = file_tree_item_a.attr("edit_url");
	var bean_name = getParameterByName(edit_url, "bean_name");
	var bean_file_name = getParameterByName(edit_url, "bean_file_name");
	var file_path = getParameterByName(edit_url, "path");
	var service = getParameterByName(edit_url, "service");
	var method = getParameterByName(edit_url, "method");
	var func_name = getParameterByName(edit_url, "function");
	
	var module_id = file_path.lastIndexOf("/") != -1 ? file_path.substr(0, file_path.lastIndexOf("/")) : file_path;
	module_id = module_id.replace(/\//g, ".");
	var service_id = service && method ? service + "." + method : func_name;
	
	//preparing task properties according with dragged and dropped table
	var taskFlowChartObj = iframe_win.taskFlowChartObj;
	var selected_task_properties = iframe_win.$("#" + taskFlowChartObj.Property.selected_task_properties_id);
	var task_html_elm = selected_task_properties.find(".call_business_logic_task_html");
	
	//preparing broker method obj
	var broker_name = file_tree_item_a.parent().closest(".main_node_businesslogic").find(" > a > label").text().toLowerCase();
	onChooseWorkflowTaskBrokerMethodObj(iframe_win, task_html_elm, broker_name);
	
	//preparing module id
	task_html_elm.find(".module_id input").val(module_id);
	task_html_elm.find(".module_id select").val("string");
	
	//preparing service id
	task_html_elm.find(".service_id input").val(service_id);
	task_html_elm.find(".service_id select").val("string");
	
	var bean_ui_props = bean_name && iframe_win.main_layers_properties && iframe_win.main_layers_properties.hasOwnProperty(bean_name) && iframe_win.main_layers_properties[bean_name].hasOwnProperty("ui") ? iframe_win.main_layers_properties[bean_name]["ui"] : {};
	var get_file_properties_url = bean_ui_props.hasOwnProperty("file") && bean_ui_props["file"].hasOwnProperty("attributes") && bean_ui_props["file"]["attributes"] ? bean_ui_props["file"]["attributes"]["get_file_properties_url"] : null;
	
	if (get_file_properties_url && typeof iframe_win.updateBusinessLogicParams == "function")
		iframe_win.updateBusinessLogicParams(task_html_elm, bean_file_name, bean_name, file_path, service_id);
	
	//save properties
	taskFlowChartObj.Property.saveTaskProperties();
	
	//load again task
	taskFlowChartObj.Property.showTaskProperties(task_id);
}

//Note that this logic was taken from edit_php_code.js:chooseClassName
function onChooseWorkflowCreateClassObjectTask(iframe_win, file_tree_item, task_id) {
	//get method props
	var file_tree_item_a = file_tree_item.children("a");
	var edit_url = file_tree_item_a.attr("edit_url");
	var bean_name = getParameterByName(edit_url, "bean_name");
	var file_path = getParameterByName(edit_url, "path");
	var class_obj = getParameterByName(edit_url, "class");
	
	//preparing task properties according with dragged and dropped table
	var taskFlowChartObj = iframe_win.taskFlowChartObj;
	var selected_task_properties = iframe_win.$("#" + taskFlowChartObj.Property.selected_task_properties_id);
	var task_html_elm = selected_task_properties.find(".create_class_object_task_html");
	
	//set include path
	var include_path = typeof iframe_win.getNodeIncludePath == "function" ? iframe_win.getNodeIncludePath(file_tree_item, file_path, bean_name) : null;
	
	if (include_path) {
		task_html_elm.find(".include_file input[name=include_file_path]").val(include_path);
		task_html_elm.find(".include_file select[name=include_file_path_type]").val("");
		task_html_elm.find(".include_file input[name=include_once]").prop("checked", true).attr("checked", "");
	}
	
	//preparing class_name
	task_html_elm.find(".class_name input").val(class_obj);
	
	var bean_ui_props = bean_name && iframe_win.main_layers_properties && iframe_win.main_layers_properties.hasOwnProperty(bean_name) && iframe_win.main_layers_properties[bean_name].hasOwnProperty("ui") ? iframe_win.main_layers_properties[bean_name]["ui"] : {};
	var get_file_properties_url = bean_ui_props.hasOwnProperty("file") && bean_ui_props["file"].hasOwnProperty("attributes") && bean_ui_props["file"]["attributes"] ? bean_ui_props["file"]["attributes"]["get_file_properties_url"] : null;
	
	if (get_file_properties_url && typeof iframe_win.getMethodArguments == "function")
		if (iframe_win.auto_convert || confirm("Do you wish to update automatically this class arguments?")) {
			var args = iframe_win.getMethodArguments(get_file_properties_url, file_path, class_obj, "__construct");
			iframe_win.ProgrammingTaskUtil.setArgs(args, task_html_elm.find(".class_args .args"));
		}
	
	//save properties
	taskFlowChartObj.Property.saveTaskProperties();
	
	//load again task
	taskFlowChartObj.Property.showTaskProperties(task_id);
}

//Note that this logic was taken from edit_php_code.js:chooseHibernateObject
function onChooseWorkflowCallHibernateObjectTask(iframe_win, file_tree_item, task_id) {
	//get method props
	var file_tree_item_a = file_tree_item.children("a");
	var edit_url = file_tree_item_a.attr("edit_url");
	var file_path = getParameterByName(edit_url, "path");
	var hbn_obj = getParameterByName(edit_url, "obj");
	
	var module_id = file_path.lastIndexOf("/") != -1 ? file_path.substr(0, file_path.lastIndexOf("/")) : file_path;
	module_id = module_id.replace(/\//g, ".");
	var service_id = hbn_obj;
	
	//preparing task properties according with dragged and dropped table
	var taskFlowChartObj = iframe_win.taskFlowChartObj;
	var selected_task_properties = iframe_win.$("#" + taskFlowChartObj.Property.selected_task_properties_id);
	var task_html_elm = selected_task_properties.find(".call_hibernate_object_task_html");
	
	//preparing broker method obj
	var broker_name = file_tree_item_a.parent().closest(".main_node_hibernate").find(" > a > label").text().toLowerCase();
	onChooseWorkflowTaskBrokerMethodObj(iframe_win, task_html_elm, broker_name);
	
	//preparing module id
	task_html_elm.find(".module_id input").val(module_id);
	task_html_elm.find(".module_id select").val("string");
	
	//preparing service id
	task_html_elm.find(".service_id input").val(service_id);
	task_html_elm.find(".service_id select").val("string");
	
	//save properties
	taskFlowChartObj.Property.saveTaskProperties();
	
	//load again task
	taskFlowChartObj.Property.showTaskProperties(task_id);
}

//Note that this logic was taken from edit_php_code.js:chooseHibernateObjectMethod
function onChooseWorkflowCallHibernateMethodTask(iframe_win, file_tree_item, task_id) {
	//get method props
	var file_tree_item_a = file_tree_item.children("a");
	var edit_url = file_tree_item_a.attr("edit_url");
	var bean_name = getParameterByName(edit_url, "bean_name");
	var bean_file_name = getParameterByName(edit_url, "bean_file_name");
	var file_path = getParameterByName(edit_url, "path");
	var hbn_obj = getParameterByName(edit_url, "obj");
	var query_id = getParameterByName(edit_url, "query_id");
	var query_type = getParameterByName(edit_url, "query_type");
	var relationship_type = getParameterByName(edit_url, "relationship_type");
	
	var module_id = file_path.lastIndexOf("/") != -1 ? file_path.substr(0, file_path.lastIndexOf("/")) : file_path;
	module_id = module_id.replace(/\//g, ".");
	
	//preparing task properties according with dragged and dropped table
	var taskFlowChartObj = iframe_win.taskFlowChartObj;
	var selected_task_properties = iframe_win.$("#" + taskFlowChartObj.Property.selected_task_properties_id);
	var task_html_elm = selected_task_properties.find(".call_hibernate_method_task_html");
	
	//preparing broker method obj
	var broker_name = file_tree_item_a.parent().closest(".main_node_hibernate").find(" > a > label").text().toLowerCase();
	onChooseWorkflowTaskBrokerMethodObj(iframe_win, task_html_elm, broker_name);
	
	//preparing module id
	task_html_elm.find(".module_id input").val(module_id);
	task_html_elm.find(".module_id select").val("string");
	
	//preparing service id
	task_html_elm.find(".service_id input").val(hbn_obj);
	task_html_elm.find(".service_id select").val("string");
	
	//preparing query id /rel name
	var method = null;
	
	if (relationship_type == "queries") {
		task_html_elm.find(".sma_query_id input").val(query_id);
		task_html_elm.find(".sma_query_id select").val("string");
		
		task_html_elm.find(".sma_query_type input").val(query_type);
		task_html_elm.find(".sma_query_type select[name=sma_query_type]").val(query_type);
		task_html_elm.find(".sma_query_type select[name=sma_query_type_type]").val("string");
		
		method = "call" + query_type.charAt(0).toUpperCase() + query_type.slice(1).toLowerCase();
	}
	else if (relationship_type == "relationships") {
		task_html_elm.find(".sma_rel_name input").val(query_id);
		task_html_elm.find(".sma_rel_name select").val("string");
		
		method = "findRelationship";
	}
	else if (relationship_type == "native") {
		method = query_id;
	}
	
	//preparing method name
	task_html_elm.find(".service_method .service_method_string").val(method);
	task_html_elm.find(".service_method .service_method_type").val("string");
	
	iframe_win.CallHibernateMethodTaskPropertyObj.onChangeServiceMethodType( task_html_elm.find(".service_method .service_method_type")[0] );
	iframe_win.CallHibernateMethodTaskPropertyObj.onChangeServiceMethod( task_html_elm.find(".service_method .service_method_string")[0] );
	
	//preparing parameters
	if (typeof iframe_win.updateHibernateObjectMethodParams == "function") {
		var db_driver = iframe_win.default_db_driver ? iframe_win.default_db_driver : ""; //Do not use getIframeBeanDBDriver(iframe_win, bean_name), bc this is only for the DBDriver beans and this is a hibernate bean.
		var db_type = iframe_win.default_db_type ? iframe_win.default_db_type : "db";
		
		iframe_win.updateHibernateObjectMethodParams(task_html_elm, bean_file_name, bean_name, db_driver, db_type, file_path, query_type, query_id, hbn_obj, relationship_type);
	}
	
	//save properties
	taskFlowChartObj.Property.saveTaskProperties();
	
	//load again task
	taskFlowChartObj.Property.showTaskProperties(task_id);
}

//Note that this logic was taken from edit_php_code.js:chooseQuery
function onChooseWorkflowCallIbatisQueryTask(iframe_win, file_tree_item, task_id) {
	//get method props
	var file_tree_item_a = file_tree_item.children("a");
	var edit_url = file_tree_item_a.attr("edit_url");
	var bean_name = getParameterByName(edit_url, "bean_name");
	var bean_file_name = getParameterByName(edit_url, "bean_file_name");
	var file_path = getParameterByName(edit_url, "path");
	var query_id = getParameterByName(edit_url, "query_id");
	var query_type = getParameterByName(edit_url, "query_type");
	var relationship_type = getParameterByName(edit_url, "relationship_type");
	
	var module_id = file_path.lastIndexOf("/") != -1 ? file_path.substr(0, file_path.lastIndexOf("/")) : file_path;
	module_id = module_id.replace(/\//g, ".");
	
	//preparing task properties according with dragged and dropped table
	var taskFlowChartObj = iframe_win.taskFlowChartObj;
	var selected_task_properties = iframe_win.$("#" + taskFlowChartObj.Property.selected_task_properties_id);
	var task_html_elm = selected_task_properties.find(".call_ibatis_query_task_html");
	
	//preparing broker method obj
	var broker_name = file_tree_item_a.parent().closest(".main_node_ibatis").find(" > a > label").text().toLowerCase();
	onChooseWorkflowTaskBrokerMethodObj(iframe_win, task_html_elm, broker_name);
	
	//preparing module id
	task_html_elm.find(".module_id input").val(module_id);
	task_html_elm.find(".module_id select").val("string");
	
	//preparing service id
	var service_id = task_html_elm.children(".service_id");
	service_id.children("input").val(query_id);
	service_id.children("select").val("string");

	//preparing query type
	var service_type = task_html_elm.children(".service_type");
	var service_type_type = service_type.children(".service_type_type");
	service_type_type.val("string");
	iframe_win.CallIbatisQueryTaskPropertyObj.onChangeServiceType(service_type_type[0]);
	service_type.children(".service_type_string").val(query_type.toLowerCase());
	
	//preparing parameters
	if (typeof iframe_win.updateQueryParams == "function") {
		var db_driver = iframe_win.default_db_driver ? iframe_win.default_db_driver : ""; //Do not use getIframeBeanDBDriver(iframe_win, bean_name), bc this is only for the DBDriver beans and this is a hibernate bean.
		var db_type = iframe_win.default_db_type ? iframe_win.default_db_type : "db";
		
		iframe_win.updateQueryParams(task_html_elm, bean_file_name, bean_name, db_driver, db_type, file_path, query_type, query_id, "", relationship_type);
	}
	
	//save properties
	taskFlowChartObj.Property.saveTaskProperties();
	
	//load again task
	taskFlowChartObj.Property.showTaskProperties(task_id);
}

function onChooseWorkflowTaskBrokerMethodObj(iframe_win, task_html_elm, broker_name) {
	//update the selected broker
	var select = task_html_elm.find(".broker_method_obj select");
	//console.log(broker_name);
	
	if (select && select[0])
		for (var i = 0; i < select[0].options.length; i++) {
			var option = select[0].options[i];
			
			if (option.value.indexOf('("' + broker_name + '")') != -1) {
				select.val( option.value );
				iframe_win.BrokerOptionsUtilObj.onBrokerChange(select[0]);
				break;
			}
		}
}

function onChooseWorkflowDBTableTaskOptions(event, iframe_droppable_elm, iframe_win, iframe_offset, db_driver, table_name, task_type, url) {
	var popup = $(".choose_db_table_task_options_popup");
	
	if (!popup[0]) {
		popup = $('<div class="myfancypopup with_title choose_db_table_task_options_popup">'
				+ '<div class="title">Choose your options:</div>'
				+ '<div class="method_name">'
					+ '<label>Action:</label>'
					+ '<select>'
						+ '<option value="insertObject">Insert</option>'
						+ '<option value="updateObject">Update</option>'
						+ '<option value="deleteObject">Delete</option>'
						+ '<option value="findObjects">List</option>'
						+ '<option value="countObjects">Count</option>'
					+ '</select>'
				+ '</div>'
				+ '<div class="button">'
					+ '<input type="button" value="Proceed" onclick="DBTableTaskOptionsFancyPopup.settings.updateFunction(this)">'
				+ '</div>'
			+ '</div>');
		$(document.body).append(popup);
	}
	
	DBTableTaskOptionsFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		
		updateFunction: function(elm) {
			//get table attributes url
			if (url) {
				var method_name = popup.find(".method_name select").val();
				var j_iframe_droppable_elm = $(iframe_droppable_elm);
				
				//show workflow loading
				iframe_win.taskFlowChartObj.getMyFancyPopupObj().showLoading();
				
				//fetch url
				$.ajax({
					type : "get",
					url : url,
					dataType : "json",
					success : function(data, textStatus, jqXHR) {
						var task_label = method_name.replace(/_/g, " ") + " " + table_name;
						
						onChooseWorkflowTask(event, iframe_droppable_elm, iframe_win, iframe_offset, task_type, task_label, function(task_id) {
							var taskFlowChartObj = iframe_win.taskFlowChartObj;
							var DBDAOActionTaskPropertyObj = iframe_win.DBDAOActionTaskPropertyObj;
							
							//prepare table attributes
							var table_attributes = {};
							
							for (var attribute_name in data)
								if (attribute_name != "properties") {
									var attribute_props = data[attribute_name]["properties"];
									delete attribute_props["item_id"];
									delete attribute_props["item_type"];
									delete attribute_props["item_menu"];
									delete attribute_props["bean_name"];
									delete attribute_props["bean_file_name"];
									delete attribute_props["table"];
									
									table_attributes[attribute_name] = attribute_props;
								}
							
							//preparing task properties according with dragged and dropped table
							var selected_task_properties = iframe_win.$("#" + taskFlowChartObj.Property.selected_task_properties_id);
							var task_html_elm = selected_task_properties.find(".db_dao_action_task_html");
							
							var select = task_html_elm.find(".method_name select");
							select.val(method_name);
							DBDAOActionTaskPropertyObj.onChangeMethodName(select[0]);
							
							var table_and_attributes = {
								table: table_name,
								attributes: table_attributes,
							};
							DBDAOActionTaskPropertyObj.chooseTable(select[0], table_and_attributes);
							
							//save properties
							taskFlowChartObj.Property.saveTaskProperties();
							
							//get saved task properties
							var task_property_values = taskFlowChartObj.TaskFlow.tasks_properties[task_id];
							task_property_values = task_property_values ? task_property_values : {};
							
							//set db driver, if not the default one
							if (db_driver && db_driver != iframe_win.default_db_driver) {
								task_property_values["options_type"] = "array";
								task_property_values["options"] = {
									key: "db_driver",
									key_type: "string",
									value: db_driver,
									value_type: "string"
								};
							}
							
							//set new task properties
							taskFlowChartObj.TaskFlow.tasks_properties[task_id] = task_property_values;
							
							//load again task
							taskFlowChartObj.Property.showTaskProperties(task_id);
						});
					},
					error : function(jqXHR, textStatus, errorThrown) { 
						var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
						taskFlowChartObj.StatusMessage.showError((errorThrown ? errorThrown + " error.\n" : "") + "Error trying to get table attributes.\nPlease try again..." + msg);
						
						iframe_win.taskFlowChartObj.getMyFancyPopupObj().hideLoading();
					},
				});
			}
			else
				iframe_win.taskFlowChartObj.StatusMessage.showError("Could not get table attributes because there is no table correspondent url!");
			
			DBTableTaskOptionsFancyPopup.hidePopup();
		},
	});
	DBTableTaskOptionsFancyPopup.showPopup();
}

function initDBTableAttributesSorting(elm) {
	var li = elm.parent();
	var tables_ul = li.parent();
	
	elm.sortable({
		scroll: true,
		scrollSensitivity: 20,
		//refreshPositions: true,
		
		connectWith: "ul",
		items: "li.jstree-node.jstree-leaf",
		containment: tables_ul,
		//appendTo: elm, //disable to allow copy attribute accross different tables.
		handle: "> a.jstree-anchor > i.jstree-icon.attribute",
		revert: true,
		cursor: "ns-resize",
          tolerance: "pointer",
		grid: [5, 5],
		axis: "y",
		helper: function(event, item) {
			var clone = item.clone();
			clone.addClass("sortable_helper");
			clone.children("a").removeClass("jstree-hovered jstree-clicked");
			clone.children("ul").remove();
			clone.children(".sub_menu").remove();
			
			return clone;
		},
		start: function(event, ui_obj) {
			//check if dragged item contains the attribute sort_url
			var item = ui_obj.item;
			var sort_url = item.children("a").attr("sort_url");
			
			if (sort_url) {
				item.show();
				item.data("parent_ul", item.parent());
				item.data("droppable_table_node", null);
				item.data("is_droppable_table_ul", null);
				
				return true;
			}
			
			return false;
		},
		sort: function(event, ui_obj) {
			if (ui_obj.placeholder.parent().is( ui_obj.item.data("parent_ul") ))
				$(this).sortable("option", "cursor", "crosshair"); //set cursor to crosshair
			else
				$(this).sortable("option", "cursor", "ns-resize"); //set cursor to ns-resize
		},
		stop: function(event, ui_obj) {
			var item = ui_obj.item;
			var a = item.children("a");
			var original_parent_ul = item.data("parent_ul");
			var parent_ul = item.parent();
			var parent_li = parent_ul.parent();
			
			item.data("parent_ul", null);
			
			if (ui_obj.helper)
				ui_obj.helper.remove();
			
			if (parent_li.find(" > a > i.table").length == 1 && original_parent_ul) {
				var attribute_table = a.attr("table_name");
				var attribute_name = a.attr("attribute_name");
				var attribute_index = item.index();
				
				var previous_item = item.prev("li:not(.ui-sortable-helper)");
				var next_item = item.next("li:not(.ui-sortable-helper)");
				var previous_attribute = previous_item.length ? previous_item.children("a").attr("attribute_name") : null;
				var next_attribute = next_item.length ? next_item.children("a").attr("attribute_name") : null;
				
				var droppable_table_node = item.data("droppable_table_node");
				var is_droppable_table_ul = item.data("is_droppable_table_ul");
				
				if (droppable_table_node) {
					parent_li = $(droppable_table_node);
					parent_ul = parent_li.children("ul");
					
					if (!is_droppable_table_ul) //only resets vars if droppable is in table li, and not in the ul, bc it will get the wrong values.
						attribute_index = previous_item = next_item = previous_attribute = next_attribute = null;
				}
				
				var data = {
					attribute_table: attribute_table,
					attribute_name: attribute_name,
					attribute_index: attribute_index,
					previous_attribute: previous_attribute,
					next_attribute: next_attribute,
				};
				var callback = function(a, attr_name, action, new_name, url, tree_node_id_to_be_updated) {
					refreshAndShowNodeChildsByNodeId( parent_li.attr("id") ); //refresh all table's attributes
				};
				
				//move attribute
				if (parent_ul.is(original_parent_ul) && !droppable_table_node) {
					manageDBTableAction(a[0], "sort_url", "sort_attribute", callback, callback, data);
					
					return true; //true: so the attribute can be moved to the new position.
				}
				else { //copy attribute to another table, adding it as a foreign key
					manageDBTableAction(parent_li.children("a")[0], "add_fk_attribute_url", "add_fk_attribute", function(a, attr_name, action, new_name, url, tree_node_id_to_be_updated) {
						//add clone attribute
						var clone = item.clone();
						clone.removeClass("primary_key");
						
						if (previous_attribute) //prepare previous_item bc it looses its reference
							previous_item = parent_li.find(" > ul > li > a[attribute_name=" + previous_attribute + "]").parent();
						
						if (next_attribute) //prepare previous_item bc it looses its reference
							next_item = parent_li.find(" > ul > li > a[attribute_name=" + next_attribute + "]").parent();
						
						if (previous_item && previous_item.length > 0)
							previous_item.after(clone);
						else if (next_item && next_item.length > 0)
							next_item.before(clone);
						else
							parent_ul.append(clone);
						
						callback(a, attr_name, action, new_name, url, tree_node_id_to_be_updated);
					}, callback, data);
					
					return false; //false: so the attribute can be reverted to the initial position.
				}
			}
			
			return false; //false: so the attribute can be reverted to the initial position.
		},
	});
}

function showProperties(menu_item) {
	var selected_menu_properties = $("#selected_menu_properties");
	selected_menu_properties.hide();
	
	var id = menu_item.getAttribute("properties_id");
	//console.log(menu_item);
	var html;
	
	if (id && menu_item_properties.hasOwnProperty(id) && menu_item_properties[id]) {
		var properties = menu_item_properties[id];
		
		if (properties) {
			html = "";
			
			for (var key in properties) {
				var value = properties[key];
				
				key = key.replace(/_/g, " ").toLowerCase();
				key = key.charAt(0).toUpperCase() + key.slice(1);
				
				html += "<label>" + key + ": </label>" + value + "<br/>\n";
			}
		}
		else
			html = "There are no properties to be shown";
	}
	else
		html = "There are no properties to be shown";
	
	selected_menu_properties.find(".content").html(html);
	
	MyFancyPopup.init({
		elementToShow: $("#selected_menu_properties")
	});
	
	MyFancyPopup.showPopup();
	
	return false;
}

function goTo(a, attr_name, originalEvent) {
	originalEvent = originalEvent || window.event;
	
	if (originalEvent && (originalEvent.ctrlKey || originalEvent.keyCode == 65)) 
		return goToNew(a, attr_name);
	
	var url = a.getAttribute(attr_name);
	//console.log(attr_name+":"+url);
	
	if (url) {
		var d = new Date();
		url += (url.indexOf("?") != -1 ? "&" : "?") + "t=" + d.getTime();
		
		goToHandler(url, a, attr_name, originalEvent);
	}
	
	var j_a = $(a);
	if (j_a.hasClass("jstree-anchor")) 
		last_selected_node_id = j_a.parent().attr("id");
	else 
		last_selected_node_id = j_a.parent().parent().attr("last_selected_node_id");
	
	MyContextMenu.hideAllContextMenu();
	
	return false;
}

function goToNew(a, attr_name) {
	var rand = Math.random() * 10000;
	var win = openWindow(a, attr_name, "tab" + rand);
	
	return false;
}

function openWindow(a, attr_name, tab) {
	var url = a.getAttribute(attr_name);
	//console.log(attr_name+":"+url);
	
	if (url) {
		var win = typeof tab != "undefined" && tab ? window.open(url, tab) : window.open(url);
		
		MyContextMenu.hideAllContextMenu();
		
		if(win) { //Browser has allowed it to be opened
			win.focus();
			return win;
		}
		else //Broswer has blocked it
			alert('Please allow popups for this site');
	}
}

function goToPopup(a, attr_name, originalEvent, popup_class_name, on_success_popup_action_handler) {
	var url = a.getAttribute(attr_name);
	//console.log(attr_name+":"+url);
	
	if (url) {
		//check if ctrlKey is pressed and if yes, open in a new window
		originalEvent = originalEvent || window.event;
		
		if (originalEvent && (originalEvent.ctrlKey || originalEvent.keyCode == 65)) {
			a.setAttribute(attr_name, url.replace(/(\?|&)popup=1/i, "")); //remove popup parameter from url
			goToNew(a, attr_name);
			a.setAttribute(attr_name, url); //add original url again
			return false;
		}
		
		//prepare popup
		var popup = $(".go_to_popup");
		
		if (!popup[0]) {
			popup = $('<div class="myfancypopup go_to_popup ' + (popup_class_name ? popup_class_name : "") + '"></div>');
			$(document.body).append(popup);
		}
		else
			popup[0].className = 'myfancypopup go_to_popup ' + (popup_class_name ? popup_class_name : "");
		
		popup.html('<iframe src="' + url + '"></iframe>');
		
		MyFancyPopup.init({
			elementToShow: popup,
			//parentElement: document,
			
			on_success_popup_action_handler: on_success_popup_action_handler,
		});
		MyFancyPopup.showPopup();
		
		var j_a = $(a);
		if (j_a.hasClass("jstree-anchor")) 
			last_selected_node_id = j_a.parent().attr("id");
		else 
			last_selected_node_id = j_a.parent().parent().attr("last_selected_node_id");
	}
	
	return false;
}

function onSuccessfullPopupAction(opts) {
	if (MyFancyPopup.settings && typeof MyFancyPopup.settings.on_success_popup_action_handler == "function")
		MyFancyPopup.settings.on_success_popup_action_handler(opts);
	
	MyFancyPopup.hidePopup();
}

function onSuccessfullAddProject(opts) {
	var filter_by_layout, bean_name, bean_file_name, project, is_ctrl_key_pressed;
	
	if (opts) {
		filter_by_layout = opts["new_filter_by_layout"];
		bean_name = opts["new_bean_name"];
		bean_file_name = opts["new_bean_file_name"];
		project = opts["new_project"];
		is_ctrl_key_pressed = opts["is_ctrl_key_pressed"];
	}
	
	if (is_ctrl_key_pressed && filter_by_layout && typeof admin_home_project_page_url != "undefined") {
		url = admin_home_project_page_url.replace("#filter_by_layout#", filter_by_layout);
		
		var rand = Math.random() * 10000;
		var win = window.open(url, "tab" + rand);
		
		if (win) { //Browser has allowed it to be opened
			win.focus();
			
			MyFancyPopup.hidePopup();
			
			//refreshes window
			window.document.location = "" + window.document.location;
			
			return true; //don't execute code below.
		}
	}
	
	if (filter_by_layout || (bean_name && bean_file_name && project)) {
		var current_url = "" + document.location;
		
		current_url = current_url.indexOf("#") != -1 ? current_url.substr(0, current_url.indexOf("#")) : current_url; //remove # so it can refresh page
		current_url = current_url.replace(/(bean_name|bean_file_name|project|filter_by_layout)=([^&]*)&?/g, ""); //erase previous bean_name|bean_file_name|project|filter_by_layout attributes
		current_url += current_url.indexOf("?") != -1 ? "" : "?"; //add "?" if apply
		current_url += "&bean_name=" + bean_name + "&bean_file_name=" + bean_file_name + "&project=" + project + "&filter_by_layout=" + filter_by_layout; //add new bean_name|bean_file_name|project|filter_by_layout
		current_url = current_url.replace(/\?&+/, "?"); //replace "?&&&" with "?"
		
		//set cookie with default page
		window.MyJSLib.CookieHandler.setCurrentDomainEternalRootSafeCookie('default_page', ''); //save cookie with url, so when we refresh the browser, the right panel contains the latest opened url
		
		//refresh main window with new params
		document.location = current_url;
	}
	else
		refreshLastNodeParentChildsIfNotTreeLayoutAndMainTreeNode(opts);
}

function onSuccessfullEditProject(opts) {
	if (opts && opts["is_rename_project"]) {
		var selected_project_elm = $("#top_panel .filter_by_layout > ul li.selected a");
		var selected_project = selected_project_elm.attr("value");
		
		//only refresh page if exists a selected project
		if (!selected_project_elm[0] || selected_project) {
			var url = "" + document.location;
			url = url.indexOf("#") != -1 ? url.substr(0, url.indexOf("#")) : url; //remove # so it can refresh page
			
			if (url.match(/(&|\?)filter_by_layout\s*=([^&#]+)/)) { //check if parent url has any filter_by_layout
				url = url.replace(/(&|\?)filter_by_layout\s*=\s*([^&#]*)/, "");
				
				if (opts["new_filter_by_layout"])
					url += (url.indexOf("?") != -1 ? "&" : "?") + "filter_by_layout=" + opts["new_filter_by_layout"];
			}
			
			//get default_page url and check if contains filter_by_layout in the url and if so, replace it with new project name
			var default_page = MyJSLib.CookieHandler.getCookie('default_page');
			
			if (default_page && opts["new_filter_by_layout"]) {
				if (default_page.match(/(&|\?)filter_by_layout\s*=([^&#]+)/)) { //check if default_page url has any filter_by_layout
					default_page = default_page.replace(/(&|\?)filter_by_layout\s*=\s*([^&#]*)/, "");
					default_page += (default_page.indexOf("?") != -1 ? "&" : "?") + "filter_by_layout=" + opts["new_filter_by_layout"];
				}
				
				//set cookie with default page
				MyJSLib.CookieHandler.setCurrentDomainEternalRootSafeCookie('default_page', default_page); //save cookie with url, so when we refresh the browser, the right panel contains the latest opened url
			}
			
			document.location = url;
		}
		else {
			//refresh grand-parent folder
			refreshLastNodeParentChildsIfNotTreeLayoutAndMainTreeNode(opts);
			
			//check if project was moved to a grand-parent folder and if so, refresh that grand-parent
			if (last_selected_node_id && opts["new_filter_by_layout"] && opts["old_filter_by_layout"]) {
				var new_filter_by_layout = opts["new_filter_by_layout"];
				var old_filter_by_layout = opts["old_filter_by_layout"];
				
				//remove duplicated, first and last slashes
				new_filter_by_layout = new_filter_by_layout.replace(/(^\/|\/$)/g, "").replace(/\/+/g, "/");
				old_filter_by_layout = old_filter_by_layout.replace(/(^\/|\/$)/g, "").replace(/\/+/g, "/");
				
				//get project folder
				var suffix = null;
				
				do {
					var pos = new_filter_by_layout.lastIndexOf("/");
					
					if (pos != -1) {
						new_filter_by_layout = new_filter_by_layout.substr(0, pos);
						
						//check if project folder is inside of old_filter_by_layout
						if (old_filter_by_layout.indexOf(new_filter_by_layout + "/") === 0) {
							suffix = old_filter_by_layout.substr(new_filter_by_layout.length + 1);
							break;
						}
					}
				}
				while (pos != -1);
				
				//prepare parent level
				if (suffix) {
					var parent_level = suffix.split("/").length - 1;
					var node_id = getLastNodeParentId();
					
					while (parent_level > 0) {
						node_id = getNodeParentIdByNodeId(node_id);
						parent_level--;
					}
					
					//prepare parent node
					refreshNodeChildsByNodeId(node_id);
				}
			}
		}
	}
	else
		refreshLastNodeParentChildsIfNotTreeLayoutAndMainTreeNode(opts);
}

function onSuccessfullRemoveProject(a, attr_name, action, new_file_name, url, tree_node_id_to_be_updated) {
	var url = a.getAttribute(attr_name);
	
	if (url) {
		var bean_name = getParameterByName(url, "bean_name");
		var bean_file_name = getParameterByName(url, "bean_file_name");
		var project = getParameterByName(url, "path");
		
		var current_url = "" + document.location;
		var current_bean_name = getParameterByName(current_url, "bean_name");
		var current_bean_file_name = getParameterByName(current_url, "bean_file_name");
		var current_project = getParameterByName(current_url, "project");
		var current_filter_by_layout = getParameterByName(current_url, "filter_by_layout");
		
		project = project.replace(/^\/+/g, "").replace(/\/+$/g, "");
		current_project = current_project.replace(/^\/+/g, "").replace(/\/+$/g, "");
		current_filter_by_layout = current_filter_by_layout.replace(/^\/+/g, "").replace(/\/+$/g, "");
		
		var is_removed_project_selected = (bean_name == current_bean_name && bean_file_name == current_bean_file_name && project == current_project) || current_filter_by_layout.substr(- (project.length + 1)) == "/" + project;
		
		if (!is_removed_project_selected) {
			current_bean_name = MyJSLib.CookieHandler.getCookie('bean_name');
			current_bean_file_name = MyJSLib.CookieHandler.getCookie('bean_file_name');
			current_project = MyJSLib.CookieHandler.getCookie('project');
			current_filter_by_layout = MyJSLib.CookieHandler.getCookie('filter_by_layout');
			
			is_removed_project_selected = (bean_name == current_bean_name && bean_file_name == current_bean_file_name && project == current_project) || current_filter_by_layout.substr(- (project.length + 1)) == "/" + project;
		}
		
		if (is_removed_project_selected) {
			current_url = current_url.indexOf("#") != -1 ? current_url.substr(0, current_url.indexOf("#")) : current_url; //remove # so it can refresh page
			current_url = current_url.replace(/(bean_name|bean_file_name|project|filter_by_layout)=([^&]*)&?/g, ""); //erase previous bean_name|bean_file_name|project|filter_by_layout attributes
			
			//set cookie with default page
			window.MyJSLib.CookieHandler.setCurrentDomainEternalRootSafeCookie('default_page', ''); //save cookie with url, so when we refresh the browser, the right panel contains the latest opened url
			
			//refresh main window with new params
			document.location = current_url;
		}
	}
}

function refreshLastNodeParentChildsIfNotTreeLayoutAndMainTreeNode(opts) {
	var pid = getLastNodeParentId();
	var is_project = last_selected_node_id && $("#" + last_selected_node_id + " > a > i.project").length > 0;
	
	if (pid && is_project && $("#left_panel").is(".left_panel_with_tabs") && $("#left_panel .mytree #" + pid).is(".hide_tree_item"))
		return ;
	
	if (is_project)
		refreshLastNodeParentChilds();
	else
		refreshLastNodeChilds();
}

function manageFile(a, attr_name, action, on_success_callbacks) {
	var url = a.getAttribute(attr_name);
	//console.log(attr_name+":"+url);
	
	if (url) {
		var new_file_name;
		var props;
		var status = false;
		var original_action = action;
		
		var tree_node_id_to_be_updated = $(a).parent().parent().attr("last_selected_node_id");
		
		var file_name = getParameterByName(url, "path");
		file_name = file_name.substr(file_name.length - 1, 1) == "/" ? file_name.substr(0, file_name.length - 1) : file_name;
		file_name = file_name.lastIndexOf("/") != -1 ? file_name.substr(file_name.lastIndexOf("/") + 1) : file_name;
		
		switch (action) {
			case "remove": 
				var jstree_attr = $("#" + tree_node_id_to_be_updated).attr("data-jstree");
				var file_type = jstree_attr == '{"icon":"project"}' ? "project" : (jstree_attr == '{"icon":"project_folder"}' ? "projects folder" : null);
				
				if (file_type)
					status = confirm("Do you wish to remove the " + file_type + ": '" + file_name + "'?") && confirm("If you delete this " + file_type + ", you will loose all the created pages and other files inside of this " + file_type + "!\nDo you wish to continue?") && confirm("LAST WARNING:\nIf you proceed, you cannot undo this deletion!\nAre you sure you wish to remove this " + file_type + "?");
				else
					status = confirm("Do you wish to remove the file '" + file_name + "'?");
				
				break;
				
			case "create_folder": 
			case "create_file": 
				status = (new_file_name = prompt("Please write the file name:")); 
				break;
				
			case "rename_name": 
				action = "rename";
				var pos = file_name.lastIndexOf(".");
				
				if (pos != -1) {
					var base_name = file_name.substr(0, pos);
					var extension = file_name.substr(pos + 1);
					status = (new_file_name = prompt("Please write the new name:", base_name));
					new_file_name = ("" + new_file_name).replace(/^\s+/g, "").replace(/\s+$/g, ""); //trim name
					
					if (status && new_file_name)
						new_file_name += "." + extension;
				}
				else
					status = (new_file_name = prompt("Please write the new name:", base_name));
				break;
				
			case "rename": 
				status = (new_file_name = prompt("Please write the new name:", file_name)); 
				break;
				
			case "zip": 
			case "unzip": 
				//TODO: In the future allow the user to choose a destination folder
				new_file_name = "";//new_file_name is the destination folder. if empty, it means the zip file will be unziped into the same folder.
				
				status = confirm("You are about to " + action + " '" + file_name + "' into the same folder. Do you wish to proceed?");
				break;
				
			case "paste": 
				if (file_to_copy_or_cut) {
					try {
						props = file_to_copy_or_cut.replace(/,/g, "','").replace(/\[/g, "['").replace(/\]/g, "']");
						eval ('props = ' + props + ';');
					}
					catch (e) {
						props = null;
					}
				}
				
				if (props) {
					status = copy_or_cut_action == "cut" ? confirm("You are about to cut and paste the file '" + props[2] + "' from the '" + props[0] + "' Layer to the '" + file_name + "' folder.\nDo you wish to continue?") : true; 
					new_file_name = file_to_copy_or_cut;
					action = copy_or_cut_action == "cut" ? "paste_and_remove" : action;
				}
				else
					alert("Error trying to paste file! In order to paste, you must copy or cut a valid file first...");
				
				break;
		}
		
		if (status) {
			var is_file_new_name_action = action == "rename" || action == "create_folder" || action == "create_file";
			
			if (is_file_new_name_action && new_file_name) {
				new_file_name = ("" + new_file_name).replace(/^\s+/g, "").replace(/\s+$/g, ""); //trim name
				
				//normalize new file name
				var allow_upper_case = a.getAttribute("allow_upper_case") == 1; //in case of businesslogic services class
				new_file_name = normalizeFileName(new_file_name, allow_upper_case);
			}
			
			if (is_file_new_name_action && !new_file_name)
				alert("Error: File name cannot be empty");
			else {
				url = url.replace("#action#", action);
				url = url.replace("#extra#", new_file_name);
				
				url = encodeUrlWeirdChars(url); //Note: Is very important to add the encodeUrlWeirdChars otherwise if a value has accents, won't work in IE.
				
				var str = action == "create_folder" || action == "create_file" ? "create" : action.replace(/_/g, " ");
				
				$.ajax({
					type : "get",
					url : url,
					success : function(data, textStatus, jqXHR) {
						if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
							showAjaxLoginPopup(jquery_native_xhr_object.responseURL, url, function() {
								StatusMessageHandler.removeLastShownMessage("error");
								manageFile(a, attr_name, original_action, on_success_callbacks);
							});
						else if (data == "1") {
							if (action == "create_folder" || action == "create_file" || action == "paste" || action == "paste_and_remove")
								refreshAndShowNodeChildsByNodeId(tree_node_id_to_be_updated);
							else if (action != "remove")
								refreshNodeParentChildsByChildId(tree_node_id_to_be_updated);
							
							StatusMessageHandler.showMessage("File " + str + (action == "unzip" || action == "zip" ? "pe" : "") + "d correctly", "", "bottom_messages", 1500);
							
							on_success_callbacks = $.isArray(on_success_callbacks) ? on_success_callbacks : [on_success_callbacks];
							for (var i = 0; i < on_success_callbacks.length; i++)
								if (typeof on_success_callbacks[i] == "function")
									on_success_callbacks[i](a, attr_name, action, new_file_name, url, tree_node_id_to_be_updated);
							
							if (action == "remove" || action == "paste_and_remove") {
								var li = $("#" + (action == "remove" ? tree_node_id_to_be_updated : copy_or_cut_tree_node_id));
								
								if (li.is(":last-child")) 
									li.prev("li").addClass("jstree-last");
								
								li.remove();
							}
						}
						else
							StatusMessageHandler.showError("There was a problem trying to " + str + " file. Please try again..." + (data ? "\n" + data : ""));
					},
					error : function(jqXHR, textStatus, errorThrown) { 
						var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
						StatusMessageHandler.showError((errorThrown ? errorThrown + " error.\n" : "") + "Error trying to " + str + " file.\nPlease try again..." + msg);
					},
				});
			}
		}
	}
	
	MyContextMenu.hideAllContextMenu();
	
	return false;
}

function renameProject(a, attr_name, action, new_file_name, url, tree_node_id_to_be_updated) {
	alert("Please don't forget to go to the permissions panel and update the correspondent permissions..."); //Do not use StatusMessageHandler.showMessage bc the onSuccessfullEditProject will refresh the main page
	
	//refresh page and replace old project in url 
	var opts = {
		is_rename_project: true,
		layer_bean_folder_name: null,
		old_filter_by_layout: null,
		new_filter_by_layout: null,
	};
	
	var bean_name = getParameterByName(url, "bean_name");
	var bean_file_name = getParameterByName(url, "bean_file_name");
	var file_path = getParameterByName(url, "path");
	file_path = file_path ? file_path.replace(/\/$/g, "") : ""; //remove last /
	
	var file_name = file_path;
	var folder_path = "";
	
	if (file_path.lastIndexOf("/") != -1) {
		file_name = file_path.substr(file_path.lastIndexOf("/") + 1);
		folder_path = file_path.substr(0, file_path.lastIndexOf("/") + 1);
	}
	
	var new_file_path = folder_path + new_file_name;
	
	if (main_layers_properties)
		for (var bn in main_layers_properties) {
			var layer_props = main_layers_properties[bn];
			
			if (layer_props["bean_name"] == bean_name && layer_props["bean_file_name"] == bean_file_name) {
				var layer_bean_folder_name = layer_props["layer_bean_folder_name"];
				
				layer_bean_folder_name = layer_bean_folder_name.replace(/\/+/g, "/").replace(/^\//g, "").replace(/\/$/g, ""); //remove duplicated slashes and at the begin and at the end.
				
				opts["layer_bean_folder_name"] = layer_bean_folder_name;
				opts["old_filter_by_layout"] = layer_bean_folder_name + "/" + file_path;
				opts["new_filter_by_layout"] = layer_bean_folder_name + "/" + new_file_path;
				break;
			}
		}
	
	//console.log(opts);
	last_selected_node_id = tree_node_id_to_be_updated;
	
	onSuccessfullEditProject(opts);
}

function triggerFileNodeAfterCreateFile(a, attr_name, action, new_file_name, url, tree_node_id_to_be_updated) {
	var node = $("#" + tree_node_id_to_be_updated);
	
	//normalize new file name
	var allow_upper_case = a.getAttribute("allow_upper_case") == 1; //in case of businesslogic services class
	var new_file_name_normalized = normalizeFileName(new_file_name, allow_upper_case, true);
	
	if (node[0])
		mytree.refreshNodeChilds(node[0], {
			ajax_callback_last: function(ul, data) {
				$(ul).find(" > li > a > label").each(function(idx, item) {
					item = $(item);
					
					if (item.text().toLowerCase() == new_file_name.toLowerCase() || item.text().toLowerCase() == new_file_name_normalized.toLowerCase()) {
						var new_a = item.parent();
						
						if (new_a.attr("onClick")) {
							try {
								new_a.trigger("click");
							}
							catch(e) {
								if (console && console.log)
									console.log(e);
							}
						}
						
						return false;
					}
				});
			},
		});
}

function triggerFileNodeAfterCreatePage(a, attr_name, action, new_file_name, url, tree_node_id_to_be_updated, ccc) {
	var node = $("#" + tree_node_id_to_be_updated);
	
	//normalize new file name
	var allow_upper_case = a.getAttribute("allow_upper_case") == 1; //in case of businesslogic services class
	var new_file_name_normalized = normalizeFileName(new_file_name, allow_upper_case, true);
	
	if (node[0])
		mytree.refreshNodeChilds(node[0], {
			ajax_callback_last: function(ul, data) {
				$(ul).find(" > li > a > label").each(function(idx, item) {
					item = $(item);
					
					if (item.text().toLowerCase() == new_file_name.toLowerCase() || item.text().toLowerCase() == new_file_name_normalized.toLowerCase()) {
						var new_a = item.parent();
						
						try {
							var on_click = new_a.attr("onClick");
							
							if (new_a.attr("add_url")) {
								goToPopup(new_a[0], "add_url", window.event, 'with_iframe_title add_entity_popup big', function(opts) {
									if (on_click) {
										try {
											//set ctr key into event so it can open a new window
											var is_ctrl_key_pressed = opts && opts["is_ctrl_key_pressed"];
											
											if (is_ctrl_key_pressed) {
												if (on_click.match(/^return goTo\(/))
													new_a.attr("onClick", on_click.replace(/^return goTo\(/, "return goToNew("));
												else if (on_click.match(/^goTo\(/))
													new_a.attr("onClick", on_click.replace(/^goTo\(/, "return goToNew("));
											}
											
											//trigger on click
											new_a.trigger("click");
											
											if (is_ctrl_key_pressed)
												new_a.attr("onClick", on_click);
										}
										catch(e) {
											if (console && console.log)
												console.log(e);
										}
									}
								});
							}
							else if (on_click)
								new_a.trigger("click");
						}
						catch(e) {
							if (console && console.log)
								console.log(e);
						}
						
						return false;
					}
				});
			},
		});
}

function managePresentationFile(a, attr_name, action, new_file_name, url, tree_node_id_to_be_updated) {
	if (url && url.indexOf("/src/entity/") != -1) { //deletes view file for entity
		var str = action == "create_folder" || action == "create_file" ? "create" : action;
		
		var entity_folder = $("#" + tree_node_id_to_be_updated);
		var entities_folder = entity_folder.closest('li[data-jstree=\'{"icon":"entities_folder"}\']');
		var entities_folder_a = entities_folder.children("a");
		var project_with_auto_view = parseInt(entities_folder_a.attr("project_with_auto_view")) == 1;
		
		if (project_with_auto_view && confirm("Do you wish to " + str + " the correspondent view too?")) {
			var view_url = url.replace("/src/entity/", "/src/view/"); //does not need encodeUrlWeirdChars bc the url is already encoded
			
			var options = {
				url : view_url,
				success : function(data) {
					if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
						showAjaxLoginPopup(jquery_native_xhr_object.responseURL, view_url, function() {
							StatusMessageHandler.removeLastShownMessage("error");
							managePresentationFile(a, attr_name, action, new_file_name, url, tree_node_id_to_be_updated);
						});
					else if (data == "1") {
						StatusMessageHandler.showMessage("View " + str + "d successfully", "", "bottom_messages", 1500);
					
						if (entity_folder[0]) {
							var p = entity_folder;
							var view_folder = null;
						
							while (p != null && view_folder == null) {
								p = p.parent().parent();
							
								if (p.children("a").children("i").hasClass("project")) {
									view_folder = p.children("ul").children("li")[1];
									break;
								}
							}
						
							if (view_folder) {
								refreshNodeChildsByNodeId( view_folder.getAttribute("id") );
							}
						}
					}
					else
						StatusMessageHandler.showError("There was a problem trying to " + str + " the correspondent view. Please try again...") + (data ? "\n" + data : "");
				},
				error : function(jqXHR, textStatus, errorThrown) { 
					var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
					StatusMessageHandler.showError((errorThrown ? errorThrown + " error.\n" : "") + "Error trying to " + str + " file.\nPlease try again..." + msg);
				},
				async: false,
			};
			
			$.ajax(options);
		}
	}
	else if (url && url.indexOf("/src/template/") != -1 && action == "remove") { //deletes template webroot folder if apply
		var tree_node = $("#" + tree_node_id_to_be_updated);
		var p = tree_node.parent().parent();
		var is_template_folder = tree_node.find(" > a > i").is(".folder, .template_folder") && p.find(" > a > i").is(".templates_folder"); //by default it should be a .template_folder
		
		//deletes template folder from webroot
		if (is_template_folder && confirm("Do you wish to remove the correspondent webroot folder too")) {
			var template_url = url.replace("/src/template/", "/webroot/template/"); //does not need encodeUrlWeirdChars bc the url is already encoded
			
			var options = {
				url : template_url,
				success : function(data) {
					if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
						showAjaxLoginPopup(jquery_native_xhr_object.responseURL, template_url, function() {
							StatusMessageHandler.removeLastShownMessage("error");
							managePresentationFile(a, attr_name, action, new_file_name, url, tree_node_id_to_be_updated);
						});
					else if (data == "1") {
						StatusMessageHandler.showMessage("Template webroot deleted successfully", "", "bottom_messages", 1500);
						
						var folder_name = tree_node.find(" > a > label").text();
						var project = p.closest("li[data-jstree=\'{\"icon\":\"project\"}\']");
						var labels = project.find(" > ul > li[data-jstree=\'{\"icon\":\"webroot_folder\"}\']").find(" > ul > li > a > label");
						var webroot_template_li = null;
						
						$.each(labels, function(idx, label) {
							if ($(label).text() == "template") {
								webroot_template_li = $(label).parent().parent();
								return false;
							}
						});
						
						if (webroot_template_li) {
							var labels = webroot_template_li.find(" > ul > li > a > label");
							var selected_template_li = null;
							
							$.each(labels, function(idx, label) {
								if ($(label).text() == folder_name) {
									selected_template_li = $(label).parent().parent();
									return false;
								}
							});
							
							if (selected_template_li)
								selected_template_li.remove();
							else
								refreshNodeChildsByNodeId( webroot_template_li.attr("id") );
						}
					}
					else
						StatusMessageHandler.showError("There was a problem trying to delete the correspondent template webroot folder. Please try again...") + (data ? "\n" + data : "");
				},
				error : function(jqXHR, textStatus, errorThrown) { 
					var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
					StatusMessageHandler.showError((errorThrown ? errorThrown + " error.\n" : "") + "Error trying to delete template webroot folder.\nPlease try again..." + msg);
				},
			};
			
			$.ajax(options);
		}
	}
}

function manageBusinessLogicObject(a, attr_name, action) {
	manageFile(a, attr_name, action, function(a, attr_name, action, new_file_name, url, tree_node_id_to_be_updated) {
		var file_node_id = getNodeParentIdByNodeId(tree_node_id_to_be_updated);
		refreshNodeChildsByNodeId(file_node_id);
	});
}

//Note that any change here, should be replicate in the edit_php_code.js:addIconToOpenBusinessLogicObjFromFileManagerTreePopup
function createClassObjectOrMethodOrFunction(a, save_attr_name, edit_attr_name, type, on_success_callbacks, originalEvent) {
	var save_url = a.getAttribute(save_attr_name);
	//console.log(save_attr_name+":"+save_url);
	
	if (save_url) {
		var type_label = type.replace(/_/g, " ");
		var new_file_name = prompt("Please write the " + type_label + " name:");
		var status = new_file_name && typeof new_file_name == "string" && new_file_name.replace(/\s/g, "") != "";
		
		var tree_node_id_to_be_updated = $(a).parent().parent().attr("last_selected_node_id");
		
		if (status) {
			if (new_file_name) {
				new_file_name = ("" + new_file_name).replace(/(^\s+|\s+$)/g, ""); //trim name and remove spaces
				
				if (new_file_name) {
					//normalize new file name
					new_file_name = normalizeFileName(new_file_name, true);
					new_file_name = new_file_name.replace(/\b[a-z]/g, function(letter) { //Do not call .toLowerCase() otherwise when we create a new object service, it will put all letters lower case.
						return letter.toUpperCase();
					}).replace(/\s+/g, ""); //ucwords
					
					if (type == "service_method" || type == "class_method" || type == "function")
						new_file_name = new_file_name[0].toLowerCase() + new_file_name.substr(1).replace(/\s+/g, "");
				}
			}
			
			if (!new_file_name)
				alert("Error: " + type_label + " name cannot be empty");
			else {
				var post_data = {
					object : {
						name : new_file_name,
						is_business_logic_service : 1,
					}
				};
				
				if (a.getAttribute("static") == "1")
					post_data["object"]["static"] = 1;
				
				$.ajax({
					type : "post",
					url : save_url,
					data : post_data,
					success : function(data, textStatus, jqXHR) {
						if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
							showAjaxLoginPopup(jquery_native_xhr_object.responseURL, save_url, function() {
								StatusMessageHandler.removeLastShownMessage("error");
								createClassObjectOrMethodOrFunction(a, save_attr_name, edit_attr_name, type, on_success_callbacks, originalEvent);
							});
						else {
							var data_status = data == "1";
							var json_data = data && ("" + data).substr(0, 1) == "{" ? JSON.parse(data) : null;
							
							if ($.isPlainObject(json_data) && json_data.hasOwnProperty("status") && json_data["status"])
								data_status = true;
							
							if (data_status) {
								var node = $("#" + tree_node_id_to_be_updated);
								var i = node.find(" > a > i");
								
								if (i.filter(".file, .util_file").length > 0)
									refreshAndShowNodeChilds(node.parent().parent());
								else if (i.filter(".service, .class").length > 0)
									refreshAndShowNodeChilds(node.parent().parent().parent().parent());
								else
									refreshAndShowNodeChildsByNodeId(tree_node_id_to_be_updated);
								
								StatusMessageHandler.showMessage(type_label + " created correctly", "", "bottom_messages", 1500);
								
								on_success_callbacks = $.isArray(on_success_callbacks) ? on_success_callbacks : [on_success_callbacks];
								for (var i = 0; i < on_success_callbacks.length; i++)
									if (typeof on_success_callbacks[i] == "function")
										on_success_callbacks[i](a, save_attr_name, edit_attr_name, type, originalEvent, new_file_name, tree_node_id_to_be_updated);
								
								var edit_url = a.getAttribute(edit_attr_name);
								
								if (edit_url) {
									//replace new_file_name in the url
									edit_url = edit_url.replace(/#extra#/g, new_file_name);
									
									goToHandler(edit_url, a, edit_attr_name, originalEvent);
								}
							}
							else
								StatusMessageHandler.showError("There was a problem trying to create this " + type_label + ". Please try again..." + (data && !json_data ? "\n" + data : ""));
						}
					},
					error : function(jqXHR, textStatus, errorThrown) { 
						var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
						StatusMessageHandler.showError((errorThrown ? errorThrown + " error.\n" : "") + "Error trying to create this " + type_label + ".\nPlease try again..." + msg);
					},
				});
			}
		}
	}
	
	MyContextMenu.hideAllContextMenu();
	
	return false;
}

function removeItem(a, attr_name, on_success_callback) {
	if (confirm("Do you wish to remove this item?")) {
		var url = a.getAttribute(attr_name);
		//console.log(attr_name+":"+url);
	
		if (url) {
			var tree_node_id_to_be_updated = $(a).parent().parent().attr("last_selected_node_id");
			url = encodeUrlWeirdChars(url); //Note: Is very important to add the encodeUrlWeirdChars otherwise if a value has accents, won't work in IE.
			
			$.ajax({
				type : "get",
				url : url,
				dataType : "json",
				success : function(data, textStatus, jqXHR) {
					if(data == 1) {
						StatusMessageHandler.showMessage("Removed successfully", "", "bottom_messages", 1500);
						
						if (typeof on_success_callback == "function")
							on_success_callback(a, attr_name, tree_node_id_to_be_updated);
						
						$("#" + tree_node_id_to_be_updated).remove();
					}
					else
						StatusMessageHandler.showError("Error trying to remove item.\nPlease try again..." + (data ? "\n" + data : ""));
				},
				error : function(jqXHR, textStatus, errorThrown) { 
					if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
						showAjaxLoginPopup(jquery_native_xhr_object.responseURL, url, function() {
							StatusMessageHandler.removeLastShownMessage("error");
							removeItem(a, attr_name);
						});
					else {
						var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
						StatusMessageHandler.showError((errorThrown ? errorThrown + " error.\n" : "") + "Error trying to remove item.\nPlease try again..." + msg);
					}
				},
			});
		}
	}
	
	MyContextMenu.hideAllContextMenu();
	
	return false;
}

function removeBusinessLogicObject(a, attr_name) {
	removeItem(a, attr_name, function(a, attr_name, tree_node_id_to_be_updated) {
		var file_node_id = getNodeParentIdByNodeId(tree_node_id_to_be_updated);
		refreshNodeChildsByNodeId(file_node_id);
	});
}

function refresh(a) {
	var tree_node_id_to_be_refreshed = $(a).parent().parent().attr("last_selected_node_id");
	
	setTimeout(function() {
		refreshAndShowNodeChildsByNodeId(tree_node_id_to_be_refreshed);
	}, 100);
	
	MyContextMenu.hideAllContextMenu();
	
	return false;
}

function toggleAllChildren(a) {
	var tree_node_id_to_show_all_Children = $(a).parent().parent().attr("last_selected_node_id");
	var node = $("#" + tree_node_id_to_show_all_Children);
	
	node.toggleClass("show_all_children");
	
	if (node.find(" > ul > li.hidden").length == 0)
		StatusMessageHandler.showMessage("There are no private children", "", "bottom_messages", 1500);
	else if (node.hasClass("show_all_children"))
		StatusMessageHandler.showMessage("Private children shown", "", "bottom_messages", 1500);
	else
		StatusMessageHandler.showMessage("Private children hidden", "", "bottom_messages", 1500);
		
}

function copyFile(a) {
	return copyOrCutFile(a, "copy");
}
function cutFile(a) {
	return copyOrCutFile(a, "cut");
}
function copyOrCutFile(a, action) {
	copy_or_cut_tree_node_id = $(a).parent().parent().attr("last_selected_node_id");
	copy_or_cut_action = action;
	
	setTimeout(function() {
		file_to_copy_or_cut = a.getAttribute(action == "cut" ? "cut_url" : "copy_url");
		StatusMessageHandler.showMessage("File " + (action == "cut" ? "cut" : "copied") + " successfully", "", "bottom_messages", 1500);
	}, 100);
	
	MyContextMenu.hideAllContextMenu();
}

//a var could be a contextmenu item or a jstree-node
function manageDBTableAction(a, attr_name, action, on_success_callback, on_error_callback, opts) {
	var url = a.getAttribute(attr_name);
	//console.log(attr_name+":"+url);
	
	if (url) {
		var new_name, original_name;
		var status = false;
		var original_action = action;
		
		var is_jstree_node = $(a).parent().hasClass("jstree-node");
		var tree_node_id_to_be_updated = is_jstree_node ? $(a).parent().attr("id") : $(a).parent().parent().attr("last_selected_node_id");
		var node_a = $("#" + tree_node_id_to_be_updated + " > a");
		
		var table_name = node_a.attr("table_name");
		var attribute_name = node_a.attr("attribute_name");
		
		switch (action) {
			case "remove_table": 
				status = confirm("Do you really wish to remove this table: '" + table_name + "'?") && confirm("Are you sure you wish to remove this table? No rollback can be done!") && confirm("Last change! Do you really wish to proceed?");
				break;
				
			case "remove_attribute": 
				status = confirm("Do you really wish to remove this attribute: '" + attribute_name + "'?") && confirm("Are you sure you wish to remove this attribute? No rollback can be done!") && confirm("Last change! Do you really wish to proceed?");
				break;
				
			case "add_table": 
				status = (new_name = prompt("Please write the new table name:"));
				break;
				
			case "add_attribute": 
				status = (new_name = prompt("Please write the new attribute name:"));
				break;
			
			case "rename_table": 
				original_name = table_name;
				status = (new_name = prompt("Please write the new table name:", table_name));
				break;
			
			case "rename_attribute": 
				original_name = attribute_name;
				status = (new_name = prompt("Please write the new attribute name:", attribute_name));
				break;
			
			case "sort_attribute":
			case "add_fk_attribute": 
				if ($.isPlainObject(opts)) {
					url = url.replace("#previous_attribute#", opts["previous_attribute"] ? opts["previous_attribute"] : "");
					url = url.replace("#next_attribute#", opts["next_attribute"] ? opts["next_attribute"] : "");
					url = url.replace("#attribute_index#", $.isNumeric(opts["attribute_index"]) ? opts["attribute_index"] : "");
					url = url.replace("#fk_table#", opts["attribute_table"] ? opts["attribute_table"] : "");
					url = url.replace("#fk_attribute#", opts["attribute_name"] ? opts["attribute_name"] : "");
					
					status = true;
				}
				break;
			
			case "set_primary_key":
			case "set_null":
				var prop_key = action == "set_primary_key" ? "primary_key" : "null";
				var prop_value = $(a).parent().hasClass("checked") ? 0 : 1;
				var properties = {};
				properties[prop_key] = prop_value;
				var property_value = JSON.stringify(properties);
				
				url = url.replace("#properties#", property_value);
				status = true;
				break;
			
			case "set_type": 
				var input = $(a).children("input");
				var property_length = input[0].hasAttribute("disabled") ? null : input.val();
				var type_select = $(a).children("select");
				var property_type = type_select.val();
				var simple_props = type_select.data("simple_props");
				var properties = {
					type : property_type, 
					length: property_length
				};
				
				//check if type is simple type and update with the defaults values
				if ($.isPlainObject(simple_props)) {
					properties = simple_props;
					delete properties["label"];
					delete properties["name"];
					
					if ($.isArray(properties["type"])) {
						var original_type = type_select.find("option:selected").attr("original_type");
						
						//check if original type previous set belongs to the any simple types and if yes, stays with the original value, bc it was not changed. This is very important, bc if we have an attribute with a native type, which was converted to a simple type, when we convert it to the native type again, we must stay with original value. Otherwise we are changing automatically the types of the attributes without the consent of the user. The original type is is very important!!!
						if (original_type && $.inArray(original_type, properties["type"]) != -1)
							properties["type"] = original_type;
						else
							properties["type"] = properties["type"][0];
					}
					
					properties["length"] = property_length;
				}
				
				var property_value = JSON.stringify(properties);
				
				url = url.replace("#properties#", property_value);
				status = true;
				break;
		}
		
		if (status) {
			var is_new_name_action = action == "add_table" || action == "rename_table" || action == "add_attribute" || action == "rename_attribute";
			
			if (is_new_name_action && new_name) {
				new_name = ("" + new_name).replace(/^\s+/g, "").replace(/\s+$/g, ""); //trim name
				
				//normalize new file name
				var allow_upper_case = a.getAttribute("allow_upper_case") == 1; //in case of non standards DB names. DB, Table or attributes' names should always be lowercase - this is the standard.
				new_name = normalizeFileName(new_name, allow_upper_case);
			}
			
			if (is_new_name_action && (!new_name || new_name == original_name)) {
				if (!new_name)
					alert("Error: Name cannot be empty");
				else
					alert("Error: Name cannot be the same");
				
				if (typeof on_error_callback == "function")
					on_error_callback(a, attr_name, action, new_name, url, tree_node_id_to_be_updated);
			}
			else {
				var duplicated = false;
				
				//check for duplicates
				if (is_new_name_action) {
					duplicated = node_a.parent().children("ul").find(" > li > a[" + (action == "add_table" || action == "rename_table" ? "table_name" : "attribute_name") + "='" + new_name + "']").length > 0;
					
					if (duplicated)
						alert("Error: Name cannot be duplicated");
				}
				
				if (!duplicated) {
					StatusMessageHandler.showMessage("Saving... Wait a while...", "", "bottom_messages", 60000); //during 1 minute, but after the ajax finish, this message wil be removed.
					
					url = url.replace("#action#", action);
					url = url.replace("#extra#", new_name);
					
					url = encodeUrlWeirdChars(url); //Note: Is very important to add the encodeUrlWeirdChars otherwise if a value has accents, won't work in IE.
					
					var str = action == "add_table" || action == "add_attribute" ? "add" : (
						action == "rename_table" || action == "rename_attribute" ? "rename" : action.replace(/_/g, " ")
					);
					
					$.ajax({
						type : "get",
						url : url,
						success : function(data, textStatus, jqXHR) {
							StatusMessageHandler.removeLastShownMessage("info", "bottom_messages");
							
							if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
								showAjaxLoginPopup(jquery_native_xhr_object.responseURL, url, function() {
									StatusMessageHandler.removeLastShownMessage("error");
									manageDBTableAction(a, attr_name, original_action, on_success_callback, on_error_callback, opts);
								});
							else {
								//always refreshes the tree_node_id_to_be_updated
								if (action == "add_table" || action == "add_attribute" || action == "add_fk_attribute")
									refreshAndShowNodeChildsByNodeId(tree_node_id_to_be_updated);
								else if (action != "remove_table" && action != "remove_attribute")
									refreshNodeParentChildsByChildId(tree_node_id_to_be_updated);
								
								if (data == "1") {
									StatusMessageHandler.showMessage(str + " correctly", "", "bottom_messages", 1500);
									
									if (typeof on_success_callback == "function")
										on_success_callback(a, attr_name, action, new_name, url, tree_node_id_to_be_updated);
									
									if (action == "remove_table" || action == "remove_attribute") {
										var li = $("#" + tree_node_id_to_be_updated);
										
										if (li.is(":last-child")) 
											li.prev("li").addClass("jstree-last");
										
										li.remove();
									}
								}
								else {
									//always refreshes the tree_node_id_to_be_updated
									if (action == "remove_table" || action == "remove_attribute")
										refreshNodeParentChildsByChildId(tree_node_id_to_be_updated);
									
									var json_data = data && ("" + data).substr(0, 1) == "{" ? JSON.parse(data) : null;
									
									if ($.isPlainObject(json_data) && json_data.hasOwnProperty("sql") && json_data["sql"] && a.getAttribute("execute_sql_url")) { //try to show sql and then execute it manually
										
										showSQLToExecute(a, attr_name, action, on_success_callback, on_error_callback, opts, new_name, url, tree_node_id_to_be_updated, json_data);
										
										StatusMessageHandler.showError("There was a problem trying to execute this action.\nPlease check the correspondent SQL and execute it manually." + (json_data["error"] ? "\n" + json_data["error"] : ""));
									}
									else {
										StatusMessageHandler.showError("There was a problem trying to execute this action. Please try again..." + (data ? "\n" + data : ""));
										
										if (typeof on_error_callback == "function")
											on_error_callback(a, attr_name, action, new_name, url, tree_node_id_to_be_updated);
									}
								}
							}
						},
						error : function(jqXHR, textStatus, errorThrown) { 
							StatusMessageHandler.removeLastShownMessage("info", "bottom_messages");
							
							var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
							StatusMessageHandler.showError((errorThrown ? errorThrown + " error.\n" : "") + "Error trying to execute this action.\nPlease try again..." + msg);
							
							if (typeof on_error_callback == "function")
								on_error_callback(a, attr_name, action, new_name, url, tree_node_id_to_be_updated);
						},
					});
				}
			}
		}
		else if (typeof on_error_callback == "function")
			on_error_callback(a, attr_name, action, new_name, url, tree_node_id_to_be_updated);
	}
	else if (typeof on_error_callback == "function")
		on_error_callback(a, attr_name, action, null, url, null);
	
	MyContextMenu.hideAllContextMenu();
	
	return false;
}

function showSQLToExecute(a, attr_name, action, on_success_callback, on_error_callback, opts, new_name, url, tree_node_id_to_be_updated, json_data) {
	var url = a.getAttribute("execute_sql_url");
	var sql = json_data["sql"];
	var sql_str = sql.join(";");
	
	url += (url.indexOf("?") != -1 ? "&" : "?") + "popup=1&sql=" + encodeURIComponent(sql_str);
	
	var popup = $(".execute_sql_popup");
	
	if (!popup[0]) {
		popup = $('<div class="myfancypopup execute_sql_popup with_iframe_title"></div>');
		$(document.body).append(popup);
	}
	
	popup.html('<iframe></iframe>');
	
	//add onload event handler to detect if query was successfull executed, and if yes close popup.
	var iframe = popup.children("iframe");
	var iframe_on_load_func = function() {
		var status = $(this).contents().find(".sql_results table td").first().hasClass("success");
		
		if (status)
			MyFancyPopup.hidePopup();
	};
	iframe.bind("load", iframe_on_load_func);
	iframe.bind("unload", function() {
		iframe.bind("load", iframe_on_load_func);
	});
	iframe[0].src = url;
	
	MyFancyPopup.init({
		elementToShow: popup,
		//parentElement: document,
		onClose: function() {
			var status = iframe.contents().find(".sql_results table td").first().hasClass("success");
			
			if (tree_node_id_to_be_updated) {
				if (action == "add_table" || action == "add_attribute" || action == "add_fk_attribute")
					refreshAndShowNodeChildsByNodeId(tree_node_id_to_be_updated);
				else
					refreshNodeParentChildsByChildId(tree_node_id_to_be_updated);
			}
			
			if (status) {
				if (typeof on_success_callback == "function")
					on_success_callback(a, attr_name, action, new_name, url, tree_node_id_to_be_updated);
			}
			else if (typeof on_error_callback == "function")
				on_error_callback(a, attr_name, action, new_name, url, tree_node_id_to_be_updated);
		},
		
		popupClass: "execute_sql",
	});
	MyFancyPopup.showPopup();
}

function flushCacheFromAdmin(url) {
	$.ajax({
		type : "get",
		url : url,
		dataType : "text",
		success : function(data, textStatus, jqXHR) {
			if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
				showAjaxLoginPopup(jquery_native_xhr_object.responseURL, url, function() {
					StatusMessageHandler.removeLastShownMessage("error");
					flushCacheFromAdmin(url);
				});
			else if (data == "1") 
				StatusMessageHandler.showMessage("Cache flushed!", "", "bottom_messages", 1500);
			else
				StatusMessageHandler.showError("Cache NOT flushed! Please try again..." + (data ? "\n" + data : ""));
		},
		error : function(jqXHR, textStatus, errorThrown) { 
			if (jqXHR.responseText);
				StatusMessageHandler.showError(jqXHR.responseText);
		}
	});
	
	return false;
}

function getParameterByName(url, name) {
	name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
	var regex = new RegExp("[\\?&]" + name + "=([^&#]*)", "i");
	var results = ("" + url).match(regex);
	
	if (results === null || !results[1])
		return "";
	
	var parameter = results[1].replace(/\+/g, " "); //decodes the encoded spaces into spaces.
	
	try {
		parameter = decodeURIComponent(parameter);
	}
	catch(e) {
		if (console && console.log)
			console.log(e);
	}
	
	return parameter;
}

function chooseAvailableTool(url) {
	var popup = $(".choose_available_tool_popup");
	
	if (!popup[0]) {
		popup = $('<div class="myfancypopup with_iframe_title choose_available_tool_popup"><iframe src="' + url + '"></iframe></div>');
		$(document.body).append(popup);
	}
	
	ToolsFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		
		goTo: function(url, originalEvent) {
			var a = document.createElement("a");
			a.setAttribute("url", url);
			goTo(a, "url", originalEvent);
			
			ToolsFancyPopup.hidePopup();
		},
	});
	ToolsFancyPopup.showPopup();
	
	return false;
}

function chooseAvailableProject(url) {
	var popup = $(".choose_available_project_popup");
	
	if (!popup[0]) {
		popup = $('<div class="myfancypopup with_iframe_title choose_available_project_popup"><iframe src="' + url + '"></iframe></div>');
		$(document.body).append(popup);
	}
	
	ProjectsFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		
		goTo: function(url, originalEvent) {
			ProjectsFancyPopup.hidePopup();
			ProjectsFancyPopup.showOverlay();
			ProjectsFancyPopup.showLoading();
			
			setTimeout(function() {
				document.location = url;
			}, 300);
		},
	});
	ProjectsFancyPopup.showPopup();
	
	return false;
}

function chooseAvailableTutorial(url, originalEvent) {
	if (url) {
		//check if ctrlKey is pressed and if yes, open in a new window
		originalEvent = originalEvent || window.event;
		
		if (originalEvent && (originalEvent.ctrlKey || originalEvent.keyCode == 65)) {
			url = url.replace(/(\?|&)popup=1/i, ""); //remove popup parameter from url
			var a = $('<a url="' + url + '"></a>');
			goToNew(a[0], "url");
			
			a.remove();
			
			return false;
		}
	
		var popup = $(".choose_available_tutorial_popup");
		
		if (!popup[0]) {
			popup = $('<div class="myfancypopup with_iframe_title choose_available_tutorial_popup"><iframe src="' + url + '"></iframe></div>');
			$(document.body).append(popup);
		}
		
		ProjectsFancyPopup.init({
			elementToShow: popup,
			parentElement: document,
			
			goTo: function(url, originalEvent) {
				ProjectsFancyPopup.hidePopup();
				ProjectsFancyPopup.showOverlay();
				ProjectsFancyPopup.showLoading();
				
				setTimeout(function() {
					document.location = url;
				}, 300);
			},
		});
		ProjectsFancyPopup.showPopup();
	}
	
	return false;
}

function openOnlineTutorialsPopup(url, originalEvent) {
	if (url) {
		if (window.parent != window && typeof window.parent.openOnlineTutorialsPopup == "function") {
			window.parent.openOnlineTutorialsPopup(url, originalEvent);
			return;
		}
		
		//check if ctrlKey is pressed and if yes, open in a new window
		originalEvent = originalEvent || window.event;
		
		if (originalEvent && (originalEvent.ctrlKey || originalEvent.keyCode == 65)) {
			url = url.replace(/(\?|&)popup=1/i, ""); //remove popup parameter from url
			var a = $('<a url="' + url + '"></a>');
			goToNew(a[0], "url");
			
			a.remove();
			
			return false;
		}
		
		var popup = $(".choose_online_tutorials_popup");
		
		if (!popup[0]) {
			popup = $('<div class="myfancypopup with_title choose_online_tutorials_popup"><div class="title">Tutorials - How to?</div><iframe></iframe></div>');
			$(document.body).append(popup);
		}
		
		var iframe = popup.children("iframe");
		var main_navigator_reverse = $(document.body).is(".main_navigator_reverse");
		
		if (iframe[0].hasAttribute("src")) {
			var src = iframe.attr("src");
			var src_main_navigator_reverse = src.indexOf("main_navigator_reverse=1") != -1;
			
			if (src_main_navigator_reverse != main_navigator_reverse)
				iframe.removeAttr("src");
		}
		
		if (!iframe[0].hasAttribute("src")) {
			url += (url.indexOf("?") != -1 ? "&" : "?") + "popup=1&main_navigator_reverse=" + (main_navigator_reverse ? 1 : 0);
			
			iframe.attr("src", url);
		}
		
		ProjectsFancyPopup.init({
			elementToShow: popup,
			parentElement: document,
		});
		
		ProjectsFancyPopup.showPopup();
	}
}

function openConsole(url, originalEvent) {
	if (url) {
		//check if ctrlKey is pressed and if yes, open in a new window
		originalEvent = originalEvent || window.event;
		
		if (originalEvent && (originalEvent.ctrlKey || originalEvent.keyCode == 65)) {
			url = url.replace(/(\?|&)popup=1/i, ""); //remove popup parameter from url
			var a = $('<a url="' + url + '"></a>');
			goToNew(a[0], "url");
			
			a.remove();
			
			return false;
		}
		
		//prepare popup
		var popup = $(".log_console_popup");
		
		if (!popup[0]) {
			popup = $('<div class="myfancypopup with_iframe_title log_console_popup"></div>');
			$(document.body).append(popup);
		}
		
		popup.html('<iframe src="' + url + '"></iframe>');
		
		LogConsoleFancyPopup.init({
			elementToShow: popup,
			parentElement: document,
			
			onClose: function() {
				//remove iframe so the ajax request to refresh the logs doesn't get executed anymore.
				popup.children("iframe").remove();
			}
		});
		LogConsoleFancyPopup.showPopup();
	}
	
	return false;
}

if (typeof onToggleFullScreen != "function")
	function onToggleFullScreen(in_full_screen) {
		$("iframe").each(function(idx, iframe) {
			var full_screen_elm = $(iframe).contents().find(".top_bar .full_screen > a").first();
			
			if (full_screen_elm[0] && typeof iframe.contentWindow.toggleFullScreen == "function")
				iframe.contentWindow.toggleFullScreen(full_screen_elm[0], true, in_full_screen);
		});
	}
