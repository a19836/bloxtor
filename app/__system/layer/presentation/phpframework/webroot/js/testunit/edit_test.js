var saved_test_settings_id = null;

$(function () {
	if (is_obj_valid) {
		$(window).bind('beforeunload', function () {
			if (isTestObjChanged()) {
				if (window.parent && window.parent.iframe_overlay)
					window.parent.iframe_overlay.hide();
				
				return "If you proceed your changes won't be saved. Do you wish to continue?";
			}
			
			return null;
		});
		
		//prepare top_bar
		$("#ui > .taskflowchart").addClass("with_top_bar_menu fixed_side_properties").children(".workflow_menu").addClass("top_bar_menu");
		
		$("#code > .code_menu > ul, #ui > .taskflowchart > .workflow_menu > ul").prepend('<li class="toggle_main_settings" title="Toggle Main Settings"><a onClick="toggleSettingsPanel(this)"><i class="icon toggle_main_settings"></i> <span>Show Main Settings</span> <input type="checkbox"/></a></li><li class="separator"></li>');
		
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
		
		chooseBusinessLogicFromFileManagerTree = new MyTree({
			multiple_selection : false,
			toggle_selection : false,
			toggle_children_on_click : true,
			ajax_callback_before : prepareLayerNodes1,
			ajax_callback_after : removeObjectPropertiesAndMethodsAndFunctionsFromTreeForBusinessLogic,
		});
		chooseBusinessLogicFromFileManagerTree.init("choose_business_logic_from_file_manager");
		
		chooseQueryFromFileManagerTree = new MyTree({
			multiple_selection : false,
			toggle_selection : false,
			toggle_children_on_click : true,
			ajax_callback_before : prepareLayerNodes1,
			ajax_callback_after : removeMapsAndOtherIbatisNodesFromTree,
		});
		chooseQueryFromFileManagerTree.init("choose_query_from_file_manager");
		
		chooseHibernateObjectFromFileManagerTree = new MyTree({
			multiple_selection : false,
			toggle_selection : false,
			toggle_children_on_click : true,
			ajax_callback_before : prepareLayerNodes1,
			ajax_callback_after : removeQueriesAndMapsAndOtherHbnNodesFromTree,
		});
		chooseHibernateObjectFromFileManagerTree.init("choose_hibernate_object_from_file_manager");
		
		chooseHibernateObjectMethodFromFileManagerTree = new MyTree({
			multiple_selection : false,
			toggle_selection : false,
			toggle_children_on_click : true,
			ajax_callback_before : prepareLayerNodes1,
			ajax_callback_after : removeMapsAndOtherHbnNodesFromTree,
		});
		chooseHibernateObjectMethodFromFileManagerTree.init("choose_hibernate_object_method_from_file_manager");
		
		choosePresentationFromFileManagerTree = new MyTree({
			multiple_selection : false,
			toggle_selection : false,
			toggle_children_on_click : true,
			ajax_callback_before : prepareLayerNodes1,
			ajax_callback_after : removeAllThatIsNotPresentationPagesFromTree,
		});
		choosePresentationFromFileManagerTree.init("choose_presentation_from_file_manager");
		
		chooseBlockFromFileManagerTree = new MyTree({
			multiple_selection : false,
			toggle_selection : false,
			toggle_children_on_click : true,
			ajax_callback_before : prepareLayerNodes1,
			ajax_callback_after : removeAllThatIsNotBlocksFromTree,
		});
		chooseBlockFromFileManagerTree.init("choose_block_from_file_manager");
		
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
		
		//init edit_test
		var edit_test = $(".edit_test");
		
		if (edit_test[0]) {
			edit_test.tabs({active: show_low_code_first ? 1 : 0});
			
			var textarea = $("#code textarea")[0];
			if (textarea) {
				var editor = createCodeEditor(textarea, {save_func: saveTest});
				
				if (editor)
					editor.focus();
			}
			
			onLoadTaskFlowChartAndCodeEditor({do_not_hide_popup : true});
			
			//init tasks flow tab
			onClickTaskWorkflowTab( edit_test.find(" > .tabs > #tasks_flow_tab > a")[0], {
				on_success: function() {
					//set saved_test_settings_id
					saved_test_settings_id = getTestSettingsId();
					
					MyFancyPopup.hidePopup();
				},
				on_error: function() {
					edit_test.tabs("option", "active", 0); //show code tab
					
					//set saved_test_settings_id
					saved_test_settings_id = getTestSettingsId();
					
					MyFancyPopup.hidePopup();
				}
			});
			
			//init settings
			var settings = edit_test.find("#settings");
			
			settings.draggable({
				axis: "y",
				appendTo: 'body',
				cursor: 'move',
		          tolerance: 'pointer',
		          handle: ' > .settings_header',
		    		cancel: '.icon', //this means that is inside of .settings_header
				start: function(event, ui) {
					settings.addClass("resizing").removeClass("collapsed");
					settings.find(" > .settings_header .icon").addClass("minimize").removeClass("maximize");
					
					return true;
				},
				drag: function(event, ui) {
					var h = $(window).height() - (ui.offset.top - $(window).scrollTop());
					
					settings.css({
						height: h + "px",
						top: "", 
						left: "", 
						bottom: ""
					});
				},
				stop: function(event, ui) {
					var top = parseInt(ui.helper.css("top"));//Do not use ui.offset.top bc if the window has scrollbar, it will get the wrong top for the calculations inside of resizeSettingsPanel
					resizeSettingsPanel(settings, top);
				},
			});
		}
		else	//hide loading icon
			MyFancyPopup.hidePopup();
	}
	else	//hide loading icon
		MyFancyPopup.hidePopup();
});

