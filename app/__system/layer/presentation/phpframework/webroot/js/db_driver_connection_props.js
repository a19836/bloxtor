var db_ignore_options = null;

function toggleDBPasswordField(elm) {
	var field = $(elm).parent().children("input");
	
	if (field.attr("type") == "password")
		field[0].type = "text";
	else
		field[0].type = "password";
}

function toggleDBAdvancedFields(elm) {
	elm = $(elm);
	var p = elm.parent().closest(".form_fields");
	p.find(".form_field_db_advanced").toggleClass("can_be_shown");
	
	toggleDBFields(p);
	
	elm.toggleClass("can_be_shown");
	elm.html(elm.hasClass("can_be_shown") ? "Hide Advanced Options" : "Show Advanced Options");
	
	var select = p.find(".db_extension select");
	onChangeDBExtension(select[0]);
}

function toggleDBFields(form_fields) {
	var db_type = form_fields.find(".db_type select").val();
	
	if (db_type == "")
		form_fields.find(".form_field_db:not(.db_type)").hide();
	else {
		//reset fields, showing them all
		form_fields.children(".form_field_db").show(); 
		
		//reset advanced fields
		form_fields.children(".form_field_db.form_field_db_advanced:not(.can_be_shown)").hide();
		
		//then hide the ignore fields
		var ignore_options = getDBIgnoreOptions(form_fields);
		
		if (ignore_options)
			$.each(ignore_options, function(idx, ignore_option) {
				form_fields.find("." + (ignore_option.substr(0, 3) == "db_" ? "" : "db_") + ignore_option).hide();
			});
	}
}

function onChangeDBType(db_type_elm) {
	db_type_elm = $(db_type_elm);
	var db_type = db_type_elm.val();
	var p = db_type_elm.parent().closest(".form_fields");
	
	//reset fields to their initial state
	toggleDBFields(p);
	
	var select = p.find(".db_extension select");
	prepareDBExtension(select, db_type);
	onChangeDBExtension(select[0]);
	
	var select = p.find(".db_encoding select");
	prepareDBEncoding(select, db_type);
}

function onChangeDBExtension(elm) {
	elm = $(elm);
	var extension = elm.val();
	var p = elm.parent().closest(".form_fields");
		
	toggleDBFields(p);
	
	/* DEPRECATED: bc is already inside of the toggleFields which contains the ignore_options_by_extension
	if (extension == "pdo" || extension == "odbc") {
		var db_type = p.find(".db_type select").val();
		
		if (db_type) { //only show if db_type is selected
			if (!ignoreField("odbc_data_source")) //only show if is not a field to ignore
				p.find(".db_odbc_data_source.can_be_shown").show(); //and if can_be_shown 
			
			if (!ignoreField("odbc_driver")) //only show if is not a field to ignore
				p.find(".db_odbc_driver.can_be_shown").show(); //and if can_be_shown 
			
			if (!ignoreField("extra_dsn")) //only show if is not a field to ignore
				p.find(".db_extra_dsn.can_be_shown").show(); //and if can_be_shown 
		}
	}
	else
		p.find(".db_odbc_data_source, .db_odbc_driver, .db_extra_dsn").hide();*/
}

function prepareDBExtension(select, db_type) {
	var extension_options = '';
	
	if (drivers_extensions) {
		var driver_extensions = drivers_extensions[db_type];
		
		if ($.isArray(driver_extensions))
			$.each(driver_extensions, function(idx, value) {
				extension_options += '<option value="' + value + '">' + value + (idx == 0 ? " - Default" : "") + '</option>';
			});
	}
	
	var selected_extension = select[0].hasAttribute("selected_extension") ? select.attr("selected_extension") : select.val();
	
	select.html(extension_options);
	select.val(selected_extension);
	
	//set saved value
	if (select.val() == selected_extension)
		select.removeAttr("selected_extension");
	else if (selected_extension)
		select.attr("selected_extension", selected_extension);
}

function prepareDBEncoding(select, db_type) {
	var encoding_options = '<option value="">-- Default --</option>';
	var driver_encodings = $.isPlainObject(drivers_encodings) ? drivers_encodings[db_type] : null;
	
	if (!$.isPlainObject(driver_encodings) || $.isEmptyObject(driver_encodings))
		select.parent().closest(".db_encoding").hide();
	else {
		if (db_type && !ignoreField("encoding")) //only show if db_type is selected and is not a field to ignore
			select.parent().closest(".db_encoding.can_be_shown").show(); //and if can_be_shown 
		
		$.each(driver_encodings, function(enc, label) {
			encoding_options += '<option value="' + enc + '">' + label + '</option>';
		});
	}
	
	var selected_encoding = select[0].hasAttribute("selected_encoding") ? select.attr("selected_encoding") : select.val();
	
	select.html(encoding_options);
	select.val(selected_encoding);
	
	//set saved value
	if (select.val() == selected_encoding)
		select.removeAttr("selected_encoding");
	else if (selected_encoding)
		select.attr("selected_encoding", selected_encoding);
}

function getDBIgnoreOptions(form_fields) {
	var db_type = form_fields.find(".db_type select").val();
	var extension = form_fields.find(".db_extension select").val();
	var ignore_options = null;
	
	if ($.isPlainObject(drivers_ignore_connection_options) && drivers_ignore_connection_options.hasOwnProperty(db_type) && $.isArray(drivers_ignore_connection_options[db_type]))
		ignore_options = drivers_ignore_connection_options[db_type];
	
	if ($.isPlainObject(drivers_ignore_connection_options_by_extension) && drivers_ignore_connection_options_by_extension.hasOwnProperty(db_type) && $.isPlainObject(drivers_ignore_connection_options_by_extension[db_type]) && $.isArray(drivers_ignore_connection_options_by_extension[db_type][extension])) {
		if (!ignore_options)
			ignore_options = drivers_ignore_connection_options_by_extension[db_type][extension];
		else
			ignore_options = ignore_options.concat(drivers_ignore_connection_options_by_extension[db_type][extension]);
	}
	
	db_ignore_options = ignore_options;
	
	return ignore_options;
}

function ignoreField(name) {
	return name && db_ignore_options && $.inArray(name, db_ignore_options) != -1;
}
