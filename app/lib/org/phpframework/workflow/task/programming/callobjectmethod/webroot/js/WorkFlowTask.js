var CallObjectMethodTaskPropertyObj = {
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		ProgrammingTaskUtil.prepareEditSourceIcon(properties_html_elm);
		
		var task_html_elm = $(properties_html_elm).find(".call_object_method_task_html");
		CallObjectMethodTaskPropertyObj.setMethodObject(task_property_values, task_html_elm);
		ProgrammingTaskUtil.setIncludeFile(task_property_values, task_html_elm);
		ProgrammingTaskUtil.setResultVariableType(task_property_values, task_html_elm);
		
		var method_args = task_property_values["method_args"];
		
		ProgrammingTaskUtil.setArgs(method_args, task_html_elm.find(".method_args .args").first());
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".call_object_method_task_html");
		ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithType(task_html_elm);
		ProgrammingTaskUtil.onSubmitResultVariableType(task_html_elm);
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var label = CallObjectMethodTaskPropertyObj.getDefaultExitLabel(task_property_values);
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
		
			var label = CallObjectMethodTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
		
			onEditLabel(task_id);
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 100);
	},
	
	getDefaultExitLabel : function(task_property_values) {
		if (task_property_values["method_name"]) {
			var method_obj = (task_property_values["method_static"] != 1 && task_property_values["method_obj"].trim().substr(0, 1) != "$" ? "$" : "") + task_property_values["method_obj"];
		
			return ProgrammingTaskUtil.getResultVariableString(task_property_values) + method_obj + (task_property_values["method_static"] == 1 ? '::' : '->') + task_property_values["method_name"] + "(" + ProgrammingTaskUtil.getArgsString(task_property_values["method_args"]) + ")";
		}
		return "";
	},
	
	setMethodObject : function(task_property_values, task_html_elm) {
		if (task_property_values["method_obj"] && task_property_values["method_static"] == 0 && typeof task_property_values["method_obj"] == "string" && task_property_values["method_obj"].substr(0, 1) == '$')
			task_html_elm.find(".method_obj_name input").val( task_property_values["method_obj"].substr(1) );
	},
	
	onEditFile : function(elm) {
		ProgrammingTaskUtil.onEditSource(elm, $(elm).closest(".call_object_method_task_html"), "file");
	},
	
	onEditClass : function(elm) {
		ProgrammingTaskUtil.onEditSource(elm, $(elm).closest(".call_object_method_task_html"), "class");
	},
	
	onEditMethod : function(elm) {
		ProgrammingTaskUtil.onEditSource(elm, $(elm).closest(".call_object_method_task_html"), "method");
	},
};
