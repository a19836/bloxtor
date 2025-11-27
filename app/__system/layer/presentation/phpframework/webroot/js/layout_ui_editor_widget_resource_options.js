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

/* LAYOUTUIEDITOR FUNCTIONS */
var creating_resources = {};
var creating_resources_by_table = {};
var no_cache_for_first_resource_creation = false;
var no_cache_for_first_resource_creation_ttl = 300000; //5 min
var flush_cache = false;

var LayoutUIEditorWidgetResourceFancyPopup = new MyFancyPopupClass();
var LayoutUIEditorWidgetBrokerResourceAttributeFancyPopup = new MyFancyPopupClass();
var CreateDBTableOrAttributeFancyPopup = new MyFancyPopupClass();
var AddLayoutUIEditorWidgetResourceSLAResourceAsyncFancyPopup = new MyFancyPopupClass();

AddLayoutUIEditorWidgetResourceSLAResourceAsyncFancyPopup.init({
	parentElement: window,
});

function initLayoutUIEditorWidgetResourceOptions(PtlLayoutUIEditor) {
	PtlLayoutUIEditor.options.on_context_menu_widget_setting = typeof onContextMenuLayoutUIEditorWidgetSetting == "function" ? onContextMenuLayoutUIEditorWidgetSetting : null;
	PtlLayoutUIEditor.options.on_template_source_editor_ready_func = typeof setCodeEditorAutoCompleter == "function" ? setCodeEditorAutoCompleter : null;
	
	PtlLayoutUIEditor.options.on_choose_event_func = toggleChooseEventPopup;
	var exists_choose_db_table_or_attribute_popup = $("#choose_db_table_or_attribute").length > 0;
	
	if (exists_choose_db_table_or_attribute_popup) {
		PtlLayoutUIEditor.LayoutUIEditorWidgetResource.options.toggle_choose_db_table_attribute_popup_func = toggleChooseLayoutUIEditorWidgetResourceDBTableAttributePopup;
		PtlLayoutUIEditor.LayoutUIEditorWidgetResource.options.toggle_choose_widget_resource_popup_func = function(elm, widget, handler) {
			toggleChooseLayoutUIEditorWidgetResourceValueAttributePopup(elm, widget, handler, PtlLayoutUIEditor, false);
		};
		PtlLayoutUIEditor.LayoutUIEditorWidgetResource.options.toggle_choose_widget_resource_value_attribute_popup_func = function(elm, widget, handler) {
			toggleChooseLayoutUIEditorWidgetResourceValueAttributePopup(elm, widget, handler, PtlLayoutUIEditor, true);
		};
	}
	
	//only add handlers if db drivers exists
	if (typeof db_brokers_drivers_tables_attributes != "undefined") {
		PtlLayoutUIEditor.LayoutUIEditorWidgetResource.options.get_db_brokers_func = getLayoutUIEditorWidgetResourceDBBrokers;
		PtlLayoutUIEditor.LayoutUIEditorWidgetResource.options.get_db_drivers_func = getLayoutUIEditorWidgetResourceDBDrivers;
		PtlLayoutUIEditor.LayoutUIEditorWidgetResource.options.get_db_tables_func = getLayoutUIEditorWidgetResourceDBTables;
		PtlLayoutUIEditor.LayoutUIEditorWidgetResource.options.get_db_attributes_func = getLayoutUIEditorWidgetResourceDBAttributes;
	}
	
	PtlLayoutUIEditor.LayoutUIEditorWidgetResource.options.user_module_installed = typeof user_module_installed != "undefined" && user_module_installed;
	
	PtlLayoutUIEditor.LayoutUIEditorWidgetResource.options.get_resources_references_func = getLayoutUIEditorWidgetResourceResourcesReferences;
	PtlLayoutUIEditor.LayoutUIEditorWidgetResource.options.get_user_types_func = getLayoutUIEditorWidgetResourceUserTypes;
	PtlLayoutUIEditor.LayoutUIEditorWidgetResource.options.get_php_numeric_types_func = getLayoutUIEditorWidgetResourcePHPNumericTypes;
	PtlLayoutUIEditor.LayoutUIEditorWidgetResource.options.get_db_numeric_types_func = getLayoutUIEditorWidgetResourceDBNumericTypes;
	PtlLayoutUIEditor.LayoutUIEditorWidgetResource.options.get_db_blob_types_func = getLayoutUIEditorWidgetResourceDBBlobTypes;
	PtlLayoutUIEditor.LayoutUIEditorWidgetResource.options.get_internal_attribute_names_func = getLayoutUIEditorWidgetResourceInternalAttributeNames;
	
	PtlLayoutUIEditor.LayoutUIEditorWidgetResource.options.add_sla_resource_func = addLayoutUIEditorWidgetResourceSLAResourceSyncBasedInResourceDBTable;
	PtlLayoutUIEditor.LayoutUIEditorWidgetResource.options.remove_sla_resource_func = removeLayoutUIEditorWidgetResourceSLAResource;
	PtlLayoutUIEditor.LayoutUIEditorWidgetResource.options.create_resource_names_func = createLayoutUIEditorWidgetResourceSLAResourceNamesBasedInResourceDBTable;
}

/* ChooseEventPopup FUNCTIONS */

function toggleChooseEventPopup(elm, widget, handler, available_events, default_event) {
	var popup = $("#choose_event_popup");
	
	if (!popup[0]) {
		html = '<div id="choose_event_popup" class="myfancypopup choose_event_popup with_title">'
				+ '<div class="title">Available Events</div>'
				+ '<div class="content">'
					+ '<div class="filter">'
						+ '<label>Filter:</label>'
						+ '<select>';
		
		for (var k in available_events)
			html += 		'<option>' + k + '</option>';
		
			html += 		'</select>'
					+ '</div>'
					+ '<ul class="events">'
					+ '</ul>'
				+ '</div>'
				+ '<div class="button">'
					+ '<input type="button" value="update" onClick="LayoutUIEditorWidgetResourceFancyPopup.settings.updateFunction(this)" />'
				+ '</div>'
			+ '</div>';
		
		popup = $(html);
		
		$("body").append(popup);
		
		var draw_events_handler = function(select, items) {
			while (select.next("select").length > 0)
				select.next("select").remove();
			
			popup.find(".events").html("");
			
			if ($.isPlainObject(items)) {
				var items_html = '<select>';
				
				for (var k in items)
					items_html += '<option>' + k + '</option>';
				
				items_html += '</select>';
				
				var item_select = $(items_html);
				item_select.on("change", function() {
					var value = item_select.val();
					var select_items = items[value];
					
					draw_events_handler(item_select, select_items);
				});
				
				select.after(item_select);
				item_select.trigger("change");
			}
			else if ($.isArray(items)) {
				var items_html = '';
				
				for (var i = 0, t = items.length; i < t; i++) {
					var item = items[i];
					var value = item["value"];
					var title = item["title"];
					var description = item["description"];
					
					items_html += '<li>'
								+ '<input type="radio" name="event" value="' + value + '"/>'
								+ '<label>' + title + '</label>'
								+ '<div class="info">' + description + '</div>'
							+ '</li>';
				}
				
				popup.find(".events").html(items_html);
				
				LayoutUIEditorWidgetResourceFancyPopup.updatePopup();
			}
			else
				LayoutUIEditorWidgetResourceFancyPopup.updatePopup();
		};
		
		var select = popup.find(".filter select");
		select.on("change", function() {
			var value = select.val();
			var items = available_events[value];
			
			draw_events_handler(select, items);
		});
		select.trigger("change");
	}
	
	if (popup[0]) {
		var style = window.getComputedStyle(popup[0]);
		
		if (style.display === "none") {
			popup.hide(); //This popup is shared with other actions so we must hide it first otherwise the user experience will be weird bc we will see the popup changing with the new changes.
			
			var search_event_handler = function(events, to_search, paths) {
				var exists = false;
				
				if ($.isPlainObject(events)) {
					for (var k in events) {
						var items = events[k];
						
						if (search_event_handler(items, to_search, paths)) {
							paths.unshift(k);
							exists = true;
							break;
						}
					}
				}
				else if ($.isArray(events)) {
					for (var i = 0, t = events.length; i < t; i++) {
						var item = events[i];
						var value = item["value"];
						
						if (value) {
							var pos = value.indexOf("(");
							
							if (pos != -1)
								value = value.substr(0, pos);
							
							if (value == to_search) {
								paths.unshift( item["value"] );
								exists = true;
								break;
							}
						}
					}
				}
				
				return exists;
			};
			
			LayoutUIEditorWidgetResourceFancyPopup.init({
				elementToShow: popup,
				parentElement: document,
				onOpen: function() {
					//show default event first
					if (default_event) {
						//disable previous selection
						popup.find("input[name=event]:checked").removeAttr("checked").prop("checked", false);
						popup.children(".content").scrollTop(0);
						
						//prepare default_event
						var pos = default_event.indexOf("(");
						
						if (pos != -1)
							default_event = default_event.substr(0, pos);
						
						//check if exists the default_event
						var founds = [];
						search_event_handler(available_events, default_event, founds);
						
						//select the default_event
						if (founds.length > 0) {
							var select = popup.find(".filter select").first();
							
							for (var i = 0, t = founds.length - 1; i < t; i++) {
								if (!select[0])
									break;
								
								select.val(founds[i]);
								select.trigger("change");
								
								select = select.next("select");
							}
							
							popup.find(".events > li > input[name=event]").each(function(idx, input) {
								input = $(input);
								var value = input.val();
								var pos = value.indexOf("(");
								
								if (pos != -1)
									value = value.substr(0, pos);
								
								if (value == default_event) {
									input.attr("checked", "checked").prop("checked", true);
									
									popup.children(".content").scrollTop( input.offset().top - 200 );
									return false;
								}
							});
						}
					}
				},
				updateFunction: function(btn) { //prepare update handler
					var code = popup.find("input[name=event]:checked").val();
					
					if (!code) 
						StatusMessageHandler.showError("Please select an event first...");
					else {
						if (typeof handler == "function")
							handler(code);
						
						LayoutUIEditorWidgetResourceFancyPopup.hidePopup();
					}
				},
			});
			LayoutUIEditorWidgetResourceFancyPopup.showPopup();
		}
		else {
			LayoutUIEditorWidgetResourceFancyPopup.hidePopup();
		}
	}
}

/* ChooseLayoutUIEditorWidgetResourceValueAttributePopup FUNCTIONS */

