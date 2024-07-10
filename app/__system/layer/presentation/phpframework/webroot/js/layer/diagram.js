if (typeof allow_connections_to_multiple_levels == "undefined")
	var allow_connections_to_multiple_levels = true;

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
	taskFlowChartObj.TaskFlow.default_connection_connector = "Straight";
	taskFlowChartObj.TaskFlow.default_connection_overlay = "Forward Arrow";
	taskFlowChartObj.TaskFlow.available_connection_overlays_type = ["Forward Arrow"];
	taskFlowChartObj.TaskFlow.available_connection_overlays[0][1]["location"] = 0.999; //Sets the arrow to the end to the conneciton line. Note that this cannot be 1 or we will get a javascript error from jsplumb. This is only used by jsplumb. If Leaderline is used, we won't need the "location" attribute.
	
	taskFlowChartObj.TaskFile.on_success_read = updateTasksAfterFileRead;
	taskFlowChartObj.TaskFile.on_success_update = updateTasksAfterFileRead;
	
	//allow connections to only 1 level below.
	if (!allow_connections_to_multiple_levels) {
		PresentationLayerTaskPropertyObj.allow_multi_lower_level_layer_connections = false;
		BusinessLogicLayerTaskPropertyObj.allow_multi_lower_level_layer_connections = false;
	}
	
	//add default function to reset the top positon of the tasksflow panels, if with_top_bar class exists 
	onResizeTaskFlowChartPanels(taskFlowChartObj, 0);
});

function toggleLayersNameAutoNormalization(elm) {
	normalize_task_layer_label = !normalize_task_layer_label; //This var is defined in the app/lib/org/phpframework/workflow/task/layer/common/webroot/js/global.js
	
	$(elm).children("span").html(normalize_task_layer_label ? "Disable" : "Enable");
}

function onToggleFullScreen(in_full_screen) {
	taskFlowChartObj.resizePanels();
}

function onResizeTaskFlowChartPanels(WF, height) {
	if ($("#" + WF.ContextMenu.main_tasks_menu_obj_id).parent().hasClass("with_top_bar_menu"))
		$("#" + WF.ContextMenu.main_tasks_menu_obj_id + ", #" + WF.ContextMenu.main_tasks_menu_hide_obj_id + ", #" + WF.TaskFlow.main_tasks_flow_obj_id).css("top", "");
}

function updateTasksAfterFileRead() {
	//load tasks properties
	var tasks = taskFlowChartObj.TaskFlow.getAllTasks();
	
	if (tasks)
		for (var i = 0, l = tasks.length; i < l; i++) {
			var task = $(tasks[i]);
			var task_id = task.attr("id");
			var task_properties = taskFlowChartObj.TaskFlow.tasks_properties[task_id];
			var is_active = task_properties && parseInt(task_properties["active"]) == 1 || ("" + task_properties["active"]).toLowerCase() == "true";
			
			if (is_active)
				task.addClass("active");
			else
				task.removeClass("active");
			
			prepareLayerTaskActiveStatus(task);
		}
}

function saveLayersDiagram() {
	prepareAutoSaveVars();
	
	if (taskFlowChartObj.TaskFile.isWorkFlowChangedFromLastSaving()) {
		taskFlowChartObj.TaskFile.save(null, {
			success: function(data, textStatus, jqXHR) {
				if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
					showAjaxLoginPopup(jquery_native_xhr_object.responseURL, taskFlowChartObj.TaskFile.set_tasks_file_url, function() {
						taskFlowChartObj.StatusMessage.removeLastShownMessage("error");
						
						saveLayersDiagram();
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
		StatusMessageHandler.showMessage("Nothing to save.", "", "bottom_messages", 1500);
	else
		resetAutoSave();

	return false;
}

function openGlobalSettingsAndVarsPopup(url, options) {
	openIframePopup(url, options);
	
	var popup = taskFlowChartObj.getMyFancyPopupObj().getPopup();
	popup.addClass("with_iframe_title")
}

function onOpenGlobalSettingsAndVars() {
	/*var close_popup_func = function(e) {
		e.preventDefault();
		
		if (confirm("You are about to close this popup and loose the unsaved changes. Do you wish to proceed?"))
			taskFlowChartObj.getMyFancyPopupObj().hidePopup();
	};
	
	var close_btn = taskFlowChartObj.getMyFancyPopupObj().getPopupCloseButton();
	close_btn.off("click");
	close_btn.click(close_popup_func);
	
	var overlay = taskFlowChartObj.getMyFancyPopupObj().getOverlay();
	overlay.off("click");
	overlay.click(close_popup_func);*/
}
