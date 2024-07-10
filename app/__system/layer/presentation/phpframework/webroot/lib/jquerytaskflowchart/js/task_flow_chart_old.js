/*
 * Copyright (c) 2007 PHPMyFrameWork - Joao Paulo Lopes Pinto -- http://jplpinto.com
 * The use of this code must comply with the Bloxtor framework license.
 */

//CREATING THE jsPlumbWorkFlow object from the jsPlumbWorkFlowHandler class.
;(function() {
	window.jsPlumbWorkFlow = new jsPlumbWorkFlowHandler("jsPlumbWorkFlow");
})();

//DEFINING nextColor FUNCTION IF THEY DOESN'T EXIST YET.
if(typeof nextColor !== 'function') {
	var curColourIndex = 1;
	var maxColourIndex = 24;

	function nextColor() {
		var R, G, B;
	
		R = parseInt(128 + Math.sin((curColourIndex * 3 + 0) * 1.3) * 128);
		G = parseInt(128 + Math.sin((curColourIndex * 3 + 1) * 1.3) * 128);
		B = parseInt(128 + Math.sin((curColourIndex * 3 + 2) * 1.3) * 128);
	
		curColourIndex = curColourIndex + 1;
	
		if (curColourIndex > maxColourIndex) {
			curColourIndex = 1;
		}
	
		return "rgb(" + R + "," + G + "," + B + ")";
	}
}

//DEFINING randomColor FUNCTION IF THEY DOESN'T EXIST YET.
if(typeof randomColor !== 'function') {
	function randomColor() {
		var R, G, B;
	
		var max = 255;
		var min = 0;
	
		R = Math.floor(Math.random() * (max - min)) + min;
		G = Math.floor(Math.random() * (max - min)) + min;
		B = Math.floor(Math.random() * (max - min)) + min;
	
		return "rgb(" + R + "," + G + "," + B + ")";
	}
}

//DEFINING trim FUNCTION IF THEY DOESN'T EXIST YET.
//Leave this code here, because is adding the TRIM function to the IE browsers. Otherwise the browser gives errors.
if(typeof String.prototype.trim !== 'function') {
	String.prototype.trim = function() {
		return this.replace(/^\s+|\s+$/g, ''); 
	}
}

//DEFINING hashCode FUNCTION IF THEY DOESN'T EXIST YET.
//Leave this code here, because is adding the hashCode function to all browsers.
if(typeof String.prototype.hashCode !== 'function') {
	String.prototype.hashCode = function(){
		var hash = 0;
		if (this.length == 0) return hash;
		for (i = 0; i < this.length; i++) {
			char = this.charCodeAt(i);
			hash = ((hash<<5)-hash)+char;
			hash = hash & hash; // Convert to 32bit integer
		}
		return hash;
	}
}

//Leave this code here, because is a generic function that is used in multiple places
if (typeof assignObjectRecursively != "function")
	function assignObjectRecursively(to_obj, from_obj) {
		//return Object.assign(to_obj, from_obj); //Note that Object.assign doesn't copy the inner objects, which means it will remain with the references for the inner objects. Basically the Object.assign only clones the properties in the first level.
		//Do not use JSON.parse(JSON.stringify(from_obj)), bc obj may contain DOM objects that will loose its references, and this elements we want to keep their reference.
		
		if ($.isPlainObject(from_obj)) {
			for (var k in from_obj) {
				var v = from_obj[k];
				
				if ($.isPlainObject(v))
					to_obj[k] = assignObjectRecursively({}, v);
				else if ($.isArray(v))
					to_obj[k] = assignObjectRecursively([], v);
				else
					to_obj[k] = v;
			}
			
			return to_obj;
		}
		else if ($.isArray(from_obj)) {
			for (var i = 0, t = from_obj.length; i < t; i++) {
				var v = from_obj[i];
				
				if ($.isPlainObject(v))
					to_obj[i] = assignObjectRecursively({}, v);
				else if ($.isArray(v))
					to_obj[i] = assignObjectRecursively([], v);
				else
					to_obj[i] = v;
			}
			
			return to_obj;
		}
		
		return from_obj;
	}

