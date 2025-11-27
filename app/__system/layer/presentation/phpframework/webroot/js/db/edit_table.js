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

var editors_inited = false;
var step = false;

$(function() {
	var edit_table = $(".edit_table");
	
	if (edit_table.children(".table_settings").length > 0)
		edit_table.accordion({
			//collapsible: true,
	 		disabled: true,
	 		active: step,
	 		heightStyle: "fill",
		});
	
	//set auto convert to true for better user experience
	auto_convert = true;
	
	//init taskFlowChartObj.StatusMessage, otherwise in case any error, we get get a javascript error
	myWFObj.getTaskFlowChart().StatusMessage.init();
	
	//load task
	var properties_html_elm = edit_table.find(" > .table_settings > .selected_task_properties");
	var task_html_elm = properties_html_elm.children(".db_table_task_html");
	
	if (!$.isPlainObject(task_property_values))
		task_property_values = {};
	
	DBTableTaskPropertyObj.onLoadTaskProperties(properties_html_elm, null, task_property_values);
	
	//load old_name
	loadTableTaskOldNameProperty(task_html_elm, task_property_values);
	
	//set table name
	task_html_elm.find(".table_name input").val(task_property_values["table_name"]);
	
	//convert to list view
	DBTableTaskPropertyObj.convertTableToList( task_html_elm.find("#advanced_ui .attributes .icon.switch")[0] );
	
	//prepare step 1
	if (step == 1) {
		if (!with_advanced_options) {
			$("#error_message").hide();
			
			edit_table.accordion("option", "active", 0);
			edit_table.accordion("refresh"); //refreshes the height of the table_settings
			edit_table.find(".table_sql_statements .save_button .execute").attr("do_not_confirm", 1).trigger("click");
		}
		else {
			editors_inited = true;
			edit_table.find(".table_sql_statements .sql_statement > textarea.editor").each(function(idx, textarea) {
				createSqlEditor(textarea);
			});
		}
	}
});

function toggleAdvancedOptions() {
	var main_obj = $(".edit_table");
	var top_bar = $(".top_bar");
	var toggle_advanced_options = top_bar.find("li.toggle_advanced_options");
	var input = toggle_advanced_options.find("input");
	var span = toggle_advanced_options.find("span");
	
	toggle_advanced_options.toggleClass("active");
	main_obj.toggleClass("with_advanced_options");
	top_bar.toggleClass("with_advanced_options");
	
	if (toggle_advanced_options.hasClass("active")) {
		input.attr("checked", "checked").prop("checked", true);
		span.html("Hide Advanced Features");
	}
	else {
		input.removeAttr("checked").prop("checked", false);
		span.html("Show Advanced Features");
	}
	
	//show simple ui
	if (!main_obj.hasClass("with_advanced_options") && main_obj.find(" > .table_settings .selected_task_properties .db_table_task_html.advanced_ui_shown"))
		main_obj.find(" > .table_settings .selected_task_properties .db_table_task_html.advanced_ui_shown > ul > li:nth-child(1) > a").trigger("click");
}