//To be used in the toggleFullScreen function
function onToggleFullScreen(in_full_screen) {
	setTimeout(function() {
		var settings = $(".edit_test #settings");
		var top = parseInt(settings.css("top"));
		
		resizeSettingsPanel(settings, top);
	}, 500);
}

function resizeSettingsPanel(settings, top) {
	var icon = settings.find(" > .settings_header .icon");
	var wh = $(window).height();
	var top_bar_height = $(".top_bar header").outerHeight();
	var height = 0;
	
	settings.removeClass("resizing");
	settings.css({top: "", left: "", bottom: ""}); //remove top, left and bottom from style attribute in #settings_header
	
	if (top < top_bar_height) {
		height = wh - (top_bar_height + 5); //5 is the height of the #settings_header resize bar
		
		settings.css("height", height + "px");
	}
	else if (top > wh - 35) { //35 is the size of #settings .settings_header when collapsed
		icon.addClass("maximize").removeClass("minimize");
		settings.addClass("collapsed");
		
		settings.css("height", ""); //remove height from style attribute in #settings
	}
	else
		settings.css("height", (wh - top) + "px");
}

function toggleSettingsPanel(elm) {
	var settings = $("#settings");
	var lis = $("#code > .code_menu > ul li.toggle_main_settings, #ui > .taskflowchart > .workflow_menu > ul li.toggle_main_settings");
	var inputs = lis.find("input");
	var spans = lis.find("span");
	var icon = settings.find(" > .settings_header > .icon").filter(".maximize, .minimize");
	
	icon.toggleClass("maximize").toggleClass("minimize");
	settings.toggleClass("collapsed");
	
	if (settings.hasClass("collapsed")) {
		inputs.removeAttr("checked").prop("checked", false);
		spans.html("Show Main Settings");
	}
	else {
		inputs.attr("checked", "checked").prop("checked", true);
		spans.html("Hide Main Settings");
	}
}

function addNewGlobalVariableFilePath(elm) {
	var html_obj = $(new_global_variables_file_path_html);
	$(elm).closest(".global_variables_files_path").find("table .fields").append(html_obj);
	
	return html_obj;
}

function addNewAnnotation(elm) {
	var html_obj = $(new_annotation_html);
	$(elm).closest(".annotations").find("table .fields").append(html_obj);
	
	return html_obj;
}