function toggleChooseLayoutUIEditorWidgetResourceValueAttributePopup(elm, widget, handler, PtlLayoutUIEditor, show_resource_attributes) {
	var popup = $("#choose_widget_resource_value_attribute_popup");
	
	if (!popup[0]) {
		var brokers_html = null;
		var brokers_html_elms = null;
		
		if (typeof initSLAGroupItemTasks == "function" && typeof onChangeBrokersLayerType == "function") {
			brokers_html = $(".regions_blocks_includes_settings > .resource_settings > .sla > .sla_groups_flow > .sla_main_groups > .sla_group_item.sla_group_default > .sla_group_body > section.broker_action_body").first().html();
			brokers_html_elms = $(brokers_html);
			
			if (brokers_html)
				brokers_html = '<div class="sla_main_groups hidden">'
								+ '<div class="sla_group_item">'
									+ '<div class="sla_group_body selected_task_properties">'
										+ '<div class="broker_action_body">'
											+ brokers_html
										+ '</div>'
									+ '</div>'
								+ '</div>'
							+ '</div>';
		}
		
		html = '<div id="choose_widget_resource_value_attribute_popup" class="myfancypopup choose_widget_resource_value_attribute_popup with_title">'
				+ '<div class="title"></div>'
				+ '<ul class="tabs tabs_transparent tabs_center">'
					+ '<li class="existent_resource_attribute_tab"><a href="#existent_resource_attribute">Existent Resources</a></li>'
					+ '<li class="new_resource_attribute_tab"><a href="#new_resource_attribute">New Resource</a></li>'
				+ '</ul>'
				+ '<div id="existent_resource_attribute">'
					+ '<ul>'
						+ '<li class="empty_items">There are no available resources...</li>'
					+ '</ul>' //show all existent resources with correspondent attributes
				+ '</div>'
				+ '<div id="new_resource_attribute">'
					+ '<div class="resource_type">'
						//+ '<label>Resource based in:</label>'
						+ '<select>'
							+ '<option value="db_table_attribute">Resource based in DB Table Attribute</option>'
							+ (brokers_html ?
								(brokers_html_elms.filter(".call_business_logic_task_html").length ? '<option value="callbusinesslogic">Resource based in Business Logic Service</option>' : '')
								+ (brokers_html_elms.filter(".call_ibatis_query_task_html").length ? '<option value="callibatisquery">Resource based in Ibatis Rule</option>' : '')
								+ (brokers_html_elms.filter(".call_hibernate_method_task_html").length ? '<option value="callhibernatemethod">Resource based in Hibernate Rule</option>' : '')
								+ (brokers_html_elms.filter(".get_query_data_task_html").length ? '<option value="getquerydata">Resource based in Get SQL Query Results</option>' : '')
								+ (brokers_html_elms.filter(".set_query_data_task_html").length ? '<option value="setquerydata">Resource based in Set SQL Query</option>' : '')
								+ (brokers_html_elms.filter(".call_function_task_html").length ? '<option value="callfunction">Resource based in Function</option>' : '')
								+ (brokers_html_elms.filter(".call_object_method_task_html").length ? '<option value="callobjectmethod">Resource based in Object Method</option>' : '')
								+ (brokers_html_elms.filter(".get_url_contents_task_html").length ? '<option value="restconnector">Resource based in Rest Connector</option>' : '')
								+ (brokers_html_elms.filter(".soap_connector_task_html").length ? '<option value="soapconnector">Resource based in SOAP Connector</option>' : '')
							  : '')
						+ '</select>'
					+ '</div>'
					+ '<div class="db_table_attribute">'
						+ $("#choose_db_table_or_attribute > .contents").html()
						+ '<div class="db_table_alias" title="Write a table alias if apply or leave it blank for default">'
							+ '<label>Table Alias:</label>'
							+ '<input placeHolder="Leave blank for default">'
						+ '</div>'
						+ '<div class="query_type">'
							+ '<label>Query Type:</label>'
							+ '<select>' //options must be the same than the action_type in LayoutUIEditorWidgetResource.js
								+ '<option value="get_all">Get multiple records list</option>'
								+ '<option value="count">Count multiple records list</option>'
								+ '<option value="get">Get a specific record</option>'
								+ '<option value="insert">Add a record</option>'
								+ '<option value="update">Update a record</option>'
								+ '<option value="save">Save a record (Add and Update)</option>'
								+ '<option value="update_attribute">Update a record attribute</option>'
								+ '<option value="insert_update_attribute">Insert or update a record attribute</option>'
								+ '<option value="insert_delete_attribute" title="Insert or delete a record based if a value from an attribute exists or not">Insert or delete a record attribute</option>'
								+ '<option value="multiple_insert_delete_attribute" title="Delete all records and insert new ones, based in a attribute from table">Multiple Delete and Insert records attribute</option>'
								+ '<option value="multiple_save">Save multiple records (Add and Update)</option>'
								+ '<option value="delete">Remove a record</option>'
								+ '<option value="multiple_delete">Remove multiple records</option>'
								+ '<option value="get_all_options">Get Options</option>'
							+ '</select>'
						+ '</div>'
						+ '<div class="row_index">'
							+ '<label>Row Index:</label>'
							+ '<input placeHolder="Leave blank for automatic">'
						+ '</div>'
						+ '<div class="query_conditions">'
							+ '<label>Query Conditions:</label>'
							+ '<ul>'
								+ '<li class="empty_items">There are no table attributes...</li>'
							+ '</ul>' //show all the attributes from the selected table so we can create a condition
						+ '</div>'
					+ '</div>'
					+ brokers_html
					+ '<div class="resource_info">'
						+ 'To predefine some values in the attributes, is advisable to follow the rules below:'
						+ '<ul>'
							+ '<li>For values coming from forms, automatically generated, please use: <i>#_POST[attributes][<span class="to_replace" title="Replace this text with the real attribute name">name of the attribute</span>]#</i> or <i>#_POST[conditions][<span class="to_replace" title="Replace this text with the real attribute pk name">name of the primary key</span>]#</i>;</li>'
							+ '<li>For values coming from search action, please use: <i>#_GET[search_attrs][<span class="to_replace" title="Replace this text with the real attribute name">name of the attribute</span>]#</i></li>'
							+ '<li>For values coming from sort action, please use: <i>#_GET[sort_attrs][<span class="to_replace" title="Replace this text with the real attribute name">name of the attribute</span>]#</i></li>'
							+ '<li>For values coming from the URL, please use: <i>#_GET[<span class="to_replace" title="Replace this text with the real var name">name of the variable</span>]#</i></li>'
						+ '</ul>'
					+ '</div>'
				+ '</div>'
				+ '<div class="button">'
					+ '<input type="button" value="update" />'
				+ '</div>'
			+ '</div>';
		
		popup = $(html);
		
		popup.tabs();
		
		var new_resource_attribute = popup.children("#new_resource_attribute");
		var db_table_attribute = new_resource_attribute.children(".db_table_attribute");
		var sla_main_groups = new_resource_attribute.children(".sla_main_groups");
		
		db_table_attribute.find(" > .db_driver > select,  > .type > select").attr("onChange", "onChangeChooseLayoutUIEditorWidgetResourceValueDBDriverOrType(this)");
		db_table_attribute.find(" > .db_table > select").attr("onChange", "onChangeChooseLayoutUIEditorWidgetResourceValueDBTable(this)");
		db_table_attribute.find(" > .db_attribute > select").attr("onChange", "onChangeChooseLayoutUIEditorWidgetResourceValueDBAttribute(this)"); //Note that this select called before the syncChooseTableOrAttributePopups, but we want to ignore this sync function. So we simply replace it.
		
		addCreateTableOptionToChooseLayoutUIEditorWidgetResourceValueDBTables( db_table_attribute.find(" > .db_table > select")[0] ); //add create DB Table option;
		
		db_table_attribute.find(" > .db_attribute > select").html(""); //reset the attributes, just in case, bc in the begining there is no selected table.
		
		popup.find(" > .tabs > li > a").on("click", function(event) {
			popup.css("width", "");
			popup.css("height", "");
			
			setTimeout(function() {
				LayoutUIEditorWidgetResourceFancyPopup.updatePopup();
			}, 300);
		});
		
		new_resource_attribute.find(" > .resource_type > select").on("change", function(event) {
			var selection = $(this).val();
			
			if (selection == "db_table_attribute") {
				db_table_attribute.removeClass("hidden");
				sla_main_groups.addClass("hidden");
			}
			else {
				db_table_attribute.addClass("hidden");
				sla_main_groups.removeClass("hidden");
				
				var sla_group_item = sla_main_groups.children(".sla_group_item");
				var sla_broker_action_body = sla_group_item.find(" > .sla_group_body > .broker_action_body");
				
				//code from sla.js:onChangeSLAInputType method
				initSLAGroupItemTasks(sla_group_item, {});
				onChangeBrokersLayerType(selection, sla_broker_action_body);
				addBrokerResourceTypeEventToGetAutomaticallyIcon(selection, sla_broker_action_body);
			}
			
			popup.css("width", "");
			popup.css("height", "");
			
			setTimeout(function() {
				LayoutUIEditorWidgetResourceFancyPopup.updatePopup();
			}, 300);
		});
		
		db_table_attribute.find(" > .query_type > select").on("change", function(event) {
			var row_index = db_table_attribute.children(".row_index");
			var query_conditions = db_table_attribute.children(".query_conditions");
			var query_conditions_attributes = query_conditions.find(" > ul > li:not(.empty_items):not(.primary_key)");
			
			if (this.value == "get_all" || this.value == "count" || this.value == "get_all_options") {
				row_index.show();
				query_conditions.show();
				query_conditions_attributes.show();
			}
			else if (this.value == "get") {
				row_index.hide();
				query_conditions.show();
				
				query_conditions_attributes.each(function(idx, li) {
					li = $(li);
					var name = li.find("input[type=text]").attr("name");
					var default_value = "#_GET[search_attrs]" + name.substr("conditions".length) + "#";
					
					li.hide().removeClass("condition_activated");
					li.find("input[type=checkbox]").prop("checked", false).removeAttr("checked");
					li.find("input[type=text]").val(default_value);
				});
			}
			else {
				row_index.hide();
				query_conditions.hide();
			}
			
			/*disabled bc we only create one getAllOptions method in the server for each table/service file.
			if (popup.attr("show_resource_attributes") != 1) {
				if (this.value == "get_all_options")
					db_table_attribute.children(".db_attribute").show();
				else
					db_table_attribute.children(".db_attribute").hide();
			}*/
		});
		
		$("body").append(popup);
	}
	
	if (popup[0]) {
		var style = window.getComputedStyle(popup[0]);
		
		if (style.display === "none") {
			popup.attr("show_resource_attributes", show_resource_attributes ? 1 : 0);
			
			//update popup class and toggle some fields
			var new_resource_attribute = popup.children("#new_resource_attribute");
			var db_table_attribute = new_resource_attribute.children(".db_table_attribute");
			var query_type_select = db_table_attribute.find(" > .query_type > select");
			var query_type_select_options = query_type_select.find("option[value=insert], option[value=update], option[value=save], option[value=update_attribute], option[value=insert_update_attribute], option[value=insert_delete_attribute], option[value=multiple_insert_delete_attribute], option[value=multiple_save], option[value=delete], option[value=multiple_delete], option[value=get_all_options], option[value=]");
			
			if (show_resource_attributes) {
				popup.addClass("show_resource_attributes");
				db_table_attribute.children(".db_attribute").show();
				query_type_select_options.hide();
				
				if (query_type_select_options.filter( query_type_select.find("option:selected") ).length > 0) {
					query_type_select.val("get_all");
					query_type_select.trigger("change");
				}
			}
			else {
				popup.removeClass("show_resource_attributes");
				db_table_attribute.children(".db_attribute").hide();
				query_type_select_options.show();
			}
			
			//update title
			popup.children(".title").html("Choose Widget Resource" + (show_resource_attributes ? " Attribute" : ""));
			
			//set popup update click event
			popup.find(" > .button input").unbind("click").on("click", function(event) {
				prepareChooseLayoutUIEditorWidgetResourceValueUserData(this, elm, widget, PtlLayoutUIEditor, show_resource_attributes, handler);
				
				LayoutUIEditorWidgetResourceFancyPopup.hidePopup();
			});
			
			//prepare existent resources
			//get all available resources
			var available_resources = getLayoutUIEditorWidgetResourceSLAsDescriptionByName();
			
			//show available resources
			var ul = popup.find(" > #existent_resource_attribute > ul");
			var lis = ul.children("li:not(.empty_items)");
			
			if ($.isEmptyObject(available_resources)) {
				lis.remove();
				ul.children("li.empty_items").show();
			}
			else {
				ul.children("li.empty_items").hide();
				
				//remove old slas
				$.each(lis, function(idx, li) {
					li = $(li);
					var resource_name = li.attr("resource_name");
					
					if (!available_resources.hasOwnProperty(resource_name))
						li.remove();
				});
				
				//create new slas
				for (var resource_name in available_resources) {
					var resource_description = available_resources[resource_name];
					
					//Do not show the _group resources bc are dummy resources
					if (resource_name.match(/_group$/) && available_resources.hasOwnProperty(resource_name.substr(0, resource_name.length - 6)))
						continue;
					
					var li = lis.filter("[resource_name='" + resource_name + "']");
					
					//add new resource item
					if (!li[0]) {
						var html = '<li resource_name="' + resource_name + '">'
									+ '<div class="sla_resource_header">'
										+ '<div class="title"><input type="radio" name="selected_resource" /> Resource: "' + resource_name + '":</div>'
										+ '<div class="description"></div>'
									+ '</div>'
									+ '<div class="sla_resource_body">'
										+ '<div class="sla_resource_index">'
											+ '<label>Row Index (Only fill this field if the resource is a list):</label>'
											+ '<input type="text" placeHolder="Leave it blank for automatic" />'
										+ '</div>'
										+ '<div class="sla_resource_attributes">'
											+ '<label>Choose an attribute:</label>'
											+ '<ul>'
												+ '<li class="user_defined_item">'
													+ 'If you wish a different attribute than the below ones, please fill the text box below.<br/>Or if you wish the resource it-self, choose the text box below but leave it empty.<br/>'
													+ '<input type="radio" name="selected_resource_attribute" value="" />'
													+ '<input type="text" />'
												+ '</li>'
												+ '<li class="empty_items">The system couldn\'t detect the attributes for this resource. Please use the text box above.</li>'
											+ '</ul>'
										+ '</div>'
									+ '</div>'
								+ '</li>';
						li = $(html);
						
						li.find(".sla_resource_header input[type=radio]").on("click", function() {
							ul.children("li:not(.empty_items)").removeClass("selected");
							$(this).parent().closest("li").addClass("selected");
						});
						
						//set keydown event to update value of selected_resource_attribute input
						li.find(".sla_resource_attributes li.user_defined_item > input[type=text]")/*.on("keydown", function() {
							var input_text = $(this);
							
							if (input_text.data("set_timeout_id"))
								clearTimeout( input_text.data("set_timeout_id") );
							
							var set_timeout_id = setTimeout(function() {
								input_text.data("set_timeout_id", null);
								
								var input_radio = input_text.parent().children("input[type=radio]");
								
								input_radio.prop("checked", true).attr("checked", "checked");
								input_radio.val( input_text.val() );
								//console.log(input_text.val());
							}, 500);
							
							input_text.data("set_timeout_id", set_timeout_id);
						})*/
						.on("focus", function() {
							var input_radio = $(this).parent().children("input[type=radio]");
							input_radio.prop("checked", true).attr("checked", "checked");
						})
						.on("blur", function() {
							var input_radio = $(this).parent().children("input[type=radio]");
							input_radio.val( $(this).val() );
						});
						
						ul.append(li);
					}
					
					//get description from group
					if (!resource_description && available_resources[resource_name + "_group"])
						resource_description = available_resources[resource_name + "_group"];
					
					//update description
					li.find(" > .sla_resource_header > .description").html(resource_description);
				}
				
				//prepare attributes for the available resources
				if (show_resource_attributes) {
					var select = $('<select></select>');
					var variables_in_workflow = assignObjectRecursively({}, ProgrammingTaskUtil.variables_in_workflow);
					var callback = function() {
						if (window.variables_in_workflow_loading_processes == 0) {
							//because the updateSLAProgrammingTaskVariablesInWorkflowSelect method resets the ProgrammingTaskUtil.variables_in_workflow var, we need to update it with the variables_in_workflow var, which contains the vars created from the addLayoutUIEditorWidgetResourceSLAResourceSyncBasedInResourceDBTable method
							
							if ($.isPlainObject(variables_in_workflow) && !$.isEmptyObject(variables_in_workflow)) {
								for (var k in variables_in_workflow)
									if (!ProgrammingTaskUtil.variables_in_workflow.hasOwnProperty(k))
										ProgrammingTaskUtil.variables_in_workflow[k] = variables_in_workflow[k];
								
								var html = select.html(); //backup select html bc the populateVariablesOfTheWorkflowInSelectField will remove all html
								populateVariablesOfTheWorkflowInSelectField(select);
								select.append(html); //append previous html
							}
							
							//get all resources' attributes by resource name
							var options = select.find("option");
							var available_resources_attributes = {};
							var resources_multiple = {};
							
							$.each(options, function(idx, option) {
								var var_name = $(option).val();
								
								for (var resource_name in available_resources) {
									var prefix = resource_name + "[";
									
									if (var_name.indexOf(prefix) == 0) {
										if (!$.isArray(available_resources_attributes[resource_name]))
											available_resources_attributes[resource_name] = [];
										
										var pos = var_name.lastIndexOf("[") + 1; //get last position for "[" bc the var_name may be: "xxx[idx][attr_name]"
										var parsed_var_name = var_name.substr(pos, var_name.length - (pos + 1)); //+1 bc of the last char: "]"
										
										if (available_resources_attributes[resource_name].indexOf(parsed_var_name) == -1)
											available_resources_attributes[resource_name].push(parsed_var_name);
										
										if (var_name.indexOf("[idx]") != -1)
											resources_multiple[resource_name] = true;
										
										break;
									}
								}
							});
							//console.log(available_resources_attributes);
							
							//show attributes for all the displayed resources
							for (var resource_name in available_resources_attributes) {
								var resource_attributes = available_resources_attributes[resource_name];
								var resource_li = ul.find(" > li[resource_name='" + resource_name + "']");
								var resource_ul = resource_li.find(".sla_resource_attributes > ul");
								var attribute_lis = resource_ul.children(":not(.empty_items):not(.user_defined_item)");
								
								if (resource_li && resources_multiple[resource_name])
									resource_li.addClass("is_multiple");
								
								if (resource_attributes.length > 0) {
									resource_ul.children(".empty_items").hide();
									resource_ul.children(".user_defined_item").show();
									
									//remove old attributes
									for (var i = 0, t = attribute_lis.length; i < t; i++) {
										var attribute_li = $(attribute_lis[i]);
										var attr_name = attribute_li.children("input[type=radio]").attr("value");
										
										if (!attr_name || resource_attributes.indexOf(attr_name) == -1)
											attribute_li.remove();
									}
									
									//add new attributes
									for (var i = 0, t = resource_attributes.length; i < t; i++) {
										var attr_name = resource_attributes[i];
										var attr_input = attribute_lis.children("input[type=radio][value='" + attr_name + "']");
										
										if (!attr_input[0]) {
											var html = '<li>'
														+ '<input type="radio" name="selected_resource_attribute" value="' + attr_name + '" />'
														+ '<label>' + attr_name + '</label>'
													+ '</li>';
											var li = $(html);
											
											resource_ul.append(li);
										}
									}
								}
								else {
									resource_ul.children(".empty_items").show();
									resource_ul.children(".user_defined_item").hide();
									attribute_lis.remove();
								}
							}
						}
						else
							setTimeout(callback, 500);
					};
					
					updateSLAProgrammingTaskVariablesInWorkflowSelect(select);
					setTimeout(callback, 1000);
				}
			}
			
			//show popup
			LayoutUIEditorWidgetResourceFancyPopup.init({
				elementToShow: popup,
				parentElement: document,
			});
			LayoutUIEditorWidgetResourceFancyPopup.showPopup();
		}
		else {
			LayoutUIEditorWidgetResourceFancyPopup.hidePopup();
		}
	}
}

