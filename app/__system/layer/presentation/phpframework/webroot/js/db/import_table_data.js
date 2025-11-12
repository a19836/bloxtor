/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

function submitForm(elm) {
	elm = $(elm);
	var oForm = elm.parent().closest(".top_bar").parent().find(".import_table_data form");
	elm.hide();
	oForm.submit();
}

function addNewColumn(elm) {
	elm = $(elm);
	var table = elm.parent().parent().parent().children("table.columns_attributes_table");
	var thead = table.children("thead").children("tr");
	var tbody = table.children("tbody").children("tr");
	var column_index = thead.children().length;
	
	thead_html = $( column_head_html.replace(/#column_index#/g, column_index + 1) );
	thead.append(thead_html);
	
	var tbody_html = $(column_attributes_html);
	tbody.append(tbody_html);
}

function removeColumn(elm) {
	elm = $(elm);
	var td = elm.parent();
	var thead = td.closest("tr");
	var tbody = thead.parent().parent().children("tbody").children("tr");
	var index = thead.children().index(td);
	
	td.remove();
	$( tbody.children().get(index) ).remove();
	
	//reorder indexes
	var tds = thead.children();
	for (var i = index; i < tds.length; i++)
		$(tds[i]).children(".label").html("Column " + (i + 1));
}

function activateCheckBox(elm) {
	elm = $(elm);
	var p = elm.parent();
	var selector = p.hasClass("insert_ignore") ? ".update_existent" : ".insert_ignore";
	
	if (elm.is(":checked"))
		p.parent().find(" > " + selector + " input").attr("disabled", "disabled");
	else
		p.parent().find(" > " + selector + " input").removeAttr("disabled", "disabled");
}

function onChangeFileType(elm) {
	elm = $(elm);
	var p = elm.parent().parent();
	
	if (elm.val() == "csv" || elm.val() == "xls")
		p.children(".rows_delimiter, .columns_delimiter, .enclosed_by").hide();
	else
		p.children(".rows_delimiter, .columns_delimiter, .enclosed_by").show();
}
