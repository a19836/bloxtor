var SetVarTaskPropertyObj = {
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".set_var_task_html");
		
		if (typeof ProgrammingTaskUtil.on_programming_task_choose_page_url_callback == "function")
			task_html_elm.addClass("with_search");
		
		ProgrammingTaskUtil.setResultVariableType(task_property_values, task_html_elm);
		
		var textarea = task_html_elm.find(".value textarea");
		var patt = new RegExp("<(/?)([a-zA-Z]+)");//contains html
		
		if (task_property_values["type"] == "date") {
			SetVarTaskPropertyObj.createDatePicker( task_html_elm.find(".value input") );
			textarea.hide();
			task_html_elm.find(".value .textarea").hide();
		}
		else if (task_property_values["value"] && (task_property_values["value"].indexOf("\n") != -1 || patt.test(task_property_values["value"].replace(/\n/g, "")))) { //if text has \n then the regex won't work. So we need to use .replace(/\n/g, "")
			task_html_elm.find(".value input").hide();
			task_html_elm.find(".value .add_variable").hide();
			task_html_elm.find(".value .maximize").show();
			
			textarea.show();
			textarea.val( task_property_values["value"] );
			
			SetVarTaskPropertyObj.createTextareEditor(textarea);
		}
		else
			textarea.hide();
		
		//PREPARING RESULT VAR TYPE
		var select = task_html_elm.find(".result .result_var_type select");
		select.find("option[value=\"\"]").remove();
		ProgrammingTaskUtil.onChangeResultVariableType(select[0]);
	},
	
	createTextareEditor : function(textarea) {
		if (ace && ace.edit && textarea[0]) {
			var value_div = textarea.parent();
			
			ace.require("ace/ext/language_tools");
			var editor = ace.edit( textarea[0] );
			editor.setTheme("ace/theme/chrome");
			editor.session.setMode({path:"ace/mode/php", inline:true});
			editor.setAutoScrollEditorIntoView(true);
			editor.setOptions({
				enableBasicAutocompletion: true,
				enableSnippets: true,
				enableLiveAutocompletion: false,
			});
			
			var var_type = value_div.parent().find(".var_type select").val();
			if (var_type == "string" || var_type == "date") {
				editor.getSession().setUseWorker(false);//disable the syntax checking/validation
			}
			
			value_div.data("editor", editor);
			
			editor.focus();
		}
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".set_var_task_html");
		ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithType(task_html_elm);
		ProgrammingTaskUtil.onSubmitResultVariableType(task_html_elm);
		
		if (!task_html_elm.find(".value input").is(':visible')) {
			task_html_elm.find(".value input").remove();
			
			var value_div = task_html_elm.find(".value");
			var editor = value_div.data("editor");
			var value = editor ? editor.getValue() : value_div.children("textarea").val();
			
			var textarea = $('<textarea class="task_property_field" name="value"></textarea>');
			value_div.append(textarea);
			textarea.val(value ? value : "");
		}
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var label = SetVarTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
		}
	},
	
	onCancelTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		return true;	
	},
	
	onCompleteLabel : function(task_id) {
		return ProgrammingTaskUtil.onEditLabel(task_id);
	},
	
	onTaskCreation : function(task_id) {
		setTimeout(function() {
			var task_property_values = myWFObj.getTaskFlowChart().TaskFlow.tasks_properties[task_id];
			ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithTaskPropertiesValues(task_property_values);
			
			var label = SetVarTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
			
			onEditLabel(task_id);
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 30);
	},
	
	getDefaultExitLabel : function(task_property_values) {
		return ProgrammingTaskUtil.getResultVariableString(task_property_values) + ProgrammingTaskUtil.getValueString(task_property_values["value"], task_property_values["type"]);
	},
	
	changeValueTextField : function(elm) {
		var parent = $(elm).parent();
		
		var input = parent.children("input");
		var textarea = parent.children("textarea");
		var search_icon = parent.children(".search");
		var editor = parent.data("editor");
		//var maximize_icon = parent.children(".maximize");
		
		if (input.is(':visible')) {
			input.hide();
			search_icon.hide();
			//maximize_icon.show();
			
			if (editor) {
				editor.setValue(input.val(), 1);
				parent.children(".ace_editor").show();
				editor.resize();
				editor.focus();
			}
			else {
				textarea.val( input.val() );
				textarea.show();
				
				this.createTextareEditor(textarea);
			}
			
			myWFObj.getTaskFlowChart().getMyFancyPopupObj().resizeOverlay();
		}
		else {
			if (editor) {
				parent.children(".ace_editor").hide();
				input.val( editor.getValue() );
			}
			else {
				textarea.hide();
				input.val( textarea.val() );
			}
			
			input.show();
			//maximize_icon.hide();
			
			if (typeof ProgrammingTaskUtil.on_programming_task_choose_page_url_callback == "function")
				search_icon.show();
		}
		
		ProgrammingTaskUtil.onChangeTaskFieldType(elm);
	},
	
	onChangeVarType : function(elm) {
		elm = $(elm);
		var type = elm.val();
			
		var value_div = elm.parent().parent().find(".value");
		var editor = value_div.data("editor");
		if (editor) {
			var enabled = type != "string" && type != "date";
			editor.getSession().setUseWorker(enabled);//enable/disable the syntax checking/validation
			
			if (!enabled) {
				value_div.find(".ace_editor .ace_gutter .ace_layer .ace_gutter-cell.ace_error").removeClass("ace_error");
			}
		}
		
		var input = value_div.children("input");
		
		if (type == "date") {
			input.show();
			value_div.children(".textarea, .maximize, .ace_editor, textarea").hide();
			
			this.createDatePicker(input);
		}
		else if (type == "variable") {
			input.datepicker("destroy");
			input.show();
			value_div.children(".textarea, .maximize, .ace_editor, textarea").hide();
		}
		else if (input.is(':visible')) {
			input.datepicker("destroy");
			value_div.children(".textarea").show();
		}
		
		ProgrammingTaskUtil.onChangeTaskFieldType(input[0]);
	},
	
	maximizeEditor : function(elm) {
		var parent = $(elm).parent();
		
		var maximize_icon = parent.children(".maximize");
		var textarea = parent.children("textarea");
		var editor = parent.data("editor");
		
		var element = editor ? parent.children(".ace_editor") : textarea;
		
		if (!element[0].hasAttribute("is_maximize")) {
			var popup = myWFObj.getTaskFlowChart().getMyFancyPopupObj().getPopup();
			var w = popup.width() - 20;
			var h = popup.height() - 20;
			var o = popup.offset();
			
			element.attr("width", element.width());
			element.attr("height", element.height());
			element.attr("position", element.css("position"));
			element.attr("z-index", element.css("z-index"));
			
			element.css({position: "absolute", width: w + "px", height: h + "px", "z-index": 6000});
			element.offset({top:o.top + 3, left:o.left + 3});
			element.attr("is_maximize", 1);
			
			maximize_icon.attr("position", maximize_icon.css("position"));
			maximize_icon.attr("z-index", maximize_icon.css("z-index"));
			
			maximize_icon.css({position: "absolute", "z-index": 6001});
			maximize_icon.offset({top:o.top + 7, left:o.left + w - 28});
		}
		else {
			element.removeAttr("is_maximize");
			
			element.css({
				position: element.attr("position"), 
				width: element.attr("width"), 
				height: element.attr("height"), 
				"z-index": element.attr("z-index"),
				top: "auto",
				left: "auto",
			});
			
			maximize_icon.css({
				position: maximize_icon.attr("position"), 
				"z-index": maximize_icon.attr("z-index"),
				top: "auto",
				left: "auto",
			});
		}
		
		if (editor) {
			editor.resize();
			editor.focus();
			
			setTimeout(function() {
				SetVarTaskPropertyObj.onChangeVarType( parent.parent().find(".var_type select")[0] );
			}, 500);
		}
	},
	
	createDatePicker : function(input) {
		input.datepicker({
			dateFormat: "dd/mm/yy",
			showWeek: true,
			firstDay: 1,
			beforeShow: function() {
				var zindex = input.css("z-index");
				var position = input.css("position");
				
				input.css({"z-index": 6006, "position": "relative"});
		
				$(".ui-datepicker").css("font-size", "10px");
				
				setTimeout(function() {
					input.css({"z-index": zindex, "position": position});
				}, 10);
			},
		});
	},
};
