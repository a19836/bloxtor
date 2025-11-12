/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

var classesTree = null;
var saved_class_obj_id = null;

$(function () {
	$(window).bind('beforeunload', function () {
		if (isClassObjChanged()) {
			if (window.parent && window.parent.iframe_overlay)
				window.parent.iframe_overlay.hide();
			
			return "If you proceed your changes won't be saved. Do you wish to continue?";
		}
		
		return null;
	});
	
	//init auto save
	/* No need for this bc this file includes the edit_file_includes.js which already does this code.
	addAutoSaveMenu(".top_bar li.sub_menu li.save");
	enableAutoSave(onToggleAutoSave);
	initAutoSave(".top_bar li.sub_menu li.save a");*/
	
	//init trees
	classesTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeNonPHPClassFilesFromTree,
	});
	classesTree.init("choose_php_class_from_file_manager");
	
	//set saved_class_obj_id
	saved_class_obj_id = getFileClassObjId();
});

function removeNonPHPClassFilesFromTree(ul, data) {
	ul = $(ul);
	
	ul.find("i.function, i.entity_file, i.view_file, i.template_file, i.controller_file, i.config_file, i.undefined_file, i.js_file, i.css_file, i.img_file, i.block_file, i.cache_file, .entities_folder, .views_folder, .templates_folder, .template_folder, .webroot_folder, .blocks_folder, .configs_folder, .controllers_folder, .caches_folder").each(function(idx, elm){
		$(elm).parent().parent().remove();
	});
	
	ul.find("i.file").each(function(idx, elm){
		elm = $(elm);
		var a = elm.parent();
		var li = a.parent();
		var file_path = a.attr("file_path");
		
		if (!file_path || !("" + file_path).match(/\.php([0-9]*)$/i)) //is not a php file
			li.remove();
		else 
			li.find(" > ul > li > a > i").not("i.service, i.class, i.test_unit_obj").parent().parent().remove();
	});
	
	ul.find("i.service, i.class, i.test_unit_obj").each(function(idx, elm){
		$(elm).parent().parent().children("ul").remove();
	});
}

function getClassFromFileManager(elm, selector) {
	MyFancyPopup.init({
		elementToShow: $("#choose_php_class_from_file_manager"),
		parentElement: document,
		
		targetField: $(elm).parent().find(selector)[0],
		updateFunction: updateClassFieldFromFileManager
		
	});
	
	MyFancyPopup.showPopup();
}

function updateClassFieldFromFileManager(elm) {
	var node = classesTree.getSelectedNodes();
	node = node[0];
	
	var file_path = null, is_php_file = false, is_php_file = false, file_name = false;
	
	if (node) {
		var a = $(node).children("a");
		
		if (a) {
			file_path = a.attr("file_path");
			is_php_obj = a.children("i").is(".service, .class");
			is_php_file = file_path && file_path.substr(file_path.length - 4) == ".php";
			file_name = a.children("label").text();
			
			if (is_php_file && file_name) {
				if (!is_php_obj && confirm("We couldn't detect if this php file contains the class '" + file_name + "'. Do you still wish to proceed?")) {
					is_php_obj = true;
					file_name = file_name.substr(0, file_name.length - 4); //remove the php extension
				}
			}
			else
				is_php_obj = false;
		}
	}
	
	if (is_php_obj) {
		$(MyFancyPopup.settings.targetField).val(file_name ? file_name.trim() : "");
		$(MyFancyPopup.settings.targetField).parent().children("select").val("string");
		
		//prepare include file
		var exists_include = false;
		var include_paths = $(".file_class_obj .includes .include .include_path");
		for (var i = 0, l = include_paths.length; i < l; i++)
			if ($(include_paths[i]).val() == file_path) {
				exists_include = true;
				break;
			}
		
		if (!exists_include) {
			var inc = addNewInclude( $(".file_class_obj .includes .add")[0] );
			if (inc) {
				var bean_name = a.attr("bean_name");
				file_path = getNodeIncludePath(node, file_path, bean_name);
				
				inc.children(".include_path").val(file_path);
				inc.children(".include_type").val("");
				inc.children(".include_once").attr("checked", "checked").prop("checked", true);
			}
		}
		
		MyFancyPopup.hidePopup();
	}
	else
		alert("Invalid File selection.\nPlease choose a php file and then click the button.");
}

function addNewProperty(elm) {
	var html_obj = $(new_property_html);
	$(elm).parent().closest(".properties").find("table .fields").append(html_obj);
	
	return html_obj;
}

function replaceNewNameInUrl(obj, new_class_id, data, options) {
	var is_id_different = original_class_id != new_class_id;
	
	//If id is different, reload page with right id
	if (is_id_different) {
		if (window.parent && typeof window.parent.refreshLastNodeParentChilds == "function")
			window.parent.refreshLastNodeParentChilds();
		
		var attr_name = typeof options == "object" && options["class_url_attr_name"] ? options["class_url_attr_name"] : "class";
		var url = "" + document.location;
		var status = true;
		
		//replace class name in url
		var regex = new RegExp("(&|\\?)" + attr_name + "=[^&]*", "g");
		var m = url.match(regex);
		
		if (m && m.length > 0) //if edit file class
			url = url.replace(regex, "$1" + attr_name + "=" + new_class_id);
		else //if add new file class
			url += "&" + attr_name + "=" + new_class_id;
		
		//replace file name in url
		var original_obj_name = original_class_id.indexOf("\\") != -1 ? original_class_id.substr(original_class_id.lastIndexOf("\\") + 1) : original_class_id;
		
		if (url.indexOf("/" + original_obj_name + ".php") != -1) //if edit file class
			url = url.replace("/" + original_obj_name + ".php", "/" + obj["name"] + ".php");
		else { //if add new file class
			var regex = new RegExp("(&|\\?)path=[^&]*", "g");
			var m = url.match(regex);
			
			if (m && m.length > 0) {
				var str = m[0];
				
				if (str.substr(str.length - 1) == "/" || str.substr(str.length - 4).toLowerCase() != ".php") {
					var pos = url.indexOf(str) + str.length;
					url = url.substr(0, pos) + (url.charAt(pos - 1) == "/" ? "" : "/") + obj["name"] + ".php" + url.substr(pos, url.length - pos);
				}
			}
			else
				status = false;
		}
		
		if (status)
			document.location = url;
		//else
		//	StatusMessageHandler.showMessage("Could not redirect page to edit panel. Please do it manually...");
	}
}

