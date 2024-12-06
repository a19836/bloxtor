var old_tables_names = {};
var old_tables_attributes_names = {};
var auto_sync_error_message_shown = false;
var is_workflow_already_loaded = false; //check if workflow was already loaded for the first time
var sync_with_db_server_called = false;

var MyFancyPopupCreateDiagramSQL = new MyFancyPopupClass();
var MyFancyPopupSyncSQL = new MyFancyPopupClass();

$(function () {
	$(window).bind('beforeunload', function () {
		if (taskFlowChartObj.TaskFile.isWorkFlowChangedFromLastSaving()) {
			if (window.parent && window.parent.iframe_overlay)
				window.parent.iframe_overlay.hide();
			
			return "If you proceed your changes won't be saved. Do you wish to continue?";
		}
		
		return null;
	});
	
	//prepare top_bar
	$(".taskflowchart").addClass("with_top_bar_menu fixed_side_properties").children(".workflow_menu").addClass("top_bar_menu");
	
	//init auto save
	addAutoSaveMenu(".taskflowchart.with_top_bar_menu .workflow_menu.top_bar_menu li.save", "onToggleWorkflowAutoSave");
	addAutoConvertMenu(".taskflowchart.with_top_bar_menu .workflow_menu.top_bar_menu li.save", "onToggleWorkflowAutoConvert");
	enableAutoSave(onToggleWorkflowAutoSave);
	enableAutoConvert(onToggleWorkflowAutoConvert);
	initAutoSave(".taskflowchart.with_top_bar_menu .workflow_menu.top_bar_menu li.save a");
	
	$(".taskflowchart.with_top_bar_menu .workflow_menu.top_bar_menu li.auto_save_activation").addClass("with_padding");
	$(".taskflowchart.with_top_bar_menu .workflow_menu.top_bar_menu li.auto_convert_activation").addClass("with_padding");
	
	//init workflow
	taskFlowChartObj.TaskFlow.default_connection_connector = "Flowchart";
	taskFlowChartObj.TaskFlow.default_connection_overlay = "One To One";
	//taskFlowChartObj.TaskFlow.available_connection_connectors_type = ["Flowchart"];
	taskFlowChartObj.TaskFlow.available_connection_overlays_type = ["One To One", "Many To One", "One To Many"]; //Do not add "Many To Many" bc it doesn't make sense for the db relational diagram.
	
	taskFlowChartObj.TaskFile.on_success_read = updateTasksAfterFileRead;
	taskFlowChartObj.TaskFile.on_success_update = updateTasksAfterFileRead;
	taskFlowChartObj.TaskFile.on_success_save = updateTasksAfterFileSave;
	
	//prepare task contextmenu
	taskFlowChartObj.onReady(function() {
		$("#" + taskFlowChartObj.ContextMenu.task_context_menu_id + " .set_label a").html("Edit Table Name");
		
		var start_task = $("#" + taskFlowChartObj.ContextMenu.task_context_menu_id + " .start_task a");
		start_task.html("Get DB Table\'s Attributes");
		start_task.attr("onClick", "");
		start_task.click(function() {
			taskFlowChartObj.ContextMenu.hideContextMenus();
			
			var task_id = taskFlowChartObj.ContextMenu.getContextMenuTaskId();
			
			updateTaskTableAttributes(task_id);
		});
	});
	
	DBTableTaskPropertyObj.show_properties_on_connection_drop = true;
});

function onToggleFullScreen(in_full_screen) {
	taskFlowChartObj.resizePanels();
}

function updateTaskTableAttributes(task_id, do_not_confirm, opts) {
	if (task_id) {
		var table_name = taskFlowChartObj.TaskFlow.getTaskLabelByTaskId(task_id);
		table_name = table_name ? table_name.trim() : "";

		if (table_name && (do_not_confirm || confirm("The system will now get the DB\'s attributes for the table '" + table_name + "'.\nDo you wish to proceed?"))) {
			var url = get_db_data_url.replace("#table#", table_name);
			
			$.ajax({
				type : "get",
				url : url,
				dataType : "json",
				success : function(data, textStatus, jqXHR) {
					if (data) {
						//preparing attributes data
						var attributes_data = {};
						
						for (var attr_name in data)
							if (attr_name != "properties")
								attributes_data[attr_name] = data[attr_name]["properties"];
						
						//preparing attributes
						DBTableTaskPropertyObj.updateTaskPropertiesFromTableAttributes(task_id, attributes_data);
						
						//preparing old_tables_attributes_names
						old_tables_attributes_names[task_id] = [];
						
						for (var attr_name in attributes_data)
							old_tables_attributes_names[task_id].push(attr_name);
						
						//calling on success
						if ($.isPlainObject(opts) && typeof opts["success"] == "function")
							opts["success"](task_id, data);
					}
				},
				error : function(jqXHR, textStatus, errorThrown) { 
					if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
						showAjaxLoginPopup(jquery_native_xhr_object.responseURL, url, function() {
							taskFlowChartObj.StatusMessage.removeLastShownMessage("error");
							updateTaskTableAttributes(task_id, true);
						});
					else if (jqXHR.responseText) {
						taskFlowChartObj.StatusMessage.showError(jqXHR.responseText);
						
						if ($.isPlainObject(opts) && typeof opts["error"] == "function")
							opts["error"](task_id, data);
					}
				},
			});
		}
	}
}

