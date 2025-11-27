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

var SendEmailTaskPropertyObj = {
	
	dependent_file_path_to_include : "LIB_PATH . 'org/phpframework/util/web/SendEmailHandler.php'",
	exists_phpmailer: true,
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".send_email_task_html");
		ProgrammingTaskUtil.setResultVariableType(task_property_values, task_html_elm);
		
		SendEmailTaskPropertyObj.onChangeMethod( task_html_elm.find(".method select")[0] );
		
		var settings = task_property_values["settings"];
		
		if (task_property_values["settings_type"] == "array") {
			ArrayTaskUtilObj.onLoadArrayItems( task_html_elm.find(".settngs .settings").first(), settings, "");
			task_html_elm.find(".settngs .settings_code").val("");
		}
		else {
			settings = settings ? "" + settings : "";
			settings = task_property_values["settings_type"] == "variable" && settings.trim().substr(0, 1) == '$' ? settings.trim().substr(1) : settings;
			task_html_elm.find(".settngs .settings_code").val(settings);
		}
		SendEmailTaskPropertyObj.onChangeSettingsType(task_html_elm.find(".settngs .settings_type")[0]);
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".send_email_task_html");
		ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithType(task_html_elm);
		ProgrammingTaskUtil.onSubmitResultVariableType(task_html_elm);
		
		if (task_html_elm.find(".settngs .settings_type").val() == "array") {
			task_html_elm.find(".settngs .settings_code").remove();
		}
		else {
			task_html_elm.find(".settngs .settings").remove();
		}
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var label = SendEmailTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
		}
	},
	
	onCancelTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		return true;	
	},
	
	onCompleteLabel : function(task_id) {
		return ProgrammingTaskUtil.onEditLabel(task_id);
	},
	
	onTaskCloning : function(task_id) {
		var task_property_values = myWFObj.getTaskFlowChart().TaskFlow.tasks_properties[task_id];
		task_property_values["settings_type"] = "array";
		task_property_values["settings"] = SendEmailTaskPropertyObj.getDefaultMethodArrayItems( task_property_values["method"] );
	
		ProgrammingTaskUtil.onTaskCloning(task_id);
		
		ProgrammingTaskUtil.addIncludeFileTaskBeforeTaskIfNotExistsYet(task_id, SendEmailTaskPropertyObj.dependent_file_path_to_include, '', 1);
	},
	
	onTaskCreation : function(task_id) {
		setTimeout(function() {
			var task_property_values = myWFObj.getTaskFlowChart().TaskFlow.tasks_properties[task_id];
			ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithTaskPropertiesValues(task_property_values);
		
			var label = SendEmailTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
			
			onEditLabel(task_id);
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 30);
	},
	
	getDefaultExitLabel : function(task_property_values) {
		if (task_property_values["method"]) {
			var settings = task_property_values["settings_type"] == "array" ? ArrayTaskUtilObj.arrayToString(task_property_values["settings"]) : ProgrammingTaskUtil.getValueString(task_property_values["settings"], task_property_values["settings_type"]);
			settings = settings ? settings : "null";
			
			return ProgrammingTaskUtil.getResultVariableString(task_property_values) + task_property_values["method"] + "(" + settings + ")";
		}
		return "";
	},
	
	onChangeMethod : function(elm) {
		var elm = $(elm);
		var task_html_elm = elm.parent().closest(".send_email_task_html");
		var method = task_html_elm.find(".method select").val();
		
		//set info
		var info = "The settings below should have the following attributes:";
		
		if (!SendEmailTaskPropertyObj.exists_phpmailer && method == "SendEmailHandler::sendSMTPEmail")
			info += "<div class=\"error\">Note that this function won't because the PHPMailer library is not installed!</div>";
		
		info += "<ul>";
		
		if (method == "SendEmailHandler::sendSMTPEmail")
			info += "<li>smtp_host: domain name for the smtp server;</li>"
				+ "<li>smtp_port: domain port for the smtp server;</li>"
				+ "<li>smtp_user: username for the smtp server;</li>"
				+ "<li>smtp_pass: password for the smtp server;</li>"
				+ "<li>smtp_secure: security type for the smtp connection. Possible values are: \"ssl\", \"tls\" or null for no security. Default value is null;</li>"
				+ "<li>smtp_encoding: encoding type for the smtp connection. Default value: \"utf-8\";</li>"
				+ "<li>from_email: from email address;</li>"
				+ "<li>reply_email: reply email address;</li>"
				+ "<li>to_email: to email address;</li>"
				+ "<li>subject: subject of the email;</li>"
				+ "<li>message: message of the email;</li>"
				+ "<li>debug: enable smtp debugging. Possible values: 0 for no debug, 1 for client messages, 2 for client and server messages. Default value is 0;</li>";
		else
			info += "<li>mail_boundary: boundary code to split the message contents. Default value is null or false, so the system sets its internal default code;</li>"
				+ "<li>encoding: encoding type for the email. Default value: \"utf-8\";</li>"
				+ "<li>from_email: from email address;</li>"
				+ "<li>reply_email: reply email address;</li>"
				+ "<li>to_email: to email address;</li>"
				+ "<li>subject: subject of the email;</li>"
				+ "<li>message: message of the email;</li>"
				+ "<li>extra_headers: extra headers to be added to the email. This could be a string or an associative array. Default value is null or false;</li>";
		
		info += "</ul>";
		
		elm.parent().children(".info").html(info);
		
		//prepare new settings if old settings are empty
		if (task_html_elm.find(".settngs .settings_type").val() == "array")
			SendEmailTaskPropertyObj.reloadMethodAttributes(task_html_elm.children()[0]);
	},
	
	onChangeSettingsType : function(elm) {
		var settings_type = $(elm).val();
		
		var parent = $(elm).parent();
		var settings_elm = parent.children(".settings");
		
		if (settings_type == "array") {
			parent.find(".settings_code").hide();
			settings_elm.show();
			
			if (!settings_elm.find(".items")[0]) {
				var task_html_elm = parent.parent().closest(".send_email_task_html");
				var method = task_html_elm.find(".method select").val();
				
				var items = SendEmailTaskPropertyObj.getDefaultMethodArrayItems(method);
				ArrayTaskUtilObj.onLoadArrayItems(settings_elm, items, "");
			}
		}
		else {
			parent.find(".settings_code").show();
			settings_elm.hide();
		}
		
		ProgrammingTaskUtil.onChangeTaskFieldType(elm);
	},
	
	reloadMethodAttributes : function(elm) {
		var task_html_elm = $(elm).parent().closest(".send_email_task_html");
		var method = task_html_elm.find(".method select").val();
		var items = SendEmailTaskPropertyObj.getDefaultMethodArrayItems(method);
		var settings_elm = task_html_elm.find(".settngs .settings");
		var settings_type = task_html_elm.find(".settngs .settings_type");
		
		//set old array items into new items
		var other_items = SendEmailTaskPropertyObj.getDefaultMethodArrayItems(method == "SendEmailHandler::sendSMTPEmail" ? "SendEmailHandler::sendEmail" : "SendEmailHandler::sendSMTPEmail");
		var old_items = SendEmailTaskPropertyObj.getCurrentSettings(task_html_elm);
		var new_idx = 0;
		var other_items_keys = [];
		
		$.each(items, function(idx, item) {
			if (new_idx < parseInt(idx))
				new_idx = parseInt(idx);
		});
		
		$.each(other_items, function(idx, item) {
			other_items_keys.push(other_items[idx]["key"]);
		});
		
		$.each(old_items, function(idx, old_item) {
			var exists = false;
			
			$.each(items, function(idj, new_item) {
				if (old_item["key"] == new_item["key"]) {
					new_item["value"] = old_item["value"];
					new_item["value_type"] = old_item["value_type"];
					exists = true;
					
					return false;
				}
			});
			
			if (!exists && $.inArray(old_item["key"], other_items_keys) == -1) {
				new_idx++;
				items[new_idx] = old_item;
			}
		});
		
		//show items
		ArrayTaskUtilObj.onLoadArrayItems(settings_elm, items, "");
		
		if (settings_type.val() != "array") {
			settings_type.val("array");
			SendEmailTaskPropertyObj.onChangeSettingsType(settings_type[0]);
		}
	},
	
	getDefaultMethodArrayItems : function(method) {
		if (method == "SendEmailHandler::sendSMTPEmail")
			return {
				1: {key: "smtp_host", key_type: "string", value: "", value_type: "string"},
				2: {key: "smtp_port", key_type: "string", value: "", value_type: "string"},
				3: {key: "smtp_user", key_type: "string", value: "", value_type: "string"},
				4: {key: "smtp_pass", key_type: "string", value: "", value_type: "string"},
				5: {key: "smtp_secure", key_type: "string", value: "", value_type: "string"},
				6: {key: "smtp_encoding", key_type: "string", value: "", value_type: "string"},
				7: {key: "from_email", key_type: "string", value: "", value_type: "string"},
				8: {key: "reply_email", key_type: "string", value: "", value_type: "string"},
				9: {key: "to_email", key_type: "string", value: "", value_type: "string"},
				10: {key: "subject", key_type: "string", value: "", value_type: "string"},
				11: {key: "message", key_type: "string", value: "", value_type: "string"},
				12: {key: "debug", key_type: "string", value: "", value_type: "string"},
			};
		else //sendEmail
			return {
				1: {key: "mail_boundary", key_type: "string", value: "", value_type: "string"},
				2: {key: "encoding", key_type: "string", value: "", value_type: "string"},
				3: {key: "from_email", key_type: "string", value: "", value_type: "string"},
				4: {key: "reply_email", key_type: "string", value: "", value_type: "string"},
				5: {key: "to_email", key_type: "string", value: "", value_type: "string"},
				6: {key: "subject", key_type: "string", value: "", value_type: "string"},
				7: {key: "message", key_type: "string", value: "", value_type: "string"},
				8: {key: "extra_headers", key_type: "string", value: "", value_type: "string"},
			};
	},
	
	getCurrentSettings : function(task_html_elm) {
		var settings_elm = task_html_elm.find(".settngs .settings");
		var items = {};
		var query_string = myWFObj.getTaskFlowChart().Property.getPropertiesQueryStringFromHtmlElm(settings_elm, "task_property_field");
		parse_str(query_string, items);
		items = items.hasOwnProperty("settings") ? items["settings"] : {};
		
		return items;
	},
};
