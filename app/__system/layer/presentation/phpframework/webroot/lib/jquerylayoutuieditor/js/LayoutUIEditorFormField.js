/*
 * Copyright (c) 2007 PHPMyFrameWork - Joao Paulo Lopes Pinto -- http://jplpinto.com
 * The use of this code must comply with the Bloxtor framework license.
 */

function LayoutUIEditorFormField(ui_creator) {
	/* #ADD_SECURITY_CODE_HERE# */  //Important: do not remove this comment because it will be used by the other/scripts/obfuscate_js_files.php to only allow this code in the allowed domains.
	
	var me = this;
	
	me.init = function() {
		if (ui_creator && typeof FormFieldsUtilObj == "object" && typeof ProgrammingTaskUtil == "object") {
			var menu_widgets = ui_creator.getMenuWidgets().find(".menu-widget-input, .menu-widget-textarea, .menu-widget-radiobox, .menu-widget-checkbox, .menu-widget-select, .menu-widget-button");
			
			$.each(menu_widgets, function(idx, menu_widget) {
				menu_widget = $(menu_widget);
				var props = menu_widget.children(".properties");
				
				var func = menu_widget.attr("data-on-create-template-widget-func");
				menu_widget.attr("data-on-create-template-widget-func", ui_creator.obj_var_name + ".LayoutUIEditorFormField.formFieldUtilCreateTemplateWidget");
				if (func)
					menu_widget.attr("data-on-create-template-widget-orig-func", func);
				
				var func = props.attr("data-on-open-settings-func");
				props.attr("data-on-open-settings-func", ui_creator.obj_var_name + ".LayoutUIEditorFormField.formFieldUtilOpenSettings");
				if (func)
					props.attr("data-on-open-settings-orig-func", func);
				
				var func = props.attr("data-on-after-save-settings-field-func");
				props.attr("data-on-after-save-settings-field-func", ui_creator.obj_var_name + ".LayoutUIEditorFormField.formFieldUtilSaveSettingsField");
				if (func)
					props.attr("data-on-after-save-settings-field-orig-func", func);
				
				var func = props.attr("data-on-before-parse-widget-settings-func");
				props.attr("data-on-before-parse-widget-settings-func", ui_creator.obj_var_name + ".LayoutUIEditorFormField.formFieldUtilParseSettings");
				if (func)
					props.attr("data-on-before-parse-widget-settings-orig-func", func);
			});
		}
	};
	
	me.formFieldUtilParseSettings = function(widget, widget_settings) {
		if (!widget_settings.hasOwnProperty("extra"))
			widget_settings["extra"] = {};
		
		for (var i = 0; i < widget[0].attributes.length; i++) {
			var attr = widget[0].attributes[i];
			var attr_name = attr.name.toLowerCase();
			var attr_value = attr.value;
			
			if (attr_value.length > 0)
				switch (attr_name) {
					case "data-allow-null":
					case "allow-null":
					case "allow_null":
					case "allownull":
						widget_settings["extra"]["allow_null"] = attr_value;
						break;
					case "data-allow-javascript":
					case "allow-javascript":
					case "allow_javascript":
					case "allowjavascript":
						widget_settings["extra"]["allow_javascript"] = attr_value;
						break;
					case "data-validation-regex":
					case "validation-regex":
					case "validation_regex":
					case "validationregex":
						widget_settings["extra"]["validation_regex"] = attr_value;
						break;
					case "data-validation-func":
					case "validation-func":
					case "validation_func":
					case "validationfunc":
						widget_settings["extra"]["validation_func"] = attr_value;
						break;
					case "data-validation-label":
					case "validation-label":
					case "validation_label":
					case "validationlabel":
						widget_settings["extra"]["validation_label"] = attr_value;
						break;
					case "data-validation-message":
					case "validation-message":
					case "validation_message":
					case "validationmessage":
						widget_settings["extra"]["validation_message"] = attr_value;
						break;
					case "data-validation-type":
					case "validation-type":
					case "validation_type":
					case "validationtype":
						widget_settings["extra"]["validation_type"] = attr_value;
						break;
					case "data-confirmation":
					case "confirmation":
						widget_settings["extra"]["confirmation"] = attr_value;
						break;
					case "data-confirmation-message":
					case "confirmation-message":
					case "confirmation_message":
					case "confirmationmessage":
						widget_settings["extra"]["confirmation_message"] = attr_value;
						break;
					case "data-min-words":
					case "min-words":
					case "min_words":
					case "minwords":
						widget_settings["extra"]["min_words"] = attr_value;
						break;
					case "data-max-words":
					case "max-words":
					case "max_words":
					case "maxwords":
						widget_settings["extra"]["max_words"] = attr_value;
						break;
					case "data-min-length":
					case "min-length":
					case "min_length":
					case "minlength":
						widget_settings["extra"]["min_length"] = attr_value;
						break;
					case "data-max-length":
					case "max-length":
					case "max_length":
					case "maxlength":
						widget_settings["extra"]["max_length"] = attr_value;
						break;
					case "data-min":
					case "min":
					case "data-min-value":
					case "min-value":
					case "min_value":
					case "minvalue":
						widget_settings["extra"]["min_value"] = attr_value;
						break;
					case "data-max":
					case "max":
					case "data-max-value":
					case "max-value":
					case "max_value":
					case "maxvalue":
						widget_settings["extra"]["max_value"] = attr_value;
						break;
				}
		}
		
		var menu_widget = ui_creator.getTemplateMenuWidget(widget);
		var func = menu_widget.children(".properties").attr("data-on-before-parse-widget-settings-orig-func");
		if (func && eval('typeof ' + func + ' == "function"'))
			eval(func + '(widget, widget_settings);');
	};
	
	//Bascially this is only to set the ignore_attributes in each of the ui_creator.menu_widgets_objs, so when we execute LayoutUIEditor.convertHtmlContentIntoWidget, the form fields attributes won't be added to the widgets properties
	me.formFieldUtilCreateTemplateWidget = function(widget, html_element) {
		var menu_widget = ui_creator.getTemplateMenuWidget(widget);
		
		var func = menu_widget.attr("data-on-create-template-widget-orig-func");
		if (func && eval('typeof ' + func + ' == "function"'))
			eval(func + '(widget, html_element);');
		
		var widget_tag = menu_widget.attr("data-tag");
		var widget_obj = ui_creator.menu_widgets_objs[widget_tag];
		
		if (widget_obj) {
			var new_attributes_to_ignore = ["data-allow-null", "allow-null", "allow_null", "allownull", "data-allow-javascript", "allow-javascript", "allow_javascript", "allowjavascript", "data-validation-regex", "validation-regex", "validation_regex", "validationregex", "data-validation-func", "validation-func", "validation_func", "validationfunc", "data-validation-label", "validation-label", "validation_label", "validationlabel", "data-validation-message", "validation-message", "validation_message", "validationmessage", "data-validation-type", "validation-type", "validation_type", "validationtype", "data-confirmation", "confirmation", "data-confirmation-message", "confirmation-message", "confirmation_message", "confirmationmessage", "data-min-words", "min-words", "min_words", "minwords", "data-max-words", "max-words", "max_words", "maxwords", "data-min-length", "min-length", "min_length", "minlength", "data-max-length", "max-length", "max_length", "maxlength", "data-min", "min", "data-min-value", "min-value", "min_value", "minvalue", "data-max", "max", "data-max-value", "max-value", "max_value", "maxvalue"];
			
			if (widget_obj.hasOwnProperty("ignore_attributes") && $.isArray(widget_obj.ignore_attributes)) {
				if (widget_obj.ignore_attributes.indexOf("data-allow-null") == -1) //only add if it didn't added before
					widget_obj.ignore_attributes = widget_obj.ignore_attributes.concat(new_attributes_to_ignore);
			}
			else
				widget_obj.ignore_attributes = new_attributes_to_ignore;
		}
	};
	
	me.formFieldUtilOpenSettings = function(widget, menu_settings) {
		var menu_widget = ui_creator.getTemplateMenuWidget(widget);
		var func = menu_widget.children(".properties").attr("data-on-open-settings-orig-func");
		if (func && eval('typeof ' + func + ' == "function"'))
			eval(func + '(widget, menu_settings);');
		
		if (widget && widget[0]) {
			var settings_properties_elm = menu_settings.find(" > .settings-properties");
			
			var props = ui_creator.parseWidgetAttributesToSettings(widget);
			var nn = widget[0].nodeName.toLowerCase();
			
			switch (nn) {
				case "input":
					var t = widget.attr("type") ? widget.attr("type").toLowerCase() : "text";
					props["type"] = t =="image" ? "button_img" : t;
					break;
				case "a":
					props["type"] = "link";
					break;
				case "img":
					props["type"] = "image";
					break;
				
				default:
					props["type"] = nn; //it works for textarea, button, label, h1, h2...
			}
			
			if (!props.hasOwnProperty("allow_null"))
				props["allow_null"] = 1;
			
			var html = $( FormFieldsUtilObj.getFieldInputSettingsHtml("extra", props) );
			
			var other_settings = html.filter(".other_settings");
			other_settings.children("label").first().remove();
			var items = other_settings.children();
			
			$.each( items.not(".clear"), function(idx, item) {
				item = $(item);
				item.attr("class", "form-group row settings-property " + ("" + item.attr("class")).replace(/_/g, "-"));
				
				var label = item.children("label").first();
				label.attr("class", "col-md-4 col-sm-5 col-form-label " + label.attr("class"));
				
				if (!label[0].hasAttribute("title")) {
					var title = ("" + $(label).text()).replace(/^\s*/g, "").replace(/\s*$/g, "");
					
					if (title != "")
						label.attr("title", title.replace(/\s*:\s*$/, "") );
				}
				
				var fields = item.children("input, textarea, select");
				
				$.each(fields, function(idy, input) {
					input = $(input);
					var t = ("" + input.attr("type")).toLowerCase();
					
					if (t == "checkbox" || t == "radio") {
						//input.attr("class", "col-md-1 col-sm-1 form-control " + input.attr("class"));
					}
					else
						input.attr("class", "col-md-8 col-sm-7 form-control " + input.attr("class"));
				});
				
				fields.blur(function (event) {
					return ui_creator.saveMenuSettingsField(this);
				});
				
				ui_creator.addMenuSettingsContextMenu(fields);
			});
			
			settings_properties_elm.children("ul").append(items);
		}
	};
	
	me.formFieldUtilSaveSettingsField = function(field, widget, status) {
		var status = true;
		
		var menu_widget = ui_creator.getTemplateMenuWidget(widget);
		var func = menu_widget.children(".properties").attr("data-on-after-save-settings-field-orig-func");
		if (func && eval('typeof ' + func + ' == "function"'))
			eval('status = ' + func + '(field, widget, status);');
		
		//if blur of any of the new settings, get correspondent value and set new attribute to widget html.
		if (status) {
			var name = field.getAttribute("name");
			
			if (name && name.substr(0, 6) == "extra[") {
				var prop_name = name.substr(6, name.length - 7);
				
				var sp = $(field).parent().closest(".settings-properties");
				var props = ui_creator.getMenuSettingsProperties(sp);
				props = props && props.hasOwnProperty("extra") ? props["extra"] : {};
				var prop_value = props && props.hasOwnProperty(prop_name) ? props[prop_name] : "";
				var is_checkbox = $(field).is("input[type=checkbox]");
				
				var attr_name = "data-" + prop_name.replace(/_/g, "-");
				
				switch (prop_name) {
					case "min_length":
					case "max_length":
						attr_name = prop_name.replace("_length", "Length");
						break;
					case "min_value":
					case "max_value":
						attr_name = prop_name.substr(0, 3);
						break;
				}
				
				if (prop_value != "" || $.isNumeric(prop_value))
					widget.attr(attr_name, prop_value);
				else if (is_checkbox && !prop_value)
					widget.attr(attr_name, 0);
				else
					widget.removeAttr(attr_name);
			}
		}
		
		return status;
	};
}
