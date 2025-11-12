/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

$(function() {
	var edit_single_query = $(".edit_single_query");
	
	if (edit_single_query[0]) {
		//init auto save
		addAutoSaveMenu(".top_bar li.sub_menu li.save", "onToggleQueryAutoSave");
		addAutoConvertMenu(".top_bar li.sub_menu li.save", "onToggleQueryAutoConvert");
		enableAutoSave(onToggleQueryAutoSave);
		
		if (is_covertable_sql)
			enableAutoConvert(onToggleQueryAutoConvert);
		else
			disableAutoConvert(onToggleQueryAutoSave);
		
		initAutoSave(".top_bar li.sub_menu li.save a");
		
		//init ui
		var relationship = edit_single_query.find(".data_access_obj .relationships .relationship");
		var query = relationship.find(".query");
		var rand_number = query.attr("rand_number");
		var select = relationship.find(".rel_type select");
		select.attr("onChange", "updateSingleQueryRelationshipType(this, " + rand_number + ")");
		
		updateSingleQueryRelationshipType(select[0], rand_number);
		
		relationship.css("display", "block");
		
		//init main settings panel
		initMainSettingsPanel();
		
		//load sql
		var a = $(".query_tabs .query_sql_tab a").first();
		a.attr("not_create_sql_from_ui", 1);
		a.click();
		a.removeAttr("not_create_sql_from_ui");
		
		//update design with query
		var a = $(".query_tabs .query_design_tab a").first();
		a.attr("do_not_confirm", 1);
		a.click();
		a.removeAttr("do_not_confirm");
		
		//hide advanced settings
		var advanced_query_settings = query.find(".query_settings .advanced_query_settings");
		showOrHideExtraQuerySettings(advanced_query_settings[0], rand_number);
		
		//DEPRECATED - this is already done in the updateSingleQueryRelationshipType method
		//show query properties
		//if (relationship.hasClass("query_select"))
		//	showOrHideSingleQuerySettings($(".top_bar .toggle_settings a")[0], rand_number);
		
		//set sync_ui_settings_with_sql to 1 so it updates automatically the sql query on every change on UI.
		eval('var WF = taskFlowChartObj_' + rand_number + ';');
		var main_tasks_flow_obj = $("#" + WF.TaskFlow.main_tasks_flow_obj_id);
		main_tasks_flow_obj.attr("sync_ui_settings_with_sql", is_covertable_sql ? 1 : 0);
		main_tasks_flow_obj.attr("sync_sql_with_ui_settings", is_covertable_sql ? 1 : 0);
		
		//set save function to sql editor
		var query_sql_elm = query.find(".sql_text_area")
		var editor = getQuerySqlEditor(query_sql_elm);
		
		if (editor)
			editor.commands.addCommand({
				name: 'saveFile',
				bindKey: {
					win: 'Ctrl-S',
					mac: 'Command-S',
					sender: 'editor|cli'
				},
				exec: function(env, args, request) {
					saveQueryObject(onSuccessSingleQuerySave);
				},
			});
		
		//set window resize events
		$(window).resize(function() {
			WF.getMyFancyPopupObj().updatePopup();
		});
	}
});

function onToggleQueryAutoConvert() {
	onToggleAutoConvert();
	
	var rand_number = $(".edit_single_query .data_access_obj .relationships .relationship .query").attr("rand_number");
	eval('var WF = taskFlowChartObj_' + rand_number + ';');
	var main_tasks_flow_obj = $("#" + WF.TaskFlow.main_tasks_flow_obj_id);
	
	if (auto_convert) {
		main_tasks_flow_obj.attr("sync_ui_settings_with_sql", 1);
		main_tasks_flow_obj.attr("sync_sql_with_ui_settings", 1);
		
		onBlurQuerySqlEditor(rand_number);
	}
	else {
		main_tasks_flow_obj.attr("sync_ui_settings_with_sql", 0);
		main_tasks_flow_obj.attr("sync_sql_with_ui_settings", 0);
	}
}

function updateSingleQueryRelationshipType(elm, rand_number) {
	updateRelationshipType(elm, rand_number);
	
	var rel_type = $(elm).val();
	var menus = $(".edit_single_query > .top_bar ul .select_query");
	
	if (rel_type == "select") {
		menus.addClass("show");
		
		//show query properties if there are hidden but in the menu says that is shown. This should only happen once, when we are creating a new query.
		var settings = $(elm).parent().closest(".relationship").find(" > .query .query_select .query_settings");
		var toggle_settings_input = $(".top_bar .toggle_settings a > input");
		
		if (settings.css("display") == "none" && toggle_settings_input.is(":checked"))
			showOrHideSingleQuerySettings(toggle_settings_input.parent()[0], rand_number);
	}
	else
		menus.removeClass("show");
}

