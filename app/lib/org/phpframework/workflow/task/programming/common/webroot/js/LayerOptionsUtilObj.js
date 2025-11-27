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

var LayerOptionsUtilObj = {
	
	on_choose_db_driver_callback : null,
	options_true_false: ["no_cache", "no_annotations"],
	options_value: ["db_driver"],
	
	onLoadTaskProperties : function(task_html_elm, task_property_values) {
		//console.log(task_property_values);
		var options = task_property_values["options"];
		if (task_property_values["options_type"] == "array") {
			LayerOptionsUtilObj.initOptionsArray( task_html_elm.find(".opts .options").first(), options);
			task_html_elm.find(".opts .options_code").val("");
		}
		else {
			options = options ? "" + options + "" : "";
			options = task_property_values["options_type"] == "variable" && options.trim().substr(0, 1) == '$' ? options.trim().substr(1) : options;
			task_html_elm.find(".opts .options_code").val(options);
		}
		this.onChangeOptionsType(task_html_elm.find(".opts .options_type")[0]);
	},
	
	initOptionsArray : function(options_elm, items) {
		if (ArrayTaskUtilObj && ArrayTaskUtilObj.onLoadArrayItems) {
			ArrayTaskUtilObj.onLoadArrayItems(options_elm, items, "", "options");
			
			var html = '<a class="icon add add1" onClick="LayerOptionsUtilObj.addOption(this, 2)" title="Add new option 1">add option</a>' + 
					'<a class="icon add add2" onClick="LayerOptionsUtilObj.addOption(this)" title="Add new option 2">add option</a>';
			
			options_elm.children(".items").append(html);
			
			options_elm.children("ul").children(".item").each(function(idx, item) {
				item = $(item);
				
				var key = item.children(".key").val();
				var key_type = item.children(".key_type").val();
				
				if (key_type == "string") {
					var exists_true_false = $.inArray(key, LayerOptionsUtilObj.options_true_false) != -1;
					var exists_value = $.inArray(key, LayerOptionsUtilObj.options_value) != -1;
					
					if (key_type == "string" && (exists_true_false || exists_value)) {
						var item_idx = item.children(".key").attr("name");
						item_idx = item_idx.substr("options[".length, item_idx.indexOf("]") - "options[".length);
						var item_html = LayerOptionsUtilObj.getOptionHtml(item_idx, key, key_type, item.children(".value").val(), item.children(".value_type").val());
						var new_item = $(item_html);
						
						item.after(new_item);
						item.remove();
						
						LayerOptionsUtilObj.onChangeOptionKey( new_item.find("select.key")[0] );
						
						ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml(new_item);
					}
				}
			});
		}
		else
			this.initOptionsArrayOld(options_elm, items);
	},
	
	initOptionsArrayOld : function(options_elm, items) {
		if (!items)
			items = {};

		if (items.hasOwnProperty('value'))
			items = {0 : items};
		
		var html = '<div class="items">' +
				'<a class="icon group_add" onClick="LayerOptionsUtilObj.addOption(this, 2)" title="Add new option 1">add option</a>' + 
				'<a class="icon item_add" onClick="LayerOptionsUtilObj.addOption(this)" title="Add new option 2">add option</a>' + 
				'<a class="icon add" onClick="LayerOptionsUtilObj.addOption(this, 3)" title="Add new option 3">add option</a>' + 
			'</div>' +
			'<ul>';
		
		var idx = 0;
		for (var i in items) {
			if (i >= 0) {
				idx++;
				
				var item = items[i];
				var key = item["key"];
				var key_type = item["key_type"];
				var value = item["value"];
				var value_type = item["value_type"];
				
				html += this.getOptionHtml(idx, key, key_type, value, value_type);
			}
		}
		
		html += "</ul>";
		
		options_elm.html(html);
		
		ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml( options_elm.children("ul") );
	},
	
	getOptionHtml : function(idx, key, key_type, value, value_type, type) {
		key = typeof key != "undefined" && key != null ? "" + key : "";
		key_type = typeof key_type != "undefined" ? "" + key_type : "";
		value = typeof value != "undefined" && value != null ? "" + value : "";
		value_type = typeof value_type != "undefined" ? "" + value_type : "";
		
		//console.log(idx+", "+key+", "+key_type+", "+value+", "+value_type+", "+type);
		
		var exists_true_false = $.inArray(key, this.options_true_false) != -1;
		var exists_value = $.inArray(key, this.options_value) != -1;
		
		if (type != 3 && key_type == "string" && (!key || exists_true_false || exists_value)) {
			var options = this.options_true_false.concat(this.options_value);
		
			var html = '<li class="item">' +
				'<input type="hidden" class="task_property_field" name="options[' + idx + '][key_type]" value="string" />';
			
			if (type != 2 && (!key || !value || value == "1" || value == "0" || value.toLowerCase() == "true" || value.toLowerCase() == "false")) {
				value = value && (value == "1" || value.toLowerCase() == "true") ? 1 : 0;
				
				html += '<select class="key task_property_field" name="options[' + idx + '][key]">';
				for (var i = 0; i < options.length; i++) {
					html += '<option' + (options[i] == key ? ' selected' : '') + '>' + options[i] + '</option>';
				}
				html += '</select>' +
					'<select class="value task_property_field" name="options[' + idx + '][value]" title="Option value">' +
						'<option' + (value ? " selected" : "") + '>true</option>' +
						'<option' + (!value ? " selected" : "") + '>false</option>' +
					'</select>' +
					'<input type="hidden" class="value_type task_property_field" name="options[' + idx + '][value_type]" value="" />';
			}
			else {
				var add_variable_icon = typeof ProgrammingTaskUtil.on_programming_task_choose_created_variable_callback == "function";
				
				html += '<select class="key task_property_field" name="options[' + idx + '][key]" onChange="LayerOptionsUtilObj.onChangeOptionKey(this)">';
				for (var i = 0; i < options.length; i++) {
					html += '<option' + (options[i] == key ? ' selected' : '') + '>' + options[i] + '</option>';
				}
				html += '</select>' +
					'<input type="text" class="value task_property_field" name="options[' + idx + '][value]" value="' + value.replace(/"/g, "&quot;") + '" title="Option value" />' +
					(add_variable_icon ? '<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>' : '') +
					'<span class="icon search" onClick="LayerOptionsUtilObj.onChooseDBDriver(this)" style="' + (key != "db_driver" ? 'display:none;' : '') + '">Search</span>' +
					'<select class="value_type task_property_field" name="options[' + idx + '][value_type]" title="Option value type">' +
						'<option' + (value_type == "string" ? " selected" : "") + '>string</option>' +
						'<option' + (value_type == "variable" ? " selected" : "") + '>variable</option>' +
						'<option value=""' + (value && value_type != "string" && value_type != "variable" ? " selected" : "") + '></option>' +
					'</select>';
			}
			
			html += 	'<a class="icon remove" onClick="$(this).parent().remove()" title="Remove item">remove</a>' +
				'</li>';
			
			return html;
		}
		else if (ArrayTaskUtilObj && ArrayTaskUtilObj.getItemHtml)
			return ArrayTaskUtilObj.getItemHtml('options[' + idx + ']', key, key_type, value, value_type);
		else {
			return '<li class="item">' +
				'<input type="text" class="key task_property_field" name="options[' + idx + '][key]" value="' + key.replace(/"/g, "&quot;") + '" title="Option key" />' +
				'<select class="key_type task_property_field" name="options[' + idx + '][key_type]" title="Option key type">' +
					'<option' + (key_type == "string" ? " selected" : "") + '>string</option>' +
					'<option' + (key_type == "variable" ? " selected" : "") + '>variable</option>' +
					'<option value=""' + (key && key_type != "string" && key_type != "variable" && key_type != "null" ? " selected" : "") + '>code</option>' +
					'<option value="null"' + (key_type == "null" ? " selected" : "") + '>-- none --</option>' +
				'</select>' +
				'<input type="text" class="value task_property_field" name="options[' + idx + '][value]" value="' + value.replace(/"/g, "&quot;") + '" title="Option value" />' +
				'<select class="value_type task_property_field" name="options[' + idx + '][value_type]" title="Option value type">' +
					'<option' + (value_type == "string" ? " selected" : "") + '>string</option>' +
					'<option' + (value_type == "variable" ? " selected" : "") + '>variable</option>' +
					'<option value=""' + (value && value_type != "string" && value_type != "variable" ? " selected" : "") + '>code</option>' +
				'</select>' +
				'<a class="icon remove" onClick="$(this).parent().remove()" title="Remove item">remove</a>' +
			'</li>';
		}
	},

	addOption : function(a, type) {
		var main_ul = $(a).parent().parent().children("ul").first();

		if (main_ul) {
			var idx = $(main_ul).attr('li_counter');
			if (!idx || idx <= 0)
				idx = $(main_ul).children().length;
			
			++idx;
			$(main_ul).attr('li_counter', idx);

			var html = this.getOptionHtml(idx, "", "string", "", "string", type);
			var item = $(html);
			item.addClass("option_" + (typeof type != "undefined" ? type : ""));
			
			$(main_ul).append(item);
			
			ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml(item);
		}
	},
	
	onChangeOptionsType : function(elm) {
		var options_type = $(elm).val();
		
		var parent = $(elm).parent();
		var options_elm = parent.children(".options");
		
		if (options_type == "array") {
			parent.find(".options_code").hide();
			options_elm.show();
			
			if (!options_elm.find(".items")[0]) {
				var items = {0: {key_type: "string", value_type: "string"}};
				this.initOptionsArray(options_elm, items);
			}
		}
		else {
			parent.find(".options_code").show();
			options_elm.hide();
		}
		
		ProgrammingTaskUtil.onChangeTaskFieldType(elm);
	},
	
	onChangeOptionKey : function(elm) {
		var key = $(elm).val();
		var p = $(elm).parent();
		
		if (key == "db_driver") {
			p.children(".search").show();
			p.children(".add_variable").hide();
		}
		else {
			p.children(".search").hide();
			p.children(".add_variable").css("display", "inline"); //do not use show() otherwise the display will be block and UI will be weired.
		}
	},
	
	onChooseDBDriver : function(elm) {
		if (typeof this.on_choose_db_driver_callback == "function") {
			this.on_choose_db_driver_callback(elm);
		}
	},
};
