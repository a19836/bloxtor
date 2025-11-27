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

$(function () {
	if (is_obj_valid) {
		hideOrShowIsBusinessLogicService( $(".top_bar .title > select.is_business_logic_service")[0] );
		
		$("#ui .taskflowchart .workflow_menu > ul.dropdown, #code .code_menu > ul").find("li.toggle_main_settings").after(toggle_advanced_settings_html);
		
		//set saved_class_method_settings_id
		saved_class_method_settings_id = getFileClassMethodSettingsId();
	}
});

function toggleBLAdvancedSettings(elm) {
	elm = $(elm);
	var advanced_settings = $(".advanced_settings");
	var menu = $("#ui .taskflowchart .workflow_menu > ul.dropdown, #code .code_menu > ul").find("li.toggle_advanced_settings");
	var input = menu.find("input");
	var span = menu.find("span");
	var is_shown = advanced_settings.css("display") != "none";
	
	if (is_shown) {
		input.removeAttr("checked").prop("checked", false);
		span.html("Show Advanced Settings");
		advanced_settings.hide();
	}
	else {
		input.attr("checked", "checked").prop("checked", true);
		span.html("Hide Advanced Settings");
		advanced_settings.show();
	}
}

function hideOrShowIsBusinessLogicService(elm) {
	var is_business_logic_service = $(elm).val();
	
	var settings = $(".file_class_method_obj #settings");
	var arguments_tbody = settings.find(".arguments .fields");
	var arguments_rows = arguments_tbody.children(".argument");
	var annotations_tbody = settings.find(".annotations .fields");
	var annotations_rows = annotations_tbody.children(".annotation");
	
	if (is_business_logic_service == 1) {
		settings.children(".type, .abstract, .static, .arguments").hide();
		
		//remove argument from ProgrammingTaskUtil.variables_in_workflow
		for (var i = 0; i < arguments_rows.length; i++)
			removeArgument( $(arguments_rows[i]).find(".icon.delete")[0] );
		
		//add default $data variable to ProgrammingTaskUtil.variables_in_workflow
		ProgrammingTaskUtil.variables_in_workflow["$data"] = {};
		
		//add annotations to ProgrammingTaskUtil.variables_in_workflow
		for (var i = 0; i < annotations_rows.length; i++) {
			var input = $(annotations_rows[i]).find(".name input");
			var name = input.val();
			name = ("" + name).replace(/^&?\$?/g, "");
			
			delete ProgrammingTaskUtil.variables_in_workflow["$" + name];
			
			//add annotation as normal argument
			onBlurAnnotationName(input[0]);
		}
	}
	else {
		//remove default $data variable from ProgrammingTaskUtil.variables_in_workflow
		delete ProgrammingTaskUtil.variables_in_workflow["$data"];
		
		//remove method annotations variables from ProgrammingTaskUtil.variables_in_workflow
		for (var i = 0; i < annotations_rows.length; i++) {
			var input = $(annotations_rows[i]).find(".name input");
			var name = input.val();
			name = ("" + name).replace(/^&?\$?/g, "");
			
			delete ProgrammingTaskUtil.variables_in_workflow["$data[\"" + name + "\"]"];
			delete ProgrammingTaskUtil.variables_in_workflow["$data['" + name + "']"];
			
			//add annotation as normal argument
			onBlurAnnotationName(input[0]);
		}
		
		if (arguments_rows.length == 0) {
			var item = addNewArgument( arguments_tbody.parent().find("thead .add")[0] );
			//item.find(".name input").val("data");
		}
		else //add argument to ProgrammingTaskUtil.variables_in_workflow
			for (var i = 0; i < arguments_rows.length; i++)
				onBlurArgumentName( $(arguments_rows[i]).find(".name input")[0] );
		
		if (settings.hasClass("function_settings")) {
			settings.children(".arguments").show();
			settings.children(".type, .abstract, .static").hide();
		}
		else
			settings.children(".type, .abstract, .static, .arguments").show();
	}
}

function prepareFileClassMethodSettingsObj(obj) {
	obj["is_business_logic_service"] = $(".top_bar .title > select.is_business_logic_service").val();
}
