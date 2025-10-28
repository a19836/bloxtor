var daoObjsTree = null;
var includesTree = null;
var query_auto_update_sql_from_ui_func = null;
var query_auto_update_ui_from_sql_func = null;
var saved_user_relationships_obj_id = null;
var on_new_html_callback = null
var sql_editor_completer_keywords = [];
var sql_editor_completer_keywords_strings = [];

$(function () {
	$(window).unbind('beforeunload').bind('beforeunload', function () {
		if (isUserRelationshipsObjChanged()) {
			if (window.parent && window.parent.iframe_overlay)
				window.parent.iframe_overlay.hide();
			
			return "If you proceed your changes won't be saved. Do you wish to continue?";
		}
		
		return null;
	});
	
	//init trees
	includesTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeHbnObjectFromTree,
	});
	includesTree.init("choose_include_from_file_manager");
	
	daoObjsTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
	});
	daoObjsTree.init("choose_dao_object_from_file_manager");
	
	//others
	$(window).resize(function() {
		MyFancyPopup.updatePopup();
	});
	
	$(".relationships_tabs").tabs();
	$(".relationships_tabs").show();
	
	var main_relationships_elms = $(".relationships");
	
	if (main_relationships_elms.length > 0) {
		main_relationships_elms.find(".rels .query").tabs();
		main_relationships_elms.find(".query_settings").tabs();
		main_relationships_elms.find(".query_insert_update_delete").tabs();
		
		DBQueryTaskPropertyObj.show_properties_on_connection_drop = true;
		
		//set saved_user_relationships_obj_id
		saved_user_relationships_obj_id = getUserRelationshipsObjId();
	}
});

function onNewHtmlCallback(elm) {
	if (typeof on_new_html_callback == "function")
		on_new_html_callback(elm);
}

function onToggleQueryAutoSave() {
	onToggleAutoSave();
	
	$(".query").each(function(idx, query) {
		query = $(query);
		var rand_number = query.attr("rand_number");
		
		if (rand_number) {
			eval('var WF = taskFlowChartObj_' + rand_number + ';');
			
			if (auto_save) {
				WF.TaskFile.auto_save = false; //should be false bc the saveObj calls the getCodeForSaving method which already saves the workflow by default, and we don't need 2 saves at the same time.
				WF.Property.auto_save = true;
				$(".taskflowchart").removeClass("auto_save_disabled");
			}
			else {
				WF.TaskFile.auto_save = false;
				WF.Property.auto_save = false;
				$(".taskflowchart").addClass("auto_save_disabled");
			}
			//console.log("WF.Property.auto_save:"+WF.Property.auto_save);
		}
	});
}

/* START: INCLUDES */
function getIncludePathFromFileManager(elm, selector) {
	MyFancyPopup.init({
		elementToShow: $("#choose_include_from_file_manager"),
		parentElement: document,
		
		targetField: $(elm).parent().find(selector)[0],
		updateFunction: updateIncludeFieldFromFileManager
		
	});
	
	MyFancyPopup.showPopup();
}

function updateIncludeFieldFromFileManager(elm) {
	var node = includesTree.getSelectedNodes();
	node = node[0];
	
	var file_path = null, is_xml_file = false;
	
	if (node) {
		var a = $(node).children("a");
		
		if (a) {
			file_path = a.attr("file_path");
			is_xml_obj = a.children("i")[0].className.indexOf("file") != -1 || a.children("i")[0].className.indexOf("import") != -1;
			
			if (file_path && is_xml_obj && file_path.substr(file_path.length - 4) != ".xml") {
				is_xml_obj = false;
			}
		}
	}
	
	if (file_path && is_xml_obj) {
		var fp = prepareIncludeFilePath(file_path);
		
		$(MyFancyPopup.settings.targetField).val(fp);
		$(MyFancyPopup.settings.targetField).parent().children("input.is_include_relative").attr("checked", "checked").prop("checked", true);
		
		MyFancyPopup.hidePopup();
	}
	else {
		alert("Invalid File selection.\nPlease choose a xml file and then click the button.");
	}
}

function prepareIncludeFilePath(file_path) {
	var count = relative_file_path.split("/").length - 1;
	
	var prefix = "";
	for (var i = 0; i < count; i++) {
		prefix += "../";
	}
	
	return prefix + file_path;
}

function addNewInclude(elm) {
	var html_obj = $(new_include_html);
	var fields = $(elm).parent().children(".fields");
	fields.append(html_obj);
	
	onNewHtmlCallback(html_obj);
	
	fields.children(".no_includes").hide();
	
	return html_obj;
}

function removeInclude(elm) {
	var include = $(elm).parent();
	var fields = include.parent();
	include.remove();
	
	if (fields.children(".include").length == 0)
		fields.children(".no_includes").show();
}

function removeHbnObjectFromTree(ul, data) {
	$(ul).find("i.file").each(function(idx, elm){
		$(elm).parent().parent().children("ul").remove();
	});
}
/* END: INCLUDES */

/* START: MAPS / CLASSES */
function getParameterClassFromFileManager(elm) {
	MyFancyPopup.init({
		elementToShow: $("#choose_dao_object_from_file_manager"),
		parentElement: document,
		targetField: $(elm).parent().children("input")[0],
		updateFunction: updateMapPHPTypeOrClassFieldFromFileManager
	});
	
	MyFancyPopup.showPopup();
}

function updateMapPHPTypeOrClassFieldFromFileManager(elm) {
	var node = daoObjsTree.getSelectedNodes();
	node = node[0];
	
	var file_path = null, file_name = null, is_obj_type_obj = false;
	
	if (node) {
		var a = $(node).children("a");
		
		if (a) {
			file_path = a.attr("file_path");
			file_name = a.children("label").html();
			is_obj_type_obj = a.children("i")[0].className.indexOf("objtype") != -1;
		}
	}
	
	if (file_path && is_obj_type_obj) {
		file_path = "vendor.dao." + file_path.replace("/", ".").replace(".php", "");
		
		if (MyFancyPopup.settings.targetField.nodeName.toLowerCase() == "select") {
			var optgrp = $(MyFancyPopup.settings.targetField).children("optgroup[label=\'Composite Types\']")[0];
			
			addSelectedFileToSelectField(MyFancyPopup.settings.targetField, file_path, file_name, optgrp);
		
			MyFancyPopup.hidePopup();
		}
		else {
			$(MyFancyPopup.settings.targetField).val(file_path);
		}
		
		MyFancyPopup.hidePopup();
	}
	else {
		alert("Invalid File selection.\nPlease choose an object type file and then click in the button.");
	}
}

function addSelectedFileToSelectField(select_elm, option_value, option_text, append_to_obj) {
	var options = $(select_elm).find("option");
	
	var exists = false;
	for (var i = 0; i < options.length; i++) {
		if ($(options[i]).val() == option_value) {
			exists = true;
			break;
		}
	}
	
	if (!exists) {
		var html = "<option value=\"" + option_value + "\">" + option_text + "</option>";
		
		if (append_to_obj && ( append_to_obj.nodeName.toLowerCase() == "select" || append_to_obj.nodeName.toLowerCase() == "optgroup") ) {
			$(append_to_obj).append(html);
		}
		else {
			$(select_elm).append(html);
		}
	}
	
	$(select_elm).val(option_value);
}

function getResultClassFromFileManager(elm) {
	MyFancyPopup.init({
		elementToShow: $("#choose_dao_object_from_file_manager"),
		parentElement: document,
		
		targetField: $(elm).parent().children("input")[0],
		updateFunction: updateMapPHPTypeOrClassFieldFromFileManager
	});
	
	MyFancyPopup.showPopup();
}

function getAvailableParameterMap(elm, query_type) {
	getAvailableMapIds(elm, "parameter", query_type);
}

function getAvailableResultMap(elm, query_type) {
	getAvailableMapIds(elm, "result", query_type);
}

function getAvailableMapIds(elm, map_type, query_type) {
	var sel = $("#choose_map_id .map select");
	var mt = sel.attr("map_type");
	var rt = sel.attr("query_type");
	
	if (mt && map_type != mt) {
		sel.html("");
	}
	else if (rt && query_type != rt) {
		sel.html("");
	}
	
	sel.attr("map_type", map_type);
	sel.attr("query_type", query_type);
	
	MyFancyPopup.init({
		elementToShow: $("#choose_map_id"),
		parentElement: document,
		onOpen: function() {
			if (!$("#choose_map_id .map select option")[0]) {
				updateAvailableMapsOptions();
			}
		},
		
		targetField: $(elm).parent().children("input")[0],
		updateFunction: updateMapId
	});
	
	MyFancyPopup.showPopup();
}

function updateAvailableMapsOptions() {
	MyFancyPopup.showLoading();
	
	var sel = $("#choose_map_id .map select");
	var map_type = sel.attr("map_type");
	var query_type = sel.attr("query_type");
	var selected_option = $(MyFancyPopup.settings.targetField).val();
	
	var options = [];
	var url = get_available_map_ids_url;
	url += (url.indexOf("?") != -1 ? "" : "?") + "&time=" + (new Date()).getTime();
	
	$.ajax({
		type : "get",
		url : url.replace("#map_type#", map_type).replace("#query_type#", query_type),
		dataType : "json",
		success : function(data, textStatus, jqXHR) {
			if(data && $.isArray(data)) {
				options = data;
			}
		},
		error : function(jqXHR, textStatus, errorThrown) { 
			if (jqXHR.responseText)
				StatusMessageHandler.showError(jqXHR.responseText);
		},
		async : false,
	});
	
	var dms = $(MyFancyPopup.settings.targetField).parent().parent().parent().parent().parent().children("div").children("." + map_type + "s_maps").children("." + map_type + "s").children(".map").children(".map_id").children("input");
	for (var i = 0; i < dms.length; i++) {
		var v = dms[i].value;
		
		if ($.inArray(v, options) == -1) {
			options.push(v);
		}
	}
	
	var html = "";
	for (var i = 0; i < options.length; i++) {
		html += "<option>" + options[i] + "</option>";
	}
	
	sel.html(html);
	sel.val(selected_option);
	
	MyFancyPopup.hideLoading();
}

function updateMapId(elm) {
	var map_id = $(elm).parent().parent().find(".map").children("select").val();
	map_id = map_id ? map_id : "";
	
	$(MyFancyPopup.settings.targetField).val(map_id);
	
	MyFancyPopup.hidePopup();
}

function addNewParameter(elm) {
	var html_obj = $(new_parameter_html);
	$(elm).parent().parent().parent().parent().children(".fields").append(html_obj);
	
	onNewHtmlCallback(html_obj);
	
	return html_obj;
}

function addNewResult(elm) {
	var html_obj = $(new_result_html);
	$(elm).parent().parent().parent().parent().children(".fields").append(html_obj);
	
	onNewHtmlCallback(html_obj);
	
	return html_obj;
}

function addParameterMap(elm) {
	var html_obj = $(new_parameter_map_html);
	$(elm).parent().children(".parameters").append(html_obj);
	
	onNewHtmlCallback(html_obj);
	
	return html_obj;
}

function addResultMap(elm) {
	var html_obj = $(new_result_map_html);
	$(elm).parent().children(".results").append(html_obj);
	
	onNewHtmlCallback(html_obj);
	
	return html_obj;
}

function geMapPHPTypeFromFileManager(elm, selector) {
	MyFancyPopup.init({
		elementToShow: $("#choose_dao_object_from_file_manager"),
		parentElement: document,
		
		targetField: $(elm).parent().children(selector)[0],
		updateFunction: updateMapPHPTypeOrClassFieldFromFileManager
	});
	
	MyFancyPopup.showPopup();
}

function createParameterOrResultMapAutomatically(elm, type) {
	var popup = $("#choose_db_table_or_attribute");
	
	popup.hide(); //This popup is shared with other actions so we must hide it first otherwise the user experience will be weird bc we will see the popup changing with the new changes.
	
	popup.find(".db_attribute").hide();
	
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		
		targetField: $(elm).parent().children("table").children(".fields")[0],
		hideChooseAttributesField: true,
		mapType: type,
		updateFunction: updateResultParameterMapFields
	});
	
	MyFancyPopup.showPopup();
}

function updateResultParameterMapFields(elm) {
	var p = $(elm).parent().parent();
	var db_broker = p.find(".db_broker select").val();
	var db_driver = p.find(".db_driver select").val();
	var type = p.find(".type select").val();
	var db_table = p.find(".db_table select").val();
	var map_type = MyFancyPopup.settings.mapType;
	
	if (db_broker && db_driver && type && db_table && map_type) {
		MyFancyPopup.showLoading();
		
		var fields_elm = MyFancyPopup.settings.targetField;
		var url = get_map_fields_url;
		url += (url.indexOf("?") != -1 ? "" : "?") + "&time=" + (new Date()).getTime();
		
		$.ajax({
			type : "post",
			url : url,
			data : {"db_broker" : db_broker, "db_driver" : db_driver, "type" : type, "db_table" : db_table, "map_type": map_type},
			dataType : "json",
			success : function(data, textStatus, jqXHR) {
				//console.log(data);
				
				if(data) {
					var map = data[map_type + "_map"];
					
					updateResultParameterMapFieldsHtml(map, map_type, fields_elm);
					
					var map_id = $(fields_elm).parent().parent().children(".map_id").children("input");
					if (map_id.val().trim() == "") {
						var id = getParsedName(db_table) + (map_type == "result" ? "ResultMap" : "ParameterMap");
						map_id.val(id);
					}
				}
				
				MyFancyPopup.hidePopup();
			},
			error : function(jqXHR, textStatus, errorThrown) { 
				MyFancyPopup.hideLoading();
				
				if (jqXHR.responseText)
					StatusMessageHandler.showError(jqXHR.responseText);
			},
		});
	}
}

function updateResultParameterMapFieldsHtml(map, map_type, fields_elm) {
	if (map) {
		var map_id = map.id;
		var items = map[map_type];
		
		if (!$.isArray(items)) {
			items = [items];
		}
		
		fields_elm = $(fields_elm);
		var icon_add_map = fields_elm.parent().children(".fields_title").children("tr").children("th").children(".add").first();
		var func = (map_type == "parameter" ? addNewParameter : addNewResult);
		
		for (var i = 0; i < items.length; i++) {
			var item = items[i];
			
			var last_elm = func(icon_add_map[0]);
			
			last_elm.children(".input_name").children("input").val(item.input_name);
			last_elm.children(".output_name").children("input").val(item.output_name);
			
			var input_select = last_elm.children(".input_type").children("select");
			var input_text = getAttributeTypeText(item.input_type);
			var input_optgrp = input_select.children("optgroup[label=\'Composite Types\']");
			addSelectedFileToSelectField(input_select[0], item.input_type, input_text, input_optgrp[0])
			
			var output_select = last_elm.children(".output_type").children("select");
			var output_text = getAttributeTypeText(item.output_type);
			var output_optgrp = output_select.children("optgroup[label=\'Composite Types\']");
			addSelectedFileToSelectField(output_select[0], item.output_type, output_text, output_optgrp[0])
			
			if (item.mandatory == "1" || item.mandatory == "true") {
				last_elm.children(".mandatory").children("input").attr("checked", "checked").prop("checked", true);
			}
			else {
				last_elm.children(".mandatory").children("input").removeAttr("checked").prop("checked", false);
			}
		}
	}
}

function getAttributeTypeText(attr_type) {
	if (attr_type) {
		var pos = attr_type.lastIndexOf(".");
		if (pos != -1) {
			attr_type = attr_type.substr(pos + 1);
			
			pos = attr_type.indexOf("(");
			if (pos != -1) {
				attr_type = attr_type.substr(pos + 1);
				
				pos = attr_type.indexOf(")");
				if (pos != -1) {
					attr_type = attr_type.substr(0, pos);
				}
			}
		}
	}
	
	return attr_type;
}

function validateMapId(elm, map_type) {
	var icon_add_map;
	
	var p = $(elm).parent().parent().parent().parent();
	if (p[0].id == "tabs-2" || p[0].id == "tabs-3") {
		icon_add_map = p.parent().children(".hbn_obj_relationships").children(".relationships").children(".relationships_tabs").children("div").children("." + map_type + "s_maps").children(".add");
	}
	else if (p.hasClass("results_maps") || p.hasClass("parameters_maps")) {
		icon_add_map = p.children(".add");
	}
	
	if (icon_add_map) {
		var existent_map_ids = getExistentMapIds(icon_add_map, map_type);
		
		//CHECKING IF EXISTS ANY REPEATED IDS
		var str = "";
		for (var i = 0; i < existent_map_ids.length; i++) {
			var a = existent_map_ids[i];
			
			for (var j = i + 1; j < existent_map_ids.length; j++) {
				var b = existent_map_ids[j];
				
				if (a == b) {
					str += "\n- " + a;
				}
			}
		}
		
		if (str != "") {
			alert("We detected some repeated " + map_type + " map ids.\nPlease check and change the following ids so they can be unique please:" + str);
		}
	}
}
/* END: MAPS / CLASSES */

/* START: CHOOSE DB TABLE OR ATTRIBUTE */
function updateDBDrivers(elm, do_not_sync) {
	var db_broker = $(elm).val();
	var p = $(elm).parent().parent();
	var db_driver_elm = p.children(".db_driver");
	var select = db_driver_elm.children("select");
	var chosen_db_driver = select.val();
	
	select.html("");
	
	if (db_broker && db_brokers_drivers_tables_attributes[db_broker]) {
		var html = "<option></option>";
		for (var db_driver in db_brokers_drivers_tables_attributes[db_broker]) 
			html += "<option>" + db_driver + "</option>";
		
		if (!chosen_db_driver || !db_brokers_drivers_tables_attributes[db_broker].hasOwnProperty(chosen_db_driver)) //if no db driver selected before or doesn't exists in db_brokers_drivers_tables_attributes[db_broker], set it to default db driver
			chosen_db_driver = default_db_driver;
		
		select.html(html);
		select.val(chosen_db_driver);
		
		db_driver_elm.show();
	}
	
	var rand_number = p.parent().closest(".query").attr("rand_number");
	updateDBTables(select[0], rand_number, do_not_sync);
}

function updateDBTables(elm, rand_number, do_not_sync) {
	var p = $(elm).parent().parent();
	var db_broker = p.find(".db_broker select").val();
	var db_driver = p.find(".db_driver select").val();
	var type = p.find(".type select").val();
	var db_table_elm = p.children(".db_table");
	var select = db_table_elm.children("select");
	var chosen_table = select.val();
	
	select.html("");
	
	if (db_broker && db_driver && type) {
		var MyFP = MyFancyPopup;
		if (rand_number) {
			eval('var WF = taskFlowChartObj_' + rand_number + ';');
			MyFP = WF.getMyFancyPopupObj();
		}
		
		MyFP.showLoading();
		
		var db_tables = getDBTables(db_broker, db_driver, type);
		
		var html = "<option></option>";
		
		for (var db_table in db_tables)
			html += "<option>" + db_table + "</option>";
		
		if (!chosen_table || !db_tables || !db_tables.hasOwnProperty(chosen_table))
			chosen_table = default_db_table; //if no table selected before or doesn't exists in db_tables, set it to default table
		
		select.html(html);
		select.val(chosen_table);
		
		db_table_elm.show();
		
		//update editor completer db_tables
		$.each(db_tables, function(db_table, db_attributes) {
			if ($.inArray(db_table, sql_editor_completer_keywords_strings) == -1) {
				sql_editor_completer_keywords_strings.push(db_table);
				sql_editor_completer_keywords.push({
					caption: db_table, 
					value: db_table, 
					meta: "table",
					score: 11
				});
			}
		});
		
		MyFP.hideLoading();
	}
	
	updateDBAttributes(select[0], rand_number);
}

function refreshDBTables(elm) {
	var p = $(elm).parent().parent();
	var db_broker = p.find(".db_broker select").val();
	var db_driver = p.find(".db_driver select").val();
	var type = p.find(".type select").val();
	
	if (!db_broker)
		StatusMessageHandler.showMessage("DB Broker cannot be undefined!", "", "bottom_messages", 2000);
	else if (!db_driver)
		StatusMessageHandler.showMessage("DB Driver cannot be undefined!", "", "bottom_messages", 2000);
	else if (!type)
		StatusMessageHandler.showMessage("DB Type cannot be undefined!", "", "bottom_messages", 2000);
	else {
		var select_with_trigger = p.find(".db_driver select[onChange], .type select[onChange]").first();
		
		if (select_with_trigger[0]) {
			if (db_brokers_drivers_tables_attributes[db_broker] && db_brokers_drivers_tables_attributes[db_broker][db_driver] && db_brokers_drivers_tables_attributes[db_broker][db_driver][type])
				db_brokers_drivers_tables_attributes[db_broker][db_driver][type] = null;
			
			select_with_trigger.trigger("change");
		}
	}
}

