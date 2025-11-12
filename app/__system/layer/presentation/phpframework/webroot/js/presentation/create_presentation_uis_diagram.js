/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

var choosePageBlockFromFileManagerTree = null;
var choosePresentationIncludeFromFileManagerTree = null;
var tasks_details = {};
var loaded_tables_props = {};
var tasks_count_control = 0;
var auto_scroll_active = true;
var MyDiagramUIFancyPopup = new MyFancyPopupClass();

$(function () {
	$(window).bind('beforeunload', function () {
		if (taskFlowChartObj.TaskFile.isWorkFlowChangedFromLastSaving()) {
			if (window.parent && window.parent.iframe_overlay)
				window.parent.iframe_overlay.hide();
			
			return "If you proceed your changes won't be saved. Do you wish to continue?";
		}
		
		return null;
	});
	
	//prepare top_bar
	$(".taskflowchart").addClass("with_top_bar_menu fixed_properties").children(".workflow_menu").addClass("top_bar_menu");
	
	//init auto save
	addAutoSaveMenu(".taskflowchart.with_top_bar_menu .workflow_menu.top_bar_menu li.save", "onToggleWorkflowAutoSave");
	enableAutoSave(onToggleWorkflowAutoSave);
	initAutoSave(".taskflowchart.with_top_bar_menu .workflow_menu.top_bar_menu li.save a");
	
	$(".taskflowchart.with_top_bar_menu .workflow_menu.top_bar_menu li.auto_save_activation").addClass("with_padding");
	
	//init workflow
	taskFlowChartObj.TaskFlow.default_connection_connector = "Straight";
	taskFlowChartObj.TaskFlow.default_connection_overlay = "Forward Arrow";
	taskFlowChartObj.TaskFlow.available_connection_connectors_type = ["Straight"];
	taskFlowChartObj.TaskFlow.available_connection_overlays_type = ["Forward Arrow"];
	taskFlowChartObj.TaskFlow.available_connection_overlays[0][1]["location"] = 0.999; //Sets the arrow to the end to the conneciton line. Note that this cannot be 1 or we will get a javascript error from jsplumb. This is only used by jsplumb. If Leaderline is used, we won't need the "location" attribute.
	
	//init trees
	choosePageUrlFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeAllThatIsNotPagesFromTree,
	});
	choosePageUrlFromFileManagerTree.init("choose_page_url_from_file_manager");
	
	chooseImageUrlFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeAllThatIsNotAPossibleImageFromTree,
	});
	chooseImageUrlFromFileManagerTree.init("choose_image_url_from_file_manager");
	
	chooseWebrootFileUrlFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeAllThatIsNotWebrootFileFromTree,
	});
	chooseWebrootFileUrlFromFileManagerTree.init("choose_webroot_file_url_from_file_manager");
	
	choosePageBlockFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeAllThatIsNotBlocksFromTree,
	});
	choosePageBlockFromFileManagerTree.init("choose_page_block_from_file_manager");
	
	choosePresentationIncludeFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeAllInvalidPresentationIncludePagesFromTree,
	});
	choosePresentationIncludeFromFileManagerTree.init("choose_presentation_include_from_file_manager");
	
	choosePropertyVariableFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeObjectPropertiesAndMethodsAndFunctionsFromTreeForVariables,
	});
	choosePropertyVariableFromFileManagerTree.init("choose_property_variable_from_file_manager .class_prop_var");
	
	chooseBusinessLogicFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeObjectPropertiesAndMethodsAndFunctionsFromTreeForBusinessLogic,
	});
	chooseBusinessLogicFromFileManagerTree.init("choose_business_logic_from_file_manager");
	
	chooseQueryFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeMapsAndOtherIbatisNodesFromTree,
	});
	chooseQueryFromFileManagerTree.init("choose_query_from_file_manager");
	
	chooseHibernateObjectMethodFromFileManagerTree = new MyTree({
		multiple_selection : false,
		toggle_selection : false,
		toggle_children_on_click : true,
		ajax_callback_before : prepareLayerNodes1,
		ajax_callback_after : removeMapsAndOtherHbnNodesFromTree,
	});
	chooseHibernateObjectMethodFromFileManagerTree.init("choose_hibernate_object_method_from_file_manager");
	
	//init ui
	var old_file_code = $(".confirm_save .file_code .old_file_code pre");
	var new_file_code = $(".confirm_save .file_code .new_file_code pre");
	
	old_file_code.scroll(function() {
		if (auto_scroll_active) {
			new_file_code.scrollTop( $(this).scrollTop() );
			//new_file_code.scrollLeft( $(this).scrollLeft() );
		}
	});
	
	new_file_code.scroll(function() {
		if (auto_scroll_active) {
			old_file_code.scrollTop( $(this).scrollTop() );
			//old_file_code.scrollLeft( $(this).scrollLeft() );
		}
	});
	
	//add default function to reset the top positon of the tasksflow panels, if with_top_bar class exists 
	onResizeTaskFlowChartPanels(taskFlowChartObj, 0);
	
	MyFancyPopup.hidePopup();
});

function onToggleFullScreen(in_full_screen) {
	taskFlowChartObj.resizePanels();
}

function onIncludeBlockTaskChooseFile(elm) {
	var popup = $("#choose_page_block_from_file_manager");
	
	MyDiagramUIFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		
		targetField: $(elm).parent().children("input.block"),
		updateFunction: chooseIncludeBlock
	});
	
	MyDiagramUIFancyPopup.showPopup();
}

function chooseIncludeBlock(elm) {
	var node = choosePageBlockFromFileManagerTree.getSelectedNodes();
	node = node[0];
	
	if (node) {
		var a = $(node).children("a");
		var file_path = a.attr("file_path");
		
		if (file_path) {
			var bean_name = a.attr("bean_name");
			var pos = file_path.indexOf("/src/block/");
			
			if (file_path && pos != -1) {
				var project_path = getNodeProjectPath(node);
				project_path = project_path && project_path.substr(project_path.length - 1) == "/" ? project_path.substr(0, project_path.length - 1) : project_path;
				project_path = project_path == selected_project_id ? "" : project_path + "/";
				
				var block_path = file_path.substr(pos + 11);//11 == /src/block/
				block_path = block_path.substr(block_path.length - 4, 1) == "." ? block_path.substr(0, block_path.lastIndexOf(".")) : block_path;
				
				var input_block = MyDiagramUIFancyPopup.settings.targetField;
				input_block.val(block_path);
				input_block.parent().children("input.project").val(project_path);
				
				MyDiagramUIFancyPopup.hidePopup();
			}
			else
				alert("invalid selected block.\nPlease choose a valid block.");
		}
		else
			alert("invalid selected block.\nPlease choose a valid block.");
	}
}

function onPresentationIncludeFileTaskChoosePage(elm) {
	var popup = $("#choose_presentation_include_from_file_manager");
	
	MyDiagramUIFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		
		targetField: $(elm).parent(),
		updateFunction: choosePresentationIncludeFile
	});
	
	MyDiagramUIFancyPopup.showPopup();
}

function choosePresentationIncludeFile(elm) {
	var node = choosePresentationIncludeFromFileManagerTree.getSelectedNodes();
	node = node[0];
	
	if (node) {
		var a = $(node).children("a");
		var file_path = a.attr("file_path");
		var bean_name = a.attr("bean_name");
		var include_path = file_path ? getNodeIncludePath(node, file_path, bean_name) : null;
		
		if (include_path) {
			var p = MyDiagramUIFancyPopup.settings.targetField;
			p.children("input.include_path").val(include_path);
			p.children("input.include_once").attr("checked", "checked").prop("checked", true);
	
			MyDiagramUIFancyPopup.hidePopup();
		}
		else 
			alert("Selected item must be a valid page!\nPlease try again...");
	}
}

function removeAllInvalidPresentationIncludePagesFromTree(ul, data) {
	$(ul).find("i.controller_file, i.undefined_file, i.js_file, i.css_file, i.img_file, i.cache_file, i.controllers_folder, i.caches_folder, i.routers_folder, i.dispatchers_folder").each(function(idx, elm){
		$(elm).parent().parent().remove();
	});
	
	$(ul).find("i.folder").each(function(idx, elm){
		var label = $(elm).parent().children("label").text();
		
		if (label == "webroot" || label == "others") {
			$(elm).parent().parent().remove();
		}
	});
}

function createUIFiles() {
	var tasks = taskFlowChartObj.TaskFlow.getAllTasks();
	
	//check if there are no tasks
	if (tasks.length == 0) 
		taskFlowChartObj.StatusMessage.showError("Please create some tasks first...");
	else {
		//check if there is any task with an undefined db_table
		var invalid_task_label = getTaskLabelWithUndefinedTable(tasks);
		
		if (invalid_task_label) 
			taskFlowChartObj.StatusMessage.showError("The task '" + invalid_task_label + "' has an undefined db table selected. Please correct this before you proceed!");
		else { //if everyting is correct show popup
			var popup = $("#create_uis_files");
			
			//this is very important, otherwise will lead the user to think that if he makes a change in some panel's permissions, that the system will be updated, and it will not, bc the panel settings are only loaded from the workflowtasks in the .step_1 panel!
			var btn = popup.find(".step_1 .button input")[0]; 
			goToStepAutomaticCreation(btn, 1);
			
			MyDiagramUIFancyPopup.init({
				elementToShow: popup,
				parentElement: document,
				onOpen: function() {
					//to be used by external pages like the create_page_presentation_uis_diagram_block.php
					if (typeof onOpenCreateUIFiles == "function")
						onOpenCreateUIFiles(btn);
				},
				onClose: function() {
					//to be used by external pages like the create_page_presentation_uis_diagram_block.php
					if (typeof onCloseCreateUIFiles == "function")
						onCloseCreateUIFiles(btn);
				},
			});
			MyDiagramUIFancyPopup.showPopup();
		}
	}
}

function getTaskLabelWithUndefinedTable(tasks) {
	//check if there is any task with an undefined db_table
	var invalid_task_label = null;
	
	for (var i = 0; i < tasks.length; i++) {
		var task = $(tasks[i]);
		var task_tag = task.attr("tag");
		
		if (task_tag == "listing" || task_tag == "form" || task_tag == "view") {
			var task_id = task.attr("id");
			var task_properties = taskFlowChartObj.TaskFlow.tasks_properties[task_id];
			
			if (!task_properties || !task_properties["choose_db_table"] || !task_properties["choose_db_table"]["db_table"]) {
				invalid_task_label = taskFlowChartObj.TaskFlow.getTaskLabelByTaskId(task_id);
				break;
			}
		}
	}
	
	return invalid_task_label;
}

function loadNewExistentFiles() {
	if (confirm("You are about to load new existent files to this diagram.\nThis action won't afect the current tasks. Only new will be created.\nDo you wish to proceed?")) 
		$.ajax({
			type : "get",
			url : get_current_path_sub_files_url,
			dataType : "json",
			success : function(data, textStatus, jqXHR) {
				if (data) {
					var pages = [];
					
					$.each(data, function(file_name, file_data) {
						if (file_data.hasOwnProperty("properties") && file_data["properties"] && file_data["properties"]["item_type"] == "file" && file_name.substr(file_name.length - 4) == ".php") {
							file_name = file_name.substr(0, file_name.length - 4);
							pages.push(file_name);
						}
					});
					
					if (pages.length > 0) {
						var existent_pages = [];
						var tasks = taskFlowChartObj.TaskFlow.getAllTasks();
						var added = false;
						
						for (var i = 0; i < tasks.length; i++) {
							var task = $(tasks[i]);
							var task_tag = task.attr("tag");
							
							if (task_tag == "page") {
								var task_id = task.attr("id");
								var task_properties = taskFlowChartObj.TaskFlow.tasks_properties[task_id];
								
								if (task_properties && task_properties["file_name"])
									existent_pages.push(("" + task_properties["file_name"]).toLowerCase());
							}
						}
						
						for (var i = 0; i < pages.length; i++) {
							var page_name = pages[i];
							
							if ($.inArray(page_name.toLowerCase(), existent_pages) == -1) {
								added = true;
								
								//create new page task
								var task_id = taskFlowChartObj.ContextMenu.addTaskByType(page_task_type);
								prepareNewExistentPageTask(task_id, page_name);
							}
						}
						
						if (added)
							StatusMessageHandler.showMessage("Done. All existent files added.");
						else
							StatusMessageHandler.showMessage("There are no new files to add...");
					}
					else
						StatusMessageHandler.showError("There are no new files to add...");
				}
				else
					StatusMessageHandler.showError("Error: Couldn't load files. Please try again.");
			},
			error : function(jqXHR, textStatus, errorThrown) { 
				var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
				StatusMessageHandler.showError((errorThrown ? errorThrown + " error.\n" : "") + "Error: Couldn't load files. Please try again." + msg);
			},
		});
}

function prepareNewExistentPageTask(task_id, page_name) {
	if (task_id) {
		taskFlowChartObj.TaskFlow.setTaskLabelByTaskId(task_id, {"label": page_name});
		taskFlowChartObj.TaskFlow.tasks_properties[task_id]["file_name"] = page_name;
		
		//prepare page properties
		$.ajax({
			type : "get",
			url : PageTaskPropertyObj.get_page_settings_url.replace("#entity#", page_name),
			dataType : "json",
			success : function(page_settings, textStatus, jqXHR) {
				if (page_settings) {
					var page_settings_props = {};
					
					if (page_settings["regions"]) {
						page_settings_props["regions_blocks"] = [];
						
						for (var region_id in page_settings["regions"]) {
							var blocks = page_settings["regions"][region_id];
							
							$.each(blocks, function(idx, block) {
								page_settings_props["regions_blocks"].push({
									"region": convertToNormalTextIfValueIsSurroundedWithQuotesAndIsNotAPHPCode(region_id), 
									"block": convertToNormalTextIfValueIsSurroundedWithQuotesAndIsNotAPHPCode(block["block"]), 
									"project" : convertToNormalTextIfValueIsSurroundedWithQuotesAndIsNotAPHPCode(block["proj"])
								});
							});
						}
					}
					
					if (page_settings["includes"]) {
						page_settings_props["includes"] = [];
						
						$.each(page_settings["includes"], function(idx, inc) {
							page_settings_props["includes"].push({
								"path": inc["path"],
								"once": inc["once"]
							});
						});
					}
					
					if (page_settings["template_params"]) {
						page_settings_props["template_params"] = [];
						
						$.each(page_settings["template_params"], function(param_name, param_value) {
							page_settings_props["template_params"].push({
								"name": convertToNormalTextIfValueIsSurroundedWithQuotesAndIsNotAPHPCode(param_name), 
								"value": convertToNormalTextIfValueIsSurroundedWithQuotesAndIsNotAPHPCode(param_value)
							});
						});
					}
					
					taskFlowChartObj.TaskFlow.tasks_properties[task_id]["page_settings"] = page_settings_props;
					taskFlowChartObj.TaskFlow.tasks_properties[task_id]["template"] = page_settings["template"] ? page_settings["template"] : "";
				}
				else
					StatusMessageHandler.showError("Error: Couldn't load \'" + page_name + "\' file's settings correctly. Please try again.");
			},
			error : function(jqXHR, textStatus, errorThrown) { 
				var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
				StatusMessageHandler.showError((errorThrown ? errorThrown + " error.\n" : "") + "Error: Couldn't load \'" + page_name + "\' file's settings correctly. Please try again." + msg);
			},
		});
	}
}

