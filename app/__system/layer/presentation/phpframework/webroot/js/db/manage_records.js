$(function () {
	var manage_records = $(".manage_records");
	
	updateTableHeight();
	
	/*manage_records.find("table").first().DataTable({
		"scrollY": (h - 70) + "px",
		"scrollCollapse": true,
		"scrollX": false,
		"lengthMenu": [[50, 100, 200, 500, -1], [50, 100, 200, 500, "All"]],
	});

	manage_records.find(".dataTables_scrollHead, .dataTables_scrollHead thead tr").each(function(idx, elm){
		$(elm).addClass("table_header");
	});*/
	
	MyJSLib.FormHandler.initForm( manage_records.find("form")[0] );
});

function updateTableHeight() {
	var manage_records = $(".manage_records");
	var top_pagination = manage_records.children(".top_pagination");	
	var bottom_pagination = manage_records.children(".bottom_pagination");	
	var conditions = manage_records.children(".conditions");
	var buttons = manage_records.children(".buttons");
	var total = manage_records.children(".total");
	
	var wh = $(window).height();
	var tph = top_pagination.is(":visible") ? top_pagination.height() + parseInt(top_pagination.css("margin-top")) + parseInt(top_pagination.css("margin-bottom")) : 0;
	var bph = bottom_pagination.is(":visible") ? bottom_pagination.height() + parseInt(bottom_pagination.css("margin-top")) + parseInt(bottom_pagination.css("margin-bottom")) : 0;
	var ch = conditions.height() + parseInt(conditions.css("margin-top")) + parseInt(conditions.css("margin-bottom"));
	var bh = buttons.height() + parseInt(buttons.css("margin-top")) + parseInt(buttons.css("margin-bottom"));
	var th = total.height() + parseInt(total.css("margin-top")) + parseInt(total.css("margin-bottom"));
	var h = wh - (140 + tph + bph + ch + bh + th);
	h = h < 200 ? 200 : h;
	
	manage_records.find(" > form > .responsive_table").css("height", h + "px");
}

function goBackPage() {
	window.history.back();
}

function refreshPage() {
	var url = document.location;
	document.location= url;
}

function onDBTypeChange(elm) {
	elm = $(elm);
	var db_type = elm.val();
	
	MyFancyPopup.init({
		parentElement: window,
	});
	MyFancyPopup.showOverlay();
	MyFancyPopup.showLoading();
	
	var url = "" + document.location;
	url = url.replace("/db_type=[^&]*/g", "");
	url += "&db_type=" + db_type;
	
	setTimeout(function() {
		document.location= url;
	}, 500);
}

