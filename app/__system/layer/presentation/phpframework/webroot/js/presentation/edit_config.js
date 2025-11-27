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
	$(window).bind('beforeunload', function () {
		if (isConfigCodeObjChanged()) {
			if (window.parent && window.parent.iframe_overlay)
				window.parent.iframe_overlay.hide();
			
			return "If you proceed your changes won't be saved. Do you wish to continue?";
		}
		
		return null;
	});
	
	//prepare top_bar
	$("#ui > .taskflowchart").addClass("with_top_bar_menu fixed_side_properties").children(".workflow_menu").addClass("top_bar_menu");
	
	//init auto save
	enableAutoSave(onTogglePHPCodeAutoSave);
	enableAutoConvert(onTogglePHPCodeAutoConvert);
	
	initAutoSave("#code > .code_menu li.save a");
	
	//init trees
	choosePropertyVariableFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeObjectPropertiesAndMethodsAndFunctionsFromTreeForVariables,
	});
	choosePropertyVariableFromFileManagerTree.init("choose_property_variable_from_file_manager .class_prop_var");
	
	chooseMethodFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeObjectPropertiesAndMethodsAndFunctionsFromTreeForMethods,
	});
	chooseMethodFromFileManagerTree.init("choose_method_from_file_manager");
	
	chooseFunctionFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeObjectPropertiesAndMethodsAndFunctionsFromTreeForFunctions,
	});
	chooseFunctionFromFileManagerTree.init("choose_function_from_file_manager");
	
	chooseFileFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeObjectPropertiesAndMethodsAndFunctionsFromTree,
	});
	chooseFileFromFileManagerTree.init("choose_file_from_file_manager");
	
	chooseFolderFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeAllThatIsNotFoldersFromTree,
	});
	chooseFolderFromFileManagerTree.init("choose_folder_from_file_manager");
	
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
	var config_obj = $(".config_obj");
	
	if (config_obj[0]) {
		config_obj.tabs({active: show_low_code_first ? 1 : 0});
		
		var textarea = $("#code textarea")[0];
		if (textarea) {
			var editor = createCodeEditor(textarea, {save_func: saveConfig});
			
			if (editor)
				editor.focus();
		}
		
		//load workflow
		onLoadTaskFlowChartAndCodeEditor({do_not_hide_popup : true});
		
		//init tasks flow tab
		onClickTaskWorkflowTab( config_obj.find(" > .tabs > #tasks_flow_tab > a")[0], {
			on_success: function() {
				//set saved_obj_id
				saved_obj_id = getConfigCodeObjId();
			},
			on_error: function() {
				config_obj.tabs("option", "active", 0); //show code tab
				
				//set saved_obj_id
				saved_obj_id = getConfigCodeObjId();
			}
		});
	}
	else	//hide loading icon
		MyFancyPopup.hidePopup();
});

function getConfigCodeObjId() {
	var obj = getConfigCodeObj();
	
	//remove error messages bc when we call the getCodeForSaving method, it will save try to save the workflow but it will give an error bc we are calling the isTestObjChanged on window before load, which will kill the ongoing ajax requests...
	StatusMessageHandler.removeMessages("error");
	taskFlowChartObj.StatusMessage.removeMessages("error");
	
	$(".workflow_menu").show();
	MyFancyPopup.hidePopup();
	
	return $.md5(save_object_url + JSON.stringify(obj));
}

function isConfigCodeObjChanged() {
	var config_obj = $(".config_obj");
	
	if (!config_obj[0])
		return false;
	
	return isCodeAndWorkflowObjChanged(config_obj);
}

function getConfigCodeObj() {
	var config_obj = $(".config_obj");
	
	if (!config_obj[0])
		return null;
	
	var code = getCodeForSaving(config_obj); //if tasks flow tab is selected ask user to convert workfow into code
	
	return {"code": code};
}

function saveConfig() {
	var config_obj = $(".config_obj");
	
	prepareAutoSaveVars();
	
	var is_from_auto_save_bkp = is_from_auto_save; //backup the is_from_auto_save, bc if there is a concurrent process running at the same time, this other process may change the is_from_auto_save value.
		
	if (config_obj[0]) {
		if (!window.is_save_func_running) {
			window.is_save_func_running = true;
			
			if (is_from_auto_save_bkp && (!isConfigCodeObjChanged() || isEditorCodeWithErrors()) && checkIfWorkflowDoesNotNeedToChangePreviousCodeWithErrors(config_obj)) {
				resetAutoSave();
				window.is_save_func_running = false;
				return;
			}
			
			var obj = getConfigCodeObj();
			
			//check if user is logged in
			//if there was a previous function that tried to execute an ajax request, like the getCodeForSaving method, we detect here if the user needs to login, and if yes, recall the save function again. 
			//Do not re-call only the ajax request below, otherwise there will be some other files that will not be saved, this is, the getCodeForSaving saves the workflow and if we only call the ajax request below, the workflow won't be saved. To avoid this situation, we call the all save function.
			if (!is_from_auto_save_bkp && jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL)) {
				showAjaxLoginPopup(jquery_native_xhr_object.responseURL, jquery_native_xhr_object.responseURL, function() {
					taskFlowChartObj.StatusMessage.removeLastShownMessage("error");
					StatusMessageHandler.removeLastShownMessage("error");
					
					window.is_save_func_running = false;
					saveConfig();
				});
				
				return;
			}
			
			//call saveObjCode
			saveObjCode(save_object_url, obj, {
				complete: function() {
					window.is_save_func_running = false;
				},
			});
		}
		else if (!is_from_auto_save_bkp)
			StatusMessageHandler.showMessage("There is already a saving process running. Please wait a few seconds and try again...");
	}
	else if (!is_from_auto_save_bkp)
		alert("No config object to save! Please contact the sysadmin...");
}
