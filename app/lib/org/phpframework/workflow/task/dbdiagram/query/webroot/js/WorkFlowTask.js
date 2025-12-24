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

var DBQueryTaskPropertyObj = {
	selected_connection_properties_data : null,
	old_connection_property_values : null,
	show_properties_on_connection_drop : false,
	
	on_click_checkbox : null,
	on_delete_table : null,
	on_complete_table_label : null,
	on_complete_connection_properties : null,
	on_complete_select_start_task : null,
	
	connection_exit_props : {
		color: "#31498f",
		id: "layer_exit",
		overlay: "No Arrows",
		type: "Straight"
	},
	
	/** START: TASK METHODS **/
	prepareTableAttributes : function(task_id, data, rand_number) {
		if (data) {
			var WF = myWFObj.getTaskFlowChart();
			var task = WF.TaskFlow.getTaskById(task_id);
			
			if (task[0]) {
				var table_name = data.table_name;
				
				WF.TaskFlow.getTaskLabelElement(task).html(table_name);//the label has now 2 span elements: 1 for the label and another for the delete icon
				
				onEditLabel(task_id);
				WF.TaskFlow.repaintTaskByTaskId(task_id);
		
				var attributes_elm = task.find(" > ." + WF.TaskFlow.task_eps_class_name + " .table_attrs");
				if (!attributes_elm[0]) {
					attributes_elm = $('<table class="table_attrs"></table>');
					task.children("." + WF.TaskFlow.task_eps_class_name).append(attributes_elm);
				}
			
				var attr_names = data.table_attr_names;
			
				if (attr_names) {
					//PREPARE ATTRIBUTES
					var html = "";
					var count = 0;
					for (var attr_name in attr_names) {
						var checked = checkIfValueIsTrue(attr_names[attr_name]);
						attr_name = attr_name ? attr_name : "";
					
						html += '<tr class="table_attr"><td class="check"><input type="checkbox" name="query_attributes[' + table_name +  '][' + attr_name + ']" value="1" ' + (checked ? "checked" : "") + ' attribute="' + attr_name + '" /></td><td class="name">' + attr_name + '</td></tr>';
					
						count++;
					}
			
					attributes_elm.html(html);
					
					attributes_elm.find(".check input").click(function(originalEvent) {
						if (originalEvent && originalEvent.stopPropagation) originalEvent.stopPropagation();//bc checkbox is inside of eps and task, we should avoid the click of the eps and task to be trigger
						
						if (typeof DBQueryTaskPropertyObj.on_click_checkbox == "function") {
							DBQueryTaskPropertyObj.on_click_checkbox(this, WF, rand_number);
						}
					});
					
					var label_height = parseInt( task.children("." + WF.TaskFlow.task_label_class_name).height() );
					var min_height = parseInt( task.css("min-height") );
					
					var height = count * 20 + label_height; 
					height = height < min_height ? min_height : height;
					
					task.css("height", height + "px");
					
					resizeTableTaskBasedOnAttributes(task_id);
				}
			}
		}
	},
	
	deleteTable : function(task_id, to_confirm) {
		var task = myWFObj.getTaskFlowChart().TaskFlow.getTaskById(task_id);
		
		if (task[0]) {
			var status = myWFObj.getTaskFlowChart().TaskFlow.deleteTask(task_id, {confirm: to_confirm});
			
			if (status) {
				if (typeof DBQueryTaskPropertyObj.on_delete_table == "function") {
					DBQueryTaskPropertyObj.on_delete_table(task, myWFObj.getTaskFlowChart());
				}
			}
			else {
				myWFObj.getTaskFlowChart().StatusMessage.showError("Error: Couldn't delete the selected table.\nPlease try again...");
			}
		}
	},
	
	onStartLabel : function(task_id) {
		var WF = myWFObj.getTaskFlowChart();
	
		var label = WF.TaskFlow.getTaskLabelByTaskId(task_id);
		var parts = label.split(" ");
		var table_name = parts[0];
		var alias = parts[1] ? parts[1] : "";
		
		var span = WF.TaskFlow.getTaskLabelElementByTaskId(task_id);
		span.html(alias);
		span.attr("table_name", table_name);
		span.attr("table_alias", alias);
		
		return true;
	},
	
	onCheckLabel : function(label_obj, task_id) {
		var WF = myWFObj.getTaskFlowChart();
		var span = WF.TaskFlow.getTaskLabelElementByTaskId(task_id);
		var table_name = span.attr("table_name");
		
		if (label_obj.label.trim() == "")
			return isTaskLabelRepeated({label: table_name}, task_id) == false;
		else if (isTaskTableLabelValid(label_obj, task_id)) {
			if (label_obj.label == "as") {
				WF.StatusMessage.showError("Invalid Label! Please try again...");
				return false;
			}
			
			var new_label = table_name + (label_obj.label.trim() ? " " + label_obj.label.replace(/[ -]+/g, "_").trim() : "");
			
			return isTaskLabelRepeated({label: new_label}, task_id) == false;
		}
		return false;
	},
	
	onCompleteLabel : function(task_id) {
		var WF = myWFObj.getTaskFlowChart();
		var span = WF.TaskFlow.getTaskLabelElementByTaskId(task_id);
		
		if (span[0].hasAttribute("table_name")) {
			var table_name = span.attr("table_name");
			
			var new_alias = span.text();
			var old_alias = span.attr("table_alias");
			
			var new_label = table_name + (new_alias.trim() ? " " + new_alias.replace(/[ -]+/g, "_").trim() : "");
			var old_label = table_name + (old_alias ? " " + old_alias : "");
			
			span.html(new_label);
			span.removeAttr("table_name");
			span.removeAttr("table_alias");
			
			onEditLabel(task_id);
			WF.TaskFlow.repaintTaskByTaskId(task_id);
			
			if (old_label != new_label && typeof DBQueryTaskPropertyObj.on_complete_table_label == "function")
				DBQueryTaskPropertyObj.on_complete_table_label(WF, task_id, old_label, new_label);
		}
		else {
			onEditLabel(task_id);
			WF.TaskFlow.repaintTaskByTaskId(task_id);
		}
	},
	
	onTaskCreation : function(task_id) {
		var WF = myWFObj.getTaskFlowChart();
		var task = WF.TaskFlow.getTaskById(task_id);
		
		if (task[0]) {
			task.children("." + WF.TaskFlow.task_label_class_name).append('<i class="icon delete"></i></i>');//do not use span
			
			task.find(" > ." + WF.TaskFlow.task_label_class_name + " .delete").click(function(){
				myWFObj.setTaskFlowChart(WF);
				DBQueryTaskPropertyObj.deleteTable(task.attr("id"), true);
			});
		}
	},
	/** END: TASK METHODS **/
	
	/** START: CONNECTION METHODS **/
	initSelectedConnectionPropertiesData : function(connection) {
		var WF = myWFObj.getTaskFlowChart();
		var source_table = WF.TaskFlow.getTaskLabelByTaskId(connection.sourceId);
		var target_table = WF.TaskFlow.getTaskLabelByTaskId(connection.targetId);
		
		var attrs = $("#" + WF.TaskFlow.main_tasks_flow_obj_id + " #" + connection.sourceId + " > ." + WF.TaskFlow.task_eps_class_name + " .table_attrs .table_attr .check input");
		
		var source_attributes = [];
		for (var i = 0; i < attrs.length; i++) {
			var attr = $(attrs[i]).attr("attribute");
			
			if (attr != "*") 
				source_attributes.push(attr);
		}
		
		attrs = $("#" + WF.TaskFlow.main_tasks_flow_obj_id + " #" + connection.targetId + " > ." + WF.TaskFlow.task_eps_class_name + " .table_attrs .table_attr .check input");
		
		var target_attributes = [];
		for (var i = 0; i < attrs.length; i++) {
			var attr = $(attrs[i]).attr("attribute");
			
			if (attr != "*") 
				target_attributes.push(attr);
		}
		
		DBQueryTaskPropertyObj.selected_connection_properties_data = {
			source_table: source_table ? source_table : "",
			source_attributes: source_attributes,
			target_table: target_table ? target_table : "",
			target_attributes: target_attributes
		};
	},
	
	onLoadConnectionProperties : function(properties_html_elm, connection, connection_property_values) {
		//console.debug(properties_html_elm);
		//console.debug(connection);
		//console.debug(connection_property_values);
		
		//PREPARE CONNECTION PROPERTIES DATA
		DBQueryTaskPropertyObj.old_connection_property_values = connection_property_values;
		DBQueryTaskPropertyObj.initSelectedConnectionPropertiesData(connection);
		
		var properties_data = DBQueryTaskPropertyObj.selected_connection_properties_data;
		
		properties_html_elm.find('.db_table_connection_html .header .tables_join').val(connection_property_values.tables_join);
		properties_html_elm.find('.db_table_connection_html .header .source_table').val(properties_data.source_table);
		properties_html_elm.find('.db_table_connection_html .header .target_table').val(properties_data.target_table);
		
		properties_html_elm.find('.db_table_connection_html th.source_column').html(properties_data.source_table);
		properties_html_elm.find('.db_table_connection_html th.target_column').html(properties_data.target_table);
		
		var attributes = connection_property_values.attributes;
		
		var html;
		
		if (!connection_property_values || !connection_property_values.source_columns || connection_property_values.source_columns.length == 0) {
			html = DBQueryTaskPropertyObj.getTableJoinKey();
		}
		else {
			if (!$.isArray(connection_property_values.source_columns) && !$.isPlainObject(connection_property_values.source_columns)) {
				connection_property_values.source_columns = [ connection_property_values.source_columns ];
				connection_property_values.target_columns = [ connection_property_values.target_columns ];
				connection_property_values.column_values = [ connection_property_values.column_values ];
				connection_property_values.operators = [ connection_property_values.operators ];
			}
			
			html = "";
			
			for (var i in connection_property_values.source_columns) {
				if (i >= 0) {
					var data = {
						source_column: connection_property_values.source_columns[i],
						target_column: connection_property_values.target_columns[i],
						column_value: connection_property_values.column_values[i],
						operator: connection_property_values.operators[i]
					};
			
					html += DBQueryTaskPropertyObj.getTableJoinKey(data);
				}
			}
		}
		
		if (!html) {
			myWFObj.getTaskFlowChart().StatusMessage.showError("Error: Couldn't detect this connection's properties. Please remove this connection, create a new one and try again...");
		}
		else {
			properties_html_elm.find(".db_table_connection_html .table_attrs").html(html);
		}
	},
	
	onSubmitConnectionProperties : function(properties_html_elm, connection, connection_property_values) {
		//console.debug(properties_html_elm);
		//console.debug(connection);
		//console.debug(connection_property_values);
		
		var properties_data = DBQueryTaskPropertyObj.selected_connection_properties_data;
		
		var source_columns = properties_html_elm.find(".db_table_connection_html .source_column .connection_property_field");
		var target_columns = properties_html_elm.find(".db_table_connection_html .target_column .connection_property_field");
		
		var status = true;
		var error_message = "";
		
		for (var i = 0; i < source_columns.length; i++) {
			var source_column = $(source_columns[i]).val();
			var target_column = $(target_columns[i]).val();
		
			if (!source_column && !target_column) {
				status = false;
				error_message = "Error: Parent and child attribute names cannot be empty!";
				break;
			}
		}
		
		if (!status) {
			myWFObj.getTaskFlowChart().StatusMessage.showError(error_message);
		}
		
		return status;
	},
	
	onCompleteConnectionProperties : function(properties_html_elm, connection, connection_property_values, status) {
		if (status) {
			if (typeof DBQueryTaskPropertyObj.on_complete_connection_properties == "function") {
				DBQueryTaskPropertyObj.on_complete_connection_properties(myWFObj.getTaskFlowChart(), connection, DBQueryTaskPropertyObj.old_connection_property_values, connection_property_values);
			}
			
			DBQueryTaskPropertyObj.selected_connection_properties_data = null;
		}
	},
	
	onSuccessConnectionDeletion : function(connection) {
		var props = myWFObj.getTaskFlowChart().TaskFlow.connections_properties[connection.id];
		
		if (typeof DBQueryTaskPropertyObj.on_complete_connection_properties == "function") {
			DBQueryTaskPropertyObj.on_complete_connection_properties(myWFObj.getTaskFlowChart(), connection, props);
		}
		
		return true;
	},
	
	onSuccessConnectionDrag : function(conn) {
		if (!invalidateTaskConnectionIfItIsToItSelf(conn)) {
			myWFObj.getTaskFlowChart().Property.hideSelectedConnectionProperties();
			
			return false;
		}
		
		return true;
	},
	
	onSuccessConnectionDrop : function(conn) {
		if (conn.sourceId == conn.targetId) {
			myWFObj.getTaskFlowChart().StatusMessage.showError("Invalid connection. You cannot create self-connections.\nIf you wish to create a connection between the same tables, please create another task with the table name but with a different alias.");
			return false;
		}
		
		//checks if already exists the same connection.
		var connections = myWFObj.getTaskFlowChart().TaskFlow.getSourceConnections(conn.sourceId);
	
		var exists = false;
		for (var i = 0; i < connections.length; i++) {
			var c = connections[i];
			
			if (c.id != conn.connection.id && c.sourceId == conn.sourceId && c.targetId == conn.targetId) {
				exists = true;
				break;
			}
		}
		
		if (exists) {
			myWFObj.getTaskFlowChart().StatusMessage.showError("Already exists a connection with these tables.\nYou cannot create repeated connections.\nPlease use the existent connection.");
			return false;
		}
		
		var status = onTableConnectionDrop(conn);
		
		if (status && conn.sourceId != conn.targetId) { //if not the same table
			DBQueryTaskPropertyObj.initSelectedConnectionPropertiesData(conn);
		
			var properties_data = DBQueryTaskPropertyObj.selected_connection_properties_data;
			var source_attributes = properties_data["source_attributes"];
			var target_attributes = properties_data["target_attributes"];
			
			//finds the same attribute in both tables. Note that we don't know what are the primary keys, so we can only find the attributes with the same name in both tables.
			var conn_attrs = {};
			
			for (var i = 0; i < source_attributes.length; i++) {
				var source_attribute = source_attributes[i];
				
				for (var j = 0; j < target_attributes.length; j++) {
					var target_attribute = target_attributes[j];
					
					if (target_attribute == source_attribute) {
						conn_attrs[source_attribute] = target_attribute;
						break;
					}
				}
			}
			
			//add connection properties with correspondent attributes
			var props = myWFObj.getTaskFlowChart().TaskFlow.connections_properties[conn.connection.id];
			var old_props = props;
			
			if (!props)
				myWFObj.getTaskFlowChart().TaskFlow.connections_properties[conn.connection.id] = props = {};
			
			if (!props.source_columns) {
				props.source_columns = [];
				props.target_columns = [];
				props.column_values = [];
				props.operators = [];
			}
			else	if (!$.isArray(props.source_columns) && !$.isPlainObject(props.source_columns)) {
				props.source_columns = [ props.source_columns ];
				props.target_columns = [ props.target_columns ];
				props.column_values = [ props.column_values ];
				props.operators = [ props.operators ];
			}
			
			//if props.source_columns is a plain object, gets the maximum index
			var max_index = -1;
			
			if ($.isPlainObject(props.source_columns))
				for (var i in props.source_columns)
					if (i > max_index)
						max_index = i;
			
			max_index++;
			
			//sets the pk_name to connection: source and target tables
			for (var src_attr_name in conn_attrs) {
				var trg_attr_name = conn_attrs[src_attr_name];
				
				if ($.isArray(props.source_columns)) {
					//only adds if not exists yet
					if ($.inArray(src_attr_name, props.source_columns) == -1 || $.inArray(trg_attr_name, props.target_columns) == -1) {
						props.source_columns.push(src_attr_name);
						props.target_columns.push(trg_attr_name);
						props.column_values.push("");
						props.operators.push("=");
					}
				}
				else {
					//only adds if not exists yet
					for (var idx in props.source_columns)
						if (props.source_columns[idx] == src_attr_name && props.target_columns[idx] == trg_attr_name) {
							props.source_columns[max_index] = src_attr_name;
							props.target_columns[max_index] = trg_attr_name;
							props.column_values[max_index] = "";
							props.operators[max_index] = "=";
							max_index++;
							break;
						}
				}
			}
			
			props["tables_join"] = "inner";
			props["source_table"] = properties_data.source_table;
			props["target_table"] = properties_data.target_table;
			//console.log(props);
			
			//refresh connection with new configurations
			if (typeof DBQueryTaskPropertyObj.on_complete_connection_properties == "function")
				DBQueryTaskPropertyObj.on_complete_connection_properties(myWFObj.getTaskFlowChart(), conn.connection, old_props, props);
		}
		
		if (DBQueryTaskPropertyObj.show_properties_on_connection_drop)
			myWFObj.getTaskFlowChart().Property.showConnectionProperties(conn.connection.id);
		
		return status;
	},
	
	addTableJoinKey : function(elm) {
		var html = DBQueryTaskPropertyObj.getTableJoinKey();
		
		if (!html) {
			myWFObj.getTaskFlowChart().StatusMessage.showError("Error: Couldn't detect this connection's properties. Please remove this connection, create a new one and try again...");
		}
		else {
			var db_table_connection_html = elm ? $(elm).closest(".db_table_connection_html") : $("#" + myWFObj.getTaskFlowChart().Property.selected_connection_properties_id + " .db_table_connection_html");
			
			db_table_connection_html.find(".table_attrs").append(html);
		}
	},
	
	removeTableJoinKey : function(elm) {
		$(elm).parent().parent().remove();
	},
	
	getTableJoinKey : function(data) {
		var properties_source_attributes = [];
		var properties_target_attributes = [];
		
		var properties_data = DBQueryTaskPropertyObj.selected_connection_properties_data;
		
		if (properties_data) {
			properties_source_attributes = properties_data.source_attributes ? properties_data.source_attributes : [];
			properties_target_attributes = properties_data.target_attributes ? properties_data.target_attributes : [];
		}
		
		if (properties_source_attributes.length > 0 && properties_target_attributes.length > 0) {
			var operators = ["=", "!=", ">", ">=", "<=", "like", "not like", "in", "not in", "is", "is not"];
			
			var source_column = "", target_column = "", column_value = "", operator = "";
		
			if (data) {
				source_column = data.source_column ? data.source_column : "";
				target_column = data.target_column ? data.target_column : "";
				column_value = data.column_value ? data.column_value : "";
				operator = data.operator ? data.operator : "";
			}
			
			var html = '<tr>'
				+ '<td class="source_column"><select class="connection_property_field" name="source_columns[]"><option></option>';
			for (var j = 0; j < properties_source_attributes.length; j++) {
				html += '<option ' + (properties_source_attributes[j] == source_column ? "selected" : "") + '>' + properties_source_attributes[j] + '</option>';
			}
			html +=	'</select></td>'
				+ '<td class="operator"><select class="connection_property_field" name="operators[]">';
			for (var j = 0; j < operators.length; j++) {
				html += '<option ' + (operators[j] == operator ? "selected" : "") + '>' + operators[j] + '</option>';
			}
			html +=	'</select></td>'
				+ '<td class="target_column"><select class="connection_property_field" name="target_columns[]"><option></option>';
			for (var j = 0; j < properties_target_attributes.length; j++) {
				html += '<option ' + (properties_target_attributes[j] == target_column ? "selected" : "") + '>' + properties_target_attributes[j] + '</option>';
			}
			html += '</select></td>'
				+ '<td class="column_value"><input class="connection_property_field" name="column_values[]" value="' + column_value + '" /></td>'
				+ '<td class="table_attr_icons"><a class="icon delete" onClick="DBQueryTaskPropertyObj.removeTableJoinKey(this)">remove</a></td>'
			+ '</tr>';
			
			return html;
		}
	},
	/** END: CONNECTION METHODS **/
	
	/** END: MENUS METHODS **/
	onShowTaskMenu : function(task_id, j_task, task_context_menu) {
		task_context_menu.find(".start_task").off().attr("onclick", "return DBQueryTaskPropertyObj.setSelectedStartTask();").children("a").html("Is Main Table");
	},
	
	setSelectedStartTask : function() {
		var WF = myWFObj.getTaskFlowChart();
		WF.ContextMenu.hideContextMenus();
		
		var task_id = WF.ContextMenu.getContextMenuTaskId();
		this.setStartTaskById(task_id);
		
		return false;
	},
	
	setStartTaskById : function(task_id) {
		var WF = myWFObj.getTaskFlowChart();
		var tasks = WF.TaskFlow.getAllTasks();
		var j_task = WF.TaskFlow.getTaskById(task_id);
		
		for (var i = 0, l = tasks.length; i < l; i++) {
			var task = $(tasks[i]);
			task.removeAttr("is_start_task");
			task.removeClass(WF.TaskFlow.start_task_class_name);
		}
		
		if (j_task[0]) {
			j_task.attr("is_start_task", 1);
			j_task.addClass(WF.TaskFlow.start_task_class_name);
		}
		
		this.onCompleteSelectStartTask(task_id, j_task);
	},
	
	onCompleteSelectStartTask : function(task_id, j_task) {
		if (typeof DBQueryTaskPropertyObj.on_complete_select_start_task == "function")
			DBQueryTaskPropertyObj.on_complete_select_start_task(myWFObj.getTaskFlowChart(), task_id, j_task);
	},
	/** END: MENUS METHODS **/
};