function updateDBAttributes(elm, rand_number, do_not_sync) {
	var p = $(elm).parent().parent();
	var db_broker = p.find(".db_broker select").val();
	var db_driver = p.find(".db_driver select").val();
	var type = p.find(".type select").val();
	var db_table = p.find(".db_table select").val();
	var db_attribute_elm = p.children(".db_attribute");
	var select = db_attribute_elm.children("select");
	var chosen_attribute = select.val();
	
	select.html("");
	
	if (db_broker && db_driver && type && db_table) {
		var MyFP = MyFancyPopup;
		if (rand_number) {
			eval('var WF = taskFlowChartObj_' + rand_number + ';');
			MyFP = WF.getMyFancyPopupObj();
		}
		
		MyFP.showLoading();
		
		var db_attributes = getDBAttributes(db_broker, db_driver, type, db_table);
		
		var html = "<option></option>";
		for (var i = 0; i < db_attributes.length; i++)
			html += "<option>" + db_attributes[i] + "</option>";
		
		select.html(html);
		select.val(chosen_attribute);
		
		if (!MyFP.settings.hideChooseAttributesField) //this field was hidden before the system have called this function.
			db_attribute_elm.show();
		
		//update editor completer db_attributes
		$.each(db_attributes, function(idx, attr_name) {
			if ($.inArray(attr_name, sql_editor_completer_keywords_strings) == -1) {
				sql_editor_completer_keywords_strings.push(attr_name);
				sql_editor_completer_keywords.push({
					caption: attr_name, 
					value: attr_name, 
					meta: "attribute",
					score: 10
				});
			}
		});
		
		MyFP.hideLoading();
	}
	
	if (!do_not_sync)
		syncChooseTableOrAttributePopups(elm);
}

function refreshDBAttributes(elm) {
	var p = $(elm).parent().parent();
	var db_broker = p.find(".db_broker select").val();
	var db_driver = p.find(".db_driver select").val();
	var type = p.find(".type select").val();
	var db_table = p.find(".db_table select").val();
	
	if (!db_broker)
		StatusMessageHandler.showMessage("DB Broker cannot be undefined!", "", "bottom_messages", 2000);
	else if (!db_driver)
		StatusMessageHandler.showMessage("DB Driver cannot be undefined!", "", "bottom_messages", 2000);
	else if (!type)
		StatusMessageHandler.showMessage("DB Type cannot be undefined!", "", "bottom_messages", 2000);
	else if (!db_table)
		StatusMessageHandler.showMessage("DB Table cannot be undefined!", "", "bottom_messages", 2000);
	else {
		var select_with_trigger = p.find(".db_table select[onChange]").first();
		
		if (select_with_trigger[0]) {
			if (db_brokers_drivers_tables_attributes[db_broker] && db_brokers_drivers_tables_attributes[db_broker][db_driver] && db_brokers_drivers_tables_attributes[db_broker][db_driver][type] && db_brokers_drivers_tables_attributes[db_broker][db_driver][type][db_table])
				db_brokers_drivers_tables_attributes[db_broker][db_driver][type][db_table] = null;
			
			select_with_trigger.trigger("change");
		}
	}
}

function syncChooseTableOrAttributePopups(elm) {
	var popup = $(elm).parent().closest(".choose_table_or_attribute");
	var parent = null;
	
	if (popup.is("#choose_db_table_or_attribute")) 
		parent = $(MyFancyPopup.settings.targetField).parent().closest(".query");
	else {
		syncChooseTableOrAttributePopupWithAnotherPopup(popup, $("#choose_db_table_or_attribute"));
		
		parent = popup.parent().closest(".query");
	}
	
	parent.find(".choose_table_or_attribute").each(function(idx, other_popup) {
		syncChooseTableOrAttributePopupWithAnotherPopup(popup, other_popup);
	});
}

function syncChooseTableOrAttributePopupWithAnotherPopup(popup, other_popup) {
	if (other_popup != popup[0]) {
		other_popup = $(other_popup);
		
		other_popup.find(".db_broker select").html( popup.find(".db_broker select").html() );
		other_popup.find(".db_driver select").html( popup.find(".db_driver select").html() );
		other_popup.find(".type select").html( popup.find(".type select").html() );
		other_popup.find(".db_table select").html( popup.find(".db_table	 select").html() );
		other_popup.find(".db_attribute select").html( popup.find(".db_attribute select").html() );
		
		other_popup.find(".db_broker select").val( popup.find(".db_broker select").val() );
		other_popup.find(".db_driver select").val( popup.find(".db_driver select").val() );
		other_popup.find(".type select").val( popup.find(".type select").val() );
		other_popup.find(".db_table select").val( popup.find(".db_table	 select").val() );
		other_popup.find(".db_attribute select").val( popup.find(".db_attribute select").val() );
	}
}

/** START: CHOOSE DB TABLE OR ATTRIBUTE - MAP **/
function getTableFromDB(elm, rand_number) {
	var popup = $("#choose_db_table_or_attribute");
	
	popup.hide(); //This popup is shared with other actions so we must hide it first otherwise the user experience will be weird bc we will see the popup changing with the new changes.
	
	var input = $(elm).parent().find("input")[0];
	
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		onOpen: function() {
			$("#choose_db_table_or_attribute .db_attribute").hide();
			$("#choose_db_table_or_attribute .title").html("DB Table Selection");
			
			if (input.value != "") {
				$("#choose_db_table_or_attribute .db_table select").val(input.value);
			}
		},
		
		targetField: input,
		hideChooseAttributesField: true,
		updateFunction: function(sub_elm) {
			updateTableField(sub_elm, rand_number);
		}
	});
	
	MyFancyPopup.showPopup();
}

function getTableAttributeFromDB(elm, selector, rand_number) {
	var popup = $("#choose_db_table_or_attribute");
	
	popup.hide(); //This popup is shared with other actions so we must hide it first otherwise the user experience will be weird bc we will see the popup changing with the new changes.
	
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		onOpen: function() {
			$("#choose_db_table_or_attribute .title").html("DB Attribute Selection");
	
			var html = $("#choose_db_table_or_attribute .db_attribute select").html();
			if (html && html.toLowerCase().indexOf("<option") != -1) {
				$("#choose_db_table_or_attribute .db_attribute").show();
			}
			
			var field_value = $(elm).parent().find(selector).val();
			
			if (field_value != "")
				$("#choose_db_table_or_attribute .db_attribute select").val(field_value);
		},
		
		targetField: $(elm).parent().find(selector)[0],
		updateFunction: function(sub_elm) {
			updateAttributeField(sub_elm, rand_number);
		}
	});
	
	MyFancyPopup.showPopup();
}

function updateTableField(elm, rand_number) {
	var table_name = $(elm).parent().parent().find(".db_table select").val();
	table_name = table_name ? table_name : "";
	
	$(MyFancyPopup.settings.targetField).val(table_name);
	
	//auto update sql from settings
	if (typeof rand_number == "number") {
		eval('var WF = taskFlowChartObj_' + rand_number + ';');
		
		if ($("#" + WF.TaskFlow.main_tasks_flow_obj_id).attr("sync_ui_settings_with_sql") == 1)
			autoUpdateSqlFromUI(rand_number);
	}
	
	MyFancyPopup.hidePopup();
}

function updateAttributeField(elm, rand_number) {
	var attribute_name = $(elm).parent().parent().find(".db_attribute select").val();
	attribute_name = attribute_name ? attribute_name : "";
	
	$(MyFancyPopup.settings.targetField).val(attribute_name);
	
	//auto update sql from settings
	if (typeof rand_number == "number") {
		eval('var WF = taskFlowChartObj_' + rand_number + ';');
		
		if ($("#" + WF.TaskFlow.main_tasks_flow_obj_id).attr("sync_ui_settings_with_sql") == 1)
			autoUpdateSqlFromUI(rand_number);
	}
	
	MyFancyPopup.hidePopup();
}
/** END: CHOOSE DB TABLE OR ATTRIBUTE - MAP **/

/** START: CHOOSE DB TABLE OR ATTRIBUTE - QUERY **/
function getQueryTableFromDB(elm, rand_number) {
	eval('var WF = taskFlowChartObj_' + rand_number + ';');
	
	var popup = $("#" + WF.TaskFlow.main_tasks_flow_obj_id).parent().parent().parent().children(".query_settings").children(".choose_table_or_attribute");
	
	popup.hide(); //This popup is shared with other actions so we must hide it first otherwise the user experience will be weird bc we will see the popup changing with the new changes.
	
	var input = $(elm).parent().find("input")[0];
	
	popup.find(".db_attribute").hide();
	
	WF.getMyFancyPopupObj().init({
		elementToShow: popup,
		onOpen: function() {
			popup.find(".db_table").show();
	
			popup.find(".title").html("DB Table Selection");
			popup.find(".db_driver select").attr("onChange", "updateDBTables(this, " + rand_number + ")");
			popup.find(".type select").attr("onChange", "updateDBTables(this, " + rand_number + ")");
			popup.find(".db_table select").attr("onChange", "updateDBAttributes(this, " + rand_number + ")");
	
			if (input.value != "") {
				popup.find(".db_table select").val(input.value);
			}
		},
		
		targetField: input,
		hideChooseAttributesField: true,
		updateFunction: function(element) {
			updateQueryTableField(element, rand_number);
		}
	});
	
	WF.getMyFancyPopupObj().showPopup();
}

//This function is called when the user clicks in the search button for each column or attribute field.
function getQueryTableAttributeFromDB(elm, selector, rand_number) {
	eval('var WF = taskFlowChartObj_' + rand_number + ';');
		
	var popup = $("#" + WF.TaskFlow.main_tasks_flow_obj_id).parent().parent().parent().children(".query_settings").children(".choose_table_or_attribute");
	
	popup.hide(); //This popup is shared with other actions so we must hide it first otherwise the user experience will be weird bc we will see the popup changing with the new changes.
	
	var input = $(elm).parent().find(selector)[0];
	
	WF.getMyFancyPopupObj().init({
		elementToShow: popup,
		onOpen: function() {
			popup.find(".db_attribute").show();
			popup.find(".db_table").show();
	
			popup.find(".title").html("DB Attribute Selection");
			popup.find(".db_driver select").attr("onChange", "updateDBTables(this, " + rand_number + ")");
			popup.find(".type select").attr("onChange", "updateDBTables(this, " + rand_number + ")");
			popup.find(".db_table select").attr("onChange", "updateDBAttributes(this, " + rand_number + ")");
			popup.find(".db_attribute select").attr("onChange", "syncChooseTableOrAttributePopups(this)");
			
			var html = popup.find(".db_attribute select").html();
			if (html && html.toLowerCase().indexOf("<option") != -1) 
				popup.find(".db_attribute").show();
	
			var selected_attribute_name = input.value.trim();
			var selected_table_name = null;
			
			var column_div = $(input).parent();
			if (column_div.hasClass("column"))
				selected_table_name = column_div.parent().find(".table input").val().trim();
			else if (column_div.hasClass("pcolumn"))
				selected_table_name = column_div.parent().find(".ptable input").val().trim();
			else if (column_div.hasClass("fcolumn"))
				selected_table_name = column_div.parent().find(".ftable input").val().trim();
			
			var select = popup.find(".db_table select");
			
			if (!selected_table_name)
				selected_table_name = select.val();
			
			if (!selected_table_name)
				selected_table_name = default_db_table;
			
			if (!selected_attribute_name)
				selected_attribute_name = popup.find(".db_attribute select").val();
			
			select.val(selected_table_name);
			updateDBAttributes(select[0], rand_number);
			
			popup.find(".db_attribute select").val(selected_attribute_name);
		},
		
		targetField: input,
		updateFunction: function(element) {
			updateQueryAttributeField(element, rand_number);
		}
	});
	
	WF.getMyFancyPopupObj().showPopup();
}

function updateQueryTableField(elm, rand_number) {
	eval('var WF = taskFlowChartObj_' + rand_number + ';');
	
	var popup = $(elm).parent().parent();
	var table_name = popup.find(".db_table select").val();
	table_name = table_name ? table_name : "";
	
	var tasks = getTasksByTableName(table_name, WF);
	if (tasks[0]) {
		table_name = WF.TaskFlow.getTaskLabel(tasks[0]);
	}
	
	var input = $(WF.getMyFancyPopupObj().settings.targetField);
	input.attr("old_value", input.val().trim());
	input.val(table_name);
	
	var main_tasks_flow_obj = $("#" + WF.TaskFlow.main_tasks_flow_obj_id);
	
	if (main_tasks_flow_obj.attr("sync_ui_and_settings") == 1)
		onBlurQueryTableField(input[0], rand_number);
	
	if (main_tasks_flow_obj.attr("sync_ui_settings_with_sql") == 1)
		autoUpdateSqlFromUI(rand_number);
	
	WF.getMyFancyPopupObj().hidePopup();
}

function updateQueryAttributeField(elm, rand_number) {
	//WRITING SELECTED ATTRIBUTE TO INPUT 
	eval('var WF = taskFlowChartObj_' + rand_number + ';');
	
	var popup = $(elm).parent().parent();
	var table_name = popup.find(".db_table select").val();
	var attribute_name = popup.find(".db_attribute select").val();
	
	table_name = table_name ? table_name : "";
	attribute_name = attribute_name ? attribute_name : "";
	
	//PREPARING NEW INPUT VALUE
	var input = $(WF.getMyFancyPopupObj().settings.targetField);
	var old_attribute_name = input.val().trim();
	input.val(attribute_name);
	
	var column_div = input.parent();
	
	//PREPARING TABLE NAME ALIAS ACCORDING WITH THE UI
	var tasks = getTasksByTableName(table_name, WF);
	var table_name_alias = tasks[0] ? WF.TaskFlow.getTaskLabel(tasks[0]) : table_name;
	var old_table_name = null;
	
	if (column_div.hasClass("column")) {
		old_table_name = column_div.parent().find(".table input").val().trim();
	}
	else if (column_div.hasClass("pcolumn")) {
		old_table_name = column_div.parent().find(".ptable input").val().trim();
	}
	else if (column_div.hasClass("fcolumn")) {
		old_table_name = column_div.parent().find(".ftable input").val().trim();
	}
	
	var parts = old_table_name.split(" ");
	table_name = parts[0] != table_name ? table_name_alias : (old_table_name.trim() != "" ? old_table_name : table_name);
	
	if (column_div.hasClass("column")) {
		column_div.parent().find(".table input").val(table_name);
	}
	else if (column_div.hasClass("pcolumn")) {
		column_div.parent().find(".ptable input").val(table_name);
	}
	else if (column_div.hasClass("fcolumn")) {
		column_div.parent().find(".ftable input").val(table_name);
	}
	
	//PREPARING UI
	var main_tasks_flow_obj = $("#" + WF.TaskFlow.main_tasks_flow_obj_id);
	
	if (main_tasks_flow_obj.attr("sync_ui_and_settings") == 1)
		prepareUIWhenChangingQueryTableAttributeField(rand_number, column_div, table_name, attribute_name, old_table_name, old_attribute_name);
	
	if (main_tasks_flow_obj.attr("sync_ui_settings_with_sql") == 1)
		autoUpdateSqlFromUI(rand_number);
	
	WF.getMyFancyPopupObj().hidePopup();
}

function onFocusTableField(input) {
	input.setAttribute("old_value", input.value.trim());
}

function onBlurQueryTableField(input, rand_number) {
	eval('var WF = taskFlowChartObj_' + rand_number + ';');
	var main_tasks_flow_obj = $("#" + WF.TaskFlow.main_tasks_flow_obj_id);
	
	if (main_tasks_flow_obj.attr("sync_ui_and_settings") == 1) {
		var old_table_name = input.getAttribute("old_value");
		var new_table_name = input.value.trim();
	
		var column_div = $(input).parent();
		var attribute_name = null;
	
		if (column_div.hasClass("table"))
			attribute_name = column_div.parent().find(".column input").val().trim();
		else if (column_div.hasClass("ptable"))
			attribute_name = column_div.parent().find(".pcolumn input").val().trim();
		else if (column_div.hasClass("ftable")) 
			attribute_name = column_div.parent().find(".fcolumn input").val().trim();
	
		if (!new_table_name || new_table_name == "" || !old_table_name || old_table_name == "") {
			var tasks = getTasksByTableName(default_db_table, WF);
			var tb = tasks[0] ? WF.TaskFlow.getTaskLabel(tasks[0]) : default_db_table;
		
			new_table_name = new_table_name == "" ? tb : new_table_name;
			old_table_name = old_table_name == "" ? tb : old_table_name;
		}
		
		prepareUIWhenChangingQueryTableAttributeField(rand_number, column_div, new_table_name, attribute_name, old_table_name, attribute_name);
	}
	
	if (main_tasks_flow_obj.attr("sync_ui_settings_with_sql") == 1)
		autoUpdateSqlFromUI(rand_number);
	
	$(input).removeAttr("old_value");
}

function onFocusAttributeField(input) {
	input.setAttribute("old_value", input.value.trim());
}

function onBlurQueryAttributeField(input, rand_number) {
	eval('var WF = taskFlowChartObj_' + rand_number + ';');
	var main_tasks_flow_obj = $("#" + WF.TaskFlow.main_tasks_flow_obj_id);
	
	if (main_tasks_flow_obj.attr("sync_ui_and_settings") == 1) {
		var old_attribute_name = input.getAttribute("old_value");
		var attribute_name = input.value.trim();
	
		var column_div = $(input).parent();
		var table_name = null;
	
		if (column_div.hasClass("column"))
			table_name = column_div.parent().find(".table input").val().trim();
		else if (column_div.hasClass("pcolumn"))
			table_name = column_div.parent().find(".ptable input").val().trim();
		else if (column_div.hasClass("fcolumn"))
			table_name = column_div.parent().find(".ftable input").val().trim();
	
		if (!table_name || table_name == "") {
			table_name = default_db_table;
		
			var tasks = getTasksByTableName(table_name, WF);
			table_name = tasks[0] ? WF.TaskFlow.getTaskLabel(tasks[0]) : table_name;
		}
	
		prepareUIWhenChangingQueryTableAttributeField(rand_number, column_div, table_name, attribute_name, table_name, old_attribute_name);
	}
	
	if (main_tasks_flow_obj.attr("sync_ui_settings_with_sql") == 1)
		autoUpdateSqlFromUI(rand_number);
	
	$(input).removeAttr("old_value");
}

function onBlurQueryInputField(input, rand_number) {
	eval('var WF = taskFlowChartObj_' + rand_number + ';');
	
	if ($("#" + WF.TaskFlow.main_tasks_flow_obj_id).attr("sync_ui_settings_with_sql") == 1)
		autoUpdateSqlFromUI(rand_number);
}

function prepareUIWhenChangingQueryTableAttributeField(rand_number, column_div, table_name, attribute_name, old_table_name, old_attribute_name) {
	if (column_div.hasClass("column") || column_div.hasClass("table")) {
		//UPDATING UI
		var main_div = column_div.parent().parent().parent().parent();
	
		if (!main_div.hasClass("attributes") && !main_div.hasClass("keys")) {
			prepareWorkFlowQueryTableAttributeUI(rand_number, {
				table_name_to_select: table_name,
			}, column_div.hasClass("table"));
		}
		else {
			var uncheck = true;
			var items = column_div.parent().parent().find(".column input");
			for (var i = 0; i < items.length; i++) {
				var column_name = $(items[i]).val();
			
				if (column_name == old_attribute_name && $(items[i]).parent().parent().find(".table input").val().trim() == old_table_name) {
					uncheck = false;
					break;
				}
			}
		
			prepareWorkFlowQueryTableAttributeUI(rand_number, {
				table_name_to_select: table_name, 
				attribute_name_to_select: attribute_name, 
				table_name_to_unselect: (uncheck ? old_table_name : null),
				attribute_name_to_unselect: (uncheck ? old_attribute_name : null),
			}, column_div.hasClass("table"));
		}
	}
	else if (column_div.hasClass("pcolumn") || column_div.hasClass("ptable") || column_div.hasClass("fcolumn") || column_div.hasClass("ftable")) {
		//UPDATING CONNECTIONS
		if (column_div.hasClass("pcolumn") || column_div.hasClass("fcolumn")) {
			if (old_attribute_name != attribute_name || old_table_name != table_name) {
				var input = column_div.children("input")[0];
				input.setAttribute("old_value", old_attribute_name);
				
				var table_input = column_div.hasClass("pcolumn") ? column_div.parent().find(".ptable input") : column_div.parent().find(".ftable input");
				
				table_input.attr("old_value", old_table_name);
				
				onBlurQueryKey(input, rand_number);
				
				table_input.removeAttr("old_value");
			}
		}
		else if (old_table_name != table_name) {
			var input = column_div.children("input")[0];
			input.setAttribute("old_value", old_table_name);
		
			onBlurQueryKey(input, rand_number);
		}
	}
}

