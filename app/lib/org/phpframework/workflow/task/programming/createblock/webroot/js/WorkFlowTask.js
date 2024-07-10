var CreateBlockTaskPropertyObj = {
	
	brokers_options : null,
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".create_block_task_html");
		
		BrokerOptionsUtilObj.initFields(task_html_elm.find(".broker_method_obj"), CreateBlockTaskPropertyObj.brokers_options, task_property_values["method_obj"]);
		
		var module_id = task_property_values["module_id"] ? "" + task_property_values["module_id"] : "";
		module_id = task_property_values["module_id_type"] == "variable" && module_id.trim().substr(0, 1) == '$' ? module_id.trim().substr(1) : module_id;
		task_html_elm.find(".module_id input").val(module_id);
		
		var block_id = task_property_values["block_id"] ? "" + task_property_values["block_id"] : "";
		block_id = task_property_values["block_id_type"] == "variable" && block_id.trim().substr(0, 1) == '$' ? block_id.trim().substr(1) : block_id;
		task_html_elm.find(".block_id input").val(block_id);
		
		var block_settings = task_property_values["block_settings"] ? "" + task_property_values["block_settings"] : "";
		block_settings = task_property_values["block_settings_type"] == "variable" && block_settings.trim().substr(0, 1) == '$' ? block_settings.trim().substr(1) : block_settings;
		task_html_elm.find(".block_settings input").val(block_settings);
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var label = CreateBlockTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
			
			var default_method_obj_str = BrokerOptionsUtilObj.getDefaultBroker(CreateBlockTaskPropertyObj.brokers_options);
			if (!task_property_values["method_obj"] && default_method_obj_str) {
				task_property_values["method_obj"] = default_method_obj_str;
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
			var label = CreateBlockTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
		
			onEditLabel(task_id);
			
			var default_method_obj_str = BrokerOptionsUtilObj.getDefaultBroker(CreateBlockTaskPropertyObj.brokers_options);
			if (!task_property_values["method_obj"] && default_method_obj_str) {
				myWFObj.getTaskFlowChart().TaskFlow.tasks_properties[task_id]["method_obj"] = default_method_obj_str;
			}
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 100);
	},
	
	getDefaultExitLabel : function(task_property_values) {
		var method_obj = (task_property_values["method_obj"] && task_property_values["method_obj"].trim().substr(0, 1) != "$" ? "$" : "") + task_property_values["method_obj"];
		
		return method_obj + "->createBlock(" + ProgrammingTaskUtil.getValueString(task_property_values["module_id"], task_property_values["module_id_type"]) + ", " + ProgrammingTaskUtil.getValueString(task_property_values["block_id"], task_property_values["block_id_type"]) + ", " + ProgrammingTaskUtil.getValueString(task_property_values["block_settings"], task_property_values["block_settings_type"]) + ")";
	},
};