function getBrokerResourceGetAutomaticallyIcon(broker_type, sla_broker_action_body) {
	var get_automatically_icon = null;
	
	switch(broker_type) {
		case "callbusinesslogic":
			get_automatically_icon = sla_broker_action_body.find(".call_business_logic_task_html .get_automatically > .icon");
			break;
		case "callibatisquery":
			get_automatically_icon = sla_broker_action_body.find(".call_ibatis_query_task_html .get_automatically > .icon").show();
			break;
		case "callhibernatemethod":
			get_automatically_icon = sla_broker_action_body.find(".call_hibernate_method_task_html .get_automatically > .icon").show();
			break;
	}
	
	return get_automatically_icon;
}

function addBrokerResourceTypeEventToGetAutomaticallyIcon(broker_type, sla_broker_action_body) {
	var get_automatically_icon = getBrokerResourceGetAutomaticallyIcon(broker_type, sla_broker_action_body);
	
	if (get_automatically_icon && get_automatically_icon[0] && get_automatically_icon[0].hasAttribute("onClick")) {
		var on_click = "" + get_automatically_icon.attr("onClick");
		
		if (on_click.indexOf("onBrokerResourceTypeGetAutomaticallyIconClickEvent") == -1)
			get_automatically_icon.attr("onClick", on_click + ";onBrokerResourceTypeGetAutomaticallyIconClickEvent(this, \'" + broker_type + "\');");
	}
}

function onBrokerResourceTypeGetAutomaticallyIconClickEvent(elm, broker_type) {
	var on_close = MyFancyPopup.settings.onClose;
	
	MyFancyPopup.settings.onClose = function() {
		if (typeof on_close == "function")
			on_close();
		
		var sla_broker_action_body = $(elm).parent().closest(".broker_action_body");
		
		loadBrokerResourceArgsWithDefaultValues(broker_type, sla_broker_action_body);
		
		MyFancyPopup.settings.onClose = on_close;
	};
}

function loadBrokerResourceArgsWithDefaultValues(broker_type, sla_broker_action_body) {
	var items = null;
	
	switch(broker_type) {
		case "callbusinesslogic":
			var section = sla_broker_action_body.children(".call_business_logic_task_html");
			items = section.children(".params").children(".parameters");
			break;
		case "callibatisquery":
			var section = sla_broker_action_body.children(".call_ibatis_query_task_html");
			items = section.children(".params").children(".parameters");
			break;
		case "callhibernatemethod":
			var section = sla_broker_action_body.children(".call_hibernate_method_task_html");
			var method = section.children(".service_method").children(".service_method_string").val();
			
			var params_class_name = "sma_data";
			if (method == "findRelationships" || method == "findRelationship" || method == "countRelationships" || method == "countRelationship")
				params_class_name = "sma_parent_ids";
			
			items = section.children(".service_method_args").children("." + params_class_name).children("." + params_class_name);
			break;
	}
	
	if (items) {
		var broker_data = getSLAResourceBrokerData(broker_type, sla_broker_action_body);
		var is_post = isSLAResourceBrokerPostMethod(broker_type, broker_data);
		
		loadBrokerResourceArgsItemsWithDefaultValues(items, is_post, "");
	}
}

function loadBrokerResourceArgsItemsWithDefaultValues(array_items, is_post, prefix) {
	var ul = array_items.children("ul");
	
	ul.find(" > .item > .key").each(function(idx, field) {
		field = $(field);
		var name = field.val();
		var default_value = !is_post ? "#_GET[search_attrs]" + prefix + "[" + name + "]#" : (name.toLowerCase().indexOf("id") != -1 ? "#_POST[conditions]" + prefix + "[" + name + "]#" : "#_POST[attributes]" + prefix + "[" + name + "]#");
		
		field.parent().children(".value").val(default_value);
		field.parent().children(".value_type").val("string");
	});
	
	ul.children("li:not(.item)").each(function(idx, li) {
		li = $(li);
		
		var li_items = li.children(".items").first();
		var key = li_items.children(".key").val();
		var li_prefix = prefix + "[" + key + "]";
		loadBrokerResourceArgsItemsWithDefaultValues(li, is_post, li_prefix);
	});
}

function addCreateTableOptionToChooseLayoutUIEditorWidgetResourceValueDBTables(elm) {
	//add option: Create new table
	if (isCreateDBTableAllowed()) {
		var option = $(elm).children("option:first-child").first();
		
		var html = '<option value="" create_new_table>-- Create new table --</option><option disabled></option>';
		
		if (!option.attr("value"))
			option.after(html);
		else
			option.before(html);
	}
}

function addCreateAttributeOptionToChooseLayoutUIEditorWidgetResourceValueDBAttributes(elm) {
	//add option: Create new attribute
	if (isCreateDBAttributeAllowed()) {
		var option = $(elm).children("option:first-child").first();
		
		var html = '<option value="" create_new_attribute>-- Create new attribute --</option><option disabled></option>';
		
		if (!option.attr("value"))
			option.after(html);
		else
			option.before(html);
	}
}

function onChangeChooseLayoutUIEditorWidgetResourceValueDBDriverOrType(elm) {
	updateDBTables(elm);
	
	var p = $(elm).parent().parent();
	var db_table_select = p.find(" > .db_table select");
	addCreateTableOptionToChooseLayoutUIEditorWidgetResourceValueDBTables(db_table_select[0]);
}

