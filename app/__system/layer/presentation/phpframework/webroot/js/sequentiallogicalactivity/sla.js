var cached_data_for_variables_in_workflow = {};
var update_sla_programming_task_variables_from_sla_groups = true;
var update_sla_programming_task_variables_from_workflow = false;
var save_sla_settings_func = null;
var saved_sla_action_settings_id = null;
var saved_sla_action_settings_code = null;
var saved_sla_action_settings_php_code = null;

function initSLA(main_elm) {
	chooseBusinessLogicFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeObjectPropertiesAndMethodsAndFunctionsFromTreeForBusinessLogic,
	});
	chooseBusinessLogicFromFileManagerTree.init("choose_business_logic_from_file_manager");
	
	chooseQueryFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeMapsAndOtherIbatisNodesFromTree,
	});
	chooseQueryFromFileManagerTree.init("choose_query_from_file_manager");
	
	chooseHibernateObjectMethodFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeMapsAndOtherHbnNodesFromTree,
	});
	chooseHibernateObjectMethodFromFileManagerTree.init("choose_hibernate_object_method_from_file_manager");
	
	chooseBlockFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeAllThatIsNotBlocksFromTree,
	});
	chooseBlockFromFileManagerTree.init("choose_block_from_file_manager");
	
	chooseViewFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeAllThatIsNotViewsFromTree,
	});
	chooseViewFromFileManagerTree.init("choose_view_from_file_manager");
	
	var sla = main_elm.find(".sla");
	
	//remove database options bc there are no detected db_drivers
	if (typeof db_brokers_drivers_tables_attributes != "undefined" && $.isEmptyObject(db_brokers_drivers_tables_attributes)) 
		initSLAGroupItemsActionType( sla.find(".sla_groups_flow > .sla_groups") );
	
	sla.tabs();
	
	//change some handlers
	ProgrammingTaskUtil.on_programming_task_choose_created_variable_callback = onSLAProgrammingTaskChooseCreatedVariable;
	
	//load task flow and code editor
	onLoadTaskFlowChartAndCodeEditor({parent_elm: sla});
	
	//setting the save_func in the CreateFormTaskPropertyObj
	if (typeof CreateFormTaskPropertyObj != "undefined" && CreateFormTaskPropertyObj)
		CreateFormTaskPropertyObj.editor_save_func = function () {
			$(".top_bar .save a").first().trigger("click");
		};
}

function initSLAGroupItemsActionType(main_elm) {
	//remove database options bc there are no detected db_drivers
	if (typeof db_brokers_drivers_tables_attributes != "undefined" && $.isEmptyObject(db_brokers_drivers_tables_attributes)) 
		main_elm.find(".sla_group_item > .sla_group_header > .action_type").each(function(idx, item) {
			 $(item).children("optgroup").first().remove(); 
		});
}

function initSLAAutoSaveActivationMenu() {
	$(".top_bar li.auto_save_activation").attr("onClick", "toggleAutoSaveCheckbox(this, onToggleSLAAutoSave)");
}

function onToggleSLAAutoSave() {
	if (typeof onToggleQueryAutoSave == "function")
		onToggleQueryAutoSave();
	
	onTogglePHPCodeAutoSave();
}

/* LOAD FUNCTIONS */

function loadSLASettingsActions(add_group_icon, actions, is_sub_group, asynchronous, original_actions) {
	if (actions) {
		$.each(actions, function (i, action) {
			var group_item = is_sub_group ? addNewSLASubGroup(add_group_icon) : addNewSLAGroup(add_group_icon);
			var original_action = $.isPlainObject(original_actions) || $.isArray(original_actions) ? original_actions[i] : null;
			
			if (asynchronous)
				setTimeout(function() {
					loadSLASettingsAction(action, group_item, true, original_action);
				}, 1);
			else
				loadSLASettingsAction(action, group_item, false, original_action);
		});
		
		var sla_groups_flow = $(add_group_icon).parent().closest(".sla_groups_flow");
		var exists_deprecated_actions = sla_groups_flow.find(".sla_groups .sla_group_item_undefined").length > 0;
		
		if (sla_groups_flow.children(".deprecated_actions_message").length == 0 && exists_deprecated_actions)
			sla_groups_flow.prepend('<div class="deprecated_actions_message">Attention: There are actions which are now DEPRECATED! Apparently this presentation layer is not connected anymore with all layers that some actions need use!</div>');
	}
}
		
function loadSLASettingsAction(action, group_item, asynchronous, original_action) {
	if ($.isPlainObject(action) && group_item[0]) {
		var result_var_name = action["result_var_name"];
		var action_type = ("" + action["action_type"]).toLowerCase();
		var action_value = action["action_value"];
		var original_action_value = $.isPlainObject(original_action) && original_action["action_value"] ? original_action["action_value"] : {};
		var condition_type = action["condition_type"];
		var condition_value = action["condition_value"];
		var action_description = action["action_description"];
		var task_default_values = {};
		
		switch (action_type) {
			case "html":
				task_default_values["createform"] = {
					"form_settings_data_type": action_value["form_settings_data_type"], 
					"form_settings_data": action_value["form_settings_data"]
				};
				break;
			
			case "callbusinesslogic":
			case "callibatisquery":
			case "callhibernatemethod":
			case "getquerydata":
			case "setquerydata":
			case "callfunction":
			case "callobjectmethod":
			case "restconnector":
			case "soapconnector":
				task_default_values[ action_type ] = action_value;
				break;
			
			case "show_ok_msg":
			case "show_ok_msg_and_stop":
			case "show_ok_msg_and_die":
			case "show_ok_msg_and_redirect":
			case "show_error_msg":
			case "show_error_msg_and_die":
			case "show_error_msg_and_stop":
			case "show_error_msg_and_redirect":
			case "alert_msg":
			case "alert_msg_and_stop":
			case "alert_msg_and_redirect":
				var message_elm = group_item.find(' > .sla_group_body > .message_action_body');
				message_elm.find(' > .message > input').val(action_value["message"]);
				message_elm.find(' > .redirect_url > input').val(action_value["redirect_url"]);
				break;
			
			case "redirect":
				var redirect_type = "";
				var redirect_url = "";
				
				if ($.isPlainObject(action_value)) {
					redirect_type = action_value["redirect_type"];
					redirect_url = action_value["redirect_url"];
				}
				else
					redirect_url = action_value;
				
				var redirect_elm = group_item.find(' > .sla_group_body > .redirect_action_body');
				redirect_elm.find(' > .redirect_type > select').val(redirect_type);
				redirect_elm.find(' > .redirect_url > input').val(redirect_url);
				break;
				
			case "return_previous_record":
			case "return_next_record":
			case "return_specific_record":
				var records_elm = group_item.find(' > .sla_group_body > .records_action_body');
				records_elm.find(' > .records_variable_name > input').val(action_value["records_variable_name"]);
				records_elm.find(' > .index_variable_name > input').val(action_value["index_variable_name"]);
				break;
			
			case "check_logged_user_permissions":
				var clupab = group_item.find(" > .sla_group_body > .check_logged_user_permissions_action_body");
				
				if (action_value["all_permissions_checked"] == 1)
					clupab.find(" > .all_permissions_checked > input").attr("checked", "checked").prop("checked", true);
				
				if (action_value["logged_user_id"])
					clupab.find(" > .logged_user_id > input").val(action_value["logged_user_id"]);
				
				if (action_value["entity_path_var_name"])
					clupab.find(" > .entity_path_var_name").val(action_value["entity_path_var_name"]);
				
				if (action_value["users_perms"] && ($.isArray(action_value["users_perms"]) || $.isPlainObject(action_value["users_perms"]))) {
					var add_elm = clupab.find(" > .users_perms > table > thead .add");
					
					if (action_value["users_perms"].hasOwnProperty("user_type_id") || action_value["users_perms"].hasOwnProperty("activity_id")) //This is very important bc the users_perms come from the workflow xml too, and it can be the item it-self. In this case we must convert it to an array.
						action_value["users_perms"] = [ action_value["users_perms"] ];
					
					$.each(action_value["users_perms"], function(idx, user_perm) {
						if ($.isPlainObject(user_perm)) {
							var row = addUserPermission(add_elm[0]);
							var user_type_id_elm = row.find(".user_type_id select");
							var activity_id_elm = row.find(".activity_id select");
							
							user_type_id_elm.val(user_perm["user_type_id"]);
							activity_id_elm.val(user_perm["activity_id"]);
							
							//in case the values don't exist, add them...
							if (user_type_id_elm.val() != user_perm["user_type_id"]) 
								user_type_id_elm.append('<option selected>NOT IN DB: ' + user_perm["user_type_id"] + '</option>');
							
							//in case the values don't exist, add them...
							if (activity_id_elm.val() != user_perm["activity_id"]) 
								activity_id_elm.append('<option selected>NOT IN DB: ' + user_perm["activity_id"] + '</option>');
						}
					});
				}
				break;
				
			case "code":
				group_item.find(' > .sla_group_body > .code_action_body > textarea').val(action_value);
				break;
				
			case "array":
				ArrayTaskUtilObj.onLoadArrayItems( group_item.find(' > .sla_group_body > .array_action_body'), action_value, "");
				break;
				
			case "string":
				if ($.isPlainObject(action_value)) {
					group_item.find(' > .sla_group_body > .string_action_body > .string > input').val(action_value["string"]);
					group_item.find(' > .sla_group_body > .string_action_body > .operator > select').val(action_value["operator"]);
				}
				else
					group_item.find(' > .sla_group_body > .string_action_body > .string > input').val(action_value);
				break;
				
			case "variable":
				if ($.isPlainObject(action_value)) {
					var variable = "" + action_value["variable"];
					variable = variable.trim().substr(0, 1) == '$' ? variable.trim().substr(1) : variable;
					group_item.find(' > .sla_group_body > .variable_action_body > .variable > input').val(variable);
					group_item.find(' > .sla_group_body > .variable_action_body > .operator > select').val(action_value["operator"]);
				}
				else {
					action_value = "" + action_value;
					action_value = action_value.trim().substr(0, 1) == '$' ? action_value.trim().substr(1) : action_value;
					group_item.find(' > .sla_group_body > .variable_action_body > .variable > input').val(action_value);
				}
				
				break;
				
			case "sanitize_variable":
				action_value = "" + action_value;
				action_value = action_value.trim().substr(0, 1) == '$' ? action_value.trim().substr(1) : action_value;
				group_item.find(' > .sla_group_body > .sanitize_variable_action_body > input').val(action_value);
				
				break;
				
			case "validate_variable":
				var method = action_value["method"];
				var variable = action_value["variable"];
				var offset = action_value["offset"];
				
				variable = variable.trim().substr(0, 1) == '$' ? variable.trim().substr(1) : variable;
				
				var validate_variable_elm = group_item.find(' > .sla_group_body > .validate_variable_action_body');
				validate_variable_elm.find(' > .method > select').val(method);
				validate_variable_elm.find(' > .variable > input').val(variable);
				validate_variable_elm.find(' > .offset > input').val(offset);
				
				validate_variable_elm.find(' > .method > select').trigger("change");
				
				break;
				
			case "list_report":
				var list_report_elm = group_item.find(' > .sla_group_body > .list_report_action_body');
				list_report_elm.find(' > .type > select').val( action_value["type"] );
				list_report_elm.find(' > .doc_name > input').val( action_value["doc_name"] );
				list_report_elm.find(' > .continue > select').val( action_value["continue"] );
				
				var variable = "" + action_value["variable"];
				variable = variable.trim().substr(0, 1) == '$' ? variable.trim().substr(1) : variable;
				list_report_elm.find(' > .variable > input').val(variable);
				
				break;
				
			case "call_block":
				var call_block_elm = group_item.find(' > .sla_group_body > .call_block_action_body');
				call_block_elm.find(' > .block > input').val( action_value["block"] );
				
				if (action_value["project"]) {
					var select = call_block_elm.find(' > .project > select');
					select.val( action_value["project"] );
					
					if (select.val() != action_value["project"])
						select.append('<option value="' + action_value["project"] + '" selected>' + action_value["project"] + ' - DOES NOT EXIST ANYMORE</option>');
				}
				break;
				
			case "call_view":
				var call_view_elm = group_item.find(' > .sla_group_body > .call_view_action_body');
				call_view_elm.find(' > .view > input').val( action_value["view"] );
				
				if (action_value["project"]) {
					var select = call_view_elm.find(' > .project > select');
					select.val( action_value["project"] );
					
					if (select.val() != action_value["project"])
						select.append('<option value="' + action_value["project"] + '" selected>' + action_value["project"] + ' - DOES NOT EXIST ANYMORE</option>');
				}
				break;
			
			case "include_file":
				var include_file_elm = group_item.find(' > .sla_group_body > .include_file_action_body');
				include_file_elm.children('input.path').val( action_value["path"] );
				
				if (action_value["once"] == 1)
					include_file_elm.children('input.once').attr("checked", "checked").prop("checked", true);
				
				break;
			
			case "draw_graph":
				var draw_graph_elm = group_item.find(' > .sla_group_body > .draw_graph_action_body');
				
				if ($.isPlainObject(action_value)) {
					var draw_graph_settings_elm = draw_graph_elm.children(".draw_graph_settings");
					addDrawGraphSettingsDataSet( draw_graph_settings_elm.find(".graph_data_sets > label > .add")[0] );
					
					if (action_value.hasOwnProperty("code")) {
						var draw_graph_js_elm = draw_graph_elm.children(".draw_graph_js_code");
						draw_graph_js_elm.children("textarea").val(action_value["code"]);
					}
					else
						loadDrawGraphSettings(draw_graph_settings_elm, action_value, original_action_value);
				}
				break;
				
			case "loop":
				var loop_elm = group_item.find(' > .sla_group_body > .loop_action_body');
				var sub_add_group_icon = loop_elm.find(' > header > a');
				
				loop_elm.find(' > header > .records_variable_name > input').val(action_value["records_variable_name"]);
				loop_elm.find(' > header > .records_start_index > input').val(action_value["records_start_index"]);
				loop_elm.find(' > header > .records_end_index > input').val(action_value["records_end_index"]);
				loop_elm.find(' > header > .array_item_key_variable_name > input').val(action_value["array_item_key_variable_name"]);
				loop_elm.find(' > header > .array_item_value_variable_name > input').val(action_value["array_item_value_variable_name"]);
				
				loadSLASettingsActions(sub_add_group_icon[0], action_value["actions"], true, asynchronous, original_action_value["actions"]);
				
				break;
			
			case "group":
				var group_elm = group_item.find(' > .sla_group_body > .group_action_body');
				var sub_add_group_icon = group_elm.find(' > header > a');
				
				group_elm.find(' > header > .group_name > input').val(action_value["group_name"]);
				
				loadSLASettingsActions(sub_add_group_icon[0], action_value["actions"], true, asynchronous, original_action_value["actions"]);
				
				break;
		}
		
		initSLAGroupItemTasks(group_item, task_default_values);
		
		var group_header = group_item.children(".sla_group_header");
		
		if (result_var_name != "")
			group_header.children(".result_var_name").val(result_var_name).removeClass("result_var_name_output");
		
		var select = group_header.find(" > .sla_group_sub_header > .condition_type");
		select.val(condition_type);
		onGroupConditionTypeChange( select[0] );
		
		group_header.find(" > .sla_group_sub_header > .condition_value").val(condition_value);
		
		group_header.find(" > .sla_group_sub_header > .action_description > textarea").val(action_description);
		
		select = group_header.children(".action_type");
		select.val(action_type);
		onChangeSLAInputType( select[0] );
		
		switch (action_type) {
			case "insert":
			case "update":
			case "delete":
			case "select":
			case "count":
			case "procedure":
			case "getinsertedid":
				$(function () { //must be after everything loads otherwise the UI was not created yet
					var db_elm = group_item.find(' > .sla_group_body > .database_action_body');
					
					if (typeof DBQueryTaskPropertyObj != "undefined" && db_elm[0]) { //db_drivers can be null and so DBQueryTaskPropertyObj won't exists
						//load header fields
						var select = db_elm.find('.dal_broker > select');
						select.val(action_value["dal_broker"]);
						updateDALActionBroker(select[0]);
						
						select = db_elm.find('.db_type > select');
						var selected_type = select.val();
						
						//note that the updateDBActionType doesn't need to run bc the updateDBActionDriver runs in the updateDALActionBroker and does almost the same thing.
						if (selected_type != action_value["db_type"]) {
							select.val(action_value["db_type"]);
							
							updateDBActionType(select[0]);
						}
						
						select = db_elm.find('.db_driver > select');
						var selected_driver = select.val();
						
						//note that the updateDBActionDriver already runs in the updateDALActionBroker
						if (selected_driver != action_value["db_driver"]) {
							select.val(action_value["db_driver"]);
							
							updateDBActionDriver(select[0]);
						}
						
						if (action_type != "getinsertedid") {
							//load table fields
							if (action_value["table"]) {
								var db_action_table = db_elm.find(".database_action_table");
								select = db_action_table.find(" > .table > select");
								select.val(action_value["table"]);
								updateDBActionTableAttributes(select[0]);
								
								if (action_value["attributes"]) {
									var ul = db_action_table.find(" > .attributes > ul");
									ul.children(".attr_activated").removeClass("attr_activated").children(".attr_active").removeAttr("checked").prop("checked", false);
									
									$.each(action_value["attributes"], function (idx, attribute) {
										var attr_name = attribute["column"];
										var attr_value = attribute["value"];
										var attr_alias = attribute["name"];
										
										var li = ul.children("li[data_attr_name='" + attr_name + "']");
										li.addClass("attr_activated");
										li.children(".attr_active").attr("checked", "checked").prop("checked", true);
										li.children(".attr_value").val(attr_value);
										li.children(".attr_name").val(attr_alias);
									});
								}
								
								if (action_value["conditions"]) {
									var ul = db_action_table.find(" > .conditions > ul");
									ul.children(".attr_activated").removeClass("attr_activated").children(".attr_active").removeAttr("checked").prop("checked", false);
									
									$.each(action_value["conditions"], function (idx, condition) {
										var attr_name = condition["column"];
										var attr_value = condition["value"];
										
										var li = ul.children("li[data_attr_name='" + attr_name + "']");
										li.addClass("attr_activated");
										li.children(".attr_active").attr("checked", "checked").prop("checked", true);
										li.children(".attr_value").val(attr_value);
									});
								}
								
								//load sql and settings tabs
								var sql = convertActionValueToSQL(action_type, action_value);
								
								if (sql) {
									var rel_query_elm = db_elm.find(".relationship .query").first();
									var query_tabs = rel_query_elm.children(".query_tabs");
									var query_sql_elm = rel_query_elm.find(" > .sql_text_area");
									var editor = getQuerySqlEditor(query_sql_elm);
									
									if (editor)
										editor.setValue(sql);
									else
										query_sql_elm.children("textarea").val(sql);
									
									//prepare sql ui
									query_tabs.find(" > .query_design_tab a").attr("do_not_confirm", 1).trigger("click").removeAttr("do_not_confirm");
									
									//show tables ui
									query_tabs.find(" > .query_table_tab a").trigger("click");
									
									//close settings ui popup automatically
									setTimeout(function() {
										eval('var WF = taskFlowChartObj_' + rel_query_elm.attr("rand_number") + ';');
										WF.getMyFancyPopupObj().hidePopup();
										
										//just in case
										rel_query_elm.find(".popup_overlay, .choose_table_or_attribute").hide();
									}, 10000);
								}
							}
							else {
								var sql_elm = db_elm.find(".sql_text_area");
								var editor = sql_elm.data("editor");
								var sql = prepareFieldValueIfValueTypeIsString(action_value["sql"]); //remove enclosed quotes if exists
								
								if (editor)
									editor.setValue(sql);
								else
									sql_elm.children("textarea").val(sql);
								
								db_elm.find(".query > .query_tabs > .query_sql_tab > a").attr("not_create_sql_from_ui", 1).click().removeAttr("not_create_sql_from_ui");
							}
						}
						
						//load footer fields
						db_elm.find(" > footer .opts .options_type").val( action_value["options_type"] );
						
						LayerOptionsUtilObj.onLoadTaskProperties(db_elm.children("footer"), action_value); //init options
					}
					else 
						initSLAGroupItemUndefinedTask(group_item, action_type, action_value);
				});	
				break;
			
			case "draw_graph":
				var draw_graph_elm = group_item.find(' > .sla_group_body > .draw_graph_action_body');
				
				if ($.isPlainObject(action_value) && action_value.hasOwnProperty("code")) {
					draw_graph_elm.tabs("option", "active", 1);
					initDrawGraphCode( draw_graph_elm.children(".draw_graph_js_code") );
				}
		}
	}
}

