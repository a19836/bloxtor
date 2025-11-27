# JQuery Task flow Chart

> Original Repos:   
> - JQuery Task flow Chart: https://github.com/a19836/jquerytaskflowchart/   
> - Bloxtor: https://github.com/a19836/bloxtor/

## Overview

**JQuery Task flow Chart** is a JavaScript library to draw diagrams, like: work-flows, logic flows, DB tables diagrams, use cases diagrams or any other type of diagrams. Basically you define your widgets and the exit points for each widget and then you connect them through drag and drop.

Check out a live example by opening [index.php](index.php).

Requirements:
- jquery
- jquerymycontextmenu
- jquerymyfancylightbox
- phpjs/parse_str
- leaderline

---

## Usage

```html
<html>
<head>
	<!-- Add jquery lib -->
	<script language="javascript" type="text/javascript" src="lib/jquery/js/jquery-1.8.1.min.js"></script>
	<script language="javascript" type="text/javascript" src="lib/jquery/js/jquery.center.js"></script>
	
	<!-- Add Jquery UI JS and CSS files -->
	<link rel="stylesheet" href="lib/jqueryui/css/jquery-ui-1.11.4.css" type="text/css" />
	<script language="javascript" type="text/javascript" src="lib/jqueryui/js/jquery-ui-1.11.4.min.js"></script>
	
	<!-- Add Fancy LighBox lib -->
	<link rel="stylesheet" href="lib/jquerymyfancylightbox/css/style.css" type="text/css" charset="utf-8" media="screen, projection" />
	<script language="javascript" type="text/javascript" src="lib/jquerymyfancylightbox/js/jquery.myfancybox.js"></script>
	
	<!-- Add LeaderLine main JS and CSS files -->
	<link rel="stylesheet" href="lib/leaderline/leader-line.css" type="text/css" charset="utf-8" />
	<script language="javascript" type="text/javascript" src="lib/leaderline/leader-line.js"></script>
	<script language="javascript" type="text/javascript" src="lib/leaderline/LeaderLineFlowHandler.js"></script>

	<!-- Add TaskFlowChart main JS and CSS files -->
	<link rel="stylesheet" href="css/style.css" type="text/css" charset="utf-8" />
	<link rel="stylesheet" href="css/print.css" type="text/css" charset="utf-8" media="print" />
	<script type="text/javascript" src="js/ExternalLibHandler.js"></script>
	<script type="text/javascript" src="js/TaskFlowChart.js"></script>

	<!-- Add ContextMenu main JS and CSS files -->
	<link rel="stylesheet" href="lib/jquerymycontextmenu/css/style.css" type="text/css" charset="utf-8" />
	<script language="javascript" type="text/javascript" src="lib/jquerymycontextmenu/js/jquery.mycontextmenu.js"></script>

	<!-- Parse_Str -->
	<script type="text/javascript" src="lib/phpjs/functions/strings/parse_str.js"></script>
	
	<style type="text/css">
		.taskflowchart .tasks_menu {padding:10px; box-sizing:border-box;}
		.taskflowchart .tasks_menu .task {background:#00ff00; border-radius:100%;}
	</style>
	<script>
		;(function() {
			//set default configurations
			taskFlowChartObj.ContextMenu.main_tasks_menu_obj_id = "taskflowchart > .tasks_menu";
			taskFlowChartObj.ContextMenu.main_tasks_menu_hide_obj_id = "taskflowchart > .tasks_menu_hide";
			taskFlowChartObj.TaskFlow.main_tasks_flow_obj_id = "taskflowchart > .tasks_flow";
			taskFlowChartObj.TaskFlow.main_tasks_properties_obj_id = "taskflowchart > .tasks_properties";
			taskFlowChartObj.TaskFlow.main_connections_properties_obj_id = "taskflowchart > .connections_properties";
			
			//init tasks flow chart
			taskFlowChartObj.init();
		})();
	</script>
</head>
<body>
	<!-- Task Flow Chart -->
	<div id="taskflowchart" class="taskflowchart">
	
		<!-- TASKS SIDE BAR -->
		<div class="tasks_menu">
			<!-- 
				HERE IS WHERE YOU WRITE AVAILABLE TASKS.
				EACH TASK SHOULD HAVE THE CLASSES "task", "task_menu", "task_" FOLLOWED BY THE TASK TYPE ID, AND THE CLASS WITH THE TASK TYPE ID. 
				ADDITIONALLY MUST HAVE THE ATTRIBUTES: TYPE AND TAG. 
				THE INNER HTML CAN BE WHATEVER YOU WANT.
			-->
			
			<div class="task task_menu task_tasktypea task_a" type="tasktypea" tag="a" title="A"><span>A</span></div>
			<div class="task task_menu task_tasktypeb task_b" type="tasktypeb" tag="b" title="B">B</div>
			
			<!-- 
				OR IF YOU WISH YOU CAN ALSO ORGANIZE TASKS IN GROUPS, IF APPLY. (OPTIONAL)
				IF GROUPS EXISTS, THEN THEY MUST BE INSIDE OF A NODE WITH A CLASS "tasks_groups".
				EACH GROUP MUST HAVE THE CLASS "tasks_group".
				INSIDE OF EACH GROUP THE TASKS SHOULD BE INSIDE OF THE CHILDREN WITH THE CLASS "tasks_group_tasks".
			
				<ul class="tasks_groups">
					<li class="tasks_group tasks_group_others">
						<div class="tasks_group_label">Some Tasks</div>
						<div class="tasks_group_tasks">
							<div class="task task_menu task_tasktypea task_a" type="tasktypea" tag="a" title="A"><span>A</span></div>
							<div class="task task_menu task_tasktypeb task_b" type="tasktypeb" tag="b" title="B">B</div>
						</div>
						<div style="clear:left; float:none;"></div>
					</li>
					<li class="tasks_group tasks_group_others">
						<div class="tasks_group_label">Others</div>
						<div class="tasks_group_tasks">
							<div class="empty">There are no tasks available in this group</div>
						</div>
						<div style="clear:left; float:none;"></div>
					</li>
				</ul>
			-->
		</div>
		
		<!-- TASKS MENU HIDE -->
		<div class="tasks_menu_hide">
			<div class="button" onclick="taskFlowChartObj.ContextMenu.toggleTasksMenuPanel(this)"></div>
		</div>
		
		<!-- TASKS FLOW - CANVAS -->
		<div class="tasks_flow"></div>
		
		<!-- TASKS PROPERTIES -->
		<div class="tasks_properties" style="display:none">
			<!-- 
				HERE IS WHERE YOU WRITE THE HTML FOR THE PROPERTIES OF EACH TASK. 
				THE TASK PROPERTIES NODE MUST HAVE THE CLASSES "task_properties" AND "task_properties_" FOLLOWED BY THE TASK TYPE ID. 
				THEN, INSIDE OF THIS NODE, YOU CAN WRITE WHATEVER YOU WANT.
			-->
			
			<!-- TASK A PROPERTIES -->
			<div class="task_properties task_properties_tasktypea">
				<!-- define the html shown in the task properties. Fields to be save must have the class task_property_field. -->
				Some html here for task A
				
				<!-- define the exit points for your task. 2 exit points defined: -->
				<div class="task_property_exit" exit_id="true" exit_color="#51D87A" exit_label="True"></div>
				<div class="task_property_exit" exit_id="false" exit_color="#FF4D4D" exit_label="False"></div>
			</div>
			
			<!-- TASK B PROPERTIES -->
			<div class="task_properties task_properties_tasktypeb">
				<!-- define the html shown in the task properties. Fields to be save must have the class task_property_field. -->
				Some html here for task B
				
				<!-- define the exit points for your task. 1 exit point defined: -->
				<div class="task_property_exit" exit_id="default_exit" exit_color="#000" exit_label="Default"></div>
			</div>
		</div>
		
		<!-- CONNECTION PROPERTIES -->
		<div class="connections_properties" style="display:none">
			<!-- 
				HERE IS WHERE YOU WRITE THE HTML FOR THE PROPERTIES OF EACH CONNECTION. 
				THE CONNECTION PROPERTIES NODE MUST HAVE THE CLASSES "connection_properties" AND "connection_properties_" FOLLOWED BY THE TASK TYPE ID. 
				THEN, INSIDE OF THIS NODE, YOU CAN WRITE WHATEVER YOU WANT.
			-->
			
			<!-- CONNECTION A PROPERTIES -->
			<div class="connection_properties connection_properties_tasktypea">
				<!-- define the html shown in the connection properties. Fields to be save must have the class connection_property_field. -->
				Some html here for connection A
			</div>
			
			<!-- CONNECTION B PROPERTIES -->
			<div class="connection_properties connection_properties_tasktypeb">
				<!-- define the html shown in the connection properties. Fields to be save must have the class connection_property_field. -->
				Some html here for connection B
			</div>
		</div>
	</div>
</body>
</html>
```