/* MENUS Methods */

function saveDBDiagram() {
	prepareAutoSaveVars();
	var local_is_from_auto_save = is_from_auto_save;
	
	//prepare old_tables_names and old_tables_attributes_names settings
	if (!$.isPlainObject(taskFlowChartObj.TaskFile.file_settings)) 
		taskFlowChartObj.TaskFile.file_settings = {};
	
	taskFlowChartObj.TaskFile.file_settings["old_tables_names"] = old_tables_names;
	taskFlowChartObj.TaskFile.file_settings["old_tables_attributes_names"] = old_tables_attributes_names;
	
	if (taskFlowChartObj.TaskFile.isWorkFlowChangedFromLastSaving()) {
		//save workflow
		taskFlowChartObj.TaskFile.save(null, {
			success: function(data, textStatus, jqXHR) {
				if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
					showAjaxLoginPopup(jquery_native_xhr_object.responseURL, taskFlowChartObj.TaskFile.set_tasks_file_url, function() {
						taskFlowChartObj.StatusMessage.removeLastShownMessage("error");
						
						saveDBDiagram();
					});
				else if (local_is_from_auto_save) {
					taskFlowChartObj.StatusMessage.removeMessages("status");
					resetAutoSave();
				}
			},
			timeout: local_is_from_auto_save && auto_save_connection_ttl ? auto_save_connection_ttl : 0,
		});
	}
	else if (!local_is_from_auto_save)
		StatusMessageHandler.showMessage("Nothing to save.", "", "bottom_messages", 1500);
	else
		resetAutoSave();

	return false;
}

function updateDBDiagram() {
	prepareAutoSaveVars();
	var local_is_from_auto_save = is_from_auto_save;
	
	taskFlowChartObj.TaskFile.update(get_updated_db_diagram_url, {
		success: function(data, textStatus, jqXHR) {
			if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
				showAjaxLoginPopup(jquery_native_xhr_object.responseURL, taskFlowChartObj.TaskFile.set_tasks_file_url, function() {
					taskFlowChartObj.StatusMessage.removeLastShownMessage("error");
					updateDBDiagram();
				});
			else if (local_is_from_auto_save)
				resetAutoSave();
		},
		error: function(jqXHR, textStatus, errorThrown) {
			if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
				showAjaxLoginPopup(jquery_native_xhr_object.responseURL, [ taskFlowChartObj.TaskFile.set_tasks_file_url, get_updated_db_diagram_url ], function() {
					taskFlowChartObj.StatusMessage.removeLastShownMessage("error");
					updateDBDiagram();
				});
			else if (local_is_from_auto_save)
				resetAutoSave();
		},
	});

	return false;
}

function addNewTable() {
	var table_name = prompt("Please enter the table name:");
	
	if (table_name != null && ("" + table_name).replace(/\s+/, "") != "") {
		var label_obj = {label: table_name};
		
		//check if table already exists
		if (isTaskTableLabelValid(label_obj)) {
			var task_id = taskFlowChartObj.ContextMenu.addTaskByType(task_type_id);
			
			if (task_id) {
				taskFlowChartObj.TaskFlow.setTaskLabelByTaskId(task_id, label_obj); //set {label: table_name}, so the TaskFlow.setTaskLabel method ignores the prompt and adds the default label or an auto generated label.
			
				//add id, created_date and modified_date attributes by default
				var task_label = taskFlowChartObj.TaskFlow.getTaskLabelByTaskId(task_id);
				var task_property_values = taskFlowChartObj.TaskFlow.tasks_properties[task_id];
				task_property_values = DBTableTaskPropertyObj.prepareTaskPropertyValuesWithDefaultAttributes(task_property_values, task_label);
				taskFlowChartObj.TaskFlow.tasks_properties[task_id] = task_property_values;
				
				DBTableTaskPropertyObj.prepareShortTableAttributes(task_id, task_property_values);
				
				//open properties
				//taskFlowChartObj.Property.showTaskProperties(task_id); //disable show proeprties bc is annoying
				
				 old_tables_names[task_id] = "";
				 old_tables_attributes_names[task_id] = {};
				
				return task_id;
			}
			else
				taskFlowChartObj.StatusMessage.showError("Could not add table '" + table_name + "' to diagram. Please try again...");
		}
	}
	else
		taskFlowChartObj.StatusMessage.showError("Table name cannot be empty!");
}

