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

var SLAItemGroupTaskPropertyObj = {
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		SLAItemTaskPropertyObj.onLoadTaskProperties(properties_html_elm, task_id, task_property_values, true);
	},
	
	onTaskCreation : function(task_id) {
		setTimeout(function() {
			var task_property_values = myWFObj.getTaskFlowChart().TaskFlow.tasks_properties[task_id];
			var labels = SLAItemGroupTaskPropertyObj.getExitLabels(task_property_values);
			ProgrammingTaskUtil.updateTaskExitsLabels(task_id, labels);
			
			onEditLabel(task_id);
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 80);
	},
	
	getExitLabels : function(task_property_values) {
		var labels = {"inside_group_exit": "Inside", "outside_group_exit": "Outside"}; //bc of old diagrams where task_property_values["exits"] don't have the labels.
		
		if (task_property_values && task_property_values["exits"]) {
			var exits = task_property_values["exits"];
			labels["inside_group_exit"] = exits["inside_group_exit"] && exits["inside_group_exit"]["label"] ? exits["inside_group_exit"]["label"] : labels["inside_group_exit"];
			labels["outside_group_exit"] = exits["outside_group_exit"] && exits["outside_group_exit"]["label"] ? exits["outside_group_exit"]["label"] : labels["outside_group_exit"];
		}
		
		return labels;
	},
};