function goBackStepAutomaticCreation(elm, step) {
	var p = $(elm).parent().closest(".myfancypopup");
	p.children(".step").hide().find(" > .button > input").removeAttr("disabled").removeClass("loading");
	p.children(".step_" + step).show();
	
	MyDiagramUIFancyPopup.resizeOverlay();
}

function goToStepAutomaticCreation(elm, step) {
	elm = $(elm);
	var p = elm.parent().closest(".myfancypopup");
	
	p.find(" > .step > .button > input").removeAttr("disabled").removeClass("loading");
	elm.attr("disabled", "disabled").addClass("loading");
	
	switch (step) {
		case 1:
			tasks_details = {};
			
			p.children(".step").hide();
			p.children(".step_1").show();
			
			elm.removeAttr("disabled").removeClass("loading");
			break;
			
		case 2:
			var step_2 = p.children(".step_2");
			var existent_tasks_elm_ul = step_2.find(".existent_tasks > ul");
			existent_tasks_elm_ul.children(":not(.no_existent_tasks)").remove();
			existent_tasks_elm_ul.children(".no_existent_tasks").show();
			
			tasks_details = getWorkflowTasksTables();
			
			if (!$.isEmptyObject(tasks_details)) {
				p.children(".step").hide();
				step_2.show();
				existent_tasks_elm_ul.children(".no_existent_tasks").hide();
				
				var html = getTasksHtmlForAutomaticCreation(tasks_details, true);
				existent_tasks_elm_ul.append(html);
			}
			else
				alert("Cannot continue bc there are no available tables.");
			
			break;
			
		case 3:
			var step_3 = p.children(".step_3");
			step_3.children(".button").hide();
			var existent_tasks_elm = step_3.children(".existent_tasks");
			var existent_tasks_elm_ul = existent_tasks_elm.children("ul");
			existent_tasks_elm_ul.children(":not(.no_existent_tasks)").remove();
			existent_tasks_elm_ul.children(".no_existent_tasks").show();
			
			if (!$.isEmptyObject(tasks_details)) {
				p.children(".step").hide();
				step_3.show();
				existent_tasks_elm_ul.children(".no_existent_tasks").hide();
				
				//prepare brokers
				var active_brokers = {};
				$.each(p.find(".brokers table tbody tr"), function(idx, tr) {
					tr = $(tr);
					var broker_name = tr.attr("broker_name");
					
					if (tr.find(".status input").is(":checked"))
						active_brokers[broker_name] = tr.find(".path input").val();
				});
				
				//prepare tables
				tasks_details = setTasksDetailsTableAliasAndIncludeDBDriver(p.find(" > .step_2 > .existent_tasks > ul"), tasks_details);
				var html = getTasksHtmlForAutomaticCreation(tasks_details, false);
				existent_tasks_elm_ul.append(html);
				
				existent_tasks_elm_ul.find(".services_and_rules").show();
				existent_tasks_elm.children(".error, .warning").hide();
				
				//change tasks_details to have the correct action services to get from ajax
				tasks_details = prepareTaskDetailsWithCorrespondentActionServices(tasks_details, null);
				
				tasks_count_control = 0;
				
				updateMyDiagramUIFancyPopup();
				MyDiagramUIFancyPopup.showLoading();
				prepareServicesAndRulesAutomatically(existent_tasks_elm, tasks_details, active_brokers);
			}
			else
				alert("Cannot continue bc there are no available tables.");
			
			break;
			
		case 4:
			var go_back_btn = p.children(".step_4").find(".button input").first(); //go back button
			go_back_btn.attr("onClick", "goBackStepAutomaticCreation(this, 3)");
			
			//prepare tasks_details with the selected brokers services and rules
			var existent_tasks_elm = p.children(".step_3").children(".existent_tasks");
			tasks_details = prepareTasksDetailsWithServicesAndRulesSettings(existent_tasks_elm, tasks_details);
			//console.log(tasks_details);
			
			checkUISFiles(elm[0], {tasks_details: tasks_details});
			break;
			
		case 5:
			var existent_tasks_elm = p.children(".step_4").children(".existent_tasks");
			tasks_details = prepareTasksDetailsWithFilesValidationsSettings(existent_tasks_elm, tasks_details);
			
			saveUISFiles(elm[0], {tasks_details: tasks_details});
			break;
			
		case 6:
			MyDiagramUIFancyPopup.hidePopup();
			break;
	}
	
	updateMyDiagramUIFancyPopup();
}

function getWorkflowTasksTables() {
	//var tasks = taskFlowChartObj.TaskFlow.getAllTasks();
	var workflow_data = taskFlowChartObj.TaskFile.getWorkFlowData();
	var tasks = workflow_data && workflow_data["tasks"] ? workflow_data["tasks"] : {};
	tasks = JSON.parse(JSON.stringify(tasks)); //clones the tasks object, otherwise all new objects added in this script to this array (tasks_details) will be added to the properties of each task and saved to the workflow XML. WE DO NOT WANT THIS!
	tasks = prepareWorkflowTasksTablesProps(tasks);
	tasks = prepareWorkflowTasksTables(tasks, ["page"]);
	tasks = prepareHardCodedTasks(tasks);
	
	return tasks;
}

function prepareWorkflowTasksTablesProps(tasks, old_tasks) {
	if (tasks)
		for (var task_id in tasks) { 
			var task = tasks[task_id];
			var task_tag = task["tag"];
			
			if ($.inArray(task_tag, ["listing", "form", "view"]) != -1) {
				var task_properties = task["properties"];
				var old_task_properties = old_tasks && old_tasks[task_id] && old_tasks[task_id].hasOwnProperty("properties") ? old_tasks[task_id]["properties"] : null;
				
				if (task_properties && task_properties.hasOwnProperty("attributes") && task_properties["attributes"]) {
					var attributes = task_properties["attributes"];
					
					if (attributes.hasOwnProperty("name") || attributes.hasOwnProperty("active"))
						attributes = [ attributes ];
					
					if ($.isArray(attributes) || $.isPlainObject(attributes)) {
						var attributes_by_name = {};
						
						$.each(attributes, function(key, attribute_props) {
							var attribute_name = attribute_props["name"] ? attribute_props["name"] : key;
							attributes_by_name[attribute_name] = attribute_props;
						});
						
						tasks[task_id]["properties"]["attributes"] = attributes_by_name;
					}
				}
			}
		}
	
	return tasks;
}

function prepareWorkflowTasksTables(tasks, allowed_tags, filter_tasks) {
	var arr = {};
	
	if (tasks)
		for (var task_id in tasks) 
			if (!filter_tasks || $.inArray(task_id, filter_tasks) != -1) {
				var task = tasks[task_id];
				var task_tag = task["tag"];
				
				if (!allowed_tags || $.inArray(task_tag, allowed_tags) != -1) {
					if (task["tasks"]) {
						var inner_tasks_id = [];
						for (var inner_task_id in task["tasks"])
							inner_tasks_id.push(inner_task_id);
						
						task["tasks"] = prepareWorkflowTasksTables(tasks, ["listing", "form", "view"], inner_tasks_id);
					}
					
					arr[task_id] = task;
				}
			}
	
	return arr;
}

//check if there is any page task which is invalid and if yes, filter it
function prepareHardCodedTasks(tasks) {
	var entities = "";
	
	for (var task_id in tasks) {
		var task = tasks[task_id];
		
		if (task["tag"] == "page" && task["label"])
			entities += (entities != "" ? "," : "") + task["label"];
	}
	
	$.ajax({
		type : "get",
		url : are_entities_hard_coded_url.replace("#entities#", entities),
		dataType : "json",
		success : function(data, textStatus, jqXHR) {
			if (data) {
				for (var label in data) 
					if (data[label]) 
						for (var task_id in tasks) 
							if (tasks[task_id]["label"] == label) {
								tasks[task_id]["properties"]["hard_coded"] = true;
								break;
							}
			}
			else
				StatusMessageHandler.showError("Error: Couldn't check if entities files were hard-coded. Please try again.");
		},
		error : function(jqXHR, textStatus, errorThrown) { 
			var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
			StatusMessageHandler.showError((errorThrown ? errorThrown + " error.\n" : "") + "Error: Couldn't check if entities files were hard-coded. Please try again." + msg);
		},
		async: false,
	});
	
	return tasks;
}

function setTasksDetailsTableAliasAndIncludeDBDriver(parent_elm, tasks_details) {
	if (tasks_details)
		for (var idx in tasks_details) {
			var task_details = tasks_details[idx];
			var elm = parent_elm.find('li.task_details_' + task_details["id"]);
			var task_tag = task_details["tag"];
			
			if (task_tag == "listing" || task_tag == "form" || task_tag == "view") {
				tasks_details[idx]["properties"]["choose_db_table"]["db_table_alias"] = elm.find(' > .task_info > .db_table_alias > input').first().val();
				tasks_details[idx]["properties"]["choose_db_table"]["include_db_driver"] = elm.find(' > .task_info > .include_db_driver > input').first().is(":checked") ? 1 : 0;
				
				if (task_tag == "listing" && tasks_details[idx]["properties"]["choose_db_table"]["db_table_parent"])
					tasks_details[idx]["properties"]["choose_db_table"]["db_table_parent_alias"] = elm.find(' > .task_info > .db_table_parent_alias > input').first().val();
			}
			
			tasks_details[idx]["tasks"] = setTasksDetailsTableAliasAndIncludeDBDriver(elm, task_details["tasks"]);
		};
	
	return tasks_details;
}

function getTaskDetailsByTaskId(tasks_details, task_id) {
	if (tasks_details)
		for (var idx in tasks_details) {
			var task_details = tasks_details[idx];
			
			if (task_details["id"] == task_id)
				return task_details;
			else if (task_details["tasks"]) {
				var td = getTaskDetailsByTaskId(task_details["tasks"], task_id);
				
				if (td)
					return td;
			}
		};
	
	return null;
}

function isSameTable(task_details_1, task_details_2, stricted) {
	return task_details_1 && task_details_2 && 
		task_details_1["properties"] && task_details_2["properties"] && 
		task_details_1["properties"]["choose_db_table"] && task_details_2["properties"]["choose_db_table"] && 
		task_details_1["properties"]["choose_db_table"]["db_driver"] == task_details_2["properties"]["choose_db_table"]["db_driver"] && 
		(!stricted || task_details_1["properties"]["choose_db_table"]["db_type"] == task_details_2["properties"]["choose_db_table"]["db_type"]) && 
		task_details_1["properties"]["choose_db_table"]["db_table"] == task_details_2["properties"]["choose_db_table"]["db_table"];
}

function getTasksHtmlForAutomaticCreation(tasks_details, editable) {
	var html = "";
	
	$.each(tasks_details, function(idx, task_details) {
		var hard_coded_html = task_details["properties"]["hard_coded"] ? '<i class="icon warning" title="It appears that this file was hard coded. If you continue, the current file code will be overwriting..."></i>' : '';
		
		html += '<li class="task_details_' + task_details["id"] + '" task_id="' + task_details["id"] + '">'
			+ '<div class="task_info">'
			+ 	hard_coded_html
			+ '	<label class="task_file" task_file="' + task_details["label"] + '">Page Task - ' + task_details["label"] + ':</label>'
			+ '	<i class="icon maximize" onClick="toggleTaskDetails(this)">Toggle</i>'
			+ '</div>'
			+ getInnerTasksHtmlForAutomaticCreation(task_details["tasks"], editable)
		+ '</li>';
	});
	
	return html;
}

