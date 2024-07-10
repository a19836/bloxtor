function onChangeDBDriver(elm) {
	elm = $(elm);
	var selected_db_driver = elm.val();
	var p = elm.parent();
	var opt = elm.find("option:selected");
	
	p.children("input[name='db_layer_file']").val( opt.attr("bean_file_name") );
	p.children("input[name='db_layer']").val( opt.attr("bean_name") );
	
	if (default_db_driver && selected_db_driver == default_db_driver) 
		p.parent().find(" > .include_db_driver > input").removeAttr("checked").prop("checked", false);
	else
		p.parent().find(" > .include_db_driver > input").attr("checked", "checked").prop("checked", true);
}

function onLoadTableSettings() {
	choosePropertyVariableFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeObjectPropertiesAndMethodsAndFunctionsFromTreeForVariables,
	});
	choosePropertyVariableFromFileManagerTree.init("choose_property_variable_from_file_manager .class_prop_var");
	
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
	
	loadTableGroups();
	
	MyFancyPopup.hidePopup();
}

function openUsersManagementAdminPanelPopup(elm) {
	elm = $(elm);
	var popup_elm = elm.parent().parent().children(".users_management_admin_panel_popup");
	var iframe = popup_elm.children("iframe");
	var url = !iframe.attr("src") ? users_management_admin_panel_url : null;
	
	MyFancyPopup.init({
		elementToShow: popup_elm,
		type: "iframe",
		url: url,
	});
	MyFancyPopup.showPopup();
}

function addTableAlias(elm) {
	elm = $(elm);
	var clicked = elm.attr("clicked");
	elm.attr("clicked", "1");
	
	var p = elm.parent();
	var table_name = p.children("input[type='checkbox']").val();
	var table_alias = p.children("input[type='hidden']").val();
	
	var alias = prompt("Please enter the new table alias:", clicked && table_alias ? table_alias : table_name);
	
	if (typeof alias == "string") {
		alias = alias.replace(/ /g, "");
		
		if (alias == table_name)
			alias = "";

		p.children("input[type='hidden']").val(alias);

		if (alias)
			elm.html(table_name + " => " + alias);
		else
			elm.html(table_name);
	}
}

function loadTableGroups() {
	var table_groups = $(".table_group");
	var maximum_call_stack_requests = 5; //This means that the system will do around 12 requests per time.
	var count = 0;
	
	alert("The system will now start initializing the correspondent tables and relationships.\nPlease wait, do not change this browser tab and click in the OK buttons from the next popups in order to everything runs perfectly...");
	
	$.each(table_groups, function (idx, elm) {
		elm = $(elm);
		var table_name = elm.attr("table_name");
		var table_ui_panels = elm.find(".table_ui_panel");
		
		$.each(table_ui_panels, function (idx, elm) {
			count++;
			
			if (!force_user_action || (count >= maximum_call_stack_requests && confirm("Please press the OK button to continue...\n\nThis message will appear several times. Please click always on the OK button to complete this process!")))
				count = 1;
			
			if (count < maximum_call_stack_requests) {
				elm = $(elm);
				var type = elm.attr("type");
				var relationship_table = elm.attr("relationship_table");
				
				elm.children(".brokers_layer_type").children("select").each(function (idx, elm) {
					onChangeBrokersLayerType(elm);
				});
				
				elm.children(".task_properties").children("div").each(function (idx, elm) {
					loadTask(elm, table_name, type, relationship_table);
					
					if ($(elm).hasClass("get_query_data_task_html"))
						$(elm).find(".opts").append('<div class="info">The system will automatically add the "return_type" option with the value: "result".</div>');
				});
				
				elm.children(".brokers_layer_type").children("select").each(function (idx, elm) {
					showCorrectLoadedTask(elm, table_name, type);
				});
			}
			else 
				return false;
		});
		
		if (count >= maximum_call_stack_requests)
			return false;
	});
	
	if (count >= maximum_call_stack_requests)
		alert("Error: System couldn't finish to inited all the interface. Please go back or refresh this page.");
}