function onChangeChooseLayoutUIEditorWidgetResourceValueDBTable(elm) {
	elm = $(elm);
	updateDBAttributes(elm[0]);
	
	var p = elm.parent().parent();
	var popup = p.closest(".myfancypopup");
	var db_attribute = p.children(".db_attribute");
	var db_attribute_select = db_attribute.children("select");
	
	if (popup[0] && popup[0].hasAttribute("show_resource_attributes") && popup[0].getAttribute("show_resource_attributes") == 0)
		db_attribute.hide();
	
	addCreateAttributeOptionToChooseLayoutUIEditorWidgetResourceValueDBAttributes(db_attribute_select[0]);
	
	//prepare attributes ui
	var db_broker = p.find(" > .db_broker select").val();
	var db_driver = p.find(" > .db_driver select").val();
	var db_type = p.find(" > .type select").val();
	var db_table = p.find(" > .db_table select").val();
	var query_type = p.find(" > .query_type select").val();
	
	var ul = p.find(" > .query_conditions > ul");
	
	ul.children("li:not(.empty_items)").remove();
	ul.children("li.empty_items").show();
	
	if (db_table) {
		var db_attributes = getLayoutUIEditorWidgetResourceDBAttributes(db_broker, db_driver, db_type, db_table);
		
		if (!$.isEmptyObject(db_attributes)) {
			ul.children("li.empty_items").hide();
			
			var html = '';
			
			for (var attr_name in db_attributes) {
				var attr = db_attributes[attr_name];
				var is_pk = attr["primary_key"];
				var on_click_func = typeof ProgrammingTaskUtil != "undefined" && ProgrammingTaskUtil.on_programming_task_choose_created_variable_callback ? "ProgrammingTaskUtil.on_programming_task_choose_created_variable_callback" : "onProgrammingTaskChooseCreatedVariableForUrlQueryStringAttribute";
				
				var default_value = "#_GET[search_attrs][" + attr_name + "]#";
				
				html += '<li' + (is_pk ? ' class="primary_key"' : '') + '>'
						+ '<input type="checkbox" onClick="toggleChooseLayoutUIEditorWidgetResourceValueQueryCondition(this)">'
						+ '<label>' + attr_name + ':</label>'
						+ '<input type="text" name="conditions[' + attr_name + ']" value="' + default_value + '" />'
						+ '<span class="icon add_variable" onClick="' + on_click_func + '(this)"></span>'
					+ '</li>';
			}
			
			ul.append(html);
			
			//only show the correspondent conditions according with the query type
			p.find(" > .query_type > select").trigger("change");
		}
	}
	else if (elm.parent().is(".db_table") && !elm.val() && elm.children("option[create_new_table]:selected").length > 0) {
		elm.val(""); //update value to empty so it doesn't call this function again
		
		//show popup to create new table, if url exists
		openCreateDBTablePopup(db_broker, db_driver, db_type, db_table, function(created_table) {
			if (!created_table) {
				//get old tables
				var old_tables = elm.find("option").toArray().map(function(option) {
					return $(option).val();
				}).filter(function(table) { return table != ""; });
				
				//update tables
				elm.parent().children(".refresh").trigger("click");
				
				//get new tables
				var new_tables = elm.find("option").toArray().map(function(option) {
					return $(option).val();
				}).filter(function(table) { return table != ""; });
				
				//get created table by doing the difference between 2 arrays
				var created_tables = new_tables.filter(function(table) {return !old_tables.includes(table); });
				created_table = created_tables[0];
			}
			else //update tables
				elm.parent().children(".refresh").trigger("click");
			
			//show new table
			if (created_table) {
				elm.val(created_table);
				onChangeChooseLayoutUIEditorWidgetResourceValueDBTable(elm[0]);
			}
		});
	}
	
	LayoutUIEditorWidgetResourceFancyPopup.updatePopup();
}

function onChangeChooseLayoutUIEditorWidgetResourceValueDBAttribute(elm) {
	//Note that this function should not call the syncChooseTableOrAttributePopups method, otherwise we are copying the option[create_new_attribute] to the other popups. We can simply ignore this function bc it doesn't make sense call it here.
	
	elm = $(elm);
	
	if (elm.parent().is(".db_attribute") && !elm.val() && elm.children("option[create_new_attribute]:selected").length > 0) {
		elm.val(""); //update value to empty so it doesn't call this function again
		
		var p = elm.parent().parent();
		var db_broker = p.find(" > .db_broker select").val();
		var db_driver = p.find(" > .db_driver select").val();
		var db_type = p.find(" > .type select").val();
		var db_table = p.find(" > .db_table select").val();
		
		//show popup to create new attribute, if url exists
		openCreateDBAttributePopup(db_broker, db_driver, db_type, db_table, function(created_attribute) {
			if (!created_attribute) {
				//get old attributes
				var old_attributes = elm.find("option").toArray().map(function(option) {
					return $(option).val();
				}).filter(function(attribute) { return attribute != ""; });
				
				//update attributes
				elm.parent().children(".refresh").trigger("click");
				
				//get new attributes
				var new_attributes = elm.find("option").toArray().map(function(option) {
					return $(option).val();
				}).filter(function(attribute) { return attribute != ""; });
				
				if (new_attributes.length == 0)
					p.find(" > .db_table .refresh").trigger("click");
				else {
					//get created attribute by doing the difference between 2 arrays
					var created_attributes = new_attributes.filter(function(attribute) {return !old_attributes.includes(attribute); });
					created_attribute = created_attributes[0];
				}
			}
			else //update attributes
				elm.parent().children(".refresh").trigger("click");
			
			//show new attribute
			if (created_attribute) {
				elm.val(created_attribute);
				onChangeChooseLayoutUIEditorWidgetResourceValueDBAttribute(elm[0]);
			}
		});
	}
}

function toggleChooseLayoutUIEditorWidgetResourceValueQueryCondition(elm) {
	$(elm).parent().toggleClass("condition_activated");
}

function prepareChooseLayoutUIEditorWidgetResourceValueUserData(elm, menu_settings_elm, widget, PtlLayoutUIEditor, show_resource_attributes, handler) {
	var popup = $(elm).parent().closest(".choose_widget_resource_value_attribute_popup");
	var active_tab = popup.find(" > ul > .ui-state-active");
	var resource_name = null;
	var resource_attribute = null;
	var resource_index = null;
	
	//check what is the active panel and get the correspondent attribute and resource reference. 
	if (active_tab.is(".existent_resource_attribute_tab")) {
		var li = popup.find(" > #existent_resource_attribute li:not(empty_items) > .sla_resource_header input[type=radio]:checked").parent().closest("li[resource_name]");
		resource_name = li.attr("resource_name");
		var input = li.find(".sla_resource_attributes input[name=selected_resource_attribute]:checked").first();
		resource_attribute = input.val();
		resource_index = li.find(".sla_resource_index input").val();
	}
	else if (active_tab.is(".new_resource_attribute_tab")) {
		var new_resource_attribute = popup.children("#new_resource_attribute");
		var selection = new_resource_attribute.find(" > .resource_type > select").val();
		
		var widget_group = widget.closest("[data-widget-group-list], [data-widget-group-form], [data-widget-list], [data-widget-form]");
		var widget_group_view_permissions = widget_group[0] ? PtlLayoutUIEditor.LayoutUIEditorWidgetResource.getWidgetPermissions(widget_group) : null;
		var widget_view_permissions = PtlLayoutUIEditor.LayoutUIEditorWidgetResource.getWidgetPermissions(widget);
		var available_resources = getLayoutUIEditorWidgetResourceSLAsDescriptionByName();
		
		//prepare resource data
		if (selection == "db_table_attribute") {
			var p = new_resource_attribute.children(".db_table_attribute");
			var db_broker = p.find(" > .db_broker select").val();
			var db_driver = p.find(" > .db_driver select").val();
			var db_type = p.find(" > .type select").val();
			var db_table = p.find(" > .db_table select").val();
			var db_table_alias = p.find(" > .db_table_alias input").val();
			var db_attribute = p.find(" > .db_attribute select").val();
			var query_type = p.find(" > .query_type select").val();
			var query_conditions_ul = p.find(" > .query_conditions > ul");
			var query_conditions_inputs = query_conditions_ul.find(" > li.condition_activated input:not([type=checkbox])");
			
			resource_index = p.find(" > .row_index input").val();
			
			query_conditions_inputs.removeClass("task_property_field");
			query_conditions_inputs.addClass("task_property_field");
			
			var conditions = parseArray(query_conditions_ul);
			conditions = $.isPlainObject(conditions) ? conditions["conditions"] : null;
			
			query_conditions_inputs.removeClass("task_property_field");
			
			var resource_data = {
				conditions: conditions,
			};
			var resource_conditions_hash = $.isPlainObject(conditions) && !$.isEmptyObject(conditions) ? ("" + JSON.stringify(conditions).hashCode()).replace(/-/g, "_") : null;
			//console.log("resource_data:");
			//console.log(resource_data);
			
			//prepare resource_attribute, if apply
			if (show_resource_attributes)
				resource_attribute = db_attribute;
			
			//disabled bc we only create one getAllOptions method in the server for each table/service file.
			//if (query_type == "get_all_options")
			//	resource_data["attribute"] = db_attribute;
			
			//prepare resource possible names
			var resources_name = createLayoutUIEditorWidgetResourceSLAResourceNamesBasedInResourceDBTable(query_type, db_driver, db_table, db_table_alias, null, resource_data);
			
			var resource_possible_names = [resources_name];
			var resource_possible_name_permissions = [null];
			
			if (widget_group_view_permissions) {
				resource_possible_names.push( createLayoutUIEditorWidgetResourceSLAResourceNamesBasedInResourceDBTable(query_type, db_driver, db_table, db_table_alias, widget_group_view_permissions, resource_data) );
				resource_possible_name_permissions.push(widget_group_view_permissions);
			}
			
			if (widget_view_permissions) {
				resource_possible_names.push( createLayoutUIEditorWidgetResourceSLAResourceNamesBasedInResourceDBTable(query_type, db_driver, db_table, db_table_alias, widget_view_permissions, resource_data) );
				resource_possible_name_permissions.push(widget_view_permissions);
			}
			
			//If the resource name doesn't exist yet, create it
			for (var i = resource_possible_names.length - 1; i >= 0; i--) {
				var resources_name = resource_possible_names[i];
				
				for (var j = 0, t = resources_name.length; j < t; j++) {
					var rn = resources_name[j];
					
					//if conditions exists add the conditions hash code to the resource name, otherwise the system wil find an incorrect resource
					if (resource_conditions_hash)
						rn += "_" + resource_conditions_hash;
					
					if (available_resources.hasOwnProperty(rn)) {
						resource_name = rn;
						break;
					}
				}
			}
			
			if (!resource_name) {
				var idx = resource_possible_names.length - 1;
				resource_name = resource_possible_names[idx][0]; //get the latest names from resource_possible_names and then set resource name with widget's permissions, and if they don't exist, set to widget_group's permissions, and if they don't exist, set the resource name without permissions.
				var permissions = resource_possible_name_permissions[idx];
				
				//if conditions exists add the conditions hash code to the resource name, otherwise the system wil find an incorrect resource
				if (resource_conditions_hash)
					resource_name += "_" + resource_conditions_hash;
				
				//console.log("resource_name:"+resource_name);
				
				//create the resource in server, if not yet created
				addLayoutUIEditorWidgetResourceSLAResourceSyncBasedInResourceDBTable(db_broker, db_driver, db_type, db_table, db_table_alias, query_type, resource_name, permissions, resource_data);
			}
		}
		else {
			var sla_broker_action_body = new_resource_attribute.find(" > .sla_main_groups > .sla_group_item > .sla_group_body > .broker_action_body");
			var broker_data = getSLAResourceBrokerData(selection, sla_broker_action_body);
			
			if ($.isPlainObject(broker_data) && !$.isEmptyObject(broker_data)) {
				//prepare resource possible names
				var resources_name = createLayoutUIEditorWidgetResourceSLAResourceNamesBasedInResourceBroker(selection, broker_data, null);
				
				var resource_possible_names = [resources_name];
				var resource_possible_name_permissions = [null];
				
				if (widget_group_view_permissions) {
					resource_possible_names.push( createLayoutUIEditorWidgetResourceSLAResourceNamesBasedInResourceBroker(selection, broker_data, widget_group_view_permissions) );
					resource_possible_name_permissions.push(widget_group_view_permissions);
				}
				
				if (widget_view_permissions) {
					resource_possible_names.push( createLayoutUIEditorWidgetResourceSLAResourceNamesBasedInResourceBroker(selection, broker_data, widget_view_permissions) );
					resource_possible_name_permissions.push(widget_view_permissions);
				}
				
				//If the resource name doesn't exist yet, create it
				for (var i = resource_possible_names.length - 1; i >= 0; i--) {
					var resources_name = resource_possible_names[i];
					
					for (var j = 0, t = resources_name.length; j < t; j++) {
						var rn = resources_name[j];
						
						if (available_resources.hasOwnProperty(rn)) {
							resource_name = rn;
							break;
						}
					}
				}
				
				if (!resource_name) {
					var idx = resource_possible_names.length - 1;
					resource_name = resource_possible_names[idx][0]; //get the latest names from resource_possible_names and then set resource name with widget's permissions, and if they don't exist, set to widget_group's permissions, and if they don't exist, set the resource name without permissions.
					var permissions = resource_possible_name_permissions[idx];
					
					//console.log("resource_name:"+resource_name);
					
					//create the resource in server, if not yet created
					addLayoutUIEditorWidgetResourceSLAResourceSyncBasedInResourceBroker(selection, broker_data, permissions, resource_name);
				}
				
				//prepare new handler to open popup, showing the attributes from the new resource
				if (show_resource_attributes) {
					var handler_bkp = handler;
					
					handler = function(r_name, r_index, r_attribute) {
						setTimeout(function() {
							StatusMessageHandler.showMessage("Loading possible attributes for new resource... Wait a while...", "", "bottom_messages", 5000);
							LayoutUIEditorWidgetResourceFancyPopup.showLoading();
							
							//waits a while to give time for the system to get the props for the correspondent resource_name
							var maximum_secs_to_wait = 5;
							var secs_count = 0;
							var func = function() {
								secs_count++;
								
								//get resource_attributes
								var exists = false;
								
								if (ProgrammingTaskUtil.variables_in_workflow)
									for (var var_name in ProgrammingTaskUtil.variables_in_workflow)
										if (var_name.substr(0, r_name.length + 1) == r_name + "[") { //var_name sample: xxx[idx][id] or xxx[id]
											exists = true;
											break;
										}
								
								//if no resource_attributes call it again after 1 sec
								if (!exists) {
									if (secs_count < maximum_secs_to_wait)
										setTimeout(function() {
											func();
										}, 1000);
									else {
										StatusMessageHandler.removeLastShownMessage("info", "bottom_messages");
										LayoutUIEditorWidgetResourceFancyPopup.hideLoading();
										
										handler_bkp(r_name, r_index, r_attribute);
									}
								}
								else {
									prepareChooseLayoutUIEditorWidgetBrokerResourceAttribute(r_name, r_index, r_attribute, handler_bkp);
									
									StatusMessageHandler.removeLastShownMessage("info", "bottom_messages");
									LayoutUIEditorWidgetResourceFancyPopup.hideLoading();
								}
							};
							
							func();
						}, 300);
					};
				}
			}
		}
	}
	//console.log("resource_name:"+resource_name);
	//console.log("resource_attribute:"+resource_attribute);
	
	handler(resource_name, resource_index, resource_attribute);
}

