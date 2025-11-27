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

$(function () {
	var iframe = $(".test_project > iframe");
		
	iframe.load(function() {
		var style = '<style>' +
'::-webkit-scrollbar {' +
'	width:10px;' +
'	height:10px;' +
'	background:transparent;' +
'}' +
//track
'::-webkit-scrollbar-track {' +
'    */-webkit-border-radius:5px;' +
'    border-radius:5px;' +
'    -webkit-box-shadow:inset 0 0 6px rgba(0,0,0, 0);*/' +
'    background-color:transparent;' +
'}' +
//Handle
'::-webkit-scrollbar-thumb {' +
'	background:#83889E;' +
'	/*-webkit-box-shadow:inset 0 0 6px rgba(250,250,250,0.8);*/' +
	
'	background-clip:padding-box;' +
'	border:2px solid transparent;' +
'	border-radius:9999px;' +
'	/*-webkit-box-shadow:0 0px 1px rgba(250,250,250,0.8);*/' +
'}' +
'::-webkit-scrollbar-thumb:window-inactive {' +
'	/*background:rgba(0,0,0,0.2);*/ ' +
'}' +
'</style>';
		var iframe_head = this.contentWindow.document.head;
		$(iframe_head).prepend(style);
	});
});

function addVar(elm, type) {
	var tbody = $(elm).parent().closest("table").children("tbody");
	tbody.children(".no_vars").hide();
	var index = getListNewIndex(tbody);
	
	var row = vars_html.replace(/#type#/g, type).replace(/#index#/g, index).replace(/#name#/g, "").replace(/#value#/g, "");
	row = $(row);
	tbody.append(row);
	
	return row;
}

function removeVar(elm) {
	var tr = $(elm).parent().closest("tr");
	var tbody = tr.parent();
	
	tr.remove();
	
	if (tbody.children().length == 1)
		tbody.children(".no_vars").show();
}

function toggleSettings(elm) {
	var icon = $(elm).children(".icon");
	
	$(".test_project").toggleClass("hide_settings");
	
	if (icon.hasClass("maximize"))
		icon.removeClass("maximize").addClass("minimize");
	else
		icon.removeClass("minimize").addClass("maximize");
}