function addCondition(elm) {
	elm = $(elm);
	var p = elm.parent();
	var option = p.find("select option:selected");
	var attribute = option.attr("value");
	var ul = p.closest(".conditions").children("ul");
	
	if (ul.find("li input[name=" + attribute + "]").length > 0)
		StatusMessageHandler.showError("This condition already exists. Choose another one...");
	else {
		var li = $( new_condition_html.replace(/#field_name#/g, option.attr("value")) );
		li.children("label").html( option.html() );
		
		ul.append(li);
		
		updateTableHeight();
	}
}

function deleteCondition(elm) {
	$(elm).parent().remove();
	updateTableHeight();
}

function searchCondition(elm) {
	elm = $(elm);
	
	var url = "" + document.location;
	var expression = new RegExp("&(conditions|conditions_operators)\\[[^\\]]+\\]=[^&]*", "gi"); 
	url = url.replace(expression, "");
	
	var inputs = elm.parent().closest(".conditions").find("ul li input");
	$.each(inputs, function(idx, input) {
		input = $(input);
		var select = input.parent().children("select");
		
		url += "&conditions[" + input.attr("name") + "]=" + input.val();
		url += "&conditions_operators[" + input.attr("name") + "]=" + select.val();
	});
	
	document.location= url;
}

function resetCondition(elm) {
	elm = $(elm);
	
	var url = "" + document.location;
	var expression = new RegExp("&(conditions|conditions_operators)\\[[^\\]]+\\]=[^&]*", "gi"); 
	url = url.replace(expression, "");
	
	document.location= url;
}

function toggleAll(elm) {
	elm = $(elm);
	var inputs = elm.parent().closest("table").find("tbody tr td.select_item input");
	
	if (elm.is(":checked"))
		inputs.attr("checked", "checked").prop("checked", true);
	else
		inputs.removeAttr("checked").prop("checked", false);
}

function sortRecords(elm) {
	elm = $(elm);
	var td = elm.parent();
	var field_name = td.attr("attr_name");
	var order = elm.hasClass("sort_asc") ? "desc" : "asc";
	
	elm.addClass("loading").removeClass("sort_asc sort_desc");
	
	var url = "" + document.location;
	var expression = new RegExp("&sorts\\[" + field_name + "\\]=[^&]*", "gi"); 
	url = url.replace(expression, "");
	url += "&sorts[" + field_name + "]=" + order;
	
	document.location= url;
}

function unsortRecords(elm) {
	elm = $(elm);
	var td = elm.parent();
	var field_name = td.attr("attr_name");
	
	td.children(".sort").addClass("loading").removeClass("sort_asc sort_desc");
	
	var url = "" + document.location;
	var expression = new RegExp("&sorts\\[" + field_name + "\\]=[^&]*", "gi"); 
	url = url.replace(expression, "");
	
	document.location= url;
}

function insertNewRecord(elm) {
	editRow(elm);
}

function downloadFile(elm, attr_name) {
	if (attr_name) {
		elm = $(elm);
		var row = elm.parent().closest("tr");
		var pks = getPKsObj(row);
		
		if (!$.isEmptyObject(pks)) {
			var query_string = "";
			for (var k in pks)
				query_string += "&pks[" + k + "]=" + pks[k];
			
			var url = manage_record_action_url + "&download=1&attr_name=" + attr_name + query_string;
			window.open(url, "download_file");
		}
		else 
			StatusMessageHandler.showError("Could not get primary keys for this record!");
	}
	else 
		StatusMessageHandler.showError("No attribute name specify!");
}

function deleteRow(elm) {
	if (confirm("Do you wish to delete this record?")) {
		elm = $(elm);
		var row = elm.parent().closest("tr");
		var pks = getPKsObj(row);
		
		if (!$.isEmptyObject(pks)) {
			$.ajax({
				type : "post",
				url : manage_record_action_url,
				data : {"action" : "delete", "conditions" : pks},
				dataType : "text",
				success : function(data, textStatus, jqXHR) {
					if (data == "1") {
						row.remove();
						StatusMessageHandler.showMessage("Record deleted successfully!", "", "bottom_messages", 1500);
					}
					else
						StatusMessageHandler.showError(data ? data : "Error deleting this record. Please try again...");
				},
				error : function(jqXHR, textStatus, errorThrown) { 
					if (jqXHR.responseText)
						StatusMessageHandler.showError(jqXHR.responseText);
				},
			});
		}
		else 
			StatusMessageHandler.showError("Could not get primary keys for this record!");
	}
}

function toggleRow(elm, already_saved) {
	elm = $(elm);
	var row = elm.parent().closest("tr");
	var is_editable = row.hasClass("editable");
	
	if (!already_saved && is_editable && confirm("Did you save your changes to DB? If not you will loose them. Do you wish to save this record?"))
		saveRow( row.find(".actions .save")[0], true );
	else {
		var attributes = {};
		
		row.children("td:not(.select_item):not(.actions):not(.fks):not(.binary)").each(function(idx, td) {
			td = $(td);
			var attr_name = td.attr("attr_name");
			var current_value = getAttributeValue(td);
			var field_html_type = table_fields_types[attr_name];
			field_html_type = field_html_type ? field_html_type : "text";
			
			if (is_editable) {
				if (field_html_type == "file")
					td.children("input").remove();
				else {
					td.html(""); //remove inputs so we can then call the setAttributeValue method
					
					var v = already_saved ? current_value : td.data("current_value");
					setAttributeValue(td, v);
					
					attributes[attr_name] = v;
				}
			}
			else {
				attributes[attr_name] = current_value;
				
				var td_width = td.width();
				var td_height = td.height();
				var options = null;
				
				if ($.isPlainObject(field_html_type)) {
					options = field_html_type["options"];
					field_html_type = options ? field_html_type["type"] : "text";
				}
				
				var html = "";
				
				if (field_html_type == "textarea")
					html = '<textarea>' + current_value + '</textarea>';
				else if (field_html_type == "file")
					html = '<input type="file"/>';
				else if (field_html_type == "select") {
					html = '<select>';
					var value_exists = false;
					
					if ($.isPlainObject(options) || $.isArray(options)) {
						//add empty value as the first value, otherwise it will be at the end
						var exists_empty_option = $.isPlainObject(options) && options.hasOwnProperty("");
						
						if (exists_empty_option) {
							value_exists = current_value == "";
							html += '<option value=""' + (value_exists ? ' selected' : '') + '>' + options[""] + '</option>';
						}
						
						//add other values
						$.each(options, function(v, l) {
							if (!exists_empty_option || v != "") {
								html += '<option value="' + v + '"' + (v == current_value ? ' selected' : '') + '>' + l + '</option>';
								
								if (v == current_value)
									value_exists = true;
							}
						});
					}
					
					if (!value_exists)
						html += '<option value="' + current_value + '" selected>' + current_value + '</option>';
					
					html += '</select>';
				}
				else if (field_html_type == "checkbox" || field_html_type == "radio")
					html = '<input type="' + field_html_type + '" value="1"' + (parseInt(current_value) == 1 ? ' checked' : '') + '/>';
				else 
					html = '<input type="' + field_html_type + '" value="' + current_value + '"/>';
				
				if (field_html_type == "file")
					td.append(html);
				else {
					td.html(html);
					
					td.data("current_value", current_value);
					td.css({width: td_width, height: td_height});
					
					prepareRecordFields(td);
				}
			}
		});
		
		setLinksAttributesObj(row, attributes);
		
		row.toggleClass("editable");
	}
}

function saveRow(elm, do_not_confirm) {
	if (do_not_confirm || confirm("Do you wish to save this record?")) {
		elm = $(elm);
		var row = elm.parent().closest("tr");
		var pks = getPKsObj(row);
		var attributes = getAttributesObj(row);
		var file_inputs_exist = fileInputsContainsNewFiles(row.find("input[type=file]"));
		
		if (!$.isEmptyObject(pks) && (!$.isEmptyObject(attributes) || file_inputs_exist)) {
			var ajax_options = {
				type : "post",
				url : manage_record_action_url,
				data : {"action" : "update", "attributes": attributes, "conditions" : pks},
				dataType : "text",
				success : function(data, textStatus, jqXHR) {
					if (data == "1") {
						StatusMessageHandler.showMessage("Record saved successfully!", "", "bottom_messages", 1500);
						
						//update pks in case the user change them
						for (var k in pks)
							if (attributes[k] != pks[k])
								pks[k] = attributes[k];
						
						setPKsObj(row, pks);
						
						//toggle input fields to non-edit mode.
						toggleRow(elm[0], true);
						
						if (file_inputs_exist)
							updateNewFileAttributes(elm, pks);
					}
					else
						StatusMessageHandler.showError(data ? data : "Error saving this record. Please try again...");
				},
				error : function(jqXHR, textStatus, errorThrown) { 
					if (jqXHR.responseText)
						StatusMessageHandler.showError(jqXHR.responseText);
				},
			};
			
			if (file_inputs_exist) {
				ajax_options["data"] = getFormDataObjectWithUploadedFiles(ajax_options["data"], row.find("input[type=file]"));
				ajax_options["contentType"] = false;
				ajax_options["processData"] = false;
				ajax_options["cache"] = false;
			}
			
			$.ajax(ajax_options);
		}
		else 
			StatusMessageHandler.showError("Could not get primary keys for this record!");
	}
}

function editRow(elm) {
	elm = $(elm);
	var is_insert = elm.is(".add");
	var row = null;
	var pks = {};
	
	if (!is_insert) {
		row = elm.parent().closest("tr");
		pks = getPKsObj(row);
	}
	
	if (is_insert || !$.isEmptyObject(pks)) {
		//get popup
		var popup = $(".manage_record_popup");
		
		if (!popup[0]) {
			popup = $('<div class="myfancypopup with_iframe_title manage_record_popup"></div>');
			$(document.body).append(popup);
		}
		
		//remove and readd iframe so we don't see the previous loaded html
		popup.children("iframe").remove(); 
		popup.prepend('<iframe></iframe>');
		
		//prepare url
		var url = manage_record_url;
		url += (url.indexOf("?") == -1 ? "?" : "&") + "popup=1"
		
		if (is_insert)
			url += "&action=insert";
		else
			for (var k in pks)
				url += "&conditions[" + k + "]=" + pks[k];
		
		//open popup
		MyFancyPopup.init({
			elementToShow: popup,
			parentElement: document,
			type: "iframe",
			url: url,
			targetRow: is_insert ? null : row[0],
			beforeClose: function() {
				var win = popup.children("iframe")[0].contentWindow;
				
				if (typeof win.isRecordChanged == "function") {
					if (win.isRecordChanged())
						return confirm("If you proceed your changes won't be saved. Do you wish to continue?");
				}
				
				return true;
			},
		});
		MyFancyPopup.showPopup();
	}
	else 
		StatusMessageHandler.showError("Could not get primary keys for this record!");
}

//This method will be call from popup url
function addCurrentRow(attributes) {
	var tbody = $(".manage_records table tbody");
	
	//prepare new index
	var inputs = tbody.find("td.select_item input[type=checkbox]");
	var index = 0;
	
	$.each(inputs, function(idx, input) {
		var idx = parseInt( $(input).val() );
		
		if (idx > index)
			index = idx;
	});
	
	//prepare html
	var html = $(new_row_html.replace(/#idx#/g, index + 1));
	
	setAttributesObj(html, attributes);
	setLinksAttributesObj(html, attributes);
	setPKsObj(html, attributes);
	
	tbody.append(html);
}

//This method will be call from popup url
function updateCurrentRow(pks) {
	var row = $(MyFancyPopup.settings.targetRow);
	
	if (!row[0])
		row = $(getRowFromPks(pks));
	
	if (row[0] && !$.isEmptyObject(pks)) {
		StatusMessageHandler.showMessage("Updating changed record...", "", "bottom_messages", 1500);
		
		//get the data for the changed record
		$.ajax({
			type : "post",
			url : manage_record_action_url,
			data : {"action" : "get", "conditions" : pks},
			dataType : "json",
			success : function(data, textStatus, jqXHR) {
				StatusMessageHandler.removeLastShownMessage("info");
				
				if (data && $.isPlainObject(data)) { //update record with new data
					//update attributes
					setAttributesObj(row, data);
					setLinksAttributesObj(row, data);
					
					//update pks in case are different
					setPKsObj(row, pks);
				}
				else 
					alert("Error: Could not get record data. Please refresh page."); //must be an alert so it can appear over the popup.
			},
			error : function(jqXHR, textStatus, errorThrown) { 
				StatusMessageHandler.removeLastShownMessage("info");
				
				if (jqXHR.responseText)
					StatusMessageHandler.showError(jqXHR.responseText);
			},
		});
	}
}

//This method will be call from popup url
function deleteCurrentRow() {
	var row = $(MyFancyPopup.settings.targetRow);
	
	if (row[0])
		row.remove();
}

function getRowFromPks(pks) {
	if (!$.isEmptyObject(pks)) {
		var tds = $(".manage_records table tbody tr td.select_item");
		
		for (var i = 0, t1 = tds.length; i < t1; i++) {
			var td = $(tds[i]);
			
			//get pks for this td
			var inputs = td.find("input[type=hidden]");
			var td_pks = {};
			var td_pks_length = 0;
			
			for (var j = 0, t2 = inputs.length; j < t2; j++) {
				var input = inputs[j];
				var name = input.name;
				
				if (name) {
					var m = name.match(/selected_pks\[[0-9]+\]\[([^\]]+)\]/);
					
					if (m && m[1]) {
						td_pks[ m[1] ] = input.value;
						td_pks_length++;
					}
				}
			}
			
			//check if the name
			var exists = true;
			var pks_length = 0;
			
			for (var k in pks) {
				var v = pks[k];
				
				if (v != td_pks[k]) {
					exists = false;
					break;
				}
				
				pks_length++;
			}
			
			if (pks_length != td_pks_length)
				exists = false;
			
			if (exists)
				return td.parent().closest("tr")[0];
		}
	}
	
	return null;
}

function updateNewFileAttributes(elm, pks) {
	$.ajax({
		type : "post",
		url : manage_record_action_url,
		data : {"action" : "get", "conditions" : pks},
		dataType : "json",
		success : function(data, textStatus, jqXHR) {
			StatusMessageHandler.removeLastShownMessage("info");
			
			if (data && $.isPlainObject(data)) { //update record with new data
				var row = elm.parent().closest("tr");
				var file_contents = row.find(".file_content");
				
				$.each(file_contents, function(idx, file_content) {
					var td = $(file_content).parent().closest("td");
					var field_name = td.attr("attr_name");
					var field_value = field_name && data.hasOwnProperty(field_name) ? data[field_name] : "";
					$(file_content).html(field_value);
				});
			}
			else 
				StatusMessageHandler.showError("Error: Could not get record data. Please refresh this page.");
		},
		error : function(jqXHR, textStatus, errorThrown) { 
			StatusMessageHandler.removeLastShownMessage("info");
			
			if (jqXHR.responseText)
				StatusMessageHandler.showError(jqXHR.responseText);
		},
	});
}

function fileInputsContainsNewFiles(file_inputs) {
	var exists = false;
	
	$.each(file_inputs, function(idx, input) {
		if (input.files && input.files.length > 0) {
			exists = true;
			return false;
		}
	});
	
	return exists;
}

function getFormDataObjectWithUploadedFiles(data, file_inputs) {
	var formData = convertValueToFormData(data);
	
	$.each(file_inputs, function(idx, input) {
		var td = $(input).parent().closest("td");
		var field_name = td.attr("attr_name");
		
		if (input.files)
			for (var i = 0; i < input.files.length; i++) 
				formData.append(field_name, input.files[i]);
	});
	
	return formData
}

function convertValueToFormData(val, formData, namespace) {
	if (!formData)
		formData = new FormData();
	
	var is_plain_object = $.isPlainObject(val) && !(val instanceof File);
	
	if (namespace || is_plain_object) {
		if (val instanceof Date)
			formData.append(namespace, val.toISOString());
		else if (val instanceof Array) {
			for (var i = 0; i < val.length; i++)
				convertValueToFormData(val[i], formData, namespace + '[' + i + ']');
		}
		else if (is_plain_object) {
			for (var property_name in val)
				if (val.hasOwnProperty(property_name))
					convertValueToFormData(val[property_name], formData, namespace ? namespace + '[' + property_name + ']' : property_name);
		}
		else if (val instanceof File) {
			if (val.name)
				formData.append(namespace, val, val.name);
			else
				formData.append(namespace, val);
		}
		else if (typeof val !== 'undefined' || val == null)
			formData.append(namespace, val);
		else
			formData.append(namespace, val.toString());
	}
	
	return formData;
}

function getPKInputColumnName(input) {
	var name = input.attr("name");
	var field_name = name.replace(/selected_pks\[[0-9]+\]\[/, "");
	field_name = field_name.substr(0, field_name.length - 1);
	
	return field_name;
}

function setPKsObj(row, pks) {
	//update pks in case are different
	if (row && $.isPlainObject(pks))
		row.children("td.select_item").find("input[type=hidden]").each(function(idx, input) {
			input = $(input);
			var field_name = getPKInputColumnName(input);
			
			if (pks.hasOwnProperty(field_name) && pks[field_name] != input.val())
				input.val(pks[field_name]);
		});
}

function getPKsObj(row) {
	row = $(row);
	var inputs = row.children("td.select_item").find("input[type=hidden]");
	var obj = {};
	
	$.each(inputs, function(idx, input) {
		var input = $(input);
		var field_name = getPKInputColumnName(input);
		
		if (field_name) {
			var v = input.val();
			v = typeof v == "undefined" ? "" : v;
			
			obj[field_name] = v;
		}
	});
	
	return obj;
}

function setLinksAttributesObj(row, attributes) {
	if (row && $.isPlainObject(attributes) && (table_fks || table_extra_fks)) {
		var links = row.find(".fks a");
		
		$.each(links, function(idx, link) {
			link = $(link);
			var href = link.attr("href");
			var fk_table = link.attr("fk_table");
			var fk_attributes = $.isPlainObject(table_fks) ? table_fks[fk_table] : {};
			
			if (fk_attributes) {
				var extra_fk_attributes = $.isPlainObject(table_extra_fks) ? table_extra_fks[fk_table] : {};
				
				for (var fk_attribute in extra_fk_attributes)
					fk_attributes[fk_attribute] = extra_fk_attributes[fk_attribute];
				
				for (var fk_attribute in fk_attributes) {
					var attribute = fk_attributes[fk_attribute];
					var expression = new RegExp("&conditions\\[" + fk_attribute + "\\]=[^&]*", "gi"); 
					
					if (expression.test(href)) {
						href = href.replace(expression, "&conditions[" + fk_attribute + "]=" + attributes[attribute]);
						link.attr("href", href);
					}
				}
			}
		});
	}
}

function setAttributesObj(row, attributes) {
	//update attributes
	if (row && $.isPlainObject(attributes)) {
		var tds = row.children("td:not(.select_item):not(.actions):not(.fks)");
		
		$.each(tds, function(idx, td) {
			var td = $(td);
			var field_name = td.attr("attr_name");
			
			if (field_name && attributes.hasOwnProperty(field_name))
				setAttributeValue(td, attributes[field_name]);
		});
	}
}

function getAttributesObj(row) {
	row = $(row);
	var inputs = row.children("td:not(.select_item):not(.actions):not(.fks)").find("input, select, textarea");
	var obj = {};
	
	$.each(inputs, function(idx, input) {
		var input = $(input);
		var td = input.parent().closest("td");
		var field_name = td.attr("attr_name");
		
		if (field_name && input[0].type != "file") //Do not include inputs with type=file
			obj[field_name] = getAttributeValue(td);
	});
	
	return obj;
}

function getAttributeValue(td) {
	var v = "";
	var input = td.find("input, select, textarea").first();
	
	if (input[0]) {
		if (input.attr("type") == "checkbox" || input.attr("type") == "radio") {
			if (input.is(":checked"))
				v = input[0].hasAttribute("value") ? input.val() : 1;
			else
				v = 0; //This should be empty and not null otherwise it messes the values from the checkboxes/radiobuttons when the field name is an array with numeric keys.
		}
		else {
			v = input.val();
			
			if (input.is("select") && v == null) //v can be null if the select field does not have any options.
				v = "";
		}
		
		v = typeof v == "undefined" ? "" : v; //if a field doesn't have the value attribute then the v can be undefined
	}
	else if (td[0].hasAttribute("attr_value"))
		v = td.attr("attr_value");
	else
		v = td.html();
	
	return v;
}

function setAttributeValue(td, v) {
	var input = td.find("input, textarea, select").first();
	
	if (input[0]) {
		if (input.attr("type") == "checkbox" || input.attr("type") == "radio") {
			if (input.attr("value") == v)
				input.attr("checked", "checked").prop("checked", true);
			else
				input.removeAttr("checked").prop("checked", false);
		}
		else {
			input.val(v);
			
			if (input.is("select"))
				td.attr("attr_value", v);
		}
	}
	else {
		var attr_name = td.attr("attr_name");
		var field_html_type = table_fields_types[attr_name];
		
		if ($.isPlainObject(field_html_type) && $.isPlainObject(field_html_type["options"]) && field_html_type["options"].hasOwnProperty(v)) {
			td.html(field_html_type["options"][v]);
			td.attr("attr_value", v);
		}
		else if (field_html_type == "file")
			td.children(".file_content").html(v);
		else
			td.html(v);
	}
}

function prepareRecordFields(item) {
	//Only add date plugin if browser doesn't have a default date field or if Firefox (bc the Modernizr does not work properly in the new firefox browsers)
	var is_firefox = navigator.userAgent.toLowerCase().indexOf('firefox') != -1;
	var is_chrome = navigator.userAgent.toLowerCase().indexOf('chrome') != -1;
	
	if (typeof Modernizr == "undefined" || !Modernizr || !Modernizr.inputtypes.date || is_firefox) { 
		if (typeof item.datetimepicker != "undefined") {
			item.find('input[type="datetime"]').each(function(idx, input) {
				input = $(input);
				
				input.datetimepicker({
					controlType: 'select',
					oneLine: true,
					minDate: new Date(1970, 01, 01), //add min date to 1970-01-01, otherwise mysql will fail
					showSecond: true,
			   		dateFormat: input.attr('dateFormat') ? input.attr('dateFormat') : 'yy-mm-dd',
					timeFormat: input.attr('timeFormat') ? input.attr('timeFormat') : 'HH:mm:ss',
				});
			});
			
			item.find('input[type="date"]').each(function(idx, input) {
				input = $(input);
				
				input.datepicker({
					minDate: new Date(1970, 01, 01), //add min date to 1970-01-01, otherwise mysql will fail
					dateFormat: item.attr('dateFormat') ? item.attr('dateFormat') : 'yy-mm-dd',
				});
			});
			
			item.find('input[type="time"]').each(function(idx, input) {
				input = $(input);
				
				input.datetimepicker({
					showSecond: true,
			   		dateFormat: '',
					timeFormat: item.attr('timeFormat') ? item.attr('timeFormat') : 'HH:mm:ss'
				});
			});
		}
	}
	else if (typeof Modernizr != "undefined" && Modernizr && Modernizr.inputtypes.date && is_chrome) { 
		if (typeof item.datetimepicker != "undefined") {
			item.find('input[type="datetime"]').each(function(idx, input) {
				input = $(input);

				input.datetimepicker({
					controlType: 'select',
					oneLine: true,
					minDate: new Date(1970, 01, 01), //add min date to 1970-01-01, otherwise mysql will fail
					showSecond: true,
					dateFormat: input.attr('dateFormat') ? input.attr('dateFormat') : 'yy-mm-dd',
					timeFormat: input.attr('timeFormat') ? input.attr('timeFormat') : 'HH:mm:ss',
				});
			});
		}
	}
	else //Replace yyy-mm-dd hh:ii by yyy-mm-ddThh:ii if input is datetime-local
		item.find('input[type="datetime-local"]').each(function(idx, input) {
			input = $(input);
			var v = input.attr("value");
			 
			if (v && (/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2}) ([0-9]{1,2}):([0-9]{1,2})(:([0-9]{1,2}))?$/).test(v))
				input.val( v.replace(' ', 'T') );
		});
}