function prepareChooseLayoutUIEditorWidgetBrokerResourceAttribute(resource_name, resource_index, resource_attribute, handler) {
	//get resource_attributes
	var resource_attributes = [];
	var is_multiple = false;
	
	if (ProgrammingTaskUtil.variables_in_workflow)
		for (var var_name in ProgrammingTaskUtil.variables_in_workflow)
			if (var_name.substr(0, resource_name.length + 1) == resource_name + "[") { //var_name sample: xxx[idx][id] or xxx[id]
				var attr_name = var_name.substr(resource_name.length + 1); //remove prefix
				attr_name = attr_name.substr(0, attr_name.length - 1); //remove last ]
				
				if (attr_name.substr(0, 5) == "idx][") {
					is_multiple = true;
					attr_name = attr_name.substr(5);
				}
				
				resource_attributes.push(attr_name);
			}
	
	//show popup to choose attribute
	if (resource_attributes.length > 0) {
		var popup = $("#choose_attribute_for_special_resource_popup");
		
		if (!popup[0]) {
			html = '<div class="myfancypopup choose_attribute_for_special_resource_popup with_title">'
					+ '<div class="title">Please choose one of the following attributes:</div>'
					+ '<ul></ul>'
					+ '<div class="button">'
						+ '<input type="button" value="update" onClick="LayoutUIEditorWidgetBrokerResourceAttributeFancyPopup.settings.updateFunction(this)" />'
					+ '</div>'
				+ '</div>';
			
			popup = $(html);
			
			$("body").append(popup);
		}
		
		var ul = popup.children("ul");								
		var html = '<li class="resource_index' + (is_multiple ? ' is_multiple' : '') + '">'
					+ '<label>Row Index (Only fill this field if the resource is a list):</label>'
					+ '<input type="text" placeHolder="Leave it blank for automatic" />'
				+ '</li>'
				+ '<li class="user_defined_item">'
					+ 'If you wish a different attribute than the below ones, please fill the text box below.<br/>Or if you wish the resource it-self, choose the text box below but leave it empty.<br/>'
					+ '<input type="radio" name="selected_resource_attribute" value="" />'
					+ '<input type="text" />'
				+ '</li>';
		
		for (var i = 0, t = resource_attributes.length; i < t; i++) {
			var attr_name = resource_attributes[i];
			
			html += '<li>'
				+ '<input type="radio" name="selected_resource_attribute" value="' + attr_name + '" />'
				+ '<label>' + attr_name + '</label>'
			+ '</li>';
		}
		
		ul.append(html);
		
		ul.find("li.user_defined_item > input[type=text]").on("focus", function() {
			var input_radio = $(this).parent().children("input[type=radio]");
			input_radio.prop("checked", true).attr("checked", "checked");
		})
		.on("blur", function() {
			var input_radio = $(this).parent().children("input[type=radio]");
			input_radio.val( $(this).val() );
		});
		
		LayoutUIEditorWidgetBrokerResourceAttributeFancyPopup.init({
			elementToShow: popup,
			parentElement: document,
			updateFunction: function() { //prepare update handler
				//prepare resource_attribute
				resource_attribute = ul.find(" > li input[name=selected_resource_attribute]:checked").first().val();
				resource_index = ul.find(" > li.resource_index input").val();
				
				handler(resource_name, resource_index, resource_attribute);
				
				LayoutUIEditorWidgetBrokerResourceAttributeFancyPopup.hidePopup();
			},
		});
		LayoutUIEditorWidgetBrokerResourceAttributeFancyPopup.showPopup();
	}
	else
		handler(resource_name, resource_index, resource_attribute);
}

/* ChooseLayoutUIEditorWidgetResourceDBTableAttributePopup FUNCTIONS */

function toggleChooseLayoutUIEditorWidgetResourceDBTableAttributePopup(elm, event, handler) {
	var popup = $("#choose_widget_resource_db_table_or_attribute_popup");
	
	if (!popup[0] && $("#choose_db_table_or_attribute").length > 0) {
		popup = $("#choose_db_table_or_attribute").clone();
		popup.attr("id", "choose_widget_resource_db_table_or_attribute_popup");
		popup.find(" > .button > input").attr("onClick", "LayoutUIEditorWidgetResourceFancyPopup.settings.updateFunction(this)");
		
		$(document.body).append(popup);
	}
	
	if (popup[0]) {
		var style = window.getComputedStyle(popup[0]);
		
		if (style.display === "none") {
			popup.hide(); //This popup is shared with other actions so we must hide it first otherwise the user experience will be weird bc we will see the popup changing with the new changes.
			
			var default_value = $(elm).parent().children("input").val();
			var db_attribute_elm = popup.find(".db_attribute");
			db_attribute_elm.show();
			
			if (default_value)
				db_attribute_elm.find("select").val(default_value);
			
			LayoutUIEditorWidgetResourceFancyPopup.init({
				elementToShow: popup,
				parentElement: document,
				updateFunction: function(btn) { //prepare update handler
					var p = $(btn).parent().parent();
					var db_attribute = p.find(".db_attribute select").val();
					
					handler(db_attribute);
					
					LayoutUIEditorWidgetResourceFancyPopup.hidePopup();
				},
			});
			LayoutUIEditorWidgetResourceFancyPopup.showPopup();
		}
		else {
			LayoutUIEditorWidgetResourceFancyPopup.hidePopup();
		}
	}
	else 
		StatusMessageHandler.showError("No #choose_db_table_or_attribute elm to be open as a popup! Please talk with the sys admin...");
}

/* EDIT DB TABLE FUNCTIONS */

function openCreateDBTablePopup(db_broker, db_driver, db_type, db_table, handler) {
	var func = typeof handler == "function" ? function(popup) {
		var url = popup.children("iframe")[0].contentWindow.document.location;
		var params = getURLSearchParams(url);
		var table = params["table"];
		
		handler(table);
	} : null;
	
	openCreateDBTableOrAttributePopup(db_broker, db_driver, db_type, db_table, func);
}

function openCreateDBAttributePopup(db_broker, db_driver, db_type, db_table, handler) {
	var func = typeof handler == "function" ? function(popup) {
		handler();
	} : null;
	
	openCreateDBTableOrAttributePopup(db_broker, db_driver, db_type, db_table, func);
}

function openCreateDBTableOrAttributePopup(db_broker, db_driver, db_type, db_table, handler) {
	if (isCreateDBAttributeAllowed()) {
		var popup = $("#create_db_table_or_attribute_popup");
		
		if (!popup[0]) {
			popup = $('<div id="create_db_table_or_attribute_popup" class="myfancypopup create_db_table_or_attribute_popup with_iframe_title"></div>');
			$(document.body).append(popup);
		}
		
		var url = create_db_table_or_attribute_url;
		url += url.indexOf("?") != -1 ? "" : "?";
		url += "&db_broker=" + db_broker + "&db_driver=" + db_driver + "&db_type=" + db_type + "&db_table=" + db_table + "&popup=1";
		popup.html('<iframe src="' + url + '"></iframe>');
		
		CreateDBTableOrAttributeFancyPopup.init({
			elementToShow: popup,
			parentElement: document,
			onClose: function() { //prepare update handler
				if (typeof handler == "function")
					handler(popup);
			},
		});
		
		CreateDBTableOrAttributeFancyPopup.showPopup();
	}
}

function isCreateDBTableAllowed() {
	return typeof create_db_table_or_attribute_url != "undefined" && create_db_table_or_attribute_url;
}

function isCreateDBAttributeAllowed() {
	return isCreateDBTableAllowed();
}

/* GET FUNCTIONS */

function getLayoutUIEditorWidgetResourceDBBrokers() {
	if (typeof db_brokers_drivers_tables_attributes != "undefined") {
		var db_brokers = [];
		
		if (db_brokers_drivers_tables_attributes)
			for (var db_broker in db_brokers_drivers_tables_attributes)
				db_brokers.push(db_broker);
		
		return db_brokers;
	}
	
	return null;
}

function getLayoutUIEditorWidgetResourceDBDrivers(db_broker) {
	if (typeof db_brokers_drivers_tables_attributes != "undefined") {
		//set defaults
		db_broker = db_broker ? db_broker : (typeof default_dal_broker != "undefined" ? default_dal_broker : null);
		
		if (db_broker) {
			var db_drivers = [];
			
			if (db_brokers_drivers_tables_attributes && db_brokers_drivers_tables_attributes[db_broker])
				for (var db_driver in db_brokers_drivers_tables_attributes[db_broker])
					db_drivers.push(db_driver);
			
			return db_drivers;
		}
	}
	
	return null;
}

