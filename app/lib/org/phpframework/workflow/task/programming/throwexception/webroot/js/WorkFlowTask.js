var ThrowExceptionTaskPropertyObj = {
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".throw_exception_task_html");
		ProgrammingTaskUtil.setResultVariableType(task_property_values, task_html_elm);
		
		var class_args = task_property_values["class_args"];
		
		ProgrammingTaskUtil.setArgs(class_args, task_html_elm.find(".class_args .args").first());
		
		var select = task_html_elm.find(".exception_type select");
		select.val(task_property_values["exception_type"]);
		ThrowExceptionTaskPropertyObj.onChangeExceptionType(select[0]);
		
		var exception_var_name = task_property_values["exception_var_name"] ? "" + task_property_values["exception_var_name"] + "" : "";
		exception_var_name = task_property_values["exception_var_type"] == "variable" && exception_var_name.trim().substr(0, 1) == '$' ? exception_var_name.trim().substr(1) : exception_var_name;
		task_html_elm.find(".exception_var_name input").val(exception_var_name);
	},
	
	onChangeExceptionType : function(elm) {
		elm = $(elm);
		
		var main_div = elm.parent().parent();
		var type = elm.val();
		
		if (type == "new") {
			main_div.children(".result, .class_name, .class_args").show();
			main_div.children(".exception_var_name, .exception_var_type").hide();
		}
		else {
			main_div.children(".result, .class_name, .class_args").hide();
			main_div.children(".exception_var_name, .exception_var_type").show();
		}
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".throw_exception_task_html");
		var class_name = task_html_elm.find(".class_name input").val();
		ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithType(task_html_elm, class_name);
		ProgrammingTaskUtil.onSubmitResultVariableType(task_html_elm);
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var label = ThrowExceptionTaskPropertyObj.getDefaultExitLabel(task_property_values);
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
		
				var label = ThrowExceptionTaskPropertyObj.getDefaultExitLabel(task_property_values);
				ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
			}
		
			onEditLabel(task_id);
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 100);
	},
	
	getDefaultExitLabel : function(task_property_values) {
		var label = "";
		
		if (task_property_values["exception_type"] == "new") {
			label = task_property_values["class_name"] ? "throw " + ProgrammingTaskUtil.getResultVariableString(task_property_values) + task_property_values["class_name"] + "(" + ProgrammingTaskUtil.getArgsString(task_property_values["class_args"]) + ")" : "";
		}
		else {
			label = task_property_values["exception_var_name"] ? "throw " + ProgrammingTaskUtil.getValueString(task_property_values["exception_var_name"], task_property_values["exception_var_type"]) : "";
		}
		
		return label;
	},
};