function prepareWorkFlowQueryTableAttributeUI(rand_number, settings, dont_check_compatibility) {
	//console.log(settings);
	
	eval('var WF = taskFlowChartObj_' + rand_number + ';');
	
	//PREPARING SETTINGS
	var table_name_to_select = settings.table_name_to_select;
	var attribute_name_to_select = settings.attribute_name_to_select;
	var table_name_to_unselect = settings.table_name_to_unselect;
	var attribute_name_to_unselect = settings.attribute_name_to_unselect;
	
	table_name_to_unselect = table_name_to_unselect ? table_name_to_unselect : table_name_to_select;
	
	//GETTING UI ATTRIBUTES
	var table_attr_names_to_select = {};
	var table_attr_names_to_unselect = {};
	var task_to_select = null;
	var task_to_unselect = null;
	var task_id_to_select = null;
	var task_id_to_unselect = null;
	
	var tasks = WF.TaskFlow.getAllTasks();
	for (var i = 0; i < tasks.length; i++) {
		var task = $(tasks[i]);
		
		var db_table = WF.TaskFlow.getTaskLabel(task);
		
		if (db_table == table_name_to_select || db_table == table_name_to_unselect) {
			if (db_table == table_name_to_select) {
				task_to_select = task;
				task_id_to_select = task.attr("id");
				table_attr_names_to_select = getTaskTableAttributes(task, WF);
			}
			else if (db_table == table_name_to_unselect) {
				task_to_unselect = task;
				task_id_to_unselect = task.attr("id");
				table_attr_names_to_unselect = getTaskTableAttributes(task, WF);
			}
		}
	}
	
	myWFObj.setTaskFlowChart(WF);
		
	//PREPARING NEW UI ATTRIBUTES
	if (task_to_unselect && task_id_to_unselect && table_name_to_unselect && attribute_name_to_unselect && table_name_to_unselect != table_name_to_select) {
		if (table_attr_names_to_unselect.hasOwnProperty(attribute_name_to_unselect)) {
			table_attr_names_to_unselect[attribute_name_to_unselect] = false;
		
			var data = {
				table_name : table_name_to_unselect,
				table_attr_names : table_attr_names_to_unselect,
			}
			DBQueryTaskPropertyObj.prepareTableAttributes(task_id_to_unselect, data, rand_number);
		}
	}
	
	if (task_to_select && task_id_to_select) {
		var is_compatible = true;
		var changed = false;
		
		//PREPARING table_attr_names
		if (attribute_name_to_select) {
			if (table_attr_names_to_select.hasOwnProperty(attribute_name_to_select)) {
				table_attr_names_to_select[attribute_name_to_select] = true;
				changed = true;
			}
			else if (!dont_check_compatibility) {
				is_compatible = false;
			}
		}
		
		if (table_name_to_unselect && attribute_name_to_unselect && table_name_to_unselect == table_name_to_select && attribute_name_to_unselect != attribute_name_to_select) {
			if (table_attr_names_to_select.hasOwnProperty(attribute_name_to_unselect)) {
				table_attr_names_to_select[attribute_name_to_unselect] = false;
				changed = true;
			}
		}
		
		//PREPARING UI
		var data = {
			table_name : table_name_to_select,
			table_attr_names : table_attr_names_to_select,
		}
		
		//CHECKING IMCOMPATIBILITY
		if (!is_compatible) {
			updateQueryTablesFromDBBroker(null, rand_number, false);
			
			data.table_attr_names = getTaskTableAttributes(task_to_select, WF);
			
			if (data.table_attr_names.hasOwnProperty(attribute_name_to_select)) {
				data.table_attr_names[attribute_name_to_select] = true;
			}
		}
		
		if (changed || !is_compatible) {
			DBQueryTaskPropertyObj.prepareTableAttributes(task_id_to_select, data, rand_number);
		}
	}
	else if (table_name_to_select) {
		//CREATING NEW TABLE
		var popup = $("#" + WF.TaskFlow.main_tasks_flow_obj_id + " .choose_table_or_attribute");
		var db_broker = popup.find(".db_broker select").val();
		var db_driver = popup.find(".db_driver select").val();
		var type = popup.find(".type select").val();
		
		var table_attr_names = {"*": false};
		var db_attrs = getDBAttributes(db_broker, db_driver, type, table_name_to_select);
		for (var i = 0; i < db_attrs.length; i++) {
			table_attr_names[ db_attrs[i] ] = attribute_name_to_select && db_attrs[i] == attribute_name_to_select ? true : false;
		}
		
		var task_id = addNewTable(rand_number, table_name_to_select, table_attr_names);
		
		if (!task_id) {
			WF.StatusMessage.showError("Error: Couldn't create the '" + table_name_to_select + "' table.\nPlease try again...");
		}
	}
}
/** END: CHOOSE DB TABLE OR ATTRIBUTE - QUERY **/
/* END: CHOOSE DB TABLE OR ATTRIBUTE */

/* START: QUERY */
/** START: QUERY - INIT **/
function initAllQueryTasks() {
	$(".relationships .rels div.query").each(function(idx, elm) {
		var rand_number = $(elm).attr("rand_number");
		
		repaintQueryTasks(rand_number);
	});
}

function initQuerySql(tab_elm, rand_number) {
	tab_elm = $(tab_elm);
	var selector = tab_elm.attr("href");
	var do_not_confirm = tab_elm.attr("do_not_confirm");
	var not_create_sql_from_ui = tab_elm.attr("not_create_sql_from_ui");
	
	setQuerySqlEditor(selector);
	
	setTimeout(function() {
		if (not_create_sql_from_ui != 1) {
			prepareQuerySqlFromUIOrViceVersa(tab_elm, rand_number, do_not_confirm);
		}
		
		var editor = getQuerySqlEditor(selector);
		if (editor && $(selector).is(":visible")) {
			editor.focus();
		}
	}, 10);
}

function initQueryDesign(tab_elm, rand_number) {
	tab_elm = $(tab_elm);
	var do_not_confirm = tab_elm.attr("do_not_confirm");
	var not_create_ui_from_sql = tab_elm.attr("not_create_ui_from_sql");
	
	var query_sql_elm_selector = tab_elm.parent().parent().children(".query_sql_tab").children("a").attr("href");
	setQuerySqlEditor(query_sql_elm_selector);
	
	setTimeout(function() {
		eval('var WF = taskFlowChartObj_' + rand_number + ';');
		
		if (!WF.isInitialized())
			WF.init();
		
		WF.getMyFancyPopupObj().updatePopup();
		
		//prepare auto_save in WF
		onToggleQueryAutoSave();
		
		if (not_create_ui_from_sql != 1) 
			prepareQuerySqlFromUIOrViceVersa(tab_elm, rand_number, do_not_confirm);
	}, 10);
}

function addTaskFlowChart(rand, init_now) {
	var options = {
		on_init_function : function(WFObj) {
			var task_context_menu = $("#" + WFObj.ContextMenu.task_context_menu_id);
			task_context_menu.children(".set_label").children("a").html("Set Table Alias");
		
			var delete_menu_elm = task_context_menu.children(".delete").children("a");
			delete_menu_elm.attr("onClick", "");
			delete_menu_elm.click(function(){
				myWFObj.setTaskFlowChart(WFObj);
			
				var task_id = WFObj.ContextMenu.getContextMenuTaskId();
				DBQueryTaskPropertyObj.deleteTable(task_id, true);
			
				return false;
			});
		}
	};
	
	var WF = new TaskFlowChart("taskFlowChartObj_" + rand, options);
	eval('window.taskFlowChartObj_' + rand + ' = WF;');
	
	WF.TaskFlow.default_connection_connector = "Straight";
	WF.TaskFlow.available_connection_overlays_type = ["No Arrows"];
	
	WF.TaskFlow.main_tasks_flow_obj_id = "taskflowchart_" + rand + " .tasks_flow";
	WF.TaskFlow.main_tasks_properties_obj_id = "taskflowchart_global .tasks_properties";
	WF.TaskFlow.main_connections_properties_obj_id = "taskflowchart_global .connections_properties";
	WF.ContextMenu.main_tasks_menu_obj_id = "taskflowchart_global .tasks_menu";
	WF.ContextMenu.main_tasks_menu_hide_obj_id = "taskflowchart_global .tasks_menu_hide";
	WF.ContextMenu.main_workflow_menu_obj_id = "taskflowchart_global .workflow_menu";
	
	WF.TaskFlow.default_connection_line_width = 3;
	WF.TaskFlow.default_connection_from_target = true;
	
	WF.Property.tasks_settings = tasks_settings;
	
	if (init_now)
		WF.init();
	
	return WF;
}

function showQueryDesign(relationship_obj) {
	//show the UI for all queries
	relationship_obj.find(".query_design_tab a").each(function(idx, query_design_tab_a) {
		query_design_tab_a = $(query_design_tab_a);
		query_design_tab_a.attr("do_not_confirm", 1);
		query_design_tab_a.trigger("click");
		query_design_tab_a.removeAttr("do_not_confirm");
	});
}
/** END: QUERY - INIT **/

/** START: QUERY - UPDATE TASK TABLE AUTOMATICALLY **/
//This function will be called when we click in the "Update Tables' Attributes" button.
function updateQueryDBBroker(rand_number, is_automatically) {
	eval('var WF = taskFlowChartObj_' + rand_number + ';');
	
	var popup = $("#" + WF.TaskFlow.main_tasks_flow_obj_id + " .choose_table_or_attribute");
	
	popup.hide(); //This popup is shared with other actions so we must hide it first otherwise the user experience will be weird bc we will see the popup changing with the new changes.
	
	popup.find(".db_driver select").attr("onChange", "syncChooseTableOrAttributePopups(this);");
	popup.find(".type select").attr("onChange", "syncChooseTableOrAttributePopups(this);");
	
	popup.find(".db_attribute").hide();
	popup.find(".db_table").hide();
	popup.children(".title").html("Update Table's Attributes");
	
	WF.getMyFancyPopupObj().init({
		elementToShow: popup,
		onOpen: function() {
			if (is_automatically) {
				updateQueryDBBrokerAutomatically(rand_number);
			}
		},
		
		hideChooseAttributesField: true,
		updateFunction: function(elm) {
			updateQueryTablesFromDBBroker(elm, rand_number, is_automatically);
		},
	});
	
	WF.getMyFancyPopupObj().showPopup();
}

function updateQueryTablesFromDBBroker(elm, rand_number, is_automatically) {
	//console.log(elm);
	//console.log(rand_number);
	
	//PREPARING DB TABLE ATTRIBUTES
	eval('var WF = taskFlowChartObj_' + rand_number + ';');
	
	WF.getMyFancyPopupObj().showLoading();
	
	var popup = $("#" + WF.TaskFlow.main_tasks_flow_obj_id + " .choose_table_or_attribute");
	var db_broker = popup.find(".db_broker select").val();
	var db_driver = popup.find(".db_driver select").val();
	var type = popup.find(".type select").val();
	
	//Fixing inconsistencies of the loaded tables when addNewTable. DO NOT REMOVE THIS CODE, otherwise it won't show correctly the tables for the correspondent brokers.
	updateDBTables( popup.find(".db_broker select")[0], rand_number );
	popup.find(".db_table").hide();
	
	var tasks = WF.TaskFlow.getAllTasks();
	
	var old_tables = {};
	var new_tables = {};
	
	for (var i = 0; i < tasks.length; i++) {
		var task = $(tasks[i]);
		var task_id = task.attr("id");
		
		var db_table = getTaskTableName(task, WF);
		
		old_tables[task_id] = {
			"table_name": db_table, 
			"attributes": getTaskTableAttributes(task, WF)
		};
		new_tables[ db_table ] = getDBAttributes(db_broker, db_driver, type, db_table);
	}
	
	//CHECKING INCOMPATIBILITIES BETWEEN EXISTENT TABLES AND DB TABLES
	var is_compatible = true;
	var old_attributes_to_delete = {};
	
	for (var task_id in old_tables) {
		var db_table = old_tables[task_id]["table_name"];
		old_attributes_to_delete[db_table] = [];
		
		if (!new_tables[db_table]) {
			is_compatible = false;
		}
			
		for (var ot in old_tables[task_id]["attributes"]) {
			if (ot != "*" && $.inArray(ot, new_tables[db_table]) == -1) {
				is_compatible = false;
				
				old_attributes_to_delete[db_table].push(ot);
			}
		}
	}
	
	//console.log(new_tables);
	//console.log(old_tables);
	
	//REDESIGNING THE TABLE ATTRIBUTES
	if (is_automatically && !is_compatible) {
		WF.getMyFancyPopupObj().hidePopup();
		
		updateQueryDBBroker(rand_number, false);
	}
	else if (!is_automatically && !is_compatible && !confirm("The system detected some incompatibilities between the existent tables's attributes and DB's attributes.\nIf you continue the system will discart these incompatibilities and overwrite the existent tables with the DB's tables.\nDo you wish to continue?")) {
		WF.getMyFancyPopupObj().hidePopup();
	}
	else {
		myWFObj.setTaskFlowChart(WF);
		
		for (var i = 0; i < tasks.length; i++) {
			var task = $(tasks[i]);
			
			var task_id = task.attr("id");
			var db_table = getTaskTableName(task, WF);
			
			if (new_tables[db_table]) {
				var table_attr_names = {}
				table_attr_names["*"] = old_tables[task_id]["attributes"]["*"] == true;
				
				for (var j = 0; j < new_tables[db_table].length; j++) {
					var att = new_tables[db_table][j];
				
					table_attr_names[att] = old_tables[task_id]["attributes"][att] == true;
				}
				
				var data = {
					table_name : WF.TaskFlow.getTaskLabel(task),
					table_attr_names : table_attr_names,
				}
			
				DBQueryTaskPropertyObj.prepareTableAttributes(task_id, data, rand_number);
				
				deleteOldAttributesThatDontExistAnymore(WF, old_attributes_to_delete);
			}
			else {
				DBQueryTaskPropertyObj.deleteTable(task_id, false);
			}
		}
		
		WF.getMyFancyPopupObj().hidePopup();
	}
	
	WF.getMyFancyPopupObj().hideLoading();
}

function updateQueryDBBrokerAutomatically(rand_number) {
	eval('var WF = taskFlowChartObj_' + rand_number + ';');
	
	var popup = $("#" + WF.TaskFlow.main_tasks_flow_obj_id + " .choose_table_or_attribute");
	var db_broker = popup.find(".db_broker select")[0];
	var db_driver = popup.find(".db_driver select")[0];
	
	if (db_broker.options.length <= 2 && db_driver.options.length <= 2) {
		WF.getMyFancyPopupObj().settings.updateFunction( popup.find(".button input")[0] );
	}
}

function deleteOldAttributesThatDontExistAnymore(WF, old_attributes_to_delete) {
	var settings = $("#" + WF.TaskFlow.main_tasks_flow_obj_id).parent().parent().parent().children(".query_settings");
		
	if (settings) {
		var tasks = getTasksByTableName(default_db_table, WF);
		var tb = tasks[0] ? WF.TaskFlow.getTaskLabel(tasks[0]) : default_db_table;
		
		settings.find("tbody .field .ptable").each(function(idx, elm) {
			elm = $(elm).parent()[0];
			var j_elm = $(elm);
			
			var ptable = j_elm.find(".ptable input").val();
			var pcolumn = j_elm.find(".pcolumn input").val();
			
			var ftable = j_elm.find(".ftable input").val();
			var fcolumn = j_elm.find(".fcolumn input").val();
			
			ptable = ptable.trim() != "" ? ptable.trim() : tb;
			pcolumn = pcolumn.trim() != "" ? pcolumn.trim() : "";
			ftable = ftable.trim() != "" ? ftable.trim() : tb;
			fcolumn = fcolumn.trim() != "" ? fcolumn.trim() : "";
			
			var parts = ptable.split(" ");
			ptable = parts[0];
			
			parts = ftable.split(" ");
			ftable = parts[0];
			
			if (pcolumn && $.inArray(pcolumn, old_attributes_to_delete[ptable]) != -1) {
				j_elm.find(".icon_cell .delete").click();
			}
			else if (fcolumn && $.inArray(fcolumn, old_attributes_to_delete[ftable]) != -1) {
				j_elm.find(".icon_cell .delete").click();
			}
		});
		
		settings.find("tbody .field .table").each(function(idx, elm) {
			elm = $(elm).parent()[0];
			var j_elm = $(elm);
			
			var table = j_elm.find(".table input").val();
			var column = j_elm.find(".column input").val();
			
			var remove = false;
			
			table = table.trim() != "" ? table.trim() : tb;
			column = column.trim() != "" ? column.trim() : "";
			
			var parts = table.split(" ");
			table = parts[0];
			
			if (column && $.inArray(column, old_attributes_to_delete[table]) != -1) {
				j_elm.find(".icon_cell .delete").click();
			}
		});
	}
}
/** END: QUERY - UPDATE TASK TABLE AUTOMATICALLY **/

/** START: QUERY - ADD NEW TASK TABLE **/
function addNewTask(rand_number) {
	eval('var WF = taskFlowChartObj_' + rand_number + ';');
	
	var popup = $("#" + WF.TaskFlow.main_tasks_flow_obj_id + " .choose_table_or_attribute");
	
	popup.hide(); //This popup is shared with other actions so we must hide it first otherwise the user experience will be weird bc we will see the popup changing with the new changes.
	
	popup.find(".db_driver select").attr("onChange", "updateDBTables(this, " + rand_number + ")");
	popup.find(".type select").attr("onChange", "updateDBTables(this, " + rand_number + ")");
	popup.find(".db_table select").attr("onChange", "syncChooseTableOrAttributePopups(this);");
	
	popup.find(".db_attribute").hide();
	popup.find(".db_table").show();
	popup.find(".title").html("Choose Table to Add");
	
	WF.getMyFancyPopupObj().init({
		elementToShow: popup,
		onOpen: function() {
			updateDBTables( popup.find(".db_driver select")[0], rand_number);
		},
		
		hideChooseAttributesField: true,
		updateFunction: function(elm) {
			var db_broker = popup.find(".db_broker select").val();
			var db_driver = popup.find(".db_driver select").val();
			var type = popup.find(".type select").val();
			var db_table = popup.find(".db_table select").val();
			
			var db_attributes = getDBAttributes(db_broker, db_driver, type, db_table);
			
			var table_attr_names = {"*" : false};
			for (var i = 0; i < db_attributes.length; i++) {
				table_attr_names[ db_attributes[i] ] = false;
			}
			
			var task_id = addNewTable(rand_number, db_table, table_attr_names);
			
			//check if there is any start task, and if not sets this new task as start task
			//TODO
			
			WF.getMyFancyPopupObj().hidePopup();
		},
	});
	
	WF.getMyFancyPopupObj().showPopup();
}

function addNewTable(rand_number, table_name, table_attr_names, offset) {
	eval('var WF = taskFlowChartObj_' + rand_number + ';');
	
	/*console.log(table_name);
	console.log(table_attr_names);
	console.log(offset);
	console.log(WF);*/
	
	var previous_tasks = WF.TaskFlow.getAllTasks();
	if (previous_tasks.length == 0)
		default_db_table = table_name;
	
	//If table name is repeated, give an automatically alias
	var tasks = getTasksByTableName(table_name, WF);
	if (tasks.length > 0)
		table_name += " t" + parseInt(Math.random() * 1000);
	
	var task_id = WF.ContextMenu.addTaskByType(task_table_type_id, offset);
	
	var data = {
		table_name : table_name,
		table_attr_names : table_attr_names,
	}
	myWFObj.setTaskFlowChart(WF);
	DBQueryTaskPropertyObj.prepareTableAttributes(task_id, data, rand_number);
	
	$("#" + WF.TaskFlow.main_tasks_flow_obj_id + " #" + task_id + " ." + WF.TaskFlow.task_label_class_name + " span").each(function(idx, elm){
		var j_elm = $(elm);
		
		if (!j_elm.hasClass("icon")) {
			j_elm.unbind("click");
		}
	});
	
	if (previous_tasks.length == 0) {
		WF.ContextMenu.setContextMenuTaskId(task_id);
		WF.ContextMenu.setSelectedStartTask({do_not_call_hide_properties: true});
	}
	
	return task_id;
}

function connectTables(rand_number, source_task_id, target_task_id, exit_props) {
	eval('var WF = taskFlowChartObj_' + rand_number + ';');
	
	var connection = WF.TaskFlow.connect(source_task_id, target_task_id, "", DBQueryTaskPropertyObj.connection_exit_props.type, DBQueryTaskPropertyObj.connection_exit_props.overlay, DBQueryTaskPropertyObj.connection_exit_props);
	
	if (exit_props && connection.id) {
		WF.TaskFlow.connections_properties[connection.id] = exit_props;
	}
	
	return connection;
}
/** END: QUERY - ADD NEW TASK TABLE **/

