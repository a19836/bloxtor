var menu_item_properties = {};
var main_layers_properties = {};
var last_selected_node_id = null;
var last_selected_node_parent_id= null;

var mytree = new MyTree({
	multiple_selection : false,
	toggle_selection : false,
	toggle_children_on_click : true,
	ajax_callback_before : prepareLayerNodes1,
	ajax_callback_after : prepareLayerNodes2,
	ajax_callback_error : validateLayerNodesRequest,
});

function prepareLayerNodes1(ul, data) {
	if (data) {
		var parent_path = data.properties && data.properties.path ? data.properties.path : "";
		
		var bean_name = data.properties && data.properties.bean_name ? data.properties.bean_name : "";
		var main_layer_properties = main_layers_properties && bean_name && main_layers_properties[bean_name] ? main_layers_properties[bean_name] : null;
		
		//console.log(main_layers_properties);
		//console.log(data);
		if (data.properties && data.properties.item_type == "db") //prepare db drivers
			data = prepareDataNodesForDBDrivers(data);
		
		prepareFolderNodes(ul, data, main_layer_properties, parent_path);
	}
}

function prepareLayerNodes2(ul, data) {
	if (data) {
		var bean_name = data.properties && data.properties.bean_name ? data.properties.bean_name : "";
		var main_layer_properties = main_layers_properties && bean_name && main_layers_properties[bean_name] ? main_layers_properties[bean_name] : null;
		
		initUlChildsContextMenu(ul, data, main_layer_properties);
		initUlChildsEvents(ul, data, main_layer_properties);
	}
}

function getPathToFilterPartsIndex(elm, stop_selector) {
	var path_to_filter_parts_idx = 0;
	elm = $(elm);
	var is_file_tree = false;
	
	do {
		elm = elm.parent().parent();
		is_file_tree = elm.is(stop_selector);
		
		if (!is_file_tree)
			path_to_filter_parts_idx++;
	}
	while (!is_file_tree);
	//console.log("path_to_filter_parts_idx:"+path_to_filter_parts_idx);
	
	return path_to_filter_parts_idx;
}

function prepareDataAccordingWithPathToFilterIndex(data, path_to_filter, path_to_filter_parts, path_to_filter_parts_idx) {
	if (path_to_filter != "" && data && path_to_filter_parts_idx >= 0 && path_to_filter_parts.length > path_to_filter_parts_idx) {
		var path_to_filter_part = path_to_filter_parts[path_to_filter_parts_idx];
		var new_data = {};
		
		if (data.hasOwnProperty("properties"))
			new_data["properties"] = data["properties"];
		
		if (data.hasOwnProperty("aliases"))
			new_data["aliases"] = data["aliases"];
		
		if (data.hasOwnProperty(path_to_filter_part)) {
			var sub_data = data[path_to_filter_part];
			
			if ($.isPlainObject(sub_data))
				sub_data = prepareDataAccordingWithPathToFilterIndex(sub_data, path_to_filter, path_to_filter_parts, path_to_filter_parts_idx + 1);
			
			new_data[path_to_filter_part] = sub_data;
		}
		
		data = new_data;
	}
	
	return data;
}

function validateLayerNodesRequest(ul, url, jqXHR, textStatus, errorThrown) {
	if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL)) {
		var url = document.location;
		document.location = url;
	}
}

function prepareDataNodesForDBDrivers(data) {
	
	if (!main_layers_properties)
		main_layers_properties = {};
	
	if (data) {
		var data_properties = null;
		
		if (data.properties) {
			var bean_name = data.properties.bean_name ? data.properties.bean_name : "";
			var main_layer_properties = bean_name && main_layers_properties[bean_name] ? main_layers_properties[bean_name] : null;
			data_properties = main_layer_properties ? main_layer_properties : data.properties;
		}
		
		for (var key in data) {
			if (key != "properties" && key != "aliases") {
				var item = data[key];
				
				var properties = item.properties;
				var item_type = properties && properties.item_type ? properties.item_type : "";
				
				var new_driver = {
					"Tables": {},
					"Views": {},
					"Procedures": {},
					"Functions": {},
					"Events": {},
					"Triggers": {},
					//"DB Diagram": {"properties": {}}, 
					"properties": {}
				};
				
				assignObjectRecursively(new_driver["Tables"], item);
				assignObjectRecursively(new_driver["Views"], item);
				assignObjectRecursively(new_driver["Procedures"], item);
				assignObjectRecursively(new_driver["Functions"], item);
				assignObjectRecursively(new_driver["Events"], item);
				assignObjectRecursively(new_driver["Triggers"], item);
				//assignObjectRecursively(new_driver["DB Diagram"]["properties"], properties);
				assignObjectRecursively(new_driver["properties"], properties);
				
				new_driver["Tables"]["properties"]["item_type"] = "db_tables";
				new_driver["Views"]["properties"]["item_type"] = "db_views";
				new_driver["Procedures"]["properties"]["item_type"] = "db_procedures";
				new_driver["Functions"]["properties"]["item_type"] = "db_functions";
				new_driver["Events"]["properties"]["item_type"] = "db_events";
				new_driver["Triggers"]["properties"]["item_type"] = "db_triggers";
				//new_driver["DB Diagram"]["properties"]["item_type"] = "db_diagram";
				
				data[key] = new_driver;
				
				if (data_properties && !main_layers_properties.hasOwnProperty(key))
					main_layers_properties[key] = data_properties;
			}
		}
	}
	
	return data;
}

