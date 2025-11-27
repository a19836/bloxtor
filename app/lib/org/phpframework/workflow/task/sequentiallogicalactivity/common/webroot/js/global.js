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

var SLAItemTaskPropertyObj = {
	tmp_task_properties : null,
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values, is_sla_item_group) {
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		
		//prepare properties html
		var sla = properties_html_elm.parent().closest(".sla");
		var task_html_elm = properties_html_elm.find(".sla_item_task_html > .sla_group_item");
		var cloned = sla.find(".sla_groups_flow > .sla_groups > .sla_group_item.sla_group_default").clone();
		
		task_html_elm.removeAttr("inited").html("").append(cloned.children());
		cloned.remove();
		
		var header_elm = task_html_elm.children(".sla_group_header");
		var action_type_elm = header_elm.children(".action_type");
		
		if (is_sla_item_group) {
			action_type_elm.append( action_type_elm.find("option[value='loop'], option[value='group']") );
			action_type_elm.children("optgroup").remove();
			action_type_elm.children("option").not("[value='loop'], [value='group']").remove();
		}
		else 
			action_type_elm.find("option[value='loop'], option[value='group']").remove();
		
		toggleGroupBody( header_elm.children(".toggle")[0] );
		header_elm.children(".toggle, .remove, .move_down, .move_up").remove();
		
		//load task_property_values
		if (task_property_values && task_property_values["properties"])
			loadSLASettingsAction(task_property_values["properties"], task_html_elm);
		else
			onChangeSLAInputType( action_type_elm[0] );
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		//Note that by default the lib/org/phpframework/workflow/task/common/webroot/js/global.js must be loaded before. This will be used in the onCompleteTaskProperties function
		if (!myWFObj) {
			alert("myWFObj does not exists! Please include the lib/org/phpframework/workflow/task/common/webroot/js/global.js first");
			return false;
		}
		
		var task_html_elm = properties_html_elm.find(".sla_item_task_html > .sla_group_item");
		var properties = getSLASettingsFromItemsToSave(task_html_elm);
		properties = properties ? properties[0] : {};
		SLAItemTaskPropertyObj.tmp_task_properties = properties;
		
		task_html_elm.html(""); //reset the task_html_elm, otherwise it will give an exception bc the myWFObj.getTaskFlowChart() will try to parse_str the createform properties, and it will give an error on the .task_property_field fields.
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		var WF = myWFObj.getTaskFlowChart();
		WF.TaskFlow.tasks_properties[task_id]["properties"] = SLAItemTaskPropertyObj.tmp_task_properties;
		
		if (status)
			SLAItemTaskPropertyObj.prepareTaskLabel(task_id);
		
		SLAItemTaskPropertyObj.tmp_task_properties = null;
	},
	
	onCancelTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		return true;	
	},
	
	onCompleteLabel : function(task_id) {
		return ProgrammingTaskUtil.onEditLabel(task_id);
	},
	
	prepareTaskLabel : function(task_id) {
		var WF = myWFObj.getTaskFlowChart();
		var label = WF.TaskFlow.getTaskLabelByTaskId(task_id);
		label = ("" + label).replace(/\s+/g, "");
		
		if (label == "" && WF.TaskFlow.tasks_properties[task_id]["properties"] && WF.TaskFlow.tasks_properties[task_id]["properties"]["action_type"]) {
			var props = WF.TaskFlow.tasks_properties[task_id]["properties"] ? WF.TaskFlow.tasks_properties[task_id]["properties"] : {};
			var task_label = (props["result_var_name"] ? "$" + props["result_var_name"] + " = " : "") + props["action_type"] + " (...)";
			
			WF.TaskFlow.getTaskById(task_id).attr("title", task_label).find(".info span").html(task_label);
			WF.TaskFlow.repaintTaskByTaskId(task_id);
		}
	},
};
