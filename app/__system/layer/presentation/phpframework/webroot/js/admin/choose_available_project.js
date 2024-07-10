function toggleProjectsListType(elm, type) {
	elm = $(elm);
	var p = elm.parent();
	
	p.find(".icon").removeClass("active");
	elm.find(".icon").addClass("active");
	
	p.closest(".choose_available_project").children(".projects").removeClass(type == "block_view" ? "list_view" : "block_view").addClass(type == "block_view" ? "block_view" : "list_view");
}

function updateLayerProjects(folder_to_filter) {
	var option = $(".choose_available_project > .layer select option:selected");
	var bean_name = option.attr("bean_name");
	var layer_projects = layers_props && bean_name && layers_props[bean_name] ? layers_props[bean_name]["projects"] : null;
	
	prepareChooseAvailableProjectsHtml(layer_projects, folder_to_filter);
}

function prepareChooseAvailableProjectsHtml(layer_projects, folder_to_filter) {
	var aps = getAvailableProjectsConvertedWithFolders(layer_projects, folder_to_filter);
	var choose_available_project = $(".choose_available_project");
	
	if (folder_to_filter) {
		folder_to_filter = folder_to_filter.replace(/[\/]+/, "/").replace(/[\/]+$/, "");
		var dirs = folder_to_filter.split("/");
		dirs.pop();
		var parent_folder = dirs.join("/");
	}
	
	/*var folders_html = '';
	
	if (!$.isEmptyObject(aps)) {
		//add folders
		for (var k in aps) 
			if (aps[k]) { 
				var is_project = $.isPlainObject(aps[k]) && aps[k]["is_project"] === true;
				
				if (!is_project) {
					folders_html += getChooseAvailableProjectHtml(folder_to_filter, k, aps[k]);
					
					aps[k] = null;
				}
			}
	}
	
	if (folders_html == '')
		folders_html += '<li class="no_projects">There are no folders' + (folder_to_filter ? ' inside of "' + folder_to_filter + '".' : '.') + '</li>';*/
	
	var projects_html = '';
	
	if (!$.isEmptyObject(aps)) {
		choose_available_project.children(".new_project").removeClass("hidden");
		choose_available_project.children(".new_first_project").addClass("hidden");
		
		//add files
		for (var k in aps)
			if (aps[k] && (folder_to_filter || k != "common"))
				projects_html += getChooseAvailableProjectHtml(folder_to_filter, k, aps[k]);
	}
	
	if (!projects_html) {
		choose_available_project.children(".new_project").addClass("hidden");
		choose_available_project.children(".new_first_project").removeClass("hidden");
		
		projects_html += '<li class="no_projects">There are no projects created.<br/>Soon this space will be filled with your projects once you create your first one.</li>';
		
		//open popup to create new project, if root folder
		if (!folder_to_filter)
			addProject();
	}
	
	var group_projects = choose_available_project.children(".group.projects");
	var ul = group_projects.children("ul");
	
	ul.html(/*folders_html + */projects_html);
	
	if (folder_to_filter)
		choose_available_project.addClass("in_sub_folder");
	else
		choose_available_project.removeClass("in_sub_folder");
	
	//add contextmenu
	ul.children("li.folder").each(function(idx, li) {
		li = $(li);
		li.addcontextmenu( li.find(".mycontextmenu").clone() );
	});
	
	choose_available_project.find(".top_bar .breadcrumbs").attr("folder_to_filter", folder_to_filter).html(folder_to_filter ? '<span class="breadcrumb-item"><a href="javascript:void(0)" onClick="updateLayerProjects(\'\')">Home</a></span>' + getChooseAvailableProjectCurrentFolderHtml(folder_to_filter) : "");
	
	choose_available_project.children(".loading_projects").hide();
	
	if (!$.isEmptyObject(aps)) {
		var projects_actions = choose_available_project.children(".projects_actions");
		searchProjects( projects_actions.find(" > .search_project > input")[0] );
		sortProjects( projects_actions.children(".sort_projects")[0] );
	}
}

