/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

var SoapConnectorTaskPropertyObj = {
	
	dependent_file_path_to_include : "LIB_PATH . 'org/phpframework/connector/SoapConnector.php'",
	//brokers_options : null,
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		//console.log(task_property_values);
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".soap_connector_task_html");
		
		if (typeof ProgrammingTaskUtil.on_programming_task_choose_page_url_callback == "function")
			task_html_elm.addClass("with_search");
		
		ProgrammingTaskUtil.setResultVariableType(task_property_values, task_html_elm);
		
		//load data
		var data = task_property_values.hasOwnProperty("data") ? task_property_values["data"] : null;
		
		if ($.isPlainObject(data) || !data) {
			if (!data)
				data = {type_type: "options", wsdl_url_type: "string", options_type: "options", headers_type: "options", remote_function_name_type: "string", remote_function_args_type: "array"};
			
			task_html_elm.find(" > .data input").val("");
			task_html_elm.find(" > .data select").val("options");
			
			//prepare type
			var type_elm = task_html_elm.children(".type");
			
			if (!data["type"] || data["type_type"] == "options" || (data["type_type"] == "string" && (data["type"] == "callSoapFunction" || data["type"] == "callSoapClient"))) {
				type_elm.children(".type_options").val(data["type"]);
				data["type_type"] = "options";
			}
			else
				type_elm.children(".type_code").val(data["type"]);
			
			type_elm.children(".type_type").val(data["type_type"]);
			SoapConnectorTaskPropertyObj.onChangeType( type_elm.children(".type_type")[0] );
			
			//load wsdl url
			task_html_elm.find(" > .wsdl_url input").val(data["wsdl_url"]);
			task_html_elm.find(" > .wsdl_url select").val(data["wsdl_url_type"]);
			
			//load client options
			if (data["options"]) {
				if (data["options_type"] == "array" || data["options_type"] == "options") {
					data["options_type"] = "options";
					
					var add_icon = task_html_elm.find(" > .client_options table thead th .icon.add");
					
					if ($.isPlainObject(data["options"]) && (data["options"].hasOwnProperty("name") || data["options"].hasOwnProperty("value")))
						data["options"] = [data["options"]];
					
					$.each(data["options"], function(idx, option) {
						var new_item = SoapConnectorTaskPropertyObj.addNewOption(add_icon[0]);
						
						new_item.find(".name input").val(option["name"]);
						new_item.find(".value input").val(option["value"]);
						new_item.find(".var_type select").val(option["var_type"] == "string" ? "string" : "");
					});
				}
				else
					task_html_elm.find(" > .client_options > input").val(data["options"]);
			}
			
			var select = task_html_elm.find(" > .client_options > select");
			select.val(data["options_type"]);
			SoapConnectorTaskPropertyObj.onChangeClientOptions(select);
			
			//load client headers
			if (data["headers"]) {
				if (data["headers_type"] == "array" || data["headers_type"] == "options") {
					data["headers_type"] = "options";
					
					var add_icon = task_html_elm.find(" > .client_headers > .icon.add");
					
					if ($.isPlainObject(data["headers"]) && (data["headers"].hasOwnProperty("namespace") || data["headers"].hasOwnProperty("name")))
						data["headers"] = [ data["headers"] ];
					
					$.each(data["headers"], function(idx, header) {
						var new_item = SoapConnectorTaskPropertyObj.addNewHeader(add_icon[0]);
						
						new_item.find(".client_header_namespace input").val(header["namespace"]);
						new_item.find(".client_header_namespace select").val(header["namespace_type"]);
						new_item.find(".client_header_name input").val(header["name"]);
						new_item.find(".client_header_name select").val(header["name_type"]);
						new_item.find(".client_header_actor input").val(header["actor"]);
						new_item.find(".client_header_actor select").val(header["actor_type"]);
						
						//load must_understand
						var client_header_must_understand = new_item.children(".client_header_must_understand");
						var select = client_header_must_understand.children("select");
						
						if (!header["must_understand"] || header["must_understand"] == "1" || header["must_understand"] == "0") {
							if (header["must_understand"] == "1")
								client_header_must_understand.children("input[type=checkbox]").attr("checked", "checked").prop("checked", true);
							
							select.val("options");
						}
						else {
							client_header_must_understand.children("input").val(header["must_understand"]);
							select.val(header["must_understand_type"]);
						}
						
						SoapConnectorTaskPropertyObj.onChangeClientHeaderMustUnderstandType(select[0]);
						
						//load parameters
						var client_header_parameters = new_item.children(".client_header_parameters");
						client_header_parameters.children(".parameters_type").val(header["parameters_type"]);
						
						if (header["parameters_type"] == "array") {
							if ($.isPlainObject(header["parameters"]) && (header["parameters"].hasOwnProperty("key") || header["parameters"].hasOwnProperty("key_type") || header["parameters"].hasOwnProperty("value") || header["parameters"].hasOwnProperty("value_type")))
								header["parameters"] = [ header["parameters"] ];
							
							ArrayTaskUtilObj.onLoadArrayItems( client_header_parameters.children(".parameters").first(), header["parameters"], "", client_header_parameters.children(".parameters_code").attr("name"));
							client_header_parameters.children(".parameters_code").val("").removeClass("task_property_field"); //removeClass("task_property_field") is very important here otherwise the taskFlowChartObj will give an error when trying to saveTaskPropertiesFromHtmlElm. Error is in the parse_str.
						}
						else {
							var parameters = header["parameters"] ? "" + header["parameters"] : "";
							parameters = header["parameters_type"] == "variable" && parameters.trim().substr(0, 1) == '$' ? parameters.trim().substr(1) : parameters;
							client_header_parameters.children(".parameters_code").val(parameters);
						}
						
						SoapConnectorTaskPropertyObj.onChangeClientHeaderParametersType( client_header_parameters.children(".parameters_type")[0] );
					});
				}
				else
					task_html_elm.find(" > .client_headers > input").val(data["headers"]);
			}
			
			var select = task_html_elm.find(" > .client_headers > select");
			select.val(data["headers_type"]);
			SoapConnectorTaskPropertyObj.onChangeClientHeaders(select);
			
			//load remote function name
			task_html_elm.find(" > .remote_function_name input").val(data["remote_function_name"]);
			task_html_elm.find(" > .remote_function_name select").val(data["remote_function_name_type"]);
			
			//load remote function args
			var remote_function_arguments = task_html_elm.children(".remote_function_arguments");
			remote_function_arguments.children(".remote_function_args_type").val(data["remote_function_args_type"]);
			
			if (data["remote_function_args_type"] == "array") {
				ArrayTaskUtilObj.onLoadArrayItems( remote_function_arguments.children(".remote_function_args").first(), data["remote_function_args"], "", remote_function_arguments.children(".remote_function_args_code").attr("name"));
				remote_function_arguments.children(".remote_function_args_code").val("").removeClass("task_property_field"); //removeClass("task_property_field") is very important here otherwise the taskFlowChartObj will give an error when trying to saveTaskPropertiesFromHtmlElm. Error is in the parse_str.
			}
			else {
				var args = data["remote_function_args"] ? "" + data["remote_function_args"] : "";
				args = data["remote_function_args_type"] == "variable" && args.trim().substr(0, 1) == '$' ? args.trim().substr(1) : args;
				remote_function_arguments.children(".remote_function_args_code").val(args);
			}
			SoapConnectorTaskPropertyObj.onChangeRemoteFunctionArgsType( remote_function_arguments.children(".remote_function_args_type")[0] );
		}
		
		SoapConnectorTaskPropertyObj.onChangeDataType( task_html_elm.find(" > .data select")[0] );
		
		//load result type
		var select = task_html_elm.find(".result_type select[name=result_type_type]");
		
		if (!task_property_values["result_type"]) {
			task_html_elm.find(".result_type select[name=result_type]").val("content");
			select.val("options");
		}
		else if (task_property_values["result_type_type"] == "string" && (task_property_values["result_type"] == "header" || task_property_values["result_type"] == "content" || task_property_values["result_type"] == "content_json" || task_property_values["result_type"] == "content_xml" || task_property_values["result_type"] == "content_xml_simple" || task_property_values["result_type"] == "content_serialized" || task_property_values["result_type"] == "settings")) {
			task_html_elm.find(".result_type select[name=result_type]").val(task_property_values["result_type"]);
			select.val("options");
		}
		
		SoapConnectorTaskPropertyObj.onChangeResultType(select[0]);
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".soap_connector_task_html");
		ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithType(task_html_elm);
		ProgrammingTaskUtil.onSubmitResultVariableType(task_html_elm);
		
		//prepare data
		if (task_html_elm.find(" > .data select").val() == "options") {
			task_html_elm.find(" > .data input").removeClass("task_property_field");
			
			//prepare type
			var type_elm = task_html_elm.children(".type");
			var select = type_elm.children(".type_type");
			
			if (select.val() == "options") {
				type_elm.children(".type_code").val( type_elm.children(".type_options").val() );
				select.val("string");
			}
			
			//prepare client options
			var client_options = task_html_elm.children(".client_options");
			
			if (client_options.children("select").val() == "options") {
				client_options.children("input").removeClass("task_property_field");
			}
			else {
				client_options.children("input").addClass("task_property_field");
				client_options.children("table").find(".task_property_field").removeClass("task_property_field").addClass("task_property_field_removed");
			}
			
			//prepare client headers
			var client_headers = task_html_elm.children(".client_headers");
			
			if (client_headers.children("select").val() == "options") {
				client_headers.children("input").removeClass("task_property_field");
				
				$.each(task_html_elm.find(" > .client_headers > ul > li"), function(idx, client_header) {
					client_header = $(client_header);
					
					//prepare must_understand
					var client_header_must_understand = client_header.children(".client_header_must_understand");
					var must_understand_type = client_header_must_understand.children("select").val();
					client_header_must_understand.children("input").addClass("task_property_field");
					
					if (must_understand_type == "options") 
						client_header_must_understand.children("input[type=text]").removeClass("task_property_field");
					else
						client_header_must_understand.children("input[type=checkbox]").removeClass("task_property_field");
					
					//prepare parameters
					var client_header_parameters = client_header.children(".client_header_parameters");
					
					if (client_header_parameters.children(".parameters_type").val() == "array") 
						client_header_parameters.children(".parameters_code").removeClass("task_property_field");
					else {
						client_header_parameters.children(".parameters_code").addClass("task_property_field");
						client_header_parameters.children(".parameters").html("");
					}
				});
			}
			else {
				client_headers.children("input").addClass("task_property_field");
				client_headers.children("ul").find(".task_property_field").removeClass("task_property_field").addClass("task_property_field_removed");
			}
			
			//prepare remote function args
			var remote_function_arguments = task_html_elm.children(".remote_function_arguments");
			
			if (remote_function_arguments.children(".remote_function_args_type").val() == "array") 
				remote_function_arguments.children(".remote_function_args_code").removeClass("task_property_field");
			else {
				remote_function_arguments.children(".remote_function_args_code").addClass("task_property_field");
				remote_function_arguments.children(".remote_function_args").html("");
			}
		}
		else {
			task_html_elm.find(" > .data input").addClass("task_property_field");
			 task_html_elm.children(".type, .wsdl_url, .client_options, .client_headers, .remote_function_name, .remote_function_arguments").find(".task_property_field").removeClass("task_property_field").addClass("task_property_field_removed");
		}
		
		//prepare result type
		var select = task_html_elm.find(".result_type select[name=result_type_type]");
		var result_type_type = select.val();
		
		if (result_type_type == "options") {
			task_html_elm.find(".result_type input[name=result_type]").val( task_html_elm.find(".result_type select[name=result_type]").val() );
			select.val("string");
		}
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var label = SoapConnectorTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
		}
		else		task_html_elm.children(".type, .wsdl_url, .client_options, .client_headers, .remote_function_name, .remote_function_arguments").find(".task_property_field_removed").removeClass("task_property_field_removed").addClass("task_property_field");
	},
	
	onCancelTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		return true;	
	},
	
	onCompleteLabel : function(task_id) {
		return ProgrammingTaskUtil.onEditLabel(task_id);
	},
	
	onTaskCloning : function(task_id) {
		ProgrammingTaskUtil.onTaskCloning(task_id);
		
		ProgrammingTaskUtil.addIncludeFileTaskBeforeTaskIfNotExistsYet(task_id, SoapConnectorTaskPropertyObj.dependent_file_path_to_include, '', 1);
	},
	
	onTaskCreation : function(task_id) {
		setTimeout(function() {
			var task_property_values = myWFObj.getTaskFlowChart().TaskFlow.tasks_properties[task_id];
			ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithTaskPropertiesValues(task_property_values);
			
			var label = SoapConnectorTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
		
			onEditLabel(task_id);
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 110);
	},
	
	getDefaultExitLabel : function(task_property_values) {
		if (task_property_values["data"]) {
			var data = $.isPlainObject(task_property_values["data"]) ? "[" + ProgrammingTaskUtil.getValueString(task_property_values["data"]["wsdl_url"], task_property_values["data"]["wsdl_url_type"]) + ", ...]" : ProgrammingTaskUtil.getValueString(task_property_values["data"], task_property_values["data_type"]);
			data = data ? data : "null";
			
			var result_type = ProgrammingTaskUtil.getValueString(task_property_values["result_type"], task_property_values["result_type_type"]);
			
			return ProgrammingTaskUtil.getResultVariableString(task_property_values) + 'SoapConnector::connector(' + data + (result_type ? ", " + result_type : "") + ')';
		}
		
		return "";
	},
	
	/* UTILS */
	
	addNewOption : function(elm) {
		var var_types = {"string": "string", "": "default"};
		var tbody = $(elm).parent().closest("table").children("tbody");
		var idx = getListNewIndex(tbody);
		var index_prefix = tbody.attr("index_prefix");
		
		var html = '<tr class="client_option">'
				+ '	<td class="name">'
				+ '		<input class="task_property_field" name="' + index_prefix + '[' + idx + '][name]" type="text" value="" />'
				+ '		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>'
				+ '	</td>'
				+ '	<td class="value">'
				+ '		<input class="task_property_field" name="' + index_prefix + '[' + idx + '][value]" type="text" value="" />'
				+ '		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>'
				+ '	</td>'
				+ '	<td class="var_type">'
				+ '		<select class="task_property_field" name="' + index_prefix + '[' + idx + '][var_type]">';
		
		for (var k in var_types) 
			html += '<option value="' + k + '">' + var_types[k] + '</option>';
				
		html += '			</select>'
				+ '	</td>'
				+ '	<td class="icon_cell table_header"><span class="icon delete" onClick="$(this).parent().parent().remove();">Remove</span></td>'
				+ '</tr>';
		
		var new_item = $(html);
		
		tbody.append(new_item);
		
		ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml(new_item);
		
		return new_item;
	},
	
	addNewHeader : function(elm) {
		var ul = $(elm).parent().children("ul");
		var idx = getListNewIndex(ul);
		var index_prefix = ul.attr("index_prefix");
		
		var html = '<li>'
			+ '		<span class="icon delete" onclick="$(this).parent().remove()">Remove</span>'
			+ '		<div class="client_header_namespace">'
			+ '			<label>Namespace:</label>'
			+ '			<input type="text" class="task_property_field" name="' + index_prefix + '[' + idx + '][namespace]" />'
			+ '			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>'
			+ '			<select class="task_property_field" name="' + index_prefix + '[' + idx + '][namespace_type]">'
			+ '				<option>string</option>'
			+ '				<option>variable</option>'
			+ '				<option value="">code</option>'
			+ '			</select>'
			+ '		</div>'
			+ '		<div class="client_header_name">'
			+ '			<label>Name:</label>'
			+ '			<input type="text" class="task_property_field" name="' + index_prefix + '[' + idx + '][name]" />'
			+ '			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>'
			+ '			<select class="task_property_field" name="' + index_prefix + '[' + idx + '][name_type]">'
			+ '				<option>string</option>'
			+ '				<option>variable</option>'
			+ '				<option value="">code</option>'
			+ '			</select>'
			+ '		</div>'
			+ '		<div class="client_header_must_understand">'
			+ '			<label>Must Understand:</label>'
			+ '			<input type="text" class="task_property_field" name="' + index_prefix + '[' + idx + '][must_understand]" value="" />'
			+ '			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>'
			+ '			<input type="checkbox" class="task_property_field" name="' + index_prefix + '[' + idx + '][must_understand]" value="1" />'
			+ '			<select class="task_property_field" name="' + index_prefix + '[' + idx + '][must_understand_type]" onChange="SoapConnectorTaskPropertyObj.onChangeClientHeaderMustUnderstandType(this)">'
			+ '				<option>options</option>'
			+ '				<option>variable</option>'
			+ '			</select>'
			+ '		</div>'
			+ '		<div class="client_header_actor">'
			+ '			<label>Actor:</label>'
			+ '			<input type="text" class="task_property_field" name="' + index_prefix + '[' + idx + '][actor]" />'
			+ '			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>'
			+ '			<select class="task_property_field" name="' + index_prefix + '[' + idx + '][actor_type]">'
			+ '				<option>string</option>'
			+ '				<option>variable</option>'
			+ '				<option value="">code</option>'
			+ '			</select>'
			+ '		</div>'
			+ '		<div class="client_header_parameters">'
			+ '			<label>Parameters:</label>'
			+ '			<input type="text" class="task_property_field parameters_code" name="' + index_prefix + '[' + idx + '][parameters]" />'
			+ '			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>'
			+ '			<select class="task_property_field parameters_type" name="' + index_prefix + '[' + idx + '][parameters_type]" onChange="SoapConnectorTaskPropertyObj.onChangeClientHeaderParametersType(this)">'
			+ '				<option>string</option>'
			+ '				<option>variable</option>'
			+ '				<option value="">code</option>'
			+ '				<option>array</option>'
			+ '			</select>'
			+ '			<div class="parameters array_items"></div>'
			+ '		</div>'
			+ '	</li>';
		
		var new_item = $(html);
		
		ul.append(new_item);
		
		SoapConnectorTaskPropertyObj.onChangeClientHeaderMustUnderstandType( new_item.find(".client_header_must_understand select")[0] );
		
		ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml( ul.children("li").last() );
		
		return new_item;
	},
	
	onChangeType : function(elm) {
		elm = $(elm);
		var p = elm.parent();
		var type = elm.val();
		
		if (type == "options") {
			p.children(".type_code").hide();
			p.children(".type_options").show();
			
			this.onChangeTypeOptions( p.children(".type_options")[0] );
		}
		else {
			p.children(".type_code").show();
			p.children(".type_options").hide();
			
			p.parent().children(".remote_function_name, .remote_function_arguments, .result_type").show();
		}
		
		ProgrammingTaskUtil.onChangeTaskFieldType(elm[0]);
	},
	
	onChangeTypeOptions : function(elm) {
		elm = $(elm);
		var p = elm.parent().parent();
		var type = elm.val();
		
		if (type == "callSoapClient")
			p.children(".remote_function_name, .remote_function_arguments, .result_type").hide();
		else
			p.children(".remote_function_name, .remote_function_arguments, .result_type").show();
	},
	
	onChangeDataType : function(elm) {
		elm = $(elm);
		var p = elm.parent();
		var type = elm.val();
		
		if (type == "options") {
			p.children("input").hide();
			p.parent().children(".type, .wsdl_url, .client_options, .client_headers, .remote_function_name, .remote_function_arguments").show();
			
			this.onChangeType( p.parent().find(" > .type > .type_type")[0] );
		}
		else {
			p.children("input").show();
			p.parent().children(".type, .wsdl_url, .client_options, .client_headers, .remote_function_name, .remote_function_arguments").hide();
		}
		
		ProgrammingTaskUtil.onChangeTaskFieldType(elm[0]);
	},
	
	onChangeClientOptions : function(elm) {
		elm = $(elm);
		var p = elm.parent();
		var type = elm.val();
		
		if (type == "options") {
			p.children("input").hide();
			p.children("table").show();
		}
		else {
			p.children("input").show();
			p.children("table").hide();
		}
		
		ProgrammingTaskUtil.onChangeTaskFieldType(elm[0]);
	},
	
	onChangeClientHeaders : function(elm) {
		elm = $(elm);
		var p = elm.parent();
		var type = elm.val();
		
		if (type == "options") {
			p.children("input, ").hide();
			p.children("ul").show();
			p.children(".icon.add").show();
		}
		else {
			p.children("input").show();
			p.children("ul").hide();
			p.children(".icon.add").hide();
		}
		
		ProgrammingTaskUtil.onChangeTaskFieldType(elm[0]);
	},
	
	onChangeClientHeaderMustUnderstandType : function(elm) {
		elm = $(elm);
		var p = elm.parent();
		var type = elm.val();
		
		if (type == "options") {
			p.children("input[type=text]").hide();
			p.children("input[type=checkbox]").show();
		}
		else {
			p.children("input[type=text]").show();
			p.children("input[type=checkbox]").hide();
		}
		
		ProgrammingTaskUtil.onChangeTaskFieldType(elm[0]);
	},
	
	onChangeClientHeaderParametersType : function(elm) {
		this.onChangeArrayType(elm, 'parameters', $(elm).parent().children(".parameters_code").attr("name"));
	},
	
	onChangeRemoteFunctionArgsType : function(elm) {
		this.onChangeArrayType(elm, 'remote_function_args', $(elm).parent().children(".remote_function_args_code").attr("name"));
	},
	
	onChangeArrayType : function(elm, class_name, parent_name) {
		elm = $(elm);
		var type = elm.val();
		
		var parent = elm.parent();
		var array_main_elm = parent.children("." + class_name);
		
		if (type == "array") {
			parent.children("." + class_name + "_code").hide();
			array_main_elm.show();
			
			if (!array_main_elm.find(".items")[0]) {
				var items = {0: {key: "", key_type: "null", value_type: "string"}}; //set url as default
				ArrayTaskUtilObj.onLoadArrayItems(array_main_elm, items, "", parent_name);
			}
		}
		else {
			parent.children("." + class_name + "_code").show();
			array_main_elm.hide();
		}
		
		ProgrammingTaskUtil.onChangeTaskFieldType(elm[0]);
	},
	
	onChangeResultType : function(elm) {
		elm = $(elm);
		var p = elm.parent();
		var result_type_type = elm.val();
		
		if (result_type_type == "options") {
			p.children("input[name=result_type]").hide();
			p.children("select[name=result_type]").show();
		}
		else {
			p.children("input[name=result_type]").show();
			p.children("select[name=result_type]").hide();
		}
		
		ProgrammingTaskUtil.onChangeTaskFieldType(elm[0]);
	},
};
