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

var CallHibernateObjectTaskPropertyObj = {
	
	on_choose_hibernate_object_callback : null,
	brokers_options : null,
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		ProgrammingTaskUtil.prepareEditSourceIcon(properties_html_elm);
		
		var task_html_elm = $(properties_html_elm).find(".call_hibernate_object_task_html");
		ProgrammingTaskUtil.setResultVariableType(task_property_values, task_html_elm);
		
		BrokerOptionsUtilObj.initFields(task_html_elm.find(".broker_method_obj"), CallHibernateObjectTaskPropertyObj.brokers_options, task_property_values["method_obj"]);
		
		var module_id = task_property_values["module_id"] ? "" + task_property_values["module_id"] + "" : "";
		module_id = task_property_values["module_id_type"] == "variable" && module_id.trim().substr(0, 1) == '$' ? module_id.trim().substr(1) : module_id;
		task_html_elm.find(".module_id input").val(module_id);
		
		var service_id = task_property_values["service_id"] ? "" + task_property_values["service_id"] + "" : "";
		service_id = task_property_values["service_id_type"] == "variable" && service_id.trim().substr(0, 1) == '$' ? service_id.trim().substr(1) : service_id;
		task_html_elm.find(".service_id input").val(service_id);
		
		LayerOptionsUtilObj.onLoadTaskProperties(task_html_elm, task_property_values);
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".call_hibernate_object_task_html");
		ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithType(task_html_elm);
		ProgrammingTaskUtil.onSubmitResultVariableType(task_html_elm);
		
		if (task_html_elm.find(".opts .options_type").val() == "array") {
			task_html_elm.find(".opts .options_code").remove();
		}
		else {
			task_html_elm.find(".opts .options").remove();
		}
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var label = CallHibernateObjectTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
			
			var default_method_obj_str = BrokerOptionsUtilObj.getDefaultBroker(CallHibernateObjectTaskPropertyObj.brokers_options);
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
			ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithTaskPropertiesValues(task_property_values);
		
			var label = CallHibernateObjectTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
		
			onEditLabel(task_id);
		
			var default_method_obj_str = BrokerOptionsUtilObj.getDefaultBroker(CallHibernateObjectTaskPropertyObj.brokers_options);
			if (!task_property_values["method_obj"] && default_method_obj_str) {
				myWFObj.getTaskFlowChart().TaskFlow.tasks_properties[task_id]["method_obj"] = default_method_obj_str;
			}
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 50);
	},
	
	getDefaultExitLabel : function(task_property_values) {
		if (task_property_values["module_id"] && task_property_values["service_id"]) {
			var method_obj = (task_property_values["method_obj"].trim().substr(0, 1) != "$" ? "$" : "") + task_property_values["method_obj"];
			var module = ProgrammingTaskUtil.getValueString(task_property_values["module_id"], task_property_values["module_id_type"]);
			var service = ProgrammingTaskUtil.getValueString(task_property_values["service_id"], task_property_values["service_id_type"]);
			var options = task_property_values["options_type"] == "array" ? ArrayTaskUtilObj.arrayToString(task_property_values["options"]) : ProgrammingTaskUtil.getValueString(task_property_values["options"], task_property_values["options_type"]);
			options = options ? options : "null";
			
			return ProgrammingTaskUtil.getResultVariableString(task_property_values) + method_obj + '->callObject(' + module + ", " + service + ", " + options + ")";
		}
		return "";
	},
	
	onChooseHibernateObject : function(elm) {
		if (typeof this.on_choose_hibernate_object_callback == "function") {
			this.on_choose_hibernate_object_callback(elm);
		}
	},
	
	onEditFile : function(elm) {
		ProgrammingTaskUtil.onEditSource(elm, $(elm).closest(".call_hibernate_object_task_html"), "file");
	},
	
	onEditObject : function(elm) {
		ProgrammingTaskUtil.onEditSource(elm, $(elm).closest(".call_hibernate_object_task_html"), "object");
	},
};
