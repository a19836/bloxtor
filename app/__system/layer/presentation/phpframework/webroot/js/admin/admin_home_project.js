/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

var pagesFromFileManagerTree = null;
var templatesFromFileManagerTree = null;
var default_available_templates = ["empty", "ajax", "blank"];

$(function() {
	var name_elm = $(".admin_panel .project .project_title .name");
	var ctxmenu = name_elm.parent().find(" > .sub_menu > .mycontextmenu");
	
	if (ctxmenu[0])
		name_elm.addcontextmenu( ctxmenu.clone() );
	
	var project_files = $(".admin_panel .project_files");
	
	if (project_files[0]) {
		project_files.tabs({active: active_tab});
		
		pagesFromFileManagerTree = new MyTree({
			multiple_selection : false,
			toggle_selection : false,
			toggle_children_on_click : true,
			ajax_callback_before : prepareLayerNodes1,
			ajax_callback_after : prepareProjectLayerNodes2,
			ajax_callback_error : validateLayerNodesRequest,
			on_select_callback : selectMyTreeNode,
			default_id: "pages_",
		});
		pagesFromFileManagerTree.init("pages");
		
		templatesFromFileManagerTree = new MyTree({
			multiple_selection : false,
			toggle_selection : false,
			toggle_children_on_click : true,
			ajax_callback_before : prepareLayerNodes1,
			ajax_callback_after : prepareProjectLayerNodes2,
			ajax_callback_error : validateLayerNodesRequest,
			on_select_callback : selectMyTreeNode,
			default_id: "templates_",
		});
		templatesFromFileManagerTree.init("templates");
		
		pagesFromFileManagerTree.refreshNodeChilds( project_files.find(".pages > .mytree > li")[0] );
		templatesFromFileManagerTree.refreshNodeChilds( project_files.find(".templates > .mytree > li")[0] );
		
		onClickPagesTab();
		
		$(window).resize(function() {
			MyFancyPopup.updatePopup();
		});
		
		//if number of created pages are 1 or 0, then show popup so user can create new page
		if (is_fresh_project) {
			var msg = '<h1>Create your first page by clicking in the button below</h1>'
					+ '<div>'
						+ '<button onClick="showCreateFilePopupThroughMessage()" title="Create a new folder or page">Add New</button>'
					+ '</div>';
			StatusMessageHandler.showMessage(msg, "create_new_page_message", "", 60000 * 60 * 24); //1 day of expiration
		}
	}
	
	MyFancyPopup.hidePopup();
});

