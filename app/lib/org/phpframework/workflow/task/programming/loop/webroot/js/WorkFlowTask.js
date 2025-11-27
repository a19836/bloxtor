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

var LoopTaskPropertyObj = {
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		//console.log(properties_html_elm);
		//console.log(task_id);
		//console.log(task_property_values);
		
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		
		if (!task_property_values) {
			task_property_values = {};
		}

		var html;

		/* START: INIT */
		if (!task_property_values.init) {
			task_property_values.init = [];
		}
		
		html = '';
		if (task_property_values.init.length == 0) {
			html = LoopTaskPropertyObj.getInitCounterVariableHtml(1);
		}
		else {
			if (task_property_values.init.hasOwnProperty("name") || task_property_values.init.hasOwnProperty("code")) {
				task_property_values.init = [ task_property_values.init ];
			}
		
			var idx = 0;
			for (var i in task_property_values.init) {
				if (i >= 0) {
					var data = task_property_values.init[i];
					idx++;
					
					if (data.hasOwnProperty("code")) {
						html += LoopTaskPropertyObj.getInitCounterCodeHtml(idx, data["code"]);
					}
					else {
						html += LoopTaskPropertyObj.getInitCounterVariableHtml(idx, data["name"], data["value"], data["type"]);
					}
				}
			}
		}
		var fields_ul= $(properties_html_elm).find('.loop_task_html .init_counters .fields ul');
		fields_ul.html(html);
		
		ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml( fields_ul.children() );
		/* END: INIT */

		/* START: CONDITIONS */
		if (!task_property_values.cond) {
			task_property_values.cond = {};
		}

		if (!task_property_values.cond.group) {
			task_property_values.cond.group = {'join' : 'and', item : {first : '', operator : '', second : ''}};
		}
		
		html = '';
		if (task_property_values.cond.group.hasOwnProperty('join')) {
			html += ConditionsTaskUtilObj.loadPropertyValues(task_property_values.cond.group, 'cond[group][1]', true);
		}
		else {
			for (var i in task_property_values.cond.group) {
				var idx = parseInt(i) + 1;
				html += ConditionsTaskUtilObj.loadPropertyValues(task_property_values.cond.group[i], 'cond[group][' + idx + ']', true);
			}
		}

		var conditions = $(properties_html_elm).find('.loop_task_html .test_counters .conditions');
		conditions.html(html);
		
		ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml( conditions.children() );
		/* END: CONDITIONS */

		/* START: INC */
		if (!task_property_values.inc) {
			task_property_values.inc = [];
		}
		
		html = '';
		if (task_property_values.inc.length == 0) {
			html = LoopTaskPropertyObj.getIncrementCounterVariableHtml(1);
		}
		else {
			if (task_property_values.inc.hasOwnProperty("name") || task_property_values.inc.hasOwnProperty("code")) {
				task_property_values.inc = [ task_property_values.inc ];
			}
		
			var idx = 0;
			for (var i in task_property_values.inc) {
				if (i >= 0) {
					var data = task_property_values.inc[i];
					idx++;
					
					if (data.hasOwnProperty("code")) {
						html += LoopTaskPropertyObj.getIncrementCounterCodeHtml(idx, data["code"]);
					}
					else {
						html += LoopTaskPropertyObj.getIncrementCounterVariableHtml(idx, data["name"], data["inc_or_dec"]);
					}
				}
			}
		}
		var fields_ul = $(properties_html_elm).find('.loop_task_html .increment_counters .fields ul');
		fields_ul.html(html);
		
		ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml( fields_ul.children() );
		/* END: INC */
	},
	
	getInitCounterVariableHtml : function(idx, name, value, type) {
		name = typeof name != "undefined" && name != null ? name.replace(/"/g, "&quot;") : "";
		value = typeof value != "undefined" && value != null ? value.replace(/"/g, "&quot;") : "";
		type = typeof type != "undefined" && type != null ? type : "";
		
		name = name.substr(0, 1) == '$' ? name.substr(1) : name;
		value = type == "variable" && value.substr(0, 1) == '$' ? value.substr(1) : value;
		
		return '<li class="variable">' +
			'<label class="var_name">Var Name:</label>' +
			'<input type="text" class="task_property_field" name="init[' + idx + '][name]" value="' + name + '" />' +
			'<label class="var_value">Value:</label>' +
			'<input type="text" class="task_property_field" name="init[' + idx + '][value]" value="' + value + '" />' +
			'<select class="task_property_field" name="init[' + idx + '][type]">' +
				'<option></option>' +
				'<option' + (type == "variable" ? " selected" : "") + '>variable</option>' +
				'<option' + (type == "string" ? " selected" : "") + '>string</option>' +
			'</select>' +
			'<a class="icon remove" onClick="$(this).parent().remove()"></a>' +
		'</li>';
	},
	
	getInitCounterCodeHtml : function(idx, code) {
		code = code ? code.replace(/"/g, "&quot;") : "";
		
		return '<li class="code">' +
			'<label>Code:</label>' +
			'<input type="text" class="task_property_field" name="init[' + idx + '][code]" value="' + code + '" />' +
			'<a class="icon remove" onClick="$(this).parent().remove()"></a>' +
		'</li>';
	},
	
	addInitCounterVariable : function(elm) {
		var fields = $(elm).parent().find(".fields ul");
		var idx = this.getNewLiCounter(fields);
		
		var html = this.getInitCounterVariableHtml(idx);
		fields.append(html);
		
		ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml( fields.children("li").last() );
	},
	
	addInitCounterCode : function(elm) {
		var fields = $(elm).parent().find(".fields ul");
		var idx = this.getNewLiCounter(fields);
		
		var html = this.getInitCounterCodeHtml(idx);
		fields.append(html);
		
		ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml( fields.children("li").last() );
	},
	
	getIncrementCounterVariableHtml : function(idx, name, type) {
		name = name ? name.replace(/"/g, "&quot;") : "";
		type = type ? type : "";
		
		name = name.substr(0, 1) == '$' ? name.substr(1) : name;
		
		return '<li class="variable">' +
			'<label class="var_name">Var Name:</label>' +
			'<input type="text" class="task_property_field" name="inc[' + idx + '][name]" value="' + name + '" />' +
			'<label class="inc_type">Type:</label>' +
			'<select class="task_property_field" name="inc[' + idx + '][inc_or_dec]">' +
				'<option' + (type == "increment" ? " selected" : "") + '>increment</option>' +
				'<option' + (type == "decrement" ? " selected" : "") + '>decrement</option>' +
			'</select>' +
			'<a class="icon remove" onClick="$(this).parent().remove()"></a>' +
		'</li>';
	},
	
	getIncrementCounterCodeHtml : function(idx, code) {
		code = code ? code.replace(/"/g, "&quot;") : "";
		
		return '<li class="code">' +
			'<label>Code:</label>' +
			'<input type="text" class="task_property_field" name="inc[' + idx + '][code]" value="' + code + '" />' +
			'<a class="icon remove" onClick="$(this).parent().remove()"></a>' +
		'</li>';
	},
	
	addIncrementCounterVariable : function(elm) {
		var fields = $(elm).parent().find(".fields ul");
		var idx = this.getNewLiCounter(fields);
		
		var html = this.getIncrementCounterVariableHtml(idx);
		fields.append(html);
		
		ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml( fields.children("li").last() );
	},
	
	addIncrementCounterCode : function(elm) {
		var fields = $(elm).parent().find(".fields ul");
		var idx = this.getNewLiCounter(fields);
		
		var html = this.getIncrementCounterCodeHtml(idx);
		fields.append(html);
		
		ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml( fields.children("li").last() );
	},
	
	getNewLiCounter : function(fields) {
		var idx = fields.attr("li_counter");
		if (!idx || idx <= 0) {
			idx = fields.children().length;
		}
		++idx;
		fields.attr('li_counter', idx);
		
		return idx;
	},
		
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var labels = LoopTaskPropertyObj.getExitLabels(task_property_values);
			ProgrammingTaskUtil.updateTaskExitsLabels(task_id, labels);
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
			var labels = LoopTaskPropertyObj.getExitLabels(task_property_values);
			ProgrammingTaskUtil.updateTaskExitsLabels(task_id, labels);
		
			onEditLabel(task_id);
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 30);
	},
	
	getExitLabels : function(task_property_values) {
		var labels = {"start_exit": "Start loop", "default_exit": "End loop"}; //bc of old diagrams where task_property_values["exits"] don't have the labels.
		
		if (task_property_values && task_property_values["exits"]) {
			var exits = task_property_values["exits"];
			labels["start_exit"] = exits["start_exit"] && exits["start_exit"]["label"] ? exits["start_exit"]["label"] : labels["start_exit"];
			labels["default_exit"] = exits["default_exit"] && exits["default_exit"]["label"] ? exits["default_exit"]["label"] : labels["default_exit"];
		}
		
		return labels;
	},
};