/** END: QUERY - UTILS FOR UI **/
function addRelationshipBlock(elm, is_hbn_obj, sql) {
	var rand_number = parseInt(Math.random() * 1000); 
	
	var html = is_hbn_obj ? new_relationship_block_html : new_relationship_query_block_html;
	html = html.replace(/#rand#/g, rand_number);
	
	var rels = $(elm).parent().children(".rels");
	var html_obj = $(html);
	rels.append(html_obj);
	
	onNewHtmlCallback(html_obj);
	
	var WF = addTaskFlowChart(rand_number, false);
	
	var main_tasks_flow_obj = $("#" + WF.TaskFlow.main_tasks_flow_obj_id);
	var query_obj_tab = main_tasks_flow_obj.parent().parent().parent().parent();
	var query_obj = query_obj_tab.parent();
	
	var sync = main_tasks_flow_obj.attr("sync_ui_and_settings") == 1;
	
	if (sync)
		query_obj.tabs({active: 1});
	else
		query_obj.tabs();
	
	query_obj_tab.children(".query_select").children(".query_settings").tabs();
	query_obj_tab.children(".query_insert_update_delete").tabs();
	
	var relationship_obj = query_obj.parent();
	
	updateRelationshipType(relationship_obj.children(".rel_type").children("select")[0], rand_number);
	
	if (sync) {
		var a = query_obj.children(".query_tabs").children(".query_sql_tab").children("a");
		
		if (sql) {
			var query_sql_elm_selector = a.attr("href");
			query_obj.children(query_sql_elm_selector).children("textarea").val(sql);
		}
		
		a.attr("not_create_sql_from_ui", 1);
		initQuerySql(a[0], rand_number);
		a.removeAttr("not_create_sql_from_ui");
	}
	
	return [html_obj, rand_number];
}

function addQueryAttribute1(elm, rand_number) {
	var html_obj = $(new_relationship_attribute1_html.replace(/#rand#/g, rand_number));
	$(elm).parent().parent().parent().parent().children(".fields").append(html_obj);
	
	onNewHtmlCallback(html_obj);
	
	return html_obj;
}

function addQueryAttribute2(elm, rand_number) {
	var html_obj = $(new_relationship_attribute2_html.replace(/#rand#/g, rand_number));
	$(elm).parent().parent().parent().parent().children(".fields").append(html_obj);
	
	onNewHtmlCallback(html_obj);
	
	return html_obj;
}

function addQueryKey(elm, rand_number) {
	var html_obj = $(new_relationship_key_html.replace(/#rand#/g, rand_number));
	$(elm).parent().parent().parent().parent().children(".fields").append(html_obj);
	
	onNewHtmlCallback(html_obj);
	
	return html_obj;
}

function addQueryCondition1(elm, rand_number) {
	var html_obj = $(new_relationship_condition1_html.replace(/#rand#/g, rand_number));
	$(elm).parent().parent().parent().parent().children(".fields").append(html_obj);
	
	onNewHtmlCallback(html_obj);
	
	return html_obj;
}

function addQueryCondition2(elm, rand_number) {
	var html_obj = $(new_relationship_condition2_html.replace(/#rand#/g, rand_number));
	$(elm).parent().parent().parent().parent().children(".fields").append(html_obj);
	
	onNewHtmlCallback(html_obj);
	
	return html_obj;
}

function addQueryGroupBy(elm, rand_number) {
	var html_obj = $(new_relationship_group_by_html.replace(/#rand#/g, rand_number));
	$(elm).parent().parent().parent().parent().children(".fields").append(html_obj);
	
	onNewHtmlCallback(html_obj);
	
	return html_obj;
}

function addQuerySort(elm, rand_number) {
	var html_obj = $(new_relationship_sort_html.replace(/#rand#/g, rand_number));
	$(elm).parent().parent().parent().parent().children(".fields").append(html_obj);
	
	onNewHtmlCallback(html_obj);
	
	return html_obj;
}

function deleteQueryField(elm, rand_number) {
	var field = $(elm).parent().parent();
	
	var input = field.children(".table, .column, .value").children("input");
	input.val("");
	
	onBlurQueryInputField(input[0], rand_number);
	
	field.remove();
}

function deleteQueryAttribute(elm, rand_number) {
	var field = $(elm).parent().parent();
	
	var input = field.children(".column").children("input")[0];
	input.setAttribute("old_value", input.value.trim());
	input.value = "";
	
	onBlurQueryAttributeField(input, rand_number);
	
	field.remove();
}

function deleteQueryKey(elm, rand_number) {
	eval('var WF = taskFlowChartObj_' + rand_number + ';');
	
	var field = $(elm).parent().parent();
	
	var ptable = field.children(".ptable").children("input").val().trim();
	var pcolumn = field.children(".pcolumn").children("input").val().trim();
	var ftable = field.children(".ftable").children("input").val().trim();
	var fcolumn = field.children(".fcolumn").children("input").val().trim();
	var cv = field.children(".value").children("input").val();
	var join_value = field.children(".join").children("select").val();
	var operator = field.children(".operator").children("select").val();
	
	var tasks = getTasksByTableName(default_db_table, WF);
	var tb = tasks[0] ? WF.TaskFlow.getTaskLabel(tasks[0]) : default_db_table;
	
	ptable = ptable == "" ? tb : ptable;
	ftable = ftable == "" ? tb : ftable;
	
	deleteQueryKeyFromConnectionsProperties(rand_number, ptable, pcolumn, ftable, fcolumn, cv, join_value, operator);
	
	field.remove();
	
	if ($("#" + WF.TaskFlow.main_tasks_flow_obj_id).attr("sync_ui_settings_with_sql") == 1)
		autoUpdateSqlFromUI(rand_number);
}

function onFocusQueryKey(input) {
	input.setAttribute("old_value", $(input).val().trim());	//it can be a select element
}

function onBlurQueryKey(input, rand_number) {
	eval('var WF = taskFlowChartObj_' + rand_number + ';');
	var main_tasks_flow_obj = $("#" + WF.TaskFlow.main_tasks_flow_obj_id);
	
	if (main_tasks_flow_obj.attr("sync_ui_and_settings") == 1) {
		var field = $(input).parent().parent();
	
		var new_ptable = "", old_ptable = "", new_pcolumn = "", old_pcolumn = "", new_ftable = "", old_ftable = "", new_fcolumn = "", old_fcolumn = "", new_cv = "", old_cv = "", new_join_value = "", old_join_value = "", new_operator = "", old_operator = "";
	
		new_ptable = field.children(".ptable").children("input").val().trim();
		new_pcolumn = field.children(".pcolumn").children("input").val().trim();
		new_ftable = field.children(".ftable").children("input").val().trim();
		new_fcolumn = field.children(".fcolumn").children("input").val().trim();
		new_cv = field.children(".value").children("input").val();
		new_join_value = field.children(".join").children("select").val();
		new_operator = field.children(".operator").children("select").val();
	
		var column_div = $(input).parent();
		var input_old_value = $(input).attr("old_value");
		input_old_value = !input_old_value ? "" : input_old_value;
	
		if (column_div.hasClass("ptable")) {
			old_ptable = input_old_value;
		
			old_pcolumn = new_pcolumn;
			old_ftable = new_ftable;
			old_fcolumn = new_fcolumn;
			old_cv = new_cv;
			old_join_value = new_join_value;
			old_operator = new_operator;
		}
		else if (column_div.hasClass("pcolumn")) {
			old_pcolumn = input_old_value;
		
			var inp = field.children(".ptable").children("input");
			if (inp[0].hasAttribute("old_value")) {
				old_ptable = inp.attr("old_value");
			}
			else {
				old_ptable = new_ptable;
			}
		
			old_ftable = new_ftable;
			old_fcolumn = new_fcolumn;
			old_cv = new_cv;
			old_join_value = new_join_value;
			old_operator = new_operator;
		}
		else if (column_div.hasClass("ftable")) {
			old_ftable = input_old_value;
		
			old_ptable = new_ptable;
			old_pcolumn = new_pcolumn;
			old_fcolumn = new_fcolumn;
			old_cv = new_cv;
			old_join_value = new_join_value;
			old_operator = new_operator;
		}
		else if (column_div.hasClass("fcolumn")) {
			old_fcolumn = input_old_value;
		
			var inp = field.children(".ftable").children("input");
			if (inp[0].hasAttribute("old_value")) {
				old_ftable = inp.attr("old_value");
			}
			else {
				old_ftable = new_ftable;
			}
		
			old_ptable = new_ptable;
			old_pcolumn = new_pcolumn;
			old_cv = new_cv;
			old_join_value = new_join_value;
			old_operator = new_operator;
		}
		else if (column_div.hasClass("value")) {
			old_cv = input_old_value;
		
			old_ptable = new_ptable;
			old_pcolumn = new_pcolumn;
			old_ftable = new_ftable;
			old_fcolumn = new_fcolumn;
			old_join_value = new_join_value;
			old_operator = new_operator;
		}
		else if (column_div.hasClass("join")) {
			old_join_value = input_old_value;
		
			old_ptable = new_ptable;
			old_pcolumn = new_pcolumn;
			old_ftable = new_ftable;
			old_fcolumn = new_fcolumn;
			old_cv = new_cv;
			old_operator = new_operator;
		}
		else if (column_div.hasClass("operator")) {
			old_operator = input_old_value;
		
			old_ptable = new_ptable;
			old_pcolumn = new_pcolumn;
			old_ftable = new_ftable;
			old_fcolumn = new_fcolumn;
			old_cv = new_cv;
			old_join_value = new_join_value;
		}
	
		//SETTING TABLE DEFAULT
		var tasks = getTasksByTableName(default_db_table, WF);
		var tb = tasks[0] ? WF.TaskFlow.getTaskLabel(tasks[0]) : default_db_table;
	
		old_ptable = !old_ptable || old_ptable == "" ? tb : old_ptable;
		new_ptable = !new_ptable || new_ptable == "" ? tb : new_ptable;
		old_ftable = !old_ftable || old_ftable == "" ? tb : old_ftable;
		new_ftable = !new_ftable || new_ftable == "" ? tb : new_ftable;
	
		//ADDING OR DELETING FOREGIN KEYS PROPERTIES
		if (old_ptable != new_ptable || old_pcolumn != new_pcolumn || old_ftable != new_ftable || old_fcolumn != new_fcolumn || old_cv != new_cv || old_join_value != new_join_value || old_operator != new_operator) {
			//console.log("OLD:"+old_ptable+", "+old_pcolumn+", "+old_ftable+", "+old_fcolumn+", "+old_cv+", "+old_join_value+", "+old_operator);
			//console.log("NEW:"+new_ptable+", "+new_pcolumn+", "+new_ftable+", "+new_fcolumn+", "+new_cv+", "+new_join_value+", "+new_operator);
		
			//we need to call the prepareWorkFlowQueryTableAttributeUI because the table might not exist and the prepareWorkFlowQueryTableAttributeUI will created it automatically, but only if doesn't exist yet!
			prepareWorkFlowQueryTableAttributeUI(rand_number, {
				table_name_to_select: new_ptable
			}, true);
		
			if (new_ptable != new_ftable) {
				prepareWorkFlowQueryTableAttributeUI(rand_number, {
					table_name_to_select: new_ftable
				}, true);
			}
		
			if (old_ptable != "" && old_ftable != "" && old_ptable != old_ftable && (old_pcolumn != "" || old_fcolumn != "")) {
				deleteQueryKeyFromConnectionsProperties(rand_number, old_ptable, old_pcolumn, old_ftable, old_fcolumn, old_cv, old_join_value, old_operator);
			}
		
			if (new_ptable != "" && new_ftable != "" && new_ptable != new_ftable && (new_pcolumn != "" || new_fcolumn != "")) {
				addQueryKeyToConnectionsProperties(rand_number, new_ptable, new_pcolumn, new_ftable, new_fcolumn, new_cv, new_join_value, new_operator);
			}
		}
	
		//CHECKING IF COLUMN REAL EXISTS AND IF DOESN't, BLANK IT
		if (column_div.hasClass("pcolumn") && new_pcolumn != "") {
			var tasks = getTasksByTableName(new_ptable, WF);
			var attrs = getTaskTableAttributes(tasks[0], WF);
			if (!attrs.hasOwnProperty(new_pcolumn)) {
				WF.StatusMessage.showError("The attribute '" + new_pcolumn + "' that you inserted doesn't belong to the table '" + new_ptable + "'. Please replace it with a correct attribute...");
			}
		}
		else if (column_div.hasClass("fcolumn") && new_fcolumn != "") {
			var tasks = getTasksByTableName(new_ftable, WF);
			var attrs = getTaskTableAttributes(tasks[0], WF);
			if (!attrs.hasOwnProperty(new_fcolumn)) {
				WF.StatusMessage.showError("The attribute '" + new_fcolumn + "' that you inserted doesn't belong to the table '" + new_ftable + "'. Please replace it with a correct attribute...");
			}
		}
	}
	
	if (main_tasks_flow_obj.attr("sync_ui_settings_with_sql") == 1)
		autoUpdateSqlFromUI(rand_number);
	
	$(input).removeAttr("old_value");
}

function toggleParameterAndResultFields(elm, prefix_class) {
	elm = $(elm);
	var selector = (prefix_class ? prefix_class : "") + " .data_access_obj";
	var data_access_obj = $(selector);
	var is_shown = elm.hasClass("active");
	
	data_access_obj.find(".relationship").find(".parameter_class_id, .parameter_map_id, .result_class_id, .result_map_id").each(function(idx, child) {
		child = $(child);
		
		if (is_shown)
			child.slideUp("normal", function() {
				child.hide();
			});
		else
			child.slideDown("normal", function() {
				child.css("display", "inline-block");
			});
	}).promise().done(function () { 
		if (is_shown) {
			elm.removeClass("active");
			data_access_obj.removeClass("with_parameter_and_result_fields");
		}
		else {
			elm.addClass("active");
			data_access_obj.addClass("with_parameter_and_result_fields");
		}
	});
}

function toggleMainSettingsPanel(elm, prefix_class) {
	elm = $(elm);
	var selector = (prefix_class ? prefix_class : "") + " .data_access_obj";
	var data_access_obj = $(selector);
	var settings = data_access_obj.find(".relationship").find(".settings");
	var toggle_main_settings = $(".top_bar header ul li.toggle_main_settings");
	var input = toggle_main_settings.find("input");
	var span = toggle_main_settings.find("span");
	
	settings.toggleClass("collapsed");
	settings.children(".settings_header").children(".icon.minimize, .icon.maximize").toggleClass("maximize").toggleClass("minimize");
	
	if (settings.hasClass("collapsed")) {
		input.prop("checked", false).removeAttr("checked");
		span.html("Show Main Settings");
	}
	else {
		input.prop("checked", true).attr("checked", "checked");
		span.html("Hide Main Settings");
	}
}

function initMainSettingsPanel() {
	var settings = $(".data_access_obj .relationship .settings");
	
	settings.draggable({
		axis: "y",
		appendTo: 'body',
		cursor: 'move',
          tolerance: 'pointer',
          handle: ' > .settings_header',
    		cancel: '.icon', //this means that is inside of .settings_header
		start: function(event, ui) {
			settings.addClass("resizing").removeClass("collapsed");
			settings.find(" > .settings_header .icon").addClass("minimize").removeClass("maximize");
			$(".top_bar header ul li.toggle_main_settings > a.toggle_icon").addClass("active");
			
			return true;
		},
		drag: function(event, ui) {
			var h = $(window).height() - (ui.offset.top - $(window).scrollTop());
			
			settings.css({
				height: h + "px",
				top: "", 
				left: "", 
				bottom: ""
			});
		},
		stop: function(event, ui) {
			var top = parseInt(ui.helper.css("top"));//Do not use ui.offset.top bc if the window has scrollbar, it will get the wrong top for the calculations inside of resizeSettingsPanel
			resizeSettingsPanel(settings, top);
		},
	});
}

//To be used in the toggleFullScreen function
function onToggleFullScreen(in_full_screen) {
	setTimeout(function() {
		var settings = $(".data_access_obj .relationship .settings");
		var top = parseInt(settings.css("top"));
		
		resizeSettingsPanel(settings, top);
	}, 500);
}

function resizeSettingsPanel(settings, top) {
	var icon = settings.find(" > .settings_header .icon");
	var wh = $(window).height();
	var top_bar_height = $(".top_bar header").outerHeight();
	var height = 0;
	
	settings.removeClass("resizing");
	settings.css({top: "", left: "", bottom: ""}); //remove top, left and bottom from style attribute in #settings_header
	
	if (top < top_bar_height) {
		height = wh - (top_bar_height + 5); //5 is the height of the #settings_header resize bar
		
		settings.css("height", height + "px");
	}
	else if (top > wh - 35) { //35 is the size of #settings .settings_header when collapsed
		icon.addClass("maximize").removeClass("minimize");
		$(".top_bar header ul li.toggle_main_settings > a.toggle_icon").removeClass("active");
		settings.addClass("collapsed");
		
		settings.css("height", ""); //remove height from style attribute in #settings
	}
	else {
		settings.css("height", (wh - top) + "px");
	}
}

function toggleQuery(elm) {
	elm = $(elm);
	
	var relationship_obj = elm.parent().parent();
	var query_obj = relationship_obj.children("div.query");
	var rel_type = relationship_obj.children(".rel_type").children("select").val();
	
	var extra_selector = rel_type == "insert" || rel_type == "update" || rel_type == "delete" ? "" : ", .result_class_id, .result_map_id";
	
	relationship_obj.children(".parameter_class_id, .parameter_map_id, div.query" + extra_selector).each(function(idx, child) {
		if($(query_obj).css("display") != "none") {
			$(child).slideUp("slow");
			elm.removeClass("active");
		}
		else {
			$(child).slideDown("slow");
			elm.addClass("active");
		}
	});
}

function showOrHideQuerySettings(elm, rand_number) {
	var settings = $(elm).parent().parent().parent().parent().parent().parent().children(".query_settings")[0];
	
	if (settings) {
		if($(settings).css("display") == "none") {//show
			$(settings).slideDown("slow");
			$(elm).addClass("active");
		}
		else {//hide
			$(settings).slideUp("slow");
			$(elm).removeClass("active");
		}
	}
}

function showOrHideQueryUI(elm, rand_number, options) {
	var tfc = $(elm).parent().parent().parent().parent();
	
	if (tfc[0]) {
		var wm = tfc.children(".workflow_menu");
		var tf = tfc.children(".tasks_flow");
		var h = parseInt(tfc.css("height"));
		
		if (!tfc[0].hasAttribute("original_height"))
			tfc.attr("original_height", h);
		
		var oh = parseInt(tfc.attr("original_height"));
		var callback = typeof options == "object" && typeof options["callback"] == "function" ? options["callback"] : null;
		
		//30px + margin-bottom = 33, but just in case we put 40
		if(h < 40) {//show 
			tfc.animate( { height: oh + "px" }, { queue:false, duration:500, always: callback });
			tf.slideDown("slow");
			
			repaintQueryTasks(rand_number);
		}
		else {//hide
			tf.slideUp("slow");
			tfc.animate( { height: (parseInt(wm.css("height")) + 5) + "px" }, { queue:false, duration:500, always: callback });
		}
	}
}

function showOrHideExtraQuerySettings(elm, rand_number) {
	var query_settings_tabs = $(elm).parent().children(".query_settings_tabs");
	
	if (query_settings_tabs.children(".query_settings_tabs_limit").css("display") == "none") {//it will show in the next code
		$(elm).addClass("active");
	}
	else {//it will hide in the next code
		$(elm).removeClass("active");
	}
	
	query_settings_tabs.children(".query_settings_tabs_conditions, .query_settings_tabs_group_by, .query_settings_tabs_sorting, .query_settings_tabs_limit").each(function(idx, tab_elm) {
		if($(tab_elm).css("display") == "none") {//show
			$(tab_elm).show();
		}
		else {//hide
			$(tab_elm).hide();
		}
	});
}

function repaintQueryTasks(rand_number) {
	eval('var WF = taskFlowChartObj_' + rand_number + ';');

	WF.TaskFlow.repaintAllTasks();
	WF.getMyFancyPopupObj().updatePopup();
}

function updateRelationshipType(elm, rand_number) {
	eval('var WF = taskFlowChartObj_' + rand_number + ';');
	var main_tasks_flow_obj = $("#" + WF.TaskFlow.main_tasks_flow_obj_id);
	
	if (main_tasks_flow_obj.attr("sync_ui_and_settings") == 1) {
		var rel_type = $(elm).val();
		
		var relationship_obj = $(elm).parent().parent();
		relationship_obj.removeClass("query_insert query_update query_delete query_select query_procedure");
		relationship_obj.addClass("query_" + rel_type.toLowerCase());
		
		if (rel_type == "select") {
			repaintQueryTasks(rand_number);
		}
		else {
			var query_obj = relationship_obj.children(".query");
			
			if (rel_type == "insert") {
				var query_insert_update_delete = query_obj.children("div").children(".query_insert_update_delete");
				query_insert_update_delete.children(".conditions").children("table").children(".fields").html("");
				query_insert_update_delete.children(".query_insert_update_delete_tabs").children(".query_insert_update_delete_tabs_attributes").children("a").click();
			}
			else if (rel_type == "delete") {
				var query_insert_update_delete = query_obj.children("div").children(".query_insert_update_delete");
				query_insert_update_delete.children(".attributes").children("table").children(".fields").html("");
				query_insert_update_delete.children(".query_insert_update_delete_tabs").children(".query_insert_update_delete_tabs_conditions").children("a").click();
			}
			else if (rel_type == "procedure") {
				query_obj.children(".query_tabs").children(".query_sql_tab").children("a").click();
			}
		}
	}
	
	if (main_tasks_flow_obj.attr("sync_ui_settings_with_sql") == 1)
		autoUpdateSqlFromUI(rand_number);
}
	
function prepareQuerySqlFromUIOrViceVersa(tab_elm, rand_number, do_not_confirm) {
	var is_repeated = tab_elm.attr("is_repeated");
	
	if (is_repeated != 1) {
		var loading = tab_elm.children(".loading");
		if (!loading[0]) {
			loading = $('<div class="icon loading"></div>');
			tab_elm.append(loading);
		}
		else
			loading.show();
	
		var ul = tab_elm.parent().parent();
		var query_tabs_overlay = $('<div class="query_tabs_overlay"></div>');
		query_tabs_overlay.insertBefore(ul);//this is to don't create incompatibilities when click between tabs very fast and this function didn't finished yet. The query_tabs_overlay will fix this issue.
	
		tab_elm.attr("is_repeated", 1);
		
		var is_sql_tab = tab_elm.parent().hasClass("query_sql_tab");
		if (is_sql_tab) {
			ul.children(".query_design_tab").children("a").attr("is_repeated", 0);
			createSqlFromUI(ul, rand_number, do_not_confirm);
		}
		else {
			ul.children(".query_sql_tab").children("a").attr("is_repeated", 0);
			createUIFromSql(ul, rand_number, do_not_confirm);
		}
	
		query_tabs_overlay.remove();
		loading.hide();
	}
}

function createSqlFromUI(ul, rand_number, do_not_confirm, do_not_focus_sql) {
	eval('var WF = taskFlowChartObj_' + rand_number + ';');
	
	var query_sql_elm_selector = ul.children(".query_sql_tab").children("a").attr("href");
	var query_sql_elm = $(query_sql_elm_selector);
	
	var data = getQueryFieldsDataObj(rand_number);
	
	var old_obj_id = query_sql_elm.attr("obj");
	old_obj_id = old_obj_id ? old_obj_id : "";
	
	var new_obj_id = getDataObjId(data);
	
	var relationship_obj = ul.parent().parent();
	var new_rel_type = relationship_obj.children(".rel_type").children("select").val();
	var old_rel_type = query_sql_elm.attr("rel_type");
	
	//console.log("OBJ:"+old_obj_id+"#####"+new_obj_id+"### IS EQUAL: "+(old_obj_id == new_obj_id));
	//console.log(data);
	
	var can_continue = data.attributes.length != 0 || data.conditions.length != 0 || data.groups_by.length != 0 || data.keys.length != 0;
	
	if (can_continue && (old_obj_id != new_obj_id || (old_rel_type && new_rel_type != old_rel_type))) {
		if (do_not_confirm || auto_convert || confirm("The system will now create a sql query based in the fields that you inserted in the UI.\nDo you wish to proceed?")) {
			//get selected db broker and db driver and add them to get_sql_from_query_obj
			var popup = relationship_obj.find(".choose_table_or_attribute").first();
			var db_broker = popup.find(".db_broker select").val();
			var db_driver = popup.find(".db_driver select").val();
			var url = get_sql_from_query_obj.replace("#db_broker#", db_broker ? db_broker : "").replace("#db_driver#", db_driver ? db_driver : "");
			url += (url.indexOf("?") != -1 ? "" : "?") + "&time=" + (new Date()).getTime();
			
			$.ajax({
				type : "post",
				url : url,
				data : {"obj" : data},
				dataType : "text",
				success : function(data, textStatus, jqXHR) {
					var sql = data ? data : "";
					
					query_sql_elm.attr("sql", $.md5(sql));
					query_sql_elm.attr("obj", new_obj_id);
					query_sql_elm.attr("rel_type", new_rel_type);
					
					setQuerySqlEditorValue(query_sql_elm_selector, sql, do_not_focus_sql);
				},
				error : function(jqXHR, textStatus, errorThrown) { 
					if (jqXHR.responseText)
						StatusMessageHandler.showError(jqXHR.responseText);
				},
				async: false,
			});
		}
	}
}

function createUIFromSql(ul, rand_number, do_not_confirm) {
	var query_sql_elm_selector = ul.children(".query_sql_tab").children("a").attr("href");
	var query_sql_elm = $(query_sql_elm_selector);
	
	var new_sql = getQuerySqlEditorValue(query_sql_elm);
	new_sql = new_sql ? new_sql : "";
	
	var old_sql_id = query_sql_elm.attr("sql");
	old_sql_id = old_sql_id ? old_sql_id : "";
	
	var new_sql_id = new_sql != "" ? $.md5(new_sql) : "";
	
	var relationship_obj = ul.parent().parent();
	var new_rel_type = relationship_obj.children(".rel_type").children("select").val();
	var old_rel_type = query_sql_elm.attr("rel_type");
	
	//console.log("SQL:"+old_sql_id+"#####"+new_sql_id+"### IS EQUAL: "+(old_sql_id == new_sql_id)+"### NEW SQL: "+new_sql);
	
	eval('var WF = taskFlowChartObj_' + rand_number + ';');
	var main_tasks_flow_obj = $("#" + WF.TaskFlow.main_tasks_flow_obj_id);
	
	if (!old_sql_id && !new_sql) {
		WF.getMyFancyPopupObj().hidePopup();
	}
	else if (old_sql_id != new_sql_id || (old_rel_type && new_rel_type != old_rel_type)) {
		if (do_not_confirm || auto_convert || confirm("The system will now create a UI based in the sql query that you wrote.\nDo you wish to proceed?")) {
			//CHECKS IF EXISTS ANY INNER SELECT STATEMENT
			var new_sql_without_comments = new_sql.replace(/^--.*$/gm, '').replace(/(^\s+|\s+$)/g, ""); //regex to remove all comments that starts with "--" in each line. "m" regex flag is the multi line flag.
			var aux = new_sql_without_comments.toLowerCase().replace("select", "");//only removes 1st select
			var inner_select = aux.indexOf("select") != -1;
			
			if (!inner_select || !do_not_confirm || confirm("ATTENTION: Apparently this sql query contains inner select statements, but the UI doesn't have this feature.\nDo you still wish to continue?")) {
				//get selected db broker and db driver and add them to get_query_obj_from_sql
				var popup = relationship_obj.find(".choose_table_or_attribute").first();
				var db_broker = popup.find(".db_broker select").val();
				var db_driver = popup.find(".db_driver select").val();
				var url = get_query_obj_from_sql.replace("#db_broker#", db_broker ? db_broker : "").replace("#db_driver#", db_driver ? db_driver : "");
				url += (url.indexOf("?") != -1 ? "" : "?") + "&time=" + (new Date()).getTime();
				
				//PREAPRING UI
				$.ajax({
					type : "post",
					url : url,
					data : {"sql" : new_sql},
					dataType : "json",
					success : function(data, textStatus, jqXHR) {
						data = data ? data : {};
						var main_table = data.table ? data.table : "";
						var rel_type = data.type ? data.type : "";
						//console.log(new_sql);
						//console.log(data);
						
						//UPDATING REL TYPE
						if (rel_type != "") {
							var select_type = relationship_obj.children(".rel_type").children("select");
							select_type.val(rel_type);
							
							//update relationship type, but before disable the sync_ui_settings_with_sql
							var sync_ui_settings_with_sql = main_tasks_flow_obj.attr("sync_ui_settings_with_sql");
							main_tasks_flow_obj.attr("sync_ui_settings_with_sql", 0);
							updateRelationshipType(select_type[0], rand_number);
							main_tasks_flow_obj.attr("sync_ui_settings_with_sql", sync_ui_settings_with_sql);
							
							//UPDATING QUERY SETTINGS
							var query_settings;
							
							if (rel_type == "select")
								query_settings = relationship_obj.children(".query").children("div").children(".query_select").children(".query_settings");
							else {
								query_settings = relationship_obj.children(".query").children("div").children(".query_insert_update_delete");
								
								query_settings.children(".query_table").children("input").val(main_table);
							}
							
							updateQuerySettingsFields(data, query_settings, rel_type, rand_number);
						}
						else {
							relationship_obj.children(".query").children("div").children(".query_select").children(".query_settings").children("div").children("table").children(".fields").html("");
							relationship_obj.children(".query").children("div").children(".query_insert_update_delete").children("div").children("table").children(".fields").html("");
						}
						
						//UPDATING QUERY UI, QUERY OBJ ID AND SQL ID 
						var new_ui_data = getQueryFieldsDataObj(rand_number, main_table);
						var new_ui_data_obj_id = getDataObjId(new_ui_data);
						var old_ui_data_obj_id = query_sql_elm.attr("obj");
						
						if (rel_type == "" || (rel_type == "select" && old_ui_data_obj_id != new_ui_data_obj_id))
							updateQueryUITableFromQuerySettings(rand_number, main_table);
						
						query_sql_elm.attr("obj", new_ui_data_obj_id);
						query_sql_elm.attr("sql", new_sql_id);
						query_sql_elm.attr("rel_type", relationship_obj.children(".rel_type").children("select").val() );
					},
					error : function(jqXHR, textStatus, errorThrown) { 
						if (jqXHR.responseText)
							StatusMessageHandler.showError(jqXHR.responseText);
					},
					async: false,
				});
			}
		}
	}
}

function autoUpdateSqlFromUI(rand_number) {
	eval('var WF = taskFlowChartObj_' + rand_number + ';');
	var main_tasks_flow_obj = $("#" + WF.TaskFlow.main_tasks_flow_obj_id);
	var query = main_tasks_flow_obj.parent().closest(".query");
	var ul = query.children(".query_tabs");
	
	createSqlFromUI(ul, rand_number, true, true);
}

function autoUpdateUIFromSql(rand_number) {
	eval('var WF = taskFlowChartObj_' + rand_number + ';');
	var main_tasks_flow_obj = $("#" + WF.TaskFlow.main_tasks_flow_obj_id);
	var query = main_tasks_flow_obj.parent().closest(".query");
	var ul = query.children(".query_tabs");
	
	createUIFromSql(ul, rand_number, true);
}

function updateQuerySettingsFields(data, query_settings, rel_type, rand_number) {
	var start = data.start || data.start == 0 ? data.start : "";
	var limit = data.limit || data.start == 0 ? data.limit : "";
	
	var limit_start_obj = query_settings.children(".limit_start").children("div");
	limit_start_obj.children(".start").children("input").val(start);
	limit_start_obj.children(".limit").children("input").val(limit);
	
	var types = ["attributes", "keys", "conditions", "groups_by", "sorts"];
	
	for (var i = 0; i < types.length; i++) {
		var type = types[i];
		var items = data[type];
		
		if (items) {
			var table = query_settings.children("." + type).children("table");
			var add_button = table.children(".fields_title").children("tr").children(".icon_cell").children(".add");
			var fields = table.children(".fields");
			fields.html("");
			
			var func = updateQuerySettingsFieldsTypeAddFunction(type, rel_type != "select");
			
			for (var j = 0; j < items.length; j++) {
				var item = items[j];

				if (item) {
					var last = func(add_button[0], rand_number);
					
					for (var attr_name in item) {
						var value = item[attr_name];

						last.children("." + attr_name).children("input").val(value);
						
						var select = last.children("." + attr_name).children("select");
						select.val(value);
						
						if (select.val() != value)
							select.val(("" + value).toLowerCase());//in case it exists select, intead of input.
					}
				}
			}
		}
	}
}

function updateQuerySettingsFieldsTypeAddFunction(type, is_insert_update_or_delete) {
	switch(type) {
		case "attributes": return is_insert_update_or_delete ? addQueryAttribute2 : addQueryAttribute1;
		case "keys": return addQueryKey;
		case "conditions": return is_insert_update_or_delete ? addQueryCondition2 : addQueryCondition1;
		case "groups_by": return addQueryGroupBy;
		case "sorts": return addQuerySort;
	}
	
	return null;
}

function updateQueryUITableFromQuerySettings(rand_number, main_table) {
	eval('var WF = taskFlowChartObj_' + rand_number + ';');
	
	if ($("#" + WF.TaskFlow.main_tasks_flow_obj_id).attr("sync_ui_and_settings") == 1) {
		var settings = $("#" + WF.TaskFlow.main_tasks_flow_obj_id).parent().parent().parent().children(".query_settings");
		//console.log(settings);
		
		if (settings) {
			var tables = {};
			var connections = {};
			var main_table_regex = main_table ? new RegExp("^" + main_table + "($|\s)") : null;
			//console.log(main_table_regex);
			
			//PREPARING DBQueryTaskPropertyObj - REMOVING THE CALLBACKS
			myWFObj.setTaskFlowChart(WF);
			
			var bkp_task_func = DBQueryTaskPropertyObj.on_delete_table;
			var bkp_conn_func = DBQueryTaskPropertyObj.on_complete_connection_properties;
		
			DBQueryTaskPropertyObj.on_delete_table = null;
			DBQueryTaskPropertyObj.on_complete_connection_properties = null;
		
			//PREPARING TABLES TO CREATE
			var fields = settings.children("div").children("table").children(".fields").children(".field");
			var items = fields.find(".table input, .ptable input, .ftable input");
			for (var i = 0; i < items.length; i++) {
				var item = $(items[i]);
			
				var table_name = item.val().trim();
				var attr_name;
			
				var div = item.parent();
				var is_attributes = div.parent().parent().parent().parent().hasClass("attributes");
			
				if (div.hasClass("table"))
					attr_name = div.parent().find(".column input").val().trim();
				else if (div.hasClass("ptable")) 
					attr_name = div.parent().find(".pcolumn input").val().trim();
				else if (div.hasClass("ftable"))
					attr_name = div.parent().find(".fcolumn input").val().trim();
				
				if (table_name != "" && attr_name != "") {
					//console.log("check regex "+main_table_regex+" in table: '"+table_name+"'");
					if (main_table_regex && table_name.match(main_table_regex))
						main_table = table_name;
					
					if (!tables.hasOwnProperty(table_name))
						tables[table_name] = {};
					
					if (!tables[table_name].hasOwnProperty(attr_name) || is_attributes)
						tables[table_name][attr_name] = is_attributes;
				}
			}
		
			//PREPARING CONNECTIONS
			items = settings.children(".keys").children("table").children(".fields").children(".field");
			for (var i = 0; i < items.length; i++) {
				var item = $(items[i]);
			
				var ptable = item.find(".ptable input").val();
				var pcolumn = item.find(".pcolumn input").val();
				var ftable = item.find(".ftable input").val();
				var fcolumn = item.find(".fcolumn input").val();
				var column_value = item.find(".value input").val();
				var tables_join = item.find(".join input").val();
				var operator = item.find(".operator input").val();
				
				ptable = ptable ? ptable.trim() : default_db_table;
				ftable = ftable ? ftable.trim() : default_db_table;
				tables_join = tables_join ? tables_join : "inner";
				operator = operator ? operator : "=";
			
				var c_id = ptable + "_" + ftable + "_" + tables_join;
				var c_id_aux = ftable + "_" + ptable + "_" + tables_join;
				
				//check if already exists any similar connections and it if does, only adds the new props, inastead of creating a new connection.
				if (connections.hasOwnProperty(c_id_aux) && !connections.hasOwnProperty(c_id)) {
					c_id = c_id_aux;
					
					ft = ftable;
					fc = fcolumn;
					ftable = ptable;
					fcolumn = pcolumn;
					ptable = ft;
					pcolumn = fc;
				}
				
				if (!connections.hasOwnProperty(c_id))
					connections[c_id] = {
						"source_table": ptable, 
						"target_table": ftable, 
						"tables_join": tables_join, 
						"source_columns": [], 
						"target_columns": [],  
						"column_values": [],
						"operators": []
					};
			
				connections[c_id]["source_columns"].push(pcolumn);
				connections[c_id]["target_columns"].push(fcolumn);
				connections[c_id]["column_values"].push(column_value);
				connections[c_id]["operators"].push(operator);
			}
		
			var tasks = WF.TaskFlow.getAllTasks();
			var task_ids = {};
		
			//GETTING EXISTENT TASKS
			var existent_tasks = {};
			var is_ui_empty = tasks.length == 0;
		
			for (var i = 0; i < tasks.length; i++) {
				var task = $(tasks[i]);
				var table_name = WF.TaskFlow.getTaskLabel(task);
				
				existent_tasks[table_name] = false;
				task_ids[table_name] = task.attr("id");
				
				//in case the settings panel contains something like 'count(*)' where there is no table name, we must add it manually here, otherwise that table will be deleted on the code below. Here is a query example of a sql where this happens: "SELECT count(*) FROM item".
				if (task.is(".is_start_task") && !tables.hasOwnProperty(table_name)) {
					tables[table_name] = {};
					
					if (main_table_regex && table_name.match(main_table_regex)) {
						//console.log("check regex "+main_table_regex+" in table: '"+table_name+"'");
						main_table = table_name;
					}
				}
			}
			
			//SETTING MAIN TABLE AS DEFAULT TABLE TO BE CREATED IF EXISTS
			if (main_table && !tables.hasOwnProperty(main_table)) {
				//console.log("main table does not exists");
				tables[main_table] = {};
			}
			
			//console.log("tables:");
			//console.log(tables);
			//console.log(main_table);
			
			//ADDING NEW TABLES
			for (var table_name in tables) {
				var attributes = tables[table_name];
				
				if (!existent_tasks.hasOwnProperty(table_name)) {
					//console.log("table is new:"+table_name);
					task_ids[table_name] = addNewTable(rand_number, table_name, attributes);
				}
				else {
					//console.log("existent table:"+table_name);
					existent_tasks[table_name] = true;
			
					var data = {
						table_name : table_name,
						table_attr_names : attributes,
					}
					DBQueryTaskPropertyObj.prepareTableAttributes(task_ids[table_name], data, rand_number);
				}
			}
		
			//DELETING OLD TABLES
			for (var table_name in existent_tasks)
				if (!existent_tasks[table_name])
					DBQueryTaskPropertyObj.deleteTable(task_ids[table_name], false);
			
			//REORDER TASKS' POSITIONS
			var top = 20;
			var left = 10;
			var attr_count = 0;
		
			for (var table_name in tables) {
				var task_id = task_ids[table_name];
				var task = $("#" + WF.TaskFlow.main_tasks_flow_obj_id + " #" + task_id);
			
				task.css({top: top + "px", left: left + "px"});
			
				var total = 0;
				var attributes = tables[table_name];
				for (var attr_name in attributes)
					total++;
			
				attr_count = total > attr_count ? total : attr_count;
		
				left += 250;
				if (left > 800) {
					top += attr_count * 18 + 60;
					left = 10;
				}
			}
		
			//PREPARING CONNECTIONS
			var existent_connections = WF.TaskFlow.getConnections();
			var existent_connections_id = {};
			
			for (var c_id in connections) {
				var props = connections[c_id];
			
				var source_task_id = task_ids[ props.source_table ];
				var target_task_id = task_ids[ props.target_table ];
			
				if (source_task_id && target_task_id) {
					var connection_id = null;
				
					for (var i in existent_connections) {
						var c = existent_connections[i];
			
						if (c.sourceId == source_task_id && c.targetId == target_task_id) {
							var c_props = WF.TaskFlow.connections_properties[c.id];
						
							if (c_props["tables_join"] == props["tables_join"]) {
								connection_id = c.id;
								break;
							}
						}
					}
				
					if (connection_id) {
						existent_connections_id[connection_id] = true;
					
						WF.TaskFlow.connections_properties[connection_id] = props;
					}
					else
						connectTables(rand_number, source_task_id, target_task_id, props);
				}
			}
			
			//DELETING OLD CONNECTIONS
			for (var i in existent_connections) {
				var c = existent_connections[i];
			
				if (c.id && !existent_connections_id[c.id]) 
					WF.TaskFlow.deleteConnection(c.id, true);
			}
		
			//PREPARING DBQueryTaskPropertyObj - ADDING THE CALLBACKS AGAIN
			DBQueryTaskPropertyObj.on_delete_table = bkp_task_func;
			DBQueryTaskPropertyObj.on_complete_connection_properties = bkp_conn_func;
		
			//UPDATING TABLES' ATTRIBUTES AUTOMATICALLY
			updateQueryDBBroker(rand_number, true);
			
			//SETTING START TASK FOR MAIN TABLE
			if (main_table || default_db_table) {
				var table_task_id = task_ids[main_table ? main_table : default_db_table];
				
				if (table_task_id)
					DBQueryTaskPropertyObj.setStartTaskById(table_task_id);
			}
			else {
				var tasks = $("#" + WF.TaskFlow.main_tasks_flow_obj_id + " ." + WF.TaskFlow.task_class_name);
				
				if (tasks.length > 0 && tasks.filter("." + WF.TaskFlow.start_task_class_name).length == 0)
					DBQueryTaskPropertyObj.setStartTaskById( tasks.first().attr("id") );
			}
				
			//REPAINT UI
			repaintQueryTasks(rand_number);
		}
	}
}

function getQueryFieldsDataObj(rand_number, main_table) {
	eval('var WF = taskFlowChartObj_' + rand_number + ';');
	
	var relationship_obj = $("#" + WF.TaskFlow.main_tasks_flow_obj_id).parent().parent().parent().parent().parent().parent();
	var rel_type = relationship_obj.children(".rel_type").children("select").val();
	var query_settings;
	var query_table;
	
	if (rel_type == "insert" || rel_type == "update" || rel_type == "delete") {
		query_settings = relationship_obj.children(".query").children("div").children(".query_insert_update_delete");
		query_table = query_settings.children(".query_table").children("input").val();
	}
	else {
		query_settings = relationship_obj.children(".query").children("div").children(".query_select").children(".query_settings");
		
		if (main_table)
			query_table = main_table;
		else {
			var start_task = $("#" + WF.TaskFlow.main_tasks_flow_obj_id + " ." + WF.TaskFlow.task_class_name + "." + WF.TaskFlow.start_task_class_name);
			query_table = WF.TaskFlow.getTaskLabel(start_task);
		}
	}
	
	var tasks = getTasksByTableName(default_db_table, WF);
	var tb = tasks[0] ? WF.TaskFlow.getTaskLabel(tasks[0]) : default_db_table;
	
	var data = {
		"type": rel_type,
		"attributes": [],
		"keys": [],
		"conditions": [],
		"groups_by": [],
		"sorts": [],
		"start": query_settings.find(".limit_start .start input").val(),
		"limit": query_settings.find(".limit_start .limit input").val(),
		"main_table": query_table ? query_table : tb
	}
	
	for (var type in data) {
		if (type != "start" && type != "limit") {
			var fields = query_settings.children("." + type).children("table").children(".fields").children(".field");
			
			for (var i = 0; i < fields.length; i++) {
				var field = $(fields[i]);
				var tds = field.children("td");
			
				var item = {};
				
				for (var j = 0; j < tds.length; j++) {
					var td = tds[j];
					var j_td = $(td);
					
					var attr_name = td.className;
					
					var input = j_td.children("input")[0];
					var select = j_td.children("select")[0];
					
					if (input || select) {
						var value = input ? $(input).val() : $(select).val();
					
						item[attr_name] = value;
					}
				}
				
				data[type].push(item);
			}
		}
	}
	
	return data;
}

function getDataObjId(data) {
	return $.md5(JSON.stringify(data));
}

function validateRelationshipName(elm) {
	var items = $(elm).parent().parent().parent().children().children(".rel_name").children("input");
	
	var str = "";
	for (var i = 0; i < items.length; i++) {
		var a = $(items[i]).val().trim();
		
		for (var j = i + 1; j < items.length; j++) {
			var b = $(items[j]).val().trim();
		
			if (a == b)
				str += "\n- " + a;
		}
	}
	
	if (str != "")
		alert("We detected some repeated relationships\' names.\nPlease check and change the following names so they can be unique please:" + str);
	
}

function setQuerySqlEditor(selector) {
	var elm = $(selector);
	var editor = elm.data("editor");
	
	if (!editor) {
		var textarea = elm.children("textarea")[0];
		
		if (textarea) {
			//prepare editor
			ace.require("ace/ext/language_tools");
			var editor = ace.edit(textarea);
			editor.setTheme("ace/theme/chrome");
			editor.session.setMode("ace/mode/sql");
    		editor.setAutoScrollEditorIntoView(true);
			editor.setOptions({
				enableBasicAutocompletion: true,
				enableSnippets: true,
				enableLiveAutocompletion: true,
			});
			editor.setOption("wrap", true);
			editor.getSession().setUseWrapMode(true);
			
			if (typeof setCodeEditorAutoCompleter == "function")
				setCodeEditorAutoCompleter(editor);
			
			//prepare special autocomplete
			var completer = {
				getCompletions: function(editor, session, pos, prefix, callback) {
					//console.log(sql_editor_completer_keywords);
					callback(null, sql_editor_completer_keywords);
				},
			};
			editor.completers.push(completer); //append to default completers
			
			// Default completers
			var default_completers = editor.completers;
			
			// Set up autocompletion switching logic
			editor.commands.on("afterExec", function (e) {
				if (e.command.name === "insertstring") {
					var char_typed = e.args;
					var table_attrs = [];
					
					if (char_typed === ".") {
						var cursor = editor.getCursorPosition(); // Current cursor position
						var line = editor.session.getLine(cursor.row); // Get the full line at the cursor's row
						var text_before_cursor = line.substring(0, cursor.column); // Extract characters up to the cursor position
						var match = text_before_cursor.match(/\b`?(\w+)`?\.$/);
						var last_word = match ? match[1] : null;
						
						if (last_word) {
							$.each(db_brokers_drivers_tables_attributes, function(db_broker, broker_drivers) {
								$.each(broker_drivers, function(db_driver, driver_types) {
									$.each(driver_types, function(db_type, db_tables) {
										$.each(db_tables, function(db_table, db_attributes) {
											if (db_table == last_word || db_table.toLowerCase() == last_word.toLowerCase())
												$.each(db_attributes, function(idx, attr_name) {
													if ($.inArray(attr_name, table_attrs) == -1)
														table_attrs.push(attr_name);
												});
										});
									});
								});
							});
						}
					}
					
					// Switch completers based on the character typed. Note that the language_tools.setCompleters doesn't work.
					if (table_attrs.length > 0) { //if filter_by_table_attrs exists, only show table_attrs_completer
						var table_completions = [];
						
						$.each(table_attrs, function(idx, attr_name) {
							table_completions.push({
								caption: attr_name,
								value: attr_name,
								meta: "attribute",
								score: 10
							});
						});
						
						var table_attrs_completer = {
							getCompletions: function(editor, session, pos, prefix, callback) {
								callback(null, table_completions);
							},
						};
						editor.completers = [table_attrs_completer];
					}
					else
						editor.completers = default_completers;
						
					// Trigger autocomplete but if not white space
					var is_white_space = typeof char_typed == "string" && char_typed.match(/\s/);
					
					if (!is_white_space)
						editor.execCommand("startAutocomplete");
				}
			});
			
			//prepare chatbot
			editor.system_message = getCodeChatBotSystemMessage;
			editor.showCodeEditorChatBot = openCodeChatBot;
			
			//set blur function for sql editor
			editor.on("blur", function() {
				var rand_number = elm.parent().closest(".query").attr("rand_number");
				onBlurQuerySqlEditor(rand_number);
			});
			
			editor.renderer.setScrollMargin(5, 5); //set padding-top and bottom
			
			elm.find("textarea.ace_text-input").removeClass("ace_text-input"); //fixing problem with scroll up, where when focused or pressed key inside editor the page scrolls to top.
			
			elm.data("editor", editor);
		}
	}
}

function getQuerySqlEditor(selector) {
	var editor = $(selector).data("editor");
	
	return editor;
}

function onBlurQuerySqlEditor(rand_number) {
	if (rand_number) {
		eval('var WF = taskFlowChartObj_' + rand_number + ';');
		
		if ($("#" + WF.TaskFlow.main_tasks_flow_obj_id).attr("sync_sql_with_ui_settings") == 1)
			autoUpdateUIFromSql(rand_number);
	}
}

function setQuerySqlEditorValue(selector, value, do_not_focus_sql) {
	var editor = getQuerySqlEditor(selector);
	if (editor) {
		editor.setValue(value, 1);
		editor.resize();
		
		if (!do_not_focus_sql)
			editor.focus();
	}
	else {
		var textarea = $(selector).children("textarea").first();
		textarea.val(value);
		
		if (!do_not_focus_sql)
			textarea.focus();
	}
}

function getQuerySqlEditorValue(selector) {
	var editor = getQuerySqlEditor(selector);
	if (editor) {
		return editor.getValue();
	}
	else {
		var textarea = $(selector).children("textarea").first();
		return textarea.val();
	}
}
/** END: QUERY - UTILS FOR UI **/

/** START: QUERY - UTILS FOR THE DBQUERYTABLEHANDLER **/	
function onClickQueryAtributeCheckBox(checkbox, WF, rand_number) {
	checkbox = $(checkbox);
	var task = checkbox.parent().closest(".task");
	var settings = task.parent().closest(".taskflowchart").parent().parent().children(".query_settings")[0];
	
	var table_name = WF.TaskFlow.getTaskLabel(task);
	var attribute_name = checkbox.attr("attribute");
	var fields = $(settings).find(".attributes .fields");
	var items = $(fields).children(".field");
	
	var tasks = getTasksByTableName(default_db_table, WF);
	var tb = tasks[0] ? WF.TaskFlow.getTaskLabel(tasks[0]) : default_db_table;
	
	if (checkbox.is(':checked')) {
		var exists = false;
		
		for (var i = 0; i < items.length; i++) {
			var field = items[i];
			
			var table = $(field).find(".table input")[0].value.trim();
			var column = $(field).find(".column input")[0].value.trim();
			
			table = table == "" ? tb : table;
			
			if (table == table_name && column == attribute_name) {
				exists = true;
				break;
			}
		}
		
		if (!exists) {
			var html_obj = $(new_relationship_attribute1_html.replace(/#rand#/g, rand_number));
			$(fields).append(html_obj);
			
			onNewHtmlCallback(html_obj);
			
			items = $(fields).children(".field");
			var last_field = items[items.length - 1];
			
			$(last_field).find(".table input").val(table_name);
			$(last_field).find(".column input").val(attribute_name);
		}
	}
	else {
		for (var i = 0; i < items.length; i++) {
			var field = items[i];
			
			var table = $(field).find(".table input")[0].value.trim();
			var column = $(field).find(".column input")[0].value.trim();
			
			table = table == "" ? tb : table;
			
			if (table == table_name && column == attribute_name) {
				$(field).remove();
				break;
			}
		}
	}
	
	if ($("#" + WF.TaskFlow.main_tasks_flow_obj_id).attr("sync_ui_settings_with_sql") == 1)
		autoUpdateSqlFromUI(rand_number);
}

function onDeleteQueryTable(task, WF) {
	if (task[0]) {
		var table_name = getTaskTableName(task, WF);
		var table_name_alias = WF.TaskFlow.getTaskLabel(task);
		
		var settings = $("#" + WF.TaskFlow.main_tasks_flow_obj_id).parent().parent().parent().children(".query_settings");
		
		if (settings) {
			var tasks = getTasksByTableName(default_db_table, WF);
			var tb = tasks[0] ? WF.TaskFlow.getTaskLabel(tasks[0]) : default_db_table;
			
			settings.find("tbody .field td").each(function(idx, elm) {
				var j_elm = $(elm);
				if(j_elm.hasClass("table") || j_elm.hasClass("ptable") || j_elm.hasClass("ftable")) {
					var tn = j_elm.children("input").val().trim();
					
					if (table_name_alias == tn || (tn == "" && table_name == tb) ) {
						j_elm.parent().remove();
					}
				}
			});
			
			//auto update sql from ui
			var rand_number = settings.parent().closest(".query").attr("rand_number");
			
			if (rand_number && $("#" + WF.TaskFlow.main_tasks_flow_obj_id).attr("sync_ui_settings_with_sql") == 1)
				autoUpdateSqlFromUI(rand_number);
		}
		
		//If any table gets deleted reset the obj id, so if the user generates again a new diagram based in the same sql, it will work fine!
		var query_sql_elm_selector = $("#" + WF.TaskFlow.main_tasks_flow_obj_id).parent().closest(".query").find(" > .query_tabs > .query_sql_tab > a").attr("href");
		$(query_sql_elm_selector).attr("obj", "");
	}	
}

function prepareTableLabelSettings(WF, task_id, old_table_name, new_table_name) {
	var settings = $("#" + WF.TaskFlow.main_tasks_flow_obj_id).parent().parent().parent().children(".query_settings");
	
	if (settings[0]) {
		var tasks = getTasksByTableName(default_db_table, WF);
		var tb = tasks[0] ? WF.TaskFlow.getTaskLabel(tasks[0]) : default_db_table;
		
		settings.find(".fields .table input, .fields .ptable input, .fields .ftable input").each(function(idx, elm) {
			var table_name = elm.value.trim();
			
			if (table_name == old_table_name || (table_name == "" && tb == new_table_name)) {
				elm.value = new_table_name;
			}
		});
		
		//auto update sql from ui
		var rand_number = settings.parent().closest(".query").attr("rand_number");
		
		if (rand_number && $("#" + WF.TaskFlow.main_tasks_flow_obj_id).attr("sync_ui_settings_with_sql") == 1)
			autoUpdateSqlFromUI(rand_number);
	}
}

function prepareTablesRelationshipKeys(WF, connection, old_connection_property_values, new_connection_property_values) {
	//console.log(old_connection_property_values);
	//console.log(new_connection_property_values);
	
	var settings = $("#" + WF.TaskFlow.main_tasks_flow_obj_id).parent().parent().parent().find(".query_settings");
	var settings_fields = settings.find(".keys .fields");
	
	if (settings_fields[0] && (new_connection_property_values || old_connection_property_values)) {
		new_connection_property_values = !new_connection_property_values ? {} : new_connection_property_values;
		
		var tasks = getTasksByTableName(default_db_table, WF);
		var tb = tasks[0] ? WF.TaskFlow.getTaskLabel(tasks[0]) : default_db_table;
		
		var source_table = new_connection_property_values.source_table;
		var target_table = new_connection_property_values.target_table;
		
		source_table = source_table ? source_table : old_connection_property_values.source_table;
		target_table = target_table ? target_table : old_connection_property_values.target_table;
		
		var source_task = getTasksByTableName(source_table, WF);
		source_task = $(source_task[0]);
		var source_task_id = source_task.attr("id");
		
		var target_task = getTasksByTableName(target_table, WF);
		target_task = $(target_task[0]);
		var target_task_id = target_task.attr("id");
		
		var selected_connection_id = connection.id;
		var connections = WF.TaskFlow.getSourceConnections(source_task_id);
		
		var new_tables_join = new_connection_property_values.tables_join;
		
		/* START: DELETE OLD KEYS */
		//Based on the old_connection_property_values, delete all existent keys in the query_settings panel.
		//However only deletes the keys, if the same keys do NOT exist in another connections
		if (old_connection_property_values) {
			var old_tables_join = old_connection_property_values.tables_join;
			var exists_idx = [];
		
			//checking if keys already exist in another connections
			for (var j in old_connection_property_values.source_columns) {
				var source_column = old_connection_property_values.source_columns[j];
				var target_column = old_connection_property_values.target_columns[j];
				var column_value = old_connection_property_values.column_values[j];
				var operator = old_connection_property_values.operators[j];
			
				for (var i = 0; i < connections.length; i++) {
					var connection_id = connections[i].id;
			
					if (connection_id != selected_connection_id && connections[i].targetId == target_task_id) {
						var props = WF.TaskFlow.connections_properties[connection_id];
			
						if (props && props.tables_join == old_tables_join) {
							for (var w in props.source_columns) {
								if (props.source_columns[w] == source_column && props.target_columns[w] == target_column && props.column_values[w] == column_value && props.operators[w] == operator) {
									exists_idx[j] = true;
									break;
								}
							}
						}
				
						if (exists_idx.length >= old_connection_property_values.source_columns.length) {
							break;
						}
					}
				}
			}
		
			//checking if keys already exist in the new props
			var new_keys_ids = {}
			if (new_connection_property_values.source_columns) {
				for (var i in new_connection_property_values.source_columns) {
					var source_column = new_connection_property_values.source_columns[i];
					var target_column = new_connection_property_values.target_columns[i];
					var column_value = new_connection_property_values.column_values[i];
					var operator = new_connection_property_values.operators[i];
			
					new_keys_ids[source_column + "_" + target_column + "_" + column_value + "_" + operator + "_" + new_tables_join] = true;
				}
			}
			
			//removing correspondent keys from keys panel
			for (var i in old_connection_property_values.source_columns) {
				if (!exists_idx[i]) {
					var source_column = old_connection_property_values.source_columns[i];
					var target_column = old_connection_property_values.target_columns[i];
					var column_value = old_connection_property_values.column_values[i];
					var operator = old_connection_property_values.operators[i];
				
					//console.log(source_column + "_" + target_column + "_" + column_value + "_" + operator + "_" + old_tables_join +":"+new_keys_ids[source_column + "_" + target_column + "_" + column_value + "_" + operator + "_" + old_tables_join]);
					
					if (!new_keys_ids[source_column + "_" + target_column + "_" + column_value + "_" + operator + "_" + old_tables_join]) {
						var field = getQueryKeyFieldElement(settings_fields, source_table, target_table, source_column, target_column, column_value, old_tables_join, operator, tb);
				
						if (field && field[0]) {
							field.remove();
						}
					}
				}
			}
		}
		/* END: DELETE OLD KEYS */
		
		/* START: ADD NEW KEYS */
		//Based on the new_connection_property_values, add all the NON existent keys in the query_settings panel.
		//This is, only adds the keys, if the same keys do NOT exist already in the query_settings panel.
		if (new_connection_property_values.source_columns) {
			for (var i in new_connection_property_values.source_columns) {
				var source_column = new_connection_property_values.source_columns[i];
				var target_column = new_connection_property_values.target_columns[i];
				var column_value = new_connection_property_values.column_values[i];
				var operator = new_connection_property_values.operators[i];
			
				//checking if keys already exist in keys panel
				var field = getQueryKeyFieldElement(settings_fields, source_table, target_table, source_column, target_column, column_value, new_tables_join, operator, tb);
			
				//adding correspondent keys to keys panel
				if (!field) {
					settings_fields.parent().find(".fields_title .icon_cell .add").click();
					field = settings_fields.find(".field").last();
				
					field.find(".ptable input").val(source_table);
					field.find(".pcolumn input").val(source_column);
					field.find(".ftable input").val(target_table);
					field.find(".fcolumn input").val(target_column);
					field.find(".value input").val(column_value);
					field.find(".join select").val(new_tables_join);
					field.find(".operator select").val(operator);
				}
			}
		}
		/* END: ADD NEW KEYS */
		
		//auto update sql from ui
		var rand_number = settings.parent().closest(".query").attr("rand_number");
		
		if (rand_number && $("#" + WF.TaskFlow.main_tasks_flow_obj_id).attr("sync_ui_settings_with_sql") == 1)
			autoUpdateSqlFromUI(rand_number);
	}
}

function prepareTableStartTask(WF, task_id, task) {
	//auto update sql from ui
	var main_tasks_flow_obj = $("#" + WF.TaskFlow.main_tasks_flow_obj_id);
	var rand_number = main_tasks_flow_obj.parent().closest(".query").attr("rand_number");
	
	if (rand_number && main_tasks_flow_obj.attr("sync_ui_settings_with_sql") == 1)
		autoUpdateSqlFromUI(rand_number);
}
/** END: QUERY - UTILS FOR THE DBQUERYTABLEHANDLER **/

/*** START: QUERY - UTILS FOR THE DBQUERYTABLEHANDLER - UTILS ***/
function getQueryKeyFieldElement(settings_fields, source_table, target_table, source_column, target_column, column_value, tables_join, operator, tb) {
	//console.log("getQueryKeyFieldElement:"+source_table+", "+target_table+", "+source_column+", "+target_column+", "+column_value+", "+tables_join+", "+operator);
	
	var items = settings_fields.find(".field");
	
	for (var i = 0; i < items.length; i++) {
		var item = $(items[i]);
		
		var ptable = item.find(".ptable input").val().trim();
		var pcolumn = item.find(".pcolumn input").val().trim();
		var ftable = item.find(".ftable input").val().trim();
		var fcolumn = item.find(".fcolumn input").val().trim();
		var cv = item.find(".value input").val();
		var join_value = item.find(".join select").val();
		var op = item.find(".operator select").val();
		
		ptable = ptable != "" ? ptable : tb;
		ftable = ftable != "" ? ftable : tb;
		join_value = join_value != "" ? join_value : "inner";
		op = op != "" ? op : "=";
	
		//console.log("getQueryKeyFieldElement:"+ptable+" == "+source_table+" && "+ftable+" == "+target_table+" && "+pcolumn+" == "+source_column+" && "+fcolumn+" == "+target_column+" && "+cv+" == "+column_value+" && "+join_value+" == "+tables_join+" && "+op+" == "+operator);
		
		if (ptable == source_table && ftable == target_table && pcolumn == source_column && fcolumn == target_column && cv == column_value && join_value == tables_join && op == operator) {
			return item;
		}
	}
	
	return null;
}
/*** END: QUERY - UTILS FOR THE DBQUERYTABLEHANDLER - UTILS ***/	
/** END: QUERY - UTILS FOR THE DBQUERYTABLEHANDLER **/	

/** START: QUERY - UTIL **/
function deleteQueryKeyFromConnectionsProperties(rand_number, source_table, source_column, target_table, target_column, column_value, tables_join, operator) {
	eval('var WF = taskFlowChartObj_' + rand_number + ';');
	
	var source_task = getTasksByTableName(source_table, WF);
	source_task = $(source_task[0]);
	var source_task_id = source_task.attr("id");
	
	var target_task = getTasksByTableName(target_table, WF);
	target_task = $(target_task[0]);
	var target_task_id = target_task.attr("id");
	
	if (source_task_id && target_task_id && source_task_id != target_task_id) {
		var connections = WF.TaskFlow.getSourceConnections(source_task_id);
		var existent_connections = {};
	
		for (var i = 0; i < connections.length; i++) {
			var connection_id = connections[i].id;
			var props = WF.TaskFlow.connections_properties[connection_id];
		
			if (props && connections[i].targetId == target_task_id && props.tables_join == tables_join) {
				var new_source_columns = [];
				var new_target_columns = [];
				var new_column_values = [];
				var new_operators = [];
			
				for (var j in props.source_columns) {
					if (props.source_columns[j] != source_column || props.target_columns[j] != target_column || props.column_values[j] != column_value || props.operators[j] != operator) {
						new_source_columns.push( props.source_columns[j] );
						new_target_columns.push( props.target_columns[j] );
						new_column_values.push( props.column_values[j] );
						new_operators.push( props.operators[j] );
					}
				}
			
				props.source_columns = new_source_columns;
				props.target_columns = new_target_columns;
				props.column_values = new_column_values;
				props.operators = new_operators;
			
				WF.TaskFlow.connections_properties[connection_id] = props;
				
				if (new_source_columns.length == 0) {
					WF.TaskFlow.deleteConnection(connection_id, true);
				}
				else {
					existent_connections[connection_id] = true;
				}
			}
		}
	
		return existent_connections;
	}
	
	return null;
}

function addQueryKeyToConnectionsProperties(rand_number, source_table, source_column, target_table, target_column, column_value, tables_join, operator) {
	eval('var WF = taskFlowChartObj_' + rand_number + ';');
	
	var source_task = getTasksByTableName(source_table, WF);
	source_task = $(source_task[0]);
	var source_task_id = source_task.attr("id");
	
	var target_task = getTasksByTableName(target_table, WF);
	target_task = $(target_task[0]);
	var target_task_id = target_task.attr("id");
	
	if (source_task_id && target_task_id && source_task_id != target_task_id) {
		var connections = WF.TaskFlow.getSourceConnections(source_task_id);
	
		var existent_connections = deleteQueryKeyFromConnectionsProperties(rand_number, source_table, source_column, target_table, target_column, column_value, tables_join, operator);
	
		var exists = false;
		for (var connection_id in existent_connections) {
			var props = WF.TaskFlow.connections_properties[connection_id];
		
			props.source_columns.push(source_column);
			props.target_columns.push(target_column);
			props.column_values.push(column_value);
			props.operators.push(operator);
				
			WF.TaskFlow.connections_properties[connection_id] = props;
			
			exists = true;
		}
	
		if (!exists) {
			var exit_props = {
				source_table: source_table,
				target_table: target_table,
				tables_join: tables_join,
				source_columns: [source_column],
				target_columns: [target_column],
				column_values: [column_value],
				operators: [operator],
			};
		
			var connection = connectTables(rand_number, source_task_id, target_task_id, exit_props);
		
			if (connection && connection.id) {
				existent_connections[connection.id] = true;
			}
		}
	
		return existent_connections;
	}
	
	return null;
}

function getDBTables(db_broker, db_driver, type) {
	var db_tables = db_brokers_drivers_tables_attributes[db_broker] && db_brokers_drivers_tables_attributes[db_broker][db_driver] ? db_brokers_drivers_tables_attributes[db_broker][db_driver][type] : null;
	
	if (!db_tables || jQuery.isEmptyObject(db_tables)) {
		var url = get_broker_db_data_url;
		url += (url.indexOf("?") != -1 ? "" : "?") + "&time=" + (new Date()).getTime();
		
		$.ajax({
			type : "post",
			url : url,
			data : {"db_broker" : db_broker, "db_driver" : db_driver, "type" : type},
			dataType : "json",
			success : function(data, textStatus, jqXHR) {
				if(data) {
					db_tables = {};
					
					for (var i = 0; i < data.length; i++)
						db_tables[ data[i] ] = {};
					
					if (!$.isPlainObject(db_brokers_drivers_tables_attributes[db_broker]))
						db_brokers_drivers_tables_attributes[db_broker] = {};
					
					if (!$.isPlainObject(db_brokers_drivers_tables_attributes[db_broker][db_driver]))
						db_brokers_drivers_tables_attributes[db_broker][db_driver] = {};
					
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

function getDBAttributes(db_broker, db_driver, type, db_table) {
	var parts = db_table.split(" ");
	db_table = parts[0];
	
	var db_attributes = db_brokers_drivers_tables_attributes[db_broker] && db_brokers_drivers_tables_attributes[db_broker][db_driver] && db_brokers_drivers_tables_attributes[db_broker][db_driver][type] ? db_brokers_drivers_tables_attributes[db_broker][db_driver][type][db_table] : null;

	if (!db_attributes || jQuery.isEmptyObject(db_attributes) || (db_attributes && !db_attributes.length)) {
		var url = get_broker_db_data_url;
		url += (url.indexOf("?") != -1 ? "" : "?") + "&time=" + (new Date()).getTime();
		
		$.ajax({
			type : "post",
			url : url,
			data : {"db_broker" : db_broker, "db_driver" : db_driver, "type" : type, "db_table" : db_table},
			dataType : "json",
			success : function(data, textStatus, jqXHR) {
				db_attributes = data && data.length > 0 ? data : [];
			
				if (!$.isPlainObject(db_brokers_drivers_tables_attributes[db_broker]))
					db_brokers_drivers_tables_attributes[db_broker] = {};
				
				if (!$.isPlainObject(db_brokers_drivers_tables_attributes[db_broker][db_driver]))
					db_brokers_drivers_tables_attributes[db_broker][db_driver] = {};
				
				if (!$.isPlainObject(db_brokers_drivers_tables_attributes[db_broker][db_driver][type]))
					db_brokers_drivers_tables_attributes[db_broker][db_driver][type] = {};
				
				db_brokers_drivers_tables_attributes[db_broker][db_driver][type][db_table] = db_attributes;
			},
			error : function(jqXHR, textStatus, errorThrown) { 
				if (jqXHR.responseText)
					StatusMessageHandler.showError(jqXHR.responseText);
			},
			async: false,
		});
	}
	
	return db_attributes;
}

function getDBTableAttributesDetailedInfo(db_broker, db_driver, type, db_table) {
	var parts = db_table.split(" ");
	db_table = parts[0];
	
	var detailed_info;
	
	var url = get_broker_db_data_url;
	url += (url.indexOf("?") != -1 ? "" : "?") + "&time=" + (new Date()).getTime();
	
	$.ajax({
		type : "post",
		url : url,
		data : {"db_broker" : db_broker, "db_driver" : db_driver, "type" : type, "db_table" : db_table, "detailed_info" : 1},
		dataType : "json",
		success : function(data, textStatus, jqXHR) {
			detailed_info = data ? data : {};
			
			if ($.isPlainObject(detailed_info) && !$.isEmptyObject(detailed_info) && (
				!db_brokers_drivers_tables_attributes.hasOwnProperty(db_broker) || 
				!db_brokers_drivers_tables_attributes[db_broker].hasOwnProperty(db_driver) || 
				!db_brokers_drivers_tables_attributes[db_broker][db_driver].hasOwnProperty(type) || 
				!db_brokers_drivers_tables_attributes[db_broker][db_driver][type].hasOwnProperty(db_table)
			)) {
				if (!$.isPlainObject(db_brokers_drivers_tables_attributes[db_broker]))
					db_brokers_drivers_tables_attributes[db_broker] = {};
				
				if (!$.isPlainObject(db_brokers_drivers_tables_attributes[db_broker][db_driver]))
					db_brokers_drivers_tables_attributes[db_broker][db_driver] = {};
				
				if (!$.isPlainObject(db_brokers_drivers_tables_attributes[db_broker][db_driver][type]))
					db_brokers_drivers_tables_attributes[db_broker][db_driver][type] = {};
				
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

function getBrokerHbnObjRelationships(db_broker, db_driver, type, db_table, with_maps, rel_type) {
	var relationships = {};
	
	var url = create_hbn_object_relationships_automatically_url;
	url += (url.indexOf("?") != -1 ? "" : "?") + "&time=" + (new Date()).getTime();
	
	$.ajax({
		type : "post",
		url : url,
		data : {"db_broker" : db_broker, "db_driver" : db_driver, "type" : type, "st" : [db_table], "with_maps" : with_maps, "rel_type" : rel_type},
		dataType : "json",
		success : function(data, textStatus, jqXHR) {
			relationships = data && data[db_table] ? data[db_table] : {};
		},
		error : function(jqXHR, textStatus, errorThrown) { 
			if (jqXHR.responseText)
				StatusMessageHandler.showError(jqXHR.responseText);
		},
		async: false,
	});
	
	return relationships;
}

function getTaskTableName(task, WF) {
	var label = WF.TaskFlow.getTaskLabel(task);
	
	var parts = label.split(" ");
	
	return parts[0];
}

//Note: table_name variable can have alias too
function getTasksByTableName(table_name, WF) {
	var new_tasks = [];
	
	var tasks = WF.TaskFlow.getAllTasks();
	
	for (var i = 0; i < tasks.length; i++) {
		var task = $(tasks[i]);
		var label = WF.TaskFlow.getTaskLabel(task);
		
		if (label == table_name) {
			new_tasks.push(task);
		}
	}
	
	if (new_tasks.length == 0) {
		for (var i = 0; i < tasks.length; i++) {
			var task = $(tasks[i]);
		
			var db_table = getTaskTableName(task, WF);
		
			if (db_table == table_name) {
				new_tasks.push(task);
			}
		}
	}
	
	return new_tasks;
}

function getTaskTableAttributes(task, WF) {
	var db_attrs = task.find("." + WF.TaskFlow.task_eps_class_name + " .table_attrs .table_attr .check input");
	
	var attrs = {};	
	for (var j = 0; j < db_attrs.length; j++) {
		var input = $(db_attrs[j]);
		
		attrs[ input.attr("attribute") ] = input.is(":checked");
	}
	
	return attrs;
}

function getParsedName(str) {
	var text = str.replace(/[\-_]+/g, " ");

	var parts = text.split(' ');
	var len = parts.length;
	var i;
	var words = [];
	
	for (i = 0; i < len; i++) {
	    var part = parts[i];
	    var first = part[0].toUpperCase();
	    var rest = part.substring(1, part.length);
	    var word = first + rest;
	    words.push(word);
	}

	return words.join('');
};
/** END: QUERY - UTIL **/

/* START: UPDATE AUTOMATICALLY */
function updateDataAccessObjectRelationshipsAutomatically(elm) {
	var target_field = $(elm).parent().find(".relationships_tabs .add_relationship")[0];
	
	updateRelationshipsAutomatically(elm, target_field, updateRelationshipsAutomaticallyForSelectedTable);
}

function updateRelationshipsAutomatically(elm, target_field, update_function) {
	var popup = $("#choose_db_table_or_attribute");
	
	popup.hide(); //This popup is shared with other actions so we must hide it first otherwise the user experience will be weird bc we will see the popup changing with the new changes.
	
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		onOpen: function() {
			popup.find(".db_attribute").hide();
			popup.find(".title").html("DB Table Selection");
			
			var select = popup.find(".db_table select");
			
			if (select.val() == "")
				select.val(default_db_table);
			
			var map_option = $('<div class="map_option"><label>With Maps:</label><input type="checkbox" value="1" /></div>');
			popup.find(".db_attribute").after(map_option);
		},
		onClose: function() {
			popup.find(".map_option").remove();
		},
		
		targetField: target_field,
		hideChooseAttributesField: true,
		updateFunction: function(sub_elm) {
			if (typeof update_function == "function")
				update_function(sub_elm, true);
		}
	});
	
	MyFancyPopup.showPopup();
}

function updateRelationshipsAutomaticallyForSelectedTable(elm, do_not_confirm) {
	MyFancyPopup.showLoading();
	$("#choose_db_table_or_attribute .button").hide();
	
	var p = $(elm).parent().parent();
	var db_broker = p.find(".db_broker select").val();
	var db_driver = p.find(".db_driver select").val();
	var type = p.find(".type select").val();
	var db_table = p.find(".db_table select").val();
	var with_maps = p.find(".map_option input").prop('checked');
	
	db_table = db_table ? db_table.trim() : "";
	
	if (db_table != "" && (do_not_confirm || confirm("This action may take a while.\nDo you wish to proceed?")) ) {
		/*if (do_not_confirm) {
			StatusMessageHandler.removeLastShownMessage("info", "bottom_messages", 1500);
			StatusMessageHandler.showMessage("This action may take a while...", "", "bottom_messages", 5000);
		}*/
		
		var data = getBrokerHbnObjRelationships(db_broker, db_driver, type, db_table, with_maps, "queries");
		//console.log(data);
		
		if (data) {
			var icon_add_relationship = $(MyFancyPopup.settings.targetField);
			var relationships_obj = icon_add_relationship.parent().parent();
			
			for (var rel_type in data) {
				if (rel_type == "parameter_map" || rel_type == "result_map") {
					updateRelationshipsMapsAutomatically(data[rel_type], rel_type, relationships_obj);
				}
				else {
					updateRelationshipsSqlStatementsAutomatically(data[rel_type], rel_type, icon_add_relationship);
				}
			}
		}
	}
	
	MyFancyPopup.hidePopup();
	$("#choose_db_table_or_attribute .button").show();
}

function getExistentRelationshipIds(icon_add_relationship) {
	var existent_ids = [];
	
	var items = icon_add_relationship.parent().children(".rels").children(".relationship");
	
	var rel_id = null;
	for (var i = 0; i < items.length; i++) {
		rel_id = $(items[i]).children(".rel_name").children("input").first().val().trim();
		if (rel_id)
			existent_ids.push(rel_id);
	}
	
	return existent_ids;
}

function getExistentMapIds(icon_add_map, map_type) {
	var existent_map_ids = [];
	
	var items = icon_add_map.parent().children("." + map_type + "s").children(".map");
	
	var map_id = null;
	for (var i = 0; i < items.length; i++) {
		map_id = $(items[i]).children(".map_id").children("input").first().val().trim();
		if (map_id) 
			existent_map_ids.push(map_id);
	}
	
	var tabs = icon_add_map.parent().parent().parent().parent().parent().parent();
	var map_type_tab = map_type == "result" ? tabs.children("#tabs-3") : tabs.children("#tabs-2");
	var input = map_type_tab.children("." + map_type + "s").children(".map").children(".map_id").children("input");
	var default_map_id = input.val();
	
	if (default_map_id)
		existent_map_ids.push(default_map_id);
	
	return existent_map_ids;
}

function updateRelationshipsMapsAutomatically(maps, rel_type, relationships_obj) {
	if (maps) {
		if (!$.isArray(maps)) {
			maps = [maps];
		}
	
		var icon_add_map;
		var map_type;
		var func;
		
		if (rel_type == "parameter_map") {
			icon_add_map = relationships_obj.children("div").children(".parameters_maps").children(".add");
			map_type = "parameter";
			func = addParameterMap;
		}
		else {
			icon_add_map = relationships_obj.children("div").children(".results_maps").children(".add");
			map_type = "result";
			func = addResultMap;
		}
		
		var existent_map_ids = getExistentMapIds(icon_add_map, map_type);
		//var maps_obj = icon_add_map.parent().children("." + map_type + "s");
		
		for (var i = 0; i < maps.length; i++) {
			var map = maps[i];
		
			if (map["id"] || map["result"] || map["parameter"]) {
				var map_id = map["id"] ? map["id"] : "";
			
				if (map_id == "" || $.inArray(map_id, existent_map_ids) == -1) {
					var map_obj = func(icon_add_map[0]);
					
					var fields_elm = map_obj.children("table").children(".fields");
					fields_elm.children().remove();//removes the default field
					
					map_obj.children(".map_id").children("input").val(map_id);
					map_obj.children(".map_class").children("input").val(map["class"] ? map["class"] : "");
					
					updateResultParameterMapFieldsHtml(map, map_type, fields_elm[0]);
				}
			}
		}
	}
}

function updateRelationshipsSqlStatementsAutomatically(relationships, rel_type, icon_add_relationship) {
	if (relationships) {
		if (!$.isArray(relationships)) {
			relationships = [relationships];
		}
	
		var existent_rel_ids = getExistentRelationshipIds(icon_add_relationship);
		//var relationships_obj = icon_add_relationship.parent().children(".rels");
		
		for (var i = 0; i < relationships.length; i++) {
			var relationship = relationships[i];
		
			if (relationship["value"]) {
				var sql_id = relationship["id"] ? relationship["id"] : "";
			
				if (sql_id == "" || $.inArray(sql_id, existent_rel_ids) == -1) {
					var sql = relationship["value"].replace(/\t/g, "").trim();
					//sql = sql.replace(" FROM ", "\n FROM ").replace(" WHERE ", "\n WHERE ").replace(" SET ", " SET\n ").replace(" VALUES ", "\n VALUES\n ").replace(/ AND /g, " AND\n    ");//No need anymore because the sql already comes with the right format.
				
					var aux = addRelationshipBlock(icon_add_relationship[0], 0, sql);
					var relationship_obj = aux[0];
					var rand_number = aux[1];
					
					var select = relationship_obj.children(".rel_type").children("select");
					select.val(rel_type);
					updateRelationshipType(select[0], rand_number);
					
					relationship_obj.children(".rel_name").children("input").val(sql_id);
					relationship_obj.children(".parameter_class_id").children("input").val( relationship["parameter_class"] ? relationship["parameter_class"] : "" );
					relationship_obj.children(".parameter_map_id").children("input").val( relationship["parameter_map"] ? relationship["parameter_map"] : "" );
					relationship_obj.children(".result_class_id").children("input").val( relationship["result_class"] ? relationship["result_class"] : "" );
					relationship_obj.children(".result_map_id").children("input").val( relationship["result_map"] ? relationship["result_map"] : "" );
					
					toggleQuery(relationship_obj.children(".header_buttons").children(".minimize")[0]);
				}
			}
		}
	}
}
/* END: UPDATE AUTOMATICALLY */

/* START: AI */
function openGenerateSQLPopup() {
	if (typeof manage_ai_action_url != "undefined") {
		var popup = $(".generate_sql");
		
		if (!popup[0]) {
			popup = $('<div class="myfancypopup generate_sql with_title">'
							+ '<div class="title">Generate SQL</div>'
							+ '<div class="instructions">'
								+ '<label>Please write in natural language which type of sql statement do you wish to generate:</label>'
								+ '<textarea placeHolder="eg: get all records"></textarea>'
							+ '</div>'
							+ '<div class="button">'
								+ '<button onClick="generateSQL(this)">Generate SQL</button>'
							+ '</div>'
						+ '</div>');
			$(document.body).append(popup);
			
			var choose_db_table_or_attribute = $("#choose_db_table_or_attribute");
			var cloned = choose_db_table_or_attribute.find(".contents").clone(true); //true: clone data events also
			cloned.children(".db_attribute").remove();
			
			popup.find(".title").after(cloned);
		}
		
		MyFancyPopup.init({
			elementToShow: popup,
			parentElement: document,
		});
		
		MyFancyPopup.showPopup();
	}
}
function generateSQL(elm) {
	var zindex = parseInt(MyFancyPopup.settings.elementToShow.css("z-index")) + 1;
	
	if (typeof manage_ai_action_url == "undefined") {
		StatusMessageHandler.showError("Manage AI Action url is not defined. Please talk with sysadmin");
		StatusMessageHandler.getMessageHtmlObj()[0].style.setProperty("z-index", zindex, "important"); //move error to front of filemanager popup
	}
	else if (!manage_ai_action_url) {
		StatusMessageHandler.showError("Artificial Intelligence is disabled. To enable it, please add your OpenAI Key in the 'Manage Permissions/Users' panel.");
		StatusMessageHandler.getMessageHtmlObj()[0].style.setProperty("z-index", zindex, "important"); //move error to front of filemanager popup
	}
	else {
		var p = $(elm).parent().parent();
		var button = p.find(".button");
		var db_broker = p.find(".db_broker select").val();
		var db_driver = p.find(".db_driver select").val();
		var type = p.find(".type select").val();
		var db_table = p.find(".db_table select").val();
		var instructions = p.find("textarea").val();
		
		if (!instructions) {
			StatusMessageHandler.showError("Please write what sql statement do you wish to create.");
			StatusMessageHandler.getMessageHtmlObj()[0].style.setProperty("z-index", zindex, "important"); //move error to front of filemanager popup
		}
		else {
			var msg = StatusMessageHandler.showMessage("AI loading. Wait a while...", "", "bottom_messages", 60000);
			StatusMessageHandler.getMessageHtmlObj()[0].style.setProperty("z-index", zindex, "important"); //move error to front of filemanager popup
			
			var ul = $(".data_access_obj .relationships .query > ul.tabs");
			var query_sql_elm_selector = ul.children(".query_sql_tab").children("a").attr("href");
			var query_sql_elm = $(query_sql_elm_selector);
			var editor = getQuerySqlEditor(query_sql_elm_selector);
			var system_instructions = getCodeChatBotSystemMessage(editor);
			var url = manage_ai_action_url + (manage_ai_action_url.indexOf("?") != -1 ? "" : "?") + "&action=generate_sql";
			
			MyFancyPopup.showLoading();
			button.hide();
			
			$.ajax({
				type : "post",
				url : url,
				data: {
					db_broker: db_broker,
					db_driver: db_driver,
					type: type,
					db_table: db_table,
					instructions: instructions,
					system_instructions: system_instructions
				},
				dataType : "json",
				success : function(data, textStatus, jqXHR) {
					//console.log(sql);
					MyFancyPopup.hideLoading();
					button.show();
					msg.remove();
					
					var sql = $.isPlainObject(data) && data.hasOwnProperty("sql") ? data["sql"] : null;
					
					if (sql) {
						var query = $(".data_access_obj .relationships .query");
						var ul = query.children("ul.tabs");
						var query_sql_elm_selector = ul.children(".query_sql_tab").children("a").attr("href");
						setQuerySqlEditorValue(query_sql_elm_selector, sql);
						
						var rand_number = query.attr("rand_number");
						onBlurQuerySqlEditor(rand_number);
						
						MyFancyPopup.hidePopup();
						p.find("textarea").val("");
					}
					else {
						StatusMessageHandler.showError("Error: Couldn't process this request with AI. Please try again...");
						StatusMessageHandler.getMessageHtmlObj()[0].style.setProperty("z-index", zindex, "important"); //move error to front of filemanager popup
					}
				},
				error : function(jqXHR, textStatus, errorThrown) {
					MyFancyPopup.hideLoading();
					button.show();
					msg.remove();
					
					if (jqXHR.responseText) {
						StatusMessageHandler.showError(jqXHR.responseText);
						StatusMessageHandler.getMessageHtmlObj()[0].style.setProperty("z-index", zindex, "important"); //move error to front of filemanager popup
					}
				},
			});
		}
	}
}

function explainSQL() {
	if (typeof manage_ai_action_url == "undefined")
		StatusMessageHandler.showError("Manage AI Action url is not defined. Please talk with sysadmin");
	else if (!manage_ai_action_url)
		StatusMessageHandler.showError("Artificial Intelligence is disabled. To enable it, please add your OpenAI Key in the 'Manage Permissions/Users' panel.");
	else {
		var ul = $(".data_access_obj .relationships .query > ul.tabs");
		var query_sql_elm_selector = ul.children(".query_sql_tab").children("a").attr("href");
		var query_sql_elm = $(query_sql_elm_selector);
		var sql = getQuerySqlEditorValue(query_sql_elm_selector);
		var url = manage_ai_action_url + (manage_ai_action_url.indexOf("?") != -1 ? "" : "?") + "&action=explain_sql";
		
		if (!sql)
			StatusMessageHandler.showMessage("There is no sql to comment...", "", "bottom_messages", 1500);
		else {
			var msg = StatusMessageHandler.showMessage("AI loading. Wait a while...", "", "bottom_messages", 60000);
			
			$.ajax({
				type : "post",
				url : url,
				processData: false,
				contentType: 'text/plain',
				data: sql,
				dataType : "html",
				success : function(message, textStatus, jqXHR) {
					//console.log(message);
					
					msg.remove();
					
					if (message) {
						var new_sql = "-- " + message.replace(/\n/g, "\n-- ") + "\n" + sql;
						setQuerySqlEditorValue(query_sql_elm_selector, new_sql);
						
						StatusMessageHandler.showMessage("SQL explanation:\n" + message + "\n\nSQL:\n" + sql, "", "", 600000); //1 hour
					}
					else
						StatusMessageHandler.showError("Error: Couldn't process this request with AI. Please try again...");
				},
				error : function(jqXHR, textStatus, errorThrown) {
					msg.remove();
					
					if (jqXHR.responseText)
						StatusMessageHandler.showError(jqXHR.responseText);
				},
			});
		}
	}
}

function openCodeChatBot() {
	if (typeof manage_ai_action_url == "undefined")
		StatusMessageHandler.showError("Manage AI Action url is not defined. Please talk with sysadmin");
	else if (!manage_ai_action_url)
		StatusMessageHandler.showError("Artificial Intelligence is disabled. To enable it, please add your OpenAI Key in the 'Manage Permissions/Users' panel.");
	else {
		var ul = $(".data_access_obj .relationships .query > ul.tabs");
		var query_sql_elm_selector = ul.children(".query_sql_tab").children("a").attr("href");
		var query_sql_elm = $(query_sql_elm_selector);
		var editor = getQuerySqlEditor(query_sql_elm_selector);
		
		if (editor)
			showCodeEditorChatBot(editor);
	}
}

function getCodeChatBotSystemMessage(editor) {
	var system_message = getCodeEditorChatBotDefaultSystemMessage(editor) + "\n\n";
	var tables = {};
		
	$.each(db_brokers_drivers_tables_attributes, function(db_broker, broker_drivers) {
		$.each(broker_drivers, function(db_driver, driver_types) {
			$.each(driver_types, function(db_type, db_tables) {
				$.each(db_tables, function(db_table, db_attributes) {
					if (db_table) {
						if (!tables.hasOwnProperty(db_table))
							tables[db_table] = $.isArray(db_attributes) ? db_attributes : [];
						else
							$.each(db_attributes, function(idx, attr_name) {
								if ($.inArray(attr_name, tables[db_table]) == -1)
									tables[db_table].push(attr_name);
							});
					}
				});
			});
		});
	});
	
	var query_type = $(".data_access_obj .relationships .rel_type select").val();
	
	if (query_type)
		system_message += "Query type to be generated: `" + query_type + "`";
	
	if (!$.isEmptyObject(tables)) {
		system_message += "\nTables:";
		
		$.each(tables, function(table_name, table_attributes) {
			system_message += "\n- `" + table_name + (table_attributes.length > 0 ? "` with attributes: `" + table_attributes.join("`, `") + "`" : "");
		});
	}
	
	return system_message;
}
/* END: AI */

/* START: SAVE ALL */
function onSuccessSingleQuerySave(obj, new_obj_id, data, options) {
	var new_obj_type = $(".data_access_obj .relationships .rel_type select").val();
	var is_type_different = old_obj_type && new_obj_type && old_obj_type.trim() != new_obj_type.trim();
	var is_id_different = old_obj_id && new_obj_id && old_obj_id.trim() != new_obj_id.trim();
	
	//If name or type is different, reload page with right name and type
	if (is_type_different || is_id_different) {
		if (window.parent && typeof window.parent.refreshLastNodeParentChilds == "function")
			window.parent.refreshLastNodeParentChilds();
		
		var url = "" + document.location;
		url = url.replace(/(&|\?)query_type=[^&]*/g, "$1query_type=" + new_obj_type);
		url = url.replace(/(&|\?)query_id=[^&]*/g, "$1query_id=" + new_obj_id);
		
		document.location = url;
	}
}

function saveQueryObject(on_success_callback) {
	var new_obj_id = $(".rel_name input").first().val();
	var options = on_success_callback ? {on_success: on_success_callback} : null;
	
	saveIbatisObject(new_obj_id, options);
}

function saveMapObject() {
	var new_obj_id = $(".map_id input").first().val();
	
	saveIbatisObject(new_obj_id);
}

function saveIbatisObject(new_obj_id, options) {
	var main_relationships_elm = $(".relationships");
	var obj = getUserRelationshipsObj(main_relationships_elm);
	
	var error_msg = validateUserRelationshipsObj(obj);
	
	if (error_msg == "") {
		obj = {"queries": obj};
	
		saveDataAccessObject(obj, new_obj_id, options);
	}
	else {
		prepareAutoSaveVars();
		
		if (!is_from_auto_save)
			StatusMessageHandler.showError("Error:" + error_msg);
		else
			resetAutoSave();
	}
}

function saveDataAccessObject(obj, new_obj_id, options) {
	prepareAutoSaveVars();
	
	var is_from_auto_save_bkp = is_from_auto_save; //backup the is_from_auto_save, bc if there is a concurrent process running at the same time, this other process may change the is_from_auto_save value.
	
	if (!window.is_save_func_running) {
		window.is_save_func_running = true;
		
		//console.log(obj);
		options = typeof options == "object" ? options : {};
		
		var new_user_relationships_obj_id = getUserRelationshipsObjId();
		
		//only saves if object is different
		if (saved_user_relationships_obj_id != new_user_relationships_obj_id || options["force"]) {
			//console.log(obj);
			
			var ajax_opts = {
				type : "post",
				url : save_data_access_object_url,
				data : {"object" : obj, "overwrite" : 1},
				dataType : "json",
				success : function(data, textStatus, jqXHR) {
					if(data == 1) {
						if (typeof options["on_success"] == "function")
							options["on_success"](obj, new_obj_id, data, options);
						
						var msg = "Saved successfully.";
						var msg_class = "bottom_messages";
						
						if (old_obj_id && new_obj_id && old_obj_id.trim() != new_obj_id.trim()) {
							if (removeDataAccessObject(old_obj_id))
								old_obj_id = new_obj_id.trim();
							else {
								msg += "\nHowever we couldn't replace this object with the new id/name that you inserted, so we created a duplicated object with the new id.\n\nTo remove the old object, please try to save again...";
								msg_class = null;
							}
							
							if (window.parent && typeof window.parent.refreshLastNodeParentChilds == "function")
								window.parent.refreshLastNodeParentChilds();
						}
						else if (!old_obj_id) {//it means it is a new object
							if (window.parent && typeof window.parent.refreshLastNodeParentChilds == "function")
								window.parent.refreshLastNodeParentChilds();
						}
						
						if (!is_from_auto_save_bkp) //only show message if a manual save action
							StatusMessageHandler.showMessage(msg, "", msg_class);
						
						//update saved_user_relationships_obj_id
						saved_user_relationships_obj_id = new_user_relationships_obj_id;
					}
					else
						StatusMessageHandler.showError("Error trying to save new changes.\nPlease try again..." + (data ? "\n" + data : ""));
					
					if (is_from_auto_save_bkp)
						resetAutoSave();
					
					window.is_save_func_running = false;
				},
				error : function(jqXHR, textStatus, errorThrown) { 
					if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL)) {
						if (!is_from_auto_save_bkp) {
							showAjaxLoginPopup(jquery_native_xhr_object.responseURL, save_data_access_object_url, function() {
								StatusMessageHandler.removeLastShownMessage("error");
								
								$.ajax(ajax_opts);
							});
						}
						else {
							resetAutoSave();
							window.is_save_func_running = false;
						}
					}
					else {
						if (is_from_auto_save_bkp)
							resetAutoSave();
						else {
							var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
							StatusMessageHandler.showError("Error trying to save new changes.\nPlease try again..." + msg);
						}
						
						window.is_save_func_running = false;
					}
				},
				timeout: is_from_auto_save_bkp && auto_save_connection_ttl ? auto_save_connection_ttl : 0,
			};
			
			$.ajax(ajax_opts);
		}
		else {
			if (!is_from_auto_save_bkp)
				StatusMessageHandler.showMessage("Nothing to save.", "", "bottom_messages", 1500);
			else
				resetAutoSave();
			
			window.is_save_func_running = false;
		}
	}
	else if (!is_from_auto_save_bkp)
		StatusMessageHandler.showMessage("There is already a saving process running. Please wait a few seconds and try again...");
	else
		resetAutoSave();
}

function removeDataAccessObject(obj_id) {
	var status = false;
	var url = remove_data_access_object_url;
	url += (url.indexOf("?") != -1 ? "" : "?") + "&time=" + (new Date()).getTime();
	
	$.ajax({
		type : "get",
		url : url.replace("#obj_id#", obj_id),
		dataType : "json",
		success : function(data, textStatus, jqXHR) {
			status = data == 1;
		},
		error : function(jqXHR, textStatus, errorThrown) { 
			if (jqXHR.responseText)
				StatusMessageHandler.showError(jqXHR.responseText);
		},
		async: false,
	});
	
	return status;
}

function getUserRelationshipsObjId() {
	var main_relationships_elm = $(".relationships");
	var obj = getUserRelationshipsObj(main_relationships_elm);
	return $.md5(JSON.stringify(obj));
}

function isUserRelationshipsObjChanged() {
	var new_user_relationships_obj_id = getUserRelationshipsObjId();
	
	return saved_user_relationships_obj_id != new_user_relationships_obj_id;
}

function getUserRelationshipsObj(main_relationships_elm) {
	var main_relationship_obj = {};
	
	var main_includes_elm = main_relationships_elm.find(".includes");
	var includes = getUserIncludesObj(main_includes_elm);
	if (includes.length > 0) {
		main_relationship_obj["import"] = includes;
	}
	
	var parameters = [];
	var items = main_relationships_elm.find(".parameters_maps .parameters .map");
	for (var i = 0; i < items.length; i++) {
		parameters.push( getUserMapObj( $(items[i]), "parameter" ) );
	}
	if (parameters.length > 0) {
		main_relationship_obj["parameter_map"] = parameters;
	}
	
	var results = [];
	var items = main_relationships_elm.find(".results_maps .results .map");
	for (var i = 0; i < items.length; i++) {
		results.push( getUserMapObj( $(items[i]), "result" ) );
	}
	if (results.length > 0) {
		main_relationship_obj["result_map"] = results;
	}
	
	var items = main_relationships_elm.find(".rels .relationship");
	for (var i = 0; i < items.length; i++) {
		var rel = getUserRelationshipObj( $(items[i]) );
		var rel_type = rel[0].toLowerCase();
		rel = rel[1];
		
		if (!main_relationship_obj.hasOwnProperty(rel_type)) {
			main_relationship_obj[rel_type] = [];
		}
		
		main_relationship_obj[rel_type].push(rel);
	}
	
	return main_relationship_obj;
}

function getUserRelationshipObj(relationship_elm) {
	var rel = {};
	
	var rel_type = relationship_elm.find(".rel_type select").val();
	var rel_name = relationship_elm.find(".rel_name input").val().trim();
	var result_class = relationship_elm.find(".result_class_id input").val().trim();
	var result_map = relationship_elm.find(".result_map_id input").val().trim();
	
	if (relationship_elm.find(".parameter_class_id input")[0]) {//HBN RELATIONSHIPS DON'T HAVE THIS FEATURE
		var parameter_class = relationship_elm.find(".parameter_class_id input").val().trim();
		var parameter_map = relationship_elm.find(".parameter_map_id input").val().trim();
		
		if (parameter_class != "")
			rel["parameter_class"] = parameter_class;
		
		if (parameter_map != "")
			rel["parameter_map"] = parameter_map;
	}
	
	if (result_class != "") 
		rel["result_class"] = result_class;
	
	if (result_map != "")
		rel["result_map"] = result_map;
	
	var main_type = rel_type == "insert" || rel_type == "update" || rel_type == "delete" || rel_type == "select" || rel_type == "procedure" ? "queries" : "relationships";
	
	var query = relationship_elm.find("div.query");
	var rand_number = query.attr("rand_number");
	
	if (main_type == "queries") {
		if (rel_name != "")
			rel["id"] = rel_name;
		
		var query_sql_tab = query.find(".query_tabs .query_sql_tab");
		if (!query_sql_tab.hasClass("ui-tabs-selected") && !query_sql_tab.hasClass("ui-tabs-active") && rel_type != "procedure")
			createSqlFromUI(query.children(".query_tabs").first(), rand_number, true, true);
		
		var sql_text_area = query.find(".sql_text_area");
		rel["value"] = getQuerySqlEditorValue(sql_text_area);
	}
	else {
		if (rel_name != "") 
			rel["name"] = rel_name;
		
		var fields = getQueryFieldsDataObj(rand_number);
		
		var types = {
			"attributes": "attribute",
			"keys": "key",
			"conditions": "condition",
			"groups_by": "group_by",
			"sorts": "sort",
		};
		
		for (var type in types) {
			var items = fields[type];
			
			if (items && items.length > 0) {
				var xml_type = types[type];
				rel[xml_type] = [];
				
				for (var i = 0; i < items.length; i++) 
					rel[xml_type].push(items[i]);
			}
		}
		
		if (fields["limit"]) 
			rel["limit"] = {"value": fields["limit"]};
		
		if (fields["start"]) 
			rel["start"] = {"value": fields["start"]};
	}
	
	return [rel_type, rel];
}

function getUserIncludesObj(main_includes_elm) {
	var includes = [];
	
	var items = main_includes_elm.find(".fields .include");
	for (var i = 0; i < items.length; i++) {
		var item = $(items[i]);
		
		var path = item.find("input.include_path").val().trim();
		
		if (path != "") {
			includes.push({
				"value": path, 
				"relative": item.find("input.is_include_relative").is(":checked") ? 1 : 0, 
			});
		}
	}
	
	return includes;
}

function getUserMapObj(map_elm, map_type) {
	var map_obj = {};
	
	var map_id = map_elm.find(".map_id input").val().trim();
	var map_class =  map_elm.find(".map_class input").val().trim();
	
	if (map_id != "") {
		map_obj["id"] = map_id;
	}
	if (map_class != "") {
		map_obj["class"] = map_class;
	}
	
	map_obj[map_type] = [];
	
	var fields = map_elm.find("table .fields .field");
	for (var i = 0; i < fields.length; i++) {
		var field = $(fields[i]);
		
		var f = {};
		
		var tds = field.children();
		for (var j = 0; j < tds.length; j++) {
			var td = tds[j];
			var j_td = $(td);
			
			var v = null;
			if (j_td.hasClass("input_name") || j_td.hasClass("output_name")) {
				v = j_td.children("input").val();
			}
			else if (j_td.hasClass("input_type") || j_td.hasClass("output_type")) {
				v = j_td.children("select").val();
			}
			else if (j_td.hasClass("mandatory")) {
				v = j_td.children("input").is(":checked") ? 1 : 0;
			}
			
			if (v != null) {
				v = typeof v != "undefined" ? v : "";
				
				f[ td.className ] = v;
			}
		}
		
		map_obj[map_type].push(f);
	}
	
	return map_obj;
}

function validateUserRelationshipsObj(main_relationships_obj) {
	var error_msg = "";
	
	for (var key in main_relationships_obj) {
		var attr_name = "";
		var msg = "";
		
		if (key == "import") {
			attr_name = "value";
			msg = "\n- Includes' paths cannot be empty or contains invalid characters";
		}
		else if (key == "parameter_map") {
			attr_name = "id";
			msg = "\n- Parameter maps' " + attr_name + "s cannot be empty or contains invalid characters";
		}
		else if (key == "result_map") {
			attr_name = "id";
			msg = "\n- Result maps' " + attr_name + "s cannot be empty or contains invalid characters";
		}
		else if (key == "insert" || key == "update" || key == "delete" || key == "select" || key == "procedure") {
			attr_name = "id";
			//msg = "\n- " + key + " relationships' " + attr_name + "s cannot be empty";
			msg = "\n- " + key + " query's name cannot be empty or contains invalid characters";
		}
		else if (key == "one_to_one" || key == "one_to_many" || key == "many_to_one" || key == "many_to_many") {
			attr_name = "name";
			msg = "\n- " + key + " relationships' " + attr_name + "s cannot be empty or contains invalid characters";
		}
		
		if (attr_name != "") {
			var items = main_relationships_obj[key];
			
			for (var i = 0; i < items.length; i++)
				if (!isUserRelationshipObjValid(items[i][attr_name])) {
					error_msg += msg;
					break;
				}
		}
	}
	
	return error_msg;
}

function isUserRelationshipObjValid(value) {
	if (!value || value == "")
		return false;
	
	value = "" + value
	value = value.replace(/^\s+/g, "").replace(/\s+$/g, ""); //trim()
	
	if (value == "")
		return false;
	
	//return value.match(/^([\p{L}\w \-]+)$/giu); //\p{L} and /../u is to get parameters with accents and ç. Already includes the a-z. Cannot use this bc it does not work in IE.
	return value.match(/^([\w\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u024F\u1EBD\u1EBC \-]+)$/gi); //'\w' means all words with '_' and 'u' means with accents and ç too.
}
/* END: SAVE ALL */
/* END: QUERY */
