/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

var CreateClassObjectTaskPropertyObj = {
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".create_class_object_task_html");
		ProgrammingTaskUtil.setResultVariableType(task_property_values, task_html_elm);
		
		var class_args = task_property_values["class_args"];
		
		ProgrammingTaskUtil.setArgs(class_args, task_html_elm.find(".class_args .args").first());
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".create_class_object_task_html");
		var class_name = task_html_elm.find(".class_name input").val();
		ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithType(task_html_elm, class_name);
		ProgrammingTaskUtil.onSubmitResultVariableType(task_html_elm);
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var label = CreateClassObjectTaskPropertyObj.getDefaultExitLabel(task_property_values);
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
			if (task_property_values) {
				var class_name = task_property_values["class_name"];
				ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithTaskPropertiesValues(task_property_values, class_name);
		
				var label = CreateClassObjectTaskPropertyObj.getDefaultExitLabel(task_property_values);
				ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
			}
		
			onEditLabel(task_id);
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 50);
	},
	
	getDefaultExitLabel : function(task_property_values) {
		return task_property_values["class_name"] ? ProgrammingTaskUtil.getResultVariableString(task_property_values) + task_property_values["class_name"] + "(" + ProgrammingTaskUtil.getArgsString(task_property_values["class_args"]) + ")" : "";
	},
};