function getInnerTasksHtmlForAutomaticCreation(tasks_details, editable) {
	var html = "";
	
	if (tasks_details && !$.isEmptyObject(tasks_details)) {
		html += '<div class="inner_tasks">'
			+ '	<div class="inner_tasks_head">'
			+ '		<label>Inner Tasks:</label>'
			+ '		<i class="icon maximize" onClick="toggleInnerTasks(this)">Toggle</i>'
			+ '	</div>'
			+ '	<ul class="inner_tasks_body">';
		
		$.each(tasks_details, function(idx, task_details) {
			var choose_db_table = task_details["properties"] ? task_details["properties"]["choose_db_table"] : null;
			choose_db_table = choose_db_table ? choose_db_table : {};
			
			var include_db_driver_checked = default_db_driver && default_db_driver == choose_db_table["db_driver"] ? false : true;
			
			//Note: when changing the db_driver in each task, bc there is no include_db_driver in the html, the choose_db_table will not contain the "include_db_driver" prop, so it will not enter in the following condition, which is what we want!
			if (choose_db_table.hasOwnProperty("include_db_driver"))
				include_db_driver_checked = parseInt(choose_db_table["include_db_driver"]) > 0;
			
			var db_table_alias = choose_db_table["db_table_alias"] ? choose_db_table["db_table_alias"] : "";
			var db_table_alias_html = editable ? '<input value="' + db_table_alias + '" placeHolder="Table Alias"/>' : db_table_alias;
			var db_table_parent_html = '';
			var db_table_conditions_html = '';
			var include_db_driver_html = editable ? '<input type="checkbox" value="1"' + (include_db_driver_checked ? " checked" : "") + '/>' : (include_db_driver_checked ? 'true' : 'false');
			
			if (choose_db_table["db_table_parent"]) {
				var db_table_parent_alias = choose_db_table["db_table_parent_alias"] ? choose_db_table["db_table_parent_alias"] : "";
				var db_table_parent_alias_html = editable ? '<input value="' + db_table_parent_alias + '" placeHolder="Table Parent Alias"/>' : db_table_parent_alias;
				
				db_table_parent_html = '<div class="db_table_parent">DB Table Parent: ' + choose_db_table["db_table_parent"] + '</div>';
				db_table_parent_html += '<div class="db_table_parent_alias">DB Table Parent Alias: ' + db_table_parent_alias_html + '</div>';
			}
			
			if (choose_db_table["db_table_conditions"] && choose_db_table["db_table_conditions"].hasOwnProperty("attribute") && choose_db_table["db_table_conditions"]["attribute"]) {
				db_table_conditions_html = '<div class="db_table_conditions">DB Table Conditions: ';
				
				var cas = choose_db_table["db_table_conditions"]["attribute"];
				
				if ($.isArray(cas) || $.isPlainObject(cas)) {
					var exists = false;
					$.each(cas, function(idx, ca) {
						if (ca) {
							var cv = choose_db_table["db_table_conditions"]["value"][idx];
							cv = cv ? cv : "''";
							db_table_conditions_html += (exists ? ' && ' : '') + ca + ' = ' + cv;
							exists = true;
						}
					});
				}
				else if (cas) {
					var cv = choose_db_table["db_table_conditions"]["value"];
					cv = cv ? cv : "''";
					db_table_conditions_html += cas + ' = ' + cv;
				}
				
				db_table_conditions_html += '</div>';
			}
			
			html += '<li class="task_details_' + task_details["id"] + '" task_id="' + task_details["id"] + '">'
				+ '<div class="task_info">'
				+ '	<div class="task_label" task_label="' + task_details["label"] + '">Task Label: ' + task_details["label"] + '</div>'
				+ '	<div class="db_driver" db_driver="' + choose_db_table["db_driver"] + '">DB Driver: ' + choose_db_table["db_driver"] + '</div>'
				+ '	<div class="db_type" db_type="' + choose_db_table["db_type"] + '">DB Type: ' + choose_db_table["db_type"] + '</div>'
				+ '	<div class="db_table" db_table="' + choose_db_table["db_table"] + '">DB Table: ' + choose_db_table["db_table"] + '</div>'
				+ '	<div class="db_table_alias">DB Table Alias: ' + db_table_alias_html + '</div>'
				+ '	' + db_table_parent_html
				+ '	' + db_table_conditions_html
				+ '	<div class="include_db_driver">Hard-Code DB-Driver: ' + include_db_driver_html + '</div>'
				+ '</div>'
				+ '<div class="services_and_rules">'
				+ '	<div class="services_and_rules_head">'
				+ '		<label class="services_and_rules_title">Services and Rules</label>'
				+ '		<i class="icon maximize" onClick="toggleServicesAndRules(this)">Toggle</i>'
				+ '	</div>'
				+ '	<div class="services_and_rules_body">No services or rules detected...</div>'
				+ '</div>'
				+ getInnerTasksHtmlForAutomaticCreation(task_details["tasks"], editable)
			+ '</li>';
		});
		
		html += '</ul>'
			+ '</div>';
	}
	
	return html;
}

function toggleTaskDetails(elm) {
	elm = $(elm);
	var li = elm.parent().closest("li");
	li.find(".task_info").find(".db_driver, .db_type, .db_table, .db_table_alias, .db_table_conditions, .db_table_parent, .db_table_parent_alias, .include_db_driver").toggle();
	li.find(".inner_tasks, .files_statuses, .non_authenticated_template").toggle();
	
	if (elm.hasClass("maximize"))
		elm.removeClass("maximize").addClass("minimize");
	else 
		elm.removeClass("minimize").addClass("maximize");
		
	MyDiagramUIFancyPopup.resizeOverlay(); //update poopup overlay
}

function toggleServicesAndRules(elm) {
	elm = $(elm);
	elm.parent().closest(".services_and_rules").children(".services_and_rules_body").toggle();
	
	if (elm.hasClass("maximize"))
		elm.removeClass("maximize").addClass("minimize");
	else 
		elm.removeClass("minimize").addClass("maximize");
		
	MyDiagramUIFancyPopup.resizeOverlay(); //update poopup overlay
}

function toggleInnerTasks(elm) {
	elm = $(elm);
	elm.parent().closest(".inner_tasks").children(".inner_tasks_body").toggle();
	
	if (elm.hasClass("maximize"))
		elm.removeClass("maximize").addClass("minimize");
	else
		elm.removeClass("minimize").addClass("maximize");
	
	MyDiagramUIFancyPopup.resizeOverlay(); //update poopup overlay
}

function prepareTaskDetailsWithCorrespondentActionServices(tasks_details, parent_task_details) {
	if (tasks_details)
		for (var task_details_index in tasks_details) {
			var task_details = tasks_details[task_details_index];
			var task_tag = task_details["tag"];
			
			if (parent_task_details && (task_tag == "listing" || task_tag == "form" || task_tag == "view")) {
				var services = {};
				var task_actions = task_details["properties"]["action"];
				task_actions = task_actions ? task_actions : {};
				
				if (parent_task_details["tag"] == "page") { //if parent is page
					var db_table = task_details["properties"]["choose_db_table"] ? task_details["properties"]["choose_db_table"]["db_table"] : null;
					var db_table_parent = task_details["properties"]["choose_db_table"] ? task_details["properties"]["choose_db_table"]["db_table_parent"] : null;
					var is_parent_same_table = db_table && db_table_parent && db_table.toLowerCase() == db_table_parent.toLowerCase();
					
					switch (task_tag) {
						case "view": //single form view (no buttons to move to next or previous item)
							services = {
								get: 1, //item from this table
							};
							break;
						case "form": //single form (no buttons to move to next or previous item)
							services = {
								get: 1, //item from this table. 
								insert: task_actions["single_insert"] || task_actions["multiple_insert"], //insert in this db_table
								update: task_actions["single_update"] || task_actions["multiple_update"], //update in this db_table
								update_pks: task_actions["single_update"] || task_actions["multiple_update"], //update in this db_table
								delete: task_actions["single_delete"] || task_actions["multiple_delete"], //delete in this db_table
							};
							
							//Only gets item if there is update or delete action and no ad action, this is, if there is an add action and no update neither delete action it should not get anything.
							if (services["insert"] && !services["update"] && !services["delete"])
								services["get"] = false;
							
							break;
						case "listing": //table with pagination
							services = {
								get_all: db_table_parent && !is_parent_same_table ? "fk" : 1, //items from this table
								count: db_table_parent && !is_parent_same_table ? "fk" : 1, //items count from this table
								insert: task_actions["single_insert"] || task_actions["multiple_insert"], //insert in this db_table
								update: task_actions["single_update"] || task_actions["multiple_update"], //update in this db_table
								update_pks: task_actions["single_update"] || task_actions["multiple_update"], //update in this db_table
								delete: task_actions["single_delete"] || task_actions["multiple_delete"], //delete in this db_table
							};
							break;
					}
				}
				else { //if parent is another task like table, form or view
					var is_same_table = isSameTable(task_details, parent_task_details, false);
					
					if (is_same_table) { //if the same table
						switch (task_tag) {
							case "view": //single form view (no buttons to move to next or previous item)
								services = {
									get: 1, //item from this table
								};
								break;
							case "form": //single form (no buttons to move to next or previous item)
							case "listing": //table with pagination
								services = {
									get: 1, //item from this table
									insert: task_actions["single_insert"] || task_actions["multiple_insert"], //insert in this db_table
									update: task_actions["single_update"] || task_actions["multiple_update"], //update in this db_table
									update_pks: task_actions["single_update"] || task_actions["multiple_update"], //update in this db_table
									delete: task_actions["single_delete"] || task_actions["multiple_delete"], //delete in this db_table
								};
								
								//Only gets item if there is update or delete action and no ad action, this is, if there is an add action and no update neither delete action it should not get anything.
								if (services["insert"] && !services["update"] && !services["delete"])
									services["get"] = false;
								
								break;
						}
					}
					else { //if not the same table
						switch (task_tag) {
							case "view": //form view with multiple items (with previous and next buttons)
								services = {
									get_all: "fk", //items of the fk_table
									count: "fk", //items count of the fk_table
								};
								break;
							case "form": //form view with multiple items (with previous and next buttons)
								var get_all_or_count = task_actions["single_update"] || task_actions["multiple_update"] || task_actions["single_delete"] || task_actions["multiple_delete"];
								
								services = {
									get_all: get_all_or_count ? "fk" : false, //items of the fk_table
									count: get_all_or_count ? "fk" : false, //items count of the fk_table
									insert: task_actions["single_insert"] || task_actions["multiple_insert"], //insert in the fk_table
									update: task_actions["single_update"] || task_actions["multiple_update"], //update in the fk_table
									update_pks: task_actions["single_update"] || task_actions["multiple_update"], //update in the fk_table
									delete: task_actions["single_delete"] || task_actions["multiple_delete"], //delete in the fk_table
								};
								break;
							case "listing":
								services = {
									get_all: "fk", //items of the fk_table
									count: "fk", //items count of the fk_table
									insert: task_actions["single_insert"] || task_actions["multiple_insert"], //insert in the fk_table
									update: task_actions["single_update"] || task_actions["multiple_update"], //update in the fk_table
									update_pks: task_actions["single_update"] || task_actions["multiple_update"], //update in the fk_table
									delete: task_actions["single_delete"] || task_actions["multiple_delete"], //delete in the fk_table
								};
								break;
						}
					}
				}
				
				tasks_details[task_details_index]["properties"]["services"] = services;
			}
			
			tasks_details[task_details_index]["tasks"] = prepareTaskDetailsWithCorrespondentActionServices(task_details["tasks"], task_details);
		}
	
	return tasks_details;
}

/*
prepareServicesAndRulesAutomatically
 |
 |-> if(list|form|view) prepareTaskServicesAndRulesAutomatically
 |		|	(make ajax request if not cached)
 |		|	(var loaded_table_props_id = id based in db_table) 	===> db_table=Modelo
 |		|	(var data = result from ajax)
 |		|	(loaded_tables_props[loaded_table_props_id] = data)
 |		|
 |		|-> if(table has a parent table) 
 |		|		replaceTableServicesAndRulesWithTableParent(data) 
 |		|			(var loaded_table_props_id = id based in db_table_parent) 	===> db_table_parent=null (is not Marca)
 |		|			(loaded_tables_props[loaded_table_props_id] = db_table_parent props from cache or ajax request Synchronous) 
 |		|			(data["tables"][db_table][broker_name]["get_all"] = loaded_tables_props[loaded_table_props_id]["tables"][db_table_parent][broker_name]["relationships"][db_table]) 	===> ["get_all"]=getMarcaModeloChilds
 |		|		
 |		|-> prepareTaskServicesAndRulesManually(data) 
 |			|
 |			|-> loadTask (task__of_db_table, get_all|count|relationships, "")
 |			|-> or loadTask (task__of_db_parent_table, relationships, db_table) THIS WON'T HAPPEN NOW
 |			|		draw get_all rules and other rules for db_table	===> db_table=Modelo where ["relationships"]=getModeloMarcaParent
 |			|		If db_table_parent exists design get_all and count rules based in db_table_parent	===> db_table_parent=null - THIS WON'T HAPPEN NOW
 |			|
 |			|-> prepareTaskAttributesServicesAndRulesAutomatically
 |			|	|	(loop attributes settings e prepara attribute_table_props with attribute_db_table)
 |			|	|
 |			|	|-> prepareTaskAttributeServicesAndRulesAutomatically(attribute_table_props)
 |			|		|	(var loaded_table_props_id = based in the attribute_db_table)
 |			|		|	(loaded_tables_props[loaded_table_props_id] = attribute_db_table props from cache or ajax request Synchronous) 
 |			|		|	
 |			|		|-> prepareTaskAttributeServicesAndRulesManually(loaded_tables_props[loaded_table_props_id])
 |			|			|
 |			|			|-> loadExtraTask(get_all|count, loaded_tables_props[loaded_table_props_id])
 | 			|
 |			|-> if (sub tasks)
 |			| 		prepareServicesAndRulesAutomatically
 |			 		
 |-> if(page) 
 | 		prepareServicesAndRulesAutomatically
 
 ALL THIS METHODS MUST BE SYNCHRONOUS OTHERWISE THIS WON'T WORK AND THERE WILL BE BROKERS THAT WILL REPLACE OTHER BROKERS BEFORE THEY BEING USED WHICH WILL MESS ALL THIS LOGIC
*/
function prepareServicesAndRulesAutomatically(existent_tasks_elm, tasks_details, active_brokers, parent_task_details) {
	//console.log(tasks_details);
	
	if (tasks_details) {
		for (var task_details_index in tasks_details) {
			var task_details = tasks_details[task_details_index];
			var task_tag = task_details["tag"];
			
			if (task_tag == "listing" || task_tag == "form" || task_tag == "view") { //only if task is form, view or table
				if (task_details["properties"]["choose_db_table"]) { 
					tasks_count_control++;
					prepareTaskServicesAndRulesAutomatically(existent_tasks_elm, task_details, active_brokers, parent_task_details);
				}
				else {
					existent_tasks_elm.children(".error").show();
					updateMyDiagramUIFancyPopup();
				}
			}
			else if (task_details["tasks"]) //if task_tag == "page"
				prepareServicesAndRulesAutomatically(existent_tasks_elm, task_details["tasks"], active_brokers, task_details["properties"] && task_details["properties"]["choose_db_table"] ? task_details : null); //only call inner tasks if task_tag is "page", otherwise it can happen the case where the parent_task (task_tag == table or form or view) didn't load yet his table_ui_props but the inner tasks are already loading, which means that the inner tasks won't have the right services, bc it's parent task didn't load his services yet... We should only call this function after each ajax request finish.
		}
	}
	else {
		existent_tasks_elm.parent().closest(".step").children(".button").show();
		MyDiagramUIFancyPopup.hideLoading();
	}
}

