/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

var mytree = new MyTree({
	multiple_selection : true,
	toggle_children_on_click : true,
	ajax_callback_before : prepareLayerNodesForDiffFiles,
	ajax_callback_after : removeFileContentsFromTreeForVariables,
	on_select_callback : selectLayerNode,
});
var first_selection = null;
var second_selection = null;

$(function () {
	//prepaer tree
	mytree.init("file_tree");
	
	var file_tree = $("#file_tree");
	file_tree.removeClass("hidden");
	
	//prepare iframe
	var iframe = $(".files_differences iframe");
	
	iframe.load(function() {
		var iframe_body = iframe[0].contentWindow.document.body;
		iframe.css("height", (iframe_body.scrollHeight + 10) + 'px');
	});
	
	//prepare loaded first node
	if (first_node_to_load && first_node_to_load["bean_name"] && first_node_to_load["path"])
		file_tree.find(" > ul > li > a").each(function(idx, a) {
			a = $(a);
			var bean_name = a.attr("bean_name");
			var item_type = a.attr("item_type");
			var is_special_vendor = false;
			
			if (!bean_name && item_type == "vendor" && (first_node_to_load["item_type"] == "dao" || first_node_to_load["item_type"] == "test_unit")) {
				bean_name = first_node_to_load["bean_name"] = item_type;
				item_type = first_node_to_load["item_type"];
				is_special_vendor = true;
			}
			else if (!bean_name && (item_type == "vendor" || item_type == "other" || item_type == "lib"))
				bean_name = item_type;
			
			if (bean_name == first_node_to_load["bean_name"] && a.attr("bean_file_name") == first_node_to_load["bean_file_name"] && item_type == first_node_to_load["item_type"]) {
				var path_parts = ("" + first_node_to_load["path"]).replace(/\/+/g, "/").split("/");
				var path_index = 0;
				var func = function(ul, data) {
					var path_aux = path_parts.slice(0, path_index + 1).join("/");
					
					$(ul).find(" > li > a").each(function(idy, sub_a) {
						sub_a = $(sub_a);
						var sub_path = sub_a.attr("folder_path") ? sub_a.attr("folder_path") : sub_a.attr("file_path");
						sub_path = sub_path ? sub_path.replace(/\/+$/g, "") : "";
						
						if (sub_path && sub_path == path_aux) {
							path_index++;
							var sub_li = sub_a.parent();
							sub_li.addClass("jstree-open").removeClass("jstree-closed");
							
							if (path_index == path_parts.length) {
								selectLayerNode(sub_li);
								sub_a.addClass("jstree-clicked");
								
								$("html, body").animate({ scrollTop: sub_li.offset().top - 100 }, 500, 'swing');
			
							}
							else
								mytree.refreshNodeChilds(sub_li[0], {
									ajax_callback_last: func
								});
						}
					});
				};
				
				var li = a.parent();
				li.addClass("jstree-open").removeClass("jstree-closed");
				
				mytree.refreshNodeChilds(li[0], {
					ajax_callback_last: function(ul, data) {
						//for dao and test_unit files
						if (is_special_vendor) {
							var sub_a = $(ul).find(" > li > a[item_type=" + first_node_to_load["item_type"] + "]");
							var sub_li = sub_a.parent();
							
							if (sub_li[0]) {
								sub_li.addClass("jstree-open").removeClass("jstree-closed");
								
								mytree.refreshNodeChilds(sub_li[0], {
									ajax_callback_last: func
								});
							}
						}
						else 
							func(ul, data);
					}
				});
			}
		});
});

function prepareLayerNodesForDiffFiles(ul, data) {
	if (data) {
		var bean_name = data.properties && data.properties.bean_name ? data.properties.bean_name : "";
		
		if (bean_name) {
			var bean_file_name = data.properties && data.properties.bean_file_name ? data.properties.bean_file_name : "";
			var item_type = data.properties && data.properties.item_type ? data.properties.item_type : "";
			
			prepareLayerSubNodesForDiffFiles(data, bean_name, bean_file_name, item_type);
			//console.log(main_layers_properties);
		}
	}
	
	prepareLayerNodes1(ul, data);
}