function getChooseAvailableProjectHtml(folder_to_filter, fp, project_props) {
	var html = "";
	
	var is_project = $.isPlainObject(project_props) && project_props["is_project"] === true;
	var project_id = (folder_to_filter ? folder_to_filter + "/" : "") + fp;
	var project_logo_url = $.isPlainObject(project_props) && project_props["logo_url"] ? project_props["logo_url"] : null;
	var label = fp.replace("/_/g", " ");
	label = label.charAt(0).toUpperCase() + label.substr(1, label.length - 1);
	
	if (is_project)
		html += '<li class="project ' + (!folder_to_filter && project_id == "common" ? "project_common" : "") + (typeof selected_project_id != "undefined" && project_id == selected_project_id ? " selected_project" : "") + '" onClick="selectAvailableProject(\'' + project_id + '\', event);" title="' + label + '">';
	else
		html += '<li class="folder" onClick="updateLayerProjects(\'' + project_id + '\');" title="' + label + '">';
	
	if (project_logo_url)
		html += '	<div class="image">' + (project_logo_url ? '<img src="' + project_logo_url + '" onError="$(this).parent().removeClass("image").addClass("photo");$(this).remove();" />' : '') + '</div>';
	else
		html += '	<div class="photo"></div>';
	
	html += '	<label>' + label + '</label>';
	
	if (!is_project)
		html += '<div class="sub_menu" onClick="openProjectFileSubmenu(this)">'
				+ '<i class="icon sub_menu_vertical"></i>'
				+ '<ul class="mycontextmenu with_top_center_triangle">'
					+ '<li class="sub_menu_item rename"><a onclick="return manageFile(this, \'project_folder\', \'rename\', \'' + project_id + '\', onSuccessfullRenameFile)">Rename Folder</a></li>'
					+ '<li class="sub_menu_item line_break"></li>'
					+ '<li class="sub_menu_item remove"><a onclick="return manageFile(this, \'project_folder\', \'remove\', \'' + project_id + '\', onSuccessfullRemoveFile)">Delete Folder</a></li>'
				+ '</ul>'
			+ '</div>';
	
	/*if (!is_project) //if is folder
		html += '<a href="javascript:void(0)">View Projects in this folder</a>';*/
	
	html += '</li>';
	
	return html;
}

function getChooseAvailableProjectCurrentFolderHtml(current_path) {
	current_path = current_path.replace(/^\/+/g, "").replace(/\/+$/g, "");
	var dirs = current_path.split("/");
	var html = '';
	var parent_folder = "";
	
	for (var i = 0; i < dirs.length; i++) {
		var dir = dirs[i];
		
		if (dir) {
			parent_folder += (parent_folder ? "/" : "") + dir;
			
			html += '<span class="breadcrumb-item"><a href="javascript:void(0)" onClick="updateLayerProjects(\'' + parent_folder + '\');">' + dir + '</a></span>';
		}
	}
	
	return html;
}

function selectAvailableProject(project_id, originalEvent) {
	var option = $(".choose_available_project > .layer select option:selected");
	var bean_name = option.attr("bean_name");
	var bean_file_name = option.attr("bean_file_name");
	var layer_bean_folder_name = option.attr("layer_bean_folder_name");
	var url = select_project_url.replace("#bean_name#", bean_name).replace("#bean_file_name#", bean_file_name).replace("#project#", project_id).replace("#filter_by_layout#", layer_bean_folder_name + "/" + project_id);
	
	//if ctrl key is pressed
	if (originalEvent && (originalEvent.ctrlKey || originalEvent.keyCode == 65)) {
		var win = window.open(url);
		
		if (win)
			win.focus();
	}
	else {
		var selected_admin_home_project_page_url = admin_home_project_page_url.replace("#filter_by_layout#", layer_bean_folder_name + "/" + project_id);
		MyJSLib.CookieHandler.setCurrentDomainEternalRootSafeCookie('default_page', selected_admin_home_project_page_url); //save cookie with url, so when we refresh the browser, the admin right panel contains the project home page
		
		if (is_popup) { //if is popup
			if (typeof window.parent.ProjectsFancyPopup != "undefined" && window.parent.ProjectsFancyPopup.settings && typeof window.parent.ProjectsFancyPopup.settings.goTo == "function")
				window.parent.ProjectsFancyPopup.settings.goTo(url, originalEvent);
			else
				window.parent.document.location = url;
		}
		else //if is current window
			document.location = url;
	}
}