function prepareTaskServicesAndRulesAutomatically(existent_tasks_elm, task_details, active_brokers, parent_task_details) {
	//console.log(task_details);
	var choose_db_table = task_details["properties"]["choose_db_table"];
	
	var db_table = choose_db_table["db_table"];
	var lower_db_table = db_table.toLowerCase();
	var db_table_alias = choose_db_table["db_table_alias"];
	var db_table_parent = choose_db_table["db_table_parent"];
	var db_table_parent_alias = choose_db_table["db_table_parent_alias"];
	
	var ab = {};
	for (var broker_name in active_brokers)
		ab[broker_name] = 1;
	
	task_details["properties"]["ui_props"] = {
		ab: ab,
		abf: active_brokers,
		st: db_table,
		sta: {},
	}
	
	if (db_table_parent_alias)
		task_details["properties"]["ui_props"]["sta"][db_table_parent] = db_table_parent_alias;
	
	if (db_table_alias)
		task_details["properties"]["ui_props"]["sta"][db_table] = db_table_alias;
	
	var url = get_tables_ui_props_url.replace("#db_driver#", choose_db_table["db_driver"]).replace("#type#", choose_db_table["db_type"]);
	var loaded_table_props_id = url.hashCode() + "_" + JSON.stringify(task_details["properties"]["ui_props"]).hashCode(); 
	
	existent_tasks_elm.find(".task_details_" + task_details["id"] + " > .services_and_rules > .services_and_rules_body").html("");
	
	if (loaded_tables_props.hasOwnProperty(loaded_table_props_id)) {
		var data = loaded_tables_props[loaded_table_props_id];
		
		if (choose_db_table["db_table_parent"] && data && data["brokers"] && data["tables"] && data["tables"][lower_db_table])
			replaceTableServicesAndRulesWithTableParent(task_details, active_brokers, data);
		
		prepareTaskServicesAndRulesManually(existent_tasks_elm, task_details, active_brokers, parent_task_details, data);
		
		tasks_count_control--;
		
		if (tasks_count_control == 0) {
			existent_tasks_elm.parent().closest(".step").children(".button").show();
			MyDiagramUIFancyPopup.hideLoading();
		}
	}
	else 
		$.ajax({
			type : "post",
			url: url,
			data : task_details["properties"]["ui_props"],
			dataType: "json",
			success: function(data) {
				//console.log(data);
				
				//Note that the db_table_parent will only exist for the "Listing" tasks which are directly child of "Page" tasks. Otherwise this will not exist and will never enter in this if condition bellow.
				if (choose_db_table["db_table_parent"] && data && data["brokers"] && data["tables"] && data["tables"][lower_db_table])
					replaceTableServicesAndRulesWithTableParent(task_details, active_brokers, data);
				
				loaded_tables_props[loaded_table_props_id] = data;
				
				prepareTaskServicesAndRulesManually(existent_tasks_elm, task_details, active_brokers, parent_task_details, data);
				
				tasks_count_control--;
				
				if (tasks_count_control == 0) {
					existent_tasks_elm.parent().closest(".step").children(".button").show();
					MyDiagramUIFancyPopup.hideLoading();
				}
			},
			error : function(jqXHR, textStatus, errorThrown) {
				//call inner tasks, even if on error, so it can create the services html with empty values and show correspondent error
				if (task_details["tasks"])
					prepareServicesAndRulesAutomatically(existent_tasks_elm, task_details["tasks"], active_brokers, task_details);
				
				tasks_count_control--;
				
				if (tasks_count_control == 0) {
					existent_tasks_elm.parent().closest(".step").children(".button").show();
					MyDiagramUIFancyPopup.hideLoading();
				}
				
				var msg = "Error: trying to get services an rules for task " + task_details["id"] + " with the url:" + url;
				if (console && console.log)
					console.log(msg);
				
				if (jqXHR.responseText)
					StatusMessageHandler.showError(msg + "\n" + jqXHR.responseText);
			},
			async: false,
		});
}

//replace "get_all" and "count" services by the relationships services of the db_table_parent
function replaceTableServicesAndRulesWithTableParent(task_details, active_brokers, data) {
	var choose_db_table = task_details["properties"]["choose_db_table"];
	var db_table = choose_db_table["db_table"];
	var lower_db_table = db_table.toLowerCase();
	var db_table_alias = choose_db_table["db_table_alias"];
	var db_table_parent = choose_db_table["db_table_parent"];
	var lower_db_table_parent = db_table_parent.toLowerCase();
	var db_table_parent_alias = choose_db_table["db_table_parent_alias"];
	
	var ab = {};
	for (var broker_name in active_brokers)
		ab[broker_name] = 1;
	
	var ui_props = {
		ab: ab,
		abf: active_brokers,
		st: db_table_parent,
		sta: {},
	}
	
	if (db_table_parent_alias)
		ui_props["sta"][db_table_parent] = db_table_parent_alias;
	
	if (db_table_alias)
		ui_props["sta"][db_table] = db_table_alias;
	
	var url = get_tables_ui_props_url.replace("#db_driver#", choose_db_table["db_driver"]).replace("#type#", choose_db_table["db_type"]);
	var loaded_table_props_id = url.hashCode() + "_" + JSON.stringify(ui_props).hashCode(); 
	
	if (!loaded_tables_props.hasOwnProperty(loaded_table_props_id))
		$.ajax({
			type : "post",
			url: url,
			data : ui_props,
			dataType: "json",
			success: function(parent_data) {
				loaded_tables_props[loaded_table_props_id] = parent_data;
			},
			error : function(jqXHR, textStatus, errorThrown) {
				var msg = "Error: trying to get services an rules for table parent '" + db_table_parent + "' in task " + task_details["id"] + " with the url:" + url;
				
				if (console && console.log)
					console.log(msg);
				
				if (jqXHR.responseText)
					StatusMessageHandler.showError(msg + "\n" + jqXHR.responseText);
			},
			async: false,
		});
	
	var table_parent_services = loaded_tables_props[loaded_table_props_id] && loaded_tables_props[loaded_table_props_id]["tables"] ? loaded_tables_props[loaded_table_props_id]["tables"][lower_db_table_parent] : null;
	
	if (table_parent_services)
		for (var broker_name in active_brokers) 
			if (table_parent_services.hasOwnProperty(broker_name) && data["tables"][lower_db_table].hasOwnProperty(broker_name)) {
				data["tables"][lower_db_table][broker_name]["parents_get_all"] = table_parent_services[broker_name]["relationships"] ? table_parent_services[broker_name]["relationships"][lower_db_table] : null;
				data["tables"][lower_db_table][broker_name]["parents_count"] = table_parent_services[broker_name]["relationships_count"] ? table_parent_services[broker_name]["relationships_count"][lower_db_table] : null;
			}
	
	//console.log(table_parent_services);
	//console.log(data);
}

