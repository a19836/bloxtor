/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

var UploadTaskPropertyObj = {
	
	dependent_file_path_to_include : "LIB_PATH . 'org/phpframework/util/web/UploadHandler.php'",
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".upload_task_html");
		ProgrammingTaskUtil.setResultVariableType(task_property_values, task_html_elm);
		
		var validation = task_property_values["validation"];
		
		if (task_property_values["validation_type"] == "array") {
			ArrayTaskUtilObj.onLoadArrayItems( task_html_elm.find(".vldt_type .validation").first(), validation, "");
			task_html_elm.find(".vldt_type .validation_code").val("");
		}
		else {
			validation = validation ? "" + validation : "";
			validation = task_property_values["validation_type"] == "variable" && validation.trim().substr(0, 1) == '$' ? validation.trim().substr(1) : validation;
			task_html_elm.find(".vldt_type .validation_code").val(validation);
		}
		UploadTaskPropertyObj.onChangeValidationTypeType(task_html_elm.find(".vldt_type .validation_type")[0]);
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".upload_task_html");
		ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithType(task_html_elm);
		ProgrammingTaskUtil.onSubmitResultVariableType(task_html_elm);
		
		if (task_html_elm.find(".vldt_type .validation_type").val() == "array") {
			task_html_elm.find(".vldt_type .validation_code").remove();
		}
		else {
			task_html_elm.find(".vldt_type .validation").remove();
		}
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var label = UploadTaskPropertyObj.getDefaultExitLabel(task_property_values);
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
		
		ProgrammingTaskUtil.addIncludeFileTaskBeforeTaskIfNotExistsYet(task_id, UploadTaskPropertyObj.dependent_file_path_to_include, '', 1);
	},
	
	onTaskCreation : function(task_id) {
		setTimeout(function() {
			var task_property_values = myWFObj.getTaskFlowChart().TaskFlow.tasks_properties[task_id];
			ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithTaskPropertiesValues(task_property_values);
		
			var label = UploadTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
			
			onEditLabel(task_id);
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 30);
	},
	
	getDefaultExitLabel : function(task_property_values) {
		var file = ProgrammingTaskUtil.getValueString(task_property_values["file"], task_property_values["file_type"]);
		var dst_folder = ProgrammingTaskUtil.getValueString(task_property_values["dst_folder"], task_property_values["dst_folder_type"]);
		var validation = task_property_values["validation_type"] == "array" ? ArrayTaskUtilObj.arrayToString(task_property_values["validation"]) : ProgrammingTaskUtil.getValueString(task_property_values["validation"], task_property_values["validation_type"]);
		validation = validation ? ", " + validation : "null";
		
		return ProgrammingTaskUtil.getResultVariableString(task_property_values) + "UploadHandler::upload(" + file + ", " + dst_folder + validation + ")";
	},
	
	onChangeValidationTypeType : function(elm) {
		var validation_type = $(elm).val();
		
		var parent = $(elm).parent();
		var validation_elm = parent.children(".validation");
		
		if (validation_type == "array") {
			parent.find(".validation_code").hide();
			validation_elm.show();
			
			if (!validation_elm.find(".items")[0]) {
				var items = {0: {key_type: "null", value_type: "string"}};
				ArrayTaskUtilObj.onLoadArrayItems(validation_elm, items, "");
			}
		}
		else {
			parent.find(".validation_code").show();
			validation_elm.hide();
		}
		
		ProgrammingTaskUtil.onChangeTaskFieldType(elm);
	},
};