function prepareFolderNodes(ul, data, main_layer_properties, parent_path) {
	for (var key in data) {
		if (key != "properties" && key != "aliases") {
			var item = data[key];
			
			if (item) {
				key = key.trim();
				
				var properties = item.properties;
				var item_type = properties && properties.item_type ? properties.item_type : "";
				var item_label = properties && properties.item_label ? properties.item_label : key;
				var item_class = properties && properties.item_class ? properties.item_class : "";
				var item_title = properties && properties.item_title ? properties.item_title : "";
				var item_id = properties && properties.item_id ? properties.item_id : false;
				var item_menu = properties && properties.item_menu ? properties.item_menu : false;
				
				var child_li = document.createElement("LI");
				var j_child_li = $(child_li);
				
				j_child_li.attr("data-jstree", '{"icon":"' + item_type.toLowerCase() + '"}');
				
				if (item_class)
					j_child_li.addClass(item_class);
				
				j_child_li.html("<a><label>" + item_label + "</label></a>");
				
				var file_path = properties && properties.path ? properties.path : "";
				if (!file_path && (item_type == "folder" || item_type == "file" || item_type == "import")) 
					file_path = parent_path + key + (item_type == "folder" ? "/" : "");
				
				var a = j_child_li.find("a");
				
				if (item_title)
					a.attr("title", item_title);
				
				var ui_props = main_layer_properties && main_layer_properties.ui && main_layer_properties.ui[item_type] ? main_layer_properties.ui[item_type] : {};
				
				var folder_type = properties && properties.folder_type ? properties.folder_type : "";
				var vendor_framework = properties && properties.vendor_framework ? properties.vendor_framework : "";
				
				if (ui_props.attributes) {
					for (var attr_name in ui_props.attributes) {
						var attr_value = ui_props.attributes[attr_name];
						attr_value = attr_value.replace("#path#", file_path);
						attr_value = attr_value.replace("#folder_type#", folder_type);
						attr_value = attr_value.replace("#vendor_framework#", vendor_framework);
						
						if (item_type == "db_driver" || item_type == "db_diagram" || item_type == "db_tables" || item_type == "db_views" || item_type == "db_procedures" || item_type == "db_functions" || item_type == "db_events" || item_type == "db_triggers") {
							var bean_name = properties && properties.bean_name ? properties.bean_name : "";
							var bean_file_name = properties && properties.bean_file_name ? properties.bean_file_name : "";
							
							attr_value = attr_value.replace("#bean_name#", bean_name);
							attr_value = attr_value.replace("#bean_file_name#", bean_file_name);
						}
						else if (item_type == "table" || item_type == "attribute") {
							var bean_name = properties && properties.bean_name ? properties.bean_name : "";
							var bean_file_name = properties && properties.bean_file_name ? properties.bean_file_name : "";
							var name = properties && properties.name ? properties.name : "";
							
							attr_value = attr_value.replace("#bean_name#", bean_name);
							attr_value = attr_value.replace("#bean_file_name#", bean_file_name);
							attr_value = attr_value.replace("#name#", name);
							attr_value = attr_value.replace("#table#", item_type == "attribute" ? properties.table : name);
							attr_value = attr_value.replace("#attribute#", name);
						}
						else if (item_type == "db_view" || item_type == "db_procedure" || item_type == "db_function" || item_type == "db_event" || item_type == "db_trigger") {
							var bean_name = properties && properties.bean_name ? properties.bean_name : "";
							var bean_file_name = properties && properties.bean_file_name ? properties.bean_file_name : "";
							var name = properties && properties.name ? properties.name : "";
							
							attr_value = attr_value.replace("#bean_name#", bean_name);
							attr_value = attr_value.replace("#bean_file_name#", bean_file_name);
							attr_value = attr_value.replace("#item_type#", item_type);
							attr_value = attr_value.replace("#object#", name);
						}
						else if (item_type == "obj" || item_type == "query" || item_type == "relationship" || item_type == "hbn_native" || item_type == "map" || item_type == "import") {
							var hbn_obj_id = properties && properties.hbn_obj_id ? properties.hbn_obj_id : "";
							hbn_obj_id = item_type == "query" || item_type == "relationship" || item_type == "hbn_native" || item_type == "map" || item_type == "import" ? hbn_obj_id : key;
							var query_type = properties && properties.query_type ? properties.query_type : "";
							var relationship_type = properties && properties.relationship_type ? properties.relationship_type : "";
							
							attr_value = attr_value.replace("#hbn_obj_id#", hbn_obj_id);
							attr_value = attr_value.replace("#query_type#", query_type);
							attr_value = attr_value.replace("#relationship_type#", relationship_type);
							attr_value = attr_value.replace("#node_id#", key);
						}
						else if (item_type == "service" || item_type == "class" || item_type == "method" || item_type == "function") {
							var service_id = properties && properties.service ? properties.service : "";
							service_id = item_type == "method" || service_id != "" ? service_id : key;
							
							var class_id = properties && properties.class ? properties.class : "";
							class_id = item_type == "method" || class_id != "" ? class_id : key;
							
							attr_value = attr_value.replace("#service#", service_id);
							attr_value = attr_value.replace("#class#", class_id);
							attr_value = attr_value.replace("#method#", key);
						}
						else if (item_type == "entities_folder" && attr_name == "project_with_auto_view")
							attr_value = properties && properties.project_with_auto_view ? properties.project_with_auto_view : "0";
						
						a.attr(attr_name, attr_value);
					}
				}
				
				var url = null;
				if (ui_props.get_sub_files_url) {
					url = ui_props.get_sub_files_url;
					url = url.replace("#path#", file_path);
					url = url.replace("#folder_type#", folder_type);
					url = url.replace("#vendor_framework#", vendor_framework);
					
					if (item_type == "db_driver" || item_type == "db_diagram" || item_type == "db_tables" || item_type == "db_views" || item_type == "db_procedures" || item_type == "db_functions" || item_type == "db_events" || item_type == "db_triggers" || item_type == "table") {
						var bean_name = properties && properties.bean_name ? properties.bean_name : "";
						var bean_file_name = properties && properties.bean_file_name ? properties.bean_file_name : "";
						
						url = url.replace("#bean_name#", bean_name);
						url = url.replace("#bean_file_name#", bean_file_name);
						url = url.replace("#item_type#", item_type);
						url = url.replace("#table#", key);
					}
					
					if (properties && properties.parse_get_sub_files_url_handler) {
						try {
							eval('var parse_get_sub_files_url_handler = ' + properties.parse_get_sub_files_url_handler + ';');
							
							if (typeof parse_get_sub_files_url_handler == "function")
								url = parse_get_sub_files_url_handler(url);
						}
						catch (e) {}
					}
				}
				
				if (item_id) //2021-12-22: This is used to ge the properties from data in other files. DO NOT REMOVE THIS
					a.attr("properties_id", item_id);
					
				if (item_menu && item_id) {
					try {
						eval("menu_item_properties." + item_id + " = item_menu;");
					} 
					catch(e) { alert(e.message ? e.message : e); }
				}
				
				ul.appendChild(child_li);
				
				var has_childs = false;
				for (var item_key in item) {
					if (item_key != "properties") {
						has_childs = true;
						break;
					}
				}
		
				if (has_childs || url) {
					var child_ul = document.createElement("UL");
					child_li.appendChild(child_ul);
					
					if (url) 
						child_ul.setAttribute("url", url);
					
					if (has_childs) 
						prepareFolderNodes(child_ul, item, main_layer_properties, file_path);
				}
			}
		}
	}
}