function prepareProjectLayerNodes2(ul, data) {
	prepareLayerNodes2(ul, data);
	
	ul = $(ul);
	var main_parent = ul.parent().closest(".mytree").parent();
	
	if (ul.children().length == 0) {
		var is_page = main_parent.hasClass("pages");
		var html = '<li class="empty_files">No files availabes...</li>';
		
		if (is_page)
			html = '<li class="empty_files">'
					+ '<span class="icon empty_page"></span>'
					+ '<p>No pages/folders yet</p>'
					+ '<p>When you have new pages/folders, you will see them here.</p>'
					+ '<button onClick="showCreateFilePopup()"><span class="icon add"></span> Start to create a page</button>'
				+ '</li>';
		else if (ul.parent().parent().parent().is(main_parent)) //if pages or if templates root
			html = '<li class="empty_files">'
					+ '<span class="icon empty_template"></span>'
					+ '<p>No templates yet</p>'
					+ '<p>When you have new templates, you will see them here.</p>'
					+ '<button onClick="importTemplates()"><span class="icon add"></span> Start to import a template</button>'
				+ '</li>';
		
		ul.append(html);
	}
	else {
		//prepare properties_by_file_path
		var properties_by_file_path = {};
		
		if (data)
			for (var key in data) {
				if (key != "properties" && key != "aliases") {
					var item = data[key];
					var item_path = item["properties"]["path"];
					
					properties_by_file_path[item_path] = item;
				}
			}
		
		//only show pages and templates. All the other items should be removed
		ul.find("li").each(function(idx, li) {
			li = $(li);
			var a = li.children("a");
			
			if (!a.children("i").is(".entity_file, .template_file, .folder, .template_folder"))
				li.remove();
			else
				li.attr("title", a.children("label").text());
		});
		
		//prepare folders
		var folders = ul.find("i.folder, i.template_folder")
		
		if (folders.length > 0) {
			for (var i = folders.length; i >= 0; i--) {
				var elm = $(folders[i]);
				var a = elm.parent();
				var li = a.parent();
				var file_path = a.attr("folder_path");
				var is_template_folder = elm.is("i.template_folder");
				var properties = properties_by_file_path[file_path];
				var modified_date = properties && properties["properties"] && properties["properties"]["item_menu"] ? properties["properties"]["item_menu"]["modified_date"] : "";
				
				var html = '<div class="modified_date">Last updated ' + modified_date + '</div>'
						+ '<div class="sub_menu" onClick="openProjectFileSubmenu(this)">'
							+ '<i class="icon sub_menu_vertical"></i>'
							+ '<ul class="mycontextmenu with_top_right_triangle">'
								+ '<li class="rename"><a onclick="return manageFile(this, \'folder\', \'rename\', \'' + file_path + '\', onSuccessfullRenameFile)">Rename Folder</a></li>'
							+ '<li class="line_break"></li>'
								+ '<li class="remove"><a onclick="return manageFile(this, \'folder\', \'remove\', \'' + file_path + '\', ' + (is_template_folder ? 'onSuccessfullRemoveTemplateFolder' : 'onSuccessfullRemoveFile') + ')">Delete Folder</a></li>'
							+ '</ul>'
						+ '</div>';
				
				a.children("label").after(html);
				a.addcontextmenu( a.find(".mycontextmenu").clone() );
				
				//ul.prepend(li);
			};
			
			//ul.prepend('<li class="separator">Folders:</li>');
		}
		
		//prepare page files
		var entities = ul.find("i.entity_file");
		//entities.first().parent().closest("li").before('<li class="separator">Pages:</li>');
		entities.each(function(idx, elm) {
			elm = $(elm);
			var a = elm.parent();
			var label = a.children("label");
			var li = a.parent();
			var file_path = a.attr("file_path");
			var view_url = view_entity_url.replace(/#path#/g, file_path);
			var edit_url = edit_entity_url.replace(/#path#/g, file_path);
			var properties = properties_by_file_path[file_path];
			var modified_date = properties && properties["properties"] && properties["properties"]["item_menu"] ? properties["properties"]["item_menu"]["modified_date"] : "";
			
			var html = '<div class="modified_date">Last updated ' + modified_date + '</div>'
					+ '<div class="sub_menu" onClick="openProjectFileSubmenu(this)">'
						+ '<i class="icon sub_menu_vertical"></i>'
						+ '<ul class="mycontextmenu with_top_right_triangle">'
							+ '<li class="edit"><a href="' + edit_url + '">Edit Page</a></li>'
							+ '<li class="rename"><a onclick="return manageFile(this, \'file\', \'rename\', \'' + file_path + '\', onSuccessfullRenameFile)">Rename Page</a></li>'
							+ '<li class="line_break"></li>'
							+ '<li class="view_project"><a href="' + view_url + '" target="project">Preview Page</a></li>'
							+ '<li class="line_break"></li>'
							+ '<li class="remove"><a onclick="return manageFile(this, \'file\', \'remove\', \'' + file_path + '\', onSuccessfullRemoveFile)">Delete Page</a></li>'
						+ '</ul>'
					+ '</div>';
			
			label.before('<div class="first_letter">' + label.text().substr(0, 1).toUpperCase() + '</div>');
			label.after(html);
			a.addcontextmenu( a.find(".mycontextmenu").clone() );
		});
		
		//prepare template files
		var default_templates_lis = {};
		
		var templates = ul.find("i.template_file");
		var installed_templates_separator = $('<li class="separator">Installed Templates:</li>');
		templates.first().parent().closest("li").before(installed_templates_separator);
		templates.each(function(idx, elm) {
			elm = $(elm);
			var a = elm.parent();
			var li = a.parent();
			var file_path = a.attr("file_path");
			var edit_url = edit_template_url.replace(/#path#/g, file_path);
			var properties = properties_by_file_path[file_path];
			var modified_date = properties && properties["properties"] && properties["properties"]["item_menu"] ? properties["properties"]["item_menu"]["modified_date"] : "";
			
			var html = '<div class="modified_date">Last updated ' + modified_date + '</div>'
					+ '<div class="sub_menu" onClick="openProjectFileSubmenu(this)">'
						+ '<i class="icon sub_menu_vertical"></i>'
						+ '<ul class="mycontextmenu with_top_right_triangle">'
							+ '<li class="edit"><a href="' + edit_url + '">Edit Template</a></li>'
							+ '<li class="rename"><a onclick="return manageFile(this, \'file\', \'rename\', \'' + file_path + '\', onSuccessfullRenameFile)">Rename Template</a></li>'
							+ '<li class="line_break"></li>'
							+ '<li class="set_default"><a onclick="return setTemplateAsDefault(this, \'' + file_path + '\')">Set Template as Default</a></li>'
							+ '<li class="line_break"></li>'
							+ '<li class="remove"><a onclick="return manageFile(this, \'file\', \'remove\', \'' + file_path + '\', onSuccessfullRemoveFile)">Delete Template</a></li>'
						+ '</ul>'
					+ '</div>';
			
			a.children("label").after(html);
			a.addcontextmenu( a.find(".mycontextmenu").clone() );
			
			var pos = file_path.indexOf("/src/template/");
			
			if (pos != -1) {
				pos += "/src/template/".length
				var template_id = file_path.substr(pos, file_path.length - pos - 4);
				
				//check if template has a default image in available_templates_props
				if (available_templates_props && !$.isEmptyObject(available_templates_props)) {
					var template_props = available_templates_props[template_id];
					var logo = template_props ? template_props["logo"] : null;
					
					if (logo) {
						var img = $("<img />");
						img.attr("src", logo);
						img.on("error", function() {
							$(this).remove();
						});
						
						a.children("i").append(img);
					}
				}
				
				if ($.inArray(template_id, default_available_templates) != -1) {
					li.addClass("template_" + template_id);
					
					default_templates_lis[template_id] = li[0];
				}
							
				if (template_id == project_default_template) {
					ul.find("li.default_template").removeClass("default_template");
					li.addClass("default_template");
				}
			}
		}).promise().done(function() {
			if (!$.isEmptyObject(default_templates_lis)) {
				for (var i = default_available_templates.length; i >= 0; i--) {
					var template_id = default_available_templates[i];
					
					if (default_templates_lis.hasOwnProperty(template_id))
						ul.prepend( default_templates_lis[template_id] );
				}
				
				ul.children(".separator").first().prev("li").addClass("last_from_group");
				
				ul.prepend('<li class="separator">Default Templates:</li>');
				
				if (installed_templates_separator.next("li").length == 0)
					installed_templates_separator.after('<li class="empty_files">No files availabes...</li>');
			}
		});
		
		//prepare searched files
		sortFiles( main_parent.find(" > .sort_files")[0] );
	}
}

function openProjectFileSubmenu(elm) {
	window.event.stopPropagation(); //prevent the event to fire in the parent "a" html element.
	
	openSubmenu(elm);
}

function previewProject(elm) {
	elm = $(elm);
	var href = elm.attr("href");
	var tab = elm.attr("target");
	
	var win = window.open(href, tab);
	
	if(win) //Browser has allowed it to be opened
		win.focus();
}

function selectMyTreeNode(node) {
	node = $(node);
	var a = node.children("a");
	var i = a.children("i");
	
	if (i.is(".folder, .template_folder")) {
		var path = a.attr("folder_path");
		
		if (path)
			refreshTreeWithNewPath(node, path);
	}
	else {
		var url = null;
		
		if (i.is(".entity_file")) {
			var path = a.attr("file_path");
			url = edit_entity_url.replace(/#path#/g, path);
		}
		else if (i.is(".template_file")) {
			var path = a.attr("file_path");
			url = edit_template_url.replace(/#path#/g, path);
		}
		
		if (url) {
			var is_new_win = window.event && (window.event.ctrlKey || window.event.keyCode == 65);
			var win = null;
			
			if (is_new_win) {
				var win_tab_name = "tab" + (Math.random() * 10000);
				win = window.open(url, win_tab_name) ;
			}
			
			if(win) //Browser has allowed it to be opened
				win.focus();
			else {
				MyFancyPopup.showOverlay();
				MyFancyPopup.showLoading();
				
				document.location = url;
			}
		}
	}
	
	return false;
}

function refreshTreeWithNewPath(elm, path, mytree_parent_class) {
	var mytree = mytree_parent_class ? $(".admin_panel .project_files > ." + mytree_parent_class + " > .mytree") : $(elm).parent().closest(".mytree");
	var mytree_parent = mytree.parent();
	var mytree_main_li = mytree.children("li");
	var mytree_main_ul = mytree_main_li.children("ul");
	
	path = ("" + path).replace(/\/+/g, "/").replace(/^\/+/g, "").replace(/\/+$/g, ""); //remove duplicates, start and end slash
	
	updatePathBreadcrumbs(mytree, path);
	
	//replace new path in path url in ul
	var url = mytree_main_ul.attr("url");
	var p = path ? path + "/" : path;
	url = url.replace(/&path=[^&]*/, "&path=" + p);
	mytree_main_ul.attr("url", url);
	mytree_parent.attr("current_path", p);
	
	//reset files searched
	mytree_parent.find(" > .search_file > input").val("");
	
	//refresh ul childs
	refreshAndShowNodeChilds(mytree_main_li);
}

function updatePathBreadcrumbs(mytree, path) {
	var mytree_parent = mytree.parent();
	var is_pages = mytree_parent.is(".pages");
	
	//remove old current_project_folder
	var breadcrumbs = $(".top_bar .breadcrumbs");
	breadcrumbs.find(".breadcrumb-item:not(.fixed)").remove();
	
	var home_label = is_pages ? "pages" : "templates";
	var mytree_parent_class = is_pages ? "pages" : "templates";
	var root_path = mytree_parent.attr("root_path");
	var breadcrumps_html = '<span class="breadcrumb-item"><a href="javascript:void(0)" onClick="refreshTreeWithNewPath(this, \'' + root_path + '\', \'' + mytree_parent_class + '\')">' + home_label + '</a></span>';
	
	if (path) { //path could be undefined
		path = ("" + path).replace(/\/+/g, "/").replace(/^\/+/g, "").replace(/\/+$/g, ""); //remove duplicates, start and end slash
		root_path = ("" + root_path).replace(/\/+/g, "/").replace(/^\/+/g, "").replace(/\/+$/g, ""); //remove duplicates, start and end slash
		
		if (path != root_path) {
			var pos = path.lastIndexOf("/");
			var parent_path = pos != -1 ? path.substr(0, pos) + "/" : "";
			var current_path = path.substr(root_path.length + 1);
			var str = is_pages ? "/src/entity/" : "/src/template/";
			var pos = path.indexOf(str);
			var prefix_path = path.substr(0, pos + str.length);
			
			//add new current_project_folder
			breadcrumps_html = '<span class="breadcrumb-item"><a href="javascript:void(0)" onClick="refreshTreeWithNewPath(this, \'' + prefix_path + '\', \'' + mytree_parent_class + '\')">' + home_label + '</a></span>' + getProjectCurrentFolderHtml(current_path, prefix_path, mytree_parent_class);
		}
	}
	
	breadcrumbs.append(breadcrumps_html);
}

function getProjectCurrentFolderHtml(current_path, prefix_path, mytree_parent_class) {
	current_path = current_path.replace(/^\/+/g, "").replace(/\/+$/g, "");
	var dirs = current_path.split("/");
	var html = '';
	var parent_folder = "";
	
	for (var i = 0; i < dirs.length; i++) {
		var dir = dirs[i];
		
		if (dir) {
			parent_folder += (parent_folder ? "/" : "") + dir;
			
			html += '<span class="breadcrumb-item"><a href="javascript:void(0)" onClick="refreshTreeWithNewPath(this, \'' + prefix_path + parent_folder + '\', \'' + (mytree_parent_class ? mytree_parent_class : mytree_parent_class) + '\');">' + dir + '</a></span>';
		}
	}
	
	return html;
}

function onClickPagesTab() {
	mytree = pagesFromFileManagerTree;
	
	var p = $(".admin_panel .project_files > .pages");
	updatePathBreadcrumbs(p.children(".mytree"), p.attr("current_path"));
}

function onClickTemplatesTab() {
	mytree = templatesFromFileManagerTree;
	
	var p = $(".admin_panel .project_files > .templates");
	updatePathBreadcrumbs(p.children(".mytree"), p.attr("current_path"));
}

function editProject() {
	//get popup
	var popup = $("body > .edit_project_details_popup");
	
	if (!popup[0]) {
		popup = $('<div class="myfancypopup with_iframe_title edit_project_details_popup"></div>');
		$(document.body).append(popup);
	}
	
	popup.html('<iframe></iframe>'); //cleans the iframe so we don't see the previous html
	
	//prepare popup iframe
	var iframe = popup.children("iframe");
	iframe.attr("src", edit_project_url);
	
	//open popup
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
	});
	
	MyFancyPopup.showPopup();
}

function onSuccessfullEditProject(opts) {
	var url = "" + document.location;
	url = url.indexOf("#") != -1 ? url.substr(0, url.indexOf("#")) : url; //remove # so it can refresh page
	url = url.replace(/[&]+/g, "&");
	
	if (opts && opts["is_rename_project"]) {
		url = url.replace(/(&|\?)filter_by_layout\s*=\s*([^&#]*)/, "");
		url += (url.indexOf("?") != -1 ? "&" : "?") + "filter_by_layout=" + opts["new_filter_by_layout"];
		
		if (window.parent && window.parent != window && typeof window.parent.onSuccessfullEditProject == "function") { //if admin_advanced or other admin main page
			window.parent.onSuccessfullEditProject(opts);
			return; //avoids executing the code below.
		}
	}
	
	document.location = url;
}

function toggleProjectDetails(elm) {
	elm = $(elm);
	var project = $(".project");
	project.toggleClass("with_details");
	
	if (project.hasClass("with_details"))
		elm.html("Hide Project Details");
	else
		elm.html("Show Project Details");
}

function toggleProjectsListType(elm, type) {
	elm = $(elm);
	var p = elm.parent();
	
	p.find(".icon").removeClass("active");
	elm.find(".icon").addClass("active");
	
	p.closest(".pages, .templates").children(".mytree").removeClass(type == "block_view" ? "list_view" : "block_view").addClass(type == "block_view" ? "block_view" : "list_view");
}

function searchFiles(elm) {
	elm = $(elm);
	var to_search = elm.val().toLowerCase().replace(/^\s*/, "").replace(/\s*$/, "");
	var mytree = elm.parent().parent().children(".mytree");
	
	if (to_search == "")
		mytree.find("li").removeClass("hidden");
	else {
		var items = mytree.find("i.folder, i.entity_file, i.template_file, i.template_folder");
		
		items.each(function(idx, i) {
			var a = $(i).parent();
			var li = a.parent();
			var file_name = a.children("label").text();
			var matched = to_search != "" ? file_name.toLowerCase().indexOf(to_search) != -1 : true;
			
			if (matched)
				li.removeClass("hidden");
			else
				li.addClass("hidden");
		});
	}
}

function resetSearchFiles(elm) {
	var input = $(elm).parent().children("input");
	input.val("");
	searchFiles(input[0]);
}

function sortFiles(elm) {
	elm = $(elm);
	var sort_type = elm.val();
	var mytree = elm.parent().children(".mytree");
	var ul = mytree.find(" > li > ul");
	var lis = ul.children("li");
	var selector = sort_type == "first_updated" || sort_type == "last_updated" ? " > a > .modified_date" : " > a > label";
	
	lis.sort(function(li_a, li_b) {
		var a = $(li_a).find(selector).text();
		var b = $(li_b).find(selector).text();
		
		if (sort_type == "a_z" || sort_type == "first_updated") {
			if(a > b)
				return 1;
			if(a < b)
				return -1;
		}
		else {
			if(a > b)
				return -1;
			if(a < b)
				return 1;
		}
		
		return 0;
	});
	lis.detach().appendTo(ul);
}

function showCreateFilePopupThroughMessage() {
	StatusMessageHandler.removeLastShownMessage("info");
	
	showCreateFilePopup();
}

function showCreateFilePopup() {
	//get popup
	var popup = $(".admin_panel > .create_file_popup");
	
	popup.find("button").off().on("click", function() {
		var pages = $(".admin_panel .project_files > .pages");
		var elm = pages.children("button");
		
		createFile(elm[0], popup);
	});
	
	popup.find("input").off().on("keypress", function(event) {
		if (event.which == 13) //if enter key pressed
			popup.find("button").trigger("click");
	});
	
	//open popup
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
	});
	
	MyFancyPopup.showPopup();
}

function createFile(elm, popup) {
	var type = popup.find("select").val();
	var file_name = popup.find("input").val();
	var normalize = popup.find(".auto_normalize input").is(":checked");
	var action = type == "page" ? "create_file" : "create_folder";
	var handler = function(elm, type, action, path, new_file_name) {
		//hide popup first because the onSuccessfullCreateFile will open it again
		MyFancyPopup.hidePopup();
		
		if (type == "page")
			onSuccessfullCreateFile(elm, type, action, path, new_file_name);
		else
			onSuccessfullCreateFolder(elm, type, action, path, new_file_name);
	};
	
	if (normalize && file_name)
		file_name = normalizeFileName(file_name, false, true);
	
	//get current opened folder
	var mytree_parent = $(elm).parent();
	var current_path = mytree_parent.attr("current_path");
	
	manageFile(elm, type, action, current_path, handler, file_name);
}

function manageFile(elm, type, action, path, handler, new_file_name) {
	if (path)
		path = path.replace(/\/+/g, "/").replace(/^\/+/g, "").replace(/\/+$/g, ""); //remove duplicates, start and end slash
	
	if (type && action && path) {
		var status = true;
		var action_label = action;
		
		if (type == "project" && action == "remove") 
			status = confirm("Do you wish to delete this project?") && confirm("If you delete this project, you will loose all the created pages and other files inside of this project!\nDo you wish to continue?") && confirm("LAST WARNING:\nIf you proceed, you cannot undo this deletion!\nAre you sure you wish to delete this project?");
		else if (action == "remove") 
			status = confirm("Do you wish to delete this file?")
		else if (action == "rename") {
			var pos = path.lastIndexOf(".");
			var dir_pos = path.lastIndexOf("/");
			
			pos = pos != -1 ? pos : path.length;
			dir_pos = dir_pos != -1 ? dir_pos + 1 : 0;
			
			var dir_name = path.substr(0, dir_pos);
			var base_name = type == "file" ? path.substr(dir_pos, pos - dir_pos) : path;
			var extension = type == "file" && pos + 1 < path.length ? path.substr(pos + 1) : "";
			
			if (!new_file_name)
				status = (new_file_name = prompt("Please write the new name:", base_name));
			
			new_file_name = ("" + new_file_name).replace(/^\s+/g, "").replace(/\s+$/g, ""); //trim name
			
			if (status && new_file_name && extension)
				new_file_name += "." + extension;
			else if (!new_file_name)
				status = false;
		}
		else if (action == "create_folder" || action == "create_file") {
			action_label = "create";
			
			if (!new_file_name)
				status = (new_file_name = prompt("Please write the " + type + " name:"));
			
			new_file_name = ("" + new_file_name).replace(/^\s+/g, "").replace(/\s+$/g, ""); //trim name
			
			if (!new_file_name)
				status = false;
		}
		
		if (status) {
			var is_file_new_name_action = action == "rename" || action == "create_folder" || action == "create_file";
			
			if (is_file_new_name_action && new_file_name) {
				new_file_name = ("" + new_file_name).replace(/^\s+/g, "").replace(/\s+$/g, ""); //trim name
				
				//normalize new file name
				var allow_upper_case = elm.getAttribute("allow_upper_case") == 1; //in case of businesslogic services class
				new_file_name = normalizeFileName(new_file_name, allow_upper_case);
			}
			
			if (is_file_new_name_action && !new_file_name)
				alert("Error: File name cannot be empty");
			else {
				var url = manage_file_url.replace("#action#", action).replace("#path#", path).replace("#extra#", new_file_name);
				
				url = encodeUrlWeirdChars(url); //Note: Is very important to add the encodeUrlWeirdChars otherwise if a value has accents, won't work in IE.
				
				$.ajax({
					type : "get",
					url : url,
					success : function(data, textStatus, jqXHR) {
						if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
							showAjaxLoginPopup(jquery_native_xhr_object.responseURL, url, function() {
								StatusMessageHandler.removeLastShownMessage("error");
								manageFile(elm, type, action, path, handler, new_file_name);
							});
						else if (data == "1") {
							StatusMessageHandler.showMessage(type + " " + action_label + "d successfully!", "", "bottom_messages", 1500);
							
							if (typeof handler == "function")
								handler(elm, type, action, path, new_file_name, url);
						}
						else
							StatusMessageHandler.showError("There was a problem trying to " + action_label + " " + type + ". Please try again..." + (data ? "\n" + data : ""));
					},
					error : function(jqXHR, textStatus, errorThrown) { 
						var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
						StatusMessageHandler.showError((errorThrown ? errorThrown + " error.\n" : "") + "Error trying to " + action_label + " " + type + ".\nPlease try again..." + msg);
					},
				});
			}
		}
	}
}

function setTemplateAsDefault(elm, path) {
	var node = $(elm).parent().closest(".sub_menu").parent().closest("li");
	var file_path = node.children("a").attr("file_path");
	var pos = file_path.indexOf("/src/template/");
	
	if (pos != -1) {
		pos += "/src/template/".length
		var template_id = file_path.substr(pos, file_path.length - pos - 4);
		
		if (template_id != project_default_template) {
			StatusMessageHandler.showMessage("Setting default template... Loading...");
			
			var obj = {
				project_default_template: template_id,
			};
			var opts = {
				success: function(data, textStatus, jqXHR) {
					StatusMessageHandler.removeLastShownMessage("info");
					
					var mytree = node.parent().closest(".mytree");
					var main_div = mytree.parent();
					
					mytree.find("li.default_template").removeClass("default_template");
					node.addClass("default_template");
					main_div.find(" > .project_default_template > .breadcrumbs").html(template_id);
					
					project_default_template = template_id;
					
					StatusMessageHandler.showMessage("Default template set successfully");
				},
				error: function(jqXHR, textStatus, data) {
					StatusMessageHandler.showError("There was a problem trying to set this template as default. Please try again...") + (data ? "\n" + data : "");
				},
			};
			
			saveObj(save_project_default_template_url, obj, opts);
		}
		else
			StatusMessageHandler.showMessage("This template is already the default template!", "", "bottom_messages", 1500);
	}
	else
		StatusMessageHandler.showError("This template cannot be set as default!");
}

function refreshNodeParentChildsOnSuccessfullAction(elm) {
	var node_id = $(elm).parent().closest(".sub_menu").parent().closest("li").attr("id");
	refreshNodeParentChildsByChildId(node_id);
}

function onSuccessfullRemoveProject(elm, type, action, path, new_file_name, url) {
	//refresh main window navigator files tree
	refreshParentNavigatorFilesTree(path);
	
	if (window.parent && window.parent != window) {
		var parent_url = window.parent.location;
		parent_url = "" + parent_url; //convert to string
		parent_url = parent_url.indexOf("#") != -1 ? parent_url.substr(0, parent_url.indexOf("#")) : parent_url; //remove # so it can refresh parent page
		parent_url = parent_url.replace(/(bean_name|bean_file_name|project|filter_by_layout)=([^&]*)&?/g, ""); //erase previous bean_name|bean_file_name|project|filter_by_layout attributes
		
		//set cookie with default page
		window.parent.MyJSLib.CookieHandler.setCurrentDomainEternalRootSafeCookie('default_page', ''); //save cookie with url, so when we refresh the browser, the right panel contains the latest opened url
		
		//refresh main window with new params
		window.parent.location = parent_url;
	}
	else //show project list page
		document.location = admin_home_page_url;
}
function onSuccessfullRemoveFile(elm, type, action, path, new_file_name, url) {
	refreshNodeParentChildsOnSuccessfullAction(elm);
	
	//refresh main window navigator files tree
	var folder_path = path.substr(0, path.lastIndexOf("/"));
	refreshParentNavigatorFilesTree(folder_path);
}
function onSuccessfullRemoveTemplateFolder(elm, type, action, path, new_file_name, url) {
	onSuccessfullRemoveFile(elm, type, action, path, new_file_name, url);
	
	var url = manage_file_url.replace("#action#", action).replace("#path#", path).replace("#extra#", "");
	url = url.replace("/src/template/", "/webroot/template/"); //does not need encodeUrlWeirdChars bc the url is already encoded
	
	$.ajax({
		url : url,
		success : function(data) {
			if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
				showAjaxLoginPopup(jquery_native_xhr_object.responseURL, template_url, function() {
					StatusMessageHandler.removeLastShownMessage("error");
					onSuccessfullRemoveTemplateFolder(elm, type, action, path, new_file_name, url);
				});
			else if (data == "1") 
				StatusMessageHandler.showMessage("Template webroot deleted successfully", "", "bottom_messages", 1500);
			else
				StatusMessageHandler.showError("There was a problem trying to delete the correspondent template webroot folder. Please try again...") + (data ? "\n" + data : "");
		},
		error : function(jqXHR, textStatus, errorThrown) {
			var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
			StatusMessageHandler.showError((errorThrown ? errorThrown + " error.\n" : "") + "Error trying to delete template webroot folder.\nPlease try again..." + msg);
		},
	});
}
function onSuccessfullRenameProject(elm, type, action, path, new_file_name, url) {
	if (new_file_name) {
		var file_path = getParameterByName(url, "path");
		file_path = file_path ? file_path.replace(/\/$/g, "") : ""; //remove last /
		
		var file_name = file_path;
		var folder_path = "";
		
		if (file_path.lastIndexOf("/") != -1) {
			file_name = file_path.substr(file_path.lastIndexOf("/") + 1);
			folder_path = file_path.substr(0, file_path.lastIndexOf("/") + 1);
		}
		
		var new_file_path = folder_path + new_file_name;
		new_file_path = new_file_path.replace(/\/+/g, "/"); //remove duplicates of /
		
		if (new_file_path != file_path) {
			var doc_url = "" + (window.parent && window.parent != window ? window.parent.location : document.location);
			doc_url = doc_url.indexOf("#") != -1 ? doc_url.substr(0, doc_url.indexOf("#")) : doc_url; //remove # so it can refresh page
			
			if (doc_url.match(/(&|\?)filter_by_layout\s*=([^&#]+)/)) { //check if doc_url has any filter_by_layout
				var m = doc_url.match(/(&|\?)filter_by_layout\s*=\s*([^&#]*)/);
				var old_filter_by_layout = m[2];
				old_filter_by_layout = old_filter_by_layout.replace(/\/+/g, "/").replace(/\/$/g, ""); //remove duplicates and last /
				var new_filter_by_layout = old_filter_by_layout.substr(0, old_filter_by_layout.length - file_path.length) + new_file_path;
				
				doc_url = doc_url.replace(/(&|\?)filter_by_layout\s*=\s*([^&#]*)/, "");
				doc_url += (doc_url.indexOf("?") != -1 ? "&" : "?") + "filter_by_layout=" + new_filter_by_layout;
			}
			
			if (window.parent && window.parent != window) {
				if (doc_url.match(/(&|\?)project\s*=([^&#]+)/)) { //check if doc_url has any filter_by_layout
					var m = doc_url.match(/(&|\?)project\s*=\s*([^&#]*)/);
					var old_project = m[2];
					old_project = old_project.replace(/\/+/g, "/").replace(/\/$/g, ""); //remove duplicates and last /
					
					doc_url = doc_url.replace(/(&|\?)project\s*=\s*([^&#]*)/, "");
					doc_url += (doc_url.indexOf("?") != -1 ? "&" : "?") + "project=" + new_file_path;
				}
				
				//set cookie with default page
				window.parent.MyJSLib.CookieHandler.setCurrentDomainEternalRootSafeCookie('default_page', ''); //save cookie with url, so when we refresh the browser, the right panel contains the latest opened url
				
				//refresh main window with new params
				window.parent.location = doc_url;
			}
			else {
				//get default_page url and check if contains filter_by_layout in the url and if so, replace it with new project name
				var default_page = MyJSLib.CookieHandler.getCookie('default_page');
				
				if (default_page) {
					if (default_page.match(/(&|\?)filter_by_layout\s*=([^&#]+)/)) { //check if default_page url has any filter_by_layout
						var m = default_page.match(/(&|\?)filter_by_layout\s*=\s*([^&#]*)/);
						var old_filter_by_layout = m[2];
						old_filter_by_layout = old_filter_by_layout.replace(/\/+/g, "/").replace(/\/$/g, ""); //remove duplicates and last /
						var new_filter_by_layout = old_filter_by_layout.substr(0, old_filter_by_layout.length - file_path.length) + new_file_path;
						
						default_page = default_page.replace(/(&|\?)filter_by_layout\s*=\s*([^&#]*)/, "");
						default_page += (default_page.indexOf("?") != -1 ? "&" : "?") + "filter_by_layout=" + new_filter_by_layout;
					}
					
					//set cookie with default page
					MyJSLib.CookieHandler.setCurrentDomainEternalRootSafeCookie('default_page', default_page); //save cookie with url, so when we refresh the browser, the right panel contains the latest opened url
				}
				
				document.location = doc_url;
			}
		}
	}
}
function onSuccessfullRenameFile(elm, type, action, path, new_file_name, url) {
	refreshNodeParentChildsOnSuccessfullAction(elm);
	
	//refresh main window navigator files tree
	var folder_path = path.substr(0, path.lastIndexOf("/"));
	refreshParentNavigatorFilesTree(folder_path);
}
function onSuccessfullCreateFile(elm, type, action, path, new_file_name, url) {
	var node = $(elm).parent().find(" > .mytree > li");
	refreshAndShowNodeChilds(node);
	
	//refresh main window navigator files tree
	refreshParentNavigatorFilesTree(path);
	
	//open file to edit
	path += "/" + new_file_name + ".php";
	var add_url = add_entity_url.replace(/#path#/g, path) + "&popup=1";
	var edit_url = edit_entity_url.replace(/#path#/g, path);
	
	//open popup
	var popup = $(".add_entity_popup");
	
	if (!popup[0]) {
		popup = $('<div class="myfancypopup with_iframe_title add_entity_popup big"></div>');
		$(document.body).append(popup);
	}
	
	popup.html('<iframe src="' + add_url + '"></iframe>');
	
	MyFancyPopup.init({
		elementToShow: popup,
		//parentElement: document,
		
		onClose: function() { //just in case the user finds a way to close the popup.
			StatusMessageHandler.showMessage("Wait a while... Page editor is loading");
			
			setTimeout(function() {
				document.location = edit_url;
			}, 300);
		}
	});
	MyFancyPopup.showPopup();
}
function onSuccessfullCreateFolder(elm, type, action, path, new_file_name, url) {
	var node = $(elm).parent().find(" > .mytree > li");
	refreshAndShowNodeChilds(node);
	
	//refresh main window navigator files tree
	refreshParentNavigatorFilesTree(path);
}

function refreshParentNavigatorFilesTree(path) {
	if (path && window.parent && window.parent != window && typeof window.parent.refreshOpenNodeChildsBasedInPath == "function")
		window.parent.refreshOpenNodeChildsBasedInPath(path);
}

function importTemplates() {
	var url = install_template_url;
	
	//get popup
	var popup = $("body > .import_templates_popup");
	
	if (!popup[0]) {
		popup = $('<div class="myfancypopup with_iframe_title import_templates_popup"></div>');
		$(document.body).append(popup);
	}
	
	popup.html('<iframe></iframe>'); //cleans the iframe so we don't see the previous html
	
	//prepare popup iframe
	var iframe = popup.children("iframe");
	iframe.attr("src", url);
	
	//open popup
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
	});
	
	MyFancyPopup.showPopup();
}
function onSuccessfullInstallTemplate() {
	StatusMessageHandler.showMessage("Refreshing templates...");
	
	var url = "" + document.location;
	url = url.replace(/&active_tab=[^&]*/g, "");
	url += "&active_tab=1";
	
	document.location = url;
}
