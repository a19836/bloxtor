/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

var chooseTemplateTaskLayerFileFromFileManagerTree = null;
var chooseTestUnitsFromFileManagerTree = null;
var MyDeploymentUIFancyPopup = new MyFancyPopupClass();

$(function() {
	$(window).bind('beforeunload', function () {
		if (taskFlowChartObj.TaskFile.isWorkFlowChangedFromLastSaving()) {
			if (window.parent && window.parent.iframe_overlay)
				window.parent.iframe_overlay.hide();
			
			return "If you proceed your changes won't be saved. Do you wish to continue?";
		}
		
		return null;
	});
	
	//prepare top_bar
	$(".taskflowchart").addClass("with_top_bar_menu").children(".workflow_menu").addClass("top_bar_menu");
	
	//init auto save
	addAutoSaveMenu(".taskflowchart.with_top_bar_menu .workflow_menu.top_bar_menu li.save", "onToggleWorkflowAutoSave");
	enableAutoSave(onToggleWorkflowAutoSave);
	initAutoSave(".taskflowchart.with_top_bar_menu .workflow_menu.top_bar_menu li.save a");
	
	$(".taskflowchart.with_top_bar_menu .workflow_menu.top_bar_menu li.auto_save_activation").addClass("with_padding");
	
	//init workflow
	taskFlowChartObj.TaskFlow.default_connection_connector = "Flowchart";
	taskFlowChartObj.TaskFlow.default_connection_overlay = "One To One";
	//taskFlowChartObj.TaskFlow.available_connection_connectors_type = ["Flowchart"];
	taskFlowChartObj.TaskFlow.available_connection_overlays_type = ["One To One"];
	
	taskFlowChartObj.TaskFile.on_success_read = updateTasksAfterFileRead;
	taskFlowChartObj.TaskFile.on_success_update = updateTasksAfterFileRead;
	
	//init trees
	chooseTemplateTaskLayerFileFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeAllInvalidTemplateTaskLayerFilesFromTree,
	});
	chooseTemplateTaskLayerFileFromFileManagerTree.init("choose_template_task_layer_file_from_file_manager");
	
	chooseTestUnitsFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeAllInvalidTestUnitsFromTree,
	});
	chooseTestUnitsFromFileManagerTree.init("choose_test_units_from_file_manager");
	
	chooseFileFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeObjectPropertiesAndMethodsAndFunctionsFromTree,
	});
	chooseFileFromFileManagerTree.init("choose_file_from_file_manager");
	
	$("#choose_template_task_layer_file_from_file_manager > .mytree > li:first-child > a").attr("file_path", "");
	$("#choose_test_units_from_file_manager > .mytree > li:first-child > a").attr("file_path", "");
	
	MyFancyPopup.hidePopup();
});

function onToggleFullScreen(in_full_screen) {
	taskFlowChartObj.resizePanels();
}

function removeAllInvalidTemplateTaskLayerFilesFromTree(ul, data) {
	ul = $(ul);
	
	ul.find("i.function, i.reserved_file").each(function(idx, elm){
		$(elm).parent().parent().remove();
	});
	
	ul.find("i.file, i.service, i.project, i.project_common").each(function(idx, elm){
		$(elm).parent().parent().children("ul").remove();
	});
}

function removeAllInvalidTestUnitsFromTree(ul, data) {
	ul = $(ul);
	
	ul.find("i.function").each(function(idx, elm){
		$(elm).parent().parent().remove();
	});
	
	ul.find("i.file").each(function(idx, elm){
		elm = $(elm);
		var a = elm.parent();
		var li = a.parent();
		var file_path = a.attr("file_path");
		
		if (!file_path || !("" + file_path).match(/\.php([0-9]*)$/i)) //is not a php file
			li.remove();
		else if (li.find(" > ul > li > a").children("i.test_unit_obj").length == 0)
			li.remove();
	});
}

function onChooseTemplateTaskLayerFile(elm, layer_name) {
	if (layer_name) {
		layer_name = ("" + layer_name).toLowerCase();
		
		var popup = $("#choose_template_task_layer_file_from_file_manager");
		var broker = popup.children(".broker");
		var select = broker.children("select");
		
		broker.hide();
		select.val(layer_name);
		
		if (popup.attr("layer_name") != layer_name) {
			popup.attr("layer_name", layer_name);
			updateTemplateTaskLayerUrlFileManager(select[0]);
		}
		
		MyDeploymentUIFancyPopup.init({
			elementToShow: popup,
			parentElement: document,
			
			targetField: $(elm).parent().children("input")[0],
			updateFunction: function(elm) {
				chooseFile(elm, chooseTemplateTaskLayerFileFromFileManagerTree);
			},
		});
		
		MyDeploymentUIFancyPopup.showPopup();
	}
}

