var active_tab = null;
var active_tabs_ids = null;
var saved_simple_form_settings_id = null;

$(function () {
	$(window).bind('beforeunload', function () {
		if (isGlobalVariablesCodeObjChanged()) {
			if (window.parent && window.parent.iframe_overlay)
				window.parent.iframe_overlay.hide();
			
			return "If you proceed your changes won't be saved. Do you wish to continue?";
		}
		
		return null;
	});
	
	//prepare top_bar
	$("#ui > .taskflowchart").addClass("with_top_bar_menu fixed_side_properties").children(".workflow_menu").addClass("top_bar_menu");
	
	//init auto save
	$("#code .code_menu.top_bar_menu ul li.auto_save_activation, #ui .taskflowchart .workflow_menu ul.dropdown li.auto_save_activation a").attr("onClick", "toggleAutoSaveCheckbox(this, onToggleGlobalVariablesAutoSave)");
	
	$("#code .code_menu.top_bar_menu li.auto_convert_activation, #ui .taskflowchart .workflow_menu ul.dropdown li.auto_convert_activation a").attr("onClick", "toggleAutoConvertCheckbox(this, onToggleGlobalVariablesAutoConvert)");
	
	var auto_save_icon = $("#code > .code_menu li.auto_save_activation").first().clone();
	var auto_convert_icon = $("#code > .code_menu li.auto_convert_activation").first().clone();
	$("#form_global_vars .code_menu.top_bar_menu ul li.save").before(auto_save_icon).before(auto_convert_icon);
	
	enableAutoSave(onToggleGlobalVariablesAutoSave);
	enableAutoConvert(onToggleGlobalVariablesAutoConvert);
	auto_save = false;
	
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
	
	//init ui
	var global_vars_obj = $(".global_vars_obj");
	
	if (global_vars_obj[0]) {
		//prepare active_tabs_ids
		active_tabs_ids = {};
		var tabs_lis = global_vars_obj.find(" > ul.tabs > li");
		
		for (var i = 0; i < tabs_lis.length; i++)
			active_tabs_ids[ tabs_lis[i].id ] = i;
		
		active_tab = is_code_valid ? active_tabs_ids["form_global_vars_tab"] : active_tabs_ids["code_editor_tab"];
		
		//prpeare active tabs
		global_vars_obj.tabs({
			active: show_low_code_first ? active_tabs_ids["tasks_flow_tab"] : active_tab, //show workflow tab
		});
		
		if (!is_code_valid)
			global_vars_obj.tabs("disable" , 0); //disable simple form tab
		
		//prpeare code editor
		var textarea = $("#code textarea")[0];
		if (textarea) {
			var editor = createCodeEditor(textarea, {save_func: saveGlobalVariables});
			
			if (editor)
				editor.focus();
		}
		
		//load workflow
		onLoadTaskFlowChartAndCodeEditor({do_not_hide_popup : true});
		
		//init tasks flow tab
		onClickTaskWorkflowTab( global_vars_obj.find(" > .tabs > #tasks_flow_tab > a")[0], {
			on_success: function() {
				//set saved_simple_form_settings_id
				saved_simple_form_settings_id = getSimpleFormSettingsObjId();
				
				if (is_code_valid)
					global_vars_obj.tabs("option", "active", active_tabs_ids["form_global_vars_tab"]);
				
				auto_save = true;
				
				MyFancyPopup.hidePopup();
			},
			on_error: function() {
				global_vars_obj.tabs("option", "active", active_tab); //show previous tab
				
				//set saved_simple_form_settings_id
				saved_simple_form_settings_id = getSimpleFormSettingsObjId();
				
				auto_save = true;
				
				MyFancyPopup.hidePopup();
			}
		});
	}
	else	//hide loading icon
		MyFancyPopup.hidePopup();
});

function onToggleGlobalVariablesAutoSave() {
	onTogglePHPCodeAutoSave();
	
	var inputs = $("#form_global_vars .code_menu.top_bar_menu ul li.auto_save_activation input");
	
	if (auto_save) 
		inputs.attr("checked", "checked").prop("checked", true);
	else
		inputs.removeAttr("checked", "checked").prop("checked", false);
}

function onToggleGlobalVariablesAutoConvert() {
	onTogglePHPCodeAutoConvert();
	
	var inputs = $("#form_global_vars .code_menu.top_bar_menu ul li.auto_convert_activation input");
	
	if (auto_convert) 
		inputs.attr("checked", "checked").prop("checked", true);
	else
		inputs.removeAttr("checked", "checked").prop("checked", false);
}