function prepareTaskServicesAndRulesManually(existent_tasks_elm, task_details, active_brokers, parent_task_details, data) {
	var choose_db_table = task_details["properties"]["choose_db_table"];
	var db_table = choose_db_table["db_table"];
	var lower_db_table = db_table.toLowerCase();
	var db_table_alias = choose_db_table["db_table_alias"];
	var task_services = task_details["properties"]["services"];
	
	var services_and_rules_body_elm = existent_tasks_elm.find(".task_details_" + task_details["id"] + " > .services_and_rules > .services_and_rules_body");
	
	var table_ui_props = data && data.hasOwnProperty("tables") && data["tables"].hasOwnProperty(lower_db_table) ? data["tables"][lower_db_table] : null;
	var brokers_ui_props = data && data.hasOwnProperty("brokers") ? data["brokers"] : null;
	
	task_details["properties"]["ui_props"]["table"] = table_ui_props;
	task_details["properties"]["ui_props"]["brokers"] = brokers_ui_props;
	
	//prepare task services
	for (var service_type in task_services) 
		if (task_services[service_type]) {
			var title  ="";
			var html_type = service_type;
			var relationship_table = "";
			var services_and_rules_task_id = task_details["id"];
			
			switch (service_type) {
				case "get_all": 
					title = "Search/Get all table's rows"; 
					
					if (parent_task_details && task_services[service_type] == "fk") {
						html_type = "relationships";
						services_and_rules_task_id = parent_task_details["id"];
						relationship_table = db_table;
					}
					else if (task_services[service_type] == "fk" && choose_db_table["db_table_parent"]) {
						html_type = "parents_get_all";
						//relationship_table = choose_db_table["db_table_parent"]; //no need bc won't be used
					}
					
					break;
					
				case "count": 
					title = "Count all table's items"; 
					
					if (parent_task_details && task_services[service_type] == "fk") {
						html_type = "relationships_count";
						services_and_rules_task_id = parent_task_details["id"];
						relationship_table = db_table;
					}
					else if (task_services[service_type] == "fk" && choose_db_table["db_table_parent"]) {
						html_type = "parents_count";
						//relationship_table = choose_db_table["db_table_parent"]; //no need bc won't be used
					}
					
					break;
					
				case "get": 
					title = "Get a specific table's row";
					break;
					
				case "insert": 
					title = "Insert a specific table's row"; 
					break;
					
				case "update": 
					title = "Update a specific table's row"; 
					break;
					
				case "update_pks": 
					title = "Update a specific table's row primary key"; 
					break;
					
				case "delete": 
					title = "Delete a specific table's row"; 
					break;
			}
			
			var html = table_action_ui_html.replace(/#title#/g, title).replace(/#type#/g, html_type).replace(/#relationship_table#/g, relationship_table);
			html = $(html);
			html.children(".table_ui_panel").attr("services_and_rules_task_id", services_and_rules_task_id);
			
			//add table alias
			if (db_table_alias)
				html.children(".table_header").children("label").append(" with alias: '" + db_table_alias + "'");
			
			removeNonActiveBrokersFromTaskServicesAndRules(active_brokers, html);
			
			services_and_rules_body_elm.append(html);
		}
	
	//prepare panels
	$.each(services_and_rules_body_elm.find(" > .table_ui > .table_ui_panel"), function (idx, elm) {
		elm = $(elm);
		var type = elm.attr("type");
		var relationship_table = elm.attr("relationship_table");
		var sr_task_id = elm.attr("services_and_rules_task_id");
		
		elm.children(".brokers_layer_type").children("select").each(function (idy, item) {
			onChangeBrokersLayerType(item);
		});
		
		$.each(elm.children(".task_properties").children("div"), function (idy, item) {
			loadTask(item, sr_task_id, type, relationship_table);
			
			if ($(item).hasClass("get_query_data_task_html"))
				$(item).find(".opts").append('<div class="info">The system will automatically add the "return_type" option with the value: "result".</div>');
		});
		
		elm.children(".brokers_layer_type").children("select").each(function (idy, item) {
			var task_details = getTaskDetailsByTaskId(tasks_details, sr_task_id);
			var ui_props = task_details["properties"]["ui_props"];
			showCorrectLoadedTask(item, type, ui_props);
		});
	});
	
	prepareTaskAttributesServicesAndRulesAutomatically(existent_tasks_elm, task_details, active_brokers);
	
	//call inner tasks, but only after the ajax request finish, otherwise if the parent_task didn't load yet his table_ui_props, the inner tasks won't have the right services... 
	if (task_details["tasks"])
		prepareServicesAndRulesAutomatically(existent_tasks_elm, task_details["tasks"], active_brokers, task_details);
}

function removeNonActiveBrokersFromTaskServicesAndRules(active_brokers, table_ui_elm) {
	var table_ui_panel = table_ui_elm.children(".table_ui_panel");
	var action_type = table_ui_panel.attr("type");
	var is_set_data = action_type == "insert" || action_type == "update" || action_type == "delete";
	
	//prepare active_brokers_tasks_by_tag
	var active_brokers_tasks_by_tag = [];
	var tasks_tag_to_properties_class = {"callbusinesslogic": "call_business_logic_task_html", "callibatisquery": "call_ibatis_query_task_html", "callhibernatemethod": "call_hibernate_method_task_html", "getquerydata": "get_query_data_task_html", "setquerydata": "set_query_data_task_html"};
	var get_query_data_added = false;
	var set_query_data_added = false;
	
	for (var broker_name in active_brokers) {
		if (typeof CallBusinessLogicTaskPropertyObj != "undefined" && CallBusinessLogicTaskPropertyObj && !$.isEmptyObject(CallBusinessLogicTaskPropertyObj.brokers_options) && CallBusinessLogicTaskPropertyObj.brokers_options.hasOwnProperty(broker_name))
			active_brokers_tasks_by_tag.push("callbusinesslogic");
		else if (typeof CallIbatisQueryTaskPropertyObj != "undefined" && CallIbatisQueryTaskPropertyObj && !$.isEmptyObject(CallIbatisQueryTaskPropertyObj.brokers_options) && CallIbatisQueryTaskPropertyObj.brokers_options.hasOwnProperty(broker_name)) {
			active_brokers_tasks_by_tag.push("callibatisquery");
			
			if (!is_set_data && !get_query_data_added && (typeof GetQueryDataTaskPropertyObj != "undefined" && GetQueryDataTaskPropertyObj && !$.isEmptyObject(GetQueryDataTaskPropertyObj.brokers_options) && GetQueryDataTaskPropertyObj.brokers_options.hasOwnProperty(broker_name))) {
				active_brokers_tasks_by_tag.push("getquerydata");
				get_query_data_added = true;
			}
			
			if (is_set_data && !set_query_data_added && (typeof SetQueryDataTaskPropertyObj != "undefined" && SetQueryDataTaskPropertyObj && !$.isEmptyObject(SetQueryDataTaskPropertyObj.brokers_options) && SetQueryDataTaskPropertyObj.brokers_options.hasOwnProperty(broker_name))) {
				active_brokers_tasks_by_tag.push("setquerydata");
				set_query_data_added = true;
			}
		}
		else if (typeof CallHibernateMethodTaskPropertyObj != "undefined" && CallHibernateMethodTaskPropertyObj && !$.isEmptyObject(CallHibernateMethodTaskPropertyObj.brokers_options) && CallHibernateMethodTaskPropertyObj.brokers_options.hasOwnProperty(broker_name))
			active_brokers_tasks_by_tag.push("callhibernatemethod");
		else if (!is_set_data && typeof GetQueryDataTaskPropertyObj != "undefined" && GetQueryDataTaskPropertyObj && !$.isEmptyObject(GetQueryDataTaskPropertyObj.brokers_options) && GetQueryDataTaskPropertyObj.brokers_options.hasOwnProperty(broker_name)) {
				active_brokers_tasks_by_tag.push("getquerydata");
				get_query_data_added = true;
		}
		else if (is_set_data && typeof SetQueryDataTaskPropertyObj != "undefined" && SetQueryDataTaskPropertyObj && !$.isEmptyObject(SetQueryDataTaskPropertyObj.brokers_options) && SetQueryDataTaskPropertyObj.brokers_options.hasOwnProperty(broker_name)) {
				active_brokers_tasks_by_tag.push("setquerydata");
				set_query_data_added = true;
		}
	}
	
	//prepare active brokers
	var options = table_ui_panel.find(" > .brokers_layer_type > select option");
	
	$.each(options, function(idx, option) {
		option = $(option);
		var task_tag = option.attr("value");
		
		if ($.inArray(task_tag, active_brokers_tasks_by_tag) == -1) {
			option.remove();
			var task_class_selector = tasks_tag_to_properties_class[task_tag];
			table_ui_panel.find(" > .task_properties > ." + task_class_selector).parent().remove();
		}
	});
}

function prepareTaskAttributesServicesAndRulesAutomatically(existent_tasks_elm, task_details, active_brokers) {
	if (task_details["properties"]["attributes"]) {
		var other_tables_to_load = {};
		
		for (var attribute_name in task_details["properties"]["attributes"]) {
			var attribute_props = task_details["properties"]["attributes"][attribute_name];
			
			if (attribute_props && attribute_props["db_table"] && attribute_props["db_attribute_label"]) {
				var table_props = {
					"db_driver": attribute_props["db_driver"],
					"db_type": attribute_props["db_type"],
					"db_table": attribute_props["db_table"],
					"db_table_alias": attribute_props["db_table_alias"] ? attribute_props["db_table_alias"] : "",
				};
				var table_props_id = JSON.stringify(table_props).hashCode();
				other_tables_to_load[table_props_id] = table_props;
			}
		}
		
		if (!$.isEmptyObject(other_tables_to_load)) {
			var services_and_rules_body_elm = existent_tasks_elm.find(".task_details_" + task_details["id"] + " > .services_and_rules > .services_and_rules_body");
			var html = '<div class="table_ui other_tables_ui">'
					+ '	<div class="table_header">'
					+ '		<label>Other Tables</label>'
					+ '	</div>'
					+ '	<div class="table_ui_panel" style="display: block;"></div>'
				+ '</div>';
			services_and_rules_body_elm.append(html);
			
			var other_tables_ui_panel = services_and_rules_body_elm.find(".other_tables_ui > .table_ui_panel");
			var choose_db_table = task_details["properties"]["choose_db_table"];
			
			for (var et_id in other_tables_to_load) {
				var db_tables_alias = {};
				var table_props = other_tables_to_load[et_id];
				
				if (table_props["db_table_alias"])
					db_tables_alias[ table_props["db_table"] ] = table_props["db_table_alias"];
					
				if (choose_db_table["db_table_alias"])
					db_tables_alias[ choose_db_table["db_table"] ] = choose_db_table["db_table_alias"]; //this is very important, otherwise if it won't find the relationships with the alias, if exists any...
				
				prepareTaskAttributeServicesAndRulesAutomatically(other_tables_ui_panel, task_details, active_brokers, table_props, db_tables_alias);
			}
		}
	}
}

function prepareTaskAttributeServicesAndRulesAutomatically(other_tables_ui_panel, task_details, active_brokers, table_props, db_tables_alias) {
	var db_table = table_props["db_table"];
	
	//ajax to get this table
	var ab = {};
	for (var broker_name in active_brokers)
		ab[broker_name] = 1;
	
	var url = get_tables_ui_props_url.replace("#db_driver#", table_props["db_driver"]).replace("#type#", table_props["db_type"]);
	var post_data = {
		ab: ab,
		abf: active_brokers,
		st: db_table,
		sta: db_tables_alias
	};
	var loaded_table_props_id = url.hashCode() + "_" + JSON.stringify(post_data).hashCode(); 
	
	if (loaded_tables_props.hasOwnProperty(loaded_table_props_id)) 
		prepareTaskAttributeServicesAndRulesManually(other_tables_ui_panel, task_details, active_brokers, db_table, loaded_tables_props[loaded_table_props_id]);
	else {
		tasks_count_control++;
		
		$.ajax({
			type : "post",
			url: url,
			data : post_data,
			dataType: "json",
			success: function(data) {
				loaded_tables_props[loaded_table_props_id] = data;
				prepareTaskAttributeServicesAndRulesManually(other_tables_ui_panel, task_details, active_brokers, db_table, data);
				
				tasks_count_control--;
				
				if (tasks_count_control == 0) {
					other_tables_ui_panel.parent().closest(".step").children(".button").show();
					MyDiagramUIFancyPopup.hideLoading();
				}
			},
			error : function(jqXHR, textStatus, errorThrown) {
				tasks_count_control--;
				
				if (tasks_count_control == 0) {
					other_tables_ui_panel.parent().closest(".step").children(".button").show();
					MyDiagramUIFancyPopup.hideLoading();
				}
				
				var msg = "Error: trying to get other tables services an rules for task " + task_details["id"] + " with the url:" + url + ' for table: ' + db_table;
				
				if (console && console.log)
					console.log(msg);
				
				if (jqXHR.responseText)
					StatusMessageHandler.showError(msg + "\n" + jqXHR.responseText);
			},
			async: false,
		});
	}
}

function prepareTaskAttributeServicesAndRulesManually(other_tables_ui_panel, task_details, active_brokers, db_table, data) {
	var lower_db_table = db_table.toLowerCase();
	
	var ui_props = {
		"table": data && data.hasOwnProperty("tables") && data["tables"].hasOwnProperty(lower_db_table) ? data["tables"][lower_db_table] : null,
		"brokers": data && data.hasOwnProperty("brokers") ? data["brokers"] : null,
	};
	
	var get_all_html = table_action_ui_html.replace(/#title#/g, "Get All items from table: " + db_table).replace(/#type#/g, "get_all").replace(/#relationship_table#/g, db_table);
	get_all_html = $(get_all_html);
	get_all_html.children(".table_ui_panel").attr("services_and_rules_task_id", task_details["id"]);
	removeNonActiveBrokersFromTaskServicesAndRules(active_brokers, get_all_html);
	
	var count_html = table_action_ui_html.replace(/#title#/g, "Count items from table: " + db_table).replace(/#type#/g, "count").replace(/#relationship_table#/g, db_table);
	count_html = $(count_html);
	count_html.children(".table_ui_panel").attr("services_and_rules_task_id", task_details["id"]);
	removeNonActiveBrokersFromTaskServicesAndRules(active_brokers, count_html);
	
	other_tables_ui_panel.append(get_all_html);
	other_tables_ui_panel.append(count_html);
	
	//prepare panels
	var panels = [get_all_html, count_html];
	$.each(panels, function (idx, elm) {
		elm = $(elm).children(".table_ui_panel");
		var type = elm.attr("type");
		var sr_task_id = elm.attr("services_and_rules_task_id");
		
		elm.children(".brokers_layer_type").children("select").each(function (idy, item) {
			onChangeBrokersLayerType(item);
		});
		
		$.each(elm.children(".task_properties").children("div"), function (idy, item) {
			loadExtraTask(item, sr_task_id, type, ui_props);
		});
		
		elm.children(".brokers_layer_type").children("select").each(function (idy, item) {
			showCorrectLoadedTask(item, type, ui_props);
		});
	});
}

function getDefaultBrokerNameFromTableUIProps(table_ui_props, brokers) {
	var default_broker = brokers ? BrokerOptionsUtilObj.getDefaultBroker(brokers) : null;
	var matches = default_broker ? default_broker.match(/\(([^\(\)]+)\)/g) : [];
	default_broker = matches && matches[0] ? matches[0].replace(/[\(\)"]+/g, "") : default_broker;
	
	if (table_ui_props && default_broker) {
		default_broker = default_broker.toLowerCase();
		
		//if default broker doesn't exists, try to get the others
		if (table_ui_props.hasOwnProperty(default_broker))
			return default_broker;
		
		for (var broker_name in brokers) {
			default_broker = brokers[broker_name];
			var matches = default_broker ? default_broker.match(/\(([^\(\)]+)\)/g) : [];
			default_broker = matches && matches[0] ? matches[0].replace(/[\(\)"]+/g, "") : default_broker;
			default_broker = default_broker.toLowerCase();
			
			if (table_ui_props.hasOwnProperty(default_broker))
				return default_broker;
		}
	}
	
	return default_broker;
}

function showCorrectLoadedTask(brokers_layer_type_elm, type, ui_props) {
	var table_ui_props = ui_props["table"];
	var brokers_ui_props = ui_props["brokers"];
	
	if (table_ui_props && !$.isEmptyObject(brokers_ui_props)) {
		brokers_layer_type_elm = $(brokers_layer_type_elm);
		var brokers_layer_type = "";
		
		for (var key in brokers_ui_props) {
			var layer_broker_name = brokers_ui_props[key];
			
			if (table_ui_props.hasOwnProperty(layer_broker_name) && table_ui_props[layer_broker_name].hasOwnProperty(type) && table_ui_props[layer_broker_name][type]) {
				if (key == "business_logic_broker_name")
					brokers_layer_type = "callbusinesslogic";
				else if (key == "ibatis_broker_name")
					brokers_layer_type = "callibatisquery";
				else if (key == "hibernate_broker_name")
					brokers_layer_type = "callhibernatemethod";
				else if (key == "db_broker_name")
					brokers_layer_type = brokers_layer_type_elm.find("option[value=getquerydata]").length > 0 ? "getquerydata" : "setquerydata";
				
				break;
			}
		}
		
		if (brokers_layer_type != brokers_layer_type_elm.val()) {
			brokers_layer_type_elm.val(brokers_layer_type);
			onChangeBrokersLayerType( brokers_layer_type_elm[0] );
		}
	}
}

function loadTask(elm, task_id, type, relationship_table) {
	elm = $(elm);
	var func = null;
	var brokers = null;
	
	if (elm.hasClass("call_business_logic_task_html")) {
		func = js_load_functions["callbusinesslogic"];
		brokers = CallBusinessLogicTaskPropertyObj.brokers_options;
	}
	else if (elm.hasClass("call_ibatis_query_task_html")) {
		func = js_load_functions["callibatisquery"];
		brokers = CallIbatisQueryTaskPropertyObj.brokers_options;
	}
	else if (elm.hasClass("call_hibernate_method_task_html")) {
		func = js_load_functions["callhibernatemethod"];
		brokers = CallHibernateMethodTaskPropertyObj.brokers_options;
	}
	else if (elm.hasClass("get_query_data_task_html")) { //Note that Presentation Layer can be directly connected with the DB Layer.
		func = js_load_functions["getquerydata"];
		brokers = GetQueryDataTaskPropertyObj.brokers_options;
	}
	else if (elm.hasClass("set_query_data_task_html")) { //Note that Presentation Layer can be directly connected with the DB Layer.
		func = js_load_functions["setquerydata"];
		brokers = SetQueryDataTaskPropertyObj.brokers_options;
	}
	
	if (func) {
		//PREPARING PROPS
		var props = null;
		var task_details = getTaskDetailsByTaskId(tasks_details, task_id);
		var ui_props = task_details["properties"]["ui_props"];
		var table_ui_props = ui_props["table"];
		var brokers_ui_props = ui_props["brokers"];
		
		var default_broker = getDefaultBrokerNameFromTableUIProps(table_ui_props, brokers);
		
		if (table_ui_props && default_broker && type) {
			type = type.toLowerCase();
			
			props = table_ui_props && table_ui_props.hasOwnProperty(default_broker) && table_ui_props[default_broker].hasOwnProperty(type) ? table_ui_props[default_broker][type] : null;
			
			if (props && relationship_table && (type == "relationships" || type == "relationships_count")) {
				var rtn = relationship_table.toLowerCase();
				props = props.hasOwnProperty(rtn) ? props[rtn] : null;
			}
		}
		
		props = props ? props : {};
		
		if ($.isEmptyObject(props)) {
			var error_class = "";
			
			if (table_ui_props && brokers_ui_props)
				for (var key in brokers_ui_props) {
					var layer_broker_name = brokers_ui_props[key];
					
					if (table_ui_props.hasOwnProperty(layer_broker_name) && table_ui_props[layer_broker_name].hasOwnProperty(type) && table_ui_props[layer_broker_name][type]) {
						if (relationship_table && (type == "relationships" || type == "relationships_count")) {
							var rtn = relationship_table.toLowerCase();
							
							if (table_ui_props[layer_broker_name][type].hasOwnProperty(rtn))
								error_class = "info";
						}
						else
							error_class = "info";
						
						break;
					}
				}
			
			//preparing error
			var msg = '<div class="error error_' + error_class + ' error_' + type + '">The system tried to detect automatically all table\'s settings, but it couldn\'t for the type: "' + type + '".</div>';
			
			var services_and_rules_body = elm.parent().closest(".services_and_rules_body");
			
			if (error_class != "info")
				services_and_rules_body.parent().closest(".existent_tasks").children(".error").show();
			
			updateMyDiagramUIFancyPopup();
			
			if (services_and_rules_body.children(".error_" + type).length == 0)
				services_and_rules_body.append(msg);
		}
		
		//console.log(table_name+":"+default_broker+":"+type);
		//console.log(props);
		
		//INIT TASK
		taskFlowChartObj.Property.setPropertiesFromHtmlElm(elm.parent(), "task_property_field", props);
		eval (func + "(elm.parent(), null, props);");
		
		//PREPARING PARAMS
		var db_table_parent = task_details["properties"]["choose_db_table"]["db_table_parent"];
		var load_params = !$.isEmptyObject(props) && (db_table_parent || (type != "get_all" && type != "count")); //Do nothing if type == get_all || type == count and there is no db_table_parent, because we want all items when type == get_all or type == count and there is no db_table_parent
		
		if (load_params) {
			loadTaskParams(elm[0], task_details["properties"]["choose_db_table"]["db_driver"], task_details["properties"]["choose_db_table"]["db_type"], props);
			loadTaskParamsWithDefaultValues(elm[0], type);
		}
	}
}

function loadExtraTask(elm, task_id, type, ui_props) {
	elm = $(elm);
	var func = null;
	var brokers = null;
	
	if (elm.hasClass("call_business_logic_task_html")) {
		func = js_load_functions["callbusinesslogic"];
		brokers = CallBusinessLogicTaskPropertyObj.brokers_options;
	}
	else if (elm.hasClass("call_ibatis_query_task_html")) {
		func = js_load_functions["callibatisquery"];
		brokers = CallIbatisQueryTaskPropertyObj.brokers_options;
	}
	else if (elm.hasClass("call_hibernate_method_task_html")) {
		func = js_load_functions["callhibernatemethod"];
		brokers = CallHibernateMethodTaskPropertyObj.brokers_options;
	}
	else if (elm.hasClass("get_query_data_task_html")) { //Note that Presentation Layer can be directly connected with the DB Layer.
		func = js_load_functions["getquerydata"];
		brokers = GetQueryDataTaskPropertyObj.brokers_options;
	}
	else if (elm.hasClass("set_query_data_task_html")) { //Note that Presentation Layer can be directly connected with the DB Layer.
		func = js_load_functions["setquerydata"];
		brokers = SetQueryDataTaskPropertyObj.brokers_options;
	}
	
	if (func) {
		//PREPARING PROPS
		var props = null;
		var task_details = getTaskDetailsByTaskId(tasks_details, task_id);
		var table_ui_props = ui_props["table"];
		var brokers_ui_props = ui_props["brokers"];
		
		var default_broker = getDefaultBrokerNameFromTableUIProps(table_ui_props, brokers);
		
		if (table_ui_props && default_broker && type) {
			type = type.toLowerCase();
			
			props = table_ui_props && table_ui_props.hasOwnProperty(default_broker) && table_ui_props[default_broker].hasOwnProperty(type) ? table_ui_props[default_broker][type] : null;
		}
		
		props = props ? props : {};
		
		if ($.isEmptyObject(props)) {
			var error_class = "";
			
			if (table_ui_props && brokers_ui_props)
				for (var key in brokers_ui_props) {
					var layer_broker_name = brokers_ui_props[key];
					
					if (table_ui_props.hasOwnProperty(layer_broker_name) && table_ui_props[layer_broker_name].hasOwnProperty(type) && table_ui_props[layer_broker_name][type]) {
						error_class = "info";
						break;
					}
				}
			
			//preparing error
			var msg = '<div class="error error_' + error_class + ' error_' + type + '">The system tried to detect automatically all table\'s settings, but it couldn\'t for the type: "' + type + '".</div>';
			
			var other_tables_ui = elm.parent().closest(".other_tables_ui");
			
			if (error_class != "info")
				other_tables_ui.parent().closest(".existent_tasks").children(".error").show();
			
			updateMyDiagramUIFancyPopup();
			
			if (other_tables_ui.children(".error_" + type).length == 0)
				other_tables_ui.append(msg);
		}
		
		//console.log(table_name+":"+default_broker+":"+type);
		//console.log(props);
		
		//INIT TASK
		taskFlowChartObj.Property.setPropertiesFromHtmlElm(elm.parent(), "task_property_field", props);
		eval (func + "(elm.parent(), null, props);");
		
		//PREPARING PARAMS
		//Loading params even for the get_all and count_all services, bc these services may have some params
		if (task_details) {
			loadTaskParams(elm[0], task_details["properties"]["choose_db_table"]["db_driver"], task_details["properties"]["choose_db_table"]["db_type"], props);
			loadTaskParamsWithDefaultValues(elm[0], type);
		}
	}
}

function loadTaskParams(elm, db_driver, db_type, props) {
	elm = $(elm);
	
	var is_bl = elm.hasClass("call_business_logic_task_html");
	var is_query = elm.hasClass("call_ibatis_query_task_html");
	var is_hbn = elm.hasClass("call_hibernate_method_task_html");
	
	if (props && (is_bl || is_query || is_hbn)) {
		var selected_broker = elm.children(".broker_method_obj").children("select").val();
		var matches = selected_broker ? selected_broker.match(/\(([^\(\)]+)\)/g) : [];
		selected_broker = matches && matches[0] ? matches[0].replace(/[\(\)"]+/g, "") : selected_broker;
		
		var bean_name = "";
		var bean_file_name = "";
		var bs = is_bl ? business_logic_brokers : (is_query ? ibatis_brokers : hibernate_brokers);
		
		for (var i = 0; i < bs.length; i++) {
			var b = bs[i];
			if (b[0] == selected_broker) {
				bean_name = b[2];
				bean_file_name = b[1];
				break;
			}
		}
		
		if (bean_file_name && bean_name) {
			if (is_bl && props["path"] && props["service_id"])
				updateBusinessLogicParams(elm, bean_file_name, bean_name, props["path"], props["service_id"]);
			else if (is_query && props["path"] && props["service_type"] && props["service_id"])
				updateQueryParams(elm, bean_file_name, bean_name, db_driver, db_type, props["path"], props["service_type"], props["service_id"], "", "queries");
			else if (is_hbn && props["path"] && props["service_id"]) {
				var method = props["service_method"];
				var relationship_type = "";
				var query_type = "";
			
				if ($.inArray(method, CallHibernateMethodTaskPropertyObj.available_native_methods) != -1) {
					relationship_type = "native";
				}
				else if ($.inArray(method, CallHibernateMethodTaskPropertyObj.available_relationship_methods) != -1) {
					method = props["sma_rel_name"];
					relationship_type = "relationships";
				}
				else if ($.inArray(method, CallHibernateMethodTaskPropertyObj.available_query_methods) != -1) {
					relationship_type = "queries";
				
					switch (method) {
						case "callInsertSQL":
						case "callInsert":
							query_type = "insert"; break;
						case "callUpdateSQL":
						case "callUpdate":
							query_type = "update"; break;
						case "callDeleteSQL":
						case "callDelete":
							query_type = "delete"; break;
						case "callSelectSQL":
						case "callSelect":
							query_type = "select"; break;
						case "callProcedureSQL":
						case "callProcedure":
							query_type = "procedure"; break;
					}
				}
			
				if (relationship_type)
					updateHibernateObjectMethodParams(elm, bean_file_name, bean_name, db_driver, db_type, props["path"], query_type, method, props["service_id"], relationship_type);
			}
		}
	}
}

function loadTaskParamsWithDefaultValues(elm, type) {
	elm = $(elm);
	
	var is_get = type == "get_all" || type == "count" || type == "get" || type == "relationships" || type == "relationships_count" || type == "parents_get_all" || type == "parents_count";
	var table_name = tn = null;
	
	if (!is_get) {
		var task_id = elm.parent().closest("li").attr("task_id");
		var task_details = getTaskDetailsByTaskId(tasks_details, task_id);
		var task_properties = task_details && task_details["properties"] ? task_details["properties"] : {};
		var choose_db_table = task_properties && task_properties["choose_db_table"] ? task_properties["choose_db_table"] : {};
		table_name = choose_db_table["db_table_alias"] ? choose_db_table["db_table_alias"] : choose_db_table["db_table"];
		
		//2021-02-19 JP: This is very important, bc if there are no tables loaded yet, the system will only add the choose_db_table["db_table"] to the PresentationTaskUtil.db_drivers_tables and all the other tables in the diagram or server will not be added.
		if (!PresentationTaskUtil.db_drivers_tables || !PresentationTaskUtil.db_drivers_tables.hasOwnProperty(choose_db_table["db_driver"]) || !PresentationTaskUtil.db_drivers_tables[ choose_db_table["db_driver"] ].hasOwnProperty(choose_db_table["db_type"]) || !PresentationTaskUtil.db_drivers_tables[ choose_db_table["db_driver"] ][ choose_db_table["db_type"] ])
			PresentationTaskUtil.getDBTables(choose_db_table["db_driver"], choose_db_table["db_type"]);
		
		var attributes = task_properties && task_properties["attributes"] ? task_properties["attributes"] : {};
		var db_table_attributes = choose_db_table && PresentationTaskUtil && PresentationTaskUtil.getDBTableAttributes ? PresentationTaskUtil.getDBTableAttributes(choose_db_table["db_driver"], choose_db_table["db_type"], choose_db_table["db_table"]) : {};
		
		var pks = [];
		for (var attr_name in db_table_attributes)
			if (db_table_attributes[attr_name]["primary_key"])
				pks.push(attr_name);
	}
	
	if (table_name)
		tn = ("" + table_name).replace(/\./g, "_");
	
	if (elm.hasClass("get_query_data_task_html") || elm.hasClass("set_query_data_task_html")) {
		var sql_elm = elm.children(".sql");
		var editor = sql_elm.data("editor");
		var sql = editor ? editor.getValue() : sql_elm.children("textarea.sql_editor").val();
		
		if (sql) {
			var matches = sql.match(/#([^#]+)#/g);
			
			if (matches) {
				for(var i = 0; i < matches.length; i++) {
					var m = matches[i];
					var name = m.replace(/#/g, "");
					var value = is_get ? "{$_GET['" + name + "']}" : (table_name ? "{$_POST['" + tn + "']['" + name + "']}" : "{$_POST['" + name + "']}");
					
					do {
						sql = sql.replace(m, value);
					}
					while (sql.indexOf(m) >= 0);
				}
			
				if (editor)
					editor.setValue(sql, 1);
				else
					sql_elm.children("textarea.sql_editor").val(sql);
			}
		}
	}
	else {
		var items = null;
		
		if (elm.hasClass("call_hibernate_method_task_html")) {
			var method = elm.children(".service_method").children(".service_method_string").val();
			
			var params_class_name = "sma_data";
			if (method == "findRelationships" || method == "findRelationship" || method == "countRelationships" || method == "countRelationship")
				params_class_name = "sma_parent_ids";
			
			items = elm.children(".service_method_args").children("." + params_class_name).children("." + params_class_name);
			
			var sma_ids = elm.children(".service_method_args").children(".sma_ids");
			if (sma_ids.css("display") != "none")
				sma_ids.children("input").val("ids");
		}
		else 
			items = elm.children(".params").children(".parameters");
		
		var selected_task_properties = elm.parent().closest(".selected_task_properties");
		selected_task_properties.children(".warning").remove();
		
		items.find(".item .key").each(function(idx, field) {
			field = $(field);
			var p = field.parent();
			var name = field.val();
			
			var value = is_get ? "$_GET['" + name + "']" : (table_name ? "$_POST['" + tn + "']['" + name + "']" : "$_POST['" + name + "']");
			p.children(".value").val(value);
			p.children(".value_type").val("");
			
			//if insert, update or delete action and if attr is not PK and if doesn't exist in the attributes array
			var name_aux = (name.substr(0, 4) == "old_" || name.substr(0, 4) == "new_") && $.inArray(name.substr(4), pks) != -1 ? name.substr(4) : name;
			var is_name_aux_pk = PresentationTaskUtil.getAttributePropertiesName(name_aux, pks) != null; 
			var attr = PresentationTaskUtil.getAttributePropertiesName(name_aux, attributes); 
			
			if (!is_get && attributes && !is_name_aux_pk && (!attr || ($.isPlainObject(attr) && !attr["active"]))) {
				//if code entered here, it means that there is an attribute that this service/rule is using as a param and that the user removed from the UI, this is, that is not in the attributes array.
				p.closest(".item").addClass("warning"); //change class to have a red border
				
				//preparing warning
				var msg = '<div class="warning">Attribute "' + name + '" was removed from the user UI but it\'s being used in this service! Please check this issue and solve it before continue, if necessary...</div>';
				selected_task_properties.append(msg);
				
				selected_task_properties.parent().closest(".existent_tasks").children(".warning").show(); //show correspondent warning
			}
			else
				p.closest(".item").removeClass("warning");
		});
	}
}

function removeTableUIPanel(elm) {
	if (confirm("Are you sure that you wish to remove this UI panel?"))
		$(elm).parent().parent().remove();
}

function toggleTableUIPanel(elm) {
	elm = $(elm);
	var table_ui_panel = elm.parent().parent().children(".table_ui_panel");
	
	if (table_ui_panel.css("display") == "none") {
		table_ui_panel.show();
		elm.removeClass("maximize").addClass("minimize");
	}
	else {
		table_ui_panel.hide();
		elm.removeClass("minimize").addClass("maximize");
	}
	
	MyDiagramUIFancyPopup.resizeOverlay(); //update poopup overlay
}

function onChangeBrokersLayerType(elm) {
	elm = $(elm);
	
	var type = elm.val();
	var tasks_properties = elm.parent().parent().children(".task_properties");
	
	switch(type) {
		case "callbusinesslogic":
			tasks_properties.children(".call_business_logic_task_html").show();
			tasks_properties.children(".call_ibatis_query_task_html, .call_hibernate_method_task_html, .get_query_data_task_html, .set_query_data_task_html").hide();
			break;
		case "callibatisquery":
			tasks_properties.children(".call_ibatis_query_task_html").show();
			tasks_properties.children(".call_business_logic_task_html, .call_hibernate_method_task_html, .get_query_data_task_html, .set_query_data_task_html").hide();
			break;
		case "callhibernatemethod":
			tasks_properties.children(".call_hibernate_method_task_html").show();
			tasks_properties.children(".call_business_logic_task_html, .call_ibatis_query_task_html, .get_query_data_task_html, .set_query_data_task_html").hide();
			break;
		case "getquerydata":
			tasks_properties.children(".get_query_data_task_html").show();
			tasks_properties.children(".call_business_logic_task_html, .call_ibatis_query_task_html, .call_hibernate_method_task_html, .set_query_data_task_html").hide();
			break;
		case "setquerydata":
			tasks_properties.children(".set_query_data_task_html").show();
			tasks_properties.children(".call_business_logic_task_html, .call_ibatis_query_task_html, .call_hibernate_method_task_html, .get_query_data_task_html").hide();
			break;
	}
}

function prepareTasksDetailsWithServicesAndRulesSettings(existent_tasks_elm, tasks_details) {
	if (tasks_details)
		for (var task_details_index in tasks_details) {
			var task_details = tasks_details[task_details_index];
			var services_and_rules_body= existent_tasks_elm.find(".task_details_" + task_details["id"] + " > .services_and_rules > .services_and_rules_body");
			var table_ui_panels = services_and_rules_body.find(" > .table_ui:not(.other_tables_ui) > .table_ui_panel.selected_task_properties");
			var other_tables_ui_panels = services_and_rules_body.find(" > .table_ui.other_tables_ui > .table_ui_panel > .table_ui > .table_ui_panel.selected_task_properties");
			
			task_details["properties"]["brokers_services_and_rules"] = {};
			
			//prepare services and rules for the main task table 
			for (var j = 0; j < table_ui_panels.length; j++) {
				var table_ui_panel = $(table_ui_panels[j]);
				var type = table_ui_panel.attr("type");
				
				if (type) {
					//change type if relationships or relationships_count bc the table_ui_panel is already in the right table and so the type should be get_all and count
					if (type == "relationships")
						type = "get_all";
					else if (type == "relationships_count")
						type = "count";
					
					task_details["properties"]["brokers_services_and_rules"][type] = getTableUIPanelServicesAndRulesSettings(table_ui_panel);
				}
			}
			
			//prepare services and rules for other related tables
			if (other_tables_ui_panels.length > 0) {
				task_details["properties"]["brokers_services_and_rules"]["other"] = {};
				
				for (var j = 0; j < other_tables_ui_panels.length; j++) {
					var table_ui_panel = $(other_tables_ui_panels[j]);
					var type = table_ui_panel.attr("type");
					var relationship_table = table_ui_panel.attr("relationship_table");
					
					if (type && relationship_table) {
						if (!task_details["properties"]["brokers_services_and_rules"]["other"].hasOwnProperty(relationship_table))
							task_details["properties"]["brokers_services_and_rules"]["other"][relationship_table] = {};
						
						task_details["properties"]["brokers_services_and_rules"]["other"][relationship_table][type] = getTableUIPanelServicesAndRulesSettings(table_ui_panel);
					}
				}
			}
			
			//prepare inner tasks
			task_details["tasks"] = prepareTasksDetailsWithServicesAndRulesSettings(existent_tasks_elm, task_details["tasks"]);
		}
	
	return tasks_details;
}

function getTableUIPanelServicesAndRulesSettings(table_ui_panel) {
	var brokers_layer_type = table_ui_panel.find(".brokers_layer_type select").val();
	var task_properties_elm = null;
	
	switch (brokers_layer_type) {
		case "callbusinesslogic": task_properties_elm = table_ui_panel.find(".task_properties .call_business_logic_task_html").parent(); break;
		case "callibatisquery": task_properties_elm = table_ui_panel.find(".task_properties .call_ibatis_query_task_html").parent(); break;
		case "callhibernatemethod": task_properties_elm = table_ui_panel.find(".task_properties .call_hibernate_method_task_html").parent(); break;
		case "getquerydata": task_properties_elm = table_ui_panel.find(".task_properties .get_query_data_task_html").parent(); break;
		case "setquerydata": task_properties_elm = table_ui_panel.find(".task_properties .set_query_data_task_html").parent(); break;
	}
	
	var settings = {
		"brokers_layer_type": brokers_layer_type
	};
	
	if (task_properties_elm) {
		var status = true;
		
		var func = js_submit_functions[brokers_layer_type];
		if (func) {
			//console.log(func + "(task_properties_elm, null, {})");
			eval ("status = " + func + "(task_properties_elm, null, {});");
		}
		
		if (status) {
			var query_string = taskFlowChartObj.Property.getPropertiesQueryStringFromHtmlElm(task_properties_elm, "task_property_field");
			parse_str(query_string, settings);
		}
		
		func = js_complete_functions[brokers_layer_type];
		if (func) {
			//console.log(func + "(task_properties_elm, null, settings, status)");
			eval (func + "(task_properties_elm, null, settings, status);");
		}
	}
	
	return settings;
}

function saveUISFilesWithNewQueries(elm) {
	var p = $(elm).parent().closest(".myfancypopup");
	var go_back_btn = p.children(".step_4").find(".button input").first(); //go back button
	go_back_btn.attr("onClick", "goBackStepAutomaticCreation(this, 1)");
	
	tasks_details = getWorkflowTasksTables();
	cleanTaskBrokersServicesAndRules(tasks_details);
	
	checkUISFiles(elm, {tasks_details: tasks_details});
}

function cleanTaskBrokersServicesAndRules(tasks_details) {
	if (tasks_details)
		for (var task_details_index in tasks_details) {
			var task_details = tasks_details[task_details_index];
			
			if (task_details && task_details["properties"] && task_details["properties"]["brokers_services_and_rules"])
				task_details["properties"]["brokers_services_and_rules"] = null;
			
			cleanTaskBrokersServicesAndRules(task_details["tasks"]);
		}
}

function checkUISFiles(elm, settings) {	
	//console.log(settings);
	elm = $(elm);
	elm.attr("disabled", "disabled").addClass("loading");
	
	var p = elm.parent().closest(".myfancypopup");
	var step_4 = p.children(".step_4");
	var existent_tasks = step_4.find(".existent_tasks");
	var ul = existent_tasks.children("ul");
	
	p.children(".step").hide();
	step_4.show();
	existent_tasks.children(".loading, .error").remove();
	$('<div class="loading">loading...</div>').insertBefore(ul);
	ul.hide();
	existent_tasks.children(".warning").hide();
	$( step_4.find(" > .button > input")[1] ).attr("disabled", "disabled").addClass("disabled");
	
	var overwrite = p.find(".overwrite input").is(":checked");
	var users_perms_relative_folder = p.find(".users_perms_relative_folder input").val();
	var list_and_edit_users = p.find(".list_and_edit_users select").val();
	list_and_edit_users = $.isArray(list_and_edit_users) ? list_and_edit_users.join(",") : "";
	var url = create_presentation_uis_diagram_files_url + "&overwrite=" + (overwrite ? 1 : 0) + "&files_date_simulation=1&users_perms_relative_folder=" + users_perms_relative_folder + "&list_and_edit_users=" + list_and_edit_users;
	
	$.ajax({
		type : "post",
		url : url,
		data : JSON.stringify(settings),
		dataType : "json",
		processData: false,
		contentType: 'application/json', //typically 'application/x-www-form-urlencoded', but the service you are calling may expect 'text/json'... check with the service to see what they expect as content-type in the HTTP header.
		success : function(data, text_status, jqXHR) {
			//show status of the created/changed files
			var html = '';
			
			if (data)
				html += getTasksHtmlForAutomaticCreation(tasks_details, false);
			else 
				html = '<li class="error">No file details returned. Please check this better before you proceed!</li>';
			
			ul.html(html);
			
			//remove services_and_rules from step 4
			ul.find(".services_and_rules").remove();
			ul.find(".services_and_rules").show();
			elm.removeAttr("disabled").removeClass("loading");
			
			prepareCheckedUISFilesHtml(ul, data);
			
			updateMyDiagramUIFancyPopup();
		},
		error : function(jqXHR, textStatus, errorThrown) {
			elm.removeAttr("disabled").removeClass("loading");
			existent_tasks.children(".loading").remove();
			
			var msg = "Error requesting url: " + url;
			msg += jqXHR.responseText ? "\n" + jqXHR.responseText : "";
			$('<div class="error">' + msg + '</div>').show().insertBefore(ul);
			taskFlowChartObj.StatusMessage.showError(msg);
			
			updateMyDiagramUIFancyPopup();
		},
	});
}

function prepareCheckedUISFilesHtml(ul, tasks_files_details) {
	if (tasks_files_details)
		$.each(tasks_files_details, function(task_id, task_files_details) {
			var html = '';
			var li = '';
			var is_main_reserved = false;
			
			if (task_id != "*") {
				var task_details = getTaskDetailsByTaskId(tasks_details, task_id);
				li = ul.find(".task_details_" + task_id);
			}
			else {
				is_main_reserved = true;
				li = ul.children(".tasks_reserved_files");
				
				if (!li[0]) {
					li = $('<li class="tasks_reserved_files"><div class="task_info"><label class="task_file" task_file="list">Reserved Files:</label><i class="icon maximize" onclick="toggleTaskDetails(this)">Toggle</i></div></li>');
					ul.prepend(li);
				}
				
				var exists_reserved_files_to_create = false;
				if (task_files_details)
					$.each(task_files_details, function(file_path, file_details) {
						if (file_details && (!file_details.hasOwnProperty("modified_time") || !file_details["modified_time"]) && file_details["allow_non_authenticated_file"]) {
							exists_reserved_files_to_create = true;
							return false;
						}
					});
				
				if (exists_reserved_files_to_create) {
					html = '<div class="non_authenticated_template">'
						+ '	<label>Template for the above non created files: </label>'
						+ '	<select>'
						+ '		<option value="">-- DEFAULT --</option>';
					
					if (PageTaskPropertyObj.templates)
						$.each(PageTaskPropertyObj.templates, function(idx, template) {
							html+= '<option>' + template + '</option>';
						});
					
					html += '	</select>'
						+ '	<div class="info">Note that this template will only be used for the above files that were not created yet...</div>'
						+ '</div>';
					
					li.append(html);
				}
			}
			
			if (task_files_details) {
				html = '<div class="files_statuses">'
					+ '		<label>Files Validation:</label>'
					+ '		<table>'
					+ '			<thead>'
					+ '				<tr>'
					+ '					<th class="table_header select_file"></th>'
					+ '					<th class="table_header file_path">File Path</th>'
					+ '					<th class="table_header file_type">Type</th>'
					+ '					<th class="table_header status">Validated</th>'
					+ '					<th class="table_header view_code"></th>'
					+ '				</tr>'
					+ '			</thead>'
					+ '			<tbody>';
				
				$.each(task_files_details, function(file_path, file_details) {
					var validation = true;
					var file_type = "";
					var file_exists = false;
					var file_reserved = false;
					var is_index_block_file = file_path.substr(file_path.length - "/index".length) == "/index" && file_path.indexOf("/src/block/") != -1; //if is a index file, uncheck the checkbox bellow, so it doesn't overight the index files, bc the index files are created with the create_presentation_uis_automatically.php script that builds a beautifull UI and if the checkbox is checked that UI will be deleted.
					
					if (file_details) {
						//get files last modified date and check if the files were changed manually and alerts user, that if he continues, the system will remove the previous changes.
						var file_id = file_details["file_id"];
						var real_modified_time = file_details["modified_time"];
						var saved_modified_time = task_details && task_details["properties"] && task_details["properties"]["created_files"] ? task_details["properties"]["created_files"][file_id] : null;
						validation = !real_modified_time || (saved_modified_time && saved_modified_time >= real_modified_time); //if no real_modified_time it means the file does not exists
						
						//Note that this hard code is only checking if exist any "save action time" in cache. This means that does not really means that the file is hard coded.
						//Deprecated, bc the validation above with dates is enough and we cannot know if the blocks are hard-coded or not, bc we are not saving the "save action time" in the edit_block.php.
						//if (file_details["hard_coded"]) 
						//	validation = false;
						
						if (is_main_reserved)
							validation = true; //always validated bc this files will always be created, independent if they are validaded or not.
						
						file_type = file_details["type"] ? file_details["type"] : "";
						file_exists = $.isNumeric(real_modified_time);
						file_reserved = file_type == "reserved";
					}
					
					var validation_label = validation ? "YES" : "NO";
					var validation_checked = validation && (!is_index_block_file || !file_exists); //if is index block file but not exists yet, check the checkbox!
					
					if (is_main_reserved)
						validation_label = file_details && (!file_details.hasOwnProperty("modified_time") || !file_details["modified_time"]) ? "TO CREATE" : "CREATED";
					
					html += '<tr>'
						+ '	<td class="select_file">' + (file_reserved ? '<input type="hidden" value="' + file_path + '"/>' : '<input type="checkbox" value="' + file_path + '"' + (validation_checked ? ' checked' : '') + '/>') + '</td>'
						+ '	<td class="file_path">' + file_path + '</td>'
						+ '	<td class="file_type">' + file_type + '</td>'
						+ '	<td class="status status_' + (validation ? 'ok' : 'error') + '">' + validation_label + '</td>'
						+ '	<td class="view_code">' + (file_exists ? '<i class="icon view" onclick="checkFileCode(this, \'' + file_path + '\')">Check</i>' : '') + '</td>'
						+ '</tr>';
				});
				
				html += '			</tbody>'
					+ '		</table>'
					+ '	</div>';
			}
			else if (task_details && task_details["tag"] == "page")
				html = '<div class="warning">No files.</div>';
			
			$(html).insertAfter( li.children(".task_info") );
		});
	
	revalidateFiles(ul);
}

function prepareTasksDetailsWithFilesValidationsSettings(existent_tasks_elm, tasks_details) {
	if (tasks_details)
		for (var task_details_index in tasks_details) {
			var task_details = tasks_details[task_details_index];
			var inputs = existent_tasks_elm.find(".task_details_" + task_details["id"] + " > .files_statuses > table > tbody .select_file >  input");
			
			task_details["properties"]["files_to_create"] = {};
			
			$.each(inputs, function(idx, input) {
				input = $(input);
				var file_path = input.val();
				task_details["properties"]["files_to_create"][file_path] = input.attr("type") == "hidden" ? true : input.is(":checked");
			});
			
			task_details["tasks"] = prepareTasksDetailsWithFilesValidationsSettings(existent_tasks_elm, task_details["tasks"]);
		}
	
	return tasks_details;
}

function revalidateFiles(ul) {
	var tds = ul.find("li .files_statuses td.status.status_error");
	var files_path = {};
	
	$.each(tds, function(idx, td) {
		td = $(td);
		var tr = td.parent();
		var file_path = tr.children(".file_path").text();
		files_path[file_path] = {
			"tr": tr,
			"task_id": tr.parent().closest(".files_statuses").parent().closest("li").attr("task_id")
		};
	});
	
	var tasks = JSON.parse(JSON.stringify(tasks_details)); //clones the tasks object to save the the tasks_details var
	tasks = prepareTasksDetailsFilesToCreate(ul.parent().closest(".existent_tasks"), tasks, files_path);
	
	var settings = {tasks_details: tasks};
	var popup = ul.parent().closest(".myfancypopup");
	var overwrite = popup.find(".overwrite input").is(":checked");
	var users_perms_relative_folder = popup.find(".users_perms_relative_folder input").val();
	var list_and_edit_users = popup.find(".list_and_edit_users select").val();
	list_and_edit_users = $.isArray(list_and_edit_users) ? list_and_edit_users.join(",") : "";
	var url = create_presentation_uis_diagram_files_url + "&overwrite=" + (overwrite ? 1 : 0) + "&files_code_validation=1&users_perms_relative_folder=" + users_perms_relative_folder + "&list_and_edit_users=" + list_and_edit_users;
	
	$.ajax({
		type : "post",
		url : url,
		data : JSON.stringify(settings),
		dataType : "json",
		processData: false,
		contentType: 'application/json', //typically 'application/x-www-form-urlencoded', but the service you are calling may expect 'text/json'... check with the service to see what they expect as content-type in the HTTP header.
		success : function(data, text_status, jqXHR) {
			if (data)
				$.each(data, function(task_id, task_files_details) {
					$.each(task_files_details, function(fp, file_details) {
						if (files_path[fp]) {
							var old_code = file_details && file_details["old_code"] ? file_details["old_code"] : "";
							var new_code = file_details && file_details["new_code"] ? file_details["new_code"] : "";
							
							old_code = old_code.replace(/>/g, "&gt;").replace(/</g, "&lt;");
							new_code = new_code.replace(/>/g, "&gt;").replace(/</g, "&lt;");
							
							var validation = old_code.trim() == "" || old_code.hashCode() == new_code.hashCode();
							
							if (validation) {
								var tr = files_path[fp]["tr"];
								tr.children(".status").removeClass("status_error").addClass("status_ok").html("YES");
								tr.find(" > .select_file > input").attr("checked", "checked").prop("checked", true);
							}
							else
								ul.parent().children(".warning").show();
						}
					});
				});
		},
		error : function(jqXHR, text_status, errorThrown) {
			ul.parent().children(".warning").show();
			
			if (jqXHR.responseText)
				StatusMessageHandler.showError(jqXHR.responseText);
		},
		complete : function(jqXHR, text_status) {
			$( ul.parent().closest(".step_4").find(" > .button > input")[1] ).removeAttr("disabled").removeClass("disabled");
			
			ul.show();
			ul.parent().children(".loading").remove();
			
			//to be used by external pages like the create_page_presentation_uis_diagram_block.php
			if (typeof onCheckedUISFilesHtml == "function")
				onCheckedUISFilesHtml(ul);
		},
	});
}

function checkFileCode(elm, file_path) {
	elm = $(elm);
	
	var tasks = JSON.parse(JSON.stringify(tasks_details)); //clones the tasks object to save the the tasks_details var
	var tr = elm.parent().closest("tr");
	var is_reserved_file = tr.find(".file_type").text() == "reserved";
	var files_path = {};
	files_path[file_path] = {
		"tr": tr,
		"task_id": tr.parent().closest(".files_statuses").parent().closest("li").attr("task_id"),
	};
	tasks = prepareTasksDetailsFilesToCreate(tr.parent().closest(".existent_tasks"), tasks, files_path);
	
	var settings = {tasks_details: tasks};
	var popup = tr.parent().closest(".myfancypopup");
	var overwrite = popup.find(".overwrite input").is(":checked");
	var users_perms_relative_folder = popup.find(".users_perms_relative_folder input").val();
	var list_and_edit_users = popup.find(".list_and_edit_users select").val();
	list_and_edit_users = $.isArray(list_and_edit_users) ? list_and_edit_users.join(",") : "";
	var url = create_presentation_uis_diagram_files_url + "&overwrite=" + (overwrite ? 1 : 0) + "&files_code_validation=1&users_perms_relative_folder=" + users_perms_relative_folder + "&list_and_edit_users=" + list_and_edit_users;
	
	var confirm_save_elm = $(".confirm_save");
	var old_file_code_area = confirm_save_elm.find(".file_code .old_file_code pre code");
	var new_file_code_area = confirm_save_elm.find(".file_code .new_file_code pre code");
	confirm_save_elm.attr("file_path", file_path);
	old_file_code_area.html("loading...");
	new_file_code_area.html("loading...");
	
	if (is_reserved_file)
		confirm_save_elm.addClass("is_reserved");
	else
		confirm_save_elm.removeClass("is_reserved");
	
	var z_index = parseInt($("#create_uis_files").css("z-index")) + 1;
	confirm_save_elm.css("z-index", z_index);
	confirm_save_elm.show();
	
	$.ajax({
		type : "post",
		url : url,
		data : JSON.stringify(settings),
		dataType : "json",
		processData: false,
		contentType: 'application/json', //typically 'application/x-www-form-urlencoded', but the service you are calling may expect 'text/json'... check with the service to see what they expect as content-type in the HTTP header.
		success : function(data, text_status, jqXHR) {
			var file_codes = null;
			
			if (data)
				$.each(data, function(task_id, task_files_details) {
					$.each(task_files_details, function(fp, file_details) {
						if (fp == file_path) {
							file_codes = file_details;
							return false; //breaks the loop
						}
					});
					
					if (file_codes)
						return false; //breaks the loop
				});
			
			if (file_codes) {
				var old_code = file_codes && file_codes["old_code"] ? file_codes["old_code"] : "";
				var new_code = file_codes && file_codes["new_code"] ? file_codes["new_code"] : "";
				
				old_code = old_code.replace(/>/g, "&gt;").replace(/</g, "&lt;");
				new_code = new_code.replace(/>/g, "&gt;").replace(/</g, "&lt;");
				
				old_file_code_area.html(old_code);
				new_file_code_area.html(new_code);
				
				hljs.highlightBlock(old_file_code_area[0]);
				hljs.highlightBlock(new_file_code_area[0]);
			}
		},
		error : function(jqXHR, textStatus, errorThrown) {
			var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
			taskFlowChartObj.StatusMessage.showError("Error requesting url: " + url + msg);
		},
	});
}

function validateCheckedFileCode() {
	var confirm_save_elm = $(".confirm_save");
	var file_path = confirm_save_elm.attr("file_path");
	var tds = $("#create_uis_files.myfancypopup").find(".step_4 > .existent_tasks > ul li .files_statuses td.file_path");
	
	$.each(tds, function(idx, td) {
		td = $(td);
		
		if (td.text() == file_path) {
			var tr = td.parent();
			tr.children(".status").removeClass("status_error").addClass("status_ok").html("YES");
			tr.find(" > .select_file > input").attr("checked", "checked").prop("checked", true);
			confirm_save_elm.hide();
			
			return false; //breaks the loop
		}
	});
}

function cancelCheckedFileCode() {
	$(".confirm_save").hide();
}

function enableDisableAutoScroll(elm) {
	auto_scroll_active = !auto_scroll_active;
	
	$(elm).html(auto_scroll_active ? "Click here to disable auto scroll." : "Click here to enable auto scroll.");
}

function prepareTasksDetailsFilesToCreate(existent_tasks_elm, tasks, files_path) {
	var tds = existent_tasks_elm.find("li .files_statuses td.file_path");
	var default_files_path = {};
	
	$.each(tds, function(idx, td) {
		td = $(td);
		var file_path = td.text();
		
		default_files_path[file_path] = {
			"tr": td.parent(),
			"task_id": td.parent().closest(".files_statuses").parent().closest("li").attr("task_id"),
		};
	});
	tasks = addTasksDetailsFilesToCreate(tasks, default_files_path);
	
	tasks = cleanTasksDetailsFilesToCreate(tasks);
	tasks = addTasksDetailsFilesToCreate(tasks, files_path);
	return tasks;
}

function cleanTasksDetailsFilesToCreate(tasks_details) {
	if (tasks_details)
		for (var task_details_index in tasks_details) {
			var task_details = tasks_details[task_details_index];
			
			if (task_details["properties"]["files_to_create"]) 
				for (var fp in task_details["properties"]["files_to_create"])
					task_details["properties"]["files_to_create"][fp] = false;
			
			task_details["tasks"] = cleanTasksDetailsFilesToCreate(task_details["tasks"]);
		}
	
	return tasks_details;
}

function addTasksDetailsFilesToCreate(tasks_details, files_path) {
	if (files_path && tasks_details) {
		var tasks_files_path = {};
		
		for (var file_path in files_path) {
			var file_data = files_path[file_path];
			var task_id = file_data["task_id"];
			
			if (task_id) {
				if (!tasks_files_path.hasOwnProperty(task_id))
					tasks_files_path[task_id] = [];
				
				tasks_files_path[task_id].push(file_path);
			}
		}
		
		for (var task_id in tasks_files_path) 
			tasks_details = addTaskDetailsFilesToCreate(tasks_details, task_id, tasks_files_path[task_id])
	}
	
	return tasks_details;
}

function addTaskDetailsFilesToCreate(tasks_details, task_id, files_path) {
	if (tasks_details && files_path)
		for (var task_details_index in tasks_details) {
			var task_details = tasks_details[task_details_index];
			
			if (task_details["id"] == task_id) {
				if (!task_details["properties"]["files_to_create"]) 
					task_details["properties"]["files_to_create"] = {};
				
				for (var i in files_path) {
					var fp = files_path[i];
					task_details["properties"]["files_to_create"][fp] = 1;
				}
				
				if (task_details["properties"]["files_to_create"]) 
					for (var fp in task_details["properties"]["files_to_create"])
						if ($.inArray(fp, files_path) == -1)
							task_details["properties"]["files_to_create"][fp] = false;
				
				break;
			}
			else
				task_details["tasks"] = addTaskDetailsFilesToCreate(task_details["tasks"], task_id, files_path);
		}
	
	return tasks_details;
}

function saveUISFiles(elm, settings) {	
	//console.log(settings);
	elm = $(elm);
	elm.attr("disabled", "disabled").addClass("loading");
	
	var p = elm.parent().closest(".myfancypopup");
	var step_5 = p.children(".step_5");
	var tbody = step_5.find(".files_statuses table tbody");
	
	p.children(".step").hide();
	step_5.show();
	tbody.html('<tr><td class="loading" colspan="3">loading...</td></tr>');
	
	var overwrite = p.find(".overwrite input").is(":checked");
	var users_perms_relative_folder = p.find(".users_perms_relative_folder input").val();
	var list_and_edit_users = p.find(".list_and_edit_users select").val();
	var non_authenticated_template = p.find(".non_authenticated_template select").val();
	
	users_perms_relative_folder = users_perms_relative_folder ? users_perms_relative_folder : "";
	list_and_edit_users = $.isArray(list_and_edit_users) ? list_and_edit_users.join(",") : "";
	non_authenticated_template = non_authenticated_template ? non_authenticated_template : "";
	
	var url = create_presentation_uis_diagram_files_url + "&overwrite=" + (overwrite ? 1 : 0) + "&users_perms_relative_folder=" + users_perms_relative_folder + "&list_and_edit_users=" + list_and_edit_users + "&non_authenticated_template=" + non_authenticated_template;
	
	$.ajax({
		type : "post",
		url : url,
		data : JSON.stringify(settings),
		dataType : "json",
		processData: false,
		contentType: 'application/json', //typically 'application/x-www-form-urlencoded', but the service you are calling may expect 'text/json'... check with the service to see what they expect as content-type in the HTTP header.
		success : function(data, text_status, jqXHR) {
			//show status of the created/changed files
			var html = '';
			
			if (data) 
				$.each(data, function(task_id, task_files_details) {
					if (task_id != "*") {
						var task_details = getTaskDetailsByTaskId(tasks_details, task_id);
						var files_to_create = task_details["properties"] ? task_details["properties"]["files_to_create"] : {};
						
						$.each(task_files_details, function(file_path, file_data) {
							var modified_time = null, file_id = null, file_status = false;
							var file_selected = !files_to_create || !files_to_create.hasOwnProperty(file_path) || files_to_create[file_path];
							
							if ($.isPlainObject(file_data)) {
								modified_time = file_data["modified_time"];
								file_id = file_data["file_id"];
								file_status = file_data["status"];
							}
							
							html += '<tr>'
								+ '	<td class="task_label">' + task_details["label"] + '</td>'
								+ '	<td class="file_path">' + file_path + '</td>'
								+ '	<td class="status status_' + (!file_selected || file_status ? 'ok' : 'error') + '">' + (file_selected ? (file_status ? "ok" : "error") : "-") + '</td>'
								+ '</tr>';
							
							//update modified date for each file in task_properties
							if (file_status && file_id && modified_time && file_selected && files_to_create && files_to_create[file_path]) {
								if (!taskFlowChartObj.TaskFlow.tasks_properties[task_id]["created_files"])
									taskFlowChartObj.TaskFlow.tasks_properties[task_id]["created_files"] = {};
								
								taskFlowChartObj.TaskFlow.tasks_properties[task_id]["created_files"][file_id] = modified_time;
							}
						});
					}
					else { //if reserved files from *
						$.each(task_files_details, function(file_path, file_data) {
							var file_status = file_data["status"];
							
							html += '<tr>'
								+ '	<td class="task_label">*</td>'
								+ '	<td class="file_path">' + file_path + '</td>'
								+ '	<td class="status status_' + (file_status ? 'ok' : 'error') + '">' + (file_status ? "ok" : "error") + '</td>'
								+ '</tr>';
						});
					}
				});
			
			tbody.html(html);
			
			//set include_db_driver prop in taskFlowChartObj.TaskFlow.tasks_properties
			prepareSavedTasksProperties(settings["tasks_details"]);
			
			//save workflow with new modified dates 
			//console.log(taskFlowChartObj.TaskFlow.tasks_properties);
			taskFlowChartObj.TaskFile.save();
			
			elm.removeAttr("disabled").removeClass("loading");
			
			updateMyDiagramUIFancyPopup();
			
			//Refreshing folder in main tree of the admin advanced panel
			if (window.parent.refreshAndShowLastNodeChilds && window.parent.mytree && window.parent.mytree.tree_elm) { //it could be a poopup inside of a popup in case this file gets called by the create_page_presentation_uis_diagram_block.php
				//Refreshing last node clicked in the entities folder.
				//window.parent.refreshAndShowLastNodeChilds();
				
				//Refreshing blocks folder
				var project = window.parent.$("#" + window.parent.last_selected_node_id).parent().closest("li[data-jstree=\'{\"icon\":\"project\"}\']");
				var entities_folder_id = project.children("ul").children("li[data-jstree=\'{\"icon\":\"entities_folder\"}\']").attr("id");
				window.parent.refreshAndShowNodeChildsByNodeId(entities_folder_id);
				
				var blocks_folder_id = project.children("ul").children("li[data-jstree=\'{\"icon\":\"blocks_folder\"}\']").attr("id");
				window.parent.refreshAndShowNodeChildsByNodeId(blocks_folder_id);
			}
		},
		error : function(jqXHR, textStatus, errorThrown) {
			elm.removeAttr("disabled").removeClass("loading");
			
			var msg = "Error requesting url: " + url;
			msg += jqXHR.responseText ? "\n" + jqXHR.responseText : "";
			tbody.html('<tr><td class="error" colspan="3">' + msg + '</td></tr>').children("td").show();
			taskFlowChartObj.StatusMessage.showError(msg);
				
			updateMyDiagramUIFancyPopup();
		},
		complete : function() {
			//to be used by external pages like the create_page_presentation_uis_diagram_block.php
			if (typeof onSaveUISFiles == "function")
				onSaveUISFiles(step_5);
		},
	});
}

function prepareSavedTasksProperties(tasks) {
	if (tasks)
		for (var task_id in tasks) {
			var task = tasks[task_id];
			var task_tag = task["tag"];
			var sub_tasks = task["tasks"];
			
			if (task_tag == "listing" || task_tag == "form" || task_tag == "view") {
				if (task["properties"] && task["properties"]["choose_db_table"] && taskFlowChartObj.TaskFlow.tasks_properties[task_id]) {
					taskFlowChartObj.TaskFlow.tasks_properties[task_id]["choose_db_table"]["include_db_driver"] = task["properties"]["choose_db_table"]["include_db_driver"];
				}
			}
			
			if (sub_tasks)
				prepareSavedTasksProperties(sub_tasks)
		};
}

function updateMyDiagramUIFancyPopup() {
	$(window).scrollTop(0);
	MyDiagramUIFancyPopup.updatePopup();
}

function saveUIsDiagramFlow() {
	prepareAutoSaveVars();
	
	if (taskFlowChartObj.TaskFile.isWorkFlowChangedFromLastSaving()) {
		taskFlowChartObj.TaskFile.save(null, {
			success: function(data, textStatus, jqXHR) {
				if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
					showAjaxLoginPopup(jquery_native_xhr_object.responseURL, taskFlowChartObj.TaskFile.set_tasks_file_url, function() {
						taskFlowChartObj.StatusMessage.removeLastShownMessage("error");
						saveUIsDiagramFlow();
					});
				else if (is_from_auto_save) {
					taskFlowChartObj.StatusMessage.removeMessages("status");
					resetAutoSave();
				}
			},
			timeout: is_from_auto_save && auto_save_connection_ttl ? auto_save_connection_ttl : 0,
		});
	}
	else if (!is_from_auto_save)
		taskFlowChartObj.StatusMessage.showMessage("Nothing to save.", "", "bottom_messages", 1500);
	else
		resetAutoSave();
}