function onChooseTemplateFile(elm, layer_name) {
	var popup = $("#choose_file_from_file_manager");
	
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		
		updateFunction: function(btn) {
			var node = chooseFileFromFileManagerTree.getSelectedNodes();
			node = node[0];
			
			if (node) {
				var a = $(node).children("a");
				var file_path = a.attr("file_path");
				var bean_name = a.attr("bean_name");
				var include_path_prefix = file_path && bean_name ? beans_folders_name[bean_name] : null;
				
				if (include_path_prefix) {
					var input = $(elm).parent().find("input");
					input.val(include_path_prefix + file_path);
					
					MyFancyPopup.hidePopup();
				}
				else {
					alert("invalid selected file.\nPlease choose a valid file.");
				}
			}
		}
	});
	
	MyFancyPopup.showPopup();
}

function onGetLayerWordPressInstallationsUrl(layer_name) {
	var url = null;
	
	if (layer_name) {
		layer_name = ("" + layer_name).toLowerCase();
		
		var popup = $("#choose_template_task_layer_file_from_file_manager");
		var options = popup.find(".broker select option");
		
		$.each(options, function (idx, option) {
			option = $(option);
			
			if (option.val() == layer_name) {
				url = option.attr("url");
				
				if (url) 
					url = url.replace("#path#", wordpress_installations_relative_path);
				
				return false;
			}
		});
	}
	
	return url;
}

function onChooseTemplateActionTestUnit(elm) {
	var popup = $("#choose_test_units_from_file_manager");
	
	MyDeploymentUIFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		
		targetField: $(elm).parent().children("input")[0],
		updateFunction: function(elm) {
			chooseFile(elm, chooseTestUnitsFromFileManagerTree);
		},
	});
	
	MyDeploymentUIFancyPopup.showPopup();
}

function chooseFile(elm, treeObj) {
	var node = treeObj.getSelectedNodes();
	node = node[0];
	
	if (node) {
		var a = $(node).children("a");
		
		if (a[0].hasAttribute("file_path")) {
			$(MyDeploymentUIFancyPopup.settings.targetField).val( a.attr("file_path") ); //if file_path is empty it means it is the root of the layer
	
			MyDeploymentUIFancyPopup.hidePopup();
		}
		else 
			alert("Selected item must be a valid file or folder!\nPlease try again...");
	}
}

function onOpenServerPropertiesPopup() {
	//console.log("auto_save 0:"+auto_save);
	//when auto_save is on and I open a template diagram inside of a server properties, and then the auto save runs, the system is saving the tasks from the layers diagram to the deployment diagram, so we must disable the auto_save until the server properties popup gets closed.	
	window.auto_save_bkp = auto_save;
	auto_save = false;
}

function onCloseServerPropertiesPopup() {
	auto_save = window.auto_save_bkp;
	
	//console.log("auto_save 1:"+auto_save);
}

function updateTemplateTaskLayerUrlFileManager(elm) {
	var option = elm.options[ elm.selectedIndex ];
	var url = option.getAttribute("url");
	
	var mytree = $(elm).parent().parent().find(".mytree");
	var root_elm = mytree.children("li").first();
	var ul = root_elm.children("ul").first();
	
	root_elm.removeClass("jstree-open").addClass("jstree-closed");
	ul.html("");
	ul.attr("url", url);
}

function prepareTaskContextMenu() {
	/*;(function() {
		taskFlowChartObj.onReady(function() {
			$("#" + taskFlowChartObj.ContextMenu.task_context_menu_id + " .set_label a").html("Edit Server Name");
		});
	})();*/
}

function updateTasksAfterFileRead() {
	$(".loading_panel").hide();
}

function saveDeploymentDiagram() {
	prepareAutoSaveVars();
	
	if (taskFlowChartObj.TaskFile.isWorkFlowChangedFromLastSaving()) {
		taskFlowChartObj.TaskFile.save(null, {
			success: function(data, textStatus, jqXHR) {
				if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
					showAjaxLoginPopup(jquery_native_xhr_object.responseURL, taskFlowChartObj.TaskFile.set_tasks_file_url, function() {
						taskFlowChartObj.StatusMessage.removeLastShownMessage("error");
						
						saveDeploymentDiagram();
					});
				else if (is_from_auto_save) {
					taskFlowChartObj.StatusMessage.removeMessages("status");
					resetAutoSave();
				}
			},
			timeout: is_from_auto_save && auto_save_connection_ttl ? auto_save_connection_ttl : 0,
		});
	}
	else if (!is_from_auto_save) 
		taskFlowChartObj.StatusMessage.showMessage("Nothing to save.", "", "bottom_messages", 1500);
	else
		resetAutoSave();

	return false;
}

function addNewServer() {
	var auto_save_bkp = auto_save;
	
	var server_task_type_id = ServerTaskPropertyObj.template_tasks_types_by_tag["server"];
	var task_id = taskFlowChartObj.ContextMenu.addTaskByType(server_task_type_id);
	
	taskFlowChartObj.TaskFlow.setTaskLabelByTaskId(task_id, {label: null}); //set {label: null}, so the TaskFlow.setTaskLabel method ignores the prompt and adds the default label or an auto generated label.
	
	//set auto_save from bkp bc when we call addTaskByType, it will call the loadTaskProperties which then calls the onOpenServerPropertiesPopup, but then it doesn't call the onCloseServerPropertiesPopup, which will return a wrong auto_save value.
	auto_save = auto_save_bkp;
	
	//open properties
	taskFlowChartObj.Property.showTaskProperties(task_id);
}
