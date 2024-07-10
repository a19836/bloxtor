var DBLayerTaskPropertyObj = {
	
	onCheckLabel : function(label_obj, task_id) {
		return onCheckTaskLayerLabel(label_obj, task_id);
	},
	
	onCancelLabel : function(task_id) {
		return prepareLabelIfUserLabelIsInvalid(task_id);
	},
};