//default_task are used in the admin_menu.js when we dragged tables from the Left_panel tree to the DB diagram.
function addExistentTable(table_name, offset) {
	if (typeof table_name == "string" && table_name.replace(/\s+/, "") != "") {
		var label_obj = {label: table_name};
		
		//check if table already exists
		if (isTaskTableLabelValid(label_obj)) {
			var task_id = taskFlowChartObj.ContextMenu.addTaskByType(task_type_id, offset);
			
			if (task_id) {
				taskFlowChartObj.TaskFlow.setTaskLabelByTaskId(task_id, label_obj); //set {label: table_name}, so the TaskFlow.setTaskLabel method ignores the prompt and adds the default label or an auto generated label.
				
				//update real table attributes
				updateTaskTableAttributes(task_id, true, {
					success: function() {
						//open properties
						//taskFlowChartObj.Property.showTaskProperties(task_id); //disable show proeprties bc is annoying
						
						//re-update old_tables_names and old_tables_attributes_names
						onDBTableTaskCreation(task_id);
					},
					error: function() {
						//delete task
						taskFlowChartObj.TaskFlow.deleteTask(task_id, {confirm: false});
						
						taskFlowChartObj.StatusMessage.showError("Could not add table '" + table_name + "' to diagram. Please try again...");
					}
				});
				
				return task_id;
			}
			else
				taskFlowChartObj.StatusMessage.showError("Could not add table '" + table_name + "' to diagram. Please try again...");
		}
	}
	else
		taskFlowChartObj.StatusMessage.showError("Table name cannot be empty!");
}

function createDiagamSQL() {
	var popup = $('.create_diagram_sql_popup');
	
	if (!popup[0]) {
		popup = $('<div class="create_diagram_sql_popup with_iframe_title myfancypopup"><iframe></iframe></div>');
		$("body").append(popup);
	}
	else {
		//remove and readd iframe so we don't see the previous loaded html
		popup.children("iframe").remove(); 
		popup.append('<iframe></iframe>');
	}
	
	MyFancyPopupCreateDiagramSQL.init({
		elementToShow: popup,
		parentElement: document,
		type: "iframe",
		url: create_diagram_sql_url,
	});
	
	MyFancyPopupCreateDiagramSQL.showPopup();
}

function sortWorkflowTables() {
	var sort_type = 1;
	taskFlowChartObj.TaskSort.sortTasks(sort_type);
}

/* SYNC Methods */

function prepareDiagramSettings() {
	var file_settings = taskFlowChartObj.TaskFile.file_settings;
	var workflow_menu = $("#" + taskFlowChartObj.ContextMenu.main_workflow_menu_obj_id);
	
	if ($.isPlainObject(file_settings)) {
		var workflow_data = taskFlowChartObj.TaskFile.getWorkFlowData();
		var existent_tasks = workflow_data && workflow_data["tasks"] ? workflow_data["tasks"] : {};
		
		//prepare old_tables_names setting
		if (file_settings.hasOwnProperty("old_tables_names") && $.isPlainObject(file_settings["old_tables_names"]))
			for (var task_id in file_settings["old_tables_names"])
				if (existent_tasks.hasOwnProperty(task_id)) { //only add existent tables
					if (!is_workflow_already_loaded) //if first worklow load, set it with loaded workflow settings, overwriting table
						old_tables_names[task_id] = file_settings["old_tables_names"][task_id];
					else if (!old_tables_names.hasOwnProperty(task_id)) //if worklow already loaded and table don't exist yet, set with loaded workflow settings
						old_tables_names[task_id] = file_settings["old_tables_names"][task_id];
				}
		
		//prepare old_tables_attributes_names setting
		if (file_settings.hasOwnProperty("old_tables_attributes_names") && $.isPlainObject(file_settings["old_tables_attributes_names"]))
			for (var task_id in file_settings["old_tables_attributes_names"])
				if (existent_tasks.hasOwnProperty(task_id)) { //only add existent tables
					if (!is_workflow_already_loaded) //if first worklow load, set it with loaded workflow settings, overwriting table
						old_tables_attributes_names[task_id] = file_settings["old_tables_attributes_names"][task_id];
					else if (!old_tables_attributes_names.hasOwnProperty(task_id)) //if worklow already loaded and table don't exist yet, set with loaded workflow settings
						old_tables_attributes_names[task_id] = file_settings["old_tables_attributes_names"][task_id];
				}
	}
	
	//prepare sync_with_db_server setting
	var sync_automatically_with_db_server = workflow_menu.find(".sync_automatically_with_db_server");
	var sync_enable = sync_automatically_with_db_server.hasClass("sync_enable");
	
	if (!$.isPlainObject(file_settings) || !file_settings.hasOwnProperty("sync_with_db_server") || file_settings["sync_with_db_server"] == 1) {
		if (!sync_enable)
			toggleAutoSyncWithDBServer( sync_automatically_with_db_server.children("a")[0] );
		else if (!$.isPlainObject(file_settings) || !file_settings.hasOwnProperty("sync_with_db_server")) {
			if (!$.isPlainObject(taskFlowChartObj.TaskFile.file_settings))
				taskFlowChartObj.TaskFile.file_settings = {};
			
			taskFlowChartObj.TaskFile.file_settings["sync_with_db_server"] = 1;
			taskFlowChartObj.TaskFile.save(null, {silent: true});
		}
	}
	else if (sync_enable && $.isPlainObject(file_settings) && file_settings["sync_with_db_server"] != 1)
		toggleAutoSyncWithDBServer( sync_automatically_with_db_server.children("a")[0] );
	
	sync_automatically_with_db_server.removeClass("hidden");
	
	//set is_workflow_already_loaded to true when workflow gets loaded
	is_workflow_already_loaded = true;
}

