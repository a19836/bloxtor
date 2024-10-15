<?php
include $EVC->getViewPath("presentation/create_presentation_uis_diagram");

if (!empty($new_path)) {
	$page_name = $db_table . "_" . $task_tag . ($task_tag_action ? "_" . implode("_", $task_tag_action) : "");
	
	$head .= '
	<style>
	.taskflowchart .tasks_menu_hide,
	  .taskflowchart .workflow_menu {
		display:none !important;
	}
	.taskflowchart.with_top_bar_menu .tasks_menu, 
	  .taskflowchart.with_top_bar_menu .tasks_menu_hide, 
	  .taskflowchart.with_top_bar_menu .tasks_flow {
		top:60px;
	}
	.taskflowchart .selected_task_properties {
		font-size:11px;
	}
	.taskflowchart .selected_task_properties .ui-widget {
		font-size:11px;
	}
	.taskflowchart.fixed_properties .selected_task_properties.maximize_properties, 
	  .taskflowchart.fixed_properties .selected_connection_properties.maximize_properties {
		top:60px !important;
	}
	
	.taskflowchart:not(.with_top_bar_menu):not(.reverse) .tasks_menu {
		width:130px !important;
		top: -50px !important;
	    	left: -20px !important;
	}
	.taskflowchart:not(.with_top_bar_menu):not(.reverse) .tasks_flow {
		top:0 !important;
		left:110px !important;
	}
	.taskflowchart:not(.with_top_bar_menu):not(.reverse) .selected_task_properties {
		left: 114px !important;
	    	bottom: 48px !important;
	}
	.taskflowchart:not(.with_top_bar_menu):not(.reverse) .workflow_message {
		bottom:48px !important;
	}

	.taskflowchart .tasks_flow .task_page {
		min-width:100% !important;
		min-height:100% !important;
		position:absolute !important;
		top:-2px !important;
		left:-2px !important;
		right:-2px !important;
		bottom:-2px !important;
		
		background-image:none;
		border:0;
		background:transparent;
	}
	.taskflowchart .tasks_flow .task_page > .task_info,
	  .taskflowchart .tasks_flow .task_page > .info,
	  .taskflowchart .tasks_flow .task_page > .eps,
	  .taskflowchart .tasks_flow .task:hover:after {
		display:none;
	}
	.taskflowchart .tasks_flow .task.task_page > .task_droppable {
		top:0;
	}

	.create_uis_files {
		top:50px !important;
		left:15px !important;
		right:15px !important;
	}
	.create_uis_files .overwrite {
		display:none;
	}
	</style>

	<script>
	var file_block_to_search = "' . str_replace("/src/entity/", "/src/block/", $new_path) . $page_name . '";
	
	$(function () {
		$(".create_uis_files .overwrite input").removeAttr("checked").prop("checked", false);
		
		var top_bar_header = $(".top_bar header");
		top_bar_header.children("ul").remove();
		top_bar_header.append(\'<ul><li class="continue" title="Continue"><a onclick="createPageUIFiles(this)"><i class="icon continue"></i> Continue</a></li></ul>\');
		
		//prepare workflow
		var WF = taskFlowChartObj;
		var taskflowchart = $(".taskflowchart");
		taskflowchart.children(".workflow_menu").remove();
		var tasks_menu = taskflowchart.children(".tasks_menu");
		var page_task_menu = tasks_menu.find(".task_page");
		var page_task_type = page_task_menu.attr("type");
		
		//hide page task
		page_task_menu.hide();
		
		//add default page task to the tasks_flow
		WF.Property.tasks_settings[page_task_type]["is_resizable_task"] = false;
		var page_task_id = WF.ContextMenu.addTaskByType(page_task_type);

		if (page_task_id) {
			var tasks_flow = $("#" + WF.TaskFlow.main_tasks_flow_obj_id);
			var task = WF.TaskFlow.getTaskById(page_task_id);
			var task_droppable = task.children(".task_droppable");
			
			WF.TaskFlow.setTaskLabelByTaskId(page_task_id, {label: "' . $page_name . '"});
			
			if (!WF.TaskFlow.tasks_properties[page_task_id])
				WF.TaskFlow.tasks_properties[page_task_id] = {};
			
			WF.TaskFlow.tasks_properties[page_task_id]["join_type"] = "list";
			
			//add selected task to page task
			var selected_task = tasks_menu.find(".task_' . $task_tag . '");
			var selected_task_type = selected_task.attr("type");
			
			var selected_task_id = WF.ContextMenu.addTaskByType(selected_task_type, {top: 0, left: 0}, task_droppable);
			
			//prepare selected task properties
			PresentationTaskUtil.getDBTables("' . $db_driver . '", "' . $db_type . '"); //update db tables list
			var db_attributes = PresentationTaskUtil.getDBTableAttributes("' . $db_driver . '", "' . $db_type . '", "' . $db_table . '");
			var uis_attributes = [];
			
			if (db_attributes)
				for (var attribute_name in db_attributes)
					uis_attributes.push( {active: 1, name: attribute_name} );
			
			if (!WF.TaskFlow.tasks_properties[selected_task_id])
				WF.TaskFlow.tasks_properties[selected_task_id] = {};
			
			if (!WF.TaskFlow.tasks_properties[selected_task_id]["choose_db_table"])
				WF.TaskFlow.tasks_properties[selected_task_id]["choose_db_table"] = {};
			
			if (!WF.TaskFlow.tasks_properties[selected_task_id]["action"])
				WF.TaskFlow.tasks_properties[selected_task_id]["action"] = {};
			
			WF.TaskFlow.tasks_properties[selected_task_id]["choose_db_table"]["db_driver"] = "' . $db_driver . '";
			WF.TaskFlow.tasks_properties[selected_task_id]["choose_db_table"]["db_type"] = "' . $db_type . '";
			WF.TaskFlow.tasks_properties[selected_task_id]["choose_db_table"]["db_table"] = "' . $db_table . '";
			WF.TaskFlow.tasks_properties[selected_task_id]["attributes"] = uis_attributes;
			
			';
	
	if ($task_tag_action) 
		foreach ($task_tag_action as $tta)
			$head .= '
			WF.TaskFlow.tasks_properties[selected_task_id]["action"]["single_' . $tta . '"] = 1;
			' . ($task_tag == "listing" ? 'WF.TaskFlow.tasks_properties[selected_task_id]["action"]["multiple_' . $tta . '"] = 1;' : '') . '
			
			';
	
	$head .= '
			//prepare page properties
			var page_properties = $("#" + WF.TaskFlow.main_tasks_properties_obj_id + " .task_properties_" + page_task_type.toLowerCase());
			page_properties.find(".file_name, .template, .links, .authentication_tab, .advanced_settings_tab").hide();
			WF.Property.tasks_settings[page_task_type]["task_menu"]["show_set_label_menu"] = false;
			WF.Property.tasks_settings[page_task_type]["task_menu"]["show_start_task_menu"] = false;
			WF.Property.tasks_settings[page_task_type]["task_menu"]["show_delete_menu"] = false;
			
			createPageUIFiles();
		}
	});

	function onCheckedUISFilesHtml(ul) {
		ul.find(".files_statuses table tbody tr td.file_path").each(function(idx, td) {
			td = $(td);
			var file_path = td.text();
			
			if (file_path == \'' . $new_path . $page_name . '\') {
				td.parent().find(".select_file input").removeAttr("checked").prop("checked", false);
				td.parent().hide();
			}
			else if (file_path.indexOf("/src/entity/") != -1 || file_path.indexOf("/src/block/") != -1)
				td.parent().find(".select_file input").attr("checked", "checked").prop("checked", true);
		});
	}

	function onSaveUISFiles(step_5) {
		var selected_block_path = null;
		var tds = step_5.find(".files_statuses table tbody tr td.file_path");
		
		$.each(tds, function(idx, td) {
			td = $(td);
			var file_path = td.text();
			
			if (file_path == \'' . $new_path . $page_name . '\')
				td.parent().hide();
			else if (td.parent().find(".status.status_ok").length > 0) {
				if (file_path.toLowerCase() == file_block_to_search.toLowerCase()) {
					selected_block_path = file_path;
					return false;
				}
				
				var m = file_path.match(/_[0-9]+$/);
				
				if (m && file_path.substr(0, file_path.length - m[0].length).toLowerCase() == file_block_to_search.toLowerCase())
					selected_block_path = file_path;
			}
		});
		
		if (selected_block_path) {
			step_5.find("> .button").hide();
			
			setTimeout(function() {
				if (parent && typeof parent.' . $parent_add_block_func . ' == "function")
					parent.' . $parent_add_block_func . '(selected_block_path);
				else
					alert("Block created successfully");
				
				//Refreshing entities and blocks folder in main tree of the admin advanced panel
				if (window.parent && window.parent.parent && window.parent.parent.refreshAndShowLastNodeChilds && window.parent.parent.mytree && window.parent.parent.mytree.tree_elm) {
					var project = window.parent.parent.$("#" + window.parent.parent.last_selected_node_id).parent().closest("li[data-jstree=\'{\"icon\":\"project\"}\']");
					
					var entities_folder_id = project.children("ul").children("li[data-jstree=\'{\"icon\":\"entities_folder\"}\']").attr("id");
					window.parent.parent.refreshAndShowNodeChildsByNodeId(entities_folder_id);
					
					var blocks_folder_id = project.children("ul").children("li[data-jstree=\'{\"icon\":\"blocks_folder\"}\']").attr("id");
					window.parent.parent.refreshAndShowNodeChildsByNodeId(blocks_folder_id);
				}
			}, 500); //setTimeout is to show the the popup with the step_5 info
		}
	}

	function onCloseCreateUIFiles() {
		$(".top_bar li.continue").show();
	}

	function createPageUIFiles() {
		var tasks = taskFlowChartObj.TaskFlow.getAllTasks();
		var exists_tasks = false;
		var exists_permissions = false;
		
		for (var i = 0; i < tasks.length; i++) {
			var task = $(tasks[i]);
			var task_tag = task.attr("tag");
			
			if (task_tag == "listing" || task_tag == "form" || task_tag == "view") {
				exists_tasks = true;
				
				var task_id = task.attr("id");
				if (taskFlowChartObj.TaskFlow.tasks_properties[task_id] && !$.isEmptyObject(taskFlowChartObj.TaskFlow.tasks_properties[task_id]["users_perms"])) {
					exists_permissions = true;
					break;
				}
			}
		}
		
		if (exists_tasks) {
			var can_continue = exists_permissions ? confirm("We detected that you added some permissions. \nIn order to them to work, the page must be already configured to initialize the logged user. \nIf this is not the case, please click on the CANCEL button.") : true;
			
			if (can_continue) {
				$(".top_bar li.continue").hide();
				createUIFiles();
			}
		}
		else
			taskFlowChartObj.StatusMessage.showError("Please create some tasks first...");
	}
	</script>';
	
	$main_content .= '<script>
	$(".top_bar").addClass("in_popup");
	</script>';
}
?>
