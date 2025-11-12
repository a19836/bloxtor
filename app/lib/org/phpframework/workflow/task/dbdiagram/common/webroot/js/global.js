/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

if (typeof is_global_db_diagram_common_file_already_included == "undefined") {
	var is_global_db_diagram_common_file_already_included = 1;
	
	function removeTableConnectionFromConnectionProperties(btn) {
		var WF = myWFObj.getTaskFlowChart();
		var selected_connection_properties = $(btn).closest("#" + WF.Property.selected_connection_properties_id);
		var connection_id = selected_connection_properties.attr("connection_id");
		
		WF.Property.hideSelectedConnectionProperties();
		
		if (connection_id)
			WF.TaskFlow.deleteConnection(connection_id);
	}
	
	function onTableConnectionDrop(conn) {
		if (conn.sourceId == conn.targetId) {
			var connector_type = conn.connection.connector.type;
			
			if(connector_type == "Flowchart") {
				var connection = conn.connection;
				connection.setParameter("connection_exit_type", "StateMachine");
				
				myWFObj.getTaskFlowChart().TaskFlow.setNewConnectionConnector(connection);
			}
		}
		
		return true;
	}
	
	//Checks all existent connections and change them accordingly if exists any inconsistency...
	//To be call after a xml file loads.
	function prepareTasksTableConnections() {
		var connections = myWFObj.getTaskFlowChart().TaskFlow.getConnections();
		
		for (var i = 0; i < connections.length; i++)
			getConfiguredTaskTableConnection(connections[i]);
		
	}
	
	//checks if this connection is "One To Many" and flip it to "Many To One"
	//To be call on DBTableTaskPropertyObj.onCompleteConnectionProperties.
	function getConfiguredTaskTableConnection(conn) {
		var WF = myWFObj.getTaskFlowChart();
		var conn_overlay = conn.getParameter("connection_exit_overlay");
		
		if (conn_overlay == "One To Many") {
			var new_conn = WF.TaskFlow.flipConnection(conn.id);
			
			if (new_conn) {
				WF.TaskFlow.changeConnectionOverlayType(new_conn, "Many To One");
				
				var conn_connector = conn.getParameter("connection_exit_type");
				WF.TaskFlow.changeConnectionConnectorType(new_conn, conn_connector);
				
				//flip connection properties
				var aux = WF.TaskFlow.connections_properties[new_conn.id]["source_columns"];
				WF.TaskFlow.connections_properties[new_conn.id]["source_columns"] = WF.TaskFlow.connections_properties[new_conn.id]["target_columns"];
				WF.TaskFlow.connections_properties[new_conn.id]["target_columns"] = aux;
				
				return new_conn;
			}
		}
		
		return conn;
	}
	
	function isTaskTableLabelValid(label_obj, task_id, ignore_msg) {
		var valid = false;
		var is_repeated = false;
		
		if (label_obj.label && label_obj.label.length > 0) {
			//var valid = inputTextContainsRegex(label_obj.label, /^[\p{L}\w\.]+$/ugi); //\p{L} and /../u is to get parameters with accents and ç. Already includes the a-z. Cannot use this bc it does not work in IE.
			var valid = inputTextContainsRegex(label_obj.label, /^[\w\.\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u024F\u1EBD\u1EBC]+$/gi); //'\w' means all words with '_' and 'u' means with accents and ç too.
			
			if (valid)
				valid = inputTextContainsAtLeastOneLetter(label_obj.label); //checks if label has at least one letter
			
			if (valid)
				valid = inputTextContainsRegex(label_obj.label, /^[^\.]/); //checks if label starts with a word and not a '.'
			
			if (valid) {
				is_repeated = isTaskLabelRepeated(label_obj, task_id, ignore_msg);
				valid = is_repeated == false;
			}
			
			if (valid)
				isTaskTableNameAdvisable(label_obj.label);
		}
		
		if (!valid) {
			var msg = (is_repeated ? "\n" : "") + "Invalid label. Please choose a different label.\nOnly this characters are allowed: a-z, A-Z, 0-9, '_', '.' and you must have at least 1 letter.\nNote that by adding the '.' char you are adding a schema to your table.";
			myWFObj.getTaskFlowChart().StatusMessage.showError(msg);
			//console.log(msg);
		}
		
		return valid;
	}
	
	function isTaskTableNameAdvisable(name) {
		if (name) {
			var normalized = ("" + name);
			
			if (typeof normalized.normalize == "function") //This doesn't work in IE11
				normalized = normalized.normalize("NFD");
				
			normalized = normalized.replace(/[\u0300-\u036f]/g, ""); //replaces all characters with accents with non accented characters including 'ç' to 'c'
			
			if (name != normalized)
				myWFObj.getTaskFlowChart().StatusMessage.showError("Is NOT advisable to add names with accents and with non-standard characters. Please try to only use A-Z 0-9 and '_'.");
		}
	}
	
	function normalizeTaskTableName(name) {
		//return name ? ("" + name).replace(/\n/g, "").replace(/[ \-]+/g, "_").match(/[\p{L}\w\.]+/giu).join("") : name; //\p{L} and /../u is to get parameters with accents and ç. Already includes the a-z. Cannot use this bc it does not work in IE.
		return name ? ("" + name).replace(/(^\s+|\s+$)/g, "").replace(/\n/g, "").replace(/[ \-]+/g, "_").match(/[\w\.\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u024F\u1EBD\u1EBC]+/gi).join("") : name; //'\w' means all words with '_' and 'u' means with accents and ç too.
	}
	
	function resizeTableTaskBasedOnAttributes(task_id) {
		var WF = myWFObj.getTaskFlowChart();
		var task = WF.TaskFlow.getTaskById(task_id);
		var items = task.find(" > ." + WF.TaskFlow.task_eps_class_name + " ." + WF.TaskFlow.task_ep_class_name + " .table_attr .name p");
		
		var name_elm = null;
		var n = "";
		$.each(items, function(idx, item) {
			item = $(item);
			name_elm = item.parent();
			var str = item.text();
			
			n = n.length < ("" + str).length ? str : n;
		});
		
		if (name_elm) {
			var span = document.createElement("SPAN");
			span = $(span);
			span.html(n);
			span.css("font-size", name_elm.css("font-size"));
			$('body').append(span);
			var diff = span.width() - name_elm.width();
			//console.log(task_id+"("+n+"=="+span.text()+"):"+(span.width() - name_elm.width()) +" = "+span.width()+" - "+name_elm.width());
			span.remove();
			
			if (diff > 0) {
				task.css("width", (parseInt(task.css("width")) + diff + 5) + "px");
				
				WF.TaskFlow.repaintTask(task);
			}
		}
	}
}
