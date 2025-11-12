/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

var BusinessLogicLayerTaskPropertyObj = {
	allow_multi_lower_level_layer_connections : true, //false: only allow connections to data-access layers
	allow_same_layer_connection : true, //false: do not allow connections between multiple business logic layers.
	allow_dbdriver_connections : false, //false: do not allow connections with dbdrivers. If allow_multi_lower_level_layer_connections and allow_dbdriver_connections are true, it means we must connect with the DB layer to get the DBDrivers. DB Layer has same methods than DBDriver, so any connection to a DB Layer, should work with a direct connection with a DBDriver too, but just in case this option is disabled bc is not well tested!
	
	onCheckLabel : function(label_obj, task_id) {
		return onCheckTaskLayerLabel(label_obj, task_id);
	},
	
	onCancelLabel : function(task_id) {
		return prepareLabelIfUserLabelIsInvalid(task_id);
	},
};
