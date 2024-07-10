var GetDBDriverTaskPropertyObj = {
	
	brokers_options : null,
	db_drivers_options : null,
	default_db_driver_variable : "$GLOBALS['default_db_driver']",
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".get_db_driver_task_html");
		ProgrammingTaskUtil.setResultVariableType(task_property_values, task_html_elm);
		
		BrokerOptionsUtilObj.initFields(task_html_elm.find(".broker_method_obj"), GetDBDriverTaskPropertyObj.brokers_options, task_property_values["method_obj"]);
		
		var db_driver = task_property_values["db_driver"] ? task_property_values["db_driver"] : "";
		var select = task_html_elm.find(".db_driver select.db_driver_options");
		var input = task_html_elm.find(".db_driver input.db_driver_variable");
		var select_type = task_html_elm.find(".db_driver select[name=db_driver_type]");
		var is_default_db_driver = (
			task_property_values["db_driver_type"] == "variable" && (
				db_driver == GetDBDriverTaskPropertyObj.default_db_driver_variable || 
				('$' + db_driver) == GetDBDriverTaskPropertyObj.default_db_driver_variable || 
				db_driver == GetDBDriverTaskPropertyObj.default_db_driver_variable.replace(/'/g, '"') || 
				('$' + db_driver) == GetDBDriverTaskPropertyObj.default_db_driver_variable.replace(/'/g, '"')
			)
		) || (
			!task_property_values["db_driver_type"] && ( //if is type: code
				db_driver == GetDBDriverTaskPropertyObj.default_db_driver_variable ||
				db_driver == GetDBDriverTaskPropertyObj.default_db_driver_variable.replace(/'/g, '"')
			)
		);
		
		GetDBDriverTaskPropertyObj.updateDBDriverTypeOptions(select[0]);
		
		if ($.isArray(GetDBDriverTaskPropertyObj.db_drivers_options) && GetDBDriverTaskPropertyObj.db_drivers_options.length > 0 && (
			jQuery.isEmptyObject(task_property_values) || 
			db_driver == "" ||
			(task_property_values["db_driver_type"] == "string" && $.inArray(db_driver, GetDBDriverTaskPropertyObj.db_drivers_options) != -1) ||
			is_default_db_driver
		)) {
			input.hide();
			select.show();
			select.val(is_default_db_driver ? "" : db_driver);
			input.val(db_driver); //set value to input too
			select_type.val("db_drivers");
		}
		else {
			input.show();
			select.hide();
			
			db_driver = "" + db_driver + "";
			db_driver = db_driver.trim().substr(0, 1) == '$' ? db_driver.trim().substr(1) : db_driver;
			input.val(db_driver);
		}
		GetDBDriverTaskPropertyObj.onChangeDBDriverType(select_type[0]);
	},
	
	onChangeDBDriverType : function(elm) {
		var db_driver_type = $(elm).val();
		var parent = $(elm).parent();
		
		if (db_driver_type == "db_drivers") {
			parent.children("input.db_driver_variable").hide();
			parent.children("select.db_driver_options").show();
		}
		else {
			parent.children("input.db_driver_variable").show();
			parent.children("select.db_driver_options").hide();
		}
		
		ProgrammingTaskUtil.onChangeTaskFieldType(elm);
	},
	
	updateDBDriverTypeOptions : function(elm) {
		var html = '<option value="' + this.default_db_driver_variable + '">-- default --</option>';
		
		if ($.isArray(this.db_drivers_options))
			$.each(this.db_drivers_options, function(idx, db_driver) {
				html += '<option>' + db_driver + '</option>';
			});
		
		$(elm).html(html);
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".get_db_driver_task_html");
		ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithType(task_html_elm);
		ProgrammingTaskUtil.onSubmitResultVariableType(task_html_elm);
		
		var db_driver = "";
		var db_driver_type = task_html_elm.find(".db_driver select[name=db_driver_type]").val();
		
		if (db_driver_type == "db_drivers") {
			db_driver = task_html_elm.find(".db_driver select.db_driver_options").val();
			task_html_elm.find(".db_driver select[name=db_driver_type]").val(db_driver.charAt(0) == '$' ? "variable" : "string");
		}
		else
			db_driver = task_html_elm.find(".db_driver .db_driver_variable").val();
		
		task_html_elm.find(".db_driver input.db_driver_value").val( db_driver ? db_driver : "" );
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var label = GetDBDriverTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
			
			var default_method_obj_str = BrokerOptionsUtilObj.getDefaultBroker(GetDBDriverTaskPropertyObj.brokers_options);
			if (!task_property_values["method_obj"] && default_method_obj_str)
				task_property_values["method_obj"] = default_method_obj_str;
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
		
			var label = GetDBDriverTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
		
			onEditLabel(task_id);
		
			var default_method_obj_str = BrokerOptionsUtilObj.getDefaultBroker(GetDBDriverTaskPropertyObj.brokers_options);
			if (!task_property_values["method_obj"] && default_method_obj_str)
				myWFObj.getTaskFlowChart().TaskFlow.tasks_properties[task_id]["method_obj"] = default_method_obj_str;
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 30);
	},
	
	getDefaultExitLabel : function(task_property_values) {
		if (task_property_values["db_driver"]) {
			var method_obj = (task_property_values["method_obj"].trim().substr(0, 1) != "$" ? "$" : "") + task_property_values["method_obj"];
			var db_driver = ProgrammingTaskUtil.getValueString(task_property_values["db_driver"], task_property_values["db_driver_type"]);
			
			return ProgrammingTaskUtil.getResultVariableString(task_property_values) + method_obj + '->getBroker(' + db_driver + ')';
		}
		return "";
	},
};