function convertActionValueToSQL(action_type, action_value) {
	var sql = "";
	
	var conditions = "";
	if (action_value["conditions"])
		$.each(action_value["conditions"], function (idx, condition) {
			if (condition["column"])
				conditions += (conditions ? " and " : "") + "`" + condition["column"] + "`='" + condition["value"].replace(/'/g, "") + "'";
		});
	conditions = conditions ? " where " + conditions : "";
	
	switch (action_type) {
		case "insert":
			var attributes_name = "";
			var attributes_value = "";
			
			if (action_value["attributes"])
				$.each(action_value["attributes"], function (idx, attribute) {
					if (attribute["column"]) {
						attributes_name += (attributes_name ? ", " : "") + "`" + attribute["column"] + "`";
						attributes_value += (attributes_value ? ", " : "") + "'" + ("" + attribute["value"]).replace(/'/g, "") + "'";
					}
				});
			
			if (attributes_name)
				sql += "insert into " + action_value["table"] + "(" + attributes_name + ") values (" + attributes_value + ");";
			break;
		case "update":
			var attributes = "";
			
			if (action_value["attributes"])
				$.each(action_value["attributes"], function (idx, attribute) {
					if (attribute["column"])
						attributes += (attributes ? ", " : "") + "`" + attribute["column"] + "`='" + attribute["value"].replace(/'/g, "") + "'";
				});
			
			if (attributes)
				sql += "update " + action_value["table"] + " set " + attributes + conditions;
			break;
		case "delete":
			sql += "delete from " + action_value["table"] + conditions;
			break;
		case "select":
			var attributes = "";
			
			if (action_value["attributes"])
				$.each(action_value["attributes"], function (idx, attribute) {
					if (attribute["column"])
						attributes += (attributes ? ", " : "") + "`" + attribute["column"] + "`";
				});
			
			if (attributes)
				sql += "select " + attributes + " from " + action_value["table"] + conditions;
			break;
		case "count":
			sql += "select count(*) from " + action_value["table"] + conditions;
			break;
	}
	
	return sql;
}

function convertSettingsToTasksValues(settings_values, lower_case) {
	var tasks_values = {};
	
	if (!$.isEmptyObject(settings_values) && settings_values["actions"]) {
		tasks_values = convertBlockSettingsValuesIntoBasicArray(settings_values);
		
		var actions = tasks_values["actions"];
		tasks_values["actions"] = [];
		
		if (actions) {
			if (actions.hasOwnProperty("key") || actions.hasOwnProperty("value") || actions.hasOwnProperty("items"))
				actions = [actions];
			
			$.each(actions, function (i, action) {
				var action_type = action["action_type"];
				var item_settings = settings_values["actions"]["items"][i];
				item_settings = item_settings && item_settings.hasOwnProperty("items") ? item_settings["items"] : item_settings;
				var action_value = item_settings["action_value"];
				var condition_value = item_settings["condition_value"];
				
				if (action["condition_type"] == "execute_if_code" || action["condition_type"] == "execute_if_not_code")
					action["condition_value"] = condition_value["value_type"] == "string" ? '"' + condition_value["value"].replace(/"/g, '\\"') + '"' : condition_value["value"];
				//else if (condition_value["value_type"] == "string") 
				else if ($.type(action["condition_value"]) == "string")
					action["condition_value"] = prepareFieldValueIfValueTypeIsString(action["condition_value"]);
				
				if (!action_value)
					action["action_value"] = "";
				else if (action_value["value"] && get_input_data_method_settings_url && (action_value["value_type"] == "method" || action_value["value_type"] == "function")) {
					$.ajax({
						type : "post",
						url : get_input_data_method_settings_url,
						data : {"method" : action_value["value"]},
						dataType : "json",
						success : function(data, textStatus, jqXHR) {
							if (data && data["brokers_layer_type"] && data["brokers"] && $.isPlainObject(data["brokers"])) {
								action["action_value"] = data["brokers"];
								action["action_type"] = data["brokers_layer_type"];
							}
							else
								StatusMessageHandler.showError("Error trying to load new settings.\nPlease try again...");
						},
						error : function() { 
							StatusMessageHandler.showError("Error trying to load new settings.\nPlease try again...");
						},
						async: false,
					});
				}
				else if (action_type == "html") {
					var av = {};
					
					if (action_value["value"]) {
						av["form_settings_data_type"] = action_value["value_type"];
						av["form_settings_data"] = action_value["value"];
					}
					else {
						av["form_settings_data_type"] = "array";
						av["form_settings_data"] = convertObjectIntoArray(action_value["items"]);
					}
					
					action["action_value"] = av;
				}
				else if ((action_type == "array" || action_type == "code") && action_value && action_value.hasOwnProperty("items") && action_value["items"]) { //fix some wrong hard coded values
					action["action_value"] = convertObjectIntoArray(action_value["items"]);
					action["action_type"] = "array";
				}
				else if ((action_type == "string" || action_type == "variable") && action_value && action_value.hasOwnProperty("items") && action_value["items"]) {
					var basic_action_value = convertBlockSettingsValuesIntoBasicArray(action_value);
					action["action_value"] = basic_action_value["items"];
				}
				//Is already done bellow
				//else if (action_type == "code" || action_type == "variable" || action_type == "string")
				//	action["action_value"] = $.type(action_value["value"]) == "string" ? prepareFieldValueIfValueTypeIsString(action_value["value"]) : action_value["value"];
				else if ((action_type == "loop" || action_type == "group") && action_value && action_value.hasOwnProperty("items") && action_value["items"]) {
					var sub_task_values = convertSettingsToTasksValues(action_value["items"], lower_case);
					action["action_value"]["actions"] = sub_task_values["actions"];
				}
				else if (action_type == "callbusinesslogic" || action_type == "callibatisquery" || action_type == "callhibernatemethod" || action_type == "getquerydata" || action_type == "setquerydata" || action_type == "callfunction" || action_type == "callobjectmethod" || action_type == "restconnector") {
					//Preparing new broker settings
					if (action_value["items"] && $.isPlainObject(action_value["items"]))
						for (var k in action_value["items"]) {
							var v = action_value["items"][k];
							
							if (v && $.isPlainObject(v)) {
								if (v.hasOwnProperty("items")) {
									var v_items = convertObjectIntoArray(v["items"]);
									
									//pass the value_type inside of the array to type, but only if is callfunction or callobjectmethod
									if ((action_type == "callfunction" && k == "func_args") || (action_type == "callobjectmethod" && k == "method_args"))
										$.each(v_items, function(idx, v_item) {
											v_item["type"] = v_item["value_type"];
											delete v_item["value_type"];
											v_items[idx] = v_item;
										});
									
									action["action_value"][k] = v_items;
									action["action_value"][k + "_type"] = "array";
								}
								else if (v.hasOwnProperty("value")) {
									action["action_value"][k + "_type"] = v["value_type"]; //adding the _type attribute for all the other values
									
									if (v["value_type"] == "" && v["value"] == "null")
										action["action_value"][k] = "";
								}
							}
						}
						
					//console.log(action_value);
					//console.log(action["action_value"]);
				}
				else if (action_type == "insert" || action_type == "update" || action_type == "delete" || action_type == "select" || action_type == "count" || action_type == "procedure" || action_type == "getinsertedid") {
					if (action_value["items"] && $.isPlainObject(action_value["items"]) && action_value["items"]["options"]) {
						var v = action_value["items"]["options"];
						
						if (v && $.isPlainObject(v)) {
							if (v.hasOwnProperty("items")) {
								action["action_value"]["options"] = convertObjectIntoArray(v["items"]);
								action["action_value"]["options_type"] = "array";
							}
							else {
								action["action_value"]["options"] = v["value"];
								action["action_value"]["options_type"] = v["value_type"];
								
								if (v["value_type"] == "string")
									action["action_value"]["options"] = prepareFieldValueIfValueTypeIsString(action["action_value"]["options"]); //remove extra quotes that were added by the convertBlockSettingsValuesIntoBasicArray function
							}
						}
						else
							action["action_value"]["options_type"] = "";
					}
				}
				else if (action_type == "soapconnector") {
					if (action_value["items"] && $.isPlainObject(action_value["items"]))
						for (var k in action_value["items"]) { //check first level for: data and result_type
							var v = action_value["items"][k];
							
							if (v && $.isPlainObject(v)) {
								if (v.hasOwnProperty("items")) { //for data
									var new_v = {};
									
									if (v["items"] && $.isPlainObject(v["items"]))
										for (var sub_k in v["items"]) { //check second level for: data[headers], data[options], data[type], data[wsdl_url], etc...
											var sub_v = v["items"][sub_k];
											
											if (sub_v && $.isPlainObject(sub_v)) {
												if (sub_v.hasOwnProperty("items")) { //for data[headers], data[options], etc...
													switch(sub_k) {
														case "headers": //for data[headers]
															var headers = [];
															
															if (sub_v["items"] && ($.isPlainObject(sub_v["items"]) || $.isArray(sub_v["items"])))
																for (var header_idx in sub_v["items"]) {
																	var header = sub_v["items"][header_idx];
																	var new_header = {};
																	
																	if (header && $.isPlainObject(header)) //for data[headers][idx][namespace], data[headers][idx][name], data[headers][idx][actor], data[headers][idx][parameters], etc...
																		for (var header_k in header) {
																			var header_v = header[header_k];
																			
																			if (header_v && $.isPlainObject(header_v)) {
																				if (header_v.hasOwnProperty("items")) { //for data[headers][idx][parameters]
																					new_header[header_k] = convertObjectIntoArray(header_v["items"]);
																					new_header[header_k + "_type"] = "array";
																				}
																				else if (header_v.hasOwnProperty("value")) { //for data[headers][idx][namespace], data[headers][idx][name], data[headers][idx][actor], etc...
																					new_header[header_k] = header_v["value"];
																					new_header[header_k + "_type"] = header_v["value_type"];
																					
																					if (header_v["value_type"] == "" && header_v["value"] == "null")
																						new_header[header_k] = "";
																				}
																			}
																		}
																		
																	headers.push(new_header);
																}
															
															new_v[sub_k] = headers;
															new_v[sub_k + "_type"] = "options";
															break;
														
														case "options": //for data[options]
															var options = [];
															
															if (sub_v["items"] && ($.isPlainObject(sub_v["items"]) || $.isArray(sub_v["items"])))
																for (var opt_idx in sub_v["items"]) {
																	var opt = sub_v["items"][opt_idx];
																	
																	options.push({
																		name: opt["key"],
																		value: opt["items"] ? opt["items"] : opt["value"],
																		var_type: opt["items"] ? "array" : opt["value_type"],
																	});
																}
															
															new_v[sub_k] = options;
															new_v[sub_k + "_type"] = "options";
															break;
														
														case "remote_function_args": //for data[remote_function_args]
															new_v[sub_k] = convertObjectIntoArray(sub_v["items"]);
															new_v[sub_k + "_type"] = "array";
															break;
													}
												}
												else if (sub_v.hasOwnProperty("value")) { //for data[type], data[wsdl_url], etc...
													new_v[sub_k] = sub_v["value"];
													new_v[sub_k + "_type"] = sub_v["value_type"]; //adding the _type attribute for all the other values
													
													if (sub_v["value_type"] == "" && sub_v["value"] == "null")
														new_v[sub_k] = "";
												}
											}
										}
										
									action["action_value"][k] = new_v;
									action["action_value"][k + "_type"] = "options";
								}
								else if (v.hasOwnProperty("value")) { //for result_type
									action["action_value"][k + "_type"] = v["value_type"]; //adding the _type attribute for all the other values
									
									if (v["value_type"] == "" && v["value"] == "null")
										action["action_value"][k] = "";
								}
							}
						}
					//console.log(action_value);
				}
				else if ($.type(action["action_value"]) == "string") //remove extra quotes that were added by the convertBlockSettingsValuesIntoBasicArray function. This will include the parse for the values with "code", "variable" and "string"
					action["action_value"] = prepareFieldValueIfValueTypeIsString(action["action_value"]); 
				else if ($.isPlainObject(action["action_value"])) { //for the messages and database/sql cases and also the string and variable cases (in case they have the operator attribute).
				
					for (var k in action["action_value"]) {
						var v = action["action_value"][k];
						
						if ($.type(v) == "string")
							action["action_value"][k] = prepareFieldValueIfValueTypeIsString(v); //remove extra quotes that were added by the convertBlockSettingsValuesIntoBasicArray function
					}
				}
				
				if (action_type == "")
					action["action_type"] = "code";
				
				tasks_values["actions"].push(action);
			});
		}
		
		if (lower_case)
			tasks_values = convertBlockSettingsValuesKeysToLowerCase(tasks_values);
	}
	
	//console.log(tasks_values);
	return tasks_values;
}

function initSLAGroupItemTasks(group_item, values) {
	if (!group_item[0].hasAttribute("inited")) {
		group_item[0].setAttribute("inited", 1);
		var exists = false;
		
		if (js_load_functions) {
			for (var tag in js_load_functions) {
				var func = js_load_functions[tag];
				
				var s = values && values.hasOwnProperty(tag) && values[tag] ? values[tag] : {};
				var m = null;
				
				switch(tag) {
					case "callbusinesslogic": 
						m = group_item.find(" > .sla_group_body > .broker_action_body > .call_business_logic_task_html");
						break;
					case "callibatisquery": 
						m = group_item.find(" > .sla_group_body > .broker_action_body > .call_ibatis_query_task_html");
						break;
					case "callhibernatemethod": 
						m = group_item.find(" > .sla_group_body > .broker_action_body > .call_hibernate_method_task_html");
						break;
					case "getquerydata": 
						m = group_item.find(" > .sla_group_body > .broker_action_body > .get_query_data_task_html");
						break;
					case "setquerydata": 
						m = group_item.find(" > .sla_group_body > .broker_action_body > .set_query_data_task_html");
						break;
					case "callfunction": 
						m = group_item.find(" > .sla_group_body > .broker_action_body > .call_function_task_html");
						break;
					case "callobjectmethod": 
						m = group_item.find(" > .sla_group_body > .broker_action_body > .call_object_method_task_html");
						break;
					case "restconnector": 
						m = group_item.find(" > .sla_group_body > .broker_action_body > .get_url_contents_task_html");
						break;
					case "soapconnector": 
						m = group_item.find(" > .sla_group_body > .broker_action_body > .soap_connector_task_html");
						break;
					case "createform": 
						m = group_item.find(" > .sla_group_body > .html_action_body > .create_form_task_html");
						break;
				}

				if (m && m[0]) {
					if (!exists)
						exists = $.isEmptyObject(values) || values.hasOwnProperty(tag);
					
					//prepare properties
					taskFlowChartObj.Property.setPropertiesFromHtmlElm(m, "task_property_field", s);
					
					//console.log(func);
					//console.log(m);
					//console.log(s);
					eval (func + '(m.parent(), null, s);');
				}
			}
		}
		
		//if a broker does not exists anymore (bc the layer stop be related with another layer), then we must continue saving the value, in case the user relates again the layer with that layer.
		if (!exists && values) {
			//get first key which is the tag
			for (var tag in values)
				break;
			
			var s = values.hasOwnProperty(tag) && values[tag] ? values[tag] : {};
			initSLAGroupItemUndefinedTask(group_item, tag, s);
		}
	}
}

function initSLAGroupItemUndefinedTask(group_item, tag_name, tag_values) {
	group_item.find(" > .sla_group_body > .undefined_action_value").val( JSON.stringify(tag_values) );
	
	var select = group_item.find(" > .sla_group_header > select.action_type");
	var option = select.find("option[value=" + tag_name + "]");
	
	if (option.length == 0) {
		select.append('<option value="' + tag_name + '" undefined="1">Undefined - ' + tag_name + '</option>');
		select.val(tag_name);
		onChangeSLAInputType( select[0] );
	}
	
	group_item.addClass("sla_group_item_undefined");
}

/* UI FUNCTIONS */

function addNewSLAGroup(elm) {
	var groups = $(elm).parent().closest(".sla_groups_flow").children(".sla_groups");
	var new_group = groups.children(".sla_group_item.sla_group_default").clone().removeClass("sla_group_default");
	groups.append(new_group);
	new_group.show();
	
	groups.children(".sla_group_empty_items").hide();
	
	return new_group;
}

function addNewSLASubGroup(elm) {
	var groups = $(elm).parent().closest(".sla_groups_flow").children(".sla_groups");
	var new_group = groups.children(".sla_group_item.sla_group_default").clone().removeClass("sla_group_default");
	
	var sub_groups = $(elm).parent().closest("section").children(".sla_sub_groups");
	sub_groups.append(new_group);
	new_group.show();
	
	sub_groups.children(".sla_group_empty_items").hide();
	
	return new_group;
}

function collapseSLAGroups(elm) {
	$(elm).parent().closest(".sla_groups_flow").find(".sla_group_item:not(.sla_group_default) > .sla_group_header > .toggle").each(function(idx, item) {
		item = $(item);

		if (item.parent().closest(".sla_group_item").children(".sla_group_body").css("display") != "none")
			item.trigger("click");
	});
}

function expandSLAGroups(elm) {
	$(elm).parent().closest(".sla_groups_flow").find(".sla_group_item:not(.sla_group_default) > .sla_group_header > .toggle").each(function(idx, item) {
		item = $(item);

		if (item.parent().closest(".sla_group_item").children(".sla_group_body").css("display") == "none")
			item.trigger("click");
	});
}

function addAndInitNewSLAGroup(elm) {
	var new_group = addNewSLAGroup(elm);
	onChangeSLAInputType( new_group.find(" > .sla_group_header .action_type")[0] );
	return new_group;
}

function addAndInitNewSLASubGroup(elm) {
	var new_group = addNewSLASubGroup(elm);
	onChangeSLAInputType( new_group.find(" > .sla_group_header .action_type")[0] );
	return new_group;
}

function onChangeSLAInputType(elm) {
	elm = $(elm);
	var group_item = elm.parent().closest(".sla_group_item");
	var group_body = group_item.children(".sla_group_body");
	var selection = elm.val();
	var is_undefined = elm.find("option:selected").attr("undefined");
	
	if (is_undefined)
		group_item.addClass("sla_group_item_undefined");
	else
		group_item.removeClass("sla_group_item_undefined");
	
	var sections = group_body.children();
	sections.hide();
	
	if (group_body.css("display") == "none")
		toggleGroupBody( group_item.find(" > .sla_group_header > .toggle")[0] );
	
	var section = null;
	
	switch (selection) {
		case "html":
			section = sections.filter(".html_action_body");
			section.show();
			initSLAGroupItemTasks(group_item, {});
			break;
			
		case "insert":
		case "update":
		case "delete":
		case "select":
		case "count":
		case "procedure":
		case "getinsertedid":
			section = sections.filter(".database_action_body");
			section.attr("class", "database_action_body database_action_body_" + selection).show();
			
			var db_action_body = section.children("article");
			var rel_type_select = db_action_body.find(".rel_type > select");
			
			//even if selection is getinsertedid, execute code below, so it can initalize the dal and db broker fields.
			if (!rel_type_select[0]) {
				//preparing taskworkflow
				var rand = Math.floor(Math.random() * 1000);
				
				var html = $( query_task_html.replace(/#rand#/g, rand) );
				var query = html.find(".query");
				db_action_body.append(html);
				
				var table_html = '<div id="query_obj_tabs_#rand#_3" class="database_action_table">'
					+ '<div class="table"><label>Table: </label><select class="task_property_field" name="form[0][table]" onChange="updateDBActionObjTableAttributes(this)"></select></div>'
					+ '<div class="attributes"><label>Attributes: </label><ul></ul></div>'
					+ '<div class="conditions"><label>Conditions: </label><ul></ul></div>'
				+ '</div>';
				table_html = $( table_html.replace(/#rand#/g, rand) );
				query.append(table_html);
				
				var query_tabs = query.children(".query_tabs");
				query_tabs.prepend( '<li class="query_table_tab"><a href="#query_obj_tabs_#rand#_3">Table</a></li>'.replace(/#rand#/g, rand) );
				
				var query_design_tab = query_tabs.find(" > .query_design_tab > a");
				var on_click = query_design_tab.attr("onClick").replace("initQueryDesign", "initDatabaseActionBodyQueryDesign");
				query_design_tab.attr("onClick", on_click);
				
				var query_sql_tab = query_tabs.find(" > .query_sql_tab > a");
				var on_click = query_sql_tab.attr("onClick").replace("initQuerySql", "initDatabaseActionBodyQuerySql");
				query_sql_tab.attr("onClick", on_click);
				
				//settings tabs
				query.tabs();
				html.find(".query_settings").tabs();
				html.find(".query_insert_update_delete").tabs();
				
				$(function () { //must be after everything loads, otherwise the JsPlumb gives error. This error happens when we have saved settings and we are loading them before the page loads. In this case the JsPlumb gives error. So we can only run the the code bellow after the page loads.
					addTaskFlowChart(rand, true);
					updateQueryUITableFromQuerySettings(rand);
					
					html.children(".header_buttons, .rel_name, .parameter_class_id, .parameter_map_id, .result_class_id, .result_map_id").hide();
					
					var rel_type = html.children(".rel_type");
					rel_type.hide();
					rel_type_select = rel_type.children("select");
					rel_type_select.val(selection == "count" ? "select" : selection);
					updateRelationshipType(rel_type_select[0], rand);
					
					html.find(".myfancypopup.choose_table_or_attribute > .contents > .db_broker > select").change(function () {
						onChangePopupDBBrokers(this);
					});
					
					html.find(".myfancypopup.choose_table_or_attribute > .contents > .db_driver > select").change(function () {
						onChangePopupDBDrivers(this);
					});
					
					html.find(".myfancypopup.choose_table_or_attribute > .contents > .type > select").change(function () {
						onChangePopupDBTypes(this);
					});
					
					//preparing db brokers, drivers and tables
					var options = '<option></option>';
					for (var broker in db_brokers_drivers_tables_attributes)
						options += "<option" + (default_dal_broker == broker ? " selected" : "") + ">" + broker + "</option>";
					
					var select = section.find(" > header > .dal_broker > select");
					select.html(options);
					updateDALActionBroker(select[0]);
				});
			}
			else {
				var rand = rel_type_select.parent().closest(".relationship").children(".query").attr("rand_number");
				rel_type_select.val(selection == "count" ? "select" : selection);
				updateRelationshipType(rel_type_select[0], rand);
			}
			
			//if procedure table tabshould be hidden, so we need to change the active tab automatically. The rest is done through css.
			if (selection == "procedure" && db_action_body.find(".query").tabs("option", "active") == 0)
				db_action_body.find(".query").tabs("option", "active", 2);
			
			//add add_variable icon to inputs
			var rel_query_elm = db_action_body.find(" > .relationship > .query").first();
			
			if (rel_query_elm.attr("icon_add_variable_added") != 1) {
				rel_query_elm.attr("icon_add_variable_added", 1);
				
				var query_obj_tabs_id = rel_query_elm.find(" > .query_tabs > .query_design_tab > a").attr("href");
				var query_obj_tabs = rel_query_elm.children(query_obj_tabs_id);
				addSLAVariableIconToInputs( query_obj_tabs.find(" > .query_insert_update_delete > .query_table") );
				addSLAVariableIconToInputs( query_obj_tabs.find(".limit_start") );
				
				query_obj_tabs.find(".attributes, .keys, .conditions, .groups_by, .sorts").find(" > table > thead .icon.add").each(function(idx, add_icon) {
					add_icon = $(add_icon);
					add_icon.attr("onClick", "addSLAVariableIconToInputs(" + add_icon.attr("onClick") + ")");
				});
			}
			break;
			
		case "callbusinesslogic":
		case "callibatisquery":
		case "callhibernatemethod":
		case "getquerydata":
		case "setquerydata":
		case "callfunction":
		case "callobjectmethod":
		case "restconnector":
		case "soapconnector":
			section = sections.filter(".broker_action_body");
			section.show();
			
			initSLAGroupItemTasks(group_item, {});
			onChangeBrokersLayerType(selection, section);
			
			break;
			
		case "show_ok_msg":
		case "show_ok_msg_and_stop":
		case "show_ok_msg_and_die":
		case "show_ok_msg_and_redirect":
		case "show_error_msg":
		case "show_error_msg_and_stop":
		case "show_error_msg_and_die":
		case "show_error_msg_and_redirect":
		case "alert_msg":
		case "alert_msg_and_stop":
		case "alert_msg_and_redirect":
			onMessageChange(elm[0]);
			section = sections.filter(".message_action_body");
			section.show();
			break;
			
		case "redirect":
			section = sections.filter(".redirect_action_body");
			section.show();
			break;
		
		case "refresh":
			group_body.hide();
			break;
		
		case "return_previous_record":
		case "return_next_record":
		case "return_specific_record":
			section = sections.filter(".records_action_body");
			section.show();
			break;
			
		case "check_logged_user_permissions":
			section = sections.filter(".check_logged_user_permissions_action_body");
			section.show();
			break;
			
		case "code":
			section = sections.filter(".code_action_body");
			section.show();
			
			var editor = section.data("editor");
			
			if (!editor)
				createSLAItemCodeEditor( section.children("textarea")[0], "php", true);
			break;
			
		case "array":
			section = sections.filter(".array_action_body");
			section.show();
			
			if (!section.find(".items")[0]) {
				var items = {0: {key_type: "null", value_type: "string"}};
				ArrayTaskUtilObj.onLoadArrayItems(section, items, "");
			}
			break;
			
		case "string":
			section = sections.filter(".string_action_body");
			section.show();
			break;
			
		case "variable":
			section = sections.filter(".variable_action_body");
			section.show();
			break;
			
		case "sanitize_variable":
			section = sections.filter(".sanitize_variable_action_body");
			section.show();
			break;
			
		case "validate_variable":
			section = sections.filter(".validate_variable_action_body");
			section.show();
			break;
			
		case "list_report":
			section = sections.filter(".list_report_action_body");
			section.show();
			break;
			
		case "call_block":
			section = sections.filter(".call_block_action_body");
			section.show();
			break;
			
		case "call_view":
			section = sections.filter(".call_view_action_body");
			section.show();
			break;
			
		case "include_file":
			section = sections.filter(".include_file_action_body");
			section.show();
			break;
			
		case "draw_graph":
			section = sections.filter(".draw_graph_action_body");
			section.show();
			section.tabs();
			break;
		
		case "loop":
			section = sections.filter(".loop_action_body");
			section.show();
			break;
			
		case "group":
			section = sections.filter(".group_action_body");
			section.show();
			break;
	}
	
	addProgrammingTaskUtilInputsContextMenu(section)
}

function onChangeBrokersLayerType(type, parent) {
	switch(type) {
		case "callbusinesslogic":
			parent.children(".call_business_logic_task_html").show();
			parent.children(":not(.call_business_logic_task_html)").hide();
			break;
		case "callibatisquery":
			parent.children(".call_ibatis_query_task_html").show();
			parent.children(":not(.call_ibatis_query_task_html)").hide();
			break;
		case "callhibernatemethod":
			parent.children(".call_hibernate_method_task_html").show();
			parent.children(":not(.call_hibernate_method_task_html)").hide();
			break;
		case "getquerydata":
			parent.children(".get_query_data_task_html").show();
			parent.children(":not(.get_query_data_task_html)").hide();
			break;
		case "setquerydata":
			parent.children(".set_query_data_task_html").show();
			parent.children(":not(.set_query_data_task_html)").hide();
			break;
		case "callfunction":
			parent.children(".call_function_task_html").show();
			parent.children(":not(.call_function_task_html)").hide();
			break;
		case "callobjectmethod":
			parent.children(".call_object_method_task_html").show();
			parent.children(":not(.call_object_method_task_html)").hide();
			break;
		case "restconnector":
			parent.children(".get_url_contents_task_html").show();
			parent.children(":not(.get_url_contents_task_html)").hide();
			break;
		case "soapconnector":
			parent.children(".soap_connector_task_html").show();
			parent.children(":not(.soap_connector_task_html)").hide();
			break;
	}
}

function initDatabaseActionBodyQueryDesign(elm, rand_number) {
	elm = $(elm);
	var query_settings_elm = $( elm.attr("href") ).find(".query_settings");
	var prev_html = query_settings_elm.html();
	
	initQueryDesign(elm, rand_number);
	
	//update action type based on sql
	var count = 5;
	var interval = setInterval(function() {
		var next_html = query_settings_elm.html();
		count--;
		
		if (next_html != prev_html) {
			clearInterval(interval);
			
			var rel_type = elm.parent().closest(".relationship").find(" > .rel_type > select").val();
			var sla_group_item = elm.parent().closest(".sla_group_item");
			
			//check if rel_type var should be "count" instead of "select", based in the sql
			if (rel_type == "select") {
				var rel = getUserRelationshipObj( query_settings_elm.parent().closest(".relationship") );
				var sql = rel[1] && rel[1]["value"] ? rel[1]["value"] : "";
				
				if (sql != "" && sla_group_item.find(" > .sla_group_body > .database_action_body").hasClass("database_action_body_count") && sql.replace(/^\s+/g, "").match(/^select\scount\(\*\)\sfrom\s/i))
					rel_type = "count";
			}
			
			var action_type_select = sla_group_item.find(" > .sla_group_header select.action_type");
			action_type_select.val(rel_type);
			onChangeSLAInputType( action_type_select[0] );
		}
		else if (count <= 0)
			clearInterval(interval);
	}, 700);
}

function initDatabaseActionBodyQuerySql(elm, rand_number) {
	initQuerySql(elm, rand_number);
	
	if (!elm.hasAttribute("data_editor_with_save_func")) {
		elm = $(elm);
		elm.attr("data_editor_with_save_func", 1);
		var selector = elm.attr("href");
		var editor = $(selector).data("editor");
		
		if (editor)
			editor.commands.addCommand({
				name: 'saveFile',
				bindKey: {
					win: 'Ctrl-S',
					mac: 'Command-S',
					sender: 'editor|cli'
				},
				exec: function(env, args, request) {
					$(".top_bar .save a").first().trigger("click");
				},
			});
	}
}

function addSLAVariableIconToInputs(added_elm) {
	added_elm.find("input[type=text]").each(function(idx, input) {
		$(input).after('<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>');
	});
}

function onMessageChange(elm) {
	elm = $(elm);
	var selection = elm.val();
	
	var redirect_url = elm.parent().closest(".sla_group_item").find(" > .sla_group_body > .message_action_body > .redirect_url");
	redirect_url.hide();
	
	switch (selection) {
		case "show_ok_msg_and_redirect":
		case "show_error_msg_and_redirect":
		case "alert_msg_and_redirect":
			redirect_url.show();
			break;
	}
}

/* CHECK LOGGED USER PERMISSIONS ACTION POPUPS FUNCTIONS */
function addUserPermission(elm) {
	var tr = '<tr>'
		+ '		<td class="user_type_id">'
		+ '			<select>';
		
	if (available_user_types)
		$.each(available_user_types, function(user_type_id, user_type_name) {
			tr += '		<option value="' + user_type_id + '">' + user_type_name + '</option>';
		});
		
	tr += '			</select>'
		+ '		</td>'
		+ '		<td class="activity_id">'
		+ '			<select>';
		
	if (available_activities)
		$.each(available_activities, function(activity_id, activity_name) {
			tr += '		<option value="' + activity_id + '">' + activity_name + '</option>';
		});
		
	tr += '			</select>'
		+ '		</td>'
		+ '		<td class="actions">'
		+ '			<i class="icon remove" onClick="removeUserPermission(this)"></i>'
		+ '		</td>'
		+ '	</tr>';
	
	tr = $(tr);
	
	var tbody = $(elm).parent().closest("table").children("tbody");
	tbody.children(".no_users").hide();
	tbody.append(tr);
	
	addProgrammingTaskUtilInputsContextMenu(tr);
	
	return tr;
}

function removeUserPermission(elm) {
	var tr = $(elm).parent().closest('tr');
	var tbody = tr.parent().closest("tbody");
	tr.remove();
	
	if (tbody.children().length == 1)
		tbody.children(".no_users").show();
}

/* DATABASE ACTION POPUPS FUNCTIONS */

function onChangePopupDBBrokers(elm) {
	elm = $(elm);
	var p = elm.parent().closest(".choose_table_or_attribute");
	
	if (p.is("#choose_db_table_or_attribute"))
		p = $(MyFancyPopup.settings.targetField).parent().closest(".database_action_body");
	else
		p = elm.parent().closest(".database_action_body");
	
	var db_driver_select = p.find(" > header > .db_driver > select");
	var old_db_driver = db_driver_select.val();
	
	//update db broker
	var select = p.find(" > header > .dal_broker > select");
	select.val( elm.val() );
	updateDALActionBroker(select[0], true);
	
	//sync db driver too
	var new_db_driver = elm.parent().parent().find(".db_driver > select").val();
	
	if (new_db_driver != old_db_driver || new_db_driver != db_driver_select.val()) {
		db_driver_select.val(new_db_driver);
		updateDBActionDriver(db_driver_select[0], true);
	}
}
	
function onChangePopupDBDrivers(elm) {
	elm = $(elm);
	
	var p = elm.parent().closest(".choose_table_or_attribute");
	
	if (p.is("#choose_db_table_or_attribute"))
		p = $(MyFancyPopup.settings.targetField).parent().closest(".database_action_body");
	else
		p = elm.parent().closest(".database_action_body");
	
	//update db driver
	var select = p.find(" > header > .db_driver > select");
	select.val( elm.val() );
	updateDBActionDriver(select[0], true);
}
	
function onChangePopupDBTypes(elm) {
	elm = $(elm);
	
	var p = elm.parent().closest(".choose_table_or_attribute");
	
	if (p.is("#choose_db_table_or_attribute"))
		p = $(MyFancyPopup.settings.targetField).parent().closest(".database_action_body");
	else
		p = elm.parent().closest(".database_action_body");
	
	//update db type
	var select = p.find(" > header > .db_type > select");
	select.val( elm.val() );
	updateDBActionType(select[0], true);
}

function updateDALActionBroker(elm, already_synced) {
	elm = $(elm);
	var selected_broker = elm.val();
	var db_body = elm.parent().closest(".database_action_body");
	
	if (selected_broker == "")
		db_body.children("article").hide();
	else 
		db_body.children("article").show();
	
	if (!already_synced) { //already_synced means that the popups are already synced and the updateDBDrivers was already called.
		//update all the other brokers in the popups and trigger onchange event on each of them
		var selects = db_body.find(".myfancypopup > .contents > .db_broker > select");
		selects.push( $("#choose_db_table_or_attribute > .contents > .db_broker > select")[0] );
		$.each(selects, function (idx, select) { //must be synchronous bc the code bellow needs to have the
			$(select).val(selected_broker);
			updateDBDrivers(select, false);
		});
	}
	
	//update db_drivers
	var db_drivers = db_brokers_drivers_tables_attributes ? db_brokers_drivers_tables_attributes[selected_broker] : null;
	var options = '<option value="">-- Default --</option>';
	
	if (db_drivers)
		for (var db_driver in db_drivers) 
			options += "<option>" + db_driver + "</option>";
	
	var select = db_body.find(" > header > .db_driver > select");
	var db_driver = select.val();
	select.html(options);
	select.val(db_driver);
	
	if (db_driver != select.val())
		updateDBActionDriver(select[0], already_synced);
}

function updateDBActionDriver(elm, already_synced) {
	//update tables
	elm = $(elm);
	var selected_driver = elm.val();
	selected_driver = selected_driver ? selected_driver : default_db_driver;
	var db_body = elm.parent().closest(".database_action_body");
	
	if (!already_synced) { //already_synced means that the popups are already synced and the updateDBTables was already called.
		//update all the other brokers in the popups and trigger onchange event on each of them
		var rand = db_body.find(".relationship > .query").attr("rand_number");
		var selects = db_body.find(".myfancypopup > .contents > .db_driver > select");
		selects.push( $("#choose_db_table_or_attribute > .contents > .db_driver > select")[0] );
		$.each(selects, function (idx, select) { //must be synchronous bc the code bellow needs to have the db_brokers_drivers_tables_attributes[selected_broker][selected_driver] inited!
			$(select).val(selected_driver);
			updateDBTables(select, rand, false);
		});
	}
	
	//update tables
	var selected_broker = db_body.find(" > header > .dal_broker > select").val();
	var selected_type = db_body.find(" > header > .db_type > select").val();
	var tables = db_brokers_drivers_tables_attributes && db_brokers_drivers_tables_attributes[selected_broker] && db_brokers_drivers_tables_attributes[selected_broker][selected_driver] ? db_brokers_drivers_tables_attributes[selected_broker][selected_driver][selected_type] : null;
	
	var options = '<option></option>';
	if (tables)
		for (var table in tables) 
			options += "<option>" + table + "</option>";
	
	var select = db_body.find(".database_action_table > .table > select");
	var prev_table = select.val();
	select.html(options).val(prev_table);
	updateDBActionTableAttributes(select[0], already_synced);
}

function updateDBActionType(elm, already_synced) {
	//update tables
	elm = $(elm);
	var selected_type = elm.val();
	var db_body = elm.parent().closest(".database_action_body");
	
	if (!already_synced) { //already_synced means that the popups are already synced and the updateDBTables was already called.
		//update all the other brokers in the popups and trigger onchange event on each of them
		var rand = db_body.find(".relationship > .query").attr("rand_number");
		var selects = db_body.find(".myfancypopup > .contents > .type > select");
		selects.push( $("#choose_db_table_or_attribute > .contents > .type > select")[0] );
		$.each(selects, function (idx, select) { //must be synchronous bc the code bellow needs to have the db_brokers_drivers_tables_attributes[selected_broker][selected_driver][selected_type] inited!
			$(select).val(selected_type);
			updateDBTables(select, rand, false);
		});
	}
	
	//update tables
	var selected_broker = db_body.find(" > header > .dal_broker > select").val();
	var selected_driver = db_body.find(" > header > .db_driver > select").val();
	selected_driver = selected_driver ? selected_driver : default_db_driver;
	var tables = db_brokers_drivers_tables_attributes && db_brokers_drivers_tables_attributes[selected_broker] && db_brokers_drivers_tables_attributes[selected_broker][selected_driver] ? db_brokers_drivers_tables_attributes[selected_broker][selected_driver][selected_type] : null;
	
	var options = '<option></option>';
	if (tables)
		for (var table in tables) 
			options += "<option>" + table + "</option>";
	
	var select = db_body.find(".database_action_table > .table > select");
	var prev_table = select.val();
	select.html(options).val(prev_table);
	updateDBActionTableAttributes(select[0], already_synced);
}

function updateDBActionTableAttributes(elm, already_synced) {
	//update tables
	elm = $(elm);
	var selected_table = elm.val();
	var db_body = elm.parent().closest(".database_action_body");
	
	if (!already_synced) { //already_synced means that the popups are already synced and the updateDBAttributes was already called.
		//update all the other brokers in the popups and trigger onchange event on each of them
		var rand = db_body.find(".relationship > .query").attr("rand_number");
		var selects = db_body.find(".myfancypopup > .contents > .db_table > select");
		selects.push( $("#choose_db_table_or_attribute > .contents > .db_table > select")[0] );
		$.each(selects, function (idx, select) { //must be synchronous bc the code bellow needs to have the db_brokers_drivers_tables_attributes[selected_broker][selected_driver] inited!
			$(select).val(selected_table);
			updateDBAttributes(select, rand, false);
		});
	}
	
	//update attributes
	var selected_broker = db_body.find(" > header > .dal_broker > select").val();
	var selected_driver = db_body.find(" > header > .db_driver > select").val();
	selected_driver = selected_driver ? selected_driver : default_db_driver;
	var selected_type = db_body.find(" > header > .db_type > select").val();
	var attributes = db_brokers_drivers_tables_attributes && db_brokers_drivers_tables_attributes[selected_broker] && db_brokers_drivers_tables_attributes[selected_broker][selected_driver] && db_brokers_drivers_tables_attributes[selected_broker][selected_driver][selected_type] ? db_brokers_drivers_tables_attributes[selected_broker][selected_driver][selected_type][selected_table] : null;
	
	var html = '';
	if (attributes)
		for (var idx in attributes) 
			html += '<li data_attr_name="' + attributes[idx] + '">'
					+ '<input class="attr_active" type="checkbox" onClick="activateDBActionTableAttribute(this)" />'
					+ '<label>' + attributes[idx] + '</label>'
					+ '<input class="attr_value" type="text" title="Write the value here" placeHolder="Write a value" />'
					+ '<input class="attr_name" type="text" title="Write the alias/label here" placeHolder="Write an alias/label" />'
					+ '<span class="icon add_variable" onClick="onSLADataBaseActionTableProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>'
				+ '</li>';
	
	var db_action_table = db_body.find(".database_action_table");
	var previous_data = {};
	
	//get previous attributes settings
	if (db_action_table.children(".attributes li, .conditions li").length > 0) {
		var lis = db_action_table.children(".attributes, .conditions").find(" > ul > li");
		previous_data[attributes] = {};
		previous_data[conditions] = {};
		
		$.each(lis, function(idx, li) {
			li = $(li);
			var attr_group = li.parent().parent().hasClass("attributes") ? "attributes" : "conditions";
			var attr_name = li.attr("data_attr_name");
			
			previous_data[attr_group][attr_name] = {
				checked: li.children("input.attr_active").is(":checked"),
				value: li.children("input.attr_value").val(),
				name: li.children("input.attr_name").val(),
			};
		});
	}
	
	//load attributes new html
	var attributes_ul = db_action_table.find(" > .attributes > ul");
	var conditions_ul = db_action_table.find(" > .conditions > ul");
	attributes_ul.html(html);
	conditions_ul.html(html);
	
	addProgrammingTaskUtilInputsContextMenu( attributes_ul.children("li") );
	addProgrammingTaskUtilInputsContextMenu( conditions_ul.children("li") );
	
	//set new attributes settings if none previously
	if ($.isEmptyObject(previous_data)) //set new attributes settings
		db_action_table.find(" > .attributes > ul").find("input.attr_active").attr("checked", "checked").prop("checked", true).parent().addClass("attr_activated");
	else { //load previous attributes settings
		for (var attr_group in previous_data) {
			var attr_group_items = previous_data[attr_group];
			var ul = db_action_table.find(" > .attributes > ul");
			
			for (var attr_name in attr_group_items) {
				var attr_values = attr_group_items[attr_name];
				var li = ul.children("li[data_attr_name='" + attr_name + "']");
				
				if (attr_values["checked"])
					li.children("input.attr_active").attr("checked", "checked").prop("checked", true).parent().addClass("attr_activated");
				
				li.children("input.attr_value").val(attr_values["value"]);
				li.children("input.attr_name").val(attr_values["name"]);
			}
		}
	}
	
	//show attributes ui
	if (selected_table)
		db_action_table.children(".attributes, .conditions").show();
	else
		db_action_table.children(".attributes, .conditions").hide();
}

function updateDBActionObjTableAttributes(elm, already_synced) {
	updateDBActionTableAttributes(elm, already_synced);
	
	elm = $(elm);
	var selected_table = elm.val();
	var db_body = elm.parent().closest(".database_action_body");
	
	db_body.find(".query .query_insert_update_delete .query_table input").val(selected_table);
}

function activateDBActionTableAttribute(elm) {
	elm = $(elm);
	
	if (elm.is(":checked"))
		elm.parent().addClass("attr_activated");
	else 
		elm.parent().removeClass("attr_activated");
}

/* DRAW GRAPH FUNCTIONS */

function initDrawGraphCode(elm) {
	var section = $(elm).parent().closest(".draw_graph_action_body");
	var js_code = section.children(".draw_graph_js_code");
	var editor = js_code.data("editor");
	
	if (!editor)
		createSLAItemCodeEditor( js_code.children("textarea")[0], "php", true);
}

function loadDrawGraphSettings(draw_graph_settings_elm, action_value, original_action_value) {
	draw_graph_settings_elm.find('.include_graph_library select').val( action_value["include_graph_library"] );
	draw_graph_settings_elm.find('.graph_width input').val( action_value["width"] );
	draw_graph_settings_elm.find('.graph_height input').val( action_value["height"] );
	draw_graph_settings_elm.find('.labels_variable input').val( action_value["labels_variable"] );
	
	var data_sets = $.isPlainObject(original_action_value) && original_action_value["data_sets"] ? original_action_value["data_sets"] : action_value["data_sets"];
	
	if (data_sets) {
		if ($.isPlainObject(data_sets) && data_sets.hasOwnProperty("values_variable"))
			data_sets = [ data_sets ];
		
		var graph_data_sets = draw_graph_settings_elm.find(".graph_data_sets");
		var ul = graph_data_sets.children("ul");
		var li = ul.children("li:not(.no_data_sets):last-child");
		var static_options = ["type", "item_label", "values_variable", "background_colors", "border_colors", "border_width"];
		var count = 1;
		
		$.each(data_sets, function (idx, data_set) {
			if (ul.children("li:not(.no_data_sets)").length < count)
				li = addDrawGraphSettingsDataSet( graph_data_sets.find(" > label > .add")[0] );
			
			$.each(data_set, function(key, value) {
				if ($.inArray(key, static_options) != -1)
					li.find('.' + key).children('input, select, textarea').val(value);
				else {
					var sub_li = addDrawGraphSettingsDataSetOtherOption( li.find(".other_options > label > .add")[0] );
					sub_li.find(".option_name").val(key);
					sub_li.find(".option_value").val(value);
				}
			});
			
			count++;
		});
	}
}

function addDrawGraphSettingsDataSet(elm) {
	elm = $(elm);
	var html = $( getDrawGraphSettingsDataSetHtml() );
	
	var ul = elm.parent().closest(".graph_data_sets").children("ul");
	
	ul.append(html);
	
	ul.children("li.no_data_sets").hide();
	
	addProgrammingTaskUtilInputsContextMenu( ul.children("li").last() );
	
	return html;
}

function removeDrawGraphSettingsDataSet(elm) {
	if (confirm("Do you wish to remove this data-set?")) {
		var li = $(elm).parent().closest("li");
		var ul = li.parent();
		
		li.remove();
		
		if (ul.children("li:not(.no_data_sets)").length == 0)
			ul.children("li.no_data_sets").show();
	}
}

function getDrawGraphSettingsDataSetHtml() {
	return '<li>'
		 + '	<span class="icon delete" onClick="removeDrawGraphSettingsDataSet(this)" title="Click here to remove this data-set">Remove</span>'
		 + '	<div class="type">'
		 + '		<label>Graph Type: </label>'
		 + '		<select class="task_property_field">'
		 + '			<option value="bar">Bar</option>'
		 + '			<option value="line">Line</option>'
		 + '			<option value="radar">Radar</option>'
		 + '			<option value="pie">Pie</option>'
		 + '			<option value="doughnut">Doughnut</option>'
		 + '			<option value="polarArea">Polar Area</option>'
		 + '			<option value="bubble">Bubble</option>'
		 + '			<option value="scatter">Scatter</option>'
		 + '			<option value="area">Area</option>'
		 + '		</select>'
		 + '	</div>'
		 + '	<div class="item_label">'
		 + '		<label>Item Info Label: </label>'
		 + '		<input class="task_property_field" />'
		+ '		<span class="icon add_variable" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>'
		 + '	</div>'
		 + '	<div class="values_variable">'
		 + '		<label>Data Variable: </label>'
		 + '		<input class="task_property_field" />'
		+ '		<span class="icon add_variable" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>'
		 + '	</div>'
		 + '	<div class="background_colors">'
		 + '		<label>Background Colors: </label>'
		 + '		<input class="task_property_field" />'
		+ '		<span class="icon add_variable" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>'
		 + '	</div>'
		 + '	<div class="border_colors">'
		 + '		<label>Border Colors: </label>'
		 + '		<input class="task_property_field" />'
		+ '		<span class="icon add_variable" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>'
		 + '	</div>'
		 + '	<div class="border_width">'
		 + '		<label>Border width: </label>'
		 + '		<input class="task_property_field" />'
		+ '		<span class="icon add_variable" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>'
		 + '	</div>'
		 + '	<div class="other_options">'
		 + '		<label>Other Options: <span class="icon add" onClick="addDrawGraphSettingsDataSetOtherOption(this)">Add</span></label>'
		 + '		<ul>'
		 + '			<li class="no_other_options">No options defined...</li>'
		 + '		</ul>'
		 + '	</div>'
		 + '</li>';
}

function addDrawGraphSettingsDataSetOtherOption(elm) {
	elm = $(elm);
	var html = $( getDrawGraphSettingsDataSetOtherOptionHtml() );
	
	var ul = elm.parent().closest(".other_options").children("ul");
	ul.append(html);
	
	ul.children("li.no_other_options").hide();
	
	addProgrammingTaskUtilInputsContextMenu( ul.children("li").last() );
	
	return html;
}

function getDrawGraphSettingsDataSetOtherOptionHtml() {
	return '<li>'
		 + '	<input class="task_property_field option_name" placeHolder="option name" />'
		 + '	<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>'
		 + '	<input class="task_property_field option_value" placeHolder="option value" />'
		 + '	<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>'
		 + '	<span class="icon delete" onClick="removeDrawGraphSettingsDataSetOtherOption(this)" title="Click here to remove this data-set option">Remove</span>'
		 + '</li>';
}

function removeDrawGraphSettingsDataSetOtherOption(elm) {
	if (confirm("Do you wish to remove this data-set option?")) {
		var li = $(elm).parent().closest("li");
		var ul = li.parent();
		
		li.remove();
		
		if (ul.children("li:not(.no_other_options)").length == 0)
			ul.children("li.no_other_options").show();
	}
}

function onDrawGraphJSCodeTabClick(elm) {
	elm = $(elm);
	
	if (elm.attr("is_init") != 1) {
		elm.attr("is_init", 1);
		initDrawGraphCode(elm);
	}
	
	if (confirm("Do you wish to convert the setings into javascript code?")) {
		var draw_graph_elm = elm.parent().closest(".draw_graph_action_body");
		var draw_graph_settings_elm = draw_graph_elm.children(".draw_graph_settings");
		var draw_graph_js_elm = draw_graph_elm.children(".draw_graph_js_code");
		
		var include_graph_library = draw_graph_settings_elm.find('.include_graph_library select').val();
		var width = draw_graph_settings_elm.find('.graph_width input').val();
		var height = draw_graph_settings_elm.find('.graph_height input').val();
		var labels_variable = draw_graph_settings_elm.find('.labels_variable input').val();
		var lis = draw_graph_settings_elm.find('.graph_data_sets > ul > li:not(.no_data_sets)');
		
		width = getDrawGraphSettingValueToJSCode(width, false, false, true);
		height = getDrawGraphSettingValueToJSCode(height, false, false, true);
		labels_variable = getDrawGraphSettingValueToJSCode(labels_variable, true, true, true);
		
		var code = '';
		var data_sets_code = '';
		var default_type = null;
		
		$.each(lis, function(idx, li) {
			li = $(li);
			
			var type = li.find('.type select').val();
			var item_label = li.find('.item_label input').val();
			var values_variable = li.find('.values_variable input').val();
			var background_colors = li.find('.background_colors input').val();
			var border_colors = li.find('.border_colors input').val();
			var border_width = li.find('.border_width input').val();
			var other_options = li.find(".other_options > ul > li:not(.no_other_options)");
			var data_set_options_code = '';
			var order = null;
			
			if (!default_type)
				default_type = type;
			
			values_variable = getDrawGraphSettingValueToJSCode(values_variable, true, true, true);
			item_label = getDrawGraphSettingValueToJSCode(item_label, false, false, true);
			background_colors = getDrawGraphSettingValueToJSCode(background_colors, false, true, true);
			border_colors = getDrawGraphSettingValueToJSCode(border_colors, false, true, true);
			border_width = getDrawGraphSettingValueToJSCode(border_width, false, false, true);
			
			//parse other_options into an object
			var parsed_other_options = {};
			
			$.each(other_options, function(idy, other_option) {
				other_option = $(other_option);
				var option_name = other_option.find(".option_name").val();
				option_name = option_name.replace(/\s*/g, "").replace(/(^\.+|\.+$)/g, ""); //remove all spaces and '.' at the begining and end of string
				
				if (option_name || $.isNumeric(option_name)) {
					var option_value = other_option.find(".option_value").val();
					
					if (option_name.indexOf(".") != -1) { //if is a composite option inside of an object
						var parts = option_name.split(".");
						var part_obj = parsed_other_options;
						
						for (var i = 0, t = parts.length; i < t; i++) {
							var part = parts[i];
							
							if (part || $.isNumeric(part)) {
								if (i + 1 == t)
									part_obj[part] = option_value;
								else {
									if (!part_obj.hasOwnProperty(part) || !$.isPlainObject(part_obj[part]))
										part_obj[part] = {};
								
									part_obj = part_obj[part];
								}
							}
						}
					}
					else
						parsed_other_options[option_name] = option_value;
				}
			});
			
			var parse_other_options_func = function(option_name, option_value, prefix) {
				var code = "";
				
				if ($.isPlainObject(option_value)) {
					code += prefix + option_name + ': {' + "\n";
					
					$.each(option_value, function(sub_option_name, sub_option_value) {
						code += parse_other_options_func(sub_option_name, sub_option_value, prefix + "\t");
					});
					
					code += prefix + '},' + "\n";
				}
				else {
					option_value = getDrawGraphSettingValueToJSCode(option_value, false, true, true);
					
					code += prefix + option_name + ': ' + option_value+ ',' + "\n";
				}
				
				return code;
			};
			
			$.each(parsed_other_options, function(option_name, option_value) {
				if (option_name == "order")
					order = getDrawGraphSettingValueToJSCode(option_value, false, true, true);
				else
					data_set_options_code += parse_other_options_func(option_name, option_value, "\t\t\t\t");
			});
			
			data_sets_code += ''
				 + "\t\t\t" + '{' + "\n"
				 + (type && type != default_type ? "\t\t\t\t" + 'type: "' + type + '",' + "\n" : '')
				 + (item_label ? "\t\t\t\t" + 'label: "' + item_label + '",' + "\n" : '')
				 + "\t\t\t\t" + 'data: ' + (values_variable ? values_variable : "null") + ',' + "\n"
				 + (background_colors ? "\t\t\t\t" + 'backgroundColor: ' + background_colors + ',' + "\n" : '')
				 + (border_colors ? "\t\t\t\t" + 'borderColor: ' + border_colors + ',' + "\n" : '')
				 + (border_width || $.isNumeric(border_width) ? "\t\t\t\t" + 'borderWidth: ' + border_width + ',' + "\n" : '')
				 + (order !== null ? "\t\t\t\t" + 'order: ' + order + (data_set_options_code ? ',' : '') + "\n" : '')
				 + data_set_options_code
				 + "\t\t\t" + '},' + "\n";
		});
		
		if (include_graph_library == "cdn_even_if_exists")
			code += '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>' + "\n\n";
		else if (include_graph_library == "cdn_if_not_exists")
			code += '<script>' + "\n"
				+ 'if (typeof Chart != "function")' + "\n"
				+ '	document.write(\'<scr\' + \'ipt src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></scr\' + \'ipt>\');' + "\n"
				+ '</script>' + "\n\n";
		
		var rand = parseInt(Math.random() * 1000);
		code += '<canvas id="my_chart_' + rand + '"' + (width || $.isNumeric(width) ? ' width="' + width + '"' : '') + (height || $.isNumeric(height) ? ' height="' + height + '"' : '') + '></canvas>' + "\n"
			 + "\n"
			 + '<script>' + "\n"
			 + 'var canvas_' + rand + ' = document.getElementById("my_chart_' + rand + '");' + "\n"
			 + 'var myChart_' + rand + ' = new Chart(canvas_' + rand + ', {' + "\n"
			 + "\t" + 'type: "' + default_type + '",' + "\n"
			 + "\t" + 'data: {' + "\n"
			 + (labels_variable ? "\t\t" + 'labels: ' + labels_variable + ',' + "\n" : '')
			 + "\t\t" + 'datasets: [' + "\n"
			 + data_sets_code
			 + "\t\t" + ']' + "\n"
			 + "\t" + '}' + "\n"
			 + '});' + "\n"
			 + '</script>';
		
		var editor = draw_graph_js_elm.data("editor");
		
		if (editor)
			editor.setValue(code);
		else
			draw_graph_js_elm.children("textarea").val(code);
	}
}

function getDrawGraphSettingValueToJSCode(value, is_variable_name, is_json_encode, is_code) {
	if (value) {
		var fc = value.charAt(0);
		var lc = value.charAt(value.length - 1);
		
		if ($.isNumeric(value) || value.toLowerCase() == "true" || value.toLowerCase() == "false")
			return value;
		else if (fc == '$')
			value = '\\' + value;
		else if (fc == '#' && lc == '#')
			value = '\\$' + value.substr(1, value.length - 2);
		else {
			var aux = FormFieldsUtilObj.getFormSettingsAttributeValueDesconfigured(value);
			var type = aux[1];
			
			if (type == "string" && is_variable_name)
				value = '\\$' + value;
			else
				value = getArgumentCode(value, type);
		}
		
		return is_json_encode ? '<?php echo json_encode(' + value + '); ?>' : (is_code ? '<?php echo ' + value + '; ?>' : value);
	}
	
	return "";
}

/* VARIABLE VALIDATOR FUNCTIONS */

function onChangeVariableValidatorMethodName(elm) {
	var elm = $(elm);
	var task_html_elm = elm.parent().closest(".validate_variable_action_body");
	var method = elm.val();
	var offset_div = task_html_elm.children(".offset");
	
	if (method.indexOf("TextValidator::check") === 0) {
		offset_div.show();
		
		var label = method.indexOf("Length") != -1 ? "Length" : (
			method.indexOf("Min") != -1 ? "Min" : (
				method.indexOf("Max") != -1 ? "Max" : "Offset"
			)
		);
		offset_div.children("label").html(label + ":");
	}
	else
		offset_div.hide();
}

/* GROUP ITEMS FUNCTIONS */

function toggleGroupBody(elm) {
	elm = $(elm);
	elm.toggleClass("expand_content collapse_content");
	
	var group_header = elm.parent().closest(".sla_group_header");
	var group = group_header.parent().closest(".sla_group_item");
	var group_body = group.children(".sla_group_body");
	var is_hidden = group_body.css("display") != "none";
	group_body.toggle("fast");
	
	if (is_hidden) {
		group_header.children(".sla_group_sub_header").hide("fast");
		group.addClass("collapsed");
	}
	else {
		group_header.children(".sla_group_sub_header").show("fast");
		group.removeClass("collapsed");
	}
}

function removeGroupItem(elm, do_not_confirm) {
	if (do_not_confirm || confirm("Do you wish to remove this item?")) {
		var item = $(elm).parent().closest(".sla_group_item");
		var parent = item.parent();
		item.remove();
		
		if (parent.children(".sla_group_item:not(.sla_group_default)").length == 0)
			parent.children(".sla_group_empty_items").show();
	}
}

function moveUpGroupItem(elm) {
	var item = $(elm).parent().closest(".sla_group_item");
	
	if (item.prev()[0] && !item.prev().hasClass("sla_group_default"))
		item.parent()[0].insertBefore(item[0], item.prev()[0]);
}

function moveDownGroupItem(elm) {
	var item = $(elm).parent().closest(".sla_group_item");
	
	if (item.next()[0])
		item.parent()[0].insertBefore(item.next()[0], item[0]);
}

/* CONDITIONS */

function onGroupConditionTypeChange(elm) {
	elm = $(elm);
	var selection = elm.val();
	
	var condition_value = elm.parent().children(".condition_value");
	condition_value.hide();
	
	switch (selection) {
		case "execute_if_var":
		case "execute_if_not_var":
			condition_value.show().attr("placeHolder", "Variable name");
			break;
			
		case "execute_if_post_button":
		case "execute_if_not_post_button":
		case "execute_if_get_button":
		case "execute_if_not_get_button":
			condition_value.show().attr("placeHolder", "Button name");
			break;
			
		case "execute_if_post_resource":
		case "execute_if_not_post_resource":
		case "execute_if_get_resource":
		case "execute_if_not_get_resource":
			condition_value.show().attr("placeHolder", "Resource name");
			break;
			
		case "execute_if_condition":
		case "execute_if_not_condition":
			condition_value.show().attr("placeHolder", "Condition code");
			break;
			
		case "execute_if_code":
		case "execute_if_not_code":
			condition_value.show().attr("placeHolder", "Condition Code");
			break;
	}
}

/* ProgrammingTaskUtil.on_programming_task_choose_created_variable_callback FUNCTIONS */

function onSLADataBaseActionTableProgrammingTaskChooseCreatedVariable(elm) {
	elm = $(elm);
	var p = elm.parent();
	
	if (p.children("input.attr_name").is(":visible"))
		elm.attr("input_selector", "input.attr_name");
	else
		elm.attr("input_selector", "input.attr_value");
	
	onSLAProgrammingTaskChooseCreatedVariable(elm);
}

function onSLAProgrammingTaskChooseCreatedVariable(elm) {
	elm = $(elm);
	var target_field = getTargetFieldForProgrammingTaskChooseFromFileManager(elm);
	
	if (target_field[0]) {
		var popup = $("#choose_property_variable_from_file_manager");
		
		//hide variable_settings bc it doesn't matter. Note that the onChangePropertyVariableType method (called below), shows the variable_settings, so we need to remove the proper classes
		var variable_settings = popup.children(".variable_settings");
		variable_settings.removeClass("new_var existent_var");
		
		//hide option 2 bc it doesn't matter
		var class_prop_var_option = popup.find(" > .type > select > option[value=class_prop_var]");
		class_prop_var_option.hide();
		
		//show GLOBALS[logged_user_id] option
		popup.find(" > .new_var > .scope > select > option[is_final_var]").show();
		
		//add new list option
		var option_get = popup.find(" > .new_var > .scope > select > option[value=_GET]");
		var option_prev = option_get.prev("option");
		
		if (option_prev.attr("value") != "[idx]") //Note that the [\\$idx] doesn't work.
			option_get.before('<option value="[idx]">Local list variable</option>');
		
		//prepare type field
		popup.children(".type").show();
		onChangePropertyVariableType( popup.find(" > .type > select")[0] );
		
		//prepare popup
		MyFancyPopup.init({
			elementToShow: popup,
			parentElement: document,
			onOpen: function() {
				//bc this can be a little bit slow if the forms has several groups, we add a timeout to happen asynchronously
				setTimeout(function() {
					//update ProgrammingTaskUtil.variables_in_workflow
					updateSLAProgrammingTaskVariablesInWorkflow(popup);
				}, 100);
			},
			onClose: function() {
				class_prop_var_option.show();
				variable_settings.addClass("new_var existent_var").show();
			},
			
			targetField: target_field[0],
			updateFunction: function(other_elm) { //prepare update handler
				var type = popup.find(" > .type > select").val();
				var type_elm = popup.find("." + type);
				var value = null;
				
				if (type == "existent_var")
					value = type_elm.find(" > .variable > select").val();
				else if (type) {
					var is_final_var = type == "new_var" && type_elm.find(" > .scope > select option:selected[is_final_var]").length;
					
					if (is_final_var) { //used for GLOBALS['logged_user_id']
						value = type_elm.find(" > .scope > select").val();
						value = value.replace(/\[(['"])/g, "[$1").replace(/(['"])\]/g, "$1]"); //replaces ['xxx'] with [xxx]
					}
					else {
						value = type_elm.find(" > .name > input").val();
						value = ("" + value).replace(/^\s+/g, "").replace(/\s+$/g, "");
					}
					
					if (value) {
						value = value[0] == '$' ? value.substr(1, value.length) : value; //remove $ if exists
						
						if (type == "new_var" && !is_final_var)
							value = getNewVarWithSubGroupsInProgrammingTaskChooseCreatedVariablePopup(type_elm, value, false);
					}
				}
				
				if (value) {
					var value_type = "string";
					
					target_field.val(value ? "#" + value + "#" : "");
					target_field.parent().parent().find(".var_type select").val(value_type);
					
					//set value_type if exists and if only input name is simple without "]" and "[" chars:
					var target_field_name = target_field.attr("name");
					
					if (target_field_name && !target_field_name.match(/[\[\]]/)) {
						var target_field_type = target_field.parent().children("select[name=" + target_field_name + "_type]");
						
						if (target_field_type[0])
							target_field_type.val(value_type);
					}
					else if (target_field.is(".key") && target_field.parent().is(".item")) //in case of array items
						target_field.parent().children(".key_type").val(value_type);
					else if (target_field.is(".value") && target_field.parent().is(".item")) //in case of array items
						target_field.parent().children(".value_type").val(value_type);
					else if (target_field.parent().is(".table_arg_value")) //in case of method/function args
						target_field.parent().parent().find(".table_arg_type select").val(value_type);
					
					//put cursor in targetField
					target_field.focus();
					
					MyFancyPopup.hidePopup();
				}
				else
					alert("No variable selected!");
			},
		});
		
		//show popup
		MyFancyPopup.showPopup();
	}
	else
		StatusMessageHandler.showMessage("No targeted field found!");
}

function updateSLAProgrammingTaskVariablesInWorkflow(popup) {
	//update ProgrammingTaskUtil.variables_in_workflow
	var select = popup.find(".existent_var .variable select");
	
	//show loading bar
	showLoadingBarInSLAProgrammingTaskVariablesInWorkflow(select);
	
	//set vars in select
	updateSLAProgrammingTaskVariablesInWorkflowSelect(select);
	
	//hide loading bar
	hideLoadingBarInSLAProgrammingTaskVariablesInWorkflow(select);
}

function updateSLAProgrammingTaskVariablesInWorkflowSelect(select) {
	window.variables_in_workflow_loading_processes = 0;
	ProgrammingTaskUtil.variables_in_workflow = {};
	
	if (update_sla_programming_task_variables_from_sla_groups) {
		var inputs = $(".sla_groups_flow .sla_groups .sla_group_item:not(sla_group_default) > .sla_group_header > .result_var_name");
		var include_paths = [];
		
		$.each(inputs, function(idx, input) {
			input = $(input);
			var sla_group_header = input.parent();
			var sla_group_item = sla_group_header.parent();
			var action_type = sla_group_header.children(".action_type").val();
			
			if (action_type == "include_file") {
				var item_settings = getSLASettingsFromItemsToSave(sla_group_item, {ignore_errors: true});
				var action_value = item_settings && item_settings[0] ? item_settings[0]["action_value"] : null;
				
				if ($.isPlainObject(action_value) && action_value["path"])
					include_paths.push(action_value["path"]);
			}
		});
		
		$.each(inputs, function(idx, input) {
			input = $(input);
			var var_name = input.val();
			var_name = var_name ? var_name.replace(/^\s+/g, "").replace(/\s+$/g, "") : "";
			
			if (var_name != "") {
				var sla_group_header = input.parent();
				var sla_group_item = sla_group_header.parent();
				var action_type = sla_group_header.children(".action_type").val();
				
				ProgrammingTaskUtil.variables_in_workflow[var_name] = {};
				
				//update sub vars if var is a composed var
				if (action_type == "select" || action_type == "callbusinesslogic" || action_type == "callibatisquery" || action_type == "callhibernatemethod" || action_type == "getquerydata" || action_type == "setquerydata" || action_type == "restconnector" || action_type == "soapconnector" || action_type == "array" || action_type == "callobjectmethod") {
					var item_settings = getSLASettingsFromItemsToSave(sla_group_item, {ignore_errors: true});
					updateSLAProgrammingTaskVariablesInWorkflowBasedInItemSettings(select, var_name, item_settings ? item_settings[0] : null, include_paths);
				}
			}
		});
	}
	
	if (update_sla_programming_task_variables_from_workflow && taskFlowChartObj) {
		var tasks_properties = taskFlowChartObj.TaskFlow.tasks_properties;
		
		if (tasks_properties) {
			var include_paths = [];
			
			$.each(tasks_properties, function(idx, task_properties) {
				if (task_properties && $.isPlainObject(task_properties["properties"]) && task_properties["properties"]["action_type"] == "include_file") {
					var action_value = task_properties["properties"]["action_value"];
					
					if ($.isPlainObject(action_value) && action_value["path"])
						include_paths.push(action_value["path"]);
				}
			});
			
			$.each(tasks_properties, function(idx, task_properties) {
				if (task_properties && $.isPlainObject(task_properties["properties"])) {
					var var_name = task_properties["properties"].hasOwnProperty("result_var_name") ? task_properties["properties"]["result_var_name"] : "";
					var_name = var_name ? var_name.replace(/^\s+/g, "").replace(/\s+$/g, "") : "";
					
					if (var_name != "") {
						ProgrammingTaskUtil.variables_in_workflow[var_name] = {};
						
						//update sub vars if var is a composed var
						updateSLAProgrammingTaskVariablesInWorkflowBasedInItemSettings(select, var_name, task_properties["properties"], include_paths);
					}
				}
			});
		}
	}
	
	//update select field from ProgrammingTaskUtil.variables_in_workflow
	populateVariablesOfTheWorkflowInSelectField(select);
}

function showLoadingBarInSLAProgrammingTaskVariablesInWorkflow(select) {
	var p = select.parent();
	var loading = p.children(".loading");
	
	if (!loading[0]) {
		loading = $('<span class="icon loading"></span>');
		p.append(loading);
	}
	else
		loading.show();
}

function hideLoadingBarInSLAProgrammingTaskVariablesInWorkflow(select) {
	if (window.variables_in_workflow_loading_processes == 0)
		select.parent().children(".loading").remove();
}

function updateSLAProgrammingTaskVariablesInWorkflowBasedInItemSettings(select, var_name, item_settings, include_paths) {
	if (item_settings && $.isPlainObject(item_settings)) {
		var action_type = item_settings["action_type"];
		var action_value = item_settings["action_value"];
		
		if ($.isPlainObject(action_value) || $.isArray(action_value))
			switch (action_type) {
				case "select":
					if (action_value["attributes"]) {
						$.each(action_value["attributes"], function(idx, attr) {
							var column = attr["name"] ? attr["name"] : attr["column"];
							
							if (column && column != "*")
								ProgrammingTaskUtil.variables_in_workflow[var_name + "[" + column + "]"] = {};
						});
					}
					else if (action_value["sql"])
						updateSLAProgrammingTaskVariablesInWorkflowBasedInSQL(select, var_name, action_value["sql"]);
					
					break;
				case "callbusinesslogic":
					updateSLAProgrammingTaskVariablesInWorkflowBasedInBusinessLogicService(select, var_name, action_value);
					break;
				case "callibatisquery":
					updateSLAProgrammingTaskVariablesInWorkflowBasedInIbatisQuery(select, var_name, action_value);
					break;
				case "callhibernatemethod":
					updateSLAProgrammingTaskVariablesInWorkflowBasedInHibernateMethod(select, var_name, action_value);
					break;
				case "getquerydata":
					updateSLAProgrammingTaskVariablesInWorkflowBasedInSQL(select, var_name, action_value["sql"]);
					break;
				case "setquerydata":
					updateSLAProgrammingTaskVariablesInWorkflowBasedInSQL(select, var_name, action_value["sql"]);
					break;
				case "callobjectmethod":
					updateSLAProgrammingTaskVariablesInWorkflowBasedInObjectMethod(select, var_name, action_value, include_paths);
					break;
				case "restconnector":
					updateSLAProgrammingTaskVariablesInWorkflowBasedInRestConnector(select, var_name, action_value);
					break;
				case "soapconnector":
					updateSLAProgrammingTaskVariablesInWorkflowBasedInSoapConnector(select, var_name, action_value);
					break;
				case "array":
					var count = 0;
					
					$.each(action_value, function(idx, item) {
						if ($.isPlainObject(item)) {
							if (item["key"] && ($.isNumeric(item["key"]) || item["key_type"] == "string" || item["key_type"] == "null")) {
								if (item["key_type"] == "null")
									ProgrammingTaskUtil.variables_in_workflow[var_name + "[" + count + "]"] = {};
								else
									ProgrammingTaskUtil.variables_in_workflow[var_name + "[" + item["key"] + "]"] = {};
							}
							
							if (item["key_type"] == "null")
								count++;
						}
					});
					break;
			}
	}
}

function updateSLAProgrammingTaskVariablesInWorkflowBasedInBusinessLogicService(select, var_name, bl_settings) {
	if (bl_settings && bl_settings["service_id"]) {
		var broker_name = brokers_name_by_obj_code[ bl_settings["method_obj"] ];
		
		if (broker_name) {
			var broker_props = brokers_settings[broker_name];
			
			if (broker_props) {
				if (("" + bl_settings["service_id"]).indexOf(".get") != -1) { //if a get method: like get, getAll, gets, getItem, getItems, getAllItems
					//http://jplpinto.localhost/__system/phpframework/businesslogic/get_business_logic_attributes?bean_name=SoaBLLayer&bean_file_name=soa_bll.xml&module_id=sample.test&service=ItemService.get
					var url = get_business_logic_result_properties_url.replace("#bean_file_name#", broker_props[1]).replace("#bean_name#", broker_props[2]).replace("#module_id#", bl_settings["module_id"]).replace("#service#", bl_settings["service_id"]);
					var url_id = $.md5(url);
					
					if (cached_data_for_variables_in_workflow.hasOwnProperty(url_id)) {
						var data = cached_data_for_variables_in_workflow[url_id];
						
						if(data && $.isPlainObject(data) && data["attributes"])
							$.each(data["attributes"], function(idx, attr) {
								var column = attr["column"];
								
								if (column)
									ProgrammingTaskUtil.variables_in_workflow[var_name + (data["is_multiple"] ? "[idx]" : "") + "[" + attr["column"] + "]"] = {};
							});
					}
					else {
						window.variables_in_workflow_loading_processes++;
						
						$.ajax({
							type : "get",
							url : url,
							dataType : "json",
							success : function(data, textStatus, jqXHR) {
								cached_data_for_variables_in_workflow[url_id] = data;
								
								if(data && $.isPlainObject(data) && data["attributes"]) {
									$.each(data["attributes"], function(idx, attr) {
										var column = attr["column"];
										
										if (column) 
											ProgrammingTaskUtil.variables_in_workflow[var_name + (data["is_multiple"] ? "[idx]" : "") + "[" + attr["column"] + "]"] = {};
									});
									
									populateVariablesOfTheWorkflowInSelectField(select);
								}
								
								window.variables_in_workflow_loading_processes--;
								hideLoadingBarInSLAProgrammingTaskVariablesInWorkflow(select);
							},
							error : function(jqXHR, textStatus, errorThrown) { 
								window.variables_in_workflow_loading_processes--;
								hideLoadingBarInSLAProgrammingTaskVariablesInWorkflow(select);
							},
						});
					}
				}
			}
		}
	}
}

//get results for ResourceUtil method
function updateSLAProgrammingTaskVariablesInWorkflowBasedInObjectMethod(select, var_name, om_settings, include_paths) {
	if (om_settings && om_settings["method_obj"] && om_settings["method_name"]) {
		if (("" + om_settings["method_name"]).indexOf("get") != -1) { //if a get method: like get, getAll, gets, getItem, getItems, getAllItems
			//get util path for correspondent method_obj (class name) if exists
			var method_obj_path = "";
			var regex = new RegExp("\\->\\s*getUtilPath\\s*\\(");
			
			if (include_paths)
				$.each(include_paths, function(idx, path) {
					if (path.indexOf(om_settings["method_obj"]) != -1 && (
						path.indexOf("/util/") != -1 || path.match(regex)
					)) {
						//if: $EVC->getUtilPath("some path here")
						if (path.match(regex)) { 
							regex = new RegExp("\\->\\s*getUtilPath\\s*\\(\\s*\"([^\"]+)\"\\s*\\)");
							var m = path.match(regex);
							
							if (m && m[1])
								method_obj_path = m[1];
							else {
								regex = new RegExp("\\->\\s*getUtilPath\\s*\\(\\s*'([^']+)'\\s*\\)");
								m = path.match(regex);
								
								if (m && m[1])
									method_obj_path = m[1];
							}
							
							if (method_obj_path)
								method_obj_path = "/src/util/" + method_obj_path + "." + (default_extension ? default_extension : "php");
						}
						//if: $EVC->getPresentationLayer()->getLayerPathSetting() . \"some path here\"
						else if (path.match(/\s*\.\s*"/)) { 
							var m = path.match(/\s*\.\s*"([^"]+)"/);
							
							if (m && m[1])
								method_obj_path = m[1];
						}
						
						return false;
					}
				});
			
			//http://jplpinto.localhost/__system/phpframework/presentation/get_util_attributes?bean_name=PresentationPLayer&bean_file_name=presentation_pl.xml&class_path=resource/ItemResourceUtil.php&class_name=ItemResourceUtil&method=get
			var url = get_util_result_properties_url.replace("#class_path#", method_obj_path).replace("#class_name#", om_settings["method_obj"]).replace("#method#", om_settings["method_name"]);
			var url_id = $.md5(url);
			
			if (cached_data_for_variables_in_workflow.hasOwnProperty(url_id)) {
				var data = cached_data_for_variables_in_workflow[url_id];
				
				if(data && $.isPlainObject(data) && data["attributes"])
					$.each(data["attributes"], function(idx, attr) {
						var column = attr["column"];
						
						if (column)
							ProgrammingTaskUtil.variables_in_workflow[var_name + (data["is_multiple"] ? "[idx]" : "") + "[" + attr["column"] + "]"] = {};
					});
			}
			else {
				window.variables_in_workflow_loading_processes++;
				
				$.ajax({
					type : "get",
					url : url,
					dataType : "json",
					success : function(data, textStatus, jqXHR) {
						cached_data_for_variables_in_workflow[url_id] = data;
						
						if(data && $.isPlainObject(data) && data["attributes"]) {
							$.each(data["attributes"], function(idx, attr) {
								var column = attr["column"];
								
								if (column) 
									ProgrammingTaskUtil.variables_in_workflow[var_name + (data["is_multiple"] ? "[idx]" : "") + "[" + attr["column"] + "]"] = {};
							});
							
							populateVariablesOfTheWorkflowInSelectField(select);
						}
						
						window.variables_in_workflow_loading_processes--;
						hideLoadingBarInSLAProgrammingTaskVariablesInWorkflow(select);
					},
					error : function(jqXHR, textStatus, errorThrown) { 
						window.variables_in_workflow_loading_processes--;
						hideLoadingBarInSLAProgrammingTaskVariablesInWorkflow(select);
					},
				});
			}
		}
	}
}

function updateSLAProgrammingTaskVariablesInWorkflowBasedInIbatisQuery(select, var_name, ibatis_settings) {
	if (ibatis_settings && ibatis_settings["service_type"] == "select") {
		var broker_name = brokers_name_by_obj_code[ ibatis_settings["method_obj"] ];
		
		if (broker_name) {
			var broker_props = brokers_settings[broker_name];
			
			if (broker_props) {
				//http://jplpinto.localhost/__system/phpframework/dataaccess/get_query_attributes?bean_name=IormIDALayer&bean_file_name=iorm_dal.xml&module_id=sample.test&query_type=select&query=get_item
				var url = get_query_result_properties_url.replace("#bean_file_name#", broker_props[1]).replace("#bean_name#", broker_props[2]).replace("#module_id#", ibatis_settings["module_id"]).replace("#query_type#", ibatis_settings["service_type"]).replace("#query#", ibatis_settings["service_id"]);
				updateSLAProgrammingTaskVariablesInWorkflowBasedInDataAccess(select, var_name, url);
			}
		}
	}
}

function updateSLAProgrammingTaskVariablesInWorkflowBasedInHibernateMethod(select, var_name, hibernate_settings) {
	if (hibernate_settings) {
		var broker_name = brokers_name_by_obj_code[ hibernate_settings["method_obj"] ];
		
		if (broker_name) {
			var broker_props = brokers_settings[broker_name];
			
			if (broker_props) {
				var url = null;
				
				if (hibernate_settings["service_method"] == "callSelect") {
					//http://jplpinto.localhost/__system/phpframework/dataaccess/get_query_attributes?bean_name=HormHDALayer&bean_file_name=horm_dal.xml&db_driver=test&module_id=sample.test&query_type=select&query=get_items&obj=Item&relationship_type=queries
					url = get_query_result_properties_url.replace("#bean_file_name#", broker_props[1]).replace("#bean_name#", broker_props[2]).replace("#module_id#", hibernate_settings["module_id"]).replace("#query_type#", "select").replace("#query#", hibernate_settings["sma_query_id"]).replace("#obj#", hibernate_settings["service_id"]).replace("#relationship_type#", "queries");
				}
				else if (hibernate_settings["service_method"] == "callQuery" && hibernate_settings["sma_query_type"] == "select") {
					//http://jplpinto.localhost/__system/phpframework/dataaccess/get_query_attributes?bean_name=HormHDALayer&bean_file_name=horm_dal.xml&db_driver=test&module_id=sample.test&query_type=select&query=get_items&obj=Item&relationship_type=queries
					url = get_query_result_properties_url.replace("#bean_file_name#", broker_props[1]).replace("#bean_name#", broker_props[2]).replace("#module_id#", hibernate_settings["module_id"]).replace("#query_type#", hibernate_settings["sma_query_type"]).replace("#query#", hibernate_settings["sma_query_id"]).replace("#obj#", hibernate_settings["service_id"]).replace("#relationship_type#", "queries");
				}
				else if (hibernate_settings["service_method"] == "find" || hibernate_settings["service_method"] == "findById") {
					//http://jplpinto.localhost/__system/phpframework/dataaccess/get_query_attributes?bean_name=HormHDALayer&bean_file_name=horm_dal.xml&db_driver=test&module_id=sample.test&query=findById&obj=Item&relationship_type=native
					url = get_query_result_properties_url.replace("#bean_file_name#", broker_props[1]).replace("#bean_name#", broker_props[2]).replace("#module_id#", hibernate_settings["module_id"]).replace("#query#", hibernate_settings["service_method"]).replace("#obj#", hibernate_settings["service_id"]).replace("#relationship_type#", "native");
				}
				else if (hibernate_settings["service_method"] == "findRelationship") {
					//http://jplpinto.localhost/__system/phpframework/dataaccess/get_query_attributes?bean_name=HormHDALayer&bean_file_name=horm_dal.xml&db_driver=test&module_id=sample.test&query=findrelationship&rel_name=item_sub_item_childs&obj=Item&relationship_type=native
					url = get_query_result_properties_url.replace("#bean_file_name#", broker_props[1]).replace("#bean_name#", broker_props[2]).replace("#module_id#", hibernate_settings["module_id"]).replace("#query#", hibernate_settings["service_method"]).replace("#rel_name#", hibernate_settings["sma_rel_name"]).replace("#obj#", hibernate_settings["service_id"]).replace("#relationship_type#", "native");
				}
				
				updateSLAProgrammingTaskVariablesInWorkflowBasedInDataAccess(select, var_name, url);
			}
		}
	}
}

function updateSLAProgrammingTaskVariablesInWorkflowBasedInDataAccess(select, var_name, url) {
	if (url) {
		url = url.replace(/#[a-z0-9_\-]+#/gi, ""); //remove all #xxx# that were not replaced previously, otherwise the url will be shrinked because everything after # will be considered a hashtag.
		var url_id = $.md5(url);
		
		if (cached_data_for_variables_in_workflow.hasOwnProperty(url_id)) {
			var data = cached_data_for_variables_in_workflow[url_id];
			
			if(data && $.isPlainObject(data) && data["attributes"])
				$.each(data["attributes"], function(idx, attr) {
					var column = attr["name"] ? attr["name"] : attr["column"];
					
					if (column != "*") {
						if (data["is_single"] || !data["is_multiple"])
							ProgrammingTaskUtil.variables_in_workflow[var_name + "[" + attr["column"] + "]"] = {};
						
						if (data["is_multiple"])
							ProgrammingTaskUtil.variables_in_workflow[var_name + "[idx][" + attr["column"] + "]"] = {};
					}
				});
		}
		else {
			window.variables_in_workflow_loading_processes++;
			
			$.ajax({
				type : "get",
				url : url,
				dataType : "json",
				success : function(data, textStatus, jqXHR) {
					cached_data_for_variables_in_workflow[url_id] = data;
					
					if(data && $.isPlainObject(data) && data["attributes"]) {
						$.each(data["attributes"], function(idx, attr) {
							var column = attr["name"] ? attr["name"] : attr["column"];
							
							if (column != "*") {
								if (data["is_single"] || !data["is_multiple"])
									ProgrammingTaskUtil.variables_in_workflow[var_name + "[" + attr["column"] + "]"] = {};
								
								if (data["is_multiple"])
									ProgrammingTaskUtil.variables_in_workflow[var_name + "[idx][" + attr["column"] + "]"] = {};
							}
						});
						
						populateVariablesOfTheWorkflowInSelectField(select);
					}
					
					window.variables_in_workflow_loading_processes--;
					hideLoadingBarInSLAProgrammingTaskVariablesInWorkflow(select);
				},
				error : function(jqXHR, textStatus, errorThrown) { 
					window.variables_in_workflow_loading_processes--;
					hideLoadingBarInSLAProgrammingTaskVariablesInWorkflow(select);
				},
			});
		}
	}
}

function updateSLAProgrammingTaskVariablesInWorkflowBasedInSQL(select, var_name, sql) {
	if (sql) {
		var sql_id = $.md5(sql);
		
		if (cached_data_for_variables_in_workflow.hasOwnProperty(sql_id)) {
			var data = cached_data_for_variables_in_workflow[sql_id];
			
			if(data && $.isPlainObject(data) && data["attributes"] && data["type"] == "select")
				$.each(data["attributes"], function(idx, attr) {
					var column = attr["name"] ? attr["name"] : attr["column"];
					
					if (column && column != "*") {
						ProgrammingTaskUtil.variables_in_workflow[var_name + "[" + column + "]"] = {};
						ProgrammingTaskUtil.variables_in_workflow[var_name + "[idx][" + column + "]"] = {};
					}
				});
		}
		else {
			window.variables_in_workflow_loading_processes++;
			
			//get selected db broker and db driver and add them to get_query_obj_from_sql
			var url = get_query_obj_from_sql.replace("#db_broker#", default_dal_broker ? default_dal_broker : "").replace("#db_driver#", default_db_driver ? default_db_driver : "");
			
			$.ajax({
				type : "post",
				url : url,
				data : {"sql" : sql},
				dataType : "json",
				success : function(data, textStatus, jqXHR) {
					cached_data_for_variables_in_workflow[sql_id] = data;
					
					if(data && $.isPlainObject(data) && data["attributes"] && data["type"] == "select") {
						$.each(data["attributes"], function(idx, attr) {
							var column = attr["name"] ? attr["name"] : attr["column"];
							
							if (column && column != "*") {
								ProgrammingTaskUtil.variables_in_workflow[var_name + "[" + column + "]"] = {};
								ProgrammingTaskUtil.variables_in_workflow[var_name + "[idx][" + column + "]"] = {};
							}
						});
						
						populateVariablesOfTheWorkflowInSelectField(select);
					}
					
					window.variables_in_workflow_loading_processes--;
					hideLoadingBarInSLAProgrammingTaskVariablesInWorkflow(select);
				},
				error : function(jqXHR, textStatus, errorThrown) { 
					window.variables_in_workflow_loading_processes--;
					hideLoadingBarInSLAProgrammingTaskVariablesInWorkflow(select);
				},
			});
		}
	}
}

function updateSLAProgrammingTaskVariablesInWorkflowBasedInRestConnector(select, var_name, rest_settings) {
	var allowed_result_types = ["content", "content_json", "content_xml", "content_xml_simple", "content_serialized"];
	
	if (rest_settings && $.inArray(rest_settings["result_type"], allowed_result_types) != -1) {
		var post_data = {"action_type" : "restconnector", "action_value": rest_settings};
		var post_data_id = $.md5(JSON.stringify(post_data));
		
		if (cached_data_for_variables_in_workflow.hasOwnProperty(post_data_id)) {
			var data = cached_data_for_variables_in_workflow[post_data_id];
			
			if (data && $.isPlainObject(data) && data["attributes"])
				$.each(data["attributes"], function(idx, attr) {
					var column = attr["column"];
					
					if (column) 
						ProgrammingTaskUtil.variables_in_workflow[var_name + (data["is_multiple"] ? "[idx]" : "") + "[" + attr["column"] + "]"] = {};
				});
		}
		else {
			window.variables_in_workflow_loading_processes++;
			//samples: 
			//- https://postman-echo.com/get?test=123
			//- http://localhost/samples/books.xml
			//- http://localhost/samples/book.xml
			//- http://localhost/samples/books.json
			//- http://localhost/samples/book.json
			
			$.ajax({
				type : "post",
				url : get_sla_action_result_properties_url,
				data : post_data,
				dataType : "json",
				success : function(data, textStatus, jqXHR) {
					cached_data_for_variables_in_workflow[post_data_id] = data;
					
					if (data && $.isPlainObject(data) && data["attributes"]) {
						$.each(data["attributes"], function(idx, attr) {
							var column = attr["column"];
							
							if (column) 
								ProgrammingTaskUtil.variables_in_workflow[var_name + (data["is_multiple"] ? "[idx]" : "") + "[" + attr["column"] + "]"] = {};
						});
						
						populateVariablesOfTheWorkflowInSelectField(select);
					}
					
					window.variables_in_workflow_loading_processes--;
					hideLoadingBarInSLAProgrammingTaskVariablesInWorkflow(select);
				},
				error : function(jqXHR, textStatus, errorThrown) { 
					window.variables_in_workflow_loading_processes--;
					hideLoadingBarInSLAProgrammingTaskVariablesInWorkflow(select);
				},
			});
		}
	}
}

function updateSLAProgrammingTaskVariablesInWorkflowBasedInSoapConnector(select, var_name, soap_settings) {
	//console.log(soap_settings);
	var allowed_types = ["callSoapClient", "callSoapFunction"];
	
	if (soap_settings && $.isPlainObject(soap_settings["data"]) && $.inArray(soap_settings["data"]["type"], allowed_types) != -1) {
		var allowed_result_types = ["content", "content_json", "content_xml", "content_xml_simple", "content_serialized"];
		
		if (soap_settings["data"]["type"] != "callSoapFunction" || $.inArray(soap_settings["result_type"], allowed_result_types) != -1) {
			var post_data = {"action_type" : "soapconnector", "action_value": soap_settings};
			var post_data_id = $.md5(JSON.stringify(post_data));
			
			if (cached_data_for_variables_in_workflow.hasOwnProperty(post_data_id)) {
				var data = cached_data_for_variables_in_workflow[post_data_id];
				
				if (data && $.isPlainObject(data)) {
					if (soap_settings["data"]["type"] == "callSoapClient") {
						if (data["functions"] && $.isArray(data["functions"]))
							$.each(data["functions"], function(idx, func) {
								if (func["name"])
									ProgrammingTaskUtil.variables_in_workflow[var_name + "[" + func["name"] + "]"] = {};
							});
					}
					else if (data["attributes"]) {
						$.each(data["attributes"], function(idx, attr) {
							var column = attr["column"];
							
							if (column) 
								ProgrammingTaskUtil.variables_in_workflow[var_name + (data["is_multiple"] ? "[idx]" : "") + "[" + attr["column"] + "]"] = {};
						});
					}
				}
			}
			else {
				window.variables_in_workflow_loading_processes++;
				//samples: 
				//- http://localhost/samples/soap.wsdl
				
				$.ajax({
					type : "post",
					url : get_sla_action_result_properties_url,
					data : post_data,
					dataType : "json",
					success : function(data, textStatus, jqXHR) {
						//console.log(data);
						cached_data_for_variables_in_workflow[post_data_id] = data;
						
						if (data && $.isPlainObject(data)) {
							if (soap_settings["data"]["type"] == "callSoapClient") {
								if (data["functions"] && $.isArray(data["functions"]))
									$.each(data["functions"], function(idx, func) {
										if (func["name"])
											ProgrammingTaskUtil.variables_in_workflow[var_name + "[" + func["name"] + "]"] = {};
									});
							}
							else if (data["attributes"]) {
								$.each(data["attributes"], function(idx, attr) {
									var column = attr["column"];
									
									if (column) 
										ProgrammingTaskUtil.variables_in_workflow[var_name + (data["is_multiple"] ? "[idx]" : "") + "[" + attr["column"] + "]"] = {};
								});
							}
							
							populateVariablesOfTheWorkflowInSelectField(select);
						}
						
						window.variables_in_workflow_loading_processes--;
						hideLoadingBarInSLAProgrammingTaskVariablesInWorkflow(select);
					},
					error : function(jqXHR, textStatus, errorThrown) { 
						window.variables_in_workflow_loading_processes--;
						hideLoadingBarInSLAProgrammingTaskVariablesInWorkflow(select);
					},
				});
			}
		}
	}
}

/* SAVE FUNCTIONS */

function getSLASettingsFromItemsToSave(items, options) {
	var actions_settings = [];
	var ignore_errors = options && $.isPlainObject(options) && options["ignore_errors"];
	
	$.each(items, function(idx, item) {
		item = $(item);
		var header = item.children(".sla_group_header");
		var result_var_name = header.children(".result_var_name");
		var action_type = header.children(".action_type");
		var sub_header = header.children(".sla_group_sub_header");
		var condition_type = sub_header.children(".condition_type");
		var condition_value = sub_header.children(".condition_value");
		var action_description = sub_header.find(" > .action_description > textarea");
		var group_body = item.children(".sla_group_body");
		
		var selection = action_type.val();
		var is_selection_undefined = action_type.find("option:selected").attr("undefined");
		
		var item_settings = {
			"result_var_name": result_var_name.val(), 
			"action_type": selection, 
			"condition_type": condition_type.val(), 
			"condition_value": condition_value.val(),
			"action_description": action_description.val(),
			"action_value": {},
		};
		
		if (is_selection_undefined) {
			var v = group_body.children(".undefined_action_value").val();
			item_settings["action_value"] = v ? JSON.parse(v) : {};
		}
		else {
			switch (selection) {
				case "html": //getting design form html settings
					var section = group_body.children(".html_action_body");
					var create_form_task_html = section.children(".create_form_task_html");
					
					item_settings["action_value"]["form_settings_data_type"] = create_form_task_html.find(".form_settings select").val();
					
					if (item_settings["action_value"]["form_settings_data_type"] == "array") {
						var form_settings_data = parseArray( create_form_task_html.children(".form_settings_data") );
						item_settings["action_value"]["form_settings_data"] = form_settings_data["form_settings_data"];
					}
					else if (item_settings["action_value"]["form_settings_data_type"] == "settings") {
						CreateFormTaskPropertyObj.prepareCssAndJsFieldsToSave(create_form_task_html);
						
						var form_settings_data = FormFieldsUtilObj.convertFormSettingsDataSettingsToArray( create_form_task_html.children(".inline_settings") );
						ArrayTaskUtilObj.onLoadArrayItems( create_form_task_html.children(".form_settings_data"), form_settings_data, "");
						
						var form_settings_data = parseArray( create_form_task_html.children(".form_settings_data") );
						item_settings["action_value"]["form_settings_data"] = form_settings_data["form_settings_data"];
						item_settings["action_value"]["form_settings_data_type"] = "array";
					}
					else if (item_settings["action_value"]["form_settings_data_type"] == "ptl") {
						var ptl_settings = create_form_task_html.find(".ptl_settings");
						var code = getPtlElementTemplateSourceEditorValue(ptl_settings, true);
						var external_vars = {};
						
						$.each( ptl_settings.find(" > .ptl_external_vars .item"), function (idx, item) {
							item = $(item);
							var k = item.children(".key").val();
							var v = item.children(".value").val();
							
							if (k && v)
								external_vars[ k.charAt(0) == "$" ? k.substr(1) : k ] = v.charAt(0) == "$" ? v : "$" + v;
						});
						
						item_settings["action_value"]["form_settings_data"] = {"ptl" : {
							"code" : code,
							"input_data_var_name" : ptl_settings.find(" > .input_data_var_name > input").val(),
							"idx_var_name" : ptl_settings.find(" > .idx_var_name > input").val(),
							"external_vars" : external_vars,
						}};
						item_settings["action_value"]["form_settings_data_type"] = "ptl";
					}
					else
						item_settings["action_value"]["form_settings_data"] = create_form_task_html.find(".form_settings input").val();
					
					break;

				case "callbusinesslogic":
				case "callibatisquery":
				case "callhibernatemethod":
				case "getquerydata":
				case "setquerydata":
				case "callfunction":
				case "callobjectmethod":
				case "restconnector":
				case "soapconnector":
					//getting brokers settings
					var section = group_body.children(".broker_action_body");
					
					item_settings["action_value"] = getBrokerSettings(section, selection);
					break;
					
				case "insert":
				case "update":
				case "delete":
				case "select":
				case "count":
				case "procedure":
				case "getinsertedid":
					var section = group_body.children(".database_action_body");
					
					//get header fields
					item_settings["action_value"]["dal_broker"] = section.find(".dal_broker select").val();
					item_settings["action_value"]["db_driver"] = section.find(".db_driver select").val();
					item_settings["action_value"]["db_type"] = section.find(".db_type select").val();
					
					if (selection != "getinsertedid") {
						//get table or sql fields
						var select_tab_index = section.find(".query").tabs("option", "active");
						
						if (select_tab_index == 0) {
							var table = section.find(".database_action_table > .table > select").val();
							
							item_settings["action_value"]["table"] = table;
							
							if (table != "") {
								var attributes = [];
								var conditions = [];
								
								$.each( section.find(".database_action_table > .attributes > ul > li"), function (idx, li) {
									li = $(li);
									
									if (li.children(".attr_active").is(":checked"))
										attributes.push({
											"column": li.attr("data_attr_name"),
											"value": li.children(".attr_value").val(),
											"name": li.children(".attr_name").val()
										});
								});
								
								$.each( section.find(".database_action_table > .conditions > ul > li"), function (idx, li) {
									li = $(li);
									
									if (li.children(".attr_active").is(":checked"))
										conditions.push({
											"column": li.attr("data_attr_name"),
											"value": li.children(".attr_value").val()
										});
								});
								
								item_settings["action_value"]["attributes"] = attributes;
								item_settings["action_value"]["conditions"] = conditions;
							}
							else if (!ignore_errors) {
								var label = action_type.find("option:selected").text();
								
								if (!is_from_auto_save)
									StatusMessageHandler.showError("'" + label + "' group cannot be empty!");
								
								MyFancyPopup.hidePopup();
								return;
							}
							
						}
						else {
							var rel = getUserRelationshipObj( section.find(".relationship") );
							var sql_type = rel[0] ? rel[0].toLowerCase() : "";
							var sql = rel[1] && rel[1]["value"] ? rel[1]["value"] : "";
							
							if (sql != "" && sql_type && sql_type != selection)
								item_settings["action_type"] = sql_type;
							
							item_settings["action_value"]["sql"] = sql;
							
							if (sql == "" && !ignore_errors) {
								var label = action_type.find("option:selected").text();
								
								if (!is_from_auto_save)
									StatusMessageHandler.showError("'" + label + "' group cannot be empty!");
								
								MyFancyPopup.hidePopup();
								return;
							}
						}
					}
					
					//get footer fields
					var opts = section.find(" > footer > .opts");
					item_settings["action_value"]["options_type"] = opts.children(".options_type").val();
					
					if (item_settings["action_value"]["options_type"] == "array") {
						var aux = parseArray( opts.children(".options") );
						item_settings["action_value"]["options"] = aux["options"];
					}
					else
						item_settings["action_value"]["options"] = opts.children(".options_code").val();
					
					break;
					
				case "show_ok_msg":
				case "show_ok_msg_and_stop":
				case "show_ok_msg_and_die":
				case "show_ok_msg_and_redirect":
				case "show_error_msg":
				case "show_error_msg_and_stop":
				case "show_error_msg_and_die":
				case "show_error_msg_and_redirect":
				case "alert_msg":
				case "alert_msg_and_stop":
				case "alert_msg_and_redirect":
					var section = group_body.children(".message_action_body");
					
					item_settings["action_value"]["message"] = section.find(" > .message > input").val();
					item_settings["action_value"]["redirect_url"] = section.find(" > .redirect_url > input").val();
					break;
					
				case "redirect": //getting redirect settings
					var section = group_body.children(".redirect_action_body");
					
					item_settings["action_value"]["redirect_type"] = section.find(" > .redirect_type > select").val();
					item_settings["action_value"]["redirect_url"] = section.find(" > .redirect_url > input").val();
					break;
				
				case "return_previous_record":
				case "return_next_record":
				case "return_specific_record":
					var section = group_body.children(".records_action_body");
					
					item_settings["action_value"]["records_variable_name"] = section.find(" > .records_variable_name > input").val();
					item_settings["action_value"]["index_variable_name"] = section.find(" > .index_variable_name > input").val();
					break;
				
				case "check_logged_user_permissions":
					var section = group_body.children(".check_logged_user_permissions_action_body");
					var user_types = section.find(" > .users_perms > table > tbody .user_type_id select");
					
					item_settings["action_value"] = {
						"all_permissions_checked": section.find(" > .all_permissions_checked > input").is(":checked") ? 1 : 0,
						"entity_path_var_name": section.find(" > .entity_path_var_name").val(),
						"logged_user_id": section.find(" > .logged_user_id > input").val(),
						"users_perms": []
					};
					
					$.each(user_types, function(idx, user_type) {
						user_type = $(user_type);
						
						item_settings["action_value"]["users_perms"].push({
							"user_type_id": user_type.val(),
							"activity_id": user_type.parent().closest("tr").find(".activity_id select").val()
						});
					});
					
					break;
								
				case "code": //getting code settings
					var section = group_body.children(".code_action_body");
					var editor = section.data("editor");
					
					item_settings["action_value"] = editor ? editor.getValue() : section.children("textarea").val();
					break;
					
				case "array": //getting array settings
					var section = group_body.children(".array_action_body");
					var aux = parseArray(section);
					
					item_settings["action_value"] = aux["array_action_body"];
					break;
					
				case "string": //getting string settings
					var section = group_body.children(".string_action_body");
					
					item_settings["action_value"] = {
						"string": section.find(".string input").val(),
						"operator": section.find(".operator select").val()
					};
					break;
					
				case "variable": //getting variable settings
					var section = group_body.children(".variable_action_body");
					
					item_settings["action_value"] = {
						"variable": section.find(".variable input").val(),
						"operator": section.find(".operator select").val()
					};
					break;
					
				case "sanitize_variable": //getting variable settings
					var section = group_body.children(".sanitize_variable_action_body");
					
					item_settings["action_value"] = section.children("input").val();
					break;
					
				case "validate_variable": //getting variable settings
					var section = group_body.children(".validate_variable_action_body");
					
					item_settings["action_value"] = {
						"method": section.find(".method > select").val(),
						"variable": section.find(".variable > input").val(),
						"offset": section.find(".offset > input").val(),
					};
					break;
					
				case "list_report": //getting variable settings
					var section = group_body.children(".list_report_action_body");
					
					item_settings["action_value"] = {
						"type": section.find(".type > select").val(),
						"doc_name": section.find(".doc_name > input").val(),
						"variable": section.find(".variable > input").val(),
						"continue": section.find(".continue > select").val(),
					};
					break;
					
				case "call_block": //getting variable settings
					var section = group_body.children(".call_block_action_body");
					
					item_settings["action_value"] = {
						"block": section.find(".block > input").val(),
						"project": section.find(".project > select").val(),
					};
					break;
					
				case "call_view": //getting variable settings
					var section = group_body.children(".call_view_action_body");
					
					item_settings["action_value"] = {
						"view": section.find(".view > input").val(),
						"project": section.find(".project > select").val(),
					};
					break;
					
				case "include_file": //getting include_file settings
					var section = group_body.children(".include_file_action_body");
					
					item_settings["action_value"] = {
						"path": section.children("input.path").val(),
						"once": section.children("input.once").is(":checked") ? 1 : 0,
					};
					break;
				
				case "draw_graph": //getting draw_graph settings
					var section = group_body.children(".draw_graph_action_body");
					var is_code = section.tabs("option", "active") == 1;
					
					if (is_code) {
						var editor = section.children(".draw_graph_js_code").data("editor");
						
						item_settings["action_value"] = {
							"code": editor ? editor.getValue() : sub_section.children("textarea").val(),
						};
					}
					else {
						var sub_section = section.children(".draw_graph_settings");
						var lis = sub_section.find(".graph_data_sets > ul > li:not(.no_data_sets)");
						var data_sets = [];
						
						$.each(lis, function(idx, li) {
							li = $(li);
							
							var data_set_options = {
								"type": li.find(".type select").val(),
								"item_label": li.find(".item_label input").val(),
								"values_variable": li.find(".values_variable input").val(),
								"background_colors": li.find(".background_colors input").val(),
								"border_colors": li.find(".border_colors input").val(),
								"border_width": li.find(".border_width input").val()
							};
							
							var other_options = li.find(".other_options > ul > li");
							$.each(other_options, function(idy, other_option) {
								other_option = $(other_option);
								var option_name = other_option.find(".option_name").val();
								
								if (option_name)
									data_set_options[option_name] = other_option.find(".option_value").val();
							});
							
							data_sets.push(data_set_options);
						});
						
						item_settings["action_value"] = {
							"include_graph_library": sub_section.find(".include_graph_library select").val(),
							"width": sub_section.find(".graph_width input").val(),
							"height": sub_section.find(".graph_height input").val(),
							"labels_variable": sub_section.find(".labels_variable input").val(),
							
							"data_sets": data_sets
						};
					}
					break;
				
				case "loop": //getting loop settings
					var section = group_body.children(".loop_action_body");
					var header = section.children("header");
					var loop_items = section.find(" > .sla_sub_groups > .sla_group_item");
					
					item_settings["action_value"] = {
						"records_variable_name": header.find(".records_variable_name input").val(),
						"records_start_index": header.find(".records_start_index input").val(),
						"records_end_index": header.find(".records_end_index input").val(),
						"array_item_key_variable_name": header.find(".array_item_key_variable_name input").val(),
						"array_item_value_variable_name": header.find(".array_item_value_variable_name input").val(),
						"actions": getSLASettingsFromItemsToSave(loop_items),
					};
					break;
					
				case "group": //getting group settings
					var section = group_body.children(".group_action_body");
					var header = section.children("header");
					var group_items = section.find(" > .sla_sub_groups > .sla_group_item");
					
					item_settings["action_value"] = {
						"group_name": header.find(".group_name input").val(),
						"actions": getSLASettingsFromItemsToSave(group_items),
					};
					break;
			}
		}
		
		actions_settings.push(item_settings);
	});
	
	return actions_settings;
}

function getBrokerSettings(elm, brokers_layer_type) {
	var settings = {};
	
	switch(brokers_layer_type) {
		case "callbusinesslogic":
			var task_html_elm = elm.children(".call_business_logic_task_html");
			
			if (task_html_elm[0]) {
				settings["method_obj"] = prepareMethodObj( task_html_elm.find(".broker_method_obj input").val() );
				settings["module_id"] = task_html_elm.find(".module_id input").val();
				settings["module_id_type"] = task_html_elm.find(".module_id select").val();
				settings["service_id"] = task_html_elm.find(".service_id input").val();
				settings["service_id_type"] = task_html_elm.find(".service_id select").val();
				
				var params = task_html_elm.children(".params");
				settings["parameters_type"] = params.children(".parameters_type").val();
				if (settings["parameters_type"] == "array") {
					var aux = parseArray( params.children(".parameters") );
					settings["parameters"] = aux["parameters"];
				}
				else {
					settings["parameters"] = params.children(".parameters_code").val();
				}
		
				var opts = task_html_elm.children(".opts");
				settings["options_type"] = opts.children(".options_type").val();
				if (settings["options_type"] == "array") {
					var aux = parseArray( opts.children(".options") );
					settings["options"] = aux["options"];
				}
				else {
					settings["options"] = opts.children(".options_code").val();
				}
			}
				
			break;
		case "callibatisquery":
			var task_html_elm = elm.children(".call_ibatis_query_task_html");
			
			if (task_html_elm[0]) {
				settings["method_obj"] = prepareMethodObj( task_html_elm.find(".broker_method_obj input").val() );
				settings["module_id"] = task_html_elm.find(".module_id input").val();
				settings["module_id_type"] = task_html_elm.find(".module_id select").val();
				settings["service_id"] = task_html_elm.find(".service_id input").val();
				settings["service_id_type"] = task_html_elm.find(".service_id select").val();
				
				var service_type = task_html_elm.children(".service_type");
				settings["service_type_type"] = service_type.children("select.service_type_type").val();
				if (settings["service_type_type"] == "string") {
					settings["service_type"] = service_type.children("select.service_type_string").val();
				}
				else {
					settings["service_type"] = service_type.children("input.service_type_code").val();
				}
				
				var params = task_html_elm.children(".params");
				settings["parameters_type"] = params.children(".parameters_type").val();
				if (settings["parameters_type"] == "array") {
					var aux = parseArray( params.children(".parameters") );
					settings["parameters"] = aux["parameters"];
				}
				else {
					settings["parameters"] = params.children(".parameters_code").val();
				}
		
				var opts = task_html_elm.children(".opts");
				settings["options_type"] = opts.children(".options_type").val();
				if (settings["options_type"] == "array") {
					var aux = parseArray( opts.children(".options") );
					settings["options"] = aux["options"];
				}
				else {
					settings["options"] = opts.children(".options_code").val();
				}
			}
			
			break;
		case "callhibernatemethod":
			var task_html_elm = elm.children(".call_hibernate_method_task_html");
			
			if (task_html_elm[0]) {
				settings["broker_method_obj_type"] = task_html_elm.find(".broker_method_obj select").val();
				settings["method_obj"] = prepareMethodObj( task_html_elm.find(".broker_method_obj input").val() );
				settings["module_id"] = task_html_elm.find(".module_id input").val();
				settings["module_id_type"] = task_html_elm.find(".module_id select").val();
				settings["service_id"] = task_html_elm.find(".service_id input").val();
				settings["service_id_type"] = task_html_elm.find(".service_id select").val();
				
				var opts = task_html_elm.children(".opts");
				settings["options_type"] = opts.children(".options_type").val();
				if (settings["options_type"] == "array") {
					var aux = parseArray( opts.children(".options") );
					settings["options"] = aux["options"];
				}
				else {
					settings["options"] = opts.children(".options_code").val();
				}
				
				var service_method = task_html_elm.children(".service_method");
				settings["service_method_type"] = service_method.children("select.service_method_type").val();
				if (settings["service_method_type"] == "string") {
					settings["service_method"] = service_method.children("select.service_method_string").val();
				}
				else {
					settings["service_method"] = service_method.children("input.service_method_code").val();
				}
				
				var service_method_args = task_html_elm.children(".service_method_args");
				
				var sma = service_method_args.children(".sma_query_type");
				settings["sma_query_type_type"] = sma.children("select.service_method_arg_type").val();
				if (settings["sma_query_type_type"] == "string") {
					settings["sma_query_type"] = sma.children("select.sma_query_type_string").val();
				}
				else {
					settings["sma_query_type"] = sma.children("input").val();
				}
				
				settings["sma_query_id"] = service_method_args.find(".sma_query_id input").val();
				settings["sma_query_id_type"] = service_method_args.find(".sma_query_id select").val();
				settings["sma_function_name"] = service_method_args.find(".sma_function_name input").val();
				settings["sma_function_name_type"] = service_method_args.find(".sma_function_name select").val();
				
				var sma = service_method_args.children(".sma_data");
				settings["sma_data_type"] = sma.children("select").val();
				if (settings["sma_data_type"] == "array") {
					var aux = parseArray( sma.children(".array_items") );
					settings["sma_data"] = aux["sma_data"];
				}
				else {
					settings["sma_data"] = sma.children("input").val();
				}
				
				settings["sma_statuses"] = service_method_args.find(".sma_statuses input").val();
				settings["sma_statuses_type"] = "variable";
				settings["sma_ids"] = service_method_args.find(".sma_ids input").val();
				settings["sma_ids_type"] = "variable";
				settings["sma_rel_name"] = service_method_args.find(".sma_rel_name input").val();
				settings["sma_rel_name_type"] = service_method_args.find(".sma_rel_name select").val();
				
				var sma = service_method_args.children(".sma_parent_ids");
				settings["sma_parent_ids_type"] = sma.children("select").val();
				if (settings["sma_parent_ids_type"] == "array") {
					var aux = parseArray( sma.children(".array_items") );
					settings["sma_parent_ids"] = aux["sma_parent_ids"];
				}
				else {
					settings["sma_parent_ids"] = sma.children("input").val();
				}
				
				var sma = service_method_args.children(".sma_sql");
				settings["sma_sql_type"] = sma.children("select").val();
				if (settings["sma_sql_type"] == "string") {
					var editor = sma.data("editor");
					settings["sma_sql"] = editor ? editor.getValue() : sma.children("textarea.sql_editor").val();
				}
				else {
					settings["sma_sql"] = sma.children("input").val();
				}
				
				var sma = service_method_args.children(".sma_options");
				settings["sma_options_type"] = sma.children("select").val()
				if (settings["sma_options_type"] == "array") {
					var items = sma.children(".array_items").find(".task_property_field");
					var bkp_items = [];
					for (var i = 0; i < items.length; i++) {
						var item = $(items[i]);
						var name = item.attr("name");
						
						item.attr("name", "sma_" + name);
						bkp_items.push([ item[0], name ]);
					}
					
					var aux = parseArray( sma.children(".array_items") );
					settings["sma_options"] = aux["sma_options"];
					
					for (var i = 0; i < bkp_items.length; i++) {
						$(bkp_items[i][0]).attr("name", bkp_items[i][1]);
					}
				}
				else {
					settings["sma_options"] = sma.children("input").val();
				}
			}
			
			break;
		case "getquerydata":
		case "setquerydata":
			var task_html_elm = elm.children(brokers_layer_type == "getquerydata" ? ".get_query_data_task_html" : ".set_query_data_task_html");
				
			if (task_html_elm[0]) {
				settings["method_obj"] = prepareMethodObj( task_html_elm.find(".broker_method_obj input").val() );
				
				var sql = task_html_elm.children(".sql");
				settings["sql_type"] = sql.children("select").val();
				if (settings["sql_type"] == "string") {
					var editor = sql.data("editor");
					settings["sql"] = editor ? editor.getValue() : sql.children("textarea.sql_editor").val();
				}
				else {
					settings["sql"] = sql.children("input.sql_variable").val();
				}
				
				var opts = task_html_elm.children(".opts");
				settings["options_type"] = opts.children(".options_type").val();
				if (settings["options_type"] == "array") {
					var aux = parseArray( opts.children(".options") );
					settings["options"] = aux["options"];
				}
				else {
					settings["options"] = opts.children(".options_code").val();
				}
			}
			
			break;
		case "callfunction":
			var task_html_elm = elm.children(".call_function_task_html");
			
			settings["func_name"] = task_html_elm.find(".func_name input").val();
			settings["func_args"] = parseArgs(task_html_elm.children(".func_args"), "func_args");
			
			settings["include_file_path"] = task_html_elm.find(".include_file input[type=text]").val();
			settings["include_file_path_type"] = task_html_elm.find(".include_file select").val();
			settings["include_once"] = task_html_elm.find(".include_file input[type=checkbox]").is(":checked") ? 1 : 0;
			
			break;
		case "callobjectmethod":
			var task_html_elm = elm.children(".call_object_method_task_html");
			
			settings["method_obj"] = task_html_elm.find(".method_obj_name input").val();
			settings["method_name"] = task_html_elm.find(".method_name input").val();
			settings["method_static"] = task_html_elm.find(".method_static input:checked").val();
			settings["method_args"] = parseArgs(task_html_elm.children(".method_args"), "method_args");
			
			settings["include_file_path"] = task_html_elm.find(".include_file input[type=text]").val();
			settings["include_file_path_type"] = task_html_elm.find(".include_file select").val();
			settings["include_once"] = task_html_elm.find(".include_file input[type=checkbox]").is(":checked") ? 1 : 0
			
			break;
		case "restconnector":
			var task_html_elm = elm.children(".get_url_contents_task_html");
			
			var dts = task_html_elm.children(".dts");
			settings["data_type"] = dts.children(".data_type").val();
			if (settings["data_type"] == "array") {
				var aux = parseArray( dts.children(".data") );
				settings["data"] = aux["data"];
			}
			else {
				settings["data"] = dts.children(".data_code").val();
			}
			
			settings["result_type_type"] = task_html_elm.find(".result_type > select[name=result_type_type]").val();
			settings["result_type"] = settings["result_type_type"] == "options" ? task_html_elm.find(".result_type > select[name=result_type]").val() : task_html_elm.find(".result_type > input").val();
			
			break;
		case "soapconnector":
			var task_html_elm = elm.children(".soap_connector_task_html");
			var is_call_soap_client = false;
			
			settings["data_type"] = task_html_elm.find(".data > select").val();
			if (settings["data_type"] != "options") 
				settings["data"] = task_html_elm.find(".data > input").val();
			else {
				settings["data"] = {};
				
				settings["data"]["type_type"] = task_html_elm.find(".type > select.type_type").val();
				settings["data"]["type"] = settings["data"]["type_type"] == "options" ? task_html_elm.find(".type > select.type_options").val() : task_html_elm.find(".type > input.type_code").val();
				
				if (settings["data"]["type"] == "callSoapClient")
					is_call_soap_client = true;
				
				settings["data"]["wsdl_url_type"] = task_html_elm.find(".wsdl_url > select").val();
				settings["data"]["wsdl_url"] = task_html_elm.find(".wsdl_url > input").val();
				
				settings["data"]["options_type"] = task_html_elm.find(".client_options > select").val();
				if (settings["data"]["options_type"] == "options") {
					 var aux = parseArray( task_html_elm.find(".client_options > table > tbody") );
					 settings["data"]["options"] = aux && aux["data"] && aux["data"]["options"] ? aux["data"]["options"] : {};
				}
				else
					settings["data"]["options"] = task_html_elm.find(".client_options > input").val();
				
				settings["data"]["headers_type"] = task_html_elm.find(".client_headers > select").val();
				if (settings["data"]["headers_type"] == "options") {
					settings["data"]["headers"] = [];
					
					var lis = task_html_elm.find(".client_headers > ul > li");
					$.each(lis, function(idx, li) {
						li = $(li);
						
						var must_understand_type = li.find(".client_header_must_understand > select").val();
						var must_understand = li.find(".client_header_must_understand > input[type=text]").val();
						
						if (must_understand_type == "options") {
							var checkbox = li.find(".client_header_must_understand > input[type=checkbox]");
							must_understand = checkbox.is(":checked") ? checkbox.val() : "";
						}
						
						var parameters_type = li.find(".client_header_parameters > select").val();
						var parameters = li.find(".client_header_parameters > input.parameters_code").val();
						
						if (parameters_type == "array") {
							var aux = parseArray( li.find(".client_header_parameters > .parameters") );
							
							if (aux && aux["data"] && aux["data"]["headers"]) {
								for (var idx in aux["data"]["headers"])
									break;
								
								parameters = $.isNumeric(idx) && aux["data"]["headers"][idx] && aux["data"]["headers"][idx]["parameters"] ? aux["data"]["headers"][idx]["parameters"] : {};
							}
						}
						
						var header = {
							namespace: li.find(".client_header_namespace > input").val(),
							namespace_type: li.find(".client_header_namespace > select").val(),
							name: li.find(".client_header_name > input").val(),
							name_type: li.find(".client_header_name > select").val(),
							must_understand: must_understand,
							must_understand_type: must_understand_type,
							actor: li.find(".client_header_actor > input").val(),
							actor_type: li.find(".client_header_actor > select").val(),
							parameters: parameters,
							parameters_type: parameters_type,
						};
						
						settings["data"]["headers"].push(header);
					});
				}
				else
					settings["data"]["headers"] = task_html_elm.find(".client_headers > input").val();
				
				if (!is_call_soap_client) {
					settings["data"]["remote_function_name_type"] = task_html_elm.find(".remote_function_name > select").val();
					settings["data"]["remote_function_name"] = task_html_elm.find(".remote_function_name > input").val();
					
					var rfa = task_html_elm.children(".remote_function_arguments");
					settings["data"]["remote_function_args_type"] = rfa.children("select.remote_function_args_type").val();
					if (settings["data"]["remote_function_args_type"] == "array") {
						var aux = parseArray( rfa.children(".remote_function_args") );
						settings["data"]["remote_function_args"] = aux && aux["data"] && aux["data"]["remote_function_args"] ? aux["data"]["remote_function_args"] : {};
					}
					else
						settings["data"]["remote_function_args"] = rfa.children("input.remote_function_args_code").val();
				}
			}
			
			if (!is_call_soap_client) {
				settings["result_type_type"] = task_html_elm.find(".result_type > select[name=result_type_type]").val();
				settings["result_type"] = settings["result_type_type"] == "options" ? task_html_elm.find(".result_type > select[name=result_type]").val() : task_html_elm.find(".result_type > input").val();
			}
			
			break;
	}
	
	return settings;
}

function parseArgs(html_elm, attr_name) {
	var aux = parseArray( html_elm.children(".args") );
	var args = [];
	
	if (aux && aux[attr_name])
		$.each(aux[attr_name], function(idx, arg) {
			var item = {
				"childs" : {
					"value" : [ {"value" : arg["value"]} ],
					"type" : [ {"value" : arg["type"]} ],
				}
			};
		
			args.push(item);
		});
	
	return args;
}

function parseArray(html_elm) {
	var query_string = taskFlowChartObj.Property.getPropertiesQueryStringFromHtmlElm(html_elm[0], "task_property_field");
	var settings = {};
	parse_str(query_string, settings);
	
	return settings;
}

function prepareMethodObj(method_obj) {
	method_obj = "" + method_obj;
	var static_pos = method_obj.indexOf("::");
	var non_static_pos = method_obj.indexOf("->");
	
	method_obj = method_obj.substr(0, 1) != '$' && (static_pos == -1 || (non_static_pos != -1 && static_pos > non_static_pos)) ? '$' + method_obj : method_obj;
	
	return method_obj;
}

function getSLASettings(sla) {
	var status = true;
	var task_flow_tab_openned_by_user = sla.find("#tasks_flow_tab a").attr("is_init");
	
	if (task_flow_tab_openned_by_user && !is_from_auto_save) { //only if not auto save action, otherwise ignore it and only save the properties.
		var selected_tab = sla.children("ul").find("li.ui-tabs-selected, li.ui-tabs-active").first();
		
		if (selected_tab.attr("id") != "tasks_flow_tab") {
			sla.find(" > ul #tasks_flow_tab a").first().click();
			updateTasksFlow();
		}
		
		status = taskFlowChartObj.TaskFile.save(null, {overwrite: true, silent: true});
	
		if (status && confirm("Do you wish to generate new Groups based in the Workflow tab, before you save?\nIf you click the cancel button, the system will discard the changes in the Workflow tab and give preference to the Groups tab."))
			status = generateSLAGroupsFromTasksFlow(true);
		
		if (selected_tab.attr("id") != "tasks_flow_tab")
			selected_tab.children("a").click();
	}
	
	return status ? getSLASettingsFromGroups(sla) : null;
}

function getSLASettingsFromGroups(sla) {
	var items = sla.find(".sla_groups_flow > .sla_groups > .sla_group_item:not(.sla_group_default)");
	return getSLASettingsFromItemsToSave(items);
}

function onClickSLAGroupsFlowTab(elm) {
	update_sla_programming_task_variables_from_sla_groups = true;
	update_sla_programming_task_variables_from_workflow = false;
}

function onClickSLATaskWorkflowTab(elm) {
	update_sla_programming_task_variables_from_sla_groups = false;
	update_sla_programming_task_variables_from_workflow = true;
	
	onClickTaskWorkflowTab(elm);
}

function generateSLAGroupsFromTasksFlow(do_not_confirm) {
	var status = true;
	
	if (do_not_confirm || confirm("Do you wish to update Groups accordingly with the workflow tasks?")) {
		status = false;
		
		MyFancyPopup.init({
			parentElement: window,
		});
		MyFancyPopup.showOverlay();
		MyFancyPopup.showLoading();
		var workflow_menu = $(".workflow_menu");
		workflow_menu.hide();
		
		var save_options = {
			overwrite: true,
			success: function(data, textStatus, jqXHR) {
				if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
					showAjaxLoginPopup(jquery_native_xhr_object.responseURL, set_tmp_workflow_file_url, function() {
						taskFlowChartObj.StatusMessage.removeLastShownMessage("error");
						StatusMessageHandler.removeLastShownMessage("error");
						generateSLAGroupsFromTasksFlow(true);
					});
			},
		};
		
		if (taskFlowChartObj.TaskFile.save(set_tmp_workflow_file_url, save_options)) {
			$.ajax({
				type : "get",
				url : create_sla_settings_from_workflow_file_url,
				dataType : "json",
				success : function(data, textStatus, jqXHR) {
					if (data && data.hasOwnProperty("actions")) {
						//console.log(data);
						var actions_settings = data["actions"];
						var sla = $(".sla");
						
						//create group settings
						if (actions_settings) {
							var add_group_icon = sla.find(".sla_groups_flow > nav > .add_sla_group")[0];
							
							//remove old groups
							sla.find(".sla_groups_flow > .sla_groups > .sla_group_item").not(".sla_group_default").remove();
							
							//load new groups
							loadSLASettingsActions(add_group_icon, actions_settings, false, false);
						}
						
						if (data["error"] && data["error"]["infinit_loop"] && data["error"]["infinit_loop"][0]) {
							var loops = data["error"]["infinit_loop"];
							
							var msg = "";
							for (var i = 0; i < loops.length; i++) {
								var loop = loops[i];
								var slabel = taskFlowChartObj.TaskFlow.getTaskLabelByTaskId(loop["source_task_id"]);
								var tlabel = taskFlowChartObj.TaskFlow.getTaskLabelByTaskId(loop["target_task_id"]);
								
								msg += (i > 0 ? "\n" : "") + "- '" + slabel + "' => '" + tlabel + "'";
							}
							
							msg = "The system detected the following invalid loops and discarded them from the Groups settings:\n" + msg + "\n\nYou should remove them from the workflow and apply the correct 'loop task' for doing loops.";
							taskFlowChartObj.StatusMessage.showError(msg);
							alert(msg);
						}
						else {
							sla.find("#sla_groups_flow_tab a").first().click();
							status = true;
						}
					}
					else 
						taskFlowChartObj.StatusMessage.showError("There was an error trying to update Groups. Please try again.");
					
					MyFancyPopup.hidePopup();
					workflow_menu.show();
				},
				error : function() { 
					if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
						showAjaxLoginPopup(jquery_native_xhr_object.responseURL, create_sla_settings_from_workflow_file_url, function() {
							generateSLAGroupsFromTasksFlow(true);
						});
					else {
						taskFlowChartObj.StatusMessage.showError("There was an error trying to update Groups. Please try again.");
				
						MyFancyPopup.hidePopup();
						workflow_menu.show();
					}
				},
				async : false,
			});
		}
		else 
			taskFlowChartObj.StatusMessage.showError("There was an error trying to update Groups. Please try again.");
	}
	
	return status;
}

function generateSLATasksFlowFromGroups(do_not_confirm) {
	var status = true;
	
	if (do_not_confirm || confirm("Do you wish to update this workflow accordingly with the settings in the Groups Tab?")) {
		status = false;
		
		var sla = $(".sla");
		
		taskFlowChartObj.getMyFancyPopupObj().hidePopup();
		MyFancyPopup.init({
			parentElement: window,
		});
		MyFancyPopup.showOverlay();
		MyFancyPopup.showLoading();
		sla.find(".workflow_menu").hide();
		
		var actions_settings = getSLASettingsFromGroups(sla);
		
		$.ajax({
			type : "post",
			url : create_sla_workflow_file_from_settings_url,
			data : {"actions": actions_settings},
			dataType : "text",
			success : function(data, textStatus, jqXHR) {
				if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
					showAjaxLoginPopup(jquery_native_xhr_object.responseURL, create_sla_workflow_file_from_settings_url, function() {
						generateSLATasksFlowFromGroups(true);
					});
				else if (data == 1) {
					var previous_callback = taskFlowChartObj.TaskFile.on_success_read;
					
					taskFlowChartObj.TaskFile.on_success_read = function(data, text_status, jqXHR) {
						if (!data)
							taskFlowChartObj.StatusMessage.showError("There was an error trying to load the workflow's tasks.");
						else {
							taskFlowChartObj.TaskSort.sortTasks();
							status = true;
						}
						
						taskFlowChartObj.TaskFile.on_success_read = previous_callback;
					}
				
					taskFlowChartObj.TaskFile.reload(get_tmp_workflow_file_url, {"async": true});
				}
				else
					taskFlowChartObj.StatusMessage.showError("There was an error trying to update this workflow. Please try again.");
				
				MyFancyPopup.hidePopup();
				sla.find(".workflow_menu").show();
			},
			error : function() { 
				taskFlowChartObj.StatusMessage.showError("There was an error trying to update this workflow. Please try again.");
			
				MyFancyPopup.hidePopup();
				sla.find(".workflow_menu").show();
			},
			async : false,
		});
	}
	
	return status;
}

function getSLASettingsCode() {
	var code = "";
	var status = true;
	
	var sla = $(".sla");
	var actions_settings = getSLASettings(sla);
	//console.log(actions_settings);
	
	if (!$.isArray(actions_settings))
		status = false;
	else {
		var new_sla_action_settings_id = $.md5(JSON.stringify(actions_settings));
		
		if (new_sla_action_settings_id == saved_sla_action_settings_id && saved_sla_action_settings_code)
			code = saved_sla_action_settings_code;
		else 
			$.ajax({
				type : "post",
				url : create_sla_settings_code_url,
				data : {"actions" : actions_settings},
				dataType : "json",
				success : function(data, textStatus, jqXHR) {
					if (data && data["code"]) {
						code = data["code"];
						
						saved_sla_action_settings_code = code;
						saved_sla_action_settings_id = new_sla_action_settings_id;
					}
					else if (!is_from_auto_save) {
						status = false;
						StatusMessageHandler.showError("Error trying to create settings code.\nPlease try again...");
					}
				},
				error : function(jqXHR, textStatus, errorThrown) {
					status = false;
					
					if (jqXHR.responseText && !is_from_auto_save) {	
						if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
							StatusMessageHandler.showError("Please Login first!");
						else 
							StatusMessageHandler.showError("Error trying to create settings code.\nPlease try again...");
					}
				},
				async : false,
			});
	}
	
	return {code: code, status: status};
}

function convertSLASettingsToPHPCode() {
	var code = "";
	var status = true;
	
	var sla = $(".sla");
	var actions_settings = getSLASettings(sla);
	//console.log(actions_settings);
	
	if (!$.isArray(actions_settings))
		status = false;
	else {
		var new_sla_action_settings_id = $.md5(JSON.stringify(actions_settings));
		
		if (new_sla_action_settings_id == saved_sla_action_settings_id && saved_sla_action_settings_php_code)
			code = saved_sla_action_settings_php_code;
		else 
			$.ajax({
				type : "post",
				url : convert_sla_settings_to_php_code_url,
				data : {"actions" : actions_settings},
				dataType : "json",
				success : function(data, textStatus, jqXHR) {
					if (data && data["code"]) {
						code = data["code"];
						
						saved_sla_action_settings_php_code = code;
						saved_sla_action_settings_id = new_sla_action_settings_id;
					}
					else if (!is_from_auto_save) {
						status = false;
						StatusMessageHandler.showError("Error trying to convert settings into code.\nPlease try again...");
					}
				},
				error : function(jqXHR, textStatus, errorThrown) {
					status = false;
					
					if (jqXHR.responseText && !is_from_auto_save) {	
						if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
							StatusMessageHandler.showError("Please Login first!");
						else 
							StatusMessageHandler.showError("Error trying to convert settings into code.\nPlease try again...");
					}
				},
				async : false,
			});
	}
	
	return {code: code, status: status};
}

function createSLAItemCodeEditor(textarea, mode, save_func) {
	if (textarea) {
		var parent = $(textarea).parent();
	
		ace.require("ace/ext/language_tools");
		var editor = ace.edit(textarea);
		editor.setTheme("ace/theme/chrome");
		editor.session.setMode("ace/mode/" + mode);
		//editor.setAutoScrollEditorIntoView(false);
		editor.setOption("minLines", 10);
		editor.setOptions({
			enableBasicAutocompletion: true,
			enableSnippets: true,
			enableLiveAutocompletion: true,
		});
		editor.setOption("wrap", true);
		
		if (typeof setCodeEditorAutoCompleter == "function")
			setCodeEditorAutoCompleter(editor);
		
		if (typeof save_func == "function" || save_func) {
			editor.commands.addCommand({
				name: 'saveFile',
				bindKey: {
					win: 'Ctrl-S',
					mac: 'Command-S',
					sender: 'editor|cli'
				},
				exec: function(env, args, request) {
					var button = $(".top_bar .save a").first();
					
					if (typeof save_func == "function")
						save_func(button[0]);
					else if (save_func)
						button.trigger("click");
				},
			});
		}
	
		parent.data("editor", editor);
		
		parent.find("textarea.ace_text-input").removeClass("ace_text-input"); //fixing problem with scroll up, where when focused or pressed key inside editor the page scrolls to top.
	
		return editor;
	}
	
	return null;
}