## Other calls

For each task you can define specific configurations also - before you call the init method:
```
taskFlowChartObj.Property.tasks_settings = {
	"tasktypea":{ //type of the task. This is unique
		"task_menu":{
			"show_context_menu":true,
			"show_set_label_menu":true,
			"show_properties_menu":true,
			"show_start_task_menu":true,
			"show_delete_menu":true
		},
		"connection_menu":{
			"show_context_menu":true,
			"show_set_label_menu":true,
			"show_properties_menu":true,
			"show_connector_types_menu":true,
			"show_overlay_types_menu":true,
			"show_delete_menu":true
		},
		"callback":{
			"on_load_task_properties": function(properties_html_elm, task_id, task_property_values) {},
			"on_submit_task_properties":function(properties_html_elm, task_id, task_property_values) {},
			"on_complete_task_properties":function(properties_html_elm, task_id, task_property_values, status) {},
			"on_cancel_task_properties":function(properties_html_elm, task_id, task_property_values) {},
			
			"on_load_connection_properties":function(properties_html_elm, connection, connection_properties_values) {},
			"on_submit_connection_properties":function(properties_html_elm, connection, connection_properties_values) {},
			"on_complete_connection_properties":function(properties_html_elm, connection, connection_properties_values, status) {},
			"on_cancel_connection_properties":function(properties_html_elm, connection, connection_properties_values) {},
			
			"on_start_task_label":function(task_id) {},
			"on_check_task_label":function(label_obj, task_id) {},
			"on_submit_task_label":function(label_obj, task_id) {},
			"on_cancel_task_label":function(task_id) {},
			"on_complete_task_label":function(task_id) {},
			
			"on_check_connection_label":function(label_obj, connection_id) {},
			"on_submit_connection_label":function(label_obj, connection_id) {},
			"on_cancel_connection_label":function(connection_id) {},
			"on_complete_connection_label":function(label_overlay, connection_id) {},
			
			"on_success_task_cloning":function(task_id) {},
			"on_success_task_append":function(task_id) {},
			"on_success_task_creation":function(task_id) {},
			"on_check_task_deletion":function(task_id, task) {},
			"on_success_task_deletion":function(task_id, task) {},
			"on_task_drag_stop_validation":function(task) {},
			"on_task_drag_stop_end":function(task) {},
			
			"on_success_task_between_connection":function(task_id) {},
			"on_success_connection_drag":function(connection) {},
			"on_success_connection_drop":function(connection) {},
			"on_success_connection_deletion":function(connection) {},
			
			"on_show_task_menu":function(task_id, j_task, task_context_menu) {},
			"on_show_connection_menu":function(connection_id, connection, connection_context_menu) {},
			
			"on_click_task":function(task_id, task) {},
			"on_click_connection":function(connection) {},
		},
		"center_inner_elements":true,
		"is_resizable_task":true,
		"allow_inner_tasks_outside_connections":true,
	},
	"tasktypeb":{ //type of the task. This is unique
		//...
	}
};
```

