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

var ForeachTaskPropertyObj = {
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".foreach_task_html");
		
		var obj = task_property_values["obj"] ? "" + task_property_values["obj"] + "" : "";
		var key = task_property_values["key"] ? "" + task_property_values["key"] + "" : "";
		var value = task_property_values["value"] ? "" + task_property_values["value"] + "" : "";
		
		task_html_elm.find(".obj input").val( obj.substr(0, 1) == "$" ? obj.substr(1) : obj );
		task_html_elm.find(".key input").val( key.substr(0, 1) == "$" ? key.substr(1) : key );
		task_html_elm.find(".value input").val( value.substr(0, 1) == "$" ? value.substr(1) : value );
	},
		
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var labels = ForeachTaskPropertyObj.getExitLabels(task_property_values);
			ProgrammingTaskUtil.updateTaskExitsLabels(task_id, labels);
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
			var labels = ForeachTaskPropertyObj.getExitLabels(task_property_values);
			ProgrammingTaskUtil.updateTaskExitsLabels(task_id, labels);
		
			onEditLabel(task_id);
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 80);
	},
	
	getExitLabels : function(task_property_values) {
		var labels = {"start_exit": "Start loop", "default_exit": "End loop"}; //bc of old diagrams where task_property_values["exits"] don't have the labels.
		
		if (task_property_values && task_property_values["exits"]) {
			var exits = task_property_values["exits"];
			labels["start_exit"] = exits["start_exit"] && exits["start_exit"]["label"] ? exits["start_exit"]["label"] : labels["start_exit"];
			labels["default_exit"] = exits["default_exit"] && exits["default_exit"]["label"] ? exits["default_exit"]["label"] : labels["default_exit"];
		}
		
		return labels;
	},
};