function showCorrectLoadedTask(brokers_layer_type_elm, table_name, type) {
	var tn = table_name.toLowerCase();
	
	if (tables_ui_props && tables_ui_props.hasOwnProperty(tn) && !$.isEmptyObject(brokers_props)) {
		brokers_layer_type_elm = $(brokers_layer_type_elm);
		var brokers_layer_type = "";
		
		for (var key in brokers_props) {
			var layer_broker_name = brokers_props[key];
			
			if (tables_ui_props[tn].hasOwnProperty(layer_broker_name) && tables_ui_props[tn][layer_broker_name].hasOwnProperty(type) && tables_ui_props[tn][layer_broker_name][type]) {
				if (key == "business_logic_broker_name")
					brokers_layer_type = "callbusinesslogic";
				else if (key == "ibatis_broker_name")
					brokers_layer_type = "callibatisquery";
				else if (key == "hibernate_broker_name")
					brokers_layer_type = "callhibernatemethod";
				else if (key == "db_broker_name")
					brokers_layer_type = brokers_layer_type_elm.find("option[value=getquerydata]").length > 0 ? "getquerydata" : "setquerydata";
				
				break;
			}
		}
		
		if (brokers_layer_type != brokers_layer_type_elm.val()) {
			brokers_layer_type_elm.val(brokers_layer_type);
			onChangeBrokersLayerType( brokers_layer_type_elm[0] );
		}
	}
}