function getLayoutUIEditorWidgetResourceDBTables(db_broker, db_driver, db_type) {
	//set defaults
	db_broker = db_broker ? db_broker : (typeof default_dal_broker != "undefined" ? default_dal_broker : null);
	db_driver = db_driver ? db_driver : (typeof default_db_driver != "undefined" ? default_db_driver : null);
	db_type = db_type ? db_type : (typeof default_db_type != "undefined" ? default_db_type : null);
	
	if (db_broker && db_driver && db_type) {
		var db_tables = getDBTables(db_broker, db_driver, db_type);
		var names = [];
		
		if ($.isPlainObject(db_tables))
			for (var table_name in db_tables)
				names.push(table_name);
		
		return names;
	}
	
	return null;
}

function getLayoutUIEditorWidgetResourceDBAttributes(db_broker, db_driver, db_type, db_table) {
	//set defaults
	db_broker = db_broker ? db_broker : (typeof default_dal_broker != "undefined" ? default_dal_broker : null);
	db_driver = db_driver ? db_driver : (typeof default_db_driver != "undefined" ? default_db_driver : null);
	db_type = db_type ? db_type : (typeof default_db_type != "undefined" ? default_db_type : null);
	
	if (db_broker && db_driver && db_type && db_table)
		return getDBTableAttributesDetailedInfo(db_broker, db_driver, db_type, db_table);
	
	return null;
}

//based in the updateSLAProgrammingTaskVariablesInWorkflowSelect method inside of sla.js
function getLayoutUIEditorWidgetResourceResourcesReferences() {
	var references = [];
	var resources = getLayoutUIEditorWidgetResourceSLAsDescriptionByName();
	
	for (var resource_name in resources)
		references.push(resource_name);
	
	return references;
}

function getLayoutUIEditorWidgetResourceSLAsDescriptionByName() {
	var resources = {};
	
	var inputs = $(".sla_groups_flow .sla_groups .sla_group_item:not(sla_group_default) > .sla_group_header > .result_var_name");
	
	$.each(inputs, function(idx, input) {
		input = $(input);
		var var_name = input.val();
		var_name = var_name ? var_name.replace(/^\s+/g, "").replace(/\s+$/g, "") : "";
		
		if (var_name != "")
			resources[var_name] = input.parent().closest(".sla_group_header").find(" > .sla_group_sub_header > .action_description > textarea").val();
	});
	
	if (taskFlowChartObj) {
		var tasks_properties = taskFlowChartObj.TaskFlow.tasks_properties;
		
		if (tasks_properties)
			$.each(tasks_properties, function(idx, task_properties) {
				var var_name = task_properties && task_properties["properties"] ? task_properties["properties"]["result_var_name"] : "";
				var_name = var_name ? var_name.replace(/^\s+/g, "").replace(/\s+$/g, "") : "";
				
				if (var_name != "")
					resources[var_name] = task_properties["properties"]["action_description"];
			});
	}
	
	return resources;
}

function getLayoutUIEditorWidgetResourceUserTypes() {
	return typeof available_user_types != "undefined" ? available_user_types : null;
}

function getLayoutUIEditorWidgetResourcePHPNumericTypes() {
	return typeof php_numeric_types != "undefined" ? php_numeric_types : null;
}

function getLayoutUIEditorWidgetResourceDBNumericTypes() {
	return typeof db_numeric_types != "undefined" ? db_numeric_types : null;
}

function getLayoutUIEditorWidgetResourceDBBlobTypes() {
	return typeof db_blob_types != "undefined" ? db_blob_types : null;
}

function getLayoutUIEditorWidgetResourceInternalAttributeNames() {
	return typeof internal_attribute_names != "undefined" ? internal_attribute_names : null;
}

function getSLAResourceBrokerData(broker_type, sla_broker_action_body) {
	var broker_data = getBrokerSettings(sla_broker_action_body, broker_type);
	
	switch(broker_type) {
		case "callfunction":
			var section = sla_broker_action_body.children(".call_function_task_html");
			var aux = parseArray( section.children(".func_args") );
			broker_data["func_args"] = aux && aux["func_args"] ? aux["func_args"] : null;
			break;
		case "callobjectmethod":
			var section = sla_broker_action_body.children(".call_object_method_task_html");
			var aux = parseArray( section.children(".method_args") );
			broker_data["method_args"] = aux && aux["method_args"] ? aux["method_args"] : null;
			break;
		case "restconnector":
			if (broker_data["result_type_type"] == "options")
				broker_data["result_type_type"] = "string";
			break;
		case "soapconnector":
			if (broker_data["result_type_type"] == "options")
				broker_data["result_type_type"] = "string";
			break;
	}
	
	return broker_data;
}

function isSLAResourceBrokerPostMethod(broker_type, broker_data) {
	var is_post = false;
	
	if ($.isPlainObject(broker_data) && !$.isEmptyObject(broker_data))
		switch (broker_type) {
			case "callbusinesslogic":
				var parts = ("" + broker_data["service_id"]).split(".");
				
				if (parts.length > 1)
					is_post = parts[1].match(/(insert|update|delete)/i);
				
				break;
			case "callibatisquery":
				is_post = ("" + broker_data["service_id"]).match(/(insert|update|delete)/i);
				break;
			case "callhibernatemethod":
				is_post = ("" + broker_data["service_method"]).match(/(insert|update|delete)/i);
				break;
			case "setquerydata":
				is_post = true;
				break;
			case "callfunction":
				is_post = ("" + broker_data["func_name"]).match(/(insert|update|delete)/i);
				break;
			case "callobjectmethod":
				is_post = ("" + broker_data["method_name"]).match(/(insert|update|delete)/i);
				break;
		}
	
	return is_post;
}

function getSLAResourceBrokerDescription(broker_type) {
	switch (broker_type) {
		case "callbusinesslogic": return "Call business logic service.";
		case "callibatisquery": return "Call Ibatis rule.";
		case "callhibernatemethod": return "Call hibernate rule.";
		case "getquerydata": return "Call sql query and return results.";
		case "setquerydata": return "Execute sql query and return status.";
		case "callfunction": return "Call function.";
		case "callobjectmethod": return "Call object method.";
		case "restconnector": return "Call a third-party REST service.";
		case "soapconnector": return "Call a third-party SOAP service.";
	}
	
	return "";
}

/* add and remove LayoutUIEditorWidgetResourceSLAResource FUNCTIONS */

function addLayoutUIEditorWidgetResourceSLAResourceSyncBasedInResourceBroker(broker_type, broker_data, permissions, resource_name) {
	var resource_settings = $(".regions_blocks_includes_settings .resource_settings");
	
	//check if resource_name doesn't exist already, bc meanwhile it may was created before. Note that it is possible to happen multiple concurrent calls of this function with the same resource_name. So just in case we check if exists again...
	var inputs = resource_settings.find(".sla_groups_flow .sla_groups .sla_group_item:not(sla_group_default) > .sla_group_header > .result_var_name");
	var exists = false;
	
	$.each(inputs, function(idx, input) {
		if ($(input).val() == resource_name) {
			exists = true;
			return false
		}
	});
	
	if (!exists) {
		var action_description = getSLAResourceBrokerDescription(broker_type);
		var is_post = isSLAResourceBrokerPostMethod(broker_type, broker_data);
		
		//prepare actions with action_group
		var action_group = {
			result_var_name: resource_name + "_group", 
			action_type: "group", 
			condition_type: "execute_if_condition", 
			condition_value: '\\$_GET["resource"] == "' + resource_name + '"' + (is_post ? ' && \\$_POST' : ''),
			action_description: action_description,
			action_value: {
				group_name: "",
				actions: []
			}
		};
		var actions = [action_group];
		
		//prepare access permissions
		var permissions_exist = false;
		
		if (permissions && typeof access_activity_id != "undefined" && $.isNumeric(access_activity_id)) {
			var access_permissions = null;
			
			if ($.isPlainObject(permissions)) {
				access_permissions = permissions["show"];
				
				if (data.hasOwnProperty("show"))
					access_permissions = data["show"];
				else if (data.hasOwnProperty("access"))
					access_permissions = data["access"];
				else
					access_permissions = data["view"];
			}
			
			if (access_permissions) {
				if (!$.isPlainObject(access_permissions))
					access_permissions = {user_type_ids: access_permissions};
				
				var user_type_ids = access_permissions["user_type_ids"];
				user_type_ids = $.isArray(user_type_ids) ? user_type_ids : [user_type_ids];
				
				var resources = access_permissions["resources"];
				resources = $.isArray(resources) ? resources : [resources];
				
				//prepare user type ids
				if (user_type_ids.length > 0) {
					var user_perms = [];
					
					//add user types
					for (var i = 0, t = user_type_ids.length; i < t; i++) {
						var user_type_id = user_type_ids[i];
						
						if ($.isNumeric(user_type_id))
							user_perms.push({
								user_type_id: user_type_id,
								activity_id: access_activity_id,
							});
					}
					
					if (user_perms.length > 0) {
						action_group["action_value"]["actions"].push({
							result_var_name: "allowed", 
							action_type: "check_logged_user_permissions", 
							condition_type: "execute_always", 
							condition_value: "", 
							action_value: {
								all_permissions_checked: 0,
								entity_path: '$entity_path',
								logged_user_id: '$GLOBALS["logged_user_id"]',
								users_perms: user_perms,
							}
						});
						
						permissions_exist = true;
					}
				}
				
				//prepare resources
				if (resources.length > 0) {
					var resource_names = [];
					
					for (var i = 0, t = resources.length; i < t; i++) {
						var resource = resources[i];
						
						//if is string
						if (!$.isPlainObject(resource))
							resource = {name: resource};
						
						if (resource["name"] || $.isNumeric(resource["name"]))
							resource_names.push(resource["name"]);
					}
					
					if (resource_names.length > 0) {
						action_group["action_value"]["actions"].push({
							result_var_name: "allowed", 
							action_type: "string", 
							condition_type: "execute_if_condition",
							condition_value: (permissions_exist ? '\\$allowed && ' : "") + '\\$' + resource_names.join(' && \\$'),
							action_value: '1'
						});
						
						permissions_exist = true;
					}
				}
			}
		}
		
		//prepare action
		action_group["action_value"]["actions"].push({
			result_var_name: resource_name,
			action_type: broker_type,
			condition_type: permissions_exist ? "execute_if_var" : "execute_always",	
			condition_value: permissions_exist ? "allowed" : "",	
			action_value: broker_data,
		});
		
		//prepare anti-action (create_unsuccessfully_resource)
		if (is_post)
			action_group["action_value"]["actions"].push({
				result_var_name: resource_name + "_unsuccessfully",
				action_type: "string",
				condition_type: "execute_if_not_var",	
				condition_value: resource_name,	
				action_value: 1,
			});
		
		//load actions
		var add_group_icon = resource_settings.find(".sla_groups_flow > nav > .add_sla_group");
		loadSLASettingsActions(add_group_icon[0], actions, false, false); //set asynchronous argument to false, otherwise it won't updateSLAProgrammingTaskVariablesInWorkflow correctly and then will not call the prepareChooseLayoutUIEditorWidgetBrokerResourceAttribute method
	
		//add the new vars of the resource_name to ProgrammingTaskUtil.variables_in_workflow.
		var popup = $("#choose_property_variable_from_file_manager");
		updateSLAProgrammingTaskVariablesInWorkflow(popup);
		
		//check main settings, if parse html is disable, and if so, enable it
		enableMainSettingsParseHtml();
		
		//prepare messages
		StatusMessageHandler.showMessage("Resource '" + resource_name + "' created successfully!");
		
		StatusMessageHandler.shown_messages_timeout && clearTimeout(StatusMessageHandler.shown_messages_timeout);
		
		StatusMessageHandler.shown_messages_timeout = setTimeout(function() {
			StatusMessageHandler.shown_messages_timeout = null;
			
			StatusMessageHandler.removeMessages("info");
		}, 10000); //hide messages after 10 secs
	}
}

