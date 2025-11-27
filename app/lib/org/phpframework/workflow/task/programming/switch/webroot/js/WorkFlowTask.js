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

var SwitchTaskPropertyObj = {
	properties_html_elm : null,
	previous_task_property_values : null,
	
	available_default_colors : ["#46F4A0", "#99F2FA", "#33ADB7", "#AAAAFD", "#FF83B4", "#FF9B6F", "#F2EC22", "#B9BF15", "#7B800E", "#00727C", "#29B473", "#00DEF2", "#7171FB", "#BF4B79", "#FF590F", "#124DB5", "#99D6DB", "#A3FAD0", "#FFC1DA", "#FCFFA4"],
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		//console.debug(properties_html_elm);
		//console.debug(task_id);
		//console.debug(task_property_values);
		
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);

		SwitchTaskPropertyObj.properties_html_elm = properties_html_elm;

		var html = '';
	
		if (!task_property_values) {
			task_property_values = {};
		}

		if (!task_property_values.cases) {
			task_property_values.cases = {};
		}

		if (!task_property_values.cases.hasOwnProperty('case') || !task_property_values.cases['case']) {
			task_property_values.cases['case'] = [{'value' : '', exit : 'exit_1'}];
		}

		if (!task_property_values.hasOwnProperty('default') || !task_property_values['default']) {
			task_property_values['default'] = {};
		}

		if (!task_property_values['default'].hasOwnProperty('exit') || !task_property_values['default'].exit) {
			task_property_values['default'].exit = 'default_exit';
		}
		
		var object_var = typeof task_property_values["object_var"] != "undefined" && task_property_values["object_var"] != null ? task_property_values["object_var"] : "";
		object_var = task_property_values["object_type"] == "variable" && object_var.substr(0, 1) == "$" ? object_var.substr(1) : object_var;
		properties_html_elm.find(".object_var input").val(object_var);
		
		//PREPARING CASES
		if (task_property_values.cases['case']) {
			var c = task_property_values.cases['case'];
	
			var idx = 0;
			if (c.hasOwnProperty('value')) {//case singular
				html += SwitchTaskPropertyObj.getCaseHtml(task_property_values, c, idx, "#426efa");
			}
			else {//case multiple
				for (var i in c) {
					html += SwitchTaskPropertyObj.getCaseHtml(task_property_values, c[i], idx, idx == 0 ? "#426efa" : "");
					idx++;
				}
			}
		}
		var cases_ul = $(properties_html_elm).find('.switch_task_html .cases ul').first();
		cases_ul.html(html);
		cases_ul.attr('cases_counter', idx + 1);
		
		ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml( cases_ul.children("li") );
		
		//PREPARING DEFAULT
		var default_color = "#000";//SwitchTaskPropertyObj.getExitColor(task_property_values, task_property_values['default'].exit);

		$(properties_html_elm).find('.switch_task_html .default_property_exit').css({backgroundColor : default_color});
		$(properties_html_elm).find('.switch_task_html .default_property_exit').attr('value', task_property_values['default'].exit);

		$(properties_html_elm).find('.switch_task_html .default_exit').attr('exit_id', task_property_values['default'].exit);
		$(properties_html_elm).find('.switch_task_html .default_exit').attr('exit_color', default_color);
	},

	getCaseHtml : function(task_property_values, case_item, idx, case_color) {
		var case_color = case_color ? case_color : this.getExitColor(task_property_values, case_item.exit);
		var case_value = case_item.value ? case_item.value.replace(/"/g, "&quot;") : "";

		return '<li>' +
				'<input type=\"text\" class=\"task_property_field\" property_name=\"cases[case][' + idx + '][value]\" value=\"' + case_value + '\" />' +
				'<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Search</span>' + 
				'<div class=\"task_property_field case_property_exit\" property_name=\"cases[case][' + idx + '][exit]\" value=\"' + case_item.exit + '\" style=\"background-color:' + case_color + '\"></div>' +
				
				'<div class=\"task_property_exit case_exit\" exit_id=\"' + case_item.exit + '\" exit_color=\"' + case_color + '\"></div>' +
		
				'<a class="icon remove" onClick=\"SwitchTaskPropertyObj.removeCase(this)\">remove</a>' +
		'</li>';
	},

	getExitColor : function(task_property_values, exit_id) {
		if (!exit_id) {
			alert('Error in SwitchTaskPropertyObj->getExitColor: exit_id is undefined.');
		}

		if (task_property_values && task_property_values.exits) {
			var exit = task_property_values.exits[ exit_id ];
	
			if (exit && exit.color) {
				return exit.color;
			}
		}
		
		if (this.properties_html_elm) {
			var items = $(this.properties_html_elm).find('.switch_task_html .task_property_exit');
			var total = items.length;
		
			var new_exit_color;
			var status;
			var idx = 0;
		
			do {
				//get first default colors and after create new colors.
				new_exit_color = this.available_default_colors && this.available_default_colors.length > idx ? this.available_default_colors[idx] : nextColor();
				
				status = new_exit_color != "rgb(0,0,0)";
				for (var i = 0; status && i < total; i++) {
					if ($(items[i]).attr('exit_color') == new_exit_color) {
						status = false;
						break;
					}
				}
				
				idx++;
			} while (!status);
		
			return new_exit_color;
		}
		
		return randomColor();
	},
	
	getNewExitId : function() {
		var items = $(this.properties_html_elm).find('.switch_task_html .task_property_exit');
		var total = items.length;
		
		var new_exit_id;
		var status;
		
		do {
			new_exit_id = 'exit_' + parseInt(Math.random() * 10000);
			
			status = true;
			for (var i = 0; i < total; i++) {
				if ($(items[i]).attr('exit_id') == new_exit_id) {
					status = false;
				}
			}
		} while (!status);
		
		return new_exit_id;
	},

	addCase : function(a) {
		var main_ul = $(this.properties_html_elm).find('.switch_task_html .cases ul').first();
		
		var cases_counter = parseInt( $(main_ul).attr('cases_counter') );
		$(main_ul).attr('cases_counter', cases_counter + 1);
		
		var exit_id = this.getNewExitId();
		var case_item = {'value' : '', 'exit' : exit_id};

		var html = this.getCaseHtml(null, case_item, cases_counter);

		main_ul.append(html);
		
		ProgrammingTaskUtil.onProgrammingTaskPropertiesNewHtml( main_ul.children("li").last() );
	},

	removeCase : function(a) {
		$(a).parent().remove();
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		SwitchTaskPropertyObj.previous_task_property_values = myWFObj.getTaskFlowChart().TaskFlow.tasks_properties[task_id];
		
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var labels = SwitchTaskPropertyObj.getExitLabels(task_property_values);
			ProgrammingTaskUtil.updateTaskExitsLabels(task_id, labels);
			
			//update labels on all connections but without user label, for only the cases exits
			if (labels) {
				var previous_task_property_values = SwitchTaskPropertyObj.previous_task_property_values;
				var prev_cases = previous_task_property_values["cases"] && previous_task_property_values["cases"]["case"] ? previous_task_property_values["cases"]["case"] : null;
				
				if (prev_cases) {
					//prepare cases in case not being an array
					if (prev_cases["exit"])
						prev_cases = [prev_cases];
					
					//checks which exits were changed
					var labels_to_update = {};
					
					for (var i in prev_cases) {
						var c = prev_cases[i];
						var exit_id = c["exit"];
						
						if (exit_id && exit_id != "default_exit") {
							var prev_exit_value = typeof c["value"] != "undefined" || typeof c["value"] != null ? c["value"] : "";
							
							if (labels.hasOwnProperty(exit_id) && labels[exit_id] != prev_exit_value)
								labels_to_update[exit_id] = labels[exit_id];
						}
					}
					
					//update exits that were changed
					ProgrammingTaskUtil.updateTaskExitsConnectionExitLabelAttribute(task_id, labels_to_update);
					ProgrammingTaskUtil.updateTaskExitsConnectionsLabels(task_id, labels_to_update);
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
			var labels = SwitchTaskPropertyObj.getExitLabels(task_property_values);
			ProgrammingTaskUtil.updateTaskExitsLabels(task_id, labels);
			ProgrammingTaskUtil.updateTaskExitsConnectionExitLabelAttribute(task_id, labels);
			
			onEditLabel(task_id);
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 100);
	},
	
	getExitLabels : function(task_property_values) {
		var labels = {"default_exit": "Default"}; //bc of old diagrams where task_property_values["exits"] don't have the labels.
		
		if (task_property_values && task_property_values["exits"]) {
			var exits = task_property_values["exits"];
			labels["default_exit"] = exits["default_exit"] && exits["default_exit"]["label"] ? exits["default_exit"]["label"] : labels["default_exit"];
		}
		
		var cases = task_property_values["cases"] && task_property_values["cases"]["case"] ? task_property_values["cases"]["case"] : null;
		if (cases) {
			if (cases["exit"]) {
				cases = [cases];
			}
			
			for (var i in cases) {
				var c = cases[i];
				var exit_id = c["exit"];
				
				if (exit_id && exit_id != "default_exit") {
					labels[exit_id] = typeof c["value"] != "undefined" || typeof c["value"] != null ? c["value"] : "";
				}
			}
		}
		//console.log(labels);
		
		return labels;
	},
};