function loadTask(elm, table_name, type, relationship_table) {
	elm = $(elm);
	var func = null;
	var brokers = null;
	
	if (elm.hasClass("call_business_logic_task_html")) {
		func = js_load_functions["callbusinesslogic"];
		brokers = CallBusinessLogicTaskPropertyObj.brokers_options;
	}
	else if (elm.hasClass("call_ibatis_query_task_html")) {
		func = js_load_functions["callibatisquery"];
		brokers = CallIbatisQueryTaskPropertyObj.brokers_options;
	}
	else if (elm.hasClass("call_hibernate_method_task_html")) {
		func = js_load_functions["callhibernatemethod"];
		brokers = CallHibernateMethodTaskPropertyObj.brokers_options;
	}
	else if (elm.hasClass("get_query_data_task_html")) { //Note that Presentation Layer can be directly connected with the DB Layer.
		func = js_load_functions["getquerydata"];
		brokers = GetQueryDataTaskPropertyObj.brokers_options;
	}
	else if (elm.hasClass("set_query_data_task_html")) { //Note that Presentation Layer can be directly connected with the DB Layer.
		func = js_load_functions["setquerydata"];
		brokers = SetQueryDataTaskPropertyObj.brokers_options;
	}

	if (func) {
		//PREPARING PROPS
		var props = null;
		var tn = table_name.toLowerCase();
		
		var default_broker = brokers ? BrokerOptionsUtilObj.getDefaultBroker(brokers) : null;
		var matches = default_broker ? default_broker.match(/\(([^\(\)]+)\)/g) : [];
		default_broker = matches && matches[0] ? matches[0].replace(/[\(\)"]+/g, "") : default_broker;
		
		if (table_name && default_broker && type) {
			type = type.toLowerCase();
			default_broker = default_broker.toLowerCase();
			
			props = tables_ui_props && tables_ui_props.hasOwnProperty(tn) && tables_ui_props[tn].hasOwnProperty(default_broker) && tables_ui_props[tn][default_broker].hasOwnProperty(type) ? tables_ui_props[tn][default_broker][type] : null;
			
			if (props && relationship_table && (type == "relationships" || type == "relationships_count")) {
				var rtn = relationship_table.toLowerCase();
				props = props.hasOwnProperty(rtn) ? props[rtn] : null;
			}
		}
		
		props = props ? props : {};
		
		if ($.isEmptyObject(props)) {
			var error_class = "warning";
			
			if (tables_ui_props[tn] && !$.isEmptyObject(brokers_props)) {
				for (var key in brokers_props) {
					var layer_broker_name = brokers_props[key];
					
					if (tables_ui_props[tn].hasOwnProperty(layer_broker_name) && tables_ui_props[tn][layer_broker_name].hasOwnProperty(type) && tables_ui_props[tn][layer_broker_name][type]) {
						if (relationship_table && (type == "relationships" || type == "relationships_count")) {
							var rtn = relationship_table.toLowerCase();
							
							if (tables_ui_props[tn][layer_broker_name][type].hasOwnProperty(rtn))
								error_class = "info";
						}
						else
							error_class = "info";
						
						break;
					}
				}
				
				/*var business_logic_broker_name = brokers_props.hasOwnProperty("business_logic_broker_name") ? brokers_props["business_logic_broker_name"] : "";
				var ibatis_broker_name = brokers_props.hasOwnProperty("ibatis_broker_name") ? brokers_props["ibatis_broker_name"] : "";
				var hibernate_broker_name = brokers_props.hasOwnProperty("hibernate_broker_name") ? brokers_props["hibernate_broker_name"] : "";
				var db_broker_name = brokers_props.hasOwnProperty("db_broker_name") ? brokers_props["db_broker_name"] : "";
				
				switch (default_broker) {
					case business_logic_broker_name: 
						error_class = "warning"; 
						break;
					case ibatis_broker_name: 
						error_class = tables_ui_props[tn].hasOwnProperty(business_logic_broker_name) && tables_ui_props[tn][business_logic_broker_name].hasOwnProperty(type) ? "info" : "warning"; 
						break;
					case hibernate_broker_name: 
						error_class = (tables_ui_props[tn].hasOwnProperty(business_logic_broker_name) && tables_ui_props[tn][business_logic_broker_name].hasOwnProperty(type)) || (tables_ui_props[tn].hasOwnProperty(ibatis_broker_name) && tables_ui_props[tn][ibatis_broker_name].hasOwnProperty(type)) ? "info" : "warning"; 
						break;
					case db_broker_name:  
						error_class = (tables_ui_props[tn].hasOwnProperty(business_logic_broker_name) && tables_ui_props[tn][business_logic_broker_name].hasOwnProperty(type)) || (tables_ui_props[tn].hasOwnProperty(ibatis_broker_name) && tables_ui_props[tn][ibatis_broker_name].hasOwnProperty(type)) || (tables_ui_props[tn].hasOwnProperty(hibernate_broker_name) && tables_ui_props[tn][hibernate_broker_name].hasOwnProperty(type)) ? "info" : "warning"; 
						break;
				}*/
			}
			
			var msg = "<li class=\"" + error_class + "\">Table: '" + table_name + "'; Type: '" + type + "'; Broker: '" + default_broker + "'" + (relationship_table ? "; Foreign Table: '" + relationship_table + "'" : "") + "</li>";
			var error = $(".tables_settings").children(".error");
			error.children("ul").append(msg);
			error.show();
		}
		
		//console.log(table_name+":"+default_broker+":"+type);
		//console.log(props);
		
		//INIT TASK
		taskFlowChartObj.Property.setPropertiesFromHtmlElm(elm.parent(), "task_property_field", props);
		eval (func + "(elm.parent(), null, props);");
		
		//PREPARING PARAMS
		if (!$.isEmptyObject(props) && type != "get_all" && type != "count") {//Do nothing if type == get_all || type == count, because we want all items when type == get_all or type == count
			loadTaskParams(elm[0], props);
			loadTaskParamsWithDefaultValues(elm[0], type);
		}
	}
}

function loadTaskParams(elm, props) {
	elm = $(elm);
	
	var is_bl = elm.hasClass("call_business_logic_task_html");
	var is_query = elm.hasClass("call_ibatis_query_task_html");
	var is_hbn = elm.hasClass("call_hibernate_method_task_html");
	
	if (props && (is_bl || is_query || is_hbn)) {
		var selected_broker = elm.children(".broker_method_obj").children("select").val();
		var matches = selected_broker ? selected_broker.match(/\(([^\(\)]+)\)/g) : [];
		selected_broker = matches && matches[0] ? matches[0].replace(/[\(\)"]+/g, "") : selected_broker;
		
		var bean_name = "";
		var bean_file_name = "";
		var bs = is_bl ? business_logic_brokers : (is_query ? ibatis_brokers : hibernate_brokers);
		
		for (var i = 0; i < bs.length; i++) {
			var b = bs[i];
			if (b[0] == selected_broker) {
				bean_name = b[2];
				bean_file_name = b[1];
				break;
			}
		}
		
		if (bean_file_name && bean_name) {
			if (is_bl && props["path"] && props["service_id"]) 
				updateBusinessLogicParams(elm, bean_file_name, bean_name, props["path"], props["service_id"]);
			else if (is_query && props["path"] && props["service_type"] && props["service_id"])
				updateQueryParams(elm, bean_file_name, bean_name, null, null, props["path"], props["service_type"], props["service_id"], "", "queries"); //db_driver and db_type were already replaced in the get_query_properties_url, so we don't need to pass them here
			else if (is_hbn && props["path"] && props["service_id"]) {
				var method = props["service_method"];
				var relationship_type = "";
				var query_type = "";
			
				if ($.inArray(method, CallHibernateMethodTaskPropertyObj.available_native_methods) != -1) {
					relationship_type = "native";
				}
				else if ($.inArray(method, CallHibernateMethodTaskPropertyObj.available_relationship_methods) != -1) {
					method = props["sma_rel_name"];
					relationship_type = "relationships";
				}
				else if ($.inArray(method, CallHibernateMethodTaskPropertyObj.available_query_methods) != -1) {
					relationship_type = "queries";
				
					switch (method) {
						case "callInsertSQL":
						case "callInsert":
							query_type = "insert"; break;
						case "callUpdateSQL":
						case "callUpdate":
							query_type = "update"; break;
						case "callDeleteSQL":
						case "callDelete":
							query_type = "delete"; break;
						case "callSelectSQL":
						case "callSelect":
							query_type = "select"; break;
						case "callProcedureSQL":
						case "callProcedure":
							query_type = "procedure"; break;
					}
				}
			
				if (relationship_type)
					updateHibernateObjectMethodParams(elm, bean_file_name, bean_name, null, null, props["path"], query_type, method, props["service_id"], relationship_type); //db_driver and db_type were already replaced in the get_query_properties_url, so we don't need to pass them here
			}
		}
	}
}

function loadTaskParamsWithDefaultValues(elm, type) {
	elm = $(elm);
	
	var is_get = type == "get_all" || type == "count" || type == "get" || type == "relationships" || type == "relationships_count";
	var table_name = tn = null;
	
	if (!is_get) {
		var table_group = elm.parent().closest(".table_group");
		table_name = table_group.attr("table_alias") ? table_group.attr("table_alias") : table_group.attr("table_name");
	}
	
	if (table_name)
		tn = ("" + table_name).replace(/\./g, "_");
	
	if (elm.hasClass("get_query_data_task_html") || elm.hasClass("set_query_data_task_html")) {
		var sql_elm = elm.children(".sql");
		var editor = sql_elm.data("editor");
		var sql = editor ? editor.getValue() : sql_elm.children("textarea.sql_editor").val();
		
		if (sql) {
			var matches = sql.match(/#([^#]+)#/g);
			
			if (matches) {
				for(var i = 0; i < matches.length; i++) {
					var m = matches[i];
					var name = m.replace(/#/g, "");
					var value = is_get ? "{$_GET['" + name + "']}" : (table_name ? "{$_POST['" + tn + "']['" + name + "']}" : "{$_POST['" + name + "']}");
					
					do {
						sql = sql.replace(m, value);
					}
					while (sql.indexOf(m) >= 0);
				}
			
				if (editor)
					editor.setValue(sql, 1);
				else
					sql_elm.children("textarea.sql_editor").val(sql);
			}
		}
	}
	else {
		var items = null;
		
		if (elm.hasClass("call_hibernate_method_task_html")) {
			var method = elm.children(".service_method").children(".service_method_string").val();
			
			var params_class_name = "sma_data";
			if (method == "findRelationships" || method == "findRelationship" || method == "countRelationships" || method == "countRelationship")
				params_class_name = "sma_parent_ids";
			
			items = elm.children(".service_method_args").children("." + params_class_name).children("." + params_class_name);
			
			var sma_ids = elm.children(".service_method_args").children(".sma_ids");
			if (sma_ids.css("display") != "none")
				sma_ids.children("input").val("ids");
		}
		else
			items = elm.children(".params").children(".parameters");
		
		items.find(".item .key").each(function(idx, field) {
			field = $(field);
			var name = field.val();
			var value = is_get ? "$_GET['" + name + "']" : (table_name ? "$_POST['" + tn + "']['" + name + "']" : "$_POST['" + name + "']");
			field.parent().children(".value").val(value);
			field.parent().children(".value_type").val("");
		});
	}
}

function removeTablePanel(elm) {
	if (confirm("Are you sure that you wish to remove this panel?"))
		$(elm).parent().parent().remove();
}

function toggleTablePanel(elm) {
	elm = $(elm);
	var table_panel = elm.parent().parent().children(".table_panel");
	
	if (!table_panel.is(":visible")) {
		table_panel.show();
		elm.removeClass("maximize").addClass("minimize");
	}
	else {
		table_panel.hide();
		elm.removeClass("minimize").addClass("maximize");
	}
}

function removeTableUIPanel(elm) {
	if (confirm("Are you sure that you wish to remove this UI panel?")) {
		$(elm).parent().parent().remove();
	}
}

function toggleTableUIPanel(elm) {
	elm = $(elm);
	var table_ui_panel = elm.parent().parent().children(".table_ui_panel");
	
	if (!table_ui_panel.is(":visible")) {
		table_ui_panel.show();
		elm.removeClass("maximize").addClass("minimize");
	}
	else {
		table_ui_panel.hide();
		elm.removeClass("minimize").addClass("maximize");
	}
}

function onChangeBrokersLayerType(elm) {
	elm = $(elm);
	
	var type = elm.val();
	var tasks_properties = elm.parent().parent().children(".task_properties");
	
	switch(type) {
		case "callbusinesslogic":
			tasks_properties.children(".call_business_logic_task_html").show();
			tasks_properties.children(".call_ibatis_query_task_html, .call_hibernate_method_task_html, .get_query_data_task_html, .set_query_data_task_html").hide();
			break;
		case "callibatisquery":
			tasks_properties.children(".call_ibatis_query_task_html").show();
			tasks_properties.children(".call_business_logic_task_html, .call_hibernate_method_task_html, .get_query_data_task_html, .set_query_data_task_html").hide();
			break;
		case "callhibernatemethod":
			tasks_properties.children(".call_hibernate_method_task_html").show();
			tasks_properties.children(".call_business_logic_task_html, .call_ibatis_query_task_html, .get_query_data_task_html, .set_query_data_task_html").hide();
			break;
		case "getquerydata":
			tasks_properties.children(".get_query_data_task_html").show();
			tasks_properties.children(".call_business_logic_task_html, .call_ibatis_query_task_html, .call_hibernate_method_task_html, .set_query_data_task_html").hide();
			break;
		case "setquerydata":
			tasks_properties.children(".set_query_data_task_html").show();
			tasks_properties.children(".call_business_logic_task_html, .call_ibatis_query_task_html, .call_hibernate_method_task_html, .get_query_data_task_html").hide();
			break;
	}
}

function addForeignTable(elm) {
	elm = $(elm);
	var table_name = elm.parent().closest(".table_group").attr("table_name");
	
	var foreign_table_name = prompt("Please write the new foreign table name (Not the table alias, but the real table name):");
	
	if (foreign_table_name == null)
		return;
	else if (foreign_table_name.trim() == "")
		alert("Table Name cannot be empty!");
	else {
		MyFancyPopup.showOverlay();
		MyFancyPopup.showLoading();
		
		foreign_table_name = foreign_table_name.trim();
	
		if (tables.hasOwnProperty(foreign_table_name)) {
			var table_ui_panel = elm.parent().parent().children(".table_ui_panel");
			
			var exists = false;
			var foreign_tables = table_ui_panel.children(".table_ui").children(".table_ui_panel");
			for (var i = 0; i < foreign_tables.length; i++)
				if ($(foreign_tables[i]).attr("relationship_table") == foreign_table_name) {
					exists = true;
					break;
				}
			
			if (!exists || confirm("This foreign table already exist! Do you still wish to proceed?")) {
				//PREPARING NEW TABLE GROUP FOR FOREIGN TABLE
				var exists = false;
				var tables_groups = $(".table_group");
				for (var i = 0; i < tables_groups.length; i++)
					if ($(tables_groups[i]).attr("table_name") == foreign_table_name) {
						exists = true;
						break;
					}
				
				//Add another prompt so the user can insert the table alias, but only if doesn't exist yet.
				var sta = selected_tables_alias;
				var foreign_table_alias = "";
				
				if (!sta || !sta.hasOwnProperty(foreign_table_name) || sta[foreign_table_name] == "") {
					foreign_table_alias = prompt("Do you wish to add a different alias for the '" + foreign_table_name + "' table?", foreign_table_name);
					
					if (foreign_table_alias != null && foreign_table_alias.trim() != "" && foreign_table_alias.trim() != foreign_table_name)
						sta[foreign_table_name] = foreign_table_alias.trim();
					else
						foreign_table_alias = "";
				}
				else //Getting existent table alias
					foreign_table_alias = ("" + sta[foreign_table_name]).trim();
				
				if (!exists && confirm("Do you wish to add this new table to the main table's settings?")) {
					//PREPARING TABLE PROPS
					if (!tables_ui_props.hasOwnProperty(foreign_table_name)) {
						$.ajax({
							type : "post",
							url: get_tables_ui_props_url,
							data : {
								"ab": active_brokers,
								"abf": active_brokers_folder,
								"st": foreign_table_name,
								"sta": sta,
							},
							dataType: "json",
							success: function(data) {
								var ftn = foreign_table_name.toLowerCase();
								tables_ui_props[ftn] = data && data.hasOwnProperty("tables") && data["tables"].hasOwnProperty(ftn) ? data["tables"][ftn] : null;
							},
							async: false
						});
					}
			
					//PREPARING TABLE GROUP HTML
					var table_group = $(table_group_html.replace(/#table_name#/g, foreign_table_name));
					
					//add table alias
					if (foreign_table_alias) {
						table_group.children(".table_header").children("label").append(" with alias: '" + foreign_table_alias + "'");
						table_group.attr("table_alias", foreign_table_alias);
					}
					
					table_group.find(".table_ui_panel").each(function (idx, elm) {
						elm = $(elm);
						var type = elm.attr("type");
						var relationship_table = elm.attr("relationship_table");
				
						elm.children(".brokers_layer_type").children("select").each(function (idx, item) {
							onChangeBrokersLayerType(item);
						});
						
						elm.children(".task_properties").children("div").each(function (idx, item) {
							loadTask(item, foreign_table_name, type, relationship_table);
						});
						
						elm.children(".brokers_layer_type").children("select").each(function (idx, item) {
							showCorrectLoadedTask(item, foreign_table_name, type);
						});
					});
			
					$(".tables_groups").append(table_group);
				}
				
				//PREPARING FOREIGN TABLE HTML
				var html = foreign_table_html.replace(/#table_name#/g, table_name).replace(/#foreign_table_name#/g, foreign_table_name);
				var foreign_elm = $(html);
				
				//Add foreign table alias
				if (foreign_table_alias)
					foreign_elm.children(".table_header").children("label").each(function (idx, elm) {
						$(elm).append(" (with table alias: '" + foreign_table_alias + "')");
					});
				
				//LOAD FOREIGN TABLE TASKS
				foreign_elm.find(".table_ui_panel").each(function (idx, elm) {
					elm = $(elm);
					var type = elm.attr("type");
					var relationship_table = elm.attr("relationship_table");
					
					elm.children(".brokers_layer_type").children("select").each(function (idx, item) {
						onChangeBrokersLayerType(item);
					});
					
					elm.children(".task_properties").children("div").each(function (idx, item) {
						loadTask(item, table_name, type, relationship_table);
					});
					
					elm.children(".brokers_layer_type").children("select").each(function (idx, item) {
						showCorrectLoadedTask(item, table_name, type);
					});
				});
				
				//APPEND NEW FOREIGN TABLE HTML
				table_ui_panel.append(foreign_elm);
			}
		}
		else {
			alert("Table doesn't exist! Please choose a table's name that already exists!");
		}
		
		MyFancyPopup.hidePopup();
	}
}

function submitForm(elm, on_submit_func) {
	elm = $(elm);
	var oForm = elm.parent().closest(".top_bar").parent().children("form");
	var status = typeof on_submit_func == "function" ? on_submit_func( oForm[0] ) : true;
	
	if (status) {
		var on_click = elm.attr("onClick");
		elm.addClass("loading").removeAttr("onClick");
		
		oForm.submit();
		
		/*setTimeout(function() {
			elm.removeClass("loading").attr("onClick", on_click);
		}, 2000);*/
	}
	
	return status;
}

function save(elm) {
	elm = $(elm);
	var on_click = elm.attr("onClick");
	elm.addClass("loading").attr("onClick", "");
	
	MyFancyPopup.showLoading();
	
	var settings = getPanelsSettings();
	settings["sta"] = selected_tables_alias;
	settings["users_perms"] = users_perms;
	settings["list_and_edit_users"] = list_and_edit_users;
	//console.log(settings);return false;
	
	$.ajax({
		type : "post",
		url : create_presentation_uis_files_automatically_url,
		data : JSON.stringify(settings),
		dataType : "text",
		processData: false,
		contentType: 'application/json', //typically 'application/x-www-form-urlencoded', but the service you are calling may expect 'text/json'... check with the service to see what they expect as content-type in the HTTP header.
		success : function(statuses, textStatus, jqXHR) {
			try {
				var obj_statuses = $.parseJSON(statuses); //if no json, it will throw a javascript error
				
				if (obj_statuses && $.isPlainObject(obj_statuses) && !$.isEmptyObject(obj_statuses)) {
					var form = $(".tables_settings .stas_form form");
					form.find("textarea[name='statuses']").val(statuses);
					form.append('<input type="hidden" name="step_3" value="Continue" />');
					form.submit();
				}
				else
					elm.removeClass("loading").attr("onClick", on_click);
			} 
			catch(e) {
				elm.removeClass("loading").attr("onClick", on_click);
				alert("Error trying to create automatically ui files. Please try again...");
				
				if (console && console.log) {
					console.log(e);
					console.log(statuses);
				}
			}
			
			MyFancyPopup.hidePopup();
		},
		error : function(jqXHR, textStatus, errorThrown) { 
			var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
			StatusMessageHandler.showError("Error trying to save new settings.\nPlease try again..." + msg);
			elm.removeClass("loading").attr("onClick", on_click);
	
			MyFancyPopup.hidePopup();
		},
	});
	
	return false;
}

function getPanelsSettings() {
	var settings = {};
	
	var tables_groups = $(".tables_groups .table_group");
	for (var i = 0; i < tables_groups.length; i++) {
		var table_group = $(tables_groups[i]);
		var table_name = table_group.attr("table_name");
		
		settings[table_name] = {};
		
		var table_ui_panels = table_group.children(".table_panel").children(".table_ui").children(".table_ui_panel");
		for (var j = 0; j < table_ui_panels.length; j++) {
			var table_ui_panel = $(table_ui_panels[j]);
			
			if (table_ui_panel.hasClass("selected_task_properties")) {
				var type = table_ui_panel.attr("type");
				//console.log("TABLE: "+table_name+" - "+type);
				if (type) {
					settings[table_name][type] = getPanelSettings(table_ui_panel);
				}
			}
			else {//For the Foreign tables
				var fks_panels = table_ui_panel.children(".table_ui").children(".table_ui_panel");
				for (var w = 0; w < fks_panels.length; w++) {
					var fk_panel = $(fks_panels[w]);
					var type = fk_panel.attr("type");
					var relationship_table = fk_panel.attr("relationship_table");
					//console.log("TABLE: "+table_name+" - "+type+" - "+relationship_table);
					
					if (type && relationship_table) {
						if (!settings[table_name].hasOwnProperty(type)) {
							settings[table_name][type] = {};
						}
						
						if (!settings[table_name][type].hasOwnProperty(relationship_table)) {
							settings[table_name][type][relationship_table] = [];
						}
						
						settings[table_name][type][relationship_table].push( getPanelSettings(fk_panel) );
					}
				}
			}
		}
	}
	
	return settings;
}

function getPanelSettings(table_panel) {
	var brokers_layer_type = table_panel.find(".brokers_layer_type select").val();
	
	var task_properties_elm = null;
	switch (brokers_layer_type) {
		case "callbusinesslogic": task_properties_elm = table_panel.find(".task_properties .call_business_logic_task_html").parent(); break;
		case "callibatisquery": task_properties_elm = table_panel.find(".task_properties .call_ibatis_query_task_html").parent(); break;
		case "callhibernatemethod": task_properties_elm = table_panel.find(".task_properties .call_hibernate_method_task_html").parent(); break;
		case "getquerydata": task_properties_elm = table_panel.find(".task_properties .get_query_data_task_html").parent(); break;
		case "setquerydata": task_properties_elm = table_panel.find(".task_properties .set_query_data_task_html").parent(); break;
	}
	
	var settings = {
		"brokers_layer_type": brokers_layer_type
	};
	
	if (task_properties_elm) {
		var status = true;
		
		var func = js_submit_functions[brokers_layer_type];
		if (func) {
			//console.log(func + "(task_properties_elm, null, {})");
			eval ("status = " + func + "(task_properties_elm, null, {});");
		}
		
		if (status) {
			var query_string = taskFlowChartObj.Property.getPropertiesQueryStringFromHtmlElm(task_properties_elm, "task_property_field");
			parse_str(query_string, settings);
		}
		
		func = js_complete_functions[brokers_layer_type];
		if (func) {
			//console.log(func + "(task_properties_elm, null, settings, status)");
			eval (func + "(task_properties_elm, null, settings, status);");
		}
	}
	
	return settings;
}