function initUlChildsContextMenu(ul, data, main_layer_properties) {
	ul = $(ul);
	
	if (main_layer_properties) {
		var layer_type = main_layer_properties && main_layer_properties.item_type ? main_layer_properties.item_type.toLowerCase() : "";
		
		if ((layer_type == "db" || layer_type == "dbdriver") && typeof initDBContextMenu == "function") //dbdriver is when I expand the menu "DB: xxxx" in the admin panel to see the tables inside of a db.
			initDBContextMenu(ul, data);
		else if (layer_type == "ibatis" && typeof initIbatisContextMenu == "function")
			initIbatisContextMenu(ul, data);
		else if (layer_type == "hibernate" && typeof initHibernateContextMenu == "function")
			initHibernateContextMenu(ul, data);
		else if (layer_type == "lib" && typeof initLibContextMenu == "function")
			initLibContextMenu(ul, data);
		else if (layer_type == "dao" && typeof initDaoContextMenu == "function")
			initDaoContextMenu(ul, data);
		else if (layer_type == "vendor" && typeof initVendorContextMenu == "function")
			initVendorContextMenu(ul, data);
		else if (layer_type == "test_unit" && typeof initVendorContextMenu == "function")
			initTestUnitContextMenu(ul, data);
		else if (layer_type == "other" && typeof initOtherContextMenu == "function")
			initOtherContextMenu(ul, data);
		else if (layer_type == "businesslogic" && typeof initContextContextMenu == "function")
			initContextContextMenu(ul, data);
		else if (layer_type == "presentation" && typeof initPresentationContextMenu == "function")
			initPresentationContextMenu(ul, data);
	}
	
	prepareParentChildsEventToHideContextMenu(ul);
	addSubMenuIconToParentChildsWithContextMenu(ul);
}