function addLayoutUIEditorWidgetResourceSLAResourceSyncBasedInResourceDBTable(db_broker, db_driver, db_type, db_table, db_table_alias, action_type, resource_name, permissions, data) {
	if (typeof create_sla_resource_url != "undefined" && create_sla_resource_url && !creating_resources[resource_name]) {
		//very important to be here, otherwise if there are not business loigc services and ibatis rules yet, it will create multiple business logic classes and ibatis files for the same table, bc this function will be called concorrently. This avoids concorrent process for the same table, which avoids multiple different files to be created for the same table service and rule.
		if (creating_resources_by_table[db_broker + "_" + db_driver + "_" + db_type + "_" + db_table])
			setTimeout(function() {
				addLayoutUIEditorWidgetResourceSLAResourceSyncBasedInResourceDBTable(db_broker, db_driver, db_type, db_table, db_table_alias, action_type, resource_name, permissions, data)
			}, 300);
		else
			addLayoutUIEditorWidgetResourceSLAResourceAsyncBasedInResourceDBTable(db_broker, db_driver, db_type, db_table, db_table_alias, action_type, resource_name, permissions, data);
	}
}

function addLayoutUIEditorWidgetResourceSLAResourceAsyncBasedInResourceDBTable(db_broker, db_driver, db_type, db_table, db_table_alias, action_type, resource_name, permissions, data) {
	var resource_table_id = db_broker + "_" + db_driver + "_" + db_type + "_" + db_table;
	
	if (typeof create_sla_resource_url != "undefined" && create_sla_resource_url && !creating_resources[resource_name] && !creating_resources_by_table[resource_table_id]) {
		creating_resources[resource_name] = true;
		creating_resources_by_table[resource_table_id] = true;
		
		//console.log("ADD RESOURCE: "+resource_name);
		var status_message_elm = StatusMessageHandler.showMessage("Creating new resource with name: '" + resource_name + "'");
		
		//disable cache, but only the first time, so the system can find the right resources and services in the business logic and data_access layers. This is very important bc if we have execute this before, then delete some business logic services and execute this again, the system will have cached that some services already exists, which is not true anymore. So then the system will not create the right business loigc services, bc it thinks that they already exist, but will use them, which will give an error later on when we execute the page. So we must no use the cache the first time we open the page editor, so we can load a new cache again. But we only do it once, so don't overload the systems. We still want the system be faster and use the cache.
		if (!no_cache_for_first_resource_creation) {
			no_cache_for_first_resource_creation = true;
			//console.log("no_cache_for_first_resource_creation");
			
			//reset no_cache_for_first_resource_creation after 60 secs, bc it might be some changes meanwhile in the business logic layer.
			setTimeout(function() {
				no_cache_for_first_resource_creation = false;
			}, no_cache_for_first_resource_creation_ttl);
		}
		
		var post_data = {
			db_broker: db_broker,
			db_driver: db_driver,
			db_type: db_type,
			db_table: db_table,
			db_table_alias: db_table_alias,
			action_type: action_type,
			resource_name: resource_name,
			permissions: permissions,
			resource_data: data,
			no_cache: no_cache_for_first_resource_creation,
		};
		//console.log(post_data);
		
		AddLayoutUIEditorWidgetResourceSLAResourceAsyncFancyPopup.showLoading();
		
		$.ajax({
			url: create_sla_resource_url,
			type: 'post',
			data: post_data,
			dataType: 'json',
			success: function(data, textStatus, jqXHR) {
				//console.log(data);
				creating_resources[resource_name] = null;
				delete creating_resources[resource_name];
				
				creating_resources_by_table[resource_table_id] = null;
				delete creating_resources_by_table[resource_table_id];
				
				status_message_elm.remove();
				
				if (data && data["flush_cache"])
					flush_cache = data["flush_cache"];
				
				if (data && data["status"]) {
					//create resource in sla panel
					if (data["actions"]) {
						var resource_settings = $(".regions_blocks_includes_settings .resource_settings");
						
						//check if resource_name doesn't exist already, bc meanwhile it may was created before. Note that it is possible to happen multiple concurrent calls of this function with the same resource_name. So just in case we check if exists again...
						var inputs = resource_settings.find(".sla_groups_flow .sla_groups .sla_group_item:not(sla_group_default) > .sla_group_header > .result_var_name");
						var exists = false;
						
						$.each(inputs, function(idx, input) {
							if ($(input).val() == resource_name) {
								exists = true;
								return false
							}
						});
						
						if (!exists) {
							var add_group_icon = resource_settings.find(".sla_groups_flow > nav > .add_sla_group")[0];
							
							loadSLASettingsActions(add_group_icon, data["actions"], false, false);
							
							//add vars with resource_name to ProgrammingTaskUtil.variables_in_workflow
							addSLAProgrammingTaskVariablesBasedInResourceDBTable(db_broker, db_driver, db_type, db_table, resource_name);
							
							//check main settings, if parse html is disable, and if so, enable it
							enableMainSettingsParseHtml();
							
							//prepare messages
							StatusMessageHandler.showMessage("Resource '" + resource_name + "' created successfully!");
							
							StatusMessageHandler.shown_messages_timeout && clearTimeout(StatusMessageHandler.shown_messages_timeout);
							
							StatusMessageHandler.shown_messages_timeout = setTimeout(function() {
								StatusMessageHandler.shown_messages_timeout = null;
								
								StatusMessageHandler.removeMessages("info");
							}, 10000); //hide messages after 10 secs
						}
					}
				}
				else if (data && data["error_message"])
					StatusMessageHandler.showError(data["error_message"]);
				else
					StatusMessageHandler.showError("Error trying to create resource '" + resource_name + "'! Please create it manually...");
				
				if ($.isEmptyObject(creating_resources_by_table))
					AddLayoutUIEditorWidgetResourceSLAResourceAsyncFancyPopup.hideLoading();
			},
			error: function (jqXHR, textStatus, errorThrown) {
				creating_resources[resource_name] = null;
				delete creating_resources[resource_name];
				
				creating_resources_by_table[resource_table_id] = null;
				delete creating_resources_by_table[resource_table_id];
				
				if ($.isEmptyObject(creating_resources_by_table))
					AddLayoutUIEditorWidgetResourceSLAResourceAsyncFancyPopup.hideLoading();
				
				if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
					showAjaxLoginPopup(jquery_native_xhr_object.responseURL, create_sla_resource_url, function() {
						StatusMessageHandler.removeLastShownMessage("error");
						
						addLayoutUIEditorWidgetResourceSLAResourceSyncBasedInResourceDBTable(db_broker, db_driver, db_type, db_table, db_table_alias, action_type, resource_name, permissions, data);
					});
				else {
					var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
					StatusMessageHandler.showError("Error trying to create new resource '" + resource_name + "'.\nPlease try again..." + msg);
				}
			},
		});
	}
}

//remove all slas with result_var_name equal to resource_name or if result_var_name is child of result_var_name
function removeLayoutUIEditorWidgetResourceSLAResource(resources_name, do_not_confirm) {
	if (!resources_name)
		resources_name = [];
	else if (!$.isArray(resources_name))
		resources_name = [resources_name];
	
	if (resources_name.length > 0) {
		var found_inputs_by_resource_name = {};
		var task_ids_by_resource_name = {};
		var inputs = $(".sla_groups_flow .sla_groups .sla_group_item:not(sla_group_default) > .sla_group_header > .result_var_name");
		
		$.each(inputs, function(idx, input) {
			input = $(input);
			var var_name = input.val();
			var_name = var_name ? var_name.replace(/^\s+/g, "").replace(/\s+$/g, "") : "";
			
			for (var i = 0, t = resources_name.length; i < t; i++) {
				var resource_name = resources_name[i];
				
				if (var_name == resource_name || var_name.indexOf(resource_name + "[") === 0) { //if var_name is equal to resource_name or if is a child
					if (!found_inputs_by_resource_name.hasOwnProperty(resource_name))
						found_inputs_by_resource_name[resource_name] = [];
					
					//get correspondent group for resource if apply
					var sla_group_item = input.parent().closest(".sla_group_item");
					var parent_sla_group_item = sla_group_item.parent().closest(".sla_group_item");
					var parent_input = parent_sla_group_item.find(" > .sla_group_header > .result_var_name");
					var parent_var_name = parent_input.val();
					
					if (parent_var_name == resource_name + "_group" || var_name.indexOf(resource_name + "_group" + "[") === 0) //if var_name is equal to resource_name or if is a child
						found_inputs_by_resource_name[resource_name].push(parent_input[0]);
					else
						found_inputs_by_resource_name[resource_name].push(input[0]);
				}
			}
		});
		
		if (taskFlowChartObj) {
			var tasks_properties = taskFlowChartObj.TaskFlow.tasks_properties;
			
			if (tasks_properties)
				for (var task_id in tasks_properties) {
					var task_properties = tasks_properties[task_id];
					var var_name = task_properties && task_properties["properties"] ? task_properties["properties"]["result_var_name"] : "";
					var_name = var_name ? var_name.replace(/^\s+/g, "").replace(/\s+$/g, "") : "";
					
					for (var i = 0, t = resources_name.length; i < t; i++) {
						var resource_name = resources_name[i];
						
						if (var_name == resource_name || var_name.indexOf(resource_name + "[") === 0) { //if var_name is equal to resource_name or if is a child
							if (!task_ids_by_resource_name.hasOwnProperty(resource_name))
								task_ids_by_resource_name[resource_name] = [];
							
							//get correspondent group for resource if apply
							var found_parent_task_id = null;
							
							for (var parent_task_id in tasks_properties) {
								var parent_task_properties = tasks_properties[parent_task_id];
								var parent_var_name = parent_task_properties && parent_task_properties["properties"] ? parent_task_properties["properties"]["result_var_name"] : "";
								parent_var_name = parent_var_name ? parent_var_name.replace(/^\s+/g, "").replace(/\s+$/g, "") : "";
								
								if (parent_var_name == resource_name + "_group" || parent_var_name.indexOf(resource_name + "_group" + "[") === 0) { //if var_name is equal to resource_name or if is a child
									found_parent_task_id = parent_task_id;
									break;
								}
							}
							
							task_ids_by_resource_name[resource_name].push(found_parent_task_id ? found_parent_task_id : task_id);
						}
					}
				}
		}
		
		if (!$.isEmptyObject(found_inputs_by_resource_name) || !$.isEmptyObject(task_ids_by_resource_name)) {
			var msg = resources_name.length > 1 ? "The following resources: '" + resources_name.join("', '") + "' are not currently being used anymore. Do you wish to remove them from the Resources Panel in the Main-Settings?" : "The '" + resources_name[0] + "' resource is not currently being used anymore. Do you wish to remove it from the Resources Panel in the Main-Settings?";
			
			if (do_not_confirm || confirm(msg)) {
				var repeated_inputs = [];
				
				for (var resource_name in found_inputs_by_resource_name) {
					$.each(found_inputs_by_resource_name[resource_name], function(idx, input) {
						if (repeated_inputs.indexOf(input) == -1) {
							removeGroupItem(input, true);
							repeated_inputs.push(input);
						}
					});
					
					//remove vars with resource_name in ProgrammingTaskUtil.variables_in_workflow
					removeSLAProgrammingTaskVariablesBasedInResource(resource_name);
				}
				
				var repeated_task_ids = [];
				
				for (var resource_name in task_ids_by_resource_name) {
					$.each(task_ids_by_resource_name[resource_name], function(idx, task_id) {
						if (repeated_task_ids.indexOf(task_id) == -1) {
							taskFlowChartObj.TaskFlow.deleteTask(task_id, {confirm: false});
							repeated_inputs.push(task_id);
						}
					});
					
					//remove vars with resource_name in ProgrammingTaskUtil.variables_in_workflow
					removeSLAProgrammingTaskVariablesBasedInResource(resource_name);
				}
				
			}
		}
	}
}

function addSLAProgrammingTaskVariablesBasedInResourceDBTable(db_broker, db_driver, db_type, db_table, resource_name) {
	if (db_table && resource_name) {
		var db_attributes = getLayoutUIEditorWidgetResourceDBAttributes(db_broker, db_driver, db_type, db_table);
		
		if (!$.isEmptyObject(db_attributes))
			for (var attr_name in db_attributes) {
				var key = resource_name + "[" + attr_name + "]";
				
				if (!ProgrammingTaskUtil.variables_in_workflow.hasOwnProperty(key))
					ProgrammingTaskUtil.variables_in_workflow[key] = {};
			}
	}
}

