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

var  DBDriverTaskPropertyObj = {
	encodings : null,
	extensions : null,
	ignore_options : null,
	ignore_options_by_extension : null,
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		//console.log(properties_html_elm);
		//console.log(task_id);
		//console.log(task_property_values);
		
		var task_html_elm = properties_html_elm.find('.db_driver_task_html');
		
		//var html = '';
		//properties_html_elm.find('.db_driver_task_html').html(html);
		
		var type = $.isPlainObject(task_property_values) && task_property_values.hasOwnProperty("type") && task_property_values["type"] ? task_property_values["type"] : task_html_elm.find('.type select').val();
		
		//TOGGLE FIELDS
		//DBDriverTaskPropertyObj.toggleFields(task_html_elm); //No need bc is calling in the onChangeExtension function.
		
		//PREPARING EXTENSIONS
		DBDriverTaskPropertyObj.updateExtensionByType(task_html_elm, type);
		var extension_select = task_html_elm.find('.extension select');
		extension_select.removeAttr("selected_extension");
		
		if (task_property_values["extension"]) {
			extension_select.val(task_property_values["extension"]); //remove selected_extension to reset the previous loaded extension
			
			if (extension_select.val() != task_property_values["extension"]) {
				var html = '<option value="' + task_property_values["extension"] + '">' + task_property_values["extension"] + ' - DEPRECATED</option>';
				extension_select.append(html).val(task_property_values["extension"]);
			}
		}
		else
			extension_select.val("");
		
		DBDriverTaskPropertyObj.onChangeExtension(extension_select[0]);
		
		//PREPARING PASSWORD
		var password = task_html_elm.find('.password');
		if (("" + password.children("input").val()).substr(0, 1) == "$")
			DBDriverTaskPropertyObj.togglePasswordField( password.children(".toggle_password")[0] );
		
		//PREPARING ENCODINGS
		DBDriverTaskPropertyObj.updateEncodingByType(task_html_elm, type);
		var encoding_select = task_html_elm.find('.encoding select');
		encoding_select.removeAttr("selected_encoding");
		
		if (task_property_values["encoding"]) {
			encoding_select.val(task_property_values["encoding"]); //remove selected_encoding to reset the previous loaded encoding
			
			if (encoding_select.val() != task_property_values["encoding"]) {
				var html = '<option value="' + task_property_values["encoding"] + '">' + task_property_values["encoding"] + ' - DEPRECATED</option>';
				encoding_select.append(html).val(task_property_values["encoding"]);
			}
		}
		else
			encoding_select.val("");
	},
	
	onSubmitTaskProperties : function(selected_task_properties_elm, task_id, task_property_values) {
		checkIfLayerTaskPropertiesContainsGlobalVariables(selected_task_properties_elm);
		
		//check if there was any changes and if so alert message to change manually the correspondent wordpress installation's DB credentials too, if exist...
		var query_string = myWFObj.getTaskFlowChart().Property.getPropertiesQueryStringFromHtmlElm(selected_task_properties_elm, "task_property_field");
		var new_task_property_values = {};
		
		try {
			parse_str(query_string, new_task_property_values);
		}
		catch(e) {}
		
		var status = true;
		
		if (!task_property_values["port"])
			task_property_values["port"] = "";
			
		var is_different = new_task_property_values["host"] != task_property_values["host"] || new_task_property_values["port"] != task_property_values["port"] || new_task_property_values["db_name"] != task_property_values["db_name"] || new_task_property_values["username"] != task_property_values["username"] || new_task_property_values["password"] != task_property_values["password"];
		
		if (is_different)
			status = confirm("By changing this DB Driver's credentials, you must then update manually the correspondent wordpress installation's DB credentials too, if exist...\nDo you wish to continue?\n\nIf so, please don't forget to change the correspondent wordpress' wp-config.php file with these new credentials.");
		
		return status;
	},
	
	onTaskCloning : function(task_id) {
		onLayerTaskCloning(task_id, {do_not_show_task_properties : false}); //show properties on task cloning
	},
	
	onCheckLabel : function(label_obj, task_id) {
		return onCheckTaskLayerLabel(label_obj, task_id);
	},
	
	onCancelLabel : function(task_id) {
		return prepareLabelIfUserLabelIsInvalid(task_id);
	},
	
	onChangeType : function(elm) {
		elm = $(elm);
		var type = elm.val();
		var task_html_elm = elm.parent().closest(".db_driver_task_html");
		
		//this.toggleFields(task_html_elm); //No need bc is calling in the onChangeExtension function.
		
		this.updateExtensionByType(task_html_elm, type);
		this.updateEncodingByType(task_html_elm, type);
		
		this.onChangeExtension( task_html_elm.find(".extension select")[0] );
	},
	
	onChangeExtension : function(elm) {
		elm = $(elm);
		var extension = elm.val();
		var task_html_elm = elm.parent().closest(".db_driver_task_html");
		
		this.toggleFields(task_html_elm);
		
		/* DEPRECATED: bc is already inside of the toggleFields which contains the ignore_options_by_extension
		if (extension == "pdo" || extension == "odbc") {
			task_html_elm.find(".odbc_data_source, .odbc_driver, .extra_dsn").show(); 
		}
		else
			task_html_elm.find(".odbc_data_source, .odbc_driver, .extra_dsn").hide();*/
	},
	
	updateExtensionByType : function(task_html_elm, type) {
		var extension_options = '';
		
		if (this.extensions) {
			var driver_extensions = this.extensions[type];
			
			if ($.isArray(driver_extensions))
				$.each(driver_extensions, function(idx, value) {
					extension_options += '<option value="' + value + '">' + value + (idx == 0 ? " - Default" : "") + '</option>';
				});
		}
		
		var select = task_html_elm.find('.extension select');
		var selected_extension = select[0].hasAttribute("selected_extension") ? select.attr("selected_extension") : select.val();
		
		select.html(extension_options);
		select.val(selected_extension);
		
		//set saved value
		if (select.val() == selected_extension)
			select.removeAttr("selected_extension");
		else if (selected_extension)
			select.attr("selected_extension", selected_extension);
	},
	
	updateEncodingByType : function(task_html_elm, type) {
		var encoding_options = '<option value="">-- Default --</option>';
		var driver_encodings = $.isPlainObject(this.encodings) ? this.encodings[type] : null;
		
		//this.toggleFields(task_html_elm); //No need bc is calling before this functions gets called.
		
		if (!$.isPlainObject(driver_encodings) || $.isEmptyObject(driver_encodings))
			task_html_elm.find('.encoding').hide();
		else {
			task_html_elm.find('.encoding').show();
			
			$.each(driver_encodings, function(enc, label) {
				encoding_options += '<option value="' + enc + '">' + label + '</option>';
			});
		}
		
		var select = task_html_elm.find('.encoding select');
		var selected_encoding = select[0].hasAttribute("selected_encoding") ? select.attr("selected_encoding") : select.val();
		
		select.html(encoding_options);
		select.val(selected_encoding);
		
		//set saved value
		if (select.val() == selected_encoding)
			select.removeAttr("selected_encoding");
		else if (selected_encoding)
			select.attr("selected_encoding", selected_encoding);
	},
	
	togglePasswordField : function(elm) {
		var field = $(elm).parent().children("input");
		
		if (field.attr("type") == "password")
			field[0].type = "text";
		else
			field[0].type = "password";
	},
	
	toggleFields : function(task_html_elm) {
		//reset fields, showing them all
		task_html_elm.children().show(); 
		
		//then hide the ignore fields
		var ignore_options = this.getIgnoreOptions(task_html_elm);
		
		if (ignore_options)
			$.each(ignore_options, function(idx, ignore_option) {
				task_html_elm.find('.' + ignore_option).hide();
			});
	},
	
	getIgnoreOptions : function(task_html_elm) {
		var type = task_html_elm.find(".type select").val();
		var extension = task_html_elm.find(".extension select").val();
		var ignore_options = null;
		
		if ($.isPlainObject(this.ignore_options) && this.ignore_options.hasOwnProperty(type) && $.isArray(this.ignore_options[type]))
			ignore_options = this.ignore_options[type];
		
		if ($.isPlainObject(this.ignore_options_by_extension) && this.ignore_options_by_extension.hasOwnProperty(type) && $.isPlainObject(this.ignore_options_by_extension[type]) && $.isArray(this.ignore_options_by_extension[type][extension])) {
			if (!ignore_options)
				ignore_options = this.ignore_options_by_extension[type][extension];
			else
				ignore_options = ignore_options.concat(this.ignore_options_by_extension[type][extension]);
		}
		
		return ignore_options;
	},
};