function toggleAutoSyncWithDBServer(elm, sync_with_server) {
	elm = $(elm);
	var li = elm.parent();
	var sync_enable = li.hasClass("sync_enable");
	
	if (sync_enable || auto_convert || confirm("You are about to sync this diagram with DB server. Do you wish to proceed?")) {
		elm = $(elm);
		var li = elm.parent();
		var html = elm.html();
		var title = li.attr("title");
		var is_sync_with_db_server = $.isPlainObject(taskFlowChartObj.TaskFile.file_settings) && taskFlowChartObj.TaskFile.file_settings["sync_with_db_server"] == 1;
		var call_sync_with_db_server = false;
		
		if (!$.isPlainObject(taskFlowChartObj.TaskFile.file_settings))
			taskFlowChartObj.TaskFile.file_settings = {};
		
		if (sync_enable) {
			li.removeClass("sync_enable");
			html = html.replace("Disable", "Enable");
			title = title.replace("Disable", "Enable");
			
			taskFlowChartObj.TaskFile.file_settings["sync_with_db_server"] = 0;
		}
		else {
			li.addClass("sync_enable");
			html = html.replace("Enable", "Disable");
			title = title.replace("Enable", "Disable");
			
			taskFlowChartObj.TaskFile.file_settings["sync_with_db_server"] = 1;
			
			if (sync_with_server) 
				call_sync_with_db_server = true;
		}
		
		elm.html(html);
		li.attr("title", title);
		
		//save workflow
		var new_is_sync_with_db_server = taskFlowChartObj.TaskFile.file_settings["sync_with_db_server"] == 1;
		
		if (is_sync_with_db_server != new_is_sync_with_db_server) {
			sync_with_db_server_called = false;
			
			taskFlowChartObj.TaskFile.save(null, {
				silent: true,
				success: function(data, textStatus, jqXHR) {
					if (call_sync_with_db_server && !sync_with_db_server_called) //Note that the taskFlowChartObj.TaskFile.on_success_save already calls the syncWithDBServer, so we don't need to call it twice. This 'if statement' prevents this case.
						syncWithDBServer();
				},
			});
		}
		else if (call_sync_with_db_server)
			syncWithDBServer();
	}
}

function syncNowWithDBServer(elm) {
	if (auto_convert || confirm("You are about to sync this diagram with DB server. Do you wish to proceed?"))
		syncWithDBServer();
}

function syncWithDBServer(do_not_simulate) {
	sync_with_db_server_called = true;
	
	prepareAutoSaveVars();
	var local_is_from_auto_save = is_from_auto_save;
	
	var workflow_data = taskFlowChartObj.TaskFile.getWorkFlowData();
	
	//set original tables names and attributes names in workflow_data
	if (workflow_data && workflow_data["tasks"]) {
		for (var task_id in workflow_data["tasks"]) {
			var task = workflow_data["tasks"][task_id];
			
			//clone task properties, otherwise any change in the task properties will be saved in the workflow too
			task["properties"] = assignObjectRecursively({}, task["properties"]); 
			
			//set old/original table name
			task["old_label"] = old_tables_names.hasOwnProperty(task_id) ? old_tables_names[task_id] : "";
			
			//set old/original attributes names
			if (task["properties"] && old_tables_attributes_names[task_id])
				task["properties"]["table_attr_old_names"] = old_tables_attributes_names[task_id];
			
			workflow_data["tasks"][task_id] = task;
		}
		
		//set post data
		var post_data = {"sync" : true, "data" : workflow_data};
		
		if (!do_not_simulate)
			post_data["simulate"] = true;
		
		//call request
		$.ajax({
			type : "post",
			url : sync_diagram_with_db_server_url,
			data : post_data,
			dataType : "json",
			success : function(data, textStatus, jqXHR) {
				//console.log(data);
				
				if (!do_not_simulate) {
					//Note that if the diagram is empty, then the server will return a data["data"] and data["statements"] as an empty array, so we ust convert it to an object
					if ($.isPlainObject(data) && data.hasOwnProperty("data") && data.hasOwnProperty("statements")) {
						if ($.isArray(data["data"]))
							data["data"] = Object({}, data["data"]);
						
						if ($.isArray(data["statements"]))
							data["statements"] = Object({}, data["statements"]);
					}
					
					if ($.isPlainObject(data) && $.isPlainObject(data["data"]) && $.isPlainObject(data["statements"])) {
						var show_sql = false;
						var exists_sql = false;
						
						//check if exists attributes to add or delete
						for (var table_name in data["data"]) {
							var table_statements = data["statements"][table_name];
							var table_parsed_data = data["data"][table_name];
							var attributes_to_add = table_parsed_data["attributes_to_add"];
							var attributes_to_delete = table_parsed_data["attributes_to_delete"];
							
							if (
								(attributes_to_add && attributes_to_add.length > 0) || 
								(attributes_to_delete && attributes_to_delete.length > 0)
							) {
								show_sql = true;
								exists_sql = true;
								break;
							}
							else if (table_statements["sql_statements"] && table_statements["sql_statements"].length > 0)
								exists_sql = true;
						}
						
						//execute sql directly in server
						if (!show_sql && exists_sql)
							executeSyncSQLStatements(workflow_data, data["statements"]);
						else if (!local_is_from_auto_save) { //if manual action
							if (!exists_sql) {
								if (!$.isEmptyObject(workflow_data["tasks"]))
									StatusMessageHandler.showMessage("Nothing to sync...", "", "bottom_messages", 1500);
							}
							//show sql and confirm with user
							else if (show_sql)
								showSyncWithDBServerSQL(workflow_data, data["statements"]);
						}
						else if (show_sql && !auto_sync_error_message_shown) { //if automatically saving action and exists sql to show, shows error_message, but only show it once!
							//if auto-sync is enabled
							if (taskFlowChartObj.TaskFile.file_settings["sync_with_db_server"] == 1) {
								alert("System could NOT sync diagram with DB Server automatically! Please do it manually..."); //show in an ALERT box so the user can see it and don't automatically disappear...
								auto_sync_error_message_shown = true;
							}
						}
					}
					else
						taskFlowChartObj.StatusMessage.showError("Something went wrong with the syncronization with the DB server. Please try again...");
				}
				else
					checkSyncWithDBServerResult(local_is_from_auto_save, workflow_data, data);
			},
			error : function(jqXHR, textStatus, errorThrown) { 
				if (!local_is_from_auto_save) {
					if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
						showAjaxLoginPopup(jquery_native_xhr_object.responseURL, url, function() {
							taskFlowChartObj.StatusMessage.removeLastShownMessage("error");
							syncWithDBServer(do_not_simulate);
						});
					else if (jqXHR.responseText) 
						taskFlowChartObj.StatusMessage.showError(jqXHR.responseText);
				}
			},
		});
	}
}

