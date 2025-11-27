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

var ValidatorTaskPropertyObj = {
	
	dependent_file_path_to_include : "LIB_PATH . 'org/phpframework/util/text/TextValidator.php'",
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".validator_task_html");
		ProgrammingTaskUtil.setResultVariableType(task_property_values, task_html_elm);
		
		var value = task_property_values["value"] ? "" + task_property_values["value"] : "";
		value = task_property_values["value_type"] == "variable" && value.trim().substr(0, 1) == '$' ? value.trim().substr(1) : value;
		task_html_elm.find(".value input").val(value);
		
		var offset = task_property_values["offset"] ? "" + task_property_values["offset"] : "";
		offset = task_property_values["offset_type"] == "variable" && offset.trim().substr(0, 1) == '$' ? offset.trim().substr(1) : offset;
		task_html_elm.find(".offset input").val(offset);
		
		ValidatorTaskPropertyObj.onChangeMethodName( task_html_elm.find(".method select")[0] );
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".validator_task_html");
		ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithType(task_html_elm);
		ProgrammingTaskUtil.onSubmitResultVariableType(task_html_elm);
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var label = ValidatorTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
		}
	},
	
	onCancelTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		return true;	
	},
	
	onCompleteLabel : function(task_id) {
		return ProgrammingTaskUtil.onEditLabel(task_id);
	},
	
	onTaskCloning : function(task_id) {
		ProgrammingTaskUtil.onTaskCloning(task_id);
		
		ProgrammingTaskUtil.addIncludeFileTaskBeforeTaskIfNotExistsYet(task_id, ValidatorTaskPropertyObj.dependent_file_path_to_include, '', 1);
	},
	
	onTaskCreation : function(task_id) {
		setTimeout(function() {
			var task_property_values = myWFObj.getTaskFlowChart().TaskFlow.tasks_properties[task_id];
			ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithTaskPropertiesValues(task_property_values);
		
			var label = ValidatorTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
			
			onEditLabel(task_id);
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 30);
	},
	
	getDefaultExitLabel : function(task_property_values) {
		if (task_property_values["method"]) {
			var offset = "";
			
			if (("" + task_property_values["method"]).indexOf("TextValidator::check") === 0)
				offset += ", " + ProgrammingTaskUtil.getValueString(task_property_values["offset"], task_property_values["offset_type"]);
			
			return ProgrammingTaskUtil.getResultVariableString(task_property_values) + task_property_values["method"] + "(" + ProgrammingTaskUtil.getValueString(task_property_values["value"], task_property_values["value_type"]) + offset + ")";
		}
		return "";
	},
	
	onChangeMethodName : function(elm) {
		var elm = $(elm);
		var task_html_elm = elm.parent().closest(".validator_task_html");
		var method = elm.val();
		var offset_div = task_html_elm.children(".offset");
		
		if (method.indexOf("TextValidator::check") === 0) {
			offset_div.show();
			
			var label = method.indexOf("Length") != -1 ? "Length" : (
				method.indexOf("Min") != -1 ? "Min" : (
					method.indexOf("Max") != -1 ? "Max" : "Offset"
				)
			);
			offset_div.children("label").html(label);
		}
		else
			offset_div.hide();
	},
};