function getAvailableProjectsConvertedWithFolders(layer_projects, folder_to_filter) {
	var aps = {};
	folder_to_filter = folder_to_filter ? folder_to_filter.replace(/[\/]+/, "/").replace(/[\/]+$/, "") + "/" : "";
	
	if (layer_projects)
		for (var fp in layer_projects) {
			var project_props = layer_projects[fp];
			
			fp = fp.replace(/[\/]+/, "/"); //remove duplicated "/"
			
			if (!folder_to_filter || fp.substr(0, folder_to_filter.length) == folder_to_filter) {
				var fp_aux = fp;
				
				if (folder_to_filter)
					fp_aux = fp_aux.substr(folder_to_filter.length);
				
				var dirs = fp_aux.split("/");
				var file_name = dirs.pop();
				var obj = aps;
				
				for (var j = 0; j < dirs.length; j++) {
					var dir = dirs[j];
					
					if (!obj.hasOwnProperty(dir))
						obj[dir] = {};
					
					obj = obj[dir];
				}
				
				if (project_props["item_type"] != "project_folder")
					project_props["is_project"] = true;
				
				obj[file_name] = project_props;
			}
		}
	
	return aps;
}

function addProject() {
	var choose_available_project = $(".choose_available_project");
	var option = choose_available_project.find(" > .layer select option:selected").first();
	var folder_to_filter = choose_available_project.find(".top_bar .breadcrumbs").attr("folder_to_filter");
	var bean_name = option.attr("bean_name");
	var bean_file_name = option.attr("bean_file_name");
	var path = folder_to_filter ? folder_to_filter + "/" : "";
	var url = add_project_url.replace("#bean_name#", bean_name).replace("#bean_file_name#", bean_file_name).replace("#path#", path);
	
	//get popup
	var popup = $("body > .edit_project_details_popup");
	
	if (!popup[0]) {
		popup = $('<div class="myfancypopup with_iframe_title edit_project_details_popup"></div>');
		$(document.body).append(popup);
	}
	
	if (show_programs_on_add_project)
		popup.addClass("big");
	else
		popup.removeClass("big");
	
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

function onSuccessfullAddProject(opts) {
	var filter_by_layout, bean_name, bean_file_name, project, is_ctrl_key_pressed;
	
	if (opts) {
		filter_by_layout = opts["new_filter_by_layout"];
		bean_name = opts["new_bean_name"];
		bean_file_name = opts["new_bean_file_name"];
		project = opts["new_project"];
		is_ctrl_key_pressed = opts["is_ctrl_key_pressed"];
	}
	
	if (is_ctrl_key_pressed && filter_by_layout) {
		url = admin_home_project_page_url.replace("#filter_by_layout#", filter_by_layout);
		
		var rand = Math.random() * 10000;
		var win = window.open(url, "tab" + rand);
		
		if (win) { //Browser has allowed it to be opened
			win.focus();
			
			MyFancyPopup.hidePopup();
			
			//refreshes parent window
			if (window.parent.parent != window.parent)
				window.parent.parent.document.location = "" + window.parent.parent.document.location;
			else if (window.parent != window)
				window.parent.document.location = "" + window.parent.document.location;
			else //refreshes window
				window.document.location = "" + window.document.location;
			
			return true; //don't execute code below.
		}
	}
	
	var choose_available_project= $(".choose_available_project");
	var folder_to_filter = choose_available_project.find(".top_bar .breadcrumbs").attr("folder_to_filter");
	var url = "" + document.location;
	url = url.indexOf("#") != -1 ? url.substr(0, url.indexOf("#")) : url; //remove # so it can refresh page
	
	if (folder_to_filter)
		url += "&folder_to_filter=" + folder_to_filter;
	
	if (window.parent && window.parent != window) {
		var parent_url = window.parent.location;
		
		if (filter_by_layout || (bean_name && bean_file_name && project)) {
			url = admin_home_project_page_url.replace("#filter_by_layout#", filter_by_layout);
			
			parent_url = "" + parent_url; //convert to string
			parent_url = parent_url.indexOf("#") != -1 ? parent_url.substr(0, parent_url.indexOf("#")) : parent_url; //remove # so it can refresh parent page
			parent_url = parent_url.replace(/(bean_name|bean_file_name|project|filter_by_layout)=([^&]*)&?/g, ""); //erase previous bean_name|bean_file_name|project|filter_by_layout attributes
			parent_url += parent_url.indexOf("?") != -1 ? "" : "?"; //add "?" if apply
			parent_url += "&bean_name=" + bean_name + "&bean_file_name=" + bean_file_name + "&project=" + project + "&filter_by_layout=" + filter_by_layout; //add new bean_name|bean_file_name|project|filter_by_layout
			parent_url = parent_url.replace(/\?&+/, "?"); //replace "?&&&" with "?"
		}
		
		//set cookie with default page
		window.parent.MyJSLib.CookieHandler.setCurrentDomainEternalRootSafeCookie('default_page', url); //save cookie with url, so when we refresh the browser, the right panel contains the latest opened url
		
		//refresh main window with new params
		window.parent.location = parent_url;
	}
	else {
		selectAvailableProject(project, window.event);
		
		/* Deprecated: if we are in the "choose project" from the simple workspace, we don't want to do this redirect, but intead, we want to call the selectAvailableProject. Basically the selectAvailableProject can be used for both simple and advanced workspace, bc it takes care of both cases.
		if (filter_by_layout)
			url = admin_home_project_page_url.replace("#filter_by_layout#", filter_by_layout);
		
		document.location = url;*/
	}
}

function openProjectFileSubmenu(elm) {
	window.event.stopPropagation(); //prevent the event to fire in the parent "a" html element.
	
	openSubmenu(elm);
}

function manageFile(elm, type, action, path, handler, new_file_name) {
	if (path)
		path = path.replace(/\/+/g, "/").replace(/^\/+/g, "").replace(/\/+$/g, ""); //remove duplicates, start and end slash
	
	if (type && action && path) {
		var status = true;
		var action_label = action;
		var file_type = type.replace(/_/g, " ");
		
		if (action == "remove")
			status = confirm("Do you wish to remove the " + file_type + ": '" + path + "'?") && confirm("If you delete this " + file_type + ", you will loose all the created pages and other files inside of this " + file_type + "!\nDo you wish to continue?") && confirm("LAST WARNING:\nIf you proceed, you cannot undo this deletion!\nAre you sure you wish to remove this " + file_type + "?");
		else if (action == "rename") {
			var pos = path.lastIndexOf(".");
			var dir_pos = path.lastIndexOf("/");
			
			pos = pos != -1 ? pos : path.length;
			dir_pos = dir_pos != -1 ? dir_pos + 1 : 0;
			
			var dir_name = path.substr(0, dir_pos);
			var base_name = path.substr(dir_pos, pos - dir_pos);
			var extension = pos + 1 < path.length ? path.substr(pos + 1) : "";
			
			if (!new_file_name)
				status = (new_file_name = prompt("Please write the new name:", base_name));
			
			new_file_name = ("" + new_file_name).replace(/^\s+/g, "").replace(/\s+$/g, ""); //trim name
			
			if (status && new_file_name && extension)
				new_file_name += "." + extension;
			else if (!new_file_name)
				status = false;
		}
		else if (action == "create_folder") {
			action_label = "create";
			
			if (!new_file_name)
				status = (new_file_name = prompt("Please write the folder name:"));
			
			new_file_name = ("" + new_file_name).replace(/^\s+/g, "").replace(/\s+$/g, ""); //trim name
			
			if (!new_file_name)
				status = false;
		}
		
		if (status) {
			var choose_available_project= $(".choose_available_project");
			var option = choose_available_project.find(" > .layer select option:selected").first();
			var bean_name = option.attr("bean_name");
			var bean_file_name = option.attr("bean_file_name");
			var url = manage_file_url.replace("#bean_name#", bean_name).replace("#bean_file_name#", bean_file_name).replace("#path#", path).replace("#action#", action).replace("#extra#", new_file_name);
			
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
						StatusMessageHandler.showMessage(file_type + " " + action_label + "d successfully!", "", "bottom_messages", 1500);
						
						if (typeof handler == "function")
							handler(elm, type, action, path, new_file_name);
					}
					else
						StatusMessageHandler.showError("There was a problem trying to " + action_label + " " + file_type + ". Please try again..." + (data ? "\n" + data : ""));
				},
				error : function(jqXHR, textStatus, errorThrown) { 
					var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
					StatusMessageHandler.showError((errorThrown ? errorThrown + " error.\n" : "") + "Error trying to " + action_label + " " + file_type + ".\nPlease try again..." + msg);
				},
			});
		}
	}
}

