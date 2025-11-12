/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

var chooseQueriesFromFileManagerTree = null;

$(function () {
	chooseQueriesFromFileManagerTree = new MyTree({
		multiple_selection : true,
		toggle_selection : true,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeQueriesAndMapsAndOtherHbnNodesFromTreeForBusinessLogicObjsAutomatically,
		on_select_callback : checkIfIsFileOrHbnObj,
	});
	chooseQueriesFromFileManagerTree.init("choose_queries_from_file_manager");
	
	var select = $("#choose_queries_from_file_manager .broker select")[0];
	
	if (select)
		onChangeDBBroker(select);
});

function submitForm(elm, on_submit_func) {
	elm = $(elm);
	var oForm = elm.parent().closest(".top_bar").parent().children("form");
	var status = typeof on_submit_func == "function" ? on_submit_func( oForm[0] ) : true;
	
	if (status) {
		var on_click = elm.attr("onClick");
		elm.addClass("loading").removeAttr("onClick");
		
		oForm.submit();
		
		/*setTimeout(function() {
			elm.removeClass("loading").attr("onClick", on_click);
		}, 2000);*/
	}
	
	return status;
}

function removeQueriesAndMapsAndOtherHbnNodesFromTreeForBusinessLogicObjsAutomatically(ul, data, mytree_obj) {
	$(ul).find("i.query, i.map, i.relationship, i.hbn_native, i.referenced_folder").each(function(idx, elm){
		var li = $(elm).parent().parent();
		
		if (li.next("li").length == 0)
			li.prev("li").addClass("jstree-last");
		
		li.remove();
	});
}

function checkIfIsFileOrHbnObj(node) {
	node = $(node);
	
	var a = node.children("a");
	var i = a.children("i");
	
	var is_hbn_obj = i.hasClass("obj");
	var status = i.hasClass("file") || is_hbn_obj || i.hasClass("import");
	
	var file_path = null;
	
	if (status) {
		var value = is_hbn_obj ? a.children("label").first().text() : "all";
		file_path = a.attr("file_path");
		
		var broker_name = a.attr("broker_name");
		
		if (a.hasClass("jstree-clicked")) {
			a.children("input[type='checkbox'], input[type='hidden'], span").remove();
		}
		else {
			var file_name = file_path.substring(file_path.lastIndexOf("/") + 1, file_path.lastIndexOf("."));
			var parts = file_name.replace(/[_\.]/g, " ").split(" ");
			var service_name = "";
			for (var i = 0; i < parts.length; i++)
				service_name += parts[i].charAt(0).toUpperCase() + parts[i].slice(1).toLowerCase();
			service_name += "Service";
			
			a.append('<input type="checkbox" name="files[' + file_path + '][' + value + ']" value="' + broker_name + '" checked /><input type="hidden" name="aliases[' + file_path + '][' + value + ']" value="" /><span title="Click here to enter a non default service name..."> => ' + service_name + '</span>');
			
			a.children("span").click(function(e){
				e.stopPropagation();
				addServiceAlias(this, service_name);
				return false;
			});
		}
	}
	
	return status && file_path;
}

function addServiceAlias(elm, service_name) {
	elm = $(elm);
	var clicked = elm.attr("clicked");
	elm.attr("clicked", "1");
	
	var p = elm.parent();
	var service_alias = p.children("input[type='hidden']").val();
	
	var alias = prompt("Please enter the new service name:", clicked && service_alias ? service_alias : service_name);
	
	if (typeof alias == "string") {
		alias = alias.replace(/ /g, "");
		
		if (alias == service_name)
			alias = "";
		
		p.children("input[type='hidden']").val(alias);
		
		var prefix = "";
		
		if (p.hasClass("table"))
			prefix = p.attr("table");
		
		elm.html(prefix + " => " + (alias != "" ? alias : service_name));
	}
}