function initUlChildsEvents(ul, data, main_layer_properties) {
	ul = $(ul);
	
	if (main_layer_properties) {
		var item_type = data && data.properties && data.properties.item_type ? data.properties.item_type : "";
		var layer_type = main_layer_properties && main_layer_properties.item_type ? main_layer_properties.item_type.toLowerCase() : "";
		
		if ((layer_type == "db" || layer_type == "dbdriver")) {
			if (item_type == "table" && typeof initDBTableAttributesSorting == "function") //prepare db table attributes with user-friendly sorting
				initDBTableAttributesSorting(ul);
			else if (item_type == "dbdriver" && typeof initDBTablesSorting == "function") //prepare db tables with user-friendly sorting
				initDBTablesSorting(ul);
		}
		else
			initFilesDragAndDrop(ul);
	}
	
	prepareParentChildsEventOnClick(ul);
}

function prepareParentChildsEventOnClick(ul) {
	//set event on label to show again the ul that was previously open when the toggle_children_on_click get triggered
	if (mytree.options.toggle_children_on_click)
		ul.find("li > a").click(function(originalEvent) {
			var a = this;
			var li = $(a).parent();
			
			if (a.hasAttribute("onClick") && li.hasClass("jstree-open"))
				setTimeout(function() {
					li.removeClass("jstree-closed").addClass("jstree-open");
					li.children("ul").show();
				}, 210); //note that 200 is the timeout in the mytree.toggleLi method
		});
}

function prepareParentChildsEventToHideContextMenu(ul) {
	ul.find("li *:not(a > label)").click(function(originalEvent) {
		if (originalEvent.preventDefault) originalEvent.preventDefault(); 
		else originalEvent.returnValue = false;
		
		MyContextMenu.hideAllContextMenu();
	});
}
	
function addSubMenuIconToParentChildsWithContextMenu(ul) {
	var func = function(originalEvent) {
		if (originalEvent.preventDefault) originalEvent.preventDefault(); 
		else originalEvent.returnValue = false;
		
		if (originalEvent.stopPropagation) originalEvent.stopPropagation(); 
		
		var a = $(this).parent().children("a");
		triggerUlChildLinkContextMenu(a, originalEvent);
	};
	
	ul.find("li > a").each(function(idx, a) {
		a = $(a);
		var context_menu_id = a.data("context_menu_id");
		
		if (context_menu_id) {
			var menu_icon = $('<span class="icon sub_menu" title="Open context menu"></span>');
			menu_icon.click(func);
			a.after(menu_icon);
		}
	});
}

function triggerUlChildLinkContextMenu(a, originalEvent) {
	MyContextMenu.hideAllContextMenu();
	
	var context_menu_id = a.data("context_menu_id");
	
	if (context_menu_id) {
		var context_menu_elm = $("#" + context_menu_id);
		
		if (context_menu_elm[0]) {
			var context_menu_options = a.data("context_menu_options");
			
			if (context_menu_options && context_menu_options.callback && typeof context_menu_options.callback == "function") {
				var original_target = originalEvent.target;
				originalEvent.target = a.children("label")[0];
				context_menu_options.callback(a, context_menu_elm, originalEvent);
				originalEvent.target = original_target;
			}
			
			MyContextMenu.updateContextMenuPosition(context_menu_elm, originalEvent);
			MyContextMenu.showContextMenu(context_menu_elm, originalEvent);
		}
	}
}