function checkSyncWithDBServerResult(local_is_from_auto_save, workflow_data, data) {
	var status = false;
		
	if (data == 1) {
		status = true;
		
		//reset old_tables_names and old_tables_attributes_names
		for (var task_id in workflow_data["tasks"]) {
			var task = workflow_data["tasks"][task_id];
			var task_properties = task["properties"];
			
			//set old/original table name
			if (old_tables_names.hasOwnProperty(task_id)) {
				old_tables_names[task_id] = task["label"];
				
				//set old/original attributes names
				old_tables_attributes_names[task_id] = task["properties"] && task["properties"]["table_attr_names"] ? 
					$.map(assignObjectRecursively({}, task["properties"]["table_attr_names"]), function(value, idx) { return [value]; }) //clone object/array and convert it to array
				: [];
			}
		}
		
		if (!local_is_from_auto_save)
			StatusMessageHandler.showMessage("Synced successfully!", "", "bottom_messages", 1500);
		
		//save old_tables_names and old_tables_attributes_names
		saveDBDiagram();
	}
	else if ($.isPlainObject(data)) {
		status = true;
		
		//show errors
		if (!local_is_from_auto_save) {
			if (data["errors"]) {
				var errors = data["errors"];
				var msg = "Error trying to sync diagram with DB server:";
				
				$.each(errors, function(idx, error) {
					msg += "\n" + error;
				});
				
				taskFlowChartObj.StatusMessage.showError(msg);
			}
			else 
				taskFlowChartObj.StatusMessage.showMessage("Please confirm with your diagram changes were saved correctly in the DB server.");
		}
		
		//fetch new server data and update old/original table and attributes names with new server data, bc even if there were errors, some sql may hv been executed correctly to the server, so we need to get and update that changes...
		if (get_updated_db_diagram_url)
			$.ajax({
				type : "get",
				url : get_updated_db_diagram_url,
				dataType : "json",
				success : function(server_data, textStatus, jqXHR) {
					//console.log(server_data);
					
					if ($.isPlainObject(server_data) && server_data["tasks"]) {
						var server_tasks = server_data["tasks"];
						var msg = "";
						
						for (var task_id in workflow_data["tasks"]) {
							var task = workflow_data["tasks"][task_id];
							var table_name = task["label"];
							var old_table_name = task["old_label"];
							
							//set old/original table name
							if (old_tables_names.hasOwnProperty(task_id)) {
								//prepare function to update old/original attributes names
								var update_old_table_attributes = function(table_name) {
									var server_task = server_tasks[table_name];
									var server_table_attr_names = server_task["properties"] && server_task["properties"]["table_attr_names"] ? server_task["properties"]["table_attr_names"] : [];
									var table_attr_names = task["properties"] && task["properties"]["table_attr_names"] ? task["properties"]["table_attr_names"] : [];
									var table_attr_old_names = old_tables_attributes_names[task_id];
									//console.log("update_old_table_attributes");
									
									//update old_tables_attributes_names with new attribute names if already updated in the server
									$.each(table_attr_old_names, function(idx, old_attribute_name) {
										var new_attribute_name = table_attr_names[idx];
										var old_index = server_table_attr_names.indexOf(old_attribute_name);
										var new_index = server_table_attr_names.indexOf(new_attribute_name);
										//console.log("old_index:"+old_index);
										//console.log("new_index:"+new_index);
										
										//if old attribute doesn't exist anymore and new attribute exists.
										if (old_index == -1 && new_index != -1) {
											old_tables_attributes_names[task_id][idx] = new_attribute_name;
											
											//console.log("update old attribute from "+old_table_name+"."+old_attribute_name+" to "+table_name+"."+new_attribute_name);
										}
										//if attributes names don't exist anymore
										else if (old_index == -1 && new_index == -1) {
											if (old_attribute_name != new_attribute_name)
												msg += "\n- " + table_name + "." + old_attribute_name + " and " + table_name + "." + new_attribute_name + " attributes don't exist anymore.";
											else
												msg += "\n- " + table_name + "." + old_attribute_name + " attribute doesn't exist anymore.";
										}
										//if attributes names exists and are different
										else if (old_index != -1 && new_index != -1 && old_attribute_name != new_attribute_name)
											msg += "\n- " + table_name + "." + old_attribute_name + " and " + table_name + "." + new_attribute_name + " attributes already exists in server.";
									});
								};
								
								//set new table name
								if (server_tasks.hasOwnProperty(table_name)) {
									old_tables_names[task_id] = table_name;
									
									//set old/original attributes names based in server table
									update_old_table_attributes(table_name);
								}
								else if (server_tasks.hasOwnProperty(old_table_name)) {
									//set old/original attributes names based in server table
									update_old_table_attributes(old_table_name);
								}
							}
						}
						
						if (msg)
							taskFlowChartObj.StatusMessage.showError("The system detected some inconsistencies between the diagram and server:" + msg + "\n\nPlease correct them before you continue...");
						
						//save old_tables_names and old_tables_attributes_names
						saveDBDiagram();
					}
				}
			});
	}
	else if (!local_is_from_auto_save) 
		taskFlowChartObj.StatusMessage.showError("Error trying to sync diagram with DB server. Please try again");
	
	return status;
}