function getFileClassObjId() {
	var obj = getFileClassObj();
	return $.md5(JSON.stringify(obj));
}

function isClassObjChanged() {
	var new_class_obj_id = getFileClassObjId();
	
	return saved_class_obj_id != new_class_obj_id;
}

function getFileClassObj() {
	var class_elm = $(".file_class_obj");
	
	var extends_value = class_elm.children(".extend").children("input").val();
	extends_value = extends_value ? extends_value.split(",") : [];
	
	var implements_value = class_elm.children(".implement").children("input").val();
	implements_value = implements_value ? implements_value.split(",") : [];
	
	var properties = [];
	var items = class_elm.children(".properties").find(".fields .property");
	for (var i = 0; i < items.length; i++) {
		var item = $(items[i]);
		
		var name = item.find(".name input").val();
		var value = item.find(".value input").val();
		var type = item.find(".type select").val();
		var is_static = item.find(".static input").prop("checked") ? 1 : 0;
		var var_type = item.find(".var_type select").val();
		var comments = item.find(".comments input").val();
		
		properties.push({
			"name": name,
			"value": value,
			"type": type,
			"static": is_static,
			"var_type": var_type,
			"comments": comments,
		});
	}
	
	var object = getIncludesObj();
	var obj = {
		"name": class_elm.children(".name").children("input").val(),
		"hidden": class_elm.children(".visibility").children("input").prop("checked") ? 0 : 1, //if checked the hidden is 0, bc this field if is checked means it is visible. So we need to do the opposite.
		"extends": extends_value,
		"implements": implements_value,
		"abstract": class_elm.children(".abstract").children("input").prop("checked") ? 1 : 0,
		"namespace": object["namespaces"] && object["namespaces"].length > 0 ? object["namespaces"][0] : "",
		"uses": object["uses"],
		"includes": object["includes"],
		"properties": properties,
		"comments": class_elm.children(".comments").children("textarea").val(),
	};
	
	return obj;
}

function saveFileClass(options) {
	prepareAutoSaveVars();
	
	var new_class_obj_id = getFileClassObjId();
	
	//only saves if object is different
	if (!saved_class_obj_id || saved_class_obj_id != new_class_obj_id) {
		var obj = getFileClassObj();
		var url = save_object_url.replace("#class_id#", original_class_id);
		
		options = typeof options == "object" ? options : {};
		
		$.ajax({
			type : "post",
			url : url,
			data : {"object" : obj},
			dataType : "json",
			success : function(data, textStatus, jqXHR) {
				if (data == 1 || ($.isPlainObject(data) && data["status"] == 1)) {
					//update saved_class_obj_id
					saved_class_obj_id = new_class_obj_id;
					
					var namespace = obj["namespace"] ? obj["namespace"].replace(/^[ \n\r]+/g, "").replace(/[ \n\r]+$/g, "") : "";
					var new_class_id = (namespace ? (namespace.substr(0, 1) == "\\" ? "" : "\\") + namespace + "\\" : "") + obj["name"];
					
					if (typeof options["on_success"] == "function")
						options["on_success"](obj, new_class_id, data, options);
					
					if (original_class_id != new_class_id) {
						//prepare file name if namespace exists
						var original_obj_name = original_class_id.indexOf("\\") != -1 ? original_class_id.substr(original_class_id.lastIndexOf("\\") + 1) : original_class_id;
						
						save_object_url = save_object_url.replace("/" + original_obj_name + ".php", "/" + obj["name"] + ".php");
						original_class_id = new_class_id;
						
						if (window.parent && typeof window.parent.refreshLastNodeParentChilds == "function")
							window.parent.refreshLastNodeParentChilds();
					}
					
					if (!is_from_auto_save)
						StatusMessageHandler.showMessage("Saved successfully.", "", "bottom_messages");
					else
						resetAutoSave();
				}
				else if (!is_from_auto_save)
					StatusMessageHandler.showError("Error trying to save new changes.\nPlease try again..." + (data && !$.isPlainObject(data) ? "\n" + data : ""));
				else
					resetAutoSave();
			},
			error : function(jqXHR, textStatus, errorThrown) { 
				if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
					showAjaxLoginPopup(jquery_native_xhr_object.responseURL, url, function() {
						StatusMessageHandler.removeLastShownMessage("error");
						saveFileClass();
					});
				else if (!is_from_auto_save) {
					var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
					StatusMessageHandler.showError("Error trying to save new changes.\nPlease try again..." + msg);
				}
				else
					resetAutoSave();
			},
			timeout : is_from_auto_save && auto_save_connection_ttl ? auto_save_connection_ttl : 0,
		});
	}
	else if (!is_from_auto_save) {
		StatusMessageHandler.showMessage("Nothing to save.", "", "bottom_messages");
	}
	else
		resetAutoSave();
}