And you can also redefine a bunch of configurations for the taskFlowChartObj before you call the init method, this is:
```
//set default configurations
taskFlowChartObj.setTaskFlowChartObjOption("is_droppable_connection", true);
taskFlowChartObj.setTaskFlowChartObjOption("add_default_start_task", true);
taskFlowChartObj.setTaskFlowChartObjOption("resizable_task_properties", true);
taskFlowChartObj.setTaskFlowChartObjOption("resizable_connection_properties", true);

taskFlowChartObj.ContextMenu.main_tasks_menu_obj_id = "taskflowchart > .tasks_menu";
taskFlowChartObj.ContextMenu.main_tasks_menu_hide_obj_id = "taskflowchart > .tasks_menu_hide";
taskFlowChartObj.ContextMenu.main_workflow_menu_obj_id = "taskflowchart > .workflow_menu";

//define the appearance of the task/connection properties 
taskFlowChartObj.setTaskFlowChartObjOption("fixed_properties", false); //starts with fixed_properties
taskFlowChartObj.setTaskFlowChartObjOption("fixed_side_properties", true); //starts with fixed_side_properties

//set tasks flow canvas configurations
taskFlowChartObj.TaskFlow.default_connection_connector = "Straight";
taskFlowChartObj.TaskFlow.default_connection_hover_color = null;
taskFlowChartObj.TaskFlow.main_tasks_flow_obj_id = "taskflowchart > .tasks_flow";
taskFlowChartObj.TaskFlow.main_tasks_properties_obj_id = "taskflowchart > .tasks_properties";
taskFlowChartObj.TaskFlow.main_connections_properties_obj_id = "taskflowchart > .connections_properties";

//set some default callbacks
//taskFlowChartObj.setTaskFlowChartObjOption("on_init_function", function(WF) {});
//taskFlowChartObj.setTaskFlowChartObjOption("on_resize_panels_function", function(WF, height) {});
//taskFlowChartObj.setTaskFlowChartObjOption("on_toggle_properties_panel_side_function", function(properties_panel, properties_panel_resizable) {});

//set tasks files configurations
//taskFlowChartObj.TaskFile.get_tasks_file_url = "server/get_tasks.php";
//taskFlowChartObj.TaskFile.set_tasks_file_url = "server/set_tasks.php";

//set other tasks files configurations
//taskFlowChartObj.TaskFile.update_task_panel_id = "tasks_flow_update_task_panel_confirmation_98123";
//taskFlowChartObj.TaskFile.update_connection_panel_id = "tasks_flow_update_connection_panel_confirmation_98123";

//set other tasks flow canvas configurations
taskFlowChartObj.TaskFlow.task_class_name = "task";
taskFlowChartObj.TaskFlow.start_task_class_name = "is_start_task";
taskFlowChartObj.TaskFlow.task_info_class_name = "task_info";
taskFlowChartObj.TaskFlow.task_info_tag_class_name = "task_info_tag";
taskFlowChartObj.TaskFlow.task_info_label_class_name = "task_info_label";
taskFlowChartObj.TaskFlow.task_short_actions_class_name = "short_actions";
taskFlowChartObj.TaskFlow.task_label_class_name = "info";
taskFlowChartObj.TaskFlow.task_full_label_class_name = "full_info";
taskFlowChartObj.TaskFlow.task_eps_class_name = "eps";
taskFlowChartObj.TaskFlow.task_ep_class_name = "ep";
taskFlowChartObj.TaskFlow.task_droppable_class_name = "task_droppable";

taskFlowChartObj.TaskFlow.default_label = "";
taskFlowChartObj.TaskFlow.default_connection_connector = "StateMachine";
taskFlowChartObj.TaskFlow.default_connection_overlay = "Forward Arrow";
taskFlowChartObj.TaskFlow.default_connection_hover_color = "#CCC";
taskFlowChartObj.TaskFlow.default_on_source_connection_color = "#555";
taskFlowChartObj.TaskFlow.default_connection_line_width = 1;
taskFlowChartObj.TaskFlow.default_connection_from_target = false;
taskFlowChartObj.TaskFlow.default_connection_z_index = 10;
taskFlowChartObj.TaskFlow.default_similar_connections_gap = 20;

taskFlowChartObj.TaskFlow.available_connection_connectors_type = [
	"Straight",
	"Bezier",
	"StateMachine",
	"Flowchart"
];

taskFlowChartObj.TaskFlow.available_connection_overlays_type = [
	"Forward Arrow",
	"Backward Arrow",
	"Both Arrows",
	"No Arrows",
	"No Arrows With Directional Arrow"
];

taskFlowChartObj.TaskFlow.available_connection_connectors = [
	[ "Straight", { stub:5, gap:0}, { cssClass:"myCssClass" } ],
	[ "Bezier", { curviness:10 }, { cssClass:"myCssClass" } ],
	[ "StateMachine", { margin:5, curviness:10, proximityLimit:80 }, { cssClass:"myCssClass" } ],
	[ "Flowchart", { stub:5, alwaysRespectStubs:false, gap:0, midpoint:0.5, cornerRadius:0}, { cssClass:"myCssClass" } ]
];

taskFlowChartObj.TaskFlow.available_connection_overlays = [
	//0: "Forward Arrow" or "Both Arrows"
	[ 
		"Arrow", 
		{ 
			id: "arrow_forward",
		      	location: 0.85,  //location for jsplumb. Note that this cannot be 1 or we will get a javascript error from jsplumb
		      	position: "end", //position for leaderline
			foldback: 0.8,
	      		length: 10,
	      		width: 10,
		}
	],
	//1: "Backward Arrow" or "Both Arrows"
	 	[ 
		"Arrow", 
		{ 
			id: "arrow_backward",
		      	location: 0.15, //location for jsplumb
		      	position: "start", //position for leaderline
			length: 14,
	      		width: 18,
	      		foldback: 0.8,
	      		direction:-1
		}
	],
	//2: "One To Many" or "Many To Many"
	[ 
		"Diamond", 
		{ 
			id: "diamond_forward",
		      	location: 0.97, //location for jsplumb
		      	position: "end", //position for leaderline
			foldback: 2,
	      		length: 18,
	      		width: 13,
			cssClass: "diamond_forward to_diamond",
		}
	],
	//3: "Many To One" or "Many To Many"
	 	[ 
		"Diamond", 
		{ 
			id: "diamond_backward",
		      	location: 0.08, //location for jsplumb
		      	position: "start", //position for leaderline
			foldback: 2,
	      		length: 18,
	      		width: 13,
			cssClass: "diamond_backward from_diamond",
		}
	],
	//4: 
	[
		"Custom", 
		{
			id: "custom_select",
			create: function(component) {
				return $("<select id='myDropDown'><option value='foo'>foo</option><option value='bar'>bar</option></select>");                
			},
			location: 0.2, //location for jsplumb
		      	position: "start", //position for leaderline
		}
	],
	//5: 
	[
		"Custom", 
		{
			id: "custom_input",
			create: function(component) {
				return $("<input type='text' id='myTextInput' value='Foo' style='width:50px;'/>");                
			},
			location: 0.8, //location for jsplumb
		      	position: "end", //position for leaderline
		}
	],
	//6: "Many To One" or "One To One"
	[
		"Custom", 
		{
			id: "to_one",
			create: function(component) {
				return $("<div>1</div>");
			},
			cssClass: "connector_overlay_text_one to_one",
			location: 0.95, //location for jsplumb
		      	position: "end", //position for leaderline
		}
	],
	//7: "One To Many" or "Many To Many"
	[
		"Custom", 
		{
			id: "to_many",
			create: function(component) {
				return $("<div>*</div>");
			},
			cssClass: "connector_overlay_text_many to_many",
			location: 0.95, //location for jsplumb
		      	position: "end", //position for leaderline
		}
	],
	//8: "One To Many" or "One To One"
	[
		"Custom", 
		{
			id: "from_one",
			create: function(component) {
				return $("<div>1</div>");
			},
			cssClass: "connector_overlay_text_one from_one",
			location: 0.08, //location for jsplumb
		      	position: "start", //position for leaderline
		}
	],
	//9: "Many To One" or "Many To Many"
	[
		"Custom", 
		{
			id: "from_many",
			create: function(component) {
				return $("<div>*</div>");
			},
			cssClass: "connector_overlay_text_many from_many",
			location: 0.08, //location for jsplumb
		      	position: "start", //position for leaderline
		}
	],
	//10: "One To Many" or "Many To One" or "One To One" or "Many To Many" or "No Arrows With Directional Arrow"
	[ 
		"Arrow", 
		{ 
			id: "arrow_middle",
		      	location: 0.5, //location for jsplumb
		      	position: "end", //position for leaderline
			foldback: 0.8,
	      		length: 4,
	      		width: 5,
		}
	],
];

taskFlowChartObj.TaskFlow.connection_label_overlay = ["Label", { 
	id: "label",
	label: this.default_label, 
	cssClass: "connector_overlay_label",
	location: 0.6, //location for jsplumb
	position: "middle" //position for leaderline
}];

taskFlowChartObj.TaskFlow.connection_add_icon_overlay = ["Custom", {
	id: "addicon",
	cssClass: "connector_overlay_add_icon",
	location: 0.4, //location for jsplumb
	position: "middle", //position for leaderline
	create: function(component) {
		return $('<span title="Add new task in between">Add</span>');
	},
	events: {
		 	"click": function(labelOverlay, originalEvent) { 
		 		if (originalEvent) {
				if (originalEvent.preventDefault) originalEvent.preventDefault(); 
				else originalEvent.returnValue = false;
			}
			
			var connection_id = labelOverlay && labelOverlay.component && labelOverlay.component.canvas && typeof labelOverlay.component.canvas.getAttribute == "function" ? labelOverlay.component.canvas.getAttribute("connection_id") : null;
			var connection = taskFlowChartObj.TaskFlow.getConnection(connection_id);
			
			taskFlowChartObj.ContextMenu.showConnectionAddNewTaskPanel(originalEvent, connection);
			return false;
	 	}
	 	}
}];

//set other tasks sort configurations
taskFlowChartObj.TaskSort.task_margins_left_and_right_average = 30;
taskFlowChartObj.TaskSort.task_margins_top_and_bottom_average = 70;
taskFlowChartObj.TaskSort.start_left_position = 100;
taskFlowChartObj.TaskSort.start_top_position = 50;

//set other tasks property configurations
taskFlowChartObj.Property.selected_task_properties_id = "selected_task_properties";
taskFlowChartObj.Property.selected_task_properties_classes = "selected_task_properties";
taskFlowChartObj.Property.selected_connection_properties_id = "selected_connection_properties";
taskFlowChartObj.Property.selected_connection_properties_classes = "selected_connection_properties";
taskFlowChartObj.Property.selected_connection_properties_id_text = 'Connection ID: "<span></span>"';
taskFlowChartObj.Property.selected_task_properties_id_text = 'Task Type: "<span class="task_tag"></span>", Task ID: "<span class="task_id"></span>"';
	
//set other tasks context menus configurations
taskFlowChartObj.ContextMenu.main_workflow_menu_obj_id = "workflow_menu";
taskFlowChartObj.ContextMenu.main_tasks_menu_obj_id = "tasks_menu";
taskFlowChartObj.ContextMenu.main_tasks_menu_hide_obj_id = "tasks_menu_hide";
taskFlowChartObj.ContextMenu.task_menu_class_name = "task";
taskFlowChartObj.ContextMenu.tasks_group_label_class_name = "tasks_group_label";
taskFlowChartObj.ContextMenu.tasks_group_tasks_class_name = "tasks_group_tasks";
taskFlowChartObj.ContextMenu.task_context_menu_id = "task_context_menu";
taskFlowChartObj.ContextMenu.task_context_menu_classes = "task_context_menu";
taskFlowChartObj.ContextMenu.connection_context_menu_id = "connection_context_menu";
taskFlowChartObj.ContextMenu.connection_context_menu_classes = "connection_context_menu";
taskFlowChartObj.ContextMenu.connection_add_new_task_panel_id = "connection_add_new_task_panel";

taskFlowChartObj.ContextMenu.task_html = '<div class="#task_class_name# task_#task_type# task_#task_tag# #start_task_class_name#" id="#task_id#" type="#task_type#" tag="#task_tag#" is_start_task="#is_start_task#" is_resizable_task="#is_resizable_task#" title="#task_title#">'
			+ '<div class="start_task_overlay"></div>'
			+ '<div class="#task_info_class_name#">'
				+ '<span class="#task_info_tag_class_name#" title="#task_info_tag#">#task_info_tag#</span>'
				+ '<span class="#task_info_label_class_name#" title="#task_info_label#">#task_info_label#</span>'
			+ '</div>'
			+ '<div class="#task_short_actions_class_name#">'
				+ '<span class="move_action"></span>'
				+ '<span class="context_menu_action"></span>'
			+ '</div>'
			+ '<div class="#task_full_label_class_name#">#task_label#</div>'
			+ '<div class="#task_label_class_name#">'
				+ '<span>#task_label#</span>'
			+ '</div>'
			+ '<div class="#task_eps_class_name#">#exit_html#</div>'
		+ '</div>';
taskFlowChartObj.ContextMenu.task_exit_html = '<div class="#task_ep_class_name#"></div>';

//set other tasks containers configurations
taskFlowChartObj.Container.tasks_containers_class_name = "task_container";

//set other tasks messages configurations
taskFlowChartObj.StatusMessage.message_html_obj_id = "workflow_message_98123";
```

