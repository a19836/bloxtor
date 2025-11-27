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

var GetBeanObjectTaskPropertyObj = {
	
	phpframeworks_options : null,
	bean_names_options : null,
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".get_bean_object_task_html");
		ProgrammingTaskUtil.setResultVariableType(task_property_values, task_html_elm);
		
		BrokerOptionsUtilObj.initFields(task_html_elm.find(".phpframework_obj"), GetBeanObjectTaskPropertyObj.phpframeworks_options, task_property_values["phpframework_obj"], {
			other_broker_variable_label: "From some other variable",
			broker_prefix_label: "From variable: "
		});
		
		var bean_name = task_property_values["bean_name"] ? task_property_values["bean_name"] : "";
		var select = task_html_elm.find(".bean_name select.bean_name_options");
		var input = task_html_elm.find(".bean_name input.bean_name_variable");
		var select_type = task_html_elm.find(".bean_name select[name=bean_name_type]");
		var exists_in_beans = bean_name && task_property_values["bean_name_type"] == "string" && $.isArray(GetBeanObjectTaskPropertyObj.bean_names_options) && $.inArray(bean_name, GetBeanObjectTaskPropertyObj.bean_names_options) != -1;
		
		GetBeanObjectTaskPropertyObj.updateBeanNameTypeOptions(select[0]);
		
		if ( 
			(jQuery.isEmptyObject(task_property_values) && $.isArray(GetBeanObjectTaskPropertyObj.bean_names_options) && GetBeanObjectTaskPropertyObj.bean_names_options.length > 0) || 
			exists_in_beans
		) {
			input.hide();
			select.show();
			select.val(bean_name);
			select_type.val("beans");
		}
		else {
			input.show();
			select.hide();
			
			bean_name = "" + bean_name + "";
			bean_name = bean_name.trim().substr(0, 1) == '$' ? bean_name.trim().substr(1) : bean_name;
			input.val(bean_name);
		}
		GetBeanObjectTaskPropertyObj.onChangeBeanNameType(select_type[0]);
	},
	
	onChangeBeanNameType : function(elm) {
		var bean_name_type = $(elm).val();
		var parent = $(elm).parent();
		
		if (bean_name_type == "beans") {
			parent.children("input.bean_name_variable").hide();
			parent.children("select.bean_name_options").show();
		}
		else {
			parent.children("input.bean_name_variable").show();
			parent.children("select.bean_name_options").hide();
		}
		
		ProgrammingTaskUtil.onChangeTaskFieldType(elm);
	},
	
	updateBeanNameTypeOptions : function(elm) {
		var html = "<option></option>";
		
		if ($.isArray(this.bean_names_options))
			$.each(this.bean_names_options, function(idx, bean_name) {
				html += '<option>' + bean_name + '</option>';
			});
		
		$(elm).html(html);
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".get_bean_object_task_html");
		ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithType(task_html_elm);
		ProgrammingTaskUtil.onSubmitResultVariableType(task_html_elm);
		
		var bean_name = "";
		var bean_name_type = task_html_elm.find(".bean_name select[name=bean_name_type]").val();
		
		if (bean_name_type == "beans") {
			bean_name = task_html_elm.find(".bean_name select.bean_name_options").val();
			task_html_elm.find(".bean_name select[name=bean_name_type]").val("string");
		}
		else
			bean_name = task_html_elm.find(".bean_name .bean_name_variable").val();
		
		task_html_elm.find(".bean_name input.bean_name_value").val( bean_name ? bean_name : "" );
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var label = GetBeanObjectTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
			
			var default_phpframework_obj_str = BrokerOptionsUtilObj.getDefaultBroker(GetBeanObjectTaskPropertyObj.phpframeworks_options);
			if (!task_property_values["phpframework_obj"] && default_phpframework_obj_str)
				task_property_values["phpframework_obj"] = default_phpframework_obj_str;
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
		
			var label = GetBeanObjectTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
		
			onEditLabel(task_id);
		
			var default_phpframework_obj_str = BrokerOptionsUtilObj.getDefaultBroker(GetBeanObjectTaskPropertyObj.phpframeworks_options);
			if (!task_property_values["phpframework_obj"] && default_phpframework_obj_str)
				myWFObj.getTaskFlowChart().TaskFlow.tasks_properties[task_id]["phpframework_obj"] = default_phpframework_obj_str;
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 30);
	},
	
	getDefaultExitLabel : function(task_property_values) {
		if (task_property_values["bean_name"]) {
			var phpframework_obj = (task_property_values["phpframework_obj"].trim().substr(0, 1) != "$" ? "$" : "") + task_property_values["phpframework_obj"];
			var bean_name = ProgrammingTaskUtil.getValueString(task_property_values["bean_name"], task_property_values["bean_name_type"]);
			
			return ProgrammingTaskUtil.getResultVariableString(task_property_values) + phpframework_obj + '->getObject(' + bean_name + ')';
		}
		return "";
	},
};
