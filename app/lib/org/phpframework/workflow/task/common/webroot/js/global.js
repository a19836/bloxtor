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

if (typeof is_global_common_file_already_included == "undefined") {
	var is_global_common_file_already_included = 1;
	
	var myWFObj = {
		WF: taskFlowChartObj,
		
		setTaskFlowChart : function(WF) {
			this.WF = WF;
		},
		
		getTaskFlowChart : function() {
			return this.WF;
		}
	};
	
	function inputTextContainsRegex(text, valid_text_regex) {
		if (text && text.length > 0) {
			text = text.replace(/\n/g, ""); //if text has \n then the regex won't work. So we need to use .replace(/\n/g, "")
			
			var ret = typeof valid_text_regex == "string" ? (new RegExp(valid_text_regex)).test(text) : valid_text_regex.exec(text);
			
			return ret ? true : false;
		}
		
		return false;
	}
	
	function inputTextContainsAtLeastOneLetter(text) {
		return inputTextContainsRegex(text, /[a-z]+/i);
	}
	
	function isLabelValid(label_obj, ignore_msg) {
		//var valid = !inputTextContainsRegex(label_obj.label, /[^\p{L}\w\-\.\$ ]+/u); //'\w' means all words with '_' and '/u' means with accents and ç too. Cannot use this bc it does not work in IE.
		var valid = !inputTextContainsRegex(label_obj.label, /[^\w\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u024F\u1EBD\u1EBC\-\.\$ ]+/); //'\w' means all words with '_' and 'u' means with accents and ç too.
		
		if (valid)
			valid = inputTextContainsAtLeastOneLetter(label_obj.label); //checks if label has at least one letter
		
		if (!valid && !ignore_msg) {
			var msg = "Invalid label. Please choose a different label.\nOnly this characters are allowed: a-z, A-Z, 0-9, '-', '_', '.', ' ', '$' and you must have at least 1 character.";
			
			if (label_obj.from_prompt)
				alert(msg);
			else
				myWFObj.getTaskFlowChart().StatusMessage.showError(msg);
		}
		
		return valid;
	}
	
	function isTaskLabelValid(label_obj, task_id, ignore_msg) {
		if (!isLabelValid(label_obj, ignore_msg))
			return false;
		
		return isTaskLabelRepeated(label_obj, task_id, ignore_msg) == false;
	}
	
	function isTaskLabelRepeated(label_obj, task_id, ignore_msg) {
		var WF = myWFObj.getTaskFlowChart();
		var l = label_obj.label.toLowerCase();
		
		var tasks = WF.TaskFlow.getAllTasks();
		var total = tasks.length;
		
		for (var i = 0; i < total; i++) {
			var t = $(tasks[i]);
			var elm_label = WF.TaskFlow.getTaskLabel(t);
			
			if (l == elm_label.toLowerCase() && t.attr("id") != task_id) {
				if (!ignore_msg) {
					var msg = "Error: Repeated label.\nYou cannot have repeated labels!\nPlease try again...";
					WF.StatusMessage.showError(msg);
					
					var msg_elm = WF.StatusMessage.getMessageHtmlObj().children(".error").last();
					
					if (!msg_elm.is(":visible"))
						alert(msg);
				}
				
				return true;
			}
		}
		return false;
	}
	
	function isConnectionLabelValid(label_obj, task_id) {
		if (label_obj.label == "") //connection label can be empty
			return true;
		
		if (!isLabelValid(label_obj))
			return false;
		
		return true;
	}
	
	function prepareLabelIfUserLabelIsInvalid(task_id) {
		var WF = myWFObj.getTaskFlowChart();
		
		//console.debug(task_id);
		var tasks = WF.TaskFlow.getAllTasks();
		var total = tasks.length;
		
		var task_label = WF.TaskFlow.getTaskLabelByTaskId(task_id);
		
		for (var i = 0; i < total; i++) {
			var t = $(tasks[i]);
			var elm_label = WF.TaskFlow.getTaskLabel(t);
			
			if (task_label == elm_label && t.attr("id") != task_id) {
				var r = parseInt(Math.random() * 10000);
				var new_label = task_label + "_" + r;
				
				WF.TaskFlow.getTaskLabelElementByTaskId(task_id).html(new_label);
				WF.TaskFlow.centerTaskInnerElements(task_id);
				
				break;
			}
		}
		
		return true;
	}
	
	function isTaskConnectionToItSelf(conn) {
		return conn.sourceId == conn.targetId;
	}
	
	function invalidateTaskConnectionIfItIsToItSelf(conn) {
		if (isTaskConnectionToItSelf(conn)) {
			myWFObj.getTaskFlowChart().StatusMessage.showError("WARNING: Sorry but you cannot create a connection to a task it-self!");
			return false;	
		}
		return true;
	}
	
	function onlyAllowOneConnectionPerExitAndNotToItSelf(conn) {
		if (invalidateTaskConnectionIfItIsToItSelf(conn)) {
			var source_id = conn.sourceId;
			var connection_exit_id = conn.connection.getParameter("connection_exit_id");
			
			if (connection_exit_id) {
				var connections = myWFObj.getTaskFlowChart().TaskFlow.getSourceConnections(source_id);
				
				for (var i = 0; i < connections.length; i++) {
					var c = connections[i];
					var ceid = c.getParameter("connection_exit_id");
					
					if (ceid && c.id != conn.connection.id && ceid == connection_exit_id) {
						myWFObj.getTaskFlowChart().StatusMessage.showError("You can only have 1 connection from the each exit point.");
						return false
					}
				}
			}
			return true;
		}
		return false;
	}
	
	function onTaskCloning(task_id, opts) {
		var WF = myWFObj.getTaskFlowChart();
		WF.TaskFlow.setTaskLabelByTaskId(task_id, {label: null}); //set {label: null}, so the TaskFlow.setTaskLabel method ignores the prompt and adds the default label or an auto generated label.
		
		//open properties
		if (!opts || !opts["do_not_show_task_properties"])
			WF.Property.showTaskProperties(task_id);
	}
	
	function checkIfValueIsTrue(value) {
		var v = typeof value == "string" ? value.toLowerCase() : "";
		
		return (value && value != null && value != 0 && value !== false && v != "null" && v != "false" && v != "0");
	}
	
	function onEditLabel(task_id) {
		var task = myWFObj.getTaskFlowChart().TaskFlow.getTaskById(task_id);
		var info = task.find(".info");
		var span = info.find("span").first();
		
		var width = span.width() + 50;
		task.css("width", width + "px");
		
		var num = 5;
		while (span.height() > info.height()) {
			width = info.width() + 50;
			task.css("width", width + "px");
			--num;
			
			if (num < 0) {
				break;
			}
		}
	}
	
	function updateTaskLabelInShownTaskProperties(task_id, task_properties_input_selector) {
		//if task properties is open, update label
		var WF = myWFObj.getTaskFlowChart();
		var task = WF.TaskFlow.getTaskById(task_id);
		var task_type = task.attr("type");
		var show_task_properties = WF.Property.isTaskSubSettingTrue(task_type, "task_menu", "show_properties_menu", true);
		
		if (show_task_properties) {
			var selected_task_properties = $("#" + WF.Property.selected_task_properties_id);
			
			if (selected_task_properties.is(":visible") && selected_task_properties.attr("task_id") == task_id)
				selected_task_properties.find(task_properties_input_selector).val( WF.TaskFlow.getTaskLabel(task) );
		}
	}
	
	function stringToUCWords(str) {
		var parts = str.split(" ");
		
		for (var i = 0; i < parts.length; i++) 
			if (parts[i])
				parts[i] = parts[i].substr(0, 1).toUpperCase() + parts[i].substr(1);
		
		return parts.join(" ");
	}
	
	function checkIfValueIsAssociativeArray(value) {
		var is_associative = false;
		
		if ($.isPlainObject(value) && !$.isArray(value)) {
			var idx = 0;
			
			$.each(value, function(i, v) {
				if (idx != i) {
					is_associative = true;
					return false;
				}
				
				idx++;
			});
		}
		
		return is_associative;
	}
	
	function checkIfValueIsAssociativeNumericArray(value) {
		if (checkIfValueIsAssociativeArray(value)) {
			var is_numeric_keys = true;
			
			$.each(value, function(i, v) {
				if (!$.isNumeric(i)) {
					is_numeric_keys = false;
					return false;
				}
			});
			
			return is_numeric_keys;
		}
	}
	
	function checkIfValueIsSurroundedWithQuotesAndIsNotAPHPCode(value) {
	 	if (value) {
			var fc = value.charAt(0);
			var lc = value.charAt(value.length - 1);
			
			//Check if exists quotes in the beginning and end of the value and in the middle (which are encapsulated), that means, there is not a php code in between and is a encapsulated string that should be decapsulated.
			//DO NOT USE /^"(.*)"$/.test(value) because if the value contains an end-line, this regex will never work!
			if (fc == '"' && lc == '"' && !/^"(.*)([^\\])"(.*)"$/.test(value.replace(/\n/g, ""))) 
				return 1;
			else if (fc == "'" && lc == "'" && !/^'(.*)([^\\])'(.*)'$/.test(value.replace(/\n/g, ""))) 
				return 2;
		}
		
		return false;
	}
	
	function convertToNormalTextIfValueIsSurroundedWithQuotesAndIsNotAPHPCode(value) {
	 	if (value) {
	 		var r = checkIfValueIsSurroundedWithQuotesAndIsNotAPHPCode(value);
	 		
	 		if (r == 1)
	 			value = value.substr(1, value.length - 2).replace(/\\"/g, '"').replace(/\\\\/g, "\\");
	 		else if (r == 2)
	 			value = value.substr(1, value.length - 2).replace(/\\'/g, "'").replace(/\\\\/g, "\\");
		}
		
		return value;
	}
	
	function showTaskPropertiesIfExists(task_id, task) {
		var WF = myWFObj.getTaskFlowChart();
		var task_type = task.attr("type");
		var show_task_properties = WF.Property.isTaskSubSettingTrue(task_type, "task_menu", "show_properties_menu", true);
		
		if (show_task_properties) {
			WF.Property.showTaskProperties(task_id, {do_not_call_hide_properties : true});
			
			//if properties are open, then closes the contextmenu
			if (WF.Property.isSelectedTaskPropertiesOpen())
				WF.ContextMenu.hideContextMenus();
		}
	}
	
	function showConnectionPropertiesIfExists(connection) {
		if (connection) {
			var WF = myWFObj.getTaskFlowChart();
			var task_type = $("#" + connection.sourceId).attr("type");
			var show_connection_properties = WF.Property.isTaskSubSettingTrue(task_type, "connection_menu", "show_properties_menu", true);
			
			if (show_connection_properties) {
				WF.Property.showConnectionProperties(connection.id, {do_not_call_hide_properties : true});
				
				//if properties are open, then closes the contextmenu
				if (WF.Property.isSelectedConnectionPropertiesOpen())
					WF.ContextMenu.hideContextMenus();
			}
		}
	}
	
	function getObjectorArraySize(obj_arr) {
		if ($.isArray(obj_arr))
			return obj_arr.length;
		
		if ($.isPlainObject(obj_arr)) {
			var count = 0;
			
			for (var k in obj_arr)
				count++;
			
			return count;
		}
		
		return $.isNumeric(obj_arr.length) ? obj_arr.length : null; //in case of nodes list from jquery
	}
}
