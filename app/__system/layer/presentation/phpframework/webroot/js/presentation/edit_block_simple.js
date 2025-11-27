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

var ModuleAdminPanelFancyPopup = new MyFancyPopupClass();

$(function () {
	$(window).bind('beforeunload', function () {
		if (isBlockCodeObjChanged()) {
			if (window.parent && window.parent.iframe_overlay)
				window.parent.iframe_overlay.hide();
			
			return "If you proceed your changes won't be saved. Do you wish to continue?";
		}
		
		return null;
	});
	
	//init auto save
	addAutoSaveMenu(".top_bar li.sub_menu li.save");
	enableAutoSave(onToggleAutoSave);
	initAutoSave(".top_bar li.sub_menu li.save a");
	
	//init trees
	choosePageUrlFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeAllThatIsNotPagesFromTree,
	});
	choosePageUrlFromFileManagerTree.init("choose_page_url_from_file_manager");
	
	chooseImageUrlFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeAllThatIsNotAPossibleImageFromTree,
	});
	chooseImageUrlFromFileManagerTree.init("choose_image_url_from_file_manager");
	
	chooseWebrootFileUrlFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeAllThatIsNotWebrootFileFromTree,
	});
	chooseWebrootFileUrlFromFileManagerTree.init("choose_webroot_file_url_from_file_manager");
	
	//init ui
	var block_obj = $(".block_obj");
	
	if (block_obj[0]) {
		if (load_module_settings_function && typeof load_module_settings_function == "function")
			load_module_settings_function(block_obj.find(".module_settings .settings"), block_settings_obj);
		
		if (typeof block_join_points_settings_objs != "undefined") {
			var join_points_elms = $(".module_join_points > .join_points > .join_point");
			onLoadBlockJoinPoints(join_points_elms, block_join_points_settings_objs, available_block_local_join_point);
		}
		
		//set saved_obj_id
		saved_obj_id = getBlockCodeObjId();
	}
	
	MyFancyPopup.hidePopup();
});

function showOrHideModuleData(elm) {
	elm = $(elm);
	var module_data = $(".block_obj > .module_data");
	var p = elm.parent();
	var input = p.find("input");
	var span = p.find("span");
	
	if (module_data[0]) {
		if (module_data.css("display") == "none") {//show
			module_data.slideDown("slow");
			elm.addClass("active");
			input.attr("checked", "checked").prop("checked", true);
			span.html("Hide Module Info");
		}
		else {//hide
			module_data.slideUp("slow");
			elm.removeClass("active");
			input.removeAttr("checked").prop("checked", false);
			span.html("Show Module Info");
		}
	}
}


function openModuleAdminPanelPopup() {
	var popup = $(".module_admin_panel_popup");
	
	if (!popup[0]) {
		var url = module_admin_panel_url + (module_admin_panel_url.indexOf("?") != -1 ? "&": "?") + "popup=1";
		
		popup = $('<div class="myfancypopup' + (is_popup ? " in_popup" : "") + ' module_admin_panel_popup with_iframe_title"><iframe src="' + url + '"></iframe></div>');
		$(document.body).append(popup);
	}
	
	ModuleAdminPanelFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
	});
	ModuleAdminPanelFancyPopup.showPopup();
}

/* SAVING FUNCTIONS */

function getBlockCodeObjId() {
	var obj = getBlockCodeObj();
	
	return $.md5(save_object_url + JSON.stringify(obj));
}

function isBlockCodeObjChanged() {
	var block_obj = $(".block_obj");
	
	if (!block_obj[0])
		return false;
	
	var new_saved_obj_id = getBlockCodeObjId();
	
	return saved_obj_id != new_saved_obj_id;
}

function getBlockCodeObj() {
	var block_obj = $(".block_obj");
	var module_id = block_obj.children(".module_data").children("input").val();
	var join_points_elm = block_obj.find(".module_join_points .join_points");
	var joint_points = getBlockJoinPointsObjForSaving(join_points_elm, "module_join_points_property");
	
	var obj = {
		"module_id": module_id,
		"join_points": joint_points,
	};
	
	var settings_elm = block_obj.find(".module_settings .settings");
	
	if (settings_elm[0])
		obj["settings"] = getBlockSettingsObjForSaving(settings_elm);
	
	return obj;
}

function saveBlock(opts) {
	var obj = getBlockCodeObj(); //obj includes settings
	
	return saveBlockObj(obj, opts);
}

function saveBlockRawCode(code, opts) {
	var obj = getBlockCodeObj();
	delete obj["settings"]; //obj does NOT include settings
	obj["code"] = code;
	
	return saveBlockObj(obj, opts);
}

function saveBlockObj(obj, opts) {
	opts = opts ? opts : {};
	
	var block_obj = $(".block_obj");
	var status = false;
	
	prepareAutoSaveVars();
	
	var is_from_auto_save_bkp = is_from_auto_save; //backup the is_from_auto_save, bc if there is a concurrent process running at the same time, this other process may change the is_from_auto_save value.
	
	if (block_obj[0]) {
		var success_func = opts.success;
		opts.success = function(data, textStatus, jqXHR) {
			var func_status = true;
			
			var json_data = data && ("" + data).substr(0, 1) == "{" ? JSON.parse(data) : null;
			status = parseInt(data) == 1 || ($.isPlainObject(json_data) && json_data["status"] == 1);
			
			if (typeof success_func == "function")
				func_status = success_func(data, textStatus, jqXHR);
			
			return func_status;
		};
		opts.async = false;
		
		saveObj(save_object_url, obj, opts);
	}
	else {
		if (typeof opts.complete == "function")
			opts.complete();
		
		if (!is_from_auto_save_bkp)
			alert("No block object to save! Please contact the sysadmin...");
		else
			resetAutoSave();
	}
	
	return status;
}