function onChangeSimpleFormGlobalVarSelect(elm) {
	if (auto_save)
		saveGlobalVariables();
}

function onBlurSimpleFormGlobalVarInput(elm) {
	if (auto_save)
		saveGlobalVariables();
}

function addNewVariable(elm) {
	var table = $(elm).parent().parent().parent();
	var item = $(global_var_html);
	
	table.append(item);
	
	return item;
}

function onIncludeFileTaskChooseFileForProjectGlobalVars(elm) {
	var popup = $("#choose_file_from_file_manager");
	
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		
		targetField: $(elm).parent(),
		updateFunction: chooseIncludeFileForProjectGlobalVars
	});
	
	MyFancyPopup.showPopup();
}

function chooseIncludeFileForProjectGlobalVars(elm) {
	var node = chooseFileFromFileManagerTree.getSelectedNodes();
	node = node[0];
	
	if (node) {
		var a = $(node).children("a");
		var file_path = a.attr("file_path");
		var bean_name = a.attr("bean_name");
		
		if (file_path) {
			var parts = current_relative_file_path.replace(/\/\//g, "").split("/");
			var inc_1 = "";
			var inc_2 = "";
			for (var i = 0; i < parts.length; i++) {
				inc_1 += "dirname(";
				inc_2 += ")";
			}
			
			var include_path = inc_1 + "__FILE__" + inc_2;
			
			if (bean_name == "dao" || bean_name == "lib" || bean_name == "vendor" || bean_name == "test_unit")
				include_path = 'dirname(dirname(' + include_path + ')) . "' + bean_name.toLowerCase() + '/' + file_path + '"';
			else if (layer_type == "pres")
				include_path += ' . "' + file_path + '"';
			
			MyFancyPopup.settings.targetField.children("input").val(include_path);
			MyFancyPopup.settings.targetField.parent().find(".type select").val("");
			
			//This is for the presentation task: includes and includes_once items.
			MyFancyPopup.settings.targetField.children(".value_type").val("");
			MyFancyPopup.settings.targetField.children(".includes_type").val("");
			MyFancyPopup.settings.targetField.children(".includes_once_type").val("");
		
			MyFancyPopup.hidePopup();
		}
		else {
			alert("invalid selected file.\nPlease choose a valid file.");
		}
	}
}

function onChooseAvailableTemplate(elm) {
	var available_projects_templates_props = {};
	available_projects_templates_props[selected_project_id] = available_templates_props;
	
	var settings = getSimpleFormSettings();
	var selected_template = null;
	
	for (var i = 0, l = settings["vars_name"].length; i < l; i++)
		if (settings["vars_name"][i] == "project_default_template") {
			selected_template = settings["vars_value"][i];
			break;
		}
	
	chooseAvailableTemplate( $(elm).parent().parent().find(" > .var_value select")[0], {
		available_projects_templates_props: available_projects_templates_props,
		get_available_templates_props_url: get_available_templates_props_url,
		install_template_url: install_template_url,
		chosen_template: selected_template,
		show_templates_only: true,
		hide_choose_different_editor: true,
		hide_choose_different_project: true,
		hide_template_options: true,
		//hide_chosen_project_template: true,
	} );
}

function onClickGlobalVariablesCodeEditorTab(elm) {
	if (auto_convert && active_tab == active_tabs_ids["form_global_vars_tab"]) {
		var code = convertSimpleFormIntoCode();
		setEditorCodeRawValue(code);
	}
	else
		onClickCodeEditorTab(elm);
	
	active_tab = active_tabs_ids["code_editor_tab"];
}

function onClickGlobalVariablesTaskWorkflowTab(elm) {
	if (auto_convert && active_tab == active_tabs_ids["form_global_vars_tab"]) {
		var code = convertSimpleFormIntoCode();
		setEditorCodeRawValue(code);
	}
	
	onClickTaskWorkflowTab(elm);
	
	active_tab = active_tabs_ids["tasks_flow_tab"];
}

function onClickGlobalVariablesSimpleFormTab(elm) {
	if (auto_convert) {
		var convert_code_to_vars_func = function() {
			var code = getEditorCodeRawValue(); //get code from editor
			var vars = convertCodeIntoList(code); //parse code into vars based in regex
			loadSimpleFormWithNewVars(vars); //load new vars with addNewVariable
		};
		
		if (active_tab == active_tabs_ids["code_editor_tab"])
			convert_code_to_vars_func();
		else if (active_tab == active_tabs_ids["tasks_flow_tab"]) {
			//close properties popup in case the auto_save be active on close task properties popup
			if (auto_save && taskFlowChartObj.Property.auto_save) {
				if (taskFlowChartObj.Property.isSelectedTaskPropertiesOpen())
					taskFlowChartObj.Property.saveTaskProperties({do_not_call_hide_properties: true});
				else if (taskFlowChartObj.Property.isSelectedConnectionPropertiesOpen())
					taskFlowChartObj.Property.saveConnectionProperties({do_not_call_hide_properties: true});
			}
			
			var old_code_id = $("#ui").attr("code_id");
			var code = getEditorCodeRawValue();
			var new_code_id = $.md5(code);
			var old_workflow_id = $("#ui").attr("workflow_id");
			var new_workflow_id = getCurrentWorkFlowId();
			
			var convert_code = (old_workflow_id != new_workflow_id) || (old_code_id != new_code_id);
			
			if (convert_code) {
				//convert workflow in code, then parse code into vars based in regex and load new vars with addNewVariable
				var options = {
					do_not_change_to_code_tab: true, 
					
					success : function() {
						StatusMessageHandler.removeMessages("info");
						convert_code_to_vars_func();
					},
					error : function() {
						StatusMessageHandler.removeMessages("info");
						StatusMessageHandler.showError("Couldn't convert workflow to vars bc couldn't generate correspondent code! Please try again...");
					},
				};
				generateCodeFromTasksFlow(true, options);
				
				if (options["generating"])
					StatusMessageHandler.showMessage("Generating code based in workflow... Loading...", "", "bottom_messages", 1500);
			}
			else
				convert_code_to_vars_func();
		}
	}
	
	active_tab = active_tabs_ids["form_global_vars_tab"];
}

function loadSimpleFormWithNewVars(vars) {
	var table = $("#form_global_vars .vars");
	
	//delete non default vars, which are all vars with text inputs
	table.find("td input.var_name[type=text]").parent().closest("tr").remove();
	
	//load new default vars
	var inputs = table.find("td input.var_name[type=hidden]");
	
	for (var i = 0, l = inputs.length; i < l; i++) {
		var name_input = $(inputs[i]);
		var var_name = name_input.val();
		var value_input = name_input.parent().closest("tr").children(".var_value").find("select, input, textarea");
		
		if (vars.hasOwnProperty(var_name)) {
			var var_value = vars[var_name];
			value_input.val(var_value);
			
			//if select and value does not exists, append it
			if (value_input.is("select") && value_input.val() != var_value) {
				value_input.append('<option>' + var_value + '</option>');
				value_input.val(var_value);
			}
			
			//delete correspondent var from vars object
			delete vars[var_name];
		}
		else
			value_input.val("");
	}
	
	if (!$.isEmptyObject(vars)) {
		var add_icon = table.find("th.buttons .add");
		
		for (var var_name in vars) {
			var var_value = vars[var_name];
			var item = addNewVariable(add_icon[0]);
			
			item.find("input.var_name").val(var_name);
			item.find("input.var_value").val(var_value);
		}
	}
}

//Note that if you change this function please check the code in choose_available_template.js bc is using this function.
function convertCodeIntoList(code) {
	var regex = new RegExp('([\\w\\$:\\-\\>]+)([ ]*)=([ ]*)([^;]+);', 'g');
	var m;
	var vars = {};
	
	do {
		m = regex.exec(code);
		
		if (m && m[1]) {
			var var_name = m[1];
			var_name = var_name.charAt(0) == "$" ? var_name.substr(1, var_name.length) : var_name;
			
			var var_value = m[4];
			var_value = (var_value.charAt(0) == "'" || var_value.charAt(var_name.length - 1) == '"') || (var_value.charAt(0) == '"' || var_value.charAt(var_name.length - 1) == '"') ? var_value.substr(1, var_value.length - 2) : var_value;
			
			vars[var_name] = var_value;
		}
	} 
	while (m);
	
	return vars;
}

function convertSimpleFormIntoCode() {
	var code = "<?php\n";
	var settings = getSimpleFormSettings();
	
	for (var i = 0, l = settings["vars_name"].length; i < l; i++) {
		var var_name = settings["vars_name"][i];
		var var_value = settings["vars_value"][i];
		
		if (var_name) {
			var var_value_lower = ("" + var_value).toLowerCase();
			var quotes = '"';
			
			if ($.isNumeric(var_value) || var_value_lower == "true" || var_value_lower == "false" || var_value_lower == "null") {
				var_value = var_value_lower;
				quotes = '';
			}
			
			code += '$' + var_name + ' = ' + quotes + var_value + quotes + ';' + "\n";
		}
	}
	
	code += "?>";
	
	return code;	
}

function getSimpleFormSettings() {
	var table = $("#form_global_vars .vars");
	
	var vars_name = table.find("td .var_name");
	var vars_value = table.find("td .var_value");
	
	var vars_name_data = [];
	var vars_value_data = [];
	
	for (var i = 0; i < vars_name.length; i++) {
		var name = $(vars_name[i]).val();
		var value = $(vars_value[i]).val();
		
		if (name && value != "__DEFAULT__") {
			vars_name_data.push(name);
			vars_value_data.push(value);
		}
	}
	
	return {
		"vars_name": vars_name_data, 
		"vars_value": vars_value_data,
	};
}

function getSimpleFormSettingsObjId() {
	var obj = getSimpleFormSettings();
	
	return $.md5(JSON.stringify(obj));
}

function isGlobalVariablesCodeObjChanged() {
	var global_vars_obj = $(".global_vars_obj");
	
	if (!global_vars_obj[0])
		return false;
	
	if(isCodeAndWorkflowObjChanged(global_vars_obj))
		return true;
	
	var new_simple_form_settings_id = getSimpleFormSettingsObjId();
	
	return saved_simple_form_settings_id != new_simple_form_settings_id;
}

//Note that if you change this function please check the code in choose_available_template.js bc is using this function.
function getGlobalVariablesCodeObj() {
	var global_vars_obj = $(".global_vars_obj");
	
	if (!global_vars_obj[0])
		return null;
	
	var active_tab = global_vars_obj.tabs('option', 'active');
	
	//if simple vars
	if (active_tab == active_tabs_ids["form_global_vars_tab"]) {
		prepareAutoSaveVars();
		
		var obj = getSimpleFormSettings();
		obj["code"] = "";
		
		return obj;
	}
	
	//else if advanced (code or workflow)
	var code = getCodeForSaving(global_vars_obj);
	return {"code": code};
}

function getGlobalVariablesSaveUrl() {
	var active_tab = $(".global_vars_obj").tabs('option', 'active');
	return active_tab == active_tabs_ids["form_global_vars_tab"] ? save_object_simple_url : save_object_advanced_url;
}

function saveGlobalVariables() {
	var global_vars_obj = $(".global_vars_obj");
	
	prepareAutoSaveVars();
	
	var is_from_auto_save_bkp = is_from_auto_save; //backup the is_from_auto_save, bc if there is a concurrent process running at the same time, this other process may change the is_from_auto_save value.
	
	if (global_vars_obj[0]) {
		if (!window.is_save_func_running) {
			window.is_save_func_running = true;
			
			if (is_from_auto_save_bkp && (!isGlobalVariablesCodeObjChanged() || isEditorCodeWithErrors()) && checkIfWorkflowDoesNotNeedToChangePreviousCodeWithErrors(global_vars_obj)) {
				resetAutoSave();
				window.is_save_func_running = false;
				return;
			}
			
			var url = getGlobalVariablesSaveUrl();
			var obj = getGlobalVariablesCodeObj();
			
			//check if user is logged in
			//if there was a previous function that tried to execute an ajax request, like the getCodeForSaving method, we detect here if the user needs to login, and if yes, recall the save function again. 
			//Do not re-call only the ajax request below, otherwise there will be some other files that will not be saved, this is, the getCodeForSaving saves the workflow and if we only call the ajax request below, the workflow won't be saved. To avoid this situation, we call the all save function.
			if (!is_from_auto_save_bkp && jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL)) {
				showAjaxLoginPopup(jquery_native_xhr_object.responseURL, jquery_native_xhr_object.responseURL, function() {
					taskFlowChartObj.StatusMessage.removeLastShownMessage("error");
					StatusMessageHandler.removeLastShownMessage("error");
					
					window.is_save_func_running = false;
					saveGlobalVariables();
				});
				
				return;
			}
			
			//call saveObjCode
			saveObjCode(url, obj, {
				success: function(data, textStatus, jqXHR) {
					//update saved_simple_form_settings_id
					saved_simple_form_settings_id = getSimpleFormSettingsObjId();
					
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
		alert("No global vars object to save! Please contact the sysadmin...");
}
