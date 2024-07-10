var PageTaskPropertyObj = {
	templates : null,
	on_choose_block_callback : null,
	on_choose_include_callback : null,
	is_page_hard_coded_url : null,
	get_page_settings_url : null,
	edit_page_admin_panel_url : null,
	diagram_path : null,
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		var task_html_element = properties_html_elm.find(".page_task_html");
		task_html_element.tabs();
		
		PresentationTaskUtil.onLoadTaskProperties(task_html_element, task_id, task_property_values);
		
		//prepare file name
		var task_label = myWFObj.getTaskFlowChart().TaskFlow.getTaskLabelByTaskId(task_id);
		task_html_element.find(".file_name input").val(task_label);
		
		//prepare template
		if (PageTaskPropertyObj.templates) {
			var html = '<option value="">-- DEFAULT --</option>';
			
			$.each(PageTaskPropertyObj.templates, function(idx, template) {
				html += '<option>' + template + '</option>';
			});
			
			var template_select = task_html_element.find(".template > select");
			template_select.html(html);
			
			var selected_template = task_property_values["template"];
			template_select.val(selected_template);
		}
		
		//prepare authentication
		PageTaskPropertyObj.loadAuthenticationUsers(task_html_element, task_property_values["authentication_users"]);
		
		//prepare page blocks, includes and template params
		if (task_property_values["page_settings"] && $.isPlainObject(task_property_values["page_settings"])) {
			PageTaskPropertyObj.loadPageRegionBlocks(task_html_element, task_property_values["page_settings"]["regions_blocks"]);
			PageTaskPropertyObj.loadPageIncludes(task_html_element, task_property_values["page_settings"]["includes"]);
			PageTaskPropertyObj.loadPageTemplateParams(task_html_element, task_property_values["page_settings"]["template_params"]);
		}
		
		//check if file is hard coded
		if (PageTaskPropertyObj.is_page_hard_coded_url && task_label) {
			task_html_element.prepend('<div class="loading">Loading...</div>');
			
			$.ajax({
				type : "get",
				url : PageTaskPropertyObj.is_page_hard_coded_url.replace("#entities#", task_label),
				dataType : "text",
				success : function(text, textStatus, jqXHR) {
					var data = null;
					
					if (text) {
						try  {
							data = $.parseJSON(text);
						}
						catch(e) {
							//is a new file, so is valid by default
							if (("" + text).indexOf("File Not Found") != -1) {
								data = {};
								data[task_label] = false;
							}
						}
					}
					
					task_html_element.children(".loading").remove();
					
					if (data && data.hasOwnProperty(task_label)) {
						if (data[task_label]) {
							var msg = 'It appears that this file was hard coded. If you continue, the current file code will be overwriting...';
							msg += PageTaskPropertyObj.edit_page_admin_panel_url ? '<br>To check the current code please click <a href="#">here</a>' : '';
							task_html_element.prepend('<div class="invalid_file">' + msg + '</div>');
							
							task_html_element.find(" > .invalid_file > a").click(function() {
								PageTaskPropertyObj.openEditPageAdminPanelPopup( task_html_element.children()[0] );
							});
						}
					}
					else
						task_html_element.prepend('<div class="error">Error trying to check if page is a valid file. Please close and reopen this task properties.</div>');
				},
				error : function(jqXHR, textStatus, errorThrown) { 
					var msg = (errorThrown ? errorThrown + " error.\n" : "") + "Error trying to check if page is a valid file. Please close and reopen this task properties.";
					task_html_element.children(".loading").remove();
					task_html_element.prepend('<div class="error">' + msg + '</div>');
				},
			});
		}
	},
	
	continueLoadingTaskProperties : function(task_html_element, task_id, task_property_values) {
		
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		var task_html_element = properties_html_elm.find(".page_task_html");
		var task_label = task_html_element.find(".file_name input").val();
		var label_obj = {label: task_label};
		
		if (PresentationTaskUtil.isTaskFileLabelValid(label_obj, task_id)) {
			//update label
			var WF = myWFObj.getTaskFlowChart();
			var task = WF.TaskFlow.getTaskById(task_id);
			WF.TaskFlow.getTaskLabelElement(task).html(task_label);
			PresentationTaskUtil.onCompleteLabel(task_id);
			
			return PresentationTaskUtil.onSubmitTaskProperties(properties_html_elm, task_id, task_property_values);
		}
		
		return false;
	},
	
	onTaskCloning : function(task_id) {
		var WF = myWFObj.getTaskFlowChart();
		var j_task = WF.TaskFlow.getTaskById(task_id);
		var main_tasks_flow_obj = $("#" + WF.TaskFlow.main_tasks_flow_obj_id);
		var task_parent = WF.TaskFlow.getTaskParentTasks(j_task);
		task_parent = task_parent[0];
		
		if (!task_parent || $(task_parent).is(main_tasks_flow_obj)) {
			onTaskCloning(task_id);
			return true;
		}
		
		//delete task
		WF.TaskFlow.deleteTask(task_id, {confirm: false});
		WF.StatusMessage.showError("This task cannot be dropped here!");
	},
	
	onCheckLabel : function(label_obj, task_id) {
		//if (label_obj && typeof label_obj.label == "string") //spaces are allowed in files including at the begin and end.
		//	label_obj.label = label_obj.label.replace(/(^\s+|\s+$)/g, ""); //trim
		
		return PresentationTaskUtil.onCheckLabel(label_obj, task_id);
	},

	onCompleteLabel : function(task_id) {
		var WF = myWFObj.getTaskFlowChart();
		var label_elm = WF.TaskFlow.getTaskLabelElementByTaskId(task_id);
		WF.TaskFlow.tasks_properties[task_id]["file_name"] = label_elm.text();
		
		PresentationTaskUtil.onCompleteLabel(task_id);
		
		return true;
	},
	
	/* UTILS - Authentication Functions */
	
	loadAuthenticationUsers : function(task_html_element, authentication_users) {
		//prepare authentication
		if (PresentationTaskUtil.users_management_admin_panel_url)
			task_html_element.find(" > .authentication > .users_management_admin_panel").show();
		else
			task_html_element.find(" > .authentication > .users_management_admin_panel").hide();
		
		PageTaskPropertyObj.onChangeUserAuthentication( task_html_element.find(" > .authentication > .authentication_type > select")[0] );
		
		if (authentication_users) {
			if (authentication_users.hasOwnProperty("user_type_id"))
				authentication_users = [ authentication_users ];
			
			if ($.isArray(authentication_users) || $.isPlainObject(authentication_users)) {
				var add_elm = task_html_element.find(".authentication > .authentication_users > table > thead .add");
			
				$.each(authentication_users, function(idx, authentication_user) {
					var row = PageTaskPropertyObj.addAuthenticationUser(add_elm[0]);
					var user_type_id_elm = row.find(".user_type_id select");
					
					user_type_id_elm.val(authentication_user["user_type_id"]);
					
					//in case the values don't exist, add them...
					if (user_type_id_elm.val() != authentication_user["user_type_id"]) 
						user_type_id_elm.append('<option selected>NOT IN DB: ' + authentication_user["user_type_id"] + '</option>');
				});
			}
		}
	},
	
	onChangeUserAuthentication : function(elm) {
		elm = $(elm);
		
		if (elm.val() == "authenticated")
			elm.parent().closest(".authentication").children(".authentication_users").show();
		else
			elm.parent().closest(".authentication").children(".authentication_users").hide();
	},
	
	addAuthenticationUser : function(elm) {
		var tbody = $(elm).parent().closest("table").children("tbody");
		tbody.children(".no_users").hide();
		var index = getListNewIndex(tbody);
		
		var tr = '<tr>'
			+ '		<td class="user_type_id">'
			+ '			<select class="task_property_field" name="authentication_users[' + index + '][user_type_id]">';
			
		if (PresentationTaskUtil.available_user_types)
			$.each(PresentationTaskUtil.available_user_types, function(user_type_id, user_type_name) {
				tr += '		<option value="' + user_type_id + '">' + user_type_name + '</option>';
			});
			
		tr += '			</select>'
			+ '		</td>'
			+ '		<td class="actions">'
			+ '			<i class="icon remove" onClick="PageTaskPropertyObj.removeAuthenticationUser(this)"></i>'
			+ '		</td>'
			+ '	</tr>';
		
		tr = $(tr);
		
		tbody.append(tr);
		
		return tr;
	},
	
	removeAuthenticationUser : function(elm) {
		var tr = $(elm).parent().closest('tr');
		var tbody = tr.parent().closest("tbody");
		tr.remove();
		
		if (tbody.children().length == 1)
			tbody.children(".no_users").show();
	},
	
	/* UTILS - Page Settings Functions */
	
	updateSettingsFromPageFile : function(elm) {
		if (this.get_page_settings_url) {
			var task_html_element = $(elm).parent().closest(".page_task_html");
			var page = task_html_element.find(".file_name input").val();
			
			$.ajax({
				type : "get",
				url : this.get_page_settings_url.replace("#entity#", page),
				dataType : "json",
				success : function(data, textStatus, jqXHR) {
					if (data) {
						//loading regions blocks
						var regions = data["regions"];
						var regions_blocks_ul = task_html_element.find(".regions_blocks > ul");
						var lis = regions_blocks_ul.children();
						lis.filter(":not(.no_page_regions_blocks)").remove();
						lis.filter(".no_page_regions_blocks").show();
						
						var new_regions = [];
						
						if (regions)
							$.each(regions, function(region, region_blocks) {
								region = convertToNormalTextIfValueIsSurroundedWithQuotesAndIsNotAPHPCode(region);
								
								if (region_blocks) {
									$.each(region_blocks, function(idx, region_block) {
										var block = region_block["block"];
										var proj = region_block["proj"];
										
										block = convertToNormalTextIfValueIsSurroundedWithQuotesAndIsNotAPHPCode(block);
										proj = convertToNormalTextIfValueIsSurroundedWithQuotesAndIsNotAPHPCode(proj);
										
										new_regions.push({
											"region": region,
											"block": block,
											"project": proj,
										});
									});
								}
								else
									new_regions.push({
										"region": region,
									});
							});
						
						PageTaskPropertyObj.loadPageRegionBlocks(task_html_element, new_regions);
						
						//loading includes
						var includes = data["includes"];
						var includes_ul = task_html_element.find(".includes > ul");
						var lis = includes_ul.children();
						lis.filter(":not(.no_page_includes)").remove();
						lis.filter(".no_page_includes").show();
						
						PageTaskPropertyObj.loadPageIncludes(task_html_element, includes);
						
						//loading template params
						var template_params = data["template_params"];
						var template_params_ul = task_html_element.find(".template_params > ul");
						var lis = template_params_ul.children();
						lis.filter(":not(.no_page_template_params)").remove();
						lis.filter(".no_page_template_params").show();
						
						var new_template_params = [];
						
						if (template_params)
							$.each(template_params, function(param_name, param_value) {
								param_name = convertToNormalTextIfValueIsSurroundedWithQuotesAndIsNotAPHPCode(param_name);
								param_value = convertToNormalTextIfValueIsSurroundedWithQuotesAndIsNotAPHPCode(param_value);
								
								new_template_params.push({
									"name": param_name,
									"value": param_value,
								});
							});
						
						PageTaskPropertyObj.loadPageTemplateParams(task_html_element, new_template_params);
					}
					else
						StatusMessageHandler.showError((errorThrown ? errorThrown + " error.\n" : "") + "Error trying to get page settings.\nPlease try again...");
				},
				error : function(jqXHR, textStatus, errorThrown) { 
					StatusMessageHandler.showError((errorThrown ? errorThrown + " error.\n" : "") + "Error trying to get page settings.\nPlease try again...");
				},
			});
		}
	},
	
	openEditPageAdminPanelPopup : function(elm) {
		var task_html_element = $(elm).parent().closest(".page_task_html");
		var page = task_html_element.find(".file_name input").val();
		
		var popup_elm = task_html_element.find(".edit_page_admin_panel_popup");
		var iframe = popup_elm.children("iframe");
		var url = null;
		
		if (!iframe.attr("src"))
			url = this.edit_page_admin_panel_url.replace("#entity#", page);
		
		var popup = new MyFancyPopupClass();
		popup.init({
			elementToShow: popup_elm,
			type: "iframe",
			url: url,
		});
		popup.showPopup();
	},
	
	/* UTILS - Page Settings - Region-Blocks Functions */
	
	loadPageRegionBlocks : function(task_html_element, page_regions_blocks) {
		if (page_regions_blocks) {
			if (page_regions_blocks.hasOwnProperty("region") || page_regions_blocks.hasOwnProperty("block") || page_regions_blocks.hasOwnProperty("project"))
				page_regions_blocks = [ page_regions_blocks ];
			
			var add_elm = task_html_element.find(".regions_blocks > label > .add");
			
			if ($.isArray(page_regions_blocks) || $.isPlainObject(page_regions_blocks))
				$.each(page_regions_blocks, function(idx, page_region_block) {
					PageTaskPropertyObj.loadPageRegionBlock(task_html_element, add_elm[0], page_region_block["region"], page_region_block["block"], page_region_block["project"]);
				});
		}
	},
	
	loadPageRegionBlock : function(task_html_element, add_elm, region, block, project) {
		var row = this.addPageRegionBlock(add_elm, region);
		
		if (row) {
			row.find("input.block").val(block);
			row.find("input.project").val(project);
			
			var page = task_html_element.find(".file_name input").val();
			var b = block ? block.substr(this.diagram_path.length) : "";
			b = b.substr(0, 1) == "/" ? b.substr(1) : b;
			
			if (b == page) {
				row.addClass("reserved").find(".search, .remove").remove();
				row.find("input.block").unbind("click").off().removeAttr("onClick");
			}
		}
	},
	
	addUserPageRegionBlock : function(elm) {
		var region = prompt("Please write the region name that you wish:");
		return (region && region != '') ? this.addPageRegionBlock(elm, region) : null;
	},
	
	addPageRegionBlock : function(elm, region) {
		var ul = $(elm).parent().closest(".regions_blocks").children("ul");
		ul.children(".no_page_regions_blocks").hide();
		var index = getListNewIndex(ul);
		
		var li = '<li>'
				+ '<label title="' + region + '">"' + region + '":</label>'
				+ '<input class="task_property_field region" type="hidden" name="page_settings[regions_blocks][' + index + '][region]" value="' + region + '"/>'
				+ '<input class="task_property_field block" type="text" name="page_settings[regions_blocks][' + index + '][block]" readonly onClick="PageTaskPropertyObj.onChoosePageBlock(this)"/>'
				+ '<input class="task_property_field project" type="hidden" name="page_settings[regions_blocks][' + index + '][project]"/>'
				+ '<i class="icon add" onclick="PageTaskPropertyObj.addPageRegionBlock(this, \'' + region + '\')" title="Add new block for region: ' + region + '">Add</i>'
				+ '<i class="icon up" onclick="PageTaskPropertyObj.moveUpPageRegionBlock(this)" title="Move up this region-block">Move up</i>'
				+ '<i class="icon down" onclick="PageTaskPropertyObj.moveDownPageRegionBlock(this)" title="Move down this region-block">Move up</i>'
				+ '<i class="icon remove" onClick="PageTaskPropertyObj.removePageRegionBlock(this)"></i>'
			+ '</li>';
		
		li = $(li);
		ul.append(li);
		
		return li;
	},
	
	removePageRegionBlock : function(elm) {
		var li = $(elm).parent();
		var ul = li.parent();
		
		li.remove();
		
		if (ul.children().length == 1)
			ul.children(".no_page_regions_blocks").show();
	},
	
	moveUpPageRegionBlock : function(elm) {
		var item = $(elm).parent();
		var prev_item = item.prev();
		
		if (prev_item[0]) {
			item.parent()[0].insertBefore(item[0], prev_item[0]);
			
			switchListChildrenIndexes(item.parent(), item, prev_item);
		}
	},
	
	moveDownPageRegionBlock : function(elm) {
		var item = $(elm).parent();
		var next_item = item.next();
		
		if (next_item[0]) {
			item.parent()[0].insertBefore(next_item[0], item[0]);
			
			switchListChildrenIndexes(item.parent(), item, next_item);
		}
	},
	
	onChoosePageBlock : function(elm) {
		if (typeof this.on_choose_block_callback == "function")
			this.on_choose_block_callback(elm);
	},
	
	/* UTILS - Page Settings - Includes Functions */
	
	loadPageIncludes : function(task_html_element, includes) {
		if (includes) {
			if (includes.hasOwnProperty("path") || includes.hasOwnProperty("once"))
				includes = [ includes ];
		
			var add_elm = task_html_element.find(".includes > label > .add");
			
			if ($.isArray(includes) || $.isPlainObject(includes))
				$.each(includes, function(idx, include) {
					var row = PageTaskPropertyObj.addPageInclude(add_elm[0]);
					row.find("input.include_path").val(include["path"]);
					
					if (include["once"])
						row.find("input.include_once").attr("checked", "checked").prop("checked", true);
				});
		}
	},
	
	addPageInclude : function(elm) {
		var ul = $(elm).parent().closest(".includes").children("ul");
		ul.children(".no_page_includes").hide();
		var index = getListNewIndex(ul);
		
		var li = '<li>'
				+ '<input class="task_property_field include_path" type="text" name="page_settings[includes][' + index + '][path]"/>'
				+ '<input class="task_property_field include_once" type="checkbox" name="page_settings[includes][' + index + '][once]" value="1" title="Check to include file only once"/>'
				+ '<i class="icon search" onclick="PageTaskPropertyObj.onChoosePageInclude(this)">Search</i>'
				+ '<i class="icon remove" onClick="PageTaskPropertyObj.removePageInclude(this)"></i>'
			+ '</li>';
		
		li = $(li);
		ul.append(li);
		
		return li;
	},
	
	removePageInclude : function(elm) {
		var li = $(elm).parent();
		var ul = li.parent();
		
		li.remove();
		
		if (ul.children().length == 1)
			ul.children(".no_page_includes").show();
	},
	
	onChoosePageInclude : function(elm) {
		if (typeof this.on_choose_include_callback == "function")
			this.on_choose_include_callback(elm);
	},
	
	/* UTILS - Page Settings - Template Params Functions */
	
	loadPageTemplateParams : function(task_html_element, page_template_params) {
		if (page_template_params) {
			if (page_template_params.hasOwnProperty("name") || page_template_params.hasOwnProperty("value"))
				page_template_params = [ page_template_params ];
		
			var add_elm = task_html_element.find(".template_params > label > .add");
			
			if ($.isArray(page_template_params) || $.isPlainObject(page_template_params))
				$.each(page_template_params, function(idx, page_template_param) {
					var row = PageTaskPropertyObj.addPageTemplateParam(add_elm[0], page_template_param["name"]);
					
					if (row) 
						row.find("input.param_value").val(page_template_param["value"]);
				});
		}
	},
	
	addUserPageTemplateParam : function(elm) {
		var param = prompt("Please write the param name that you wish:");
		
		if (param)
			param = param.replace(/^[ \n\r\t]+/g, "").replace(/[ \n\r\t]+$/g, "");
		
		var ul = $(elm).parent().closest(".template_params").children("ul");
		var exists = ul.find("input.param_name[value='" + param + "']").length > 0;
		
		if (exists) {
			alert("Template param repeated!");
			return null;
		}
		
		return (param && param != '') ? this.addPageTemplateParam(elm, param) : null;
	},
	
	addPageTemplateParam : function(elm, param) {
		var ul = $(elm).parent().closest(".template_params").children("ul");
		ul.children(".no_page_template_params").hide();
		var index = getListNewIndex(ul);
		
		var li = '<li>'
				+ '<label title="' + param + '">"' + param + '":</label>'
				+ '<input class="task_property_field param_name" type="hidden" name="page_settings[template_params][' + index + '][name]" value="' + param + '"/>'
				+ '<input class="task_property_field param_value" type="text" name="page_settings[template_params][' + index + '][value]"/>'
				+ '<i class="icon remove" onClick="PageTaskPropertyObj.removePageTemplateParam(this)"></i>'
			+ '</li>';
		
		li = $(li);
		ul.append(li);
		
		return li;
	},
	
	removePageTemplateParam : function(elm) {
		var li = $(elm).parent();
		var ul = li.parent();
		
		li.remove();
		
		if (ul.children().length == 1)
			ul.children(".no_page_template_params").show();
	},
};
