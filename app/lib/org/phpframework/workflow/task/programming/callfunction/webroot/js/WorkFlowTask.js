/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

var CallFunctionTaskPropertyObj = {
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		ProgrammingTaskUtil.prepareEditSourceIcon(properties_html_elm);
		
		var task_html_elm = $(properties_html_elm).find(".call_function_task_html");
		ProgrammingTaskUtil.setIncludeFile(task_property_values, task_html_elm);
		ProgrammingTaskUtil.setResultVariableType(task_property_values, task_html_elm);
		
		var func_args = task_property_values["func_args"];
		
		ProgrammingTaskUtil.setArgs(func_args, task_html_elm.find(".func_args .args").first());
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".call_function_task_html");
		ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithType(task_html_elm);
		ProgrammingTaskUtil.onSubmitResultVariableType(task_html_elm);
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var label = CallFunctionTaskPropertyObj.getDefaultExitLabel(task_property_values);
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
		
			var label = CallFunctionTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
		
			onEditLabel(task_id);
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 100);
	},
	
	getDefaultExitLabel : function(task_property_values) {
		return task_property_values["func_name"] ? ProgrammingTaskUtil.getResultVariableString(task_property_values) + task_property_values["func_name"] + "(" + ProgrammingTaskUtil.getArgsString(task_property_values["func_args"]) + ")" : "";
	},
	
	onEditFile : function(elm) {
		ProgrammingTaskUtil.onEditSource(elm, $(elm).closest(".call_function_task_html"), "file");
	},
	
	onEditFunction : function(elm) {
		ProgrammingTaskUtil.onEditSource(elm, $(elm).closest(".call_function_task_html"), "function");
	},
};
