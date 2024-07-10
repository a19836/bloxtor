$(function() {
	//prepare popup
	MyFancyPopup.init({
		parentElement: window,
	});
	MyFancyPopup.showOverlay();
	MyFancyPopup.showLoading();
	
	//prepare redirect when user is logged out
	var win_url = "" + document.location;
	win_url = win_url.indexOf("#") != -1 ? win_url.substr(0, win_url.indexOf("#")) : win_url;
	
	$.ajaxSetup({
		complete: function(jqXHR, textStatus) {
			if (jqXHR.status == 200 && jqXHR.responseText.indexOf('<div class="login">') > 0 && jqXHR.responseText.indexOf('<h1>Login</h1>') > 0)
				document.location = win_url;
	    	}
	});
	
	//prepare path_to_filter
	path_to_filter = path_to_filter.replace(/\/+/g, "/").replace(/^\//g, "").replace(/\/$/g, ""); //remove duplicated slashes and at the begin and at the end. Then converts path_to_filter to an array.
	
	//prepare menu tree
	var file_tree = $("#file_tree");
	var file_tree_ul = file_tree.children("ul");
	
	mytree.options.toggle_selection = false;
	mytree.options.ajax_callback_before = prepareLayerFileNodes1;
	mytree.options.ajax_callback_after = prepareLayerFileNodes2;
	initFileTreeMenu();
	
	if (path_to_filter != "")
		file_tree.addClass("hidden");
	
	var lis = file_tree_ul.children("li");
	lis.each(function(idx, li) {
		li = $(li);
		var link = li.children("a");
		
		//prepare menus for main nodes
		if (li.is(".main_node_db")) {
			addLiContextMenu(link.addClass("link"), "main_db_group_context_menu", {callback: onDBContextMenu});
			initDBContextMenu(li);//This covers the scenario where the DB_DRIVER node is inside of the ".db_layers li.main_node_db" and ".db_layers" node
		}
		else if (li.is(".main_node_ibatis")) {
			addLiContextMenu(link.addClass("link"), "main_ibatis_group_context_menu", {callback: onIbatisContextMenu});
			initIbatisContextMenu(li);
		}
		else if (li.is(".main_node_hibernate")) {
			addLiContextMenu(link.addClass("link"), "main_hibernate_group_context_menu", {callback: onHibernateContextMenu});
			initHibernateContextMenu(li);
		}
		else if (li.is(".main_node_businesslogic")) {
			addLiContextMenu(link.addClass("link"), "main_business_logic_group_context_menu", {callback: onContextContextMenu});
			initContextContextMenu(li);
		}
		else if (li.is(".main_node_presentation")) {
			addLiContextMenu(link.addClass("link"), "main_presentation_group_context_menu", {callback: onPresentationContextMenu});
			initPresentationContextMenu(li);
		}
		else if (li.is(".main_node_lib")) {
			initLibContextMenu(li);
		}
		else if (li.is(".main_node_lib")) {
			initLibContextMenu(li);
		}
		else if (li.is(".main_node_dao")) {
			addLiContextMenu(link.addClass("link"), "main_dao_group_context_menu", {callback: onDaoContextMenu});
			initDaoContextMenu(li);
		}
		else if (li.is(".main_node_vendor")) {
			addLiContextMenu(link.addClass("link"), "main_vendor_group_context_menu", {callback: onVendorContextMenu});
			initVendorContextMenu(li);
		}
		else if (li.is(".main_node_test_unit")) {
			addLiContextMenu(link.addClass("link"), "main_test_unit_group_context_menu", {callback: onTestUnitContextMenu});
			initTestUnitContextMenu(li);
		}
		else if (li.is(".main_node_other")) {
			addLiContextMenu(link.addClass("link"), "main_other_group_context_menu", {callback: onVendorContextMenu});
			initOtherContextMenu(li);
		}
		
		prepareFileTreeItemLinkMenus(link);
		
		//show first children in layer, but only the first level
		refreshAndShowNodeChilds(li);
	});
	
	if (lis.length == 0) {
		MyFancyPopup.hidePopup();
		StatusMessageHandler.showError("There are no files");
	}
	//else Do not close popup bc it will be closed in the prepareLayerFileNodes2 method
});

function toggleListType(elm, type) {
	elm = $(elm);
	var p = elm.parent();
	
	p.find(".icon").removeClass("active");
	elm.find(".icon").addClass("active");
	
	p.closest(".top_bar").parent().children("#file_tree").removeClass(type == "block_view" ? "list_view" : "block_view").addClass(type == "block_view" ? "block_view" : "list_view");
}

function searchFiles(elm) {
	elm = $(elm);
	var to_search = elm.val().toLowerCase().replace(/^\s*/, "").replace(/\s*$/, "");
	var mytree = elm.parent().closest(".top_bar").parent().children("#file_tree");
	var main_ul = mytree.find(" > ul > li > ul");
	
	var searchFilesInUl = function(ul, to_search) {
		var found = false;
		
		$.each(ul.find(" > li > a.jstree-anchor"), function(idx, a) {
			a = $(a);
			var li = a.parent();
			var name = a.children("label").text();
			var matched = name.toLowerCase().indexOf(to_search) != -1;
			var sub_ul = li.children("ul");
			
			if (sub_ul.children().length > 0 && searchFilesInUl(sub_ul, to_search))
				matched = true;
			
			if (matched) {
				li.removeClass("hidden");
				found = true;
			}
			else
				li.addClass("hidden");
		});
		
		return found;
	}
	
	if (to_search == "")
		main_ul.find("li").removeClass("hidden");
	else
		searchFilesInUl(main_ul, to_search);
}

function resetSearchFiles(elm) {
	var input = $(elm).parent().children("input");
	input.val("");
	searchFiles(input[0]);
}

function sortFiles(elm) {
	elm = $(elm);
	var sort_type = elm.val();
	var mytree = elm.parent().closest(".top_bar").parent().children("#file_tree");
	var main_ul = mytree.find(" > ul > li > ul");
	
	var sortFilesInUl = function(ul, sort_type) {
		var lis = ul.children("li");
		var selector = " > a > label";
		
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
		
		lis.each(function(idx, li) {
			var sub_ul = $(li).children("ul");
			
			if (sub_ul.children().length > 0)
				sortFilesInUl(sub_ul, sort_type);
		});
	};
	
	sortFilesInUl(main_ul, sort_type);
}

function prepareLayerFileNodes1(ul, data) {
	//filter data by path
	if (path_to_filter != "" && data) {
		var path_to_filter_parts = path_to_filter.split("/");
		var path_to_filter_parts_idx = getPathToFilterPartsIndex($(ul).parent(), "#file_tree");
		data = prepareDataAccordingWithPathToFilterIndex(data, path_to_filter, path_to_filter_parts, path_to_filter_parts_idx);
	}
	
	//create nodes based in data
	prepareLayerNodes1(ul, data);
}

function prepareLayerFileNodes2(ul, data) {
	var path_to_filter_parts = null;
	var path_to_filter_parts_idx = null;
	
	//filter data by path
	if (path_to_filter != "" && data) {
		path_to_filter_parts = path_to_filter.split("/");
		path_to_filter_parts_idx = getPathToFilterPartsIndex($(ul).parent(), "#file_tree");
		data = prepareDataAccordingWithPathToFilterIndex(data, path_to_filter, path_to_filter_parts, path_to_filter_parts_idx);
	}
	
	//prepare created nodes based in data
	prepareLayerNodes2(ul, data);
	
	if (data) {
		ul = $(ul);
		var links = ul.find("li > a");
		
		//prepare icons menus
		$.each(links, function(idx, link) {
			prepareFileTreeItemLinkMenus(link);
		});
		
		//prepare projects urls for presentation projects with some element_type
		if (item_type == "presentation" && element_type && data.properties) {
			var bean_name = data.properties.bean_name ? data.properties.bean_name : "";
			var main_layer_properties = bean_name && main_layers_properties[bean_name] ? main_layers_properties[bean_name] : null;
			var prefix_path = main_layer_properties ? main_layer_properties["prefix_path"] : null;
			
			if (prefix_path)
				ul.find(".project, .project_common").each(function(idx, i) {
					var li = $(i).parent().parent();
					var li_ul = li.children("ul");
					
					//prepare ul url
					var url = li_ul.attr("url");
					
					url = url.replace("&folder_type=project", "&folder_type=" + element_type);
					url = url.replace(/&path=([^&]*)&/, function (m, p1, offset, string) {
						return "&path=" + p1 + prefix_path + "&";
					});
					
					li_ul.attr("url", url);
					
					//add new icons menus
					var key = (element_type == "webroot" ? element_type : (element_type.substr(element_type.length - 1) == "y" ? element_type.substr(0, element_type.length - 1) + "ies" : element_type + "s")) + "_folder";
					var sub_a = li_ul.find(" > li > a ." + key).parent();
					var new_icons = sub_a.children(".icons").children(".create_folder, .create_file");
					var li_a = li.children("a");
					li_a.children(".icons").prepend(new_icons);
					li_a.attr("create_url", sub_a.attr("create_url"));
					
					//add new icons
					var icon_classes = {};
					
					if (element_type == "entity") {
						icon_classes = {
							create_automatically: "create_automatically_url",
							create_uis_diagram: "create_uis_diagram_url",
							view_project: "view_project_url",
							paste: "paste_url"
						};
						
						li_a.children("i").addClass("entities_folder"); //must add this bc presentation menu for entities folders are init based in the entities_folder class
					}
					else if (element_type == "template") {
						icon_classes = {
							install_template: "install_template_url",
							convert_template: "convert_template_url",
							paste: "paste_url"
						};
					}
					else
						icon_classes = {paste: "paste_url"};
					
					addTreeNodeIcons(icon_classes, li_a, sub_a);
					
					//clean ul so we can get the right children
					li_ul.html("");
				});
		}
		//prepare db driver urls
		else if (item_type == "db" && path_to_filter) { 
			ul.find(".db_driver").each(function(idx, i) {
				var li = $(i).parent().parent();
				var li_ul = li.children("ul");
				var li_a = li.children("a");
				var sub_a = li_ul.find(" > li > a .db_management").parent();
				
				//add new icons for db_driver type
				addTreeNodeIcons({add_table: "add_table_url"}, li_a, sub_a);
				
				//clean ul so we can get the right children
				li_ul.html("");
			});
		}
		
		//open sub nodes according with path_to_filter
		if (path_to_filter != "" && path_to_filter_parts_idx >= 0 && path_to_filter_parts.length > path_to_filter_parts_idx) {
			var file_tree = $("#file_tree");
			
			//check if exists list from data
			var data_contains_filtered_items = false;
			for (var k in data)
				if (k != "properties" && k != "aliases") {
					data_contains_filtered_items = true;
					break;
				}
			
			if (data_contains_filtered_items) {
				//refresh created lis and show their sub-nodes
				var lis = ul.children("li");
				
				lis.each(function(idx, li) {
					refreshAndShowNodeChilds( $(li) ); 
				});
				
				//make last node as primary node
				if (path_to_filter_parts_idx == path_to_filter_parts.length - 1) {
					var main_ul = file_tree.children("ul");
					var old_lis = main_ul.children("li");
					main_ul.append(lis);
					old_lis.remove(); //I can only remove the old lis after add the new lis, otherwise I will loose all events fro mthe new lis, including the contextmenus...
					
					//add refresh icon
					lis.find(" > a > .icons").each(function(idx, icons) {
						icons = $(icons);
						icons.children(".sub_menu").before('<span class="icon refresh" title="Refresh" onClick="refreshAndShowNodeChildsFromIcon(this)">Add File</span>');
						
						//add sub menus to context_menu.
						var menu_items_classes = {};
						var a = icons.parent().closest("a");
						var context_menu_id = a.data("context_menu_id");
						var context_menu_elm = $("#" + context_menu_id);
						
						if (element_type == "entity")
							menu_items_classes = {
								"-": null,
								create_automatically: "create_automatically_url",
								create_uis_diagram: "create_uis_diagram_url",
								"-": null,
								paste: "paste_url"
							};
						else if (element_type == "template")
							menu_items_classes = {
								install_template: "install_template_url",
								convert_template: "convert_template_url",
								"-": null,
								paste: "paste_url"
							};
						else if (item_type == "db" && icons.children(".add_table").length > 0)
							menu_items_classes = {
								add_table: "add_table_url"
							};
						else
							menu_items_classes = {
								paste: "paste_url"
							};
						
						var html = getMenuItemsHtml(menu_items_classes, a, icons);
						
						if (html)
							context_menu_elm.append(html);
					});
					
					//disable path_to_filter
					path_to_filter = "";
					
					file_tree.removeClass("hidden");
					MyFancyPopup.hidePopup();
				}
				//else do nothing bc it exists childs and path_to_filter didn't end yet...
			}
			else { //if there are no filtered childs
				file_tree.children("ul").children("li").remove(); //delete all children
				StatusMessageHandler.showError("No files in: " + path_to_filter);
				
				file_tree.removeClass("hidden");
				MyFancyPopup.hidePopup();
			}
		}
		else
			MyFancyPopup.hidePopup();
		
		var title = $(".top_bar > header > .title");
		searchFiles( title.find(" > .search_file > input")[0] );
		sortFiles( title.children(".sort_files")[0] );
	}
}

function refreshAndShowNodeChildsFromIcon(elm) {
	window.event.stopPropagation();
	refreshAndShowNodeChilds( $(elm).parent().closest('li') )
}

function addTreeNodeIcons(icon_classes, li_a, sub_a) {
	for (var icon_class in icon_classes) {
		var new_icon = sub_a.children(".icons").children("." + icon_class);
		
		if (new_icon.length > 0) {
			var url_attr = icon_classes[icon_class];
			li_a.children(".icons").prepend(new_icon);
			li_a.attr(url_attr, sub_a.attr(url_attr));
		}
	};
}

function getMenuItemsHtml(menu_items_classes, a, icons) {
	var html = "";
	
	for (var menu_item_class in menu_items_classes) {
		if (menu_item_class == "-")
			html += '<li class="line_break"></li>';
		else {
			var icon = icons.children("." + menu_item_class);
			
			if (icon.length > 0) {
				var url_attr = menu_items_classes[menu_item_class];
				
				html += '<li class="' + menu_item_class + '">' + ("" + icon.html()).replace("<div ", '<a ' + url_attr + '="' + a.attr(url_attr) + '"').replace("</div>", "</a>").replace('context_menu_on_click="', 'onClick="') + '</li>';
			}
		}
	}
	
	return html;
}

function prepareFileTreeItemLinkMenus(a) {
	a = $(a);
	
	//put link onclick event in label and disable link click event
	var on_click = a.attr("onClick");
	
	if (on_click) {
		a.removeAttr("onClick");
		
		//replace "this" string in onclick attribute by $(this).parent()[0]
		on_click = ("" + on_click).replace(/(|\()(this)(,|\.|\))/g, function (m, p1, p2, p3, offset, string) {
			return p1 + "$(this).parent()[0]" + p3;
		});
		a.children("label").attr("onClick", on_click);
	}

	//add sub menus based in 
	var context_menu_id = a.data("context_menu_id");
	
	if (context_menu_id) {
		var context_menu_elm = $("#" + context_menu_id);
		var html = $('<div class="icons">'
				+ 	'<span class="icon sub_menu" title="Show other menus"></span>'
				+ '</div>');
		var sub_menu_icon = html.find(".sub_menu");
		
		sub_menu_icon.click(function(e) {
			e.preventDefault();
			e.stopPropagation();
			
			//show or hide contextmenu
			if (context_menu_elm.is(":visible"))
				MyContextMenu.hideContextMenu(context_menu_elm);
			else {
				$(this).parent().closest("li").find(" > a > label").contextmenu();
				MyContextMenu.updateContextMenuPosition(context_menu_elm, e);
				
				//update upload menu to open in a popup
				var cm_link = context_menu_elm.find(" > .upload > a");
				if (cm_link[0] && cm_link.attr("onClick") == "return goTo(this, 'upload_url', event)")
					cm_link.attr("onClick", "return openPopup(this, 'upload_url', event)");
			}
			return false;
		});
		
		//prepare other icons
		if (inline_icons_by_context_menus && inline_icons_by_context_menus.hasOwnProperty(context_menu_id) && inline_icons_by_context_menus[context_menu_id]) {
			$.each(inline_icons_by_context_menus[context_menu_id], function(idx, class_name) {
				if (class_name) {
					var context_menu_li = context_menu_elm.children("." + class_name);
					
					if (context_menu_li[0]) {
						var icon = $('<span class="icon ' + class_name + '" title="' + context_menu_li.children("a").text() + '">' + context_menu_li.html().replace("<a ", "<div ").replace("</a>", "</div>") + '</span>'); //replace "a" element by "div" element, bc icons is already inside of a "a" element.
						var div = icon.children("div");
						div.attr("context_menu_on_click", div.attr("onClick"));
						div.removeAttr("onClick");
						div.click(function(e) {
							return onMenuItemIconClick(this, e);
						});
						
						icon.data("context_menu_id", context_menu_id);
						icon.data("context_menu_options", a.data("context_menu_options"));
						
						sub_menu_icon.before(icon);
					}
				}
			});
		}
		
		a.append(html);
	}
}

function onMenuItemIconClick(elm, e) {
	e.preventDefault();
	e.stopPropagation();
	
	elm = $(elm);
	var context_menu_on_click = elm.attr("context_menu_on_click");
	
	if (context_menu_on_click) {
		var icon = elm.parent();
		var icons = icon.parent();
		var context_menu_options = icon.data("context_menu_options");
		var context_menu_id = icon.data("context_menu_id");
		var context_menu_elm = $("#" + context_menu_id);
		
		var class_names = icon.attr("class");
		var class_name = class_names.replace("icon", "").replace(/\s+/, "");
		//console.log(class_name);
		var context_menu_li = context_menu_elm.find("." + class_name);
		var context_menu_li_a = context_menu_li.children("a");
		
		//execute callback handler so it can update the attributes with the correspondent urls
		if (context_menu_options && context_menu_options.callback && typeof context_menu_options.callback == "function") {
			var original_target = e.target;
			e.target = icons[0];
			context_menu_options.callback(icons.parent().closest("a"), context_menu_elm, e);
			e.target = original_target;
			
			//add new attributes to div
			var attributes = context_menu_li_a[0].attributes;
			
			if (attributes)
				for (var i = 0; i < attributes.length; i++) {
					var attr_name = attributes[i].nodeName;
					var attr_value = attributes[i].nodeValue;
					
					if (attr_name.toLowerCase() != "onclick" && attr_name.toLowerCase() != "class")
						elm.attr(attr_name, attr_value);
				}
		}
		
		//update last_selected_node_id - simulate the contextmenu behaviour - Note that some functions from context_menu_on_click will do $(elm).parent().parent().attr("last_selected_node_id") so we need to add the last_selected_node_id
		icons.attr("last_selected_node_id", icons.parent().closest("li").attr("id"));
		
		//prepare context_menu_on_click code for eval
		context_menu_on_click = context_menu_on_click.replace("return ", "");
		
		if (!context_menu_on_click.match(/;\s*$/))
			context_menu_on_click += ";";
		
		context_menu_on_click = context_menu_on_click.replace(/(|\()(this)(,|\.|\))/g, function (m, p1, p2, p3, offset, string) {
			return p1 + "elm[0]" + p3;
		});
		
		//simulates onclick event
		//console.log(context_menu_on_click);
		eval(context_menu_on_click);
	}
	
	return false;
}

//is used in the goTo function
function goToHandler(url, a, attr_name, originalEvent) {
	MyFancyPopup.showOverlay();
	MyFancyPopup.showLoading();
	
	try {
		//save cookie with url, so when we refresh the browser, the right panel contains the latest opened url
		MyJSLib.CookieHandler.setCurrentDomainEternalRootSafeCookie('default_page', url); //usually the list.php is open in the admin_simple so we need to set the default_page cookie, so the next time we return to the framework, it opens the last page.
		//console.log(url);
	}
	catch(e) {
		//sometimes gives an error bc of the iframe beforeunload event. This doesn't matter, but we should catch it and ignore it.
		if (console && console.log)
			console.log(e);
	}
	
	//open url
	document.location = url;
}

function openPopup(a, attr_name, originalEvent) {
	var ap = $(".auxiliar_popup");
	var iframe = ap.children("iframe");
	var url = a.getAttribute(attr_name);
	
	$(document.body).addClass("no_scroll");
	
	var j_a = $(a);
	if (j_a.hasClass("jstree-anchor")) 
		last_selected_node_id = j_a.parent().attr("id");
	else 
		last_selected_node_id = j_a.parent().parent().attr("last_selected_node_id");
	
	url += (url.indexOf("?") != -1 ? "&" : "?") + "popup=1";
	
	MyFancyPopup.init({
		elementToShow: ap,
		parentElement: document,
		type:"iframe",
		url:url,
		onOpen: function() {
			iframe[0].src = url;
		},
		onClose: function() {
			$(document.body).removeClass("no_scroll");
			
			refreshAndShowNodeChildsByNodeId(last_selected_node_id);
		},
	});
	
	MyFancyPopup.showPopup();
}

function createPage(elm) {
	var entities_folder = $('#file_tree.list_entity .entities_folder').parent();
	var create_file_icon = entities_folder.find(' > .icons > .create_file > [context_menu_on_click]');
	create_file_icon.click();
	
}

//overright this method from admin_menu.js
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
						if (item.attr("onClick")) {
							try {
								item.trigger("click");
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
//overright this method from admin_menu.js
function triggerFileNodeAfterCreatePage(a, attr_name, action, new_file_name, url, tree_node_id_to_be_updated) {
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
							if (new_a.attr("add_url")) {
								goToPopup(new_a[0], "add_url", window.event, 'with_iframe_title add_entity_popup big', function() {
									if (item.attr("onClick")) {
										try {
											item.trigger("click");
										}
										catch(e) {
											if (console && console.log)
												console.log(e);
										}
									}
								});
							}
							else if (item.attr("onClick"))
								item.trigger("click");
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
