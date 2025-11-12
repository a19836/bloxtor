/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

var IfTaskPropertyObj = {
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		//console.debug(properties_html_elm);
		//console.debug(task_id);
		//console.debug(task_property_values);
		
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		
		if (!task_property_values) {
			task_property_values = {};
		}

		if (!task_property_values.group) {
			task_property_values.group = {'join' : 'and', item : {first : {value: '', type: 'string'}, operator : '', second : {value: '', type: 'string'}}};
		}

		var html = '';

		if (task_property_values.group.hasOwnProperty('join')) {
			html += ConditionsTaskUtilObj.loadPropertyValues(task_property_values.group, 'group[1]', true);
		}
		else {
			var idx = 0;
			for (var i in task_property_values.group) {
				idx++;
				
				html += ConditionsTaskUtilObj.loadPropertyValues(task_property_values.group[i], 'group[' + idx + ']', true);
			}
		}

		var conditions = $(properties_html_elm).find('.if_task_html .conditions');
		conditions.html(html);
		
		ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml( conditions.children() );
	},

	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		return true;
	},

	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var labels = IfTaskPropertyObj.getExitLabels(task_property_values);
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
			var labels = IfTaskPropertyObj.getExitLabels(task_property_values);
			ProgrammingTaskUtil.updateTaskExitsLabels(task_id, labels);
		
			onEditLabel(task_id);
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 100);
	},
	
	getExitLabels : function(task_property_values) {
		var labels = {"true": "True", "false": "False"}; //bc of old diagrams where task_property_values["exits"] don't have the labels.
		
		if (task_property_values && task_property_values["exits"]) {
			var exits = task_property_values["exits"];
			labels["true"] = exits["true"] && exits["true"]["label"] ? exits["true"]["label"] : labels["true"];
			labels["false"] = exits["false"] && exits["false"]["label"] ? exits["false"]["label"] : labels["false"];
		}
		//console.log(labels);
		
		return labels;
	},
};
