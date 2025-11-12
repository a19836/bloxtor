/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

var DebugLogTaskPropertyObj = {
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".debug_log_task_html");
		
		//change the log_type from string to options if apply
		var options_exist = !task_property_values || !$.isPlainObject(task_property_values) || !task_property_values["log_type"] || (task_property_values["log_type_type"] == "string" && $.inArray(task_property_values["log_type"], ["debug", "info", "error", "exception"]) != -1);
		
		if (options_exist) {
			task_html_elm.find(".log_type .log_type_options").val( task_property_values["log_type"] );
			task_html_elm.find(".log_type .log_type_type").val("options");
		}
		
		DebugLogTaskPropertyObj.onChangeDebugLogTypeType(task_html_elm.find(".log_type .log_type_type")[0]);
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".debug_log_task_html");
		
		if (task_html_elm.find(".log_type .log_type_type").val() == "options") {
			task_html_elm.find(".log_type .log_type_code").remove();
			task_html_elm.find(".log_type .log_type_type").val("string");
		}
		else {
			task_html_elm.find(".log_type .log_type_options").remove();
		}
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var label = DebugLogTaskPropertyObj.getDefaultExitLabel(task_property_values);
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
		
			var label = DebugLogTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
		
			onEditLabel(task_id);
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 30);
	},
	
	getDefaultExitLabel : function(task_property_values) {
		var message = ProgrammingTaskUtil.getValueString(task_property_values["message"], task_property_values["message_type"]);
		var log_type = ProgrammingTaskUtil.getValueString(task_property_values["log_type"], task_property_values["log_type_type"]);
		log_type = log_type ? ", " + log_type : "null";
		
		return "debug_log(" + message + log_type + ")";
	},
	
	onChangeDebugLogTypeType : function(elm) {
		var log_type = $(elm).val();
		
		var parent = $(elm).parent();
		
		if (log_type == "options") {
			parent.find(".log_type_code").hide();
			parent.children(".log_type_options").show();
		}
		else {
			parent.find(".log_type_code").show();
			parent.children(".log_type_options").hide();
		}
		
		ProgrammingTaskUtil.onChangeTaskFieldType(elm);
	},
};