function refreshLayerProjects(path) {
	var choose_available_project= $(".choose_available_project");
	var option = choose_available_project.find(" > .layer select option:selected").first();
	var bean_name = option.attr("bean_name");
	var bean_file_name = option.attr("bean_file_name");
	
	if (layers_props && bean_name && layers_props[bean_name]) {
		StatusMessageHandler.showMessage("Refreshing projects' list...", "", "bottom_messages", 5000);
		
		var url = get_available_projects_props_url.replace("#bean_name#", bean_name).replace("#bean_file_name#", bean_file_name).replace("#path#", "/");
		
		$.ajax({
			type : "get",
			url : url,
			dataType: "json",
			success : function(data, textStatus, jqXHR) {
				if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
					showAjaxLoginPopup(jquery_native_xhr_object.responseURL, url, function() {
						StatusMessageHandler.removeLastShownMessage("error");
						refreshLayerProjects(path);
					});
				else {
					StatusMessageHandler.removeLastShownMessage("info");
					
					layers_props[bean_name]["projects"] = data;
					
					path = path.replace(/[\/]+/, "/").replace(/[\/]+$/, "");
					var dirs = path.split("/");
					dirs.pop();
					var parent_folder = dirs.join("/");
					
					updateLayerProjects(parent_folder);
				}
			},
			error : function(jqXHR, textStatus, errorThrown) { 
				var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
				StatusMessageHandler.showError((errorThrown ? errorThrown + " error.\n" : "") + "Error trying to refresh the files list.\nPlease refresh this page manually..." + msg);
			},
		});
	}
}

