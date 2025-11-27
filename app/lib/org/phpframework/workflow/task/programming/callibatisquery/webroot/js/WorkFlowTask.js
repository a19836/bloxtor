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

var CallIbatisQueryTaskPropertyObj = {
	
	on_choose_query_callback : null,
	brokers_options : null,
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		ProgrammingTaskUtil.prepareEditSourceIcon(properties_html_elm);
		
		var task_html_elm = $(properties_html_elm).find(".call_ibatis_query_task_html");
		ProgrammingTaskUtil.setResultVariableType(task_property_values, task_html_elm);
		
		BrokerOptionsUtilObj.initFields(task_html_elm.find(".broker_method_obj"), CallIbatisQueryTaskPropertyObj.brokers_options, task_property_values["method_obj"]);
		
		var module_id = task_property_values["module_id"] ? "" + task_property_values["module_id"] + "" : "";
		module_id = task_property_values["module_id_type"] == "variable" && module_id.trim().substr(0, 1) == '$' ? module_id.trim().substr(1) : module_id;
		task_html_elm.find(".module_id input").val(module_id);
		
		var service_type = task_property_values["service_type"] ? "" + task_property_values["service_type"] + "" : "";
		service_type = service_type.toLowerCase();
		if (jQuery.isEmptyObject(task_property_values) || task_property_values["service_type_type"] == "string") {
			task_html_elm.find(".service_type select.service_type_string").val(service_type);
			task_html_elm.find(".service_type input.service_type_code").hide();
		}
		else {
			service_type = task_property_values["service_type_type"] == "variable" && service_type.trim().substr(0, 1) == '$' ? service_type.trim().substr(1) : service_type;
			task_html_elm.find(".service_type input.service_type_code").val(service_type);
			task_html_elm.find(".service_type select.service_type_string").hide();
		}
		CallIbatisQueryTaskPropertyObj.onChangeServiceType( task_html_elm.find(".service_type select.service_type_type") );
		
		var service_id = task_property_values["service_id"] ? "" + task_property_values["service_id"] + "" : "";
		service_id = task_property_values["service_id_type"] == "variable" && service_id.trim().substr(0, 1) == '$' ? service_id.trim().substr(1) : service_id;
		task_html_elm.find(".service_id input").val(service_id);
		
		var parameters = task_property_values["parameters"];
		if (task_property_values["parameters_type"] == "array") {
			ArrayTaskUtilObj.onLoadArrayItems( task_html_elm.find(".params .parameters").first(), parameters, "");
			task_html_elm.find(".params .parameters_code").val("");
		}
		else {
			parameters = parameters ? "" + parameters + "" : "";
			parameters = task_property_values["parameters_type"] == "variable" && parameters.trim().substr(0, 1) == '$' ? parameters.trim().substr(1) : parameters;
			task_html_elm.find(".params .parameters_code").val(parameters);
		}
		CallIbatisQueryTaskPropertyObj.onChangeParametersType(task_html_elm.find(".params .parameters_type")[0]);
		
		LayerOptionsUtilObj.onLoadTaskProperties(task_html_elm, task_property_values);
	},
	
	onChangeServiceType : function(elm) {
		var service_type = $(elm).val();
		
		var parent = $(elm).parent();
		
		if (service_type == "string") {
			parent.children(".service_type_string").show();
			parent.children("input").hide();
		}
		else {
			parent.children(".service_type_string").hide();
			parent.children("input").show();
		}
		
		ProgrammingTaskUtil.onChangeTaskFieldType(elm);
	},
	
	onChangeParametersType : function(elm) {
		var parameters_type = $(elm).val();
		
		var parent = $(elm).parent();
		var parameters_elm = parent.children(".parameters");
		
		if (parameters_type == "array") {
			parent.find(".parameters_code").hide();
			parameters_elm.show();
			
			if (!parameters_elm.find(".items")[0]) {
				var items = {0: {key_type: "null", value_type: "string"}};
				ArrayTaskUtilObj.onLoadArrayItems(parameters_elm, items, "");
			}
		}
		else {
			parent.find(".parameters_code").show();
			parameters_elm.hide();
		}
		
		ProgrammingTaskUtil.onChangeTaskFieldType(elm);
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".call_ibatis_query_task_html");
		ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithType(task_html_elm);
		ProgrammingTaskUtil.onSubmitResultVariableType(task_html_elm);
		
		var service_type_type = task_html_elm.find(".service_type select.service_type_type").val();
		var service_type = service_type_type == "string" ? task_html_elm.find(".service_type select.service_type_string").val() : task_html_elm.find(".service_type input.service_type_code").val();
		task_html_elm.find(".service_type input.service_type").val(service_type);
		
		if (task_html_elm.find(".params .parameters_type").val() == "array") {
			task_html_elm.find(".params .parameters_code").remove();
		}
		else {
			task_html_elm.find(".params .parameters").remove();
		}
		
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
			var label = CallIbatisQueryTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
			
			var default_method_obj_str = BrokerOptionsUtilObj.getDefaultBroker(CallIbatisQueryTaskPropertyObj.brokers_options);
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
		
			var label = CallIbatisQueryTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
		
			onEditLabel(task_id);
		
			var default_method_obj_str = BrokerOptionsUtilObj.getDefaultBroker(CallIbatisQueryTaskPropertyObj.brokers_options);
			if (!task_property_values["method_obj"] && default_method_obj_str) {
				myWFObj.getTaskFlowChart().TaskFlow.tasks_properties[task_id]["method_obj"] = default_method_obj_str;
			}
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 100);
	},
	
	getDefaultExitLabel : function(task_property_values) {
		if (task_property_values["module_id"] && task_property_values["service_id"]) {
			var method_obj = (task_property_values["method_obj"].trim().substr(0, 1) != "$" ? "$" : "") + task_property_values["method_obj"];
			var method_name = task_property_values["query_type"] == 1 ? 'getSQL' : 'executeSQL';
			var module = ProgrammingTaskUtil.getValueString(task_property_values["module_id"], task_property_values["module_id_type"]);
			var type = ProgrammingTaskUtil.getValueString(task_property_values["service_type"], task_property_values["service_type_type"]);
			var service = ProgrammingTaskUtil.getValueString(task_property_values["service_id"], task_property_values["service_id_type"]);
			var parameters = task_property_values["parameters_type"] == "array" ? ArrayTaskUtilObj.arrayToString(task_property_values["parameters"]) : ProgrammingTaskUtil.getValueString(task_property_values["parameters"], task_property_values["parameters_type"]);
			parameters = parameters ? parameters : "null";
			
			
			var options = task_property_values["options_type"] == "array" ? ArrayTaskUtilObj.arrayToString(task_property_values["options"]) : ProgrammingTaskUtil.getValueString(task_property_values["options"], task_property_values["options_type"]);
			options = options ? options : "null";
			
			return ProgrammingTaskUtil.getResultVariableString(task_property_values) + method_obj + '->' + method_name + '(' + module + ", " + type + ", " + service + ", " + parameters + ", " + options + ")";
		}
		return "";
	},
	
	onChooseQuery : function(elm) {
		if (typeof this.on_choose_query_callback == "function") {
			this.on_choose_query_callback(elm);
		}
	},
	
	onEditFile : function(elm) {
		ProgrammingTaskUtil.onEditSource(elm, $(elm).closest(".call_ibatis_query_task_html"), "file");
	},
	
	onEditQuery : function(elm) {
		var task_html_elm = $(elm).closest(".call_ibatis_query_task_html");
		
		var service_type_type = task_html_elm.find(".service_type select.service_type_type").val();
		var service_type = service_type_type == "string" ? task_html_elm.find(".service_type select.service_type_string").val() : task_html_elm.find(".service_type input.service_type_code").val();
		task_html_elm.find(".service_type input.service_type").val(service_type);
		
		ProgrammingTaskUtil.onEditSource(elm, task_html_elm, "query");
	},
};
