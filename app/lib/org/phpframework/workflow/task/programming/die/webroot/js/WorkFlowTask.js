/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

var ExitTaskPropertyObj = {
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		
		var value = task_property_values["value"] ? "" + task_property_values["value"] + "" : "";
		value = task_property_values["type"] == "variable" && value.trim().substr(0, 1) == '$' ? value.trim().substr(1) : value;
		properties_html_elm.find(".exit_task_html .value input").val(value);
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var label = ExitTaskPropertyObj.getDefaultExitLabel(task_property_values);
			//ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
			myWFObj.getTaskFlowChart().TaskFlow.getTaskById(task_id).attr("title", label);
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
			var label = ExitTaskPropertyObj.getDefaultExitLabel(task_property_values);
			//ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
			myWFObj.getTaskFlowChart().TaskFlow.getTaskById(task_id).attr("title", label);
		
			onEditLabel(task_id);
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 80);
	},
	
	getDefaultExitLabel : function(task_property_values) {
		return "die(" + (task_property_values["value"] ? ProgrammingTaskUtil.getValueString(task_property_values["value"], task_property_values["type"]) : "") + ")";
	},
};