function onSuccessfullRenameFile(elm, type, action, path, new_file_name) {
	refreshLayerProjects(path);
}

function onSuccessfullRemoveFile(elm, type, action, path, new_file_name) {
	refreshLayerProjects(path);
}

function onSuccessfullCreateFile(elm, type, action, path, new_file_name) {
	refreshLayerProjects(path);
}

function searchProjects(elm) {
	elm = $(elm);
	var to_search = elm.val().toLowerCase().replace(/^\s*/, "").replace(/\s*$/, "");
	var ul = elm.parent().closest(".choose_available_project").find(" > .projects > ul");
	var lis = ul.children("li");
	
	if (to_search == "")
		lis.removeClass("hidden");
	else
		lis.each(function(idx, li) {
			li = $(li);
			var file_name = li.children("label").text();
			var matched = to_search != "" ? file_name.toLowerCase().indexOf(to_search) != -1 : true;
			
			if (matched)
				li.removeClass("hidden");
			else
				li.addClass("hidden");
		});
}

function resetSearchProjects(elm) {
	var input = $(elm).parent().children("input");
	input.val("");
	searchProjects(input[0]);
}

function sortProjects(elm) {
	elm = $(elm);
	var sort_type = elm.val();
	var ul = elm.parent().closest(".choose_available_project").find(" > .projects > ul");
	var lis = ul.children("li");
	var selector = " > label";
	
	lis.sort(function(li_a, li_b) {
		var a = $(li_a).find(selector).text();
		var b = $(li_b).find(selector).text();
		
		if (sort_type == "a_z") {
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