function onChangeDBBroker(elm) {
	var option = elm.options[ elm.selectedIndex ];
	var broker_name = option.getAttribute("broker_name");
	var is_db_broker = option.getAttribute("is_db_broker") == 1;
	var choose_queries_from_file_manager = $(elm).parent().parent();
	var mytree = choose_queries_from_file_manager.children(".mytree");
	var tables = choose_queries_from_file_manager.children(".tables");
	var db_drivers = brokers_db_drivers_name.hasOwnProperty(broker_name) ? brokers_db_drivers_name[broker_name] : null;
	
	if (db_drivers) {
		var options = '';
		
		for (var db_driver_name in db_drivers) {
			var db_driver_props = db_drivers[db_driver_name];
			
			options += '<option value="' + db_driver_name + '">' + db_driver_name + (db_driver_props && db_driver_props.length > 0 ? '' : ' (Rest)') + '</option>'; 
		}
		
		choose_queries_from_file_manager.children(".db_driver").children("select").html(options);
	}
	
	if (is_db_broker) {
		tables.show();
		mytree.hide();
	}
	else {
		tables.hide();
		mytree.show();
		updateLayerUrlFileManager(elm);
	}
	
	onChangeDBDriver( choose_queries_from_file_manager.find(".db_driver select")[0] );
}

function onChangeDBDriver(elm) {
	updateDBTables(elm);
}

function onChangeDBType(elm) {
	updateDBTables(elm);
}

function updateDBTables(elm) {
	var p = $(elm).parent().parent();
	var db_broker = p.find(".broker select").val();
	var db_driver = p.find(".db_driver select").val();
	var type = p.find(".type select").val();
	var ul = p.find(".tables ul");
	
	ul.html("<li>Loading tables...</li>");
	
	if (db_broker && db_driver && type) {
		$.ajax({
			type : "post",
			url : get_broker_db_data_url,
			data : {"db_broker" : db_broker, "db_driver" : db_driver, "type" : type},
			dataType : "json",
			success : function(data, textStatus, jqXHR) {
				if(data) {
					var html = "";
					for (var i = 0; i < data.length; i++) {
						var table = data[i];
						
						var parts = ("" + table).replace(/[_\.]/g, " ").split(" ");
						var service_name = "";
						for (var j = 0; j < parts.length; j++)
							service_name += parts[j].charAt(0).toUpperCase() + parts[j].slice(1).toLowerCase();
						service_name += "Service";
						
						html += '<li class="table" table="' + table + '">' +
							'<input type="checkbox" name="files[' + table + '][all]" value="' + db_broker + '" />' +
							'<input type="hidden" name="aliases[' + table + '][all]" value="" />' +
							'<label title="Click here to enter a different table alias..." onClick="addServiceAlias(this, \'' + service_name + '\')">' + table + ' => <span>' + service_name + '</span></label>' +
						'</li>';
					}
					
					ul.html(html);
				}
				else
					ul.html("<li>No tables available...</li>");
			},
			error : function(jqXHR, textStatus, errorThrown) { 
				if (jqXHR.responseText)
					StatusMessageHandler.showError(jqXHR.responseText);
				
				ul.html("<li>No tables available...</li>");
			},
		});
	}
	else
		ul.html("<li>No tables available...</li>");
}

function updateLayerUrlFileManager(elm) {
	var option = elm.options[ elm.selectedIndex ];
	var bean_file_name = option.getAttribute("bean_file_name");
	var bean_name = option.getAttribute("bean_name");
	var choose_queries_from_file_manager = $(elm).parent().parent();
	var mytree = choose_queries_from_file_manager.children(".mytree");
	var root_elm = mytree.children("li").first();
	var ul = root_elm.children("ul").first();
	
	root_elm.removeClass("jstree-open").addClass("jstree-closed");
	ul.html("");
	
	var url = ul.attr("layer_url");
	url = url.replace("#bean_file_name#", bean_file_name).replace("#bean_name#", bean_name);
	ul.attr("url", url);
}

function checkChooseFiles(oForm) {
	var btn = $(oForm).find("input[type=submit]");
	btn.hide();
	
	var broker_select = $(oForm).find(".broker select")[0];
	var option = broker_select.options[ broker_select.selectedIndex ];
	var is_db_broker = option.getAttribute("is_db_broker") == 1;
	
	var items = $(oForm).find(is_db_broker ? ".tables input:checked" : ".mytree input:checked");
	
	if (items.length > 0) {
		if (is_db_broker) {
			$(oForm).find(".tables input").removeAttr("disabled");
			$(oForm).find(".mytree input").attr("disabled", "disabled");
		}
		else {
			$(oForm).find(".tables input").attr("disabled", "disabled");
			$(oForm).find(".mytree input").removeAttr("disabled");
		}
		
		return true;
	}
	
	btn.show();
	alert("You must select at least 1 item.");
	return false;
}
