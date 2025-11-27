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

function initContextMenus() {
	var file_tree = $("#file_tree");
	
	initFilesContextMenuOnExpertWorkspace(file_tree);
	
	prepareParentChildsEventToHideContextMenu(file_tree);
	addSubMenuIconToParentChildsWithContextMenu(file_tree);
	prepareParentChildsEventOnClick(file_tree);
}

function initFilesContextMenuOnExpertWorkspace(elm, request_data) {
	var folders = elm.find("li i.folder");
	var files = elm.find("li i.file");
	var zip_files = elm.find("li i.zip_file");
	
	folders.parent().addClass("link");
	files.parent().addClass("link");
	zip_files.parent().addClass("link");

	addLiContextMenu(folders.parent(), "folder_context_menu", {callback: onContextMenu});
	addLiContextMenu(files.parent(), "file_context_menu", {callback: onContextMenu});
	addLiContextMenu(zip_files.parent(), "zip_file_context_menu", {callback: onContextMenu});
}

function prepareLayerNodes2(ul, data) {
	if (data) {
		var bean_name = data.properties && data.properties.bean_name ? data.properties.bean_name : "";
		var main_layer_properties = main_layers_properties && bean_name && main_layers_properties[bean_name] ? main_layers_properties[bean_name] : null;
		
		initUlChildsContextMenuOnExpertWorkspace(ul, data, main_layer_properties);
		initUlChildsEvents(ul, data, main_layer_properties);
	}
}

function initUlChildsContextMenuOnExpertWorkspace(ul, data, main_layer_properties) {
	ul = $(ul);
	
	if (main_layer_properties)
		initFilesContextMenuOnExpertWorkspace(ul, data);
	
	prepareParentChildsEventToHideContextMenu(ul);
	addSubMenuIconToParentChildsWithContextMenu(ul);
}
