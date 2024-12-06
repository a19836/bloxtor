var CreateFormTaskPropertyObj = {
	editor_ready_func: null,
	editor_save_func: null,
	layout_ui_editor_menu_widgets_elm_selector : null,
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		//console.log(properties_html_elm);
		//console.log(task_property_values);
		//if (task_property_values && task_property_values["form_settings_data"])console.log(task_property_values["form_settings_data"][0]["items"][0]["value"]);
		
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".create_form_task_html");
		
		if (typeof ProgrammingTaskUtil.on_programming_task_choose_page_url_callback == "function")
			task_html_elm.addClass("with_search");
		
		if (!task_property_values || jQuery.isEmptyObject(task_property_values)) {
			task_property_values = {
				"form_settings_data_type": "array",
				"form_settings_data": [],
			};
		}
		
		//PREPARING RESULT VARIABLE
		ProgrammingTaskUtil.setResultVariableType(task_property_values, task_html_elm);
		
		//PREPARING FORM SETTINGS DATA
		var form_settings_data = task_property_values["form_settings_data"];
		
		if (task_property_values["form_settings_data_type"] == "array") {
			ArrayTaskUtilObj.onLoadArrayItems( task_html_elm.children('.form_settings_data').first(), form_settings_data, "");
			task_html_elm.find(".form_settings input").val("");
			
			var fs_data = FormFieldsUtilObj.convertFormSettingsDataArrayToSettings(form_settings_data);
			
			if (fs_data.hasOwnProperty("ptl") && fs_data["ptl"]) {
				CreateFormTaskPropertyObj.loadPTLSettings(task_html_elm, fs_data["ptl"]);
				task_html_elm.find(".form_settings select").val("ptl");
			}
			else if (!form_settings_data || $.isEmptyObject(form_settings_data) || fs_data.hasOwnProperty("with_form") || fs_data.hasOwnProperty("form_id") || fs_data.hasOwnProperty("form_method") || fs_data.hasOwnProperty("form_css")) { //this check should be enough, otherwise add the other ones
				CreateFormTaskPropertyObj.loadFormSettings(task_html_elm, form_settings_data);
				task_html_elm.find(".form_settings select").val("settings");
			}
			else
				task_html_elm.find(".form_settings select").val("array");
		}
		else if (task_property_values["form_settings_data_type"] == "ptl") {
			if (form_settings_data.hasOwnProperty("ptl") && form_settings_data["ptl"]) {
				if (form_settings_data["ptl"].hasOwnProperty("external_vars"))
					form_settings_data["ptl"]["external_vars"] = FormFieldsUtilObj.convertFormSettingsDataArrayToSettings(form_settings_data["ptl"]["external_vars"]);
					
				CreateFormTaskPropertyObj.loadPTLSettings(task_html_elm, form_settings_data["ptl"]);
			}
			task_html_elm.find(".form_settings select").val("ptl");
		}
		else {
			form_settings_data = form_settings_data ? "" + form_settings_data + "" : "";
			form_settings_data = task_property_values["form_settings_data_type"] == "variable" && form_settings_data.trim().substr(0, 1) == '$' ? form_settings_data.trim().substr(1) : form_settings_data;
			task_html_elm.find(".form_settings input").val(form_settings_data);
		}
		CreateFormTaskPropertyObj.onChangeFormSettingsType( task_html_elm.find(".form_settings select")[0] );
		
		//PREPARING FORM INPUT DATA
		var form_input_data = task_property_values["form_input_data"];
		if (task_property_values["form_input_data_type"] == "array") {
			ArrayTaskUtilObj.onLoadArrayItems( task_html_elm.children('.form_input_data').first(), form_input_data, "");
			task_html_elm.find(".form_input input").val("");
		}
		else {
			form_input_data = form_input_data ? "" + form_input_data + "" : "";
			form_input_data = task_property_values["form_input_data_type"] == "variable" && form_input_data.trim().substr(0, 1) == '$' ? form_input_data.trim().substr(1) : form_input_data;
			task_html_elm.find(".form_input input").val(form_input_data);
		}
		CreateFormTaskPropertyObj.onChangeFormInputType( task_html_elm.find(".form_input select")[0] );
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".create_form_task_html");
		ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithType(task_html_elm);
		ProgrammingTaskUtil.onSubmitResultVariableType(task_html_elm);
		
		var type = task_html_elm.find(".form_settings select").val();
		if (type == "array") {
			task_html_elm.find(".form_settings input").remove();
			task_html_elm.find(".ptl_settings").remove();
			task_html_elm.find(".inline_settings").remove();
		}
		else if (type == "settings") {
			task_html_elm.find(".form_settings input").remove();
			task_html_elm.find(".ptl_settings").remove();
			CreateFormTaskPropertyObj.prepareCssAndJsFieldsToSave(task_html_elm);
			
			var form_settings_data = FormFieldsUtilObj.convertFormSettingsDataSettingsToArray( task_html_elm.children(".inline_settings") );
			ArrayTaskUtilObj.onLoadArrayItems( task_html_elm.find('.form_settings_data').first(), form_settings_data, "");
			task_html_elm.find(".form_settings select").val("array");
			
			task_html_elm.find(".inline_settings").remove();	
		}
		else if (type == "ptl") {
			task_html_elm.find(".form_settings input").remove();
			task_html_elm.find(".inline_settings, .form_settings_data").remove();
			CreateFormTaskPropertyObj.preparePTLFieldsToSave(task_html_elm);
			
			var ptl_editor = task_html_elm.data("ptl_editor");
			task_html_elm.data("ptl_editor", null); //cleans editors from browser cache
			CreateFormTaskPropertyObj.ptl_editor = ptl_editor; //sets this temporary to be use in the onCompleteTaskProperties
		}
		else {
			task_html_elm.find(".form_settings_data").remove();
			task_html_elm.find(".ptl_settings").remove();
			task_html_elm.find(".inline_settings").remove();
		}
		
		if (task_html_elm.find(".form_input select").val() == "array") {
			task_html_elm.find(".form_input input").remove();
		}
		else {
			task_html_elm.find(".form_input_data").remove();
		}
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var label = CreateFormTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
			
			//console.log(task_property_values);
			
			//When the onCompleteTaskProperties function is called, the myWFObj.getTaskFlowChart().Property.hideSelectedTaskProperties() function was already called, which removed the html from properties popup. This means that we can call then the CreateFormTaskPropertyObj.ptl_editor.resetDroppables().
			
			//check all droppables from jquery and resetting, this is, remove the invalid droppables.
			//if we create a jquery droppable element inside of an iframe and then move the iframe or delete it or reload it, the droppable elements stop to exist, but jquery continues with them registered. So we need to call the resetDroppables() function to fix this cases, otherwise we will have weird javascript errors.
			//console.log(CreateFormTaskPropertyObj.ptl_editor.resetDroppables);
			if (CreateFormTaskPropertyObj.ptl_editor) { //CreateFormTaskPropertyObj.ptl_editor was set in the onSubmitTaskProperties
				CreateFormTaskPropertyObj.ptl_editor.resetDroppables();
				CreateFormTaskPropertyObj.ptl_editor = null;
			}
		}
	},
	
	onCancelTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		//console.log("onCancelTaskProperties");
		//console.log(CreateFormTaskPropertyObj.ptl_editor.resetDroppables);
		
		var task_html_elm = $(properties_html_elm).find(".create_form_task_html");
		var ptl_editor = task_html_elm.data("ptl_editor");
		
		if (ptl_editor) {
			//hide popup first and empty html from properties popup
			myWFObj.getTaskFlowChart().Property.hideSelectedTaskProperties();
			
			//check all droppables from jquery and resetting, this is, remove the invalid droppables.
			//if we create a jquery droppable element inside of an iframe and then move the iframe or delete it or reload it, the droppable elements stop to exist, but jquery continues with them registered. So we need to call the resetDroppables() function to fix this cases, otherwise we will have weird javascript errors.
			ptl_editor.resetDroppables();
		}
		
		//cleans editors from browser cache
		task_html_elm.data("editor", null);
		
		return true;	
	},
	
	onCompleteLabel : function(task_id) {
		return ProgrammingTaskUtil.onEditLabel(task_id);
	},
	
	onTaskCreation : function(task_id) {
		setTimeout(function() {
			var task_property_values = myWFObj.getTaskFlowChart().TaskFlow.tasks_properties[task_id];
			ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithTaskPropertiesValues(task_property_values);
		
			var label = CreateFormTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
			
			onEditLabel(task_id);
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 50);
	},
	
	getDefaultExitLabel : function(task_property_values) {
		var form_settings_data = task_property_values["form_settings_data_type"] == "array" ? ArrayTaskUtilObj.arrayToString(task_property_values["form_settings_data"]) : ProgrammingTaskUtil.getValueString(task_property_values["form_settings_data"], task_property_values["form_settings_data_type"]);
		form_settings_data = form_settings_data ? form_settings_data : "null";
		
		var form_input_data = task_property_values["form_input_data_type"] == "array" ? ArrayTaskUtilObj.arrayToString(task_property_values["form_input_data"]) : ProgrammingTaskUtil.getValueString(task_property_values["form_input_data"], task_property_values["form_input_data_type"]);
		form_input_data = form_input_data ? form_input_data : "null";
		
		return ProgrammingTaskUtil.getResultVariableString(task_property_values) + "HtmlFormHandler::createHtmlForm(" + form_settings_data + ", " + form_input_data + ")";
	},
	
	onChangeFormSettingsType : function(elm) {
		elm = $(elm);
		
		var type = elm.val();
		var old_type = elm.attr("old_type");
		
		var main_div = elm.parent().closest(".create_form_task_html");
		var form_settings_data_elm = main_div.children(".form_settings_data").first();
		
		if (type == "settings") {
			main_div.children(".inline_settings").show();
			main_div.children(".ptl_settings").hide();
			elm.parent().children("input").hide();
			form_settings_data_elm.hide();
			
			//PREPARING CSS
			var form_css = main_div.find(".form_css");
			if (!form_css.data("editor"))
				CreateFormTaskPropertyObj.createCodeEditor(form_css.children("textarea.editor")[0], "css", CreateFormTaskPropertyObj.editor_save_func);
			
			//PREPARING JS
			var form_js = main_div.find(".form_js");
			if (!form_js.data("editor"))
				CreateFormTaskPropertyObj.createCodeEditor(form_js.children("textarea.editor")[0], "javascript", CreateFormTaskPropertyObj.editor_save_func);
			
			if (old_type == "array" && confirm("Do you wish to convert automatically the array items into form settings?")) {
				var WF = myWFObj.getTaskFlowChart();
				var query_string = WF.Property.getPropertiesQueryStringFromHtmlElm(form_settings_data_elm, "task_property_field");
				var form_settings_data = {};
				parse_str(query_string, form_settings_data);
				form_settings_data = form_settings_data["form_settings_data"] ? form_settings_data["form_settings_data"] : {};
				CreateFormTaskPropertyObj.loadFormSettings(main_div, form_settings_data);
			}
		}
		else if (type == "array") {
			main_div.children(".inline_settings, .ptl_settings").hide();
			elm.parent().children("input").hide();
			form_settings_data_elm.show();
			
			if (!form_settings_data_elm.find(".items")[0]) {
				var items = {0: {key_type: "null", value_type: "string"}};
				ArrayTaskUtilObj.onLoadArrayItems(form_settings_data_elm, items, "");
			}
			
			if (old_type == "settings" && confirm("Do you wish to convert automatically the form settings into array items?")) {
				CreateFormTaskPropertyObj.prepareCssAndJsFieldsToSave(main_div);
				
				var form_settings_data = FormFieldsUtilObj.convertFormSettingsDataSettingsToArray( main_div.children(".inline_settings") );
				ArrayTaskUtilObj.onLoadArrayItems( form_settings_data_elm, form_settings_data, "");
			}
		}
		else if (type == "ptl") {
			var ptl_settings_elm = main_div.children(".ptl_settings");
			
			main_div.children(".inline_settings").hide();
			elm.parent().children("input").hide();
			ptl_settings_elm.show();
			form_settings_data_elm.hide();
			
			//PREPARING UI EDITOR - only execute once
			if (!ptl_settings_elm[0].hasAttribute("data-already-initialized")) {
				ptl_settings_elm.attr("data-already-initialized", 1);
				
				var ui = ptl_settings_elm.children(".layout-ui-editor");
				
				if (ui[0] && !ui.data("LayoutUIEditor") && typeof LayoutUIEditor == "function") {
					//add CreateFormTaskPropertyObj.layout_ui_editor_menu_widgets_elm_selector in <ul class="menu-widgets hidden"></ul>
					if (CreateFormTaskPropertyObj.layout_ui_editor_menu_widgets_elm_selector) {
						var mwb = $(CreateFormTaskPropertyObj.layout_ui_editor_menu_widgets_elm_selector);
						
						if (mwb[0]) {
							var menu_widgets = ui.children(".menu-widgets");
							
							if (!menu_widgets[0]) {
								menu_widgets = $('<ul class="menu-widgets hidden"></ul>');
								ui.append(menu_widgets);
							}
						
							menu_widgets.append( mwb.contents().clone() );
						}
					}
					
					//init LayoutUIEditor
					var ptl_ui_creator_var_name = "PTLLayoutUIEditor_" + Math.floor(Math.random() * 1000);
					var PtlLayoutUIEditor = new LayoutUIEditor();
					PtlLayoutUIEditor.options.ui_element = ui;
					PtlLayoutUIEditor.options.on_template_source_editor_save_func = CreateFormTaskPropertyObj.editor_save_func;
					PtlLayoutUIEditor.options.on_choose_variable_func = ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable;
					PtlLayoutUIEditor.options.on_choose_page_url_func = ProgrammingTaskUtil.onProgrammingTaskChoosePageUrl;
					PtlLayoutUIEditor.options.on_choose_image_url_func = ProgrammingTaskUtil.onProgrammingTaskChooseImageUrl;
					PtlLayoutUIEditor.options.on_choose_webroot_file_url_func = ProgrammingTaskUtil.onProgrammingTaskChooseWebrootFileUrl;
					
					PtlLayoutUIEditor.options.on_ready_func = function() {
						//hide php menu widget bc it doesn't apply here
						PtlLayoutUIEditor.getMenuWidgets().find(".menu-widget-php").hide();
						
						//show php widgets, borders and background
						PtlLayoutUIEditor.showTemplateWidgetsDroppableBackground();
						PtlLayoutUIEditor.showTemplateWidgetsBorders();
						PtlLayoutUIEditor.showTemplatePHPWidgets();
						
						//runf ready function
						if (typeof CreateFormTaskPropertyObj.editor_ready_func == "function")
							CreateFormTaskPropertyObj.editor_ready_func(PtlLayoutUIEditor);
					};
					window[ptl_ui_creator_var_name] = PtlLayoutUIEditor;
					PtlLayoutUIEditor.init(ptl_ui_creator_var_name);
					
					main_div.data("ptl_editor", PtlLayoutUIEditor);
				}
				else {
					CreateFormTaskPropertyObj.createCodeEditor(ui.find("> .template-source > textarea")[0], "php", CreateFormTaskPropertyObj.editor_save_func);
					
					var editor = ui.children(".template-source").data("editor");
					ptl_settings_elm.data("editor", editor);
				}
			}
			
			//Preparing external vars
			if (ptl_settings_elm.find('.ptl_external_vars > .items').length == 0)
				CreateFormTaskPropertyObj.loadPTLExternalVars(ptl_settings_elm.children('.ptl_external_vars').first(), null);
			
			//Preparing migration from settings and array to ptl code
			if ((old_type == "settings" && confirm("Do you wish to convert automatically the form settings into ptl code?")) || (old_type == "array" && confirm("Do you wish to convert automatically the array items into ptl code?"))) {
				if (old_type == "array") {
					var WF = myWFObj.getTaskFlowChart();
					var query_string = WF.Property.getPropertiesQueryStringFromHtmlElm(form_settings_data_elm, "task_property_field");
					var form_settings_data = {};
					parse_str(query_string, form_settings_data);
					form_settings_data = form_settings_data["form_settings_data"] ? form_settings_data["form_settings_data"] : {};
					CreateFormTaskPropertyObj.loadFormSettings(main_div, form_settings_data);
				}
				
				CreateFormTaskPropertyObj.prepareCssAndJsFieldsToSave(main_div);
				
				PTLFieldsUtilObj.external_vars = {};
				var ptl = PTLFieldsUtilObj.convertFormSettingsDataSettingsToPTL( main_div.children(".inline_settings") );
				var code = ptl ? ptl["code"] : "";
				CreateFormTaskPropertyObj.setTemplateSourceEditorValue(main_div, code);
				
				CreateFormTaskPropertyObj.loadPTLExternalVars(ptl_settings_elm.children('.ptl_external_vars').first(), PTLFieldsUtilObj.external_vars);
			}
		}
		else {
			main_div.children(".inline_settings, .ptl_settings").hide();
			elm.parent().children("input").show();
			form_settings_data_elm.hide();
		}
		
		elm.attr("old_type", type);
		
		ProgrammingTaskUtil.onChangeTaskFieldType(elm[0]);
	},
	
	onChangeWithForm : function(elm) {
		elm = $(elm);
		
		var inline_settings = elm.parent().parent();
		var with_form = elm.val() == 1;
		
		if (with_form) {
			inline_settings.children(".form_id, .form_method, .form_class, .form_type, .form_on_submit, .form_action").show();
		}
		else {
			inline_settings.children(".form_id, .form_method, .form_class, .form_type, .form_on_submit, .form_action").hide();
		}
	},
	
	onChangeFormInputType : function(elm) {
		var type = $(elm).val();
		
		var form_input_data_elm = $(elm).parent().parent().children(".form_input_data").first();
		
		if (type == "array") {
			$(elm).parent().children("input").hide();
			form_input_data_elm.show();
			
			if (!form_input_data_elm.find(".items")[0]) {
				var items = {0: {key_type: "null", value_type: "string"}};
				ArrayTaskUtilObj.onLoadArrayItems(form_input_data_elm, items, "");
			}
		}
		else {
			$(elm).parent().children("input").show();
			form_input_data_elm.hide();
		}
		
		ProgrammingTaskUtil.onChangeTaskFieldType(elm);
	},
	
	//PREPARING FORM SETTINGS
	loadFormSettings : function(task_html_elm, form_settings_data) {
		//console.log(form_settings_data);
		var settings = FormFieldsUtilObj.convertFormSettingsDataArrayToSettings(form_settings_data);
		//console.log(settings);
		
		task_html_elm.find(".with_form select").first().val( settings["with_form"] );
		task_html_elm.find(".form_id input").first().val( settings["form_id"] );
		task_html_elm.find(".form_method select").first().val( settings["form_method"] );
		task_html_elm.find(".form_class input").first().val( settings["form_class"] );
		task_html_elm.find(".form_type select").first().val( settings["form_type"] );
		task_html_elm.find(".form_on_submit input").first().val( settings["form_on_submit"] );
		task_html_elm.find(".form_action input").first().val( settings["form_action"] );
		
		CreateFormTaskPropertyObj.onChangeWithForm( task_html_elm.find(".with_form select")[0] );
		
		FormFieldsUtilObj.loadContainers(task_html_elm.find(".form_containers .fields").first(), "form_containers", settings["form_containers"]);
		
		//PREPARING CSS
		var form_css = task_html_elm.find(".form_css");
		var editor = form_css.data("editor");
		
		//stripslashes removed from here 3-Nov-2015. 
		//2019-10-17: Commented the repalce \n and \t bc it might exist an intentional escaped end-line, which means this will NOT convert it to a real end-line and it will show the '\n' escaped
		var css = settings["form_css"] ? settings["form_css"]/*.replace(/\\t/g, "\t").replace(/\\n/g, "\n")*/ : "";
		css = convertToNormalTextIfValueIsSurroundedWithQuotesAndIsNotAPHPCode(css);
		
		if (!editor)
			form_css.children("textarea.editor").val(css);
		else
			editor.setValue(css, -1);
		
		//PREPARING JS
		var form_js = task_html_elm.find(".form_js");
		editor = form_js.data("editor");
		
		//stripslashes removed from here 3-Nov-2015. 
		//2019-10-17: Commented the repalce \n and \t bc it might exist an intentional escaped end-line, which means this will NOT convert it to a real end-line and it will show the '\n' escaped
		var js = settings["form_js"] ? settings["form_js"]/*.replace(/\\t/g, "\t").replace(/\\n/g, "\n")*/ : "";
		js = convertToNormalTextIfValueIsSurroundedWithQuotesAndIsNotAPHPCode(js);
		
		if (!editor)
			form_js.children("textarea.editor").val(js);
		else
			editor.setValue(js, -1);
	},
	
	//PREPARING PTL SETTINGS
	loadPTLSettings : function(task_html_elm, ptl_settings_data) {
		//console.log(ptl_settings_data);
		
		//PREPARING CODE
		var code = ptl_settings_data.hasOwnProperty("code") ? ptl_settings_data["code"] : "";
		//console.log(code);
		
		//removing extra quotes
		if (code != "") {
			code = convertToNormalTextIfValueIsSurroundedWithQuotesAndIsNotAPHPCode(code);
			//console.log("!"+code+"!");
			
			//code = code.replace(/\\t/g, "\t").replace(/\\n/g, "\n"); //2019-10-17: DO NOT DO THIS bc if there are escaped end-lines '\n' or "\\n", it will convert to end-lines and if this is inside of some javascript code, it will then give a javascript error.
			CreateFormTaskPropertyObj.setTemplateSourceEditorValue(task_html_elm, code);
		}
		
		task_html_elm.find(".ptl_settings .input_data_var_name input").first().val( ptl_settings_data["input_data_var_name"] );
		task_html_elm.find(".ptl_settings .idx_var_name input").first().val( ptl_settings_data["idx_var_name"] );
		
		var external_vars = ptl_settings_data.hasOwnProperty("external_vars") ? ptl_settings_data["external_vars"] : {};
		CreateFormTaskPropertyObj.loadPTLExternalVars(task_html_elm.find('.ptl_settings .ptl_external_vars').first(), external_vars);
	},
	
	loadPTLExternalVars : function(external_vars_elm, external_vars) {
		var evs = [];
		if (external_vars)
			for (var k in external_vars)
				evs.push({
					"key" : k,
					"key_type" : "string",
					"value" : external_vars[k],
					"value_type" : "variable",
				});
		
		if (!evs || $.isEmptyObject(evs))
			evs = {0: {key_type: "string", value_type: "string"}};
		
		ArrayTaskUtilObj.onLoadArrayItems(external_vars_elm, evs, "Add External Vars", "form_settings_data[ptl][external_vars]");
		
		$(external_vars_elm).find(".item_add").attr("onClick", "CreateFormTaskPropertyObj.onAddNewPTLExternalVar(this)");
		
		$(external_vars_elm).find("ul li.item").each(function(idx, item) {
			item = $(item);
			item.find(".key").attr("placeHolder", "Var Name");
			item.find(".value").attr("placeHolder", "Var with $");
		});
	},
	
	onAddNewPTLExternalVar : function(elm) {
		var item = ArrayTaskUtilObj.addItem(elm);
		item.find(".key").attr("placeHolder", "Var Name");
		item.find(".value").attr("placeHolder", "Var with $");
	},
	
	prepareCssAndJsFieldsToSave : function(task_html_elm) {
		var editor = task_html_elm.find(".form_css").data("editor");
		var css = editor ? editor.getValue() : task_html_elm.find(".form_css textarea").val();
		//css = css.replace(/\n/g, "\\n"); //2019-10-17: remove replace of the end-lines bc we want to save this with the end-lines. Otherwise we cannot detect after what are the escaped end-lines versus the not escaped.
		task_html_elm.find(".form_css textarea.task_property_field").val(css); 
		
		editor = task_html_elm.find(".form_js").data("editor");
		var js = editor ? editor.getValue() : task_html_elm.find(".form_js textarea").val();
		//js = js.replace(/\n/g, "\\n"); //2019-10-17: remove replace of the end-lines bc we want to save this with the end-lines. Otherwise we cannot detect after what are the escaped end-lines versus the not escaped.
		task_html_elm.find(".form_js textarea.task_property_field").val(js);
	},
	
	preparePTLFieldsToSave : function(task_html_elm) {
		var ptl_settings_elm = task_html_elm.find(".ptl_settings");
		
		var code = CreateFormTaskPropertyObj.getTemplateSourceEditorValue(task_html_elm, true);
		ptl_settings_elm.children("textarea.task_property_field").attr("name", "form_settings_data[ptl][code]").val(code);
		
		ptl_settings_elm.find(".input_data_var_name input").attr("name", "form_settings_data[ptl][input_data_var_name]");
		ptl_settings_elm.find(".idx_var_name input").attr("name", "form_settings_data[ptl][idx_var_name]");
	},
	
	setTemplateSourceEditorValue : function(task_html_elm, value) {
		var ptl_settings_elm = task_html_elm.find(".ptl_settings");
		var ptl_editor = task_html_elm.data("ptl_editor");
		
		if (ptl_editor)
			ptl_editor.setTemplateSourceEditorValue(value);
		else {
			var editor = ptl_settings_elm.data("editor");
			
			if (editor)
				editor.setValue(value, -1);
			else 
				ptl_settings_elm.find(" > .layout-ui-editor > .template-source > textarea").val(value);
		}
	},
	
	getTemplateSourceEditorValue : function(task_html_elm, force) {
		var ptl_settings_elm = task_html_elm.find(".ptl_settings");
		var ptl_editor = task_html_elm.data("ptl_editor");
		
		if (ptl_editor) {
			if (force)
				ptl_editor.forceTemplateSourceConversionAutomatically(); //Be sure that the template source is selected
			
			return ptl_editor.getTemplateSourceEditorValue();
		}
		
		var editor = ptl_settings_elm.data("editor");
		
		return editor ? editor.getValue() : ptl_settings_elm.find(" > .layout-ui-editor > .template-source > textarea").val();
	},
	
	createCodeEditor : function(textarea, type, save_func) {
		if (ace && textarea) {
			var parent = $(textarea).parent();
			
			ace.require("ace/ext/language_tools");
			var editor = ace.edit(textarea);
			editor.setTheme("ace/theme/chrome");
			editor.session.setMode("ace/mode/" + type);
			//editor.setAutoScrollEditorIntoView(false);
			editor.setOption("minLines", 10);
			editor.setOption("maxLines", "100");//100 or Infinity
			editor.setOptions({
				enableBasicAutocompletion: true,
				enableSnippets: true,
				enableLiveAutocompletion: false,
			});
			editor.setOption("wrap", true);
			
			if (typeof save_func == "function")
				editor.commands.addCommand({
					name: 'saveFile',
					bindKey: {
						win: 'Ctrl-S',
						mac: 'Command-S',
						sender: 'editor|cli'
					},
					exec: function(env, args, request) {
						save_func();
					},
				});
			
			parent.find("textarea.ace_text-input").removeClass("ace_text-input"); //fixing problem with scroll up, where when focused or pressed key inside editor the page scrolls to top
			
			parent.data("editor", editor);
	
			return editor;
		}
	},
};
