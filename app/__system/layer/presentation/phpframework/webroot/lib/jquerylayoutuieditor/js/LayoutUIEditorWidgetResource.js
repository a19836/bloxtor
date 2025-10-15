/*
 * Copyright (c) 2007 PHPMyFrameWork - Joao Paulo Lopes Pinto -- http://jplpinto.com
 * The use of this code must comply with the Bloxtor framework license.
 */

function LayoutUIEditorWidgetResource(ui_creator) {
	/* #ADD_SECURITY_CODE_HERE# */  //Important: do not remove this comment because it will be used by the other/scripts/obfuscate_js_files.php to only allow this code in the allowed domains.
	
	var me = this;
	var selected_widget = null; //saves the selected widget in the LayoutUIEditor
	var update_field_in_selected_widget_active = true; //if enable gets widget settings and update them in the html element.
	var on_update_field_in_selected_widget_timeout_id = null;
	var on_update_click_event_field_timeout_id = null;
	var internal_cached_data = {}; //used to save data in cache used in this cache. This is an internal cache use only.
	var temp_main_widget_id = null;
	var ignore_main_widget_error = false;
	var saved_table_alias = {};
	
	/* PUBLIC PROPERTIES */
	
	me.options = {
		toggle_choose_db_table_attribute_popup_func: null, //popup to choose a db attribute.
		toggle_choose_widget_resource_popup_func: null, //popup to choose a resource.
		toggle_choose_widget_resource_value_attribute_popup_func: null, //popup to choose a resource attribute. This element will be appended to the clicked element. Internal use only. Besides the attribute in this popup, the user should choose the action type and then the parameters according with that action. Example: If he chooses the action "GET ITEM" then he will need to fill the values for the correspondent PK. If he chooses the action "GET ITEMS", he will need to fill the row index, etc...
		on_choose_event_func: null, //popup to choose an event.
		
		get_db_brokers_func: null, //sync func: returns array with available db brokers
		get_db_drivers_func: null, //sync func: returns array with available db drivers for a broker
		get_db_tables_func: null, //sync func: returns array with available tables for a driver
		get_db_attributes_func: null, //sync func: returns an obj with attributes for a table. Each key is the attribute name and the value is the atribute properties
		get_resources_references_func: null, //sync func: returns array with available resources reference
		get_user_types_func: null, //sync func: returns array with available user types
		get_php_numeric_types_func: null,//sync func: returns array with numeric types
		get_db_numeric_types_func: null,//sync func: returns array with numeric types
		get_db_blob_types_func: null,//sync func: returns array with binary types
		get_internal_attribute_names_func: null,//sync func: returns array with internal attribute names
		
		add_sla_resource_func: null, //async func: add to the SLA panel (if exists), an external resource with a specific resource name.
		remove_sla_resource_func: null, //async func: remove the external resource from the SLA panel (if exists), based in the resource name.
		create_resource_names_func: null, //sync func: return an array with possible names of a resource based in the parameters
		
		user_module_installed: true,
	};
	
	/* PUBLIC FUNCTIONS - ui_creator callbacks */
	
	//this method is called in the LayoutUIEditor.js
	me.init = function() {
		//add new sub-option to the widget header
		var ui = ui_creator.getUI();
		var template_widgets = ui_creator.getTemplateWidgets();
		var widget_header = template_widgets.children("." + ui_creator.options.widget_header_class);
		var sub_option = widget_header.find(" > .options > .other-options > .sub-options > .props");
		
		var load_resource_option = $('<li class="option load-widget-resource" title="Load Resource"><i class="zmdi zmdi-edit"></i>Load Resource</li>');
		var show_resource_attribute_option = $('<li class="option choose-widget-resource-attribute" title="Show Resource Attribute"><i class="zmdi zmdi-edit"></i>Show Resource Attribute</li>');
		
		load_resource_option.on("click", function(event) {
			event.preventDefault();
	  		event.stopPropagation();
	  		
	  		//show menu settings panel
			ui.find(" > .options .show-settings").click();
			
			//click in the load resource icon to open popup
			var menu_settings = ui_creator.getMenuSettings();
			var settings_widget = menu_settings.find(" > .settings-tabs > ul > li.settings-tab-widget > a").trigger("click");
			var settings_widget = menu_settings.children(".settings-widget");
			settings_widget.addClass("group-open");
			settings_widget.children("ul").show();
			
			var widget_resources = settings_widget.find(" > ul > .widget-resources");
			widget_resources.addClass("group-open");
			widget_resources.children("ul").show();
			
			var widget_resources_load = widget_resources.find(" > ul > .widget-resources-type.widget-resources-load");
			widget_resources_load.addClass("group-open");
			widget_resources_load.children("ul").show();
			
			var icon = widget_resources_load.find(" > ul > .widget-resource").first().find(" > ul > .widget-resource-reference > .choose-widget-resource");
			
			if (icon.length == 0) {
				widget_resources_load.find(" > .group-title > .widget-group-item-add").trigger("click");
				icon = widget_resources_load.find(" > ul > .widget-resource").first().find(" > ul > .widget-resource-reference > .choose-widget-resource");
			}
			
			if (icon[0]) {
				icon.trigger("click");
				
				//scroll until icon
				var ms_top = menu_settings.offset().top;
				var i_top = icon.offset().top + menu_settings.scrollTop();
				var ms_h = parseInt(menu_settings.height() / 2);
				
				menu_settings.scrollTop(i_top - ms_top - ms_h); //"- ms_h" to center the icon at the middle of the menu_settings
			}
		});
		
		show_resource_attribute_option.on("click", function(event) {
			event.preventDefault();
	  		event.stopPropagation();
	  		
	  		//show menu settings panel
			ui.find(" > .options .show-settings").click();
			
			//click in the display resource attribute icon to open popup
			var menu_settings = ui_creator.getMenuSettings();
			var settings_widget = menu_settings.find(" > .settings-tabs > ul > li.settings-tab-widget > a").trigger("click");
			var settings_widget = menu_settings.children(".settings-widget");
			settings_widget.addClass("group-open");
			settings_widget.children("ul").show();
			
			var widget_display_resource_value = settings_widget.find(" > ul > .widget-display-resource-value");
			widget_display_resource_value.addClass("group-open");
			widget_display_resource_value.children("ul").show();
			
			var icon = widget_display_resource_value.find(" > ul > .widget-display-resource-value-resource-attribute > .choose-widget-resource-attribute");
			icon.trigger("click");
			
			//scroll until icon
			var ms_top = menu_settings.offset().top;
			var i_top = icon.offset().top + menu_settings.scrollTop();
			var ms_h = parseInt(menu_settings.height() / 2);
			
			menu_settings.scrollTop(i_top - ms_top - ms_h); //"- ms_h" to center the icon at the middle of the menu_settings
		});
		
		sub_option.after(show_resource_attribute_option);
		sub_option.after(load_resource_option);
	};
	
	me.onBeforeDeleteTemplateWidget = function(widget) {
		if (typeof me.options.remove_sla_resource_func == "function") {
			//get resources to remove
			var resources_name = getWidgetResourcesName(widget);
			var children = widget.find("[data-widget-resources]");
			
			$.each(children, function(idx, child) {
				var names = getWidgetResourcesName( $(child) );
				resources_name = resources_name.concat(names);
			});
			
			this.resources_name_to_delete_after_widget_removal = resources_name;
		}
	};
	
	me.onAfterDeleteTemplateWidget = function() {
		//remove resources
		var resources_name = this.resources_name_to_delete_after_widget_removal;
		
		if (resources_name)
			removeSLAResourcesIfNotUsedAnymore(resources_name);
		
		this.resources_name_to_delete_after_widget_removal = null;
		
		//onChangeSelectedWidget(); //This is already called in the ui_creator.deleteTemplateWidget method
	};
	
	me.openMenuSettings = function(widget, force) {
		update_field_in_selected_widget_active = false;
		
		widget = widget instanceof jQuery ? widget : $(widget);
		var menu_settings = ui_creator.getMenuSettings();
		var widget_settings = menu_settings.children(".settings-widget");
		
		if (!widget_settings[0]) {
			var html = getMainWidgetSettingsHtml();
			html = $(html);
			
			menu_settings.children(".settings-properties").after(html);
			prepareMenuSettingsEvents(html);
			
			var html = getMainWidgetSettingsTabHtml();
			html = $(html);
			
			var settings_tabs = menu_settings.find(".settings-tabs");
			settings_tabs.find(" > ul > li.settings-tab-properties").after(html);
			settings_tabs.tabs("refresh");
			ui_creator.initMenuSettingsTab(html.children("a"));
			
			widget_settings = menu_settings.children(".settings-widget");
		}
		
		//prepare html based in the selected widget if different from previous one
		if (widget[0] != selected_widget || force) {
			selected_widget = widget[0];
			prepareSelectedWidgetSettings(widget, widget_settings);
		}
		
		update_field_in_selected_widget_active = true;
		
		//hide fields that don't have callbacks
		if (typeof me.options.toggle_choose_db_table_attribute_popup_func != "function")
			widget_settings.find(".choose-table-attribute").hide();
		
		if (typeof me.options.toggle_choose_widget_resource_popup_func != "function")
			widget_settings.find(".choose-widget-resource").hide();
		
		if (typeof me.options.toggle_choose_widget_resource_value_attribute_popup_func != "function")
			widget_settings.find(".choose-widget-resource-attribute").hide();
		
		if (typeof ui_creator.options.on_choose_event_func != "function")
			widget_settings.find(".choose-event").hide();
		
		if (typeof ui_creator.options.on_choose_page_url_func != "function")
			widget_settings.find(".choose-page").hide();
	};
	
	me.onBeforeParseWidgetSettings = function(widget, widget_settings) {
		//For now, do nothing, but it could be useful in the future...
	};
	
	me.onAfterParseWidgetSettings = function(widget, widget_settings) {
		//parse attributes html and remove all the reserved attributes
		if (widget_settings["attributes"]) {
			var reserved_attributes = me.getWidgetReservedAttributes();
			var attributes = MyHtmlBeautify.getAttributes(widget_settings["attributes"]);
			var html = "";
			
			for (var i = 0, t = attributes.length; i < t; i++) {
				var attribute = attributes[i];
				
				if (attribute["name"].length > 0 && reserved_attributes.indexOf(attribute["name"].toLowerCase()) == -1)
					html += (html ? " ": "") + attribute["name"] + (attribute["raw_value"] ? "=" + attribute["raw_value"] : "");
			}
			
			//console.log("html:"+html);
			widget_settings["attributes"] = html;
		}
	};
	
	me.loadMenuSettings = function(widget) {
		update_field_in_selected_widget_active = false;
		
		loadWidgetSettingsData(widget);
		
		update_field_in_selected_widget_active = true;
	};
	
	me.addExtraMenuSettingsContextMenu = function(context_menu_ul) {
		if (typeof me.options.toggle_choose_widget_resource_value_attribute_popup_func == "function")
			context_menu_ul.append('<li class="choose_variable" title="Choose an attribute from a resource. If resource does not exists yet, the system will create and load it automatically"><a onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.chooseMenuSettingsContextMenuFieldResourceValueAttribute(this, true, event)">Choose Resource Attribute</a></li>'
							 + '<li class="choose_variable" title="Choose an attribute from an existing resource. The resource must be already created and loaded."><a onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.chooseMenuSettingsContextMenuFieldResourceValueAttribute(this, false, event)">Choose Existing Resource Attribute</a></li>');
		
		if (typeof me.options.toggle_choose_db_table_attribute_popup_func == "function")
			context_menu_ul.append('<li class="choose_table_attribute" title="Choose Table attribute"><a onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.chooseMenuSettingsContextMenuFieldTableAttribute(this, event)">Choose Existing Table attribute</a></li>');
	};
	
	me.chooseMenuSettingsContextMenuFieldTableAttribute = function(elm, event) {
		if (typeof MyContextMenu == "object") {
			var field = MyContextMenu.getSelectedEventTarget();
			
			if (field && typeof me.options.toggle_choose_db_table_attribute_popup_func == "function") {
				me.options.toggle_choose_db_table_attribute_popup_func(field, event, function(attr_name) {
					//set new attribute in field
					field = $(field);
					field.val("#" + attr_name + "#");
					
					//update UI if apply
					if (field[0].hasAttribute("onBlur"))
						field.trigger("blur");
					else if (field[0].hasAttribute("onChange"))
						field.trigger("change");
				});
			}
			
			MyContextMenu.hideContextMenu( $(elm).closest(".mycontextmenu") );
		}
	};
	
	me.chooseMenuSettingsContextMenuFieldResourceValueAttribute = function(elm, create_resource_if_not_exists, event) {
		if (typeof MyContextMenu == "object") {
			var field = MyContextMenu.getSelectedEventTarget();
			
			if (field && typeof me.options.toggle_choose_widget_resource_value_attribute_popup_func == "function") {
				//check if field is the widget-display-resource-value-resource-attribute
				if ($(field).parent().hasClass("widget-display-resource-value-resource-attribute"))
					me.toggleChooseWidgetResourceValueAttributePopup(field, event);
				else {
					var widget = $(selected_widget);
					
					me.options.toggle_choose_widget_resource_value_attribute_popup_func(elm, widget, function(resource_name, resource_index, resource_attribute) {
						if (!resource_attribute && !$.isNumeric(resource_attribute)) {
							ui_creator.showError("Attribute cannot be undefined!");
							return false;
						}
						else if (!resource_name) {
							ui_creator.showError("Resource cannot be undefined!");
							return false;
						}
						else {
							var li = $(elm).parent();
							var ul = li.parent();
							var resource_index_exists = resource_index || $.isNumeric(resource_index);
							
							//set new attribute in field
							field = $(field);
							field.val(resource_name); //set resource_name to field, temporary only to call the prepareResourceReferenceFromWidgetResourceValueSetting. we set the resource_attribute after
							
							//add a new load resource to resources element
							var ret = create_resource_if_not_exists ? prepareResourceReferenceFromWidgetResourceValueSetting(field[0]) : null;
							var exists = ret && ret["exists"] && ret["is_parent"];
							var prefix = exists ? "" : "Resources[" + resource_name + "]";
							
							//prepare image_prefix if resource_attribute is a binary
							//var image_prefix = false && field.is("input") && field.attr("name") == "src" ? 'data:image/*;base64, ' : "";
							var image_prefix = ""; //I cannot know if a attribute is a binary or not, so this needs to be always empty
							
							if (resource_index_exists)
								field.val(image_prefix + "#" + prefix + "[" + resource_index + "][" + resource_attribute + "]#");
							else if (prefix)
								field.val(image_prefix + "#" + prefix + "[" + resource_attribute + "]#");
							else
								field.val(image_prefix + "#" + resource_attribute + "#");
							
							return true;
						}
					});
				}
			}
			
			MyContextMenu.hideContextMenu( $(elm).closest(".mycontextmenu") );
		}
	};
	
	me.onBeforeSaveSettingsField = function(field, widget, status) {
		//For now, do nothing, but it could be useful in the future...
		
		return status;
	};
	
	me.onAfterSaveSettingsField = function(field, widget, status) {
		/* No need this anymore bc I add some reserved attribute names in the ui_creator.
		if (update_field_in_selected_widget_active) {
			var menu_settings = ui_creator.getMenuSettings();
			var widget_settings = menu_settings.children(".settings-widget");
			var sections = widget_settings.children("ul").children(".group.widget-resources, .group.widget-display-resource-value, .group.widget-dependencies, .group.widget-permissions, .group.widget-properties");
			
			$.each(sections, function(idx, section) {
				updateFieldInSelectedWidget(section);
			});
			
			sections.filter(".widget-properties").find(" > ul > .widget-property-widget-type > select").trigger("change");
		}*/
		
		return status;
	};
	
	me.getWidgetSingleAttributes = function(widget) {
		return getDefaultWidgetTypesAttributes();
	};
	
	me.getWidgetReservedAttributes = function(widget) {
		var attributes = me.getWidgetSingleAttributes();
		attributes.push("data-widget-props");
		attributes.push("data-widget-permissions");
		attributes.push("data-widget-resource-value");
		attributes.push("data-widget-resources");
		attributes.push("data-widget-resources-load");
		attributes.push("data-widget-item-resources-load");
		
		if (widget && widget.is("[data-widget-list], [data-widget-form], form"))
			attributes.push("data-widget-pks-attrs");
		
		return attributes;
	};
	
	me.getWidgetAttributesToIgnore = function(widget) {
		return me.getWidgetReservedAttributes(widget);
	};
	
	/* PUBLIC FUNCTIONS - ui_creator.LayoutUIEditorWidgetResource ui handlers */
	
	//refresh the .select-existent-widget-resources select boxes with the available defined resources names/references.
	me.refreshWidgetExistentResourcesReferences = function(elm, event) {
		var widget_settings = $(elm).parent().closest(".settings-widget");
		var elms = widget_settings.find(".select-existent-widget-resources");
		updateWidgetExistentResourcesReferences(elms, true);
	};
	
	//refresh the .select-existent-user-types select boxes with the user types.
	me.refreshWidgetExistentUserTypes = function(elm, event) {
		var widget_settings = $(elm).parent().closest(".settings-widget");
		var elms = widget_settings.find(".select-existent-user-types");
		updateWidgetExistentUserTypes(elms, true);
	};
	
	//refresh the .select-existent-widget-ids select boxes with the available defined widgets ids.
	me.refreshWidgetExistentWidgetIds = function(elm, event) {
		var widget_settings = $(elm).parent().closest(".settings-widget");
		var elms = widget_settings.find(".select-existent-widget-ids");
		updateWidgetExistentWidgetIds(elms, true);
	};
	
	//refresh the .select-existent-widget-popups select boxes with the available defined popup ids.
	me.refreshWidgetExistentPopupIds = function(elm, event) {
		var widget_settings = $(elm).parent().closest(".settings-widget");
		var elms = widget_settings.find(".select-existent-widget-popups");
		updateWidgetExistentPopupIds(elms, true);
	};
	
	me.onClickWidgetGroupTitle = function(elm, event) {
		elm = $(elm);
		var p = elm.parent();
		p.children("ul").toggle("slow");
		p.toggleClass("group-open");
		
		elm.children(".toggle").toggleClass("zmdi-caret-right zmdi-caret-down");
	};
	
	me.onClickWidgetGroupTitleWithDisplay = function(elm, event) {
		var is_active = $(elm).find("input").is(":checked");
		
		if (!is_active) {
			ui_creator.showMessage("You must enable this property first!");
			
			setTimeout(function() {
				me.onClickWidgetGroupTitle(elm, event);
			}, 300);
		}
	};
	
	me.onClickWidgetGroupTitleDisplay = function(elm, event) {
		event && event.stopPropagation();
	};
	
	me.onChangeWidgetGroupTitleDisplay = function(elm, event) {
		event && event.stopPropagation();
		
		elm = $(elm);
		var p = elm.parent().closest(".group-title");
		var li = p.parent();
		
		if (elm.is(":checked")) {
			if (!li.hasClass("group-open"))
				me.onClickWidgetGroupTitle(p[0], event);
			
			li.removeClass("group-disabled");
		}
		else {
			if (li.hasClass("group-open"))
				me.onClickWidgetGroupTitle(p[0], event);
			
			li.addClass("group-disabled");
		}
		
		me.onChangeField(elm, event);
	};
	
	me.onChangeField = function(elm, event) {
		updateFieldInSelectedWidget(elm);
	};
	
	me.onBlurField = function(elm, event) {
		elm = $(elm);
		var p = elm.parent();
		var value = elm.val();
		var force_refresh = elm.data("force_refresh");
		
		if (p.hasClass("with-extra-callbacks"))
			me.convertWidgetCallbackInExtraCallbacks(p);
		else if (p.hasClass("with-extra-events"))
			me.convertWidgetEventInExtraEvents(p);
		
		var parsed_value = elm.val();
		var saved_value = elm.data("saved_value");
		//console.log("onBlurField:"+elm.val());
		//console.log(value+" != "+saved_value);
		
		//only update if value is different
		if (value != saved_value || parsed_value != saved_value || force_refresh) {
			elm.data("force_refresh", null);
			elm.data("saved_value", parsed_value);
			//console.log("save new value!");
			
			//console.log(elm);
			updateFieldInSelectedWidget(elm);
		}
	};
	
	me.onFocusField = function(elm, event) {
		elm = $(elm);
		elm.data("saved_value", elm.val());
		//console.log("onFocusField:"+elm.val());
	};
	
	//updates the click event field to the widget-properties on-click field
	me.onBlurWidgetClickEvent = function(elm, event) {
		on_update_click_event_field_timeout_id && clearTimeout(on_update_click_event_field_timeout_id);
		on_update_click_event_field_timeout_id = null;
		
		elm = $(elm);
		var p = elm.parent();
		var value = elm.val();
		var force_refresh = elm.data("force_refresh");
		
		//convert value to multiple lines
		if (p.is(".widget-property-button-on-click.with-extra-events") && event.type == "blur")
			me.convertWidgetEventInExtraEvents(p);
		
		var parsed_value = elm.val();
		var saved_value = elm.data("saved_value");
		
		if (value != saved_value || parsed_value != saved_value || force_refresh) {
			elm.data("force_refresh", null);
			elm.data("saved_value", parsed_value);
			
			me.onChangeWidgetClickEvent(elm[0], event);
		};
	};
	
	me.onKeyUpWidgetClickEvent = function(elm, event) {
		on_update_click_event_field_timeout_id && clearTimeout(on_update_click_event_field_timeout_id);
		
		on_update_click_event_field_timeout_id = setTimeout(function() {
			me.onBlurWidgetClickEvent(elm, event);
		}, 500);
	};
	
	me.onChangeWidgetClickEvent = function(elm, event) {
		elm = $(elm);
		var p = elm.parent();
		
		//get main parent
		if (p.parent().hasClass("extra-events")) {
			p = p.parent().parent();
			elm = p.hasClass("select-shown") ? p.children("select") : p.children("input");
		}
		
		var value = me.convertWidgetEventWithExtraEventsInString(p); //get value with extra events
		var menu_settings = ui_creator.getMenuSettings();
		var widget_settings = p.closest(".settings-widget");
		var with_extra_events = p.hasClass("with-extra-events");
			
		if (p.hasClass("settings-onclick")) {
			var p2 = menu_settings.find(" > .settings-widget > ul > .widget-properties > ul > .widget-property-button-on-click");
			
			//reset fields
			if (with_extra_events) {
				p2.find(" > .extra-events > li").remove(); //reset fields
				
				loadWidgetSettingValueIntoSwappedField(p2, value); //set new value
				me.convertWidgetEventInExtraEvents(p2); //convert new value if apply
			}
			else
				loadWidgetSettingValueIntoSwappedField(p2, value);
		}
		else if (p.hasClass("widget-property-button-on-click")) {
			var p2 = menu_settings.find(" > .settings-events > ul > .settings-onclick");
			
			if (with_extra_events) {
				p2.find(" > .extra-events > li").remove(); //reset fields
				
				p2.find("input").val(value); //set new value
				me.convertWidgetEventInExtraEvents(p2); //convert new value if apply
			}
			else
				p2.find("input").val(value);
		}
		
		me.onChangeField(elm, event);
	};
	
	me.preparedAddedExtraClickEvent = function(elm, event) {
		elm = $(elm);
		var p = elm.parent().closest(".with-extra-events");
		var ul = p.children(".extra-events");
		var li = ul.children("li").last();
		
		li.find(".clear-user-input").click(function(ev) {
			me.onBlurWidgetClickEvent(li.children("input")[0], ev);
		});
		
		li.find(".remove-extra-event").click(function(ev) {
			me.onChangeWidgetClickEvent(p.children("input")[0], ev);
		});
	};
	
	me.swapInputAndSelectFieldsOnClickEvent = function(elm, event) {
		on_update_click_event_field_timeout_id && clearTimeout(on_update_click_event_field_timeout_id);
		on_update_click_event_field_timeout_id = null;
		
		me.swapInputAndSelectFields(elm, event);
		
		//if onKeyUp/onChange event trigger it
		var p = $(elm).parent();
		
		if (p.hasClass("select-shown"))
			me.onChangeWidgetClickEvent(p.children("select")[0], event);
		else
			me.onChangeWidgetClickEvent(p.children("input")[0], event);
	};
	
	me.onBlurWidgetId = function(elm, event) {
		elm = $(elm);
		var value = elm.val();
		var saved_value = elm.data("saved_value");
		
		//only update if value is different
		if (value != saved_value) {
			me.onBlurField(elm, event);
			
			replaceDependentWidgetsId(saved_value, value);
		}
	};
	
	me.onBeforeChangeWidgetType = function(elm, event) {
		elm = $(elm);
		elm.data("previous_value", elm.val());
	};
	
	me.onChangeWidgetType = function(elm, event) {
		elm = $(elm);
		var value = elm.val();
		var previous_value = elm.data("previous_value");
		var widget_types_attributes = getDefaultWidgetTypesAttributes();
		var widget = $(selected_widget);
		var is_new_widget = true;
		
		value = $.isArray(value) ? value : [value];
		
		for (var i = 0, t = widget_types_attributes.length; i < t; i++) {
			var widget_type_attribute = widget_types_attributes[i];
			
			if (widget[0].hasAttribute(widget_type_attribute)) {
				is_new_widget = false;
				
				if (value.indexOf(widget_type_attribute) == -1)
					widget.removeAttr(widget_type_attribute);
			}
		}
		
		for (var i = 0, t = value.length; i < t; i++)
			if (value[i] && !widget[0].hasAttribute(value[i]))
				widget.attr(value[i], "");
		
		//reload widget settings
		me.openMenuSettings(widget, true);
		me.loadMenuSettings(widget);
		
		//remove anoying messages bc the db broker, driver, table and others are not selected yet
		ui_creator.removeMessages();
		
		//prepare widget default html
		prepareWidgetWithDefaultHtml(elm, widget);
		
		//remove anoying messages bc the db broker, driver, table and others are not selected yet
		ui_creator.removeMessages();
		
		//update field properties and others if apply
		updateFieldInSelectedWidget(elm);
		
		//load it again so it can load the pks_attrs_names and shown_attrs_names
		me.loadMenuSettings(widget);
		
		//remove anoying messages bc the db broker, driver, table and others are not selected yet
		ui_creator.removeMessages();
		
		//if new widget, set some default settings. Note that this must be here bc nothing was added yet, bc there are no selected db broker, driver and table.
		var widget_properties_ul = elm.parent().closest(".widget-properties").children("ul");
		
		if (is_new_widget) {
			//activating the "view" settings.
			if (widget.is("[data-widget-group-list], [data-widget-group-form], [data-widget-list], [data-widget-form]")) {
				var widget_properties_viewable = widget_properties_ul.children(".widget-property-viewable");
				widget_properties_viewable.children("input[type=checkbox]").prop("checked", true).attr("checked", "checked");
			}
			
			//setting table as default in the widget-property-list-type settings.
			if (widget.is("[data-widget-group-list]"))
				widget_properties_ul.find(" > .widget-property-list-type select").val("table");
			
			if (widget.is("[data-widget-html-node]"))
				widget_properties_ul.parent().closest(".settings-widget").find(".widget-resources .widget-resources-load .widget-resource .widget-resource-parse-callback input").val("MyWidgetResourceLib.FieldHandler.filterResourceHtml");
		}
		else if (previous_value != value && widget.is("[data-widget-group-list]")) { //if not a new widget but we changed the widget-type to data-widget-group-list
			widget_properties_ul.find(" > .widget-property-list-type select").val("table");
		}
	};
	
	me.onChangePermissionField = function(elm, event) {
		me.onChangeField(elm, event);
		
		var widget = $(selected_widget);
		prepareWidgetWithPermissionsHtml(elm, widget);
	};
	
	me.onBlurPermissionField = function(elm, event) {
		elm = $(elm);
		var value = elm.val();
		var saved_value = elm.data("saved_value");
		
		me.onBlurField(elm[0], event);
		
		//only update if value is different
		if (value != saved_value) {
			var widget = $(selected_widget);
			prepareWidgetWithPermissionsHtml(elm, widget);
		}
	};
	
	me.swapInputAndSelectPermissionFields = function(elm, event) {
		me.swapInputAndSelectFields(elm, event);
		
		var p = $(elm).parent();
		var input = p.children("input");
		var select = p.children("select");
		
		if (input.val() != select.val()) {
			var widget = $(selected_widget);
			
			if (p.hasClass("select-shown") != "none")
				prepareWidgetWithPermissionsHtml(select[0], widget);
			else
				prepareWidgetWithPermissionsHtml(input[0], widget);
		}
	};
	
	me.addWidgetResourceReference = function(elm, event) {
		var prefix = $(elm).parent().closest("li").children("ul").attr("prefix");
		var html = getWidgetResourceReferenceSettingsHtml(prefix);
		
		var item = addItem(elm, event, html);
		item.addClass("selected-widget-setting");
		
		item.find("input").attr("onBlur", ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangePermissionField(this, event)');
		item.find("select").attr("onChange", ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onBlurPermissionField(this, event)');
		item.find(".swap-input-select").attr("onClick", ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.swapInputAndSelectPermissionFields(this, event)');
		
		prepareMenuSettingsEvents(item);
		
		return item;
	};
	me.removeWidgetResourceReference = function(elm, event) {
		removeItem(elm, event, "Do you wish to remove this reference?");
	};
	
	me.addWidgetValue = function(elm, event) {
		var prefix = $(elm).parent().closest("li").children("ul").attr("prefix");
		var html = getWidgetValueSettingsHtml(prefix);
		
		var item = addItem(elm, event, html);
		item.addClass("selected-widget-setting");
		
		item.find("input").attr("onBlur", ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangePermissionField(this, event)');
		
		prepareMenuSettingsEvents(item);
		
		return item;
	};
	me.removeWidgetValue = function(elm, event) {
		removeItem(elm, event, "Do you wish to remove this value?");
	};
	
	me.addWidgetUserType = function(elm, event) {
		var prefix = $(elm).parent().closest("li").children("ul").attr("prefix");
		var html = getWidgetUserTypeSettingsHtml(prefix);
		
		var item = addItem(elm, event, html);
		item.addClass("selected-widget-setting");
		
		item.find("input").attr("onBlur", ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangePermissionField(this, event)');
		item.find("select").attr("onChange", ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onBlurPermissionField(this, event)');
		
		prepareMenuSettingsEvents(item);
		
		return item;
	};
	me.removeWidgetUserType = function(elm, event) {
		removeItem(elm, event, "Do you wish to remove this user type?");
	};
	
	me.addWidgetId = function(elm, event) {
		event && event.stopPropagation();
		
		var prefix = $(elm).parent().closest("li").children("ul").attr("prefix");
		var html = getWidgetIdSettingsHtml(prefix);
		
		var item = addItem(elm, event, html);
		item.addClass("selected-widget-setting");
		prepareMenuSettingsEvents(item);
		
		return item;
	};
	
	me.removeWidgetId = function(elm, event) {
		removeItem(elm, event, "Do you wish to remove this dependency?");
	};
	
	me.addWidgetAvailableValue = function(elm, event) {
		var type = $(elm).parent().closest("li").children("select").val();
		
		return type == "resource" ? me.addWidgetAvailableResourceValue(elm, event) : me.addWidgetAvailableStaticValue(elm, event);
	};
	
	me.addWidgetAvailableStaticValue = function(elm, event) {
		var li = $(elm).parent().closest("li");
		var prefix = li.children("ul").attr("prefix");
		var html = getWidgetAvailableValueSettingsHtml("", prefix);
		
		var item = addItem(elm, event, html);
		item.addClass("selected-widget-setting");
		prepareMenuSettingsEvents(item);
		
		return item;
	};
	
	me.addWidgetAvailableResourceValue = function(elm, event) {
		var li = $(elm).parent().closest("li");
		var prefix = li.children("ul").attr("prefix");
		var html = getWidgetAvailableValueSettingsHtml("resource", prefix);
		
		var item = addItem(elm, event, html);
		item.addClass("selected-widget-setting");
		prepareMenuSettingsEvents(item);
		
		return item;
	};
	
	me.removeWidgetAvailableValue = function(elm, event) {
		removeItem(elm, event, "Do you wish to remove this item?");
	};
	
	me.removeWidgetAvailableValues = function(elm, event) {
		if (confirm("Do you wish to remove all the defined available values?")) {
			var li = $(elm).parent().closest("li");
			var ul = li.children("ul");
			
			ul.children("li:not(.empty-items)").remove();
			
			if (ul.children("li:not(.empty-items)").length == 0)
				ul.children(".empty-items").show();
			
			updateFieldInSelectedWidget(elm);
		}
	};
	
	me.changeWidgetResourceType = function(elm, event) {
		elm = $(elm);
		
		if (elm.val() == "url")
			elm.parent().closest(".widget-resource").addClass("widget-resource-url");
		else
			elm.parent().closest(".widget-resource").removeClass("widget-resource-url");
	};
	
	me.swapInputAndSelectFields = function(elm, event) {
		elm = $(elm);
		var p = elm.parent();
		var input = p.children("input");
		var select = p.children("select");
		
		p.toggleClass("select-shown");
		
		if (input.val() != select.val())
			updateFieldInSelectedWidget(elm);
	};
	
	me.toggleWidgetAdvancedOptions = function(elm, event) {
		event && event.stopPropagation();
		
		$(elm).parent().closest("li").toggleClass("widget-advanced-options");
	};
	
	me.addWidgetResourceType = function(elm, event) {
		event && event.stopPropagation();
		
		var resources_type = prompt("Type the 'Resources Type' name you wish: (spaces not allowed)");
		
		if (typeof resources_type != "string") {
			ui_creator.showError("Name not allowed! Please type another one...");
			return;
		}
		
		resources_type = resources_type.replace(/\s+/, "");
		
		if (resources_type) {
			var menu_settings = ui_creator.getMenuSettings();
			var widget_settings_ul = menu_settings.find(" > .settings-widget > ul");
			var widget_resources_ul = widget_settings_ul.find(" > .widget-resources > ul");
			var widget_resources_type_elm = widget_resources_ul.children(".widget-resources-" + resources_type);
			
			if (!widget_resources_type_elm[0]) {
				var html = getWidgetResourcesTypeSettingsHtml(resources_type, "", true);
				widget_resources_type_elm = $(html);
				widget_resources_type_elm.addClass("selected-widget-setting");
				widget_resources_ul.append(widget_resources_type_elm);
				
				prepareMenuSettingsEvents(widget_resources_type_elm);
			}
		}
	};
	
	me.removeWidgetResourceType = function(elm, event) {
		event && event.stopPropagation();
		
		var li = $(elm).parent().closest("li");
		var p = li.parent();
		
		//prepare resources name to remove if apply
		var resource_reference_elms = li.find(" > ul > .widget-resource > ul > .widget-resource-reference");
		var resources_name = [];
		
		for (var i = 0, t = resource_reference_elms.length; i < t; i ++) {
			var resource_reference_elm = $(resource_reference_elms[i]);
			var resource_name = resource_reference_elm.hasClass("select-shown") ? resource_reference_elm.children("select").val() : resource_reference_elm.children("input").val();
			
			if (resource_name)
				resources_name.push(resource_name);
		}
		
		//remove item
		removeItem(elm, event, "Do you wish to remove this resource type?");
		
		//remove resources
		if (li.parent().length == 0) //if it was really removed
			removeSLAResourcesIfNotUsedAnymore(resources_name);
	};
	
	me.addWidgetResource = function(elm, event) {
		event && event.stopPropagation();
		
		var li = $(elm).parent().closest("li");
		var resources_type = li.attr("data-resource-type");
		var prefix = li.children("ul").attr("prefix");
		var html = getWidgetResourceSettingsHtml(prefix);
		
		var item = addItem(elm, event, html);
		item.addClass("selected-widget-setting");
		prepareMenuSettingsEvents(item);
		
		return item;
	};
	
	me.removeWidgetResource = function(elm, event) {
		var li = $(elm).parent().closest("li");
		var p = li.parent();
		
		//prepare resource name to remove if apply
		var resource_reference_elm = li.find(" > ul > .widget-resource-reference");
		var resource_name = resource_reference_elm.hasClass("select-shown") ? resource_reference_elm.children("select").val() : resource_reference_elm.children("input").val();
		
		//remove item
		removeItem(elm, event, "Do you wish to remove this resource?");
		
		//remove resource
		if (li.parent().length == 0) //if it was really removed
			removeSLAResourcesIfNotUsedAnymore(resource_name);
		
		prepareWidgetResourcesTypeResourceReferenceField(p);
	};
	
	me.addWidgetResourceCondition = function(elm, event) {
		var li = $(elm).parent().closest("li");
		var prefix = li.attr("data-prefix");
		var table = li.find("table > tbody");
		
		//add row
		var tr = $('<tr class="condition">'
				 + '<td class="name">'
				 	+ '<input name="' + prefix + '[tridx][name]" />'
				 + '</td>'
				 + '<td class="type">'
					+ '<select name="' + prefix + '[tridx][type]">'
						+ '<option value="">Contains</option>'
						+ '<option value="not like">Not Contains</option>'
						+ '<option value="starts_with">Starts With</option>'
						+ '<option value="ends_with">Ends With</option>'
						+ '<option value="equal" selected>Equal</option>'
						+ '<option value="!=">Different</option>'
						+ '<option value="&gt;">Bigger</option>'
						+ '<option value="&gt;=">Bigger or Equal</option>'
						+ '<option value="&lt;">Smaller</option>'
						+ '<option value="&lt;=">Smaller or Equal</option>'
						+ '<option value="in">In</option>'
						+ '<option value="not in">Not In</option>'
						+ '<option value="is">Is</option>'
						+ '<option value="is not">Is Not</option>'
					+ '</select>'
				 + '</td>'
				 + '<td class="value">'
				 	+ '<input name="' + prefix + '[tridx][value]" />'
				 + '</td>'
				 + '<td class="case">'
					+ '<select name="' + prefix + '[tridx][case]">'
						+ '<option value="">-- Default --</option>'
						+ '<option value="sensitive">Sensitive</option>'
						+ '<option value="insensitive">Insensitive</option>'
					+ '</select>'
				 + '</td>'
				 + '<td class="operator">'
				 	+ '<select name="' + prefix + '[tridx][operator]">'
				 		+ '<option value="">-- Default --</option>'
				 		+ '<option value="or">Or</option>'
				 		+ '<option value="and">And</option>'
				 	+ '</select>'
				 + '</td>'
				 + '<td class="action"><i class="zmdi zmdi-delete remove-resource-condition" title="Remove this condition" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.removeWidgetResourceCondition(this, event)"></i></td>'
			 + '</tr>');
		
		table.children("tr.empty-items").hide();
		table.append(tr);
		
		tr.addClass("selected-widget-setting");
		prepareMenuSettingsEvents(tr);
		
		return tr;
	};
	
	me.removeWidgetResourceCondition = function(elm, event) {
		var tr = $(elm).parent().closest("tr");
		var table = tr.parent();
		
		//remove item
		if (confirm("Do you wish to remove this condition?")) {
			tr.remove();
			
			//show empty
			if (table.children("tr:not(.empty-items)").length == 0)
				table.children("tr.empty-items").show();
			
			updateFieldInSelectedWidget(table);
		}
	};
	
	me.convertWidgetCallbackInExtraCallbacks = function(li) {
		if (!li.hasClass("select-shown")) {
			var input = li.children("input");
			var value = input.val();
			
			if (value && (value.indexOf(",") != -1 || value.indexOf(";") != -1)) {
				var parts = value.replace(/;/g, ",").replace(/\s*,\s*/g, ",").replace(/,+/g, ",").replace(/(^,|,$)/g, "").split(",");
				var reference = li;
				var main_li = li.closest(".with-extra-callbacks");
				var icon = main_li.children(".add-extra-callback");
				var ul = main_li.children(".extra-callbacks");
				
				input.val(parts[0]);
				
				if (li.hasClass("widget-swap-field")) {
					var select = li.children("select");
					select.val(parts[0]);
					
					if (select.val() == parts[0])
						li.addClass("select-shown");
					else
						li.removeClass("select-shown");
				}
				
				for (var i = 1, t = parts.length; i < t; i++) {
					var part = parts[i];
					var extra_li = me.addExtraCallback(icon[0], window.event);
					
					extra_li.children("input, select").val(part);
					
					if (extra_li.hasClass("widget-swap-field")) {
						if (extra_li.children("select").val() == part)
							extra_li.addClass("select-shown");
						else
							extra_li.removeClass("select-shown");
					}
					
					if (li.hasClass("with-extra-callbacks") && i == 1)
						ul.prepend(extra_li);
					else
						reference.after(extra_li);
					
					reference = extra_li;
				}
			}
		}
	};
	
	me.addExtraCallback = function(elm, event) {
		var p = $(elm).parent().closest(".with-extra-callbacks");
		var ul = p.children(".extra-callbacks");
		var li = $('<li class="selected-widget-setting"></li>');
		var clones = p.children("input, select, .swap-input-select, .choose-event, .clear-user-input").clone();
		
		li.append(clones);
		li.append('<i class="zmdi zmdi-delete remove-extra-callback" title="Remove this callback" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.removeExtraCallback(this, event)"></i>');
		ul.append(li);
		
		if (p.hasClass("widget-swap-field"))
			li.addClass("widget-swap-field");
		
		if (p.hasClass("select-shown"))
			li.addClass("select-shown");
		
		li.children("input, select").val("");
		
		prepareMenuSettingsEvents(li);
		
		li.children("input").bind("blur", function(ev) {
			me.convertWidgetCallbackInExtraCallbacks(li);
		});
		
		return li;
	};
	
	me.removeExtraCallback = function(elm, event) {
		var li = $(elm).parent().closest("li");
		var main_li = li.parent().closest(".with-extra-callbacks");
		li.remove();
		
		updateFieldInSelectedWidget( main_li.hasClass("select-shown") ? main_li.children("select") : main_li.children("input") );
	};
	
	me.convertWidgetEventInExtraEvents = function(li) {
		if (!li.hasClass("select-shown")) {
			var next_elm = li.next("li");
			
			ui_creator.convertWidgetEventInExtraEvents(li);
			
			if (li.hasClass("widget-swap-field")) {
				var select = li.children("select");
				var part = li.children("input").val();
				select.val(part);
				
				if (select.val() == part)
					li.addClass("select-shown");
				else
					li.removeClass("select-shown");
			}
			
			var new_next_elm = li.next("li");
			
			while (new_next_elm[0] && !new_next_elm.is(next_elm)) {
				if (new_next_elm.hasClass("widget-swap-field")) {
					var select = new_next_elm.children("select");
					var part = new_next_elm.children("input").val();
					select.val(part);
					
					if (select.val() == part)
						new_next_elm.addClass("select-shown");
					else
						new_next_elm.removeClass("select-shown");
				}
					
				new_next_elm = new_next_elm.next("li");
			}
		}
	};
	
	me.convertWidgetEventWithExtraEventsInString = function(li) {
		if (li.hasClass("widget-swap-field")) {
			var value = li.hasClass("select-shown") ? li.children("select").val() : li.children("input").val();
			value = value.replace(/\s*;+\s*$/g, "").replace(/(^\s+|\s+$)/g, "");
			
			//add extra events
			var extra_lis = li.find(" > .extra-events > li");
			
			for (var i = 0, t = extra_lis.length; i < t; i++) {
				var extra_li = $(extra_lis[i]);
				var v = extra_li.hasClass("select-shown") ? extra_li.children("select").val() : extra_li.children("input").val();
				v = v.replace(/\s*;+\s*$/g, "").replace(/(^\s+|\s+$)/g, "");
				
				if (v || $.isNumeric(v))
					value += (value == "" || value.match(/;\s*$/) ? "" : "; ") + v;
			}
			
			return value;
		}
		else
			return ui_creator.convertWidgetEventWithExtraEventsInString(li);
	};
	
	me.addExtraEvent = function(elm, event) {
		var p = $(elm).parent().closest(".with-extra-events");
		var ul = p.children(".extra-events");
		var li = $('<li class="selected-widget-setting"></li>');
		var clones = p.children("input, select, .swap-input-select, .choose-event, .clear-user-input").clone();
		
		li.append(clones);
		li.append('<i class="zmdi zmdi-delete remove-extra-event" title="Remove this event" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.removeExtraEvent(this, event)"></i>');
		ul.append(li);
		
		if (p.hasClass("widget-swap-field"))
			li.addClass("widget-swap-field");
		
		if (p.hasClass("select-shown"))
			li.addClass("select-shown");
		
		li.children("input, select").val("");
		
		prepareMenuSettingsEvents(li);
		
		li.children("input").bind("blur", function(ev) {
			me.convertWidgetEventInExtraEvents(li);
		});
		
		return li;
	};
	
	me.removeExtraEvent = function(elm, event) {
		var li = $(elm).parent().closest("li");
		var ul = li.parent();
		li.remove();
		
		var main_li = ul.parent();
		var default_elm = main_li.hasClass("select-shown") ? main_li.children("select") : main_li.children("input");
		
		me.onChangeWidgetClickEvent(default_elm[0], event);
	};
	
	me.toggleChooseWidgetHandlerPopup = function(elm, event) {
		if (typeof ui_creator.options.on_choose_event_func == "function") {
			var widget = $(selected_widget);
			var p = $(elm).parent();
			var input = p.children("input");
			var select = p.children("select");
			var curr_value = p.hasClass("widget-swap-field") && p.hasClass("select-shown") ? select.val() : input.val();
			var handler = function(code) {
				var pos = code.indexOf("(");
				
				if (pos != -1)
					code = code.substr(0, pos);
				
				if (p.hasClass("widget-swap-field"))
					loadWidgetSettingValueIntoSwappedField(p, code);
				else
					input.val(code);
				
				//if onKeyUp/onChange event trigger it
				if (p.hasClass("select-shown")) {
					if (select[0].hasAttribute("onChange"))
						select.trigger("change");
				}
				else {
					if (p.hasClass("with-extra-callbacks") || p.parent().hasClass("extra-callbacks")) {
						me.convertWidgetCallbackInExtraCallbacks(p);
						input.data("force_refresh", 1);
					}
					
					if (input[0].hasAttribute("onKeyUp"))
						input.trigger("keyup");
					
					input.focus();
				}
			};
			var available_events = getSelectedWidgetMyWidgetResourceLibJSEventFunctions(widget);
			
			ui_creator.options.on_choose_event_func(elm, widget, handler, available_events, curr_value);
		}
	};
	
	me.toggleChooseWidgetEventPopup = function(elm, event) {
		if (typeof ui_creator.options.on_choose_event_func == "function") {
			elm = $(elm);
			var widget = $(selected_widget);
			var p = elm.parent();
			var input = elm.is("input") ? elm : p.children("input");
			var select = elm.is("select") ? elm : p.children("select");
			var curr_value = p.hasClass("widget-swap-field") && p.hasClass("select-shown") ? select.val() : input.val();
			var handler = function(code) {
				if (p.hasClass("widget-swap-field"))
					loadWidgetSettingValueIntoSwappedField(p, code);
				else
					input.val(code);
				
				//if onKeyUp/onChange event trigger it
				if (p.hasClass("select-shown")) {
					if (select[0].hasAttribute("onChange"))
						select.trigger("change");
				}
				else {
					if (p.hasClass("with-extra-events") || p.parent().hasClass("extra-events")) {
						me.convertWidgetEventInExtraEvents(p);
						input.data("force_refresh", 1);
					}
					
					if (input[0].hasAttribute("onKeyUp"))
						input.trigger("keyup");
					
					input.focus();
				}
			};
			var available_events = getSelectedWidgetMyWidgetResourceLibJSEventFunctions(widget);
			
			ui_creator.options.on_choose_event_func(elm[0], widget, handler, available_events, curr_value);
		}
	};
	
	me.toggleChooseWidgetDBTableAttributePopup = function(elm, event) {
		if (typeof me.options.toggle_choose_db_table_attribute_popup_func == "function")
			me.options.toggle_choose_db_table_attribute_popup_func(elm, event, function(attr_name) {
				var target_field = $(elm).parent().children("input.attr_name");
				
				if (target_field) {
					if (attr_name) {
						target_field = $(target_field);
						var old_attr_name = target_field.val();
						var exists = false;
						
						//check if attr_name already exists
						var inputs = target_field.parent().closest("li").parent().find("input");
						
						for (var i = 0, t = inputs.length; i < t; i++) {
							var input = $(inputs[i]);
							
							if (!target_field.is(input) && input.val() == attr_name) {
								exists = true;
								break;
							}
						}
						
						if (exists)
							ui_creator.showError("Attribute already exist! Please choose another one...");
						else if (attr_name != old_attr_name) {
							target_field.data("saved_attr_name", old_attr_name);
							target_field.val(attr_name);
							
							//update UI if apply
							if (target_field[0].hasAttribute("onBlur"))
								target_field.trigger("blur");
						}
					}
				}
				else
					ui_creator.showError("No target field in 'Choose DB Table Attribute' Popup. Please talk with sysadmin bc this should not be happening.");
			});
	};
	
	me.toggleChooseWidgetResourcePopup = function(elm, event, handler) {
		if (typeof me.options.toggle_choose_widget_resource_popup_func == "function") {
			var widget = $(selected_widget);
			
			me.options.toggle_choose_widget_resource_popup_func(elm, widget, function(resource_name, resource_index) {
				if (!resource_name) {
					ui_creator.showError("Resource cannot be undefined!");
					return false;
				}
				else {
					var li = $(elm).parent();
					loadWidgetSettingValueIntoSwappedField(li, resource_name);
					
					if (li.hasClass("select-shown"))
						updateFieldInSelectedWidget( li.children("select") );
					else
						updateFieldInSelectedWidget( li.children("inputs") );
					
					if (typeof handler == "function")
						handler(elm, event);
					
					return true;
				}
			});
		}
	};
	
	me.toggleChooseWidgetResourceOriginalPopup = function(elm, event, handler) {
		var handler = function(elm, event) {
			var p = $(elm).parent().closest(".widget-resources-load, .widget-resources");
			
			if (p.hasClass("widget-resources-load")) {
				var ul = p.find(" > .widget-resources-type-properties > ul");
				
				//prepare load handler
				var load_handler_elm = ul.children(".widget-property-handler-load");
				var load_handler_field = load_handler_elm.hasClass("select-shown") ? load_handler_elm.children("select") : load_handler_elm.children("input");
				var load_handler_value = load_handler_field.val();
				
				if (!load_handler_value) {
					var load_handler_options = load_handler_elm.find(" > select > option");
					var default_load_handler = load_handler_options.filter("option[value!='']").first().val(); //chooses the default load handler which is MyWidgetResourceLib.FieldHandler.loadElementFieldsResource, if exists.
					
					if (default_load_handler) {
						//check if exists MyWidgetResourceLib.FieldHandler.loadFieldResource loadHandler, and if so, selects it.
						var better_load_handler = load_handler_options.filter("option[value='MyWidgetResourceLib.FieldHandler.loadFieldResource']").val();
						
						if (better_load_handler)
							default_load_handler = better_load_handler;
						
						//load default_load_handler
						loadWidgetSettingValueIntoSwappedField(load_handler_elm, default_load_handler);
					}
				}
				
				//prepare load type
				var load_type_elm = ul.children(".widget-property-load-type");
				var load_type_field = load_type_elm.children("select");
				var load_type_value = load_type_field.val();
				
				if (!load_type_value) {
					load_type_field.val("data-widget-resources-load");
					load_type_field.trigger("change");
				}
			}
		};
		
		me.toggleChooseWidgetResourcePopup(elm, event, handler);
	};
	
	me.toggleChooseWidgetResourceReferencePopup = function(elm, event) {
		var handler = function(elm, event) {
			var resource_reference_elm = $(elm).parent().closest(".widget-resource-reference");
			var field = resource_reference_elm.hasClass("select-shown") ? resource_reference_elm.children("select") : resource_reference_elm.children("input");
			var resource_name = field.val(); //save resource name to be used below
			
			prepareResourceReferenceFromWidgetResourceValueSetting(field[0]);
			
			//set resuorce name to reference field, bc prepareResourceReferenceFromWidgetResourceValueSetting set it to empty value, bc it is now a parent load action.
			loadWidgetSettingValueIntoSwappedField(resource_reference_elm, resource_name);
			field.data("saved_resource_reference", resource_name); //set saved_resource_reference for next time
		};
		
		me.toggleChooseWidgetResourcePopup(elm, event, handler);
	};
	
	me.toggleChooseWidgetResourceValueAttributePopup = function(elm, event) {
		if (typeof me.options.toggle_choose_widget_resource_value_attribute_popup_func == "function") {
			var widget = $(selected_widget);
			
			me.options.toggle_choose_widget_resource_value_attribute_popup_func(elm, widget, function(resource_name, resource_index, resource_attribute) {
				/* If resource_attribute is empty, it means it should show the resource it-self
				if (!resource_attribute && !$.isNumeric(resource_attribute)) {
					ui_creator.showError("Attribute cannot be undefined!");
					return false;
				}
				else */if (!resource_name) {
					ui_creator.showError("Resource cannot be undefined!");
					return false;
				}
				else {
					var li = $(elm).parent();
					var ul = li.parent();
					var target_field = li.children("input");
					var resource_attribute_exists = resource_attribute || $.isNumeric(resource_attribute);
					var resource_index_exists = resource_index || $.isNumeric(resource_index);
					
					//set new attribute in .widget-display-resource-value-resource-attribute
					target_field.val(resource_attribute);
					
					//set new index in .widget-display-resource-value-resource-index
					ul.find(" > .widget-display-resource-value-resource-index input").val(resource_index);
					
					//compare the resource reference and update it in the right field, otherwise set the parent value.
					var is_parent = false;
					var widget_resources = me.getWidgetResources(widget);
					var exist_load_resources = $.isPlainObject(widget_resources) && $.isArray(widget_resources["load"]) && widget_resources["load"].length > 0;
					
					//check if widget already contains this resource name
					if (exist_load_resources)
						$.each(widget_resources["load"], function(idx, load_resource) {
							if ($.isPlainObject(load_resource) && load_resource["name"] == resource_name) {
								if (idx == 0)
									is_parent = true;
								
								return false;
							}
						});
					
					//check if main widget already contains this resource name
					var parent_widget = widget.parent().closest("[data-widget-resources]");
					
					if (parent_widget[0] && !is_parent) { //only set is_parent if there are no load resources in the widget
						var parent_widget_resources = me.getWidgetResources(parent_widget);
						
						if ($.isPlainObject(parent_widget_resources) && $.isArray(parent_widget_resources["load"]) && parent_widget_resources["load"].length > 0)
							$.each(parent_widget_resources["load"], function(idx, load_resource) {
								if ($.isPlainObject(load_resource) && load_resource["name"] == resource_name) {
									if (idx == 0)
										is_parent = true;
									
									return false;
								}
							});
					}
					
					//save old resource name
					var resource_reference_elm = ul.children(".widget-display-resource-value-resource-reference");
					var old_resource_name = resource_reference_elm.hasClass("select-shown") ? resource_reference_elm.children("select").val() : resource_reference_elm.children("input").val();
					
					//set resource reference field with right resource name
					if (!is_parent)
						loadWidgetSettingValueIntoSwappedField(resource_reference_elm, resource_name); //already updates the widget with the right attributes
					else
						loadWidgetSettingValueIntoSwappedField(resource_reference_elm, ""); //already updates the widget with the right attributes
					
					//add a new load resource to resources element
					var field = resource_reference_elm.hasClass("select-shown") ? resource_reference_elm.children("select") : resource_reference_elm.children("input");
					field.data("saved_resource_reference", old_resource_name);
					
					prepareResourceReferenceFromWidgetResourceValueSetting(field[0]);
					
					//prepare resource-display
					var resource_display_select = ul.find(" > .widget-display-resource-value-resource-display > select");
					
					if (!resource_attribute_exists && !resource_index_exists) {
						resource_display_select.val("resource");
						resource_display_select.trigger("change");
					}
					else if (resource_display_select.val() != "attribute") {
						resource_display_select.val("attribute");
						resource_display_select.trigger("change");
					}
					
					return true;
				}
			});
		}
	};
	
	me.onChangeWidgetResourceValueResourceDisplayField = function(elm, event) {
		elm = $(elm);
		
		var type = elm.val();
		var wdrv = elm.parent().closest(".widget-display-resource-value");
		var p = wdrv.children("ul");
		var attribute_input = p.find(" > .widget-display-resource-value-resource-attribute > input");
		var index_input = p.find(" > .widget-display-resource-value-resource-index > input");
		
		if (type == "resource") {
			wdrv.removeClass("with-display-disabled").addClass("with-display-resource");
			attribute_input.val("");
			index_input.val("");
		}
		else if (type == "") {
			wdrv.addClass("with-display-disabled").removeClass("with-display-resource");
			attribute_input.val("");
			index_input.val("");
		}
		else {
			wdrv.removeClass("with-display-disabled").removeClass("with-display-resource");
			
			var attribute = attribute_input.val();
			var index = index_input.val();
			var attribute_exists = attribute || $.isNumeric(attribute);
			var index_exists = index || $.isNumeric(index);
			
			if (!attribute_exists && !index_exists) {
				attribute_input.addClass("highlight").focus();
				index_input.addClass("highlight");
			}
		}
		
		attribute_input.data("saved_value", null); //very important otherwise when changing from the elm options, the attribute_input doesn't get updated sometimes bc is in cache. So we need to delete the saved_value, so it gets always updated.
		
		me.onBlurField(attribute_input[0], event);
	};
	
	me.onBlurWidgetResourceValueAttributeField = function(elm, event) {
		var p = $(elm).parent().closest(".widget-display-resource-value").children("ul");
		var display_select = p.find(" > .widget-display-resource-value-resource-display > select");
		var display = display_select.val();
		var attribute_input = p.find(" > .widget-display-resource-value-resource-attribute > input");
		var attribute = attribute_input.val();
		var index_input = p.find(" > .widget-display-resource-value-resource-index > input");
		var index = index_input.val();
		var attribute_exists = attribute || $.isNumeric(attribute);
		var index_exists = index || $.isNumeric(index);
		
		if (!attribute_exists && !index_exists && display == "attribute") {
			//only change the display and trigger the onChange event inside of the timeout, otherwise the user cannot click in the pencil icon aside this input, bc the onChange event from the display_select is hidding the ".widget-display-resource-value-resource-attribute" right away.
			attribute_input[0].timeout_id = setTimeout(function() {
				display_select.val("");
				display_select.trigger("change");
				
				attribute_input.removeClass("highlight");
				index_input.removeClass("highlight");
			}, 300);
		}
		else if ((attribute_exists || index_exists) && display == "") {
			display_select.val("attribute");
			display_select.trigger("change");
			
			attribute_input.removeClass("highlight");
			index_input.removeClass("highlight");
		}
		else {
			me.onBlurField(elm, event);
			
			attribute_input.removeClass("highlight");
			index_input.removeClass("highlight");
		}
	};
	
	me.onFocusWidgetResourceValueAttributeField = function(elm, event) {
		if (elm.timeout_id) {
			clearTimeout(elm.timeout_id);
			elm.timeout_id = null;
		}
	};
	
	me.onBlurWidgetResourceValueIndexField = function(elm, event) {
		me.onBlurWidgetResourceValueAttributeField(elm, event);
	};
	
	me.onFocusWidgetResourceValueIndexField = function(elm, event) {
		var p = $(elm).parent().closest(".widget-display-resource-value").children("ul");
		var attribute_input = p.find(" > .widget-display-resource-value-resource-attribute > input");
		
		if (attribute_input[0].timeout_id) {
			clearTimeout(attribute_input[0].timeout_id);
			attribute_input[0].timeout_id = null;
		}
	};
	
	me.onFocusWidgetResourceValueResourceReferenceField = function(elm, event) {
		elm = $(elm);
		
		//only executes once
		if (!elm.data("saved_resource_reference"))
			elm.data("saved_resource_reference", elm.val());
	};
	
	me.onChangeWidgetResourceValueResourceReferenceField = function(elm, event) {
		me.onChangeField(elm, event);
		
		prepareResourceReferenceFromWidgetResourceValueSetting(elm);
	};
	
	me.onBlurWidgetResourceValueResourceReferenceField = function(elm, event) {
		me.onBlurField(elm, event);
		
		prepareResourceReferenceFromWidgetResourceValueSetting(elm);
	};
	
	me.onChangeWidgetResourceValueResourceDisplayTargetTypeField = function(elm, event) {
		elm = $(elm);
		var p = elm.parent().closest(".widget-display-resource-value");
		
		if (elm.val() == "attribute")
			p.addClass("with-display-target-attribute");
		else
			p.removeClass("with-display-target-attribute");
		
		me.onChangeField(elm[0], event);
	};
	
	me.onChangeWidgetResourcesTypeLoadTypeField = function(elm, event) {
		elm = $(elm);
		var widget = $(selected_widget);
		var load_type = elm.val();
		
		widget.removeAttr("data-widget-resources-load").removeAttr("data-widget-item-resources-load");
		
		if (load_type) {
			widget.attr(load_type, "");
			
			onChangeSelectedWidget();
		}
	};
	
	me.onChangeWidgetResourcesTypeResourceReferenceField = function(elm, event) {
		me.onChangeField(elm, event);
		
		var resource_name = $(elm).val();
		prepareWidgetResourcesTypeResourceReferenceField(elm, resource_name);
	};
	
	me.onBlurWidgetResourcesTypeResourceReferenceField = function(elm, event) {
		me.onBlurField(elm, event);
		
		var resource_name = $(elm).val();
		prepareWidgetResourcesTypeResourceReferenceField(elm, resource_name);
	};
	
	me.toggleChooseWidgetPageUrlPopup = function(elm, event) {
		if (typeof ui_creator.options.on_choose_page_url_func == "function")
			ui_creator.options.on_choose_page_url_func(elm, event);
	};
	
	me.onChangeWidgetDBBroker = function(elm, event) {
		elm = $(elm);
		var p = elm.parent().closest("li").parent();
		var db_broker = elm.val();
		
		updateWidgetDBDrivers(p, db_broker);
	};
	
	me.onChangeWidgetPropertyDBBroker = function(elm, event) {
		me.onChangeWidgetDBBroker(elm, event);
		
		updateFieldInSelectedWidget(elm);
	};
	
	me.onChangeWidgetDBDriver = function(elm, event) {
		elm = $(elm);
		var p = elm.parent().closest("li").parent();
		var db_broker = p.find(" > .widget-property-db-broker > select").val();
		var db_driver = elm.val();
		var db_type = p.find(" > .widget-property-db-type > select").val();
		
		updateWidgetDBTables(p, db_broker, db_driver, db_type);
	};
	
	me.onChangeWidgetPropertyDBDriver = function(elm, event) {
		me.onChangeWidgetDBDriver(elm, event);
		
		updateFieldInSelectedWidget(elm);
	};
	
	me.onChangeWidgetDBType = function(elm, event) {
		elm = $(elm);
		var p = elm.parent().closest("li").parent();
		var db_broker = p.find(" > .widget-property-db-broker > select").val();
		var db_driver = p.find(" > .widget-property-db-driver > select").val();
		var db_type = elm.val();
		
		updateWidgetDBTables(p, db_broker, db_driver, db_type);
	};
	
	me.onChangeWidgetPropertyDBType = function(elm, event) {
		me.onChangeWidgetDBType(elm, event);
		
		updateFieldInSelectedWidget(elm);
	};
	
	me.onChangeWidgetDBTable = function(elm, event) {
		elm = $(elm);
		var p = elm.parent().closest("li").parent();
		var db_broker = p.find(" > .widget-property-db-broker > select").val();
		var db_driver = p.find(" > .widget-property-db-driver > select").val();
		var db_type = p.find(" > .widget-property-db-type > select").val();
		var db_table = elm.val();
		
		updateWidgetDBAttributes(p, db_broker, db_driver, db_type, db_table);
	};
	
	me.onChangeWidgetPropertyDBTable = function(elm, event) {
		elm = $(elm);
		var widget = $(selected_widget);
		var widget_properties_ul = elm.closest(".widget-properties").children("ul");
		var table_name = elm.val();
		
		//set default alias if user whishes to
		if (table_name) {
			var db_table_alias_input = widget_properties_ul.find(".widget-property-db-table-alias input");
			var db_table_alias = db_table_alias_input.val();
			
			if (!db_table_alias && table_name.indexOf("_") != -1) { //only if exists _ in the table name
				db_table_alias = prompt("Do you wish to write an alias for the table '" + table_name + "'? (leave it blank for default)", saved_table_alias.hasOwnProperty(table_name) ? saved_table_alias[table_name] : "");
				saved_table_alias[table_name] = db_table_alias ? db_table_alias : "";
				
				if (db_table_alias) {
					db_table_alias_input.val(db_table_alias);
					
					var widget_properties = me.getWidgetProperties(widget);
					widget_properties["db_table_alias"] = db_table_alias;
					widget.attr("data-widget-props", JSON.stringify(widget_properties));
				}
			}
		}
		
		//update db attributes select fields
		me.onChangeWidgetDBTable(elm[0], event);
		
		//update field properties first and others if apply
		updateFieldInSelectedWidget(elm[0]);
		
		//prepare widget default html
		prepareWidgetWithDefaultHtml(elm[0], widget);
		
		//prepare new attributes and pks
		var db_attributes = getWidgetGroupDBAttributes(widget);
		
		if (db_attributes && !$.isEmptyObject(db_attributes)) {
			//only include attributes that are not internal attributes like: created_date or modified_date, modified_by created_by, created_user_id, modified_user_id...
			db_attributes = purgeInternalAttributesFromWidgetDBAttributes(db_attributes);
			
			var shown_attrs_names_elm = widget_properties_ul.children(".widget-property-shown-attrs-names");
			var shown_add_icon = shown_attrs_names_elm.children(".widget-item-add");
			var shown_inputs = shown_attrs_names_elm.find(" > ul > li:not(.empty-items) > input.attr_name");
			var shown_index = 0;
			
			var pks_attrs_names_elm = widget_properties_ul.children(".widget-property-pks-attrs-names");
			var pks_add_icon = pks_attrs_names_elm.children(".widget-item-add");
			var pks_inputs = pks_attrs_names_elm.find(" > ul > li:not(.empty-items) > input.attr_name");
			var pks_index = 0;
			
			//add or replace attributes according with new table selection
			$.each(db_attributes, function(attr_name, attr) {
				var shown_input = $(shown_inputs[shown_index]);
				
				//create new attr (with empty attr_name) in shown attributes
				if (!shown_input[0]) {
					var item = me.addWidgetAttrName(shown_add_icon[0], event);
					shown_input = item.children("input.attr_name");
				}
				
				//set new attr_name to input in shown attributes
				if (shown_input[0] && shown_input.val() != attr_name) {
					shown_input.data("saved_attr_name", shown_input.val());
					shown_input.val(attr_name);
					//console.log("START ATTR replaceWidgetAttrNameFromUI for "+attr_name);
					replaceWidgetAttrNameFromUI(shown_input[0], widget); //update UI
				}
				//else 
				//	console.log("EXISTS "+attr_name);
				
				//prepare pks
				if (attr && attr["primary_key"]) {
					var pks_input = $(pks_inputs[pks_index]);
					
					//create new attr (with empty attr_name) in pks attributes
					if (!pks_input[0]) {
						var item = me.addWidgetAttrName(pks_add_icon[0], event);
						pks_input = item.children("input.attr_name");
					}
					
					//set new attr_name to input in pks attributes
					if (pks_input[0] && pks_input.val() != attr_name) {
						pks_input.data("saved_attr_name", pks_input.val());
						pks_input.val(attr_name);
						//console.log("START PK replaceWidgetAttrNameFromUI for "+attr_name);
						replaceWidgetAttrNameFromUI(pks_input[0], widget); //update UI
					}
					
					pks_index++;
				}
				
				shown_index++;
			});
			
			//remove extra old shown attributes
			for (var i = shown_index, t = shown_inputs.length; i < t; i++) {
				var remove_icon = $(shown_inputs[i]).parent().children(".widget-item-remove");
				me.removeWidgetAttrName(remove_icon[0], event, true);
			}
			
			//remove extra old pks attributes
			for (var i = pks_index, t = pks_inputs.length; i < t; i++) {
				var remove_icon = $(pks_inputs[i]).parent().children(".widget-item-remove");
				me.removeWidgetAttrName(remove_icon[0], event, true);
			}
			
			//prepare default attrs values
			var main_widget = me.getWidgetGroupMainWidget(widget);
			
			if (main_widget && main_widget.is("[data-widget-list], [data-widget-form], form")) {
				var default_attrs_values = me.getWidgetDefaultAttributesValues(main_widget);
				default_attrs_values = $.isPlainObject(default_attrs_values) ? default_attrs_values : {};
				var orig_default_attrs_values = Object.assign({}, default_attrs_values);
				
				//remove default attributes which are not in db_attributes. 
				for (var attr_name in default_attrs_values) {
					if (!attr_name || !db_attributes.hasOwnProperty(attr_name)) {
						default_attrs_values[attr_name] = null;
						delete default_attrs_values[attr_name];
					}
				}
				
				//add default attributes which are PKs
				if (widget.is("[data-widget-group-form]")) {
					var no_pks = !hasDBAttributesPKs(db_attributes);
					
					$.each(db_attributes, function(attr_name, attr) {
						if (attr && isDBAttributePK(no_pks, attr_name, attr) && !default_attrs_values.hasOwnProperty(attr_name))
							default_attrs_values[attr_name] = '#_GET[' + attr_name + ']#';
					});
				}
				
				//update default attributes in main_widget
				if (default_attrs_values != orig_default_attrs_values) {
					if ($.isEmptyObject(default_attrs_values))
						main_widget.removeAttr("data-widget-pks-attrs");
					else
						main_widget.attr("data-widget-pks-attrs", JSON.stringify(default_attrs_values));
					
					onChangeSelectedWidget();
				}
			}
			
			//add resources for the new table and remove the old table resources if not used anymore
			//get all sub-widgets with resources defined, and for each resource check if the old_table_name is present. If it is replace it with the new table name resource. If no old resources, add the new resources for the new table.
			var sub_widgets = widget.find("[data-widget-resources]");
			var resources_types_to_search = ["load", "add", "update", "update_attribute", "remove"];
			var old_db_driver = elm.data("saved_db_driver");
			var old_db_table = elm.data("saved_db_table");
			var old_db_table_alias = elm.data("saved_db_table_alias");
			var something_changed = false;
			
			$.each(sub_widgets, function(idx, sub_widget) {
				sub_widget = $(sub_widget);
				var sub_widget_resources = me.getWidgetResources(sub_widget);
				
				if ($.isPlainObject(sub_widget_resources)) {
					var changed = false;
					
					//search the existent widget resources and for each type's resource, replace it with the new table, if exists. If no resource (inlcuding the load resource), doesn't do anything, bc it means that the user deleted that resource on purpose.
					for (var resources_type in sub_widget_resources) 
						if ($.inArray(resources_type, resources_types_to_search) != -1) {
					 		var sub_resources = sub_widget_resources[resources_type];
					 		
					 		//only add resource if previous one already exists. If no resources available (inlcuding the load resource), doesn't do anything, bc it means that the user deleted that resource on purpose, so we should NOT create new ones.
					 		if ($.isArray(sub_resources) && sub_resources.length > 0) {
						 		//add new resource based in the new table name
						 		var action_type = getWidgetActionTypeResourcesType(sub_widget, resources_type);
						 		
						 		if (action_type) {
							 		var permissions = me.getWidgetPermissions(sub_widget);
						 			var new_resource_name = addWidgetResourceByType(sub_widget, resources_type, action_type, permissions, null, true);
						 			var new_resources_type_props = getWidgetResourcesTypeDefaultProperties(resources_type, action_type);
							 		var replaced = false;
							 		var exists = false;
							 		
							 		//find old resource, delete it and replace it by new resource
						 			var possible_old_resource_names = old_db_table ? createResourceNames(action_type, old_db_driver, old_db_table, old_db_table_alias) : null;
						 			
						 			for (var i = 0, ti = sub_resources.length; i < ti; i++) {
						 				var sub_resource = sub_resources[i];
						 				
						 				if (sub_resource["name"] == new_resource_name) 
						 					exists = true;
						 				else if (possible_old_resource_names && possible_old_resource_names.length > 0) {
						 					var is_old_resource = false;
						 					
						 					for (var j = 0, tj = possible_old_resource_names.length; j < tj; j++)
						 						if (sub_resource["name"] == possible_old_resource_names[j] || ("" + sub_resource["name"]).indexOf(possible_old_resource_names[j] + "_") === 0) {
						 							is_old_resource = true;
						 							break;
						 						}
						 					
						 					if (is_old_resource) {
							 					//remove external sla resources
												removeSLAResourcesIfNotUsedAnymore(sub_resource["name"], sub_widget);
												
												//replace resource reference by new reference
							 					if (new_resource_name) {
							 						if (new_resources_type_props)
							 							sub_resource = Object.assign(new_resources_type_props, sub_resource);
							 						
							 						sub_resource["name"] = new_resource_name;
							 						sub_widget_resources[resources_type][i] = sub_resource;
							 						replaced = true;
							 					}
							 				}
						 				}
						 			}
							 		
							 		//add new resource to resources, bc it could NOT find the old resource
							 		if (new_resource_name && !exists) {
								 		if (!replaced) {
								 			var resource_obj = {name: new_resource_name};
								 			resource_obj = new_resources_type_props ? Object.assign(new_resources_type_props, resource_obj) : resource_obj;
								 			
								 			sub_widget_resources[resources_type].push(resource_obj);
								 		}
								 		
								 		changed = true;
							 		}
							 	}
					 		}
						}
					
					//update new resources in widget
					if (changed) {
						something_changed = true;
						sub_widget.attr("data-widget-resources", JSON.stringify(sub_widget_resources));
					}
				}
			}).promise().done( function() {
				if (something_changed)
					onChangeSelectedWidget();
			});
		}
		
		//console.log("length:"+elm.closest(".widget-properties").children("ul").children(".widget-property-shown-attrs-names").find(" > ul > li:not(.empty-items) > input.attr_name").length);
		
		//load it again so it can load the pks_attrs_names and shown_attrs_names
		me.loadMenuSettings(widget);
		
		//save new driver and table name for next time
		var db_driver_settings = getWidgetSettingsData(elm[0], ".widget-properties > ul > .widget-property-db-driver, .widget-properties > ul > .widget-property-db-table-alias");
		
		elm.data("saved_db_driver", db_driver_settings["widget_properties"]["db_driver"]);
		elm.data("saved_db_table", table_name);
		elm.data("saved_db_table_alias", db_driver_settings["widget_properties"]["db_table_alias"]);
	};
	
	me.onFocusWidgetPropertyDBTable = function(elm, event) {
		elm = $(elm);
		
		//only executes once
		if (!elm.data("saved_db_table")) {
			var db_driver_settings = getWidgetSettingsData(elm, ".widget-properties > ul > .widget-property-db-driver, .widget-properties > ul > .widget-property-db-table-alias");
			
			elm.data("saved_db_driver", db_driver_settings["widget_properties"]["db_driver"]);
			elm.data("saved_db_table", elm.val());
			elm.data("saved_db_table_alias", db_driver_settings["widget_properties"]["db_table_alias"]);
		}
	};
	
	me.onBlurWidgetPropertyDBTable = function(elm, event) {
		elm = $(elm);
		var new_db_table = elm.val();
		var old_db_table = elm.data("saved_db_table");
		
		if (new_db_table != old_db_table)
			me.onChangeWidgetPropertyDBTable(elm, event);
	};
	
	//widget-property-default-attrs-values
	me.addWidgetAttrNameAndValue = function(elm, event) {
		var li = $(elm).parent().closest("li");
		var ul = li.children("ul");
		var attr_name = li.children("select").val();
		var item = null;
		
		//check if attr_name already exists
		if (attr_name) {
			var inputs = ul.find("input");
			
			for (var i = 0, t = inputs.length; i < t; i++) {
				var input = $(inputs[i]);
				
				if (input.val() == attr_name) {
					item = input.parent().closest("li");
					break;
				}
			}
		}
		
		if (item)
			ui_creator.showError("Attribute was already added!");
		else {
			var prefix = ul.attr("prefix");
			var attr_value = attr_name ? '#_GET[' + attr_name + ']#' : '';
			var html = getWidgetAttrNameAndValueHtml(prefix, attr_name, attr_value);
			
			item = addItem(elm, event, html);
			item.addClass("selected-widget-setting");
			prepareMenuSettingsEvents(item);
			
			//update UI
			if (attr_name) {
				var widget = $(selected_widget);
				addWidgetAttrNameFromUI(item.find("input.attr_name")[0], widget);
			}
		}
		
		return item;
	};
	
	//widget-property-shown-attrs-names, widget-property-pks-attrs-names and widget-property-search-attrs-names
	me.addWidgetAttrName = function(elm, event) {
		var li = $(elm).parent().closest("li");
		var ul = li.children("ul");
		var attr_name = li.children("select").val();
		var item = null;
		
		//check if attr_name already exists
		if (attr_name) {
			var inputs = ul.find("input");
			
			for (var i = 0, t = inputs.length; i < t; i++) {
				var input = $(inputs[i]);
				
				if (input.val() == attr_name) {
					item = input.parent().closest("li");
					break;
				}
			}
		}
		
		if (item)
			ui_creator.showError("Attribute was already added!");
		else {
			var prefix = ul.attr("prefix");
			var html = getWidgetAttrNameHtml(prefix, attr_name);
			
			item = addItem(elm, event, html);
			item.addClass("selected-widget-setting");
			prepareMenuSettingsEvents(item);
			
			//update UI
			if (attr_name) {
				var widget = $(selected_widget);
				addWidgetAttrNameFromUI(item.find("input.attr_name")[0], widget);
			}
		}
		
		return item;
	};
	
	me.removeWidgetAttrName = function(elm, event, do_not_confirm) {
		if (do_not_confirm || confirm("Do you wish to remove this attribute name?")) {
			var widget = $(selected_widget);
			removeWidgetAttrNameFromUI(elm, widget); //must be before the removeItem method runs, otherwise the input will be deleted
			
			removeItem(elm, event, null);
		}
	};
	
	me.onFocusWidgetAttrName = function(elm, event) {
		elm = $(elm);
		
		if (elm.hasClass("attr_name"))
			elm.data("saved_attr_name", elm.val());
	};
	
	me.onBlurWidgetAttrName = function(elm, event) {
		var widget = $(selected_widget);
		replaceWidgetAttrNameFromUI(elm, widget);
	};
	
	//add or remove html elements with data-widget-search.
	me.onChangeWidgetWithSearch = function(elm, event) {
		elm = $(elm);
		//var value = elm.val();
		var value = "input";
		var widget = $(selected_widget);
		var search_widget = widget.find("[data-widget-search]");
		
		//add html elements with data-widget-search.
		if (elm.is(":checked")) {
			var main_widget = me.getWidgetGroupMainWidget(widget);
			
			if (!main_widget)
				elm.prop("checked", false).removeAttr("checked");
			else if (search_widget[0])
				search_widget.show();
			else
				addWidgetSearch(elm, widget, value);
		}
		else if (search_widget[0]) {
			if (confirm("This will remove the search widget. Do you wish to continue?")) //remove html elements with data-widget-search.
				removeWidget(search_widget);
			else
				elm.prop("checked", true).attr("checked", "");
		}
	};
	
	//add or remove html elements with data-widget-short-actions.
	me.onChangeWidgetWithShortActions = function(elm, event) {
		elm = $(elm);
		var widget = $(selected_widget);
		var short_actions_widget = widget.find("[data-widget-short-actions]");
		
		//add html elements with data-widget-short-actions.
		if (elm.is(":checked")) {
			var main_widget = me.getWidgetGroupMainWidget(widget);
			
			if (!main_widget)
				elm.prop("checked", false).removeAttr("checked");
			else if (short_actions_widget[0]) {
				short_actions_widget.show();
				
				prepareWidgetShortActions(elm, widget, short_actions_widget);
			}
			else {
				addWidgetShortActions(elm, widget);
				
				var is_short_actions_empty = widget.find("[data-widget-short-actions]").find("[data-widget-button-multiple-remove], [data-widget-button-multiple-save], [data-widget-button-add], [data-widget-button-toggle-between-widgets], [data-widget-button-toggle-list-attribute-select-checkboxes], [data-widget-button-reset-sorting]").length == 0;
				
				if (is_short_actions_empty) {
					var exists_multiple_actions = elm.parent().closest(".widget-properties").find(" > ul > .widget-property-multiple-items-actions > select").val() != "";
					
					if (!exists_multiple_actions)
						ui_creator.showMessage("Don't forget to choose an action in the 'With Multiple Items Actions' setting.");
				}
			}
		}
		else if (short_actions_widget[0]) {
			if (confirm("This will remove the short-actions widget. Do you wish to continue?")) //remove html elements with data-widget-short-actions.
				removeWidget(short_actions_widget);
			else
				elm.prop("checked", true).attr("checked", "");
		}
	};
	
	//add or remove html elements with data-widget-pagination before the widget data-widget-list.
	me.onChangeWidgetWithTopPagination = function(elm, event) {
		elm = $(elm);
		var widget = $(selected_widget);
		var first = widget.find("[data-widget-pagination], [data-widget-list]").first();
		var pagination_top_elm = first.is("[data-widget-pagination]") ? first : null; //be sure that first is a top pagination
		
		//add html elements with data-widget-pagination.
		if (elm.is(":checked")) {
			var main_widget = me.getWidgetGroupMainWidget(widget);
			
			if (!main_widget)
				elm.prop("checked", false).removeAttr("checked");
			else if (pagination_top_elm && pagination_top_elm[0])
				pagination_top_elm.show();
			else {
				//get html
				var html = getWidgetPaginationHtml(elm, widget, true);
				pagination_top_elm = $(html);
				
				//add pagination id to search widget and add popup if exists
				var pagination_id = pagination_top_elm.attr("id");
				
				widget.find("[data-widget-search], [data-widget-popup-add] [data-widget-form]").each(function(idx, sub_widget) {
					sub_widget = $(sub_widget);
					
					var sub_widget_properties = me.getWidgetProperties(sub_widget);
					
					if (typeof sub_widget_properties["dependent_widgets_id"] == "string")
						sub_widget_properties["dependent_widgets_id"] = [ sub_widget_properties["dependent_widgets_id"] ];
					
					if ($.isArray(sub_widget_properties["dependent_widgets_id"]))
						sub_widget_properties["dependent_widgets_id"].push(pagination_id);
					
					sub_widget.attr("data-widget-props", JSON.stringify(sub_widget_properties));
				}).promise().done( function() {
					onChangeSelectedWidget();
				});
				
				//insert before list element
				var list = widget.find("[data-widget-list]").first();
				
				if (list[0])
					list.before(pagination_top_elm);
				else //prepend to widget
					widget.prepend(pagination_top_elm);
				
				//convert html to LayoutUIEditor widgets
				convertHtmlElementToLayoutUIEditorWidget(pagination_top_elm);
			}
		}
		else if (pagination_top_elm && pagination_top_elm[0]) {
			if (confirm("This will remove the top pagination widget. Do you wish to continue?")) //remove html elements with data-widget-pagination.
				removeWidget(pagination_top_elm);
			else
				elm.prop("checked", true).attr("checked", "");
		}
	};
	
	//add or remove html elements with data-widget-pagination after the widget data-widget-list.
	me.onChangeWidgetWithBottomPagination = function(elm, event) {
		elm = $(elm);
		var widget = $(selected_widget);
		var last = widget.find("[data-widget-pagination], [data-widget-list]").last();
		var pagination_bottom_elm = last.is("[data-widget-pagination]") ? last : null; //be sure that last is a bottom pagination
		
		//add html elements with data-widget-pagination.
		if (elm.is(":checked")) {
			var main_widget = me.getWidgetGroupMainWidget(widget);
			
			if (!main_widget)
				elm.prop("checked", false).removeAttr("checked");
			else if (pagination_bottom_elm && pagination_bottom_elm[0])
				pagination_bottom_elm.show();
			else {
				//get html
				var html = getWidgetPaginationHtml(elm, widget, false);
				pagination_bottom_elm = $(html);
				
				//add pagination id to search widget if exists
				var pagination_id = pagination_bottom_elm.attr("id");
				
				widget.find("[data-widget-search], [data-widget-popup-add] [data-widget-form]").each(function(idx, sub_widget) {
					sub_widget = $(sub_widget);
					
					var sub_widget_properties = me.getWidgetProperties(sub_widget);
					
					if (typeof sub_widget_properties["dependent_widgets_id"] == "string")
						sub_widget_properties["dependent_widgets_id"] = [ sub_widget_properties["dependent_widgets_id"] ];
					
					if ($.isArray(sub_widget_properties["dependent_widgets_id"]))
						sub_widget_properties["dependent_widgets_id"].push(pagination_id);
					
					sub_widget.attr("data-widget-props", JSON.stringify(sub_widget_properties));
				}).promise().done( function() {
					onChangeSelectedWidget();
				});
				
				//insert before list element
				var list = widget.find("[data-widget-list]").first();
				
				if (list[0])
					list.after(pagination_bottom_elm);
				else //prepend to widget
					widget.append(pagination_bottom_elm);
				
				//convert html to LayoutUIEditor widgets
				convertHtmlElementToLayoutUIEditorWidget(pagination_bottom_elm);
			}
		}
		else if (pagination_bottom_elm && pagination_bottom_elm[0]) {
			if (confirm("This will remove the bottom pagination widget. Do you wish to continue?")) //remove html elements with data-widget-pagination.
				removeWidget(pagination_bottom_elm);
			else
				elm.prop("checked", true).attr("checked", "");
		}
	};
	
	//if "table" is choosen, then add html element with data-widget-list-table and remove data-widget-list-tree.
	//if "tree" is choosen, then add html element with data-widget-list-tree and remove data-widget-list-table.
	//if "both" is choosen, then add or remove html elements with data-widget-button-toggle-between-widgets.
	me.onChangeWidgetListType = function(elm, event, reload_menu_settings) {
		var properties_settings = getWidgetSettingsData(elm, ".widget-properties");
		properties_settings = properties_settings["widget_properties"];
		
		var widget = $(selected_widget);
		var widget_group = widget.closest("[data-widget-group-list], [data-widget-group-form]");
		widget = widget_group ? widget_group : widget;
		
		var db_attributes = getWidgetGroupDBAttributes(widget);
		
		if (db_attributes && !$.isEmptyObject(db_attributes)) {
			elm = $(elm);
			var type = elm.val();
			var list_widget = widget.find("[data-widget-list]");
			var list_table_widget = widget.find("[data-widget-list-table]");
			var list_tree_widget = widget.find("[data-widget-list-tree]");
			
			//add html elements with data-widget-list.
			if (!type) {
				if (list_widget[0]) {
					removeWidget(list_widget);
					list_widget = null;
				}
			}
			else if (!list_widget[0]) {
				var html = getWidgetListHtml(elm, widget, type, db_attributes, properties_settings);
				list_widget = $(html);
				
				var first = widget.find("[data-widget-search], [data-widget-short-actions], [data-widget-pagination]").last();
				var last = widget.find("[data-widget-popup]").first();
				
				if (first[0]) {
					if (first.is("[data-widget-pagination]"))
						first = widget.find("[data-widget-pagination]").first();
					
					first.after(list_widget);
				}
				else if (last[0])
					last.before(list_widget);
				else
					widget.append(list_widget);
				
				//convert html to LayoutUIEditor widgets
				convertHtmlElementToLayoutUIEditorWidget(list_widget);
				
				//add list id to dependent_id in search, short actions, pagination widgets
				var list_id = widget.attr("id");
				
				widget.find("[data-widget-search], [data-widget-short-actions], [data-widget-pagination]").each(function(idx, sub_widget) {
					sub_widget = $(sub_widget);
					
					var sub_widget_properties = me.getWidgetProperties(sub_widget);
					
					if (typeof sub_widget_properties["dependent_widgets_id"] == "string")
						sub_widget_properties["dependent_widgets_id"] = [ sub_widget_properties["dependent_widgets_id"] ];
					
					if ($.isArray(sub_widget_properties["dependent_widgets_id"]))
						sub_widget_properties["dependent_widgets_id"].push(list_id);
					
					sub_widget.attr("data-widget-props", JSON.stringify(sub_widget_properties));
				}).promise().done( function() {
					onChangeSelectedWidget();
				});
				
				//reload the menu settings again so it can load the pks_attrs_names and shown_attrs_names
				if (reload_menu_settings)
					me.loadMenuSettings(widget);
			}
			else {
				if (type == "table" || type == "both") {
					if (type == "both" && list_tree_widget[0] && list_tree_widget.css("display") == "none")
						list_tree_widget.show();
					
					if (!list_table_widget[0]) {
						//get html
						var html = getWidgetListTableHtml(elm, widget, db_attributes, properties_settings);
						list_table_widget = $(html);
						
						var list_tree = list_widget.find("[data-widget-list-tree]");
						
						if (list_tree[0])
							list_tree.before(list_table_widget);
						else {
							var list_responsive = list_widget.children(".list-responsive, .table-responsive");
							var list_caption = list_widget.children("[data-widget-list-caption]");
							
							if (list_responsive[0])
								list_responsive.append(list_table_widget);
							else if (list_caption[0])
								list_caption.before(list_table_widget);
							else
								list_widget.append(list_table_widget);
						}
						
						//convert html to LayoutUIEditor widgets
						convertHtmlElementToLayoutUIEditorWidget(list_table_widget);
					}
					else if (list_table_widget.css("display") == "none") 
						list_table_widget.show();
					
					//only delete tree at the end otherwise it won't find the list_tree var above
					if (type == "table" && list_tree_widget[0])
						removeWidget(list_tree_widget);
				}
				
				if (type == "tree" || type == "both") {
					if (type == "both" && list_table_widget[0] && list_table_widget.css("display") == "none")
						list_table_widget.show();
					
					if (!list_tree_widget[0]) {
						//get html
						var html = getWidgetListTreeHtml(elm, widget, db_attributes, properties_settings);
						list_tree_widget = $(html);
						
						var list_table = list_widget.find("[data-widget-list-table]");
						
						if (list_table[0])
							list_table.before(list_tree_widget);
						else {
							var list_caption = list_widget.children("[data-widget-list-caption]");
							
							if (list_caption[0])
								list_caption.before(list_tree_widget);
							else
								list_widget.append(list_tree_widget);
						}
						
						//convert html to LayoutUIEditor widgets
						convertHtmlElementToLayoutUIEditorWidget(list_tree_widget);
					}
					else if (list_tree_widget.css("display") == "none") 
						list_tree_widget.show();
					
					//only delete table at the end otherwise it won't find the list_table var above
					if (type == "tree" && list_table_widget[0])
						removeWidget(list_table_widget);
				}
			}
			
			//call on change short actions to add the data-widget-button-toggle-between-widgets button. 
			var input = elm.parent().closest(".widget-properties").find(" > ul > .widget-property-with-short-actions > input[type=checkbox]");
			
			if (input[0])
				me.onChangeWidgetWithShortActions(input[0], event);
			
			//attribute for toggle button. This needs to be after the onChangeWidgetWithShortActions, otherwise when we create for the first time the list_widget, it won't add [data-widget-list-with-toggle-table-tree] bc there are not short-actions yet...
			if (list_widget) {
				var has_short_actions = widget.find("[data-widget-short-actions]").length > 0; //Do not use properties_settings["with_short_actions"] bc when the onChangeWidgetWithShortActions was called preciously, it disabled the with_short_actions property.
				
				if (has_short_actions && type == "both")
					list_widget.attr("data-widget-list-with-toggle-table-tree", "");
				else
					list_widget.removeAttr("data-widget-list-with-toggle-table-tree");
			}
		}
	};
	
	me.onChangeWidgetViewable = function(elm, event) {
		elm = $(elm);
		var ul = elm.parent().children("ul");
		var widget = $(selected_widget);
		var widget_group = widget.closest("[data-widget-group-list], [data-widget-group-form]");
		widget = widget_group ? widget_group : widget;
		
		if (elm.is(":checked")) {
			var main_widget = me.getWidgetGroupMainWidget(widget);
			
			if (!main_widget)
				elm.prop("checked", false).removeAttr("checked");
			else {
				openMenuSettingsSubOptions(elm);
			
				//add html elements according with view type, like: data-widget-item-attribute-link-view, data-widget-item-attribute-field-view, data-widget-item-button-toggle-inline-edit-view, data-widget-item-button-view, data-widget-popup-view popup.
				me.onChangeWidgetViewableType(ul.find(" > .widget-property-viewable-type > select")[0], event);
			}
		}
		else {
			closeMenuSettingsSubOptions(elm);
			
			//remove attribute data-widget-with-button-toggle-inline-edit-view from [data-widget-item]
			removeWidgetWithButtonToggleInlineEditView(widget);
			
			//remove html elements with data-widget-item-attribute-link-view, data-widget-item-attribute-field-view, data-widget-item-button-toggle-inline-edit-view, data-widget-item-button-view, data-widget-popup-view popup.
			removeWidget( widget.find("[data-widget-item-attribute-link-view], [data-widget-item-attribute-field-view], [data-widget-item-button-toggle-inline-edit-view], [data-widget-item-button-view], [data-widget-popup-view]") );
		}
	};
	
	me.onChangeWidgetViewableType = function(elm, event) {
		elm = $(elm);
		var li = elm.parent().closest("li");
		var ul = li.parent();
		var value = elm.val();
		var toggle_edit_view_button = ul.parent().closest(".widget-properties").find(" > ul > .widget-property-editable > ul > .widget-property-toggle-edit-view-fields-button");
		
		var widget = $(selected_widget);
		var widget_group = widget.closest("[data-widget-group-list], [data-widget-group-form]");
		widget = widget_group ? widget_group : widget;
		
		var db_attributes = getWidgetGroupDBAttributes(widget);
		
		if (db_attributes && !$.isEmptyObject(db_attributes)) {
			if (!value)
				value = "inline";
			
			var main_widget = me.getWidgetGroupMainWidget(widget);
			var popup_view = widget.find("[data-widget-popup][data-widget-popup-view]");
			
			var properties_settings = getWidgetSettingsData(elm, ".widget-properties");
			properties_settings = properties_settings["widget_properties"];
			
			toggle_edit_view_button.hide();
			
			var view_active = properties_settings["viewable"] ? properties_settings["viewable"]["active"] : false;
			var toggle_edit_view_button_enabled = properties_settings["editable"]["active"] && (properties_settings["editable"]["type"] == "link" || properties_settings["editable"]["type"] == "inline");
			
			if (!view_active) {
				ui_creator.showError("Cannot proceed because the 'Viewable' option is disabled. Please enable it first!");
				toggleMenuSettingsSubOptions( elm.parent().closest(".widget-property-viewable").children(".toggle")[0] );
			}
			//if link: allows users to click the values and open a popup to view details of that element. Add or remove all html elements which are "data-widget-item-button-view". If yes, the data-widget-popup-view popup is added, otherwise remove it.
			else if (value == "link") {
				ul.children(".widget-property-viewable-with-view-button").show();
				
				//show or hide toggle button
				if (toggle_edit_view_button_enabled)
					toggle_edit_view_button.show();
				
				if (main_widget) {
					//remove attribute data-widget-with-button-toggle-inline-edit-view from [data-widget-item]
					removeWidgetWithButtonToggleInlineEditView(main_widget);
					
					//remove data-widget-item-attribute-field-view, data-widget-item-button-toggle-inline-edit-view.
					removeWidget( main_widget.find("[data-widget-item-attribute-field-view], [data-widget-item-button-toggle-inline-edit-view]") );
					
					//add data-widget-item-attribute-link-view if does not exists
					main_widget.find("[data-widget-item-column]").each(function(idx, column) {
						if (column.hasAttribute("data-widget-item-attribute-name")) {
							column = $(column);
							
							if (column.find("[data-widget-item-attribute-link-view]").length == 0) {
								var html = getWidgetItemAttributeLinkViewHtml(elm, widget, db_attributes, properties_settings, column.attr("data-widget-item-attribute-name"));
								html = $(html);
								
								column.append(html);
								
								//convert html to LayoutUIEditor widgets
								convertHtmlElementToLayoutUIEditorWidget(html);
							}
						}
					});
					
					//add data-widget-item-button-view and data-widget-popup-view popup if is checked and does not exist.
					if (properties_settings["viewable"]["with_view_button"]) {
						var html = getWidgetItemActionsButtonViewHtml(elm, widget);
						addWidgetItemButton(elm, main_widget, "[data-widget-item-button-view]", html);
					}
					else //remove button if exists
						removeWidget( main_widget.find("[data-widget-item-button-view]") );
					
					//add data-widget-item-button-toggle-inline-edit-view if apply
					if (toggle_edit_view_button_enabled && properties_settings["editable"]["toggle_edit_view_fields_button"]) { //add toggle button
						//add attribute data-widget-with-button-toggle-inline-edit-view to [data-widget-item]
						addWidgetWithButtonToggleInlineEditView(main_widget);
						
						var html = getWidgetItemActionsButtonToggleInlineEditViewHtml(elm, widget);
						addWidgetItemButton(elm, main_widget, "[data-widget-item-button-toggle-inline-edit-view]", html);
					}
					else { 
						//remove attribute data-widget-with-button-toggle-inline-edit-view from [data-widget-item]
						removeWidgetWithButtonToggleInlineEditView(main_widget);
						
						//remove toggle button
						removeWidget( main_widget.find("[data-widget-item-button-toggle-inline-edit-view]") );
					}
				}
			}
			//if inline: allows users to view details inside of the row. Add or remove all html elements which are "data-widget-item-attribute-field-view".
			else if (value == "inline") {
				ul.children(".widget-property-viewable-with-view-button").show();
				
				//show or hide toggle button
				if (toggle_edit_view_button_enabled)
					toggle_edit_view_button.show();
				
				//remove popup view
				if (!properties_settings["viewable"]["with_view_button"]) 
					removeWidget(popup_view);
				
				if (main_widget) {
					//remove data-widget-item-attribute-link-view
					removeWidget( main_widget.find("[data-widget-item-attribute-link-view]") );
					
					//add data-widget-item-attribute-field-view if does not exists
					main_widget.find("[data-widget-item-column]").each(function(idx, column) {
						if (column.hasAttribute("data-widget-item-attribute-name")) {
							column = $(column);
							
							if (column.find("[data-widget-item-attribute-field-view]").length == 0) {
								var html = getWidgetItemAttributeLabelViewHtml(elm, widget, db_attributes, properties_settings, column.attr("data-widget-item-attribute-name"));
								html = $(html);
								
								column.append(html);
								
								//convert html to LayoutUIEditor widgets
								convertHtmlElementToLayoutUIEditorWidget(html);
							}
						}
					});
					
					//add data-widget-item-button-view and data-widget-popup-view popup if is checked and does not exist. Otherwise remove it.
					if (properties_settings["viewable"]["with_view_button"]) {
						var html = getWidgetItemActionsButtonViewHtml(elm, widget);
						addWidgetItemButton(elm, main_widget, "[data-widget-item-button-view]", html);
					}
					else { //remove button and edit popup if exist
						removeWidget( main_widget.find("[data-widget-item-button-view]") );
						removeWidget( widget.find("[data-widget-popup][data-widget-popup-view]") );
					}
					
					//add data-widget-item-button-toggle-inline-edit-view if apply
					if (toggle_edit_view_button_enabled && properties_settings["editable"]["toggle_edit_view_fields_button"]) { //add toggle button
						//add attribute data-widget-with-button-toggle-inline-edit-view to [data-widget-item]
						addWidgetWithButtonToggleInlineEditView(main_widget);
						
						var html = getWidgetItemActionsButtonToggleInlineEditViewHtml(elm, widget);
						addWidgetItemButton(elm, main_widget, "[data-widget-item-button-toggle-inline-edit-view]", html);
					}
					else { 
						//remove attribute data-widget-with-button-toggle-inline-edit-view from [data-widget-item]
						removeWidgetWithButtonToggleInlineEditView(main_widget);
						
						//remove toggle button
						removeWidget( main_widget.find("[data-widget-item-button-toggle-inline-edit-view]") );
					}
				}
			}
			//if button: allows users to click the button and open a popup to view details of that element. Add or remove all html elements which are "data-widget-item-button-view". If yes, the data-widget-popup-view popup is added, otherwise remove it.
			/* This is redundant bc is the same than the inline with the view button
			else if (value == "button") {
				ul.children(".widget-property-viewable-with-view-button").hide();
				
				if (main_widget) {
					//remove attribute data-widget-with-button-toggle-inline-edit-view from [data-widget-item]
					removeWidgetWithButtonToggleInlineEditView(main_widget);
					
					//remove data-widget-item-attribute-link-view, data-widget-item-button-toggle-inline-edit-view
					removeWidget( main_widget.find("[data-widget-item-attribute-link-view], [data-widget-item-button-toggle-inline-edit-view]") );
					
					//add data-widget-item-attribute-field-view if does not exists
					main_widget.find("[data-widget-item-column]").each(function(idx, column) {
						if (column.hasAttribute("data-widget-item-attribute-name")) {
							column = $(column);
							
							if (column.find("[data-widget-item-attribute-field-view]").length == 0) {
								var html = getWidgetItemAttributeLabelViewHtml(elm, widget, db_attributes, properties_settings, column.attr("data-widget-item-attribute-name"));
								html = $(html);
								
								column.append(html);
								
								//convert html to LayoutUIEditor widgets
								convertHtmlElementToLayoutUIEditorWidget(html);
							}
						}
					});
					
					//add data-widget-item-button-view and data-widget-popup-view popup if does not exist.
					var html = getWidgetItemActionsButtonViewHtml(elm, widget);
					addWidgetItemButton(elm, main_widget, "[data-widget-item-button-view]", html);
				}
			}*/
		} 
	};
	
	//view button (checkbox): allows users to click the button and open a popup form to view that element. Add or remove all html elements which are "data-widget-item-button-view".
	me.onChangeWidgetViewableWithViewButton = function(elm, event) {
		elm = $(elm);
		
		var widget = $(selected_widget);
		var widget_group = widget.closest("[data-widget-group-list], [data-widget-group-form]");
		widget = widget_group ? widget_group : widget;
		
		var properties_settings = getWidgetSettingsData(elm, ".widget-properties");
		properties_settings = properties_settings["widget_properties"];
		
		var view_active = properties_settings["viewable"] ? properties_settings["viewable"]["active"] : false;
		
		if (!view_active) {
			elm.prop("checked", false).removeAttr("checked");
			
			ui_creator.showError("Cannot proceed because the 'Viewable' option is disabled. Please enable it first!");
			toggleMenuSettingsSubOptions( elm.parent().closest(".widget-property-viewable").children(".toggle")[0] );
		}
		else if (elm.is(":checked")) {
			var main_widget = me.getWidgetGroupMainWidget(widget);
			
			if (main_widget) {
				var html = getWidgetItemActionsButtonViewHtml(elm, widget);
				addWidgetItemButton(elm, main_widget, "[data-widget-item-button-view]", html);
			}
		}
		else {
			removeWidget( widget.find("[data-widget-item-button-view]") );
			
			//remove popup view if viewable type is inline
			var view_type = view_active ? properties_settings["viewable"]["type"] : false;
			
			if (view_type == "inline")
				removeWidget( widget.find("[data-widget-popup][data-widget-popup-view]") );
		}
	};
	
	me.onChangeWidgetAddable = function(elm, event) {
		elm = $(elm);
		var ul = elm.parent().children("ul");
		var widget = $(selected_widget);
		var widget_group = widget.closest("[data-widget-group-list], [data-widget-group-form]");
		widget = widget_group ? widget_group : widget;
		
		if (elm.is(":checked")) {
			var main_widget = me.getWidgetGroupMainWidget(widget);
			
			if (!main_widget)
				elm.prop("checked", false).removeAttr("checked");
			else {
				openMenuSettingsSubOptions(elm);
				
				//add [data-widget-button-add] to main_widget table head, if not exists yet
				if (main_widget && main_widget.find("[data-widget-button-add]").length == 0) {
					var db_attributes = getWidgetGroupDBAttributes(widget);
						
					if (db_attributes) {
						var properties_settings = getWidgetSettingsData(elm, ".widget-properties");
						properties_settings = properties_settings["widget_properties"];
						
						var add_active = properties_settings["addable"] ? properties_settings["addable"]["active"] : false;
						var add_type = add_active ? properties_settings["addable"]["type"] : false;
						var add_permissions = add_active ? properties_settings["addable"]["permissions"] : false;
						var add_permissions_html = add_permissions ? ' data-widget-permissions="' + JSON.stringify(add_permissions).replace(/"/g, "&quot;") + '"' : "";
						
						//add button to thead
						var add_button_html = getWidgetListTableButtonAddHtml(elm, widget, db_attributes, properties_settings, add_active, add_type, add_permissions_html);
						
						if (main_widget.is("[data-widget-list]")) {
							addWidgetItemButtonToListTableHead(elm, main_widget, "[data-widget-button-add]", add_button_html);
							
							//add "add" button to the data-widget-empty
							main_widget.find("[data-widget-empty]").each(function(idx, list_empty) { //it could be multiple
								list_empty = $(list_empty);
								
								if (list_empty.find("[data-widget-button-add], [data-widget-empty-add]").length == 0) {
									var html = getWidgetListEmptyAddHtml(elm, widget, db_attributes, properties_settings, add_active, add_type, add_permissions_html);
									var list_empty_add = $(html);
									
									if (list_empty.is("tr")) {
										if (list_empty.children("td").length == 0) {
											var table = list_empty.closest("table");
											var colspan = table.find("[data-widget-item]").first().children().length;
											colspan = colspan ? colspan : table.find("thead > tr").first().children().length;
											list_empty.append('<td class="border-0 text-center text-muted small p-3" colspan="' + colspan + '"></td>');
										}
										
										list_empty.children("td").first().append(list_empty_add);
									}
									else
										list_empty.append(list_empty_add);
									
									//convert html to LayoutUIEditor widgets
									convertHtmlElementToLayoutUIEditorWidget(list_empty_add);
								}
							});
						}
						//add popup add button if widget is data-widget-group-form and main_widget is data-widget-form, but only if view fields exist too.
						else if (widget.is("[data-widget-group-form]") && main_widget.is("[data-widget-form]")) {
							var view_active = properties_settings["viewable"] ? properties_settings["viewable"]["active"] : false;
							
							if (view_active)
								addWidgetItemButtonToForm(elm, main_widget, "[data-widget-button-add]", add_button_html);
							else
								main_widget.addClass("show-add-fields");
						}
					}
				}
				
				//do the same than onChangeWidgetAddableType. add html elements with data-widget-button-add and data-widget-item-button-add and data-widget-item-button-add-cancel, data-widget-item-attribute-field-add (according with "fields type" below).
				me.onChangeWidgetAddableType(ul.find(" > .widget-property-addable-type > select")[0], event);
			}
		}
		else {
			closeMenuSettingsSubOptions(elm);
			
			//remove html elements with data-widget-button-add, data-widget-item-button-add and data-widget-item-button-add-cancel, data-widget-popup-add.
			removeWidget( widget.find("[data-widget-button-add], [data-widget-empty-add], [data-widget-item-add], [data-widget-item-attribute-field-add], [data-widget-item-button-add], [data-widget-item-button-add-cancel], [data-widget-popup][data-widget-popup-add]") );
			
			//remove "add" resource
			removeMainWidgetResourcesByType(widget, "add");
		}
		
		//call on change short actions to add the "short action add" button, in caseit doesn't exists yet
		var input = elm.parent().closest(".widget-properties").find(" > ul > .widget-property-with-short-actions > input[type=checkbox]");
		
		if (input[0])
			me.onChangeWidgetWithShortActions(input[0], event);
	};
	
	me.onChangeWidgetAddableType = function(elm, event) {
		elm = $(elm);
		var li = elm.parent().closest("li");
		var value = elm.val();
		var widget = $(selected_widget);
		var widget_group = widget.closest("[data-widget-group-list], [data-widget-group-form]");
		widget = widget_group ? widget_group : widget;
		
		var db_attributes = getWidgetGroupDBAttributes(widget);
		
		if (db_attributes && !$.isEmptyObject(db_attributes)) {
			if (!value)
				value = "popup";
			
			var main_widget = me.getWidgetGroupMainWidget(widget);
			var popup_add = widget.find("[data-widget-popup][data-widget-popup-add]");
			
			var properties_settings = getWidgetSettingsData(elm, ".widget-properties");
			properties_settings = properties_settings["widget_properties"];
			
			var add_active = properties_settings["addable"] ? properties_settings["addable"]["active"] : false;
			
			if (!add_active) {
				ui_creator.showError("Cannot proceed because the 'Addable' option is disabled. Please enable it first!");
				toggleMenuSettingsSubOptions( elm.parent().closest(".widget-property-addable").children(".toggle")[0] );
			}
			//if popup: 
			//remove data-widget-item-button-add and data-widget-item-button-add-cancel and data-widget-item-attribute-field-add.
			//add popup: [data-widget-popup][data-widget-popup-add].
			//change all add buttons to open popup.
			else if (value == "popup") {
				var changed = false;
				
				if (main_widget) {
					//remove data-widget-item-button-add and data-widget-item-button-add-cancel.
					removeWidget( main_widget.find("[data-widget-item-add], [data-widget-item-button-add], [data-widget-item-button-add-cancel], [data-widget-item-attribute-field-add]") );
					
					//remove complete[add] callback function from main_widget
					removeMainWidgetCompleteCallbackByType(main_widget, "add");
				}
				
				//add popup: [data-widget-popup][data-widget-popup-add].
				if (!popup_add[0]) {
					var popup_html = getWidgetPopupAddHtml(elm, widget, db_attributes, properties_settings);
					popup_add = $(popup_html);
					widget.append(popup_add);
					
					//convert html to LayoutUIEditor widgets
					convertHtmlElementToLayoutUIEditorWidget(popup_add);
				}
				
				//change onclick event of data-widget-button-add in table and short actions
				var popup_id = popup_add.attr("id");
				var add_buttons = widget.find("[data-widget-button-add]");
				
				add_buttons.each(function(idx, btn) {
					btn = $(btn);
					var on_click = btn.attr("onClick");
					
					if (!on_click || on_click.match(/MyWidgetResourceLib\.ListHandler\.addInlineResourceListItem\s*\(/) || on_click.match(/MyWidgetResourceLib\.ShortActionHandler\.addInlineResourceListItemToDependentWidgets\s*\(/)) {
						changed = true;
						
						//set popup id
						btn.attr("data-widget-popup-id", popup_id);
						
						//set new event
						btn.attr("onClick", "MyWidgetResourceLib.PopupHandler.openButtonAddPopup(this); return false;");
						
						//remove dependencies
						var btn_properties = me.getWidgetProperties(btn);
						
						if (btn_properties.hasOwnProperty("dependent_widgets_id")) {
							btn_properties["dependent_widgets_id"] = null;
							delete btn_properties["dependent_widgets_id"];
							
							btn.attr("data-widget-props", JSON.stringify(btn_properties));
						}
					}
				}).promise().done( function() {
					if (changed)
						onChangeSelectedWidget();
				});
			}
			//if inline: 
			//add html elements with data-widget-item-button-add and data-widget-item-button-add-cancel
			//add data-widget-item-attribute-field-add if does not exists
			//remove popup: [data-widget-popup][data-widget-popup-add].
			//change all add buttons to add inline row instead of opening popup.
			else if (value == "inline") {
				if (main_widget) {
					var changed = false;
					var is_list_main_widget = main_widget.is("[data-widget-list], [data-widget-list-table], [data-widget-list-tree], table");
					
					var add_permissions = add_active ? properties_settings["addable"]["permissions"] : false;
					var add_permissions_html = add_permissions ? ' data-widget-permissions="' + JSON.stringify(add_permissions).replace(/"/g, "&quot;") + '"' : "";
					
					//add html elements with data-widget-item-button-add and data-widget-item-button-add-cancel
					var add_html = getWidgetItemActionsButtonAddHtml(elm, widget, db_attributes, properties_settings, add_permissions_html);
					var add_cancel_html = getWidgetItemActionsButtonAddCancelHtml(elm, widget, db_attributes, properties_settings, add_permissions_html);
					
					if (is_list_main_widget) {
						//add data-widget-item-add element if none
						var exists_item_add = main_widget.find("[data-widget-item-add]").length > 0;
						
						if (!exists_item_add) {
							var table = main_widget.is("table") ? main_widget : main_widget.find("[data-widget-list-table], table");
							var tree = main_widget.is("[data-widget-list-tree]") ? main_widget : main_widget.find("[data-widget-list-tree]");
							
							var attributes_to_filter = createWidgetSettingsDataShownAttributesNames(main_widget); //get attributes for filtering
							
							$.each(table, function(idx, t) {
								t = $(t);
								var html = getWidgetListTableItemAddHtml(elm, widget, db_attributes, properties_settings, attributes_to_filter);
								var item_add = $(html);
								
								var last = t.find("[data-widget-item]").last();
								
								if (last[0])
									last.after(item_add);
								else {
									var tbody = t.find("tbody");
									
									if (tbody[0])
										tbody.append(item_add);
									else
										t.append(item_add);
								}
								
								//convert html to LayoutUIEditor widgets
								convertHtmlElementToLayoutUIEditorWidget(item_add);
							});
							
							$.each(tree, function(idx, t) {
								t = $(t);
								var html = getWidgetListTreeItemAddHtml(elm, widget, db_attributes, properties_settings, attributes_to_filter);
								var item_add = $(html);
								
								var last = t.find("[data-widget-item]").last();
								
								if (last[0])
									last.after(item_add);
								else
									t.append(item_add);
								
								//convert html to LayoutUIEditor widgets
								convertHtmlElementToLayoutUIEditorWidget(item_add);
							});
						}
						
						//add buttons
						addWidgetItemAddButton(elm, main_widget, "[data-widget-item-button-add-cancel]", add_cancel_html);
						addWidgetItemAddButton(elm, main_widget, "[data-widget-item-button-add]", add_html);
					}
					else { //else is form main_widget
						addWidgetItemButton(elm, main_widget, "[data-widget-item-button-add-cancel]", add_cancel_html);
						addWidgetItemButton(elm, main_widget, "[data-widget-item-button-add]", add_html);
					}
					
					//add data-widget-item-attribute-field-add if does not exists
					var item_columns_parent = is_list_main_widget ? main_widget.find("[data-widget-item-add]") : main_widget;
					
					item_columns_parent.find("[data-widget-item-column]").each(function(idx, column) {
						column = $(column);
						
						if (column[0].hasAttribute("data-widget-item-attribute-name") && column.find("[data-widget-item-attribute-field-add]").length == 0) {
							var html = getWidgetItemAttributeFieldAddHtml(elm, widget, db_attributes, properties_settings, column.attr("data-widget-item-attribute-name"), add_permissions_html);
							html = $(html);
							
							column.append(html);
							
							//convert html to LayoutUIEditor widgets
							convertHtmlElementToLayoutUIEditorWidget(html);
						}
					});
					
					//add "add" resource with permissions
					addMainWidgetResourceByType(main_widget, "add", "insert", add_permissions);
					var resources_type_props = getWidgetResourcesTypeDefaultProperties("add", "insert");
					addMainWidgetActionPropertiesToLatestAddedWidgetResourcesAttribute(main_widget, "add", resources_type_props);
					
					//add complete[add] callback function to main_widget if it is a widget list or a form
					addMainWidgetCompleteCallbackByType(main_widget, "add", "MyWidgetResourceLib.ListHandler.onAddResourceItem", "MyWidgetResourceLib.FormHandler.onAddResourceItem");
					
					//change onclick event of data-widget-button-add in table and short actions
					var main_widget_btns = main_widget.find("[data-widget-button-add]").toArray();
					var ids = [];
					
					$.each(main_widget, function(idx, item) {
						if (item.id)
							ids.push(item.id);
					});
					
					widget.find("[data-widget-button-add]").each(function(idx, btn) {
						btn = $(btn);
						var on_click = btn.attr("onClick");
						
						if (!on_click || on_click.match(/MyWidgetResourceLib\.PopupHandler\.openButtonAddPopup\s*\(/)) {
							changed = true;
							
							//remove popup id
							btn.removeAttr("data-widget-popup-id"); 
							
							//set new event
							var belongs_to_main_widget = $.inArray(btn[0], main_widget_btns) != -1;
							
							if (belongs_to_main_widget) {
								btn.attr("onClick", "MyWidgetResourceLib.ListHandler.addInlineResourceListItem(this); return false;");
							}
							else {
								btn.attr("onClick", "MyWidgetResourceLib.ShortActionHandler.addInlineResourceListItemToDependentWidgets(this); return false;");
								
								//set dependencies
								if (ids.length > 0) {
									var btn_properties = me.getWidgetProperties(btn);
									
									if (btn_properties.hasOwnProperty("dependent_widgets_id")) {
										if (typeof btn_properties["dependent_widgets_id"] == "string")
											btn_properties["dependent_widgets_id"] = [ btn_properties["dependent_widgets_id"] ];
										
										//add main widget id if not exists yet in dependent_widgets_id
										if ($.isArray(btn_properties["dependent_widgets_id"])) {
											for (var i = 0, t = ids.length; i < t; i++)
												if ($.inArray(ids[i], btn_properties["dependent_widgets_id"]) == -1)
													btn_properties["dependent_widgets_id"].push(ids[i]);
										}
									}
									else 
										btn_properties["dependent_widgets_id"] = ids;
									
									btn.attr("data-widget-props", JSON.stringify(btn_properties));
								}
							}
						}
					}).promise().done( function() {
						if (changed)
							onChangeSelectedWidget();
					});
				}
				
				//remove add popup: [data-widget-popup][data-widget-popup-add].
				removeWidget(popup_add); //This must be at the end, otherwise, if type was equal to popup previously, the system will remove the insert sla resource and then re-create it again, which doesn't make sense, bc we should reuse the existent insert sla resource.
			}
		}
	};
	
	me.onChangeWidgetEditable = function(elm, event) {
		elm = $(elm);
		var ul = elm.parent().children("ul");
		var widget = $(selected_widget);
		var widget_group = widget.closest("[data-widget-group-list], [data-widget-group-form]");
		widget = widget_group ? widget_group : widget;
		
		if (elm.is(":checked")) {
			var main_widget = me.getWidgetGroupMainWidget(widget);
			
			if (!main_widget)
				elm.prop("checked", false).removeAttr("checked");
			else {
				openMenuSettingsSubOptions(elm);
			
				//add html elements according with edit type, like: data-widget-item-attribute-link-edit, data-widget-item-attribute-field-edit, data-widget-item-button-update, data-widget-item-button-toggle-inline-edit-view, data-widget-item-button-edit, data-widget-popup-edit popup.
				me.onChangeWidgetEditableType(ul.find(" > .widget-property-editable-type > select")[0], event);
			}
		}
		else {
			closeMenuSettingsSubOptions(elm);
			
			//remove attribute data-widget-with-button-toggle-inline-edit-view from [data-widget-item]
			removeWidgetWithButtonToggleInlineEditView(widget);
			
			//remove html elements with data-widget-item-attribute-link-edit, data-widget-item-button-update, data-widget-item-button-toggle-inline-edit-view, data-widget-item-button-edit, data-widget-popup-edit popup.
			removeWidget( widget.find("[data-widget-item-attribute-link-edit], [data-widget-item-attribute-field-edit], [data-widget-item-button-update], [data-widget-item-button-toggle-inline-edit-view], [data-widget-item-button-edit], [data-widget-popup-edit]") );
			
			//remove update and update_attribute resources if this resources are not being used anymore
			removeMainWidgetResourcesByType(widget, "update");
			removeMainWidgetResourcesByType(widget, "update_attribute");
		}
	};
	
	me.onChangeWidgetEditableType = function(elm, event) {
		elm = $(elm);
		var li = elm.parent().closest("li");
		var ul = li.parent();
		var value = elm.val();
		var inline_settings_elms = ul.children(".widget-property-editable-with-auto-save, .widget-property-editable-with-save-button");
		var toggle_edit_view_button = ul.children(".widget-property-toggle-edit-view-fields-button");
		
		var widget = $(selected_widget);
		var widget_group = widget.closest("[data-widget-group-list], [data-widget-group-form]");
		widget = widget_group ? widget_group : widget;
		
		var db_attributes = getWidgetGroupDBAttributes(widget);
		
		if (db_attributes && !$.isEmptyObject(db_attributes)) {
			if (!value)
				value = "inline";
			
			var main_widget = me.getWidgetGroupMainWidget(widget);
			var popup_edit = widget.find("[data-widget-popup][data-widget-popup-edit]");
			
			var properties_settings = getWidgetSettingsData(elm, ".widget-properties");
			properties_settings = properties_settings["widget_properties"];
			
			var edit_active = properties_settings["editable"] ? properties_settings["editable"]["active"] : false;
			var toggle_edit_view_button_enabled = properties_settings["viewable"]["active"];// && properties_settings["viewable"]["type"] == "inline";
			
			var item_columns_parent = null;
			
			if (main_widget) {
				var is_list_main_widget = main_widget.is("[data-widget-list], [data-widget-list-table], [data-widget-list-tree], table");
				item_columns_parent = is_list_main_widget ? main_widget.find("[data-widget-item]") : main_widget;
			}
			
			if (!edit_active) {
				ui_creator.showError("Cannot proceed because the 'Editable' option is disabled. Please enable it first!");
				toggleMenuSettingsSubOptions( elm.parent().closest(".widget-property-editable").children(".toggle")[0] );
			}
			//if link: allows users to click the values and open a popup form to edit that element. Add or remove all html elements which are "data-widget-item-button-edit". If yes, the data-widget-popup-edit popup is added, otherwise remove it.
			//	do the same than onChangeWidgetEditableWithEditButton
			else if (value == "link") {
				inline_settings_elms.hide();
				ul.children(".widget-property-editable-with-edit-button").show();
				
				//show or hide toggle button
				if (toggle_edit_view_button_enabled)
					toggle_edit_view_button.show();
				else
					toggle_edit_view_button.hide();
				
				if (main_widget) {
					//remove attribute data-widget-with-button-toggle-inline-edit-view from [data-widget-item]
					removeWidgetWithButtonToggleInlineEditView(main_widget);
					
					//remove data-widget-item-button-update, data-widget-item-button-toggle-inline-edit-view
					removeWidget( main_widget.find("[data-widget-item-attribute-field-edit], [data-widget-item-button-update], [data-widget-item-button-toggle-inline-edit-view]") );
					
					//add data-widget-item-attribute-link-edit if does not exists
					item_columns_parent.find("[data-widget-item-column]").each(function(idx, column) {
						if (column.hasAttribute("data-widget-item-attribute-name")) {
							column = $(column);
							
							if (column.find("[data-widget-item-attribute-link-edit]").length == 0) {
								var html = getWidgetItemAttributeLinkEditHtml(elm, widget, db_attributes, properties_settings, column.attr("data-widget-item-attribute-name"));
								html = $(html);
								
								column.append(html);
								
								//convert html to LayoutUIEditor widgets
								convertHtmlElementToLayoutUIEditorWidget(html);
							}
						}
					});
					
					//add data-widget-popup-edit
					if (!popup_edit) {
						var popup_html = getWidgetPopupEditHtml(elm, widget, db_attributes, properties_settings);
						popup_edit = $(popup_html);
						widget.append(popup_edit);
						
						//convert html to LayoutUIEditor widgets
						convertHtmlElementToLayoutUIEditorWidget(popup_edit);
					}
					
					//add data-widget-item-button-edit and data-widget-popup-edit popup if is checked and does not exist.
					if (properties_settings["editable"]["with_edit_button"]) {
						var html = getWidgetItemActionsButtonEditHtml(elm, widget, db_attributes, properties_settings);
						addWidgetItemButton(elm, main_widget, "[data-widget-item-button-edit]", html);
					}
					else //remove button if exists
						removeWidget( main_widget.find("[data-widget-item-button-edit]") );
					
					//remove complete[update] and complete[update_attribute] callback function from main_widget
					removeMainWidgetCompleteCallbackByType(main_widget, "update");
					removeMainWidgetCompleteCallbackByType(main_widget, "update_attribute");
				}
			}
			//if inline: allows users to edit inside of the row. Add or remove all html elements which are "data-widget-item-attribute-field-edit".
			//	do the same than in onChangeWidgetEditableWithAutoSave and onChangeWidgetEditableWithSaveButton
			//	hide or show auto_save button and save button.
			else if (value == "inline") {
				inline_settings_elms.show();
				ul.children(".widget-property-editable-with-edit-button").show();
				
				//show or hide toggle button
				if (toggle_edit_view_button_enabled)
					toggle_edit_view_button.show();
				else
					toggle_edit_view_button.hide();
				
				//remove popup edit, but only if no edit button
				if (!properties_settings["editable"]["with_edit_button"])
					removeWidget(popup_edit);
				
				if (main_widget) {
					//remove data-widget-item-attribute-link-edit (with auto save if apply)
					removeWidget( main_widget.find("[data-widget-item-attribute-link-edit]") );
					
					//enable auto_save automatically by default if no save button
					if (!properties_settings["editable"]["with_save_button"] && !properties_settings["editable"]["with_auto_save"]) {
						elm.parent().closest("ul").find(" > .widget-property-editable-with-auto-save > input[type=checkbox]").prop("checked", true).attr("checked", "checked");
						properties_settings["editable"]["with_auto_save"] = true;
					}
					
					//add data-widget-item-attribute-field-edit if does not exists
					item_columns_parent.find("[data-widget-item-column]").each(function(idx, column) {
						if (column.hasAttribute("data-widget-item-attribute-name")) {
							column = $(column);
							
							if (column.find("[data-widget-item-attribute-field-edit]").length == 0) {
								var html = getWidgetItemAttributeFieldEditHtml(elm, widget, db_attributes, properties_settings, column.attr("data-widget-item-attribute-name"), properties_settings["editable"]["with_auto_save"]);
								html = $(html);
								
								column.append(html);
								
								//convert html to LayoutUIEditor widgets
								convertHtmlElementToLayoutUIEditorWidget(html);
							}
						}
					});
					
					var edit_permissions = edit_active ? properties_settings["editable"]["permissions"] : false;
					
					//add data-widget-item-button-update (if save button setting exists) if does not exists
					if (properties_settings["editable"]["with_save_button"]) {
						var html = getWidgetItemActionsButtonUpdateHtml(elm, widget, db_attributes, properties_settings);
						addWidgetItemButton(elm, main_widget, "[data-widget-item-button-update]", html);
						
						//add update resource with permissions
						addMainWidgetResourceByType(main_widget, "update", "update", edit_permissions);
						var resources_type_props = getWidgetResourcesTypeDefaultProperties("update", "update");
						addMainWidgetActionPropertiesToLatestAddedWidgetResourcesAttribute(main_widget, "update", resources_type_props);
						
						//add complete[update] callback function to main_widget if it is a widget list or a form
						if (toggle_edit_view_button_enabled && properties_settings["editable"]["toggle_edit_view_fields_button"])
							addMainWidgetCompleteCallbackByType(main_widget, "update", "MyWidgetResourceLib.ItemHandler.updateViewFieldsBasedInEditFields", "MyWidgetResourceLib.ItemHandler.updateViewFieldsBasedInEditFields");
					}
					
					if (properties_settings["editable"]["with_auto_save"]) {
						//add update_attribute resource with permissions
						addMainWidgetResourceByType(main_widget, "update_attribute", "update_attribute", edit_permissions);
						var resources_type_props = getWidgetResourcesTypeDefaultProperties("update_attribute", "update_attribute");
						addMainWidgetActionPropertiesToLatestAddedWidgetResourcesAttribute(main_widget, "update_attribute", resources_type_props);
						
						//add complete[update_attribute] callback function to main_widget if it is a widget list or a form
						if (toggle_edit_view_button_enabled && properties_settings["editable"]["toggle_edit_view_fields_button"])
							addMainWidgetCompleteCallbackByType(main_widget, "update_attribute", "MyWidgetResourceLib.ItemHandler.updateViewFieldsBasedInEditFields", "MyWidgetResourceLib.ItemHandler.updateViewFieldsBasedInEditFields");
					}
					
					//add data-widget-item-button-edit and data-widget-popup-edit popup if is checked and does not exist.
					if (properties_settings["editable"]["with_edit_button"]) {
						var html = getWidgetItemActionsButtonEditHtml(elm, widget, db_attributes, properties_settings);
						addWidgetItemButton(elm, main_widget, "[data-widget-item-button-edit]", html);
					}
					else { //remove button and edit popup if exist
						removeWidget( main_widget.find("[data-widget-item-button-edit]") );
						removeWidget( widget.find("[data-widget-popup][data-widget-popup-edit]") );
					}
					
					//add or remove data-widget-item-button-toggle-inline-edit-view if apply
					if (toggle_edit_view_button_enabled && properties_settings["editable"]["toggle_edit_view_fields_button"]) { //add toggle button
						//add attribute data-widget-with-button-toggle-inline-edit-view to [data-widget-item]
						addWidgetWithButtonToggleInlineEditView(main_widget);
						
						var html = getWidgetItemActionsButtonToggleInlineEditViewHtml(elm, widget, db_attributes, properties_settings);
						addWidgetItemButton(elm, main_widget, "[data-widget-item-button-toggle-inline-edit-view]", html);
					}
					else {
						//remove attribute data-widget-with-button-toggle-inline-edit-view from [data-widget-item]
						removeWidgetWithButtonToggleInlineEditView(main_widget);
						
						//remove toggle button
						removeWidget( main_widget.find("[data-widget-item-button-toggle-inline-edit-view]") );
					}
				}
			}
			//if button: allows users to click the button and open a popup form to edit that element. Add or remove all html elements which are "data-widget-item-button-edit". If yes, the data-widget-popup-edit popup is added, otherwise remove it.
			else if (value == "button") {
				inline_settings_elms.hide();
				ul.children(".widget-property-editable-with-edit-button").hide();
				toggle_edit_view_button.hide();
				
				if (main_widget) {
					//remove attribute data-widget-with-button-toggle-inline-edit-view from [data-widget-item]
					removeWidgetWithButtonToggleInlineEditView(main_widget);
					
					//remove data-widget-item-attribute-link-edit, data-widget-item-button-update, data-widget-item-button-toggle-inline-edit-view
					removeWidget( main_widget.find("[data-widget-item-attribute-link-edit], [data-widget-item-attribute-field-edit], [data-widget-item-button-update], [data-widget-item-button-toggle-inline-edit-view]") );
					
					//add data-widget-item-button-edit and data-widget-popup-edit popup if does not exist.
					var html = getWidgetItemActionsButtonEditHtml(elm, widget, db_attributes, properties_settings);
					addWidgetItemButton(elm, main_widget, "[data-widget-item-button-edit]", html);
					
					//remove complete[update_attribute] callback function from main_widget
					removeMainWidgetCompleteCallbackByType(main_widget, "update_attribute");
				}
			}
		}
	};
	
	//auto_save (checkbox): add or remove the onBlur, onKeyUp, onClick and onChange events.
	me.onChangeWidgetEditableWithAutoSave = function(elm, event) {
		elm = $(elm);
		var widget = $(selected_widget);
		var widget_group = widget.closest("[data-widget-group-list], [data-widget-group-form]");
		widget = widget_group ? widget_group : widget;
		
		var main_widget = me.getWidgetGroupMainWidget(widget);
		var items = main_widget ? main_widget.find("[data-widget-item-attribute-field-edit]") : null; //include fields from main list or main form. Do not incude the items from edit popup, bc the edit_popup doesn't have auto_save.
		var properties_settings = getWidgetSettingsData(elm, ".widget-properties");
		properties_settings = properties_settings["widget_properties"];
		
		var edit_active = properties_settings["editable"] ? properties_settings["editable"]["active"] : false;
		
		if (!edit_active) {
			elm.prop("checked", false).removeAttr("checked");
			
			ui_creator.showError("Cannot proceed because the 'Editable' option is disabled. Please enable it first!");
			toggleMenuSettingsSubOptions( elm.parent().closest(".widget-property-editable").children(".toggle")[0] );
		}
		else if (elm.is(":checked")) {
			if (items)
				items.each(function(idx, item) {
					item = $(item);
					
					if (item.is("input[type=checkbox], input[type=radio]"))
						item.attr("onBlur", "MyWidgetResourceLib.ItemHandler.updateResourceItemAttributeOnBlur(this); return false;").attr("onClick", "MyWidgetResourceLib.ItemHandler.updateResourceItemAttributeOnClick(this); return false;");
					else if (item.is("input"))
						item.attr("onBlur", "MyWidgetResourceLib.ItemHandler.updateResourceItemAttributeOnBlur(this); return false;").attr("onKeyUp", "MyWidgetResourceLib.ItemHandler.updateResourceItemAttributeOnKeyUp(this); return false;");
					else if (item.is("select"))
						item.attr("onBlur", "MyWidgetResourceLib.ItemHandler.updateResourceItemAttributeOnBlur(this); return false;").attr("onChange", "MyWidgetResourceLib.ItemHandler.updateResourceItemAttributeOnChange(this); return false;");
				}).promise().done( function() {
					onChangeSelectedWidget();
				});
			
			
			if (main_widget) {
				//add update_attribute resource with permissions
				var edit_permissions = edit_active ? properties_settings["editable"]["permissions"] : false;
				
				addMainWidgetResourceByType(main_widget, "update_attribute", "update_attribute", edit_permissions);
				var resources_type_props = getWidgetResourcesTypeDefaultProperties("update_attribute", "update_attribute");
				addMainWidgetActionPropertiesToLatestAddedWidgetResourcesAttribute(main_widget, "update_attribute", resources_type_props);
				
				//add complete[update_attribute] callback function to main_widget if it is a widget list or a form
				var toggle_edit_view_button_enabled = properties_settings["viewable"]["active"];// && properties_settings["viewable"]["type"] == "inline";
				
				if (toggle_edit_view_button_enabled && properties_settings["editable"]["toggle_edit_view_fields_button"])
					addMainWidgetCompleteCallbackByType(main_widget, "update_attribute", "MyWidgetResourceLib.ItemHandler.updateViewFieldsBasedInEditFields", "MyWidgetResourceLib.ItemHandler.updateViewFieldsBasedInEditFields");
			}
		}
		else {
			if (items)
				items.removeAttr("onBlur").removeAttr("onKeyUp").removeAttr("onClick").removeAttr("onChange");
			
			//remove update_attribute resource
			removeMainWidgetResourcesByType(widget, "update_attribute");
		}
	};
	
	//save button (checkbox): add or remove a save button at the end of row. Add or remove all html elements which are "data-widget-item-button-update".
	me.onChangeWidgetEditableWithSaveButton = function(elm, event) {
		elm = $(elm);
		var widget = $(selected_widget);
		var widget_group = widget.closest("[data-widget-group-list], [data-widget-group-form]");
		widget = widget_group ? widget_group : widget;
		
		var properties_settings = getWidgetSettingsData(elm, ".widget-properties");
		properties_settings = properties_settings["widget_properties"];
		
		var edit_active = properties_settings["editable"] ? properties_settings["editable"]["active"] : false;
		
		if (!edit_active) {
			elm.prop("checked", false).removeAttr("checked");
			
			ui_creator.showError("Cannot proceed because the 'Editable' option is disabled. Please enable it first!");
			toggleMenuSettingsSubOptions( elm.parent().closest(".widget-property-editable").children(".toggle")[0] );
		}
		else if (elm.is(":checked")) {
			var main_widget = me.getWidgetGroupMainWidget(widget);
			
			if (main_widget) {
				var edit_permissions = edit_active ? properties_settings["editable"]["permissions"] : false;
				
				//add update button to main widget
				var btn_html = getWidgetItemActionsButtonUpdateHtml(elm, widget);
				addWidgetItemButton(elm, main_widget, "[data-widget-item-button-update]", btn_html);
				
				//add update resource with permissions
				addMainWidgetResourceByType(main_widget, "update", "update", edit_permissions);
				var resources_type_props = getWidgetResourcesTypeDefaultProperties("update", "update");
				addMainWidgetActionPropertiesToLatestAddedWidgetResourcesAttribute(main_widget, "update", resources_type_props);
				
				//add complete[update] callback function to main_widget if it is a widget list or a form
				var toggle_edit_view_button_enabled = properties_settings["viewable"]["active"];// && properties_settings["viewable"]["type"] == "inline";
				
				if (toggle_edit_view_button_enabled && properties_settings["editable"]["toggle_edit_view_fields_button"])
					addMainWidgetCompleteCallbackByType(main_widget, "update", "MyWidgetResourceLib.ItemHandler.updateViewFieldsBasedInEditFields", "MyWidgetResourceLib.ItemHandler.updateViewFieldsBasedInEditFields");
				
				//add update button to popup edit if exists
				var popup_edit = widget.find("[data-widget-popup][data-widget-popup-edit]");
				var popup_edit_form = popup_edit.find("[data-widget-form], form");
				
				if (popup_edit_form.length > 0) {
					addWidgetItemButtonToForm(elm, popup_edit_form, "[data-widget-item-button-update]", btn_html);
					
					//add "update" resource to popup
					addWidgetResourceByType(popup_edit_form, "update", "update", edit_permissions);
					var resources_type_props = getWidgetResourcesTypeDefaultProperties("update", "update");
					addWidgetActionPropertiesToLatestAddedWidgetResourcesAttribute(popup_edit_form, "update", resources_type_props);
					
					//add complete[update] callback function to popup
					addWidgetCompleteCallbackByType(popup_edit_form, "update", null, "MyWidgetResourceLib.FormHandler.onUpdatePopupResourceItem");
				}
			}
		}
		else {
			removeWidget( widget.find("[data-widget-item-button-update]") );
			
			//remove update resource
			removeMainWidgetResourcesByType(widget, "update");
		}
	};
	
	//edit button (checkbox): allows users to click the button and open a popup form to edit that element. Add or remove all html elements which are "data-widget-item-button-edit".
	me.onChangeWidgetEditableWithEditButton = function(elm, event) {
		elm = $(elm);
		var widget = $(selected_widget);
		var widget_group = widget.closest("[data-widget-group-list], [data-widget-group-form]");
		widget = widget_group ? widget_group : widget;
		
		var properties_settings = getWidgetSettingsData(elm, ".widget-properties");
		properties_settings = properties_settings["widget_properties"];
		
		var edit_active = properties_settings["editable"] ? properties_settings["editable"]["active"] : false;
		
		if (!edit_active) {
			elm.prop("checked", false).removeAttr("checked");
			
			ui_creator.showError("Cannot proceed because the 'Editable' option is disabled. Please enable it first!");
			toggleMenuSettingsSubOptions( elm.parent().closest(".widget-property-editable").children(".toggle")[0] );
		}
		else if (elm.is(":checked")) {
			var main_widget = me.getWidgetGroupMainWidget(widget);
			
			if (main_widget) {
				var html = getWidgetItemActionsButtonEditHtml(elm, widget);
				addWidgetItemButton(elm, main_widget, "[data-widget-item-button-edit]", html);
			}
		}
		else {
			removeWidget( widget.find("[data-widget-item-button-edit]") );
			
			//remove popup edit if editable type is inline
			var edit_type = edit_active ? properties_settings["editable"]["type"] : false;
			
			if (edit_type == "inline")
				removeWidget( widget.find("[data-widget-popup][data-widget-popup-edit]") );
		}
	};
	
	//toggle between edit and view fields (checkbox): If "viewable details" and "editable" options are selected, then show this option, which will add or remove html elements with data-widget-item-button-toggle-inline-edit-view.
	me.onChangeWidgetToggleEditViewFieldsButton = function(elm, event) {
		elm = $(elm);
		var widget = $(selected_widget);
		var widget_group = widget.closest("[data-widget-group-list], [data-widget-group-form]");
		widget = widget_group ? widget_group : widget;
		
		var properties_settings = getWidgetSettingsData(elm, ".widget-properties");
		properties_settings = properties_settings["widget_properties"];
		
		var edit_active = properties_settings["editable"] ? properties_settings["editable"]["active"] : false;
		
		if (!edit_active) {
			elm.prop("checked", false).removeAttr("checked");
			
			ui_creator.showError("Cannot proceed because the 'Editable' option is disabled. Please enable it first!");
			toggleMenuSettingsSubOptions( elm.parent().closest(".widget-property-editable").children(".toggle")[0] );
		}
		else if (elm.is(":checked")) {
			var main_widget = me.getWidgetGroupMainWidget(widget);
			
			if (main_widget) {
				//add attribute data-widget-with-button-toggle-inline-edit-view to [data-widget-item]
				addWidgetWithButtonToggleInlineEditView(main_widget);
				
				var html = getWidgetItemActionsButtonToggleInlineEditViewHtml(elm, widget);
				addWidgetItemButton(elm, main_widget, "[data-widget-item-button-toggle-inline-edit-view]", html);
				
				if (properties_settings["editable"]["with_save_button"])
					addMainWidgetCompleteCallbackByType(main_widget, "update", "MyWidgetResourceLib.ItemHandler.updateViewFieldsBasedInEditFields", "MyWidgetResourceLib.ItemHandler.updateViewFieldsBasedInEditFields");
				
				if (properties_settings["editable"]["with_auto_save"])
					addMainWidgetCompleteCallbackByType(main_widget, "update_attribute", "MyWidgetResourceLib.ItemHandler.updateViewFieldsBasedInEditFields", "MyWidgetResourceLib.ItemHandler.updateViewFieldsBasedInEditFields");
			}
		}
		else {
			//remove attribute data-widget-with-button-toggle-inline-edit-view from [data-widget-item]
			removeWidgetWithButtonToggleInlineEditView(widget);
			
			removeWidget( widget.find("[data-widget-item-button-toggle-inline-edit-view]") );
			
			removeMainWidgetCompleteCallbackByType(widget, "update");
			removeMainWidgetCompleteCallbackByType(widget, "update_attribute");
		}	
	};
	
	//removable button (checkbox): yes|no. Add or remove all html elements which are "data-widget-item-button-remove", "data-widget-item-button-remove".
	me.onChangeWidgetRemovable = function(elm, event) {
		elm = $(elm);
		var widget = $(selected_widget);
		var widget_group = widget.closest("[data-widget-group-list], [data-widget-group-form]");
		widget = widget_group ? widget_group : widget;
		
		if (elm.is(":checked")) {
			var main_widget = me.getWidgetGroupMainWidget(widget);
			
			if (!main_widget)
				elm.prop("checked", false).removeAttr("checked");
			else {
				openMenuSettingsSubOptions(elm);
				
				var properties_settings = getWidgetSettingsData(elm, ".widget-properties");
				properties_settings = properties_settings["widget_properties"];
				
				var remove_active = properties_settings["removable"] ? properties_settings["removable"]["active"] : false;
				var remove_permissions = remove_active ? properties_settings["removable"]["permissions"] : false;
				
				//add remove button to main widget
				var btn_html = getWidgetItemActionsButtonRemoveHtml(elm, widget);
				addWidgetItemButton(elm, main_widget, "[data-widget-item-button-remove]", btn_html);
				
				//add remove button after update button if exists
				main_widget.find("[data-widget-item-button-remove]").each(function(idx, btn) {
					$(btn).parent().find("[data-widget-item-button-update]").before(btn);
				});
				
				//add "remove" resource with permissions
				addMainWidgetResourceByType(main_widget, "remove", "delete", remove_permissions);
				var resources_type_props = getWidgetResourcesTypeDefaultProperties("remove", "delete");
				addMainWidgetActionPropertiesToLatestAddedWidgetResourcesAttribute(main_widget, "remove", resources_type_props);
				
				//add complete[remove] callback function to main_widget if it is a widget list or a form
				addMainWidgetCompleteCallbackByType(main_widget, "remove", "MyWidgetResourceLib.ListHandler.onRemoveResourceItem", "MyWidgetResourceLib.FormHandler.onRemoveResourceItem");
				
				//add remove button to popup edit if exists
				var popup_edit = widget.find("[data-widget-popup][data-widget-popup-edit]");
				var popup_edit_form = popup_edit.find("[data-widget-form], form");
				
				if (popup_edit_form.length > 0) {
					addWidgetItemButtonToForm(elm, popup_edit_form, "[data-widget-item-button-remove]", btn_html);
					
					//add "remove" resource to popup
					addWidgetResourceByType(popup_edit_form, "remove", "delete", remove_permissions);
					var resources_type_props = getWidgetResourcesTypeDefaultProperties("remove", "delete");
					addWidgetActionPropertiesToLatestAddedWidgetResourcesAttribute(popup_edit_form, "remove", resources_type_props);
					
					//add complete[remove] callback function to popup
					addWidgetCompleteCallbackByType(popup_edit_form, "remove", null, "MyWidgetResourceLib.FormHandler.onRemovePopupResourceItem");
				}
			}
		}
		else {
			closeMenuSettingsSubOptions(elm);
			
			removeWidget( widget.find("[data-widget-item-button-remove]") );
			
			//remove "remove" resource
			removeMainWidgetResourcesByType(widget, "remove");
			
			//remove "remove" resource from edit popup
			var popup_edit = widget.find("[data-widget-popup][data-widget-popup-edit]");
			var popup_edit_form = popup_edit.find("[data-widget-form], form");
			
			if (popup_edit_form.length > 0)
				removeWidgetResourcesByType(popup_edit_form, "remove");
		}
	};
	
	//multiple items actions (checkbox): yes|no. Add or remove all html elements which are "data-widget-list-select-items-head", "data-widget-list-select-items-checkbox", "data-widget-item-selected-checkbox", "data-widget-item-selected-column" and "data-widget-button-multiple-save", "data-widget-button-multiple-remove" (if empty).
	me.onChangeWidgetMultipleItemsActions = function(elm, event) {
		elm = $(elm);
		var value = elm.val();
		var widget = $(selected_widget);
		var widget_group = widget.closest("[data-widget-group-list], [data-widget-group-form]");
		widget = widget_group ? widget_group : widget;
		
		if (value) {
			var main_widget = me.getWidgetGroupMainWidget(widget);
			
			if (!main_widget)
				elm.val("");
			else {
				openMenuSettingsSubOptions(elm);
				addWidgetListMultipleItemsActions(elm, widget);
			}
		}
		else {
			closeMenuSettingsSubOptions(elm);
			removeWidgetListMultipleItemsActions(elm, widget);
		}
		
		//call on change short actions to add the short actions buttons: save, remove, add and toggle buttons
		var input = elm.parent().closest(".widget-properties").find(" > ul > .widget-property-with-short-actions > input[type=checkbox]");
		
		if (input[0])
			me.onChangeWidgetWithShortActions(input[0], event);
	};
	
	//multiple items actions (checkbox): yes|no. Add or remove all html elements which are "data-widget-list-select-items-head", "data-widget-list-select-items-checkbox" and "data-widget-item-selected-checkbox", "data-widget-item-selected-column"
	me.onChangeWidgetListMultipleItemsActions = function(elm, event) {
		elm = $(elm);
		var widget = $(selected_widget);
		
		if (elm.is(":checked")) {
			addWidgetListMultipleItemsActions(elm, widget);
		}
		else {
			removeWidgetListMultipleItemsActions(elm, widget);
		}
	};
	
	//button "add input search field". (Shows or hides data-widget-search-input)
	me.onChangeWidgetSearchWithInput = function(elm, event) {
		elm = $(elm);
		var widget = $(selected_widget);
		var widget_search = widget.closest("[data-widget-group-list], [data-widget-search]");
		widget_search = widget_search.is("[data-widget-group-list]") ? widget_search.find("[data-widget-search]") : widget_search;
		var input = widget_search.find("[data-widget-search-input]");
		
		if (elm.is(":checked")) {
			if (!input[0]) {
				//add widget search
				if (!widget_search[0])
					addWidgetSearch(elm, widget, "input");
				else { //add widget search input
					var html = getWidgetSearchInputHtml(elm, widget);
					html = $(html);
					
					widget_search.prepend(html);
					
					//convert html to LayoutUIEditor widgets
					convertHtmlElementToLayoutUIEditorWidget(html);
				}
			}
		}
		else if (input[0])
			removeWidget(input);
		
		//reload properties menu settings so we can have the attributes updated in the menu_settings
		loadWidgetPropertiesSettingsData(widget);
	};
	
	//button "add select search field". (Shows or hides data-widget-search-select)
	me.onChangeWidgetSearchWithSelect = function(elm, event) {
		elm = $(elm);
		var widget = $(selected_widget);
		var widget_search = widget.closest("[data-widget-search]");
		widget_search = widget_search.is("[data-widget-group-list]") ? widget_search.find("[data-widget-search]") : widget_search;
		var select = widget_search.find("[data-widget-search-select]");
		
		if (elm.is(":checked")) {
			if (!select[0]) {
				//add widget search
				if (!widget_search[0])
					addWidgetSearch(elm, widget, "select");
				else { //add widget search select
					var html = getWidgetSearchSelectHtml(elm, widget);
					html = $(html);
					
					var input = widget_search.find("[data-widget-search-input]");
					
					if (input[0])
						input.after(html);
					else
						widget_search.prepend(html);
					
					//convert html to LayoutUIEditor widgets
					convertHtmlElementToLayoutUIEditorWidget(html);
				}
			}
		}
		else if (select[0])
			removeWidget(select);
		
		//reload properties menu settings so we can have the attributes updated in the menu_settings
		loadWidgetPropertiesSettingsData(widget);
	};
	
	//button "add user choice based in attributes". (Shows or hides data-widget-search-multiple. Shows an UI to choose what attributes should be available)
	me.onChangeWidgetSearchWithUserChoice = function(elm, event) {
		elm = $(elm);
		var widget = $(selected_widget);
		var widget_search = widget.closest("[data-widget-search]");
		widget_search = widget_search.is("[data-widget-group-list]") ? widget_search.find("[data-widget-search]") : widget_search;
		var multiple = widget_search.find("[data-widget-search-multiple]");
		
		if (elm.is(":checked")) {
			if (!multiple[0]) {
				//add widget search
				if (!widget_search[0])
					addWidgetSearch(elm, widget, "user");
				else { //add widget search multiple
					var html = getWidgetSearchUserChoiceHtml(elm, widget);
					html = $(html);
					
					widget_search.append(html);
					
					//convert html to LayoutUIEditor widgets
					convertHtmlElementToLayoutUIEditorWidget(html);
				}
			}
		}
		else if (multiple[0])
			removeWidget(multiple);
		
		//reload properties menu settings so we can have the attributes updated in the menu_settings
		loadWidgetPropertiesSettingsData(widget);
	};
	
	me.onChangeWidgetSearchType = function(elm, event) {
		updateFieldInSelectedWidget(elm);
	};
	
	me.onChangeWidgetSearchOperator = function(elm, event) {
		updateFieldInSelectedWidget(elm);
	};
	
	me.onChangeWidgetSearchCase = function(elm, event) {
		updateFieldInSelectedWidget(elm);
	};
	
	me.onChangeWidgetLoadResourceLoadType = function() { //don't have aerguments bc is a function used in jquery bind and unbind methods
		var select = $(this);
		var load_type_select = select.parent().closest(".settings-widget").find(" > ul > .widget-properties > ul > .widget-property-graph-load-type > select, > ul > .widget-properties > ul > .widget-property-calendar-load-type > select, > ul > .widget-properties > ul > .widget-property-matrix-load-type > select");
		
		load_type_select.val( select.val() );
	};
	
	me.syncWidgetLoadResourceLoadType = function(elm, event) {
		elm = $(elm);
		var load_type = elm.val();
		var resource_load_type_select = elm.parent().closest(".settings-widget").find(".widget-resources .widget-resources-type.widget-resources-load > .widget-resources-type-properties > ul > .widget-property-load-type > select");
		
		resource_load_type_select.val(load_type);
		resource_load_type_select.trigger("change");
	};
	
	me.toggleWidgetGraphDataSetInfo = function(elm, event) {
		event && event.stopPropagation();
		
		$(elm).parent().closest("li").toggleClass("with-widget-group-info");
	};
	
	me.addWidgetGraphDataSet = function(elm, event, do_not_update_widget) {
		event && event.stopPropagation();
		
		var li = $(elm).parent().closest("li");
		var ul = li.children("ul");
		var prefix = ul.attr("prefix");
		var html = getWidgetGraphDataSetSettingsHtml(prefix);
		var item = addItem(elm, event, html);
		item.addClass("selected-widget-setting");
		prepareMenuSettingsEvents(item);
		
		//update resources in select
		me.refreshWidgetExistentResourcesReferences(li);
		
		if (!do_not_update_widget)
			updateFieldInSelectedWidget(item);
		
		return item;
	};
	
	me.removeWidgetGraphDataSet = function(elm, event) {
		removeItem(elm, event, null);
	};
	
	me.onChangeWidgetGraphDataSetDataType = function(elm, event) {
		elm = $(elm);
		var type = elm.val();
		var p = elm.parent().closest("li").parent();
		
		if (type == "resource" || type == "parent") {
			p.children(".widget-property-graph-data-set-data-resource-name").show();
			p.find(" > .widget-property-graph-data-set-data-labels > input").attr("placeHolder", "Attribute name or strings divided by comma delimiter").attr("title", "Attribute name or strings divided by comma delimiter");
			p.find(" > .widget-property-graph-data-set-data-values > input").attr("placeHolder", "Attribute name").attr("title", "Attribute name");
		}
		else {
			p.children(".widget-property-graph-data-set-data-resource-name").hide();
			p.find(" > .widget-property-graph-data-set-data-labels > input").attr("placeHolder", "Strings divided by comma delimiter or JSON array with strings").attr("title", "Strings divided by comma delimiter or JSON array with strings");
			p.find(" > .widget-property-graph-data-set-data-values > input").attr("placeHolder", "Values divided by comma delimiter or JSON array with values").attr("title", "Values divided by comma delimiter or JSON array with values");
		}
	};
	
	me.addWidgetCalendarDataSet = function(elm, event, do_not_update_widget) {
		event && event.stopPropagation();
		
		var li = $(elm).parent().closest("li");
		var ul = li.children("ul");
		var prefix = ul.attr("prefix");
		var html = getWidgetCalendarDataSetSettingsHtml(prefix);
		
		//replace [idx] by the real index because the data-pks-attrs-names already have one [idx] when they exist
		//Note that when we remove a data-set, it will get he new id, which may not be by numeric order, which means that the data_sets will be saved as an object and not as an array. To avoid this case. we had some code in the updateFieldInSelectedWidget to convert the data-sets back to an array.
		var siblings = ul.find(" > li:not(.empty-items) > ul > .widget-property-calendar-data-set-data-type > select");
		var new_index = 0;
		
		$.each(siblings, function(idx, sibling) {
			var name = sibling.getAttribute("name");
			var pos = prefix.length + 1; //'+ 1' bc: 'prefix['
			var index = name.substr(pos, name.indexOf("]", pos) - pos);
			
			if ($.isNumeric(index) && parseInt(index) >= new_index)
				new_index = parseInt(index) + 1;
		});
		
		html = html.replace(/\[idx\]/g, "[" + new_index + "]");
		
		//add item
		var item = addItem(elm, event, html);
		item.addClass("selected-widget-setting");
		prepareMenuSettingsEvents(item);
		
		//update resources in select
		me.refreshWidgetExistentResourcesReferences(li);
		
		if (!do_not_update_widget)
			updateFieldInSelectedWidget(item);
		
		return item;
	};
	
	me.removeWidgetCalendarDataSet = function(elm, event) {
		removeItem(elm, event, null);
	};
	
	me.onChangeWidgetCalendarDataSetDataType = function(elm, event) {
		elm = $(elm);
		var type = elm.val();
		var p = elm.parent().closest("li").parent();
		
		if (type == "resource")
			p.children(".widget-property-calendar-data-set-data-resource-name").show();
		else
			p.children(".widget-property-calendar-data-set-data-resource-name").hide();
	};
	
	me.addWidgetCalendarResourcesDataSet = function(elm, event, do_not_update_widget) {
		event && event.stopPropagation();
		
		var li = $(elm).parent().closest("li");
		var ul = li.children("ul");
		var prefix = ul.attr("prefix");
		var html = getWidgetCalendarResourceDataSetSettingsHtml(prefix);
		
		//replace [idx] by the real index because the data-pks-attrs-names already have one [idx] when they exist
		//Note that when we remove a data-set, it will get he new id, which may not be by numeric order, which means that the data_sets will be saved as an object and not as an array. To avoid this case. we had some code in the updateFieldInSelectedWidget to convert the data-sets back to an array.
		var siblings = ul.find(" > li:not(.empty-items) > ul > .widget-property-calendar-resources-data-set-data-resource-name > select");
		var new_index = 0;
		
		$.each(siblings, function(idx, sibling) {
			var name = sibling.getAttribute("name");
			var pos = prefix.length + 1; //'+ 1' bc: 'prefix['
			var index = name.substr(pos, name.indexOf("]", pos) - pos);
			
			if ($.isNumeric(index) && parseInt(index) >= new_index)
				new_index = parseInt(index) + 1;
		});
		
		html = html.replace(/\[idx\]/g, "[" + new_index + "]");
		
		//add item
		var item = addItem(elm, event, html);
		item.addClass("selected-widget-setting");
		prepareMenuSettingsEvents(item);
		
		//update resources in select
		me.refreshWidgetExistentResourcesReferences(li);
		
		if (!do_not_update_widget)
			updateFieldInSelectedWidget(item);
		
		return item;
	};
	
	me.removeWidgetCalendarResourcesDataSet = function(elm, event) {
		removeItem(elm, event, null);
	};
	
	me.addWidgetCalendarAttrName = function(elm, event) {
		var li = me.addWidgetAttrName(elm, event);
		me.prepareWidgetCalendarAttrName(li);
		
		return li;
	};
	
	me.prepareWidgetCalendarAttrName = function(li) {
		$(li).find("input").removeAttr("onfocus").attr("onBlur", ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onBlurField(this, event)');
	};
	
	me.addWidgetMatrixAttrName = function(elm, event) {
		var li = me.addWidgetAttrName(elm, event);
		me.prepareWidgetMatrixAttrName(li);
		
		return li;
	};
	
	me.prepareWidgetMatrixAttrName = function(li) {
		$(li).find("input").removeAttr("onfocus").attr("onBlur", ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onBlurField(this, event)');
	};
	
	me.addWidgetMatrixFKResourceAttrName = function(elm, event) {
		var li = $(elm).parent().closest("li");
		var table = li.children("table");
		var prefix = table.attr("prefix");
		var tbody = table.children("tbody");
		var html = getWidgetMatrixFKResourceAttrNameHtml(prefix);
		var item = $(html);
		
		tbody.children(".empty-items").hide();
		tbody.append(item);
		
		item.addClass("selected-widget-setting");
		prepareMenuSettingsEvents(item);
		
		return item;
	};
	
	me.removeWidgetMatrixFKResourceAttrName = function(elm, event) {
		var tr = $(elm).parent().closest("tr");
		var tbody = tr.parent();
		tr.remove();
		
		if (tbody.children("tr:not(.empty-items)").length == 0)
			tbody.children(".empty-items").show();
		
		updateFieldInSelectedWidget(tbody);
	};
	
	/* PUBLIC FUNCTIONS - ui_creator.LayoutUIEditorWidgetResource utils */
	
	me.getWidgetPermissions = function(widget) {
		var permissions = widget.attr("data-widget-permissions");
		
		try {
			if (permissions) {
				permissions = permissions.replace(/^\s+/, "").replace(/\s+$/, "").replace(/[\t\n]+/g, ""); //trim attribute value and remove tabs and end_lines, bc the html indent adds some tabs and end_line, which will break the json.
				permissions = permissions.substr(0, 1) == "{" ? ui_creator.parseJson(permissions) : permissions;
				
				if (permissions && !$.isPlainObject(permissions))
					permissions = {view: { user_type_ids: permissions }};
			}
		}
		catch(e) {
			if (console && console.log) 
				console.log(e);
		}
		
		return permissions;
	};
	
	me.getWidgetResourceValue = function(widget) {
		var widget_resource_value = widget.attr("data-widget-resource-value");
		
		try {
			if (widget_resource_value) {
				widget_resource_value = widget_resource_value.replace(/^\s+/, "").replace(/\s+$/, "").replace(/[\t\n]+/g, ""); //trim attribute value and remove tabs and end_lines, bc the html indent adds some tabs and end_line, which will break the json.
				widget_resource_value = widget_resource_value.substr(0, 1) == "{" ? ui_creator.parseJson(widget_resource_value) : null;
			}
		}
		catch(e) {
			if (console && console.log) 
				console.log(e);
		}
		
		return widget_resource_value;
	};
	
	me.getWidgetResources = function(widget) {
		var resources = widget.attr("data-widget-resources");
		
		try {
			if (resources) {
				resources = resources.replace(/^\s+/, "").replace(/\s+$/, "").replace(/[\t\n]+/g, ""); //trim attribute value and remove tabs and end_lines, bc the html indent adds some tabs and end_line, which will break the json.
				
				if (resources) {
					resources = resources.substr(0, 1) == "{" || resources.substr(0, 1) == "[" ? ui_creator.parseJson(resources) : resources;
					
					if ($.isArray(resources) || typeof resources == "string" || ($.isPlainObject(resources) && resources.hasOwnProperty("name"))) {
						if (typeof resources == "string")
							resources = {name: resources};
						
						var key = "load";
						
						if (widget.is("[data-widget-button-multiple-save]"))
							key = "save";
						else if (widget.is("[data-widget-button-multiple-remove]"))
							key = "remove";
						
						var r = {};
						r[key] = resources;
						resources = r;
					}
					
					if ($.isPlainObject(resources)) {
						for (var resources_type in resources) {
							var sub_resources = resources[resources_type];
							
							if (typeof sub_resources == "string")
								resources[resources_type] = [ {name: sub_resources} ];
							else if ($.isPlainObject(sub_resources))
								resources[resources_type] = [sub_resources];
						}
					}
				}
			}
		}
		catch(e) {
			if (console && console.log) {
				console.log(e);
				/*console.log("data-widget-resources:"+widget.attr("data-widget-resources"));
				console.log("resources:");
				console.log(resources);
				console.log("widget:");
				console.log(widget[0]);*/
			}
		}
		
		return resources;
	};
	
	me.getWidgetDefaultAttributesValues = function(widget) {
		var default_attrs_values = widget.attr("data-widget-pks-attrs");
		
		try {
			if (default_attrs_values) {
				default_attrs_values = default_attrs_values.replace(/^\s+/, "").replace(/\s+$/, "").replace(/[\t\n]+/g, ""); //trim attribute value and remove tabs and end_lines, bc the html indent adds some tabs and end_line, which will break the json.
				default_attrs_values = default_attrs_values.substr(0, 1) == "{" ? ui_creator.parseJson(default_attrs_values) : {};
			}
		}
		catch(e) {
			if (console && console.log) 
				console.log(e);
		}
		
		return $.isPlainObject(default_attrs_values) ? default_attrs_values : {};
	};
	
	me.getWidgetProperties = function(widget) {
		var properties = widget.attr("data-widget-props");
		
		try {
			if (properties) {
				properties = properties.replace(/^\s+/, "").replace(/\s+$/, "").replace(/[\t\n]+/g, ""); //trim attribute value and remove tabs and end_lines, bc the html indent adds some tabs and end_line, which will break the json.
				properties = properties.substr(0, 1) == "{" ? ui_creator.parseJson(properties) : {};
			}
		}
		catch(e) {
			if (console && console.log) 
				console.log(e);
		}
		
		return $.isPlainObject(properties) ? properties : {};
	};
	
	me.getWidgetGroupProperties = function(widget) {
		var widget_group = widget.closest("[data-widget-group-list], [data-widget-group-form]");
		
		if (widget_group[0])
			return me.getWidgetProperties(widget_group);
		
		return null;	
	};
	
	me.getNewWidgetIdCount = function(widget_prefix_id) {
		var widget_id = null;
		var count = 0;
		var template_widgets_iframe_body = ui_creator.getTemplateWidgetsIframeBody();
		var exists = false;
		
		do {
			count++;
			widget_id = widget_prefix_id + count;
			exists = template_widgets_iframe_body.find("#" + widget_id).length > 0;
		}
		while(exists);
		
		return count;
	};
	
	me.getNewMainWidgetId = function(widget, widget_prefix_id) {
		var widget_suffix_id = me.getWidgetSuffix(widget);
		widget_suffix_id = widget_suffix_id ? widget_suffix_id : me.getNewWidgetIdCount(widget_prefix_id);
		var widget_id = widget_prefix_id + widget_suffix_id;
		
		return widget_id;
	};
	
	me.getWidgetSuffix = function(widget) {
		if (!widget.attr("id"))
			widget = me.getWidgetGroupMainWidget(widget);
		
		if (widget && widget.attr("id")) {
			if (widget.is("[data-widget-group-list]"))
				return widget.attr("id").substr("widget_group_list_".length);
			else if (widget.is("[data-widget-group-form]"))
				return widget.attr("id").substr("widget_group_form_".length);
			else if (widget.is("[data-widget-list]"))
				return widget.attr("id").substr("widget_list_".length);
			else if (widget.is("[data-widget-form]"))
				return widget.attr("id").substr("widget_form_".length);
		}
		
		return null;
	};
	
	me.getWidgetGroupMainWidget = function(widget) {
		var main_widget = null;
		
		if (widget.is("table, [data-widget-list], form, [data-widget-form]"))
			main_widget = widget;
		else if (widget.is("[data-widget-group-list]"))
			main_widget = widget.find("[data-widget-list]");
		else if (widget.is("[data-widget-group-form]")) {
			var forms = widget.find("[data-widget-form]");
			
			//get widget form that is not in a popup or is first inside of a [data-widget-group-form]
			$.each(forms, function(idx, form) {
				form = $(form);
				var parent = form.parent().closest("[data-widget-group-form], [data-widget-popup]");
				
				if (parent.is("[data-widget-group-form]")) {
					main_widget = form;
					return false;
				}
			});
		}
		
		if (main_widget && main_widget.length == 0)
			main_widget = null;
		
		if (!main_widget && !ignore_main_widget_error) {
			ui_creator.showError("Main widget doesn't exists!");
			
			if (console && console.log)
				console.log("Undefined main widget in getWidgetGroupMainWidget!");
		}
		
		return main_widget;
	};
	
	/* PUBLIC FUNCTIONS - ui_creator.LayoutUIEditorWidgetResource user utils */
	
	me.prepareWidgetBasedInUserSettings = function(widget, settings) {
		if ($.isPlainObject(settings) && settings["db_table"]) {
			var db_broker = settings["db_broker"];
			var db_driver = settings["db_driver"];
			var db_type = settings["db_type"];
			var db_table = settings["db_table"];
			var db_table_alias = settings["db_table_alias"];
			var widget_group = settings["widget_group"];
			var widget_list_type = settings["widget_list_type"];
			var widget_action = settings["widget_action"];
			
			if (widget_group == "list")
				widget.attr("data-widget-group-list", "");
			else if (widget_group == "form")
				widget.attr("data-widget-group-form", "");
			
			widget.addClass("text-right text-end");
			
			//set db properties into widget, so when triggered click, already loads the db properties
			var properties = {
				db_broker: db_broker,
				db_driver: db_driver,
				db_type: db_type,
				db_table: db_table,
				db_table_alias: db_table_alias
			};
			widget.attr("data-widget-props", JSON.stringify(properties));
			
			widget.trigger("click");
			
			//remove anoying messages bc the db broker, driver, table and others are not selected yet
			ui_creator.removeMessages();
			
			//set settings in menu settings and then call me.onChangeWidgetPropertyDBTable
			var menu_settings = ui_creator.getMenuSettings();
			var widget_settings_ul = menu_settings.find(" > .settings-widget > ul");
			var widget_properties_ul = widget_settings_ul.find(" > .widget-properties > ul");
			
			if (widget_group == "list") {
				//prepare list with search, short_actions and pagination
				if (!widget_list_type)
					widget_list_type = "table";
				
				widget_properties_ul.find(" > .widget-property-list-type select").val(widget_list_type);
				widget_properties_ul.find(" > .widget-property-with-search input").prop("checked", true).attr("checked", "checked");
				widget_properties_ul.find(" > .widget-property-with-short-actions input").prop("checked", true).attr("checked", "checked");
				widget_properties_ul.find(" > .widget-property-with-top-pagination input").prop("checked", false).removeAttr("checked");
				widget_properties_ul.find(" > .widget-property-with-bottom-pagination input").prop("checked", true).attr("checked", "checked");
				
				//reset remove all action to empty
				//widget_properties_ul.find(" > .widget-property-multiple-items-actions > select").val(""); //no need - this is done by default
				
				//prepare view action
				widget_properties_ul.find(" > .widget-property-viewable > input").prop("checked", true).attr("checked", "checked");
				widget_properties_ul.find(" > .widget-property-viewable > ul > .widget-property-viewable-type select").val("inline");
				widget_properties_ul.find(" > .widget-property-viewable > ul > .widget-property-viewable-with-view-button input").prop("checked", false).removeAttr("checked");
				
				if (widget_action == "edit") {
					//prepare edit action
					widget_properties_ul.find(" > .widget-property-editable > input").prop("checked", true).attr("checked", "checked");
					widget_properties_ul.find(" > .widget-property-editable > ul > .widget-property-editable-type select").val("button");
					
					//prepare remove action
					widget_properties_ul.find(" > .widget-property-removable > input").prop("checked", true).attr("checked", "checked");
					
					//prepare add action
					widget_properties_ul.find(" > .widget-property-addable > input").prop("checked", true).attr("checked", "checked");
					widget_properties_ul.find(" > .widget-property-addable > ul > .widget-property-addable-type select").val("popup");
					
					//prepare remove all action
					widget_properties_ul.find(" > .widget-property-multiple-items-actions > select").val("remove");
				}
				else if (widget_action == "add") {
					//prepare add action
					widget_properties_ul.find(" > .widget-property-addable > input").prop("checked", true).attr("checked", "checked");
					widget_properties_ul.find(" > .widget-property-addable > ul > .widget-property-addable-type select").val("popup");
				}
				else if (widget_action == "remove") {
					//prepare remove action
					widget_properties_ul.find(" > .widget-property-removable > input").prop("checked", true).attr("checked", "checked");
					
					//prepare remove all action
					widget_properties_ul.find(" > .widget-property-multiple-items-actions > select").val("remove");
				}
			}
			else if (widget_group == "form") {
				if (widget_action == "edit") {
					//prepare view action (remove view action)
					widget_properties_ul.find(" > .widget-property-viewable > input").prop("checked", false).removeAttr("checked");
					
					//prepare edit action
					widget_properties_ul.find(" > .widget-property-editable > input").prop("checked", true).attr("checked", "checked");
					widget_properties_ul.find(" > .widget-property-editable > ul > .widget-property-editable-type select").val("inline");
					widget_properties_ul.find(" > .widget-property-editable > ul > .widget-property-editable-with-auto-save input").prop("checked", false).removeAttr("checked");
					widget_properties_ul.find(" > .widget-property-editable > ul > .widget-property-editable-with-save-button input").prop("checked", true).attr("checked", "checked");
					widget_properties_ul.find(" > .widget-property-editable > ul > .widget-property-editable-with-edit-button input").prop("checked", false).removeAttr("checked");
					
					//prepare remove action
					widget_properties_ul.find(" > .widget-property-removable > input").prop("checked", true).attr("checked", "checked");
					
				}
				else if (widget_action == "add") {
					//prepare view action (remove view action)
					widget_properties_ul.find(" > .widget-property-viewable > input").prop("checked", false).removeAttr("checked");
					
					//prepare add action
					widget_properties_ul.find(" > .widget-property-addable > input").prop("checked", true).attr("checked", "checked");
					widget_properties_ul.find(" > .widget-property-addable > ul > .widget-property-addable-type select").val("inline");
				}
				else {
					//prepare view action
					widget_properties_ul.find(" > .widget-property-viewable > input").prop("checked", true).attr("checked", "checked");
					widget_properties_ul.find(" > .widget-property-viewable > ul > .widget-property-viewable-type select").val("inline");
					widget_properties_ul.find(" > .widget-property-viewable > ul > .widget-property-viewable-with-view-button input").prop("checked", false).removeAttr("checked");
					
					if (widget_action == "remove") {
						//prepare remove action
						widget_properties_ul.find(" > .widget-property-removable > input").prop("checked", true).attr("checked", "checked");
					}
				}
			}
			else {
				ui_creator.showError("Widget group must be list or form");
				return false;
			}
			
			//prepare widget default html
			var elm = widget_properties_ul.find(" > .widget-property-db-table select");
			prepareWidgetWithDefaultHtml(elm[0], widget);
			
			//prepare default values in form
			if (widget_group == "form") {
				var main_widget = me.getWidgetGroupMainWidget(widget);
				
				if (main_widget) {
					if (widget_action == "add") {
						//remove load event from form
						removeMainWidgetResourcesByType(widget, "load");
						//main_widget.removeAttr("data-widget-resources-load").removeAttr("data-widget-item-resources-load"); //Do not remove these attributes bc we need the form to load in case there are inner fields with resources to load, like select boxes with dynamic data.
					}
					else {
						var db_attributes = getWidgetGroupDBAttributes(widget);
						
						if (db_attributes && !$.isEmptyObject(db_attributes)) {
							var default_attrs_values = me.getWidgetDefaultAttributesValues(main_widget);
							default_attrs_values = $.isPlainObject(default_attrs_values) ? default_attrs_values : {};
							
							var no_pks = !hasDBAttributesPKs(db_attributes);
							
							$.each(db_attributes, function(attr_name, attr) {
								if (attr && isDBAttributePK(no_pks, attr_name, attr) && !default_attrs_values.hasOwnProperty(attr_name))
									default_attrs_values[attr_name] = '#_GET[' + attr_name + ']#';
							});
							
							main_widget.attr("data-widget-pks-attrs", JSON.stringify(default_attrs_values));
						}
					}
				}
			}
			
			//update widget
			onChangeSelectedWidget();
			
			//load it again so it can load the pks_attrs_names and shown_attrs_names
			me.loadMenuSettings(widget);
			
			//update label in widget header
			var node_name = widget[0].nodeName.toLowerCase();
			var label = node_name.charAt(0).toUpperCase() + node_name.slice(1);
			ui_creator.updateTemplateWidgetLabel(widget, label);
			
			return true;
		}
		else
			ui_creator.showError("Invalid user settings in prepareWidgetBasedInUserSettings. Please confirm the settings have the db_table setting.");
		
		return false;
	};
	
	me.replaceUserSettingsInWidgetGroup = function(widget, settings) {
		if ($.isPlainObject(settings) && settings["db_table"]) {
			var db_broker = settings["db_broker"];
			var db_driver = settings["db_driver"];
			var db_type = settings["db_type"];
			var db_table = settings["db_table"];
			var db_table_alias = settings["db_table_alias"];
			
			widget.trigger("click");
			
			var menu_settings = ui_creator.getMenuSettings();
			var widget_settings_ul = menu_settings.find(" > .settings-widget > ul");
			var widget_properties_ul = widget_settings_ul.find(" > .widget-properties > ul");
			
			var li = widget_properties_ul.children(".widget-property-db-broker");
			var cur_value = li.hasClass("select-shown") ? li.children("select").val() : li.children("input").val();
			
			if (cur_value != db_broker) {
				loadWidgetSettingValueIntoSwappedField(li, db_broker);
				
				if (li.hasClass("select-shown"))
					li.children("select").trigger("change");
				else
					li.children("input").trigger("blur");
			}
			
			var li = widget_properties_ul.children(".widget-property-db-driver");
			var cur_value = li.hasClass("select-shown") ? li.children("select").val() : li.children("input").val();
			
			if (cur_value != db_driver) {
				loadWidgetSettingValueIntoSwappedField(li, db_driver);
				
				if (li.hasClass("select-shown"))
					li.children("select").trigger("change");
				else
					li.children("input").trigger("blur");
			}
			
			var li = widget_properties_ul.children(".widget-property-db-type");
			var cur_value = li.hasClass("select-shown") ? li.children("select").val() : li.children("input").val();
			
			if (cur_value != db_type) {
				loadWidgetSettingValueIntoSwappedField(li, db_type);
				
				if (li.hasClass("select-shown"))
					li.children("select").trigger("change");
				else
					li.children("input").trigger("blur");
			}
			
			var li = widget_properties_ul.children(".widget-property-db-table");
			var cur_value = li.hasClass("select-shown") ? li.children("select").val() : li.children("input").val();
			
			widget_properties_ul.find(" > .widget-property-db-table-alias input").val(db_table_alias);
			
			if (cur_value != db_table) {
				loadWidgetSettingValueIntoSwappedField(li, db_table);
				
				if (li.hasClass("select-shown"))
					li.children("select").trigger("change");
				else
					li.children("input").trigger("blur");
			}
		}
	};
	
	/* SELECTED WIDGET HTML FUNCTIONS */
	
	function replaceDependentWidgetsId(old_id, new_id) {
		var template_widgets_iframe_body = ui_creator.getTemplateWidgetsIframeBody();
		
		if (template_widgets_iframe_body) {
			var widgets = template_widgets_iframe_body.find("[data-widget-props]");
			var changed = false;
			
			$.each(widgets, function(idx, widget) {
				widget = $(widget);
				
				if (widget[0].hasAttribute("data-widget-props") && widget.attr("data-widget-props").indexOf("dependent_widgets_id") != -1) {
					var properties = me.getWidgetProperties(widget);
					
					if (properties["dependent_widgets_id"]) {
						var dependent_widgets_id = properties["dependent_widgets_id"];
						
						if (typeof dependent_widgets_id == "string")
							dependent_widgets_id = [ dependent_widgets_id ];
						
						if ($.isArray(dependent_widgets_id))
							for (var i = 0, t = dependent_widgets_id.length; i < t; i++)
								if (dependent_widgets_id[i] == old_id) {
									if (new_id)
										dependent_widgets_id[i] = new_id;
									else {
										dependent_widgets_id.splice(i, 1);
										i--
									}
								}
						
						properties["dependent_widgets_id"] = dependent_widgets_id;
						widget.attr("data-widget-props", JSON.stringify(properties));
						
						changed = true;
					}
				}
			}).promise().done( function() {
				if (changed)
					onChangeSelectedWidget();
			});
		}
	}
	
	function prepareSelectedWidgetSettings(widget, widget_settings) {
		var widget_settings_ul = widget_settings.children("ul");
		var widget_resources_ul = widget_settings_ul.find(" > .widget-resources > ul");
		var widget_properties_ul = widget_settings_ul.find(" > .widget-properties > ul");
		
		//remove old settings from another widgets
		widget_settings_ul.find(".selected-widget-setting").remove();
		
		//reset settings fields
		var fields = widget_settings_ul.find("input, select, textarea");
		var swap_parents = [];
		
		for (var i = 0, t = fields.length; i < t; i++) {
			var field = $(fields[i]);
			
			if (field.is("[type=checkbox], [type=radio]"))
				field.prop("checked", false).removeAttr("checked");
			else {
				field.val("");
				
				//prepare swap-input-select
				var li = field.parent();
				
				if (li.hasClass("widget-swap-field") && $.inArray(li[0], swap_parents) == -1)
					swap_parents.push(li[0]);
			}
			
			//remove saved_value if exists
			var saved_value = field.data("saved_value");
			
			if (typeof saved_value != "undefined")
				field.data("saved_value", null);
		}
		
		//prepare widget-swap-fields
		for (var i = 0, t = swap_parents.length; i < t; i++)
			loadWidgetSettingValueIntoSwappedField(swap_parents[i], "");
		
		//add other resources html based in the selected widget
		var resources_types = getSelectedWidgetResourceTypes(widget);
		
		if (resources_types) {
			//update load resource info
			var load_info = resources_types["load"];
			widget_resources_ul.find(" > .widget-resources-load > .widget-group-info").html(load_info);
			
			//prepare html with other resources
			var html = "";
			
			for (var resources_type in resources_types)
				if (resources_type != "load")
					html += getWidgetResourcesTypeSettingsHtml(resources_type, resources_types[resources_type]);
			
			if (html) {
				html = $(html);
				html.addClass("selected-widget-setting");
				
				widget_resources_ul.append(html);
				prepareMenuSettingsEvents(html);
			}
		}
		
		//add properties handler and callbacks funcs, by finding the callbacks fields in the resources and populate the select field with correspondent funcs
		var handler_funcs = getSelectedWidgetMyWidgetResourceLibJSHandlerFunctions(widget);
		var complete_callback_funcs = getSelectedWidgetMyWidgetResourceLibJSCompleteCallbackFunctions(widget);
		var end_callback_funcs = getSelectedWidgetMyWidgetResourceLibJSEndCallbackFunctions(widget);
		var widget_resources_type_elms = widget_resources_ul.children(".widget-resources-type");
		
		$.each(widget_resources_type_elms, function(idx, widget_resources_type_elm) {
			widget_resources_type_elm = $(widget_resources_type_elm);
			var resources_type = widget_resources_type_elm.attr("data-resource-type");
			var widget_resources_type_properties_ul = widget_resources_type_elm.find(" > .widget-resources-type-properties > ul");
			var handler_selects = widget_resources_type_properties_ul.find(".widget-property-handler select");
			var complete_and_end_callback_selects = widget_resources_type_properties_ul.find(".widget-property-complete-callback select, .widget-property-end-callback select");//Do not add " > select", bc we can have multiple selects if parent is .with-extra-callbacks
			
			$.each(handler_selects, function(idx, select) {
				select = $(select);
				var html = "";
				var exists_empty_option = false;
				var funcs = handler_funcs && $.isPlainObject(handler_funcs[resources_type]) ? handler_funcs[resources_type] : null;
				
				if (funcs)
					for (var k in funcs) {
						if (k == "")
							exists_empty_option = true;
						
						html += '<option value="' + k + '" title="' + funcs[k] + '">' + funcs[k] + '</option>';
					}
				
				if (!exists_empty_option) //add default option
					html = '<option value="" selected>-- None --</option>' + html;
				
				select.html(html);
				
				if (funcs && !select.parent().hasClass("select-shown")) //show selects instead of inputs
					me.swapInputAndSelectFields(select.parent().children(".swap-input-select")[0], window.event);
			});
			
			$.each(complete_and_end_callback_selects, function(idx, select) {
				select = $(select);
				var html = "";
				var exists_empty_option = false;
				var funcs = complete_callback_funcs && $.isPlainObject(complete_callback_funcs[resources_type]) ? complete_callback_funcs[resources_type] : null;
				
				if (select.parent().hasClass("widget-property-end-callback"))
					funcs = end_callback_funcs && $.isPlainObject(end_callback_funcs[resources_type]) ? end_callback_funcs[resources_type] : null;
				
				var funcs_exists = $.isPlainObject(funcs) && !$.isEmptyObject(funcs);
				
				if (funcs_exists)
					for (var k in funcs) {
						if (k == "")
							exists_empty_option = true;
						
						html += '<option value="' + k + '" title="' + funcs[k] + '">' + funcs[k] + '</option>';
					}
				
				if (!exists_empty_option) //add default option
					html = '<option value="" selected>-- None --</option>' + html;
				
				select.html(html);
				
				if (funcs_exists && !select.parent().hasClass("select-shown")) //show selects instead of inputs
					me.swapInputAndSelectFields(select.parent().children(".swap-input-select")[0], window.event);
			});
		});
		
		//reset the extra callbacks and events
		widget_settings_ul.find(".with-extra-callbacks > .extra-callbacks li, .with-extra-events > .extra-events li").remove();
		
		//add some load fields if select field
		if (widget.is("select")) {
			var html = getSelectedWidgetFieldSelectLoadResourcePropertiesSettingsHtml(widget);
			
			if (html) {
				html = $(html);
				html.addClass("selected-widget-setting");
				
				widget_resources_type_elms.filter(".widget-resources-load").find(" > .widget-resources-type-properties > ul > li").last().after(html);
				prepareMenuSettingsEvents(html);
			}
		}
		
		//add properties handlers html
		var html = getSelectedWidgetPropertiesHandlersSettingsHtml(widget);
		
		if (html) {
			html = $(html);
			html.addClass("selected-widget-setting");
			
			widget_properties_ul.find(".widget-property-widget-type").after(html);
			prepareMenuSettingsEvents(html);
		}
		
		//add properties html
		var html = getSelectedWidgetPropertiesSettingsHtml(widget);
		
		if (html) {
			html = $(html);
			html.addClass("selected-widget-setting");
			
			widget_properties_ul.append(html);
			prepareMenuSettingsEvents(html);
		}
		
		//prepare other settings
		prepareSelectedWidgetPropertiesInfoSettings(widget, widget_settings);
		
		//remove onclick property from the LayoutUIEDitor default settings, bc they were added in the widget settings
		var on_click_setting = widget_settings.parent().closest(".menu-settings").find(".settings-events .settings-onclick");
		var on_click_setting_input = on_click_setting.children("input");
		var on_click_setting_add_icon = on_click_setting.children(".add-extra-event");
		var on_click_setting_clear_icon = on_click_setting.children(".clear-user-input");
		
		if (widget[0].hasAttribute("data-widget-item-attribute-link-view") || widget[0].hasAttribute("data-widget-item-attribute-link-edit") || widget[0].hasAttribute("data-widget-button-add") || widget[0].hasAttribute("data-widget-item-button-edit") || widget[0].hasAttribute("data-widget-item-button-view")) {
			//on_click_setting_input.attr("disabled", "disabled");
			on_click_setting_input.attr("title_bkp", on_click_setting_input.attr("title"));
			on_click_setting_input.attr("title", "To edit the onClick event please go to the Widget-Properties Settings.");
			
			on_click_setting_input.attr("onKeyUp_bkp", on_click_setting_input.attr("onKeyUp"));
			on_click_setting_input.attr("onKeyUp", ui_creator.obj_var_name + ".LayoutUIEditorWidgetResource.onKeyUpWidgetClickEvent(this, event)");
			
			on_click_setting_input.attr("onBlur_bkp", on_click_setting_input.attr("onBlur"));
			on_click_setting_input.attr("onBlur", ui_creator.obj_var_name + ".LayoutUIEditorWidgetResource.onBlurWidgetClickEvent(this, event)");
			
			on_click_setting_add_icon.attr("onClick_bkp", on_click_setting_add_icon.attr("onClick"));
			on_click_setting_add_icon.attr("onClick", ui_creator.obj_var_name + ".LayoutUIEditorWidgetResource.preparedAddedExtraClickEvent(this, event)");
			
			on_click_setting_clear_icon.attr("onClick_bkp", on_click_setting_clear_icon.attr("onClick"));
			on_click_setting_clear_icon.attr("onClick", ui_creator.obj_var_name + ".LayoutUIEditorWidgetResource.onBlurWidgetClickEvent($(this).parent().children('input')[0], event)");
		}
		else if (on_click_setting_input[0].hasAttribute("title_bkp")) {
			//on_click_setting_input.removeAttr("disabled");
			on_click_setting_input.attr("title", on_click_setting_input.attr("title_bkp"));
			on_click_setting_input.removeAttr("title_bkp");
			
			on_click_setting_input.attr("onKeyUp", on_click_setting_input.attr("onKeyUp_bkp"));
			on_click_setting_input.removeAttr("onKeyUp_bkp");
			
			on_click_setting_input.attr("onBlur", on_click_setting_input.attr("onBlur_bkp"));
			on_click_setting_input.removeAttr("onBlur_bkp");
			
			on_click_setting_add_icon.attr("onClick", on_click_setting_add_icon.attr("onClick_bkp"));
			on_click_setting_add_icon.removeAttr("onClick_bkp");
			
			on_click_setting_clear_icon.attr("onClick", on_click_setting_clear_icon.attr("onClick_bkp"));
			on_click_setting_clear_icon.removeAttr("onClick_bkp");
		}
		
		//prepare available db attributes for list, form and search widgets
		if (widget[0].hasAttribute("data-widget-search") || widget[0].hasAttribute("data-widget-search-input") || widget[0].hasAttribute("data-widget-search-select") || widget[0].hasAttribute("data-widget-list") || widget[0].hasAttribute("data-widget-form")) {
			var parent_widget = widget.parent().closest("[data-widget-group-list], [data-widget-group-form]");
			var properties = me.getWidgetProperties(parent_widget);
			updateWidgetDBAttributes(widget_properties_ul, properties["db_broker"], properties["db_driver"], properties["db_type"], properties["db_table"]);
		}
		
		//prepare widget-graph, widget-calendar and widget-matrix
		var resource_load_type_select = widget_resources_ul.find(".widget-resources-type.widget-resources-load > .widget-resources-type-properties > ul > .widget-property-load-type > select");
		
		if (widget[0].hasAttribute("data-widget-graph") || widget[0].hasAttribute("data-widget-calendar") || widget[0].hasAttribute("data-widget-matrix"))
			resource_load_type_select.bind("change", me.onChangeWidgetLoadResourceLoadType);
		else
			resource_load_type_select.unbind("change", me.onChangeWidgetLoadResourceLoadType);
		
		//remove on blur event and add new ones
		var setting_id_input = widget_settings.parent().closest(".menu-settings").find(" > .settings-id input");
		setting_id_input.attr("onFocus", ui_creator.obj_var_name + ".LayoutUIEditorWidgetResource.onFocusField(this, event)").attr("onBlur", ui_creator.obj_var_name + ".LayoutUIEditorWidgetResource.onBlurWidgetId(this, event)");
	}
	
	function prepareSelectedWidgetPropertiesInfoSettings(widget, widget_settings) {
		var elm = widget[0];
		var node_name = elm.nodeName.toUpperCase();
		
		if (elm.hasAttribute("data-widget-group-list") || elm.hasAttribute("data-widget-group-form")) {
			var info = 'Note that any change in these properties will take imediate effect in the inner html and correspondent elements, overwriting any previous changes...';
			
			var widget_properties_ul = widget_settings.find("ul > .widget-properties > ul");
			var group_info = widget_properties_ul.children(".widget-group-info");
			
			if (!group_info[0]) {
				group_info = $('<div class="widget-group-info selected-widget-setting"></div>');
				widget_properties_ul.children(".group-title").after(group_info);
			}
			else {
				var aux = $('<div class="selected-widget-setting"></div>');
				group_info.append(aux);
				group_info = aux;
			}
			
			group_info.append(info);
		}
	}
	
	function getSelectedWidgetPropertiesHandlersSettingsHtml(widget) {
		var elm = widget[0];
		var handler_funcs = getSelectedWidgetMyWidgetResourceLibJSHandlerFunctions(widget);
		
		if (elm.hasAttribute("data-widget-pagination-go-to-page-dropdown"))
			return getWidgetPropertiesHandlerHtml("get", handler_funcs["get"], false) 
				+ getWidgetPropertiesHandlerHtml("set", handler_funcs["set"], true);
		
		if (elm.hasAttribute("data-widget-popup"))
			return getWidgetPropertiesHandlerHtml("show", handler_funcs["show"], true)
				+ getWidgetPropertiesHandlerHtml("hide", handler_funcs["hide"], true);
		
		return null;
	}
	
	function getSelectedWidgetPropertiesSettingsHtml(widget) {
		var html = "";
		var elm = widget[0];
		var node_name = elm.nodeName.toUpperCase();
		
		if (elm.hasAttribute("data-widget-group-list"))
			html += getSelectedWidgetGroupListPropertiesSettingsHtml(widget);
		
		if (elm.hasAttribute("data-widget-group-form"))
			html += getSelectedWidgetGroupFormPropertiesSettingsHtml(widget);
		
		if (elm.hasAttribute("data-widget-list"))
			html += getSelectedWidgetListPropertiesSettingsHtml(widget);
		
		if (elm.hasAttribute("data-widget-form"))
			html += getSelectedWidgetFormPropertiesSettingsHtml(widget);
		
		if (elm.hasAttribute("data-widget-list-caption"))
			html += getSelectedWidgetListCaptionPropertiesSettingsHtml(widget);
		
		if (elm.hasAttribute("data-widget-search"))
			html += getSelectedWidgetSearchPropertiesSettingsHtml(widget);
		
		if (elm.hasAttribute("data-widget-search-input"))
			html += getSelectedWidgetSearchInputPropertiesSettingsHtml(widget);
		
		if (elm.hasAttribute("data-widget-search-select"))
			html += getSelectedWidgetSearchSelectPropertiesSettingsHtml(widget);
		
		if (elm.hasAttribute("data-widget-search-multiple-button"))
			html += getSelectedWidgetSearchMultipleButtonPropertiesSettingsHtml(widget);
		
		if (elm.hasAttribute("data-widget-button-multiple-remove"))
			html += getSelectedWidgetButtonMultipleRemovePropertiesSettingsHtml(widget);
		
		if (elm.hasAttribute("data-widget-button-multiple-save"))
			html += getSelectedWidgetButtonMultipleSavePropertiesSettingsHtml(widget);
		
		if (elm.hasAttribute("data-widget-pagination"))
			html += getSelectedWidgetPaginationPropertiesSettingsHtml(widget);
		
		if (elm.hasAttribute("data-widget-item-head") || elm.hasAttribute("data-widget-item-column"))
			html += getSelectedWidgetItemAttributeNamePropertiesSettingsHtml(widget);
		
		if (elm.hasAttribute("data-widget-item-attribute-link-view") || elm.hasAttribute("data-widget-item-attribute-link-edit") || elm.hasAttribute("data-widget-button-add") || elm.hasAttribute("data-widget-item-button-edit") || elm.hasAttribute("data-widget-item-button-view"))
			html += getSelectedWidgetButtonSettingsHtml(widget);
		
		if (elm.hasAttribute("data-widget-html-node"))
			html += getSelectedWidgetHtmlNodePropertiesSettingsHtml(widget);
		
		if (elm.hasAttribute("data-widget-graph"))
			html += getSelectedWidgetGraphPropertiesSettingsHtml(widget);
		
		if (elm.hasAttribute("data-widget-calendar"))
			html += getSelectedWidgetCalendarPropertiesSettingsHtml(widget);
		
		if (elm.hasAttribute("data-widget-matrix"))
			html += getSelectedWidgetMatrixPropertiesSettingsHtml(widget);
		
		if (elm.hasAttribute("data-widget-matrix-head-row") || elm.hasAttribute("data-widget-matrix-body-row"))
			html += getSelectedWidgetMatrixRowPropertiesSettingsHtml(widget);
		
		if (
			elm.hasAttribute("data-widget-matrix-head-column") || 
			(elm.hasAttribute("data-widget-matrix-body-column") && (
				elm.nodeName == "TH" ||
				elm.parentNode.closest("thead, [data-widget-matrix-head-row]")
			))
		)
			html += getSelectedWidgetMatrixColumnPropertiesSettingsHtml(widget);
		
		return html;
	}
	
	function getSelectedWidgetGroupListPropertiesSettingsHtml(widget) {
		return '<li class="widget-property-db-broker widget-swap-field select-shown">'
			 + '	<label>DB Broker: <span class="mandatory">*</span> </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[db_broker]" placeHolder="DB broker name" />'
			 + '	<select name="widget_properties[db_broker]" class="select-existent-widget-db-brokers" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetPropertyDBBroker(this, event);">'
			 + '		<option value="">-- Default --</option>'
			 + '	</select>'
			 + '	<i class="zmdi zmdi-swap swap-input-select" title="Swap between pre-defined options or open text option" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.swapInputAndSelectFields(this, event)"></i>'
			 + '</li>'
			 + '<li class="widget-property-db-driver widget-swap-field select-shown">'
			 + '	<label>DB Driver: <span class="mandatory">*</span> </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[db_driver]" placeHolder="DB driver name" />'
			 + '	<select name="widget_properties[db_driver]" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetPropertyDBDriver(this, event);">'
			 + '		<option value="">-- Default --</option>'
			 + '	</select>'
			 + '	<i class="zmdi zmdi-swap swap-input-select" title="Swap between pre-defined options or open text option" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.swapInputAndSelectFields(this, event)"></i>'
			 + '</li>'
			 + '<li class="widget-property-db-type">'
			 + '	<label>DB Type: <span class="mandatory">*</span> </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<select name="widget_properties[db_type]" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetPropertyDBType(this, event);">'
			 + '		<option value="db">From DB Server</option>'
			 + '		<option value="diagram">From DB Diagram</option>'
			 + '	</select>'
			 + '</li>'
			 + '<li class="widget-property-db-table widget-swap-field select-shown" title="Choose a table to show its records">'
			 + '	<label>DB Table: <span class="mandatory">*</span> </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[db_table]" placeHolder="DB table name" onFocus="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onFocusWidgetPropertyDBTable(this, event);" onBlur="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onBlurWidgetPropertyDBTable(this, event);" />'
			 + '	<select name="widget_properties[db_table]" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetPropertyDBTable(this, event);" onFocus="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onFocusWidgetPropertyDBTable(this, event);">'
			 + '		<option value=""></option>'
			 + '	</select>'
			 + '	<i class="zmdi zmdi-swap swap-input-select" title="Swap between pre-defined options or open text option" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.swapInputAndSelectFields(this, event)"></i>'
			 + '</li>'
			 + '<li class="widget-property-db-table-alias" title="Write a table alias if apply or leave it blank for default">'
			 + '	<label>DB Table Alias: <span class="mandatory">*</span> </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[db_table_alias]" placeHolder="DB table alias" />'
			 + '</li>'
			 + '<li class="widget-property-shown-attrs-names" title="Choose the attributes to show">'
			 + '	<label>Attributes to Show: <span class="mandatory">*</span> </label>'
			 + '	<i class="zmdi zmdi-plus-square widget-item-add" title="Add attribute to show" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addWidgetAttrName(this, event)"></i>'
			 + '	<select class="ignore">'
			 + '		<option value="">-- User Defined --</option>'
			 + '	</select>'
			 + '	<ul prefix="widget_properties[shown_attrs_names]">'
			 + '		<li class="empty-items">No attributes defined...</li>'
			 + '	</ul>'
			 + '</li>'
			 + '<li class="widget-property-pks-attrs-names" title="Attribute name(s) that correspondent to the table Primary Key or unique identifier of each record.">'
			 + '	<label>Primary Keys:</label>'
			 + '	<i class="zmdi zmdi-plus-square widget-item-add" title="Add Primary key attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addWidgetAttrName(this, event)"></i>'
			 + '	<select class="ignore">'
			 + '		<option value="">-- User Defined --</option>'
			 + '	</select>'
			 + '	<ul prefix="widget_properties[pks_attrs_names]">'
			 + '		<li class="empty-items">No pks defined...</li>'
			 + '	</ul>'
			 + '</li>'
			 
			 + '<li class="widget-property-with-search widget-property-checkbox" title="Add search fields at top of the list">'
			 + '	<label>With Search: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[with_search]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetWithSearch(this, event)" />'
			 + '	<!--select name="widget_properties[with_search]" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetWithSearch(this, event)">'
			 + '		<option value="">-- None --</option>'
			 + '		<option value="input">Free Text</option>'
			 + '		<option value="select">Pre-defined Values</option>'
			 + '		<option value="user">User Choice</option>'
			 + '	</select-->'
			 + '</li>'
			 + '<li class="widget-property-with-short-actions widget-property-checkbox" title="Add save and remove buttons at top of the list">'
			 + '	<label>With Short Actions: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[with_short_actions]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetWithShortActions(this, event)" />'
			 + '</li>'
			 + '<li class="widget-property-with-top-pagination widget-property-checkbox" title="Add pagination at top of the list">'
			 + '	<label>With Top Pagination: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[with_top_pagination]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetWithTopPagination(this, event)" />'
			 + '</li>'
			 + '<li class="widget-property-with-bottom-pagination widget-property-checkbox" title="Add pagination at bottom of the list">'
			 + '	<label>With Bottom Pagination: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[with_bottom_pagination]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetWithBottomPagination(this, event)" />'
			 + '</li>'
			 
			 + '<li class="widget-property-list-type">'
			 + '	<label>List Type: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<select name="widget_properties[list_type]" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetListType(this, event, true)">'
			 + '		<option value="">-- None --</option>'
			 + '		<option value="table" title="Table layout">Table</option>'
			 + '		<option value="tree" title="Tree layout based in ul/li">Tree</option>'
			 + '		<option value="both" title="Table and tree layout">Table and Tree</option>'
			 + '	</select>'
			 + '</li>'
			 + '<li class="widget-property-viewable widget-property-checkbox with-sub-options">'
			 + '	<i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>'
			 + '	<label title="With Viewable Details">With Viewable Details: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[viewable][active]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetViewable(this, event)" title="Allow item to be viewed" checked />'
			 + '	<ul>'
			 + '		<li class="widget-property-viewable-type">'
			 + '			<label>View Type: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<select name="widget_properties[viewable][type]" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetViewableType(this, event)">'
			 + '				<option value="inline" title="Simply shows attribute">Inline</option>'
			 + '				<option value="link" title="Shows attribute in a link to open popup with details">Link</option>'
			 + '				<!--option value="button" title="Simply shows attribute and a button in the last column to open popup with details">Button</option-->'
			 + '			</select>'
			 + '		</li>'
			 + '		<li class="widget-property-viewable-with-view-button widget-property-checkbox" title="If active, a button will be added to open a popup to view the correspondent item.">'
			 + '			<label>With View Button in View Mode: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<input name="widget_properties[viewable][with_view_button]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetViewableWithViewButton(this, event)" />'
			 + '		</li>'
			 + '		<li class="widget-property-action-permissions">'
			 + '			<label>Permissions: </label>'
			 + 			getWidgetPermissionSettingsHtml("show", "widget_properties[viewable][permissions][show]")
			 + 			getWidgetPermissionSettingsHtml("hide", "widget_properties[viewable][permissions][hide]")
			 + 			getWidgetPermissionSettingsHtml("remove", "widget_properties[viewable][permissions][remove]")
			 + '		</li>'
			 + '	</ul>'
			 + '</li>'
			 + '<li class="widget-property-addable widget-property-checkbox with-sub-options">'
			 + '	<i class="zmdi zmdi-caret-right toggle" title="Toggle sub-Options"></i>'
			 + '	<label title="With Add Action">With Add Action: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[addable][active]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetAddable(this, event)" title="Allow item to be added" />'
			 + '	<ul>'
			 + '		<li class="widget-property-addable-type">'
			 + '			<label>Panel Type: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<select name="widget_properties[addable][type]" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetAddableType(this, event)">'
			 + '				<option value="" title="A popup will be open to add a new item">Popup</option>'
			 + '				<option value="inline" title="An inline item will be added to the list so the user can fill the fields.">Inline</option>'
			 + '			</select>'
			 + '		</li>'
			 + '		<li class="widget-property-action-permissions">'
			 + '			<label>Permissions: </label>'
			 + 			getWidgetPermissionSettingsHtml("show", "widget_properties[addable][permissions][show]")
			 + 			getWidgetPermissionSettingsHtml("hide", "widget_properties[addable][permissions][hide]")
			 + 			getWidgetPermissionSettingsHtml("remove", "widget_properties[addable][permissions][remove]")
			 + '		</li>'
			 + '	</ul>'
			 + '</li>'
			 + '<li class="widget-property-editable widget-property-checkbox with-sub-options">'
			 + '	<i class="zmdi zmdi-caret-right toggle" title="Toggle sub-Options"></i>'
			 + '	<label title="With Edit Action">With Edit Action: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[editable][active]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetEditable(this, event)" title="Allow item to be edit" />'
			 + '	<ul>'
			 + '		<li class="widget-property-editable-type">'
			 + '			<label>Edit Type: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<select name="widget_properties[editable][type]" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetEditableType(this, event)">'
			 + '				<option value="inline" title="Shows editable attribute">Inline</option>'
			 + '				<option value="link" title="Shows attribute in a link to open popup to edit details">Link</option>'
			 + '				<option value="button" title="Shows editable attribute and a button in the last column to open popup to edit details">Button</option>'
			 + '			</select>'
			 + '		</li>'
			 + '		<li class="widget-property-editable-with-auto-save widget-property-checkbox" title="If active, every change will be saved instantaneously.">'
			 + '			<label>With Auto Save: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<input name="widget_properties[editable][with_auto_save]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetEditableWithAutoSave(this, event)" />'
			 + '		</li>'
			 + '		<li class="widget-property-editable-with-save-button widget-property-checkbox" title="If active, a button will be added to save the item editable fields.">'
			 + '			<label>With Save Button: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<input name="widget_properties[editable][with_save_button]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetEditableWithSaveButton(this, event)" />'
			 + '		</li>'
			 + '		<li class="widget-property-editable-with-edit-button widget-property-checkbox" title="If active, a button will be added to open a popup to edit the correspondent item.">'
			 + '			<label>With Edit Button in View Mode: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<input name="widget_properties[editable][with_edit_button]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetEditableWithEditButton(this, event)" />'
			 + '		</li>'
			 + '		<li class="widget-property-toggle-edit-view-fields-button widget-property-checkbox" title="If active, a button will be added to toggle between the edit and readonly fields.">'
			 + '			<label>With Toggle Edit-View Fields Button: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<input name="widget_properties[editable][toggle_edit_view_fields_button]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetToggleEditViewFieldsButton(this, event)" />'
			 + '		</li>'
			 + '		<li class="widget-property-action-permissions">'
			 + '			<label>Permissions: </label>'
			 + 			getWidgetPermissionSettingsHtml("show", "widget_properties[editable][permissions][show]")
			 + 			getWidgetPermissionSettingsHtml("hide", "widget_properties[editable][permissions][hide]")
			 + 			getWidgetPermissionSettingsHtml("remove", "widget_properties[editable][permissions][remove]")
			 + '		</li>'
			 + '	</ul>'
			 + '</li>'
			 + '<li class="widget-property-removable widget-property-checkbox with-sub-options">'
			 + '	<i class="zmdi zmdi-caret-right toggle" title="Toggle sub-Options"></i>'
			 + '	<label title="With Remove Action">With Remove Action: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[removable][active]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetRemovable(this, event)" title="Add a button to remove the correspondent item" />'
			 + '	<ul>'
			 + '		<li class="widget-property-action-permissions">'
			 + '			<label>Permissions: </label>'
			 + 			getWidgetPermissionSettingsHtml("show", "widget_properties[removable][permissions][show]")
			 + 			getWidgetPermissionSettingsHtml("hide", "widget_properties[removable][permissions][hide]")
			 + 			getWidgetPermissionSettingsHtml("remove", "widget_properties[removable][permissions][remove]")
			 + '		</li>'
			 + '	</ul>'
			 + '</li>'
			 + '<li class="widget-property-multiple-items-actions widget-property-checkbox with-sub-options">'
			 + '	<i class="zmdi zmdi-caret-right toggle" title="Toggle sub-Options"></i>'
			 + '	<label title="With Multiple Items Actions">With Multiple Items Actions: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<select name="widget_properties[multiple_items_actions][type]" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetMultipleItemsActions(this, event)">'
			 + '		<option value="" title="No actions for selected items">-- None --</option>'
			 + '		<option value="remove" title="Add button to remove the selected items">Multiple Remove</option>'
			 + '		<option value="save" title="Add button to save the selected items">Multiple Save</option>'
			 + '		<option value="both" title="Add remove and save buttons to remove/save the selected items">Multiple Remove and Save</option>'
			 + '	</select>'
			 + '	<ul>'
			 + '		<li class="widget-property-action-permissions">'
			 + '			<label>Permissions: </label>'
			 + 			getWidgetPermissionSettingsHtml("show", "widget_properties[multiple_items_actions][permissions][show]")
			 + 			getWidgetPermissionSettingsHtml("hide", "widget_properties[multiple_items_actions][permissions][hide]")
			 + 			getWidgetPermissionSettingsHtml("remove", "widget_properties[multiple_items_actions][permissions][remove]")
			 + '		</li>'
			 + '	</ul>'
			 + '</li>';
	}
	
	function getSelectedWidgetGroupFormPropertiesSettingsHtml(widget) {
		return '<li class="widget-property-db-broker widget-swap-field select-shown">'
			 + '	<label>DB Broker: <span class="mandatory">*</span> </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[db_broker]" placeHolder="DB broker name" />'
			 + '	<select name="widget_properties[db_broker]" class="select-existent-widget-db-brokers" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetPropertyDBBroker(this, event);">'
			 + '		<option value="">-- Default --</option>'
			 + '	</select>'
			 + '	<i class="zmdi zmdi-swap swap-input-select" title="Swap between pre-defined options or open text option" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.swapInputAndSelectFields(this, event)"></i>'
			 + '</li>'
			 + '<li class="widget-property-db-driver widget-swap-field select-shown">'
			 + '	<label>DB Driver: <span class="mandatory">*</span> </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[db_driver]" placeHolder="DB driver name" />'
			 + '	<select name="widget_properties[db_driver]" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetPropertyDBDriver(this, event);">'
			 + '		<option value="">-- Default --</option>'
			 + '	</select>'
			 + '	<i class="zmdi zmdi-swap swap-input-select" title="Swap between pre-defined options or open text option" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.swapInputAndSelectFields(this, event)"></i>'
			 + '</li>'
			 + '<li class="widget-property-db-type">'
			 + '	<label>DB Type: <span class="mandatory">*</span> </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<select name="widget_properties[db_type]" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetPropertyDBType(this, event);">'
			 + '		<option value="db">From DB Server</option>'
			 + '		<option value="diagram">From DB Diagram</option>'
			 + '	</select>'
			 + '</li>'
			 + '<li class="widget-property-db-table widget-swap-field select-shown" title="Choose a table to show its records">'
			 + '	<label>DB Table: <span class="mandatory">*</span> </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[db_table]" placeHolder="DB table name" onFocus="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onFocusWidgetPropertyDBTable(this, event);" onBlur="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onBlurWidgetPropertyDBTable(this, event);" />'
			 + '	<select name="widget_properties[db_table]" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetPropertyDBTable(this, event);" onFocus="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onFocusWidgetPropertyDBTable(this, event);">'
			 + '		<option value=""></option>'
			 + '	</select>'
			 + '	<i class="zmdi zmdi-swap swap-input-select" title="Swap between pre-defined options or open text option" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.swapInputAndSelectFields(this, event)"></i>'
			 + '</li>'
			 + '<li class="widget-property-db-table-alias" title="Write a table alias if apply">'
			 + '	<label>DB Table Alias: <span class="mandatory">*</span> </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[db_table_alias]" placeHolder="DB table alias" />'
			 + '</li>'
			 + '<li class="widget-property-shown-attrs-names" title="Choose the attributes to show">'
			 + '	<label>Attributes to Show: <span class="mandatory">*</span> </label>'
			 + '	<i class="zmdi zmdi-plus-square widget-item-add" title="Add attribute to show" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addWidgetAttrName(this, event)"></i>'
			 + '	<select class="ignore">'
			 + '		<option value="">-- User Defined --</option>'
			 + '	</select>'
			 + '	<ul prefix="widget_properties[shown_attrs_names]">'
			 + '		<li class="empty-items">No attributes defined...</li>'
			 + '	</ul>'
			 + '</li>'
			 + '<li class="widget-property-pks-attrs-names" title="Attribute name(s) that correspondent to the table Primary Key or unique identifier of each record.">'
			 + '	<label>Primary keys:</label>'
			 + '	<i class="zmdi zmdi-plus-square widget-item-add" title="Add Primary key attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addWidgetAttrName(this, event)"></i>'
			 + '	<select class="ignore">'
			 + '		<option value="">-- User Defined --</option>'
			 + '	</select>'
			 + '	<ul prefix="widget_properties[pks_attrs_names]">'
			 + '		<li class="empty-items">No pks defined...</li>'
			 + '	</ul>'
			 + '</li>'
			 
			 + '<li class="widget-property-viewable widget-property-checkbox with-sub-options">'
			 + '	<i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>'
			 + '	<label title="With Viewable Details">With Viewable Details: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[viewable][active]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetViewable(this, event)" title="Allow item to be viewed" checked />'
			 + '	<ul>'
			 + '		<li class="widget-property-viewable-type">'
			 + '			<label>View Type: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<select name="widget_properties[viewable][type]" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetViewableType(this, event)">'
			 + '				<option value="inline" title="Simply shows attribute">Inline</option>'
			 + '				<option value="link" title="Shows attribute in a link to open popup with details">Link</option>'
			 + '				<!--option value="button" title="Simply shows attribute and a button in the last column to open popup with details">Button</option-->'
			 + '			</select>'
			 + '		</li>'
			 + '		<li class="widget-property-viewable-with-view-button widget-property-checkbox" title="If active, a button will be added to open a popup to view the correspondent item.">'
			 + '			<label>With View Button in View Mode: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<input name="widget_properties[viewable][with_view_button]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetViewableWithViewButton(this, event)" />'
			 + '		</li>'
			 + '		<li class="widget-property-action-permissions">'
			 + '			<label>Permissions: </label>'
			 + 			getWidgetPermissionSettingsHtml("show", "widget_properties[viewable][permissions][show]")
			 + 			getWidgetPermissionSettingsHtml("hide", "widget_properties[viewable][permissions][hide]")
			 + 			getWidgetPermissionSettingsHtml("remove", "widget_properties[viewable][permissions][remove]")
			 + '		</li>'
			 + '	</ul>'
			 + '</li>'
			 + '<li class="widget-property-addable widget-property-checkbox with-sub-options">'
			 + '	<i class="zmdi zmdi-caret-right toggle" title="Toggle sub-Options"></i>'
			 + '	<label title="With Add Action">With Add Action: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[addable][active]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetAddable(this, event)" title="Allow item to be added" />'
			 + '	<ul>'
			 + '		<li class="widget-property-addable-type">'
			 + '			<label>Panel Type: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<select name="widget_properties[addable][type]" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetAddableType(this, event)">'
			 + '				<option value="" title="A popup will be open to add a new item">Popup</option>'
			 + '				<option value="inline" title="An inline item will be added to the list so the user can fill the fields.">Inline</option>'
			 + '			</select>'
			 + '		</li>'
			 + '		<li class="widget-property-action-permissions">'
			 + '			<label>Permissions: </label>'
			 + 			getWidgetPermissionSettingsHtml("show", "widget_properties[addable][permissions][show]")
			 + 			getWidgetPermissionSettingsHtml("hide", "widget_properties[addable][permissions][hide]")
			 + 			getWidgetPermissionSettingsHtml("remove", "widget_properties[addable][permissions][remove]")
			 + '		</li>'
			 + '	</ul>'
			 + '</li>'
			 + '<li class="widget-property-editable widget-property-checkbox with-sub-options">'
			 + '	<i class="zmdi zmdi-caret-right toggle" title="Toggle sub-Options"></i>'
			 + '	<label title="With Edit Action">With Edit Action: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[editable][active]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetEditable(this, event)" title="Allow item to be edit" />'
			 + '	<ul>'
			 + '		<li class="widget-property-editable-type">'
			 + '			<label>Edit Type: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<select name="widget_properties[editable][type]" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetEditableType(this, event)">'
			 + '				<option value="inline" title="Shows editable attribute">Inline</option>'
			 + '				<option value="link" title="Shows attribute in a link to open popup to edit details">Link</option>'
			 + '				<option value="button" title="Shows editable attribute and a button in the last column to open popup to edit details">Button</option>'
			 + '			</select>'
			 + '		</li>'
			 + '		<li class="widget-property-editable-with-auto-save widget-property-checkbox" title="If active, every change will be saved instantaneously.">'
			 + '			<label>With Auto Save: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<input name="widget_properties[editable][with_auto_save]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetEditableWithAutoSave(this, event)" />'
			 + '		</li>'
			 + '		<li class="widget-property-editable-with-save-button widget-property-checkbox" title="If active, a button will be added to save the item editable fields.">'
			 + '			<label>With Save Button: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<input name="widget_properties[editable][with_save_button]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetEditableWithSaveButton(this, event)" />'
			 + '		</li>'
			 + '		<li class="widget-property-editable-with-edit-button widget-property-checkbox" title="If active, a button will be added to open a popup to edit the correspondent item.">'
			 + '			<label>With Edit Button in View Mode: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<input name="widget_properties[editable][with_edit_button]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetEditableWithEditButton(this, event)" />'
			 + '		</li>'
			 + '		<li class="widget-property-toggle-edit-view-fields-button widget-property-checkbox" title="If active, a button will be added to toggle between the edit and readonly fields.">'
			 + '			<label>With Toggle Edit-View Fields Button: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<input name="widget_properties[editable][toggle_edit_view_fields_button]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetToggleEditViewFieldsButton(this, event)" />'
			 + '		</li>'
			 + '		<li class="widget-property-action-permissions">'
			 + '			<label>Permissions: </label>'
			 + 			getWidgetPermissionSettingsHtml("show", "widget_properties[editable][permissions][show]")
			 + 			getWidgetPermissionSettingsHtml("hide", "widget_properties[editable][permissions][hide]")
			 + 			getWidgetPermissionSettingsHtml("remove", "widget_properties[editable][permissions][remove]")
			 + '		</li>'
			 + '	</ul>'
			 + '</li>'
			 + '<li class="widget-property-removable widget-property-checkbox with-sub-options">'
			 + '	<i class="zmdi zmdi-caret-right toggle" title="Toggle sub-Options"></i>'
			 + '	<label title="With Remove Action">With Remove Action: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[removable][active]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetRemovable(this, event)" title="Add a button to remove the correspondent item" />'
			 + '	<ul>'
			 + '		<li class="widget-property-action-permissions">'
			 + '			<label>Permissions: </label>'
			 + 			getWidgetPermissionSettingsHtml("show", "widget_properties[removable][permissions][show]")
			 + 			getWidgetPermissionSettingsHtml("hide", "widget_properties[removable][permissions][hide]")
			 + 			getWidgetPermissionSettingsHtml("remove", "widget_properties[removable][permissions][remove]")
			 + '		</li>'
			 + '	</ul>'
			 + '</li>';
	}
	
	function getSelectedWidgetListPropertiesSettingsHtml(widget) {
		//empty_message (input): "There are no items available" (only show this property if selector item: "[data-widget-empty]" does not exist)
		//loading_message (input): "Loading items..." (only show this property if selector item: "[data-widget-loading]" does not exist)
		
		return '<li class="widget-property-shown-attrs-names" title="Choose the attributes to show">'
			 + '	<label>Attributes to Show: <span class="mandatory">*</span> </label>'
			 + '	<i class="zmdi zmdi-plus-square widget-item-add" title="Add attribute to show" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addWidgetAttrName(this, event)"></i>'
			 + '	<select class="ignore">'
			 + '		<option value="">-- User Defined --</option>'
			 + '	</select>'
			 + '	<ul prefix="widget_properties[shown_attrs_names]">'
			 + '		<li class="empty-items">No attributes defined...</li>'
			 + '	</ul>'
			 + '</li>'
			 + '<li class="widget-property-pks-attrs-names" title="Attribute name(s) that correspondent to the table Primary Key or unique identifier of each record.">'
			 + '	<label>Primary keys:</label>'
			 + '	<i class="zmdi zmdi-plus-square widget-item-add" title="Add Primary key attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addWidgetAttrName(this, event)"></i>'
			 + '	<select class="ignore">'
			 + '		<option value="">-- User Defined --</option>'
			 + '	</select>'
			 + '	<ul prefix="widget_properties[pks_attrs_names]">'
			 + '		<li class="empty-items">No pks defined...</li>'
			 + '	</ul>'
			 + '</li>'
			 + '<li class="widget-property-default-attrs-values" title="Default values that will be passed by default to the search attributes and used in the load resource.">'
			 + '	<label>Default Values:</label>'
			 + '	<i class="zmdi zmdi-plus-square widget-item-add" title="Add attribute with default value" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addWidgetAttrNameAndValue(this, event)"></i>'
			 + '	<select class="ignore">'
			 + '		<option value="">-- User Defined --</option>'
			 + '	</select>'
			 + '	<ul prefix="widget_properties[default_attrs_values]">'
			 + '		<li class="empty-items">No default values defined...</li>'
			 + '	</ul>'
			 + '</li>'
			 
			 + '<li class="widget-property-empty-message" title="Empty message to show if no records available. Eg: There are no items available">'
			 + '	<label>Empty Message: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[empty_message]" placeHolder="There are no items available" />'
			 + '</li>'
			 + '<li class="widget-property-loading-message" title="Loading message while getting records. Eg: Loading items...">'
			 + '	<label>Loading Message: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[loading_message]" placeHolder="Loading items..." />'
			 + '</li>'
			 + '<li class="widget-property-items-limit-per-page" title="Records limit per page. Eg: 100">'
			 + '	<label>Items Limit Per Page: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[items_limit_per_page]" placeHolder="Records limit per page. Eg: 100" />'
			 + '</li>'
			 + '<li class="widget-property-starting-page-number" title="Page number to start showing the records">'
			 + '	<label>Starting Page Number: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[starting_page_number]" placeHolder="0" />'
			 + '</li>'
			 + '<li class="widget-property-check-callback with-extra-callbacks" title="Callback to check the fields before executing an action. This function must return true/false and receives the arguments: elm, widget, error fields, error message">'
			 + '	<label>On Check Callback: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[check][]" placeHolder="Blank for default" />'
			 + '	<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
			 + '	<i class="zmdi zmdi-plus-square add-extra-callback" title="Add new callback" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addExtraCallback(this, event)"></i>'
			 + '	<ul class="extra-callbacks"></ul>'
			 + '</li>'
			 + '<li class="widget-property-list-type">'
			 + '	<label>List Type: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<select name="widget_properties[list_type]" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetListType(this, event, true)">'
			 + '		<option value="">-- None --</option>'
			 + '		<option value="table" title="Table layout">Table</option>'
			 + '		<option value="tree" title="Tree layout based in ul/li">Tree</option>'
			 + '		<option value="both" title="Table and tree layout">Table and Tree</option>'
			 + '	</select>'
			 + '</li>'
			 
			 + '<li class="widget-property-viewable widget-property-checkbox with-sub-options">'
			 + '	<i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>'
			 + '	<label title="With Viewable Details">With Viewable Details: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[viewable][active]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetViewable(this, event)" title="Allow item to be viewed" checked />'
			 + '	<ul>'
			 + '		<li class="widget-property-viewable-type">'
			 + '			<label>View Type: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<select name="widget_properties[viewable][type]" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetViewableType(this, event)">'
			 + '				<option value="inline" title="Simply shows attribute">Inline</option>'
			 + '				<option value="link" title="Shows attribute in a link to open popup with details">Link</option>'
			 + '				<!--option value="button" title="Simply shows attribute and a button in the last column to open popup with details">Button</option-->'
			 + '			</select>'
			 + '		</li>'
			 + '		<li class="widget-property-viewable-with-view-button widget-property-checkbox" title="If active, a button will be added to open a popup to view the correspondent item.">'
			 + '			<label>With View Button in View Mode: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<input name="widget_properties[viewable][with_view_button]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetViewableWithViewButton(this, event)" />'
			 + '		</li>'
			 + '		<li class="widget-property-action-permissions">'
			 + '			<label>Permissions: </label>'
			 + 			getWidgetPermissionSettingsHtml("show", "widget_properties[viewable][permissions][show]")
			 + 			getWidgetPermissionSettingsHtml("hide", "widget_properties[viewable][permissions][hide]")
			 + 			getWidgetPermissionSettingsHtml("remove", "widget_properties[viewable][permissions][remove]")
			 + '		</li>'
			 + '	</ul>'
			 + '</li>'
			 + '<li class="widget-property-addable widget-property-checkbox with-sub-options">'
			 + '	<i class="zmdi zmdi-caret-right toggle" title="Toggle sub-Options"></i>'
			 + '	<label title="With Add Action">With Add Action: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[addable][active]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetAddable(this, event)" title="Allow item to be added" />'
			 + '	<ul>'
			 + '		<li class="widget-property-addable-type">'
			 + '			<label>Panel Type: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<select name="widget_properties[addable][type]" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetAddableType(this, event)">'
			 + '				<option value="" title="A popup will be open to add a new item">Popup</option>'
			 + '				<option value="inline" title="An inline item will be added to the list so the user can fill the fields.">Inline</option>'
			 + '			</select>'
			 + '		</li>'
			 + '		<li class="widget-property-action-permissions">'
			 + '			<label>Permissions: </label>'
			 + 			getWidgetPermissionSettingsHtml("show", "widget_properties[addable][permissions][show]")
			 + 			getWidgetPermissionSettingsHtml("hide", "widget_properties[addable][permissions][hide]")
			 + 			getWidgetPermissionSettingsHtml("remove", "widget_properties[addable][permissions][remove]")
			 + '		</li>'
			 + '	</ul>'
			 + '</li>'
			 + '<li class="widget-property-editable widget-property-checkbox with-sub-options">'
			 + '	<i class="zmdi zmdi-caret-right toggle" title="Toggle sub-Options"></i>'
			 + '	<label title="With Edit Action">With Edit Action: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[editable][active]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetEditable(this, event)" title="Allow item to be edit" />'
			 + '	<ul>'
			 + '		<li class="widget-property-editable-type">'
			 + '			<label>Edit Type: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<select name="widget_properties[editable][type]" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetEditableType(this, event)">'
			 + '				<option value="inline" title="Shows editable attribute">Inline</option>'
			 + '				<option value="link" title="Shows attribute in a link to open popup to edit details">Link</option>'
			 + '				<option value="button" title="Shows editable attribute and a button in the last column to open popup to edit details">Button</option>'
			 + '			</select>'
			 + '		</li>'
			 + '		<li class="widget-property-editable-with-auto-save widget-property-checkbox" title="If active, every change will be saved instantaneously.">'
			 + '			<label>With Auto Save: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<input name="widget_properties[editable][with_auto_save]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetEditableWithAutoSave(this, event)" />'
			 + '		</li>'
			 + '		<li class="widget-property-editable-with-save-button widget-property-checkbox" title="If active, a button will be added to save the item editable fields.">'
			 + '			<label>With Save Button: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<input name="widget_properties[editable][with_save_button]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetEditableWithSaveButton(this, event)" />'
			 + '		</li>'
			 + '		<li class="widget-property-editable-with-edit-button widget-property-checkbox" title="If active, a button will be added to open a popup to edit the correspondent item.">'
			 + '			<label>With Edit Button: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<input name="widget_properties[editable][with_edit_button]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetEditableWithEditButton(this, event)" />'
			 + '		</li>'
			 + '		<li class="widget-property-toggle-edit-view-fields-button widget-property-checkbox" title="If active, a button will be added to toggle between the edit and readonly fields.">'
			 + '			<label>With Toggle Edit-View Fields Button: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<input name="widget_properties[editable][toggle_edit_view_fields_button]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetToggleEditViewFieldsButton(this, event)" />'
			 + '		</li>'
			 + '		<li class="widget-property-action-permissions">'
			 + '			<label>Permissions: </label>'
			 + 			getWidgetPermissionSettingsHtml("show", "widget_properties[editable][permissions][show]")
			 + 			getWidgetPermissionSettingsHtml("hide", "widget_properties[editable][permissions][hide]")
			 + 			getWidgetPermissionSettingsHtml("remove", "widget_properties[editable][permissions][remove]")
			 + '		</li>'
			 + '	</ul>'
			 + '</li>'
			 + '<li class="widget-property-removable widget-property-checkbox with-sub-options">'
			 + '	<i class="zmdi zmdi-caret-right toggle" title="Toggle sub-Options"></i>'
			 + '	<label title="With Remove Action">With Remove Action: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[removable][active]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetRemovable(this, event)" title="Add a button to remove the correspondent item" />'
			 + '	<ul>'
			 + '		<li class="widget-property-action-permissions">'
			 + '			<label>Permissions: </label>'
			 + 			getWidgetPermissionSettingsHtml("show", "widget_properties[removable][permissions][show]")
			 + 			getWidgetPermissionSettingsHtml("hide", "widget_properties[removable][permissions][hide]")
			 + 			getWidgetPermissionSettingsHtml("remove", "widget_properties[removable][permissions][remove]")
			 + '		</li>'
			 + '	</ul>'
			 + '</li>'
			 + '<li class="widget-property-multiple-items-actions widget-property-multiple-list-items-actions widget-property-checkbox" title="Allow selection by item, so the user can execute an action in multiple items">'
			 + '	<label>Allow Multiple Items Actions: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[multiple_items_actions]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetListMultipleItemsActions(this, event)" />'
			 + '</li>';
	}
	
	function getSelectedWidgetFormPropertiesSettingsHtml(widget) {
		return '<li class="widget-property-shown-attrs-names" title="Choose the attributes to show">'
			 + '	<label>Attributes to Show: <span class="mandatory">*</span> </label>'
			 + '	<i class="zmdi zmdi-plus-square widget-item-add" title="Add attribute to show" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addWidgetAttrName(this, event)"></i>'
			 + '	<select class="ignore">'
			 + '		<option value="">-- User Defined --</option>'
			 + '	</select>'
			 + '	<ul prefix="widget_properties[shown_attrs_names]">'
			 + '		<li class="empty-items">No attributes defined...</li>'
			 + '	</ul>'
			 + '</li>'
			 + '<li class="widget-property-pks-attrs-names" title="Attribute name(s) that correspondent to the table Primary Key or unique identifier of each record.">'
			 + '	<label>Primary keys:</label>'
			 + '	<i class="zmdi zmdi-plus-square widget-item-add" title="Add Primary key attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addWidgetAttrName(this, event)"></i>'
			 + '	<select class="ignore">'
			 + '		<option value="">-- User Defined --</option>'
			 + '	</select>'
			 + '	<ul prefix="widget_properties[pks_attrs_names]">'
			 + '		<li class="empty-items">No pks defined...</li>'
			 + '	</ul>'
			 + '</li>'
			 + '<li class="widget-property-default-attrs-values" title="Default values that will be passed by default to the search attributes and used in the load resource.">'
			 + '	<label>Default Values:</label>'
			 + '	<i class="zmdi zmdi-plus-square widget-item-add" title="Add attribute with default value" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addWidgetAttrNameAndValue(this, event)"></i>'
			 + '	<select class="ignore">'
			 + '		<option value="">-- User Defined --</option>'
			 + '	</select>'
			 + '	<ul prefix="widget_properties[default_attrs_values]">'
			 + '		<li class="empty-items">No default values defined...</li>'
			 + '	</ul>'
			 + '</li>'
			 + '<li class="widget-property-check-callback" title="Callback to check the fields before executing an action. This function must return true/false and receives the arguments: elm, widget, error fields, error message">'
			 + '	<label>On Check Callback: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[check]" placeHolder="Blank for default" />'
			 + '</li>'
			 + '<li class="widget-property-enter-key-press-callback" title="Callback when ENTER key is pressed. This function receives the arguments: elm">'
			 + '	<label>On Enter Key Press Callback: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[enter_key_press]" placeHolder="Blank for default" />'
			 + '</li>'
			 + '<li class="widget-property-on-enter-key-press-button" title="Leave this field with white space to disabled the ENTER key or write a selector, id or class name of a button to trigger. Note that the button must be inside of the form.">'
			 + '	<label>On Enter Key Press Button: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[enter_key_press_button]" placeHolder="White space or a selector, id or class" />'
			 + '</li>'
			 
			 + '<li class="widget-property-viewable widget-property-checkbox with-sub-options">'
			 + '	<i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>'
			 + '	<label title="With Viewable Details">With Viewable Details: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[viewable][active]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetViewable(this, event)" title="Allow item to be viewed" checked />'
			 + '	<ul>'
			 + '		<li class="widget-property-action-permissions">'
			 + '			<label>Permissions: </label>'
			 + 			getWidgetPermissionSettingsHtml("show", "widget_properties[viewable][permissions][show]")
			 + 			getWidgetPermissionSettingsHtml("hide", "widget_properties[viewable][permissions][hide]")
			 + 			getWidgetPermissionSettingsHtml("remove", "widget_properties[viewable][permissions][remove]")
			 + '		</li>'
			 + '	</ul>'
			 + '</li>'
			 + '<li class="widget-property-addable widget-property-checkbox with-sub-options">'
			 + '	<i class="zmdi zmdi-caret-right toggle" title="Toggle sub-Options"></i>'
			 + '	<label title="With Add Action">With Add Action: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[addable][active]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetAddable(this, event)" title="Allow item to be added" />'
			 + '	<ul>'
			 + '		<li class="widget-property-action-permissions">'
			 + '			<label>Permissions: </label>'
			 + 			getWidgetPermissionSettingsHtml("show", "widget_properties[addable][permissions][show]")
			 + 			getWidgetPermissionSettingsHtml("hide", "widget_properties[addable][permissions][hide]")
			 + 			getWidgetPermissionSettingsHtml("remove", "widget_properties[addable][permissions][remove]")
			 + '		</li>'
			 + '	</ul>'
			 + '</li>'
			 + '<li class="widget-property-editable widget-property-checkbox with-sub-options">'
			 + '	<i class="zmdi zmdi-caret-right toggle" title="Toggle sub-Options"></i>'
			 + '	<label title="With Edit Action">With Edit Action: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[editable][active]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetEditable(this, event)" title="Allow item to be edit" />'
			 + '	<ul>'
			 + '		<li class="widget-property-action-permissions">'
			 + '			<label>Permissions: </label>'
			 + 			getWidgetPermissionSettingsHtml("show", "widget_properties[editable][permissions][show]")
			 + 			getWidgetPermissionSettingsHtml("hide", "widget_properties[editable][permissions][hide]")
			 + 			getWidgetPermissionSettingsHtml("remove", "widget_properties[editable][permissions][remove]")
			 + '		</li>'
			 + '	</ul>'
			 + '</li>'
			 + '<li class="widget-property-removable widget-property-checkbox with-sub-options">'
			 + '	<i class="zmdi zmdi-caret-right toggle" title="Toggle sub-Options"></i>'
			 + '	<label title="With Remove Action">With Remove Action: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[removable][active]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetRemovable(this, event)" title="Add a button to remove the correspondent item" />'
			 + '	<ul>'
			 + '		<li class="widget-property-action-permissions">'
			 + '			<label>Permissions: </label>'
			 + 			getWidgetPermissionSettingsHtml("show", "widget_properties[removable][permissions][show]")
			 + 			getWidgetPermissionSettingsHtml("hide", "widget_properties[removable][permissions][hide]")
			 + 			getWidgetPermissionSettingsHtml("remove", "widget_properties[removable][permissions][remove]")
			 + '		</li>'
			 + '	</ul>'
			 + '</li>';
	}
	
	function getSelectedWidgetListCaptionPropertiesSettingsHtml(widget) {
		return '<li class="widget-property-draw-callback with-extra-callbacks" title="Callback to draw the list caption information. This function receives the arguments: elm, list widget, list settings">'
			 + '	<label>On Draw Info Callback: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[draw][]" placeHolder="Blank for default" />'
			 + '	<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
			 + '	<i class="zmdi zmdi-plus-square add-extra-callback" title="Add new callback" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addExtraCallback(this, event)"></i>'
			 + '	<ul class="extra-callbacks"></ul>'
			 + '</li>';
	}
	
	function getSelectedWidgetSearchPropertiesSettingsHtml(widget) {
		return '<li class="widget-property-search-with-input widget-property-checkbox" title="Add free text input to search">'
			 + '	<label>With Input Field: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[search_with_input]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetSearchWithInput(this, event)" />'
			 + '</li>'
			 + '<li class="widget-property-search-with-select widget-property-checkbox" title="Add dropdown field so the user can select a specific record to search">'
			 + '	<label>With Select Field: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[search_with_select]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetSearchWithSelect(this, event)" />'
			 + '</li>'
			 + '<li class="widget-property-search-with-user-choice widget-property-checkbox" title="Shows an UI so the user can choose what attributes he wishes to search...">'
			 + '	<label>With User Choice: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[search_with_user_choice]" type="checkbox" value="1" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetSearchWithUserChoice(this, event)" />'
			 + '</li>'
			 + getSelectedWidgetSearchInputPropertiesSettingsHtml(widget);
	}
	
	function getSelectedWidgetSearchInputPropertiesSettingsHtml(widget) {
		return '<li class="widget-property-search-attrs-names">'
			 + '	<label>Attributes to Search:</label>'
			 + '	<i class="zmdi zmdi-plus-square widget-item-add" title="Add attribute name" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addWidgetAttrName(this, event)"></i>'
			 + '	<select class="ignore">'
			 + '		<option value="">-- User Defined --</option>'
			 + '	</select>'
			 + '	<ul prefix="widget_properties[search_attrs_names]">'
			 + '		<li class="empty-items">No attributes defined...</li>'
			 + '	</ul>'
			 + '</li>'
			 + getSelectedWidgetSearchMultipleButtonPropertiesSettingsHtml(widget);
	}
	
	function getSelectedWidgetSearchSelectPropertiesSettingsHtml(widget) {
		return getSelectedWidgetSearchInputPropertiesSettingsHtml(widget);
	}
	
	function getSelectedWidgetSearchMultipleButtonPropertiesSettingsHtml(widget) {
		return '<li class="widget-property-search-type" title="The type of the search to be executed">'
			 + '	<label>Search Type: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<select name="widget_properties[search_type]" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetSearchType(this, event)">'
			 + '		<option value="">Contains</option>'
			 + '		<option value="starts_with">Starts With</option>'
			 + '		<option value="ends_with">Ends With</option>'
			 + '		<option value="equal">Equal</option>'
			 + '	</select>'
			 + '</li>'
			 + '<li class="widget-property-search-case" title="if search should be done case sensitive or insensitive">'
			 + '	<label>Search Case: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<select name="widget_properties[search_case]" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetSearchCase(this, event)">'
			 + '		<option value="">-- Default --</option>'
			 + '		<option value="sensitive">Sensitive</option>'
			 + '		<option value="insensitive">Insensitive</option>'
			 + '	</select>'
			 + '</li>'
			 + '<li class="widget-property-search-operator" title="The operator that combines multiple attributes for the search">'
			 + '	<label>Search Operator: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<select name="widget_properties[search_operator]" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetSearchOperator(this, event)">'
			 + '		<option value="">-- Default --</option>'
			 + '		<option value="or">Or</option>'
			 + '		<option value="and">And</option>'
			 + '	</select>'
			 + '</li>';
	}
	
	function getSelectedWidgetButtonMultipleRemovePropertiesSettingsHtml(widget) {
		return '<li class="widget-property-empty-message" title="Empty message to show if no selected elements. Eg: No selected elements...">'
			 + '	<label>Empty Message: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[empty_message]" placeHolder="No selected elements..." />'
			 + '</li>';
	}
	
	function getSelectedWidgetButtonMultipleSavePropertiesSettingsHtml(widget) {
		return getSelectedWidgetButtonMultipleRemovePropertiesSettingsHtml(widget);
	}
	
	function getSelectedWidgetPaginationPropertiesSettingsHtml(widget) {
		return '<li class="widget-property-number-of-pages-to-show-at-once" title="Number of pages to show at once. Eg: 10">'
			 + '	<label># of Pages to Show at once: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[number_of_pages_to_show_at_once]" placeHolder="10" />'
			 + '</li>';
	}
	
	function getSelectedWidgetItemAttributeNamePropertiesSettingsHtml(widget) {
		return '<li class="widget-property-item-attribute-name" title="The attribute name">'
			 + '	<label>Attribute Name: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[attribute_name]" />'
			 + '</li>';
	}
	
	function getSelectedWidgetButtonSettingsHtml(widget) {
		return '<li class="widget-property-button-on-click widget-swap-field with-extra-events select-shown" title="Javascript code to be executed on click">'
			 + '	<label>On Click: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[on_click][]" onKeyUp="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onKeyUpWidgetClickEvent(this, event)" onBlur="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onBlurWidgetClickEvent(this, event)" />'
			 + '	<select name="widget_properties[on_click][]" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetClickEvent(this, event)">'
			 + '		<option value="" selected>-- None --</option>'
			 + '		<option value="MyWidgetResourceLib.PopupHandler.openButtonAddPopup(this)" title="openButtonAddPopup">openButtonAddPopup</option>'
			 + '		<option value="MyWidgetResourceLib.ItemHandler.openItemEditPopupById(this)" title="openItemEditPopupById">openItemEditPopupById</option>'
			 + '		<option value="MyWidgetResourceLib.ItemHandler.openItemViewPopupById(this)" title="openItemViewPopupById">openItemViewPopupById</option>'
			 + '		<option value="MyWidgetResourceLib.ItemHandler.openItemDependentWidgets(this)" title="openItemDependentWidgets">openItemDependentWidgets</option>'
			 + '		<option value="MyWidgetResourceLib.PopupHandler.openPopup(this)" title="openPopup">openPopup</option>'
			 + '		<option value="MyWidgetResourceLib.PopupHandler.closePopup(this)" title="closePopup">closePopup</option>'
			 + '		<option value="MyWidgetResourceLib.PopupHandler.closeParentPopup(this)" title="closeParentPopup. Note that the \'Popup Id\' setting will not be used by this function">closeParentPopup</option>'
			 + '	</select>'
			 + '	<i class="zmdi zmdi-swap swap-input-select" title="Swap between pre-defined options or open text option" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.swapInputAndSelectFieldsOnClickEvent(this, event)"></i>'
			 + '	<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined events" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetEventPopup(this, event)"></i>'
			 + '	<i class="zmdi zmdi-plus-square add-extra-event" title="Add new event" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addExtraEvent(this, event)"></i>'
			 + '	<ul class="extra-events"></ul>'
			 + '</li>'
			 + '<li class="widget-property-button-popup-id widget-swap-field select-shown" title="Existent popup id to be called by the onclick event. Unless the \'On Click\' setting is \'closeParentPopup\', you must fill this setting with the correspondent popup id.">'
			 + '	<label>Popup Id: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[popup_id]" />'
			 + '	<select name="widget_properties[popup_id]" class="select-existent-widget-popups">'
			 + '		<option value="" selected>-- None --</option>'
			 + '	</select>'
			 + '	<i class="zmdi zmdi-swap swap-input-select" title="Swap between pre-defined options or open text option" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.swapInputAndSelectFields(this, event)"></i>'
			 + '	<i class="zmdi zmdi-refresh refresh-existent-data" title="Refresh existent popup ids in dropdown box" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.refreshWidgetExistentPopupIds(this, event)"></i>'
			 + '</li>';
	}
	
	function getSelectedWidgetFieldSelectLoadResourcePropertiesSettingsHtml(widget) {
		return '<li class="widget-property-field-select-load-resource-type" title="Load Resource Type">'
			 + '	<label>Load Replacement Type: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<select name="widget_properties[load_resource_type]">'
			 	+ '<option value="">Replace all options</option>'
			 	+ '<option value="append">Append to previous options</option>'
			 	+ '<option value="prepend">Prepend to previous options</option>'
			 + '	</select>'
			 + '</li>';
	}
	
	function getSelectedWidgetHtmlNodePropertiesSettingsHtml(widget) {
		return '<li class="widget-property-filter-resource-html-selector" title="Filter Resource Html Selector: Selector for the node in the resource result that the system should get to be used as replacement. This works in combination with the onParse event of the load resource.">'
			 + '	<label>Filter Resource Html Selector: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[filter_resource_html_selector]" />'
			 + '</li>';
	}
	
	function getSelectedWidgetGraphPropertiesSettingsHtml(widget) {
		return '<li class="widget-group-info widget-property-graph-info">For more information or options about "Drawing a Graph" and how it works, please open the "<a href="https://www.chartjs.org/" target="chartjs">https://www.chartjs.org/</a>" web-page.</li>'
			 + '<li class="widget-property-graph-load-type" title="Define when will the chart be drawn">'
				 + '<label>Load Chart: </label>'
				 + '<select onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.syncWidgetLoadResourceLoadType(this, event)">'
					 + '<option value="" selected>-- None --</option>'
					 + '<option value="data-widget-resources-load" title="Create chart when page loads">On page load</option>'
					 + '<option value="data-widget-item-resources-load" title="Create chart when parent draws me">When parent draws chart</option>'
				 + '</select>'
			 + '</li>'
			 + '<li class="group widget-property-graph-legend">'
				 + '<div class="group-title" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onClickWidgetGroupTitleWithDisplay(this, event)"><i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>With Legend: <input name="widget_properties[graph][legend][display]" type="checkbox" value="1" title="Check if you wish to display the legend" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onClickWidgetGroupTitleDisplay(this, event)" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetGroupTitleDisplay(this, event)" /></div>'
				 + '<ul>'
					 + '<li class="widget-property-graph-legend-position">'
						 + '<label>Position: </label>'
						 + '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						 + '<select name="widget_properties[graph][legend][position]">'
							 + '<option value="">-- Default --</option>'
							 + '<option value="top">Top</option>'
							 + '<option value="left">Left</option>'
							 + '<option value="right">Right</option>'
							 + '<option value="bottom">Bottom</option>'
							 + '<option value="chartArea">Chart Area</option>'
						 + '</select>'
					 + '</li>'
					 + '<li class="widget-property-graph-legend-align">'
						 + '<label>Alignment: </label>'
						 + '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						 + '<select name="widget_properties[graph][legend][align]">'
							 + '<option value="">-- Default --</option>'
							 + '<option value="start">Start</option>'
							 + '<option value="center">Center</option>'
							 + '<option value="end">End</option>'
						 + '</select>'
					 + '</li>'
					 + '<li class="widget-property-graph-legend-text-align">'
						 + '<label>Text Alignment: </label>'
						 + '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						 + '<select name="widget_properties[graph][legend][text_align]">'
							 + '<option value="">-- Default --</option>'
							 + '<option value="left">Left</option>'
							 + '<option value="center">Center</option>'
							 + '<option value="right">Right</option>'
						 + '</select>'
					 + '</li>'
				 + '</ul>'
			 + '</li>'
			 + '<li class="group widget-property-graph-title">'
				 + '<div class="group-title" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onClickWidgetGroupTitleWithDisplay(this, event)"><i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>With Title: <input name="widget_properties[graph][title][display]" type="checkbox" value="1" title="Check if you wish to display the title" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onClickWidgetGroupTitleDisplay(this, event)" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetGroupTitleDisplay(this, event)" /></div>'
				 + '<ul>'
					 + '<li class="widget-property-graph-title-text">'
						 + '<label>Text: </label>'
						 + '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						 + '<input name="widget_properties[graph][title][text]" />'
					 + '</li>'
					 + '<li class="widget-property-graph-title-position">'
						 + '<label>Position: </label>'
						 + '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						 + '<select name="widget_properties[graph][title][position]">'
							 + '<option value="">-- Default --</option>'
							 + '<option value="top">Top</option>'
							 + '<option value="left">Left</option>'
							 + '<option value="right">Right</option>'
							 + '<option value="bottom">Bottom</option>'
							 + '<option value="chartArea">Chart Area</option>'
						 + '</select>'
					 + '</li>'
					 + '<li class="widget-property-graph-title-align">'
						 + '<label>Alignment: </label>'
						 + '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						 + '<select name="widget_properties[graph][title][align]">'
							 + '<option value="">-- Default --</option>'
							 + '<option value="start">Start</option>'
							 + '<option value="center">Center</option>'
							 + '<option value="end">End</option>'
						 + '</select>'
					 + '</li>'
					 + '<li class="widget-property-graph-title-color color-style">'
						 + '<label>Color: </label>'
						 + '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						 + '<input class="color-selector" type="color">'
						 + '<input class="color-code ignore" name="widget_properties[graph][title][color]">'
					 + '</li>'
					 + '<li class="widget-property-graph-title-padding">'
						 + '<label>Padding: </label>'
						 + '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						 + '<input name="widget_properties[graph][title][padding]" type="number" />'
					 + '</li>'
				 + '</ul>'
			 + '</li>'
			 + '<li class="group widget-property-graph-sub-title">'
				 + '<div class="group-title" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onClickWidgetGroupTitleWithDisplay(this, event)"><i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>With Sub Title: <input name="widget_properties[graph][sub_title][display]" type="checkbox" value="1" title="Check if you wish to display the sub title" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onClickWidgetGroupTitleDisplay(this, event)" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetGroupTitleDisplay(this, event)" /></div>'
				 + '<ul>'
					 + '<li class="widget-property-graph-sub-title-text">'
						 + '<label>Text: </label>'
						 + '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						 + '<input name="widget_properties[graph][sub_title][text]" />'
					 + '</li>'
					 + '<li class="widget-property-graph-sub-title-position">'
						 + '<label>Position: </label>'
						 + '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						 + '<select name="widget_properties[graph][sub_title][position]">'
							 + '<option value="">-- Default --</option>'
							 + '<option value="top">Top</option>'
							 + '<option value="left">Left</option>'
							 + '<option value="right">Right</option>'
							 + '<option value="bottom">Bottom</option>'
							 + '<option value="chartArea">Chart Area</option>'
						 + '</select>'
					 + '</li>'
					 + '<li class="widget-property-graph-sub-title-align">'
						 + '<label>Alignment: </label>'
						 + '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						 + '<select name="widget_properties[graph][sub_title][align]">'
							 + '<option value="">-- Default --</option>'
							 + '<option value="start">Start</option>'
							 + '<option value="center">Center</option>'
							 + '<option value="end">End</option>'
						 + '</select>'
					 + '</li>'
					 + '<li class="widget-property-graph-sub-title-color color-style">'
						 + '<label>Color: </label>'
						 + '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						 + '<input class="color-selector" type="color">'
						 + '<input class="color-code ignore" name="widget_properties[graph][sub_title][color]">'
					 + '</li>'
					 + '<li class="widget-property-graph-sub-title-padding">'
						 + '<label>Padding: </label>'
						 + '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						 + '<input name="widget_properties[graph][sub_title][padding]" type="number" />'
					 + '</li>'
				 + '</ul>'
			 + '</li>'
			 + '<li class="group widget-property-graph-callbacks">'
				 + '<div class="group-title"><i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>Callbacks:</div>'
				 + '<ul>'
					 + '<li class="widget-property-graph-callbacks-parse with-extra-callbacks" title="Define a parse callback function for the chart main settings before the chart gets created. This function returns data_set_obj and receives the arguments: elm, chart_settings">'
						 + '<label>Parse Callback: </label>'
						 + '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						 + '<input name="widget_properties[graph][callbacks][parse][]" placeHolder="function name" />'
					 	 + '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
						 + '<i class="zmdi zmdi-plus-square add-extra-callback" title="Add new callback" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addExtraCallback(this, event)"></i>'
						 + '<ul class="extra-callbacks"></ul>'
					 + '</li>'
					 + '<li class="widget-property-graph-callbacks-click with-extra-callbacks" title="Define a on-click callback function for the chart. This function receives the arguments: elm, chart_settings, event, a, b">'
						 + '<label>Click Callback: </label>'
						 + '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						 + '<input name="widget_properties[graph][callbacks][click][]" placeHolder="function name" />'
					 	 + '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
						 + '<i class="zmdi zmdi-plus-square add-extra-callback" title="Add new callback" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addExtraCallback(this, event)"></i>'
						 + '<ul class="extra-callbacks"></ul>'
					 + '</li>'
					 + '<li class="widget-property-graph-callbacks-hover with-extra-callbacks" title="Define a on-hover callback function for the chart. This function receives the arguments: elm, chart_settings, event, item">'
						 + '<label>Hover Callback: </label>'
						 + '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						 + '<input name="widget_properties[graph][callbacks][hover][]" placeHolder="function name" />'
					 	 + '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
						 + '<i class="zmdi zmdi-plus-square add-extra-callback" title="Add new callback" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addExtraCallback(this, event)"></i>'
						 + '<ul class="extra-callbacks"></ul>'
					 + '</li>'
				 + '</ul>'
			 + '</li>'
			 + '<li class="group group-open widget-property-graph-data-sets">'
				 + '<div class="group-title"><i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>Data-Sets: <span class="zmdi zmdi-hc-lg zmdi-info-outline info" title="Note that, although you can have mixed chart defined, there are some that don\'t work propertly together. For mixed charts, please only use the Line, Vertical-Bar and Scatter charts. For more information, please go to www.chartjs.org" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleWidgetGraphDataSetInfo(this, event)"></span><span class="zmdi zmdi-plus-square widget-group-item-add" title="Add new data-set" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addWidgetGraphDataSet(this, event)"></span></div>'
				 + '<div class="widget-group-info">Note that, although you can have mixed chart defined, there are some that don\'t work propertly together.<br/>For mixed charts, please only use the Line, Vertical-Bar and Scatter charts.<br/>For more information, please go to <a href="https://www.chartjs.org/" target="chartjs">www.chartjs.org</a></div>'
				 + '<ul prefix="widget_properties[graph][data_sets]" style="display:block;">'
					 + '<li class="empty-items">No data-sets defined yet</li>'
				 + '</ul>'
			 + '</li>'
			+ '<li class="group widget-property-graph-advanced">'
				+ '<div class="group-title"><i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>Advanced:</div>'
				+ '<ul>'
					+ '<li class="widget-property-graph-include-lib" title="Include javascript lib automatically if not yet included">'
						+ '<label>Include Javascript Lib: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[graph][include_lib]" type="checkbox" value="1" />'
					+ '</li>'
				 + '</ul>'
			 + '</li>';
	}
	
	function getSelectedWidgetCalendarPropertiesSettingsHtml(widget) {
		return '<li class="widget-group-info widget-property-calendar-info">For more information or options about "Drawing a Calendar" and how it works, please open the "<a href="https://fullcalendar.io/" target="fullcalendar">https://fullcalendar.io/</a>" web-page.</div>'
			+ '<li class="widget-property-calendar-load-type" title="Define when will the calendar be drawn">'
				+ '<label>Load Calendar: </label>'
				+ '<select onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.syncWidgetLoadResourceLoadType(this, event)">'
					+ '<option value="" selected>-- None --</option>'
					+ '<option value="data-widget-resources-load" title="Create calendar when page loads">On page load</option>'
					+ '<option value="data-widget-item-resources-load" title="Create calendar when parent draws me">When parent draws calendar</option>'
				+ '</select>'
			+ '</li>'
			+ '<li class="group group-open widget-property-calendar-header-toolbar">'
				+ '<div class="group-title" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onClickWidgetGroupTitleWithDisplay(this, event)"><i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>With Header Toolbar: <input name="widget_properties[calendar][header_toolbar][display]" type="checkbox" value="1" title="Check if you wish to display the header toolbar" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onClickWidgetGroupTitleDisplay(this, event)" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetGroupTitleDisplay(this, event)" /></div>'
				+ '<ul style="display:block;">'
					+ '<li class="widget-property-calendar-header-toolbar-left">'
						+ '<label>Left Bar: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][header_toolbar][left]" placeHolder="prevYear,prev,next,nextYear" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-header-toolbar-center">'
						+ '<label>Center Bar: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][header_toolbar][center]" placeHolder="title" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-header-toolbar-right">'
						+ '<label>Right Bar: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][header_toolbar][right]" placeHolder="today refresh" />'
					+ '</li>'
				+ '</ul>'
			+ '</li>'
			+ '<li class="group group-open widget-property-calendar-footer-toolbar">'
				+ '<div class="group-title" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onClickWidgetGroupTitleWithDisplay(this, event)"><i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>With Footer Toolbar: <input name="widget_properties[calendar][footer_toolbar][display]" type="checkbox" value="1" title="Check if you wish to display the footer toolbar" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onClickWidgetGroupTitleDisplay(this, event)" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetGroupTitleDisplay(this, event)" /></div>'
				+ '<ul style="display:block;">'
					+ '<li class="widget-property-calendar-footer-toolbar-left">'
						+ '<label>Left Bar: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][footer_toolbar][left]" placeHolder="multiMonthYear dayGridMonth,dayGridWeek,dayGridDay" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-footer-toolbar-center">'
						+ '<label>Center Bar: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][footer_toolbar][center]" placeHolder="timeGridWeek,timeGridDay" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-footer-toolbar-right">'
						+ '<label>Right Bar: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][footer_toolbar][right]" placeHolder="listYear,listMonth,listWeek,listDay" />'
					+ '</li>'
				+ '</ul>'
			+ '</li>'
			+ '<li class="group widget-property-calendar-views">'
				+ '<div class="group-title"><i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>Buttons/Views Labels:</div>'
				+ '<ul>'
					+ '<li class="widget-property-calendar-views-multi-month-year-title">'
						+ '<label>Multi Month Year: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][views][multi_month_year][title]" placeHolder="grid year" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-views-day-grid-month-title">'
						+ '<label>Day Grid Month: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][views][day_grid_month][title]" placeHolder="grid month" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-views-day-grid-week-title">'
						+ '<label>Day Grid Week: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][views][day_grid_week][title]" placeHolder="grid week" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-views-day-grid-day-title">'
						+ '<label>Day Grid Day: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][views][day_grid_day][title]" placeHolder="grid day" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-views-time-grid-week-title">'
						+ '<label>Time Grid Week: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][views][time_grid_week][title]" placeHolder="time week" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-views-time-grid-day-title">'
						+ '<label>Time Grid Day: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][views][time_grid_day][title]" placeHolder="time day" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-views-list-year-title">'
						+ '<label>List Year: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][views][list_year][title]" placeHolder="list year" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-views-list-month-title">'
						+ '<label>List Month: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][views][list_month][title]" placeHolder="list month" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-views-list-week-title">'
						+ '<label>List Week: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][views][list_week][title]" placeHolder="list week" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-views-list-day-title">'
						+ '<label>List Day: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][views][list_day][title]" placeHolder="list day" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-views-resource-time-line-year-title">'
						+ '<label>Resource Time Line Year: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][views][resource_time_line_year][title]" placeHolder="resource time line year" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-views-resource-time-line-month-title">'
						+ '<label>Resource Time Line Month: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][views][resource_time_line_month][title]" placeHolder="resource time line month" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-views-resource-time-line-week-title">'
						+ '<label>Resource Time Line Week: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][views][resource_time_line_week][title]" placeHolder="resource time line week" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-views-resource-time-line-day-title">'
						+ '<label>Resource Time Line Day: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][views][resource_time_line_day][title]" placeHolder="resource time line day" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-views-resource-time-grid-week-title">'
						+ '<label>Resource Time Grid Week: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][views][resource_time_grid_week][title]" placeHolder="resource time grid week" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-views-resource-time-grid-day-title">'
						+ '<label>Resource Time Grid Day: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][views][resource_time_grid_day][title]" placeHolder="resource time grid day" />'
					+ '</li>'
				+ '</ul>'
			+ '</li>'
			+ '<li class="group widget-property-calendar-callbacks">'
				+ '<div class="group-title"><i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>Callbacks:</div>'
				+ '<ul>'
					+ '<li class="widget-property-calendar-callbacks-parse with-extra-callbacks" title="Define a parse callback function for the calendar main settings before the calendar gets created. This function returns calendar_settings and receives the arguments: elm, calendar_settings">'
						+ '<label>Parse Callback: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][callbacks][parse][]" placeHolder="function name" />'
					 	+ '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
						+ '<i class="zmdi zmdi-plus-square add-extra-callback" title="Add new callback" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addExtraCallback(this, event)"></i>'
						+ '<ul class="extra-callbacks"></ul>'
					+ '</li>'
					+ '<li class="widget-property-calendar-callbacks-select with-extra-callbacks widget-swap-field select-shown" title="Define a on-select callback function for the calendar events">'
						+ '<label>Select Cell Callback: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][callbacks][select][]" placeHolder="function name" />'
						+ '<select name="widget_properties[calendar][callbacks][select][]" title="Choose one of the callbacks">'
							+ '<option value=""></option>'
							+ '<option value="MyWidgetResourceLib.CalendarHandler.openCalendarEventAddPopupById" title="Open a popup with an editable form with empty values. The html element that will execute this function, must be the main calendar element, where the FullCalendar lib was initialized and must contain the correspondent popup id in the calendar events settings or [data-widget-popup-id] attribute.">openCalendarEventAddPopupById</option>'
							+ '<option value="MyWidgetResourceLib.CalendarHandler.addResourceCalendarEvent" title="User enters the event title and send request to server with the event dates, so a new record can be added. Note that this function should be called inside of a Calendar widget with a \'add\' resource defined.">addResourceCalendarEvent</option>'
							+ '<option value="MyWidgetResourceLib.CalendarHandler.addStaticCalendarEvent" title="Handler to be called on add callback setting of your calendar. In summary this handler will be called when the user clicks in a calendar cell amd prompts a box so he can write the new event title. Then the system adds a new static event in the calendar, without connecting with the server. This is done only in the client side. Note that this function should be only used with the Calendar widgets which also have the \'add\' resource defined.">addStaticCalendarEvent</option>'
						+ '</select>'
						+ '<i class="zmdi zmdi-swap swap-input-select" onClick="$(this).parent().toggleClass(\'select-shown\')" title="Swap between pre-defined options or open text option"></i>'
					 	+ '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
						+ '<i class="zmdi zmdi-plus-square add-extra-callback" title="Add new callback" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addExtraCallback(this, event)"></i>'
						+ '<ul class="extra-callbacks"></ul>'
					+ '</li>'
					+ '<li class="widget-property-calendar-callbacks-click with-extra-callbacks widget-swap-field select-shown" title="Define a on-click callback function for the calendar events. This function receives the arguments: elm, calendar_settings, arg">'
						+ '<label>Click Callback: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][callbacks][click][]" placeHolder="function name" />'
						+ '<select name="widget_properties[calendar][callbacks][click][]" title="Choose one of the callbacks">'
							+ '<option value=""></option>'
							+ '<option value="MyWidgetResourceLib.CalendarHandler.openCalendarEventEditPopupById" title="Get the event values and open a popup with an editable form with that values. The html element that will execute this function, must be the main calendar element, where the FullCalendar lib was initialized and must contain the correspondent popup id in the calendar event settings or [data-widget-popup-id] attribute.">openCalendarEventEditPopupById</option>'
							+ '<option value="MyWidgetResourceLib.CalendarHandler.openCalendarEventViewPopupById" title="Get the event values and open a popup with a readonly form with that values. The html element that will execute this function, must be the main calendar element, where the FullCalendar lib was initialized and must contain the correspondent popup id in the calendar event settings or [data-widget-popup-id] attribute.">openCalendarEventViewPopupById</option>'
							+ '<option value="MyWidgetResourceLib.CalendarHandler.removeStaticCalendarEvent" title="Handler to be called on \'remove\' callback setting of your calendar. In summary this handler will be called to remove an event. However the system removes the static event in the calendar, without connecting with the server. This is done only in the client side. Note that this function should be only used with the Calendar widgets which also have the \'remove\' resource defined.">removeStaticCalendarEvent</option>'
						+ '</select>'
						+ '<i class="zmdi zmdi-swap swap-input-select" onClick="$(this).parent().toggleClass(\'select-shown\')" title="Swap between pre-defined options or open text option"></i>'
					 	+ '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
						+ '<i class="zmdi zmdi-plus-square add-extra-callback" title="Add new callback" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addExtraCallback(this, event)"></i>'
						+ '<ul class="extra-callbacks"></ul>'
					+ '</li>'
					+ '<li class="widget-property-calendar-callbacks-resize with-extra-callbacks widget-swap-field select-shown" title="Define a on-resize callback function for the calendar events. This function receives the arguments: elm, calendar_settings, arg">'
						+ '<label>Resize Callback: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][callbacks][resize][]" placeHolder="function name" />'
						+ '<select name="widget_properties[calendar][callbacks][resize][]" title="Choose one of the callbacks">'
							+ '<option value=""></option>'
							+ '<option value="MyWidgetResourceLib.CalendarHandler.updateResourceCalendarEvent" title="Get values of the current event and send request to server to save them. Note that this function will execute the \'update\' resource defined in the calendar.">updateResourceCalendarEvent</option>'
						+ '</select>'
						+ '<i class="zmdi zmdi-swap swap-input-select" onClick="$(this).parent().toggleClass(\'select-shown\')" title="Swap between pre-defined options or open text option"></i>'
					 	+ '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
						+ '<i class="zmdi zmdi-plus-square add-extra-callback" title="Add new callback" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addExtraCallback(this, event)"></i>'
						+ '<ul class="extra-callbacks"></ul>'
					+ '</li>'
					+ '<li class="widget-property-calendar-callbacks-move with-extra-callbacks widget-swap-field select-shown" title="Define a on-move callback function for the calendar events. This function receives the arguments: elm, calendar_settings, arg">'
						+ '<label>Move Callback: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][callbacks][move][]" placeHolder="function name" />'
						+ '<select name="widget_properties[calendar][callbacks][move][]" title="Choose one of the callbacks">'
							+ '<option value=""></option>'
							+ '<option value="MyWidgetResourceLib.CalendarHandler.updateResourceCalendarEvent" title="Get values of the current event and send request to server to save them. Note that this function will execute the \'update\' resource defined in the calendar.">updateResourceCalendarEvent</option>'
						+ '</select>'
						+ '<i class="zmdi zmdi-swap swap-input-select" onClick="$(this).parent().toggleClass(\'select-shown\')" title="Swap between pre-defined options or open text option"></i>'
					 	+ '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
						+ '<i class="zmdi zmdi-plus-square add-extra-callback" title="Add new callback" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addExtraCallback(this, event)"></i>'
						+ '<ul class="extra-callbacks"></ul>'
					+ '</li>'
					+ '<li class="widget-property-calendar-callbacks-hover with-extra-callbacks widget-swap-field" title="Define a on-hover callback function for the calendar events. This function receives the arguments: elm, calendar_settings, arg">'
						+ '<label>Hover Callback: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][callbacks][hover][]" placeHolder="function name" />'
						+ '<select name="widget_properties[calendar][callbacks][hover][]" title="Choose one of the callbacks">'
							+ '<option value=""></option>'
						+ '</select>'
						+ '<i class="zmdi zmdi-swap swap-input-select" onClick="$(this).parent().toggleClass(\'select-shown\')" title="Swap between pre-defined options or open text option"></i>'
					 	+ '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
						+ '<i class="zmdi zmdi-plus-square add-extra-callback" title="Add new callback" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addExtraCallback(this, event)"></i>'
						+ '<ul class="extra-callbacks"></ul>'
					+ '</li>'
					+ '<li class="widget-property-calendar-callbacks-out with-extra-callbacks widget-swap-field" title="Define a on-out callback function for the calendar events. This function receives the arguments: elm, calendar_settings, arg">'
						+ '<label>Out Callback: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][callbacks][out][]" placeHolder="function name" />'
						+ '<select name="widget_properties[calendar][callbacks][out][]" title="Choose one of the callbacks">'
							+ '<option value=""></option>'
						+ '</select>'
						+ '<i class="zmdi zmdi-swap swap-input-select" onClick="$(this).parent().toggleClass(\'select-shown\')" title="Swap between pre-defined options or open text option"></i>'
					 	+ '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
						+ '<i class="zmdi zmdi-plus-square add-extra-callback" title="Add new callback" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addExtraCallback(this, event)"></i>'
						+ '<ul class="extra-callbacks"></ul>'
					+ '</li>'
					+ '<li class="widget-property-calendar-callbacks-add with-extra-callbacks widget-swap-field" title="Define a on-add callback function for the calendar events. This function receives the arguments: elm, calendar_settings, arg">'
						+ '<label>Add Callback: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][callbacks][add][]" placeHolder="function name" />'
						+ '<select name="widget_properties[calendar][callbacks][add][]" title="Choose one of the callbacks">'
							+ '<option value=""></option>'
						+ '</select>'
						+ '<i class="zmdi zmdi-swap swap-input-select" onClick="$(this).parent().toggleClass(\'select-shown\')" title="Swap between pre-defined options or open text option"></i>'
					 	+ '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
						+ '<i class="zmdi zmdi-plus-square add-extra-callback" title="Add new callback" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addExtraCallback(this, event)"></i>'
						+ '<ul class="extra-callbacks"></ul>'
					+ '</li>'
					+ '<li class="widget-property-calendar-callbacks-remove with-extra-callbacks widget-swap-field" title="Define a on-remove callback function for the calendar events. This function receives the arguments: elm, calendar_settings, arg">'
						+ '<label>Remove Callback: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][callbacks][remove][]" placeHolder="function name" />'
						+ '<select name="widget_properties[calendar][callbacks][remove][]" title="Choose one of the callbacks">'
							+ '<option value=""></option>'
						+ '</select>'
						+ '<i class="zmdi zmdi-swap swap-input-select" onClick="$(this).parent().toggleClass(\'select-shown\')" title="Swap between pre-defined options or open text option"></i>'
					 	+ '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
						+ '<i class="zmdi zmdi-plus-square add-extra-callback" title="Add new callback" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addExtraCallback(this, event)"></i>'
						+ '<ul class="extra-callbacks"></ul>'
					+ '</li>'
					+ '<li class="widget-property-calendar-callbacks-mount with-extra-callbacks widget-swap-field" title="Define a on-mount callback function for the calendar events. This function receives the arguments: elm, calendar_settings, arg">'
						+ '<label>Mount Callback: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][callbacks][mount][]" placeHolder="function name" />'
						+ '<select name="widget_properties[calendar][callbacks][mount][]" title="Choose one of the callbacks">'
							+ '<option value=""></option>'
						+ '</select>'
						+ '<i class="zmdi zmdi-swap swap-input-select" onClick="$(this).parent().toggleClass(\'select-shown\')" title="Swap between pre-defined options or open text option"></i>'
					 	+ '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
						+ '<i class="zmdi zmdi-plus-square add-extra-callback" title="Add new callback" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addExtraCallback(this, event)"></i>'
						+ '<ul class="extra-callbacks"></ul>'
					+ '</li>'
					+ '<li class="widget-property-calendar-callbacks-unmount with-extra-callbacks widget-swap-field select-shown" title="Define a on-unmount callback function for the calendar events. This function receives the arguments: elm, calendar_settings, arg">'
						+ '<label>Unmount Callback: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][callbacks][unmount][]" placeHolder="function name" />'
						+ '<select name="widget_properties[calendar][callbacks][unmount][]" title="Choose one of the callbacks">'
							+ '<option value=""></option>'
							+ '<!--option value="MyWidgetResourceLib.CalendarHandler.removeResourceCalendarEvent" title="Send request to server to remove current event. Note that this function should be called inside of a Calendar widget with a \'remove\' resource defined.">removeResourceCalendarEvent</option-->'
						+ '</select>'
						+ '<i class="zmdi zmdi-swap swap-input-select" onClick="$(this).parent().toggleClass(\'select-shown\')" title="Swap between pre-defined options or open text option"></i>'
					 	+ '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
						+ '<i class="zmdi zmdi-plus-square add-extra-callback" title="Add new callback" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addExtraCallback(this, event)"></i>'
						+ '<ul class="extra-callbacks"></ul>'
					+ '</li>'
					+ '<li class="widget-property-calendar-callbacks-window-resize with-extra-callbacks widget-swap-field" title="Define a on-window-resize callback function for the calendar. This function receives the arguments: elm, calendar_settings, arg">'
						+ '<label>Window Resize Callback: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][callbacks][window_resize][]" placeHolder="function name" />'
						+ '<select name="widget_properties[calendar][callbacks][window_resize][]" title="Choose one of the callbacks">'
							+ '<option value=""></option>'
						+ '</select>'
						+ '<i class="zmdi zmdi-swap swap-input-select" onClick="$(this).parent().toggleClass(\'select-shown\')" title="Swap between pre-defined options or open text option"></i>'
					 	+ '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
						+ '<i class="zmdi zmdi-plus-square add-extra-callback" title="Add new callback" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addExtraCallback(this, event)"></i>'
						+ '<ul class="extra-callbacks"></ul>'
					+ '</li>'
					+ '<li class="widget-property-calendar-callbacks-calendar-show-loading with-extra-callbacks widget-swap-field" title="Define a on-show-loading callback function for the calendar. This function receives the arguments: elm, calendar_settings, arg">'
						+ '<label>Show Loading Callback: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][callbacks][show_loading][]" placeHolder="function name" />'
						+ '<select name="widget_properties[calendar][callbacks][show_loading][]" title="Choose one of the callbacks">'
							+ '<option value=""></option>'
						+ '</select>'
						+ '<i class="zmdi zmdi-swap swap-input-select" onClick="$(this).parent().toggleClass(\'select-shown\')" title="Swap between pre-defined options or open text option"></i>'
					 	+ '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
						+ '<i class="zmdi zmdi-plus-square add-extra-callback" title="Add new callback" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addExtraCallback(this, event)"></i>'
						+ '<ul class="extra-callbacks"></ul>'
					+ '</li>'
					+ '<li class="widget-property-calendar-callbacks-calendar-hide-loading with-extra-callbacks widget-swap-field" title="Define a on-hide-loading callback function for the calendar. This function receives the arguments: elm, calendar_settings, arg">'
						+ '<label>Hide Loading Callback: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][callbacks][hide_loading][]" placeHolder="function name" />'
						+ '<select name="widget_properties[calendar][callbacks][hide_loading][]" title="Choose one of the callbacks">'
							+ '<option value=""></option>'
						+ '</select>'
						+ '<i class="zmdi zmdi-swap swap-input-select" onClick="$(this).parent().toggleClass(\'select-shown\')" title="Swap between pre-defined options or open text option"></i>'
					 	+ '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
						+ '<i class="zmdi zmdi-plus-square add-extra-callback" title="Add new callback" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addExtraCallback(this, event)"></i>'
						+ '<ul class="extra-callbacks"></ul>'
					+ '</li>'
				+ '</ul>'
			+ '</li>'
			+ '<li class="group group-open widget-property-calendar-data-sets">'
				+ '<div class="group-title"><i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>Data-Sets: <span class="zmdi zmdi-plus-square widget-group-item-add" title="Add new data-set" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addWidgetCalendarDataSet(this, event)"></span></div>'
				+ '<div class="widget-property-calendar-include-all-load-resources" title="Instead of adding the corresponding datasets to the defined load resources, check this setting to do it automatically.">'
					+ '<label>Include all load resources: </label>'
					+ '<input name="widget_properties[calendar][include_all_load_resources]" type="checkbox" value="1" />'
				+ '</div>'
				+ '<ul prefix="widget_properties[calendar][data_sets]" style="display:block;">'
					+ '<li class="empty-items">No data-sets defined yet</li>'
				+ '</ul>'
			+ '</li>'
			+ '<li class="group group-open widget-property-calendar-data-sets-settings">'
				+ '<div class="group-title"><i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>Settings for all Data-sets: <i class="zmdi zmdi-fullscreen toggle-advanced-options" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleWidgetAdvancedOptions(this, event)" title="Show/Hide advanced options"></i></div>'
				+ '<ul style="display:block;">'
					+ '<li class="widget-property-calendar-data-parse" title="Define a parse callback function for the data-set settings before it gets added to the calendar main settings. This function returns data and receives the arguments: elm, data">'
						+ '<label>Parse Callback: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][data_parse]" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-add-popup-id" title="Add Popup Id, if apply">'
						+ '<label>Add Event Popup Id: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][add_popup_id]" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-edit-popup-id" title="Edit Popup Id for each event, if apply">'
						+ '<label>Edit Event Popup Id: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][edit_popup_id]" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-data-sets-data-pks-attrs-names" title="Attribute name(s) that correspondent to the table Primary Key or unique identifier of each record.">'
						+ '<label>Event Primary Keys:</label>'
						+ '<i class="zmdi zmdi-plus-square widget-item-add" title="Add Primary key attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addWidgetCalendarAttrName(this, event)"></i>'
						+ '<ul prefix="widget_properties[calendar][attributes_name][pks]">'
							+ '<li class="empty-items">No pks defined...</li>'
						+ '</ul>'
					+ '</li>'
					+ '<li class="widget-property-calendar-data-sets-data-title-attribute-name">'
						+ '<label>Event Title Attribute Name: <span class="mandatory">*</span> </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input class="attr_name" name="widget_properties[calendar][attributes_name][title]" placeHolder="Attribute name" title="Attribute name correspondent to the event title." />'
						+ '<i class="zmdi zmdi-edit choose-table-attribute" title="Choose Table attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetDBTableAttributePopup(this, event)"></i>'
					+ '</li>'
					+ '<li class="widget-property-calendar-data-sets-data-start-attribute-name">'
						+ '<label>Event Start Attribute Name: <span class="mandatory">*</span> </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input class="attr_name" name="widget_properties[calendar][attributes_name][start]" placeHolder="Attribute name" title="Attribute name correspondent to the event start date." />'
						+ '<i class="zmdi zmdi-edit choose-table-attribute" title="Choose Table attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetDBTableAttributePopup(this, event)"></i>'
					+ '</li>'
					+ '<li class="widget-property-calendar-data-sets-data-end-attribute-name">'
						+ '<label>Event End Attribute Name: <span class="mandatory">*</span> </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input class="attr_name" name="widget_properties[calendar][attributes_name][end]" placeHolder="Attribute name" title="Attribute name correspondent to the event end date." />'
						+ '<i class="zmdi zmdi-edit choose-table-attribute" title="Choose Table attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetDBTableAttributePopup(this, event)"></i>'
					+ '</li>'
					+ '<li class="widget-property-calendar-data-sets-data-url-attribute-name advanced-option">'
						+ '<label>Event URL Attribute Name: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input class="attr_name" name="widget_properties[calendar][attributes_name][url]" placeHolder="Attribute name" title="Attribute name correspondent to the event url." />'
						+ '<i class="zmdi zmdi-edit choose-table-attribute" title="Choose Table attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetDBTableAttributePopup(this, event)"></i>'
					+ '</li>'
					+ '<li class="widget-property-calendar-data-sets-data-resource-id-attribute-name advanced-option">'
						+ '<label>Event Resource Id Attribute Name: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input class="attr_name" name="widget_properties[calendar][attributes_name][resource_id]" placeHolder="Attribute name" title="Attribute name correspondent to the event resource id." />'
						+ '<i class="zmdi zmdi-edit choose-table-attribute" title="Choose Table attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetDBTableAttributePopup(this, event)"></i>'
					+ '</li>'
					+ '<li class="widget-property-calendar-data-sets-data-group-id-attribute-name advanced-option">'
						+ '<label>Event Group Id Attribute Name: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input class="attr_name" name="widget_properties[calendar][attributes_name][group_id]" placeHolder="Attribute name" title="Attribute name correspondent to the event group id." />'
						+ '<i class="zmdi zmdi-edit choose-table-attribute" title="Choose Table attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetDBTableAttributePopup(this, event)"></i>'
					+ '</li>'
					+ '<li class="widget-property-calendar-data-sets-data-group-display-attribute-name advanced-option">'
						+ '<label>Event Group Display Attribute Name: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input class="attr_name" name="widget_properties[calendar][attributes_name][group_display]" placeHolder="Attribute name" title="Attribute name correspondent to the event group display. Controls which preset rendering style events use. Possible values: auto, block, list-item, background, inverse-background, none" />'
						+ '<i class="zmdi zmdi-edit choose-table-attribute" title="Choose Table attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetDBTableAttributePopup(this, event)"></i>'
					+ '</li>'
					+ '<li class="widget-property-calendar-data-sets-data-color-attribute-name advanced-option">'
						+ '<label>Event Color Attribute Name: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input class="attr_name" name="widget_properties[calendar][attributes_name][color]" placeHolder="Attribute name" title="Attribute name correspondent to the event color." />'
						+ '<i class="zmdi zmdi-edit choose-table-attribute" title="Choose Table attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetDBTableAttributePopup(this, event)"></i>'
					+ '</li>'
				+ '</ul>'
			+ '</li>'
			+ '<li class="group widget-property-calendar-resources-data-sets">'
				+ '<div class="group-title"><i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>Calendar Resources Data-Sets: <span class="zmdi zmdi-plus-square widget-group-item-add" title="Add new resources data-set" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addWidgetCalendarResourcesDataSet(this, event)"></span></div>'
				+ '<div class="widget-group-info">Please note that these data sets will be utilized to display multiple resources in the calendar, but only if you have the Premium features of the FullCalendar installed, which are NOT by default.<br/>Kindly distinguish between calendar resources and framework resources, as they are distinct entities.<br/>For further details on calendar resources, please refer to the provided link in <a href="https://fullcalendar.io/docs/resource-display" target="fullcalendar">fullcalendar.io</a>.</div>'
				+ '<ul prefix="widget_properties[calendar][resources_data_sets]">'
					+ '<li class="empty-items">No resources data-sets defined yet</li>'
				+ '</ul>'
			+ '</li>'
			+ '<li class="group widget-property-calendar-advanced">'
				+ '<div class="group-title"><i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>Advanced:</div>'
				+ '<ul>'
					+ '<li class="widget-property-calendar-include-lib" title="Include javascript lib automatically if not yet included">'
						+ '<label>Include Javascript Lib: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][include_lib]" type="checkbox" value="1" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-time-zone" title="Time Zone: \'UTC\' or \'America/New_York\' or \'Europe/Lisbon\'">'
						+ '<label>Time Zone: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][time_zone]" placeHolder="local" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-locale" title="Language (2 letters): en, pt, es, fr...">'
						+ '<label>Locale: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][locale]" placeHolder="en" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-initial-view" title="View to show as default. Choose one of the followings: multiMonthYear, dayGridMonth, dayGridWeek, dayGridDay, timeGridWeek, timeGridDay, listYear, listMonth, listWeek or listDay. if blank, the default is dayGridMonth.">'
						+ '<label>Initial View: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][initial_view]" placeHolder="dayGridMonth" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-initial-date" title="Date to show as default. Format: YYYY-mm-dd. if blank, the default is the current date.">'
						+ '<label>Initial Date: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][initial_date]" type="date" placeHolder="Blank for current date" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-nav-links" title="Allow user to click day/week names to navigate views. Days in the dayGridMonth become clickable links to timeGridWeek and timeGridDay.">'
						+ '<label>With Nav Links: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][nav_links]" type="checkbox" value="1" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-selectable" title="Allows a user to highlight multiple days or timeslots by clicking and dragging.">'
						+ '<label>Selectable Timeslots: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][selectable]" type="checkbox" value="1" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-select-mirror" title="Whether to draw a “placeholder” event while the user is dragging.">'
						+ '<label>Select Mirror: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][select_mirror]" type="checkbox" value="1" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-now-indicator" title="Whether or not to display a marker indicating the current time.">'
						+ '<label>With Now Indicator: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][now_indicator]" type="checkbox" value="1" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-editable" title="Determines whether the events on the calendar can be modified.">'
						+ '<label>Editable Events: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][editable]" type="checkbox" value="1" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-day-max-events" title="In, dayGrid view, the max number of events within a given day, not counting the +more link. The rest will show up in a popover.">'
						+ '<label>Day Max Events: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][day_max_events]" type="checkbox" value="1" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-all-day-slot" title="Determines if the \'all-day\' slot is displayed at the top of the calendar.">'
						+ '<label>All Day Slot: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][all_day_slot]" type="checkbox" value="1" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-week-numbers" title="Determines if week numbers should be displayed on the calendar.">'
						+ '<label>With Week Numbers: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][week_numbers]" type="checkbox" value="1" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-week-number-calculation" title="The method for calculating week numbers that are displayed with the weekNumbers setting. If blank, the default is \'local\'. ">'
						+ '<label>Week Number Calculation: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][week_numbers_calculation]" placeHolder="local" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-event-color color-style" title="Default events color">'
						+ '<label>Events Color: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input class="color-selector" type="color">'
						+ '<input class="color-code" name="widget_properties[calendar][color]">'
					+ '</li>'
					+ '<li class="widget-property-calendar-slot-duration" title="This is how the time will be divided in the grids. to view this better go to the dayGridWeek or dayGridDay.">'
						+ '<label>Slot Duration: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][slot_duration]" type="time" placeHolder="00:30:00" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-slot-min-time" title="Determines the first time slot that will be displayed for each day.">'
						+ '<label>Slot Min Time: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][slot_min_time]" type="time" placeHolder="00:00:00" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-slot-max-time" title="Determines the last time slot that will be displayed for each day.">'
						+ '<label>Slot Max Time: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][slot_max_time]" type="time" placeHolder="24:00:00" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-business-hours-days-of-week" title="Days of week in a string split by comma - zero-based day of week integers (0=Sunday)">'
						+ '<label>Business Days of Week: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][business_hours][days_of_week]" placeHolder="1, 2, 3, 4, 5" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-business-hours-start-time" title="A start time (10:00 in this example)">'
						+ '<label>Business Start Hour: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][business_hours][start_time]" type="time" placeHolder="8:00" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-business-hours-end-time" title="An end time (18:00 in this example)">'
						+ '<label>Business End Hour: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="widget_properties[calendar][business_hours][end_time]" type="time" placeHolder="20:00" />'
					+ '</li>'
				+ '</ul>'
			+ '</li>';
	}
	
	function getSelectedWidgetMatrixPropertiesSettingsHtml(widget) {
		return '<li class="widget-property-matrix-load-type" title="Define when will the matrix be drawn">'
				+ '<label>Load Matrix: </label>'
				+ '<select onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.syncWidgetLoadResourceLoadType(this, event)">'
					+ '<option value="" selected>-- None --</option>'
					+ '<option value="data-widget-resources-load" title="Create matrix when page loads">On page load</option>'
					+ '<option value="data-widget-item-resources-load" title="Create matrix when parent draws me">When parent draws matrix</option>'
				+ '</select>'
			+ '</li>'
			+ '<li class="widget-property-matrix-data-display-type" title="Define how the resource data will be displayed">'
				+ '<label>Data Display Type: </label>'
				+ '<select name="widget_properties[matrix][data_display_type]">'
					+ '<option value="" selected>Always show data</option>'
					+ '<option value="show_if_exist" title="">Only show data if exists</option>'
				+ '</select>'
			+ '</li>'
			+ '<li class="widget-property-matrix-data-allow-repeated" title="Allow repeated data in the some column">'
				+ '<label>Allow Repeated Data: </label>'
				+ '<input name="widget_properties[matrix][data_allow_repeated]" type="checkbox" value="1" />'
			+ '</li>'
			+ getSelectedWidgetMatrixRowPropertiesSettingsHtml(widget)
			+ '<li class="widget-property-matrix-fks-attrs-names" title="Attribute name(s) that correspondent to the table Foreign Keys for the Axis X and Y.">'
				+ '<label>Foreign Keys:</label>'
	 			+ '<i class="zmdi zmdi-plus-square widget-item-add" title="Add Foreign key attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addWidgetMatrixFKResourceAttrName(this, event)"></i>'
				+ '<table prefix="widget_properties[matrix][fks_attrs_names]">'
					+ '<thead>'
						+ '<tr>'
							+ '<th class="widget-attr-name">Attribute Name</th>'
							+ '<th class="widget-axis">Axis</th>'
							+ '<th class="widget-fk-attr-name">FK Attribute Name</th>'
							+ '<th class="action"></th>'
						+ '</tr>'
					+ '</thead>'
					+ '<tbody>'
						+ '<tr class="empty-items"><td>No fks defined...</td></tr>'
					+ '</tbody>'
				+ '</table>'
			+ '</li>';
	}
	
	function getSelectedWidgetMatrixRowPropertiesSettingsHtml(widget) {
		return '<li class="widget-property-matrix-pks-attrs-names" title="Attribute name(s) that correspondent to the table Primary Key or unique identifier of each record.">'
				+ '<label>Primary Keys:</label>'
	 			+ '<i class="zmdi zmdi-plus-square widget-item-add" title="Add Primary key attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addWidgetMatrixAttrName(this, event)"></i>'
				+ '<ul prefix="widget_properties[matrix][pks_attrs_names]" style="display:block;">'
					+ '<li class="empty-items">No pks defined...</li>'
				+ '</ul>'
			+ '</li>';
	}
	
	function getSelectedWidgetMatrixColumnPropertiesSettingsHtml(widget) {
		return getSelectedWidgetMatrixRowPropertiesSettingsHtml(widget);
	}
	
	function getMainWidgetSettingsHtml() {
		var html = '<li class="group group-open settings-widget">'
				 + '	<div class="group-title"><i class="zmdi zmdi-caret-right toggle" title="Toggle sub-Options"></i>Dynamic Widget</div>'
				 + '	<ul>';
		
		html += getMainWidgetResourcesSettingsHtml();
		html += getMainWidgetDisplayResourceValueSettingsHtml();
		html += getMainWidgetDependenciesSettingsHtml();
		html += getMainWidgetPermissionsSettingsHtml();
		html += getMainWidgetPropertiesSettingsHtml();
		
		html += '	</ul>'
			 + '</li>';
		
		return html;
	}
	
	function getMainWidgetSettingsTabHtml() {
		return '<li class="settings-tab-widget"><a href="#settings-tabs-dummy-container" selector=".settings-widget">Dynamic</a></li>';
	}
	
	function getMainWidgetResourcesSettingsHtml() {
		var html = "";
		
		//set default resources types
		var resources_types = {
			"load": "Resource(s) to load some data into this widget when it gets loaded."
		};
		
		//prepare html
		html += '<li class="group widget-resources">'
			 + '	<div class="group-title"><i class="zmdi zmdi-caret-right toggle" title="Toggle sub-Options"></i>Resources <span class="zmdi zmdi-plus-square widget-group-item-add" title="Add a new resource type" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addWidgetResourceType(this, event)"></span></div>'
			 + '	<ul>';
		
		for (var resources_type in resources_types)
			html += getWidgetResourcesTypeSettingsHtml(resources_type, resources_types[resources_type]);
		
		html += '	</ul>'
			 + '</li>';
		
		return html;
	}
	
	function getMainWidgetDisplayResourceValueSettingsHtml() {
		return '<li class="group widget-display-resource-value group-open with-display-disabled">'
			 + '	<div class="group-title"><i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>Display Resource Attribute <span class="zmdi zmdi-fullscreen widget-display-resource-value-toggle" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleWidgetAdvancedOptions(this, event)" title="Show/Hide advanced options"></span></div>'
			 + '	<div class="widget-group-info">Display an attribute from a loaded the resource</div>'
			 + '	<ul>'
			 + '		<li class="widget-display-resource-value-resource-display" title="">'
			 + '			<label>Display Type: <span class="mandatory">*</span> </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<select onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetResourceValueResourceDisplayField(this, event)">'
			 + '				<option value="" selected>-- Disabled --</option>'
			 + '				<option value="attribute">Show Resource Attribute</option>'
			 + '				<option value="resource">Show Resource it-self</option>'
			 + '			</select>'
			 + '		</li>'
			 + '		<li class="widget-display-resource-value-resource-reference widget-resource-reference widget-swap-field select-shown" title="Choose an existent resource reference">'
			 + '			<label>Resource Reference: <span class="mandatory">*</span> </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<input name="widget_resource_value[resource_name]" placeHolder="Resource reference" onFocus="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onFocusWidgetResourceValueResourceReferenceField(this, event)" onBlur="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onBlurWidgetResourceValueResourceReferenceField(this, event)" />'
			 + '			<select name="widget_resource_value[resource_name]" class="select-existent-widget-resources" onFocus="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onFocusWidgetResourceValueResourceReferenceField(this, event)" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetResourceValueResourceReferenceField(this, event)">'
			 + '				<option value="" selected title="Resource is defined in this widget or in some parent widget">-- Default Resource --</option>'
			 + '			</select>'
			 + '			<i class="zmdi zmdi-swap swap-input-select" title="Swap between pre-defined options or open text option" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.swapInputAndSelectFields(this, event)"></i>'
			+ '			<i class="zmdi zmdi-refresh refresh-existent-data" title="Refresh existent resources references in dropdown box" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.refreshWidgetExistentResourcesReferences(this, event)"></i>'
			+ '			<i class="zmdi zmdi-edit choose-widget-resource" title="Choose a Resource" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetResourceReferencePopup(this, event)"></i>'
			 + '		</li>'
			 + '		<li class="widget-display-resource-value-resource-attribute" title="Attribute name to show">'
			 + '			<label>Attribute Name: <span class="mandatory">*</span> </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<input name="widget_resource_value[attribute]" placeHolder="Attribute name" onBlur="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onBlurWidgetResourceValueAttributeField(this, event)" onFocus="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onFocusWidgetResourceValueAttributeField(this, event)" />'
			 + '			<i class="zmdi zmdi-edit choose-widget-resource-attribute" title="Choose Table attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetResourceValueAttributePopup(this, event)"></i>'
			 + '		</li>'
			 + '		<li class="widget-display-resource-value-resource-index" title="If loading data is a list, please type the row index or leave it in blank for automatic.">'
			 + '			<label>List Index (if apply): </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<input name="widget_resource_value[index]" title="Empty for automatic index or a numeric value" placeHolder="Empty for automatic index or a numeric value" onBlur="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onBlurWidgetResourceValueIndexField(this, event)" onFocus="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onFocusWidgetResourceValueIndexField(this, event)" />'
			 + '		</li>'
			 + '		<li class="widget-display-resource-value-replacement" title="How to show the loading value">'
			 + '			<label>Replacement Type: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<select name="widget_resource_value[type]">'
			 + '				<option value="">replace</option>'
			 + '				<option value="append">append</option>'
			 + '				<option value="prepend">prepend</option>'
			 + '			</select>'
			 + '		</li>'
			 + '		<li class="widget-display-resource-value-display-target-type" title="Where to display the value">'
			 + '			<label>Display Target: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<select name="widget_resource_value[target_type]" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetResourceValueResourceDisplayTargetTypeField(this, event)">'
			 + '				<option value="">Inner HTML</option>'
			 + '				<option value="attribute">Html Attribute</option>'
			 + '			</select>'
			 + '		</li>'
			 + '		<li class="widget-display-resource-value-display-target-attribute" title="Attribute name to be filled with the value">'
			 + '			<label>Display Target Attribute Name: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<input name="widget_resource_value[target_attribute]" />'
			 + '		</li>'
			 + '		<li class="widget-display-resource-value-display-handler widget-swap-field with-extra-callbacks" title="Choose a function to handle the display of the value. Leave it blank for default handler. Default Handler code can be found in the MyWidgetResourceLib.FieldHandler.setFieldValue method.">'
			 + '			<label>Display Handler: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<input name="widget_resource_value[display][]" placeHolder="Blank for default" />'
			 + '			<select name="widget_resource_value[display][]">'
			 + '				<option value="" selected title="Default handler">-- Default --</option>'
			 + '				<option value="MyWidgetResourceLib.ListHandler.drawListData">drawListData</option>'
			 + '				<option value="MyWidgetResourceLib.ListHandler.drawListDataRecursively">drawListDataRecursively</option>'
			 + '				<option value="MyWidgetResourceLib.ListHandler.loadAndDrawListDataRecursively">loadAndDrawListDataRecursively</option>'
			 + '			</select>'
			 + '			<i class="zmdi zmdi-swap swap-input-select" title="Swap between pre-defined options or open text option" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.swapInputAndSelectFields(this, event)"></i>'
		 	 + '			<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
			 + '			<i class="zmdi zmdi-plus-square add-extra-callback" title="Add new callback" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addExtraCallback(this, event)"></i>'
			 + '			<ul class="extra-callbacks"></ul>'
			 + '		</li>'
			 + '		<li class="widget-display-resource-value-display-callback with-extra-callbacks" title="Callback on complete display or replace value. This function receives the arguments: elm">'
			 + '			<label>Complete Display Callback: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<input name="widget_resource_value[complete][display][]" />'
		 	 + '			<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
			 + '			<i class="zmdi zmdi-plus-square add-extra-callback" title="Add new callback" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addExtraCallback(this, event)"></i>'
			 + '			<ul class="extra-callbacks"></ul>'
			 + '		</li>'
			 + '		<li class="widget-display-resource-value-default" title="If loading value is empty, show a default">'
			 + '			<label>Default Value: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<input name="widget_resource_value[default]" title="Default value on empty" placeHolder="Default value on empty" />'
			 + '		</li>'
			 + '		<li class="widget-display-resource-value-ignore-field-name" title="Use the \'Display Resource Value\' attribute for saving actions, overriding the input field\'s name attribute. Particularly handy for Bootstrap radio buttons with generic names in lists, as it fetches field values by name during resource addition and update. When active, the system retrieves the name defined in data-widget-display-resource-value, rather than the name attribute.">'
			 + '			<label>Ignore Field Name Attribute: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<input type="checkbox" name="widget_resource_value[ignore_field_name]" value="1" />'
			 + '		</li>'
			 + '		<li class="widget-display-resource-value-allow-new-options" title="If loading value does not exists in select, add that value">'
			 + '			<label>Allow New Options: </label>'
			 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '			<input type="checkbox" name="widget_resource_value[allow_new_options]" value="1" />'
			 + '		</li>'
			 + '		<li class="widget-available-values widget-display-resource-value-available-values" title="Show a diferent value for the loading value">'
			 + '			<label>Available Values: </label>'
			 + '			<i class="zmdi zmdi-close widget-item-remove" title="Remove all available values" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.removeWidgetAvailableValues(this, event)"></i>'
			 + '			<i class="zmdi zmdi-plus-square widget-item-add" title="Add a new available value" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addWidgetAvailableValue(this, event)"></i>'
			 + '			<select class="ignore">'
			 + '				<option value="">Static</option>'
			 + '				<option value="resource">Resource</option>'
			 + '			</select>'
			 + '			<ul class="widget-sortable-children" prefix="widget_resource_value[available_values]">'
			 + '				<li class="empty-items">No available values defined yet...</li>'
			 + '			</ul>'
			 + '		</li>'
			 + '	</ul>'
			 + '</li>';
	}
	
	function getMainWidgetDependenciesSettingsHtml() {
		return '<li class="group widget-dependencies">'
			 + '	<div class="group-title"><i class="zmdi zmdi-caret-right toggle" title="Toggle sub-Options"></i>Dependencies <span class="zmdi zmdi-plus-square widget-group-item-add" title="Add a new dependency" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addWidgetId(this, event)"></span></div>'
			 + '	<div class="widget-group-info">Add other widgets to reload, everytime an action is executed.</div>'
			 + '	<ul class="widget-sortable-children" prefix="widget_dependencies">'
			 + '		<li class="empty-items">No dependencies defined yet...</li>'
			 + '	</ul>'
			 + '</li>';
	}
	
	function getMainWidgetPermissionsSettingsHtml() {
		return '<li class="group widget-permissions">'
			 + '	<div class="group-title"><i class="zmdi zmdi-caret-right toggle" title="Toggle sub-Options"></i>Permissions</div>'
			 + '	<div class="widget-group-info">Add conditions to show, hide or remove this widget. Each of these permissions will be executed if at least one valid resourced is true and if at least one user matches.</div>'
			 + 	getWidgetPermissionSettingsHtml("show", "widget_permissions[show]")
			 + 	getWidgetPermissionSettingsHtml("hide", "widget_permissions[hide]")
			 + 	getWidgetPermissionSettingsHtml("remove", "widget_permissions[remove]")
			 + '</li>';
	}
	
	function getMainWidgetPropertiesSettingsHtml() {
		var options = "";
		var widget_types = getDefaultWidgetTypes();
		
		var handler = function(obj, prefix) {
			for (var k in obj) {
				var v = obj[k];
				
				if ($.isPlainObject(v)) {
					options += '<option class="widget_type_group" title="' + k + '" disabled>' + prefix + k + '</option>';
					handler(v, prefix + "&nbsp;&nbsp;&nbsp;");
				}
				else
					options += '<option value="' + k + '" title="[' + k + ']: ' + v + '">' + prefix + v + '</option>';
			}
		};
		
		handler(widget_types, "");
		
		return '<li class="group group-open widget-properties">'
				 + '	<div class="group-title"><i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>Properties</div>'
				 + '	<ul>'
				 + '		<li class="widget-property-widget-type" title="Choose a widget type">'
				 + '			<label>Is Default Widget Type: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<select name="widget_properties[type]" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onBeforeChangeWidgetType(this, event)" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetType(this, event)" multiple>'
				 + '				<option value="" title="Not a default widget">-- None --</option>'
				 + 				options
				 + '			</select>'
				 + '		</li>'
				 + '	</ul>'
				 + '</li>';
	}
	
	/* ITEMS HTML FUNCTIONS */
	
	function getWidgetResourcesLoadTypeHtml() {
		return '<li class="widget-property-load-type" title="Choose when the load resources should be executed">'
			 + '	<label>Load Type: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<select name="widget_properties[load_type]" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetResourcesTypeLoadTypeField(this, event)">'
			 + 		'<option value="" selected>-- None --</option>'
			 + 		'<option value="data-widget-resources-load" title="Get load resources when page loads">On page load</option>'
			 + 		'<option value="data-widget-item-resources-load" title="Get load resources when parent draws me">When parent draws item</option>'
			 + '	</select>'
			 + '</li>';
	}
	
	function getWidgetPropertiesHandlerHtml(type, funcs, with_extra_callbacks) {
		var label = getLabel(type);
		var html = "";
		var exists_empty_option = false;
		
		if (funcs)
			for (var k in funcs) {
				if (k == "")
					exists_empty_option = true;
				
				html += '<option value="' + k + '" title="' + funcs[k] + '">' + funcs[k] + '</option>';
			}
		
		return '<li class="widget-property-handler widget-property-handler-' + type + ' widget-swap-field' + (with_extra_callbacks ? ' with-extra-callbacks' : '') + '" title="Choose a function to handle the \'' + label + '\' resource results and display them accordingly.">'
			 + '	<label>' + label + ' Handler: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[' + type + ']' + (with_extra_callbacks ? '[]' : '') + '" placeHolder="JS function name" />'
			 + '	<select name="widget_properties[' + type + ']' + (with_extra_callbacks ? '[]' : '') + '">'
			 + 		(!exists_empty_option ? '<option value="" selected>-- None --</option>' : '')
			 + 		html
			 + '	</select>'
			 + '	<i class="zmdi zmdi-swap swap-input-select" title="Swap between pre-defined options or open text option" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.swapInputAndSelectFields(this, event)"></i>'
			 + '	<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
			 + (with_extra_callbacks ? 
			 	'<i class="zmdi zmdi-plus-square add-extra-callback" title="Add new callback" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addExtraCallback(this, event)"></i>'
			 	+ '<ul class="extra-callbacks"></ul>'
			  : '')
			 + '</li>';
	}
	
	function getWidgetPropertiesCompleteCallbackHtml(type, funcs) {
		var label = getLabel(type);
		var html = "";
		var exists_empty_option = false;
		
		if (funcs)
			for (var k in funcs) {
				if (k == "")
					exists_empty_option = true;
				
				html += '<option value="' + k + '" title="' + funcs[k] + '">' + funcs[k] + '</option>';
			}
		
		return '<li class="widget-property-complete-callback widget-property-complete-callback-' + type + ' widget-swap-field with-extra-callbacks" title="Choose a callback to be called after all the \'' + label + '\' resources get executed. This function receives the arguments: elm, resources_name, resources_cache_key">'
			 + '	<label>Complete ' + label + ' Callback: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[complete][' + type + '][]" placeHolder="JS function name" />'
			 + '	<select name="widget_properties[complete][' + type + '][]">'
			 + 		(!exists_empty_option ? '<option value="" selected>-- None --</option>' : '')
			 + 		html
			 + '	</select>'
			 + '	<i class="zmdi zmdi-swap swap-input-select" title="Swap between pre-defined options or open text option" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.swapInputAndSelectFields(this, event)"></i>'
			 + '	<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
			 + '	<i class="zmdi zmdi-plus-square add-extra-callback" title="Add new callback" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addExtraCallback(this, event)"></i>'
			 + '	<ul class="extra-callbacks"></ul>'
			 + '</li>';
	}
	
	function getWidgetResourcesEndCallbackHtml(type, funcs) {
		var label = getLabel(type);
		var html = "";
		var exists_empty_option = false;
		
		if (funcs)
			for (var k in funcs) {
				if (k == "")
					exists_empty_option = true;
				
				html += '<option value="' + k + '" title="' + funcs[k] + '">' + funcs[k] + '</option>';
			}
		
		return '<li class="widget-property-end-callback widget-property-end-callback-' + type + ' widget-swap-field with-extra-callbacks" title="Choose a callback to be called at the end of the ' + label + ' handler after the complete callback or if any resource is defined. This function receives the arguments: elm">'
			 + '	<label>End ' + label + ' Callback: </label>'
			 + '	<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
			 + '	<input name="widget_properties[end][' + type + '][]" placeHolder="JS function name" />'
			 + '	<select name="widget_properties[end][' + type + '][]">'
			 + 		(!exists_empty_option ? '<option value="" selected>-- None --</option>' : '')
			 + 		html
			 + '	</select>'
			 + '	<i class="zmdi zmdi-swap swap-input-select" title="Swap between pre-defined options or open text option" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.swapInputAndSelectFields(this, event)"></i>'
			 + '	<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
			 + '	<i class="zmdi zmdi-plus-square add-extra-callback" title="Add new callback" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addExtraCallback(this, event)"></i>'
			 + '	<ul class="extra-callbacks"></ul>'
			 + '</li>';
	}
	
	function getWidgetPermissionSettingsHtml(type, prefix) {
		var label = getLabel(type);
		
		return '<div class="widget-permission widget-permission-' + type + '">'
			 + '	<label>' + label + ' this element if there is:</label>'
			 + '	<ul class="group-block">'
			 + '	<li class="widget-permission-valid-resources">'
			 + '		<label>Valid Resource (If any defined):</label>'
			 + '		<i class="zmdi zmdi-plus-square widget-item-add" title="Add a reference for a resource" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addWidgetResourceReference(this, event)"></i>'
			 + '		<ul class="widget-sortable-children" prefix="' + prefix + '[resources]">'
			 + '			<li class="empty-items">No resources defined yet...</li>'
			 + '		</ul>'
			 + '	</li>'
			 + '	<li class="widget-permission-valid-values">'
			 + '		<label>(and) Valid Value:</label>'
			 + '		<i class="zmdi zmdi-plus-square widget-item-add" title="Add a value" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addWidgetValue(this, event)"></i>'
			 + '		<ul class="widget-sortable-children" prefix="' + prefix + '[values]">'
			 + '			<li class="empty-items">No values defined yet...</li>'
			 + '		</ul>'
			 + '	</li>'
		 	 + '	<li class="widget-permission-user-types" ' + (me.options.user_module_installed ? '' : ' style="display:none;"') + '>' //only show this setion if module user is installed
			 + '		<label>(and) Logged User: </label>'
			 + '		<i class="zmdi zmdi-plus-square widget-item-add" title="Add a user type" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addWidgetUserType(this, event)"></i>'
			 + '		<ul class="widget-sortable-children" prefix="' + prefix + '[user_type_ids]">'
			 + '			<li class="empty-items">No user types defined yet...</li>'
			 + '		</ul>'
			 + '	</li>'
			 + '	</ul>'
			 + '</div>';
	}
	
	function getWidgetResourceReferenceSettingsHtml(prefix) {
		return '<li class="widget-resource-reference widget-swap-field select-shown" title="Choose an existent resource reference">'
			 + '	<label>Resource Reference: </label>'
			 + '	<i class="zmdi zmdi-delete widget-item-remove" title="Remove this item" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.removeWidgetResourceReference(this, event)"></i>'
			 + '	<input name="' + prefix + '[idx]" placeHolder="Resource reference" />'
			 + '	<select name="' + prefix + '[idx]" class="select-existent-widget-resources">'
			 + '		<option value="" selected>-- None --</option>'
			 + '	</select>'
			 + '	<i class="zmdi zmdi-swap swap-input-select" title="Swap between pre-defined options or open text option" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.swapInputAndSelectFields(this, event)"></i>'
			 + '	<i class="zmdi zmdi-refresh refresh-existent-data" title="Refresh existent resources references in dropdown box" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.refreshWidgetExistentResourcesReferences(this, event)"></i>'
			 + '	<i class="zmdi zmdi-edit choose-widget-resource" title="Choose a Resource" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetResourceReferencePopup(this, event)"></i>'
			 + '</li>';
	}
	
	function getWidgetValueSettingsHtml(prefix) {
		return '<li class="widget-value">'
			 + '	<label>Value: </label>'
			 + '	<i class="zmdi zmdi-delete widget-item-remove" title="Remove this item" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.removeWidgetValue(this, event)"></i>'
			 + '	<input name="' + prefix + '[idx]" placeHolder="Value" />'
			 + '</li>';
	}
	
	function getWidgetUserTypeSettingsHtml(prefix) {
		return '<li class="widget-user-type widget-swap-field select-shown">'
			 + '	<label>User Type: </label>'
			 + '	<i class="zmdi zmdi-delete widget-item-remove" title="Remove this item" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.removeWidgetUserType(this, event)"></i>'
			 + '	<input name="' + prefix + '[idx]" placeHolder="User Type Id" />'
			 + '	<select name="' + prefix + '[idx]" class="select-existent-user-types">'
			 + '		<option value="" selected>-- None --</option>'
			 + '	</select>'
			 + '	<i class="zmdi zmdi-swap swap-input-select" title="Swap between pre-defined options or open text option" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.swapInputAndSelectFields(this, event)"></i>'
			 + '	<i class="zmdi zmdi-refresh refresh-existent-data" title="Refresh existent user types in dropdown box" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.refreshWidgetExistentUserTypes(this, event)"></i>'
			 + '</li>';
	}
	
	function getWidgetIdSettingsHtml(prefix) {
		return '<li class="widget-id widget-swap-field select-shown">'
			 + '	<label>Widget Id: </label>'
			 + '	<i class="zmdi zmdi-delete widget-item-remove" title="Remove this item" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.removeWidgetId(this, event)"></i>'
			 + '	<input name="' + prefix + '[idx]" placeHolder="Widget Id" />'
			 + '	<select name="' + prefix + '[idx]" class="select-existent-widget-ids">'
			 + '		<option value="" selected>-- None --</option>'
			 + '	</select>'
			 + '	<i class="zmdi zmdi-swap swap-input-select" title="Swap between pre-defined options or open text option" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.swapInputAndSelectFields(this, event)"></i>'
			 + '	<i class="zmdi zmdi-refresh refresh-existent-data" title="Refresh existent widget ids in dropdown box" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.refreshWidgetExistentWidgetIds(this, event)"></i>'
			 + '</li>';
	}
	
	function getWidgetAvailableValueSettingsHtml(type, prefix) {
		if (type == "resource")
			return getWidgetResourceReferenceSettingsHtml(prefix);
		else
			return '<li class="widget-static-item">'
				+ '	<i class="zmdi zmdi-delete widget-item-remove" title="Remove this item" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.removeWidgetAvailableValue(this, event)"></i>'
				+ '	<input name="' + prefix + '[idx][value]" class="widget-static-item-value" placeHolder="Value" />'
				+ '	<input name="' + prefix + '[idx][key]" class="widget-static-item-key" placeHolder="Key" />'
				+ '</li>';
	}
	
	function getWidgetResourcesTypeSettingsHtml(type, info, non_standard) {
		var label = getLabel(type);
		
		return '<li class="group group-open widget-resources-type widget-resources-' + type + '" data-resource-type="' + type + '">'
			 + '		<div class="group-title"><i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>' + label + ' Resources <span class="zmdi zmdi-plus-square widget-group-item-add" title="Add a new resource" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addWidgetResource(this, event)"></span>'
			 + (non_standard ? '<i class="zmdi zmdi-delete widget-item-remove" title="Remove this resource type" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.removeWidgetResourceType(this, event)"></i>' : '')
			 + '		</div>'
			 + '		<div class="widget-group-info">' + info + '</div>'
			 + '		<div class="widget-resources-type-properties">'
			 + '			<ul>'
			 + 				(type == "load" ? getWidgetPropertiesHandlerHtml(type, null, true) : "")
			 + 				getWidgetPropertiesCompleteCallbackHtml(type)
			 + 				getWidgetResourcesEndCallbackHtml(type)
			 + 				(type == "load" ? getWidgetResourcesLoadTypeHtml() : "")
			 + '			</ul>'
			 + '		</div>'
			 + '		<ul class="widget-sortable-children" prefix="widget_resources[' + type + ']">'
			 + '			<li class="empty-items">No resources defined yet...</li>'
			 + 			getWidgetResourceSettingsHtml('widget_resources[' + type + ']')
			 + '		</ul>'
			 + '	</li>';
	}
	
	function getWidgetResourceSettingsHtml(prefix) {
		var html = '<li class="widget-resource">'
				 + '	<i class="zmdi zmdi-delete widget-resource-remove" title="Remove this item" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.removeWidgetResource(this, event)"></i>'
				 + '	<i class="zmdi zmdi-fullscreen widget-resource-toggle" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleWidgetAdvancedOptions(this, event)" title="Show/Hide advanced options"></i>'
				 + '	<ul class="group-block">'
				 + '		<li class="widget-resource-reference widget-swap-field select-shown" title="Choose an existent resource reference or if is an URL type, type a new reference so the url result be saved.">'
				 + '			<label>Resource Reference: <span class="mandatory">*</span> </label>'
				 + '			<input name="' + prefix + '[idx][name]" placeHolder="Resource reference/name" onBlur="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onBlurWidgetResourcesTypeResourceReferenceField(this, event)" />'
				 + '			<select name="' + prefix + '[idx][name]" class="select-existent-widget-resources" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetResourcesTypeResourceReferenceField(this, event)">'
				 + '				<option value="" selected>-- None --</option>'
				 + '			</select>'
				 + '			<span class="mandatory">*</span>'
				 + '			<i class="zmdi zmdi-swap swap-input-select" title="Swap between pre-defined options or open text option" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.swapInputAndSelectFields(this, event)"></i>'
				 + '			<i class="zmdi zmdi-refresh refresh-existent-data" title="Refresh existent resources references in dropdown box" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.refreshWidgetExistentResourcesReferences(this, event)"></i>'
				 + '			<i class="zmdi zmdi-edit choose-widget-resource" title="Choose a Resource" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetResourceOriginalPopup(this, event)"></i>'
				 + '		</li>'
				 + '		<li class="widget-resource-type" title="Native resource or external url">'
				 + '			<label>Type: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<select name="' + prefix + '[idx][type]" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.changeWidgetResourceType(this, event)">'
				 + '				<option value="">Native</option>'
				 + '				<option value="url">Url</option>'
				 + '			</select>'
				 + '		</li>'
				 + '		<li class="widget-resource-value">'
				 + '			<label>Url: <span class="mandatory">*</span> </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input name="' + prefix + '[idx][value]" />'
			 	 + '			<i class="zmdi zmdi-search choose-page" title="Choose a page url" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetPageUrlPopup(this, event)"></i>'
				 + '		</li>'
				 + '		<li class="widget-resource-mehod" title="Method to request the resource">'
				 + '			<label>Method: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<select name="' + prefix + '[idx][method]">'
				 + '				<option value="">-- Default --</option>'
				 + '				<option value="GET">Get</option>'
				 + '				<option value="POST">Post</option>'
				 + '			</select>'
				 + '		</li>'
				 + '		<li class="widget-resource-data-type" title="Data type of the request response">'
				 + '			<label>Data Type: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<select name="' + prefix + '[idx][data_type]">'
				 + '				<option value="">-- Default --</option>'
				 + '				<option value="json">JSON</option>'
				 + '				<option value="xml">XML</option>'
				 + '				<option value="text">Text or Html</option>'
				 + '				<option value="javascript">Javascript Code</option>'
				 + '				<option value="javascript_in_html">Javascript in Html</option>'
				 + '			</select>'
				 + '		</li>'
				 + '		<li class="widget-resource-target" title="Write a target window name if you wish to execute this resource in a different window. Leave it blank for default and execute it in the same window as an ajax request. Note that if this option is active, the callbacks and messages below will be discard!">'
				 + '			<label>Target Window: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input name="' + prefix + '[idx][target]" />'
				 + '		</li>'
				 + '		<li class="widget-resource-confirmation-message" title="Message to show on confirmation. Eg: Do you wish to proceed?" placeHolder="Do you wish to proceed?">'
				 + '			<label>Confirmation Message: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input name="' + prefix + '[idx][confirmation_message]" />'
				 + '		</li>'
				 + '		<li class="widget-resource-before-callback" title="Callback to parse the ajax settings before it sends the request. This function receives the arguments: ajax_options">'
				 + '			<label>On Before Callback: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input name="' + prefix + '[idx][before]" />'
			 	 + '			<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
				 + '		</li>'
				 + '		<li class="widget-resource-parse-callback" title="Callback to parse the response. This function returns data and receives the arguments: elm, data">'
				 + '			<label>On Parse Callback: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input name="' + prefix + '[idx][parse]" />'
			 	 + '			<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
				 + '		</li>'
				 + '		<li class="widget-resource-validate-callback with-extra-callbacks" title="Callback to validate the response. This function returns true/false and receives the arguments: elm, data">'
				 + '			<label>On Validate Callback: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input name="' + prefix + '[idx][validate][]" />'
			 	 + '			<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
				 + '			<i class="zmdi zmdi-plus-square add-extra-callback" title="Add new callback" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addExtraCallback(this, event)"></i>'
				 + '			<ul class="extra-callbacks"></ul>'
				 + '		</li>'
				 + '		<li class="widget-resource-success-callback with-extra-callbacks" title="Callback on success validation. This function receives the arguments: elm, data">'
				 + '			<label>On Success Callback: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input name="' + prefix + '[idx][success][]" />'
			 	 + '			<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
				 + '			<i class="zmdi zmdi-plus-square add-extra-callback" title="Add new callback" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addExtraCallback(this, event)"></i>'
				 + '			<ul class="extra-callbacks"></ul>'
				 + '		</li>'
				 + '		<li class="widget-resource-success-message" title="Message to show on success. Eg: Executed successfully!" placeHolder="Executed successfully!">'
				 + '			<label>Success Message: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input name="' + prefix + '[idx][success_message]" />'
				 + '		</li>'
				 + '		<li class="widget-resource-error-callback with-extra-callbacks" title="Callback on error. This function returns true/false and receives the arguments: elm, jqXHR, text_status, data. If this callback returns false, the error message will not be displayed.">'
				 + '			<label>On Error Callback: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input name="' + prefix + '[idx][error][]" />'
			 	 + '			<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined handlers" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetHandlerPopup(this, event)"></i>'
				 + '			<i class="zmdi zmdi-plus-square add-extra-callback" title="Add new callback" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addExtraCallback(this, event)"></i>'
				 + '			<ul class="extra-callbacks"></ul>'
				 + '		</li>'
				 + '		<li class="widget-resource-error-message" title="Message to show on error. Eg: Error: executed unsuccessfully. Please try again..." placeHolder="Error: executed unsuccessfully. Please try again...">'
				 + '			<label>Error Message: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input name="' + prefix + '[idx][error_message]" />'
				 + '		</li>'
				 + '		<li class="widget-resource-conditions" title="Conditions to be passed on resource execution" data-prefix="' + prefix + '[idx][conditions]">'
				 + '			<label>Initial Conditions: </label>'
				 + '			<span class="zmdi zmdi-plus-square widget-group-item-add add-resource-condition" title="Add a new condition" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addWidgetResourceCondition(this, event)"></span>'
				 + '			<div class="widget-resource-conditions-table">'
				 + '				<table>'
				 + '					<thead>'
				 + '						<tr>'
				 + '							<th class="name">Name</th>'
				 + '							<th class="type">Type</th>'
				 + '							<th class="value">Value</th>'
				 + '							<th class="case">Case</th>'
				 + '							<th class="operator">Operator</th>'
				 + '							<th class="action"></th>'
				 + '						</tr>'
				 + '					</thead>'
				 + '					<tbody>'
				 + '						<tr class="empty-items"><td colspan="6">No conditions defined</td></tr>'
				 + '					</tbody>'
				 + '				</table>'
				 + '			</div>'
				 + '		</li>'
				 + '	</ul>'
				 + '</li>';
		
		return html;
	}
	
	function getWidgetAttrNameHtml(prefix, attr_name) {
		if (!attr_name)
			attr_name = "";
		
		return '<li class="widget-attr-name">'
			 + '	<label>Attribute Name: </label>'
			 + '	<i class="zmdi zmdi-delete widget-item-remove" title="Remove this item" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.removeWidgetAttrName(this, event)"></i>'
			 + '	<input class="attr_name" name="' + prefix + '[idx]" value="' + attr_name + '" placeHolder="Attribute name" onFocus="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onFocusWidgetAttrName(this, event)" onBlur="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onBlurWidgetAttrName(this, event)" />'
			 + '	<i class="zmdi zmdi-edit choose-table-attribute" title="Choose Table attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetDBTableAttributePopup(this, event)"></i>'
			 + '</li>';
	}
	
	function getWidgetAttrNameAndValueHtml(prefix, attr_name, attr_value) {
		if (!attr_name)
			attr_name = "";
		
		return '<li class="widget-attr-name">'
			 + '	<i class="zmdi zmdi-delete widget-item-remove" title="Remove this item" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.removeWidgetAttrName(this, event)"></i>'
			 + '	<input class="attr_value" name="' + prefix + '[idx]" value="' + attr_value.replace(/"/g, "&quot;") + '" placeHolder="Attribute value" onFocus="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onFocusWidgetAttrName(this, event)" onBlur="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onBlurWidgetAttrName(this, event)" />'
			 + '	<input class="attr_name" name="' + prefix + '[idx]" value="' + attr_name + '" placeHolder="Attribute name" onBlur="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onBlurWidgetAttrName(this, event)" />'
			 + '	<i class="zmdi zmdi-edit choose-table-attribute" title="Choose Table attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetDBTableAttributePopup(this, event)"></i>'
			 + '</li>';
	}
	
	function getWidgetGraphDataSetSettingsHtml(prefix) {
		return '<li class="widget-property-graph-data-set">'
				+ '<label>Data-set with order: </label>'
				 + '<i class="zmdi zmdi-delete widget-item-remove" title="Remove this data-set" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.removeWidgetGraphDataSet(this, event)"></i>'
				+ '<input name="' + prefix + '[idx][data_order]" type="number" min="0" title="Set the data-sets order in the graph. Leave it blank for default." />'
				+ '<ul>'
					+ '<li class="widget-property-graph-data-set-chart-type">'
						+ '<label>Chart Type: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<select name="' + prefix + '[idx][chart_type]">'
							+ '<option value="vertical_bar">Vertical Bar Chart</option>'
							+ '<option value="horizontal_bar">Horizontal Bar Chart</option>'
							+ '<option value="line">Line Chart</option>'
							+ '<option value="bubble">Bubble Chart</option>'
							+ '<option value="pie">Pie Chart</option>'
							+ '<option value="doughnut">Doughnut Chart</option>'
							+ '<!--option value="area">Area Chart</option-->'
							+ '<option value="polarArea">Polar Area Chart</option>'
							+ '<option value="radar">Radar Chart</option>'
							+ '<option value="scatter">Scatter Chart</option>'
						+ '</select>'
					+ '</li>'
					+ '<li class="widget-property-graph-data-set-data-type">'
						+ '<label>Data Type: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<select name="' + prefix + '[idx][data_type]" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetGraphDataSetDataType(this, event)">'
							+ '<option value="resource">Based in a Resource</option>'
							+ '<option value="hardcoded" title="Values must be a valid JSON or comma delimiter string">Hard coded values</option>'
							+ '<option value="parent" title="Values come from a inner object of a parent resource attribute that was displayed before.">From parent display</option>'
						+ '</select>'
					+ '</li>'
					+ '<li class="widget-property-graph-data-set-data-resource-name widget-swap-field select-shown">'
						+ '<label>Data Resource Name: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="' + prefix + '[idx][data_resource_name]" placeholder="Resource name for the chart data">'
						+ '<select name="' + prefix + '[idx][data_resource_name]" title="Choose one of the existing resources" class="select-existent-widget-resources">'
							+ '<option value="" selected>-- Parent Resource --</option>'
						+ '</select>'
						+ '<i class="zmdi zmdi-swap swap-input-select" title="Swap between pre-defined options or open text option" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.swapInputAndSelectFields(this, event)"></i>'
						+ '<i class="zmdi zmdi-refresh refresh-existent-data" title="Refresh existent resources references in dropdown box" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.refreshWidgetExistentResourcesReferences(this, event)"></i>'
					+ '</li>'
					+ '<li class="widget-property-graph-data-set-data-title">'
						+ '<label>Data Title: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="' + prefix + '[idx][data_title]" />'
					+ '</li>'
					+ '<li class="widget-property-graph-data-set-data-labels">'
						+ '<label>Data Labels: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="' + prefix + '[idx][data_labels]" placeHolder="Attribute name or strings divided by comma delimiter" title="Attribute name or strings divided by comma delimiter" />'
					+ '</li>'
					+ '<li class="widget-property-graph-data-set-data-values">'
						+ '<label>Data Values: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="' + prefix + '[idx][data_values]" placeHolder="Attribute name" title="Attribute name" />'
					+ '</li>'
					+ '<li class="widget-property-graph-data-set-background-color color-style">'
						+ '<label>Background Color: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input class="color-selector" type="color">'
						+ '<input class="color-code ignore" name="' + prefix + '[idx][background_color]">'
					+ '</li>'
					+ '<li class="widget-property-graph-data-set-border-color color-style">'
						+ '<label>Border Color: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input class="color-selector" type="color">'
						+ '<input class="color-code ignore" name="' + prefix + '[idx][border_color]">'
					+ '</li>'
					+ '<li class="widget-property-graph-data-set-border-width color-style">'
						+ '<label>Border Width: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="' + prefix + '[idx][border_width]" type="number" min="0" />'
					+ '</li>'
					+ '<li class="widget-property-graph-data-set-data-parse" title="Define a parse callback function for this data settings before it gets added to the chart main settings. This function returns data_set_obj and receives the arguments: elm, data_set_obj.">'
						+ '<label>Parse Callback: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="' + prefix + '[idx][data_parse]" />'
					+ '</li>'
				+ '</ul>'
			+ '</li>';
	}
	
	function getWidgetCalendarDataSetSettingsHtml(prefix) {
		return '<li class="widget-property-calendar-data-set">'
				+ '<label>Data-set: <span class="zmdi zmdi-hc-lg zmdi-info-outline info" title="Please use this section exclusively for modifying settings from the \'Settings for all Data-sets\'. Otherwise, please utilize the \'Settings for all Data-sets\' section instead.."></span></label>'
				+ '<i class="zmdi zmdi-delete widget-item-remove" title="Remove this data-set" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.removeWidgetCalendarDataSet(this, event)"></i>'
				+ '<i class="zmdi zmdi-fullscreen toggle-advanced-options" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleWidgetAdvancedOptions(this, event)" title="Show/Hide advanced options"></i>'
				+ '<ul>'
					+ '<li class="widget-property-calendar-data-set-data-type">'
						+ '<label>Data Type: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<select name="' + prefix + '[idx][data_type]" onChange="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeWidgetCalendarDataSetDataType(this, event)">'
							+ '<option value="resource">Based in a Resource</option>'
							+ '<option value="parent" title="Values come from a inner object of a parent resource attribute that was displayed before.">From parent display</option>'
						+ '</select>'
					+ '</li>'
					+ '<li class="widget-property-calendar-data-set-data-resource-name widget-swap-field select-shown">'
						+ '<label>Data Resource Name: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="' + prefix + '[idx][data_resource_name]" placeholder="Resource name for the chart data">'
						+ '<select name="' + prefix + '[idx][data_resource_name]" title="Choose one of the existing resources" class="select-existent-widget-resources">'
							+ '<option value="" selected>-- Parent Resource --</option>'
						+ '</select>'
						+ '<i class="zmdi zmdi-swap swap-input-select" title="Swap between pre-defined options or open text option" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.swapInputAndSelectFields(this, event)"></i>'
						+ '<i class="zmdi zmdi-refresh refresh-existent-data" title="Refresh existent resources references in dropdown box" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.refreshWidgetExistentResourcesReferences(this, event)"></i>'
					+ '</li>'
					+ '<li class="widget-property-calendar-data-set-data-parse" title="Define a parse callback function for this data settings before it gets added to the calendar main settings. This function returns data and receives the arguments: elm, data.">'
						+ '<label>Parse Callback: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="' + prefix + '[idx][data_parse]" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-data-set-data-popup-id">'
						+ '<label>Edit Event Popup Id: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="' + prefix + '[idx][data_popup_id]" title="Popup Id for each event, if apply" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-data-set-data-pks-attrs-names advanced-option" title="Attribute name(s) that correspondent to the table Primary Key or unique identifier of each record.">'
						+ '<label>Event Primary Keys:</label>'
			 			+ '<i class="zmdi zmdi-plus-square widget-item-add" title="Add Primary key attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addWidgetCalendarAttrName(this, event)"></i>'
						+ '<ul prefix="' + prefix + '[idx][data_pks_attrs_names]">'
							+ '<li class="empty-items">No pks defined...</li>'
						+ '</ul>'
					+ '</li>'
					+ '<li class="widget-property-calendar-data-set-data-title-attribute-name advanced-option">'
						+ '<label>Event Title Attribute Name: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input class="attr_name" name="' + prefix + '[idx][data_title_attribute_name]" placeHolder="Attribute name" title="Attribute name correspondent to the event title. Default name is: title." />'
						+ '<i class="zmdi zmdi-edit choose-table-attribute" title="Choose Table attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetDBTableAttributePopup(this, event)"></i>'
					+ '</li>'
					+ '<li class="widget-property-calendar-data-set-data-start-attribute-name advanced-option">'
						+ '<label>Event Start Attribute Name: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input class="attr_name" name="' + prefix + '[idx][data_start_attribute_name]" placeHolder="Attribute name" title="Attribute name correspondent to the event start date. Default name is: start." />'
						+ '<i class="zmdi zmdi-edit choose-table-attribute" title="Choose Table attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetDBTableAttributePopup(this, event)"></i>'
					+ '</li>'
					+ '<li class="widget-property-calendar-data-set-data-end-attribute-name advanced-option">'
						+ '<label>Event End Attribute Name: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input class="attr_name" name="' + prefix + '[idx][data_end_attribute_name]" placeHolder="Attribute name" title="Attribute name correspondent to the event end date. Default name is: end." />'
						+ '<i class="zmdi zmdi-edit choose-table-attribute" title="Choose Table attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetDBTableAttributePopup(this, event)"></i>'
					+ '</li>'
					+ '<li class="widget-property-calendar-data-set-data-url-attribute-name advanced-option">'
						+ '<label>Event URL Attribute Name: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input class="attr_name" name="' + prefix + '[idx][data_url_attribute_name]" placeHolder="Attribute name" title="Attribute name correspondent to the event url. Default name is: url." />'
						+ '<i class="zmdi zmdi-edit choose-table-attribute" title="Choose Table attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetDBTableAttributePopup(this, event)"></i>'
					+ '</li>'
					+ '<li class="widget-property-calendar-data-set-data-resource-id-attribute-name advanced-option">'
						+ '<label>Event Resource Id Attribute Name: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input class="attr_name" name="' + prefix + '[idx][data_resource_id_attribute_name]" placeHolder="Attribute name" title="Attribute name correspondent to the event resource id. Default name is: resource_id." />'
						+ '<i class="zmdi zmdi-edit choose-table-attribute" title="Choose Table attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetDBTableAttributePopup(this, event)"></i>'
					+ '</li>'
					+ '<li class="widget-property-calendar-data-set-data-group-id-attribute-name advanced-option">'
						+ '<label>Event Group Id Attribute Name: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input class="attr_name" name="' + prefix + '[idx][data_group_id_attribute_name]" placeHolder="Attribute name" title="Attribute name correspondent to the event group id." />'
						+ '<i class="zmdi zmdi-edit choose-table-attribute" title="Choose Table attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetDBTableAttributePopup(this, event)"></i>'
					+ '</li>'
					+ '<li class="widget-property-calendar-data-set-data-group-display-attribute-name advanced-option">'
						+ '<label>Event Group Display Attribute Name: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input class="attr_name" name="' + prefix + '[idx][data_group_display_attribute_name]" placeHolder="Attribute name" title="Attribute name correspondent to the event group display. Controls which preset rendering style events use. Possible values: auto, block, list-item, background, inverse-background, none" />'
						+ '<i class="zmdi zmdi-edit choose-table-attribute" title="Choose Table attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetDBTableAttributePopup(this, event)"></i>'
					+ '</li>'
					+ '<li class="widget-property-calendar-data-set-data-color-attribute-name advanced-option">'
						+ '<label>Event Color Attribute Name: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input class="attr_name" name="' + prefix + '[idx][data_color_attribute_name]" placeHolder="Attribute name" title="Attribute name correspondent to the event color." />'
						+ '<i class="zmdi zmdi-edit choose-table-attribute" title="Choose Table attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetDBTableAttributePopup(this, event)"></i>'
					+ '</li>'
					+ '<li class="widget-property-calendar-data-set-background-color color-style advanced-option">'
						+ '<label>Events Background Color: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input class="color-selector" type="color">'
						+ '<input class="color-code" name="' + prefix + '[idx][background_color]">'
					+ '</li>'
					+ '<li class="widget-property-calendar-data-set-text-color color-style advanced-option">'
						+ '<label>Events Text Color: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input class="color-selector" type="color">'
						+ '<input class="color-code" name="' + prefix + '[idx][text_color]">'
					+ '</li>'
				+ '</ul>'
			+ '</li>';
	}
	
	function getWidgetCalendarResourceDataSetSettingsHtml(prefix) {
		return '<li class="widget-property-calendar-resources-data-set">'
				+ '<label>Data-set: </label>'
				+ '<i class="zmdi zmdi-delete widget-item-remove" title="Remove this data-set" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.removeWidgetCalendarResourcesDataSet(this, event)"></i>'
				+ '<i class="zmdi zmdi-fullscreen toggle-advanced-options" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleWidgetAdvancedOptions(this, event)" title="Show/Hide advanced options"></i>'
				+ '<ul>'
					+ '<li class="widget-property-calendar-resources-data-set-data-resource-name widget-swap-field select-shown">'
						+ '<label>Data Resource Name: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="' + prefix + '[idx][data_resource_name]" placeholder="Resource name for the chart data">'
						+ '<select name="' + prefix + '[idx][data_resource_name]" title="Choose one of the existing resources" class="select-existent-widget-resources">'
							+ '<option value="" selected>-- Parent Resource --</option>'
						+ '</select>'
						+ '<i class="zmdi zmdi-swap swap-input-select" title="Swap between pre-defined options or open text option" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.swapInputAndSelectFields(this, event)"></i>'
						+ '<i class="zmdi zmdi-refresh refresh-existent-data" title="Refresh existent resources references in dropdown box" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.refreshWidgetExistentResourcesReferences(this, event)"></i>'
					+ '</li>'
					+ '<li class="widget-property-calendar-resources-data-set-data-parse" title="Define a parse callback function for this data settings before it gets added to the calendar main settings. This function returns data and receives the arguments: elm, data.">'
						+ '<label>Parse Callback: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input name="' + prefix + '[idx][data_parse]" />'
					+ '</li>'
					+ '<li class="widget-property-calendar-resources-data-set-data-pks-attrs-names advanced-option" title="Attribute name(s) that correspondent to the table Primary Key or unique identifier of each record.">'
						+ '<label>Resource Primary Keys:</label>'
			 			+ '<i class="zmdi zmdi-plus-square widget-item-add" title="Add Primary key attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.addWidgetCalendarAttrName(this, event)"></i>'
						+ '<ul prefix="' + prefix + '[idx][data_pks_attrs_names]">'
							+ '<li class="empty-items">No pks defined...</li>'
						+ '</ul>'
					+ '</li>'
					+ '<li class="widget-property-calendar-resources-data-set-data-title-attribute-name advanced-option">'
						+ '<label>Resource Title Attribute Name: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input class="attr_name" name="' + prefix + '[idx][data_title_attribute_name]" placeHolder="Attribute name" title="Attribute name correspondent to the resource title. Default name is: title." />'
						+ '<i class="zmdi zmdi-edit choose-table-attribute" title="Choose Table attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetDBTableAttributePopup(this, event)"></i>'
					+ '</li>'
					+ '<li class="widget-property-calendar-resources-data-set-data-children-attribute-name advanced-option">'
						+ '<label>Event Children Attribute Name: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input class="attr_name" name="' + prefix + '[idx][data_children_attribute_name]" placeHolder="Attribute name" title="Attribute name correspondent to the event children sub-set. Default name is: children." />'
						+ '<i class="zmdi zmdi-edit choose-table-attribute" title="Choose Table attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetDBTableAttributePopup(this, event)"></i>'
					+ '</li>'
					+ '<li class="widget-property-calendar-resources-data-set-data-allow-events-attribute-name advanced-option">'
						+ '<label>Resource Allow Events Attribute Name: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input class="attr_name" name="' + prefix + '[idx][data_allow_events_attribute_name]" placeHolder="Attribute name" title="Attribute name correspondent to the resource allow events." />'
						+ '<i class="zmdi zmdi-edit choose-table-attribute" title="Choose Table attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetDBTableAttributePopup(this, event)"></i>'
					+ '</li>'
					+ '<li class="widget-property-calendar-resources-data-set-data-color-attribute-name advanced-option">'
						+ '<label>Event Color Attribute Name: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input class="attr_name" name="' + prefix + '[idx][data_color_attribute_name]" placeHolder="Attribute name" title="Attribute name correspondent to the event color." />'
						+ '<i class="zmdi zmdi-edit choose-table-attribute" title="Choose Table attribute" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.toggleChooseWidgetDBTableAttributePopup(this, event)"></i>'
					+ '</li>'
					+ '<li class="widget-property-calendar-resources-data-set-background-color color-style advanced-option">'
						+ '<label>Events Background Color: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input class="color-selector" type="color">'
						+ '<input class="color-code" name="' + prefix + '[idx][background_color]">'
					+ '</li>'
					+ '<li class="widget-property-calendar-resources-data-set-text-color color-style advanced-option">'
						+ '<label>Events Text Color: </label>'
						+ '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
						+ '<input class="color-selector" type="color">'
						+ '<input class="color-code" name="' + prefix + '[idx][text_color]">'
					+ '</li>'
				+ '</ul>'
			+ '</li>';
	}
	
	function getWidgetMatrixFKResourceAttrNameHtml(prefix) {
		return '<tr>'
				+ '<td class="widget-attr-name" title="Attribute name correspondent to the records that will be drawn">'
					+ '<input name="' + prefix + '[tridx][attr_name]" placeHolder="Attribute name" />'
				+ '</td>'
				+ '<td class="widget-axis" title="Axis X or Y where the resources are defined">'
					+ '<select name="' + prefix + '[tridx][axis]">'
						+ '<option value="">both</option>'
						+ '<option>x</option>'
						+ '<option>y</option>'
					+ '</select>'
				+ '</td>'
				+ '<td class="widget-fk-attr-name" title="Parent Resource attribute name that this fk attribute is related to">'
					+ '<input name="' + prefix + '[tridx][fk_attr_name]" placeHolder="FK Attribute name" />'
				+ '</td>'
				+ '<td class="action"><i class="zmdi zmdi-delete remove-fk" title="Remove this fk" onClick="' + ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.removeWidgetMatrixFKResourceAttrName(this, event)"></i></td>'
			+ '</tr>';
	}
	
	/* WIDGETS DEFAULT HTML */
	
	function getWidgetSearchHtml(elm, widget, type) {
		var db_attributes = getWidgetGroupDBAttributes(widget);
		
		if (db_attributes && !$.isEmptyObject(db_attributes)) {
			//prepare id
			var main_widget_suffix_id = me.getWidgetSuffix(widget);
			var widget_prefix_id = "widget_search_" + (main_widget_suffix_id ? main_widget_suffix_id + "_" : "");
			var widget_suffix_id = me.getNewWidgetIdCount(widget_prefix_id);
			var widget_id = widget_prefix_id + widget_suffix_id;
			
			//prepare dependent_widgets_id
			var dependent_widgets_id = getWidgetGroupDependentWidgetsId(widget);
			
			var names = [];
			var names_options_html = "";
			var search_props_html = "";
			
			$.each(db_attributes, function(attr_name, attr) {
				if (!isDBInternalAttributeName(attr_name)) {
					names.push(attr_name);
					names_options_html += '<option value="' + attr_name + '" title="' + getLabel(attr_name) + '">' + getLabel(attr_name) + '</option>';
				}
			});
			
			if (dependent_widgets_id) {
				var search_props = {dependent_widgets_id: dependent_widgets_id};
				search_props_html = ' data-widget-props="' + JSON.stringify(search_props).replace(/"/g, "&quot;") + '"';
			}
			
			var html = '<div id="' + widget_id + '" class="card mb-4 text-left text-start" data-widget-search' + search_props_html + '>'
					+ '<div class="card-body">'
						+ '<label class="text-muted mb-1 small">Filter by:</label>'
						+ (!type || type == "input" ? getWidgetSearchInputHtml(elm, widget, names) : "")
						+ (type == "select" ? getWidgetSearchSelectHtml(elm, widget, names) : "")
						+ (type == "user" ? getWidgetSearchUserChoiceHtml(elm, widget, names_options_html) : "")
					+ '</div>'
				+ '</div>';
			
			return html;
		}
		
		return null;
	}
	
	function getWidgetSearchInputHtml(elm, widget, names) {
		if (!names) {
			var db_attributes = getWidgetGroupDBAttributes(widget);
			
			if (db_attributes && !$.isEmptyObject(db_attributes)) {
				names = [];
				
				$.each(db_attributes, function(attr_name, attr) {
					if (!isDBInternalAttributeName(attr_name))
						names.push(attr_name);
				});
			}
		}
		
		return '<div class="input-group input-group-sm" data-widget-search-input data-widget-props="{&quot;search_attrs&quot;:&quot;' + names.join(",") + '&quot;, &quot;search_operator&quot;:&quot;or&quot;}">'
				+ '<input class="form-control border border-secondary" placeHolder="Type to search..." onKeyUp="MyWidgetResourceLib.SearchHandler.onKeyUpSearchWidgetThroughInput(this, 1); return false;" onBlur="MyWidgetResourceLib.SearchHandler.refreshSearchWidgetThroughInput(this); return false;" />'
				//+ '<div class="input-group-append">' //new bootstrap doesn't need this anymore
					+ '<button class="btn btn-sm btn-outline-secondary text-nowrap" onClick="MyWidgetResourceLib.SearchHandler.refreshSearchWidgetThroughInput(this, true); return false;" title="Search">'
						+ '<i class="bi bi-search icon icon-search overflow-visible"></i>'
					+ '</button>'
					+ '<button class="btn btn-sm btn-outline-secondary text-nowrap" onClick="MyWidgetResourceLib.SearchHandler.resetSearchWidgetThroughInput(this, true); return false;" title="Reset">'
						+ '<i class="bi bi-x icon icon-reset overflow-visible"></i>'
					+ '</button>'
				//+ '</div>'
			+ '</div>';
	}
	
	function getWidgetSearchSelectHtml(elm, widget, names) {
		//prpeare names
		if (!names) {
			var db_attributes = getWidgetGroupDBAttributes(widget);
			
			if (db_attributes && !$.isEmptyObject(db_attributes)) {
				names = [];
				
				//var no_pks = !hasDBAttributesPKs(db_attributes);
				
				$.each(db_attributes, function(attr_name, attr) {
					//if (isDBAttributePK(no_pks, attr_name, attr))
					if (!isDBInternalAttributeName(attr_name))
						names.push(attr_name);
				});
			}
		}
		
		//create resource to load the select field, by getting the values from the correspondent table
		var properties = me.getWidgetGroupProperties(widget);
		var resource_name = "";
		
		if (properties && properties["db_table"]) {
			permissions = me.getWidgetPermissions(widget);
			resource_name = addWidgetResourceByType(widget, "load", "get_all_options", permissions, {table: properties["db_table"], table_alias: properties["db_table_alias"]}, true);
		}
		else
			ui_creator.showMessage("Please create a resource to load the correspondent values for this select field.");
		
		return '<div class="input-group" data-widget-search-select data-widget-props="{&quot;search_attrs&quot;:&quot;' + names.join(",") + '&quot;, &quot;search_operator&quot;:&quot;or&quot;, &quot;search_type&quot;:&quot;equal&quot;}" >'
				+ '<select class="form-control custom-select form-select border border-secondary" onChange="MyWidgetResourceLib.SearchHandler.refreshSearchWidgetThroughSelect(this); return false;" data-widget-props="{&quot;load&quot;:&quot;MyWidgetResourceLib.FieldHandler.loadFieldResource&quot;}" data-widget-resources="' + resource_name + '" data-widget-resources-load>'
					+ '<option value=""></option>'
				+ '</select>'
				//+ '<div class="input-group-append">' //new bootstrap doesn't need this anymore
					+ '<button class="btn btn-sm btn-outline-secondary text-nowrap" onClick="MyWidgetResourceLib.SearchHandler.refreshSearchWidgetThroughSelect(this, true); return false;" title="Search">'
						+ '<i class="bi bi-search icon icon-search overflow-visible"></i>'
					+ '</button>'
					+ '<button class="btn btn-sm btn-outline-secondary text-nowrap" onClick="MyWidgetResourceLib.SearchHandler.resetSearchWidgetThroughSelect(this, true); return false;" title="Reset">'
						+ '<i class="bi bi-x icon icon-reset overflow-visible"></i>'
					+ '</button>'
				//+ '</div>'
			+ '</div>';
	}
	
	function getWidgetSearchUserChoiceHtml(elm, widget, names_options_html) {
		if (!names_options_html) {
			var db_attributes = getWidgetGroupDBAttributes(widget);
			
			if (db_attributes && !$.isEmptyObject(db_attributes)) {
				names_options_html = "";
				
				$.each(db_attributes, function(attr_name, attr) {
					names_options_html += '<option value="' + attr_name + '" title="' + getLabel(attr_name) + '">' + getLabel(attr_name) + '</option>';
				});
			}
		}
		
		//If user choice based in attributes
		return '<div data-widget-search-multiple class="text-right text-end">'
			+ '	<div class="input-group">'
			+ '		<select class="form-control custom-select form-select">'
			+ 			names_options_html
			+ '		</select>'
			//+ '		<div class="input-group-append">' //new bootstrap doesn't need this anymore
			+ '			<button class="btn btn-sm btn-success font-weight-bold h1 mb-0" onClick="MyWidgetResourceLib.SearchHandler.addWidgetSearchDynamicAttribute(this, 1); return false;">+</button>'
			//+ '		</div>'
			+ '	</div>'
			+ '	<ul class="list-group mt-2 mb-2 text-left text-start" data-widget-search-added-attrs>'
			+ '		<li class="list-group-item text-muted border-0 small" data-widget-search-added-attrs-empty>No added attributes to search yet...</li>'
			+ '		<li class="list-group-item border-0" data-widget-search-added-attrs-item>'
			+ '			<div class="input-group">'
			+ '				<label class="input-group-text">#widget_search_attr_name#: </label>'
			+ '				<input class="form-control" data-widget-search-multiple-field onKeyUp="MyWidgetResourceLib.SearchHandler.onKeyUpSearchWidgetThroughMultipleField(this, #widget_search_secs_to_wait#)" onBlur="MyWidgetResourceLib.SearchHandler.refreshSearchWidgetThroughMultipleField(this)" data-widget-props="{&quot;search_attrs&quot;:&quot;#widget_search_attr_name#&quot;}" />'
			+ '				<button class="btn btn-sm btn-outline-danger text-nowrap" onClick="MyWidgetResourceLib.SearchHandler.removeWidgetSearchDynamicAttribute(this)" title="Remove"><i class="bi bi-trash icon icon-remove mr-1 me-1 overflow-visible"></i>Remove</button>'
			+ '			</div>'
			+ '		</li>'
			+ '	</ul>'
			+ '	<button class="btn btn-sm btn-outline-secondary text-nowrap m-1" onClick="MyWidgetResourceLib.SearchHandler.refreshSearchWidget(this, true); return false;" data-widget-search-multiple-button data-widget-props="{&quot;search_operator&quot;:&quot;or&quot;}" title="Search"><i class="bi bi-search icon icon-search mr-1 me-1 overflow-visible"></i>Search</button>'
			+ '	<button class="btn btn-sm btn-outline-secondary text-nowrap m-1" onClick="MyWidgetResourceLib.SearchHandler.resetSearchWidgetThroughMultipleField(this, true); return false;" title="Reset"><i class="bi bi-x-circle icon icon-reset mr-1 me-1 overflow-visible"></i>Reset</button>'
			+ '</div>';
	}
	
	function getWidgetShortActionsHtml(elm, widget) {
		//prepare id
		var main_widget_suffix_id = me.getWidgetSuffix(widget);
		var widget_prefix_id = "widget_short_actions_" + (main_widget_suffix_id ? main_widget_suffix_id + "_" : "");
		var widget_suffix_id = me.getNewWidgetIdCount(widget_prefix_id);
		var widget_id = widget_prefix_id + widget_suffix_id;
		
		//prepare properties_settings 
		var properties_settings = getWidgetSettingsData(elm, ".widget-properties");
		properties_settings = properties_settings["widget_properties"];
		
		//prepare dependent_widgets_id
		var dependent_widgets_id = getWidgetGroupDependentWidgetsId(widget);
		
		//prepare some html vars
		var refresh_props = {
			dependent_widgets_id: dependent_widgets_id
		};
		var refresh_props_html = ' data-widget-props="' + JSON.stringify(refresh_props).replace(/"/g, "&quot;") + '"';
		
		//prepare html
		var html = '<div id="' + widget_id + '" class="mb-4 btn-group text-center mw-100" data-widget-short-actions style="overflow:auto;">'
				+ getWidgetShortActionsRemoveButtonHtml(elm, widget, properties_settings, dependent_widgets_id)
				+ getWidgetShortActionsSaveButtonHtml(elm, widget, properties_settings, dependent_widgets_id)
				+ getWidgetShortActionsAddButtonHtml(elm, widget, properties_settings, dependent_widgets_id)
				+ getWidgetShortActionsToggleBetweenWidgetsButtonHtml(elm, widget, properties_settings, dependent_widgets_id)
				+ getWidgetShortActionsResetSortingButtonHtml(elm, widget, properties_settings, dependent_widgets_id)
				+ getWidgetShortActionsToggleListAttributeSelectCheckboxesButtonHtml(elm, widget, properties_settings, dependent_widgets_id)
				+ '<button class="btn btn-sm btn-outline-secondary text-nowrap" onClick="MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgets(this); return false;"' + refresh_props_html + ' title="Refresh"><i class="bi bi-arrow-clockwise icon icon-refresh mr-1 me-1 overflow-visible"></i>Refresh</button>';
		
		html += '</div>';
		
		return html;
	}
	
	function getWidgetShortActionsAddButtonHtml(elm, widget, properties_settings, dependent_widgets_id) {
		if (properties_settings) {
			properties_settings = getWidgetSettingsData(elm, ".widget-properties");
			properties_settings = properties_settings["widget_properties"];
		}
		
		var add = properties_settings["addable"] ? properties_settings["addable"]["active"] : false;
		
		if (add) {
			var add_type = properties_settings["addable"]["type"];
			var add_permissions = properties_settings["addable"]["permissions"];
			var add_permissions_html = add_permissions ? ' data-widget-permissions="' + JSON.stringify(add_permissions).replace(/"/g, "&quot;") + '"' : "";
			
			if (add_type == "inline") {
				//prepare dependent_widgets_id
				if (!dependent_widgets_id)
					dependent_widgets_id = getWidgetGroupDependentWidgetsId(widget);
				
				//prepare some html vars
				var short_action_props = {
					dependent_widgets_id: dependent_widgets_id
				};
				var short_action_props_html = ' data-widget-props="' + JSON.stringify(short_action_props).replace(/"/g, "&quot;") + '"';
				
				return '<button class="btn btn-sm btn-outline-success text-nowrap" onClick="MyWidgetResourceLib.ShortActionHandler.addInlineResourceListItemToDependentWidgets(this); return false;" data-widget-button-add' + short_action_props_html + add_permissions_html + ' title="Add"><i class="bi bi-plus-lg icon icon-add mr-1 me-1 overflow-visible"></i>Add</button>';
			}
			else {
				var popup_add = widget.find("[data-widget-popup][data-widget-popup-add]");
				
				if (!popup_add[0]) {
					var popup_html = getWidgetPopupAddHtml(elm, widget);
					popup_add = $(popup_html);
					
					widget.append(popup_add);
					
					//convert html to LayoutUIEditor widgets
					convertHtmlElementToLayoutUIEditorWidget(popup_add);
				}
				
				var popup_id = popup_add.attr("id");
				
				return '<button class="btn btn-sm btn-outline-success text-nowrap" onClick="MyWidgetResourceLib.PopupHandler.openButtonAddPopup(this); return false;" data-widget-button-add data-widget-popup-id="' + popup_id + '"' + add_permissions_html + ' title="Add"><i class="bi bi-plus-lg icon icon-add mr-1 me-1 overflow-visible"></i>Add</button>';
			}
		}
		
		return '';
	}
	
	function getWidgetShortActionsToggleBetweenWidgetsButtonHtml(elm, widget, properties_settings, dependent_widgets_id) {
		if (properties_settings) {
			properties_settings = getWidgetSettingsData(elm, ".widget-properties");
			properties_settings = properties_settings["widget_properties"];
		}
		
		var toggle_between_widgets_button = properties_settings["list_type"] == "both";
		
		if (toggle_between_widgets_button)	{	
			//prepare dependent_widgets_id
			if (!dependent_widgets_id)
				dependent_widgets_id = getWidgetGroupDependentWidgetsId(widget);
			
			//prepare some html vars
			var short_action_props = {
				dependent_widgets_id: dependent_widgets_id
			};
			var short_action_props_html = ' data-widget-props="' + JSON.stringify(short_action_props).replace(/"/g, "&quot;") + '"';
			
			return '<button class="btn btn-sm btn-outline-secondary text-nowrap" onClick="MyWidgetResourceLib.ShortActionHandler.toggleWidgetListTableAndTree(this); return false;" data-widget-button-toggle-between-widgets' + short_action_props_html + ' title="Toggle table to/from tree"><i class="bi bi-back icon icon-toggle mr-1 me-1 overflow-visible"></i>Toggle table to/from tree</button>';
		}
		
		return '';
	}
	
	function getWidgetShortActionsToggleListAttributeSelectCheckboxesButtonHtml(elm, widget, properties_settings, dependent_widgets_id) {
		if (properties_settings) {
			properties_settings = getWidgetSettingsData(elm, ".widget-properties");
			properties_settings = properties_settings["widget_properties"];
		}
		
		var active = false;
		
		if (!$.isPlainObject(properties_settings["multiple_items_actions"]) && properties_settings["multiple_items_actions"]) //from data-widget-list
			active = true;
		else if ($.isPlainObject(properties_settings["multiple_items_actions"]) && properties_settings["multiple_items_actions"]["type"]) //from data-widget-group-list
			active = typeof properties_settings["multiple_items_actions"]["type"] == "string" && properties_settings["multiple_items_actions"]["type"].length > 0;
		
		if (active) {
			//prepare dependent_widgets_id
			if (!dependent_widgets_id)
				dependent_widgets_id = getWidgetGroupDependentWidgetsId(widget);
			
			//prepare some html vars
			var short_action_props = {
				dependent_widgets_id: dependent_widgets_id
			};
			var short_action_props_html = ' data-widget-props="' + JSON.stringify(short_action_props).replace(/"/g, "&quot;") + '"';
			
			return '<button class="btn btn-sm btn-outline-secondary text-nowrap" onClick="MyWidgetResourceLib.ShortActionHandler.toggleWidgetListAttributeSelectCheckboxes(this); return false;"' + short_action_props_html + ' data-widget-button-toggle-list-attribute-select-checkboxes title="Select All"><i class="bi bi-check2-square icon icon-reset mr-1 me-1 overflow-visible"></i>Select All</button>';
		}
		
		return '';
	}
	
	function getWidgetShortActionsResetSortingButtonHtml(elm, widget, properties_settings, dependent_widgets_id) {
		if (properties_settings) {
			properties_settings = getWidgetSettingsData(elm, ".widget-properties");
			properties_settings = properties_settings["widget_properties"];
		}
		
		var is_table = properties_settings["list_type"] == "table" || properties_settings["list_type"] == "both";
		
		if (is_table)	{	
			//prepare dependent_widgets_id
			if (!dependent_widgets_id)
				dependent_widgets_id = getWidgetGroupDependentWidgetsId(widget);
			
			//prepare some html vars
			var short_action_props = {
				dependent_widgets_id: dependent_widgets_id
			};
			var short_action_props_html = ' data-widget-props="' + JSON.stringify(short_action_props).replace(/"/g, "&quot;") + '"';
			
			return '<button class="btn btn-sm btn-outline-secondary text-nowrap" onClick="MyWidgetResourceLib.ShortActionHandler.resetWidgetListResourceSort(this); return false;"' + short_action_props_html + ' data-widget-button-reset-sorting title="Reset Sorting"><i class="bi bi-x-circle icon icon-reset mr-1 me-1 overflow-visible"></i>Reset Sorting</button>';
		}
		
		return '';
	}
	
	function getWidgetShortActionsRemoveButtonHtml(elm, widget, properties_settings, dependent_widgets_id) {
		if (properties_settings) {
			properties_settings = getWidgetSettingsData(elm, ".widget-properties");
			properties_settings = properties_settings["widget_properties"];
		}
		
		var active = false;
		
		if (!$.isPlainObject(properties_settings["multiple_items_actions"]) && properties_settings["multiple_items_actions"]) //from data-widget-list
			active = true;
		else if ($.isPlainObject(properties_settings["multiple_items_actions"]) && properties_settings["multiple_items_actions"]["type"]) //from data-widget-group-list
			active = properties_settings["multiple_items_actions"]["type"] == "remove" || properties_settings["multiple_items_actions"]["type"] == "both";
		
		if (active) {
			//prepare dependent_widgets_id
			var dependent_widgets_id = getWidgetGroupDependentWidgetsId(widget, true); //Force to get new dependent_widgets_id, without including the pagination widgets.
			
			//prepare some html vars
			var short_action_props = {
				empty_message: "Please select some records first...", 
				dependent_widgets_id: dependent_widgets_id
			};
			var short_action_props_html = ' data-widget-props="' + JSON.stringify(short_action_props).replace(/"/g, "&quot;") + '"';
			
			//preparing permissions for multiple remove button.
			var multiple_items_actions_permissions = null;
			var multiple_items_actions_permissions_html = "";
			
			if ($.isPlainObject(properties_settings["multiple_items_actions"])) {
				multiple_items_actions_permissions = properties_settings["multiple_items_actions"]["permissions"];
				multiple_items_actions_permissions_html = multiple_items_actions_permissions ? ' data-widget-permissions="' + JSON.stringify(multiple_items_actions_permissions).replace(/"/g, "&quot;") + '"' : "";
			}
			
			//preparing resources
			var multiple_remove_resource = getWidgetResourcesTypeDefaultProperties("remove", "multiple_delete");
			multiple_remove_resource["name"] = addWidgetResourceByType(widget, "remove", "multiple_delete", multiple_items_actions_permissions, null, true);
			
			var multiple_remove_resource_html = ' data-widget-resources="' + JSON.stringify(multiple_remove_resource).replace(/"/g, "&quot;") + '"';
			
			return '<button class="btn btn-sm btn-outline-danger text-nowrap" onClick="MyWidgetResourceLib.ShortActionHandler.executeResourceMultipleRemoveAction(this); return false;" data-widget-button-multiple-remove' + short_action_props_html + multiple_remove_resource_html + multiple_items_actions_permissions_html + ' title="Remove"><i class="bi bi-trash icon icon-remove mr-1 me-1 overflow-visible"></i>Remove</button>';
		}
		
		return '';
	}
	
	function getWidgetShortActionsSaveButtonHtml(elm, widget, properties_settings, dependent_widgets_id) {
		if (properties_settings) {
			properties_settings = getWidgetSettingsData(elm, ".widget-properties");
			properties_settings = properties_settings["widget_properties"];
		}
		
		var active = false;
		
		if (!$.isPlainObject(properties_settings["multiple_items_actions"]) && properties_settings["multiple_items_actions"]) //from data-widget-list
			active = true;
		else if ($.isPlainObject(properties_settings["multiple_items_actions"]) && properties_settings["multiple_items_actions"]["type"]) //from data-widget-group-list
			active = properties_settings["multiple_items_actions"]["type"] == "save" || properties_settings["multiple_items_actions"]["type"] == "both";
		
		if (active) {
			//prepare dependent_widgets_id
			if (!dependent_widgets_id)
				dependent_widgets_id = getWidgetGroupDependentWidgetsId(widget);
			
			//prepare some html vars
			var short_action_props = {
				empty_message: "Please select some records first...", 
				dependent_widgets_id: dependent_widgets_id
			};
			var short_action_props_html = ' data-widget-props="' + JSON.stringify(short_action_props).replace(/"/g, "&quot;") + '"';
			
			//preparing permissions for multiple save button.
			var multiple_items_actions_permissions = null;
			var multiple_items_actions_permissions_html = "";
			
			if ($.isPlainObject(properties_settings["multiple_items_actions"])) {
				multiple_items_actions_permissions = properties_settings["multiple_items_actions"]["permissions"];
				multiple_items_actions_permissions_html = multiple_items_actions_permissions ? ' data-widget-permissions="' + JSON.stringify(multiple_items_actions_permissions).replace(/"/g, "&quot;") + '"' : "";
			}
			
			//preparing resources
			var multiple_save_resource = getWidgetResourcesTypeDefaultProperties("save", "multiple_save");
			multiple_save_resource["name"] = addWidgetResourceByType(widget, "save", "multiple_save", multiple_items_actions_permissions, null, true);
			
			var multiple_save_resource_html = ' data-widget-resources="' + JSON.stringify(multiple_save_resource).replace(/"/g, "&quot;") + '"';
			
			return '<button class="btn btn-sm btn-outline-primary text-nowrap" onClick="MyWidgetResourceLib.ShortActionHandler.executeResourceMultipleSaveAction(this); return false;" data-widget-button-multiple-save' + short_action_props_html + multiple_save_resource_html + multiple_items_actions_permissions_html + ' title="Save"><i class="bi bi-save icon icon-save mr-1 me-1 overflow-visible"></i>Save</button>';
		}
		
		return '';
	}
	
	function getWidgetPaginationHtml(elm, widget, is_top_pagination) {
		//prepare id
		var main_widget_suffix_id = me.getWidgetSuffix(widget);
		var widget_prefix_id = "widget_" + (is_top_pagination ? "top" : "bottom") + "_pagination_" + (main_widget_suffix_id ? main_widget_suffix_id + "_" : "");
		var widget_suffix_id = me.getNewWidgetIdCount(widget_prefix_id);
		var widget_id = widget_prefix_id + widget_suffix_id;
		
		var properties_settings = getWidgetSettingsData(elm, ".widget-properties");
		properties_settings = properties_settings["widget_properties"];
		
		var view_active = properties_settings["viewable"] ? properties_settings["viewable"]["active"] : false;
		var view_permissions = view_active ? properties_settings["viewable"]["permissions"] : false;
		
		var get_total_resource = addWidgetResourceByType(widget, "load", "count", view_permissions, null, true);
		var get_total_resource_html = ' data-widget-resources="' + get_total_resource + '"'; //json object with resource properties that will return the count of the items of some table
		
		//prepare dependent_widgets_id
		var dependent_widgets_id = getWidgetGroupDependentWidgetsId(widget, true);
		
		var pagination_props = {
			number_of_pages_to_show_at_once: 10,
			load: "MyWidgetResourceLib.PaginationHandler.loadPaginationResource",
			dependent_widgets_id: dependent_widgets_id,
		};
		var pagination_props_html = ' data-widget-props="' + JSON.stringify(pagination_props).replace(/"/g, "&quot;") + '"';
		
		var html = '<div id="' + widget_id + '" class="btn-toolbar justify-content-between text-left text-start mb-3" data-widget-pagination' + pagination_props_html + get_total_resource_html + ' data-widget-resources-load>'
				+ getWidgetPaginationGoToPageHtml()
				+ getWidgetPaginationPagesHtml()
				+ '</div>';
		
		return html;
	}
	
	function getWidgetPaginationGoToPageHtml() {
		return '	<div class="input-group input-group-sm col-6 col-sm-4 col-md-3 col-lg-3 col-xl-1 align-items-start p-0" data-widget-pagination-go-to-page style="min-width:150px;">'
			+ '		<select class="form-control custom-select form-select border-secondary text-muted" data-widget-pagination-go-to-page-dropdown onChange="MyWidgetResourceLib.PaginationHandler.goToDropdownPage(this); return false;"></select>'
			//+ '		<div class="input-group-append">' //new bootstrap doesn't need this anymore
			+ '			<button class="btn btn-sm btn-outline-secondary text-nowrap" href="javascript:void(0)" onClick="MyWidgetResourceLib.PaginationHandler.goToDropdownPage(this, true); return false;" title="Refresh"><i class="bi bi-arrow-clockwise icon icon-refresh mr-1 me-1 overflow-visible"></i>Refresh</button>'
			//+ '		</div>'
			+ '	</div>';
	}
		
	function getWidgetPaginationPagesHtml() {
		return '	<div class="btn-group justify-content-end align-items-start" data-widget-pagination-pages role="group">'
			+ getWidgetPaginationPagesInnerHtml()
			+ '	</div>';
	}
	
	function getWidgetPaginationPagesInnerHtml() {
		return '		<a class="btn btn-sm btn-outline-secondary" data-widget-pagination-pages-first href="javascript:void(0)" onClick="MyWidgetResourceLib.PaginationHandler.goToFirstPage(this); return false;">First</a>'
			+ '		<a class="btn btn-sm btn-outline-secondary" data-widget-pagination-pages-previous href="javascript:void(0)" onClick="MyWidgetResourceLib.PaginationHandler.goToPreviousPage(this); return false;">Previous</a>'
			
			+ '		<div data-widget-pagination-pages-numbers class="btn-group" role="group">'
			+ '			<a class="btn btn-sm btn-outline-secondary" data-widget-pagination-pages-numbers-item href="javascript:void(0)" onClick="MyWidgetResourceLib.PaginationHandler.goToElementPage(this); return false;"><span data-widget-pagination-pages-numbers-item-value>dummy page #</span></a>'
			+ '		</div>'
			
			+ '		<a class="btn btn-sm btn-outline-secondary" data-widget-pagination-pages-next href="javascript:void(0)" onClick="MyWidgetResourceLib.PaginationHandler.goToNextPage(this); return false;">Next</a>'
			+ '		<a class="btn btn-sm btn-outline-secondary" data-widget-pagination-pages-last href="javascript:void(0)" onClick="MyWidgetResourceLib.PaginationHandler.goToLastPage(this); return false;">Last</a>';
	}
	
	function getWidgetListHtml(elm, widget, type, db_attributes, properties_settings) {
		if (!properties_settings) {
			properties_settings = getWidgetSettingsData(elm, ".widget-properties");
			properties_settings = properties_settings["widget_properties"];
		}
		
		if (!db_attributes || $.isEmptyObject(db_attributes))
			db_attributes = getWidgetGroupDBAttributes(widget);
		
		if (db_attributes) {
			ignore_main_widget_error = true;
			
			//prepare id
			var widget_id = me.getNewMainWidgetId(widget, "widget_list_");
			var widget_caption_id = widget_id.replace("widget_list_", "widget_list_caption_");
			
			temp_main_widget_id = widget_id;
			
			//prepare pks_attrs_names
			var pks_attrs_names = getDBAttributesPKsAttrsNames(db_attributes);
			
			//prepare resources
			var add_active = properties_settings["addable"] ? properties_settings["addable"]["active"] : false;
			var add_type = add_active ? properties_settings["addable"]["type"] : false;
			var add_permissions = add_active ? properties_settings["addable"]["permissions"] : false;
			
			var view_active = properties_settings["viewable"] ? properties_settings["viewable"]["active"] : false;
			var view_permissions = view_active ? properties_settings["viewable"]["permissions"] : false;
			
			var edit_active = properties_settings["editable"] ? properties_settings["editable"]["active"] : false;
			var edit_type = edit_active ? properties_settings["editable"]["type"] : false;
			var edit_with_auto_save = edit_type == "inline" ? properties_settings["editable"]["with_auto_save"] : false;
			var edit_with_save_button = edit_type == "inline" ? properties_settings["editable"]["with_save_button"] : false;
			var edit_with_edit_button = edit_active ? properties_settings["editable"]["with_edit_button"] : false;
			var edit_toggle_edit_view_fields_button = edit_type == "inline" ? properties_settings["editable"]["toggle_edit_view_fields_button"] : false;
			var edit_permissions = edit_active ? properties_settings["editable"]["permissions"] : false;
			
			var remove_active = properties_settings["removable"] ? properties_settings["removable"]["active"] : false;
			var remove_permissions = remove_active ? properties_settings["removable"]["permissions"] : false;
			
			//prepare properties
			var list_props = {
				empty_message: properties_settings["empty_message"],
				loading_message: properties_settings["loading_message"],
				items_limit_per_page: properties_settings.hasOwnProperty("items_limit_per_page") ? properties_settings["items_limit_per_page"] : 50,
				starting_page_number: properties_settings["starting_page_number"],
				pks_attrs_names: pks_attrs_names,
				load: "MyWidgetResourceLib.ListHandler.loadListTableAndTreeResource",
				dependent_widgets_id: widget_caption_id,
				complete: {}
			};
			var caption_props = {
				load: "MyWidgetResourceLib.ListHandler.loadListCaptionResource"
			};
			
			if (add_type == "inline")
				list_props["complete"]["add"] = "MyWidgetResourceLib.ListHandler.onAddResourceItem"; //add complete[add] callback
			
			if (edit_with_auto_save)
				list_props["complete"]["update_attribute"] = edit_toggle_edit_view_fields_button ? "MyWidgetResourceLib.ListHandler.onUpdateResourceItemAttribute" : "MyWidgetResourceLib.ListHandler.purgeCachedLoadParentListResource"; //add complete[update_attribute] callback
			
			if (edit_with_save_button && edit_toggle_edit_view_fields_button)
				list_props["complete"]["update"] = "MyWidgetResourceLib.ListHandler.onUpdateResourceItem"; //add complete[update] callback
			
			if (remove_active)
				list_props["complete"]["remove"] = "MyWidgetResourceLib.ListHandler.onRemoveResourceItem"; //add complete[remove] callback
			
			var list_props_html = ' data-widget-props="' + JSON.stringify(list_props).replace(/"/g, "&quot;") + '"';
			var caption_props_html = ' data-widget-props="' + JSON.stringify(caption_props).replace(/"/g, "&quot;") + '"';
			
			//prepare resources
			var list_resource = {
				load: addWidgetResourceByType(widget, "load", "get_all", view_permissions, null, true),
			};
			var caption_resource = {
				load: addWidgetResourceByType(widget, "load", "count", view_permissions, null, true),
			};
			
			if (add_type == "inline") {
				var add_props = getWidgetResourcesTypeDefaultProperties("add", "insert");
				add_props["name"] = addWidgetResourceByType(widget, "add", "insert", add_permissions, null, true);
				list_resource["add"] = add_props;
			}
			
			if (edit_with_save_button) {
				var update_props = getWidgetResourcesTypeDefaultProperties("update", "update");
				update_props["name"] = addWidgetResourceByType(widget, "update", "update", edit_permissions, null, true);
				list_resource["update"] = update_props;
			}
			
			if (edit_with_auto_save) {
				var update_props = getWidgetResourcesTypeDefaultProperties("update_attribute", "update_attribute");
				update_props["name"] = addWidgetResourceByType(widget, "update_attribute", "update_attribute", edit_permissions, null, true);
				list_resource["update_attribute"] = update_props;
			}
			
			if (remove_active) {
				var remove_props = getWidgetResourcesTypeDefaultProperties("remove", "delete");
				remove_props["name"] = addWidgetResourceByType(widget, "remove", "delete", remove_permissions, null, true);
				list_resource["remove"] = remove_props;
			}
			
			var list_resource_html = ' data-widget-resources="' + JSON.stringify(list_resource).replace(/"/g, "&quot;") + '"'; //json object with resource properties that will return the count of the items of some table
			var caption_resource_html = ' data-widget-resources="' + JSON.stringify(caption_resource).replace(/"/g, "&quot;") + '"'; //json object with resource properties that will return the count of the items of some table
			
			//prepare html
			var html = '<div class="card mb-3 text-left text-start" id="' + widget_id + '" data-widget-list' + list_props_html + list_resource_html + ' data-widget-resources-load>'
				+ '<div class="card-body">'
					+ '<div class="list-responsive table-responsive">';
			
			if (type == "table" || type == "both")
				html += getWidgetListTableHtml(elm, widget, db_attributes, properties_settings);
			
			if (type == "tree" || type == "both")
				html += getWidgetListTreeHtml(elm, widget, db_attributes, properties_settings);
			
			html += 	'</div>'
						+ '<div class="text-muted small mt-3" id="' + widget_caption_id + '" data-widget-list-caption ' + caption_props_html + caption_resource_html + '>'
							+ '<div data-widget-list-caption-info></div>'
						+ '</div>' //Do not add data-widget-resources-load bc this is dependent from the widget-list
					+ '</div>'
				+ '</div>';
			
			ignore_main_widget_error = false;
			temp_main_widget_id = null;
			
			return html;
		}
		
		return null;
	}
	
	function getWidgetListTableHtml(elm, widget, db_attributes, properties_settings) {
		if (!properties_settings) {
			properties_settings = getWidgetSettingsData(elm, ".widget-properties");
			properties_settings = properties_settings["widget_properties"];
		}
		
		if (!db_attributes || $.isEmptyObject(db_attributes))
			db_attributes = getWidgetGroupDBAttributes(widget);
		
		if (db_attributes) {
			//only include attributes that are not internal attributes like: created_date or modified_date, modified_by created_by, created_user_id, modified_user_id...
			db_attributes = purgeInternalAttributesFromWidgetDBAttributes(db_attributes);
			
			var add_active = properties_settings["addable"] ? properties_settings["addable"]["active"] : false;
			var add_type = add_active ? properties_settings["addable"]["type"] : false;
			var add_permissions = add_active ? properties_settings["addable"]["permissions"] : false;
			var add_permissions_html = add_permissions ? ' data-widget-permissions="' + JSON.stringify(add_permissions).replace(/"/g, "&quot;") + '"' : '';
			
			var view_active = properties_settings["viewable"] ? properties_settings["viewable"]["active"] : false;
			var view_type = view_active ? properties_settings["viewable"]["type"] : false;
			var view_with_view_button = view_active ? properties_settings["viewable"]["with_view_button"] : false;
			var view_permissions = view_active ? properties_settings["viewable"]["permissions"] : false;
			var view_permissions_html = view_permissions ? ' data-widget-permissions="' + JSON.stringify(view_permissions).replace(/"/g, "&quot;") + '"' : '';
			
			var edit_active = properties_settings["editable"] ? properties_settings["editable"]["active"] : false;
			var edit_type = edit_active ? properties_settings["editable"]["type"] : false;
			var edit_with_auto_save = edit_type == "inline" ? properties_settings["editable"]["with_auto_save"] : false;
			var edit_with_save_button = edit_type == "inline" ? properties_settings["editable"]["with_save_button"] : false;
			var edit_with_edit_button = edit_active ? properties_settings["editable"]["with_edit_button"] : false;
			var edit_toggle_edit_view_fields_button = edit_type == "inline" ? properties_settings["editable"]["toggle_edit_view_fields_button"] : false;
			var edit_permissions = edit_active ? properties_settings["editable"]["permissions"] : false;
			var edit_permissions_html = edit_permissions ? ' data-widget-permissions="' + JSON.stringify(edit_permissions).replace(/"/g, "&quot;") + '"' : '';
			
			var remove_active = properties_settings["removable"] ? properties_settings["removable"]["active"] : false;
			var remove_permissions = remove_active ? properties_settings["removable"]["permissions"] : false;
			var remove_permissions_html = remove_permissions ? ' data-widget-permissions="' + JSON.stringify(remove_permissions).replace(/"/g, "&quot;") + '"' : '';
			
			var multiple_items_actions = ($.isPlainObject(properties_settings["multiple_items_actions"]) && properties_settings["multiple_items_actions"]["type"]) || (!$.isPlainObject(properties_settings["multiple_items_actions"]) && properties_settings["multiple_items_actions"]); //it could be from: data-widget-group-list or data-widget-list
			var multiple_items_actions_permissions = multiple_items_actions && $.isPlainObject(properties_settings["multiple_items_actions"]) ? properties_settings["multiple_items_actions"]["permissions"] : false;
			var multiple_items_actions_permissions_html = multiple_items_actions_permissions ? ' data-widget-permissions="' + JSON.stringify(multiple_items_actions_permissions).replace(/"/g, "&quot;") + '"' : '';
			
			var num_of_columns = 0;
			
			//prepare table html
			var html = '<table class="table table-sm table-striped table-hover text-left text-start list-table mb-0 small" data-widget-list-table>'
					+ '	<thead>'
					+ '		<tr>';
			
			//prepare thead html
			if (multiple_items_actions) {
				num_of_columns++;
				html += getWidgetListTableSelectItemsHeadHtml(multiple_items_actions_permissions_html);
			}
			
			$.each(db_attributes, function(attr_name, attr) {
				num_of_columns++;
				
				html += getWidgetListTableItemColumnHeadHtml(attr_name);
			});
			
			var has_buttons_column = add_active || edit_type == "inline" || edit_type == "button" || edit_with_edit_button || edit_toggle_edit_view_fields_button || remove_active || view_with_view_button || view_type == "button";
			
			if (has_buttons_column) {
				num_of_columns++;
				
				html += '<th class="border-0 pt-0 text-right text-end text-muted fw-normal align-middle small" data-widget-item-actions-head>'
					+ getWidgetListTableButtonAddHtml(elm, widget, db_attributes, properties_settings, add_active, add_type, add_permissions_html)
					+ '</th>';
			}
			
			html += '</tr>'
				+ '</thead>'
				+ '<tbody>';
			
			//prepare tbody html
			html += '<tr data-widget-item' + (edit_toggle_edit_view_fields_button ? " data-widget-with-button-toggle-inline-edit-view" : "") + '>';
			
			if (multiple_items_actions) //se checkbox to execute the multiple remove and save
				html += getWidgetListTableItemSelectedColumnHtml(multiple_items_actions_permissions_html);
			
			//show attributes
			$.each(db_attributes, function(attr_name, attr) {
				html += getWidgetListTableItemColumnBodyHtml(elm, widget, db_attributes, properties_settings, attr_name, false, null, "", view_active, view_type, view_with_view_button, view_permissions_html, edit_active, edit_type, edit_with_auto_save, edit_with_save_button, edit_with_edit_button, edit_toggle_edit_view_fields_button, edit_permissions_html, remove_active, remove_permissions_html);
			});
			
			//prepare last column with buttons
			if (has_buttons_column)
				html += '<td class="border-0 text-right text-end align-middle" data-widget-item-actions-column>'
						+ '<div class="btn-group justify-content-center align-items-center">'
							+ getWidgetItemActionsHtml(elm, widget, db_attributes, properties_settings, false, null, "", view_active, view_type, view_with_view_button, view_permissions_html, edit_active, edit_type, edit_with_auto_save, edit_with_save_button, edit_with_edit_button, edit_toggle_edit_view_fields_button, edit_permissions_html, remove_active, remove_permissions_html, true)
						+ '</div>'
					+ '</td>';
			
			html += '</tr>';
			
			if (add_type == "inline")
				html += getWidgetListTableItemAddHtml(elm, widget, db_attributes, properties_settings);
			
			//prepare loading html
			html += '<tr data-widget-loading><td class="border-0 text-center text-muted small p-3" colspan="' + num_of_columns + '">Loading new data... Please wait a while...</td></tr>';
			
			//prepare empty html
			html += '<tr data-widget-empty><td class="border-0 text-center text-muted small p-3" colspan="' + num_of_columns + '">'
				+ 'There are no records available.';
			
			if (add_active)
				html += getWidgetListEmptyAddHtml(elm, widget, db_attributes, properties_settings, add_active, add_type, add_permissions_html);
			
			html += '</td></tr>';
			
			html += '</tbody>'
				+ '</table>';
			
			return html;
		}
		
		return null;
	}
	
	function getWidgetListTableItemAddHtml(elm, widget, db_attributes, properties_settings, attributes_to_filter) {
		if (!properties_settings) {
			properties_settings = getWidgetSettingsData(elm, ".widget-properties");
			properties_settings = properties_settings["widget_properties"];
		}
		
		if (!db_attributes || $.isEmptyObject(db_attributes))
			db_attributes = getWidgetGroupDBAttributes(widget);
		
		if (db_attributes) {
			//only include attributes that are not internal attributes like: created_date or modified_date, modified_by created_by, created_user_id, modified_user_id...
			db_attributes = $.isArray(attributes_to_filter) && attributes_to_filter.length > 0 ? db_attributes : purgeInternalAttributesFromWidgetDBAttributes(db_attributes);
			
			var add_active = properties_settings["addable"] ? properties_settings["addable"]["active"] : false;
			var add_type = add_active ? properties_settings["addable"]["type"] : false;
			var add_permissions = add_active ? properties_settings["addable"]["permissions"] : false;
			var add_permissions_html = add_permissions ? ' data-widget-permissions="' + JSON.stringify(add_permissions).replace(/"/g, "&quot;") + '"' : '';
			
			var multiple_items_actions = ($.isPlainObject(properties_settings["multiple_items_actions"]) && properties_settings["multiple_items_actions"]["type"]) || (!$.isPlainObject(properties_settings["multiple_items_actions"]) && properties_settings["multiple_items_actions"]); //it could be from: data-widget-group-list or data-widget-list
			var multiple_items_actions_permissions = multiple_items_actions && $.isPlainObject(properties_settings["multiple_items_actions"]) ? properties_settings["multiple_items_actions"]["permissions"] : false;
			var multiple_items_actions_permissions_html = multiple_items_actions_permissions ? ' data-widget-permissions="' + JSON.stringify(multiple_items_actions_permissions).replace(/"/g, "&quot;") + '"' : '';
			
			//prepare tbody html
			var html = '<tr data-widget-item-add>';
			
			if (multiple_items_actions) //se checkbox to execute the multiple remove and save
				html += getWidgetListTableItemAddSelectedColumnHtml(multiple_items_actions_permissions_html);
			
			//show attributes
			if ($.isArray(attributes_to_filter) && attributes_to_filter.length > 0)
				$.each(attributes_to_filter, function(idx, attr_name) {
					if (db_attributes.hasOwnProperty(attr_name))
						html += getWidgetListTableItemColumnBodyHtml(elm, widget, db_attributes, properties_settings, attr_name, add_active, add_type, add_permissions_html);
				});
			else
				$.each(db_attributes, function(attr_name, attr) {
					html += getWidgetListTableItemColumnBodyHtml(elm, widget, db_attributes, properties_settings, attr_name, add_active, add_type, add_permissions_html);
				});
			
			//prepare last column with buttons
			html += '<td class="border-0 text-right text-end align-middle" data-widget-item-actions-column>'
					+ '<div class="btn-group justify-content-center align-items-center">'
						+ getWidgetItemActionsHtml(elm, widget, db_attributes, properties_settings, add_active, add_type, add_permissions_html, true)
					+ '</div>'
				+ '</td>';
			
			html += '</tr>';
			
			return html;
		}
		
		return null;
	}
	
	function getWidgetListTableItemColumnHeadHtml(attr_name) {
		return '<th class="border-0 pt-0 text-muted fw-normal align-middle small text-nowrap" data-widget-item-head onClick="MyWidgetResourceLib.ListHandler.sortListResource(this, event); return false;" data-widget-item-attribute-name="' + attr_name + '">' 
			+ getLabel(attr_name)
			+ '<i class="bi bi-filter-left ml-1 ms-1 overflow-visible icon icon-sort text-center"></i>'
			+ '<i class="bi bi-sort-down-alt ml-1 ms-1 overflow-visible icon icon-sort-asc text-center"></i>'
			+ '<i class="bi bi-sort-up ml-1 ms-1 overflow-visible icon icon-sort-desc text-center"></i>'
			+ '<i class="bi bi-x-circle-fill ml-1 ms-1 overflow-visible icon icon-sort-reset text-center" onClick="MyWidgetResourceLib.ListHandler.resetListResourceSortAttribute(this, event); return false;"></i>'
		+ '</th>';
	}
	
	function getWidgetListTableSelectItemsHeadHtml(multiple_items_actions_permissions_html) {
		return '<th class="border-0 pt-0 text-center text-muted fw-normal align-middle small" data-widget-list-select-items-head' + multiple_items_actions_permissions_html + '>'
			+ '	<input type="checkbox" onClick="MyWidgetResourceLib.ListHandler.toggleListAttributeSelectCheckboxes(this); return true;" data-widget-list-select-items-checkbox />' //must return true otherwise the checbox will never be checked.
			+ '</th>';
	}
	
	function getWidgetListTableItemSelectedColumnHtml(multiple_items_actions_permissions_html) {
		return '<td class="border-0 text-center align-middle" data-widget-item-selected-column' + multiple_items_actions_permissions_html + '>'
			+ '	<input type="checkbox" data-widget-item-selected-checkbox />'
			+ '</td>';
	}
	
	function getWidgetListTableItemAddSelectedColumnHtml(multiple_items_actions_permissions_html) {
		return '<td class="border-0 text-center align-middle" data-widget-item-selected-column' + multiple_items_actions_permissions_html + '></td>';
	}
	
	function getWidgetListTableButtonAddHtml(elm, widget, db_attributes, properties_settings, add_active, add_type, add_permissions_html) {
		if (add_active) {
			if (add_type == "inline")
				return '<button class="btn btn-sm btn-success text-nowrap m-1" onClick="MyWidgetResourceLib.ListHandler.addInlineResourceListItem(this); return false;" data-widget-button-add' + add_permissions_html + ' title="Add"><i class="bi bi-plus-lg icon icon-add mr-1 me-1 overflow-visible"></i>Add new Record</a>';
			else {
				var popup_add = widget.find("[data-widget-popup][data-widget-popup-add]");
				
				if (!popup_add[0]) {
					var popup_html = getWidgetPopupAddHtml(elm, widget, db_attributes, properties_settings);
					popup_add = $(popup_html);
					widget.append(popup_add);
					
					//convert html to LayoutUIEditor widgets
					convertHtmlElementToLayoutUIEditorWidget(popup_add);
				}
				
				var popup_id = popup_add.attr("id");
						
				return '<button class="btn btn-sm btn-success text-nowrap m-1" onClick="MyWidgetResourceLib.PopupHandler.openButtonAddPopup(this); return false;" data-widget-button-add data-widget-popup-id="' + popup_id + '"' + add_permissions_html + ' title="Add"><i class="bi bi-plus-lg icon icon-add mr-1 me-1 overflow-visible"></i>Add new Record</button>';
			}
		}
		
		return "";
	}
	
	function getWidgetListTableItemColumnBodyHtml(elm, widget, db_attributes, properties_settings, attr_name, add_active, add_type, add_permissions_html, view_active, view_type, view_with_view_button, view_permissions_html, edit_active, edit_type, edit_with_auto_save, edit_with_save_button, edit_with_edit_button, edit_toggle_edit_view_fields_button, edit_permissions_html, remove_active, remove_permissions_html) {
				
		return '<td class="border-0 align-middle" data-widget-item-column data-widget-item-attribute-name="' + attr_name + '">'
			+ getWidgetItemAttributeHtml(elm, widget, db_attributes, properties_settings, attr_name, add_active, add_type, add_permissions_html, view_active, view_type, view_with_view_button, view_permissions_html, edit_active, edit_type, edit_with_auto_save, edit_with_save_button, edit_with_edit_button, edit_toggle_edit_view_fields_button, edit_permissions_html, remove_active, remove_permissions_html)
			+ '</td>';
	}
	
	function getWidgetListTreeHtml(elm, widget, db_attributes, properties_settings) {
		if (!properties_settings) {
			properties_settings = getWidgetSettingsData(elm, ".widget-properties");
			properties_settings = properties_settings["widget_properties"];
		}
		
		if (!db_attributes || $.isEmptyObject(db_attributes))
			db_attributes = getWidgetGroupDBAttributes(widget);
		
		if (db_attributes) {
			//only include attributes that are not internal attributes like: created_date or modified_date, modified_by created_by, created_user_id, modified_user_id...
			db_attributes = purgeInternalAttributesFromWidgetDBAttributes(db_attributes);
			
			var add_active = properties_settings["addable"] ? properties_settings["addable"]["active"] : false;
			var add_type = add_active ? properties_settings["addable"]["type"] : false;
			var add_permissions = add_active ? properties_settings["addable"]["permissions"] : false;
			var add_permissions_html = add_permissions ? ' data-widget-permissions="' + JSON.stringify(add_permissions).replace(/"/g, "&quot;") + '"' : '';
			
			var view_active = properties_settings["viewable"] ? properties_settings["viewable"]["active"] : false;
			var view_type = view_active ? properties_settings["viewable"]["type"] : false;
			var view_with_view_button = view_active ? properties_settings["viewable"]["with_view_button"] : false;
			var view_permissions = view_active ? properties_settings["viewable"]["permissions"] : false;
			var view_permissions_html = view_permissions ? ' data-widget-permissions="' + JSON.stringify(view_permissions).replace(/"/g, "&quot;") + '"' : '';
			
			var edit_active = properties_settings["editable"] ? properties_settings["editable"]["active"] : false;
			var edit_type = edit_active ? properties_settings["editable"]["type"] : false;
			var edit_with_auto_save = edit_type == "inline" ? properties_settings["editable"]["with_auto_save"] : false;
			var edit_with_save_button = edit_type == "inline" ? properties_settings["editable"]["with_save_button"] : false;
			var edit_with_edit_button = edit_active ? properties_settings["editable"]["with_edit_button"] : false;
			var edit_toggle_edit_view_fields_button = edit_type == "inline" ? properties_settings["editable"]["toggle_edit_view_fields_button"] : false;
			var edit_permissions = edit_active ? properties_settings["editable"]["permissions"] : false;
			var edit_permissions_html = edit_permissions ? ' data-widget-permissions="' + JSON.stringify(edit_permissions).replace(/"/g, "&quot;") + '"' : '';
			
			var remove_active = properties_settings["removable"] ? properties_settings["removable"]["active"] : false;
			var remove_permissions = remove_active ? properties_settings["removable"]["permissions"] : false;
			var remove_permissions_html = remove_permissions ? ' data-widget-permissions="' + JSON.stringify(remove_permissions).replace(/"/g, "&quot;") + '"' : '';
			
			var multiple_items_actions = ($.isPlainObject(properties_settings["multiple_items_actions"]) && properties_settings["multiple_items_actions"]["type"]) || (!$.isPlainObject(properties_settings["multiple_items_actions"]) && properties_settings["multiple_items_actions"]); //it could be from: data-widget-group-list or data-widget-list
			var multiple_items_actions_permissions = multiple_items_actions && $.isPlainObject(properties_settings["multiple_items_actions"]) ? properties_settings["multiple_items_actions"]["permissions"] : false;
			var multiple_items_actions_permissions_html = multiple_items_actions_permissions ? ' data-widget-permissions="' + JSON.stringify(multiple_items_actions_permissions).replace(/"/g, "&quot;") + '"' : '';
			
			var has_buttons_column = add_active || edit_type == "inline" || edit_type == "button" || edit_with_edit_button || edit_toggle_edit_view_fields_button || remove_active || view_with_view_button || view_type == "button";
			
			//This widget is the same than the table widget but with different html
			var html = '<ul class="list-group border-0" data-widget-list-tree>'
					+ '	<li class="list-group-item" data-widget-item' + (edit_toggle_edit_view_fields_button ? " data-widget-with-button-toggle-inline-edit-view" : "") + '>';
			
			if (multiple_items_actions) //se checkbox to execute the multiple remove and save
				html += getWidgetListTreeItemSelectedColumnHtml(multiple_items_actions_permissions_html);
			
			//show attributes
			$.each(db_attributes, function(attr_name, attr) {
				html += getWidgetItemColumnHtml(elm, widget, db_attributes, properties_settings, attr_name, false, null, "", view_active, view_type, view_with_view_button, view_permissions_html, edit_active, edit_type, edit_with_auto_save, edit_with_save_button, edit_with_edit_button, edit_toggle_edit_view_fields_button, edit_permissions_html, remove_active, remove_permissions_html);
			});
			
			//prepare last column with buttons
			if (has_buttons_column)
				html += '<div class="text-right text-end mt-4" data-widget-item-actions-column>'
					+ getWidgetItemActionsHtml(elm, widget, db_attributes, properties_settings, false, null, "", view_active, view_type, view_with_view_button, view_permissions_html, edit_active, edit_type, edit_with_auto_save, edit_with_save_button, edit_with_edit_button, edit_toggle_edit_view_fields_button, edit_permissions_html, remove_active, remove_permissions_html)
					+ '</div>';
			
			html += '</li>';
			
			if (add_type == "inline")
				html += getWidgetListTreeItemAddHtml(elm, widget, db_attributes, properties_settings);
			
			//prepare loading html
			html += '<li class="list-group-item" data-widget-loading>Loading new data... Please wait a while...</li>';
			
			//prepare empty html
			html += '<li class="list-group-item" data-widget-empty>'
				+ 'There are no records available.';
			
			if (add_active)
				html += getWidgetListEmptyAddHtml(elm, widget, db_attributes, properties_settings, add_active, add_type, add_permissions_html);
			
			html += '</li>';
			
			html += '</ul>';
			
			return html;
		}
		
		return null;
	}
	
	function getWidgetListTreeItemAddHtml(elm, widget, db_attributes, properties_settings, attributes_to_filter) {
		if (!properties_settings) {
			properties_settings = getWidgetSettingsData(elm, ".widget-properties");
			properties_settings = properties_settings["widget_properties"];
		}
		
		if (!db_attributes || $.isEmptyObject(db_attributes))
			db_attributes = getWidgetGroupDBAttributes(widget);
		
		if (db_attributes) {
			//only include attributes that are not internal attributes like: created_date or modified_date, modified_by created_by, created_user_id, modified_user_id...
			db_attributes = $.isArray(attributes_to_filter) && attributes_to_filter.length > 0 ? db_attributes : purgeInternalAttributesFromWidgetDBAttributes(db_attributes);
			
			var add_active = properties_settings["addable"] ? properties_settings["addable"]["active"] : false;
			var add_type = add_active ? properties_settings["addable"]["type"] : false;
			var add_permissions = add_active ? properties_settings["addable"]["permissions"] : false;
			var add_permissions_html = add_permissions ? ' data-widget-permissions="' + JSON.stringify(add_permissions).replace(/"/g, "&quot;") + '"' : '';
			
			var multiple_items_actions = ($.isPlainObject(properties_settings["multiple_items_actions"]) && properties_settings["multiple_items_actions"]["type"]) || (!$.isPlainObject(properties_settings["multiple_items_actions"]) && properties_settings["multiple_items_actions"]); //it could be from: data-widget-group-list or data-widget-list
			var multiple_items_actions_permissions = multiple_items_actions && $.isPlainObject(properties_settings["multiple_items_actions"]) ? properties_settings["multiple_items_actions"]["permissions"] : false;
			var multiple_items_actions_permissions_html = multiple_items_actions_permissions ? ' data-widget-permissions="' + JSON.stringify(multiple_items_actions_permissions).replace(/"/g, "&quot;") + '"' : '';
			
			var html = '<li class="list-group-item" data-widget-item-add>';
			
			if (multiple_items_actions) //se checkbox to execute the multiple remove and save
				html += getWidgetListTreeItemAddSelectedColumnHtml(multiple_items_actions_permissions_html);
			
			//show attributes
			if ($.isArray(attributes_to_filter) && attributes_to_filter.length > 0)
				$.each(attributes_to_filter, function(idx, attr_name) {
					if (db_attributes.hasOwnProperty(attr_name))
						html += getWidgetItemColumnHtml(elm, widget, db_attributes, properties_settings, attr_name, add_active, add_type, add_permissions_html);
				});
			else
				$.each(db_attributes, function(attr_name, attr) {
					html += getWidgetItemColumnHtml(elm, widget, db_attributes, properties_settings, attr_name, add_active, add_type, add_permissions_html);
				});
			
			//prepare last column with buttons
			html += '<div class="text-right text-end mt-4" data-widget-item-actions-column>'
				+ getWidgetItemActionsHtml(elm, widget, db_attributes, properties_settings, add_active, add_type, add_permissions_html)
				+ '</div>';
			
			html += '</li>';
			
			return html;
		}
		
		return null;
	}
	
	function getWidgetListTreeItemSelectedColumnHtml(multiple_items_actions_permissions_html) {
		return '<div data-widget-item-selected-column' + multiple_items_actions_permissions_html + '>'
			+ '	<input type="checkbox" data-widget-item-selected-checkbox />'
			+ '</div>';
	}
	
	function getWidgetListTreeItemAddSelectedColumnHtml(multiple_items_actions_permissions_html) {
		return '<div data-widget-item-selected-column' + multiple_items_actions_permissions_html + '></div>';
	}
	
	function getWidgetListEmptyAddHtml(elm, widget, db_attributes, properties_settings, add_active, add_type, add_permissions_html) {
		return '<div data-widget-empty-add>'
				+ 'Please click in the button below to add new records:'
				+ '<br/>'
				+ '<br/>'
				+ getWidgetListTableButtonAddHtml(elm, widget, db_attributes, properties_settings, add_active, add_type, add_permissions_html)
				+ '</div>';
	}
	
	function getWidgetFormHtml(elm, widget, db_attributes, properties_settings) {
		if (!properties_settings) {
			properties_settings = getWidgetSettingsData(elm, ".widget-properties");
			properties_settings = properties_settings["widget_properties"];
		}
		
		if (!db_attributes || $.isEmptyObject(db_attributes))
			db_attributes = getWidgetGroupDBAttributes(widget);
		
		if (db_attributes) {
			ignore_main_widget_error = true;
			
			//only include attributes that are not internal attributes like: created_date or modified_date, modified_by created_by, created_user_id, modified_user_id...
			db_attributes = purgeInternalAttributesFromWidgetDBAttributes(db_attributes);
			
			//prepare id
			var widget_id = me.getNewMainWidgetId(widget, "widget_form_");
			temp_main_widget_id = widget_id;
			
			//prepare pks_attrs_names
			var pks_attrs_names = getDBAttributesPKsAttrsNames(db_attributes);
			
			//prepare resources
			var add_active = properties_settings["addable"] ? properties_settings["addable"]["active"] : false;
			var add_type = add_active ? properties_settings["addable"]["type"] : false;
			var add_permissions = add_active ? properties_settings["addable"]["permissions"] : false;
			var add_permissions_html = add_permissions ? ' data-widget-permissions="' + JSON.stringify(add_permissions).replace(/"/g, "&quot;") + '"' : '';
			
			var view_active = properties_settings["viewable"] ? properties_settings["viewable"]["active"] : false;
			var view_type = view_active ? properties_settings["viewable"]["type"] : false;
			var view_with_view_button = view_active ? properties_settings["viewable"]["with_view_button"] : false;
			var view_permissions = view_active ? properties_settings["viewable"]["permissions"] : false;
			var view_permissions_html = view_permissions ? ' data-widget-permissions="' + JSON.stringify(view_permissions).replace(/"/g, "&quot;") + '"' : '';
			
			var edit_active = properties_settings["editable"] ? properties_settings["editable"]["active"] : false;
			var edit_type = edit_active ? properties_settings["editable"]["type"] : false;
			var edit_with_auto_save = edit_type == "inline" ? properties_settings["editable"]["with_auto_save"] : false;
			var edit_with_save_button = edit_type == "inline" ? properties_settings["editable"]["with_save_button"] : false;
			var edit_with_edit_button = edit_active ? properties_settings["editable"]["with_edit_button"] : false;
			var edit_toggle_edit_view_fields_button = false;
			var edit_permissions = edit_active ? properties_settings["editable"]["permissions"] : false;
			var edit_permissions_html = edit_permissions ? ' data-widget-permissions="' + JSON.stringify(edit_permissions).replace(/"/g, "&quot;") + '"' : '';
			
			var remove_active = properties_settings["removable"] ? properties_settings["removable"]["active"] : false;
			var remove_permissions = remove_active ? properties_settings["removable"]["permissions"] : false;
			var remove_permissions_html = remove_permissions ? ' data-widget-permissions="' + JSON.stringify(remove_permissions).replace(/"/g, "&quot;") + '"' : '';
			
			var has_buttons_column = add_active || edit_type == "inline" || edit_type == "button" || edit_with_edit_button || edit_toggle_edit_view_fields_button || remove_active || view_with_view_button || view_type == "button";
			
			//prepare properties
			var form_props = {
				pks_attrs_names: pks_attrs_names,
				load: "MyWidgetResourceLib.FormHandler.loadFormResource",
				complete: {},
				enter_key_press_button: edit_active ? "[data-widget-item-button-edit],[data-widget-item-button-update]" : (
					add_active ? "[data-widget-item-button-add]" : (
						remove_active ? "[data-widget-item-button-remove]" : 
						"#some_id_that_do_not_exists_just_to_disable_the_enter_key_event"
					)
				)
			};
			
			if (add_type == "inline")
				form_props["complete"]["add"] = "MyWidgetResourceLib.FormHandler.onAddResourceItem"; //add complete[add] callback
			
			if (edit_with_auto_save)
				form_props["complete"]["update_attribute"] = edit_toggle_edit_view_fields_button ? "MyWidgetResourceLib.FormHandler.onUpdateResourceItemAttribute" : "MyWidgetResourceLib.FormHandler.purgeCachedLoadParentFormResource"; //add complete[update_attribute] callback
			
			if (edit_with_save_button && edit_toggle_edit_view_fields_button)
				form_props["complete"]["update"] = "MyWidgetResourceLib.FormHandler.onUpdateResourceItem"; //add complete[update] callback
			
			if (remove_active)
				form_props["complete"]["remove"] = "MyWidgetResourceLib.FormHandler.onRemoveResourceItem"; //add complete[remove] callback
			
			var form_props_html = ' data-widget-props="' + JSON.stringify(form_props).replace(/"/g, "&quot;") + '"';
			
			//prepare resources
			var form_resource = {
				load: getWidgetResourcesTypeDefaultProperties("load", "get")
			};
			form_resource["load"] = $.isPlainObject(form_resource["load"]) ? form_resource["load"] : {};
			form_resource["load"]["name"] = addWidgetResourceByType(widget, "load", "get", view_permissions, null, true);
			
			if (add_type == "inline") {
				var add_props = getWidgetResourcesTypeDefaultProperties("add", "insert");
				add_props["name"] = addWidgetResourceByType(widget, "add", "insert", add_permissions, null, true);
				form_resource["add"] = add_props;
			}
			
			if (edit_with_save_button) {
				var update_props = getWidgetResourcesTypeDefaultProperties("update", "update");
				update_props["name"] = addWidgetResourceByType(widget, "update", "update", edit_permissions, null, true);
				form_resource["update"] = update_props;
			}
			
			if (edit_with_auto_save) {
				var update_props = getWidgetResourcesTypeDefaultProperties("update_attribute", "update_attribute");
				update_props["name"] = addWidgetResourceByType(widget, "update_attribute", "update_attribute", edit_permissions, null, true);
				form_resource["update_attribute"] = update_props;
			}
			
			if (remove_active) {
				var remove_props = getWidgetResourcesTypeDefaultProperties("remove", "delete");
				remove_props["name"] = addWidgetResourceByType(widget, "remove", "delete", remove_permissions, null, true);
				form_resource["remove"] = remove_props;
			}
			
			var form_resource_html = ' data-widget-resources="' + JSON.stringify(form_resource).replace(/"/g, "&quot;") + '"'; //json object with resource properties that will return the count of the items of some table
			
			var enctype_html = (add_active || edit_active) && hasWidgetItemBinaryAttribute(db_attributes) ? ' enctype="multipart/form-data"' : "";
			
			//prepare html
			var html = '<form id="' + widget_id + '" class="card text-left text-start mb-3" method="post"' + enctype_html + ' onSubmit="return false;" data-widget-form' + form_props_html + form_resource_html + ' data-widget-resources-load' + (edit_toggle_edit_view_fields_button ? " data-widget-with-button-toggle-inline-edit-view" : "") + '>'
				+ '<div class="card-body">';
			
			//show attributes
			$.each(db_attributes, function(attr_name, attr) {
				html += getWidgetItemColumnHtml(elm, widget, db_attributes, properties_settings, attr_name, add_active, add_type, add_permissions_html, view_active, view_type, view_with_view_button, view_permissions_html, edit_active, edit_type, edit_with_auto_save, edit_with_save_button, edit_with_edit_button, edit_toggle_edit_view_fields_button, edit_permissions_html, remove_active, remove_permissions_html);
			});
			
			//prepare last column with buttons
			if (has_buttons_column)
				html += '<div class="text-right text-end mt-4" data-widget-item-actions-column>'
					+ getWidgetItemActionsHtml(elm, widget, db_attributes, properties_settings, add_active, add_type, add_permissions_html, view_active, view_type, view_with_view_button, view_permissions_html, edit_active, edit_type, edit_with_auto_save, edit_with_save_button, edit_with_edit_button, edit_toggle_edit_view_fields_button, edit_permissions_html, remove_active, remove_permissions_html)
					+ '</div>';
			
			html += '</div>'
				+ '</form>';
			
			ignore_main_widget_error = false;
			temp_main_widget_id = null;
			
			return html;
		}
		
		return null;
	}
	
	function getWidgetItemColumnHtml(elm, widget, db_attributes, properties_settings, attr_name, add_active, add_type, add_permissions_html, view_active, view_type, view_with_view_button, view_permissions_html, edit_active, edit_type, edit_with_auto_save, edit_with_save_button, edit_with_edit_button, edit_toggle_edit_view_fields_button, edit_permissions_html, remove_active, remove_permissions_html) {
		var html = getWidgetItemAttributeHtml(elm, widget, db_attributes, properties_settings, attr_name, add_active, add_type, add_permissions_html, view_active, view_type, view_with_view_button, view_permissions_html, edit_active, edit_type, edit_with_auto_save, edit_with_save_button, edit_with_edit_button, edit_toggle_edit_view_fields_button, edit_permissions_html, remove_active, remove_permissions_html);
		var is_mandatory = html && (html.indexOf('data-allow-null="0"') != -1 || html.indexOf("required") != -1);
		
		//only show if html is not empty. This is very important, otherwise when we show a form with add action, it will show the auto incremented PKs with only labels and without the field.
		return html ? '<div class="mb-3 row" data-widget-item-column data-widget-item-attribute-name="' + attr_name + '">'
				+ '<label class="col-sm-4 col-form-label" data-widget-item-head>' + getLabel(attr_name) + '<span class="label-colon">:</span>' + (is_mandatory ? ' <span class="text-danger label-mandatory">*</span>' : '') + '</label>'
				+ (html ? '<div class="col-sm-8">' + html + '</div>' : '')
				+ '</div>' : '';
	}
	
	function getWidgetItemActionsHtml(elm, widget, db_attributes, properties_settings, add_active, add_type, add_permissions_html, view_active, view_type, view_with_view_button, view_permissions_html, edit_active, edit_type, edit_with_auto_save, edit_with_save_button, edit_with_edit_button, edit_toggle_edit_view_fields_button, edit_permissions_html, remove_active, remove_permissions_html) {
		var html = '';
		
		//prepare insert button if inline add
		if (add_type == "inline")
			html += getWidgetItemActionsButtonAddCancelHtml(elm, widget, db_attributes, properties_settings, add_permissions_html)
				+ getWidgetItemActionsButtonAddHtml(elm, widget, db_attributes, properties_settings, add_permissions_html);
		
		//prepare view button to see the details in a popup
		if (view_with_view_button || view_type == "button")
			html += getWidgetItemActionsButtonViewHtml(elm, widget, db_attributes, properties_settings, view_permissions_html);
		
		//prepare edit button to edit data in a popup
		if (edit_with_edit_button || edit_type == "button")
			html += getWidgetItemActionsButtonEditHtml(elm, widget, db_attributes, properties_settings, edit_permissions_html);
		
		//prepare remove button
		if (remove_active)
			html += getWidgetItemActionsButtonRemoveHtml(elm, widget, db_attributes, properties_settings, remove_permissions_html);
		
		//prepare edit button to save data inline
		if (edit_type == "inline") {
			html += getWidgetItemActionsButtonUpdateHtml(elm, widget, db_attributes, properties_settings, edit_permissions_html);
			
			if (view_active && edit_toggle_edit_view_fields_button)
				html += getWidgetItemActionsButtonToggleInlineEditViewHtml(elm, widget, db_attributes, properties_settings, edit_permissions_html);
		}
		
		return html;
	}
	
	function getWidgetItemActionsButtonAddHtml(elm, widget, db_attributes, properties_settings, add_permissions_html) {
		if (!add_permissions_html) {
			if (!properties_settings) {
				properties_settings = getWidgetSettingsData(elm, ".widget-properties");
				properties_settings = properties_settings["widget_properties"];
			}
			
			var add_active = properties_settings["addable"] ? properties_settings["addable"]["active"] : false;
			var add_permissions = add_active ? properties_settings["addable"]["permissions"] : false;
			add_permissions_html = add_permissions ? ' data-widget-permissions="' + JSON.stringify(add_permissions).replace(/"/g, "&quot;") + '"' : '';
		}
		
		return '<button class="btn btn-sm btn-primary text-nowrap m-1" data-widget-item-button-add class="add" onClick="MyWidgetResourceLib.ItemHandler.addResourceItem(this); return false;"' + add_permissions_html + ' title="Add"><i class="bi bi-plus-lg icon icon-add mr-1 me-1 overflow-visible"></i>Add</button>';
	}
	
	function getWidgetItemActionsButtonAddCancelHtml(elm, widget, db_attributes, properties_settings, add_permissions_html) {
		if (!add_permissions_html) {
			if (!properties_settings) {
				properties_settings = getWidgetSettingsData(elm, ".widget-properties");
				properties_settings = properties_settings["widget_properties"];
			}
			
			var add_active = properties_settings["addable"] ? properties_settings["addable"]["active"] : false;
			var add_permissions = add_active ? properties_settings["addable"]["permissions"] : false;
			add_permissions_html = add_permissions ? ' data-widget-permissions="' + JSON.stringify(add_permissions).replace(/"/g, "&quot;") + '"' : '';
		}
		
		return '<button class="btn btn-sm btn-secondary text-nowrap m-1" data-widget-item-button-add-cancel class="cancel" onClick="MyWidgetResourceLib.ItemHandler.cancelAddResourceItem(this); return false;"' + add_permissions_html + ' title="Cancel"><i class="bi bi-backspace icon icon-cancel mr-1 me-1 overflow-visible"></i>Cancel</button>';
	}
	
	function getWidgetItemActionsButtonViewHtml(elm, widget, db_attributes, properties_settings, view_permissions_html, popup_id) {
		if (!properties_settings) {
			properties_settings = getWidgetSettingsData(elm, ".widget-properties");
			properties_settings = properties_settings["widget_properties"];
		}
		
		if (!view_permissions_html) {
			var view_active = properties_settings["viewable"] ? properties_settings["viewable"]["active"] : false;
			var view_permissions = view_active ? properties_settings["viewable"]["permissions"] : false;
			view_permissions_html = view_permissions ? ' data-widget-permissions="' + JSON.stringify(view_permissions).replace(/"/g, "&quot;") + '"' : '';
		}
		
		if (!popup_id) {
			var popup_view = widget.find("[data-widget-popup][data-widget-popup-view]");
			
			if (!popup_view[0]) {
				var popup_html = getWidgetPopupViewHtml(elm, widget, db_attributes, properties_settings);
				popup_view = $(popup_html);
				widget.append(popup_view);
				
				//convert html to LayoutUIEditor widgets
				convertHtmlElementToLayoutUIEditorWidget(popup_view);
			}
			
			popup_id = popup_view.attr("id");
		}
		
		return '<button class="btn btn-sm btn-light text-nowrap m-1" data-widget-item-button-view class="view" onClick="MyWidgetResourceLib.ItemHandler.openItemViewPopupById(this); return false;" data-widget-popup-id="' + popup_id + '"' + view_permissions_html + ' title="View"><i class="bi bi-eye icon icon-view mr-1 me-1 overflow-visible"></i>View</button>';
	}
	
	function getWidgetItemActionsButtonUpdateHtml(elm, widget, db_attributes, properties_settings, edit_permissions_html) {
		if (!edit_permissions_html) {
			if (!properties_settings) {
				properties_settings = getWidgetSettingsData(elm, ".widget-properties");
				properties_settings = properties_settings["widget_properties"];
			}
			
			var edit_active = properties_settings["editable"] ? properties_settings["editable"]["active"] : false;
			var edit_permissions = edit_active ? properties_settings["editable"]["permissions"] : false;
			edit_permissions_html = edit_permissions ? ' data-widget-permissions="' + JSON.stringify(edit_permissions).replace(/"/g, "&quot;") + '"' : '';
		}
		
		return '<button class="btn btn-sm btn-primary text-nowrap m-1" data-widget-item-button-update class="save" onClick="MyWidgetResourceLib.ItemHandler.updateResourceItem(this); return false;"' + edit_permissions_html + ' title="Save"><i class="bi bi-save icon icon-save mr-1 me-1 overflow-visible"></i>Save</button>';
	}
	
	function getWidgetItemActionsButtonEditHtml(elm, widget, db_attributes, properties_settings, edit_permissions_html, popup_id) {
		if (!properties_settings) {
			properties_settings = getWidgetSettingsData(elm, ".widget-properties");
			properties_settings = properties_settings["widget_properties"];
		}
		
		if (!edit_permissions_html) {
			var edit_active = properties_settings["editable"] ? properties_settings["editable"]["active"] : false;
			var edit_permissions = edit_active ? properties_settings["editable"]["permissions"] : false;
			edit_permissions_html = edit_permissions ? ' data-widget-permissions="' + JSON.stringify(edit_permissions).replace(/"/g, "&quot;") + '"' : '';
		}
		
		if (!popup_id) {
			var popup_edit = widget.find("[data-widget-popup][data-widget-popup-edit]");
			
			if (!popup_edit[0]) {
				var popup_html = getWidgetPopupEditHtml(elm, widget, db_attributes, properties_settings);
				popup_edit = $(popup_html);
				widget.append(popup_edit);
				
				//convert html to LayoutUIEditor widgets
				convertHtmlElementToLayoutUIEditorWidget(popup_edit);
			}
			
			popup_id = popup_edit.attr("id");
		}
		
		return '<button class="btn btn-sm btn-primary text-nowrap m-1" data-widget-item-button-edit class="edit" onClick="MyWidgetResourceLib.ItemHandler.openItemEditPopupById(this); return false;" data-widget-popup-id="' + popup_id + '"' + edit_permissions_html + ' title="Edit"><i class="bi bi-pencil icon icon-edit mr-1 me-1 overflow-visible"></i>Edit</button>';
	}
	
	function getWidgetItemActionsButtonRemoveHtml(elm, widget, db_attributes, properties_settings, remove_permissions_html) {
		if (!remove_permissions_html) {
			if (!properties_settings) {
				properties_settings = getWidgetSettingsData(elm, ".widget-properties");
				properties_settings = properties_settings["widget_properties"];
			}
			
			var remove_active = properties_settings["removable"] ? properties_settings["removable"]["active"] : false;
			var remove_permissions = remove_active ? properties_settings["removable"]["permissions"] : false;
			remove_permissions_html = remove_permissions ? ' data-widget-permissions="' + JSON.stringify(remove_permissions).replace(/"/g, "&quot;") + '"' : '';
		}
		
		return '<button class="btn btn-sm btn-danger text-nowrap float-left float-start m-1" data-widget-item-button-remove class="remove" onClick="MyWidgetResourceLib.ItemHandler.removeResourceItem(this); return false;"' + remove_permissions_html + ' title="Remove"><i class="bi bi-trash icon icon-remove mr-1 me-1 overflow-visible"></i>Remove</button>';
	}
	
	function getWidgetItemActionsButtonToggleInlineEditViewHtml(elm, widget, db_attributes, properties_settings, edit_permissions_html) {
		if (!edit_permissions_html) {
			if (!properties_settings) {
				properties_settings = getWidgetSettingsData(elm, ".widget-properties");
				properties_settings = properties_settings["widget_properties"];
			}
			var edit_active = properties_settings["editable"] ? properties_settings["editable"]["active"] : false;
			var edit_permissions = edit_active ? properties_settings["editable"]["permissions"] : false;
			edit_permissions_html = edit_permissions ? ' data-widget-permissions="' + JSON.stringify(edit_permissions).replace(/"/g, "&quot;") + '"' : '';
		}
		
		return '<button class="btn btn-sm btn-light text-nowrap m-1" data-widget-item-button-toggle-inline-edit-view class="toggle" onClick="MyWidgetResourceLib.ItemHandler.toggleResourceAttributesEditing(this); return false;"' + edit_permissions_html + ' title="Toggle Edit and View Fields"><i class="bi bi-back icon icon-toggle mr-1 me-1 overflow-visible"></i>Toggle</button>';
	}
	
	function getWidgetItemAttributeHtml(elm, widget, db_attributes, properties_settings, attr_name, add_active, add_type, add_permissions_html, view_active, view_type, view_with_view_button, view_permissions_html, edit_active, edit_type, edit_with_auto_save, edit_with_save_button, edit_with_edit_button, edit_toggle_edit_view_fields_button, edit_permissions_html, remove_active, remove_permissions_html) {
		var html = '';
		
		//if view details with edit links to popup
		if (view_active && edit_type == "link")
			html += getWidgetItemAttributeLinkEditHtml(elm, widget, db_attributes, properties_settings, attr_name, view_permissions_html);
		//if view details with edit fields or with edit button or no edit
		else if (view_active) {
			if (view_type == "link")
				html += getWidgetItemAttributeLinkViewHtml(elm, widget, db_attributes, properties_settings, attr_name, view_permissions_html);
			else
				html += getWidgetItemAttributeLabelViewHtml(elm, widget, db_attributes, properties_settings, attr_name, view_permissions_html);
		}
		
		if (edit_type == "inline")
			html += getWidgetItemAttributeFieldEditHtml(elm, widget, db_attributes, properties_settings, attr_name, edit_with_auto_save, edit_permissions_html);
		
		if (add_type == "inline")
			html += getWidgetItemAttributeFieldAddHtml(elm, widget, db_attributes, properties_settings, attr_name, add_permissions_html);
		
		return html;
	}
	
	function getWidgetItemAttributeLabelViewHtml(elm, widget, db_attributes, properties_settings, attr_name, view_permissions_html) {
		if (!view_permissions_html) {
			if (!properties_settings) {
				properties_settings = getWidgetSettingsData(elm, ".widget-properties");
				properties_settings = properties_settings["widget_properties"];
			}
			
			var view_active = properties_settings["viewable"] ? properties_settings["viewable"]["active"] : false;
			var view_permissions = view_active ? properties_settings["viewable"]["permissions"] : false;
			view_permissions_html = view_permissions ? ' data-widget-permissions="' + JSON.stringify(view_permissions).replace(/"/g, "&quot;") + '"' : '';
		}
		
		//prepare attr foreign key if exists
		var av_html_props = getWidgetItemAttributeAvailableValuesHtmlProps(elm, widget, db_attributes, properties_settings, attr_name, view_permissions_html);
		
		//prepare resource_value
		var resource_value_html = ' data-widget-resource-value="{&quot;attribute&quot;:&quot;' + attr_name + '&quot;' + av_html_props["widget_resource_value_html"] + '}"';
		
		//prepare binary files as image
		var attr = $.isPlainObject(db_attributes) ? db_attributes[attr_name] : null;
		var attr_html_props = getWidgetItemAttributeFieldParameters(attr);
		var input_type = attr_html_props ? attr_html_props["input_type"] : null;
		var inner_html = "";
		
		if (input_type == "file") {
			inner_html = '<img src="data:image/*;base64, #' + attr_name + '#" style="width:100px;" />';
			resource_value_html = "";
		}
		
		return '<span class="form-control-plaintext" data-widget-item-attribute-field-view' + resource_value_html + view_permissions_html + av_html_props["attrs_html"] + '>' + inner_html + '</span>';
	}
	
	function getWidgetItemAttributeLinkViewHtml(elm, widget, db_attributes, properties_settings, attr_name, view_permissions_html, popup_id) {
		if (!view_permissions_html) {
			if (!properties_settings) {
				properties_settings = getWidgetSettingsData(elm, ".widget-properties");
				properties_settings = properties_settings["widget_properties"];
			}
			
			var view_active = properties_settings["viewable"] ? properties_settings["viewable"]["active"] : false;
			var view_permissions = view_active ? properties_settings["viewable"]["permissions"] : false;
			view_permissions_html = view_permissions ? ' data-widget-permissions="' + JSON.stringify(view_permissions).replace(/"/g, "&quot;") + '"' : '';
		}
		
		if (!popup_id) {
			var popup_view = widget.find("[data-widget-popup][data-widget-popup-view]");
			
			if (!popup_view[0]) {
				var popup_html = getWidgetPopupViewHtml(elm, widget, db_attributes, properties_settings);
				popup_view = $(popup_html);
				widget.append(popup_view);
				
				//convert html to LayoutUIEditor widgets
				convertHtmlElementToLayoutUIEditorWidget(popup_view);
			}
			
			popup_id = popup_view.attr("id");
		}
		
		//prepare attr foreign key if exists
		var av_html_props = getWidgetItemAttributeAvailableValuesHtmlProps(elm, widget, db_attributes, properties_settings, attr_name, view_permissions_html);
		
		//prepare resource_value
		var resource_value_html = ' data-widget-resource-value="{&quot;attribute&quot;:&quot;' + attr_name + '&quot;' + av_html_props["widget_resource_value_html"] + '}"';
		
		//prepare binary files as image
		var attr = $.isPlainObject(db_attributes) ? db_attributes[attr_name] : null;
		var attr_html_props = getWidgetItemAttributeFieldParameters(attr);
		var input_type = attr_html_props ? attr_html_props["input_type"] : null;
		var inner_html = "";
		
		if (input_type == "file") {
			inner_html = '<img src="data:image/*;base64, #' + attr_name + '#" style="width:100px;" />';
			resource_value_html = "";
		}
		
		return '<a class="form-control-plaintext" data-widget-item-attribute-link-view href="javascript:void(0)" onClick="MyWidgetResourceLib.ItemHandler.openItemViewPopupById(this); return false;" data-widget-popup-id="' + popup_id + '"' + resource_value_html + view_permissions_html + av_html_props["attrs_html"] + '></a>';
	}
	
	function getWidgetItemAttributeLinkEditHtml(elm, widget, db_attributes, properties_settings, attr_name, view_permissions_html, popup_id) {
		if (!view_permissions_html) {
			if (!properties_settings) {
				properties_settings = getWidgetSettingsData(elm, ".widget-properties");
				properties_settings = properties_settings["widget_properties"];
			}
			
			var view_active = properties_settings["viewable"] ? properties_settings["viewable"]["active"] : false;
			var view_permissions = view_active ? properties_settings["viewable"]["permissions"] : false;
			view_permissions_html = view_permissions ? ' data-widget-permissions="' + JSON.stringify(view_permissions).replace(/"/g, "&quot;") + '"' : '';
		}
		
		if (!popup_id) {
			var popup_edit = widget.find("[data-widget-popup][data-widget-popup-edit]");
			
			if (!popup_edit[0]) {
				var popup_html = getWidgetPopupEditHtml(elm, widget, db_attributes, properties_settings);
				popup_edit = $(popup_html);
				widget.append(popup_edit);
				
				//convert html to LayoutUIEditor widgets
				convertHtmlElementToLayoutUIEditorWidget(popup_edit);
			}
			
			popup_id = popup_edit.attr("id");
		}
		
		//prepare attr foreign key if exists
		var av_html_props = getWidgetItemAttributeAvailableValuesHtmlProps(elm, widget, db_attributes, properties_settings, attr_name, view_permissions_html);
		
		//prepare resource_value
		var resource_value_html = ' data-widget-resource-value="{&quot;attribute&quot;:&quot;' + attr_name + '&quot;' + av_html_props["widget_resource_value_html"] + '}"';
		
		//prepare binary files as image
		var attr = $.isPlainObject(db_attributes) ? db_attributes[attr_name] : null;
		var attr_html_props = getWidgetItemAttributeFieldParameters(attr);
		var input_type = attr_html_props ? attr_html_props["input_type"] : null;
		var inner_html = "";
		
		if (input_type == "file") {
			inner_html = '<img src="data:image/*;base64, #' + attr_name + '#" style="width:100px;" />';
			resource_value_html = "";
		}
		
		return '<a class="form-control-plaintext" data-widget-item-attribute-link-edit href="javascript:void(0)" onClick="MyWidgetResourceLib.ItemHandler.openItemEditPopupById(this); return false;" data-widget-popup-id="' + popup_id + '"' + resource_value_html + view_permissions_html + av_html_props["attrs_html"] + '>' + inner_html + '</a>';
	}
	
	function getWidgetItemAttributeFieldEditHtml(elm, widget, db_attributes, properties_settings, attr_name, edit_with_auto_save, edit_permissions_html) {
		if (!properties_settings) {
			properties_settings = getWidgetSettingsData(elm, ".widget-properties");
			properties_settings = properties_settings["widget_properties"];
		}
		
		if (!db_attributes || $.isEmptyObject(db_attributes))
			db_attributes = getWidgetGroupDBAttributes(widget);
		
		if (!edit_permissions_html) {
			var edit_active = properties_settings["editable"] ? properties_settings["editable"]["active"] : false;
			var edit_permissions = edit_active ? properties_settings["editable"]["permissions"] : false;
			edit_permissions_html = edit_permissions ? ' data-widget-permissions="' + JSON.stringify(edit_permissions).replace(/"/g, "&quot;") + '"' : '';
		}
		
		//if attr is a primary key auto_icremented, we should not create any field, bc the user should not be able to edit this attribute.
		var attr = $.isPlainObject(db_attributes) ? db_attributes[attr_name] : null;
		
		if (attr && attr["primary_key"] && attr["auto_increment"]) {
			//prepare attr foreign key if exists
			var av_html_props = getWidgetItemAttributeAvailableValuesHtmlProps(elm, widget, db_attributes, properties_settings, attr_name, edit_permissions_html);
			
			return '<span class="form-control-plaintext" data-widget-item-attribute-field-edit data-widget-resource-value="{&quot;attribute&quot;:&quot;' + attr_name + '&quot;' + av_html_props["widget_resource_value_html"] + '}"' + edit_permissions_html + av_html_props["attrs_html"] + '></span>';
		}
		else
			return getWidgetItemAttributeFieldHtml(elm, widget, db_attributes, properties_settings, attr_name, edit_with_auto_save, edit_permissions_html, "data-widget-item-attribute-field-edit");
	}
	
	function getWidgetItemAttributeFieldAddHtml(elm, widget, db_attributes, properties_settings, attr_name, add_permissions_html) {
		if (!properties_settings) {
			properties_settings = getWidgetSettingsData(elm, ".widget-properties");
			properties_settings = properties_settings["widget_properties"];
		}
		
		if (!add_permissions_html) {
			var add_active = properties_settings["addable"] ? properties_settings["addable"]["active"] : false;
			var add_permissions = add_active ? properties_settings["addable"]["permissions"] : false;
			add_permissions_html = add_permissions ? ' data-widget-permissions="' + JSON.stringify(add_permissions).replace(/"/g, "&quot;") + '"' : '';
		}
		
		return getWidgetItemAttributeFieldHtml(elm, widget, db_attributes, properties_settings, attr_name, false, add_permissions_html, "data-widget-item-attribute-field-add");
	}
	
	function getWidgetItemAttributeFieldHtml(elm, widget, db_attributes, properties_settings, attr_name, with_auto_save, permissions_html, widget_type) {
		if (!db_attributes || $.isEmptyObject(db_attributes))
			db_attributes = getWidgetGroupDBAttributes(widget);
		
		var attr = $.isPlainObject(db_attributes) ? db_attributes[attr_name] : null;
		
		//if attr is a primary key auto_icremented, we should not create any field, bc the user should not be able to edit this attribute.
		if (attr && attr["primary_key"] && attr["auto_increment"])
			return "";
		
		var html = "";
		var attr_html_props = getWidgetItemAttributeFieldParameters(attr);
		var input_type = attr_html_props ? attr_html_props["input_type"] : null;
		var extra_html = getWidgetItemAttributeFieldAttributesHtml(attr_html_props);
		var is_add_type = widget_type == "data-widget-item-attribute-field-add";
		
		//prepare extra_html
		var resource_value_html = ' data-widget-resource-value="{&quot;attribute&quot;:&quot;' + attr_name + '&quot;}"';
		
		//prepare attr foreign key if exists
		var resource_name = getWidgetItemAttributeFKResourceName(elm, widget, db_attributes, properties_settings, attr_name, permissions_html);
		
		if (resource_name) { //create field "select"
			html = '<div ' + widget_type + permissions_html + ' data-widget-item-attribute-field-toggle-select-input>'
					+ '<div class="input-group show">'
						+ '<select class="form-control custom-select form-select" ' + resource_value_html + extra_html + (with_auto_save ? ' onBlur="MyWidgetResourceLib.ItemHandler.updateResourceItemAttributeOnBlur(this); return false;" onChange="MyWidgetResourceLib.ItemHandler.updateResourceItemAttributeOnChange(this); return false;"' : '') + ' data-widget-props="{&quot;load&quot;:&quot;MyWidgetResourceLib.FieldHandler.loadFieldResource&quot;}" data-widget-resources="' + resource_name + '" data-widget-item-resources-load>'
							+ '<option></option>'
						+ '</select>'
						+ '<div class="input-group-append">'
							+ '<button class="btn btn-outline-secondary" onClick="MyWidgetResourceLib.ItemHandler.toggleItemAttributeSelectFieldToInputField(this); return false;">'
								+ '<span class="bi bi-plus-lg icon icon-add"></span>'
							+ '</button>'
						+ '</div>'
					+ '</div>'
					+ '<div class="input-group">'
						+ '<input class="form-control" type="' + input_type + '"' + (with_auto_save ? ' onBlur="MyWidgetResourceLib.ItemHandler.updateResourceItemAttributeOnBlur(this); return false;" onKeyUp="MyWidgetResourceLib.ItemHandler.updateResourceItemAttributeOnKeyUp(this); return false;"' : '') + ' value="" />'
						+ '<div class="input-group-append">'
							+ '<button class="btn btn-outline-secondary" onClick="MyWidgetResourceLib.ItemHandler.toggleItemAttributeInputFieldToSelectField(this); return false;">'
								+ '<span class="bi bi-search icon icon-search"></span>'
							+ '</button>'
						+ '</div>'
					+ '</div>'
				+ '</div>';
			
			//console.log(html);
		}
		else if (input_type == "textarea") //create field "textarea"
			html = '<textarea class="form-control"' + widget_type + resource_value_html + extra_html + permissions_html + (with_auto_save ? ' onBlur="MyWidgetResourceLib.ItemHandler.updateResourceItemAttributeOnBlur(this); return false;" onKeyUp="MyWidgetResourceLib.ItemHandler.updateResourceItemAttributeOnKeyUp(this); return false;"' : '') + '></textarea>';
		else if (input_type == "checkbox") //create field "checkbox"
			html = '<div class="form-check form-switch pl-0 ps-0"' + widget_type + '>'
					+ '<input class="form-check-input ml-0 ms-0" type="' + input_type + '"' + resource_value_html + extra_html + permissions_html + (with_auto_save ? ' onChange="MyWidgetResourceLib.ItemHandler.updateResourceItemAttributeOnBlur(this); return false;"' : '') + ' value="1" style="width:3em; height:1.5em;" />'
				+ '</div>';
		else if (input_type == "radio") //create field "radio"
			html = '<div class="form-check pl-0 ps-0"' + widget_type + '>'
					+ '<input class="form-check-input ml-0 ms-0" type="' + input_type + '"' + resource_value_html + extra_html + permissions_html + (with_auto_save ? ' onClick="MyWidgetResourceLib.ItemHandler.updateResourceItemAttributeOnBlur(this); return false;"' : '') + ' value="1" />'
				+ '</div>';
		else if (input_type == "file") { //create field "file"
			var name_html = extra_html.match(/(^|\s)name\s*=/) ? "" : ' name="' + attr_name + '"'; //input file must have a name! Is mandatory, otherwise the browser doesn't send the file information into the server, through the $_FILES variable.
			
			html = '<div ' + widget_type + permissions_html + '>'
				+ '<input class="form-control" type="' + input_type + '"' + name_html + extra_html + (with_auto_save ? ' onBlur="MyWidgetResourceLib.ItemHandler.updateResourceItemAttributeOnBlur(this); return false;" onChange="MyWidgetResourceLib.ItemHandler.updateResourceItemAttributeOnChange(this); return false;"' : '') + ' value="" />'
				+ (!is_add_type ? '<img src="data:image/*;base64, #' + attr_name + '#" style="width:100px;" />' : "") //add: resource_value_html
			+ '</div>';
		}
		else //create field "input"
			html = '<input class="form-control" type="' + input_type + '"' + widget_type + resource_value_html + extra_html + permissions_html + (with_auto_save ? ' onBlur="MyWidgetResourceLib.ItemHandler.updateResourceItemAttributeOnBlur(this); return false;" onKeyUp="MyWidgetResourceLib.ItemHandler.updateResourceItemAttributeOnKeyUp(this); return false;"' : '') + ' value="" />';
		
		return html;
	}
	
	function getWidgetItemAttributeFKResourceName(elm, widget, db_attributes, properties_settings, attr_name, permissions_html) {
		if (!db_attributes || $.isEmptyObject(db_attributes))
			db_attributes = getWidgetGroupDBAttributes(widget);
		
		var attr = $.isPlainObject(db_attributes) ? db_attributes[attr_name] : null;
		
		//check if attr is a foreign key
		if (attr && attr["fk"]) {
			var aux = $.isArray(attr["fk"]) ? attr["fk"][0] : attr["fk"]; //if attr["fk"] is array, get the first item
			var fk_exists = aux && aux["table"] && aux["attribute"];
			
			if (fk_exists) {
				//get permissions for resource
				var permissions = null;
				
				if (permissions_html) {
					var aux = $('<div' + permissions_html + '></div>');
					permissions = me.getWidgetPermissions(aux);
					
					delete aux;
				}
				
				//set default alias if user whishes to
				if (!aux["table_alias"]) {
					var db_table_alias = null;
					
					if (saved_table_alias.hasOwnProperty( aux["table"] ))
						db_table_alias = saved_table_alias[ aux["table"] ];
					else if (aux["table"].indexOf("_") != -1) //only if exists _ in the table name
						db_table_alias = prompt("Do you wish to write an alias for the table '" + aux["table"] + "'? (leave it blank for default)");
					
					saved_table_alias[ aux["table"] ] = db_table_alias ? db_table_alias : "";
					
					if (db_table_alias) {
						if ($.isArray(attr["fk"]))
							attr["fk"][0]["table_alias"] = db_table_alias;
						else
							attr["fk"]["table_alias"] = db_table_alias;
					}
				}
				else if (!saved_table_alias.hasOwnProperty( aux["table"] ))
					saved_table_alias[ aux["table"] ] = aux["table_alias"];
				
				//create resource if not exists yet
				var resource_name = addWidgetResourceByType(widget, "load", "get_all_options", permissions, attr["fk"], true);
				
				return resource_name;
			}
		}
		
		return null;
	}
	
	function getWidgetItemAttributeAvailableValuesHtmlProps(elm, widget, db_attributes, properties_settings, attr_name, permissions_html) {
		var resource_name = getWidgetItemAttributeFKResourceName(elm, widget, db_attributes, properties_settings, attr_name, permissions_html);
		var attrs_html = '';
		var widget_resource_value_html = '';
		
		if (resource_name) {
			attrs_html = ' data-widget-props="{&quot;load&quot;:&quot;MyWidgetResourceLib.FieldHandler.cacheFieldResource&quot;}" data-widget-resources="' + resource_name + '" data-widget-item-resources-load';
			widget_resource_value_html = ', &quot;available_values&quot;:{&quot;0&quot;:{&quot;name&quot;:&quot;' + resource_name + '&quot;}}';
		}
		else {
			if (!db_attributes || $.isEmptyObject(db_attributes))
				db_attributes = getWidgetGroupDBAttributes(widget);
			
			var attr = $.isPlainObject(db_attributes) ? db_attributes[attr_name] : null;
			var attr_html_props = getWidgetItemAttributeFieldParameters(attr);
			var input_type = attr_html_props ? attr_html_props["input_type"] : null;
			
			if (input_type == "checkbox")
				widget_resource_value_html = ', &quot;available_values&quot;:{&quot;&quot;:&quot;NO&quot;, &quot;0&quot;:&quot;NO&quot;, &quot;1&quot;:&quot;YES&quot;}';
		}
		
		return {
			attrs_html: attrs_html,
			widget_resource_value_html: widget_resource_value_html
		};
	}
	
	//copied from the __system/layer/presentation/phpframework/src/util/CMSPresentationFormSettingsUIHandler.php::getFormFieldInputAttrs method
	function getWidgetItemAttributeFieldAttributesHtml(attr_html_props) {
		var html = '';
		
		if (attr_html_props) {
			html += ' data-allow-null="' + (attr_html_props["allow_null"] ? 1 : 0) + '"' + (attr_html_props["allow_null"] ? "" : " required");
			
			if (attr_html_props["validation_type"])
				html += ' data-validation-type="' + attr_html_props["validation_type"] + '"';
			
			if (attr_html_props["validation_message"])
				html += ' data-validation-message="' + attr_html_props["validation_message"].replace(/"/g, "&quot;") + '"';
			
			if (attr_html_props["validation_label"])
				html += ' data-validation-label="' + attr_html_props["validation_label"].replace(/"/g, "&quot;") + '"';
			
			if (attr_html_props["place_holder"])
				html += ' placeHolder="' + attr_html_props["place_holder"].replace(/"/g, "&quot;") + '"';
			
			if (attr_html_props["max_length"])
				html += ' maxLength="' + attr_html_props["max_length"] + '"';
			
			if ($.isNumeric(attr_html_props["min_value"]))
				html += ' min="' + attr_html_props["min_value"] + '"';
			
			if ($.isNumeric(attr_html_props["max_value"]))
				html += ' max="' + attr_html_props["max_value"] + '"';
			
			if (attr_html_props["extra_attributes"])
				for (var k in attr_html_props["extra_attributes"])
					html += ' ' + k + '="' + ("" + attr_html_props["extra_attributes"][k]).replace(/"/g, "&quot;") + '"';
		}
		
		return html;
	}
	
	//copied from the __system/layer/presentation/phpframework/src/util/CMSPresentationFormSettingsUIHandler.php::prepareFormInputParameters method
	function getWidgetItemAttributeFieldParameters(attr) {
		if ($.isPlainObject(attr) && attr["name"] && attr["type"]) {
			var input_type = null, 
				allow_null = null, 
				validation_type = null, 
				validation_message = null, 
				validation_label = null, 
				place_holder = null, 
				extra_attributes = null, 
				max_length = null, 
				min_value = null, 
				max_value = null;
			
			allow_null = !attr.hasOwnProperty("null") || !attr["null"] === null || attr["null"] ? true : false;
			max_length = getWidgetItemAttributeFieldMaxLength(attr);
			
			var label = getLabel(attr["name"]);
			validation_label = label;
			
			switch (attr["type"]) {
				case "int":
					place_holder = "0";
					validation_type = "int";
					validation_message = "'" + label + "' field is not a valid integer number.";
					input_type = "number";
					break;
				case "bigint":
					place_holder = "0";
					validation_type = "bigint";
					validation_message = "'" + label + "' field is not a valid big integer number.";
					input_type = "number";
					break;
				case "decimal":
					place_holder = "0";
					validation_type = "decimal";
					validation_message = "'" + label + "' field is not a valid decimal number.";
					input_type = "number";
					extra_attributes = {"step": "any"};
					break;
				case "double":
					place_holder = "0";
					validation_type = "double";
					validation_message = "'" + label + "' field is not a valid double number.";
					input_type = "number";
					extra_attributes = {"step": "0.00000000000001"};
					break;
				case "float":
					place_holder = "0";
					validation_type = "float";
					validation_message = "'" + label + "' field is not a valid float number.";
					input_type = "number";
					extra_attributes = {"step": "0.0000001"};
					break;
				case "smallint":
					if (attr["length"] == 1)
						input_type = "checkbox";
					else {
						place_holder = "0";
						validation_type = "smallint";
						validation_message = "'" + label + "' field is not a valid small integer number.";
						input_type = "number";
					}
					break;
					
				case "bit":
				case "boolean":
					input_type = "checkbox";
					break;
				case "tinyint":
					if (attr["length"] == 1)
						input_type = "checkbox";
					break;
				
				case "text":
				case "tinytext":
				case "mediumtext":
				case "longtext":
					input_type = "textarea";
					break;
				case "blob":
				case "tinyblob":
				case "mediumblob":
				case "longblob":
					input_type = "file";
					break;
				
				case "date":
					place_holder = "yyyy-mm-dd";
					validation_type = "date";
					validation_message = "'" + label + "' field is not a valid date. Please respect this format: " + place_holder;
					input_type = "date";
					break;
				case "datetime":
				case "datetime2":
				case "datetimeoffset":
				case "smalldatetime":
				case "timestamp":
				case "timestamp without time zone":
					place_holder = "yyyy-mm-dd hh:ii:ss";
					validation_type = "datetime";
					validation_message = "'" + label + "' field is not a valid date. Please respect this format: " + place_holder;
					input_type = "datetime";
					break;
				case "time":
				case "time without time zone":
					place_holder = "hh:ii:ss";
					validation_type = "time";
					validation_message = "'" + label + "' field is not a valid time. Please respect this format: " + place_holder;
					input_type = "time";
					break;
				
				default:
					if (attr["type"].indexOf("text") != -1)
						input_type = "textarea";
					else if (attr["type"].indexOf("blob") != -1)
						input_type = "file";
			}
			
			//If there is a form or an editable list with a checkbox with a numeric value, then allows the checkbox to be null, bc the logic code then will add a default value or null, to the attribute correspondent to this checkbox.
			if (allow_null == "0" && (input_type == "checkbox" || input_type == "radio") && (
				isDBTypeNumeric(attr["type"]) || isPHPTypeNumeric(attr["type"])
			))
				allow_null = true;
			
			if (!input_type && attr["type"].indexOf("char") != -1 && attr["length"] > 255)
				input_type = "textarea";
			
			if (isDBTypeBlob(attr["type"]))
				input_type = "file";
			else if (isDBTypeNumeric(attr["type"]) || isPHPTypeNumeric(attr["type"])) {
				place_holder = place_holder ? place_holder : "0";
				validation_type = validation_type ? validation_type : "number";
				validation_message = "'" + label + "' field is not a valid number.";
				input_type = input_type ? input_type : "number";
				max_length = attr["length"] ? attr["length"] : null;
				min_value = attr["unsigned"] ? 0 : null;
				
				if (max_length) {
					try {
						max_value = "9".repeat(max_length);
					}
					catch(e) {
						if (console && console.log)
							console.log(e);
					}
				}
			}
			else if (attr["name"].match(/(email|e_mail)/i)) {
				place_holder = "example@email.here";
				validation_type = validation_type ? validation_type : "email";
				validation_message = "'" + label + "' field is not a valid email.";
				input_type = input_type ? input_type : "email";
			}
			else if (attr["name"].match(/(date|data|fecha)/i)) {
				place_holder = place_holder ? place_holder : "yyyy-mm-dd hh:ii:ss";
				validation_type = validation_type ? validation_type : "datetime";
				validation_message = "'" + label + "' field is not a valid date. Please respect this format: yyyy-mm-dd hh:ii:ss";
				input_type = input_type ? input_type : "datetime";
			}
			else if (attr["name"].match(/url/i)) {
				place_holder = place_holder ? place_holder : "https://www.foo.bar/";
				input_type = input_type ? input_type : "url";
			}
			else if (attr["name"].match(/(phone|fone|fono|contact|contacto|contato)/i)) {
				place_holder = place_holder ? place_holder : "000000000";
				validation_type = validation_type ? validation_type : "phone";
				validation_message = "'" + label + "' field is not a valid phone.";
				input_type = input_type ? input_type : "tel";
			}
			
			if (!input_type)
				input_type = "text";
			
			return {
				input_type: input_type, 
				allow_null: allow_null, 
				validation_type: validation_type, 
				validation_message: validation_message, 
				validation_label: validation_label, 
				place_holder: place_holder, 
				extra_attributes: extra_attributes, 
				max_length: max_length,
				min_value: min_value,
				max_value: max_value
			};
		}
		
		return null;
	}
	
	//copied from the __system/layer/presentation/phpframework/src/util/CMSPresentationFormSettingsUIHandler.php::getFormInputMaxLength method
	function getWidgetItemAttributeFieldMaxLength(attr) {
		if ($.isPlainObject(attr)) {
			var type = attr["type"];
			var length = attr["length"];
			
			if (length) {
				var other_text_types = ["date", "datetime", "datetime2", "datetimeoffset", "smalldatetime", "timestamp", "timestamp without time zone", "time", "time without time zone"];
				
				if (type.indexOf("char") != -1 || type.indexOf("text") != -1 || type.indexOf("blob") != -1 || $.inArray(type, other_text_types) != -1)
					return length;
			}
		}
		
		return null;
	}
	
	function hasWidgetItemBinaryAttribute(db_attributes) {
		if ($.isPlainObject(db_attributes))
			for (var attr_name in db_attributes) {
				var attr = db_attributes[attr_name];
				
				if (attr && attr["type"] && attr["type"].indexOf("blob") != -1)
					return true;
			}
		
		return false;
	}
	
	function isDBInternalAttributeName(attr_name) {
		var internal_attribute_names = executeCallback(me.options.get_internal_attribute_names_func, "get_internal_attribute_names_func");
		return attr_name && $.isArray(internal_attribute_names) && $.inArray(("" + attr_name).toLowerCase(), internal_attribute_names) != -1;
	}
	
	function isDBTypeBlob(type) {
		var blob_types = executeCallback(me.options.get_db_blob_types_func, "get_db_blob_types_func");
		//console.log(type+":"+($.isArray(blob_types) ? $.inArray(type, blob_types) : -1));
		return $.isArray(blob_types) && $.inArray(type, blob_types) != -1;
	}
	
	function isDBTypeNumeric(type) {
		var numeric_types = executeCallback(me.options.get_db_numeric_types_func, "get_db_numeric_types_func");
		return $.isArray(numeric_types) && $.inArray(type, numeric_types) != -1;
	}
	
	function isPHPTypeNumeric(type) {
		var numeric_types = executeCallback(me.options.get_php_numeric_types_func, "get_php_numeric_types_func");
		return $.isArray(numeric_types) && $.inArray(type, numeric_types) != -1;
	}
	
	function getWidgetPopupViewHtml(elm, widget, db_attributes, properties_settings) {
		if (!properties_settings) {
			properties_settings = getWidgetSettingsData(elm, ".widget-properties");
			properties_settings = properties_settings["widget_properties"];
		}
		
		if (!db_attributes || $.isEmptyObject(db_attributes))
			db_attributes = getWidgetGroupDBAttributes(widget);
		
		if (db_attributes) {
			//only include attributes that are not internal attributes like: created_date or modified_date, modified_by created_by, created_user_id, modified_user_id...
			db_attributes = purgeInternalAttributesFromWidgetDBAttributes(db_attributes);
			
			var view_active = properties_settings["viewable"] ? properties_settings["viewable"]["active"] : false;
			var view_type = view_active ? "inline" : false;
			var view_with_view_button = view_active ? properties_settings["viewable"]["with_view_button"] : false;
			var view_permissions = view_active ? properties_settings["viewable"]["permissions"] : false;
			var view_permissions_html = view_permissions ? ' data-widget-permissions="' + JSON.stringify(view_permissions).replace(/"/g, "&quot;") + '"' : '';
			
			//prepare id
			var main_widget_suffix_id = me.getWidgetSuffix(widget);
			
			var widget_prefix_id = "widget_popup_view_" + (main_widget_suffix_id ? main_widget_suffix_id + "_" : "");
			var widget_suffix_id = me.getNewWidgetIdCount(widget_prefix_id);
			var widget_id = widget_prefix_id + widget_suffix_id;
			
			var widget_prefix_id = "widget_popup_view_form_" + (main_widget_suffix_id ? main_widget_suffix_id + "_" : "");
			var widget_suffix_id = me.getNewWidgetIdCount(widget_prefix_id);
			var widget_form_id = widget_prefix_id + widget_suffix_id;
			
			//prepare pks_attrs_names
			var pks_attrs_names = getDBAttributesPKsAttrsNames(db_attributes);
			
			//prepare properties
			var form_props = {
				pks_attrs_names: pks_attrs_names,
				load: "MyWidgetResourceLib.FormHandler.loadFormResource",
				//enter_key_press_button: "#some_id_that_do_not_exists_just_to_disable_the_enter_key_event" //there are no buttons so this issue doesn't apply to this popup
			};
			var form_props_html = ' data-widget-props="' + JSON.stringify(form_props).replace(/"/g, "&quot;") + '"';
			
			var popup_props = {
				dependent_widgets_id: widget_form_id,
				load: "MyWidgetResourceLib.PopupHandler.loadPopupResource",
			};
			var popup_props_html = ' data-widget-props="' + JSON.stringify(popup_props).replace(/"/g, "&quot;") + '"';
			
			//prepare resources
			var form_resource = {
				load: getWidgetResourcesTypeDefaultProperties("load", "get"),
			};
			form_resource["load"] = $.isPlainObject(form_resource["load"]) ? form_resource["load"] : {};
			form_resource["load"]["name"] = addWidgetResourceByType(widget, "load", "get", view_permissions, null, true);
			
			var form_resource_html = ' data-widget-resources="' + JSON.stringify(form_resource).replace(/"/g, "&quot;") + '"'; //json object with resource properties that will return the count of the items of some table
			
			//prepare html
			var db_table = properties_settings["db_table"];
			var db_table_alias = properties_settings["db_table_alias"];
			
			if (!db_table) {
				var parent_properties = me.getWidgetGroupProperties(widget);
				
				if (parent_properties) {
					db_table = parent_properties["db_table"];
					db_table_alias = parent_properties["db_table_alias"];
				}
			}
			
			db_table = db_table_alias ? db_table_alias : db_table;
			
			var html = '<div id="' + widget_id + '" class="modal fade text-left text-start" data-widget-popup data-widget-popup-view tabindex="-1" role="dialog" ' + popup_props_html + ' style="background-color:rgba(0, 0, 0, .5);">'
					+ '	<div class="modal-dialog">'
					+ '		<div class="modal-content">'
					+ '			<div class="modal-header">'
					+ '				<h5 class="modal-title text-muted">View ' + getLabel(db_table) + '</h5>'
					+ '				<button class="btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" title="Close Popup"></button>'
					+ '			</div>'
					+ '			<div class="modal-body">'
					+ '				<form id="' + widget_form_id + '" onSubmit="return false;" data-widget-form' + form_resource_html + form_props_html + '>';
			
			//show attributes
			$.each(db_attributes, function(attr_name, attr) {
				html += getWidgetItemColumnHtml(elm, widget, db_attributes, properties_settings, attr_name, false, null, "", view_active, view_type, view_with_view_button, view_permissions_html);
			});
			
			html += '					</form>'
				+ '				</div>'
				+ '			</div>'
				+ '		</div>'
				+ '	</div>';
			
			return html;
		}
		
		return null;
	}
	
	function getWidgetPopupEditHtml(elm, widget, db_attributes, properties_settings) {
		if (!properties_settings) {
			properties_settings = getWidgetSettingsData(elm, ".widget-properties");
			properties_settings = properties_settings["widget_properties"];
		}
		
		if (!db_attributes || $.isEmptyObject(db_attributes))
			db_attributes = getWidgetGroupDBAttributes(widget);
		
		if (db_attributes) {
			//only include attributes that are not internal attributes like: created_date or modified_date, modified_by created_by, created_user_id, modified_user_id...
			db_attributes = purgeInternalAttributesFromWidgetDBAttributes(db_attributes);
			
			var view_active = true;
			var view_type = view_active ? "inline" : false;
			var view_with_view_button = false;
			var view_permissions = view_active ? properties_settings["viewable"]["permissions"] : false;
			var view_permissions_html = view_permissions ? ' data-widget-permissions="' + JSON.stringify(view_permissions).replace(/"/g, "&quot;") + '"' : '';
			
			var edit_active = properties_settings["editable"] ? properties_settings["editable"]["active"] : false;
			var edit_type = edit_active ? "inline" : false;
			var edit_with_auto_save = false;
			var edit_with_save_button = false;
			var edit_with_edit_button = false;
			var edit_toggle_edit_view_fields_button = false;
			var edit_permissions = edit_active ? properties_settings["editable"]["permissions"] : false;
			var edit_permissions_html = edit_permissions ? ' data-widget-permissions="' + JSON.stringify(edit_permissions).replace(/"/g, "&quot;") + '"' : '';
			
			var remove_active = properties_settings["removable"] ? properties_settings["removable"]["active"] : false;
			var remove_permissions = remove_active ? properties_settings["removable"]["permissions"] : false;
			var remove_permissions_html = remove_permissions ? ' data-widget-permissions="' + JSON.stringify(remove_permissions).replace(/"/g, "&quot;") + '"' : '';
			
			//prepare id
			var main_widget_suffix_id = me.getWidgetSuffix(widget);
			
			var widget_prefix_id = "widget_popup_edit_" + (main_widget_suffix_id ? main_widget_suffix_id + "_" : "");
			var widget_suffix_id = me.getNewWidgetIdCount(widget_prefix_id);
			var widget_id = widget_prefix_id + widget_suffix_id;
			
			var widget_prefix_id = "widget_popup_edit_form_" + (main_widget_suffix_id ? main_widget_suffix_id + "_" : "");
			var widget_suffix_id = me.getNewWidgetIdCount(widget_prefix_id);
			var widget_form_id = widget_prefix_id + widget_suffix_id;
			
			//prepare pks_attrs_names
			var pks_attrs_names = getDBAttributesPKsAttrsNames(db_attributes);
			
			//prepare properties
			var dependent_widgets_id = getWidgetGroupDependentWidgetsIdForPopupForm(widget, true);
			var form_props = {
				pks_attrs_names: pks_attrs_names,
				dependent_widgets_id: dependent_widgets_id,
				load: "MyWidgetResourceLib.FormHandler.loadFormResource",
				complete: {},
				enter_key_press_button: edit_active ? "[data-widget-item-button-edit],[data-widget-item-button-update]" : (
					add_active ? "[data-widget-item-button-add]" : (
						remove_active ? "[data-widget-item-button-remove]" : 
						"button.cancel"
					)
				)
			};
			
			if (edit_active) 
				form_props["complete"]["update"] = "MyWidgetResourceLib.FormHandler.onUpdatePopupResourceItem";
			
			if (remove_active) 
				form_props["complete"]["remove"] = "MyWidgetResourceLib.FormHandler.onRemovePopupResourceItem";
			
			var form_props_html = ' data-widget-props="' + JSON.stringify(form_props).replace(/"/g, "&quot;") + '"';
			
			var popup_props = {
				dependent_widgets_id: widget_form_id,
				load: "MyWidgetResourceLib.PopupHandler.loadPopupResource",
			};
			var popup_props_html = ' data-widget-props="' + JSON.stringify(popup_props).replace(/"/g, "&quot;") + '"';
			
			//prepare resources
			var form_resource = {
				load: getWidgetResourcesTypeDefaultProperties("load", "get")
			};
			form_resource["load"] = $.isPlainObject(form_resource["load"]) ? form_resource["load"] : {};
			form_resource["load"]["name"] = addWidgetResourceByType(widget, "load", "get", view_permissions, null, true);
			
			if (edit_active) {
				var update_props = getWidgetResourcesTypeDefaultProperties("update", "update");
				update_props["name"] = addWidgetResourceByType(widget, "update", "update", edit_permissions, null, true);
				form_resource["update"] = update_props;
			}
			
			if (remove_active) {
				var remove_props = getWidgetResourcesTypeDefaultProperties("remove", "delete");
				remove_props["name"] = addWidgetResourceByType(widget, "remove", "delete", remove_permissions, null, true);
				form_resource["remove"] = remove_props;
			}
			
			var form_resource_html = ' data-widget-resources="' + JSON.stringify(form_resource).replace(/"/g, "&quot;") + '"'; //json object with resource properties that will return the count of the items of some table
			
			var enctype_html = hasWidgetItemBinaryAttribute(db_attributes) ? ' enctype="multipart/form-data"' : "";
			
			//prepare html
			var db_table = properties_settings["db_table"];
			var db_table_alias = properties_settings["db_table_alias"];
			
			if (!db_table) {
				var parent_properties = me.getWidgetGroupProperties(widget);
				
				if (parent_properties) {
					db_table = parent_properties["db_table"];
					db_table_alias = parent_properties["db_table_alias"];
				}
			}
			
			db_table = db_table_alias ? db_table_alias : db_table;
			
			var html = '<div id="' + widget_id + '" class="modal fade text-left text-start" data-widget-popup data-widget-popup-edit tabindex="-1" role="dialog" ' + popup_props_html + ' style="background-color:rgba(0, 0, 0, .5);">'
					+ '	<div class="modal-dialog">'
					+ '		<div class="modal-content">'
					+ '			<div class="modal-header">'
					+ '				<h5 class="modal-title text-muted">Edit ' + getLabel(db_table) + '</h5>'
					+ '				<button class="btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" title="Close Popup"></button>'
					+ '			</div>'
					+ '			<div class="modal-body">'
					+ '				<form id="' + widget_form_id + '" method="post"' + enctype_html + ' onSubmit="return false;" data-widget-form' + form_resource_html + form_props_html + (edit_toggle_edit_view_fields_button ? " data-widget-with-button-toggle-inline-edit-view" : "") + '>';
			
			//show attributes
			$.each(db_attributes, function(attr_name, attr) {
				//if attr is a primary key auto_icremented, we should not create any field, bc the user should not be able to edit this attribute.
				if (attr["primary_key"] && attr["auto_increment"]) {
					var field_html = getWidgetItemColumnHtml(elm, widget, db_attributes, properties_settings, attr_name, false, null, "", view_active, view_type, view_with_view_button, view_permissions_html);
					field_html = field_html.replace(" data-widget-item-attribute-field-view ", " data-widget-item-attribute-field-edit "); //make this field as editable type but readonly, otherwise when we unselect the View fields, it will hide this field too, and this field belongs to the edit fields, so we want to still show it.
					
					html += field_html;
				}
				else
					html += getWidgetItemColumnHtml(elm, widget, db_attributes, properties_settings, attr_name, false, null, "", false, null, false, "", edit_active, edit_type, edit_with_auto_save, edit_with_save_button, edit_with_edit_button, edit_toggle_edit_view_fields_button, edit_permissions_html, remove_active, remove_permissions_html);
			});
			
			//prepare last column with buttons
			if (edit_active || remove_active) {
				html += '<div class="text-right text-end mt-4" data-widget-item-actions-column>';
				html += '	<button class="btn btn-sm btn-secondary text-nowrap m-1 cancel" onClick="MyWidgetResourceLib.PopupHandler.closeParentPopup(this); return false;" title="Cancel"><i class="bi bi-backspace icon icon-cancel mr-1 me-1 overflow-visible"></i>Cancel</button>';
				
				if (remove_active)
					html += getWidgetItemActionsButtonRemoveHtml(elm, widget, db_attributes, properties_settings, remove_permissions_html);
				
				if (edit_active)
					html += getWidgetItemActionsButtonUpdateHtml(elm, widget, db_attributes, properties_settings, edit_permissions_html);
				html += '</div>';
			}
			
			html += '					</form>'
				+ '				</div>'
				+ '			</div>'
				+ '		</div>'
				+ '	</div>';
			
			return html;
		}
		
		return null;
	}
	
	function getWidgetPopupAddHtml(elm, widget, db_attributes, properties_settings) {
		if (!properties_settings) {
			properties_settings = getWidgetSettingsData(elm, ".widget-properties");
			properties_settings = properties_settings["widget_properties"];
		}
		
		if (!db_attributes || $.isEmptyObject(db_attributes))
			db_attributes = getWidgetGroupDBAttributes(widget);
		
		if (db_attributes) {
			//only include attributes that are not internal attributes like: created_date or modified_date, modified_by created_by, created_user_id, modified_user_id...
			db_attributes = purgeInternalAttributesFromWidgetDBAttributes(db_attributes);
			
			var add_active = properties_settings["addable"] ? properties_settings["addable"]["active"] : false;
			var add_type = add_active ? "inline" : false;
			var add_permissions = add_active ? properties_settings["addable"]["permissions"] : false;
			var add_permissions_html = add_permissions ? ' data-widget-permissions="' + JSON.stringify(add_permissions).replace(/"/g, "&quot;") + '"' : '';
			
			var view_active = properties_settings["viewable"] ? properties_settings["viewable"]["active"] : false;
			var view_permissions = view_active ? properties_settings["viewable"]["permissions"] : false;
			
			//prepare id
			var main_widget_suffix_id = me.getWidgetSuffix(widget);
			
			var widget_prefix_id = "widget_popup_add_" + (main_widget_suffix_id ? main_widget_suffix_id + "_" : "");
			var widget_suffix_id = me.getNewWidgetIdCount(widget_prefix_id);
			var widget_id = widget_prefix_id + widget_suffix_id;
			
			var widget_prefix_id = "widget_popup_add_form_" + (main_widget_suffix_id ? main_widget_suffix_id + "_" : "");
			var widget_suffix_id = me.getNewWidgetIdCount(widget_prefix_id);
			var widget_form_id = widget_prefix_id + widget_suffix_id;
			
			//prepare pks_attrs_names
			var pks_attrs_names = getDBAttributesPKsAttrsNames(db_attributes, true);
			
			//prepare properties
			var dependent_widgets_id = widget.is("[data-widget-group-form]") ? null : getWidgetGroupDependentWidgetsIdForPopupForm(widget); //data-widget-droup-form doesn't have any dependencies.
			var form_props = {
				pks_attrs_names: pks_attrs_names,
				dependent_widgets_id: dependent_widgets_id,
				complete: {
					add: "MyWidgetResourceLib.FormHandler.onAddPopupResourceItem"
				},
				enter_key_press_button: add_active ? "[data-widget-item-button-add]" : "#some_id_that_do_not_exists_just_to_disable_the_enter_key_event"
			};
			var form_props_html = ' data-widget-props="' + JSON.stringify(form_props).replace(/"/g, "&quot;") + '"';
			
			var popup_props = {
				dependent_widgets_id: widget_form_id,
			};
			var popup_props_html = ' data-widget-props="' + JSON.stringify(popup_props).replace(/"/g, "&quot;") + '"';
			
			//prepare resources
			var add_props = getWidgetResourcesTypeDefaultProperties("add", "insert");
			add_props["name"] = addWidgetResourceByType(widget, "add", "insert", add_permissions, null, true);
			
			var form_resource = {
				add: add_props
			};
			var form_resource_html = ' data-widget-resources="' + JSON.stringify(form_resource).replace(/"/g, "&quot;") + '"'; //json object with resource properties that will return the count of the items of some table
			
			var enctype_html = hasWidgetItemBinaryAttribute(db_attributes) ? ' enctype="multipart/form-data"' : "";
			
			//prepare html
			var db_table = properties_settings["db_table"];
			var db_table_alias = properties_settings["db_table_alias"];
			
			if (!db_table) {
				var parent_properties = me.getWidgetGroupProperties(widget);
				
				if (parent_properties) {
					db_table = parent_properties["db_table"];
					db_table_alias = parent_properties["db_table_alias"];
				}
			}
			
			db_table = db_table_alias ? db_table_alias : db_table;
			
			var html = '<div id="' + widget_id + '" class="modal fade text-left text-start" data-widget-popup data-widget-popup-add tabindex="-1" role="dialog" ' + popup_props_html + ' style="background-color:rgba(0, 0, 0, .5);">'
					+ '	<div class="modal-dialog">'
					+ '		<div class="modal-content">'
					+ '			<div class="modal-header">'
					+ '				<h5 class="modal-title text-muted">Add ' + getLabel(db_table) + '</h5>'
					+ '				<button class="btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" title="Close Popup"></button>'
					+ '			</div>'
					+ '			<div class="modal-body">'
					+ '				<form id="' + widget_form_id + '" class="show-add-fields" method="post"' + enctype_html + ' onSubmit="return false;" data-widget-form' + form_resource_html + form_props_html + '>'; //show-add-fields class is very important here, otherwise the input fields will be hidden by the MyWidgetResourceLib.css
			
			//show attributes
			$.each(db_attributes, function(attr_name, attr) {
				//if attr is a primary key auto_icremented, we should not create any field, bc the user should not be able to edit this attribute.
				if (!attr["primary_key"] || !attr["auto_increment"])
					html += getWidgetItemColumnHtml(elm, widget, db_attributes, properties_settings, attr_name, add_active, add_type, add_permissions_html);
			});
			
			//prepare last column with buttons
			if (add_active) {
				html += '<div class="text-right text-end mt-4" data-widget-item-actions-column>'
					+ '	<button class="btn btn-sm btn-secondary text-nowrap m-1 cancel" onClick="MyWidgetResourceLib.PopupHandler.closeParentPopup(this); return false;" title="Cancel"><i class="bi bi-backspace icon icon-cancel mr-1 me-1 overflow-visible"></i>Cancel</button>'
					+ getWidgetItemActionsButtonAddHtml(elm, widget, db_attributes, properties_settings, add_permissions_html)
					+ '</div>';
			}
			
			html += '					</form>'
				+ '				</div>'
				+ '			</div>'
				+ '		</div>'
				+ '	</div>';
			
			return html;
		}
		
		return null;
	}
	
	/* LOAD FUNCTIONS */
	
	function loadWidgetSettingsData(widget) {
		var settings = createWidgetSettingsData(widget);
		var menu_settings = ui_creator.getMenuSettings();
		var widget_settings_ul = menu_settings.find(" > .settings-widget > ul");
		var widget_resources_ul = widget_settings_ul.find(" > .widget-resources > ul");
		
		//console.log("loaded settings:");
		//console.log(settings);
		
		//reset some dynamic html that might be added when the user clicks twice, which means the system will load the data incrementally, showing duplicated html. So we need to reset all the dynamic items.
		widget_settings_ul.find(".widget-display-resource-value, .widget-dependencies, .widget-permissions, .widget-properties").find("ul[prefix]").children("li:not(.empty-items)").remove();
		
		//load resources
		loadWidgetResourcesSettingsData(widget, settings);
		
		//load resource_value
		var widget_display_resource_value = widget_settings_ul.children(".widget-display-resource-value");
		
		if ($.isPlainObject(settings["widget_resource_value"])) {
			var widget_display_resource_value_ul = widget_display_resource_value.children("ul");
			
			loadWidgetSettingsIntoFields(widget_display_resource_value_ul, settings["widget_resource_value"], "widget_resource_value");
			
			//prepare widget-display-resource-value-resource-display and resource-attribute
			var select = widget_display_resource_value_ul.find(" > .widget-display-resource-value-resource-display > select");
			
			if (settings["widget_resource_value"].hasOwnProperty("attribute")) {
				select.val("attribute");
				widget_display_resource_value.removeClass("with-display-disabled").removeClass("with-display-resource");
			}
			else {
				select.val("resource");
				widget_display_resource_value.removeClass("with-display-disabled").addClass("with-display-resource");
			}
			
			//prepare widget-display-resource-value-display-target-type and target-attribute
			var select = widget_display_resource_value_ul.find(" > .widget-display-resource-value-display-target-type > select");
			
			if (settings["widget_resource_value"]["target_type"] == "attribute") {
				select.val("attribute");
				widget_display_resource_value.addClass("with-display-target-attribute");
			}
			else {
				select.val("");
				widget_display_resource_value.removeClass("with-display-target-attribute");
			}
			
			//prepare available_values
			if (settings["widget_resource_value"]["available_values"]) {
				var available_values_elm = widget_display_resource_value_ul.children(".widget-available-values");
				var add_icon = available_values_elm.children(".widget-item-add");
				var select = available_values_elm.children("select");
				
				for (var k in settings["widget_resource_value"]["available_values"]) {
					var v = settings["widget_resource_value"]["available_values"][k];
					
					if ($.isPlainObject(v)) { //add resource item
						if (v["name"] || $.isNumeric(v["name"])) {
							var item = me.addWidgetAvailableResourceValue(add_icon[0], window.event);
							loadWidgetSettingValueIntoSwappedField(item, v["name"]);
						}
					}
					else { //add static item
						var item = me.addWidgetAvailableStaticValue(add_icon[0], window.event);
						item.find("input.widget-static-item-key").val(k);
						item.find("input.widget-static-item-value").val(v);
					}
				}
			}
		}
		else
			widget_display_resource_value.addClass("with-display-disabled");
		
		//load widget_dependencies
		if ($.isArray(settings["widget_dependencies"]) || $.isPlainObject(settings["widget_dependencies"])) {
			var widget_dependencies_elm = widget_settings_ul.children(".widget-dependencies");
			var add_icon = widget_dependencies_elm.find(" > .group-title > .widget-group-item-add");
			
			$.each(settings["widget_dependencies"], function(idx, widget_id) {
				var item = me.addWidgetId(add_icon[0], window.event);
				loadWidgetSettingValueIntoSwappedField(item, widget_id);
			});
		}
		
		//load widget_permissions
		var widget_permissions_elm = widget_settings_ul.children(".widget-permissions");
		loadWidgetSettingsActionPermissionsData(widget_permissions_elm, settings["widget_permissions"]);
		
		//load widget_properties
		loadWidgetPropertiesSettingsData(widget, settings);
		
		//load extra callbacks
		var callback_lis = widget_settings_ul.find(".with-extra-callbacks");
		
		callback_lis.find(" > .extra-callbacks li").remove(); //reset the extra callbacks
		
		for (var i = 0, t = callback_lis.length; i < t; i++)
			me.convertWidgetCallbackInExtraCallbacks( $(callback_lis[i]) ); //it means has multiple callbacks that should be shown in multiple lines
		
		//load extra events
		var event_lis = widget_settings_ul.find(".with-extra-events");
		
		event_lis.find(" > .extra-events li").remove(); //reset the extra events
		
		for (var i = 0, t = event_lis.length; i < t; i++)
			me.convertWidgetEventInExtraEvents( $(event_lis[i]) ); //it means has multiple events that should be shown in multiple lines
	}
	
	function loadWidgetPropertiesSettingsData(widget, settings) {
		settings = settings ? settings : createWidgetSettingsData(widget);
		
		var menu_settings = ui_creator.getMenuSettings();
		var widget_settings_ul = menu_settings.find(" > .settings-widget > ul");
		
		//console.log("loaded settings:");
		//console.log(settings);
		
		if ($.isPlainObject(settings["widget_properties"]) && !$.isEmptyObject(settings["widget_properties"])) {
			var widget_properties_elm = widget_settings_ul.children(".widget-properties");
			var widget_properties_ul_elm = widget_properties_elm.children("ul");
			
			loadWidgetSettingsIntoFields(widget_properties_ul_elm, settings["widget_properties"], "widget_properties");
			
			//load: shown_attrs_names, pks_attrs_names, search_attrs_names, 
			var selectors = {
				"shown_attrs_names": " > .widget-property-shown-attrs-names",
				"pks_attrs_names": " > .widget-property-pks-attrs-names",
				"search_attrs_names": " > .widget-property-search-attrs-names",
			};
			
			for (var key in selectors) {
				var selector = selectors[key];
				var selector_elm = widget_properties_ul_elm.find(selector);
				var attrs_names = settings["widget_properties"][key];
				
				loadWidgetSettingsAttrsNamesData(selector_elm, attrs_names);
			}
			
			//load: shown_attrs_names, pks_attrs_names, search_attrs_names, 
			var selectors = {
				"default_attrs_values": " > .widget-property-default-attrs-values",
			};
			
			for (var key in selectors) {
				var selector = selectors[key];
				var selector_elm = widget_properties_ul_elm.find(selector);
				var attrs_names = settings["widget_properties"][key];
				
				loadWidgetSettingsAttrsNamesAndValuesData(selector_elm, attrs_names);
			}
			
			//load viewable, addable, editable, removable, multiple_items_actions permissions
			var selectors = {
				"viewable": " > .widget-property-viewable > ul > .widget-property-action-permissions",
				"addable": " > .widget-property-addable > ul > .widget-property-action-permissions",
				"editable": " > .widget-property-editable > ul > .widget-property-action-permissions",
				"removable": " > .widget-property-removable > ul > .widget-property-action-permissions",
				"multiple_items_actions": " > .widget-property-multiple-items-actions > ul > .widget-property-action-permissions",
			};
			
			for (var key in selectors) {
				if (settings["widget_properties"].hasOwnProperty(key) && settings["widget_properties"][key]["permissions"]) {
					var selector = selectors[key];
					var selector_elm = widget_properties_ul_elm.find(selector);
					var permissions = settings["widget_properties"][key]["permissions"];
					
					loadWidgetSettingsActionPermissionsData(selector_elm, permissions);
				}
			}
			
			//prepare editable type select box
			var editable_type_select = widget_properties_ul_elm.find(" > .widget-property-editable > ul > .widget-property-editable-type > select");
			
			if (editable_type_select[0]) {
				var edit_active = settings["widget_properties"]["editable"] ? settings["widget_properties"]["editable"]["active"] : false;
				
				if (edit_active)
					me.onChangeWidgetEditableType(editable_type_select[0], window.event);
			}
			
			//update db brokers, drivers and tables after widget data gets loaded, but only if a data-widget-group-list|form
			if (widget.is("[data-widget-group-list], [data-widget-group-form]"))
				updateWidgetDBBrokers(widget_properties_ul_elm);
			
			//load graph properties
			if (widget.is("[data-widget-graph]")) {
				//load load-type setting
				var load_type = "";
				
				if (widget[0].hasAttribute("data-widget-resources-load"))
					load_type = "data-widget-resources-load";
				else if (widget[0].hasAttribute("data-widget-item-resources-load"))
					load_type = "data-widget-item-resources-load";
				
				widget_properties_ul_elm.find(" > .widget-property-graph-load-type > select").val(load_type);
				
				//prepare legend settings
				var legend_elm = widget_properties_ul_elm.children(".widget-property-graph-legend");
				me.onChangeWidgetGroupTitleDisplay(legend_elm.find(" > .group-title > input")[0], window.event);
				
				//prepare title settings
				var title_elm = widget_properties_ul_elm.children(".widget-property-graph-title");
				me.onChangeWidgetGroupTitleDisplay(title_elm.find(" > .group-title > input")[0], window.event);
				
				//prepare sub_title settings
				var sub_title_elm = widget_properties_ul_elm.children(".widget-property-graph-sub-title");
				me.onChangeWidgetGroupTitleDisplay(sub_title_elm.find(" > .group-title > input")[0], window.event);
				
				//load data-sets
				var data_sets_props = settings["widget_properties"].hasOwnProperty("graph") && settings["widget_properties"]["graph"] ? settings["widget_properties"]["graph"]["data_sets"] : null;
				var data_sets_elm = widget_properties_ul_elm.children(".widget-property-graph-data-sets");
				
				if (data_sets_props) {
					var add_icon = widget_properties_ul_elm.find(" > .widget-property-graph-data-sets > .group-title > .widget-group-item-add");
					
					if ($.isPlainObject(data_sets_props))
						data_sets_props = [data_sets_props];
					
					$.each(data_sets_props, function(idx, data_set_props) {
						if ($.isPlainObject(data_set_props) && !$.isEmptyObject(data_set_props)) {
							var li = me.addWidgetGraphDataSet(add_icon[0], window.event, true);
							
							//prepare chart_type
							if (data_set_props["chart_type"] == "bar")
								data_set_props["chart_type"] = "vertical_bar";
							
							//set properties
							loadWidgetSettingsIntoFields(li, data_set_props, "widget_properties[graph][data_sets][idx]");
							
							me.onChangeWidgetGraphDataSetDataType( li.find(".widget-property-graph-data-set-data-type select")[0], window.event);
						}
					});
				}
			}
			
			//load calendar properties
			if (widget.is("[data-widget-calendar]")) {
				//load load-type setting
				var load_type = "";
				
				if (widget[0].hasAttribute("data-widget-resources-load"))
					load_type = "data-widget-resources-load";
				else if (widget[0].hasAttribute("data-widget-item-resources-load"))
					load_type = "data-widget-item-resources-load";
				
				widget_properties_ul_elm.find(" > .widget-property-calendar-load-type > select").val(load_type);
				
				//prepare header-toolbar settings
				var header_toolbar_elm = widget_properties_ul_elm.children(".widget-property-calendar-header-toolbar");
				me.onChangeWidgetGroupTitleDisplay(header_toolbar_elm.find(" > .group-title > input")[0], window.event);
				
				//prepare footer-toolbar settings
				var footer_toolbar_elm = widget_properties_ul_elm.children(".widget-property-calendar-footer-toolbar");
				me.onChangeWidgetGroupTitleDisplay(footer_toolbar_elm.find(" > .group-title > input")[0], window.event);
				
				//load data-sets
				var data_sets_props = settings["widget_properties"].hasOwnProperty("calendar") && settings["widget_properties"]["calendar"] ? settings["widget_properties"]["calendar"]["data_sets"] : null;
				var data_sets_elm = widget_properties_ul_elm.children(".widget-property-calendar-data-sets");
				
				if (data_sets_props) {
					var add_icon = widget_properties_ul_elm.find(" > .widget-property-calendar-data-sets > .group-title > .widget-group-item-add");
					
					if ($.isPlainObject(data_sets_props))
						data_sets_props = [data_sets_props];
					
					$.each(data_sets_props, function(idx, data_set_props) {
						if ($.isPlainObject(data_set_props) && !$.isEmptyObject(data_set_props)) {
							var li = me.addWidgetCalendarDataSet(add_icon[0], window.event, true);
							
							//set properties
							var name = li.find(" > ul > .widget-property-calendar-data-set-data-type > select").attr("name");
							var pos = li.parent().attr("prefix").length + 1; //'+ 1' bc: 'prefix['
							var index = name.substr(pos, name.indexOf("]", pos) - pos);
							
							loadWidgetSettingsIntoFields(li, data_set_props, "widget_properties[calendar][data_sets][" + index + "]");
							
							me.onChangeWidgetCalendarDataSetDataType( li.find(".widget-property-calendar-data-set-data-type select")[0], window.event);
							
							//prepare and load pks_attrs_names
							var data_pks_attrs_names_elm = li.find("li.widget-property-calendar-data-set-data-pks-attrs-names");
							loadWidgetSettingsAttrsNamesData(data_pks_attrs_names_elm, data_set_props["data_pks_attrs_names"]);
							
							data_pks_attrs_names_elm.find(" > ul > li:not(.empty-items)").each(function(idx, li) {
								me.prepareWidgetCalendarAttrName(li);
							});
						}
					});
				}
				
				//load calendar resources-data-sets
				var resources_data_sets_props = settings["widget_properties"].hasOwnProperty("calendar") && settings["widget_properties"]["calendar"] ? settings["widget_properties"]["calendar"]["resources_data_sets"] : null;
				var resources_data_sets_elm = widget_properties_ul_elm.children(".widget-property-calendar-resources-data-sets");
				
				if (resources_data_sets_props) {
					var add_icon = widget_properties_ul_elm.find(" > .widget-property-calendar-resources-data-sets > .group-title > .widget-group-item-add");
					
					if ($.isPlainObject(resources_data_sets_props))
						resources_data_sets_props = [resources_data_sets_props];
					
					$.each(resources_data_sets_props, function(idx, resources_data_set_props) {
						if ($.isPlainObject(resources_data_set_props) && !$.isEmptyObject(resources_data_set_props)) {
							var li = me.addWidgetCalendarResourcesDataSet(add_icon[0], window.event, true);
							
							//set properties
							var name = li.find(" > ul > .widget-property-calendar-resources-data-set-data-resource-name > select").attr("name");
							var pos = li.parent().attr("prefix").length + 1; //'+ 1' bc: 'prefix['
							var index = name.substr(pos, name.indexOf("]", pos) - pos);
							
							loadWidgetSettingsIntoFields(li, resources_data_set_props, "widget_properties[calendar][resources_data_sets][" + index + "]");
							
							//prepare and load pks_attrs_names
							var data_pks_attrs_names_elm = li.find("li.widget-property-calendar-resources-data-set-data-pks-attrs-names");
							loadWidgetSettingsAttrsNamesData(data_pks_attrs_names_elm, resources_data_set_props["data_pks_attrs_names"]);
							
							data_pks_attrs_names_elm.find(" > ul > li:not(.empty-items)").each(function(idx, li) {
								me.prepareWidgetCalendarAttrName(li);
							});
						}
					});
				}
				
				//prepare and load pks_attrs_names
				var attributes_name = settings["widget_properties"].hasOwnProperty("calendar") && settings["widget_properties"]["calendar"] ? settings["widget_properties"]["calendar"]["attributes_name"] : null;
				
				if (attributes_name) {
					var data_pks_attrs_names_elm = widget_properties_ul_elm.find(" > .widget-property-calendar-data-sets-settings > ul > li.widget-property-calendar-data-sets-data-pks-attrs-names");
					loadWidgetSettingsAttrsNamesData(data_pks_attrs_names_elm, attributes_name["pks"]);
					
					data_pks_attrs_names_elm.find(" > ul > li:not(.empty-items)").each(function(idx, li) {
						me.prepareWidgetCalendarAttrName(li);
					});
				}
			}
			
			//load matrix properties
			if (widget.is("[data-widget-matrix]")) {
				//load load-type setting
				var load_type = "";
				
				if (widget[0].hasAttribute("data-widget-resources-load"))
					load_type = "data-widget-resources-load";
				else if (widget[0].hasAttribute("data-widget-item-resources-load"))
					load_type = "data-widget-item-resources-load";
				
				widget_properties_ul_elm.find(" > .widget-property-matrix-load-type > select").val(load_type);
				
				//prepare and load fks_attrs_names
				var fks_attrs_names = settings["widget_properties"].hasOwnProperty("matrix") && settings["widget_properties"]["matrix"] ? settings["widget_properties"]["matrix"]["fks_attrs_names"] : null;
				
				if (fks_attrs_names) {
					var fks_attrs_names_elm = widget_properties_ul_elm.find(" > .widget-property-matrix-fks-attrs-names");
					var tbody = fks_attrs_names_elm.find(" > table > tbody");
					var empty_items = tbody.children("li.empty-items");
					
					tbody.children("li:not(.empty-items)").remove();
					empty_items.show();
					
					if ($.isArray(fks_attrs_names) && fks_attrs_names.length > 0) {
						var icon_add = fks_attrs_names_elm.children(".widget-item-add");
						empty_items.hide();
						
						for (var i = 0, t = fks_attrs_names.length; i < t; i++) {
							var fks_attrs_name = fks_attrs_names[i];
							
							if ($.isPlainObject(fks_attrs_name)) {
								var item = me.addWidgetMatrixFKResourceAttrName(icon_add[0], window.event);
								
								if (item) {
									item.find(".widget-attr-name input").val(fks_attrs_name["attr_name"]);
									item.find(".widget-axis select").val(fks_attrs_name["axis"]);
									item.find(".widget-fk-attr-name input").val(fks_attrs_name["fk_attr_name"]);
								}
							}
						}
					}
				}
			}
			
			if (widget.is("[data-widget-matrix], [data-widget-matrix-head-row], [data-widget-matrix-body-row], [data-widget-matrix-head-column], [data-widget-matrix-body-column]")) {
				//prepare and load pks_attrs_names
				var pks_attrs_names = settings["widget_properties"].hasOwnProperty("matrix") && settings["widget_properties"]["matrix"] ? settings["widget_properties"]["matrix"]["pks_attrs_names"] : null;
				
				if (pks_attrs_names) {
					var pks_attrs_names_elm = widget_properties_ul_elm.find(" > .widget-property-matrix-pks-attrs-names");
					loadWidgetSettingsAttrsNamesData(pks_attrs_names_elm, pks_attrs_names);
					
					pks_attrs_names_elm.find(" > ul > li:not(.empty-items)").each(function(idx, li) {
						me.prepareWidgetMatrixAttrName(li);
					});
				}
			}
		}
	}
	
	function loadWidgetResourcesSettingsData(widget, settings) {
		settings = settings ? settings : createWidgetSettingsData(widget);
		
		var menu_settings = ui_creator.getMenuSettings();
		var widget_settings_ul = menu_settings.find(" > .settings-widget > ul");
		var widget_resources_ul = widget_settings_ul.find(" > .widget-resources > ul");
		
		//console.log("loaded settings:");
		//console.log(settings);
		
		//add non standard resources type html
		if ($.isPlainObject(settings["widget_resources"]))
			for (var resources_type in settings["widget_resources"]) 
				if (resources_type) {
					var widget_resources_type_elm = widget_resources_ul.children(".widget-resources-" + resources_type);
					
					if (!widget_resources_type_elm[0]) {
						var html = getWidgetResourcesTypeSettingsHtml(resources_type, "", true);
						widget_resources_type_elm = $(html);
						widget_resources_type_elm.addClass("selected-widget-setting");
						widget_resources_ul.append(widget_resources_type_elm);
						
						prepareMenuSettingsEvents(widget_resources_type_elm);
					}
				}
		
		//load resources data
		var widget_resources_type_elms = widget_resources_ul.children(".widget-resources-type");
		
		$.each(widget_resources_type_elms, function(idx, widget_resources_type_elm) {
			widget_resources_type_elm = $(widget_resources_type_elm);
			var resources_type = widget_resources_type_elm.attr("data-resource-type");
			
			if (resources_type) {
				//load resource properties
				if ($.isPlainObject(settings["widget_properties"]) && !$.isEmptyObject(settings["widget_properties"])) {
					var widget_resources_type_properties_ul = widget_resources_type_elm.find(" > .widget-resources-type-properties > ul");
					loadWidgetSettingsIntoFields(widget_resources_type_properties_ul, settings["widget_properties"], "widget_properties");
				}
				
				//load resource items
				if ($.isPlainObject(settings["widget_resources"]) && settings["widget_resources"].hasOwnProperty(resources_type)) {
					var resources = settings["widget_resources"][resources_type];
					
					if (!$.isArray(resources))
						resources = [resources];
					
					var lis = widget_resources_type_elm.find(" > ul > li.widget-resource");
					var add_icon = widget_resources_type_elm.find(" > .group-title > .widget-group-item-add");
					
					for (var i = 0; i < resources.length; i++) {
						var resource = resources[i];
						var li = $(lis[i]);
						
						//check if there is any resource elm available and if not create a new one
						if (!li[0])
							li = me.addWidgetResource(add_icon[0], window.event);
						
						//update settings data in html elements
						loadWidgetSettingsIntoFields(li, resource, "widget_resources[" + resources_type + "][idx]");
						
						//update resource conditions
						if (resource.hasOwnProperty("conditions") && resource["conditions"]) {
							var add_conditions_icon = li.find(".widget-resource-conditions > .add-resource-condition");
							
							if ($.isPlainObject(resource["conditions"]))
								resource["conditions"] = [ resource["conditions"] ];
							
							if ($.isArray(resource["conditions"]))
								for (var j = 0, tj = resource["conditions"].length; j < tj; j++) {
									var condition = resource["conditions"][j];
									
									if ($.isPlainObject(condition)) {
										var tr = me.addWidgetResourceCondition(add_conditions_icon[0], window.event);
										
										if (condition.hasOwnProperty("name"))
											tr.find(" > .name > input").val(condition["name"]);
										
										if (condition.hasOwnProperty("type"))
											tr.find(" > .type > select").val(condition["type"]);
										
										if (condition.hasOwnProperty("value"))
											tr.find(" > .value > input").val(condition["value"]);
										
										if (condition.hasOwnProperty("case"))
											tr.find(" > .case > select").val(condition["case"]);
										
										if (condition.hasOwnProperty("operator"))
											tr.find(" > .operator > select").val(condition["operator"]);
									}
								}
						}
						
						//call onchange handler in case the resource is an url type
						me.changeWidgetResourceType(li.find(".widget-resource-type select")[0], window.event);
					}
				}
			}
		});
	}
	
	//load viewable, addable, editable, removable, multiple_items_actions permissions
	function loadWidgetSettingsActionPermissionsData(widget_permissions_elm, data) {
		var types = ["show", "hide", "remove"];
		
		for (var i = 0, t = types.length; i < t; i++) {
			var type = types[i];
			var type_permissions = null;
			
			if ($.isPlainObject(data)) {
				type_permissions = data[type];
				
				if (type == "show" && !data.hasOwnProperty(type)) {
					if (data.hasOwnProperty("access"))
						type_permissions = data["access"];
					else
						type_permissions = data["view"];
				}
			}
			
			var widget_permissions_type_ul_elm = widget_permissions_elm.find(" > .widget-permission-" + type + " > ul");
			loadWidgetSettingsPermissionsData(widget_permissions_type_ul_elm, type_permissions); //call this method even if type_permissions var is null, so we can delete the old permissions from UI
		}
	}
	
	function loadWidgetSettingsPermissionsData(widget_permissions_type_ul_elm, type_permissions) {
		var resources_elm = widget_permissions_type_ul_elm.children(".widget-permission-valid-resources");
		var values_elm = widget_permissions_type_ul_elm.children(".widget-permission-valid-values");
		var user_types_elm = widget_permissions_type_ul_elm.children(".widget-permission-user-types");
		var add_resource_icon = resources_elm.children(".widget-item-add");
		var add_value_icon = values_elm.children(".widget-item-add");
		var add_user_type_icon = user_types_elm.children(".widget-item-add");
		var resources_ul = resources_elm.children("ul");
		var values_ul = values_elm.children("ul");
		var user_types_ul = user_types_elm.children("ul");
		
		//delete old permissions
		resources_ul.children("li:not(.empty-items)").remove();
		values_ul.children("li:not(.empty-items)").remove();
		user_types_ul.children("li:not(.empty-items)").remove();
		
		//prepare permissions
		if (type_permissions && !$.isPlainObject(type_permissions))
			type_permissions = {user_type_ids: type_permissions};
		
		//add new permissions
		if ($.isPlainObject(type_permissions)) {
			var resources = type_permissions.hasOwnProperty("resources") ? type_permissions["resources"] : [];
			var values = type_permissions.hasOwnProperty("values") ? type_permissions["values"] : [];
			var user_type_ids = type_permissions.hasOwnProperty("user_type_ids") ? type_permissions["user_type_ids"] : [];
			
			resources = $.isArray(resources) ? resources : [resources];
			values = $.isArray(values) ? values : [values];
			user_type_ids = $.isArray(user_type_ids) ? user_type_ids : [user_type_ids];
			
			//add resources references
			for (var i = 0, t = resources.length; i < t; i++) {
				var resource = resources[i];
				
				//if is string
				if (!$.isPlainObject(resource))
					resource = {name: resource};
				
				if (resource["name"] || $.isNumeric(resource["name"])) {
					var item = me.addWidgetResourceReference(add_resource_icon[0], window.event);
					loadWidgetSettingValueIntoSwappedField(item, resource["name"]);
				}
			}
			
			//add values
			for (var i = 0, t = values.length; i < t; i++) {
				var item = me.addWidgetValue(add_value_icon[0], window.event);
				item.children("input").val(values[i]);
			}
			
			//add user types
			for (var i = 0, t = user_type_ids.length; i < t; i++) {
				var user_type_id = user_type_ids[i];
				
				if ($.isNumeric(user_type_id)) {
					var item = me.addWidgetUserType(add_user_type_icon[0], window.event);
					loadWidgetSettingValueIntoSwappedField(item, user_type_id);
				}
			}
		}
	}
	
	//load: shown_attrs_names, pks_attrs_names, search_attrs_names, 
	function loadWidgetSettingsAttrsNamesData(elm, data) {
		var icon_add = elm.children(".widget-item-add");
		var ul = elm.children("ul");
		var empty_items = ul.children("li.empty-items");
		
		ul.children("li:not(.empty-items)").remove();
		empty_items.show();
		
		if (data) {
			if (typeof data == "string")
				data = data.replace(/;/g, ",").split(",");
			
			if ($.isArray(data) && data.length > 0) {
				empty_items.hide();
				
				for (var i = 0, t = data.length; i < t; i++) {
					var value = data[i];
					var item = me.addWidgetAttrName(icon_add[0], window.event);
					
					if (item)
						item.find("input.attr_name").val(value);
				}
			}
		}
	}
	
	//load: default_attrs_values 
	function loadWidgetSettingsAttrsNamesAndValuesData(elm, data) {
		var icon_add = elm.children(".widget-item-add");
		var ul = elm.children("ul");
		var empty_items = ul.children("li.empty-items");
		
		ul.children("li:not(.empty-items)").remove();
		empty_items.show();
		
		if ($.isPlainObject(data) && !$.isEmptyObject(data)) {
			empty_items.hide();
			
			for (var key in data) {
				var value = data[key];
				var item = me.addWidgetAttrNameAndValue(icon_add[0], window.event);
				
				if (item) {
					item.find("input.attr_name").val(key);
					item.find("input.attr_value").val(value);
				}
			}
		}
	}
	
	function loadWidgetSettingsIntoFields(elm, data, prefix) {
		if ($.isPlainObject(data)) {
			var fields = elm.find("input, select, textarea");
			var swap_parents = [];
			var swap_parents_values = [];
			
			//set values
			for (var i = 0, t = fields.length; i < t; i++) {
				var field = $(fields[i]);
				var field_name = field.attr("name");
				
				if (field_name) {
					//prepare key through prefix. Eg: get "name" from widget_resources[load][idx][name] where prefix is "widget_resources[load][idx]"
					var key = field_name.substr(prefix.length + 1); //prefix.length + 1: prefix + "["
					key = key.substr(0, key.length - 1); //key.length - 1: "]"
					
					if (key.length) {
						var exists = false;
						var value = null;
						
						//prepare key and find correspondent value
						if (data.hasOwnProperty(key)) {
							value = data[key];
							exists = true;
						}
						else if (key.indexOf("[") != -1 || key.indexOf("]") != -1) { //if is a composite key like: widget_properties[complete][load]
							var regex = /([^\[\]]+)/g;
							var m;
							var keys = [];
							var data_aux = data;
							
							while ((m = regex.exec(key)) !== null) {
								var k = m[0].replace(/^\s+/g, "").replace(/\s+$/g, "");
								
								if (k.length > 0) {
									var fc = k.substr(0, 1);
									var lc = k.substr(k.length - 1);
									
									if ((fc == '"' && lc == '"') || (fc == "'" && lc == "'"))
										k = k.substr(1, k.length - 2);
									
									if (k.length > 0)
										keys.push(k);
								}
							}
							
							for (var j = 0, tj = keys.length; j < tj; j++) {
								var k = keys[j];
								
								if ($.isPlainObject(data_aux) || $.isArray(data_aux)) {
									if (j + 1 == tj) {
										if (data_aux.hasOwnProperty(k)) {
											value = data_aux[k];
											exists = true;
										}
									}
									else
										data_aux = data_aux[k];
								}
								else
									break;
							}
						}
						//console.log("exists:"+exists+" => key: "+key+"; with value("+(typeof value)+"):"+value+";");
						
						//if value exists in data set it to input
						if (exists) {
							if (field.is("[type=checkbox], [type=radio]")) {
								if ((field[0].hasAttribute("value") && field.attr("value") == value) || (!field[0].hasAttribute("value") && value))
									field.prop("checked", true).attr("checked", "checked");
								else
									field.prop("checked", false).removeAttr("checked");
							}
							else
								field.val(value);
							
							//prepare swap-input-select
							var li = field.parent();
							
							if (li.hasClass("widget-swap-field") && $.inArray(li[0], swap_parents) == -1) {
								swap_parents.push(li[0]);
								swap_parents_values.push(value);
							}
							
							//prepare color field
							if (li.is(".color-style"))
								li.find(".color-selector").val(value);
						}
					}
				}
			}
			
			//prepare widget-swap-fields
			for (var i = 0, t = swap_parents.length; i < t; i++)
				loadWidgetSettingValueIntoSwappedField(swap_parents[i], swap_parents_values[i]);
		}
	}
	
	function loadWidgetSettingValueIntoSwappedField(parent, value) {
		parent = $(parent);
		var input = parent.children("input").first();
		var select = parent.children("select").first();
		
		input.val(value);
		select.val(value);
		
		if (select.val() == value) {
			if (!parent.hasClass("select-shown")) //show selects instead of inputs
				me.swapInputAndSelectFields(parent.children(".swap-input-select")[0], window.event);
			else
				updateFieldInSelectedWidget(select);
		}
		else if (parent.hasClass("select-shown")) //show input instead of select
			me.swapInputAndSelectFields(parent.children(".swap-input-select")[0], window.event);
		else
			updateFieldInSelectedWidget(input);
	}
	
	function createWidgetSettingsData(widget) {
		var settings = {};
		
		//prepare resources
		if (widget[0].hasAttribute("data-widget-resources")) {
			var resources = me.getWidgetResources(widget);
			
			if ($.isPlainObject(resources))
				settings["widget_resources"] = resources;
		}
		
		//prepare resource_value
		if (widget[0].hasAttribute("data-widget-resource-value")) {
			var widget_resource_value = me.getWidgetResourceValue(widget);
			
			if ($.isPlainObject(widget_resource_value)) {
				//prepare available_values
				/*
				 * 	available_values: {
				 * 		key: value,
				 * 		key: value,
				 * 		0: {
				 * 			name: resource name
				 * 			...
				 * 		},
				 * 		key: value,
				 * 		1: [{
				 * 			name: resource name
				 * 			...
				 * 		},
				 * 		{
				 * 			name: resource name
				 * 			...
				 * 		}],
				 * 	}
				 * 	available_values: [{...}, {...}]
				 */
				if (widget_resource_value["available_values"]) {
					var items = widget_resource_value["available_values"];
					var available_values = {};
					var index = 0;
					
					if (!$.isArray(items))
						items = [items];
					
					for (var i = 0, ti = items.length; i < ti; i++) {
						var item = items[i];
						
						if ($.isPlainObject(item) && !$.isEmptyObject(item)) {
							for (var k in item) {
								var v = item[k];
								
								if ($.isPlainObject(v))
									v = [v];
								
								if ($.isArray(v)) {
									for (var j = 0, tj = v.length; j < tj; j++)
										if ($.isPlainObject(v[j]) && (v[j]["name"] || $.isNumeric(v[j]["name"]))) {
											while (available_values.hasOwnProperty(index))
												index++;
											
											available_values[index] = v[j];
											index++;
										}
								}
								else
									available_values[k] = v;
							}
						}
					}
					
					widget_resource_value["available_values"] = available_values;
				}
				
				settings["widget_resource_value"] = widget_resource_value;
			}
			else
				settings["widget_resource_value"] = {}; //for the cases that we only want to show the resource it-self
		}
		
		//prepare permissions
		if (widget[0].hasAttribute("data-widget-permissions")) {
			var permissions = me.getWidgetPermissions(widget);
			
			if ($.isPlainObject(permissions))
				settings["widget_permissions"] = permissions;
		}
		
		//prepare properties
		if (widget[0].hasAttribute("data-widget-props")) {
			var properties = me.getWidgetProperties(widget);
			settings["widget_properties"] = properties;
			
			if (properties.hasOwnProperty("dependent_widgets_id")) {
				if (typeof properties["dependent_widgets_id"] == "string")
					properties["dependent_widgets_id"] = [ properties["dependent_widgets_id"] ];
				
				settings["widget_dependencies"] = properties["dependent_widgets_id"];
			}
		}
		
		if (!$.isPlainObject(settings["widget_properties"]))
			settings["widget_properties"] = {};
		
		//add data-widget-pks-attrs to settings["widget_properties"]["default_attrs_values"]
		if (widget.is("[data-widget-list], [data-widget-form], form") & widget[0].hasAttribute("data-widget-pks-attrs"))
			settings["widget_properties"]["default_attrs_values"] = me.getWidgetDefaultAttributesValues(widget);
		
		//prepare widget type
		var widget_types_attributes = getDefaultWidgetTypesAttributes();
		var current_types_attributes = [];
		
		for (var i = 0, t = widget_types_attributes.length; i < t; i++) {
			var widget_type_attribute = widget_types_attributes[i];
			
			if (widget[0].hasAttribute(widget_type_attribute))
				current_types_attributes.push(widget_type_attribute);
		}
		
		if (current_types_attributes.length > 0) {
			if (current_types_attributes.length == 1)
				settings["widget_properties"]["type"] = current_types_attributes[0];
			else 
				settings["widget_properties"]["type"] = current_types_attributes;
		}
		
		//prepare other properties according with widgets
		if (widget[0].hasAttribute("data-widget-group-list")) {
			var main_widget = me.getWidgetGroupMainWidget(widget);
			var main_widget_properties = main_widget ? me.getWidgetProperties(main_widget) : {};
			var shown_attrs_names = createWidgetSettingsDataShownAttributesNames(widget);
			var pks_attrs_names = main_widget_properties["pks_attrs_names"];
			
			var with_search = widget.find("[data-widget-search]").length > 0;
			var with_short_actions = widget.find("[data-widget-short-actions]").length > 0;
			
			var top_pagination_elm = widget.find("[data-widget-pagination], [data-widget-list]").first();
			var bottom_pagination_elm = widget.find("[data-widget-pagination], [data-widget-list]").last();
			var with_top_pagination = top_pagination_elm.is("[data-widget-pagination]");
			var with_bottom_pagination = bottom_pagination_elm.is("[data-widget-pagination]") && !top_pagination_elm.is(bottom_pagination_elm);
			
			var table_exists = widget.find("[data-widget-list-table], table").length > 0;
			var tree_exists = widget.find("[data-widget-list-tree]").length > 0;
			var list_type = table_exists && tree_exists ? "both" : (tree_exists ? "tree" : (table_exists ? "table" : null));
			
			settings["widget_properties"]["shown_attrs_names"] = shown_attrs_names;
			settings["widget_properties"]["pks_attrs_names"] = pks_attrs_names;
			
			settings["widget_properties"]["with_search"] = with_search;
			settings["widget_properties"]["with_short_actions"] = with_short_actions;
			settings["widget_properties"]["with_top_pagination"] = with_top_pagination;
			settings["widget_properties"]["with_bottom_pagination"] = with_bottom_pagination;
			settings["widget_properties"]["list_type"] = list_type;
			
			var actions_settings = createWidgetSettingsDataActions(widget);
			for (var k in actions_settings)
				settings["widget_properties"][k] = actions_settings[k];
		}
		else if (widget[0].hasAttribute("data-widget-group-form")) {
			var main_widget = me.getWidgetGroupMainWidget(widget);
			var main_widget_properties = main_widget ? me.getWidgetProperties(main_widget) : {};
			var shown_attrs_names = createWidgetSettingsDataShownAttributesNames(widget);
			var pks_attrs_names = main_widget_properties["pks_attrs_names"];
			
			settings["widget_properties"]["shown_attrs_names"] = shown_attrs_names;
			settings["widget_properties"]["pks_attrs_names"] = pks_attrs_names;
			
			var actions_settings = createWidgetSettingsDataActions(widget);
			for (var k in actions_settings)
				settings["widget_properties"][k] = actions_settings[k];
			
			delete settings["widget_properties"]["multiple_items_actions"];
		}
		else if (widget[0].hasAttribute("data-widget-list")) {
			var shown_attrs_names = createWidgetSettingsDataShownAttributesNames(widget);
			var pks_attrs_names = settings["widget_properties"]["pks_attrs_names"];
			
			settings["widget_properties"]["shown_attrs_names"] = shown_attrs_names;
			settings["widget_properties"]["pks_attrs_names"] = pks_attrs_names;
			
			var table_exists = widget.find("[data-widget-list-table], table").length > 0 || widget.is("[data-widget-list-table], table");
			var tree_exists = widget.find("[data-widget-list-tree]").length > 0 || widget.is("[data-widget-list-tree]");
			var list_type = table_exists && tree_exists ? "both" : (tree_exists ? "tree" : (table_exists ? "table" : null));
			
			var multiple_items_actions = widget.find("[data-widget-item-selected-checkbox]").length > 0;
			
			settings["widget_properties"]["list_type"] = list_type;
			
			var actions_settings = createWidgetSettingsDataActions(widget);
			for (var k in actions_settings)
				settings["widget_properties"][k] = actions_settings[k];
			
			settings["widget_properties"]["multiple_items_actions"] = multiple_items_actions ? 1 : false;
		}
		else if (widget[0].hasAttribute("data-widget-form")) {
			var shown_attrs_names = createWidgetSettingsDataShownAttributesNames(widget);
			var pks_attrs_names = settings["widget_properties"]["pks_attrs_names"];
			
			settings["widget_properties"]["shown_attrs_names"] = shown_attrs_names;
			settings["widget_properties"]["pks_attrs_names"] = pks_attrs_names;
			
			var actions_settings = createWidgetSettingsDataActions(widget);
			for (var k in actions_settings)
				settings["widget_properties"][k] = actions_settings[k];
			
			delete settings["widget_properties"]["multiple_items_actions"];
		}
		else if (widget[0].hasAttribute("data-widget-search")) {
			var input = widget.find("[data-widget-search-input]");
			var select = widget.find("[data-widget-search-select]");
			var multiple_btn = widget.find("[data-widget-search-multiple-button]");
			
			settings["widget_properties"]["search_with_input"] = input.length > 0 ? 1 : null;
			settings["widget_properties"]["search_with_select"] = select.length > 0 ? 1 : null;
			settings["widget_properties"]["search_with_user_choice"] = multiple_btn.length > 0 ? 1 : null;
			
			var search_attrs_names = null, search_type = null, search_case = null, search_operator = null;
			
			if (settings["widget_properties"]["search_with_input"]) {
				var btn_properties = me.getWidgetProperties(input);
				
				if (!search_attrs_names)
					search_attrs_names = btn_properties["search_attrs"];
				
				if (!search_type)
					search_type = btn_properties["search_type"];
				
				if (!search_case)
					search_case = btn_properties["search_case"];
				
				if (!search_operator)
					search_operator = btn_properties["search_operator"];
			}
			
			if (settings["widget_properties"]["search_with_select"]) {
				var btn_properties = me.getWidgetProperties(select);
				
				if (!search_attrs_names)
					search_attrs_names = btn_properties["search_attrs"];
				
				if (!search_type)
					search_type = btn_properties["search_type"];
				
				if (!search_case)
					search_case = btn_properties["search_case"];
				
				if (!search_operator)
					search_operator = btn_properties["search_operator"];
			}
			
			if (settings["widget_properties"]["search_with_user_choice"]) {
				var btn_properties = me.getWidgetProperties(multiple_btn);
				
				if (!search_attrs_names) {
					var options = widget.find("[data-widget-search-multiple] select option");
					search_attrs_names = "";
					
					$.each(options, function(idx, option) {
						var attr_name = $(option).attr("value");
						
						if (attr_name)
							search_attrs_names += (search_attrs_names ? "," : "") + attr_name;
					});
				}
				
				if (!search_type)
					search_type = btn_properties["search_type"];
				
				if (!search_case)
					search_case = btn_properties["search_case"];
				
				if (!search_operator)
					search_operator = btn_properties["search_operator"];
			}
			
			settings["widget_properties"]["search_attrs_names"] = typeof search_attrs_names == "string" ? search_attrs_names.replace(/;/g, ",").split(",") : null;
			settings["widget_properties"]["search_type"] = search_type;
			settings["widget_properties"]["search_case"] = search_case;
			settings["widget_properties"]["search_operator"] = search_operator;
		}
		else if (widget[0].hasAttribute("data-widget-search-input") || widget[0].hasAttribute("data-widget-search-select")) {
			var search_attrs_names = settings["widget_properties"]["search_attrs"];
			settings["widget_properties"]["search_attrs_names"] = typeof search_attrs_names == "string" ? search_attrs_names.replace(/;/g, ",").split(",") : null;;
			delete settings["widget_properties"]["search_attrs"];
		}
		else if (widget[0].hasAttribute("data-widget-item-head") || widget[0].hasAttribute("data-widget-item-column")) {
			settings["widget_properties"]["attribute_name"] = widget.attr("data-widget-item-attribute-name");
		}
		else if (widget[0].hasAttribute("data-widget-item-attribute-link-view") || widget[0].hasAttribute("data-widget-item-attribute-link-edit") || widget[0].hasAttribute("data-widget-button-add") || widget[0].hasAttribute("data-widget-item-button-edit") || widget[0].hasAttribute("data-widget-item-button-view")) {
			settings["widget_properties"]["on_click"] = widget.attr("onClick");
			settings["widget_properties"]["popup_id"] = widget.attr("data-widget-popup-id");
		}
		
		//prepare data-widget-resources-load and data-widget-item-resources-load
		if (widget[0].hasAttribute("data-widget-resources-load"))
			settings["widget_properties"]["load_type"] = "data-widget-resources-load";
		else if (widget[0].hasAttribute("data-widget-item-resources-load"))
			settings["widget_properties"]["load_type"] = "data-widget-item-resources-load";
		
		//console.log("createWidgetSettingsData:");
		//console.log(settings);
		
		return settings;
	}
	
	function createWidgetSettingsDataShownAttributesNames(widget) {
		var shown_attrs_names = [];
		var main_widget = me.getWidgetGroupMainWidget(widget);
		
		if (main_widget) {
			var columns = main_widget.find("[data-widget-item-head], [data-widget-item-column]");
			
			$.each(columns, function(idx, column) {
				column = $(column);
				var attr_name = column.attr("data-widget-item-attribute-name");
				
				if (!attr_name && column[0].hasAttribute("data-widget-resource-value")) {
					var widget_resource_value = me.getWidgetResourceValue(column);
					
					if (widget_resource_value && widget_resource_value["attribute"])
						attr_name = widget_resource_value["attribute"];
				}
				
				if (!attr_name) {
					var elms = column.find("[data-widget-resource-value]");
					
					for (var i = 0, t = elms.length; i < t; i++) {
						var widget_resource_value = me.getWidgetResourceValue( $(elms[i]) );
						
						if (widget_resource_value && widget_resource_value["attribute"]) {
							attr_name = widget_resource_value["attribute"];
							break;
						}
					}
				}
				
				if (attr_name && shown_attrs_names.indexOf(attr_name) == -1)
					shown_attrs_names.push(attr_name);
			});
		}
		
		return shown_attrs_names;
	}
	
	function createWidgetSettingsDataActions(widget) {
		var settings = {};
		var main_widget = me.getWidgetGroupMainWidget(widget);
		
		if (main_widget) {
			//prepare viewable
			var viewable_items = main_widget.find("[data-widget-item-attribute-link-view], [data-widget-item-attribute-field-view], [data-widget-item-button-view]");
			var with_view_button = viewable_items.filter("[data-widget-item-button-view]").length > 0;
			
			settings["viewable"] = {};
			settings["viewable"]["active"] = viewable_items.length > 0 ? 1 : false;
			settings["viewable"]["type"] = viewable_items.filter("[data-widget-item-attribute-link-view]").length > 0 ? "link" : (viewable_items.filter("[data-widget-item-attribute-field-view]").length > 0 ? "inline" : null); //link|inline|button. "Button" is deprecated bc is the same than "inline" but with view button
			settings["viewable"]["with_view_button"] = with_view_button;
			settings["viewable"]["permissions"] = createWidgetSettingsDataActionPermissions(viewable_items);
			
			//prepare addable
			var addable_items = widget.find("[data-widget-button-add], [data-widget-item-add], [data-widget-item-attribute-field-add], [data-widget-item-button-add], [data-widget-item-button-add-cancel]");
			var popup_add = addable_items.filter("[data-widget-button-add]").length > 0 && widget.find("[data-widget-popup][data-widget-popup-add]").length > 0;
			
			settings["addable"] = {};
			settings["addable"]["active"] = addable_items.length > 0 ? 1 : false;
			settings["addable"]["type"] = popup_add ? "popup" : (addable_items.filter("[data-widget-item-add], [data-widget-item-attribute-field-add], [data-widget-item-button-add], [data-widget-item-button-add-cancel]").length > 0 ? "inline" : null); //popup|inline
			settings["addable"]["permissions"] = createWidgetSettingsDataActionPermissions(addable_items);
			
			//prepare editable
			var editable_items = main_widget.find("[data-widget-item-attribute-link-edit], [data-widget-item-attribute-field-edit], [data-widget-item-button-update], [data-widget-item-button-toggle-inline-edit-view], [data-widget-item-button-edit]");
			var with_save_button = editable_items.filter("[data-widget-item-button-update]").length > 0;
			var with_edit_button = editable_items.filter("[data-widget-item-button-edit]").length > 0;
			var toggle_edit_view_fields_button = editable_items.filter("[data-widget-item-button-toggle-inline-edit-view]").length > 0;
			var with_auto_save = false;
			
			$.each(editable_items.filter("[data-widget-item-attribute-field-edit]"), function(idx, field) {
				var field = $(field);
				var possible_events = ["onBlur", "onChange", "onKeyUp", "onClick"];
				
				for (var i = 0, t = possible_events.length; i < t; i++) {
					var v = field.attr(possible_events[i]);
					
					if (typeof v == "string" && v.match(/MyWidgetResourceLib\.ItemHandler\.updateResourceItemAttribute/)) {
						with_auto_save = true;
						return false;
					}
				}
			});
			
			settings["editable"] = {};
			settings["editable"]["active"] = editable_items.length > 0 ? 1 : false;
			settings["editable"]["type"] = editable_items.filter("[data-widget-item-attribute-link-edit]").length > 0 ? "link" : (editable_items.filter("[data-widget-item-attribute-field-edit]").length > 0 ? "inline" : (with_edit_button ? "button" : null)); //link|inline|button
			settings["editable"]["with_auto_save"] = with_auto_save;
			settings["editable"]["with_save_button"] = with_save_button;
			settings["editable"]["with_edit_button"] = with_edit_button;
			settings["editable"]["toggle_edit_view_fields_button"] = toggle_edit_view_fields_button;
			settings["editable"]["permissions"] = createWidgetSettingsDataActionPermissions(editable_items);
			
			//prepare removable
			var removable_items = widget.find("[data-widget-item-button-remove]");
			
			settings["removable"] = {};
			settings["removable"]["active"] = removable_items.length > 0 ? 1 : false;
			settings["removable"]["permissions"] = createWidgetSettingsDataActionPermissions(removable_items);
			
			//prepare multiple_items_actions
			var short_actions_items = widget.find("[data-widget-button-multiple-remove], [data-widget-button-multiple-save]");
			var short_actions_remove = short_actions_items.filter("[data-widget-button-multiple-remove]").length > 0;
			var short_actions_save = short_actions_items.filter("[data-widget-button-multiple-save]").length > 0;
			
			settings["multiple_items_actions"] = {};
			settings["multiple_items_actions"]["type"] = short_actions_remove && short_actions_save ? "both" : (short_actions_remove ? "remove" : (short_actions_save ? "save" : "")); //remove|save|both
			settings["multiple_items_actions"]["permissions"] = createWidgetSettingsDataActionPermissions(short_actions_items);
		}
		
		return settings;
	}
	
	function createWidgetSettingsDataActionPermissions(items) {
		var found_permissions = null;
		
		$.each(items, function(idx, item) {
			var permissions = me.getWidgetPermissions( $(item) );
			
			if ($.isPlainObject(permissions)) {
				found_permissions = permissions;
				return false;
			}
		});
		
		return found_permissions;
	}
	
	/* SAVE FUNCTIONS */
	
	function updateFieldInSelectedWidget(elm) {
		if (update_field_in_selected_widget_active) {
			//console.log(new Error("updateFieldInSelectedWidget"));
			
			//get fields name and create a object with the user data but only for the parent section settings
			elm = elm instanceof jQuery ? elm : $(elm);
			var available_selectors = {
				".group.widget-resources": "widget_resources", 
				".group.widget-display-resource-value": "widget_resource_value", 
				".group.widget-dependencies": "widget_dependencies", 
				".group.widget-permissions": "widget_permissions", 
				".group.widget-properties": "widget_properties"
			};
			
			var main_parent_selector = "";
			for (var k in available_selectors)
				main_parent_selector += (main_parent_selector ? ", " : "") + k;
			
			var main_parent = elm.closest(main_parent_selector);
			var main_parent_class = main_parent.attr("class");
			
			var section_selector = null;
			for (var k in available_selectors)
				if (main_parent.is(k)) {
					section_selector = k;
					break;
				}
			//console.log(section_selector);
			
			if (section_selector) {
				var setting_name = available_selectors[section_selector];
				var widget = $(selected_widget);
				var widget_attr_name = "data-" + setting_name.replace(/_/g, "-");
				var settings = getWidgetSettingsData(main_parent, section_selector);
				var section_settings = settings[setting_name];
				
				//save new widget_properties from resources
				if (setting_name == "widget_resources" && $.isPlainObject(settings["widget_properties"])) {
					var current_properties = me.getWidgetProperties(widget);
					
					//prepare load type
					widget.removeAttr("data-widget-resources-load").removeAttr("data-widget-item-resources-load");
					
					if (settings["widget_properties"]["load_type"])
						widget.attr(settings["widget_properties"]["load_type"], "");
					
					//delete property that don't matter, like the above one.
					settings["widget_properties"] = filterWidgetPropertiesSettingsBySelectedWidget(widget, settings["widget_properties"]);
					
					//prepare other properties
					for (var k in settings["widget_properties"])
						current_properties[k] = settings["widget_properties"][k];
					
					//clean empty settings
					current_properties = cleanWidgetEmptySettings(current_properties);
					
					//console.log("saving properties: data-widget-props when is inside of widget_resources");
					//console.log(current_properties);
					
					if (current_properties == null) 
						widget.removeAttr("data-widget-props");
					else {
						var json = JSON.stringify(current_properties);
						widget.attr("data-widget-props", json);
					}
				}
				
				//save section settings
				if (setting_name == "widget_resources") {
					widget_attr_name = "data-widget-resources";
					
					//discard the resources with empty names
					if ($.isPlainObject(section_settings))
						for (var resources_type in section_settings) {
							var resources = section_settings[resources_type];
							var new_resources = [];
							
							$.each(resources, function(idx, resource) {
								if (resource["name"])
									new_resources.push(resource);
							});
							
							section_settings[resources_type] = new_resources;
						}
				}
				else if (setting_name == "widget_resource_value") {
					var resource_display = main_parent.find(".widget-display-resource-value-resource-display > select").val();
					
					if (resource_display == "")
						section_settings = null;
					else if ($.isPlainObject(section_settings)) {
						if (resource_display == "resource" && section_settings.hasOwnProperty("attribute")) {
							section_settings["attribute"] = null;
							delete section_settings["attribute"];
						}
						
						if (section_settings.hasOwnProperty("available_values")) {
							var av = {};
							
							$.each(section_settings["available_values"], function(idx, item) {
								if ($.isPlainObject(item))
									av[ item["key"] ] = item["value"];
								else
									av[idx] = {name: item};
							});
							
							//console.log(JSON.stringify(av));
							section_settings["available_values"] = av;
						}
					}
				}
				else if (setting_name == "widget_dependencies") {
					var current_properties = me.getWidgetProperties(widget);
					
					if (section_settings)
						current_properties["dependent_widgets_id"] = section_settings;
					else
						delete current_properties["dependent_widgets_id"];
					
					section_settings = current_properties;
					widget_attr_name = "data-widget-props";
				}
				else if (setting_name == "widget_properties") {
					if (!$.isPlainObject(section_settings))
						section_settings = {};
					
					//add some attributes to widget for some specific settings, like: popup_id (data-widget-popup-id), on_click (onClick), attribute_name (data-widget-item-attribute-name)...
					updateWidgetPropertiesSettingsInSelectedWidget(widget, section_settings);
					
					//add dependent_widgets_id
					var current_properties = me.getWidgetProperties(widget);
					
					if (current_properties && current_properties.hasOwnProperty("dependent_widgets_id"))
						section_settings["dependent_widgets_id"] = current_properties["dependent_widgets_id"];
					
					//add the properties in the widget-resources
					var resources_settings = getWidgetSettingsData(main_parent, ".group.widget-resources");
					
					if ($.isPlainObject(resources_settings["widget_properties"]))
						for (var k in resources_settings["widget_properties"])
							section_settings[k] = resources_settings["widget_properties"][k];
					
					//prepare default_attrs_values
					var default_attrs_values = section_settings["default_attrs_values"];
					
					if ($.isPlainObject(default_attrs_values) && !$.isEmptyObject(default_attrs_values))
						widget.attr("data-widget-pks-attrs", JSON.stringify(default_attrs_values));
					else
						widget.removeAttr("data-widget-pks-attrs");
					
					//prepare calendar data_sets, converting to array if not yet
					var data_sets_props = section_settings.hasOwnProperty("calendar") && section_settings["calendar"] ? section_settings["calendar"]["data_sets"] : null;
					
					if (data_sets_props && !$.isArray(data_sets_props)) {
						var new_data_sets = [];
						
						$.each(data_sets_props, function(idx, data_set_props) {
							new_data_sets.push(data_set_props);
						});
						
						section_settings["calendar"]["data_sets"] = new_data_sets;
					}
					
					//prepare calendar resources_data_sets, converting to array if not yet
					var resources_data_sets_props = section_settings.hasOwnProperty("calendar") && section_settings["calendar"] ? section_settings["calendar"]["resources_data_sets"] : null;
					
					if (resources_data_sets_props && !$.isArray(resources_data_sets_props)) {
						var new_resources_data_sets = [];
						
						$.each(resources_data_sets_props, function(idx, resources_data_set_props) {
							new_resources_data_sets.push(resources_data_set_props);
						});
						
						section_settings["calendar"]["resources_data_sets"] = new_resources_data_sets;
					}
					
					//prepare matrix data_sets, converting to array if not yet
					var data_sets_props = section_settings.hasOwnProperty("matrix") && section_settings["matrix"] ? section_settings["matrix"]["data_sets"] : null;
					
					if (data_sets_props && !$.isArray(data_sets_props)) {
						var new_data_sets = [];
						
						$.each(data_sets_props, function(idx, data_set_props) {
							new_data_sets.push(data_set_props);
						});
						
						section_settings["matrix"]["data_sets"] = new_data_sets;
					}
					
					//delete property that don't matter, like the above one.
					section_settings = filterWidgetPropertiesSettingsBySelectedWidget(widget, section_settings);
					
					widget_attr_name = "data-widget-props";
				}
				
				if (typeof section_settings != "undefined") {
					//clean empty settings
					section_settings = cleanWidgetEmptySettings(section_settings);
					
					//allow data-widget-resource-value with empty value
					if (setting_name == "widget_resource_value" && !$.isPlainObject(section_settings)) {
						var resource_display = main_parent.find(".widget-display-resource-value-resource-display > select").val();
						
						if (resource_display == "resource")
							section_settings = "";
					}
					
					//set widget attributes
					if (section_settings == null)
						widget.removeAttr(widget_attr_name);
					else {
						var json = $.isPlainObject(section_settings) ? JSON.stringify(section_settings) : section_settings; //for the cases that section_settings == "", like in the widget_resource_value example above
						widget.attr(widget_attr_name, json);
					}
				}
				else
					widget.removeAttr(widget_attr_name);
				
				onChangeSelectedWidget();
				
				//console.log("Saving settings:" + widget_attr_name);
				//console.log(settings);
				//console.log(section_settings);
			}
		}
	}
	
	function onChangeSelectedWidget() {
		if (update_field_in_selected_widget_active) {
			var curr_selected_widget = $(selected_widget);
			
			on_update_field_in_selected_widget_timeout_id && clearTimeout(on_update_field_in_selected_widget_timeout_id);
			
			on_update_field_in_selected_widget_timeout_id = setTimeout(function() {
				on_update_field_in_selected_widget_timeout_id && clearTimeout(on_update_field_in_selected_widget_timeout_id);
				on_update_field_in_selected_widget_timeout_id = null;
				
				//call callback
				if (typeof ui_creator.options.on_template_widgets_layout_changed_func == "function")
					ui_creator.options.on_template_widgets_layout_changed_func(curr_selected_widget);
			}, 500);
		}
	}
	
	function updateWidgetPropertiesSettingsInSelectedWidget(widget, widget_properties) {
		if (widget[0].hasAttribute("data-widget-button-add") 
			|| widget[0].hasAttribute("data-widget-item-attribute-link-view") 
			|| widget[0].hasAttribute("data-widget-item-attribute-link-edit") 
			|| widget[0].hasAttribute("data-widget-item-button-edit") 
			|| widget[0].hasAttribute("data-widget-item-button-view")
		) {
			if (widget_properties.hasOwnProperty("on_click")) {
				if (widget_properties["on_click"]) {
					//convert to string
					if ($.isArray(widget_properties["on_click"])) {
						var str = "";
						
						for (var i = 0, t = widget_properties["on_click"].length; i < t; i++) {
							var v = widget_properties["on_click"][i];
							v = v.replace(/\s*;+\s*$/g, "").replace(/(^\s+|\s+$)/g, "");
							
							if (v || $.isNumeric(v))
								str += (str == "" || str.match(/;\s*$/) ? "" : "; ") + v;
						}
						
						widget_properties["on_click"] = str;
					}
					
					widget.attr("onClick", widget_properties["on_click"]);
				}
				else
					widget.removeAttr("onClick");
			}
			
			if (widget_properties.hasOwnProperty("popup_id")) {
				if (widget_properties["popup_id"])
					widget.attr("data-widget-popup-id", widget_properties["popup_id"]);
				else
					widget.removeAttr("data-widget-popup-id");
			}
		}
		
		if (widget[0].hasAttribute("data-widget-item-attribute-name") 
			|| widget[0].hasAttribute("data-widget-item-column") 
			|| widget[0].hasAttribute("data-widget-item-head")
		) {
			if (widget_properties.hasOwnProperty("attribute_name")) {
				if (widget_properties["attribute_name"])
					widget.attr("data-widget-item-attribute-name", widget_properties["attribute_name"]);
				else
					widget.removeAttr("data-widget-item-attribute-name");
			}
		}
	}
	
	function filterWidgetPropertiesSettingsBySelectedWidget(widget, widget_properties) {
		var to_filter = {
			"dependent_widgets_id": "dependent_widgets_id", 
			"load": "load", 
			"complete": "complete",
			"end": "end"
		};
		
		if (widget[0].hasAttribute("data-widget-group-list") || widget[0].hasAttribute("data-widget-group-form")) {
			to_filter["db_broker"] = "db_broker";
			to_filter["db_driver"] = "db_driver";
			to_filter["db_type"] = "db_type";
			to_filter["db_table"] = "db_table";
			to_filter["db_table_alias"] = "db_table_alias";
		}
		
		if (widget[0].hasAttribute("data-widget-list")) {
			to_filter["empty_message"] = "empty_message";
			to_filter["loading_message"] = "loading_message";
			to_filter["items_limit_per_page"] = "items_limit_per_page";
			to_filter["starting_page_number"] = "starting_page_number";
			to_filter["pks_attrs_names"] = "pks_attrs_names";
			to_filter["check"] = "check";
		}
		
		if (widget[0].hasAttribute("data-widget-list-caption"))
			to_filter["draw"] = "draw";
		
		if (widget[0].hasAttribute("data-widget-form")) {
			to_filter["pks_attrs_names"] = "pks_attrs_names";
			to_filter["check"] = "check";
			to_filter["enter_key_press"] = "enter_key_press";
			to_filter["enter_key_press_button"] = "enter_key_press_button";
		}
		
		if (widget[0].hasAttribute("data-widget-search-input") || widget[0].hasAttribute("data-widget-search-select")) {
			to_filter["search_attrs_names"] = "search_attrs";
			to_filter["search_type"] = "search_type";
			to_filter["search_case"] = "search_case";
			to_filter["search_operator"] = "search_operator";
		}
		
		if (widget[0].hasAttribute("data-widget-search-multiple-button")) {
			to_filter["search_type"] = "search_type";
			to_filter["search_case"] = "search_case";
			to_filter["search_operator"] = "search_operator";
		}
		
		if (widget[0].hasAttribute("data-widget-button-multiple-remove") || widget[0].hasAttribute("data-widget-button-multiple-save")) {
			to_filter["empty_message"] = "empty_message";
		}
		
		if (widget[0].hasAttribute("data-widget-pagination")) {
			to_filter["number_of_pages_to_show_at_once"] = "number_of_pages_to_show_at_once";
		}
		
		if (widget[0].hasAttribute("data-widget-pagination-go-to-page-dropdown")) {
			to_filter["get"] = "get";
			to_filter["set"] = "set";
		}
		
		if (widget[0].hasAttribute("data-widget-popup")) {
			to_filter["show"] = "show";
			to_filter["hide"] = "hide";
		}
		
		if (widget[0].hasAttribute("data-widget-html-node")) {
			to_filter["filter_resource_html_selector"] = "filter_resource_html_selector";
		}
		
		if (widget.is("select")) {
			to_filter["load_resource_type"] = "load_resource_type";
		}
		
		if (widget[0].hasAttribute("data-widget-graph"))
			to_filter["graph"] = "graph";
		
		if (widget[0].hasAttribute("data-widget-calendar"))
			to_filter["calendar"] = "calendar";
		
		if (widget[0].hasAttribute("data-widget-matrix") || widget[0].hasAttribute("data-widget-matrix-head-row") || widget[0].hasAttribute("data-widget-matrix-body-row") || widget[0].hasAttribute("data-widget-matrix-head-column") || widget[0].hasAttribute("data-widget-matrix-body-column"))
			to_filter["matrix"] = "matrix";
		
		//filter widget_properties according with to_filter array
		var props = {};
		
		for (var key in to_filter) {
			var new_key = to_filter[key];
			
			if (widget_properties.hasOwnProperty(key)) {
				//convert search_attrs array to string
				if (key == "shown_attrs_names" || key == "pks_attrs_names" || key == "search_attrs_names") {
					if ($.isArray(widget_properties[key]))
						widget_properties[key] = widget_properties[key].join(",");
				}
				
				props[new_key] = widget_properties[key];
			}
		}
		
		return props;
	}
	
	function cleanWidgetEmptySettings(settings) {
		if ($.isPlainObject(settings)) {
			var new_settings = {};
			
			for (var k in settings) {
				var v = cleanWidgetEmptySettings(settings[k]);
				
				if (v !== null)
					new_settings[k] = v;
			}
			
			return !$.isEmptyObject(new_settings) ? new_settings : null;
		}
		else if ($.isArray(settings)) {
			var new_settings = [];
			
			for (var i = 0, t = settings.length; i < t; i++) {
				var v = cleanWidgetEmptySettings(settings[i]);
				
				if (v !== null)
					new_settings.push(v);
			}
			
			return new_settings.length > 0 ? new_settings : null;
		}
		else if (settings != "" || $.isNumeric(settings))
			return settings;
		
		return null;
	}
	
	//get fields name and create a object with the user data
	function getWidgetSettingsData(elm, selector) {
		elm = $(elm);
		var widget_settings = elm.closest(".settings-widget");
		var selector_elm = selector ? widget_settings.find(selector) : widget_settings;
		var inputs = selector_elm.find("input, select, textarea");
		var query_string = "";
		var prefix_names_to_convert_to_array = [];
		var prefix_names_indexes = {};
		
		for (var i = 0, t = inputs.length; i < t; i++) {
			var input = inputs[i];
			var name = input.name;
			
			if (name) {
				input = $(input);
				var value = input.val();
				var li = input.parent().closest("li");
				var valid = true;
				
				if (input.is("[type=checkbox], [type=radio]")) {
					if (input.is(":checked"))
						value = input[0].hasAttribute("value") ? value : 1;
					else
						valid = false;
				}
				
				if (valid && li.hasClass("widget-swap-field")) {
					var style = window.getComputedStyle(input[0]);
	    				
	    				if (style.display === "none") //if swap field and is not shown, it means the user is using the field (select or input).
						valid = false;
				}
				
				if (valid) {
					//prepare [idx]
					var pos = name.indexOf("[idx]");
					
					if (pos != -1) {
						var ul = li.parent();
						
						while (ul[0] && ul.children("li.empty-items").length == 0) {
							li = ul.parent();
							ul = li.parent();
						};
						
						var index = ul.children("li:not(.empty-items)").index(li);
						
						name = name.replace(/\[idx\]/g, "[" + index + "]");
						
						prefix_names_to_convert_to_array.push(name.substr(0, pos));
					}
					
					//prepare [tridx]
					var pos = name.indexOf("[tridx]");
					
					if (pos != -1) {
						var tr = input.parent().closest("tr");
						var table = tr.parent();
						var index = table.children("tr:not(.empty-items)").index(tr);
						
						name = name.replace(/\[tridx\]/g, "[" + index + "]");
						
						prefix_names_to_convert_to_array.push(name.substr(0, pos));
					}
					
					//prepare []
					do {
						var pos = name.indexOf("[]");
						
						if (pos != -1) {
							var prefix_name = name.substr(0, pos);
							var index = prefix_names_indexes.hasOwnProperty(prefix_name) && $.isNumeric(prefix_names_indexes[prefix_name]) ? prefix_names_indexes[prefix_name] + 1 : 0;
							
							name = prefix_name + "[" + index + "]" + name.substr(pos + 2);
							
							prefix_names_indexes[prefix_name] = index;
							prefix_names_to_convert_to_array.push(prefix_name);
						}
					}
					while (pos != -1);
					
					query_string += (i > 0 ? "&" : "") + encodeURIComponent(name) + "=" + encodeURIComponent(value);
				}
			}
		}
		
		try {
			var settings = {};
			
			parse_str(query_string, settings);
			
			//converts all objects with [idx] into real arrays. Note that the parse_str will convert the query_string into an object and not an array.
			if (prefix_names_to_convert_to_array.length > 0 && !$.isEmptyObject(settings)) {
				for (var i = 0, ti = prefix_names_to_convert_to_array.length; i < ti; i++) {
					var str = prefix_names_to_convert_to_array[i];
					var keys = convertStringKeyToArray(str);
					
					if (keys && keys.length > 0) {
						keys = '["' + keys.join('"]["') + '"]';
						eval("var obj = settings" + keys + ";");
						
						if ($.isPlainObject(obj)) {
							//convert object to array
							var arr = [];
							
							for (var idx in obj)
								arr.push(obj[idx]);
							
							eval("settings" + keys + " = arr;");
						}
					}
				}
				//console.log("settings:");
				//console.log(settings);
			}
			
			return settings;
		}
		catch(e) {
			//alert(e);
			if (console && console.log) {
				console.log(e);
				console.log("Error executing getWidgetSettingsData function with query_string: " + query_string);
			}
		}
		
		return null;
	}
	
	//converts a string like: "[x][v]" or "x[u]" to an array with keys.
	function convertStringKeyToArray(str) {
		if (typeof str == "string" && (str.indexOf("[") != -1 || str.indexOf("]") != -1)) {
			var regex = /([^\[\]]+)/g;
			var m;
			var keys = [];
			
			while ((m = regex.exec(str)) !== null) {
				var k = m[0].replace(/^\s+/g, "").replace(/\s+$/g, "");
				
				if (k.length > 0) {
					var fc = k.substr(0, 1);
					var lc = k.substr(k.length - 1);
					
					if ((fc == '"' && lc == '"') || (fc == "'" && lc == "'"))
						k = k.substr(1, k.length - 2);
					
					if (k.length > 0)
						keys.push(k);
				}
			}
			
			return keys;
		}
		else if (str || $.isNumeric(str))
			return [str];
		
		return null;
	}
	
	/* UTILS FUNCTIONS */
	
	function getWidgetGroupDependentWidgetsId(widget, do_not_include_pagination) {
		var dependent_widgets_id = [];
		
		var widget_group = widget.closest("[data-widget-group-list], [data-widget-group-form]");
		var main_widget = me.getWidgetGroupMainWidget(widget);
		
		if (main_widget && main_widget.attr("id")) //note that the main_widget may be a data-widget-form
			dependent_widgets_id.push( main_widget.attr("id") );
		
		var lists = widget.find("[data-widget-list]" + (do_not_include_pagination ? "" : ", [data-widget-pagination]"));
		
		$.each(lists, function(idx, list_elm) {
			if (list_elm.id && (!main_widget || !main_widget.is(list_elm)))
				dependent_widgets_id.push(list_elm.id);
		});
		
		return dependent_widgets_id;
	}
	
	function getWidgetGroupDependentWidgetsIdForPopupForm(widget, do_not_include_pagination) {
		var dependent_widgets_id = getWidgetGroupDependentWidgetsId(widget, do_not_include_pagination);
		var main_widget_id = temp_main_widget_id;
		
		if (!main_widget_id) {
			var widget_group = widget.closest("[data-widget-group-list], [data-widget-group-form]");
			var widget_prefix_id= widget_group.is("[data-widget-group-list]") ? "widget_list_" : (widget_group.is("[data-widget-group-form]") ? "widget_form_" : null);
			var main_widget_suffix_id = me.getWidgetSuffix(widget);
			main_widget_id = widget_prefix_id && main_widget_suffix_id ? widget_prefix_id + main_widget_suffix_id : me.getNewMainWidgetId(widget, widget_prefix_id); //Note that getNewMainWidgetId is very important because when we call the getWidgetListHtml or getWidgetFormHtml the main widget doesn't exists yet, so we need to extrapulate the correspondent main widget id.
		}
		
		if (main_widget_id && $.inArray(main_widget_id, dependent_widgets_id) == -1)
			dependent_widgets_id.push(main_widget_id);
		
		return dependent_widgets_id;
	}
	
	function getWidgetResourcesName(widget) {
		var resources = me.getWidgetResources(widget);
		var resources_name = [];
		
		if ($.isPlainObject(resources))
			for (var resources_type in resources) {
				var names = getResourcesTypeResourcesName( resources[resources_type] );
				resources_name = resources_name.concat(names);
			}
		
		return resources_name;
	}
	
	function getResourcesTypeResourcesName(type_resources) {
		var resources_name = [];
		
		if ($.isArray(type_resources))
			for (var i = 0, t = type_resources.length; i < t; i++) {
				var type_resource = type_resources[i];
				
				if (type_resource["name"])
					resources_name.push(type_resource["name"]);
			}
		
		return resources_name;
	}
	
	function prepareWidgetResourcesTypeResourceReferenceField(elm, resource_name) {
		var widget_resources_load_elm = $(elm).parent().closest(".widget-resources-type").filter(".widget-resources-load");
		
		//if belongs to a load resource
		if (widget_resources_load_elm[0]) {
			var select = widget_resources_load_elm.find(" > .widget-resources-type-properties > ul > .widget-property-load-type > select");
			
			//if no resource name and there are no resources defined, then set the load_type to empty
			if (!resource_name) {
				var exists = false;
				var widget_resources = getWidgetSettingsData(widget_resources_load_elm, ".widget-resources > ul > .widget-resources-load > ul > .widget-resource > ul > li.widget-resource-reference");
				widget_resources = widget_resources["widget_resources"] ? widget_resources["widget_resources"]["load"] : null;
				
				if (widget_resources)
					for (var i = 0, t = widget_resources.length; i < t; i++) 
						if (widget_resources[i]["name"]) {
							exists = true;
							break;
						}
				
				if (!exists) {
					select.val("");
					me.onChangeWidgetResourcesTypeLoadTypeField(select[0]);
				}
			}
			//if resource is defined, then sets the load type if it is empty
			else if (select.val() == "") {
				var widget = $(selected_widget);
				var is_item = widget.closest("[data-widget-item], [data-widget-item-add]").length > 0;
				
				select.val(is_item ? "data-widget-item-resources-load" : "data-widget-resources-load");
				me.onChangeWidgetResourcesTypeLoadTypeField(select[0]);
			}
		}
	}
	
	function prepareResourceReferenceFromWidgetResourceValueSetting(elm) {
		elm = $(elm);
		var p = elm.parent();
		var resource_name = elm.val();
		var old_resource_name = elm.data("saved_resource_reference");
		var widget = $(selected_widget);
		var widget_has_multiple_resources_defined = false;
		var exists = false;
		var is_parent = false;
		var resource_name_loaded_in_widget = false;
		var resource_name_loaded_in_parent_widget = false;
		
		//save new resource name
		elm.data("saved_resource_reference", resource_name);
		
		//add new resource name to resources if does not exist yet
		if (resource_name) {
			var widget_resources = me.getWidgetResources(widget);
			
			//check if widget already contains this resource name
			if ($.isPlainObject(widget_resources) && $.isArray(widget_resources["load"])) {
				widget_has_multiple_resources_defined = widget_resources["load"].length > 1;
				
				for (var i = 0, t = widget_resources["load"].length; i < t; i++)
			 		if (widget_resources["load"][i]["name"] == resource_name) {
						exists = true;
						resource_name_loaded_in_widget = true;
						
						if (i == 0)
							is_parent = true;
						
						break;
					}
			}
			
			//check if main widget already contains this resource name
			var parent_widget = widget.parent().closest("[data-widget-resources]");
			
			if (parent_widget[0] && !exists) {
				var parent_widget_resources = me.getWidgetResources(parent_widget);
				
				if ($.isPlainObject(parent_widget_resources) && $.isArray(parent_widget_resources["load"]))
					for (var i = 0, t = parent_widget_resources["load"].length; i < t; i++)
				 		if (parent_widget_resources["load"][i]["name"] == resource_name) {
							exists = true;
							resource_name_loaded_in_parent_widget = true;
							
							if (i == 0)
								is_parent = true;
							
							break;
						}
			}
			
			//add a new load resource to resources element
			if (!exists) {
				//add resource name to data-widget-resources attribute
				var load_type = elm.parent().is(".widget-resource-reference") && elm.attr("name") == "widget_resource_value[available_values][idx]" ? "data-widget-item-resources-load" : null;
				addResourceReferenceToWidgetResourcesAttribute(widget, "load", resource_name, load_type); //TODO: add type of reload type according with element
				exists = true;
				
				//reload resources menu settings if widget is the selected_widget with the properties opened
				loadWidgetResourcesSettingsData(widget);
				
				//set parent to be used below
				if (!$.isPlainObject(widget_resources) || !$.isArray(widget_resources["load"]) || widget_resources["load"].length == 0)
					is_parent = true;
				
				//set load handler if none defined
				if (!resource_name_loaded_in_parent_widget) {
					var widget_properties = me.getWidgetProperties(widget);
					
					if (!widget_properties["load"]) {
						var load_handler_elm = p.closest(".menu-settings").find(".settings-widget > ul > .widget-resources > ul > .widget-resources-load > .widget-resources-type-properties > ul > .widget-property-handler-load");
						var load_handler_options = load_handler_elm.find(" > select > option");
						var default_load_handler = load_handler_options.filter("option[value!='']").first().val(); //chooses the default load handler which is MyWidgetResourceLib.FieldHandler.loadElementFieldsResource, if exists.
						
						if (default_load_handler) {
							//check if exists MyWidgetResourceLib.FieldHandler.loadFieldResource loadHandler, and if so, selects it.
							var better_load_handler = load_handler_options.filter("option[value='MyWidgetResourceLib.FieldHandler.loadFieldResource']").val();
							
							if (better_load_handler)
								default_load_handler = better_load_handler;
							
							//load default_load_handler
							loadWidgetSettingValueIntoSwappedField(load_handler_elm, default_load_handler);
						}
					}
				}
			}
			
			//prepare set parent (or with empty value) bc the resource comes from the parent
			if (is_parent)
				loadWidgetSettingValueIntoSwappedField(p, "");
		}
		
		//delete old resource from widget, but only if widget is not a main widget
		if (old_resource_name && old_resource_name != resource_name && !widget_has_multiple_resources_defined && !widget.is("[data-widget-list], [data-widget-form], table, form"))
			removeResourceReferenceFromWidgetResourcesAttribute(widget, old_resource_name);
		
		return {
			widget_has_multiple_resources_defined: widget_has_multiple_resources_defined,
			exists: exists,
			is_parent: is_parent,
			resource_name_loaded_in_widget: resource_name_loaded_in_widget,
			resource_name_loaded_in_parent_widget: resource_name_loaded_in_parent_widget
		};
	}
	
	function removeResourceReferencesFromWidgetResourcesAttribute(widget, resources_name) {
		if (!$.isArray(resources_name))
			resources_name = [resources_name];
		
		var repeated = [];
		
		for (var i = 0, t = resources_name.length; i < t; i++) {
			var resource_name = resources_name[i];
			
			if (repeated.indexOf(resource_name) == -1) {
				repeated.push(resource_name);
				
				//remove resource from widget
				removeResourceReferenceFromWidgetResourcesAttribute(widget, resource_name);
			}
		}
	}
	
	function removeResourceReferenceFromWidgetResourcesAttribute(widget, resource_name) {
		var resources = me.getWidgetResources(widget);
		
		if ($.isPlainObject(resources)) {
			for (var resources_type in resources) {
				var sub_resources = resources[resources_type];
				
				if ($.isArray(sub_resources)) {
					var new_sub_resources = [];
					
					for (var i = 0, t = sub_resources.length; i < t; i++) {
						var sub_resource = sub_resources[i];
						
						if (sub_resource["name"] != resource_name)
							new_sub_resources.push(sub_resource);
					}
					
					resources[resources_type] = new_sub_resources;
				}
			}
			
			widget.attr("data-widget-resources", JSON.stringify(resources));
			
			//remove attr data-widget-resources-load and data-widget-item-resources-load
			//if (!resources["load"] || $.isEmptyObject(resources["load"]) || ($.isArray(resources["load"]) && resources["load"].length == 0))
			//	widget.removeAttr("data-widget-resources-load").removeAttr("data-widget-item-resources-load");
			
			onChangeSelectedWidget();
		}
	}
	
	function removeWidget(widget) {
		ui_creator.deleteTemplateWidget(widget);
	}
	
	function removeMainWidgetResourcesByType(widget, resources_type) {
		var main_widget = me.getWidgetGroupMainWidget(widget);
		
		if (main_widget)
			removeWidgetResourcesByType(main_widget, resources_type);
	}
	
	function removeWidgetResourcesByType(widget, resources_type) {
		var resources = me.getWidgetResources(widget);
		
		if ($.isPlainObject(resources)) {
			var resources_name = getResourcesTypeResourcesName( resources[resources_type] );
			
			//remove resource references from widget attribute data-widget-resources
			removeResourceReferencesFromWidgetResourcesAttribute(widget, resources_name);
			
			//remove external sla resources
			removeSLAResourcesIfNotUsedAnymore(resources_name, widget);
			
			//remove correspondent complete callback, if exists
			removeWidgetCompleteCallbackByType(widget, resources_type);
		}
	}
	
	function removeSLAResourcesIfNotUsedAnymore(resources_name, widget_to_ignore, do_not_confirm) {
		if (typeof me.options.remove_sla_resource_func == "function") {
			//get resources name if not used in any other widget
			var filtered_resources_name = me.filterResourcesIfNotUsedAnymore(resources_name, widget_to_ignore); //this already removes the duplicates
			
			me.options.remove_sla_resource_func(filtered_resources_name, do_not_confirm);
		}
	}
	
	me.filterResourcesIfNotUsedAnymore = function(resources_name, widget_to_ignore) {
		if (!resources_name)
			resources_name = [];
		else if (!$.isArray(resources_name))
			resources_name = [resources_name];
		
		if (resources_name.length > 0) {
			//remove duplicates and empty strings
			resources_name = resources_name.filter(function(resource_name, index) {
				return resource_name && resources_name.indexOf(resource_name) === index;
			});
			
			//check if resource name is used in any other widget
			var template_widgets_iframe_body = ui_creator.getTemplateWidgetsIframeBody();
			
			if (template_widgets_iframe_body) {
				var widgets = template_widgets_iframe_body.find("[data-widget-resources], [data-widget-permissions]");
				var existent_resources_name = [];
				
				for (var i = 0, ti = widgets.length; i < ti; i++) {
					widget = $(widgets[i]);
					
					if (!widget_to_ignore || !widget.is(widget_to_ignore)) {
						//check if the resource is used in the data-widget-resources
						var resources_str = widget.attr("data-widget-resources");
						
						if (resources_str) {
							var widget_resources_name = getWidgetResourcesName(widget);
							
							//populate existent_resources_name
							for (var j = 0, tj = widget_resources_name.length; j < tj; j++) {
								var resource_name = widget_resources_name[j];
								
								if (resource_name && resources_name.indexOf(resource_name) != -1) {
									if (existent_resources_name.indexOf(resource_name) == -1)
										existent_resources_name.push(resource_name);
									
									if (resources_name.length == existent_resources_name.length)
										return [];
								}
							}
						}
						
						//check if the resource is used in the data-widget-permissions
						var resources_str = widget.attr("data-widget-permissions");
						
						if (resources_str) {
							var permissions = me.getWidgetPermissions(widget);
							
							if ($.isPlainObject(permissions)) {
								for (var k in permissions) {
									var v = permissions[k];
									
									if ($.isPlainObject(v) && v["resources"]) {
										var resources = v["resources"];
										
										resources = $.isArray(resources) ? resources : [resources];
										
										for (var j = 0, tj = resources.length; j < tj; j++) {
											var resource = resources[j];
											
											//if is string
											if (!$.isPlainObject(resource))
												resource = {name: resource};
											
											//populate existent_resources_name
											var resource_name = resource["name"];
											
											if (resource_name && resources_name.indexOf(resource_name) != -1) {
												if (existent_resources_name.indexOf(resource_name) == -1)
													existent_resources_name.push(resource_name);
												
												if (resources_name.length == existent_resources_name.length)
													return [];
											}
										}
									}
								}
							}
						}
					}
				}
				
				if (existent_resources_name.length > 0)
					resources_name = resources_name.filter(function(resource_name, index) {
						return existent_resources_name.indexOf(resource_name) == -1;
					});
			}
		}
		
		return resources_name;
	};
	
	/*
	 * resources_type: 
	 *	data-widget-list and data-widget-form: load (gets), add (insert), update, update_attribute, remove, 
	 */
	function addMainWidgetResourceByType(widget, resources_type, action_type, permissions) {
		var main_widget = me.getWidgetGroupMainWidget(widget);
		
		return main_widget ? addWidgetResourceByType(main_widget, resources_type, action_type, permissions) : null;
	}
	
	/*
	 * resources_type: 
	 *	data-widget-list and data-widget-form: load (gets), add (insert), update, update_attribute, remove (delete), 
	 *	data-widget-button-multiple-remove: remove (multiple_delete)
	 *	data-widget-button-multiple-save: save (multiple_save)
	 *	data-widget-pagination: load (count|total)
	 *	select (data-widget-item-attribute-field-edit, data-widget-item-attribute-field-add): load (fk)
	 */
	function addWidgetResourceByType(widget, resources_type, action_type, permissions, data, do_not_add_resource_to_widget_attribute) {
		var group_properties = me.getWidgetGroupProperties(widget);
		
		if (group_properties && group_properties["db_table"]) {
			var possible_resource_names = createResourceNames(action_type, group_properties["db_driver"], group_properties["db_table"], group_properties["db_table_alias"], permissions, data);
			//console.log("adding resource: " + (resource_names ? resource_names.join(",") : null));
			
			if (possible_resource_names && possible_resource_names.length > 0) {
				var resource_name = null;
				var resources = !do_not_add_resource_to_widget_attribute ? me.getWidgetResources(widget) : null;
				
				//check if already exists in widget resources
				if ($.isPlainObject(resources) && resources[resources_type]) {
					var resources_name = getResourcesTypeResourcesName( resources[resources_type] );
					resource_name = searchItemInArray(resources_name, possible_resource_names);
				}
				
				if (!resource_name) {
					//check if exists in external SLA panel through the me.options.get_resources_references_func function
					var items = executeCallback(me.options.get_resources_references_func, "existent_resources_references", true);
					
					if (items && $.isArray(items))
						resource_name = searchItemInArray(items, possible_resource_names);
					
					//if not exists create a new resource with resource_name
					if (!resource_name) {
						resource_name = possible_resource_names[0];
						addSLAResource(group_properties["db_broker"], group_properties["db_driver"], group_properties["db_type"], group_properties["db_table"], group_properties["db_table_alias"], action_type, resource_name, permissions, data);
					}
					
					//add to resource attribute
					if (!do_not_add_resource_to_widget_attribute) {
						//add resource name to data-widget-resources attribute
						addResourceReferenceToWidgetResourcesAttribute(widget, resources_type, resource_name);
						
						//reload resources menu settings if widget is the selected_widget with the properties opened
						if (widget.is(selected_widget))
							loadWidgetResourcesSettingsData(widget);
					}
				}
				
				return resource_name;
			}
			else
				ui_creator.showError("The action_type: '" + action_type + "' is unrecognized in addWidgetResourceByType method. Please talk with the sysadmin.");
		}
		else
			ui_creator.showError("Cannot create resource for empty DB Table in addWidgetResourceByType method. Please select a DB Table first.");
		
		return null;
	}
	
	function addResourceReferenceToWidgetResourcesAttribute(widget, resources_type, resource_name, load_type) {
		var resources = me.getWidgetResources(widget);
		
		if (!$.isPlainObject(resources))
			resources = {};
		
		if (!resources[resources_type])
			resources[resources_type] = [];
		
		resources[resources_type].push({name: resource_name});
		
		widget.attr("data-widget-resources", JSON.stringify(resources));
		
		//set load type
		if (resources_type == "load") {
			var is_item = widget.closest("[data-widget-item], [data-widget-item-add]").length > 0;
			
			if (load_type) {
				var widget_has_already_resources_defined = resources[resources_type].length > 1; //" > 1" bc it already contains the new resource
				
				if (widget_has_already_resources_defined && load_type == "data-widget-item-resources-load" && widget[0].hasAttribute("data-widget-resources-load")) {
					//DO nothing. if there are other resources which will load on page load, then do not overwrite it with data-widget-item-resources-load
				}
				else
					widget.removeAttr("data-widget-resources-load").removeAttr("data-widget-item-resources-load").attr(load_type, "");
			}
			else if (is_item)
				widget.attr("data-widget-item-resources-load", "").removeAttr("data-widget-resources-load");
			else
				widget.attr("data-widget-resources-load", "").removeAttr("data-widget-item-resources-load");
		}
		
		onChangeSelectedWidget();
	}
	
	function addSLAResource(db_broker, db_driver, db_type, db_table, db_table_alias, action_type, resource_name, permissions, data) {
		if (typeof me.options.add_sla_resource_func == "function")
			me.options.add_sla_resource_func(db_broker, db_driver, db_type, db_table, db_table_alias, action_type, resource_name, permissions, data);
	}
	
	function addMainWidgetActionPropertiesToLatestAddedWidgetResourcesAttribute(widget, resources_type, resource_properties) {
		var main_widget = me.getWidgetGroupMainWidget(widget);
		
		if (main_widget)
			addWidgetActionPropertiesToLatestAddedWidgetResourcesAttribute(main_widget, resources_type, resource_properties);
	}
	
	//add some properties to the latest resources_type that was added in the data-widget-resources
	function addWidgetActionPropertiesToLatestAddedWidgetResourcesAttribute(widget, resources_type, resource_properties) {
		if ($.isPlainObject(resource_properties) && !$.isEmptyObject(resource_properties)) {
			var resources = me.getWidgetResources(widget);
			
			if ($.isPlainObject(resources) && $.isArray(resources[resources_type])) {
				var length = resources[resources_type].length;
				
				if (length > 0 && $.isPlainObject(resources[resources_type][length - 1]))
					for (var k in resource_properties)
						resources[resources_type][length - 1][k] = resource_properties[k];
				
				widget.attr("data-widget-resources", JSON.stringify(resources));
				
				onChangeSelectedWidget();
				
				//reload resources menu settings if widget is the selected_widget with the properties opened
				if (widget.is(selected_widget))
					loadWidgetResourcesSettingsData(widget);
			}
		}
	}
	
	function addActionPropertiesToWidgetResourcesAttribute(widget, resources_type, resource_properties) {
		if ($.isPlainObject(resource_properties) && !$.isEmptyObject(resource_properties)) {
			var resources = me.getWidgetResources(widget);
			
			if ($.isPlainObject(resources) && $.isArray(resources[resources_type])) {
				for (var i = 0, t = resources[resources_type].length; i < t; i++)
					if ($.isPlainObject(resources[resources_type][i]))
						for (var k in resource_properties)
							resources[resources_type][i][k] = resource_properties[k];
				
				widget.attr("data-widget-resources", JSON.stringify(resources));
			}
		}
	}
	
	function getWidgetResourcesTypeDefaultProperties(resources_type, action_type) {
		if (resources_type == "add" || resources_type == "update" || resources_type == "update_attribute" || resources_type == "save" || resources_type == "remove") {
			var label = resources_type == "update_attribute" ? "update" : resources_type;
			var last_char = label[label.length - 1];
			var past_label = last_char == "e" ? label + "d" : (
				last_char == "y" ? label.substr(0, label.length - 1) + "ied" : label + "ed"
			);
			var props = {
				error_message: "Error trying to " + label + ". Please try again..."
			};
			
			if (resources_type != "update_attribute")
				props["success_message"] = past_label[0].toUpperCase() + past_label.substr(1) + " successfully!";
			
			if (resources_type == "remove")
				props["confirmation_message"] = "Are you sure you want to continue?";
			
			return props;
		}
		else if (resources_type == "load" && action_type == "get") {
			var props = {
				error_message: "No record available..."
			};
			
			return props;
		}
		
		return null;
	}
	
	//create resource name similar with Data-Access Ibatis Rules
	function createResourceNames(action_type, db_driver, db_table, db_table_alias, permissions, data) {
		if (typeof me.options.create_resource_names_func == "function") {
			var names = me.options.create_resource_names_func(action_type, db_driver, db_table, db_table_alias, permissions, data);
			
			if (names && !$.isArray(names))
				names = [names];
			
			return names;
		}
		
		return "";
	}
	
	
	function addWidgetWithButtonToggleInlineEditView(widget) {
		if (widget.is("[data-widget-form], form")) //in case of form inside of data-widget-group-form
			widget.attr("data-widget-with-button-toggle-inline-edit-view", "");
		else
			widget.find("[data-widget-item]").attr("data-widget-with-button-toggle-inline-edit-view", "");
		
		onChangeSelectedWidget();
		
		if (widget.is(selected_widget)) {
			var menu_settings = ui_creator.getMenuSettings();
			menu_settings.find(" > .settings-widget > ul > .widget-properties > ul > .widget-property-widget-type > select option[value='data-widget-with-button-toggle-inline-edit-view']").prop("selected", false).removeAttr("selected");
		}
	}
	
	function removeWidgetWithButtonToggleInlineEditView(widget) {
		if (widget.is("[data-widget-form], form")) //in case of form inside of data-widget-group-form
			widget.removeAttr("data-widget-with-button-toggle-inline-edit-view");
		else
			widget.find("[data-widget-item]").removeAttr("data-widget-with-button-toggle-inline-edit-view");
		
		onChangeSelectedWidget();
		
		if (widget.is(selected_widget)) {
			var menu_settings = ui_creator.getMenuSettings();
			menu_settings.find(" > .settings-widget > ul > .widget-properties > ul > .widget-property-widget-type > select option[value='data-widget-with-button-toggle-inline-edit-view']").prop("selected", false).removeAttr("selected");
		}
	}
	
	function removeMainWidgetCompleteCallbackByType(widget, resources_type) {
		//remove complete[resources_type] callback function from main_widget
		var main_widget = me.getWidgetGroupMainWidget(widget);
		
		if (main_widget && main_widget.is("[data-widget-list], [data-widget-form], form"))
			return removeWidgetCompleteCallbackByType(main_widget, resources_type);
		
		return false;
	}
	
	function removeWidgetCompleteCallbackByType(widget, resources_type) {
		//remove complete[resources_type] callback function from widget
		if (widget) {
			var widget_properties = me.getWidgetProperties(widget);
			
			if ($.isPlainObject(widget_properties["complete"]) && widget_properties["complete"][resources_type]) {
				widget_properties["complete"][resources_type] = null;
				delete widget_properties["complete"][resources_type];
				
				if ($.isEmptyObject(widget_properties["complete"]))
					delete widget_properties["complete"];
				
				widget.attr("data-widget-props", JSON.stringify(widget_properties));
				
				//reload resources menu settings if widget is the selected_widget with the properties opened
				if (widget.is(selected_widget))
					loadWidgetResourcesSettingsData(widget);
				
				return true;
			}
		}
		
		return false;
	}
	
	function addMainWidgetCompleteCallbackByType(widget, resources_type, list_callback, form_callback) {
		//add complete[resources_type] callback function to main_widget if it is a widget list or a form
		var main_widget = me.getWidgetGroupMainWidget(widget);
		
		if (main_widget && main_widget.is("[data-widget-list], [data-widget-form], form"))
			return addWidgetCompleteCallbackByType(main_widget, resources_type, list_callback, form_callback);
		
		return false;
	}
	
	function addWidgetCompleteCallbackByType(widget, resources_type, list_callback, form_callback) {
		//add complete[resources_type] callback function to widget
		if (widget) {
			var widget_properties = me.getWidgetProperties(widget);
			
			if (!$.isPlainObject(widget_properties["complete"]))
				widget_properties["complete"] = {};
			
			widget_properties["complete"][resources_type] = widget.is("[data-widget-list]") || !form_callback ? list_callback : form_callback;
			
			widget.attr("data-widget-props", JSON.stringify(widget_properties));
			
			//reload resources menu settings if widget is the selected_widget with the properties opened
			if (widget.is(selected_widget))
				loadWidgetResourcesSettingsData(widget);
			
			return true;
		}
		
		return false;
	}
	
	function searchItemInArray(items, item_to_search) {
		if (item_to_search) {
			if (!$.isArray(item_to_search))
				item_to_search = [item_to_search];
			
			for (var i = 0, t = item_to_search.length; i < t; i++)
				if (items.indexOf(item_to_search[i]) != -1)
					return item_to_search[i];
		}
		return null;
	}
	
	function getWidgetActionTypeResourcesType(widget, resources_type) {
		if (widget[0].hasAttribute("data-widget-list") && resources_type == "load")
			return "get_all";
		else if (widget[0].hasAttribute("data-widget-form") && resources_type == "load")
			return "get";
		else if (widget[0].hasAttribute("data-widget-pagination") && resources_type == "load")
			return "count";
		else if (widget[0].hasAttribute("data-widget-button-multiple-remove") && resources_type == "remove")
			return "multiple_delete";
		else if (widget[0].hasAttribute("data-widget-button-multiple-save") && resources_type == "save")
			return "multiple_save";
		else if (resources_type == "add")
			return "insert";
		
		return null;
	}
	
	function prepareWidgetWithDefaultHtml(elm, widget) {
		elm = $(elm);
		var widget_properties_ul = elm.parent().closest(".widget-properties").children("ul");
		var attributes_name = [];
		
		for (var i = 0, t = widget[0].attributes.length; i < t; i++) 
			attributes_name.push(widget[0].attributes[i].name);
		
		if (attributes_name.indexOf("data-widget-group-list") != -1) {
			me.onChangeWidgetListType(widget_properties_ul.find(" > .widget-property-list-type > select")[0], event);
			
			me.onChangeWidgetViewable(widget_properties_ul.find(" > .widget-property-viewable > input[type=checkbox]")[0], event);
			me.onChangeWidgetAddable(widget_properties_ul.find(" > .widget-property-addable > input[type=checkbox]")[0], event);
			me.onChangeWidgetEditable(widget_properties_ul.find(" > .widget-property-editable > input[type=checkbox]")[0], event);
			me.onChangeWidgetRemovable(widget_properties_ul.find(" > .widget-property-removable > input[type=checkbox]")[0], event);
			me.onChangeWidgetMultipleItemsActions(widget_properties_ul.find(" > .widget-property-multiple-items-actions > select")[0], event);
			
			me.onChangeWidgetWithTopPagination(widget_properties_ul.find(" > .widget-property-with-top-pagination > input[type=checkbox]")[0], event);
			me.onChangeWidgetWithBottomPagination(widget_properties_ul.find(" > .widget-property-with-bottom-pagination > input[type=checkbox]")[0], event);
			
			//me.onChangeWidgetWithSearch(widget_properties_ul.find(" > .widget-property-with-search > select")[0], event);
			me.onChangeWidgetWithSearch(widget_properties_ul.find(" > .widget-property-with-search > input[type=checkbox]")[0], event);
			me.onChangeWidgetWithShortActions(widget_properties_ul.find(" > .widget-property-with-short-actions > input[type=checkbox]")[0], event);
		}
		
		if (attributes_name.indexOf("data-widget-group-form") != -1) {
			//add form if not exists
			var main_widget = me.getWidgetGroupMainWidget(widget);
			
			if (!main_widget) {
				//remove anoying messages bc the db broker, driver, table and others are not selected yet
				ui_creator.removeMessages();
				
				var html = getWidgetFormHtml(elm, widget);
				html = $(html);
				
				widget.prepend(html);
				
				//convert html to LayoutUIEditor widgets
				convertHtmlElementToLayoutUIEditorWidget(html);
			}
			
			me.onChangeWidgetViewable(widget_properties_ul.find(" > .widget-property-viewable > input[type=checkbox]")[0], event);
			me.onChangeWidgetAddable(widget_properties_ul.find(" > .widget-property-addable > input[type=checkbox]")[0], event);
			me.onChangeWidgetEditable(widget_properties_ul.find(" > .widget-property-editable > input[type=checkbox]")[0], event);
			me.onChangeWidgetRemovable(widget_properties_ul.find(" > .widget-property-removable > input[type=checkbox]")[0], event);
		}
		
		if (attributes_name.indexOf("data-widget-form") != -1) {
			me.onChangeWidgetViewable(widget_properties_ul.find(" > .widget-property-viewable > input[type=checkbox]")[0], event);
			me.onChangeWidgetAddable(widget_properties_ul.find(" > .widget-property-addable > input[type=checkbox]")[0], event);
			me.onChangeWidgetEditable(widget_properties_ul.find(" > .widget-property-editable > input[type=checkbox]")[0], event);
			me.onChangeWidgetRemovable(widget_properties_ul.find(" > .widget-property-removable > input[type=checkbox]")[0], event);
		}
		
		if (attributes_name.indexOf("data-widget-list") != -1) {
			me.onChangeWidgetListType(widget_properties_ul.find(" > .widget-property-list-type > select")[0], event);
			me.onChangeWidgetViewable(widget_properties_ul.find(" > .widget-property-viewable > input[type=checkbox]")[0], event);
			me.onChangeWidgetAddable(widget_properties_ul.find(" > .widget-property-addable > input[type=checkbox]")[0], event);
			me.onChangeWidgetEditable(widget_properties_ul.find(" > .widget-property-editable > input[type=checkbox]")[0], event);
			me.onChangeWidgetRemovable(widget_properties_ul.find(" > .widget-property-removable > input[type=checkbox]")[0], event);
			me.onChangeWidgetListMultipleItemsActions(widget_properties_ul.find(" > .widget-property-multiple-items-actions > input[type=checkbox]")[0], event);
		}
		
		if (attributes_name.indexOf("data-widget-search") != -1) {
			me.onChangeWidgetSearchWithInput(widget_properties_ul.find(" > .widget-property-search-with-input > input[type=checkbox]")[0], event);
			me.onChangeWidgetSearchWithSelect(widget_properties_ul.find(" > .widget-property-search-with-select > input[type=checkbox]")[0], event);
			me.onChangeWidgetSearchWithUserChoice(widget_properties_ul.find(" > .widget-property-search-with-user-choice > input[type=checkbox]")[0], event);
			me.onChangeWidgetSearchType(widget_properties_ul.find(" > .widget-property-search-type > select")[0], event);
			me.onChangeWidgetSearchOperator(widget_properties_ul.find(" > .widget-property-search-operator > select")[0], event);
			me.onChangeWidgetSearchCase(widget_properties_ul.find(" > .widget-property-search-case > select")[0], event);
		}
		
		if (attributes_name.indexOf("data-widget-search-input") != -1 || attributes_name.indexOf("data-widget-search-select") != -1 || attributes_name.indexOf("data-widget-search-multiple-button") != -1) {
			me.onChangeWidgetSearchType(widget_properties_ul.find(" > .widget-property-search-type > select")[0], event);
			me.onChangeWidgetSearchOperator(widget_properties_ul.find(" > .widget-property-search-operator > select")[0], event);
			me.onChangeWidgetSearchCase(widget_properties_ul.find(" > .widget-property-search-case > select")[0], event);
		}
		
		if (attributes_name.indexOf("data-widget-pagination") != -1) {
			if (widget.find("[data-widget-pagination-go-to-page]").length == 0) {
				var html = getWidgetPaginationGoToPageHtml();
				html = $(html);
				
				widget.append(html);
				
				//convert html to LayoutUIEditor widgets
				convertHtmlElementToLayoutUIEditorWidget(html);
			}
			
			if (widget.find("[data-widget-pagination-pages]").length == 0) {
				var html = getWidgetPaginationPagesHtml();
				html = $(html);
				
				widget.append(html);
				
				//convert html to LayoutUIEditor widgets
				convertHtmlElementToLayoutUIEditorWidget(html);
			}
		}
		
		if (attributes_name.indexOf("data-widget-popup-add") != -1)
			widget.find("form").addClass("show-add-fields");
		
		if (attributes_name.indexOf("data-widget-pagination-pages") != -1) {
			if (widget.find("[data-widget-pagination-pages-first], [data-widget-pagination-pages-previous], [data-widget-pagination-pages-next], [data-widget-pagination-pages-last], [data-widget-pagination-pages-numbers]").length == 0) {
				var html = getWidgetPaginationPagesInnerHtml();
				html = $(html);
				
				widget.append(html);
				
				//convert html to LayoutUIEditor widgets
				convertHtmlElementToLayoutUIEditorWidget(html);
			}
		}
		
		if (attributes_name.indexOf("data-widget-graph") != -1 && widget.children().length == 0) {
			//set some default dimensions
			var settings_dimension_ul = widget_properties_ul.parent().closest(".menu-settings").find(" > .settings-dimension > ul");
			var width_input = settings_dimension_ul.find(" > .settings-width > input");
			var height_input = settings_dimension_ul.find(" > .settings-height > input");
			
			if (!width_input.val()) {
				width_input.val("200");
				width_input.parent().children("select").val("px");
				width_input.trigger("blur");
			}
			
			if (!height_input.val()) {
				height_input.val("200");
				height_input.parent().children("select").val("px");
				height_input.trigger("blur");
			}
			
			//set page resource load attribute and handler
			var ul = widget_properties_ul.parent().closest(".settings-widget").find(".widget-resources .widget-resources-load > .widget-resources-type-properties > ul");
			ul.find(" > .widget-property-load-type select").val("data-widget-resources-load");
			loadWidgetSettingValueIntoSwappedField(ul.children(".widget-property-handler-load"), "MyWidgetResourceLib.ChartHandler.loadChartResource");
			
			widget_properties_ul.find(".widget-property-graph-include-lib").children("input").prop("checked", true).attr("checked", "checked");
			updateFieldInSelectedWidget( widget_properties_ul.find(".widget-property-graph-include-lib") );
		}
		
		if (attributes_name.indexOf("data-widget-calendar") != -1 && widget.children().length == 0) {
			//set some default dimensions
			var menu_settings = widget_properties_ul.parent().closest(".menu-settings");
			var settings_dimension_ul = menu_settings.find(" > .settings-dimension > ul");
			var width_input = settings_dimension_ul.find(" > .settings-width > input");
			var height_input = settings_dimension_ul.find(" > .settings-height > input");
			var min_width_input = settings_dimension_ul.find(" > .settings-min-width > input");
			var min_height_input = settings_dimension_ul.find(" > .settings-min-height > input");
			
			if (!width_input.val()) {
				width_input.val("100");
				width_input.parent().children("select").val("%");
				width_input.trigger("blur");
			}
			
			if (!height_input.val()) {
				height_input.val("calc(100vh - 20px)");
				height_input.parent().children("select").val("");
				height_input.trigger("blur");
			}
			
			if (!min_width_input.val()) {
				min_width_input.val("200");
				min_width_input.parent().children("select").val("px");
				min_width_input.trigger("blur");
			}
			
			if (!min_height_input.val()) {
				min_height_input.val("200");
				min_height_input.parent().children("select").val("px");
				min_height_input.trigger("blur");
			}
			
			menu_settings.find(" > .settings-general > ul > .settings-position > select").val("relative").trigger("change");
			
			//set page resource load attribute and handler
			var ul = widget_properties_ul.parent().closest(".settings-widget").find(".widget-resources .widget-resources-load > .widget-resources-type-properties > ul");
			ul.find(" > .widget-property-load-type select").val("data-widget-resources-load");
			loadWidgetSettingValueIntoSwappedField(ul.children(".widget-property-handler-load"), "MyWidgetResourceLib.CalendarHandler.loadCalendarResource");
			
			var toolbar = widget_properties_ul.children(".widget-property-calendar-header-toolbar");
			var toolbar_ul = toolbar.children("ul");
			toolbar.find(" > .group-title > input").prop("checked", true).attr("checked", "checked");
			toolbar_ul.find(" > .widget-property-calendar-header-toolbar-left > input").val("prevYear,prev,next,nextYear");
			toolbar_ul.find(" > .widget-property-calendar-header-toolbar-center > input").val("title");
			toolbar_ul.find(" > .widget-property-calendar-header-toolbar-right > input").val("today");
			
			var toolbar = widget_properties_ul.children(".widget-property-calendar-footer-toolbar");
			var toolbar_ul = toolbar.children("ul");
			toolbar.find(" > .group-title > input").prop("checked", true).attr("checked", "checked");
			toolbar_ul.find(" > .widget-property-calendar-footer-toolbar-left > input").val("multiMonthYear dayGridMonth,dayGridWeek,dayGridDay");
			toolbar_ul.find(" > .widget-property-calendar-footer-toolbar-center > input").val("timeGridWeek,timeGridDay");
			toolbar_ul.find(" > .widget-property-calendar-footer-toolbar-right > input").val("listYear,listMonth,listWeek,listDay");
			
			widget_properties_ul.find(".widget-property-calendar-include-all-load-resources, .widget-property-calendar-include-lib, .widget-property-calendar-nav-links, .widget-property-calendar-selectable, .widget-property-calendar-select-mirror, .widget-property-calendar-now-indicator, .widget-property-calendar-editable, .widget-property-calendar-day-max-events, .widget-property-calendar-all-day-slot, .widget-property-calendar-week-numbers").children("input").prop("checked", true).attr("checked", "checked");
			
			updateFieldInSelectedWidget(toolbar_ul);
			
			var loading_elm = $('<h2 class="border-0 rounded text-center text-white" data-widget-loading style="position:absolute; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,.2); padding-top:30%; z-index:1;">Loading new data... Please wait a while...</h2>');
			widget.append(loading_elm);
			
			//convert html to LayoutUIEditor widget
			convertHtmlElementToLayoutUIEditorWidget(loading_elm);
		}
		
		if (attributes_name.indexOf("data-widget-matrix") != -1 && widget.children().length == 0) {
			//set some default dimensions
			var menu_settings = widget_properties_ul.parent().closest(".menu-settings");
			var settings_dimension_ul = menu_settings.find(" > .settings-dimension > ul");
			var width_input = settings_dimension_ul.find(" > .settings-width > input");
			var min_height_input = settings_dimension_ul.find(" > .settings-min-height > input");
			
			if (!width_input.val()) {
				width_input.val("100");
				width_input.parent().children("select").val("%");
				width_input.trigger("blur");
			}
			
			if (!min_height_input.val()) {
				min_height_input.val("200");
				min_height_input.parent().children("select").val("px");
				min_height_input.trigger("blur");
			}
			
			//set page resource load attribute and handler
			var ul = widget_properties_ul.parent().closest(".settings-widget").find(".widget-resources .widget-resources-load > .widget-resources-type-properties > ul");
			ul.find(" > .widget-property-load-type select").val("data-widget-resources-load");
			loadWidgetSettingValueIntoSwappedField(ul.children(".widget-property-handler-load"), "MyWidgetResourceLib.MatrixHandler.loadMatrixResource");
			
			//prepare table
			var is_table = widget.is("table");
			
			if (is_table) {
				//append loading row
				var loading_elm = $('<tr data-widget-loading><td class="border-0 rounded text-center text-muted small p-3">Loading new data... Please wait a while...</td></tr>');
				var elm_to_append = widget.querySelector("tbody");
				elm_to_append = elm_to_append ? elm_to_append : widget;
				
				elm_to_append.append(loading_elm);
				
				//convert html to LayoutUIEditor widget
				convertHtmlElementToLayoutUIEditorWidget(loading_elm);
				
				//append empty row
				var empty_elm = $('<tr data-widget-empty><td class="border-0 text-center text-muted small p-3">No elements available...</td></tr>');
				var elm_to_append = widget.querySelector("tbody");
				elm_to_append = elm_to_append ? elm_to_append : widget;
				
				elm_to_append.append(empty_elm);
				
				//convert html to LayoutUIEditor widget
				convertHtmlElementToLayoutUIEditorWidget(empty_elm);
			}
			else { //create inner elements structure, appending table
				var table_elm = $('<table style="width:100%;">'
						+ '<thead>'
							+ '<tr data-widget-matrix-head-row data-widget-props="{&quot;load&quot;:[&quot;MyWidgetResourceLib.MatrixHandler.loadMatrixHeadRowResource&quot;]}"><th class="border">Axis Y/Axis X</th><th class="border" data-widget-matrix-head-column>Axis X Column</th></tr>'
						+ '</thead>'
						+ '<tbody>'
							+ '<tr data-widget-matrix-body-row data-widget-props="{&quot;load&quot;:[&quot;MyWidgetResourceLib.MatrixHandler.loadMatrixBodyRowResource&quot;]}"><th class="border" data-widget-matrix-head-column>Axis Y Column</th><td class="border" data-widget-matrix-body-column><div data-widget-item>Item Column</div></td></tr>'
							+ '<tr data-widget-loading><td colspan="2" class="border-0 rounded text-center text-muted small p-3">Loading new data... Please wait a while...</td></tr>'
							+ '<tr data-widget-empty style="display:none;"><td colspan="2" class="border-0 text-center text-muted small p-3">No elements available...</td></tr>'
						+ '</tbody>'
					+ '</table>');
				widget.append(table_elm);
				
				//convert html to LayoutUIEditor widget
				convertHtmlElementToLayoutUIEditorWidget(table_elm);
			}
		}
	}
	
	function prepareWidgetWithPermissionsHtml(elm, widget) {
		elm = $(elm);
		var p = elm.parent().closest(".widget-property-action-permissions, .widget-permissions");
		
		if (p.hasClass("widget-property-action-permissions") && widget.is("[data-widget-group-list], [data-widget-group-form], [data-widget-list], [data-widget-form]")) {
			//get properties_settings
			var properties_settings = getWidgetSettingsData(elm, ".widget-properties");
			properties_settings = properties_settings["widget_properties"];
			
			//set new permissions
			var action_elm = p.parent().parent();
			var items = null;
			var permissions = null;
			
			if (action_elm.hasClass("widget-property-viewable")) {
				var view_active = properties_settings["viewable"] ? properties_settings["viewable"]["active"] : false;
				permissions = view_active ? properties_settings["viewable"]["permissions"] : null;
				
				items = widget.find("[data-widget-item-attribute-field-view], [data-widget-item-attribute-link-view], [data-widget-item-button-view]");

			}
			else if (action_elm.hasClass("widget-property-addable")) {
				var add_active = properties_settings["addable"] ? properties_settings["addable"]["active"] : false;
				permissions = add_active ? properties_settings["addable"]["permissions"] : false;
				
				items = widget.find("[data-widget-button-add], [data-widget-item-add], [data-widget-item-attribute-field-add], [data-widget-item-button-add], [data-widget-item-button-add-cancel]");
			}
			else if (action_elm.hasClass("widget-property-editable")) {
				var edit_active = properties_settings["editable"] ? properties_settings["editable"]["active"] : false;
				permissions = edit_active ? properties_settings["editable"]["permissions"] : false;
				
				items = widget.find("[data-widget-item-attribute-link-edit], [data-widget-item-attribute-field-edit], [data-widget-item-button-update], [data-widget-item-button-toggle-inline-edit-view], [data-widget-item-button-edit]");
				
			}
			else if (action_elm.hasClass("widget-property-removable")) {
				var remove_active = properties_settings["removable"] ? properties_settings["removable"]["active"] : false;
				permissions = remove_active ? properties_settings["removable"]["permissions"] : false;
				
				items = widget.find("[data-widget-item-button-remove]");
			}
			else if (action_elm.hasClass("widget-property-multiple-items-actions") && widget.is("[data-widget-group-list]")) {
				if ($.isPlainObject(properties_settings["multiple_items_actions"]))
					permissions = properties_settings["multiple_items_actions"]["permissions"];
				
				items = widget.find("[data-widget-button-multiple-remove], [data-widget-button-multiple-save]");
			}
			
			if (items) {
				var permissions_json = permissions ? JSON.stringify(permissions) : null;
				
				items.each(function(idx, item) {
					item = $(item);
					
					if (permissions_json)
						item.attr("data-widget-permissions", permissions_json);
					else
						item.removeAttr("data-widget-permissions");
				}).promise().done( function() {
					onChangeSelectedWidget();
				});
			}
		}
	}
	
	function removeWidgetAttrNameFromUI(elm, widget) {
		elm = $(elm);
		var li = elm.parent().closest("li");
		var main_li = li.parent().parent();
		var attr_name = li.children("input.attr_name").val(); //must be before the removeItem method runs, otherwise the input will be deleted
		
		//update UI
		var widget_with_pks = null;
		var widget_with_defaults = null;
		var widget_search = null;
		
		if (main_li.is(".widget-property-shown-attrs-names")) {
			//get search bar to remove correspondent attr_name below.
			widget_search = widget.find("[data-widget-search]");
			
			//get items to remove
			var items_to_remove = widget.find("[data-widget-item-column][data-widget-item-attribute-name='" + attr_name + "'], [data-widget-item-head][data-widget-item-attribute-name='" + attr_name + "']");
			
			if (items_to_remove.length > 0) {
				//remove selected widget item-column with correspondent attr name, including popups
				removeWidget(items_to_remove);
				
				//prepare colspan in tr[data-widget-loading] and tr[data-widget-empty]
				var main_widget = me.getWidgetGroupMainWidget(widget);
				
				if (main_widget) {
					var table = main_widget.is("[data-widget-list]") ? main_widget.find("table[data-widget-list-table], table") : (
						main_widget.is("[data-widget-list-table], table") ? main_widget : null
					);
					
					if (table) {
						table.find("tr[data-widget-loading], tr[data-widget-empty]").each(function(idx, tr) {
							var td = $(tr).children("td").last();
							
							if (td[0].hasAttribute("colspan")) {
								var colspan = td.attr("colspan");
								
								colspan = (colspan > 0 ? parseInt(colspan) : 1) - 1;
								
								if (colspan > 0)
									td.attr("colspan", colspan);
								else
									td.removeAttr("colspan");
							}
						}).promise().done( function() {
							onChangeSelectedWidget();
						});
					}
				}
			}
		}
		else if (main_li.is(".widget-property-pks-attrs-names")) {
			var main_widget = me.getWidgetGroupMainWidget(widget);
			
			if (main_widget && main_widget.is("[data-widget-list], [data-widget-form]"))
				widget_with_pks = main_widget;
		}
		else if (main_li.is(".widget-property-default-attrs-values")) {
			var main_widget = me.getWidgetGroupMainWidget(widget);
			
			if (main_widget && main_widget.is("[data-widget-list], [data-widget-form]"))
				widget_with_defaults = main_widget;
		}
		else if (main_li.is(".widget-property-search-attrs-names"))
			widget_search = widget;
		
		//remove attr_name from pks in widget_with_pks properties
		if (widget_with_pks && widget_with_pks[0]) {
			var widget_with_pks_properties = me.getWidgetProperties(widget_with_pks);
			
			if (widget_with_pks_properties["pks_attrs_names"]) {
				var pks_attrs_names = widget_with_pks_properties["pks_attrs_names"];
				pks_attrs_names = $.isArray(pks_attrs_names) ? pks_attrs_names : pks_attrs_names.replace(/;/g, ",").split(",");
				var array_pos = $.inArray(attr_name, pks_attrs_names);
				
				if (array_pos != -1) {
					pks_attrs_names.splice(array_pos, 1); //remove attr_name from array
					
					widget_with_pks_properties["pks_attrs_names"] = pks_attrs_names.join(",");
					widget_with_pks.attr("data-widget-props", JSON.stringify(widget_with_pks_properties));
					
					onChangeSelectedWidget();
				}
			}
		}
		
		//remove attr_name from defaults in widget_with_defaults properties
		if (widget_with_defaults && widget_with_defaults[0]) {
			var default_attrs_values = me.getWidgetDefaultAttributesValues(widget_with_defaults);
			
			if ($.isPlainObject(default_attrs_values) && default_attrs_values.hasOwnProperty(attr_name)) {
				//remove attr_name
				default_attrs_values[attr_name] = null; 
				delete default_attrs_values[attr_name];
				
				widget_with_defaults.attr("data-widget-pks-attrs", JSON.stringify(default_attrs_values));
				
				onChangeSelectedWidget();
			}
		}
		
		//remove attr_name from search bar
		if (widget_search && widget_search[0]) {
			//remove search_attrs from input and select search fields
			var widget_search_items = widget_search.is("[data-widget-search-input], [data-widget-search-select]") ? widget_search : widget_search.find("[data-widget-search-input], [data-widget-search-select]");
				
			widget_search_items.each(function(idx, field) {
				field = $(field);
				var field_properties = me.getWidgetProperties(field);
				
				if (field_properties["search_attrs"]) {
					var search_attrs = field_properties["search_attrs"].replace(/;/g, ",").split(",");
					var array_pos = $.inArray(attr_name, search_attrs);
					
					if (array_pos != -1) {
						search_attrs.splice(array_pos, 1); //remove attr_name from array
						
						field_properties["search_attrs"] = search_attrs.join(",");
						field.attr("data-widget-props", JSON.stringify(field_properties));
					}
				}
			}).promise().done( function() {
				onChangeSelectedWidget();
			});
			
			//remove option from data-widget-search-multiple
			var select = widget_search.find("[data-widget-search-multiple] select");
			
			if (select[0]) {
				var option = select.find("option[value='" + attr_name + "']");
				
				if (option[0])
					removeWidget(option);
			}
		}
	}
	
	function addWidgetAttrNameFromUI(elm, widget) {
		elm = $(elm);
		var li = elm.parent().closest("li");
		var main_li = li.parent().parent();
		var input = li.children("input.attr_name");
		var attr_name = input.val();
		var attr_value = li.children("input.attr_value").val();
		
		if (attr_name) {
			//update saved value
			input.data("saved_attr_name", attr_name);
			
			//update UI
			var widget_with_pks = null;
			var widget_with_defaults = null;
			var widget_search = null;
			
			if (main_li.is(".widget-property-shown-attrs-names")) {
				//get search bar to remove correspondent attr_name below.
				widget_search = widget.find("[data-widget-search]");
				
				//add selected widget item-column and item-head with correspondent attr name, including popups
				var main_widget = me.getWidgetGroupMainWidget(widget);
				
				if (main_widget) {
					//get properties_settings
					var properties_settings = getWidgetSettingsData(elm, ".widget-properties");
					properties_settings = properties_settings["widget_properties"];
					
					var db_attributes = getWidgetGroupDBAttributes(widget);
					
					var add_active = properties_settings["addable"] ? properties_settings["addable"]["active"] : false;
					var add_type = add_active ? properties_settings["addable"]["type"] : false;
					var add_permissions = add_active ? properties_settings["addable"]["permissions"] : false;
					var add_permissions_html = add_permissions ? ' data-widget-permissions="' + JSON.stringify(add_permissions).replace(/"/g, "&quot;") + '"' : '';
					
					var view_active = properties_settings["viewable"] ? properties_settings["viewable"]["active"] : false;
					var view_type = view_active ? properties_settings["viewable"]["type"] : false;
					var view_with_view_button = view_active ? properties_settings["viewable"]["with_view_button"] : false;
					var view_permissions = view_active ? properties_settings["viewable"]["permissions"] : false;
					var view_permissions_html = view_permissions ? ' data-widget-permissions="' + JSON.stringify(view_permissions).replace(/"/g, "&quot;") + '"' : '';
					
					var edit_active = properties_settings["editable"] ? properties_settings["editable"]["active"] : false;
					var edit_type = edit_active ? properties_settings["editable"]["type"] : false;
					var edit_with_auto_save = edit_type == "inline" ? properties_settings["editable"]["with_auto_save"] : false;
					var edit_with_save_button = edit_type == "inline" ? properties_settings["editable"]["with_save_button"] : false;
					var edit_with_edit_button = edit_active ? properties_settings["editable"]["with_edit_button"] : false;
					var edit_toggle_edit_view_fields_button = edit_type == "inline" ? properties_settings["editable"]["toggle_edit_view_fields_button"] : false;
					var edit_permissions = edit_active ? properties_settings["editable"]["permissions"] : false;
					var edit_permissions_html = edit_permissions ? ' data-widget-permissions="' + JSON.stringify(edit_permissions).replace(/"/g, "&quot;") + '"' : '';
					
					var remove_active = properties_settings["removable"] ? properties_settings["removable"]["active"] : false;
					var remove_permissions = remove_active ? properties_settings["removable"]["permissions"] : false;
					var remove_permissions_html = remove_permissions ? ' data-widget-permissions="' + JSON.stringify(remove_permissions).replace(/"/g, "&quot;") + '"' : '';
					
					var table = main_widget.is("[data-widget-list]") ? main_widget.find("table[data-widget-list-table], table") : (
						main_widget.is("[data-widget-list-table], table") ? main_widget : null
					);
					var table_items = table ? table.find("[data-widget-item], [data-widget-item-add]") : null;
					var other_items = main_widget.is("form") ? main_widget : (
						main_widget.is("[data-widget-list]") ? main_widget.find("[data-widget-list-tree], ul").find("[data-widget-item], [data-widget-item-add]") : null
					);
					var popups = widget.find("[data-widget-popup-view], [data-widget-popup-edit], [data-widget-popup-add]");
					var table_column_added = false;
					
					//add table head if attr_name does not exists
					if (table)
						table.each(function(idx, t) {
							t = $(t);
							var exists = t.find("[data-widget-item-head][data-widget-item-attribute-name='" + attr_name + "']").length > 0;
							
							if (!exists) {
								var html = getWidgetListTableItemColumnHeadHtml(attr_name);
								
								if (html) {
									var thead_tr = t.find("thead tr");
									var last = t.find("[data-widget-item-head]").last();
									var actions = t.find("[data-widget-item-actions-head]").first();
									
									html = $(html);
									
									if (last[0])
										last.after(html);
									else if (actions[0])
										actions.before(html);
									else if (thead_tr[0])
										thead_tr.append(html);
									else 
										t.find("tr").first().append(html);
									
									//convert html to LayoutUIEditor widgets
									convertHtmlElementToLayoutUIEditorWidget(html);
									
									table_column_added = true;
								}
							}
						});
					
					//add table column if attr_name does not exists
					if (table_items)
						table_items.each(function(idx, item) {
							item = $(item);
							var exists = item.find("[data-widget-item-column][data-widget-item-attribute-name='" + attr_name + "']").length > 0;
							
							if (!exists) {
								var html = item[0].hasAttribute("data-widget-item-add") ? 
											getWidgetListTableItemColumnBodyHtml(elm, widget, db_attributes, properties_settings, attr_name, add_active, add_type, add_permissions_html) 
										: getWidgetListTableItemColumnBodyHtml(elm, widget, db_attributes, properties_settings, attr_name, false, null, "", view_active, view_type, view_with_view_button, view_permissions_html, edit_active, edit_type, edit_with_auto_save, edit_with_save_button, edit_with_edit_button, edit_toggle_edit_view_fields_button, edit_permissions_html, remove_active, remove_permissions_html);
								
								if (html) {
									var last = item.find("[data-widget-item-column]").last();
									var actions = item.find("[data-widget-item-actions-column]").first();
									
									html = $(html);
									
									if (last[0])
										last.after(html);
									else if (actions[0])
										actions.before(html);
									else 
										item.append(html);
									
									//convert html to LayoutUIEditor widgets
									convertHtmlElementToLayoutUIEditorWidget(html);
									
									table_column_added = true;
								}
							}
						});
					
					//prepare colspan in tr[data-widget-loading] and tr[data-widget-empty]
					if (table && table_column_added) {
						table.find("tr[data-widget-loading], tr[data-widget-empty]").each(function(idx, tr) {
							var td = $(tr).children("td").last();
							var colspan = td.attr("colspan");
							
							colspan = (colspan > 0 ? parseInt(colspan) : 1) + 1;
							td.attr("colspan", colspan);
						}).promise().done( function() {
							onChangeSelectedWidget();
						});
					}
					
					//add column if attr_name does not exists
					if (other_items)
						other_items.each(function(idx, item) {
							item = $(item);
							var exists = item.find("[data-widget-item-column][data-widget-item-attribute-name='" + attr_name + "']").length > 0;
							
							if (!exists) {
								var item_parent = item.closest("form, [data-widget-list], [data-widget-list-tree]");
								var is_tree = item_parent.is("[data-widget-list-tree]");
								var is_form = item_parent.is("form");
								var popup = is_form ? item.closest("[data-widget-popup], [data-widget-group-list], [data-widget-group-form]") : null;
								
								if (!popup.is("[data-widget-popup]"))
									popup = null;
								
								var html = "";
								
								if (is_tree)
									html = item[0].hasAttribute("data-widget-item-add") ? 
											getWidgetItemColumnHtml(elm, widget, db_attributes, properties_settings, attr_name, add_active, add_type, add_permissions_html) 
										: getWidgetItemColumnHtml(elm, widget, db_attributes, properties_settings, attr_name, false, null, "", view_active, view_type, view_with_view_button, view_permissions_html, edit_active, edit_type, edit_with_auto_save, edit_with_save_button, edit_with_edit_button, edit_toggle_edit_view_fields_button, edit_permissions_html, remove_active, remove_permissions_html);
								else if (popup && popup.length > 0) {
									if (popup.is("[data-widget-popup-view]"))
										html = getWidgetItemColumnHtml(elm, widget, db_attributes, properties_settings, attr_name, false, null, "", view_active, "inline", view_with_view_button, view_permissions_html);
									else if (popup.is("[data-widget-popup-add]"))
										html = getWidgetItemColumnHtml(elm, widget, db_attributes, properties_settings, attr_name, add_active, "inline", add_permissions_html);
									else if (popup.is("[data-widget-popup-edit]"))
										html = getWidgetItemColumnHtml(elm, widget, db_attributes, properties_settings, attr_name, false, null, "", false, null, false, "", edit_active, "inline", edit_with_auto_save, edit_with_save_button, edit_with_edit_button, edit_toggle_edit_view_fields_button, edit_permissions_html, remove_active, remove_permissions_html);
								}
								else
									html = getWidgetItemColumnHtml(elm, widget, db_attributes, properties_settings, attr_name, add_active, add_type, add_permissions_html, view_active, view_type, view_with_view_button, view_permissions_html, edit_active, edit_type, edit_with_auto_save, edit_with_save_button, edit_with_edit_button, edit_toggle_edit_view_fields_button, edit_permissions_html, remove_active, remove_permissions_html);
								
								if (html) {
									var last = item.find("[data-widget-item-column]").last();
									var actions = item.find("[data-widget-item-actions-column]").first();
									
									html = $(html);
									
									if (last[0])
										last.after(html);
									else if (actions[0])
										actions.before(html);
									else 
										item.append(html);
									
									//convert html to LayoutUIEditor widgets
									convertHtmlElementToLayoutUIEditorWidget(html);
								}
							}
						});
					
					//add column if attr_name does not exists
					popups.each(function(idx, popup) {
						popup = $(popup);
						var exists = popup.find("[data-widget-item-column][data-widget-item-attribute-name='" + attr_name + "']").length > 0;
						
						if (!exists) {
							var html = "";
							
							if (popup.is("[data-widget-popup-view]"))
								html = getWidgetItemColumnHtml(elm, widget, db_attributes, properties_settings, attr_name, false, null, "", view_active, "inline", view_with_view_button, view_permissions_html);
							else if (popup.is("[data-widget-popup-add]"))
								html = getWidgetItemColumnHtml(elm, widget, db_attributes, properties_settings, attr_name, add_active, "inline", add_permissions_html);
							else if (popup.is("[data-widget-popup-edit]"))
								html = getWidgetItemColumnHtml(elm, widget, db_attributes, properties_settings, attr_name, false, null, "", false, null, false, "", edit_active, "inline", edit_with_auto_save, edit_with_save_button, edit_with_edit_button, edit_toggle_edit_view_fields_button, edit_permissions_html, remove_active, remove_permissions_html);
							
							if (html) {
								var last = popup.find("[data-widget-item-column]").last();
								var actions = popup.find("[data-widget-item-actions-column]").first();
								var form = popup.find("form");
								
								html = $(html);
								
								if (last[0])
									last.after(html);
								else if (actions[0])
									actions.before(html);
								else if (form[0])
									form.append(html);
								else 
									popup.append(html);
								
								//convert html to LayoutUIEditor widgets
								convertHtmlElementToLayoutUIEditorWidget(html);
							}
						}
					});
				}
			}
			else if (main_li.is(".widget-property-pks-attrs-names")) {
				var main_widget = me.getWidgetGroupMainWidget(widget);
				
				if (main_widget && main_widget.is("[data-widget-list], [data-widget-form]"))
					widget_with_pks = main_widget;
			}
			else if (main_li.is(".widget-property-default-attrs-values")) {
				var main_widget = me.getWidgetGroupMainWidget(widget);
				
				if (main_widget && main_widget.is("[data-widget-list], [data-widget-form]"))
					widget_with_defaults = main_widget;
			}
			else if (main_li.is(".widget-property-search-attrs-names")) {
				widget_search = widget;
			}
			
			//add attr_name to pks in widget_with_pks properties
			if (widget_with_pks && widget_with_pks[0]) {
				var widget_with_pks_properties = me.getWidgetProperties(widget_with_pks);
				var pks_attrs_names = widget_with_pks_properties["pks_attrs_names"];
				pks_attrs_names = $.isArray(pks_attrs_names) ? pks_attrs_names : (typeof pks_attrs_names == "string" ? pks_attrs_names.replace(/;/g, ",").split(",") : []);
				
				if ($.inArray(attr_name, pks_attrs_names) == -1) {
					pks_attrs_names.push(attr_name);
					widget_with_pks_properties["pks_attrs_names"] = pks_attrs_names.join(",");
					widget_with_pks.attr("data-widget-props", JSON.stringify(widget_with_pks_properties));
					
					onChangeSelectedWidget();
				}
			}
			
			//add attr_name to defaults in widget_with_defaults properties
			if (widget_with_defaults && widget_with_defaults[0]) {
				var default_attrs_values = me.getWidgetDefaultAttributesValues(widget_with_defaults);
				default_attrs_values = $.isPlainObject(default_attrs_values) ? default_attrs_values : {};
				
				if (!default_attrs_values.hasOwnProperty(attr_name)) {
					default_attrs_values[attr_name] = attr_value;
					
					widget_with_defaults.attr("data-widget-pks-attrs", JSON.stringify(default_attrs_values));
					
					onChangeSelectedWidget();
				}
			}
			
			//add attr_name to search bar
			if (widget_search && widget_search[0]) {
				//remove search_attrs from input and select search fields
				var widget_search_items = widget_search.is("[data-widget-search-input], [data-widget-search-select]") ? widget_search : widget_search.find("[data-widget-search-input], [data-widget-search-select]");
				
				widget_search_items.each(function(idx, field) {
					field = $(field);
					var field_properties = me.getWidgetProperties(field);
					
					if (field_properties["search_attrs"]) {
						var search_attrs = field_properties["search_attrs"].replace(/;/g, ",").split(",");
						
						if ($.inArray(attr_name, search_attrs) == -1) {
							search_attrs.push(attr_name);
							field_properties["search_attrs"] = search_attrs.join(",");
							field.attr("data-widget-props", JSON.stringify(field_properties));
						}
					}
					else {
						field_properties["search_attrs"] = attr_name;
						field.attr("data-widget-props", JSON.stringify(field_properties));
					}
				}).promise().done( function() {
					onChangeSelectedWidget();
				});
				
				//remove option from data-widget-search-multiple
				var select = widget_search.find("[data-widget-search-multiple] select");
				
				if (select[0]) {
					var option = select.find("option[value='" + attr_name + "']");
					
					if (!option[0]) {
						select.append('<option value="' + attr_name + '" title="' + getLabel(attr_name) + '">' + getLabel(attr_name) + '</option>');
						
						onChangeSelectedWidget();
					}
				}
			}
		}
	}
	
	function replaceWidgetAttrNameFromUI(elm, widget) {
		elm = $(elm);
		var li = elm.parent().closest("li");
		var main_li = li.parent().parent();
		var input = li.children("input.attr_name");
		var new_attr_name = input.val();
		var old_attr_name = input.data("saved_attr_name");
		var attr_value = li.children("input.attr_value").val();
		var is_attr_value = elm.is(".attr_value");
		//console.log("replaceWidgetAttrNameFromUI:\nnew_attr_name:"+new_attr_name+"\nold_attr_name:"+old_attr_name+"\nis_attr_value:"+is_attr_value);
		
		if (new_attr_name != old_attr_name || is_attr_value) {
			//update saved value
			input.data("saved_attr_name", new_attr_name);
			//console.log("replaceWidgetAttrNameFromUI:"+old_attr_name+"|"+new_attr_name);
			
			//remove attr_name
			if (!is_attr_value && old_attr_name && !new_attr_name) {
				//console.log("DELETE:"+old_attr_name);
				input.val(old_attr_name);
				removeWidgetAttrNameFromUI(elm, widget);
				input.val(new_attr_name);
			}
			//add attr_name
			else if (!is_attr_value && !old_attr_name && new_attr_name) {
				addWidgetAttrNameFromUI(elm, widget);
				//console.log("ADD:"+new_attr_name);
			}
			//replace attr_name
			else if (old_attr_name && new_attr_name) {
				//console.log("REPLACE:"+old_attr_name+"|"+new_attr_name);
				
				//update UI
				var widget_with_defaults = null;
				var widget_with_pks = null;
				var widget_search = null;
				var old_attr_label = getLabel(old_attr_name);
				
				if (main_li.is(".widget-property-shown-attrs-names")) {
					//get search bar to remove correspondent attr_name below.
					widget_search = widget.find("[data-widget-search]");
					
					//replace selected widget item-column and item-head with correspondent attr name, including popups
					var prepare_head_handler = function(head) {
						//console.log("Found head:"+old_attr_name+":"+new_attr_name);
						
						if (head[0].hasAttribute("data-widget-item-attribute-name"))
							head.attr("data-widget-item-attribute-name", new_attr_name);
						
						var html = head.html();
						var regex = new RegExp("(" + old_attr_name + "|" + old_attr_label + ")", "gi");
						
						if (html.match(regex)) {
							html = html.replace(regex, getLabel(new_attr_name));
							head.html(html);
							
							//convert html to LayoutUIEditor widgets
							ui_creator.recreateWidgetChildren(head);
						}
					};
					
					widget.find("[data-widget-item-column][data-widget-item-attribute-name='" + old_attr_name + "']").each(function(idx, column) {
						column = $(column);
						column.attr("data-widget-item-attribute-name", new_attr_name);
						//console.log("Found column:"+old_attr_name+":"+new_attr_name);
						
						//prepare sub-head if exists
						var head = column.find("[data-widget-item-head]");
						
						if (head[0]) 
							prepare_head_handler(head);
						
						//prepare sub-fields
						column.find("[data-widget-resource-value]").each(function(idy, field) {
							field = $(field);
							var widget_resource_value = me.getWidgetResourceValue(field);
							
							if ($.isPlainObject(widget_resource_value) && widget_resource_value.hasOwnProperty("attribute") && widget_resource_value["attribute"] == old_attr_name) {
								widget_resource_value["attribute"] = new_attr_name;
								field.attr("data-widget-resource-value", JSON.stringify(widget_resource_value));
								
								//in case the field is an edit field, check if its type is the same than the new attribute, otherwise replace it with the new html, but with the attributes from the old field.
								if (field.is("[data-widget-item-attribute-field-edit], [data-widget-item-attribute-field-add]")) {
									var html = field[0].hasAttribute("data-widget-item-attribute-field-edit") ? getWidgetItemAttributeFieldEditHtml(elm, widget, null, null, new_attr_name) : getWidgetItemAttributeFieldAddHtml(elm, widget, null, null, new_attr_name);
									
									if (html) {
										var new_field = $(html);
										
										//check if fields are the same type
										if (field[0].nodeName != new_field[0].nodeName || field.attr("type") != new_field.attr("type")) {
											field.after(new_field);
											
											//setting old attributes
											for (var i = 0, t = field[0].attributes.length; i < t; i++) {
												var attribute = field[0].attributes[i];
												
												if (attribute.name != "type" && attribute.name != "value")
													new_field[0].setAttribute(attribute.name, attribute.value);
											}
											
											//remove checked attribute is not apply (in case of non checkbox or radio buttons)
											if (new_field.is(":not([type=checkbox])") && new_field.is(":not([type=radio])") && new_field[0].hasAttribute("checked"))
												new_field.removeAttr("checked");
											
											//remove old field
											field.remove();
											
											//convert html to LayoutUIEditor widgets
											convertHtmlElementToLayoutUIEditorWidget(new_field);
										}
									}
								}
							}
						}).promise().done( function() {
							onChangeSelectedWidget();
						});
					}).promise().done( function() {
						onChangeSelectedWidget();
					});
					
					widget.find("[data-widget-item-head][data-widget-item-attribute-name='" + old_attr_name + "']").each(function(idx, head) {
						head = $(head);
						prepare_head_handler(head);
					}).promise().done( function() {
						onChangeSelectedWidget();
					});
				}
				else if (main_li.is(".widget-property-pks-attrs-names")) {
					var main_widget = me.getWidgetGroupMainWidget(widget);
					
					if (main_widget && main_widget.is("[data-widget-list], [data-widget-form]"))
						widget_with_pks = main_widget;
				}
				else if (main_li.is(".widget-property-default-attrs-values")) {
					var main_widget = me.getWidgetGroupMainWidget(widget);
					
					if (main_widget && main_widget.is("[data-widget-list], [data-widget-form]"))
						widget_with_defaults = main_widget;
				}
				else if (main_li.is(".widget-property-search-attrs-names")) {
					widget_search = widget;
				}
				
				//replace old_attr_name in pks in widget_with_pks properties
				if (widget_with_pks && widget_with_pks[0]) {
					var widget_with_pks_properties = me.getWidgetProperties(widget_with_pks);
					
					if (widget_with_pks_properties["pks_attrs_names"]) {
						var pks_attrs_names = widget_with_pks_properties["pks_attrs_names"];
						pks_attrs_names = $.isArray(pks_attrs_names) ? pks_attrs_names : (typeof pks_attrs_names == "string" ? pks_attrs_names.replace(/;/g, ",").split(",") : []);
						var array_pos = $.inArray(old_attr_name, pks_attrs_names);
						
						if (array_pos != -1) {
							var new_array_pos = $.inArray(new_attr_name, pks_attrs_names);
							
							if (new_array_pos != -1)
								pks_attrs_names.splice(array_pos, 1); //remove old_attr_name in array bc new_attr_name was already added.
							else
								pks_attrs_names.splice(array_pos, 1, new_attr_name); //replace old_attr_name in array
							
							widget_with_pks_properties["pks_attrs_names"] = pks_attrs_names.join(",");
							widget_with_pks.attr("data-widget-props", JSON.stringify(widget_with_pks_properties));
							
							onChangeSelectedWidget();
						}
					}
				}
				
				//replace old_attr_name in defaults in widget_with_defaults properties
				if (widget_with_defaults && widget_with_defaults[0]) {
					var default_attrs_values = me.getWidgetDefaultAttributesValues(widget_with_defaults);
					
					if ($.isPlainObject(default_attrs_values) && default_attrs_values.hasOwnProperty(old_attr_name)) {
						//remove old_attr_name
						default_attrs_values[old_attr_name] = null;
						delete default_attrs_values[old_attr_name];
						
						//add new_attr_name
						default_attrs_values[new_attr_name] = attr_value;
						
						widget_with_defaults.attr("data-widget-pks-attrs", JSON.stringify(default_attrs_values));
						
						onChangeSelectedWidget();
					}
				}
				
				//remove old_attr_name from search bar
				if (widget_search && widget_search[0]) {
					//remove search_attrs from input and select search fields
					var widget_search_items = widget_search.is("[data-widget-search-input], [data-widget-search-select]") ? widget_search : widget_search.find("[data-widget-search-input], [data-widget-search-select]");
					
					widget_search_items.each(function(idx, field) {
						field = $(field);
						var field_properties = me.getWidgetProperties(field);
						
						if (field_properties["search_attrs"]) {
							var search_attrs = field_properties["search_attrs"].replace(/;/g, ",").split(",");
							var array_pos = $.inArray(old_attr_name, search_attrs);
							
							if (array_pos != -1) {
								var new_array_pos = $.inArray(new_attr_name, search_attrs);
								
								if (new_array_pos != -1)
									search_attrs.splice(array_pos, 1); //remove old_attr_name in array bc new_attr_name was already added.
								else
									search_attrs.splice(array_pos, 1, new_attr_name); //remove old_attr_name from array
								
								field_properties["search_attrs"] = search_attrs.join(",");
								field.attr("data-widget-props", JSON.stringify(field_properties));
							}
						}
					}).promise().done( function() {
						onChangeSelectedWidget();
					});
					
					//remove option from data-widget-search-multiple
					var select = widget_search.find("[data-widget-search-multiple] select");
					
					if (select[0]) {
						var old_option = select.find("option[value='" + old_attr_name + "']");
						var new_option = select.find("option[value='" + new_attr_name + "']");
						
						if (new_option[0])
							removeWidget(old_option);
						else {
							old_option.attr("value", new_attr_name).attr("title", getLabel(new_attr_name)).html( getLabel(new_attr_name) );
							
							onChangeSelectedWidget();
						}
					}
				}
			}
		}
	}
	
	function addWidgetItemButton(elm, widget, button_selector, button_html) {
		addWidgetItemButtonToList(elm, widget, button_selector, button_html);
		addWidgetItemButtonToForm(elm, widget, button_selector, button_html);
	}
	
	function addWidgetItemAddButton(elm, widget, button_selector, button_html) {
		addWidgetItemButtonToList(elm, widget, button_selector, button_html, "[data-widget-item-add]");
		addWidgetItemButtonToForm(elm, widget, button_selector, button_html);
	}
	
	function addWidgetItemButtonToListTableHead(elm, widget, button_selector, button_html) {
		//if table and there is not a column for actions, add one
		var table = widget.is("table") ? widget : widget.find("[data-widget-list-table], table"); //it could be multiple tables
		
		//add data-widget-list-actions-head to table
		if (table[0])
			table.each(function(idx, table_item) {
				table_item = $(table_item);
				var tr = table_item.find("thead")[0] ? table_item.find("thead > tr") : table_item.find("tr:not([data-widget-item]):not([data-widget-item-add])").first(); //all thead trs or first tbody tr
				var button_added = false;
				var actions_added = false;
				
				tr.each(function(idx, tr_item) {
					tr_item = $(tr_item);
					var item_button_column = tr_item.find(button_selector);
				
					if (!item_button_column[0]) {
						var list_actions = tr_item.find("[data-widget-item-actions-head]");
						
						if (!list_actions[0]) {
							list_actions = $('<th class="border-0 pt-0 text-right text-end text-muted fw-normal align-middle small" data-widget-item-actions-head></th>');
							tr_item.append(list_actions);
							
							//convert html to LayoutUIEditor widgets
							convertHtmlElementToLayoutUIEditorWidget(list_actions);
							
							actions_added = true;
						}
						
						var button_elm = $(button_html);
						list_actions.append(button_elm);
						
						//convert html to LayoutUIEditor widgets
						convertHtmlElementToLayoutUIEditorWidget(button_elm);
						
						button_added = true;
					}
				});
				
				//add data-widget-item-actions-column if doesn't exists yet
				if (button_added)
					table_item.find("[data-widget-item], [data-widget-item-add]").each(function(idx, list_item) {
						list_item = $(list_item);
						var list_actions = list_item.find("[data-widget-item-actions-column]");
						
						if (!list_actions[0]) {
							list_actions = $('<td class="border-0 text-right text-end align-middle" data-widget-item-actions-column></td>');
							list_item.append(list_actions);
							
							//convert html to LayoutUIEditor widgets
							convertHtmlElementToLayoutUIEditorWidget(list_actions);
						}
					});
				
				//prepare colspan in tr[data-widget-loading] and tr[data-widget-empty]
				if (actions_added)
					table_item.find("tr[data-widget-loading], tr[data-widget-empty]").each(function(idx, tr) {
						var td = $(tr).children("td").last();
						var colspan = td.attr("colspan");
						
						colspan = (colspan > 0 ? parseInt(colspan) : 1) + 1;
						td.attr("colspan", colspan);
					}).promise().done( function() {
						onChangeSelectedWidget();
					});
			});
	}
		
	function addWidgetItemButtonToList(elm, widget, button_selector, button_html, item_selector) {
		//if table and there is not a column for actions, add one
		var table = widget.is("table") ? widget : widget.find("[data-widget-list-table], table"); //it could be multiple tables
		
		//add data-widget-list-actions-head to table
		if (table[0])
			table.each(function(idx, table_item) {
				table_item = $(table_item);
				var tr = table_item.find("thead")[0] ? table_item.find("thead > tr") : table_item.find("tr:not([data-widget-item]):not([data-widget-item-add])").first(); //all thead trs or first tbody tr
				var actions_added = false;
				
				tr.each(function(idx, tr_item) {
					tr_item = $(tr_item);
					var list_actions = tr_item.find("[data-widget-item-actions-head]");
					
					if (!list_actions[0]) {
						list_actions = $('<th class="border-0 pt-0 text-right text-end text-muted fw-normal align-middle small" data-widget-item-actions-head></th>');
						tr_item.append(list_actions);
						
						//convert html to LayoutUIEditor widgets
						convertHtmlElementToLayoutUIEditorWidget(list_actions);
						
						actions_added = true;
					}
				});
				
				//prepare colspan in tr[data-widget-loading] and tr[data-widget-empty]
				if (actions_added)
					table_item.find("tr[data-widget-loading], tr[data-widget-empty]").each(function(idx, tr) {
						var td = $(tr).children("td").last();
						var colspan = td.attr("colspan");
						
						colspan = (colspan > 0 ? parseInt(colspan) : 1) + 1;
						td.attr("colspan", colspan);
					}).promise().done( function() {
						onChangeSelectedWidget();
					});
			});
		
		//add remove button to all items
		if (widget.is("[data-widget-list], [data-widget-list-table], [data-widget-list-tree], table")) {
			item_selector = item_selector ? item_selector : "[data-widget-item]";
			
			widget.find("[data-widget-item], [data-widget-item-add]").each(function(idx, list_item) {
				list_item = $(list_item);
				var item_button_column = list_item.find(button_selector);
				
				if (!item_button_column[0]) {
					var list_actions = list_item.find("[data-widget-item-actions-column]");
					
					if (!list_actions[0]) {
						list_actions = $('<td class="border-0 text-right text-end align-middle" data-widget-item-actions-column></td>');
						list_item.append(list_actions);
						
						//convert html to LayoutUIEditor widgets
						convertHtmlElementToLayoutUIEditorWidget(list_actions);
					}
					
					if (list_item.is(item_selector)) {
						var button_elm = $(button_html);
						var other_buttons = list_actions.find("button");
						
						//in case there are other buttons inside of inner divs like btn-group, append the new button inside of the inner div
						if (other_buttons.length > 0)
							other_buttons.last().after(button_elm);
						else
							list_actions.append(button_elm);
						
						//convert html to LayoutUIEditor widgets
						convertHtmlElementToLayoutUIEditorWidget(button_elm);
					}
				}
			});
		}
	}
		
	function addWidgetItemButtonToForm(elm, widget, button_selector, button_html) {
		//if edit popup, add remove button
		if (widget.is("[data-widget-form], form"))
			widget.each(function(idx, form_item) {
				form_item = $(form_item);
				var item_button_column = form_item.find(button_selector);
				
				if (!item_button_column[0]) {
					var form_actions = form_item.find("[data-widget-item-actions-column]");
					
					if (!form_actions[0]) {
						form_actions = $('<div class="text-right text-end mt-4" data-widget-item-actions-column></div>');
						form_item.append(form_actions);
						
						//convert html to LayoutUIEditor widgets
						convertHtmlElementToLayoutUIEditorWidget(form_actions);
					}
					
					var button_elm = $(button_html);
					var other_buttons = form_actions.find("button");
					
					//in case there are other buttons inside of inner divs like btn-group, append the new button inside of the inner div
					if (other_buttons.length > 0)
						other_buttons.last().after(button_elm);
					else
						form_actions.append(button_elm);
					
					//convert html to LayoutUIEditor widgets
					convertHtmlElementToLayoutUIEditorWidget(button_elm);
				}
			});
	}
	
	function addWidgetSearch(elm, widget, type) {
		elm = $(elm);
		var widget_group = widget.closest("[data-widget-group-list]");
		var html = getWidgetSearchHtml(elm, widget_group, type);
		
		if (!widget_group[0]) {
			elm.prop("checked", false).removeAttr("checked");
			ui_creator.showError("Invalid widget group list.");
		}
		if (!html) {
			elm.prop("checked", false).removeAttr("checked");
			ui_creator.showError("Invalid attributes. Please select a DB Table first!");
		}
		else {
			var first = widget_group.find("[data-widget-short-actions], [data-widget-pagination], [data-widget-list]").first();
			
			html = $(html);
			
			//insert before first element
			if (first[0] && !first.is("[data-widget-list]")) //be sure that first is not a bottom pagination
				first.before(html);
			else //prepend to widget
				widget_group.prepend(html);
			
			//convert html to LayoutUIEditor widgets
			convertHtmlElementToLayoutUIEditorWidget(html);
		}
	}
	
	function addWidgetShortActions(elm, widget) {
		elm = $(elm);
		var html = getWidgetShortActionsHtml(elm, widget);
		var first = widget.find("[data-widget-search], [data-widget-pagination], [data-widget-list]").first();
		
		html = $(html);
		
		//insert before first element
		if (first[0] && !first.is("[data-widget-list]")) //be sure that first is not a bottom pagination
			first.before(html);
		else //prepend to widget
			widget.prepend(html);
		
		//convert html to LayoutUIEditor widgets
		convertHtmlElementToLayoutUIEditorWidget(html);
	}
	
	function prepareWidgetShortActions(elm, widget, short_actions_widget) {
		var multiple_save = short_actions_widget.find("[data-widget-button-multiple-save]");
		var multiple_remove = short_actions_widget.find("[data-widget-button-multiple-remove]");
		var add_button = short_actions_widget.find("[data-widget-button-add]");
		var toggle_between_widgets_button = short_actions_widget.find("[data-widget-button-toggle-between-widgets]");
		var toggle_list_attribute_select_checkboxes_button = short_actions_widget.find("[data-widget-button-toggle-list-attribute-select-checkboxes]");
		var reset_sorting_button = short_actions_widget.find("[data-widget-button-reset-sorting]");
		
		//prepare properties_settings
		var properties_settings = getWidgetSettingsData(elm, ".widget-properties");
		properties_settings = properties_settings["widget_properties"];
		
		//prepare dependent_widgets_id
		var dependent_widgets_id = getWidgetGroupDependentWidgetsId(widget);
		
		//Add "data-widget-button-multiple-save"
		var html = getWidgetShortActionsSaveButtonHtml(elm, widget, properties_settings, dependent_widgets_id);
		
		if (html) {
			if (!multiple_save[0]) {
				html = $(html);
				
				short_actions_widget.prepend(html);
				
				//convert html to LayoutUIEditor widgets
				convertHtmlElementToLayoutUIEditorWidget(html);
			}
		}
		else if (multiple_save[0])
			removeWidget(multiple_save);
		
		//Add "data-widget-button-multiple-remove"
		var html = getWidgetShortActionsRemoveButtonHtml(elm, widget, properties_settings, dependent_widgets_id);
		
		if (html) {
			if (!multiple_remove[0]) {
				html = $(html);
				short_actions_widget.prepend(html);
				
				//convert html to LayoutUIEditor widgets
				convertHtmlElementToLayoutUIEditorWidget(html);
			}
		}
		else if (multiple_remove[0])
			removeWidget(multiple_remove);
		
		//add or remove "add" button
		var html = getWidgetShortActionsAddButtonHtml(elm, widget, properties_settings, dependent_widgets_id);
		
		if (html) {
			if (!add_button[0]) {
				html = $(html);
				short_actions_widget.append(html);
				
				//convert html to LayoutUIEditor widgets
				convertHtmlElementToLayoutUIEditorWidget(html);
			}
		}
		else if (add_button[0])
			removeWidget(add_button);
		
		//add or remove toggle_between_widgets_button
		var html = getWidgetShortActionsToggleBetweenWidgetsButtonHtml(elm, widget, properties_settings, dependent_widgets_id);
		
		if (html) {
			if (!toggle_between_widgets_button[0]) {
				html = $(html);
				short_actions_widget.append(html);
				
				//convert html to LayoutUIEditor widgets
				convertHtmlElementToLayoutUIEditorWidget(html);
			}
		}
		else if (toggle_between_widgets_button[0])
			removeWidget(toggle_between_widgets_button);
		
		//add or remove toggle_list_attribute_select_checkboxes_button
		var html = getWidgetShortActionsToggleListAttributeSelectCheckboxesButtonHtml(elm, widget, properties_settings, dependent_widgets_id);
		
		if (html) {
			if (!toggle_list_attribute_select_checkboxes_button[0]) {
				html = $(html);
				short_actions_widget.append(html);
				
				//convert html to LayoutUIEditor widgets
				convertHtmlElementToLayoutUIEditorWidget(html);
			}
		}
		else if (toggle_list_attribute_select_checkboxes_button[0])
			removeWidget(toggle_list_attribute_select_checkboxes_button);
		
		//add or remove reset_sorting_button
		var html = getWidgetShortActionsResetSortingButtonHtml(elm, widget, properties_settings, dependent_widgets_id);
		
		if (html) {
			if (!reset_sorting_button[0]) {
				html = $(html);
				short_actions_widget.append(html);
				
				//convert html to LayoutUIEditor widgets
				convertHtmlElementToLayoutUIEditorWidget(html);
			}
		}
		else if (reset_sorting_button[0])
			removeWidget(reset_sorting_button);
	}
	
	function addWidgetListMultipleItemsActions(elm, widget) {
		var main_widget = me.getWidgetGroupMainWidget(widget);
		
		if (main_widget) {
			var table = main_widget.is("table") ? main_widget : main_widget.find("[data-widget-list-table], table"); //it could be multiple tables
			
			if (!table[0])
				table = widget.find("[data-widget-list-table], table"); //it could be multiple tables
			
			var select_items_head = table.find("[data-widget-list-select-items-head]");
			
			var properties_settings = getWidgetSettingsData(elm, ".widget-properties");
			properties_settings = properties_settings["widget_properties"];
			
			var multiple_items_actions = ($.isPlainObject(properties_settings["multiple_items_actions"]) && properties_settings["multiple_items_actions"]["type"]) || (!$.isPlainObject(properties_settings["multiple_items_actions"]) && properties_settings["multiple_items_actions"]); //it could be from: data-widget-group-list or data-widget-list
			var multiple_items_actions_permissions = multiple_items_actions && $.isPlainObject(properties_settings["multiple_items_actions"]) ? properties_settings["multiple_items_actions"]["permissions"] : false;
			var multiple_items_actions_permissions_html = multiple_items_actions_permissions ? ' data-widget-permissions="' + JSON.stringify(multiple_items_actions_permissions).replace(/"/g, "&quot;") + '"' : '';
			
			//add data-widget-list-select-items-head to table
			if (table[0] && !select_items_head[0]) {
				var html = getWidgetListTableSelectItemsHeadHtml(multiple_items_actions_permissions_html);
				
				table.each(function(idx, table_item) {
					table_item = $(table_item);
					var tr = table_item.find("thead")[0] ? table_item.find("thead > tr") : table_item.find("tr:not([data-widget-item]):not([data-widget-item-add])").first(); //all thead trs or first tbody tr
					var actions_added = false;
					
					tr.each(function(idx, tr_item) {
						tr_item = $(tr_item);
						var list_actions = tr_item.find("[data-widget-list-select-items-head]");
						
						if (!list_actions[0]) {
							var html_elm = $(html);
							
							tr.prepend(html_elm);
							
							//convert html to LayoutUIEditor widgets
							convertHtmlElementToLayoutUIEditorWidget(html_elm);
							
							actions_added = true;
						}
					});
					
					//prepare colspan in tr[data-widget-loading] and tr[data-widget-empty]
					if (actions_added)
						table_item.find("tr[data-widget-loading], tr[data-widget-empty]").each(function(idx, tr) {
							var td = $(tr).children("td").last();
							var colspan = td.attr("colspan");
							
							colspan = (colspan > 0 ? parseInt(colspan) : 1) + 1;
							td.attr("colspan", colspan);
						}).promise().done( function() {
							onChangeSelectedWidget();
						});
				});
			}
			
			//add data-widget-item-selected-column to table
			main_widget.find("[data-widget-item], [data-widget-item-add]").each(function(idx, list_item) {
				list_item = $(list_item);
				var item_selected_column = list_item.find("[data-widget-item-selected-column]");
				
				if (!item_selected_column[0]) {
					var is_table = list_item.parent().closest("[data-widget-list-table], table");
					var is_tree = list_item.parent().closest("[data-widget-list-tree]");
					var is_item_add = list_item.is("[data-widget-item-add]");
					var html = "";
					
					if (is_table)
						html = is_item_add ? getWidgetListTableItemAddSelectedColumnHtml(multiple_items_actions_permissions_html) : getWidgetListTableItemSelectedColumnHtml(multiple_items_actions_permissions_html);
					else if (is_tree)
						html = is_item_add ? getWidgetListTreeItemAddSelectedColumnHtml(multiple_items_actions_permissions_html) : getWidgetListTreeItemSelectedColumnHtml(multiple_items_actions_permissions_html);
					
					if (html) {
						html = $(html);
						
						list_item.prepend(html);
						
						//convert html to LayoutUIEditor widgets
						convertHtmlElementToLayoutUIEditorWidget(html);
					}
				}
			});
		}
	}
	
	function removeWidgetListMultipleItemsActions(elm, widget) {
		var main_widget = me.getWidgetGroupMainWidget(widget);
		
		if (main_widget) {
			var table = main_widget.is("table") ? main_widget : main_widget.find("[data-widget-list-table], table"); //it could be multiple tables
			
			if (!table[0])
				table = widget.find("[data-widget-list-table], table"); //it could be multiple tables
			
			var select_items_head = table.find("[data-widget-list-select-items-head]");
			
			if (select_items_head[0]) {
				removeWidget(select_items_head);
				
				//prepare colspan in tr[data-widget-loading] and tr[data-widget-empty]
				table.find("tr[data-widget-loading], tr[data-widget-empty]").each(function(idx, tr) {
					var td = $(tr).children("td").last();
					
					if (td[0].hasAttribute("colspan")) {
						var colspan = td.attr("colspan");
						
						colspan = (colspan > 0 ? parseInt(colspan) : 1) - 1;
						
						if (colspan > 0)
							td.attr("colspan", colspan);
						else
							td.removeAttr("colspan");
					}
				}).promise().done( function() {
					onChangeSelectedWidget();
				});
			}
			
			removeWidget( main_widget.find("[data-widget-item-selected-column]") );
		}
	}
	
	function updateWidgetExistentResourcesReferences(elms, force) {
		updateWidgetExistentData(elms, me.options.get_resources_references_func, "existent_resources_references", force);
	}
	
	function updateWidgetExistentUserTypes(elms, force) {
		updateWidgetExistentData(elms, me.options.get_user_types_func, "existent_user_types", force);
	}
	
	function updateWidgetExistentWidgetIds(elms, force) {
		var callback = function() {
			var items = [];
			var template_widgets_iframe_body = ui_creator.getTemplateWidgetsIframeBody();
			
			if (template_widgets_iframe_body) {
				//var widget_types_attributes = getDefaultWidgetTypesAttributes();
				//var selector = "[" + widget_types_attributes.join("], [") + "]";
				var selector = "[id]";
				
				var widgets = template_widgets_iframe_body.find(selector);
				
				$.each(widgets, function(idx, widget) {
					if (widget.hasAttribute("id"))
						items.push(widget.getAttribute("id"));
				});
			}
			
			return items;
		};
		
		updateWidgetExistentData(elms, callback, "existent_widget_ids", force);
	}
	
	function updateWidgetExistentPopupIds(elms, force) {
		var callback = function() {
			var items = [];
			var template_widgets_iframe_body = ui_creator.getTemplateWidgetsIframeBody();
			
			if (template_widgets_iframe_body) {
				var widgets = template_widgets_iframe_body.find("[data-widget-popup], [data-widget-popup-view], [data-widget-popup-edit], [data-widget-popup-add]");
				
				$.each(widgets, function(idx, widget) {
					if (widget.hasAttribute("id"))
						items.push(widget.getAttribute("id"));
				});
			}
			
			return items;
		};
		
		updateWidgetExistentData(elms, callback, "existent_popup_ids", force);
	}
	
	function updateWidgetExistentData(elms, callback, cache_key, force) {
		if (typeof callback == "function") {
			var items = executeCallback(callback, cache_key, force);
			
			if (items) {
				var html = '';
				var new_items = items;
				
				if ($.isArray(items)) {
					new_items = {};
					
					$.each(items, function(idx, v) {
						new_items[v] = v;
					});
					
					items = new_items;
				}
				
				$.each(items, function(k, v) {
					html += '<option value="' + k + '" title="' + v + '">' + v + '</option>';
				});
				
				$.each(elms, function(idx, elm) {
					elm = $(elm);
					
					if (elm.is("select")) {
						var value = elm.val();
				
						elm.find("option:not([value=''])").remove();
						elm.append(html);
						elm.val(value);
						
						if (elm.val() != value) {
							var li = elm.parent().closest("li");
							
							if (li.hasClass("widget-swap-field")) {
								if (li.hasClass("select-shown")) { //show input instead of select
									me.swapInputAndSelectFields(li.children(".swap-input-select")[0], window.event);
									li.children("input").val(value);
								}
							}
							else
								elm.append('<option value="' + value + '">' + value + ' - DEPRECATED</option>');
						}
					}
				});
			}
		}
	}
	
	function executeCallback(callback, cache_key, force) {
		var items = null;
		
		//get from cache if not force
		if (cache_key) {
			if (!force && internal_cached_data.hasOwnProperty(cache_key) && internal_cached_data[cache_key])
				items = internal_cached_data[cache_key];
			else if (typeof callback == "function") {
				items = callback();
				internal_cached_data[cache_key] = items; //set cache
			}
		}
		else //get from callback
			items = callback();
		
		return items;
	}
	
	function updateWidgetDBBrokers(parent) {
		var li = parent.children(".widget-property-db-broker");
		var select = li.children("select");
		var input = li.children("input");
		var value = li.hasClass("widget-swap-field") && !li.hasClass("select-shown") ? input.val() : select.val();
		
		select.find("option:not([value=''])").remove();
		
		var db_brokers = getWidgetDBBrokers();
		
		if ($.isArray(db_brokers) && db_brokers.length > 0) {
			var html = "";
			
			$.each(db_brokers, function (idx, broker_name) {
				html += '<option' + (broker_name == value ? ' selected' : '') + '>' + broker_name + '</option>';
			});
			
			select.append(html);
			
			if (li.hasClass("widget-swap-field") && !li.hasClass("select-shown")) //show selects instead of inputs
				me.swapInputAndSelectFields(select.parent().children(".swap-input-select")[0], window.event);
		}
		else if (li.hasClass("widget-swap-field") && li.hasClass("select-shown")) { //show inputs instead of selects
			me.swapInputAndSelectFields(parent.children(".widget-property-db-broker").children(".swap-input-select")[0], window.event);
			me.swapInputAndSelectFields(parent.children(".widget-property-db-driver").children(".swap-input-select")[0], window.event);
			me.swapInputAndSelectFields(parent.children(".widget-property-db-table").children(".swap-input-select")[0], window.event);
		}
		
		var db_broker = select.val();
		updateWidgetDBDrivers(parent, db_broker);
	}
	
	function updateWidgetDBDrivers(parent, db_broker) {
		var li = parent.children(".widget-property-db-driver");
		var select = li.children("select");
		var input = li.children("input");
		var value = li.hasClass("widget-swap-field") && !li.hasClass("select-shown") ? input.val() : select.val();
		
		select.find("option:not([value=''])").remove();
		
		//get db_drivers based in db_broker
		var db_drivers = getWidgetDBDrivers(db_broker);
		
		if ($.isArray(db_drivers) && db_drivers.length > 0) {
			var html = "";
			
			$.each(db_drivers, function (idx, driver_name) {
				html += '<option' + (driver_name == value ? ' selected' : '') + '>' + driver_name + '</option>';
			});
			
			select.append(html);
			
			if (li.hasClass("widget-swap-field") && !li.hasClass("select-shown")) //show selects instead of inputs
				me.swapInputAndSelectFields(select.parent().children(".swap-input-select")[0], window.event);
		}
		else if (li.hasClass("widget-swap-field") && li.hasClass("select-shown")) { //show inputs instead of selects
			me.swapInputAndSelectFields(parent.children(".widget-property-db-driver").children(".swap-input-select")[0], window.event);
			me.swapInputAndSelectFields(parent.children(".widget-property-db-table").children(".swap-input-select")[0], window.event);
		}
		
		var db_broker = parent.find(" > .widget-property-db-broker > select").val();
		var db_driver = select.val();
		var db_type = parent.find(" > .widget-property-db-type > select").val();
		
		updateWidgetDBTables(parent, db_broker, db_driver, db_type);
	}
	
	function updateWidgetDBTables(parent, db_broker, db_driver, db_type) {
		var li = parent.children(".widget-property-db-table");
		var select = li.children("select");
		var input = li.children("input");
		var value = li.hasClass("widget-swap-field") && !li.hasClass("select-shown") ? input.val() : select.val();
		
		select.find("option:not([value=''])").remove();
		
		//get db_tables based in db_broker and db_driver and db_type
		var db_tables = getWidgetDBTables(db_broker, db_driver, db_type);
		
		if ($.isArray(db_tables) && db_tables.length > 0) {
			var html = "";
			
			$.each(db_tables, function (idx, table_name) {
				html += '<option' + (table_name == value ? ' selected' : '') + '>' + table_name + '</option>';
			});
			
			select.append(html);
			
			if (li.hasClass("widget-swap-field") && !li.hasClass("select-shown")) //show selects instead of inputs
				me.swapInputAndSelectFields(select.parent().children(".swap-input-select")[0], window.event);
		}
		
		var db_broker = parent.find(" > .widget-property-db-broker > select").val();
		var db_driver = parent.find(" > .widget-property-db-driver > select").val();
		var db_type = parent.find(" > .widget-property-db-type > select").val();
		var db_table = select.val();
		
		updateWidgetDBAttributes(parent, db_broker, db_driver, db_type, db_table);
	}
	
	function updateWidgetDBAttributes(parent, db_broker, db_driver, db_type, db_table) {
		var li = parent.children(".widget-property-db-attribute, .widget-property-shown-attrs-names, .widget-property-pks-attrs-names, .widget-property-default-attrs-values, .widget-property-search-attrs-names");
		var select = li.children("select");
		var value = select.val();
		
		//clean select
		select.find("option:not([value=''])").remove();
		
		//get table attributes based in db_table
		var db_attributes = getWidgetDBAttributes(db_broker, db_driver, db_type, db_table);
		
		//set new options
		var options = "";
		
		if ($.isPlainObject(db_attributes) && !$.isEmptyObject(db_attributes))
			$.each(db_attributes, function (attr_name, attr) {
				options += '<option' + (attr_name == value ? ' selected' : '') + '>' + attr_name + '</option>';
			});
		
		select.append(options);
	}
	
	//return an array with db brokers name
	function getWidgetDBBrokers() {
		return executeCallback(me.options.get_db_brokers_func, "db_brokers");
	}
	
	//return an array with db drivers name
	function getWidgetDBDrivers(db_broker) {
		if (typeof me.options.get_db_drivers_func == "function") {
			//if getWidgetDBBrokers was not yet called, call it first
			if (!internal_cached_data.hasOwnProperty("db_brokers"))
				getWidgetDBBrokers();
			
			var callback = function() {
				return me.options.get_db_drivers_func(db_broker);
			};
			return executeCallback(callback, "db_broker_drivers_" + db_broker);
		}
		
		return null;
	}
	
	//return an array with db tables name
	function getWidgetDBTables(db_broker, db_driver, db_type) {
		if (typeof me.options.get_db_tables_func == "function") {
			//if getWidgetDBDrivers was not yet called, call it first
			if (!internal_cached_data.hasOwnProperty("db_broker_drivers_" + db_broker))
				getWidgetDBDrivers(db_broker);
			
			var callback = function() {
				return me.options.get_db_tables_func(db_broker, db_driver, db_type);
			};
			return executeCallback(callback, "db_broker_driver_tables_" + db_broker + "_" + db_driver + "_" + db_type);
		}
		
		return null;
	}
	
	//return an array with db attributes with key=="attribute name" and value="attribute properties"
	function getWidgetDBAttributes(db_broker, db_driver, db_type, db_table) {
		if (typeof me.options.get_db_attributes_func == "function") {
			//if getWidgetDBTables was not yet called, call it first
			if (!internal_cached_data.hasOwnProperty("db_broker_driver_tables_" + db_broker + "_" + db_driver + "_" + db_type))
				getWidgetDBTables(db_broker, db_driver, db_type);
			
			var callback = function() {
				return me.options.get_db_attributes_func(db_broker, db_driver, db_type, db_table);
			};
			return executeCallback(callback, "db_broker_driver_table_attributes_" + db_broker + "_" + db_driver + "_" + db_type + "_" + db_table);
		}
		
		return null;
	}
	
	function getWidgetGroupDBAttributes(widget) {
		var db_attributes = null;
		var properties = me.getWidgetGroupProperties(widget);
		
		if (properties)
			db_attributes = getWidgetDBAttributes(properties["db_broker"], properties["db_driver"], properties["db_type"], properties["db_table"]);
		
		if (!db_attributes || $.isEmptyObject(db_attributes))
			ui_creator.showError("Invalid attributes. Please select a DB Table first!");
	
		return db_attributes;
	}
	
	function hasDBAttributesPKs(db_attributes) {
		//prepare pks_attrs_names
		var has_pks = false;
		
		$.each(db_attributes, function(attr_name, attr) {
			if (attr["primary_key"]) {
				has_pks = true;
				return false;
			}
		});
		
		return has_pks;
	}
	
	function isDBAttributePK(no_pks, attr_name, attr, ignore_auto_increment) {
		if (no_pks) {
			//only include attributes that are not internal attributes like: created_date or modified_date, modified_by created_by, created_user_id, modified_user_id...
			if (!isDBInternalAttributeName(attr_name))
				return true;
		}
		else if (attr["primary_key"] && (!ignore_auto_increment || !attr["auto_increment"]))
			return true;
		
		return false;
	}
	
	function getDBAttributesPKsAttrsNames(db_attributes, ignore_auto_increment) {
		//prepare pks_attrs_names
		var pks_attrs_names = "";
		var no_pks = !hasDBAttributesPKs(db_attributes);
		
		$.each(db_attributes, function(attr_name, attr) {
			if (isDBAttributePK(no_pks, attr_name, attr, ignore_auto_increment))
				pks_attrs_names += (pks_attrs_names ? "," : "") + attr_name;
		});
		
		return pks_attrs_names;
	}
	
	function purgeInternalAttributesFromWidgetDBAttributes(db_attributes) {
		//only include attributes that are not internal attributes like: created_date or modified_date, modified_by created_by, created_user_id, modified_user_id...
		var new_db_attributes = {};
		
		$.each(db_attributes, function(attr_name, attr) {
			if (attr["primary_key"] || !isDBInternalAttributeName(attr_name))
				new_db_attributes[attr_name] = db_attributes[attr_name];
		});
		
		return new_db_attributes;
	}
	
	function getDefaultWidgetTypesAttributes() {
		var widget_types_attributes = [];
		var widget_types = getDefaultWidgetTypes();
		
		var handler = function(obj) {
			for (var k in obj) {
				var v = obj[k];
				
				if ($.isPlainObject(v))
					handler(v);
				else
					widget_types_attributes.push(k);
			}
		};
		
		handler(widget_types);
		
		return widget_types_attributes;
	}
	
	function getDefaultWidgetTypes() {
		return {
			"Main Widgets": {
				"data-widget-group-list": "Main list group",
				"data-widget-group-form": "Main form group"
			},
			"Form Widgets": {
				"data-widget-form": "Form"
			},
			"List Widgets": {
				"data-widget-list": "List group - with table/tree",
				"data-widget-list-table": "List table (table)",
				"data-widget-list-tree": "List tree (ul/ol)",
				"data-widget-list-caption": "Caption with the list info, like total number of records...",
				
				"Inside of Table or Tree": {
					"data-widget-loading": "Loading bar row",
					"data-widget-empty": "Empty items row",
					"data-widget-empty-add": "Div or other html element inside of the [data-widget-empty] element which contains an empty message with a button to add new records, like the [data-widget-button-add] button.",
				},
				
				"Inside of List Caption": {
					"data-widget-list-caption-items-total": "Total number of items",
					"data-widget-list-caption-items-limit-per-page": "Total number of items per page",
					"data-widget-list-caption-num-pages": "Total number of pages",
					"data-widget-list-caption-page-number": "Current page number shown",
					"data-widget-list-caption-start-index": "Current start index of items shown",
					"data-widget-list-caption-end-index": "Current end index of items shown",
					"data-widget-list-caption-info": "Label with from-to items index and total number of items",
				},
				
				"Properties": {
					"data-widget-list-with-toggle-table-tree": "List contains toggle between table and tree"
				}
			},
			"Item Widgets (in table/tree/form)": {
				"data-widget-item": "Row item (tr/div)",
				"data-widget-item-add": "Row to add new inline record (tr/div)",
				
				"Attribute Columns": {
					"data-widget-item-head": "Column label/head (th/label)",
					"data-widget-item-column": "Column body (td/div)",
					
					"data-widget-list-select-items-head": "Column label/head (th/label) that may contain a checkbox to toggle the [data-widget-item-selected-checkbox] element",
					"data-widget-item-selected-column": "Column body (td/div) that contains the [data-widget-item-selected-checkbox] element",
					
					"Body Fields": {
						"data-widget-item-selected-checkbox": "Checkbox to select row/item for short action",
						"data-widget-item-attribute-field-view": "Readonly attribute field",
						"data-widget-item-attribute-field-edit": "Editable attribute field",
						"data-widget-item-attribute-field-add": "Addable attribute field",
						"data-widget-item-attribute-field-toggle-select-input": "Editable attribute group with combox and input fields where you can toggle between each other.",
						"data-widget-item-attribute-link-view": "Linkable attribute field to open readonly popup with item details",
						"data-widget-item-attribute-link-edit": "Linkable attribute field to open editable popup with item details"
					}
				},
				"Button/Action Columns": {
					"data-widget-item-actions-head": "Column head with actions (th - in table only)",
					"data-widget-item-actions-column": "Column body with actions (td/div)",
					
					"Head Buttons (in table only)": {
						"data-widget-list-select-items-checkbox": "Checkbox to toggle all items in table",
						"data-widget-button-add": "Button to add new record"
					},
					"Body Buttons": {
						"data-widget-item-button-add": "Button to insert a new added row",
						"data-widget-item-button-add-cancel": "Button to rollback/cancel a new added row",
						"data-widget-item-button-update": "Button to save an existent row",
						"data-widget-item-button-remove": "Button to remove an existent row",
						"data-widget-item-button-edit": "Button to open editable popup with item details",
						"data-widget-item-button-view": "Button to open readonly popup with item details",
						"data-widget-item-button-toggle-inline-edit-view": "Button to toggle between edit and view fields in a row"
					}
				},
				
				"Properties": {
					"data-widget-with-button-toggle-inline-edit-view": "Row item contains button to toggle between edit and view fields."
				}
			},
			"Search Widgets": {
				"data-widget-search": "Search group",
				"data-widget-search-input": "Input box for search",
				"data-widget-search-select": "Dropdown box for search",
				"Multiple Fields Search": {
					"data-widget-search-multiple": "Search group element based on multiple fields",
					"data-widget-search-added-attrs": "Element that will contain the added attributes boxes to search",
					"data-widget-search-added-attrs-empty": "Element with empty message when no added attributes boxes to search",
					"data-widget-search-added-attrs-item": "Element that will be used to add the attribute box to search",
					"data-widget-search-multiple-field": "Editable field for user to write his search",
					"data-widget-search-multiple-button": "Button to execute search based on multiple fields"
				}
			},
			"Short Actions Widgets": {
				"data-widget-short-actions": "Short actions group",
				"data-widget-button-multiple-remove": "Button to remove multiple items",
				"data-widget-button-multiple-save": "Button to save multiple items",
				"data-widget-button-add": "Button to add new record",
				"data-widget-button-toggle-between-widgets": "Button to toggle between table or tree in a list",
				"data-widget-button-toggle-list-attribute-select-checkboxes": "Button to toggle items selection in a list",
				"data-widget-button-reset-sorting": "Button to reset sorting"
			},
			"Pagination Widgets": {
				"data-widget-pagination": "Pagination group",
				
				"Dropdown": {
					"data-widget-pagination-go-to-page": "Pagination dropdown group",
					"data-widget-pagination-go-to-page-dropdown": "Dropdown box with page numbers"
				},
				"Pages": {
					"data-widget-pagination-pages": "Pagination pages group",
					"data-widget-pagination-pages-first": "Link for first page",
					"data-widget-pagination-pages-previous": "Link for previous page",
					"data-widget-pagination-pages-next": "Link for next page",
					"data-widget-pagination-pages-last": "Link for last page",
					
					"Page Numbers": {
						"data-widget-pagination-pages-numbers": "Pagination numbers group",
						"data-widget-pagination-pages-numbers-item": "Link for page number",
						"data-widget-pagination-pages-numbers-item-value": "Page number value inside of [data-widget-pagination-pages-numbers-item] element",
					}
				}
			},
			"Popup Widgets": {
				"data-widget-popup": "Popup",
				"data-widget-popup-view": "Readonly Popup",
				"data-widget-popup-edit": "Editable Popup",
				"data-widget-popup-add": "Addable Popup"
			},
			"Other Widgets": {
				"data-widget-html-node": "Html Node",
				"data-widget-graph": "Graph",
				"Calendar Widgets": {
					"data-widget-calendar": "Calendar group",
					
					"Inside of Calendar": {
						"data-widget-loading": "Loading bar node",
					},
				},
				"Matrix Widgets": {
					"data-widget-matrix": "Matrix group",
					
					"Inside of Matrix": {
						"data-widget-matrix-head-row": "Head Row",
						"data-widget-matrix-head-column": "Head Column",
						"data-widget-matrix-head-column-group": "Head Column Group/Combined",
						"data-widget-matrix-body-row": "Body Row",
						"data-widget-matrix-body-column": "Body Column",
						"data-widget-item": "Column Item",
						"data-widget-loading": "Loading row",
						"data-widget-empty": "Empty items row",
						
						"Properties": {
							"data-widget-matrix-previous-related": "Related with previous element"
						}
					},
				}
			}
		};
	}
	
	/*
	 * Load:
	 * 	data-widget-pagination: MyWidgetResourceLib.PaginationHandler.loadPaginationResource
	 * 	data-widget-pagination-go-to-page-dropdown: (optional - default: MyWidgetResourceLib.PaginationHandler.loadDropdownPages)
	 * 	data-widget-pagination-pages-numbers-item: (optional - default: MyWidgetResourceLib.PaginationHandler.loadItemPage)
	 * 	data-widget-list: MyWidgetResourceLib.ListHandler.loadListTableAndTreeResource
	 * 	data-widget-popup: MyWidgetResourceLib.PopupHandler.loadPopupResource
	 * 	data-widget-form: MyWidgetResourceLib.FormHandler.loadFormResource
	 * 	all others (like a select box): MyWidgetResourceLib.FieldHandler.loadFieldResource and MyWidgetResourceLib.FieldHandler.cacheFieldResource
	 */
	function getSelectedWidgetMyWidgetResourceLibJSHandlerFunctions(widget) {
		var elm = widget[0];
		
		if (elm.hasAttribute("data-widget-pagination"))
			return {
				"load": {
					"MyWidgetResourceLib.PaginationHandler.loadPaginationResource" : "loadPaginationResource"
				}
			};
		else if (elm.hasAttribute("data-widget-pagination-go-to-page-dropdown"))
			return {
				"load": {
					"" : "-- Default --",
					"MyWidgetResourceLib.PaginationHandler.loadDropdownPages" : "loadDropdownPages"
				},
				"get": {
					"": "-- Default --",
					"MyWidgetResourceLib.PaginationHandler.getDropdownSelectedPageNumber": "getDropdownSelectedPageNumber"
				},
				"set": {
					"": "-- Default --",
					"MyWidgetResourceLib.PaginationHandler.setDropdownSelectedPageNumber": "setDropdownSelectedPageNumber"
				}
			};
		else if (elm.hasAttribute("data-widget-pagination-pages-numbers-item"))
			return {
				"load": {
					"" : "-- Default --",
					"MyWidgetResourceLib.PaginationHandler.loadItemPage" : "loadItemPage"
				}
			};
		else if (elm.hasAttribute("data-widget-list") || elm.hasAttribute("data-widget-list-table") || elm.hasAttribute("data-widget-list-tree") || elm.nodeName == "TABLE" || elm.nodeName == "UL" || elm.nodeName == "OL")
			return {
				"load": {
					"MyWidgetResourceLib.ListHandler.loadListTableAndTreeResource" : "loadListTableAndTreeResource"
				}
			};
		else if (elm.hasAttribute("data-widget-list-caption"))
			return {
				"load": {
					"MyWidgetResourceLib.ListHandler.loadListCaptionResource" : "loadListCaptionResource"
				}
			};
		else if (elm.hasAttribute("data-widget-popup") || ui_creator.hasNodeClass(widget, "modal"))
			return {
				"load": {
					"MyWidgetResourceLib.PopupHandler.loadPopupResource" : "loadPopupResource"
				},
				"show": {
					"MyWidgetResourceLib.FormHandler.resetElementForms" : "resetElementForms"
				},
				"hide": {
					"MyWidgetResourceLib.FormHandler.resetElementForms" : "resetElementForms"
				}
			};
		else if (elm.hasAttribute("data-widget-form") || widget.is("form"))
			return {
				"load": {
					"MyWidgetResourceLib.FormHandler.loadFormResource" : "loadFormResource"
				}
			};
		else if (widget.is("select"))
			return {
				"load": {
					"MyWidgetResourceLib.FieldHandler.loadElementFieldsResource" : "loadElementFieldsResource",
					"MyWidgetResourceLib.FieldHandler.loadFieldResource" : "loadFieldResource",
					"MyWidgetResourceLib.FieldHandler.loadSelectFieldResource" : "loadSelectFieldResource",
					"MyWidgetResourceLib.FieldHandler.cacheFieldResource" : "cacheFieldResource"
				}
			};
		else 
			return {
				"load": {
					"MyWidgetResourceLib.FieldHandler.loadElementFieldsResource" : "loadElementFieldsResource",
					"MyWidgetResourceLib.FieldHandler.loadElementSelectFieldsResource" : "loadElementSelectFieldsResource",
					"MyWidgetResourceLib.FieldHandler.loadFieldResource" : "loadFieldResource",
					"MyWidgetResourceLib.FieldHandler.cacheFieldResource" : "cacheFieldResource"
				}
			};
	}
	
	function getSelectedWidgetMyWidgetResourceLibJSCompleteCallbackFunctions(widget) {
		var elm = widget[0];
		
		if (elm.hasAttribute("data-widget-list"))
			return {
				"add": {
					"MyWidgetResourceLib.ListHandler.reloadParentListResource": "reloadParentListResource",
					"MyWidgetResourceLib.ListHandler.onAddResourceItem": "onAddResourceItem"
				},
				"update": {
					"MyWidgetResourceLib.ListHandler.reloadParentListResource": "reloadParentListResource",
					"MyWidgetResourceLib.ItemHandler.updateViewFieldsBasedInEditFields": "updateViewFieldsBasedInEditFields",
					"MyWidgetResourceLib.ListHandler.onUpdateResourceItem": "onUpdateResourceItem"
				},
				"update_attribute": {
					"MyWidgetResourceLib.ListHandler.reloadParentListResource": "reloadParentListResource",
					"MyWidgetResourceLib.ItemHandler.updateViewFieldsBasedInEditFields": "updateViewFieldsBasedInEditFields",
					"MyWidgetResourceLib.ListHandler.onUpdateResourceItemAttribute": "onUpdateResourceItemAttribute",
					"MyWidgetResourceLib.ListHandler.purgeCachedLoadParentListResource": "purgeCachedLoadParentListResource"
				},
				"remove": {
					"MyWidgetResourceLib.ListHandler.reloadParentListResource": "reloadParentListResource",
					"MyWidgetResourceLib.ListHandler.onRemoveResourceItem": "onRemoveResourceItem"
				}
			};
		
		if (elm.hasAttribute("data-widget-form") || widget.is("form"))
			return {
				"load": {
					"MyWidgetResourceLib.FieldHandler.onLoadElementFieldsResourceWithSelectSearchFields": "onLoadElementFieldsResourceWithSelectSearchFields",
					"MyWidgetResourceLib.FieldHandler.loadElementSelectFieldsResource" : "loadElementSelectFieldsResource",
				},
				"add": {
					"MyWidgetResourceLib.PopupHandler.closeParentPopup": "closeParentPopup",
					"MyWidgetResourceLib.FormHandler.reloadParentFormResource": "reloadParentFormResource",
					"MyWidgetResourceLib.FormHandler.onAddPopupResourceItem": "onAddPopupResourceItem",
					"MyWidgetResourceLib.FormHandler.onAddResourceItem": "onAddResourceItem",
					"MyWidgetResourceLib.FormHandler.onAddResourceItemAndConvertItIntoEditForm": "onAddResourceItemAndConvertItIntoEditForm",
					"MyWidgetResourceLib.FormHandler.resetForm": "resetForm"
				},
				"update": {
					"MyWidgetResourceLib.PopupHandler.closeParentPopup": "closeParentPopup",
					"MyWidgetResourceLib.FormHandler.reloadParentFormResource": "reloadParentFormResource",
					"MyWidgetResourceLib.ItemHandler.updateViewFieldsBasedInEditFields": "updateViewFieldsBasedInEditFields",
					"MyWidgetResourceLib.FormHandler.onUpdatePopupResourceItem": "onUpdatePopupResourceItem",
					"MyWidgetResourceLib.FormHandler.onUpdateResourceItem": "onUpdateResourceItem",
					"MyWidgetResourceLib.FormHandler.resetForm": "resetForm"
				},
				"update_attribute": {
					"MyWidgetResourceLib.PopupHandler.closeParentPopup": "closeParentPopup",
					"MyWidgetResourceLib.FormHandler.reloadParentFormResource": "reloadParentFormResource",
					"MyWidgetResourceLib.ItemHandler.updateViewFieldsBasedInEditFields": "updateViewFieldsBasedInEditFields",
					"MyWidgetResourceLib.FormHandler.onUpdatePopupResourceItemAttribute": "onUpdatePopupResourceItemAttribute",
					"MyWidgetResourceLib.FormHandler.onUpdateResourceItemAttribute": "onUpdateResourceItemAttribute",
					"MyWidgetResourceLib.ListHandler.purgeCachedLoadParentFormResource": "purgeCachedLoadParentFormResource"
				},
				"remove": {
					"MyWidgetResourceLib.PopupHandler.closeParentPopup": "closeParentPopup",
					"MyWidgetResourceLib.FormHandler.reloadParentFormResource": "reloadParentFormResource",
					"MyWidgetResourceLib.FormHandler.onRemovePopupResourceItem": "onRemovePopupResourceItem",
					"MyWidgetResourceLib.FormHandler.onRemoveResourceItem": "onRemoveResourceItem",
					"MyWidgetResourceLib.FormHandler.resetForm": "resetForm"
				}
			};
		
		if (widget.is("select"))
			return {
				"load": {
					"MyWidgetResourceLib.FieldHandler.setWidgetResourceValueDefaultValue": "setWidgetResourceValueDefaultValue",
					"MyWidgetResourceLib.FieldHandler.setSelectFieldFirstValue": "setSelectFieldFirstValue",
					
					"MyWidgetResourceLib.ResourceHandler.setWidgetResourcesLoadedAttribute": "setWidgetResourcesLoadedAttribute",
					"MyWidgetResourceLib.ResourceHandler.setDependentWidgetsResourcesLoadedAttribute": "setDependentWidgetsResourcesLoadedAttribute",
					"MyWidgetResourceLib.ResourceHandler.setParentWidgetResourcesLoadedAttribute": "setParentWidgetResourcesLoadedAttribute",
					
					"MyWidgetResourceLib.ShortActionHandler.redirectTo": "redirectTo",
					"MyWidgetResourceLib.ShortActionHandler.redirectToBasedInResources": "redirectToBasedInResources",
					"MyWidgetResourceLib.ShortActionHandler.redirectToBasedInData": "redirectToBasedInData",
					"MyWidgetResourceLib.ShortActionHandler.appendDataAndRedirectTo": "appendDataAndRedirectTo",
					
					"MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInInputNonEmptyValue": "refreshDependentWidgetsBasedInInputNonEmptyValue",
					"MyWidgetResourceLib.ShortActionHandler.refreshNotYetLoadedDependentWidgetsBasedInInputNonEmptyValue": "refreshNotYetLoadedDependentWidgetsBasedInInputNonEmptyValue",
					"MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInInputValue": "refreshDependentWidgetsBasedInInputValue",
					"MyWidgetResourceLib.ShortActionHandler.refreshNotYetLoadedDependentWidgetsBasedInInputValue": "refreshNotYetLoadedDependentWidgetsBasedInInputValue",
					"MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInInputValueButIfEmptyGetAll": "refreshDependentWidgetsBasedInInputValueButIfEmptyGetAll",
					"MyWidgetResourceLib.ShortActionHandler.refreshNotYetLoadedDependentWidgetsBasedInInputValueButIfEmptyGetAll": "refreshNotYetLoadedDependentWidgetsBasedInInputValueButIfEmptyGetAll",
					
					"MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInResourceNonEmptyValue": "refreshDependentWidgetsBasedInResourceNonEmptyValue",
					"MyWidgetResourceLib.ShortActionHandler.refreshNotYetLoadedDependentWidgetsBasedInResourceNonEmptyValue": "refreshNotYetLoadedDependentWidgetsBasedInResourceNonEmptyValue",
					"MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInResourceValue": "refreshDependentWidgetsBasedInResourceValue",
					"MyWidgetResourceLib.ShortActionHandler.refreshNotYetLoadedDependentWidgetsBasedInResourceValue": "refreshNotYetLoadedDependentWidgetsBasedInResourceValue",
					"MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInResourceValueButIfEmptyGetAll": "refreshDependentWidgetsBasedInResourceValueButIfEmptyGetAll",
					"MyWidgetResourceLib.ShortActionHandler.refreshNotYetLoadedDependentWidgetsBasedInResourceValueButIfEmptyGetAll": "refreshNotYetLoadedDependentWidgetsBasedInResourceValueButIfEmptyGetAll"
				}
			};
		
		if (widget.is("input, textarea"))
			return {
				"load": {
					"MyWidgetResourceLib.ResourceHandler.setWidgetResourcesLoadedAttribute": "setWidgetResourcesLoadedAttribute",
					"MyWidgetResourceLib.ResourceHandler.setDependentWidgetsResourcesLoadedAttribute": "setDependentWidgetsResourcesLoadedAttribute",
					"MyWidgetResourceLib.ResourceHandler.setParentWidgetResourcesLoadedAttribute": "setParentWidgetResourcesLoadedAttribute",
					
					"MyWidgetResourceLib.ShortActionHandler.redirectTo": "redirectTo",
					"MyWidgetResourceLib.ShortActionHandler.redirectToBasedInResources": "redirectToBasedInResources",
					"MyWidgetResourceLib.ShortActionHandler.redirectToBasedInData": "redirectToBasedInData",
					"MyWidgetResourceLib.ShortActionHandler.appendDataAndRedirectTo": "appendDataAndRedirectTo",
					
					"MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInInputNonEmptyValue": "refreshDependentWidgetsBasedInInputNonEmptyValue",
					"MyWidgetResourceLib.ShortActionHandler.refreshNotYetLoadedDependentWidgetsBasedInInputNonEmptyValue": "refreshNotYetLoadedDependentWidgetsBasedInInputNonEmptyValue",
					"MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInInputValue": "refreshDependentWidgetsBasedInInputValue",
					"MyWidgetResourceLib.ShortActionHandler.refreshNotYetLoadedDependentWidgetsBasedInInputValue": "refreshNotYetLoadedDependentWidgetsBasedInInputValue",
					"MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInInputValueButIfEmptyGetAll": "refreshDependentWidgetsBasedInInputValueButIfEmptyGetAll",
					"MyWidgetResourceLib.ShortActionHandler.refreshNotYetLoadedDependentWidgetsBasedInInputValueButIfEmptyGetAll": "refreshNotYetLoadedDependentWidgetsBasedInInputValueButIfEmptyGetAll",
					
					"MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInResourceNonEmptyValue": "refreshDependentWidgetsBasedInResourceNonEmptyValue",
					"MyWidgetResourceLib.ShortActionHandler.refreshNotYetLoadedDependentWidgetsBasedInResourceNonEmptyValue": "refreshNotYetLoadedDependentWidgetsBasedInResourceNonEmptyValue",
					"MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInResourceValue": "refreshDependentWidgetsBasedInResourceValue",
					"MyWidgetResourceLib.ShortActionHandler.refreshNotYetLoadedDependentWidgetsBasedInResourceValue": "refreshNotYetLoadedDependentWidgetsBasedInResourceValue",
					"MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInResourceValueButIfEmptyGetAll": "refreshDependentWidgetsBasedInResourceValueButIfEmptyGetAll",
					"MyWidgetResourceLib.ShortActionHandler.refreshNotYetLoadedDependentWidgetsBasedInResourceValueButIfEmptyGetAll": "refreshNotYetLoadedDependentWidgetsBasedInResourceValueButIfEmptyGetAll"
				}
			};
		
		return {
			"load": {
				"MyWidgetResourceLib.ResourceHandler.setWidgetResourcesLoadedAttribute": "setWidgetResourcesLoadedAttribute",
				"MyWidgetResourceLib.ResourceHandler.setDependentWidgetsResourcesLoadedAttribute": "setDependentWidgetsResourcesLoadedAttribute",
				"MyWidgetResourceLib.ResourceHandler.setParentWidgetResourcesLoadedAttribute": "setParentWidgetResourcesLoadedAttribute"
			}
		};
	}
	
	function getSelectedWidgetMyWidgetResourceLibJSEndCallbackFunctions(widget) {
		if (widget.is("select") && widget.closest("[data-widget-search-select]").length > 0)
			return {
				"load": {
					"MyWidgetResourceLib.SearchHandler.refreshSearchWidgetThroughSelect": "refreshSearchWidgetThroughSelect",
					"MyWidgetResourceLib.SearchHandler.resetSearchWidgetThroughSelect": "resetSearchWidgetThroughSelect",
				},
			};
		
		if (widget.is("input") && widget.closest("[data-widget-input-select]").length > 0)
			return {
				"load": {
					"MyWidgetResourceLib.SearchHandler.refreshSearchWidgetThroughInput": "refreshSearchWidgetThroughInput",
					"MyWidgetResourceLib.SearchHandler.resetSearchWidgetThroughInput": "resetSearchWidgetThroughInput",
				},
			};
		
		return null;
	}
	
	function getSelectedWidgetResourceTypes(widget) {
		var elm = widget[0];
		
		if (elm.hasAttribute("data-widget-list") || elm.hasAttribute("data-widget-form") || widget.is("form"))
			return {
				"load": "Resource(s) to load when the page loads.", 
				"add": "Resource(s) to set new records.", 
				"update": "Resource(s) to update an existent record.", 
				"update_attribute": "Resource(s) to update an attribute value. (if apply)", 
				"remove": "Resource(s) to remove a record."
			};
		else if (elm.hasAttribute("data-widget-pagination-go-to-page-dropdown"))
			return {
				"load": "Resource(s) to load when the page loads.", 
			};
		else if (elm.hasAttribute("data-widget-button-multiple-remove"))
			return {
				"load": "Resource(s) to load when the page loads.", 
				"remove": "Resource(s) to remove multiple records, by executing the remove action based on the selected items from the dependent widgets. The resource(s) should be ready to receive a list with element ids."
			};
		else if (elm.hasAttribute("data-widget-button-multiple-save"))
			return {
				"load": "Resource(s) to load when the page loads.", 
				"save": "Resource(s) to update and add multiple records, by executing the save action based on the selected items from the dependent widgets. The resource(s) should be ready to receive a list with element ids."
			};
		else if (elm.hasAttribute("data-widget-pagination") || elm.hasAttribute("data-widget-list-caption")) 
			return {
				"load": "Resource(s) to get the total records number. The resource(s) should return an integer bigger or equal to 0."
			};
		else if (elm.hasAttribute("data-widget-matrix") || elm.hasAttribute("data-widget-calendar"))
			return {
				"load": "Resource(s) to load when the page loads.", 
				"add": "Resource(s) to set new records.", 
				"update": "Resource(s) to update an existent record.", 
				"remove": "Resource(s) to remove a record."
			};
		else
			return {
				"load": "Resource(s) to load some data into this widget when it gets loaded."
			};
		
		return null;
	}
	
	function getSelectedWidgetMyWidgetResourceLibJSEventFunctions(widget) {
		var elm = widget[0];
		
		return {
			"Trigger": {
				"Popup": [
					{
						value: "MyWidgetResourceLib.PopupHandler.openButtonAddPopup(this); return false;",
						title: "openButtonAddPopup",
						description: 'Open a popup with an editable form with empty values.<br/>The html element that will execute this function, must have the attribute "data-widget-popup-id" with the correspondent popup id.'
					},
					{
						value: "MyWidgetResourceLib.ItemHandler.openItemEditPopupById(this); return false;",
						title: "openItemEditPopupById",
						description: "Get the item' values and open a popup with an editable form with that values.<br/>The html element that will execute this function, must have the attribute 'data-widget-popup-id' with the correspondent popup id.<br/>Additionally this html element must be inside of a parent element with the following selector: '[data-widget-item], [data-widget-form], form' too."
					},
					{
						value: "MyWidgetResourceLib.ItemHandler.openItemViewPopupById(this); return false;",
						title: "openItemViewPopupById",
						description: "Get the item' values and open a popup with a readonly form with that values.<br/>The html element that will execute this function, must have the attribute 'data-widget-popup-id' with the correspondent popup id.<br/>Additionally this html element must be inside of a parent element with the following selector: '[data-widget-item], [data-widget-form], form' too."
					},
					{
						value: "MyWidgetResourceLib.ItemHandler.openItemDependentWidgets(this); return false;",
						title: "openItemDependentWidgets",
						description: "Get the item' values and load dependent widgets.<br/>Additionally this html element must be inside of a parent element with the following selector: '[data-widget-item], [data-widget-form], form' too."
					},
					{
						value: "MyWidgetResourceLib.PopupHandler.openPopup(this); return false;",
						title: "openPopup",
						description: "Open a popup.<br/>The html element that will execute this function, must have the attribute 'data-widget-popup-id' with the correspondent popup id."
					},
					{
						value: "MyWidgetResourceLib.PopupHandler.closePopup(this); return false;",
						title: "closePopup",
						description: "Close a popup.<br/>The html element that will execute this function, must have the attribute 'data-widget-popup-id' with the correspondent popup id."
					},
					{
						value: "MyWidgetResourceLib.PopupHandler.closeParentPopup(this); return false;",
						title: "closeParentPopup",
						description: "Close the parent popup for the html element that will execute this function."
					},
					{
						value: "MyWidgetResourceLib.PopupHandler.executeSinglePopupResource(this, 'resource type/action name'); return false;",
						title: "executeSinglePopupResource",
						description: "Executes a resource based on the saved parameters of a popup.<br/>This method receives a third argument with an 'opts' object correspondent to some options that will be passed on execution of the correspondent resource."
					},
					{
						value: "MyWidgetResourceLib.FormHandler.resetElementForms(this); return false;",
						title: "resetElementForms",
						description: "Resets all fields from the forms inside of a popup."
					}
				],
				"Calendar Popup": [
					{
						value: "MyWidgetResourceLib.PopupHandler.openButtonAddPopup(this); return false;",
						title: "openButtonAddPopup",
						description: 'Open a popup with an editable form with empty values.<br/>The html element that will execute this function, must have the attribute "data-widget-popup-id" with the correspondent popup id.'
					},
					{
						value: "MyWidgetResourceLib.CalendarHandler.openCalendarEventAddPopupById(this, calendar_settings, arg); return false;",
						title: "openCalendarEventAddPopupById",
						description: "Open a popup with an editable form with empty values.<br/>The html element that will execute this function, must be the main calendar element, where the FullCalendar lib was initialized and must contain the correspondent popup id in the calendar events settings or [data-widget-popup-id] attribute."
					},
					{
						value: "MyWidgetResourceLib.CalendarHandler.openCalendarEventEditPopupById(this, calendar_settings, arg); return false;",
						title: "openCalendarEventEditPopupById",
						description: "Get the event' values and open a popup with an editable form with that values.<br/>The html element that will execute this function, must be the main calendar element, where the FullCalendar lib was initialized and must contain the correspondent popup id in the calendar event settings or [data-widget-popup-id] attribute."
					},
					{
						value: "MyWidgetResourceLib.CalendarHandler.openCalendarEventViewPopupById(this, calendar_settings, arg); return false;",
						title: "openCalendarEventViewPopupById",
						description: "Get the event' values and open a popup with a readonly form with that values.<br/>The html element that will execute this function, must be the main calendar element, where the FullCalendar lib was initialized and must contain the correspondent popup id in the calendar event settings or [data-widget-popup-id] attribute."
					},
					{
						value: "MyWidgetResourceLib.PopupHandler.openPopup(this); return false;",
						title: "openPopup",
						description: "Open a popup.<br/>The html element that will execute this function, must have the attribute 'data-widget-popup-id' with the correspondent popup id."
					},
					{
						value: "MyWidgetResourceLib.PopupHandler.closePopup(this); return false;",
						title: "closePopup",
						description: "Close a popup.<br/>The html element that will execute this function, must have the attribute 'data-widget-popup-id' with the correspondent popup id."
					},
					{
						value: "MyWidgetResourceLib.PopupHandler.closeParentPopup(this); return false;",
						title: "closeParentPopup",
						description: "Close the parent popup for the html element that will execute this function."
					},
					{
						value: "MyWidgetResourceLib.PopupHandler.executeSinglePopupResource(this, 'resource type/action name'); return false;",
						title: "executeSinglePopupResource",
						description: "Executes a resource based on the saved parameters of a popup.<br/>This method receives a third argument with an 'opts' object correspondent to some options that will be passed on execution of the correspondent resource."
					},
				],
				"Matrix Popup": [
					{
						value: "MyWidgetResourceLib.PopupHandler.openButtonAddPopup(this); return false;",
						title: "openButtonAddPopup",
						description: 'Open a popup with an editable form with empty values.<br/>The html element that will execute this function, must have the attribute "data-widget-popup-id" with the correspondent popup id.'
					},
					{
						value: "MyWidgetResourceLib.MatrixHandler.openMatrixEventAddPopupById(this); return false;",
						title: "openMatrixEventAddPopupById",
						//description: "Open a popup with an editable form with empty values.<br/>The html element that will execute this function, must be the main calendar element, where the FullCalendar lib was initialized and must contain the correspondent popup id in the calendar events settings or [data-widget-popup-id] attribute." //TODO
					},
					{
						value: "MyWidgetResourceLib.MatrixHandler.openMatrixEventEditPopupById(this); return false;",
						title: "openMatrixEventEditPopupById",
						//description: "Get the event' values and open a popup with an editable form with that values.<br/>The html element that will execute this function, must be the main calendar element, where the FullCalendar lib was initialized and must contain the correspondent popup id in the calendar event settings or [data-widget-popup-id] attribute." //TODO
					},
					{
						value: "MyWidgetResourceLib.MatrixHandler.openMatrixEventViewPopupById(this); return false;",
						title: "openMatrixEventViewPopupById",
						//description: "Get the event' values and open a popup with a readonly form with that values.<br/>The html element that will execute this function, must be the main calendar element, where the FullCalendar lib was initialized and must contain the correspondent popup id in the calendar event settings or [data-widget-popup-id] attribute." //TODO
					},
					{
						value: "MyWidgetResourceLib.PopupHandler.openPopup(this); return false;",
						title: "openPopup",
						description: "Open a popup.<br/>The html element that will execute this function, must have the attribute 'data-widget-popup-id' with the correspondent popup id."
					},
					{
						value: "MyWidgetResourceLib.PopupHandler.closePopup(this); return false;",
						title: "closePopup",
						description: "Close a popup.<br/>The html element that will execute this function, must have the attribute 'data-widget-popup-id' with the correspondent popup id."
					},
					{
						value: "MyWidgetResourceLib.PopupHandler.closeParentPopup(this); return false;",
						title: "closeParentPopup",
						description: "Close the parent popup for the html element that will execute this function."
					},
					{
						value: "MyWidgetResourceLib.PopupHandler.executeSinglePopupResource(this, 'resource type/action name'); return false;",
						title: "executeSinglePopupResource",
						description: "Executes a resource based on the saved parameters of a popup.<br/>This method receives a third argument with an 'opts' object correspondent to some options that will be passed on execution of the correspondent resource."
					},
				],
				"Pagination": {
					"Dropdown": [
						{
							value: "MyWidgetResourceLib.PaginationHandler.goToDropdownPage(this); return false;",
							title: "goToDropdownPage",
							description: "Get the selected page number and reload dependent widgets.<br/>This function should be called inside of a pagination widget with a dropdown which contains the following selector: '[data-widget-pagination-go-to-page-dropdown]'.<br/>If second argument is false, load the cached data from the dependent widgets."
						},
					],
					"Button": [
						{
							value: "MyWidgetResourceLib.PaginationHandler.goToFirstPage(this); return false;",
							title: "goToFirstPage",
							description: "Reload the first page in the dependent widgets.<br/>This function should be called inside of a pagination widget."
						},
						{
							value: "MyWidgetResourceLib.PaginationHandler.goToPreviousPage(this); return false;",
							title: "goToPreviousPage",
							description: "Reload the previous page in the dependent widgets.<br/>This function should be called inside of a pagination widget."
						},
						{
							value: "MyWidgetResourceLib.PaginationHandler.goToElementPage(this); return false;",
							title: "goToElementPage",
							description: "Reload the a specific page in the dependent widgets.<br/>This function should be called inside of a pagination widget and in a html element with the attribute 'data-widget-pagination-pages-numbers-item-value' with the correspondent page number value."
						},
						{
							value: "MyWidgetResourceLib.PaginationHandler.goToNextPage(this); return false;",
							title: "goToNextPage",
							description: "Reload the next page in the dependent widgets.<br/>This function should be called inside of a pagination widget."
						},
						{
							value: "MyWidgetResourceLib.PaginationHandler.goToLastPage(this); return false;",
							title: "goToLastPage",
							description: "Reload the last page in the dependent widgets.<br/>This function should be called inside of a pagination widget."
						},
					]
				},
				"Search": {
					"Input": [
						{
							value: "MyWidgetResourceLib.SearchHandler.refreshSearchWidgetThroughInput(this); return false;",
							title: "refreshSearchWidgetThroughInput",
							description: "Refresh the data from the dependent widgets based in a search input field with the selector: '[data-widget-search-input]'.<br/>This function should be called inside of a search widget."
						},
						{
							value: "MyWidgetResourceLib.SearchHandler.onKeyUpSearchWidgetThroughInput(this, 1); return false;",
							title: "onKeyUpSearchWidgetThroughInput",
							description: "Refresh the data on key up, from the dependent widgets based in a search input field with the selector: '[data-widget-search-input]'.<br/>This function should be called inside of a search widget."
						},
						{
							value: "MyWidgetResourceLib.SearchHandler.resetSearchWidgetThroughInput(this, true); return false;",
							title: "resetSearchWidgetThroughInput",
							description: "Clean search input field and reload the data from the dependent widgets.<br/>This function should be called inside of an input with the selector: '[data-widget-search-input]', which is inside of a search widget."
						},
					],
					"Select": [
						{
							value: "MyWidgetResourceLib.SearchHandler.refreshSearchWidgetThroughSelect(this); return false;",
							title: "refreshSearchWidgetThroughSelect",
							description: "Refresh the data from the dependent widgets based in a search select field with the selector: '[data-widget-search-select]'.<br/>This function should be called inside of a search widget."
						},
						{
							value: "MyWidgetResourceLib.SearchHandler.resetSearchWidgetThroughSelect(this, true); return false;",
							title: "resetSearchWidgetThroughSelect",
							description: "Clean search select field and reload the data from the dependent widgets.<br/>This function should be called inside of a select with the selector: '[data-widget-search-select]', which is inside of a search widget."
						},
						
						{
							value: "MyWidgetResourceLib.FieldHandler.setWidgetResourceValueDefaultValue(this); return false;",
							title: "setWidgetResourceValueDefaultValue",
							description: "Handler to be called on complete of a load action. In summary this handler checks if there is a default value set for the current field and, if it exists, sets that default value on the field. This method is used on comboboxes with default values from the URL that are not being set propertly..."
						},
						{
							value: "MyWidgetResourceLib.FieldHandler.setSelectFieldFirstValue(this); return false;",
							title: "setSelectFieldFirstValue",
							description: "Handler to be called on complete of a load action. In summary this handler selects the first option of a combobox. This method is used on comboboxes that don't have any option selected by default."
						},
						
						{
							value: "MyWidgetResourceLib.ResourceHandler.setDependentWidgetsResourcesLoadedAttribute(this); return false;",
							title: "setDependentWidgetsResourcesLoadedAttribute",
							description: "Set the attribute data-widget-resources-loaded to the dependent widgets."
						},
						{
							value: "MyWidgetResourceLib.ResourceHandler.setParentWidgetResourcesLoadedAttribute(this); return false;",
							title: "setParentWidgetResourcesLoadedAttribute",
							description: "Set the attribute data-widget-resources-loaded to the parent widget with a load resource."
						}
					],
					"Multiple search": [
						{
							value: "MyWidgetResourceLib.SearchHandler.addWidgetSearchDynamicAttribute(this, 1); return false;",
							title: "addWidgetSearchDynamicAttribute",
							description: "Add an attribute field so the user can search.<br/>This function should be called inside of a [data-widget-search-multiple] widget."
						},
						{
							value: "MyWidgetResourceLib.SearchHandler.refreshSearchWidget(this, true); return false;",
							title: "refreshSearchWidget",
							description: "Refresh the data from the dependent widgets based in a multiple search fields with the selector: '[data-widget-search-multiple-button]'.<br/>This function should be called inside of a search widget."
						},
						{
							value: "MyWidgetResourceLib.SearchHandler.resetSearchWidgetThroughMultipleField(this, true); return false;",
							title: "resetSearchWidgetThroughMultipleField",
							description: "Reset search created dynamically by the user.<br/>This function should be called inside of a [data-widget-search-multiple] widget."
						},
					],
					"Button": [
						{
							value: "MyWidgetResourceLib.SearchHandler.refreshSearchWidget(this, true); return false;",
							title: "refreshSearchWidget",
							description: "Refresh the data from the dependent widgets based in the defined search fields.<br/>This function should be called inside of a search widget."
						},
						{
							value: "MyWidgetResourceLib.SearchHandler.resetSearchWidget(this, true); return false;",
							title: "resetSearchWidget",
							description: "Reset search.<br/>This function should be called inside of a [data-widget-search] widget."
						},
					]
				},
				"Short action (Field/Buttons/Links/Elements...)": [
						/* REFRESH FUNCTIONS */
						{
							value: "MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgets(this); return false;",
							title: "refreshDependentWidgets",
							description: "Reload data from the dependent widgets."
						},
						{
							value: "MyWidgetResourceLib.ShortActionHandler.refreshNotYetLoadedDependentWidgets(this); return false;",
							title: "refreshNotYetLoadedDependentWidgets",
							description: "Reload data from the dependent widgets, that were not loaded yet."
						},
						
						/* REFRESH FUNCTIONS - INPUT VALUE */
						{
							value: "MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInInputNonEmptyValue(this); return false;",
							title: "refreshDependentWidgetsBasedInInputNonEmptyValue",
							description: "Reload data from the dependent widgets based on a non-empty input field value."
						},
						{
							value: "MyWidgetResourceLib.ShortActionHandler.refreshNotYetLoadedDependentWidgetsBasedInInputNonEmptyValue(this); return false;",
							title: "refreshNotYetLoadedDependentWidgetsBasedInInputNonEmptyValue",
							description: "Reload data from the dependent widgets, that were not loaded yet, based on a non-empty input field value."
						},
						{
							value: "MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInInputValue(this); return false;",
							title: "refreshDependentWidgetsBasedInInputValue",
							description: "Reload data from the dependent widgets based on an input field value."
						},
						{
							value: "MyWidgetResourceLib.ShortActionHandler.refreshNotYetLoadedDependentWidgetsBasedInInputValue(this); return false;",
							title: "refreshNotYetLoadedDependentWidgetsBasedInInputValue",
							description: "Reload data from the dependent widgets, that were not loaded yet, based on an input field value."
						},
						{
							value: "MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInInputValueButIfEmptyGetAll(this); return false;",
							title: "refreshDependentWidgetsBasedInInputValueButIfEmptyGetAll",
							description: "Reload data from the dependent widgets based on an input field value. If that value is empty, reload dependent widgets without any filter, this is, get all items from dependent widgets."
						},
						{
							value: "MyWidgetResourceLib.ShortActionHandler.refreshNotYetLoadedDependentWidgetsBasedInInputValueButIfEmptyGetAll(this); return false;",
							title: "refreshNotYetLoadedDependentWidgetsBasedInInputValueButIfEmptyGetAll",
							description: "Reload data from the dependent widgets, that were not loaded yet, based on an input field value. If that value is empty, reload dependent widgets without any filter, this is, get all items from dependent widgets."
						},
						
						/* REFRESH FUNCTIONS - RESOURCE VALUE */
						{
							value: "MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInResourceNonEmptyValue(this); return false;",
							title: "refreshDependentWidgetsBasedInResourceNonEmptyValue",
							description: "Reload data from the dependent widgets based on a non-empty resource-attribute name and value of a html element. The resource-attribute value is based in the available_values and if empty get the default value."
						},
						{
							value: "MyWidgetResourceLib.ShortActionHandler.refreshNotYetLoadedDependentWidgetsBasedInResourceNonEmptyValue(this); return false;",
							title: "refreshNotYetLoadedDependentWidgetsBasedInResourceNonEmptyValue",
							description: "Reload data from the dependent widgets, that were not loaded yet, based on a non-empty resource-attribute name and value of a html element. The resource-attribute value is based in the available_values and if empty get the default value."
						},
						{
							value: "MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInResourceValue(this); return false;",
							title: "refreshDependentWidgetsBasedInResourceValue",
							description: "Reload data from the dependent widgets based on a resource-attribute name and value of a html element. The resource-attribute value is based in the available_values and if empty get the default value."
						},
						{
							value: "MyWidgetResourceLib.ShortActionHandler.refreshNotYetLoadedDependentWidgetsBasedInResourceValue(this); return false;",
							title: "refreshNotYetLoadedDependentWidgetsBasedInResourceValue",
							description: "Reload data from the dependent widgets, that were not loaded yet, based on a resource-attribute name and value of a html element. The resource-attribute value is based in the available_values and if empty get the default value."
						},
						{
							value: "MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInResourceValueButIfEmptyGetAll(this); return false;",
							title: "refreshDependentWidgetsBasedInResourceValueButIfEmptyGetAll",
							description: "Reload data from the dependent widgets based on a resource-attribute name and value of a html element. If that value is empty, reload dependent widgets without any filter, this is, get all items from dependent widgets. The resource-attribute value is based in the available_values and if empty get the default value."
						},
						{
							value: "MyWidgetResourceLib.ShortActionHandler.refreshNotYetLoadedDependentWidgetsBasedInResourceValueButIfEmptyGetAll(this); return false;",
							title: "refreshNotYetLoadedDependentWidgetsBasedInResourceValueButIfEmptyGetAll",
							description: "Reload data from the dependent widgets, that were not loaded yet, based on a resource-attribute name and value of a html element. If that value is empty, reload dependent widgets without any filter, this is, get all items from dependent widgets. The resource-attribute value is based in the available_values and if empty get the default value."
						},
						
						/* REDIRECT FUNCTIONS */
						{
							value: "MyWidgetResourceLib.ShortActionHandler.redirectTo(this); return false;",
							title: "redirectTo",
							description: "Redirect browser to an url registered in the [data-widget-redirect-url] attribute."
						},
						{
							value: "MyWidgetResourceLib.ShortActionHandler.redirectToBasedInResources(this, resources_name, resources_cache_key, resource_index); return false;",
							title: "redirectToBasedInResources",
							description: "Redirect browser to an url registered in the [data-widget-redirect-url] attribute, but before replaces the url with the resource data if any is present."
						},
						{
							value: "MyWidgetResourceLib.ShortActionHandler.redirectToBasedInData(this, input_data, input_idx); return false;",
							title: "redirectToBasedInData",
							description: "Redirect browser to an url registered in the [data-widget-redirect-url] attribute, but before replaces the url with the returned data if any is present."
						},
						{
							value: "MyWidgetResourceLib.ShortActionHandler.appendDataAndRedirectTo(this, data); return false;",
							title: "appendDataAndRedirectTo",
							description: "Redirect browser to an url registered in the [data-widget-redirect-url] attribute, but before append returned data if any is present. This is very usefull to be called after add resources get executed, since most of them return a numeric value correspondent to the new pk."
						},
						
						/* OTHER FUNCTIONS */
						{
							value: "MyWidgetResourceLib.ShortActionHandler.purgeCachedLoadDependentWidgetsResource(this); return false;",
							title: "purgeCachedLoadDependentWidgetsResource",
							description: "Purge cache from the dependent widgets."
						},
						{
							value: "MyWidgetResourceLib.ShortActionHandler.addInlineResourceListItemToDependentWidgets(this); return false;",
							title: "addInlineResourceListItemToDependentWidgets",
							description: "Add an inline item inside of the dependent widgets."
						},
						{
							value: "MyWidgetResourceLib.ShortActionHandler.toggleWidgetListTableAndTree(this); return false;",
							title: "toggleWidgetListTableAndTree",
							description: "Toggle between the table and tree in a list widget."
						},
						{
							value: "MyWidgetResourceLib.ShortActionHandler.toggleWidgetListAttributeSelectCheckboxes(this); return false;",
							title: "toggleWidgetListAttributeSelectCheckboxes",
							description: "Toggle selection in the loaded items of the dependent widgets."
						},
						{
							value: "MyWidgetResourceLib.ShortActionHandler.resetWidgetListResourceSort(this); return false;",
							title: "resetWidgetListResourceSort",
							description: "Reset sorting from the dependent widgets."
						},
						{
							value: "MyWidgetResourceLib.ShortActionHandler.resetFormDependentWidgets(this); return false;",
							title: "resetFormDependentWidgets",
							description: "Resets all fields from the dependent form widgets."
						},
						{
							value: "MyWidgetResourceLib.ShortActionHandler.resetFormDependentWidgetsAndConvertThemIntoAddForms(this); return false;",
							title: "resetFormDependentWidgetsAndConvertThemIntoAddForms",
							description: "Resets all fields from the dependent form widgets and remove the attribute: data-widget-pks-attrs.<br/>In case we wish to convert the edit forms to add forms, we should use this function. This allows to have forms that allows add and update items simultaneously."
						},
						{
							value: "MyWidgetResourceLib.ShortActionHandler.executeResourceMultipleAddAction(this); return false;",
							title: "executeResourceMultipleAddAction",
							description: "Execute add action/resource, for the new records that are selected from the dependent widgets.<br/>This method receives a second argument with an 'opts' object correspondent to some options that will be passed on execution of the correspondent resource."
						},
						{
							value: "MyWidgetResourceLib.ShortActionHandler.executeResourceMultipleUpdateAction(this); return false;",
							title: "executeResourceMultipleUpdateAction",
							description: "Execute update action/resource, for only the existent records that are selected from the dependent widgets.<br/>This method receives a second argument with an 'opts' object correspondent to some options that will be passed on execution of the correspondent resource."
						},
						{
							value: "MyWidgetResourceLib.ShortActionHandler.executeResourceMultipleSaveAction(this); return false;",
							title: "executeResourceMultipleSaveAction",
							description: "Execute save (update and add) action/resource, for the existent and new records that are selected from the dependent widgets.<br/>This method receives a second argument with an 'opts' object correspondent to some options that will be passed on execution of the correspondent resource."
						},
						{
							value: "MyWidgetResourceLib.ShortActionHandler.executeResourceMultipleRemoveAction(this); return false;",
							title: "executeResourceMultipleRemoveAction",
							description: "Execute removal action for all the selected items from the dependent widgets.<br/>This method receives a second argument with an 'opts' object correspondent to some options that will be passed on execution of the correspondent resource."
						},
						{
							value: "MyWidgetResourceLib.ShortActionHandler.executeResourceMultipleAction(this, 'resource type/action name'); return false;",
							title: "executeResourceMultipleAction",
							description: "Execute a specific action/resource type, based in its name, for all the selected items from the dependent widgets.<br/>This method receives a third argument with an 'opts' object correspondent to some options that will be passed on execution of the correspondent resource."
						},
						{
							value: "MyWidgetResourceLib.ShortActionHandler.executeResourceSingleAction(this, 'resource type/action name'); return false;",
							title: "executeResourceSingleAction",
							description: "Execute a specific action/resource type, based in its name, from the dependent widgets.<br/>This method receives a third argument with an 'opts' object correspondent to some options that will be passed on execution of the correspondent resource."
						},
						
						{
							value: "MyWidgetResourceLib.FieldHandler.executeSingleElementFieldsResource(this, 'resource type/action name'); return false;",
							title: "executeSingleElementFieldsResource",
							description: "Execute a specific action/resource type, based in its name, from the same widget.<br/>This method receives a third argument with an 'opts' object correspondent to some options that will be passed on execution of the correspondent resource."
						},
						
						{
							value: "MyWidgetResourceLib.FieldHandler.setWidgetResourceValueDefaultValue(this, resources_name); return false;",
							title: "setWidgetResourceValueDefaultValue",
							description: "Handler to be called on complete of a load action. In summary this handler checks if there is a default value set for the current field and, if it exists, sets that default value on the field. This method is used on comboboxes with default values from the URL that are not being set propertly..."
						},
						{
							value: "MyWidgetResourceLib.FieldHandler.setSelectFieldFirstValue(this, resources_name); return false;",
							title: "setSelectFieldFirstValue",
							description: "Handler to be called on complete of a load action. In summary this handler selects the first option of a combobox. This method is used on comboboxes that don't have any option selected by default."
						},
						
						{
							value: "MyWidgetResourceLib.ResourceHandler.setWidgetResourcesLoadedAttribute(this); return false;",
							title: "setWidgetResourcesLoadedAttribute",
							description: "Set the attribute data-widget-resources-loaded to the current widget."
						},
						{
							value: "MyWidgetResourceLib.ResourceHandler.setDependentWidgetsResourcesLoadedAttribute(this); return false;",
							title: "setDependentWidgetsResourcesLoadedAttribute",
							description: "Set the attribute data-widget-resources-loaded to the dependent widgets."
						},
						{
							value: "MyWidgetResourceLib.ResourceHandler.setParentWidgetResourcesLoadedAttribute(this); return false;",
							title: "setParentWidgetResourcesLoadedAttribute",
							description: "Set the attribute data-widget-resources-loaded to the parent widget with a load resource."
						}
				],
				"List": {
					"Head button": [
						{
							value: "MyWidgetResourceLib.ListHandler.sortListResource(this, event); return false;",
							title: "sortListResource",
							description: "Sort a list column based a attribute name. Note that element that executes this function must have the 'data-widget-item-attribute-name' attribute defined with the correspondent attribute name to be sorted."
						},
						{
							value: "MyWidgetResourceLib.ListHandler.resetListResourceSortAttribute(this, event); return false;",
							title: "resetListResourceSortAttribute",
							description: "Reset previous sorting for a specific column/attribute."
						},
						{
							value: "MyWidgetResourceLib.ListHandler.addInlineResourceListItem(this); return false;",
							title: "addInlineResourceListItem",
							description: "Add an inline item inside of the current list."
						},
						{
							value: "MyWidgetResourceLib.ListHandler.toggleListAttributeSelectCheckboxes(this); return false;",
							title: "toggleListAttributeSelectCheckboxes",
							description: "Toggle selection in the loaded items of the current list."
						},
						{
							value: "MyWidgetResourceLib.ListHandler.reloadParentListResource(this); return false;",
							title: "reloadParentListResource",
							description: "Reload data of the current list."
						},
						{
							value: "MyWidgetResourceLib.ListHandler.executeSingleListResource(this, 'resource type/action name'); return false;",
							title: "executeSingleListResource",
							description: "Executes a resource based on the saved parameters of a list.<br/>This method receives a third argument with an 'opts' object correspondent to some options that will be passed on execution of the correspondent resource."
						},
					]
				},
				"Item (list/form)": {
					"Item icon": [
						{
							value: "MyWidgetResourceLib.ItemHandler.addResourceItem(this); return false;",
							title: "addResourceItem",
							description: "Get values from the new item and send request to server so a new record can be added.<br/>This function receives a second parameter, which is a string with the resource key to be called, in case the user wishes to defined a different one from the default resource key: 'add'.<br/>Note that this function should be called inside of a list/form widget with a 'add' resource defined."
						},
						{
							value: "MyWidgetResourceLib.ItemHandler.cancelAddResourceItem(this); return false;",
							title: "cancelAddResourceItem",
							description: "Cancel itention of adding new item."
						},
						{
							value: "MyWidgetResourceLib.ItemHandler.updateResourceItem(this); return false;",
							title: "updateResourceItem",
							description: "Get values of the current item and send request to server to update them.<br/>This function receives a second parameter, which is a string with the resource key to be called, in case the user wishes to defined a different one from the default resource key: 'update'.<br/>Note that this function should be called inside of a list/form widget with a 'update' resource defined."
						},
						{
							value: "MyWidgetResourceLib.ItemHandler.saveResourceItem(this); return false;",
							title: "saveResourceItem",
							description: "Get values of the current item and send request to server to save it. If no record, adds a new one. If the attribute [data-widget-pks-attrs] is present calls the updateResourceItem function, otherwise the addResourceItem.<br/>This function receives a second parameter, which is a string with the resource key to be called, in case the user wishes to defined a different one from the default resource key: 'update'.<br/>Note that this function should be called inside of a list/form widget with a 'update' resource defined."
						},
						{
							value: "MyWidgetResourceLib.ItemHandler.removeResourceItem(this); return false;",
							title: "removeResourceItem",
							description: "Send request to server to remove current item.<br/>This function receives a second parameter, which is a string with the resource key to be called, in case the user wishes to defined a different one from the default resource key: 'remove'.<br/>Note that this function should be called inside of a list/form widget with a 'remove' resource defined."
						},
						{
							value: "MyWidgetResourceLib.ItemHandler.toggleResourceAttributesEditing(this); return false;",
							title: "toggleResourceAttributesEditing",
							description: "Toggle between view and edit fields. This will only work if there are view and edit fields together in the same item."
						},
						{
							value: "MyWidgetResourceLib.ItemHandler.updateViewFieldsBasedInEditFields(this); return false;",
							title: "updateViewFieldsBasedInEditFields",
							description: "Based in the edit fields' values, update the view fields, this is, when toggle between edit to view fields, the system calls automatically this function to update the new changed values. If you wish to call it too in another cenario, you can do it too..."
						},
						{
							value: "MyWidgetResourceLib.ItemHandler.openItemDependentWidgets(this); return false;",
							title: "openItemDependentWidgets",
							description: "Get the item' values and load dependent widgets.<br/>Additionally this html element must be inside of a parent element with the following selector: '[data-widget-item], [data-widget-form], form' too."
						},
					],
					"Attribute combox and input" : [
						{
							value: "MyWidgetResourceLib.ItemHandler.toggleItemAttributeSelectFieldToInputField(this); return false;",
							title: "toggleItemAttributeSelectFieldToInputField",
							description: "If you wish to hide a shown combobox and show a hidden input.<br/>Note that this function should be called inside of a [data-widget-item-attribute-field-toggle-select-input] widget."
						},
						{
							value: "MyWidgetResourceLib.ItemHandler.toggleItemAttributeInputFieldToSelectField(this); return false;",
							title: "toggleItemAttributeInputFieldToSelectField",
							description: "If you wish to hide a shown input and show a hidden combobox.<br/>Note that this function should be called inside of a [data-widget-item-attribute-field-toggle-select-input] widget."
						},
					],
					"Attribute field (input/select/textarea)": [
						{
							value: "MyWidgetResourceLib.ItemHandler.updateResourceItemAttributeOnBlur(this); return false;",
							title: "updateResourceItemAttributeOnBlur",
							description: "Get value on blur of the current field (input/select/textarea) and send request to server to save it.<br/>This function receives a second parameter, which is a string with the resource key to be called, in case the user wishes to defined a different one from the default resource key: 'update_attribute'.<br/>Note that this function should be called inside of a list/form widget with a 'update_attribute' resource defined."
						},
						{
							value: "MyWidgetResourceLib.ItemHandler.updateResourceItemAttributeOnClick(this); return false;",
							title: "updateResourceItemAttributeOnClick",
							description: "Get value on click of the current field (checkbox or radio button) and send request to server to save it.<br/>This function receives a second parameter, which is a string with the resource key to be called, in case the user wishes to defined a different one from the default resource key: 'update_attribute'.<br/>Note that this function should be called inside of a list/form widget with a 'update_attribute' resource defined."
						},
						{
							value: "MyWidgetResourceLib.ItemHandler.updateResourceItemAttributeOnKeyUp(this); return false;",
							title: "updateResourceItemAttributeOnKeyUp",
							description: "Get value on key up of the current field (input/textarea) and send request to server to save it.<br/>This function receives a second parameter, which is a string with the resource key to be called, in case the user wishes to defined a different one from the default resource key: 'update_attribute'.<br/>Note that this function should be called inside of a list/form widget with a 'update_attribute' resource defined."
						},
						{
							value: "MyWidgetResourceLib.ItemHandler.updateResourceItemAttributeOnChange(this); return false;",
							title: "updateResourceItemAttributeOnChange",
							description: "Get value on change of the current select/combobox field and send request to server to save it.<br/>This function receives a second parameter, which is a string with the resource key to be called, in case the user wishes to defined a different one from the default resource key: 'update_attribute'.<br/>Note that this function should be called inside of a list/form widget with a 'update_attribute' resource defined."
						},
						{
							value: "MyWidgetResourceLib.ItemHandler.updateResourceItemAttribute(this); return false;",
							title: "updateResourceItemAttribute",
							description: "Get value on change of the current field and send request to server to save it.<br/>This function receives a second parameter, which is a string with the resource key to be called, in case the user wishes to defined a different one from the default resource key: 'update_attribute'.<br/>Note that this function should be called inside of a list/form widget with a 'update_attribute' resource defined."
						},
					]
				},
				"Form": {
					"button": [
						{
							value: "MyWidgetResourceLib.FormHandler.reloadParentFormResource(this); return false;",
							title: "reloadParentFormResource",
							description: "Reload data of the parent widget.<br/>Note that this function should be called inside a form widget."
						},
						{
							value: "MyWidgetResourceLib.ItemHandler.addResourceItem(this); return false;",
							title: "addResourceItem",
							description: "Get values from the new item and send request to server so a new record can be added.<br/>This function receives a second parameter, which is a string with the resource key to be called, in case the user wishes to defined a different one from the default resource key: 'add'.<br/>Note that this function should be called inside of a list/form widget with a 'add' resource defined."
						},
						{
							value: "MyWidgetResourceLib.ItemHandler.cancelAddResourceItem(this); return false;",
							title: "cancelAddResourceItem",
							description: "Cancel itention of adding new item."
						},
						{
							value: "MyWidgetResourceLib.ItemHandler.updateResourceItem(this); return false;",
							title: "updateResourceItem",
							description: "Get values of the current item and send request to server to save them.<br/>This function receives a second parameter, which is a string with the resource key to be called, in case the user wishes to defined a different one from the default resource key: 'update'.<br/>Note that this function should be called inside of a list/form widget with a 'update' resource defined."
						},
						{
							value: "MyWidgetResourceLib.ItemHandler.saveResourceItem(this); return false;",
							title: "saveResourceItem",
							description: "Get values of the current item and send request to server to save it. If no record, adds a new one. If the attribute [data-widget-pks-attrs] is present calls the updateResourceItem function, otherwise the addResourceItem.<br/>This function receives a second parameter, which is a string with the resource key to be called, in case the user wishes to defined a different one from the default resource key: 'update'.<br/>Note that this function should be called inside of a list/form widget with a 'update' resource defined."
						},
						{
							value: "MyWidgetResourceLib.ItemHandler.removeResourceItem(this); return false;",
							title: "removeResourceItem",
							description: "Send request to server to remove current item.<br/>This function receives a second parameter, which is a string with the resource key to be called, in case the user wishes to defined a different one from the default resource key: 'remove'.<br/>Note that this function should be called inside of a list/form widget with a 'remove' resource defined."
						},
						{
							value: "MyWidgetResourceLib.ItemHandler.toggleResourceAttributesEditing(this); return false;",
							title: "toggleResourceAttributesEditing",
							description: "Toggle between view and edit fields. This will only work if there are view and edit fields together in the same item."
						},
						{
							value: "MyWidgetResourceLib.ItemHandler.updateViewFieldsBasedInEditFields(this); return false;",
							title: "updateViewFieldsBasedInEditFields",
							description: "Based in the edit fields' values, update the view fields, this is, when toggle between edit to view fields, the system calls automatically this function to update the new changed values. If you wish to call it too in another cenario, you can do it too..."
						},
						{
							value: "MyWidgetResourceLib.FormHandler.executeSingleFormResource(this, 'resource type/action name'); return false;",
							title: "executeSingleFormResource",
							description: "Executes a resource based on the saved parameters of a form. This method receives a third argument with an 'opts' object correspondent to some options that will be passed on execution of the correspondent resource."
						},
						{
							value: "MyWidgetResourceLib.FormHandler.resetForm(this); return false;",
							title: "resetForm",
							description: "Resets all fields from a form."
						},
						{
							value: "MyWidgetResourceLib.FormHandler.resetFormAndConvertItIntoAddForm(this); return false;",
							title: "resetFormAndConvertItIntoAddForm",
							description: "Resets all fields from a form and remove the attribute: data-widget-pks-attrs.<br/>In case we wish to convert an edit form to an add form, we should use this function. This allows to have a form that allows add and update items simultaneously."
						}
					]
				}
			},
			"Load": {
				"Pagination": {
					"Pagination Group": [
						{
							value: "MyWidgetResourceLib.PaginationHandler.loadPaginationResource(this); return false;",
							title: "loadPaginationResource",
							description: "Load data of a pagination widget.<br/>This function should be called by a pagination widget."
						}
					],
					"Dropdown": [
						{
							value: "MyWidgetResourceLib.PaginationHandler.loadDropdownPages(dropdown, num_pages); return false;",
							title: "loadDropdownPages",
							description: "Load data of a pagination dropdown widget.<br/>This function should be called by a [data-widget-pagination-go-to-page-dropdown] widget."
						},
						{
							value: "MyWidgetResourceLib.PaginationHandler.setDropdownSelectedPageNumber(this, page_number); return false;",
							title: "setDropdownSelectedPageNumber",
							description: "Set page number in a pagination dropdown widget.<br/>This function should be called by a [data-widget-pagination-go-to-page-dropdown] widget."
						}
					],
					"Page Number Item": [
						{
							value: "MyWidgetResourceLib.PaginationHandler.loadItemPage(this, num_page); return false;",
							title: "loadItemPage",
							description: "Load data of a pagination item widget.<br/>This function should be called by a [data-widget-pagination-pages-numbers-item] widget."
						}
					]
				},
				"List": {
					"List Group": [
						{
							value: "MyWidgetResourceLib.ListHandler.loadListTableAndTreeResource(this); return false;",
							title: "loadListTableAndTreeResource",
							description: "Load data of a list widget.<br/>This function should be called by a list widget."
						}
					],
					"List Caption": [
						{
							value: "MyWidgetResourceLib.ListHandler.loadListCaptionResource(this); return false;",
							title: "loadListCaptionResource",
							description: "Load data of a list caption widget.<br/>This function should be called by a [data-widget-list-caption] widget."
						}
					]
				},
				"Popup": [
					{
						value: "MyWidgetResourceLib.PopupHandler.loadPopupResource(this); return false;",
						title: "loadPopupResource",
						description: "Load data of a popup widget.<br/>This function should be called by a popup widget."
					}
				],
				"Form": [
					{
						value: "MyWidgetResourceLib.FormHandler.loadFormResource(this); return false;",
						title: "loadFormResource",
						description: "Load data of a form widget.<br/>This function should be called by a form widget."
					}
				],
				"Field": [
					{
						value: "MyWidgetResourceLib.FieldHandler.loadElementFieldsResource(this); return false;",
						title: "loadElementFieldsResource",
						description: "Load data of an element widget and shows that data in the inner field elements.<br/>This function can be called by any html element with a 'load' resource defined. This method receives a second argument with an 'opts' object correspondent to some options that will be passed on execution of the correspondent resource."
					},
					{
						value: "MyWidgetResourceLib.FieldHandler.loadElementSelectFieldsResource(this); return false;",
						title: "loadElementSelectFieldsResource",
						description: "Load data of an element widget and shows that data in the inner comboboxes. This function can be called by any html element with a 'load' resource defined and comboboxes as children. The resource result can be an associative list with records coming from the DB, or a simple list with alpha-numeric values. This method receives a second argument with an 'opts' object correspondent to some options that will be passed on execution of the correspondent resource."
					},
					{
						value: "MyWidgetResourceLib.FieldHandler.loadFieldResource(this); return false;",
						title: "loadFieldResource",
						description: "Load data of a field widget.<br/>This function can be called by any html element with a 'load' resource defined."
					},
					{
						value: "MyWidgetResourceLib.FieldHandler.cacheFieldResource(this); return false;",
						title: "cacheFieldResource",
						description: "Load data of a field widget.<br/>This function can be called by any html element with a 'load' resource defined.<br/>The difference between cacheFieldResource and loadFieldResource methods, is that the cacheFieldResource method checks if the 'load' resource was previously loaded and, if yes, use it instead of loading it again. Additionally only executes a resource without loading its values or executing the dependent_widget_ids. This is used to load the available_values."
					},
					{
						value: "MyWidgetResourceLib.FieldHandler.loadSelectFieldResource(this); return false;",
						title: "loadSelectFieldResource",
						description: "Load data into a combobox.<br/>This function can be called by any combobox with a 'load' resource defined.<br/>In summary this handler calls the loadFieldResource method, loading a resource into the correspondent combobox, and then, on complete, selects the default value if exists, otherwise the first option of a combobox. Then add the attribute data-widget-resources-loaded to the dependent widgets and refreshes them, if exist."
					}
				],
				"Graph": [
					{
						value: "MyWidgetResourceLib.ChartHandler.loadChartResource(this); return false;",
						title: "loadChartResource",
						description: "Draws a graph inside of the current widget based in the result of a resource, defined in the data-sets of the widget properties."
					}
				],
				"Calendar": [
					{
						value: "MyWidgetResourceLibCalendarHandler.loadCalendarResource(this); return false;",
						title: "loadCalendarResource",
						description: "Draws a calendar inside of the current widget based in the result of a resource, defined in the data-sets of the widget properties."
					}
				],
				"Matrix": [
					{
						value: "MyWidgetResourceLib.MatrixHandler.loadMatrixResource(this); return false;",
						title: "loadMatrixResource",
						description: "Load and draw main Matrix element based in dynamic resources defined inside of the current widget."
					},
					{
						value: "MyWidgetResourceLib.MatrixHandler.loadMatrixHeadRowResource(this); return false;",
						title: "loadMatrixHeadRowResource",
						description: "Load and draw axis-x head rows based in dynamic resources."
					},
					{
						value: "MyWidgetResourceLib.MatrixHandler.loadMatrixHeadColumnResource(this); return false;",
						title: "loadMatrixHeadColumnResource",
						description: "Load and draw axis-x head columns based in dynamic resources."
					},
					{
						value: "MyWidgetResourceLib.MatrixHandler.loadMatrixBodyRowResource(this); return false;",
						title: "loadMatrixBodyRowResource",
						description: "Load and draw axis-y rows with head columns based in dynamic resources."
					}
				],
				"Html Node": [
					{
						value: "MyWidgetResourceLib.FieldHandler.loadElementFieldsResource(this); return false;",
						title: "loadElementFieldsResource",
						description: "Load data of an element widget and shows that data in the inner field elements.<br/>This function can be called by any html element with a 'load' resource defined. This method receives a second argument with an 'opts' object correspondent to some options that will be passed on execution of the correspondent resource."
					},
					{
						value: "MyWidgetResourceLib.FieldHandler.loadElementSelectFieldsResource(this); return false;",
						title: "loadElementSelectFieldsResource",
						description: "Load data of an element widget and shows that data in the inner comboboxes. This function can be called by any html element with a 'load' resource defined and comboboxes as children. The resource result can be an associative list with records coming from the DB, or a simple list with alpha-numeric values. This method receives a second argument with an 'opts' object correspondent to some options that will be passed on execution of the correspondent resource."
					},
					{
						value: "MyWidgetResourceLib.FieldHandler.loadFieldResource(this); return false;",
						title: "loadFieldResource",
						description: "Load data of a field widget.<br/>This function can be called by any html element with a 'load' resource defined."
					},
					{
						value: "MyWidgetResourceLib.FieldHandler.cacheFieldResource(this); return false;",
						title: "cacheFieldResource",
						description: "Load data of a field widget.<br/>This function can be called by any html element with a 'load' resource defined.<br/>The difference between cacheFieldResource and loadFieldResource methods, is that the cacheFieldResource method checks if the 'load' resource was previously loaded and, if yes, use it instead of loading it again. Additionally only executes a resource without loading its values or executing the dependent_widget_ids. This is used to load the available_values."
					}
				]
			},
			"Handler/CallBack": {
				"Popup": {
					"show": [
						{
							value: "MyWidgetResourceLib.FormHandler.resetElementForms(this); return false;",
							title: "resetElementForms",
							description: "Resets all fields from the forms inside of a popup."
						}
					],
					"hide": [
						{
							value: "MyWidgetResourceLib.FormHandler.resetElementForms(this); return false;",
							title: "resetElementForms",
							description: "Resets all fields from the forms inside of a popup."
						}
					]
				},
				"List": {
					"Load": [
						{
							value: "MyWidgetResourceLib.ListHandler.onLoadListResourceWithSelectSearchFields(this, resources_name, list_items_html); return false;",
							title: "onLoadListResourceWithSelectSearchFields",
							description: "Callback to be called on complete of a load action. In summary this handler checks if there are any search fields that were updated with new values and reload their dependencies, this is, finds inside of a form/popup, the search elements and load their dependencies. This method is used when we have comboboxes that depend on another fields, like another combobox."
						},
						{
							value: "MyWidgetResourceLib.FieldHandler.onLoadElementFieldsResourceCleanAllHashTagsLeft(this); return false;",
							title: "onLoadElementFieldsResourceCleanAllHashTagsLeft",
							description: "Callback to be called after a load or display handler get executed, to clean all the hashtags left from the html inside of the node element passed as argument."
						}
					],
					"Add": [
						{
							value: "MyWidgetResourceLib.ListHandler.reloadParentListResource(this); return false;",
							title: "reloadParentListResource",
							description: "Reload data of the current list."
						},
						{
							value: "MyWidgetResourceLib.ListHandler.onAddResourceItem(this); return false;",
							title: "onAddResourceItem",
							description: "Handler to be called on success of an add action. In summary, this handler reloads the data from the parent widget."
						}
					],
					"Update": [
						{
							value: "MyWidgetResourceLib.ListHandler.reloadParentListResource(this); return false;",
							title: "reloadParentListResource",
							description: "Reload data of the current list."
						},
						{
							value: "MyWidgetResourceLib.ItemHandler.updateViewFieldsBasedInEditFields(this); return false;",
							title: "updateViewFieldsBasedInEditFields",
							description: "Based in the edit fields' values, update the view fields, this is, when toggle between edit to view fields, the system calls automatically this function to update the new changed values. If you wish to call it too in another cenario, you can do it too..."
						},
						{
							value: "MyWidgetResourceLib.ListHandler.onUpdateResourceItem(this); return false;",
							title: "onUpdateResourceItem",
							description: "Handler to be called on success of an update action. In summary, this handler calls the updateViewFieldsBasedInEditFields method."
						}
					],
					"Update Attribute": [
						{
							value: "MyWidgetResourceLib.ListHandler.reloadParentListResource(this); return false;",
							title: "reloadParentListResource",
							description: "Reload data of the current list."
						},
						{
							value: "MyWidgetResourceLib.ItemHandler.updateViewFieldsBasedInEditFields(this); return false;",
							title: "updateViewFieldsBasedInEditFields",
							description: "Based in the edit fields' values, update the view fields, this is, when toggle between edit to view fields, the system calls automatically this function to update the new changed values. If you wish to call it too in another cenario, you can do it too..."
						},
						{
							value: "MyWidgetResourceLib.ListHandler.onUpdateResourceItemAttribute(this); return false;",
							title: "onUpdateResourceItemAttribute",
							description: "Handler to be called on success of an update attribute action. In summary, this handler calls the updateViewFieldsBasedInEditFields method."
						}
					],
					"Remove": [
						{
							value: "MyWidgetResourceLib.ListHandler.reloadParentListResource(this); return false;",
							title: "reloadParentListResource",
							description: "Reload data of the current list."
						},
						{
							value: "MyWidgetResourceLib.ListHandler.onRemoveResourceItem(this); return false;",
							title: "onRemoveResourceItem",
							description: "Handler to be called on success of a removal action. In summary, this handler deletes the removed item from the parent widget."
						}
					],
					"Display": [
						{
							value: "MyWidgetResourceLib.ListHandler.drawListData(this, data); return false;",
							title: "drawListData",
							description: "Handler to be called as a display handler of the 'Display Resource Attribute' settings. This function receives 2 arguments: a html node element and an object with data values to be displayed inside of that node element.<br/>Basically this function will loop the data objects and for each object, will display its values inside of the child node with the [data-widget-item] attribute.<br/>This behaviour is very similar with the method 'MyWidgetResourceLib.ListHandler.loadListTableAndTreeResource' but instead of displaying a resource data, it displays a data that is passed as an argument of this function. In this case, this data could be an object inside of an attribute of a record from a resource, where the loadListTableAndTreeResource displays the records and for a specific attribute, the drawListData method displays the sub-object inside of the correspondent element.<br/>This method should be used when we wish to display objects with other inner objects inside, like tree objects."
						},
						{
							value: "MyWidgetResourceLib.ListHandler.drawListDataRecursively(this, data); return false;",
							title: "drawListDataRecursively",
							description: "Handler to be called as a display handler of the 'Display Resource Attribute' settings. This function receives 2 arguments: a html node element and an object with data values to be displayed inside of that node element.<br/>Basically this function will loop the data objects and for each object, will display its values inside of the node element it-self, based in the parent settings.<br/>Basically it will get parent node with the attribute [data-widget-item] and for each data record will create nodes based in that [data-widget-item] parent node and then append it to the main node (passed as argument in this function).<br/>This behaviour is very similar with the method 'MyWidgetResourceLib.ListHandler.loadListTableAndTreeResource' but instead of displaying a resource data, it displays a data that is passed as an argument of this function. In this case, this data could be an object inside of an attribute of a record from a resource, where the loadListTableAndTreeResource displays the records and for a specific attribute, the drawListDataRecursively method displays the sub-object inside of the correspondent element, based in the parent node with the attribute [data-widget-item].<br/>This method should be used when we wish to display objects with others inner objects inside, like recursive tree objects, but in a recursively way, where each record (independent if is inside of another record) is always displayed with the same html.<br/>This method should be used to show recursive data. However, although not mandatory, works better if called inside of a [data-widget-list] node."
						},
						{
							value: "MyWidgetResourceLib.ListHandler.loadAndDrawListDataRecursively(this, data); return false;",
							title: "loadAndDrawListDataRecursively",
							description: "Handler to be called as a display handler of the 'Display Resource Attribute' settings.<br/>This function receives 1 argument: the current html node element.<br/>This function will get the parent node with the attribute [data-widget-list], find what is the child node with attribute [data-widget-item], save this correspondent HTML into the current node, then load the resource registered in the properties of the current node, replicating the saved HTML for each record of the loaded resource, and appending that HTML to the current node.<br/>Basically this function finds out what is the HTML that should be replicated for each record based in its' parents and then call the method 'MyWidgetResourceLib.ResourceHandler.loadWidgetResource' to load the registered resource based in that HTML.<br/>This method should be used when we wish to display records that have another children records of the same database table, but in a recursively way, where each record is always displayed with the same HTML.<br/>This method should be used to show recursive data from a database table."
						},
						{
							value: "MyWidgetResourceLib.FieldHandler.onLoadElementFieldsResourceCleanAllHashTagsLeft(this); return false;",
							title: "onLoadElementFieldsResourceCleanAllHashTagsLeft",
							description: "Callback to be called after a load or display handler get executed, to clean all the hashtags left from the html inside of the node element passed as argument."
						}
					]
				},
				"Form": {
					"Load": [
						{
							value: "MyWidgetResourceLib.FieldHandler.onLoadElementFieldsResourceWithSelectSearchFields(this, resources_name); return false;",
							title: "onLoadElementFieldsResourceWithSelectSearchFields",
							description: "Handler to be called on complete of a load action. In summary this handler checks if there are any search fields that were updated with new values and reload their dependencies, this is, finds inside of a form/popup, the search elements and load their dependencies. This method is used when we have comboboxes that depend on another fields, like another combobox."
						},
						{
							value: "MyWidgetResourceLib.FieldHandler.onLoadElementFieldsResourceCleanAllHashTagsLeft(this); return false;",
							title: "onLoadElementFieldsResourceCleanAllHashTagsLeft",
							description: "Callback to be called after a load or display handler get executed, to clean all the hashtags left from the html inside of the node element passed as argument."
						}
					],
					"Add": [
						{
							value: "MyWidgetResourceLib.PopupHandler.closeParentPopup(this); return false;",
							title: "closeParentPopup",
							description: "Close the parent popup for the html element that will execute this function."
						},
						{
							value: "MyWidgetResourceLib.FormHandler.reloadParentFormResource(this); return false;",
							title: "reloadParentFormResource",
							description: "Reload data of the current form."
						},
						{
							value: "MyWidgetResourceLib.FormHandler.onAddPopupResourceItem(this); return false;",
							title: "onAddPopupResourceItem",
							description: "Handler to be called on success of an add action. In summary this handler closest the parent popup and resets the form for the next addition."
						},
						{
							value: "MyWidgetResourceLib.FormHandler.onAddResourceItem(this); return false;",
							title: "onAddResourceItem",
							description: "Handler to be called on success of an add action. In summary this handler reloads the data from the parent widget."
						},
						{
							value: "MyWidgetResourceLib.FormHandler.onAddResourceItemAndConvertItIntoEditForm(this, returned_data); return false;",
							title: "onAddResourceItemAndConvertItIntoEditForm",
							description: "Handler to be called on success of an add action. In summary this handler sets data-widget-pks-attrs with the returned pks values in the returned_data variable.<br/>Note that the returned_data variable can be a simple value (such as a numeric value) or a string or JSON object, such as an associative array with pks attributes.<br/>In case we wish to convert an add form to an edit form, we should use this function. This allows you to have a form that allows you to add and update items simultaneously, where after adding an item, it allows you to edit it later."
						},
						{
							value: "MyWidgetResourceLib.FormHandler.resetForm(this); return false;",
							title: "resetForm",
							description: "Handler to be called on success of an add action. In summary this handler resets the data from the form."
						}
					],
					"Update": [
						{
							value: "MyWidgetResourceLib.PopupHandler.closeParentPopup(this); return false;",
							title: "closeParentPopup",
							description: "Close the parent popup for the html element that will execute this function."
						},
						{
							value: "MyWidgetResourceLib.FormHandler.reloadParentFormResource(this); return false;",
							title: "reloadParentFormResource",
							description: "Reload data of the current form."
						},
						{
							value: "MyWidgetResourceLib.ItemHandler.updateViewFieldsBasedInEditFields(this); return false;",
							title: "updateViewFieldsBasedInEditFields",
							description: "Based in the edit fields' values, update the view fields, this is, when toggle between edit to view fields, the system calls automatically this function to update the new changed values. If you wish to call it too in another cenario, you can do it too..."
						},
						{
							value: "MyWidgetResourceLib.FormHandler.onUpdatePopupResourceItem(this); return false;",
							title: "onUpdatePopupResourceItem",
							description: "Handler to be called on success of an update action. In summary this handler closes the parent popup for the html element that will execute this function."
						},
						{
							value: "MyWidgetResourceLib.FormHandler.onUpdateResourceItem(this); return false;",
							title: "onUpdateResourceItem",
							description: "Handler to be called on success of an update action. In summary, this handler calls the updateViewFieldsBasedInEditFields method."
						},
						{
							value: "MyWidgetResourceLib.FormHandler.resetForm(this); return false;",
							title: "resetForm",
							description: "Handler to be called on success of an update action. In summary this handler resets the data from the form."
						}
					],
					"Update Attribute": [
						{
							value: "MyWidgetResourceLib.PopupHandler.closeParentPopup(this); return false;",
							title: "closeParentPopup",
							description: "Close the parent popup for the html element that will execute this function."
						},
						{
							value: "MyWidgetResourceLib.FormHandler.reloadParentFormResource(this); return false;",
							title: "reloadParentFormResource",
							description: "Reload data of the current form."
						},
						{
							value: "MyWidgetResourceLib.ItemHandler.updateViewFieldsBasedInEditFields(this); return false;",
							title: "updateViewFieldsBasedInEditFields",
							description: "Based in the edit fields' values, update the view fields, this is, when toggle between edit to view fields, the system calls automatically this function to update the new changed values. If you wish to call it too in another cenario, you can do it too..."
						},
						{
							value: "MyWidgetResourceLib.FormHandler.onUpdatePopupResourceItemAttribute(this); return false;",
							title: "onUpdatePopupResourceItemAttribute",
							description: "Handler to be called on success of an update attribute action. In summary, this handler calls the updateViewFieldsBasedInEditFields method."
						},
						{
							value: "MyWidgetResourceLib.FormHandler.onUpdateResourceItemAttribute(this); return false;",
							title: "onUpdateResourceItemAttribute",
							description: "Handler to be called on success of an update attribute action. In summary, this handler calls the updateViewFieldsBasedInEditFields method."
						}
					],
					"Remove": [
						{
							value: "MyWidgetResourceLib.PopupHandler.closeParentPopup(this); return false;",
							title: "closeParentPopup",
							description: "Close the parent popup for the html element that will execute this function."
						},
						{
							value: "MyWidgetResourceLib.FormHandler.reloadParentFormResource(this); return false;",
							title: "reloadParentFormResource",
							description: "Reload data of the current form."
						},
						{
							value: "MyWidgetResourceLib.FormHandler.onRemovePopupResourceItem(this); return false;",
							title: "onRemovePopupResourceItem",
							description: "Handler to be called on success of a removal action. In summary this handler closest the parent popup."
						},
						{
							value: "MyWidgetResourceLib.FormHandler.onRemoveResourceItem(this); return false;",
							title: "onRemoveResourceItem",
							description: "Handler to be called on success of a removal action. In summary, this handler deletes the removed item from the parent widget."
						},
						{
							value: "MyWidgetResourceLib.FormHandler.resetForm(this); return false;",
							title: "resetForm",
							description: "Handler to be called on success of an add action. In summary this handler resets the data from the form."
						}
					],
					"Display": [
						{
							value: "MyWidgetResourceLib.ListHandler.drawListData(this, data); return false;",
							title: "drawListData",
							description: "Handler to be called as a display handler of the 'Display Resource Attribute' settings. This function receives 2 arguments: a html node element and an object with data values to be displayed inside of that node element.<br/>Basically this function will loop the data objects and for each object, will display its values inside of the child node with the [data-widget-item] attribute.<br/>This behaviour is very similar with the method 'MyWidgetResourceLib.ListHandler.loadListTableAndTreeResource' but instead of displaying a resource data, it displays a data that is passed as an argument of this function. In this case, this data could be an object inside of an attribute of a record from a resource, where the loadListTableAndTreeResource displays the records and for a specific attribute, the drawListData method displays the sub-object inside of the correspondent element.<br/>This method should be used when we wish to display objects with other inner objects inside, like tree objects."
						},
						{
							value: "MyWidgetResourceLib.ListHandler.drawListDataRecursively(this, data); return false;",
							title: "drawListDataRecursively",
							description: "Handler to be called as a display handler of the 'Display Resource Attribute' settings. This function receives 2 arguments: a html node element and an object with data values to be displayed inside of that node element.<br/>Basically this function will loop the data objects and for each object, will display its values inside of the node element it-self, based in the parent settings.<br/>Basically it will get parent node with the attribute [data-widget-item] and for each data record will create nodes based in that [data-widget-item] parent node and then append it to the main node (passed as argument in this function).<br/>This behaviour is very similar with the method 'MyWidgetResourceLib.ListHandler.loadListTableAndTreeResource' but instead of displaying a resource data, it displays a data that is passed as an argument of this function. In this case, this data could be an object inside of an attribute of a record from a resource, where the loadListTableAndTreeResource displays the records and for a specific attribute, the drawListDataRecursively method displays the sub-object inside of the correspondent element, based in the parent node with the attribute [data-widget-item].<br/>This method should be used when we wish to display objects with others inner objects inside, like recursive tree objects, but in a recursively way, where each record (independent if is inside of another record) is always displayed with the same html.<br/>This method should be used to show recursive data. However, although not mandatory, works better if called inside of a [data-widget-list] node."
						},
						{
							value: "MyWidgetResourceLib.ListHandler.loadAndDrawListDataRecursively(this, data); return false;",
							title: "loadAndDrawListDataRecursively",
							description: "Handler to be called as a display handler of the 'Display Resource Attribute' settings.<br/>This function receives 1 argument: the current html node element.<br/>This function will get the parent node with the attribute [data-widget-list], find what is the child node with attribute [data-widget-item], save this correspondent HTML into the current node, then load the resource registered in the properties of the current node, replicating the saved HTML for each record of the loaded resource, and appending that HTML to the current node.<br/>Basically this function finds out what is the HTML that should be replicated for each record based in its' parents and then call the method 'MyWidgetResourceLib.ResourceHandler.loadWidgetResource' to load the registered resource based in that HTML.<br/>This method should be used when we wish to display records that have another children records of the same database table, but in a recursively way, where each record is always displayed with the same HTML.<br/>This method should be used to show recursive data from a database table."
						},
						{
							value: "MyWidgetResourceLib.FieldHandler.onLoadElementFieldsResourceCleanAllHashTagsLeft(this); return false;",
							title: "onLoadElementFieldsResourceCleanAllHashTagsLeft",
							description: "Callback to be called after a load or display handler get executed, to clean all the hashtags left from the html inside of the node element passed as argument."
						}
					]
				},
				"Select Field": {
					"Load": [
						{
							value: "MyWidgetResourceLib.FieldHandler.setWidgetResourceValueDefaultValue(this, resources_name); return false;",
							title: "setWidgetResourceValueDefaultValue",
							description: "Handler to be called on complete of a load action. In summary this handler checks if there is a default value set for the current field and, if it exists, sets that default value on the field. This method is used on comboboxes with default values from the URL that are not being set propertly..."
						},
						{
							value: "MyWidgetResourceLib.FieldHandler.setSelectFieldFirstValue(this, resources_name); return false;",
							title: "setSelectFieldFirstValue",
							description: "Handler to be called on complete of a load action. In summary this handler selects the first option of a combobox. This method is used on comboboxes that don't have any option selected by default."
						},
						{
							value: "MyWidgetResourceLib.FieldHandler.onLoadElementFieldsResourceCleanAllHashTagsLeft(this); return false;",
							title: "onLoadElementFieldsResourceCleanAllHashTagsLeft",
							description: "Callback to be called after a load or display handler get executed, to clean all the hashtags left from the html inside of the node element passed as argument."
						}
					],
				},
				"Graph": {
					"Load": [
						{
							value: "MyWidgetResourceLib.ChartHandler.loadChartResource(this); return false;",
							title: "loadChartResource",
							description: "Handler to be called on complete of a load action/resource or as the load handler, when we wish to show a graph. In summary this handler draws a graph inside of the current widget based in the result of a resource, defined in the data-sets of the widget properties."
						}
					],
				},
				"Calendar": {
					"Load": [
						{
							value: "MyWidgetResourceLib.CalendarHandler.loadCalendarResource(this); return false;",
							title: "loadCalendarResource",
							description: "Handler to be called on complete of a load action/resource or as the load handler, when we wish to show a Calendar. In summary this handler draws a Calendar inside of the current widget based in the result of a resource, defined in the data-sets of the widget properties."
						}
					],
					"Add/Select": [
						{
							value: "MyWidgetResourceLib.CalendarHandler.addResourceCalendarEvent(this, calendar_settings, arg, opts); return false;",
							title: "addResourceCalendarEvent",
							description: "Handler to be called on 'add' callback setting of your calendar. In summary this handler will be called when the user clicks in a calendar cell amd prompts a box so he can write the new event title. Then the system sends a request to server with the event dates, so a new record can be added.<br/>Note that this function should be only used with the Calendar widgets which also have the 'add' resource defined."
						},
						{
							value: "MyWidgetResourceLib.CalendarHandler.addStaticCalendarEvent(this, calendar_settings, arg); return false;",
							title: "addStaticCalendarEvent",
							description: "Handler to be called on add callback setting of your calendar. In summary this handler will be called when the user clicks in a calendar cell amd prompts a box so he can write the new event title. Then the system adds a new static event in the calendar, without connecting with the server. This is done only in the client side.<br/>Note that this function should be only used with the Calendar widgets which also have the 'add' resource defined."
						},
						{
							value: "MyWidgetResourceLib.CalendarHandler.refreshCalendarEvents(this, calendar_settings, arg, opts); return false;",
							title: "refreshCalendarEvents",
							description: "Handler to be called on complete of an action, when we wish to refetch the Calendar events again.<br/>Note that this function should be only used with the Calendar."
						}
					],
					"Update/Resize/Move": [
						{
							value: "MyWidgetResourceLib.CalendarHandler.updateResourceCalendarEvent(this, calendar_settings, arg, opts); return false;",
							title: "updateResourceCalendarEvent",
							description: "Handler to be called on 'resize' or 'move' callbacks settings of your calendar. In summary this handler will be called when the user resizes or moves an event. Then the system gets values of the current event and sends a request to server with the event dates, so the correspondent record be updated.<br/>Note that this function should be only used with the Calendar widgets which also have the 'update' resource defined."
						},
						{
							value: "MyWidgetResourceLib.CalendarHandler.refreshCalendarEvents(this, calendar_settings, arg, opts); return false;",
							title: "refreshCalendarEvents",
							description: "Handler to be called on complete of an action, when we wish to refetch the Calendar events again.<br/>Note that this function should be only used with the Calendar"
						}
					],
					"Remove/Click": [
						{
							value: "MyWidgetResourceLib.CalendarHandler.removeResourceCalendarEvent(this, calendar_settings, arg, opts); return false;",
							title: "removeResourceCalendarEvent",
							description: "Handler to be called on 'remove' callback setting of your calendar. In summary this handler will be called when the user removes an event. Then the system gets values of the current event and sends a request to server with the event dates, so the correspondent record be removed.<br/>Note that this function should be only used with the Calendar widgets which also have the 'remove' resource defined."
						},
						{
							value: "MyWidgetResourceLib.CalendarHandler.removeStaticCalendarEvent(this, calendar_settings, arg); return false;",
							title: "removeStaticCalendarEvent",
							description: "Handler to be called on 'remove' callback setting of your calendar. In summary this handler will be called to remove an event. However the system removes the static event in the calendar, without connecting with the server. This is done only in the client side.<br/>Note that this function should be only used with the Calendar widgets which also have the 'remove' resource defined."
						},
						{
							value: "MyWidgetResourceLib.CalendarHandler.refreshCalendarEvents(this, calendar_settings, arg, opts); return false;",
							title: "refreshCalendarEvents",
							description: "Handler to be called on complete of an action, when we wish to refetch the Calendar events again.<br/>Note that this function should be only used with the Calendar"
						}
					],
				},
				"Matrix": {
					"Load": [
						{
							value: "MyWidgetResourceLib.MatrixHandler.loadMatrixResource(this); return false;",
							title: "loadMatrixResource",
							description: "Handler to be called on complete of a load action/resource or as the load handler, when we wish to show a Matrix. In summary this handler draws a Matrix inside of the current widget based in the result of resources, defined in the data-sets of the widget properties."
						},
						{
							value: "MyWidgetResourceLib.MatrixHandler.loadMatrixHeadRowResource(this); return false;",
							title: "loadMatrixHeadRowResource",
							description: "Handler to be called on complete of a load action/resource or as the load handler, when we wish to draw axis-x head rows based in dynamic resources."
						},
						{
							value: "MyWidgetResourceLib.MatrixHandler.loadMatrixHeadColumnResource(this); return false;",
							title: "loadMatrixHeadColumnResource",
							description: "Handler to be called on complete of a load action/resource or as the load handler, when we wish to draw axis-x head columns based in dynamic resources."
						},
						{
							value: "MyWidgetResourceLib.MatrixHandler.loadMatrixBodyRowResource(this); return false;",
							title: "loadMatrixBodyRowResource",
							description: "Handler to be called on complete of a load action/resource or as the load handler, when we wish to draw axis-y rows with head columns based in dynamic resources."
						}
					],
				},
				"Html Node": {
					"Load": [
						{
							value: "MyWidgetResourceLib.FieldHandler.onLoadElementFieldsResourceCleanAllHashTagsLeft(this); return false;",
							title: "onLoadElementFieldsResourceCleanAllHashTagsLeft",
							description: "Callback to be called after a load or display handler get executed, to clean all the hashtags left from the html inside of the node element passed as argument."
						},
						
						{
							value: "MyWidgetResourceLib.ResourceHandler.setWidgetResourcesLoadedAttribute(this); return false;",
							title: "setWidgetResourcesLoadedAttribute",
							description: "Set the attribute data-widget-resources-loaded to the current widget."
						},
						{
							value: "MyWidgetResourceLib.ResourceHandler.setDependentWidgetsResourcesLoadedAttribute(this); return false;",
							title: "setDependentWidgetsResourcesLoadedAttribute",
							description: "Set the attribute data-widget-resources-loaded to the dependent widgets."
						},
						{
							value: "MyWidgetResourceLib.ResourceHandler.setParentWidgetResourcesLoadedAttribute(this); return false;",
							title: "setParentWidgetResourcesLoadedAttribute",
							description: "Set the attribute data-widget-resources-loaded to the parent widget with a load resource."
						}
					],
					"Parse": [
						{
							value: "MyWidgetResourceLib.FieldHandler.filterResourceHtml(this, resource_result); return false;",
							title: "filterResourceHtml",
							description: "Handler to be called on parse of a load action. In summary this handler parses the result of a resource and filter it accordingly with a selector."
						}
					],
					"Display": [
						{
							value: "MyWidgetResourceLib.ListHandler.drawListData(this, data); return false;",
							title: "drawListData",
							description: "Handler to be called as a display handler of the 'Display Resource Attribute' settings. This function receives 2 arguments: a html node element and an object with data values to be displayed inside of that node element.<br/>Basically this function will loop the data objects and for each object, will display its values inside of the child node with the [data-widget-item] attribute.<br/>This behaviour is very similar with the method 'MyWidgetResourceLib.ListHandler.loadListTableAndTreeResource' but instead of displaying a resource data, it displays a data that is passed as an argument of this function. In this case, this data could be an object inside of an attribute of a record from a resource, where the loadListTableAndTreeResource displays the records and for a specific attribute, the drawListData method displays the sub-object inside of the correspondent element.<br/>This method should be used when we wish to display objects with other inner objects inside, like tree objects."
						},
						{
							value: "MyWidgetResourceLib.ListHandler.drawListDataRecursively(this, data); return false;",
							title: "drawListDataRecursively",
							description: "Handler to be called as a display handler of the 'Display Resource Attribute' settings. This function receives 2 arguments: a html node element and an object with data values to be displayed inside of that node element.<br/>Basically this function will loop the data objects and for each object, will display its values inside of the node element it-self, based in the parent settings.<br/>Basically it will get parent node with the attribute [data-widget-item] and for each data record will create nodes based in that [data-widget-item] parent node and then append it to the main node (passed as argument in this function).<br/>This behaviour is very similar with the method 'MyWidgetResourceLib.ListHandler.loadListTableAndTreeResource' but instead of displaying a resource data, it displays a data that is passed as an argument of this function. In this case, this data could be an object inside of an attribute of a record from a resource, where the loadListTableAndTreeResource displays the records and for a specific attribute, the drawListDataRecursively method displays the sub-object inside of the correspondent element, based in the parent node with the attribute [data-widget-item].<br/>This method should be used when we wish to display objects with others inner objects inside, like recursive tree objects, but in a recursively way, where each record (independent if is inside of another record) is always displayed with the same html.<br/>This method should be used to show recursive data. However, although not mandatory, works better if called inside of a [data-widget-list] node."
						},
						{
							value: "MyWidgetResourceLib.ListHandler.loadAndDrawListDataRecursively(this, data); return false;",
							title: "loadAndDrawListDataRecursively",
							description: "Handler to be called as a display handler of the 'Display Resource Attribute' settings.<br/>This function receives 1 argument: the current html node element.<br/>This function will get the parent node with the attribute [data-widget-list], find what is the child node with attribute [data-widget-item], save this correspondent HTML into the current node, then load the resource registered in the properties of the current node, replicating the saved HTML for each record of the loaded resource, and appending that HTML to the current node.<br/>Basically this function finds out what is the HTML that should be replicated for each record based in its' parents and then call the method 'MyWidgetResourceLib.ResourceHandler.loadWidgetResource' to load the registered resource based in that HTML.<br/>This method should be used when we wish to display records that have another children records of the same database table, but in a recursively way, where each record is always displayed with the same HTML.<br/>This method should be used to show recursive data from a database table."
						},
						{
							value: "MyWidgetResourceLib.FieldHandler.onLoadElementFieldsResourceCleanAllHashTagsLeft(this); return false;",
							title: "onLoadElementFieldsResourceCleanAllHashTagsLeft",
							description: "Callback to be called after a load or display handler get executed, to clean all the hashtags left from the html inside of the node element passed as argument."
						}
					]
				}
			}
		};
	}
	
	function prepareMenuSettingsEvents(elm) {
		elm.find(".group-title").click(function(event) {
			me.onClickWidgetGroupTitle(this, event);
		});
		
		elm.find(".clear-user-input").click(function(event) {
			var p = $(this).parent();
			var fields = p.children("input, select, textarea");
			
			if (p.hasClass("buttons-style"))
				p.children("i").removeClass("selected");
			
			//update UI
			var reseted = false;
			
			$.each(fields, function(idx, field) {
				field = $(field);
				var style = window.getComputedStyle(field[0]);
    				
    				if (style.display != "none") {
					if (field.is("input[type=checkbox], input[type=radio]")) {
						field.prop("checked", false).removeAttr("checked");
						
						if (field[0].hasAttribute("onChange"))
							field.trigger("change");
						else
							me.onChangeField(field[0], event);
					}
					else if (field.is("select")) {
						field.val("");
						
						if (field[0].hasAttribute("onChange"))
							field.trigger("change");
						else
							me.onChangeField(field[0], event);
					}
					else {
						field.val("");
						
						if (field[0].hasAttribute("onBlur"))
							field.trigger("blur");
						else
							me.onBlurField(field[0], event);
					}
					
					reseted = true;
					return false;
				}
			});
			
			if (!reseted) {
				if (field[0].hasAttribute("onBlur"))
					field.trigger("blur");
				else
					me.onBlurField(fields[0], event);
			}
		});
		
		elm.find(".color-selector").on("input", function(event) {
			var cc = $(this).parent().children(".color-code");
			cc.val( this.value );
			cc.blur();
		});
		
		elm.find("input, select, textarea").each(function(idx, field) {
			field = $(field);
			
			if (!field.hasClass("ignore")) {
				if (!field.attr("onChange") && !field.attr("onBlur")) {
					if (field.is("select, input[type=checkbox], input[type=radio]"))
						field.attr("onChange", ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onChangeField(this, event)');
					else
						field.attr("onBlur", ui_creator.obj_var_name + '.LayoutUIEditorWidgetResource.onBlurField(this, event)');
				}
				
				//add contextmenu with "reset", "choose variable" and "enlarge field" menus
				ui_creator.addMenuSettingsContextMenu(field);
			}
		});
		
		if (typeof elm.sortable == "function")
			elm.find(".widget-sortable-children").each(function(idx, child) {
				$(child).sortable({
					placeholder: "widget-sortable-child-place-holder",
					stop: function(event, ui) {
						updateFieldInSelectedWidget(child);
					}
				});
			});
		
		elm.find(".with-sub-options").children("label, .toggle").click(function(event) {
			toggleMenuSettingsSubOptions(this);
		});
		
		elm.filter(".with-sub-options").children("label, .toggle").click(function(event) {
			toggleMenuSettingsSubOptions(this);
		});
		
		//update the .select-existent-widget-db-brokers select boxes with the available defined db brokers, through: 
		$.each(elm.find(".select-existent-widget-db-brokers"), function(idx, sub_elm) {
			var p = $(sub_elm).parent().closest("li").parent();
			
			updateWidgetDBBrokers(p);
		});
		
		//update the .select-existent-widget-resources select boxes with the available defined resources names/references.
		updateWidgetExistentResourcesReferences( elm.find(".select-existent-widget-resources") );
		
		//update the .select-existent-user-types select boxes with the user types.
		updateWidgetExistentUserTypes( elm.find(".select-existent-user-types") );
		
		//update the .select-existent-widget-ids select boxes with the available defined widgets ids.
		updateWidgetExistentWidgetIds( elm.find(".select-existent-widget-ids") );
		
		//update the .select-existent-widget-popups select boxes with the available defined popup ids.
		updateWidgetExistentPopupIds( elm.find(".select-existent-widget-popups") );
		
		//hide fields that don't have callbacks
		if (typeof me.options.toggle_choose_db_table_attribute_popup_func != "function")
			elm.find(".choose-table-attribute").hide();
		
		if (typeof me.options.toggle_choose_widget_resource_popup_func != "function")
			elm.find(".choose-widget-resource").hide();
		
		if (typeof me.options.toggle_choose_widget_resource_value_attribute_popup_func != "function")
			elm.find(".choose-widget-resource-attribute").hide();
		
		if (typeof ui_creator.options.on_choose_event_func != "function")
			elm.find(".choose-event").hide();
		
		if (typeof ui_creator.options.on_choose_page_url_func != "function")
			elm.find(".choose-page").hide();
	}
	
	function toggleMenuSettingsSubOptions(elm) {
		var p = $(elm).closest("li");
		var is_open = p.hasClass("widget-property-open");
		var input = p.children("input, select");
		var is_active = (input.is("input") && input.is(":checked")) || p.children("select").val();
		
		if (is_open || is_active) {
			var ul = p.children("ul");
			p.toggleClass("widget-property-open");
			ul.css("display", ""); //remove display
			
			p.children(".toggle").toggleClass("zmdi-caret-right zmdi-caret-down");
		}
		else 
			ui_creator.showError("You must enable this option first in order to see its' sub-options...");
	}
	
	function openMenuSettingsSubOptions(elm) {
		var p = $(elm).closest("li");
		var ul = p.children("ul");
		ul.slideDown("slow", function() {
			//ul.show();
			ul.css("display", ""); //remove display
			p.addClass("widget-property-open");
		});
		
		p.children(".toggle").addClass("zmdi-caret-down").removeClass("zmdi-caret-right");
	}
	
	function closeMenuSettingsSubOptions(elm) {
		var p = $(elm).closest("li");
		var ul = p.children("ul");
		ul.slideUp("slow", function() {
			//ul.hide();
			ul.css("display", ""); //remove display
			p.removeClass("widget-property-open");
		});
		
		p.children(".toggle").addClass("zmdi-caret-right").removeClass("zmdi-caret-down");
	}
	
	function addItem(elm, event, html) {
		var li = $(elm).parent().closest("li");
		var ul = li.children("ul");
		
		html = $(html);
		
		ul.children(".empty-items").hide();
		ul.append(html);
		
		return html;
	}
	
	function removeItem(elm, event, message) {
		if (!message || confirm(message)) {
			var li = $(elm).parent().closest("li");
			var ul = li.parent();
			li.remove();
			
			if (ul.children("li:not(.empty-items)").length == 0)
				ul.children(".empty-items").show();
			
			updateFieldInSelectedWidget(ul);
		}
	}
	
	function getLabel(str, lower) {
		return (lower ? str.toLowerCase() : str).replace(/_/g, " ").replace(/\b\w/g, function(m) { return m.toUpperCase() });
	}
	
	function convertHtmlElementToLayoutUIEditorWidget(elm) {
		ui_creator.convertHtmlElementToWidget(elm);
		ui_creator.refreshElementMenuLayer( elm.parent() );
		
		onChangeSelectedWidget();
	}
}
