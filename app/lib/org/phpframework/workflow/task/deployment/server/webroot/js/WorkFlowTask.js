/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

var ServerTaskPropertyObj = {
	
	//public attributes to be populated by external pages
	template_workflow_html : "",
	get_layers_tasks_file_url : "",
	validate_template_properties_url : "",
	deploy_template_to_server_url : "",
	template_tasks_types_by_tag : null,
	on_choose_test_units_callback : null,
	on_choose_template_file_callback : null,
	on_choose_template_flow_layer_file_callback : null,
	on_get_layer_wordpress_installations_url_callback : null,
	on_open_template_properties_popup_callback : null,
	on_close_template_properties_popup_callback : null,
	on_open_server_properties_popup_callback : null,
	on_close_server_properties_popup_callback : null,
	show_php_obfuscation_option : true,
	show_js_obfuscation_option : true,
	projects_max_expiration_date : "",
	sysadmin_max_expiration_date : "",
	projects_max_num : "",
	users_max_num : "",
	end_users_max_num : "",
	actions_max_num : "",
	allowed_paths : "",
	allowed_domains : "",
	check_allowed_domains_port : false,
	allowed_sysadmin_migration : false,
	
	//private attributes
	templates_properties : {},
	templates_changed : {},
	server_deployments_template_name_replacements : {},
	server_time_diff_in_milliseconds : 0,
	
	TemplatesWorkflowHandlerObject : null,
	TaskFlowChartObject : null,
	workflow_global_variables : null,
	TemplatePropertiesMyFancyPopupObject : new MyFancyPopupClass(),
	TemplatePropertiesGlobalSettingsAndVarsMyFancyPopupObject : new MyFancyPopupClass(),
	
	/* HANDLERS */
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		var task_html_element = properties_html_elm.find(".server_task_html");
		var template_properties = task_html_element.find(" > .templates_container > .template_properties");
		
		//call callback
		if (typeof ServerTaskPropertyObj.on_open_server_properties_popup_callback == "function")
			ServerTaskPropertyObj.on_open_server_properties_popup_callback(properties_html_elm, task_id, task_property_values);
		
		//init TaskFlowChartObject and workflow_global_variables
		ServerTaskPropertyObj.TaskFlowChartObject = myWFObj.getTaskFlowChart();
		ServerTaskPropertyObj.workflow_global_variables = assignObjectRecursively({}, window.workflow_global_variables);
		
		//prepare tabs
		task_html_element.tabs();
		template_properties.tabs();
		
		task_html_element.find(" > ul > li > a").click(function() {
			ServerTaskPropertyObj.TaskFlowChartObject.getMyFancyPopupObj().resizeOverlay();
		});
		template_properties.find(" > ul > li > a").click(function() {
			ServerTaskPropertyObj.TemplatePropertiesMyFancyPopupObject.resizeOverlay();
		});
		
		//prepare server details
		var select = task_html_element.find(" > .details_container > .authentication_type > select");
		ServerTaskPropertyObj.onServerAuthenticationTypeChange( select[0] );
		
		//load templates
		ServerTaskPropertyObj.templates_properties = {};
		ServerTaskPropertyObj.TemplatesWorkflowHandlerObject = null;
		
		if (task_property_values && task_property_values.hasOwnProperty("templates") && task_property_values["templates"]) {
			var add_icon = task_html_element.find(" > .templates_container > table > thead > tr > .actions > .add")[0];
			
			if ($.isPlainObject(task_property_values["templates"]) && (task_property_values["templates"].hasOwnProperty("name") || task_property_values["templates"].hasOwnProperty("created_date") || task_property_values["templates"].hasOwnProperty("modified_date") || task_property_values["templates"].hasOwnProperty("properties") || task_property_values["templates"].hasOwnProperty("template_id"))) {
				var template_properties = task_property_values["templates"];
				var template_id = $.isNumeric(template_properties["template_id"]) ? template_properties["template_id"] : 0;
				
				task_property_values["templates"] = {};
				task_property_values["templates"][template_id] = template_properties;
			}
			
			$.each(task_property_values["templates"], function(template_id, props) {
				if (!props.hasOwnProperty("template_id"))
					props["template_id"] = template_id;
				
				ServerTaskPropertyObj.templates_properties[template_id] = assignObjectRecursively({}, props["properties"]); //very important otherwise the ServerTaskPropertyObj.templates_properties[template_id] will be the same object that the task_property_values["templates"][template_id] which will mess all the ServerTaskPropertyObj logic code.
				
				ServerTaskPropertyObj.addTemplate(add_icon, props);
			});
		}
		
		//load deployments
		ServerTaskPropertyObj.server_deployments_template_name_replacements = {};
		
		if (task_property_values && task_property_values.hasOwnProperty("deployments") && task_property_values["deployments"]) {
			var tbody = task_html_element.find(" > .deployments_container > table > tbody");
			
			if ($.isPlainObject(task_property_values["deployments"]) && (task_property_values["deployments"].hasOwnProperty("deployment_id") || task_property_values["deployments"].hasOwnProperty("template_name") || task_property_values["deployments"].hasOwnProperty("created_date") || task_property_values["deployments"].hasOwnProperty("template_id")))
				task_property_values["deployments"] = [ task_property_values["deployments"] ];
			
			$.each(task_property_values["deployments"], function(idx, props) {
				if (props)
					ServerTaskPropertyObj.addDeployment(tbody, props);
			});
		}
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		//delete templates workflow
		var template_properties = properties_html_elm.find(".server_task_html > .templates_container > .template_properties");
		ServerTaskPropertyObj.destroyTemplatesWorkflow(template_properties);
		
		//saved deployments bc after this method it will be reseted!
		var WF = myWFObj.getTaskFlowChart();
		ServerTaskPropertyObj.saved_deployments = WF.TaskFlow.tasks_properties[task_id]["deployments"];
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		//save templates
		if (task_property_values && task_property_values.hasOwnProperty("templates") && task_property_values["templates"]) {
			$.each(task_property_values["templates"], function(template_id, props) {
				task_property_values["templates"][template_id]["template_id"] = template_id;
				task_property_values["templates"][template_id]["properties"] = ServerTaskPropertyObj.templates_properties[template_id];
			});
		}
		
		//save deployments
		task_property_values["deployments"] = ServerTaskPropertyObj.saved_deployments ? ServerTaskPropertyObj.saved_deployments : [];
		
		//update deployments templates names, in case some someone changed a template name.
		if (ServerTaskPropertyObj.server_deployments_template_name_replacements)
			$.each(ServerTaskPropertyObj.server_deployments_template_name_replacements, function(template_id, name) {
				$.each(task_property_values["deployments"], function(idx, deployment) {
					if (deployment["template_id"] == template_id)
						task_property_values["deployments"][idx]["template_name"] = name;
				});
			});
		
		//release some memory
		ServerTaskPropertyObj.templates_properties = {};
		ServerTaskPropertyObj.TemplatesWorkflowHandlerObject = null;
		ServerTaskPropertyObj.server_deployments_template_name_replacements = {};
		
		ServerTaskPropertyObj.saved_deployments = null;
		delete ServerTaskPropertyObj.saved_deployments;
		
		//call callback
		if (typeof ServerTaskPropertyObj.on_close_server_properties_popup_callback == "function")
			ServerTaskPropertyObj.on_close_server_properties_popup_callback(properties_html_elm, task_id, task_property_values, status);
	},
	
	onCancelTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		//delete templates workflow
		var template_properties = properties_html_elm.find(".server_task_html > .templates_container > .template_properties");
		ServerTaskPropertyObj.destroyTemplatesWorkflow(template_properties);
		
		//release some memory
		ServerTaskPropertyObj.templates_properties = {};
		ServerTaskPropertyObj.TemplatesWorkflowHandlerObject = null;
		ServerTaskPropertyObj.server_deployments_template_name_replacements = {};
		
		//call callback
		if (typeof ServerTaskPropertyObj.on_close_server_properties_popup_callback == "function")
			ServerTaskPropertyObj.on_close_server_properties_popup_callback(properties_html_elm, task_id, task_property_values, status);
		
		return true;
	},
	
	onTaskConnection : function(conn) {
		//init TaskFlowChartObject
		ServerTaskPropertyObj.TaskFlowChartObject = myWFObj.getTaskFlowChart();
		
		return true;
	},
	
	onCheckLabel : function(label_obj, task_id) {
		return isTaskLabelRepeated(label_obj, task_id) == false;
	},

	onCancelLabel : function(task_id) {
		return prepareLabelIfUserLabelIsInvalid(task_id);
	},

	onCompleteLabel : function(task_id) {
		var WF = myWFObj.getTaskFlowChart();
		var label_elm = WF.TaskFlow.getTaskLabelElementByTaskId(task_id);
		label_elm.closest("." + WF.TaskFlow.task_label_class_name).attr("title", label_elm.text());
		
		WF.TaskFlow.repaintTaskByTaskId(task_id);
		
		return true;
	},
	
	/* SERVER DETAILS FUNCTIONS */
	
	onServerAuthenticationTypeChange : function(elm) {
		elm = $(elm);
		var type = elm.val();
		var p = elm.parent().parent();
		
		switch (type) {
			case "password":
				p.children(".password").show();
				p.children(".ssh_auth_pub, .ssh_auth_pri, .ssh_auth_pub_file, .ssh_auth_pri_file, .ssh_auth_passphrase").hide();
				break;
			case "key_strings":
				p.children(".ssh_auth_pub, .ssh_auth_pri, .ssh_auth_passphrase").show();
				p.children(".password, .ssh_auth_pub_file, .ssh_auth_pri_file").hide();
				break;
			case "key_files":
				p.children(".ssh_auth_pub_file, .ssh_auth_pri_file, .ssh_auth_passphrase").show();
				p.children(".password, .ssh_auth_pub, .ssh_auth_pri").hide();
				break;
		}
		
		//refresh popup
		ServerTaskPropertyObj.TaskFlowChartObject.getMyFancyPopupObj().updatePopup();
	},
	
	/* TEMPLATE FUNCTIONS */
	
	getNewTemplateId : function(tbody) {
		var id = getListNewIndex(tbody);
		var tds = tbody.parent().closest(".server_task_html").find(" > .deployments_container > table > tbody > tr:not(.no_items) > td.template");
		
		$.each(tds, function(idx, td) {
			var t_id = parseInt(td.getAttribute("template_id"));
			
			if (t_id > id)
				id = t_id + 1;
		});
		
		return id;
	},
	
	addUserTemplate : function(elm) {
		var template_name = prompt("Please write the new template name:");
		
		if (template_name && this.isTemplateNameValid(elm, template_name)) {
			template_name = template_name.replace(/^\s+/, "").replace(/\s+$/, "");
			
			var new_item = this.addTemplate(elm, {name: template_name});
			var template_id = new_item.attr("template_id");
			
			this.openTemplateProperties(new_item.find(" > .actions > .edit")[0], template_id);
			
			return new_item;
		}
	},
	
	addTemplate : function(elm, props) {
		var d = new Date();
		var time = d.getTime() + this.server_time_diff_in_milliseconds;
		
		var template_name = props && props.hasOwnProperty("name") && props["name"] != null ? props["name"] : "";
		var created_date = props && props.hasOwnProperty("created_date") ? props["created_date"] : time;
		var modified_date = props && props.hasOwnProperty("modified_date") ? props["modified_date"] : time;
		
		var tbody = $(elm).parent().closest("table").children("tbody");
		var template_id = props && props.hasOwnProperty("template_id") ? props["template_id"] : this.getNewTemplateId(tbody);
		
		var html = $('<tr template_id="' + template_id + '">'
			+ '	<td class="name"><input class="task_property_field" type="text" name="templates[' + template_id + '][name]" value="' + template_name + '" onBlur="ServerTaskPropertyObj.onTemplateNameInputBLur(this);" prev_value="' + template_name + '" /></td>'
			+ '	<td class="created_date"><span>' + this.printTime(created_date) + '</span><input class="task_property_field" type="hidden" name="templates[' + template_id + '][created_date]" value="' + created_date + '"/></td>'
			+ '	<td class="modified_date"><span>' + this.printTime(modified_date) + '</span><input class="task_property_field" type="hidden" name="templates[' + template_id + '][modified_date]" value="' + modified_date + '"/></td>'
			+ '	<td class="actions">'
			+ '		<i class="icon edit" onClick="ServerTaskPropertyObj.editTemplate(this)" title="Edit this template"></i>'
			+ '		<i class="icon remove" onClick="ServerTaskPropertyObj.removeTemplate(this)" title="Remove this template"></i>'
			+ '	<div></div>'
			+ '	</td>'
			+ '</tr>');
		
		tbody.children(".no_items").hide();
		tbody.append(html);
		
		return html;
	},
	
	removeTemplate : function(elm) {
		var tr = $(elm).parent().closest("tr");
		var tbody = tr.parent().closest("tbody");
		
		tr.remove();
		
		if (tbody.children(":not(.no_items)").length == 0)
			tbody.children(".no_items").show();
	},
	
	editTemplate : function(elm) {
		var tr = $(elm).parent().closest("tr");
		var template_id = tr.attr("template_id");
		
		this.openTemplateProperties(elm, template_id);
		
		//validate workflow
		var server_task_id = ServerTaskPropertyObj.TaskFlowChartObject.ContextMenu.getContextMenuTaskId();
		var server_label = ServerTaskPropertyObj.TaskFlowChartObject.TaskFlow.getTaskLabelByTaskId(server_task_id);
		ServerTaskPropertyObj.validateTemplateProperties(server_label, template_id);
	},
	
	onTemplateNameInputBLur : function(elm) {
		elm = $(elm);
		var tr = elm.parent().closest("tr");
		var template_id = tr.attr("template_id");
		var name = elm.val().replace(/^\s+/, "").replace(/\s+$/, "");
		var prev_name = elm.attr("prev_value");
		
		if (!name)
			elm.val(prev_name);
		if (prev_name != name) {
			if (!this.isTemplateNameValid(elm[0], name))
				elm.val(prev_name);
			else {
				elm.attr("prev_value", name);
				var task_html_elm = elm.parent().closest(".server_task_html");
				
				this.updateModifiedDate(task_html_elm, template_id);
				
				//update correspondent deployments with this new name
				this.replaceDeploymentsTemplateName(task_html_elm, template_id, name);
			}
		}
	},
	
	isTemplateNameValid : function(elm, name) {
		if (name) {
			name = ("" + name).replace(/^\s+/, "").replace(/\s+$/, "");
			
			if (name) {
				var already_exists = false;
				var inputs = $(elm).parent().closest("table").find(" > tbody > tr > td.name > input");
				var selected_input = elm.nodeName.toLowerCase() == "input" ? elm : null;
				
				$.each(inputs, function(idx, input) {
					if (input != selected_input && $(input).val() == name) {
						already_exists = true;
						return false;
					}
				});
				
				if (already_exists) {
					name = "";
					alert("Template name already exists. Please choose a different name!");
				}
			}
			else 
				alert("Template name cannot be blank!");
		}
		else
			alert("Template name cannot be blank!");
		
		return name ? true : false;
	},
	
	updateModifiedDate : function(task_html_element, template_id) {
		var d = new Date();
		var time = d.getTime() + this.server_time_diff_in_milliseconds;
		
		var md = task_html_element.find(" > .templates_container > table > tbody > tr[template_id=" + template_id + "] > .modified_date");
		md.children("span").html( this.printTime(time) );
		md.children("input").val(time);
	},
	
	openTemplateProperties : function(elm, template_id) {
		var template_properties = $(elm).parent().closest(".templates_container").children(".template_properties");
		var template_actions = template_properties.children(".template_actions");
		var template_workflow = template_properties.children(".template_workflow");
		
		//set selected template id
		template_properties.attr("template_id", template_id);
		
		//prepare template_properties
		template_properties.tabs({active: 0}); //This is very important otherwise if the popup is closed with the Actions tab and then openned again, the workflow connections will appear all messy.
		
		//prepare actions
		this.prepareTemplateActions(template_actions);
		
		//prepare workflow
		this.prepareTemplateWorkflow(template_workflow);
		
		//prepare global vars or settings
		this.prepareTemplateGlobalVarsOrSettings(template_properties);
		
		//open popup
		var server_properties_popup = template_properties.parent().closest(".myfancypopup");
		var popup_close = server_properties_popup.children(".popup_close");
		var server_templates_icons = server_properties_popup.find(".server_task_html > .templates_container > table > tbody > tr > td.actions > .icon");
		
		if (typeof this.on_open_template_properties_popup_callback == "function")
			this.on_open_template_properties_popup_callback(elm, template_id, template_properties);
		
		this.TemplatePropertiesMyFancyPopupObject.init({
			elementToShow: template_properties,
			onOpen: function() {
				popup_close.hide();
				server_templates_icons.hide();
				
				setTimeout(function() {
					var z_index = parseInt( template_properties.css("z-index") ) + 5; //must be 5!
					var WF = myWFObj.getTaskFlowChart();
					
					if (z_index)
						$("#" + WF.ContextMenu.task_context_menu_id + ", #" + WF.ContextMenu.connection_context_menu_id).css("z-index", z_index);
				}, 800);
			},
			onClose: function() {
				popup_close.show();
				
				if (ServerTaskPropertyObj.TemplatePropertiesMyFancyPopupObject.settings.saveTemplateProperties)
					ServerTaskPropertyObj.saveTemplateProperties(template_properties);
				
				var WF = myWFObj.getTaskFlowChart();
				WF.Property.hideSelectedConnectionProperties();
				WF.Property.hideSelectedTaskProperties();
				WF.StatusMessage.removeMessages();
				
				myWFObj.setTaskFlowChart(ServerTaskPropertyObj.TaskFlowChartObject);
				window.workflow_global_variables = assignObjectRecursively({}, ServerTaskPropertyObj.workflow_global_variables);
				
				//should be the last to execute
				server_templates_icons.show();
				
				if (typeof ServerTaskPropertyObj.on_close_template_properties_popup_callback == "function")
					ServerTaskPropertyObj.on_close_template_properties_popup_callback(elm, template_id, template_properties);
			},
			saveTemplateProperties: true,
		});
		this.TemplatePropertiesMyFancyPopupObject.showPopup();
	},
	
	saveTemplateProperties : function(template_properties) {
		var template_id = template_properties.attr("template_id");
		
		if ($.isNumeric(template_id)) {
			var properties = this.getTemplateActionsPropertiesToSave( template_properties.children(".template_actions") );
			var workflow_properties = this.getTemplateFlowPropertiesToSave( template_properties.children(".template_workflow") );
			var global_vars_and_settings = this.getTemplateGlobalVarsOrSettingsToSave(template_properties);
			
			for (var k in workflow_properties)
				properties[k] = workflow_properties[k];
			
			//disallow actions[xxx][copy_layers][sysadmin] if not allowed and someone hack the systems
			if (!this.allowed_sysadmin_migration && $.isPlainObject(properties["actions"]))
				$.each(properties["actions"], function(idx, action) {
					if ($.isPlainObject(action) && action.hasOwnProperty("copy_layers") && $.isPlainObject(action["copy_layers"]) && action["copy_layers"].hasOwnProperty("sysadmin")) {
						properties["actions"][idx]["copy_layers"]["sysadmin"] = null;
						delete properties["actions"][idx]["copy_layers"]["sysadmin"];
					}
				});
			
			if (!this.templates_properties.hasOwnProperty(template_id))
				this.templates_properties[template_id] = {};
			else if ($.isPlainObject(this.templates_properties[template_id]) && (!this.templates_properties[template_id].hasOwnProperty("server_installation_folder_path") || !this.templates_properties[template_id]["server_installation_folder_path"])) {
				this.templates_properties[template_id]["server_installation_folder_path"] = ""; //in the beggininig the server_installation_folder_path will be null and we must convert it to "", otherwise the comparison will be true.
				this.templates_properties[template_id]["server_installation_url"] = ""; //in the beggininig the server_installation_url will be null and we must convert it to "", otherwise the comparison will be true.
			}
			
			var is_different = !$.isEmptyObject(global_vars_and_settings) || this.templates_properties[template_id]["server_installation_folder_path"] != properties["server_installation_folder_path"] || this.templates_properties[template_id]["server_installation_url"] != properties["server_installation_url"] || JSON.stringify(this.templates_properties[template_id]["actions"]) != JSON.stringify(properties["actions"]) || JSON.stringify(this.templates_properties[template_id]["connection"]) != JSON.stringify(properties["connection"]) || JSON.stringify(this.templates_properties[template_id]["task"]) != JSON.stringify(properties["task"]);
			
			//console.log(JSON.parse(JSON.stringify(this.templates_properties[template_id])));
			//console.log(JSON.stringify(this.templates_properties[template_id]));
			//console.log(JSON.stringify(properties));
			//console.log(workflow_properties);
			//console.log(JSON.stringify(workflow_properties));
			//console.log(JSON.stringify(properties["task"]));
			//console.log(JSON.stringify(this.templates_properties[template_id]["task"]));
			
			if (is_different && confirm("Do you wish to save your changes?")) {
				this.updateModifiedDate(template_properties.parent().closest(".server_task_html"), template_id);
				
				//save properties
				for (var k in properties)
					this.templates_properties[template_id][k] = properties[k];
				
				//save global vars and settings
				if ($.isPlainObject(global_vars_and_settings))
					for (var k in global_vars_and_settings)
						this.templates_properties[template_id][k] = global_vars_and_settings[k];
				
				this.templates_changed[template_id] = true;
			}
		}
	},
	
	//check if there is global_vasriables or settings that are incompatible with the layers diagram
	//check if there is any connection that is incompatible with the layers diagram
	//check if there is any task that is incompatible with the layers diagram
	validateTemplateProperties : function(server_label, template_id, options) {
		var WF = myWFObj.getTaskFlowChart();
		var url = ("" + this.validate_template_properties_url).replace("#server#", server_label).replace("#template_id#", template_id);
		
		$.ajax({
			url : url,
			dataType : "json",
			success : function(data, text_status, jqXHR) {
				if ($.isNumeric(data) && parseInt(data) == 1) {
					WF.StatusMessage.showMessage("Template is valid!");
					
					if (options && typeof options.success == "function")
						options.success();
				}
				else {
					WF.StatusMessage.showError("There was an error trying to update this code. Please try again." + (data ? "<br/>" + data : ""));
					
					if (options && typeof options.error == "function")
						options.error();
				}
			},
			error : function(jqXHR, textStatus, errorThrown) { 
				var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
				WF.StatusMessage.showError("Error: Could not validate template!\nPlease try again..." + msg);
				
				if (options && typeof options.error == "function")
					options.error();
			},
		});
	},
	
	/* TEMPLATE WORKFLOW FUNCTIONS */
	
	prepareTemplateWorkflow : function(template_workflow) {
		var WF = this.TemplatesWorkflowHandlerObject;
		
		//prepare workflow
		if (!WF) {
			var workflow_elm = $( this.template_workflow_html.replace(/#rand#/g, "global") );
			template_workflow.append(workflow_elm);
			
			WF = this.addTemplateWorkflow("global");
			this.TemplatesWorkflowHandlerObject = WF;
		}
		else
			this.editTemplateWorkflow();
	},
	
	editTemplateWorkflow : function() {
		var WF = this.TemplatesWorkflowHandlerObject;
		
		myWFObj.setTaskFlowChart(WF);
		
		WF.TaskFile.reload();
	},
	
	addTemplateWorkflow : function(rand) {
		//create TaskFlowChart
		var WF = new TaskFlowChart("taskFlowChartObj_" + rand);
		eval('window.taskFlowChartObj_' + rand + ' = WF;');
		
		WF.TaskFlow.main_tasks_flow_obj_id = "taskflowchart_" + rand + " .tasks_flow";
		WF.TaskFlow.main_tasks_properties_obj_id = "taskflowchart_" + rand + " .tasks_properties";
		WF.TaskFlow.main_connections_properties_obj_id = "taskflowchart_" + rand + " .connections_properties";
		WF.ContextMenu.main_tasks_menu_obj_id = "taskflowchart_" + rand + " .tasks_menu";
		WF.ContextMenu.main_tasks_menu_hide_obj_id = "taskflowchart_" + rand + " .tasks_menu_hide";
		WF.ContextMenu.main_workflow_menu_obj_id = "taskflowchart_" + rand + " .workflow_menu";
		
		WF.TaskFlow.default_connection_line_width = 2;
		WF.TaskFlow.default_connection_from_target = true;
		
		myWFObj.setTaskFlowChart(WF);
		
		WF.TaskFile.on_success_read = function() {
			//disable draggable event to all tasks
			var WF = myWFObj.getTaskFlowChart();
			var tasks = WF.TaskFlow.getAllTasks();
			
			if (tasks)
				for (var i = 0; i < tasks.length; i++) {
					var task = $(tasks[i]);
					task.draggable('disable'); //disallow draggable events for tasks
					
					task.find("." + WF.TaskFlow.task_label_class_name + " span").off();
					task.find("." + WF.TaskFlow.task_eps_class_name + " ." + WF.TaskFlow.task_ep_class_name).off();
				}
			
			var template_workflow = $("#" + WF.TaskFlow.main_tasks_flow_obj_id).parent().closest(".template_workflow");
			ServerTaskPropertyObj.loadTemplateFlowProperties(template_workflow);
		};
		
		WF.TaskFile.get_tasks_file_url = this.get_layers_tasks_file_url;

		//prepare tasks settings
		WF.Property.tasks_settings = assignObjectRecursively({}, ServerTaskPropertyObj.TaskFlowChartObject.Property.tasks_settings);
		
		var server_type_id = this.template_tasks_types_by_tag["server"];
		WF.Property.tasks_settings[server_type_id] = null;
		delete WF.Property.tasks_settings[server_type_id];
		var db_type_id = this.template_tasks_types_by_tag["db"];
		
		$.each(WF.Property.tasks_settings, function(type_id, task_settings) {
			if (!task_settings.hasOwnProperty("task_menu") || !$.isPlainObject(task_settings["task_menu"]))
				task_settings["task_menu"] = {};
			
			if (!task_settings.hasOwnProperty("connection_menu") || !$.isPlainObject(task_settings["connection_menu"]))
				task_settings["connection_menu"] = {};
			
			task_settings["task_menu"]["show_context_menu"] = true;
			task_settings["task_menu"]["show_start_task_menu"] = true;
			task_settings["task_menu"]["show_properties_menu"] = true;
			task_settings["task_menu"]["show_set_label_menu"] = false;
			task_settings["task_menu"]["show_delete_menu"] = false;
			
			task_settings["connection_menu"]["show_context_menu"] = true;
			task_settings["connection_menu"]["show_properties_menu"] = type_id == db_type_id ? false : true;
			task_settings["connection_menu"]["show_set_label_menu"] = false;
			task_settings["connection_menu"]["show_connector_types_menu"] = false;
			task_settings["connection_menu"]["show_overlay_types_menu"] = false;
			task_settings["connection_menu"]["show_delete_menu"] = false;
			
			switch (type_id) {
				case ServerTaskPropertyObj.template_tasks_types_by_tag["dbdriver"]:
					var func = task_settings["callback"]["on_load_task_properties"];
					
					task_settings["callback"]["on_load_task_properties"] = function(properties_html_elm, task_id, task_property_values) {
						if (typeof func == "function")
							func(properties_html_elm, task_id, task_property_values);
						
						ServerTaskPropertyObj.onCheckMigrateDB( properties_html_elm.find(".migrate_db_schema > input")[0] );
					};
					
					break;
				
				case ServerTaskPropertyObj.template_tasks_types_by_tag["dataaccess"]:
				case ServerTaskPropertyObj.template_tasks_types_by_tag["businesslogic"]:
				case ServerTaskPropertyObj.template_tasks_types_by_tag["presentation"]:
					var func = task_settings["callback"]["on_load_task_properties"];
					
					task_settings["callback"]["on_load_task_properties"] = function(properties_html_elm, task_id, task_property_values) {
						if (typeof func == "function")
							func(properties_html_elm, task_id, task_property_values);
						
						ServerTaskPropertyObj.onLoadTemplateFlowTaskProperties(properties_html_elm, task_id, task_property_values);
					};
					
					break;
			}
			
			WF.Property.tasks_settings[type_id] = task_settings;
		});
		
		//prepare html
		var workflow_elm = $("#" + WF.TaskFlow.main_tasks_flow_obj_id).parent();
		
		workflow_elm.find(".tasks_flow #layer_presentations").html("<span class=\"layer_title\">Presentation Layers</span>");
		workflow_elm.find(".tasks_flow #layer_bls").html("<span class=\"layer_title\">Business Logic Layers</span>");
		workflow_elm.find(".tasks_flow #layer_dals").html("<span class=\"layer_title\">Data-Access Layers (SQL)</span>");
		workflow_elm.find(".tasks_flow #layer_dbs").html("<span class=\"layer_title\">DB Layers</span>");
		workflow_elm.find(".tasks_flow #layer_drivers").html("<span class=\"layer_title\">DB Drivers</span>");
		
		//prepare tasks_properties
		var tasks_properties_elm = $("#" + WF.TaskFlow.main_tasks_properties_obj_id);
		
		//prepare task_properties - dbdriver
		var task_html_elm = tasks_properties_elm.find(" > .task_properties_" + this.template_tasks_types_by_tag["dbdriver"] + " > .db_driver_task_html");
		//task_html_elm.children(".type, .encoding").hide(); //2021-08-13: Shows type and encoding bc user may change it.
		
		var html = '<div class="migrate_db_schema">'
				+ '	<input type="checkbox" class="task_property_field" name="migrate_db_schema" value="1" checked onClick="ServerTaskPropertyObj.onCheckMigrateDB(this)"/>'
				+ '	<label>Migrate Data-Base schema.</label>'
				+ '	<span>Note this option will not fully dump your DB schema. It only dumps the main objects like: tables, its\' constraints (primary, foreign and unique keys) and indexes, views, functions, procedures, triggers and events.</span>'
				+ '</div>'
				+ '<div class="remove_deprecated_tables_and_attributes">'
				+ '	<input type="checkbox" class="task_property_field" name="remove_deprecated_tables_and_attributes" value="1" checked/>'
				+ '	<label>Remove deprecated tables and attributes from schema.</label>'
				+ '	<span>Note this option only works if the "Migrate Data-Base schema" option is checked.</span>'
				+ '</div>'
				+ '<div class="migrate_db_data">'
				+ '	<input type="checkbox" class="task_property_field" name="migrate_db_data" value="1" onClick="ServerTaskPropertyObj.onCheckMigrateDB(this)"/>'
				+ '	<label>Migrate Data-Base data.</label>'
				+ '	<span>Note that if the "Migrate Data-Base schema" option is checked, this server\'s DB data will be completed whipped out and all data will be migrated! Otherwise only the new records will be added to this server\'s DB.<br/>Basically the system will create a file dump with the original DB data and then restore it to this DB, which means you need your DB server well configured to received this file, which could have a few hundreds MBs. Eg: In Mysql, you need to have the "max_allowed_packet" and "wait_timeout" configured with a high value.</span>'
				+ '</div>';
		task_html_elm.append(html);
		
		//prepare task_properties - dataaccess
		var task_html_elm = tasks_properties_elm.find(" > .task_properties_" + this.template_tasks_types_by_tag["dataaccess"] + " > .data_access_layer_task_html");
		var files_html = this.getLayerTaskPropertiesFilesHtml(["common", "module"]);
		task_html_elm.append(files_html);
		task_html_elm.children(".type").hide();
		
		//prepare task_properties - businesslogic
		var task_html_elm = tasks_properties_elm.find(" > .task_properties_" + this.template_tasks_types_by_tag["businesslogic"] + " > .businesslogic_layer_task_html");
		var files_html = this.getLayerTaskPropertiesFilesHtml(["common", "module"]);
		task_html_elm.append(files_html);
		
		//prepare task_properties - presentation
		var task_html_elm = tasks_properties_elm.find(" > .task_properties_" + this.template_tasks_types_by_tag["presentation"] + " > .presentation_layer_task_html");
		var files_html = this.getLayerTaskPropertiesFilesHtml(["common"]);
		var default_project_html = '<div class="default_project">'
			+ '	<label>Default Project:</label>'
			+ '	<input type="text" class="task_property_field" name="default_project" value="" />'
			+ '	<i class="icon search" onClick="ServerTaskPropertyObj.chooseTemplateFlowTaskFile(this)" title="Choose file"></i>'
			+ '</div>';
		var cms_wordpress_installations_html = '<div class="wordpress_installations">'
			+ '	<label>WordPress Installations:</label>'
			+ '	<ul>'
			+ '		<li class="no_wordpress_installations">There no wordpress installations...</li>'
			+ '	</ul>'
			+ '</div>';
		task_html_elm.append(default_project_html);
		task_html_elm.append(files_html);
		task_html_elm.append(cms_wordpress_installations_html);
		
		//init flow
		WF.init();
		
		return WF;
	},
	
	onCheckMigrateDB : function(elm) {
		var p = $(elm).parent().parent();
		var migrate_db_schema_checked = p.find(" > .migrate_db_schema > input").is(":checked");
		var migrate_db_data_checked = p.find(" > .migrate_db_data > input").is(":checked");
		var input = p.find(" > .remove_deprecated_tables_and_attributes > input");
		
		if (migrate_db_schema_checked) {
			if (migrate_db_data_checked) 
				input.attr("disabled", "disabled").attr("checked", "checked").prop("checked", true);
			else
				input.removeAttr("disabled");
		}
		else
			input.attr("disabled", "disabled").removeAttr("checked").prop("checked", false);
	},
	
	getTemplateFlowPropertiesToSave : function(template_workflow) {
		var template_properties = {connection : [], task: []};
		var WF = myWFObj.getTaskFlowChart();
		
		//save connections
		if (WF.TaskFlow.connections_properties)
			for (var connection_id in WF.TaskFlow.connections_properties) {
				var connection = WF.TaskFlow.getConnection(connection_id);
				
				if (connection) {
					var source_label = WF.TaskFlow.getTaskLabelByTaskId(connection.sourceId);
					var target_label = WF.TaskFlow.getTaskLabelByTaskId(connection.targetId);
					
					template_properties["connection"].push({
						source_label: source_label,
						target_label: target_label,
						properties: WF.TaskFlow.connections_properties[connection_id]
					});
				}
			}
		
		//save tasks
		var tasks = WF.TaskFlow.getAllTasks();
		
		if (tasks) {
			for (var i = 0, l = tasks.length; i < l; i++) {
				var task = $(tasks[i]);
				var task_label = WF.TaskFlow.getTaskLabel(task);
				
				if (task_label) {
					var task_properties = {};
					var task_id = task.attr("id");
					
					if (WF.TaskFlow.tasks_properties && WF.TaskFlow.tasks_properties.hasOwnProperty(task_id) && $.isPlainObject(WF.TaskFlow.tasks_properties[task_id]))
						for (var k in WF.TaskFlow.tasks_properties[task_id])
							if (k != "exits" || (WF.TaskFlow.tasks_properties[task_id][k] && !$.isEmptyObject(WF.TaskFlow.tasks_properties[task_id][k])))
								task_properties[k] = WF.TaskFlow.tasks_properties[task_id][k];
					
					task_properties["active"] = task.hasClass("active") ? "1" : "0"; //important to be a string bc the xml will load the tasks properties has strings. Must be at the end bc the WF.TaskFlow.tasks_properties[task_id] contains an active property too.
					
					//prepare default layer, this is if layer is start it means is the default layer
					task_properties["start"] = parseInt(task.attr("is_start_task")) > 0 ? "1" : "0"; //important to be a string bc the xml will load the tasks properties has strings.
					
					template_properties["task"].push({
						label: task_label,
						properties: task_properties
					});
				}
			}
		}
		
		return template_properties;
	},
	
	loadTemplateFlowProperties : function(template_workflow) {
		var template_id = template_workflow.parent().closest(".template_properties").attr("template_id");
		
		if ($.isNumeric(template_id)) {
			var WF = myWFObj.getTaskFlowChart();
			var template_properties = this.templates_properties[template_id];
			template_properties = template_properties ? template_properties : {};
			
			//console.log(JSON.parse(JSON.stringify(this.templates_properties[template_id])));
			//console.log(JSON.stringify(this.templates_properties[template_id]));
			
			//load task connections properties
			var connections = WF.TaskFlow.getConnections();
			
			if (connections && template_properties.hasOwnProperty("connection") && template_properties["connection"]) {
				//convert template_properties["connection"] to an associative array
				var tasks_connections = {};
				var tpcs = template_properties["connection"];
				
				if (tpcs) {
					if (!$.isArray(tpcs))
						tpcs = [ tpcs ];
					
					$.each(tpcs, function(idx, connection_props) {
						var source_label = connection_props["source_label"];
						var target_label = connection_props["target_label"];
						tasks_connections[source_label] = {};
						tasks_connections[source_label][target_label] = connection_props["properties"];
					});
				}
				
				//prepare connections
				for (var i = 0, l = connections.length; i < l; i++) {
					var connection = connections[i];
					var source_label = WF.TaskFlow.getTaskLabelByTaskId(connection.sourceId);
					var target_label = WF.TaskFlow.getTaskLabelByTaskId(connection.targetId);
					var connection_properties = tasks_connections.hasOwnProperty(source_label) && $.isPlainObject(tasks_connections[source_label]) && tasks_connections[source_label].hasOwnProperty(target_label) ? tasks_connections[source_label][target_label] : null;
					
					//try to find the connection_properties case insensitive
					if (!connection_properties) {
						var source_label_lower = ("" + source_label).toLowerCase();
						var target_label_lower = ("" + target_label).toLowerCase();
						
						for (var sl in tasks_connections)
							if (("" + sl).toLowerCase() == source_label_lower) {
								if (tasks_connections[sl])
									for (var tl in tasks_connections[sl])
										if (("" + tl).toLowerCase() == target_label_lower) {
											connection_properties = tasks_connections[sl][tl];
											break;
										}
								
								break;
							}
					}
					
					//update connection properties
					if (connection_properties)
						WF.TaskFlow.connections_properties[connection.id] = connection_properties;
				}
			}
			
			//load tasks properties
			var tasks = WF.TaskFlow.getAllTasks();
			
			if (tasks) {
				//convert template_properties["connection"] to an associative array
				var tasks_properties = {};
				var tpts = template_properties["task"];
				
				if (tpts) {
					if (!$.isArray(tpts))
						tpts = [ tpts ];
					
					$.each(tpts, function(idx, task_props) {
						var task_label = task_props["label"];
						tasks_properties[task_label] = task_props["properties"];
					});
				}
				
				//prepare tasks
				for (var i = 0, l = tasks.length; i < l; i++) {
					var task = $(tasks[i]);
					var task_id = task.attr("id");
					var task_label = WF.TaskFlow.getTaskLabel(task);
					var task_props = WF.TaskFlow.tasks_properties[task_id];
					
					task_label = task_label ? ("" + task_label).toLowerCase() : "";
					var task_properties = task_label && tasks_properties.hasOwnProperty(task_label) && $.isPlainObject(tasks_properties[task_label]) ? tasks_properties[task_label] : null;
					
					//overwrite the active status
					//var is_active = task_props && parseInt(task_props["active"]) == 1 || ("" + task_props["active"]).toLowerCase() == "true"; - DEPRECATED.
					var is_active = false; //By default the tasks are inactive
					
					if (task_properties)
						is_active = parseInt(task_properties["active"]) == 1 || ("" + task_properties["active"]).toLowerCase() == "true";
					
					if (is_active)
						task.addClass("active");
					else
						task.removeClass("active");
					
					prepareLayerTaskActiveStatus(task);
					
					//overwrite the start status
					var is_start = parseInt(task.attr("is_start_task")) > 0;
					
					if (task_properties)
						is_start = parseInt(task_properties["start"]) > 0 || ("" + task_properties["start"]).toLowerCase() == "true";
					
					if (is_start)
						task.addClass(WF.TaskFlow.start_task_class_name).attr("is_start_task", 1);
					else
						task.removeClass(WF.TaskFlow.start_task_class_name).removeAttr("is_start_task");
					
					//prepare WF task properties
					if (task_properties) {
						if (!task_props)
							WF.TaskFlow.tasks_properties[task_id] = {};
						
						for (var k in task_properties)
							if (k != "active" && k != "start") 
								WF.TaskFlow.tasks_properties[task_id][k] = task_properties[k];
					}
				}
			}
		}
	},
	
	/* TEMPLATE WORKFLOW - TASK FILES FUNCTIONS */
	
	getLayerTaskPropertiesFilesHtml : function(default_files) {
		if (default_files && !$.isArray(default_files) && !$.isPlainObject(default_files))
			default_files = [ default_files ];
		
		var html = '<div class="files">'
			+ '		<label>Selected Files: <i class="icon add" onClick="ServerTaskPropertyObj.addTemplateFlowTaskFile(this)" title="Add new file"></i></label>'
			+ '		<div class="info">All paths bellow are relative to this Layer\'s path.</div>'
			+ '		<ul>';
		
		if (default_files)
			$.each(default_files, function(idx, file) {
				if (file)
					html += ''
						+ '<li class="default_file">'
						+ '	<input type="checkbox" class="task_property_field" name="files[]" value="' + file + '" /> ' + file
						+ '</li>';
			});
		
		html += '	<li class="no_files">No extra selected files to be copied...</li>'
			+ '		</ul>'
			+ '		<div class="info">To include all files in this Layer, please do not select any files or select one and leave it blank.</div>'
			+ '	</div>';
		
		return html;
	},
	
	onLoadTemplateFlowTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		var files = task_property_values && task_property_values.hasOwnProperty("files") ? task_property_values["files"] : null;
		this.prepareTemplateFlowTaskFilesProperties( properties_html_elm.find(".files"), files);
		
		//only if .wordpress_installation, bc this method gets executed for all layers properties and only the presentation layers will have the .wordpress_installation
		if (properties_html_elm.find(".wordpress_installations")[0]) {
			var wordpress_installations = task_property_values && task_property_values.hasOwnProperty("wordpress_installations") ? task_property_values["wordpress_installations"] : null;
			this.prepareTemplateFlowTaskWordPressInstallationsProperties(properties_html_elm.find(".wordpress_installations"), wordpress_installations, task_id);
		}
	},
	
	prepareTemplateFlowTaskWordPressInstallationsProperties : function(wordpress_installations_elm, wordpress_installations, task_id) {
		//reset to default values
		wordpress_installations_elm.find(" > ul > li:not(.no_wordpress_installations)").remove();
		
		//load template actions
		this.loadTemplateFlowTaskWordPressInstallationsProperties(wordpress_installations_elm, wordpress_installations);
		
		//get available wordpress installations
		if (typeof this.on_get_layer_wordpress_installations_url_callback == "function") {
			var WF = myWFObj.getTaskFlowChart();
			var task_label = WF.TaskFlow.getTaskLabelByTaskId(task_id);
			var url = this.on_get_layer_wordpress_installations_url_callback(task_label);
			
			WF.StatusMessage.showMessage("Loading available wordpress installations...");
			
			$.ajax({
				url : url,
				dataType : "json",
				success : function(data, text_status, jqXHR) {
					WF.StatusMessage.removeLastShownMessage();
					
					if (data && ($.isPlainObject(data) || $.isArray(data))) {
						var ul = wordpress_installations_elm.children("ul");
						var existents = [];
						
						$.each(data, function(name, props) {
							if (name && $.isPlainObject(props) && $.isPlainObject(props["properties"]) && props["properties"]["item_type"] == "wordpress_installation_folder") { //if is a wordpress_installation_folder 
								if (!ul.find("li input[value=" + name + "]")[0]) //if not exists yet or was not loaded before!
									ServerTaskPropertyObj.addTemplateFlowTaskWordPressInstallation(wordpress_installations_elm, {name: name, checked: false});
								
								existents.push(name);
							}
						});
						
						ul.find("li input").each(function(idx, input) {
							input = $(input);
							
							if ($.inArray(input.val(), existents) == -1)
								input.parent().addClass("invalid");
						});
					}
					else
						WF.StatusMessage.showError("There was an error trying to get the wordpress installations. Please try again." + (data ? "<br/>" + data : ""));
				},
				error : function(jqXHR, textStatus, errorThrown) { 
					WF.StatusMessage.removeLastShownMessage();
					
					var msg = jqXHR.responseText ? "<br/>" + jqXHR.responseText : "";
					WF.StatusMessage.showError("There was an error trying to get the wordpress installations. Please try again..." + msg);
				},
			});
		}
	},
	
	loadTemplateFlowTaskWordPressInstallationsProperties : function(wordpress_installations_elm, wordpress_installations) {
		if (wordpress_installations) {
			if (!$.isPlainObject(wordpress_installations) && !$.isArray(wordpress_installations))
				wordpress_installations = [wordpress_installations];
			
			$.each(wordpress_installations, function(idx, name) {
				if (name)
					ServerTaskPropertyObj.addTemplateFlowTaskWordPressInstallation(wordpress_installations_elm, {name: name, checked: true});
			});
		}
	},
	
	addTemplateFlowTaskWordPressInstallation : function(wordpress_installations_elm, props) {
		var ul = wordpress_installations_elm.children("ul");
		var name = props && props.hasOwnProperty("name") && props["name"] != null ? props["name"] : "";
		var checked = props && props.hasOwnProperty("checked") ? props["checked"] : "";
		
		var html = '<li>'
			+ '	<input type="checkbox" class="task_property_field" name="wordpress_installations[]" value="' + name + '"' + (checked ? ' checked' : '') + ' /> ' + name
			+ '</li>';
		
		html = $(html);
		
		ul.append(html);
		ul.children(".no_wordpress_installations").hide();
		
		return html;
	},
	
	prepareTemplateFlowTaskFilesProperties : function(files_elm, files) {
		//reset to default values
		files_elm.find(" > ul > li:not(.no_files):not(.default_file)").remove();
		
		//load template actions
		this.loadTemplateFlowTaskFilesProperties(files_elm, files);
	},
	
	loadTemplateFlowTaskFilesProperties : function(files_elm, files) {
		if (files) {
			var add_icon = files_elm.find(".add")[0];
			
			if (!$.isPlainObject(files) && !$.isArray(files))
				files = [files];
			
			var default_inputs = files_elm.find(" > ul > li.default_file > input");
			var default_files = [];
			$.each(default_inputs, function(idx, input) {
				default_files.push( $(input).val() );
			});
			
			$.each(files, function(idx, file) {
				if (file) {
					var f = file.replace(/[\/]+$/, "");
					
					if ($.inArray(f, default_files) != -1)
						files_elm.find(" > ul > li.default_file > input[value='" + f + "']").attr("checked", "checked").prop("checked", true);
					else if (file)
						ServerTaskPropertyObj.addTemplateFlowTaskFile(add_icon, {file: file});
				}
			});
		}
	},
	
	addTemplateFlowTaskFile : function(elm, props) {
		var files = $(elm).parent().closest(".files");
		var ul = files.children("ul");
		var file = props && props.hasOwnProperty("file") && props["file"] != null ? props["file"] : "";
		
		var html = '<li>'
			+ '	<input type="text" class="task_property_field" name="files[]" value="' + file + '" />'
			+ '	<i class="icon search" onClick="ServerTaskPropertyObj.chooseTemplateFlowTaskFile(this)" title="Choose file"></i>'
			+ '	<i class="icon remove" onClick="ServerTaskPropertyObj.removeTemplateFlowTaskFile(this)" title="Remove this file"></i>'
			+ '</li>';
		
		html = $(html);
		
		ul.append(html);
		ul.children(".no_files").hide();
		
		return html;
	},
	
	removeTemplateFlowTaskFile : function(elm) {
		var li = $(elm).parent().closest("li");
		var ul = li.parent();
		
		li.remove();
		
		if (ul.children(":not(.no_files):not(.default_file)").length == 0)
			ul.children(".no_files").show();
	},
	
	chooseTemplateFlowTaskFile : function(elm) {
		if (typeof this.on_choose_template_flow_layer_file_callback == "function") {
			var WF = myWFObj.getTaskFlowChart();
			var task_id = WF.ContextMenu.getContextMenuTaskId();
			var task_label = WF.TaskFlow.getTaskLabelByTaskId(task_id);
			
			this.on_choose_template_flow_layer_file_callback(elm, task_label);
		}
	},
	
	chooseTemplateFile : function(elm) {
		if (typeof this.on_choose_template_file_callback == "function")
			this.on_choose_template_file_callback(elm);
	},
	
	/* TEMPLATE WORKFLOW - WORKFLOW MENU FUNCTIONS */
	
	prepareTemplateGlobalVarsOrSettings : function(template_properties) {
		template_properties.data("template_global_vars_and_settings", null);
		
		//set global_variables variable
		var template_id = template_properties.attr("template_id");
		
		//prepare template workflow_global_variables
		if (this.templates_properties && this.templates_properties.hasOwnProperty(template_id) && $.isPlainObject(this.templates_properties[template_id]) && this.templates_properties[template_id].hasOwnProperty("global_vars") && this.templates_properties[template_id]["global_vars"]) {
			var global_properties = this.templates_properties[template_id]["global_vars"];
			var vars_name = global_properties["vars_name"];
			var vars_value = global_properties["vars_value"];
			var local_workflow_global_variables = assignObjectRecursively({}, window.workflow_global_variables);
			
			if (vars_name) {
				if (!$.isPlainObject(vars_name) && !$.isArray(vars_name)) {
					vars_name = this.templates_properties[template_id]["global_vars"]["vars_name"] = [vars_name];
					vars_value = this.templates_properties[template_id]["global_vars"]["vars_value"] = [vars_value];
				}
				
				$.each(vars_name, function(idx, var_name) {
					local_workflow_global_variables[var_name] = vars_value[idx];
				});
			}
			
			window.workflow_global_variables = local_workflow_global_variables;
		}
	},
	
	openTemplateGlobalVarsOrSettingsPopup : function(elm, url) {
		var template_properties = $(elm).parent().closest(".template_properties");
		var template_id = template_properties.attr("template_id");
		
		//remove previous existent popup
		var popup = this.TemplatePropertiesGlobalSettingsAndVarsMyFancyPopupObject.getPopup();
		if (popup.hasClass(".server_task_html_template_global_vars_and_settings_popup")) 
			popup.destroyPopup();
		
		//create new popup
		var close_popup_func = function(e) {
			e.preventDefault();
			var popup = ServerTaskPropertyObj.TemplatePropertiesGlobalSettingsAndVarsMyFancyPopupObject.getPopup();
			var iframe = popup.find("iframe");
			var contents = iframe.contents();
			var changed = ServerTaskPropertyObj.checkIfTemplateGlobalVarsOrSettingsWereChanged(contents, template_id);
			var status = true;
			
			if (changed) {
				var iframe_win = iframe[0].contentWindow;
				var main_fields_elm = contents.find(".form_fields, .vars")[0];
				status = typeof iframe_win.MyJSLib == 'undefined' || iframe_win.MyJSLib.FormHandler.formCheck(main_fields_elm);
				
				//console.log("changed");
				ServerTaskPropertyObj.setTemplateGlobalVarsOrSettingsToSaveLater(template_properties, contents);
			}
			
			if (status)
				ServerTaskPropertyObj.TemplatePropertiesGlobalSettingsAndVarsMyFancyPopupObject.hidePopup();
		};
		
		this.TemplatePropertiesGlobalSettingsAndVarsMyFancyPopupObject.init({
			url: url,
			type: "iframe",
			onOpen: function() {
				var popup = ServerTaskPropertyObj.TemplatePropertiesGlobalSettingsAndVarsMyFancyPopupObject.getPopup();
				popup.addClass("server_task_html_template_global_vars_and_settings_popup with_iframe_title");
				
				var close_btn = ServerTaskPropertyObj.TemplatePropertiesGlobalSettingsAndVarsMyFancyPopupObject.getPopupCloseButton();
				close_btn.off("click");
				close_btn.click(close_popup_func);
				
				var overlay = ServerTaskPropertyObj.TemplatePropertiesGlobalSettingsAndVarsMyFancyPopupObject.getOverlay();
				overlay.off("click");
				overlay.click(close_popup_func);
			},
			onIframeOnLoad: function(e) {
				var popup = ServerTaskPropertyObj.TemplatePropertiesGlobalSettingsAndVarsMyFancyPopupObject.getPopup();
				var iframe = popup.find("iframe");
				var iframe_win = iframe[0].contentWindow;
				var contents = iframe.contents();
				
				contents.find("input[type=submit], .top_bar li.save").remove();
				
				contents.find("form").each(function(idx, form) {
					form = $(form);
					form.before( form.contents() );
					form.remove();
				});
				
				//load template global vars or settings
				ServerTaskPropertyObj.loadTemplateGlobalVarsOrSettings(template_properties, contents, template_id);
			},
		});
		
		this.TemplatePropertiesGlobalSettingsAndVarsMyFancyPopupObject.showPopup();
	},
	
	loadTemplateGlobalVarsOrSettings : function(template_properties_elm, iframe_contents, template_id) {
		iframe_contents = $(iframe_contents);
		var fields = iframe_contents.find(".form_fields, .vars");
		var key = fields.hasClass("vars") ? "global_vars" : "global_settings";
		var inputs = fields.find("input, select, textarea");
		//console.log("loadTemplateGlobalVarsOrSettings");
		
		var global_properties = this.templates_properties.hasOwnProperty(template_id) && this.templates_properties[template_id] && this.templates_properties[template_id].hasOwnProperty(key) ? this.templates_properties[template_id][key] : {};
		
		//update global properties with previous if exists
		var global_properties_to_save = this.getTemplateGlobalVarsOrSettingsToSave(template_properties_elm);
		
		if (global_properties_to_save && global_properties_to_save.hasOwnProperty(key))
			global_properties = global_properties_to_save[key];
		
		//show global properties
		if ($.isPlainObject(global_properties) && !$.isEmptyObject(global_properties)) {
			var missing_vars = {};
			var saved_vars_name = null;
			
			//prepare global vars
			if (key == "global_vars") {
				var vars_name = global_properties["vars_name"];
				var vars_value = global_properties["vars_value"];
				
				//load vars
				if (vars_name) {
					if (!$.isPlainObject(vars_name) && !$.isArray(vars_name)) {
						vars_name = this.templates_properties[template_id][key]["vars_name"] = [vars_name];
						vars_value = this.templates_properties[template_id][key]["vars_value"] = [vars_value];
						global_properties = this.templates_properties[template_id][key];
					}
					
					$.each(vars_name, function(idx, var_name) {
						var var_value = vars_value[idx];
						var input = inputs.filter("[value='" + var_name + "']").first();
						
						if (input[0])
							input.parent().closest("tr").find(".var_value input").val(var_value);
						else
							missing_vars[var_name] = var_value;
					});
				}
				
				//check missing vars
				if (!$.isEmptyObject(missing_vars)) {
					var tbody = fields.find("tbody");
					
					for (var var_name in missing_vars) {
						var var_value = missing_vars[var_name];
						
						var length = tbody.children("tr").length;
						iframe_contents.find(".add").first().trigger("click");
						
						if (length + 1 == tbody.children("tr").length) {
							var last = fields.find("tr:last-child").last();
							last.find(".var_name input").val(var_name);
							last.find(".var_value input").val(var_value);
						}
						else 
							alert("Template Global Var '" + var_name + "' cannot be added with value: " + var_value);
					}
				}
			}
			else { //prepare global settings
				//load settings
				for (var var_name in global_properties) {
					var var_value = global_properties[var_name];
					var input = inputs.filter("[name='data[" + var_name + "]']").first();
					
					if (input[0])
						input.val(var_value);
					else
						missing_vars[var_name] = var_value;
				}
				
				//check missing settings
				if (!$.isEmptyObject(missing_vars)) {
					var msg = "Could not add the following saved Template Global Settings:";
					
					for (var var_name in missing_vars) {
						var var_value = missing_vars[var_name];
						
						msg += "\n- " + var_name + ": " + var_value;
					}
					
					alert(msg);
				}
			}
		}
		
		if (key == "global_vars") {
			var vars_name = global_properties["vars_name"];
			var vars_name_arr = [];
			
			//convert vars_name into array in case be an object
			if (vars_name && ($.isPlainObject(vars_name) || $.isArray(vars_name))) 
				$.each(vars_name, function(idx, var_name) {
					vars_name_arr.push(var_name);
				});
			
			//preare UI and default vars css
			iframe_contents.find(".global_vars").css({width: "auto", "min-width": "500px"});
			
			fields.find("tr > .var_name input").each(function(idx, input) {
				input = $(input);
				var var_name = input.val();
				
				if ($.inArray(var_name, vars_name_arr) == -1) {
					input.css({opacity: .5}).attr("disabled", "disabled");
					var tr = input.parent().closest("tr");
					tr.find(".var_value input").css({opacity: .5}).attr("disabled", "disabled");
					
					var html = '<input type="checkbox" title="Activate default global variable"/>';
					html = $(html);
					html.css("width", "auto");
					html.click(function() {
						var input_elm = $(this);
						
						if (input_elm.is(":checked"))
							input_elm.parent().closest("tr").find(".var_name input, .var_value input").css({opacity: 1}).removeAttr("disabled");
						else
							input_elm.parent().closest("tr").find(".var_name input, .var_value input").css({opacity: .5}).attr("disabled", "disabled");
					});
					
					tr.find(".buttons").append(html).append('<span title="This Global Var contains the default value!" onClick="alert(this.getAttribute(\'title\'));"> default</span>');
				}
			});
		}
		else {	
			//set default vars css
			fields.find(".form_field input, .form_field select").each(function(idx, input) {
				input = $(input);
				var var_name = input.attr("name"); //get data[...]
				var_name = var_name.substr("data[".length); //remove data[
				var_name = var_name.substr(0, var_name.length - 1); //remove ]
				
				if (!global_properties || !global_properties.hasOwnProperty(var_name)) {
					input.css("background-color", "#eeeeeeaa");
					input.parent().append('<span title="This Global Var contains the default value!" onClick="alert(this.getAttribute(\'title\'));"> default</span>');
				}
			});
		}
	},
	
	checkIfTemplateGlobalVarsOrSettingsWereChanged : function(iframe_contents, template_id) {
		var global_properties = this.getTemplateGlobalVarsOrSettingsToSaveLater(iframe_contents);
		var key = $(iframe_contents).find(".vars").length > 0 ? "global_vars" : "global_settings";
		
		if (!this.templates_properties.hasOwnProperty(template_id) || !this.templates_properties[template_id].hasOwnProperty(key) || $.isEmptyObject(this.templates_properties[template_id][key]))
			return !$.isEmptyObject(global_properties);
		
		if (key == "global_vars") {
			//replace null values with "" and convert to object instead of array, otherwise the comparison bellow won't work.
			for (var type in this.templates_properties[template_id][key]) { //type is vars_name or vars_value
				var items = this.templates_properties[template_id][key][type]; 
				var new_items = {};
				
				if (items)
					for (var n in items) {
						var v = items[n];
						new_items[n] = v == null ? "" : v;
					}
				
				this.templates_properties[template_id][key][type] = new_items;
			}
		}
		else {
			//replace null values with "", otherwise the comparison bellow won't work.
			for (var n in this.templates_properties[template_id][key]) {
				var v = this.templates_properties[template_id][key][n];
				
				if (v == null)
					this.templates_properties[template_id][key][n] = "";
			}
		}
		
		//console.log(this.templates_properties[template_id][key]);
		//console.log(global_properties);
		return JSON.stringify(this.templates_properties[template_id][key]) != JSON.stringify(global_properties);
	},
	
	getTemplateGlobalVarsOrSettingsToSave : function(template_properties) {
		return template_properties.data("template_global_vars_and_settings");
	},
	
	setTemplateGlobalVarsOrSettingsToSaveLater : function(template_properties, iframe_contents) {
		var global_properties = this.getTemplateGlobalVarsOrSettingsToSaveLater(iframe_contents);
		var key = $(iframe_contents).find(".vars").length > 0 ? "global_vars" : "global_settings";
		
		var template_global_vars_and_settings = template_properties.data("template_global_vars_and_settings");
		
		if (!$.isPlainObject(template_global_vars_and_settings))
			template_global_vars_and_settings = {};
		
		template_global_vars_and_settings[key] = global_properties;
		
		template_properties.data("template_global_vars_and_settings", template_global_vars_and_settings);
	},
	
	getTemplateGlobalVarsOrSettingsToSaveLater : function(iframe_contents) {
		var WF = myWFObj.getTaskFlowChart();
		var global_properties = {};
		var fields = $(iframe_contents).find(".form_fields, .vars");
		fields.find("input:not([disabled]), select:not([disabled]), textarea:not([disabled])").addClass("task_property_field");
		var query_string = WF.Property.getPropertiesQueryStringFromHtmlElm(fields, "task_property_field");
		
		try {
			parse_str(query_string, global_properties);
		}
		catch(e) {
			//alert(e);
			if (console && console.log) {
				console.log(e);
				console.log("Error executing parse_str function in ServerTaskPropertyObj.saveGlobalVarsOrSettings with query_string: " + query_string);
			}
		}
		
		var key = fields.hasClass("vars") ? "global_vars" : "global_settings";
		return key == "global_vars" ? global_properties : global_properties["data"];
	},
	
	/* TEMPLATE ACTIONS FUNCTIONS */
	
	prepareTemplateActions : function(template_actions) {
		//reset to default values
		template_actions.find(" > .server_installation_folder_path > input").val("");
		template_actions.find(" > .server_installation_url > input").val("");
		template_actions.find(" > .available_actions > select").val("");
		template_actions.find(" > .actions > .action").remove();
		
		//load template actions
		this.loadTemplateActionsProperties(template_actions);
	},
	
	addUserTemplateAction : function(elm, props) {
		var action = $(elm).parent().closest(".available_actions").children("select").val();
		
		if (action) {
			var props = {};
			props[action] = {};
			
			return this.addTemplateAction(elm, props);
		}
	},
	
	addTemplateAction : function(elm, props) {
		var action = null;
		
		if (props && $.isPlainObject(props))
			for (action in props)
				break;
		
		if (action) {
			var available_actions = $(elm).parent().closest(".available_actions");
			var ul = available_actions.parent().children(".actions");
			var action_label = available_actions.find("select option[value='" + action + "']").text();
			action_label = action_label ? action_label : stringToUCWords( action.replace(/_/g, " ") );
			
			var action_id = props && props.hasOwnProperty("action_id") ? props["action_id"] : getListNewIndex(ul);
			
			var html = '<li action_id="' + action_id + '" class="action action_' + action + '">'
				+ '	<input type="hidden" class="task_property_field" name="actions[' + action_id + '][' + action + '][active]" value="1" />'
				+ '	<label>' + action_label + '</label>'
				+ '	<i class="icon up" onClick="ServerTaskPropertyObj.moveUpTemplateAction(this)" title="Move up this action"></i>'
				+ '	<i class="icon down" onClick="ServerTaskPropertyObj.moveDownTemplateAction(this)" title="Move down this action"></i>'
				+ '	<i class="icon remove" onClick="ServerTaskPropertyObj.removeTemplateAction(this)" title="Remove this action"></i>';
			
			switch (action) {
				case "run_test_units":
					html += '	<i class="icon add" onClick="ServerTaskPropertyObj.addTemplateActionTestUnit(this)" title="Add new test-units to be validaded"></i></label>'
						+ '	<div class="info">The selected test-units bellow will run when a deployment with this template gets executed. <br/>Only if the test-units are validaded, the deployment continues executing, which means this action should be the first one to run! Otherwise the deployment may not be executed correctly...</div>'
						+ '	<ol>'
						+ '		<li class="no_test_units">No selected Test-Units to run!</li>'
						+ '	</ol>';
					break;
					
				case "migrate_dbs":
					html += '	<div class="info">All the Data-Bases correspondent to the active DB-Drivers in the Workflow, will be migrated!</div>';
					break;
					
				case "copy_layers":
					html += '	<div class="sysadmin">'
						+ '		<input type="checkbox" class="task_property_field" name="actions[' + action_id + '][copy_layers][sysadmin]" value="1" onClick="ServerTaskPropertyObj.onSelectSysAdmin(this)" ' + (this.allowed_sysadmin_migration ? 'checked' : 'disabled') + ' />'
						+ '		<label>Include SysAdmin Panel</label>'
						+ '	</div>'
						+ '	<div class="vendor">'
						+ '		<input type="checkbox" class="task_property_field" name="actions[' + action_id + '][copy_layers][vendor]" value="1" checked />'
						+ '		<label>Include Vendor Folder</label>'
						+ '	</div>'
						+ '	<div class="dao">'
						+ '		<input type="checkbox" class="task_property_field" name="actions[' + action_id + '][copy_layers][dao]" value="1" checked />'
						+ '		<label>Include DAO Folder</label>'
						+ '	</div>'
						+ '	<div class="modules">'
						+ '		<input type="checkbox" class="task_property_field" name="actions[' + action_id + '][copy_layers][modules]" value="1" checked />'
						+ '		<label>Include Modules</label>'
						+ '	</div>';
					
					if (this.show_php_obfuscation_option)
						html += ''	
						+ '	<div class="obfuscate_proprietary_php_files">'
						+ '		<input type="checkbox" class="task_property_field" name="actions[' + action_id + '][copy_layers][obfuscate_proprietary_php_files]" value="1" />'
						+ '		<label>Obfuscate Proprietary PHP Files</label>'
						+ '	</div>';
						
					if (this.show_js_obfuscation_option)	
						html += ''	
						+ '	<div class="obfuscate_proprietary_js_files">'
						+ '		<input type="checkbox" class="task_property_field" name="actions[' + action_id + '][copy_layers][obfuscate_proprietary_js_files]" value="1" onClick="ServerTaskPropertyObj.onSelectJSObfuscation(this)" />'
						+ '		<label>Obfuscate Proprietary JS Files</label>'
						+ '	</div>';
					
					html += ''
						+ '	<div class="create_licence">'
						+ '		<input type="checkbox" class="task_property_field" name="actions[' + action_id + '][copy_layers][create_licence]" value="1" onClick="ServerTaskPropertyObj.onSelectCreateLicence(this)" />'
						+ '		<label>Create Licence</label>'
						+ '		<ul style="display:none">';
					
					if (this.projects_max_expiration_date)
						html += ''
						+ '			<li class="projects_expiration_date">'
						+ '				<label>Projects Expiration Date: </label>'
						+ '				<input type="text" class="task_property_field" name="actions[' + action_id + '][copy_layers][projects_expiration_date]" value="" placeHolder="YYYY-mm-dd' + (this.projects_max_expiration_date == "-1" ? ' or -1' : '') + '" />'
						+ '				<div class="info">' + (this.projects_max_expiration_date == "-1" ? 'If empty value, the system will replace this with "-1".' : 'If empty value, the system will replace this with the default date "' + this.projects_max_expiration_date + '". If not empty value, it cannot exceed the default date.') + '</div>'
						+ '			</li>';
					
					if (this.sysadmin_max_expiration_date)
						html += ''
						+ '			<li class="sysadmin_expiration_date">'
						+ '				<label>SysAdmin Panel Expiration Date: </label>'
						+ '				<input type="text" class="task_property_field" name="actions[' + action_id + '][copy_layers][sysadmin_expiration_date]" value="" placeHolder="YYYY-mm-dd" />'
						+ '				<div class="info">If empty value, the system will replace this for a default expiration date of +30 days trial... The maximum date allowed is: "' + this.sysadmin_max_expiration_date + '"</div>'
						+ '			</li>';
					
					if ($.isNumeric(this.projects_max_num)) //if not numeric it means the system was hacked, so we don't show this option.
						html += ''
						+ '			<li class="projects_maximum_number">'
						+ '				<label>Maximum Projects Number Allowed: </label>'
						+ '				<input type="number" class="task_property_field" name="actions[' + action_id + '][copy_layers][projects_maximum_number]" value="" min="-1" ' + (parseInt(this.projects_max_num) > -1 ? 'max="' + this.projects_max_num + '"' : '') + ' />'
						+ '			</li>';
					
					if ($.isNumeric(this.users_max_num)) //if not numeric it means the system was hacked, so we don't show this option.
						html += ''
						+ '			<li class="users_maximum_number">'
						+ '				<label>Maximum Developer-Users Number Allowed: </label>'
						+ '				<input type="number" class="task_property_field" name="actions[' + action_id + '][copy_layers][users_maximum_number]" value="" min="-1" ' + (parseInt(this.users_max_num) > -1 ? 'max="' + this.users_max_num + '"' : '') + ' />'
						+ '			</li>';
					
					if ($.isNumeric(this.end_users_max_num)) //if not numeric it means the system was hacked, so we don't show this option.
						html += ''
						+ '			<li class="end_users_maximum_number">'
						+ '				<label>Maximum End-Users Number Allowed: </label>'
						+ '				<input type="number" class="task_property_field" name="actions[' + action_id + '][copy_layers][end_users_maximum_number]" value="" min="-1" ' + (parseInt(this.end_users_max_num) > -1 ? 'max="' + this.end_users_max_num + '"' : '') + ' />'
						+ '			</li>';
					
					if ($.isNumeric(this.actions_max_num)) //if not numeric it means the system was hacked, so we don't show this option.
						html += ''
						+ '			<li class="actions_maximum_number">'
						+ '				<label>Maximum Actions Number Allowed: </label>'
						+ '				<input type="number" class="task_property_field" name="actions[' + action_id + '][copy_layers][actions_maximum_number]" value="" min="-1" ' + (parseInt(this.actions_max_num) > -1 ? 'max="' + this.actions_max_num + '"' : '') + ' />'
						+ '				<div class="info">An action is a "save" of a file!</div>'
						+ '			</li>';
					
					html += ''		
						+ '			<li class="allowed_paths">'
						+ '				<label>Allowed Paths: </label>'
						+ '				<input type="text" class="task_property_field" name="actions[' + action_id + '][copy_layers][allowed_paths]" value="' + this.allowed_paths + '" />'
						+ '				<div class="info">Paths must be absolute and splitted by comma!' + (this.allowed_paths ? ' Note that only the following paths are valid: ' + this.allowed_paths : '') + '</div>'
						+ '			</li>'
						+ '			<li class="keys_type">'
						+ '				<label>Keys Type: </label>'
						+ '				<select class="task_property_field" name="actions[' + action_id + '][copy_layers][keys_type]" onChange="ServerTaskPropertyObj.onTemplateCreateLicenceKeysTypeChange(this)">'
						+ '					<option value="key_files">Files with SSH Keys</option>'
						+ '					<option value="key_strings">SSH Keys in string</option>'
						+ '				</select>'
						+ '			</li>'
						+ '			<li class="private_key">'
						+ '				<label>Your Private Key: </label>'
						+ '				<textarea class="task_property_field" name="actions[' + action_id + '][copy_layers][private_key]" placeHolder="private key to create licence"></textarea>'
						+ '			</li>'
						+ '			<li class="public_key">'
						+ '				<label>Your Public Key: </label>'
						+ '				<textarea class="task_property_field" name="actions[' + action_id + '][copy_layers][public_key]" placeHolder="public key to create licence"></textarea>'
						+ '			</li>'
						+ '			<li class="private_key_file">'
						+ '				<label>Your Private Key File Path: </label>'
						+ '				<input type="text" class="task_property_field" name="actions[' + action_id + '][copy_layers][private_key_file]" value="" />'
						+ '				<div class="info">This path must be the relative path to CMS_PATH where your private .pem file is!</div>'
						+ '			</li>'
						+ '			<li class="public_key_file">'
						+ '				<label>Your Public Key File Path: </label>'
						+ '				<input type="text" class="task_property_field" name="actions[' + action_id + '][copy_layers][public_key_file]" value="" />'
						+ '				<div class="info">This path must be the relative path to CMS_PATH where your public .pem file is!</div>'
						+ '			</li>'
						+ '			<li class="passphrase">'
						+ '				<label>Your Passphrase: </label>'
						+ '				<input type="text" class="task_property_field" name="actions[' + action_id + '][copy_layers][passphrase]" value="" />'
						+ '				<div class="info">Enter some text to be used as the passphrase to encode the licence.</div>'
						+ '			</li>'
						+ '		</ul>'
						+ '	</div>'
						+ '	<div class="allowed_domains">'
						+ '		<label>Allowed Domains: </label>'
						+ '		<input type="text" class="task_property_field" name="actions[' + action_id + '][copy_layers][allowed_domains]" value="' + this.allowed_domains + '" />'
						+ '		<div class="info">Domains must be splitted by comma!' + (this.allowed_domains ? ' Note that only the following domains are valid: ' + this.allowed_domains : '') + '</div>'
						+ '	</div>'
						+ '	<div class="check_allowed_domains_port"' + (this.check_allowed_domains_port ? ' style="display:none"' : '') + '>'
						+ '		<label>Check Allowed Domains Port: </label>'
						+ '		<input type="checkbox" class="task_property_field" name="actions[' + action_id + '][copy_layers][check_allowed_domains_port]" value="1"' + (this.check_allowed_domains_port ? ' checked' : '') + ' />'
						+ '		<span class="info">Check this box to validate the domain port too.</span>'
						+ '	</div>';
					break;
				
				case "copy_files":
					html += '	<div class="server_relative_folder_path">'
						+ '		<label>Server Relative Folder Path:</label>'
						+ '		<input class="task_property_field" name="actions[' + action_id + '][copy_files][server_relative_folder_path]" />'
						+ '		<div class="info">Path to a folder in the server where the selected files will be copied. This path is relative to the "Server Installation" Folder.</div>'
						+ '	</div>'
						+ '	<div class="files">'
						+ '		<label>Selected Files: <i class="icon add" onClick="ServerTaskPropertyObj.addTemplateActionFile(this)" title="Add new file to be copied"></i></label>'
						+ '		<div class="info">All paths bellow are relative to the CMS_PATH of this framework.</div>'
						+ '		<ul>'
						+ '			<li class="no_files">No selected files to be copied...</li>'
						+ '		</ul>'
						+ '	</div>';
					break;
				
				case "execute_shell_cmds":
					html += '	<textarea class="task_property_field" name="actions[' + action_id + '][execute_shell_cmds][cmds]" placeHolder="Write here your shell script..."></textarea>';
					break;
			}
			
			html += '</li>';
			
			html = $(html);
			
			ul.append(html);
			ul.children(".no_actions").hide();
			
			//load props
			this.loadTemplateActionProperties(html, props);
			
			return html;
		}
		
		return null;
	},
	
	removeTemplateAction : function(elm) {
		var li = $(elm).parent().closest("li");
		var ul = li.parent();
		
		li.remove();
		
		if (ul.children(":not(.no_actions)").length == 0)
			ul.children(".no_actions").show();
	},
	
	moveUpTemplateAction : function(elm) {
		var item = $(elm).parent().closest("li");
		var prev_item = item.prev(":not(.no_actions)");
		
		if (prev_item[0]) {
			item.parent()[0].insertBefore(item[0], prev_item[0]);
			
			switchListChildrenIndexes(item.parent(), item, prev_item);
			
			var aux = item.attr("action_id");
			item.attr("action_id", prev_item.attr("action_id"));
			prev_item.attr("action_id", aux);
		}
	},
	
	moveDownTemplateAction : function(elm) {
		var item = $(elm).parent().closest("li");
		var next_item = item.next();
		
		if (next_item[0]) {
			item.parent()[0].insertBefore(next_item[0], item[0]);
			
			switchListChildrenIndexes(item.parent(), item, next_item);
			
			var aux = item.attr("action_id");
			item.attr("action_id", next_item.attr("action_id"));
			next_item.attr("action_id", aux);
		}
	},
	
	getTemplateActionsPropertiesToSave : function(template_actions) {
		template_actions.find(" > .server_installation_folder_path > input").addClass("task_property_field");
		template_actions.find(" > .server_installation_url > input").addClass("task_property_field");
		
		var WF = myWFObj.getTaskFlowChart();
		var template_properties = {};
		var query_string = WF.Property.getPropertiesQueryStringFromHtmlElm(template_actions, "task_property_field");
		
		try {
			parse_str(query_string, template_properties);
		}
		catch(e) {
			//alert(e);
			if (console && console.log) {
				console.log(e);
				console.log("Error executing parse_str function in ServerTaskPropertyObj.getTemplateActionsPropertiesToSave with query_string: " + query_string);
			}
		}
		
		template_actions.find(" > .server_installation_folder_path > input").removeClass("task_property_field");
		template_actions.find(" > .server_installation_url > input").removeClass("task_property_field");
		
		return template_properties;
	},
	
	loadTemplateActionsProperties : function(template_actions) {
		var template_id = template_actions.parent().closest(".template_properties").attr("template_id");
		
		if ($.isNumeric(template_id)) {
			var template_properties = this.templates_properties[template_id];
			
			if (template_properties) {
				var server_installation_folder_path = template_properties.hasOwnProperty("server_installation_folder_path") ? template_properties["server_installation_folder_path"] : "";
				var server_installation_url = template_properties.hasOwnProperty("server_installation_url") ? template_properties["server_installation_url"] : "";
				var actions = template_properties.hasOwnProperty("actions") ? template_properties["actions"] : null;
				
				template_actions.find(" > .server_installation_folder_path > input").val(server_installation_folder_path);
				template_actions.find(" > .server_installation_url > input").val(server_installation_url);
				
				var add_icon = template_actions.find(" > .available_actions > .add")[0];
				
				if (actions) {
					if ($.isPlainObject(actions) && !$.isArray(actions) && !checkIfValueIsAssociativeNumericArray(actions))
						actions = [actions];
					
					$.each(actions, function(idx, action) {
						ServerTaskPropertyObj.addTemplateAction(add_icon, action);
					});
				}
			}
		}
	},
	
	loadTemplateActionProperties : function(template_action, props) {
		if (props && $.isPlainObject(props))
			for (var action in props) {
				var action_props = props[action];
				
				if (action_props && $.isPlainObject(action_props)) {
					switch (action) {
						case "run_test_units":
							var files = action_props.hasOwnProperty("files") ? action_props["files"] : null;
							
							if (files) {
								var add_icon = template_action.find(".add")[0];
								
								if (!$.isPlainObject(files) && !$.isArray(files))
									files = [files];
								
								$.each(files, function(idx, file) {
									ServerTaskPropertyObj.addTemplateActionTestUnit(add_icon, {file: file});
								});
							}
							
							break;
						
						case "copy_layers":
							var is_sysadmin = action_props.hasOwnProperty("sysadmin") ? parseInt(action_props["sysadmin"]) == 1 || ("" + action_props["sysadmin"]).toLowerCase() == "true" : false;
							var is_vendor = action_props.hasOwnProperty("vendor") ? parseInt(action_props["vendor"]) == 1 || ("" + action_props["vendor"]).toLowerCase() == "true" : true;
							var is_dao = action_props.hasOwnProperty("dao") ? parseInt(action_props["dao"]) == 1 || ("" + action_props["dao"]).toLowerCase() == "true" : true;
							var is_modules = action_props.hasOwnProperty("modules") ? parseInt(action_props["modules"]) == 1 || ("" + action_props["modules"]).toLowerCase() == "true" : true;
							var obfuscate_proprietary_php_files = action_props.hasOwnProperty("obfuscate_proprietary_php_files") ? parseInt(action_props["obfuscate_proprietary_php_files"]) == 1 || ("" + action_props["obfuscate_proprietary_php_files"]).toLowerCase() == "true" : false;
							var obfuscate_proprietary_js_files = action_props.hasOwnProperty("obfuscate_proprietary_js_files") ? parseInt(action_props["obfuscate_proprietary_js_files"]) == 1 || ("" + action_props["obfuscate_proprietary_js_files"]).toLowerCase() == "true" : false;
							var allowed_domains = action_props.hasOwnProperty("allowed_domains") ? action_props["allowed_domains"] : "";
							var check_allowed_domains_port = action_props.hasOwnProperty("check_allowed_domains_port") ? parseInt(action_props["check_allowed_domains_port"]) == 1 || ("" + action_props["check_allowed_domains_port"]).toLowerCase() == "true" : false;
							var create_licence = action_props.hasOwnProperty("create_licence") ? parseInt(action_props["create_licence"]) == 1 || ("" + action_props["create_licence"]).toLowerCase() == "true" : false;
							var projects_expiration_date = action_props.hasOwnProperty("projects_expiration_date") ? action_props["projects_expiration_date"] : "";
							var sysadmin_expiration_date = action_props.hasOwnProperty("sysadmin_expiration_date") ? action_props["sysadmin_expiration_date"] : "";
							var projects_maximum_number = action_props.hasOwnProperty("projects_maximum_number") ? action_props["projects_maximum_number"] : "";
							var users_maximum_number = action_props.hasOwnProperty("users_maximum_number") ? action_props["users_maximum_number"] : "";
							var end_users_maximum_number = action_props.hasOwnProperty("end_users_maximum_number") ? action_props["end_users_maximum_number"] : "";
							var actions_maximum_number = action_props.hasOwnProperty("actions_maximum_number") ? action_props["actions_maximum_number"] : "";
							var allowed_paths = action_props.hasOwnProperty("allowed_paths") ? action_props["allowed_paths"] : "";
							var keys_type = action_props.hasOwnProperty("keys_type") ? action_props["keys_type"] : "";
							var private_key = action_props.hasOwnProperty("private_key") ? action_props["private_key"] : "";
							var public_key = action_props.hasOwnProperty("public_key") ? action_props["public_key"] : "";
							var private_key_file = action_props.hasOwnProperty("private_key_file") ? action_props["private_key_file"] : "";
							var public_key_file = action_props.hasOwnProperty("public_key_file") ? action_props["public_key_file"] : "";
							var passphrase = action_props.hasOwnProperty("passphrase") ? action_props["passphrase"] : "";
							
							var is_sysadmin_input = template_action.find(".sysadmin > input");
							var create_licence_input = template_action.find(".create_licence > input");
							var obfuscate_proprietary_js_files_input = template_action.find(".obfuscate_proprietary_js_files > input");
							var check_allowed_domains_port_input = template_action.find(".check_allowed_domains_port > input");
							
							if (is_sysadmin)
								is_sysadmin_input.attr("checked", "checked").prop("checked", true);
							else
								is_sysadmin_input.removeAttr("checked").prop("checked", false);
							
							if (is_vendor)
								template_action.find(".vendor > input").attr("checked", "checked").prop("checked", true);
							else
								template_action.find(".vendor > input").removeAttr("checked").prop("checked", false);
							
							if (is_dao)
								template_action.find(".dao > input").attr("checked", "checked").prop("checked", true);
							else
								template_action.find(".dao > input").removeAttr("checked").prop("checked", false);
							
							if (is_modules)
								template_action.find(".modules > input").attr("checked", "checked").prop("checked", true);
							else
								template_action.find(".modules > input").removeAttr("checked").prop("checked", false);
							
							if (obfuscate_proprietary_php_files)
								template_action.find(".obfuscate_proprietary_php_files > input").attr("checked", "checked").prop("checked", true);
							else
								template_action.find(".obfuscate_proprietary_php_files > input").removeAttr("checked").prop("checked", false);
							
							if (obfuscate_proprietary_js_files)
								obfuscate_proprietary_js_files_input.attr("checked", "checked").prop("checked", true);
							else
								obfuscate_proprietary_js_files_input.removeAttr("checked").prop("checked", false);
							
							template_action.find(".allowed_domains input").val(allowed_domains);
							
							if (check_allowed_domains_port)
								check_allowed_domains_port_input.attr("checked", "checked").prop("checked", true);
							else
								check_allowed_domains_port_input.removeAttr("checked").prop("checked", false);
							
							if (create_licence)
								create_licence_input.attr("checked", "checked").prop("checked", true);
							else
								create_licence_input.removeAttr("checked").prop("checked", false);
							
							template_action.find(".create_licence .projects_expiration_date input").val(projects_expiration_date);
							template_action.find(".create_licence .sysadmin_expiration_date input").val(sysadmin_expiration_date);
							template_action.find(".create_licence .projects_maximum_number input").val(projects_maximum_number);
							template_action.find(".create_licence .users_maximum_number input").val(users_maximum_number);
							template_action.find(".create_licence .end_users_maximum_number input").val(end_users_maximum_number);
							template_action.find(".create_licence .actions_maximum_number input").val(actions_maximum_number);
							template_action.find(".create_licence .allowed_paths input").val(allowed_paths);
							template_action.find(".create_licence .keys_type select").val(keys_type);
							template_action.find(".create_licence .private_key textarea").val(private_key);
							template_action.find(".create_licence .public_key textarea").val(public_key);
							template_action.find(".create_licence .private_key_file input").val(private_key_file);
							template_action.find(".create_licence .public_key_file input").val(public_key_file);
							template_action.find(".create_licence .passphrase input").val(passphrase);
							
							ServerTaskPropertyObj.onSelectSysAdmin(is_sysadmin_input[0]);
							ServerTaskPropertyObj.onSelectJSObfuscation(obfuscate_proprietary_js_files_input[0]);
							ServerTaskPropertyObj.onSelectCreateLicence(create_licence_input[0]);
							
							break;
						
						case "copy_files":
							var server_relative_folder_path = action_props.hasOwnProperty("server_relative_folder_path") ? action_props["server_relative_folder_path"] : "";
							template_action.find(".server_relative_folder_path > input").val(server_relative_folder_path);
							
							var files = action_props.hasOwnProperty("files") ? action_props["files"] : null;
							
							if (files) {
								var add_icon = template_action.find(".files .add")[0];
								
								if (!$.isPlainObject(files) && !$.isArray(files))
									files = [files];
								
								$.each(files, function(idx, file) {
									ServerTaskPropertyObj.addTemplateActionFile(add_icon, {file: file});
								});
							}
							
							break;
						
						case "execute_shell_cmds":
							var cmds = action_props.hasOwnProperty("cmds") ? action_props["cmds"] : "";
							template_action.find("textarea").val(cmds);
							
							break;
					}
				}
			}
	},
	
	/* TEMPLATE ACTIONS - COPY LAYERS FUNCTIONS */
	
	onSelectJSObfuscation : function(elm) {
		elm = $(elm);
		var p = elm.parent().parent();
		var show_allowed_domains = elm.is(":checked") || p.find(" > .create_licence > input").is(":checked");
		
		if (show_allowed_domains)
			p.children(".allowed_domains, .check_allowed_domains_port").show();
		else
			p.children(".allowed_domains, .check_allowed_domains_port").hide();
	},
	
	onSelectCreateLicence : function(elm) {
		elm = $(elm);
		var p = elm.parent();
		var ul = p.children("ul");
		
		if (elm.is(":checked")) {
			ul.show();
			var sysadmin_expiration_date = ul.find(".sysadmin_expiration_date");
			
			if (ul.parent().closest(".action.action_copy_layers").find(".sysadmin > input").is(":checked"))
				sysadmin_expiration_date.show();
			else
				sysadmin_expiration_date.hide();
		}
		else
			ul.hide();
		
		this.onTemplateCreateLicenceKeysTypeChange( ul.find(".keys_type select")[0] );
		this.onSelectJSObfuscation( p.parent().find(" > .obfuscate_proprietary_js_files > input")[0] );
	},
	
	onTemplateCreateLicenceKeysTypeChange : function(elm) {
		elm = $(elm);
		var type = elm.val();
		var p = elm.parent().parent();
		
		switch (type) {
			case "key_files":
				p.children(".private_key_file, .public_key_file").show();
				p.children(".private_key, .public_key").hide();
				break;
			case "key_strings":
				p.children(".private_key, .public_key").show();
				p.children(".private_key_file, .public_key_file").hide();
				break;
		}
	},
	
	onSelectSysAdmin : function(elm) {
		elm = $(elm);
		var sysadmin_expiration_date = elm.parent().parent().find(" > .create_licence .sysadmin_expiration_date");
		
		if (elm.is(":checked"))
			sysadmin_expiration_date.show();
		else
			 sysadmin_expiration_date.hide();
	},
	
	/* TEMPLATE ACTIONS - COPY FILES FUNCTIONS */
	
	addTemplateActionFile : function(elm, props) {
		var files = $(elm).parent().closest(".files");
		var ul = files.children("ul");
		
		var file = props && props.hasOwnProperty("file") && props["file"] != null ? props["file"] : "";
		var action_id = ul.parent().closest(".action").attr("action_id");
		
		var html = '<li>'
			+ '	<input type="text" class="task_property_field" name="actions[' + action_id + '][copy_files][files][]" value="' + file + '" />'
			+ '	<i class="icon search" onclick="ServerTaskPropertyObj.chooseTemplateFile(this)" title="Choose file"></i>'
			+ '	<i class="icon remove" onClick="ServerTaskPropertyObj.removeTemplateActionFile(this)" title="Remove this file"></i>'
			+ '</li>';
		
		html = $(html);
		
		ul.append(html);
		ul.children(".no_files").hide();
		
		return html;
	},
	
	removeTemplateActionFile : function(elm) {
		var li = $(elm).parent().closest("li");
		var ul = li.parent();
		
		li.remove();
		
		if (ul.children(":not(.no_files)").length == 0)
			ul.children(".no_files").show();
	},
	
	/* TEMPLATE ACTIONS - TEST UNITS FUNCTIONS */
	
	addTemplateActionTestUnit : function(elm, props) {
		var li = $(elm).parent().closest("li");
		var ul = li.children("ol");
		
		var file = props && props.hasOwnProperty("file") && props["file"] != null ? props["file"] : "";
		var action_id = li.attr("action_id");
		
		var html = '<li>'
			+ '	<input type="text" class="task_property_field" name="actions[' + action_id + '][run_test_units][files][]" value="' + file + '" />'
			+ '	<i class="icon search" onClick="ServerTaskPropertyObj.chooseTemplateActionTestUnit(this)" title="Choose file"></i>'
			+ '	<i class="icon up" onClick="ServerTaskPropertyObj.moveUpTemplateActionTestUnit(this)" title="Move up this test-unit"></i>'
			+ '	<i class="icon down" onClick="ServerTaskPropertyObj.moveDownTemplateActionTestUnit(this)" title="Move down this test-unit"></i>'
			+ '	<i class="icon remove" onClick="ServerTaskPropertyObj.removeTemplateActionTestUnit(this)" title="Remove this file"></i>'
			+ '</li>';
		
		html = $(html);
		
		ul.append(html);
		ul.children(".no_test_units").hide();
		
		return html;
	},
	
	removeTemplateActionTestUnit : function(elm) {
		var li = $(elm).parent().closest("li");
		var ul = li.parent();
		
		li.remove();
		
		if (ul.children(":not(.no_test_units)").length == 0)
			ul.children(".no_test_units").show();
	},
	
	moveUpTemplateActionTestUnit : function(elm) {
		var item = $(elm).parent().closest("li");
		var prev_item = item.prev(":not(.no_test_units)");
		
		if (prev_item[0])
			item.parent()[0].insertBefore(item[0], prev_item[0]);
	},
	
	moveDownTemplateActionTestUnit : function(elm) {
		var item = $(elm).parent().closest("li");
		var next_item = item.next();
		
		if (next_item[0])
			item.parent()[0].insertBefore(next_item[0], item[0]);
	},
	
	chooseTemplateActionTestUnit : function(elm) {
		if (typeof this.on_choose_test_units_callback == "function")
			this.on_choose_test_units_callback(elm);
	},
	
	/* DEPLOYMENT FUNCTIONS */
	
	addDeployment : function(tbody, props) {
		var selected = props && props.hasOwnProperty("selected") && props["selected"] != null ? parseInt(props["selected"]) == 1 || ("" + props["selected"]).toLowerCase() == "true" : "";
		var deployment_id = props && props.hasOwnProperty("deployment_id") && props["deployment_id"] != null ? props["deployment_id"] : "";
		var template_id = props && props.hasOwnProperty("template_id") && props["template_id"] != null ? props["template_id"] : "";
		var template_name = props && props.hasOwnProperty("template_name") && props["template_name"] != null ? props["template_name"] : "";
		var created_date = props && props.hasOwnProperty("created_date") && props["created_date"] != null ? props["created_date"] : "";
		var status = props && props.hasOwnProperty("status") && props["status"] != null ? parseInt(props["status"]) == 1 || ("" + props["status"]).toLowerCase() == "true" : "";
		//console.log(props);
		var c = status ? "ok" : "error";
		
		var html = $('<tr>'
			+ '	<td class="selected">' + (selected ? '<i class="icon star" title="Last deployment executed"></i>' : '') + '</td>'
			+ '	<td class="deployment_id ' + c + '" deployment_id="' + deployment_id + '">' + deployment_id + '</td>'
			+ '	<td class="template ' + c + '" template_id="' + template_id + '">' + template_name + '</td>'
			+ '	<td class="created_date ' + c + '">' + this.printTime(created_date) + '</td>'
			+ '	<td class="status ' + c + '">' + (status ? "OK" : "WRONG") + '</td>'
			+ '	<td class="logs"><a href="javascript:void(0)" onClick="ServerTaskPropertyObj.showDeploymentLogs(this)">view logs</a></td>'
			+ '	<td class="actions">'
			+ '		<i class="icon undo" onClick="ServerTaskPropertyObj.executeDeploymentServerActionRollback(this)" title="Roll Back this deployment"></i>'
			+ '		<i class="icon clean" onClick="ServerTaskPropertyObj.executeDeploymentServerActionClean(this)" title="Clean extra files of this deployment from the server"></i>'
			+ '		<i class="icon delete" onClick="ServerTaskPropertyObj.executeDeploymentServerActionDelete(this)" title="Delete this deployment from the server"></i>'
			+ '		<i class="icon right" onClick="ServerTaskPropertyObj.executeDeploymentServerActionRedeploy(this)" title="Repeat this deployment again to the server"></i>'
			+ '	</td>'
			+ '</tr>');
		
		html = $(html);
		
		html.find(".logs a").click(function() {
			
		});
		
		tbody.children(".no_items").hide();
		tbody.append(html);
		
		return html;
	},
	
	removeDeployment : function(task_html_elm, tasks_properties, template_id, deployment_id) {
		task_html_elm.find(" > .deployments_container > table > tbody td.template[template_id=" + template_id + "]").each(function(idx, td) {
			td = $(td);
			var tr = td.parent();
			var exists = tr.children("td.deployment_id[deployment_id=" + deployment_id + "]").length > 0;
			
			if (exists)
				tr.remove();
		});
		
		if (tasks_properties && tasks_properties["deployments"]) {
			var new_deployments = [];
			
			$.each(tasks_properties["deployments"], function (idx, deployment) {
				if (deployment && (deployment["template_id"] != template_id || deployment["deployment_id"] != deployment_id))
					new_deployments.push(deployment);
			});
			
			tasks_properties["deployments"] = new_deployments;
		}
	},
	
	seSelectedDeployment : function(task_html_elm, tasks_properties, template_id, deployment_id) {
		task_html_elm.find(" > .deployments_container > table > tbody td.template[template_id=" + template_id + "]").each(function(idx, td) {
			td = $(td);
			var tr = td.parent();
			var exists = tr.children("td.deployment_id[deployment_id=" + deployment_id + "]").length > 0;
			
			if (exists)
				tr.children("td.selected").html('<i class="icon star" title="Last deployment executed"></i>');
			else
				tr.children("td.selected").html('');
		});
		
		if (tasks_properties && tasks_properties["deployments"])
			$.each(tasks_properties["deployments"], function(idx, props) {
				var v = props && props.hasOwnProperty("deployment_id") && props["deployment_id"] != null ? props["deployment_id"] : "";
				var tid = props && props.hasOwnProperty("template_id") && props["template_id"] != null ? props["template_id"] : "";
				
				if (tid == template_id && v != deployment_id)
					tasks_properties["deployments"][idx]["selected"] = 0;
				if (tid == template_id && v == deployment_id)
					tasks_properties["deployments"][idx]["selected"] = 1;
			});
	},
	
	showDeploymentLogs : function(elm) {
		var WF = this.TaskFlowChartObject;
		var server_task_id = WF.ContextMenu.getContextMenuTaskId();
		var template_id = $(elm).parent().closest("tr").children(".template").attr("template_id");
		var deployment_id = $(elm).parent().closest("tr").children(".deployment_id").attr("deployment_id");
		
		if (server_task_id && template_id && deployment_id && WF.TaskFlow.tasks_properties.hasOwnProperty(server_task_id) && WF.TaskFlow.tasks_properties[server_task_id].hasOwnProperty("deployments"))
			$.each(WF.TaskFlow.tasks_properties[server_task_id]["deployments"], function (idx, deployment) {
				if (deployment["template_id"] == template_id && deployment["deployment_id"] == deployment_id) {
					var logs = deployment["logs"];
					alert(logs ? logs : "No logs!");
					
					return false;
				}
			});
	},
	
	updateDeploymentLogs : function(server_task_id, template_id, deployment_id, msg) {
		var WF = this.TaskFlowChartObject;
		
		if (msg && WF.TaskFlow.tasks_properties.hasOwnProperty(server_task_id) && WF.TaskFlow.tasks_properties[server_task_id].hasOwnProperty("deployments"))
			$.each(WF.TaskFlow.tasks_properties[server_task_id]["deployments"], function (idx, deployment) {
				if (deployment["template_id"] == template_id && deployment["deployment_id"] == deployment_id) {
					var logs = deployment["logs"];
					logs = logs ? logs : "";
					logs += (logs ? "\n\n" : "") + msg;
					
					WF.TaskFlow.tasks_properties[server_task_id]["deployments"][idx]["logs"] = logs;
					
					return false;
				}
			});
	},
	
	executeDeploymentServerActionDeploy : function(elm) {
		var select = $(elm).parent().children("select");
		var template_id = select.val();
		
		if (template_id) {
			if (confirm("The deployment will be executed based in the saved data, which means in order to continue you must save this workflow first.\nOnly the saved properties in this workflow will be used!\n\nDo you wish to save and continue deploying?\n\nIf you do not wish to save your latest changes, please refresh the page and re-execute this deployment...") && (!this.templates_changed[template_id] || confirm("The system detected that you made some changes in this template.\nIf you continue these changes won't be considered.\n\nTo deploy your new changes please save this workflow first.\nClick CANCEL to save it first or OK to continue...")) && confirm("This action may take a while.\nDo not close your browser and be patient!\n\nDo still wish to continue?")) {
				var task_html_elm = $(elm).parent().closest(".server_task_html");
				
				//delete templates workflow
				var template_properties = task_html_elm.find(" > .templates_container > .template_properties");
				this.destroyTemplatesWorkflow(template_properties);
				
				var WF = this.TaskFlowChartObject;
				myWFObj.setTaskFlowChart(WF);
				var saved = WF.TaskFile.save();
				
				if (saved) {
					WF.StatusMessage.removeLastShownMessage();
					this.showDeploymentServerActionLoading(task_html_elm);
					
					var server_task_id = WF.ContextMenu.getContextMenuTaskId();
					var server_label = WF.TaskFlow.getTaskLabelByTaskId(server_task_id);
					
					this.validateTemplateProperties(server_label, template_id, {
						success: function() {
							var tbody = select.parent().closest(".deployments_container").find(" > table > tbody");
							
							//getting new deployment_id
							var deployment_id = 1;
							
							if (WF.TaskFlow.tasks_properties.hasOwnProperty(server_task_id) && WF.TaskFlow.tasks_properties[server_task_id].hasOwnProperty("deployments"))
								$.each (WF.TaskFlow.tasks_properties[server_task_id]["deployments"], function(idx, deployment) {
									if (parseInt(deployment["deployment_id"]) >= deployment_id)
										deployment_id = parseInt(deployment["deployment_id"]) + 1;
								});
							
							var url = ("" + ServerTaskPropertyObj.deploy_template_to_server_url).replace("#server#", server_label).replace("#template_id#", template_id).replace("#deployment_id#", deployment_id).replace("#action#", "deploy");
							
							WF.StatusMessage.showMessage("Deploying...");
							
							$.ajax({
								url : url,
								dataType : "json",
								success : function(data, text_status, jqXHR) {
									WF.StatusMessage.removeLastShownMessage();
									
									if (data && $.isPlainObject(data)) {
										WF.StatusMessage.showMessage("Deployment is done...");
										
										var status = data["status"];
										var error_message = data["error_message"];
										var deployment_created = data["deployment_created"];
										
										if (deployment_created) {
											//prepare deployment data
											var d = new Date();
											var time = d.getTime() + ServerTaskPropertyObj.server_time_diff_in_milliseconds;
											
											var deployment = {
												selected: 1,
												deployment_id: deployment_id,
												template_id: template_id,
												template_name: select.find("option:selected").text(),
												created_date: time,
												status: status,
												logs: "*** DEPLOYMENT OF VERSION '" + deployment_id + "' at " + ServerTaskPropertyObj.printTime(time) + " ***\n" + (error_message ? "****** DEPLOYMENT ERRORS ******\n\n" + error_message : ""),
											};
											
											//add deployment to task properties
											if (!WF.TaskFlow.tasks_properties.hasOwnProperty(server_task_id))
												WF.TaskFlow.tasks_properties[server_task_id] = {};
											
											if (!WF.TaskFlow.tasks_properties[server_task_id].hasOwnProperty("deployments"))
												WF.TaskFlow.tasks_properties[server_task_id]["deployments"] = {};
											
											var next_idx = 0;
											$.each (WF.TaskFlow.tasks_properties[server_task_id]["deployments"], function(idx, deployment) {
												if (idx >= next_idx)
													next_idx = idx + 1;
											});
											
											WF.TaskFlow.tasks_properties[server_task_id]["deployments"][next_idx] = deployment;
											
											//add deployment html
											ServerTaskPropertyObj.addDeployment(tbody, deployment);
											
											//set last deployment executed
											ServerTaskPropertyObj.seSelectedDeployment(task_html_elm, WF.TaskFlow.tasks_properties[server_task_id], template_id, deployment_id);
											
											//save workflow with this deployment
											var saved = ServerTaskPropertyObj.saveDeploymentServerActionWorkflow(task_html_elm);
											
											//Do not use WF.StatusMessage.showMessage/showError bc the deployments take too long and the user can leave the computer alone and only come back later. If we use WF.StatusMessage.showMessage/showError the user will not see the message!
											if (status) {
												if (saved) {
													alert("Template deployed successfully to server!" + (error_message ? "\n" + error_message : "")); 
													WF.StatusMessage.showMessage("Workflow already saved!");
												}
												else
													alert("Template deployed successfully to server, but not saved. Please save this workflow manually!" + (error_message ? "\n" + error_message : ""));
												
												if (ServerTaskPropertyObj.existsDeploymentServerTemplateWordPressInstallations(server_task_id, template_id)) {
													var msg = "Note that you moved some WordPress installations to different servers, with probably different root folders or domains.\n\nIn order to these installations work correctly, you must update manually all the WordPress permanent links. Please login to your WordPress installations and follow the tutorial in https://wordpress.org/support/article/moving-wordpress/";
													alert(msg);
													WF.StatusMessage.showMessage( msg.replace(/\n/g, "<br/>").replace("https://wordpress.org/support/article/moving-wordpress/", '<a href="https://wordpress.org/support/article/moving-wordpress/" target="wordpress.org">https://wordpress.org/support/article/moving-wordpress/</a>') );
												}
											}
											else {
												if (saved) {
													alert("Template NOT deployed successfully to server!" + (error_message ? "\n" + error_message : "")); 
													WF.StatusMessage.showMessage("Workflow already saved!");
												}
												else
													alert("Template NOT deployed successfully to server and workflow not saved. Please save this workflow manually!" + (error_message ? "\n" + error_message : ""));
											}
										}
										else {
											if (status)
												alert("Template executed correctly but nothing done in the server.\nNo deployment will be created bellow!" + (error_message ? "\n" + error_message : ""));
											else
												alert("Template not deployed to server!" + (error_message ? "\n" + error_message : ""));
										}
									}
									else
										alert("There was an error trying to deploy this template to server. Please try again." + (data ? "\n" + data : "")); //Do not use WF.StatusMessage.showMessage/showError bc the deployments take too long and the user can leave the computer alone and only come back later. If we use WF.StatusMessage.showMessage/showError the user will not see the message!
									
									ServerTaskPropertyObj.hideDeploymentServerActionLoading(task_html_elm);
								},
								error : function(jqXHR, textStatus, errorThrown) { 
									WF.StatusMessage.removeLastShownMessage();
									
									var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
									alert("Error: Could not deploy template!\nPlease try again..." + msg); //Do not use WF.StatusMessage.showMessage/showError bc the deployments take too long and the user can leave the computer alone and only come back later. If we use WF.StatusMessage.showMessage/showError the user will not see the message!
									
									ServerTaskPropertyObj.hideDeploymentServerActionLoading(task_html_elm);
								},
							});
						},
						error: function() {
							alert("Template is not valid. Please re-check this template...");
							ServerTaskPropertyObj.hideDeploymentServerActionLoading(task_html_elm);
						},
					});
				}
			}
		}
		else 
			alert("You must select first a template!");
	},
	
	executeDeploymentServerActionDelete : function(elm) {
		this.executeDeploymentServerAction(elm, "delete", "Do you really wish to delete this deployment? \nIf you proceed all this deployment's files will be deleted in the server!", function(server_task_id, server_label, template_id, deployment_id, WF, task_html_elm, data) {
			if (data && $.isPlainObject(data)) {
				var status = data["status"];
				var error_message = data["error_message"];
				
				if (status) {
					//remove deployment from html table 
					ServerTaskPropertyObj.removeDeployment(task_html_elm, WF.TaskFlow.tasks_properties[server_task_id], template_id, deployment_id);
					
					//save workflow with this deployment
					var saved = ServerTaskPropertyObj.saveDeploymentServerActionWorkflow(task_html_elm);
					
					//Do not use WF.StatusMessage.showMessage/showError bc the deployments take too long and the user can leave the computer alone and only come back later. If we use WF.StatusMessage.showMessage/showError the user will not see the message!
					if (saved) {
						alert("Template deleted successfully to server!" + (error_message ? "\n" + error_message : "")); 
						WF.StatusMessage.showMessage("Workflow already saved!");
					}
					else
						alert("Template deleted successfully to server, but not saved. Please save this workflow manually!" + (error_message ? "\n" + error_message : ""));
				}
				else
					alert("Deployment NOT deleted in server!" + (error_message ? "\n" + error_message : "")); 
			}
			else
				alert("There was an error trying to delete this deployment in the server. Please try again." + (data ? "<br/>" + data : "")); //Do not use WF.StatusMessage.showMessage/showError bc the deployments take too long and the user can leave the computer alone and only come back later. If we use WF.StatusMessage.showMessage/showError the user will not see the message!
		});
	},
	
	executeDeploymentServerActionRedeploy : function(elm) {
		this.executeDeploymentServerAction(elm, "redeploy", "Do you really wish to repeat this deployment?", function(server_task_id, server_label, template_id, deployment_id, WF, task_html_elm, data) {
			if (data && $.isPlainObject(data)) {
				var status = data["status"];
				var error_message = data["error_message"];
				var deployment_created = data["deployment_created"];
				var redeployed_deployment_id = data["redeployed_deployment_id"];
				
				if (deployment_created) {
					//update deployment logs to task properties
					var d = new Date();
					var time = d.getTime() + ServerTaskPropertyObj.server_time_diff_in_milliseconds;
					var logs = "*** REDEPLOY '" + redeployed_deployment_id + "' VERSION at " + ServerTaskPropertyObj.printTime(time) + "  ***\n" + (error_message ? "****** REDEPLOY ERRORS ******\n\n" + error_message : "");
					ServerTaskPropertyObj.updateDeploymentLogs(server_task_id, template_id, deployment_id, logs);
					
					//set last deployment executed
					if (redeployed_deployment_id)
						ServerTaskPropertyObj.seSelectedDeployment(task_html_elm, WF.TaskFlow.tasks_properties[server_task_id], template_id, redeployed_deployment_id);
					
					//save workflow with this deployment
					var saved = ServerTaskPropertyObj.saveDeploymentServerActionWorkflow(task_html_elm);
					
					//Do not use WF.StatusMessage.showMessage/showError bc the deployments take too long and the user can leave the computer alone and only come back later. If we use WF.StatusMessage.showMessage/showError the user will not see the message!
					if (status) {
						if (saved) {
							alert("Template redeployed successfully to server!" + (error_message ? "\n" + error_message : "")); 
							WF.StatusMessage.showMessage("Workflow already saved!");
						}
						else
							alert("Template redeployed successfully to server, but not saved. Please save this workflow manually!" + (error_message ? "\n" + error_message : ""));
					}
					else {
						if (saved) {
							alert("Template NOT redeployed successfully to server!" + (error_message ? "\n" + error_message : "")); 
							WF.StatusMessage.showMessage("Workflow already saved!");
						}
						else
							alert("Template NOT redeployed successfully to server and workflow not saved. Please save this workflow manually!" + (error_message ? "\n" + error_message : ""));
					}
				}
				else
					alert("Template not redeployed to server!" + (error_message ? "\n" + error_message : ""));
			}
			else
				alert("There was an error trying to redeploy this deployment in the server. Please try again." + (data ? "<br/>" + data : "")); //Do not use WF.StatusMessage.showMessage/showError bc the deployments take too long and the user can leave the computer alone and only come back later. If we use WF.StatusMessage.showMessage/showError the user will not see the message!
		});
	},
	
	executeDeploymentServerActionRollback : function(elm) {
		this.executeDeploymentServerAction(elm, "rollback", "Do you really wish to rollback this deployment?", function(server_task_id, server_label, template_id, deployment_id, WF, task_html_elm, data) {
			if (data && $.isPlainObject(data)) {
				var status = data["status"];
				var error_message = data["error_message"];
				var rollbacked_deployment_id = data["rollbacked_deployment_id"];
				
				//update deployment logs to task properties
				var d = new Date();
				var time = d.getTime() + ServerTaskPropertyObj.server_time_diff_in_milliseconds;
				var logs = "*** ROLLBACK TO '" + rollbacked_deployment_id + "' VERSION at " + ServerTaskPropertyObj.printTime(time) + "  ***\n" + (error_message ? "****** ROLLBACK ERRORS ******\n\n" + error_message : "");
				ServerTaskPropertyObj.updateDeploymentLogs(server_task_id, template_id, deployment_id, logs);
				
				//set last deployment executed
				if ($.isNumeric(rollbacked_deployment_id))
					ServerTaskPropertyObj.seSelectedDeployment(task_html_elm, WF.TaskFlow.tasks_properties[server_task_id], template_id, rollbacked_deployment_id);
				
				//save workflow with this deployment
				var saved = ServerTaskPropertyObj.saveDeploymentServerActionWorkflow(task_html_elm);
				
				//Do not use WF.StatusMessage.showMessage/showError bc the deployments take too long and the user can leave the computer alone and only come back later. If we use WF.StatusMessage.showMessage/showError the user will not see the message!
				if (status) {
					if (saved) {
						alert("Template rollbacked successfully to server!" + (error_message ? "\n" + error_message : "")); 
						WF.StatusMessage.showMessage("Workflow already saved!");
					}
					else
						alert("Template rollbacked successfully to server, but not saved. Please save this workflow manually!" + (error_message ? "\n" + error_message : ""));
				}
				else {
					if (saved) {
						alert("Template NOT rollbacked successfully to server!" + (error_message ? "\n" + error_message : "")); 
						WF.StatusMessage.showMessage("Workflow already saved!");
					}
					else
						alert("Template NOT rollbacked successfully to server and workflow not saved. Please save this workflow manually!" + (error_message ? "\n" + error_message : ""));
				}
			}
			else
				alert("There was an error trying to rollback this deployment in the server. Please try again." + (data ? "<br/>" + data : "")); //Do not use WF.StatusMessage.showMessage/showError bc the deployments take too long and the user can leave the computer alone and only come back later. If we use WF.StatusMessage.showMessage/showError the user will not see the message!
			
		});
	},
	
	executeDeploymentServerActionClean : function(elm) {
		this.executeDeploymentServerAction(elm, "clean", "Do you wish to clean the temporary files for this deployment? Only auto-generated files from the system will be removed! This action is harmless...", function(server_task_id, server_label, template_id, deployment_id, WF, task_html_elm, data) {
			if (data && $.isPlainObject(data)) {
				var status = data["status"];
				var error_message = data["error_message"];
				
				//Do not use WF.StatusMessage.showMessage/showError bc the deployments take too long and the user can leave the computer alone and only come back later. If we use WF.StatusMessage.showMessage/showError the user will not see the message!
				if (status)
					alert("Template cleaned successfully to server!" + (error_message ? "\n" + error_message : "")); 
				else 
					alert("Template NOT cleaned successfully to server!" + (error_message ? "\n" + error_message : ""));
			}
			else
				alert("There was an error trying to clean this deployment in the server. Please try again." + (data ? "<br/>" + data : "")); //Do not use WF.StatusMessage.showMessage/showError bc the deployments take too long and the user can leave the computer alone and only come back later. If we use WF.StatusMessage.showMessage/showError the user will not see the message!
		});
	},
	
	executeDeploymentServerAction : function(elm, action, confirmation_msg, func) {
		if (!confirmation_msg || confirm(confirmation_msg)) {
			var tr = $(elm).parent().closest("tr");
			var deployment_id = tr.children("td.deployment_id").attr("deployment_id");
			var template_id = tr.children("td.template").attr("template_id");
			
			var WF = this.TaskFlowChartObject;
			myWFObj.setTaskFlowChart(WF);
			
			var server_task_id = WF.ContextMenu.getContextMenuTaskId();
			var server_label = WF.TaskFlow.getTaskLabelByTaskId(server_task_id);
			
			if (server_label && template_id && deployment_id) {
				var tbody = tr.parent();
				var task_html_elm = tbody.parent().closest(".server_task_html");
				
				WF.StatusMessage.removeLastShownMessage();
				this.showDeploymentServerActionLoading(task_html_elm);
				
				//delete templates workflow
				var template_properties = task_html_elm.find(" > .templates_container > .template_properties");
				this.destroyTemplatesWorkflow(template_properties);
				
				var url = ("" + ServerTaskPropertyObj.deploy_template_to_server_url).replace("#server#", server_label).replace("#template_id#", template_id).replace("#deployment_id#", deployment_id).replace("#action#", action);
				
				var act = action == "delete" ? "delet" : action;
				WF.StatusMessage.showMessage(act + "ing...");
							
				$.ajax({
					url : url,
					dataType : "json",
					success : function(data, text_status, jqXHR) {
						WF.StatusMessage.removeLastShownMessage();
						
						if (typeof func == "function")
							func(server_task_id, server_label, template_id, deployment_id, WF, task_html_elm, data);
						else
							alert("Deployment " + act + "ed to server!" + (error_message ? "\n" + error_message : ""));
						
						ServerTaskPropertyObj.hideDeploymentServerActionLoading(task_html_elm);
					},
					error : function(jqXHR, textStatus, errorThrown) { 
						WF.StatusMessage.removeLastShownMessage();
							
						var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
						alert("Error: Could not " + action + " deployment!\nPlease try again..." + msg); //Do not use WF.StatusMessage.showMessage/showError bc the deployments take too long and the user can leave the computer alone and only come back later. If we use WF.StatusMessage.showMessage/showError the user will not see the message!
						
						ServerTaskPropertyObj.hideDeploymentServerActionLoading(task_html_elm);
					},
				});
			}
		}
	},
	
	showDeploymentServerActionLoading : function(task_html_elm) {
		var WF = this.TaskFlowChartObject;
		WF.getMyFancyPopupObj().showLoading();
		
		var frozen_overlay = task_html_elm.children(".frozen_overlay");
		
		if (!frozen_overlay[0]) {
			frozen_overlay = $('<div class="frozen_overlay"></div>');
			
			frozen_overlay.click(function(e) {
				e.preventDefault();
				e.stopPropagation();
			})
			.mousedown(function(e) {
				e.preventDefault();
				e.stopPropagation();
			});
			
			task_html_elm.append(frozen_overlay);
		}
		
		var z_index = parseInt(WF.getMyFancyPopupObj().getPopupCloseButton().css("z-index"));
		if (z_index > 0)
			frozen_overlay.css("z-index", z_index);
		
		frozen_overlay.show();
	},
	
	hideDeploymentServerActionLoading : function(task_html_elm) {
		var WF = this.TaskFlowChartObject;
		WF.getMyFancyPopupObj().hideLoading();
		
		task_html_elm.children(".frozen_overlay").hide();
	},
	
	saveDeploymentServerActionWorkflow : function(task_html_elm) {
		var WF = this.TaskFlowChartObject;
		
		//hide template properties in case be open
		if (this.TemplatePropertiesMyFancyPopupObject.settings) //maybe it wasn't inited yet
			this.TemplatePropertiesMyFancyPopupObject.settings.saveTemplateProperties = false;
		
		this.TemplatePropertiesMyFancyPopupObject.hidePopup();
		
		//hide other popups
		var overlay_id = WF.getMyFancyPopupObj().getOverlay().attr("id");
		var loading_id = WF.getMyFancyPopupObj().getLoading().attr("id");
		var popup_id = WF.getMyFancyPopupObj().getPopup().attr("id");
		
		$(".myfancypopup:not(#" + popup_id + ")").hide();
		$(".myfancypopup.choose_from_file_manager").hide(); 
		
		$(".popup_overlay:not(#" + overlay_id + ")").hide();
		$(".popup_loading:not(#" + loading_id + ")").hide();
		
		//set deployments tab active
		task_html_elm.tabs({active: 2});
		
		//delete templates workflow again. Very important bc meanwhile the user may open a template again.
		var template_properties = task_html_elm.find(" > .templates_container > .template_properties");
		ServerTaskPropertyObj.destroyTemplatesWorkflow(template_properties);
		
		//save workflow with this deployment
		var saved = WF.TaskFile.save();
		WF.StatusMessage.removeLastShownMessage();
		
		return saved;
	},
	
	existsDeploymentServerTemplateWordPressInstallations : function(server_task_id, template_id) {
		var exists_wordpress_installations = false;
		var WF = this.TaskFlowChartObject;
		
		if (WF.TaskFlow.tasks_properties.hasOwnProperty(server_task_id) && WF.TaskFlow.tasks_properties[server_task_id]["templates"] && WF.TaskFlow.tasks_properties[server_task_id]["templates"][template_id] && WF.TaskFlow.tasks_properties[server_task_id]["templates"][template_id]["properties"]) {
			var template_tasks = WF.TaskFlow.tasks_properties[server_task_id]["templates"][template_id]["properties"]["task"];
			//console.log(template_tasks);
			
			if (template_tasks)
				for (var i = 0; i < template_tasks.length; i++) {
					var template_task = template_tasks[i];
					
					if ($.isPlainObject(template_task) && template_task["properties"] && template_task["properties"].hasOwnProperty("wordpress_installations") && $.isArray(template_task["properties"]["wordpress_installations"])) {
						//console.log(template_task["properties"]["wordpress_installations"]);
						
						for (var j = 0; j < template_task["properties"]["wordpress_installations"].length; j++) {
							var wordpress_name = template_task["properties"]["wordpress_installations"][j];
							
							if (wordpress_name) {
								exists_wordpress_installations = true;
								//console.log(wordpress_name);
								break;
							}
						}
					}
					
					if (exists_wordpress_installations)
						break;
				}
		}
		//console.log(exists_wordpress_installations);
		
		return exists_wordpress_installations;
	},
	
	/* DEPLOYMENT - UTILS FUNCTIONS */
	
	onClickServerDetailsTab : function(elm) {
		//remove width and height style so the popup get updated automatically
		myWFObj.getTaskFlowChart().getMyFancyPopupObj().getPopup().css({width: "", height: ""});
	},
	
	onClickServerTemplatesTab : function(elm) {
		//remove width and height style so the popup get updated automatically
		myWFObj.getTaskFlowChart().getMyFancyPopupObj().getPopup().css({width: "", height: ""});
	},
	
	onClickServerDeploymentsTab : function(elm) {
		this.updateAvailableTemplates(elm);
		
		//remove width and height style so the popup get updated automatically
		myWFObj.getTaskFlowChart().getMyFancyPopupObj().getPopup().css({width: "", height: ""});
	},
	
	updateAvailableTemplates : function(elm) {
		var task_html_elm = $(elm).parent().closest(".server_task_html");
		var select = task_html_elm.find(" > .deployments_container > .deploy_template > select");
		var inputs = task_html_elm.find(" > .templates_container > table > tbody > tr > td.name > input");
		var server_task_id = ServerTaskPropertyObj.TaskFlowChartObject.ContextMenu.getContextMenuTaskId();
		
		var props = ServerTaskPropertyObj.TaskFlowChartObject.TaskFlow.tasks_properties;
		props = props && props.hasOwnProperty(server_task_id) ? props[server_task_id] : null;
		props = props ? props["templates"] : null;
		
		var original_templates_name = [];
		if (props)
			$.each(props, function(idx, prop) {
				if ($.isPlainObject(prop) && prop["name"])
					original_templates_name.push(prop["name"]);
			});
		
		var options = '<option></option>';
		
		$.each(inputs, function(idx, input) {
			var template_name = $(input).val();
			
			if (template_name) {
				var template_id = $(input).parent().closest("tr").attr("template_id");
				var is_active = $.inArray(template_name, original_templates_name) != -1;
				options += '<option value="' + template_id + '"' + (is_active ? '' : ' disabled') + '>' + template_name + '</option>';
			}
		});
		
		select.html(options);
	},
	
	replaceDeploymentsTemplateName : function(task_html_elm, template_id, name) {
		this.server_deployments_template_name_replacements[template_id] = name;
		
		task_html_elm.find(" > .deployments_container > table > tbody > tr > td.template[template_id=" + template_id + "]").each(function(idx, td) {
			$(td).html(name);
		});
	},
	
	destroyTemplatesWorkflow : function(template_properties) {
		//delete templates workflow
		if (ServerTaskPropertyObj.TemplatesWorkflowHandlerObject)
			ServerTaskPropertyObj.TemplatesWorkflowHandlerObject.destroy();
		
		ServerTaskPropertyObj.TemplatesWorkflowHandlerObject = null;
		
		//remove task_property_field objects from template_workflow
		template_properties.find(" > .template_workflow > .taskflowchart").remove();
		
		//remove task_property_field objects from template_actions
		var template_actions = template_properties.children(".template_actions");
		template_actions.find(" > .server_installation_folder_path > input").removeClass("task_property_field");
		template_actions.find(" > .server_installation_url > input").removeClass("task_property_field");
		template_actions.find(" > .actions > .action").remove();
		
	},
	
	/* UTILS FUNCTIONS */
	
	printTime : function(time) {
		if ($.isNumeric(time)) {
			var date = new Date();
			date.setTime(time);
			
			var yyyy = date.getFullYear();
			var mm = String(date.getMonth() + 1).padStart(2, '0'); //January is 0!
			var dd = String(date.getDate()).padStart(2, '0');
			var h = String(date.getHours()).padStart(2, '0');
			var i = String(date.getMinutes()).padStart(2, '0');
			var s = String(date.getSeconds()).padStart(2, '0');

			return yyyy + '-' + mm + '-' + dd + ' ' + h + ':' + i + ':' + s;
		}
		
		return "";
	},
};