function prepareLayerSubNodesForDiffFiles(data, bean_name, bean_file_name, item_type) {
	if (!main_layers_properties.hasOwnProperty(bean_name))
		main_layers_properties[bean_name] = {};
	
	if (!main_layers_properties[bean_name].hasOwnProperty("ui"))
		main_layers_properties[bean_name]["ui"] = {};
	
	var bean_get_sub_files_url = get_sub_files_url.replace(/#bean_name#/g, bean_name).replace(/#bean_file_name#/g, bean_file_name);
	var item_label = data.properties && data.properties.item_label ? data.properties.item_label : "";
	
	for (var key in data) 
		if (key != "properties" && key != "aliases") {
			var item = data[key];
			var sub_properties = item.hasOwnProperty("properties") ? item["properties"] : {};
			var sub_item_type = sub_properties.hasOwnProperty("item_type") ? sub_properties["item_type"] : "";
			var sub_folder_type = sub_properties.hasOwnProperty("folder_type") ? sub_properties["folder_type"] : "";
			var item_type_to_replace = sub_item_type == "dao" || sub_item_type == "test_unit" ? sub_item_type : item_type;
			var folder_type_to_replace = item_type == "presentation" ? sub_folder_type : "";
			
			if (!main_layers_properties[bean_name]["ui"].hasOwnProperty(sub_item_type))
				main_layers_properties[bean_name]["ui"][sub_item_type] = {};
			
			if (!main_layers_properties[bean_name]["ui"][sub_item_type].hasOwnProperty("attributes"))
				main_layers_properties[bean_name]["ui"][sub_item_type]["attributes"] = {};
			
			main_layers_properties[bean_name]["ui"][sub_item_type]["attributes"]["file_path"] = "#path#";
			main_layers_properties[bean_name]["ui"][sub_item_type]["attributes"]["bean_name"] = bean_name;
			main_layers_properties[bean_name]["ui"][sub_item_type]["attributes"]["bean_file_name"] = bean_file_name;
			main_layers_properties[bean_name]["ui"][sub_item_type]["attributes"]["item_type"] = item_type_to_replace;
			main_layers_properties[bean_name]["ui"][sub_item_type]["attributes"]["path_prefix"] = item_label ? item_label : item_type;
			
			main_layers_properties[bean_name]["ui"][sub_item_type]["get_sub_files_url"] = bean_get_sub_files_url.replace(/#item_type#/g, item_type_to_replace).replace(/#folder_type#/g, folder_type_to_replace);
			
			prepareLayerSubNodesForDiffFiles(item, bean_name, bean_file_name, item_type);
		}
	
}

function removeFileContentsFromTreeForVariables(ul, data) {
	ul = $(ul);
	ul.attr("was_already_opened", "1");
	
	var i_children = ul.find("i");
	
	$.each(i_children, function(idx, elm) {
		elm = $(elm);
		var a = elm.parent();
		var li = a.parent();
		var file_path = a.attr("file_path");
		var is_php_or_xml = file_path && ( ("" + file_path).match(/\.php([0-9]*)$/i) || ("" + file_path).match(/\.xml$/i) );
		
		if (elm.hasClass("file") || is_php_or_xml) //is a php or xml file
			li.addClass("jstree-leaf").children("ul").remove();
	});
	
	if (ul.children().length != 0) {
		var li = ul.parent();
		var a = li.children("a");
		a.attr("folder_path", a.attr("file_path"));
		a.removeAttr("file_path");
		
		//deselect node bc folders cannot be selected
		a.removeClass("jstree-clicked second_selection"); 
		
		if (li[0] == second_selection)
			second_selection = null;
		else if (li[0] == first_selection)
			first_selection = null;
		
		updateSelectedNodesInfo();
	}
}

function selectLayerNode(node) {
	node = $(node);
	var a = node.children("a");
	var ul = node.children("ul");
	
	if (a.attr("file_path")) {
		//select node
		if (!a.hasClass("jstree-clicked")) {
			if (first_selection) {
				node.children("a").addClass("second_selection");
				
				if (second_selection)
					$(second_selection).children("a").removeClass("jstree-clicked second_selection");
				
				second_selection = node[0];
			}
			else
				first_selection = node[0];
		}
		//deselect node
		else if (node[0] == second_selection)
			second_selection = null;
		else if (node[0] == first_selection)
			first_selection = null;
		
		updateSelectedNodesInfo();
		
		//check if node can be selected, this is, check if not a folder and if it is disallow selection
		if (ul[0] && ul.attr("was_already_opened") != 1) {
			node.addClass("jstree-open");
			mytree.refreshNodeChilds(node[0]); //by default it will deselect if is a folder
			node.removeClass("jstree-open");
		}
		
		return true;
	}
}

function updateSelectedNodesInfo() {
	var first_file_to_compare = "";
	var second_file_to_compare = "";
	
	if (first_selection) {
		var a = $(first_selection).children("a");
		
		if (a.attr("file_path"))
			first_file_to_compare = a.attr("path_prefix") + "/" + a.attr("file_path");
	}
		
	if (second_selection) {
		var a = $(second_selection).children("a");
		
		if (a.attr("file_path"))
			second_file_to_compare = a.attr("path_prefix") + "/" + a.attr("file_path");
	}
		
	var files_selection_info = $(".files_selection_info");
	files_selection_info.find(".first_selection_info").val(first_file_to_compare);
	files_selection_info.find(".second_selection_info").val(second_file_to_compare);
}

function diff() {
	if (first_selection && second_selection) {
		var first_a = $(first_selection).children("a");
		var second_a = $(second_selection).children("a");
		
		if (first_a.attr("file_path") && second_a.attr("file_path")) {
			var query_string = ""
				+ "&src_bean_name=" + encodeURI(first_a.attr("bean_name"))
				+ "&src_bean_file_name=" + encodeURI(first_a.attr("bean_file_name"))
				+ "&src_item_type=" + encodeURI(first_a.attr("item_type"))
				+ "&src_path=" + encodeURI(first_a.attr("file_path"))
				+ "&dst_bean_name=" + encodeURI(second_a.attr("bean_name"))
				+ "&dst_bean_file_name=" + encodeURI(second_a.attr("bean_file_name"))
				+ "&dst_item_type=" + encodeURI(second_a.attr("item_type"))
				+ "&dst_path=" + encodeURI(second_a.attr("file_path"))
			
			var files_differences = $(".files_differences")
			var iframe = files_differences.children("iframe");
			var url = iframe.attr("orig_src") + "?" + query_string;
			
			iframe.css("height", "");
			iframe.attr("src", url);
			files_differences.show();
			
			$("html, body").animate({ scrollTop: $(window).scrollTop() + 200 }, 500, 'swing');
			
			return;
		}
	}
	else
		$(".files_differences").hide();
	
	StatusMessageHandler.showError("You must select 2 files first");
}
