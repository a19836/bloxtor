var iframe_overlay = null; //To be used by sub-pages
var empty_layers_uls = [];

$(function() {
	var hide_panel = $("#hide_panel");
	var left_panel = $("#left_panel");
	var right_panel = $("#right_panel");
	
	//prepare right panel iframe
	if (right_panel[0]) {
		var win_url = "" + document.location;
		win_url = win_url.indexOf("#") != -1 ? win_url.substr(0, win_url.indexOf("#")) : win_url;
		
		iframe_overlay = right_panel.find('.iframe_overlay');
		var iframe = right_panel.find('iframe');
		
		var iframe_unload_func = function (e) {
			iframe_overlay.show();
		};
		
		iframe.load(function() {
			$(iframe[0].contentWindow).unload(iframe_unload_func);
		
			iframe_overlay.hide();
			
			//prepare redirect when user is logged out
			try {
				iframe[0].contentWindow.$.ajaxSetup({
					complete: function(jqXHR, textStatus) {
						if (jqXHR.status == 200 && jqXHR.responseText.indexOf('<div class="login">') > 0 && jqXHR.responseText.indexOf('<div id="layoutAuthentication">') > 0) 
							document.location = win_url;
				    	}
				});
			}
			catch (e) {}
		});
		$(iframe[0].contentWindow).unload(iframe_unload_func);
		
		//prepare redirect when user is logged out
		$.ajaxSetup({
			complete: function(jqXHR, textStatus) {
				if (jqXHR.status == 200 && jqXHR.responseText.indexOf('<div class="login">') > 0 && jqXHR.responseText.indexOf('<div id="layoutAuthentication">') > 0)
					document.location = win_url;
		    	}
		});
	}
	
	//set scroll to where the select project is
	initFilterByLayout();
	
	//prepare hide_panel
	if (hide_panel[0]) 
		hide_panel.draggable({
			axis: "x",
			appendTo: 'body',
			containment: $("#hide_panel").parent(),
			cursor: 'move',
			cancel: '.button',
			start : function(event, ui) {
				if (typeof navigator_droppables_active != "undefined")
					navigator_droppables_active = false;
				
				if ($(this).children(".button").hasClass("minimize")) {
					$('#left_panel, #hide_panel, #right_panel').addClass("dragging"); // We need to hide the iframe bc the draggable event has some problems with iframes
					return true;
				}
				return false;
			},
			drag : function(event, ui) {
				updatePanelsAccordingWithHidePanel();
			},
			stop : function(event, ui) {
				updatePanelsAccordingWithHidePanel();
				$('#left_panel, #hide_panel, #right_panel').removeClass("dragging");
				
				if (typeof navigator_droppables_active != "undefined")
					navigator_droppables_active = true;
			}
		});
	
	if (left_panel[0]) {
		//prepare path_to_filter
		path_to_filter = path_to_filter.replace(/\/+/g, "/").replace(/^\//g, "").replace(/\/$/g, ""); //remove duplicated slashes and at the begin and at the end. Then converts path_to_filter to an array.
		var path_to_filter_exists = path_to_filter != "";
		
		var is_left_panel_with_tabs = left_panel.is(".left_panel_with_tabs");
		
		if (!is_left_panel_with_tabs) {
			//prepare menu tree
			mytree.options.ajax_callback_after = function(ul, data) {
				prepareLayerNodes2(ul, data);
				
				//prepare empty_layers_uls
				if (empty_layers_uls.indexOf(ul) != -1)
					prepareLayerEmptyContainers(ul);
			};
			
			initFileTreeMenu();
			
			//open pages li for the filtered project
			var file_tree_ul = $("#file_tree > ul");
			var lis = file_tree_ul.children("li");
			var pres_item = lis.filter(".presentation_layers");
			
			if (pres_item[0]) {
				var new_label = "Interface - " + pres_item.find(" > a > label > label").text();
				pres_item.find(" > a > label > label").text(new_label);
				
				if (path_to_filter_exists)
					openMainNodeTreeItemByPath(pres_item, path_to_filter + "/src/entity");
			}
			
			var da_item = lis.filter(".data_access_layers");
			if (da_item[0]) {
				var new_label = "SQL - " + da_item.find(" > a > label > label").text();
				da_item.find(" > a > label > label").text(new_label);
			}
			
			lis.filter(":not(.presentation_layers)").each(function(idx, li) {
				$(li).removeClass("jstree-open").addClass("jstree-closed");
			});
			
			//prepare empty_layers_uls
			lis.filter(".presentation_layers, .business_logic_layers, .data_access_layers, .db_layers").each(function(idx, li) {
				li = $(li);
				var ul = li.find(" > ul > li > ul")[0];
				
				empty_layers_uls.push(ul);
				prepareLayerEmptyContainers(ul);
			});
		}
		else { //change tree to be separated with tabs
			//prepare menu tree
			mytree.options.ajax_callback_before = prepareLayerFileNodes1;
			mytree.options.ajax_callback_after = prepareLayerFileNodes2;
			
			initFileTreeMenu();
			
			//prepare file_tree with tabs
			var file_tree_ul = $("#file_tree > ul");
			var lis = file_tree_ul.children("li");
			var tabs_html = '<ul class="tabs">';
			
			$.each(lis, function(idx, li) {
				li = $(li);
				var id = li.attr("id");
				var label = li.find(" > a > label").text();
				var djst = li.attr("data-jstree");
				var m = djst.match(/"icon"\s*:\s*"(.*)"/);
				var classes = m[1];
				var tab_classes = "tab_main_node tab_" + classes.split(" ").join(" tab_");
				var tab_label = "";
				
				if (classes.indexOf("main_node_presentation_layers") != -1)
					tab_label = "Interface";
				else if (classes.indexOf("main_node_business_logic_layers") != -1)
					tab_label = "Logic";
				else if (classes.indexOf("main_node_data_access_layers") != -1)
					tab_label = "SQL";
				else if (classes.indexOf("main_node_db_layers") != -1)
					tab_label = "DB";
				else if (classes.indexOf("main_node_library") != -1)
					tab_label = "Library";
				
				tabs_html	+= '<li class="' + tab_classes + '"><a href="#' + id + '" title="' + tab_label + '"><i class="tab_icon ' + classes + '"></i><span class="tab_label">' + tab_label + '</span></a></li>';
				
				li.addClass("main_tree_node scroll");
			});
			
			tabs_html	+= '</ul>';
			
			file_tree_ul.prepend(tabs_html);
			file_tree_ul.tabs();
			
			file_tree_ul.find(" > ul.tabs > .tab_main_node a").click(function(originalEvent){
				MyContextMenu.hideAllContextMenu();
				
				prepareLayerActiveTab( $(this).parent() );
			});
			
			file_tree_ul.children("li.main_tree_node").each(function(idx, item) {
				item = $(item);
				item.addClass("hide_tree_item");
				
				var main_tree_node_label = item.find(" > a > label").text();
				
				if (item.is(".presentation_layers"))
					main_tree_node_label = "Interface - " + main_tree_node_label;
				else if (item.is(".data_access_layers"))
					main_tree_node_label = "SQL - " + main_tree_node_label;
				
				item.prepend('<div class="title">' + main_tree_node_label + '</div>');
				
				var main_ul = item.children("ul");
				var children = main_ul.children("li");
				
				//if multiple presentation layers and path_to_filter exists, remove the presentation layers that don't have the correspondent project
				if (children.length > 1 && item.is(".presentation_layers") && path_to_filter_exists) {
					var selected_bean_name = null;
					var selected_bean_file_name = null;
					
					for (var bean_name in main_layers_properties) {
						var layer_props = main_layers_properties[bean_name];
						var layer_bean_folder_name = layer_props["layer_bean_folder_name"];
						
						layer_bean_folder_name = layer_bean_folder_name.replace(/\/+/g, "/").replace(/^\//g, "").replace(/\/$/g, ""); //remove duplicated slashes and at the begin and at the end. Then converts path_to_filter to an array.
						
						if (path_to_filter.indexOf(layer_bean_folder_name) == 0) {
							selected_bean_name = bean_name;
							selected_bean_file_name = layer_props["bean_file_name"];
							break;
						}
					}
					
					if (selected_bean_name && selected_bean_file_name) {
						$.each(children, function(idx, child) {
							child = $(child);
							var child_ul_url = child.children("ul").attr("url");
							
							if (child_ul_url.indexOf("?bean_name=" + selected_bean_name + "&bean_file_name=" + selected_bean_file_name + "&") != -1) 
								child.addClass("jstree-last");
							else
								child.remove();
						});
						
						children = main_ul.children("li");
					}
				}
				
				//if layer is unique, then show only its content
				if (item.find(" > a > i").is(".main_node_management, .main_node_vendor")) {
					item.removeClass("jstree-closed").addClass("jstree-open hide_tree_item");
					
					iniSubMenu(item);
				}
				else if (children.length == 1) {
					var child = children.first();
					var sub_children = child.find(" > ul > li");
					var is_main_node = child.is(".main_node_presentation, .main_node_businesslogic, .main_node_ibatis, .main_node_hibernate, .main_node_db");
					
					item.removeClass("with_sub_groups");
					child.removeClass("jstree-closed").addClass("jstree-open hide_tree_item");
					
					if (is_main_node) {
						var child_label = child.find(" > a > label").text();
						item.children(".title").attr("title", "Layer name: " + child_label);
					}
					
					if (sub_children.length == 0)
						mytree.refreshNodeChilds(child, {ajax_callback_last: function(ul, data) {
							//if path_to_filter exists and is main_node_presentation, the submenu is already inited with the right project node.
							if (!child.is(".main_node_presentation") || !path_to_filter_exists) {
								iniSubMenu(child);
								//console.log(child[0]);
							}
							
							//prepare empty_layers_uls
							if (is_main_node) {
								empty_layers_uls.push(ul);
								prepareLayerEmptyContainers(ul);
							}
						}});
					else {
						//prepare empty_layers_uls
						if (is_main_node) {
							var ul = child.children("ul")[0];
							empty_layers_uls.push(ul);
							prepareLayerEmptyContainers(ul);
						}
						
						if (!child.is(".main_node_presentation") || !path_to_filter_exists) { //if path_to_filter exists and is main_node_presentation, the submenu is already inited with the right project node, so we only need to init the sub menu for the others.
							iniSubMenu(child);
							//console.log(child[0]);
						}
					}
				}
				else 
					item.addClass("with_sub_groups");
			});
		}
	}
});

function prepareLayerActiveTab(li) {
	var ul = li.parent();
	ul.children("li").removeClass("prev_tab");
	
	do {
		var prev = li.prev("li");
		
		if (prev.is(":visible")) {
			prev.addClass("prev_tab");
			break;
		}
		else
			li = prev;
	}
	while (prev[0]);
}

function prepareLayerFileNodes1(ul, data) {
	//filter data by path
	if (path_to_filter != "" && data && data.properties && data.properties["item_type"] == "presentation") {
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
	var execute_filter = path_to_filter != "" && data && data.properties && data.properties["item_type"] == "presentation";
	
	//filter data by path
	if (execute_filter) {
		path_to_filter_parts = path_to_filter.split("/");
		path_to_filter_parts_idx = getPathToFilterPartsIndex($(ul).parent(), "#file_tree");
		data = prepareDataAccordingWithPathToFilterIndex(data, path_to_filter, path_to_filter_parts, path_to_filter_parts_idx);
	}
	
	//prepare created nodes based in data
	prepareLayerNodes2(ul, data);
	
	if (data) {
		ul = $(ul);
		
		//open sub nodes according with path_to_filter
		if (execute_filter && path_to_filter_parts_idx >= 0 && path_to_filter_parts.length > path_to_filter_parts_idx) {
			//check if exists list from data
			var data_contains_filtered_items = false;
			for (var k in data)
				if (k != "properties" && k != "aliases") {
					data_contains_filtered_items = true;
					break;
				}
			
			var lis = ul.children("li");
			
			if (data_contains_filtered_items) {
				lis.addClass("hide_tree_item");
				
				//make last node as primary node
				if (path_to_filter_parts_idx == path_to_filter_parts.length - 1) {
					//disable path_to_filter
					path_to_filter = "";
					
					//prepare project and open the entities_folder by default
					if (lis.find(" > a > i").is(".project, .project_common")) {
						lis.each(function(idx, li) {
							li = $(li);
							li.removeClass("jstree-closed").addClass("jstree-open with_sub_groups");
							
							iniSubMenu(li);
							
							//open project children
							mytree.refreshNodeChilds(li, {ajax_callback_last: function(ul, data) {
								ul = $(ul);
								var project_lis = ul.children();
								
								//open the pages li by default
								var pages_li = project_lis.find(" > a > i.entities_folder").parent().parent();
								refreshAndShowNodeChilds(pages_li);
							}});
							li.children("ul").show();
						});
					}
					else {
						//refresh created lis and show their sub-nodes
						lis.each(function(idx, li) {
							refreshAndShowNodeChilds( $(li) ); 
						});
					}
				}
				else {
					//refresh created lis and show their sub-nodes
					lis.each(function(idx, li) {
						refreshAndShowNodeChilds( $(li) ); 
					});
				}
			}
			else { //if there are no filtered childs
				lis.remove(); //delete all children
			}
		}
	}
}

function openMainNodeTreeItemByPath(main_node, path_to_filter) {
	var ul = main_node.children("ul");
	var children = ul.children("li");
	var selected_children = null;
	var path_to_search = null;
	
	//if multiple presentation layers and path_to_filter exists, remove the presentation layers that don't have the correspondent project
	if (children.length > 0) {
		var selected_bean_name = null;
		var selected_bean_file_name = null;
		
		for (var bean_name in main_layers_properties) {
			var layer_props = main_layers_properties[bean_name];
			var layer_bean_folder_name = layer_props["layer_bean_folder_name"];
			
			layer_bean_folder_name = layer_bean_folder_name.replace(/\/+/g, "/").replace(/^\//g, "").replace(/\/$/g, ""); //remove duplicated slashes and at the begin and at the end. Then converts path_to_filter to an array.
			
			if (path_to_filter.indexOf(layer_bean_folder_name) == 0) {
				selected_bean_name = bean_name;
				selected_bean_file_name = layer_props["bean_file_name"];
				path_to_search = path_to_filter.substr(layer_bean_folder_name.length, path_to_filter.length - layer_bean_folder_name.length);
				path_to_search = path_to_search.replace(/\/+/g, "/").replace(/^\//g, "").replace(/\/$/g, ""); //remove duplicated slashes and at the begin and at the end. Then converts path_to_filter to an array.
				break;
			}
		}
		
		if (selected_bean_name && selected_bean_file_name) {
			$.each(children, function(idx, child) {
				child = $(child);
				var child_ul_url = child.children("ul").attr("url");
				
				if (child_ul_url.indexOf("?bean_name=" + selected_bean_name + "&bean_file_name=" + selected_bean_file_name + "&") != -1) {
					selected_children = child;
					return false;
				}
			});
		}
	}
		
	if (selected_children && path_to_search)
		openTreeItemByPath(selected_children, path_to_search);
}

function openTreeItemByPath(li, path_to_search) {
	var ul = li.children("ul");
	var url = ul.attr("url");
	var m = url.match(/&path=([^&]*)/);
	
	if (m && m.length >= 2) {
		var path = m[1];
		path = path.replace(/\/+/g, "/").replace(/^\//g, "").replace(/\/$/g, ""); //remove duplicated slashes and at the begin and at the end. Then converts path_to_search to an array.
		var regex = new RegExp("^" + path);
		
		if (regex.test(path_to_search)) {
			var children = ul.children();
			var handler = function(sub_lis) {
				
				for (var i = 0; i < sub_lis.length; i++) {
					var sub_li = $(sub_lis[i]);
					
					var found = openTreeItemByPath(sub_li, path_to_search);
					
					if (found)
						break;
				}
			};
			
			li.removeClass("jstree-closed").addClass("jstree-open");
			
			if (children.length == 0)
				mytree.refreshNodeChilds(li, {ajax_callback_last: function(sub_ul, data) {
					var sub_children = $(sub_ul).children();
					handler(sub_children);
				}});
			else
				handler(children);
			
			return true;
		}
	}
	
	return false;
}

function iniSubMenu(tree_item_li) {
	var a = tree_item_li.children("a");
	var context_menu_id = a.data("context_menu_id");
	var context_menu_options = a.data("context_menu_options");
	
	if (context_menu_id && $.isPlainObject(context_menu_options) && typeof context_menu_options.callback == "function") {
		var main_tree_node = tree_item_li.closest(".main_tree_node");
		var main_tree_node_id = main_tree_node.attr("id");
		var tabs = main_tree_node.parent().children("ul.tabs");
		var tab_link = tabs.find("li > a[href='#" + main_tree_node_id + "']");
		var context_menu_elm = $("#" + context_menu_id);
		//console.log(tree_item_li[0]);
		//console.log(main_tree_node[0]);
		//console.log(tab_link[0]);
		
		var html = '<span class="icon sub_menu" title="Open context menu"></span>';
		var func = function(e) {
			e.preventDefault();
			e.stopPropagation();
			
			//show or hide contextmenu
			if (context_menu_elm.is(":visible"))
				MyContextMenu.hideContextMenu(context_menu_elm);
			else {
				a.children("label").contextmenu();
				MyContextMenu.updateContextMenuPosition(context_menu_elm, e);
			}
			
			return false;
		};
		
		var sub_menu_icon_1 = $(html);
		tab_link.parent().append(sub_menu_icon_1);
		sub_menu_icon_1.click(func);
		
		var sub_menu_icon_2 = $(html);
		main_tree_node.children(".title").append(sub_menu_icon_2);
		sub_menu_icon_2.click(func);
		
		/*if (tree_item_li.find(" > a > i").is(".project, .project_common")) {
			var level_menu = $('<li class="level">'
							+ '<label>Level:</label>'
							+ '<select onChange="toggleComplexityLevel(this)">'
								+ '<option value="0">Basic</option>'
								+ '<option value="1">Advanced</option>'
							+ '</select>'
						+ '</li>');
			
			context_menu_elm.append('<li class="line_break"></li>');
			context_menu_elm.append(level_menu);
		}*/
	}
}

function updatePanelsAccordingWithHidePanel() {
	var left_panel = $("#left_panel");
	var hide_panel = $("#hide_panel");
	var right_panel = $("#right_panel");
	var is_reverse = $("body").hasClass("main_navigator_reverse");
	var left_panel_min_width = 150;
	var hide_panel_width = hide_panel.outerWidth(); //include borders
	
	if (is_reverse) {
		var left = parseInt(hide_panel.offset().left);
		var right = $(window).width() - left - hide_panel_width;
		
		right = right < left_panel_min_width ? left_panel_min_width : (right > $(window).width() - left_panel_min_width ? $(window).width() - left_panel_min_width : right);
		
		left_panel.css("width", right + "px");
		hide_panel.css({"right": right + "px", left: "", top: "", width: ""});
		right_panel.css({"right": (right + hide_panel_width) + "px", left: ""});
	}
	else {
		var left = parseInt(hide_panel.css("left"));
		left = left < left_panel_min_width ? left_panel_min_width : (left > $(window).width() - left_panel_min_width ? $(window).width() - left_panel_min_width : left);
		
		left_panel.css("width", left + "px");
		hide_panel.css({"left": left + "px", right: "", top: "", width: ""});
		right_panel.css({"left": (left + hide_panel_width) + "px", right: ""});
	}
}

function prepareLayersEmptyContainers() {
	//prepare empty class if no visible children
	for (var i = 0, t = empty_layers_uls.length; i < t; i++)
		prepareLayerEmptyContainers(empty_layers_uls[i]);
}

function prepareLayerEmptyContainers(ul) {
	//prepare empty class if no visible children
	ul = $(ul);
	var children = ul.children("li");
	var exists_visible_children = false;
	
	for (var i = 0, t = children.length; i < t; i++) 
		if ($(children[i]).css("display") != "none") {
			exists_visible_children = true;
			break;
		}
	
	if (!exists_visible_children)
		ul.addClass("empty");
	else
		ul.removeClass("empty");
}

function toggleLeftPanel(elm) {
	button = $(elm);
	
	var body = $("body");
	
	if (body.hasClass("left_panel_hidden"))
		body.removeClass("left_panel_hidden");
	else
		body.addClass("left_panel_hidden");
}

function toggleAdvancedLevel(elm) {
	var left_panel = $("#left_panel");
	var advanced_level = left_panel.hasClass("advanced_level") ? "simple_level" : "advanced_level";
	
	MyJSLib.CookieHandler.setCurrentDomainEternalRootSafeCookie('advanced_level', advanced_level);
	left_panel.toggleClass("simple_level").toggleClass("advanced_level");
	
	//check if active tab is a hidden tab
	if (left_panel.is(".left_panel_with_tabs")) {
		var tabs_parent = left_panel.find(" > .mytree > ul");
		var active_tab = tabs_parent.tabs("option", "active");
		var li = $(tabs_parent.find(" > ul > li")[active_tab]);
		
		if (!li.is(":visible")) {
			li = tabs_parent.find(" > ul > li:visible").first();
			
			tabs_parent.tabs("option", "active", li.index());
		}
		
		prepareLayerActiveTab(li);
	}
	
	//prepare empty class if no visible children
	prepareLayersEmptyContainers();
}

function toggleTreeLayout(elm) {
	var left_panel = $("#left_panel");
	var tree_layout = left_panel.hasClass("left_panel_with_tabs") ? "left_panel_without_tabs" : "left_panel_with_tabs";
	var url = ("" + document.location);
	url = url.replace(/(&?)tree_layout=[^&]*/g, "");
	url += (url.indexOf("?") != -1 ? "&" : "?") + "tree_layout=" + tree_layout;
	url = url.replace(/[&]+/g, "&");
	
	document.location = url;
}

function toggleThemeLayout(elm) {
	var body = $("body");
	var theme_layout = body.hasClass("dark_theme") ? "light_theme" : "dark_theme"; //if no theme selected yet or if light_theme was previously selected, then set dark_theme. Otherwise choose light_theme.
	
	MyJSLib.CookieHandler.setCurrentDomainEternalRootSafeCookie('theme_layout', theme_layout);
	body.toggleClass("light_theme").toggleClass("dark_theme");
	
	updateThemeLayoutInIframes( $("iframe"), theme_layout);
}

function toggleNavigatorSide(elm) {
	var body = $("body");
	var main_navigator_side = body.hasClass("main_navigator_reverse") ? "" : "main_navigator_reverse";
	var url = ("" + document.location);
	url = url.replace(/(&?)main_navigator_side=[^&]*/g, "");
	url += (url.indexOf("?") != -1 ? "&" : "?") + "main_navigator_side=" + main_navigator_side;
	url = url.replace(/[&]+/g, "&");
	
	document.location = url;
}

function updateThemeLayoutInIframes(iframes, theme_layout) {
	iframes.each(function(idx, iframe) {
		//Note that if the iframe is from other domain, we cannot edit the iframe's html. So we need to have this enclosed in a try and catch.
		//Another situation that it can throw an exception, is if the iframe is not loaded yet.
		try { 
			var iframe_window = iframe.contentWindow; //we can get the reference to the inner window
			var iframe_doc = iframe.contentDocument || iframe_window.document; //...but we cannot get the reference to the document inside of an iframe from a different domain. By doing this code, it will launch an exception, not executing the rest of the code.
			var iframe_body = $(iframe_doc.body);
			//var iframe_body = $(iframe).contents().find("body");
			
			iframe_body.removeClass(theme_layout == "light_theme" ? "dark_theme" : "light_theme").addClass(theme_layout);
			
			if (iframe_body.hasClass(theme_layout)) //be sure that the body has the class and that we can really edit the iframe body, bc if the iframe is from other domain, we cannot edit the iframe's html.
				updateThemeLayoutInIframes(iframe_body.find("iframes"), theme_layout);
		}
		catch(e) {
			console.log(e);
		};
	});
}

function openFilterByLayoutSubMenu(elm) {
	openSubmenu(elm);
	
	//if ( $(elm).closest(".sub_menu").is(".open") )
	//	initFilterByLayout();
}
function initFilterByLayout() {
	//set scroll to where the select project is
	var selected_project_elm = $(".filter_by_layout  > ul > li.scroll li.selected");
	
	if (selected_project_elm[0])
		selected_project_elm[0].scrollIntoView({ behavior: "instant", block: "center", inline: "nearest" });
}

function filterByLayout(elm) {
	elm = $(elm);
	var filter_by_layout = elm.parent().closest(".filter_by_layout");
	var current_selected_project = filter_by_layout.attr("current_selected_project");
	var proj_id = elm.attr("value");
	var selected_admin_home_project_page_url = admin_home_project_page_url.replace("#filter_by_layout#", proj_id);
	
	if (current_selected_project == proj_id) {
		if (window.event && (window.event.ctrlKey || window.event.keyCode == 65)) {
			var win = window.open(selected_admin_home_project_page_url);
			
			if(win) //Browser has allowed it to be opened
				win.focus();
		}
		else
			goToHandler(selected_admin_home_project_page_url);
	}
	else {
		var url = ("" + document.location);
		url = url.replace(/(&?)(filter_by_layout|default_page|bean_name|bean_file_name|project)=[^&]*/g, "");
		url += (url.indexOf("?") != -1 ? "&" : "?") + "filter_by_layout=" + proj_id;
		url = url.replace(/[&]+/g, "&");
		
		//Note that if proj_id is empty, it means that the "ALL PROJECTS" was selected, and in this case we should not show the previous opened page, but the admin_home_projects.
		//save cookie with url, so when we refresh the browser, the right panel contains the latest opened url
		if (proj_id)
			MyJSLib.CookieHandler.setCurrentDomainEternalRootSafeCookie('default_page', selected_admin_home_project_page_url); 
		else
			MyJSLib.CookieHandler.setCurrentDomainEternalRootSafeCookie('default_page', admin_home_projects_page_url); 
		
		document.location = url;
	}
	
	filter_by_layout.removeClass("open");
}

//is used in the goTo function
function goToHandler(url, a, attr_name, originalEvent) {
	iframe_overlay.show();
	
	setTimeout(function() {
		try {
			//save cookie with url, so when we refresh the browser, the right panel contains the latest opened url
			MyJSLib.CookieHandler.setCurrentDomainEternalRootSafeCookie('default_page', url);
			//console.log(url);
			
			//open url in right panel
			$("#right_panel iframe")[0].src = url;
		}
		catch(e) {
			//sometimes gives an error bc of the iframe beforeunload event. This doesn't matter, but we should catch it and ignore it.
			if (console && console.log)
				console.log(e);
		}
	}, 100);
}

function goBack() {
	var iframe = $("#right_panel iframe")[0];
	var win = iframe.contentWindow;
	
	if (win) {
		//get history url and set cookie
		//TODO: find a way to get the previous url
		//MyJSLib.CookieHandler.setCurrentDomainEternalRootSafeCookie('default_page', url);
		
		//loads new page
		win.history.go(-1);
	}
}

function goForward() {
	var iframe = $("#right_panel iframe")[0];
	var win = iframe.contentWindow;
	
	if (win) {
		//get history url and set cookie
		//TODO: find a way to get the next url
		//MyJSLib.CookieHandler.setCurrentDomainEternalRootSafeCookie('default_page', url);
		
		//loads new page
		win.history.go(1);
	}
}

function refreshIframe() {
	$("#right_panel .iframe_overlay").show();
	
	var iframe = $("#right_panel iframe")[0];
	var doc = (iframe.contentWindow || iframe.contentDocument);
	doc = doc.document ? doc.document : doc;
	
	try {
		var url = "" + doc.location;
		
		if (url.indexOf("#") != -1)
			url = url.substr(0, url.indexOf("#"));
		
		iframe.src = url;
	}
	catch(e) {
		//sometimes gives an error bc of the iframe beforeunload event. This doesn't matter, but we should catch it and ignore it.
		if (console && console.log)
			console.log(e);
	}
}