function showSyncWithDBServerSQL(workflow_data, statements) {
	//prepare statements html
	var html = '<ul>';
	
	if ($.isPlainObject(statements))
		for (var table_name in statements) {
			var table_statements = statements[table_name];
			var sql_statements = table_statements["sql_statements"];
			var sql_statements_labels = table_statements["sql_statements_labels"];
			
			if (sql_statements && sql_statements.length > 0) {
				html += '<li table_name="' + table_name + '">'
					+ '<div class="table_header">Statements for table: ' + table_name + '<span class="icon maximize" onClick="toggleSyncWithDBServerSQLStatements(this)" title="Toggle SQL statements">Toggle</span></div>';
				
				for (var i = 0; i < sql_statements.length; i++) {
					var sql = sql_statements[i];
					var sql_label = sql_statements_labels[i];
					
					html += '<div class="sql_statement">'
							+ '<label>' + sql_label + '</label>'
							+ '<textarea class="hidden" name="sql_statements[]">' + sql + '</textarea>'
							+ '<textarea class="editor">' + sql + '</textarea>'
						+ '</div>';
				}
				
				html += '</li>';
			}
		}
	
	html += '</ul>';
	
	//get popup
	var popup = $(".show_sync_sql_popup");
	
	if (!popup[0]) {
		popup = $('<div class="myfancypopup with_title show_sync_sql_popup">'
				+ '<div class="title">Please confirm the following Statements</div>'
				+ '<div class="button">'
					+ '<button onClick="proceedToExecuteSyncSQL(this)">Execute and Proceed</button>'
				+ '</div>'
			+ '</div>');
		$(document.body).append(popup);
	}
	
	//remove previous ul and add new one
	popup.children("ul").remove(); 
	popup.children(".title").after(html);
	
	//create sql editor
	popup.find(".sql_statement > textarea.editor").each(function(idx, textarea) {
		createSqlEditor(textarea);
	});
	
	popup.children("ul").accordion({
     	heightStyle: "content",
 		header: " > .table_header"
	})
	.sortable({
		axis: "y",
		handle: " > .table_header",
		stop: function( event, ui ) {
			// IE doesn't register the blur when sorting
			// so trigger focusout handlers to remove .ui-state-focus
			ui.item.children(".table_header").triggerHandler("focusout");

			// Refresh accordion to handle new order
			$(this).accordion( "refresh" );
		}
      });
	
	//open popup
	MyFancyPopupSyncSQL.init({
		elementToShow: popup,
		parentElement: document,
		workflow_data: workflow_data
	});
	MyFancyPopupSyncSQL.showPopup();
}

function toggleSyncWithDBServerSQLStatements(elm) {
	$(elm).parent().closest("li").toggleClass("show_sql");
}

function proceedToExecuteSyncSQL(elm) {
	var popup = $(elm).parent().closest(".show_sync_sql_popup");
	var lis = popup.find(" > ul > li");
	var statements = {};
	
	$.each(lis, function(idx, li) {
		li = $(li);
		var table_name = li.attr("table_name");
		var textareas = li.find(".sql_statement > textarea:not(.editor)");
		
		statements[table_name] = {
			"sql_statements": []
		};
		
		$.each(textareas, function(idx, textarea) {
			var sql = $(textarea).val();
			statements[table_name]["sql_statements"].push(sql);
		});
	});
	
	executeSyncSQLStatements(MyFancyPopupSyncSQL.settings.workflow_data, statements);
}

