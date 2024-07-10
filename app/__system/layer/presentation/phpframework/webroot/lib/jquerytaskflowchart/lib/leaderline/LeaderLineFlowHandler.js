/* 
Other TODOs:
- fix droppable feature that is not working on drop arrow.
- update setConnector to have all options defined in available_connection_connectors
*/

var FlowHandlerObj = new LeaderLineFlowHandler();

if (typeof window != 'undefined') 
	window.FlowHandlerObj = FlowHandlerObj;

(function($) {	
	$(document).ready(FlowHandlerObj.init);
})(jQuery);

function LeaderLineFlowHandler() {
	var me = this;
	var docs = [];
	var containers = [];
	var sources = [];
	var targets = [];
	var lines = [];
	var events = {};
	
	var initialized = false;
	var elm_point = null;
	var connect_mode = false;
	var mouse_clicked_elm = null;
	var mouse_dragged = false;
	var dragged_line = null;
	var dragged_line_canvas = null;
	var previous_over_target = null;
	var scrollable_containers = null;
	var available_line_paths = {
		"straight" : "straight",
		"arc" : "arc",
		"fluid" : "fluid",
		"magnet" : "magnet",
		"grid" : "grid",
		"Straight": "straight",
		"Bezier": "arc",
		"StateMachine": "magnet",
		"Flowchart": "grid",
	};
	var available_line_paths_types = {
		"straight": "Straight",
		"arc": "Bezier",
		"magnet": "StateMachine",
		"grid": "Flowchart",
		"fluid": "Fluid",
	};
	var internal_leader_line_options = {
		path: 'fluid', //straight, arc, fluid, magnet, grid
		lineColor: 'coral',
		lineSize: 4,
		plugSE: ['behind', window.DEFAULT_END_PLUG],
		plugSizeSE: [1, 1],
		lineOutlineEnabled: false,
		lineOutlineColor: 'indianred',
		lineOutlineSize: 0.25,
		plugOutlineEnabledSE: [false, false],
		plugOutlineSizeSE: [1, 1]
	};
	me.SVG = "svg"; //bc of jsPlumb
	me.Defaults = {
		endPlug: "arrow3",
		path: "straight", //straight, arc, fluid, magnet, grid
		startPointSVG: '<svg viewBox="0 0 10 10" width="10" height="10"><use href="#leader-line-disc" x="5" y="5" /></svg>',
		endPointSVG: '<svg viewBox="0 0 10 10" width="10" height="10"><use href="#leader-line-disc" x="5" y="5" /></svg>',
		size: 2, //line size
		
		connect_from_target: false,
		on_connection_body_class: "leader-line-dragging-body",
		dragging_hover_target_class: "leader-line-dragging-target-hover",
		dragging_active_target_class: "leader-line-dragging-target-active",
		dragged_line_class: "",
		overlay_class: "",
		end_point_class: "",
		scroll_inc: 50,
		scroll_margin: 10,
		similar_lines_gap: 10,
	};
	me.line_sample_options = {
		startPlug: "diamond",
		endPlug: "arrow3",
		dash: false,
		/*dash: {
			animation: true
		},*/
		path: "straight", //straight, arc, fluid, magnet, grid
		startLabel: '*',
		middleLabel: 'MIDDLE',
		endLabel: '1',
		startLabelStyle: {fill: "red", fontSize: "28px"},
		middleLabelStyle: {fill: "purple", fontSize: "9px"},
		endLabelStyle: {fill: "green", fontSize: "30px"},
		startLabelClass: "",
		middleLabelClass: "test",
		endLabelClass: "",
		startPointSVG: '<svg viewBox="0 0 10 10" width="10" height="10"><use href="#leader-line-disc" x="5" y="5" /></svg>',
		endPointSVG: '<svg viewBox="0 0 10 10" width="10" height="10"><use href="#leader-line-disc" x="5" y="5" /></svg>',
		//startPointColor: null,
		//endPointColor: null,
		//startPlugSize: 1.5,
		startPlugColor: '#1a6be0',
		endPlugSize: 1.5,
		endPlugColor: '#1efdaa',
		color: '#666', 
		hoverColor: "#000",
		hoverPaintStyle: {
			fillStyle:"red",
			strokeStyle: "pink",
			//lineWidth: 4,
		},
		size: 2, //line size
		//gradient: true,
	};
	me.line_options = me.Defaults;
	me.is_ready = false;
	me.is_cloned = false;
	me.zoom = 1;
	me.debug = true;
	me.repaint_enable = true;
	
	me.getInstance = function(options) {
		options = options ? me.mergeOptions(this.line_options, options) : this.line_options;
		
		var l = new LeaderLineFlowHandler();
		l.importDefaults(options);
		l.init();
		
		return l;
	};
	
	me.init = function() {
		if (!initialized) {
			me.makeDocument(document);
			me.makeContainer(me.line_options["container"]);
			me.is_cloned = true;
			initialized = true;
			
			me.onReady();
		}
	};
	
	me.onReady = function() {
		/*var ready_func = function() {
			if (typeof LeaderLine == "function" && document.body) { //document.body is very important otherwise we are loading the ready function when the body doesn't exist yet. Do not use document.readyState === "complete".
				me.is_ready = true;
				
				me.trigger("ready");
			}
			else {
				setTimeout(function() {
					ready_func();
				}, 300);
			}
		}*/
		
		/*function sleep(ms) {
			return new Promise(resolve => setTimeout(resolve, ms));
		}
		
		async function ready_func() {
			var is_leader_line_ready = false;
			
			do {
				is_leader_line_ready = typeof LeaderLine == "function" && document.body; //document.body is very important otherwise we are loading the ready function when the body doesn't exist yet. Do not use document.readyState === "complete".
				
				if (!is_leader_line_ready)
					await sleep(300);
			}
			while (!is_leader_line_ready);
			
			me.is_ready = true;
			me.trigger("ready");
		}
		
		ready_func();*/
		
		if (typeof LeaderLine == "function" && document.body) { //document.body is very important otherwise we are loading the ready function when the body doesn't exist yet. Do not use document.readyState === "complete".
			me.is_ready = true;
			me.trigger("ready");
		}
	};
	
	me.bind = function(event, handler) {
		if (typeof handler == "function") {
			if (event == "ready" && initialized)
				handler();
			else {
				if (!events.hasOwnProperty(event))
					events[event] = [];
				
				events[event].push(handler);
			}
			
			return true;
		}
		
		return false;
	};
	
	me.trigger = function(type, arg_1, arg_2, arg_3, arg_4) {
		var status = true;
		
		if (events.hasOwnProperty(type) && events[type].length > 0)
			for (var i = 0, t = events[type].length; i < t; i++)
				if (events[type][i] && typeof events[type][i])
					if (!events[type][i](arg_1, arg_2, arg_3, arg_4))
						status = false;
		
		return status;
	};
	
	me.destroy = function() {
		this.detachEveryConnection();
		this.deleteEveryEndpoint();
		this.unmakeEverySource();
		this.unmakeEveryTarget();
		this.unmakeEveryDocument();
		this.unmakeEveryContainer();
		
		this.reset();
	};
	
	me.updateEventDraggedLine = function(event) {
		if (connect_mode && dragged_line) {
			var top = (event.clientY + window.pageYOffset);
			var left = (event.clientX + window.pageXOffset);
			
			elm_point.style.top = top + "px";
			elm_point.style.left = left + "px";
			
			me.repaintLine(dragged_line);
			
			var over_elm = me.getEventTarget(event);
			
			if (over_elm) {
				var target = me.getElementTarget(over_elm);
				
				if (previous_over_target != target) {
					var j_previous_over_target = $(previous_over_target);
					var j_target = $(target);
					
					//TODO: droppable events trigger not working 
					if (previous_over_target && j_previous_over_target.is(".ui-droppable")) {
						me.line_options["dragging_hover_target_class"] && j_previous_over_target.removeClass(me.line_options["dragging_hover_target_class"]);
						j_previous_over_target.trigger("out");
					}
					
					if (target && j_target.is(".ui-droppable")) {
						me.line_options["dragging_hover_target_class"] && j_target.addClass(me.line_options["dragging_hover_target_class"]);
						j_target.trigger("over");
					}
					
					previous_over_target = target;
				}
			}
		}
	};
	
	me.getEventTarget = function(event) {
		var over_elm = null;
		
		try {
			over_elm = event.target;
			
			if (over_elm == dragged_line_canvas)
				over_elm = document.elementFromPoint(event.clientX, event.clientY);
			
			if (over_elm == dragged_line_canvas) {
				var direction = me.getLineDirection(dragged_line);
				var diff = 5;
				
				switch (direction) {
					case "top": over_elm = document.elementFromPoint(event.clientX, event.clientY - diff); break;
					case "bottom": over_elm = document.elementFromPoint(event.clientX, event.clientY + diff); break;
					case "left": over_elm = document.elementFromPoint(event.clientX - diff, event.clientY); break;
					case "right": over_elm = document.elementFromPoint(event.clientX + diff, event.clientY); break;
					case "top_left": over_elm = document.elementFromPoint(event.clientX - diff, event.clientY - diff); break;
					case "top_right": over_elm = document.elementFromPoint(event.clientX + diff, event.clientY - diff); break;
					case "bottom_left": over_elm = document.elementFromPoint(event.clientX - diff, event.clientY + diff); break;
					case "bottom_right": over_elm = document.elementFromPoint(event.clientX + diff, event.clientY + diff); break;
				}
				
				if (over_elm == dragged_line_canvas)
					over_elm = null;
			}
		}
		catch (e) {
			if (me.debug && console && console.log)
				console.log(e);
		}
		
		return over_elm;
	};
	
	me.getElementTarget = function(elm) {
		if (elm && targets.length > 0) {
			do {
				if (targets.indexOf(elm) != -1)
					break;
				
				elm = elm.parentNode;
			}
			while (elm);
		}
		
		return elm;
	};
	
	me.getElementSource = function(elm) {
		if (elm && sources.length > 0) {
			do {
				if (sources.indexOf(elm) != -1)
					break;
				
				elm = elm.parentNode;
			}
			while (elm);
		}
		
		return elm;
	};
	
	me.getTargetSources = function(elm) {
		var target_sources = [];
		
		if (elm && targets.length > 0 && sources.length > 0) {
			var target = me.getElementTarget(elm);
			
			if (target)
				for (var i = 0, t = sources.length; i < t; i++)
					if (sources[i].target == target)
						target_sources.push(sources[i]);
		}
		
		return target_sources;
	};
	
	me.getLineContainers = function(elm) {
		var line_containers = [];
		
		if (elm) {
			var parent = $(elm).parent();
			line_containers.push(parent[0]);
		}
		
		if (me.line_options["container"]) {
			var line_container = $(me.line_options["container"])[0];
			
			if (line_container && line_containers.indexOf(line_container) == -1)
				line_containers.push(line_container);
		}
		
		for (var i = 0, t = lines.length; i < t; i++) {
			var line = lines[i];
			
			if (line) {
				var line_container = line.options["container"];
				
				if (line_container) {
					line_container = line_container instanceof jQuery ? line_container[0] : $(line_container)[0];
					
					if (line_container && line_containers.indexOf(line_container) == -1)
						line_containers.push(line_container);
				}
			}
		}
		
		for (var i = 0, t = sources.length; i < t; i++) {
			var source = sources[0];
			var p = $(source).parent()[0];
			
			if (p && line_containers.indexOf(p) == -1)
				line_containers.push(p);
		}
		
		for (var i = 0, t = targets.length; i < t; i++) {
			var target = targets[0];
			var p = $(target).parent()[0];
			
			if (p && line_containers.indexOf(p) == -1)
				line_containers.push(p);
		}
		
		return line_containers;
	};
	
	me.getScrollableContainers = function(line_containers) {
		var containers = [];
		
		for (var i = 0, t = line_containers.length; i < t; i++) {
			var line_container = $(line_containers[i]);
			var overflow = line_container.css("overflow");
			
			if (overflow == "auto" || overflow == "scroll" || overflow == "overlay") {
				var w = line_container.width();
				var h = line_container.height();
				var o = line_container.offset();
				
				containers.push({
					canvas: line_container[0],
					top: o.top,
					left: o.left,
					right: o.left + w,
					bottom: o.top + h,
				});
			}
		}
		
		return containers;
	};
	
	me.autoScrollContainer = function(scrollable_elm, is_scroll_top, is_scroll_left, is_scroll_right, is_scroll_down) {
		scrollable_elm = $(scrollable_elm);
		var scroll_inc = me.line_options["scroll_inc"] ? me.line_options["scroll_inc"] : 50;
		
		if (is_scroll_top || is_scroll_down) { //scroll top or down
			var current_scroll = scrollable_elm.scrollTop();
			current_scroll = is_scroll_top ? current_scroll - scroll_inc : current_scroll + scroll_inc;
			scrollable_elm.scrollTop(current_scroll);
		}
		else if (is_scroll_left || is_scroll_right) { //scroll left or right
			var current_scroll = scrollable_elm.scrollLeft();
			current_scroll = is_scroll_left ? current_scroll - scroll_inc : current_scroll + scroll_inc;
			scrollable_elm.scrollLeft(current_scroll);
		}
	};
	
	me.startDraggingLine = function(event) {
		if (mouse_clicked_elm) {
			var doc = mouse_clicked_elm.ownerDocument || mouse_clicked_elm.document;
			elm_point = doc.createElement("DIV");
			elm_point.className = "leader-line-dragged-line-element-point";
			doc.body.appendChild(elm_point);
			
			dragged_line = me.startConnection({
				source: mouse_clicked_elm, 
				target: elm_point
			});
			
			if (dragged_line) {
				dragged_line_canvas = $(doc).find("body > .leader-line:last-child")[0];
				dragged_line_canvas.classList.add("leader-line-dragged-line");
				
				if (me.line_options.dragged_line_class)
					dragged_line_canvas.classList.add(me.line_options.dragged_line_class);
				
				me.line_options.on_connection_body_class && doc.body.classList.add(me.line_options.on_connection_body_class);
				connect_mode = true;
				me.updateEventDraggedLine(event);
				
				if (me.line_options["dragging_active_target_class"])
					for (var i = 0, t = targets.length; i < t; i++)
						$(targets[i]).addClass(me.line_options["dragging_active_target_class"]);
				
				//trigger connectionDragStart
				me.trigger("connectionDragStart", dragged_line, event);
				
				//trigger connectionDragBegin
				me.trigger("connectionDragBegin", dragged_line, event);
				
				//prepare scrollable containers
				var line_containers = me.getLineContainers(mouse_clicked_elm);
				scrollable_containers = me.getScrollableContainers(line_containers);
			}
		}
	};
	
	me.onSourceMouseDown = function(event) {
		event.preventDefault && event.preventDefault();
		event.stopPropagation && event.stopPropagation();
		
		var is_left_mouse_btn = false;

		if ("buttons" in event)
			is_left_mouse_btn = event.buttons == 1;

		var button = event.which || event.button;
		is_left_mouse_btn = button == 1;
		
		//only allow drag if the mouse is pressed and is dragged a little bit, in order to avoid the simple click button
		if (is_left_mouse_btn)
			mouse_clicked_elm = this;
	};
	
	me.onTargetMouseUp = function(event) {
		event.preventDefault && event.preventDefault();
		
		if (connect_mode && dragged_line) {
			var over_elm = me.getEventTarget(event);
			var target = me.getElementTarget(over_elm);
			
			if (target) {
				me.line_options["dragging_hover_target_class"] && $(target).removeClass(me.line_options["dragging_hover_target_class"]);
				
				lines.push(dragged_line);
				
				var doc = dragged_line.start.ownerDocument || dragged_line.start.document;
				var start_elm = dragged_line.start;
				var end_elm = target;
				
				//prepare line properties and get start and end elm to connect.
				var props = me.getConnectionSourceTargetProperties(dragged_line, start_elm, end_elm);
				
				dragged_line.source = $(props["source"]);
				dragged_line.target = $(props["target"]);
				dragged_line.sourceId = props["sourceId"];
				dragged_line.targetId = props["targetId"];
				dragged_line.orig_start = props["orig_start"];
				dragged_line.orig_end = props["orig_end"];
				
				//trigger beforeDrop
				var status = me.trigger("beforeDrop", dragged_line, event);
				
				if (status) {
					try {
						var opts = me.prepareLineStartEndOptionsBasedInProps(dragged_line, props);
						dragged_line.setOptions(opts);
												
						//if droppable feature is set...
						if (dragged_line.target.is(".ui-droppable"))
							dragged_line.target.trigger("drop"); //TODO: droppable events trigger not working
						
						//trigger connectionDragEnd
						me.trigger("connectionDragEnd", dragged_line, event);
						
						//trigger connectionDragStop
						me.trigger("connectionDragStop", dragged_line, event);
						
						me.endConnection(dragged_line);
					}
					catch(e) {
						if (me.debug && console && console.log)
							console.log(e);
						
						status = false;
					}
				}
				
				if (!status) {
					var detached = me.detachConnection(dragged_line, false);
					
					if (!detached) {
						try {
							var div = dragged_line.canvas;
							
							lines.pop();
							dragged_line.remove();
							
							if (div)
								$(div).remove();
						}
						catch(e) {}
					}
				}
				
				//remove elm point
				doc.body.removeChild(elm_point);
				elm_point = null;
				
				//reset vars
				mouse_clicked_elm = null;
				mouse_dragged = false;
				connect_mode = false;
				dragged_line = null;
				dragged_line_canvas = null;
				scrollable_containers = null;
				me.line_options.on_connection_body_class && doc.body.classList.remove(me.line_options.on_connection_body_class);
				
				if (me.line_options["dragging_active_target_class"])
					for (var i = 0, t = targets.length; i < t; i++)
						$(targets[i]).removeClass(me.line_options["dragging_active_target_class"]);
			}
		}
	};
	
	me.onDraggedLineRemove = function(event) {
		//trigger connectionDragStop
		me.trigger("connectionDragStop", dragged_line, event);
		
		//remove dragged line
		var doc = dragged_line.start.ownerDocument || dragged_line.start.document;
		var detached = me.detachConnection(dragged_line, false);
		
		if (!detached) {
			try {
				var div = dragged_line.canvas;
				
				dragged_line.remove();
				
				if (div)
					$(div).remove();
			}
			catch(e) {}
		}
		
		//remove elm point
		doc.body.removeChild(elm_point);
		elm_point = null;
		
		//reset vars
		mouse_clicked_elm = null;
		mouse_dragged = false;
		connect_mode = false;
		dragged_line = null;
		dragged_line_canvas = null;
		scrollable_containers = null;
		me.line_options.on_connection_body_class && doc.body.classList.remove(me.line_options.on_connection_body_class);
		
		if (me.line_options["dragging_active_target_class"])
			for (var i = 0, t = targets.length; i < t; i++)
				$(targets[i]).removeClass(me.line_options["dragging_active_target_class"]);
	};
	
	me.onDocumentMouseUp = function(event) {
		//cannot set preventDefault or stopPropagation, otherwise this will enter in conflict with others behaviours or the parent websites. Either way, it doesn't make sense either, bc we should not prevent default of the document event. This should only be used for the documents.
		
		if (dragged_line || connect_mode) {
			var over_elm = me.getEventTarget(event);
			var target = me.getElementTarget(over_elm);
			
			if (target)
				me.onTargetMouseUp(event);
			else if (dragged_line)
				me.onDraggedLineRemove(event);
		}
		else {
			mouse_clicked_elm = null;
			mouse_dragged = false;
		}
	};
	
	me.onDocumentMouseMove = function(event) {
		if (mouse_clicked_elm && !mouse_dragged) {
			mouse_dragged = true;
			me.startDraggingLine(event);
		}
		else
			me.updateEventDraggedLine(event);
		
		//prepare scrollable containers
		if (scrollable_containers && scrollable_containers.length > 0) {
			var event_x = event.pageX / me.zoom;
			var event_y = event.pageY / me.zoom;
			var scroll_margin = me.line_options["scroll_margin"] ? me.line_options["scroll_margin"] : 10;
			
			for (var i = 0, t = scrollable_containers.length; i < t; i++) {
				var scrollable_container = scrollable_containers[i];
				
				//for top and left use event.pageX/Y, for right and bottom use event_x/y which include me.zoom.
				var is_scroll_top = event.pageY <= scrollable_container.top + scroll_margin && event.pageY >= scrollable_container.top;
				var is_scroll_left = event.pageX <= scrollable_container.left + scroll_margin && event.pageX >= scrollable_container.left;
				var is_scroll_right = event_x >= scrollable_container.right - scroll_margin && event_x <= scrollable_container.right;
				var is_scroll_down = event_y >= scrollable_container.bottom - scroll_margin && event_y <= scrollable_container.bottom;
				
				if (is_scroll_top || is_scroll_left || is_scroll_right || is_scroll_down) {
					//console.log(scrollable_container.canvas);
					//console.log(is_scroll_top+" || "+is_scroll_left+" || "+is_scroll_right+" || "+is_scroll_down);
					
					if (scrollable_container.canvas.timeout_id)
						clearTimeout(scrollable_container.canvas.timeout_id);
					
					scrollable_container.canvas.timeout_id = setTimeout(function() {
						me.autoScrollContainer(scrollable_container.canvas, is_scroll_top, is_scroll_left, is_scroll_right, is_scroll_down);
					}, 10);
				}
			}
		}
		
		//trigger connectionDrag
		if (connect_mode && dragged_line)
			me.trigger("connectionDrag", dragged_line, event);
	};
	
	me.onDocumentKeyDown = function(event) {
		if (event.key === "Escape" && dragged_line)
			me.onDraggedLineRemove(event);
	};
	
	me.onContainerScroll = function(event) {
		me.updateEventDraggedLine(event);
	};
	
	me.makeDocument = function(doc) {
		if (doc && docs.indexOf(doc) == -1) {
			docs.push(doc);
			
			doc.addEventListener("mouseup", me.onDocumentMouseUp);
			doc.addEventListener("touchend", me.onDocumentMouseUp);
			
			doc.addEventListener('mousemove', me.onDocumentMouseMove);
			doc.addEventListener('touchmove', me.onDocumentMouseMove);
			
			doc.addEventListener('keydown', me.onDocumentKeyDown);
			
			me.makeContainer(doc.body);
		}
		
		return true;
	};
	
	me.makeContainer = function(elm) {
		elm = elm instanceof jQuery ? elm[0] : elm;
		
		if (elm && !elm._scroll_event_listener_added) {
			elm._scroll_event_listener_added = true;
			containers.push(elm);
			
			elm.addEventListener('scroll', me.onContainerScroll);
		}
	};
	
	me.makeSource = function(elm, options) {
		elm = elm instanceof jQuery ? elm[0] : elm;
		
		if (elm && sources.indexOf(elm) == -1) {
			//save options
			elm.options = options;
			
			//save target
			elm.target = me.getElementTarget(elm);
			
			//save elm
			sources.push(elm);
			
			//add class
			elm.classList.add("leader-line-source");
			
			//add listeners
			elm.addEventListener("mousedown", me.onSourceMouseDown);
			elm.addEventListener("touchstart", me.onSourceMouseDown);
			
			me.makeDocument( elm.ownerDocument || elm.document );
			me.makeContainer(options["container"]);
			
			if ($.isPlainObject(options) && options["dragOptions"])
				me.draggable(elm, options["dragOptions"]);
			
			if ($.isPlainObject(options) && options["dropOptions"])
				me.droppable(elm, options["dropOptions"]);
			
			//set elm id
			var id = elm.id;
			
			if (!id && id !== 0) {
				var elm_target = me.getElementTarget(elm);
				var prefix = "leader_line_source";
				
				if (elm_target && (elm_target.id || elm_target.id === 0))
					prefix = elm_target.id;
				
				var id_number = parseInt(Math.random() * 10000) + "" + parseInt(Math.random() * 10000);
				elm.id = prefix + "_" + id_number;
			}
		}
		
		return true;
	};
	
	me.makeTarget = function(elm, options) {
		elm = elm instanceof jQuery ? elm[0] : elm;
		
		if (elm && targets.indexOf(elm) == -1) {
			//save options
			elm.options = options;
			
			//save elm
			targets.push(elm);
			
			//add class
			elm.classList.add("leader-line-target");
			
			//add listeners
			elm.addEventListener("mouseup", me.onTargetMouseUp);
			elm.addEventListener("touchend", me.onTargetMouseUp);
			
			me.makeDocument( elm.ownerDocument || elm.document );
			
			if ($.isPlainObject(options) && options["dragOptions"])
				me.draggable(elm, options["dragOptions"]);
			
			if ($.isPlainObject(options) && options["dropOptions"])
				me.droppable(elm, options["dropOptions"]);
		}
		
		return true;
	};
	
	me.unmakeDocument = function(doc) {
		doc = doc instanceof jQuery ? doc[0] : doc;
		
		if (doc && docs.indexOf(doc) != -1) {
			doc.removeEventListener("mouseup", me.onDocumentMouseUp);
			doc.removeEventListener("touchend", me.onDocumentMouseUp);
			
			doc.removeEventListener('mousemove', me.onDocumentMouseMove);
			doc.removeEventListener('touchmove', me.onDocumentMouseMove);
			
			doc.removeEventListener('keydown', me.onDocumentKeyDown);
		}
	};
	
	me.unmakeContainer = function(elm) {
		elm = elm instanceof jQuery ? elm[0] : elm;
		
		if (elm && containers.indexOf(elm) != -1) {
			elm._scroll_event_listener_added = false;
			
			elm.removeEventListener('scroll', me.onContainerScroll);
		}
	};
	
	me.unmakeSource = function(elm) {
		elm = elm instanceof jQuery ? elm[0] : elm;
		
		if (elm && sources.indexOf(elm) != -1) {
			delete elm["line_options"];
			
			var i = sources.indexOf(elm);
			sources.splice(i, 1);
			
			elm.removeEventListener("mousedown", me.onSourceMouseDown);
			elm.removeEventListener("touchstart", me.onSourceMouseDown);
		}
		
		return true;
	};
	
	me.unmakeTarget = function(elm) {
		elm = elm instanceof jQuery ? elm[0] : elm;
		
		if (elm && targets.indexOf(elm) != -1) {
			delete elm["line_options"];
			
			var i = targets.indexOf(elm);
			targets.splice(i, 1);
			
			elm.removeEventListener("mouseup", me.onTargetMouseUp);
			elm.removeEventListener("touchend", me.onTargetMouseUp);
		}
		
		return true;
	};
	
	me.unmakeEveryDocument = function() {
		for (var i = 0; i < docs.length; i++)
			if (docs[i])
				me.unmakeDocument(docs[i]);
		
		return true;
	};
	
	me.unmakeEveryContainer = function() {
		for (var i = 0; i < containers.length; i++)
			if (containers[i])
				me.unmakeContainer(containers[i]);
		
		return true;
	};
	
	me.unmakeEverySource = function() {
		for (var i = 0; i < sources.length; i++)
			if (sources[i])
				me.unmakeSource(sources[i]);
		
		return true;
	};
	
	me.unmakeEveryTarget = function() {
		for (var i = 0; i < targets.length; i++)
			if (targets[i])
				me.unmakeTarget(targets[i]);
		
		return true;
	};
	
	me.getConnectionSourceTargetProperties = function(line, start_elm, end_elm) {
		var orig_start_elm = start_elm;
		var orig_end_elm = end_elm;
		
		var start_target = me.getElementTarget(start_elm);
		start_elm = line.options["connect_from_target"] && start_target ? start_target : start_elm;
		
		var end_target = me.getElementTarget(end_elm);
		end_elm = line.options["connect_from_target"] && end_target ? end_target : end_elm;
		
		var start = start_elm;
		var end = end_elm;
		var start_line_options = line.options;
		var end_line_options = end_elm.options;
		
		//start_line_options["anchor"] = {x:5,y:5};
		//end_line_options["anchor"] = {x:5,y:5};
		
		if (start_line_options["anchor"]) {
			var anchor_offset = me.getAnchorOffset(start_line_options["anchor"]);
			
			if (anchor_offset) {
				line.start_anchor = start_line_options["anchor"];
				
				start = LeaderLine.pointAnchor(start_elm, anchor_offset);
				start.element = start_elm;
				start.offset = anchor_offset;
			}
		}
		
		if ($.isPlainObject(end_line_options) && end_line_options["anchor"]) {
			var anchor_offset = me.getAnchorOffset(end_line_options["anchor"]);
			
			if (anchor_offset) {
				line.end_anchor = end_line_options["anchor"];
				
				end = LeaderLine.pointAnchor(end_elm, anchor_offset);
				end.element = end_elm;
				end.offset = anchor_offset;
			}
		}
		
		return {
			source: start_target,
			target: end_target,
			sourceId: start_target.id,
			targetId: end_target.id,
			start: start,
			end: end,
			orig_start: orig_start_elm,
			orig_end: orig_end_elm
		};
	};
	
	me.connect = function(options) {
		if (options && options["parameters"] && options["parameters"]["connection_exit_id"]) {
			var source_selector = typeof options.source == "string" ? "#" + options.source : options.source;
			var source_elm = me.getSelector(source_selector);
			
			if (source_elm) {
				var new_source_elm = $(source_elm).find("[connection_exit_id='" + options["parameters"]["connection_exit_id"] + "']");
				
				if (new_source_elm[0])
					options.source = new_source_elm[0];
			}
		}
		
		var line = me.startConnection(options);
		
		if (line) {
			lines.push(line);
			
			var status = true;
			
			try {
				var source_selector = typeof options.source == "string" ? "#" + options.source : options.source;
				var target_selector = typeof options.target == "string" ? "#" + options.target : options.target;
				var source_elm = me.getSelector(source_selector);
				var target_elm = me.getSelector(target_selector);
				var props = me.getConnectionSourceTargetProperties(line, source_elm, target_elm);
				
				line.source = $(props["source"]);
				line.target = $(props["target"]);
				line.sourceId = props["sourceId"];
				line.targetId = props["targetId"];
				line.orig_start = props["orig_start"];
				line.orig_end = props["orig_end"];
				
				var opts = me.prepareLineStartEndOptionsBasedInProps(line, props);
				line.setOptions(opts);
				
				line = me.endConnection(line);
			}
			catch(e) {
				if (me.debug && console && console.log)
					console.log(e);
				
				status = false;
			}
			
			if (!status) {
				var detached = me.detachConnection(line, false);
					
				if (!detached) {
					try {
						var div = line.canvas;
						
						lines.pop();
						line.remove();
						
						if (div)
							$(div).remove();
					}
					catch(e) {}
				}
				
				line = null; //so it returns null
			}
		}
		
		return line;
	};
	
	me.prepareLineStartEndOptionsBasedInProps = function(line, props) {
		var opts = {start: props["start"], end: props["end"]};
		
		//Note that if source and target are the same element, the LeaderLine class will give an exception, so we need to create a dummy inner div inside of that target so the LeaderLine can create the connection.
		if (props["start"] == props["end"]) {
			var anchor_offset = {x:10, y:0};
			var div = LeaderLine.pointAnchor(props["end"], anchor_offset);
			div.element = props["end"];
			div.offset = anchor_offset;
			
			opts["end"] = props["end"] = div;
			line.options["path"] = opts["path"] = "magnet";
		}
		
		if (line.source.is(line.target)) {
			opts["startSocket"] = "top";
			opts["endSocket"] = "top";
		}
		
		return opts;
	};
	
	me.startConnection = function(options) {
		options = me.cloneOptions(options);
		
		var source_selector = typeof options.source == "string" ? "#" + options.source : options.source;
		var target_selector = typeof options.target == "string" ? "#" + options.target : options.target;
		var source_elm = me.getSelector(source_selector);
		var target_elm = me.getSelector(target_selector);
		
		//delete source and target from options
		delete options["source"];
		delete options["target"];
		
		//prepare startPlugColor and endPlugColor in options if paintStyle is defined
		me.prepareInitialOptions(options);
		
		//clone current default/global line_options
		var line_options = me.cloneOptions(me.line_options);
		
		//prepare startPlugColor and endPlugColor in line_options if paintStyle is defined
		me.prepareInitialOptions(line_options);
		
		//set line_options with source elm options
		if ($.isPlainObject(source_elm.options)) {
			//prepare startPlugColor and endPlugColor in options if paintStyle is defined
			me.prepareInitialOptions(source_elm.options);
			
			//merge source_elm.options to line_options
			me.mergeOptions(line_options, source_elm.options);
			
			//merge options to line_options. options take precedence over source_elm.options
			me.mergeOptions(line_options, options);
		}
		else
			me.mergeOptions(line_options, options);
		
		var parsed_line_options = me.cloneOptions(line_options);
		delete parsed_line_options["startLabel"];
		delete parsed_line_options["middleLabel"];
		delete parsed_line_options["endLabel"];
		
		try {
			//console.log(parsed_line_options);
			var line = new LeaderLine(source_elm, target_elm, parsed_line_options);
			
			line.source = $(source_elm);
			line.target = $(target_elm);
			line.options = line_options;
			line.original_options = options;
			
			if (parsed_line_options.ConnectionOverlays || parsed_line_options.connectorOverlays) {
				var connection_overlays = me.filterOverlays(parsed_line_options.ConnectionOverlays, null, ["Label", "Custom"]);
				var connector_overlays = me.filterOverlays(parsed_line_options.connectorOverlays, null, ["Label", "Custom"]);
				
				me.addLineOverlay(line, connection_overlays);
				me.addLineOverlay(line, connector_overlays);
			}
			
			//prepare zIndex
			var doc = line.start.ownerDocument || line.start.document;
			var line_svg_canvas = $(doc).find("body > .leader-line:last-child");
			
			if ($.isNumeric(parsed_line_options.ConnectorZIndex))
				line_svg_canvas.css("z-index", parsed_line_options.ConnectorZIndex);
		}
		catch(e) {
			if (me.debug && console && console.log)
				console.log(e);
		}
		
		return line;
	};
	
	me.endConnection = function(line) {
		var line_options = line.options;
		var target_line_options = $.isPlainObject(line.target[0].options) ? line.target[0].options : {}; //get options with options from target
		//console.log(line_options);
		
		//prepare line canvas
		var doc = line.source[0].ownerDocument || line.source[0].document;
		var line_svg_canvas = $(doc).find("body > .leader-line:last-child");
		var line_canvas = $(doc.createElement("div"));
		
		line.svg_canvas = line_svg_canvas[0];
		line.canvas = line_canvas[0];
		
		if (line_options["container"] && !line_svg_canvas.parent().is(line_options["container"]))
			$(line_options["container"]).append(line.canvas);
		else
			line.svg_canvas.parentNode.insertBefore(line.canvas, line.svg_canvas);
		
		line.canvas.classList.remove("leader-line-dragged-line");
		
		if (line_options["dragged_line_class"])
			line.canvas.classList.remove(line_options["dragged_line_class"]);
		
		line.canvas.setAttribute("class", line.svg_canvas.getAttribute("class"));
		line.canvas.setAttribute("style", line.svg_canvas.getAttribute("style"));
		line.canvas.setAttribute("id", "leader-line-" + line._id);
		line.canvas.setAttribute("data-leader-line-id-number", line._id);
		
		me.appendLineBodySVGToCanvas(line);
		//console.log(line);
		//console.log(line._id);
		//console.log(line_canvas);
		
		//set right anchors, if there is no anchors defined and repeated lines with the source and target
		me.prepareLineAnchors(line);
		
		//prepare endPlugColor in target_line_options if paintStyle is defined
		me.setOptionsPropBasedInPaintStyle(target_line_options, "endPlugColor", ["fillStyle", "color"]);
		me.setOptionsPropBasedInPaintStyle(target_line_options, "endPlugSize", ["lineWidth", "size"]);
		
		//change endPlugColor if different
		var new_end_plug_color = target_line_options.hasOwnProperty("endPlugColor") ? target_line_options.endPlugColor : line_options.endPlugColor;
		
		if (new_end_plug_color != line_options.endPlugColor) {
			line.endPlugColor = new_end_plug_color;
			line.options.endPlugColor = line_options.endPlugColor = new_end_plug_color;
		}
		
		//add labels if exists in options
		if (line_options.ConnectionOverlays || line_options.connectorOverlays) {
			me.removeLineOverlays(line);
			me.addLineOverlay(line, line_options.ConnectionOverlays);
			me.addLineOverlay(line, line_options.connectorOverlays);
		}
		else {
			me.createLineLabel(line, "start");
			me.createLineLabel(line, "middle");
			me.createLineLabel(line, "end", target_line_options);
		}
		
		//add line points if exist in options
		me.createLinePoint(line, "start");
		me.createLinePoint(line, "end", target_line_options);
		
		//prepare zIndex
		if ($.isNumeric(line_options.ConnectorZIndex))
			line_canvas.css("z-index", line_options.ConnectorZIndex);
		
		//prepare children
		var line_line_path_elm = line_canvas.find("#leader-line-" + line._id + "-line-path")[0];
		var line_line_shape_elm = line_canvas.find("#leader-line-" + line._id + "-line-shape")[0];
		var line_plug_marker_0_elm = line_canvas.find("#leader-line-" + line._id + "-plug-marker-0 > g > use:first-child")[0];
		var line_plug_marker_1_elm = line_canvas.find("#leader-line-" + line._id + "-plug-marker-1 > g > use:first-child")[0];
		
		line_line_path_elm.classList.add("leader-line-path");
		line_line_shape_elm.classList.add("leader-line-shape");
		line_plug_marker_0_elm.classList.add("leader-line-plug");
		line_plug_marker_0_elm.classList.add("leader-line-start-plug");
		line_plug_marker_1_elm.classList.add("leader-line-plug");
		line_plug_marker_1_elm.classList.add("leader-line-end-plug");
		
		//set pointer events. This is very important bc if I have multiple lines over another ones, I can access them individually with the mouse. if these pointer events are not set, then I cannot access the lines below.
		line.canvas.setAttribute("pointer-events", "none");
		line_canvas.children("svg")[0].setAttribute("pointer-events", "none");
		line_line_path_elm.setAttribute("pointer-events", "all");
		line_plug_marker_0_elm.setAttribute("pointer-events", "all");
		line_plug_marker_1_elm.setAttribute("pointer-events", "all");
		
		$.each(line_canvas.find(".leader-line-shape, .leader-line-plug"), function(idx, child) {
			var child_options = line_options;
			
			//set line_options with target options
			if ($(child).is(".leader-line-end-plug")) {
				if (!$.isEmptyObject(target_line_options)) {
					child_options = me.cloneOptions(child_options);
					me.mergeOptions(child_options, target_line_options);
				}
			}
			
			me.setLineEvents(child, child_options);
		});
		
		me.setLineEvents(line_canvas[0], line_options); //bc of firefox
		
		//prepare parameters
		line.parameters = $.isPlainObject(line_options["parameters"]) ? line_options["parameters"] : {};
		
		//prepare other properties and handlers
		//prepare other properties and handlers - overlays
		if (!$.isPlainObject(line.overlays))
			line.overlays = {};
		
		line.getOverlays = function() {
			var arr = [];
			
			for (var type in this.overlays) {
				if ($.isPlainObject(this.overlays[type]))
					for (var overlay_type_id in this.overlays[type])
						arr.push(this.overlays[type][overlay_type_id]);
			}
			
			return arr;
		};
		line.getOverlay = function(overlay_type_id, type) {
			if (type) {
				if ($.isPlainObject(this.overlays[type]) && this.overlays[type].hasOwnProperty(overlay_type_id))
					return this.overlays[type][overlay_type_id];
			}
			else 
				for (var type in this.overlays)
					if ($.isPlainObject(this.overlays[type]) && this.overlays[type].hasOwnProperty(overlay_type_id))
						return this.overlays[type][overlay_type_id];
			
			return null;
		};
		line.removeAllOverlays = function() {
			me.removeLineOverlays(line);
		};
		line.addOverlay = function(overlay) { //overlay is an array of overlays
			return me.addLineOverlay(this, overlay);
		};
		
		//prepare other properties and handlers - paint style
		line.paintStyle = {
			strokeStyle: line.startPlugColor,
			fillStyle: line.gradient ? null : line.color,
			lineWidth: line.startPlugSize
		};
		line.setPaintStyle = function(paint_style) {
			if ($.isPlainObject(paint_style)) {
				for (var k in paint_style)
					this.paintStyle[k] = paint_style[k];
				
				var line_canvas = $(this.canvas);
				var path_elm = line_canvas.find(".leader-line-shape")[0];
				me.setElementPaintStyle(path_elm, paint_style);
				
				if (paint_style["lineWidth"])
					this.size = paint_style["lineWidth"];
				
				//prepare plugs
				var plug_paint_style = me.cloneOptions(paint_style);
				var plugs_color = plug_paint_style["strokeStyle"] ? plug_paint_style["strokeStyle"] : plug_paint_style["stroke"];
				
				if (plugs_color) {
					this.startPlugColor = plugs_color;
					this.endPlugColor = plugs_color;
				}
				
				if (paint_style["lineWidth"]) {
					this.startPlugSize = me.getLinePlugSize(this, "start");
					this.endPlugSize = me.getLinePlugSize(this, "end");
				}
				
				delete plug_paint_style["strokeStyle"];
				delete plug_paint_style["stroke"];
				delete plug_paint_style["fillStyle"];
				delete plug_paint_style["lineWidth"];
				
				if (!$.isPlainObject(plug_paint_style)) {
					var start_plug_elm = line_canvas.find(".leader-line-start-plug")[0];
					var end_plug_elm = line_canvas.find(".leader-line-end-plug")[0];
					
					me.setElementPaintStyle(start_plug_elm, plug_paint_style);
					me.setElementPaintStyle(end_plug_elm, plug_paint_style);
				}
			}
		};
		line.getPaintStyle = function() {
			return this.paintStyle;
		};
		
		//prepare other properties and handlers - parameters
		line.getParameters = function() {
			return this.parameters;
		};
		line.getParameter = function(param_name) {
			return $.isPlainObject(this.parameters) ? this.parameters[param_name] : null;
		};
		line.setParameter = function(param_name, param_value) {
			if (!$.isPlainObject(this.parameters))
				this.parameters = {};
			
			this.parameters[param_name] = param_value;
		};
		
		//prepare other properties and handlers - end points
		line.endpoints = [ line.sourceEndPoint, line.targetEndPoint ];
		line.sourceEndpoint = line.endpoints[0]; //'p' in 'Endpoint' must be lowercase, bc of jsplumb
		line.targetEndpoint = line.endpoints[1]; //'p' in 'Endpoint' must be lowercase, bc of jsplumb
		
		//prepare other properties and handlers - connector
		line.connector = {
			type: available_line_paths_types[this.path],
			canvas: line.canvas,
		};
		
		//prepare other properties and handlers - others
		line.id = line._id;
		line.connection = line;
		
		line.getElement = function() {
			return $(this.canvas); //return jquery node, not native.
		};
		line.setConnector = function(connector, do_not_repaint) {
			/* TODO: use startSocketGravity and endSocketGravity
			connector = [ "Straight", { stub:5, gap:0}, { cssClass:"myCssClass" } ],
			or
			connector = [ "Bezier", { curviness:10 }, { cssClass:"myCssClass" } ],
			or
			connector = [ "StateMachine", { margin:5, curviness:10, proximityLimit:80 }, { cssClass:"myCssClass" } ],
			or
			connector = [ "Flowchart", { stub:5, alwaysRespectStubs:false, gap:0, midpoint:0.5, cornerRadius:0}, { cssClass:"myCssClass" } ]
			*/
			//prepare path type
			var type = $.isArray(connector) ? connector[0] : connector;
			var path = typeof type == "string" ? available_line_paths[type] : null;
			
			if (path) {
				//set new path
				this.path = path; 
				this.connector.type = type;
				
				//set line canvas class
				if ($.isArray(connector) && $.isPlainObject(connector[2])) {
					var c = connector[2]["cssClass"];
					
					if (c)
						this.getElement().addClass(c);
				}
				
				//repaint line
				if (!do_not_repaint)
					this.repaint({force: true});
			}
		};
		line.repaint = function(opts) {
			me.repaintLine(this, opts);
		};
		line.bind = function(event_type, handler) {
			var line_canvas = this.getElement();
			line_canvas.find(".leader-line-shape, .leader-line-plug").bind(event_type, handler);
			line_canvas.parent().children("leader-line-overlay-" + this._id).bind(event_type, handler);
		};
		line.unbind = function(event_type, handler) {
			var line_canvas = this.getElement();
			line_canvas.find(".leader-line-shape, .leader-line-plug").unbind(event_type, handler);
			line_canvas.parent().children("leader-line-overlay-" + this._id).unbind(event_type, handler);
		};
		
		me.repaintLine(line);
		
		//trigger connection event
		me.trigger("connection", line, window.event);
		
		return line;
	};
	
	//set right anchors, if there is no anchors defined and repeated lines with the source and target. Additionally, unset other anchors if were created unnecessary
	me.prepareLineAnchors = function(line, diff) {
		var auto_anchor_start = !line.start_anchor;
		var auto_anchor_end = !line.end_anchor;
		
		//only if there is not an user-defined anchor
		if (auto_anchor_start || auto_anchor_end) {
			var line_anchors_offsets = me.getLinePointsOffsets(line, true); //get original offsets discarding the previous end_points
			
			if ($.isPlainObject(line_anchors_offsets)) {
				diff = diff > 0 ? diff : line.options.similar_lines_gap;
				//console.log(diff);
				
				if (diff > 0) {
					var change_position_start = false;
					var change_position_end = false;
					
					//reset available_anchors_offsets
					if (!$.isPlainObject(line.available_anchors_offsets) || !$.isPlainObject(line.current_anchors_offsets))
						line.available_anchors_offsets = {};
					else {
						if (!$.isPlainObject(line.current_anchors_offsets.start) || line.current_anchors_offsets.start.x != line_anchors_offsets.start.x || line.current_anchors_offsets.start.y != line_anchors_offsets.start.y) {
							line.available_anchors_offsets.start = {};
							change_position_start = true;
						}
						
						if (!$.isPlainObject(line.current_anchors_offsets.end) || line.current_anchors_offsets.end.x != line_anchors_offsets.end.x || line.current_anchors_offsets.end.y != line_anchors_offsets.end.y) {
							line.available_anchors_offsets.end = {};
							change_position_end = true;
						}
					}
					
					//prepare available_anchors_offsets if empty
					if (auto_anchor_start && 
						(!$.isPlainObject(line.available_anchors_offsets.start) || $.isEmptyObject(line.available_anchors_offsets.start))
					)
						line.available_anchors_offsets.start = me.getAvailableAnchorsOffsets(line_anchors_offsets.start, diff);
					
					if (auto_anchor_end && 
						(!$.isPlainObject(line.available_anchors_offsets.end) || $.isEmptyObject(line.available_anchors_offsets.end))
					) 
						line.available_anchors_offsets.end = me.getAvailableAnchorsOffsets(line_anchors_offsets.end, diff);
					
					if (!$.isEmptyObject(line.available_anchors_offsets.start) || !$.isEmptyObject(line.available_anchors_offsets.end)) {
						//prepare used_anchors_offsets
						var line_start_ref = line.start.element ? line.start.element : line.start;
						var line_end_ref = line.end.element ? line.end.element : line.end;
						var is_self_connection = line_start_ref == line_end_ref;
						
						//do not execute the code below if is a connection to it-self, otherwise it will give an exception
						if (!is_self_connection) {
							var used_anchors_offsets = {
								start: {},
								end: {}
							};
							
							for (var i = 0, t = lines.length; i < t; i++) {
								var l = lines[i];
								
								if (l._id != line._id) {
									var l_start_ref = l.start.element ? l.start.element : l.start;
									var l_end_ref = l.end.element ? l.end.element : l.end;
									var similar_start = auto_anchor_start && l_start_ref == line_start_ref;
									var similar_end = auto_anchor_end && l_end_ref == line_end_ref;
									
									if (similar_start || similar_end) {
										var l_point_offsets = me.getLinePointsOffsets(l);
										
										if (similar_start) {
											var l_point_offsets_start_str = l_point_offsets.start.x + "_" + l_point_offsets.start.y;
											used_anchors_offsets.start[l_point_offsets_start_str] = l_point_offsets.start;
										}
										
										if (similar_end) {
											var l_point_offsets_end_str = l_point_offsets.end.x + "_" + l_point_offsets.end.y;
											used_anchors_offsets.end[l_point_offsets_end_str] = l_point_offsets.end;
										}
									}
									//in case the connection is flipped
									else if (l_start_ref == line_end_ref && l_end_ref == line_start_ref && (auto_anchor_start || auto_anchor_end)) {
										var l_point_offsets = me.getLinePointsOffsets(l);
										
										if (auto_anchor_start) {
											var l_point_offsets_end_str = l_point_offsets.end.x + "_" + l_point_offsets.end.y;
											used_anchors_offsets.start[l_point_offsets_end_str] = l_point_offsets.end; //set used_anchors_offsets.start with l_point_offsets.end
										}
										
										if (auto_anchor_end) {
											var l_point_offsets_start_str = l_point_offsets.start.x + "_" + l_point_offsets.start.y;
											used_anchors_offsets.end[l_point_offsets_start_str] = l_point_offsets.start; //set used_anchors_offsets.end with l_point_offsets.start
										}
									}
								}
							}
							
							/*console.log(line_end_ref);
							console.log(line._id);
							console.log(line_anchors_offsets.end);
							console.log(used_anchors_offsets.end);
							console.log(line.available_anchors_offsets.end);*/
							
							var status_start = setNewPointAnchor(line, "start", line_start_ref, line_anchors_offsets.start, used_anchors_offsets.start, line.available_anchors_offsets.start, change_position_start);
							var status_end = setNewPointAnchor(line, "end", line_end_ref, line_anchors_offsets.end, used_anchors_offsets.end, line.available_anchors_offsets.end, change_position_end);
							
							//set line.current_anchors_offsets
							if (status_start || status_end)
								line.current_anchors_offsets = line_anchors_offsets;
							
							//create setNewPointAnchor function
							function setNewPointAnchor(line_obj, anchor_type, anchor_element, default_anchor_offset, used_anchor_offsets, available_anchor_offsets, change_position) {
								if ($.isEmptyObject(used_anchor_offsets))
									unsetOldPointAnchor(line_obj, anchor_type, anchor_element, default_anchor_offset, used_anchor_offsets);
								else if (available_anchor_offsets && available_anchor_offsets.length > 0) {
									var default_anchor_offset_str = default_anchor_offset.x + "_" + default_anchor_offset.y;
									var used = used_anchor_offsets.hasOwnProperty(default_anchor_offset_str);
									
									//if default position is not used anymore and previously anchor was sent, then remove anchor and set original element, without anchor. Return true just in case the change_position is true.
									if (!used) {
										if (unsetOldPointAnchor(line_obj, anchor_type, anchor_element, default_anchor_offset, used_anchor_offsets))
											return true;
									}
									
									//if position is already used, create new anchor. Or if anchor already exists, updates its position. 
									if (used || change_position)
										for (var i = 0, t = available_anchor_offsets.length; i < t; i++) {
											var point_offset = available_anchor_offsets[i];
											var point_offset_str = point_offset.x + "_" + point_offset.y;
											
											if (!used_anchor_offsets.hasOwnProperty(point_offset_str)) {
												var anchor_offset = {
													x: point_offset.x - default_anchor_offset.points[3].x,
													y: point_offset.y - default_anchor_offset.points[0].y,
												};
												//console.log(anchor_offset.y);
												
												if (anchor_offset.x < 0)
													anchor_offset.x = 0;
												
												if (anchor_offset.y < 0)
													anchor_offset.y = 0;
												
												var orig_anchor_offset = anchor_offset;
												
												if (me.zoom != 1) {
													orig_anchor_offset = Object.assign({}, anchor_offset);
													
													anchor_offset.x = anchor_offset.x * me.zoom;
													anchor_offset.y = anchor_offset.y * me.zoom;
												}
												
												//console.log(anchor_type);
												//console.log(default_anchor_offset);
												//console.log(used_anchor_offsets);
												//console.log(available_anchor_offsets);
												//console.log(point_offset);
												//console.log(anchor_offset);
												//console.log(anchor_element);
												
												line_obj[anchor_type] = LeaderLine.pointAnchor(anchor_element, anchor_offset);
												line_obj[anchor_type].element = anchor_element;
												line_obj[anchor_type].offset = orig_anchor_offset;
												
												return true;
											}
										}
								}
								
								return false;
							}
							
							//create unsetOldPointAnchor function
							function unsetOldPointAnchor(line_obj, anchor_type, anchor_element, default_anchor_offset, used_anchor_offsets) {
								var default_anchor_offset_str = default_anchor_offset.x + "_" + default_anchor_offset.y;
								
								if (line_obj[anchor_type].element && 
									($.isEmptyObject(used_anchor_offsets) || !used_anchor_offsets.hasOwnProperty(default_anchor_offset_str))
								) {
									//console.log(default_anchor_offset);
									//console.log(used_anchor_offsets);
									
									line_obj[anchor_type] = anchor_element;
									
									return true;
								}
								
								return false;
							}
						}
					}
				}
			}
		}
	};
	
	me.getConnectionIdNumber = function(elm) {
		var line_canvas = me.getLineCanvasFromElm(elm);
		
		if (line_canvas) {
			var line_id = line_canvas.attr("id");
			var number = ("" + line_id).match(/leader-line-([0-9]+)/);
			
			return number ? number[1] : null;
		}
		
		return null;
	};
	
	me.setLineEvents = function(elm, options) {
		if (elm) {
			var line_canvas = me.getLineCanvasFromElm(elm);
			
			if (line_canvas && line_canvas[0]) {
				var line_id_number = me.getConnectionIdNumber(line_canvas);
				var line = me.getConnectionById(line_id_number);
				var j_elm = $(elm);
				var p = line_canvas.parent();
				//console.log(line_id_number);
				
				//set hover event
				var hover_text_color = options.hasOwnProperty("hoverColor") ? options["hoverColor"] : me.getOptionsPropBasedInHoverPaintStyle(options, "hoverColor", ["fillStyle", "color"]);
				var hover_line_color = options.hasOwnProperty("hoverColor") ? options["hoverColor"] : me.getOptionsPropBasedInHoverPaintStyle(options, "hoverColor", ["strokeStyle", "color"]);
				var hover_line_size = options.hasOwnProperty("hoverSize") ? options["hoverSize"] : me.getOptionsPropBasedInHoverPaintStyle(options, "hoverSize", ["lineWidth", "size"]);
				//console.log(elm);
				//console.log(hover_text_color);
				
				if (hover_text_color || hover_line_color || hover_line_size) {
					j_elm.hover(
						function(e) {
							if (line._dragging)
								return false;
							
							//set line z-index
							if (line_canvas[0].style.zIndex && !line_canvas[0].hasOwnProperty("z_index_bkp"))
								line_canvas[0].z_index_bkp = line_canvas[0].style.zIndex;
							
							var z_index = line_canvas[0].z_index_bkp ? line_canvas[0].z_index_bkp : line_canvas.css("z-index");
							z_index = parseInt(z_index > 0 ? z_index : 0) + 1;
							line_canvas.css("z-index", z_index);
							
							//set children css
							var children = line_canvas.find(".leader-line-shape, .leader-line-plug, .leader-line-label");
							
							children.each(function(idx, child) {
								child = $(child);
								
								if (child.is(".leader-line-shape")) {
									if (hover_line_color) {
										if (child[0].style.stroke && !child[0].hasOwnProperty("stroke_bkp"))
											child[0].stroke_bkp = child[0].style.stroke;
											
										child.css("stroke", hover_line_color);
									}
									
									if (hover_line_size) {
										if (child[0].style.strokeWidth && !child[0].hasOwnProperty("stroke_width_bkp"))
											child[0].stroke_width_bkp = child[0].style.strokeWidth;
											
										child.css("stroke-width", hover_line_size);
									}
								}
								else if (hover_text_color) {
									if (child[0].style.fill && !child[0].hasOwnProperty("fill_bkp")) 
										child[0].fill_bkp = child[0].style.fill;
									
									child.css("fill", hover_text_color);
								}
							});
							
							if (hover_text_color) {
								var overlays = p.children(".leader-line-overlay-" + line_id_number);
								var end_points = p.children(".leader-line-end-point-" + line_id_number);
								
								overlays.children().each(function(idx, child) {
									child = $(child);
									
									if (child[0].style.color && !child[0].hasOwnProperty("color_bkp")) 
										child[0].color_bkp = child[0].style.color;
									
									child.css("color", hover_text_color);
								});
								
								end_points.each(function(idx, child) {
									child = $(child);
									
									if (child[0].style.fill && !child[0].hasOwnProperty("fill_bkp")) 
										child[0].fill_bkp = child[0].style.fill;
									
									child.css("fill", hover_text_color);
								});
							}
						}, 
						function(e) {
							if (line._dragging)
								return false;
							
							//rollback line z-index
							line_canvas.css("z-index", line_canvas[0].hasOwnProperty("z_index_bkp") ? line_canvas[0].z_index_bkp : "");
							
							//rollback children css
							var children = line_canvas.find(".leader-line-shape, .leader-line-plug, .leader-line-label");
							
							children.each(function(idx, child) {
								child = $(child);
								
								if (child.is(".leader-line-shape")) {
									if (hover_line_color)
										child.css("stroke", child[0].hasOwnProperty("stroke_bkp") ? child[0].stroke_bkp : "");
									
									if (hover_line_size)
										child.css("stroke-width", child[0].hasOwnProperty("stroke_width_bkp") ? child[0].stroke_width_bkp : "");
								}
								else if (hover_text_color)
									child.css("fill", child[0].hasOwnProperty("fill_bkp") ? child[0].fill_bkp : "");
							});
							
							if (hover_text_color) {
								var overlays = p.children(".leader-line-overlay-" + line_id_number);
								var end_points = p.children(".leader-line-end-point-" + line_id_number);
								
								overlays.children().each(function(idx, child) {
									child = $(child);
									child.css("color", child[0].hasOwnProperty("color_bkp") ? child[0].color_bkp : "");
								});
								
								end_points.each(function(idx, child) {
									child = $(child);
									child.css("fill", child[0].hasOwnProperty("fill_bkp") ? child[0].fill_bkp : "");
								});
							}
						}
					);
				}
				
				//set other events
				var has_context_menu_event = events["contextmenu"] && events["contextmenu"].length > 0;
				var has_click_event = events["click"] && events["click"].length > 0;
				var has_dbl_click_event = events["dblclick"] && events["dblclick"].length > 0;
				
				if (has_context_menu_event)
					j_elm.bind("contextmenu", function(e) {
						var clicked_elm = this;
						var j_clicked_elm = $(clicked_elm);
						
						if (j_clicked_elm.is(".leader-line-end-point")) {
							var is_end_point_start = j_clicked_elm.is(".leader-line-end-point-start");
							var ret_elm = $(is_end_point_start ? line.start : line.end);
							line.isSource = is_end_point_start;
							line.isTarget = !is_end_point_start;
							
							var orig_func = line.getElement;
							
							line.getElement = function() {
								return ret_elm;
							};
							
							me.trigger("contextmenu", line, e);
							
							//set getelement func back to the original
							line.getElement = orig_func;
						}
						else
							me.trigger("contextmenu", line, e);
					});
				
				if (has_click_event || has_dbl_click_event) {
					if (has_click_event) {
						j_elm.bind("click", function(e) {
							if (has_dbl_click_event) {
								elm.is_single_click = true;
								
								setTimeout(function() {
									if (elm.is_single_click)
										me.trigger("click", line, e);
								}, 200);
							}
							else
								me.trigger("click", line, e);
						});
					}
					
					if (has_dbl_click_event)
						j_elm.bind("dblclick", function(e) {
							if (has_click_event)
								elm.is_single_click = false;
							
							me.trigger("dblclick", line, e);
						});
				}
			}
		}
	};
	
	me.getLineCanvasFromElm = function(elm) {
		var line_canvas = null;
		
		if (elm) {
			var j_elm = $(elm);
			
			if (j_elm.is(".leader-line-overlay, .leader-line-end-point")) {
				line_canvas = j_elm.parent().children("#leader-line-" + j_elm.attr("data-leader-line-id-number"));
				
				if (!line_canvas.is(".leader-line"))
					line_canvas = null;
			}
			else
				line_canvas = j_elm.closest(".leader-line");
		}
		
		return line_canvas;
	};
	
	me.getNextLineLabelId = function(line, type) {
		var id = 0;
		
		if (line && line.overlays && line.overlays[type])
			for (var overlay_type_id in line.overlays[type])
				if ($.isNumeric(overlay_type_id) && parseInt(overlay_type_id) >= id)
					id = parseInt(overlay_type_id) + 1;
		
		return id;
	};
	
	me.replaceLineLabel = function(elm, type, label) {
		if (elm) {
			var line_id_number = me.getConnectionIdNumber(elm);
			var line = me.getConnectionById(line_id_number);
			var overlay = line ? line[type + "LabelOverlayCanvas"] : null;
			
			if (overlay) {
				var div = $(overlay).children("div:first-child");
				
				if (div[0]) {
					var overlay_type_id = div.attr("data-overlay-id");
					var overlay_obj = line.overlays[type][overlay_type_id];
					
					if (overlay_obj)
						overlay_obj.setLabel(label);
					
					return overlay_obj;
				}
			}
		}
		
		return null;
	};
	
	me.addLineLabel = function(line, type, label, options) {
		if (line) {
			var overlay = line[type + "LabelOverlayCanvas"];
			
			if (overlay) {
				var overlay_options = {
					type: "Label",
					label: label,
					id: me.getNextLineLabelId(line, type),
				};
				
				var overlay_obj = me.createLineLabel(line, type, options, overlay_options);
				
				return overlay_obj;
			}
			
			var options_bkp = me.cloneOptions(line.options);
			line.options[type + "Label"] = label;
			
			var overlay_options = {
				type: "Label",
				label: label,
			};
			
			var overlay_obj = me.createLineLabel(line, type, options, overlay_options);
			line.options = options_bkp;
			
			return overlay_obj;
		}
		
		return null;
	};
	
	me.addLineLabelInDefaultOverlay = function(elm, type, label) {
		if (elm) {
			var line_id_number = me.getConnectionIdNumber(elm);
			var line = me.getConnectionById(line_id_number);
			var overlay = line ? line[type + "LabelOverlayCanvas"] : null;
			
			if (overlay) {
				var overlay_options = {
					type: "Label",
					label: label,
				};
				
				var overlay_obj = me.createLineLabel(line, type, null, overlay_options);
				
				return overlay_obj;
			}
		}
		
		return null;
	};
	
	me.createLineLabel = function(line, type, options, overlay_options) {
		overlay_options = $.isPlainObject(overlay_options) ? overlay_options : {};
		var line_options = me.cloneOptions(line.options);
		
		//set line_options with external options
		if ($.isPlainObject(options))
			me.mergeOptions(line_options, options);
		
		var label = overlay_options.hasOwnProperty("label") ? overlay_options["label"] : line_options[type + "Label"];
		var label_html = label instanceof jQuery ? label[0].outerHTML : (label instanceof Node ? label.outerHTML : label);
		
		//always create label overlay, even if label is an empty string, because when a connection/line gets created, it must create all the correspondent overlays defined in the options, even if the label is empty, bc then the TaskFlowChart adds specific classes to these overlays. This means the LeaderLine cannot remove overlay labels if the label is empty.
		var line_canvas = $(line.canvas);
		var label_elm = null;
		var overlay = null;
		var j_overlay = null;
		
		var overlay_id = overlay_options.hasOwnProperty("id") ? overlay_options["id"] : line_options[type + "LabelId"];
		var style = overlay_options.hasOwnProperty("style") ? overlay_options["style"] : line_options[type + "LabelStyle"];
		var class_name = overlay_options.hasOwnProperty("class") ? overlay_options["class"] : line_options[type + "LabelClass"];
		var label_elm_style = null;
		var label_elm_fill = null;
		
		//add line text canvas if not exists
		if (!line[type + "LabelCanvas"]) {
			//create pathlabel so the labels be in the right positions. This is very important for the DB Diagram, otherwise the labels will appear in wrong positions.
			var label_offset = type == "start" ? -100 : (type == "end" ? 100 : 0);
			var pl = LeaderLine.pathLabel(".", {lineOffset: label_offset});
			
			line[type + "Label"] = pl; //set label to line so it can create the text node. Do not add space bc the leaderline removes it. Put some other character
			label_elm = line_canvas.find(" > svg > text:last-child");
			line[type + "LabelCanvas"] = label_elm[0];
			
			label_elm_style = label_elm.attr("style");
			label_elm_fill = label_elm.css("fill");
			
			if (!label_elm.attr("id"))
				label_elm.attr("id", "leader-line-label-" + type + "-" + line._id);
			
			label_elm[0].classList.add("leader-line-label");
			label_elm[0].classList.add("leader-line-label-" + type);
			
			if (style)
				label_elm.css(style);
			
			//if (class_name)
			//	label_elm[0].classList.add(class_name);
		}
		else {
			label_elm = $(line[type + "LabelCanvas"]);
			overlay = line[type + "LabelOverlayCanvas"];
		}
		
		//add overlay if not exists
		if (!overlay) {
			var doc = label_elm[0].ownerDocument || label_elm[0].document;
			overlay = doc.createElement("div");
			j_overlay = $(overlay);
			line_canvas.after(overlay);
			
			line[type + "LabelOverlayCanvas"] = overlay;
			
			overlay.id = "leader-line-overlay-label-" + type + "-" + line._id;
			overlay.setAttribute("class", "leader-line-overlay leader-line-overlay-" + line._id + " leader-line-overlay-label leader-line-overlay-label-" + type + (line_options["overlay_class"] ? " " + line_options["overlay_class"] : ""));
			overlay.setAttribute("data-leader-line-id-number", line._id);
			overlay.setAttribute("style", label_elm_style);
			
			if (line_canvas.css("z-index") && !j_overlay.css("z-index"))
				j_overlay.css("z-index", line_canvas.css("z-index"));
			
			overlay.repaint = function(opts) {
				var line_id = j_overlay.attr("data-leader-line-id-number");
				var l = me.getConnectionById(line_id);
				
				if (l) {
					var label_elm = $(l[type + "LabelCanvas"]);
					var overlay_elm = $(l[type + "LabelOverlayCanvas"]);
					var offset = label_elm.offset();
					
					//offset = me.prepareOffsetBasedInContainer(offset, l.options["container"]);
					//overlay_elm.css(offset);
					overlay_elm.offset(offset);
					
					/*
					//DEPRECATED bc the offset of LabelCanvas already contains the right offset
					//prepare offset if zoom active
					var zoom_exists = me.zoom != 1 && line.canvas && line.options["container"];
					
					if (zoom_exists) {
						var line_container = $(line.options["container"]);
						var co = line_container.offset();
						//var st = line_container.scrollTop();
						//var sl = line_container.scrollLeft();
						
						//Do not add st or sl here
						offset.top = offset.top - (window.pageYOffset + co.top);
						offset.left = offset.left - (window.pageXOffset + co.left);
						
						overlay_elm.css({
							top: offset.top + "px",
							left: offset.left + "px",
						});
					}*/
				}
			};
			
			if (label_elm_fill)
				j_overlay.css("color", label_elm_fill);
			
			if (!overlay.style.fill || !overlay.style.color) {
				var color = type == "start" && line.startPlugColor ? line.startPlugColor : (
					type == "end" && line.endPlugColor ? line.endPlugColor : line.color
				);
				
				if (!color)
					color = internal_leader_line_options["lineColor"];
				
				if (color) {
					if (!overlay.style.fill)
						j_overlay.css("fill", color);
					
					if (!overlay.style.color)
						j_overlay.css("color", color);
				}
			}
			
			label_elm.css("opacity", 0);
			
			me.setLineEvents(overlay, line_options);
		}
		else
			j_overlay = $(overlay);
		
		//prepare html_elm
		var overlay_type_id = overlay_id || $.isNumeric(overlay_id) ? overlay_id : 0;
		var html_elm = j_overlay.children("[data-overlay-id='" + overlay_type_id + "']")[0];
		
		if (!html_elm) {
			var doc = label_elm[0].ownerDocument || label_elm[0].document;
			html_elm = doc.createElement("div");
			
			overlay.appendChild(html_elm);
		}
		
		var j_html_elm = $(html_elm);
		
		if (label || label === 0)
			j_html_elm.append(label);
		
		html_elm.id = overlay.id + "-" + overlay_type_id;
		html_elm.setAttribute("data-overlay-id", overlay_type_id);
		
		if (class_name)
			j_html_elm.addClass(class_name);
				
		if (style) {
			if (typeof style == "string")
				html_elm.setAttribute("style", style);
			else
				j_html_elm.css(style);
		}
		
		if (html_elm.style.fill && !html_elm.style.color)
			j_html_elm.css("color", j_html_elm.css("fill"));
		
		//prepare overlay obj
		var overlay_obj = overlay_options ? me.cloneOptions(overlay_options) : {};
		overlay_obj.canvas = html_elm;
		overlay_obj.position = type;
		overlay_obj.component = line,
		overlay_obj.getLabel = function() {
			return $(this.getElement()).html();
		};
		overlay_obj.setLabel = function(html) {
			var elm = $(this.getElement());
			elm.html("");
			
			if (html)
				elm.append(html);
			
			this.refresh();
		};
		overlay_obj.getElement = function() {
			return this.canvas; //return native node, not jquery node.
		};
		overlay_obj.repaint = function(opts) {
			overlay.repaint(opts);
			this.refresh();
		};
		overlay_obj.refresh = function() {
			//set new offset based in width and height of the overlay
			var elm = $(this.getElement());
			var width = elm.width();
			var height = elm.height();
			var mt = me.parseFloatWithOneDecimalPlace(height / 2);
			var ml = me.parseFloatWithOneDecimalPlace(width / 2);
			
			elm.css({
				"margin-top": (- mt) + "px",
				"margin-left": (- ml) + "px",
			});
			
			//set label into overlay again so it can set the visibility
			if (this.getLabel())
				elm.show();
			else
				elm.hide();
		};
		
		//repaint ovelay to reposition it according with new label
		overlay_obj.repaint();
		
		//set overlay in line
		if (!$.isPlainObject(line.overlays))
			line.overlays = {};
		
		if (!$.isPlainObject(line.overlays[type]))
			line.overlays[type] = {};
		
		line.overlays[type][overlay_type_id] = overlay_obj;
		
		return overlay_obj;
	};
	
	me.addLineOverlay = function(line, overlay) {
		/*overlay = ["Label", { 
			id: "other_label",
			label: "some other text",
			cssClass: "some_overlay_class",
			style: "color:gray; font-size:10px; margin-top:-15px;",
			position: "start",
		}];
		or
		overlay = ["Custom", { 
			create: function(opts) {
				return $('<span title="Add new task in between">Add</span>');
			},
			events: {
			    	"click": function(labelOverlay, originalEvent) { 
			    		if (originalEvent) {
						if (originalEvent.preventDefault) originalEvent.preventDefault(); 
						else originalEvent.returnValue = false;
					}
					
					var connection_id = labelOverlay && labelOverlay.component && labelOverlay.component.canvas && typeof labelOverlay.component.canvas.getAttribute == "function" ? labelOverlay.component.canvas.getAttribute("connection_id") : null;
					var connection = WF.TaskFlow.getConnection(connection_id);
					
					WF.ContextMenu.showConnectionAddNewTaskPanel(originalEvent, connection);
					return false;
			 	}
		    	}
		}]
		or
		overlay = [
			["Label", { ... }],
			["Arrow", { ... }],
			["Diamond", { ... }],
			["Custom", { ... }],
		]*/
		var status = false;
		
		if ($.isArray(overlay)) {
			status = true;
			
			var target_line_options = $.isPlainObject(line.target[0].options) ? line.target[0].options : {}; //get options with options from target
			var bind_func = function(event_type, event_handler, overlay_canvas, overlay_obj) {
				$(overlay_canvas).bind(event_type, function(event) {
					return event_handler(overlay_obj, event);
				});
			};
			
			if (typeof overlay[0] == "string" || $.isPlainObject(overlay[1]))
				overlay = [overlay];
			
			for (var i = 0, t = overlay.length; i < t; i++) {
				var overlay_item = overlay[i];
				var overlay_type = overlay_item[0];
				var overlay_type_lower = ("" + overlay_type).toLowerCase();
				var overlay_options = overlay_item[1];
				
				if ($.isPlainObject(overlay_options)) {
					var position = overlay_options.position ? overlay_options.position : "start"; //start, middle or end
					var options = position == "end" ? target_line_options : null;
					
					overlay_options.type = overlay_type;
					
					if (overlay_type_lower == "label" || overlay_type_lower == "custom") {
						var label = overlay_type_lower == "label" ? overlay_options.label : (
							typeof overlay_options.create == "function" ? overlay_options.create(overlay_options) : ""
						);
						
						overlay_options["label"] = label;
						overlay_options["class"] = (overlay_options.cssClass ? overlay_options.cssClass : "") + (overlay_options.cssClass && overlay_options.class ? " " : "") + (overlay_options.class ? overlay_options.class : "");
						
						var overlay_obj = me.createLineLabel(line, position, options, overlay_options);
						
						if (overlay_obj) {
							//set events
							if ($.isPlainObject(overlay_options.events))
								for (var event_type in overlay_options.events) {
									var event_handler = overlay_options.events[event_type];
									
									if (typeof event_handler == "function")
										bind_func(event_type, event_handler, overlay_obj.canvas, overlay_obj);
								}
						}
						else
							status = false;
					}
					else if (overlay_type_lower == "arrow" || overlay_type_lower == "diamond") {
						if (position == "middle")
							status = false; //Not supported
						else {
							me.appendLineCanvasSVGToBody(line); //This is very important before we change the startPlug or endPlug, otherwise the line may appear in a weird position.
							
							line[position + "Plug"] = overlay_type_lower == "arrow" ? (overlay_options.arrow_type ? overlay_options.arrow_type : "arrow3") : "diamond";
							
							if (overlay_options.size)
								line[position + "PlugSize"] = overlay_options.size; 
							/*else if (overlay_options.width) {
								var factor = line.size ? line.size * 0.5 : 0.5; //get 50% of the length
								line[position + "PlugSize"] = overlay_options.width * factor;
							}*/
							else
								line[position + "PlugSize"] = me.getLinePlugSize(line, position);
							
							me.appendLineBodySVGToCanvas(line);
							
							me.repaintLine(line);
							
							//prepare overlay obj - without canvas. Do not set canvas, otherwise the arrows won't appear.
							var overlay_type_id = overlay_options.id ? overlay_options.id : 0;
							var overlay_obj = me.cloneOptions(overlay_options);
							overlay_obj.position = position;
							overlay_obj.component = line;
							
							//set overlay in line
							if (!$.isPlainObject(line.overlays))
								line.overlays = {};
							
							if (!$.isPlainObject(line.overlays[overlay_type_lower]))
								line.overlays[overlay_type_lower] = {};
							
							line.overlays[overlay_type_lower][overlay_type_id] = overlay_obj;
						}
					}
					else
						status = false;
				}
			}
		}
		
		return status;
	};
	
	me.removeLineOverlays = function(line) {
		//me.appendLineCanvasSVGToBody(line); //no need to do this.
		
		//loop for all line.overlays and remove canvas
		if (line.overlays)
			for (var type in line.overlays)
				for (var overlay_type_id in line.overlays[type]) {
					var overlay = line.overlays[type][overlay_type_id];
					
					if (overlay && overlay.canvas && overlay.canvas.parentNode)
						$(overlay.canvas).remove();
				}
		
		//reset line.overlays
		line.overlays = {};
		
		//remove plugs
		line.startPlug = "behind";
		line.endPlug = "behind";
		
		//Removes labels. This will remove the startLabelCanvas, middleLabelCanvas and endLabelCanvas from DOM
		line.startLabel = "";
		line.middleLabel = "";
		line.endLabel = "";
		
		if (line.startLabelCanvas) {
			line.startLabelCanvas.parentNode && $(line.startLabelCanvas).remove();
			line.startLabelCanvas = null;
		}
		
		if (line.middleLabelCanvas) {
			line.middleLabelCanvas.parentNode && $(line.middleLabelCanvas).remove();
			line.middleLabelCanvas = null;
		}
		
		if (line.endLabelCanvas) {
			line.endLabelCanvas.parentNode && $(line.endLabelCanvas).remove();
			line.endLabelCanvas = null;
		}
		
		//removes labels' overlays
		if (line.startLabelOverlayCanvas) {
			line.startLabelOverlayCanvas.parentNode && $(line.startLabelOverlayCanvas).remove();
			line.startLabelOverlayCanvas = null;
		}
		
		if (line.middleLabelOverlayCanvas) {
			line.middleLabelOverlayCanvas.parentNode && $(line.middleLabelOverlayCanvas).remove();
			line.middleLabelOverlayCanvas = null;
		}
		
		if (line.endLabelOverlayCanvas) {
			line.endLabelOverlayCanvas.parentNode && $(line.endLabelOverlayCanvas).remove();
			line.endLabelOverlayCanvas = null;
		}
		
		//me.appendLineBodySVGToCanvas(line); //no need to do this.
		
		//repaint line
		me.repaintLine(line);
	};
	
	me.filterOverlays = function(overlays, includes, excludes) {
		if ($.isArray(overlays) && (includes || excludes)) {
			if (includes && !$.isArray(includes)) 
				includes = [includes];
			
			if (excludes && !$.isArray(excludes)) 
				excludes = [excludes];
			
			if (typeof overlays[0] == "string" || $.isPlainObject(overlays[1]))
				overlays = [overlays];
			
			var new_overlays = [];
			
			for (var i = 0, t = overlays.length; i < t; i++) {
				var overlay_item = overlays[i];
				var overlay_type = overlay_item[0];
				
				if (includes && $.inArray(overlay_type, includes) != -1)
					new_overlays.push(overlay_item);
				else if (!excludes || $.inArray(overlay_type, excludes) == -1)
					new_overlays.push(overlay_item);
			}
			
			return new_overlays;
		}
		
		return overlays;
	};
	
	me.getLinePlugSize = function(line, position) {
		var type = me.getLinePlugType(line, position);
		var factor = type == "arrow" ? 5 : 1;
		
		return factor / line.size > 1 ? factor / line.size : 1;
	};
	
	me.getLinePlugType = function(line, position) {
		var plug = line[position + "Plug"];
		
		if (plug)
			return plug.indexOf("arrow") != -1 ? "arrow" : (plug.indexOf("diamond") != -1 ? "diamond" : null);
		
		return null;
	};
	
	me.createLinePoint = function(line, type, options) {
		var line_options = me.cloneOptions(line.options);
		
		//set line_options with external options
		if ($.isPlainObject(options))
			me.mergeOptions(line_options, options);
		
		var html = line_options[type + "PointSVG"];
		
		if (html) {
			var j_end_point = $("<div></div>");
			j_end_point.append(html);
			var end_point = j_end_point[0];
			var line_canvas = $(line.canvas);
			
			//append end_point
			line_canvas.after(j_end_point);
			
			j_end_point.attr("id", "leader-line-end-point-" + type + "-" + line._id);
			j_end_point.attr("data-leader-line-id-number", line._id);
			j_end_point.addClass("leader-line-end-point leader-line-end-point-" + type + " leader-line-end-point-" + line._id + (line_options["end_point_class"] ? " " + line_options["end_point_class"] : ""));
			
			var color = me.getLinePointColor(line, type, options);
			
			if (color)
				j_end_point.css({fill: color});
			
			if (!$.isPlainObject(line_options["dragOptions"]))
				line_options["dragOptions"] = {};
			
			if (line_canvas.css("z-index") && !j_end_point.css("z-index"))
				j_end_point.css("z-index", line_canvas.css("z-index"));
			
			//set draggable events
			var drag_start = line_options["dragOptions"]["start"];
			line_options["dragOptions"]["start"] = function(event, ui) {
				//get current connection and set isSource and isTarget to true.
				var line_id_number = me.getConnectionIdNumber(end_point);
				var point_line = me.getConnectionById(line_id_number);
				
				if (point_line) {
					delete point_line.isSource;
					delete point_line.isTarget;
					
					end_point.previous_offset = {top: j_end_point.css("top"), left: j_end_point.css("left")};
					
					if (type == "start")
						point_line.isSource = true;
					else
						point_line.isTarget = true;
					
					if (typeof drag_start == "function" && drag_start(event, ui) === false)
						return false;
					
					return true;
				}
				
				return false;
			};
			
			var drag_start = line_options["dragOptions"]["stop"];
			line_options["dragOptions"]["stop"] = function(event, ui) {
				//if there is no droppable, revert end point to initial position
				if (end_point.found_droppable) {
					var line_id_number = me.getConnectionIdNumber(end_point);
					var point_line = me.getConnectionById(line_id_number);
					
					if (point_line) {
						//Update line color including end points and overlays
						var point_color = me.getLinePointColor(line, type);
						
						if (point_color) {
							j_end_point.css({fill: point_color});
							point_line[type + "PlugColor"] = point_color;
							j_end_point.parent().children("#leader-line-overlay-label-" + type + "-" + line_id_number).css({fill: point_color, color: point_color});
							
							if (type == "start") {
								point_line.color = point_color;
								j_end_point.parent().children("#leader-line-overlay-label-middle-" + line_id_number).css({fill: point_color, color: point_color});
								
								
								var end_overlay = j_end_point.parent().children("#leader-line-overlay-label-end-" + line_id_number);
								var end_overlay_color = end_overlay.css("fill");
								end_overlay_color = end_overlay_color ? end_overlay_color : end_overlay.css("color");
								
								if (end_overlay_color && end_overlay_color != point_line.endPlugColor)
									end_overlay.css({fill: point_color, color: point_color});
							}
						}
						
						//repaint line
						me.repaintLine(point_line);
						
						//trigger connection event
						me.trigger("connection", point_line, event);
					}
				}
				else
					j_end_point.css(end_point.previous_offset);
				
				delete end_point.found_droppable;
				delete end_point.previous_offset;
			};
			
			me.draggable(j_end_point, line_options["dragOptions"] ? line_options["dragOptions"] : null);
			
			me.setLineEvents(end_point, line_options);
			
			//add end_point elm to line
			line[type + "PointCanvas"] = end_point;
			
			end_point.repaint = function(opts) {
				var line_id = j_end_point.attr("data-leader-line-id-number");
				var l = me.getConnectionById(line_id);
				
				if (l) {
					/*var ref = type == "start" ? l.start : l.end;
					var ref_st = type == "start" ? l.source[0] : l.target[0];
					
					var ref_element = l.options["connect_from_target"] ? (
						ref.element ? ref.element : ref_st
					) : ref;
					var socket_type = me.getLineSocketType(l.canvas, ref_element);
					var offset = ref.element && l.options["connect_from_target"] ? me.getAnchorPointOffset(ref, j_end_point.width(), j_end_point.height()) : me.getSocketPointOffset(ref_element, socket_type, j_end_point.width(), j_end_point.height());
					//offset = me.prepareOffsetBasedInContainer(offset, l.options["container"]);
					//j_end_point.css(offset);
					j_end_point.offset(offset);
					*/
					
					var point_offsets = me.getLinePointsOffsets(l);
					var point_offset = type == "start" ? point_offsets.start : point_offsets.end;
					var offset = {
						top: point_offset.y - me.parseFloatWithOneDecimalPlace(j_end_point.height() / 2), 
						left: point_offset.x - me.parseFloatWithOneDecimalPlace(j_end_point.width() / 2)
					}
					
					j_end_point.offset(offset);
					
					//prepare offset if zoom active
					var zoom_exists = me.zoom != 1 && line.canvas && line.options["container"];
					
					if (zoom_exists) {
						var line_container = $(line.options["container"]);
						var co = line_container.offset();
						//var st = line_container.scrollTop();
						//var sl = line_container.scrollLeft();
						
						//Do not add st or sl here
						offset.top = offset.top - (window.pageYOffset + co.top);
						offset.left = offset.left - (window.pageXOffset + co.left);
						
						j_end_point.css({
							top: offset.top + "px",
							left: offset.left + "px",
						});
					}
				}
			};
			
			//prepare source and target end points
			var paint_style = color ? {fillStyle: color} : {};
			
			var point_obj = {
				active: true,
				element: j_end_point,
				canvas: end_point,
				paintStyle: paint_style,
				component: line,
				type: type,
				
				getElement: function() {
					return this.element;
				},
				setEnabled: function(b) {
					this.active = b;
					j_end_point.draggable("option", "disabled", !b);
				},
				detachFrom: function(end_point_elm, fire_event) { //delete line if canvas is from this end point connected to end_point_elm
					var status = true;
					
					for (var i = 0, t = lines.length; i < t; i++)  {
						var l = lines[i];
						
						if (l.startPointCanvas == this.canvas && l.endPointCanvas == end_point_elm.canvas) {
							if (!me.detachConnection(l, fire_event))
								status = false;
							
							break;
						}
					}
					
					return status;
				},
				setPaintStyle: function(paint_style) {
					if ($.isPlainObject(paint_style)) {
						for (var k in paint_style)
							this.paintStyle[k] = paint_style[k];
					
						me.setElementPaintStyle(this.canvas, paint_style);
					}
				},
				getPaintStyle: function() {
					return this.paintStyle;
				},
				repaint: function() {
					this.canvas.repaint();
				},
				getUuid: function() {
					return this.element.attr("id");
				},
			};
			line[type + "Point"] = point_obj;
			
			var end_point_obj = {
				active: true,
				element: $(type == "start" ? line.orig_start : line.orig_end), //must be the end_point if exists and only the target otherwise
				canvas: end_point,
				paintStyle: paint_style,
				component: line,
				type: type,
				
				getElement: function() {
					return this.element;
				},
				setEnabled: function(b) {
					this.active = b;
					j_end_point.draggable("option", "disabled", !b);
				},
				detachFrom: function(end_point_obj, fire_event) { //delete line if sourceEndPoint and correspondent canvas are from this end point connected to end_point_obj
					var status = true;
					
					for (var i = 0, t = lines.length; i < t; i++)  {
						var l = lines[i];
						
						if (l.sourceEndPoint.element.is(this.element) && l.targetEndPoint.element.is(end_point_obj.element) && l.startPointCanvas == this.canvas && l.endPointCanvas == end_point_obj.canvas) {
							if (!me.detachConnection(l, fire_event))
								status = false;
							
							break;
						}
					}
					
					return status;
				},
				setPaintStyle: function(paint_style) {
					if ($.isPlainObject(paint_style)) {
						for (var k in paint_style)
							this.paintStyle[k] = paint_style[k];
					
						me.setElementPaintStyle(this.canvas, paint_style);
						
						//set paintStyle for all correspondent connections
						/*for (var i = 0, t = lines.length; i < t; i++)  {
							var l = lines[i];
							
							if (l.sourceEndPoint.canvas == this.canvas)
								me.setElementPaintStyle(l.sourceEndPoint.canvas, paint_style);
							else if (l.targetEndPoint.canvas == this.canvas)
								me.setElementPaintStyle(l.targetEndPoint.canvas, paint_style);
						}*/
					}
				},
				getPaintStyle: function() {
					return this.paintStyle;
				},
				repaint: function() {
					this.canvas.repaint();
				},
				getUuid: function() {
					return this.element.attr("id");
				},
			};
			line[(type == "start" ? "source" : "target") + "EndPoint"] = end_point_obj;
			
			point_obj.repaint();
			
			return point_obj;
		}
		
		return null;
	};
	
	me.removeLinePoints = function(line) {
		if (line.startPointCanvas)
			$(line.startPointCanvas).remove();
		
		if (line.endPointCanvas)
			$(line.endPointCanvas).remove();
		
		delete line.startPointCanvas;
		delete line.endPointCanvas;
		
		delete line.startPoint;
		delete line.endPoint;
	};
	
	me.getLinePointColor = function(line, type, options) {
		var line_options = me.cloneOptions(line.options);
		
		//set line_options with external options
		if ($.isPlainObject(options))
			me.mergeOptions(line_options, options);
		
		//get color, giving preference if exists PointColor or PlugColor in line_options, and only then check paintStyle
		var color = null;
		me.setOptionsPropBasedInPaintStyle(line_options, type + "PlugColor", ["fillStyle", "color"]); //if PlugColor is not defined set it with PaintStyle if exists
		
		if (!line_options.hasOwnProperty(type + "PointColor") && line_options.hasOwnProperty(type + "PlugColor")) //if PointColor not defined and PlugColor defined, set color with PlugColor. Note that at this point the PlugColor already contains the PaintStyle set previously...
			color = line_options[type + "PlugColor"];
		else
			color = line_options.hasOwnProperty(type + "PointColor") ? line_options[type + "PointColor"] : line_options["color"];
		
		//overwrite color if set in external options
		if (options && $.isPlainObject(options))
			color = options.hasOwnProperty(type + "PointColor") ? options[type + "PointColor"] : (
				options.hasOwnProperty(type + "PlugColor") ? options[type + "PlugColor"] : color
			); //give preference if exists PointColor or PlugColor in external options, and only then check paintStyle
		
		if (!color)
			color = internal_leader_line_options["lineColor"];
		
		return color;
	};
	
	me.setElementPaintStyle = function(elm, paint_style) {
		if (elm && $.isPlainObject(paint_style)) {
			var css = {};
			
			if (paint_style.hasOwnProperty("color")) {
				css["fill"] = paint_style["color"];
				css["stroke"] = paint_style["color"];
			}
			
			if (paint_style.hasOwnProperty("fillStyle"))
				css["fill"] = paint_style["fillStyle"];
			
			if (paint_style.hasOwnProperty("strokeStyle"))
				css["stroke"] = paint_style["strokeStyle"];
			
			if (paint_style.hasOwnProperty("lineWidth"))
				css["stroke-width"] = paint_style["lineWidth"];
			else if (paint_style.hasOwnProperty("size"))
				css["stroke-width"] = paint_style["lineWidth"];
			
			$(elm).css(css);
		}
	};
	
	me.selectOverlays = function() {
		var overlays = [];
		
		for (var i = 0, t = lines.length; i < t; i++) {
			var line = lines[i];
			
			if (line) {
				if (line.overlays)
					for (var type in line.overlays)
						for (var overlay_type_id in line.overlays[type])
							overlays.push(line.overlays[type][overlay_type_id]);
				
				/*if (line.startLabelOverlayCanvas)
					overlays.push(line.startLabelOverlayCanvas);
				
				if (line.middleLabelOverlayCanvas)
					overlays.push(line.middleLabelOverlayCanvas);
				
				if (line.endLabelOverlayCanvas)
					overlays.push(line.endLabelOverlayCanvas);*/
			}
		}
		
		return $(overlays);
	};
	
	me.getConnectionById = function(line_id) {
		if (line_id)
			for (var i = 0, t = lines.length; i < t; i++) 
				if (lines[i]._id == line_id)
					return lines[i];
		
		return null;
	};
	
	me.getConnections = function() {
		//Do not return the lines it self, otherwise we are returning a reference to an internal var
		var clone = [];
		
		for (var i = 0, t = lines.length; i < t; i++) 
			clone.push(lines[i]);
			
		return clone;
	};
	
	me.detachEveryConnection = function() {
		for (var i = 0, t = lines.length; i < t; i++) {
			var line = lines[i];
			
			if (line)
				me.detachConnection(line);
		}
		
		lines = [];
		
		return true;
	};
	
	me.detachConnection = function(line, fire_event) {
		line = line instanceof jQuery ? line[0] : line;
		
		var status = !fire_event || me.trigger("beforeDetach", line, window.event);
		
		if (status) {
			if (fire_event)
				me.trigger("connectionDetached", line, window.event);
			
			var found_index = -1;
			
			for (var i = 0, t = lines.length; i < t; i++)
				if (lines[i] == line || lines[i]._id == line._id) {
					lines.splice(i, 1);
					found_index = i;
					break;
				}
			
			var div = line.canvas;
			
			if (div) {
				var svg = $(div).children("svg");
				var doc = div.ownerDocument || div.document;
				
				try {
					//remove overlays
					me.removeLineOverlays(line);
				}
				catch(e) {
					if (dragged_line != line && console && console.log)
						console.log(e);
				}
				
				try {
					//remove end points
					me.removeLinePoints(line);
				}
				catch(e) {
					if (dragged_line != line && console && console.log)
						console.log(e);
				}
				
				//remove line svg
				if (svg[0])
					doc.body.appendChild(svg[0]);
			}
			
			try {
				line.remove(); //remove only removes elms from body node.
				
				$(div).remove(); //remove main line div
			}
			catch(e) {
				status = false;
				
				if (found_index >= 0)
					lines.splice(found_index, 0, line);
				
				if (div && div.parentNode && svg[0])
					div.appendChild(svg[0]);
				
				if (console && console.log)
					console.log(e);
			}
		}
		
		return status;
	};
	
	me.reset = function() {
		me.line_options = me.Defaults; //reset line_options to default
		
		return true;
	};
	
	me.setRenderMode = function(mode) {
		//do nothing bc the default is already SVG
	};
	
	me.importDefaults = function(options) {
		options = me.cloneOptions(options);
		me.mergeOptions(me.line_options, options); //overwrite the default options merging new options
	};
	
	me.draggable = function(elm, options) {
		elm = elm instanceof jQuery ? elm[0] : elm;
		
		if (!options)
			options = {};
		
		var start = options["start"];
		var drag = options["drag"];
		var stop = options["stop"];
		var parent = $(options["containment"] ? options["containment"] : elm.parentNode);
		var pointer_x = 0;
		var pointer_y = 0;
		var elm_lines = [];
		
		options["start"] = function(event, ui) {
			var parent_offset = parent.offset();
			var parent_scroll_top = parent.scrollTop();
			var parent_scroll_left = parent.scrollLeft();
			
			//prepare pointer with parent scroll or zoom
			pointer_y = (event.pageY - parent_offset.top + parent_scroll_top) / me.zoom - parseFloat( $(event.target).css('top') );
			pointer_x = (event.pageX - parent_offset.left + parent_scroll_left) / me.zoom - parseFloat( $(event.target).css('left') );
			
			//prepare related lines
			elm_lines = [];
			
			for (var i = 0, t = lines.length; i < t; i++) {
				var line = lines[i];
				
				if (line && (line.source[0] == elm || line.target[0] == elm)) {
					line._dragging = true;
					
					elm_lines.push(line);
					
					me.appendLineCanvasSVGToBody(line);
				}
			}
			
			//prepare scrollable containers
			var line_containers = me.getLineContainers(elm);
			
			if (options["container"]) {
				var line_container = $(options["container"])[0];
				
				if (line_container && line_containers.indexOf(line_container) == -1)
					line_containers.push(line_container);
			}
			
			scrollable_containers = me.getScrollableContainers(line_containers);
			
			if (typeof start == "function")
				start(event, ui);
		};
		
		options["drag"] = function(event, ui) {
			var parent_offset = parent.offset();
			var parent_height = parent.height();
			var parent_width = parent.width();
			var parent_scroll_top = parent.scrollTop();
			var parent_scroll_left = parent.scrollLeft();
			
			//fix for zoom or with parent scroll
			ui.position.top = me.parseFloatWithOneDecimalPlace((event.pageY - parent_offset.top + parent_scroll_top) / me.zoom - pointer_y); 
			ui.position.left = me.parseFloatWithOneDecimalPlace((event.pageX - parent_offset.left + parent_scroll_left) / me.zoom - pointer_x); 
			
			//check if element is outside of containment
			if (options["containment"]) {
				var overflow = $(options["containment"]).css("overflow");
				var is_scrollable = overflow == "auto" || overflow == "scroll" || overflow == "overlay";
				
				if (!is_scrollable) {
					if (ui.position.left < 0)
						ui.position.left = 0;
					
					if (ui.position.left + $(this).width() > parent_width)
						ui.position.left = parent_width - $(this).width();
					
					if (ui.position.top < 0)
						ui.position.top = 0;
					
					if (ui.position.top + $(this).height() > parent_height)
						ui.position.top = parent_height - $(this).height();
				}
			}
			
			//finally, make sure offset aligns with position
			ui.offset.top = me.parseFloatWithOneDecimalPlace(ui.position.top + parent_offset.top);
			ui.offset.left = me.parseFloatWithOneDecimalPlace(ui.position.left + parent_offset.left);
			
			//prepare lines
			for (var i = 0, t = elm_lines.length; i < t; i++) {
				var line = elm_lines[i];
				
				if (line) {
					me.prepareLineAnchors(line); //set some line anchors if necessary or unset them if were created unnecessary
					me.repaintLine(line, {do_not_zoom: line.svg_canvas ? true : false});
				}
			}
			
			if (typeof drag == "function")
				drag(event, ui);
		};
		
		options["stop"] = function(event, ui) {
			for (var i = 0, t = elm_lines.length; i < t; i++) {
				var line = elm_lines[i];
				
				if (line) {
					me.repaintLine(line, {do_not_zoom: line.svg_canvas ? true : false});
					
					if (line.svg_canvas)
						me.appendLineBodySVGToCanvas(line);
					
					if (me.zoom != 1)
						me.repaintLine(line);
					
					delete line._dragging;
				}
			}
			
			if (typeof stop == "function")
				stop(event, ui);
			
			scrollable_containers = null;
		};
		
		return $(elm).draggable(options);
	};
	
	me.droppable = function(elm, options) {
		elm = elm instanceof jQuery ? elm[0] : elm;
		
		if (!options)
			options = {};
		
		if (options["activeClass"] || options["hoverClass"]) {
			if (!options["classes"])
				options["classes"] = {}
			
			if (options["activeClass"])
				options["classes"]["ui-droppable-active"] = options["activeClass"];
			
			if (options["hoverClass"])
				options["classes"]["ui-droppable-hover"] = options["hoverClass"];
		}
		
		var drop_func = options["drop"];
		options["drop"] = function(event, ui) {
			if (typeof drop_func == "function" && drop_func(event, ui) === false)
				return false;
			
			var droppable = me.getElementTarget(elm);
			var dragged_elm = ui.draggable;
			var line_id_number = me.getConnectionIdNumber(dragged_elm);
			var line = me.getConnectionById(line_id_number);
			
			if (line) {
				if (droppable) {
					var droppable_options = null;
					var status = false;
					
					//set line to droppable
					try {
						var start_elm = line.start;
						var end_elm = line.end;
						
						start_elm = start_elm.element ? start_elm.element : start_elm;
						end_elm = end_elm.element ? end_elm.element : end_elm;
						
						if (line.isSource && start_elm != droppable) {
							status = true;
							line.start = droppable;
							line.source = $(droppable);
							line.sourceId = droppable.id;
							droppable_options = droppable.options;
						}
						else if (line.isTarget && end_elm != droppable) {
							status = true;
							line.end = droppable;
							line.target = $(droppable);
							line.targetId = droppable.id;
							droppable_options = droppable.options;
						}
					}
					catch(e) {
						if (me.debug && console && console.log)
							console.log(e);
					}
					
					if (status) {
						//set line options
						var line_options = me.cloneOptions(me.line_options);
						var original_options = line.original_options;
						
						if ($.isPlainObject(droppable_options)) {
							//prepare startPlugColor and endPlugColor in options if paintStyle is defined
							me.prepareInitialOptions(droppable_options);
							
							//merge droppable_options to line_options
							me.mergeOptions(line_options, droppable_options);
							
							//merge options to line_options. options take precedence over droppable_options
							me.mergeOptions(line_options, original_options);
						}
						else
							me.mergeOptions(line_options, original_options);
						
						line.options = line_options;
						
						//set anchor
						if (line_options["anchor"]) {
							var anchor_offset = me.getAnchorOffset(line_options["anchor"]);
							
							if (anchor_offset) {
								if (line.isSource) {
									var point_element = line.start.element ? line.start.element : line.start;
									line.start = LeaderLine.pointAnchor(point_element, anchor_offset);
									line.start.element = point_element;
									line.start.offset = anchor_offset;
									line.start_anchor = line_options["anchor"];
								}
								else if (line.isTarget) {
									var point_element = line.end.element ? line.end.element : line.end;
									line.end = LeaderLine.pointAnchor(point_element, anchor_offset);
									line.end.element = point_element;
									line.end.offset = anchor_offset;
									line.end_anchor = line_options["anchor"];
								}
							}
						}
						
						//set found droppable to true
						dragged_elm[0].found_droppable = true;
					}
				}
				else
					dragged_elm.css(dragged_elm.previous_offset);
			}
			
			return true;
		};
		
		return $(elm).droppable(options);
	};
	
	me.getSelector = function(selector) {
		if (selector instanceof jQuery)
			return selector[0];
		else if (selector instanceof Node)
			return selector;
    		
    		var elm = me.querySelector(document, selector);
		
		if (!elm)
			for (var i = 0, t = docs.length; i < t; i++) {
				elm = me.querySelector(docs[i], selector);
				
				if (elm)
					break;
			}
		
		return elm;
	};
	
	//This is very important bc querySelector method uses CSS3 selectors for querying the DOM and CSS3 doesn't support ID selectors that start with a digit. For these cases, we must use dcoument.getElementById method.
	//https://stackoverflow.com/questions/37270787/uncaught-syntaxerror-failed-to-execute-queryselector-on-document
	me.querySelector = function(parent, selector) {
		try {
			return parent.querySelector(selector);
		}
		catch(e1) {
			if (typeof selector == "string" && selector.substr(0, 1) == "#") {
				var parts = selector.split(" ");
				var id = parts[0].substr(1);
				
				if (id) {
					var doc = parent.getElementById ? parent : (parent.ownerDocument || parent.document);
					
					try {
						var elm = doc.getElementById(id);
						
						if (elm) {
							parts.shift();
							
							if (parts.length > 0) {
								var sub_selector = parts.join(" ");
								
								return me.querySelector(elm, sub_selector);
							}
							else
								return elm;
						}
						
						return null;
					}
					catch(e2) {
						if (console && console.log)
							console.log(e2);
						
						return null;
					}
				}
			}
			
			if (console && console.log)
				console.log(e1);
			
			return null;
		}
	};
	
	me.appendLineCanvasSVGToBody = function(line, opts) {
		if (line.canvas && $(line.canvas).children("svg")[0]) {
			opts = opts ? opts : {};
			
			//1 step: get offset. offset is relative to body
			var line_canvas = $(line.canvas);
			var offset = line_canvas.offset(); //get offset related with window.body. Note that this is Not related with container, is related with body.
			var top = parseFloat(line_canvas[0].style.top);
			var left = parseFloat(line_canvas[0].style.left);
			var width = parseFloat(line_canvas[0].style.width);
			var height = parseFloat(line_canvas[0].style.height);
			var z_index = line_canvas[0].style.zIndex;
			var svg = line_canvas.children("svg");
			//console.log("1- GET DIV style:"+line_canvas.attr("style"));
			
			svg[0].classList.add("leader-line");
			svg.css("width", width + "px");
			svg.css("height", height + "px");
			//console.log("line_canvas style: "+line_canvas.attr("style"));
			//console.log("svg style in line_canvas: "+svg.attr("style"));
			
			//2 step: append to body. Note that now the line canvas has a new offset related with the new parent, which is the body.
			$("body").append(svg); //append to body
			
			//3 step: set offset that we got before and that is relative to body.
			svg.offset(offset); //set new offset in css, bc previous css is relative to the container
			//console.log("svg style in body before position: "+svg.attr("style"));
			
			if ($.isNumeric(z_index))
				svg.css("z_index", z_index);
			
			//4 step: prepare dimensions according with zoom
			var zoom_exists = me.zoom != 1 && line.options["container"] && !opts["do_not_zoom"];
			
			if (zoom_exists) {
				var line_container = $(line.options["container"]);
				var co = line_container.offset();
				var st = line_container.scrollTop();
				var sl = line_container.scrollLeft();
				
				//st and sl must be added in the zoom calculation and not after
				top = me.parseFloatWithOneDecimalPlace((top - st) * me.zoom);
				left = me.parseFloatWithOneDecimalPlace((left - sl) * me.zoom);
				top = top + co.top;
				left = left + co.left;
				
				//console.log("zoom_exists before:"+width+":"+me.parseFloatWithOneDecimalPlace(width * me.zoom));
				svg.css("top", top + "px");
				svg.css("left", left + "px");
				svg.css("width", me.parseFloatWithOneDecimalPlace(width * me.zoom) + "px");
				svg.css("height", me.parseFloatWithOneDecimalPlace(height * me.zoom) + "px");
				//console.log("2- SET SVG style:"+svg.attr("style"));
			}
			
			line.svg_canvas = svg[0];
		}
	};
	
	me.appendLineBodySVGToCanvas = function(line, opts) {
		if (line.canvas && line.svg_canvas) {
			opts = opts ? opts : {};
			
			//1 step: get new offset relative to body
			var line_canvas = $(line.canvas);
			var svg = $(line.svg_canvas);
			var offset = svg.offset();
			var top = parseFloat(svg[0].style.top);
			var left = parseFloat(svg[0].style.left);
			var width = parseFloat(svg[0].style.width);
			var height = parseFloat(svg[0].style.height);
			var z_index = svg[0].style.zIndex;
			//console.log("svg style in body after position: "+svg.attr("style"));
			//console.log("3- GET SVG style:"+svg.attr("style"));
			
			//2 step: append line canvas to previous parent. Note that now the line canvas has a new offset related with the new parent.
			line_canvas.append(svg);
			
			//3 step: set the offset so the line canvas can be re-positioning in the parent.
			line_canvas.css("width", width + "px");
			line_canvas.css("height", height + "px");
			line_canvas.offset(offset);
			
			if ($.isNumeric(z_index))
				line_canvas.css("z_index", z_index);
			
			//console.log("line_canvas style after position: "+svg.attr("style"));
			
			svg.removeAttr("class");
			svg.removeAttr("style");
			//svg.attr("style", "width:100%; height:100%;");
			
			//4 step: prepare dimensions according with zoom
			var zoom_exists = me.zoom != 1 && line.options["container"] && !opts["do_not_zoom"];
			
			if (zoom_exists) {
				var line_container = $(line.options["container"]);
				var co = line_container.offset();
				var st = line_container.scrollTop();
				var sl = line_container.scrollLeft();
				
				//st and sl must be added after the zoom calculation
				top = top - co.top;
				left = left - co.left;
				top = me.parseFloatWithOneDecimalPlace(top / me.zoom) + st;
				left = me.parseFloatWithOneDecimalPlace(left / me.zoom) + sl;
				
				//console.log("zoom_exists after:"+width+":"+me.parseFloatWithOneDecimalPlace(width / me.zoom));
				line_canvas.css("top", top + "px");
				line_canvas.css("left", left + "px");
				line_canvas.css("width", me.parseFloatWithOneDecimalPlace(width / me.zoom) + "px");
				line_canvas.css("height", me.parseFloatWithOneDecimalPlace(height / me.zoom) + "px");
				//console.log("4- SET DIV style:"+line_canvas.attr("style"));
			}
			
			delete line.svg_canvas;
		}
	};
	
	me.repaintLine = function(line, opts) {
		if (me.repaint_enable) {
			opts = opts ? opts : {};
			
			var force = opts["force"];
			
			if (line.canvas && $(line.canvas).children("svg")[0]) {
				//2nd step: update line position
				//2.1 step: then get offset. offset is relative to body
				//2.2 step: append to body. Note that now the line canvas has a new offset related with the new parent, which is the body.
				//2.3 step: set offset that we got before and that is relative to body.
				//2.4 step: prepare dimensions according with zoom
				me.appendLineCanvasSVGToBody(line, opts);
				
				//2.4 step: update line to right position
				var ret = line.position();
				//console.log("ret.updated.position:"+ret.updated.position);
				
				//2.5 step: check if line was really positioning and if not, force it to repositioning it again. This is important when we change the line.path to another one
				if (force) {
					var position_updated = ret && ret.updated && ret.updated.position;
					
					if (!position_updated) {
						var o = line.target.offset();
						line.target.offset({top: o.top - 1, left: o.left}); //change target offset so the position function gets executed in the LeaderLine.
						
						ret = line.position();
						
						line.target.offset({top: o.top, left: o.left}); //sets the original target offset.
					}
				}
				
				//2.6 step: get new offset relative to body
				//2.7 step: append line canvas to previous parent. Note that now the line canvas has a new offset related with the new parent.
				//2.8 step: set the offset so the line canvas can be re-positioning in the parent.
				//2.9 step: prepare dimensions according with zoom
				me.appendLineBodySVGToCanvas(line, opts);
			}
			else
				line.position();
			
			//3rd step: update overlays and points
			me.repaintLineRelatives(line);
		}
	};
	
	me.repaintLineRelatives = function(line, opts) {
		if (me.repaint_enable) {
			//3rd step: update overlays and points
			if (line.startLabelOverlayCanvas)
				line.startLabelOverlayCanvas.repaint(opts);
			
			if (line.middleLabelOverlayCanvas)
				line.middleLabelOverlayCanvas.repaint(opts);
			
			if (line.endLabelOverlayCanvas)
				line.endLabelOverlayCanvas.repaint(opts);
			
			if (line.startPointCanvas)
				line.startPointCanvas.repaint(opts);
			
			if (line.endPointCanvas)
				line.endPointCanvas.repaint(opts);
		}
	};
	
	me.repaint = function(elm, position, opts) { //elm: task
		if (me.repaint_enable) {
			elm = elm instanceof jQuery ? elm[0] : elm;
			
			if (position && $.isPlainObject(position))
				elm.offset(position);
			
			for (var i = 0, t = lines.length; i < t; i++) {
				var line = lines[i];
				
				if (line && (line.source[0] == elm || line.target[0] == elm))
					me.repaintLine(line, opts);
			}
		}
	};
	
	me.repaintEverything = function(opts) {
		if (me.repaint_enable)
			for (var i = 0, t = lines.length; i < t; i++) {
				var line = lines[i];
				
				if (line)
					me.repaintLine(line, opts);
			}
	};
	
	me.deleteEveryEndpoint = function() {
		var status = true;
		
		for (var i = 0, t = lines.length; i < t; i++) {
			var line = lines[i];
			
			if (line) {
				if (line.sourceEndpoint && !me.deleteEndpoint(line.sourceEndpoint))
					status = false;
				
				if (line.targetEndpoint && !me.deleteEndpoint(line.targetEndpoint))
					status = false;
			}
		}
		
		return status;
	};
	
	me.deleteEndpoint = function(end_point) {
		end_point = end_point instanceof jQuery ? end_point[0] : end_point;
		
		if (end_point) {
			var status = true;
			
			for (var i = 0, t = lines.length; i < t; i++) {
				var line = lines[i];
				
				if (line && (line.sourceEndpoint == end_point || line.targetEndpoint == end_point)) {
					if (me.detachConnection(line))
						i--;
					else
						status = false;
				}
			}
			
			return status;
		}
		
		return false;
	};
	
	me.removeAllEndpoints = function(elm) {
		elm = elm instanceof jQuery ? elm[0] : elm;
		
		if (elm) {
			var end_point = me.getElementSource(elm);
			
			if (end_point) 
				return me.deleteEndpoint(end_point);
			else {
				var target = me.getElementTarget(elm);
				
				if (target) {
					var target_sources = me.getTargetSources(elm);
					var status = true;
					
					for (var i = 0, t = target_sources.length; i < t; i++)
						if (!me.deleteEndpoint(target_sources[i]))
							status = false;
					
					for (var i = 0, t = lines.length; i < t; i++) {
						var line = lines[i];
						
						if (line && (line.source.is(target) || line.target.is(target))) {
							if (me.detachConnection(line))
								i--;
							else
								status = false;
						}
					}
					
					return status;
				}
			}
		}
		
		return false;
	};
	
	//selects all end points
	me.selectEndpoints = function() {
		var points = [];
		
		for (var i = 0, t = lines.length; i < t; i++) {
			var line = lines[i];
			
			if (line) {
				if (line.sourceEndpoint)
					points.push(line.sourceEndpoint);
				
				if (line.targetEndpoint)
					points.push(line.targetEndpoint);
			}
		}
		
		return $(points);
	};
	
	me.deleteEveryLinePoint = function() {
		for (var i = 0, t = lines.length; i < t; i++) {
			var line = lines[i];
			
			if (line)
				me.removeLinePoints(line);
		}
		
		return true;
	};
	
	me.deleteLinePoint = function(end_point) {
		end_point = end_point instanceof jQuery ? end_point[0] : end_point;
		
		if (end_point)
			for (var i = 0, t = lines.length; i < t; i++) {
				var line = lines[i];
				
				if (line) {
					if (line.startPointCanvas == end_point) {
						$(line.startPointCanvas).remove();
						line.startPointCanvas = null;
						line.startPoint = null;
						return true;
					}
					else if (line.endPointCanvas == end_point) {
						$(line.endPointCanvas).remove();
						line.endPointCanvas = null;
						line.endPoint = null;
						return true;
					}
				}
			}
		
		return false;
	};
	
	me.removeAllLinePoints = function(elm) {
		elm = elm instanceof jQuery ? elm[0] : elm;
		var line_id_number = me.getConnectionIdNumber(elm);
		var line = me.getConnectionById(line_id_number);
		
		if (line) {
			if (line.startPointCanvas) {
				$(line.startPointCanvas).remove();
				line.startPointCanvas = null;
				line.startPoint = null;
			}
			
			if (line.endPointCanvas) {
				$(line.endPointCanvas).remove();
				line.endPointCanvas = null;
				line.endPoint = null;
			}
			
			return true;
		}
		
		return false;
	};
	
	//selects all end points
	me.selectLinePoints = function() {
		var points = [];
		
		for (var i = 0, t = lines.length; i < t; i++) {
			var line = lines[i];
			
			if (line) {
				if (line.startPointCanvas)
					points.push(line.startPointCanvas);
				
				if (line.endPointCanvas)
					points.push(line.endPointCanvas);
			}
		}
		
		return $(points);
	};
	
	me.setZoom = function(zoom_level) {
		me.zoom = zoom_level;
	};
	
	me.getLineDirection = function(line) {
		var direction = null;
		
		if (line.start && line.end) {
			var start = $(line.start.element ? line.start.element : line.start);
			var end = $(line.end.element ? line.end.element : line.end);
			var so = start.offset();
			var eo = end.offset();
			var sw = start.width();
			var sh = start.height();
			var ew = end.width();
			var eh = end.height();
			var vertical_direction = null;
			var horizontal_direction = null;
			
			if (sw > 0)
				so.left += me.parseFloatWithOneDecimalPlace(sw / 2);
			
			if (sh > 0)
				so.top += me.parseFloatWithOneDecimalPlace(sh / 2);
			
			if (ew > 0)
				eo.left += me.parseFloatWithOneDecimalPlace(ew / 2);
			
			if (eh > 0)
				eo.top += me.parseFloatWithOneDecimalPlace(eh / 2);
			
			if (eo.top < so.top)
				vertical_direction = "top";
			else if (eo.top > so.top)
				vertical_direction = "bottom";
			
			if (eo.left < so.left)
				horizontal_direction = "left";
			else if (eo.left > so.left)
				horizontal_direction = "right";
			
			if (vertical_direction)
				direction = vertical_direction;
			
			if (horizontal_direction)
				direction = (direction ? direction + "_" : "") + horizontal_direction;
		}
		
		return direction;
	};
	
	me.getLineSocketType = function(line_canvas, source_target) {
		line_canvas = $(line_canvas);
		source_target = $(source_target);
		
		var sto = source_target.offset();
		var stw = source_target.width();
		var sth = source_target.height();
		var lco = line_canvas.offset();
		var lcw = line_canvas.width();
		var lch = line_canvas.height();
		
		return me.getOffsetSocketType(lco, lcw, lch, sto, stw, sth);
	};
	
	me.getOffsetSocketType = function(a_offset, a_width, a_height, b_offset, b_width, b_height) {
		var diff_x = 0;
		var diff_y = 0;
		var type = null;
		
		a_offset = Object.assign({}, a_offset);
		b_offset = Object.assign({}, b_offset);
		
		a_offset.bottom = a_offset.top + a_height;
		a_offset.right = a_offset.left + a_width;
		b_offset.bottom = b_offset.top + b_height;
		b_offset.right = b_offset.left + b_width;
		
		for (var i = 0; i < 30; i++) {
			if (a_offset.top + diff_y >= b_offset.bottom)
				type = "bottom";
			else if (a_offset.bottom - diff_y <= b_offset.top)
				type = "top";
			else if (a_offset.left + diff_x >= b_offset.right)
				type = "right";
			else if (a_offset.right - diff_x <= b_offset.left)
				type = "left";
			
			if (type)
				break;
			
			diff_x += 2;
			diff_y += 2;
		}
		
		return type;
	};
	
	me.getSocketPointOffset = function(source_target, position_type, socket_width, socket_height) {
		source_target = $(source_target);
		
		var offset = {};
		var sto = source_target.offset();
		var stw = source_target.width();
		var sth = source_target.height();
		
		switch (position_type) {
			case "top":
				offset.top = sto.top;
				offset.left = sto.left + (stw / 2);
				break;
			case "bottom":
				offset.top = sto.top + sth;
				offset.left = sto.left + (stw / 2);
				break;
			case "left":
				offset.top = sto.top + (sth / 2);
				offset.left = sto.left;
				break;
			case "right":
				offset.top = sto.top + (sth / 2);
				offset.left = sto.left + stw;
				break;
		}
		
		if (socket_height && offset.top)
			offset.top -= socket_height/2;
		
		if (socket_width && offset.left)
			offset.left -= socket_width/2;
		
		//round offsets but leave decimal place
		offset.top = me.parseFloatWithOneDecimalPlace(offset.top);
		offset.left = me.parseFloatWithOneDecimalPlace(offset.left);
		
		return offset;
	};
	
	me.getAnchorPointOffset = function(point_anchor, socket_width, socket_height) {
		var offset = {};
		var x = point_anchor.offset.x;
		var y = point_anchor.offset.y;
		var source_target = $(point_anchor.element);
		var sto = source_target.offset();
		var stw = source_target.width();
		var sth = source_target.height();
		
		if (("" + x).indexOf("%") != -1)
			x = parseFloat(x) / 100 * stw;
		
		if (("" + y).indexOf("%") != -1)
			y = parseFloat(y) / 100 * sth;
		
		offset.top = sto.top + y;
		offset.left = sto.left + x;
		
		if (socket_height && offset.top)
			offset.top -= socket_height/2;
		
		if (socket_width && offset.left)
			offset.left -= socket_width/2;
		
		//round offsets but leave decimal place
		offset.top = me.parseFloatWithOneDecimalPlace(offset.top);
		offset.left = me.parseFloatWithOneDecimalPlace(offset.left);
		
		return offset;
	};
	
	me.prepareOffsetBasedInContainer = function(offset, container) {
		if (container) {
			var container = $(container);
			var co = container.offset();
			var st = container.scrollTop();
			var sl = container.scrollLeft();
			
			offset.top = offset.top - co.top + st;
			offset.left = offset.left - co.left + sl;
		}
		
		return offset;
	};
	
	me.getAnchorOffset = function(anchor) {
		if (anchor && typeof anchor == "string") {
			anchor = ("" + anchor).toLowerCase();
			
			switch(anchor) {
				case "left": 
				case "leftmiddle": 
				case "left_middle": 
					return {y: "50%", x: 0};
				case "lefttop": 
				case "left_top": 
					return {y: 0, x: 0};
				case "leftbottom": 
				case "left_bottom": 
					return {y: "100%", x: 0};
				case "right": 
				case "rightmiddle": 
				case "right_middle": 
					return {y: "50%", x: "100%"};
				case "righttop": 
				case "right_top": 
					return {y: 0, x: "100%"};
				case "rightbottom": 
				case "right_bottom": 
					return {y: "100%", x: "100%"};
				case "top": 
				case "topmiddle": 
				case "top_middle": 
					return {y: 0, x: "50%"};
				case "topleft": 
				case "top_left": 
					return {y: 0, x: 0};
				case "topright": 
				case "top_right": 
					return {y: 0, x: "100%"};
				case "bottom": 
				case "bottommiddle": 
				case "bottom_middle": 
					return {y: "100%", x: "50%"};
				case "bottomleft": 
				case "bottom_left": 
					return {y: "100%", x: 0};
				case "bottomright": 
				case "bottom_right": 
					return {y: "100%", x: "100%"};
			}
		}
		else if ($.isPlainObject(anchor))
			return anchor;
		
		return null;
	};
	
	me.getAvailableAnchorsOffsets = function(point_offset, diff) {
		function getNewAnchorOffset(prop_name, prop_value, point_offset) {
			var new_po = {};
			
			if (prop_name == "x") {
				new_po.x = prop_value;
				new_po.y = point_offset.y;
			}
			else {
				new_po.x = point_offset.x;
				new_po.y = prop_value;
			}
			
			return new_po;
		}
		
		var available_anchors_offsets = [];
		
		if (point_offset.type) {
			var prop_name = null, begin = null, finish = null;
			
			if (point_offset.type == "top" || point_offset.type == "bottom") {
				prop_name = "x";
				begin = point_offset.points[3].x;
				finish = point_offset.points[1].x;
			}
			else {
				prop_name = "y";
				begin = point_offset.points[0].y;
				finish = point_offset.points[2].y;
			}
			
			var s = point_offset[prop_name];
			var e = point_offset[prop_name];
			var s_ok = true;
			var e_ok = true;
			
			while (s_ok || e_ok) {
				if (s <= begin)
					s = begin;
				
				if (e >= finish)
					e = finish;
				
				if (s_ok) {
					var new_point_offset = getNewAnchorOffset(prop_name, s, point_offset);
					available_anchors_offsets.push(new_point_offset);
				}
				
				if (e_ok && s != e) {
					var new_point_offset = getNewAnchorOffset(prop_name, e, point_offset);
					available_anchors_offsets.push(new_point_offset);
				}
				
				if (s == begin)
					s_ok = false;
				
				if (e == finish)
					e_ok = false;
				
				s = s - diff;
				e = e + diff;
			}
		}
		
		return available_anchors_offsets;
	};
	
	me.getLinePointsOffsets = function(line, original_offsets) {
		function getPointsOnSides(element) {
			var rect = element.getBoundingClientRect(),
				top = rect.top,
				left = rect.left,
				right = rect.right,
				bottom = rect.bottom,
				width = rect.width,
				height = rect.height;
			
			var zoom_exists = me.zoom != 1 && line.canvas && line.options["container"];
			
			if (zoom_exists) {
				var line_container = $(line.options["container"]);
				var co = line_container.offset();
				var st = line_container.scrollTop();
				var sl = line_container.scrollLeft();
				
				//st and sl must be only added outside the zoom calculation. Do not add st and sl inside of the me.parseFloatWithOneDecimalPlace.
				top = co.top + st + me.parseFloatWithOneDecimalPlace((top - co.top) / me.zoom);
				left = co.left + sl + me.parseFloatWithOneDecimalPlace((left - co.left) / me.zoom);
				right = co.left + sl + me.parseFloatWithOneDecimalPlace((right - co.left) / me.zoom);
				bottom = co.top + st + me.parseFloatWithOneDecimalPlace((bottom - co.top) / me.zoom);
				width = me.parseFloatWithOneDecimalPlace(width / me.zoom);
				height = me.parseFloatWithOneDecimalPlace(height / me.zoom);
			}
			
			top += window.pageYOffset;
			left += window.pageXOffset;
			right += window.pageXOffset;
			bottom += window.pageYOffset;
			
			return [
				{x: left + width / 2, y: top},
				{x: right, y: top + height / 2},
				{x: left + width / 2, y: bottom},
				{x: left, y: top + height / 2}
			];
		}

		function getPointsLength(p0, p1) {
			var lx = p0.x - p1.x,
				ly = p0.y - p1.y;
			
			return Math.sqrt(lx * lx + ly * ly);
		}
		
		function getAnchorPoints(anchor, points) {
			var offset_x = anchor.offset.x;
			var offset_y = anchor.offset.y;
			
			if (("" + offset_x).indexOf("%") != -1)
				offset_x = me.parseFloatWithOneDecimalPlace($(anchor.element).width() * parseFloat(offset_x) / 100);
			
			if (("" + offset_y).indexOf("%") != -1)
				offset_y = me.parseFloatWithOneDecimalPlace($(anchor.element).height() * parseFloat(offset_y) / 100);
			
			return {
				x: points[3].x + offset_x, //left + offset_x
				y: points[0].y + offset_y, //top + offset_y
			};
		}
		
		//prepare point offsets
		var line_start_ref = line.start.element ? line.start.element : line.start;
		var line_end_ref = line.end.element ? line.end.element : line.end;
		var points_start = getPointsOnSides(line_start_ref);
		var points_end = getPointsOnSides(line_end_ref);
		
		var min_len = null;
		var min_points = null;
		
		for (var i = 0, ti = points_start.length; i < ti; i++) {
			var point_start = points_start[i];
			
			for (var j = 0, tj = points_end.length; j < tj; j++) {
				var point_end = points_end[j];
				var len = getPointsLength(point_start, point_end);
				
				if (min_len == null || len < min_len) {
					min_len = len;
					min_points = {
						start: Object.assign({}, point_start), 
						end: Object.assign({}, point_end)
					};
				}
			}
		}
		
		//prepare anchors offsets, if exists
		if (!original_offsets) {
			if (line.start.element)
				min_points.start = getAnchorPoints(line.start, points_start);
			
			if (line.end.element)
				min_points.end = getAnchorPoints(line.end, points_end);
		}
		
		//prepare offsets type
		var offset = {left: points_start[3].x, top: points_start[0].y};
		var width = points_start[1].x - points_start[3].x;
		var height = points_start[2].y - points_start[0].y;
		min_points.start.type = me.getOffsetSocketType({left: min_points.start.x, top: min_points.start.y}, 1, 1, offset, width, height);
		min_points.start.points = points_start;
		
		var offset = {left: points_end[3].x, top: points_end[0].y};
		var width = points_end[1].x - points_end[3].x;
		var height = points_end[2].y - points_end[0].y;
		min_points.end.type = me.getOffsetSocketType({left: min_points.end.x, top: min_points.end.y}, 1, 1, offset, width, height);
		min_points.end.points = points_end;
		
		return min_points;
	};
	
	me.mergeOptions = function(options_1, options_2) {
		if ($.isPlainObject(options_1) && $.isPlainObject(options_2))
			for (var k in options_2) {
				var v = options_2[k];
				
				if ($.isPlainObject(v)) {
					if ($.isPlainObject(options_1[k]))
						me.mergeOptions(options_1[k], v);
					else
						options_1[k] = v;
				}
				else
					options_1[k] = v;
			}
		
		return options_1;
	};
	
	me.cloneOptions = function(obj) {
		//return Object.assign({}, obj); //Note that Object.assign doesn't copy the inner objects, which means it will remain with the references for the inner objects. Basically the Object.assign only clones the properties in the first level.
		//Do not use JSON.parse(JSON.stringify(obj)), bc obj may contain DOM objects that will loose its references, and this elements we want to keep their reference.
		
		if ($.isPlainObject(obj)) {
			if (typeof assignObjectRecursively == "function")
				return assignObjectRecursively({}, obj);
			
			var new_obj = Object.assign({}, obj);
			
			for (var k in new_obj)
				if ($.isPlainObject(new_obj[k]))
					new_obj[k] = me.cloneOptions(new_obj[k]);
			
			return new_obj;
		}
		
		return obj;
	}
	
	me.prepareInitialOptions = function(options) {
		//prepare startPlugColor and endPlugColor in options if paintStyle is defined
		me.setOptionsPropBasedInConnectorStyle(options, "color", ["strokeStyle", "fillStyle", "color"]);
		me.setOptionsPropBasedInConnectorStyle(options, "startPlugColor", ["strokeStyle", "fillStyle", "color"]);
		me.setOptionsPropBasedInConnectorStyle(options, "endPlugColor", ["strokeStyle", "fillStyle", "color"]);
		me.setOptionsPropBasedInConnectorStyle(options, "size", ["lineWidth", "size"]);
		me.setOptionsPropBasedInConnectorStyle(options, "startPlugSize", ["lineWidth", "size"]);
		me.setOptionsPropBasedInConnectorStyle(options, "endPlugSize", ["lineWidth", "size"]);
		
		me.setOptionsPropBasedInPaintStyle(options, "color", ["strokeStyle", "fillStyle", "color"]);
		me.setOptionsPropBasedInPaintStyle(options, "startPlugColor", ["fillStyle", "color"]);
		me.setOptionsPropBasedInPaintStyle(options, "endPlugColor", ["fillStyle", "color"]);
		me.setOptionsPropBasedInPaintStyle(options, "size", ["lineWidth", "size"]);
		me.setOptionsPropBasedInPaintStyle(options, "startPlugSize", ["lineWidth", "size"]);
		me.setOptionsPropBasedInPaintStyle(options, "endPlugSize", ["lineWidth", "size"]);
	};
	
	me.setOptionsPropBasedInConnectorStyle = function(options, prop_name, paint_style_props) {
		//prepare prop_name in options if connectorStyle is defined
		if (!options.hasOwnProperty(prop_name) && options["connectorStyle"] && $.isPlainObject(options["connectorStyle"])) {
			for (var i = 0, t = paint_style_props.length; i < t; i++) {
				var paint_style_prop = paint_style_props[i];
				
				if (options["connectorStyle"].hasOwnProperty(paint_style_prop)) {
					options[prop_name] = options["connectorStyle"][paint_style_prop];
					break;
				}
			}
		}
	};
	
	me.getOptionsPropBasedInConnectorStyle = function(options, prop_name, paint_style_props) {
		//prepare prop_name in options if connectorStyle is defined
		if (!options.hasOwnProperty(prop_name) && options["connectorStyle"] && $.isPlainObject(options["connectorStyle"])) {
			for (var i = 0, t = paint_style_props.length; i < t; i++) {
				var paint_style_prop = paint_style_props[i];
				
				if (options["connectorStyle"].hasOwnProperty(paint_style_prop))
					return options["connectorStyle"][paint_style_prop];
			}
		}
		
		return null;
	};
	
	me.setOptionsPropBasedInPaintStyle = function(options, prop_name, paint_style_props) {
		//prepare prop_name in options if paintStyle is defined
		if (!options.hasOwnProperty(prop_name) && options["paintStyle"] && $.isPlainObject(options["paintStyle"])) {
			for (var i = 0, t = paint_style_props.length; i < t; i++) {
				var paint_style_prop = paint_style_props[i];
				
				if (options["paintStyle"].hasOwnProperty(paint_style_prop)) {
					options[prop_name] = options["paintStyle"][paint_style_prop];
					break;
				}
			}
		}
	};
	
	me.getOptionsPropBasedInPaintStyle = function(options, prop_name, paint_style_props) {
		//prepare prop_name in options if paintStyle is defined
		if (!options.hasOwnProperty(prop_name) && options["paintStyle"] && $.isPlainObject(options["paintStyle"])) {
			for (var i = 0, t = paint_style_props.length; i < t; i++) {
				var paint_style_prop = paint_style_props[i];
				
				if (options["paintStyle"].hasOwnProperty(paint_style_prop))
					return options["paintStyle"][paint_style_prop];
			}
		}
		
		return null;
	};
	
	me.setOptionsPropBasedInHoverPaintStyle = function(options, prop_name, paint_style_props) {
		//prepare prop_name in options if paintStyle is defined
		if (!options.hasOwnProperty(prop_name) && options["hoverPaintStyle"] && $.isPlainObject(options["hoverPaintStyle"])) {
			for (var i = 0, t = paint_style_props.length; i < t; i++) {
				var paint_style_prop = paint_style_props[i];
				
				if (options["hoverPaintStyle"].hasOwnProperty(paint_style_prop)) {
					options[prop_name] = options["hoverPaintStyle"][paint_style_prop];
					break;
				}
			}
		}
	};
	
	me.getOptionsPropBasedInHoverPaintStyle = function(options, prop_name, paint_style_props) {
		//prepare prop_name in options if paintStyle is defined
		if (!options.hasOwnProperty(prop_name) && options["hoverPaintStyle"] && $.isPlainObject(options["hoverPaintStyle"])) {
			for (var i = 0, t = paint_style_props.length; i < t; i++) {
				var paint_style_prop = paint_style_props[i];
				
				if (options["hoverPaintStyle"].hasOwnProperty(paint_style_prop)) 
					return options["hoverPaintStyle"][paint_style_prop];
			}
		}
		
		return null;
	};
	
	//round value but leave decimal place
	me.parseFloatWithOneDecimalPlace = function(value) {
		return parseInt( Math.round(parseFloat(value) * 10) ) / 10;
	};
	
	me.setRepaintStatus = function(status) {
		me.repaint_enable = status;
	};
	
	me.getRepaintStatus = function() {
		return me.repaint_enable;
	};
}
