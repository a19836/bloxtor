/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

var GetUrlContentsTaskPropertyObj = {
	
	dependent_file_path_to_include : "LIB_PATH . 'org/phpframework/util/web/MyCurl.php'",
	//brokers_options : null,
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".get_url_contents_task_html");
		
		ProgrammingTaskUtil.setResultVariableType(task_property_values, task_html_elm);
		
		var data = task_property_values["data"];
		
		//sets default data with url
		if (!task_property_values.hasOwnProperty("data")) {
			task_property_values["data"] = data = {0: {key: "url", key_type: "string", value_type: "string"}}; //set url as default
			task_property_values["data_type"] = "array";
			task_html_elm.find(".dts > .data_type").val("array");
		}
		
		if (task_property_values["data_type"] == "array") {
			GetUrlContentsTaskPropertyObj.onLoadData( task_html_elm.find(".dts > .data").first(), data, "");
			task_html_elm.find(".dts > .data_code").val("").removeClass("task_property_field"); //removeClass("task_property_field") is very important here otherwise the taskFlowChartObj will give an error when trying to saveTaskPropertiesFromHtmlElm. Error is in the parse_str.
		}
		else {
			data = data ? "" + data + "" : "";
			data = task_property_values["data_type"] == "variable" && data.trim().substr(0, 1) == '$' ? data.trim().substr(1) : data;
			task_html_elm.find(".dts > .data_code").val(data);
		}
		GetUrlContentsTaskPropertyObj.onChangeDataType(task_html_elm.find(".dts > .data_type")[0], "data");
		
		//set result type
		var select = task_html_elm.find(".result_type select[name=result_type_type]");
		
		if (!task_property_values["result_type"]) {
			task_html_elm.find(".result_type select[name=result_type]").val("");
			select.val("options");
		}
		else if (task_property_values["result_type_type"] == "string" && (task_property_values["result_type"] == "header" || task_property_values["result_type"] == "content" || task_property_values["result_type"] == "content_json" || task_property_values["result_type"] == "content_xml" || task_property_values["result_type"] == "content_xml_simple" || task_property_values["result_type"] == "content_serialized" || task_property_values["result_type"] == "settings")) {
			task_html_elm.find(".result_type select[name=result_type]").val(task_property_values["result_type"]);
			select.val("options");
		}
		
		GetUrlContentsTaskPropertyObj.onChangeResultType(select[0]);
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".get_url_contents_task_html");
		ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithType(task_html_elm);
		ProgrammingTaskUtil.onSubmitResultVariableType(task_html_elm);
		
		//prepare data
		if (task_html_elm.find(".dts > .data_type").val() == "array") {
			task_html_elm.find(".dts > .data_code").removeClass("task_property_field");
			
			var key_types = task_html_elm.find(".dts > .data .key_type");
			GetUrlContentsTaskPropertyObj.changed_key_types = [];
			
			$.each(key_types, function(idx, key_type) {
				key_type = $(key_type);
				
				if (key_type.val() == "options") {
					key_type.val("string");
					GetUrlContentsTaskPropertyObj.changed_key_types.push(key_type[0]);
				}
			});
		}
		else {
			task_html_elm.find(".dts > .data_code").addClass("task_property_field");
			task_html_elm.find(".dts > .data").html("");
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
			var label = GetUrlContentsTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
		}
		else if (GetUrlContentsTaskPropertyObj.changed_key_types)
			$.each(GetUrlContentsTaskPropertyObj.changed_key_types, function(idx, key_type) {
				$(key_type).val("options");
			});
	},
	
	onCancelTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		return true;	
	},
	
	onCompleteLabel : function(task_id) {
		return ProgrammingTaskUtil.onEditLabel(task_id);
	},
	
	onTaskCloning : function(task_id) {
		ProgrammingTaskUtil.onTaskCloning(task_id);
		
		ProgrammingTaskUtil.addIncludeFileTaskBeforeTaskIfNotExistsYet(task_id, GetUrlContentsTaskPropertyObj.dependent_file_path_to_include, '', 1);
	},
	
	onTaskCreation : function(task_id) {
		setTimeout(function() {
			var task_property_values = myWFObj.getTaskFlowChart().TaskFlow.tasks_properties[task_id];
			ProgrammingTaskUtil.saveNewVariableInWorkflowAccordingWithTaskPropertiesValues(task_property_values);
			
			var label = GetUrlContentsTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
		
			onEditLabel(task_id);
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 80);
	},
	
	getDefaultExitLabel : function(task_property_values) {
		if (task_property_values["data"]) {
			var data = task_property_values["data_type"] == "array" ? ArrayTaskUtilObj.arrayToString(task_property_values["data"]) : ProgrammingTaskUtil.getValueString(task_property_values["data"], task_property_values["data_type"]);
			data = data ? data : "null";
			
			var result_type = ProgrammingTaskUtil.getValueString(task_property_values["result_type"], task_property_values["result_type_type"]);
			
			return ProgrammingTaskUtil.getResultVariableString(task_property_values) + 'MyCurl::getUrlContents(' + data + (result_type ? ", " + result_type : "") + ')';
		}
		
		return "";
	},
	
	/* UTILS */
	
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
		
		ProgrammingTaskUtil.onChangeTaskFieldType(elm);
	},
	
	onChangeDataType : function(elm, class_name) {
		var parameters_type = $(elm).val();
		
		var parent = $(elm).parent();
		var parameters_elm = parent.children("." + class_name);
		
		if (parameters_type == "array") {
			parent.children("." + class_name + "_code").hide();
			parameters_elm.show();
			
			if (!parameters_elm.find(".items")[0]) {
				var items = {0: {key: "url", key_type: "string", value_type: "string"}}; //set url as default
				this.onLoadData(parameters_elm, items, "");
			}
		}
		else {
			parent.children("." + class_name + "_code").show();
			parameters_elm.hide();
		}
		
		ProgrammingTaskUtil.onChangeTaskFieldType(elm);
	},
	
	onLoadData : function(array_items_html_elm, items, root_label) {
		ArrayTaskUtilObj.onLoadArrayItems(array_items_html_elm, items, root_label);
		
		//prepare icons
		array_items_html_elm.find(" > .items .group_add").first().attr("onclick", "GetUrlContentsTaskPropertyObj.addDataGroup(this)");
		//array_items_html_elm.find(" > .items .item_add").first().attr("onclick", "GetUrlContentsTaskPropertyObj.addDataItem(this)");
		
		//prepare existent items
		var lis = array_items_html_elm.find(" > ul > li");
		
		$.each(lis, function(idx, li) {
			li = $(li);
			
			if (li.hasClass("item")) {
				//GetUrlContentsTaskPropertyObj.prepareDataItem(li);
				
				//prepare url field
				var is_url = (li.children(".key_type").val() == "options" && li.children("select.key").val() == "url") || (li.children(".key_type").val() == "string" && li.children("input.key").val() == "url");
				
				if (is_url) {
					li.addClass("url");
					li.children(".key").attr("readonly", "readonly");
					li.children(".key_type").hide();
					li.children(".remove").hide();
					li.children(".value").attr("placeHolder", "Write here your url");
					
					//add search icon
					if (typeof ProgrammingTaskUtil.on_programming_task_choose_page_url_callback == "function")
						li.children(".value_type").before('<span class="icon search" onClick="ProgrammingTaskUtil.onProgrammingTaskChoosePageUrl(this)">Search</span>');
					
					//move it to the beginnning
					li.parent().prepend(li); 
				}
			}
			else
				GetUrlContentsTaskPropertyObj.prepareDataGroup(li.children(".items"));
		});
	},
	
	/* ARRAY GROUP UTILS */
	
	addDataGroup : function(a) {
		ArrayTaskUtilObj.addGroup(a);
		
		var main_ul = $(a).parent().parent().children("ul").first();

		if (main_ul[0]) {
			var last_li = main_ul.children("li").last().children(".items");
			var sub_lis = last_li.parent().children("ul").children("li.item");
			
			$.each(sub_lis, function(idx, sub_li) {
				$(sub_li).children(".key_type").val("string");
			});
			
			this.prepareDataGroup(last_li);
		}
	},
	
	prepareDataGroup : function(li) {
		//prepare item_add and group_add icons
		li.children(".group_add").attr("onClick", "GetUrlContentsTaskPropertyObj.addDataGroupGroup(this)");
		li.children(".item_add").attr("onClick", "GetUrlContentsTaskPropertyObj.addDataGroupItem(this)");
		
		//prepare .key input
		var key = li.children(".key");
		key.attr("onKeyUp", "GetUrlContentsTaskPropertyObj.onChangeGroupKey(this)");
		
		//prepare .key_type select
		var key_type = li.children(".key_type");
		key_type.append('<option>options</option>');
		key_type.attr("onClick", "GetUrlContentsTaskPropertyObj.onChangeGroupKeyType(this)");
		
		//add .key select
		var opts = {"get": "Get Vars", "post": "Post Vars", "cookie": "Cookies", "files": "Files", "settings": "Other Settings"};
		
		var html = '<select class="key task_property_field" title="Item key" onChange="GetUrlContentsTaskPropertyObj.onChangeGroupKeyOptions(this)"><option></option>';
		for (var k in opts)
			html += '<option value="' + k + '">' + opts[k] + '</option>';
		html += '</select>';
		
		var key_options = $(html);
		key_options.attr("name", key.attr("name"));
		key.after(key_options);
		
		ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml(key_options);
		
		//show .key select
		key_type.val("options");
		this.onChangeGroupKeyType(key_type[0]);
	},
	
	onChangeGroupKeyType : function(elm) {
		elm = $(elm);
		var p = elm.parent();
		var key_type = elm.val();
		var key_input = p.children("input.key");
		var key_select = p.children("select.key");
		
		if (key_type == "options") {
			key_input.hide().removeClass("task_property_field");
			key_select.show().addClass("task_property_field");
			
			if (elm.attr("previous_key_type") != key_type)
				key_select.val( key_input.val() );
			
			this.onChangeGroupKeyOptions(key_select[0]); //show or hide search icon
		}
		else {
			key_input.show().addClass("task_property_field");
			key_select.hide().removeClass("task_property_field");
			
			if (elm.attr("previous_key_type") != key_type && elm.attr("previous_key_type") == "options")
				key_input.val( key_select.val() );
			
			this.onChangeGroupKey(key_input[0]); //show or hide search icon
		}

		elm.attr("previous_key_type", key_type);
	},
	
	onChangeGroupKey : function(elm) {
		this.onChangeGroupKeyOptions(elm);
	},
	
	onChangeGroupKeyOptions : function(elm) {
		elm = $(elm);
		var lis = elm.parent().parent().children("ul").first().children("li.item");
		var key_option = elm.val();
		
		if (key_option == "settings") {
			//prepare item_add icon
			elm.parent().children(".item_add").attr("onClick", "GetUrlContentsTaskPropertyObj.addGroupSettingsItem(this)");
			
			//prepare current lis children
			$.each(lis, function(idx, li) {
				//prepare li key_options and key_type
				GetUrlContentsTaskPropertyObj.prepareGroupSettingsItemLi( $(li) );
			});
		}
		else {
			//prepare .group_add and item_add btns
			elm.parent().children(".item_add").attr("onClick", "GetUrlContentsTaskPropertyObj.addDataGroupItem(this)");
			
			//prepare current lis children
			$.each(lis, function(idx, li) {
				li = $(li);
				var key_type = li.children(".key_type");
				
				//prepare .key input
				if (key_type.val() == "options") {
					key_type.val("string");
					
					GetUrlContentsTaskPropertyObj.onChangeGroupSettingsKeyType(key_type[0]);
				}
				
				//prepare .key_type select
				var options = key_type.children("option");
				
				for (var i = 0; i < options.length; i++)
					if ($(options[i]).text() == "options") {
						$(options[i]).hide();
						break;
					}
			});
		}
	},
	
	onChangeGroupSettingsKeyType : function(elm) {
		elm = $(elm);
		var p = elm.parent();
		var key_type = elm.val();
		var key_input = p.children("input.key");
		var key_select = p.children("select.key");
		
		if (key_type == "options") {
			key_input.hide().removeClass("task_property_field");
			key_select.show().addClass("task_property_field");
			
			if (elm.attr("previous_key_type") != key_type)
				key_select.val( key_input.val() );
		}
		else {
			key_input.show().addClass("task_property_field");
			key_select.hide().removeClass("task_property_field");
			
			if (elm.attr("previous_key_type") != key_type && elm.attr("previous_key_type") == "options")
				key_input.val( key_select.val() );
		}
		
		elm.attr("previous_key_type", key_type);
	},
	
	addDataGroupGroup : function(a) {
		ArrayTaskUtilObj.addGroup(a);
		
		var main_ul = $(a).parent().parent().children("ul").first();

		if (main_ul[0]) {
			var last_li = main_ul.children("li").last().children(".items");
			
			//prepare item_add and group_add icons
			last_li.children(".group_add").attr("onClick", "GetUrlContentsTaskPropertyObj.addDataGroupGroup(this)");
			last_li.children(".item_add").attr("onClick", "GetUrlContentsTaskPropertyObj.addDataGroupItem(this)");
			
			//prepare key_type
			last_li.children(".key_type").val("string");
			
			//prepare sub_lis
			var sub_lis = last_li.parent().children("ul").children("li.item");
			
			$.each(sub_lis, function(idx, sub_li) {
				$(sub_li).children(".key_type").val("string");
			});
		}
	},
	
	addDataGroupItem : function(a) {
		ArrayTaskUtilObj.addItem(a);
		
		var main_ul = $(a).parent().parent().children("ul").first();

		if (main_ul[0]) {
			var last_li = main_ul.children("li").last();
			
			//prepare key_type
			last_li.children(".key_type").val("string");
		}
	},
	
	addGroupSettingsItem : function(a) {
		ArrayTaskUtilObj.addItem(a);
		
		var main_ul = $(a).parent().parent().children("ul").first();

		if (main_ul[0]) {
			var last_li = main_ul.children("li").last();
			this.prepareGroupSettingsItemLi(last_li);
		}
	},
	
	prepareGroupSettingsItemLi : function(li) {
		var key_type = li.children(".key_type");
		var options = key_type.children("option");
		var opts = {
			"header" : "Header (Boolean)", 
			"connection_timeout" : "Connection Timeout (Numeric)",
			"no_body" : "No Body (Boolean)",
			"http_header" : "HTTP header (Array|String)",
			"referer" : "Referer (String)",
			"follow_location" : "Follow Location (Boolean)",
			"http_auth" : "HTTP Auth (String|Defined Var)",
			"user_pwd" : "User+Pwd (String)",
			"put" : "Put (Boolean)",
			"in_file" : "In File (String)",
			"in_file_size" : "In File Size (Numeric)",
			"read_cookies_from_file" : "Read Cookies from File (String)",
			"save_cookies_to_file" : "Save Cookies to File (String)",
		};
		var exists = false;
		
		for (var i = 0; i < options.length; i++)
			if ($(options[i]).text() == "options") {
				$(options[i]).show();
				exists = true;
				break;
			}
		
		if (!exists) {
			//prepare .key_type select
			key_type.append('<option>options</option>');
			key_type.attr("onClick", "GetUrlContentsTaskPropertyObj.onChangeGroupSettingsKeyType(this)");
			
			//add .key select
			var html = '<select class="key task_property_field" title="Item key">'
				+ '<option></option>';
			for (var k in opts)
				html += '<option value="' + k + '">' + opts[k] + '</option>';
			html += '</select>';
			
			var key = li.children(".key");
			var key_options = $(html);
			key_options.attr("name", key.attr("name"));
			key.after(key_options);
			
			ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml(key_options);
			
			key_options.hide(); //hide. if should be shown, it will be when called by the onChangeGroupSettingsKeyType
		}
		
		//set current key value
		var key_input_val = li.children("input.key").val();
		
		if (!key_input_val || opts.hasOwnProperty(key_input_val)) {
			var key_type = li.children(".key_type");
			key_type.val("options");
			
			GetUrlContentsTaskPropertyObj.onChangeGroupSettingsKeyType(key_type[0]);
		}
	},
	
	/* ARRAY ITEM UTILS */
	
	addDataItem : function(a) {
		ArrayTaskUtilObj.addItem(a);
		
		var main_ul = $(a).parent().parent().children("ul").first();

		if (main_ul[0]) {
			var last_li = main_ul.children("li").last();
			this.prepareDataItem(last_li);
		}
	},
	
	prepareDataItem : function(li) {
		//prepare .key input
		var key = li.children(".key");
		key.attr("onKeyUp", "GetUrlContentsTaskPropertyObj.onChangeItemKey(this)");
		
		//prepare .key_type select
		var key_type = li.children(".key_type");
		key_type.append('<option>options</option>');
		key_type.attr("onClick", "GetUrlContentsTaskPropertyObj.onChangeItemKeyType(this)");
		
		//add .key select
		var opts = ["url"];
		
		var html = '<select class="key task_property_field" title="Item key" onChange="GetUrlContentsTaskPropertyObj.onChangeItemKeyOptions(this)"><option></option>';
		for (var i = 0; i < opts.length; i++)
			html += '<option>' + opts[i] + '</option>';
		html += '</select>';
		
		var key_options = $(html);
		key_options.attr("name", key.attr("name"));
		key.after(key_options);
		
		ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml(key_options);
		
		//show .key select
		key_type.val("options");
		this.onChangeItemKeyType(key_type[0]);
	},
	
	onChangeItemKeyType : function(elm) {
		elm = $(elm);
		var p = elm.parent();
		var key_type = elm.val();
		var key_input = p.children("input.key");
		var key_select = p.children("select.key");
		
		if (key_type == "options") {
			key_input.hide().removeClass("task_property_field");
			key_select.show().addClass("task_property_field");
			
			if (elm.attr("previous_key_type") != key_type)
				key_select.val( key_input.val() );
			
			this.onChangeItemKeyOptions(key_select[0]); //show or hide search icon
		}
		else {
			key_input.show().addClass("task_property_field");
			key_select.hide().removeClass("task_property_field");
			
			if (elm.attr("previous_key_type") != key_type && elm.attr("previous_key_type") == "options")
				key_input.val( key_select.val() );
			
			this.onChangeItemKey(key_input[0]); //show or hide search icon
		}
		
		elm.attr("previous_key_type", key_type);
	},
	
	onChangeItemKey : function(elm) {
		ArrayTaskUtilObj.onFieldValueBlur(elm);
		this.onChangeItemKeyOptions(elm); //show or hide search icon
	},
	
	onChangeItemKeyOptions : function(elm) {
		elm = $(elm);
		var p = elm.parent();
		var key_option = elm.val();
		var icon = p.children(".icon.search");
		
		if (key_option == "url") {
			if (!icon[0] && typeof ProgrammingTaskUtil.on_programming_task_choose_page_url_callback == "function")
				p.children(".value").after('<span class="icon search" onClick="ProgrammingTaskUtil.onProgrammingTaskChoosePageUrl(this)">Search</span>');
			
			icon.show();
		}
		else if (icon[0])
			icon.hide();
	},
};
