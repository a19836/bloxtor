/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

var CallHibernateMethodTaskPropertyObj = {
	
	on_choose_hibernate_object_method_callback : null,
	brokers_options : null,
	
	available_methods_args : {
		"insert": ["data", "ids", "options"],
		"insertAll": ["data", "statuses", "ids", "options"],
		"update": ["data", "options"],
		"updateAll": ["data", "statuses", "options"],
		"insertOrUpdate": ["data", "ids", "options"],
		"insertOrUpdateAll": ["data", "statuses", "ids", "options"],
		"updatePrimaryKeys": ["data", "options"],
		"delete": ["data", "options"],
		"deleteAll": ["data", "statuses", "options"],
		"findById": ["data", "data", "options"],
		"find": ["data", "options"],
		"count": ["data", "options"],
		"findRelationships": ["parent_ids", "options"],
		"findRelationship": ["rel_name", "parent_ids", "options"],
		"countRelationships": ["parent_ids", "options"],
		"countRelationship": ["rel_name", "parent_ids", "options"],
		"callQuerySQL": ["query_type", "query_id", "data", "options"],
		"callQuery": ["query_type", "query_id", "data", "options"],
		"callInsertSQL": ["query_id", "data", "options"],
		"callInsert": ["query_id", "data", "options"],
		"callUpdateSQL": ["query_id", "data", "options"],
		"callUpdate": ["query_id", "data", "options"],
		"callDeleteSQL": ["query_id", "data", "options"],
		"callDelete": ["query_id", "data", "options"],
		"callSelectSQL": ["query_id", "data", "options"],
		"callSelect": ["query_id", "data", "options"],
		"callProcedureSQL": ["query_id", "data", "options"],
		"callProcedure": ["query_id", "data", "options"],
		"getFunction": ["function_name", "data", "options"],
		"getData": ["sql", "options"],
		"setData": ["sql", "options"],
		"getInsertedId": ["options"],
	},
	
	available_native_methods : ["insert", "insertAll", "update", "updateAll", "insertOrUpdate", "insertOrUpdateAll", "updatePrimaryKeys", "delete", "deleteAll", "findById", "find", "count"],
	available_relationship_methods : ["findRelationships", "findRelationship", "countRelationships", "countRelationship"],
	available_query_methods : ["callInsertSQL", "callInsert", "callUpdateSQL", "callUpdate", "callDeleteSQL", "callDelete", "callSelectSQL", "callSelect", "callProcedureSQL", "callProcedure"],
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		ProgrammingTaskUtil.prepareEditSourceIcon(properties_html_elm);
		
		var task_html_elm = $(properties_html_elm).find(".call_hibernate_method_task_html");
		ProgrammingTaskUtil.setResultVariableType(task_property_values, task_html_elm);
		
		//LOAD BROKERS
		BrokerOptionsUtilObj.initFields(task_html_elm.find(".broker_method_obj"), CallHibernateMethodTaskPropertyObj.brokers_options, task_property_values["method_obj"]);
		
		var exists = task_property_values["broker_method_obj_type"] == "exists_hbn_var";
		var mo = task_property_values["method_obj"];
		mo = !mo && !exists ? BrokerOptionsUtilObj.getDefaultBroker(CallHibernateMethodTaskPropertyObj.brokers_options) : mo;
		mo = mo && mo.trim().substr(0, 1) == '$' ? mo.trim().substr(1) : mo;
		
		var select = task_html_elm.find(".broker_method_obj select");
		select.append('<option value="exists_hbn_var" default_method_obj="' + (exists ? mo : "") + '">From some previous hbn variable</option>');
		select.val( exists ? "exists_hbn_var" : mo );
		CallHibernateMethodTaskPropertyObj.onChangeBrokerMethodObj(select[0]);
		task_html_elm.find(".broker_method_obj input").val(mo);
		
		//LOAD MODULE ID
		var module_id = task_property_values["module_id"] ? "" + task_property_values["module_id"] : "";
		module_id = task_property_values["module_id_type"] == "variable" && module_id.trim().substr(0, 1) == '$' ? module_id.trim().substr(1) : module_id;
		task_html_elm.find(".module_id input").val(module_id);
		
		//LOAD SERVICE ID
		var service_id = task_property_values["service_id"] ? "" + task_property_values["service_id"] : "";
		service_id = task_property_values["service_id_type"] == "variable" && service_id.trim().substr(0, 1) == '$' ? service_id.trim().substr(1) : service_id;
		task_html_elm.find(".service_id input").val(service_id);
		
		//LOAD SERVICE METHOD
		var available_methods = '';
		for (var mn in CallHibernateMethodTaskPropertyObj.available_methods_args) {
			available_methods += '<option>' + mn + '</option>';
		}
		task_html_elm.find(".service_method select.service_method_string").html(available_methods);
		
		var service_method = task_property_values["service_method"] ? "" + task_property_values["service_method"] : "";
		if (jQuery.isEmptyObject(task_property_values) || task_property_values["service_method_type"] == "string") {
			task_html_elm.find(".service_method select.service_method_string").val(service_method);
			task_html_elm.find(".service_method input.service_method_code").hide();
		}
		else {
			service_method = task_property_values["service_method_type"] == "variable" && service_method.trim().substr(0, 1) == '$' ? service_method.trim().substr(1) : service_method;
			task_html_elm.find(".service_method input.service_method_code").val(service_method);
			task_html_elm.find(".service_method select.service_method_string").hide();
		}
		CallHibernateMethodTaskPropertyObj.onChangeServiceMethodType( task_html_elm.find(".service_method select.service_method_type") );
		
		//LOAD OPTIONS
		LayerOptionsUtilObj.onLoadTaskProperties(task_html_elm, task_property_values);
		
		//LOAD ARGS
		var service_method_args = task_html_elm.children(".service_method_args");
		CallHibernateMethodTaskPropertyObj.prepareServiceMethodArgs(service_method_args, service_method);
		
		var sma_data = task_property_values["sma_query_type"];
		var sma_data_div = service_method_args.children(".sma_query_type");
		if (!jQuery.isEmptyObject(task_property_values) && task_property_values["sma_query_type_type"] != "string") {
			sma_data_div.children("input").show();
			sma_data_div.children("select.sma_query_type_string").hide();
		}
		
		var sma_data = task_property_values["sma_data"];
		var sma_data_div = service_method_args.children(".sma_data");
		if (task_property_values["sma_data_type"] == "array") {
			ArrayTaskUtilObj.onLoadArrayItems( sma_data_div.children(".sma_data").first(), sma_data, "");
			sma_data_div.children("input").val("");
		}
		else {
			sma_data = sma_data ? "" + sma_data : "";
			sma_data = task_property_values["sma_data_type"] == "variable" && sma_data.trim().substr(0, 1) == '$' ? sma_data.trim().substr(1) : sma_data;
			sma_data_div.children("input").val(sma_data);
		}
		CallHibernateMethodTaskPropertyObj.onChangeSMAType(sma_data_div.children("select")[0]);
		
		var sma_parent_ids = task_property_values["sma_parent_ids"];
		var sma_parent_ids_div = service_method_args.children(".sma_parent_ids");
		if (task_property_values["sma_parent_ids_type"] == "array") {
			ArrayTaskUtilObj.onLoadArrayItems( sma_parent_ids_div.children(".sma_parent_ids").first(), sma_parent_ids, "");
			sma_parent_ids_div.children("input").val("");
		}
		else {
			sma_parent_ids = sma_parent_ids ? "" + sma_parent_ids : "";
			sma_parent_ids = task_property_values["sma_parent_ids_type"] == "variable" && sma_parent_ids.trim().substr(0, 1) == '$' ? sma_parent_ids.trim().substr(1) : sma_parent_ids;
			sma_parent_ids_div.children("input").val(sma_parent_ids);
		}
		CallHibernateMethodTaskPropertyObj.onChangeSMAType(sma_parent_ids_div.children("select")[0]);
		
		var sma_sql = task_property_values["sma_sql"];
		var sma_sql_div = service_method_args.children(".sma_sql");
		if (jQuery.isEmptyObject(task_property_values) || task_property_values["sma_sql_type"] == "string") {
			sma_sql_div.children("textarea.sql_editor").val(sma_sql);
			sma_sql_div.children("textarea.sql_editor").show();
			sma_sql_div.children("input").val("");
		}
		else {
			sma_sql = sma_sql ? "" + sma_sql : "";
			sma_sql = task_property_values["sma_sql_type"] == "variable" && sma_sql.trim().substr(0, 1) == '$' ? sma_sql.trim().substr(1) : sma_sql;
			sma_sql_div.children("input").val(sma_sql);
		}
		CallHibernateMethodTaskPropertyObj.onChangeSMASQLType(sma_sql_div.children("select")[0]);
		
		var sma_options = task_property_values["sma_options"];
		var sma_options_div = service_method_args.children(".sma_options");
		if (task_property_values["sma_options_type"] == "array") {
			LayerOptionsUtilObj.initOptionsArray( sma_options_div.children(".sma_options").first(), sma_options);
			sma_options_div.children("input").val("");
		}
		else {
			sma_options = sma_options ? "" + sma_options + "" : "";
			sma_options = task_property_values["sma_options_type"] == "variable" && sma_options.trim().substr(0, 1) == '$' ? sma_options.trim().substr(1) : sma_options;
			sma_options_div.children("input").val(sma_options);
		}
		CallHibernateMethodTaskPropertyObj.onChangeSMAOptionsType(sma_options_div.children("select")[0]);
		
		//PREPARE SQL EDITOR
		if (ace && ace.edit) {
			var parent = service_method_args.children(".sma_sql");
			
			ace.require("ace/ext/language_tools");
			var editor = ace.edit( parent.children("textarea.sql_editor")[0] );
			editor.setTheme("ace/theme/chrome");
			editor.session.setMode("ace/mode/sql");
			editor.setAutoScrollEditorIntoView(true);
			editor.setOptions({
				enableBasicAutocompletion: true,
				enableSnippets: true,
				enableLiveAutocompletion: false,
			});
			
			parent.find("textarea.ace_text-input").removeClass("ace_text-input"); //fixing problem with scroll up, where when focused or pressed key inside editor the page scrolls to top
			
			parent.data("editor", editor);
		}
	},
	
	prepareServiceMethodArgs : function(service_method_args, service_method) {
		var args = service_method ? this.available_methods_args[service_method] : this.available_methods_args["insert"];
		
		service_method_args.children("div").hide();
		for (var idx in args) {
			var an = args[idx];
			var sma_elm = service_method_args.children(".sma_" + an);
			
			sma_elm.show();
			
			if (an == "query_type")
				this.onChangeSMAQueryTypeType( sma_elm.children("select.service_method_arg_type")[0] );
		}
	},
	
	onChangeBrokerMethodObj : function(elm) {
		elm = $(elm);
		
		var type = elm.val();
		var parent = elm.parent().parent();
		
		if (type == "exists_hbn_var") {
			var option = elm.children("option[value='exists_hbn_var']");
			elm.parent().children("input").val( option.attr("default_method_obj") );
			
			parent.children(".get_automatically, .module_id, .service_id, .opts").hide();
		}
		else {
			parent.children(".get_automatically, .module_id, .service_id, .opts").show();
			
			BrokerOptionsUtilObj.onBrokerChange(elm[0]);
		}
	},
	
	chooseCreatedBrokerVariable : function(elm) {
		elm = $(elm);
		
		var select = elm.parent().children("select");
		var type = elm.parent().children("select").val();
		
		if (type == "exists_hbn_var") {
			ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(elm[0]);
		}
		else {
			BrokerOptionsUtilObj.chooseCreatedBrokerVariable(elm[0]);
		}
	},
	
	onChangeServiceMethod : function(elm) {
		elm = $(elm);
		
		var service_method = elm.val();
		var service_method_args = elm.parent().parent().children(".service_method_args");
		
		this.prepareServiceMethodArgs(service_method_args, service_method);
	},
	
	onChangeServiceMethodType : function(elm) {
		var service_method_type = $(elm).val();
		
		var parent = $(elm).parent();
		
		if (service_method_type == "string") {
			parent.children(".service_method_string").show();
			parent.children("input").hide();
		}
		else {
			parent.children(".service_method_string").hide();
			parent.children("input").show();
		}
		
		ProgrammingTaskUtil.onChangeTaskFieldType(elm);
	},
	
	onChangeSMAType : function(elm) {
		var type = $(elm).val();
		
		var parent = $(elm).parent();
		var items_elm = parent.children(".array_items");
		
		if (type == "array") {
			parent.children("input").hide();
			items_elm.show();
			
			if (!items_elm.find(".items")[0]) {
				var items = {0: {key_type: "null", value_type: "string"}};
				ArrayTaskUtilObj.onLoadArrayItems(items_elm, items, "");
			}
		}
		else {
			parent.children("input").show();
			items_elm.hide();
		}
		
		ProgrammingTaskUtil.onChangeTaskFieldType(elm);
	},
	
	onChangeSMASQLType : function(elm) {
		var type = $(elm).val();
		
		var parent = $(elm).parent();
		
		if (type == "string") {
			parent.children("textarea.sql_editor, .ace_editor").show();
			parent.children("input").hide();
			
			var editor = parent.data("editor");
			if (editor) {
				editor.resize();
				editor.focus();
			}
		}
		else {
			parent.children("textarea.sql_editor, .ace_editor").hide();
			parent.children("input").show();
		}
		
		ProgrammingTaskUtil.onChangeTaskFieldType(elm);
	},
	
	onChangeSMAOptionsType : function(elm){
		var options_type = $(elm).val();
		
		var parent = $(elm).parent();
		var options_elm = parent.children(".sma_options");
		
		if (options_type == "array") {
			parent.children("input").hide();
			options_elm.show();
			
			if (!options_elm.find(".items")[0]) {
				var items = {0: {key_type: "string", value_type: "string"}};
				LayerOptionsUtilObj.initOptionsArray(options_elm, items);
			}
		}
		else {
			parent.children("input").show();
			options_elm.hide();
		}
		
		ProgrammingTaskUtil.onChangeTaskFieldType(elm);
	},
	
	onChangeSMAQueryTypeType : function(elm) {
		var type = $(elm).val();
		
		var parent = $(elm).parent();
		var select = parent.children("select.sma_query_type_string");
		var input = parent.children("input");
		
		if (type == "string") {
			select.val(input.val());
			select.show();
			input.hide();
		}
		else {
			input.val(select.val());
			select.hide();
			input.show();
		}
		
		ProgrammingTaskUtil.onChangeTaskFieldType(elm);
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".call_hibernate_method_task_html");
		ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithType(task_html_elm);
		ProgrammingTaskUtil.onSubmitResultVariableType(task_html_elm);
		
		if (task_html_elm.find(".opts .options_type").val() == "array") {
			task_html_elm.find(".opts .options_code").remove();
		}
		else {
			task_html_elm.find(".opts .options").remove();
		}
		
		var service_method_type = task_html_elm.find(".service_method select.service_method_type").val();
		var service_method = service_method_type == "string" ? task_html_elm.find(".service_method select.service_method_string").val() : task_html_elm.find(".service_method input.service_method_code").val();
		task_html_elm.find(".service_method input.service_method").val(service_method);
		
		var service_method_args = task_html_elm.children(".service_method_args");
		
		var sma = service_method_args.children(".sma_query_type");
		if (sma.children("select.service_method_arg_type").val() == "string") {
			sma.children("input").remove();
		}
		else {
			sma.children("select.sma_query_type_string").remove();
		}
		
		var sma = service_method_args.children(".sma_data");
		if (sma.children("select").val() == "array") {
			sma.children("input").remove();
		}
		else {
			sma.children(".array_items").remove();
		}
		
		var sma = service_method_args.children(".sma_parent_ids");
		if (sma.children("select").val() == "array") {
			sma.children("input").remove();
		}
		else {
			sma.children(".array_items").remove();
		}
		
		var sma = service_method_args.children(".sma_sql");
		var sql = "";
		if (sma.children("select").val() == "string") {
			var editor = sma.data("editor");
			sql = editor ? editor.getValue() : sma.children("textarea.sql_editor").val();
		}
		else {
			sql = sma.children("input").val();
		}
		sma.children("textarea.task_property_field").val( sql ? sql : "" );
		
		var sma = service_method_args.children(".sma_options");
		if (sma.children("select").val() == "array") {
			sma.children("input").remove();
			
			var items = sma.children(".array_items").find(".task_property_field");
			for (var i = 0; i < items.length; i++) {
				var item = $(items[i]);
				var name = item.attr("name");
				item.attr("name", "sma_" + name);
			}
		}
		else {
			sma.children(".array_items").remove();
		}
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var label = CallHibernateMethodTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
			
			if (task_property_values["broker_method_obj_type"] != "exists_hbn_var") {
				var default_method_obj_str = BrokerOptionsUtilObj.getDefaultBroker(CallHibernateMethodTaskPropertyObj.brokers_options);
				if (!task_property_values["method_obj"] && default_method_obj_str) {
					task_property_values["method_obj"] = default_method_obj_str;
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
		
			var label = CallHibernateMethodTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
		
			onEditLabel(task_id);
		
			if (task_property_values["broker_method_obj_type"] != "exists_hbn_var") {
				var default_method_obj_str = BrokerOptionsUtilObj.getDefaultBroker(CallHibernateMethodTaskPropertyObj.brokers_options);
				if (!task_property_values["method_obj"] && default_method_obj_str) {
					myWFObj.getTaskFlowChart().TaskFlow.tasks_properties[task_id]["method_obj"] = default_method_obj_str;
				}
			}
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 100);
	},
	
	getDefaultExitLabel : function(task_property_values) {
		if ((task_property_values["broker_method_obj_type"] == "exists_hbn_var" || (task_property_values["module_id"] && task_property_values["service_id"])) && task_property_values["service_method"]) {
			var method_obj = (task_property_values["method_obj"].trim().substr(0, 1) != "$" ? "$" : "") + task_property_values["method_obj"];
			var service_method = ProgrammingTaskUtil.getValueString(task_property_values["service_method"], task_property_values["service_method_type"]);
			service_method = task_property_values["service_method_type"] == "string" ? service_method.replace(/'/g, "") : service_method;
			
			var args = this.available_methods_args[service_method];
			args = args ? args : [];
			
			var service_method_args = "";
			for (var idx in args) {
				var an = "sma_" + args[idx];
				var ant = "sma_" + args[idx] + "_type";
				var arg = task_property_values[ant] == "array" ? ArrayTaskUtilObj.arrayToString(task_property_values[an]) : ProgrammingTaskUtil.getValueString(task_property_values[an], task_property_values[ant]);
				arg = !arg && arg !== 0 ? "null" : arg;
			
				service_method_args += (service_method_args ? ", " : "") + arg;
			}
			
			var label = ProgrammingTaskUtil.getResultVariableString(task_property_values) + method_obj + '->';
			if (task_property_values["broker_method_obj_type"] != "exists_hbn_var") {
				var module = ProgrammingTaskUtil.getValueString(task_property_values["module_id"], task_property_values["module_id_type"]);
				var service = ProgrammingTaskUtil.getValueString(task_property_values["service_id"], task_property_values["service_id_type"]);
			
				var options = task_property_values["options_type"] == "array" ? ArrayTaskUtilObj.arrayToString(task_property_values["options"]) : ProgrammingTaskUtil.getValueString(task_property_values["options"], task_property_values["options_type"]);
				options = options ? options : "null";
			
				label += 'callObject(' + module + ", " + service + ", " + options + ")->";
			}
			label += service_method + "(" + service_method_args + ")";
			
			return label;
		}
		return "";
	},
	
	onChooseHibernateObjectMethod : function(elm) {
		if (typeof this.on_choose_hibernate_object_method_callback == "function") {
			this.on_choose_hibernate_object_method_callback(elm);
		}
	},
	
	onEditFile : function(elm) {
		ProgrammingTaskUtil.onEditSource(elm, $(elm).closest(".call_hibernate_method_task_html"), "file");
	},
	
	onEditObject : function(elm) {
		ProgrammingTaskUtil.onEditSource(elm, $(elm).closest(".call_hibernate_method_task_html"), "object");
	},
	
	onEditQuery : function(elm) {
		var task_html_elm = $(elm).closest(".call_hibernate_method_task_html");
		
		var service_method_type = task_html_elm.find(".service_method select.service_method_type").val();
		var service_method = service_method_type == "string" ? task_html_elm.find(".service_method select.service_method_string").val() : task_html_elm.find(".service_method input.service_method_code").val();
		task_html_elm.find(".service_method input.service_method").val(service_method);
		
		ProgrammingTaskUtil.onEditSource(elm, task_html_elm, "query");
	},
};
