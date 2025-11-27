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

var SetBlockParamsTaskPropertyObj = {
	
	main_variable_name : "block_local_variables",
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".set_block_params_task_html");
		
		if (typeof ProgrammingTaskUtil.on_programming_task_choose_page_url_callback == "function")
			task_html_elm.addClass("with_search");
		
		if (!task_html_elm.children("input.main_variable_name").val())
			task_html_elm.children("input.main_variable_name").val( SetBlockParamsTaskPropertyObj.main_variable_name );
		
		var value = task_property_values["value"] ? "" + task_property_values["value"] + "" : "";
		value = task_property_values["value_type"] == "variable" && value.trim().substr(0, 1) == '$' ? value.trim().substr(1) : value;
		task_html_elm.find(".value input").val(value);
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var label = SetBlockParamsTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
			
			SetBlockParamsTaskPropertyObj.saveNewVariable(task_property_values);
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
			SetBlockParamsTaskPropertyObj.saveNewVariable(task_property_values);
		
			var label = SetBlockParamsTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
		
			onEditLabel(task_id);
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 100);
	},
	
	getDefaultExitLabel : function(task_property_values) {
		var mvn = task_property_values["main_variable_name"] ? task_property_values["main_variable_name"] : this.main_variable_name;
		
		return '$' + mvn + ' = ' + ProgrammingTaskUtil.getValueString(task_property_values["value"], task_property_values["type"]);
	},
	
	saveNewVariable : function(task_property_values) {
		var mvn = task_property_values["main_variable_name"] ? task_property_values["main_variable_name"] : this.main_variable_name;
		ProgrammingTaskUtil.variables_in_workflow[mvn] = {};
	},
};