function removeSLAProgrammingTaskVariablesBasedInResource(resource_name) {
	if (resource_name) {
		var variables_in_workflow = {};
		
		for (var key in ProgrammingTaskUtil.variables_in_workflow) {
			var regex = new RegExp("^" + resource_name + "($|\\[)");
			var exists = key.match(regex);
			
			if (!exists)
				variables_in_workflow[key] = ProgrammingTaskUtil.variables_in_workflow[key];
		}
		
		ProgrammingTaskUtil.variables_in_workflow = variables_in_workflow;
	}
}

function createLayoutUIEditorWidgetResourceSLAResourceNamesBasedInResourceBroker(broker_type, broker_data, permissions) {
	var resource_names = [];
	var prefix = null;
	
	switch (broker_type) {
		case "callbusinesslogic":
			prefix = "call_bl_";
			break;
		case "callibatisquery":
			prefix = "call_ibatis_";
			break;
		case "callhibernatemethod":
			prefix = "call_hbn_";
			break;
		case "getquerydata":
			prefix = "get_query_";
			break;
		case "setquerydata":
			prefix = "set_query_";
			break;
		case "callfunction":
			prefix = "call_func_";
			break;
		case "callobjectmethod":
			prefix = "call_method_";
			break;
		case "restconnector":
			prefix = "call_rest_";
			break;
		case "soapconnector":
			prefix = "call_soap_";
			break;
	}
	
	if (prefix) {
		var resource_name = prefix + ("_" + JSON.stringify(broker_data).hashCode()).replace(/-/g, "_");
		resource_names.push(resource_name);
		
		if (permissions) {
			var permissions_hash_code = ("_" + JSON.stringify(permissions).hashCode()).replace(/-/g, "_");
			
			resource_name += permissions_hash_code;
			resource_names.push(resource_name);
		}
	}
	
	return resource_names;
}

function createLayoutUIEditorWidgetResourceSLAResourceNamesBasedInResourceDBTable(action_type, db_driver, db_table, db_table_alias, permissions, data) {
	var resource_names = [];
	
	//first get the rules for the table_alias
	if (db_table_alias && db_table_alias != db_table && action_type != "get_all_options")
		resource_names = createLayoutUIEditorWidgetResourceSLAResourceNamesBasedInResourceDBTable(action_type, db_driver, db_table_alias, null, permissions, data);
	
	var permissions_hash_code = permissions ? ("_" + JSON.stringify(permissions).hashCode()).replace(/-/g, "_") : "";
	var is_default_db_driver = !db_driver || (typeof default_db_driver != "undefined" && default_db_driver == db_driver);
	var db_table_plural = db_table.substr(db_table.length - 1) == "y" ? db_table.substr(0, db_table.length - 1) + "ies" : db_table + "s";
	var db_driver_table = (db_driver ? db_driver + "_" : "") + db_table + permissions_hash_code;
	var db_driver_table_plural = (db_driver ? db_driver + "_" : "") + db_table_plural + permissions_hash_code;
	
	//and then for the table name
	switch (action_type) {
		case "insert": 
			if (is_default_db_driver)
				resource_names.push("insert_" + db_table);
			
			resource_names.push("insert_" + db_driver_table);
			break;
		case "update": 
			if (is_default_db_driver)
				resource_names.push("update_" + db_table);
			
			resource_names.push("update_" + db_driver_table);
			break;
		case "save": 
			if (is_default_db_driver)
				resource_names.push("save_" + db_table);
			
			resource_names.push("save_" + db_driver_table);
			break;
		case "multiple_save": 
			if (is_default_db_driver) {
				resource_names.push("update_all_" + db_table_plural);
				resource_names.push("update_all_" + db_table_plural + "_items");
				resource_names.push("update_all_" + db_table);
				resource_names.push("update_all_" + db_table + "_items");
			}
			
			resource_names.push("update_all_" + db_driver_table);
			resource_names.push("update_all_" + db_driver_table + "_items");
			resource_names.push("update_all_" + db_driver_table_plural);
			resource_names.push("update_all_" + db_driver_table_plural + "_items");
			break;
		case "update_attribute": 
			if (is_default_db_driver)
				resource_names.push("update_" + db_table + "_attribute");
			
			resource_names.push("update_" + db_driver_table + "_attribute");
			break;
		case "insert_update_attribute": 
			if (is_default_db_driver)
				resource_names.push("insert_update_" + db_table + "_attribute");
			
			resource_names.push("insert_update_" + db_driver_table + "_attribute");
			break;
		case "insert_delete_attribute": 
			if (is_default_db_driver)
				resource_names.push("insert_delete_" + db_table + "_attribute");
			
			resource_names.push("insert_delete_" + db_driver_table + "_attribute");
			break;
		case "multiple_insert_delete_attribute": 
			if (is_default_db_driver)
				resource_names.push("multiple_insert_delete_" + db_table + "_attribute");
			
			resource_names.push("multiple_insert_delete_" + db_driver_table + "_attribute");
			break;
		case "delete": 
			if (is_default_db_driver)
				resource_names.push("delete_" + db_table);
			
			resource_names.push("delete_" + db_driver_table);
			break;
		case "multiple_delete": 
			if (is_default_db_driver) {
				resource_names.push("delete_all_" + db_table_plural);
				resource_names.push("delete_all_" + db_table_plural + "_items");
				resource_names.push("delete_all_" + db_table);
				resource_names.push("delete_all_" + db_table + "_items");
			}
			
			resource_names.push("delete_all_" + db_driver_table);
			resource_names.push("delete_all_" + db_driver_table + "_items");
			resource_names.push("delete_all_" + db_driver_table_plural);
			resource_names.push("delete_all_" + db_driver_table_plural + "_items");
			break;
		case "get": 
			if (is_default_db_driver) {
				resource_names.push(db_table);
				resource_names.push("get_" + db_table);
				resource_names.push("get_" + db_table + "_item");
			}
			
			resource_names.push("get_" + db_driver_table);
			resource_names.push("get_" + db_driver_table + "_item");
			resource_names.push(db_driver_table);
			break;
		case "get_all": 
			if (is_default_db_driver) {
				resource_names.push(db_table_plural);
				resource_names.push("get_" + db_table_plural);
				resource_names.push("get_" + db_table_plural + "_items");
				resource_names.push("get_" + db_table + "_items");
			}
			
			resource_names.push("get_" + db_driver_table + "_items");
			resource_names.push("get_" + db_driver_table_plural);
			resource_names.push("get_" + db_driver_table_plural + "_items");
			resource_names.push(db_driver_table_plural);
			break;
		case "count": 
			if (is_default_db_driver) {
				resource_names.push("count_" + db_table_plural);
				resource_names.push("count_" + db_table_plural + "_items");
				resource_names.push("count_" + db_table);
				resource_names.push("count_" + db_table + "_items");
			}
			
			resource_names.push("count_" + db_driver_table);
			resource_names.push("count_" + db_driver_table + "_items");
			resource_names.push("count_" + db_driver_table_plural);
			resource_names.push("count_" + db_driver_table_plural + "_items");
			break;
		case "get_all_options":
			//prepare data and if invalid, set the data with the db_table
			if ($.isPlainObject(data))
				data = [data];
			else if (!$.isArray(data))
				data = [{
					table: db_table,
					table_alias: db_table_alias
				}];
			
			if ($.isArray(data)) {
				//if data if invalid, set the data with the db_table
				if (!$.isPlainObject(data[0]) || $.isEmptyObject(data[0]))
					data = [{
						table: db_table,
						table_alias: db_table_alias
					}];
				else if (!data[0]["table"]) {
					data[0]["table"] = db_table;
					data[0]["table_alias"] = db_table_alias;
				}
				
				//prepare names for table alias
				if (data[0]["table_alias"]) {
					var suffix = data[0]["table_alias"] + (data[0]["attribute"] ? "_" + data[0]["attribute"] : "") + permissions_hash_code + "_options";
					
					if (is_default_db_driver) {
						resource_names.push("get_" + suffix);
						resource_names.push(suffix);
					}
					
					resource_names.push("get_" + (db_driver ? db_driver + "_" : "") + suffix);
					resource_names.push((db_driver ? db_driver + "_" : "") + suffix);
				}
				
				//prepare names for table name
				if (data[0]["table_alias"] != data[0]["table"]) {
					var suffix = data[0]["table"] + (data[0]["attribute"] ? "_" + data[0]["attribute"] : "") + permissions_hash_code + "_options";
					
					if (is_default_db_driver) {
						resource_names.push("get_" + suffix);
						resource_names.push(suffix);
					}
					
					resource_names.push("get_" + (db_driver ? db_driver + "_" : "") + suffix);
					resource_names.push((db_driver ? db_driver + "_" : "") + suffix);
				}
			}
			break;
	}
	
	return resource_names;
}

function enableMainSettingsParseHtml() {
	var parser = $(".regions_blocks_includes_settings > .advanced_settings > .parser");
	var select = parser.find(" > .parse_html > select");
	
	if (select.val() == 0) {
		select.val(1);
		select.trigger("change");
		
		parser.find(" > .execute_sla > select").val("");
		parser.find(" > .parse_hash_tags > select").val("");
		parser.find(" > .parse_ptl > select").val("");
		parser.find(" > .add_my_js_lib > select").val("");
		parser.find(" > .add_widget_resource_lib > select").val("");
		parser.find(" > .filter_by_permission > select").val("");
		parser.find(" > .init_user_data > select").val("");
	}
}

/* edit_query.js FUNCTIONS - if not exit */

if (typeof getDBTables != "function")
	function getDBTables(db_broker, db_driver, type) {
		var db_tables = db_brokers_drivers_tables_attributes[db_broker] && db_brokers_drivers_tables_attributes[db_broker][db_driver] ? db_brokers_drivers_tables_attributes[db_broker][db_driver][type] : null;
		
		if (jQuery.isEmptyObject(db_tables)) {
			$.ajax({
				type : "post",
				url : get_broker_db_data_url,
				data : {"db_broker" : db_broker, "db_driver" : db_driver, "type" : type},
				dataType : "json",
				success : function(data, textStatus, jqXHR) {
					if(data) {
						db_tables = {};
						for (var i = 0; i < data.length; i++) {
							db_tables[ data[i] ] = {};
						}
						
						db_brokers_drivers_tables_attributes[db_broker][db_driver][type] = db_tables;
					}
				},
				error : function(jqXHR, textStatus, errorThrown) { 
					if (jqXHR.responseText)
						StatusMessageHandler.showError(jqXHR.responseText);
				},
				async: false,
			});
		}
		
		return db_tables;
	}

if (typeof getDBTableAttributesDetailedInfo != "function")
	function getDBTableAttributesDetailedInfo(db_broker, db_driver, type, db_table) {
		var parts = db_table.split(" ");
		db_table = parts[0];
		
		var detailed_info;
		
		$.ajax({
			type : "post",
			url : get_broker_db_data_url,
			data : {"db_broker" : db_broker, "db_driver" : db_driver, "type" : type, "db_table" : db_table, "detailed_info" : 1},
			dataType : "json",
			success : function(data, textStatus, jqXHR) {
				detailed_info = data ? data : {};
				
				if ($.isPlainObject(detailed_info) && !$.isEmptyObject(detailed_info) && !db_brokers_drivers_tables_attributes[db_broker][db_driver][type].hasOwnProperty(db_table)) {
					db_brokers_drivers_tables_attributes[db_broker][db_driver][type][db_table] = [];
					
					for (var attr_name in detailed_info)
						db_brokers_drivers_tables_attributes[db_broker][db_driver][type][db_table].push(attr_name);
				}
			},
			error : function(jqXHR, textStatus, errorThrown) { 
				if (jqXHR.responseText)
					StatusMessageHandler.showError(jqXHR.responseText);
			},
			async: false,
		});
		
		return detailed_info;
	}

