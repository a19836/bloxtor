/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

var DBDAOActionTaskPropertyObj = {
	
	brokers_options : null,
	on_choose_table_callback : null,
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".db_dao_action_task_html");
		ProgrammingTaskUtil.setResultVariableType(task_property_values, task_html_elm);
		
		BrokerOptionsUtilObj.initFields(task_html_elm.find(".broker_method_obj"), DBDAOActionTaskPropertyObj.brokers_options, task_property_values["method_obj"]);
		
		LayerOptionsUtilObj.onLoadTaskProperties(task_html_elm, task_property_values);
		
		//console.log(task_property_values); 
		task_property_values = assignObjectRecursively({}, task_property_values); //very important otherwise the convertArrayToSimpleSettings changes the attributes_type, conditions_type, xxx_type in myWFObj.getTaskFlowChart().TaskFlow.tasks_properties[task_id], which will then mess the convertion to code, bc we cannot types = options
		
		if (task_property_values["relations_type"] == "array" && (task_property_values["method_name"] == "findRelationshipObjects" || task_property_values["method_name"] == "countRelationshipObjects")) {
			var new_relations = [];
			var relations_to_export = ["attributes", "conditions", "keys"];
			
			$.each(task_property_values["relations"], function(idx, relation) {
				var pos = relation["key_type"] == "string" ? relations_to_export.indexOf(relation["key"]) : -1;
				
				if (pos != -1) {
					var key = relations_to_export[pos];
					
					if (relation["items"]) {
						task_property_values[key + "_type"] = "array";
						
						if ($.isPlainObject(relation["items"]) && relation["items"].hasOwnProperty("key"))
							task_property_values[key] = [ relation["items"] ];
						else
							task_property_values[key] = relation["items"];
					}
					else if (relation["value"]) { //in case one of the elements be null, ignores it
						task_property_values[key + "_type"] = relation["value_type"];
						task_property_values[key] = relation["value"];
					}
				}
				else
					new_relations.push(relation);
			});
			task_property_values["relations"] = new_relations;
		}
		
		task_property_values = DBDAOActionTaskPropertyObj.convertArrayToSimpleSettings(task_property_values, "attributes");
		task_property_values = DBDAOActionTaskPropertyObj.convertArrayToSimpleSettings(task_property_values, "conditions");
		task_property_values = DBDAOActionTaskPropertyObj.convertArrayToSimpleSettings(task_property_values, "keys");
		task_property_values = DBDAOActionTaskPropertyObj.convertArrayToSimpleSettings(task_property_values, "parent_conditions");
		
		//console.log(task_property_values); 
		
		//PREPARE ATTRIBUTES
		var attributes = task_property_values["attributes"];
		var attributes_type_select = task_html_elm.find(".attrs > .attributes_type");
		attributes_type_select.val(task_property_values["attributes_type"]); //must set this bc the attributes may be inited above
		
		if (task_property_values["attributes_type"] == "array") {
			ArrayTaskUtilObj.onLoadArrayItems( task_html_elm.find(".attrs > .attributes").first(), attributes, "");
			task_html_elm.find(".attrs > .attributes_code").val("");
		}
		else if (task_property_values["attributes_type"] == "options") {
			DBDAOActionTaskPropertyObj.loadSavedTableAttributesOptions( task_html_elm.find(".attrs > .attributes_options").first(), attributes);
			//attributes_type_select.val("options"); //this was set above
		}
		else {
			attributes = attributes ? "" + attributes + "" : "";
			attributes = task_property_values["attributes_type"] == "variable" && attributes.trim().substr(0, 1) == '$' ? attributes.trim().substr(1) : attributes;
			task_html_elm.find(".attrs > .attributes_code").val(attributes);
		}
		DBDAOActionTaskPropertyObj.onChangeAttributesType(task_html_elm.find(".attrs > .attributes_type")[0]);
		
		//PREPARE CONDITIONS
		var conditions = task_property_values["conditions"];
		var conditions_type_select = task_html_elm.find(".conds > .conditions_type");
		conditions_type_select.val(task_property_values["conditions_type"]); //must set this bc the conditions may be inited above
		
		if (task_property_values["conditions_type"] == "array") {
			ArrayTaskUtilObj.onLoadArrayItems( task_html_elm.find(".conds > .conditions").first(), conditions, "");
			task_html_elm.find(".conds > .conditions_code").val("");
		}
		else if (task_property_values["conditions_type"] == "options") {
			DBDAOActionTaskPropertyObj.loadSavedTableAttributesOptions( task_html_elm.find(".conds > .conditions_options").first(), conditions);
			//conditions_type_select.val("options"); //this was set above
		}
		else {
			conditions = conditions ? "" + conditions + "" : "";
			conditions = task_property_values["conditions_type"] == "variable" && conditions.trim().substr(0, 1) == '$' ? conditions.trim().substr(1) : conditions;
			task_html_elm.find(".conds > .conditions_code").val(conditions);
		}
		DBDAOActionTaskPropertyObj.onChangeConditionsType(conditions_type_select[0]);
		
		//PREPARE KEYS
		var keys = task_property_values["keys"];
		var keys_type_select = task_html_elm.find(".kys > .keys_type");
		keys_type_select.val(task_property_values["keys_type"]); //must set this bc the keys may be inited above
		
		if (task_property_values["keys_type"] == "array") {
			ArrayTaskUtilObj.onLoadArrayItems( task_html_elm.find(".kys > .keys").first(), keys, "");
			task_html_elm.find(".kys > .keys_code").val("");
		}
		else if (task_property_values["keys_type"] == "options") {
			DBDAOActionTaskPropertyObj.loadSavedTableAttributesOptions( task_html_elm.find(".kys > .keys_options").first(), keys);
			//keys_type_select.val("options"); //this was set above
		}
		else {
			keys = keys ? "" + keys + "" : "";
			keys = task_property_values["keys_type"] == "variable" && keys.trim().substr(0, 1) == '$' ? keys.trim().substr(1) : keys;
			task_html_elm.find(".kys > .keys_code").val(keys);
		}
		DBDAOActionTaskPropertyObj.onChangeKeysType(task_html_elm.find(".kys > .keys_type")[0]);
		
		//PREPARE REL_ELM
		var relations = task_property_values["relations"];
		var relations_type_select = task_html_elm.find(".rels > .relations_type");
		relations_type_select.val(task_property_values["relations_type"]); //must set this bc the relations may be changed above
		
		if (task_property_values["relations_type"] == "array") {
			ArrayTaskUtilObj.onLoadArrayItems( task_html_elm.find(".rels > .relations").first(), relations, "");
			task_html_elm.find(".rels > .relations_code").val("");
		}
		else {
			relations = relations ? "" + relations + "" : "";
			relations = task_property_values["relations_type"] == "variable" && relations.trim().substr(0, 1) == '$' ? relations.trim().substr(1) : relations;
			task_html_elm.find(".rels > .relations_code").val(relations);
		}
		DBDAOActionTaskPropertyObj.onChangeRelElmType(relations_type_select[0]);
		
		//PREPARE PARENT CONDITIONS
		var parent_conditions = task_property_values["parent_conditions"];
		var parent_conditions_type_select = task_html_elm.find(".parent_conds > .parent_conditions_type");
		
		if (task_property_values["parent_conditions_type"] == "array") {
			ArrayTaskUtilObj.onLoadArrayItems( task_html_elm.find(".parent_conds > .parent_conditions").first(), parent_conditions, "");
			task_html_elm.find(".parent_conds > .parent_conditions_code").val("");
		}
		else if (task_property_values["parent_conditions_type"] == "options") {
			DBDAOActionTaskPropertyObj.loadSavedTableAttributesOptions( task_html_elm.find(".parent_conds > .parent_conditions_options").first(), parent_conditions);
			parent_conditions_type_select.val("options");
		}
		else {
			parent_conditions = parent_conditions ? "" + parent_conditions + "" : "";
			parent_conditions = task_property_values["parent_conditions_type"] == "variable" && parent_conditions.trim().substr(0, 1) == '$' ? parent_conditions.trim().substr(1) : parent_conditions;
			task_html_elm.find(".parent_conds > .parent_conditions_code").val(parent_conditions);
		}
		DBDAOActionTaskPropertyObj.onChangeParentConditionsType(parent_conditions_type_select[0]);
		
		DBDAOActionTaskPropertyObj.onChangeMethodName( task_html_elm.find(".method_name select")[0] );
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".db_dao_action_task_html");
		ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithType(task_html_elm);
		ProgrammingTaskUtil.onSubmitResultVariableType(task_html_elm);
		
		//prepare attributes
		if (task_html_elm.find(".attrs > .attributes_type").val() == "array")
			task_html_elm.find(".attrs > .attributes_code, .attrs > .attributes_options").remove();
		else if (task_html_elm.find(".attrs > .attributes_type").val() == "options")  { 
			task_html_elm.find(".attrs > .attributes_type").val("array");
			
			//convert .attributes_options to array
			var items = DBDAOActionTaskPropertyObj.convertSimpleSettingsToArray(task_html_elm, task_html_elm.find(".attrs > .attributes_options") );
			ArrayTaskUtilObj.onLoadArrayItems( task_html_elm.find('.attrs > .attributes').first(), items, "");
			
			task_html_elm.find(".attrs > .attributes_code, .attrs > .attributes_options").remove();
		}
		else
			task_html_elm.find(".attrs > .attributes, .attrs > .attributes_options").remove();
		
		//prepare conditions
		if (task_html_elm.find(".conds > .conditions_type").val() == "array")
			task_html_elm.find(".conds > .conditions_code, .conds > .conditions_options").remove();
		else if (task_html_elm.find(".conds > .conditions_type").val() == "options")  { 
			task_html_elm.find(".conds > .conditions_type").val("array");
			
			//convert .conditions to array
			var items = DBDAOActionTaskPropertyObj.convertSimpleSettingsToArray(task_html_elm, task_html_elm.find(".conds > .conditions_options") );
			ArrayTaskUtilObj.onLoadArrayItems( task_html_elm.find('.conds > .conditions').first(), items, "");
			
			task_html_elm.find(".conds > .conditions_code, .conds > .conditions_options").remove();
		}
		else
			task_html_elm.find(".conds > .conditions, .conds > .conditions_options").remove();
		
		//prepare keys
		if (task_html_elm.find(".kys > .keys_type").val() == "array")
			task_html_elm.find(".kys > .keys_code, .kys > .keys_options").remove();
		else if (task_html_elm.find(".kys > .keys_type").val() == "options")  { 
			task_html_elm.find(".kys > .keys_type").val("array");
			
			//convert .keys to array
			var items = DBDAOActionTaskPropertyObj.convertSimpleSettingsToArray(task_html_elm, task_html_elm.find(".kys > .keys_options") );
			ArrayTaskUtilObj.onLoadArrayItems( task_html_elm.find('.kys > .keys').first(), items, "");
			
			task_html_elm.find(".kys > .keys_code, .kys > .keys_options").remove();
		}
		else
			task_html_elm.find(".kys > .keys, .kys > .keys_options").remove();
		
		//prepare relations
		if (task_html_elm.find(".rels > .relations_type").val() == "array")
			task_html_elm.find(".rels > .relations_code, .rels > .relations_options").remove();
		else if (task_html_elm.find(".rels > .relations_type").val() == "options")  { 
			task_html_elm.find(".rels > .relations_type").val("array");
			
			//convert .relations to array
			var items = DBDAOActionTaskPropertyObj.convertSimpleSettingsToArray(task_html_elm, task_html_elm.find(".rels > .relations_options") );
			ArrayTaskUtilObj.onLoadArrayItems( task_html_elm.find('.rels > .relations').first(), items, "");
			
			task_html_elm.find(".rels > .relations_code, .rels > .relations_options").remove();
		}
		else
			task_html_elm.find(".rels > .relations, .rels > .relations_options").remove();
		
		//prepare parent_conditions
		if (task_html_elm.find(".parent_conds > .parent_conditions_type").val() == "array")
			task_html_elm.find(".parent_conds > .parent_conditions_code, .parent_conds > .parent_conditions_options").remove();
		else if (task_html_elm.find(".parent_conds > .parent_conditions_type").val() == "options") { 
			task_html_elm.find(".parent_conds > .parent_conditions_type").val("array");
			
			//convert .parent_conditions to array
			var items = DBDAOActionTaskPropertyObj.convertSimpleSettingsToArray(task_html_elm, task_html_elm.find(".parent_conds > .parent_conditions_options") );
			ArrayTaskUtilObj.onLoadArrayItems( task_html_elm.find('.parent_conds > .parent_conditions').first(), items, "");
			
			task_html_elm.find(".parent_conds > .parent_conditions_code, .parent_conds > .parent_conditions_options").remove();
		}
		else
			task_html_elm.find(".parent_conds > .parent_conditions, .parent_conds > .parent_conditions_options").remove();
		
		if (task_html_elm.find(".opts .options_type").val() == "array")
			task_html_elm.find(".opts .options_code").remove();
		else
			task_html_elm.find(".opts .options").remove();
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var label = DBDAOActionTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
			
			var default_method_obj_str = BrokerOptionsUtilObj.getDefaultBroker(DBDAOActionTaskPropertyObj.brokers_options);
			if (!task_property_values["method_obj"] && default_method_obj_str)
				task_property_values["method_obj"] = default_method_obj_str;
			
			//prepare relations with attributes, conditions and keys attributes
			if ((task_property_values["method_name"] == "findRelationshipObjects" || task_property_values["method_name"] == "countRelationshipObjects")) {
				var is_rels_ok = task_property_values["relations_type"] == "array" || !task_property_values["relations"];
				
				if (is_rels_ok) {
					//prepare items to add
					var items_to_add = [];
					var items_names = ["attributes", "conditions", "keys"];
					
					for (var i = 0; i < items_names.length; i++) {
						var key = items_names[i];
						
						if (task_property_values[key + "_type"] == "array")
							items_to_add.push({
								key: key,
								key_type: "string",
								items: task_property_values[key]
							});
						else if (task_property_values[key])
							items_to_add.push({
								key: key,
								key_type: "string",
								value: task_property_values[key],
								value_type: task_property_values[key + "_type"]
							});
					}
					
					//prepare relations
					if (!$.isArray(task_property_values["relations"]) && !$.isPlainObject(task_property_values["relations"])) {
						task_property_values["relations_type"] = "array";
						task_property_values["relations"] = {};
					}
					
					//add items_to_add to relations
					if (items_to_add.length > 0) {
						if ($.isPlainObject(task_property_values["relations"])) {
							var max_index = 0;
							
							for (var idx in task_property_values["relations"])
								if (idx > max_index)
									max_index = idx;
							
							max_index++;
							
							for (var i = 0; i < items_to_add.length; i++)
								task_property_values["relations"][max_index + i] = items_to_add[i];
							
						}
						else if ($.isArray(task_property_values["relations"])) {
							for (var i = 0; i < items_to_add.length; i++)
								task_property_values["relations"].push(items_to_add[i]);
						}
					}
					
					//remove items_names from task_property_values, since they were added to the relations
					for (var i = 0; i < items_names.length; i++) {
						var key = items_names[i];
						
						task_property_values[key] = null;
						delete task_property_values[key];
						
						task_property_values[key + "_type"] = null;
						delete task_property_values[key + "_type"];
					}
					
					//console.log(task_property_values);
				}
			}
		}
	},
	
	onCancelTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		return true;	
	},
	
	onCompleteLabel : function(task_id) {
		return ProgrammingTaskUtil.onEditLabel(task_id);
	},
	
	onTaskCreation : function(task_id) {
		setTimeout(function() {
			var task_property_values = myWFObj.getTaskFlowChart().TaskFlow.tasks_properties[task_id];
			ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithTaskPropertiesValues(task_property_values);
		
			var label = DBDAOActionTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
		
			onEditLabel(task_id);
		
			var default_method_obj_str = BrokerOptionsUtilObj.getDefaultBroker(DBDAOActionTaskPropertyObj.brokers_options);
			if (!task_property_values["method_obj"] && default_method_obj_str)
				myWFObj.getTaskFlowChart().TaskFlow.tasks_properties[task_id]["method_obj"] = default_method_obj_str;
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 30);
	},
	
	getDefaultExitLabel : function(task_property_values) {
		if (task_property_values["method_name"]) {
			var method_obj = (task_property_values["method_obj"].trim().substr(0, 1) != "$" ? "$" : "") + task_property_values["method_obj"];
			var method_name = task_property_values["method_name"];
			var table_name = ProgrammingTaskUtil.getValueString(task_property_values["table_name"], task_property_values["table_name_type"]);
			
			var attributes = task_property_values["attributes_type"] == "array" ? ArrayTaskUtilObj.arrayToString(task_property_values["attributes"]) : ProgrammingTaskUtil.getValueString(task_property_values["attributes"], task_property_values["attributes_type"]);
			attributes = attributes ? attributes : "null";
			
			var conditions = task_property_values["conditions_type"] == "array" ? ArrayTaskUtilObj.arrayToString(task_property_values["conditions"]) : ProgrammingTaskUtil.getValueString(task_property_values["conditions"], task_property_values["conditions_type"]);
			conditions = conditions ? conditions : "null";
			
			var relations = task_property_values["relations_type"] == "array" ? ArrayTaskUtilObj.arrayToString(task_property_values["relations"]) : ProgrammingTaskUtil.getValueString(task_property_values["relations"], task_property_values["relations_type"]);
			relations = relations ? relations : "null";
			
			var parent_conditions = task_property_values["parent_conditions_type"] == "array" ? ArrayTaskUtilObj.arrayToString(task_property_values["parent_conditions"]) : ProgrammingTaskUtil.getValueString(task_property_values["parent_conditions"], task_property_values["parent_conditions_type"]);
			parent_conditions = parent_conditions ? parent_conditions : "null";
			
			var options = task_property_values["options_type"] == "array" ? ArrayTaskUtilObj.arrayToString(task_property_values["options"]) : ProgrammingTaskUtil.getValueString(task_property_values["options"], task_property_values["options_type"]);
			options = options ? options : "null";
			
			var label = ProgrammingTaskUtil.getResultVariableString(task_property_values) + method_obj + '->' + method_name + '(' + table_name;
			
			if (method_name == "insertObject" || method_name == "findObjectsColumnMax")
				label += ', ' + attributes;
			else if (method_name == "updateObject" || method_name == "findObjects")
				label += ', ' + attributes + ', ' + conditions;
			else if (method_name == "deleteObject" || method_name == "countObjects")
				label += ', ' + conditions;
			else if (method_name == "findRelationshipObjects" || method_name == "countRelationshipObjects")
				label += ', ' + relations + ', ' + parent_conditions;
			
			label += ', ' + options + ')';
			
			return label;
		}
		return "";
	},
	
	convertSimpleSettingsToArray : function(task_html_elm, html_elm) {
		//prepare atribuets, conditions, relations and parent_conditions
		var method_name = task_html_elm.find(".method_name select").val();
		var checkboxes = html_elm.find(".attr_active:checked");
		
		html_elm.find(".task_property_field").removeClass("task_property_field");
		
		for (var i = 0; i < checkboxes.length; i++) {
			if (method_name == "findObjectsColumnMax") //remove all checked boxes for attributes on findObjectsColumnMax, bc only 1 column can be selected.
				$(checkboxes[i]).addClass("task_property_field");
			else
				$(checkboxes[i]).parent().find("input.attr_alias:visible, input.attr_value:visible, select.attr_operator:visible").addClass("task_property_field");
		}
		
		//get html item to plain object
		var obj = FormFieldsUtilObj.getFormSettingsDataSettings(html_elm);
		
		//simplify conditions with operator "="
		if (html_elm.is(".conditions_options, .parent_conditions_options") && $.isPlainObject(obj)) {
			for (var attr_name in obj) {
				var attr_obj = obj[attr_name];
				
				if ($.isPlainObject(attr_obj) && attr_obj.hasOwnProperty("value") && (!attr_obj.hasOwnProperty("operator") || !attr_obj["operator"] || attr_obj["operator"] == "="))
					obj[attr_name] = attr_obj["value"];
			}
			//console.log(obj);
		}
		
		//convert plain object to array
		return FormFieldsUtilObj.convertFormSettingsObjectToArray(obj);
	},
	
	convertArrayToSimpleSettings : function(task_property_values, key) {
		if (task_property_values[key + "_type"] == "array" && (
			$.isPlainObject(task_property_values[key]) || $.isArray(task_property_values[key])
		)) {
			if ($.isPlainObject(task_property_values[key]) && task_property_values[key].hasOwnProperty("key"))
				task_property_values[key] = [ task_property_values[key] ];
			
			//convert simple array to an associative array, but only if is a simple array like: ["attr name x", "attr name y"]
			if (key == "attributes") {
				var obj = FormFieldsUtilObj.convertFormSettingsDataArrayToSettings(task_property_values[key]);
				
				if ($.isPlainObject(obj)) {
					var new_values = {};
					var changed = false;
					
					for (var k in obj) {
						if ($.isNumeric(k) && typeof obj[k] == "string") {
							k = obj[k];
							obj[k] = "";
							changed = true;
						}
						
						new_values[k] = obj[k];
					}
					
					if (changed)
						task_property_values[key] = FormFieldsUtilObj.convertFormSettingsObjectToArray(new_values);
				}
			}
			
			var obj = FormFieldsUtilObj.convertFormSettingsDataArrayToSettings(task_property_values[key]);
			var arr = FormFieldsUtilObj.convertFormSettingsObjectToArray(obj);
			
			//prepare numeric indexes to be as an array
			var new_arr_data = [];
			$.each(task_property_values[key], function(i, item) {
				if (item["value"] == null && item["value_type"] == "string") //uniform the value so we can compare it with the arr variable
					item["value"] = "";
				else if (item.hasOwnProperty("items") && $.isPlainObject(item["items"])) {
					var new_items = [];
					
					$.each(item["items"], function(j, sub_item) {
						new_items.push(sub_item);
					});
					
					item["items"] = new_items;
				}
				
				new_arr_data.push(item);
			});
			
			if (JSON.stringify(arr) == JSON.stringify(new_arr_data)) {
				task_property_values[key + "_type"] = "options";
				task_property_values[key] = obj;
			}
			else {
				//console.log(arr);
				//console.log(new_arr_data);
				//console.log(JSON.stringify(arr));
				//console.log(JSON.stringify(new_arr_data));
			}
		}
		
		return task_property_values;
	},
	
	onChangeMethodName : function(elm) {
		var method_name = $(elm).val();
		var task_html_elm = $(elm).parent().parent();
		
		task_html_elm.children(".get_automatically, .table_name, .attrs, .conds, .kys, .rels, .parent_conds").hide();
		task_html_elm.removeClass("insertObject findObjectsColumnMax updateObject findObjects deleteObject countObjects findRelationshipObjects countRelationshipObjects");
		
		if (method_name) {
			task_html_elm.addClass(method_name);
			task_html_elm.children(".get_automatically, .table_name").show();
			
			if (method_name == "insertObject" || method_name == "findObjectsColumnMax") {
				task_html_elm.children(".attrs").show();
				
				if (method_name == "findObjectsColumnMax") //remove all checked boxes for attributes on findObjectsColumnMax, bc only 1 column can be selected.
					task_html_elm.find(".attrs > .attributes_options li").removeClass("attr_activated").find("input.attr_active").removeAttr("checked").prop("checked", false);
			}
			else if (method_name == "updateObject" || method_name == "findObjects")
				task_html_elm.children(".attrs, .conds").show();
			else if (method_name == "deleteObject" || method_name == "countObjects")
				task_html_elm.children(".conds").show();
			else if (method_name == "findRelationshipObjects" || method_name == "countRelationshipObjects")
				task_html_elm.children(".attrs, .conds, .kys, .rels, .parent_conds").show();
		}
	},
	
	onChangeAttributesType : function(elm) {
		this.onChangeParametersType(elm, "attributes");
	},
	
	onChangeConditionsType : function(elm) {
		this.onChangeParametersType(elm, "conditions");
	},
	
	onChangeKeysType : function(elm) {
		this.onChangeParametersType(elm, "keys");
	},
	
	onChangeRelElmType : function(elm) {
		this.onChangeParametersType(elm, "relations");
		
		elm = $(elm);
		var task_html_elm = elm.parent().closest(".db_dao_action_task_html");
		
		if (elm.val() != "array")
			task_html_elm.children(".attrs, .conds, .kys").hide();
		else
			task_html_elm.children(".attrs, .conds, .kys").show();
	},
	
	onChangeParentConditionsType : function(elm) {
		this.onChangeParametersType(elm, "parent_conditions");
	},
	
	onChangeParametersType : function(elm, prefix_class) {
		elm = $(elm);
		var type = elm.val();
		
		var parent = elm.parent();
		var arr_elm = parent.children("." + prefix_class);
		var code_elm = parent.children("." + prefix_class + "_code");
		var options_elm = parent.children("." + prefix_class + "_options");
		
		if (type == "array") {
			code_elm.hide();
			arr_elm.show();
			
			if (elm.attr("current_type") == "options" && options_elm.children("li:not(.no_items):not(.add)").length > 0 && confirm("Do you wish to convert these options to an array?")) {
				var items = this.convertSimpleSettingsToArray(parent.parent().closest(".db_dao_action_task_html"), options_elm);
				ArrayTaskUtilObj.onLoadArrayItems(arr_elm, items, "");
			}
			else if (!arr_elm.find(".items")[0]) {
				var items = {0: {key_type: "null", value_type: "string"}};
				ArrayTaskUtilObj.onLoadArrayItems(arr_elm, items, "");
			}
			
			options_elm.hide();
		}
		else if (type == "options") {
			code_elm.hide();
			options_elm.show();
			arr_elm.hide();
			
			if (elm.attr("current_type") == "array" && arr_elm.find(".item").length > 0 && confirm("Do you wish to convert this array to options?")) {
				var WF = myWFObj.getTaskFlowChart();
				var query_string = WF.Property.getPropertiesQueryStringFromHtmlElm(arr_elm, "task_property_field");
				var items = {};
				parse_str(query_string, items);
				
				var first_key = arr_elm.children("ul").attr("parent_name");
				items = items[first_key] ? items[first_key] : {};
				
				var options = FormFieldsUtilObj.convertFormSettingsDataArrayToSettings(items);
				this.loadSavedTableAttributesOptions(options_elm, options);
			}
		}
		else {
			code_elm.show();
			options_elm.hide();
			arr_elm.hide();
		}
		
		elm.attr("current_type", type);
		
		ProgrammingTaskUtil.onChangeTaskFieldType(elm[0]);
	},
	
	onChooseTable : function(elm) {
		if (typeof this.on_choose_table_callback == "function")
			this.on_choose_table_callback(elm, function(table_and_attributes) {
				DBDAOActionTaskPropertyObj.chooseTable(elm, table_and_attributes);
			});
			
	},
	
	chooseTable : function(elm, table_and_attributes) {
		if (table_and_attributes && $.isPlainObject(table_and_attributes)) {
			var table = table_and_attributes["table"];
			var attributes = table_and_attributes["attributes"];
			var task_html_elm = $(elm).parent().closest(".db_dao_action_task_html");
			
			//convert attributes array into a plain object where keys are the attribute names.
			if ($.isArray(attributes)) {
				var attributes_obj = {};
				
				for (var i = 0; i < attributes.length; i++) 
					attributes_obj[ attributes[i] ] = {};
				
				attributes = attributes_obj;
			}
			
			//prepare table name
			task_html_elm.find(".table_name input").val(table);
			task_html_elm.find(".table_name select").val("string");
			
			//prepare table attributes fields html
			var method_name_elm = task_html_elm.find(".method_name select");
			var method_name = method_name_elm.val();
			var attributes_options = task_html_elm.find(".attrs > .attributes_options");
			var conditions_options = task_html_elm.find(".conds > .conditions_options");
			var keys_options = task_html_elm.find(".kys > .keys_options");
			var relations_options = task_html_elm.find(".rels > .relations_options");
			var parent_conditions_options = task_html_elm.find(".parent_conds > .parent_conditions_options");
			
			DBDAOActionTaskPropertyObj.loadNewTableAttributesOptions(attributes_options, attributes, method_name);
			DBDAOActionTaskPropertyObj.loadNewTableAttributesOptions(conditions_options, attributes, method_name);
			DBDAOActionTaskPropertyObj.loadNewTableAttributesOptions(keys_options, null, method_name);
			DBDAOActionTaskPropertyObj.loadNewTableAttributesOptions(relations_options, null, method_name);
			DBDAOActionTaskPropertyObj.loadNewTableAttributesOptions(parent_conditions_options, null, method_name);
			
			//prepare fields types
			var attributes_type = task_html_elm.find(".attrs > .attributes_type");
			var conditions_type = task_html_elm.find(".conds > .conditions_type");
			var keys_type = task_html_elm.find(".kys > .keys_type");
			var relations_type = task_html_elm.find(".rels > .relations_type");
			var parent_conditions_type = task_html_elm.find(".parent_conds > .parent_conditions_type");
			
			attributes_type.val("options");
			conditions_type.val("options");
			keys_type.val("options");
			relations_type.val("options");
			parent_conditions_type.val("options");
			
			DBDAOActionTaskPropertyObj.onChangeAttributesType(attributes_type[0]);
			DBDAOActionTaskPropertyObj.onChangeConditionsType(conditions_type[0]);
			DBDAOActionTaskPropertyObj.onChangeKeysType(keys_type[0]);
			DBDAOActionTaskPropertyObj.onChangeRelElmType(relations_type[0]);
			DBDAOActionTaskPropertyObj.onChangeParentConditionsType(parent_conditions_type[0]);
			DBDAOActionTaskPropertyObj.onChangeMethodName(method_name_elm[0]);
		}
	},
	
	onAddTableAttributeOption : function(elm) {
		var name = prompt("Write the attribute name you pretend to add?");
		
		if (name && name.replace(/\s+/g, "") != "") {
			DBDAOActionTaskPropertyObj.addTableAttributeOption(elm, {
				name : name.replace(/\s+/g, ""),
				checked : true,
			});
		}
	},
	
	loadNewTableAttributesOptions : function(table_items_elm, table_items_options, method_name) {
		if (table_items_elm[0]) {
			//clean html
			table_items_elm.children("li:not(.no_items):not(.add)").remove();
			
			if (table_items_options && $.isPlainObject(table_items_options) && !$.isEmptyObject(table_items_options)) {
				//hide no_items element
				table_items_elm.children("li.no_items").hide();
				
				//prepare html
				var add_icon = table_items_elm.children(".add");
				
				for (var item_name in table_items_options) {
					var item_props = table_items_options[item_name];
					item_props = item_props && $.isPlainObject(item_props) ? item_props : {};
					
					var item_settings = {name : item_name};
					
					if (table_items_elm.is(".conditions_options, .parent_conditions_options"))
						item_settings["checked"] = item_props["primary_key"];
					else
						item_settings["checked"] = method_name != "findObjectsColumnMax";
					
					this.addTableAttributeOption(add_icon[0], item_settings);
				}
			}
			else //show no_items element
				table_items_elm.children("li.no_items").show();
		}
	},
	
	loadSavedTableAttributesOptions : function(table_items_elm, table_items_options) {
		if (table_items_elm[0]) {
			//clean html
			table_items_elm.children("li:not(.no_items):not(.add)").remove();
			
			if (table_items_options && $.isPlainObject(table_items_options) && !$.isEmptyObject(table_items_options)) {
				//hide no_items element
				table_items_elm.children("li.no_items").hide();
				
				//prepare html
				var add_icon = table_items_elm.children(".add");
				var add_icon_parent = add_icon.parent();
				
				for (var item_name in table_items_options) {
					var item_value = table_items_options[item_name];
					
					var item_settings = {
						name : item_name,
						checked : true,
						value : item_value,
						alias : item_value,
					};
					
					this.addTableAttributeOption(add_icon[0], item_settings);
				}
			}
			else //show no_items element
				table_items_elm.children("li.no_items").show();
		}
	},
	
	addTableAttributeOption : function(elm, settings) {
		var name = settings["name"];
		
		if (name || $.isNumeric(name)) { //.keys will have the [name] with numeric fields from loadSavedTableAttributesOptions method.
			elm = $(elm);
			var p = elm.parent();
			var checked = settings.hasOwnProperty("checked") ? settings["checked"] : false;
			var value = settings.hasOwnProperty("value") ? settings["value"] : "";
			var alias = settings.hasOwnProperty("alias") ? settings["alias"] : "";
			var operator = null;
			var html = '';
			
			if ($.isPlainObject(value)) {
				operator = value["operator"];
				value = value["value"];
			}
			
			var n = name != null ? ("" + name).replace(/"/g, "&quot;") : "";
			var v = value != null ? ("" + value).replace(/"/g, "&quot;") : "";
			var a = alias != null ? ("" + alias).replace(/"/g, "&quot;") : "";
			
			var operators = ["=", "!=", ">", ">=", "<=", "like", "not like", "in", "not in", "is", "is not"];
			var operator_options = "";
			
			for (var i = 0, t = operators.length; i < t; i++)
				operator_options += '<option' + (operator && operator == operators[i] ? ' selected' : '') + '>' + operators[i] + '</option>';
			
			if (p.is(".conditions_options, .parent_conditions_options")) {
				html = '<li ' + (checked ? ' class="attr_activated"' : '') + '>'
					+ '	<input class="attr_active" type="checkbox" onclick="DBDAOActionTaskPropertyObj.activateDBActionTableAttributeOption(this)" ' + (checked ? 'checked' : '') + '>'
					+ '	<label>' + name + '</label>'
					+ '	<select class="attr_operator" name="' + n + '[operator]">'
					+ operator_options
					+ '	</select>'
					+ '	<input class="attr_value" type="text" name="' + n + '[value]" value="' + v + '" placeHolder="Write the value here">'
					+ '	<span class="icon add_variable" onclick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)" input_selector=".attr_value">Add Variable</span>'
					+ '	<span class="icon delete" title="Remove item" onClick="$(this).parent().remove();"></span>'
					+ '</li>';
			}
			else if (p.is(".keys_options")) {
				var idx = 0;
				var lis = p.children("li[data-idx]");
				
				for (var i = 0, t = lis.length; i < t; i++) {
					var li_idx = parseInt(lis[i].getAttribute("data-idx"));
					
					if (li_idx >= idx)
						idx = li_idx + 1;
				}
				
				settings = settings.hasOwnProperty("value") ? settings["value"] : {};
				var code_exists = false;
				
				for (var sn in settings) {
					var sv = settings[sn];
					
					if (sv.indexOf("<?") != -1) {
						code_exists = true;
						settings[sn] = settings[sn].replace(/<\?(=|php|)\s*/ig, "").replace(/\s*\?>/g, "");
					}
				}
				
				if (code_exists)
					myWFObj.getTaskFlowChart().StatusMessage.showError("There was a problem converting some settings, since they contain pure programming code. Please choose the 'array' type instead.");
				
				var ptable = settings.hasOwnProperty("ptable") ? settings["ptable"] : "";
				var pcolumn = settings.hasOwnProperty("pcolumn") ? settings["pcolumn"] : (name && !$.isNumeric(name) ? name : "");
				var ftable = settings.hasOwnProperty("ftable") ? settings["ftable"] : "";
				var fcolumn = settings.hasOwnProperty("fcolumn") ? settings["fcolumn"] : "";
				var column_join = settings.hasOwnProperty("join") ? settings["join"].toLowerCase() : "";
				
				if (ptable)
					pcolumn = ptable + "." + pcolumn;
				
				if (ftable)
					fcolumn = ftable + "." + fcolumn;
				
				pcolumn = pcolumn != null ? ("" + pcolumn).replace(/"/g, "&quot;") : "";
				fcolumn = fcolumn != null ? ("" + fcolumn).replace(/"/g, "&quot;") : "";
				
				html = '<li ' + (checked ? ' class="attr_activated"' : '') + ' data-idx="' + idx + '">'
					+ '	<input class="attr_active" type="checkbox" onclick="DBDAOActionTaskPropertyObj.activateDBActionTableAttributeOption(this)" ' + (checked ? 'checked' : '') + '>'
					+ '	<input class="attr_value" type="text" name="' + idx + '[pcolumn]" value="' + pcolumn + '" PlaceHolder="Parent table.column">'
					+ '	<select class="attr_operator" name="' + idx + '[operator]">'
					+ operator_options
					+ '	</select>'
					+ '	<input class="attr_value" type="text" name="' + idx + '[fcolumn]" value="' + fcolumn + '" PlaceHolder="Child table.column">'
					+ '	<input class="attr_value column_value" type="text" name="' + idx + '[value]" value="' + v + '" placeHolder="Value if apply">'
					+ '	<span class="icon add_variable" onclick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)" input_selector=".attr_value.column_value">Add Variable</span>'
					+ '	<select class="attr_operator" name="' + idx + '[join]">'
					+ '		<option' + (column_join == "inner" ? " selected" : "") + '>inner</option>'
					+ '		<option' + (column_join == "left" ? " selected" : "") + '>left</option>'
					+ '		<option' + (column_join == "right" ? " selected" : "") + '>right</option>'
					+ '	</select>'
					+ '	<span class="icon delete" title="Remove item" onClick="$(this).parent().remove();"></span>'
					+ '</li>';
			}
			else {
				html = '<li ' + (checked ? ' class="attr_activated"' : '') + '>'
					+ '	<input class="attr_active" type="checkbox" name="' + n + '" value="" onclick="DBDAOActionTaskPropertyObj.activateDBActionTableAttributeOption(this)" ' + (checked ? 'checked' : '') + '>'
					+ '	<label>' + name + '</label>'
					+ '	<input class="attr_value" type="text" name="' + n + '" value="' + v + '" PlaceHolder="Write the value here">'
					+ '	<input class="attr_alias" type="text" name="' + n + '" value="' + a + '" PlaceHolder="Write the alias here">'
					+ '	<span class="icon add_variable" onclick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)" input_selector=".attr_value, .attr_alias">Add Variable</span>'
					+ '	<span class="icon delete" title="Remove item" onClick="$(this).parent().remove();"></span>'
					+ '</li>';
			}
			
			var item = $(html);
			p.append(item);
			
			ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml(item);
		
			return item;
		}
		
		return null;
	},
	
	activateDBActionTableAttributeOption : function(elm) {
		elm = $(elm);
		var parent_li = elm.parent();
		
		if (elm.is(":checked"))
			parent_li.addClass("attr_activated");
		else 
			parent_li.removeClass("attr_activated");
		
		var ul = elm.parent().closest("ul");
		
		if (ul.is(".attributes_options")) { //only if attributes
			var task_html_elm = elm.parent().closest(".db_dao_action_task_html");
			var parent_attr_name = parent_li.children(".attr_value").attr("name");
			
			if (task_html_elm.is(".findObjectsColumnMax")) {
				ul.find("li").each(function(idx, li) {
					li = $(li);
					
					if (li.children(".attr_value").attr("name") != parent_attr_name)
						li.removeClass("attr_activated").find("input.attr_active").removeAttr("checked").prop("checked", false);
				});
			}
		}
	},
};