function loadTableTaskOldNameProperty(task_html_elm, task_property_values) {
	if (task_property_values && task_property_values.table_attr_names) {
		var table_inputs = task_html_elm.find(".table_attrs .table_attr_name input");
		var simple_inputs = task_html_elm.find(".simple_attributes > ul > li .simple_attr_name");
		
		$.each(task_property_values.table_attr_names, function(i, table_attr_name) {
			var old_name = task_property_values.table_attr_old_names && task_property_values.table_attr_old_names[i] ? task_property_values.table_attr_old_names[i] : table_attr_name;
			var table_input = table_inputs[i];
			var simple_input = simple_inputs[i];
			
			if (table_input)
				table_input.setAttribute("old_name", old_name);
			
			if (simple_input)
				simple_input.setAttribute("old_name", old_name);
		});
	}
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

function onSaveButton(elm) {
	var edit_table = $(".edit_table");
	var table_settings = edit_table.children(".table_settings");
	var properties_html_elm = table_settings.children(".selected_task_properties");
	
	//prepare task_property_values
	var task_property_values = {};
	var fields = DBTableTaskPropertyObj.getParsedTaskPropertyFields(properties_html_elm, null);
		
	if (fields) {
		var query_string = taskFlowChartObj.Property.getPropertiesQueryStringFromHtmlElm(properties_html_elm, "task_property_field");
		var table_attr_old_names = [];
		
		try {
			var task_html_elm = properties_html_elm.children(".db_table_task_html");
			task_property_values["table_name"] = task_html_elm.find(".table_name input").val();
			
			parse_str(query_string, task_property_values);
			
			var inputs = task_html_elm.find(".table_attr_name input");
			
			$.each(inputs, function(idx, input) {
				var old_name = input.hasAttribute("old_name") ? input.getAttribute("old_name") : "";
				table_attr_old_names.push(old_name);
			});
		}
		catch(e) {
			//alert(e);
			if (console && console.log) {
				console.log(e);
				console.log("Error executing parse_str function with query_string: " + query_string);
			}
		}
	}
	else {
		var error_exists = myWFObj.getTaskFlowChart().StatusMessage.getMessageHtmlObj().children(".error").last().text().length > 0;
		
		if (error_exists) {
			var clone = myWFObj.getTaskFlowChart().StatusMessage.getMessageHtmlObj().children(".error").last().clone();
			clone.find(".close_message").remove();
			var error = clone.html();
			
			clone.remove();
			StatusMessageHandler.showError(error);
		}
		
		return false;
	}
	
	//prepare data
	var data = {};
	
	for (var k in task_property_values)
		if (k.indexOf("table_attr_") == -1)
			data[k] = task_property_values[k];
	
	if (task_property_values && task_property_values.table_attr_names) {
		data["attributes"] = [];
		
		$.each(task_property_values.table_attr_names, function(i, table_attr_name) {
			var attribute_data = {};
			
			for (var j = 0; j < DBTableTaskPropertyObj.task_property_values_table_attr_prop_names.length; j++) {
				var prop_name = DBTableTaskPropertyObj.task_property_values_table_attr_prop_names[j];
				attribute_data[prop_name] = task_property_values["table_attr_" + prop_name + "s"][i];
			}
			
			attribute_data["old_name"] = table_attr_old_names[i];
			
			data["attributes"].push(attribute_data);
		});
	}
	
	var form = table_settings.children("form");
	form.children("textarea").val( JSON.stringify(data) );
	form.children("input[name=with_advanced_options]").val( edit_table.hasClass("with_advanced_options") ? 1 : 0);
	
	//show loading
	MyFancyPopup.init();
	MyFancyPopup.showLoading();
	
	return true;
}

function onDeleteButton(elm) {
	if (confirm("This table will be deleted and all it's data will be lost forever!\nDo you wish to continue?") && confirm("Do you really wish to continue?\nThere is no rollback for this action...")) {
		var edit_table = $(".edit_table");
		var table_settings = edit_table.children(".table_settings");
		var form = table_settings.children("form");
		form.children("input[name=with_advanced_options]").val( edit_table.hasClass("with_advanced_options") ? 1 : 0);
		
		return true;
	}
	return false;
}

function onExecuteButton(elm) {
	if (elm.getAttribute("do_not_confirm") || confirm("You are about to execute this sql on the DB.\nDo you really wish to continue?")) {
		var edit_table = $(".edit_table");
		var table_sql_statements = edit_table.children(".table_sql_statements");
		var form = table_sql_statements.children("form");
		form.children("input[name=with_advanced_options]").val( edit_table.hasClass("with_advanced_options") ? 1 : 0);
		
		//show loading
		MyFancyPopup.init();
		MyFancyPopup.showLoading();
		
		return true;
	}
	return false;
}

function onBackButton(elm, new_step) {
	var edit_table = $(elm).parent().closest(".edit_table");
	edit_table.accordion("option", "active", new_step);
	
	if (new_step == 1 && !editors_inited) {
		edit_table.find(".table_sql_statements .sql_statement > textarea.editor").each(function(idx, textarea) {
			createSqlEditor(textarea);
		});
	}
	
	step = new_step;
	
	return false;
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

function goToTablePopup(url, originalEvent) {
	if (url) {
		//check if ctrlKey is pressed and if yes, open in a new window
		originalEvent = originalEvent || window.event;
		
		if (originalEvent && (originalEvent.ctrlKey || originalEvent.keyCode == 65)) {
			url = url.replace(/(\?|&)popup=1/i, ""); //remove popup parameter from url
			
			var rand = Math.random() * 10000;
			var tab = "tab" + rand;
			var win = window.open(url, tab);
			
			if(win) //Browser has allowed it to be opened
				win.focus();
			
			return false;
		}
		
		//prepare popup
		var popup = $(".go_to_table_popup");
		
		if (!popup[0]) {
			popup = $('<div class="myfancypopup go_to_table_popup with_iframe_title"></div>');
			$(document.body).append(popup);
		}
		
		url += (url.indexOf("?") != -1 ? "&" : "?") + "popup=1";
		
		popup.html('<iframe src="' + url + '"></iframe>');
		
		MyFancyPopup.init({
			elementToShow: popup,
			//parentElement: document,
		});
		MyFancyPopup.showPopup();
	}
	
	return false;
}
