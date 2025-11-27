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

var SetQueryDataTaskPropertyObj = {
	
	brokers_options : null,
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".set_query_data_task_html");
		ProgrammingTaskUtil.setResultVariableType(task_property_values, task_html_elm);
		
		BrokerOptionsUtilObj.initFields(task_html_elm.find(".broker_method_obj"), SetQueryDataTaskPropertyObj.brokers_options, task_property_values["method_obj"]);
		
		LayerOptionsUtilObj.onLoadTaskProperties(task_html_elm, task_property_values);
		
		var sql = task_property_values["sql"] ? task_property_values["sql"] : "";
		var textarea = task_html_elm.find(".sql textarea.sql_editor");
		var input = task_html_elm.find(".sql input.sql_variable");
		
		if (jQuery.isEmptyObject(task_property_values) || task_property_values["sql_type"] == "string") {
			input.hide();
			textarea.show();
			
			textarea.val(sql);
		}
		else {
			input.show();
			textarea.hide();
			
			sql = "" + sql + "";
			sql = sql.trim().substr(0, 1) == '$' ? sql.trim().substr(1) : sql;
			input.val(sql);
		}
		SetQueryDataTaskPropertyObj.onChangeSqlType( task_html_elm.find(".sql select")[0] );
		
		if (ace && ace.edit && textarea[0]) {
			ace.require("ace/ext/language_tools");
			var editor = ace.edit( textarea[0] );
			editor.setTheme("ace/theme/chrome");
			editor.session.setMode("ace/mode/sql");
			editor.setAutoScrollEditorIntoView(true);
			editor.setOptions({
				enableBasicAutocompletion: true,
				enableSnippets: true,
				enableLiveAutocompletion: false,
			});
			
			var parent = task_html_elm.find(".sql");
			
			parent.find("textarea.ace_text-input").removeClass("ace_text-input"); //fixing problem with scroll up, where when focused or pressed key inside editor the page scrolls to top
			
			parent.data("editor", editor);
		}
		
		if (!jQuery.isEmptyObject(task_property_values) && task_property_values["sql_type"] != "string") {
			task_html_elm.find(".sql textarea.sql_editor, .sql .ace_editor").hide();
		}
	},
	
	onChangeSqlType : function(elm) {
		var sql_type = $(elm).val();
		var parent = $(elm).parent();
		
		if (sql_type == "string") {
			parent.children("input.sql_variable").hide();
			parent.children("textarea.sql_editor, .ace_editor").show();
			
			var editor = parent.data("editor");
			if (editor) {
				editor.resize();
				editor.focus();
			}
		}
		else {
			parent.children("input.sql_variable").show();
			parent.children("textarea.sql_editor, .ace_editor").hide();
		}
		
		ProgrammingTaskUtil.onChangeTaskFieldType(elm);
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".set_query_data_task_html");
		ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithType(task_html_elm);
		ProgrammingTaskUtil.onSubmitResultVariableType(task_html_elm);
		
		if (task_html_elm.find(".opts .options_type").val() == "array") {
			task_html_elm.find(".opts .options_code").remove();
		}
		else {
			task_html_elm.find(".opts .options").remove();
		}
		
		var sql = "";
		if (task_html_elm.find(".sql select").val() == "string") {
			var editor = task_html_elm.find(".sql").data("editor");
			sql = editor ? editor.getValue() : task_html_elm.find(".sql textarea.sql_editor").val();
		}
		else {
			sql = task_html_elm.find(".sql .sql_variable").val();
		}
		task_html_elm.find(".sql textarea.sql_value").val( sql ? sql : "" );
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var label = SetQueryDataTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
			
			var default_method_obj_str = BrokerOptionsUtilObj.getDefaultBroker(SetQueryDataTaskPropertyObj.brokers_options);
			if (!task_property_values["method_obj"] && default_method_obj_str) {
				task_property_values["method_obj"] = default_method_obj_str;
			}
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
		
			var label = SetQueryDataTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
		
			onEditLabel(task_id);
		
			var default_method_obj_str = BrokerOptionsUtilObj.getDefaultBroker(SetQueryDataTaskPropertyObj.brokers_options);
			if (!task_property_values["method_obj"] && default_method_obj_str) {
				myWFObj.getTaskFlowChart().TaskFlow.tasks_properties[task_id]["method_obj"] = default_method_obj_str;
			}
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 100);
	},
	
	getDefaultExitLabel : function(task_property_values) {
		if (task_property_values["sql"]) {
			var method_obj = (task_property_values["method_obj"].trim().substr(0, 1) != "$" ? "$" : "") + task_property_values["method_obj"];
			var sql = ProgrammingTaskUtil.getValueString(task_property_values["sql"], task_property_values["sql_type"]);
			
			var options = task_property_values["options_type"] == "array" ? ArrayTaskUtilObj.arrayToString(task_property_values["options"]) : ProgrammingTaskUtil.getValueString(task_property_values["options"], task_property_values["options_type"]);
			options = options ? options : "null";
			
			return ProgrammingTaskUtil.getResultVariableString(task_property_values) + method_obj + '->setData(' + sql + ", " + options + ")";
		}
		return "";
	},
};
