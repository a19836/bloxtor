/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original JQuery Task flow Chart Repo: https://github.com/a19836/jquerytaskflowchart/
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

$(window).resize(function() {
	taskFlowChartObj.Container.automaticIncreaseContainersSize();
	
	taskFlowChartObj.getMyFancyPopupObj().updatePopup();
});

function emptyDiagam() {
	if (confirm("If you continue, all items will be deleted from this diagram and this diagram will be empty.\nDo you still want to proceed?"))
		taskFlowChartObj.reinit();
}

function zoomInDiagram(elm) {
	taskFlowChartObj.TaskFlow.zoomIn();
	updateCurrentZoom(elm);
	zoomEventPropagationDiagram(elm);
}

function zoomOutDiagram(elm) {
	taskFlowChartObj.TaskFlow.zoomOut();
	updateCurrentZoom(elm);
	zoomEventPropagationDiagram(elm);
}

function zoomDiagram(input) {
	taskFlowChartObj.TaskFlow.zoom(input.value);
	updateCurrentZoom(input);
	zoomEventPropagationDiagram(input);
}

function zoomResetDiagram(elm) {
	taskFlowChartObj.TaskFlow.zoomReset();
	updateCurrentZoom(elm);
	zoomEventPropagationDiagram(elm);
}

function zoomEventPropagationDiagram(elm) {
	window.event.stopPropagation();
}

function updateCurrentZoom(elm) {
	elm = $(elm);
	var current_zoom = taskFlowChartObj.TaskFlow.getCurrentZoom();
	var main_parent = elm.parent().closest("li").parent();
	main_parent.find(".zoom span").html( parseInt(current_zoom * 100) + "%");
	
	if (!elm.is("input[type=range]"))
		main_parent.find(".zoom input[type=range]").val(current_zoom);
}