function showOrHideSingleQuerySettings(elm, rand_number) {
	elm = $(elm);
	var input = elm.children("input");
	var span = elm.children("span");
	var relationship = $(".edit_single_query .data_access_obj .relationships .relationship");
	var query = relationship.children(".query");
	var settings = query.find(".query_select .query_settings");
	var other_settings = query.find(".query_insert_update_delete");
	
	if (!relationship.hasClass("query_select")) {
		var aux = settings;
		settings = other_settings;
		other_settings = aux;
	}
	
	if (settings[0]) {
		eval('var WF = taskFlowChartObj_' + rand_number + ';');
		
		if(settings.css("display") == "none") {//show
			input.attr("checked", "checked").prop("checked", true);
			span.html("Hide Query Settings");
			
			settings.slideDown("slow", function() {
				elm.addClass("active");
				query.removeClass("hide_query_settings");
				other_settings.show();
				
				MyFancyPopup.updatePopup();
				WF.getMyFancyPopupObj().updatePopup();
				
				//resize sql editor
				resizeQuerySqlEditor();
			});
		}
		else {//hide
			input.removeAttr("checked").prop("checked", false);
			span.html("Show Query Settings");
			
			settings.slideUp("slow", function() {
				elm.removeClass("active");
				query.addClass("hide_query_settings");
				other_settings.hide();
				
				MyFancyPopup.updatePopup();
				WF.getMyFancyPopupObj().updatePopup();
				
				//resize sql editor
				resizeQuerySqlEditor();
			});
		}
	}
}

function showOrHideSingleQueryUI(elm, rand_number) {
	elm = $(elm);
	var input = elm.children("input");
	var span = elm.children("span");
	var relationship = $(".edit_single_query .data_access_obj .relationships .relationship");
	var query = relationship.children(".query");
	var a = relationship.hasClass("query_select") ? query.find(".query_select .query_ui .taskflowchart .workflow_menu .toggle_ui a")[0] : null;
	
	if (a) {
		var is_shown = elm.hasClass("active");
		
		eval('var WF = taskFlowChartObj_' + rand_number + ';');
		
		if (is_shown) {
			elm.removeClass("active");
			input.removeAttr("checked").prop("checked", false);
			span.html("Show Query Diagram");
		}
		else {
			elm.addClass("active");
			input.attr("checked", "checked").prop("checked", true);
			span.html("Hide Query Diagram");
			query.removeClass("hide_taskflowchart");
			
			MyFancyPopup.updatePopup();
			WF.getMyFancyPopupObj().updatePopup();
			
			//resize sql editor
			resizeQuerySqlEditor();
		}
		
		showOrHideQueryUI(a, rand_number, {
			callback: function() {
				if (is_shown) {
					query.addClass("hide_taskflowchart");
					
					MyFancyPopup.updatePopup();
					WF.getMyFancyPopupObj().updatePopup();
					
					//resize sql editor
					resizeQuerySqlEditor();
				}
			}
		});
	}
}

function resizeQuerySqlEditor() {
	var query_sql_elm = $(".edit_single_query .data_access_obj .relationships .relationship .query .sql_text_area")
	var editor = getQuerySqlEditor(query_sql_elm);
	
	if (editor)
		editor.resize();
}

function onToggleFullScreen(in_full_screen) {
	var query = $(".edit_single_query .data_access_obj .relationships .relationship .query");
	var rand_number = query.attr("rand_number");
	eval('var WF = taskFlowChartObj_' + rand_number + ';');
	
	setTimeout(function() {
		MyFancyPopup.updatePopup();
		WF.getMyFancyPopupObj().updatePopup();
	}, 500);
}

function onChangeIsConvertableSQL(elm) {
	elm = $(elm);
	var edit_single_query = $(".edit_single_query");
	var input = elm.find("input");
	var span = elm.find("span");
	
	edit_single_query.toggleClass("covertable_sql");
	
	var is_covertable_sql = edit_single_query.is(".covertable_sql");
	
	if (is_covertable_sql) {
		input.attr("checked", "checked").prop("checked", true);
		span.html("Disable SQL convertable");
		
		if (elm.attr("previous_auto_convert") == 1) {
			enableAutoConvert(onToggleQueryAutoConvert);
			
			var query = edit_single_query.find(".data_access_obj .relationships .relationship .query");
			query.find(".rel_type select").trigger("change");
			
			var rand_number = query.attr("rand_number");
			onBlurQuerySqlEditor(rand_number);
			
			//REPAINT UI
			repaintQueryTasks(rand_number);
			
			//set z-index of popup if it is active
			eval('var WF = taskFlowChartObj_' + rand_number + ';');
			
			if (WF.getMyFancyPopupObj().isPopupOpened())
				WF.getMyFancyPopupObj().reinitZIndex();
		}
	}
	else {
		input.removeAttr("checked", "checked").prop("checked", false);
		span.html("Enable SQL convertable");
		elm.attr("previous_auto_convert", auto_convert ? 1 : 0);
		disableAutoConvert(onToggleQueryAutoConvert);
	}
	
	//resize sql editor
	resizeQuerySqlEditor();
}
