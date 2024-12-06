var InlineHTMLTaskPropertyObj = {
	editor_ready_func: null,
	editor_save_func: null,
	is_code_html_base : true, //This means that the base is html and not php
	layout_ui_editor_menu_widgets_elm_selector : null,
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		//console.log(properties_html_elm);
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		
		var code = task_property_values["code"];
		code = typeof code != "undefined" ? code : "";
		
		var inlinehtml_task_html = $(properties_html_elm).find(".inlinehtml_task_html");
		var layout_ui_editor_elm = inlinehtml_task_html.children(".layout-ui-editor");
		//console.log(layout_ui_editor_elm[0]);
		//console.log(layout_ui_editor_elm.data("LayoutUIEditor"));
		
		if (layout_ui_editor_elm[0] && !layout_ui_editor_elm.data("LayoutUIEditor") && typeof LayoutUIEditor == "function") {
			//add InlineHTMLTaskPropertyObj.layout_ui_editor_menu_widgets_elm_selector in <ul class="menu-widgets hidden"></ul>
			if (InlineHTMLTaskPropertyObj.layout_ui_editor_menu_widgets_elm_selector) {
				var mwb = $(InlineHTMLTaskPropertyObj.layout_ui_editor_menu_widgets_elm_selector);
				
				if (mwb[0]) {
					var menu_widgets = layout_ui_editor_elm.children(".menu-widgets");
					
					if (!menu_widgets[0]) {
						menu_widgets = $('<ul class="menu-widgets hidden"></ul>');
						layout_ui_editor_elm.append(menu_widgets);
					}
				
					menu_widgets.append( mwb.contents().clone() );
				}
			}
			
			//init LayoutUIEditor
			var ptl_ui_creator_var_name = "PTLLayoutUIEditor_" + Math.floor(Math.random() * 1000);
			var PtlLayoutUIEditor = new LayoutUIEditor();
			PtlLayoutUIEditor.options.ui_element = layout_ui_editor_elm;
			PtlLayoutUIEditor.options.on_template_source_editor_save_func = InlineHTMLTaskPropertyObj.editor_save_func;
			PtlLayoutUIEditor.options.on_choose_variable_func = ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable;
			
			if (typeof ProgrammingTaskUtil.on_programming_task_choose_page_url_callback == "function")
				PtlLayoutUIEditor.options.on_choose_page_url_func = function(elm) {
					ProgrammingTaskUtil.onProgrammingTaskChoosePageUrl(elm);
					MyFancyPopup.settings.is_code_html_base = InlineHTMLTaskPropertyObj.is_code_html_base;
				}
			
			if (typeof ProgrammingTaskUtil.on_programming_task_choose_image_url_callback == "function")
				PtlLayoutUIEditor.options.on_choose_image_url_func = function(elm) {
					ProgrammingTaskUtil.onProgrammingTaskChooseImageUrl(elm);
					MyFancyPopup.settings.is_code_html_base = InlineHTMLTaskPropertyObj.is_code_html_base;
				}
			
			if (typeof ProgrammingTaskUtil.on_programming_task_choose_webroot_file_url_callback == "function")
				PtlLayoutUIEditor.options.on_choose_webroot_file_url_func = function(elm) {
					ProgrammingTaskUtil.onProgrammingTaskChooseWebrootFileUrl(elm);
					MyFancyPopup.settings.is_code_html_base = InlineHTMLTaskPropertyObj.is_code_html_base;
				}
			
			PtlLayoutUIEditor.options.on_ready_func = function() {
				//show php widgets, borders and background
				PtlLayoutUIEditor.showTemplateWidgetsDroppableBackground();
				PtlLayoutUIEditor.showTemplateWidgetsBorders();
				PtlLayoutUIEditor.showTemplatePHPWidgets();
				
				//runf ready function
				if (typeof InlineHTMLTaskPropertyObj.editor_ready_func == "function")
					InlineHTMLTaskPropertyObj.editor_ready_func(PtlLayoutUIEditor);
			};
			window[ptl_ui_creator_var_name] = PtlLayoutUIEditor;
			PtlLayoutUIEditor.init(ptl_ui_creator_var_name);
				
			PtlLayoutUIEditor.setTemplateSourceEditorValue(code);
			
			inlinehtml_task_html.data("ptl_editor", PtlLayoutUIEditor);
		}
		else {
			inlinehtml_task_html.children("ul, #inlinehtml_code, #inlinehtml_wysiwyg").show();
			
			var textarea = inlinehtml_task_html.find(" > #inlinehtml_code > textarea").first();
			textarea.val(code);
			
			if (ace && ace.edit && textarea[0]) {
				var parent = textarea.parent();
				
				ace.require("ace/ext/language_tools");
				var editor = ace.edit( textarea[0] );
				editor.setTheme("ace/theme/chrome");
				editor.session.setMode("ace/mode/html");
				editor.setAutoScrollEditorIntoView(true);
				editor.setOption("minLines", 30);
				editor.setOptions({
					enableBasicAutocompletion: true,
					enableSnippets: true,
					enableLiveAutocompletion: false,
				});
				
				editor.focus();
				
				parent.find("textarea.ace_text-input").removeClass("ace_text-input"); //fixing problem with scroll up, where when focused or pressed key inside editor the page scrolls to top
				
				inlinehtml_task_html.data("code_editor", editor);
			}
			
			if (typeof CKEDITOR != "undefined") {
				inlinehtml_task_html.tabs();
				
				var textarea = inlinehtml_task_html.find(" > #inlinehtml_wysiwyg > textarea").first();
				textarea.val(code);
				
				var editor = CKEDITOR.replace(textarea[0], {
					toolbarGroups: [
						{ name: "forms" },
						{ name: "basicstyles", groups: [ "basicstyles", "cleanup" ] },
						{ name: "paragraph", groups: [ "list", "indent", "blocks", "align", "bidi" ] },
						{ name: "links" },
						{ name: "insert" },
						{ name: "styles" },
						{ name: "colors" },
						{ name: "tools" },
						{ name: "others" },
						{ name: "document", groups: [ "mode" ] },
					],
					codeSnippet_theme: "monokai_sublime",
					height: textarea.height(),
				});
			
				CKEDITOR.config.removeDialogTabs = 'link:upload;image:Upload';
				CKEDITOR.config.removePlugins = 'elementspath';
				CKEDITOR.config.resize_enabled = false;
			
				inlinehtml_task_html.data("wysiwyg_editor", editor);
			}
			else
				inlinehtml_task_html.children("ul, #inlinehtml_wysiwyg").hide();
		}
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		var code = "";
		var inlinehtml_task_html = $(properties_html_elm).find(".inlinehtml_task_html");
		var ptl_editor = inlinehtml_task_html.data("ptl_editor");
		
		if (ptl_editor) {
			ptl_editor.forceTemplateSourceConversionAutomatically(); //Be sure that the template source is selected
			code = ptl_editor.getTemplateSourceEditorValue();
		}
		else if (inlinehtml_task_html.find(" > ul > #inlinehtml_code_editor_tab").hasClass("ui-tabs-selected") || inlinehtml_task_html.find(" > ul > #inlinehtml_code_editor_tab").hasClass("ui-tabs-active")) {
			var code_editor = inlinehtml_task_html.data("code_editor");
			
			if (code_editor)
				code = code_editor.getValue();
			else
				code = inlinehtml_task_html.find(" > #inlinehtml_code > textarea").first().val();
		}
		else {
			var wysiwyg_editor = inlinehtml_task_html.data("wysiwyg_editor");
			
			if (wysiwyg_editor)
				code = wysiwyg_editor.getData();
			else
				code = inlinehtml_task_html.find(" > #inlinehtml_wysiwyg > textarea").first().val();
		}
		
		inlinehtml_task_html.find(" > textarea.task_property_field").first().val(code);
		
		//cleans editors from browser cache
		inlinehtml_task_html.data("ptl_editor", null);
		inlinehtml_task_html.data("code_editor", null);
		inlinehtml_task_html.data("wysiwyg_editor", null);
		
		InlineHTMLTaskPropertyObj.ptl_editor = ptl_editor; //sets this temporary to be use in the onCompleteTaskProperties
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var label = InlineHTMLTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
			
			//When the onCompleteTaskProperties function is called, the myWFObj.getTaskFlowChart().Property.hideSelectedTaskProperties() function was already called, which removed the html from properties popup. This means that we can call then the InlineHTMLTaskPropertyObj.ptl_editor.resetDroppables().
			
			//check all droppables from jquery and resetting, this is, remove the invalid droppables.
			//if we create a jquery droppable element inside of an iframe and then move the iframe or delete it or reload it, the droppable elements stop to exist, but jquery continues with them registered. So we need to call the resetDroppables() function to fix this cases, otherwise we will have weird javascript errors.
			if (InlineHTMLTaskPropertyObj.ptl_editor) { //InlineHTMLTaskPropertyObj.ptl_editor was set in the onSubmitTaskProperties
				InlineHTMLTaskPropertyObj.ptl_editor.resetDroppables();
				InlineHTMLTaskPropertyObj.ptl_editor = null;
			}
		}
	},
	
	onCancelTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		//console.log("onCancelTaskProperties");
		var inlinehtml_task_html = $(properties_html_elm).find(".inlinehtml_task_html");
		var ptl_editor = inlinehtml_task_html.data("ptl_editor");
		
		//cleans editors from browser cache
		inlinehtml_task_html.data("ptl_editor", null);
		inlinehtml_task_html.data("code_editor", null);
		inlinehtml_task_html.data("wysiwyg_editor", null);
		
		if (ptl_editor) {
			//hide popup first and empty html from properties popup
			myWFObj.getTaskFlowChart().Property.hideSelectedTaskProperties();
			
			//check all droppables from jquery and resetting, this is, remove the invalid droppables.
			//if we create a jquery droppable element inside of an iframe and then move the iframe or delete it or reload it, the droppable elements stop to exist, but jquery continues with them registered. So we need to call the resetDroppables() function to fix this cases, otherwise we will have weird javascript errors.
			//console.log(ptl_editor.resetDroppables);
			ptl_editor.resetDroppables();
		}
		
		return true;	
	},
	
	onCompleteLabel : function(task_id) {
		return ProgrammingTaskUtil.onEditLabel(task_id);
	},
	
	onTaskCreation : function(task_id) {
		setTimeout(function() {
			var task_property_values = myWFObj.getTaskFlowChart().TaskFlow.tasks_properties[task_id];
			var label = InlineHTMLTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
		
			onEditLabel(task_id);
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 50);
	},
	
	getDefaultExitLabel : function(task_property_values) {
		var code = task_property_values["code"] ? task_property_values["code"] : "";
		return code.substr(0, 500);
	},
	
	updateHtmlFromWysiwygEditor : function(elm) {
		elm = $(elm);
		
		var inlinehtml_task_html = elm.parent().parent().parent();
		var is_different = InlineHTMLTaskPropertyObj.isCodeEditorDifferentThanWysiwygEditor(inlinehtml_task_html);
		
		if (is_different && confirm("Do you wish to update this code from the WYSIWYG Editor?")) {
			var code = "";
			var code_editor = inlinehtml_task_html.data("code_editor");
			var wysiwyg_editor = inlinehtml_task_html.data("wysiwyg_editor");
			
			if (wysiwyg_editor)
				code = wysiwyg_editor.getData();
			else
				code = elm.parent().parent().parent().find("#inlinehtml_wysiwyg textarea").first().val();
			
			if (code_editor)
				code_editor.setValue(code, -1);
			else
				elm.parent().parent().parent().find("#inlinehtml_code textarea").first().val(code);
		}
	},
	
	updateHtmlFromCodeEditor : function(elm) {
		elm = $(elm);
		
		var inlinehtml_task_html = elm.parent().parent().parent();
		var is_different = InlineHTMLTaskPropertyObj.isCodeEditorDifferentThanWysiwygEditor(inlinehtml_task_html);
		
		if (is_different && confirm("Do you wish to update this code from the Code Editor?")) {
			var code = "";
			var code_editor = inlinehtml_task_html.data("code_editor");
			var wysiwyg_editor = inlinehtml_task_html.data("wysiwyg_editor");
			
			if (code_editor)
				code = code_editor.getValue();
			else
				code = elm.parent().parent().parent().find("#inlinehtml_code textarea").first().val();
			
			if (wysiwyg_editor)
				wysiwyg_editor.setData(code);
			else
				elm.parent().parent().parent().find("#inlinehtml_wysiwyg textarea").first().val(code);
		}
	},
	
	isCodeEditorDifferentThanWysiwygEditor : function(inlinehtml_task_html) {
		var code_1 = "";
		var code_editor = inlinehtml_task_html.data("code_editor");
		if (code_editor) 
			code_1 = code_editor.getValue();
		else 
			code_1 = inlinehtml_task_html.find("#inlinehtml_code textarea").first().val();
		
		var code_2 = "";
		var wysiwyg_editor = inlinehtml_task_html.data("wysiwyg_editor");
		if (wysiwyg_editor)
			code_2 = wysiwyg_editor.getData();
		else 
			code_2 = inlinehtml_task_html.find("#inlinehtml_wysiwyg textarea").first().val();
		
		return code_1.trim() != code_2.trim();
	},
};
