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

var FunctionUtilObj = {
	
	EditFunctionCodeMyFancyPopupObject : new MyFancyPopupClass(),
	EditFunctionCodeEditor : null,
	EditFunctionCodeTaskFlowChart : null,
	OriginalTaskFlowChartObject : null,
	set_tmp_workflow_file_url : null,
	get_tmp_workflow_file_url : null,
	create_code_from_workflow_file_url : null,
	create_workflow_file_from_code_url : null,
	auto_convert: false,
	
	on_function_task_edit_method_code_callback : null,
	
	loadMethodArgs : function(parent_elm, arguments) {
		if (arguments) {
			var add_icon = parent_elm.find(" > table thead th .icon.add");
			
			if ($.isPlainObject(arguments) && arguments.hasOwnProperty("name"))
				arguments = [ arguments ];
			
			$.each(arguments, function(idx, arg) {
				var new_item = FunctionUtilObj.addNewMethodArg(add_icon[0]);
				
				new_item.find(".name input").val(arg["name"]);
				new_item.find(".value input").val(arg["value"]);
				new_item.find(".var_type select").val(arg["var_type"]);
			});
		}
	},
	
	addNewMethodArg : function(elm) {
		var var_types = {"string": "string", "": "default"};
		var tbody = $(elm).parent().closest("table").children("tbody");
		var index_prefix = tbody.attr("index_prefix");
		var idx = getListNewIndex(tbody);
		
		var html = '<tr class="method_arg">'
				+ '	<td class="name">'
				+ '		<input class="task_property_field" name="' + index_prefix + '[' + idx + '][name]" type="text" value="" />'
				+ '	</td>'
				+ '	<td class="value">'
				+ '		<input class="task_property_field" name="' + index_prefix + '[' + idx + '][value]" type="text" value="" />'
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
	
	editMethodCode : function(elm) {
		var function_code_textarea = $(elm).parent().children("textarea.function_code");
		var code = "<?php\n" + function_code_textarea.val() + "\n?>";
		
		//backup original OriginalTaskFlowChartObject
		this.OriginalTaskFlowChartObject = myWFObj.getTaskFlowChart();
		this.OriginalTaskFlowChartObject.getMyFancyPopupObj().getPopupCloseButton().hide();
		
		var main_tasks_flow_parent = $("#" + this.OriginalTaskFlowChartObject.TaskFlow.main_tasks_flow_obj_id).parent();
		var main_div_id = "edit_function_code_" + main_tasks_flow_parent.attr("id");
		
		this.auto_convert = typeof auto_convert != "undefined" ? auto_convert : false;
		
		var is_reverse = main_tasks_flow_parent.hasClass("reverse");
		
		//prepare html
		var html = '	<div class="myfancypopup edit_function_code with_title">'
				+ '		<div class="title">Edit Function Code</div>'
				+ '		<ul class="tabs tabs_transparent tabs_right tabs_icons">'
				+ '			<li id="code_editor_tab"><a href="#code" onClick="FunctionUtilObj.onClickCodeEditorTab(this);return false;"><i class="icon code_editor_tab"></i> Code Editor</a></li>'
				+ '			<li id="tasks_flow_tab"><a href="#ui" onClick="FunctionUtilObj.onClickTaskWorkflowTab(this);return false;"><i class="icon tasks_flow_tab"></i> Diagram Editor</a></li>'
				+ '		</ul>'
				+ '		'
				+ '		<span class="message"></span>'
				+ '		'
				+ '		<div id="code">'
				+ '			<div class="code_menu top_bar_menu">'
				+ '				<ul>'
				+ '					<li class="editor_settings" title="Open Editor Setings"><a onClick="FunctionUtilObj.openEditorSettings(this)"><i class="icon settings"></i> Open Editor Setings</a></li>'
				+ '					<li class="pretty_print" title="Pretty Print Code"><a onClick="FunctionUtilObj.prettyPrintCode(this)"><i class="icon pretty_print"></i> Pretty Print Code</a></li>'
				+ '					<li class="set_word_wrap" title="Set Word Wrap"><a class="active" onClick="FunctionUtilObj.setWordWrap(this)" wrap="0"><i class="icon word_wrap"></i> Word Wrap</a></li>'
				+ '				</ul>'
				+ '			</div>'
				+ '			<textarea></textarea>'
				+ '		</div>'
				+ '		'
				+ '		<div id="ui">'
				+ '			<div id="' + main_div_id + '" class="taskflowchart ' + (is_reverse ? "reverse" : "") + ' with_top_bar_menu fixed_side_properties">'
				+ '				<div id="workflow_menu" class="workflow_menu top_bar_menu">'
				+ '					<ul class="dropdown">'
				+ '						<li class="sort_tasks" title="Sort Tasks">'
				+ '							<a onclick="FunctionUtilObj.sortWorkflowTask(this);return false;"><i class="icon sort"></i> Sort Tasks</a>'
				+ '							<ul>'
				+ '								<li class="sort_tasks"><a onclick="FunctionUtilObj.sortWorkflowTask(this, 1);return false;"><i class="icon sort"></i> Sort Type 1</a></li>'
				+ '								<li class="sort_tasks"><a onclick="FunctionUtilObj.sortWorkflowTask(this, 2);return false;"><i class="icon sort"></i> Sort Type 2</a></li>'
				+ '								<li class="sort_tasks"><a onclick="FunctionUtilObj.sortWorkflowTask(this, 3);return false;"><i class="icon sort"></i> Sort Type 3</a></li>'
				+ '								<li class="sort_tasks"><a onclick="FunctionUtilObj.sortWorkflowTask(this, 4);return false;"><i class="icon sort"></i> Sort Type 4</a></li>'
				+ '							</ul>'
				+ '						</li>'
				+ '						' + (
											(this.create_workflow_file_from_code_url && this.get_tmp_workflow_file_url) || 
											(this.create_code_from_workflow_file_url && this.set_tmp_workflow_file_url)
											 ? '<li class="separator"></li>' : ''
										)
				+ '						' + (this.create_workflow_file_from_code_url && this.get_tmp_workflow_file_url ? '<li class="generate_tasks_flow_from_code" title="Generate Diagram from Code"><a onclick="FunctionUtilObj.generateTasksFlowFromCode(this);return false;"><i class="icon generate_tasks_flow_from_code"></i> Generate Diagram from Code</a></li>' : '')
				+ '						' + (this.create_code_from_workflow_file_url && this.set_tmp_workflow_file_url ? '<li class="generate_code_from_tasks_flow" title="Generate Code From Diagram"><a onclick="FunctionUtilObj.generateCodeFromTasksFlow(this);return false;"><i class="icon generate_code_from_tasks_flow"></i> Generate Code From Diagram</a></li>' : '')
				+ '						<li class="separator"></li>'
				+ '						<li class="flip_tasks_flow_panels_side" title="Flip Panels Side"><a onclick="FunctionUtilObj.flipTasksFlowPanelsSide(this);return false;"><i class="icon flip_tasks_flow_panels_side"></i> Flip Panels Side</a></li>'
				+ '					</ul>'
				+ '				</div>'
				+ '				'
				+ '				<div class="tasks_menu scroll"></div>'
				+ '				'
				+ '				<div class="tasks_menu_hide">'
				+ '					<div class="button minimize" onClick="myWFObj.getTaskFlowChart().ContextMenu.toggleTasksMenuPanel(this)"></div>'
				+ '				</div>'
				+ '				'
				+ '				<div class="tasks_flow scroll"></div>'
				+ '				'
				+ '				<div class="tasks_properties hidden"></div>'
				+ '				'
				+ '				<div class="connections_properties hidden"></div>'
				+ '			</div>'
				+ '		</div>'
				+ '		'
				+ '		<div class="button">'
				+ '			<input type="button" value="UPDATE CODE" onClick="FunctionUtilObj.EditFunctionCodeMyFancyPopupObject.settings.updateFunction(this)" />'
				+ '		</div>'
				+ '	</div>';
		
		var popup = $(html);
		main_tasks_flow_parent.append(popup);
		
		//init tabs
		popup.tabs();
		
		//create editor
		var textarea = popup.find(" > #code > textarea");
		textarea.val(code);
		this.createEditor(textarea[0]);
		
		//init workflow
		var tasks_menu = popup.find(" > #ui > .taskflowchart > .tasks_menu");
		tasks_menu.html( main_tasks_flow_parent.children("#" + this.OriginalTaskFlowChartObject.ContextMenu.main_tasks_menu_obj_id).html() );
		tasks_menu.find(".cloned_task").remove();
		tasks_menu.find("." + this.OriginalTaskFlowChartObject.ContextMenu.task_menu_class_name).off().removeClass("ui-draggable ui-draggable-handle");
		
		$.each(tasks_menu.find("." + this.OriginalTaskFlowChartObject.ContextMenu.tasks_group_tasks_class_name), function(idx, item) {
			item = $(item);
			item.attr("bkp-height", item.css("height"));
		});
		
		popup.find(" > #ui > .taskflowchart > .tasks_properties").html( main_tasks_flow_parent.children(".tasks_properties").html() );
		
		popup.find(" > #ui > .taskflowchart > .connections_properties").html( main_tasks_flow_parent.children(".connections_properties").html() );
		
		this.EditFunctionCodeMyFancyPopupObject.init({
			elementToShow: popup,
			parentElement: main_tasks_flow_parent,
			popup_class: "edit_function_popup",
			onOpen: function() {
				popup.off().removeClass("ui-draggable ui-draggable-handle");
				
				var close_func = function(e) {
					e.preventDefault();
					
					if (confirm("If you close this popup you will loose your code changes.\nTo update your code changes please click in the 'UPDATE CODE' button instead.\nDo you still wish to proceed?"))
						FunctionUtilObj.hideEditFunctionCodeMyFancyPopup();
				};
				
				var close_btn = FunctionUtilObj.EditFunctionCodeMyFancyPopupObject.getPopupCloseButton();
				close_btn.unbind("click").off().click(close_func);
				
				var overlay = FunctionUtilObj.EditFunctionCodeMyFancyPopupObject.getOverlay();
				overlay.unbind("click").off().click(close_func);
				
				FunctionUtilObj.onFunctionTaskEditMethodCode(elm, popup);
			},
			
			targetField: function_code_textarea[0],
			updateFunction: this.updateFunctionCode
		});
		
		this.EditFunctionCodeMyFancyPopupObject.showPopup();
	},
	
	updateFunctionCode : function(elm) {
		//convert workflow to code first
		var edit_function_code = $(elm).parent().closest(".edit_function_code");
		var selected_tab = edit_function_code.children("ul.tabs").children("li.ui-tabs-selected, li.ui-tabs-active").first();
		
		if (FunctionUtilObj.auto_convert && FunctionUtilObj.EditFunctionCodeTaskFlowChart && selected_tab.attr("id") == "tasks_flow_tab")
			FunctionUtilObj.generateCodeFromTasksFlow(elm, true);
		
		var code = FunctionUtilObj.getEditFunctionCodeEditorValue(elm);
		code = "?>" + code + "<?php ";
		code = code.replace(/\?>\s*<\?(php|)/g, "").replace(/^\s+/g, "").replace(/\s+$/g, "");
		
		$(FunctionUtilObj.EditFunctionCodeMyFancyPopupObject.settings.targetField).val(code);
		FunctionUtilObj.hideEditFunctionCodeMyFancyPopup();
	},
	
	hideEditFunctionCodeMyFancyPopup : function() {
		if (this.EditFunctionCodeTaskFlowChart)
			this.EditFunctionCodeTaskFlowChart.destroy();
		
		this.EditFunctionCodeTaskFlowChart = null;
		
		myWFObj.setTaskFlowChart(this.OriginalTaskFlowChartObject);
		this.OriginalTaskFlowChartObject.getMyFancyPopupObj().getPopupCloseButton().show();
		
		this.EditFunctionCodeMyFancyPopupObject.hidePopup();
		
		setTimeout(function() {
			FunctionUtilObj.EditFunctionCodeMyFancyPopupObject.destroyPopup();
		}, 500);
	},
	
	getEditFunctionCodeEditorValue : function(elm) {
		var code = "";
		
		if (this.EditFunctionCodeEditor)
			code = this.EditFunctionCodeEditor.getValue(code);
		else
			code = $(elm).closest(".edit_function_code").find(" > #code > textarea").val();
		
		return code;
	},
	
	setEditFunctionCodeEditorValue : function(elm, code) {
		if (this.EditFunctionCodeEditor)
			code = this.EditFunctionCodeEditor.setValue(code, -1);
		else
			code = $(elm).closest(".edit_function_code").find(" > #code > textarea").val(code);
	},
	
	onClickCodeEditorTab : function(elm) {
		setTimeout(function() {
			if (FunctionUtilObj.EditFunctionCodeEditor && $(elm).closest(".edit_function_code").children("#code").is(":visible"))
				FunctionUtilObj.EditFunctionCodeEditor.focus();
			
			if (FunctionUtilObj.auto_convert && FunctionUtilObj.EditFunctionCodeTaskFlowChart)
				FunctionUtilObj.generateCodeFromTasksFlow(elm, true);
		}, 10);
	},
	
	onClickTaskWorkflowTab : function(elm) {
		elm = $(elm);
		var popup = elm.parent().closest(".edit_function_code");
		var z_index = popup.css("z-index");
		var WF = this.EditFunctionCodeTaskFlowChart;
		
		if (!WF) {
			//prepare new TaskFlowChart obj
			var selector = elm.attr("href");
			var main_div = popup.children(selector).children(".taskflowchart");
			var main_div_id = main_div.attr("id");
			
			WF = new TaskFlowChart(main_div_id, {
				on_init_function: function(innerWF) {
					//prepare tasks menus
					$("#" + innerWF.ContextMenu.main_tasks_menu_obj_id + " ." + innerWF.ContextMenu.tasks_group_tasks_class_name).each(function(idx, item) {
						item = $(item);
						item.css("height", item.attr("bkp-height"));
					});
				},
				is_droppable_connection: true
			});
			eval('window.' + main_div_id + ' = WF;');
			
			WF.TaskFlow.main_tasks_flow_obj_id = main_div_id + " .tasks_flow";
			WF.TaskFlow.main_tasks_properties_obj_id = main_div_id + " .tasks_properties";
			WF.TaskFlow.main_connections_properties_obj_id = main_div_id + " .connections_properties";
			WF.ContextMenu.main_tasks_menu_obj_id = main_div_id + " .tasks_menu";
			WF.ContextMenu.main_tasks_menu_hide_obj_id = main_div_id + " .tasks_menu_hide";
			WF.ContextMenu.main_workflow_menu_obj_id = main_div_id + " .workflow_menu";
			
			WF.TaskFlow.default_connection_line_width = this.OriginalTaskFlowChartObject.TaskFlow.default_connection_line_width;
			
			//set z-index bc of leader-line
			if ($.isNumeric(z_index)) {
				z_index = parseInt(z_index) + 10;
				WF.TaskFlow.default_connection_z_index = z_index;
				
				popup.children("style.edit_function_code_leader_line_z_index_style").remove();
				popup.append('<style class="edit_function_code_leader_line_z_index_style"> body > .leader-line {z-index:' + z_index + ';} </style>');
			}
			
			WF.Property.tasks_settings = assignObjectRecursively({}, this.OriginalTaskFlowChartObject.Property.tasks_settings);
			WF.TaskFile.set_tasks_file_url = this.OriginalTaskFlowChartObject.TaskFile.set_tasks_file_url;
			WF.TaskFile.get_tasks_file_url = this.OriginalTaskFlowChartObject.TaskFile.get_tasks_file_url;
			
			WF.Container.tasks_containers = [];
			
			WF.taskFlowChartObjOptions.on_resize_panels_function = function() {
				//reset top in style attribute, so it can have the css from the FunctionUtilObj.css file.
				main_div.find(".workflow_menu, .tasks_menu, .tasks_menu_hide, .tasks_flow").css("top", "");
			};
			
			//init flow
			WF.init();
			
			this.EditFunctionCodeTaskFlowChart = WF;
			
			setTimeout(function() {
				WF.resizePanels();
			}, 500);
		}
		
		//set the new TaskFlowChart
		myWFObj.setTaskFlowChart(WF);
		
		if (this.auto_convert)
			this.generateTasksFlowFromCode(elm, true);
		
		//prepare some z-index bc of the popup
		var main_tasks_flow_elm = $("#" + WF.TaskFlow.main_tasks_flow_obj_id);
		
		main_tasks_flow_elm.children("#" + WF.ContextMenu.task_context_menu_id).css("z-index", z_index);
		main_tasks_flow_elm.children("#" + WF.ContextMenu.connection_context_menu_id).css("z-index", z_index);
		main_tasks_flow_elm.children("#" + WF.StatusMessage.message_html_obj_id).css("z-index", z_index);
	},
	
	generateCodeFromTasksFlow : function(elm, do_not_confirm) {
		var status = false;
		
		if (this.create_code_from_workflow_file_url && this.set_tmp_workflow_file_url) {
			status = true;
			
			var edit_function_code = $(elm).parent().closest(".edit_function_code");
			var ui_elm = edit_function_code.children("#ui");
			var code_elm = edit_function_code.children("#code");
			var old_workflow_id = ui_elm.attr("workflow_id");
			var WF = myWFObj.getTaskFlowChart();
			var data = WF.TaskFile.getWorkFlowData();
			var new_workflow_id = $.md5(JSON.stringify(data));
			
			var generated_code_id = code_elm.attr("generated_code_id");
			var code = this.getEditFunctionCodeEditorValue(ui_elm);
			var new_code_id = code ? $.md5(code) : null;
			
			if (old_workflow_id != new_workflow_id || (generated_code_id && generated_code_id != new_code_id)) {
				if (do_not_confirm || this.auto_convert || confirm("Do you wish to update this code accordingly with the workflow tasks?")) {
					status = false;
					
					var workflow_menu = ui_elm.find(" > .taskflowchart > .workflow_menu");
					
					this.EditFunctionCodeMyFancyPopupObject.showLoading();
					workflow_menu.hide();
					
					var save_options = {
						overwrite: true,
						success: function(data, textStatus, jqXHR) {
							if (typeof jquery_native_xhr_object != "undefined" && jquery_native_xhr_object && typeof isAjaxReturnedResponseLogin == "function" && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
								showAjaxLoginPopup(jquery_native_xhr_object.responseURL, set_tmp_workflow_file_url, function() {
									WF.StatusMessage.removeLastShownMessage("error");
									FunctionUtilObj.generateCodeFromTasksFlow(elm, true);
								});
						},
					};
					
					if (WF.TaskFile.save(set_tmp_workflow_file_url, save_options)) {
						$.ajax({
							type : "get",
							url : this.create_code_from_workflow_file_url,
							dataType : "json",
							success : function(data, textStatus, jqXHR) {
								if (data && data.hasOwnProperty("code")) {
									var code = "<?php\n" + data.code.replace(/^\s+/g, "").replace(/\s+$/g, "") + "\n?>"; 
									code = code.replace(/^<\?php\s+\?>\s*/, "").replace(/<\?php\s+\?>$/, ""); //remove empty php tags
									
									FunctionUtilObj.setEditFunctionCodeEditorValue(ui_elm, code);
									
									ui_elm.attr("workflow_id", new_workflow_id);
									code_elm.attr("generated_code_id", $.md5(code));
									
									if (data["error"] && data["error"]["infinit_loop"] && data["error"]["infinit_loop"][0]) {
										var loops = data["error"]["infinit_loop"];
										
										var msg = "";
										for (var i = 0; i < loops.length; i++) {
											var loop = loops[i];
											var slabel = WF.TaskFlow.getTaskLabelByTaskId(loop["source_task_id"]);
											var tlabel = WF.TaskFlow.getTaskLabelByTaskId(loop["target_task_id"]);
											
											msg += (i > 0 ? "\n" : "") + "- '" + slabel + "' => '" + tlabel + "'";
										}
										
										msg = "The system detected the following invalid loops and discarded them from the code:\n" + msg + "\n\nYou should remove them from the workflow and apply the correct 'loop task' for doing loops.";
										WF.StatusMessage.showError(msg);
										alert(msg);
									}
									else {
										var edit_tab = edit_function_code.find("#code_editor_tab a").first();
										edit_tab.click();
										
										status = true;
									}
								}
								else
									FunctionUtilObj.showMessage(elm, "There was an error trying to update this code. Please try again.");
								
								FunctionUtilObj.EditFunctionCodeMyFancyPopupObject.hideLoading();
								workflow_menu.show();
							},
							error : function(jqXHR, textStatus, errorThrown) { 
								var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
								FunctionUtilObj.showMessage(elm, "There was an error trying to update this code. Please try again." + msg);
								FunctionUtilObj.EditFunctionCodeMyFancyPopupObject.hideLoading();
								workflow_menu.show();
							},
							async : false,
						});
					}
					else {
						FunctionUtilObj.showMessage(elm, "There was an error trying to update this code. Please try again.");
						FunctionUtilObj.EditFunctionCodeMyFancyPopupObject.hideLoading();
						workflow_menu.show();
					}
				}
			}
			else
				FunctionUtilObj.showMessage(elm, "The tasks flow diagram has no changes. No need to update the code.");
		}
			
		return status;
	},
	
	generateTasksFlowFromCode : function(elm, do_not_confirm) {
		var status = false;
		
		if (this.create_workflow_file_from_code_url && this.get_tmp_workflow_file_url) {
			status = true;
			
			var edit_function_code = $(elm).parent().closest(".edit_function_code");
			var ui_elm = edit_function_code.children("#ui");
			var old_code_id = ui_elm.attr("code_id");
			var code = this.getEditFunctionCodeEditorValue(ui_elm);
			new_code_id = code ? $.md5(code) : null;
			
			if (!old_code_id || old_code_id != new_code_id) {
				if (do_not_confirm || this.auto_convert || confirm("Do you wish to update this workflow accordingly with the code in the editor?")) {
					status = false;
				
					var workflow_menu = ui_elm.find(" > .taskflowchart > .workflow_menu");
					var WF = myWFObj.getTaskFlowChart();
					
					this.EditFunctionCodeMyFancyPopupObject.showLoading();
					workflow_menu.hide();
					
					$.ajax({
						type : "post",
						url : this.create_workflow_file_from_code_url,
						data : code,
						dataType : "text",
						success : function(data, textStatus, jqXHR) {
							if (typeof jquery_native_xhr_object != "undefined" && jquery_native_xhr_object && typeof isAjaxReturnedResponseLogin == "function" && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
								showAjaxLoginPopup(jquery_native_xhr_object.responseURL, create_workflow_file_from_code_url, function() {
									FunctionUtilObj.generateTasksFlowFromCode(elm, true);
								});
							else if (data == 1) {
								var previous_callback = WF.TaskFile.on_success_read;
								
								WF.TaskFile.on_success_read = function(data, text_status, jqXHR) {
									if (!data)
										WF.StatusMessage.showError("There was an error trying to load the workflow's tasks.");
									else {
										ui_elm.attr("code_id", new_code_id);
										ui_elm.attr("workflow_id", $.md5(JSON.stringify(data)) );
									
										WF.TaskSort.sortTasks();
										
										status = true;
									}
									
									WF.TaskFile.on_success_read = previous_callback;
								}
								
								WF.TaskFile.reload(get_tmp_workflow_file_url, {"async": true});
							}
							else 
								WF.StatusMessage.showError("There was an error trying to update this workflow. Please try again." + (data ? "\n" + data : ""));
						
							FunctionUtilObj.EditFunctionCodeMyFancyPopupObject.hideLoading();
							workflow_menu.show();
						},
						error : function(jqXHR, textStatus, errorThrown) { 
							var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
							WF.StatusMessage.showError("There was an error trying to update this workflow. Please try again." + msg);
							
							FunctionUtilObj.EditFunctionCodeMyFancyPopupObject.hideLoading();
							workflow_menu.show();
						},
						async : false,
					});
				}
			}
			else
				this.showMessage(elm, "The code has no changes. No need to update the tasks flow diagram.");
		}
		
		return status;
	},
	
	sortWorkflowTask : function(elm, sort_type) {
		var WF = myWFObj.getTaskFlowChart();
		
		WF.getMyFancyPopupObj().init({
			parentElement: $(elm).closest(".edit_function_code").find("#" + WF.TaskFlow.main_tasks_flow_obj_id),
			popup_class: "edit_function_popup",
		});
		WF.getMyFancyPopupObj().showOverlay();
		WF.getMyFancyPopupObj().showLoading();
		
		if (!sort_type)
			sort_type = prompt("Please choose the sort type that you wish? You can choose 1, 2, 3 or 4.");
		
		if (sort_type) {
			WF.TaskSort.sortTasks(sort_type);
			WF.StatusMessage.showMessage("Done sorting tasks based in the sort type: " + sort_type + ".");
		}
		
		WF.getMyFancyPopupObj().hidePopup();
	},
	
	flipTasksFlowPanelsSide : function(elm) {
		var WF = myWFObj.getTaskFlowChart();
		WF.ContextMenu.flipPanelsSide();
	},
	
	createEditor : function(textarea) {
		if (ace && ace.edit && textarea) {
			var parent = $(textarea).parent();
			
			ace.require("ace/ext/language_tools");
			var editor = ace.edit(textarea);
			editor.setTheme("ace/theme/chrome");
			editor.session.setMode("ace/mode/php");
			editor.setAutoScrollEditorIntoView(true);
			editor.setOption("minLines", 30);
			editor.setOptions({
				enableBasicAutocompletion: true,
				enableSnippets: true,
				enableLiveAutocompletion: false,
			});
		
			this.EditFunctionCodeEditor = editor;
			
			parent.find("textarea.ace_text-input").removeClass("ace_text-input"); //fixing problem with scroll up, where when focused or pressed key inside editor the page scrolls to top
			
			editor.focus();
		}
	},
	
	prettyPrintCode : function(elm) {
		if (typeof MyHtmlBeautify != "undefined") {
			var code = FunctionUtilObj.getEditFunctionCodeEditorValue(elm);
			code = MyHtmlBeautify.beautify(code);
			code = code.replace(/^\s+/g, "").replace(/\s+$/g, "");
			
			FunctionUtilObj.setEditFunctionCodeEditorValue(elm, code);
		}
	},

	setWordWrap : function(elm) {
		if (FunctionUtilObj.EditFunctionCodeEditor) {
			var wrap = $(elm).attr("wrap") != 1 ? false : true;
			$(elm).attr("wrap", wrap ? 0 : 1);
		
			FunctionUtilObj.EditFunctionCodeEditor.getSession().setUseWrapMode(wrap);
			FunctionUtilObj.showMessage(elm, "Wrap is now " + (wrap ? "enable" : "disable"));
		}
	},
	
	openEditorSettings : function(elm) {
		if (FunctionUtilObj.EditFunctionCodeEditor) {
			if (FunctionUtilObj.EditFunctionCodeEditor.execCommand("showSettingsMenu"))
				setTimeout(function() {
					var ace_settings_menu = $("#ace_settingsmenu").parent().parent();
					ace_settings_menu.css("z-index", $(elm).closest(".edit_function_code").css("z-index"));
					
					//prepare font size option
					var input = $("#ace_settingsmenu input#setFontSize");
					
					if (input[0]) {
						var value = input.val();
						var title = "eg: 12px, 12em, 12rem, 12pt or 120%";
						
						input.attr("title", title).attr("placeHolder", title);
						input.after('<div style="text-align:right; opacity:.5;">' + title + '</div>');
						
						if ($.isNumeric(value))
							input.val(value + "px");
						
						if (input.data("with_keyup_set") != 1) {
							input.data("with_keyup_set", 1);
							
							input.on("keyup", function() {
								var v = $(this).val();
								
								if (v.match(/([0-9]+(\.[0-9]*)?|\.[0-9]+)(px|em|rem|%|pt)/i))
									$(this).trigger("blur").focus();
							});
						}
					}
				}, 500);
		}
		else
			FunctionUtilObj.showMessage(elm, "Error trying to open the editor settings...");
	},
	
	showMessage : function(elm, msg) {
		var message_elm = $(elm).closest(".edit_function_code").children(".message");
		message_elm.html(msg).show();
		
		setTimeout(function() { 
			message_elm.html("").hide();
		}, 3000);
	},
	
	onFunctionTaskEditMethodCode : function(elm, popup) {
		//Do not use "this.", but "ProgrammingTaskUtil." instead, bc if we assign this function to a variable (var x = ProgrammingTaskUtil.onProgrammingTaskChooseImageUrl), the "this." will not work.
		if (typeof FunctionUtilObj.on_function_task_edit_method_code_callback == "function") {
			FunctionUtilObj.on_function_task_edit_method_code_callback(elm, popup);
		}
	},
};
