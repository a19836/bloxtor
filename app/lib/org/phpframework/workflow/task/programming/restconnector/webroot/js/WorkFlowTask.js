/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

var RestConnectorTaskPropertyObj = null;

if (!GetUrlContentsTaskPropertyObj)
	alert("GetUrlContentsTaskPropertyObj must be defined before the RestConnectorTaskPropertyObj gets defined!");
else
	RestConnectorTaskPropertyObj = {
		
		dependent_file_path_to_include : "LIB_PATH . 'org/phpframework/connector/RestConnector.php'",
	
		onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
			GetUrlContentsTaskPropertyObj.onLoadTaskProperties(properties_html_elm, task_id, task_property_values);
			
			var task_html_elm = $(properties_html_elm).find(".get_url_contents_task_html");
			
			if (!task_property_values["result_type"]) {
				task_html_elm.find(".result_type select[name=result_type]").val("content");
				
				var select = task_html_elm.find(".result_type select[name=result_type_type]");
				select.val("options");
				GetUrlContentsTaskPropertyObj.onChangeResultType(select[0]);
			}
		},
		
		onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
			return GetUrlContentsTaskPropertyObj.onSubmitTaskProperties(properties_html_elm, task_id, task_property_values);
		},
		
		onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
			GetUrlContentsTaskPropertyObj.onCompleteTaskProperties(properties_html_elm, task_id, task_property_values, status);
			
			if (status) {
				var label = RestConnectorTaskPropertyObj.getDefaultExitLabel(task_property_values);
				ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
			}
		},
		
		onCancelTaskProperties : function(properties_html_elm, task_id, task_property_values) {
			return GetUrlContentsTaskPropertyObj.onCancelTaskProperties(properties_html_elm, task_id, task_property_values);	
		},
		
		onCompleteLabel : function(task_id) {
			return GetUrlContentsTaskPropertyObj.onCompleteLabel(task_id);
		},
		
		onTaskCloning : function(task_id) {
			ProgrammingTaskUtil.onTaskCloning(task_id);
		
		ProgrammingTaskUtil.addIncludeFileTaskBeforeTaskIfNotExistsYet(task_id, RestConnectorTaskPropertyObj.dependent_file_path_to_include, '', 1);
		},
		
		onTaskCreation : function(task_id) {
			GetUrlContentsTaskPropertyObj.onTaskCreation(task_id);
			
			setTimeout(function() {
				var task_property_values = myWFObj.getTaskFlowChart().TaskFlow.tasks_properties[task_id];
				
				if (task_property_values && !task_property_values["result_type"]) {
					task_property_values["result_type"] = "content";
					task_property_values["result_type_type"] = "string";
				}
				
				var label = RestConnectorTaskPropertyObj.getDefaultExitLabel(task_property_values);
				ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
			
				onEditLabel(task_id);
				
				ProgrammingTaskUtil.onTaskCreation(task_id);
			}, 200);
		},
		
		getDefaultExitLabel : function(task_property_values) {
			var label = GetUrlContentsTaskPropertyObj.getDefaultExitLabel(task_property_values);
			
			if (label)
				label = label.replace("MyCurl::getUrlContents(", "RestConnector::connect(");
				
			return label;
		},
	};
