var TryCatchExceptionTaskPropertyObj = {
	previous_task_property_values : null,
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		
		var var_name = task_property_values["var_name"] ? "" + task_property_values["var_name"] + "" : "";
		var_name = var_name.trim().substr(0, 1) == '$' ? var_name.trim().substr(1) : var_name;
		properties_html_elm.find(".try_catch_exception_task_html .var_name input").val(var_name);
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		TryCatchExceptionTaskPropertyObj.previous_task_property_values = myWFObj.getTaskFlowChart().TaskFlow.tasks_properties[task_id];
		
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var labels = TryCatchExceptionTaskPropertyObj.getExitLabels(task_property_values);
			ProgrammingTaskUtil.updateTaskExitsLabels(task_id, labels);
			
			//update labels on all connections but without user label, for only the catch exit
			if (labels) {
				var labels_to_update = {};
				var previous_task_property_values = TryCatchExceptionTaskPropertyObj.previous_task_property_values;
				
				if (!previous_task_property_values || previous_task_property_values["class_name"] != task_property_values["class_name"] || previous_task_property_values["var_name"] != task_property_values["var_name"])
					labels_to_update["catch"] = labels["catch"];
				
				//update exits that were changed
				ProgrammingTaskUtil.updateTaskExitsConnectionExitLabelAttribute(task_id, labels_to_update);
				ProgrammingTaskUtil.updateTaskExitsConnectionsLabels(task_id, labels_to_update);
			}
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
			var labels = TryCatchExceptionTaskPropertyObj.getExitLabels(task_property_values);
			ProgrammingTaskUtil.updateTaskExitsLabels(task_id, labels);
			ProgrammingTaskUtil.updateTaskExitsConnectionExitLabelAttribute(task_id, labels);
		
			onEditLabel(task_id);
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 30);
	},
	
	getExitLabels : function(task_property_values) {
		var label = task_property_values && task_property_values["class_name"] && task_property_values["var_name"] ? task_property_values["class_name"] + " " + ProgrammingTaskUtil.getValueString(task_property_values["var_name"], "variable") : "";
		var labels = {"try": "No exception", "catch": label}; //bc of old diagrams where task_property_values["exits"] don't have the labels.
		
		if (task_property_values && task_property_values["exits"]) {
			var exits = task_property_values["exits"];
			labels["try"] = exits["try"] && exits["try"]["label"] ? exits["try"]["label"] : labels["try"];
			
			if (!labels["catch"])
				labels["catch"] = exits["catch"] && exits["catch"]["label"] ? exits["catch"]["label"] : labels["catch"];
		}
		
		return labels;
	},
};