//DEFINING jsPlumbWorkFlowHandler FUNCTION
function jsPlumbWorkFlowHandler(jsPlumbWorkFlowObjVarName, jsPlumbWorkFlowObjOptions) {
	/* #ADD_SECURITY_CODE_HERE# */  //Important: do not remove this comment because it will be used by the other/scripts/obfuscate_js_files.php to only allow this code in the allowed domains.
	
	var WF = this;
	
	var ExternalLibCloneBridge = new ExternalLibHandler();
	var ExternalLibClone = null;
	
	var MyFancyPopupClone = new MyFancyPopupClass();
	var is_inited = false;
	
	/* 
	 * jsPlumbWorkFlowObjOptions = {
	 * 	is_droppable_connection : true/false,
	 * 	add_default_start_task : true/false,
	 * 	resizable_task_properties : true/false,
	 * 	resizable_connection_properties : true/false,
	 * 	fixed_properties : true/false,
	 * 	fixed_side_properties : true/false,
	 * 	on_init_function : null/function(WF) {...},
	 * 	on_resize_panels_function : null/function(WF, height) {...},
	 * 	on_toggle_properties_panel_side_function : null/function(properties_panel, properties_panel_resizable) {...},
	 * }
	 */
	WF.jsPlumbWorkFlowObjOptions = jsPlumbWorkFlowObjOptions;
	
	WF.setjsPlumbWorkFlowObjOption = function(option_name, option_value) {
		if (!WF.jsPlumbWorkFlowObjOptions || !$.isPlainObject(WF.jsPlumbWorkFlowObjOptions))
			WF.jsPlumbWorkFlowObjOptions = {};
		
		WF.jsPlumbWorkFlowObjOptions[option_name] = option_value;
	};
	
	WF.getMyFancyPopupObj = function() {
		return MyFancyPopupClone;
	};
	
	WF.getJsPlumbWorkFlowObjVarName = function() {
		return jsPlumbWorkFlowObjVarName;
	};
	
	WF.isInitialized = function() {
		return is_inited;
	};
	
	WF.init = function() {
		WF.onReady(function() {
			ExternalLibCloneBridge.init();
			ExternalLibClone = ExternalLibCloneBridge.jsPlumbInstance;
			//ExternalLibClone = ExternalLibCloneBridge; //in the future the ideia is to remove this line so the ExternalLibClone be directly the ExternalLibCloneBridge obj
			
			// chrome fix.
			document.onselectstart = function () { return false; };
			
			WF.initRenderMode(ExternalLibClone.SVG); 
			
			WF.initOptions();
			
			WF.jsPlumbTaskFlow.init();
		     WF.jsPlumbContextMenu.init();
		    	WF.jsPlumbContainer.init();
		     WF.jsPlumbProperty.init();
			WF.jsPlumbTaskFile.init();
			WF.jsPlumbStatusMessage.init();
			
			WF.resizePanels();
			
			$(window).resize(function() {
				WF.resizePanels();
			});
			
			if (WF.jsPlumbWorkFlowObjOptions && WF.jsPlumbWorkFlowObjOptions.hasOwnProperty("on_init_function") && typeof WF.jsPlumbWorkFlowObjOptions.on_init_function == "function")
				WF.jsPlumbWorkFlowObjOptions.on_init_function(WF);
			
			is_inited = true;
		});
	};
	
	WF.initOptions = function() {
		if (WF.jsPlumbWorkFlowObjOptions) {
			var parent = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id).parent().closest(".taskflowchart");
			
			if (WF.jsPlumbWorkFlowObjOptions["resizable_task_properties"])
				parent.addClass("resizable_task_properties");
			else if (parent.hasClass("resizable_task_properties"))
				WF.jsPlumbWorkFlowObjOptions["resizable_task_properties"] = true;
			
			if (WF.jsPlumbWorkFlowObjOptions["resizable_connection_properties"])
				parent.addClass("resizable_connection_properties");
			else if (parent.hasClass("resizable_connection_properties"))
				WF.jsPlumbWorkFlowObjOptions["resizable_connection_properties"] = true;
			
			if (WF.jsPlumbWorkFlowObjOptions["fixed_properties"])
				parent.addClass("fixed_properties");
			else if (parent.hasClass("fixed_properties"))
				WF.jsPlumbWorkFlowObjOptions["fixed_properties"] = true;
			
			if (WF.jsPlumbWorkFlowObjOptions["fixed_side_properties"])
				parent.addClass("fixed_side_properties");
			else if (parent.hasClass("fixed_side_properties"))
				WF.jsPlumbWorkFlowObjOptions["fixed_side_properties"] = true;
			
			if (WF.jsPlumbWorkFlowObjOptions["on_init_function"] && typeof WF.jsPlumbWorkFlowObjOptions["on_init_function"] == "string")
				eval('WF.jsPlumbWorkFlowObjOptions["on_init_function"] = ' + WF.jsPlumbWorkFlowObjOptions["on_init_function"] + ';');
			
			if (WF.jsPlumbWorkFlowObjOptions["on_resize_panels_function"] && typeof WF.jsPlumbWorkFlowObjOptions["on_resize_panels_function"] == "string")
				eval('WF.jsPlumbWorkFlowObjOptions["on_resize_panels_function"] = ' + WF.jsPlumbWorkFlowObjOptions["on_resize_panels_function"] + ';');
			
			if (WF.jsPlumbWorkFlowObjOptions["on_toggle_properties_panel_side_function"] && typeof WF.jsPlumbWorkFlowObjOptions["on_toggle_properties_panel_side_function"] == "string")
				eval('WF.jsPlumbWorkFlowObjOptions["on_toggle_properties_panel_side_function"] = ' + WF.jsPlumbWorkFlowObjOptions["on_toggle_properties_panel_side_function"] + ';');
		}
	};
	
	WF.onReady = function(handler) {
		return ExternalLibCloneBridge.onReady(handler);
	};
	
	WF.resizePanels = function() {
		var workflow_menu = $("#" + WF.jsPlumbContextMenu.main_workflow_menu_obj_id);
		var tasks_menu = $("#" + WF.jsPlumbContextMenu.main_tasks_menu_obj_id);
		var tasks_menu_hide = $("#" + WF.jsPlumbContextMenu.main_tasks_menu_hide_obj_id);
		var tasks_flow = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id);
		
		var height = parseInt(workflow_menu.height());
		
		if (height > 0) {
			var top = parseInt(workflow_menu.css("top")) + height + 1;
			
			tasks_menu.css("top", top + "px");
			tasks_menu_hide.css("top", top + "px");
			tasks_flow.css("top", top + "px");
		}
		
		WF.jsPlumbTaskFlow.zoomRefresh();
		
		if (WF.jsPlumbWorkFlowObjOptions && WF.jsPlumbWorkFlowObjOptions.hasOwnProperty("on_resize_panels_function") && typeof WF.jsPlumbWorkFlowObjOptions.on_resize_panels_function == "function")
			WF.jsPlumbWorkFlowObjOptions.on_resize_panels_function(WF, height);
	};
		
	WF.reinit = function() {
		var tasks = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id + " ." + WF.jsPlumbTaskFlow.task_class_name);
		for (var i = 0; i < tasks.length; i++) {
			task = tasks[i];
			
			if (task.id)
				WF.jsPlumbTaskFlow.deleteTask(task.id, {confirm: false});
		};
		
		ExternalLibClone.detachEveryConnection();
		ExternalLibClone.deleteEveryEndpoint();
		ExternalLibClone.unmakeEverySource();
		ExternalLibClone.unmakeEveryTarget();
		
		//ExternalLibClone.selectEndpoints().each(function(e){console.log(e)});
		
		ExternalLibClone.reset();
		WF.initRenderMode(ExternalLibClone.SVG); 
		
		WF.jsPlumbTaskFlow.init();
		
		WF.resizePanels();
	};
	
	// init render mode
	WF.initRenderMode = function(mode) {
		var ret = ExternalLibClone.setRenderMode(mode);
	};
     
     // change render mode after initRenderMode, in case we which to change the render mode dynamically.
	WF.changeRenderMode = function(desiredMode) {
		if (WF.jsPlumbTaskFile.reset)
			WF.jsPlumbTaskFile.reset();
			
		if (WF.jsPlumbProperty.reset)
			WF.jsPlumbProperty.reset();
		
		if (WF.jsPlumbContainer.reset)
			WF.jsPlumbContainer.reset();
		
		if (WF.jsPlumbContextMenu.reset)
			WF.jsPlumbContextMenu.reset();
		
		if (WF.jsPlumbTaskFlow.reset) 
			WF.jsPlumbTaskFlow.reset();
		
		ExternalLibClone.reset();
		
		WF.initRenderMode(desiredMode);					
	};
	
	WF.destroy = function() {
		WF.jsPlumbContextMenu.destroy();
		
		var selector = "#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id + 
					", #" + WF.jsPlumbTaskFlow.main_tasks_properties_obj_id + 
					", #" + WF.jsPlumbTaskFlow.main_connections_properties_obj_id + 

					", #" + WF.jsPlumbProperty.selected_task_properties_id + 
					", #" + WF.jsPlumbProperty.selected_connection_properties_id + 

					", #" + WF.jsPlumbContextMenu.main_tasks_menu_obj_id + 
					", #" + WF.jsPlumbContextMenu.main_tasks_menu_hide_obj_id + 
					", #" + WF.jsPlumbContextMenu.task_context_menu_id + 
					", #" + WF.jsPlumbContextMenu.connection_context_menu_id + 

					", #" + WF.jsPlumbTaskFile.update_task_panel_id + 
					", #" + WF.jsPlumbTaskFile.update_connection_panel_id + 

					", #" + WF.jsPlumbStatusMessage.message_html_obj_id;
		
		$(selector).remove();
	};

	WF.jsPlumbTaskFlow = { 
		tasks_properties : {},
		connections_properties : {},
		
		main_tasks_flow_obj_id : "tasks_flow",
		task_class_name : "task",
		start_task_class_name : "is_start_task",
		task_info_class_name : "task_info",
		task_info_tag_class_name : "task_info_tag",
		task_info_label_class_name : "task_info_label",
		task_short_actions_class_name : "short_actions",
		task_label_class_name : "info",
		task_full_label_class_name : "full_info",
		task_eps_class_name : "eps",
		task_ep_class_name : "ep",
		task_droppable_class_name : "task_droppable",
		main_tasks_properties_obj_id : "tasks_properties",
		main_connections_properties_obj_id : "connections_properties",
	
		target_selector : null, 
		source_selector : null,
	
		default_label : "",//...
		default_connection_connector : "StateMachine",
		default_connection_overlay : "Forward Arrow",
		default_connection_hover_color : "#CCC",
		default_on_source_connection_color : "#555",
	
		available_connection_connectors_type : [
			"Straight",
			"Bezier",
			"StateMachine",
			"Flowchart"
		],
	
		available_connection_overlays_type : [
			"Forward Arrow",
			"Backward Arrow",
			"Both Arrows",
			"No Arrows",
			"No Arrows With Directional Arrow"
		],
	
		available_connection_connectors : [
			[ "Straight", { stub:5, gap:0}, { cssClass:"myCssClass" } ],
			[ "Bezier", { curviness:10 }, { cssClass:"myCssClass" } ],
			[ "StateMachine", { margin:5, curviness:10, proximityLimit:80 }, { cssClass:"myCssClass" } ],
			[ "Flowchart", { stub:5, alwaysRespectStubs:false, gap:0, midpoint:0.5, cornerRadius:0}, { cssClass:"myCssClass" } ]
		],
		
		connection_line_width : 1,
		
		available_connection_overlays : [
			//0: "Forward Arrow" or "Both Arrows"
			[ 
				"Arrow", 
				{ 
					id: "arrow_forward",
		            	location: 0.85,  //location for jsplumb. Note that this cannot be 1 or we will get a javascript error from jsplumb
		            	position: "end", //position for leaderline
					foldback: 0.8,
	            		length: 10,
	            		width: 10
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
		            	position: "middle", //position for leaderline
					foldback: 0.8,
	            		length: 4,
	            		width: 5
				}
			],
		],
		
		connection_label_overlay : ["Label", { 
			id: "label",
			label: this.default_label, 
			cssClass: "connector_overlay_label",
			location: 0.6, //location for jsplumb
			position: "middle" //position for leaderline
		}],
  		
  		connection_add_icon_overlay : ["Custom", {
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
					var connection = WF.jsPlumbTaskFlow.getConnection(connection_id);
					
					WF.jsPlumbContextMenu.showConnectionAddNewTaskPanel(originalEvent, connection);
					return false;
			 	}
		    	}
		}],
		
		current_zoom: 1,
		
		getDefaultConnectionOverlays : function() {
			return [ this.connection_label_overlay ];
		},
		
		/* START: INIT METHODS */
		init : function() {
		
			if (!this.target_selector)
				this.target_selector = "#" + this.main_tasks_flow_obj_id + " ." + this.task_class_name;
		
			if (!this.source_selector)
				this.source_selector = "." + this.task_ep_class_name;
		
			//scroll up the main_tasks_flow_obj otherwise it gives a weird behaviour when the user refreshes the pages witht he scroll down.
			var main_tasks_flow_obj = $("#" + this.main_tasks_flow_obj_id);
			main_tasks_flow_obj.addClass("jsplumbtaskworkflow");
			main_tasks_flow_obj.scrollTop(0);
		
			// setup some defaults for ExternalLibClone.
			//TODO check this if si compatible with leaderlinear
			var defaults_to_import = {
				DragOptions : { cursor: 'pointer', zIndex:100 },
				Endpoint : ["Dot", {radius:4}],
				ConnectionOverlays : this.getDefaultConnectionOverlays(),
				ConnectorZIndex:10
			};
			
			if (this.default_connection_hover_color)
				defaults_to_import["hoverPaintStyle"] = {
					strokeStyle: this.default_connection_hover_color
				};
			
			ExternalLibClone.importDefaults(defaults_to_import);

			this.initTargetAndSourceElements();
			
			// bind a right click listener to each connection;
			// listen for right clicks on connections and shows a menu.
			ExternalLibClone.bind("contextmenu", function(connection, originalEvent) {
				if (originalEvent) {
					if (originalEvent.preventDefault) originalEvent.preventDefault(); 
					else originalEvent.returnValue = false;
				}
				
				//console.log(connection);
				//console.log(originalEvent);
				//console.log(originalEvent.target);
		
				var is_source = connection.isSource && connection.getElement ? connection.getElement().hasClass("ep") : false;
				var is_target = connection.hasOwnProperty("isSource") && !connection.isSource && connection.hasOwnProperty("isTarget") && connection.getElement && connection.getElement().hasClass(WF.jsPlumbTaskFlow.task_class_name) && connection.getElement().attr("id");
				
				//PREPARE TASK CONTEXT MENU
				if(is_source || is_target) {
					var target_id = is_source ? connection.getElement().attr("target_id") : connection.getElement().attr("id");
					var target_elm = WF.jsPlumbTaskFlow.getTaskById(target_id);
					var task_type = target_elm.attr("type");
			  		
			  		if (task_type) {
				  		var show_context_menu = WF.jsPlumbProperty.isTaskSubSettingTrue(task_type, "task_menu", "show_context_menu", true);
				  		
				  		if (show_context_menu)
					  		WF.jsPlumbContextMenu.showTaskMenu(originalEvent, target_elm[0]);
				  	}
				  
				  	//FIXING A SMALL BUG FROM JSPLUMB
					var c = WF.jsPlumbTaskFlow.getConnection(connection.id);
					if (!c && connection.canvas && $(connection.canvas).hasClass("_jsPlumb_endpoint"))
						$(connection.canvas).remove();
				}
				//PREPARE CONNECTION CONTEXT MENU
				else {
					var task_type = $("#" + connection.sourceId).attr("type");
					if (task_type) {
						var show_context_menu = WF.jsPlumbProperty.isTaskSubSettingTrue(task_type, "connection_menu", "show_context_menu", true);
				  		
				  		if (show_context_menu)
							WF.jsPlumbContextMenu.showConnectionMenu(originalEvent, connection);
					}
				}
		
				//WF.jsPlumbTaskFlow.printConnection("contextmenu", connection);
		
		  		return false;
			});
			
			//Because of the iOs, otherwise the contextmenu doesn't show.
			ExternalLibClone.bind("click", function(connection, originalEvent) {
				if (originalEvent) {
					if (originalEvent.preventDefault) originalEvent.preventDefault(); 
					else originalEvent.returnValue = false;
				}
				
				var task_type = $("#" + connection.sourceId).attr("type");
				if (task_type) {
					var show_context_menu = WF.jsPlumbProperty.isTaskSubSettingTrue(task_type, "connection_menu", "show_context_menu", true);
					var target = originalEvent && originalEvent.target ? $(originalEvent.target) : null;
			  		var is_arrow_target = !target || !target.is(".connector_overlay_label, .connector_overlay_add_icon");
			  		
			  		if (show_context_menu && is_arrow_target) {
			  			if (originalEvent && originalEvent.stopPropagation) originalEvent.stopPropagation(); //if connection is inside of another, only takes care of the clicked connection, ignoring the parent connection. Currently we don't have any connection inside of another connection, but if in the future we will have it, this code is already prepared.
						
			  			//show connection menu
		  				WF.jsPlumbContextMenu.showConnectionMenu(originalEvent, connection);
		  				
		  				//add handler to be executed everytime a connection gets clicked. Example: show its properties
		  				WF.jsPlumbProperty.callTaskSettingsCallback(task_type, "on_click_connection", [ connection ]);
		  			}
				}
				
				return false;
			});
			
		  	// bind a click listener to each connection;
			// listen for clicks on connections.
			ExternalLibClone.bind("dblclick", function(connection, originalEvent) {
				if (originalEvent) {
					if (originalEvent.preventDefault) originalEvent.preventDefault(); 
					else originalEvent.returnValue = false;
				}
				
				//Hiding menu, bc the single click event will open the menu
				WF.jsPlumbContextMenu.hideContextMenus();
				
				//Changing connector type
				var connection_exit_type = connection.getParameter("connection_exit_type");
				var new_type = WF.jsPlumbTaskFlow.getNextConnectionConnectorType(connection_exit_type);
				WF.jsPlumbTaskFlow.changeConnectionConnectorType(connection, new_type);
				
				//WF.jsPlumbTaskFlow.printConnection("dblclick", conn);
				
				return false;
			});
		  	
		  	ExternalLibClone.bind("jsPlumbConnection", function(conn) {
				//console.log(conn);
				
				var exists_connection_exit_id = false, color = null, connection_overlay = null, connection_type = null, connection_label = null;
				var loaded_from_file = false;
				var source_task_id = conn.sourceId;
			  	var target_task_id = conn.targetId;
			  	var parameters = conn.connection.getParameters();
				//console.log(parameters);
				var source_task_tag = WF.jsPlumbTaskFlow.getTaskById(source_task_id).attr("tag");
		  		var target_task_tag = WF.jsPlumbTaskFlow.getTaskById(target_task_id).attr("tag");
		  		
				//when it loads the connections from a file
				if ( (exists_connection_exit_id = parameters.hasOwnProperty("connection_exit_id")) ) {
					color = parameters.connection_exit_color;
					connection_overlay = parameters.connection_exit_overlay;
					connection_type = parameters.connection_exit_type;
					connection_label = null; //Do not set label bc label will be set later in the WF.jsPlumbTaskFlow.connect method
					loaded_from_file = true;
		    		}
		    		//for new connections that the user does
		    		else if ( (exists_connection_exit_id = conn.sourceEndpoint.element[0].hasAttribute("connection_exit_id")) ) {
		    			conn.connection.setParameter("connection_exit_id", conn.sourceEndpoint.element[0].getAttribute("connection_exit_id") );
		    			
		    			connection_type = conn.sourceEndpoint.element[0].getAttribute("connection_exit_type");
		    			conn.connection.setParameter("connection_exit_type", connection_type);
		    			
		    			connection_overlay = conn.sourceEndpoint.element[0].getAttribute("connection_exit_overlay");
		    			conn.connection.setParameter("connection_exit_overlay", connection_overlay);
		    			
		    			color = conn.sourceEndpoint.element[0].getAttribute("connection_exit_color");
		    			color = color ? color : conn.connection.getPaintStyle().strokeStyle;
		    			
		    			connection_label = conn.sourceEndpoint.element[0].getAttribute("connection_exit_label");
			    	}
				
				var connection_exit_id = conn.connection.getParameter("connection_exit_id");
				
				//console.log(conn.connection.getParameters());
			
				if (!exists_connection_exit_id || !connection_exit_id) {
					if (conn.connection.endpoints[0] && conn.connection.endpoints[1])
						conn.connection.endpoints[0].detachFrom(conn.connection.endpoints[1]);
				
					WF.jsPlumbStatusMessage.showError("ERROR: There was a problem connecting this 2 tasks. Please refresh the page and if the problem persists, please contact the SysAdmin.");
					return false;
				}
				
				//sets the correct connection_type
				if (connection_type)
					WF.jsPlumbTaskFlow.changeConnectionConnectorType(conn.connection, connection_type);
				
				//sets the connection_overlay
				if (connection_overlay)
					WF.jsPlumbTaskFlow.changeConnectionOverlayType(conn.connection, connection_overlay);
				
				//sets the correct connection_color
				if (color)
					WF.jsPlumbTaskFlow.changeConnectionColor(conn.connection, color);
				
				//after the connection is set between 2 tasks, it doesn't let to move it. The solution is to delete and create a new connection.
				conn.sourceEndpoint.setEnabled(false); //conn.connection.endpoints[0]
				conn.targetEndpoint.setEnabled(false); //conn.connection.endpoints[1]
				
				//set some classes
				$(conn.sourceEndpoint.canvas).addClass("source_end_point end_point_for_source_task_" + source_task_tag + " end_point_for_target_task_" + target_task_tag);
				$(conn.targetEndpoint.canvas).addClass("target_end_point end_point_for_source_task_" + source_task_tag + " end_point_for_target_task_" + target_task_tag);
				
				var overlays = conn.connection.getOverlays();
				
				if (overlays)
					for (var i = 0; i < overlays.length; i++) 
						$(overlays[i].canvas).addClass("connector_overlay_for_source_task_" + source_task_tag + " connector_overlay_for_target_task_" + target_task_tag);
				
				//prepare connection label to be editable directly in diagram
				var label_overlay = conn.connection.getOverlay("label");
				var label_overlay_elm = $(label_overlay.canvas);
				label_overlay_elm.attr("contenteditable", "true").attr("spellcheck", "false");
				
				label_overlay_elm.off("click").attr('onclick', ''); //avoids to go to ExternalLibClone.bind("click"...
				label_overlay_elm.on("click", function(originalEvent) {
					originalEvent.stopPropagation(); 
					
					if (originalEvent.preventDefault) originalEvent.preventDefault(); 
					else originalEvent.returnValue = false;
					
					label_overlay_elm.focus();
					
					//in case the focus caret fails...
					setTimeout(function() {
						if (typeof window.getSelection !== "undefined") {
							var selection = window.getSelection();
							var focus_elm = selection ? selection.anchorNode : null;
							
							if (focus_elm && focus_elm.nodeType == Node.TEXT_NODE)
								focus_elm = focus_elm.parentNode;
							
							if (!focus_elm || focus_elm != label_overlay_elm[0])
								WF.jsPlumbTaskFlow.setConnectionLabelByOverlay(label_overlay);
						}
					}, 500);
					
					//return WF.jsPlumbTaskFlow.setConnectionLabelByOverlay(label_overlay);
				});
				label_overlay_elm.on("blur", function(originalEvent) {
					WF.jsPlumbTaskFlow.saveConnectionLabelByOverlay(label_overlay, {
						label: label_overlay_elm.text()
					});
				});
				
				//set default label
				if (connection_label)
					label_overlay.setLabel(connection_label);
				//label_overlay.setLabel( WF.jsPlumbTaskFlow.default_label );
				
				/* DEPRECATED:
				label_overlay.getElement().setAttribute("connection_id", conn.connection.id);
			  	conn.sourceEndpoint.canvas.setAttribute("connection_id", conn.connection.id);
			  	conn.targetEndpoint.canvas.setAttribute("connection_id", conn.connection.id);
			  	
			  	if (conn.connection.canvas && conn.connection.canvas.setAttribute) {
			  		conn.connection.canvas.setAttribute("connection_id", conn.connection.id);
			  	}
			  	else {//in case of IE
			  		conn.connection.connector.canvas.setAttribute("connection_id", conn.connection.id);
			  	}*/
			  	
			  	if (!loaded_from_file) {
				  	if (WF.jsPlumbProperty.tasks_settings) {
				  		var status = true;
				  		
				  		//check if connection is allowed. Example if parent task is a "function" type, doesn't allow outside connections.
				  		if (!WF.jsPlumbTaskFlow.isConnectionAllowed(source_task_id, target_task_id)) {
				  			WF.jsPlumbStatusMessage.showError("ERROR: You cannot connect these 2 tasks because outside connections are not allowed!");
				  			status = false;
				  		}
				  		else {
					  		var source_task_type = WF.jsPlumbTaskFlow.getTaskById(source_task_id).attr("type");
					  		var target_task_type = WF.jsPlumbTaskFlow.getTaskById(target_task_id).attr("type");
					  		
					  		//call callbacks
					  		if(status && !WF.jsPlumbProperty.callTaskSettingsCallback(source_task_type, "on_success_connection_drag", [ conn ]))
					  			status = false;
							
							if(status && !WF.jsPlumbProperty.callTaskSettingsCallback(target_task_type, "on_success_connection_drop", [ conn ]))
					  			status = false;
						}
						
						if (!status) {
							//delete connection
							if (conn.connection.endpoints[0] && conn.connection.endpoints[1])
								conn.connection.endpoints[0].detachFrom(conn.connection.endpoints[1]);
							
							return false;
						}
					}
			  	}
			  	
			  	/*conn.connection.bind("mouseenter", function(c) {
					c.connection.setPaintStyle({strokeStyle:color});
					c.connection.endpoints[0].setPaintStyle({fillStyle:color});
	    				c.connection.endpoints[1].setPaintStyle({fillStyle:color});
				});
			  	
			  	conn.connection.bind("mouseexit", function(c) {
					c.connection.setPaintStyle({strokeStyle:color});
					c.connection.endpoints[0].setPaintStyle({fillStyle:color});
	    				c.connection.endpoints[1].setPaintStyle({fillStyle:color});
				});*/
		  		
		  		//WF.jsPlumbTaskFlow.printConnection("jsPlumbConnection", conn);
		  	});
		  	
		  	ExternalLibClone.bind("connectionDrag", function(conn) {
		  		console.log(conn);
		  	});
		  	
		  	ExternalLibClone.bind("connectionDragEnd", function(conn) {
		  		console.log(conn);
		  	});
		
			//on connection remove
			/*ExternalLibClone.bind("connectionDragStop", function(conn) {
			  	//	connectionDragStop:
				//	connection:undefined
				//	sourceId:inperson
				//	targetId:rejected
				//	source:[object Object]
				//	target:[object Object]
				//	sourceEndpoint:undefined
				//	targetEndpoint:undefined
			  	
			  	//WF.jsPlumbTaskFlow.printConnection("connectionDragStop", conn);
			  	//console.log(conn);
			});*/
			
			this.zoom(this.current_zoom);
		},
		
		initTargetAndSourceElements : function(options) {
			var target_selector = options && options.target_selector ? options.target_selector : this.target_selector;
			var source_selector = options && options.source_selector ? options.source_selector : this.source_selector;
			var connection_exit_props = options && options.connection_exit_props ? options.connection_exit_props : null;
			
			$(target_selector).each(function(i, target_elm) {
				WF.jsPlumbTaskFlow.addTargetElement(target_elm, connection_exit_props);
				
				$(target_elm).find(source_selector).each(function(j, source_elm) {
					WF.jsPlumbTaskFlow.addSourceElement(source_elm, target_elm, connection_exit_props);
				});
			});
		},
	
		initTargetElements : function(options) {
			var target_selector = options && options.target_selector ? options.target_selector : this.target_selector;
			var connection_exit_props = options && options.connection_exit_props ? options.connection_exit_props : null;
		
			$(target_selector).each(function(i, target_elm) {
				WF.jsPlumbTaskFlow.addTargetElement(target_elm, connection_exit_props);
			
				WF.jsPlumbTaskFlow.centerTaskInnerElements(target_elm.id);
			});
		},
	
		initSourceElements : function(options) {
			var source_selector = options && options.source_selector ? options.source_selector : this.source_selector;
			var connection_exit_props = options && options.connection_exit_props ? options.connection_exit_props : null;
		
			$(source_selector).each(function(i, source_elm) {
				$(source_elm).parents(WF.jsPlumbTaskFlow.target_selector).last().each(function(j, parent_elm){
					WF.jsPlumbTaskFlow.addSourceElement(source_elm, parent_elm, connection_exit_props);
				});
			});
		},
		/* END: INIT METHODS */
		
		/* START: TARGET AND SOURCE METHODS */
		addTargetElement : function(target_elm, connection_exit_props) {
			var color = connection_exit_props && connection_exit_props.color ? connection_exit_props.color : null;
			var main_tasks_flow_obj = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id);
			var j_target_elm = $(target_elm);
		
			if (color) {
				j_target_elm.css({"border-color" : color});
			}
		
			color = color ? color : nextColor();
			
			//set event to rename task
			WF.jsPlumbTaskFlow.getTaskLabelElement(j_target_elm).each(function(i, elm) {
				$(elm).attr("contenteditable", "true").attr("spellcheck", "false");
				
				$(elm).off("click").attr('onclick', '').click( function (originalEvent) { 
					originalEvent.stopPropagation(); 
					
					if (originalEvent.preventDefault) originalEvent.preventDefault(); 
					else originalEvent.returnValue = false;
					
					$(elm).focus();
					
					//in case the focus caret fails...
					setTimeout(function() {
						if (typeof window.getSelection !== "undefined") {
							var selection = window.getSelection();
							var focus_elm = selection ? selection.anchorNode : null;
							
							if (focus_elm && focus_elm.nodeType == Node.TEXT_NODE)
								focus_elm = focus_elm.parentNode;
							
							if (!focus_elm || focus_elm != elm)
								return WF.jsPlumbTaskFlow.setTaskLabel(elm);
						}
					}, 500);
					
					//return WF.jsPlumbTaskFlow.setTaskLabel(elm);
				}).blur(function(originalEvent) {
					WF.jsPlumbTaskFlow.saveTaskLabel(elm, {label: $(elm).html()});
				});
			});
		  	
		  	//set contextmenu
		  	if (WF.jsPlumbContextMenu.isContextMenuActive()) {
		  		var task_type = target_elm.getAttribute("type");
		  		
		  		var show_context_menu = WF.jsPlumbProperty.isTaskSubSettingTrue(task_type, "task_menu", "show_context_menu", true);
		  		
		  		if (show_context_menu) {
			  		j_target_elm.addcontextmenu(WF.jsPlumbContextMenu.task_context_menu_id, {
			  			callback: function(target, contextmenu, originalEvent) {
			  				if (originalEvent) {
								if (originalEvent.preventDefault) originalEvent.preventDefault(); 
								else originalEvent.returnValue = false;
							}
							
							if (main_tasks_flow_obj.data("inner_task_dragging"))
								return false;
							
			  				WF.jsPlumbContextMenu.prepareTaskMenu(originalEvent, target_elm);
			  				
			  				return true;
			  			},
			  		}).click( function (originalEvent) {
			  			//open properties on single click if not dragging
			  			if (main_tasks_flow_obj.data("contextmenu_cannot_be_shown") != 1) {
				  			if (originalEvent) {
								if (originalEvent.preventDefault) originalEvent.preventDefault(); 
								else originalEvent.returnValue = false;
								
								if (originalEvent.stopPropagation) originalEvent.stopPropagation();
							}
							
				  			var o = j_target_elm.offset();
				  			originalEvent.pageX = o.left + j_target_elm.width();
							originalEvent.pageY = o.top;
				  			
				  			var ul = $("#" + WF.jsPlumbContextMenu.task_context_menu_id);
				  			MyContextMenu.updateContextMenuPosition(ul, originalEvent);
				  			
				  			//menu will show in the left side of the task
				  			if (parseInt(ul.css("left")) < originalEvent.pageX)
				  				originalEvent.pageX = o.left - ul.data('dimensions').w;
				  			
				  			//show task menu
				  			WF.jsPlumbContextMenu.showTaskMenu(originalEvent, target_elm);
				  			
				  			//add handler to be executed everytime a task gets clicked. Example: show its properties
				  			WF.jsPlumbProperty.callTaskSettingsCallback(task_type, "on_click_task", [ target_elm.id, j_target_elm ]);
				  		}
			  		});
			  		
			  	}
		  	}
			
			var is_firefox = navigator.userAgent.search('Firefox') !== -1;
			
			// initialise draggable elements.  note: jsPlumb does not do this by default from version 1.3.4 onwards.
			ExternalLibClone.draggable(ExternalLibClone.getSelector(target_elm), {
				scroll: true,
				scrollSensitivity: 20,
				
				start: function(e, obj) {
					var elm = e.target;
					var j_elm = $(elm);
					
					j_elm.data("top_css", j_elm.css("top"));
					j_elm.data("left_css", j_elm.css("left"));
					
					var inner_tasks = j_elm.find("." + WF.jsPlumbTaskFlow.task_class_name);
					j_elm.data("inner_tasks", inner_tasks);
					
					main_tasks_flow_obj.addClass("dragging_task");
					main_tasks_flow_obj.data("inner_task_dragging", 1);
					main_tasks_flow_obj.data("contextmenu_cannot_be_shown", 1);
					
					var parent_task = WF.jsPlumbTaskFlow.getTaskParentTasks(j_elm).first();
					j_elm.data("parent_task", null);
					
					if (parent_task.is("." + WF.jsPlumbTaskFlow.task_class_name))
						j_elm.data("parent_task", parent_task);
					
					obj.position.left = 0;
					obj.position.top = 0;
				},
				drag: function(e, obj) {
					var j_elm = $(e.target);
					// jQuery will simply use the same object we alter here
					var change_left = obj.position.left - obj.originalPosition.left; // find change in left.
					var new_left = obj.originalPosition.left + change_left;
					
					var change_top = obj.position.top - obj.originalPosition.top; // find change in top.
					var new_top = obj.originalPosition.top + change_top; 
					
					//prepare margin-top according with scroll, but only if the task is NOT inside of another droppable. If is inside of another droppable that is not the main_tasks_flow_obj, then we should not add the scroll top.
					if (!j_elm.data("parent_task")) {
						if (!is_firefox) { //only if not firefox
							//prepare margins according with scroll
							var scroll_top = main_tasks_flow_obj.scrollTop();
							var scroll_left = main_tasks_flow_obj.scrollLeft();
							
							j_elm.css("margin-top", scroll_top + "px");
							j_elm.css("margin-left", scroll_left + "px");
						}
						else { //if firefox
							//prepare scroll offsets for firefox.
							var current_zoom = WF.jsPlumbTaskFlow.getCurrentZoom();
							
							if (current_zoom < 1) {
								//JPLPINTO 2023-02-23: Note that this is not working very well, but I could NOT do better.
								new_left -= parseInt(main_tasks_flow_obj.scrollLeft() * current_zoom);
								new_top -= parseInt(main_tasks_flow_obj.scrollTop() * current_zoom);
							}
						}
					}
					//console.log(obj.position.top+"|"+new_top);
					//console.log(obj.helper.offset().top / current_zoom);
					
					new_left = new_left > 0 ? new_left : 0;
					new_top = new_top > 0 ? new_top : 0;
					
					obj.position.left = new_left;
					obj.position.top = new_top;
					
					//repaint all connections for inner tasks if exists
					var inner_tasks = j_elm.data("inner_tasks");
					
					if (inner_tasks && inner_tasks.length > 0)
						inner_tasks.each(function(idx, child) {
							// this is very important, otherwise the attached conections will not be revert to their original position.
							WF.jsPlumbTaskFlow.repaintTask( $(child) );
						});
				},
				stop: function(e, obj) {
					var elm = e.target;
					var j_elm = $(elm);
					var task_type = elm.getAttribute("type");
					var parent_task = j_elm.data("parent_task");
					var inner_tasks = j_elm.data("inner_tasks");
					j_elm.data("inner_tasks", null); //clean memory
					
					var start_scroll_top = parseInt(j_elm.css("margin-top"));
					var start_scroll_left = parseInt(j_elm.css("margin-left"));
					
					j_elm.css("margin-top", "");
					j_elm.css("margin-left", "");
					
					//convert offsets and positions according with zoom
					var current_zoom = WF.jsPlumbTaskFlow.getCurrentZoom();
					obj.position.left = parseInt(obj.position.left * current_zoom); 
					obj.position.top = parseInt(obj.position.top * current_zoom);
					
					//prepare offset with current_zoom
					var task_offset = assignObjectRecursively({}, obj.offset);
					task_offset.top = parseInt(task_offset.top * current_zoom);
					task_offset.left = parseInt(task_offset.left * current_zoom);
					
					var status = WF.jsPlumbProperty.callTaskSettingsCallback(task_type, "on_task_drag_stop_validation", [ j_elm ]);
					
					//only check if inside of containers if task is inside of main_tasks_flow_obj. If inner task, doesn't check bc it doesn't make sense. Only main task should have this check.
					if (!status || (!parent_task && !WF.jsPlumbContextMenu.checkIfTaskCanBeCreated(task_type, elm, obj.offset))) {
						WF.jsPlumbStatusMessage.showError("You cannot drop this Task on that position! Please try again...");
						
						j_elm.animate({
							top: j_elm.data("top_css"), 
							left: j_elm.data("left_css")
						},
						{
							duration: 100,
							complete: function() {
								// this is very important, otherwise the attached conections will not be revert to their original position.
								WF.jsPlumbTaskFlow.repaintTask( $(this) );
							}
						});
					}
					else { //check if exists droppable and if so, append element to it
						var droppable = j_elm.data("droppable");
						var parent = j_elm.parent();
						var droppable_connection = null;
						
						if (droppable) {
							j_elm.data("droppable", null);
							droppable = $(droppable);
							main_tasks_flow_obj.find(".task_droppable_over").removeClass("task_droppable_over"); //remove droppable over class from droppable element and from the parents of the droppable element
							
							//preparing droppable if is "connection add icon"
							if (droppable.hasClass("connector_overlay_add_icon")) {
								var add_icon = droppable;
								droppable = main_tasks_flow_obj;
								
								droppable_connection = WF.jsPlumbTaskFlow.getOverlayConnectionId(add_icon); //TODO CHEK THIS
								
								if (droppable_connection)
									droppable = $(droppable_connection.source).parent();
							}
							
							//if droppable is not the same parent, otherwise doesn't do anything bc is not necessary
							if (!parent.is(droppable[0])) {
								droppable.append(j_elm);
								
								//if droppable is main_tasks_flow_obj
								if (droppable.is(main_tasks_flow_obj)) {
									var offset = WF.jsPlumbTaskFlow.getNewElmPosition(main_tasks_flow_obj, parent, obj.position);
									offset.left += parseInt(main_tasks_flow_obj.scrollLeft());
									offset.top += parseInt(main_tasks_flow_obj.scrollTop());
									//console.log(offset);
									
									j_elm.css(offset);
								}
								//if droppable is a task or inside of a task
								else {
									var droppable_o = droppable.offset();
									var t = task_offset.top - droppable_o.top;
									var l = task_offset.left - droppable_o.left;
									t = t > 20 ? t : 20;
									l = l > 10 ? l : 10;
									
									var offset = {top: t, left: l};
									//console.log(offset);
									
									j_elm.css(offset);
									
									//resize droppable task and its' parents with the new width and height according with the new dropped inner task
									WF.jsPlumbTaskFlow.resizeTaskParentTask(droppable);
									
									// this is very important, otherwise the attached conections will not be revert to their original position.
									WF.jsPlumbTaskFlow.repaintTask( $(this) );
								}
							}
							else if (obj.position.top < 20 || obj.position.left < 10) { //if same parent check if position is negative, and if so, doesn't allow it.
								if (obj.position.top < 20)
									obj.position.top = 20;
								
								if (obj.position.left < 10)
									obj.position.left = 10;
								
								j_elm.css(obj.position);
							}
						}
						else if (!parent.is(main_tasks_flow_obj)) { //only if the parent of elm is not main_tasks_flow_obj, we will add the task to main_tasks_flow_obj
							main_tasks_flow_obj.append(j_elm);
							
							var offset = WF.jsPlumbTaskFlow.getNewElmPosition(main_tasks_flow_obj, parent, obj.position);
							offset.left += parseInt(main_tasks_flow_obj.scrollLeft());
							offset.top += parseInt(main_tasks_flow_obj.scrollTop());
							//console.log(offset);
							
							j_elm.css(offset);
						}
						else if (obj.position.top < 20 || obj.position.left < 10) { //if parent is main_tasks_flow_obj check if position is negative, and if so, doesn't allow it.
							if (obj.position.top < 20)
								obj.position.top = 20;
							
							if (obj.position.left < 10)
								obj.position.left = 10;
							
							j_elm.css(obj.position);
						}
						else {
							var offset = WF.jsPlumbTaskFlow.getNewElmPosition(main_tasks_flow_obj, parent, obj.position);
							//Do not use the main_tasks_flow_obj.scrollLeft() bc it doesn't work fine
							//offset.left += parseInt(main_tasks_flow_obj.scrollLeft()) + start_scroll_left;
							//offset.top += parseInt(main_tasks_flow_obj.scrollTop()) + start_scroll_top;
							offset.left += start_scroll_left;
							offset.top += start_scroll_top;
							
							j_elm.css(offset);
						}
						
						//Preparing task connections if droppable is already an existent connection
						if (droppable_connection)
							WF.jsPlumbContextMenu.setTaskBetweenConnection(j_elm, droppable_connection);
						
						//repaint all connections for inner tasks if exists
						if (inner_tasks && inner_tasks.length > 0) {
							WF.jsPlumbTaskFlow.resizeTaskBasedOnInnerTasks(j_elm);
							
							inner_tasks.each(function(idx, child) {
								// this is very important, otherwise the attached conections will not be revert to their original position.
								WF.jsPlumbTaskFlow.repaintTask( $(child) );
							});
						}
						
						//2019-10-28: only execute this here, inside of the else, otherwise the parent task will resize before the task be in the previous place and the parent task will be resize to a bigger size than before, and it will look here. Do not remove the resizeTaskParentTask from here!
						if (parent_task)
							WF.jsPlumbTaskFlow.resizeTaskParentTask(j_elm); //resize parent task with the new width and height according with this inner task
					}
					
					main_tasks_flow_obj.data("inner_task_dragging", null); //reset dragging
					main_tasks_flow_obj.removeClass("dragging_task");
					
					WF.jsPlumbProperty.callTaskSettingsCallback(task_type, "on_task_drag_stop_end", [ j_elm ]);
					
					if (current_zoom != 1)
						ExternalLibClone.repaintEverything(); //very important otherwise the connections will be messy
					
					setTimeout(function() {
						main_tasks_flow_obj.data("contextmenu_cannot_be_shown", null);
					}, 10);
				}
			});
			
			var target_options = {
				dropOptions:{ 
					hoverClass:"dragHover", 
					activeClass:"dragActive",
					greedy: true, //greedy is very important to be true, otherwise if the target is a task inside of another task (this is, is a inner task), the source task will be connected to this task and to its' parents too. With greedy=true this doesn't happen and the source task is only connected to this target task.
				},
				paintStyle:{ 
					//strokeStyle:color, 
					fillStyle:color 
				},
				anchor:"Continuous",
				//anchor:"LeftMiddle",
				container: main_tasks_flow_obj, //This container is very important be specified to the main_tasks_flow_obj, bc if there are inner droppable elements, all the sources and targets end-points must be appended to the main_tasks_flow_obj. Otherwise the connection SVG will appear messy and with a wrong position.
		  	};
		  	
		  	if (WF.jsPlumbTaskFlow.default_connection_hover_color)
		  		target_options["hoverPaintStyle"] = { 
					//strokeStyle: WF.jsPlumbTaskFlow.default_connection_hover_color,
					fillStyle: WF.jsPlumbTaskFlow.default_connection_hover_color,
				};
		  	
		  	ExternalLibClone.makeTarget(ExternalLibClone.getSelector(target_elm), target_options);
		  	
		  	return j_target_elm.attr("id");
		},
	
		addSourceElement : function(source_elm, parent_elm, connection_exit_props) {
			// hand off to the library specific demo code here.  not my ideal, but to write common code
		    	// is less helpful for everyone, because all developers just like to copy stuff, right?
		    	// make each ".ep" div a source and give it some parameters to work with.  here we tell it
			// to use a Continuous anchor and the StateMachine connectors, and also we give it the
			// connector's paint style.  note that in this demo the strokeStyle is dynamically generated,
			// which prevents us from just setting a ExternalLibClone.Defaults.PaintStyle.  but that is what i
			// would recommend you do.
		
			//console.log(parent_elm);
			//console.log(source_elm.outerHTML);
			//console.log(source_elm);
			//console.log(connection_exit_props);
		
			var j_source_elm = $(source_elm);
		
			var connection_exit_id = connection_exit_props ? connection_exit_props.id : null;
			var color = connection_exit_props ? connection_exit_props.color : null;
			var on_source_connection_color = connection_exit_props ? connection_exit_props.on_source_connection_color : null;
			var connection_exit_type = connection_exit_props ? connection_exit_props.type : null;
			var connection_exit_overlay = connection_exit_props ? connection_exit_props.overlay : null;
			var connection_exit_label = connection_exit_props ? connection_exit_props.label : null;
			
			if (!color) {
				color = j_source_elm.css("backgroundColor");
				
				if (!color)
					color = nextColor();
			}
			
			on_source_connection_color = on_source_connection_color ? on_source_connection_color : this.default_on_source_connection_color;
			
			var new_connector = this.getNewConnectionConnector(connection_exit_type);
			connection_exit_type = new_connector[0]; //confirms if connection_exit_type is available and correct
			
			connection_exit_overlay = this.getConnectionOverlaysType(connection_exit_overlay);
			var overlays = this.getNewConnectionOverlays(connection_exit_overlay);
			//console.log(overlays);
			
			if ($.isArray(overlays))
				overlays.push(["Arrow", {id: "endpoint_arrow_forward", location: 1, position: "end", foldback: 0.8, length: 5, width: 6}]);
			
			j_source_elm.css({"border-color":color, "background-color":color});
			
			var source_options = {
				parent:parent_elm,
				paintStyle: { 
					//strokeStyle: color, 
					//fillStyle: on_source_connection_color, 
				},
				anchor: "Continuous",
				//anchor: "RightCenter",
				connectorStyle: { strokeStyle: on_source_connection_color, lineWidth: this.connection_line_width },
				connectorOverlays: overlays,
				container: $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id), //This container is very important be specified to the main_tasks_flow_obj, bc if there are inner droppable elements, all the sources and targets end-points must be appended to the main_tasks_flow_obj. Otherwise the connection SVG will appear messy and with a wrong position.
				
				//Note: do not add the 'connector: new_connector' bc if a task is resizable and the connector is different than StateMachine, The JsPlumb will give an error. The connector needs to be change when we create a connection.
				
				/*dragOptions: {
					start: function(e, obj) {
						var current_zoom = WF.jsPlumbTaskFlow.getCurrentZoom();
						
						if (current_zoom != 1) {
							//TODO: fix dragged connection positioning, bc is appearing in wrong place if zoom exists
						}
					},
					drag: function(e, obj) {
						//convert positions according with zoom
						var current_zoom = WF.jsPlumbTaskFlow.getCurrentZoom();
						
						if (current_zoom != 1) {
							//TODO: fix dragged connection positioning, bc is appearing in wrong place if zoom exists
						}
					},
				}*/
			};
		  	
		  	if (WF.jsPlumbTaskFlow.default_connection_hover_color)
		  		source_options["hoverPaintStyle"] = { 
					//strokeStyle: WF.jsPlumbTaskFlow.default_connection_hover_color,
					fillStyle: WF.jsPlumbTaskFlow.default_connection_hover_color,
				};
		  	
		  	ExternalLibClone.makeSource(j_source_elm, source_options);
		  	
		  	var target_id = $(parent_elm).attr("id");
			
			this.centerTaskInnerElements(target_id);
			
			j_source_elm.attr("target_id", target_id);
			j_source_elm.attr("connection_exit_id", connection_exit_id);
			j_source_elm.attr("connection_exit_color", color);
			j_source_elm.attr("connection_exit_type", connection_exit_type);
			j_source_elm.attr("connection_exit_overlay", connection_exit_overlay);
			j_source_elm.attr("connection_exit_label", connection_exit_label);
			
			/*j_source_elm.bind("mouseenter", function(endpoint) {
				$("#" + endpoint.target.offsetParent.id).attr("connection_exit_id", connection_exit_id);
				$("#" + endpoint.target.offsetParent.id).attr("connection_exit_color", color);
				$("#" + endpoint.target.offsetParent.id).attr("connection_exit_type", connection_exit_type);
				$("#" + endpoint.target.offsetParent.id).attr("connection_exit_overlay", connection_exit_overlay);
				$("#" + endpoint.target.offsetParent.id).attr("connection_exit_label", connection_exit_label);
			
				$("#" + endpoint.target.offsetParent.id).attr("selected_endpoint_id", endpoint.target.id);
			});*/
			
			return j_source_elm.attr("id");
		},
	
		addExtraSourceElementToTask : function(task_id, connection_exit_props) {
			var task = this.getTaskById(task_id)[0];
		
			if (task) {
				var j_task = $(task);
				var eps = j_task.children("." + this.task_eps_class_name)[0];
			
				if (eps) {
					var j_eps = $(eps);
				
					var exit_html = WF.jsPlumbContextMenu.task_exit_html;
					exit_html = exit_html.replace(/#task_ep_class_name#/g, this.task_ep_class_name);
				
					j_eps.append(exit_html);
				
					var ep = j_eps.find("." + this.task_ep_class_name).last()[0];
					if (ep) {
						task = this.getTaskById(task_id);//just in case.
						var source_id = this.addSourceElement(ep, task, connection_exit_props);
					
						this.centerTaskInnerElements(task_id);
					
						return source_id;
					}
				}
			}
		
			return null;
		},
	
		connect : function(source_task_id, target_task_id, connection_label, connector_type, connection_overlay, connection_exit_props) {
			if (!connection_exit_props || !connection_exit_props.hasOwnProperty("id") || !connection_exit_props.id || !connection_exit_props.color) {
				WF.jsPlumbStatusMessage.showError("ERROR in connecting tasks. Connector exit ID or COLOR is undefined!");
				
				//console.log(source_task_id);
				//console.log(target_task_id);
				//console.log(connection_label);
				//console.log(connector_type);
				//console.log(connection_overlay);
				//console.log(connection_exit_props);
				
				return false;
			}
			
			connection_label = connection_label ? connection_label : this.default_label;
			connector_type = connector_type ? connector_type : this.default_connection_connector;
			connection_overlay = connection_overlay ? connection_overlay : this.default_connection_overlay;
			
			try { 
				var c = ExternalLibClone.connect({
					source:source_task_id,
					target:target_task_id,
					parameters:{
						connection_exit_id: connection_exit_props.id,
						connection_exit_color: connection_exit_props.color,
						connection_exit_type: connector_type,
						connection_exit_overlay: connection_overlay,
					},
				});
				
				c.getOverlay("label").setLabel(connection_label);
			}
			catch (e) {
				alert(e && e.message ? e.message : e);
				
				if (console && typeof console.log == "function")
					console.log(e);
			}
			
			//Note: Do not need to call the this.setNewConnectionConnector(c) or this.changeConnectionConnectorType(c, connector_type), bc the connection will be updated with the correspondent connector_type in the jsPlumbConnection handler
			
			return c;
		},
	
		centerTaskInnerElements : function(task_id) {
			var j_task = this.getTaskById(task_id);
			var task = j_task[0];
		
			if (task) {
				var task_type = j_task.attr("type");
				
				var status = WF.jsPlumbProperty.isTaskSettingTrue(task_type, "center_inner_elements", true);
				
				if (status) {
					var label = j_task.children("." + this.task_label_class_name)[0];
					var eps = j_task.children("." + this.task_eps_class_name)[0];
			
					if (label && eps) {
						label = $(label);
						eps = $(eps);
				
						var label_width;
				
						if (eps.find("." + this.task_ep_class_name).length == 0) {
							eps.hide();
							label_width = parseInt(j_task.css('min-width'));
						}
						else {
							eps.show();
							label_width = parseInt(j_task.css('min-width')); - eps.width()
						}
				
						var text_width = $(label).find("span").first().width();
						if (text_width > label_width - 10) {
							label_width = text_width + 10;
						}
				
						label.css("width", label_width + "px");
						
						eps.css("margin-top", "0px");
						label.css("margin-top", "0px");
						
						var margin_top = Math.floor( (j_task.height() - eps.height()) / 2 );
						margin_top = margin_top > 0 ? margin_top : 0;
						eps.css("margin-top", margin_top + "px");
				
						margin_top = Math.floor( (j_task.height() - label.height()) / 2 );
						margin_top = margin_top > 0 ? margin_top : 0;
						label.css("margin-top", margin_top + "px");
					}
				}
				
				this.repaintTask(j_task);
			}
		},
		
		repaintTaskByTaskId : function(task_id) {
			this.repaintTask( this.getTaskById(task_id) );
		},
		
		repaintTask : function(j_task, position) {
			try {
				ExternalLibClone.repaint(j_task, $.isPlainObject(position) ? position : null);
			}
			catch(e) {
				//console.log(e);
			}
		},
		
		repaintAllTasks : function() {
			$("#" + this.main_tasks_flow_obj_id + " ." + this.task_class_name).each(function(idx, elm){
				WF.jsPlumbTaskFlow.repaintTask( $(elm) );
			});
		},
		/* END: TARGET AND SOURCE METHODS */
	
		/* START: CONNECTION METHODS */
		/*** START: CONNECTION-OBJ METHODS ***/
		getConnection : function(connection_id) {
			var connection = null;
		
			if (connection_id) {
				var connections = ExternalLibClone.getConnections();
				for (var i = 0; i < connections.length; i++) {
					if (connections[i].id == connection_id) {
						connection = connections[i];
						break;
					}
				}
			}
		
			return connection;
		},
	
		getConnections : function() {
			return ExternalLibClone.getConnections();
		},
		
		getSourceConnections : function(task_id) {
			var connections = ExternalLibClone.getConnections();
		
			var task_connections = new Array();
		
			for (var i = 0; i < connections.length; i++) {
				var connection = connections[i];
			
				if ($(connection.source).attr('id') == task_id)
					task_connections.push(connection);
			}
			return task_connections;
		},
	
		getTargetConnections : function(task_id) {
			var connections = ExternalLibClone.getConnections();
		
			var task_connections = new Array();
		
			for (var i = 0; i < connections.length; i++) {
				var connection = connections[i];
			
				if ($(connection.target).attr('id') == task_id)
					task_connections.push(connection);
			}
			return task_connections;
		},
	
		deleteConnection : function(connection_id, do_not_confirm) {
			var connection = this.getConnection(connection_id);
		
			if (connection) {
				var source_label = this.getTaskLabelByTaskId(connection.sourceId);
				var target_label = this.getTaskLabelByTaskId(connection.targetId);
			
				if (do_not_confirm || confirm("Do you really want to delete the connection between the tasks: " + source_label + " and " + target_label + "?")) {
					//hide connection properties popup if opened
					if (WF.jsPlumbProperty.isSelectedConnectionPropertiesWithConnectionId(connection_id)) {
						var selected_connection_properties = $("#" + this.selected_connection_properties_id);
						var tasks_menu_hide = $("#" + WF.jsPlumbContextMenu.main_tasks_menu_hide_obj_id);
						var parent = tasks_menu_hide.parent().closest(".taskflowchart");
						
						//if properties are fixed and hidden, do not do anything, otherwise it will mess the layout
						if (parent.is(".fixed_side_properties") && selected_connection_properties.is(".tasks_menu_hidden"))
							WF.jsPlumbContextMenu.toggleTasksMenuPanel();
						
						if (WF.jsPlumbProperty.isSelectedConnectionPropertiesOpen())
							WF.jsPlumbProperty.hideSelectedConnectionProperties();
					}
					
					//delete connection
					var endpoints = connection.endpoints;
					endpoints[0].detachFrom(endpoints[1]);
					
					ExternalLibClone.deleteEndpoint(endpoints[0]);
					ExternalLibClone.deleteEndpoint(endpoints[1]);
					
					var source_task_type = $("#" + connection.sourceId).attr("type");
					var target_task_type = $("#" + connection.targetId).attr("type");
					
					var status = WF.jsPlumbProperty.callTaskSettingsCallback(source_task_type, "on_success_connection_deletion", [ connection ]);
					
					if (target_task_type != source_task_type && !WF.jsPlumbProperty.callTaskSettingsCallback(target_task_type, "on_success_connection_deletion", [ connection ])) {
						status = false;
					}
					
					WF.jsPlumbTaskFlow.connections_properties[connection_id] = null;
					
					return status;
				}
			}
			return false;
		},
		
		flipConnection : function(connection_id) {
			var connection = this.getConnection(connection_id);
		
			if (connection) {
				//get connection settings
				var connection_exit_id = connection.getParameter("connection_exit_id");
				var connection_exit_type = connection.getParameter("connection_exit_type");
				var connection_exit_overlay = connection.getParameter("connection_exit_overlay");
				var connection_exit_color = connection.getParameter("connection_exit_color");
				var connection_label = connection.getOverlay("label").getLabel();
				
				//get target connection settings if exists and overwrite the current connection
				var target_task = connection.endpoints[1].element[0];
				var target_ep = $(target_task).find(this.source_selector + "[connection_exit_id=" + connection_exit_id + "]");
				
				if (target_ep[0]) {
					var target_connection_exit_type = target_ep.attr("connection_exit_type");
					var target_connection_exit_overlay = target_ep.attr("connection_exit_overlay");
					var target_connection_exit_color = target_ep.attr("connection_exit_color");
					
					if (target_connection_exit_type)
						connection_exit_type = target_connection_exit_type;
					
					if (target_connection_exit_overlay)
						connection_exit_overlay = target_connection_exit_overlay;
					
					if (target_connection_exit_color)
						connection_exit_color = target_connection_exit_color;
				}
				
				//create new connection
				var connection_exit_props = {
					id : connection_exit_id,
					color : connection_exit_color,
				};
				var c = this.connect(connection.targetId, connection.sourceId, connection_label, connection_exit_type, connection_exit_overlay, connection_exit_props);
				
				if (c) {
					//add properties to new connection
					var connection_properties = WF.jsPlumbTaskFlow.connections_properties[connection_id];
					WF.jsPlumbTaskFlow.connections_properties[c.id] = connection_properties;
					
					//delete old connection
					WF.jsPlumbTaskFlow.connections_properties[connection_id] = null;
					delete WF.jsPlumbTaskFlow.connections_properties[connection_id];
					
					this.deleteConnection(connection_id, true);
					
					//return new connection
					return c;
				}
			}
			
			return false;
		},
	
		printConnection : function(event_name, conn) {
			alert(event_name + ":\n" +
				"connection:" + conn.connection + "\n" + 
				"sourceId:" + conn.sourceId + "\n" +
				"targetId:" + conn.targetId + "\n" +
				"sourceType:" + $(conn.source).attr("type") + "\n" +
				"targetType:" + $(conn.target).attr("type") + "\n" +
				"source:" + conn.source + "\n" +
				"target:" + conn.target + "\n" +
				"sourceEndpoint:" + conn.sourceEndpoint + "\n" +
				"targetEndpoint:" + conn.targetEndpoint + "\n"
		  	);
		},
		
		//check if connection is allowed. Example if parent task is a "function" type, doesn't allow outside connections.
	  	isConnectionAllowed : function(source_task_id, target_task_id) {
			var source_task = WF.jsPlumbTaskFlow.getTaskById(source_task_id);
	  		var target_task = WF.jsPlumbTaskFlow.getTaskById(target_task_id);
	  		
			//get closest source parent which does not allow outside connections
			var source_parent_tasks = WF.jsPlumbTaskFlow.getTaskParentTasks(source_task);
			var source_parent_not_allowed = null;
  			
  			for (var i = 0; i < source_parent_tasks.length; i++) {
  				var source_parent_task = source_parent_tasks[i];
  				
  				if (!WF.jsPlumbProperty.isTaskSettingTrue(source_parent_task.getAttribute("type"), "allow_inner_tasks_outside_connections", true)) {
  					source_parent_not_allowed = source_parent_task;
  					break;
  				}
  			}
			
  			//get closest target parent which does not allow outside connections
			var target_parent_tasks = WF.jsPlumbTaskFlow.getTaskParentTasks(target_task);
  			var target_parent_not_allowed = null;
  				
  			for (var i = 0; i < target_parent_tasks.length; i++) {
  				var target_parent_task = target_parent_tasks[i];
  				
  				if (!WF.jsPlumbProperty.isTaskSettingTrue(target_parent_task.getAttribute("type"), "allow_inner_tasks_outside_connections", true)) {
  					target_parent_not_allowed = target_parent_task;
  					break;
  				}
  			}
  			
  			/*
  			console.log(source_task_id);
  			console.log(source_task[0]);
  			console.log(source_parent_tasks[0]);
  			console.log(source_parent_not_allowed);
  			
  			console.log(target_task_id);
  			console.log(target_task[0]);
  			console.log(target_parent_tasks[0]);
  			console.log(target_parent_not_allowed);
  			*/
  			
  			//checks parents that don't allow outside connections if they are inside of each-other.
  			if (source_parent_not_allowed && target_parent_not_allowed) {
  				//checks if source_parent_not_allowed is the same than target_parent_not_allowed
  				if (!$(source_parent_not_allowed).is(target_parent_not_allowed))
  					return false;
  			}
  			else if (source_parent_not_allowed) {
  				//checks if source_parent_not_allowed is a parent of target_task too, otherwise returns false
  				if (target_parent_tasks.filter(source_parent_not_allowed).length == 0)
  					return false;
  			}
  			else if (target_parent_not_allowed) {
  				//checks if target_parent_not_allowed is a parent of source_task too, otherwise returns false
  				if (source_parent_tasks.filter(target_parent_not_allowed).length == 0)
  					return false;
  			}
  			
	  		return true;
		},
		/*** END: CONNECTION-OBJ METHODS ***/
	
		/*** START: CONNECTION-ID METHODS ***/
		getEventTargetConnectionId : function(target_obj) {
			/*
			in case of the endpoints:
				HTMLDivElement
					SVGSVGElement
						SVGCircleElement
			or 
			in case of the line connector:
				SVGSVGElement
					SVGPathElement
			or
			in case of the label:
				HTMLDivElement
			*/
		
			var connection_id = null;
			if (target_obj.hasAttribute("connection_id")) {
				connection_id = target_obj.getAttribute("connection_id");
			}
			else {
				var p = target_obj.parentNode;
			
				if (p) {
					if (p.hasAttribute("connection_id")) {
						connection_id = p.getAttribute("connection_id");
					}
					else {
						var pp = p.parentNode;
				
						if (pp && pp.hasAttribute("connection_id")) {
							connection_id = pp.getAttribute("connection_id");
						}
					}
				}
			}
		
			return connection_id;
		},
		
		getOverlayConnectionId : function(overlay_elm) {
			overlay_elm = $(overlay_elm);
			
			var connection_id = overlay_elm.attr("connection_id");
			var p = overlay_elm.parent();
			var is_parent_overlay = p.is(".leader-line-overlay");
			
			if (!connection_id && is_parent_overlay)
				connection_id = p.attr("connection_id");
			
			if (connection_id) {
				var connection = this.getConnection(connection_id);
				
				if (connection)
					return connection;
			}
			
			var connections = ExternalLibClone.getConnections();
			
			for (var i = 0; i < connections.length; i++) {
				var connection = connections[i];
				var overlays = connection.getOverlays();
				
				if (overlays)
					for (var j = 0; j < overlays.length; j++) {
						var connection_overlay = overlays[j].canvas;
						
						//if (connection_overlay && connection_overlay.getAttribute("id") == overlay_id)
						if ( overlay_elm.is(connection_overlay) || (is_parent_overlay && p.is(connection_overlay)) ) //in linear-line the canvas is the parent
							return connection;
					}
			}
			
			return null;
		},
		/*** END: CONNECTION-ID METHODS ***/
	
		/*** START: CONNECTION-LABEL METHODS ***/
		saveConnectionLabelByOverlay : function(label_overlay, label_obj) {
			var connection_id = label_overlay.component.id;
			var task_type = $("#" + label_overlay.component.sourceId).attr("type");
			
			WF.jsPlumbProperty.callTaskSettingsCallback(task_type, "on_submit_connection_label", [ label_obj, connection_id ]) ? false : true;
			
			label_obj.label = label_obj.label == "" ? this.default_label : label_obj.label;
			label_overlay.setLabel(label_obj.label);
		
			WF.jsPlumbProperty.callTaskSettingsCallback(task_type, "on_complete_connection_label", [ label_overlay, connection_id ]);
		},
		
		setConnectionLabelByOverlay : function(label_overlay) {
			var default_label = label_overlay.getLabel();
			default_label = default_label == this.default_label ? "" : default_label;
		
			var connection_id = label_overlay.component.id;
			var task_type = $("#" + label_overlay.component.sourceId).attr("type");
		
			var label_obj = this.getNewLabel(default_label, connection_id, task_type, "on_check_connection_label");
		  	
			if (label_obj.label != null)
				this.saveConnectionLabelByOverlay(label_overlay, label_obj);
			else
				WF.jsPlumbProperty.callTaskSettingsCallback(task_type, "on_cancel_connection_label", [ connection_id ]);
			
			return false;
		},
	
		setConnectionLabelByConnectionId : function(connection_id) {
			var connection = this.getConnection(connection_id);
		
			if (connection) {
				this.setConnectionLabelByOverlay(connection.getOverlay("label"));
			}
			return false;
		},
		/*** END: CONNECTION-LABEL METHODS ***/
	
		/*** START: CONNECTION-CONNECTORS METHODS ***/
		changeConnectionColor : function(connection, color) {
			var line_width = connection.getPaintStyle().lineWidth;
		  	
		  	connection.setPaintStyle({strokeStyle:color, lineWidth:line_width});
		 	connection.endpoints[0].setPaintStyle({fillStyle:color});
		  	connection.endpoints[1].setPaintStyle({fillStyle:color});
		  	
		  	$(connection.getOverlay("label").canvas).css("color", color);
		},
		
		getConnectionOverlaysType : function (overlay_type) {
			if (overlay_type && jQuery.inArray(overlay_type, this.available_connection_overlays_type) != -1) {
				return overlay_type;
			}
		
			overlay_type = this.default_connection_overlay;
		
			if (!overlay_type || jQuery.inArray(overlay_type, this.available_connection_overlays_type) == -1) {
				overlay_type = this.available_connection_overlays_type[0];
			}
			
			return overlay_type;
		},
		
		getNewConnectionOverlays : function (overlay_type) {
			overlay_type = this.getConnectionOverlaysType(overlay_type);
			
			var overlays = [];
			var available_overlays = this.available_connection_overlays;
		
			if (overlay_type) {
				switch(overlay_type) {
					case "Forward Arrow": overlays = [ available_overlays[0] ]; break;
					case "Backward Arrow": overlays = [ available_overlays[1] ]; break;
					case "Both Arrows": overlays = [ available_overlays[0], available_overlays[1] ]; break;
					case "No Arrows": overlays = []; break;
					case "No Arrows With Directional Arrow": overlays = [ available_overlays[10] ]; break;
					case "One To Many": overlays = [ available_overlays[2], available_overlays[7], available_overlays[8], available_overlays[10] ]; break;
					case "Many To One": overlays = [ available_overlays[3], available_overlays[6], available_overlays[9], available_overlays[10] ]; break;
					case "One To One": overlays = [ available_overlays[6], available_overlays[8], available_overlays[10] ]; break;
					case "Many To Many": overlays = [ available_overlays[2], available_overlays[3], available_overlays[7], available_overlays[9], available_overlays[10] ]; break;
				}
			}
		
			return overlays;
		},
	
		prepareConnectionOverlays : function(connection, overlay_type) {
			//console.log(overlay_type);
		
			var overlays = this.getNewConnectionOverlays(overlay_type);
			
			var connection_overlays = connection.getOverlays();
			//console.log(connection_overlays);
			//console.log(overlays);
		
			var label = connection.getOverlay("label").getLabel();
		
			connection.removeAllOverlays();
		
			for (var i = 0; i < overlays.length; i++)
				connection.addOverlay(overlays[i]);
		
			connection.addOverlay(this.connection_label_overlay);
			
			if (WF.jsPlumbWorkFlowObjOptions && WF.jsPlumbWorkFlowObjOptions.hasOwnProperty("is_droppable_connection") && WF.jsPlumbWorkFlowObjOptions.is_droppable_connection) {
				connection.addOverlay(this.connection_add_icon_overlay);
				
				//Setting droppable connection.
				var add_icon_overlay = connection.getOverlay("addicon");
				
				if (add_icon_overlay) {
					var canvas = $(add_icon_overlay.canvas);
					
					if (!canvas.hasClass("ui-droppable")) {
						var opts = {
							accept: "." + WF.jsPlumbTaskFlow.task_class_name,
							greedy: true,
							tolerance: "pointer",
							drop: function(e, obj) {
								obj.helper.data("droppable", canvas[0]);
							},
							over: function(e, obj) {
								canvas.addClass("task_droppable_over");
							},
							out: function(e, obj) {
								canvas.removeClass("task_droppable_over");
							}
						};
						canvas.droppable(opts);
					}
				}
			}
			
			if (label)
				connection.getOverlay("label").setLabel(label);
			
			//console.log(connection.getOverlays());
			
			return true;
		},
		
		changeConnectionOverlayType : function(connection, overlay_type) {
			connection.setParameter("connection_exit_overlay", overlay_type);
	
			return this.prepareConnectionOverlays(connection, overlay_type);
		},
		
		getNextConnectionConnectorType : function(selected_connector_type) {
			var types = this.available_connection_connectors_type;
		
			var idx = 0;
			if (selected_connector_type) {
				idx = jQuery.inArray(selected_connector_type, types);
				idx = idx != -1 && idx < types.length - 1 ? idx + 1 : 0;
			}
		
			return types[idx];
		},
	
		getNewConnectionConnector : function(selected_connector_type) {
			var connector = null;
		
			if (selected_connector_type) {
				connector = this.getConnectionConnector(selected_connector_type);
		
				if (!connector) {
					selected_connector_type = this.getNextConnectionConnectorType(selected_connector_type);
					connector = this.getConnectionConnector(selected_connector_type);
				}
			}
		
			if (!connector) {
				if (this.default_connection_connector) {
					selected_connector_type = this.default_connection_connector;
					connector = this.getConnectionConnector(this.default_connection_connector);
				}
		
				if (!connector) {
					connector = this.available_connection_connectors[0];
					selected_connector_type = connector[0];
				}
			}
			
			return connector;
		},
	
		setNewConnectionConnector : function(connection) {
			var is_inner_connection = connection.sourceId == connection.targetId;
		
			//Note: StateMachine connector is the only connector that works in all browsers for the inner connections.
			var connection_exit_type = is_inner_connection ? "StateMachine" : connection.getParameter("connection_exit_type");
			var new_connector = this.getNewConnectionConnector(connection_exit_type);
		
			if (new_connector) {
				if (!is_inner_connection) {
					connection_exit_type = new_connector[0];
					connection.setParameter("connection_exit_type", connection_exit_type);
				}
			
				//console.log(new_connector[0]);
				connection.setConnector(new_connector, true);
			
				var connection_exit_overlay = connection.getParameter("connection_exit_overlay");
				this.changeConnectionOverlayType(connection, connection_exit_overlay);
				
				if (connection.canvas && connection.canvas.setAttribute)
			  		connection.canvas.setAttribute("connection_id", connection.id);
			  	else //in case of IE
			  		connection.connector.canvas.setAttribute("connection_id", connection.id);
			  	
			 	connection.getOverlay("label").getElement().setAttribute("connection_id", connection.id);
			  	
			  	connection.repaint();
			  	
			  	var color = connection.getPaintStyle().strokeStyle;
			  	this.changeConnectionColor(connection, color);
			  
			  	return true;
		     }
		     
		     return false;
		},
		
		changeConnectionConnectorType : function(connection, connector_type) {
			connection.setParameter("connection_exit_type", connector_type);
		
			return this.setNewConnectionConnector(connection);
		},
	
		getConnectionConnector : function(selected_connector_type) {
			if (selected_connector_type) {
				var connectors = this.available_connection_connectors;
				
				for (var j in connectors)
					if (connectors[j][0] == selected_connector_type)
						return connectors[j];
			}
		
			return null;
		},
		/*** END: CONNECTION-CONNECTORS METHODS ***/
		/* END: CONNECTION METHODS */
	
		/* START: TASK METHODS */
		/*** START: TASK-OBJ METHODS ***/
		deleteTask : function(task_id, options) {
			if (task_id) {
				var task_label = this.getTaskLabelByTaskId(task_id);
			
				var to_confirm = !options || options.confirm;
				var user_confirmation = null;
				
				if (!to_confirm || (user_confirmation = confirm("Do you really want to delete the task: " + task_label + "?"))) {
					var j_task = this.getTaskById(task_id);
					var task_type = j_task.attr("type");
					var status = WF.jsPlumbProperty.callTaskSettingsCallback(task_type, "on_check_task_deletion", [ task_id, j_task ]);
					
					if (status) {
						//hide task properties popup if opened
						if (WF.jsPlumbProperty.isSelectedTaskPropertiesWithTaskId(task_id)) {
							var selected_task_properties = $("#" + WF.jsPlumbProperty.selected_task_properties_id);
							var tasks_menu_hide = $("#" + WF.jsPlumbContextMenu.main_tasks_menu_hide_obj_id);
							var parent = tasks_menu_hide.parent().closest(".taskflowchart");
							
							//if properties are fixed and hidden, do not do anything, otherwise it will mess the layout
							if (parent.is(".fixed_side_properties") && selected_task_properties.is(".tasks_menu_hidden"))
								WF.jsPlumbContextMenu.toggleTasksMenuPanel();
							
							if (WF.jsPlumbProperty.isSelectedTaskPropertiesOpen())
								WF.jsPlumbProperty.hideSelectedTaskProperties();
						}
						
						//delete potential connections
						var connections = this.getConnections();
						for (var i = 0; i < connections.length; i++)
							if (connections[i].sourceId == task_id || connections[i].targetId == task_id)
								this.deleteConnection(connections[i].id, true);
						
						//delete task if exists
						if (j_task[0]) {
							//delete children tasks
							var inner_tasks = j_task.find("." + this.task_class_name);
							
							for (var i = 0; i < inner_tasks.length; i++) {
								var inner_task = $(inner_tasks[i]);
								
								if (this.isTaskParentTask(inner_task, j_task)) { //only task children
									var inner_task_id = $(inner_tasks[i]).attr("id");
									this.deleteTask(inner_task_id, {to_confirm: false});
								}
							}
							
							//delete eps
							var eps = j_task.find(this.source_selector);
							
							for (var i = 0; i < eps.length; i++) {
								var ep = $(eps[i]);
								
								ExternalLibClone.removeAllEndpoints(ep);
								
								try {
									ExternalLibClone.unmakeSource(ep);
								} catch(e) {
									//console.log(e);
								}
								
								ep.remove();
							}
							
							//delete task
							ExternalLibClone.removeAllEndpoints(j_task);
							ExternalLibClone.unmakeSource(j_task);
							ExternalLibClone.unmakeTarget(j_task);
							
							j_task.remove();
							
							//call callback
							var status = WF.jsPlumbProperty.callTaskSettingsCallback(task_type, "on_success_task_deletion", [ task_id, j_task ]);
							
							this.tasks_properties[task_id] = null;
							
							return status;
						}
					}
				}
				
				if (to_confirm && user_confirmation == false) {
					return true;
				}
			}
			return false;
		},
		
		getTasksByLabel : function(label, case_sensitive) {
			var searched_tasks = [];
			
			if (label) {
				var tasks = this.getAllTasks();
				
				if (tasks) {
					if (!case_sensitive)
						label = ("" + label).toLowerCase();
					
					for (var i = 0, l = tasks.length; i < l; i++) {
						var task = tasks[i];
						var task_label = this.getTaskLabel(task);
						
						if (task_label) {
							if (!case_sensitive)
								task_label = task_label.toLowerCase();
							
							if (task_label == label)
								searched_tasks.push(task);
						}
					}
				}
			}
			
			return searched_tasks;
		},
		
		getLastAddedTask : function() {
			return $("#" + this.main_tasks_flow_obj_id + " > ." + this.task_class_name + ":last-child").last();
		},
		getLastAddedTaskByType : function(task_type) {
			return $("#" + this.main_tasks_flow_obj_id + " > ." + this.task_class_name + ".task_" + task_type + ":last-child").last();
		},
		getLastAddedTaskByTag : function(task_tag) {
			return $("#" + this.main_tasks_flow_obj_id + " > ." + this.task_class_name + ".task_" + task_tag + ":last-child").last();
		},
		
		isTaskParentTask : function(task, parent_task) {
			return this.getTaskParentTasks(task).first().is(parent_task);
		},
		
		isTaskAncestorTask : function(task, parent_task) {
			var parents = this.getTaskParentTasks(task);
			var parent_task_id = parent_task.attr("id");
			
			for (var i = 0; i < parents.length; i++) 
				if ($(parents[i]).attr("id") == parent_task_id)
					return true;
			
			return false;
		},
		
		//get only the direct children tasks (inside of another task)
		getTaskChildrenTasks : function(task) {
			var inner_tasks = this.getTaskInnerTasks(task);
			var children = [];
			
			for (var i = 0; i < inner_tasks.length; i++) {
				var inner_task = $(inner_tasks[i]);
				
				if (this.isTaskParentTask(inner_task, task))
					children.push(inner_task);
			};
			
			return children;
		},
		
		//get all inner tasks (inside of another task)
		getTaskInnerTasks : function(task) {
			return task.find("." + this.task_class_name);
		},
		
		//get parents which are tasks
		getTaskParentTasks : function(task) {
			var main_tasks_flow_obj = $("#" + this.main_tasks_flow_obj_id);
			return task.parentsUntil(main_tasks_flow_obj, "." + this.task_class_name);
		},
		
		getAllTasks : function() {
			return $("#" + this.main_tasks_flow_obj_id + " ." + this.task_class_name);
		},
		//resize parent task with the new width and height according with this inner task
		resizeTaskParentTask : function(task, force) {
			var parents = this.getTaskParentTasks(task);
			
			$.each(parents, function(idx, parent) {
				//console.log(parent);
				parent = $(parent);
				var is_resizable_task = parent.attr("is_resizable_task");
				
				if (!is_resizable_task || force) //only if not resizable
					WF.jsPlumbTaskFlow.resizeTaskBasedOnInnerTasks(parent, force);
			});
		},
		
		//resize droppable task with the new width and height according with the new dropped inner task	
		resizeTaskBasedOnInnerTasks : function(task, force) {
			var is_resizable_task = task.attr("is_resizable_task");
			
			if (!is_resizable_task || force) { //only if not resizable
				var droppables = task.find("." + this.task_droppable_class_name);
				
				if (droppables.length > 0) {
					var child_droppables = [];
					for (var i = 0; i < droppables.length; i++) {
						var d = $(droppables[i]);
						
						if (d.closest("." + this.task_class_name).is(task))
							child_droppables.push(d[0]);
					}
					
					var right = 0;
					var bottom = 0;
					//console.log("child_droppables length:"+child_droppables.length);
					
					$.each(child_droppables, function (idx, child_droppable) {
						//console.log(child_droppable);
						child_droppable = $(child_droppable);
						
						var child_droppable_parents = child_droppable.parentsUntil(task);
						child_droppable_parents = child_droppable_parents.toArray();
						child_droppable_parents.unshift(child_droppable[0]);
						
						//getting top and left for droppables
						var dl = 0, dt = 0;
						
						$.each(child_droppable_parents, function(idy, parent) {
							var o = WF.jsPlumbTaskFlow.getElementOffsetWithMargin( $(parent) );
							dt += o.top;
							dl += o.left;
						});
						
						//getting width and height according with droppables current width and height
						var height = child_droppable.outerHeight() + dt;
						var width = child_droppable.outerWidth() + dl;
						//console.log("child_droppable offset:"+dl+"|"+dt);
						//console.log("child_droppable:"+width+"|"+height);
						
						if (right < width)
							right = width;
						
						if (bottom < height)
							bottom = height;
						
						//getting width and height for inner task and with the top and left of the correspondent droppable
						var inner_tasks = child_droppable.children("." + WF.jsPlumbTaskFlow.task_class_name);
						
						$.each(inner_tasks, function(idx, child) {
							//console.log(child);
							child = $(child);
							var h = child.outerHeight();
							var w = child.outerWidth();
							var o = WF.jsPlumbTaskFlow.getElementOffsetWithMargin(child);
							//console.log("inner_task:"+w+"|"+h);
							
							h += dt + o.top + 10;
							w += dl + o.left + 10;
							//console.log("inner_task:"+w+"|"+h);
							//console.log(child.position());
							
							if (right < w)
								right = w;
							
							if (bottom < h)
								bottom = h;
						});
					});	
					//console.log("inner tasks and droppables:"+right+"|"+bottom);
					
					//adding paddings
					var pr = parseInt(task.css("padding-right"));
					var pb = parseInt(task.css("padding-bottom"));
					
					if (pr > 0)
						right += pr;
					
					if (pb > 0)
						bottom += pb;
					
					//getting with and height according with the current task width and height
					var width = task.width();
					var height = task.height();
					//console.log(width+"|"+height);
					
					if (width < right || height < bottom) {
						//setting task width and height if necessary
						if (width < right)
							task.css("width", right + "px");
						
						if (height < bottom)
							task.css("height", bottom + "px");
						
						WF.jsPlumbTaskFlow.repaintTask(task);
					}
				}
			}
		},
		/*** END: TASK-OBJ METHODS ***/
	
		/*** START: TASK-ID METHODS ***/
		getTaskById : function(task_id) {
			return $("#" + this.main_tasks_flow_obj_id + " #" + task_id);
		},
		
		getRealTaskIdFromCaseInsensitiveId : function(task_id) {
			var tasks = $("#" + this.main_tasks_flow_obj_id + " ." + this.task_class_name);
			var real_task_id = null;
			task_id = task_id.toLowerCase();
			
			$.each(tasks, function (idx, task) {
				var t_id = $(task).attr("id");
				
				if (t_id.toLowerCase() == task_id) {
					real_task_id = t_id;
					return false;
				}
			});
			
			return real_task_id;
		},
		
		getEventTargetTaskId : function(target_obj) {
			/*
			in case of the task div:
				HTMLDivElement
			or
			in case of the source circle:
				HTMLDivElement
					HTMLDivElement
			or
			in case of a inner element of the source circle EP:
				HTMLDivElement
					HTMLDivElement
						HTMLDivElement
							inner element
			or
			in case of a circle endpoint:
				HTMLDivElement
			or
			in case of the label:
				HTMLDivElement
					HTMLSpanElement
			or
			in case of the label:
				HTMLDivElement
					HTMLDivElement
						HTMLSpanElement
			*/
		
			var task_id = null;
			var j_target_obj = $(target_obj);
		
			//if task
			if (j_target_obj.hasClass(this.task_class_name))
				task_id = j_target_obj.attr("id");
			//if circle endpoint
			else if (j_target_obj.attr("task_id"))
				task_id = j_target_obj.attr("task_id");
			//if source endpoint EP
			else if (j_target_obj.attr("target_id")) 
				task_id = j_target_obj.attr("target_id");
		
			//if label or other source circle
			if (!task_id) {
				var p = j_target_obj.parent();
			
				if (p && p[0]) {
					if (p.hasClass(this.task_class_name)) {
						task_id = p.attr("id");
					}
					else {
						p = p.parent();
					
						if (p && p[0] && p.hasClass(this.task_class_name)) {
							task_id = p.attr("id");
						}
					}
				}
			}
		
			//If is a inner element of the source circle EP
			if (!task_id) {
				var p = j_target_obj;
				var limit = 20;
			
				while (true) {
					p = p.parent();
			
					if (p && p[0]) {
						if (p.hasClass(this.task_class_name)) {
							task_id = p.attr("id");
							break;
						}
						else if (p.hasClass(this.task_ep_class_name)) {
							task_id = p.attr("target_id");
					
							//only breaks if task_id is valid, otherwise continues and tries to get the task_id directly from the task.
							if (task_id) {
								break;
							}
						}
				
						if (limit <= 0) {
							break;
						}
				
						--limit;
					}
					else {
						break;
					}
				}
			}
			
			return task_id;
		},
		
		TaskIdExists : function(task_id) {
			var elms = $("#" + this.main_tasks_flow_obj_id + " ." + this.task_class_name);

			for (var i = 0; i < elms.length; i++) {
				if ($(elms[i]).attr("id") == task_id) {
					return true;
				}
			}
			return false;
		},
		/*** END: TASK-ID METHODS ***/
	
		/*** START: TASK-LABEL METHODS ***/
		getNewLabel : function(label_text, parent_id, task_type, check_callback_name) {//parent_id == task_id or connection_id
			var label_obj = {label : label_text};
			var invalid;
			var func = WF.jsPlumbProperty.getTaskSubSetting(task_type, "callback", check_callback_name);
		
			do {
				label_obj.label = prompt("Please enter the label text:", label_obj.label);
			
				if (label_obj.label != null) {
					if (func)
						invalid = WF.jsPlumbProperty.callTaskSettingsCallback(task_type, check_callback_name, [ label_obj, parent_id ]) ? false : true;
					else
						invalid = false;
				}
		  	} 
		  	while (label_obj.label != null && invalid);
		  	
		  	return label_obj;
		},
		
		saveTaskLabel : function(elm, label_obj) {
			var j_elm = $(elm);
			
			var task = j_elm.parents(this.target_selector).first();
			task = task[0];
			var j_task = $(task);
			var task_id = j_task.attr("id");
			var task_type = j_task.attr("type");
			
			WF.jsPlumbProperty.callTaskSettingsCallback(task_type, "on_submit_task_label", [ label_obj, task_id ]);
	  		
  			label_obj.label = label_obj.label == "" ? this.default_label : label_obj.label;
			j_elm.html(label_obj.label);
			j_task.children("." + this.task_full_label_class_name).first().html(label_obj.label);
			
			WF.jsPlumbTaskFlow.centerTaskInnerElements(task_id);
			WF.jsPlumbProperty.callTaskSettingsCallback(task_type, "on_complete_task_label", [ task_id ]);
		},
		
		setTaskLabel : function(elm, label_obj) {
			var j_elm = $(elm);
			
			var task = j_elm.parents(this.target_selector).first();
			task = task[0];
			var j_task = $(task);
			var task_id = j_task.attr("id");
			var task_type = j_task.attr("type");
			
			if (WF.jsPlumbProperty.callTaskSettingsCallback(task_type, "on_start_task_label", [ task_id ])) {
			  	label_obj = label_obj ? label_obj : this.getNewLabel(j_elm.text(), task_id, task_type, "on_check_task_label");
			  	
			  	if (label_obj.label != null)
			  		this.saveTaskLabel(elm, label_obj);
				else 
					WF.jsPlumbProperty.callTaskSettingsCallback(task_type, "on_cancel_task_label", [ task_id ]);
			
				return true;
			}
			return false;
		},
	
		setTaskLabelByTaskId : function(task_id, label_obj) {
			if (task_id) {
				var elm = this.getTaskLabelElementByTaskId(task_id);
				return this.setTaskLabel(elm, label_obj);
			}
			return false;
		},
		
		getTaskLabelByTaskId : function(task_id) {
			if (task_id) {
				var j_task = this.getTaskById(task_id);
				return this.getTaskLabel(j_task);
			}
			return false;
		},
		
		getTaskLabel : function(task) {
			return task ? this.getTaskLabelElement(task).text() : false;
		},
	
		getTaskLabelElement : function(task) {
			if (task) {
				var j_task = task[0] ? task : $(task);
				return j_task.find(" > ." + this.task_label_class_name + " span").first();
			}
			return false;
		},
		
		getTaskLabelElementByTaskId : function(task_id) {
			if (task_id) {
				var j_task = this.getTaskById(task_id);
				return this.getTaskLabelElement(j_task);
			}
			return false;
		},
		/*** END: TASK-LABEL METHODS ***/
		/* END: TASK METHODS */
		
		/* START: ZOOM METHODS */
		getCurrentZoom : function() {
			return this.current_zoom;
		},
		
		zoomIn : function() {
			this.current_zoom = (this.current_zoom > 0 ? this.current_zoom : 0) + 0.02;
			this.zoom(this.current_zoom);
		},
		
		zoomOut : function() {
			this.current_zoom = (this.current_zoom > 0 ? this.current_zoom : 0) - 0.02;
			this.zoom(this.current_zoom);
		},
		
		zoomReset : function() {
			this.current_zoom = 1;
			this.zoom(this.current_zoom);
		},
		
		zoomRefresh : function() {
			//update width_without_zoom and height_without_zoom
			if (this.current_zoom != 1) {
				var main_tasks_flow_obj = $("#" + this.main_tasks_flow_obj_id);
				var width_without_zoom = main_tasks_flow_obj.data("width_without_zoom");
				var height_without_zoom = main_tasks_flow_obj.data("height_without_zoom");
				
				if ($.isNumeric(width_without_zoom)) { //it could be 0
					main_tasks_flow_obj.css("width", "");
					width_without_zoom = main_tasks_flow_obj.width();
					main_tasks_flow_obj.data("width_without_zoom", width_without_zoom);
					//console.log("width:"+width_without_zoom+"/"+this.current_zoom+"="+(width_without_zoom / this.current_zoom));
					
					main_tasks_flow_obj.css("width", (width_without_zoom / this.current_zoom) + "px");
				}
				
				if ($.isNumeric(height_without_zoom)) { //it could be 0
					main_tasks_flow_obj.css("height", "");
					height_without_zoom = main_tasks_flow_obj.height();
					main_tasks_flow_obj.data("height_without_zoom", height_without_zoom);
					//console.log("height:"+height_without_zoom+"/"+this.current_zoom+"="+(height_without_zoom / this.current_zoom));
					
					main_tasks_flow_obj.css("height", (height_without_zoom / this.current_zoom) + "px");
				}
			}
			
			this.zoom(this.current_zoom);
		},
		
		zoom : function(level) {
			level = parseFloat(level); //level may be a string and we must convert it to a numeric type.
			
			this.zoom_timeout_id && clearTimeout(this.zoom_timeout_id);
			
			var main_tasks_flow_obj = $("#" + this.main_tasks_flow_obj_id);
			var width_without_zoom = main_tasks_flow_obj.data("width_without_zoom");
			var height_without_zoom = main_tasks_flow_obj.data("height_without_zoom");
			
			if (!width_without_zoom) {
				width_without_zoom = main_tasks_flow_obj.width();
				main_tasks_flow_obj.data("width_without_zoom", width_without_zoom);
			}
			
			if (!height_without_zoom) {
				height_without_zoom = main_tasks_flow_obj.height();
				main_tasks_flow_obj.data("height_without_zoom", height_without_zoom);
			}
			
			var transform = "";
			var width = "";
			var height = "";
			
			if (level == 1 || !$.isNumeric(level)) {
				//reset saved data, so it can get the updated values, just in case if they were changed manuallly by the user on purpose...
				main_tasks_flow_obj.data("width_without_zoom", null);
				main_tasks_flow_obj.data("height_without_zoom", null);
			}
			else { //resize the main_tasks_flow_obj so it can be aside with the tasks_menu_hide and tasks_menu
				if (level < 0)
					level = 0;
				
				transform = "scale(" + level + ")";
				width = (width_without_zoom / level) + "px";
				height = (height_without_zoom / level) + "px";
			}
			
			this.current_zoom = level;
			
			main_tasks_flow_obj.css({
				"transform": transform,
				"transform-origin": transform ? "0 0" : "",
				"width": width,
				"height": height,
			});
			
			ExternalLibClone.setZoom(this.current_zoom); //very important otherwise the connections will be messy
			
			setTimeout(function() {
				ExternalLibClone.repaintEverything(); //very important otherwise the connections will be messy
			}, 200);
			
			//resize containers
			WF.jsPlumbContainer.automaticZoomContainers();
			
			this.zoom_timeout_id = setTimeout(function() {
				WF.jsPlumbContainer.automaticZoomContainers();
				
				ExternalLibClone.repaintEverything(); //very important otherwise the connections will be messy
			}, 1000);
		},
		/* END: ZOOM METHODS */
		
		/* START: UTILS METHODS */
		//get position from all parents until main_tasks_flow_obj
		getNewElmPosition : function(main_tasks_flow_obj, parent, draggable_position) {
			var new_top = draggable_position.top;
			var new_left = draggable_position.left;
			
			while(!parent.is(main_tasks_flow_obj)) {
				var o = WF.jsPlumbTaskFlow.getElementOffsetWithMargin(parent);
				new_top += o.top;
				new_left += o.left;
				
				parent = parent.parent();
			}
			
			return {top: new_top, left: new_left};
		},
		
		getElementOffsetWithMargin : function(elm) {
			var t = 0, l = 0;
			
			var o = elm.position();
			t = o.top;
			l = o.left;
			
			var mt = parseInt(elm.css("margin-top"));
			var ml = parseInt(elm.css("margin-left"));
			t += mt > 0 ? mt : 0;
			l += ml > 0 ? ml : 0;
			
			return {top: t, left: l};
		},
		
		getTasksFlowElementsMaxZIndex : function() {
			var items = $("#" + this.main_tasks_flow_obj_id + " *");
			
			var max_z_index = Math.max.apply(
				null,
				$.map(items, function(e, n) {
					var position = $(e).css('position');
					
					if(position == 'absolute' || position == 'fixed')
						return parseInt( $(e).css('z-index') ) || 1;
				})
			);
			
			//2147483647 is the 2^32, which is the maximum value for integers in 32 bits systems.
			return max_z_index >= 2147483647 ? 2147483646 : max_z_index;
		},
		/* END: UTILS METHODS */
	};

	WF.jsPlumbProperty = { 
		tasks_settings : null,
		auto_save : true,
		
		selected_task_properties_id : "selected_task_properties",
		selected_task_properties_classes : "selected_task_properties",
		selected_connection_properties_id : "selected_connection_properties",
		selected_connection_properties_classes : "selected_connection_properties",
		
		selected_connection_properties_id_text : 'Connection ID: "<span></span>"',
		selected_task_properties_id_text : 'Task Type: "<span class="task_tag"></span>", Task ID: "<span class="task_id"></span>"',
		
		/* START: INIT METHODS */
		init : function() {
			this.selected_task_properties_id += "_" + Math.abs(WF.jsPlumbTaskFlow.main_tasks_flow_obj_id.hashCode());
			this.selected_connection_properties_id += "_" + Math.abs(WF.jsPlumbTaskFlow.main_tasks_flow_obj_id.hashCode());
			
			this.initConnectionProperties();
			this.initTaskProperties();
		},
	
		initConnectionProperties : function() {
			var resizable_connection_properties = WF.jsPlumbWorkFlowObjOptions && WF.jsPlumbWorkFlowObjOptions.hasOwnProperty("resizable_connection_properties") && WF.jsPlumbWorkFlowObjOptions.resizable_connection_properties;
			
			var html = '<div id="' + this.selected_connection_properties_id + '" class="myfancypopup ' + this.selected_connection_properties_classes + '">' + 
					'<span class="resize_properties_panel"><div class="button" onClick="' + jsPlumbWorkFlowObjVarName + '.jsPlumbProperty.collapseSelectedConnectionProperties(this)" title="Collapse/Expand Panel"></div></span>' + 
					'<span class="maximize_minimize_icon" onClick="' + jsPlumbWorkFlowObjVarName + '.jsPlumbProperty.maximizeSelectedConnectionProperties(this)" title="Toggle Size"></span>' + 
					'<span class="toggle_properties_side_icon" onClick="' + jsPlumbWorkFlowObjVarName + '.jsPlumbProperty.toggleSelectedConnectionPropertiesSide(this)" title="Toggle Dock Side"></span>' + 
					'<div class="title">Connection Properties</div>' + 
					'<div class="properties_connection_id">' + this.selected_connection_properties_id_text + '</div>' + 
					'<div class="content"></div>' + 
					'<div class="generic_buttons">' + 
						'<input type="button" class="cancel" value="Cancel" onClick="' + jsPlumbWorkFlowObjVarName + '.jsPlumbProperty.cancelConnectionProperties()" />' + 
				 		'<input type="button" class="save" value="Save" onClick="' + jsPlumbWorkFlowObjVarName + '.jsPlumbProperty.saveConnectionProperties()" />' + 
					'</div>' + 
				 '</div>';
			
			var selected_connection_properties = $(html);
			var main_tasks_flow_obj = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id);
			main_tasks_flow_obj.after(selected_connection_properties);
			
			if (resizable_connection_properties)
				WF.jsPlumbProperty.prepareResizablePropertiesPanel(selected_connection_properties);
			
			WF.jsPlumbProperty.hideSelectedConnectionProperties();
		},
	
		initTaskProperties : function() {
			var resizable_task_properties = WF.jsPlumbWorkFlowObjOptions && WF.jsPlumbWorkFlowObjOptions.hasOwnProperty("resizable_task_properties") && WF.jsPlumbWorkFlowObjOptions.resizable_task_properties;
			
			var html ='<div id="' + this.selected_task_properties_id + '" class="myfancypopup ' + this.selected_task_properties_classes + '">' + 
					'<span class="resize_properties_panel"><div class="button" onClick="' + jsPlumbWorkFlowObjVarName + '.jsPlumbProperty.collapseSelectedTaskProperties(this)" title="Collapse/Expand Panel"></div></span>' + 
					'<span class="maximize_minimize_icon" onClick="' + jsPlumbWorkFlowObjVarName + '.jsPlumbProperty.maximizeSelectedTaskProperties(this)" title="Toggle Size"></span>' + 
					'<span class="toggle_properties_side_icon" onClick="' + jsPlumbWorkFlowObjVarName + '.jsPlumbProperty.toggleSelectedTaskPropertiesSide(this)" title="Toggle Dock Side"></span>' + 
					'<div class="title">Task Properties</div>' + 
					'<div class="properties_task_id">' + this.selected_task_properties_id_text + '</div>' + 
					'<div class="content" stopPropagation="1"></div>' + 
				 	'<div class="generic_buttons">' + 
						'<input type="button" class="cancel" value="Cancel" onClick="' + jsPlumbWorkFlowObjVarName + '.jsPlumbProperty.cancelTaskProperties()" />' + 
				 		'<input type="button" class="save" value="Save" onClick="' + jsPlumbWorkFlowObjVarName + '.jsPlumbProperty.saveTaskProperties()" />' + 
					'</div>' + 
				 '</div>';
			
			var selected_task_properties = $(html);
			var main_tasks_flow_obj = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id);
			main_tasks_flow_obj.after(selected_task_properties);
			
			if (resizable_task_properties)
				WF.jsPlumbProperty.prepareResizablePropertiesPanel(selected_task_properties);
			
			WF.jsPlumbProperty.hideSelectedTaskProperties();
		},
		/* END: INIT METHODS */
	
		/* START: FUNCTION METHODS */
		prepareResizablePropertiesPanel : function(properties_panel) {
			var margin_bottom = null;
			
			properties_panel.draggable({
				axis: "y",
				appendTo: 'body',
				cursor: 'move',
		          tolerance: 'pointer',
		          handle: ' > .resize_properties_panel',
		          cancel: '.button', //inside of handle
		    		start: function(event, ui) {
					properties_panel.addClass("resizing_properties").removeClass("maximize_properties");
					
					margin_bottom = parseInt(ui.helper.css("margin-bottom")); /* in some cases the selected_task_properties or selected_connection_properties have margin bottom. So we must use it too, otherise the vertical resize will look weird. */
					
					return true;
				},
				drag: function(event, ui) {
					var h = $(window).height() - (ui.offset.top - $(window).scrollTop()) - margin_bottom;
					
					properties_panel.css({
						height: h + "px",
						top: "", 
						left: "",
						bottom: ""
					});
					
					WF.jsPlumbProperty.resizePropertiesPanel(properties_panel, h);
				},
				stop: function(event, ui) {
					var h = $(window).height() - (ui.offset.top - $(window).scrollTop()) - margin_bottom;
					
					properties_panel.css({
						height: h + "px",
						top: "", 
						left: "",
						bottom: ""
					});
					properties_panel.removeClass("resizing_properties");
					
					WF.jsPlumbProperty.resizePropertiesPanel(properties_panel, h);
				},
			});
		},
		
		resizePropertiesPanel : function(properties_panel, height) {
			var wh = $(window).height();
			var diff = wh - height;
			var parent = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id).parent().closest(".taskflowchart");
			var is_fixed_side_properties = parent.hasClass("fixed_side_properties");
			var is_fixed_properties = parent.hasClass("fixed_properties");
			
			//only resize if is_fixed_side_properties or is_fixed_properties
			var panels = is_fixed_side_properties ? $("#" + WF.jsPlumbContextMenu.main_tasks_menu_obj_id) : (
							is_fixed_properties ? $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id + ", #" + WF.jsPlumbContextMenu.main_tasks_menu_obj_id + ", #" + WF.jsPlumbContextMenu.main_tasks_menu_hide_obj_id) : null
						);
			
			if (diff >= wh - 30) { //30 is the size of properties_panel .resize_properties_panel when collapsed
				//add collapsed_properties
				if (properties_panel.hasClass("selected_connection_properties"))
					WF.jsPlumbProperty.addSelectedConnectionPropertiesCollapseClass(properties_panel);
				else if (properties_panel.hasClass("selected_task_properties"))
					WF.jsPlumbProperty.addSelectedTaskPropertiesCollapseClass(properties_panel);
				
				properties_panel.css("height", ""); //remove height from style attribute in properties_panel
				
				if (panels)
					panels.css("bottom", ""); //remove css from other panels
			}
			else {
				if (properties_panel.hasClass("collapsed_properties")) {
					//remove collapsed_properties
					if (properties_panel.hasClass("selected_connection_properties"))
						WF.jsPlumbProperty.removeSelectedConnectionPropertiesCollapseClass(properties_panel);
					else if (properties_panel.hasClass("selected_task_properties"))
						WF.jsPlumbProperty.removeSelectedTaskPropertiesCollapseClass(properties_panel);
				}
				
				if (diff < 10) {
					properties_panel.addClass("maximize_properties");
					properties_panel.css("height", ""); //remove height from style attribute in properties_panel
					
					if (panels)
						panels.css("bottom", ""); //remove css from other panels
				}
				else if (panels)
					panels.css("bottom", height + "px");
			}
			
			//when zoom is active panels are not resized correctly, bc when the zoomRefresh runs below, the panels are still resizing...
			setTimeout(function() {
				WF.jsPlumbTaskFlow.zoomRefresh();
			}, 300);
			
			//...and in some cases we need to call zoomRefresh again after it the resizing finishes...
			setTimeout(function() {
				WF.jsPlumbTaskFlow.zoomRefresh();
			}, 1000);
		},
		
		togglePropertiesPanelSide : function(properties_panel, properties_panel_resizable) {
			var parent = properties_panel.parent().closest(".taskflowchart");
			
			if (parent.hasClass("fixed_side_properties")) { //it will show the fixed_properties
				properties_panel.data("saved_width", properties_panel[0].style.width);
				
				if (properties_panel_resizable)
					properties_panel.data("saved_height", properties_panel[0].style.height);
				
				parent.removeClass("fixed_side_properties").addClass("fixed_properties");
				
				//remove width in case there was a resize of the task_menu_hide
				properties_panel.css({
					width: "",
				});
			}
			else if (parent.hasClass("fixed_properties")) { //it will show the a moveable popup
				parent.removeClass("fixed_properties");
				
				//remove height in case there was a resize
				properties_panel.css({
					height: "",
				});
				
				//remove previous draggable event
				WF.jsPlumbProperty.removePropertiesPanelDraggableEvent(properties_panel);
				
				//add draggable event
				properties_panel.draggable({
					stop: function(originalEvent, ui) {
						MyFancyPopupClone.resizeOverlay();
					}
				});
				
				//center popup
				setTimeout(function() { //must be with settimeout
					MyFancyPopupClone.updatePopup();
				}, 100);
			}
			else { //it will show the fixed_side_properties
				parent.addClass("fixed_side_properties");
				
				properties_panel.css({
					width: "",
					height: "",
				});
				
				var saved_width = properties_panel.data("saved_width");
				var saved_height = properties_panel.data("saved_height");
				
				if (saved_width != "")
					properties_panel.css("width", saved_width);
				
				if (properties_panel_resizable && saved_height != "")
					properties_panel.css("height", saved_height);
				
				//remove previous draggable event
				WF.jsPlumbProperty.removePropertiesPanelDraggableEvent(properties_panel);
				
				if (properties_panel_resizable) {
					//add draggable event
					WF.jsPlumbProperty.prepareResizablePropertiesPanel(properties_panel);
				}
				
				//resize fixed_side_properties panels according with tasks_menu_hide
				WF.jsPlumbProperty.updateFixedSidePropertiesPanelWidth();
			}
			
			//remove hard code style from tasks_flow, tasks_menu and tasks_menu_hide
			var panels = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id + ", #" + WF.jsPlumbContextMenu.main_tasks_menu_obj_id + ", #" + WF.jsPlumbContextMenu.main_tasks_menu_hide_obj_id);
			
			panels.css({
				top: "",
				bottom: "",
			});
			
			//remove hard code style from properties_panel
			properties_panel.css({
				position: "",
				top: "",
				left: "",
				right: "",
				bottom: "",
			});
			
			if (properties_panel_resizable) {
				WF.jsPlumbProperty.resizePropertiesPanel(properties_panel, properties_panel.outerHeight());
				
				setTimeout(function() { //must be with settimeout with at least .5 sec, bc the properties_panel has the css transition:.5s all;
					WF.jsPlumbProperty.resizePropertiesPanel(properties_panel, properties_panel.outerHeight());
				}, 600);
			}
			
			//callback
			if (WF.jsPlumbWorkFlowObjOptions && WF.jsPlumbWorkFlowObjOptions.hasOwnProperty("on_toggle_properties_panel_side_function") && typeof WF.jsPlumbWorkFlowObjOptions.on_toggle_properties_panel_side_function == "function")
				WF.jsPlumbWorkFlowObjOptions.on_toggle_properties_panel_side_function(properties_panel, properties_panel_resizable);
		},
		
		//Note that any change here must be done in the callback/handler: tasks_menu_hide.draggable({drag: handler});
		updateFixedSidePropertiesPanelWidth : function() {
			//resize fixed_side_properties panels according with tasks_menu_hide
			var selected_task_properties = $("#" + WF.jsPlumbProperty.selected_task_properties_id);
			var selected_connection_properties = $("#" + WF.jsPlumbProperty.selected_connection_properties_id);
			var tasks_menu_hide = $("#" + WF.jsPlumbContextMenu.main_tasks_menu_hide_obj_id);
			var parent = tasks_menu_hide.parent().closest(".taskflowchart");
			
			var tasks_menu_hide_offset = tasks_menu_hide.offset();
			var parent_position = ("" + parent.css("position")).toLowerCase();
			parent_extra = parent_position == "relative" || parent_position == "absolute" ? parent.offset().left : 0;
			
			if (parent.hasClass("reverse")) {
				var w = $(window).width() - (tasks_menu_hide_offset.left - $(window).scrollLeft()) - parent_extra;
				var tasks_menu_w = (w - tasks_menu_hide.outerWidth());
				
				selected_task_properties.css("width", tasks_menu_w + "px");
				selected_connection_properties.css("width", tasks_menu_w + "px");
			}
			else {
				var w = tasks_menu_hide_offset.left - parent_extra;
				
				selected_task_properties.css("width", w + "px");
				selected_connection_properties.css("width", w + "px");
			}
		},
		
		removePropertiesPanelDraggableEvent : function(properties_panel) {
			if (typeof properties_panel.draggable == "function" && (properties_panel.data("draggable") || properties_panel.hasClass("ui-draggable")))
				properties_panel.removeClass("ui-draggable").draggable("destroy");
		},
		
		callTaskSettingsCallback : function(task_type, callback_name, callback_args) {
			var func = this.getTaskSubSetting(task_type, "callback", callback_name);
		
			if (func && typeof func == "function") {
				var str = "func (";
				if (callback_args) {
					for (var i = 0; i < callback_args.length; i++) {
						str += (i > 0 ? ", " : "") + "callback_args[" + i + "]";
					}
				}
				str += ");";
			
				myWFObj.setJsPlumbWorkFlow(WF);
				
				//console.log(callback_name+":"+str);
				eval ("$status = " + str);
				return $status;
			}
		
			return true;
		},
	
		getTaskSetting : function(task_type, setting_name) {
			if (this.tasks_settings && this.tasks_settings[task_type]) {
				var task_settings = this.tasks_settings[task_type];
				var setting = task_settings[setting_name];
			
				return setting;
			}
		
			return null;
		},
	
		getTaskSubSetting : function(task_type, setting_name, sub_setting_name) {
			var settings = this.getTaskSetting(task_type, setting_name);
			return settings ? settings[sub_setting_name] : null;
		},
	
		hasTaskSetting : function(task_type, setting_name) {
			return this.tasks_settings && this.tasks_settings[task_type] && this.tasks_settings[task_type].hasOwnProperty(setting_name);
		},
	
		hasTaskSubSetting : function(task_type, setting_name, sub_setting_name) {
			var setting = this.getTaskSetting(task_type, setting_name);
		
			return setting && setting.hasOwnProperty(sub_setting_name);
		},
	
		isTaskSettingTrue : function(task_type, setting_name, if_not_exists_value) {
			if (this.hasTaskSetting(task_type, setting_name)) {
				var setting = this.getTaskSetting(task_type, setting_name);
				return setting && setting != "" && setting != "0" && setting != "false" && setting != false;
			}
		
			return if_not_exists_value;
		},
	
		isTaskSubSettingTrue : function(task_type, setting_name, sub_setting_name, if_not_exists_value) {
			if (this.hasTaskSubSetting(task_type, setting_name, sub_setting_name)) {
				var setting = this.getTaskSubSetting(task_type, setting_name, sub_setting_name);
				return setting && setting != "" && setting != "0" && setting != "false" && setting != false;
			}
		
			return if_not_exists_value;
		},
		/* END: FUNCTION METHODS */
	
		/* START: INTERFACE METHODS */
		isSelectedConnectionPropertiesOpen : function() {
			//return $("#" + this.selected_connection_properties_id).is(":visible") && MyFancyPopupClone.isPopupOpened(); //Do not use :visible, bc if the tasksflow in inside of a tab and tht tab is not active, then the :visible will return false, even if the properties are open.
			return $("#" + this.selected_connection_properties_id).css("display") != "none" && MyFancyPopupClone.isPopupOpened();
		},
		
		isSelectedConnectionPropertiesWithConnectionId : function(connection_id) {
			return $("#" + this.selected_connection_properties_id).attr("connection_id") == connection_id;
		},
		
		showSelectedConnectionProperties : function(options) {
			if (typeof options != "object" || !options["do_not_call_hide_properties"])
				WF.jsPlumbContextMenu.hideContextMenus();
			
			var connection_id = WF.jsPlumbContextMenu.getContextMenuConnectionId();
		
			return this.showConnectionProperties(connection_id, {remove_collapse: true});
		},
		
		showConnectionProperties : function(connection_id, options) {
			var connection = WF.jsPlumbTaskFlow.getConnection(connection_id);
			
			if (connection) {
				var selected_connection_properties = $("#" + this.selected_connection_properties_id);
				var tasks_menu_hide = $("#" + WF.jsPlumbContextMenu.main_tasks_menu_hide_obj_id);
				var parent = tasks_menu_hide.parent().closest(".taskflowchart");
				var is_connection_id_different = connection_id != selected_connection_properties.attr("connection_id");
				
				//if properties are fixed and hidden, do not do anything, otherwise it will mess the layout
				if (parent.is(".fixed_side_properties") && selected_connection_properties.is(".tasks_menu_hidden")) {
					if (is_connection_id_different)
						WF.jsPlumbContextMenu.toggleTasksMenuPanel(); //unhide connection properties
					else
						return false;
				}
				
				var is_selected_connection_properties_visible = this.isSelectedConnectionPropertiesOpen();
				
				//if properties are already visible, cancel previous connection properties
				if (is_selected_connection_properties_visible && selected_connection_properties.attr("connection_id") && is_connection_id_different) {
					var status = false;
					this.do_not_hide_connection_properties_popup = true; //Do not hide popup when calling saveConnectionProperties and cancelConnectionProperties methods
					
					if (WF.jsPlumbProperty.auto_save)
						status = WF.jsPlumbProperty.saveConnectionProperties(options);
					else 
						status = WF.jsPlumbProperty.cancelConnectionProperties();
					
					this.do_not_hide_connection_properties_popup = false;
					
					if (!status)
						return false;
				}
				
				//prepare properties html
				var source_id = connection.sourceId;
				//var target_id = connection.targetId;
				//var type = connection.connector.type;
			
				var source_type = $("#" + source_id).attr("type");
				/*var target_type = $("#" + target_id).attr("type");
			
				var label = connection.getOverlay("label").getLabel();
				label = label == "" || label == "..." ? WF.jsPlumbTaskFlow.default_label : label;
			
				var style = connection.getPaintStyle();
				var color = style.strokeStyle;
			
				var source_label = WF.jsPlumbTaskFlow.getTaskLabelByTaskId(source_id);
				var target_label = WF.jsPlumbTaskFlow.getTaskLabelByTaskId(target_id);
			
				var connection_type = connection.getParameter("connection_exit_type");
				var connection_overlay = connection.getParameter("connection_exit_overlay");
				*/
			
				/*var properties = ""
					+ "connection_id: " + connection_id + "\n<br/>"
					+ "connection: " + connection + "\n<br/>"
					+ "label: " + label + "\n<br/>"
					+ "color: " + color + "\n<br/>"
					+ "type: " + type + "\n<br/>"
					+ "source_label: " + source_label + "\n<br/>"
					+ "target_label: " + target_label + "\n<br/>"
					+ "source_type: " + source_type + "\n<br/>"
					+ "target_type: " + target_type + "\n<br/>"
					+ "source_id: " + source_id + "\n<br/>"
					+ "target_id: " + target_id + "\n<br/>"
					+ "endpoints: " + connection.endpoints + "\n<br/>"
					+ "endpoint from color: " + connection.endpoints[0].getPaintStyle().fillStyle + "\n<br/>"
					+ "endpoints to color: " + connection.endpoints[1].getPaintStyle().fillStyle + "\n<br/>"
					+ "endpoint from uuid: " + connection.endpoints[0].getUuid() + "\n<br/>"
					+ "endpoints to uuid: " + connection.endpoints[1].getUuid() + "\n<br/>"
				;*/
				var properties = "";
				//properties = "This connection links the task '" + source_label + "' to the task '" + target_label + "'.\n<br/>Connection type: " + connection_type + "\n<br/>Connection overlay: " + connection_overlay;
				properties += $("#" + WF.jsPlumbTaskFlow.main_connections_properties_obj_id + " .connection_properties_" +  source_type.toLowerCase()).html();
				
				selected_connection_properties.attr("connection_id", connection_id);
				selected_connection_properties.find(".properties_connection_id span").html(connection_id);
				selected_connection_properties.find(".content").html(properties);
				
				this.from_hide_selected_connection_properties_func = false;
				
				//toggle some classes
				var panels = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id + ", #" + WF.jsPlumbContextMenu.main_workflow_menu_obj_id + ", #" + WF.jsPlumbContextMenu.main_tasks_menu_obj_id + ", #" + WF.jsPlumbContextMenu.main_tasks_menu_hide_obj_id);
				panels.addClass("connection_properties_shown");
				
				var expand_collapse_icon = selected_connection_properties.find(" > .resize_properties_panel > .button");
				
				if (typeof options == "object" && options["remove_collapse"])
					this.removeCollapseSelectedConnectionProperties(expand_collapse_icon[0]);
				
				//load properties data
				this.loadConnectionProperties(connection_id);
				
				//show popup if not yet shown
				if (!is_selected_connection_properties_visible) {
					var resizable_connection_properties = WF.jsPlumbWorkFlowObjOptions && WF.jsPlumbWorkFlowObjOptions.hasOwnProperty("resizable_connection_properties") && WF.jsPlumbWorkFlowObjOptions.resizable_connection_properties;
					var is_fixed_props = panels.first().parent().closest(".taskflowchart").is(".fixed_properties, .fixed_side_properties");
					
					//reset popup draggable. Very important otherwise when we toggle between fixed_properties, fixed_side_properties and popup panels, the moveable draggable event is not init in some cases.
					if (!is_fixed_props || !resizable_connection_properties)
						WF.jsPlumbProperty.removePropertiesPanelDraggableEvent(selected_connection_properties);
					
					//if is no a fixed popup, remove the width and height styles that were set before by the fixed popup, so it can get the right dimensions with the new task properties content. This is very important, otherwise if we open a task properties as fixed popup and then toggle to non fixed popup and close it. When we open it again but with a Connection properties, it will have a predefined width, which will mess the popup dimensions, appearing the task properties content weirded and outside the popup.
					if (!is_fixed_props)
						selected_connection_properties.css({width: "", height: ""});
					
					//show popup
					MyFancyPopupClone.init({
						elementToShow : selected_connection_properties,
						onOpen() {
							if (resizable_connection_properties) {
								WF.jsPlumbProperty.resizePropertiesPanel(selected_connection_properties, selected_connection_properties.outerHeight());
							}
						},
						onClose : function() {
							if (!WF.jsPlumbProperty.from_hide_selected_connection_properties_func) {
								var status = true;
								
								if (WF.jsPlumbProperty.auto_save)
									status = WF.jsPlumbProperty.saveConnectionProperties();
								else 
									status = WF.jsPlumbProperty.cancelConnectionProperties();
								
								WF.jsPlumbProperty.from_hide_selected_connection_properties_func = false;
								
								if (!status) {
									panels.removeClass("connection_properties_shown");
									
									WF.jsPlumbProperty.removeCollapseSelectedConnectionProperties(expand_collapse_icon[0]);
								}
							}
							
							if (resizable_connection_properties) {
								WF.jsPlumbProperty.resizePropertiesPanel(selected_connection_properties, 0);
								WF.jsPlumbProperty.removeSelectedConnectionPropertiesCollapseClass(selected_connection_properties); //the resizePropertiesPanel add the collapsed_properties class
							}
						}
					});
					MyFancyPopupClone.showPopup({
						not_draggable: !resizable_connection_properties && is_fixed_props
					});
				}
			}
			return false;
		},
	
		hideSelectedConnectionProperties : function() {
			this.from_hide_selected_connection_properties_func = true; //when we call the hidePopup method, it will call the onClose from MyFancyPopupClone obj. So, to avoid calling the hideSelectedConnectionProperties again from cancelConnectionProperties or saveConnectionProperties, we set this flag to true and check it in the onClose method.
			
			if (!this.do_not_hide_connection_properties_popup) { //if this is true, it means that there was a connection previously opened, and now we open another connection properties. So we don't want to close the popup.
				MyFancyPopupClone.hidePopup();
				
				setTimeout(function() {
					WF.jsPlumbTaskFlow.zoomRefresh();
				}, 700); //must be in setTimeout, otherwise won't do anything
			}
			
			//reset html
			var selected_connection_properties = $("#" + this.selected_connection_properties_id);
			selected_connection_properties.find(".content").html("");
			
			//toggle some classes
			var panels = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id + ", #" + WF.jsPlumbContextMenu.main_workflow_menu_obj_id + ", #" + WF.jsPlumbContextMenu.main_tasks_menu_obj_id + ", #" + WF.jsPlumbContextMenu.main_tasks_menu_hide_obj_id);
			panels.removeClass("connection_properties_shown");
			
			this.removeCollapseSelectedConnectionProperties( selected_connection_properties.find(" > .resize_properties_panel > .button")[0] );
		},
		
		maximizeSelectedConnectionProperties : function(btn) {
			btn = $(btn);
			var selected_connection_properties = btn.parent().closest('.selected_connection_properties');
			selected_connection_properties.toggleClass("maximize_properties");
			
			var resizable_connection_properties = WF.jsPlumbWorkFlowObjOptions && WF.jsPlumbWorkFlowObjOptions.hasOwnProperty("resizable_connection_properties") && WF.jsPlumbWorkFlowObjOptions.resizable_connection_properties;
			
			if (resizable_connection_properties) {
				WF.jsPlumbProperty.resizePropertiesPanel(selected_connection_properties, selected_connection_properties.outerHeight());
				
				setTimeout(function() { //must be with settimeout with at least .5 sec, bc the selected_connection_properties has the css transition:.5s all;
					WF.jsPlumbProperty.resizePropertiesPanel(selected_connection_properties, selected_connection_properties.outerHeight());
				}, 600);
			}
			
			MyFancyPopupClone.updatePopup();
		},
		
		toggleSelectedConnectionPropertiesSide : function(btn) {
			var selected_connection_properties = $(btn).parent().closest('.selected_connection_properties');
			var resizable_connection_properties = WF.jsPlumbWorkFlowObjOptions && WF.jsPlumbWorkFlowObjOptions.hasOwnProperty("resizable_connection_properties") && WF.jsPlumbWorkFlowObjOptions.resizable_connection_properties;
			
			WF.jsPlumbProperty.togglePropertiesPanelSide(selected_connection_properties, resizable_connection_properties);
			WF.jsPlumbProperty.removeCollapseSelectedConnectionProperties(btn);
			
			WF.jsPlumbContextMenu.hideContextMenus();
		},
		
		collapseSelectedConnectionProperties : function(btn) {
			btn = $(btn);
			var selected_connection_properties = btn.parent().closest('.selected_connection_properties');
			selected_connection_properties.toggleClass("collapsed_properties");
			
			if (selected_connection_properties.hasClass("collapsed_properties"))
				this.addSelectedConnectionPropertiesCollapseClass(selected_connection_properties);
			else
				this.removeSelectedConnectionPropertiesCollapseClass(selected_connection_properties);
			
			var resizable_connection_properties = WF.jsPlumbWorkFlowObjOptions && WF.jsPlumbWorkFlowObjOptions.hasOwnProperty("resizable_connection_properties") && WF.jsPlumbWorkFlowObjOptions.resizable_connection_properties;
			
			if (resizable_connection_properties) {
				if (selected_connection_properties.hasClass("collapsed_properties"))
					WF.jsPlumbProperty.resizePropertiesPanel(selected_connection_properties, 30); //30 is the height of the selected_connection_properties when collapsed
				else
					setTimeout(function() { //must be with settimeout with at least .5 sec, bc the selected_connection_properties has the css transition:.5s all;
						WF.jsPlumbProperty.resizePropertiesPanel(selected_connection_properties, selected_connection_properties.outerHeight());
					}, 600);
			}
			
			MyFancyPopupClone.updatePopup();
		},
		
		removeCollapseSelectedConnectionProperties : function(btn) {
			this.removeSelectedConnectionPropertiesCollapseClass( $(btn).parent().closest('.selected_connection_properties') );
		},
		
		addSelectedConnectionPropertiesCollapseClass : function(selected_connection_properties) {
			selected_connection_properties.addClass("collapsed_properties");
			
			var panels = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id + ", #" + WF.jsPlumbContextMenu.main_workflow_menu_obj_id + ", #" + WF.jsPlumbContextMenu.main_tasks_menu_obj_id + ", #" + WF.jsPlumbContextMenu.main_tasks_menu_hide_obj_id);
			panels.addClass("connection_properties_collapsed");
		},
		
		removeSelectedConnectionPropertiesCollapseClass : function(selected_connection_properties) {
			selected_connection_properties.removeClass("collapsed_properties");
			
			var panels = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id + ", #" + WF.jsPlumbContextMenu.main_workflow_menu_obj_id + ", #" + WF.jsPlumbContextMenu.main_tasks_menu_obj_id + ", #" + WF.jsPlumbContextMenu.main_tasks_menu_hide_obj_id);
			panels.removeClass("task_connection_collapsed");
		},
		
		isSelectedTaskPropertiesOpen : function() {
			//return $("#" + this.selected_task_properties_id).is(":visible") && MyFancyPopupClone.isPopupOpened(); //Do not use :visible, bc if the tasksflow in inside of a tab and tht tab is not active, then the :visible will return false, even if the properties are open.
			return $("#" + this.selected_task_properties_id).css("display") != "none" && MyFancyPopupClone.isPopupOpened();
		},
		
		isSelectedTaskPropertiesWithTaskId : function(task_id) {
			return $("#" + this.selected_task_properties_id).attr("task_id") == task_id;
		},
		
		showSelectedTaskProperties : function(options) {
			if (typeof options != "object" || !options["do_not_call_hide_properties"])
				WF.jsPlumbContextMenu.hideContextMenus();
			
			var task_id = WF.jsPlumbContextMenu.getContextMenuTaskId();
			
			return this.showTaskProperties(task_id, {remove_collapse: true});
		},
		
		showTaskProperties : function(task_id, options) {
			if (task_id) {
				var main_tasks_properties_obj = $("#" + WF.jsPlumbTaskFlow.main_tasks_properties_obj_id);
				main_tasks_properties_obj.find(".task_properties").hide();
				
				var task = WF.jsPlumbTaskFlow.getTaskById(task_id);
				var task_type = task.attr("type");
				
				if (task_type) {
					var selected_task_properties = $("#" + this.selected_task_properties_id);
					var tasks_menu_hide = $("#" + WF.jsPlumbContextMenu.main_tasks_menu_hide_obj_id);
					var parent = tasks_menu_hide.parent().closest(".taskflowchart");
					var is_task_id_different = task_id != selected_task_properties.attr("task_id");
					
					//if properties are fixed and hidden, do not do anything, otherwise it will mess the layout
					if (parent.is(".fixed_side_properties") && selected_task_properties.is(".tasks_menu_hidden")) {
						if (is_task_id_different)
							WF.jsPlumbContextMenu.toggleTasksMenuPanel(); //unhide task properties
						else
							return false;
					}
					
					var is_selected_task_properties_visible = this.isSelectedTaskPropertiesOpen();
					
					//if properties are already visible, cancel previous task properties
					if (is_selected_task_properties_visible && selected_task_properties.attr("task_id") && is_task_id_different) {
						var status = false;
						this.do_not_hide_task_properties_popup = true; //Do not hide popup when calling saveTaskProperties and cancelTaskProperties methods
						
						if (WF.jsPlumbProperty.auto_save)
							status = WF.jsPlumbProperty.saveTaskProperties(options);
						else 
							status = WF.jsPlumbProperty.cancelTaskProperties();
						
						this.do_not_hide_task_properties_popup = false;
						
						if (!status)
							return false;
					}
					
					//prepare properties html
					task_properties_obj = main_tasks_properties_obj.find(".task_properties_" + task_type.toLowerCase());
					task_properties_obj.show();
					var properties = "";
					/*properties += "task_id: " + task_id + "\n<br/>"
							+ "task_type: " + task_type + "\n<br/>";
					*/
					properties += task_properties_obj.html();
					
					var task_tag = task.attr("tag");
					var properties_task_id = selected_task_properties.find(".properties_task_id");
					
					properties_task_id.find("span.task_id").html(task_id);
					properties_task_id.find("span.task_tag").html(task_tag);
					
					selected_task_properties.find(".title").attr("title", properties_task_id.text());
					selected_task_properties.attr("task_id", task_id);
					selected_task_properties.find(".content").html(properties);
					
					this.from_hide_selected_task_properties_func = false;
					
					//toggle some classes
					var panels = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id + ", #" + WF.jsPlumbContextMenu.main_workflow_menu_obj_id + ", #" + WF.jsPlumbContextMenu.main_tasks_menu_obj_id + ", #" + WF.jsPlumbContextMenu.main_tasks_menu_hide_obj_id);
					panels.addClass("task_properties_shown");
					
					var expand_collapse_icon = selected_task_properties.find(" > .resize_properties_panel > .button");
					
					if (typeof options == "object" && options["remove_collapse"])
						this.removeCollapseSelectedTaskProperties(expand_collapse_icon[0]);
					
					//load properties data
					this.loadTaskProperties(task_id);
					
					//show popup if not yet shown
					if (!is_selected_task_properties_visible) {
						var resizable_task_properties = WF.jsPlumbWorkFlowObjOptions && WF.jsPlumbWorkFlowObjOptions.hasOwnProperty("resizable_task_properties") && WF.jsPlumbWorkFlowObjOptions.resizable_task_properties;
						var is_fixed_props = panels.first().parent().closest(".taskflowchart").is(".fixed_properties, .fixed_side_properties");
						
						//reset popup draggable. Very important otherwise when we toggle between fixed_properties, fixed_side_properties and popup panels, the moveable draggable event is not init in some cases.
						if (!is_fixed_props || !resizable_task_properties)
							WF.jsPlumbProperty.removePropertiesPanelDraggableEvent(selected_task_properties);
						
						//if is no a fixed popup, remove the width and height styles that were set before by the fixed popup, so it can get the right dimensions with the new task properties content. This is very important, otherwise if we open a task properties as fixed popup and then toggle to non fixed popup and close it. When we open it again but with a Connection properties, it will have a predefined width, which will mess the popup dimensions, appearing the task properties content weirded and outside the popup.
						if (!is_fixed_props)
							selected_task_properties.css({width: "", height: ""});
						
						//show popup
						MyFancyPopupClone.init({
							elementToShow : selected_task_properties,
							onOpen() {
								if (resizable_task_properties)
									WF.jsPlumbProperty.resizePropertiesPanel(selected_task_properties, selected_task_properties.outerHeight());
							},
							onClose : function() {
								if (!WF.jsPlumbProperty.from_hide_selected_task_properties_func) {
									var status = true;
									
									if (WF.jsPlumbProperty.auto_save)
										status = WF.jsPlumbProperty.saveTaskProperties();
									else 
										status = WF.jsPlumbProperty.cancelTaskProperties();
									
									WF.jsPlumbProperty.from_hide_selected_task_properties_func = false;
									
									if (!status) {
										panels.removeClass("task_properties_shown");
										
										WF.jsPlumbProperty.removeCollapseSelectedTaskProperties(expand_collapse_icon[0]);
									}
								}
								
								if (resizable_task_properties) {
									WF.jsPlumbProperty.resizePropertiesPanel(selected_task_properties, 0);
									WF.jsPlumbProperty.removeSelectedTaskPropertiesCollapseClass(selected_task_properties); //the resizePropertiesPanel add the collapsed_properties class
								}
							}
						});
						
						MyFancyPopupClone.showPopup({
							not_draggable: !resizable_task_properties && is_fixed_props
						});
					}
				}
			}
			return false;
		},
		
		hideSelectedTaskProperties : function() {
			this.from_hide_selected_task_properties_func = true; //when we call the hidePopup method, it will call the onClose from MyFancyPopupClone obj. So, to avoid calling the hideSelectedTaskProperties again from cancelTaskProperties or saveTaskProperties, we set this flag to true and check it in the onClose method.
			
			if (!this.do_not_hide_task_properties_popup) { //if this is true, it means that there was a task previously opened, and now we open another task properties. So we don't want to close the popup.
				MyFancyPopupClone.hidePopup();
				
				setTimeout(function() {
					WF.jsPlumbTaskFlow.zoomRefresh();
				}, 700); //must be in setTimeout, otherwise won't do anything
			}
			
			//reset html
			var selected_task_properties = $("#" + this.selected_task_properties_id);
			selected_task_properties.find(".content").html("");
			
			//toggle some classes
			var panels = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id + ", #" + WF.jsPlumbContextMenu.main_workflow_menu_obj_id + ", #" + WF.jsPlumbContextMenu.main_tasks_menu_obj_id + ", #" + WF.jsPlumbContextMenu.main_tasks_menu_hide_obj_id);
			panels.removeClass("task_properties_shown");
			
			this.removeCollapseSelectedTaskProperties( selected_task_properties.find(" > .resize_properties_panel > .button")[0] );
		},
		
		maximizeSelectedTaskProperties : function(btn) {
			btn = $(btn);
			var selected_task_properties = btn.parent().closest('.selected_task_properties');
			selected_task_properties.toggleClass("maximize_properties");
			
			var resizable_task_properties = WF.jsPlumbWorkFlowObjOptions && WF.jsPlumbWorkFlowObjOptions.hasOwnProperty("resizable_task_properties") && WF.jsPlumbWorkFlowObjOptions.resizable_task_properties;
			
			if (resizable_task_properties) {
				WF.jsPlumbProperty.resizePropertiesPanel(selected_task_properties, selected_task_properties.outerHeight());
				
				setTimeout(function() { //must be with settimeout with at least .5 sec, bc the selected_task_properties has the css transition:.5s all;
					WF.jsPlumbProperty.resizePropertiesPanel(selected_task_properties, selected_task_properties.outerHeight());
				}, 600);
			}
			
			MyFancyPopupClone.updatePopup();
		},
		
		toggleSelectedTaskPropertiesSide : function(btn) {
			var selected_task_properties = $(btn).parent().closest('.selected_task_properties');
			var resizable_task_properties = WF.jsPlumbWorkFlowObjOptions && WF.jsPlumbWorkFlowObjOptions.hasOwnProperty("resizable_task_properties") && WF.jsPlumbWorkFlowObjOptions.resizable_task_properties;
			
			WF.jsPlumbProperty.togglePropertiesPanelSide(selected_task_properties, resizable_task_properties);
			WF.jsPlumbProperty.removeCollapseSelectedTaskProperties(btn);
			
			WF.jsPlumbContextMenu.hideContextMenus();
		},
		
		collapseSelectedTaskProperties : function(btn) {
			btn = $(btn);
			var selected_task_properties = btn.parent().closest('.selected_task_properties');
			selected_task_properties.toggleClass("collapsed_properties");
			
			if (selected_task_properties.hasClass("collapsed_properties"))
				this.addSelectedTaskPropertiesCollapseClass(selected_task_properties);
			else
				this.removeSelectedTaskPropertiesCollapseClass(selected_task_properties);
			
			var resizable_task_properties = WF.jsPlumbWorkFlowObjOptions && WF.jsPlumbWorkFlowObjOptions.hasOwnProperty("resizable_task_properties") && WF.jsPlumbWorkFlowObjOptions.resizable_task_properties;
			
			if (resizable_task_properties) {
				if (selected_task_properties.hasClass("collapsed_properties"))
					WF.jsPlumbProperty.resizePropertiesPanel(selected_task_properties, 30); //30 is the height of the selected_task_properties when collapsed
				else
					setTimeout(function() { //must be with settimeout with at least .5 sec, bc the selected_task_properties has the css transition:.5s all;
						WF.jsPlumbProperty.resizePropertiesPanel(selected_task_properties, selected_task_properties.outerHeight());
					}, 600);
			}
			
			MyFancyPopupClone.updatePopup();
		},
		
		removeCollapseSelectedTaskProperties : function(btn) {
			this.removeSelectedTaskPropertiesCollapseClass( $(btn).parent().closest('.selected_task_properties') );
		},
		
		addSelectedTaskPropertiesCollapseClass : function(selected_task_properties) {
			selected_task_properties.addClass("collapsed_properties");
			
			var panels = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id + ", #" + WF.jsPlumbContextMenu.main_workflow_menu_obj_id + ", #" + WF.jsPlumbContextMenu.main_tasks_menu_obj_id + ", #" + WF.jsPlumbContextMenu.main_tasks_menu_hide_obj_id);
			panels.addClass("task_properties_collapsed");
		},
		
		removeSelectedTaskPropertiesCollapseClass : function(selected_task_properties) {
			selected_task_properties.removeClass("collapsed_properties");
			
			var panels = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id + ", #" + WF.jsPlumbContextMenu.main_workflow_menu_obj_id + ", #" + WF.jsPlumbContextMenu.main_tasks_menu_obj_id + ", #" + WF.jsPlumbContextMenu.main_tasks_menu_hide_obj_id);
			panels.removeClass("task_properties_collapsed");
		},
		/* END: INTERFACE METHODS */
	
		/* START: ACTION METHODS */
		setPropertyFieldValue : function(j_field, property_value) {
			var node_name = j_field[0].nodeName.toLowerCase();
		
			if (node_name == "input" || node_name == "select" || node_name == "textarea") {
				if (j_field.attr("type") == "checkbox" || j_field.attr("type") == "radio") {
					if (j_field.val() == property_value) 
						j_field.attr("checked", "checked").prop("checked", true);
					else 
						j_field.removeAttr("checked").prop("checked", false);
				}
				else if (node_name == "input" && property_value) {
					//j_field.val( ("" + property_value).replace(/"/g, "&quot;") );//It doesnt need the &quot; bc the .val() already takes care of this.
					j_field.val(property_value);
				}
				else
					j_field.val(property_value);
			}
			else
				j_field.html(property_value);
		},
	
		setPropertiesFromHtmlElm : function(properties_elm, property_class_name, saved_properties_elm) {
			if (saved_properties_elm) {
				var property_fields = $(properties_elm).find("." + property_class_name);
				var length = property_fields.length;
			
				for (var i = 0; i < length; i++) {
					var field = property_fields[i];
					var j_field = $(field);
					
					var property_name = j_field.attr("property_name");
					if (!property_name)
						property_name = j_field.attr("name");
				
					if (saved_properties_elm.hasOwnProperty(property_name)) {
						var property_value = saved_properties_elm[property_name];
					
						this.setPropertyFieldValue(j_field, property_value);
					}
				}
			}
			
			return true;
		},
	
		getPropertyFieldValue : function(j_field) {
			var node_name = j_field[0].nodeName.toLowerCase();
		
			var property_value = null;
			if (node_name == "input" || node_name == "select" || node_name == "textarea") {
				if (j_field.attr("type") == "checkbox" || j_field.attr("type") == "radio") {
					if (j_field.is(":checked"))
						property_value = j_field[0].hasAttribute("value") ? j_field.val() : true;
					else
						property_value = "";//This should be empty and not null otherwise it messes the values from the checkboxes/radiobuttons when the field name is an array with numeric keys.
				}
				else if (node_name == "select") {
					var v = j_field.val();
					property_value = v == null ? "" : v; //v can be null if the select field does not have any options.
				}
				else
					property_value = j_field.val();
			}
			else if (j_field[0].hasAttribute("value"))
				property_value = j_field.attr("value");
			else
				property_value = j_field.html();
		
			return property_value;
		},
	
		getPropertiesQueryStringFromHtmlElm : function(properties_elm, property_class_name) {
			var query_string = "";
		
			var property_fields = $(properties_elm).find("." + property_class_name);
			var length = property_fields.length;
			
			for (var i = 0; i < length; i++) {
				var field = property_fields[i];
				var j_field = $(field);
				//console.log(field);
	
				var property_name = j_field.attr("property_name");
				if (!property_name)
					property_name = j_field.attr("name");
				
				if (property_name) { //it could be undefined or null or 0, which means the parse_str will give an exception
					var property_value = this.getPropertyFieldValue(j_field);
					property_value = typeof property_value == "undefined" ? "" : property_value; //otherwise the parse_str will give an error. This can be undefined if is a select and there are no options available
					
					//query_string += (i > 0 ? "&" : "") + escape(property_name) + "=" + escape(property_value);
					query_string += (i > 0 ? "&" : "") + encodeURIComponent(property_name) + "=" + encodeURIComponent(property_value);
				}
			}
			
			return query_string;
		},
	
		/* START: ACTION CONNECTION METHODS */
		loadConnectionProperties : function(connection_id) {
			return this.loadConnectionPropertiesFromHtmlElm( $("#" + this.selected_connection_properties_id), connection_id);
		},
	
		loadConnectionPropertiesFromHtmlElm : function(properties_elm, connection_id) {
			var connection = WF.jsPlumbTaskFlow.getConnection(connection_id);
		
			if (connection) {
				if (!WF.jsPlumbTaskFlow.connections_properties[connection_id]) {
					WF.jsPlumbTaskFlow.connections_properties[connection_id] = {};
				}
		
				this.setPropertiesFromHtmlElm(properties_elm, "connection_property_field", WF.jsPlumbTaskFlow.connections_properties[connection_id]);
			
				var source_id = connection.sourceId;
				var task_type = $("#" + source_id).attr("type");
			
				this.callTaskSettingsCallback(task_type, "on_load_connection_properties", [ properties_elm, connection, WF.jsPlumbTaskFlow.connections_properties[connection_id] ]);
			}
		
			return true;
		},
		
		saveConnectionProperties : function(options) {
			var selected_connection_properties = $("#" + this.selected_connection_properties_id);
			var connection_id = selected_connection_properties.attr("connection_id");
			
			var connection = WF.jsPlumbTaskFlow.getConnection(connection_id);
			var status = false;
			
			if (connection) {
				var source_id = connection.sourceId;
				var task_type = $("#" + source_id).attr("type");
				
				status = this.callTaskSettingsCallback(task_type, "on_submit_connection_properties", [ selected_connection_properties, connection, WF.jsPlumbTaskFlow.connections_properties[connection_id] ]);
				
				if (status) {
					this.saveConnectionPropertiesFromHtmlElm(selected_connection_properties, connection_id);
					
					if (typeof options != "object" || !options["do_not_call_hide_properties"]) //we use this in the edit_php_code when we want to auto_save the opened properties but leave the popup open if it was already opened.
						this.hideSelectedConnectionProperties();
				}
				
				this.callTaskSettingsCallback(task_type, "on_complete_connection_properties", [ selected_connection_properties, connection, WF.jsPlumbTaskFlow.connections_properties[connection_id], status ]);
			}
			
			return status;
		},
	
		saveConnectionPropertiesFromHtmlElm : function(properties_elm, connection_id) {
			WF.jsPlumbTaskFlow.connections_properties[connection_id] = {};
	
			var query_string = this.getPropertiesQueryStringFromHtmlElm(properties_elm, "connection_property_field");
			parse_str(query_string, WF.jsPlumbTaskFlow.connections_properties[connection_id]);
	
			//console.log(WF.jsPlumbTaskFlow.tasks_properties[task_id]);
		},
		
		cancelConnectionProperties : function() {
			var selected_connection_properties = $("#" + this.selected_connection_properties_id);
			var connection_id = selected_connection_properties.attr("connection_id");
		
			var connection = WF.jsPlumbTaskFlow.getConnection(connection_id);
		
			var status = true;
		
			if (connection) {
				var task_type = $("#" + connection.sourceId).attr("type");
			
				status = this.callTaskSettingsCallback(task_type, "on_cancel_connection_properties", [ selected_connection_properties, connection, WF.jsPlumbTaskFlow.connections_properties[connection_id] ]);
			}
		
			if (status)
				this.hideSelectedConnectionProperties();
			
			return status;
		},
		/* END: ACTION CONNECTION METHODS */
	
		/* START: ACTION TASK METHODS */
		loadTaskProperties : function(task_id) {
			return this.loadTaskPropertiesFromHtmlElm( $("#" + this.selected_task_properties_id), task_id);
		},
	
		loadTaskPropertiesFromHtmlElm : function(properties_elm, task_id) {
			if (!WF.jsPlumbTaskFlow.tasks_properties[task_id])
				WF.jsPlumbTaskFlow.tasks_properties[task_id] = {};
			
			this.setPropertiesFromHtmlElm(properties_elm, "task_property_field", WF.jsPlumbTaskFlow.tasks_properties[task_id]);
		
			var task = WF.jsPlumbTaskFlow.getTaskById(task_id);
			var task_type = task.attr("type");
			
			this.callTaskSettingsCallback(task_type, "on_load_task_properties", [ properties_elm, task_id, WF.jsPlumbTaskFlow.tasks_properties[task_id] ]);
		
			return true;
		},
		
		saveTaskProperties : function(options) {
			var selected_task_properties = $("#" + this.selected_task_properties_id);
			var task_id = selected_task_properties.attr("task_id");
			var task = WF.jsPlumbTaskFlow.getTaskById(task_id);
			var task_type = task.attr("type");
		
			var status = this.continueSavingTaskProperties(task_id, selected_task_properties);
		
			if (status) {
				status = this.callTaskSettingsCallback(task_type, "on_submit_task_properties", [ selected_task_properties, task_id, WF.jsPlumbTaskFlow.tasks_properties[task_id] ]);
				
				if (status) {
					this.saveTaskPropertiesFromHtmlElm(selected_task_properties, task_id);
					WF.jsPlumbTaskFlow.centerTaskInnerElements(task_id);
					
					if (typeof options != "object" || !options["do_not_call_hide_properties"]) //we use this in the edit_php_code when we want to auto_save the opened properties but leave the popup open if it was already opened.
						this.hideSelectedTaskProperties();
				}
				
				this.callTaskSettingsCallback(task_type, "on_complete_task_properties", [ selected_task_properties, task_id, WF.jsPlumbTaskFlow.tasks_properties[task_id], status ]);
			}
			
			return status;
		},
	
		saveTaskPropertiesFromHtmlElm : function(properties_elm, task_id) {
			var exits = WF.jsPlumbTaskFlow.tasks_properties[task_id] ? WF.jsPlumbTaskFlow.tasks_properties[task_id]["exits"] : null;
			
			WF.jsPlumbTaskFlow.tasks_properties[task_id] = {};
			WF.jsPlumbTaskFlow.tasks_properties[task_id]["exits"] = exits;
			
			this.saveTaskPropertiesExits(task_id, properties_elm);
			
			var query_string = this.getPropertiesQueryStringFromHtmlElm(properties_elm, "task_property_field");
			
			try {
				parse_str(query_string, WF.jsPlumbTaskFlow.tasks_properties[task_id]);
			}
			catch(e) {
				//alert(e);
				if (console && console.log) {
					console.log(e);
					console.log("Error executing parse_str function with query_string: " + query_string);
				}
			}
			
			//console.log(WF.jsPlumbTaskFlow.tasks_properties[task_id]);
		},
	
		//Checks if there is any connection that was deleted and ask the user if he really wants to continue.
		continueSavingTaskProperties : function(task_id, task_properties_html_element) {
			var new_exit_ids = new Array();
		
			var task_property_exits = $(task_properties_html_element).find(".task_property_exit");
			var length = task_property_exits.length;
			for (var i = 0; i < length; i++) {
				var exit = task_property_exits[i];
		
				if (exit.hasAttribute("exit_id")) {
					var j_exit = $(exit);
					var exit_id = j_exit.attr("exit_id");
					new_exit_ids.push(exit_id);
				}
			}
		
			var exits = WF.jsPlumbTaskFlow.tasks_properties[task_id] && WF.jsPlumbTaskFlow.tasks_properties[task_id].exits; //if user empties the diagram and the properties popup is open and user clicks the save button, it will give an error bc the WF.jsPlumbTaskFlow.tasks_properties[task_id] was deleted when the user emptied the diagram. So we must do this check, to avoid the javascript error.
			if (exits) {
				for (var exit_id in exits) {
					if (jQuery.inArray(exit_id, new_exit_ids) == -1) {
						return window.confirm("By saving this new settings you may delete some connections that you already made.\nDo you want to continue?");
					}
				}
			}
		
			return true;
		},
	
		saveTaskPropertiesExits : function(task_id, task_properties_html_element) {
			var old_exit_ids = new Array();
			var new_exit_ids = new Array();
			var new_exits = new Array();
			var exits_to_add = new Array();
			var exit_ids_to_delete = new Array();
			var new_task_properties_exits = {};
			var existent_changed_exits = {};
			var sorted_task_properties_exits = {};
			
			var j_task = WF.jsPlumbTaskFlow.getTaskById(task_id);
			
			//GETTING ALL THE EXITS FROM HTML
			var task_property_exits = $(task_properties_html_element).find(".task_property_exit");
			//console.log(task_property_exits);
			var length = task_property_exits.length;
			for (var i = 0; i < length; i++) {
				var exit = task_property_exits[i];
			
				if (exit.hasAttribute("exit_id")) {
					var j_exit = $(exit);
					var exit_id = j_exit.attr("exit_id");
					var exit_color = j_exit.attr("exit_color");
					var exit_type = j_exit.attr("exit_type");
					var exit_overlay = j_exit.attr("exit_overlay");
					var exit_label = j_exit.attr("exit_label");
					var on_source_connection_color = j_exit.attr("on_source_connection_color");
					
					new_exit_ids.push(exit_id);
					new_exits.push({id:exit_id, color:exit_color, type:exit_type, overlay:exit_overlay, label:exit_label, on_source_connection_color:on_source_connection_color});
				}
			}
			
			//PREPARING OLD EXITS TO DELETE
			var exits = WF.jsPlumbTaskFlow.tasks_properties[task_id].exits;
			if (exits) {
				for (var exit_id in exits) {
					var exit = exits[exit_id];
				
					old_exit_ids.push(exit_id);
					
					if (jQuery.inArray(exit_id, new_exit_ids) == -1) {
						exit_ids_to_delete.push(exit_id);
					}
					else {
						new_task_properties_exits[exit_id] = exit;
					}
				}
			}
		
			//ADDING NEW EXITS AND UPDATING THE EXISTENT EXITS
			for (var i in new_exit_ids) {
				var new_exit = new_exits[i];
				var new_exit_id = new_exit_ids[i];
				
				if (jQuery.inArray(new_exit_id, old_exit_ids) == -1) {
					if (!new_exit.color) {
						new_exit.color = nextColor();
					}
					
					exits_to_add.push(new_exit);
					
					new_task_properties_exits[new_exit_id] = {
						color : new_exit.color, 
						type : new_exit.type ? new_exit.type : null, 
						overlay : new_exit.overlay ? new_exit.overlay : null,
						label : new_exit.label ? new_exit.label : null,
					};
				}
				else {
					var old_exit = exits[new_exit_id];
					old_exit = old_exit ? old_exit : {};
					
					var new_exit_with_only_the_new_changes = {};
					var changed = false;
					for (var attr in new_exit) {
						if (attr != "id" && new_exit[attr] && new_exit[attr] != old_exit[attr]) {
							old_exit[attr] = new_exit[attr];
							new_exit_with_only_the_new_changes[attr] = new_exit[attr];
							changed = true;
						}
					}
					
					if (changed) {
						new_task_properties_exits[new_exit_id] = old_exit;
						existent_changed_exits[new_exit_id] = new_exit_with_only_the_new_changes;
					}
				}
			}
			
			//console.log(new_task_properties_exits);
			//console.log(exits_to_add);
			//console.log(exit_ids_to_delete);
		
			//PREPARING CONNECTIONS
			var connections = WF.jsPlumbTaskFlow.getSourceConnections(task_id);
			var connections_by_id = {};
			for (var i = 0; i < connections.length; i++) {
				var exit_id = connections[i].getParameter("connection_exit_id");
				
				if (exit_id) {
					if (!connections_by_id[exit_id]) {
						connections_by_id[exit_id] = new Array();
					}
				
					connections_by_id[exit_id].push(connections[i]);
				}
			}
		
			//DELETING OLD EXITS
			if (exit_ids_to_delete.length > 0) {
				for (var i = 0; i < exit_ids_to_delete.length; i++) {
					var exit_id = exit_ids_to_delete[i];
					var connections = connections_by_id[exit_id];
					
					if (connections) {
						for (var j = 0; j < connections.length; j++) {
							var connection = connections[j];
						
							if (connection) {
								WF.jsPlumbTaskFlow.deleteConnection(connection.id, true);
							}
						}
					}
					
					var j_exit_selector = j_task.find(" > ." + WF.jsPlumbTaskFlow.task_eps_class_name + " ." + WF.jsPlumbTaskFlow.task_ep_class_name + "[connection_exit_id=\"" + exit_id + "\"]");
				
					if (j_exit_selector[0]) {
						try {
							ExternalLibClone.unmakeSource(j_exit_selector);
						} catch(e) {
							//console.log(e);
						}
			
						j_exit_selector.remove();
					}
				}
			}
		
			//ADDING NEW EXITS
			for (var i in exits_to_add) {
				var exit = exits_to_add[i];
			
				//console.log([task_id, exit]);
				WF.jsPlumbTaskFlow.addExtraSourceElementToTask(task_id, exit);
			}
			
			//CHANGING EXISTENT EXITS
			for (var exit_id in existent_changed_exits) {
				var exit = existent_changed_exits[exit_id];
				var connections = connections_by_id[exit_id];
				
				if (connections) {
					//CHANGING COLOR/TYPe/OVERLAY FOR THE CONNECTIONs
					for (var i = 0; i < connections.length; i++) {
						var connection = connections[i];
					
						if (connection) {
							//preparing color
							if (exit["color"])
								WF.jsPlumbTaskFlow.changeConnectionColor(connection, exit["color"]);
							
							//preparing type ad overlay
							if (exit["type"]) {
								if (exit["overlay"])
									connection.setParameter("connection_exit_overlay", exit["overlay"]);
					
								WF.jsPlumbTaskFlow.changeConnectionConnectorType(connection, exit["type"]);
							}
							else if (exit["overlay"]) 
								WF.jsPlumbTaskFlow.changeConnectionOverlayType(connection, exit["overlay"]);
						}
					}
				}
				
				var j_exit_selector = j_task.find(" > ." + WF.jsPlumbTaskFlow.task_eps_class_name + " ." + WF.jsPlumbTaskFlow.task_ep_class_name + "[connection_exit_id=\"" + exit_id + "\"]").first();
				
				if (j_exit_selector[0]) {
					//CHANGING COLOR FOR THE EPs AND TASK BORDER:HOVER
					if (exit["color"]) {
						j_exit_selector.attr("connection_exit_color", exit["color"]);
						j_exit_selector.css({"border-color": exit["color"], "background-color": exit["color"]});
						j_task.css({"border-color" : exit["color"]});
					} 
					
					//CHANGING LABEL FOR THE EPs
					if (exit["label"]) {
						j_exit_selector.attr("connection_exit_label", exit["label"]);
					}
				}
			}
			
			//SORTING EXITS ACCORDING THE HTML
			var previous_ep = null;
			var length = task_property_exits.length;
			for (var i = 0; i < length; i++) {
				var j_exit = $(task_property_exits[i]);
				var exit_id = j_exit.attr("exit_id");
				
				if (exit_id && new_task_properties_exits.hasOwnProperty(exit_id)) {
					sorted_task_properties_exits[exit_id] = new_task_properties_exits[exit_id];
					
					//SORTING EPs IN TASK
					var ep = j_task.find(" > ." + WF.jsPlumbTaskFlow.task_eps_class_name + " ." + WF.jsPlumbTaskFlow.task_ep_class_name + "[connection_exit_id=\"" + exit_id + "\"]").first();
					if (i > 0) {
						ep.insertAfter(previous_ep);
					}
					previous_ep = ep;
				}
			}
		
			//SETTING NEW TASK_PROPERTIES_EXITS
			//console.log(WF.jsPlumbTaskFlow.tasks_properties[task_id].exits);
			WF.jsPlumbTaskFlow.tasks_properties[task_id].exits = sorted_task_properties_exits;
			//console.log(WF.jsPlumbTaskFlow.tasks_properties[task_id].exits);
		
			//GIVING A BORDER COLOR TO THE CORRESPONDENT TASK ACCORDING WITH THE FIRST EXIT COLOR
			if (exits_to_add.length > 0) {
				for (var exit_id in new_task_properties_exits) {
					var color = new_task_properties_exits[exit_id].color;
				
					var task = WF.jsPlumbTaskFlow.getTaskById(task_id);
					task.css({"border-color" : color});
					break;
				}
			}
		},
		
		cancelTaskProperties : function() {
			var selected_task_properties = $("#" + this.selected_task_properties_id);
			var task_id = selected_task_properties.attr("task_id");
			var task = WF.jsPlumbTaskFlow.getTaskById(task_id);
			var task_type = task.attr("type");
		
			var status = this.callTaskSettingsCallback(task_type, "on_cancel_task_properties", [ selected_task_properties, task_id, WF.jsPlumbTaskFlow.tasks_properties[task_id] ]);
		
			if (status)
				this.hideSelectedTaskProperties();
			
			return status;
		},
		/* END: ACTION TASK METHODS */
		/* END: ACTION METHODS */
	};

	WF.jsPlumbContextMenu = { 
		main_workflow_menu_obj_id : "workflow_menu",
		main_tasks_menu_obj_id : "tasks_menu",
		main_tasks_menu_hide_obj_id : "tasks_menu_hide",
		task_menu_class_name : "task",
		tasks_group_label_class_name : "tasks_group_label",
		tasks_group_tasks_class_name : "tasks_group_tasks",
	
		task_menu_obj_selector : null, 
		
		task_context_menu_id : "task_context_menu",
		task_context_menu_classes : "task_context_menu",
		connection_context_menu_id : "connection_context_menu",
		connection_context_menu_classes : "connection_context_menu",
		connection_add_new_task_panel_id : "connection_add_new_task_panel",
		
		task_context_menu_last_event_target_id : null,
		connection_context_menu_last_event_target_id : null,
		
		task_html : '<div class="#task_class_name# task_#task_type# task_#task_tag# #start_task_class_name#" id="#task_id#" type="#task_type#" tag="#task_tag#" is_start_task="#is_start_task#" is_resizable_task="#is_resizable_task#" title="#task_title#">'
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
				+ '</div>',
		task_exit_html : '<div class="#task_ep_class_name#"></div>',
		
		/* START: INIT METHODS */
		init : function() {
			var hash = Math.abs(WF.jsPlumbTaskFlow.main_tasks_flow_obj_id.hashCode());
			this.task_context_menu_id += "_" + hash;
			this.connection_context_menu_id += "_" + hash;
			this.connection_add_new_task_panel_id += "_" + hash;
			
			if (!this.task_menu_obj_selector) {
				this.task_menu_obj_selector = "#" + this.main_tasks_menu_obj_id + " ." + this.task_menu_class_name;
			}
			
			$("#" + this.main_tasks_menu_obj_id).addClass("jsplumbtaskmenu");
		
			this.initMenuTasks();
		
			if (this.isContextMenuActive()) {
			  	this.initTaskContextMenuSettings();
			  	this.initConnectionContextMenuSettings();
				this.initTaskContextMenu();
				this.initConnectionContextMenu();
				this.initConnectionAddNewTaskPanel();
			}
		
			this.initTasksGroups();
			this.initTasksClones();
			
			//prepare main_tasks_menu_hide_obj_id to be resixable
			var tasks_flow = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id);
			var tasks_menu = $("#" + this.main_tasks_menu_obj_id);
			var tasks_menu_hide = $("#" + this.main_tasks_menu_hide_obj_id);
			var parent = tasks_menu_hide.parent().closest(".taskflowchart");
			var selected_task_properties = null;
			var selected_connection_properties = null;
			var parent_extra = null;
			
			tasks_menu_hide.draggable({
				axis: "x",
				appendTo: 'body',
				cursor: 'move',
		          tolerance: 'pointer',
		          cancel: ' > .button', //button is inside of main_tasks_menu_hide_obj_id
		          containment: parent,
				cursor: 'move',
				start: function(event, ui) {
					if (!tasks_menu_hide.hasClass("tasks_menu_hidden")) {
						tasks_menu.addClass("resizing");
						tasks_menu_hide.addClass("resizing");
						tasks_flow.addClass("resizing");
						
						if (parent.hasClass("fixed_side_properties")) {
							selected_task_properties = $("#" + WF.jsPlumbProperty.selected_task_properties_id);
							selected_connection_properties = $("#" + WF.jsPlumbProperty.selected_connection_properties_id);
							
							selected_task_properties.addClass("resizing");
							selected_connection_properties.addClass("resizing");
						}
						
						var parent_position = ("" + parent.css("position")).toLowerCase();
						parent_extra = parent_position == "relative" || parent_position == "absolute" ? parent.offset().left : 0;
						
						return true;
					}
					
					return false;
				},
				drag : function(event, ui) {
					//Note that any change here must be replicated in the WF.jsPlumbProperty.updateFixedSidePropertiesPanelWidth method
					if (parent.hasClass("reverse")) {
						var w = $(window).width() - (ui.offset.left - $(window).scrollLeft()) - parent_extra;
						var tasks_menu_w = (w - tasks_menu_hide.outerWidth());
						
						tasks_menu.css("width", tasks_menu_w + "px");
						tasks_flow.css("right", w + "px");
						
						if (parent.hasClass("fixed_side_properties")) {
							selected_task_properties.css("width", tasks_menu_w + "px");
							selected_connection_properties.css("width", tasks_menu_w + "px");
						}
					}
					else {
						var w = ui.offset.left - parent_extra;
						
						tasks_menu.css("width", w + "px");
						tasks_flow.css("left", (w + tasks_menu_hide.outerWidth()) + "px");
						
						if (parent.hasClass("fixed_side_properties")) {
							selected_task_properties.css("width", w + "px");
							selected_connection_properties.css("width", w + "px");
						}
					}
				},
				stop : function(event, ui) {
					tasks_menu.removeClass("resizing");
					tasks_menu_hide.removeClass("resizing");
					tasks_flow.removeClass("resizing");
					
					if (parent.hasClass("fixed_side_properties")) {
						selected_task_properties.removeClass("resizing");
						selected_connection_properties.removeClass("resizing");
					}
					
					if (parent.hasClass("reverse")) {
						var w = $(window).width() - (ui.offset.left - $(window).scrollLeft()) - parent_extra;
						
						tasks_menu_hide.css({
							width: "",
							top: "",
							left: "",
							right: (w - tasks_menu_hide.outerWidth()) + "px",
						});
					}
					else
						tasks_menu_hide.css({
							width: "",
							top: "",
							right: "",
						});
					
					//reload menu tasks
					WF.jsPlumbContextMenu.sortTasksMenu();
					
					setTimeout(function() {
						WF.jsPlumbTaskFlow.zoomRefresh();
					}, 700); //must be in setTimeout, otherwise won't do anything
				}
			});
		},
	
		initMenuTasks : function() {
			var elms = $(this.task_menu_obj_selector);
			var main_tasks_menu_obj = $("#" + WF.jsPlumbContextMenu.main_tasks_menu_obj_id);
			var main_tasks_flow_obj = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id);
			
			for (var i = 0; i < elms.length; i++) {
				$(elms[i]).css("position", "relative");
				
				ExternalLibClone.draggable(elms[i], {
					scroll: true,
					scrollSensitivity: 20,
					
					start: function(e, obj) {
						var j_elm = obj.helper;
						var elm = j_elm[0];
						
						if (!j_elm.data("start_dragging")) {
							j_elm.data("start_dragging", 1);
							
							//No need to do this, since we add the jqueryui-1.11.4
							//j_elm.data("top_offset", obj.offset.top);
							//j_elm.data("left_offset", obj.offset.left);
							
							j_elm.data("top_css", j_elm.css("top"));
							j_elm.data("left_css", j_elm.css("left"));
							
							var scroll_top = main_tasks_menu_obj.scrollTop();
							var top = parseInt(main_tasks_menu_obj.css("top"));
							
							main_tasks_menu_obj.addClass("dragging_task_menu");
							main_tasks_menu_obj.removeClass("scroll");
							main_tasks_menu_obj.css("top", (top - scroll_top) + "px");
							
							j_elm.data("tasks_menu_top", top);
							j_elm.data("scroll_top", scroll_top);
							
							var fixed_position_exists = false;
							var aux = j_elm;
							
							while (aux && aux[0] && !aux.is("body")) {
								if (aux.css("position") == "fixed") {
									fixed_position_exists = true;
									break;
								}
								
								aux = aux.parent();
							}
							
							if (fixed_position_exists) //very important, otherwise if the workflow is maximized or is inside of a popup, the draggable elm will appear in the wrong position.
								j_elm.css("margin-top", "");
							else
								j_elm.css("margin-top", scroll_top + "px");
							
							main_tasks_flow_obj.addClass("dragging_task");
						}
					},
					drag: function(e, obj) {
						//convert positions according with zoom
						var current_zoom = WF.jsPlumbTaskFlow.getCurrentZoom();
						obj.position.left = parseInt(obj.position.left * current_zoom); 
						obj.position.top = parseInt(obj.position.top * current_zoom);
					},
					stop: function(e, obj) {
						var j_elm = obj.helper;
						var elm = j_elm[0];
						
						if (j_elm.data("start_dragging") == 1) {
							j_elm.data("start_dragging", 2);
							
							j_elm.css("margin-top", "");
							
							var scroll_top = parseInt( j_elm.data("scroll_top") );
							
							var current_zoom = WF.jsPlumbTaskFlow.getCurrentZoom();
							var o = main_tasks_flow_obj.offset();
							var sl = main_tasks_flow_obj.scrollLeft();
							var st = main_tasks_flow_obj.scrollTop();
							
							//prepare offset with current_zoom
							var task_offset = assignObjectRecursively({}, obj.offset);
							task_offset.top = task_offset.top / current_zoom;
							task_offset.left = task_offset.left / current_zoom;
							
							var obj_offset = {
								top: task_offset.top + st - o.top,
								left: task_offset.left + sl - o.left
							};
							
							var task_type = elm.getAttribute("type");
							var task_id = null;
							var task = null;
							var droppable_connection = null;
							
							if (!WF.jsPlumbContextMenu.checkIfTaskMenuCanBeCreated(task_type, elm, obj.offset))
								WF.jsPlumbStatusMessage.showError("You cannot drop this Task on this position! Please try again...");
							else {
								var droppable = j_elm.data("droppable");
								
								//preparing droppable if is connection add icon
								if (droppable && $(droppable).hasClass("connector_overlay_add_icon")) {
									var add_icon = droppable;
									droppable = main_tasks_flow_obj;
									
									droppable_connection = WF.jsPlumbTaskFlow.getOverlayConnectionId(add_icon);
									
									if (droppable_connection)
										droppable = $(droppable_connection.source).parent();
								}
								
								var is_first_task = WF.jsPlumbWorkFlowObjOptions && WF.jsPlumbWorkFlowObjOptions.hasOwnProperty("add_default_start_task") && WF.jsPlumbWorkFlowObjOptions.add_default_start_task && (!droppable || $(droppable).is(main_tasks_flow_obj)) && main_tasks_flow_obj.find("." + WF.jsPlumbTaskFlow.task_class_name).length == 0;
								
								task_id = WF.jsPlumbContextMenu.addTaskByType(task_type, obj_offset, droppable);
								var task = WF.jsPlumbTaskFlow.getTaskById(task_id);
								
								j_elm.data("droppable", null);
								
								//Setting task as start task by default if this feature is active
								if (task_id && is_first_task) {
									task.attr("is_start_task", 1);
									task.addClass(WF.jsPlumbTaskFlow.start_task_class_name);
								}
							}
							
							j_elm.offset(task_offset);
							
							j_elm.animate({
								top: j_elm.data("top_css"), 
								left: j_elm.data("left_css")
							},
							{
								duration: 400,
								complete: function() {
									var main_tasks_menu_obj = $("#" + WF.jsPlumbContextMenu.main_tasks_menu_obj_id);
									
									main_tasks_menu_obj.css("top", j_elm.data("tasks_menu_top") + "px");
									main_tasks_menu_obj.addClass("scroll");
									main_tasks_menu_obj.scrollTop(scroll_top);
									main_tasks_menu_obj.removeClass("dragging_task_menu");
									
									//No need to do this, since we add the jqueryui-1.11.4
									//j_elm.offset({top: j_elm.data("top_offset"), left: j_elm.data("left_offset")});
									
									if (task_id) {
										//Preparing task connections if droppable is already an existent connection
										if (droppable_connection)
											WF.jsPlumbContextMenu.setTaskBetweenConnection(task, droppable_connection);
										
										//Calling success function
										WF.jsPlumbProperty.callTaskSettingsCallback(task_type, "on_success_task_cloning", [ task_id ]);
									}
									
									j_elm.data("start_dragging", null);
								}
							});
							
							main_tasks_flow_obj.removeClass("dragging_task");
						}
					}
				});
			}
		},
	
		initTasksClones : function() {
			// creating menu tasks' clones icons
			var elms = $(this.task_menu_obj_selector);
		
			var offsets = new Array();
			for (var i = 0; i < elms.length; i++) {
				var j_elm = $(elms[i]);
			
				j_elm.css("position", "relative");
				offsets.push( j_elm.offset() );
			}
		
			for (var i = 0; i < elms.length; i++) {
				var j_elm = $(elms[i]);
			
				j_elm.css("position", "absolute");
				j_elm.offset({left : offsets[i].left, top : offsets[i].top});
				
				var clone = $( j_elm.clone() );
				clone.css({
					"zIndex": j_elm.css("zIndex") - 1,
					"opacity": 0.5
				});
				clone.addClass("cloned_task");
				clone.removeClass("ui-draggable");
				
				j_elm.after(clone);
			}
		},
	
		initTasksGroups : function() {
			//preparing tasks_groups
			var tasks_groups = $("#" + this.main_tasks_menu_obj_id + " ." + this.tasks_group_label_class_name);
		
			for (var i = 0; i < tasks_groups.length; i++) {
				var tasks_group = $(tasks_groups[i]);
				
				tasks_group.click(function() {
					var parent = $(this.parentNode);
					var tasks_group_tasks_elm = parent.children("." + WF.jsPlumbContextMenu.tasks_group_tasks_class_name).first();
					tasks_group_tasks_elm.toggle();
					parent.toggleClass("tasks_group_collapsed");
					
					//reload menu tasks
					WF.jsPlumbContextMenu.sortTasksMenu();
					
					/*var cloned = $("#" + WF.jsPlumbContextMenu.main_tasks_menu_obj_id + " .cloned_task").hide();
					
					var items = $(WF.jsPlumbContextMenu.task_menu_obj_selector);
					items.css("left", "0px");
					items.css("top", "0px");
					items.css("position", "relative");
				
					var offsets = new Array();
					var new_items = new Array();
					for (var i = 0; i < items.length; i++) {
						var item = $(items[i]);
						
						if (!item.hasClass("cloned_task")) {
							offsets.push( item.offset() );
							new_items.push(item[0]);
						}
					}
					
					$(new_items).each(function(i, item) {
						item = $(item);
						item.css("position", "absolute");
						item.offset({left : offsets[i].left, top : offsets[i].top});
						
						var cloned = item.next();
						cloned = cloned[0] && item.attr("id") == cloned.attr("id") && cloned.hasClass("cloned_task") ? cloned : tasks_group_tasks_elm.children("#" + item.attr("id") + ".cloned_task").first();
						
						if (cloned) {
							cloned.css("position", "absolute");
							cloned.show();
							cloned.offset({left : offsets[i].left, top : offsets[i].top});
						}	
					});*/
				});
		
				var parent = tasks_group.parent();
				var tasks_group_tasks_elm = parent.children("." + this.tasks_group_tasks_class_name).first();
				var height = parent.height() - tasks_group.height();
				tasks_group_tasks_elm.css("height", height + "px");
			}
		},
	
		initTaskContextMenuSettings : function() {
			var available_menus_settings = ["show_set_label_menu", "show_properties_menu", "show_start_task_menu", "show_delete_menu"];
			this.initContextMenuSettings("task_menu", available_menus_settings);
		},
	
		initConnectionContextMenuSettings : function() {
			var available_menus_settings = ["show_set_label_menu", "show_properties_menu", "show_connector_types_menu", "show_overlay_types_menu", "show_delete_menu"];
			this.initContextMenuSettings("connection_menu", available_menus_settings);
		},
	
		initContextMenuSettings : function(menu_type, available_menus_settings) {
			if (WF.jsPlumbProperty.tasks_settings) {
				for (var task_type in WF.jsPlumbProperty.tasks_settings) {
					if (WF.jsPlumbProperty.isTaskSubSettingTrue(task_type, menu_type, "show_context_menu", true)) {
						var show_context_menu = false;
					
						for (var i = 0; i < available_menus_settings.length; i++) {
							if (WF.jsPlumbProperty.isTaskSubSettingTrue(task_type, menu_type, available_menus_settings[i], true)) {
								show_context_menu = true;
								break;
							}
						}
					
						if (!WF.jsPlumbProperty.hasTaskSetting(task_type, menu_type)) {
							eval ("WF.jsPlumbProperty.tasks_settings[task_type]." + menu_type + " = {};");
						}
					
						eval ("WF.jsPlumbProperty.tasks_settings[task_type]." + menu_type + ".show_context_menu = show_context_menu;");
					}
				}
			}
		},
	
		initTaskContextMenu : function() {
			var html = '<ul id="' + this.task_context_menu_id + '" class="mycontextmenu ' + this.task_context_menu_classes + '">' + 
						'<li class="set_label"><a href="#" onClick="return ' + jsPlumbWorkFlowObjVarName + '.jsPlumbContextMenu.setSelectedTaskLabel();">Set Label</a></li>' + 
						'<li class="properties"><a href="#" onClick="return ' + jsPlumbWorkFlowObjVarName + '.jsPlumbProperty.showSelectedTaskProperties();">Properties</a></li>' + 
						'<li class="start_task"><a href="#" onClick="return ' + jsPlumbWorkFlowObjVarName + '.jsPlumbContextMenu.setSelectedStartTask();">Start Task</a></li>' + 
						'<li class="delete"><a href="#" onClick="return ' + jsPlumbWorkFlowObjVarName + '.jsPlumbContextMenu.deleteSelectedTask();">Delete</a></li>' + 
					'</ul>';
		
			$("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id).append(html);
			
			//this is already set in the WF.jsPlumbTaskFlow.addTargetElement method by: 'j_target_elm.addcontextmenu(;'
			//if (!this.isContextMenuBuilt(this.task_context_menu_id))
			//	MyContextMenu.buildContextMenu($('#' + this.task_context_menu_id));
		},
	
		initConnectionContextMenu : function() {
			var html = '<ul id="' + this.connection_context_menu_id + '" class="mycontextmenu ' + this.connection_context_menu_classes + '">' +
						'<li class="set_label"><a href="#" onClick="return ' + jsPlumbWorkFlowObjVarName + '.jsPlumbContextMenu.setSelectedConnectionLabel();">Set Label</a></li>' +
						'<li class="properties"><a href="#" onClick="return ' + jsPlumbWorkFlowObjVarName + '.jsPlumbProperty.showSelectedConnectionProperties();">Properties</a></li>' +
						'<li class="connector_types"><a href="#">Connector Types</a>' + 
							'<ul>';
		
			var types = WF.jsPlumbTaskFlow.available_connection_connectors_type;
			for (var i = 0; i < types.length; i++) {
				var connector_type = types[i];
			
				html += 			'<li id="' + connector_type + '"><a href="#" onClick="return ' + jsPlumbWorkFlowObjVarName + '.jsPlumbContextMenu.setSelectedConnectionConnector(\'' + connector_type + '\');">' + connector_type + '</a></li>';
			}
		
			html +=				'</ul>' + 
						'</li>' +
						'<li class="overlay_types"><a href="#">Overlay Types</a>' + 
							'<ul>';
		
			var types = WF.jsPlumbTaskFlow.available_connection_overlays_type;
			for (var i = 0; i < types.length; i++) {
				var overlay_type = types[i];
			
				html += 			'<li id="' + overlay_type + '"><a href="#" onClick="return ' + jsPlumbWorkFlowObjVarName + '.jsPlumbContextMenu.setSelectedConnectionOverlay(\'' + overlay_type + '\');">' + overlay_type + '</a></li>';
			}
		
			html +=				'</ul>' + 
						'</li>' +
						'<li class="delete"><a href="#" onClick="return ' + jsPlumbWorkFlowObjVarName + '.jsPlumbContextMenu.deleteSelectedConnection();">Delete</a></li>' +
					'</ul>';
		
			$("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id).append(html);
			
			//For some reason ExternalLibClone needs this to be build, bc the the 'ExternalLibClone.bind("contextmenu", ...);' in the WF.jsPlumbTaskFlow.init method is not building the connections menu. So we need to hard code this part here!
			if (!this.isContextMenuBuilt(this.connection_context_menu_id))
				MyContextMenu.buildContextMenu($('#' + this.connection_context_menu_id));
		},
	
		initConnectionAddNewTaskPanel : function() {
			var html ='<div id="' + this.connection_add_new_task_panel_id + '" class="connection_add_new_task_panel myfancypopup">' + 
					'	<div class="title">Choose task:</div>' + 
					'	<select></select>' + 
				 	'	<div class="generic_buttons">' + 
						'	<input type="button" class="cancel" value="Cancel" onClick="' + jsPlumbWorkFlowObjVarName + '.jsPlumbContextMenu.cancelAddNewTaskToConnection(this)" />' + 
				 		'	<input type="button" class="save" value="Add" onClick="' + jsPlumbWorkFlowObjVarName + '.jsPlumbContextMenu.addNewTaskToConnection(this)" />' + 
					'	</div>' + 
				 	'</div>';
			
			$("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id).append(html);
		},
		/* END: INIT METHODS */
	
		/* START: GENERIC MENUS METHODS */
		isContextMenuActive : function() {
			return typeof MyContextMenu != "undefined" && MyContextMenu ? true : false;
		},
		
		isContextMenuBuilt : function(id) {
			return this.isContextMenuActive() && MyContextMenu.getBuildContextMenuIds().indexOf(id) != -1;
		},
		
		destroy : function() {
			if (this.isContextMenuActive()) {
				var pos = MyContextMenu.getBuildContextMenuIds().indexOf(this.task_context_menu_id);
				
				if (pos != -1)
					MyContextMenu.setBuildContextMenuIds( jQuery.grep(MyContextMenu.getBuildContextMenuIds(), function(id, idx) {
						return id != WF.jsPlumbContextMenu.task_context_menu_id;
					}) );
				
				pos = MyContextMenu.getBuildContextMenuIds().indexOf(this.connection_context_menu_id);
				
				if (pos != -1)
					MyContextMenu.setBuildContextMenuIds( jQuery.grep(MyContextMenu.getBuildContextMenuIds(), function(id, idx) {
						return id != WF.jsPlumbContextMenu.connection_context_menu_id;
					}) );
			}
		},
		
		hideContextMenus : function() {
			if (this.isContextMenuActive()) {
				MyContextMenu.hideAllContextMenu();
				
				//DO NOT CLEAN THIS VARIABLES BC WE WANT THE LAST EVENT TARGET WHEN THE CONTEXTMENU WAS OPEN!
				//this.task_context_menu_last_event_target_id = null;
				//this.connection_context_menu_last_event_target_id = null;
			}
		},
		
		getTaskFromMenuByType : function(task_type) {
			return $("#" + this.main_tasks_menu_obj_id + " .task_" + task_type).get(0);
		},
	
		showTaskMenu : function(originalEvent, task) {
			if (this.isContextMenuActive()) {
				this.prepareTaskMenu(originalEvent, task);
			  	
			  	$(originalEvent.target).attr("task_id", task.id);
			  	
			  	var task_context_menu = $('#' + this.task_context_menu_id);
			  	
			  	MyContextMenu.hideAllContextMenu(); //hide all context menus (and their sub ULs)
				MyContextMenu.updateContextMenuPosition(task_context_menu, originalEvent);
				MyContextMenu.showContextMenu(task_context_menu, originalEvent);
			}
		},
	
		showConnectionMenu : function(originalEvent, connection) {
			if (this.isContextMenuActive()) {
				this.prepareConnectionMenu(originalEvent, connection);
			  	
			  	$(originalEvent.target).attr("connection_id", connection.id);
			  	
			  	var connection_context_menu = $('#' + this.connection_context_menu_id);
			  	
				MyContextMenu.hideAllContextMenu(); //hide all context menus (and their sub ULs)
				MyContextMenu.updateContextMenuPosition(connection_context_menu, originalEvent);
				MyContextMenu.showContextMenu(connection_context_menu, originalEvent);
			}
		},
		
		showConnectionAddNewTaskPanel : function(originalEvent, connection) {
			//console.log(originalEvent);
			//console.log(connection);
			var panel = $("#" + this.connection_add_new_task_panel_id);
			panel.attr("connection_id", connection.id);
			
			//load available tasks in tasks menu
			if (panel.find(" > select option").length == 0) 
				this.updateConnectionAddNewTaskPanelAvailableTasks();
			
			MyFancyPopupClone.init({
				elementToShow : panel,
			});
			MyFancyPopupClone.showPopup();
		},
		
		prepareTaskMenu : function(originalEvent, task) {
			var j_task = $(task);
			var task_id = j_task.attr("id");
		  	var task_type = j_task.attr("type");
			var task_context_menu = $('#' + this.task_context_menu_id);
		  	
			this.task_context_menu_last_event_target_id = j_task.attr("id");
			
			//PREPARE SHOW MENU ITEMS
			if (task_type) {
				var menus = ["set_label", "properties", "start_task", "delete"];
		
				for (var i = 0; i < menus.length; i++)
					if (WF.jsPlumbProperty.isTaskSubSettingTrue(task_type, "task_menu", "show_" + menus[i] + "_menu", true))
						task_context_menu.find("." + menus[i]).css("display", "block");
					else
						task_context_menu.find("." + menus[i]).css("display", "none");
			}
		
			//PREPARE START_TASK MENU SELECTED
			var is_start_task = j_task.attr("is_start_task");
			if (is_start_task > 0) 
				task_context_menu.find(".start_task").addClass("is_start_task").attr("title", "Start Task Order: " + is_start_task);
			else
				task_context_menu.find(".start_task").removeClass("is_start_task").removeAttr("title");
			
			//CALL TASK CALLBACK
			WF.jsPlumbProperty.callTaskSettingsCallback(task_type, "on_show_task_menu", [ task_id, j_task, task_context_menu ]);
		},
	
		prepareConnectionMenu : function(originalEvent, connection) {
			this.connection_context_menu_last_event_target_id = connection.id;
			
			var task_type = $("#" + connection.sourceId).attr("type");
			var connection_context_menu = $("#" + this.connection_context_menu_id);
			
			//PREPARE SHOW MENU ITEMS
			if (task_type) {
				var menus = ["set_label", "properties", "connector_types", "overlay_types", "delete"];
		
				for (var i = 0; i < menus.length; i++)
					if (WF.jsPlumbProperty.isTaskSubSettingTrue(task_type, "connection_menu", "show_" + menus[i] + "_menu", true)) 
						connection_context_menu.find("." + menus[i]).css("display", "block");
					else
						connection_context_menu.find("." + menus[i]).css("display", "none");
			}
		
			//PREPARE CONNECTOR TYPES
			var selected_connector_type = connection.getParameter("connection_exit_type");
			connection_context_menu.find(".connector_types li").each(function(i, elm) {
				if (elm.id == selected_connector_type)
					$(elm).addClass("selected_connector");
				else
					$(elm).removeClass("selected_connector");
			});
		
			//PREPARE OVERLAY TYPES
			var selected_overlay_type = connection.getParameter("connection_exit_overlay");
			connection_context_menu.find(".overlay_types li").each(function(i, elm) {
				if (elm.id == selected_overlay_type)
					$(elm).addClass("selected_overlay");
				else
					$(elm).removeClass("selected_overlay");
			});
			
			//CALL CONNECTION CALLBACK
			WF.jsPlumbProperty.callTaskSettingsCallback(task_type, "on_show_connection_menu", [ connection.id, connection, connection_context_menu ]);
		},
		/* END: GENERIC MENUS METHODS */
	
		/* START: CONNECTION MENUS METHODS */
		setContextMenuConnectionId : function(connection_id) {
			this.connection_context_menu_last_event_target_id = connection_id;
		},
		
		getContextMenuConnectionId : function() {
			var id = null;
			
			if (this.connection_context_menu_last_event_target_id)
				id = this.connection_context_menu_last_event_target_id;
			
			if (!id) {
				var obj = MyContextMenu.getSelectedEventTarget();
			
				if (obj) {
					id = WF.jsPlumbTaskFlow.getEventTargetConnectionId(obj);
			
					if (!id && event && event.target)
						id = WF.jsPlumbTaskFlow.getEventTargetConnectionId(event.target);
				}
			}
			
			return id;
		},
	
		setSelectedConnectionLabel : function(options) {
			if (typeof options != "object" || !options["do_not_call_hide_properties"])
				this.hideContextMenus();
			
			var connection_id = this.getContextMenuConnectionId();
		
			return WF.jsPlumbTaskFlow.setConnectionLabelByConnectionId(connection_id);
		},
	
		setSelectedConnectionConnector : function(selected_connector_type, options) {
			if (typeof options != "object" || !options["do_not_call_hide_properties"])
				this.hideContextMenus();
			
			if (selected_connector_type) {
				var connection_id = this.getContextMenuConnectionId();
				var connection = WF.jsPlumbTaskFlow.getConnection(connection_id);
		
				if (connection && WF.jsPlumbTaskFlow.changeConnectionConnectorType(connection, selected_connector_type)) {
					//PREPARE CONNECTOR TYPES
					$("#" + this.connection_context_menu_id + " .connector_types li").each(function(i, elm) {
						if (elm.id == selected_connector_type)
							$(elm).addClass("selected_connector");
						else 
							$(elm).removeClass("selected_connector");
					});
					
					return true;
				}
			}
			return false;
		},
		
		getSelectedConnectionConnector : function() {
			var connection_id = this.getContextMenuConnectionId();
			var connection = WF.jsPlumbTaskFlow.getConnection(connection_id);
	
			if (connection)
				return connection.getParameter("connection_exit_type");
			
			return null;
		},
	
		setSelectedConnectionOverlay : function(selected_overlay_type, options) {
			if (typeof options != "object" || !options["do_not_call_hide_properties"])
				this.hideContextMenus();
			
			if (selected_overlay_type) {
				var connection_id = this.getContextMenuConnectionId();
				var connection = WF.jsPlumbTaskFlow.getConnection(connection_id);
		
				if (connection && WF.jsPlumbTaskFlow.changeConnectionOverlayType(connection, selected_overlay_type)) {
					//PREPARE OVERLAY TYPES
					$("#" + this.connection_context_menu_id + " .overlay_types li").each(function(i, elm) {
						if (elm.id == selected_overlay_type)
							$(elm).addClass("selected_overlay");
						else
							$(elm).removeClass("selected_overlay");
					});
					
					return true;
				}
			}
			return false;
		},
		
		getSelectedConnectionOverlay : function() {
			var connection_id = this.getContextMenuConnectionId();
			var connection = WF.jsPlumbTaskFlow.getConnection(connection_id);
	
			if (connection)
				return connection.getParameter("connection_exit_overlay");
			
			return null;
		},
	
		deleteSelectedConnection : function(options) {
			if (typeof options != "object" || !options["do_not_call_hide_properties"])
				this.hideContextMenus();
			
			var connection_id = this.getContextMenuConnectionId();
		
			WF.jsPlumbTaskFlow.deleteConnection(connection_id);
			
			return false;
		},
		/* END: CONNECTION MENUS METHODS */
		
		/* START: CONNECTION ADD NEW TASK METHODS */
		addNewTaskToConnection : function(elm) {
			elm = $(elm);
			var popup = elm.parent().closest(".connection_add_new_task_panel");
			var connection_id = popup.attr("connection_id");
			var task_type = popup.children("select").val();
			var connection = WF.jsPlumbTaskFlow.getConnection(connection_id);
			
			if (connection && task_type) {
				if (!this.addTaskByTypeToConnection(task_type, connection))
					WF.jsPlumbStatusMessage.showError("Error: New task not added successfully! Please check your workflow and make the necessary changes manually...");
			}
			else if (!task_type)
				WF.jsPlumbStatusMessage.showError("Error: Wrong task to be added.");
			else
				WF.jsPlumbStatusMessage.showError("Error: Could not found connection where this new task should be added.");
			
			MyFancyPopupClone.hidePopup();
		},
		
		cancelAddNewTaskToConnection : function() {
			MyFancyPopupClone.hidePopup();
		},
		
		getAllAvailableTasksByGroups : function() {
			var tasks_by_groups = {};
			var tasks_groups = $("#" + this.main_tasks_menu_obj_id + " ." + this.tasks_group_label_class_name);
			var included_tasks_type = new Array();
			
			for (var i = 0; i < tasks_groups.length; i++) {
				var tasks_group = $(tasks_groups[i]);
				
				//var is_tasks_group_visible = tasks_group.is(":visible");
				var is_tasks_group_visible = tasks_group.css("display") != "none";
				var tasks_group_name = tasks_group.text();
				var tasks_group_tasks_elm = tasks_group.parent().children("." + this.tasks_group_tasks_class_name).first();
				var tasks = tasks_group_tasks_elm.children("." + this.task_menu_class_name);
				
				if (is_tasks_group_visible)
					tasks_by_groups[tasks_group_name] = new Array();
				
				for (var j = 0; j < tasks.length; j++) {
					var task = $(tasks[j]);
					var task_type = task.attr("type");
					
					if (!task.is(".cloned_task") && task_type && included_tasks_type.indexOf(task_type) == -1) {
						if (is_tasks_group_visible)
							tasks_by_groups[tasks_group_name].push(task[0]);
						
						included_tasks_type.push(task_type);
					}
				}
			}
			
			var all_tasks = $(this.task_menu_obj_selector);
			var default_tasks = new Array();
			
			for (var i = 0; i < all_tasks.length; i++) {
				var task = $(all_tasks[i]);
				var task_type = task.attr("type");
				
				if (task_type && included_tasks_type.indexOf(task_type) == -1) {
					default_tasks.push(task[0]);
					included_tasks_type.push(task_type);
				}
			}
			
			if (default_tasks)
				tasks_by_groups[0] = default_tasks;
			
			return tasks_by_groups;
		},
		
		//updates available tasks in tasks menu
		updateConnectionAddNewTaskPanelAvailableTasks : function() {
			var panel = $("#" + this.connection_add_new_task_panel_id);
			var tasks_by_groups = this.getAllAvailableTasksByGroups();
			var html = '';
			
			for (var group_label in tasks_by_groups) {
				var tasks = tasks_by_groups[group_label];
				
				if (group_label != 0)
					html += '<optgroup label="' + group_label + '">';
				
				for (var i = 0; i < tasks.length; i++) {
					var task = $(tasks[i]);
					html += '<option value="' + task.attr("type") + '">' + task.attr("title") + '</option>';
				}
				
				if (group_label != 0)
					html += '</optgroup>';
			}
			
			panel.children("select").html(html);
		},
		/* END: CONNECTION ADD NEW TASK METHODS */
	
		/* START: TASK MENUS METHODS */
		setContextMenuTaskId : function(task_id) {
			this.task_context_menu_last_event_target_id = task_id;
		},
		
		getContextMenuTaskId : function() {
			var id = null;
			
			if (this.task_context_menu_last_event_target_id)
				id = this.task_context_menu_last_event_target_id;
			
			if (!id) {
				var obj = MyContextMenu.getSelectedEventTarget();
			
				if (obj) {
					var id = WF.jsPlumbTaskFlow.getEventTargetTaskId(obj);
			
					if (!id && event && event.target)
						id = WF.jsPlumbTaskFlow.getEventTargetTaskId(event.target);
				}
			}
			
			return id;
		},
	
		setSelectedTaskLabel : function(options) {
			if (typeof options != "object" || !options["do_not_call_hide_properties"])
				this.hideContextMenus();
			
			var task_id = this.getContextMenuTaskId();
		
			return WF.jsPlumbTaskFlow.setTaskLabelByTaskId(task_id);
		},
		
		setSelectedStartTask : function(options) {
			if (typeof options != "object" || !options["do_not_call_hide_properties"])
				this.hideContextMenus();
			
			var task_id = this.getContextMenuTaskId();
			var j_task = WF.jsPlumbTaskFlow.getTaskById(task_id);
			var task_type = j_task.attr("type");
			var main_tasks_flow_obj = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id);
			
			$("#" + this.task_context_menu_id + " .start_task").each(function(i, elm) {
				if (j_task.attr("is_start_task") > 0) {
					$(elm).removeClass("is_start_task");
					
					j_task.removeAttr("is_start_task");
					j_task.removeClass(WF.jsPlumbTaskFlow.start_task_class_name);//Calling callback on end selection
				}
				else {
					//only get items from correspondent scope
					var task_scope = WF.jsPlumbTaskFlow.getTaskParentTasks(j_task).first();
					task_scope = task_scope[0] ? task_scope : main_tasks_flow_obj;
					
					var items = task_scope.find("." + WF.jsPlumbTaskFlow.task_class_name + "[is_start_task]");
					var filtered_items = [];
					
					//remove items that are inside of other inner droppable elements and that the parent task contains eps, which means these tasks are tasks inside of a diferent scope
					for (var i = 0; i < items.length; i++) {
						var item = $(items[i]);
						var parents = WF.jsPlumbTaskFlow.getTaskParentTasks(item);
						var invalid = false;
						
						for (var j = 0; j < parents.length; j++) {
							var parent = $(parents[j]);
							
							if ( parent.find(" > ." + WF.jsPlumbTaskFlow.task_eps_class_name + " ." + WF.jsPlumbTaskFlow.task_ep_class_name).length > 0 && !parent.is(task_scope)) {
								invalid = true;
								break;
							}
						}
						
						if (!invalid)
							filtered_items.push( item[0] );
					};
					//console.log(filtered_items);
					
					var order = 0;
					for (var i = 0; i < filtered_items.length; i++) {
						var o = filtered_items[i].getAttribute("is_start_task");
						order = order < o ? o : order;
					}
					order++;
					
					$(elm).addClass("is_start_task");
					
					j_task.attr("is_start_task", order);
					j_task.addClass(WF.jsPlumbTaskFlow.start_task_class_name);
				}
				
				//Calling callback on every task
				WF.jsPlumbProperty.callTaskSettingsCallback(task_type, "on_complete_select_start_task", [ task_id, j_task ]);
			}).promise().done(function () {
				//Calling callback on end selection
				WF.jsPlumbProperty.callTaskSettingsCallback(task_type, "on_complete_select_start_tasks", [ task_id, j_task ]);
			});
		},
	
		deleteSelectedTask : function(options) {
			if (typeof options != "object" || !options["do_not_call_hide_properties"])
				this.hideContextMenus();
			
			var task_id = this.getContextMenuTaskId();
		
			return WF.jsPlumbTaskFlow.deleteTask(task_id);
		},
	
		addTask : function(task_props) {
			//Preparing some props
			var task_type = task_props["task_type"];
			var task_tag = task_props["task_tag"];
			var task_id = task_props["task_id"];
			var task_label = task_props["task_label"];
			var task_title = task_props["task_title"];
			var connection_exits_props = task_props["connection_exits_props"];
			var offset = task_props["offset"];
			var width = task_props["width"];
			var height = task_props["height"];
			var droppable = task_props["droppable"];
			var is_start_task = task_props["is_start_task"];
			var is_resizable_task = task_props["is_resizable_task"];
			
			if (!task_type || !task_id) {
				WF.jsPlumbStatusMessage.showError("Error: Task Type or Id is undefined for task '" + task_type + "' with id '" + task_id + "' and label '" + task_label + "'.");
				return false;
			}
			
			task_type = task_type.toLowerCase();
			task_label = task_label ? task_label : WF.jsPlumbTaskFlow.default_label;
			task_title = task_title ? task_title : "";
			is_start_task = is_start_task == true ? 1 : (is_start_task > 0 ? is_start_task : "");
			is_resizable_task = is_resizable_task == true ? 1 : "";
			
			//get some default info from correspondent task_menu
			var task_menu = $("#" + WF.jsPlumbContextMenu.main_tasks_menu_obj_id + " .task_" + task_type);
			var task_menu_label = task_menu.attr("title");
			
			//Preparing Task's HTML
			var exit_html = this.task_exit_html;
			if (connection_exits_props && connection_exits_props.length > 0)
				exit_html = exit_html.replace(/#task_ep_class_name#/g, WF.jsPlumbTaskFlow.task_ep_class_name);
			else
				exit_html = "";
			
			//Creating task html
			var task_html = this.task_html;
			task_html = task_html.replace(/#task_class_name#/g, WF.jsPlumbTaskFlow.task_class_name);
			task_html = task_html.replace(/#task_info_class_name#/g, WF.jsPlumbTaskFlow.task_info_class_name);
			task_html = task_html.replace(/#task_info_tag_class_name#/g, WF.jsPlumbTaskFlow.task_info_tag_class_name);
			task_html = task_html.replace(/#task_info_label_class_name#/g, WF.jsPlumbTaskFlow.task_info_label_class_name);
			task_html = task_html.replace(/#task_label_class_name#/g, WF.jsPlumbTaskFlow.task_label_class_name);
			task_html = task_html.replace(/#task_full_label_class_name#/g, WF.jsPlumbTaskFlow.task_full_label_class_name);
			task_html = task_html.replace(/#task_eps_class_name#/g, WF.jsPlumbTaskFlow.task_eps_class_name);
			task_html = task_html.replace(/#task_short_actions_class_name#/g, WF.jsPlumbTaskFlow.task_short_actions_class_name);
			task_html = task_html.replace(/#exit_html#/g, exit_html);
			task_html = task_html.replace(/#task_id#/g, task_id);
			task_html = task_html.replace(/#task_type#/g, task_type);
			task_html = task_html.replace(/#task_tag#/g, task_tag);
			task_html = task_html.replace(/#task_info_tag#/g, task_tag);
			task_html = task_html.replace(/#task_info_label#/g, task_menu_label);
			task_html = task_html.replace(/#task_label#/g, task_label);
			task_html = task_html.replace(/#is_start_task#/g, is_start_task);
			task_html = task_html.replace(/#is_resizable_task#/g, is_resizable_task);
			task_html = task_html.replace(/#task_title#/g, task_title);
			task_html = task_html.replace(/#start_task_class_name#/g, is_start_task ? WF.jsPlumbTaskFlow.start_task_class_name : "");
			
			//Preparing dropable
			var main_tasks_flow_obj = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id);
			
			if (!droppable)
				droppable = main_tasks_flow_obj;
			else
				droppable = $(droppable);
			
			//Adding task html to droppable
			droppable.append(task_html);
			
			//Getting new added task
			var task = droppable.find("#" + task_id);
			//var task = $(task_html);
			
			//preparing bottom actions 
			//Note that there is no need to add an event to the .move_action, bc it already moves the task by default, bc the task object is already a moveable object. This means that the .move_action is only informative so the user can see the he can drag the task.
			var short_actions = task.find("." + WF.jsPlumbTaskFlow.task_short_actions_class_name);
			
			var show_context_menu = WF.jsPlumbProperty.isTaskSubSettingTrue(task_type, "task_menu", "show_context_menu", true);
	  		var context_menu_action = short_actions.find(".context_menu_action");
	  		
	  		if (show_context_menu)
				context_menu_action.click(function(originalEvent) {
					if (originalEvent) {
						if (originalEvent.preventDefault) originalEvent.preventDefault(); 
						else originalEvent.returnValue = false;
						
						if (originalEvent.stopPropagation) originalEvent.stopPropagation();
					}
					
		  			//show task menu
		  			WF.jsPlumbContextMenu.showTaskMenu(originalEvent, task[0]);
				});
			else
				context_menu_action.remove();
			
			//Calling success function
			WF.jsPlumbProperty.callTaskSettingsCallback(task_type, "on_success_task_append", [ task_id ]);
			
			//settings width and height
			if (width > 0)
				task.css("width", width + "px");
				
			if (height > 0)
				task.css("height", height + "px");
			
			//setting offsets
			if (droppable.is(main_tasks_flow_obj)) {
				if (offset) {
					//Note: Do NOT add this code: task.offset({top: offset.top, left: offset.left});
					//otherwise the tasks will be messed up when we refresh the page with a scroll down.
					task.css({
						top: offset.top + "px", 
						left: offset.left + "px"
					});
				}
				else {
					var o = droppable.offset();
				
					task.offset({
						top: parseInt( ( droppable.height() - task.height() ) / 2 + o.top),
	    					left: parseInt( ( droppable.width() - task.width() ) / 2 + o.left),
	    				});
				}
			}
			else {
				main_tasks_flow_obj.find(".task_droppable_over").removeClass("task_droppable_over"); //remove droppable over class from droppable element and from the parents of the droppable element
				
				if (offset) {
					var o = droppable.offset();
					//console.log(o);
					//console.log(offset);
					var mo = main_tasks_flow_obj.offset();
					var sl = parseInt(main_tasks_flow_obj.scrollLeft());
					var st = parseInt(main_tasks_flow_obj.scrollTop());
					var current_zoom = WF.jsPlumbTaskFlow.getCurrentZoom();
					
					o.top = (o.top / current_zoom) + st - mo.top; 
					o.left = (o.left / current_zoom) + sl - mo.left;
					
					var t = offset.top - o.top;
					var l = offset.left - o.left;
					//console.log({top: t, left: l});
					t = t > 20 ? t : 20;
					l = l > 10 ? l : 10;
					
					task.css({
						top: t, 
						left: l
					});
				}
				else 
					task.css({
						top: "20px",
	    					left: "10px",
	    				});
			}
			
			//Creating the real Task and correspondent Exits
			var cep = connection_exits_props && connection_exits_props[0] ? connection_exits_props[0] : null;
			var status = WF.jsPlumbTaskFlow.addTargetElement(task[0], cep) == task_id;
			
			//Setting inner droppables if exists
			this.prepareTaskDroppables(task);
			
			//Setting resizable task
			if (is_resizable_task == 1)
				this.prepareResizableTask(task);
			
			//resize parent task with the new width and height according with this inner task
			WF.jsPlumbTaskFlow.resizeTaskParentTask(task);
			
			if (status) {
				//add source endpoints
				var source_element = task.find(WF.jsPlumbTaskFlow.source_selector);
				if (source_element[0])
					WF.jsPlumbTaskFlow.addSourceElement(source_element[0], task[0], cep);
				
				if (connection_exits_props && connection_exits_props.length > 1)
					for (var i = 1; i < connection_exits_props.length; i++)
						WF.jsPlumbTaskFlow.addExtraSourceElementToTask(task_id, connection_exits_props[i]);
				
				//Centering inner elements
				WF.jsPlumbTaskFlow.centerTaskInnerElements(task_id);
				
				return task_id;
			}
			
			return null;
		},
		
		loadTask : function(task_props) {
			var task_type = task_props["task_type"];
			var task_id = task_props["task_id"];
			task_props["is_resizable_task"] = WF.jsPlumbProperty.isTaskSettingTrue(task_type, "is_resizable_task", false);
			//console.log("loadTask:"+task_props["task_tag"]+":"+task_props["is_resizable_task"]);
			
			var status = this.addTask(task_props) == task_id;
			
			if (status) {
				WF.jsPlumbProperty.callTaskSettingsCallback(task_type, "on_success_task_creation", [ task_id ]);
				
				return task_id;
			}
			
			return false;
		},
		
		addTaskByType : function(task_type, offset, droppable) {
			var task_id = this.generateNewTaskId(task_type);
			
			if (!task_id) {
				WF.jsPlumbStatusMessage.showError("There was an error trying to generating a new task id. Please try again later...");
				return false;
			}
		
			var task_menu = $("#" + WF.jsPlumbContextMenu.main_tasks_menu_obj_id + " .task_" + task_type);
			task_menu = $(task_menu[0]);
			var task_label = task_menu.text();
			var task_tag = task_menu.attr("tag");
			var task_title = task_menu.attr("title");
			var is_resizable_task = WF.jsPlumbProperty.isTaskSettingTrue(task_type, "is_resizable_task", false);
			//console.log("addTaskByType:"+task_tag+":"+is_resizable_task);
			
			var task_props = {
				task_type: task_type, 
				task_tag: task_tag, 
				task_id: task_id, 
				task_label: task_label, 
				task_title: task_title, 
				offset: offset,
				droppable: droppable,
				is_resizable_task : is_resizable_task,
			};
			
			var status = this.addTask(task_props) == task_id;

			if (status) {
				var properties = $("#" + WF.jsPlumbTaskFlow.main_tasks_properties_obj_id + " .task_properties_" +  task_type.toLowerCase()).html();
				
				var div_tmp = document.getElementById("div_tmp");
				if (!div_tmp) {
					div_tmp = document.createElement("DIV");
					div_tmp.id = "div_tmp";
					div_tmp.style.display = "none";
					//div_tmp.style.visibility="hidden";
	
					document.body.appendChild(div_tmp);
				}
			
				var j_div_tmp = $(div_tmp);

				j_div_tmp.html(properties);

				status = WF.jsPlumbProperty.loadTaskPropertiesFromHtmlElm( j_div_tmp, task_id);
				if (status)
					WF.jsPlumbProperty.saveTaskPropertiesFromHtmlElm( j_div_tmp, task_id);
				j_div_tmp.html("");
				
				WF.jsPlumbProperty.callTaskSettingsCallback(task_type, "on_success_task_creation", [ task_id ]);
			}
			else {	
				WF.jsPlumbStatusMessage.showError("Error trying to create task '" + task_type + "' with id '" + task_id + "'.");
				return false;
			}
			
			return task_id;
		},
		
		addTaskByTypeToConnection : function(task_type, connection) {
			//console.log(connection);
			//console.log(task_type);
			
			if (!connection) {
				WF.jsPlumbStatusMessage.showError("Error: no connection seleted.");
				return false;
			}
			else if (!task_type) {
				WF.jsPlumbStatusMessage.showError("Error: task type undefined.");
				return false;
			}
			
			//prepare new task offset
			var src_o = $(connection.endpoints[0].canvas).offset();
			var trg_o = $(connection.endpoints[1].canvas).offset();
			var offset = { //prepare offset to middle
				top: Math.abs(src_o.top - trg_o.top), 
				left: Math.abs(src_o.left - trg_o.left)
			};
			
			var droppable = connection.target.parent();
			var main_tasks_flow_obj = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id);
			
			if (droppable.is(main_tasks_flow_obj)) {
				var sl = parseInt(main_tasks_flow_obj.scrollLeft());
				var st = parseInt(main_tasks_flow_obj.scrollTop());
				
				offset.top += st;
				offset.left += sl;
			}
			
			//add new task
			var new_task_id = this.addTaskByType(task_type, offset, droppable);
			
			//prepare new connections
			if (new_task_id) {
				var j_task = WF.jsPlumbTaskFlow.getTaskById(new_task_id);
				
				if (j_task[0] && this.setTaskBetweenConnection(j_task, connection))
					return new_task_id;
			}
			
			return false;
		},
		
		setTaskBetweenConnection : function(task, connection) {
			//console.log(task);
			//console.log(connection);
			
			var task_id = task.attr("id");
			var task_type = task.attr("type");
			
			//get connection props
			var connection_exit_id = connection.getParameter("connection_exit_id");
			var connection_exit_type = connection.getParameter("connection_exit_type");
			var connection_exit_overlay = connection.getParameter("connection_exit_overlay");
			var connection_exit_color = connection.getParameter("connection_exit_color");
			var connection_label = connection.getOverlay("label").getLabel();
			var connection_status = false;
			
			connection_exit_color = connection_exit_color ? connection_exit_color : connection.getPaintStyle().strokeStyle;
			
			//delete previous connection and add a new one from Source task to new task.
			if (WF.jsPlumbTaskFlow.deleteConnection(connection.id, true))
				connection_status = WF.jsPlumbTaskFlow.connect(connection.sourceId, task_id, connection_label, connection_exit_type, connection_exit_overlay, {
					id : connection_exit_id,
					color : connection_exit_color,
				});
			
			//get new task default exit
			var task_exits = {};
			
			//get new task default exit - based in task properties
			var task_properties_html_element = $("#" + WF.jsPlumbTaskFlow.main_tasks_properties_obj_id + " .task_properties_" +  task_type.toLowerCase()).html();
			var task_property_exits = $(task_properties_html_element).find(".task_property_exit");
			
			for (var i = 0; i < task_property_exits.length; i++) { 
				var task_property_exit = task_property_exits[i];
				var exit_id = null;
				var exit_color = null;
				var exit_label = null;
				
				if (task_property_exit && task_property_exit.hasAttribute("exit_id")) {
					exit_id = task_property_exit.getAttribute("exit_id");
					exit_color = task_property_exit.getAttribute("exit_color");
					exit_label = task_property_exit.getAttribute("exit_label");
				}
				
				if (exit_id)
					task_exits[exit_id] = {color: exit_color, label: exit_label};
			}
			
			//get new task default exit - based in task eps html
			var eps = task.find(".eps .ep"); //This is very important bc there are some tasks that create exits dynamically, so we must get directly the ".ep" items.
			
			for (var i = 0; i < eps.length; i++) { 
				var ep = eps[i];
				var exit_id = ep.getAttribute("connection_exit_id");
				var exit_color = ep.getAttribute("connection_exit_color");
				var exit_label = ep.getAttribute("connection_exit_label");
				
				if (exit_id && !task_exits.hasOwnProperty(exit_id))
					task_exits[exit_id] = {color: exit_color, label: exit_label};
			}
			
			var default_color = task.css("border-color");
			default_color = default_color ? default_color : "#000";
			
			for (var exit_id in task_exits) {
				var task_exit = task_exits[exit_id];
				var exit_color = task_exit["color"];
				var exit_label = task_exit["label"];
				
				var con_status = WF.jsPlumbTaskFlow.connect(task_id, connection.targetId, exit_label, connection_exit_type, connection_exit_overlay, {
					id : exit_id,
					color : exit_color ? exit_color : default_color,
				});
				
				if (!con_status)
					connection_status = false;
			}
			
			if (connection_status)
				WF.jsPlumbProperty.callTaskSettingsCallback(task_type, "on_success_task_between_connection", [ task_id ]);
			
			//return true if both connections were created successfully
			return connection_status;
		},
		
		prepareTaskDroppables : function(task, options) {
			//Setting inner droppables if exists
			var inner_droppables = task.find("." + WF.jsPlumbTaskFlow.task_droppable_class_name);
			
			$.each(inner_droppables, function(idx, inner_droppable) {
				inner_droppable = $(inner_droppable);
				
				var opts = {
					accept: "." + WF.jsPlumbTaskFlow.task_class_name,
					greedy: true,
					tolerance: "pointer",
					drop: function(e, obj) {
						obj.helper.data("droppable", inner_droppable[0]);
					},
					over: function(e, obj) {
						inner_droppable.addClass("task_droppable_over");
					},
					out: function(e, obj) {
						inner_droppable.removeClass("task_droppable_over");
					}
				};
				
				if (options)
					for (var name in options)
						opts[name] = options[name];
				
				inner_droppable.droppable(opts);
				
				if (inner_droppable.css("position") != "absolute") {
					var task_id = task.attr("id");
					var task_tag = task.attr("tag");
					
					WF.jsPlumbStatusMessage.showError("This task contains droppable elements without the position absolute. Droppable elements must be absolute! Error in task: '" + task_tag + "' with id '" + task_id + "'.");
				}
			});
		},
		
		prepareResizableTask : function(task) {
			var main_tasks_flow_obj = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id);
			
			task.resizable({
				start: function(e, obj) {
					main_tasks_flow_obj.data("inner_task_dragging", 1);
				},
				resize: function(e, obj) {
					WF.jsPlumbTaskFlow.repaintTask(task);
				},
				stop : function(e, obj) {
					main_tasks_flow_obj.data("inner_task_dragging", null);
					
					WF.jsPlumbTaskFlow.repaintTask(task);
				},
			});
		},
	
		generateNewTaskId : function(task_type) {
			var task_id, c = 0;
		
			do {	
				task_id = 'task_' + task_type + '_' + Math.floor(Math.random()*1100) + '_' + Math.abs(WF.jsPlumbTaskFlow.main_tasks_flow_obj_id.hashCode());
			
				if (!WF.jsPlumbTaskFlow.TaskIdExists(task_id)) {
					return task_id;
				}
			
				if (c > 100) {
					break;
				}
			
				c++;
			} 
			while(true);
		
			return false;
		},
	
		checkIfTaskCanBeCreated : function(task_type, elm, task_offset) {
			return WF.jsPlumbContainer.canTaskBeCreatedInsideOfSelectedContainer(task_type, elm, task_offset);
		},
	
		checkIfTaskMenuCanBeCreated : function(task_type, elm, task_offset) {
			return WF.jsPlumbContainer.canTaskBeCreatedInsideOfSelectedContainer(task_type, elm, task_offset) && WF.jsPlumbContainer.canTaskBeCreatedInsideOfMainTaskFlowContainer(task_type, elm, task_offset);
		},
		
		sortTasksMenu : function() {
			var elms = $(this.task_menu_obj_selector);
			var parents = elms.parent();
			var originals = elms.filter(":not(.cloned_task)");
			var clones = elms.filter(".cloned_task");
			var hidden_parents = {};
			var offsets = new Array();
			
			//saves the hidden .tasks_group_tasks
			for (var i = 0; i < parents.length; i++) {
				var j_parent = $(parents[i]);
				
				if (j_parent.css("display") == "none")
					hidden_parents[i] = j_parent;
			}
			
			//reset .tasks_group_tasks' height and force to show all them
			parents.css({
				height: "auto",
				display: "", //force to show the .tasks_group_tasks
			});
			
			//hide clones
			clones.hide();
			
			//prepare menu tasks to be relative
			for (var i = 0; i < originals.length; i++) {
				var j_original = $(originals[i]);
				
				j_original.css({
					position: "relative",
					top: "",
					left: "",
				});
			}
			
			//set new .tasks_group_tasks height
			for (var i = 0; i < parents.length; i++) {
				var j_parent = $(parents[i]);
				
				j_parent.css("height", j_parent.height() + "px");
			}
			
			//hide the previous hidden .tasks_group_tasks
			for (var i in hidden_parents)
				hidden_parents[i].css("display", "none");
			
			//get menu tasks positions
			for (var i = 0; i < originals.length; i++) {
				var j_original = $(originals[i]);
				
				offsets.push( j_original.offset() );
			}
			
			//show clones
			clones.show();
			
			//sets positions for menu tasks, including clones
			for (var i = 0; i < originals.length; i++) {
				var j_original = $(originals[i]);
				var j_clone = $(clones[i]);
				
				j_original.css("position", "absolute");
				j_original.offset({left : offsets[i].left, top : offsets[i].top});
				j_clone.offset({left : offsets[i].left, top : offsets[i].top});
			}
		},
		/* END: TASK MENUS METHODS */
		
		/* START: OTHER UI UTILS */
		//show or hide tasks menu
		toggleTasksMenuPanel : function(elm) { 
			var tasks_menu_hide = $("#" + this.main_tasks_menu_hide_obj_id);
			
			if (tasks_menu_hide[0]) {
				var tasks_menu = $("#" + this.main_tasks_menu_obj_id);
				var tasks_flow = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id);
				var is_tasks_menu_hidden = tasks_menu.hasClass("tasks_menu_hidden");
				var selected_task_properties = $("#" + WF.jsPlumbProperty.selected_task_properties_id);
				var selected_connection_properties = $("#" + WF.jsPlumbProperty.selected_connection_properties_id);
				
				tasks_menu.toggleClass("tasks_menu_hidden");
				tasks_menu_hide.toggleClass("tasks_menu_hidden");
				tasks_flow.toggleClass("tasks_menu_hidden");
				selected_task_properties.toggleClass("tasks_menu_hidden");
				selected_connection_properties.toggleClass("tasks_menu_hidden");
				tasks_flow.children(".tasks_flow_update_panel_confirmation").toggleClass("tasks_menu_hidden");
				
				/*var button = $(elm);
				
				if (is_tasks_menu_hidden)
					button.removeClass("maximize").addClass("minimize");
				else
					button.removeClass("minimize").addClass("maximize");
				
				if (!tasks_menu_hide.hasClass("tasks_menu_hidden")) {
					var mtfo_left = tasks_flow.attr("left_bkp");
					mtfo_left = mtfo_left ? mtfo_left : "255px";
					
					var mtmho_left = tasks_menu_hide.attr("left_bkp");
					mtmho_left = mtmho_left ? mtmho_left : "250px";
					
					tasks_menu.show();
					tasks_flow.css("left", mtfo_left);
					tasks_menu_hide.css("left", mtmho_left);
					
					button.removeClass("maximize").addClass("minimize");
				}
				else {
					if (!tasks_flow[0].hasAttribute("left_bkp")) {
						tasks_flow.attr("left_bkp", tasks_flow.css("left"));
					}
				
					if (!tasks_menu_hide[0].hasAttribute("left_bkp")) {
						tasks_menu_hide.attr("left_bkp", tasks_menu_hide.css("left"));
					}
				
					var mtmo_left = parseInt( tasks_menu.css("left") );
					tasks_menu.hide();
				
					if (mtmo_left) {
						tasks_flow.css("left", (mtmo_left + 5) + "px");
						tasks_menu_hide.css("left", mtmo_left + "px");
					}
					else {
						tasks_flow.css("left", "5px");
						tasks_menu_hide.css("left", mtmo_left);
					}
				
					button.removeClass("minimize").addClass("maximize");
				}*/
				
				WF.jsPlumbTaskFlow.zoomRefresh();
				WF.getMyFancyPopupObj().updatePopup();
				
				setTimeout(function() {
					WF.jsPlumbTaskFlow.zoomRefresh();
					WF.getMyFancyPopupObj().updatePopup();
				}, 1000);
			}
		},
		
		//flip tasks menu side (left or right)
		flipPanelsSide : function() {
			var parent = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id).parent().closest(".taskflowchart");
			parent.toggleClass("reverse");
			
			//remove hard code style from panels
			var panels = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id + ", #" + WF.jsPlumbContextMenu.main_tasks_menu_obj_id + ", #" + WF.jsPlumbContextMenu.main_tasks_menu_hide_obj_id);
			
			panels.css({
				width: "",
				left: "",
				right: "",
			});
			
			var properties_panels = $("#" + WF.jsPlumbProperty.selected_task_properties_id + ",#" + WF.jsPlumbProperty.selected_connection_properties_id);
			
			if (parent.is(".fixed_properties, .fixed_side_properties"))
				properties_panels.css({
					width: "",
					left: "",
					right: "",
				});
			
			WF.jsPlumbTaskFlow.zoomRefresh();
			
			//reload menu tasks
			setTimeout(function() {
				WF.jsPlumbContextMenu.sortTasksMenu();
			}, 100); //must be in setTimeout, otherwise won't do anything
		},
		/* END: OTHER UI UTILS */
	};

	WF.jsPlumbContainer = { 
		tasks_containers : null,
		tasks_containers_class_name : "task_container",
	
		containers_settings : null,
	
		init : function() {
			this.initContainersSettings();
		},
	
		initContainersSettings : function() {
			var containers = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id + " ." + this.tasks_containers_class_name);
		
			var offset, width, height, allow_automatic_width_resize, allow_automatic_height_resize, o, w, h;
		
			var containers_settings = {};
		
			for (var i = 0; i < containers.length; i++) {
				var container = containers[i];
				var j_container = $(container);
			
				offset = j_container.offset();
				width = j_container.width();
				height = j_container.height();
			
				allow_automatic_width_resize = true;
				allow_automatic_height_resize = true;
			
				for (var j = 0; j < containers.length; j++) {
					var container_aux = containers[j];
				
					if (container.id != container_aux.id) {
						var j_container_aux = $(container_aux);
					
						o = j_container_aux.offset();
						w = j_container_aux.width();
						h = j_container_aux.height();
					
						if (o.left > offset.left && (
							(o.top >= offset.top && o.top < offset.top + height) || 
							(o.top < offset.top && o.top + h > offset.top) 
						   ) ) {
							allow_automatic_width_resize = false;
						}
					
						if (o.top > offset.top && (
							(o.left >= offset.left && o.left < offset.left + width) ||
							(o.left < offset.left && o.left + w > offset.left)
						   ) ) {
						   	allow_automatic_height_resize = false;
						}
					
						if (!allow_automatic_width_resize && !allow_automatic_height_resize) {
							break;
						}
					}
				}
			
				containers_settings[ container.id ] = {allow_automatic_width_resize: allow_automatic_width_resize, allow_automatic_height_resize: allow_automatic_height_resize};
			}
		
			//console.log(containers_settings);
			this.containers_settings = containers_settings;
		},
	
		canTaskBeCreatedInsideOfSelectedContainer : function(task_type, elm, task_offset) {
			if (!task_type || !elm || !task_offset || !task_offset.hasOwnProperty("left") || !task_offset.hasOwnProperty("top"))
				return false;
			
			var j_elm = $(elm);
			var task_width = parseInt(j_elm.outerWidth());
			var task_height = parseInt(j_elm.outerHeight());
			
			if (!$.isEmptyObject(this.tasks_containers)) {
				var containers_id = this.tasks_containers[task_type];
				
				if (!containers_id)
					return false;
				
				if (containers_id.length > 0) {
					var current_zoom = WF.jsPlumbTaskFlow.getCurrentZoom();
					
					for (var i = 0; i < containers_id.length; i++) {
						container_id = containers_id[i];
						
						var container = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id + " #" + container_id)[0];
						
						if (container) {
							var j_container = $(container);
							
							var container_offset = j_container.offset();
							var container_width = j_container.width();
							var container_height = j_container.height();
							
							var container_settings = this.containers_settings ? this.containers_settings[container_id] : null;
							var allow_automatic_width_resize = container_settings ? container_settings.allow_automatic_width_resize : false;
							var allow_automatic_height_resize = container_settings ? container_settings.allow_automatic_height_resize : false;
							
							var is_inside = false;
							
							if (task_offset.left >= container_offset.left && task_offset.top >= container_offset.top) {
								is_inside = true;
								
								if (!allow_automatic_width_resize && task_offset.left + task_width > container_offset.left + container_width)
									is_inside = false;
								
								if (!allow_automatic_height_resize && task_offset.top + task_height > container_offset.top + container_height)
									is_inside = false;
							}
							
							if (is_inside) {
								if (allow_automatic_width_resize || allow_automatic_height_resize)
									this.automaticIncreaseContainersSize();
								
								return true;
							}
						}
					}
					
					return false;
				}
			}
			
			return true;
		},
	
		//Check if inside of the tasks_flow main div
		canTaskBeCreatedInsideOfMainTaskFlowContainer : function(task_type, elm, task_offset) {
			if (!task_type || !elm || !task_offset || !task_offset.hasOwnProperty("left") || !task_offset.hasOwnProperty("top"))
				return false;
		
			var j_elm = $(elm);
		
			var task_width = parseInt(j_elm.outerWidth());
			var task_height = parseInt(j_elm.outerHeight());
		
			var main_tasks_flow_obj = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id);
			var o = main_tasks_flow_obj.offset();
			var sw = main_tasks_flow_obj.prop("scrollWidth");
			var sh = main_tasks_flow_obj.prop("scrollHeight");
			
			var is_inside = task_offset.left >= o.left && task_offset.left + task_width < o.left + sw && 
				task_offset.top >= o.top && task_offset.top + task_height < o.top + sh; 
			
			return is_inside;
		},
		
		automaticZoomContainers : function() {
			if (this.containers_settings) {
				var containers = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id + " ." + this.tasks_containers_class_name);
				var current_zoom = WF.jsPlumbTaskFlow.getCurrentZoom();
				
				for (var i = 0; i < containers.length; i++) {
					var container = containers[i];
					var j_container = $(container);
					
					//find containers that are at the edge of main_tasks_flow_obj
					var s = this.containers_settings[ container.id ];
					
					if (s && (s.allow_automatic_width_resize || s.allow_automatic_height_resize)) {
						//resize container
						var width_without_zoom = j_container.data("width_without_zoom");
						var height_without_zoom = j_container.data("height_without_zoom");
						
						if (!width_without_zoom) {
							width_without_zoom = j_container.width();
							j_container.data("width_without_zoom", width_without_zoom);
						}
						
						if (!height_without_zoom) {
							height_without_zoom = j_container.height();
							j_container.data("height_without_zoom", height_without_zoom);
						}
						
						var width = width_without_zoom + "px";
						var height = height_without_zoom + "px";
						
						//if current_zoom >= 1, leave it as it is bc the containers will be already big for the screen and the user needs to resize them accordingly, as it should be!
						if (current_zoom >= 1 || !$.isNumeric(current_zoom)) {
							//reset saved data, so it can get the updated values, just in case if they were changed manuallly by the user on purpose...
							j_container.data("width_without_zoom", null);
							j_container.data("height_without_zoom", null);
						}
						else { //resize the j_container so it can be at the edge of main_tasks_flow_obj
							if (current_zoom < 0)
								current_zoom = 0;
							
							width = (width_without_zoom / current_zoom) + "px";
							height = (height_without_zoom / current_zoom) + "px";
						}
						
						if (s.allow_automatic_width_resize)
							j_container.css("width", width);
						
						if (s.allow_automatic_height_resize)
							j_container.css("height", height);
					}
				}
				
				this.automaticIncreaseContainersSize();
				this.automaticDecreaseContainersSize();
			}
		},
	
		automaticIncreaseContainersSize : function() {
			if (this.containers_settings) {
				var current_zoom = WF.jsPlumbTaskFlow.getCurrentZoom();
				
				if (current_zoom <= 1) {
					var containers = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id + " ." + this.tasks_containers_class_name);
					var main_tasks_flow_obj = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id);
					var o = main_tasks_flow_obj.offset();
					var sw = main_tasks_flow_obj.prop("scrollWidth");
					var sh = main_tasks_flow_obj.prop("scrollHeight");
					var sl = main_tasks_flow_obj.scrollLeft();
					var st = main_tasks_flow_obj.scrollTop();
					
					for (var i = 0; i < containers.length; i++) {
						var container = containers[i];
						var j_container = $(container);
						
						var s = this.containers_settings[ container.id ];
						
						if (s) {
							var position = j_container.position();
							
							//convert offset to original and real offset without zoom.
							position.left = parseInt(position.left / current_zoom);
							position.top = parseInt(position.top / current_zoom);
							
							if (s.allow_automatic_width_resize) {
								var width = parseInt(sw - (position.left + sl)); //the parenthesis are very important here, bc if we do below: 'sw - position.left + sl', the result will be different. This is very weird, but it is what happen, so we need to add the parenthesis. 
								j_container.css("width", width + "px");
							}
							
							if (s.allow_automatic_height_resize) {
								var height = parseInt(sh - (position.top + st)); //the parenthesis are very important here, bc if we do below: 'sh - position.top + st', the result will be different. This is very weird, but it is what happen, so we need to add the parenthesis.
								j_container.css("height", height + "px");
							}
							
						}
					}
				}
			}
		},
	
		automaticDecreaseContainersSize : function() {
			if (this.containers_settings) {
				var max_offset = this.getMaximumTasksOffset();
				var max_left = max_offset.left;
				var max_top = max_offset.top;
				
				var main_tasks_flow_obj = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id);
				var sl = main_tasks_flow_obj.scrollLeft();
				var st = main_tasks_flow_obj.scrollTop();
				
				var containers = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id + " ." + this.tasks_containers_class_name);
				var container, j_container, offset, s;
				
				for (var i = 0; i < containers.length; i++) {
					container = containers[i];
					j_container = $(container);
			
					offset = j_container.offset();
					s = this.containers_settings[ container.id ];
					
					if (s.allow_automatic_width_resize)
						j_container.css("width", parseInt(max_left - (offset.left + sl)) + "px"); //the parenthesis are very important here, bc if we do below: 'max_left - offset.left + sl', the result will be different. This is very weird, but it is what happen, so we need to add the parenthesis. 
		
					if (s.allow_automatic_height_resize)
						j_container.css("height", parseInt(max_top - (offset.top + st)) + "px");//the parenthesis are very important here, bc if we do below: 'max_top - offset.top + st', the result will be different. This is very weird, but it is what happen, so we need to add the parenthesis. 
				}
				
				this.automaticIncreaseContainersSize();
			}
		},
		
		getMaximumTasksOffset : function() {
			var max_left = 0, max_top = 0;
		
			var main_tasks_flow_obj = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id);
			var sl = main_tasks_flow_obj.scrollLeft();
			var st = main_tasks_flow_obj.scrollTop();
		
			var tasks = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id + " ." + WF.jsPlumbTaskFlow.task_class_name);
			var task, j_task, task_offset, task_width, task_height, task_max_left, task_max_top;
			
			for (var i = 0; i < tasks.length; i++) {
				task = tasks[i];
				j_task = $(task);
			
				task_offset = j_task.offset();
				task_size = this.getTaskSize(task);
				task_width = task_size.width;
				task_height = task_size.height;
			
				task_max_left = task_offset.left + task_width + sl;
				task_max_top = task_offset.top + task_height + st;
			
				max_left = max_left > task_max_left ? max_left : task_max_left;
				max_top = max_top > task_max_top ? max_top : task_max_top;
			}
			
			var containers = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id + " ." + this.tasks_containers_class_name);
			var container, j_container, offset, width, height;
	
			for (var i = 0; i < containers.length; i++) {
				container = containers[i];
				j_container = $(container);
		
				offset = j_container.offset();
				width = j_container.width();
				height = j_container.height();
			
				max_left = offset.left + sl > max_left ? offset.left + sl : max_left;
				max_top = offset.top + st > max_top ? offset.top + st : max_top;
			}
		
			//give some margins
			max_left += 20;
			max_top += 10;
			
			return {
				left: max_left,
				top: max_top
			};
		},
	
		changeContainerSize : function(container_id, new_width, new_height) {
			var container = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id + " #" + container_id);
			var container = container ? container[0] : null;
		
			if (container && container.id) {
				var j_container = $(container);
			
				var offset = j_container.offset();
				var width = j_container.width();
				var height = j_container.height();
			
				var minw = parseInt(j_container.css("min-width"));
				var maxw = parseInt(j_container.css("max-width"));
				var minh = parseInt(j_container.css("min-height"));
				var maxh = parseInt(j_container.css("max-height"));
			
				new_width = new_width <= 0 ? width : new_width;
				new_height = new_height <= 0 ? height : new_height;
			
				new_width = minw > 0 && minw > new_width ? minw : new_width;
				new_width = maxw > 0 && maxw < new_width ? maxw : new_width;
				new_height = minh > 0 && minh > new_height ? minh : new_height;
				new_height = maxh > 0 && maxh < new_height ? maxh : new_height;
			
				var s = this.containers_settings[ container.id ];
			
				if (s.allow_automatic_width_resize && new_width < width) {
					new_width = width;
					WF.jsPlumbStatusMessage.showMessage("You cannot decrease the width for the container:'" + container_id + "'!");
				}
			
				if (s.allow_automatic_height_resize && new_height < height) {
					new_height = height;
					WF.jsPlumbStatusMessage.showMessage("You cannot decrease the height for this container:'" + container_id + "'!");
				}
			
				if (width != new_width || height != new_height) {
					//console.log(container_id+": offset("+offset.left+","+offset.top+") size("+width+","+height+")");
							
					var containers = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id + " ." + this.tasks_containers_class_name);
				
					var containers_tasks = {};
					for (var i = 0; i < containers.length; i++) {
						containers_tasks[ containers[i].id ] = this.getContainerTasks(containers[i].id);
					}
				
					//move the inner tasks accordingly
					this.moveContainerTasks(container_id, containers_tasks[container_id], offset.left, offset.top, new_width, new_height);
				
					var diff_width = new_width - width;//diff > 0: increase width; dif < 0: decrease width;
					var diff_height = new_height - height;//diff > 0: increase height; dif < 0: decrease height;
				
					//move the outer containers accordingly
					var j_c, o, w, h, l, t;
				
					for (var i = 0; i < containers.length; i++) {
						if (container_id != containers[i].id) {
							j_c = $(containers[i]);
						
							o = j_c.offset();
							w = j_c.width();
							h = j_c.height();
						
							l = o.left;
							t = o.top;
						
							if (diff_width != 0 && o.left > offset.left && (
								(o.top >= offset.top && o.top < offset.top + height) || 
								(o.top < offset.top && o.top + h > offset.top) 
							   ) ) {
								l += diff_width;
							}
					
							if (diff_height != 0 && o.top > offset.top && (
								(o.left >= offset.left && o.left < offset.left + width) ||
								(o.left < offset.left && o.left + w > offset.left)
							   ) ) {
								t += diff_height;
							}
						
							if (l != o.left || t != o.top) {
								//console.log(containers[i].id+": b("+o.left+","+o.top+") a("+l+","+t+")");
								this.moveContainerTasks(containers[i].id, containers_tasks[ containers[i].id ], l, t, null, null);
								this.moveContainer(containers[i].id, l, t);
							}
						}
					}
				
					//resize the selected container accordingly
					j_container.css({width: new_width + "px", height: new_height + "px"});
					this.automaticIncreaseContainersSize();
				}
			}
		},
	
		moveContainer : function(container_id, left, top) {
			var container = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id + " #" + container_id);
			var container = container ? container[0] : null;
		
			if (container && container.id) {
				var j_container = $(container);
			
				var offset = j_container.offset();
				left = left ? left : offset.left;
				top = top ? top : offset.top;
			
				j_container.css("position", "absolute");
				j_container.offset({left: left, top: top});
			}
		},
	
		moveContainerTasks : function(container_id, tasks, new_container_left, new_container_top, new_container_width, new_container_height) {
			var container = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id + " #" + container_id);
			var container = container ? container[0] : null;
		
			if (container && container.id && tasks && tasks.length > 0) {
				var j_container = $(container);
			
				var container_offset = j_container.offset();
				var container_width = j_container.width();
				var container_height = j_container.height();
			
				new_container_left = new_container_left ? new_container_left : container_offset.left;
				new_container_top = new_container_top ? new_container_top : container_offset.top;
				new_container_width = new_container_width > 0 ? new_container_width : container_width;
				new_container_height = new_container_height > 0 ? new_container_height : container_height;
			
				var diff_left = new_container_left != container_offset.left ? new_container_left - container_offset.left : 0;
				var diff_top = new_container_top != container_offset.top ? new_container_top - container_offset.top : 0;
				var diff_width = new_container_width != container_width ? new_container_width - container_width : 0;
				var diff_height = new_container_height != container_height ? new_container_height - container_height : 0;
				var perc_width = diff_width < 0 ? new_container_width / container_width : 0;
				var perc_height = diff_height < 0 ? new_container_height / container_height : 0;
			
				var task, j_task, task_offset, task_size, task_width, task_height, new_task_left, new_task_top;
			
				for (var i = 0; i < tasks.length; i++) {
					task = tasks[i];
					j_task = $(task);
			
					task_offset = j_task.offset();
					task_size = this.getTaskSize(task);
					task_width = task_size.width;
					task_height = task_size.height;
				
					if (diff_width < 0 && task_offset.left + task_width > new_container_left + new_container_width) {
						new_task_left = (task_offset.left - container_offset.left) * perc_width + container_offset.left;
					}
					else {
						new_task_left = task_offset.left;
					}
				
					new_task_left += diff_left;
					new_task_left = new_task_left + task_width > new_container_left + new_container_width ? new_container_left + new_container_width - task_width : new_task_left;
					new_task_left = new_task_left >= new_container_left ? new_task_left : new_container_left;
				
				
					if (diff_height < 0 && task_offset.top + task_height > new_container_top + new_container_height) {
						new_task_top = (task_offset.top - container_offset.top) * perc_height + container_offset.top;
					}
					else {
						new_task_top = task_offset.top;
					}
				
					new_task_top += diff_top;
					new_task_top = new_task_top + task_height > new_container_top + new_container_height ? new_container_top + new_container_height - task_height : new_task_top;
					new_task_top = new_task_top >= new_container_top ? new_task_top : new_container_top;
				
				
					if (new_task_left != task_offset.left || new_task_top != task_offset.top) {
						j_task.offset({left: parseInt(new_task_left), top: parseInt(new_task_top)});
						//console.log("left "+task.id+":"+task_offset.left+" | "+new_task_left+"=="+j_task.offset().left);
						//console.log((diff_height == 0 ? "equal" : (diff_height < 0 ? "decrease" : "increase"))+" top "+task.id+":"+task_offset.top+" | "+j_task.offset().top);
						
						WF.jsPlumbTaskFlow.repaintTask(j_task);
					}
				}
			}
		},
	
		getContainerTasks : function(container_id) {
			var container_tasks = new Array();
		
			var container = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id + " #" + container_id);
			var container = container ? container[0] : null;
		
			if (container && container.id) {
				var j_container = $(container);
			
				var container_offset = j_container.offset();
				var container_width = j_container.width();
				var container_height = j_container.height();
			
				var tasks = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id + " ." + WF.jsPlumbTaskFlow.task_class_name);
				var task, j_task, task_offset, task_size, task_width, task_height, is_inside;
		
				for (var i = 0; i < tasks.length; i++) {
					task = tasks[i];
					j_task = $(task);
			
					task_offset = j_task.offset();
					task_size = this.getTaskSize(task);
					task_width = task_size.width;
					task_height = task_size.height;
		
					is_inside = task_offset.left >= container_offset.left && task_offset.left < container_offset.left + container_width && 
							task_offset.top >= container_offset.top && task_offset.top < container_offset.top + container_height;
				
					if (is_inside) {
						container_tasks.push(task);
					}
				}
			}
		
			return container_tasks;
		},
	
		getTaskSize : function(task) {
			var j_task = $(task);
		
			var task_width = parseInt(j_task.outerWidth());
			var task_height = parseInt(j_task.outerHeight());
		
			return {width: task_width, height: task_height};
		}
	};

	WF.jsPlumbTaskFile = { 
		get_tasks_file_url : null,
		set_tasks_file_url : null,
		
		update_task_panel_id : "tasks_flow_update_task_panel_confirmation_98123", //private var
		update_connection_panel_id : "tasks_flow_update_connection_panel_confirmation_98123", //private var
		update_data : null, //private var
		selected_task_ids : null, //private var
		file_read_date : null, //private var
		file_settings : null,
		file_other_data : null,
		
		auto_save : false,
		auto_save_interval : 5000,
		auto_save_set_interval_func_id : null, //private var
		auto_save_started : false, //private var
		saved_data_obj : null, //private var
		save_options : null,
		
		on_success_read : null,
		on_success_update : null,
		on_success_save : null,
	
		init : function() {
			this.update_task_panel_id += "_" + Math.abs(WF.jsPlumbTaskFlow.main_tasks_flow_obj_id.hashCode());
			this.update_connection_panel_id += "_" + Math.abs(WF.jsPlumbTaskFlow.main_tasks_flow_obj_id.hashCode());
			
			this.read();
			
			if (this.auto_save_interval > 0)
				this.auto_save_set_interval_func_id = setInterval(function() {
					WF.jsPlumbTaskFile.autoSave();
				}, this.auto_save_interval);
		},
	
		read : function(get_tasks_file_url, options) {
			get_tasks_file_url = get_tasks_file_url ? get_tasks_file_url : this.get_tasks_file_url;
			
			if (get_tasks_file_url) {
				var d = new Date();
				this.file_read_date = d.getTime();
				
				//stop auto save to read, otherwise it can save half workflow, bc it wasn't loaded yet...
				this.stopAutoSave();
				
				var ajax_options = {
					url : get_tasks_file_url,
					data : "",
					dataType : "json",
					success : function(data, text_status, jqXHR) {
						WF.jsPlumbTaskFile.parseTasksFileData(data, text_status, jqXHR);
						
						//call success function
						if (options && typeof options["success"] == "function")
							options["success"](data, text_status, jqXHR);
						
						//start auto save function
						WF.jsPlumbTaskFile.startAutoSave({reset_saved_data_obj : true});
					},
					error : function(jqXHR, textStatus, errorThrown) { 
						var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
						WF.jsPlumbStatusMessage.showError("Error: Workflow not loaded!\nPlease try again..." + msg);
						
						if (options && typeof options["error"] == "function")
							options["error"](jqXHR, textStatus, errorThrown);
						
						//stop auto save function
						WF.jsPlumbTaskFile.stopAutoSave();
					},
				};
				
				if (options && options["async"])
					ajax_options["async"] = true;
				
				$.ajax(ajax_options);
		    	}
		},
		
		parseTasksFileData : function(data, text_status, jqXHR) {
			WF.jsPlumbTaskFile.updateFileSettings(data); //save diagram settings
			
			var containers = data && data.containers ? data.containers : null;
			WF.jsPlumbTaskFile.updateContainers(containers);
			
			var tasks = data && data.tasks ? data.tasks : null;
			var loaded_data = WF.jsPlumbTaskFile.prepareLoadedTasks(tasks);
			WF.jsPlumbTaskFile.updateTasks(loaded_data);
			
			if (WF.jsPlumbTaskFile.on_success_read && typeof WF.jsPlumbTaskFile.on_success_read == "function")
				WF.jsPlumbTaskFile.on_success_read(data, text_status, jqXHR);
			
			//we need to resize the containers again bc the updateTasks may increase the tasks_flow panel again...
			WF.jsPlumbContainer.automaticIncreaseContainersSize();
		},
		
		reload : function(get_tasks_file_url, options) {
			//reinit workflow
			WF.reinit();
			
			//call read manually
			this.read(get_tasks_file_url, options);
		},
		
		getWorkFlowData : function() {
			var tasks = {};
			var containers = {};
		
			var main_tasks_flow_obj = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id);
			var sl = main_tasks_flow_obj.scrollLeft();
			var st = main_tasks_flow_obj.scrollTop();
			var o = main_tasks_flow_obj.offset();
			var current_zoom = WF.jsPlumbTaskFlow.getCurrentZoom();
			
			var elms = main_tasks_flow_obj.find("." + WF.jsPlumbContainer.tasks_containers_class_name);	
		
			for (var i = 0; i < elms.length; i++) {
				var container = $(elms[i]);
				
				var container_id = container.attr("id");
				var position = container.position(); //must be position instead of offset, otherwise if there is current_zoom, it will mess the positioning of the containers
				
				//convert position to original and real position without zoom.
				position.left = parseInt(position.left / current_zoom);
				position.top = parseInt(position.top / current_zoom);
				
				//prepare size and position according with zoom
				containers[container_id] = {
					id: container_id, 
					width: parseInt(container.width()), 
					height: parseInt(container.height()), 
					left: position.left + sl, 
					top: position.top + st
				};
			}
			//console.log(containers);
			
			elms = $(WF.jsPlumbTaskFlow.target_selector);
			
			for (var i = 0; i < elms.length; i++) {
				var task = $(elms[i]);
				var is_inner_task = false;
				
				if (!task.parent().is(main_tasks_flow_obj) && WF.jsPlumbTaskFlow.getTaskParentTasks(task)[0])
					is_inner_task = true;
					
				var task_id = task.attr("id");
				tasks[task_id] = {};
				
				var info = task.find(" > ." + WF.jsPlumbTaskFlow.task_label_class_name + " span")[0];
				tasks[task_id].label = $(info).text(); //needs to be .text() and not .html(), bc the span may contain some html bc is contenteditable=true.
				
				tasks[task_id].id = task_id;
				tasks[task_id].start = task.attr("is_start_task") > 0 ? task.attr("is_start_task") : "";
				tasks[task_id].type = task.attr("type");
				tasks[task_id].tag = task.attr("tag");
				
				var position = task.position(); //must be position instead of offset, otherwise if there is current_zoom, it will mess the positioning of the tasks
				
				//convert position to original and real position without zoom.
				position.left = parseInt(position.left / current_zoom);
				position.top = parseInt(position.top / current_zoom);
				
				if (!is_inner_task) {
					position.left += sl;
					position.top += st;
				}
				
				tasks[task_id].offset_left = position.left;
				tasks[task_id].offset_top = position.top;
				tasks[task_id].width = task.outerWidth();//task.width(); //get outer width instead, bc the task can have some paddings
				tasks[task_id].height = task.outerHeight();//task.height(); //get outer height instead, bc the task can have some paddings
				tasks[task_id].properties = WF.jsPlumbTaskFlow.tasks_properties[task_id];
				tasks[task_id].exits = {exit : new Array()};
				
				if (tasks[task_id].properties && tasks[task_id].properties.exits) {
					for (var exit_id in tasks[task_id].properties.exits) {
						var exit = tasks[task_id].properties.exits[exit_id];
						
						if (exit) {
							delete exit.id;
						}
						else {
							exit = "";//this should be a string, otherwise, if an object, the php will parse this as null; Please leave this as a string!
						}
						
						tasks[task_id].properties.exits[exit_id] = exit;
					}
				}
			}
			
			//preparing task inner tasks
			for (var i = 0; i < elms.length; i++) {
				var task = $(elms[i]);
				var task_parent = task.parent();
				
				if (!task_parent.is(main_tasks_flow_obj)) {
					var task_parent_task = WF.jsPlumbTaskFlow.getTaskParentTasks(task).first();
					
					if (task_parent_task[0]) {
						var task_parent_task_id = task_parent_task.attr("id");
						var parent_path = this.getInnerTaskPathForSaving(task_parent, task_parent_task);
						var task_id = task.attr("id");
						//console.log(parent_path);
						
						if (!tasks[task_parent_task_id].hasOwnProperty("tasks"))
							tasks[task_parent_task_id].tasks = {};
						
						tasks[task_parent_task_id].tasks[task_id] = parent_path ? " > " + parent_path : "";
					}
				}
			}
			//console.log(tasks);
		
			var connections = WF.jsPlumbTaskFlow.getConnections();
			
			for (var i = 0; i < connections.length; i++) {
				var connection_id = connections[i].id;
				var source_id = connections[i].sourceId;
				var target_id = connections[i].targetId;
				var label = connections[i].getOverlay("label");
				var type = connections[i].getParameter("connection_exit_type");
				var overlay = connections[i].getParameter("connection_exit_overlay");
				var color = connections[i].getPaintStyle().strokeStyle;
				var properties = WF.jsPlumbTaskFlow.connections_properties[connection_id];
			
				if (!type) {
					type = connections[i].connector.type;
				}
			
				if (!overlay) {
					overlay = WF.jsPlumbTaskFlow.default_connection_overlay;
				}
			
				label = label ? label.getLabel() : WF.jsPlumbTaskFlow.default_label;
				label = label == "" || label == "..." ? WF.jsPlumbTaskFlow.default_label : label;
			
				var exit_id = connections[i].getParameter("connection_exit_id");
			
				//"" + exit_id + "" => this is needed bc the exit_id could be "false" or "null", but the javascript converts this strings to FALSE or to NULL, which gives an error when we try to do tasks[source_id].exits[NULL]. So we just add: "" + exit_id + "" and problem solved!  
				if (exit_id) {
					if (!tasks[source_id].exits["" + exit_id + ""]) {
						tasks[source_id].exits["" + exit_id + ""] = new Array();
					}
			
					tasks[source_id].exits["" + exit_id + ""].push({task_id: target_id, label: label, type: type, overlay: overlay, color: color, properties: properties});
				}
			}
		
			//console.log(tasks);
			//console.log(containers);
			
			var workflow_data = {};
			
			if ($.isPlainObject(WF.jsPlumbTaskFile.file_other_data))
				for (var key in WF.jsPlumbTaskFile.file_other_data)
					if (key != "containers" && key != "tasks" && key != "settings")
						workflow_data[key] = WF.jsPlumbTaskFile.file_other_data[key];
			
			workflow_data["settings"] = WF.jsPlumbTaskFile.file_settings;
			workflow_data["containers"] = containers;
			workflow_data["tasks"] = tasks;
			
			return workflow_data;
		},
		
		setAutoSaveInterval : function(interval) {
			if (interval > 0) {
				this.auto_save_interval = interval;
				
				if (this.auto_save_set_interval_func_id)
					clearInterval(this.auto_save_set_interval_func_id);
				
				this.auto_save_set_interval_func_id = setInterval(function() {
					WF.jsPlumbTaskFile.autoSave();
				}, this.auto_save_interval);
			}
		},
		
		startAutoSave : function(options) {
			this.auto_save_started = true;
			
			if (typeof options == "object" && options["reset_saved_data_obj"]) {
				var data = this.getWorkFlowData();
				this.saved_data_obj = JSON.stringify(data);
			}
		},
		
		stopAutoSave : function(reset) {
			this.auto_save_started = false;
		},
		
		autoSave : function() {
			if (this.auto_save_started && this.auto_save && this.isWorkFlowChangedFromLastSaving())
				this.save(); //Note that the saved_data_obj var will be changed with the new value in the save method.
		},
		
		isWorkFlowChangedFromLastSaving : function() {
			var data = this.getWorkFlowData();
			var new_data_obj = JSON.stringify(data);
			
			return this.saved_data_obj != new_data_obj;
		},
		
		save : function(set_tasks_file_url, options) {
			var status = false;
			
			set_tasks_file_url = set_tasks_file_url ? set_tasks_file_url : this.set_tasks_file_url;
		
			if (set_tasks_file_url) {
				var old_saved_data_obj = this.saved_data_obj;
				var data = this.getWorkFlowData();
				this.saved_data_obj = JSON.stringify(data);
				
				var post_data = {"save" : true, "data" : data};
				
				options = $.isPlainObject(options) ? options : ($.isPlainObject(this.save_options) ? this.save_options : {});
				var overwrite = options["overwrite"];
				var silent = options["silent"];
				var do_not_silent_errors = options["do_not_silent_errors"];
				
				if (!overwrite) 
					post_data["file_read_date"] = this.file_read_date;
				
				$.ajax({
					type : 'post',
					url : set_tasks_file_url,
					data : post_data,
					dataType : "text",
					success : function(data, textStatus, jqXHR) {
						if (data.trim() == "1") {
							status = true;
							!silent && WF.jsPlumbStatusMessage.showMessage("Workflow saved successfully!");
						}
						else if (data.trim() == "2") {
							WF.jsPlumbTaskFile.saved_data_obj = old_saved_data_obj; //if error, sets the old obj so it tries again the next time... This is used in the autoSave function.
							
							if (confirm("The system detected that this workflow was changed by someone else and the current workflow that you are seeing is not the most updated.\nPlease choose one of the following options:\n- Click continue to save your changes and overwrite the other people's changes.\n- Or click cancel and then refresh this workflow, make your new changes and then click save again."))
								status = WF.jsPlumbTaskFile.save(set_tasks_file_url, options);
						}
						else {
							WF.jsPlumbTaskFile.saved_data_obj = old_saved_data_obj; //if error, sets the old obj so it tries again the next time... This is used in the autoSave function.
							
							(!silent || do_not_silent_errors) && WF.jsPlumbStatusMessage.showError("Error: Workflow saved unsuccessfully!\nPlease try again..." + (data ? "\n" + data : ""));
						}
						
						if (WF.jsPlumbTaskFile.on_success_save && typeof WF.jsPlumbTaskFile.on_success_save == "function")
							WF.jsPlumbTaskFile.on_success_save(data, textStatus, jqXHR);
						
						if (typeof options["success"] == "function")
							options["success"](data, textStatus, jqXHR);
					},
					error : function(jqXHR, textStatus, errorThrown) { 
						WF.jsPlumbTaskFile.saved_data_obj = old_saved_data_obj; //if error, sets the old obj so it tries again the next time... This is used in the autoSave function.
						
						var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
						(!silent || do_not_silent_errors) && WF.jsPlumbStatusMessage.showError("Error: Workflow saved unsuccessfully!\nPlease try again..." + msg);
						
						if (typeof options["error"] == "function")
							options["error"](jqXHR, textStatus, errorThrown);
					},
					async : false,
					timeout : options["timeout"] ? options["timeout"] : 0,
				});
			}
		
			return status;
		},
	
		update : function(get_tasks_file_url, options) {
			get_tasks_file_url = get_tasks_file_url ? get_tasks_file_url : this.get_tasks_file_url;
			
			if (get_tasks_file_url) {
				var status = true;
				
				if (this.isWorkFlowChangedFromLastSaving() && confirm("Do you wish to save this workflow before you proceed?\nOtherwise all the unsaved changes could be lost..."))
					status = this.save(null, options);
				
				if (status) {
					var div = document.getElementById(this.update_task_panel_id);
					
					if (!div) {
						div = document.createElement("DIV");
						div.id = this.update_task_panel_id;
						div.className = "tasks_flow_update_panel_confirmation myfancypopup";
				
						$("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id).append(div);
				
						var div_html = '<div class="title">Please select the tasks that you wish to update:</div>'
							+ '<div class="select_buttons">'
								+ '<a onClick="$(\'#' + this.update_task_panel_id + ' .content input\').attr(\'checked\', \'checked\')">Select All</a>'
								+ '<a onClick="$(\'#' + this.update_task_panel_id + ' .content input\').removeAttr(\'checked\')">Deselect All</a>'
							+ '</div>'
							+ '<div class="content"></div>'
							+ '<div class="buttons">'
								+ '<input type="button" class="continue" value="Continue" onClick="$(this).attr(\'disabled\', \'disabled\');return ' + jsPlumbWorkFlowObjVarName + '.jsPlumbTaskFile.startUpdate();" />'
								+ '<input type="button" class="cancel" value="Cancel" onClick="$(this).attr(\'disabled\', \'disabled\');return ' + jsPlumbWorkFlowObjVarName + '.jsPlumbTaskFile.cancelUpdate();" />'
							+ '</div>';
						
						$(div).html(div_html);
					}
					
					MyFancyPopupClone.hidePopup(); //hide previous popup, if is open
					
					$(div).find(".buttons input").removeAttr("disabled"); //reset previous clicks
					
					MyFancyPopupClone.init({
						elementToShow : div
					});
					MyFancyPopupClone.showOverlay();
					MyFancyPopupClone.showLoading();
					
					options = $.isPlainObject(options) ? options : {};
					
					$.ajax({
						url : get_tasks_file_url,
						data : "",
						dataType : "json",
						success : function(data, text_status, jqXHR) {
							WF.jsPlumbTaskFile.update_data = data;
							WF.jsPlumbTaskFile.selected_task_ids = null;
							
							var tasks = data && data.tasks ? data.tasks : null;
							if (tasks) {
								var existent_tasks = new Array();
						
								var elms = $(WF.jsPlumbTaskFlow.target_selector);
								for (var i = 0; i < elms.length; i++) {
									var task_id = $(elms[i]).attr("id");
							
									if (task_id)
										existent_tasks.push(task_id);
								}
						
								var tasks_html = "";
						
								for (var task_id in tasks) {
									var task = tasks[task_id];
							
									if (task.id) {
										var exists = $.inArray(task.id, existent_tasks) != -1;
								
										tasks_html += '<div class="updated_task">'
												+ '<input type="checkbox" task_id="' + task.id + '" value="1"' + (!exists ? ' checked="checked"' : '') + ' />'
												+ '<label title="' + ("" + task.label).replace(/"/g, "&quot;") + '">' + task.label + '</label>'
											+ '</div>';
									}
								}
						
								$(div).find(".content").html(tasks_html);
							
								MyFancyPopupClone.showPopup();
							}
							else {
								WF.jsPlumbStatusMessage.showMessage("No DB tables available...");
								MyFancyPopupClone.hidePopup();
							}
						},
						error : function(jqXHR, textStatus, errorThrown) { 
							var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
							WF.jsPlumbStatusMessage.showError("Error: Workflow not loaded!\nPlease try again..." + msg);
							
							if (typeof options["error"] == "function")
								options["error"](jqXHR, textStatus, errorThrown);
						},
					});
				}
		    	}
		},
	
		startUpdate : function() {
			var div = document.getElementById(this.update_connection_panel_id);
		
			if (!div) {
				div = document.createElement("DIV");
				div.id = this.update_connection_panel_id;
				div.className = "tasks_flow_update_panel_confirmation myfancypopup";
		
				$("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id).append(div);
		
				var div_html = '<div class="title">Please select the connections that you wish to keep:</div>'
					+ '<div class="select_buttons">'
						+ '<a onClick="$(\'#' + this.update_connection_panel_id + ' .content input\').attr(\'checked\', \'checked\')">Select All</a>'
						+ '<a onClick="$(\'#' + this.update_connection_panel_id + ' .content input\').removeAttr(\'checked\')">Deselect All</a>'
					+ '</div>'
					+ '<div class="content">'
						+ '<table><tr>'
							+ '<th class="existent_connections">Existent Connections</th>'
							+ '<th class="new_connections">New Connections</th>'
						+ '</tr><tr>'
							+ '<td class="existent_connections"></td>'
							+ '<td class="new_connections"></td>'
						+ '</tr></table>'
					+ '</div>'
					+ '<div class="buttons">'
						+ '<input type="button" class="continue" value="Continue" onClick="$(this).attr(\'disabled\', \'disabled\');return ' + jsPlumbWorkFlowObjVarName + '.jsPlumbTaskFile.continueUpdate();" />'
						+ '<input type="button" class="cancel" value="Cancel" onClick="$(this).attr(\'disabled\', \'disabled\');return ' + jsPlumbWorkFlowObjVarName + '.jsPlumbTaskFile.cancelUpdate();" />'
					+ '</div>';
				
				$(div).html(div_html);
			}
			
			var inputs = $("#" + this.update_task_panel_id + " .content .updated_task input");
		
			var selected_task_ids = new Array();
			for (var i = 0; i < inputs.length; i++) {
				var j_input = $(inputs[i]);
		
				if (j_input.is(":checked"))
					selected_task_ids.push( j_input.attr("task_id") );
			}
		
			this.selected_task_ids = selected_task_ids;
			
			MyFancyPopupClone.hidePopup(); //hide previous popup, if is open
			
			$(div).find(".buttons input").removeAttr("disabled"); //reset previous clicks
			
			MyFancyPopupClone.init({
				elementToShow : div,
				onClose : function() {
					WF.jsPlumbTaskFile.cancelUpdate();
				}
			});
			MyFancyPopupClone.showOverlay();
			MyFancyPopupClone.showLoading();
			
			var tasks = this.update_data && this.update_data.tasks ? this.update_data.tasks : null;
			if (tasks) {
				//PREPARE CONNECTIONS
				var existent_connections = {};
				var new_connections = {};
				var similar_connections = {};
			
				var task_labels = {};
				for (var task_id in tasks) {
					var task = tasks[task_id];
				
					task_labels[ task.id ] = task.label;
				}
			
				for (var task_id in tasks) {
					var task = tasks[task_id];

					if (task.id) {
						if ($.inArray(task.id, selected_task_ids) != -1) {
							var connections = WF.jsPlumbTaskFlow.getSourceConnections(task.id);	
							for (var i in connections) {
								var connection = connections[i];
						
								var exit_id = connection.getParameter("connection_exit_id");
						
								var arr = existent_connections[task.id];
								arr = !arr ? new Array() : arr;
								
								if (arr.indexOf(connection.targetId) == -1) //only if exists yet
									arr.push(connection.targetId);
						
								existent_connections[task.id] = arr;
						
								if (task.exits && task.exits[exit_id]) {
									var arr = similar_connections[task.id];
									arr = !arr ? new Array() : arr;
									arr.push(connection.targetId);
							
									similar_connections[task.id] = arr;
								}
							}
						
							connections = WF.jsPlumbTaskFlow.getTargetConnections(task.id);	
							for (var i in connections) {
								var connection = connections[i];
						
								var exit_id = connection.getParameter("connection_exit_id");
						
								var arr = existent_connections[connection.sourceId];
								arr = !arr ? new Array() : arr;
								
								if (arr.indexOf(task.id) == -1) //only if exists yet
									arr.push(task.id);
								
								existent_connections[connection.sourceId] = arr;
								
								var source_task = tasks[connection.sourceId];
								
								if (!source_task) {
									source_task = WF.jsPlumbTaskFlow.tasks_properties[connection.sourceId];
									
									if (source_task && !task_labels[ connection.sourceId ])
										task_labels[ connection.sourceId ] = WF.jsPlumbTaskFlow.getTaskLabelByTaskId(connection.sourceId);
								}
								
								if (source_task && source_task.exits && source_task.exits[exit_id]) {
									var arr = similar_connections[source_task.id];
									arr = !arr ? new Array() : arr;
									arr.push(task.id);
									
									similar_connections[source_task.id] = arr;
								}
							}
					
							if (task.exits)
								for (var exit_id in task.exits) {
									var exits = task.exits[exit_id];
									exits = $.isArray(exits) ? exits : [exits];
							
									for (var i = 0; i < exits.length; i++) {
										var exit = exits[i];
										
										if ($.inArray(exit.task_id, selected_task_ids) != -1 || WF.jsPlumbTaskFlow.getTaskById(exit.task_id).length > 0) { //If task has a connection with another selected task or with an existent task in the workflow.
											var arr = new_connections[task.id];
											arr = !arr ? new Array() : arr;
											arr.push(exit.task_id);
							
											new_connections[task.id] = arr;
										}
									}
								}
						}
						else if (task.exits && WF.jsPlumbTaskFlow.getTaskById(task.id).length > 0) //if task is not selected but exists in workflow and has a connection to a selected task.
							for (var exit_id in task.exits) {
								var exits = task.exits[exit_id];
								exits = $.isArray(exits) ? exits : [exits];
								
								for (var i = 0; i < exits.length; i++) {
									var exit = exits[i];
									
									if ($.inArray(exit.task_id, selected_task_ids) != -1) {
										var arr = new_connections[task.id];
										arr = !arr ? new Array() : arr;
										arr.push(exit.task_id);
					
										new_connections[task.id] = arr;
									}
								}
							}
					}
				}
			
				//SHOW CONNECTIONS
				var existent_html = "";
				for (var source_id in existent_connections) {
					for (var i = 0; i < existent_connections[source_id].length; i++) {
						var target_id = existent_connections[source_id][i];
						var is_similar = $.inArray(target_id, similar_connections[source_id]) != -1;
						var label = task_labels[source_id] + ' => ' + task_labels[target_id];
						
						existent_html += '<div class="existent_connection">'
							+ '<input type="checkbox" source_id="' + source_id + '" target_id="' + target_id + '"' + (is_similar ? '' : 'checked="checked"') + ' />'
							+ '<label title="' + label.replace(/"/g, "&quot;") + '">' + label + '</label>'
						+ '</div>';
					}
				}
			
				var new_html = "";
				for (var source_id in new_connections) {
					for (var i = 0; i < new_connections[source_id].length; i++) {
						var target_id = new_connections[source_id][i];
						var is_similar = $.inArray(target_id, similar_connections[source_id]) != -1;
						var label = task_labels[source_id] + ' => ' + task_labels[target_id];
						
						new_html += '<div class="new_connection">'
							+ '<input type="checkbox" source_id="' + source_id + '" target_id="' + target_id + '"' + (is_similar ? '' : 'checked="checked"') + ' />'
							+ '<label title="' + label.replace(/"/g, "&quot;") + '">' + label + '</label>'
						+ '</div>';
					}
				}
			
				if (existent_html || new_html) {
					var j_div = $("#" + this.update_connection_panel_id);
				
					j_div.find(".content td.existent_connections").html(existent_html);
					j_div.find(".content td.new_connections").html(new_html);
				
					MyFancyPopupClone.showPopup();
				}
				else
					this.continueUpdate();
			}
		},
	
		continueUpdate : function() {
			//PREPARE SELECTED CONNECTIONS
			var new_inputs = $("#" + this.update_connection_panel_id + " .content .new_connection input");
			var existent_inputs = $("#" + this.update_connection_panel_id + " .content .existent_connection input");
			
			var selected_new_connections = {};
			for (var i = 0; i < new_inputs.length; i++) {
				var j_input = $(new_inputs[i]);
		
				if (j_input.is(":checked")) {
					var source_id = j_input.attr("source_id");
				
					var arr = selected_new_connections[source_id];
					arr = !arr ? new Array() : arr;
					arr.push(j_input.attr("target_id"));
		
					selected_new_connections[source_id] = arr;
				}
			}
		
			var selected_existent_connections = {};
			for (var i = 0; i < existent_inputs.length; i++) {
				var j_input = $(existent_inputs[i]);
		
				if (j_input.is(":checked")) {
					var source_id = j_input.attr("source_id");
				
					var arr = selected_existent_connections[source_id];
					arr = !arr ? new Array() : arr;
					arr.push(j_input.attr("target_id"));
		
					selected_existent_connections[source_id] = arr;
				}
			}
			
			//UPDATE DIAGRAM SETTINGS
			this.updateFileSettings(this.update_data);
			
			//UPDATE CONTAINERS
			var containers = this.update_data && this.update_data.containers ? this.update_data.containers : null;
			this.updateContainers(containers);
		
			//PREPARE TASKS AND CONNECTIONS
			var selected_task_ids = this.selected_task_ids;
			var tasks = this.update_data && this.update_data.tasks ? this.update_data.tasks : null;
			this.prepareNewSelectedTasksOffsets(tasks, selected_task_ids);
			var loaded_data = this.prepareLoadedTasks(tasks);
			var loaded_tasks = loaded_data[0];
			
			if (loaded_tasks && selected_task_ids) {
				var loaded_connections = loaded_data[1];
			
				var new_loaded_tasks = new Array();
				var new_loaded_connections = new Array();
				
				//PREPARE TASKS
				for (var i = 0; i < loaded_tasks.length; i++) {
					var loaded_task = loaded_tasks[i];
					var task = loaded_task[0];
				
					if ($.inArray(task.id, selected_task_ids) != -1) {
						new_loaded_tasks.push( assignObjectRecursively({}, loaded_task) );
					
						//PREPARE EXISTENT CONNECTIONS - FROM AND TO CONNECTIONS
						var connections_props = new Array();
						
						//preparing source connections
						var connections = WF.jsPlumbTaskFlow.getSourceConnections(task.id);
						for (var j in connections) {
							var connection = connections[j];
						
							if ($.inArray(connection.targetId, selected_existent_connections[task.id]) != -1) {
								var exit_id = connection.getParameter("connection_exit_id");
								var connection_exit_props = task.properties && task.properties.exits ? task.properties.exits[exit_id] : null;
								
								if ($.isPlainObject(connection_exit_props) && !connection_exit_props.hasOwnProperty("id"))
									connection_exit_props.id = exit_id;
								
								connections_props.push([task.id, connection.targetId, connection, connection_exit_props]);
							}
						}
						
						//preparing target connection
						var connections = WF.jsPlumbTaskFlow.getTargetConnections(task.id);
						for (var j in connections) {
							var connection = connections[j];
							
							if ($.inArray(task.id, selected_existent_connections[connection.sourceId]) != -1) {
								var exit_id = connection.getParameter("connection_exit_id");
								var task_properties = WF.jsPlumbTaskFlow.tasks_properties[connection.sourceId];
								var connection_exit_props = task_properties && task_properties.exits ? task_properties.exits[exit_id] : null;
								
								if ($.isPlainObject(connection_exit_props) && !connection_exit_props.hasOwnProperty("id"))
									connection_exit_props.id = exit_id;
								
								connections_props.push([connection.sourceId, task.id, connection, connection_exit_props]);
							}
						}
						
						//preparing both source and target connections
						for (var j = 0; j < connections_props.length; j++) {
							var c = connections_props[j];
							var source_id = c[0];
							var target_id = c[1];
							var connection = c[2];
							var connection_exit_props = c[3];
							
							var label = connection.getOverlay("label");
							var type = connection.getParameter("connection_exit_type");
							var overlay = connection.getParameter("connection_exit_overlay");
							var color = connection.getPaintStyle().strokeStyle;
							var properties = WF.jsPlumbTaskFlow.connections_properties[connection.id];
						
							label = label ? label.getLabel() : WF.jsPlumbTaskFlow.default_label;
						
							var exit = {task_id: target_id, label: label, type: type, overlay: overlay, color: color, properties: properties};
							
							new_loaded_connections.push([{id: source_id}, exit, connection_exit_props]);
						}
					
						//REMOVE OLD TASK, SO WE CAN ADD A NEW ONE LATER
						WF.jsPlumbTaskFlow.deleteTask(task.id, {confirm: false});
					}
				}
				
				//PREPARE NEW CONNECTIONS
				for (var i = 0; i < loaded_connections.length; i++) {
					var loaded_connection = loaded_connections[i];
					var task = loaded_connection[0];
				
					if ($.inArray(task.id, selected_task_ids) != -1 || WF.jsPlumbTaskFlow.getTaskById(task.id).length > 0) {
						var exit = loaded_connection[1];
					
						if ($.inArray(exit.task_id, selected_new_connections[task.id]) != -1)
							new_loaded_connections.push(loaded_connection);
						else if ($.inArray(task.id, selected_new_connections[exit.task_id]) != -1)
							new_loaded_connections.push(loaded_connection);
					}
				}
				
				MyFancyPopupClone.showLoading();
				
				$("#" + this.update_connection_panel_id).fadeOut("fast", function() {
					//UPDATE TASKS AND CONNECTIONS
					var new_loaded_data = [ new_loaded_tasks, new_loaded_connections ];
					
					//console.log(new_loaded_data);
					WF.jsPlumbTaskFile.updateTasks(new_loaded_data);
					
					if (WF.jsPlumbTaskFile.on_success_update && typeof WF.jsPlumbTaskFile.on_success_update == "function") {
						WF.jsPlumbTaskFile.on_success_update(this.update_data);
						
						WF.jsPlumbTaskFile.cancelUpdate();
					}
					else
						WF.jsPlumbTaskFile.cancelUpdate();
				});
			}
			else {
				//This is the cancel function, but in this case should be used in here too, so it can hide the popup and clean the tmp fields like: .content, .content td.existent_connections and .content td.new_connections
				this.cancelUpdate();
			}
		},
	
		cancelUpdate : function() {
			MyFancyPopupClone.hidePopup();
		
			this.update_data = null;
			this.selected_task_ids = null;
			
			$("#" + this.update_task_panel_id + " .content").html("");
			$("#" + this.update_connection_panel_id + " .content td.existent_connections").html("");
			$("#" + this.update_connection_panel_id + " .content td.new_connections").html("");
		},
		
		updateFileSettings : function(data) {
			//save tasks diagram settings
			this.file_settings = {};
			this.file_other_data = {};
			
			if (data)
				for (var key in data) {
					if (key == "settings")
						this.file_settings = data[key];
					else if (key != "containers" && key != "tasks")
						this.file_other_data[key] = data[key];
				}
		},
	
		updateContainers : function(containers) {
			if (containers) {
				var main_tasks_flow_obj = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id);
				var sl = parseInt(main_tasks_flow_obj.scrollLeft());
				var st = parseInt(main_tasks_flow_obj.scrollTop());
				var o = main_tasks_flow_obj.offset();
				
				for (var container_id in containers) {
					var container = containers[container_id];
					
					if (container.id) {
						var j_container = $("#" + container.id);
					
						if (container.width) 
							j_container.css("width", container.width + "px");
					
						if (container.height)
							j_container.css("height", container.height + "px");
					
						if (container.left && container.top) {
							j_container.css("position", "absolute");
							j_container.offset({left: parseInt(o.left) + parseInt(container.left) - sl, top: parseInt(o.top) + parseInt(container.top) - st});
						}
					}
				}
			
				WF.jsPlumbContainer.initContainersSettings();
				WF.jsPlumbContainer.automaticIncreaseContainersSize();
			}
		},
	
		updateTasks : function(loaded_data) {
			var loaded_tasks = loaded_data[0];
			var loaded_connections = loaded_data[1];
			
			if (loaded_tasks) {
				//load tasks creating them in the main_tasks_flow panel
				for (var i = 0; i < loaded_tasks.length; i++) {
					var loaded_task = loaded_tasks[i];
					var task = loaded_task[0];
					var offset = loaded_task[1];
					var connection_exits_props = loaded_task[2];
					
					WF.jsPlumbTaskFlow.tasks_properties[task.id] = task.properties;
					var task_props = {
						task_type: task.type, 
						task_tag: task.tag, 
						task_id: task.id, 
						task_label: task.label, 
						task_title: task.title, 
						connection_exits_props: connection_exits_props, 
						is_start_task: task.start, 
						offset: offset, 
						width: task.width, 
						height: task.height
					};
					
					if (WF.jsPlumbContextMenu.loadTask(task_props) != task.id)
						WF.jsPlumbStatusMessage.showError("Error trying to create task " + task.type + " with id '" + task.id + "' and label '" + task.label + "'.");
				}
		 		
		 		//preparing connections, connecting tasks between them
				for (var i = 0; i < loaded_connections.length; i++) {
					var loaded_connection = loaded_connections[i];
					var task = loaded_connection[0];
					var exit = loaded_connection[1];
					var connection_exit_props = loaded_connection[2];
					var exit_label = exit.hasOwnProperty("label") ? exit.label : (connection_exit_props && connection_exit_props.hasOwnProperty("label") ? connection_exit_props["label"] : null);
					
					var connection = WF.jsPlumbTaskFlow.connect(task.id, exit.task_id, exit_label, exit.type, exit.overlay, connection_exit_props);
			
					if (exit.properties && connection.id)
						WF.jsPlumbTaskFlow.connections_properties[connection.id] = exit.properties;
				}
				
				//preparing inner tasks, checking if exists inner tasks and add them to the correspondent parent
				for (var i = 0; i < loaded_tasks.length; i++) {
					var loaded_task = loaded_tasks[i];
					var task = loaded_task[0];
					
					if (task.hasOwnProperty("tasks") && task.tasks) {
						var inner_tasks = task.tasks;
						var task_elm = WF.jsPlumbTaskFlow.getTaskById(task.id);
						
						if (task_elm[0]) {
							var droppable = null;
							var is_resizable_task = WF.jsPlumbProperty.isTaskSettingTrue(task.type, "is_resizable_task", false);
							
							for (var inner_task_id in inner_tasks) {
								var path = inner_tasks[inner_task_id];
								var inner_task_elm = WF.jsPlumbTaskFlow.getTaskById(inner_task_id);
								
								if (!inner_task_elm[0]) {
									inner_task_id = WF.jsPlumbTaskFlow.getRealTaskIdFromCaseInsensitiveId(inner_task_id);
									inner_task_elm = WF.jsPlumbTaskFlow.getTaskById(inner_task_id);
								}
								
								if (inner_task_elm[0]) {
									droppable = path ? task_elm.find(path).first() : task_elm;
									
									if (droppable[0]) {
										/* DEPRECATED: No need to do this anymore bc the inner tasks when are saved, are already saved with the relative positioning of the parent.
										var droppable_offset = droppable.offset();
										var inner_task_offset = inner_task_elm.offset();
										
										//append inner tasks to real droppable parent
										droppable.append(inner_task_elm);
										//console.log(droppable[0]);
										
										//set position based on top - ito
										var top = inner_task_offset.top - droppable_offset.top;
										var left = inner_task_offset.left - droppable_offset.left;
										//console.log(top);
										
										inner_task_elm.css({
											top: (top > 0 ? top : 20) + "px",
											left: (left > 0 ? left : 10) + "px",
										});*/
										droppable.append(inner_task_elm);
									}
								}
							}
							
							//Do not add the 'WF.jsPlumbTaskFlow.resizeTaskParentTask(droppable)' bc since we are loading the width and height of the task, we don't need this anymore, and there could be a situation where the user really wants a specific size and if we call resizeTaskParentTask the size will be overwrited.
							//resize droppable task and its' parents with the new width and height according with the new dropped inner task
							//droppable = droppable ? droppable : task_elm.children().first();
							//WF.jsPlumbTaskFlow.resizeTaskParentTask(droppable);
						}
					}
				}
				
				//this is the hacking to fix the connection layout issue, when the file is reloaded.
				WF.jsPlumbTaskFlow.repaintAllTasks();
			}
		},
		
		prepareNewSelectedTasksOffsets : function(tasks, selected_task_ids) {
			if (tasks && selected_task_ids) {
				//get current tasks data
				var workflow_data = this.getWorkFlowData();
				var tasks_data = workflow_data["tasks"];
				
				//get biggest bottom point from all tasks
				var biggest_bottom = 0;
				var biggest_right = 0;
				
				$.each(tasks_data, function(task_id, task_data) {
					var right = task_data.offset_left + task_data.width;
					var bottom = task_data.offset_top + task_data.height;
					
					if (bottom > biggest_bottom)
						biggest_bottom = parseInt(bottom);
					
					if (right > biggest_right)
						biggest_right = parseInt(right);
				});
				
				//set some spacing from the limit
				biggest_bottom += 50;
				biggest_right += 50;
				
				//prepare offset groups
				var top = 10;
				var left = 10;
				var biggest_height = 0;
				
				for (var task_id in tasks) {
					var task = tasks[task_id];
					
					if (task.id && $.inArray(task.id, selected_task_ids) != -1) { //if task is selected
						var task_data = tasks_data[task.id];
						
						if ($.isPlainObject(task_data)) { //if task already exists
							task.offset_left = task_data.offset_left;
							task.offset_top = task_data.offset_top;
						}
						else { //if task not exists yet
							//check if top and left are inside of forbidden area
							while (left <= biggest_right && top <= biggest_bottom) {
								left = biggest_right + 50;
								
								if (left > 1010) { //in case the biggest_right be close to the 1010 limit.
									left = 10;
									top += 100; //it could be some other value. This is only to make it quickly. I lower value could make the system slow and a biggest could leave the tasks with big gaps.
								}
							}
							
							//update task offset
							task.offset_left = left;
							task.offset_top = top;
							
							//set next offsets
							var task_width = task.width ? task.width : 200;
							var task_height = task.height ? task.height : 250;
							
							if (task_height > biggest_height)
								biggest_height = task_height;
							
							left += task_width + 50;
								
							if (left > 1010) {
								left = 10;
								top += biggest_height + 50;
								biggest_height = 0;
							}
						}
					}
				}
			}
		},
	
		prepareLoadedTasks : function(tasks) {
			var loaded_tasks = new Array();
			var loaded_connections = new Array();
		
			if (tasks) {
				var main_tasks_flow_obj = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id);
				
				var exits_props_by_task = {};
	
				for (var task_id in tasks) {
					var task = tasks[task_id];
		
					if (task.id) {
						var offset = {
							left : parseInt(task.offset_left),
							top : parseInt(task.offset_top)
						};
			
						var connection_exits_props = new Array();
						if (task.properties && task.properties.exits) {
							for (var exit_id in task.properties.exits) {
								var exit = task.properties.exits[exit_id];
								//console.log(exit);
					
								//just in case, if there are more than 1 exit with the same id
								if (jQuery.isArray(exit))
									exit = exit[0];
								else if (!exit)
									exit = {};
					
								exit.id = exit_id;
					
								if (!exit.color)
									exit.color = nextColor();
								else
									nextColor();//this is only to update the color indexes so we don't have repeated colors when we create new tasks...
								
								exits_props_by_task[task.id + "_" + exit_id] = exit;
								connection_exits_props.push(exit);
								task.properties.exits[exit_id] = exit;
							}
						}
					
						loaded_tasks.push( [task, offset, connection_exits_props] );
					}
				}
	
				nextColor();//this is only to update the color indexes so we don't have repeated colors when we create new tasks...
				
				for (var task_id in tasks) {
					var task = tasks[task_id];
		
					if (task.id) {
						if (task.exits) {
							for (var exit_id in task.exits) {
								var exit = task.exits[exit_id];
					
								//Maybe there is no connections in this exit, which means the exit object will be null.
								if (exit) {
									var connection_exit_props = exits_props_by_task[task.id + "_" + exit_id];
							
									//just in case, if there are more than 1 exit with the same id
									var connection;
									if (jQuery.isArray(exit)) {
										for (var j = 0; j < exit.length; j++) {
											var exit_item = exit[j];
								
											loaded_connections.push( [task, exit_item, connection_exit_props] );
										}
									}
									else
										loaded_connections.push( [task, exit, connection_exit_props] );
								}
							}
						}
					}
				}
			}
	
			return [ loaded_tasks, loaded_connections ];
		},
			
		getInnerTaskPathForSaving : function(node, until_parent) {
			var path;

			while (node.length) {
				if (until_parent && node.is(until_parent))
					break;
				
				var realNode = node[0];
				var name = realNode.localName;

				if (!name) 
					break;

				name = name.toLowerCase();
				
				var parent = node.parent();
				
				var sameTagSiblings = parent.children(name);

				if (sameTagSiblings.length > 1) { 
					var allSiblings = parent.children();
					var index = allSiblings.index(realNode) + 1;
					
					if (index > 1)
						name += ':nth-child(' + index + ')';
				}

				path = name + (path ? ' > ' + path : '');
				node = parent;
			}

			return path;
	    	}
	};

	WF.jsPlumbStatusMessage = { 
		//isShowMessageActive : false,
		//isShowErrorActive : false,
		
		message_html_obj_id : "workflow_message_98123",
		message_html_obj : null,
		
		init : function() {
			this.message_html_obj_id += "_" + Math.abs(WF.jsPlumbTaskFlow.main_tasks_flow_obj_id.hashCode());
			this.message_html_obj = $('<div id="' + this.message_html_obj_id + '" class="workflow_message"></div>');
			
			$("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id).after(this.message_html_obj);
			
			this.message_html_obj.click(function() {
				WF.jsPlumbStatusMessage.removeMessages();
			});
		},
		
		getMessageHtmlObj : function() {
			return this.message_html_obj;
		},
		
		showMessage : function(message, message_class) {
			var status_message = this.getMessageElement(message, "status" + (message_class ? " " + message_class : ""));
			var message_html_obj = this.getMessageHtmlObj();
			
			try { //if message contains a full html page with head and body we will get a javascript error. So we need to catch it.
				if (!status_message.parent().is(message_html_obj))
					message_html_obj.append(status_message);
			}
			catch(e) {
				message_html_obj = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id).children('#' + this.message_html_obj_id); //sometimes the this.message_html_obj looses the reference for the object
				console.log(message_html_obj);
				
				if (console && console.log)
					console.log(e);
			}
			
			this.prepareMessage(status_message, 5000);
		},
	
		showError : function(message, message_class) {
			var status_message = this.getMessageElement(message, "error" + (message_class ? " " + message_class : ""));
			var message_html_obj = this.getMessageHtmlObj();
			
			try { //if message contains a full html page with head and body we will get a javascript error. So we need to catch it.
				if (!status_message.parent().is(message_html_obj))
					message_html_obj.append(status_message);
			}
			catch(e) {
				message_html_obj = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id).children('#' + this.message_html_obj_id); //sometimes the this.message_html_obj looses the reference for the object
				
				if (console && console.log)
					console.log(e);
			}
			
			this.prepareMessage(status_message, 10000);
		},
		
		getMessageElement : function(message, message_class) {
			var message_html_obj = this.getMessageHtmlObj();
			var width = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id).width() - 15;
			var created_time = (new Date()).getTime();
			var last_msg_elm = message_html_obj.children().last();
			var status_message = null;
			
			//prepare message text
			message = this.parseMessage(message);
			var parts = message.split("\n");
			var height = parts.length * 20 + (message.indexOf("<br") != -1 ? message.split("<br").length * 20 : 0);
			
			//prepare message_class
			message_class = message_class.replace(/\s+/g, " ").replace(/^\s+/g, "").replace(/\s+$/g, "");
			var message_class_selector = message_class.replace(/ /g, ".");
			
			//prepare message element
			if (last_msg_elm.is("." + message_class_selector) && last_msg_elm.data("created_time") + 1500 > created_time) { //if there is already a message created in the previous 1.5seconds, combine this text with that message element.
				status_message = last_msg_elm;
				status_message.children(".close_message").last().before( "<br/>" + message.replace(/\n/g, "<br/>") );
				
				height += parseInt(last_msg_elm.css("min-height"));
			}
			else { //if new message element
				status_message = $('<div class="' + message_class + '">' + message.replace(/\n/g, "<br/>") + '<span class="close_message">close</span></div>');
				
				status_message.css("width", width + "px"); //must be width, bc if is min-width the message won't be centered and the close button won't appear.
				
				status_message.data("created_time", created_time);
			}
			
			//set new height
			status_message.css("min-height", height + "px"); //min-height are important bc if the message is bigger than the height, the message will appear without background
			
			return status_message;
		},
		
		//sometimes the message may contain a full page html with doctype, html, head and body tags. In this case we must remove these tags and leave it with only the body innerHTML, otherwise when we append the message, will throw an exception.
		parseMessage : function(message) {
			if (message) {
				var message_lower = message.toLowerCase();
				
				var pos = message_lower.indexOf("<!doctype");
				if (pos != -1) {
					var end = message_lower.indexOf(">", pos);
					end = end != -1 ? end : message_lower.length;
					message = message.substr(0, pos) + message.substr(end + 1);
					message = message.replace(/(\s)\s+/g, "$1");
					message_lower = message.toLowerCase();
				}
				
				var html_code = this.getMessageHtmlTagContent(message, message_lower.indexOf("<html"), "html");
				html_code = html_code ? html_code[1] : null;
				
				if (html_code) {
					message = message.replace(html_code, "").replace(/(\s)\s+/g, "$1");
					
					var html_code_lower = html_code.toLowerCase();
					var body_code = this.getMessageHtmlTagContent(html_code, html_code_lower.indexOf("<body"), "body");
					body_code = body_code ? body_code[0] : null;
					message += body_code;
				}
				else {
					var head_code = this.getMessageHtmlTagContent(message, message_lower.indexOf("<head"), "head");
					head_code = head_code ? head_code[1] : null;
					
					if (head_code) {
						message = message.replace(head_code, "").replace(/(\s)\s+/g, "$1");
						message_lower = message.toLowerCase();
					}
					
					var body_code = this.getMessageHtmlTagContent(message, message_lower.indexOf("<body"), "body");
					
					if (body_code)
						message = message.replace(body_code[0], body_code[1]).replace(/(\s)\s+/g, "$1");
				}
			}
			
			return message;
		},
		
		getMessageHtmlTagContent : function(text, idx, tag_name) {
			if (typeof MyHtmlBeautify != "undefined") {
				var code = MyHtmlBeautify.getTagContent(text, idx, tag_name);
				return code ? [ code[0], code[2] ] : null;
			}
			
			var text_lower = text.toLowerCase();
			var outer_start = text_lower.indexOf("<" + tag_name, idx);
			
			if (outer_start != -1) {
				var inner_start = text_lower.indexOf(">", outer_start);
				inner_start = inner_start != -1 ? inner_start : text.length;
				var inner_end = inner_start;
				var outer_end = inner_start;
				var is_single = text_lower.substr(outer_start, inner_start - outer_start).match(/[\/]\s*$/);
				
				if (!is_single) {
					inner_end = text_lower.indexOf("</" + tag_name, inner_start);
					inner_end = inner_end != -1 ? inner_end : text.length;
					
					outer_end = text_lower.indexOf(">", inner_end);
					outer_end = outer_end != -1 ? outer_end : text.length;
				}
					
				var inner_code = text.substr(inner_start + 1, (inner_end - 1) - inner_start);
				var outer_code = text.substr(outer_start, (outer_end + 1) - outer_start);
				
				return [inner_code, outer_code];
			}
			
			return null;
		},
		
		prepareMessage : function(status_message, timeout) {
			var message_html_obj = this.getMessageHtmlObj();
			var czi = message_html_obj.css("z-index");
			var z_index = WF.jsPlumbTaskFlow.getTasksFlowElementsMaxZIndex();
			var close_icon = status_message.children(".close_message");
			
			if (czi < z_index)
				message_html_obj.css("z-index", z_index).attr("orig_z_index", czi);
			
			var max_height = parseInt(status_message.css("max-height"));
			var height = parseInt(status_message.css("min-height"));
			
			if (height && max_height && height > max_height)
				status_message.css("min-height", max_height + "px");
			
			var timeout_id = status_message.data("timeout_id");
			timeout_id && clearTimeout(timeout_id);
			
			timeout_id = setTimeout(function() { 
				close_icon.trigger("click");
			}, timeout);
			status_message.data("timeout_id", timeout_id);
			
			status_message.off();
			status_message.click(function(event) {
				event && typeof event.stopPropagation == "function" && event.stopPropagation(); //avoids to call the onClick event from message_html_obj
			});
			status_message.hover(function() { //in
				var timeout_id = status_message.data("timeout_id");
				
				if (timeout_id) {
					clearTimeout(timeout_id);
					status_message.data("timeout_id", null);
				}
			}, function() { //out
				var timeout_id = setTimeout(function() { 
					close_icon.trigger("click");
				}, timeout);
				status_message.data("timeout_id", timeout_id);
			});
			
			close_icon.off();
			close_icon.click(function(event) {
				event && typeof event.stopPropagation == "function" && event.stopPropagation(); //avoids to call the onClick event from message_html_obj
				
				var timeout_id = status_message.data("timeout_id");
				
				WF.jsPlumbStatusMessage.removeMessage(this);
				WF.jsPlumbStatusMessage.getMessageHtmlObj().css("z-index", czi);
				
				if (timeout_id)
					clearTimeout(timeout_id);
			});
		},
		
		removeMessage : function(elm) {
			$(elm).parent().remove();

			/* 2019-10-26: DO NOT ADD THIS CODE, otherwise the other messages inside of the message_html_obj can be hidden bc we are changing the z-index to its original value.
			var message_html_obj = this.getMessageHtmlObj();
			var czi = message_html_obj.attr("orig_z_index");
			
			if (czi) 
				message_html_obj.css("z-index", czi);
			
			message_html_obj.removeAttr("orig_z_index");*/
		},
		
		removeLastShownMessage : function(type) {
			var selector = type ? "." + type : ".status, .error";
			this.getMessageHtmlObj().children(selector).last().remove();
		},
		
		removeMessages : function(type) {
			var selector = type ? "." + type : ".status, .error";
			this.getMessageHtmlObj().children(selector).remove();
		},
	};
	
	WF.jsPlumbTaskSort = {
		task_margins_left_and_right_average : 30,
		task_margins_top_and_bottom_average : 70,
		start_left_position : 100,
		start_top_position : 50,
		
		left : 0,
		top : 0,
		
		start_tasks : null,
		tasks_by_id : null,
		tasks_exits : null,
		sorted_tasks_id : null,
		
		sortTasks : function(sort_type) {
			sort_type = sort_type ? sort_type : 1;
			
			var tasks = $("#" + WF.jsPlumbTaskFlow.main_tasks_flow_obj_id + " ." + WF.jsPlumbTaskFlow.task_class_name);
			var connections = WF.jsPlumbTaskFlow.getConnections();
			
			this.start_tasks = {};
			this.tasks_by_id = {};
			this.tasks_exits = {};
			this.sorted_tasks_id = {};
			
			for (var i = 0; i < tasks.length; i++) {
				var task = tasks[i];
				
				if (task.id) {
					if (task.getAttribute("is_start_task") > 0) 
						this.start_tasks[task.id] = true;
				
					this.tasks_by_id[task.id] = task;
				}
			}
			
			for (var i = 0; i < connections.length; i++) {
				var c = connections[i];
				
				if (c.sourceId && c.targetId) {
					if (!this.tasks_exits[c.sourceId]) {
						this.tasks_exits[c.sourceId] = [];
					}
				
					this.tasks_exits[c.sourceId].push(c.targetId);
				}
			}
			
			var maximum_task_width_per_column = 0;
			this.left = this.start_left_position;
			this.top = this.start_top_position;
			
			var count = 0;
			for (var task_id in this.start_tasks) {
				if (!this.sorted_tasks_id[task.id]) {
					this.top = this.start_top_position;
					this.left += count > 0 ? maximum_task_width_per_column + (this.task_margins_left_and_right_average * 2) : 0;
					maximum_task_width_per_column = this.sortTaskAndItChilds(task_id, sort_type, 0);
					
					count++;
				}
			}
			
			for (var i = 0; i < tasks.length; i++) {
				var task = tasks[i];
				
				if (task.id && !this.sorted_tasks_id[task.id]) {
					this.top = this.start_top_position;
					this.left += maximum_task_width_per_column + (this.task_margins_left_and_right_average * 2);
					
					maximum_task_width_per_column = this.sortTaskAndItChilds(task.id, sort_type, 0);
				}
			}
			
			WF.jsPlumbTaskFlow.repaintAllTasks();
		},
		
		sortTaskAndItChilds : function(task_id, sort_type, maximum_task_width_per_column) {
			var task = $(this.tasks_by_id[task_id]);
			task.css({"top": this.top + "px", "left": this.left + "px"});
			
			//console.log( task.find(".info span").first().text()+":"+this.left+":"+maximum_task_width_per_column+":"+this.task_margins_left_and_right_average );
								
			var w = parseInt(task.width());
			var h = parseInt(task.height());
			
			maximum_task_width_per_column = maximum_task_width_per_column < w ? w : maximum_task_width_per_column;
			this.top += h + (this.task_margins_top_and_bottom_average * 2);
			
			this.sorted_tasks_id[task_id] = true;
			var sub_maximum_task_width_per_column = 0;
			
			var targets_id = this.tasks_exits[task_id];
			if (targets_id) {
				var reset_top = this.top;
				
				var count = 0;
				var left = this.left;
				
				for (var i = 0; i < targets_id.length; i++) {
					var target_task_id = targets_id[i];
					
					if (!this.start_tasks[target_task_id]) {
						if (sort_type == 2 || sort_type == 4 || !this.sorted_tasks_id[target_task_id]) {
							this.top = reset_top;
							
							if (sort_type == 3 || sort_type == 4) {
								left += count > 0 ? sub_maximum_task_width_per_column + (this.task_margins_left_and_right_average * 2) : 0;
								this.left = left;
							}
							else
								this.left += count > 0 ? sub_maximum_task_width_per_column + (this.task_margins_left_and_right_average * 2) : 0;
							
							sub_maximum_task_width_per_column = this.sortTaskAndItChilds(target_task_id, sort_type, 0);
						
							count++;
						}
					}
				}
			}
			
			return maximum_task_width_per_column < sub_maximum_task_width_per_column ? sub_maximum_task_width_per_column : maximum_task_width_per_column;
		},
	};
};
