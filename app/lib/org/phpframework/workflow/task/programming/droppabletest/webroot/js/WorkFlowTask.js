var DroppableTestTaskPropertyObj = {
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		var task_html_elm = $(properties_html_elm).find(".droppable_test_task_html");
		
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		
	},
	
	onCancelTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		return true;	
	},
	
	onCompleteLabel : function(task_id) {
		return ProgrammingTaskUtil.onEditLabel(task_id);
	},
	
	onTaskCreation : function(task_id) {
		var WF = myWFObj.getTaskFlowChart();
		var j_task = WF.TaskFlow.getTaskById(task_id);
		
		var droppable1 = $('<div class="' + WF.TaskFlow.task_droppable_class_name + '"></div>');
		droppable1.css({
			"height":"100px",
			"position":"absolute",
			"top":"50px",
			"left":0,
			"right":0,
			"box-sizing":"border-box",
			"background":"green",
		});
		
		var droppable2 = $('<div class="' + WF.TaskFlow.task_droppable_class_name + '"></div>');
		droppable2.css({
			"position":"absolute",
			"top":"150px",
			"left":0,
			"right":0,
			"bottom":0,
			"min-height":"100px", 
			"box-sizing":"border-box",
			"background":"orange",
		});
		
		j_task.append(droppable1);
		j_task.append(droppable2);
		
		WF.ContextMenu.prepareTaskDroppables(j_task);
		WF.TaskFlow.resizeTaskParentTask(droppable1, true);
		
		ProgrammingTaskUtil.onTaskCreation(task_id);
	},
};
