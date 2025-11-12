/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

var callPresentationLayerWebServiceTaskPropertyObj = {
	
	//brokers_options : null,
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".call_presentation_layer_web_service_task_html");
		
		//BrokerOptionsUtilObj.initFields(task_html_elm.find(".broker_method_obj"), callPresentationLayerWebServiceTaskPropertyObj.brokers_options, task_property_values["method_obj"]);
		
		ProgrammingTaskUtil.setResultVariableType(task_property_values, task_html_elm);
		
		if (typeof ProgrammingTaskUtil.on_programming_task_choose_page_url_callback == "function")
			task_html_elm.find(".get_automatically").removeClass("disabled");
		
		var project = task_property_values["project"] ? "" + task_property_values["project"] + "" : "";
		project = task_property_values["project_type"] == "variable" && project.trim().substr(0, 1) == '$' ? project.trim().substr(1) : project;
		task_html_elm.find(".project input").val(project);
		
		var page = task_property_values["page"] ? "" + task_property_values["page"] + "" : "";
		page = task_property_values["page_type"] == "variable" && page.trim().substr(0, 1) == '$' ? page.trim().substr(1) : page;
		task_html_elm.find(".page input").val(page);
		
		var external_vars = task_property_values["external_vars"];
		if (task_property_values["external_vars_type"] == "array") {
			ArrayTaskUtilObj.onLoadArrayItems( task_html_elm.find(".extvars .external_vars").first(), external_vars, "");
			task_html_elm.find(".extvars .external_vars_code").val("");
		}
		else {
			external_vars = external_vars ? "" + external_vars + "" : "";
			external_vars = task_property_values["external_vars_type"] == "variable" && external_vars.trim().substr(0, 1) == '$' ? external_vars.trim().substr(1) : external_vars;
			task_html_elm.find(".extvars .external_vars_code").val(external_vars);
		}
		callPresentationLayerWebServiceTaskPropertyObj.onChangeParametersType(task_html_elm.find(".extvars .external_vars_type")[0], "external_vars");
		
		var includes = task_property_values["includes"];
		if (task_property_values["includes_type"] == "array") {
			callPresentationLayerWebServiceTaskPropertyObj.onLoadIncludes( task_html_elm.find(".incs .includes").first(), includes, "");
			task_html_elm.find(".incs .includes_code").val("");
		}
		else {
			includes = includes ? "" + includes + "" : "";
			includes = task_property_values["includes_type"] == "variable" && includes.trim().substr(0, 1) == '$' ? includes.trim().substr(1) : includes;
			task_html_elm.find(".incs .includes_code").val(includes);
		}
		callPresentationLayerWebServiceTaskPropertyObj.onChangeParametersType(task_html_elm.find(".incs .includes_type")[0], "includes");
		
		var includes_once = task_property_values["includes_once"];
		if (task_property_values["includes_once_type"] == "array") {
			callPresentationLayerWebServiceTaskPropertyObj.onLoadIncludes( task_html_elm.find(".incs_once .includes_once").first(), includes_once, "");
			task_html_elm.find(".incs_once .includes_once_code").val("");
		}
		else {
			includes_once = includes_once ? "" + includes_once + "" : "";
			includes_once = task_property_values["includes_once_type"] == "variable" && includes_once.trim().substr(0, 1) == '$' ? includes_once.trim().substr(1) : includes_once;
			task_html_elm.find(".incs_once .includes_once_code").val(includes_once);
		}
		callPresentationLayerWebServiceTaskPropertyObj.onChangeParametersType(task_html_elm.find(".incs_once .includes_once_type")[0], "includes_once");
	},
	
	onLoadIncludes : function(array_items_html_elm, items, root_label) {
		ArrayTaskUtilObj.onLoadArrayItems(array_items_html_elm, items, root_label);
		
		array_items_html_elm.find(".items .item_add").first().attr("onclick", "callPresentationLayerWebServiceTaskPropertyObj.addIncludeItem(this)");
		
		array_items_html_elm.find(".item .value_type").after('<span class="icon search" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseFilePath(this)">Search</span>');
	},
	
	addIncludeItem : function(a) {
		ArrayTaskUtilObj.addItem(a);
		
		var main_ul = $(a).parent().parent().children("ul").first();

		if (main_ul[0]) {
			var last_li = main_ul.children("li").last();
			last_li.children(".value_type").after('<span class="icon search" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseFilePath(this)">Search</span>');
		}
	},
	
	onChangeParametersType : function(elm, class_name) {
		var parameters_type = $(elm).val();
		
		var parent = $(elm).parent();
		var parameters_elm = parent.children("." + class_name);
		
		if (parameters_type == "array") {
			parent.children("." + class_name + "_code").hide();
			parent.children(".search").hide();
			parameters_elm.show();
			
			if (!parameters_elm.find(".items")[0]) {
				var items = {0: {key_type: "null", value_type: "string"}};
				
				if (class_name == "includes" || class_name == "includes_once") {
					this.onLoadIncludes(parameters_elm, items, "");
				}
				else {
					ArrayTaskUtilObj.onLoadArrayItems(parameters_elm, items, "");
				}
			}
		}
		else {
			parent.children("." + class_name + "_code").show();
			parent.children(".search").show();
			parameters_elm.hide();
		}
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".call_presentation_layer_web_service_task_html");
		ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithType(task_html_elm);
		ProgrammingTaskUtil.onSubmitResultVariableType(task_html_elm);
		
		if (task_html_elm.find(".extvars .external_vars_type").val() == "array") {
			task_html_elm.find(".extvars .external_vars_code").remove();
		}
		else {
			task_html_elm.find(".extvars .external_vars").remove();
		}
		
		if (task_html_elm.find(".incs .includes_type").val() == "array") {
			task_html_elm.find(".incs .includes_code").remove();
		}
		else {
			task_html_elm.find(".incs .includes").remove();
		}
		
		if (task_html_elm.find(".incs_once .includes_once_type").val() == "array") {
			task_html_elm.find(".incs_once .includes_once_code").remove();
		}
		else {
			task_html_elm.find(".incs_once .includes_once").remove();
		}
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var label = callPresentationLayerWebServiceTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
			
			/*var default_method_obj_str = BrokerOptionsUtilObj.getDefaultBroker(callPresentationLayerWebServiceTaskPropertyObj.brokers_options);
			if (!task_property_values["method_obj"] && default_method_obj_str)
				task_property_values["method_obj"] = default_method_obj_str;
			*/
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
		
			var label = callPresentationLayerWebServiceTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
		
			onEditLabel(task_id);
			
			/*var default_method_obj_str = BrokerOptionsUtilObj.getDefaultBroker(callPresentationLayerWebServiceTaskPropertyObj.brokers_options);
			if (!task_property_values["method_obj"] && default_method_obj_str)
				myWFObj.getTaskFlowChart().TaskFlow.tasks_properties[task_id]["method_obj"] = default_method_obj_str;
			*/
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 80);
	},
	
	getDefaultExitLabel : function(task_property_values) {
		if (task_property_values["project"] && task_property_values["page"]) {
			var project = ProgrammingTaskUtil.getValueString(task_property_values["project"], task_property_values["project_type"]);
			var page = ProgrammingTaskUtil.getValueString(task_property_values["page"], task_property_values["page_type"]);
			
			var external_vars = task_property_values["external_vars_type"] == "array" ? ArrayTaskUtilObj.arrayToString(task_property_values["external_vars"]) : ProgrammingTaskUtil.getValueString(task_property_values["external_vars"], task_property_values["external_vars_type"]);
			external_vars = external_vars ? external_vars : "null";
			
			var includes = task_property_values["includes_type"] == "array" ? ArrayTaskUtilObj.arrayToString(task_property_values["includes"]) : ProgrammingTaskUtil.getValueString(task_property_values["includes"], task_property_values["includes_type"]);
			includes = includes ? includes : "null";
			
			var includes_once = task_property_values["includes_once_type"] == "array" ? ArrayTaskUtilObj.arrayToString(task_property_values["includes_once"]) : ProgrammingTaskUtil.getValueString(task_property_values["includes_once"], task_property_values["includes_once_type"]);
			includes_once = includes_once ? includes_once : "null";
			
			return ProgrammingTaskUtil.getResultVariableString(task_property_values) + 'call_presentation_layer_web_service(array("presentation_id" => ' + project + ', "url" => ' + page + ', "external_vars" => ' + external_vars + ', "includes" => ' + includes + ', "includes_once" => ' + includes_once + '))';
		}
		return "";
	},
};
