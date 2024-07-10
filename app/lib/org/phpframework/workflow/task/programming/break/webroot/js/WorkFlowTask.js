var BreakTaskPropertyObj = {
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		
		var value = task_property_values["value"] ? "" + task_property_values["value"] : "";
		properties_html_elm.find(".break_task_html .value input").val(value);
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var label = BreakTaskPropertyObj.getDefaultExitLabel(task_property_values);
			//ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
			myWFObj.getTaskFlowChart().TaskFlow.getTaskById(task_id).attr("title", label).find(".info span").html(label);
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
			var label = BreakTaskPropertyObj.getDefaultExitLabel(task_property_values);
			//ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
			myWFObj.getTaskFlowChart().TaskFlow.getTaskById(task_id).attr("title", label).find(".info span").html(label);
		
			onEditLabel(task_id);
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 100);
	},
	
	getDefaultExitLabel : function(task_property_values) {
		return "Break" + (task_property_values["value"] ? " " + task_property_values["value"] : "");
	},
};