function executeSyncSQLStatements(workflow_data, statements) {
	prepareAutoSaveVars();
	var local_is_from_auto_save = is_from_auto_save;
	
	//set post data
	var post_data = {"sync" : true, "statements": statements};
	
	//call request
	$.ajax({
		type : "post",
		url : sync_diagram_with_db_server_url,
		data : post_data,
		dataType : "json",
		success : function(data, textStatus, jqXHR) {
			//console.log(data);
			
			var status = checkSyncWithDBServerResult(local_is_from_auto_save, workflow_data, data);
			
			if (status) {
				MyFancyPopupSyncSQL.hidePopup();
				
				//refresh db navigator tree
				if (window.parent != window && typeof window.parent.refreshLastNodeChilds == "function")
					window.parent.refreshLastNodeChilds();
			}
		},
		error : function(jqXHR, textStatus, errorThrown) { 
			if (!local_is_from_auto_save) {
				if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
					showAjaxLoginPopup(jquery_native_xhr_object.responseURL, url, function() {
						taskFlowChartObj.StatusMessage.removeLastShownMessage("error");
						executeSyncSQLStatements(workflow_data, sql_statements);
					});
				else if (jqXHR.responseText) 
					taskFlowChartObj.StatusMessage.showError(jqXHR.responseText);
			}
		},
	});
}

function createSqlEditor(textarea) {
	if (textarea) {
		var p = $(textarea).parent();
		
		ace.require("ace/ext/language_tools");
		var editor = ace.edit(textarea);
		editor.setTheme("ace/theme/chrome");
		editor.session.setMode("ace/mode/sql");
    	editor.setAutoScrollEditorIntoView(true);
		editor.setOption("maxLines", "Infinity");
		editor.setOption("minLines", 2);
		editor.setOptions({
			enableBasicAutocompletion: true,
			enableSnippets: true,
			enableLiveAutocompletion: true,
		});
		editor.setOption("wrap", true);
		editor.$blockScrolling = "Infinity";
		
		if (typeof setCodeEditorAutoCompleter == "function")
			setCodeEditorAutoCompleter(editor);
		
		editor.getSession().on("change", function () {
			var t = p.children("textarea:not(.editor)");
			t.val(editor.getSession().getValue());
		});
		
		p.find("textarea.ace_text-input").removeClass("ace_text-input"); //fixing problem with scroll up, where when focused or pressed key inside editor the page scrolls to top.
		
		p.data("editor", editor);
	}
}

/* TASKFLOWCHART Callbacks */

function updateTasksAfterFileRead(data) {
	$(taskFlowChartObj.TaskFlow.target_selector).each(function(idx, elm) {
		var task_id = $(elm).attr("id");
		
		//update short foreign keys
		DBTableTaskPropertyObj.updateShortTableForeignKeys(task_id);
	});
	
	//prepare table connections
	prepareTasksTableConnections(); //This function is in tasks/dbdiagram/global.js
	
	//prepare diagram settings
	prepareDiagramSettings(data);
	
	//hide loading
	$(".loading_panel").hide();
}

function updateTasksAfterFileSave() {
	prepareAutoSaveVars();
	
	var is_sync_with_db_server = $.isPlainObject(taskFlowChartObj.TaskFile.file_settings) && taskFlowChartObj.TaskFile.file_settings["sync_with_db_server"] == 1;
	
	if (is_sync_with_db_server && (auto_convert || (
		!is_from_auto_save && confirm("You are about to sync this diagram with DB server. Do you wish to proceed?")
	)))
		syncWithDBServer();
}

/* DBTableTaskPropertyObj Callbacks */

function onLoadDBTableTaskProperties(properties_html_elm, task_id, task_property_values) {
	//set old_name field
	if (task_property_values && task_property_values.table_attr_names) {
		var task_html_elm = properties_html_elm.find('.db_table_task_html');
		var selector = task_html_elm.hasClass("attributes_list_shown") ? ".list_attributes .list_attrs" : ".table_attrs";
		var table_inputs = task_html_elm.find(selector + " .table_attr_name input");
		var simple_inputs = task_html_elm.find(".simple_attributes > ul > li .simple_attr_name");
		
		$.each(task_property_values.table_attr_names, function(i, table_attr_name) {
			var old_name = old_tables_attributes_names && old_tables_attributes_names[task_id] && old_tables_attributes_names[task_id][i] ? old_tables_attributes_names[task_id][i] : table_attr_name;
			
			var table_input = table_inputs[i];
			var simple_input = simple_inputs[i];
			
			if (table_input)
				table_input.setAttribute("old_name", old_name);
			
			if (simple_input)
				simple_input.setAttribute("old_name", old_name);
		});
	}
}

function onSubmitDBTableTaskProperties(properties_html_elm, task_id, task_property_values) {
	//updating old_tables_attributes_names with new attributes order
	var fields = DBTableTaskPropertyObj.getParsedTaskPropertyFields(properties_html_elm, task_id);
	var fields_names = fields.table_attr_names;
	
	if (fields_names) {
		var new_original_table_attributes_names = [];
		
		for (var i = 0; i < fields_names.length; i++) {
			var field_name = fields_names[i];
			var old_attribute_name = field_name.hasAttribute("old_name") ? field_name.getAttribute("old_name") : "";
			
			new_original_table_attributes_names.push(old_attribute_name);
		}
		
		old_tables_attributes_names[task_id] = new_original_table_attributes_names;
	}
	
	return true;
}