function initLayoutUIEditorWidgetResourceOptionsInEditTest(PtlLayoutUIEditor) {
	initLayoutUIEditorWidgetResourceOptions(PtlLayoutUIEditor);
	
	PtlLayoutUIEditor.LayoutUIEditorWidgetResource.options.get_db_tables_func = function(db_broker, db_driver, db_type) {
		var aux = get_broker_db_data_url;
		var bean_name = null;
		var bean_file_name = null;
		
		if (db_driver_brokers)
			$.each(db_driver_brokers, function(idx, db_driver_broker) {
				if (db_driver == db_driver_broker[0]) {
					bean_name = db_driver_broker[2];
					bean_file_name = db_driver_broker[1];
					return false;
				}
			});
		
		get_broker_db_data_url = aux.replace(/#bean_name#/g, bean_name).replace(/#bean_file_name#/g, bean_file_name);
		
		var result = getLayoutUIEditorWidgetResourceDBTables(db_broker, db_driver, db_type);
		
		get_broker_db_data_url = aux;
		
		return result;
	};
	
	PtlLayoutUIEditor.LayoutUIEditorWidgetResource.options.get_db_attributes_func = function(db_broker, db_driver, db_type, db_table) {
		var aux = get_broker_db_data_url;
		var bean_name = null;
		var bean_file_name = null;
		
		if (db_driver_brokers)
			$.each(db_driver_brokers, function(idx, db_driver_broker) {
				if (db_driver == db_driver_broker[0]) {
					bean_name = db_driver_broker[2];
					bean_file_name = db_driver_broker[1];
					return false;
				}
			});
		
		get_broker_db_data_url = aux.replace(/#bean_name#/g, bean_name).replace(/#bean_file_name#/g, bean_file_name);
		
		var result = getLayoutUIEditorWidgetResourceDBAttributes(db_broker, db_driver, db_type, db_table);
		
		get_broker_db_data_url = aux;
		
		return result;
	};
}

function getTestSettingsId() {
	var obj_settings = getTestSettings();
	
	return $.md5(JSON.stringify(obj_settings));
}

function isTestObjChanged() {
	var new_test_settings_id = getTestSettingsId();
	
	var code = getEditorCodeRawValue();
	var new_code_id = $.md5(code);
	var old_code_id = $("#ui").attr("code_id");
	
	var old_workflow_id = $("#ui").attr("workflow_id");
	var new_workflow_id = getCurrentWorkFlowId();
	
	var selected_tab = $(".edit_test > ul").find("li.ui-tabs-selected, li.ui-tabs-active").first();
	
	return saved_test_settings_id != new_test_settings_id
		|| old_workflow_id != new_workflow_id
		|| old_code_id != new_code_id;
		(selected_tab.attr("id") == "tasks_flow_tab" && taskFlowChartObj.TaskFile.isWorkFlowChangedFromLastSaving()); //compares if tasks' sizes and offsets are different, but only if workflow tab is selected.
}

function getTestObj() {
	var obj = getTestSettings();
	obj["code"] = getCodeForSaving($(".edit_test"), {strip_php_tags: true}); //if tasks flow tab is selected ask user to convert workfow into code
	
	return obj;
}

function getTestSettings() {
	var edit_test = $(".edit_test");
	var settings_elm = edit_test.children("#settings");
	
	var global_variables_files_path = [];
	var items = settings_elm.children(".global_variables_files_path").find(".fields .global_variables_file_path");
	for (var i = 0; i < items.length; i++) {
		var item = $(items[i]);
	
		var path = item.find(".path input").val();
	
		global_variables_files_path.push({
			"path": path,
		});
	}
	
	var annotations = {};
	var items = settings_elm.children(".annotations").find(".fields .annotation");
	for (var i = 0; i < items.length; i++) {
		var item = $(items[i]);
		
		var annotation_type = item.find(".annotation_type select").val();
		
		if (!annotations.hasOwnProperty(annotation_type)) 
			annotations[annotation_type] = [];
		
		annotations[annotation_type].push({
			"path": item.find(".path input").val(), 
			"others": item.find(".others input").val(), 
			"desc": item.find(".description input").val(),
		});
	}

	var obj = {
		"enabled": settings_elm.children(".enabled").children("input").prop("checked") ? 1 : 0,
		"global_variables_files_path": global_variables_files_path,
		"annotations": annotations,
		"comments": settings_elm.children(".comments").children("textarea").val(),
	};
	
	return obj;
}

function saveTest() {
	var edit_test = $(".edit_test");
	
	prepareAutoSaveVars();
	
	var is_from_auto_save_bkp = is_from_auto_save; //backup the is_from_auto_save, bc if there is a concurrent process running at the same time, this other process may change the is_from_auto_save value.
	
	if (edit_test[0]) {
		if (!window.is_save_func_running) {
			window.is_save_func_running = true;
			
			if (is_from_auto_save_bkp && (!isTestObjChanged() || isEditorCodeWithErrors()) && checkIfWorkflowDoesNotNeedToChangePreviousCodeWithErrors(edit_test, true)) {
				resetAutoSave();
				window.is_save_func_running = false;
				return;
			}
			
			var obj = getTestObj();
			
			//check if user is logged in
			//if there was a previous function that tried to execute an ajax request, like the getCodeForSaving method, we detect here if the user needs to login, and if yes, recall the save function again. 
			//Do not re-call only the ajax request below, otherwise there will be some other files that will not be saved, this is, the getCodeForSaving saves the workflow and if we only call the ajax request below, the workflow won't be saved. To avoid this situation, we call the all save function.
			if (!is_from_auto_save_bkp && jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL)) {
				showAjaxLoginPopup(jquery_native_xhr_object.responseURL, jquery_native_xhr_object.responseURL, function() {
					taskFlowChartObj.StatusMessage.removeLastShownMessage("error");
					StatusMessageHandler.removeLastShownMessage("error");
					
					window.is_save_func_running = false;
					saveTest();
				});
				
				return;
			}
			
			//call saveObjCode
			saveObjCode(save_object_url, obj, {
				success: function(data, textStatus, jqXHR) {
					//update test_settings_id
					saved_test_settings_id = getTestSettingsId();
					
					return true;
				},
				complete: function() {
					window.is_save_func_running = false;
				},
			});
		}
		else if (!is_from_auto_save_bkp)
			StatusMessageHandler.showMessage("There is already a saving process running. Please wait a few seconds and try again...");
	}
	else if (!is_from_auto_save_bkp)
		alert("No object to save! Please contact the sysadmin...");
}
