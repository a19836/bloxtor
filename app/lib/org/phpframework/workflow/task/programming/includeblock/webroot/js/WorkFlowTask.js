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

var IncludeBlockTaskPropertyObj = {
	
	on_choose_file_callback : null,
	brokers_options : null,
	projects_options : null,
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".include_block_task_html");
		
		BrokerOptionsUtilObj.initFields(task_html_elm.find(".broker_method_obj"), IncludeBlockTaskPropertyObj.brokers_options, task_property_values["method_obj"]);
		
		if ($.isArray(IncludeBlockTaskPropertyObj.projects_options)) {
			var options = '<option value="">Default</option>';
			
			for (var i = 0; i < IncludeBlockTaskPropertyObj.projects_options.length; i++) {
				options += '<option>' + IncludeBlockTaskPropertyObj.projects_options[i] + '</option>';
			}
			
			task_html_elm.find(".project select.project").html(options);
		}
		
		if (!task_property_values["project"] || $.inArray(task_property_values["project"], IncludeBlockTaskPropertyObj.projects_options) != -1) {
			task_html_elm.find(".project input").hide();
			task_html_elm.find(".project select.project").val(task_property_values["project"]);
			task_html_elm.find(".project select.type").val("options");
		}
		else {
			task_html_elm.find(".project select.project").hide();
		}
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".include_block_task_html");
		
		if (task_html_elm.find(".project select.type").val() == "options") {
			var project = task_html_elm.find(".project select.project").val();
			task_html_elm.find(".project input").val(project);
			task_html_elm.find(".project select.type").val("string");
		}
		task_html_elm.find(".project select.project").remove();
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var label = IncludeBlockTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
			
			var default_method_obj_str = BrokerOptionsUtilObj.getDefaultBroker(IncludeBlockTaskPropertyObj.brokers_options);
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
			var label = IncludeBlockTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
		
			onEditLabel(task_id);
			
			var default_method_obj_str = BrokerOptionsUtilObj.getDefaultBroker(IncludeBlockTaskPropertyObj.brokers_options);
			if (!task_property_values["method_obj"] && default_method_obj_str) {
				myWFObj.getTaskFlowChart().TaskFlow.tasks_properties[task_id]["method_obj"] = default_method_obj_str;
			}
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 80);
	},
	
	getDefaultExitLabel : function(task_property_values) {
		var method_obj = (task_property_values["method_obj"].trim().substr(0, 1) != "$" ? "$" : "") + task_property_values["method_obj"];
		
		var project = task_property_values["project"] ? ', ' + ProgrammingTaskUtil.getValueString(task_property_values["project"], task_property_values["project_type"]) : '';
		var block = ProgrammingTaskUtil.getValueString(task_property_values["block"], task_property_values["block_type"]);
		
		return block ? "include " + method_obj + "->getBlockPath(" + block + project + ")" : "";
	},
	
	onChooseFile : function(elm) {
		if (typeof this.on_choose_file_callback == "function")
			this.on_choose_file_callback(elm);
	},
	
	onChangeBlockType : function(elm) {
		elm = $(elm);
		var p = elm.parent();
		
		if (elm.val() == "options") {
			p.children("input").hide();
			p.children("select.project").show();
		}
		else {
			p.children("input").show();
			p.children("select.project").hide();
		}
	},
};
