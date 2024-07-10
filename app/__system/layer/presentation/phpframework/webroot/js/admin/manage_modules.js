$(function () {
	MyFancyPopup.init({
		parentElement: window,
	});
	
	showModulesLayer($(".modules_list .layer select")[0]);
});

function showModulesLayer(elm) {
	var modules_id = elm.options[elm.selectedIndex].getAttribute("modules_id");
	
	var modules_list = $(".modules_list");
	modules_list.children(".layer_modules").hide();
	modules_list.children("#" + modules_id).show();
}

function deleteModule(elm, url, module_id, group_module_id) {
	var msg = "";
	if (module_id == group_module_id) 
		msg = "Are you sure you wish to delete the '" + module_id + "' module?\nIf you proceed you will loose all data related with this module.";
	else 
		msg = "If you proceed you will delete all the modules for the '" + group_module_id + "' group, which means you will not delete only the data for the '" + module_id + "' module, but to all modules inside of the '" + group_module_id + "' group.\nAre you sure you wish to delete all modules for the '" + group_module_id + "' group?";
	
	if (confirm(msg)) {
		MyFancyPopup.showOverlay();
		MyFancyPopup.showLoading();
		
		$.ajax({
			type : "get",
			url : url,
			dataType : "text",
			success : function(data, textStatus, jqXHR) {
				if (data == "1") {
					var tr = $(elm).parent().parent();
					var tbody = tr.parent();
					tr.remove();
				
					StatusMessageHandler.showMessage("Module successfully deleted!");
					
					//Delete the other modules from the same group
					if (module_id != group_module_id) {
						tbody.find("td.module_id").each(function(idx, item) {
							item = $(item);
							
							var md_id = item.text();
							var pos = md_id.indexOf("/");
							
							if (pos > 0) {
								var gmd_id = md_id.substring(0, pos);
								
								if (gmd_id == group_module_id)
									item.parent().remove();
							}
						});
						
						tbody.children("tr[module_id=" + group_module_id + "]").remove();
					}
				}
				else
					StatusMessageHandler.showError("Error: Module not deleted! Please try again." + (data ? "\n" + data : ""));
			},
			error : function(jqXHR, textStatus, errorThrown) { 
				if (jqXHR.responseText);
					StatusMessageHandler.showError(jqXHR.responseText);
			}
		}).always(function() {
			MyFancyPopup.hidePopup();
		});
	}
}

function disableModule(elm, url) {
	MyFancyPopup.showOverlay();
	MyFancyPopup.showLoading();
	
	var buttons = $(elm).parent();
	var tr = buttons.parent();
	var label = tr.children(".label").text();
	
	$.ajax({
		type : "get",
		url : url,
		dataType : "text",
		success : function(data, textStatus, jqXHR) {
			if (data == "1") {
				var status = tr.children(".status").children("span");
				
				buttons.children(".enable").show();
				buttons.children(".disable").hide();
				
				status.removeClass("enable");
				status.addClass("disable");
				status.attr("title", "This module is currently disabled");
				
				StatusMessageHandler.showMessage("Module '" + label + "' successfully disabled!");
			}
			else
				StatusMessageHandler.showError("Error: Module '" + label + "' not disabled! Please try again." + (data ? "\n" + data : ""));
		},
		error : function(jqXHR, textStatus, errorThrown) { 
			if (jqXHR.responseText);
				StatusMessageHandler.showError(jqXHR.responseText);
		}
	}).always(function() {
		MyFancyPopup.hidePopup();
	});
}

function enableModule(elm, url) {
	MyFancyPopup.showOverlay();
	MyFancyPopup.showLoading();
	
	var buttons = $(elm).parent();
	var tr = buttons.parent();
	var label = tr.children(".label").text();
	
	$.ajax({
		type : "get",
		url : url,
		dataType : "text",
		success : function(data, textStatus, jqXHR) {
			if (data == "1") {
				var status = tr.children(".status").children("span");
				
				buttons.children(".enable").hide();
				buttons.children(".disable").show();
				
				status.removeClass("disable");
				status.addClass("enable");
				status.attr("title", "This module is currently enabled");
				
				StatusMessageHandler.showMessage("Module '" + label + "' successfully enabled!");
			}
			else
				StatusMessageHandler.showError("Error: Module '" + label + "' not enabled! Please try again." + (data ? "\n" + data : ""));
		},
		error : function(jqXHR, textStatus, errorThrown) { 
			if (jqXHR.responseText);
				StatusMessageHandler.showError(jqXHR.responseText);
		}
	}).always(function() {
		MyFancyPopup.hidePopup();
	});
}

function executeActionInAllModules(elm, action) {
	StatusMessageHandler.showMessage("Preparing to " + action + " all modules...");
	
	var tbody = $(elm).parent().closest("table").children("tbody");
	var icons = tbody.find(" > tr > td.buttons > ." + action);
	var exists = false;
	
	$.each(icons, function(idx, icon) {
		icon = $(icon);
		
		if (icon.css("display") != "none") {
			icon.trigger("click");
			exists = true;
		}
	});
	
	if (!exists) {
		StatusMessageHandler.removeLastShownMessage("info");
		StatusMessageHandler.showMessage("All Modules already " + action + "d!");
	}
}

function toggleGroupOfMopdules(elm, group_module_id) {
	elm = $(elm);
	
	if (elm.hasClass("maximize")) {
		elm.removeClass("maximize").addClass("minimize");
		elm.parent().parent().parent().children("tr[group_module_id='" + group_module_id + "']").show();
	}
	else {
		elm.removeClass("minimize").addClass("maximize");
		elm.parent().parent().parent().children("tr[group_module_id='" + group_module_id + "']").hide();
	}
}
