//default vars from server
//window.public_user_type_id = 1; //This is set by default by the system when we load a page.
//window.logged_user_type_ids = [...]; //This is set by default by the system when we load a page.
//window.get_resource_url = null; //This is set by default by the system when we load a page.
//window.chart_js_url = null; //This is for the ChartHandler.
//window.calendar_js_url = null; //This is for the CalendarHandler.
//window.sla_results = {...}; //This is set by default by the system when we load a page.

//IMPORTANT: DO NOT ADD HERE THE ADD_SECURITY_CODE_HERE, BC THIS FILE WILL BE USED IN THE PROJECTS LEVEL TOO AND THE PROJECTS CAN BE EXPORTED TO ANY CLIENT SERVER, WHICH MEANS IT CAN RUN IN ANY DOMAIN.

(function() {
	//avoids this file to give an error if called twice.
	if (typeof MyWidgetResourceLib == "function")
		return;
	
	var MyWidgetResourceLib = window.MyWidgetResourceLib = function() {
		return new MyWidgetResourceLib.fn.init();
	};

	/****************************************************************************************
	 *				 START: CORE FUNCTIONS 					*
	 ****************************************************************************************/
	 MyWidgetResourceLib.fn = MyWidgetResourceLib.prototype = {
		/* PROPERTIES */
		
		public_user_type_id: null,
		logged_user_type_ids: null,
		
		/* CORE FUNCTIONS */
		
		init: function() {
			return this;
		},
		
		toString: Object.prototype.toString,
		
		assignObjectRecursively: function(to_obj, from_obj) {
			//return Object.assign(to_obj, from_obj); //Note that Object.assign doesn't copy the inner objects, which means it will remain with the references for the inner objects. Basically the Object.assign only clones the properties in the first level.
			//Do not use JSON.parse(JSON.stringify(from_obj)), bc obj may contain DOM objects that will loose its references, and this elements we want to keep their reference.
			
			if (typeof assignObjectRecursively == "function")
				return assignObjectRecursively(to_obj, from_obj);
			
			var is_to_arr = this.isArray(to_obj);
			
			if (this.isPlainObject(from_obj)) {
				for (var k in from_obj) {
					var v = from_obj[k];
					
					if (this.isPlainObject(v))
						v = this.assignObjectRecursively({}, v);
					else if (this.isArray(v))
						v = this.assignObjectRecursively([], v);
					
					if (is_to_arr)
						to_obj.push(v);
					else
						to_obj[k] = v;
				}
				
				return to_obj;
			}
			else if (this.isArray(from_obj)) {
				for (var i = 0, t = from_obj.length; i < t; i++) {
					var v = from_obj[i];
					
					if (this.isPlainObject(v))
						v = this.assignObjectRecursively({}, v);
					else if (this.isArray(v))
						v = this.assignObjectRecursively([], v);
					
					if (is_to_arr)
						to_obj.push(v);
					else
						to_obj[i] = v;
				}
				
				return to_obj;
			}
			
			return from_obj;
		},
		
		/* JQUERY SIMULATION FUNCTIONS */
		
		//Simulates the jquery function: $.isNumeric(obj);
		isNumeric: function(obj) {
			return !isNaN( parseFloat(obj) ) && isFinite(obj);
		},
		
		//Simulates the jquery function: $.isArray(obj);
		isArray: function(obj) {
			return (typeof Array.isArray == "function" && Array.isArray(obj)) || Object.prototype.toString.call(obj) === '[object Array]';
		},
		
		//Simulates the jquery function: $.isPlainObject(obj); Copied from jquery 3.6.0
		isPlainObject: function(obj) {
			//return (typeof obj === "object" && obj !== null && typeof Array.isArray == "function" && !Array.isArray(obj)) || Object.prototype.toString.call(obj) === "[object Object]";
			
			//defined some jquery global vars
			var getProto = Object.getPrototypeOf;
			var class2type = {};
			var toString = class2type.toString;
			var hasOwn = class2type.hasOwnProperty;
			var fnToString = hasOwn.toString;
			var ObjectFunctionString = fnToString.call( Object );
			
			//code from isPlainObject in jquery 3.6.0
			var proto, Ctor;

			// Detect obvious negatives
			// Use toString instead of jQuery.type to catch host objects
			if ( !obj || toString.call( obj ) !== "[object Object]" ) {
				return false;
			}

			proto = getProto( obj );

			// Objects with no prototype (e.g., `Object.create( null )`) are plain
			if ( !proto ) {
				return true;
			}

			// Objects with prototype are plain iff they were constructed by a global Object function
			Ctor = hasOwn.call( proto, "constructor" ) && proto.constructor;
			return typeof Ctor === "function" && fnToString.call( Ctor ) === ObjectFunctionString;
		},
		
		//Simulates the jquery function: $.isEmptyObject(obj);
		isEmptyObject: function(obj) {
			for (var name in obj)
				return false;
			return true;
		},
		
		//Simulates the jquery function: $.each(array, handler);
		each: function(obj, handler) {
			if (MyWidgetResourceLib.fn.isArray(obj) || NodeList.prototype.isPrototypeOf(obj) || HTMLCollection.prototype.isPrototypeOf(obj)) {
				for (var i = 0, t = obj.length; i < t; i++)
					if (handler(i, obj[i]) === false)
						break;
				
				return true;
			}
			else if (MyWidgetResourceLib.fn.isPlainObject(obj)) {
				for (var k in obj)
					if (handler(k, obj[k]) === false)
						break;
			
				return true;
			}
			
			return false;
		},
		
		//Simulates the jquery function: $(elm).data(key, value);
		setNodeElementData: function(elm, key, value) {
			if (elm && typeof elm == "object") {
				if (!MyWidgetResourceLib.fn.isPlainObject(elm.data))
					elm.data = {};
				
				elm.data[key] = value;
			}
		},
		
		//Simulates the jquery function: $(elm).data(key);
		getNodeElementData: function(elm, key) {
			return this.existsNodeElementData(elm, key) ? elm.data[key] : null;
		},
		
		existsNodeElementData: function(elm, key) {
			return elm && typeof elm == "object" && MyWidgetResourceLib.fn.isPlainObject(elm.data) && elm.data.hasOwnProperty(key);
		},
		
		//Simulates the jquery function: $(elm).show();
		show: function(elm) {
			if (elm && elm.nodeType == Node.ELEMENT_NODE) {
				elm.style.display = null;
				
				var style = window.getComputedStyle(elm);
	    			
	    			if (style.display === "none") {
	    				var tag_name = elm.tagName;
	    				
	    				if (tag_name) {
		    				var temp = document.createElement(tag_name);
		    				elm.parentNode.appendChild(temp);
		    				var style = window.getComputedStyle(temp);
	    					var display = style.display;
		    				
						if (display === "none")
							display = "block";
						
		    				elm.style.display = display;
		    				
		    				elm.parentNode.removeChild(temp);
		    			}
	    			}
	    		}
		},
		
		//Simulates the jquery function: $(elm).hide();
		hide: function(elm) {
			if (elm && elm.nodeType == Node.ELEMENT_NODE) {
				elm.style.display = "none";
	    		}
		},
		
		//Fire an event handler to the specified node element.
		//Event handlers can detect that the event was fired programatically by testing for a 'synthetic=true' property on the event object
		trigger: function(elm, event_name) {
			//make sure we use the ownerDocument from the provided elm to avoid cross-window problems
			var doc = MyWidgetResourceLib.fn.getElementDocument(elm);
			
			if (!doc)
				throw new Error("Invalid node passed to fireEvent: " + elm.id);
			
			if (elm.dispatchEvent) {
				//gecko-style approach (now the standard) takes more work
				var event_class = "";

				//different events have different event classes. If this switch statement can't map an event_name to an event_class, the event firing is going to fail.
				switch (event_name) {
					case "click": //dispatching of 'click' appears to not work correctly in Safari. Use 'mousedown' or 'mouseup' instead.
					case "mousedown":
					case "mouseup":
						event_class = "MouseEvents";
						break;

					case "focus":
					case "change":
					case "blur":
					case "select":
						event_class = "HTMLEvents";
						break;

					default:
						throw "fireEvent: Couldn't find an event class for event '" + event_name + "'.";
						break;
				}
				
				var event = doc.createEvent(event_class);
				event.initEvent(event_name, true, true); //all events created as bubbling and cancelable.
				event.synthetic = true; //allow detection of synthetic events
				elm.dispatchEvent(event, true); //the second parameter says go ahead with the default action
			}
			else if (elm.fireEvent) {
				//IE-old school style, you can drop this if you don't need to support IE8 and lower
				var event = doc.createEventObject();
				event.synthetic = true; //allow detection of synthetic events
				elm.fireEvent("on" + event_name, event);
			}
		},
		
		/* GLOBAL-VAR FUNCTIONS */
		
		initServerPublicUserTypeId: function() {
			this.public_user_type_id = window.public_user_type_id;
		},
		
		initServerLoggedUserTypeIds: function() {
			var ids = window.logged_user_type_ids;
			
			if (typeof ids == "undefined")
				ids = null;
			else if (MyWidgetResourceLib.fn.isArray(ids)) {
				var aux = [];
				
				for (var i = 0, t = ids.length; i < t; i++)
					if (MyWidgetResourceLib.fn.isNumeric(ids[i]))
						aux.push(ids[i]);
				
				ids = aux;
			}
			else if (MyWidgetResourceLib.fn.isPlainObject(ids)) {
				var aux = [];
				
				for (var k in ids)
					if (MyWidgetResourceLib.fn.isNumeric(ids[k]))
						aux.push(ids[k]);
				
				ids = aux;
			}
			else if (MyWidgetResourceLib.fn.isNumeric(ids))
				ids = [ids];
			else
				ids = null;
			
			this.logged_user_type_ids = ids;
		},
		
		initServerRequestChartJSUrl: function() {
			var url = window.chart_js_url;
			
			MyWidgetResourceLib.ChartHandler.chart_js_url = typeof url == "string" && url ? url : null;
		},
		
		initServerRequestCalendarJSUrl: function() {
			var url = window.calendar_js_url;
			
			MyWidgetResourceLib.CalendarHandler.calendar_js_url = typeof url == "string" && url ? url : null;
		},
		
		initServerRequestResourceUrl: function() {
			var url = window.get_resource_url;
			
			MyWidgetResourceLib.ResourceHandler.get_resource_url = typeof url == "string" && url ? url : null;
		},
		
		//init loaded_sla_results with resources from in window.sla_results var, set previously by server.
		initServerLoadedSLAResults: function() {
			var sla_results = window.sla_results;
			
			if (!this.isPlainObject(MyWidgetResourceLib.ResourceHandler.loaded_sla_results))
				MyWidgetResourceLib.ResourceHandler.loaded_sla_results = {};
			
			if (this.isPlainObject(sla_results))
				for (var k in sla_results)
					if (!MyWidgetResourceLib.ResourceHandler.existLoadedSLAResult(k, "")) {
						var v = sla_results[k];
						
						//clone object
						if (this.isPlainObject(v))
							v = MyWidgetResourceLib.fn.assignObjectRecursively({}, v);
						else if (this.isArray(v))
							v = MyWidgetResourceLib.fn.assignObjectRecursively([], v);
						
						MyWidgetResourceLib.ResourceHandler.setLoadedSLAResult(k, "", v);
					}
		},
		
		/* WIDGET RESOURCE FUNCTIONS */
		
		//function to be called at the end of body when the html loads.
		initWidgets: function() {
			//init server vars
			this.initServerPublicUserTypeId();
			this.initServerLoggedUserTypeIds();
			this.initServerRequestChartJSUrl();
			this.initServerRequestCalendarJSUrl();
			this.initServerRequestResourceUrl();
			this.initServerLoadedSLAResults();
			
			//init widgets' permissions
			MyWidgetResourceLib.PermissionHandler.loadWidgetsPermissions();
			
			//init forms
			MyWidgetResourceLib.FormHandler.initForms();
			
			//init widgets' resurces
			MyWidgetResourceLib.ResourceHandler.loadWidgetsResources();
		},
		
		prepareDependentWidgetsId: function(dependent_widgets_id) {
			if (dependent_widgets_id) 
				if (typeof dependent_widgets_id == "string" || typeof dependent_widgets_id == "number")
					dependent_widgets_id = [dependent_widgets_id];
			
			if (MyWidgetResourceLib.fn.isPlainObject(dependent_widgets_id) && !MyWidgetResourceLib.fn.isEmptyobject(dependent_widgets_id))
				dependent_widgets_id = Object.values(dependent_widgets_id);
			
			return MyWidgetResourceLib.fn.isArray(dependent_widgets_id) ? dependent_widgets_id : [];
		},
		
		filterUniqueDependentWidgetsId: function(dependent_widgets_id) {
			if (MyWidgetResourceLib.fn.isArray(dependent_widgets_id))
				return dependent_widgets_id.filter(function(item, pos) {
					return dependent_widgets_id.indexOf(item) == pos;
				});
			
			return [];
		},
		
		loadDependentWidgetsById: function(dependent_widgets_id, props, opts) {
			dependent_widgets_id = this.prepareDependentWidgetsId(dependent_widgets_id);
			dependent_widgets_id = this.filterUniqueDependentWidgetsId(dependent_widgets_id);
			
			//TODO: find a way to loop asyncronously
			MyWidgetResourceLib.fn.each(dependent_widgets_id, function(idx, dependent_widget_id) {
				var widgets = MyWidgetResourceLib.fn.getDependentWidgetsById(dependent_widget_id);
				
				if (widgets)
					//TODO: find a way to loop asyncronously
					MyWidgetResourceLib.fn.each(widgets, function(idy, widget) {
						if (widget) {
							var status = true;
							
							if (opts && opts["ignore_widgets_with_resources_to_load"] && MyWidgetResourceLib.ResourceHandler.isWidgetWithResourcesToLoad(widget))
								status = false;
							
							if (status) {
								if (MyWidgetResourceLib.fn.isPlainObject(props))
									for (var key in props)
										MyWidgetResourceLib.fn.setNodeElementData(widget, key, props[key]);
								
								//clone opts, otherwise if the loadWidgetResource method changes the opts object, then it will take efect here too.
								var cloned_opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? MyWidgetResourceLib.fn.assignObjectRecursively({}, opts) : {}; 
								
								MyWidgetResourceLib.ResourceHandler.loadWidgetResource(widget, cloned_opts);
							}
						}
					});
			});
		},
		
		loadDependentWidgetsByIdWithoutResourcesToLoad: function(dependent_widgets_id, props, opts) {
			opts = opts ? opts : {};
			opts["ignore_widgets_with_resources_to_load"] = true;
							
			this.loadDependentWidgetsById(dependent_widgets_id, props, opts);
		},
		
		reloadWidgetDependentWidgets: function(widget, props, opts) {
			var properties = MyWidgetResourceLib.fn.getWidgetProperties(widget);
			var dependent_widgets_id = properties["dependent_widgets_id"];
			dependent_widgets_id = MyWidgetResourceLib.fn.prepareDependentWidgetsId(dependent_widgets_id);
			
			opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? opts : {};
			opts["force"] = true;
			
			MyWidgetResourceLib.fn.loadDependentWidgetsById(dependent_widgets_id, props, opts);
		},
		
		reloadWidgetDependentWidgetsWithoutResourcesToLoad: function(widget, props, opts) {
			var properties = MyWidgetResourceLib.fn.getWidgetProperties(widget);
			var dependent_widgets_id = properties["dependent_widgets_id"];
			dependent_widgets_id = MyWidgetResourceLib.fn.prepareDependentWidgetsId(dependent_widgets_id);
			
			opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? opts : {};
			opts["force"] = true;
			
			MyWidgetResourceLib.fn.loadDependentWidgetsByIdWithoutResourcesToLoad(dependent_widgets_id, props, opts);
		},
		
		//purge cache from the dependent widgets.
		purgeCachedLoadDependentWidgetsResource: function(elm) {
			var dependent_widgets_id = MyWidgetResourceLib.fn.prepareDependentWidgetsId(elm_properties["dependent_widgets_id"]);
			dependent_widgets_id = MyWidgetResourceLib.fn.filterUniqueDependentWidgetsId(dependent_widgets_id);
			
			MyWidgetResourceLib.fn.each(dependent_widgets_id, function(idx, dependent_widget_id) {
				var widgets = MyWidgetResourceLib.fn.getDependentWidgetsById(dependent_widget_id);
				
				if (widgets)
					MyWidgetResourceLib.fn.each(widgets, function(idy, widget) {
						if (widget)
							MyWidgetResourceLib.ResourceHandler.purgeWidgetResources(widget, "load");
					});
			});
		},
		
		getDependentWidgetsById: function(dependent_widget_id) {
			if (dependent_widget_id) {
				dependent_widget_id = ("" + dependent_widget_id).replace(/(^\s+|\s+$)/g, ""); //trim. Note that the dependent_widget_id maybe a composite selector, so do not replace white spaces between words.
				
				if (dependent_widget_id.length > 0) {
					var widgets = document.querySelectorAll("#" + dependent_widget_id);
					
					if (widgets) 
						return widgets;
				}
			}
			
			return null;
		},
		
		getWidgetProperties: function(elm) {
			var properties = elm ? elm.getAttribute("data-widget-props") : null;
			properties = properties && properties.substr(0, 1) == "{" ? MyWidgetResourceLib.fn.parseJson(properties) : {};
			properties = MyWidgetResourceLib.fn.isPlainObject(properties) ? properties : {};
			
			return properties;
		},
		
		/* FUNCTION FUNCTIONS */
		
		//if value is function, return it, otherwise if string check if string corresponds to a function
		convertIntoFunction: function(value) {
			//starts with a letter or underscore and doesn't have invalid chars
			if (typeof value == "string" && !value.match(/[^\w\.]/) && value.match(/^[a-zA-Z_]/)) { 
				try {
					eval('value = ' + value + ';');
				}
				catch(e) {}
			}
			
			if (typeof value == "function")
				return value;
			
			return null;
		},
		
		convertIntoFunctions: function(value) {
			//shortcut to be faster, bc most of the cases this method will be called where the value is an array with parsed functions.
			//This method will be called separately from the executeFunctions method, which means, when the executeFunctions method gets executed the value parameter was already parsed, and is already an array with functions.
			if (!value)
				return value;
			else if (this.isArray(value)) {
				var is_already_parsed = true;
				
				for (var i = 0, t = value.length; i < t; i++) 
					if (typeof value[i] != "function") {
						is_already_parsed = false;
						break;
					}
				
				if (is_already_parsed)
					return value;
			}
			
			//parse value if not yet parsed
			var arr = [];
			
			if (typeof value == "string")
				arr = value.replace(/;/g, ",").split(",");
			else if (typeof value == "function")
				arr.push(value);
			else if (this.isPlainObject(value) || this.isArray(value))
				this.each(value, function(idx, v) {
					v = MyWidgetResourceLib.fn.convertIntoFunctions(v);
					
					if (v)
						for (var i = 0, t = v.length; i < t; i++)
							if (v[i])
								arr.push(v[i]);
				});
			//else //ignore all other types that are not array, object or string
			
			//console.log(arr);
			
			var funcs = [];
			
			for (var i = 0, t = arr.length; i < t; i++) {
				var func = this.convertIntoFunction(arr[i]);
				//console.log(arr[i]);
				//console.log(func);
				
				if (func)
					funcs.push(func);
			}
			/*console.log(value);
			console.log(arr);
			console.log(funcs);*/
			
			return funcs.length > 0 ? funcs : null;
		},
		
		executeFunctions: function(value, arg_1, arg_2, arg_3, arg_4, arg_5, arg_6, arg_7, arg_8, arg_9, arg_10) {
			//convert value to function
			var funcs = this.convertIntoFunctions(value);
			
			//execute function
			var status = false;
			
			if (funcs) {
				status = true;
				
				for (var i = 0, t = funcs.length; i < t; i++) {
					var func = funcs[i];
					
					if (!func(arg_1, arg_2, arg_3, arg_4, arg_5, arg_6, arg_7, arg_8, arg_9, arg_10))
						status = false;
				}
			}
			
			return status;
		},
		
		executeFunctionsAndReturnResult: function(value, arg_1, arg_2, arg_3, arg_4, arg_5, arg_6, arg_7, arg_8, arg_9, arg_10) {
			//convert value to function
			var funcs = this.convertIntoFunctions(value);
			
			//execute function
			var res = null;
			
			if (funcs)
				for (var i = 0, t = funcs.length; i < t; i++) {
					var func = funcs[i];
					res = func(arg_1, arg_2, arg_3, arg_4, arg_5, arg_6, arg_7, arg_8, arg_9, arg_10);
				}
			
			return res;
		},
		
		/* UTIL FUNCTIONS */
		
		getElementDocument: function(elm) {
			var doc = null;
			
			if (elm) {
				var doc = elm.ownerDocument || elm.document;
				
				// the elm may be the document itself, nodeType 9 = DOCUMENT_NODE
				if (!doc && elm.nodeType == 9)
					doc = elm;
			}
			
			return doc;
		},
		
		getElementWindow: function(elm) {
			var doc = this.getElementDocument(elm);
			return doc ? doc.defaultView || doc.parentWindow : null;
		},
		
		//get the path selector for an element
		getElementPathSelector: function(elm, until_parent) {
			var stack = [];
			
			while (elm.parentNode != null && elm != until_parent) {
				//console.log(elm.nodeName);
				var nodes_count = 0;
				var node_index = 0;
				
				for (var i = 0; i < elm.parentNode.childNodes.length; i++) {
					var node = elm.parentNode.childNodes[i];
					
					if (node.nodeName == elm.nodeName) {
						if (node === elm)
							node_index = nodes_count;
						
						nodes_count++;
					}
				}
				
				var path = elm.nodeName.toLowerCase();
				
				if (elm.hasAttribute('id') && elm.id != '')
					path += '#' + elm.id;
				else if (nodes_count > 1) //if nodes_count is 1 it means the selector can be only the nodeName bc is the only-child
					path += ':nth-child(' + (node_index + 1) + ')';
				
				stack.unshift(path);
				
				elm = elm.parentNode;
			}
			
			//removes the HTML node, which is the last parent node, but cannot be in the selector
			if (!until_parent || until_parent.parentNode == null)
				stack = stack.slice(1); 
			
			var selector = stack.join(' > ');
			//console.log(selector);
			
			return selector;
		},
		
		isVisible: function(elm) {
			if (elm && elm.nodeType == Node.ELEMENT_NODE) {
				var style = window.getComputedStyle(elm);
	    			
	    			return style.display != "none" && style.visibility != "hidden";
	    		}
	    		
	    		return false;
		},
		
		parseJson: function(str, do_not_catch) {
			if (do_not_catch)
				return JSON.parse(str);
			
			var obj = null;
			
			try {
				obj = JSON.parse(str);
			}
			catch(e) {
				if (console && console.log) {
					console.log(e);
					console.log(str);
				}
			}
			
			return obj;
		},
		
		//Takes a td/th or any element within and returns the row and column index of the cell taking into account rowspans and colspans
		getTableCellIndexes: function(elm) {
			var tr_index = 0;
			var col_index = 0;
			var td = elm.closest("td, th");
			
			if (td) {
				var prev = elm.previousElementSibling;
				
				while (prev) {
					if (prev.nodeName == "TD" || prev.nodeName == "TH")
						col_index += prev.colSpan ? prev.colSpan : 1;
					
					prev = prev.previousElementSibling;
				};
				
				var tr = td.parentNode;//td.closest("tr");
				var tbody = tr.parentNode;//tr.closest('thead, tbody, tfoot, table');
				var rowspans = tbody.querySelectorAll("td[rowspan], th[rowspan]");
				tr_index = Array.prototype.indexOf.call(tr.parentNode.children, tr);
				
				MyWidgetResourceLib.fn.each(rowspans, function (idx, rs_col) {
					var rs_tr = rs_col.closest("tr");
					
					if (rs_tr.parentNode == tbody) { //ignore inner tables
						var rs_tr_index = Array.prototype.indexOf.call(rs_tr.parentNode.children, rs_tr);
						var rs_quantity = rs_col.rowSpan ? rs_col.rowSpan : 0;
						
						if (tr_index > rs_tr_index && tr_index <= rs_tr_index + rs_quantity - 1) {
							var rs_col = 0;
							var rs_prev = rs_col.previousElementSibling;
							
							while (rs_prev) {
								if (rs_prev.nodeName == "TD" || rs_prev.nodeName == "TH")
									rs_col += rs_prev.colSpan ? rs_prev.colSpan : 1;
								
								rs_prev = rs_prev.previousElementSibling;
							};
							
							if (rs_col <= col_index) 
								col_index += rs_col.colSpan ? rs_col.colSpan : 1;
						}
					}
				});
			}
			
			return {
				row: tr_index,
				col: col_index
			};
		},
		
		//col_index comes from the getTableCellIndexes method
		getTableRowChildrenIndexByTableCellIndex: function(elm, col_index) {
			var tr = elm.closest("tr");
			
			if (tr) {
				//check previous rowspan and then decrease them from col_index
				var tbody = tr.parentNode;//tr.closest('thead, tbody, tfoot, table');
				var rowspans = tbody.querySelectorAll("td[rowspan], th[rowspan]");
				var tr_index = Array.prototype.indexOf.call(tr.parentNode.children, tr);
				
				MyWidgetResourceLib.fn.each(rowspans, function (idx, rs_col) {
					var rs_tr = rs_col.closest("tr");
					
					if (rs_tr.parentNode == tbody) { //ignore inner tables
						var rs_tr_index = Array.prototype.indexOf.call(rs_tr.parentNode.children, rs_tr);
						var rs_quantity = rs_col.rowSpan ? rs_col.rowSpan : 0;
						
						if (tr_index > rs_tr_index && tr_index <= rs_tr_index + rs_quantity - 1) {
							var rs_col = 0;
							var rs_prev = rs_col.previousElementSibling;
							
							while (rs_prev) {
								if (rs_prev.nodeName == "TD" || rs_prev.nodeName == "TH")
									rs_col += rs_prev.colSpan ? rs_prev.colSpan : 1;
								
								rs_prev = rs_prev.previousElementSibling;
							};
							
							if (rs_col <= col_index) 
								col_index -= rs_col.colSpan ? rs_col.colSpan : 1;
						}
					}
				});
				
				//find col_index in tr based in previous colspan attributes
				var prev_colspans = 0;
				
				if (col_index >= 0) //if col_index is negative return null
					for (var i = 0, t = tr.children.length; i < t; i++) {
						var td = tr.children[i];
						
						if (i + prev_colspans == col_index)
							return i;
						
						if (td.colSpan)
							prev_colspans += td.colSpan - 1;
						
						if (i + prev_colspans > col_index)
							break;
					}
			}
			
			return null;
		}
	};
	
	MyWidgetResourceLib.fn.init.prototype = MyWidgetResourceLib.fn;
	/****************************************************************************************
	 *				 END: CORE FUNCTIONS 					*
	 ****************************************************************************************/

	/****************************************************************************************
	 *				 START: AJAX HANDLER					*
	 ****************************************************************************************/
	MyWidgetResourceLib.AjaxHandler = MyWidgetResourceLib.fn.AjaxHandler = ({
		getAjaxRequest: function(options) {
			if (typeof jQuery == "object" || typeof jQuery == "function") {
				if (options && options.url) {
					var data_filter_func = options.dataFilter ? MyWidgetResourceLib.fn.convertIntoFunctions(options.dataFilter) : null; //( String data, String type ) 
					var before_send_func = options.beforeSend ? MyWidgetResourceLib.fn.convertIntoFunctions(options.beforeSend) : null; //( jqXHR jqXHR, PlainObject settings )
					var complete_func = options.complete ? MyWidgetResourceLib.fn.convertIntoFunctions(options.complete) : null; //( jqXHR jqXHR, String textStatus )
					var success_func = options.success ? MyWidgetResourceLib.fn.convertIntoFunctions(options.success) : null; //( Anything data, String textStatus, jqXHR jqXHR )
					var error_func = options.error ? MyWidgetResourceLib.fn.convertIntoFunctions(options.error) : null; //( jqXHR jqXHR, String textStatus, String errorThrown )
					
					if (options.dataType == "javascript" || options.dataType == "javascript_in_html") {
						var handler = options.dataType == "javascript" ? MyWidgetResourceLib.AjaxHandler.executeResponseJavascriptCode : MyWidgetResourceLib.AjaxHandler.executeResponseJavascriptInHtml;
						
						//change data type to text
						options.dataType = "text";
						
						//Set success func
						if (success_func)
							success_func.unshift(handler); //prepend handler to success_func
						else 
							success_func = handler;
					}
					
					if (data_filter_func)
						options.dataFilter = function(data, type) {
							return MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(data_filter_func, data, type);
						};
					
					if (before_send_func)
						options.beforeSend = function(jqXHR, settings) {
							return MyWidgetResourceLib.fn.executeFunctions(before_send_func, jqXHR, settings);
						};
					
					if (complete_func)
						options.complete = function(jqXHR, textStatus) {
							MyWidgetResourceLib.fn.executeFunctions(complete_func, jqXHR, textStatus);
						};
					
					if (success_func)
						options.success = function(data, textStatus, jqXHR) {
							MyWidgetResourceLib.fn.executeFunctions(success_func, data, textStatus, jqXHR);
						};
					
					if (error_func)
						options.error = function(jqXHR, textStatus, errorThrown) {
							MyWidgetResourceLib.fn.executeFunctions(error_func, jqXHR, textStatus, errorThrown);
						};
					
					//remove timeout if invalid value, otherwise jquery doesn't execute request.
					if (!MyWidgetResourceLib.fn.isNumeric(options.timeout) || options.timeout < 0)
						delete options.timeout;
					
					return $.ajax(options);
				}
				
				return false;
			}
			else
				return this.getNativeAjaxRequest(options);
			
			return false;
		},
		
		//same options than in jQuery
		getNativeAjaxRequest: function(options) {
			if(!options) 
				options = {};
			
			var url = options.url ? options.url : null;
			
			if (!url)
				return false;
			
			var method = options.method ? ("" + options.method).toUpperCase() : "GET";
			method = options.type ? ("" + options.type).toUpperCase() : method;
			var data = method == "POST" && options.data ? options.data : null;
			var process_data = options.hasOwnProperty("processData") ? options.processData : true;
			var data_type = options.dataType ? ("" + options.dataType).toLowerCase() : "text";
			var mime_type = options.mimeType ? ("" + options.mimeType).toLowerCase() : "text/plain";
			var content_type = options.hasOwnProperty("contentType") ? (
				options.contentType ? ("" + options.contentType).toLowerCase() : options.contentType
			) : "application/x-www-form-urlencoded; charset=UTF-8";
			var is_async = options.hasOwnProperty("async") ? options["async"] : true;
			var timeout = options.hasOwnProperty("timeout") ? options.timeout : 0; //in milliseconds
			var username = options.hasOwnProperty("username") ? options.username : null;
			var password = options.hasOwnProperty("password") ? options.password : null;
    		var referer_policy = options.hasOwnProperty("referrerPolicy") ? options["referrerPolicy"] : null;
			
			var data_filter_func = options.dataFilter ? MyWidgetResourceLib.fn.convertIntoFunctions(options.dataFilter) : null; //( String data, String type ) 
			var before_send_func = options.beforeSend ? MyWidgetResourceLib.fn.convertIntoFunctions(options.beforeSend) : null; //( jqXHR jqXHR, PlainObject settings )
			var complete_func = options.complete ? MyWidgetResourceLib.fn.convertIntoFunctions(options.complete) : null; //( jqXHR jqXHR, String textStatus )
			var success_func = options.success ? MyWidgetResourceLib.fn.convertIntoFunctions(options.success) : null; //( Anything data, String textStatus, jqXHR jqXHR )
			var error_func = options.error ? MyWidgetResourceLib.fn.convertIntoFunctions(options.error) : null; //( jqXHR jqXHR, String textStatus, String errorThrown )
			
			if (data_type == "javascript" || data_type == "javascript_in_html") {
				var handler = data_type == "javascript" ? MyWidgetResourceLib.AjaxHandler.executeResponseJavascriptCode : MyWidgetResourceLib.AjaxHandler.executeResponseJavascriptInHtml;
				
				//change data type to text
				data_type = "text";
				
				//Set success func
				if (success_func)
					success_func.unshift(handler); //prepend handler to success_func
				else 
					success_func = handler;
			}
			
			var XMLRequestObject = null; //XMLHttpRequest Object
			
			if (window.XMLHttpRequest) { //Mozilla, Safari, ...
				XMLRequestObject = new XMLHttpRequest();
				
				if (typeof XMLRequestObject.overrideMimeType == "function")
					XMLRequestObject.overrideMimeType("text/xml");
			} 
			else if (window.ActiveXObject) { //IE
				try {
					XMLRequestObject = new ActiveXObject("Msxml2.XMLHTTP");
				} 
				catch (e) {
					try {
						XMLRequestObject = new ActiveXObject("Microsoft.XMLHTTP");
					} 
					catch (e) {}
				}
			}
			
			if (!XMLRequestObject) {
				//MyWidgetResourceLib.MessageHandler.showErrorMessage("Giving up :( Cannot create an XMLHTTP instance");
				return false;
			}
			
			//fix Firefox error reporting when it tries to parse the response as XML, bc if the server doesn't return the correct response Content-type, by default the XMLRequestObject will try to parse the response as XML.
			if (typeof XMLRequestObject.overrideMimeType == "function")
				XMLRequestObject.overrideMimeType(mime_type);
			
			//timeout
			if (is_async && parseInt(timeout) > 0) //Timeout shouldn't be used for synchronous XMLHttpRequests requests used in a document environment or it will throw an InvalidAccessError exception.
				XMLRequestObject.timeout = parseInt(timeout);
			
			//open(method, url, async, user, psw)
			//	method: the request type GET or POST
			//	url: the file location
			//	async: true (asynchronous) or false (synchronous)
			//	user: optional user name
			//	psw: optional password
			XMLRequestObject.open(method, url, is_async, username, password);
			
			if (method == "POST") {
				if (content_type)
					XMLRequestObject.setRequestHeader("Content-type", content_type);
				
				if (data && process_data) {
					data = this.convertDataToQueryString(data);
					
					//XMLRequestObject.setRequestHeader("Content-length", data.length); //browser error saying: Refused to set unsafe header "Content-length"
				}
				
				//XMLRequestObject.setRequestHeader("Connection", "close"); //browser error saying: Refused to set unsafe header "Connection"
			}
			
			// Set the referrer policy
			if (referer_policy)
				XMLRequestObject.referrerPolicy = referer_policy;
			
    		XMLRequestObject.onreadystatechange = function() {
				if (XMLRequestObject.readyState == 4) { //readyState == 4: when request is done
					var status = XMLRequestObject.status; //http response code
					var text_status = "";
					
					if (status >= 200 && status < 300 || status === 304) {
						text_status = "success";
						
						if (status === 304)
							text_status = "notmodified";
						
						if (success_func) {
							var res = null;
							var execute_success_func = true;
							
							if (data_filter_func)
								res = MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(data_filter_func, XMLRequestObject.responseText, data_type);
							else if (data_type == "xml")
								res = XMLRequestObject.responseXML;
							else if (data_type == "json" && typeof JSON == "object") {
								try {
									res = MyWidgetResourceLib.fn.parseJson(XMLRequestObject.responseText, true);
								}
								catch(e) {
									if (console && console.log)
										console.log(e);
									
									text_status = "parsererror";
									execute_success_func = false;
									
									if (error_func) {
										var error_thrown = e && e.message ? e.message : e;
										
										MyWidgetResourceLib.fn.executeFunctions(error_func, XMLRequestObject, text_status, error_thrown);
									}
								}
							}
							else
								res = XMLRequestObject.responseText;
							
							if (execute_success_func)
								MyWidgetResourceLib.fn.executeFunctions(success_func, res, text_status, XMLRequestObject);
						}
					}
					else {
						text_status = "error";
						
						if (error_func) {
							var error_thrown = XMLRequestObject.statusText; 
							
							MyWidgetResourceLib.fn.executeFunctions(error_func, XMLRequestObject, text_status, error_thrown);
						}
					}
					
					if (complete_func)
						MyWidgetResourceLib.fn.executeFunctions(complete_func, XMLRequestObject, text_status);
				}
			};
			
			XMLRequestObject.ontimeout = function() {
				var text_status = "timeout";
				
				if (error_func) {
					text_status = "timeout";
					var error_thrown = XMLRequestObject.statusText; 
					
					MyWidgetResourceLib.fn.executeFunctions(error_func, XMLRequestObject, text_status, error_thrown);
				}
				
				if (complete_func)
					MyWidgetResourceLib.fn.executeFunctions(complete_func, XMLRequestObject, text_status);
			};
			
			XMLRequestObject.onerror = function() {
				var text_status = "error";
				
				if (error_func) {
					var error_thrown = XMLRequestObject.statusText; 
					
					MyWidgetResourceLib.fn.executeFunctions(error_func, XMLRequestObject, text_status, error_thrown);
				}
				
				if (complete_func)
					MyWidgetResourceLib.fn.executeFunctions(complete_func, XMLRequestObject, text_status);
			};
			
			XMLRequestObject.onabort = function() {
				var text_status = "abort";
				
				if (error_func) {
					var error_thrown = XMLRequestObject.statusText; 
					
					MyWidgetResourceLib.fn.executeFunctions(error_func, XMLRequestObject, text_status, error_thrown);
				}
				
				if (complete_func)
					MyWidgetResourceLib.fn.executeFunctions(complete_func, XMLRequestObject, text_status);
			};
			
			var status = true;
			
			if (before_send_func) {
				status = MyWidgetResourceLib.fn.executeFunctions(before_send_func, XMLRequestObject, options);
				
				if (status && options && typeof options["data"] == "string")
					data = options["data"];
			}
			
			if (status)
				XMLRequestObject.send(data);
			
			return status;
		},

		convertDataToQueryString: function(data, prefix) {
			var query_string = "";
			
			if (MyWidgetResourceLib.fn.isPlainObject(data) || MyWidgetResourceLib.fn.isArray(data)) {
				if (MyWidgetResourceLib.fn.isArray(data)) {
					var obj = {};
					
					for (var i = 0, t = data.length; i < t; i++)
						obj[i] = data[i];
					
					data = obj;
				}
				
				for (var name in data) {
					var value = data[name];
					
					if (prefix)
						name = prefix + "[" + name + "]";
					
					query_string += (query_string ? "&" : "") + this.convertDataToQueryString(value, name);
				}
			}
			else
				query_string = encodeURIComponent(prefix) + '=' + (data === null || data === undefined ? "" : encodeURIComponent(data)); //note that the encodeURIComponent will convert the null or undefined values to string "null" and "undefined"
			
			return query_string;
		},
		
		getCurrentUrlQueryString: function() {
			var query_string = window.location.search;
			query_string = query_string && query_string.substr(0, 1) == "?" ? query_string.substr(1) : query_string;
			
			return query_string;
		},
		
		executeResponseJavascriptCode: function(code, text_status, XMLRequestObject) {
			//console.log(code);
			
			//Do not try and catch bc the idea to give an error if the javascript code is wrong.
			//try {
				if (code && typeof code == "string")
					eval(code);
			/*}
			catch(e) {
				if (console && console.log)
					console.log(e);
			}*/
		},
		
		executeResponseJavascriptInHtml: function(html, text_status, XMLRequestObject) {
			var code = MyWidgetResourceLib.AjaxHandler.getResponseJavascriptCodeFromHtml(html);
			MyWidgetResourceLib.AjaxHandler.executeResponseJavascriptCode(code, text_status, XMLRequestObject);
		},
		
		getResponseJavascriptCodeFromHtml: function(html) {
			var code = "";
			
			//parse html and combine all the javascript code inside of the script tags
			if (html && typeof html == "string") {
				var regex = RegExp('<script(\s|>)', 'gi');
				var m = null;
				var lower_html = html.toLowerCase();
				
				while ((m = regex.exec(html)) !== null) {
					//console.log(m);
					var str = m[0];
					var start = m.index;
					var end = m[1] == ">" ? start + str.length - 1 : html.indexOf(">", start + str.length);
					
					if (end == -1)
						break;
					
					start = end + 1; //set new start to get only the content
					end = lower_html.indexOf("</script", start);
					end = end == -1 ? html.length : end;
					
					var code_piece = html.substr(start, end - start);
					
					if (code_piece)
						code += code_piece;
				}
			}
			
			return code;
		}
	});
	/****************************************************************************************
	 *				 END: AJAX HANDLER 					*
	 ****************************************************************************************/
		
	/****************************************************************************************
	 *				 START: RESOURCE HANDLER					*
	 ****************************************************************************************/
	MyWidgetResourceLib.ResourceHandler = MyWidgetResourceLib.fn.ResourceHandler = ({
		
		loaded_sla_results: {},
		cached_loaded_sla_results: {},
		requesting_urls: {},
		get_resource_url: null,
		
		loadWidgetsResources: function() {
			//:not(.template-widget) is very important so it doesn't load the resource when we are editing this widget through the LayoutUIEditor.
			var elms = document.querySelectorAll("[data-widget-resources-load]:not(.template-widget)");
			
			if (elms) {
				var t = elms.length;
				
				//first remove the data-widget-resources-load attribute
				for (var i = 0; i < t; i++) {
					var elm = elms[i];
					elm.removeAttribute("data-widget-resources-load");
					elm.setAttribute("data-widget-resources-loaded", "");
				}
				
				//then load the widgets
				//TODO: find a way to loop asyncronously
				MyWidgetResourceLib.fn.each(elms, function(idx, elm) {
					if (!MyWidgetResourceLib.ResourceHandler.loadWidgetResource(elm)) {
						elm.setAttribute("data-widget-resources-load", "");
						elm.removeAttribute("data-widget-resources-loaded");
					}
				});
			}
		},
		
		loadWidgetResource: function(widget, opts) {
			if (widget) {
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(widget);
				var load_func = MyWidgetResourceLib.fn.convertIntoFunctions(properties["load"]);
				//console.log(properties);
				
				if (load_func)
					return MyWidgetResourceLib.fn.executeFunctions(load_func, widget, opts);
			}
			
			return false;
		},
		
		isWidgetWithResourcesToLoad: function(widget) {
			return widget.hasAttribute("data-widget-resources-load") || widget.hasAttribute("data-widget-resources-loaded");
		},
		
		setWidgetResourcesLoadedAttribute: function(widget) {
			if (widget) 
				widget.setAttribute("data-widget-resources-loaded", "");
		},
		
		//Set the attribute data-widget-resources-loaded to the dependent widgets.
		setDependentWidgetsResourcesLoadedAttribute: function(elm) {
			if (elm) {
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var dependent_widgets_id = properties["dependent_widgets_id"];
				
				if (dependent_widgets_id) {
					dependent_widgets_id = MyWidgetResourceLib.fn.prepareDependentWidgetsId(dependent_widgets_id);
					
					MyWidgetResourceLib.fn.each(dependent_widgets_id, function(idx, dependent_widget_id) {
						var widgets = MyWidgetResourceLib.fn.getDependentWidgetsById(dependent_widget_id);
						
						if (widgets)
							MyWidgetResourceLib.fn.each(widgets, function(idy, widget) {
								if (widget)
									MyWidgetResourceLib.ResourceHandler.setWidgetResourcesLoadedAttribute(widget);
							});
					});
				}
			}
		},
		
		//check if the widget has a load resource with conditions equal to "null" and if so, remove this resource
		removeWidgetLoadResourcesWithConditionsNull: function(widget) {
			if (widget) {
				var resources = MyWidgetResourceLib.ResourceHandler.getWidgetResources(widget, "load");
				
				if (resources.length > 0) {
					for (var i = 0; i < resources.length; i++) {
						var resource = resources[i];
						var resource_conditions = resource["conditions"];
						
						if (resource_conditions) {
							if (MyWidgetResourceLib.fn.isPlainObject(resource_conditions))
								resource_conditions = [resource_conditions];
							
							if (MyWidgetResourceLib.fn.isArray(resource_conditions)) {
								for (var j = 0; j < resource_conditions.length; j++) {
									var resource_condition = resource_conditions[j];
									
									if (MyWidgetResourceLib.fn.isPlainObject(resource_condition) && resource_condition.hasOwnProperty("value") && typeof resource_condition["value"] == "string" && resource_condition["value"] == "null") {
										resource_conditions.splice(j, 1);
										j--;
									}
								}
								
								if (resource_conditions.length == 0) {
									resources.splice(i, 1);
									i--;
								}
							}
						}
					}
					
					if (resources.length == 0)
						widget.removeAttribute("data-widget-resources");
					else {
						var widget_resources = widget.getAttribute("data-widget-resources");
						var fc = ("" + widget_resources).substr(0, 1);
						widget_resources = fc == "{" ? MyWidgetResourceLib.fn.parseJson(widget_resources) : null;
						
						if (MyWidgetResourceLib.fn.isPlainObject(widget_resources) && widget_resources.hasOwnProperty("load"))
							widget_resources["load"] = resources;
						else
							widget_resources = {load: resources};
						
						widget.setAttribute("data-widget-resources", JSON.stringify(widget_resources));
					}
				}
			}
		},
		
		removeDependentWidgetsLoadResourcesWithConditionsNull: function(elm) {
			if (elm) {
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var dependent_widgets_id = properties["dependent_widgets_id"];
				
				if (dependent_widgets_id) {
					dependent_widgets_id = MyWidgetResourceLib.fn.prepareDependentWidgetsId(dependent_widgets_id);
					
					MyWidgetResourceLib.fn.each(dependent_widgets_id, function(idx, dependent_widget_id) {
						var widgets = MyWidgetResourceLib.fn.getDependentWidgetsById(dependent_widget_id);
						
						if (widgets)
							MyWidgetResourceLib.fn.each(widgets, function(idy, widget) {
								if (widget)
									MyWidgetResourceLib.ResourceHandler.removeWidgetLoadResourcesWithConditionsNull(widget);
							});
					});
				}
			}
		},
		
		//Set the attribute data-widget-resources-loaded to the parent widget with a load resource.
		setParentWidgetResourcesLoadedAttribute: function(elm) {
			if (elm) {
				var widget = elm.parentNode.closest("[data-widget-resources]");
				var resources = null;
				
				while (widget && !resources) {
					resources = MyWidgetResourceLib.ResourceHandler.getWidgetResources(widget, "load");
					
					if (!resources)
						widget = widget.parentNode.closest("[data-widget-resources]");
				};
				
				if (widget)
					MyWidgetResourceLib.ResourceHandler.setWidgetResourcesLoadedAttribute(widget);
			}
		},
		
		getResourceUrl: function(resource_name, resource_type, resource_value, resource_conditions) {
			var url = null;
			
			if (resource_type == "url")
				url = resource_value ? resource_value : null;
			else
				url = this.get_resource_url;
			
			if (url) {
				url += (url.indexOf("?") != -1 ? "&" : "?") + MyWidgetResourceLib.AjaxHandler.getCurrentUrlQueryString() + "&resource=" + resource_name;
				
				//prepare resource conditions
				if (resource_conditions) {
					if (MyWidgetResourceLib.fn.isPlainObject(resource_conditions))
						resource_conditions = [resource_conditions];
					
					if (MyWidgetResourceLib.fn.isArray(resource_conditions)) {
						var search_attrs = {};
						var search_types = {};
						var search_cases = {};
						var search_operators = {};
						
						MyWidgetResourceLib.fn.each(resource_conditions, function(idx, resource_condition) {
							if (MyWidgetResourceLib.fn.isPlainObject(resource_condition) && resource_condition.hasOwnProperty("name")) {
								var attr_name = resource_condition["name"];
								attr_name = attr_name || MyWidgetResourceLib.fn.isNumeric(attr_name) ? ("" + attr_name).replace(/\s+/g, "") : null;
								
								if (attr_name) {
									if (resource_condition.hasOwnProperty("value"))
										search_attrs[attr_name] = resource_condition["value"];
									
									if (resource_condition.hasOwnProperty("type"))
										search_types[attr_name] = resource_condition["type"];
									
									if (resource_condition.hasOwnProperty("case"))
										search_cases[attr_name] = resource_condition["case"];
									
									if (resource_condition.hasOwnProperty("operator"))
										search_operators[attr_name] = resource_condition["operator"];
								}
							}
						});
						
						if (MyWidgetResourceLib.fn.isPlainObject(search_attrs) && !MyWidgetResourceLib.fn.isEmptyObject(search_attrs))
							url += "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_attrs, "search_attrs") 
								+ (search_types ? "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_types, "search_types") : "") 
								+ (search_cases ? "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_cases, "search_cases") : "") 
								+ (search_operators ? "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_operators, "search_operators") : "");
					}
				}
			}
			
			return url;
		},
		
		//Note that the resource_name can ne a string like: "[x][v]" or "x[u]", so we need to parse the resource_name and convert it to an array, so then we can use it in the getLoadedSLAResult, setLoadedSLAResult and existLoadedSLAResult methods.
		convertResourceNameToArray: function(resource_name) {
			if (MyWidgetResourceLib.fn.isArray(resource_name)) {
				var new_resource_name = [];
				
				for (var i = 0, t = resource_name.length; i < t; i++) {
					var k = resource_name[i];
					var v = this.convertResourceNameToArray(k);
					
					if (v)
						new_resource_name = new_resource_name.concat(v);
				}
				
				return new_resource_name;
			}
			else if (typeof resource_name == "string" && (resource_name.indexOf("[") != -1 || resource_name.indexOf("]") != -1)) {
				var regex = /([^\[\]]+)/g;
				var m;
				var new_resource_name = [];
				
				while ((m = regex.exec(resource_name)) !== null) {
					var k = m[0].replace(/(^\s+|\s+$)/g, "");
					
					if (k.length > 0) {
						var fc = k.substr(0, 1);
						var lc = k.substr(k.length - 1);
						
						if ((fc == '"' && lc == '"') || (fc == "'" && lc == "'"))
							k = k.substr(1, k.length - 2);
						
						if (k.length > 0)
							new_resource_name.push(k);
					}
				}
				
				return new_resource_name;
			}
			else if (resource_name || MyWidgetResourceLib.fn.isNumeric(resource_name))
				return [resource_name];
			
			return null;
		},
		
		prepareResourceCacheKey: function(resource_cache_key) {
			return typeof resource_cache_key == "string" ? decodeURI(resource_cache_key).replace(/&*/, "&").replace(/^(&|\?)+/, "").replace(/&+$/, "") : resource_cache_key;
		},
		
		getResourceCacheKey: function(resource_name, resource_type, resource_value, resource_default_cache_key, resource_url) {
			resource_name = this.convertResourceNameToArray(resource_name);
			var resource_cache_key = "";
 			
 			if (typeof resource_url == "string") {
				var url = this.getResourceUrl(resource_name, resource_type, resource_value);
				var pos = url !== null ? resource_url.indexOf(url) : -1;
 				
 				if (pos === 0)
					resource_cache_key = resource_url.substr(url.length);
				else
					resource_cache_key = resource_url;
				
				resource_cache_key = this.prepareResourceCacheKey(resource_cache_key);
				resource_default_cache_key = this.prepareResourceCacheKey(resource_default_cache_key);
				
				if (resource_cache_key == resource_default_cache_key)
					resource_cache_key = "";
			}
				
			return resource_cache_key;
		},
		
		//set resource_data to vars: this.loaded_sla_results and this.cached_loaded_sla_results
		//if resource_cache_key is empty, set it to empty string
		setLoadedSLAResult: function(resource_name, resource_cache_key, resource_data) {
			resource_name = this.convertResourceNameToArray(resource_name);
			
			if (resource_name) {
				resource_cache_key = typeof resource_cache_key == "string" || MyWidgetResourceLib.fn.isNumeric(resource_cache_key) ? resource_cache_key : "";
				resource_cache_key = this.prepareResourceCacheKey(resource_cache_key);
				
				var loaded_resource = this.loaded_sla_results;
				var cached_loaded_resource = this.cached_loaded_sla_results;
				var loaded_resource_saved = false;
				var cached_loaded_resource_saved = false;
				
				for (var i = 0, t = resource_name.length; i < t; i++) {
					var k = resource_name[i];
					var is_loaded_resource_ok = MyWidgetResourceLib.fn.isPlainObject(loaded_resource) || MyWidgetResourceLib.fn.isArray(loaded_resource);
					var is_cached_loaded_resource_ok = MyWidgetResourceLib.fn.isPlainObject(cached_loaded_resource) || MyWidgetResourceLib.fn.isArray(cached_loaded_resource);
					
					if (is_loaded_resource_ok) {
						if (i + 1 == t) {
							loaded_resource[k] = resource_data; //overwrite the previous value if exists, bc the loaded_resource only contains the latest value. All resources_data are in the this.cached_loaded_sla_results var.
							loaded_resource_saved = true;
						}
						else {
							if (!MyWidgetResourceLib.fn.isPlainObject(loaded_resource[k]) && !MyWidgetResourceLib.fn.isArray(loaded_resource[k]))
								loaded_resource[k] = {};
							
							loaded_resource = loaded_resource[k];
						}
					}
					
					if (is_cached_loaded_resource_ok) {
						if (i + 1 == t) {
							if (!MyWidgetResourceLib.fn.isPlainObject(cached_loaded_resource[k]))
								cached_loaded_resource[k] = {};
							
							cached_loaded_resource[k][resource_cache_key] = resource_data; //set cached resource_data based in cache_key
							cached_loaded_resource_saved = true;
						}
						else {
							if (!MyWidgetResourceLib.fn.isPlainObject(cached_loaded_resource[k]) && !MyWidgetResourceLib.fn.isArray(cached_loaded_resource[k]))
								cached_loaded_resource[k] = {};
							
							cached_loaded_resource = cached_loaded_resource[k];
						}
					}
					
					if (!is_loaded_resource_ok && !is_cached_loaded_resource_ok)
						break;
				}
				
				return loaded_resource_saved && cached_loaded_resource_saved;
			}
			
			return false;
		},
		
		//if resource_cache_key is passed, then get the resource_name from this.cached_loaded_sla_results.
		//else if resource_cache_key is NOT passed, then get the resource_name from this.loaded_sla_results.
		getLoadedSLAResult: function(resource_name, resource_cache_key) {
			resource_name = this.convertResourceNameToArray(resource_name);
			
			if (resource_name) {
				var resource_cache_key_exists = typeof resource_cache_key == "string" || MyWidgetResourceLib.fn.isNumeric(resource_cache_key);
				resource_cache_key = resource_cache_key_exists ? resource_cache_key : "";
				resource_cache_key = this.prepareResourceCacheKey(resource_cache_key);
				
				var resource_cache_key_used = false;
				var loaded_resource = this.loaded_sla_results;
				var cached_loaded_resource = this.cached_loaded_sla_results;
				//console.log("get "+resource_name+" with cache key("+resource_cache_key_exists+"):"+resource_cache_key+"!");
				//console.log(resource_name);
				//console.log("resource_cache_key:"+resource_cache_key+"|typeof:"+(typeof resource_cache_key));
				
				for (var i = 0, t = resource_name.length; i < t; i++) {
					var k = resource_name[i];
					var is_loaded_resource_ok = MyWidgetResourceLib.fn.isPlainObject(loaded_resource) || MyWidgetResourceLib.fn.isArray(loaded_resource);
					var is_cached_loaded_resource_ok = MyWidgetResourceLib.fn.isPlainObject(cached_loaded_resource) || MyWidgetResourceLib.fn.isArray(cached_loaded_resource);
					
					if (!resource_cache_key_exists && is_loaded_resource_ok) {
						loaded_resource = loaded_resource[k];
						
						if (i + 1 == t)
							return loaded_resource; //get latest resource_data, bc the loaded_resource only contains the latest value. All resources_data are in the this.cached_loaded_sla_results var.
					}
					else if (resource_cache_key_exists && is_cached_loaded_resource_ok) {
						cached_loaded_resource = cached_loaded_resource[k];
						
						if (!resource_cache_key_used && MyWidgetResourceLib.fn.isPlainObject(cached_loaded_resource) && cached_loaded_resource.hasOwnProperty(resource_cache_key)) {
							cached_loaded_resource = cached_loaded_resource[resource_cache_key];
							resource_cache_key_used = true;
						}
						
						if (i + 1 == t) {
							if (!resource_cache_key_used)
								cached_loaded_resource = MyWidgetResourceLib.fn.isPlainObject(cached_loaded_resource) && cached_loaded_resource.hasOwnProperty(resource_cache_key) ? cached_loaded_resource[resource_cache_key] : null; //get the resource_data correspondent to the cache_key, if not yet done.
							
							//console.log("ENTERED in resource_cache_key_exists OK: "+cached_loaded_resource[resource_cache_key]);
							//console.log(cached_loaded_resource);
							return cached_loaded_resource;
						}
					}
					else if (!resource_cache_key_exists && !is_loaded_resource_ok) { //This allows to return loaded resources that are strings, numeric or boolean values, instead of being objects or arrays.
						if (typeof loaded_resource != "undefined" && ("" + k).match(/^\s*$/)) { //if loaded_resource is a string or a numeric and key is an empty string or only with white-chars (like spaces), then return the loaded_resource.
							//console.log("ENTERED in loaded_resource string:"+loaded_resource);
							return loaded_resource;
						}
						else
							break; //break so then it returns null
					}
					else if (resource_cache_key_exists && !is_cached_loaded_resource_ok)
						break; //break so then it returns null
				}
			}
			
			return null;
		},
		
		existLoadedSLAResults: function(resources_name, resources_cache_key) {
			if (resources_name && MyWidgetResourceLib.fn.isArray(resources_name))
				for (var i = 0, t = resources_name.length; i < t; i++) {
					var resource_name = resources_name[i];
					var resource_cache_key = resources_cache_key ? resources_cache_key[i] : null;
					
					if (this.existLoadedSLAResult(resource_name, resource_cache_key))
						return true;
				}
				
			return false;
		},
		
		//if resource_cache_key is passed, then check if resource_name exists in this.cached_loaded_sla_results.
		//else if resource_cache_key is NOT passed, then check if resource_name exists in this.loaded_sla_results.
		existLoadedSLAResult: function(resource_name, resource_cache_key) {
			resource_name = this.convertResourceNameToArray(resource_name);
			
			if (resource_name) {
				var resource_cache_key_exists = typeof resource_cache_key == "string" || MyWidgetResourceLib.fn.isNumeric(resource_cache_key);
				resource_cache_key = this.prepareResourceCacheKey(resource_cache_key);
				
				var resource_cache_key_used = false;
				var loaded_resource = this.loaded_sla_results;
				var cached_loaded_resource = this.cached_loaded_sla_results;
				//console.log("check if "+resource_name+" exists with cache key("+resource_cache_key_exists+"):"+resource_cache_key+"!");
				
				for (var i = 0, t = resource_name.length; i < t; i++) {
					var k = resource_name[i];
					var is_loaded_resource_ok = MyWidgetResourceLib.fn.isPlainObject(loaded_resource) || MyWidgetResourceLib.fn.isArray(loaded_resource);
					var is_cached_loaded_resource_ok = MyWidgetResourceLib.fn.isPlainObject(cached_loaded_resource) || MyWidgetResourceLib.fn.isArray(cached_loaded_resource);
					
					if (!resource_cache_key_exists && is_loaded_resource_ok) {
						if (i + 1 == t)
							return loaded_resource.hasOwnProperty(k); //check if exists any resource data from the "latest saved resource data".
						else
							loaded_resource = loaded_resource[k];
					}
					else if (resource_cache_key_exists && is_cached_loaded_resource_ok) {
						if (i + 1 == t) {
							if (!resource_cache_key_used)
								return MyWidgetResourceLib.fn.isPlainObject(cached_loaded_resource[k]) && cached_loaded_resource[k].hasOwnProperty(resource_cache_key); //check if exists any resource data from cache, if not yet used done.
							else
								return cached_loaded_resource.hasOwnProperty(k); //check if exists any resource data from cache.
						}
						else {
							cached_loaded_resource = cached_loaded_resource[k];
							
							if (!resource_cache_key_used && MyWidgetResourceLib.fn.isPlainObject(cached_loaded_resource) && cached_loaded_resource.hasOwnProperty(resource_cache_key)) {
								cached_loaded_resource = cached_loaded_resource[resource_cache_key];
								resource_cache_key_used = true;
							}
						}
					}
					else if (
						(!resource_cache_key_exists && !is_loaded_resource_ok) && 
						(resource_cache_key_exists && !is_cached_loaded_resource_ok)
					)
						break;
				}
			}
			
			return false;
		},
		
		//update the resource_name in this.loaded_sla_results with the cached data correspondent to the resource_cache_key
		updateLoadedSLAResult: function(resource_name, resource_cache_key) {
			resource_name = this.convertResourceNameToArray(resource_name);
			
			if (resource_name) {
				var loaded_resource = this.loaded_sla_results;
				
				for (var i = 0, t = resource_name.length; i < t; i++) {
					var k = resource_name[i];
					var is_loaded_resource_ok = MyWidgetResourceLib.fn.isPlainObject(loaded_resource) || MyWidgetResourceLib.fn.isArray(loaded_resource);
					
					if (is_loaded_resource_ok) {
						if (i + 1 == t) {
							//get resource data from cache
							resource_cache_key = typeof resource_cache_key == "string" || MyWidgetResourceLib.fn.isNumeric(resource_cache_key) ? resource_cache_key : "";
							
							loaded_resource[k] = this.getLoadedSLAResult(resource_name, resource_cache_key); //overwrite the previous value if exists, bc the loaded_resource only contains the latest value. All resources_data are in the this.cached_loaded_sla_results var.
							
							return true;
						}
						else {
							if (!MyWidgetResourceLib.fn.isPlainObject(loaded_resource[k]) && !MyWidgetResourceLib.fn.isArray(loaded_resource[k]))
								loaded_resource[k] = {};
							
							loaded_resource = loaded_resource[k];
						}
					}
				}
			}
			
			return false;
		},
		
		//removed cached data from resources
		removeLoadedSLAResult: function(resource_name) {
			resource_name = this.convertResourceNameToArray(resource_name);
			
			if (resource_name) {
				var loaded_resource = this.loaded_sla_results;
				var cached_loaded_resource = this.cached_loaded_sla_results;
				
				for (var i = 0, t = resource_name.length; i < t; i++) {
					var k = resource_name[i];
					var is_loaded_resource_ok = MyWidgetResourceLib.fn.isPlainObject(loaded_resource) || MyWidgetResourceLib.fn.isArray(loaded_resource);
					var is_cached_loaded_resource_ok = MyWidgetResourceLib.fn.isPlainObject(cached_loaded_resource) || MyWidgetResourceLib.fn.isArray(cached_loaded_resource);
					
					if (is_loaded_resource_ok) {
						if (i + 1 == t) {
							loaded_resource[k] = null;
							delete loaded_resource[k];
						}
						else
							loaded_resource = loaded_resource[k];
					}
					
					if (is_cached_loaded_resource_ok) {
						if (i + 1 == t) {
							cached_loaded_resource[k] = null;
							delete cached_loaded_resource[k];
						}
						else
							cached_loaded_resource = cached_loaded_resource[k];
					}
					
					if (!is_loaded_resource_ok && !is_cached_loaded_resource_ok)
						break;
				}
			}
		},
		
		/*
		 * The "data-widget-resources" attribute can have the following values:
		 * 	"native resource name",
		 * 	["native resource name x", "native resource name y"],
		 * 	id/key: "native resource name", //nome da variável do resource nativo correspondente.
		 * 	id/key: ["native resource name x", "native resource name y"], //lista sequencial com nomes da variáveis de resources nativos.
		 * 	id/key: {
		 * 		type (optional): native (default)|url
		 * 		value: (resource_name or url)
		 * 			If type==native || !type: nome da variável do resource nativo correspondente.
		 * 			If type==url: full url to execute ajax request
		 * 	}
		 * 	id/key: [
		 * 		"native resource name x",
		 * 		{value: "native resource name y", ...},
		 * 		{...}
		 * 	]
		*/
		getWidgetResources: function(elm, resource_key) {
			var found_resources = [];
			var resource = elm ? elm.getAttribute("data-widget-resources") : null;
			
			if (resource) {
				var fc = ("" + resource).substr(0, 1);
				resource = fc == "{" || fc == "[" ? MyWidgetResourceLib.fn.parseJson(resource) : resource;
				
				var keys = MyWidgetResourceLib.fn.isArray(resource_key) ? resource_key : [resource_key];
				
				for (var i = 0, t = keys.length; i < t; i++) {
					var key = keys[i];
					var r = null;
					
					if (!key && (typeof resource == "string" || MyWidgetResourceLib.fn.isArray(resource)))
						r = resource; //return resource var name
					else if (MyWidgetResourceLib.fn.isPlainObject(resource)) {
						if (!key && !resource.hasOwnProperty(key) && resource.hasOwnProperty("name"))
							r = resource;
						else
							r = resource[key];
					}
					
					if (r) {
						if (typeof r == "string")
							r = [r];
						else if (MyWidgetResourceLib.fn.isPlainObject(r))
							r = [r];
						
						if (MyWidgetResourceLib.fn.isArray(r))
							for (var i = 0, t = r.length; i < t; i++) {
								var resource_name = r[i]; //var name for the server resource
								var resource_type = "native";
								var resource_value = null; //in case resource_type=="url", the resource_value is the url it self
								var resource_method = null; //GET or POST
								var resource_data_type = null; //json, xml, text
								var resource_target = null; //target window name to execute this resource in a different window. Blank for default and execute it in this window as an ajax request. Note that if this option is active, the callbacks and messages below will be discard!
								var resource_validate = null; //function to validate ajax request response
								var resource_parse = null; //function to parse ajax request response
								var resource_success = null; //function to be executed on success ajax validation request response
								var resource_error = null; //function to be executed on unsuccess ajax validation request response
								var resource_success_message = null; //message to be shown on success ajax validation request response
								var resource_error_message = null; //message to be shown on unsuccess ajax validation request response
								var resource_confirmation_message = null; //message to be shown before the ajax request get executed
								var resource_conditions = null; //conditions to filter the resource, by adding them as search_attrs param in the resource url
								
								var resource_opts = {};
								
								if (MyWidgetResourceLib.fn.isPlainObject(resource_name)) {
									resource_opts = resource_name;
									resource_name = resource_opts["name"];
									resource_type = resource_opts["type"];
									resource_value = resource_opts["value"];
									resource_method = resource_opts["method"];
									resource_data_type = resource_opts["data_type"];
									resource_target = resource_opts["target"];
									resource_validate = resource_opts["validate"];
									resource_parse = resource_opts["parse"];
									resource_success = resource_opts["success"];
									resource_error = resource_opts["error"];
									resource_success_message = resource_opts["success_message"];
									resource_error_message = resource_opts["error_message"];
									resource_confirmation_message = resource_opts["confirmation_message"];
									resource_conditions = resource_opts["conditions"];
								}
								
								if (resource_name && typeof resource_name == "string") {
									resource_opts["name"] = resource_name;
									resource_opts["type"] = resource_type;
									resource_opts["value"] = resource_value;
									resource_opts["method"] = resource_method;
									resource_opts["data_type"] = resource_data_type;
									resource_opts["target"] = resource_target;
									resource_opts["validate"] = resource_validate;
									resource_opts["parse"] = resource_parse;
									resource_opts["success"] = resource_success;
									resource_opts["error"] = resource_error;
									resource_opts["success_message"] = resource_success_message;
									resource_opts["error_message"] = resource_error_message;
									resource_opts["confirmation_message"] = resource_confirmation_message;
									resource_opts["conditions"] = resource_conditions;
									
									found_resources.push(resource_opts);
								}
							}
					}
				}
			}
			
			return found_resources.length > 0 ? found_resources : null;
		},
		
		//removed cached data from widget resources
		purgeWidgetResources: function(elm, resource_key, opts) {
			var resources = null;
			
			if (resource_key)
				resources = this.getWidgetResources(elm, resource_key);
			
			if (!resources && (!opts || !opts["ignore_empty_resource"]))
				resources = this.getWidgetResources(elm, "");
			
			this.purgeResources(resources);
		},
		
		//removed cached data from resources
		purgeResources: function(resources) {
			if (MyWidgetResourceLib.fn.isArray(resources))
				for (var i = 0, t = resources.length; i < t; i++) {
					var resource_name = resources[i]["name"];
					
					this.removeLoadedSLAResult(resource_name);
				}
		},
		
		executeGetWidgetResourcesRequest: function(elm, resource_key, opts) {
			return this.executeWidgetResourcesRequest(elm, resource_key, opts);
		},
		
		executeSetWidgetResourcesRequest: function(elm, resource_key, opts) {
			opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? opts : {};
			var validate_func = MyWidgetResourceLib.fn.convertIntoFunctions(opts["validate"]);
			
			if (!validate_func)
				opts["validate"] = function(elm, data) {
					return data ? true : false; //note that on insert the data will be the primary key and on update and delte it will be a boolean or 1.
				};
			
			if (!opts.hasOwnProperty("force"))
				opts["force"] = true;
			
			if (!opts["method"])
				opts["method"] = "POST";
			
			return this.executeWidgetResourcesRequest(elm, resource_key, opts);
		},
		
		//resource_key and opts are optional
		executeWidgetResourcesRequest: function(elm, resource_key, opts) {
			var resources = null;
			
			if (resource_key)
				resources = this.getWidgetResources(elm, resource_key);
			
			if (!resources && (!opts || !opts["ignore_empty_resource"]))
				resources = this.getWidgetResources(elm, "");
			
			this.executeResourcesRequest(elm, resources, opts);
			
			return resources;
		},
		
		//opts are optional
		executeResourcesRequest: function(elm, resources, opts) {
			if (MyWidgetResourceLib.fn.isArray(resources) && resources.length > 0) {
				opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? opts : {};
				
				var extra_url = opts["extra_url"];
				var post_data  = opts["post_data"];
				var files_data  = opts["files_data"];
				var label  = opts["label"];
				var resources_options = opts["resources_options"];
				var complete_func = MyWidgetResourceLib.fn.convertIntoFunctions(opts["complete"]);
				var parse_func = MyWidgetResourceLib.fn.convertIntoFunctions(opts["parse"]);
				var before_func = MyWidgetResourceLib.fn.convertIntoFunctions(opts["before"]);
				var validate_func = MyWidgetResourceLib.fn.convertIntoFunctions(opts["validate"]);
				var success_func = MyWidgetResourceLib.fn.convertIntoFunctions(opts["success"]);
				var error_func = MyWidgetResourceLib.fn.convertIntoFunctions(opts["error"]);
				var force = opts.hasOwnProperty("force") ? opts["force"] : false;
				var purge_cache = opts.hasOwnProperty("purge_cache") ? opts["purge_cache"] : false;
				var ajax_async = opts.hasOwnProperty("async") ? opts["async"] : true;
				var ajax_timeout = opts.hasOwnProperty("timeout") ? opts["timeout"] : true;
				var ajax_method = opts["method"] ? opts["method"] : "GET";
				var resource_default_cache_key = opts["resource_default_cache_key"];
				var ignore_resource_conditions = opts["ignore_resource_conditions"];
				var target = opts["target"];
				var resources_name = [];
				var resources_cache_key = [];
				
				if (!validate_func)
					validate_func = function(elm, data) {
						return true;
					};
				
				//removed cached data from resources
				if (purge_cache)
					this.purgeResources(resources);
				
				//execute multiple resources if apply sequentially. Stop, if any of the resources gives an error.
				var execute_func = function(resources, index) {
					if (index >= resources.length) {
						if (complete_func)
							MyWidgetResourceLib.fn.executeFunctions(complete_func, elm, resources, resources_name, resources_cache_key);
					}
					else {
						//prepare resource options
						var r = resources[index];
						var resource_name = r["name"];
						var resource_type = r["type"];
						var resource_value = r["value"];
						var resource_method = r["method"] ? r["method"] : ajax_method;
						var resource_data_type = r["data_type"];
						var resource_target = r["target"] ? r["target"] : target;
						var resource_before = MyWidgetResourceLib.fn.convertIntoFunctions(r["before"]);
						var resource_parse = MyWidgetResourceLib.fn.convertIntoFunctions(r["parse"]);
						var resource_validate = MyWidgetResourceLib.fn.convertIntoFunctions(r["validate"]);
						var resource_success = MyWidgetResourceLib.fn.convertIntoFunctions(r["success"]);
						var resource_error = MyWidgetResourceLib.fn.convertIntoFunctions(r["error"]);
						var resource_success_message = r["success_message"];
						var resource_error_message = r["error_message"];
						var resource_confirmation_message = r["confirmation_message"];
						var resource_conditions = r["conditions"];
						
						//prepare resource extra options
						var resource_options = MyWidgetResourceLib.fn.isArray(resources_options) ? resources_options[index] : (MyWidgetResourceLib.fn.isPlainObject(resources_options) ? resources_options[resource_name] : null);
						resource_options = MyWidgetResourceLib.fn.isPlainObject(resource_options) ? resource_options : {};
						
						var resource_before_extra = MyWidgetResourceLib.fn.convertIntoFunctions(resource_options["before"]);
						var resource_parse_extra = MyWidgetResourceLib.fn.convertIntoFunctions(resource_options["parse"]);
						var resource_validate_extra = MyWidgetResourceLib.fn.convertIntoFunctions(resource_options["validate"]);
						var resource_success_extra = MyWidgetResourceLib.fn.convertIntoFunctions(resource_options["success"]);
						var resource_error_extra = MyWidgetResourceLib.fn.convertIntoFunctions(resource_options["error"]);
						var resource_extra_url = resource_options["extra_url"];
						
						if (resource_options["success_message"])
							resource_success_message = resource_options["success_message"];
						
						if (resource_options["error_message"])
							resource_error_message = resource_options["error_message"];
						
						if (resource_options["confirmation_message"])
							resource_success_message = resource_options["confirmation_message"];
						
						if (resource_options["method"])
							resource_method = resource_options["method"];
						
						if (resource_options["data_type"])
							resource_data_type = resource_options["data_type"];
						
						if (resource_options["target"])
							resource_target = resource_options["target"];
						
						//execute resource
						if (ignore_resource_conditions)
							resource_conditions = null;
						
						if (!resource_confirmation_message || confirm(resource_confirmation_message)) {
							var url = MyWidgetResourceLib.ResourceHandler.getResourceUrl(resource_name, resource_type, resource_value, resource_conditions);
							if (url && extra_url)
								url += (extra_url.substr(0, 1) == "&" ? "" : "&") + extra_url;
							
							if (url && resource_extra_url)
								url += (resource_extra_url.substr(0, 1) == "&" ? "" : "&") + resource_extra_url;
							
							if (resource_target) {
								var request_status = MyWidgetResourceLib.ResourceHandler.sendResourceRequestToExternalWindow(url, resource_method, resource_target, post_data, files_data);
								
								if (request_status)
									execute_func(resources, index + 1);
								else
									MyWidgetResourceLib.MessageHandler.showErrorMessage("Could not execute " + label + " resource: '" + resource_name + "'.");
							}
							else {
								var resource_cache_key = MyWidgetResourceLib.ResourceHandler.getResourceCacheKey(resource_name, resource_type, resource_value, resource_default_cache_key, url);
								var exist_loaded_resource = MyWidgetResourceLib.ResourceHandler.existLoadedSLAResult(resource_name, resource_cache_key);
								
								//save resource_name and resource_cache_key
								resources[index]["cache_key"] = resource_cache_key;
								
								var existent_resource_name_pos = resources_name.indexOf(resource_name);
								
								if (existent_resource_name_pos == -1 || resources_cache_key[existent_resource_name_pos] !== resource_cache_key) { //both arrays must be synced and with the same length
									resources_name.push(resource_name);
									resources_cache_key.push(resource_cache_key);
								}
								
								//if exists cache and is not force and is not a POST method. POST methods cannot be cached!
								if (exist_loaded_resource && !force && (!resource_method || ("" + resource_method).toLowerCase() == "get")) {
									MyWidgetResourceLib.ResourceHandler.updateLoadedSLAResult(resource_name, resource_cache_key);
									execute_func(resources, index + 1);
								}
								else {
									var request_status = false;
									
									if (url) {
										var url_cache_id = url + "&method=" + resource_method.toUpperCase() + (post_data ? "&data=" + JSON.stringify(post_data) : "");
										
										if (!resource_data_type)
											resource_data_type = "json";
										
										//prepare ajax success func
										var ajax_success_func = function(data, text_status, jqXHR) {
											if (parse_func)
												data = MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(parse_func, elm, data);
											
											if (resource_parse)
												data = MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(resource_parse, elm, data);
											
											if (resource_parse_extra)
												data = MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(resource_parse_extra, elm, data);
											
											if (
												(resource_validate && MyWidgetResourceLib.fn.executeFunctions(resource_validate, elm, data)) || 
												(resource_validate_extra && MyWidgetResourceLib.fn.executeFunctions(resource_validate_extra, elm, data)) || 
												(!resource_validate && !resource_validate_extra && MyWidgetResourceLib.fn.executeFunctions(validate_func, elm, data))
											) {
												MyWidgetResourceLib.ResourceHandler.setLoadedSLAResult(resource_name, resource_cache_key, data); //save data in case other html wants to access it
												
												if (success_func)
													MyWidgetResourceLib.fn.executeFunctions(success_func, elm, data);
												
												if (resource_success)
													MyWidgetResourceLib.fn.executeFunctions(resource_success, elm, data);
												
												if (resource_success_extra)
													MyWidgetResourceLib.fn.executeFunctions(resource_success_extra, elm, data);
												
												if (resource_success_message)
													MyWidgetResourceLib.MessageHandler.showInfoMessage(resource_success_message);
												
												execute_func(resources, index + 1);
											}
											else {
												var show_error = true;
												
												if (error_func)
													show_error = MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(error_func, elm, jqXHR, text_status, data);
												
												if (resource_error && !MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(resource_error, elm, jqXHR, text_status, data))
													show_error = false;
												
												if (resource_error_extra && !MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(resource_error_extra, elm, jqXHR, text_status, data))
														show_error = false;
												
												if (show_error !== false) {
													if (resource_error_message)
														MyWidgetResourceLib.MessageHandler.showErrorMessage(resource_error_message + (data ? "<br/>" + data : ""));
													else
														MyWidgetResourceLib.MessageHandler.showErrorMessage("Error executing " + label + " resource: '" + resource_name + "'." + (data ? "<br/>" + data : ""));
												}
											}
										};
										
										//prepare ajax error func
										var ajax_error_func = function(jqXHR, text_status, error_thrown) {
											var show_error = true;
											
											if (error_func)
												show_error = MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(error_func, elm, jqXHR, text_status, error_thrown);
											
											if (resource_error && !MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(resource_error, elm, jqXHR, text_status, error_thrown))
												show_error = false;
											
											if (resource_error_extra && !MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(resource_error_extra, elm, jqXHR, text_status, error_thrown))
												show_error = false;
											
											if (error_thrown && jqXHR.responseText == "" && resource_data_type == "json")
												error_thrown = "";
											
											if (show_error !== false) {
												var msg_elm = null;
												
												if (resource_error_message)
													msg_elm = MyWidgetResourceLib.MessageHandler.showErrorMessage(resource_error_message + (error_thrown ? "<br/>" + error_thrown : ""));
												else
													msg_elm = MyWidgetResourceLib.MessageHandler.showErrorMessage("Error executing " + label + " resource: '" + resource_name + "'." + (error_thrown ? "<br/>" + error_thrown : ""));
												
												if (msg_elm && jqXHR.responseText != "" && resource_data_type == "json") {
													try {
														var html = jqXHR.responseText;
														html += '<style>'
															+ 'body {'
															+ '	background:#fff;'
															+ '}'
															+ '::-webkit-scrollbar {'
															+ '	width:10px;'
															+ '	height:10px;'
															+ '	background:transparent;'
															+ '}'
															+ '::-webkit-scrollbar-track {' //Track
															+ '	background:transparent;'
															+ '}'
															+ '::-webkit-scrollbar-thumb {' //Handle
															+ '	background:rgba(0,0,0,0.2);'
															+ '	background-clip:padding-box;'
															+ '	border:2px solid transparent;'
															+ '	border-radius:9999px;'
															+ '}'
														+ '</style>';
														
														var iframe = document.createElement("IFRAME");
														msg_elm.appendChild(iframe);
														
														var doc = iframe.contentDocument ? iframe.contentDocument : iframe.contentWindow.document;
														doc.open();
														doc.write(html);
														
														
														doc.close();
													}
													catch(e) {
														if (console && console.log) {
															console.log(e);
															console.log(jqXHR.responseText);
														}
													}
												}
											}
										};
										
										//prepare ajax execute func
										var ajax_execute_func = function(url) {
											//prepare url so the browser doesn't cache it. note that this library have its own cache, so we want to avoid the browser cache.
											var ajax_url = url + (url.indexOf("?") != -1 ? "&" : "?") + "t=" + (new Date()).getTime();
											
											//prepare func to clean cached request
											var clean_url_request_func = function() {
												MyWidgetResourceLib.ResourceHandler.requesting_urls[url_cache_id] = null;
												delete MyWidgetResourceLib.ResourceHandler.requesting_urls[url_cache_id];
											};
											
											var ajax_options = {
												url: ajax_url,
												type: resource_method,
												method: resource_method,
												data: post_data,
												dataType: resource_data_type,
												success: function(data, text_status, jqXHR) {
													//cache request
													MyWidgetResourceLib.ResourceHandler.requesting_urls[url_cache_id] = {
														success: [data, text_status, jqXHR]
													};
													
													//handle data
													ajax_success_func(data, text_status, jqXHR);
													
													//clean request cache after 2secs
													setTimeout(clean_url_request_func, 2000);
												},
												error: function(jqXHR, text_status, error_thrown) {
													//cache request
													MyWidgetResourceLib.ResourceHandler.requesting_urls[url_cache_id] = {
														error: [jqXHR, text_status, error_thrown]
													};
													
													//handle error
													ajax_error_func(jqXHR, text_status, error_thrown);
													
													//clean request cache after 2secs
													setTimeout(clean_url_request_func, 2000);
												},
												async: ajax_async,
												timeout: ajax_timeout
											};
											MyWidgetResourceLib.ResourceHandler.prepareAjaxOptionsWithFileUpload(ajax_options, post_data, files_data);
											
											//parse ajax settings
											MyWidgetResourceLib.fn.executeFunctions(before_func, ajax_options);
											MyWidgetResourceLib.fn.executeFunctions(resource_before, ajax_options);
											MyWidgetResourceLib.fn.executeFunctions(resource_before_extra, ajax_options);
											
											var ajax_status = MyWidgetResourceLib.AjaxHandler.getAjaxRequest(ajax_options);
											
											//set request cache to true
											if (ajax_status)
												MyWidgetResourceLib.ResourceHandler.requesting_urls[url_cache_id] = true;
											
											return ajax_status;
										};
										
										//check if there is already another process running and do not execute it again, waiting for the previous request response, but only if the request is async.
										if (MyWidgetResourceLib.ResourceHandler.requesting_urls.hasOwnProperty(url_cache_id) && ajax_async) {
											//set maximum offset to wait until repeat request. 50 times * 100 milliseconds = 5 seconds.
											var offset = 50;
											
											var func = function() {
												var requesting_url = MyWidgetResourceLib.ResourceHandler.requesting_urls[url_cache_id];
												
												if (requesting_url === true && offset > 0)
													setTimeout(func, 100);
												else if (MyWidgetResourceLib.fn.isPlainObject(requesting_url) && (requesting_url["success"] || requesting_url["error"])) {
													if (requesting_url["success"])
														ajax_success_func(requesting_url["success"][0], requesting_url["success"][1], requesting_url["success"][2]);
													else
														ajax_error_func(requesting_url["error"][0], requesting_url["error"][1], requesting_url["error"][2]);
												}
												else {
													request_status = ajax_execute_func(url);
													
													if (!request_status)
														MyWidgetResourceLib.MessageHandler.showErrorMessage("Could not execute " + label + " resource: '" + resource_name + "'.");
												}
												
												offset--;
											};
											
											setTimeout(func, 100);
											request_status = true;
										}
										else
											request_status = ajax_execute_func(url);
									}
									
									if (!request_status)
										MyWidgetResourceLib.MessageHandler.showErrorMessage("Could not execute " + label + " resource: '" + resource_name + "'.");
								}
							}
						}
						//else do nothing else and stop the following resources requests, if any...
					}
				};
				
				execute_func(resources, 0);
			}
		},
		
		//resource_type, resource_value and opts are optional
		executeResourceNameRequest: function(elm, resource_name, resource_type, resource_value, resource_conditions, opts) {
			if (resource_name) {
				opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? opts : {};
				
				var extra_url = opts["extra_url"];
				var post_data  = opts["post_data"];
				var files_data  = opts["files_data"];
				var label  = opts["label"];
				var parse_func = MyWidgetResourceLib.fn.convertIntoFunctions(opts["parse"]);
				var before_func = MyWidgetResourceLib.fn.convertIntoFunctions(opts["before"]);
				var validate_func = MyWidgetResourceLib.fn.convertIntoFunctions(opts["validate"]);
				var success_func = MyWidgetResourceLib.fn.convertIntoFunctions(opts["success"]);
				var error_func = MyWidgetResourceLib.fn.convertIntoFunctions(opts["error"]);
				var confirmation_message = opts["confirmation_message"];
				var force = opts.hasOwnProperty("force") ? opts["force"] : false;
				var purge_cache = opts.hasOwnProperty("purge_cache") ? opts["purge_cache"] : false;
				var ajax_async = opts.hasOwnProperty("async") ? opts["async"] : true;
				var ajax_timeout = opts.hasOwnProperty("timeout") ? opts["timeout"] : true;
				var ajax_method = opts["method"] ? opts["method"] : "GET";
				var resource_default_cache_key = opts["resource_default_cache_key"];
				var ignore_resource_conditions = opts["ignore_resource_conditions"];
				var target = opts["target"];
				
				if (!confirmation_message || confirm(confirmation_message)) {
					if (!validate_func)
						validate_func = function(data) {
							return true;
						};
					
					//removed cached data from resources
					if (purge_cache)
						this.purgeResources( [ {name: resource_name} ] );
					
					if (ignore_resource_conditions)
						resource_conditions = null;
					
					var url = MyWidgetResourceLib.ResourceHandler.getResourceUrl(resource_name, resource_type, resource_value, resource_conditions);
					
					if (url && extra_url)
						url += (extra_url.substr(0, 1) == "&" ? "" : "&") + extra_url;
					
					if (target) {
						var request_status = MyWidgetResourceLib.ResourceHandler.sendResourceRequestToExternalWindow(url, ajax_method, target, post_data, files_data);
								
						if (!request_status)
							MyWidgetResourceLib.MessageHandler.showErrorMessage("Could not execute " + label + " resource: '" + resource_name + "'.");
						
						return request_status;
					}
					else {
						var resource_cache_key = MyWidgetResourceLib.ResourceHandler.getResourceCacheKey(resource_name, resource_type, resource_value, resource_default_cache_key, url);
						var exist_loaded_resource = MyWidgetResourceLib.ResourceHandler.existLoadedSLAResult(resource_name, resource_cache_key);
						
						if (exist_loaded_resource && !force) {
							MyWidgetResourceLib.ResourceHandler.updateLoadedSLAResult(resource_name, resource_cache_key);
						}
						else {
							var request_status = false;
							
							if (url) {
								var url_cache_id = url + "&method=" + ajax_method.toUpperCase() + (post_data ? "&data=" + JSON.stringify(post_data) : "");
								
								//prepare ajax success func
								var ajax_success_func = function(data, text_status, jqXHR) {
									if (parse_func)
										data = MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(parse_func, elm, data);
									
									if (MyWidgetResourceLib.fn.executeFunctions(validate_func, elm, data)) {
										MyWidgetResourceLib.ResourceHandler.setLoadedSLAResult(resource_name, resource_cache_key, data); //save data in case other html wants to access it
										
										if (success_func)
											MyWidgetResourceLib.fn.executeFunctions(success_func, elm, data);
									}
								};
								
								//prepare ajax error func
								var ajax_error_func = function(jqXHR, text_status, error_thrown) {
									var show_error = true;
									
									if (error_func)
										show_error = MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(error_func, elm, jqXHR, text_status, error_thrown);
									
									if (error_thrown && jqXHR.responseText == "" && (!resource_data_type || resource_data_type == "json"))
										error_thrown = "";
									
									if (show_error !== false) {
										MyWidgetResourceLib.MessageHandler.showErrorMessage("Error executing " + label + " resource: '" + resource_name + "'." + (error_thrown ? "<br/>" + error_thrown : ""));
									}
								};
								
								//prepare ajax execute func
								var ajax_execute_func = function(url) {
									//prepare url so the browser doesn't cache it. note that this library have its own cache, so we want to avoid the browser cache.
									var ajax_url = url + (url.indexOf("?") != -1 ? "&" : "?") + "t=" + (new Date()).getTime();
									
									//prepare func to clean cached request
									var clean_url_request_func = function() {
										MyWidgetResourceLib.ResourceHandler.requesting_urls[url_cache_id] = null;
										delete MyWidgetResourceLib.ResourceHandler.requesting_urls[url_cache_id];
									};
									
									var ajax_options = {
										url: ajax_url,
										type: ajax_method,
										method: ajax_method,
										data: post_data,
										dataType: "json",
										success: function(data, text_status, jqXHR) {
											//cache request
											MyWidgetResourceLib.ResourceHandler.requesting_urls[url_cache_id] = {
												success: [data, text_status, jqXHR]
											};
											
											//handle data
											ajax_success_func(data, text_status, jqXHR);
											
											//clean request cache after 2secs
											setTimeout(clean_url_request_func, 2000);
										},
										error: function(jqXHR, text_status, error_thrown) {
											//cache request
											MyWidgetResourceLib.ResourceHandler.requesting_urls[url_cache_id] = {
												error: [jqXHR, text_status, error_thrown]
											};
											
											//handle error
											ajax_error_func(jqXHR, text_status, error_thrown);
											
											//clean request cache after 2secs
											setTimeout(clean_url_request_func, 2000);
										},
										async: ajax_async,
										timeout: ajax_timeout
									};
									MyWidgetResourceLib.ResourceHandler.prepareAjaxOptionsWithFileUpload(ajax_options, post_data, files_data);
									//parse ajax settings
									MyWidgetResourceLib.fn.executeFunctions(before_func, ajax_options);
									
									var ajax_status = MyWidgetResourceLib.AjaxHandler.getAjaxRequest(ajax_options);
									
									//set request cache to true
									if (ajax_status)
										MyWidgetResourceLib.ResourceHandler.requesting_urls[url_cache_id] = true;
									
									return ajax_status;
								};
								
								//check if there is already another process running and do not execute it again, waiting for the previous request response, but only if the request is async.
								if (MyWidgetResourceLib.ResourceHandler.requesting_urls.hasOwnProperty(url_cache_id) && ajax_async) {
									//set maximum offset to wait until repeat request. 50 times * 100 milliseconds = 5 seconds.
									var offset = 50;
									
									var func = function() {
										var requesting_url = MyWidgetResourceLib.ResourceHandler.requesting_urls[url_cache_id];
										
										if (requesting_url === true && offset > 0)
											setTimeout(func, 100);
										else if (MyWidgetResourceLib.fn.isPlainObject(requesting_url) && (requesting_url["success"] || requesting_url["error"])) {
											if (requesting_url["success"])
												ajax_success_func(requesting_url["success"][0], requesting_url["success"][1], requesting_url["success"][2]);
											else
												ajax_error_func(requesting_url["error"][0], requesting_url["error"][1], requesting_url["error"][2]);
										}
										else {
											request_status = ajax_execute_func(url);
											
											if (!request_status)
												MyWidgetResourceLib.MessageHandler.showErrorMessage("Could not execute " + label + " resource: '" + resource_name + "'.");
										}
										
										offset--;
									};
									
									setTimeout(func, 100);
									request_status = true;
								}
								else
									request_status = ajax_execute_func(url);
							}
							
							if (!request_status)
								MyWidgetResourceLib.MessageHandler.showErrorMessage("Could not execute " + label + " resource: '" + resource_name + "'.");
							
							return request_status;
						}
					}
				}
				else
					return true;
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: Resource name cannot be undefined in executeResourceNameRequest method.");
			
			return false;
		},
		
		isWidgetResourceNameExecuting: function(elm, resource_name) {
			if (MyWidgetResourceLib.fn.isPlainObject(MyWidgetResourceLib.ResourceHandler.requesting_urls))
				for (var url in MyWidgetResourceLib.ResourceHandler.requesting_urls) {
					var regex = new RegExp("(&|\\?)resource=" + resource_name + "(&|$)");
					
					if (url.match(regex))
						return true;
				}
			
			return false;
		},
		
		isGetWidgetResourcesRequestExecuting: function(elm, resource_key, opts) {
			return this.isWidgetResourcesRequestExecuting(elm, resource_key, opts);
		},
		
		isSetWidgetResourcesRequestExecuting: function(elm, resource_key, opts) {
			opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? opts : {};
			
			if (!opts["method"])
				opts["method"] = "POST";
			
			return this.isWidgetResourcesRequestExecuting(elm, resource_key, opts);
		},
		
		isWidgetResourcesRequestExecuting: function(elm, resource_key, opts) {
			var resources = null;
			
			if (resource_key)
				resources = this.getWidgetResources(elm, resource_key);
			
			if (!resources && (!opts || !opts["ignore_empty_resource"]))
				resources = this.getWidgetResources(elm, "");
			
			return this.isResourcesRequestExecuting(elm, resources, opts);
		},
		
		isResourcesRequestExecuting: function(elm, resources, opts) {
			if (MyWidgetResourceLib.fn.isArray(resources) && resources.length > 0) {
				opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? opts : {};
				
				var extra_url = opts["extra_url"];
				var post_data  = opts["post_data"];
				var purge_cache = opts.hasOwnProperty("purge_cache") ? opts["purge_cache"] : false;
				var ajax_method = opts["method"] ? opts["method"] : "GET";
				var ignore_resource_conditions = opts["ignore_resource_conditions"];
				
				//removed cached data from resources
				if (purge_cache)
					this.purgeResources(resources);
				
				//execute multiple resources if apply sequentially. Stop, if any of the resources gives an error.
				for (var i = 0, t = resources.length; i < t; i++) {
					var r = resources[i];
					var resource_name = r["name"];
					var resource_type = r["type"];
					var resource_value = r["value"];
					var resource_method = r["method"] ? r["method"] : ajax_method;
					var resource_conditions = r["conditions"];
					
					if (ignore_resource_conditions)
						resource_conditions = null;
					
					var url = MyWidgetResourceLib.ResourceHandler.getResourceUrl(resource_name, resource_type, resource_value, resource_conditions);
					
					if (url && extra_url)
						url += (extra_url.substr(0, 1) == "&" ? "" : "&") + extra_url;
					
					if (url) {
						var url_cache_id = url + "&method=" + resource_method.toUpperCase() + (post_data ? "&data=" + JSON.stringify(post_data) : "");
						var requesting_url = MyWidgetResourceLib.ResourceHandler.requesting_urls[url_cache_id];
						
						if (requesting_url === true)
							return true;
					}
				}
			}
			
			return false;
		},
		
		isResourceNameRequestExecuting: function(resource_name, resource_type, resource_value, resource_conditions, opts) {
			if (resource_name) {
				opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? opts : {};
				
				var extra_url = opts["extra_url"];
				var post_data  = opts["post_data"];
				var purge_cache = opts.hasOwnProperty("purge_cache") ? opts["purge_cache"] : false;
				var ajax_method = opts["method"] ? opts["method"] : "GET";
				var ignore_resource_conditions = opts["ignore_resource_conditions"];
				
				//removed cached data from resources
				if (purge_cache)
					this.purgeResources( [ {name: resource_name} ] );
				
				if (ignore_resource_conditions)
					resource_conditions = null;
				
				var url = MyWidgetResourceLib.ResourceHandler.getResourceUrl(resource_name, resource_type, resource_value, resource_conditions);
				
				if (url && extra_url)
					url += (extra_url.substr(0, 1) == "&" ? "" : "&") + extra_url;
				
				if (url) {
					var url_cache_id = url + "&method=" + ajax_method.toUpperCase() + (post_data ? "&data=" + JSON.stringify(post_data) : "");
					var requesting_url = MyWidgetResourceLib.ResourceHandler.requesting_urls[url_cache_id];
					
					if (requesting_url === true)
						return true;
				}
			}
			
			return false;					
		},
		
		sendResourceRequestToExternalWindow: function(url, method, target, post_data, files_data) {
			var status = false;
			
			if (url) {
				try {
					var win = window.open("blank.htm", target); //dummy url, because the window will below be replaced by the form url
					
					if (win) {
						if (method)
							method = method.toUpperCase();
						else
							method = post_data && MyWidgetResourceLib.fn.isPlainObject(post_data) && !MyWidgetResourceLib.fn.isEmptyObject(post_data) ? "POST" : "GET";
						
						var form = document.createElement("form");
						form.setAttribute("method", method);
						form.setAttribute("action", url);
						form.setAttribute("target", target);
						
						if (method == "GET" && url.indexOf("?") != -1) {
							var url_obj = new URL(url);
							
							if (url_obj)
								url_obj.searchParams.forEach(function(param_value, param_name) {
									var input = document.createElement('input');
									input.type = 'hidden';
									input.name = param_name;
									input.value = param_value;
									
									form.appendChild(input);
								});
						}
						
						if (post_data)
							for (var k in post_data) {
								if (post_data.hasOwnProperty(k)) {
									var input = document.createElement('input');
									input.type = 'hidden';
									input.name = k;
									input.value = post_data[k];
									
									form.appendChild(input);
								}
							}
						
						document.body.appendChild(form);
						
						if (files_data && MyWidgetResourceLib.fn.isPlainObject(files_data) && !MyWidgetResourceLib.fn.isEmptyObject(files_data)) {
							form.setAttribute("enctype", "multipart/form-data");
							
							for (var k in files_data)
								if (files_data.hasOwnProperty(k)) {
									var input = document.createElement('input');
									input.type = 'file';
									input.name = k;
									input.files = files_data[k];
									
									form.appendChild(input);
								}
						}
						
						//note I am using a post.htm page since I did not want to make double request to the page 
						//it might have some Page_Load call which might screw things up.
						form.submit();
						
						document.body.removeChild(form);
						
						status = true;
					}
				}
				catch(e) {
					if (console && console.log)
						console.log(e);
				}
			}
			
			return status;
		},
		
		convertValueToFormData: function(val, formData, namespace) {
			if (!formData)
				formData = new FormData();
			
			var is_plain_object = MyWidgetResourceLib.fn.isPlainObject(val) && !(val instanceof File);
			
			if (namespace || is_plain_object) {
				if (val instanceof Date)
					formData.append(namespace, val.toISOString());
				else if (val instanceof Array) {
					for (var i = 0; i < val.length; i++)
						this.convertValueToFormData(val[i], formData, namespace + '[' + i + ']');
				}
				else if (is_plain_object) {
					for (var property_name in val)
						if (val.hasOwnProperty(property_name))
							this.convertValueToFormData(val[property_name], formData, namespace ? namespace + '[' + property_name + ']' : property_name);
				}
				else if (val instanceof File) {
					if (val.name)
						formData.append(namespace, val, val.name);
					else
	   					formData.append(namespace, val);
	   			}
				else if (typeof val !== 'undefined' || val == null)
					formData.append(namespace, val);
				else
					formData.append(namespace, val.toString());
			}
			
			return formData;
		},
		
		prepareAjaxOptionsWithFileUpload: function(ajax_options, post_data, files_data) {
			if (files_data && MyWidgetResourceLib.fn.isPlainObject(files_data) && !MyWidgetResourceLib.fn.isEmptyObject(files_data)) {
				var formData = this.convertValueToFormData(post_data);
				
				for (var input_name in files_data) {
					var files = files_data[input_name];
					
					for (var i = 0; i < files.length; i++)
						formData.append(input_name, files[i]);
				}
				//for (var pair of formData.entries()) console.log(pair[0]+ ', ' + pair[1]); 
				
				ajax_options["data"] = formData;
				ajax_options["contentType"] = false;
				ajax_options["processData"] = false;
				ajax_options["cache"] = false;
			}
			
			return ajax_options;
		},
	});
	/****************************************************************************************
	 *				 END: RESOURCE HANDLER 					*
	 ****************************************************************************************/
	
	/****************************************************************************************
	 *				 START: MESSAGE HANDLER 					*
	 ****************************************************************************************/
	MyWidgetResourceLib.MessageHandler = MyWidgetResourceLib.fn.MessageHandler = ({
		
		showInfoMessage: function(msg) {
			return this.showMessage(msg, "status_message_info");
		},
		
		showErrorMessage: function(msg) {
			return this.showMessage(msg, "status_message_error");
		},
		
		showMessage: function(msg, c) {
			if (typeof msg == "string" && msg.replace(/\s+/g, "") != "") {
				var msg_elm = document.querySelector(".status_message");
				
				if (!msg_elm) {
					document.body.insertAdjacentHTML('beforeend', '<div class="status_message"></div>');
					msg_elm = document.querySelector(".status_message");
					
					msg_elm.addEventListener("click", function() {
						msg_elm.innerHTML = ''; //clean contents
						
						MyWidgetResourceLib.fn.hide(this);
					});
				}
				
				var div = document.createElement("DIV");
				div.className = c;
				div.textContent = msg.replace(/<br\s*\/?>/gi, "\n");//msg.replace(/\n/g, "<br/>");
				div.insertAdjacentHTML('beforeend', '<span class="close_message" onClick="MyWidgetResourceLib.MessageHandler.closeMessage(this)">close</span>');
				
				msg_elm.appendChild(div);
				
				var msg_item_elms = msg_elm.querySelectorAll("." + c);
				var msg_item_elm = msg_item_elms && msg_item_elms.length > 0 ? msg_item_elms[msg_item_elms.length - 1] : null;
				
				if (msg_item_elm) {
					msg_item_elm.addEventListener("click", function(event) {
						event.stopPropagation();
					});
					
					MyWidgetResourceLib.fn.show(msg_elm);
				}
				
				return div;
			}
			
			return null;
		},
		
		closeMessage: function(elm) {
			var msg_item_elm = elm.closest(".status_message_info, .status_message_error");
			var msg_elm = elm.closest(".status_message");
			
			if (msg_item_elm)
				msg_item_elm.parentNode.removeChild(msg_item_elm);
			
			if (msg_elm) {
				var is_empty = msg_elm.querySelectorAll(".status_message_info, .status_message_error").length == 0;
				
				if (is_empty)
					MyWidgetResourceLib.fn.hide(msg_elm);
			}
		},
		
		getMessageElement: function() {
			return document.querySelector(".status_message");
		},
	});
	/****************************************************************************************
	 *				 END: MESSAGE HANDLER 					*
	 ****************************************************************************************/
		
	/****************************************************************************************
	 *				 START: PERMISSION HANDLER 					*
	 ****************************************************************************************/
	MyWidgetResourceLib.PermissionHandler = MyWidgetResourceLib.fn.PermissionHandler = ({
		
		loadWidgetsPermissions: function() {
			//:not(.template-widget) is very important so it doesn't load the resource when we are editing this widget through the LayoutUIEditor.
			var elms = document.querySelectorAll("[data-widget-permissions]:not([data-widget-permissions-loaded]):not(.template-widget)");
			
			if (elms)
				for (var i = 0, t = elms.length; i < t; i++)
					this.loadWidgetPermissions(elms[i]);
		},
		
		loadWidgetPermissions: function(elm) {
			if (elm) {
				var perms = MyWidgetResourceLib.PermissionHandler.getWidgetPermissions(elm); //Do not use the "this" code here bc we may call this function as a handler and it will loose its parentship.
				var show = perms["show"];
				var hide = perms["hide"];
				var remove = perms["remove"];
				
				if (remove)
					elm.parentNode.removeChild(elm);
				else if (hide)
					MyWidgetResourceLib.fn.hide(elm);
				else if (show && elm.style.display == "none")
					MyWidgetResourceLib.fn.show(elm);
				
				elm.setAttribute("data-widget-permissions-loaded", "");
			}
		},
		
		/*
		 * data-widget-permissions possible values:
		 *	user_type_id
		 *	[user_type_id_x, user_type_id_y]
		 *	user_type_ids:
		 *		user_type_id
		 *		[user_type_id_x, user_type_id_y]
		 *	access/view/show/hide/remove: user_type_id
		 *	access/view/show/hide/remove: [user_type_id_x, user_type_id_y]
		 *	access/view/show/hide/remove:
		 *		resources: 
		 *			resource_name
		 *			[resource_name, resource_name]
		 *			{name: xxx, ...}
		 *			[resource_name, {name: xxx, ...}]
		 *		values:
		 *			value
		 *			[value_x, value_y]
		 *		user_type_ids:
		 *			user_type_id
		 *			[user_type_id_x, user_type_id_y]
		 */
		getWidgetPermissions: function(elm) {
			var show = true;
			var hide = false;
			var remove = false;
			
			if (elm) {
				var permissions = elm.hasAttribute("data-widget-permissions") ? elm.getAttribute("data-widget-permissions") : null;
				permissions = permissions && ("" + permissions).substr(0, 1) == "{" ? MyWidgetResourceLib.fn.parseJson(permissions) : permissions;
				
				if (permissions) {
					if (!MyWidgetResourceLib.fn.isPlainObject(permissions))
						permissions = {view: { user_type_ids: permissions }};
					
					if (MyWidgetResourceLib.fn.isPlainObject(permissions)) {
						if (permissions.hasOwnProperty("access") || permissions.hasOwnProperty("view") || permissions.hasOwnProperty("show"))
							show = false;
						
						var public_user_type_id = MyWidgetResourceLib.fn.public_user_type_id;
						var logged_user_type_ids = MyWidgetResourceLib.fn.logged_user_type_ids;
						var is_logged = logged_user_type_ids && MyWidgetResourceLib.fn.isArray(logged_user_type_ids) && logged_user_type_ids.length > 0;
						
						for (var k in permissions) {
							var v = permissions[k];
							
							if (!MyWidgetResourceLib.fn.isPlainObject(v))
								v = {user_type_ids: v};
							
							if (MyWidgetResourceLib.fn.isPlainObject(v)) {
								var resources = v.hasOwnProperty("resources") ? v["resources"] : [];
								var values = v.hasOwnProperty("values") ? v["values"] : [];
								var user_type_ids = v.hasOwnProperty("user_type_ids") ? v["user_type_ids"] : [];
								
								resources = MyWidgetResourceLib.fn.isArray(resources) ? resources : [resources];
								values = MyWidgetResourceLib.fn.isArray(values) ? values : [values];
								user_type_ids = MyWidgetResourceLib.fn.isArray(user_type_ids) ? user_type_ids : [user_type_ids];
								
								//check if resources exist and are valid
								var is_valid_resource = true; //true in case there are no resources defined
								var is_valid_value = values.length ? false : true; //true in case there are no values defined, otherwise is false
								var is_valid_permission = true; //true in case there are no permissions defined
								var there_is_a_valid_resource = false;
								var validate_resource_later = false;
								
								for (var i = 0, t = resources.length; i < t; i++) {
									var resource = resources[i];
									
									//if is string
									if (!MyWidgetResourceLib.fn.isPlainObject(resource))
										resource = {name: resource};
									
									//if resource name exists and was already executed and is true, then we get the correct node permissions, so we can filter it later... Otherwise we stop this process for the correspondent permission.
									if (resource["name"] || MyWidgetResourceLib.fn.isNumeric(resource["name"])) {
										var resource_name = resource["name"];
										
										//if resource was not yet loaded, get it from server.
										if (!MyWidgetResourceLib.ResourceHandler.existLoadedSLAResult(resource_name)) {
											MyWidgetResourceLib.ResourceHandler.executeResourcesRequest(elm, [ resource ], {
												async: false, //must be async false so the following code gets executed after.
											});
										}
										
										//if resource name exists but was not yet executed (maybe the server gave some error...)
										if (!MyWidgetResourceLib.ResourceHandler.existLoadedSLAResult(resource_name))
											validate_resource_later = true;
										//if resource name exists and was already executed and is true, sets the is_valid_resource to true.
										else if (MyWidgetResourceLib.ResourceHandler.getLoadedSLAResult(resource_name)) {
											is_valid_resource = true; //in case some previous resource set this var to false
											there_is_a_valid_resource = true;
											break;
										}
										//if there isn't any resource valid yet, sets the is_valid_resource to false.
										else if (!there_is_a_valid_resource)
											is_valid_resource = false;
									}
								}
								
								//if there is a resource that was not yet executed and there are no other valid resources, then stop this process, so we can filter it later... Additionally reset the show var to true, so we can parse it later too...
								if (validate_resource_later && !there_is_a_valid_resource) {
									is_valid_resource = false;
									
									if (k == "access" || k == "view" || k == "show")
										show = true;
								}
								
								//check values, but only if resource is valid, otherwise stops this process for the correspondent permission and set the correspondent default value.
								if (is_valid_resource)
									for (var i = 0, t = values.length; i < t; i++) {
										var value = values[i];
										
										if (MyWidgetResourceLib.fn.isNumeric(value) && (value === 0 || value === "0"))
											value = false;
										
										if (value) {
											is_valid_value = true;
											break;
										}
									}
								
								//check user types, but only if resource and value are valid, otherwise stops this process for the correspondent permission and set the correspondent default value.
								if (is_valid_resource && is_valid_value) {
									for (var i = 0, t = user_type_ids.length; i < t; i++) {
										var user_type_id = user_type_ids[i];
										
										if (MyWidgetResourceLib.fn.isNumeric(user_type_id)) {
											var is_public_user_permission = !is_logged && (user_type_id === 0 || user_type_id === "0" || user_type_id == public_user_type_id);
											var is_logged_user_permission = is_logged && logged_user_type_ids.indexOf(user_type_id) != -1;
											
											if (is_public_user_permission || is_logged_user_permission) {
												is_valid_permission = true; //in case some previous resource set this var to false
												break;
											}
											else
												is_valid_permission = false;
										}
									}
								}
								
								if (is_valid_resource && is_valid_value && is_valid_permission) {
									if (k == "access" || k == "view" || k == "show")
										show = true;
									else if (k == "hide")
										hide = true;
									else if (k == "remove")
										remove = true;
								}
							}
						}
					}
					
					if (hide || remove)
						show = false;
					
					if (!show)
						hide = true;
				}
			}
			
			return {
				show: show,
				hide: hide,
				remove: remove,
			};
		},
	});
	/****************************************************************************************
	 *				 END: PERMISSION HANDLER 					*
	 ****************************************************************************************/
		
	/****************************************************************************************
	 *				 START: SEARCH HANDLER 					*
	 ****************************************************************************************/
	MyWidgetResourceLib.SearchHandler = MyWidgetResourceLib.fn.SearchHandler = ({
		
		timeout_id: null,
		search_activated: true,
		
		//Reset search.
		//This function should be called inside of a [data-widget-search] widget.
		resetSearchWidget: function(elm, force) {
			var p = elm.parentNode.closest("[data-widget-search]");
			
			if (p) {
				var selects = p.querySelectorAll("[data-widget-search-select]");
				
				if (selects)
					MyWidgetResourceLib.fn.each(selects, function(idx, select) {
						MyWidgetResourceLib.SearchHandler.resetSearchWidgetSelect(select);
					});
				
				var inputs = p.querySelectorAll("[data-widget-search-input]");
				
				if (inputs)
					MyWidgetResourceLib.fn.each(inputs, function(idx, input) {
						MyWidgetResourceLib.SearchHandler.resetSearchWidgetInput(input);
					});
				
				var btns = p.querySelectorAll("[data-widget-search-multiple]");
				
				if (btns)
					MyWidgetResourceLib.fn.each(btns, function(idx, btn) {
						MyWidgetResourceLib.SearchHandler.resetSearchWidgetMultipleFields(btn.children()[0]);
					});
				
				if (selects.length > 0)
					MyWidgetResourceLib.SearchHandler.refreshSearchWidgetThroughSelect(selects[0], force);
				else if (inputs.length > 0)
					MyWidgetResourceLib.SearchHandler.refreshSearchWidgetThroughInput(inputs[0], force);
				else if (btns.length > 0)
					MyWidgetResourceLib.SearchHandler.refreshSearchWidgetThroughMultipleField(btns[0].children()[0], force);
			}
		},
		
		//Refresh the data from the dependent widgets based in a multiple search fields with the selector: '[data-widget-search-multiple-button]'.
		//This function should be called inside of a search widget.
		refreshSearchWidget: function(elm, force) {
			if (!force && !MyWidgetResourceLib.SearchHandler.search_activated)
				return;
			
			if (MyWidgetResourceLib.SearchHandler.timeout_id)
				clearTimeout(MyWidgetResourceLib.SearchHandler.timeout_id);
			
			if (elm) {
				var p = elm.parentNode.closest("[data-widget-search]");
				
				if (p) {
					//check if value is different
					var input = elm;
					var node_name = input.nodeName;
					
					if (elm.hasAttribute("data-widget-search-input") && node_name != "INPUT") {
						input = elm.querySelector("input");
						node_name = input ? input.nodeName : null;
					}
					else if (elm.hasAttribute("data-widget-search-select") && node_name != "SELECT") {
						input = elm.querySelector("select");
						node_name = input ? input.nodeName : null;
					}
					
					if (input && ((node_name == "INPUT" && input.type != "checkbox" && input.type != "radio") || node_name == "SELECT" || node_name == "TEXTAREA")) {
						var value = MyWidgetResourceLib.FieldHandler.getInputValue(input);
						var saved_value = MyWidgetResourceLib.fn.getNodeElementData(input, "saved_value");
						
						//if value is the same don't search again nut only if not a checkbox or a radio
						if (!force && value == saved_value)
							return;
						
						MyWidgetResourceLib.fn.setNodeElementData(input, "saved_value", value);
					}
					
					//avoids multiple searches when executing the onBlur and keyUp events.
					/*if (!force && (node_name == "INPUT" || node_name == "TEXTAREA")) {
						MyWidgetResourceLib.SearchHandler.search_activated = false;
						
						setTimeout(function() {
							MyWidgetResourceLib.SearchHandler.search_activated = true;
						}, 2000);
					}*/
					
					var elm_properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
					var parent_properties = MyWidgetResourceLib.fn.getWidgetProperties(p);
					var default_search_type = elm_properties["search_type"] ? elm_properties["search_type"] : "contains";
					var default_search_case = elm_properties.hasOwnProperty("search_case") ? elm_properties["search_case"] : null;
					var default_search_operator = elm_properties.hasOwnProperty("search_operator") ? elm_properties["search_operator"] : null;
					var search_types = {};
					var search_cases = {};
					var search_operators = {};
					var search_attrs = {};
					
					var fields = p.querySelectorAll("input, textarea, select");
					
					if (fields)
						for (var i = 0, ti = fields.length; i < ti; i++) {
							var field = fields[i];
							var field_properties = MyWidgetResourceLib.fn.getWidgetProperties(field);
							var attribute_names = field_properties["search_attrs"];
							var attribute_type = field_properties["search_type"];
							var attribute_case = field_properties["search_case"];
							var attribute_operator = field_properties["search_operator"];
							
							var search_field = field.closest("[data-widget-search-input], [data-widget-search-select], [data-widget-search-multiple-field]");
							var search_field_properties = search_field ? MyWidgetResourceLib.fn.getWidgetProperties(search_field) : {};
							var is_multiple_search = (search_field && search_field.hasAttribute("data-widget-search-multiple-field")) || elm.hasAttribute("data-widget-search-multiple-button");
							
							if (!attribute_names)
								attribute_names = search_field_properties["search_attrs"];
							
							if (!attribute_type)
								attribute_type = search_field_properties["search_type"];
							
							if (!attribute_type)
								attribute_type = is_multiple_search ? default_search_type : "contains";
							
							if (!attribute_case)
								attribute_case = search_field_properties["search_case"];
							
							if (!attribute_case)
								attribute_case = is_multiple_search ? default_search_case : null;
							
							if (!attribute_operator)
								attribute_operator = search_field_properties["search_operator"];
							
							if (!attribute_operator)
								attribute_operator = is_multiple_search ? default_search_operator : null;
							
							if (attribute_names) {
								var parts = MyWidgetResourceLib.fn.isArray(attribute_names) ? attribute_names : attribute_names.replace(/;/g, ",").split(",");
								var value = MyWidgetResourceLib.FieldHandler.getFieldValue(field, {
									with_available_values: true,
									with_default: true,
								});
								
								if (parts && (value || MyWidgetResourceLib.fn.isNumeric(value)))
									for (var j = 0, tj = parts.length; j < tj; j++) {
										var attr_name = parts[j].replace(/\s+/g, "");
										
										if (attr_name) {
											search_types[attr_name] = attribute_type;
											search_cases[attr_name] = attribute_case;
											search_operators[attr_name] = attribute_operator;
											search_attrs[attr_name] = value;
										}
									}
							}
						}
					
					var search_props = {
						search_attrs: search_attrs,
						search_types: search_types,
						search_cases: search_cases,
						search_operators: search_operators,
					};
					//console.log(search_props);
					
					//execute search based in the search_attrs var
					/*
					 * search_attrs: {attribute_name: string to search}
					 * search_types: contains|starts_with|ends_with or {attribute_name: contains|starts_with|ends_with} (default: contains)
					 * search_cases: sensitive|insensitive (default: sensitive)
					 * search_operators: or|and (default: and)
					 */
					var dependent_widgets_id = MyWidgetResourceLib.fn.prepareDependentWidgetsId(parent_properties["dependent_widgets_id"]);
					dependent_widgets_id = dependent_widgets_id.concat(MyWidgetResourceLib.fn.prepareDependentWidgetsId(elm_properties["dependent_widgets_id"]));
					
					MyWidgetResourceLib.fn.loadDependentWidgetsById(dependent_widgets_id, search_props, {
						force: force //search should not have force, bc it should get the elements from cache unless if the force is active
					});
				}
			}
		},
		
		/* SEARCH WITH SELECT FUNCTIONS */
		
		resetSearchWidgetSelect: function(elm, force) {
			elm = elm.closest("[data-widget-search-select]");
			
			if (elm) {
				var select = elm.nodeName == "SELECT" ? elm : elm.querySelector("select");
				
				if (select)
					select.value = "";
				else
					MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No search select box in resetSearchWidgetThroughSelect method. Please inform the web-developer accordingly...");
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No search select box in resetSearchWidgetThroughSelect method. Please inform the web-developer accordingly...");
		},
		
		//Clean search select field and reload the data from the dependent widgets.
		//This function should be called inside of a select with the selector: '[data-widget-search-select]', which is inside of a search widget.
		resetSearchWidgetThroughSelect: function(elm, force) {
			MyWidgetResourceLib.SearchHandler.resetSearchWidgetSelect(elm);
			MyWidgetResourceLib.SearchHandler.refreshSearchWidgetThroughSelect(elm, force);
		},
		
		//Refresh the data from the dependent widgets based in a search select field with the selector: '[data-widget-search-select]'.
		//This function should be called inside of a search widget.
		refreshSearchWidgetThroughSelect: function(elm, force) {
			elm = elm.closest("[data-widget-search-select]");
			
			if (elm)
				MyWidgetResourceLib.SearchHandler.refreshSearchWidget(elm, force);
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No search select box in refreshSearchWidgetThroughSelect method. Please inform the web-developer accordingly...");
		},
		
		/* SEARCH WITH INPUT FUNCTIONS */
		
		resetSearchWidgetInput: function(elm) {
			elm = elm.closest("[data-widget-search-input]");
			
			if (elm) {
				var input = elm.nodeName == "INPUT" ? elm : elm.querySelector("input");
				
				if (input) {
					if (input.type == "checkbox" || input.type == "radio")
						input.checked = input.value == "" ? true : false;
					else
						input.value = "";
				}
				else
					MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No search input in resetSearchWidgetThroughInput method. Please inform the web-developer accordingly...");
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No search input in resetSearchWidgetThroughInput method. Please inform the web-developer accordingly...");
		},
		
		//Clean search input field and reload the data from the dependent widgets.
		//This function should be called inside of an input with the selector: '[data-widget-search-input]', which is inside of a search widget.
		resetSearchWidgetThroughInput: function(elm, force) {
			MyWidgetResourceLib.SearchHandler.resetSearchWidgetInput(elm);
			MyWidgetResourceLib.SearchHandler.refreshSearchWidgetThroughInput(elm, force);
		},
		
		//Refresh the data from the dependent widgets based in a search input field with the selector: '[data-widget-search-input]'.
		//This function should be called inside of a search widget.
		refreshSearchWidgetThroughInput: function(elm, force) {
			elm = elm.closest("[data-widget-search-input]");
			
			if (elm)
				MyWidgetResourceLib.SearchHandler.refreshSearchWidget(elm, force);
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No search input in refreshSearchWidgetThroughInput method. Please inform the web-developer accordingly...");
		},
		
		//Refresh the data on key up, from the dependent widgets based in a search input field with the selector: '[data-widget-search-input]'.
		//This function should be called inside of a search widget.
		onKeyUpSearchWidgetThroughInput: function(elm, secs_to_wait, force) {
			secs_to_wait = parseInt(secs_to_wait);
			
			if (secs_to_wait > 0) {
				if (MyWidgetResourceLib.SearchHandler.timeout_id)
					clearTimeout(MyWidgetResourceLib.SearchHandler.timeout_id);
				
				MyWidgetResourceLib.SearchHandler.timeout_id = setTimeout(function() {
					MyWidgetResourceLib.SearchHandler.refreshSearchWidgetThroughInput(elm, force);
				}, secs_to_wait * 1000);
			}
			else
				MyWidgetResourceLib.SearchHandler.refreshSearchWidgetThroughInput(elm, force);
		},
		
		/* SEARCH WITH MULTIPLE FIELD FUNCTIONS */
		
		resetSearchWidgetMultipleFields: function(elm) {
			var btn = elm.hasAttribute("data-widget-search-multiple-field") ? elm.parentNode.closest("[data-widget-search-multiple]").querySelector("[data-widget-search-multiple-button]") : elm;
			
			if (btn) {
				var p = btn.parentNode.closest("[data-widget-search-multiple]");
				var attrs = p ? p.querySelector("[data-widget-search-added-attrs]") : null;
				
				if (attrs) {
					var lis = attrs.querySelectorAll("li");
					
					if (lis)
						MyWidgetResourceLib.fn.each(lis, function(idx, li) {
					   		if (li.hasAttribute("data-widget-search-added-attrs-empty"))
					   			MyWidgetResourceLib.fn.show(li);
					   		else if (!li.hasAttribute("data-widget-search-added-attrs-item"))
					   			li.parentNode.removeChild(li);
					   	});
				}
				else
					MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No search multiple field in resetMultipleFields method. Please inform the web-developer accordingly...");
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No search multiple field in resetMultipleFields method. Please inform the web-developer accordingly...");
		},
		
		//Reset search created dynamically by the user.
		//This function should be called inside of a [data-widget-search-multiple] widget.
		resetSearchWidgetThroughMultipleField: function(elm, force) {
			MyWidgetResourceLib.SearchHandler.resetSearchWidgetMultipleFields(elm);
			MyWidgetResourceLib.SearchHandler.refreshSearchWidgetThroughMultipleField(elm, force);
		},
		
		refreshSearchWidgetThroughMultipleField: function(elm, force) {
			var btn = elm.hasAttribute("data-widget-search-multiple-field") ? elm.parentNode.closest("[data-widget-search-multiple]").querySelector("[data-widget-search-multiple-button]") : elm;
			
			if (btn)
				MyWidgetResourceLib.SearchHandler.refreshSearchWidget(btn, force);
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No search multiple field in refreshSearchWidgetThroughMultipleField method. Please inform the web-developer accordingly...");
		},
		
		onKeyUpSearchWidgetThroughMultipleField: function(elm, secs_to_wait, force) {
			secs_to_wait = parseInt(secs_to_wait);
			
			if (secs_to_wait > 0) {
				if (MyWidgetResourceLib.SearchHandler.timeout_id)
					clearTimeout(MyWidgetResourceLib.SearchHandler.timeout_id);
				
				MyWidgetResourceLib.SearchHandler.timeout_id = setTimeout(function() {
					MyWidgetResourceLib.SearchHandler.refreshSearchWidgetThroughMultipleField(elm, force);
				}, secs_to_wait * 1000);
			}
			else
				MyWidgetResourceLib.SearchHandler.refreshSearchWidgetThroughMultipleField(elm, force);
		},
		
		//Add an attribute field so the user can search.
		//This function should be called inside of a [data-widget-search-multiple] widget.
		addWidgetSearchDynamicAttribute: function(elm, secs_to_wait) {
			if (elm) {
				var p = elm.parentNode.closest("[data-widget-search-multiple]");
				var attrs = p ? p.querySelector("[data-widget-search-added-attrs]") : null;
				var select = p ? p.querySelector("select") : null;
				var attr_name = select ? MyWidgetResourceLib.FieldHandler.getInputValue(select) : null;
				
				if (attr_name && attrs) {
					var empty_li = attrs.querySelector("[data-widget-search-added-attrs-empty]");
					
					if (empty_li)
						MyWidgetResourceLib.fn.hide(empty_li);
					
					var item = attrs.querySelector("[data-widget-search-added-attrs-item]");
					var html = "";
					
					if (item) {
						html = item.outerHTML;
						html = html.replace(/("|'|\s)data-widget-search-added-attrs-item(\s*=\s*""|\s*=\s*'')?(\s|>)/g, "$3"); //remove data-widget-search-added-attrs-item attribute
						
						MyWidgetResourceLib.fn.setNodeElementData(attrs, "attr_item_html", html);
						item.parentNode.removeChild(item);
					}
					else
						html = MyWidgetResourceLib.fn.getNodeElementData(attrs, "attr_item_html");
					
					if (!html)
						html = '<li class="list-group-item border-0">'
								+ '<div class="input-group">'
									+ '<label class="input-group-text">#widget_search_attr_name#: </label>'
									+ '<input class="form-control" data-widget-search-multiple-field onKeyUp="MyWidgetResourceLib.SearchHandler.onKeyUpSearchWidgetThroughMultipleField(this, #widget_search_secs_to_wait#)" onBlur="MyWidgetResourceLib.SearchHandler.refreshSearchWidgetThroughMultipleField(this)" data-widget-props="{&quot;search_attrs&quot;:&quot;#widget_search_attr_name#&quot;}" />'
									+ '<button class="btn btn-sm btn-outline-danger text-nowrap" onClick="MyWidgetResourceLib.SearchHandler.removeWidgetSearchDynamicAttribute(this)" title="Remove"><i class="bi bi-trash icon icon-remove mr-1 me-1 overflow-visible"></i>Remove</button>'
								+ '</div>'
							+ '</li>';
					
					if (!MyWidgetResourceLib.fn.isNumeric(secs_to_wait))
						secs_to_wait = 1;
					
					html = html.replace(/#widget_search_attr_name#/g, attr_name);
					html = html.replace(/#widget_search_secs_to_wait#/g, secs_to_wait);
					
					attrs.insertAdjacentHTML('beforeend', html);
				}
			}
		},
		
		removeWidgetSearchDynamicAttribute: function(elm) {
			var li = elm.closest("li");
			var p = li ? li.parentNode : null;
			
			if (p) {
				p.removeChild(li);
				var lis = p.querySelectorAll("li:not([data-widget-search-added-attrs-empty]):not([data-widget-search-added-attrs-item])");
				
				if (!lis || lis.length == 0) {
					var empty_li = p.querySelector("[data-widget-search-added-attrs-empty]");
					
					if (empty_li)
						MyWidgetResourceLib.fn.show(empty_li);	
				}
				
				var btn = p.closest("[data-widget-search-multiple]").querySelector("[data-widget-search-multiple-button]");
				
				if (btn) {
					MyWidgetResourceLib.SearchHandler.search_activated = true;
					MyWidgetResourceLib.SearchHandler.refreshSearchWidget(btn);
				}
			}
		},
	});
	/****************************************************************************************
	 *				 END: SEARCH HANDLER 					*
	 ****************************************************************************************/
		
	/****************************************************************************************
	 *				 START: PAGINATION HANDLER 					*
	 ****************************************************************************************/
	MyWidgetResourceLib.PaginationHandler = MyWidgetResourceLib.fn.PaginationHandler = ({
		
		//Load data of a pagination widget.<br/>This function should be called by a pagination widget.
		loadPaginationResource: function(elm, opts) {
			if (elm) {
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var perms = MyWidgetResourceLib.PermissionHandler.getWidgetPermissions(elm);
				
				if (!perms["remove"]) {
					var end_handler = MyWidgetResourceLib.fn.isPlainObject(properties["end"]) ? properties["end"]["load"] : properties["end"];
					end_handler = MyWidgetResourceLib.fn.convertIntoFunctions(end_handler);
					
					//reload pagination based in the resource.
					if (elm.hasAttribute("data-widget-resources")) {
						var saved_search_attrs = MyWidgetResourceLib.fn.getNodeElementData(elm, "saved_search_attrs");
						var search_attrs = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_attrs");
						var search_types = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_types");
						var search_cases = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_cases");
						var search_operators = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_operators");
						
						search_attrs = typeof search_attrs == "string" && search_attrs[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_attrs) : search_attrs;
						search_types = typeof search_types == "string" && search_types[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_types) : search_types;
						search_cases = typeof search_cases == "string" && search_cases[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_cases) : search_cases;
						search_operators = typeof search_operators == "string" && search_operators[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_operators) : search_operators;
						
						MyWidgetResourceLib.fn.setNodeElementData(elm, "saved_search_attrs", search_attrs);
						
						var complete_func = function(resource_elm, resources, resources_name, resources_cache_key) {
							MyWidgetResourceLib.PaginationHandler.drawPagination(elm, resources_name, resources_cache_key, search_attrs != saved_search_attrs);
							
							//call complete handler
							var complete_handler = MyWidgetResourceLib.fn.isPlainObject(properties["complete"]) ? properties["complete"]["load"] : properties["complete"];
							complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
							
							if (complete_handler)
								MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
							
							//call complete handler from opts if exists
							var complete_handler = opts && opts["complete"] ? opts["complete"] : null;
							complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
							
							if (complete_handler)
								MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
							
							//reload dependent widgets like the pagination elements, if apply
							MyWidgetResourceLib.fn.loadDependentWidgetsByIdWithoutResourcesToLoad(properties["dependent_widgets_id"], {}, opts);
							
							//call end handler
							if (end_handler)
								MyWidgetResourceLib.fn.executeFunctions(end_handler, elm, resources_name, resources_cache_key);
						};
						
						var extra_url = opts && opts["extra_url"] ? opts["extra_url"] : "";
						var ignore_resource_conditions = false;
						
						if (MyWidgetResourceLib.fn.isPlainObject(search_attrs) && !MyWidgetResourceLib.fn.isEmptyObject(search_attrs)) {
							extra_url += "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_attrs, "search_attrs") 
								+ (search_types ? "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_types, "search_types") : "") 
								+ (search_cases ? "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_cases, "search_cases") : "") 
								+ (search_operators ? "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_operators, "search_operators") : "");
							ignore_resource_conditions = true;
						}
						
						var resource_opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? MyWidgetResourceLib.fn.assignObjectRecursively({}, opts) : {};
						resource_opts["ignore_resource_conditions"] = ignore_resource_conditions; //if search_attrs exists, ignore conditions
						resource_opts["extra_url"] = extra_url;
						resource_opts["label"] = "pagination";
						resource_opts["complete"] = complete_func;
						var resource = MyWidgetResourceLib.ResourceHandler.executeGetWidgetResourcesRequest(elm, "load", resource_opts);
						
						if (resource)
							return true;
						else
							MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No pagination load resource set in loadPaginationResource. Please inform the web-developer accordingly...");
					}
					
					if (!resource) {
						if (end_handler)
							MyWidgetResourceLib.fn.executeFunctions(end_handler, elm);
						
						//reload dependent widgets like the pagination elements, if apply
						MyWidgetResourceLib.fn.loadDependentWidgetsByIdWithoutResourcesToLoad(properties["dependent_widgets_id"], {}, opts);
					}
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No pagination element in loadPaginationResource method!");
			
			return false;
		},
		
		drawPagination: function(elm, resources_name, resources_cache_key, is_new_search) {
			if (elm) {
				var items_total = 0;
				
				for (var i = 0, ti = resources_name.length; i < ti; i++) {
					var resource_name = resources_name[i];
					var resource_cache_key = resources_cache_key ? resources_cache_key[i] : null;
					items_total += parseInt(MyWidgetResourceLib.ResourceHandler.getLoadedSLAResult(resource_name, resource_cache_key));
				}
				
				var page_number = MyWidgetResourceLib.fn.getNodeElementData(elm, "page_number"); //if pagination is reloaded, we should show the same pagination number
				var aux = MyWidgetResourceLib.PaginationHandler.getItemsLimitPerPageAndStartingPage(elm);
				var items_limit_per_page = aux["items_limit_per_page"];
				var starting_page_number = is_new_search ? 0 : (page_number > 0 ? page_number : aux["starting_page_number"]);
				
				this.drawStaticPagination(elm, items_total, items_limit_per_page, starting_page_number);
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No pagination element in drawPagination method!");
		},
		
		drawStaticPagination: function(elm, items_total, items_limit_per_page, page_number) {
			MyWidgetResourceLib.fn.setNodeElementData(elm, "items_total", items_total);
			
			var aux = this.getItemsLimitPerPageAndStartingPage(elm);
			var items_limit_per_page = items_limit_per_page >= 0 ? items_limit_per_page : aux["items_limit_per_page"];
			var num_pages = items_total > 0 && items_limit_per_page > 0 ? Math.ceil(items_total / items_limit_per_page) : 0;
			MyWidgetResourceLib.fn.setNodeElementData(elm, "num_pages", num_pages);
			
			if (num_pages > 1) { //only create pagination if more than 1 page
				page_number = this.getPageNumberConfigured(elm, page_number);
				
				//prepare dropdown
				var dropdown = elm.querySelector("[data-widget-pagination-go-to-page-dropdown]");
				
				if (dropdown) {
					var properties = MyWidgetResourceLib.fn.getWidgetProperties(dropdown);
					var load_func = MyWidgetResourceLib.fn.convertIntoFunctions(properties["load"]);
					
					if (load_func)
						MyWidgetResourceLib.fn.executeFunctions(load_func, dropdown, num_pages);
					else 
						this.loadDropdownPages(dropdown, num_pages);
				}
				
				//get default page items html
				if (!MyWidgetResourceLib.fn.existsNodeElementData(elm, "pages_numbers_items_html")) {
					var items = elm.querySelectorAll("[data-widget-pagination-pages-numbers-item]");
					var pages_numbers_items_html = new Array();
					
					if (items)
						for (var i = 0, t = items.length; i < t; i++) {
							var item = items[i];
							var item_original_html = item.outerHTML;
							
							//check if item needs to be inited (this is, if contains any attribute data-widget-item-resources-load) and if yes, inited that html, but before anything else...
							if (!item.classList.contains("template-widget") && (item.hasAttribute("data-widget-item-resources-load") || item.hasAttribute("data-widget-resources-load"))) {
								item.removeAttribute("data-widget-item-resources-load");
								item.removeAttribute("data-widget-resources-load");
								
								MyWidgetResourceLib.ResourceHandler.loadWidgetResource(item, {async: false}); //must be async false otherwise we will loose the the real pagination values, when the load function gets executed
							}
							
							//save default item html
							item.setAttribute("data-widget-pagination-pages-numbers-item-loaded", "");
							var item_new_html = item.outerHTML;
							var item_properties = MyWidgetResourceLib.fn.getWidgetProperties(item);
							
							pages_numbers_items_html.push({
								html: item_new_html,
								original_html: item_original_html,
								parent: item.parentNode,
								load: MyWidgetResourceLib.fn.convertIntoFunctions(item_properties["load"]),
							});
							
							item.parentNode.removeChild(item); //remove default item
						}
					
					MyWidgetResourceLib.fn.setNodeElementData(elm, "pages_numbers_items_html", pages_numbers_items_html);
				}
				
				//create page items
				this.drawStaticPaginationPagesNumbersItems(elm, num_pages, page_number);
				
				//select page number
				this.setPage(elm, page_number);
				
				//show pagination
				MyWidgetResourceLib.fn.show(elm);
			}
			else
				MyWidgetResourceLib.fn.hide(elm);
		},
		
		drawStaticPaginationPagesNumbersItems: function(elm, num_pages, page_number) {
			var pages_numbers_items_html = MyWidgetResourceLib.fn.getNodeElementData(elm, "pages_numbers_items_html");
			
			if (pages_numbers_items_html && num_pages > 1) { //only create pagination if more than 1 page
				//remove old pages items
				for (var i = 0, t = pages_numbers_items_html.length; i < t; i++) {
					var pages_numbers_item_html = pages_numbers_items_html[i];
					var item_parent = pages_numbers_item_html.parent;
					
					if (item_parent) {
						var items = item_parent.querySelectorAll("[data-widget-pagination-pages-numbers-item]");
						
						if (items)
							for (var i = 0, t = items.length; i < t; i++) {
								var item = items[i];
								var item_p = item.parentNode;
								
								item_p.removeChild(item);
							}
					}
				}
				
				//prepare new pages items
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var number_of_pages_to_show_at_once = parseInt(properties["number_of_pages_to_show_at_once"]);
				
				//prepare pages to show
				var start_index = 1;
				var end_index = num_pages;
				
				if (number_of_pages_to_show_at_once >= 0) {
					var diff = Math.floor(number_of_pages_to_show_at_once / 2);
					start_index = page_number - diff;
					end_index = page_number + diff;
					
					if (start_index < 1)
						end_index += Math.abs(start_index) + 1;
					
					if (end_index > num_pages)
						start_index = start_index - (end_index - num_pages);
					
					if (start_index < 1)
						start_index = 1;
					
					if (end_index > num_pages)
						end_index = num_pages;
				}
				//console.log("start_index:"+start_index+"|end_index:"+end_index);
				
				//create page items html
				for (var i = start_index; i <= end_index; i++) {
					for (var j = 0, tj = pages_numbers_items_html.length; j < tj; j++) {
						var pages_numbers_item_html = pages_numbers_items_html[j];
						var item_html = pages_numbers_item_html.html;
						var item_parent = pages_numbers_item_html.parent;
						
						if (item_parent) {
							var item_load = pages_numbers_item_html.load;
							
							//append html element to item_parent
							item_parent.insertAdjacentHTML('beforeend', item_html);
							var item_elm = item_parent.lastElementChild;
							
							if (i == page_number)
								item_elm.setAttribute("selected", "");
							
							var item_value_elm = item_elm.hasAttribute("data-widget-pagination-pages-numbers-item-value") ? item_elm : item_elm.querySelector("[data-widget-pagination-pages-numbers-item-value]");
							item_value_elm = item_value_elm ? item_value_elm : item_elm; //by default sets item to the [data-widget-pagination-pages-numbers-item]
							item_value_elm.setAttribute("data-widget-pagination-pages-numbers-item-value", i);
							
							if (item_load)
								MyWidgetResourceLib.fn.executeFunctions(item_load, item_value_elm, i);
							else 
								this.loadItemPage(item_value_elm, i);
						}
					}
				}
			}
		},
		
		//simple select the correspondent page_number in the pagination html
		setPage: function(elm, page_number) {
			var pagination_elm = elm ? elm.closest("[data-widget-pagination]") : null;
			
			if (pagination_elm) {
				page_number = this.getPageNumberConfigured(pagination_elm, page_number);
				MyWidgetResourceLib.fn.setNodeElementData(pagination_elm, "page_number", page_number);
				
				//prepare dropdown
				var dropdown = pagination_elm.querySelector("[data-widget-pagination-go-to-page-dropdown]");
				
				if (dropdown) {
					var properties = MyWidgetResourceLib.fn.getWidgetProperties(dropdown);
					var set_func = MyWidgetResourceLib.fn.convertIntoFunctions(properties["set"]);
					
					if (set_func)
						MyWidgetResourceLib.fn.executeFunctions(set_func, dropdown, page_number);
					else 
						this.setDropdownSelectedPageNumber(dropdown, page_number);
				}
				
				//prepare pages items
				var num_pages = MyWidgetResourceLib.fn.getNodeElementData(pagination_elm, "num_pages");
				var first_page = pagination_elm.querySelector("[data-widget-pagination-pages-first]");
				var previous_page = pagination_elm.querySelector("[data-widget-pagination-pages-previous]");
				var next_page = pagination_elm.querySelector("[data-widget-pagination-pages-next]");
				var last_page = pagination_elm.querySelector("[data-widget-pagination-pages-last]");
				var item_pages = pagination_elm.querySelectorAll("[data-widget-pagination-pages-numbers-item]");
				
				num_pages = num_pages > 1 ? num_pages : 1;
				
				if (item_pages) {
					var draw_new_pagination_items = false;
					var page_found = false;
					
					for (var i = 0, t = item_pages.length; i < t; i++) {
						var item_page = item_pages[i];
						var item_page_value = item_page.hasAttribute("data-widget-pagination-pages-numbers-item-value") ? item_page : item_page.querySelector("[data-widget-pagination-pages-numbers-item-value]");
						item_page_value = item_page_value ? item_page_value : item_page; //by default sets item to the [data-widget-pagination-pages-numbers-item]
						
						var item_page_number = parseInt(item_page_value.getAttribute("data-widget-pagination-pages-numbers-item-value"));
						
						if (item_page_number == page_number) {
							item_page.setAttribute("selected", "");
							page_found = true;
							
							//if not the first or the last page number item
							if (i == 0 || i == t - 1)
								draw_new_pagination_items = true;
						}
						else
							item_page.removeAttribute("selected");
					}
					
					if (draw_new_pagination_items || !page_found) {
						//draw new page items
						this.drawStaticPaginationPagesNumbersItems(pagination_elm, num_pages, page_number);
					}
				}
				
				if (page_number == 1) {
					first_page && MyWidgetResourceLib.fn.hide(first_page);
					previous_page && MyWidgetResourceLib.fn.hide(previous_page);
				}
				else {
					first_page && MyWidgetResourceLib.fn.show(first_page);
					previous_page && MyWidgetResourceLib.fn.show(previous_page);
				}
				
				if (page_number >= num_pages) {
					last_page && MyWidgetResourceLib.fn.hide(last_page);
					next_page && MyWidgetResourceLib.fn.hide(next_page);
				}
				else {
					last_page && MyWidgetResourceLib.fn.show(last_page);
					next_page && MyWidgetResourceLib.fn.show(next_page);
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No pagination element in setPage method!");
		},
		
		goToPage: function(elm, page_number, force) {
			var pagination_elm = elm ? elm.closest("[data-widget-pagination]") : null;
			
			if (pagination_elm) {
				page_number = this.getPageNumberConfigured(pagination_elm, page_number);
				
				//show page number
				this.setPage(pagination_elm, page_number);
				
				//prepare dependencies
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var pagination_properties = MyWidgetResourceLib.fn.getWidgetProperties(pagination_elm);
				
				var dependent_widgets_id = MyWidgetResourceLib.fn.prepareDependentWidgetsId(pagination_properties["dependent_widgets_id"]);
				dependent_widgets_id = dependent_widgets_id.concat(MyWidgetResourceLib.fn.prepareDependentWidgetsId(properties["dependent_widgets_id"]));
				
				//set scroll to top of the first widget dependency
				for (var i = 0, t = dependent_widgets_id.length; i < t; i++) {
					var dependent_widget_id = ("" + dependent_widgets_id[i]).replace(/(^\s+|\s+$)/g, ""); //trim. Note that the dependent_widget_id maybe a composite selector, so do not replace white spaces between words.
					
					if (dependent_widget_id.length > 0) {
						var widget = document.querySelector("#" + dependent_widget_id);
						
						if (widget) {
							var scroll_x = widget.offsetLeft - 20; //-20 just bc it looks better
							var scroll_y = widget.offsetTop - 20;
							
							try {
								window.scrollTo({
									top: scroll_y, 
									left: scroll_x, 
									behavior: "instant" //very important, otherwise the scroll takes time and meanwhile the dependent_widgets_id get loaded and get a different scroll, which will make the user experience weird.
								});
							}
							catch (e) {
								window.scrollTo(scroll_x, scroll_y);
							}
							break;
						}
					}
				}
				
				//load dependencies
				MyWidgetResourceLib.fn.loadDependentWidgetsById(dependent_widgets_id, {
					page_number: page_number,
				}, {
					force: force //pagination should not be force by default, bc it should get the elements from cache, unless the user forces it to be.
				});
				
				//update page_number in other pagination widgets from the same group
				var repeated_paginations = [pagination_elm];
				
				MyWidgetResourceLib.fn.each(dependent_widgets_id, function(idx, dependent_widget_id) {
					var widgets = MyWidgetResourceLib.fn.getDependentWidgetsById(dependent_widget_id);
					
					if (widgets)
						MyWidgetResourceLib.fn.each(widgets, function(idy, widget) {
							if (widget) {
								var widget_pagination_elms = MyWidgetResourceLib.ListHandler.getPaginationWidgets(widget);
								
								if (widget_pagination_elms)
									MyWidgetResourceLib.fn.each(widget_pagination_elms, function(idy, widget_pagination_elm) {
										if (repeated_paginations.indexOf(widget_pagination_elm) == -1) {
											repeated_paginations.push(widget_pagination_elm);
											
											MyWidgetResourceLib.PaginationHandler.setPage(widget_pagination_elm, page_number);
										}
									});
							}
						});
				});
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No pagination element in goToPage method!");
		},
		
		//Reload the first page in the dependent widgets.
		//This function should be called inside of a pagination widget.
		goToFirstPage: function(elm) {
			if (elm)
				this.goToPage(elm, 1);
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No pagination sub-element in goToFirstPage method!");
		},
		
		//Reload the last page in the dependent widgets.
		//This function should be called inside of a pagination widget.
		goToLastPage: function(elm) {
			var pagination_elm = elm ? elm.closest("[data-widget-pagination]") : null;
			
			if (pagination_elm) {
				var num_pages = MyWidgetResourceLib.fn.getNodeElementData(pagination_elm, "num_pages");
				
				this.goToPage(elm, num_pages);
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No pagination element in goToLastPage method!");
		},
		
		//Reload the a specific page in the dependent widgets.
		//This function should be called inside of a pagination widget and in a html element with the attribute 'data-widget-pagination-pages-numbers-item-value' with the correspondent page number value.
		goToElementPage: function(elm) {
			if (elm) {
				var page_number = null;
				var node_name = elm.nodeName.toUpperCase();
				
				if (elm.hasAttribute("data-widget-pagination-pages-numbers-item")) {
					var item_page_value = elm.hasAttribute("data-widget-pagination-pages-numbers-item-value") ? elm : elm.querySelector("[data-widget-pagination-pages-numbers-item-value]");
					item_page_value = item_page_value ? item_page_value : elm; //by default sets item to the [data-widget-pagination-pages-numbers-item]
					page_number = item_page_value.getAttribute("data-widget-pagination-pages-numbers-item-value");
				}
				else if (node_name == "INPUT" || node_name == "SELECT" || node_name == "TEXTAREA")
					page_number = MyWidgetResourceLib.FieldHandler.getInputValue(elm);
				else
					page_number = elm.innerHTML;
				
				this.goToPage(elm, parseInt(page_number));
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No pagination sub-element in goToElementPage method!");
		},
		
		//Reload the previous page in the dependent widgets.
		//This function should be called inside of a pagination widget.
		goToPreviousPage: function(elm) {
			var pagination_elm = elm ? elm.closest("[data-widget-pagination]") : null;
			
			if (pagination_elm) {
				var page_number = MyWidgetResourceLib.fn.getNodeElementData(pagination_elm, "page_number");
				
				if (!MyWidgetResourceLib.fn.isNumeric(page_number))
					page_number = 0;
				
				this.goToPage(elm, page_number - 1);
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No pagination element in goToPreviousPage method!");
		},
		
		//Reload the next page in the dependent widgets.
		//This function should be called inside of a pagination widget.
		goToNextPage: function(elm) {
			var pagination_elm = elm ? elm.closest("[data-widget-pagination]") : null;
			
			if (pagination_elm) {
				var page_number = MyWidgetResourceLib.fn.getNodeElementData(pagination_elm, "page_number");
				
				if (!MyWidgetResourceLib.fn.isNumeric(page_number))
					page_number = 0;
				
				this.goToPage(elm, page_number + 1);
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No pagination element in goToNextPage method!");
		},
		
		//Get the selected page number and reload dependent widgets.
		//This function should be called inside of a pagination widget with a dropdown which contains the following selector: '[data-widget-pagination-go-to-page-dropdown]'.
		//If second argument is false, load the cached data from the dependent widgets.
		goToDropdownPage: function(elm, force) {
			if (elm) {
				var dropdown = elm;
				
				if (!dropdown.hasAttribute("data-widget-pagination-go-to-page-dropdown")) {
					var p = elm.closest("[data-widget-pagination-go-to-page], [data-widget-pagination]");
					dropdown = p ? p.querySelector("[data-widget-pagination-go-to-page-dropdown]") : null;
				}
				
				if (dropdown) {
					var properties = MyWidgetResourceLib.fn.getWidgetProperties(dropdown);
					var get_func = MyWidgetResourceLib.fn.convertIntoFunctions(properties["get"]);
					var page_number = get_func ? MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(get_func, dropdown) : this.getDropdownSelectedPageNumber(dropdown);
					
					this.goToPage(dropdown, page_number, force);
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No pagination sub-element in goToDropdownPage method!");
		},
		
		getDropdownSelectedPageNumber: function(elm) {
			var node_name = elm.nodeName.toUpperCase();
			
			return node_name == "INPUT" || node_name == "SELECT" || node_name == "TEXTAREA" ? MyWidgetResourceLib.FieldHandler.getInputValue(elm) : elm.innerHTML;
		},
		
		//Set page number in a pagination dropdown widget.
		//This function should be called by a [data-widget-pagination-go-to-page-dropdown] widget.
		setDropdownSelectedPageNumber: function(elm, page_number) {
			var node_name = elm.nodeName.toUpperCase();
			
			if (node_name == "INPUT" || node_name == "SELECT" || node_name == "TEXTAREA")
				elm.value = page_number;
			else
				elm.innerHTML = page_number;
		},
		
		//Load data of a pagination dropdown widget.
		//This function should be called by a [data-widget-pagination-go-to-page-dropdown] widget.
		loadDropdownPages: function(elm, num_pages) {
			var html = "";
			
			if (num_pages >= 1)
				for (var i = 1; i <= num_pages; i++)
					html += '<option value="' + i + '">' + i + '</option>';
			
			elm.innerHTML = html;
		},
		
		//Load data of a pagination item widget.
		//This function should be called by a [data-widget-pagination-pages-numbers-item] widget.
		loadItemPage: function(elm, page_number) {
			elm.innerHTML = page_number;
		},
		
		getPageNumberConfigured : function(elm, page_number) {
			var num_pages = MyWidgetResourceLib.fn.getNodeElementData(elm, "num_pages");
			num_pages = num_pages > 1 ? num_pages : 1;
			
			if (!MyWidgetResourceLib.fn.isNumeric(page_number))
				page_number = 1;
			if (page_number >= num_pages)
				page_number = num_pages;
			else if (page_number < 1)
				page_number = 1;
			
			return page_number;
		},
		
		getItemsLimitPerPageAndStartingPage: function(elm) {
			var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);			
			var items_limit_per_page = parseInt(properties["items_limit_per_page"]);
			var starting_page_number = parseInt(properties["starting_page_number"]);
			var number_of_pages_to_show_at_once = parseInt(properties["number_of_pages_to_show_at_once"]);
			
			var is_items_limit_per_page_valid = MyWidgetResourceLib.fn.isNumeric(items_limit_per_page) && items_limit_per_page > 0;
			var is_starting_page_number_valid = MyWidgetResourceLib.fn.isNumeric(starting_page_number) && starting_page_number >= 0;
			
			//get items_limit_per_page and starting_page_number from the list properties based in the dependent_widgets_id (Choose the first list with a items_limit_per_page)
			if (!is_items_limit_per_page_valid || !is_starting_page_number_valid) {
				var dependent_widgets_id = properties["dependent_widgets_id"];
				
				if (dependent_widgets_id) {
					dependent_widgets_id = MyWidgetResourceLib.fn.prepareDependentWidgetsId(dependent_widgets_id);
					
					MyWidgetResourceLib.fn.each(dependent_widgets_id, function(idx, dependent_widget_id) {
						var widgets = MyWidgetResourceLib.fn.getDependentWidgetsById(dependent_widget_id);
						
						if (widgets)
							for (var i = 0, t = widgets.length; i < t; i++) {
								var widget = widgets[i];
								
								if (widget) {
									var widget_properties = MyWidgetResourceLib.fn.getWidgetProperties(widget);
									
									if (MyWidgetResourceLib.fn.isPlainObject(widget_properties)) {
										if (!is_items_limit_per_page_valid && parseInt(widget_properties["items_limit_per_page"]) > 0)
											items_limit_per_page = parseInt(widget_properties["items_limit_per_page"]);
										
										if (!is_starting_page_number_valid && parseInt(widget_properties["starting_page_number"]) > 0)
											starting_page_number = parseInt(widget_properties["starting_page_number"]);
										
										if (is_items_limit_per_page_valid && is_starting_page_number_valid)
											return false; //break loop
									}
								}
							}
					});
				}
			}
			
			return {
				items_limit_per_page: items_limit_per_page,
				starting_page_number: starting_page_number,
				number_of_pages_to_show_at_once: number_of_pages_to_show_at_once,
			};
		},
	});
	/****************************************************************************************
	 *				 END: PAGINATION HANDLER 					*
	 ****************************************************************************************/
		
	/****************************************************************************************
	 *				 START: TABLE HANDLER 					*
	 ****************************************************************************************/
	MyWidgetResourceLib.TableHandler = MyWidgetResourceLib.fn.TableHandler = ({
		
		prepareTableVars: function(elm) {
			if (elm) {
				if (!MyWidgetResourceLib.fn.existsNodeElementData(elm, "table_loading_html")) {
					var table_loading_html = MyWidgetResourceLib.ListHandler.getLoadingHtml(elm);
					MyWidgetResourceLib.fn.setNodeElementData(elm, "table_loading_html", table_loading_html);
				}
				
				if (!MyWidgetResourceLib.fn.existsNodeElementData(elm, "table_empty_html")) {
					var table_empty_html = MyWidgetResourceLib.ListHandler.getEmptyHtml(elm);
					MyWidgetResourceLib.fn.setNodeElementData(elm, "table_empty_html", table_empty_html);
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No table element in prepareTableVars method!");
		},
		
		convertTableIntoTree: function(elm) {
			var tree = null;
			
			if (elm) {
				var thead = elm.querySelector("thead");
				var tbody = elm.querySelector("tbody");
				var ths = thead ? thead.querySelectorAll("td, th") : elm.querySelectorAll("th"); 
				var trs = null;
				
				if (tbody)
					trs = tbody.querySelectorAll("tr");
				else {
					var items = elm.querySelectorAll("tr");
					trs = [];
					
					if (items)
						for (var i = 0, t = items.length; i < t; i++) {
							var tr = items[i];
							var tr_ths = tr.querySelectorAll("th");
							
							if (!tr_ths || tr_ths.length == 0)
								trs.push(tr);
						}
				}
				
				var html = '<ul data-widget-list-tree';
				
				for (var w = 0, t = elm.attributes.length; w < t; w++) {
					var attribute = elm.attributes[w];
					
					if (attribute.name != "data-widget-list-table")
						html += ' ' + attribute.name + '="' + ("" + attribute.value).replace(/"/g, "&quot;") + '"';
				}
				
				html += '>';
				
				if (trs)
					for (var i = 0, ti = trs.length; i < ti; i++) {
						var tr = trs[i];
						var tds = tr.querySelectorAll("td");
						
						html += '<li';
						
						for (var w = 0, tw = tr.attributes.length; w < tw; w++) {
							var attribute = tr.attributes[w];
							html += ' ' + attribute.name + '="' + ("" + attribute.value).replace(/"/g, "&quot;") + '"';
						}
						
						html += '>';
						
						if (tds)
							for (var j = 0, tj = tds.length; j < tj; j++) {
								var td = tds[i];
								var th = ths[i];
								
								html += '<div';
								
								for (var w = 0, tw = td.attributes.length; w < tw; w++) {
									var attribute = td.attributes[w];
									html += ' ' + attribute.name + '="' + ("" + attribute.value).replace(/"/g, "&quot;") + '"';
								}
								
								html += '>';
								
								if (th && ("" + th.innerHTML).replace(/\s+/g, "") != "") {
									html += '<label';
									
									for (var w = 0, tw = th.attributes.length; w < tw; w++) {
										var attribute = th.attributes[w];
										html += ' ' + attribute.name + '="' + ("" + attribute.value).replace(/"/g, "&quot;") + '"';
									}
									
									html += '>' + th.innerHTML + '</label>';
								}
								
								html += td.innerHTML;
								html += '</div>';
							}
						
						html += '</li>';
					}
				
				html += '</ul>';
				
				elm.insertAdjacentHTML('afterend', html);
				tree = elm.nextElementSibling;
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No table element in convertTableIntoTree method!");
			
			return tree;
		},
	});
	/****************************************************************************************
	 *				 END: TABLE HANDLER 					*
	 ****************************************************************************************/
		
	/****************************************************************************************
	 *				 START: TREE HANDLER 					*
	 ****************************************************************************************/
	MyWidgetResourceLib.TreeHandler = MyWidgetResourceLib.fn.TreeHandler = ({
		
		prepareTreeVars: function(elm) {
			if (elm) {
				if (!MyWidgetResourceLib.fn.existsNodeElementData(elm, "tree_loading_html")) {
					tree_loading_html = MyWidgetResourceLib.ListHandler.getLoadingHtml(elm, true);
					MyWidgetResourceLib.fn.setNodeElementData(elm, "tree_loading_html", tree_loading_html);
				}
				
				if (!MyWidgetResourceLib.fn.existsNodeElementData(elm, "tree_empty_html")) {
					var tree_empty_html = MyWidgetResourceLib.ListHandler.getEmptyHtml(elm, true);
					MyWidgetResourceLib.fn.setNodeElementData(elm, "tree_empty_html", tree_empty_html);
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No tree element in prepareTreeVars method!");
		},
		
		convertTreeIntoTable: function(tree) {
			var table = null;
			
			if (elm) {
				var lis = elm.querySelectorAll("li");
				
				var html = '<table data-widget-list-table';
				
				for (var i = 0, t = elm.attributes.length; i < t; i++) {
					var attribute = elm.attributes[i];
					
					if (attribute.name != "data-widget-list-tree")
						html += ' ' + attribute.name + '="' + ("" + attribute.value).replace(/"/g, "&quot;") + '"';
				}
				
				html += '>';
				
				if (lis) {
					var thead_html = "<thead>";
					var tbody_html = "<tbody>";
					
					for (var i = 0, ti = lis.length; i < ti; i++) {
						var li = lis[i];
						var divs = li.children;
						
						thead_html += '<tr>';
						tbody_html += '<tr';
						
						for (var w = 0, tw = li.attributes.length; w < tw; w++) {
							var attribute = li.attributes[w];
							tbody_html += ' ' + attribute.name + '="' + ("" + attribute.value).replace(/"/g, "&quot;") + '"';
						}
						
						tbody_html += '>';
						
						for (var j = 0, tj = divs.length; j < tj; j++) {
							var div = divs[i];
							var children = div.children;
							var label = children[0] && children[0].nodeName.toUpperCase() == "LABEL" ? li.children[0] : null;
							
							tbody_html += '<td';
							
							for (var w = 0, tw = div.attributes.length; w < tw; w++) {
								var attribute = div.attributes[w];
								tbody_html += ' ' + attribute.name + '="' + ("" + attribute.value).replace(/"/g, "&quot;") + '"';
							}
							
							tbody_html += '>';
							
							if (label) {
								thead_html += '<th';
								
								for (var w = 0, tw = label.attributes.length; w < tw; w++) {
									var attribute = label.attributes[w];
									thead_html += ' ' + attribute.name + '="' + ("" + attribute.value).replace(/"/g, "&quot;") + '"';
								}
								
								thead_html += '>' + label.innerHTML + '</th>';
							}
							else
								thead_html += '<th></th>';
							
							var td_html = "";
							
							for (var w = label ? 1 : 0; w < children.length; w++)
								td_html += children[w].outerHTML;
							
							tbody_html += td_html;
							tbody_html += '</td>';
						}
						
						thead_html += '</tr>';
						tbody_html += '</tr>';
					}
					
					thead_html += "</thead>";
					tbody_html += "</tbody>";
					
					html += thead_html;
					html += tbody_html;
				}
				
				html += '</table>';
				
				elm.insertAdjacentHTML('beforebegin', html);
				table = elm.previousElementSibling;
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No tree element in convertTreeIntoTable method!");
			
			return table;
		},
	});
	/****************************************************************************************
	 *				 END: TREE HANDLER 					*
	 ****************************************************************************************/
	
	/****************************************************************************************
	 *				 START: LIST HANDLER 					*
	 ****************************************************************************************/
	MyWidgetResourceLib.ListHandler = MyWidgetResourceLib.fn.ListHandler = ({
		
		//Load data of a list widget.
		//This function should be called by a list widget.
		loadListTableAndTreeResource: function(elm, opts) {
			MyWidgetResourceLib.TableHandler.prepareTableVars(elm);
			MyWidgetResourceLib.TreeHandler.prepareTreeVars(elm);
		
			return MyWidgetResourceLib.ListHandler.loadListResource(elm, opts); //Do not use the "this" code here bc we may call this function as a handler and it will loose its parentship.
		},
		
		//Reload data of the current list.
		reloadParentListResource: function(elm, opts) {
			var widget = elm.closest("[data-widget-list]");
			
			if (widget) {
				var cloned_opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? MyWidgetResourceLib.fn.assignObjectRecursively({}, opts) : {}; //clone opts, otherwise if the loadListResource method changes the opts object, then it will take efect here too.
				cloned_opts["purge_cache"] = true;
				
				var status = MyWidgetResourceLib.ListHandler.loadListResource(widget, cloned_opts);
				
				if (status) {
					//reload pagination too, if exists
					var pagination_elms = MyWidgetResourceLib.ListHandler.getPaginationWidgets(widget);
					
					if (pagination_elms)
						for (var i = 0, t = pagination_elms.length; i < t; i++) {
							var pagination_elm = pagination_elms[i];
							cloned_opts = MyWidgetResourceLib.fn.assignObjectRecursively({}, cloned_opts); //clone opts, otherwise if the loadPaginationResource method changes the opts object, then it will take efect here too.
							
							if (!MyWidgetResourceLib.PaginationHandler.loadPaginationResource(pagination_elm, cloned_opts))
								status = false;
						}
					
					return status;
				}
			}
			
			return false;
		},
		
		purgeCachedLoadParentListResource: function(elm) {
			var widget = elm.closest("[data-widget-list]");
			
			if (widget) {
				MyWidgetResourceLib.ResourceHandler.purgeWidgetResources(widget, "load");
				
				//purge cache in pagination too, if exists
				var pagination_elms = MyWidgetResourceLib.ListHandler.getPaginationWidgets(widget);
				
				if (pagination_elms)
					for (var i = 0, t = pagination_elms.length; i < t; i++) {
						var pagination_elm = pagination_elms[i];
						MyWidgetResourceLib.ResourceHandler.purgeWidgetResources(pagination_elm, "load");
					}
			}
		},
		
		loadListResource: function(elm, opts) {
			if (elm) {
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var perms = MyWidgetResourceLib.PermissionHandler.getWidgetPermissions(elm);
				
				if (!perms["remove"]) {
					var sort_attrs = MyWidgetResourceLib.fn.getNodeElementData(elm, "sort_attrs");
					var saved_search_attrs = MyWidgetResourceLib.fn.getNodeElementData(elm, "saved_search_attrs");
					var search_attrs = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_attrs");
					var search_types = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_types");
					var search_cases = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_cases");
					var search_operators = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_operators");
					var page_number = MyWidgetResourceLib.fn.getNodeElementData(elm, "page_number");
					var table_loading_html = MyWidgetResourceLib.fn.getNodeElementData(elm, "table_loading_html");
					var tree_loading_html = MyWidgetResourceLib.fn.getNodeElementData(elm, "tree_loading_html");
					
					//loads the default items html
					MyWidgetResourceLib.ListHandler.prepareListItemsHtml(elm);
					var list_items_html = MyWidgetResourceLib.fn.getNodeElementData(elm, "list_items_html");
					var list_items_add_html = MyWidgetResourceLib.fn.getNodeElementData(elm, "list_items_add_html");
					
					var items_limit_per_page = properties["items_limit_per_page"];
					var starting_page_number = properties["starting_page_number"];
					
					var scroll_x = window.scrollX;
					var scroll_y = window.scrollY;
					
					search_attrs = typeof search_attrs == "string" && search_attrs[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_attrs) : search_attrs;
					search_types = typeof search_types == "string" && search_types[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_types) : search_types;
					search_cases = typeof search_cases == "string" && search_cases[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_cases) : search_cases;
					search_operators = typeof search_operators == "string" && search_operators[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_operators) : search_operators;
					
					items_limit_per_page = items_limit_per_page >= 0 ? parseInt(items_limit_per_page) : 0;
					starting_page_number = starting_page_number >= 0 ? parseInt(starting_page_number) : 0;
					page_number = MyWidgetResourceLib.fn.isNumeric(page_number) && page_number >= 0 ? parseInt(page_number) : starting_page_number;
					
					//set default search
					if (!search_attrs || (
						MyWidgetResourceLib.fn.isPlainObject(search_attrs) && MyWidgetResourceLib.fn.isEmptyObject(search_attrs)
					)) {
						var search_attrs = elm.getAttribute("data-widget-pks-attrs");
						search_attrs = search_attrs ? MyWidgetResourceLib.fn.parseJson(search_attrs) : null;
						
						if (MyWidgetResourceLib.fn.isPlainObject(search_attrs) && !MyWidgetResourceLib.fn.isEmptyObject(search_attrs))
							MyWidgetResourceLib.fn.setNodeElementData(elm, "search_attrs", search_attrs);
					}
					
					MyWidgetResourceLib.fn.setNodeElementData(elm, "saved_search_attrs", search_attrs);
					
					//if is a new search
					if (search_attrs != saved_search_attrs)
						page_number = 0;
					
					//save current page_number. Note that this will be used by other widgets
					MyWidgetResourceLib.fn.setNodeElementData(elm, "page_number", page_number);
					
					//reload list based in the resource, sort_attrs, search_attrs, search_types, search_cases, search_operators and page_number and items_limit_per_page.
					var resource = null;
					var end_handler = MyWidgetResourceLib.fn.isPlainObject(properties["end"]) ? properties["end"]["load"] : properties["end"];
					end_handler = MyWidgetResourceLib.fn.convertIntoFunctions(end_handler);
					
					if (elm.hasAttribute("data-widget-resources")) {
						var is_add_item = opts && opts["add_item"];
						var is_update_item = opts && opts["update_item"];
						var is_remove_item = opts && opts["remove_item"];
						
						//set loading message - Show loading bar in all the list_items_html's parents, bc we can have list_items_html inside of data-widget-list-table and others inside of data-widget-list-tree.
						if (!is_add_item && !is_update_item && !is_remove_item && (table_loading_html || tree_loading_html)) {
							MyWidgetResourceLib.ListHandler.cleanListItems(elm);
							MyWidgetResourceLib.ListHandler.showLoadingHtml(elm);
						}
						
						var complete_func = function(resource_elm, resources, resources_name, resources_cache_key) {
							if (is_add_item || is_update_item || is_remove_item)
								MyWidgetResourceLib.ListHandler.updateListResource(elm, resources_name, resources_cache_key, opts);
							else
								MyWidgetResourceLib.ListHandler.drawListResource(elm, resources_name, resources_cache_key);
							
							//call complete handler
							var complete_handler = MyWidgetResourceLib.fn.isPlainObject(properties["complete"]) ? properties["complete"]["load"] : properties["complete"];
							complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
							
							if (complete_handler)
								MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
							
							
							//call complete handler from opts if exists
							var complete_handler = opts && opts["complete"] ? opts["complete"] : null;
							complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
							
							if (complete_handler)
								MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
							
							//reload dependent widgets like the pagination elements, if apply
							MyWidgetResourceLib.fn.loadDependentWidgetsByIdWithoutResourcesToLoad(properties["dependent_widgets_id"], {}, opts);
							
							//set previous scroll after drawing the table rows
				   			window.scrollTo(scroll_x, scroll_y);
				   			
				   			//call end handler
							if (end_handler)
								MyWidgetResourceLib.fn.executeFunctions(end_handler, elm, resources_name, resources_cache_key);
						};
						
						//prepare extra url
						var extra_url = opts && opts["extra_url"] ? opts["extra_url"] : "";
						var ignore_resource_conditions = false;
						var resource_default_cache_key = "";
						
						if ((is_update_item || is_remove_item) && opts && opts["search_attrs"]) {
							extra_url += "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(opts["search_attrs"], "search_attrs");
							ignore_resource_conditions = true;
						}
						else {
							if (sort_attrs)
								extra_url += "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(sort_attrs, "sort_attrs");
							
							if (MyWidgetResourceLib.fn.isPlainObject(search_attrs) && !MyWidgetResourceLib.fn.isEmptyObject(search_attrs)) {
								ignore_resource_conditions = true;
								
								//prepare search_attrs
								var search_res = MyWidgetResourceLib.ListHandler.prepareSearchAttrs(search_attrs, search_types, search_cases, list_items_html);
								search_attrs = search_res["search_attrs"];
								search_types = search_res["search_types"];
								
								extra_url += "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_attrs, "search_attrs") 
									+ (search_types ? "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_types, "search_types") : "") 
									+ (search_cases ? "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_cases, "search_cases") : "") 
									+ (search_operators ? "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_operators, "search_operators") : "");
							}
							
							if (page_number > 1)
								extra_url += "&page_number=" + page_number;
							
							if (starting_page_number > 0)
								resource_default_cache_key += "&page_number=" + starting_page_number;
							
							if (items_limit_per_page > 0) {
								extra_url += "&items_limit_per_page=" + items_limit_per_page;
								resource_default_cache_key += "&items_limit_per_page=" + items_limit_per_page;
								
								if (page_number > 1)
									extra_url += "&page_items_start=" + ((page_number * items_limit_per_page) - items_limit_per_page);
							}
						}
						
						var resource_opts = {};
						
						if (MyWidgetResourceLib.fn.isPlainObject(opts)) {
							resource_opts = MyWidgetResourceLib.fn.assignObjectRecursively({}, opts);
							
							delete resource_opts["add_item"];
							delete resource_opts["update_item"];
							delete resource_opts["remove_item"];
							delete resource_opts["search_attrs"];
						}
						
						resource_opts["ignore_resource_conditions"] = ignore_resource_conditions;
						resource_opts["extra_url"] = extra_url;
						resource_opts["label"] = "list";
						resource_opts["complete"] = complete_func;
						resource_opts["resource_default_cache_key"] = resource_default_cache_key;
						resource = MyWidgetResourceLib.ResourceHandler.executeGetWidgetResourcesRequest(elm, "load", resource_opts);
						
						if (!resource) {
							//remove loading message - Remove loading bar in all the list_items_html's parents, bc we can have list_items_html inside of data-widget-list-table and others inside of data-widget-list-tree.
							if (!is_add_item && !is_update_item && !is_remove_item && (table_loading_html || tree_loading_html))
								MyWidgetResourceLib.ListHandler.cleanListItems(elm);
						}
					}
					
					//static list
					if (!resource) {
						MyWidgetResourceLib.StaticListHandler.searchItems(elm, search_attrs, search_types, search_cases, search_operators);
						
						if (sort_attrs)
							MyWidgetResourceLib.StaticListHandler.sortItems(elm, sort_attrs);
						
						MyWidgetResourceLib.fn.loadDependentWidgetsByIdWithoutResourcesToLoad(properties["dependent_widgets_id"], {}, opts);
						
						if (end_handler)
							MyWidgetResourceLib.fn.executeFunctions(end_handler, elm, null);
					}
					
					return true;
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No list element in loadListResource method!");
			
			return false;
		},
		
		//Executes a resource based on the saved parameters of a list.
		//This method receives a third argument with an 'opts' object correspondent to some options that will be passed on execution of the correspondent resource.
		executeSingleListResource: function(elm, resource_key, opts) {
			if (elm) {
				if (elm.hasAttribute("data-widget-resources")) {
					var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
					var sort_attrs = MyWidgetResourceLib.fn.getNodeElementData(elm, "sort_attrs");
					var search_attrs = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_attrs");
					var search_types = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_types");
					var search_cases = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_cases");
					var search_operators = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_operators");
					var page_number = MyWidgetResourceLib.fn.getNodeElementData(elm, "page_number");
					
					var items_limit_per_page = properties["items_limit_per_page"];
					var starting_page_number = properties["starting_page_number"];
					
					search_attrs = typeof search_attrs == "string" && search_attrs[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_attrs) : search_attrs;
					search_types = typeof search_types == "string" && search_types[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_types) : search_types;
					search_cases = typeof search_cases == "string" && search_cases[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_cases) : search_cases;
					search_operators = typeof search_operators == "string" && search_operators[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_operators) : search_operators;
					
					items_limit_per_page = items_limit_per_page >= 0 ? parseInt(items_limit_per_page) : 0;
					starting_page_number = starting_page_number >= 0 ? parseInt(starting_page_number) : 0;
					page_number = MyWidgetResourceLib.fn.isNumeric(page_number) && page_number >= 0 ? parseInt(page_number) : starting_page_number;
					
					//execute resource from list based in sort_attrs, search_attrs, search_types, search_cases, search_operators and page_number and items_limit_per_page.
					var complete_func = function(resource_elm, resources, resources_name, resources_cache_key) {
						//call complete handler
						var complete_handler = MyWidgetResourceLib.fn.isPlainObject(properties["complete"]) ? properties["complete"][resource_key] : properties["complete"];
						complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
						
						if (complete_handler)
							MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
						
						//call complete handler from opts if exists
						var complete_handler = opts && opts["complete"] ? opts["complete"] : null;
						complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
						
						if (complete_handler)
							MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
						
						//call end handler
						var end_handler = MyWidgetResourceLib.fn.isPlainObject(properties["end"]) ? properties["end"][resource_key] : properties["end"];
						end_handler = MyWidgetResourceLib.fn.convertIntoFunctions(end_handler);
					
						if (end_handler)
							MyWidgetResourceLib.fn.executeFunctions(end_handler, elm, resources_name, resources_cache_key);
					};
					
					//prepare extra url
					var extra_url = opts && opts["extra_url"] ? opts["extra_url"] : "";
					var ignore_resource_conditions = false;
					var resource_default_cache_key = "";
					
					if (sort_attrs)
						extra_url += "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(sort_attrs, "sort_attrs");
					
					if (MyWidgetResourceLib.fn.isPlainObject(search_attrs) && !MyWidgetResourceLib.fn.isEmptyObject(search_attrs)) {
						extra_url += "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_attrs, "search_attrs") 
							+ (search_types ? "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_types, "search_types") : "") 
							+ (search_cases ? "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_cases, "search_cases") : "") 
							+ (search_operators ? "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_operators, "search_operators") : "");
						ignore_resource_conditions = true;
					}
					
					if (page_number > 1)
						extra_url += "&page_number=" + page_number;
					
					if (starting_page_number > 0)
						resource_default_cache_key += "&page_number=" + starting_page_number;
					
					if (items_limit_per_page > 0) {
						extra_url += "&items_limit_per_page=" + items_limit_per_page;
						resource_default_cache_key += "&items_limit_per_page=" + items_limit_per_page;
						
						if (page_number > 1)
							extra_url += "&page_items_start=" + ((page_number * items_limit_per_page) - items_limit_per_page);
					}
					
					var resource_opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? MyWidgetResourceLib.fn.assignObjectRecursively({}, opts) : {};
					resource_opts["ignore_resource_conditions"] = ignore_resource_conditions; 
					resource_opts["extra_url"] = extra_url;
					resource_opts["label"] = "list";
					resource_opts["complete"] = complete_func;
					resource_opts["resource_default_cache_key"] = resource_default_cache_key;
					var resource = MyWidgetResourceLib.ResourceHandler.executeGetWidgetResourcesRequest(elm, resource_key, resource_opts);
					
					if (resource)
						return true
				}
				else
					MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No resource set in executeSingleListResource method. Please inform the web-developer accordingly...");
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No list element in executeSingleListResource method!");
			
			return false;	
		},
		
		//Load data of a list caption widget.
		//This function should be called by a [data-widget-list-caption] widget.
		loadListCaptionResource: function(elm, opts) {
			if (elm) {
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var perms = MyWidgetResourceLib.PermissionHandler.getWidgetPermissions(elm);
				
				if (!perms["remove"]) {
					//get widget list
					var parent = elm.closest("[data-widget-list], [data-widget-group-list]");
					var list = null;
					
					if (parent.classList.contains("data-widget-group-list"))
						list = parent.querySelector("[data-widget-list]");
					else
						list = parent;
					
					//remove data-widget-resources-loaded that the loadWidgetResource method adds so we can reload this method through the List widget dependency
					elm.removeAttribute("data-widget-resources-loaded"); //Note that the caption widget will be reloaded when the List widget gets loaded.
					
					//load resource
					if (list) {
						var resource = null;
						var end_handler = MyWidgetResourceLib.fn.isPlainObject(properties["end"]) ? properties["end"]["load"] : properties["end"];
						end_handler = MyWidgetResourceLib.fn.convertIntoFunctions(end_handler);
						
						if (elm.hasAttribute("data-widget-resources")) {
							var search_attrs = MyWidgetResourceLib.fn.getNodeElementData(list, "search_attrs");
							var search_types = MyWidgetResourceLib.fn.getNodeElementData(list, "search_types");
							var search_cases = MyWidgetResourceLib.fn.getNodeElementData(list, "search_cases");
							var search_operators = MyWidgetResourceLib.fn.getNodeElementData(list, "search_operators");
							
							search_attrs = typeof search_attrs == "string" && search_attrs[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_attrs) : search_attrs;
							search_types = typeof search_types == "string" && search_types[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_types) : search_types;
							search_cases = typeof search_cases == "string" && search_cases[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_cases) : search_cases;
							search_operators = typeof search_operators == "string" && search_operators[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_operators) : search_operators;
							
							var complete_func = function(resource_elm, resources, resources_name, resources_cache_key) {
								MyWidgetResourceLib.ListHandler.drawCaption(elm, resources_name, resources_cache_key, list);
								
								//call complete handler
								var complete_handler = MyWidgetResourceLib.fn.isPlainObject(properties["complete"]) ? properties["complete"]["load"] : properties["complete"];
								complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
								
								if (complete_handler)
									MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
								
								//call complete handler from opts if exists
								var complete_handler = opts && opts["complete"] ? opts["complete"] : null;
								complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
								
								if (complete_handler)
									MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
								
								//reload dependent widgets like the pagination elements, if apply
								MyWidgetResourceLib.fn.loadDependentWidgetsByIdWithoutResourcesToLoad(properties["dependent_widgets_id"], {}, opts);
								
								//call end handler
								if (end_handler)
									MyWidgetResourceLib.fn.executeFunctions(end_handler, elm, resources_name, resources_cache_key);
							};
							
							var extra_url = opts && opts["extra_url"] ? opts["extra_url"] : "";
							var ignore_resource_conditions = false;
							
							if (MyWidgetResourceLib.fn.isPlainObject(search_attrs) && !MyWidgetResourceLib.fn.isEmptyObject(search_attrs)) {
								extra_url += "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_attrs, "search_attrs") 
									+ (search_types ? "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_types, "search_types") : "") 
									+ (search_cases ? "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_cases, "search_cases") : "") 
									+ (search_operators ? "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_operators, "search_operators") : "");
								ignore_resource_conditions = true;
							}
							
							var resource_opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? MyWidgetResourceLib.fn.assignObjectRecursively({}, opts) : {};
							resource_opts["ignore_resource_conditions"] = ignore_resource_conditions; //if search_attrs exists, ignore conditions
							resource_opts["extra_url"] = extra_url;
							resource_opts["label"] = "list caption";
							resource_opts["complete"] = complete_func;
							resource = MyWidgetResourceLib.ResourceHandler.executeGetWidgetResourcesRequest(elm, "load", resource_opts);
							
							if (resource)
								return true;
							else
								MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No list caption load resource set in loadListCaptionResource. Please inform the web-developer accordingly...");
						}
						
						if (!resource) {
							//reload dependent widgets like the pagination elements, if apply
							MyWidgetResourceLib.fn.loadDependentWidgetsByIdWithoutResourcesToLoad(properties["dependent_widgets_id"], {}, opts);
							
							if (end_handler)
								MyWidgetResourceLib.fn.executeFunctions(end_handler, elm);
						}
						
						return true;
					}
					else
						MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No list element in loadListCaptionResource method!");
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No list caption element in loadListCaptionResource method!");
			
			return false;
		},
		
		drawCaption: function(elm, resources_name, resources_cache_key, list) {
			if (elm) {
				if (list) {
					var items_total = 0;
					
					for (var i = 0, ti = resources_name.length; i < ti; i++) {
						var resource_name = resources_name[i];
						var resource_cache_key = resources_cache_key ? resources_cache_key[i] : null;
						items_total += parseInt(MyWidgetResourceLib.ResourceHandler.getLoadedSLAResult(resource_name, resource_cache_key));
					}
					
					var page_number = MyWidgetResourceLib.fn.getNodeElementData(list, "page_number");
					
					var list_properties = MyWidgetResourceLib.fn.getWidgetProperties(list);
					var items_limit_per_page = list_properties["items_limit_per_page"];
					var starting_page_number = list_properties["starting_page_number"];
					
					items_limit_per_page = items_limit_per_page >= 0 ? parseInt(items_limit_per_page) : 0;
					starting_page_number = starting_page_number >= 0 ? parseInt(starting_page_number) : 0;
					page_number = MyWidgetResourceLib.fn.isNumeric(page_number) && page_number >= 0 ? parseInt(page_number) : starting_page_number;
					page_number = page_number > 0 ? page_number : 1;
					
					var num_pages = items_total > 0 && items_limit_per_page > 0 ? Math.ceil(items_total / items_limit_per_page) : 0;
					var start_index = (page_number - 1) * items_limit_per_page;
					var end_index = start_index + items_limit_per_page;
					
					var info = items_total > 0 ? "From " + start_index + " to " + end_index + " of " + items_total + " total items" : "";
					
					//prepare inner elements with data
					var draw_caption_item = function(selector, value) {
						var sub_elms = elm.querySelectorAll(selector);
						
						if (sub_elms)
							MyWidgetResourceLib.fn.each(sub_elms, function(idx, sub_elm) {
								sub_elm.innerHTML = value;
							});
					};
					draw_caption_item("[data-widget-list-caption-items-total]", items_total);
					draw_caption_item("[data-widget-list-caption-items-limit-per-page]", items_limit_per_page);
					draw_caption_item("[data-widget-list-caption-num-pages]", num_pages);
					draw_caption_item("[data-widget-list-caption-page-number]", page_number);
					draw_caption_item("[data-widget-list-caption-start-index]", start_index);
					draw_caption_item("[data-widget-list-caption-end-index]", end_index);
					draw_caption_item("[data-widget-list-caption-info]", info);
					
					//prepare draw handler if exists
					var elm_properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
					var handler = MyWidgetResourceLib.fn.convertIntoFunctions(elm_properties["draw"]);
					
					//execute widget handler or default one
					if (handler)
						MyWidgetResourceLib.fn.executeFunctions(handler, elm, list, {
							items_total: items_total,
							items_limit_per_page: items_limit_per_page,
							num_pages: num_pages,
							page_number: page_number,
							start_index: start_index,
							end_index: end_index,
							info: info
						});
				}
				else
					MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No list element in drawCaption method!");
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No list caption element in drawCaption method!");
		},
		
		drawListResource: function(elm, resources_name, resources_cache_key) {
			if (elm) {
				var list_items_html = MyWidgetResourceLib.fn.getNodeElementData(elm, "list_items_html");
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var is_data_empty = true;
				
				//clean the loading message
				MyWidgetResourceLib.ListHandler.cleanListItems(elm);
				
				//load items
				for (var i = 0, t = resources_name.length; i < t; i++) {
					var resource_name = resources_name[i];
					var resource_cache_key = resources_cache_key ? resources_cache_key[i] : null;
					var data = MyWidgetResourceLib.ResourceHandler.getLoadedSLAResult(resource_name, resource_cache_key);
					
					if (data) {
						//TODO: find a way to loop asyncronously
					   	MyWidgetResourceLib.fn.each(data, function(idx, record) {
					   		is_data_empty = false;
					   		
					   		if (MyWidgetResourceLib.fn.isPlainObject(record)) {
								//prepare PKs string
								var pks_attrs_names = properties.hasOwnProperty("pks_attrs_names") ? properties["pks_attrs_names"] : "";
								pks_attrs_names = MyWidgetResourceLib.fn.isArray(pks_attrs_names) ? pks_attrs_names : pks_attrs_names.replace(/;/g, ",").split(",");
								var pks_attrs = {};
								
								for (var j = 0, tj = pks_attrs_names.length; j < tj; j++) {
									var pk_attr_name = ("" + pks_attrs_names[j]).replace(/\s+/g, "");
									
									if (pk_attr_name)
										pks_attrs[pk_attr_name] = record.hasOwnProperty(pk_attr_name) ? record[pk_attr_name] : null;
								}
								
								//prepare html
								var prev_item_elm = null;
								
				   				for (var j = 0, tj = list_items_html.length; j < tj; j++) {
									var list_item_html = list_items_html[j];
									var item_html = list_item_html.html;
									var item_parent = list_item_html.parent;
									
									//replace hashtags if apply to the current resource
									var inner_item_hash_tags_to_ignore = MyWidgetResourceLib.HashTagHandler.getWidgetResourceValueInnerItemsHtmlHashTagsBasedInResources(item_html, resource_name, resource_cache_key, idx);
									item_html = MyWidgetResourceLib.HashTagHandler.replaceHtmlHashTagsWithResources(item_html, resource_name, resource_cache_key, idx, inner_item_hash_tags_to_ignore);
									
									//append html element to item_parent
									var item_elm = null;
									
									if (list_item_html.prev && list_item_html.prev.parentNode) {
										var prev = list_item_html.prev;
										
										if (j > 0 && list_items_html[j - 1].prev == prev && prev_item_elm) //if multiple list_items_html, the previous one is the list_items_html[j - 1], otherwise the elements will appear flipped and with the wrong order.
											prev = prev_item_elm;
										
										prev.insertAdjacentHTML('afterend', item_html);
										item_elm = prev.nextElementSibling;
									}
									else if (list_item_html.next && list_item_html.next.parentNode) {
										list_item_html.next.insertAdjacentHTML('beforebegin', item_html);
										item_elm = list_item_html.next.previousElementSibling;
									}
									else {
										item_parent.insertAdjacentHTML('beforeend', item_html);
										item_elm = item_parent.lastElementChild;
									}
									
									//set PKs
									item_elm.setAttribute("data-widget-pks-attrs", JSON.stringify(pks_attrs));
									
									//save loaded resource data in item_elm
									MyWidgetResourceLib.FieldHandler.saveWidgetLoadedResourceValues(item_elm, resource_name, resource_cache_key, idx);
									
									//set attributes according with the resources, if apply
									MyWidgetResourceLib.FieldHandler.setWidgetResourceAttributes(item_elm, resource_name, resource_cache_key, idx);
									
									//prepare data-widget-resource-value
									MyWidgetResourceLib.FieldHandler.setWidgetResourceValues(item_elm, resource_name, resource_cache_key, idx);
									
									prev_item_elm = item_elm;
								}
							}
					   	});
					}
				}
				
				if (is_data_empty) { //set empty message - Show empty message in all the list_items_html's parents, bc we can have list_items_html inside of data-widget-list-table and others inside of data-widget-list-tree.
					MyWidgetResourceLib.ListHandler.showEmptyHtml(elm);
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No list element in drawListResource method!");
		},
		
		updateListResource: function(elm, resources_name, resources_cache_key, opts) {
			//console.log("update list for search_attrs:");
			//console.log(opts);
			
			if (elm) {
				opts = opts ? opts : {};
				
				var found = function(obj_1, obj_2) { //Do not use JSON.stringify to compare bc the order may be different
					//compare if 2 objects contains the same properties and values, even if the order is different
					var same = MyWidgetResourceLib.fn.isPlainObject(obj_1) && MyWidgetResourceLib.fn.isPlainObject(obj_2);
					
					if (same)
						for (var k in obj_1)
							if (!obj_2.hasOwnProperty(k) || obj_1[k] != obj_2[k]) {
								same = false;
								break;
							}
					
					if (same)
						for (var k in obj_2)
							if (!obj_1.hasOwnProperty(k) || obj_2[k] != obj_1[k]) {
								same = false;
								break;
							}
					
					return same;
				};
				var list_items_html = MyWidgetResourceLib.fn.getNodeElementData(elm, "list_items_html");
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var exists_data_records = false;
				var exists_data_record_item = false;
				var exists_list_item = false;
				var search_attrs = opts["search_attrs"];
				var is_add_item = opts["add_item"];
				var is_update_item = opts["update_item"];
				var is_remove_item = opts["remove_item"];
				
				var pks_attrs_names = properties.hasOwnProperty("pks_attrs_names") ? properties["pks_attrs_names"] : "";
				pks_attrs_names = MyWidgetResourceLib.fn.isArray(pks_attrs_names) ? pks_attrs_names : pks_attrs_names.replace(/;/g, ",").split(",");
				
				//filter search_attrs with only the pks - Note that the search_attrs may have more than the pks attributes, because it might come from a third-party table, so we must filter it by only the list's pk names.
				var search_attrs_pks = {};
				
				if (MyWidgetResourceLib.fn.isPlainObject(search_attrs))
					for (var k in search_attrs)
						if (pks_attrs_names.indexOf(k) != -1)
							search_attrs_pks[k] = search_attrs[k];
				
				//update ui according with resources
				for (var i = 0, t = resources_name.length; i < t; i++) {
					var resource_name = resources_name[i];
					var resource_cache_key = resources_cache_key ? resources_cache_key[i] : null;
					var data = MyWidgetResourceLib.ResourceHandler.getLoadedSLAResult(resource_name, resource_cache_key);
					
					if (data) {
						MyWidgetResourceLib.fn.each(data, function(idx, record) {
					   		if (MyWidgetResourceLib.fn.isPlainObject(record)) {
								exists_data_records = true;
								
								//prepare PKs string
								var pks_attrs = {};
								
								for (var j = 0, tj = pks_attrs_names.length; j < tj; j++) {
									var pk_attr_name = ("" + pks_attrs_names[j]).replace(/\s+/g, "");
									
									if (pk_attr_name)
										pks_attrs[pk_attr_name] = record.hasOwnProperty(pk_attr_name) ? record[pk_attr_name] : null;
								}
								
								var pks_attrs_str = JSON.stringify(pks_attrs);
								
								if (found(search_attrs_pks, pks_attrs))
									exists_data_record_item = true;
								
								//prepare html
								var prev_item_elm = null;
								
								for (var j = 0, tj = list_items_html.length; j < tj; j++) {
									var list_item_html = list_items_html[j];
									var item_html = list_item_html.html;
									var item_parent = list_item_html.parent;
									var children = item_parent.children;
									var item_exists = false;
									
									//update existent item
									for (var w = 0, tw = children.length; w < tw; w++) {
										var child = children[w];
										var child_pks_attrs_str = child.getAttribute("data-widget-pks-attrs");
										var child_pks_attrs = MyWidgetResourceLib.fn.parseJson(child_pks_attrs_str);
										
										if (found(pks_attrs, child_pks_attrs)) {
											item_exists = true;
											exists_list_item = true;
											
											if (is_update_item) {
												//replace hashtags if apply to the current resource
												var inner_item_hash_tags_to_ignore = MyWidgetResourceLib.HashTagHandler.getWidgetResourceValueInnerItemsHtmlHashTagsBasedInResources(item_html, resource_name, resource_cache_key, idx);
												var new_item_html = MyWidgetResourceLib.HashTagHandler.replaceHtmlHashTagsWithResources(item_html, resource_name, resource_cache_key, idx, inner_item_hash_tags_to_ignore);
												
												//append html element to item_parent
												child.insertAdjacentHTML('afterend', new_item_html);
												var item_elm = child.nextElementSibling;
												
												//set PKs
												item_elm.setAttribute("data-widget-pks-attrs", pks_attrs_str);
												
												//save loaded resource data in item_elm
												MyWidgetResourceLib.FieldHandler.saveWidgetLoadedResourceValues(item_elm, resource_name, resource_cache_key, idx);
												
												//set attributes according with the resources, if apply
												MyWidgetResourceLib.FieldHandler.setWidgetResourceAttributes(item_elm, resource_name, resource_cache_key, idx);
												
												//prepare data-widget-resource-value
												MyWidgetResourceLib.FieldHandler.setWidgetResourceValues(item_elm, resource_name, resource_cache_key, idx);
												
												//remove child
												item_parent.removeChild(child);
												
												prev_item_elm = item_elm;
											}
											
											break;
										}
									}
									
									//add new item if not exists
									if (!item_exists && is_add_item) {
										//replace hashtags if apply to the current resource
										var inner_item_hash_tags_to_ignore = MyWidgetResourceLib.HashTagHandler.getWidgetResourceValueInnerItemsHtmlHashTagsBasedInResources(item_html, resource_name, resource_cache_key, idx);
										item_html = MyWidgetResourceLib.HashTagHandler.replaceHtmlHashTagsWithResources(item_html, resource_name, resource_cache_key, idx, inner_item_hash_tags_to_ignore);
										
										//append html element to item_parent
										var item_elm = null;
										
										if (list_item_html.prev && list_item_html.prev.parentNode) {
											var prev = list_item_html.prev;
											
											if (j > 0 && list_items_html[j - 1].prev == prev && prev_item_elm) //if multiple list_items_html, the previous one is the list_items_html[j - 1], otherwise the elements will appear flipped and with the wrong order.
												prev = prev_item_elm;
											
											prev.insertAdjacentHTML('afterend', item_html);
											item_elm = prev.nextElementSibling;
											exists_list_item = true;
										}
										else if (list_item_html.next && list_item_html.next.parentNode) {
											list_item_html.next.insertAdjacentHTML('beforebegin', item_html);
											item_elm = list_item_html.next.previousElementSibling;
											exists_list_item = true;
										}
										else {
											item_parent.insertAdjacentHTML('beforeend', item_html);
											item_elm = item_parent.lastElementChild;
											exists_list_item = true;
										}
										
										//set PKs
										item_elm.setAttribute("data-widget-pks-attrs", JSON.stringify(pks_attrs));
										
										//save loaded resource data in item_elm
										MyWidgetResourceLib.FieldHandler.saveWidgetLoadedResourceValues(item_elm, resource_name, resource_cache_key, idx);
										
										//set attributes according with the resources, if apply
										MyWidgetResourceLib.FieldHandler.setWidgetResourceAttributes(item_elm, resource_name, resource_cache_key, idx);
										
										//prepare data-widget-resource-value
										MyWidgetResourceLib.FieldHandler.setWidgetResourceValues(item_elm, resource_name, resource_cache_key, idx);
										
										//check if data-widget-empty exists and remove it
										if (item_parent.children.length > 1)
											for (var w = 0; w < item_parent.children.length; w++)
												if (item_parent.children[w].hasAttribute("data-widget-empty")) {
													item_parent.removeChild(item_parent.children[w]);
													w--;
												}
										
										prev_item_elm = item_elm;
									}
								}
							}
					   	});
					}
			   	}
			   	
			   	if (search_attrs) {
			   		if (is_update_item && !exists_list_item /* && !exists_data_records*/) { //it means that the PK was changed, so we refresh the all list, bc there is no element anymore
				   		//purge cache from parent
						MyWidgetResourceLib.ListHandler.purgeCachedLoadParentListResource(elm);
						
						//reload list
				   		MyWidgetResourceLib.ListHandler.loadListResource(elm, {force: true});
				   	}
				   	else if (is_remove_item && !exists_list_item && exists_data_record_item) { //remove item was unsuccessfully, so refresh all list
				   		//purge cache from parent
						MyWidgetResourceLib.ListHandler.purgeCachedLoadParentListResource(elm);
						
						//reload list
						MyWidgetResourceLib.ListHandler.loadListResource(elm, {force: true});
				   	}
				   	else if (is_remove_item && !exists_data_record_item) { //remove item
				   		var removed = false;
				   		var is_page_empty = true;
				   		
				   		for (var j = 0, tj = list_items_html.length; j < tj; j++) {
							var list_item_html = list_items_html[j];
							var item_parent = list_item_html.parent;
							var children = item_parent.children;
							
							for (var w = 0, tw = children.length; w < tw; w++) {
								var child = children[w];
								var child_pks_attrs_str = child.getAttribute("data-widget-pks-attrs");
								var child_pks_attrs = MyWidgetResourceLib.fn.parseJson(child_pks_attrs_str);
								
								if (found(search_attrs_pks, child_pks_attrs)) {
									item_parent.removeChild(child);
									removed = true;
								}
								else
									is_page_empty = false;
							}
						}
						
						if (removed) {
							//purge cache from parent
							MyWidgetResourceLib.ListHandler.purgeCachedLoadParentListResource(elm);
							
							//check if page is empty, and if yes, reload list.
							if (is_page_empty) {
								//When we delete a record from the Edit Popup, we cannot replicate this behaviour, so we simply refresh the list. However If I find any way to replicate the code below that is commented, in the Edit Popup, then I should uncomment the code below.
								MyWidgetResourceLib.ListHandler.loadListResource(elm, {force: true});
								
								/*var page_number = MyWidgetResourceLib.fn.getNodeElementData(elm, "page_number");
								page_number = page_number - 1;
								page_number = page_number > 0 ? page_number : 0;
								MyWidgetResourceLib.fn.setNodeElementData(elm, "page_number", page_number);
								
								MyWidgetResourceLib.fn.setNodeElementData(elm, "page_number", page_number);
								
								var pagination_elms = MyWidgetResourceLib.ListHandler.getPaginationWidgets(elm);
								
								if (pagination_elms)
									for (var w = 0, tw = pagination_elms.length; w < tw; w++)
										MyWidgetResourceLib.fn.setNodeElementData(pagination_elms[w], "page_number", page_number);
								
								MyWidgetResourceLib.ListHandler.reloadParentListResource(elm);*/
							}
						}
					}
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No list element in updateListResource method!");
		},
		
		/*Handler to be called as a display handler of the 'Display Resource Attribute' settings. This function receives 2 arguments: a html node element and an object with data values to be displayed inside of that node element. Basically this function will loop the data objects and for each object, will display its values inside of the child node with the [data-widget-item] attribute. 
		This behaviour is very similar with the method 'MyWidgetResourceLib.ListHandler.loadListTableAndTreeResource' but instead of displaying a resource data, it displays a data that is passed as an argument of this function. In this case, this data could be an object inside of an attribute of a record from a resource, where the loadListTableAndTreeResource displays the records and for a specific attribute, the drawListData method displays the sub-object inside of the correspondent element. 
		This method should be used when we wish to display objects with other inner objects inside, like tree objects.*/
		drawListData: function(elm, data) {
			if (elm) {
				MyWidgetResourceLib.TableHandler.prepareTableVars(elm);
				MyWidgetResourceLib.TreeHandler.prepareTreeVars(elm);
				MyWidgetResourceLib.ListHandler.prepareListItemsHtml(elm);
				
				var is_data_empty = true;
				
				//clean the loading message
				MyWidgetResourceLib.ListHandler.cleanListItems(elm);
				
				if (data) {
					var list_items_html = MyWidgetResourceLib.fn.getNodeElementData(elm, "list_items_html");
					var table_loading_html = MyWidgetResourceLib.fn.getNodeElementData(elm, "table_loading_html");
					var tree_loading_html = MyWidgetResourceLib.fn.getNodeElementData(elm, "tree_loading_html");
					var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
					
					if (table_loading_html || tree_loading_html)
						MyWidgetResourceLib.ListHandler.showLoadingHtml(elm);
					
					//load items
				   	MyWidgetResourceLib.fn.each(data, function(idx, record) {
				   		is_data_empty = false;
				   		
				   		if (MyWidgetResourceLib.fn.isPlainObject(record)) {
							//prepare PKs string
							var pks_attrs_names = properties.hasOwnProperty("pks_attrs_names") ? properties["pks_attrs_names"] : "";
							pks_attrs_names = MyWidgetResourceLib.fn.isArray(pks_attrs_names) ? pks_attrs_names : pks_attrs_names.replace(/;/g, ",").split(",");
							var pks_attrs = {};
							
							for (var j = 0, tj = pks_attrs_names.length; j < tj; j++) {
								var pk_attr_name = ("" + pks_attrs_names[j]).replace(/\s+/g, "");
								
								if (pk_attr_name)
									pks_attrs[pk_attr_name] = record.hasOwnProperty(pk_attr_name) ? record[pk_attr_name] : null;
							}
							
							//prepare html
							var prev_item_elm = null;
							
			   				for (var j = 0, tj = list_items_html.length; j < tj; j++) {
								var list_item_html = list_items_html[j];
								var item_html = list_item_html.html;
								var item_parent = list_item_html.parent;
								
								//replace hashtags if apply to the current resource
								var inner_item_hash_tags_to_ignore = MyWidgetResourceLib.HashTagHandler.getWidgetResourceValueInnerItemsHtmlHashTagsBasedInData(item_html, data, idx);
								item_html = MyWidgetResourceLib.HashTagHandler.replaceHtmlHashTagsWithData(item_html, data, idx, inner_item_hash_tags_to_ignore);
								
								//append html element to item_parent
								var item_elm = null;
								
								if (list_item_html.prev && list_item_html.prev.parentNode) {
									var prev = list_item_html.prev;
									
									if (j > 0 && list_items_html[j - 1].prev == prev && prev_item_elm) //if multiple list_items_html, the previous one is the list_items_html[j - 1], otherwise the elements will appear flipped and with the wrong order.
										prev = prev_item_elm;
									
									prev.insertAdjacentHTML('afterend', item_html);
									item_elm = prev.nextElementSibling;
								}
								else if (list_item_html.next && list_item_html.next.parentNode) {
									list_item_html.next.insertAdjacentHTML('beforebegin', item_html);
									item_elm = list_item_html.next.previousElementSibling;
								}
								else {
									item_parent.insertAdjacentHTML('beforeend', item_html);
									item_elm = item_parent.lastElementChild;
								}
								
								//set PKs
								item_elm.setAttribute("data-widget-pks-attrs", JSON.stringify(pks_attrs));
								
								//save loaded data in item_elm
								MyWidgetResourceLib.FieldHandler.saveWidgetLoadedDataValues(item_elm, data, idx);
								
								//set attributes according with the resources, if apply
								MyWidgetResourceLib.FieldHandler.setWidgetDataAttributes(item_elm, data, idx);
								
								//prepare data-widget-resource-value
								MyWidgetResourceLib.FieldHandler.setWidgetDataValues(item_elm, data, idx);
								
								prev_item_elm = item_elm;
							}
						}
				   	});
				}
				
				if (is_data_empty) { //set empty message - Show empty message in all the list_items_html's parents, bc we can have list_items_html inside of data-widget-list-table and others inside of data-widget-list-tree.
					MyWidgetResourceLib.ListHandler.showEmptyHtml(elm);
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No list element in drawListData method!");
		},
		
		/*Handler to be called as a display handler of the 'Display Resource Attribute' settings. This function receives 2 arguments: a html node element and an object with data values to be displayed inside of that node element. Basically this function will loop the data objects and for each object, will display its values inside of the node element it-self, based in the parent settings. 
		Basically it will get parent node with the attribute [data-widget-item] and for each data record will create nodes based in that [data-widget-item] parent node and then append it to the main node (passed as argument in this function). This behaviour is very similar with the method 'MyWidgetResourceLib.ListHandler.loadListTableAndTreeResource' but instead of displaying a resource data, it displays a data that is passed as an argument of this function. In this case, this data could be an object inside of an attribute of a record from a resource, where the loadListTableAndTreeResource displays the records and for a specific attribute, the drawListDataRecursively method displays the sub-object inside of the correspondent element, based in the parent node with the attribute [data-widget-item]. This method should be used when we wish to display objects with others inner objects inside, like recursive tree objects, but in a recursively way, where each record (independent if is inside of another record) is always displayed with the same html. 
		This method should be used to show recursive data.
		However, although not mandatory, works better if called inside of a [data-widget-list] node.*/
		drawListDataRecursively: function(elm, data) {
			if (elm) {
				//get parent [data-widget-item]
				var parent_item_elm = elm.parentNode.closest("[data-widget-item]");
				
				if (parent_item_elm) {
					var list_items_html = null;
					var parent_list_elm = parent_item_elm.parentNode.closest("[data-widget-list]"); //Do not add table or ul in this selector, otherwise the inner children will not get the main parent, but instead some parent without the list_items_html
					
					if (parent_list_elm) {
						list_items_html = MyWidgetResourceLib.fn.getNodeElementData(parent_list_elm, "list_items_html");
						
						if (!list_items_html) { //loads the default items html
							MyWidgetResourceLib.TableHandler.prepareTableVars(parent_list_elm);
							MyWidgetResourceLib.TreeHandler.prepareTreeVars(parent_list_elm);
							MyWidgetResourceLib.ListHandler.prepareListItemsHtml(parent_list_elm);
							list_items_html = MyWidgetResourceLib.fn.getNodeElementData(parent_list_elm, "list_items_html");
						}
					}
					else {
						list_items_html = [{
							html: parent_item_elm.outerHTML,
							type: parent_item_elm.closest("table") ? "table" : "tree"
						}];
						
						//save values for inner elements with other inner elements, which will call this function recursively
						parent_item_elm.parentNode.setAttribute("data-widget-list", "");
						MyWidgetResourceLib.fn.setNodeElementData(parent_item_elm.parentNode, "list_items_html", list_items_html);
					}
					
					if (list_items_html) {
						var is_data_empty = true;
						
						//clean the contents or loading message
						var replacement_type = MyWidgetResourceLib.FieldHandler.getWidgetResourceValueReplacementType(elm);
						
						if (replacement_type == "replace")
							elm.innerHTML = "";
						else { //if (replacement_type == "prepend" || replacement_type == "append")
							var items = elm.querySelectorAll("[data-widget-empty], [data-widget-loading], [data-widget-item], [data-widget-item-add]");
							
							if (items)
								MyWidgetResourceLib.fn.each(items, function(idx, item) {
									if (item.parentNode)
										item.parentNode.removeChild(item);
								});
						}
						
						//draw data
						if (data) {
							var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
							var list_type = list_items_html && list_items_html[0] && list_items_html[0].type ? list_items_html[0].type : (parent_item_elm.closest("table") ? "table" : "tree");
							
							/* No need for loading, bc the code below draws directly the data
							//show loading
							if (parent_list_elm) {
								var table_loading_html = MyWidgetResourceLib.fn.getNodeElementData(parent_list_elm, "table_loading_html");
								var tree_loading_html = MyWidgetResourceLib.fn.getNodeElementData(parent_list_elm, "tree_loading_html");
								
								if (table_loading_html || tree_loading_html) {
									var loading_html = list_type == "tree" ? tree_loading_html : table_loading_html;
									elm.innerHTML = loading_html;
								}
							}*/
						
							//load items
						   	MyWidgetResourceLib.fn.each(data, function(idx, record) {
						   		is_data_empty = false;
						   		
						   		if (MyWidgetResourceLib.fn.isPlainObject(record)) {
									//prepare PKs string
									var pks_attrs_names = properties.hasOwnProperty("pks_attrs_names") ? properties["pks_attrs_names"] : "";
									pks_attrs_names = MyWidgetResourceLib.fn.isArray(pks_attrs_names) ? pks_attrs_names : pks_attrs_names.replace(/;/g, ",").split(",");
									var pks_attrs = {};
									
									for (var j = 0, tj = pks_attrs_names.length; j < tj; j++) {
										var pk_attr_name = ("" + pks_attrs_names[j]).replace(/\s+/g, "");
										
										if (pk_attr_name)
											pks_attrs[pk_attr_name] = record.hasOwnProperty(pk_attr_name) ? record[pk_attr_name] : null;
									}
									
									//prepare html
					   				for (var j = 0, tj = list_items_html.length; j < tj; j++) {
										var list_item_html = list_items_html[j];
										var item_html = list_item_html.html;
										
										//replace hashtags if apply to the current resource
										var inner_item_hash_tags_to_ignore = MyWidgetResourceLib.HashTagHandler.getWidgetResourceValueInnerItemsHtmlHashTagsBasedInData(item_html, data, idx);
										item_html = MyWidgetResourceLib.HashTagHandler.replaceHtmlHashTagsWithData(item_html, data, idx, inner_item_hash_tags_to_ignore);
										
										//append html element to elm
										var item_elm = null;
										
										if (replacement_type == "prepend") {
											elm.insertAdjacentHTML('afterbegin', item_html);
											item_elm = elm.firstElementChild;
										}
										else { //if (replacement_type == "append" || replacement_type == "replace")
											elm.insertAdjacentHTML('beforeend', item_html);
											item_elm = elm.lastElementChild;
										}
										
										//set PKs
										item_elm.setAttribute("data-widget-pks-attrs", JSON.stringify(pks_attrs));
										
										//save loaded data in item_elm
										MyWidgetResourceLib.FieldHandler.saveWidgetLoadedDataValues(item_elm, data, idx);
										
										//set attributes according with the resources, if apply
										MyWidgetResourceLib.FieldHandler.setWidgetDataAttributes(item_elm, data, idx);
										
										//prepare data-widget-resource-value
										MyWidgetResourceLib.FieldHandler.setWidgetDataValues(item_elm, data, idx);
									}
								}
						   	});
				   		}
				   		
				   		//show empty message
						if (is_data_empty && parent_list_elm) { //set empty message - Show empty message in all the list_items_html's parents, bc we can have list_items_html inside of data-widget-list-table and others inside of data-widget-list-tree.
							var table_empty_html = MyWidgetResourceLib.fn.getNodeElementData(parent_list_elm, "table_empty_html");
							var tree_empty_html = MyWidgetResourceLib.fn.getNodeElementData(parent_list_elm, "tree_empty_html");
							
							var empty_html = list_type == "tree" ? tree_empty_html : (table_empty_html ? table_empty_html : "");
							elm.innerHTML = empty_html;
						}
			   		}
					else
						MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No list_items_html in drawListDataRecursively method! The main parent with attribute [data-widget-list] must be init before by calling the method MyWidgetResourceLib.listHandler.loadListTableAndTreeResource.");
				}
				else
					MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No parent item element in drawListDataRecursively method! Please add the attribute [data-widget-item] to the main item.");
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No element in drawListDataRecursively method!");
		},
		
		/*Handler to be called as a display handler of the 'Display Resource Attribute' settings. 
		This function receives 1 argument: the current html node element.
		This function will get the parent node with the attribute [data-widget-list], find what is the child node with attribute [data-widget-item], save this correspondent HTML into the current node, then load the resource registered in the properties of the current node, replicating the saved HTML for each record of the loaded resource, and appending that HTML to the current node.
		Basically this function finds out what is the HTML that should be replicated for each record based in its' parents and then call the method 'MyWidgetResourceLib.ResourceHandler.loadWidgetResource' to load the registered resource based in that HTML.
		This method should be used when we wish to display records that have another children records of the same database table, but in a recursively way, where each record is always displayed with the same HTML.
		This method should be used to show recursive data from a database table.*/
		loadAndDrawListDataRecursively: function(elm) {
			if (elm) {
				//get parent [data-widget-item]
				var parent_item_elm = elm.parentNode.closest("[data-widget-item]");
				
				if (parent_item_elm) {
					var list_items_html = MyWidgetResourceLib.fn.getNodeElementData(elm, "list_items_html");
					var table_loading_html = "";
					var tree_loading_html = "";
					var table_empty_html = "";
					var tree_empty_html = "";
					
					if (!list_items_html) {
						var parent_list_elm = parent_item_elm.parentNode.closest("[data-widget-list]"); //Do not add table or ul in this selector, otherwise the inner children will not get the main parent, but instead some parent without the list_items_html
						
						if (parent_list_elm) {
							list_items_html = MyWidgetResourceLib.fn.getNodeElementData(parent_list_elm, "list_items_html");
							
							if (!list_items_html) { //loads the default items html
								MyWidgetResourceLib.TableHandler.prepareTableVars(parent_list_elm);
								MyWidgetResourceLib.TreeHandler.prepareTreeVars(parent_list_elm);
								MyWidgetResourceLib.ListHandler.prepareListItemsHtml(parent_list_elm);
								list_items_html = MyWidgetResourceLib.fn.getNodeElementData(parent_list_elm, "list_items_html");
							}
							
							table_loading_html = MyWidgetResourceLib.fn.getNodeElementData(parent_list_elm, "table_loading_html");
							tree_loading_html = MyWidgetResourceLib.fn.getNodeElementData(parent_list_elm, "tree_loading_html");
							table_empty_html = MyWidgetResourceLib.fn.getNodeElementData(parent_list_elm, "table_empty_html");
							tree_empty_html = MyWidgetResourceLib.fn.getNodeElementData(parent_list_elm, "tree_empty_html");
							
							//prepare list_items_html with new parents pointing to the elm it-self.
							if (list_items_html) {
								list_items_html = MyWidgetResourceLib.fn.assignObjectRecursively([], list_items_html);
								
								for (var j = 0, tj = list_items_html.length; j < tj; j++) {
									var list_item_html = list_items_html[j];
									list_item_html.parent = elm;
									list_item_html.prev = null;
									list_item_html.next = null;
									
									list_items_html[j] = list_item_html;
								}
							}
						}
						else
							list_items_html = [{
								html: parent_item_elm.outerHTML,
								type: parent_item_elm.closest("table") ? "table" : "tree"
							}];
					}
					
					elm.setAttribute("data-widget-list", "");
					MyWidgetResourceLib.fn.setNodeElementData(elm, "is_list_items_html_set", true);
					MyWidgetResourceLib.fn.setNodeElementData(elm, "list_items_html", list_items_html);
					MyWidgetResourceLib.fn.setNodeElementData(elm, "table_loading_html", table_loading_html);
					MyWidgetResourceLib.fn.setNodeElementData(elm, "tree_loading_html", tree_loading_html);
					MyWidgetResourceLib.fn.setNodeElementData(elm, "table_empty_html", table_empty_html);
					MyWidgetResourceLib.fn.setNodeElementData(elm, "tree_empty_html", tree_empty_html);
					
					//load new resource
					MyWidgetResourceLib.ResourceHandler.loadWidgetResource(elm);
				}
				else
					MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No parent item element in drawListDataRecursively method! Please add the attribute [data-widget-item] to the main item.");
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No element in drawListDataRecursively method!");
		},
		
		//Callback to be called on complete of a load action. In summary this handler checks if there are any search fields that were updated with new values and reload their dependencies, this is, finds inside of a form/popup, the search elements and load their dependencies. This method is used when we have comboboxes that depend on another fields, like another combobox.
		onLoadListResourceWithSelectSearchFields: function(elm, resources_name, resources_cache_key) {
			if (elm) {
				var list_items_html = MyWidgetResourceLib.fn.getNodeElementData(elm, "list_items_html");
				
				if (list_items_html) {
					var children_index = 0;
					
					for (var i = 0, t = resources_name.length; i < t; i++) {
						var resource_name = resources_name[i];
						var resource_cache_key = resources_cache_key ? resources_cache_key[i] : null;
						var data = MyWidgetResourceLib.ResourceHandler.getLoadedSLAResult(resource_name, resource_cache_key);
						
						if (data) {
							MyWidgetResourceLib.fn.each(data, function(idx, record) {
						   		if (MyWidgetResourceLib.fn.isPlainObject(record)) {
									for (var j = 0, tj = list_items_html.length; j < tj; j++) {
										var list_item_html = list_items_html[j];
										var item_parent = list_item_html.parent;
										var item_elm = item_parent.children[children_index];
										
										MyWidgetResourceLib.FieldHandler.onLoadElementFieldsResourceWithSelectSearchFields(item_elm, [resource_name], [resource_cache_key], idx);
										
										children_index++;
									}
								}
							});
						}
					}
				}
			}
		},
		
		prepareListItemsHtml: function(elm) {
			var items = elm.querySelectorAll("[data-widget-item], [data-widget-item-add]");
			var is_list_items_html_set = MyWidgetResourceLib.fn.getNodeElementData(elm, "is_list_items_html_set");
			var is_list_items_add_html_set = MyWidgetResourceLib.fn.getNodeElementData(elm, "is_list_items_add_html_set");
			
			if (!is_list_items_html_set || !is_list_items_add_html_set) {
				var list_items_html = MyWidgetResourceLib.fn.getNodeElementData(elm, "list_items_html");
				var list_items_add_html = MyWidgetResourceLib.fn.getNodeElementData(elm, "list_items_add_html");
				
				if (!is_list_items_html_set)
					list_items_html = new Array();
				
				if (!is_list_items_add_html_set)
					list_items_add_html = new Array();
				
				if (items) {
					//get siblings that are not reserved siblings
					var get_sibling_handler = function(type, item) {
						var sibling = type = "prev" ? item.previousElementSibling : item.nextElementSibling;
						
						if (sibling && (sibling.hasAttribute("data-widget-item") || sibling.hasAttribute("data-widget-item-add") || sibling.hasAttribute("data-widget-empty") || sibling.hasAttribute("data-widget-loading")))
							sibling = get_sibling_handler(type, sibling);
						
						return sibling;
					};
					
					//prepare items
					for (var i = 0, t = items.length; i < t; i++) {
						var item = items[i];
						var item_original_html = item.outerHTML;
						
						//check if item needs to be inited (this is, if contains any attribute data-widget-item-resources-load) and if yes, inited that html, but before anything else...
						if (!item.classList.contains("template-widget") && (item.hasAttribute("data-widget-item-resources-load") || item.hasAttribute("data-widget-resources-load"))) {
							item.removeAttribute("data-widget-item-resources-load");
							item.removeAttribute("data-widget-resources-load");
							
							MyWidgetResourceLib.ResourceHandler.loadWidgetResource(item, {async: false}); //must be async false otherwise we will loose the the real attribute values, when the load function gets executed
						}
						
						var item_widgets = item.querySelectorAll("[data-widget-item-resources-load]:not(.template-widget), [data-widget-resources-load]:not(.template-widget)");
						
						if (item_widgets)
							MyWidgetResourceLib.fn.each(item_widgets, function (idx, item_widget) { //:not(.template-widget) is very important so it doesn't load the resource when we are editing this widget through the LayoutUIEditor.
								item_widget.removeAttribute("data-widget-item-resources-load");
								item_widget.removeAttribute("data-widget-resources-load");
								
								MyWidgetResourceLib.ResourceHandler.loadWidgetResource(item_widget, {async: false}); //must be async false otherwise we will loose the the real attribute values, when the load function gets executed
							});
						
						//save default item html
						item.setAttribute("data-widget-item-loaded", "");
						var item_new_html = item.outerHTML;
						var table_or_tree = item.parentNode.closest("[data-widget-list-table], [data-widget-list-tree], [data-widget-list]");
						var table_or_tree_type = (table_or_tree && table_or_tree.hasAttribute("data-widget-list-tree")) || elm.nodeName == "UL" ? "tree" : "table";
						//var prev = item.prev;
						
						if (!is_list_items_html_set && item.hasAttribute("data-widget-item"))
							list_items_html.push({
								html: item_new_html,
								original_html: item_original_html,
								type: table_or_tree_type,
								parent: item.parentNode,
								prev: get_sibling_handler("prev", item),
								next: get_sibling_handler("next", item)
							});
						
						if (!is_list_items_add_html_set && item.hasAttribute("data-widget-item-add"))
							list_items_add_html.push({
								html: item_new_html,
								original_html: item_original_html,
								type: table_or_tree_type,
								parent: item.parentNode,
								prev: get_sibling_handler("prev", item),
								next: get_sibling_handler("next", item)
							});
						
						item.parentNode.removeChild(item); //remove default item
					}
				}
				
				if (!is_list_items_html_set)
					MyWidgetResourceLib.fn.setNodeElementData(elm, "list_items_html", list_items_html);
				
				if (!is_list_items_add_html_set)
					MyWidgetResourceLib.fn.setNodeElementData(elm, "list_items_add_html", list_items_add_html);
				
				MyWidgetResourceLib.fn.setNodeElementData(elm, "is_list_items_html_set", true);
				MyWidgetResourceLib.fn.setNodeElementData(elm, "is_list_items_add_html_set", true);
			}
		},
		
		showLoadingHtml: function(elm) {
			var table_loading_html = MyWidgetResourceLib.fn.getNodeElementData(elm, "table_loading_html");
			var tree_loading_html = MyWidgetResourceLib.fn.getNodeElementData(elm, "tree_loading_html");
			
			if (table_loading_html || tree_loading_html) {
				var list_items_html = MyWidgetResourceLib.fn.getNodeElementData(elm, "list_items_html");
				var repeated_parents = [];
				
				if (list_items_html)
					for (var i = 0, ti = list_items_html.length; i < ti; i++) {
						var list_item_html = list_items_html[i];
						var parent = list_item_html.parent;
						
						if (parent && repeated_parents.indexOf(parent) == -1) {
							repeated_parents.push(parent);
							
							var loading_html = list_item_html.type == "tree" ? tree_loading_html : table_loading_html;
							
							if (loading_html) {
								if (list_item_html.prev && list_item_html.prev.parentNode)
									list_item_html.prev.insertAdjacentHTML('afterend', loading_html);
								else if (list_item_html.next && list_item_html.next.parentNode)
									list_item_html.next.insertAdjacentHTML('beforebegin', loading_html);
								else
									parent.insertAdjacentHTML('beforeend', loading_html);
							}
						}
					}
			}
		},
		
		showEmptyHtml: function(elm) {
			var table_empty_html = MyWidgetResourceLib.fn.getNodeElementData(elm, "table_empty_html");
			var tree_empty_html = MyWidgetResourceLib.fn.getNodeElementData(elm, "tree_empty_html");
			
			if (table_empty_html || tree_empty_html) {
				var list_items_html = MyWidgetResourceLib.fn.getNodeElementData(elm, "list_items_html");
				var repeated_parents = [];
				
				if (list_items_html)
					for (var i = 0, t = list_items_html.length; i < t; i++) {
						var list_item_html = list_items_html[i];
						var parent = list_item_html.parent;
						
						if (parent && repeated_parents.indexOf(parent) == -1) {
							repeated_parents.push(parent);
							//MyWidgetResourceLib.ListHandler.cleanListElementChildren(parent); //already done above
							
							var empty_html = list_item_html.type == "tree" ? tree_empty_html : (table_empty_html ? table_empty_html : "");
							
							if (empty_html) {
								if (list_item_html.prev && list_item_html.prev.parentNode)
									list_item_html.prev.insertAdjacentHTML('afterend', empty_html);
								else if (list_item_html.next && list_item_html.next.parentNode)
									list_item_html.next.insertAdjacentHTML('beforebegin', empty_html);
								else
									parent.insertAdjacentHTML('beforeend', empty_html);
							}
						}
					}
			}
		},
		
		cleanListItems: function(elm) {
			//clean the loading message
			var list_items_html = MyWidgetResourceLib.fn.getNodeElementData(elm, "list_items_html");
			var repeated_parents = [];
			
			if (list_items_html)
				for (var i = 0, t = list_items_html.length; i < t; i++) {
					var list_item_html = list_items_html[i];
					var parent = list_item_html.parent;
					
					if (parent && repeated_parents.indexOf(parent) == -1) {
						repeated_parents.push(parent);
						MyWidgetResourceLib.ListHandler.cleanListElementChildren(parent);
					}
				}
		},
		
		cleanListElementChildren : function(elm) {
			if (elm)
				for (var j = elm.children.length - 1; j >= 0; j--) {
					var child = elm.children[j];
					
					if (child && (child.hasAttribute("data-widget-item") || child.hasAttribute("data-widget-item-add") || child.hasAttribute("data-widget-empty") || child.hasAttribute("data-widget-loading")))
						elm.removeChild(child);
				}
		},
		
		getListTableElement: function(elm) {
			return elm.nodeName.toUpperCase() == "TABLE" || elm.hasAttribute("data-widget-list-table") ? elm : elm.querySelector("[data-widget-list-table], table");
		},
		
		getListTreeElement: function(elm) {
			var tree_elm = elm.nodeName.toUpperCase() == "UL" || elm.hasAttribute("data-widget-list-tree") ? elm : elm.querySelector("[data-widget-list-tree]");
			
			if (!tree_elm)
				for (var i = 0, t = elm.children.length; i < t; i++)
					if (elm.children[i].nodeName.toUpperCase() == "UL") {
						tree_elm = elm.children[i];
						break;
					}
			
			return tree_elm;
		},
		
		getLoadingHtml: function(elm, is_tree) {
			var html = "";
			var tree_or_table_elm = is_tree ? this.getListTreeElement(elm) : this.getListTableElement(elm);
			var items = tree_or_table_elm ? tree_or_table_elm.querySelectorAll("[data-widget-loading]") : null;
			
			if (items)
				for (var i = 0, t = items.length; i < t; i++) {
					var item = items[i];
					var item_original_html = item.outerHTML;
					
					if (("" + item.innerHTML).replace(/\s/g, "") == "")
						continue;
					
					//check if item needs to be inited (this is, if contains any attribute data-widget-item-resources-load) and if yes, inited that html, but before anything else...
					if (!item.classList.contains("template-widget") && (item.hasAttribute("data-widget-item-resources-load") || item.hasAttribute("data-widget-resources-load"))) {
						item.removeAttribute("data-widget-item-resources-load");
						item.removeAttribute("data-widget-resources-load");
						
						MyWidgetResourceLib.ResourceHandler.loadWidgetResource(item, {async: false}); //must be async false otherwise we will loose the the real attribute values, when the load function gets executed
					}
					
					var item_widgets = item.querySelectorAll("[data-widget-item-resources-load]:not(.template-widget), [data-widget-resources-load]:not(.template-widget)");
					
					if (item_widgets)
						MyWidgetResourceLib.fn.each(item_widgets, function (idx, item_widget) { //:not(.template-widget) is very important so it doesn't load the resource when we are editing this widget through the LayoutUIEditor.
							item_widget.removeAttribute("data-widget-item-resources-load");
							item_widget.removeAttribute("data-widget-resources-load");
							
							MyWidgetResourceLib.ResourceHandler.loadWidgetResource(item_widget, {async: false}); //must be async false otherwise we will loose the the real attribute values, when the load function gets executed
						});
					
					//show item (bc is hidden in css) and then save it
					MyWidgetResourceLib.fn.show(item);
					html += item.outerHTML;
					
					item.parentNode.removeChild(item); //remove default item
				}
			
			if (!html) {
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				
				if (properties["loading_message"]) {
					if (is_tree)
						html = '<li data-widget-loading style="display:initial">' + properties["loading_message"] + '</li>';
					else {
						//get columns count for the td->colspan
						var count = 0;
						var trs = elm.querySelectorAll("tr:not([data-widget-item])"); //Do not add thead in the selector, bc the th/td can be inside of tbody
						
						if (trs)
							for (var i = 0, t = trs.length; i < t; i++) {
								var c = trs[i].querySelectorAll("th, td").length;
								
								if (c > count)
									count = c;
							}
						
						//style="display:initial" simulates the same than: MyWidgetResourceLib.fn.show(item);
						html = '<tr data-widget-loading style="display:initial"><td colspan="' + count + '">' + properties["loading_message"] + '</td></tr>';
					}
				}
			}
			
			return html;
		},
		
		getEmptyHtml: function(elm, is_tree) {
			var html = "";
			var tree_or_table_elm = is_tree ? this.getListTreeElement(elm) : this.getListTableElement(elm);
			var items = tree_or_table_elm ? tree_or_table_elm.querySelectorAll("[data-widget-empty]") : null;
			
			if (items)
				for (var i = 0, t = items.length; i < t; i++) {
					var item = items[i];
					var item_original_html = item.outerHTML;
					
					if (("" + item.innerHTML).replace(/\s/g, "") == "")
						continue;
					
					//check if item needs to be inited (this is, if contains any attribute data-widget-item-resources-load) and if yes, inited that html, but before anything else...
					if (!item.classList.contains("template-widget") && (item.hasAttribute("data-widget-item-resources-load") || item.hasAttribute("data-widget-resources-load"))) {
						item.removeAttribute("data-widget-item-resources-load");
						item.removeAttribute("data-widget-resources-load");
						
						MyWidgetResourceLib.ResourceHandler.loadWidgetResource(item, {async: false}); //must be async false otherwise we will loose the the real attribute values, when the load function gets executed
					}
					
					var item_widgets = item.querySelectorAll("[data-widget-item-resources-load]:not(.template-widget), [data-widget-resources-load]:not(.template-widget)");
					
					if (item_widgets)
						MyWidgetResourceLib.fn.each(item_widgets, function (idx, item_widget) { //:not(.template-widget) is very important so it doesn't load the resource when we are editing this widget through the LayoutUIEditor.
							item_widget.removeAttribute("data-widget-item-resources-load");
							item_widget.removeAttribute("data-widget-resources-load");
							
							MyWidgetResourceLib.ResourceHandler.loadWidgetResource(item_widget, {async: false}); //must be async false otherwise we will loose the the real attribute values, when the load function gets executed
						});
					
					//show item (bc is hidden in css) and then save it
					MyWidgetResourceLib.fn.show(item);
					html += item.outerHTML;
					
					item.parentNode.removeChild(item); //remove default item
				}
			
			if (!html) {
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				
				if (properties["empty_message"]) {
					if (is_tree)
						html = '<li data-widget-empty style="display:initial">' + properties["empty_message"] + '</li>';
					else {
						//get columns count for the td->colspan
						var count = 0;
						var trs = elm.querySelectorAll("tr:not([data-widget-item])"); //Do not add thead in the selector, bc the th/td can be inside of tbody
						
						if (trs)
							for (var i = 0, t = trs.length; i < t; i++) {
								var c = trs[i].querySelectorAll("th, td").length;
								
								if (c > count)
									count = c;
							}
						
						//style="display:initial" simulates the same than: MyWidgetResourceLib.fn.show(item);
						html = '<tr data-widget-empty style="display:initial"><td colspan="' + count + '">' + properties["empty_message"] + '</td></tr>';
					}
				}
			}
			
			return html;
		},
		
		//Sort a list column based a attribute name. Note that element that executes this function must have the 'data-widget-item-attribute-name' attribute defined with the correspondent attribute name to be sorted.
		sortListResource: function(elm, event) {
			event = event ? event : window.event;
			event.stopPropagation();
			
			if (elm) {
				var td = elm.closest("[data-widget-item-attribute-name]");
				var attr_name = td ? td.getAttribute("data-widget-item-attribute-name") : null;
				
				if (attr_name) {
					var widget = elm.parentNode.closest("[data-widget-list]");
					var sort_attrs = MyWidgetResourceLib.fn.getNodeElementData(widget, "sort_attrs");
					var sort_type = elm.classList.contains("asc") ? "desc" : "asc";
					
					if (!MyWidgetResourceLib.fn.isPlainObject(sort_attrs))
						sort_attrs = {};
					
					sort_attrs[attr_name] = sort_type;
					MyWidgetResourceLib.fn.setNodeElementData(widget, "sort_attrs", sort_attrs);
					
					//execute sort based in the sort_type var
					MyWidgetResourceLib.ResourceHandler.loadWidgetResource(widget);
					
					if (sort_type == "asc") {
						elm.classList.add("asc");
						elm.classList.remove("desc");
					}
					else {
						elm.classList.add("desc");
						elm.classList.remove("asc");
					}
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No td element in sortListResource method!");
		},
		
		resetListResourceSort: function(elm) {
			if (elm && elm.hasAttribute("data-widget-list")) {
				MyWidgetResourceLib.fn.setNodeElementData(elm, "sort_attrs", null);
				
				//execute sort based in the sort_type var
				MyWidgetResourceLib.ResourceHandler.loadWidgetResource(elm);
				
				//remove classes
				var tds = elm.querySelectorAll("[data-widget-item-head]");
				
				if (tds)
					MyWidgetResourceLib.fn.each(tds, function(idx, td) {
						var td = td.hasAttribute("data-widget-item-attribute-name") ? td : td.querySelector("[data-widget-item-attribute-name]");
						
						if (td) {
							td.classList.remove("asc");
							td.classList.remove("desc");
						}
					});
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No widget list in resetListResourceSort method!");
		},
		
		//Reset previous sorting for a specific column/attribute.
		resetListResourceSortAttribute: function(elm, event) {
			event = event ? event : window.event;
			event.stopPropagation();
			
			if (elm) {
				var td = elm.closest("[data-widget-item-attribute-name]");
				var attr_name = td ? td.getAttribute("data-widget-item-attribute-name") : null;
				
				if (attr_name) {
					var widget = td.parentNode.closest("[data-widget-list]");
					var sort_attrs = widget ? MyWidgetResourceLib.fn.getNodeElementData(widget, "sort_attrs") : null;
					
					if (attr_name && MyWidgetResourceLib.fn.isPlainObject(sort_attrs) && sort_attrs.hasOwnProperty(attr_name)) {
						sort_attrs[attr_name] = null;
						delete sort_attrs[attr_name];
						
						MyWidgetResourceLib.fn.setNodeElementData(widget, "sort_attrs", sort_attrs);
						
						//execute sort based in the sort_type var
						MyWidgetResourceLib.ResourceHandler.loadWidgetResource(widget);
						
						//remove classes
						td.classList.remove("asc");
						td.classList.remove("desc");
					}
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No element in resetListResourceSortAttribute method!");
		},
		
		toggleListTableAndTree: function(elm) {
			if (elm && elm.hasAttribute("data-widget-list")) {
				var table = this.getListTableElement(elm);
				var tree = this.getListTreeElement(elm);
				
				if (!table && tree)
					table = MyWidgetResourceLib.TreeHandler.convertTreeIntoTable(tree);
				
				if (table && !tree)
					tree = MyWidgetResourceLib.TableHandler.convertTableIntoTree(table);
				
				if (table && tree && table != tree) {
					if (MyWidgetResourceLib.fn.isVisible(table)) {
						MyWidgetResourceLib.fn.hide(table);
						MyWidgetResourceLib.fn.show(tree);
						
						MyWidgetResourceLib.PermissionHandler.loadWidgetPermissions(tree);
					}
					else {
						MyWidgetResourceLib.fn.show(table);
						MyWidgetResourceLib.fn.hide(tree);
						
						MyWidgetResourceLib.PermissionHandler.loadWidgetPermissions(table);
					}
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No widget list in toggleListTableAndTree method!");
		},
		
		//Add an inline item inside of the current list.
		addInlineResourceListItem: function(elm) {
			if (elm) {
				var widget = elm.closest("[data-widget-list]");
				
				if (!widget)
					widget = elm.closest("[data-widget-form], form");
				
				if (widget) {
					if (widget.hasAttribute("data-widget-list")) {
						var list_items_html = MyWidgetResourceLib.fn.getNodeElementData(widget, "list_items_add_html");
						
						if (list_items_html) {
							for (var i = 0, t = list_items_html.length; i < t; i++) {
								var list_item_html = list_items_html[i];
								var item_html = list_item_html.html;
								var item_parent = list_item_html.parent;
								
								//append html element to item_parent
								item_parent.insertAdjacentHTML('beforeend', item_html);
								/*var item_elm = item_parent.lastElementChild;
								
								var view_fields = item_elm.querySelectorAll("[data-widget-item-attribute-field-view], [data-widget-item-attribute-link-view], [data-widget-item-attribute-field-edit], [data-widget-item-attribute-link-edit], [data-widget-item-button-view], [data-widget-item-button-edit], [data-widget-item-button-update]");
								var edit_fields = item_elm.querySelectorAll("[data-widget-item-attribute-field-add], [data-widget-item-button-add], [data-widget-item-button-add-cancel]");
								
								if (edit_fields)
									//TODO: find a way to loop asyncronously
									MyWidgetResourceLib.fn.each(edit_fields, function(idx, item) {
										MyWidgetResourceLib.fn.show(item);
									});
								
								if (view_fields)
									MyWidgetResourceLib.fn.each(view_fields, function(idx, item) {
										MyWidgetResourceLib.fn.hide(item);
									});*/
							}
							
							var empty_items = widget.querySelectorAll("[data-widget-empty]");
							
							if (empty_items)
								for (var i = 0, t = empty_items.length; i < t; i++)
									MyWidgetResourceLib.fn.hide(empty_items[i]);
						}
					}
					else { //if (widget.hasAttribute("data-widget-form") || widget.nodeName.toUpperCase() == "FORM") {
						widget.classList.add("show-add-fields");
						
						//reset form but only the data-widget-item-attribute-field-add fields
						var inputs = widget.querySelectorAll("[data-widget-item-attribute-field-add]");
						
						if (inputs)
							//TODO: find a way to loop asyncronously
							MyWidgetResourceLib.fn.each(inputs, function(idx, input) {
								if (input.type == "checkbox" || input.type == "radio")
									input.checked = false;
								else
									input.value = "";
							});
					}
				}
				else
					MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No widget in addInlineResourceListItem method!");
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No element in addInlineResourceListItem method!");
		},
		
		//Toggle selection in the loaded items of the current list.
		toggleListAttributeSelectCheckboxes: function(elm) {
			if (elm) {
				var is_checked = elm.checked;
				var widget = elm.parentNode.closest("[data-widget-list], [data-widget-list-table], [data-widget-list-tree], table");
				var inputs = widget ? widget.querySelectorAll("input[type=checkbox][data-widget-item-selected-checkbox]") : null;
				
				if (inputs)
					//TODO: find a way to loop asyncronously
					MyWidgetResourceLib.fn.each(inputs, function(idx, input) {
						if (input != elm) {
							if (is_checked) {
								input.setAttribute("checked", "checked");
								input.checked = true;
							}
							else {
								input.removeAttribute("checked");
								input.checked = false;
							}
						}
					});
			}
		},
		
		getPaginationWidgets: function(elm) {
			var group_elm = elm.parentNode.closest("[data-widget-group-list]");
			return group_elm ? group_elm.querySelectorAll("[data-widget-pagination]") : null;
		},
		
		//if there are available values, get the correspondent pks for the found items.
		prepareSearchAttrs: function(search_attrs, search_types, search_cases, list_items_html) {
			if (search_attrs && MyWidgetResourceLib.fn.isPlainObject(search_attrs) && list_items_html.length > 0) {
				var html = "";
				
				for (var i = 0, t = list_items_html.length; i < t; i++) {
					var list_item_html = list_items_html[i];
					html += list_item_html.html;
				}
				
				var div = document.createElement("div");
				div.innerHTML = html;
				
				var items = div.querySelectorAll("[data-widget-resource-value]");
				var items_avs = {};
				
				if (items)
					for (var i = 0, t = items.length; i < t; i++) {
						var item = items[i];
						var widget_resource_value = MyWidgetResourceLib.FieldHandler.getWidgetResourceValueObj(item);
					
						if (widget_resource_value && widget_resource_value.hasOwnProperty("available_values") && widget_resource_value["available_values"]) {
							var attr_name = MyWidgetResourceLib.FieldHandler.getWidgetResourceValueAttributeName(item, {force_input_name: true});
							
							if (search_attrs.hasOwnProperty(attr_name) && ("" + search_attrs[attr_name]).length > 0) {
								var available_values = MyWidgetResourceLib.FieldHandler.getWidgetResourceValueAvailableValues(item);
								
								if (available_values && MyWidgetResourceLib.fn.isPlainObject(available_values) && !MyWidgetResourceLib.fn.isEmptyObject(available_values))
									items_avs[attr_name] = available_values;
							}
						}
					}
				
				for (var attr_name in search_attrs) {
					var av = items_avs[attr_name];
					
					if (av) {
						var search_type = MyWidgetResourceLib.fn.isPlainObject(search_types) ? search_types[attr_name] : search_types;
						var search_case = MyWidgetResourceLib.fn.isPlainObject(search_cases) ? search_cases[attr_name] : search_cases;
						var search_value = search_attrs[attr_name];
						var new_search_value = [];
						
						for (var key in av) {
							var value = av[key];
							var found_in_column = MyWidgetResourceLib.StaticListHandler.searchValueFoundInColumnValue(key, search_value, search_type, search_case) || MyWidgetResourceLib.StaticListHandler.searchValueFoundInColumnValue(value, search_value, search_type, search_case);
							
							if (found_in_column)
								new_search_value.push(key);
						}
						
						if (new_search_value.length > 0) {
							search_attrs[attr_name] = new_search_value;
							
							if (MyWidgetResourceLib.fn.isPlainObject(search_types))
								search_types[attr_name] = "in";
							else {
								search_types = {};
								
								for (var search_attr_name in search_attrs)
									search_types[search_attr_name] = search_attr_name == attr_name ? "in" : search_type;
							}
						}
					}
				}
				
				//clean memory
				div.innerHTML = "";
				delete div;
			}
			
			return {
				search_attrs: search_attrs,
				search_types: search_types
			};
		},
		
		//Handler to be called on success of an add action. In summary, this handler reloads the data from the parent widget.
		onAddResourceItem: function(elm) {
			var item = elm.closest("[data-widget-item], [data-widget-item-add]");
			var opts = {add_item: true};
			
			if (item) {
				var item_parent = item.parentNode;
				
				if (item_parent)
					item_parent.removeChild(item);
				
				MyWidgetResourceLib.ListHandler.reloadParentListResource(item_parent, opts);
			}
			else
				MyWidgetResourceLib.ListHandler.reloadParentListResource(elm, opts);
		},
		
		//Handler to be called on success of an update action. In summary, this handler calls the updateViewFieldsBasedInEditFields method.
		onUpdateResourceItem: function(elm) {
			MyWidgetResourceLib.ItemHandler.updateViewFieldsBasedInEditFields(elm);
			
			//purge cache from parent
			MyWidgetResourceLib.ListHandler.purgeCachedLoadParentListResource(elm);
		},
		
		//Handler to be called on success of an update attribute action. In summary, this handler calls the updateViewFieldsBasedInEditFields method.
		onUpdateResourceItemAttribute: function(elm) {
			MyWidgetResourceLib.ItemHandler.updateViewFieldsBasedInEditFields(elm);
			
			//purge cache from parent
			MyWidgetResourceLib.ListHandler.purgeCachedLoadParentListResource(elm);
		},
		
		//Handler to be called on success of a removal action. In summary, this handler deletes the removed item from the parent widget.
		onRemoveResourceItem: function(elm) {
			var item = elm.parentNode.closest("[data-widget-item]");
			
			if (item) {
				var item_parent = item.parentNode;
				
				if (item_parent) {
					var search_attrs_str = item.getAttribute("data-widget-pks-attrs");
					
					item_parent.removeChild(item);
					
					//remove other items with the same search_attrs
					var items = item_parent.querySelectorAll("[data-widget-item]");
					var is_page_empty = true;
					
					if (items)
						for (var i = 0, t = items.length; i < t; i++) {
							var item = items[i];
							
							if (item.getAttribute("data-widget-pks-attrs") == search_attrs_str)
								item.parentNode.removeChild(item);
							else
								is_page_empty = false;
						}
					
					//purge cache from parent
					MyWidgetResourceLib.ListHandler.purgeCachedLoadParentListResource(item_parent);
					
					//check if page is empty, and if yes, reload list.
					if (is_page_empty) {
						var widget = item_parent.closest("[data-widget-list]");
						
						if (widget) {
							//When we delete a record from the Edit Popup, we cannot replicate this behaviour, so we simply refresh the list. However If I find any way to replicate the code below that is commented, in the Edit Popup, then I should uncomment the code below.
							MyWidgetResourceLib.ListHandler.loadListResource(widget, {force: true});
							
							/*var page_number = MyWidgetResourceLib.fn.getNodeElementData(widget, "page_number");
							page_number = page_number - 1;
							page_number = page_number > 0 ? page_number : 0;
							MyWidgetResourceLib.fn.setNodeElementData(widget, "page_number", page_number);
							
							MyWidgetResourceLib.fn.setNodeElementData(widget, "page_number", page_number);
							
							var pagination_elms = MyWidgetResourceLib.ListHandler.getPaginationWidgets(widget);
							
							if (pagination_elms)
								for (var i = 0, t = pagination_elms.length; i < t; i++)
									MyWidgetResourceLib.fn.setNodeElementData(pagination_elms[i], "page_number", page_number);
							
							MyWidgetResourceLib.ListHandler.reloadParentListResource(widget);*/
						}
					}
				}
			}
		},
	});
	/****************************************************************************************
	 *				 END: LIST HANDLER 					*
	 ****************************************************************************************/
		
	/****************************************************************************************
	 *				 START: STATIC LIST HANDLER 					*
	 ****************************************************************************************/
	MyWidgetResourceLib.StaticListHandler = MyWidgetResourceLib.fn.StaticListHandler = ({
		
		/*
		 * sort_attrs: {attribute_name: asc or desc}
		 */
		sortItems: function(elm, sort_attrs) {
			//prepare vars
			if (typeof sort_attrs == "string") {
				var attribute_name = sort_attrs;
				
				sort_attrs = {};
				sort_attrs[attribute_name] = true;
			}
			else if (!MyWidgetResourceLib.fn.isPlainObject(sort_attrs))
				sort_attrs = {};
			
			//prepare pagination
			var pagination_vars = this.getPaginationVars(elm);
			var start_index = pagination_vars["start_index"];
			var end_index = pagination_vars["end_index"];
			var exist_pagination = MyWidgetResourceLib.fn.isNumeric(start_index);
			var items_index = 0;
			
			//get items' parents
			var parents = this.getListItemsParents(elm);
			
			if (MyWidgetResourceLib.fn.isEmptyObject(sort_attrs)) {
				//set original order for parents children
				for (var i = 0, ti = parents.length; i < ti; i++) {
					var parent = parents[i];
					var items = parent.children;
					var t = items.length;
					var sorted_items = {};
					var other_items = [];
					
					for (var j = 0; j < t; j++) {
						var item = items[j];
						var original_item_index = MyWidgetResourceLib.fn.getNodeElementData(item, "original_item_index");
						
						if (original_item_index >= 0)
							sorted_items[original_item_index] = item;
						else
							other_items.push(item);
					}
					
					for (var j = 0; j < t; j++)
						if (sorted_items.hasOwnProperty(j)) {
							var item = sorted_items[j];
							
							parent.appendChild(item);
							
							//show or hide item according with pagination
							if (exist_pagination) {
								if (items_index >= start_index && items_index <= end_index)
									MyWidgetResourceLib.fn.show(item);
								else
									MyWidgetResourceLib.fn.hide(item);
							}
							
							items_index++;
						}
					
					for (var j = 0, tj = other_items.length; j < tj; j++) {
						var item = other_items[j];
						
						parent.appendChild(item);
						
						//show or hide item according with pagination
						if (exist_pagination) {
							if (items_index >= start_index && items_index <= end_index)
								MyWidgetResourceLib.fn.show(item);
							else
								MyWidgetResourceLib.fn.hide(item);
						}
						
						items_index++;
					}
				}
			}
			else {
				//set compare functions
				var getElmItemValue = function(item, column_index) { 
					var column = item.children[column_index];
					return MyWidgetResourceLib.StaticListHandler.getItemValue(column);
				};
				var compareValues = function(v1, v2) {
			   		return v1 !== '' && v2 !== '' && !isNaN(v1) && !isNaN(v2) ? v1 - v2 : v1.toString().localeCompare(v2);
		    		};
				var comparer = function(sort_attrs_by_index) { 
					return function(row_1, row_2) { 
						for (var column_index in sort_attrs_by_index) {
							var asc = sort_attrs_by_index[column_index];
							var row_1_column_value = getElmItemValue(asc ? row_1 : row_2, column_index);
							var row_2_column_value = getElmItemValue(asc ? row_2 : row_1, column_index);
							
							var r = compareValues(row_1_column_value, row_2_column_value);
					    		
					    		if (r != 0)
					    			return r;
				    		}
				    		
						return 0;
					}
				};
				
				//sort parents children
				for (var i = 0, ti = parents.length; i < ti; i++) {
					var parent = parents[i];
					var items = parent.children;
					
					//save items original order - bc of the resetSortAttrs, we must save the initial rows index, so when the sort_attrs be empty, we know what is the original order.
					var original_item_index = MyWidgetResourceLib.fn.getNodeElementData(arr[0], "original_item_index");
					
					if (!MyWidgetResourceLib.fn.isNumeric(original_item_index))
						for (var j = 0, tj = items.length; j < tj; j++)
							MyWidgetResourceLib.fn.setNodeElementData(items[j], "original_item_index", j);
					
					//get column index for attribute names
					var sort_attrs_by_index = this.convertDataByAttributesToDataByColumnIndexes(sort_attrs);
					
					//sort items
					if (!MyWidgetResourceLib.fn.isEmptyObject(sort_attrs_by_index)) {
						var arr = Array.from(items);
						arr.sort(comparer(sort_attrs_by_index))
						  .forEach(function(item) { 
						  	parent.appendChild(item);
						  });
						 
						 for (var j = 0, tj = items.length; j < tj; j++) {
						 	var item = items[j];
						 	
						 	//show or hide item according with pagination
							if (exist_pagination) {
								if (items_index >= start_index && items_index <= end_index)
									MyWidgetResourceLib.fn.show(item);
								else
									MyWidgetResourceLib.fn.hide(item);
							}
						 	
						 	items_index++;
						 }
					}
				}
			}
		},
		
		/*
		 * search_attrs: {attribute_name: 'string to search'} or {attribute_name: {value: 'string to search', type: 'contains'}}
		 * search_types: 'contains|starts_with|ends_with' or {attribute_name: 'contains|starts_with|ends_with'} (default: contains)
		 * search_cases: 'sensitive|insensitive' (default: sensitive)
		 * search_operators: 'or|and' (default: and)
		 */
		searchItems: function(elm, search_attrs, search_types, search_cases, search_operators) {
			//get items' parents
			var parents = this.getListItemsParents(elm);
			
			//prepare search data
			var search_data = {};
			var exist_search_value = false;
			
			if (MyWidgetResourceLib.fn.isPlainObject(search_attrs))
				for (var attribute_name in search_attrs) {
					var search_value = search_attrs[attribute_name];
					var search_type = MyWidgetResourceLib.fn.isPlainObject(search_types) ? search_types[attribute_name] : search_types;
					var search_case = MyWidgetResourceLib.fn.isPlainObject(search_cases) ? search_cases[attribute_name] : search_cases;
					var search_operator = MyWidgetResourceLib.fn.isPlainObject(search_operators) ? search_operators[attribute_name] : search_operators;
					
					if (MyWidgetResourceLib.fn.isPlainObject(search_value)) {
						if (search_value.hasOwnProperty("type"))
							search_type = search_value["type"];
						
						if (search_value.hasOwnProperty("case"))
							search_case = search_value["case"];
						
						if (search_value.hasOwnProperty("operator"))
							search_operator = search_value["operator"];
						
						search_value = search_value.hasOwnProperty("value") ? search_value["value"] : "";
					}
					
					if (MyWidgetResourceLib.fn.isNumeric(search_value) || (search_value && ("" + search_value).replace(/\s+/g, "") != ""))
						exist_search_value = true;
					
					search_data[attribute_name] = {
						value: search_value || MyWidgetResourceLib.fn.isNumeric(search_value) ? ("" + search_value).toLowerCase() : "",
						type: search_type ? ("" + search_type).toLowerCase() : "",
						"case": search_case ? ("" + search_case).toLowerCase() : "",
						operator: search_operator ? ("" + search_operator).toLowerCase() : ""
					};
				}
			
			//prepare pagination
			var pagination_vars = this.getPaginationVars(elm);
			var start_index = pagination_vars["start_index"];
			var end_index = pagination_vars["end_index"];
			var exist_pagination = MyWidgetResourceLib.fn.isNumeric(start_index);
			var items_index = 0;
			
			//hide pagination - when we search the pagination need to disappear. Only shows if exists too many searched items...
			var pagination_elms = MyWidgetResourceLib.ListHandler.getPaginationWidgets(elm);
			
			if (pagination_elms)
				for (var i = 0, t = pagination_elms.length; i < t; i++)
					MyWidgetResourceLib.fn.hide(pagination_elms[i]);
			
			//execute search
			if (!exist_search_value) { //show all items
				for (var i = 0, ti = parents.length; i < ti; i++) {
					var parent = parents[i];
					var items = parent.children;
					
					for (var j = 0, tj = items.length; j < tj; j++) {
						var item = items[j];
						
						//only show item if inside of selected pagination
						if (exist_pagination) {
							if (items_index >= start_index && items_index <= end_index)
								MyWidgetResourceLib.fn.show(item);
							else
								MyWidgetResourceLib.fn.hide(item);
						}
						else
							MyWidgetResourceLib.fn.show(item);
						
						items_index++;
					}
				}
			}
			else {
				//get column index for attribute names
				var search_attrs_by_index = this.convertDataByAttributesToDataByColumnIndexes(search_data);
				
				//search parents children
				for (var i = 0, ti = parents.length; i < ti; i++) {
					var parent = parents[i];
					var items = parent.children;
					
					for (var j = 0, tj = items.length; j < tj; j++) {
						var item = items[j];
						var columns = item.querySelectorAll("[data-widget-item-column]");
						var found = false;
						
						columns = columns ? columns : [];
						
						for (var column_index in search_attrs_by_index)
							if (column_index < columns.length) {
								var aux = search_data[column_index];
								var search_value = aux["value"];
								var search_type = aux["type"];
								var search_case = aux["case"];
								var search_operator = aux["operator"];
								var found_in_column = false;
								
								if (search_value.length == 0) 
									found_in_column = true;
								else {
									var column = columns[column_index];
									var column_value = MyWidgetResourceLib.StaticListHandler.getItemValue(column);
									
									found_in_column = this.searchValueFoundInColumnValue(column_value, search_value, search_type, search_case);
									
									//search based in label, instead of id, if available values exists
									if (!found_in_column) {
										var column_value_label = MyWidgetResourceLib.StaticListHandler.getItemValue(column, {
											with_available_values: false,
											with_default: false,
										});
										
										if (column_value != column_value_label)
											found_in_column = this.searchValueFoundInColumnValue(column_value_label, search_value, search_type, search_case);
									}
								}
								
								//prepare found according with operator
								if (search_operator != "and" && found_in_column) { //if found and if operator == "or" or operator == "" (which by default: "" == "or")
									found = true;
									break;
								}
								else if (search_operator == "and" && !found_in_column) {
									found = false;
									break;
								}
							}
						
						if (found) {
							//only show item if inside of selected pagination
							if (exist_pagination) {
								if (items_index >= start_index && items_index <= end_index)
									MyWidgetResourceLib.fn.show(item);
								else
									MyWidgetResourceLib.fn.hide(item);
							}
							else
								MyWidgetResourceLib.fn.show(item);
							
							items_index++;
						}
						else
							MyWidgetResourceLib.fn.hide(item);
					}
				}
			}
			
			//only show pagination if items_index are bigger than items_limit_per_page.
			if (exist_pagination && pagination_elms && items_index >= 0) {
				for (var i = 0, t = pagination_elms.length; i < t; i++)
					MyWidgetResourceLib.PaginationHandler.drawStaticPagination(pagination_elms[i], items_index, pagination_vars["items_limit_per_page"], pagination_vars["page_number"]);
			}
		},
		
		searchValueFoundInColumnValue: function(column_value, search_value, search_type, search_case) {
			var found_in_column = false;
			
			column_value = column_value || MyWidgetResourceLib.fn.isNumeric(column_value) ? "" + column_value : "";
			search_value = search_value || MyWidgetResourceLib.fn.isNumeric(search_value) ? "" + search_value : "";
			
			if (search_value.length == 0) 
				found_in_column = true;
			else {
				var parsed_search_value = search_case == "insensitive" ? search_value.toLowerCase() : search_value;
				var parsed_column_value = search_case == "insensitive" ? column_value.toLowerCase() : column_value;
				
				//if column value starts with search value
				if (search_type == "starts_with" || search_type == "start_with") {
					if (parsed_column_value.substr(0, search_value.length) == parsed_search_value)
						found_in_column = true;
				}
				//else if column value end with search value
				else if (search_type == "ends_with" || search_type == "end_with") {
					if (column_value.length >= search_value.length && parsed_column_value.substr(column_value.length - search_value.length) == parsed_search_value)
						found_in_column = true;
				}
				//else if column value is equal search value
				else if (search_type == "equal") {
					if (parsed_column_value == parsed_search_value)
						found_in_column = true;
				}
				//else if column value contains search value
				else if (parsed_column_value.indexOf(parsed_search_value) != -1) //search_type == "contains" or search_type is empty or invalid
					found_in_column = true;
			}
			
			return found_in_column;
		},
		
		getPaginationVars: function(elm) {
			var start_index = null;
			var end_index = null;
			
			var page_number = MyWidgetResourceLib.fn.getNodeElementData(elm, "page_number");
			var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
			var items_limit_per_page = parseIn(properties["items_limit_per_page"]);
			var starting_page_number = properties["starting_page_number"];
			
			starting_page_number = starting_page_number >= 0 ? parseInt(starting_page_number) : 0;
			page_number = page_number >= 0 ? parseInt(page_number) : starting_page_number;
			
			if (items_limit_per_page > 0) {
				start_index = page_number * items_limit_per_page;
				end_index = start + items_limit_per_page - 1;
			}
			
			return {
				start_index: start_index,
				end_index: end_index,
				items_limit_per_page: items_limit_per_page,
				starting_page_number: starting_page_number,
				page_number: page_number,
			};
		},
		
		getListItemsParents : function(elm) {
			//get items' parents
			var list_items_html = MyWidgetResourceLib.fn.getNodeElementData(elm, "list_items_html");
			var parents = [];
			
			if (list_items_html)
				for (var i = 0, t = list_items_html.length; i < t; i++) {
					var list_item_html = list_items_html[i];
					
					if (!parents.indexOf(list_item_html.parent) == -1)
						parents.push(list_item_html.parent);
				}
			
			return parents;
		},
		
		convertDataByAttributesToDataByColumnIndexes: function(items, data_by_attrs) {
			//get column index for attribute names
			var attrs_by_index = {};
			
			for (var attribute_name in data_by_attrs) {
				var column_index = null;
				
				//get column index
				var max_iterations = 10; //for fail safe and bc of speed reasons
				var t = items.length > max_iterations ? max_iterations : items.length;
				
				for (var j = 0; j < t; j++) {
					column_index = MyWidgetResourceLib.StaticListHandler.getItemAttributeColumnIndex(items[j], attribute_name);
					
					if (column_index >= 0)
						break;
				}
				
				if (column_index >= 0)
					attrs_by_index[column_index] = data_by_attrs[attribute_name];
			}
			
			return attrs_by_index;
		},
		
		getItemAttributeColumnIndex : function(elm, attribute_name) {
			var column_index = null;
			var columns = elm.querySelectorAll("[data-widget-item-column]");
			
			if (columns)
				for (var i = 0, ti = columns.length; i < ti; i++) {
					var column = columns[i];
					var column_attribute_name = MyWidgetResourceLib.FieldHandler.getWidgetResourceValueAttributeName(column);
					
					if (column_attribute_name == attribute_name) {
						column_index = i;
						break;
					}
					else {
						var sub_items = column.querySelectorAll("input, select, textarea, [data-widget-resource-value]");
						
						if (sub_items)
							for (var j = 0, tj = sub_items.length; j < tj; j++) {
								var sub_item = sub_items[j];
								var sub_item_attribute_name = MyWidgetResourceLib.FieldHandler.getWidgetResourceValueAttributeName(sub_item);
								
								if (sub_item_attribute_name == attribute_name) {
									column_index = i;
									i = columns.length;
									break;
								}
							}
					}
				}
			
			return column_index;
		},
		
		getItemValue: function(elm, opts) { 
			if (elm && MyWidgetResourceLib.fn.isVisible(elm)) {
				var f = elm.querySelector("input, select, textarea");
				
				if (f && MyWidgetResourceLib.fn.isVisible(f))
					return MyWidgetResourceLib.FieldHandler.getFieldValue(f, {
						with_available_values: opts && opts.hasOwnProperty("with_available_values") ? opts["with_available_values"] : true,
						with_default: opts && opts.hasOwnProperty("with_default") ? opts["with_default"] : true,
					});
				
				var value_elm = elm;
				
				if (!elm.hasAttribute("data-widget-resource-value")) {
					f = elm.querySelector("[data-widget-resource-value]");
					
					if (f && MyWidgetResourceLib.fn.isVisible(f))
						value_elm = f;
				}
				
				if (value_elm.hasAttribute("data-widget-resource-value")) {
					var target_type = this.getWidgetResourceValueDisplayTargetType(value_elm);
					var target_attribute = this.getWidgetResourceValueDisplayTargetAttributeName(value_elm);
					
					if (target_type == "attribute")
						return target_attribute || MyWidgetResourceLib.fn.isNumeric(target_attribute) ? value_elm.getAttribute(target_attribute) : undefined;
					else
						return elm.innerHTML;
				}
				else
					return elm.innerHTML;
			}
			
			return null;
		},
	});
	/****************************************************************************************
	 *				 END: STATIC LIST HANDLER 					*
	 ****************************************************************************************/
	
	/****************************************************************************************
	 *				 START: ITEM HANDLER 					*
	 ****************************************************************************************/
	MyWidgetResourceLib.ItemHandler = MyWidgetResourceLib.fn.ItemHandler = ({
		
		//Get values from the new item and send request to server so a new record can be added.
		//This function receives a second parameter, which is a string with the resource key to be called, in case the user wishes to defined a different one from the default resource key: 'add'.
		//Note that this function should be called inside of a list/form widget with a 'add' resource defined.
		addResourceItem: function(elm, resource_key) {
			var opts = {add_item: true};
			
			MyWidgetResourceLib.FieldHandler.executeResourceItemFieldsAction(elm, "[data-widget-list], [data-widget-form], form", "[data-widget-item-add], [data-widget-item], form", resource_key ? resource_key : "add", true, opts);
		},
		
		//Cancel itention of adding new item.
		cancelAddResourceItem: function(elm) {
			if (elm) {
				var item = elm.closest("[data-widget-item], [data-widget-item-add], [data-widget-form], form");
				
				if (item) {
					var widget = elm.parentNode.closest("[data-widget-list]");
					
					if (!widget)
						widget = elm.parentNode.closest("[data-widget-form], form");
					
					if (widget) {
						if (widget.hasAttribute("data-widget-list")) {
							var parent = item.parentNode;
							
							//remove added item from the addInlineResourceListItem method
							parent.removeChild(item);
							
							//add empty message if no records if widget is a list widget
							var items = parent.querySelectorAll("[data-widget-item]");
							
							if (!items || items.length == 0) {
								var table_empty_html = MyWidgetResourceLib.fn.getNodeElementData(widget, "table_empty_html");
								var tree_empty_html = MyWidgetResourceLib.fn.getNodeElementData(widget, "tree_empty_html");
								var sub_widget = parent.parentNode.closest("[data-widget-list-table], [data-widget-list-tree], [data-widget-list]");
								var empty_html = sub_widget.hasAttribute("data-widget-list-tree") ? tree_empty_html : (sub_widget.hasAttribute("data-widget-list-table") ? table_empty_html : "");
								
								if (empty_html)
									parent.insertAdjacentHTML('beforeend', empty_html);
							}
						}
						else { //if (widget.hasAttribute("data-widget-form") || widget.nodeName.toUpperCase() == "FORM") {
							if (widget.querySelector("[data-widget-button-add]"))
								widget.classList.remove("show-add-fields");
							else
								MyWidgetResourceLib.FormHandler.resetForm(widget);
						}
					}
					else
						MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No widget in cancelAddResourceItem method!");
				}
				else
					MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No widget item in cancelAddResourceItem method!");
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No element in cancelAddResourceItem method!");
		},
		
		//Get values of the current item and send request to server to update them.
		//This function receives a second parameter, which is a string with the resource key to be called, in case the user wishes to defined a different one from the default resource key: 'update'.
		//Note that this function should be called inside of a list/form widget with a 'update' resource defined.
		updateResourceItem: function(elm, resource_key) {
			var opts = {update_item: true, include_opts_search_attrs: true};
			
			MyWidgetResourceLib.FieldHandler.executeResourceItemFieldsAction(elm, "[data-widget-list], [data-widget-form], form", "[data-widget-item], form", resource_key ? resource_key : "update", false, opts);
		},
		
		//Get values of the current item and send request to server to update them. If no record, adds a new one.
		//This function receives a second parameter, which is a string with the resource key to be called, in case the user wishes to defined a different one from the default resource key: 'update'.
		//Note that this function should be called inside of a list/form widget with a 'update' resource defined.
		saveResourceItem: function(elm, resource_key) {
			var search_attrs = null;
			
			if (elm) {
				var item = elm.closest("[data-widget-item-add], [data-widget-item], form");
				var search_attrs = item.getAttribute("data-widget-pks-attrs");
				search_attrs = search_attrs ? MyWidgetResourceLib.fn.parseJson(search_attrs) : null;
			}
			
			if (!search_attrs)
				MyWidgetResourceLib.ItemHandler.addResourceItem(elm, resource_key);
			else
				MyWidgetResourceLib.ItemHandler.updateResourceItem(elm, resource_key);
		},
		
		//Send request to server to remove current item.
		//This function receives a second parameter, which is a string with the resource key to be called, in case the user wishes to defined a different one from the default resource key: 'remove'.
		//Note that this function should be called inside of a list/form widget with a 'remove' resource defined.
		removeResourceItem: function(elm, resource_key) {
			var opts = {remove_item: true, include_opts_search_attrs: true};
			
			MyWidgetResourceLib.FieldHandler.removeResourceItemFromItemFieldAction(elm, "[data-widget-list], [data-widget-form], form", "[data-widget-item], form", resource_key ? resource_key : "remove", opts);
			//or MyWidgetResourceLib.FieldHandler.executeResourceItemFieldsAction(elm, "[data-widget-list], [data-widget-form], form", "[data-widget-item], form", "remove", false, opts);
		},
		
		//Get value on blur of the current field (input/select/textarea) and send request to server to save it.
		//This function receives a second parameter, which is a string with the resource key to be called, in case the user wishes to defined a different one from the default resource key: 'update_attribute'.
		//Note that this function should be called inside of a list/form widget with a 'update_attribute' resource defined.
		updateResourceItemAttributeOnBlur: function(elm, resource_key) {
			if (elm) {
				if (!MyWidgetResourceLib.fn.getNodeElementData(elm, "disable_blur_event")) {
					var update_resource_list_attribute_timeout_id = MyWidgetResourceLib.fn.getNodeElementData(elm, "update_resource_list_attribute_timeout_id");
					update_resource_list_attribute_timeout_id && clearTimeout(update_resource_list_attribute_timeout_id);
					
					update_resource_list_attribute_timeout_id = setTimeout(function() {
						MyWidgetResourceLib.ItemHandler.updateResourceItemAttribute(elm, resource_key, {cache_value: true});
					}, 500);
					
					MyWidgetResourceLib.fn.setNodeElementData(elm, "update_resource_list_attribute_timeout_id", update_resource_list_attribute_timeout_id);
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No element in updateResourceItemAttributeOnBlur method!");
		},
		
		//Get value on key up of the current field (input/textarea) and send request to server to save it.
		//This function receives a second parameter, which is a string with the resource key to be called, in case the user wishes to defined a different one from the default resource key: 'update_attribute'.
		//Note that this function should be called inside of a list/form widget with a 'update_attribute' resource defined.
		updateResourceItemAttributeOnKeyUp: function(elm, resource_key) {
			if (elm) {
				var update_resource_list_attribute_timeout_id = MyWidgetResourceLib.fn.getNodeElementData(elm, "update_resource_list_attribute_timeout_id");
				update_resource_list_attribute_timeout_id && clearTimeout(update_resource_list_attribute_timeout_id);
				
				MyWidgetResourceLib.fn.setNodeElementData(elm, "disable_blur_event", true);
				
				update_resource_list_attribute_timeout_id = setTimeout(function() {
					MyWidgetResourceLib.ItemHandler.updateResourceItemAttribute(elm, resource_key, {cache_value: true});
					
					MyWidgetResourceLib.fn.setNodeElementData(elm, "disable_blur_event", null);
				}, 1500);
				
				MyWidgetResourceLib.fn.setNodeElementData(elm, "update_resource_list_attribute_timeout_id", update_resource_list_attribute_timeout_id);
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No element in updateResourceItemAttributeOnKeyUp method!");
		},
		
		//Get value on change of the current select/combobox field and send request to server to save it.
		//This function receives a second parameter, which is a string with the resource key to be called, in case the user wishes to defined a different one from the default resource key: 'update_attribute'.
		//Note that this function should be called inside of a list/form widget with a 'update_attribute' resource defined.
		updateResourceItemAttributeOnChange: function(elm, resource_key) {
			if (elm) {
				var update_resource_list_attribute_timeout_id = MyWidgetResourceLib.fn.getNodeElementData(elm, "update_resource_list_attribute_timeout_id");
				update_resource_list_attribute_timeout_id && clearTimeout(update_resource_list_attribute_timeout_id);
				
				MyWidgetResourceLib.fn.setNodeElementData(elm, "disable_blur_event", true);
				
				update_resource_list_attribute_timeout_id = setTimeout(function() {
					MyWidgetResourceLib.ItemHandler.updateResourceItemAttribute(elm, resource_key, {cache_value: true});
					
					MyWidgetResourceLib.fn.setNodeElementData(elm, "disable_blur_event", null);
				}, 500);
				
				MyWidgetResourceLib.fn.setNodeElementData(elm, "update_resource_list_attribute_timeout_id", update_resource_list_attribute_timeout_id);
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No element in updateResourceItemAttributeOnChange method!");
		},
		
		//Get value on click of the current field (checkbox or radio button) and send request to server to save it.
		//This function receives a second parameter, which is a string with the resource key to be called, in case the user wishes to defined a different one from the default resource key: 'update_attribute'.
		//Note that this function should be called inside of a list/form widget with a 'update_attribute' resource defined.
		updateResourceItemAttributeOnClick: function(elm, resource_key) {
			if (elm) {
				var update_resource_list_attribute_timeout_id = MyWidgetResourceLib.fn.getNodeElementData(elm, "update_resource_list_attribute_timeout_id");
				update_resource_list_attribute_timeout_id && clearTimeout(update_resource_list_attribute_timeout_id);
				
				MyWidgetResourceLib.fn.setNodeElementData(elm, "disable_blur_event", true);
				
				update_resource_list_attribute_timeout_id = setTimeout(function() {
					MyWidgetResourceLib.ItemHandler.updateResourceItemAttribute(elm, resource_key, {cache_value: false}); //cache_value is false, bc this could be used for buttons where the value of the button is always the same one.
					
					MyWidgetResourceLib.fn.setNodeElementData(elm, "disable_blur_event", null);
				}, 500);
				
				MyWidgetResourceLib.fn.setNodeElementData(elm, "update_resource_list_attribute_timeout_id", update_resource_list_attribute_timeout_id);
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No element in updateResourceItemAttributeOnClick method!");
		},
		
		//Get value on change of the current field and send request to server to save it.
		//This function receives a second parameter, which is a string with the resource key to be called, in case the user wishes to defined a different one from the default resource key: 'update_attribute'.
		//Note that this function should be called inside of a list/form widget with a 'update_attribute' resource defined.
		updateResourceItemAttribute: function(elm, resource_key, opts) {
			var still_exists = elm.closest("[data-widget-item], [data-widget-form], form").parentNode; //This is very important bc if we change a checkbox and then click in the multiple save button, it will execute the multiple save action, then reload the list and the reference for the this elm will be lost. So we need to check if this element still exists, bc we call the updateResourceItemAttribute method with a setTimeout. 
			
			if (still_exists) {
				opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? opts : {};
				opts["update_item"] = true;
				opts["include_opts_search_attrs"] = true;
				opts["cache_value"] = true;
				
				MyWidgetResourceLib.FieldHandler.executeResourceItemFieldAttributeAction(elm, "[data-widget-list], [data-widget-form], form", "[data-widget-item], form", resource_key ? resource_key : "update_attribute", false, opts);
			}
		},
		
		//Get the item' values and open a popup with a readonly form with that values.
		//The html element that will execute this function, must have the attribute 'data-widget-popup-id' with the correspondent popup id.
		//Additionally this html element must be inside of a parent element with the following selector: '[data-widget-item], [data-widget-form], form' too.
		openItemViewPopupById: function(elm) {
			MyWidgetResourceLib.ItemHandler.openItemEditPopupById(elm);
		},
		
		//Get the item' values and open a popup with an editable form with that values.
		//The html element that will execute this function, must have the attribute 'data-widget-popup-id' with the correspondent popup id.
		//Additionally this html element must be inside of a parent element with the following selector: '[data-widget-item], [data-widget-form], form' too.
		openItemEditPopupById: function(elm) {
			var popup_id = elm ? elm.getAttribute("data-widget-popup-id") : null;
			var item = elm ? elm.closest("[data-widget-item], [data-widget-form], form") : null;
			var search_attrs = item ? item.getAttribute("data-widget-pks-attrs") : null;
			
			MyWidgetResourceLib.PopupHandler.openFormPopupById(elm, popup_id, search_attrs);
		},
		
		//Get the item' values and load dependent widgets.
		//Additionally this html element must be inside of a parent element with the following selector: '[data-widget-item], [data-widget-form], form' too.
		openItemDependentWidgets: function(elm) {
			var elm_properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
			var item = elm ? elm.closest("[data-widget-item], [data-widget-form], form") : null;
			var search_attrs = item ? item.getAttribute("data-widget-pks-attrs") : null;
			
			//execute search based in the search_attrs var
			var dependent_widgets_id = MyWidgetResourceLib.fn.prepareDependentWidgetsId(elm_properties["dependent_widgets_id"]);
			var search_props = {
				search_attrs: search_attrs
			};
			var opts = {
					"purge_cache": true 
			};
			MyWidgetResourceLib.fn.loadDependentWidgetsById(dependent_widgets_id, search_props, opts);
		},
		
		//Toggle between view and edit fields. This will only work if there are view and edit fields together in the same item.
		toggleResourceAttributesEditing: function(elm) {
			if (elm) {
				var item = elm.closest("[data-widget-item], [data-widget-form], form");
				var columns = item.querySelectorAll("[data-widget-item-column], [data-widget-item-actions-column]");
				
				if (columns)
					//TODO: find a way to loop asyncronously
					MyWidgetResourceLib.fn.each(columns, function(idx, column) {
						var view_fields = column.querySelectorAll("[data-widget-item-attribute-field-view], [data-widget-item-attribute-link-view]");
						var edit_fields = column.querySelectorAll("[data-widget-item-attribute-field-edit], [data-widget-item-attribute-link-edit], [data-widget-item-button-update]");
						
						if (edit_fields && edit_fields.length > 0) {
							var edit_fields_visible = MyWidgetResourceLib.fn.isVisible(edit_fields[0]);
							
							//TODO: find a way to loop asyncronously
							MyWidgetResourceLib.fn.each(edit_fields, function(idx, item) {
								if (edit_fields_visible)
									MyWidgetResourceLib.fn.hide(item);
								else {
									MyWidgetResourceLib.fn.show(item);
									MyWidgetResourceLib.PermissionHandler.loadWidgetPermissions(item);
								}
							});
							
							if (view_fields)
								//TODO: find a way to loop asyncronously
								MyWidgetResourceLib.fn.each(view_fields, function(idx, item) {
									if (edit_fields_visible) {
										MyWidgetResourceLib.fn.show(item);
										MyWidgetResourceLib.PermissionHandler.loadWidgetPermissions(item);
									}
									else
										MyWidgetResourceLib.fn.hide(item);
								});
						}
					});
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No element in toggleResourceAttributesEditing method!");
		},
		
		//when toggle button, view and edit fields exist, when the user changes some edit field and then saves, we must update the view fields accordingly. This is done by calling this function.
		//Based in the edit fields' values, update the view fields, this is, when toggle between edit to view fields, the system calls automatically this function to update the new changed values. If you wish to call it too in another cenario, you can do it too...
		updateViewFieldsBasedInEditFields: function(elm) {
			if (elm) {
				var item = elm.closest("[data-widget-item], [data-widget-form], form");
				var columns = item.querySelectorAll("[data-widget-item-column], [data-widget-item-actions-column]");
				
				if (columns)
					//TODO: find a way to loop asyncronously
					MyWidgetResourceLib.fn.each(columns, function(idx, column) {
						var view_fields = column.querySelectorAll("[data-widget-item-attribute-field-view], [data-widget-item-attribute-link-view]");
						var edit_fields = column.querySelectorAll("[data-widget-item-attribute-field-edit]");
						
						if (view_fields && view_fields.length > 0 && edit_fields && edit_fields.length > 0) {
							for (var i = 0, ti = edit_fields.length; i < ti; i++) {
								var edit_field = edit_fields[i];
								
								if (!edit_field.hasAttribute("data-widget-resource-value"))
									edit_field = edit_field.querySelector("[data-widget-resource-value]");
								
								if (edit_field) {
									//if (MyWidgetResourceLib.fn.isVisible(edit_field)) { //this must be commented, bc if the user is too fast and change something and then click in the toggle button, that field won't be updated with the new value
										var edit_field_name = MyWidgetResourceLib.FieldHandler.getWidgetResourceValueAttributeName(edit_field);
										
										if (edit_field_name) {
											//if edit_field is input/textarea/select or any other html element (note that we can have html elements with contenteditable=true)
											var value = MyWidgetResourceLib.FieldHandler.isEmptyCheckbox(edit_field) ? "" : MyWidgetResourceLib.FieldHandler.getFieldValue(edit_field, {
												with_available_values: true,
												with_default: true
											});
											
											for (var j = 0, tj = view_fields.length; j < tj; j++) {
												var view_field = view_fields[j];
												
												if (!view_field.hasAttribute("data-widget-resource-value"))
													view_field = view_field.querySelector("[data-widget-resource-value]");
												
												if (view_field) {
													var view_field_name = MyWidgetResourceLib.FieldHandler.getWidgetResourceValueAttributeName(view_field);
													
													if (edit_field_name == view_field_name) {
														MyWidgetResourceLib.FieldHandler.setFieldValue(view_field, value, {
															with_available_values: true,
															with_default: true
														});
													}
												}
											}
										}
									//}
								}
							}
						}
					});
				
				//update similar items in data-widget-list-tree if widget is table and in data-widget-list-table if widget is tree
				if (item.hasAttribute("data-widget-item")) {
					var search_attrs = item.getAttribute("data-widget-pks-attrs");
					var widget = item.parentNode.closest("[data-widget-list]");
					
					if (search_attrs && widget) {
						var values = MyWidgetResourceLib.FieldHandler.getFieldsValues(item, {
							with_available_values: true,
							with_default: true,
						});
						
						if (!MyWidgetResourceLib.fn.isEmptyObject(values)) {
							var items = widget.querySelectorAll("[data-widget-item]");
							
							if (items)
								MyWidgetResourceLib.fn.each(items, function(idx, sub_item) {
									if (item != sub_item && sub_item.getAttribute("data-widget-pks-attrs") == search_attrs) {
										var sub_item_inputs = sub_item.querySelectorAll("input, select, textarea, [data-widget-resource-value]");
										
										if (sub_item_inputs)
											//TODO: find a way to loop asyncronously
										   	MyWidgetResourceLib.fn.each(sub_item_inputs, function(idy, sub_item_input) {
										   		var sub_item_input_name = MyWidgetResourceLib.FieldHandler.getWidgetResourceValueAttributeName(sub_item_input);
												
										   		if (sub_item_input_name && values.hasOwnProperty(sub_item_input_name))
										   			MyWidgetResourceLib.FieldHandler.setFieldValue(sub_item_input, values[sub_item_input_name], {
														with_available_values: true,
														with_default: true
													});
										   	});
									}
								});
						}
					}
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No element in toggleResourceAttributesEditing method!");
		},
		
		//If you wish to hide a shown combobox and show a hidden input.
		//Note that this function should be called inside of a [data-widget-item-attribute-field-toggle-select-input] widget.
		toggleItemAttributeSelectFieldToInputField: function(elm) {
			if (elm) {
				var parent = elm.closest("[data-widget-item-attribute-field-toggle-select-input]");
				var select = parent.querySelector("select");
				var input = parent.querySelector("input");
				
				this.toggleItemAttributeFieldToField(select, input);
			}
		},
		
		//If you wish to hide a shown input and show a hidden combobox.
		//Note that this function should be called inside of a [data-widget-item-attribute-field-toggle-select-input] widget.
		toggleItemAttributeInputFieldToSelectField: function(elm) {
			if (elm) {
				var parent = elm.closest("[data-widget-item-attribute-field-toggle-select-input]");
				var select = parent.querySelector("select");
				var input = parent.querySelector("input");
				
				this.toggleItemAttributeFieldToField(input, select);
			}
		},
		
		toggleItemAttributeFieldToField: function(from_field, to_field) {
			var parents = this.getToggledItemAttributeFieldToFieldMainParents(from_field, to_field);
			
			if (parents) {
				var main_from_field_parent = parents[0];
				var main_to_field_parent = parents[1];
				
				//stop the running change/blur/keyUp/keyDown events for the from_field
				var update_resource_list_attribute_timeout_id = MyWidgetResourceLib.fn.getNodeElementData(from_field, "update_resource_list_attribute_timeout_id");
				update_resource_list_attribute_timeout_id && clearTimeout(update_resource_list_attribute_timeout_id);
				
				MyWidgetResourceLib.fn.setNodeElementData(from_field, "disable_blur_event", null); //reset value
				
				//toggle parents
				main_from_field_parent.classList.remove("show");
				main_to_field_parent.classList.add("show");
				
				//migrate attributes
				var attributes_to_search = ["data-widget-resource-value", "data-allow-null", "data-validation-type", "data-validation-message", "data-validation-label", "placeHolder", "maxLength", "step", "name"];
				
				for (var i = 0, t = attributes_to_search.length; i < t; i++) {
					var attr_name = attributes_to_search[i];
					
					if (from_field.hasAttribute(attr_name)) {
						var attr_value = from_field.getAttribute(attr_name);
						to_field.setAttribute(attr_name, attr_value);
						from_field.removeAttribute(attr_name);
					}
				}
				
				//update the values if different
				if (from_field.value != to_field.value) {
					to_field.value = from_field.value;
					
					if (to_field.hasAttribute("onChange"))
						MyWidgetResourceLib.fn.trigger(to_field, "change");
					else if (to_field.hasAttribute("onBlur"))
						MyWidgetResourceLib.fn.trigger(to_field, "blur");
					else if (to_field.hasAttribute("onKeyUp"))
						MyWidgetResourceLib.fn.trigger(to_field, "keyUp");
					else if (to_field.hasAttribute("onKeyDown"))
						MyWidgetResourceLib.fn.trigger(to_field, "keyDown");
				}
			}
		},
		
		isToggledItemAttributeFieldToField: function(elm) {
			if (elm) {
				var toggle_elm = elm.closest("[data-widget-item-attribute-field-toggle-select-input], [data-widget-item-attribute-field-edit], [data-widget-item-column], [data-widget-item], [data-widget-form], form");
				
				return toggle_elm && toggle_elm.hasAttribute("data-widget-item-attribute-field-toggle-select-input");
			}
			
			return false;
		},
		
		isToggledItemAttributeFieldShown: function(elm) {
			if (elm) {
				var parent = elm.closest("[data-widget-item-attribute-field-toggle-select-input]");
				var select = elm.nodeName != "SELECT" ? parent.querySelector("select") : elm;
				var input = elm.nodeName == "SELECT" ? parent.querySelector("input") : elm;
				
				var parents = this.getToggledItemAttributeFieldToFieldMainParents(select, input);
				
				if (parents) {
					var elm_parent = elm == select ? parents[0] : parents[1];
					
					return elm_parent.classList.contains("show");
				}
			}
			
			return false;
		},
		
		getToggledItemAttributeFieldToFieldMainParents: function(from_field, to_field) {
			if (from_field && to_field) {
				//find common parent
				var from_field_parent = from_field;
				var from_field_parents = [];
				var to_field_parent = to_field;
				var to_field_parents = [];
				
				while (from_field_parent && from_field_parent.nodeType == Node.ELEMENT_NODE && !from_field_parent.hasAttribute("data-widget-item-attribute-field-toggle-select-input")) {
					from_field_parent = from_field_parent.parentNode;
					from_field_parents.push(from_field_parent);
				};
				
				while (to_field_parent && to_field_parent.nodeType == Node.ELEMENT_NODE && !to_field_parent.hasAttribute("data-widget-item-attribute-field-toggle-select-input")) {
					to_field_parent = to_field_parent.parentNode;
					to_field_parents.push(to_field_parent);
				};
				
				var main_from_field_parent = null;
				var main_to_field_parent = null;
				
				for (var i = 0, ti = from_field_parents.length; i < ti; i++) {
					var from_field_parent = from_field_parents[i];
					
					for (var j = 0, tj = to_field_parents.length; j < tj; j++) {
						var to_field_parent = to_field_parents[j];
						
						if (from_field_parent === to_field_parent) {
							main_from_field_parent = i > 0 ? from_field_parents[i - 1] : null;
							main_to_field_parent = j > 0 ? to_field_parents[j - 1] : null;
							i = ti;
							break;
						}
					}
				}
				
				if (main_from_field_parent && main_to_field_parent) {
					return [main_from_field_parent, main_to_field_parent];
				}
			}
			
			return null;
		}
	});
	/****************************************************************************************
	 *				 END: ITEM HANDLER 					*
	 ****************************************************************************************/
	
	/****************************************************************************************
	 *				 START: POPUP HANDLER 					*
	 ****************************************************************************************/
	MyWidgetResourceLib.PopupHandler = MyWidgetResourceLib.fn.PopupHandler = ({
		
		//Load data of a popup widget.
		//This function should be called by a popup widget.
		loadPopupResource: function(elm, opts) {
			return MyWidgetResourceLib.FieldHandler.loadElementFieldsResource(elm, opts);
		},
		
		//Executes a resource based on the saved parameters of a popup.
		//This method receives a third argument with an 'opts' object correspondent to some options that will be passed on execution of the correspondent resource.
		executeSinglePopupResource: function(elm, resource_key, opts) {
			var widget = elm.closest("[data-widget-popup]");
			
			if (widget)
				elm = widget;
			
			return MyWidgetResourceLib.FieldHandler.executeSingleElementFieldsResource(elm, resource_key, opts);
		},
		
		//Open a popup with an editable form with empty values.<br/>The html element that will execute this function, must have the attribute
		//"data-widget-popup-id" with the correspondent popup id.
		openButtonAddPopup: function(elm) {
			var popup_id = elm ? elm.getAttribute("data-widget-popup-id") : null;
			var popup = popup_id ? document.querySelector("#" + popup_id) : null;
			
			//show-add-fields
			if (popup) {
				//var forms = popup.querySelectorAll("form[data-widget-form]");
				var forms = popup.querySelectorAll("form");
				
				if (forms && forms.length > 0)
					for (var i = 0, t = forms.length; i < t; i++)
						forms[i].classList.add("show-add-fields");
			}
			
			this.openFormPopupById(elm, popup_id, null, true);
		},
		
		openFormPopupById: function(elm, popup_id, search_attrs, reset) {
			//if (MyWidgetResourceLib.fn.isPlainObject(search_attrs) || MyWidgetResourceLib.fn.isArray(search_attrs))
			//	search_attrs = JSON.stringify(search_attrs);
			
			var popup = popup_id ? document.querySelector("#" + popup_id) : null;
			var opts = null;
			
			if (popup) {
				MyWidgetResourceLib.fn.setNodeElementData(popup, "search_attrs", search_attrs); //very important to be here, otherwise when call the dependencies (which are the form elements), it will loose the search_attrs
				
				//very important to purge the cache so everyime we edit a form, it should remove its loaded cache, otherwise the next time we load this popup, it will show the old data.
				opts = {
					"purge_cache": true 
				};
				
				if (reset) {
					MyWidgetResourceLib.FormHandler.resetElementForms(popup);
						
					//Do not set here the setNodeElementData for the forms with the search_attrs, bc this is already done when calling the popup dependencies.
				}
			}
			
			this.openPopupById(popup_id, opts);
		},
		
		//Open a popup.
		//The html element that will execute this function, must have the attribute 'data-widget-popup-id' with the correspondent popup id.
		openPopup: function(elm) {
			this.openPopupById( elm.getAttribute("data-widget-popup-id") );
		},
		
		openPopupById: function(popup_id, opts) {
			var popup = popup_id ? document.querySelector("#" + popup_id) : null;
			
			if (popup) {
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(popup);
				var load_func = MyWidgetResourceLib.fn.convertIntoFunctions(properties["load"]);
				
				if (load_func)
					MyWidgetResourceLib.ResourceHandler.loadWidgetResource(popup, opts);
				else //load inner item widgets. This is very important bc the ADD POPUP doesn't have any load handler, but we still need to init the inner items like the select boxes.
					this.loadPopupResource(popup, opts);
				
				MyWidgetResourceLib.ModalHandler.initModalPopup(popup, {
					show: properties["show"],
					hide: properties["hide"],
				});
				MyWidgetResourceLib.ModalHandler.showModalPopup(popup);
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Invalid popup id!");
		},
		
		//Close a popup.
		//The html element that will execute this function, must have the attribute 'data-widget-popup-id' with the correspondent popup id.
		closePopup: function(elm) {
			this.closePopupById( elm.getAttribute("data-widget-popup-id") );
		},
		
		closePopupById: function(popup_id) {
			//close popup
			var popup = popup_id ? document.querySelector("#" + popup_id) : null;
			
			if (popup)
				MyWidgetResourceLib.ModalHandler.hideModalPopup(popup);
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Invalid popup id!");
		},
		
		//Close the parent popup for the html element that will execute this function.
		closeParentPopup: function(elm) {
			//close popup
			var popup = elm.closest("[data-widget-popup]");
			
			if (popup && popup.getAttribute("id"))
				MyWidgetResourceLib.PopupHandler.closePopupById( popup.getAttribute("id") ); //Do not use this bc we may use this function as a callback
		},
	});
	/****************************************************************************************
	 *				 END: POPUP HANDLER 					*
	 ****************************************************************************************/
		
	/****************************************************************************************
	 *				 START: FORM HANDLER 					*
	 ****************************************************************************************/
	MyWidgetResourceLib.FormHandler = MyWidgetResourceLib.fn.FormHandler = ({
		
		initForms: function() {
			var elms = document.querySelectorAll("form:not(.template-widget), [data-widget-form]:not(.template-widget)");
			
			if (elms)
				//TODO: find a way to loop asyncronously
				MyWidgetResourceLib.fn.each(elms, function(idx, elm) {
					MyWidgetResourceLib.FormHandler.initForm(elm);
				});
		},
		
		initForm: function(elm) {
			if (elm && !elm.form_inited) {
				elm.form_inited = true;
				
				//disables the enter key press if the enter_key_press_button property exists. Note that if this property is blank or white space, disables the enter key. Otherwise we try to find the button correspondent to that selector and trigger the onclick event on that button.
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				
				if (properties.hasOwnProperty("enter_key_press") || properties.hasOwnProperty("enter_key_press_button"))
					elm.addEventListener("keypress", function(event) {
						if (event.which == 13) {
							var target = event.target;
							var node_name = target ? target.nodeName.toLowerCase() : null;
							
							if (node_name == "input") { //if target is textarea doesn't do anything
								event.stopPropagation();
								event.preventDefault();
								
								MyWidgetResourceLib.fn.executeFunctions(properties["enter_key_press"], elm);
								
								if (properties.hasOwnProperty("enter_key_press_button")) {
									var selector = properties["enter_key_press_button"];
									selector = selector ? selector.replace(/\s+/g, "") : null;
									
									if (selector) {
										var button = null;
										
										try {
											button = elm.querySelector(selector); //tries to find button based in selector
											
											if (!button) {
												var is_valid_selector = ("" + selector).match(/[#\.\[]/);
												
												if (!is_valid_selector) {
													button = elm.querySelector("#" + selector); //tries to find button based in id
													
													if (!button)
														button = elm.querySelector("." + selector); //tries to find button based in class
												}
											}
										}
										catch(e) {
											if (console && console.log)
												console.log(e);
										};
										
										if (button)
											MyWidgetResourceLib.fn.trigger(button, "click");
									}
								}
								
								return false;
							}
						}
					});
			}
		},
		
		//Load data of a form widget.
		//This function should be called by a form widget.
		loadFormResource: function(elm, opts) {
			MyWidgetResourceLib.FormHandler.initForm(elm);
			
			//in case of data-widget-group-form > form
			var search_attrs = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_attrs");
			
			if (!search_attrs || (MyWidgetResourceLib.fn.isPlainObject(search_attrs) && MyWidgetResourceLib.fn.isEmptyObject(search_attrs))) {
				var search_attrs = elm.getAttribute("data-widget-pks-attrs");
				search_attrs = search_attrs ? MyWidgetResourceLib.fn.parseJson(search_attrs) : null;
				
				if (MyWidgetResourceLib.fn.isPlainObject(search_attrs) && !MyWidgetResourceLib.fn.isEmptyObject(search_attrs))
					MyWidgetResourceLib.fn.setNodeElementData(elm, "search_attrs", search_attrs);
			}
			
			//load fields
			return MyWidgetResourceLib.FieldHandler.loadElementFieldsResource(elm, opts);
		},
		
		//Executes a resource based on the saved parameters of a form. This method receives a third argument with an 'opts' object correspondent to some options that will be passed on execution of the correspondent resource.
		executeSingleFormResource: function(elm, resource_key, opts) {
			var widget = elm.closest("[data-widget-form], form");
			
			if (widget)
				elm = widget;
			
			return MyWidgetResourceLib.FieldHandler.executeSingleElementFieldsResource(elm, resource_key, opts);
		},
		
		//Reload data of the parent widget.
		//Note that this function should be called inside a form widget.
		reloadParentFormResource: function(elm, opts) {
			var widget = elm.closest("[data-widget-form], form");
			
			if (widget) {
				var cloned_opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? MyWidgetResourceLib.fn.assignObjectRecursively({}, opts) : {}; //clone opts, otherwise if the loadListResource method changes the opts object, then it will take efect here too.
				cloned_opts["purge_cache"] = true;
				
				return MyWidgetResourceLib.FormHandler.loadFormResource(widget, cloned_opts);
			}
			
			return false;
		},
		
		purgeCachedLoadParentFormResource: function(elm) {
			var widget = elm.closest("[data-widget-form], form");
			
			if (widget)
				MyWidgetResourceLib.ResourceHandler.purgeWidgetResources(widget, "load");
		},
		
		//Resets all fields from a form.
		resetForm: function(elm) {
			if (elm) {
				//reset form
				var oForm = elm.closest("form");
				
				if (oForm)
					oForm.reset();
			}
		},
		
		//Resets all fields from the forms inside of a html node.
		resetElementForms: function(elm) {
			//var forms = elm.querySelectorAll("form[data-widget-form]");
			var forms = elm.querySelectorAll("form");
			
			if (forms && forms.length > 0)
				for (var i = 0, t = forms.length; i < t; i++)
					MyWidgetResourceLib.FormHandler.resetForm(forms[i]); //Note that this method can be called as an handler so we cannot use the this.resetForm
		},
		
		//Resets all fields from a form and remove the attribute: data-widget-pks-attrs.
		//In case we wish to convert an edit form to an add form, we should use this function. This allows to have a form that allows add and update items simultaneously.
		resetFormAndConvertItIntoAddForm: function(elm) {
			if (elm) {
				elm.removeAttribute("data-widget-pks-attrs");
				MyWidgetResourceLib.FormHandler.resetForm(elm);
			}
		},
		
		//Handler to be called on success of an add action. In summary this handler closest the parent popup and resets the form for the next addition.
		onAddPopupResourceItem: function(elm) {
			MyWidgetResourceLib.FormHandler.resetForm(elm);
			MyWidgetResourceLib.PopupHandler.closeParentPopup(elm);
		},
		
		//Handler to be called on success of an add action. In summary this handler reloads the data from the parent widget.
		onAddResourceItem: function(elm) {
			var oForm = elm.closest("[data-widget-form], form");
			
			if (oForm) {
				if (oForm.classList.contains("show-add-fields") && oForm.querySelector("[data-widget-button-add]"))
					oForm.classList.remove("show-add-fields");
				
				//reset form but only the data-widget-item-attribute-field-add fields
				var inputs = oForm.querySelectorAll("[data-widget-item-attribute-field-add]");
				
				if (inputs)
					//TODO: find a way to loop asyncronously
					MyWidgetResourceLib.fn.each(inputs, function(idx, input) {
						if (input.type == "checkbox" || input.type == "radio")
							input.checked = false;
						else
							input.value = "";
					});
			}
		},
		
		//Handler to be called on success of an update action. In summary this handler closes the parent popup for the html element that will execute this function.
		onUpdatePopupResourceItem: function(elm) {
			MyWidgetResourceLib.PopupHandler.closeParentPopup(elm);
			
			//purge cache from parent
			MyWidgetResourceLib.FormHandler.purgeCachedLoadParentFormResource(elm);
		},
		
		//Handler to be called on success of an update action. In summary, this handler calls the updateViewFieldsBasedInEditFields method.
		onUpdateResourceItem: function(elm) {
			MyWidgetResourceLib.ItemHandler.updateViewFieldsBasedInEditFields(elm);
			
			//purge cache from parent
			MyWidgetResourceLib.FormHandler.purgeCachedLoadParentFormResource(elm);
		},
		
		//Handler to be called on success of an update attribute action. In summary, this handler calls the updateViewFieldsBasedInEditFields method.
		onUpdatePopupResourceItemAttribute: function(elm) {
			MyWidgetResourceLib.FormHandler.onUpdateResourceItemAttribute(elm);
		},
		
		//Handler to be called on success of an update attribute action. In summary, this handler calls the updateViewFieldsBasedInEditFields method.
		onUpdateResourceItemAttribute: function(elm) {
			MyWidgetResourceLib.ItemHandler.updateViewFieldsBasedInEditFields(elm);
			
			//purge cache from parent
			MyWidgetResourceLib.FormHandler.purgeCachedLoadParentFormResource(elm);
		},
		
		//Handler to be called on success of a removal action. In summary this handler closest the parent popup.
		onRemovePopupResourceItem: function(elm) {
			MyWidgetResourceLib.PopupHandler.closeParentPopup(elm);
			
			var oForm = elm.parentNode.closest("[data-widget-form], form");
			
			if (oForm) {
				MyWidgetResourceLib.FormHandler.resetForm(elm);
				
				//purge cache from parent
				MyWidgetResourceLib.FormHandler.purgeCachedLoadParentFormResource(oForm);
			}
		},
		
		//Handler to be called on success of a removal action. In summary, this handler deletes the removed item from the parent widget.
		onRemoveResourceItem: function(elm) {
			var oForm = elm.closest("[data-widget-form], form");
			
			if (oForm) {
				MyWidgetResourceLib.FormHandler.resetForm(oForm);
				MyWidgetResourceLib.fn.hide(oForm);
				
				//purge cache from parent
				MyWidgetResourceLib.FormHandler.purgeCachedLoadParentFormResource(oForm);
			}
		},
	});
	/****************************************************************************************
	 *				 END: FORM HANDLER 					*
	 ****************************************************************************************/
		
	/****************************************************************************************
	 *				 START: FIELD HANDLER 					*
	 ****************************************************************************************/
	MyWidgetResourceLib.FieldHandler = MyWidgetResourceLib.fn.FieldHandler = ({
		
		//simply executes an resource
		executeSingleElementFieldsResource: function(elm, resource_key, opts) {
			if (elm) {
				if (elm.hasAttribute("data-widget-resources")) {
					var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
					var search_attrs = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_attrs");
					search_attrs = typeof search_attrs == "string" ? MyWidgetResourceLib.fn.parseJson(search_attrs) : search_attrs;
					
					//prepare resource
					var complete_func = function(resource_elm, resources, resources_name, resources_cache_key) {
						//call complete handler
						var complete_handler = MyWidgetResourceLib.fn.isPlainObject(properties["complete"]) ? properties["complete"][resource_key] : properties["complete"];
						complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
						
						if (complete_handler)
							MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
						
						//call complete handler from opts if exists
						var complete_handler = opts && opts["complete"] ? opts["complete"] : null;
						complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
						
						if (complete_handler)
							MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
						
						//call end handler
						var end_handler = MyWidgetResourceLib.fn.isPlainObject(properties["end"]) ? properties["end"][resource_key] : properties["end"];
						end_handler = MyWidgetResourceLib.fn.convertIntoFunctions(end_handler);
						
						if (end_handler)
							MyWidgetResourceLib.fn.executeFunctions(end_handler, elm, resources_name, resources_cache_key);
					};
					
					//prepare extra url
					var extra_url = opts && opts["extra_url"] ? opts["extra_url"] : "";
					var ignore_resource_conditions = false;
					
					if (search_attrs) {
						extra_url += "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_attrs, "search_attrs");
						ignore_resource_conditions = true;
					}
					
					var resource_opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? MyWidgetResourceLib.fn.assignObjectRecursively({}, opts) : {};
					resource_opts["ignore_resource_conditions"] = ignore_resource_conditions; 
					resource_opts["extra_url"] = extra_url;
					resource_opts["label"] = elm.nodeName.toLowerCase() + (elm.classList.length > 0 ? "." + elm.classList.toString().replace(/\s/g, ".") : "");
					resource_opts["complete"] = complete_func;
					var resource = MyWidgetResourceLib.ResourceHandler.executeGetWidgetResourcesRequest(elm, resource_key, resource_opts);
					
					if (resource)
						return true;
					else
						MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No resource set in executeSingleElementFieldsResource method. Please inform the web-developer accordingly...");
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No element in executeSingleElementFieldsResource method!");
			
			return false;
		},
		
		//Load data of an element widget and shows that data in the inner field elements. This function can be called by any html element with a 'load' resource defined. This method receives a second argument with an 'opts' object correspondent to some options that will be passed on execution of the correspondent resource.
		loadElementFieldsResource: function(elm, opts) {
			if (elm) {
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var search_attrs = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_attrs");
				search_attrs = typeof search_attrs == "string" ? MyWidgetResourceLib.fn.parseJson(search_attrs) : search_attrs;
				
				var perms = MyWidgetResourceLib.PermissionHandler.getWidgetPermissions(elm);
				
				if (!perms["remove"]) {
					//save inner html
					var inner_html = null;
					
					if (!MyWidgetResourceLib.fn.existsNodeElementData(elm, "inner_html")) { //only executes the first time
						inner_html = elm.innerHTML;
						MyWidgetResourceLib.fn.setNodeElementData(elm, "inner_html", inner_html);
					}
					else
						inner_html = MyWidgetResourceLib.fn.getNodeElementData(elm, "inner_html");
					
					//prepare resource
					var resource = null;
					var end_handler = MyWidgetResourceLib.fn.isPlainObject(properties["end"]) ? properties["end"]["load"] : properties["end"];
					end_handler = MyWidgetResourceLib.fn.convertIntoFunctions(end_handler);
					
					if (elm.hasAttribute("data-widget-resources")) {
						//check if exists any hashtag with resources
						var exists_html_hash_tags = MyWidgetResourceLib.HashTagHandler.existsHtmlHashTags(inner_html);
						var exists_html_hash_tags_resources = MyWidgetResourceLib.HashTagHandler.existsHtmlHashTagsWithResources(inner_html);
						
						var complete_func = function(resource_elm, resources, resources_name, resources_cache_key) {
							if (
								(exists_html_hash_tags && MyWidgetResourceLib.ResourceHandler.existLoadedSLAResults(resources_name, resources_cache_key)) //replace simple hashtag in html if exists a parent resource. The hashtag is a relative hashtag, something like: #attribute_name#
								|| exists_html_hash_tags_resources //replace resource hashtag in html with 'SLA' or 'Resource' prefix, something like: #SLA[resource_name][attribute_name]# or #Resource[resource_name][attribute_name]#.
							) {
								//replace hashtags if apply to the current resource
								var inner_item_hash_tags_to_ignore = MyWidgetResourceLib.HashTagHandler.getWidgetResourceValueInnerItemsHtmlHashTagsBasedInResources(inner_html, resources_name, resources_cache_key);
								inner_html = MyWidgetResourceLib.HashTagHandler.replaceHtmlHashTagsWithResources(inner_html, resources_name, resources_cache_key, null, inner_item_hash_tags_to_ignore);
								elm.innerHTML = inner_html;
							}
							
							//prepare PKs string
							var pks_attrs_names = properties.hasOwnProperty("pks_attrs_names") ? properties["pks_attrs_names"] : "";
							pks_attrs_names = MyWidgetResourceLib.fn.isArray(pks_attrs_names) ? pks_attrs_names : pks_attrs_names.replace(/;/g, ",").split(",");
							var pks_attrs = {};
							
							for (var i = 0, ti = resources_name.length; i < ti; i++) {
								var resource_name = resources_name[i];
								var resource_cache_key = resources_cache_key ? resources_cache_key[i] : null;
								var record = MyWidgetResourceLib.ResourceHandler.getLoadedSLAResult(resource_name, resource_cache_key);
								
								if (MyWidgetResourceLib.fn.isPlainObject(record))
									for (var j = 0, tj = pks_attrs_names.length; j < tj; j++) {
										var pk_attr_name = ("" + pks_attrs_names[j]).replace(/\s+/g, "");
										
										if (pk_attr_name && (!pks_attrs.hasOwnProperty(pk_attr_name) || pks_attrs[pk_attr_name] === null)) {
											pks_attrs[pk_attr_name] = record.hasOwnProperty(pk_attr_name) ? record[pk_attr_name] : null;
										}
									}
							}
							
							//set PKs
							elm.setAttribute("data-widget-pks-attrs", JSON.stringify(pks_attrs));
							
							//save loaded resource data in elm
							MyWidgetResourceLib.FieldHandler.saveWidgetLoadedResourceValues(elm, resources_name, resources_cache_key);
							
							//set resources to elm
							MyWidgetResourceLib.FieldHandler.onLoadFieldResource(elm, resources_name, resources_cache_key, properties);
							
							//set values based in inputs' names and data-widget-resource-value
							MyWidgetResourceLib.FieldHandler.setWidgetResourceValues(elm, resources_name, resources_cache_key);
							
							//call complete handler
							var complete_handler = MyWidgetResourceLib.fn.isPlainObject(properties["complete"]) ? properties["complete"]["load"] : properties["complete"];
							complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
							
							if (complete_handler)
								MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
							
							//call complete handler from opts if exists
							var complete_handler = opts && opts["complete"] ? opts["complete"] : null;
							complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
							
							if (complete_handler)
								MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
							
							//reload dependent widgets like the pagination elements, if apply
							MyWidgetResourceLib.fn.loadDependentWidgetsByIdWithoutResourcesToLoad(properties["dependent_widgets_id"], {
								search_attrs: search_attrs,
							}, opts);
							
							//call end handler
							if (end_handler)
								MyWidgetResourceLib.fn.executeFunctions(end_handler, elm, resources_name, resources_cache_key);
						};
						
						//prepare extra url
						var extra_url = opts && opts["extra_url"] ? opts["extra_url"] : "";
						var ignore_resource_conditions = false;
						
						if (search_attrs) {
							extra_url += "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_attrs, "search_attrs");
							ignore_resource_conditions = true;
						}
						
						var resource_opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? MyWidgetResourceLib.fn.assignObjectRecursively({}, opts) : {};
						resource_opts["ignore_resource_conditions"] = ignore_resource_conditions; //if search_attrs exists, ignore conditions
						resource_opts["extra_url"] = extra_url;
						resource_opts["label"] = elm.nodeName.toLowerCase() + (elm.classList.length > 0 ? "." + elm.classList.toString().replace(/\s/g, ".") : "");
						resource_opts["complete"] = complete_func;
						resource = MyWidgetResourceLib.ResourceHandler.executeGetWidgetResourcesRequest(elm, "load", resource_opts);
						
						if (resource) {
							//reset inner html
							if (exists_html_hash_tags_resources)
								elm.innerHTML = '';
							
							return true;
						}
						//else //no need to show this message bc maybe the user doesn't want any load resource
						//	MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No resource set in loadElementFieldsResource method. Please inform the web-developer accordingly...");
					}
					
					if (!resource) {
						//load inner item widgets. This is very important bc the ADD POPUP doesn't have any load handler, but we still need to init the inner items like the select boxes.
						var item_widgets = elm.querySelectorAll("[data-widget-item-resources-load]:not(.template-widget), [data-widget-resources-load]:not(.template-widget)");
						
						if (item_widgets)
							MyWidgetResourceLib.fn.each(item_widgets, function (idx, item_widget) { //:not(.template-widget) is very important so it doesn't load the resource when we are editing this widget through the LayoutUIEditor.
								item_widget.removeAttribute("data-widget-item-resources-load");
								item_widget.removeAttribute("data-widget-resources-load");
								
								MyWidgetResourceLib.ResourceHandler.loadWidgetResource(item_widget, {async: false}); //must be async false otherwise we will loose the the real attribute values, when the load function gets executed
							});
						
						//reload dependent widgets like the pagination elements, if apply
						MyWidgetResourceLib.fn.loadDependentWidgetsByIdWithoutResourcesToLoad(properties["dependent_widgets_id"], {
							search_attrs: search_attrs,
						}, opts);
						
						if (end_handler)
							MyWidgetResourceLib.fn.executeFunctions(end_handler, elm);
					}
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No element in loadElementFieldsResource method!");
			
			return false;
		},
		
		//Load data of an element widget and shows that data in the inner comboboxes. This function can be called by any html element with a 'load' resource defined and comboboxes as children. The resource result can be an associative list with records coming from the DB, or a simple list with alpha-numeric values. This method receives a second argument with an 'opts' object correspondent to some options that will be passed on execution of the correspondent resource.
		loadElementSelectFieldsResource: function(elm, opts) {
			if (elm) {
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var search_attrs = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_attrs");
				search_attrs = typeof search_attrs == "string" ? MyWidgetResourceLib.fn.parseJson(search_attrs) : search_attrs;
				
				var perms = MyWidgetResourceLib.PermissionHandler.getWidgetPermissions(elm);
				
				if (!perms["remove"]) {
					//save inner html
					var inner_html = null;
					
					if (!MyWidgetResourceLib.fn.existsNodeElementData(elm, "inner_html")) { //only executes the first time
						inner_html = elm.innerHTML;
						MyWidgetResourceLib.fn.setNodeElementData(elm, "inner_html", inner_html);
					}
					else
						inner_html = MyWidgetResourceLib.fn.getNodeElementData(elm, "inner_html");
					
					//prepare resource
					var resource = null;
					var end_handler = MyWidgetResourceLib.fn.isPlainObject(properties["end"]) ? properties["end"]["load"] : properties["end"];
					end_handler = MyWidgetResourceLib.fn.convertIntoFunctions(end_handler);
					
					if (elm.hasAttribute("data-widget-resources")) {
						//check if exists any hashtag with resources
						var exists_html_hash_tags = MyWidgetResourceLib.HashTagHandler.existsHtmlHashTags(inner_html);
						var exists_html_hash_tags_resources = MyWidgetResourceLib.HashTagHandler.existsHtmlHashTagsWithResources(inner_html);
						
						var complete_func = function(resource_elm, resources, resources_name, resources_cache_key) {
							if (
								(exists_html_hash_tags && MyWidgetResourceLib.ResourceHandler.existLoadedSLAResults(resources_name, resources_cache_key)) //replace simple hashtag in html if exists a parent resource. The hashtag is a relative hashtag, something like: #attribute_name#
								|| exists_html_hash_tags_resources //replace resource hashtag in html with 'SLA' or 'Resource' prefix, something like: #SLA[resource_name][attribute_name]# or #Resource[resource_name][attribute_name]#.
							) {
								//replace hashtags if apply to the current resource
								var inner_item_hash_tags_to_ignore = MyWidgetResourceLib.HashTagHandler.getWidgetResourceValueInnerItemsHtmlHashTagsBasedInResources(inner_html, resources_name, resources_cache_key);
								inner_html = MyWidgetResourceLib.HashTagHandler.replaceHtmlHashTagsWithResources(inner_html, resources_name, resources_cache_key, null, inner_item_hash_tags_to_ignore);
								elm.innerHTML = inner_html;
							}
							
							//save loaded resource data in elm
							MyWidgetResourceLib.FieldHandler.saveWidgetLoadedResourceValues(elm, resources_name, resources_cache_key);
							
							//load resources to select fields - The resources can be associative arrays with records coming from the DB, or simple arrays with alpha-numeric values.
							var select_fields = elm.querySelectorAll("select");
							var select_fields_name = [];
							
							for (var i = 0, ti = select_fields.length; i < ti; i++) {
								var select_field = select_fields[i];
								var name = MyWidgetResourceLib.FieldHandler.getWidgetResourceValueAttributeName(select_field);
								
								select_fields_name.push(name);
							}
							
							var values_by_name = {};
							var default_values_for_fields_without_name = [];
							
							for (var i = 0, ti = resources_name.length; i < ti; i++) {
								var resource_name = resources_name[i];
								var resource_cache_key = resources_cache_key ? resources_cache_key[i] : null;
								var records = MyWidgetResourceLib.ResourceHandler.getLoadedSLAResult(resource_name, resource_cache_key);
								
								if (MyWidgetResourceLib.fn.isArray(records))
									for (var j = 0, tj = records.length; j < tj; j++) {
										var record = records[j];
										
										for (var w = 0, tw = select_fields_name.length; w < tw; w++) {
											var select_field_name = select_fields_name[w];
											
											if (select_field_name) {
												var value = MyWidgetResourceLib.fn.isPlainObject(record) && record.hasOwnProperty(select_field_name) ? record[select_field_name] : record; //allow record to be an associative object, or to be an array with primitive values.
												
												if (!values_by_name.hasOwnProperty(select_field_name))
													values_by_name[select_field_name] = [];
												
												values_by_name[select_field_name].push(value);
											}
											else if (!MyWidgetResourceLib.fn.isPlainObject(record))
												default_values_for_fields_without_name.push(record);
										}
									}
							}
							/*console.log(select_field);
							console.log(select_fields_name);
							console.log(values_by_name);
							console.log(default_values_for_fields_without_name);*/
							
							for (var i = 0, ti = select_fields_name.length; i < ti; i++) {
								var select_field_name = select_fields_name[i];
								var select_field = select_fields[i];
								var value = null;
								
								if (select_field_name)
									value = values_by_name.hasOwnProperty(select_field_name) ? values_by_name[select_field_name] : null;
								else
									value = default_values_for_fields_without_name;
								
								MyWidgetResourceLib.FieldHandler.setFieldValue(select_field, value, {
									with_available_values: true,
									with_default: true
								});
							}
							
							//call complete handler
							var complete_handler = MyWidgetResourceLib.fn.isPlainObject(properties["complete"]) ? properties["complete"]["load"] : properties["complete"];
							complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
							
							if (complete_handler)
								MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
							
							//call complete handler from opts if exists
							var complete_handler = opts && opts["complete"] ? opts["complete"] : null;
							complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
							
							if (complete_handler)
								MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
							
							//reload dependent widgets, if apply
							MyWidgetResourceLib.fn.loadDependentWidgetsByIdWithoutResourcesToLoad(properties["dependent_widgets_id"], {
								search_attrs: search_attrs,
							}, opts);
							
							//call end handler
							if (end_handler)
								MyWidgetResourceLib.fn.executeFunctions(end_handler, elm, resources_name, resources_cache_key);
						};
						
						//prepare extra url
						var extra_url = opts && opts["extra_url"] ? opts["extra_url"] : "";
						var ignore_resource_conditions = false;
						
						if (search_attrs) {
							extra_url += "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_attrs, "search_attrs");
							ignore_resource_conditions = true;
						}
						
						var resource_opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? MyWidgetResourceLib.fn.assignObjectRecursively({}, opts) : {};
						resource_opts["ignore_resource_conditions"] = ignore_resource_conditions; //if search_attrs exists, ignore conditions
						resource_opts["extra_url"] = extra_url;
						resource_opts["label"] = elm.nodeName.toLowerCase() + (elm.classList.length > 0 ? "." + elm.classList.toString().replace(/\s/g, ".") : "");
						resource_opts["complete"] = complete_func;
						resource = MyWidgetResourceLib.ResourceHandler.executeGetWidgetResourcesRequest(elm, "load", resource_opts);
						
						if (resource) {
							//reset inner html
							if (exists_html_hash_tags_resources)
								elm.innerHTML = '';
							
							return true;
						}
						//else //no need to show this message bc maybe the user doesn't want any load resource
						//	MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No resource set in loadElementFieldsResource method. Please inform the web-developer accordingly...");
					}
					
					if (!resource) {
						//load inner item widgets. This is very important bc the ADD POPUP doesn't have any load handler, but we still need to init the inner items like the select boxes.
						var item_widgets = elm.querySelectorAll("[data-widget-item-resources-load]:not(.template-widget), [data-widget-resources-load]:not(.template-widget)");
						
						if (item_widgets)
							MyWidgetResourceLib.fn.each(item_widgets, function (idx, item_widget) { //:not(.template-widget) is very important so it doesn't load the resource when we are editing this widget through the LayoutUIEditor.
								item_widget.removeAttribute("data-widget-item-resources-load");
								item_widget.removeAttribute("data-widget-resources-load");
								
								MyWidgetResourceLib.ResourceHandler.loadWidgetResource(item_widget, {async: false}); //must be async false otherwise we will loose the the real attribute values, when the load function gets executed
							});
						
						//reload dependent widgets like the pagination elements, if apply
						MyWidgetResourceLib.fn.loadDependentWidgetsByIdWithoutResourcesToLoad(properties["dependent_widgets_id"], {
							search_attrs: search_attrs,
						}, opts);
						
						if (end_handler)
							MyWidgetResourceLib.fn.executeFunctions(end_handler, elm);
					}
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No element in loadElementFieldsResource method!");
			
			return false;
		},
		
		//Load data of a field widget. This function can be called by any html element with a 'load' resource defined.
		loadFieldResource: function(elm, opts) {
			if (elm) {
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var perms = MyWidgetResourceLib.PermissionHandler.getWidgetPermissions(elm);
				
				if (!perms["remove"]) {
					var resource = null;
					var end_handler = MyWidgetResourceLib.fn.isPlainObject(properties["end"]) ? properties["end"]["load"] : properties["end"];
					end_handler = MyWidgetResourceLib.fn.convertIntoFunctions(end_handler);
					
					if (elm.hasAttribute("data-widget-resources")) {
						var search_attrs = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_attrs");
						var search_types = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_types");
						var search_cases = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_cases");
						var search_operators = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_operators");
						
						search_attrs = typeof search_attrs == "string" && search_attrs[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_attrs) : search_attrs;
						search_types = typeof search_types == "string" && search_types[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_types) : search_types;
						search_cases = typeof search_cases == "string" && search_cases[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_cases) : search_cases;
						search_operators = typeof search_operators == "string" && search_operators[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_operators) : search_operators;
						
						//set default search
						if (!search_attrs || (MyWidgetResourceLib.fn.isPlainObject(search_attrs) && MyWidgetResourceLib.fn.isEmptyObject(search_attrs))) {
							var search_attrs = elm.getAttribute("data-widget-pks-attrs");
							search_attrs = search_attrs ? MyWidgetResourceLib.fn.parseJson(search_attrs) : null;
							
							if (MyWidgetResourceLib.fn.isPlainObject(search_attrs) && !MyWidgetResourceLib.fn.isEmptyObject(search_attrs))
								MyWidgetResourceLib.fn.setNodeElementData(elm, "search_attrs", search_attrs);
						}
						
						var complete_func = function(resource_elm, resources, resources_name, resources_cache_key) {
							//set resources to elm
							MyWidgetResourceLib.FieldHandler.onLoadFieldResource(elm, resources_name, resources_cache_key, properties);
							
							//call complete handler
							var complete_handler = MyWidgetResourceLib.fn.isPlainObject(properties["complete"]) ? properties["complete"]["load"] : properties["complete"];
							complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
							
							if (complete_handler)
								MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
							
							//call complete handler from opts if exists
							var complete_handler = opts && opts["complete"] ? opts["complete"] : null;
							complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
							
							if (complete_handler)
								MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
							
							//reload dependent widgets like the pagination elements, if apply
							MyWidgetResourceLib.fn.loadDependentWidgetsByIdWithoutResourcesToLoad(properties["dependent_widgets_id"], {}, opts);
							
							//call end handler
							if (end_handler)
								MyWidgetResourceLib.fn.executeFunctions(end_handler, elm, resources_name, resources_cache_key);
						};
						
						//prepare extra url
						var extra_url = opts && opts["extra_url"] ? opts["extra_url"] : "";
						var ignore_resource_conditions = false;
						
						if (MyWidgetResourceLib.fn.isPlainObject(search_attrs) && !MyWidgetResourceLib.fn.isEmptyObject(search_attrs)) {
							extra_url += "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_attrs, "search_attrs") 
								+ (search_types ? "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_types, "search_types") : "") 
								+ (search_cases ? "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_cases, "search_cases") : "") 
								+ (search_operators ? "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_operators, "search_operators") : "");
							ignore_resource_conditions = true;
						}
						
						var resource_opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? MyWidgetResourceLib.fn.assignObjectRecursively({}, opts) : {};
						resource_opts["ignore_resource_conditions"] = ignore_resource_conditions; //if search_attrs exists, ignore conditions
						resource_opts["extra_url"] = extra_url;
						resource_opts["label"] = "field box";
						resource_opts["complete"] = complete_func;
						resource = MyWidgetResourceLib.ResourceHandler.executeGetWidgetResourcesRequest(elm, "load", resource_opts);
						
						if (resource)
							return true;
						else
							MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No load resource set in loadFieldResource. Please inform the web-developer accordingly...");
					}
					
					if (!resource) {
						//reload dependent widgets like the pagination elements, if apply
						MyWidgetResourceLib.fn.loadDependentWidgetsByIdWithoutResourcesToLoad(properties["dependent_widgets_id"], {}, opts);
						
						if (end_handler)
							MyWidgetResourceLib.fn.executeFunctions(end_handler, elm);
					}
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No select element in loadFieldResource method!");
			
			return false;
		},
		
		//Load data of a field widget. This function can be called by any html element with a 'load' resource defined.
		//The difference between cacheFieldResource and loadFieldResource methods, is that the cacheFieldResource method checks if the 'load' resource was previously loaded and, if yes, use it instead of loading it again. Additionally only executes a resource without loading its values or executing the dependent_widget_ids. This is used to load the available_values.
		cacheFieldResource : function(elm, opts) {
			if (elm) {
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var perms = MyWidgetResourceLib.PermissionHandler.getWidgetPermissions(elm);
				
				if (!perms["remove"]) {
					var resource = null;
					var end_handler = MyWidgetResourceLib.fn.isPlainObject(properties["end"]) ? properties["end"]["load"] : properties["end"];
					end_handler = MyWidgetResourceLib.fn.convertIntoFunctions(end_handler);
					
					if (elm.hasAttribute("data-widget-resources")) {
						var complete_func = function(resource_elm, resources, resources_name, resources_cache_key) {
							//call complete handler
							var complete_handler = MyWidgetResourceLib.fn.isPlainObject(properties["complete"]) ? properties["complete"]["load"] : properties["complete"];
							complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
							
							if (complete_handler)
								MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
							
							//call complete handler from opts if exists
							var complete_handler = opts && opts["complete"] ? opts["complete"] : null;
							complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
							
							if (complete_handler)
								MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
							
							//call end handler
							if (end_handler)
								MyWidgetResourceLib.fn.executeFunctions(end_handler, elm, resources_name, resources_cache_key);
						};
						
						var resource_opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? MyWidgetResourceLib.fn.assignObjectRecursively({}, opts) : {};
						resource_opts["label"] = "field";
						resource_opts["complete"] = complete_func;
						resource = MyWidgetResourceLib.ResourceHandler.executeGetWidgetResourcesRequest(elm, "load", resource_opts);
						
						if (resource)
							return true;
						else
							MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No load resource set in cacheFieldResource. Please inform the web-developer accordingly...");
					}
					
					if (!resource) {
						if (end_handler)
							MyWidgetResourceLib.fn.executeFunctions(end_handler, elm);
					}
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No select element in cacheFieldResource method!");
			
			return false;
		},
		
		//Load data into a combobox. This function can be called by any combobox with a 'load' resource defined. In summary this handler calls the loadFieldResource method, loading a resource into the correspondent combobox, and then, on complete, selects the default value if exists, otherwise the first option of a combobox. Then add the attribute data-widget-resources-loaded to the dependent widgets and refreshes them, if exist.
		loadSelectFieldResource: function(elm, opts) {
			opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? opts : {};
			
			if (opts["complete"])
				opts["complete"] = MyWidgetResourceLib.fn.convertIntoFunctions(opts["complete"]); //may return null if there is no valid function
			
			if (!opts["complete"])
				opts["complete"] = [];
			
			opts["complete"].push(MyWidgetResourceLib.FieldHandler.setWidgetResourceValueDefaultValue);
			opts["complete"].push(MyWidgetResourceLib.FieldHandler.setSelectFieldFirstValue);
			opts["complete"].push(MyWidgetResourceLib.ResourceHandler.setDependentWidgetsResourcesLoadedAttribute);
			opts["complete"].push(MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInInputValueButIfEmptyGetAll);
			
			return MyWidgetResourceLib.FieldHandler.loadFieldResource(elm, opts);
		},
		
		//is called from the loadFieldResource to set the values from the loaded resource into the html node, if apply.
		onLoadFieldResource: function(elm, resources_name, resources_cache_key, properties) {
			var node_name = elm.nodeName.toUpperCase();
			
			//set attributes according with the resources, if apply
			MyWidgetResourceLib.FieldHandler.setWidgetResourceAttributes(elm, resources_name, resources_cache_key);
			
			//set resources values in the html node
			if (node_name == "SELECT") {
				var replacement_type = properties["load_resource_type"] ? properties["load_resource_type"] : "replace";
				var value = MyWidgetResourceLib.FieldHandler.getFieldValue(elm, {
					with_available_values: true,
					with_default: true,
					force: true,
				});
				
				//overwrite value with recent_loaded_value. This is set in the onLoadElementFieldsResource method
				var recent_loaded_value = MyWidgetResourceLib.fn.getNodeElementData(elm, "recent_loaded_value");
				
				if (recent_loaded_value != null)
					value = recent_loaded_value;
				
				//prepare select options
				var options = "";
				var empty_exists = false;
				
				for (var i = 0, t = resources_name.length; i < t; i++) {
					var resource_name = resources_name[i];
					var resource_cache_key = resources_cache_key ? resources_cache_key[i] : null;
					var resource_data = MyWidgetResourceLib.ResourceHandler.getLoadedSLAResult(resource_name, resource_cache_key);
					
					//convert array to object
					if (MyWidgetResourceLib.fn.isArray(resource_data)) {
						var new_resource_data = {};
						
						MyWidgetResourceLib.fn.each(resource_data, function(idx, item) {
					   		var k = !MyWidgetResourceLib.fn.isPlainObject(item) ? item : idx;
					   		new_resource_data[k] = item;
					   	});
					   	
					   	resource_data = new_resource_data;
					}
					
					//create new options html
					if (MyWidgetResourceLib.fn.isPlainObject(resource_data))
						for (var k in resource_data) {
							var l = resource_data[k];
							var option_html = '<option value="' + k + '">' + l + '</option>';
							
							options += option_html;
							
							if (!k)
								empty_exists = true;
						}
				}
				
				if (replacement_type == "append")
					elm.insertAdjacentHTML('beforeend', options); //last child
				else if (replacement_type == "prepend")
					elm.insertAdjacentHTML('afterbegin', options); //first child
				else {
					//add empty option if allow null is true
					if (!empty_exists) {
						var allow_null = elm.hasAttribute('data-allow-null') ? elm.getAttribute('data-allow-null') : elm.getAttribute('allownull');
						allow_null = ("" + allow_null).length && (("" + allow_null).toLowerCase() == 'false' || allow_null == '0') ? false : true;
						var empty_option = elm.querySelector("option[value=''], option[value='0']");
						var add_empty_option = allow_null || empty_option; //add empty option if existed previously or if allow_null is true. Note that is very important to add the empty option if already existed before even if the allow_null is null, bc if the user added before an empty option it was because he wanted to hard code it. This empty option could be useful to update other dependent comboxes.
						
						if (add_empty_option) {
							var option_html = empty_option ? empty_option.outerHTML : '<option value=""></option>';
							options = option_html + options;
						}
					}
					
					//update html
					elm.innerHTML = options;
				}
				
				MyWidgetResourceLib.FieldHandler.setFieldValue(elm, value, {
					with_available_values: true,
					with_default: true,
				});
				
				if (MyWidgetResourceLib.ItemHandler.isToggledItemAttributeFieldToField(elm))
					MyWidgetResourceLib.FieldHandler.onSetWidgetResourceValueForToggledItemAttributeSelectField(elm, value);
			}
			else
				MyWidgetResourceLib.FieldHandler.setWidgetResourceValue(elm, resources_name, resources_cache_key);
		},
		
		//Callback to be called after a load or display handler get executed, to clean all the hashtags left from the html inside of the node element passed as argument.
		onLoadElementFieldsResourceCleanAllHashTagsLeft: function(elm) {
			if (elm) {
				//TODO: find a way to loop asyncronously
			   	MyWidgetResourceLib.fn.each(elm.attributes, function(idx, attribute) {
			   		var attribute_name = attribute.name;
					var attribute_value = attribute.value;
					
					if (attribute_value && ("" + attribute_value).indexOf("#"))
						attribute.value = MyWidgetResourceLib.HashTagHandler.replaceHtmlHashTagsWithEmptyValues(attribute_value);
			   	});
			   	
			   	//TODO: find a way to loop asyncronously
			   	MyWidgetResourceLib.fn.each(elm.children, function(idx, child) {
			   		MyWidgetResourceLib.FieldHandler.onLoadElementFieldsResourceCleanAllHashTagsLeft(child);
			   	});
			}
		},
		
		//Find inside of a form, popup or data-widget-item, the search widgets and load their dependencies.
		//This method is used when we have comboboxes that depend on another fields, like another combobox
		//elm could be a form
		onLoadElementFieldsResourceWithSelectSearchFields: function(elm, resources_name, resources_cache_key, resource_index) {
			if (elm && resources_name) {
				var search_elms = elm.querySelectorAll("[data-widget-search]");
				
				if (search_elms && search_elms.length > 0) {
					var search_fields_to_check = [];
					var search_fields_to_trigger = [];
					
					//add the recent_loaded_value attribute to all the search comboboxes
					for (var i = 0, ti = search_elms.length; i < ti; i++) {
						var search_elm = search_elms[i];
						var search_fields = search_elm.querySelectorAll("input, select, textarea");
						
						if (search_fields)
							for (var j = 0, tj = search_fields.length; j < tj; j++) {
								var search_field = search_fields[j];
								var attribute_name = MyWidgetResourceLib.FieldHandler.getWidgetResourceValueAttributeName(search_field); //cannot be this.getWidgetResourceValueAttributeName bc the onLoadElementFieldsResource method is a handler that will be called directly without scope.
								if (attribute_name) {
									MyWidgetResourceLib.FieldHandler.prepareDependentsSelectFieldsOnLoadElementFieldsResourceWithSelectSearchField(search_field, resources_name, resources_cache_key, resource_index); //cannot be this.prepareDependentsSelectFieldsOnLoadElementFieldsResourceWithSelectSearchField bc the onLoadElementFieldsResource method is a handler that will be called directly without scope.
									
									search_fields_to_check.push(search_field);
									search_fields_to_trigger.push(search_field);
								}
							}
					}
					
					//only execute the primary fields, bc the dependent fields, will be executed by the on-change event of the primary fields.
					for (var i = 0, ti = search_fields_to_check.length; i < ti; i++) {
						var search_field = search_fields_to_check[i];
						
						var search_field_properties = MyWidgetResourceLib.fn.getWidgetProperties(search_field);
						var dependent_widgets_id = MyWidgetResourceLib.fn.prepareDependentWidgetsId(search_field_properties["dependent_widgets_id"]);
						
						if (dependent_widgets_id)
							for (var j = 0, tj = dependent_widgets_id.length; j < tj; j++) {
								var dependent_widget_id = ("" + dependent_widgets_id[j]).replace(/(^\s+|\s+$)/g, ""); //trim. Note that the dependent_widget_id maybe a composite selector, so do not replace white spaces between words.
								
								if (dependent_widget_id.length > 0)
									search_fields_to_trigger = search_fields_to_trigger.filter(function(sub_search_field) {
										return sub_search_field && sub_search_field.id != dependent_widget_id;
									});
							}
					}
					
					//trigger event in search field
					//console.log(search_fields_to_trigger);
					for (var i = 0, ti = search_fields_to_trigger.length; i < ti; i++) {
						var search_field = search_fields_to_trigger[i];
						
						if (search_field.hasAttribute("onChange"))
							MyWidgetResourceLib.fn.trigger(search_field, "change");
						else if (search_field.hasAttribute("onBlur"))
							MyWidgetResourceLib.fn.trigger(search_field, "blur");
						else if (search_field.hasAttribute("onKeyUp"))
							MyWidgetResourceLib.fn.trigger(search_field, "keyUp");
						else if (search_field.hasAttribute("onKeyDown"))
							MyWidgetResourceLib.fn.trigger(search_field, "keyDown");
					}
				}
			}
		},
		
		prepareDependentsSelectFieldsOnLoadElementFieldsResourceWithSelectSearchField: function(search_field, resources_name, resources_cache_key, resource_index) {
			//find dependent widgets
			var search_field_properties = MyWidgetResourceLib.fn.getWidgetProperties(search_field);
			var dependent_widgets_id = MyWidgetResourceLib.fn.prepareDependentWidgetsId(search_field_properties["dependent_widgets_id"]);
			
			if (dependent_widgets_id)
				for (var w = 0, tw = dependent_widgets_id.length; w < tw; w++) {
					var dependent_widget_id = ("" + dependent_widgets_id[w]).replace(/(^\s+|\s+$)/g, ""); //trim. Note that the dependent_widget_id maybe a composite selector, so do not replace white spaces between words.
					
					if (dependent_widget_id.length > 0) {
						var dependent_widget = document.querySelector("#" + dependent_widget_id);
						
						//only allow select fields
						if (dependent_widget && dependent_widget.nodeName == "SELECT") {
							var dependent_widget_attribute_name = null;
							
							//if is a select toggled field, show the select field by default, but only if attribute_name exists
							if (MyWidgetResourceLib.ItemHandler.isToggledItemAttributeFieldToField(dependent_widget) && !MyWidgetResourceLib.ItemHandler.isToggledItemAttributeFieldShown(dependent_widget)) {
								var parent = dependent_widget.closest("[data-widget-item-attribute-field-toggle-select-input]");
								var input = parent.querySelector("input");
								
								if (input) {
									dependent_widget_attribute_name = this.getWidgetResourceValueAttributeName(input);
									
									if (dependent_widget_attribute_name)
										MyWidgetResourceLib.ItemHandler.toggleItemAttributeInputFieldToSelectField(dependent_widget);
								}
							}
							else
								dependent_widget_attribute_name = this.getWidgetResourceValueAttributeName(dependent_widget);
							
							if (dependent_widget_attribute_name) {
								//update all select fields according with resources_name values on: data(...), this is, save resources_name value in data, to set it again later when it calls the method: loadFieldResource
								var recent_loaded_value = null;
								
								for (var i = 0, ti = resources_name.length; i < ti; i++) {
									var resource_name = resources_name[i];
									var resource_cache_key = resources_cache_key ? resources_cache_key[i] : null;
									recent_loaded_value = this.getWidgetResourceValueValue(dependent_widget, resource_name, resource_cache_key, resource_index);
									
									if (recent_loaded_value !== null)
										break;
								}
								
								MyWidgetResourceLib.fn.setNodeElementData(dependent_widget, "recent_loaded_value", recent_loaded_value);
							}
						}
					}
				}
		},
		
		//This method saves the loaded data into the element so we can use it in javascript, as an example, to load another inner elements based in this data. However note that if the main element allows "update attribute" action, then this data won't be update, bc it won't be loaded again. The "update attribute" action updates the new value directly in the correspondent node, but do not load its data. So this setting will only have the loaded data.
		saveWidgetLoadedDataValues: function(elm, data, data_index) {
			if (data) {
				var loaded_data = MyWidgetResourceLib.fn.isNumeric(data_index) && MyWidgetResourceLib.fn.isArray(data) ? data[data_index] : data;
				
				if (loaded_data)
					MyWidgetResourceLib.fn.setNodeElementData(elm, "loaded_data", loaded_data);
			}
		},
		
		//This method saves the resource loaded data into the element so we can use it in javascript, as an example, to load another inner elements based in this resource data. However note that if the main element allows "update attribute" action, then this data won't be update, bc it won't be loaded again. The "update attribute" action updates the new value directly in the correspondent node, but do not load its resource data. So this setting will only have the resource loaded data.
		saveWidgetLoadedResourceValues: function(elm, resources_name, resources_cache_key, resource_index) {
			if (resources_name) {
				var loaded_resources_data = MyWidgetResourceLib.fn.getNodeElementData(elm, "loaded_resources_data");
				
				if (!MyWidgetResourceLib.fn.isPlainObject(loaded_resources_data))
					loaded_resources_data = {};
				
				resources_name = MyWidgetResourceLib.fn.isArray(resources_name) ? resources_name : [resources_name];
				resources_cache_key = MyWidgetResourceLib.fn.isArray(resources_cache_key) ? resources_cache_key : [resources_cache_key];
				
				for (var i = 0, t = resources_name.length; i < t; i++) {
					var resource_name = resources_name[i];
					
					if (resource_name) {
						var resource_cache_key = resources_cache_key ? resources_cache_key[i] : null;
						var resource_data = MyWidgetResourceLib.ResourceHandler.getLoadedSLAResult(resource_name, resource_cache_key);
						var loaded_resource_data = MyWidgetResourceLib.fn.isNumeric(resource_index) && MyWidgetResourceLib.fn.isArray(resource_data) ? resource_data[resource_index] : resource_data;
						
						if (loaded_resource_data)
							loaded_resources_data[resource_name] = loaded_resource_data;
					}
				}
				
				if (!MyWidgetResourceLib.fn.isEmptyObject(loaded_resources_data))
					MyWidgetResourceLib.fn.setNodeElementData(elm, "loaded_resources_data", loaded_resources_data);
			}
		},
		
		//set the field attributes according with some data
		setWidgetDataAttributes: function(elm, data, data_index) {
			if (elm.hasAttributes()) {
				for (var i = 0, t = elm.attributes.length; i < t; i++) {
					var attribute = elm.attributes[i];
					var attribute_name = attribute.name;
					var attribute_value = attribute.value;
					
					var exists_html_hash_tags = MyWidgetResourceLib.HashTagHandler.existsHtmlHashTags(attribute_value);
					var exists_html_hash_tags_resources = MyWidgetResourceLib.HashTagHandler.existsHtmlHashTagsWithResources(attribute_value);
					
					if (exists_html_hash_tags || exists_html_hash_tags_resources) {
						var new_value = MyWidgetResourceLib.HashTagHandler.replaceHtmlHashTagsWithData(attribute_value, data, data_index);
						
						if (new_value != attribute_value)
							elm.setAttribute(attribute_name, new_value);
					}
				}
			}
		},
		
		//set the field attributes according with a loaded resource
		setWidgetResourceAttributes: function(elm, resources_name, resources_cache_key, resource_index) {
			if (elm.hasAttributes()) {
				for (var i = 0, t = elm.attributes.length; i < t; i++) {
					var attribute = elm.attributes[i];
					var attribute_name = attribute.name;
					var attribute_value = attribute.value;
					
					var exists_html_hash_tags = MyWidgetResourceLib.HashTagHandler.existsHtmlHashTags(attribute_value);
					var exists_html_hash_tags_resources = MyWidgetResourceLib.HashTagHandler.existsHtmlHashTagsWithResources(attribute_value);
					
					if (
						(exists_html_hash_tags && MyWidgetResourceLib.ResourceHandler.existLoadedSLAResults(resources_name, resources_cache_key)) //replace simple hashtag in html if exists a parent resource. The hashtag is a relative hashtag, something like: #attribute_name#
						|| exists_html_hash_tags_resources //replace resource hashtag in html with 'SLA' or 'Resource' prefix, something like: #SLA[resource_name][attribute_name]# or #Resource[resource_name][attribute_name]#.
					) {
						var new_value = MyWidgetResourceLib.HashTagHandler.replaceHtmlHashTagsWithResources(attribute_value, resources_name, resources_cache_key, resource_index);
						
						if (new_value != attribute_value)
							elm.setAttribute(attribute_name, new_value);
					}
				}
			}
		},
		
		//search children fields and set their values according with some data
		setWidgetDataValues: function(elm, data, data_index) {
			if (elm) {
				var sub_elms = elm.querySelectorAll("input, select, textarea, [data-widget-resource-value]");
				var node_name = elm.nodeName.toUpperCase();
				
				sub_elms = sub_elms ? Array.from(sub_elms) : [];
				
				if (node_name == "INPUT" || node_name == "TEXTAREA" || node_name == "SELECT" || elm.hasAttribute("data-widget-resource-value"))
					sub_elms.push(elm);
				
				if (sub_elms)
					//TODO: find a way to loop asyncronously
				   	MyWidgetResourceLib.fn.each(sub_elms, function(idx, sub_elm) {
				   		MyWidgetResourceLib.FieldHandler.setWidgetDataValue(sub_elm, data, data_index);
				   	});
			}
		},
		
		//search children fields and set their values according with a loaded resource
		setWidgetResourceValues: function(elm, resources_name, resources_cache_key, resource_index) {
			if (elm) {
				var sub_elms = elm.querySelectorAll("input, select, textarea, [data-widget-resource-value], [data-widget-item-resources-load]:not(.template-widget), [data-widget-resources-load]:not(.template-widget)"); //load also elements with data-widget-item-resources-load attribute
				var node_name = elm.nodeName.toUpperCase();
				
				sub_elms = sub_elms ? Array.from(sub_elms) : [];
				
				if (node_name == "INPUT" || node_name == "TEXTAREA" || node_name == "SELECT" || elm.hasAttribute("data-widget-resource-value"))
					sub_elms.push(elm);
				
				if (sub_elms)
					//TODO: find a way to loop asyncronously
				   	MyWidgetResourceLib.fn.each(sub_elms, function(idx, sub_elm) {
				   		MyWidgetResourceLib.FieldHandler.setWidgetResourceValue(sub_elm, resources_name, resources_cache_key, resource_index);
				   	});
			}
		},
		
		//set a field value according with a loaded resource
		setWidgetResourceValue: function(elm, resources_name, resources_cache_key, resource_index) {
			//prepare data-widget-resource-value
			/* data-widget-resource-value: {
			 *	resource_name: '...', 
			 *	attribute: '...', 
			 *	index: 'auto|0|1|...', 
			 *	available_values: {json object with the correspondent manual or dynamic items}, 
			 * 	default: '...'
			 *	type: replace|append|prepend,
			 * }
			 * data-widget-resource-value: attribute
			*/
			if (elm) {
				var node_name = elm.nodeName.toUpperCase();
				
				//check if item needs to be inited (this is, if contains any attribute data-widget-resources-load or data-widget-item-resources-load) and if yes, inited that html, but before anything else...
				if (!elm.classList.contains("template-widget") && (elm.hasAttribute("data-widget-item-resources-load") || elm.hasAttribute("data-widget-resources-load"))) {
					elm.removeAttribute("data-widget-item-resources-load");
					elm.removeAttribute("data-widget-resources-load");
					
					//TODO: find a way to pass dynamic values to the resources based in the resources_name and resource_index variables.
					
					MyWidgetResourceLib.ResourceHandler.loadWidgetResource(elm, {async: false}); //must be async false to run the code below otherwise we will loose the real replacement, when the load function gets executed
				}
				
				if (node_name == "INPUT" || node_name == "TEXTAREA" || node_name == "SELECT" || elm.hasAttribute("data-widget-resource-value")) {
					//prepare resource value and replacement type
					var replacement = null;
					resources_name = MyWidgetResourceLib.fn.isArray(resources_name) ? resources_name : [resources_name];
					resources_cache_key = MyWidgetResourceLib.fn.isArray(resources_cache_key) ? resources_cache_key : [resources_cache_key];
					
					for (var i = 0, t = resources_name.length; i < t; i++) {
						var resource_name = resources_name[i];
						var resource_cache_key = resources_cache_key ? resources_cache_key[i] : null;
						replacement = this.getWidgetResourceValueValue(elm, resource_name, resource_cache_key, resource_index);
						
						if (replacement !== null)
							break;
					}
					
					if (replacement === null || typeof replacement == "undefined")
						replacement = "";
					
					//add replacement to html
					var node_name = elm.nodeName.toUpperCase();
					
					if (node_name == "INPUT" || node_name == "TEXTAREA" || node_name == "SELECT") {
						var attribute_name = this.getWidgetResourceValueAttributeName(elm);
						
						if (attribute_name) { //only if exists the "name" or "data-widget-resource-value" attributes in elm.
							//console.log(attribute_name+":"+replacement);
							
							this.setFieldValue(elm, replacement, {
								with_available_values: true,
								with_default: true,
							});
							
							if (node_name == "SELECT" && MyWidgetResourceLib.ItemHandler.isToggledItemAttributeFieldToField(elm))
								this.onSetWidgetResourceValueForToggledItemAttributeSelectField(elm, replacement);
						}
					}
					else
						this.setFieldValue(elm, replacement, {
							with_available_values: true,
							with_default: true,
						});
				}
			}
		},
		
		//set a field value according with some data
		setWidgetDataValue: function(elm, data, data_index) {
			if (elm) {
				var node_name = elm.nodeName.toUpperCase();
				
				if (node_name == "INPUT" || node_name == "TEXTAREA" || node_name == "SELECT" || elm.hasAttribute("data-widget-resource-value")) {
					//check if item needs to be inited (this is, if contains any attribute data-widget-resources-load or data-widget-item-resources-load) and if yes, inited that html, but before anything else...
					if (!elm.classList.contains("template-widget") && (elm.hasAttribute("data-widget-item-resources-load") || elm.hasAttribute("data-widget-resources-load"))) {
						elm.removeAttribute("data-widget-item-resources-load");
						elm.removeAttribute("data-widget-resources-load");
						
						//TODO: find a way to pass dynamic values to the resources based in the data and data_index variables.
						
						MyWidgetResourceLib.ResourceHandler.loadWidgetResource(elm, {async: false}); //must be async false to run the code below otherwise we will loose the real replacement, when the load function gets executed
					}
					
					//prepare resource value and replacement type
					var attribute_name = this.getWidgetResourceValueAttributeName(elm);
					var selectors = [];
					
					if (MyWidgetResourceLib.fn.isNumeric(data_index) || data_index)
						selectors.push("idx");
					
					if (MyWidgetResourceLib.fn.isNumeric(attribute_name) || attribute_name)
						selectors.push(attribute_name);
					
					var replacement = selectors.length ? MyWidgetResourceLib.HashTagHandler.parseNewInputData("#[" + selectors.join("][") + "]#", data, data_index) : data;
					
					if (replacement === null || typeof replacement == "undefined")
						replacement = "";
					
					//add replacement to html
					var node_name = elm.nodeName.toUpperCase();
					
					if (node_name == "INPUT" || node_name == "TEXTAREA" || node_name == "SELECT") {
						if (MyWidgetResourceLib.fn.isNumeric(attribute_name) || attribute_name) { //only if exists the "name" or "data-widget-resource-value" attributes in elm.
							//console.log(attribute_name+":"+replacement);
							
							this.setFieldValue(elm, replacement, {
								with_available_values: true,
								with_default: true,
							});
							
							if (node_name == "SELECT" && MyWidgetResourceLib.ItemHandler.isToggledItemAttributeFieldToField(elm))
								this.onSetWidgetResourceValueForToggledItemAttributeSelectField(elm, replacement);
						}
					}
					else
						this.setFieldValue(elm, replacement, {
							with_available_values: true,
							with_default: true,
						});
				}
			}
		},
		
		onSetWidgetResourceValueForToggledItemAttributeSelectField: function(elm, value) {
			if (elm && elm.value != value && MyWidgetResourceLib.ItemHandler.isToggledItemAttributeFieldShown(elm)) {
				MyWidgetResourceLib.ItemHandler.toggleItemAttributeSelectFieldToInputField(elm);
				
				var parent = elm.closest("[data-widget-item-attribute-field-toggle-select-input]");
				var input = parent.querySelector("input");
				
				if (input)
					input.value = value;
			}
		},
		
		checkResourceItemFields: function(elm, widget) {
			var status = false;
			
			if (elm) {
				//prepare attrs from checking fields, if MyJSLib.js exists
				var attrs = null;
				var message = null;
				
				if (typeof MyJSLib != "undefined" && typeof MyJSLib.FormHandler == "object") {
					var node_name = elm.nodeName.toUpperCase();
					var items = null;
					
					if (node_name == "INPUT" || node_name == "TEXTAREA" || node_name == "SELECT")
						items = [elm];
					else
						items = MyJSLib.FormHandler.getFormElements(elm);
					
					attrs = MyJSLib.FormHandler.getFormElementsChecks(items);
					message = MyJSLib.FormHandler.getFormErrorMessage(attrs);
				}
				
				//prepare check handler if exists
				var widget_properties = widget ? MyWidgetResourceLib.fn.getWidgetProperties(widget) : {};
				var handler = MyWidgetResourceLib.fn.convertIntoFunctions(widget_properties["check"]);
				
				//execute widget handler or default one, if MyJSLib.js exists
				status = true;
				
				if (handler)
					status = MyWidgetResourceLib.fn.executeFunctions(handler, elm, widget, attrs, message);
				else if (attrs && message) {
					var html = message.replace(/\n/g, "<br/>");
					
					MyWidgetResourceLib.MessageHandler.showErrorMessage(html);
					status = false;
				}
			}
			
			return status;
		},
		
		isResourceFieldActionConfirmed: function(elm) {
			if (elm && (elm.getAttribute('data-confirmation') == "1" || elm.getAttribute('confirmation') == "1")) {
				var confirm_message = elm.getAttribute('data-confirmation-message') ? elm.getAttribute('data-confirmation-message') : (
					elm.getAttribute('confirmationmessage') ? elm.getAttribute('confirmationmessage') : (
						typeof MyJSLib != "undefined" && typeof MyJSLib.FormHandler == "object" ? MyJSLib.FormHandler.messages["confirmation"] : ""
					)
				);
				
				if (confirm_message)
					return confirm(confirm_message);
			}
			
			return true;
		},
		
		//widget_selector:[data-widget-list], form; item_selector:[data-widget-item], form; resource_key:update
		//widget_selector:[data-widget-list], form; item_selector:[data-widget-add], form; resource_key:add
		executeResourceItemFieldsAction: function(elm, widget_selector, item_selector, resource_key, is_new_record, opts) {
			if (elm) {
				var perms = MyWidgetResourceLib.PermissionHandler.getWidgetPermissions(elm);
				
				if (!perms["remove"] && MyWidgetResourceLib.FieldHandler.isResourceFieldActionConfirmed(elm)) {
					var item = elm.closest(item_selector);
					var widget = item ? item.closest(widget_selector) : null;
					
					if (widget) {
						var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
						var widget_properties = MyWidgetResourceLib.fn.getWidgetProperties(widget);
						var search_attrs = item.getAttribute("data-widget-pks-attrs");
						search_attrs = search_attrs ? MyWidgetResourceLib.fn.parseJson(search_attrs) : null;
						
						if (!is_new_record && !search_attrs) {
							MyWidgetResourceLib.MessageHandler.showErrorMessage("Search attrs cannot be undefined in executeResourceItemFieldsAction method.");
						}
						else if (MyWidgetResourceLib.FieldHandler.checkResourceItemFields(item, widget)) {
							var post_data = {
								conditions: search_attrs,
								attributes: MyWidgetResourceLib.FieldHandler.getFieldsValues(item, {
									with_available_values: true,
									with_default: true,
								})
							};
							
							var files_data = MyWidgetResourceLib.FieldHandler.getFileInputsData(item);
							var target = item.hasAttribute("target") ? item.getAttribute("target") : null;
							
							var end_handler = MyWidgetResourceLib.fn.isPlainObject(widget_properties["end"]) ? widget_properties["end"][resource_key] : widget_properties["end"];
							end_handler = MyWidgetResourceLib.fn.convertIntoFunctions(end_handler);
							
							var complete_func = function(resource_elm, resources, resources_name, resources_cache_key) {
								//call complete handler
								var complete_handler = MyWidgetResourceLib.fn.isPlainObject(widget_properties["complete"]) ? widget_properties["complete"][resource_key] : widget_properties["complete"];
								complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
								
								if (complete_handler)
									MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
								
								//call complete handler from opts if exists
								var complete_handler = opts && opts["complete"] ? opts["complete"] : null;
								complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
								
								if (complete_handler)
									MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
								
								//update data-widget-pks-attrs in case some of these fields be editable, so the next time we execute a save or remove action, the pks are updated with the new user defined values.
								MyWidgetResourceLib.FieldHandler.updateItemPKsAttributesWithNewValues(item, post_data["attributes"]);
								MyWidgetResourceLib.FieldHandler.updateSearchAttrsWithNewValues(search_attrs, post_data["attributes"]);
								
								//reload dependent widgets
								var dependent_widgets_id = MyWidgetResourceLib.fn.prepareDependentWidgetsId(widget_properties["dependent_widgets_id"]);
								
								if (widget != item)
									dependent_widgets_id = dependent_widgets_id.concat(MyWidgetResourceLib.fn.prepareDependentWidgetsId(properties["dependent_widgets_id"]));
								
								opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? opts : {};
								opts["purge_cache"] = true;
								
								if (opts["include_opts_search_attrs"]) 
									opts["search_attrs"] = search_attrs;
								
								MyWidgetResourceLib.fn.loadDependentWidgetsById(dependent_widgets_id, {}, opts);
								
								//call end handler
								if (end_handler)
									MyWidgetResourceLib.fn.executeFunctions(end_handler, elm);
							};
							
							var resource_opts = {
								ignore_empty_resource: true,
								post_data: post_data, 
								files_data: files_data,
								label: "field", 
								complete: complete_func,
								target: target,
							};
							
							if (opts && opts.hasOwnProperty("async"))
								resource_opts["async"] = opts["async"];
							
							if (widget.nodeName == "FORM" && widget.getAttribute("method"))
								resource_opts["method"] = widget.getAttribute("method");
							
							var resource = MyWidgetResourceLib.ResourceHandler.executeSetWidgetResourcesRequest(widget, resource_key, resource_opts);
							
							if (!resource) {
								MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No resource set in executeResourceItemFieldsAction method. Please inform the web-developer accordingly...");
								
								if (end_handler)
									MyWidgetResourceLib.fn.executeFunctions(end_handler, elm);
							}
						}
					}
					else
						MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No " + widget_selector + " element in executeResourceItemFieldsAction method!");
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No element in executeResourceItemFieldsAction method!");
		},
		
		//widget_selector:[data-widget-list]; item_selector:[data-widget-item]; resource_key:update_attribute
		//widget_selector:form; item_selector:form; resource_key:update_attribute
		executeResourceItemFieldAttributeAction: function(elm, widget_selector, item_selector, resource_key, is_new_record, opts) {
			if (elm) {
				var perms = MyWidgetResourceLib.PermissionHandler.getWidgetPermissions(elm);
				
				if (!perms["remove"] && MyWidgetResourceLib.FieldHandler.isResourceFieldActionConfirmed(elm)) {
					var item = elm.closest(item_selector);
					var widget = item ? item.closest(widget_selector) : null;
					
					if (widget) {
						var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
						var widget_properties = MyWidgetResourceLib.fn.getWidgetProperties(widget);
						var search_attrs = item.getAttribute("data-widget-pks-attrs");
						search_attrs = search_attrs ? MyWidgetResourceLib.fn.parseJson(search_attrs) : null;
						
						if (!is_new_record && !search_attrs)
							MyWidgetResourceLib.MessageHandler.showErrorMessage("Search attrs cannot be undefined in executeResourceItemFieldAttributeAction method.");
						else {
							var attribute_name = MyWidgetResourceLib.FieldHandler.getWidgetResourceValueAttributeName(elm, {force_input_name: true});
							
							if (!attribute_name)
								MyWidgetResourceLib.MessageHandler.showErrorMessage("Attribute name cannot be undefined in executeResourceItemFieldAttributeAction method.");
							else if (MyWidgetResourceLib.FieldHandler.checkResourceItemFields(elm, widget)) {
								var attribute_value = MyWidgetResourceLib.FieldHandler.isEmptyCheckbox(elm) ? "" : MyWidgetResourceLib.FieldHandler.getFieldValue(elm, {
									with_available_values: true,
									with_default: true,
								});
								//console.log(attribute_name);
								//console.log(attribute_value);
								
								//only save if value is different. Use !== to be sure that we are comparing the value types too. This doesn't apply for radio buttons/inputs
								var cache_value = opts && opts["cache_value"];
								var is_no_cache_btn = elm.nodeName.toLowerCase() == "input" && (elm.type == "checkbox" || elm.type == "radio"); //if radio btn the value is always the same, because the values changed according different buttons. So we need to disable the cache for radio btns.
								var is_different = !cache_value || is_no_cache_btn || attribute_value !== MyWidgetResourceLib.fn.getNodeElementData(elm, "saved_value");
								
								if (is_different) {
									var post_data = {
										conditions: search_attrs,
										attributes: {}
									};
									post_data["attributes"][attribute_name] = attribute_value;
									
									var files_data = MyWidgetResourceLib.FieldHandler.getFileInputsData(item);
									var target = item.hasAttribute("target") ? item.getAttribute("target") : null;
									
									var end_handler = MyWidgetResourceLib.fn.isPlainObject(widget_properties["end"]) ? widget_properties["end"][resource_key] : widget_properties["end"];
									end_handler = MyWidgetResourceLib.fn.convertIntoFunctions(end_handler);
									
									var complete_func = function(resource_elm, resources, resources_name, resources_cache_key) {
										if (cache_value)
											MyWidgetResourceLib.fn.setNodeElementData(elm, "saved_value", attribute_value);
										
										//call complete handler
										var complete_handler = MyWidgetResourceLib.fn.isPlainObject(widget_properties["complete"]) ? widget_properties["complete"][resource_key] : widget_properties["complete"];
										complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
										
										if (complete_handler)
											MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
										
										//call complete handler from opts if exists
										var complete_handler = opts && opts["complete"] ? opts["complete"] : null;
										complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
										
										if (complete_handler)
											MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
										
										//update data-widget-pks-attrs in case some of these fields be editable, so the next time we execute a save or remove action, the pks are updated with the new user defined values.
										MyWidgetResourceLib.FieldHandler.updateItemPKsAttributesWithNewValues(item, post_data["attributes"]);
										MyWidgetResourceLib.FieldHandler.updateSearchAttrsWithNewValues(search_attrs, post_data["attributes"]);
										
										//reload dependent widgets
										var dependent_widgets_id = MyWidgetResourceLib.fn.prepareDependentWidgetsId(widget_properties["dependent_widgets_id"]);
										
										if (widget != item)
											dependent_widgets_id = dependent_widgets_id.concat(MyWidgetResourceLib.fn.prepareDependentWidgetsId(properties["dependent_widgets_id"]));
										
										opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? opts : {};
										opts["purge_cache"] = true;
										
										if (opts["include_opts_search_attrs"]) 
											opts["search_attrs"] = search_attrs;
										
										MyWidgetResourceLib.fn.loadDependentWidgetsById(dependent_widgets_id, {}, opts);
										
										//call end handler
										if (end_handler)
											MyWidgetResourceLib.fn.executeFunctions(end_handler, elm);
									};
									
									var resource_opts = {
										ignore_empty_resource: true,
										post_data: post_data,
										files_data: files_data,
										label: resource_key, 
										complete: complete_func,
										target: target,
									};
									
									if (opts && opts.hasOwnProperty("async"))
										resource_opts["async"] = opts["async"];
									
									if (widget.nodeName == "FORM" && widget.getAttribute("method"))
										resource_opts["method"] = widget.getAttribute("method");
									
									var resource = MyWidgetResourceLib.ResourceHandler.executeSetWidgetResourcesRequest(widget, resource_key, resource_opts);
									
									if (!resource) {
										MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No " + resource_key + " resource set in executeResourceItemFieldAttributeAction. Please inform the web-developer accordingly...");
										
										if (end_handler)
											MyWidgetResourceLib.fn.executeFunctions(end_handler, elm);
									}
								}
							}
						}
					}
					else
						MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No " + widget_selector + " element in executeResourceItemFieldAttributeAction method!");
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No element in executeResourceItemFieldAttributeAction method!");
		},
		
		//widget_selector:[data-widget-list] ; item_selector:[data-widget-item]; resource_key:remove
		//widget_selector:form; item_selector:form; resource_key:remove
		removeResourceItemFromItemFieldAction: function(elm, widget_selector, item_selector, resource_key, opts) {
			if (elm) {
				var perms = MyWidgetResourceLib.PermissionHandler.getWidgetPermissions(elm);
				
				if (!perms["remove"] && MyWidgetResourceLib.FieldHandler.isResourceFieldActionConfirmed(elm)) {
					var item = elm.closest(item_selector);
					var widget = item ? item.closest(widget_selector) : null;
					
					if (widget) {
						var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
						var widget_properties = MyWidgetResourceLib.fn.getWidgetProperties(widget);
						var search_attrs = item.getAttribute("data-widget-pks-attrs");
						search_attrs = search_attrs ? MyWidgetResourceLib.fn.parseJson(search_attrs) : null;
						
						if (!search_attrs) {
							MyWidgetResourceLib.MessageHandler.showErrorMessage("Search attrs cannot be undefined in removeResourceItemFromItemFieldAction method.");
						}
						else {
							var post_data = {
								conditions: search_attrs
							};
							
							var end_handler = MyWidgetResourceLib.fn.isPlainObject(widget_properties["end"]) ? widget_properties["end"]["remove"] : widget_properties["end"];
							end_handler = MyWidgetResourceLib.fn.convertIntoFunctions(end_handler);
							
							var complete_func = function(resource_elm, resources, resources_name, resources_cache_key) {
								//call complete handler
								var complete_handler = MyWidgetResourceLib.fn.isPlainObject(widget_properties["complete"]) ? widget_properties["complete"]["remove"] : widget_properties["complete"];
								complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
								
								if (complete_handler)
									MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
								
								//call complete handler from opts if exists
								var complete_handler = opts && opts["complete"] ? opts["complete"] : null;
								complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
								
								if (complete_handler)
									MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
								
								//reload dependent widgets
								var dependent_widgets_id = MyWidgetResourceLib.fn.prepareDependentWidgetsId(widget_properties["dependent_widgets_id"]);
								dependent_widgets_id = dependent_widgets_id.concat(MyWidgetResourceLib.fn.prepareDependentWidgetsId(properties["dependent_widgets_id"]));
								
								opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? opts : {};
								opts["purge_cache"] = true;
								
								if (opts["include_opts_search_attrs"]) 
									opts["search_attrs"] = search_attrs;
								
								MyWidgetResourceLib.fn.loadDependentWidgetsById(dependent_widgets_id, {}, opts);
								
								//call end handler
								if (end_handler)
									MyWidgetResourceLib.fn.executeFunctions(end_handler, elm);
							};
							
							var resource_opts = {
								ignore_empty_resource: true,
								post_data: post_data, 
								label: resource_key, 
								complete: complete_func
							};
							
							if (opts && opts.hasOwnProperty("async"))
								resource_opts["async"] = opts["async"];
							
							if (widget.nodeName == "FORM" && widget.getAttribute("method"))
								resource_opts["method"] = widget.getAttribute("method");
							
							var resource = MyWidgetResourceLib.ResourceHandler.executeSetWidgetResourcesRequest(widget, resource_key, resource_opts);
							
							if (!resource) {
								MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No remove_record resource set in removeResourceItemFromItemFieldAction. Please inform the web-developer accordingly...");
								
								if (end_handler)
									MyWidgetResourceLib.fn.executeFunctions(end_handler, elm);
							}
						}
					}
					else
						MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No " + widget_selector + " element in removeResourceItemFromItemFieldAction method!");
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No element in removeResourceItemFromItemFieldAction method!");
		},
		
		updateItemPKsAttributesWithNewValues: function(item, values) {
			//update data-widget-pks-attrs based in new values. This is very usefull on forms when the user changes some editable PK, where in this case, we need to update the data-widget-pks-attrs, so the next time we execute another save or remove action, the pks are updated with the new user defined values.
			if (item && item.hasAttribute("data-widget-pks-attrs")) {
				var search_attrs = item.getAttribute("data-widget-pks-attrs");
				search_attrs = search_attrs ? MyWidgetResourceLib.fn.parseJson(search_attrs) : null;
				
				this.updateSearchAttrsWithNewValues(search_attrs, values);
				
				item.setAttribute("data-widget-pks-attrs", JSON.stringify(search_attrs));
			}
		},
		
		updateSearchAttrsWithNewValues: function(search_attrs, values) {
			if (search_attrs && MyWidgetResourceLib.fn.isPlainObject(search_attrs) && !MyWidgetResourceLib.fn.isEmptyObject(search_attrs) && MyWidgetResourceLib.fn.isPlainObject(values) && !MyWidgetResourceLib.fn.isEmptyObject(values))
				for (var attr_name in values)
					if (search_attrs.hasOwnProperty(attr_name) && search_attrs[attr_name] != values[attr_name])
						search_attrs[attr_name] = values[attr_name];
		},
		
		//Handler to be called on parse of a load action. In summary this handler parses the result of a resource and filter it accordingly with a selector.
		filterResourceHtml: function(elm, resource_result) {
			if (typeof resource_result == "string") {
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var selector = properties.hasOwnProperty("filter_resource_html_selector") ? properties["filter_resource_html_selector"] : null;
				
				if (selector) {
					try {
						var div = document.createElement("DIV");
						div.innerHTML = resource_result;
						
						var items = div.querySelectorAll(selector);
						var html = "";
						
						if (items)
							for (var i = 0, t = items.length; i < t; i++)
								html += items[i].outerHTML;
						
						resource_result = html;
					}
					catch(e) {
						if (console && console.log) {
							console.log("Error parseing resource result in MyWidgetResourceLib.js.FieldHandler.filterResourceHtml method.");
							console.log(e);
						}
					}
				}
			}
			
			return resource_result;
		},
		
		getWidgetResourceValueObj: function(elm) {
			if (elm.hasAttribute("data-widget-resource-value")) {
				var widget_resource_value = MyWidgetResourceLib.fn.getNodeElementData(elm, "widget_resource_value");
				
				if (widget_resource_value)
					return widget_resource_value;
				
				widget_resource_value = elm.getAttribute("data-widget-resource-value");
				
				if (widget_resource_value && ("" + widget_resource_value).substr(0, 1) == "{")
					return MyWidgetResourceLib.fn.parseJson(widget_resource_value);
			}
			
			return null;
		},
		
		/*
		 * data-widget-resource-value: {
		 * 	available_values: {
		 * 		key: value,
		 * 		key: value,
		 * 		0: {
		 * 			name: resource name
		 * 			...
		 * 		},
		 * 		key: value,
		 * 		1: [{
		 * 			name: resource name
		 * 			...
		 * 		},
		 * 		{
		 * 			name: resource name
		 * 			...
		 * 		}],
		 * 	}
		 * 	available_values: [{...}, {...}]
		 */
		getWidgetResourceValueAvailableValues: function(elm, opts) {
			var available_values = !opts || !opts["force"] ? MyWidgetResourceLib.fn.getNodeElementData(elm, "widget_resource_value_available_values") : null;
			
			if (!available_values) {
				var widget_resource_value = this.getWidgetResourceValueObj(elm);
				
				if (MyWidgetResourceLib.fn.isPlainObject(widget_resource_value) && widget_resource_value.hasOwnProperty("available_values")) {
					var items = widget_resource_value["available_values"];
					var available_values = {};
					
					if (!MyWidgetResourceLib.fn.isArray(items))
						items = [items];
					
					for (var i = 0, ti = items.length; i < ti; i++) {
						var item = items[i];
						
						if (MyWidgetResourceLib.fn.isPlainObject(item) && !MyWidgetResourceLib.fn.isEmptyObject(item)) {
							for (var k in item) {
								var v = item[k];
								
								if (MyWidgetResourceLib.fn.isPlainObject(v))
									v = [v];
								
								if (MyWidgetResourceLib.fn.isArray(v)) {
									for (var j = 0, tj = v.length; j < tj; j++)
										if (MyWidgetResourceLib.fn.isPlainObject(v[j])) {
											if (v[j]["name"] || MyWidgetResourceLib.fn.isNumeric(v[j]["name"])) {
												var resource_name = v[j]["name"];
												var resource_data = MyWidgetResourceLib.ResourceHandler.getLoadedSLAResult(resource_name);
												
												//it might be an array, bc if the return resource is an object with the same keys than an numeric key array, then the json_encode from php will convert it to an array, so we need to convert it back to an object.
												if (resource_data && MyWidgetResourceLib.fn.isArray(resource_data) && resource_data.length > 0)
													resource_data = Object.assign({}, resource_data);
												
												if (MyWidgetResourceLib.fn.isPlainObject(resource_data))
													for (var rk in resource_data)
														available_values[rk] = resource_data[rk];
											}
										}
								}
								else
									available_values[k] = v;
							}
						}
					}
				}
			}
			
			return available_values;
		},
		
		getWidgetResourceValueReplacementType: function(elm) {
			var widget_resource_value = this.getWidgetResourceValueObj(elm);
			
			if (MyWidgetResourceLib.fn.isPlainObject(widget_resource_value) && widget_resource_value.hasOwnProperty("type"))
				return widget_resource_value["type"];
			
			return null;
		},
		
		getWidgetResourceValueDisplayHandler: function(elm) {
			var widget_resource_value = this.getWidgetResourceValueObj(elm);
			
			if (MyWidgetResourceLib.fn.isPlainObject(widget_resource_value) && widget_resource_value.hasOwnProperty("display"))
				return MyWidgetResourceLib.fn.convertIntoFunctions(widget_resource_value["display"]);
			
			return null;
		},
		
		getWidgetResourceValueDisplayTargetType: function(elm) {
			var widget_resource_value = this.getWidgetResourceValueObj(elm);
			
			if (MyWidgetResourceLib.fn.isPlainObject(widget_resource_value) && widget_resource_value.hasOwnProperty("target_type"))
				return widget_resource_value["target_type"];
			
			return null;
		},
		
		getWidgetResourceValueDisplayTargetAttributeName: function(elm) {
			var widget_resource_value = this.getWidgetResourceValueObj(elm);
			
			if (MyWidgetResourceLib.fn.isPlainObject(widget_resource_value) && widget_resource_value.hasOwnProperty("target_attribute"))
				return widget_resource_value["target_attribute"];
			
			return null;
		},
		
		getWidgetResourceValueDisplayCallback: function(elm) {
			var widget_resource_value = this.getWidgetResourceValueObj(elm);
			
			if (MyWidgetResourceLib.fn.isPlainObject(widget_resource_value) && widget_resource_value.hasOwnProperty("complete") && MyWidgetResourceLib.fn.isPlainObject(widget_resource_value["complete"]) && widget_resource_value["complete"].hasOwnProperty("display"))
				return MyWidgetResourceLib.fn.convertIntoFunctions(widget_resource_value["complete"]["display"]);
			
			return null;
		},
		
		getWidgetResourceValueAttributeName: function(elm, opts) {
			//Do NOT add cache here, bc sometimes it could return the field attribute name and other times the name from data-widget-resource-value.
			
			if (elm) {
				var attribute_name = null;
				var force_name = opts && opts["force_input_name"];
				var node_name = elm.nodeName.toUpperCase();
				
				if (elm.hasAttribute("data-widget-resource-value")) {
					var widget_resource_value = this.getWidgetResourceValueObj(elm);
					
					if (MyWidgetResourceLib.fn.isPlainObject(widget_resource_value)) {
						if (widget_resource_value.hasOwnProperty("attribute"))
							attribute_name = widget_resource_value["attribute"];
						
						if (widget_resource_value["ignore_field_name"] && force_name) //disable force_name if is ignore_field_name
							force_name = false;
					}
					else
						attribute_name = elm.getAttribute("data-widget-resource-value");
				}
				
				if (!attribute_name && !MyWidgetResourceLib.fn.isNumeric(attribute_name) && elm.hasAttribute("data-widget-props")) {
					var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
					
					if (properties.hasOwnProperty("attribute")) 
						attribute_name = properties["attribute"];
				}
				
				//get the name of input as last one, because the name of input doesn't need to be related with the resource attribute that is being shown.
				if (elm.getAttribute("name") && (!attribute_name || force_name)/* && (node_name == "INPUT" || node_name == "TEXTAREA" || node_name == "SELECT")*/)
					attribute_name = elm.getAttribute("name");
				//console.log("attribute_name:"+attribute_name);
				
				return attribute_name;
			}
			
			return null;
		},
		
		getWidgetResourceValueValue: function(elm, default_resource_name, default_resource_cache_key, default_resource_index) {
			if (elm.hasAttribute("data-widget-resource-value")) {
				var item_resource_name = default_resource_name;
				var item_resource_cache_key = default_resource_cache_key;
				var item_resource_index = default_resource_index;
				var widget_resource_value = this.getWidgetResourceValueObj(elm);
				
				if (MyWidgetResourceLib.fn.isPlainObject(widget_resource_value)) {
					if (widget_resource_value["resource_name"] && item_resource_name != widget_resource_value["resource_name"]) {
						item_resource_name = widget_resource_value["resource_name"];
						item_resource_cache_key = null;
					}
					
					if (MyWidgetResourceLib.fn.isNumeric(widget_resource_value["index"]))
						item_resource_index = widget_resource_value["index"];
				}
				
				var selector = [item_resource_name];
				
				if (MyWidgetResourceLib.fn.isNumeric(item_resource_index))
					selector.push(item_resource_index);
				
				if (selector) {
					var item_resource_attribute = this.getWidgetResourceValueAttributeName(elm);
					var is_resource_object = elm.getAttribute("data-widget-resource-value") == "";
					
					if (!is_resource_object && (item_resource_attribute || MyWidgetResourceLib.fn.isNumeric(item_resource_attribute)))
						selector.push(item_resource_attribute);
					
					var value = MyWidgetResourceLib.ResourceHandler.getLoadedSLAResult(selector, item_resource_cache_key);
					
					//check if attribute name is equal to 'idx' and if doesn't exist any key in the loaded resource with the name 'idx', then return the default_resource_index
					if ((value === null || value === undefined) && typeof item_resource_attribute == "string" && item_resource_attribute.toLowerCase() == "idx" && !MyWidgetResourceLib.ResourceHandler.existLoadedSLAResult(selector))
						value = default_resource_index;
					
					return value;
				}
			}
			
			return null;
		},
		
		getWidgetResourceValueDefaultValue: function(elm) {
			var widget_resource_value = this.getWidgetResourceValueObj(elm);
			
			if (MyWidgetResourceLib.fn.isPlainObject(widget_resource_value) && widget_resource_value.hasOwnProperty("default"))
				return widget_resource_value["default"];
			
			return null;
		},
		
		//Handler to be called on complete of a load action. In summary this handler checks if there is a default value set for the current field and, if it exists, sets that default value on the field. This method is used on comboboxes with default values from the URL that are not being set propertly...
		setWidgetResourceValueDefaultValue: function(elm) {
			if (elm) {
				var default_value = MyWidgetResourceLib.FieldHandler.getWidgetResourceValueDefaultValue(elm);
				default_value = default_value != null || default_value != undefined ? "" : default_value;
				
				MyWidgetResourceLib.FieldHandler.setFieldValue(elm, default_value, {
					with_available_values: true,
					with_default: true,
				});
			}
		},
		
		getWidgetResourceValueAllowNewOptions: function(elm) {
			var widget_resource_value = this.getWidgetResourceValueObj(elm);
			
			return MyWidgetResourceLib.fn.isPlainObject(widget_resource_value) && widget_resource_value.hasOwnProperty("allow_new_options") && widget_resource_value["allow_new_options"];
		},
		
		getFieldsValues: function(elm, opts) {
			var inputs = elm.querySelectorAll("input, select, textarea");
			var data = {};
			
			if (inputs) {
				var inputs_opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? MyWidgetResourceLib.fn.assignObjectRecursively({}, opts) : {};
				inputs_opts["force_input_name"] = true;
				var ignore_checked_inputs_by_name = [];
				
				for (var i = 0, ti = inputs.length; i < ti; i++) {
					var input = inputs[i];
					var name = this.getWidgetResourceValueAttributeName(input, inputs_opts);
					
					if (name) {
						//if checkbox and radio button with the same name than an input previously parsed, only show the checked values, otherwise ignore the input bc it was already parsed by a previous iteration of this loop.
						var is_checked_input = node_name == "INPUT" && (input.type == "checkbox" || input.type == "radio");
						var ignore = is_checked_input && ignore_checked_inputs_by_name.indexOf(name) != -1;
						
						if (ignore)
							continue;
						
						//get input value
						var node_name = input.nodeName.toUpperCase();
						var value = this.isEmptyCheckbox(input) ? "" : this.getFieldValue(input, inputs_opts);
						
						//if checkbox and radio button with same names, change value variable with correspondent values from checked inputs, otherwise are saing the last value from the last input independent if it is checked or not, ignoring all the other checked inputs with the same names, which is wrong!
						if (is_checked_input) {
							var filtered_inputs = Array.from(inputs).filter(function(inp) { 
								var inp_name = MyWidgetResourceLib.FieldHandler.getWidgetResourceValueAttributeName(inp, inputs_opts);
								return inp != input && inp.nodeName == input.nodeName && inp_name == name && (inp.type == "checkbox" || inp.type == "radio");
							});
							
							if (filtered_inputs.length > 0) {
								ignore_checked_inputs_by_name.push(name);
								
								var values = [];
								
								if (input.checked)
									values.push(value);
								
								for (var j = 0, tj = filtered_inputs.length; j < tj; j++) {
									var inp = filtered_inputs[j];
									
									if (inp.checked)
										values.push( this.getFieldValue(inp, inputs_opts) );
								}
								
								if (values.length == 1)
									value = values[0];
								else if (values.length > 1)
									value = values;
							}
						}
						
						//console.log(name+":");
						//console.log(value);
						
						//if name contains "[]" at the end, set the value has an array. This is for checkbox and radio buttons, but also for select, textarea and other input fields.
						if (name.substr(name.length - 2) == "[]") {
							name = name.substr(0, name.length - 2);
							
							if (data.hasOwnProperty(name))
								data[name] = [ data[name] ];
							else
								data[name] = [];
							
							if (MyWidgetResourceLib.fn.isArray(value))
								data[name] = data[name].concat(value);
							else
								data[name].push(value);
						}
						else //set normal value
							data[name] = value;
					}
				}
			}
			
			return data;
		},
		
		/*
		 * In getFieldValue the default value happens first then available_values. In the setFieldValue is the opposite, this is: 
		 * 0- value is the user value
		 * 1- get the input value
		 * 2- if empty, set the default value
		 * 3- if available_values exists, search the available_values but as a flipped array, based in the available_values values and not the keys as it happens in the setFieldValue. 
		 * Note that this logic must be the inverse logic of the setFieldValue method.
		 */
		getFieldValue: function(elm, opts) {
			var value = null;
			
			if (elm) {
				var node_name = elm.nodeName.toUpperCase();
				var target_type = this.getWidgetResourceValueDisplayTargetType(elm);
				var target_attribute = this.getWidgetResourceValueDisplayTargetAttributeName(elm);
				
				if (target_type == "attribute")
					value = target_attribute || MyWidgetResourceLib.fn.isNumeric(target_attribute) ? elm.getAttribute(target_attribute) : undefined;
				else if (node_name == "INPUT" || node_name == "TEXTAREA" || node_name == "SELECT")
					value = this.getInputValue(elm);
				else
					value = elm.innerHTML;
				
				if (opts && opts["with_default"] && !value && !MyWidgetResourceLib.fn.isNumeric(value)) {
					var default_value = this.getWidgetResourceValueDefaultValue(elm);
					
					if (default_value !== null && default_value.length)
						value = default_value;
				}
				
				if (opts && opts["with_available_values"]) {
					var available_values = this.getWidgetResourceValueAvailableValues(elm, opts);
					
					//update value with available_values
					if (MyWidgetResourceLib.fn.isPlainObject(available_values))
						for (var k in available_values)
							if (value == available_values[k]) {
								value = k;
								break;
							}
				}
			}
			
			return value;
		},
		
		/*
		 * In setFieldValue the available_values happens first then default value. In the getFieldValue is the opposite, this is: 
		 * 0- value is the server value
		 * 1- if available_values exists, get the correspondent value to show, based in the available_values keys.
		 * 2- if empty, set the default value
		 * 3- set value in input
		 * Note that this logic must be the inverse logic of the getFieldValue method.
		 */
		setFieldValue: function(elm, value, opts) {
			if (elm) {
				try {
					if (opts && opts["with_available_values"]) {
						var available_values = this.getWidgetResourceValueAvailableValues(elm, opts);
						
						//update value with available_values
						if (MyWidgetResourceLib.fn.isPlainObject(available_values) && available_values.hasOwnProperty(value))
							value = available_values[value];
					}
					
					if (opts && opts["with_default"] && !value && !MyWidgetResourceLib.fn.isNumeric(value)) {
						var default_value = this.getWidgetResourceValueDefaultValue(elm);
						
						if (default_value !== null && default_value.length)
							value = default_value;
					}
					
					var node_name = elm.nodeName.toUpperCase();
					var replacement_type = this.getWidgetResourceValueReplacementType(elm);
					var target_type = this.getWidgetResourceValueDisplayTargetType(elm);
					var target_attribute = this.getWidgetResourceValueDisplayTargetAttributeName(elm);
					var display_handler = this.getWidgetResourceValueDisplayHandler(elm);
					display_handler = MyWidgetResourceLib.fn.convertIntoFunctions(display_handler);
					
					if (display_handler)
						MyWidgetResourceLib.fn.executeFunctions(display_handler, elm, value, replacement_type, target_type, target_attribute);
					else if (target_type == "attribute") {
						if (target_attribute || MyWidgetResourceLib.fn.isNumeric(target_attribute)) {
							if (elm.hasAttribute(target_attribute)) {
								if (replacement_type == "append")
									value += "" + elm.getAttribute(target_attribute); //"" so it can append as a string and not numeric
								else if (replacement_type == "prepend")
									value = elm.getAttribute(target_attribute) + "" + value; //"" so it can append as a string and not numeric
							}
							
							elm.setAttribute(target_attribute, value);
						}
						else
							elm.setAttribute(value, "");
					}
					else if (node_name == "INPUT" || node_name == "TEXTAREA" || node_name == "SELECT") {
						if (elm.type == "checkbox" || elm.type == "radio")
							MyWidgetResourceLib.FieldHandler.setInputValue(elm, value);
						else if (node_name == "SELECT") {
							MyWidgetResourceLib.FieldHandler.setInputValue(elm, value);
							
							var allow_new_options = this.getWidgetResourceValueAllowNewOptions(elm);
							
							if (allow_new_options) {
								//select multiple values if value is an array
								if (MyWidgetResourceLib.fn.isArray(value)) {
									var options = elm.querySelectorAll("option");
									
									MyWidgetResourceLib.fn.each(value, function(idx, v) {
										var exists = false;
										
										MyWidgetResourceLib.fn.each(options, function(idy, option) {
											if (option.value == v) {
												exists = true;
												return false;
											}
										});
										
										if (!exists && (v || MyWidgetResourceLib.fn.isNumeric(v))) {
											var option_html = '<option value="' + ("" + v).replace(/"/g, "&quot;") + '" title="this is a hard-coded value" selected>-- ' + v + ' --</option>';
											
											if (replacement_type == "prepend")
												elm.insertAdjacentHTML('afterbegin', option_html);
											else
												elm.insertAdjacentHTML('beforeend', option_html);
										}
									});
								}
								else if (elm.value != value && (value || MyWidgetResourceLib.fn.isNumeric(value))) { //if no value in select field, add it if allowed
									var option_html = '<option value="' + ("" + value).replace(/"/g, "&quot;") + '" title="this is a hard-coded value" selected>-- ' + value + ' --</option>';
									
									if (replacement_type == "prepend")
										elm.insertAdjacentHTML('afterbegin', option_html);
									else
										elm.insertAdjacentHTML('beforeend', option_html);
								}
							}
						}
						else {
							var prev_value = MyWidgetResourceLib.FieldHandler.getInputValue(elm);
							prev_value = prev_value != undefined && prev_value !== null ? prev_value : "";
							
							if (replacement_type == "append")
								value = prev_value + value;
							else if (replacement_type == "prepend")
								value = value + prev_value;
							
							MyWidgetResourceLib.FieldHandler.setInputValue(elm, value);
						}
					}
					else {
						if (replacement_type == "append")
							elm.insertAdjacentHTML('beforeend', value); //last child
						else if (replacement_type == "prepend")
							elm.insertAdjacentHTML('afterbegin', value); //first child
						else
							elm.innerHTML = value;
					}
					
					//if display callback exists, execute it
					var display_callback = this.getWidgetResourceValueDisplayCallback(elm);
					
					if (display_callback)
						MyWidgetResourceLib.fn.executeFunctions(display_callback, elm);
				}
				catch(e) {
					if (console && console.log)
						console.log(e);
				}
			}
		},
		
		//Handler to be called on complete of a load action. In summary this handler selects the first option of a combobox. This method is used on comboboxes that don't have any option selected by default.
		setSelectFieldFirstValue: function(elm) {
			//if select and if there is no selected option, it means that the input.value will be null. So we want to select the first element.
			if (elm && elm.nodeName == "SELECT" && elm.options.length > 0 && !elm.options[elm.selectedIndex])
				elm.options[0].selected = true;
		},
		
		setInputValue: function(elm, value) {
			if (elm) {
				var node_name = elm.nodeName;
				
				if (node_name == "INPUT" || node_name == "TEXTAREA" || node_name == "SELECT") {
					if (elm.type == "checkbox" || elm.type == "radio") {
						if ((elm.hasAttribute("value") && elm.getAttribute("value") == value) || (!elm.hasAttribute("value") && value))
							elm.checked = true;
						else
							elm.checked = false;
					}
					else if (node_name == "SELECT") {
						//select multiple values if value is an array
						if (MyWidgetResourceLib.fn.isArray(value)) {
							elm.value = null; //de-select previous values
							
							var options = elm.querySelectorAll("option");
							
							MyWidgetResourceLib.fn.each(value, function(idx, v) {
								MyWidgetResourceLib.fn.each(options, function(idy, option) {
									if (option.value == v)
										option.selected = true; //do not break here, bc there could be multiple options with the same value.
								});
							});
						}
						else
							elm.value = value;
					}
					else
						elm.value = value;
				}
			}
		},
		
		getInputValue: function(elm) {
			if (elm) {
				var node_name = elm.nodeName;
				
				if (node_name == "INPUT" || node_name == "TEXTAREA" || node_name == "SELECT") {
					if (elm.type == "checkbox" || elm.type == "radio") {
						if (elm.checked)
							return elm.hasAttribute("value") ? elm.value : true;
						else
							return false;
					}
					else if (node_name == "SELECT" && elm.multiple) {
						var values = [];
						var options = elm.querySelectorAll("option");
						
						MyWidgetResourceLib.fn.each(options, function(idx, option) {
							if (option.selected)
								values.push(option.value);
						});
						
						//only returns array if multiple options selected, otherwise return null. Even if we have only one option selected, it should return that option inside of an array, just like it happens with the FormData javascript class.
						if (values.length == 0)
							return null;
						else
							return values; 
					}
					else {
						return elm.value;
					}
				}
			}
			
			return null;
		},
		
		getFileInputsData: function(item) {
			var data = {};
			
			//DEPRECATED: item may not be a form bc maybe is a TR inside of an editable table
			//if (item && item.nodeName.toLowerCase() == "form" && item.hasAttribute("enctype") && item.getAttribute("enctype").toLowerCase().indexOf("multipart/form-data") != -1) {
				var file_inputs = item.querySelectorAll("input[type=file]");
				
				if (file_inputs) {
					for (var i = 0; i < file_inputs.length; i++) {
						var file_input = file_inputs[i];
						var file_input_name = file_input.name;
						
						if (file_input_name)
							data[file_input_name] = file_input.files;
						
						//console.log(file_input_name);
						//console.log(file_input.files);
					}
				}
			//}
			
			return data;
		},
		
		isEmptyCheckbox: function(input) {
			return input && input.nodeName.toUpperCase() == "INPUT" && (input.type == "checkbox" || input.type == "radio") && !input.checked;
		}
	});
	/****************************************************************************************
	 *				 END: FIELD HANDLER 					*
	 ****************************************************************************************/
		
	/****************************************************************************************
	 *				 START: SHORT ACTION HANDLER 					*
	 ****************************************************************************************/
	MyWidgetResourceLib.ShortActionHandler = MyWidgetResourceLib.fn.ShortActionHandler = ({
		
		/* REFRESH FUNCTIONS - GENERIC */
		
		//Reload data from the dependent widgets.
		refreshDependentWidgets: function(elm) {
			MyWidgetResourceLib.fn.reloadWidgetDependentWidgets(elm);
		},
		
		//Reload data from the dependent widgets, that were not loaded yet.
		refreshNotYetLoadedDependentWidgets: function(elm) {
			MyWidgetResourceLib.fn.reloadWidgetDependentWidgetsWithoutResourcesToLoad(elm);
		},
		
		refreshDependentWidgetsBasedInNameAndValue: function(elm, name, value, complete_func, force, ignore_widgets_with_resources_to_load) {
			var elm_properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
			var search_types = elm_properties["search_type"] ? elm_properties["search_type"] : (elm.nodeName == "SELECT" ? "equal" : "contains");
			var search_cases = elm_properties.hasOwnProperty("search_case") ? elm_properties["search_case"] : null;
			var search_operators = elm_properties.hasOwnProperty("search_operator") ? elm_properties["search_operator"] : null;
			var search_attrs = {};
			
			if (name || MyWidgetResourceLib.fn.isNumeric(name)) //if name is null, it will get all items from dependent widgets
				search_attrs[name] = value;
			
			var search_props = {
				search_attrs: search_attrs,
				search_types: search_types,
				search_cases: search_cases,
				search_operators: search_operators,
			};
			
			//execute search based in the search_attrs var
			/*
			 * search_attrs: {attribute_name: string to search}
			 * search_types: contains|starts_with|ends_with or {attribute_name: contains|starts_with|ends_with} (default: contains)
			 * search_cases: sensitive|insensitive (default: sensitive)
			 * search_operators: or|and (default: and)
			 */
			var dependent_widgets_id = MyWidgetResourceLib.fn.prepareDependentWidgetsId(elm_properties["dependent_widgets_id"]);
			
			MyWidgetResourceLib.fn.loadDependentWidgetsById(dependent_widgets_id, search_props, {
				force: force,
				complete: complete_func,
				ignore_widgets_with_resources_to_load: ignore_widgets_with_resources_to_load
			});
		},
		
		/* REFRESH FUNCTIONS - INPUT VALUE */
		
		//used when dependent widgets are lists
		//Reload data from the dependent widgets based on a non-empty input field value.
		refreshDependentWidgetsBasedInInputNonEmptyValue: function(elm) {
			var name = elm.name;
			var value = MyWidgetResourceLib.FieldHandler.getInputValue(elm);
			
			if (value != undefined && (value || MyWidgetResourceLib.fn.isNumeric(value)) && (name || MyWidgetResourceLib.fn.isNumeric(name)))
				MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInNameAndValue(elm, name, value, null, true, false);
		},
		//Reload data from the dependent widgets, that were not loaded yet, based on a non-empty input field value.
		refreshNotYetLoadedDependentWidgetsBasedInInputNonEmptyValue: function(elm) {
			var name = elm.name;
			var value = MyWidgetResourceLib.FieldHandler.getInputValue(elm);
			
			if (value != undefined && (value || MyWidgetResourceLib.fn.isNumeric(value)) && (name || MyWidgetResourceLib.fn.isNumeric(name)))
				MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInNameAndValue(elm, name, value, null, true, true);
		},
		
		//Reload data from the dependent widgets based on an input field value.
		refreshDependentWidgetsBasedInInputValue: function(elm) {
			var name = elm.name;
			var value = MyWidgetResourceLib.FieldHandler.getInputValue(elm);
			
			MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInNameAndValue(elm, name, value, null, true, false);
		},
		//Reload data from the dependent widgets, that were not loaded yet, based on an input field value.
		refreshNotYetLoadedDependentWidgetsBasedInInputValue: function(elm) {
			var name = elm.name;
			var value = MyWidgetResourceLib.FieldHandler.getInputValue(elm);
			
			MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInNameAndValue(elm, name, value, null, true, true);
		},
		
		//Reload data from the dependent widgets based on an input field value. If that value is empty, reload dependent widgets without any filter, this is, get all items from dependent widgets.
		refreshDependentWidgetsBasedInInputValueButIfEmptyGetAll: function(elm) {
			var name = elm.name;
			var value = MyWidgetResourceLib.FieldHandler.getInputValue(elm);
			
			if (value != undefined && (value || MyWidgetResourceLib.fn.isNumeric(value)) && (name || MyWidgetResourceLib.fn.isNumeric(name)))
				MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInNameAndValue(elm, name, value, null, true, false);
			else
				MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInNameAndValue(elm, null, null, null, true, false);
		},
		//Reload data from the dependent widgets, that were not loaded yet, based on an input field value. If that value is empty, reload dependent widgets without any filter, this is, get all items from dependent widgets.
		refreshNotYetLoadedDependentWidgetsBasedInInputValueButIfEmptyGetAll: function(elm) {
			var name = elm.name;
			var value = MyWidgetResourceLib.FieldHandler.getInputValue(elm);
			
			if (value != undefined && (value || MyWidgetResourceLib.fn.isNumeric(value)) && (name || MyWidgetResourceLib.fn.isNumeric(name)))
				MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInNameAndValue(elm, name, value, null, true, true);
			else
				MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInNameAndValue(elm, null, null, null, true, true);
		},
		
		/* REFRESH FUNCTIONS - RESOURCE VALUE */
		
		//used when dependent widgets are lists
		//Reload data from the dependent widgets based on a non-empty resource-attribute name and value of a html element. The resource-attribute value is based in the available_values and if empty get the default value.
		refreshDependentWidgetsBasedInResourceNonEmptyValue: function(elm) {
			var name = MyWidgetResourceLib.FieldHandler.getWidgetResourceValueAttributeName(elm, {force_input_name: true});
			var value = MyWidgetResourceLib.FieldHandler.getFieldValue(elm, {
				with_available_values: true,
				with_default: true,
			});
			
			if (value != undefined && (value || MyWidgetResourceLib.fn.isNumeric(value)) && (name || MyWidgetResourceLib.fn.isNumeric(name)))
				MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInNameAndValue(elm, name, value, null, true, false);
		},
		//Reload data from the dependent widgets, that were not loaded yet, based on a non-empty resource-attribute name and value of a html element. The resource-attribute value is based in the available_values and if empty get the default value.
		refreshNotYetLoadedDependentWidgetsBasedInResourceNonEmptyValue: function(elm) {
			var name = MyWidgetResourceLib.FieldHandler.getWidgetResourceValueAttributeName(elm, {force_input_name: true});
			var value = MyWidgetResourceLib.FieldHandler.getFieldValue(elm, {
				with_available_values: true,
				with_default: true,
			});
			
			if (value != undefined && (value || MyWidgetResourceLib.fn.isNumeric(value)) && (name || MyWidgetResourceLib.fn.isNumeric(name)))
				MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInNameAndValue(elm, name, value, null, true, true);
		},
		
		//Reload data from the dependent widgets based on a resource-attribute name and value of a html element. The resource-attribute value is based in the available_values and if empty get the default value.
		refreshDependentWidgetsBasedInResourceValue: function(elm) {
			var name = MyWidgetResourceLib.FieldHandler.getWidgetResourceValueAttributeName(elm, {force_input_name: true});
			var value = MyWidgetResourceLib.FieldHandler.getFieldValue(elm, {
				with_available_values: true,
				with_default: true,
			});
			
			MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInNameAndValue(elm, name, value, null, true, false);
		},
		//Reload data from the dependent widgets, that were not loaded yet, based on a resource-attribute name and value of a html element. The resource-attribute value is based in the available_values and if empty get the default value.
		refreshNotYetLoadedDependentWidgetsBasedInResourceValue: function(elm) {
			var name = MyWidgetResourceLib.FieldHandler.getWidgetResourceValueAttributeName(elm, {force_input_name: true});
			var value = MyWidgetResourceLib.FieldHandler.getFieldValue(elm, {
				with_available_values: true,
				with_default: true,
			});
			
			MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInNameAndValue(elm, name, value, null, true, true);
		},
		
		//Reload data from the dependent widgets based on a resource-attribute name and value of a html element. If that value is empty, reload dependent widgets without any filter, this is, get all items from dependent widgets. The resource-attribute value is based in the available_values and if empty get the default value.
		refreshDependentWidgetsBasedInResourceValueButIfEmptyGetAll: function(elm) {
			var name = MyWidgetResourceLib.FieldHandler.getWidgetResourceValueAttributeName(elm, {force_input_name: true});
			var value = MyWidgetResourceLib.FieldHandler.getFieldValue(elm, {
				with_available_values: true,
				with_default: true,
			});
			
			if (value != undefined && (value || MyWidgetResourceLib.fn.isNumeric(value)) && (name || MyWidgetResourceLib.fn.isNumeric(name)))
				MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInNameAndValue(elm, name, value, null, true, false);
			else
				MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInNameAndValue(elm, null, null, null, true, false);
		},
		//Reload data from the dependent widgets, that were not loaded yet, based on a resource-attribute name and value of a html element. If that value is empty, reload dependent widgets without any filter, this is, get all items from dependent widgets. The resource-attribute value is based in the available_values and if empty get the default value.
		refreshNotYetLoadedDependentWidgetsBasedInResourceValueButIfEmptyGetAll: function(elm) {
			var name = MyWidgetResourceLib.FieldHandler.getWidgetResourceValueAttributeName(elm, {force_input_name: true});
			var value = MyWidgetResourceLib.FieldHandler.getFieldValue(elm, {
				with_available_values: true,
				with_default: true,
			});
			
			if (value != undefined && (value || MyWidgetResourceLib.fn.isNumeric(value)) && (name || MyWidgetResourceLib.fn.isNumeric(name)))
				MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInNameAndValue(elm, name, value, null, true, true);
			else
				MyWidgetResourceLib.ShortActionHandler.refreshDependentWidgetsBasedInNameAndValue(elm, null, null, null, true, true);
		},
		
		/* OTHER FUNCTIONS */
		
		//Add an inline item inside of the dependent widgets.
		addInlineResourceListItemToDependentWidgets: function(elm) {
			if (elm) {
				var perms = MyWidgetResourceLib.PermissionHandler.getWidgetPermissions(elm);
				
				if (!perms["remove"]) {
					var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
					var dependent_widgets_id = properties["dependent_widgets_id"];
					
					if (dependent_widgets_id) {
						dependent_widgets_id = MyWidgetResourceLib.fn.prepareDependentWidgetsId(dependent_widgets_id);
						
						//TODO: find a way to loop asyncronously
						MyWidgetResourceLib.fn.each(dependent_widgets_id, function(idx, dependent_widget_id) {
							var widgets = MyWidgetResourceLib.fn.getDependentWidgetsById(dependent_widget_id);
							
							if (widgets)
								MyWidgetResourceLib.fn.each(widgets, function(idy, widget) {
									if (widget) {
										if (widget.hasAttribute("data-widget-list-table") || widget.hasAttribute("data-widget-list-tree")) 
											widget = widget.closest("data-widget-list");
										
										if (widget.hasAttribute("data-widget-list"))
											MyWidgetResourceLib.ListHandler.addInlineResourceListItem(widget);
										else
											MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: Widget with id '" + dependent_widget_id + "' is not a List-Widget in addInlineResourceListItemToDependentWidgets method!");
									}
								});
						});
					}
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No element in addInlineResourceListItemToDependentWidgets method!");
		},
		
		//Execute add action, for the new records that are selected from the dependent widgets.
		//This method receives a second argument with an 'opts' object correspondent to some options that will be passed on execution of the correspondent resource.
		executeResourceMultipleAddAction: function(elm, opts) {
			this.executeResourceMultipleAction(elm, "add", opts); //adds multiple records
		},
		
		//Execute update action, for only the existent records that are selected from the dependent widgets.
		//This method receives a second argument with an 'opts' object correspondent to some options that will be passed on execution of the correspondent resource.
		executeResourceMultipleUpdateAction: function(elm, opts) {
			this.executeResourceMultipleAction(elm, "update", opts); //updates multiple records
		},
		
		//Execute save (update and add) action, for the existent and new records that are selected from the dependent widgets.
		//This method receives a second argument with an 'opts' object correspondent to some options that will be passed on execution of the correspondent resource.
		executeResourceMultipleSaveAction: function(elm, opts) {
			this.executeResourceMultipleAction(elm, "save", opts); //updates and adds multiple records
		},
		
		//Execute removal action for all the selected items from the dependent widgets.
		//This method receives a second argument with an 'opts' object correspondent to some options that will be passed on execution of the correspondent resource.
		executeResourceMultipleRemoveAction: function(elm, opts) {
			this.executeResourceMultipleAction(elm, "remove", opts); //removes multiple records
		},
		
		//opts could be {purge_cache: true} or {force: true}
		//Execute a specific action, based in its name, for all the selected items from the dependent widgets.
		//This method receives a third argument with an 'opts' object correspondent to some options that will be passed on execution of the correspondent resource.
		executeResourceMultipleAction: function(elm, resource_key, opts) {
			if (elm) {
				var perms = MyWidgetResourceLib.PermissionHandler.getWidgetPermissions(elm);
				
				if (!perms["remove"] && MyWidgetResourceLib.FieldHandler.isResourceFieldActionConfirmed(elm)) {
					var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
					var dependent_widgets_id = properties["dependent_widgets_id"];
					var selected_conds = [];
					var selected_attrs = [];
					var selected_files = {};
					
					if (dependent_widgets_id) {
						dependent_widgets_id = MyWidgetResourceLib.fn.prepareDependentWidgetsId(dependent_widgets_id);
						
						MyWidgetResourceLib.fn.each(dependent_widgets_id, function(idx, dependent_widget_id) {
							var widgets = MyWidgetResourceLib.fn.getDependentWidgetsById(dependent_widget_id);
							
							if (widgets)
								MyWidgetResourceLib.fn.each(widgets, function(idy, widget) {
									if (widget) {
										var inputs = widget.querySelectorAll("input[type=checkbox][data-widget-item-selected-checkbox]:checked");
										
										if (inputs)
											for (var i = 0, ti = inputs.length; i < ti; i++) {
												var p = inputs[i].closest("[data-widget-pks-attrs]"); //it could be the input it self with data-widget-pks-attrs or some parent like a "tr/li" element
												var sa = p ? p.getAttribute("data-widget-pks-attrs") : null;
												sa = sa ? MyWidgetResourceLib.fn.parseJson(sa) : null;
												
												if (sa) {
													selected_conds.push(sa);
													
													if (resource_key != "remove" && MyWidgetResourceLib.FieldHandler.checkResourceItemFields(p, widget)) {
														var fields_data = MyWidgetResourceLib.FieldHandler.getFieldsValues(p, {
															with_available_values: true,
															with_default: true,
														});
														var files_data = MyWidgetResourceLib.FieldHandler.getFileInputsData(p);
														
														selected_attrs.push(fields_data);
														selected_files = Object.assign(selected_files, files_data);
													}
													
													//disable update_attribute event in boxes with onBlur attribute
													var boxes = p.querySelectorAll("input[onBlur], select[onBlur], textarea[onBlur]");
													
													if (boxes)
														for (var j = 0, tj = boxes.length; j < tj; j++) {
															var box = boxes[j];
															var update_resource_list_attribute_timeout_id = MyWidgetResourceLib.fn.getNodeElementData(box, "update_resource_list_attribute_timeout_id");
															
															if (update_resource_list_attribute_timeout_id)
																clearTimeout(update_resource_list_attribute_timeout_id);
														}
												}
											}
									}
								});
						});
					}
					
					if (selected_conds.length > 0 ) {
						var end_handler = MyWidgetResourceLib.fn.convertIntoFunctions(properties["end"]);
						
						var complete_func = function(resource_elm, resources, resources_name, resources_cache_key) {
							var complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(properties["complete"]);
							
							if (complete_handler)
								MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
							
							MyWidgetResourceLib.fn.loadDependentWidgetsById(properties["dependent_widgets_id"], {}, {
								purge_cache: true
							});
							
							if (end_handler)
								MyWidgetResourceLib.fn.executeFunctions(end_handler, elm);
						};
						
						var post_data = {
							conditions: selected_conds,
							attributes: selected_attrs
						};
						
						var resource_opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? MyWidgetResourceLib.fn.assignObjectRecursively({}, opts) : {};
						//resource_opts["ignore_empty_resource"] = true; //Do not add bc the data-widget-button-multiple-remove and data-widget-button-multiple-save will have the data-widget-resources attribute wihtout any resource key 
						resource_opts["post_data"] = post_data;
						resource_opts["files_data"] = selected_files;
						resource_opts["label"] = "multiple action";
						resource_opts["complete"] = complete_func;
						
						var resource = MyWidgetResourceLib.ResourceHandler.executeSetWidgetResourcesRequest(elm, resource_key, resource_opts);
						
						if (!resource) {
							MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No '" + resource_key + "' resource set in executeResourceMultipleAction. Please inform the web-developer accordingly...");
							
							if (end_handler)
								MyWidgetResourceLib.fn.executeFunctions(end_handler, elm);
						}
					}
					else if (properties["empty_message"])
						MyWidgetResourceLib.MessageHandler.showInfoMessage(properties["empty_message"]);
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No element in executeResourceMultipleAction method!");
		},
		
		//Execute a specific action, based in its name, from the dependent widgets.
		//This method receives a third argument with an 'opts' object correspondent to some options that will be passed on execution of the correspondent resource.
		executeResourceSingleAction: function(elm, resource_key, opts) {
			if (elm) {
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var dependent_widgets_id = properties["dependent_widgets_id"];
				
				if (dependent_widgets_id) {
					dependent_widgets_id = MyWidgetResourceLib.fn.prepareDependentWidgetsId(dependent_widgets_id);
					
					MyWidgetResourceLib.fn.each(dependent_widgets_id, function(idx, dependent_widget_id) {
						var widgets = MyWidgetResourceLib.fn.getDependentWidgetsById(dependent_widget_id);
						
						if (widgets)
							MyWidgetResourceLib.fn.each(widgets, function(idy, widget) {
								if (widget) {
									//clone opts, otherwise if the loadWidgetResource method changes the opts object, then it will take efect here too.
									var cloned_opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? MyWidgetResourceLib.fn.assignObjectRecursively({}, opts) : {}; 
									
									if (widget.nodeName == "table" || widget.hasAttribute("data-widget-list") || widget.hasAttribute("data-widget-list-table") || widget.hasAttribute("data-widget-list-tree"))
										MyWidgetResourceLib.ListHandler.executeSingleListResource(widget, resource_key, cloned_opts);
									else if (widget.nodeName == "form" || widget.hasAttribute("data-widget-form"))
										MyWidgetResourceLib.FormHandler.executeSingleFormResource(widget, resource_key, cloned_opts);
									else if (widget.hasAttribute("data-widget-popup") || widget.classList.contains("modal"))
										MyWidgetResourceLib.PopupHandler.executeSinglePopupResource(widget, resource_key, cloned_opts);
									else
										MyWidgetResourceLib.FieldHandler.executeSingleElementFieldsResource(widget, resource_key, cloned_opts);
								}
							});
					});
				}
			}
		},
		
		//Purge cache from the dependent widgets.
		purgeCachedLoadDependentWidgetsResource: function(elm) {
			if (elm) {
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var dependent_widgets_id = MyWidgetResourceLib.fn.prepareDependentWidgetsId(properties["dependent_widgets_id"]);
				dependent_widgets_id = MyWidgetResourceLib.fn.filterUniqueDependentWidgetsId(dependent_widgets_id);
				
				MyWidgetResourceLib.fn.each(dependent_widgets_id, function(idx, dependent_widget_id) {
					var widgets = MyWidgetResourceLib.fn.getDependentWidgetsById(dependent_widget_id);
					
					if (widgets)
						MyWidgetResourceLib.fn.each(widgets, function(idy, widget) {
							if (widget) {
								if (widget.closest("[data-widget-form], form"))
									MyWidgetResourceLib.FormHandler.purgeCachedLoadParentFormResource(widget);
								else if (widget.closest("[data-widget-list]"))
									MyWidgetResourceLib.ListHandler.purgeCachedLoadParentListResource(widget);
								else
									MyWidgetResourceLib.ResourceHandler.purgeWidgetResources(widget, "load");
							}
						});
				});
			}
		},
		
		//Resets all fields from the dependent form widgets.
		resetFormDependentWidgets: function(elm) {
			if (elm) {
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var dependent_widgets_id = MyWidgetResourceLib.fn.prepareDependentWidgetsId(properties["dependent_widgets_id"]);
				dependent_widgets_id = MyWidgetResourceLib.fn.filterUniqueDependentWidgetsId(dependent_widgets_id);
				
				MyWidgetResourceLib.fn.each(dependent_widgets_id, function(idx, dependent_widget_id) {
					var widgets = MyWidgetResourceLib.fn.getDependentWidgetsById(dependent_widget_id);
					
					if (widgets)
						MyWidgetResourceLib.fn.each(widgets, function(idy, widget) {
							if (widget)
								MyWidgetResourceLib.FormHandler.resetForm(widget);
						});
				});
			}
		},
		
		//Resets all fields from the dependent form widgets and remove the attribute: data-widget-pks-attrs.
		//In case we wish to convert the edit forms to add forms, we should use this function. This allows to have forms that allows add and update items simultaneously.
		resetFormDependentWidgetsAndConvertThemIntoAddForms: function(elm) {
			if (elm) {
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var dependent_widgets_id = MyWidgetResourceLib.fn.prepareDependentWidgetsId(properties["dependent_widgets_id"]);
				dependent_widgets_id = MyWidgetResourceLib.fn.filterUniqueDependentWidgetsId(dependent_widgets_id);
				
				MyWidgetResourceLib.fn.each(dependent_widgets_id, function(idx, dependent_widget_id) {
					var widgets = MyWidgetResourceLib.fn.getDependentWidgetsById(dependent_widget_id);
					
					if (widgets)
						MyWidgetResourceLib.fn.each(widgets, function(idy, widget) {
							if (widget)
								MyWidgetResourceLib.FormHandler.resetFormAndConvertItIntoAddForm(widget);
						});
				});
			}
		},
		
		//Reset sorting from the dependent widgets.
		resetWidgetListResourceSort: function(elm) {
			if (elm) {
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var dependent_widgets_id = properties["dependent_widgets_id"];
				
				if (dependent_widgets_id) {
					dependent_widgets_id = MyWidgetResourceLib.fn.prepareDependentWidgetsId(dependent_widgets_id);
					
					//TODO: find a way to loop asyncronously
					MyWidgetResourceLib.fn.each(dependent_widgets_id, function(idx, dependent_widget_id) {
						var widgets = MyWidgetResourceLib.fn.getDependentWidgetsById(dependent_widget_id);
						
						if (widgets)
							MyWidgetResourceLib.fn.each(widgets, function(idy, widget) {
								if (widget)
									MyWidgetResourceLib.ListHandler.resetListResourceSort(widget);
							});
					});
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No element in resetWidgetListResourceSort method!");
		},
		
		//Toggle between the table and tree in a list widget.
		toggleWidgetListTableAndTree: function(elm) {
			if (elm) {
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var dependent_widgets_id = properties["dependent_widgets_id"];
				
				if (dependent_widgets_id) {
					dependent_widgets_id = MyWidgetResourceLib.fn.prepareDependentWidgetsId(dependent_widgets_id);
					
					//TODO: find a way to loop asyncronously
					MyWidgetResourceLib.fn.each(dependent_widgets_id, function(idx, dependent_widget_id) {
						var widgets = MyWidgetResourceLib.fn.getDependentWidgetsById(dependent_widget_id);
						
						if (widgets)
							MyWidgetResourceLib.fn.each(widgets, function(idy, widget) {
								if (widget)
									MyWidgetResourceLib.ListHandler.toggleListTableAndTree(widget);
							});
					});
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No element in toggleWidgetListTableAndTree method!");
		},
		
		//Toggle selection in the loaded items of the dependent widgets.
		toggleWidgetListAttributeSelectCheckboxes: function(elm) {
			if (elm) {
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var dependent_widgets_id = properties["dependent_widgets_id"];
				
				if (dependent_widgets_id) {
					dependent_widgets_id = MyWidgetResourceLib.fn.prepareDependentWidgetsId(dependent_widgets_id);
					
					//TODO: find a way to loop asyncronously
					MyWidgetResourceLib.fn.each(dependent_widgets_id, function(idx, dependent_widget_id) {
						var widgets = MyWidgetResourceLib.fn.getDependentWidgetsById(dependent_widget_id);
						
						if (widgets)
							MyWidgetResourceLib.fn.each(widgets, function(idy, widget) {
								if (widget) {
									var main_input = widget.querySelector("input[type=checkbox][data-widget-list-select-items-checkbox]");
									var inputs = widget.querySelectorAll("input[type=checkbox][data-widget-item-selected-checkbox]");
									var is_checked = main_input ? main_input.checked : elm.classList.contains("checked");
									
									if (!is_checked) {
										elm.classList.add("checked");
										
										if (main_input) {
											main_input.setAttribute("checked", "checked");
											main_input.checked = true;
										}
									}
									else {
										elm.classList.remove("checked");
										
										if (main_input) {
											main_input.removeAttribute("checked");
											main_input.checked = false;
										}
									}
									
									if (inputs)
										//TODO: find a way to loop asyncronously
										MyWidgetResourceLib.fn.each(inputs, function(idx, input) {
											if (!is_checked) {
												input.setAttribute("checked", "checked");
												input.checked = true;
											}
											else {
												input.removeAttribute("checked");
												input.checked = false;
											}
										});
								}
							});
					});
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No element in toggleWidgetListAttributeSelectCheckboxes method!");
		},
	});
	/****************************************************************************************
	 *				 END: SHORT ACTION HANDLER 					*
	 ****************************************************************************************/
		
	/****************************************************************************************
	 *				 START: MODAL HANDLER 					*
	 ****************************************************************************************/
	MyWidgetResourceLib.ModalHandler = MyWidgetResourceLib.fn.ModalHandler = ({
		
		existBootstrapModal: function(elm) {
			return (typeof jQuery == "object" || typeof jQuery == "function") && typeof $(elm).modal == "function";
		},
		
		getBootstrapVersion: function() {
			return $.fn.tooltip.Constructor.VERSION;
		},
		
		initModalPopup: function(elm, settings) {
			settings = MyWidgetResourceLib.fn.isPlainObject(settings) ? settings : {};
			
			var show_func = MyWidgetResourceLib.fn.convertIntoFunctions(settings["show"]);
			var hide_func = MyWidgetResourceLib.fn.convertIntoFunctions(settings["hide"]);
			var inited = MyWidgetResourceLib.fn.getNodeElementData(elm, "inited");
			
			if (!inited) {
				if (this.existBootstrapModal(elm)) {
					if (show_func)
						$(elm).on("show.bs.modal", function(e) {
							MyWidgetResourceLib.fn.executeFunctions(show_func, elm, e);
						});
					
					if (hide_func)
						$(elm).on("hide.bs.modal", function(e) {
							MyWidgetResourceLib.fn.executeFunctions(hide_func, elm, e);
						});
					
					var v = parseInt(this.getBootstrapVersion());
					
					if (v <= 4)
						$(elm).modal({
							show: false
						});
					else
						new bootstrap.Modal(elm);
				}
				else {
					if (show_func)
						MyWidgetResourceLib.fn.setNodeElementData(elm, "show", show_func);
					
					if (hide_func)
						MyWidgetResourceLib.fn.setNodeElementData(elm, "hide", hide_func);
					
					//set close btn event: data-dismiss="modal"
					var btns = elm.querySelectorAll('[data-dismiss=modal], [data-bs-dismiss=modal]');
					
					if (btns)
						for (var i = 0, t = btns.length; i < t; i++)
							btns[i].addEventListener("click", function(event) {
								event.stopPropagation();
								
								MyWidgetResourceLib.ModalHandler.hideModalPopup( this.parentNode.closest(".modal") );
							});
					
					//add click event to close popup when clicking in the background.
					elm.addEventListener("click", function(event) {
						var target = event.target;
						
						//This check if very important, bc when the popup gets loaded if we call the MyWidgetResourceLib.FieldHandler.loadElementFieldsResource where exists hashtags in the html, the system will save the popup inner html, load the resource and set the new html in the popup, which means that all the event listeners set it here in this function for the [data-dismiss=modal] won't work, bc we are saving the inner html and clean it until the resource get loaded. So this popup event is the only event active, where we need to make this check.
						if (target.classList.contains("modal") || target.getAttribute("data-dismiss") == "modal" || target.getAttribute("data-bs-dismiss") == "modal")
							MyWidgetResourceLib.ModalHandler.hideModalPopup(this);
					});
				}
			}
			
			MyWidgetResourceLib.fn.setNodeElementData(elm, "inited", true);
		},
		
		showModalPopup: function(elm) {
			if (this.existBootstrapModal(elm)) {
				var v = parseInt(this.getBootstrapVersion());
				
				if (v <= 4)
					$(elm).modal("show");
				else {
					var modal = bootstrap.Modal.getInstance(elm);
					
					if (modal && typeof modal.show == "function")
						modal.show();
					//else
					//	$(elm).modal("show");
				}
			}
			else {
	    			//trigger show event
	    			var on_show = MyWidgetResourceLib.fn.getNodeElementData(elm, "show");
	    			
	    			if (on_show)
	    				MyWidgetResourceLib.fn.executeFunctions(on_show, elm, window.event);
	    			
				//show popup
				elm.classList.add("show");
				MyWidgetResourceLib.fn.show(elm);
			}
		},
		
		hideModalPopup: function(elm) {
			if (this.existBootstrapModal(elm)) {
				var v = parseInt(this.getBootstrapVersion());
				
				if (v <= 4)
					$(elm).modal("hide");
				else {
					var modal = bootstrap.Modal.getInstance(elm);
					
					if (modal && typeof modal.hide == "function")
						modal.hide();
					//else
					//	$(elm).modal("hide");
				}
			}
			else {
	    			//trigger hide event
	    			var on_hide = MyWidgetResourceLib.fn.getNodeElementData(elm, "hide");
	    			
	    			if (on_hide)
	    				MyWidgetResourceLib.fn.executeFunctions(on_hide, elm, window.event);
	    			
	    			//hide popup
				elm.classList.remove("show");
				MyWidgetResourceLib.fn.hide(elm);
			}
		},
	});
	/****************************************************************************************
	 *				 END: MODAL FUNCTIONS 					*
	 ****************************************************************************************/
	 
	/****************************************************************************************
	 *				 START: HASHTAG FUNCTIONS 					*
	 ****************************************************************************************/
	MyWidgetResourceLib.HashTagHandler = MyWidgetResourceLib.fn.ModalHandler = ({
		
		default_input_data_var_name: "input",
		input_data_var_name: "input",
		idx_var_name: "i",
		
		escape_regex: /[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g,
		
		//'\w' means all words with '_' and '/u' means with accents and ç too.
		//html_hash_tag_parameter_full_regex: /#([\p{L}\w"' \-\+\[\]\.\$]+)#/gu, //Cannot use this bc it does not work in IE.
		html_hash_tag_parameter_full_regex: new RegExp("#([\\w\\u00C0-\\u00D6\\u00D8-\\u00F6\\u00F8-\\u024F\\u1EBD\\u1EBC\"' \\-\\+\\[\\]\\.\\$\\\\]+)#", "g"),
		
		existsHtmlHashTags: function(html) {
			return html && typeof html == "string" && html.indexOf("#") != -1 && html.match(this.html_hash_tag_parameter_full_regex);
		},
		
		existsHtmlHashTagsWithResources: function(html) {
			return html && typeof html == "string" && html.indexOf("#") != -1 && html.match(/#\[?("|')?(Resource|SLA|Resources|SLAs)("|')?\]?/i);
		},
		
		replaceHtmlHashTagsWithEmptyValues: function(html, hash_tags_to_ignore) {
			return this.replaceHtmlHashTagsWithData(html, null, null, hash_tags_to_ignore);
		},
		
		replaceHtmlHashTagsWithResources: function(html, resources_name, resources_cache_key, idx, hash_tags_to_ignore) {
			var items = this.getHTMLHashTagParametersValues(html);
			
			if (!MyWidgetResourceLib.fn.isEmptyObject(items)) {
				resources_name = MyWidgetResourceLib.fn.isArray(resources_name) ? resources_name : [resources_name];
				resources_cache_key = MyWidgetResourceLib.fn.isArray(resources_cache_key) ? resources_cache_key : [resources_cache_key];
				
				for (var i = 0, t = resources_name.length; i < t; i++) {
					var resource_name = resources_name[i];
					var resource_cache_key = resources_cache_key ? resources_cache_key[i] : null;
					var exists_input_data = resource_name ? MyWidgetResourceLib.ResourceHandler.existLoadedSLAResult(resource_name, resource_cache_key) : false;
					var input_data = exists_input_data ? MyWidgetResourceLib.ResourceHandler.getLoadedSLAResult(resource_name, resource_cache_key) : null;
					
					//console.log(items);
					//console.log(input_data);
					//console.log(record);
					
					html = this.replaceHtmlHashTagsWithData(html, input_data, idx, hash_tags_to_ignore, true);
				}
				
				//replace all the other simple hashtags with empty values and leave the hashtags that start with SLA(s) or RESOURCE(s)
				html = this.replaceHtmlHashTagsWithEmptyValues(html, hash_tags_to_ignore);
			}
			
			return html;
		},
		
		replaceHtmlHashTagsWithData: function(html, input_data, idx, hash_tags_to_ignore, only_replace_if_exists) {
			var items = this.getHTMLHashTagParametersValues(html);
			
			if (!MyWidgetResourceLib.fn.isEmptyObject(items)) {
				//filter hasht tags
				if (hash_tags_to_ignore) {
					hash_tags_to_ignore = MyWidgetResourceLib.fn.isArray(hash_tags_to_ignore) ? hash_tags_to_ignore : [hash_tags_to_ignore];
					
					for (var to_search in items)
						if (hash_tags_to_ignore.indexOf(to_search) != -1)
							delete items[to_search];
				}
				
				if (input_data) {
					var input_data_record = idx || MyWidgetResourceLib.fn.isNumeric(idx) ? this.parseNewInputData("#" + idx + "#", input_data, idx) : null; //This covers all cases, this is, if input data is an array or an object and if idx is a numeric index or a alpha key or a composite key with "[" or "]".
					/*var input_data_record = null;
					var idx_str = "" + idx;
					
					if (idx_str.indexOf("[") != -1 || idx_str.indexOf("]") != -1)
						input_data_record = this.parseNewInputData("#" + idx + "#", input_data, idx);
					if (MyWidgetResourceLib.fn.isArray(input_data) && MyWidgetResourceLib.fn.isNumeric(idx))
						input_data_record = input_data[idx];
					else if (MyWidgetResourceLib.fn.isPlainObject(input_data) && idx_str.length) 
						input_data_record = input_data[idx];
					*/
					
					//console.log(items);
					//console.log(input_data);
					//console.log(record);
					
					for (var to_search in items) {
						var replacement_code = items[to_search];
						var ignore_errors = input_data_record && to_search.indexOf("idx") == -1; //ignore errors if is a hashtag without "idx" and if input_data_record exists, becasue in this case, we will try to get this hashtag below (in the next call of the method replaceHtmlHashTagWithData).
						html = this.replaceHtmlHashTagWithData(html, input_data, idx, to_search, replacement_code, true, ignore_errors);
						
						//replaces #xxx# by $input_data_record['xxx']. This allows the user to use the #xxx#, instead of #[idx][xxx]#, which is much more user-friendly and memorable. This is very useful when we are creating lists and showing dynamic values or attributes for children in each item of the list.
						if (input_data_record)
							html = this.replaceHtmlHashTagWithData(html, input_data_record, idx, to_search, replacement_code, true);
					}
				}
				
				if (!only_replace_if_exists) {
					//replace all the other simple hashtags with empty values and leave the hashtags that start with SLA(s) or RESOURCE(s)
					for (var to_search in items) {
						var replacement_code = items[to_search];
						html = this.replaceHtmlHashTagWithData(html, null, null, to_search, replacement_code, false);
					}
				}
			}
			
			return html;
		},
		
		replaceHtmlHashTagWithData: function(html, input_data, idx, to_search, replacement_code, only_replace_if_exists, ignore_errors) {
			var replacement_value = "";
			var status = false;
			
			try {
				var idx_replacemente = MyWidgetResourceLib.fn.isNumeric(idx) ? idx : (idx ? '"' + idx + '"' : undefined);
				
				eval("var " + this.idx_var_name + " = " + idx_replacemente + ";");
				eval("var " + this.input_data_var_name + " = input_data;");
			}
			catch(e) {
				if (console && console.log) {
					console.log("Error creating idx_var_name or input_data_var_name in MyWidgetResourceLib.js.HashTagHandler.replaceHtmlHashTagsWithResources method.");
					console.log(e);
				}
			}
			
			//console.log(i);
			//console.log(input);
			
			if (replacement_code) {
				//check if replacement is an independent Resource
				var m = to_search.match(/#\[("|')?(Resource|SLA|Resources|SLAs)("|')?\]/i); //if start with Resource or SLA
				
				if (!m)
					m = to_search.match(/#("|')?(Resource|SLA|Resources|SLAs)("|')?\[/i);
				
				var resource_obj = null;
				var exists_input_data = input_data !== null;
				 
				if (m) {
					var resource_regex = new RegExp("^" + this.input_data_var_name + "\\\[(\"|')" + m[2] + "(\"|')\\\]\\\[(\"|')([^\\\]]+)(\"|')\\\]", "i");
					var sub_m = replacement_code.match(resource_regex);
					
					if (sub_m) {
						var other_resource_name = sub_m[4];
						
						if (MyWidgetResourceLib.ResourceHandler.existLoadedSLAResult(other_resource_name)) {
							resource_obj = MyWidgetResourceLib.ResourceHandler.getLoadedSLAResult(other_resource_name);
							
							replacement_code = "resource_obj" + replacement_code.substr(sub_m[0].length); //+1 == "[", +1 == "]"
							status = true;
						}
						//else Do nothing bc the resource doesn't exist yet and it could be replaced later.
					}
					//else This case should not happen bc the getHTMLHashTagParametersValues already takes care this case
				}
				else if (exists_input_data)
					status = true;
				
				if (status) {
					try {
						//check if replacement_code contains the string: "[idx]" and if so, it means the prefix (eg: input_data) must be an array or an object.
						//Then check if replacement_code contains the string: "[idx][" and if so, it means the prefix (eg: input_data[idx]) must be an array or an object. This avoids giving an exception below
						var str = "[" + this.idx_var_name + "]";
						var pos = replacement_code.indexOf(str);
						
						if (pos === -1) { //Note that we can have #[idx + 3][xxx]#
							var regex = new RegExp("\\[" + this.idx_var_name + "[^a-z\_][^\\]]*\\]");
							var m = replacement_code.match(regex);
							
							if (m) {
								str = m[0];
								pos = m.index;
							}
						}
						
						if (pos !== -1) {
							var prefix = replacement_code.substr(0, pos);
							eval('var arr = ' + prefix + ';');
							
							if (!MyWidgetResourceLib.fn.isArray(arr) && !MyWidgetResourceLib.fn.isPlainObject(arr))
								status = false;
							else if (replacement_code.indexOf(str + "[")) {
								eval('var arr = ' + prefix + str + ';');
								
								if (!MyWidgetResourceLib.fn.isArray(arr) && !MyWidgetResourceLib.fn.isPlainObject(arr))
									status = false;
							}
						}
						
						//Do the same checks, but for strings with numbers, like: "[0]". This avoids giving an exception below
						if (status) {
							var m = replacement_code.match(/(\[[0-9]+\])/);
							
							if (m && m.index) {
								var prefix = replacement_code.substr(0, m.index);
								eval('var arr = ' + prefix + ';');
								
								if (!MyWidgetResourceLib.fn.isArray(arr) && !MyWidgetResourceLib.fn.isPlainObject(arr))
									status = false;
								else {
									var sub_m = replacement_code.match(/(\[[0-9]+\]\[)/);
									
									if (sub_m) {
										eval('var arr = ' + prefix + m[0] + ';');
										
										if (!MyWidgetResourceLib.fn.isArray(arr) && !MyWidgetResourceLib.fn.isPlainObject(arr))
											status = false;
									}
								}
							}
						}
						
						//set replacement_value, if the checks above are correct and status is true
						if (status) {
							eval('replacement_value = ' + replacement_code + ';');
							
							//set replacement_value to empty string if is undefined so it can be replaced. 
							//Note that if we don't do this and the hashtag is inside of a link, when the browser redirects the page to the correspondent url, the url will be cut off on the first '#' char, discarding the rest of the url. This is, if url == "http://..../foo/?name=#name#&age=#age#&...", if #name# is undefined, then the browser will only detect "http://..../foo/?name=". So we must replace all the #hash tags in the url. 
							//However we should not replace the hashtags from the resources that don't exist yet. In this case, the developer will realize that something is wrong and will load the resource first or edit this hashtag after on the resource complete callback.
							if (typeof replacement_value == "undefined") {
								replacement_value = "";
								
								if (only_replace_if_exists)
									status = false;
							}
						}
					}
					catch(e) {
						//set status to false by not replacing anything so the developer can check what is wrong.
						status = false;
						
						if (!ignore_errors && console && console.log) {
							console.log("Error replacing hashtag '" + to_search + "' with value: '" + replacement_code + "', with idx: '" + idx_replacemente + "', in MyWidgetResourceLib.js.HashTagHandler.replaceHtmlHashTagsWithResources method.");
							
							if (m && resource_obj)
								console.log(resource_obj);
							else if (!m)
								console.log(input_data);
							
							console.log(e);
						}
					}
				}
				
				//replaces all tags with empty string
				if (!only_replace_if_exists && !status) {
					replacement_value = "";
					status = true;
				}
			}
			//else Do nothing bc this case should not happen, so we should not replace anything so the developer can check what is wrong.
			
			//only replace if status is true
			if (status) {
				var to_search_escaped = to_search.replace(this.escape_regex, '\\$&');
				var to_search_regex = RegExp(to_search_escaped, "g");
				
				//console.log(to_search_regex+" ==> "+replacement_value);
				html = html.replace(to_search_regex, replacement_value);
			}
			
			return html;
		},
		
		//any change here should be replicated in the HtmlFormHandler.php::parseNewInputData and PTLFieldUtilObj.js::parseNewInputData methods too
		parseNewInputData: function(value, input_data, idx) {
			if (MyWidgetResourceLib.fn.isArray(value) || MyWidgetResourceLib.fn.isPlainObject(value))
				return value;
			
			if (value && input_data) {
				value = ("" + value).trim();
				
				//be sure that $value is something like: #foo#. "#bar##foo#" will not be allowed, bc it doesn't make sense here!
				if (value.substr(0, 1) == "#" && value.substr(value.length - 1, 1) == "#" && value.substr(1, value.length - 2).indexOf("#") == -1) {
					var results = this.getParsedValue(value, input_data, idx, true);
					value = results[0];
				}
				
				return value;
			}
			
			return input_data; //return input_data in case there isn't value or input_data.
		},
		
		//any change here should be replicated in the HtmlFormHandler.php::getParsedValue and PTLFieldUtilObj.js::parseNewInputData methods too
		getParsedValue: function(value, input_data, idx, result_in_array) {
			var results = new Array();
			var items = this.getHTMLHashTagParametersValues(value);
			
			if (!MyWidgetResourceLib.fn.isEmptyObject(items)) {
				var offset = 0;
				var length = value.length;
				var reg = new RegExp(this.html_hash_tag_parameter_full_regex.source, typeof this.html_hash_tag_parameter_full_regex.flags != "undefined" ? this.html_hash_tag_parameter_full_regex.flags : "g"); //must be outisde of the do-while, otherwise it will give an infinitive loop. We need to clone the this.html_hash_tag_parameter_full_regex by creating a new Regex, otherwise everytime we run this function, the loop will use the regex.lastIndex from the previous function call.
				var matches_exists = false;
				
				var idx_replacemente = MyWidgetResourceLib.fn.isNumeric(idx) ? idx : (idx ? '"' + idx + '"' : undefined);
				
				try {
					eval("var " + this.idx_var_name + " = " + idx_replacemente + ";");
					eval("var " + this.input_data_var_name + " = input_data;");
				}
				catch(e) {
					if (console && console.log) {
						console.log("Error creating idx_var_name or input_data_var_name in MyWidgetResourceLib.js.HashTagHandler.getParsedValue method.");
						console.log(e);
					}
				}
				
				do {
					var matches = reg.exec(value);
					
					if (matches !== null && matches.length > 1 && matches[1]) {
						var to_search = matches[0];
						var replacement_code = items[to_search];
						var replacement_value = "";
						
						matches_exists = true;
						
						if (replacement_code) {
							try {
								eval('replacement_value = ' + replacement_code + ';');
							}
							catch(e) {
								if (console && console.log) {
									console.log("Error replacing hashtag '" + to_search + "' with value: '" + replacement_code + "' in MyWidgetResourceLib.js.HashTagHandler.getParsedValue method.");
									console.log(e);
								}
							}
						}
						
						var aux = value.substr(offset, matches.index - offset);
						if (aux)
							results.push('"' + aux.replace(/\\/g, '\\\\').replace(/"/g, '\\"') + '"'); //must contain the escape for the slash, otherwise if exist any slash it will get lost on the way
						
						results.push(replacement_value); //in case the $replacemente be an array, the array is safetly save in the $results variable.
						
						offset = matches.index + to_search.length;
					}
				}
				while (matches && matches.length > 0 && offset < length);
				
				if (matches_exists)	{
					var aux = value.substr(offset);
					if (aux)
						results.push('"' + aux.replace(/\\/g, '\\\\').replace(/"/g, '\\"') + '"'); //must contain the escape for the slash, otherwise if exist any slash it will get lost on the way
				}
				else
					results.push(value);
			}
			else
				results.push(value);
			
			//$result_in_array is used to save the correct types of objects inside of the $results varialble, otherwise if any replacemente is an array (like Array X), when we concatenate with a string, this array (the Array X) will be converted to a string and lose his items...
			return result_in_array ? results : results.join(" ");
		},
		
		//any change here should be replicated in the HtmlFormHandler.php::getParsedValue and PTLFieldUtilObj.js::parseNewInputData methods too
		getHTMLHashTagParametersValues: function(value) {
			var items = {};
			
			if (value && typeof value == "string" && value.indexOf("#") != -1) {
				var reg = new RegExp(this.html_hash_tag_parameter_full_regex.source, typeof this.html_hash_tag_parameter_full_regex.flags != "undefined" ? this.html_hash_tag_parameter_full_regex.flags : "g"); //must be outisde of the do-while, otherwise it will give an infinitive loop. We need to clone the this.html_hash_tag_parameter_full_regex by creating a new Regex, otherwise everytime we run this function, the loop will use the regex.lastIndex from the previous function call.
				var matches_exists = null;
				
				do {
					var matches = reg.exec(value);
					matches_exists = matches !== null && matches.length > 1 && matches[1];
					
					if (matches_exists) {
						var m = matches[1];
						var to_search = matches[0];
						var replacement = "";
						//console.log(m);
						
						//echo "m($value):$m<br>";
						if (m.indexOf("[") != -1 || m.indexOf("]") != -1) { //if value == #[0]name# or #[$idx - 1][name]#, returns $input[0]["name"] or $input[$idx - 1]["name"]
							var sub_matches = m.match(/([^\[\]]+)/g);
							
							if (sub_matches.length > 0) {
								for (var j = 0, tj = sub_matches.length; j < tj; j++) {
									var sml = ("" + sub_matches[j]).toLowerCase();
									
									if (sml == 'idx' || sml == '$idx')
										sub_matches[j] = this.idx_var_name;
									else if (sml == '\\$idx')
										sub_matches[j] = this.idx_var_name;
									else if (sml.match(/\\?\$?idx[^a-z\_]/gi)) //fix the cases like: #[$idx - 1]#
										sub_matches[j] = sub_matches[j].replace(/\\?\$?idx[^a-z\_]/gi, this.idx_var_name);
									else if (sml.match("/(^|[^a-z\_])idx[^a-z\_]/gi") && !sml.match("/[a-z\_]/i")) //fix the cases like: #[$idx - 1]# where there is not alphabethic characters.
										sub_matches[j] = sub_matches[j].replace("/(^|[^a-z\_])\\?\$?idx[^a-z\_]/gi", this.idx_var_name);
									else if (sml.match("/\$idx[^a-z\_]/gi") && sml.match("/[a-z\_]/i")) { //fix the cases like: #[attribute_name_$idx]# where there are alphabethic characters.
										sub_matches[j] = '"' + sub_matches[j].replace(/\\?\$idx[^a-z\_]/gi, '" + ' + this.idx_var_name + ' + "') + '"';
										sub_matches[j] = sub_matches[j].replace(/"" \+ /g, "").replace(/ \+ ""/g, "");
									}
									else if (!MyWidgetResourceLib.fn.isNumeric(sml) && sml.indexOf("'") == -1 && sml.indexOf('"') == -1)
										sub_matches[j] = '"' + sub_matches[j] + '"';
								}
								
								replacement = this.input_data_var_name + "[" + sub_matches.join("][") + "]";
							}
						}
						else if (m == "$" + this.input_data_var_name) //#$input#, returns $input - Returns it-self.
							replacement = this.input_data_var_name;
						else if (m == "$" + this.default_input_data_var_name) //#$input#, returns $input - Returns it-self.
							replacement = this.default_input_data_var_name;
						else if (m == "$input" || m == "$input_data") { //this.default_input_data_var_name or this.input_data_var_name should have this already covered, otherwise something is wrong with the above code.
							alert("MAJOR ERROR in getParsedValue method in the files: HTMLFormHandler.php and PTLFieldsUtilObj.js. Something is missing here. Please check the code in thei method.");
						}
						else if (m == "idx" || m == "$idx" || m == "\\$idx") //#idx#, returns $idx
							replacement = this.idx_var_name;//replace by the correspondent key
						else //if $value == #name#, returns $input["name"]
							replacement = this.input_data_var_name + '["' + m + '"]';
						
						items[to_search] = replacement; //in case the $replacemente be an array, the array is safetly save in the $parameters variable.
					}
				}
				while (matches_exists);
			}
			
			return items;
		},
		
		getWidgetHashTagsBasedInResources: function(hash_tags, resources_name, resources_cache_key, idx) {
			var filtered_hash_tags = [];
			
			resources_name = MyWidgetResourceLib.fn.isArray(resources_name) ? resources_name : [resources_name];
			resources_cache_key = MyWidgetResourceLib.fn.isArray(resources_cache_key) ? resources_cache_key : [resources_cache_key];
			
			MyWidgetResourceLib.fn.each(hash_tags, function(i, hash_tag) {
				//ignore tags that start with: Resource(s)|SLA(s)
				if (!hash_tag.match(/#\[?("|')?(Resource|SLA|Resources|SLAs)("|')?\]?\[/i)) {
					//ignore tags that belong to parent data
					
					for (var i = 0, t = resources_name.length; i < t; i++) {
						var resource_name = resources_name[i];
						var resource_cache_key = resources_cache_key ? resources_cache_key[i] : null;
						var exists_input_data = resource_name ? MyWidgetResourceLib.ResourceHandler.existLoadedSLAResult(resource_name, resource_cache_key) : false;
						var input_data = exists_input_data ? MyWidgetResourceLib.ResourceHandler.getLoadedSLAResult(resource_name, resource_cache_key) : null;
						var value = input_data ? MyWidgetResourceLib.HashTagHandler.parseNewInputData(hash_tag, input_data, idx) : undefined;
						//console.log(hash_tag+":"+value);
						
						//if value is undefined means that the hash tag doesn't exist in data object, which means it belongs to another inner data. Note that in case the hash tag doesn't exist either in another inner data, we should leave and show this hash tag so the developer can see and fix it.
						if (value == undefined && filtered_hash_tags.indexOf(hash_tag) == -1)
							filtered_hash_tags.push(hash_tag);
					}
				}
			});
			
			return filtered_hash_tags;
		},
		
		getWidgetHashTagsBasedInData: function(hash_tags, data, idx) {
			var filtered_hash_tags = [];
			
			MyWidgetResourceLib.fn.each(hash_tags, function(i, hash_tag) {
				//ignore tags that start with: Resource(s)|SLA(s)
				if (!hash_tag.match(/#\[?("|')?(Resource|SLA|Resources|SLAs)("|')?\]?\[/i)) {
					//ignore tags that belong to parent data
					var value = data ? MyWidgetResourceLib.HashTagHandler.parseNewInputData(hash_tag, data, idx) : undefined;
					
					//if value is undefined means that the hash tag doesn't exist in data object, which means it belongs to another inner data. Note that in case the hash tag doesn't exist either in another inner data, we should leave and show this hash tag so the developer can see and fix it.
					if (value == undefined && filtered_hash_tags.indexOf(hash_tag) == -1)
						filtered_hash_tags.push(hash_tag);
				}
			});
			
			return filtered_hash_tags;
		},
		
		getWidgetResourceValueInnerItemsHtmlHashTagsBasedInResources: function(elm_or_html, resources_name, resources_cache_key, idx) {
			var hash_tags = this.getWidgetResourceValueInnerItemsHtmlHashTags(elm_or_html);
			var filtered_hash_tags = this.getWidgetHashTagsBasedInResources(hash_tags, resources_name, resources_cache_key, idx);
			
			return filtered_hash_tags;
		},
		
		getWidgetResourceValueInnerItemsHtmlHashTagsBasedInData: function(elm_or_html, data, idx) {
			var hash_tags = this.getWidgetResourceValueInnerItemsHtmlHashTags(elm_or_html);
			var filtered_hash_tags = this.getWidgetHashTagsBasedInData(hash_tags, data, idx);
			
			return filtered_hash_tags;
		},
		
		getWidgetItemsHtmlHashTagsBasedInResources: function(elm_or_html, resources_name, resources_cache_key, idx) {
			var hash_tags = this.getWidgetItemsHtmlHashTags(elm_or_html);
			var filtered_hash_tags = this.getWidgetHashTagsBasedInResources(hash_tags, resources_name, resources_cache_key, idx);
			
			return filtered_hash_tags;
		},
		
		getWidgetItemsHtmlHashTagsBasedInData: function(elm_or_html, data, idx) {
			var hash_tags = this.getWidgetItemsHtmlHashTags(elm_or_html);
			var filtered_hash_tags = this.getWidgetHashTagsBasedInData(hash_tags, data, idx);
			
			return filtered_hash_tags;
		},
		
		getWidgetResourceValueInnerItemsHtmlHashTags: function(elm_or_html) {
			var hash_tags = [];
			var elm = elm_or_html;
			var is_html = typeof elm_or_html == "string";
			
			if (is_html) {
				if (elm_or_html.replace(/\s+/g, "") == "")
					return hash_tags;
				
				var tag_name = elm_or_html.substr(1, elm_or_html.indexOf(" ") - 1).toLowerCase();
				var node_name = tag_name == "li" ? "ul" : (
					tag_name == "tr" || tag_name == "tbody" || tag_name == "thead" ? "table" : "div"
				); //get the right node name otherwise when we append the elm_or_html the browser will discard the invalid nodes.
				elm = document.createElement(node_name);
				elm.insertAdjacentHTML('afterbegin', elm_or_html);
			}
			
			var items = elm.querySelectorAll("[data-widget-item]");
			
			if (items) {
				var items_parents = [];
				
				MyWidgetResourceLib.fn.each(items, function(i, item) {
					//get the item parent with [data-widget-resource-value] that is inside of elm
					var item_parent = item;
					var max_iterations = 10000;
					
					while(max_iterations > 0) {
						item_parent = item_parent.parentNode;
						
						if (item_parent && item_parent == elm) {
							item_parent = null;
							break;
						}
						else if (!item_parent || item_parent.hasAttribute("data-widget-resource-value"))
							break;
						
						max_iterations--;
					};
					
					if (item_parent && items_parents.indexOf(item_parent) == -1)
						items_parents.push(item_parent);
				});
				
				//get hash tags for each item_parent
				MyWidgetResourceLib.fn.each(items_parents, function(i, item_parent) {
					var item_parent_html = item_parent.innerHTML;
					var item_parent_hash_tags = MyWidgetResourceLib.HashTagHandler.getHTMLHashTagParametersValues(item_parent_html);
					
					//filter hash tags
					if (item_parent_hash_tags)
						for (var hash_tag in item_parent_hash_tags)
							if (hash_tags.indexOf(hash_tag) == -1)
								hash_tags.push(hash_tag);
				});
			}
			
			if (is_html)
				elm = null; //clean memory
			
			return hash_tags;
		},
		
		getWidgetItemsHtmlHashTags: function(elm_or_html) {
			var hash_tags = [];
			var elm = elm_or_html;
			var is_html = typeof elm_or_html == "string";
			
			if (is_html) {
				if (elm_or_html.replace(/\s+/g, "") == "")
					return hash_tags;
				
				var tag_name = elm_or_html.substr(1, elm_or_html.indexOf(" ") - 1).toLowerCase();
				var node_name = tag_name == "li" ? "ul" : (
					tag_name == "tr" || tag_name == "tbody" || tag_name == "thead" ? "table" : "div"
				); //get the right node name otherwise when we append the elm_or_html the browser will discard the invalid nodes.
				elm = document.createElement(node_name);
				elm.insertAdjacentHTML('afterbegin', elm_or_html);
			}
			
			var items = elm.querySelectorAll("[data-widget-item]");
			
			if (items) {
				var html = "";
				
				MyWidgetResourceLib.fn.each(items, function(i, item) {
					html += item.outerHTML;
				});
				
				//get hash tags for each item_parent
				var html_hash_tags = MyWidgetResourceLib.HashTagHandler.getHTMLHashTagParametersValues(html);
				
				//filter hash tags
				if (html_hash_tags)
					for (var hash_tag in html_hash_tags)
						if (hash_tags.indexOf(hash_tag) == -1)
							hash_tags.push(hash_tag);
			}
			
			if (is_html)
				elm = null; //clean memory
			
			return hash_tags;
		}
	});
	/****************************************************************************************
	 *				 END: HASHTAG FUNCTIONS 					*
	 ****************************************************************************************/
	
	/****************************************************************************************
	 *				 START: CHART FUNCTIONS 					*
	 ****************************************************************************************/
	MyWidgetResourceLib.ChartHandler = MyWidgetResourceLib.fn.ChartHandler = ({
		
		//more cdn files in https://cdnjs.com/libraries/Chart.js
		//remote_chart_js_url: "https://cdn.jsdelivr.net/npm/chart.js",
		remote_chart_js_url: "https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js",
		chart_js_url: null, //init when the MyWidgetResourceLib is inited
		
		loadChartResource: function(elm, opts) {
			opts = opts ? opts : {};
			var complete_handler = opts["complete"] ? opts["complete"] : null;
			
			opts["complete"] = function(elm, resources_name, resources_cache_key) {
				complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
				
				if (complete_handler)
					MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
				
				MyWidgetResourceLib.ChartHandler.drawChart(elm);
			};
			
			var status = MyWidgetResourceLib.FieldHandler.loadFieldResource(elm, opts);
			
			if (!status)
				MyWidgetResourceLib.ChartHandler.drawChart(elm);
			
			return status;
		},
		
		//Draws a graph inside of the current widget based in the result of a resource, defined in the data-sets of the widget properties.
		//parent_data_values is to be used when it comes from the display handler where it passed automatically an object/Array with the correspondent values. The idea is to do something similar with the drawListData and drawListDataRecursively
		drawChart: function(elm, parent_data_values) {
			var error_message = null;
			
			if (elm) {
				var win = MyWidgetResourceLib.fn.getElementWindow(elm);
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var graph_properties = properties["graph"];
				var include_lib = !graph_properties.hasOwnProperty("include_lib") || graph_properties["include_lib"];
				
				//include chart.js, if not yet included
				if (include_lib)
					MyWidgetResourceLib.ChartHandler.includeChartScript(win);
				
				if (typeof Chart != "function") {
					if (win.adding_chart_js_script) {
						elm.counting_if_chart_js_is_already_added = (elm.counting_if_chart_js_is_already_added > 0 ? elm.counting_if_chart_js_is_already_added : 0) + 1;
						var timeout_milli_secs = 500;
						var max_secs = 10; //10 secs
						var max_count = 10*1000/timeout_milli_secs;
						
						if (elm.counting_if_chart_js_is_already_added > max_count)
							error_message = "MyWidgetResourceLib.js error: Script 'chart.js' took more than " + max_secs + " secs to load, so the system stopped this widget to be drawn!";
						else
							setTimeout(function() {
								MyWidgetResourceLib.ChartHandler.drawChart(elm, parent_data_values);
							}, timeout_milli_secs);
					}
					else
						error_message = "MyWidgetResourceLib.js error: Script 'chart.js' not included!";
				}
				else {
					//console.log(elm);
					//console.log(elm.counting_if_chart_js_is_already_added);
					
					//clean elm.counting_if_chart_js_is_already_added
					elm.counting_if_chart_js_is_already_added = null;
					delete elm.counting_if_chart_js_is_already_added;
					
					//draw chart
					var canvas = elm.nodeName.toLowerCase() == "canvas" ? elm : elm.querySelector('canvas');
					
					if (!canvas) {
						var doc = MyWidgetResourceLib.fn.getElementDocument(elm);
						canvas = doc ? doc.createElement("canvas") : null;
						canvas.setAttribute("style", "width:100%; height:100%;"); //otherwise the graph is to thin.
						elm.appendChild(canvas);
					}
					
					if (canvas) {
						//check if it was previous inited, otherwise it will give a javascript error when we execute the "new Chart(..." method again.
						if (!canvas.chart_inited) {
							canvas.chart_inited = true;
							
							var callbacks = MyWidgetResourceLib.fn.isPlainObject(graph_properties["callbacks"]) ? graph_properties["callbacks"] : {};
							var parse_func = MyWidgetResourceLib.fn.convertIntoFunctions(callbacks["parse"]);
							var click_func = MyWidgetResourceLib.fn.convertIntoFunctions(callbacks["click"]);
							var hover_func = MyWidgetResourceLib.fn.convertIntoFunctions(callbacks["hover"]);
							var legend = graph_properties["legend"];
							var title = graph_properties["title"];
							var sub_title = graph_properties["sub_title"];
							var data_sets_props = graph_properties["data_sets"];
							var options = graph_properties["options"];
							var default_type = null;
							var data_sets = [];
							//console.log(graph_properties);
							
							//prepare options
							options = MyWidgetResourceLib.fn.isPlainObject(options) ? options : {};
							
							//set it as responsive so it can resize automatically when its parent gets resized.
							//options["responsive"] = true; //already does this by default
							
							//prepare legend
							if (MyWidgetResourceLib.fn.isPlainObject(legend)) {
								if (!MyWidgetResourceLib.fn.isPlainObject(options["plugins"]))
									options["plugins"] = {};
								
								if (!MyWidgetResourceLib.fn.isPlainObject(options["plugins"]["legend"]))
									options["plugins"]["legend"] = {};
								
								for (var key in legend) {
									var value = legend[key];
									
									if (key == "text_align") {
										if (!MyWidgetResourceLib.fn.isPlainObject(options["plugins"]["legend"]["labels"]))
											options["plugins"]["legend"]["labels"] = {};
										
										options["plugins"]["legend"]["labels"]["textAlign"] = value;
									}
									else if (key == "display")
										options["plugins"]["legend"][key] = value ? true : false;
									else
										options["plugins"]["legend"][key] = value;
								}
							}
							
							//prepare title
							if (MyWidgetResourceLib.fn.isPlainObject(title)) {
								if (!MyWidgetResourceLib.fn.isPlainObject(options["plugins"]))
									options["plugins"] = {};
								
								if (!MyWidgetResourceLib.fn.isPlainObject(options["plugins"]["title"]))
									options["plugins"]["title"] = {};
								
								for (var key in title) {
									var value = title[key];
									
									if (key == "display")
										options["plugins"]["title"][key] = value ? true : false;
									else
										options["plugins"]["title"][key] = value;
								}
							}
							
							//prepare sub_title
							if (MyWidgetResourceLib.fn.isPlainObject(sub_title)) {
								if (!MyWidgetResourceLib.fn.isPlainObject(options["plugins"]))
									options["plugins"] = {};
								
								if (!MyWidgetResourceLib.fn.isPlainObject(options["plugins"]["subtitle"]))
									options["plugins"]["subtitle"] = {};
								
								for (var key in sub_title) {
									var value = sub_title[key];
									
									if (key == "display")
										options["plugins"]["subtitle"][key] = value ? true : false;
									else
										options["plugins"]["subtitle"][key] = value;
								}
							}
							
							//prepare data sets
							if (MyWidgetResourceLib.fn.isPlainObject(data_sets_props))
								data_sets_props = [data_sets_props];
							
							MyWidgetResourceLib.fn.each(data_sets_props, function(idx, data_set_props) {
								if (MyWidgetResourceLib.fn.isPlainObject(data_set_props) && !MyWidgetResourceLib.fn.isEmptyObject(data_set_props)) {
									//getting properties
									var chart_type = data_set_props["chart_type"];
									var data_type = data_set_props["data_type"]; //resource or hardcoded or parent
									var data_order = data_set_props["data_order"];
									var data_title = data_set_props["data_title"];
									var data_labels = data_set_props["data_labels"]; //can be a string with the attribute name or an array with the hardcoded labels
									var data_values = data_set_props["data_values"]; //can be a string with the attribute name or an array with the hardcoded values or an associative array/object with the hardcoded 'labels:values'.
									var resource_name = data_set_props["data_resource_name"];
									var data_set_parse_func = MyWidgetResourceLib.fn.convertIntoFunctions(data_set_props["data_parse"]);
									
									var background_color = data_set_props["background_color"];
									var border_color = data_set_props["border_color"];
									var border_width = data_set_props["border_width"];
									
									//backup some properties
									var chart_type_bkp = chart_type;
									var data_labels_bkp = data_labels; 
									var data_values_bkp = data_values;
									
									//convert comma delimiter strings into arrays
									if (typeof data_labels == "string" && (data_labels.indexOf(",") != -1 || data_labels.indexOf(";") != -1))
										data_labels = data_labels.replace(/\s*(,|;)+\s*/g, ",").split(","); //remove spaces between commas and replace semi-colon with commas.
									
									if (typeof data_values == "string" && (data_values.indexOf(",") != -1 || data_values.indexOf(";") != -1))
										data_values = data_values.replace(/\s*(,|;)+\s*/g, ",").split(","); //remove spaces between commas and replace semi-colon with commas.
									
									//prepare data
									var data = null;
									
									if (data_type == "resource") {
										if (!resource_name) { //get parent first resource
											var parent_widget = elm.closest("[data-widget-resources]");
											
											if (parent_widget) {
												var parent_resources = MyWidgetResourceLib.ResourceHandler.getWidgetResources(parent_widget, "load");
												
												if (parent_resources && parent_resources.length > 0 && parent_resources[0]["name"])
													resource_name = parent_resources[0]["name"];
											}
										}
										
										data = resource_name ? MyWidgetResourceLib.ResourceHandler.getLoadedSLAResult(resource_name) : null;
										//prepare data_labels with resource data 
										if (typeof data_labels_bkp == "string" && data)
											data_labels = MyWidgetResourceLib.ChartHandler.prepareDataSetLabels(data, data_labels);
									}
									else if (data_type == "parent")
										data = parent_data_values;
									else
										data = data_values;
									
									data = MyWidgetResourceLib.ChartHandler.prepareDataSetData(data, data_labels);
									
									//prepare data_set_obj
									if (data) {
										var ok = true;
										
										//prepare chart_type
										if (chart_type == "vertical_bar")
											chart_type = "bar";
										else if (chart_type == "horizontal_bar") {
											chart_type = "bar";
											options.indexAxis = "y";
										}
										
										//prepare default_type
										if (!default_type)
											default_type = chart_type;
										
										//prepare data_set_obj
										var data_set_obj = {
											type: chart_type,
											label: data_title,
											order: parseInt(data_order) >= 0 ? parseInt(data_order) : null,
											data: data
										};
										
										if (data_type == "resource" && MyWidgetResourceLib.fn.isArray(data)) {
											//prepare data_labels_bkp in case is not defined. This avoids the graph.js to not show this graph.
											if ((typeof data_labels_bkp != "string" || data_labels_bkp !== "") && MyWidgetResourceLib.fn.isPlainObject(data[0]))
												for (var key in data[0]) 
													if (key != data_values_bkp && !MyWidgetResourceLib.fn.isNumeric(data[0][key])) {
														data_labels_bkp = key;
														break;
													}
											
											if ((typeof data_values_bkp != "string" || data_values_bkp !== "") && MyWidgetResourceLib.fn.isPlainObject(data[0]))
												for (var key in data[0]) 
													if (key != data_labels_bkp && MyWidgetResourceLib.fn.isNumeric(data[0][key])) {
														data_values_bkp = key;
														break;
													}
											
											data_set_obj.parsing = {
												xAxisKey: typeof data_labels_bkp == "string" ? data_labels_bkp : null,
												yAxisKey: typeof data_values_bkp == "string" ? data_values_bkp : null
											};
											
											if (chart_type_bkp == "horizontal_bar") {
												var aux = data_set_obj.parsing.xAxisKey;
												data_set_obj.parsing.xAxisKey = data_set_obj.parsing.yAxisKey;
												data_set_obj.parsing.yAxisKey = aux;
											}
											
											//cannot be null otherwise the graph.js gives a javascript error
											if (data_set_obj.parsing.xAxisKey === null || data_set_obj.parsing.yAxisKey === null)
												ok = false;
										}
										//if hard coded values with labels and if exists other similar resources, resort the data variable according with previous data_sets
										else if (data_type != "resource" && MyWidgetResourceLib.fn.isPlainObject(data) && data_sets.length >= 1)
											data_set_obj.data = MyWidgetResourceLib.ChartHandler.sortDataSetDataLikeOthers(data, data_sets);
										
										if (ok) {
											if (background_color)
												data_set_obj.backgroundColor = background_color;
											
											if (border_color)
												data_set_obj.borderColor = border_color;
											
											if (border_width)
												data_set_obj.borderWidth = border_width;
											
											if (data_set_parse_func)
												data_set_obj = MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(data_set_parse_func, elm, data_set_obj);
											
											//add data_set_obj to data_sets
											data_sets.push(data_set_obj);
										}
									}
								}
							});
							
							var chart_settings = {
								type: default_type,
								data: {
									datasets: data_sets
								},
								options: options
							};
							
							//prepare unique charts with simple labels and data
							if (data_sets.length == 1) {
								var labels = [];
								var values = [];
								var data_set_data = data_sets[0].data;
								
								if (MyWidgetResourceLib.fn.isPlainObject(data_set_data)) {
									MyWidgetResourceLib.fn.each(data_set_data, function(key, value) {
										labels.push(key);
										values.push(value);
									});
									
									chart_settings.data.datasets[0].data = values;
									chart_settings.data.labels = labels;
								}
							}
							//prepare radar charts with correspondent labels and values
							else if (default_type == "radar" && data_sets.length > 1) {
								MyWidgetResourceLib.fn.each(data_sets, function(idx, data_set) {
									var labels = [];
									var values = [];
									var data_set_data = data_set.data;
									
									if (MyWidgetResourceLib.fn.isPlainObject(data_set_data)) {
										MyWidgetResourceLib.fn.each(data_set_data, function(key, value) {
											labels.push(key);
											values.push(value);
										});
										
										data_set.fill = true;
										data_set.data = values;
										chart_settings.data.labels = labels;
									}
								});
							}
							//console.log(chart_settings);
							
							//prepare events
							if (click_func)
								chart_settings.options.onClick = function(e, a, b) {
									MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(click_func, elm, chart_settings, e, a, b);
								};
							
							if (hover_func)
								chart_settings.options.onHover = function (e, item) {
									if (item.length)
										MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(hover_func, elm, chart_settings, e, item);
								};
							
							if (parse_func)
								data_set_obj = MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(parse_func, elm, chart_settings);
							
							new Chart(canvas, chart_settings);
						}
					} 
					else
						error_message = "Chart.js error: Canvas cannot be undefined!";
				}
			} 
			else
				error_message = "Chart.js error: Widget cannot be undefined!";
			
			if (error_message && console && console.log) {
				try {
					throw new Error(error_message + " Please check your javascript code.");
				}
				catch(e) {
					console.log(e);
				}
			}
		},
		
		prepareDataSetLabels: function(data, data_labels) {
			if (typeof data_labels == "string" && MyWidgetResourceLib.fn.isArray(data) && MyWidgetResourceLib.fn.isPlainObject(data[0])) {
				var attr_name = data_labels;
				data_labels = [];
				
				for (var i = 0, t = data.length; i < t; i++) 
					if (MyWidgetResourceLib.fn.isPlainObject(data[i]) && data[i].hasOwnProperty(attr_name)) {
						var label = data[i][attr_name];
						
						if (label || MyWidgetResourceLib.fn.isNumeric(label))
							data_labels.push(label);
					}
			}
			
			return data_labels;
		},
		
		prepareDataSetData: function(data, data_labels) {
			var new_data = null;
			
			//prepare data
			if (MyWidgetResourceLib.fn.isPlainObject(data)) {
				if (MyWidgetResourceLib.fn.isArray(data_labels)) {
					//prepare new object based in data_labels
					var assoc_arr = {};
					var i = 0;
					
					for (var key in data) {
						var new_key = data_labels.length > i ? data_labels[i] : key;
						
						assoc_arr[new_key] = data[key];
						i++;
					}
					
					//data is a new associative array with the user labels
					new_data = assoc_arr;
				}
				else //data is the original associative array
					new_data = data;
			}
			else if (MyWidgetResourceLib.fn.isArray(data)) {
				//check if simple array, with only native values
				var can_be_converted_assoc = true;
				
				for (var i = 0, t = data.length; i < t; i++) {
					var value = data[i];
					
					if (MyWidgetResourceLib.fn.isPlainObject(value) || MyWidgetResourceLib.fn.isArray(value)) {
						can_be_converted_assoc = false;
						break;
					}
				}
				
				//if simple array, converts it to an associative array/object
				//for cases like: [1,2,3]
				if (can_be_converted_assoc) {
					var assoc_arr = {};
					
					for (var i = 0, t = data.length; i < t; i++) {
						var key = MyWidgetResourceLib.fn.isArray(data_labels) && data_labels.length > i ? data_labels[i] : i;
						
						assoc_arr[key] = data[i];
					}
					
					//Data is now an associative array.
					new_data = assoc_arr;
				}
				else { 
					new_data = [];
					
					//Data should be now an array with objects like DB Records. But in case, we have an array with inner arrays or native values, we filter them. This is, for the cases like: [ [1,2,3], [4,5,6], 4, 5 ]
					for (var i = 0, ti = data.length; i < ti; i++) 
						if (MyWidgetResourceLib.fn.isPlainObject(data[i]))
							new_data.push(data[i]);
						//else //if is native value or array, ignore it because it means there is an array with native values or arrays mixed with inner objects. In this case, we ignore these values. Note that if is a simple array with native values, the code above was executed bc the can_be_converted_assoc is == true. Note that if is an array with other inner arrays, this is invalid and the user should set multiple datasets separately.
				}
			}
			
			return new_data; //it can only be an object (associative array) or a numeric array.
		},
		
		sortDataSetDataLikeOthers: function(data, data_sets) {
			if (MyWidgetResourceLib.fn.isPlainObject(data)) {
				//find data set with similar data
				var similar_data = null;
				var similar_data_count = 0;
				
				for (var i = 0, t = data_sets.length; i < t; i++) {
					var other_data = data_sets[i].data;
					
					if (MyWidgetResourceLib.fn.isPlainObject(other_data)) {
						var count = 0;
						
						for (var k in other_data)
							if (data.hasOwnProperty(k))
								count++;
						
						if (count > similar_data_count) {
							similar_data = other_data;
							similar_data_count = count;
						}
					}
				}
				
				if (similar_data) {
					var new_data = {};
					
					for (var k in similar_data)
						if (data.hasOwnProperty(k))
							new_data[k] = data[k];
					
					for (var k in data)	
						if (!similar_data.hasOwnProperty(k))
							new_data[k] = data[k];
					
					data = new_data;
				}
			}
			
			return data;
		},
		
		includeChartScript: function(win) {
			//console.log(win);
			
			if (typeof Chart != "function") {
				var doc = win.document;
				var scripts = doc.scripts ? doc.scripts : doc.getElementsByTagName('script');
				var exists = false;
				
				if (scripts) {
					var script = null;
					
					for (var i = 0, t = scripts.length; i < t; i++) {
						script = scripts[i];
						
						if (script.src && (script.src == this.remote_chart_js_url || script.src == this.chart_js_url)) {
							exists = true;
							break;
						}
					}
				}
				
				if (!exists && !win.adding_chart_js_script && (this.chart_js_url || this.remote_chart_js_url)) {
					win.adding_chart_js_script = true;
					
					var new_script = doc.createElement("script");
					new_script.src = this.chart_js_url && this.chart_js_url != this.remote_chart_js_url ? this.chart_js_url : this.remote_chart_js_url;
					
					doc.body.appendChild(new_script);
					
					if (this.chart_js_url && this.chart_js_url != this.remote_chart_js_url && this.remote_chart_js_url)
						new_script.onerror = function() {
							var remote_script = doc.createElement("script");
							remote_script.src = MyWidgetResourceLib.ChartHandler.remote_chart_js_url;
							
							doc.body.insertBefore(remote_script, new_script);
							doc.body.removeChild(new_script);
						};
				}
			}
		}
	});
	/****************************************************************************************
	 *				 END: CHART FUNCTIONS 					*
	 ****************************************************************************************/
	
	/****************************************************************************************
	 *				 START: FULL CALENDAR FUNCTIONS 					*
	 ****************************************************************************************/
	MyWidgetResourceLib.CalendarHandler = MyWidgetResourceLib.fn.CalendarHandler = ({
		
		remote_calendar_js_url: "https://a.fullcalendar.io/script.js",
		calendar_js_url: null, //init when the MyWidgetResourceLib is inited
		prompt_add_event_question: "Event title:",
		prompt_remove_event_question: 'Remove event?',
		
		loadCalendarResource: function(elm, opts) {
			if (elm) {
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var perms = MyWidgetResourceLib.PermissionHandler.getWidgetPermissions(elm);
				
				if (!perms["remove"]) {
					MyWidgetResourceLib.CalendarHandler.drawCalendar(elm, null, opts);
					
					return true;
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No select element in loadCalendarResource method!");
			
			return false;
		},
		
		//Draws a calendar inside of the current widget based in the result of a resource, defined in the data-sets of the widget properties.
		//parent_data_values is to be used when it comes from the display handler where it passed automatically an object/Array with the correspondent values. The idea is to do something similar with the drawListData and drawListDataRecursively
		drawCalendar: function(elm, parent_data_values, opts) {
			var error_message = null;
			
			if (elm) {
				var calendar = MyWidgetResourceLib.fn.getNodeElementData(elm, "calendar");
					
				if (calendar)
					MyWidgetResourceLib.CalendarHandler.refreshCalendarEvents(elm);
				else {
					MyWidgetResourceLib.CalendarHandler.showCalendarLoadingBar(elm);
					
					var win = MyWidgetResourceLib.fn.getElementWindow(elm);
					var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
					var calendar_properties = MyWidgetResourceLib.fn.isPlainObject(properties["calendar"]) ? properties["calendar"] : {};
					var include_lib = !calendar_properties.hasOwnProperty("include_lib") || calendar_properties["include_lib"];
					
					//include calendar.js, if not yet included
					if (include_lib)
						MyWidgetResourceLib.CalendarHandler.includeCalendarScript(win);
					
					if (typeof FullCalendar != "object" || typeof FullCalendar.Calendar != "function") {
						if (win.adding_calendar_js_script) {
							elm.counting_if_calendar_js_is_already_added = (elm.counting_if_calendar_js_is_already_added > 0 ? elm.counting_if_calendar_js_is_already_added : 0) + 1;
							var timeout_milli_secs = 500;
							var max_secs = 10; //10 secs
							var max_count = 10*1000/timeout_milli_secs;
							
							if (elm.counting_if_calendar_js_is_already_added > max_count)
								error_message = "MyWidgetResourceLib.js error: Script 'FullCalendar.js' took more than " + max_secs + " secs to load, so the system stopped this widget to be drawn!";
							else
								setTimeout(function() {
									MyWidgetResourceLib.CalendarHandler.drawCalendar(elm, parent_data_values, opts);
								}, timeout_milli_secs);
						}
						else
							error_message = "MyWidgetResourceLib.js error: Script 'FullCalendar.js' not included!";
					}
					else {
						//console.log(elm);
						//console.log(elm.counting_if_calendar_js_is_already_added);
						
						//clean elm.counting_if_calendar_js_is_already_added
						elm.counting_if_calendar_js_is_already_added = null;
						delete elm.counting_if_calendar_js_is_already_added;
						
						//draw calendar
						//console.log(properties);
						
						var business_hours = MyWidgetResourceLib.fn.isPlainObject(calendar_properties["business_hours"]) ? calendar_properties["business_hours"] : {};
						var days_of_week = business_hours["days_of_week"];
						days_of_week = typeof days_of_week == "string" ? days_of_week.replace(/\s/g, "").replace(/;/g, ",").replace(/,+/g, ",").replace(/(^,|,$)/g, "").split(",") : (
							MyWidgetResourceLib.fn.isArray(days_of_week) ? days_of_week : null
						);
						var header_toolbar = MyWidgetResourceLib.fn.isPlainObject(calendar_properties["header_toolbar"]) ? calendar_properties["header_toolbar"] : {};
						var footer_toolbar = MyWidgetResourceLib.fn.isPlainObject(calendar_properties["footer_toolbar"]) ? calendar_properties["footer_toolbar"] : {};
						var views = MyWidgetResourceLib.fn.isPlainObject(calendar_properties["views"]) ? calendar_properties["views"] : {};
						var week_numbers_calculation = calendar_properties["week_numbers_calculation"] ? ("" + calendar_properties["week_numbers_calculation"]).toLowerCase() : null;
						
						if (week_numbers_calculation != "local" && week_numbers_calculation != "ISO")
							week_numbers_calculation = MyWidgetResourceLib.fn.convertIntoFunctions(week_numbers_calculation);
						
						var calendar_settings = {
							headerToolbar: header_toolbar["display"] ? {
								left: header_toolbar.hasOwnProperty("left") ? header_toolbar["left"] : '',
								center: header_toolbar.hasOwnProperty("center") ? header_toolbar["center"] : '',
								right: header_toolbar.hasOwnProperty("right") ? header_toolbar["right"] : ''
							} : false,
							footerToolbar: footer_toolbar["display"] ? {
								left: footer_toolbar.hasOwnProperty("left") ? footer_toolbar["left"] : '',
								center: footer_toolbar.hasOwnProperty("center") ? footer_toolbar["center"] : '',
								right: footer_toolbar.hasOwnProperty("right") ? footer_toolbar["right"] : ''
							} : false,
							customButtons: {
								refresh: {
									text: 'refresh',
									click: function() {
										MyWidgetResourceLib.CalendarHandler.refreshCalendarEvents(elm);
									}
								},
							},
							timeZone: calendar_properties["time_zone"] ? calendar_properties["time_zone"] : 'local', //'UTC' or 'America/New_York' or 'Europe/Lisbon'
							locale: calendar_properties["locale"] ? calendar_properties["locale"] : null,
							initialView: calendar_properties["initial_view"] ? calendar_properties["initial_view"] : 'dayGridMonth',
							initialDate: calendar_properties["initial_date"] ? calendar_properties["initial_date"] : null,
							height: elm.style.height ? elm.style.height : null, //auto or 100% or any other css value. set height to all window without scroll
							navLinks: calendar_properties["nav_links"] ? true : false, //can click day/week names to navigate views. Days in the dayGridMonth become clickable links to timeGridWeek and timeGridDay
							selectable: calendar_properties["selectable"] ? true : false, //Allows a user to highlight multiple days or timeslots by clicking and dragging.
							selectMirror: calendar_properties["select_mirror"] ? true : false, //Whether to draw a “placeholder” event while the user is dragging.
							nowIndicator: calendar_properties["now_indicator"] ? true : false, //Whether or not to display a marker indicating the current time.
							editable: calendar_properties["editable"] ? true : false, //Determines whether the events on the calendar can be modified.
							dayMaxEvents: calendar_properties["day_max_events"] ? true : false, //In, dayGrid view, the max number of events within a given day, not counting the +more link. The rest will show up in a popover.
							allDaySlot: calendar_properties["all_day_slot"] ? true : false, //Determines if the “all-day” slot is displayed at the top of the calendar.
							//multiMonthMaxColumns: 1, // guarantee single column
							//showNonCurrentDates: true,
							//fixedWeekCount: false,
							//weekends: false,
							weekNumbers: calendar_properties["week_numbers"] ? true : false, //Determines if week numbers should be displayed on the calendar.
							weekNumbersWithinDays: calendar_properties["week_numbers"] ? true : false,
							weekNumberCalculation: week_numbers_calculation ? week_numbers_calculation : 'local',
							eventColor: calendar_properties["event_color"] ? calendar_properties["event_color"] : null, //default events color
							slotDuration: calendar_properties["slot_duration"] ? calendar_properties["slot_duration"] : "00:30:00", //20 minutes. This is how the time will be divided in the grids. to view this better go to the dayGridWeek or dayGridDay
							slotMinTime: calendar_properties["slot_min_time"] ? calendar_properties["slot_min_time"] : "00:00:00", //Determines the first time slot that will be displayed for each day.
							slotMaxTime: calendar_properties["slot_max_time"] ? calendar_properties["slot_max_time"] : "24:00:00", //Determines the last time slot that will be displayed for each day.
							
							//Emphasizes certain time slots on the calendar.
							businessHours: {
								daysOfWeek: days_of_week, //days of week. an array of zero-based day of week integers (0=Sunday)
								startTime: business_hours["start_time"] ? business_hours["start_time"] : null, // a start time (10:00 in this example)
								endTime: business_hours["end_time"] ? business_hours["end_time"] : null, // an end time (18:00 in this example)
							},
							
							//customize the button names, otherwise they'd all just say "list" in case of the lists
							views: {
								multiMonthYear: { 
									buttonText: views["multi_month_year"] && views["multi_month_year"]["title"] ? views["multi_month_year"]["title"] : 'grid year' 
								},
								dayGridMonth: { 
									buttonText: views["day_grid_month"] && views["day_grid_month"]["title"] ? views["day_grid_month"]["title"] : 'grid month',
									eventTimeFormat: { hour: 'numeric', minute: '2-digit', meridiem: false },
									displayEventTime: true,
									displayEventEnd: true,
								},
								dayGridWeek: { 
									buttonText: views["day_grid_week"] && views["day_grid_week"]["title"] ? views["day_grid_week"]["title"] : 'grid week',
									eventTimeFormat: { hour: 'numeric', minute: '2-digit', meridiem: false },
									displayEventTime: true,
									displayEventEnd: true,
								},
								dayGridDay: {
									buttonText: views["day_grid_day"] && views["day_grid_day"]["title"] ? views["day_grid_day"]["title"] : 'grid day',
									eventTimeFormat: { hour: 'numeric', minute: '2-digit', meridiem: false },
									displayEventTime: true,
									displayEventEnd: true,
								},
								timeGridWeek: {
									buttonText: views["time_grid_week"] && views["time_grid_week"]["title"] ? views["time_grid_week"]["title"] : 'time week'
								},
								timeGridDay: {
									buttonText: views["time_grid_day"] && views["time_grid_day"]["title"] ? views["time_grid_day"]["title"] : 'time day'
								},
								listYear: {
									buttonText: views["list_year"] && views["list_year"]["title"] ? views["list_year"]["title"] : 'list year'
								},
								listMonth: {
									buttonText: views["list_month"] && views["list_month"]["title"] ? views["list_month"]["title"] : 'list month'
								},
								listWeek: {
									buttonText: views["list_week"] && views["list_week"]["title"] ? views["list_week"]["title"] : 'list week'
								},
								listDay: {
									buttonText: views["list_day"] && views["list_day"]["title"] ? views["list_day"]["title"] : 'list day'
								},
								resourceTimelineYear: {
									buttonText: views["resource_time_line_year"] && views["resource_time_line_year"]["title"] ? views["resource_time_line_year"]["title"] : 'Time Line Year'
								},
								resourceTimelineMonth: {
									buttonText: views["resource_time_line_month"] && views["resource_time_line_month"]["title"] ? views["resource_time_line_month"]["title"] : 'Time Line Month'
								},
								resourceTimelineWeek: {
									buttonText: views["resource_time_line_week"] && views["resource_time_line_week"]["title"] ? views["resource_time_line_week"]["title"] : 'Time Line Week'
								},
								resourceTimelineDay: {
									buttonText: views["resource_time_line_day"] && views["resource_time_line_day"]["title"] ? views["resource_time_line_day"]["title"] : 'Time Line Day'
								},
								resourceTimeGridWeek: {
									buttonText: views["resource_time_grid_week"] && views["resource_time_grid_week"]["title"] ? views["resource_time_grid_week"]["title"] : 'Time Grid Week'
								},
								resourceTimeGridDay: {
									buttonText: views["resource_time_grid_day"] && views["resource_time_grid_day"]["title"] ? views["resource_time_grid_day"]["title"] : 'Time Grid Day'
								},
							}
						};
						
						//prepare plugins
						var toolbars = ["headerToolbar", "footerToolbar"];
						var inner_toolbars = ["left", "center", "right"];
						var plugins = [];
						
						if (calendar_settings["initialView"] && calendar_settings["initialView"].match(/,resource[a-z],/i)) {
							if (plugins.indexOf("resourceTimeGridPlugin") == -1)
								plugins.push("resourceTimeGridPlugin");
							
							if (plugins.indexOf("resourceTimelinePlugin") == -1)
								plugins.push("resourceTimelinePlugin");
						}
						
						if (plugins.length < 2)
							for (var i = 0; i < toolbars.length; i++) {
								var toolbar = toolbars[i];
								
								if (calendar_settings[toolbar])
									for (var j = 0; j < inner_toolbars.length; j++) {
										var inner_toolbar = inner_toolbars[j];
										var btns = calendar_settings[toolbar][inner_toolbar];
										
										if (btns) {
											btns = "," + btns + ",";
											var exists = btns.match(/,resource[a-z],/i);
											
											if (exists) {
												if (plugins.indexOf("resourceTimeGridPlugin") == -1)
													plugins.push("resourceTimeGridPlugin");
												
												if (plugins.indexOf("resourceTimelinePlugin") == -1)
													plugins.push("resourceTimelinePlugin");
											}
											
											if (plugins.length >= 2)
												break;
										}
									}
								
								if (plugins.length >= 2)
									break;
							}
						
						if (plugins.length > 0)
							calendar_settings["plugins"] = plugins;
						
						//get current load resources by name
						var widget_resources = MyWidgetResourceLib.ResourceHandler.getWidgetResources(elm, "load");
						var widget_resources_name = [];
						var exists_sources = false;
						var exists_resources_to_load = false;
						
						if (widget_resources)
							for (var i = 0, t = widget_resources.length; i < t; i++)
								widget_resources_name.push(widget_resources[i]["name"]);
						
						//prepare event source based in data sets
						var data_sets_props = calendar_properties["data_sets"];
						var include_all_load_resources = calendar_properties["include_all_load_resources"];
						var data_set_parse_func = MyWidgetResourceLib.fn.convertIntoFunctions(calendar_properties["data_parse"]);
						var edit_popup_id = calendar_properties["edit_popup_id"];
						var attributes_name_props = MyWidgetResourceLib.fn.isPlainObject(calendar_properties["attributes_name"]) ? calendar_properties["attributes_name"] : {};
						var pks_attribute_name = attributes_name_props["pks"];
						var title_attribute_name = attributes_name_props["title"] ? attributes_name_props["title"] : "title";
						var start_attribute_name = attributes_name_props["start"] ? attributes_name_props["start"] : "start";
						var end_attribute_name = attributes_name_props["end"] ? attributes_name_props["end"] : "end";
						var url_attribute_name = attributes_name_props["url"] ? attributes_name_props["url"] : "url";
						var resource_id_attribute_name = attributes_name_props["resource_id"] ? attributes_name_props["resource_id"] : "resource_id";
						var group_id_attribute_name = attributes_name_props["group_id"];
						var group_display_attribute_name = attributes_name_props["group_display"];
						var color_attribute_name = attributes_name_props["color"];
						
						if (MyWidgetResourceLib.fn.isPlainObject(data_sets_props))
							data_sets_props = [data_sets_props];
						
						if (!MyWidgetResourceLib.fn.isArray(data_sets_props))
							data_sets_props = [];
						
						var event_sources = [];
						
						var is_data_set_current_load_resource = function(data_type, resource_name) {
							return data_type == "resource" && (
								(!resource_name && widget_resources_name.length > 0) || //check if could be first resource
								(resource_name && widget_resources_name.indexOf(resource_name) != -1)
							);
						};
						
						//include all the defined load resources into the data_sets var with the attributes_name_props. But only for those that are not yet in the data_sets var.
						if (include_all_load_resources) {
							var widget_resources_name_in_data_sets = [];
							
							MyWidgetResourceLib.fn.each(data_sets_props, function(idx, data_set_props) {
								if (MyWidgetResourceLib.fn.isPlainObject(data_set_props) && !MyWidgetResourceLib.fn.isEmptyObject(data_set_props)) {
									if (is_data_set_current_load_resource(data_set_props["data_type"], data_set_props["data_resource_name"])) {
										var resource_name = data_set_props["data_resource_name"];
										
										if (!resource_name)
											resource_name = widget_resources_name[0];
										
										widget_resources_name_in_data_sets.push(resource_name);
									}
								}
							});
							
							for (var i = 0, t = widget_resources_name.length; i < t; i++) {
								var resource_name = widget_resources_name[i];
								
								if (widget_resources_name_in_data_sets.indexOf(resource_name) == -1)
									data_sets_props.push({
										data_type: "resource",
										data_resource_name: resource_name,
										data_pks_attrs_names: pks_attribute_name,
										data_title_attribute_name: title_attribute_name,
										data_start_attribute_name: start_attribute_name,
										data_end_attribute_name: end_attribute_name,
										data_url_attribute_name: url_attribute_name,
										data_resource_id_attribute_name: resource_id_attribute_name,
										data_group_id_attribute_name: group_id_attribute_name,
										data_group_display_attribute_name: group_display_attribute_name,
										data_color_attribute_name: color_attribute_name,
									});
							}
						}
						
						//prepare resources based in data sets
						var resources_data_sets_props = calendar_properties["resources_data_sets"];
						var new_resources_data_sets_props = [];
						var resources_data_sets_resource_names = [];
						
						if (MyWidgetResourceLib.fn.isPlainObject(resources_data_sets_props))
							resources_data_sets_props = [resources_data_sets_props];
						
						if (!MyWidgetResourceLib.fn.isArray(resources_data_sets_props))
							resources_data_sets_props = [];
						
						MyWidgetResourceLib.fn.each(resources_data_sets_props, function(idx, resources_data_set_props) {
							if (MyWidgetResourceLib.fn.isPlainObject(resources_data_set_props) && !MyWidgetResourceLib.fn.isEmptyObject(resources_data_set_props)) {
								var resource_name = resources_data_set_props["data_resource_name"];
								
								if (!resource_name)
									resource_name = widget_resources_name[0];
								
								if (resource_name) {
									resources_data_set_props["data_pks_attrs_names"] = resources_data_set_props["data_pks_attrs_names"] ? resources_data_set_props["data_pks_attrs_names"] : pks_attribute_name;
									resources_data_set_props["data_title_attribute_name"] = resources_data_set_props["data_title_attribute_name"] ? resources_data_set_props["data_title_attribute_name"] : title_attribute_name;
									resources_data_set_props["data_color_attribute_name"] = resources_data_set_props["data_color_attribute_name"] ? resources_data_set_props["data_color_attribute_name"] : color_attribute_name;
									
									new_resources_data_sets_props.push(resources_data_set_props);
									resources_data_sets_resource_names.push(resource_name);
								}
							}
						});
						
						resources_data_sets_props = new_resources_data_sets_props;
						
						if (resources_data_sets_props.length > 0)
							exists_resources_to_load = true;
						
						//prepare resources data-sets that are not in events data-sets
						var unique_resources_data_sets_indexes = [];
						
						MyWidgetResourceLib.fn.each(resources_data_sets_props, function(idx, resources_data_set_props) {
							if (MyWidgetResourceLib.fn.isPlainObject(resources_data_set_props) && !MyWidgetResourceLib.fn.isEmptyObject(resources_data_set_props)) {
								var exists = false;
								
								MyWidgetResourceLib.fn.each(data_sets_props, function(idy, data_set_props) {
									if (MyWidgetResourceLib.fn.isPlainObject(data_set_props) && !MyWidgetResourceLib.fn.isEmptyObject(data_set_props)) {
										var is_resource = data_set_props["data_type"] != "parent"; //data_set_props["data_type"] == "resource"
										
										if (is_resource && data_set_props["data_resource_name"] == resources_data_set_props["data_resource_name"]) {
											exists = true;
											return false;
										}
									}
								});
								
								if (!exists) {
									var resource_name = resources_data_set_props["data_resource_name"];
									
									//check if exists defined in the load resources of the widget
									exists = (!resource_name && widget_resources_name.length > 0) || //check if could be first resource
											(resource_name && widget_resources_name.indexOf(resource_name) != -1);
									
									if (!exists)
										unique_resources_data_sets_indexes.push(idx);
								}
							}
						});
						
						//prepare handlers
						var get_resource_handler = function(item, resources_data_set_props) {
							var data_pks_attrs_names = resources_data_set_props["data_pks_attrs_names"];
							var data_title_attribute_name = resources_data_set_props["data_title_attribute_name"] ? resources_data_set_props["data_title_attribute_name"] : "title";
							var data_children_attribute_name = resources_data_set_props["data_children_attribute_name"] ? resources_data_set_props["data_children_attribute_name"] : "children";
							var data_allow_events_attribute_name = resources_data_set_props["data_allow_events_attribute_name"];
							var data_color_attribute_name = resources_data_set_props["data_color_attribute_name"];
							var background_color = resources_data_set_props["background_color"];
							var text_color = resources_data_set_props["text_color"];
							
							var r = {
								data: item,
							};
							
							//prepare pks
							if (data_pks_attrs_names) {
								data_pks_attrs_names = MyWidgetResourceLib.fn.isArray(data_pks_attrs_names) ? data_pks_attrs_names : data_pks_attrs_names.replace(/;/g, ",").split(",");
								
								var pks_attrs = {};
								var id = "";
								
								for (var i = 0, t = data_pks_attrs_names.length; i < t; i++) {
									var pk_attr_name = ("" + data_pks_attrs_names[i]).replace(/\s+/g, "");
									
									if (pk_attr_name && (!pks_attrs.hasOwnProperty(pk_attr_name) || pks_attrs[pk_attr_name] === null)) {
										pks_attrs[pk_attr_name] = item.hasOwnProperty(pk_attr_name) ? item[pk_attr_name] : null;
										id += (id ? "_" : "") + pks_attrs[pk_attr_name];
									}
								}
								
								if (!MyWidgetResourceLib.fn.isEmptyObject(pks_attrs)) {
									event["data_pks_attrs_names"] = data_pks_attrs_names;
									r["pks_attrs"] = pks_attrs;
									r["id"] = id;
								}
							}
							
							//prepare other resource properties
							if (data_title_attribute_name && item.hasOwnProperty(data_title_attribute_name) && (
								item[data_title_attribute_name] || MyWidgetResourceLib.fn.isNumeric(item[data_title_attribute_name])
							))
								r["title"] = item[data_title_attribute_name];
							
							if (data_color_attribute_name && item[data_color_attribute_name])
								r["eventColor"] = item[data_color_attribute_name];
							
							if (!r["eventColor"] && background_color)
								r["eventBackgroundColor"] = background_color;
							
							if (text_color)
								r["eventTextColor"] = text_color;
							
							if (item.hasOwnProperty(data_children_attribute_name) && item[data_children_attribute_name] && (MyWidgetResourceLib.fn.isArray(item[data_children_attribute_name]) || MyWidgetResourceLib.fn.isPlainObject(item[data_children_attribute_name]))) {
								var children = [];
								
								MyWidgetResourceLib.fn.each(item[data_children_attribute_name], function(idx, child) {
									var child_r = get_resource_handler(child, resources_data_set_props);
									children.push(child_r);
								});
								
								r["children"] = children;
							}
							
							if (data_allow_events_attribute_name && item.hasOwnProperty(data_allow_events_attribute_name) && !item[data_allow_events_attribute_name])
								r["eventAllow"] = false;
							
							//console.log(data_allow_events_attribute_name);
							//console.log(item);
							//console.log(r);
							return r;
						};
						
						var repeated_resources_data_sets = [];
						var add_calendar_resources_sources = function(failureCallback) {
							//add resources sources
							MyWidgetResourceLib.fn.each(resources_data_sets_props, function(idx, resources_data_set_props) {
								if (repeated_resources_data_sets.indexOf(idx) == -1 && MyWidgetResourceLib.fn.isPlainObject(resources_data_set_props) && !MyWidgetResourceLib.fn.isEmptyObject(resources_data_set_props)) {
									var resource_name = resources_data_set_props["data_resource_name"];
									
									if (!resource_name)
										resource_name = widget_resources_name[0];
									
									if (resource_name) {
										repeated_resources_data_sets.push(idx);
										
										var data = MyWidgetResourceLib.ResourceHandler.getLoadedSLAResult(resource_name);
										
										if (data) {
											var data_set_parse_func = MyWidgetResourceLib.fn.convertIntoFunctions(resources_data_set_props["data_parse"]);
											
											if (data_set_parse_func)
												data = MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(data_set_parse_func, elm, data);
											
											if (data === null || data === undefined)
												failureCallback("Invalid events data");
											else {
												var calendar = MyWidgetResourceLib.fn.getNodeElementData(elm, "calendar");
												
												MyWidgetResourceLib.fn.each(data, function(idx, item) {
													if (MyWidgetResourceLib.fn.isPlainObject(item) && !MyWidgetResourceLib.fn.isEmptyObject(item)) {
														var r = get_resource_handler(item, resources_data_set_props);
														calendar.addResource(r);
													}
												});
											}
										}
									}
								}
							});
						};
						
						var get_event_handler = function(item, data_set_props) {
							var data_popup_id = data_set_props["data_popup_id"] ? data_set_props["data_popup_id"] : edit_popup_id;
							var data_pks_attrs_names = data_set_props["data_pks_attrs_names"] ? data_set_props["data_pks_attrs_names"] : pks_attribute_name;
							var data_title_attribute_name = data_set_props["data_title_attribute_name"] ? data_set_props["data_title_attribute_name"] : title_attribute_name;
							var data_start_attribute_name = data_set_props["data_start_attribute_name"] ? data_set_props["data_start_attribute_name"] : start_attribute_name;
							var data_end_attribute_name = data_set_props["data_end_attribute_name"] ? data_set_props["data_end_attribute_name"] : end_attribute_name;
							var data_url_attribute_name = data_set_props["data_url_attribute_name"] ? data_set_props["data_url_attribute_name"] : url_attribute_name;
							var data_resource_id_attribute_name = data_set_props["data_resource_id_attribute_name"] ? data_set_props["data_resource_id_attribute_name"] : resource_id_attribute_name;
							var data_group_id_attribute_name = data_set_props["data_group_id_attribute_name"] ? data_set_props["data_group_id_attribute_name"] : group_id_attribute_name;
							var data_group_display_attribute_name = data_set_props["data_group_display_attribute_name"] ? data_set_props["data_group_display_attribute_name"] : group_display_attribute_name;
							var data_color_attribute_name = data_set_props["data_color_attribute_name"] ? data_set_props["data_color_attribute_name"] : color_attribute_name;
							var background_color = data_set_props["background_color"];
							var text_color = data_set_props["text_color"];
							
							var event = {
								start: item[data_start_attribute_name],
								end: item[data_end_attribute_name],
								data: item,
								start_attr_name: data_start_attribute_name,
								end_attr_name: data_end_attribute_name,
								resource_id_attr_name: data_resource_id_attribute_name,
								popup_id: data_popup_id
							};
							
							//prepare pks
							if (data_pks_attrs_names) {
								data_pks_attrs_names = MyWidgetResourceLib.fn.isArray(data_pks_attrs_names) ? data_pks_attrs_names : data_pks_attrs_names.replace(/;/g, ",").split(",");
								
								var pks_attrs = {};
								var id = "";
								
								for (var i = 0, t = data_pks_attrs_names.length; i < t; i++) {
									var pk_attr_name = ("" + data_pks_attrs_names[i]).replace(/\s+/g, "");
									
									if (pk_attr_name && (!pks_attrs.hasOwnProperty(pk_attr_name) || pks_attrs[pk_attr_name] === null)) {
										pks_attrs[pk_attr_name] = item.hasOwnProperty(pk_attr_name) ? item[pk_attr_name] : null;
										id += (id ? "_" : "") + pks_attrs[pk_attr_name];
									}
								}
								
								if (!MyWidgetResourceLib.fn.isEmptyObject(pks_attrs)) {
									event["data_pks_attrs_names"] = data_pks_attrs_names;
									event["pks_attrs"] = pks_attrs;
									event["id"] = id;
								}
							}
							
							//prepare other event properties
							if (data_title_attribute_name && item.hasOwnProperty(data_title_attribute_name) && (
								item[data_title_attribute_name] || MyWidgetResourceLib.fn.isNumeric(item[data_title_attribute_name])
							))
								event["title"] = item[data_title_attribute_name];
							
							if (data_url_attribute_name && item[data_url_attribute_name])
								event["url"] = item[data_url_attribute_name];
							
							if (data_group_id_attribute_name && item.hasOwnProperty(data_group_id_attribute_name) && (
								item[data_group_id_attribute_name] || MyWidgetResourceLib.fn.isNumeric(item[data_group_id_attribute_name])
							)) {
								event["groupId"] = item[data_group_id_attribute_name];
							
								if (data_group_display_attribute_name && item[data_group_display_attribute_name])
									event["display"] = item[data_group_display_attribute_name];
							}
							
							if (data_color_attribute_name && item[data_color_attribute_name])
								event["color"] = item[data_color_attribute_name];
							
							if (!event["color"] && background_color)
								event["backgroundColor"] = background_color;
							
							if (text_color)
								event["textColor"] = text_color;
							
							if (data_resource_id_attribute_name && item.hasOwnProperty(data_resource_id_attribute_name) && (
								item[data_resource_id_attribute_name] || MyWidgetResourceLib.fn.isNumeric(item[data_resource_id_attribute_name])
							))
								event["resourceId"] = item[data_resource_id_attribute_name];
							
							//console.log(event);
							return event;
						};
						
						var parse_event_data_handler = function(data_set_props, data, successCallback, failureCallback) {
							var events_data_set_parse_func = MyWidgetResourceLib.fn.convertIntoFunctions(data_set_props["data_parse"]);
							
							if (!events_data_set_parse_func)
								events_data_set_parse_func = data_set_parse_func;
							
							//prepare events based in data
							if (data) {
								var events = [];
								
								MyWidgetResourceLib.fn.each(data, function(idx, item) {
									if (MyWidgetResourceLib.fn.isPlainObject(item) && !MyWidgetResourceLib.fn.isEmptyObject(item)) {
										var event = get_event_handler(item, data_set_props);
										events.push(event);
									}
								});
								
								data = events;
							}
							
							if (events_data_set_parse_func)
								data = MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(events_data_set_parse_func, elm, data);
							
							if (data === null || data === undefined)
								failureCallback("Invalid events data");
							else {
								//console.log(data);
								//console.log(successCallback);
								
								successCallback(data);
							}
						};
						
						var get_event_source_handler = function(data_set_props) {
							//getting properties
							var data_type = data_set_props["data_type"]; //resource or parent
							var resource_name = data_set_props["data_resource_name"];
							var background_color = data_set_props["background_color"];
							var text_color = data_set_props["text_color"];
							
							return {
								events: function(info, successCallback, failureCallback) {
									if (data_type == "parent")
										parse_event_data_handler(data_set_props, parent_data_values, successCallback, failureCallback);
									else { //if (data_type == "resource")
										if (!resource_name) { //get parent first resource
											var parent_widget = elm.closest("[data-widget-resources]");
											
											if (parent_widget) {
												var parent_resources = MyWidgetResourceLib.ResourceHandler.getWidgetResources(parent_widget, "load");
												
												if (parent_resources && parent_resources.length > 0 && parent_resources[0]["name"])
													resource_name = parent_resources[0]["name"];
											}
										}
										
										if (resource_name) {
											var get_resource_handler = function() {
												//console.log(JSON.stringify(MyWidgetResourceLib.ResourceHandler.requesting_urls));
												
												if (MyWidgetResourceLib.ResourceHandler.existLoadedSLAResult(resource_name)) {
													//add calendar resources
													if (resources_data_sets_resource_names.indexOf(resource_name) != -1)
														add_calendar_resources_sources(failureCallback);
													
													//add calendar events
													data = MyWidgetResourceLib.ResourceHandler.getLoadedSLAResult(resource_name);
													parse_event_data_handler(data_set_props, data, successCallback, failureCallback);
												}
												else if (MyWidgetResourceLib.ResourceHandler.isWidgetResourceNameExecuting(elm, resource_name)) {
													//console.log("LOADING resource_name:"+resource_name);
													
													setTimeout(function() { //Note that this can run infinitly. However to avoid this case, the developer needs to set a connection timeout in the correspondent load resource, through the before callback.
														get_resource_handler();
													}, 500);
												}
												else {
													//console.log("NULL resource_name:"+resource_name);
													parse_event_data_handler(data_set_props, null, successCallback, failureCallback);
												}
											};
											
											get_resource_handler();
										}
										else
											parse_event_data_handler(data_set_props, null, successCallback, failureCallback);
									}
								},
								color: background_color ? background_color : null,
								textColor: text_color ? text_color : null
							};
						};
						
						//prepare calendar resources sources that are not included in events data-sets
						for (var i = 0, t = unique_resources_data_sets_indexes.length; i < t; i++) {
							var idx = unique_resources_data_sets_indexes[i];
							var resources_data_set_props = resources_data_sets_props[idx];
							
							var event_source = get_event_source_handler(resources_data_set_props);
							event_sources.push(event_source);
						}
						
						//prepare data sets - parent data-sets
						MyWidgetResourceLib.fn.each(data_sets_props, function(idx, data_set_props) {
							if (MyWidgetResourceLib.fn.isPlainObject(data_set_props) && !MyWidgetResourceLib.fn.isEmptyObject(data_set_props)) {
								exists_sources = true;
								
								if (!is_data_set_current_load_resource(data_set_props["data_type"], data_set_props["data_resource_name"])) {
									var event_source = get_event_source_handler(data_set_props);
									event_sources.push(event_source);
								}
								else
									exists_resources_to_load = true;
							}
						});
						
						//prepare data sets - widget resources to be loaded
						if (exists_resources_to_load)
							event_sources.push({
								events: function(info, successCallback, failureCallback) {
									//load widget resources
									var resource_opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? MyWidgetResourceLib.fn.assignObjectRecursively({}, opts) : {};
									var complete_handler = resource_opts["complete"] ? resource_opts["complete"] : null;
									var error_handler = resource_opts["error"] ? resource_opts["error"] : null;
									
									//prepare resources_options with search_attrs
									resource_opts["resources_options"] = {};
									
									MyWidgetResourceLib.fn.each(data_sets_props, function(idx, data_set_props) {
										if (MyWidgetResourceLib.fn.isPlainObject(data_set_props) && !MyWidgetResourceLib.fn.isEmptyObject(data_set_props)) {
											if (is_data_set_current_load_resource(data_set_props["data_type"], data_set_props["data_resource_name"])) {
												var resource_name = data_set_props["data_resource_name"];
												var data_start_attribute_name = data_set_props["data_start_attribute_name"] ? data_set_props["data_start_attribute_name"] : start_attribute_name;
												var data_end_attribute_name = data_set_props["data_end_attribute_name"] ? data_set_props["data_end_attribute_name"] : end_attribute_name;
												
												if (!resource_name)
													resource_name = widget_resources_name[0];
												
												var resource_search_attrs = {};
												resource_search_attrs[data_start_attribute_name] = {
													value: info.startStr.substr(0, 19),
													operator: ">="
												};
												resource_search_attrs[data_end_attribute_name] = {
													value: info.endStr.substr(0, 19),
													operator: "<"
												};
												
												resource_opts["resources_options"][resource_name] = {
													extra_url: "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(resource_search_attrs, "search_attrs")
												};
											}
										}
									});
									
									//prepare callbacks
									resource_opts["complete"] = function(elm, resources_name, resources_cache_key) {
										var resource_events = [];
										var success_func = function(data) {
											MyWidgetResourceLib.fn.each(data, function(idx, item) {
												resource_events.push(item);
											});
										};
										var failure_msg = null;
										var failure_func = function(msg) {
											failure_enter = msg;
										};
										
										//add resources sources
										add_calendar_resources_sources(failureCallback);
										
										//add event sources
										MyWidgetResourceLib.fn.each(data_sets_props, function(idx, data_set_props) {
											if (MyWidgetResourceLib.fn.isPlainObject(data_set_props) && !MyWidgetResourceLib.fn.isEmptyObject(data_set_props)) {
												if (is_data_set_current_load_resource(data_set_props["data_type"], data_set_props["data_resource_name"])) {
													var resource_name = data_set_props["data_resource_name"];
													
													if (!resource_name)
														resource_name = widget_resources_name[0];
													
													var data = MyWidgetResourceLib.ResourceHandler.getLoadedSLAResult(resource_name);
													parse_event_data_handler(data_set_props, data, success_func, failure_func);
												}
											}
										});
										
										if (failure_msg)
											failureCallback(failure_msg);
										else
											successCallback(resource_events);
										
										//call complete, if exists
										complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
										
										if (complete_handler)
											MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, data);
									};
									
									resource_opts["error"] = function(jqXHR, text_status, error_thrown) {
										//call success, if exists
										error_handler = MyWidgetResourceLib.fn.convertIntoFunctions(error_handler);
										
										if (error_handler)
											MyWidgetResourceLib.fn.executeFunctions(error_handler, elm, jqXHR, text_status, error_thrown);
										
										failureCallback("Invalid events data"); //execute the failureCallback without message
									};
									
									//load widget resources
									MyWidgetResourceLib.FieldHandler.loadFieldResource(elm, resource_opts);
								}
							});
						
						//prepare events callbacks
						var callbacks = MyWidgetResourceLib.fn.isPlainObject(calendar_properties["callbacks"]) ? calendar_properties["callbacks"] : {};
						var select_func = MyWidgetResourceLib.fn.convertIntoFunctions(callbacks["select"]);
						var click_func = MyWidgetResourceLib.fn.convertIntoFunctions(callbacks["click"]);
						var resize_func = MyWidgetResourceLib.fn.convertIntoFunctions(callbacks["resize"]);
						var move_func = MyWidgetResourceLib.fn.convertIntoFunctions(callbacks["move"]);
						var hover_func = MyWidgetResourceLib.fn.convertIntoFunctions(callbacks["hover"]);
						var out_func = MyWidgetResourceLib.fn.convertIntoFunctions(callbacks["out"]);
						var mount_func = MyWidgetResourceLib.fn.convertIntoFunctions(callbacks["mount"]);
						var unmount_func = MyWidgetResourceLib.fn.convertIntoFunctions(callbacks["unmount"]);
						var add_func = MyWidgetResourceLib.fn.convertIntoFunctions(callbacks["add"]);
						var remove_func = MyWidgetResourceLib.fn.convertIntoFunctions(callbacks["remove"]);
						var window_resize_func = MyWidgetResourceLib.fn.convertIntoFunctions(callbacks["window_resize"]);
						var show_loading_func = MyWidgetResourceLib.fn.convertIntoFunctions(callbacks["show_loading"]);
						var hide_loading_func = MyWidgetResourceLib.fn.convertIntoFunctions(callbacks["hide_loading"]);
						var parse_func = MyWidgetResourceLib.fn.convertIntoFunctions(callbacks["parse"]);
						
						if (select_func)
							calendar_settings.select = function(arg) {
								MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(select_func, elm, calendar_settings, arg);
							};
						
						if (click_func)
							calendar_settings.eventClick = function(arg) {
								MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(click_func, elm, calendar_settings, arg);
								
								//console.log("on click event:"+arg.event.groupId+":"+arg.event.title);
							};
						
						if (resize_func)
							calendar_settings.eventResize = function(arg) {
								MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(resize_func, elm, calendar_settings, arg);
								
								//console.log("on resize event:"+arg.event.groupId+":"+arg.event.title);
							};
						
						if (move_func)
							calendar_settings.eventDrop = function(arg) {
								MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(move_func, elm, calendar_settings, arg);
								
								//console.log("on move event:"+arg.event.groupId+":"+arg.event.title);
							};
						
						if (hover_func)
							calendar_settings.eventMouseEnter = function(arg) {
								MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(hover_func, elm, calendar_settings, arg);
								
								//console.log("on mouse hover event:"+arg.event.groupId+":"+arg.event.title);
							};
						
						if (out_func)
							calendar_settings.eventMouseLeave = function(arg) {
								MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(out_func, elm, calendar_settings, arg);
								
								//console.log("on mouse out event:"+arg.event.groupId+":"+arg.event.title);
							};
						
						if (mount_func)
							calendar_settings.eventDidMount = function(arg) {
								MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(mount_func, elm, calendar_settings, arg);
								
								//console.log("on mount event:"+arg.event.groupId+":"+arg.event.title);
							};
						
						if (unmount_func)
							calendar_settings.eventWillUnmount = function(arg) {
								MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(unmount_func, elm, calendar_settings, arg);
								
								//console.log("on unmount event:"+arg.event.groupId+":"+arg.event.title);
							};
						
						if (add_func)
							calendar_settings.eventAdd = function(arg) {
								MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(add_func, elm, calendar_settings, arg);
								
								//console.log("on add event:"+arg.event.groupId+":"+arg.event.title);
							};
						
						if (remove_func)
							calendar_settings.eventRemove = function(arg) {
								MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(remove_func, elm, calendar_settings, arg);
								
								//console.log("on remove event:"+arg.event.groupId+":"+arg.event.title);
							};
						
						if (window_resize_func)
							calendar_settings.windowResize = function(arg) {
								MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(window_resize_func, elm, calendar_settings, arg);
								
								//console.log("on window resize:"+arg.view.type);
							};
						
						calendar_settings.loading = function(is_loading) {
							if (is_loading)
								MyWidgetResourceLib.CalendarHandler.showCalendarLoadingBar(elm);
							else
								MyWidgetResourceLib.CalendarHandler.hideCalendarLoadingBar(elm);
							
							//call callbacks
							if (show_loading_func && is_loading)
								MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(show_loading_func, elm, calendar_settings);
							else if (hide_loading_func && !is_loading)
								MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(hide_loading_func, elm, calendar_settings);
							
							//console.log("on loading:"+is_loading);
						};
						
						if (parse_func)
							calendar_settings = MyWidgetResourceLib.fn.executeFunctionsAndReturnResult(parse_func, elm, calendar_settings);
						
						//console.log(calendar_settings);
						
						//create calendar
						var calendar = new FullCalendar.Calendar(elm, calendar_settings);
						MyWidgetResourceLib.fn.setNodeElementData(elm, "calendar", calendar);
						
						//only add event_sources here, bc we need to access the 'getNodeElementData(elm, "calendar")' in the code above, and if we add the event_sources directly in the calendar_settings.eventSources, the calendar variable may not created yet. So we must add the event_sources her, after the calendar var be initiated.
						if (event_sources.length > 0)
							for (var i = 0; i < event_sources.length; i++)
								calendar.addEventSource(event_sources[i]);
						
						calendar.render();
						
						if (!exists_sources)
							MyWidgetResourceLib.CalendarHandler.hideCalendarLoadingBar(elm);
					}
				}
			} 
			else
				error_message = "FullCalendar.js error: Widget cannot be undefined!";
			
			if (error_message && console && console.log) {
				try {
					throw new Error(error_message + " Please check your javascript code.");
				}
				catch(e) {
					console.log(e);
				}
			}
		},
		
		includeCalendarScript: function(win) {
			//console.log(win);
			
			if (typeof FullCalendar != "function") {
				var doc = win.document;
				var scripts = doc.scripts ? doc.scripts : doc.getElementsByTagName('script');
				var exists = false;
				
				if (scripts) {
					var script = null;
					
					for (var i = 0, t = scripts.length; i < t; i++) {
						script = scripts[i];
						
						if (script.src && (script.src == this.remote_calendar_js_url || script.src == this.calendar_js_url)) {
							exists = true;
							break;
						}
					}
				}
				
				if (!exists && !win.adding_calendar_js_script && (this.calendar_js_url || this.remote_calendar_js_url)) {
					win.adding_calendar_js_script = true;
					
					var new_script = doc.createElement("script");
					new_script.src = this.calendar_js_url && this.calendar_js_url != this.remote_calendar_js_url ? this.calendar_js_url : this.remote_calendar_js_url;
					
					doc.body.appendChild(new_script);
					
					if (this.calendar_js_url && this.calendar_js_url != this.remote_calendar_js_url && this.remote_calendar_js_url)
						new_script.onerror = function() {
							var remote_script = doc.createElement("script");
							remote_script.src = MyWidgetResourceLib.CalendarHandler.remote_calendar_js_url;
							
							doc.body.insertBefore(remote_script, new_script);
							doc.body.removeChild(new_script);
						};
				}
			}
		},
		
		showCalendarLoadingBar: function(elm) {
			if (elm) {
				var loadings = elm.querySelectorAll("[data-widget-loading]");
				
				if (loadings)
					//TODO: find a way to loop asyncronously
					MyWidgetResourceLib.fn.each(loadings, function(idx, item) {
						MyWidgetResourceLib.fn.show(item);
					});
			}
		},
		
		hideCalendarLoadingBar: function(elm) {
			if (elm) {
				var loadings = elm.querySelectorAll("[data-widget-loading]");
				
				if (loadings)
					//TODO: find a way to loop asyncronously
					MyWidgetResourceLib.fn.each(loadings, function(idx, item) {
						MyWidgetResourceLib.fn.hide(item);
					});
			}
		},
		
		//Open a popup with an editable form with empty values.
		//The html element that will execute this function, must be the main calendar element, where the FullCalendar lib was initialized and must contain the correspondent popup id in the calendar events settings or [data-widget-popup-id] attribute.
		openCalendarEventAddPopupById: function(elm, calendar_settings, arg) {
			if (elm) {
				if (arg) {
					//prepare record with dates 
					var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
					var calendar_properties = MyWidgetResourceLib.fn.isPlainObject(properties["calendar"]) ? properties["calendar"] : {};
					var attributes_name_props = MyWidgetResourceLib.CalendarHandler.getCalendarEventAttributesName(elm);
					var start_attribute_name = attributes_name_props["start"];
					var end_attribute_name = attributes_name_props["end"];
					
					var record = {};
					var start_date = arg.startStr ? arg.startStr : (
						arg.start ? arg.start : (
							arg.event.startStr ? arg.event.startStr : arg.event.start
						)
					);
					var end_date = arg.endStr ? arg.endStr : (
						arg.end ? arg.end : (
							arg.event.endStr ? arg.event.endStr : arg.event.end
						)
					);
					var interval = MyWidgetResourceLib.CalendarHandler.getEventDateInterval(elm, start_date, end_date);
					record[start_attribute_name] = interval.start;
					record[end_attribute_name] = interval.end;
					
					//prepare popup
					var popup_id = calendar_properties["add_popup_id"] ? calendar_properties["add_popup_id"] : elm.getAttribute("data-widget-popup-id");
					var popup = popup_id ? document.querySelector("#" + popup_id) : null;
					
					if (popup) {
						MyWidgetResourceLib.FieldHandler.setWidgetDataValues(popup, record, null);
						
						MyWidgetResourceLib.PopupHandler.openFormPopupById(elm, popup_id);
					}
					else
						MyWidgetResourceLib.MessageHandler.showErrorMessage("Invalid Popup id in openCalendarEventPopupById");
				}
				else
					MyWidgetResourceLib.MessageHandler.showErrorMessage("Invalid event in openCalendarEventPopupById");
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No element in openCalendarEventPopupById method!");
		},
		
		//Get the event' values and open a popup with a readonly form with that values.
		//The html element that will execute this function, must be the main calendar element, where the FullCalendar lib was initialized and must contain the correspondent popup id in the calendar event settings or [data-widget-popup-id] attribute.
		openCalendarEventViewPopupById: function(elm, calendar_settings, arg) {
			MyWidgetResourceLib.CalendarHandler.openCalendarEventEditPopupById(elm, calendar_settings, arg);
		},
		
		//Get the event' values and open a popup with an editable form with that values.
		//The html element that will execute this function, must be the main calendar element, where the FullCalendar lib was initialized and must contain the correspondent popup id in the calendar event settings or [data-widget-popup-id] attribute.
		openCalendarEventEditPopupById: function(elm, calendar_settings, arg) {
			if (elm) {
				if (arg) {
					var event_props = arg.event.extendedProps;
					var record = event_props.data;
					var search_attrs = event_props.pks_attrs;
					
					//prepare popup
					var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
					var calendar_properties = MyWidgetResourceLib.fn.isPlainObject(properties["calendar"]) ? properties["calendar"] : {};
					var popup_id = event_props["popup_id"] ? event_props["popup_id"] : (
						calendar_properties["edit_popup_id"] ? calendar_properties["edit_popup_id"] : elm.getAttribute("data-widget-popup-id")
					);
					var popup = popup_id ? document.querySelector("#" + popup_id) : null;
					
					if (popup) {
						//set values
						MyWidgetResourceLib.FieldHandler.setWidgetDataValues(popup, record, null);
						
						//set pks attrs in forms, if exists any
						//var forms = popup.querySelectorAll("form[data-widget-form]");
						var forms = popup.querySelectorAll("form");
						
						if (forms && forms.length > 0)
							for (var i = 0, t = forms.length; i < t; i++)
								forms[i].setAttribute("data-widget-pks-attrs", JSON.stringify(search_attrs));
						
						//open popup
						MyWidgetResourceLib.PopupHandler.openFormPopupById(elm, popup_id, search_attrs);
					}
					else
						MyWidgetResourceLib.MessageHandler.showErrorMessage("Invalid Popup id in openCalendarEventPopupById");
				}
				else
					MyWidgetResourceLib.MessageHandler.showErrorMessage("Invalid event in openCalendarEventPopupById");
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No element in openCalendarEventPopupById method!");
		},
		
		//Handler to be called on add callback setting of your calendar. In summary this handler will be called when the user clicks in a calendar cell amd prompts a box so he can write the new event title. Then the system sends a request to server with the event dates, so a new record can be added.
		//Note that this function should be only used with the Calendar widgets which also have the 'add' resource defined.
		addResourceCalendarEvent: function(elm, calendar_settings, arg, opts) {
			var title = prompt(MyWidgetResourceLib.CalendarHandler.prompt_add_event_question);
       		
       		if (title) {
				arg = MyWidgetResourceLib.CalendarHandler.getCalendarArgWithNewEvent(elm, calendar_settings, arg, title);
				//console.log(arg);
				
				//prepare complete handler
				opts = opts ? opts : {};
				var complete_handler = opts["complete"] ? opts["complete"] : null;
				
				opts["complete"] = function(elm, resources_name, resources_cache_key) {
					complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
					
					if (complete_handler)
						MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
					
					delete arg.event["extendedProps"];
					MyWidgetResourceLib.CalendarHandler.addCalendarEvent(elm, calendar_settings, arg);
				};
				
				MyWidgetResourceLib.CalendarHandler.executeResourceCalendarEventAction(elm, calendar_settings, arg, "add", true, opts);
			}
		},
		
		//Handler to be called on 'resize' or 'move' callbacks settings of your calendar. In summary this handler will be called when the user resizes or moves an event. Then the system gets values of the current event and sends a request to server with the event dates, so the correspondent record be updated.
		//Note that this function should be only used with the Calendar widgets which also have the 'update' resource defined.
		updateResourceCalendarEvent: function(elm, calendar_settings, arg, opts) {
			MyWidgetResourceLib.CalendarHandler.executeResourceCalendarEventAction(elm, calendar_settings, arg, "update", false, opts);
		},
		
		//Handler to be called on 'remove' callback setting of your calendar. In summary this handler will be called when the user removes an event. Then the system gets values of the current event and sends a request to server with the event dates, so the correspondent record be removed.
		//Note that this function should be only used with the Calendar widgets which also have the 'remove' resource defined.
		removeResourceCalendarEvent: function(elm, calendar_settings, arg, opts) {
			opts = opts ? opts : {};
			var complete_handler = opts["complete"] ? opts["complete"] : null;
			
			opts["complete"] = function(elm, resources_name, resources_cache_key) {
				complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
				
				if (complete_handler)
					MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
				
				MyWidgetResourceLib.CalendarHandler.removeCalendarEvent(elm, calendar_settings, arg);
			};
			
			MyWidgetResourceLib.CalendarHandler.executeResourceCalendarEventAction(elm, calendar_settings, arg, "update", false, opts);
		},
		
		executeResourceCalendarEventAction: function(elm, calendar_settings, arg, resource_key, is_new_record , opts) {
			if (elm) {
				var perms = MyWidgetResourceLib.PermissionHandler.getWidgetPermissions(elm);
				
				if (!perms["remove"] && MyWidgetResourceLib.FieldHandler.isResourceFieldActionConfirmed(elm)) {
					if (arg) {
						var event_props = arg.event.extendedProps;
						var record = event_props.data;
						var pks_attrs = event_props.pks_attrs;
						
						if (record) {
							if (!is_new_record && !pks_attrs)
								MyWidgetResourceLib.MessageHandler.showErrorMessage("Primary keys cannot be undefined in executeResourceCalendarEventAction method.");
							else {
								var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
								
								//update record with new dates
								if (MyWidgetResourceLib.fn.isPlainObject(record)) {
									var attributes_name_props = MyWidgetResourceLib.CalendarHandler.getCalendarEventAttributesName(elm);
									var data_start_attribute_name = attributes_name_props["start"];
									var data_end_attribute_name = attributes_name_props["end"];
									var data_resource_id_attribute_name = attributes_name_props["resource_id"];
									var start_date = null;
									var end_date = null;
									
									start_date = arg.startStr ? arg.startStr : (
										arg.start ? arg.start : (
											arg.event.startStr ? arg.event.startStr : arg.event.start
										)
									);
									end_date = arg.endStr ? arg.endStr : (
										arg.end ? arg.end : (
											arg.event.endStr ? arg.event.endStr : arg.event.end
										)
									);
									
									if (start_date && end_date) {
										record[data_start_attribute_name] = start_date.substr(0, 19);
										record[data_end_attribute_name] = end_date.substr(0, 19);
									}
									
									if (data_resource_id_attribute_name && arg.newResource && arg.newResource.id)
										record[data_resource_id_attribute_name] = arg.newResource.id;
								}
								
								//prepare post data
								var post_data = {
									conditions: pks_attrs,
									attributes: record
								};
								
								var end_handler = MyWidgetResourceLib.fn.isPlainObject(properties["end"]) ? properties["end"][resource_key] : properties["end"];
								end_handler = MyWidgetResourceLib.fn.convertIntoFunctions(end_handler);
								
								var complete_func = function(resource_elm, resources, resources_name, resources_cache_key) {
									//call complete handler
									var complete_handler = MyWidgetResourceLib.fn.isPlainObject(properties["complete"]) ? properties["complete"][resource_key] : properties["complete"];
									complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
									
									if (complete_handler)
										MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
									
									//call complete handler from opts if exists
									var complete_handler = opts && opts["complete"] ? opts["complete"] : null;
									complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
									
									if (complete_handler)
										MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
									
									//reload dependent widgets
									var dependent_widgets_id = MyWidgetResourceLib.fn.prepareDependentWidgetsId(properties["dependent_widgets_id"]);
									
									opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? opts : {};
									opts["purge_cache"] = true;
									
									MyWidgetResourceLib.fn.loadDependentWidgetsById(dependent_widgets_id, {}, opts);
									
									//call end handler
									if (end_handler)
										MyWidgetResourceLib.fn.executeFunctions(end_handler, elm);
								};
								
								var resource_opts = {
									ignore_empty_resource: true,
									post_data: post_data, 
									label: "event", 
									complete: complete_func,
								};
								var resource = MyWidgetResourceLib.ResourceHandler.executeSetWidgetResourcesRequest(elm, resource_key, resource_opts);
								
								if (!resource) {
									MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No " + resource_key + " resource set in executeResourceCalendarEventAction method. Please inform the web-developer accordingly...");
									
									if (end_handler)
										MyWidgetResourceLib.fn.executeFunctions(end_handler, elm);
								}
							}
						}
						else
							MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No record data in executeResourceCalendarEventAction method!");
					}
					else
						MyWidgetResourceLib.MessageHandler.showErrorMessage("Invalid event in executeResourceCalendarEventAction");
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No element in executeResourceCalendarEventAction method!");
		},
		
		//Handler to be called on add callback setting of your calendar. In summary this handler will be called when the user clicks in a calendar cell amd prompts a box so he can write the new event title. Then the system adds a new static event in the calendar, without connecting with the server. This is done only in the client side.
		//Note that this function should be only used with the Calendar widgets which also have the 'add' resource defined.
		addStaticCalendarEvent: function(elm, calendar_settings, arg) {
			var title = prompt(MyWidgetResourceLib.CalendarHandler.prompt_add_event_question);
       		
       		if (title) {
				arg = MyWidgetResourceLib.CalendarHandler.getCalendarArgWithNewEvent(elm, calendar_settings, arg, title);
				delete arg.event["extendedProps"];
				
				MyWidgetResourceLib.CalendarHandler.addCalendarEvent(elm, calendar_settings, arg);
			}
		},
		
		//Handler to be called on 'remove' callback setting of your calendar. In summary this handler will be called to remove an event. However the system removes the static event in the calendar, without connecting with the server. This is done only in the client side.
		//Note that this function should be only used with the Calendar widgets which also have the 'remove' resource defined.
		removeStaticCalendarEvent: function(elm, calendar_settings, arg) {
			if (!MyWidgetResourceLib.CalendarHandler.prompt_remove_event_question || confirm(MyWidgetResourceLib.CalendarHandler.prompt_remove_event_question))
				MyWidgetResourceLib.CalendarHandler.removeCalendarEvent(elm, calendar_settings, arg);
		},
		
		refreshCalendarEvents: function(elm) {
			MyWidgetResourceLib.ResourceHandler.purgeWidgetResources(elm, "load");
			
			var calendar = MyWidgetResourceLib.fn.getNodeElementData(elm, "calendar");
			calendar.refetchEvents();
		},
		
		/* PRIVATE METHODS */
		
		getCalendarArgWithNewEvent: function(elm, calendar_settings, arg, title) {
			//prepare record with dates
			var attributes_name_props = this.getCalendarEventAttributesName(elm);
			var pks_attrs_names = attributes_name_props["pks"];
			var title_attribute_name = attributes_name_props["title"];
			var start_attribute_name = attributes_name_props["start"];
			var end_attribute_name = attributes_name_props["end"];
			var resource_id_attribute_name = attributes_name_props["resource_id"];
			
			var record = {};
			record[title_attribute_name] = title;
			record[resource_id_attribute_name] = arg.resource && arg.resource.id ? arg.resource.id : null;
			
			var start_date = arg.startStr ? arg.startStr : (
				arg.start ? arg.start : (
					arg.event.startStr ? arg.event.startStr : arg.event.start
				)
			);
			var end_date = arg.endStr ? arg.endStr : (
				arg.end ? arg.end : (
					arg.event.endStr ? arg.event.endStr : arg.event.end
				)
			);
			var interval = MyWidgetResourceLib.CalendarHandler.getEventDateInterval(elm, start_date, end_date);
			record[start_attribute_name] = interval.start;
			record[end_attribute_name] = interval.end;
			
			//set record to extendedProps so it can be used by the executeResourceCalendarEventAction method
			arg.event = {
				title: record[title_attribute_name],
				start: record[start_attribute_name],
				end: record[end_attribute_name],
				resourceId: record[resource_id_attribute_name],
				data: record,
				pks_attrs_names: pks_attrs_names,
				start_attr_name: start_attribute_name,
				end_attr_name: end_attribute_name,
				resource_id_attr_name: resource_id_attribute_name,
				pks_attrs: null,
				extendedProps: {
					data: record
				}
			};
			
			return arg;
		},
		
		getCalendarEventAttributesName: function(elm) {
			var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
			var calendar_properties = MyWidgetResourceLib.fn.isPlainObject(properties["calendar"]) ? properties["calendar"] : {};
			var attributes_name_props = MyWidgetResourceLib.fn.isPlainObject(calendar_properties["attributes_name"]) ? calendar_properties["attributes_name"] : {};
			var pks_attrs_names = attributes_name_props["pks"];
			var title_attribute_name = attributes_name_props["title"];
			var start_attribute_name = attributes_name_props["start"];
			var end_attribute_name = attributes_name_props["end"];
			var resource_id_attribute_name = attributes_name_props["resource_id"];
			
			//get the first event data-set props
			if (!title_attribute_name || !start_attribute_name || !end_attribute_name) {
				var data_sets_props = calendar_properties["data_sets"];

				if (MyWidgetResourceLib.fn.isPlainObject(data_sets_props))
					data_sets_props = [data_sets_props];
				
				if (!MyWidgetResourceLib.fn.isArray(data_sets_props))
					data_sets_props = [];
				
				for (var i = 0, t = data_sets_props.length; i < t; i++) {
					var data_set_props = data_sets_props[i];
					
					if (!pks_attrs_names && data_set_props["data_pks_attrs_names"])
						pks_attrs_names = data_set_props["data_pks_attrs_names"];
					
					if (!title_attribute_name && data_set_props["data_title_attribute_name"])
						title_attribute_name = data_set_props["data_title_attribute_name"];
					
					if (!start_attribute_name && data_set_props["data_start_attribute_name"])
						start_attribute_name = data_set_props["data_start_attribute_name"];
					
					if (!end_attribute_name && data_set_props["data_end_attribute_name"])
						end_attribute_name = data_set_props["data_end_attribute_name"];
					
					if (!resource_id_attribute_name && data_set_props["data_resource_id_attribute_name"])
						resource_id_attribute_name = data_set_props["data_resource_id_attribute_name"];
				}
			}
			
			title_attribute_name = title_attribute_name ? title_attribute_name : "title";
			start_attribute_name = start_attribute_name ? start_attribute_name : "start";
			end_attribute_name = end_attribute_name ? end_attribute_name : "end";
			resource_id_attribute_name = resource_id_attribute_name ? resource_id_attribute_name : "resource_id";
			
			return {
				pks: pks_attrs_names,
				title: title_attribute_name,
				start: start_attribute_name,
				end: end_attribute_name,
				resource_id: resource_id_attribute_name,
			};
		},
		
		addCalendarEvent: function(elm, calendar_settings, arg) {
			if (arg.event) {
				var calendar = MyWidgetResourceLib.fn.getNodeElementData(elm, "calendar");
				calendar.addEvent(arg.event);
			}
		},
		
		removeCalendarEvent: function(elm, calendar_settings, arg) {
			if (typeof arg.event.remove == "function")
				arg.event.remove();
			else {
				var calendar = MyWidgetResourceLib.fn.getNodeElementData(elm, "calendar");
				var event = arg.event.id ? calendar.getEventById(arg.event.id) : null;
				
				if (event)
					event.remove();
				else
					MyWidgetResourceLib.CalendarHandler.refreshCalendarEvents(elm);
			}
		},
		
		getEventDateInterval: function(elm, start_date, end_date) {
			var interval = {
				start: null,
				end: null
			};
			
			if (start_date) {
				var calendar = MyWidgetResourceLib.fn.getNodeElementData(elm, "calendar");
				var slot_duration = calendar.getOption('slotDuration');
				
				if (start_date.length <= 10)
					interval["start"] = start_date + "T00:00:00";
				else {
					var d = new Date(start_date);
					var dd = String(d.getDate()).padStart(2, '0');
					var mm = String(d.getMonth() + 1).padStart(2, '0'); //January is 0!
					var yyyy = d.getFullYear();
					var h = String(d.getHours()).padStart(2, '0');
					var i = String(d.getMinutes()).padStart(2, '0');
					
					interval["start"] = yyyy + '-' + mm + '-' + dd + 'T' + h + ':' + i + ':00';
				}
				
				if (!end_date) { //set end_date based in start_Date
					if (start_date.length <= 10)
						interval["end"] = start_date + "T24:00:00";
					else if (slot_duration) {
						//set start date
						var d = new Date(start_date);
						var dd = String(d.getDate()).padStart(2, '0');
						var mm = String(d.getMonth() + 1).padStart(2, '0'); //January is 0!
						var yyyy = d.getFullYear();
						
						//set end date
						var t1 = new Date(yyyy + '-' + mm + '-' + dd + 'T00:00:00').getTime();
						var t2 = new Date(yyyy + '-' + mm + '-' + dd + 'T' + slot_duration).getTime();
						var t = t2 - t1;
						
						d.setTime(d.getTime() + t);
						var dd = String(d.getDate()).padStart(2, '0');
						var mm = String(d.getMonth() + 1).padStart(2, '0'); //January is 0!
						var yyyy = d.getFullYear();
						var h = String(d.getHours()).padStart(2, '0');
						var i = String(d.getMinutes()).padStart(2, '0');
						
						interval["end"] = yyyy + '-' + mm + '-' + dd + 'T' + h + ':' + i + ':00';
					}
					else {
						var d = new Date(start_date);
						var dd = String(d.getDate()).padStart(2, '0');
						var mm = String(d.getMonth() + 1).padStart(2, '0'); //January is 0!
						var yyyy = d.getFullYear();
						
						interval["end"] = yyyy + '-' + mm + '-' + dd + 'T24:00:00';
					}
				}
				else { //if end_date exists
					if (end_date.length <= 10)
						interval["end"] = end_date + "T00:00:00";
					else {
						var d = new Date(end_date);
						var dd = String(d.getDate()).padStart(2, '0');
						var mm = String(d.getMonth() + 1).padStart(2, '0'); //January is 0!
						var yyyy = d.getFullYear();
						var h = String(d.getHours()).padStart(2, '0');
						var i = String(d.getMinutes()).padStart(2, '0');
						
						interval["end"] = yyyy + '-' + mm + '-' + dd + 'T' + h + ':' + i + ':00';
					}
				}
			}
			//console.log(interval);
			
			return interval;
		},
	});
	/****************************************************************************************
	 *				 END: FULL CALENDAR FUNCTIONS 					*
	 ****************************************************************************************/
	
	/****************************************************************************************
	 *				 START: MATRIX FUNCTIONS 					*
	 ****************************************************************************************/
	MyWidgetResourceLib.MatrixHandler = MyWidgetResourceLib.fn.MatrixHandler = ({
		
		times_ttl: 300, //300 milli-seconds
		max_times: 100, //30 seconds
		
		/**
		 * Load and draw main Matrix element based in dynamic resources.
		 * 
		 * Steps:
		 * - Get the [data-widget-matrix-head-column], [data-widget-matrix-head-row] and [data-widget-matrix-body-row] elements, then for each element:
		 * 	- if are not [data-widget-resources-load] or [data-widget-item-resources-load]
		 * 	- execute load resources
		 * - when all requests are complete:
		 * 	- Get the [data-widget-matrix-head-column] elements and loadWidgetResources.
		 * 	- Get the [data-widget-matrix-head-row] and [data-widget-matrix-body-row] elements and loadWidgetResources if apply, this is, if any resource and handler is defined.
		 * 	- Parallel to this, load its resources, but only draw the correspondent records, when all structure be created
		 */
		loadMatrixResource: function(elm, opts) {
			if (elm) {
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var perms = MyWidgetResourceLib.PermissionHandler.getWidgetPermissions(elm);
				
				if (!perms["remove"]) {
					var call_update_method = false;
					
					if (elm.hasAttribute("data-widget-resources")) {
						var is_add_item = opts && opts["add_item"];
						var is_update_item = opts && opts["update_item"];
						var is_remove_item = opts && opts["remove_item"];
						
						if (is_add_item || is_update_item || is_remove_item)
							call_update_method = true;
					}	
					
					//on update a specific item
					if (call_update_method) {
						var end_handler = MyWidgetResourceLib.fn.isPlainObject(properties["end"]) ? properties["end"]["load"] : properties["end"];
						end_handler = MyWidgetResourceLib.fn.convertIntoFunctions(end_handler);
						
						var complete_func = function(resource_elm, resources, resources_name, resources_cache_key) {
							MyWidgetResourceLib.MatrixHandler.updateMatrixResource(elm, resources_name, resources_cache_key, opts);
							
							//call complete handler
							var complete_handler = MyWidgetResourceLib.fn.isPlainObject(properties["complete"]) ? properties["complete"]["load"] : properties["complete"];
							complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
							
							if (complete_handler)
								MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
							
							
							//call complete handler from opts if exists
							var complete_handler = opts && opts["complete"] ? opts["complete"] : null;
							complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
							
							if (complete_handler)
								MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
							
							//reload dependent widgets like the pagination elements, if apply
							MyWidgetResourceLib.fn.loadDependentWidgetsByIdWithoutResourcesToLoad(properties["dependent_widgets_id"], {}, opts);
				   			
				   			//call end handler
							if (end_handler)
								MyWidgetResourceLib.fn.executeFunctions(end_handler, elm, resources_name, resources_cache_key);
						};
						
						//prepare extra url
						var extra_url = opts && opts["extra_url"] ? opts["extra_url"] : "";
						var ignore_resource_conditions = false;
						
						if ((is_update_item || is_remove_item) && opts && opts["search_attrs"]) {
							extra_url += "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(opts["search_attrs"], "search_attrs");
							ignore_resource_conditions = true;
						}
						
						//prepare resource_opts
						var resource_opts = {};
						
						if (MyWidgetResourceLib.fn.isPlainObject(opts)) {
							resource_opts = MyWidgetResourceLib.fn.assignObjectRecursively({}, opts);
							
							delete resource_opts["add_item"];
							delete resource_opts["update_item"];
							delete resource_opts["remove_item"];
							delete resource_opts["search_attrs"];
						}
						
						resource_opts["ignore_resource_conditions"] = ignore_resource_conditions;
						resource_opts["extra_url"] = extra_url;
						resource_opts["label"] = "matrix";
						resource_opts["complete"] = complete_func;
						var resource = MyWidgetResourceLib.ResourceHandler.executeGetWidgetResourcesRequest(elm, "load", resource_opts);
						
						if (!resource) {
							MyWidgetResourceLib.fn.loadDependentWidgetsByIdWithoutResourcesToLoad(properties["dependent_widgets_id"], {}, opts);
							
							if (end_handler)
								MyWidgetResourceLib.fn.executeFunctions(end_handler, elm, null);
						}
					}
					//on full load - load all matrix from scratch
					else {
						var widget_resource = null;
						
						//reset initial contents - in case of reloading
						var is_matrix_inner_html_set = MyWidgetResourceLib.fn.getNodeElementData(elm, "is_matrix_inner_html_set");
						
						if (!is_matrix_inner_html_set) {
							MyWidgetResourceLib.fn.setNodeElementData(elm, "is_matrix_inner_html_set", true);
							MyWidgetResourceLib.fn.setNodeElementData(elm, "matrix_inner_html", elm.innerHTML);
						}
						else {
							var html = MyWidgetResourceLib.fn.getNodeElementData(elm, "matrix_inner_html");
							elm.innerHTML = html;
						}
						
						//var is_first_load = !opts || !opts["purge_cache"];
						var is_first_load = !is_matrix_inner_html_set;
						
						//prepare loading bar and empty
						var loading_elm = elm.querySelector("[data-widget-loading]");
						var empty_elm = elm.querySelector("[data-widget-empty]");
						var is_elm_shown = MyWidgetResourceLib.MatrixHandler.isShown(elm);
						
						if (loading_elm)
							MyWidgetResourceLib.fn.show(loading_elm);
						else if (is_elm_shown)
							MyWidgetResourceLib.fn.hide(elm);
						
						if (empty_elm)
							MyWidgetResourceLib.fn.hide(empty_elm);
						
						//hide [data-widget-matrix-head-column] and [data-widget-matrix-body-row] and other columns elements
						//set also the column indexes because they will be used in the Matrix logic. Note that we need to add this indexes here, bc we delete columns, so we need to get the indexes before deletion.
						var items = elm.querySelectorAll("[data-widget-matrix-head-column], [data-widget-matrix-body-row], [data-widget-matrix-body-column], [data-widget-matrix-previous-related]:not([data-widget-matrix-head-row])");
						
						MyWidgetResourceLib.fn.each(items, function (idx, item) {
							var is_shown = MyWidgetResourceLib.MatrixHandler.isShown(item);
							
							if (is_shown) {
								MyWidgetResourceLib.fn.hide(item);
								MyWidgetResourceLib.fn.setNodeElementData(item, "is_shown", is_shown);
							}
							
							if (
								item.hasAttribute("data-widget-matrix-head-column") || 
								item.hasAttribute("data-widget-matrix-body-column") || 
								(item.hasAttribute("data-widget-matrix-previous-related") && (item.nodeName == "TH" || item.nodeName == "TD"))
							) {
								var indexes = MyWidgetResourceLib.fn.getTableCellIndexes(item);
								MyWidgetResourceLib.fn.setNodeElementData(item, "column_index", indexes["col"]);
							}
						});
						
						//execute load resources for [data-widget-matrix-head-column], [data-widget-matrix-head-row] and [data-widget-matrix-body-row] elements
						var decrement_func = function() {
							MyWidgetResourceLib.MatrixHandler.decrementMatrixItemsCountWithResourceToLoad(elm);
						};
						var items = elm.querySelectorAll("[data-widget-matrix-head-column], [data-widget-matrix-head-row], [data-widget-matrix-body-row], [data-widget-matrix-head-row] [data-widget-matrix-body-column]");
						
						MyWidgetResourceLib.fn.each(items, function (idx, item) {
							//if are not [data-widget-resources-load] or [data-widget-item-resources-load] or [data-widget-resources-loaded]
							if (item.hasAttribute("data-widget-resources") && !item.hasAttribute("data-widget-resources-load") && !item.hasAttribute("data-widget-item-resources-load") && !item.hasAttribute("data-widget-resources-loaded")) {
								var resource_opts = {};
								resource_opts["label"] = "matrix item";
								resource_opts["complete"] = decrement_func;
								resource_opts["error"] = decrement_func;
								
								if (opts && opts["purge_cache"])
									resource_opts["purge_cache"] = opts["purge_cache"];
								
								MyWidgetResourceLib.MatrixHandler.incrementMatrixItemsCountWithResourceToLoad(elm);
								
								var resource = MyWidgetResourceLib.ResourceHandler.executeGetWidgetResourcesRequest(item, "load", resource_opts);
								
								if (!resource)
									MyWidgetResourceLib.MatrixHandler.decrementMatrixItemsCountWithResourceToLoad(elm);
							}
						});
						
						//execute matrix load resources
						if (elm.hasAttribute("data-widget-resources")) {
							var resource_opts = {};
							resource_opts["label"] = "matrix";
							resource_opts["complete"] = decrement_func;
							resource_opts["error"] = decrement_func;
							
							if (opts && opts["purge_cache"])
								resource_opts["purge_cache"] = opts["purge_cache"];
							
							MyWidgetResourceLib.MatrixHandler.incrementMatrixItemsCountWithResourceToLoad(elm);
							
							widget_resource = MyWidgetResourceLib.ResourceHandler.executeGetWidgetResourcesRequest(elm, "load", resource_opts);
							
							if (!widget_resource)
								MyWidgetResourceLib.MatrixHandler.decrementMatrixItemsCountWithResourceToLoad(elm);
						}
						
						//check when all requests are complete
						var times = 0;
						var interval_id = setInterval(function() {
							var c = MyWidgetResourceLib.MatrixHandler.getMatrixItemsCountWithResourceToLoad(elm);
							
							if (c <= 0) {
								clearInterval(interval_id);
								//console.log("******************************");
								
								//First: get the [data-widget-matrix-head-column] elements and execute loadWidgetResource, calling the correspondent load handler - That could be loadMatrixHeadColumnResource. Note that this handler will only be executed if there are load resources.
								//console.log("- START loadMatrixHeadColumnResource:"+elm.querySelectorAll("[data-widget-matrix-head-column], [data-widget-matrix-head-row] [data-widget-matrix-body-column], thead [data-widget-matrix-body-column], th[data-widget-matrix-body-column]").length);
								var columns = elm.querySelectorAll("[data-widget-matrix-head-column]:not([data-widget-matrix-previous-related]), [data-widget-matrix-body-column]:not([data-widget-matrix-previous-related])");
								var head_columns = [];
								MyWidgetResourceLib.fn.each(columns, function (idx, item) {
									if (item.parentNode.closest("thead, [data-widget-matrix-head-row]")) {
										head_columns.push(item);
										
										//console.log("- START NOW");
										MyWidgetResourceLib.ResourceHandler.loadWidgetResource(item, {async: false}); //must be async false, because we want to execute this code synchronous. Note that the resources should be already loaded (bc were loaded before asynchronously).
									}
								});
								//console.log("- END loadMatrixHeadColumnResource");
								
								//Second: get the [data-widget-matrix-head-row] elements and execute loadWidgetResource, calling the correspondent load handler - That could be loadMatrixHeadRowResource. Note that this handler will only be executed if there are load resources.
								//console.log("- START loadMatrixHeadRowResource:"+elm.querySelectorAll("[data-widget-matrix-head-row]").length);
								var items = elm.querySelectorAll("[data-widget-matrix-head-row]");
								MyWidgetResourceLib.fn.each(items, function (idx, item) {
									//console.log("- START NOW");
									MyWidgetResourceLib.ResourceHandler.loadWidgetResource(item, {async: false}); //must be async false, because we want to execute this code synchronous. Note that the resources should be already loaded (bc were loaded before asynchronously).
								});
								//console.log("- END loadMatrixHeadRowResource");
								
								//Third: get the [data-widget-matrix-head-column] elements and execute loadWidgetResource, calling the correspondent load handler - That could be loadMatrixHeadColumnResource. Note that this handler will only be executed if there are load resources.
								//console.log("- START loadMatrixHeadColumnResource:"+elm.querySelectorAll("[data-widget-matrix-head-column], [data-widget-matrix-head-row] [data-widget-matrix-body-column], thead [data-widget-matrix-body-column], th[data-widget-matrix-body-column]").length);
								var table_elms = [];
								
								MyWidgetResourceLib.fn.each(columns, function (idx, item) {
									if (head_columns.indexOf(item) == -1 && (
										item.hasAttribute("data-widget-matrix-head-column") ||
										(item.hasAttribute("data-widget-matrix-body-column") && item.nodeName.toLowerCase() == "th")
									)) {
										var table_elm = item.parentNode.closest("table, [data-widget-matrix]");
										
										if (table_elm && table_elms.indexOf(table_elm) == -1)
											table_elms.push(table_elm);
										
										//console.log("- START NOW");
										MyWidgetResourceLib.ResourceHandler.loadWidgetResource(item, {async: false}); //must be async false, because we want to execute this code synchronous. Note that the resources should be already loaded (bc were loaded before asynchronously).
									}
								});
								//console.log("- END loadMatrixHeadColumnResource");
								
								//FOURTH: update colpsan in [data-widget-loading] and [data-widget-empty], if it was set by the prepareMatrixBodyRowHeadColumnHtml called by the loadMatrixHeadColumnResource
								MyWidgetResourceLib.fn.each(table_elms, function (idx, table_elm) {
									var rows = table_elm.querySelectorAll("[data-widget-loading], [data-widget-empty]");
									
									MyWidgetResourceLib.fn.each(rows, function (idx, row) {
										var colspan_to_reduce = MyWidgetResourceLib.fn.getNodeElementData(row, "colspan_to_reduce");
										colspan_to_reduce = MyWidgetResourceLib.fn.isNumeric(colspan_to_reduce) ? parseInt(colspan_to_reduce) : 0;
										
										if (colspan_to_reduce > 0) {
											var column = row.children[0];
											
											if (column && column.hasAttribute("colspan")) {
												var colspan = column.getAttribute("colspan");
												colspan = MyWidgetResourceLib.fn.isNumeric(colspan) ? parseInt(colspan) : 0;
												colspan -= colspan_to_reduce;
												
												if (colspan < 0)
													column.removeAttribute("colspan");
												else
													column.setAttribute("colspan", colspan);
											}
										}
									});
								});
								
								//FIFTH: get the [data-widget-matrix-body-row] elements and execute loadWidgetResource, calling the correspondent load handler - That could be loadMatrixBodyRowResource. Note that this handler will only be executed if there are load resources.
								//console.log("- START loadMatrixBodyRowResource:"+elm.querySelectorAll("[data-widget-matrix-body-row]").length);
								var items = elm.querySelectorAll("[data-widget-matrix-body-row]");
								MyWidgetResourceLib.fn.each(items, function (idx, item) {
									//console.log("- START NOW");
									MyWidgetResourceLib.ResourceHandler.loadWidgetResource(item, {async: false}); //must be async false, because we want to execute this code synchronous. Note that the resources should be already loaded (bc were loaded before asynchronously).
								});
								//console.log("- END loadMatrixBodyRowResource");
								
								//SIXTH: group similar columns if apply
								MyWidgetResourceLib.MatrixHandler.groupMatrixColumns(elm);
								
								//SEVENTH: after all structure be created, draw the correspondent matrix records correspondent to the user data
								var resources_name = [];
								var resources_cache_key = [];
								
								if (is_elm_shown && !loading_elm)
									MyWidgetResourceLib.fn.show(elm);
								
								if (MyWidgetResourceLib.fn.isArray(widget_resource) && widget_resource.length > 0) {
									//prepare resources name
									for (var i = 0, t = widget_resource.length; i < t; i++) {
										var wr = widget_resource[i];
										
										if (wr["name"]) {
											resources_name.push(wr["name"]);
											resources_cache_key.push(wr["cache_key"]);
										}
									}
									
									//draw resources
									MyWidgetResourceLib.MatrixHandler.drawMatrixResource(elm, resources_name, resources_cache_key);
									
									//call complete handler
									var complete_handler = MyWidgetResourceLib.fn.isPlainObject(properties["complete"]) ? properties["complete"]["load"] : properties["complete"];
									complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
									
									if (complete_handler)
										MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
									
									//call complete handler from opts if exists
									var complete_handler = opts && opts["complete"] ? opts["complete"] : null;
									complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
									
									if (complete_handler)
										MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
								}
								else if (empty_elm)
									MyWidgetResourceLib.fn.show(empty_elm);
								
								if (loading_elm)
									MyWidgetResourceLib.fn.hide(loading_elm);
								
								//EIGHT: reload dependent widgets like the pagination elements, if apply
								if (is_first_load)
									MyWidgetResourceLib.fn.loadDependentWidgetsByIdWithoutResourcesToLoad(properties["dependent_widgets_id"], {}, opts);
								else
									MyWidgetResourceLib.fn.loadDependentWidgetsById(properties["dependent_widgets_id"], {}, opts);
								
								//NINETH: call end handler
								var end_handler = MyWidgetResourceLib.fn.isPlainObject(properties["end"]) ? properties["end"]["load"] : properties["end"];
								end_handler = MyWidgetResourceLib.fn.convertIntoFunctions(end_handler);
								
								if (end_handler)
									MyWidgetResourceLib.fn.executeFunctions(end_handler, elm, resources_name, resources_cache_key);
							}
							else {
								times++;
								
								if (times > MyWidgetResourceLib.MatrixHandler.max_times)
									clearInterval(interval_id);
							}
						}, MyWidgetResourceLib.MatrixHandler.times_ttl);
					}
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No select element in loadMatrixResource method!");
			
			return false;
		},
		
		/**
		 * Load and draw axis-x head rows based in dynamic resources.
		 * 
		 * Steps:
		 * - Load resources
		 * - Get the [data-widget-matrix-head-column] and [data-widget-matrix-previous-related] elements and back up the outer html in the Row.
		 * - delete [data-widget-matrix-head-column] and [data-widget-matrix-previous-related] elements.
		 * - Check if next siblings (rows siblings) are [data-widget-matrix-previous-related] and if so save them into a variable. 
		 * - Then for each sibling:
		 * 	- get the [data-widget-matrix-head-column] and [data-widget-matrix-previous-related] elements and back up the outer html in the sibling row.
		 * 	- delete [data-widget-matrix-head-column] and [data-widget-matrix-previous-related] elements.
		 * - On complete resources load, loop resources records and for each record:
		 * 	- replicate the [data-widget-matrix-head-column] elements, replacing the record dynamic data inside of each element.
		 * 	- for the next siblings (rows siblings) - if exists - replicate the [data-widget-matrix-head-column] elements, replacing the record dynamic data inside of each element.
		 */
		loadMatrixHeadRowResource: function(elm, opts) {
			if (elm) {
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var perms = MyWidgetResourceLib.PermissionHandler.getWidgetPermissions(elm);
				
				if (!perms["remove"]) {
					var resource = null;
					var end_handler = MyWidgetResourceLib.fn.isPlainObject(properties["end"]) ? properties["end"]["load"] : properties["end"];
					end_handler = MyWidgetResourceLib.fn.convertIntoFunctions(end_handler);
					
					if (elm.hasAttribute("data-widget-resources")) {
						var search_attrs = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_attrs");
						var search_types = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_types");
						var search_cases = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_cases");
						var search_operators = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_operators");
						
						//load the default items html. 
						//Get the [data-widget-matrix-head-column] and [data-widget-matrix-previous-related] elements and back up the outer html in the Row.
						MyWidgetResourceLib.MatrixHandler.prepareMatrixHeadRowColumnsHtml(elm);
						MyWidgetResourceLib.MatrixHandler.prepareMatrixHeadRowSiblingsHtml(elm);
						
						//prepare search properties
						search_attrs = typeof search_attrs == "string" && search_attrs[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_attrs) : search_attrs;
						search_types = typeof search_types == "string" && search_types[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_types) : search_types;
						search_cases = typeof search_cases == "string" && search_cases[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_cases) : search_cases;
						search_operators = typeof search_operators == "string" && search_operators[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_operators) : search_operators;
						
						//set default search
						if (!search_attrs || (MyWidgetResourceLib.fn.isPlainObject(search_attrs) && MyWidgetResourceLib.fn.isEmptyObject(search_attrs))) {
							var search_attrs = elm.getAttribute("data-widget-pks-attrs");
							search_attrs = search_attrs ? MyWidgetResourceLib.fn.parseJson(search_attrs) : null;
							
							if (MyWidgetResourceLib.fn.isPlainObject(search_attrs) && !MyWidgetResourceLib.fn.isEmptyObject(search_attrs))
								MyWidgetResourceLib.fn.setNodeElementData(elm, "search_attrs", search_attrs);
						}
						
						var complete_func = function(resource_elm, resources, resources_name, resources_cache_key) {
							MyWidgetResourceLib.MatrixHandler.decrementMatrixItemsCountWithResourceToLoad(elm);
							
							//set resources to elm
							MyWidgetResourceLib.MatrixHandler.drawMatrixHeadRowResource(elm, resources_name, resources_cache_key);
							
							//call complete handler
							var complete_handler = MyWidgetResourceLib.fn.isPlainObject(properties["complete"]) ? properties["complete"]["load"] : properties["complete"];
							complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
							
							if (complete_handler)
								MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
							
							//call complete handler from opts if exists
							var complete_handler = opts && opts["complete"] ? opts["complete"] : null;
							complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
							
							if (complete_handler)
								MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
							
							//reload dependent widgets like the pagination elements, if apply
							MyWidgetResourceLib.fn.loadDependentWidgetsByIdWithoutResourcesToLoad(properties["dependent_widgets_id"], {}, opts);
							
							//call end handler
							if (end_handler)
								MyWidgetResourceLib.fn.executeFunctions(end_handler, elm, resources_name, resources_cache_key);
							
							//console.log("COMPLETE loadMatrixHeadRowResource");
						};
						var error_func = function() {
							MyWidgetResourceLib.MatrixHandler.decrementMatrixItemsCountWithResourceToLoad(elm);
						};
						
						//prepare extra url
						var extra_url = opts && opts["extra_url"] ? opts["extra_url"] : "";
						var ignore_resource_conditions = false;
						
						if (MyWidgetResourceLib.fn.isPlainObject(search_attrs) && !MyWidgetResourceLib.fn.isEmptyObject(search_attrs)) {
							extra_url += "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_attrs, "search_attrs") 
								+ (search_types ? "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_types, "search_types") : "") 
								+ (search_cases ? "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_cases, "search_cases") : "") 
								+ (search_operators ? "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_operators, "search_operators") : "");
							ignore_resource_conditions = true;
						}
						
						MyWidgetResourceLib.MatrixHandler.incrementMatrixItemsCountWithResourceToLoad(elm);
						
						var resource_opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? MyWidgetResourceLib.fn.assignObjectRecursively({}, opts) : {};
						resource_opts["ignore_resource_conditions"] = ignore_resource_conditions; //if search_attrs exists, ignore conditions
						resource_opts["extra_url"] = extra_url;
						resource_opts["label"] = "matrix head row";
						resource_opts["complete"] = complete_func;
						resource_opts["error"] = error_func;
						resource = MyWidgetResourceLib.ResourceHandler.executeGetWidgetResourcesRequest(elm, "load", resource_opts);
						
						if (resource)
							return true;
						else {
							MyWidgetResourceLib.MatrixHandler.decrementMatrixItemsCountWithResourceToLoad(elm);
							
							MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No load resource set in loadMatrixHeadRowResource. Please inform the web-developer accordingly...");
						}
					}
					
					if (!resource) {
						//reload dependent widgets like the pagination elements, if apply
						MyWidgetResourceLib.fn.loadDependentWidgetsByIdWithoutResourcesToLoad(properties["dependent_widgets_id"], {}, opts);
						
						if (end_handler)
							MyWidgetResourceLib.fn.executeFunctions(end_handler, elm);
					}
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No select element in loadMatrixHeadRowResource method!");
			
			return false;
		},
		
		/**
		 * Load and draw axis-x head columns based in dynamic resources.
		 * 
		 * Steps:
		 * - If inside of [data-widget-matrix-head-row]:
		 * 	- Load Resources
		 * 	- Get column outer html and back it up
		 * 	- Check if next siblings (columns siblings) are [data-widget-matrix-previous-related], and if so, append their outer html to main outer html
		 * 	- hide column (and siblings if exists)
		 * 	- On complete resources load, loop resources records and for each record: (Note that the loop of records should only happen when all resources be loaded from others [data-widget-matrix-head-column], otherwise we can have a wrong order of columns)
		 *		- append outer html to previous item, replacing the record dynamic data inside of the html.
		 * 	- after the loop finishes, delete column (and siblings if exists)
		 * 
		 * - If inside of [data-widget-matrix-body-row]:
		 * 	- Load Resources
		 * 	- Get parent [data-widget-matrix-body-row] or "tr" outer html and back it up
		 * 		Note that we should hide all other [data-widget-matrix-head-column] and correspondent [data-widget-matrix-previous-related] that are not related with the current column. 
		 * 		This makes possible the case where we have multiple head columns inside of a body row with different resources to load that call this handler. In this case, we should have the body row repeated for each individual load resource, this is, for each individual head column.
		 * 	- Check if next siblings (rows siblings) are [data-widget-matrix-previous-related], and if so, append their outer html to main outer html
		 * 	- hide parent [data-widget-matrix-body-row] or "tr" (and siblings if exists)
		 * 	- On complete resources load, loop resources records and for each record: (Note that the loop of records should only happen when all resources be loaded from others [data-widget-matrix-head-column] and [data-widget-matrix-body-rows])
		 *		- append outer html to previous item, replacing the record dynamic data inside of the html. Note that the previous item is a row.
		 * 	- after the loop finishes, delete parent [data-widget-matrix-body-row] or "TR" (and rows siblings if exists)
		 */
		loadMatrixHeadColumnResource: function(elm, opts) {
			if (elm && elm.parentNode) {
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var perms = MyWidgetResourceLib.PermissionHandler.getWidgetPermissions(elm);
				
				if (!perms["remove"]) {
					var resource = null;
					var end_handler = MyWidgetResourceLib.fn.isPlainObject(properties["end"]) ? properties["end"]["load"] : properties["end"];
					end_handler = MyWidgetResourceLib.fn.convertIntoFunctions(end_handler);
					
					if (elm.hasAttribute("data-widget-resources")) {
						var search_attrs = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_attrs");
						var search_types = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_types");
						var search_cases = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_cases");
						var search_operators = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_operators");
						
						//load the default items html. 
						//Get the [data-widget-matrix-head-column] and [data-widget-matrix-previous-related] elements and back up the outer html in the Row.
						//Get column outer html and back it up
						var column_index = MyWidgetResourceLib.fn.getTableCellIndexes(elm);
						var inside_of_head_row = elm.parentNode.hasAttribute("data-widget-matrix-head-row") || elm.parentNode.closest("thead") || (elm.nodeName.toLowerCase() == "th" && column_index["row"] == 0); //if inside of head-row or thead or is the first row of the table
						var siblings = null;
						var elm_row = null;
						
						//If inside of [data-widget-matrix-head-row]
						if (inside_of_head_row) {
							//Get column outer html and back it up. Check if next siblings (columns siblings) are [data-widget-matrix-previous-related], and if so, append their outer html to main outer html
							MyWidgetResourceLib.MatrixHandler.prepareMatrixHeadRowHeadColumnHtml(elm);
							
							//get related siblings
							siblings = MyWidgetResourceLib.MatrixHandler.getMatrixElmSiblings(elm);
							
							//hide elm and siblings
							MyWidgetResourceLib.fn.hide(elm);
							
							MyWidgetResourceLib.fn.each(siblings, function (idx, sibling) {
								MyWidgetResourceLib.fn.hide(sibling);
							});
						}
						else { //If inside of [data-widget-matrix-body-row]
							//Get parent [data-widget-matrix-body-row] or "tr" outer html and back it up
							//Note that we should hide all other [data-widget-matrix-head-column] and correspondent [data-widget-matrix-previous-related] that are not related with the current column. 
							//This makes possible the case where we have multiple head columns inside of a body row with different resources to load that call this handler. In this case, we should have the body row repeated for each individual load resource, this is, for each individual head column.
							//Check if next siblings (rows siblings) are [data-widget-matrix-previous-related], and if so, append their outer html to main outer html
							elm_row = MyWidgetResourceLib.MatrixHandler.prepareMatrixBodyRowHeadColumnHtml(elm);
							
							if (elm_row) {
								//get related siblings
								siblings = MyWidgetResourceLib.MatrixHandler.getMatrixElmSiblings(elm_row);
								
								//hide elm and siblings
								MyWidgetResourceLib.fn.hide(elm_row);
								
								MyWidgetResourceLib.fn.each(siblings, function (idx, sibling) {
									MyWidgetResourceLib.fn.hide(sibling);
								});
							}
						}
						
						//prepare search properties
						search_attrs = typeof search_attrs == "string" && search_attrs[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_attrs) : search_attrs;
						search_types = typeof search_types == "string" && search_types[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_types) : search_types;
						search_cases = typeof search_cases == "string" && search_cases[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_cases) : search_cases;
						search_operators = typeof search_operators == "string" && search_operators[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_operators) : search_operators;
						
						//set default search
						if (!search_attrs || (MyWidgetResourceLib.fn.isPlainObject(search_attrs) && MyWidgetResourceLib.fn.isEmptyObject(search_attrs))) {
							var search_attrs = elm.getAttribute("data-widget-pks-attrs");
							search_attrs = search_attrs ? MyWidgetResourceLib.fn.parseJson(search_attrs) : null;
							
							if (MyWidgetResourceLib.fn.isPlainObject(search_attrs) && !MyWidgetResourceLib.fn.isEmptyObject(search_attrs))
								MyWidgetResourceLib.fn.setNodeElementData(elm, "search_attrs", search_attrs);
						}
						
						var pks_attrs_names = properties.hasOwnProperty("matrix") && properties["matrix"].hasOwnProperty("pks_attrs_names") ? properties["matrix"]["pks_attrs_names"] : "";
						
						var complete_func = function(resource_elm, resources, resources_name, resources_cache_key) {
							MyWidgetResourceLib.MatrixHandler.decrementMatrixItemsCountWithResourceToLoad(elm);
							
							//set resources to elm
							if (inside_of_head_row)
								MyWidgetResourceLib.MatrixHandler.drawMatrixHeadColumnResource(elm, resources_name, resources_cache_key);
							else if (elm_row)
								MyWidgetResourceLib.MatrixHandler.drawMatrixBodyRowResource(elm_row, resources_name, resources_cache_key, pks_attrs_names);
							
							//call complete handler
							var complete_handler = MyWidgetResourceLib.fn.isPlainObject(properties["complete"]) ? properties["complete"]["load"] : properties["complete"];
							complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
							
							if (complete_handler)
								MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
							
							//call complete handler from opts if exists
							var complete_handler = opts && opts["complete"] ? opts["complete"] : null;
							complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
							
							if (complete_handler)
								MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
							
							//reload dependent widgets like the pagination elements, if apply
							MyWidgetResourceLib.fn.loadDependentWidgetsByIdWithoutResourcesToLoad(properties["dependent_widgets_id"], {}, opts);
							
							//call end handler
							if (end_handler)
								MyWidgetResourceLib.fn.executeFunctions(end_handler, elm, resources_name, resources_cache_key);
							
							//remove elm and siblings
							if (inside_of_head_row) {
								elm.parentNode.removeChild(elm);
								
								MyWidgetResourceLib.fn.each(siblings, function (idx, sibling) {
									sibling.parentNode.removeChild(sibling);
								});
							}
							//delete elm row and siblings, but only if is the last head column, otherwise when it executes the more than one head column, the parent row won't have any parent because doesn't exist anymore
							else if (elm_row) {
								var c = MyWidgetResourceLib.MatrixHandler.getMatrixItemsCountWithResourceToLoad(elm);
								
								if (c <= 0) {
									var list_item_html = MyWidgetResourceLib.fn.getNodeElementData(elm_row, "list_item_html");
									var can_remove = true;
									
									if (list_item_html && list_item_html.head_columns_count > 1)
										can_remove = list_item_html.head_column_index == list_item_html.head_columns_count - 1;
									
									if (can_remove) {
										elm_row.parentNode.removeChild(elm_row);
										
										MyWidgetResourceLib.fn.each(siblings, function (idx, sibling) {
											sibling.parentNode.removeChild(sibling);
										});
									}
								}
							}
							//console.log("COMPLETE loadMatrixHeadColumnResource");
						};
						var error_func = function() {
							//console.log("ERROR loadMatrixHeadColumnResource");
							MyWidgetResourceLib.MatrixHandler.decrementMatrixItemsCountWithResourceToLoad(elm);
						};
						
						//prepare extra url
						var extra_url = opts && opts["extra_url"] ? opts["extra_url"] : "";
						var ignore_resource_conditions = false;
						
						if (MyWidgetResourceLib.fn.isPlainObject(search_attrs) && !MyWidgetResourceLib.fn.isEmptyObject(search_attrs)) {
							extra_url += "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_attrs, "search_attrs") 
								+ (search_types ? "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_types, "search_types") : "") 
								+ (search_cases ? "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_cases, "search_cases") : "") 
								+ (search_operators ? "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_operators, "search_operators") : "");
							ignore_resource_conditions = true;
						}
						
						MyWidgetResourceLib.MatrixHandler.incrementMatrixItemsCountWithResourceToLoad(elm);
						
						var resource_opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? MyWidgetResourceLib.fn.assignObjectRecursively({}, opts) : {};
						resource_opts["ignore_resource_conditions"] = ignore_resource_conditions; //if search_attrs exists, ignore conditions
						resource_opts["extra_url"] = extra_url;
						resource_opts["label"] = "matrix head row";
						resource_opts["complete"] = complete_func;
						resource_opts["error"] = error_func;
						resource = MyWidgetResourceLib.ResourceHandler.executeGetWidgetResourcesRequest(elm, "load", resource_opts);
						
						if (resource)
							return true;
						else {
							MyWidgetResourceLib.MatrixHandler.decrementMatrixItemsCountWithResourceToLoad(elm);
							
							MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No load resource set in loadMatrixHeadRowResource. Please inform the web-developer accordingly...");
						}
					}
					
					if (!resource) {
						//console.log("NO RESOURCE loadMatrixHeadColumnResource");
						
						//reload dependent widgets like the pagination elements, if apply
						MyWidgetResourceLib.fn.loadDependentWidgetsByIdWithoutResourcesToLoad(properties["dependent_widgets_id"], {}, opts);
						
						if (end_handler)
							MyWidgetResourceLib.fn.executeFunctions(end_handler, elm);
					}
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No select element in loadMatrixHeadRowResource method!");
			
			return false;
		},
		
		/**
		 * Load and draw axis-y rows with head columns based in dynamic resources.
		 * 
		 * Steps:
		 * - Load resources
		 * - Get row outer html and back it up
		 * - Check if next siblings (rows siblings) are [data-widget-matrix-previous-related], and if so, append their outer html to main outer html
		 * - hide row (and siblings if exists)
		 * - On complete resources load, loop resources records and for each record:
		 * 	- append outer html to previous item, replacing the record dynamic data inside of the html.
		 * 	- prepare body-columns, by adding the pks_attrs_names from axis x and y:
		 * 		- if from head row, get the "data-widget-pks-attrs" attribute from each column.
		 * 		- if from body row, get the "data-widget-pks-attrs" attribute from each tr.
		 */
		loadMatrixBodyRowResource: function(elm, opts) {
			if (elm) {
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var perms = MyWidgetResourceLib.PermissionHandler.getWidgetPermissions(elm);
				
				if (!perms["remove"]) {
					var resource = null;
					var end_handler = MyWidgetResourceLib.fn.isPlainObject(properties["end"]) ? properties["end"]["load"] : properties["end"];
					end_handler = MyWidgetResourceLib.fn.convertIntoFunctions(end_handler);
					
					if (elm.hasAttribute("data-widget-resources")) {
						var search_attrs = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_attrs");
						var search_types = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_types");
						var search_cases = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_cases");
						var search_operators = MyWidgetResourceLib.fn.getNodeElementData(elm, "search_operators");
						
						//load the default items html. 
						//Get row outer html and back it up
		 				//Check if next siblings (rows siblings) are [data-widget-matrix-previous-related], and if so, append their outer html to main outer html
						MyWidgetResourceLib.MatrixHandler.prepareMatrixBodyRowHtml(elm);
						var siblings = MyWidgetResourceLib.MatrixHandler.getMatrixElmSiblings(elm);
						
						//hide elm and siblings - later these elms will be deleted
						MyWidgetResourceLib.fn.hide(elm);
						
						MyWidgetResourceLib.fn.each(siblings, function (idx, sibling) {
							MyWidgetResourceLib.fn.hide(sibling);
						});
						
						//prepare search properties
						search_attrs = typeof search_attrs == "string" && search_attrs[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_attrs) : search_attrs;
						search_types = typeof search_types == "string" && search_types[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_types) : search_types;
						search_cases = typeof search_cases == "string" && search_cases[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_cases) : search_cases;
						search_operators = typeof search_operators == "string" && search_operators[0] == "{" ? MyWidgetResourceLib.fn.parseJson(search_operators) : search_operators;
						
						//set default search
						if (!search_attrs || (MyWidgetResourceLib.fn.isPlainObject(search_attrs) && MyWidgetResourceLib.fn.isEmptyObject(search_attrs))) {
							var search_attrs = elm.getAttribute("data-widget-pks-attrs");
							search_attrs = search_attrs ? MyWidgetResourceLib.fn.parseJson(search_attrs) : null;
							
							if (MyWidgetResourceLib.fn.isPlainObject(search_attrs) && !MyWidgetResourceLib.fn.isEmptyObject(search_attrs))
								MyWidgetResourceLib.fn.setNodeElementData(elm, "search_attrs", search_attrs);
						}
						
						var complete_func = function(resource_elm, resources, resources_name, resources_cache_key) {
							MyWidgetResourceLib.MatrixHandler.decrementMatrixItemsCountWithResourceToLoad(elm);
							
							//set resources to elm
							MyWidgetResourceLib.MatrixHandler.drawMatrixBodyRowResource(elm, resources_name, resources_cache_key);
							
							//call complete handler
							var complete_handler = MyWidgetResourceLib.fn.isPlainObject(properties["complete"]) ? properties["complete"]["load"] : properties["complete"];
							complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
							
							if (complete_handler)
								MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
							
							//call complete handler from opts if exists
							var complete_handler = opts && opts["complete"] ? opts["complete"] : null;
							complete_handler = MyWidgetResourceLib.fn.convertIntoFunctions(complete_handler);
							
							if (complete_handler)
								MyWidgetResourceLib.fn.executeFunctions(complete_handler, elm, resources_name, resources_cache_key);
							
							//reload dependent widgets like the pagination elements, if apply
							MyWidgetResourceLib.fn.loadDependentWidgetsByIdWithoutResourcesToLoad(properties["dependent_widgets_id"], {}, opts);
							
							//call end handler
							if (end_handler)
								MyWidgetResourceLib.fn.executeFunctions(end_handler, elm, resources_name, resources_cache_key);
							
							//remove item and related siblings
							elm.parentNode.removeChild(elm);
							
							MyWidgetResourceLib.fn.each(siblings, function (idx, sibling) {
								sibling.parentNode.removeChild(sibling);
							});
							
							//console.log("COMPLETE loadMatrixBodyRowResource");
						};
						var error_func = function() {
							MyWidgetResourceLib.MatrixHandler.decrementMatrixItemsCountWithResourceToLoad(elm);
						};
						
						//prepare extra url
						var extra_url = opts && opts["extra_url"] ? opts["extra_url"] : "";
						var ignore_resource_conditions = false;
						
						if (MyWidgetResourceLib.fn.isPlainObject(search_attrs) && !MyWidgetResourceLib.fn.isEmptyObject(search_attrs)) {
							extra_url += "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_attrs, "search_attrs") 
								+ (search_types ? "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_types, "search_types") : "") 
								+ (search_cases ? "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_cases, "search_cases") : "") 
								+ (search_operators ? "&" + MyWidgetResourceLib.AjaxHandler.convertDataToQueryString(search_operators, "search_operators") : "");
							ignore_resource_conditions = true;
						}
						
						MyWidgetResourceLib.MatrixHandler.incrementMatrixItemsCountWithResourceToLoad(elm);
						
						var resource_opts = MyWidgetResourceLib.fn.isPlainObject(opts) ? MyWidgetResourceLib.fn.assignObjectRecursively({}, opts) : {};
						resource_opts["ignore_resource_conditions"] = ignore_resource_conditions; //if search_attrs exists, ignore conditions
						resource_opts["extra_url"] = extra_url;
						resource_opts["label"] = "matrix head row";
						resource_opts["complete"] = complete_func;
						resource_opts["error"] = error_func;
						resource = MyWidgetResourceLib.ResourceHandler.executeGetWidgetResourcesRequest(elm, "load", resource_opts);
						
						if (resource)
							return true;
						else {
							MyWidgetResourceLib.MatrixHandler.decrementMatrixItemsCountWithResourceToLoad(elm);
							
							MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No load resource set in loadMatrixBodyRowResource. Please inform the web-developer accordingly...");
						}
					}
					
					if (!resource) {
						//in case there are no data-widget-resources defined or the resource didn't get executed, show the default row.
						var is_shown = MyWidgetResourceLib.fn.getNodeElementData(elm, "is_shown");
						var is_hidden = !MyWidgetResourceLib.MatrixHandler.isShown(elm);
						
						if (is_shown && is_hidden)
							MyWidgetResourceLib.fn.show(elm);
						
						//show row columns
						var columns = elm.querySelectorAll("[data-widget-matrix-head-column], [data-widget-matrix-body-column], [data-widget-matrix-previous-related]");
						
						if (columns)
							MyWidgetResourceLib.fn.each(columns, function (idx, column) {
								//show column
								var is_column_shown = MyWidgetResourceLib.fn.getNodeElementData(column, "is_shown");
								var is_column_hidden = !MyWidgetResourceLib.MatrixHandler.isShown(column);
								
								if (is_column_shown && is_column_hidden)
									MyWidgetResourceLib.fn.show(column);
							});
						
						//prepare head_columns_pks
						var head_columns_pks = MyWidgetResourceLib.MatrixHandler.getMatrixHeadRowsColumnsPksByIndex(elm);
						
						if (!MyWidgetResourceLib.fn.isEmptyObject(head_columns_pks)) {
							//prepare body-columns, by adding the pks_attrs_names from axis x:
							//- if from head row, get the "data-widget-pks-attrs" attribute from each column.
							var columns = elm.querySelectorAll("td, th, [data-widget-matrix-head-column], [data-widget-matrix-previous-related]");
							
							MyWidgetResourceLib.fn.each(columns, function(idy, column) {
								//prepare axis x pks
								var column_index = MyWidgetResourceLib.fn.getTableCellIndexes(column);
								column_index = column_index["col"];
								
								if (MyWidgetResourceLib.fn.isNumeric(column_index)) {
									var axis_x_pks = head_columns_pks[column_index];
									
									if (axis_x_pks)
										column.setAttribute("data-widget-matrix-axis-x-pks-attrs", axis_x_pks);
								}
							});
						}
						
						//reload dependent widgets like the pagination elements, if apply
						MyWidgetResourceLib.fn.loadDependentWidgetsByIdWithoutResourcesToLoad(properties["dependent_widgets_id"], {}, opts);
						
						if (end_handler)
							MyWidgetResourceLib.fn.executeFunctions(end_handler, elm);
					}
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No select element in loadMatrixBodyRowResource method!");
			
			return false;
		},
		
		/* DRAW FUNCTIONS */
		
		/**
		 * Based in the saved columns html draw that html for each resource record
		 */
		drawMatrixHeadRowResource: function(elm, resources_name, resources_cache_key) {
			if (elm) {
				var siblings = MyWidgetResourceLib.fn.getNodeElementData(elm, "siblings");
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var pks_attrs_names = properties.hasOwnProperty("matrix") && properties["matrix"].hasOwnProperty("pks_attrs_names") ? properties["matrix"]["pks_attrs_names"] : "";
				pks_attrs_names = MyWidgetResourceLib.fn.isArray(pks_attrs_names) ? pks_attrs_names : pks_attrs_names.replace(/;/g, ",").split(",");
				
				//load items
				var columns_indexes = [];
				var columns_idexes_addition = 0;
				var elements = [elm];
				elements = siblings ? elements.concat(siblings) : elements;
				
				for (var w = 0, tw = elements.length; w < tw; w++) {
					var element = elements[w];
					var columns_html = MyWidgetResourceLib.fn.getNodeElementData(element, "columns_html");
					//console.log(columns_html);
					
					if (columns_html) {
						var prev_item_elm = null;
						
						for (var i = 0, t = resources_name.length; i < t; i++) {
							var resource_name = resources_name[i];
							var resource_cache_key = resources_cache_key ? resources_cache_key[i] : null;
							var data = MyWidgetResourceLib.ResourceHandler.getLoadedSLAResult(resource_name, resource_cache_key);
							
							if (data) {
								MyWidgetResourceLib.fn.each(data, function(idx, record) {
							   		if (MyWidgetResourceLib.fn.isPlainObject(record)) {
										//prepare PKs string
										var pks_attrs = {};
										
										for (var j = 0, tj = pks_attrs_names.length; j < tj; j++) {
											var pk_attr_name = ("" + pks_attrs_names[j]).replace(/\s+/g, "");
											
											if (pk_attr_name)
												pks_attrs[pk_attr_name] = record.hasOwnProperty(pk_attr_name) ? record[pk_attr_name] : null;
										}
										
										//prepare columns_idexes_addition - Do not set the column_index for first item, bc they are already in the table, set by default by the user.
										if (w == 0 && (idx > 0 || i > 0))
											columns_idexes_addition++;
										
										//prepare html
										for (var j = 0, tj = columns_html.length; j < tj; j++) {
											var column_html = columns_html[j];
											var item_html = column_html.html;
											var item_parent = column_html.parent;
											var siblings_count = column_html.siblings_count;
											var column_index = column_html.column_index;
											
											//save column_index - ignore first record - Do not set the column_index for first item, bc they are already in the table, set by default by the user.
											if ((idx > 0 || i > 0) && columns_indexes.indexOf(column_index) == -1) {
												columns_indexes.push(column_index);
												
												if (siblings_count)
													for (var q = 0; q < siblings_count; q++) 
														columns_indexes.push(column_index + q + 1);
											}
											
											//replace hashtags if apply to the current resource
											var inner_item_hash_tags_to_ignore = MyWidgetResourceLib.HashTagHandler.getWidgetResourceValueInnerItemsHtmlHashTagsBasedInResources(item_html, resource_name, resource_cache_key, idx);
											item_html = MyWidgetResourceLib.HashTagHandler.replaceHtmlHashTagsWithResources(item_html, resource_name, resource_cache_key, idx, inner_item_hash_tags_to_ignore);
											
											//append html element to item_parent
											var item_elm = null;
											
											if (prev_item_elm) { //if multiple columns_html, the previous one is the columns_html[j - 1], otherwise the elements will appear flipped and with the wrong order.
												prev_item_elm.insertAdjacentHTML('afterend', item_html);
												item_elm = prev_item_elm.nextElementSibling;
											}
											else if (column_html.prev && column_html.prev.parentNode) {
												column_html.prev.insertAdjacentHTML('afterend', item_html);
												item_elm = column_html.prev.nextElementSibling;
											}
											else {
												if (column_html.next && column_html.next.parentNode) {
													column_html.next.insertAdjacentHTML('beforebegin', item_html);
													item_elm = column_html.next.previousElementSibling;
												}
												else {
													item_parent.insertAdjacentHTML('beforeend', item_html);
													item_elm = item_parent.lastElementChild;
												}
												
												if (siblings_count)
													for (var q = 0; q < siblings_count; q++) 
														if (item_elm.previousElementSibling)
															item_elm = item_elm.previousElementSibling;
											}
											
											prev_item_elm = item_elm;
											
											var items_elms = [item_elm];
											
											if (siblings_count)
												for (var q = 0; q < siblings_count; q++) 
													if (prev_item_elm.nextElementSibling) {
														prev_item_elm = prev_item_elm.nextElementSibling;
														items_elms.push(prev_item_elm);
													}
											
											for (var q = 0, tq = items_elms.length; q < tq; q++) {
												item_elm = items_elms[q];
												//var ignore_column = item_elm.hasAttribute("data-widget-head-column-loaded"); //THIS IS DEPRECATED bc we can use the data-widget-pks-attrs instead
												var ignore_column = item_elm.hasAttribute("data-widget-pks-attrs");
												
												if (!ignore_column) {
													//set PKs
													item_elm.setAttribute("data-widget-pks-attrs", JSON.stringify(pks_attrs));
													
													//save loaded resource data in item_elm
													MyWidgetResourceLib.FieldHandler.saveWidgetLoadedResourceValues(item_elm, resource_name, resource_cache_key, idx);
													
													//set attributes according with the resources, if apply
													MyWidgetResourceLib.FieldHandler.setWidgetResourceAttributes(item_elm, resource_name, resource_cache_key, idx);
													
													//prepare data-widget-resource-value
													MyWidgetResourceLib.FieldHandler.setWidgetResourceValues(item_elm, resource_name, resource_cache_key, idx);
												}
											}
										}
									}
							   	});
							}
						}
					}
					
					var is_shown = MyWidgetResourceLib.fn.getNodeElementData(element, "is_shown");
					var is_hidden = !MyWidgetResourceLib.MatrixHandler.isShown(element);
					
					if (is_shown)
						MyWidgetResourceLib.fn.show(element);
				}
				
				//sort columns_indexes - otherwise the columns appear flipped, bc if we have multiple rows where the first one has a column with colspan, then we can have the columns_indexes with [1,3,2] which will then show flipped columns. So we need to sort the columns_indexes first.
				columns_indexes.sort();
				
				//prepare missing columns in tbody rows
				//console.log(data.length+"=="+columns_idexes_addition);
				//console.log(columns_indexes);
				var parent = elm.parentNode.closest("table, [data-widget-matrix]");
				
				if (parent) {
					var rows = parent.querySelectorAll("[data-widget-matrix-body-row], tbody tr");
					
					MyWidgetResourceLib.fn.each(rows, function(idx, row) {
						if (row.hasAttribute("data-widget-loading") || row.hasAttribute("data-widget-empty")) {
							var column = row.children[0];
							
							if (column) {
								var colspan = column.getAttribute("colspan");
								colspan = MyWidgetResourceLib.fn.isNumeric(colspan) ? parseInt(colspan) : 0;
								colspan += columns_idexes_addition * columns_indexes.length;
								
								column.setAttribute("colspan", colspan);
							}
						}
						else {
							for (var i = 0; i < columns_idexes_addition; i++) {
								var ignore_columns_indexes = [];
								
								for (var j = 0, tj = columns_indexes.length; j < tj; j++) {
									var column_index = columns_indexes[j];
									
									if (ignore_columns_indexes.indexOf(column_index) == -1) {
										var index = MyWidgetResourceLib.fn.getTableRowChildrenIndexByTableCellIndex(row, column_index);
										var column = MyWidgetResourceLib.fn.isNumeric(index) ? row.children[index] : null;
										//console.log("index:"+index);
										
										if (column) {
											if (column.hasAttribute("colspan")) {
												var colspan = column.getAttribute("colspan");
												colspan = MyWidgetResourceLib.fn.isNumeric(colspan) ? parseInt(colspan) : 0;
												
												for (var w = 1; w < colspan; w++) {
													ignore_columns_indexes.push(column_index + w);
												}
											}
											
											var is_shown = MyWidgetResourceLib.fn.getNodeElementData(column, "is_shown");
											var is_hidden = !MyWidgetResourceLib.MatrixHandler.isShown(column);
											
											if (is_shown)
												MyWidgetResourceLib.fn.show(column);
											
											var column_html = column.outerHTML;
											row.insertAdjacentHTML('beforeend', column_html);
											
											if (is_shown && is_hidden)
												MyWidgetResourceLib.fn.hide(column);
											
											//console.log(column);
											//console.log("index:"+index+" - "+column_index);
											//console.log(column_html);
										}
									 }
								}
							}
						}
					});
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No list element in drawMatrixHeadRowResource method!");
		},
		
		/**
		 * Based in a saved column html draw that html for each resource record
		 */
		drawMatrixHeadColumnResource: function(elm, resources_name, resources_cache_key, column_htmls) {
			if (elm) {
				var column_html = MyWidgetResourceLib.fn.getNodeElementData(elm, "column_html", column_html);
				
				//load items
				if (column_html) {
					var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
					var pks_attrs_names = properties.hasOwnProperty("matrix") && properties["matrix"].hasOwnProperty("pks_attrs_names") ? properties["matrix"]["pks_attrs_names"] : "";
					pks_attrs_names = MyWidgetResourceLib.fn.isArray(pks_attrs_names) ? pks_attrs_names : pks_attrs_names.replace(/;/g, ",").split(",");
					
					var item_html = column_html.html;
					var item_parent = column_html.parent;
					var siblings_count = column_html.siblings_count;
					var column_index = column_html.column_index;
					
					//prepare column_index
					var new_column_index = MyWidgetResourceLib.fn.getTableCellIndexes(elm);
					new_column_index = new_column_index["col"];
					
					var diff = new_column_index - column_index;
   					//console.log(new_column_index+"-"+column_index+"="+diff);
					column_index = column_index + diff;
   					//console.log("new column_index:"+column_index);
   					
   					var columns_indexes = [column_index];
					
					for (var j = 0; j < siblings_count; j++)
						columns_indexes.push(column_index + j + 1);
   					//console.log(columns_indexes);
   					
					//prepare missing columns in tbody rows
					var elm_parent = elm.parentNode.closest("table, [data-widget-matrix]");
					var rows = elm_parent ? elm_parent.querySelectorAll("[data-widget-matrix-body-row], tbody tr") : null;
					var add_missing_columns_to_body = function(row_col_index_to_append) {
						MyWidgetResourceLib.fn.each(rows, function(idx, row) {
							if (row.hasAttribute("data-widget-loading") || row.hasAttribute("data-widget-empty")) {
								var column = row.children[0];
								
								if (column) {
									var colspan = column.getAttribute("colspan");
									colspan = MyWidgetResourceLib.fn.isNumeric(colspan) ? parseInt(colspan) : 0;
									colspan += columns_indexes.length;
									
									column.setAttribute("colspan", colspan);
								}
							}
							else {
								var row_head_column = row.querySelector("[data-widget-matrix-head-column]:not([data-widget-matrix-previous-related])");
								var prev_item_elm = null;
								
								for (var i = 0, t = columns_indexes.length; i < t; i++) {
									var index = MyWidgetResourceLib.fn.getTableRowChildrenIndexByTableCellIndex(row, columns_indexes[i]);
									
									if (MyWidgetResourceLib.fn.isNumeric(index)) {
										var index_inc = 0;
										
										if (row_head_column)
											for (var j = 0; j <= index; j++) {
												var child = row.children[j];
												
												if (child == row_head_column) {
													for (var w = j + 1; w <= index; w++) {
														var child = row.children[w];
														
														if (!child.hasAttribute("data-widget-matrix-previous-related")) {
															j = w - 1;
															break;
														}
													}
												}
												else if (child.hasAttribute("data-widget-matrix-head-column") || child.hasAttribute("data-widget-matrix-previous-related"))
													index_inc++;
											}
										
										var column = row.children[index + index_inc];
										
										if (column) {
											var is_shown = MyWidgetResourceLib.fn.getNodeElementData(column, "is_shown");
											var is_hidden = !MyWidgetResourceLib.MatrixHandler.isShown(column);
											
											if (is_shown)
												MyWidgetResourceLib.fn.show(column);
											
											var column_html = column.outerHTML;
											
											if (prev_item_elm) {
												prev_item_elm.insertAdjacentHTML('afterend', column_html);
												prev_item_elm = col_to_append.nextElementSibling;
											}
											else {
												var col_to_append_index = MyWidgetResourceLib.fn.getTableRowChildrenIndexByTableCellIndex(row, row_col_index_to_append + index_inc);
												
												var col_to_append = row.children[col_to_append_index];
												
												if (col_to_append) {
													col_to_append.insertAdjacentHTML('afterend', column_html);
													prev_item_elm = col_to_append.nextElementSibling;
													
													if (is_shown && is_hidden)
														MyWidgetResourceLib.fn.hide(column);
												}
											}
											
											//console.log("index:"+index+"(+"+index_inc+") - "+columns_indexes[i]);
											//console.log(column_html);
										}
									}
								}
							}
						});
					};
					
					//prepare html
					var prev_column_elm = elm.previousElementSibling.hasAttribute("data-widget-matrix-head-column") || elm.previousElementSibling.hasAttribute("data-widget-matrix-body-column") || elm.previousElementSibling.hasAttribute("data-widget-matrix-previous-related") ? elm.previousElementSibling : null;
					var prev_item_elm = null;
   					var resources_column_index = column_index;
   					
   					for (var i = 0, t = resources_name.length; i < t; i++) {
						var resource_name = resources_name[i];
						var resource_cache_key = resources_cache_key ? resources_cache_key[i] : null;
						var data = MyWidgetResourceLib.ResourceHandler.getLoadedSLAResult(resource_name, resource_cache_key);
						
						if (data) {
							MyWidgetResourceLib.fn.each(data, function(idx, record) {
						   		if (MyWidgetResourceLib.fn.isPlainObject(record)) {
									//prepare PKs string
									var pks_attrs = {};
									
									for (var j = 0, tj = pks_attrs_names.length; j < tj; j++) {
										var pk_attr_name = ("" + pks_attrs_names[j]).replace(/\s+/g, "");
										
										if (pk_attr_name)
											pks_attrs[pk_attr_name] = record.hasOwnProperty(pk_attr_name) ? record[pk_attr_name] : null;
									}
									
									//prepare html
									var html = item_html;
									
									//replace hashtags if apply to the current resource
									var inner_item_hash_tags_to_ignore = MyWidgetResourceLib.HashTagHandler.getWidgetResourceValueInnerItemsHtmlHashTagsBasedInResources(html, resource_name, resource_cache_key, idx);
									html = MyWidgetResourceLib.HashTagHandler.replaceHtmlHashTagsWithResources(html, resource_name, resource_cache_key, idx, inner_item_hash_tags_to_ignore);
									
									//append html element to item_parent
									var item_elm = null;
									var row_col_index_to_append = null;
									
									if (prev_item_elm) { //if multiple columns_html, the previous one is the columns_html[j - 1], otherwise the elements will appear flipped and with the wrong order.
										prev_item_elm.insertAdjacentHTML('afterend', html);
										item_elm = prev_item_elm.nextElementSibling;
										
										//Do not set the row_col_index_to_append for first item, this is, only set the row_col_index_to_append if prev_item_elm exists, otherwise we don't need to add columns bc they are already in the table, set by default by the user.
										row_col_index_to_append = MyWidgetResourceLib.fn.getTableCellIndexes(prev_item_elm);
										row_col_index_to_append = row_col_index_to_append["col"];
									}
									else if (prev_column_elm) { //if multiple columns_html, the previous one is the columns_html[j - 1], otherwise the elements will appear flipped and with the wrong order.
										prev_column_elm.insertAdjacentHTML('afterend', html);
										item_elm = prev_column_elm.nextElementSibling;
									}
									else if (column_html.prev && column_html.prev.parentNode) {
										column_html.prev.insertAdjacentHTML('afterend', html);
										item_elm = column_html.prev.nextElementSibling;
									}
									else {
										if (column_html.next && column_html.next.parentNode) {
											column_html.next.insertAdjacentHTML('beforebegin', html);
											item_elm = column_html.next.previousElementSibling;
										}
										else {
											item_parent.insertAdjacentHTML('beforeend', html);
											item_elm = item_parent.lastElementChild;
										}
										
										if (siblings_count)
											for (var q = 0; q < siblings_count; q++) 
												if (item_elm.previousElementSibling)
													item_elm = item_elm.previousElementSibling;
									}
									
									prev_item_elm = item_elm;
									
									var items_elms = [item_elm];
									
									if (siblings_count)
										for (var j = 0; j < siblings_count; j++) 
											if (prev_item_elm.nextElementSibling) {
												prev_item_elm = prev_item_elm.nextElementSibling;
												items_elms.push(prev_item_elm);
											}
									
									for (var j = 0, tj = items_elms.length; j < tj; j++) {
										item_elm = items_elms[j];
										
										//set PKs
										item_elm.setAttribute("data-widget-pks-attrs", JSON.stringify(pks_attrs));
										
										//save loaded resource data in item_elm
										MyWidgetResourceLib.FieldHandler.saveWidgetLoadedResourceValues(item_elm, resource_name, resource_cache_key, idx);
										
										//set attributes according with the resources, if apply
										MyWidgetResourceLib.FieldHandler.setWidgetResourceAttributes(item_elm, resource_name, resource_cache_key, idx);
										
										//prepare data-widget-resource-value
										MyWidgetResourceLib.FieldHandler.setWidgetResourceValues(item_elm, resource_name, resource_cache_key, idx);
										
										//set column index to be used later by the Matrix methods
										resources_column_index += j;
										MyWidgetResourceLib.fn.setNodeElementData(item_elm, "column_index", resources_column_index);
										
										//set attribute so this column doesn't get parsed again by the loadWidgetResource of the [data-widget-head-row] - THIS IS DEPRECATED bc we can use the data-widget-pks-attrs instead
										//item_elm.setAttribute("data-widget-head-column-loaded", "");
									}
									
									//increment 1, bc the resources_column_index must be the next column_index
									resources_column_index++;
									
									//prepare missing columns in tbody rows, but only if not is the first record, otherwise we don't need to add columns bc they are already in the table, set by default by the user.
									//console.log("row_col_index_to_append("+idx+"):"+row_col_index_to_append);
									if (MyWidgetResourceLib.fn.isNumeric(row_col_index_to_append))
										add_missing_columns_to_body(row_col_index_to_append);
								}
						   	});
						}
					}
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No list element in drawMatrixHeadColumnResource method!");
		},
		
		/**
		 * Based in the saved columns html draw that html for each resource record
		 */
		drawMatrixBodyRowResource: function(elm, resources_name, resources_cache_key, pks_attrs_names) {
			if (elm) {
				var list_item_html = MyWidgetResourceLib.fn.getNodeElementData(elm, "list_item_html");
				//console.log(list_item_html);
				
				//in the case where we call this function from a head column, the pks_attrs_names will be in the properties of the correspondent column, and not in the row. But in case the pks_attrs_names arg is empty and there is some pks_attrs_names in the row, we get that pks.
				if (!pks_attrs_names) {
					var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
					pks_attrs_names = properties.hasOwnProperty("matrix") && properties["matrix"].hasOwnProperty("pks_attrs_names") ? properties["matrix"]["pks_attrs_names"] : "";
				}
				
				if (pks_attrs_names)
					pks_attrs_names = MyWidgetResourceLib.fn.isArray(pks_attrs_names) ? pks_attrs_names : pks_attrs_names.replace(/;/g, ",").split(",");
				
				//if list_item_html
				if (list_item_html) {
					var prev_item_elm = null;
					var item_html = list_item_html.html;
					var item_parent = list_item_html.parent;
					var siblings_count = list_item_html.siblings_count;
					
					//prepare head_columns_pks
					var head_columns_pks = MyWidgetResourceLib.MatrixHandler.getMatrixHeadRowsColumnsPksByIndex(elm);
					
					//load items
					for (var i = 0, t = resources_name.length; i < t; i++) {
						var resource_name = resources_name[i];
						var resource_cache_key = resources_cache_key ? resources_cache_key[i] : null;
						var data = MyWidgetResourceLib.ResourceHandler.getLoadedSLAResult(resource_name, resource_cache_key);
						
						if (data) {
							MyWidgetResourceLib.fn.each(data, function(idx, record) {
						   		if (MyWidgetResourceLib.fn.isPlainObject(record)) {
									//prepare PKs string
									var pks_attrs = {};
									
									for (var j = 0, tj = pks_attrs_names.length; j < tj; j++) {
										var pk_attr_name = ("" + pks_attrs_names[j]).replace(/\s+/g, "");
										
										if (pk_attr_name)
											pks_attrs[pk_attr_name] = record.hasOwnProperty(pk_attr_name) ? record[pk_attr_name] : null;
									}
									
									//prepare html
									var html = item_html;
									
									//replace hashtags if apply to the current resource
									var inner_item_hash_tags_to_ignore = MyWidgetResourceLib.HashTagHandler.getWidgetResourceValueInnerItemsHtmlHashTagsBasedInResources(html, resource_name, resource_cache_key, idx);
									inner_item_hash_tags_to_ignore = inner_item_hash_tags_to_ignore.concat(MyWidgetResourceLib.HashTagHandler.getWidgetItemsHtmlHashTagsBasedInResources(html, resource_name, resource_cache_key, idx));
									html = MyWidgetResourceLib.HashTagHandler.replaceHtmlHashTagsWithResources(html, resource_name, resource_cache_key, idx, inner_item_hash_tags_to_ignore);
									
									//append html element to item_parent
									var item_elm = null;
									
									if (prev_item_elm) { //if multiple list_item_html, otherwise the elements will appear flipped and with the wrong order.
										prev_item_elm.insertAdjacentHTML('afterend', html);
										item_elm = prev_item_elm.nextElementSibling;
									}
									else if (list_item_html.prev && list_item_html.prev.parentNode) {
										list_item_html.prev.insertAdjacentHTML('afterend', html);
										item_elm = list_item_html.prev.nextElementSibling;
									}
									else {
										if (list_item_html.next && list_item_html.next.parentNode) {
											list_item_html.next.insertAdjacentHTML('beforebegin', html);
											item_elm = list_item_html.next.previousElementSibling;
										}
										else {
											item_parent.insertAdjacentHTML('beforeend', html);
											item_elm = item_parent.lastElementChild;
										}
										
										if (siblings_count)
											for (var q = 0; q < siblings_count; q++) 
												if (item_elm.previousElementSibling)
													item_elm = item_elm.previousElementSibling;
									}
									
									prev_item_elm = item_elm;
									
									var items_elms = [item_elm];
									
									if (siblings_count)
										for (var q = 0; q < siblings_count; q++) 
											if (prev_item_elm.nextElementSibling) {
												prev_item_elm = prev_item_elm.nextElementSibling;
												items_elms.push(prev_item_elm);
											}
									
									for (var q = 0, tq = items_elms.length; q < tq; q++) {
										item_elm = items_elms[q];
										
										//set PKs
										var pks_attrs_str = JSON.stringify(pks_attrs);
										item_elm.setAttribute("data-widget-pks-attrs", pks_attrs_str);
										
										//save loaded resource data in item_elm
										MyWidgetResourceLib.FieldHandler.saveWidgetLoadedResourceValues(item_elm, resource_name, resource_cache_key, idx);
										
										//set attributes according with the resources, if apply
										MyWidgetResourceLib.FieldHandler.setWidgetResourceAttributes(item_elm, resource_name, resource_cache_key, idx);
										
										//prepare data-widget-resource-value - only replace data outisde of the [data-widget-item], otherwise we are replacing the values that should only be replaced in the method drawMatrixResource.
										var sub_items = item_elm.querySelectorAll("[data-widget-item], [data-widget-item-add]");
										var sub_items_html = [];
										
										MyWidgetResourceLib.fn.each(sub_items, function(idy, sub_item) { //save html from [data-widget-item]
											sub_items_html.push(sub_item.outerHTML);
										});
										
										MyWidgetResourceLib.FieldHandler.setWidgetResourceValues(item_elm, resource_name, resource_cache_key, idx);
										
										MyWidgetResourceLib.fn.each(sub_items, function(idy, sub_item) { //re set the html of [data-widget-item]
											sub_item.insertAdjacentHTML('afterend', sub_items_html[idy]);
											sub_item.parentNode.removeChild(sub_item);
										});
										
										//prepare body-columns, by adding the pks_attrs_names from axis x and y:
										//- if from head row, get the "data-widget-pks-attrs" attribute from each column.
										//- if from body row, get the "data-widget-pks-attrs" attribute from each tr.
										var columns = item_elm.querySelectorAll("td, th, [data-widget-matrix-body-column]");
										
										MyWidgetResourceLib.fn.each(columns, function(idy, column) {
											//prepare axis y pks
											column.setAttribute("data-widget-matrix-axis-y-pks-attrs", pks_attrs_str);
											
											//prepare axis x pks
											var column_index = MyWidgetResourceLib.fn.getTableCellIndexes(column);
											column_index = column_index["col"];
											
											if (MyWidgetResourceLib.fn.isNumeric(column_index)) {
												var axis_x_pks = head_columns_pks[column_index];
												
												if (axis_x_pks)
													column.setAttribute("data-widget-matrix-axis-x-pks-attrs", axis_x_pks);
											}
										});
									}
								}
						   	});
						}
					}
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No list element in drawMatrixBodyRowResource method!");
		},
		
		/**
		 * Based in loaded resouces, draw the correspondent htmlin the matrix
		 */
		drawMatrixResource: function(elm, resources_name, resources_cache_key) {
			if (elm) {
				var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
				var matrix_properties = properties.hasOwnProperty("matrix") && MyWidgetResourceLib.fn.isPlainObject(properties["matrix"]) ? properties["matrix"] : {};
				var only_show_if_exist = matrix_properties["data_display_type"] == "show_if_exist";
				var data_allow_repeated = matrix_properties["data_allow_repeated"];
				
				//prepare pks_attrs_names
				var pks_attrs_names = matrix_properties.hasOwnProperty("pks_attrs_names") ? matrix_properties["pks_attrs_names"] : "";
				pks_attrs_names = MyWidgetResourceLib.fn.isArray(pks_attrs_names) ? pks_attrs_names : pks_attrs_names.replace(/;/g, ",").split(",");
				
				//prepare fks_attrs_names
				var fks_attrs_names = matrix_properties.hasOwnProperty("fks_attrs_names") ? matrix_properties["fks_attrs_names"] : [];
				var fks_attrs_names_by_axis_attr = {};
				
				if (fks_attrs_names)
					MyWidgetResourceLib.fn.each(fks_attrs_names, function(idx, fks_attrs_name) {
						if (MyWidgetResourceLib.fn.isPlainObject(fks_attrs_name) && fks_attrs_name["attr_name"] && fks_attrs_name["fk_attr_name"]) {
							var axis = fks_attrs_name["axis"];
							axis = axis == "x" || axis == "y" ? axis : "";
							
							if (!MyWidgetResourceLib.fn.isPlainObject(fks_attrs_names_by_axis_attr[axis]))
								fks_attrs_names_by_axis_attr[axis] = {};
							
							fks_attrs_names_by_axis_attr[axis][ fks_attrs_name["attr_name"] ] = fks_attrs_name["fk_attr_name"];
						}
					});
				
				//prepare available columns and correspondent list_items_html
				MyWidgetResourceLib.MatrixHandler.prepareMatrixDataColumnsHtml(elm);
				
				//get pks for axis x and y
				var get_axis_attr_name = function(axis, attr_name) {
					var name = fks_attrs_names_by_axis_attr[axis] && fks_attrs_names_by_axis_attr[axis].hasOwnProperty(attr_name) ? fks_attrs_names_by_axis_attr[axis][attr_name] : null;
					
					if (!name)
						name = fks_attrs_names_by_axis_attr[""] && fks_attrs_names_by_axis_attr[""].hasOwnProperty(attr_name) ? fks_attrs_names_by_axis_attr[""][attr_name] : null;
					
					return name ? name : attr_name;
				};
				
				//found items for specific pks_attrs
				var columns = elm.querySelectorAll("[data-widget-matrix-axis-y-pks-attrs], [data-widget-matrix-axis-x-pks-attrs]");
				var find_columns = function(pks_attrs) {
					var found_columns = [];
					
					MyWidgetResourceLib.fn.each(columns, function(idx, column) {
				   		var axis_x_pks = column.getAttribute("data-widget-matrix-axis-x-pks-attrs");
				   		var axis_y_pks = column.getAttribute("data-widget-matrix-axis-y-pks-attrs");
				   		
				   		axis_x_pks = axis_x_pks && axis_x_pks.substr(0, 1) == "{" ? MyWidgetResourceLib.fn.parseJson(axis_x_pks) : {};
				   		axis_y_pks = axis_y_pks && axis_y_pks.substr(0, 1) == "{" ? MyWidgetResourceLib.fn.parseJson(axis_y_pks) : {};
				   		
				   		//check if column pks exists in the axis_x_pks
				   		var pks_total = 0;
				   		var fks_x_total = 0;
				   		var fks_y_total = 0;
					   	
				   		for (var attr_name in pks_attrs) {
					   		pks_total++;
					   		var axis_x_name = get_axis_attr_name("x", attr_name);
					   		var axis_y_name = get_axis_attr_name("y", attr_name);
					   		
				   			if (pks_attrs[attr_name] == axis_x_pks[axis_x_name])
					   			fks_x_total++;
				   			
				   			if (pks_attrs[attr_name] == axis_y_pks[axis_y_name])
					   			fks_y_total++;
					   	}
					   	
					   	//Note that the pks_total can be bigger or smaller than the axis x + y. The idea is to find columns with the axis x or y equal to any pk attr in the pks_attrs.
					   	if (fks_x_total && fks_y_total/* && pks_total <= fks_x_total + fks_y_total*/)
					   		found_columns.push(column);
					   	else if (MyWidgetResourceLib.fn.isEmptyObject(axis_x_pks) && fks_y_total/* && pks_total <= fks_y_total*/)
					   		found_columns.push(column);
					   	else if (MyWidgetResourceLib.fn.isEmptyObject(axis_y_pks) && fks_x_total/* && pks_total <= fks_x_total*/)
					   		found_columns.push(column);
				   	});
				   	
				   	return found_columns;
				};
				
				//prepare html for each found item
				var prepare_item_html = function(pks_attrs, list_items_html, resource_name, resource_cache_key, idx) {
					var prev_item_elm = null;
					
					if (list_items_html)
		   				for (var j = 0, tj = list_items_html.length; j < tj; j++) {
							var list_item_html = list_items_html[j];
							var item_html = list_item_html.html;
							var item_parent = list_item_html.parent;
							
							//replace hashtags if apply to the current resource
							var inner_item_hash_tags_to_ignore = MyWidgetResourceLib.HashTagHandler.getWidgetResourceValueInnerItemsHtmlHashTagsBasedInResources(item_html, resource_name, resource_cache_key, idx);
							item_html = MyWidgetResourceLib.HashTagHandler.replaceHtmlHashTagsWithResources(item_html, resource_name, resource_cache_key, idx, inner_item_hash_tags_to_ignore);
							
							//append html element to item_parent
							var item_elm = null;
							
							if (list_item_html.prev && list_item_html.prev.parentNode) {
								var prev = list_item_html.prev;
								
								if (j > 0 && list_items_html[j - 1].prev == prev && prev_item_elm) //if multiple list_items_html, the previous one is the list_items_html[j - 1], otherwise the elements will appear flipped and with the wrong order.
									prev = prev_item_elm;
								
								prev.insertAdjacentHTML('afterend', item_html);
								item_elm = prev.nextElementSibling;
							}
							else if (list_item_html.next && list_item_html.next.parentNode) {
								list_item_html.next.insertAdjacentHTML('beforebegin', item_html);
								item_elm = list_item_html.next.previousElementSibling;
							}
							else {
								item_parent.insertAdjacentHTML('beforeend', item_html);
								item_elm = item_parent.lastElementChild;
							}
							
							//set PKs
							item_elm.setAttribute("data-widget-pks-attrs", JSON.stringify(pks_attrs));
							
							//save loaded resource data in item_elm
							MyWidgetResourceLib.FieldHandler.saveWidgetLoadedResourceValues(item_elm, resource_name, resource_cache_key, idx);
							
							//set attributes according with the resources, if apply
							MyWidgetResourceLib.FieldHandler.setWidgetResourceAttributes(item_elm, resource_name, resource_cache_key, idx);
							
							//prepare data-widget-resource-value
							MyWidgetResourceLib.FieldHandler.setWidgetResourceValues(item_elm, resource_name, resource_cache_key, idx);
						
							prev_item_elm = item_elm;
						}
				};
				var prepare_item_node = function(pks_attrs, item_elm, resource_name, resource_cache_key, idx) {
					//replace hashtags if apply to the current resource
					var item_html = item_elm.innerHTML;
					var inner_item_hash_tags_to_ignore = MyWidgetResourceLib.HashTagHandler.getWidgetResourceValueInnerItemsHtmlHashTagsBasedInResources(item_html, resource_name, resource_cache_key, idx);
					var new_item_html = MyWidgetResourceLib.HashTagHandler.replaceHtmlHashTagsWithResources(item_html, resource_name, resource_cache_key, idx, inner_item_hash_tags_to_ignore);
					
					if (item_html != new_item_html)
						item_elm.innerHTML = new_item_html;
					
					//set PKs
					item_elm.setAttribute("data-widget-pks-attrs", JSON.stringify(pks_attrs));
					
					//save loaded resource data in item_elm
					MyWidgetResourceLib.FieldHandler.saveWidgetLoadedResourceValues(item_elm, resource_name, resource_cache_key, idx);
					
					//set attributes according with the resources, if apply
					MyWidgetResourceLib.FieldHandler.setWidgetResourceAttributes(item_elm, resource_name, resource_cache_key, idx);
					
					//prepare data-widget-resource-value
					MyWidgetResourceLib.FieldHandler.setWidgetResourceValues(item_elm, resource_name, resource_cache_key, idx);
				};
				
				//load items
				var repeated_columns = [];
				var is_data_empty = true;
				//console.log(elm);
				
				for (var i = 0, t = resources_name.length; i < t; i++) {
					var resource_name = resources_name[i];
					var resource_cache_key = resources_cache_key ? resources_cache_key[i] : null;
					var data = MyWidgetResourceLib.ResourceHandler.getLoadedSLAResult(resource_name, resource_cache_key);
					/*console.log("resource_name:"+resource_name);
					console.log("resource_cache_key:"+resource_cache_key);
					console.log(data);
					console.log(MyWidgetResourceLib.ResourceHandler.cached_loaded_sla_results);*/
					
					if (data) {
						MyWidgetResourceLib.fn.each(data, function(idx, record) {
					   		is_data_empty = false;
					   		
					   		if (MyWidgetResourceLib.fn.isPlainObject(record)) {
								//prepare PKs string
								var pks_attrs = {};
								
								for (var j = 0, tj = pks_attrs_names.length; j < tj; j++) {
									var pk_attr_name = ("" + pks_attrs_names[j]).replace(/\s+/g, "");
									
									if (pk_attr_name)
										pks_attrs[pk_attr_name] = record.hasOwnProperty(pk_attr_name) ? record[pk_attr_name] : null;
								}
								
								//find pks in columns
								var found_columns = find_columns(pks_attrs);
			   					//console.log(pks_attrs);
				   				//console.log(found_columns);
				   				
								//prepare found columns with resource data
								if (found_columns)
					   				MyWidgetResourceLib.fn.each(found_columns, function(idy, found_column) {
					   					var is_repeated = repeated_columns.indexOf(found_column) != -1;
					   					
					   					if (data_allow_repeated || !is_repeated) {
					   						if (!is_repeated)
						   						repeated_columns.push(found_column);
					   						
					   						var is_column_item = found_column.hasAttribute("data-widget-item") || found_column.hasAttribute("data-widget-item-add");
							   				
							   				if (only_show_if_exist) {
							   					var list_items_html = MyWidgetResourceLib.fn.getNodeElementData(found_column, "list_items_html");
							   					
							   					if (list_items_html) {
								   					if (is_column_item) {
								   						found_column.innerHTML = list_items_html[0].html;
								   						prepare_item_node(pks_attrs, found_column, resource_name, resource_cache_key, idx);
								   					}
								   					else 
								   						prepare_item_html(pks_attrs, list_items_html, resource_name, resource_cache_key, idx);
								   				}
							   				}
							   				else {
							   					if (is_column_item)
							   						prepare_item_node(pks_attrs, found_column, resource_name, resource_cache_key, idx);
							   					else {
							   						var items = found_column.querySelectorAll("[data-widget-item], [data-widget-item-add]");
							   						
							   						MyWidgetResourceLib.fn.each(items, function(idw, item) {
							   							prepare_item_node(pks_attrs, item, resource_name, resource_cache_key, idx);
							   						});
							   					}
							   				}
							   			}
									});
							}
					   	});
					}
				}
				
				//replace hashtags for all columns not found.
				var get_item_attr_name_from_axis = function(axis, attr_name) {
					if (fks_attrs_names_by_axis_attr[axis])
						for (var item_attr_name in fks_attrs_names_by_axis_attr[axis]) {
							var axis_attr_name = fks_attrs_names_by_axis_attr[axis][item_attr_name];
							
							if (axis_attr_name == attr_name)
								return item_attr_name;
						}
					
					if (fks_attrs_names_by_axis_attr[""])
						for (var item_attr_name in fks_attrs_names_by_axis_attr[""]) {
							var axis_attr_name = fks_attrs_names_by_axis_attr[""][item_attr_name];
							
							if (axis_attr_name == attr_name)
								return item_attr_name;
						}
					
					return attr_name;
				};
				var get_axis_data = function(axis, column) {
					var data = {};
					var axis_pks = column.getAttribute("data-widget-matrix-axis-" + axis + "-pks-attrs");
			   		
			   		axis_pks = axis_pks && axis_pks.substr(0, 1) == "{" ? MyWidgetResourceLib.fn.parseJson(axis_pks) : {};
			   		
			   		if (MyWidgetResourceLib.fn.isPlainObject(axis_pks))
						for (var attr_name in axis_pks) {
							var item_attr_name = get_item_attr_name_from_axis(axis, attr_name); //convert key from axis to item key
							data[item_attr_name] = axis_pks[attr_name];
						}
					
					return data;
				};
				var prepare_not_found_column_item = function(pks_attrs, item) {
					//set PKs
					item.setAttribute("data-widget-pks-attrs", JSON.stringify(pks_attrs));
					
					/*Following code is DEPRECATED bc we only want to setWidgetDataValues for the found columns, otherwise we are settings values that don't exist in the DB. LEAVE THIS CODE COMMENTED!
					//save loaded data in item
					MyWidgetResourceLib.FieldHandler.saveWidgetLoadedDataValues(item, data);
					
					//set attributes according with the resources, if apply
					MyWidgetResourceLib.FieldHandler.setWidgetDataAttributes(item, data);
					
					//prepare data-widget-resource-value
					MyWidgetResourceLib.FieldHandler.setWidgetDataValues(item, data);*/
				};
				
				MyWidgetResourceLib.fn.each(columns, function(idw, column) {
					if (repeated_columns.indexOf(column) == -1) {
						repeated_columns.push(column);
						
						//prepare data
						var data_x = get_axis_data("x", column);
						var data_y = get_axis_data("y", column);
						var data = Object.assign({}, data_x, data_y);
						
						//replace hashtags according with data
						var item_html = column.innerHTML;
						var inner_item_hash_tags_to_ignore = MyWidgetResourceLib.HashTagHandler.getWidgetResourceValueInnerItemsHtmlHashTagsBasedInData(item_html, data);
						var new_item_html = MyWidgetResourceLib.HashTagHandler.replaceHtmlHashTagsWithData(item_html, data, null, inner_item_hash_tags_to_ignore);
						
						if (item_html != new_item_html)
							column.innerHTML = new_item_html;
						
						//prepare PKs string
						var pks_attrs = {};
						
						for (var j = 0, tj = pks_attrs_names.length; j < tj; j++) {
							var pk_attr_name = ("" + pks_attrs_names[j]).replace(/\s+/g, "");
							
							if (pk_attr_name)
								pks_attrs[pk_attr_name] = data.hasOwnProperty(pk_attr_name) ? data[pk_attr_name] : null;
						}
						
						//prepare column items
						var is_column_item = column.hasAttribute("data-widget-item") || column.hasAttribute("data-widget-item-add");
						
						if (is_column_item)
							prepare_not_found_column_item(pks_attrs, column);
						else {
							var items = column.querySelectorAll("[data-widget-item], [data-widget-item-add]");
	   						
	   						MyWidgetResourceLib.fn.each(items, function(idw, item) {
	   							prepare_not_found_column_item(pks_attrs, item);
	   						});
						}
					}
				});
				
				//show empty row
				var empty_elm = elm.querySelector("[data-widget-empty]");
				
				if (empty_elm) {
					if (is_data_empty)
						MyWidgetResourceLib.fn.show(empty_elm);
					else
						MyWidgetResourceLib.fn.hide(empty_elm);
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No list element in drawMatrixResource method!");
		},
		
		/**
		 * Based in loaded resouces, draw the correspondent htmlin the matrix
		 */
		updateMatrixResource: function(elm, resources_name, resources_cache_key, opts) {
			if (elm) {
				opts = opts ? opts : {};
				
				var search_attrs = opts["search_attrs"];
				var search_attrs_str = search_attrs ? JSON.stringify(search_attrs) : "";
				var is_add_item = opts["add_item"];
				var is_update_item = opts["update_item"];
				var is_remove_item = opts["remove_item"];
				
				if (is_add_item) {
					//purge cache
					MyWidgetResourceLib.MatrixHandler.purgeCachedLoadParentMatrixResource(elm);
					
					//reload list.
			   		MyWidgetResourceLib.MatrixHandler.loadMatrixResource(elm, {force: true});
				}
				else if (search_attrs) {
					var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
					var matrix_properties = properties.hasOwnProperty("matrix") && MyWidgetResourceLib.fn.isPlainObject(properties["matrix"]) ? properties["matrix"] : {};
					var pks_attrs_names = matrix_properties.hasOwnProperty("pks_attrs_names") ? matrix_properties["pks_attrs_names"] : "";
					pks_attrs_names = MyWidgetResourceLib.fn.isArray(pks_attrs_names) ? pks_attrs_names : pks_attrs_names.replace(/;/g, ",").split(",");
					
					var found = function(obj_1, obj_2) { //Do not use JSON.stringify to compare bc the order may be different
						//compare if 2 objects contains the same properties and values, even if the order is different
						var same = MyWidgetResourceLib.fn.isPlainObject(obj_1) && !MyWidgetResourceLib.fn.isEmptyObject(obj_1) && MyWidgetResourceLib.fn.isPlainObject(obj_2);
						
						if (same)
							for (var k in obj_1)
								if (!obj_2.hasOwnProperty(k) || obj_1[k] != obj_2[k]) {
									same = false;
									break;
								}
						
						return same;
					};
					
					//prepare html for each found item
					var prepare_item_html = function(pks_attrs, prev_item_elm, list_items_html, resource_name, resource_cache_key, idx) {
						if (list_items_html)
			   				for (var j = 0, tj = list_items_html.length; j < tj; j++) {
								var list_item_html = list_items_html[j];
								var item_html = list_item_html.html;
								var item_parent = list_item_html.parent;
								
								//replace hashtags if apply to the current resource
								var inner_item_hash_tags_to_ignore = MyWidgetResourceLib.HashTagHandler.getWidgetResourceValueInnerItemsHtmlHashTagsBasedInResources(item_html, resource_name, resource_cache_key, idx);
								item_html = MyWidgetResourceLib.HashTagHandler.replaceHtmlHashTagsWithResources(item_html, resource_name, resource_cache_key, idx, inner_item_hash_tags_to_ignore);
								
								//append html element to item_parent
								var item_elm = null;
								
								if (list_item_html.prev && list_item_html.prev.parentNode) {
									var prev = list_item_html.prev;
									
									if ((j == 0 || list_items_html[j - 1].prev == prev) && prev_item_elm) //if multiple list_items_html, the previous one is the list_items_html[j - 1], otherwise the elements will appear flipped and with the wrong order.
										prev = prev_item_elm;
									
									prev.insertAdjacentHTML('afterend', item_html);
									item_elm = prev.nextElementSibling;
								}
								else if (list_item_html.next && list_item_html.next.parentNode) {
									list_item_html.next.insertAdjacentHTML('beforebegin', item_html);
									item_elm = list_item_html.next.previousElementSibling;
								}
								else {
									item_parent.insertAdjacentHTML('beforeend', item_html);
									item_elm = item_parent.lastElementChild;
								}
								
								//set PKs
								item_elm.setAttribute("data-widget-pks-attrs", JSON.stringify(pks_attrs));
								
								//save loaded resource data in item_elm
								MyWidgetResourceLib.FieldHandler.saveWidgetLoadedResourceValues(item_elm, resource_name, resource_cache_key, idx);
								
								//set attributes according with the resources, if apply
								MyWidgetResourceLib.FieldHandler.setWidgetResourceAttributes(item_elm, resource_name, resource_cache_key, idx);
								
								//prepare data-widget-resource-value
								MyWidgetResourceLib.FieldHandler.setWidgetResourceValues(item_elm, resource_name, resource_cache_key, idx);
							
								prev_item_elm = item_elm;
							}
					};
					var prepare_item_node = function(pks_attrs, item_elm, resource_name, resource_cache_key, idx) {
						//set PKs
						item_elm.setAttribute("data-widget-pks-attrs", JSON.stringify(pks_attrs));
						
						//save loaded resource data in item_elm
						MyWidgetResourceLib.FieldHandler.saveWidgetLoadedResourceValues(item_elm, resource_name, resource_cache_key, idx);
						
						//set attributes according with the resources, if apply
						MyWidgetResourceLib.FieldHandler.setWidgetResourceAttributes(item_elm, resource_name, resource_cache_key, idx);
						
						//prepare data-widget-resource-value
						MyWidgetResourceLib.FieldHandler.setWidgetResourceValues(item_elm, resource_name, resource_cache_key, idx);
					};
					
					var exists_data_records = false;
					var exists_data_record_item = false;
					var exists_matrix_item = false;
					var items = elm.querySelectorAll("[data-widget-item][data-widget-pks-attrs], [data-widget-item-add][data-widget-pks-attrs]");
					
					for (var i = 0, t = resources_name.length; i < t; i++) {
						var resource_name = resources_name[i];
						var resource_cache_key = resources_cache_key ? resources_cache_key[i] : null;
						var data = MyWidgetResourceLib.ResourceHandler.getLoadedSLAResult(resource_name, resource_cache_key);
						
						if (data) {
							MyWidgetResourceLib.fn.each(data, function(idx, record) {
						   		if (MyWidgetResourceLib.fn.isPlainObject(record)) {
									exists_data_records = true;
									
									//prepare PKs string
									var pks_attrs = {};
									
									for (var j = 0, tj = pks_attrs_names.length; j < tj; j++) {
										var pk_attr_name = ("" + pks_attrs_names[j]).replace(/\s+/g, "");
										
										if (pk_attr_name)
											pks_attrs[pk_attr_name] = record.hasOwnProperty(pk_attr_name) ? record[pk_attr_name] : null;
									}
									
									var pks_attrs_str = JSON.stringify(pks_attrs);
									
									if (found(search_attrs, pks_attrs))
										exists_data_record_item = true;
									
									//prepare html
									for (var j = 0, tj = items.length; j < tj; j++) {
										var item = items[j];
										var item_pks_attrs_str = item.getAttribute("data-widget-pks-attrs");
										var item_pks_attrs = MyWidgetResourceLib.fn.parseJson(item_pks_attrs_str);
										
										//update existent item
										if (found(pks_attrs, item_pks_attrs)) {
											exists_matrix_item = true;
											
											if (is_update_item) {
												var column = item.closest("[data-widget-matrix-axis-y-pks-attrs], [data-widget-matrix-axis-x-pks-attrs]");
												
												if (column) {
													var is_column_item = item.hasAttribute("data-widget-matrix-axis-y-pks-attrs") || item.hasAttribute("data-widget-matrix-axis-x-pks-attrs");
									   				var list_items_html = MyWidgetResourceLib.fn.getNodeElementData(column, "list_items_html");
								   					
								   					if (list_items_html) {
									   					//prepare html and insert after item, the remove item
									   					prepare_item_html(item_pks_attrs, item, list_items_html, resource_name, resource_cache_key, idx);
									   					item.parentNode.removeChild(item);
									   				}
									   				else
								   						prepare_item_node(item_pks_attrs, item, resource_name, resource_cache_key, idx);
								   				}
								   				else 
								   					prepare_item_node(item_pks_attrs, item, resource_name, resource_cache_key, idx);
											}
											
											break;
										}
									}
								}
						   	});
						}
				   	}
				   	
				   	if (is_update_item && !exists_matrix_item /* && !exists_data_records*/) { //it means that the PK was changed, so we refresh the all list, bc there is no element anymore
				   		//purge cache
						MyWidgetResourceLib.MatrixHandler.purgeCachedLoadParentMatrixResource(elm);
						
						//reload list.
				   		MyWidgetResourceLib.MatrixHandler.loadMatrixResource(elm, {force: true});
				   	}
				   	else if (is_remove_item && !exists_data_record_item) { //remove item
				   		var removed = false;
				   		var is_page_empty = true;
				   		
				   		for (var j = 0, tj = items.length; j < tj; j++) {
							var item = items[j];
							var item_pks_attrs_str = item.getAttribute("data-widget-pks-attrs");
							var item_pks_attrs = MyWidgetResourceLib.fn.parseJson(item_pks_attrs_str);
							
							if (found(search_attrs, item_pks_attrs)) {
								item.parentNode.removeChild(item);
								removed = true;
							}
							else
								is_page_empty = false;
						}
						
						if (removed) {
							//purge cache
							MyWidgetResourceLib.MatrixHandler.purgeCachedLoadParentMatrixResource(elm);
							
							//check if page is empty, and if yes, reload matrix.
							if (is_page_empty) {
								//When we delete a record from the Edit Popup, we cannot replicate this behaviour, so we simply refresh the matrix.
								MyWidgetResourceLib.MatrixHandler.loadMatrixResource(elm, {force: true});
							}
						}
					}
				}
			}
			else
				MyWidgetResourceLib.MessageHandler.showErrorMessage("Error: No matrix element in updateMatrixResource method!");
		},
		
		purgeCachedLoadParentMatrixResource: function(elm) {
			var widget = elm.closest("[data-widget-matrix]");
			
			if (widget) {
				MyWidgetResourceLib.ResourceHandler.purgeWidgetResources(widget, "load");
				
				//purge cache from children, if exist
				var children = elm.querySelectorAll("[data-widget-resources]");
				
				if (children)
					for (var i = 0, t = children.length; i < t; i++) {
						var child = children[i];
						MyWidgetResourceLib.ResourceHandler.purgeWidgetResources(child, "load");
					}
			}
		},
		
		/* GROUP FUNCTIONS */
		
		/**
		 * Group similar columns if apply
		 */
		groupMatrixColumns: function(elm) {
			if (elm) {
				var columns = elm.querySelectorAll("[data-widget-matrix-head-column-group][data-widget-resource-value]");
				
				MyWidgetResourceLib.fn.each(columns, function (idx, column) {
					if (column.parentNode) { //check if it was not yet removed
						var column_index = MyWidgetResourceLib.fn.getTableCellIndexes(column);
						var inside_of_head_row = column.parentNode.hasAttribute("data-widget-matrix-head-row") || column.parentNode.closest("thead") || (column.nodeName.toLowerCase() == "th" && column_index["row"] == 0); //if inside of head-row or thead or is the first row of the table
						var col_value = MyWidgetResourceLib.FieldHandler.getFieldValue(column);
						var row = column.parentNode;
						
						if (inside_of_head_row) {
							do {
								var next_col = column.nextElementSibling;
								
								if (next_col) {
									var next_col_value = MyWidgetResourceLib.FieldHandler.getFieldValue(next_col);
									
									if (col_value == next_col_value) {
										var colspan = column.getAttribute("colspan");
										colspan = MyWidgetResourceLib.fn.isNumeric(colspan) ? parseInt(colspan) : 1;
										colspan++;
										
										column.setAttribute("colspan", colspan);
										row.removeChild(next_col);
									}
									else
										break;
								}
							}
							while (next_col);
						}
						else {
							var col_index = column_index["col"];
							//console.log("START NEW COLUMN");
							//console.log(column);
							//console.log("col_index:"+col_index);
							
							do {
								var next_row = row.nextElementSibling;
								
								if (next_row) {
									var next_col_index = MyWidgetResourceLib.fn.getTableRowChildrenIndexByTableCellIndex(next_row, col_index);
									var next_col = next_row.children[next_col_index];
									//console.log(next_col);
									//console.log("next_col_index:"+next_col_index);
									
									if (next_col) {
										var next_col_value = MyWidgetResourceLib.FieldHandler.getFieldValue(next_col);
										
										if (col_value == next_col_value) {
											var rowspan = column.getAttribute("rowspan");
											rowspan = MyWidgetResourceLib.fn.isNumeric(rowspan) ? parseInt(rowspan) : 1;
											rowspan++;
											
											column.setAttribute("rowspan", rowspan);
											next_row.removeChild(next_col);
										}
										else {
											break;
											//console.log(next_col);
											//console.log("next_col_index:"+next_col_index);
										}
									}
								}
								
								row = next_row;
							}
							while (next_row);
						}
					}
				});
			}
		},
		
		/* PREPARE FUNCTIONS */
		
		/**
		 * Prepare related siblings from a head row element.
		 * 
		 * Steps: 
		 * - Check if next siblings (rows siblings) are [data-widget-matrix-previous-related] and if so save them into a variable. 
		 * - Then for each sibling, get the [data-widget-matrix-head-column] and [data-widget-matrix-previous-related] elements and back up the outer html in the sibling row.
		 * - At the end backup the siblings in the main row element.
		 */
		prepareMatrixHeadRowSiblingsHtml: function(elm) {
			var are_siblings_set = MyWidgetResourceLib.fn.getNodeElementData(elm, "are_siblings_set");
			
			if (!are_siblings_set) {
				var siblings = MyWidgetResourceLib.MatrixHandler.getMatrixElmSiblings(elm);
				
				MyWidgetResourceLib.fn.each(siblings, function (idx, sibling) {
					MyWidgetResourceLib.MatrixHandler.prepareMatrixHeadRowColumnsHtml(sibling);
				});
				
				MyWidgetResourceLib.fn.setNodeElementData(elm, "siblings", siblings);
				MyWidgetResourceLib.fn.setNodeElementData(elm, "are_siblings_set", true);
			}
		},
			
		/**
		 * Prepare a row element with some default html correspondent to inner columns.
		 * This method should only be called by the loadMatrixHeadRowResource which corresponds to the element [data-widget-matrix-head-row]
		 * 
		 * Steps: 
		 * - Get the [data-widget-matrix-head-column] and [data-widget-matrix-previous-related] elements and back up the outer html in the elm.
		 * - delete [data-widget-matrix-head-column] and [data-widget-matrix-previous-related] elements.
		 */
		prepareMatrixHeadRowColumnsHtml: function(elm) {
			var is_columns_html_set = MyWidgetResourceLib.fn.getNodeElementData(elm, "is_columns_html_set");
			
			if (!is_columns_html_set) {
				var columns_html = new Array();
				var columns = elm.querySelectorAll("[data-widget-matrix-head-column], [data-widget-matrix-body-column]");
				
				if (columns) {
					MyWidgetResourceLib.fn.each(columns, function (idx, item) {
						if (!item.hasAttribute("data-widget-matrix-previous-related")) {
							var column_html = MyWidgetResourceLib.MatrixHandler.getMatrixHeadColumnHtml(item);
							columns_html.push(column_html);
						}
					});
					
					//remove columns only after the getMatrixHeadColumnHtml, otherwsie the column_indexes will be messed up.
					var deleted_cols = [];
					
					MyWidgetResourceLib.fn.each(columns, function (idx, item) {
						if (deleted_cols.indexOf(item) == -1) {
							//get related siblings
							var siblings = MyWidgetResourceLib.MatrixHandler.getMatrixElmSiblings(item);
							
							//remove item and related siblings
							deleted_cols.push(item);
							item.parentNode.removeChild(item);
							
							MyWidgetResourceLib.fn.each(siblings, function (idx, sibling) {
								if (deleted_cols.indexOf(sibling) == -1) {
									deleted_cols.push(sibling);
									sibling.parentNode.removeChild(sibling);
								}
							});
						}
					});
				}
				
				MyWidgetResourceLib.fn.setNodeElementData(elm, "columns_html", columns_html);
				MyWidgetResourceLib.fn.setNodeElementData(elm, "is_columns_html_set", true);
			}
		},
		
		/**
		 * Prepare the matrix data columns elements with some default html correspondent to inner [data-widget-item].
		 * 
		 * Steps: 
		 * - Get the columns [data-widget-matrix-axis-y-pks-attrs], [data-widget-matrix-axis-x-pks-attrs].
		 * - set the list_items_html for each element.
		 */
		prepareMatrixDataColumnsHtml: function(elm) {
			var properties = MyWidgetResourceLib.fn.getWidgetProperties(elm);
			var matrix_properties = properties.hasOwnProperty("matrix") && MyWidgetResourceLib.fn.isPlainObject(properties["matrix"]) ? properties["matrix"] : {};
			var only_show_if_exist = matrix_properties["data_display_type"] == "show_if_exist";
			
			//get siblings that are not reserved siblings
			var get_sibling_handler = function(type, item) {
				var sibling = type = "prev" ? item.previousElementSibling : item.nextElementSibling;
				
				if (sibling && (sibling.hasAttribute("data-widget-item") || sibling.hasAttribute("data-widget-item-add") || sibling.hasAttribute("data-widget-empty") || sibling.hasAttribute("data-widget-loading")))
					sibling = get_sibling_handler(type, sibling);
				
				return sibling;
			};
			
			var get_item_html_handler = function(item, is_inner_html) {
				//get html
				var item_original_html = is_inner_html ? item.innerHTML : item.outerHTML;
				
				//check if item needs to be inited (this is, if contains any attribute data-widget-item-resources-load) and if yes, inited that html, but before anything else...
				if (!item.classList.contains("template-widget") && (item.hasAttribute("data-widget-item-resources-load") || item.hasAttribute("data-widget-resources-load"))) {
					item.removeAttribute("data-widget-item-resources-load");
					item.removeAttribute("data-widget-resources-load");
					
					MyWidgetResourceLib.ResourceHandler.loadWidgetResource(item, {async: false}); //must be async false otherwise we will loose the the real attribute values, when the load function gets executed
				}
				
				var item_widgets = item.querySelectorAll("[data-widget-item-resources-load]:not(.template-widget), [data-widget-resources-load]:not(.template-widget)");
				
				if (item_widgets)
					MyWidgetResourceLib.fn.each(item_widgets, function (idx, item_widget) { //:not(.template-widget) is very important so it doesn't load the resource when we are editing this widget through the LayoutUIEditor.
						item_widget.removeAttribute("data-widget-item-resources-load");
						item_widget.removeAttribute("data-widget-resources-load");
						
						MyWidgetResourceLib.ResourceHandler.loadWidgetResource(item_widget, {async: false}); //must be async false otherwise we will loose the the real attribute values, when the load function gets executed
					});
				
				//save default item html
				item.setAttribute("data-widget-item-loaded", "");
				
				var item_new_html = is_inner_html ? item.innerHTML : item.outerHTML;
				
				//return column data
				return {
					html: item_new_html,
					original_html: item_original_html,
					parent: is_inner_html ? item : item.parentNode,
					prev: is_inner_html ? null : get_sibling_handler("prev", item),
					next: is_inner_html ? null : get_sibling_handler("next", item)
				};
			};
			
			//prepare columns html
			var columns = elm.querySelectorAll("[data-widget-matrix-axis-y-pks-attrs], [data-widget-matrix-axis-x-pks-attrs]");
			
			MyWidgetResourceLib.fn.each(columns, function(idx, column) {
				var exists = MyWidgetResourceLib.fn.existsNodeElementData(column, "list_items_html");
				var is_column_item = column.hasAttribute("data-widget-item") || column.hasAttribute("data-widget-item-add");
				
				if (!exists) { //save html from column
					var list_items_html = [];
   					
					if (is_column_item) {
						var list_item_html = get_item_html_handler(column, true);
						list_items_html.push(list_item_html);
						
						//empty column
						if (only_show_if_exist)
							column.innerHTML = "";
					}
					else {
						var items = column.querySelectorAll("[data-widget-item], [data-widget-item-add]");
						
						MyWidgetResourceLib.fn.each(items, function(idx, item) {
							var list_item_html = get_item_html_handler(item, false);
							list_items_html.push(list_item_html);
							
							if (only_show_if_exist)
								item.parentNode.removeChild(item);
						});
					}
					
					//only add there are [data-widget-item] or [data-widget-item-add], otherwise it may be a disguised head column in the body
					if (list_items_html.length > 0)
						MyWidgetResourceLib.fn.setNodeElementData(column, "list_items_html", list_items_html);
				}
				else { //set original html in column
					var list_items_html = MyWidgetResourceLib.fn.setNodeElementData(column, "list_items_html");
					
					if (is_column_item) { //set original html
						if (only_show_if_exist) //empty column
							column.innerHTML = "";
						else //set original html
							column.innerHTML = list_items_html[0].html;
					}
					else {
						//delete previous items
						var items = column.querySelectorAll("[data-widget-item], [data-widget-item-add]");
						
						MyWidgetResourceLib.fn.each(items, function(idx, item) {
							item.parentNode.removeChild(item);
						});
						
						//add original items, if not only_show_if_exist
						if (!only_show_if_exist) {
							var prev_item_elm = null;
							
							for (var j = 0, tj = list_items_html.length; j < tj; j++) {
								var list_item_html = list_items_html[j];
								var item_html = list_item_html.html;
								var item_parent = list_item_html.parent;
								
								//append html element to item_parent
								var item_elm = null;
								
								if (list_item_html.prev && list_item_html.prev.parentNode) {
									var prev = list_item_html.prev;
									
									if (j > 0 && list_items_html[j - 1].prev == prev && prev_item_elm) //if multiple list_items_html, the previous one is the list_items_html[j - 1], otherwise the elements will appear flipped and with the wrong order.
										prev = prev_item_elm;
									
									prev.insertAdjacentHTML('afterend', item_html);
									item_elm = prev.nextElementSibling;
								}
								else if (list_item_html.next && list_item_html.next.parentNode) {
									list_item_html.next.insertAdjacentHTML('beforebegin', item_html);
									item_elm = list_item_html.next.previousElementSibling;
								}
								else {
									item_parent.insertAdjacentHTML('beforeend', item_html);
									item_elm = item_parent.lastElementChild;
								}
								
								prev_item_elm = item_elm;
							}
						}
					}
				}
			});
		},
		
		/**
		 * get column data with html, prev and next siblings, etc...
		 */
		getMatrixHeadColumnHtml: function(elm) {
			//get siblings that are not reserved siblings
			var get_sibling_handler = function(type, item) {
				var sibling = type = "prev" ? item.previousElementSibling : item.nextElementSibling;
				
				if (sibling && (sibling.hasAttribute("data-widget-matrix-head-column") || sibling.hasAttribute("data-widget-matrix-body-column") || sibling.hasAttribute("data-widget-matrix-previous-related") || sibling.hasAttribute("data-widget-loading") || sibling.hasAttribute("data-widget-empty")))
					sibling = get_sibling_handler(type, sibling);
				
				return sibling;
			};
			var get_item_html_handler = function(item) {
				//show column
				var is_shown = MyWidgetResourceLib.fn.getNodeElementData(item, "is_shown");
				
				if (is_shown)
					MyWidgetResourceLib.fn.show(item);
				
				//get html
				var item_original_html = item.outerHTML;
				
				//check if item needs to be inited (this is, if contains any attribute data-widget-item-resources-load) and if yes, inited that html, but before anything else...
				if (!item.classList.contains("template-widget") && (item.hasAttribute("data-widget-item-resources-load") || item.hasAttribute("data-widget-resources-load"))) {
					item.removeAttribute("data-widget-item-resources-load");
					item.removeAttribute("data-widget-resources-load");
					
					MyWidgetResourceLib.ResourceHandler.loadWidgetResource(item, {async: false}); //must be async false otherwise we will loose the the real attribute values, when the load function gets executed
				}
				
				var item_widgets = item.querySelectorAll("[data-widget-item-resources-load]:not(.template-widget), [data-widget-resources-load]:not(.template-widget)");
				
				if (item_widgets)
					MyWidgetResourceLib.fn.each(item_widgets, function (idx, item_widget) { //:not(.template-widget) is very important so it doesn't load the resource when we are editing this widget through the LayoutUIEditor.
						item_widget.removeAttribute("data-widget-item-resources-load");
						item_widget.removeAttribute("data-widget-resources-load");
						
						MyWidgetResourceLib.ResourceHandler.loadWidgetResource(item_widget, {async: false}); //must be async false otherwise we will loose the the real attribute values, when the load function gets executed
					});
				
				//save default item html
				item.setAttribute("data-widget-item-loaded", "");
				
				var item_new_html = item.outerHTML;
				
				return [item_original_html, item_new_html];
			};
				
			//get item html
			var html = get_item_html_handler(elm);
			var item_original_html = html[0];
			var item_new_html = html[1];
			
			//get related siblings html
			var siblings = MyWidgetResourceLib.MatrixHandler.getMatrixElmSiblings(elm);
			
			MyWidgetResourceLib.fn.each(siblings, function (idx, sibling) {
				var html = get_item_html_handler(sibling);
				item_original_html += html[0];
				item_new_html += html[1];
			});
			
			var column_index = MyWidgetResourceLib.fn.getNodeElementData(elm, "column_index");
			//console.log("column_index:"+column_index);
			
			//return column data
			return {
				html: item_new_html,
				original_html: item_original_html,
				parent: elm.parentNode,
				prev: get_sibling_handler("prev", elm),
				next: get_sibling_handler("next", elm),
				siblings_count: siblings.length,
				column_index: column_index
			};
		},
		
		/**
		 * get elm siblings
		 */
		getMatrixElmSiblings: function(elm) {
			var siblings = new Array();
			var next = elm.nextElementSibling;
			
			do {
				if (next && next.hasAttribute("data-widget-matrix-previous-related")) {
					siblings.push(next);
					
					next = next.nextElementSibling;
				}
				else
					break;
			}
			while (next);
			
			return siblings;
		},
		
		/**
		 * prepare column html inside of head row
		 * 
		 * Steps:
		 * - Get column outer html and back it up
		 * - Check if next siblings (columns siblings) are [data-widget-matrix-previous-related], and if so, append their outer html to main outer html
		 */
		prepareMatrixHeadRowHeadColumnHtml: function(elm) {
			var is_column_html_set = MyWidgetResourceLib.fn.getNodeElementData(elm, "is_column_html_set");
			
			if (!is_column_html_set) {
				var column_html = MyWidgetResourceLib.MatrixHandler.getMatrixHeadColumnHtml(elm);
				var siblings = MyWidgetResourceLib.MatrixHandler.getMatrixElmSiblings(elm);
				
				MyWidgetResourceLib.fn.setNodeElementData(elm, "column_html", column_html);
				MyWidgetResourceLib.fn.setNodeElementData(elm, "is_column_html_set", true);
			}
		},
		
		/**
		 * prepare column html inside of body row
		 * 
		 * Steps:
		 * - Get parent [data-widget-matrix-body-row] or "tr" outer html and back it up
		 *   Note that we should hide all other [data-widget-matrix-head-column] and correspondent [data-widget-matrix-previous-related] that are not related with the current column. 
		 *   This makes possible the case where we have multiple head columns inside of a body row with different resources to load that call this handler. In this case, we should have the body row repeated for each individual load resource, this is, for each individual head column.
		 * - Check if next siblings (rows siblings) are [data-widget-matrix-previous-related], and if so, append their outer html to main outer html
		 */
		prepareMatrixBodyRowHeadColumnHtml: function(elm) {
			var elm_row = elm.closest("[data-widget-matrix-body-row], tr");
			
			if (elm_row) {
				//Note that we should hide all other [data-widget-matrix-head-column] and correspondent [data-widget-matrix-previous-related] that are not related with the current column. 
				//This makes possible the case where we have multiple head columns inside of a body row with different resources to load that call this handler. In this case, we should have the body row repeated for each individual load resource, this is, for each individual head column.
				var row_head_columns = elm_row.querySelectorAll("[data-widget-matrix-head-column]:not([data-widget-matrix-previous-related])");
				var head_columns_count = row_head_columns ? row_head_columns.length : 0;
				var selector = MyWidgetResourceLib.fn.getElementPathSelector(elm, elm_row);
				
				if (head_columns_count > 1 && selector) {
					var deleted_row_columns = [];
					var prepare_row_handler = function(row) {
						var row_cloned = row.cloneNode(true);
						
						//set is_shown in row
						var is_shown = MyWidgetResourceLib.fn.getNodeElementData(row, "is_shown");
						MyWidgetResourceLib.fn.setNodeElementData(row_cloned, "is_shown", is_shown);
						
						//set is_shown row in columns
						var columns = row.querySelectorAll("[data-widget-matrix-head-column], [data-widget-matrix-body-column], [data-widget-matrix-previous-related]");
						var cloned_columns = row_cloned.querySelectorAll("[data-widget-matrix-head-column], [data-widget-matrix-body-column], [data-widget-matrix-previous-related]");
						
						if (columns)
							MyWidgetResourceLib.fn.each(columns, function (idx, column) {
								var is_shown = MyWidgetResourceLib.fn.getNodeElementData(column, "is_shown");
								MyWidgetResourceLib.fn.setNodeElementData(cloned_columns[idx], "is_shown", is_shown);
							});
						
						//delete all other [data-widget-matrix-head-column] and correspondent [data-widget-matrix-previous-related] that are not related with this elm
						var elm_cloned = row_cloned.querySelector(selector);
						var deleted_columns = 0;
						
						if (elm_cloned)
							for (var i = 0; i < row_cloned.children.length; i++) {
								var child = row_cloned.children[i];
								
								if (child == elm_cloned) {
									for (var j = i + 1; j < row_cloned.children.length; j++) {
										var child = row_cloned.children[j];
										
										if (!child.hasAttribute("data-widget-matrix-previous-related")) {
											i = j - 1;
											break;
										}
									}
								}
								else if (child.hasAttribute("data-widget-matrix-head-column") || child.hasAttribute("data-widget-matrix-previous-related")) {
									row_cloned.removeChild(child);
									i--;
									
									deleted_columns++;
								}
							}
						
						if (deleted_columns > 0)
							deleted_row_columns.push(deleted_columns);
						
						return row_cloned;
					};
					
					//clone row
					var parent = elm_row.parentNode;
					var elm_row_cloned = prepare_row_handler(elm_row);
					parent.insertBefore(elm_row_cloned, elm_row);
					
					//clone siblings
					var siblings = MyWidgetResourceLib.MatrixHandler.getMatrixElmSiblings(elm_row);
					var siblings_cloned = [];
					
					MyWidgetResourceLib.fn.each(siblings, function (idx, sibling) {
						var sibling_cloned = prepare_row_handler(sibling);
						parent.insertBefore(sibling_cloned, elm_row);
						siblings_cloned.push(sibling_cloned);
					});
					
					MyWidgetResourceLib.MatrixHandler.prepareMatrixBodyRowHtml(elm_row_cloned);
					
					//udpate list_item_html
					var list_item_html = MyWidgetResourceLib.fn.getNodeElementData(elm_row_cloned, "list_item_html");
					
					if (list_item_html) {
						list_item_html.head_column_index = Array.prototype.indexOf.call(elm_row.children, elm);
						list_item_html.head_columns_count = head_columns_count;
					}
					
					MyWidgetResourceLib.fn.setNodeElementData(elm_row, "list_item_html", list_item_html);
					
					//remove clones
					parent.removeChild(elm_row_cloned);
					
					MyWidgetResourceLib.fn.each(siblings_cloned, function (idx, sibling_cloned) {
						parent.removeChild(sibling_cloned);
					});
					
					//update colspan of loading and empty widgets if exists
					if (deleted_row_columns.length > 0) {
						var rows = parent.querySelectorAll("[data-widget-loading], [data-widget-empty]");
						
						if (rows && rows.length > 0) {
							var min_columns_deleted = 0;
							
							for (var i = 0, t = deleted_row_columns.length; i < t; i++)
								if (min_columns_deleted == 0 || min_columns_deleted > deleted_row_columns[i])
									min_columns_deleted = deleted_row_columns[i];
							
							if (min_columns_deleted > 0)
								MyWidgetResourceLib.fn.each(rows, function (idx, row) {
									var colspan_to_reduce = MyWidgetResourceLib.fn.getNodeElementData(row, "colspan_to_reduce");
									colspan_to_reduce = MyWidgetResourceLib.fn.isNumeric(colspan_to_reduce) ? parseInt(colspan_to_reduce) : 0;
									
									if (min_columns_deleted > colspan_to_reduce) //save bigger colspan_to_reduce. Not the smaller!
										MyWidgetResourceLib.fn.setNodeElementData(row, "colspan_to_reduce", min_columns_deleted);
								});
						}
					}
				}
				else
					MyWidgetResourceLib.MatrixHandler.prepareMatrixBodyRowHtml(elm_row);
			}
			
			return elm_row;
		},
			
		/**
		 * Prepare a row element with some default html
		 * This method should only be called by the loadMatrixBodyRowResource which corresponds to the element [data-widget-matrix-body-row]
		 * 
		 * Steps: 
		 * - Get row outer html and back it up
		 * - Check if next siblings (rows siblings) are [data-widget-matrix-previous-related], and if so, append their outer html to main outer html
		 */
		prepareMatrixBodyRowHtml: function(elm) {
			var is_list_item_html_set = MyWidgetResourceLib.fn.getNodeElementData(elm, "is_list_item_html_set");
			
			if (!is_list_item_html_set) {
				var list_item_html = MyWidgetResourceLib.MatrixHandler.getMatrixHBodyRowHtml(elm);
				
				MyWidgetResourceLib.fn.setNodeElementData(elm, "list_item_html", list_item_html);
				MyWidgetResourceLib.fn.setNodeElementData(elm, "is_list_item_html_set", true);
			}
		},
		
		/**
		 * get row data with html, prev and next siblings, etc...
		 */
		getMatrixHBodyRowHtml: function(elm) {
			//get siblings that are not reserved siblings
			var get_sibling_handler = function(type, item) {
				var sibling = type = "prev" ? item.previousElementSibling : item.nextElementSibling;
				
				if (sibling && (sibling.hasAttribute("data-widget-matrix-body-row") || sibling.hasAttribute("data-widget-matrix-previous-related") || sibling.hasAttribute("data-widget-loading") || sibling.hasAttribute("data-widget-empty")))
					sibling = get_sibling_handler(type, sibling);
				
				return sibling;
			};
			
			var get_item_html_handler = function(item) {
				//show row
				var is_shown = MyWidgetResourceLib.fn.getNodeElementData(item, "is_shown");
				
				if (is_shown)
					MyWidgetResourceLib.fn.show(item);
				
				//show row columns
				var columns = item.querySelectorAll("[data-widget-matrix-head-column], [data-widget-matrix-body-column], [data-widget-matrix-previous-related]");
				
				if (columns)
					MyWidgetResourceLib.fn.each(columns, function (idx, column) {
						//show column
						var is_column_shown = MyWidgetResourceLib.fn.getNodeElementData(column, "is_shown");
						
						if (is_column_shown)
							MyWidgetResourceLib.fn.show(column);
					});
				
				//get html
				var item_original_html = item.outerHTML;
				
				//check if item needs to be inited (this is, if contains any attribute data-widget-item-resources-load) and if yes, inited that html, but before anything else...
				if (!item.classList.contains("template-widget") && (item.hasAttribute("data-widget-item-resources-load") || item.hasAttribute("data-widget-resources-load"))) {
					item.removeAttribute("data-widget-item-resources-load");
					item.removeAttribute("data-widget-resources-load");
					
					MyWidgetResourceLib.ResourceHandler.loadWidgetResource(item, {async: false}); //must be async false otherwise we will loose the the real attribute values, when the load function gets executed
				}
				
				var item_widgets = item.querySelectorAll("[data-widget-item-resources-load]:not(.template-widget), [data-widget-resources-load]:not(.template-widget)");
				
				if (item_widgets)
					MyWidgetResourceLib.fn.each(item_widgets, function (idx, item_widget) { //:not(.template-widget) is very important so it doesn't load the resource when we are editing this widget through the LayoutUIEditor.
						item_widget.removeAttribute("data-widget-item-resources-load");
						item_widget.removeAttribute("data-widget-resources-load");
						
						MyWidgetResourceLib.ResourceHandler.loadWidgetResource(item_widget, {async: false}); //must be async false otherwise we will loose the the real attribute values, when the load function gets executed
					});
				
				//save default item html
				item.setAttribute("data-widget-item-loaded", "");
				
				var item_new_html = item.outerHTML;
				
				return [item_original_html, item_new_html];
			};
				
			//get item html
			var html = get_item_html_handler(elm);
			var item_original_html = html[0];
			var item_new_html = html[1];
			
			//get related siblings html
			var siblings = MyWidgetResourceLib.MatrixHandler.getMatrixElmSiblings(elm);
			
			MyWidgetResourceLib.fn.each(siblings, function (idx, sibling) {
				var html = get_item_html_handler(sibling);
				item_original_html += html[0];
				item_new_html += html[1];
			});
			
			//return row data
			return {
				html: item_new_html,
				original_html: item_original_html,
				parent: elm.parentNode,
				prev: get_sibling_handler("prev", elm),
				next: get_sibling_handler("next", elm),
				siblings_count: siblings.length
			};
		},
		
		getMatrixHeadRowsColumnsPksByIndex : function(elm) {
			var matrix_elm = elm.parentNode.closest("table, [data-widget-matrix]");
			var head_columns_pks = {};
			
			if (matrix_elm) {
				var head_rows = matrix_elm.querySelectorAll("[data-widget-matrix-head-row], thead tr");
				
				for (var i = 0, t = head_rows.length; i < t; i++) {
					var head_columns = head_rows[i].querySelectorAll("[data-widget-matrix-head-column], th, td");
					
					for (var j = 0, tj = head_columns.length; j < tj; j++) {
						var head_column = head_columns[j];
						var pks = head_column.getAttribute("data-widget-pks-attrs");
						
						if (pks) {
							var column_index = MyWidgetResourceLib.fn.getTableCellIndexes(head_column);
							column_index = column_index["col"];
							
							if (MyWidgetResourceLib.fn.isNumeric(column_index))
								head_columns_pks[column_index] = pks;
						}
					}
				}
			}
			
			return head_columns_pks;
		},
		
		/* UTIL FUNCTIONS */
		
		getMatrixItemsCountWithResourceToLoad: function(elm) {
			var matrix = elm.closest("[data-widget-matrix]");
			var matrix_items_with_resources_to_load = MyWidgetResourceLib.fn.getNodeElementData(matrix, "matrix_items_count_with_resources_to_load");
			return matrix_items_with_resources_to_load ? matrix_items_with_resources_to_load : 0;
		},
		
		setMatrixItemsCountWithResourceToLoad: function(elm, count) {
			var matrix = elm.closest("[data-widget-matrix]");
			MyWidgetResourceLib.fn.setNodeElementData(matrix, "matrix_items_count_with_resources_to_load", count);
		},
		
		incrementMatrixItemsCountWithResourceToLoad: function(elm) {
			var c = MyWidgetResourceLib.MatrixHandler.getMatrixItemsCountWithResourceToLoad(elm);
			MyWidgetResourceLib.MatrixHandler.setMatrixItemsCountWithResourceToLoad(elm, c + 1);
		},
		
		decrementMatrixItemsCountWithResourceToLoad: function(elm) {
			var c = MyWidgetResourceLib.MatrixHandler.getMatrixItemsCountWithResourceToLoad(elm);
			MyWidgetResourceLib.MatrixHandler.setMatrixItemsCountWithResourceToLoad(elm, c > 0 ? c - 1 : 0);
		},
		
		isShown: function(elm) {
			return elm && elm.nodeType == Node.ELEMENT_NODE && elm.style.display != "none";
		}
	});
	/****************************************************************************************
	 *				 END: MATRIX FUNCTIONS 					*
	 ****************************************************************************************/
	
})();