function getNodeParentIdByNodeId(node_id) {
	var node = $("#" + node_id).first();
	
	if (node[0]) {
		var next_node = node;
		var node_id = null;
		var ul = null;
		
		do {
			node_id = next_node.attr("id");
			ul = next_node.parent();
			next_node = ul.parent();
		} 
		while (ul[0] && !ul.is("body") && !ul[0].hasAttribute("url"));
		
		return next_node.attr("id");
	}
}

function getLastNodeGrantParentId() {
	var pid = getLastNodeParentId();
	
	if (pid) {
		return getNodeParentIdByNodeId(pid);
	}
}

function getLastNodeParentId() {
	if (last_selected_node_id && $("#" + last_selected_node_id)[0]) {
		return getNodeParentIdByNodeId(last_selected_node_id);
	}
	else if (last_selected_node_parent_id) {
		return last_selected_node_parent_id;
	}
}

function refreshLastNodeParentChilds() {
	var pid = getLastNodeParentId();
	if (pid) {
		refreshNodeChildsByNodeId(pid);
	}
}

function refreshLastNodeChilds() {
	refreshNodeChildsByNodeId(last_selected_node_id);
}

function refreshAndShowLastNodeChilds() {
	refreshAndShowNodeChildsByNodeId(last_selected_node_id);
}

function refreshNodeChildsByNodeId(node_id) {
	mytree.refreshNodeChildsByNodeId(node_id);
}

function refreshAndShowNodeChilds(node) {
	node.removeClass("jstree-closed").addClass("jstree-open"); //add in case it doesn't have, so we can then show the inner folder that was created.
	mytree.refreshNodeChilds(node);
	
	node.children("ul").show();
}

function refreshAndShowNodeChildsByNodeId(node_id) {
	if (node_id) {
		var node = $("#" + node_id);
		
		if (node) {
			refreshAndShowNodeChilds(node);
		}
	}
}

function refreshNodeParentChildsByChildId(node_id) {
	if (node_id) {
		var node = $("#" + node_id);
		
		if (node) {
			var parent_li = node.parent().parent();
			mytree.refreshNodeChilds(parent_li);
		}
	}
}

function refreshOpenNodeChildsBasedInIconClass(class_name) {
	if (class_name && mytree && mytree.tree_elm) {
		var item = mytree.tree_elm.find(".jstree-node > .jstree-anchor > .jstree-icon." + class_name);
		var node = item.parent().closest(".jstree-node");
		mytree.refreshNodeChilds(node);
	}
}

function refreshAndShowNodeChildsBasedInIconClass(class_name) {
	if (class_name && mytree && mytree.tree_elm) {
		var item = mytree.tree_elm.find(".jstree-node > .jstree-anchor > .jstree-icon." + class_name);
		var node = item.parent().closest(".jstree-node");
		refreshAndShowNodeChilds(node);
	}
}

function refreshOpenNodeChildsBasedInPath(path) {
	if (path && mytree && mytree.tree_elm) {
		var items = mytree.tree_elm.find(".jstree-node.jstree-open > ul[url]");
		
		if (path.substr(path.length - 1) != "/")
			path += "/";
		
		var regex = new RegExp("(&|\\?)path=" + path.replace(/\//g, "\\/") + "(&|$)");
		
		$.each(items, function(idx, item) {
			var url = item.getAttribute("url");
			
			if (url && url.match(regex)) {
				var node = $(item).parent();
				mytree.refreshNodeChilds(node);
				
				return false; //exit loop
			}
		});
	}
}

function refreshAndShowNodeChildsBasedInPath(path) {
	if (path && mytree && mytree.tree_elm) {
		var items = mytree.tree_elm.find(".jstree-node > ul[url]");
		
		if (path.substr(path.length - 1) != "/")
			path += "/";
		
		var regex = new RegExp("(&|\\?)path=" + path.replace(/\//g, "\\/") + "(&|$)");
		
		$.each(items, function(idx, item) {
			var url = item.getAttribute("url");
			
			if (url && url.match(regex)) {
				var node = $(item).parent();
				refreshAndShowNodeChilds(node)
				
				return false; //exit loop
			}
		});
	}
}