function onDBTableTaskCreation(task_id) {
	//backup original tables names for the sync action
	var table_name = taskFlowChartObj.TaskFlow.getTaskLabelByTaskId(task_id);
	var task_property_values = taskFlowChartObj.TaskFlow.tasks_properties[task_id];
	
	old_tables_names[task_id] = table_name;
	old_tables_attributes_names[task_id] = task_property_values && task_property_values.table_attr_names ? 
		$.map(assignObjectRecursively({}, task_property_values.table_attr_names), function(value, idx) { return [value]; }) //clone object/array and convert it to array
	: null;
}

function onDBTableTaskDeletion(task_id) {
	delete old_tables_names[task_id];
	delete old_tables_attributes_names[task_id];
}

function onUpdateSimpleAttributesHtmlWithTableAttributes(elm) {
	var task_html_elm = $(elm).closest(".db_table_task_html");
	var selector = task_html_elm.hasClass("attributes_list_shown") ? ".list_attributes .list_attrs" : ".table_attrs";
	var table_inputs = task_html_elm.find(selector + " .table_attr_name input");
	var simple_inputs = task_html_elm.find(".simple_attributes > ul li .simple_attr_name");
	
	//set old_names from table inputs to simple inputs
	$.each(table_inputs, function(idx, table_input) {
		var old_name = table_input.hasAttribute("old_name") ? table_input.getAttribute("old_name") : "";
		var simple_input = simple_inputs[idx];
		
		if (simple_input)
			simple_input.setAttribute("old_name", old_name);
	});
}

function onUpdateTableAttributesHtmlWithSimpleAttributes(elm) {
	var task_html_elm = $(elm).closest(".db_table_task_html");
	var table_inputs = task_html_elm.find(".table_attrs .table_attr_name input"); //no need to check the list_attributes .list_attrs, bc this function runs always with the .table_attrs
	var simple_inputs = task_html_elm.find(".simple_attributes > ul li .simple_attr_name");
	
	//set old_names from simple inputs to table inputs
	$.each(simple_inputs, function(idx, simple_input) {
		var old_name = simple_input.hasAttribute("old_name") ? simple_input.getAttribute("old_name") : "";
		var table_input = table_inputs[idx];
		
		if (table_input)
			table_input.setAttribute("old_name", old_name);
	});
}

function onAddTaskPropertiesAttribute(task_id, attribute_name, attribute_data, new_attribute_index) {
	var attribute_name = attribute_data["name"] ? attribute_data["name"] : null; //Do not use attribute_name bc it may be empty
	
	if ($.isArray(old_tables_attributes_names[task_id]))
		old_tables_attributes_names[task_id].push(attribute_name);
	else {
		if (!$.isPlainObject(old_tables_attributes_names[task_id]))
			old_tables_attributes_names[task_id] = {};
		
		old_tables_attributes_names[task_id][new_attribute_index] = attribute_name;
	}
}

function onBeforeRemoveTaskPropertiesAttribute(task_id, attribute_name) {
	var task_property_values = taskFlowChartObj.TaskFlow.tasks_properties[task_id];
	
	//remove attribute from task properties
	if (task_property_values && task_property_values.table_attr_names)
		$.each(task_property_values.table_attr_names, function(i, table_attr_name) {
			if (table_attr_name == attribute_name) {
				if ($.isArray(old_tables_attributes_names[task_id]))
					old_tables_attributes_names[task_id].splice(i, 1);
				else if ($.isPlainObject(old_tables_attributes_names[task_id]))
					delete old_tables_attributes_names[task_id][i];
				
				return false; //exit loop
			}
		});
	
	return true;
}

function onBeforeSortTaskPropertiesAttributes(task_id, attributes_names) {
	if (attributes_names) {
		var task_property_values = taskFlowChartObj.TaskFlow.tasks_properties[task_id];
		
		if (task_property_values && task_property_values.table_attr_names) {
			var table_attr_names = $.map(assignObjectRecursively({}, task_property_values.table_attr_names), function(value, idx) { return [value]; }); //clone object/array and convert it to array
			
			$.each(task_property_values.table_attr_names, function(i, table_attr_name) {
				var from_index = table_attr_names.indexOf(table_attr_name);
				var to_index = attributes_names.indexOf(table_attr_name);
				//console.log("attr "+table_attr_name + "("+from_index+" => "+to_index+")");
				
				if (to_index != -1 && to_index != from_index) {
					var arr = old_tables_attributes_names[task_id];
					
					//convert object to array
					if ($.isPlainObject(arr))
						arr = $.map(arr, function(value, idx){
							return [value];
						});
					
					//reorder array
					var value = arr.splice(from_index, 1)[0];
					arr.splice(to_index, 0, value);
					
					old_tables_attributes_names[task_id] = arr;
					
					//update table_attr_names
					var value = table_attr_names.splice(from_index, 1)[0];
					table_attr_names.splice(to_index, 0, value);
				}
			});
		}
	}
	
	return false;
}
