/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

var MyContextMenu = new MyContextMenuClass();

//extend jquery
//Usage: $("some_selector").addcontextmenu("id_of_context_menu_on_page")
jQuery.fn.addcontextmenu = function(context_menu_id, options) {
	return this.each(function() { //return jQuery obj
		MyContextMenu.addContextMenu(this, context_menu_id, options);
	});
};

function MyContextMenuClass() {
	var me = this;
	var right_arrow_html = '<div class="right_arrow"></div>'; //html for the right arrow
	var context_menu_offsets = [1, -1]; //additional mouse event offsets to position the context menu
	var built_context_menu_ids = []; //ids of the context menus that were already built
	var selected_event = null; //current mouse click event
	var ff_shadows_offset = 40; //40 is to account for shadows in FF
	var debug = console && console.log;
	var add_document_click_event = false;
	
	var add_document_click_event_exists = false;
	
	/* GETTERS AND SETTERS */
	
	me.getRightArrowHtml = function() {
		return right_arrow_html;
	};
	
	me.setRightArrowHtml = function(html) {
		right_arrow_html = html;
	};
	
	me.getContextMenuOffsets = function() {
		return context_menu_offsets;
	};
	
	me.setContextMenuOffsets = function(offsets) {
		context_menu_offsets = offsets;
	};
	
	me.getBuildContextMenuIds = function() {
		return built_context_menu_ids;
	};
	
	me.setBuildContextMenuIds = function(ids) {
		built_context_menu_ids = ids;
	};
	
	me.getSelectedEvent = function() {
		return selected_event;
	};
	
	me.setSelectedEvent = function(event) {
		selected_event = event;
	};
	
	me.getSelectedEventTarget = function() {
		return selected_event ? selected_event.target : null;
	};
	
	me.getFFShadowsOffset = function() {
		return ff_shadows_offset;
	};
	
	me.setFFShadowsOffset = function(offset) {
		ff_shadows_offset = offset;
	};
	
	me.getDebugStatus = function() {
		return debug;
	};
	
	me.enableDebugStatus = function() {
		debug = true;
	};
	me.disableDebugStatus = function() {
		debug = false;
	};
	
	me.getAddDocumentClickEventStatus = function() {
		return add_document_click_event;
	};
	
	me.enableAddDocumentClickEventStatus = function() {
		add_document_click_event = true;
	};
	me.disableAddDocumentClickEventStatus = function() {
		add_document_click_event = true;
	};
	
	/* CHECKERS */
	
	me.isContextMenuSet = function(elm, context_menu_id) {
		elm = $(elm);
		var binded_context_menus = elm.data("binded_context_menus");
		
		return $.isArray(binded_context_menus) && binded_context_menus.length > 0 && (!context_menu_id || $.inArray(context_menu_id, binded_context_menus) != -1);
	};
	
	/* INIT */
	
	me.initContextMenu = function(elm, context_menu_elm, options) {
		elm = $(elm);
		var id = context_menu_elm.attr("id");
		
		//save contextmenu ids to element
		var binded_context_menus = elm.data("binded_context_menus");
		binded_context_menus = $.isArray(binded_context_menus) ? binded_context_menus : [];
		binded_context_menus.push(id);
		elm.data("binded_context_menus", binded_context_menus);
		
		//check if context menu was not built already
		if (!id || jQuery.inArray(id, built_context_menu_ids) == -1) {
			me.buildContextMenu(context_menu_elm);
			
			//hide all context menus when mouse is clicked in body
			if (add_document_click_event && !add_document_click_event_exists) {
				add_document_click_event_exists = true;
				
				$(document).bind("click", function(ev) {
					if (ev.button == 0)
						me.hideAllContextMenu();
				});
			}
		}
		
		//ignore elm if is inside of an element with the .mycontextmenu class
		if (elm.parents().filter("ul.mycontextmenu").length > 0)
			return ;
		
		var context_menu_func = function(ev) {
			if (ev.preventDefault) 
				ev.preventDefault(); 
			else 
				ev.returnValue = false;
			
			var status = true;
		
			if (options && options.callback) {
	  			var func = options.callback;

				if (typeof func == "function")
					status = func(elm, context_menu_elm, ev) ? true : false;
			}
			
			if (status) {
				me.hideContextMenu(context_menu_elm);
				me.updateContextMenuPosition(context_menu_elm, ev);
				me.showContextMenu(context_menu_elm, ev);
				
				//hide all context menus except the current one
				me.hideAllContextMenu( id ? id : context_menu_elm );
			}
		
			return false;
		};
		
		//console.log(elm.data("events").contextmenu);
		elm.bind("contextmenu", context_menu_func);
		
		//For mobile devices, please include the jquery.taphold.js library
		if (!options || !options.ignore_tap_hold) {
			elm.on("taphold", {duration: 1500}, function(ev) {
				//if draggable don't show menu
				if (elm.is(".ui-draggable-dragging"))
					return false;
				
				//disable future click events
				elm.css("pointer-events", "none");
				
				//Fix bug of the TapHold event for Mobile devices and Chrome, which don't have the pageX and pageY attributes.
				if ( 
				  (!ev.hasOwnProperty("pageX") || !ev.hasOwnProperty("pageY")) && 
				  (ev.originalEvent && ev.originalEvent.touches && ev.originalEvent.touches[0])
				) {
					var touch = ev.originalEvent.touches[0];
					ev.pageX = touch.pageX;
					ev.pageY = touch.pageY;
				}
				
				context_menu_func(ev);
				
				//re-enable future click events
				setTimeout(function() {
					elm.css("pointer-events", "auto");
				}, 500);
				
				return false;
			});
		}
	};
	
	/* SHOW AND HIDE */
	
	me.showContextMenu = function(context_menu_elm, ev) {
		context_menu_elm.show();
		
		var interval = context_menu_elm.data("interval");
		
		if (interval)
			clearInterval(interval);
		
		interval = setInterval(function() {
			if (!context_menu_elm.is(":hover"))
				me.hideContextMenu(context_menu_elm);
		}, 5000);
		
		context_menu_elm.data("interval", interval);
		
		selected_event = ev;
	};
	
	//hide all context menus (and their sub ul elements)
	me.hideContextMenu = function(context_menu_elm) {
		var interval = context_menu_elm.data("interval");
		
		if (interval)
			clearInterval(interval);
		
		//hide context menu and sub uls if any...
		context_menu_elm.hide(); 
		context_menu_elm.find("ul").hide();
	};
	
	//hide all context menus (and their sub ul elements)
	me.hideAllContextMenu = function(ignore_context_menu_id) {
		if (ignore_context_menu_id instanceof jQuery && ignore_context_menu_id[0] && ignore_context_menu_id[0].hasAttribute("id"))
			ignore_context_menu_id = ignore_context_menu_id.attr("id");
		else if (typeof ignore_context_menu_id == "object" && ignore_context_menu_id.id)
			ignore_context_menu_id = ignore_context_menu_id.id;
		
		if (typeof ignore_context_menu_id == "string")
			me.hideContextMenu( $(".mycontextmenu:not(#" + ignore_context_menu_id + ")") );
		else {
			var menus = $(".mycontextmenu");
			
			if (typeof ignore_context_menu_id == "object")
				menus = menus.not(ignore_context_menu_id); //filter context_menu from menus
			
			//hide all menus
			me.hideContextMenu(menus);
		}
	};
	
	/* UPDATE POSITION */
	
	me.updateContextMenuPosition = function(ul, ev) {
		var is_main_context_menu = ul.hasClass("mycontextmenu");
		var doc = $(document);
		var win = $(window);
		var doc_in_right_edge = doc.scrollLeft() + win.width() - ff_shadows_offset;
		var doc_in_bottom_edge = doc.scrollTop() + win.height() - ff_shadows_offset;
		var ul_dimensions = ul.data("dimensions");
		
		if (is_main_context_menu) { //if main context menu DIV
			var x = ev.pageX + context_menu_offsets[0]; //x pos of main context menu UL
			var y = ev.pageY + context_menu_offsets[1];
			
			//if not enough horizontal room to the ridge of the cursor
			if (x + ul_dimensions.width > doc_in_right_edge)
				x = doc_in_right_edge - ul_dimensions.width;
			
			//if not enough vertical room to the ridge of the cursor
			if (y + ul_dimensions.height > doc_in_bottom_edge)
				y = doc_in_bottom_edge - ul_dimensions.height;
		}
		else { //if sub level context menu UL
			var parent_li = ul.data("parent_li");
			var parent_li_offset = parent_li.offset();
			var x = ul_dimensions.parent_li_width; //x pos of sub UL
			var y = 0;
			
			//if not enough horizontal room to the ridge parent LI
			if (parent_li_offset.left + x + ul_dimensions.width > doc_in_right_edge)
				x = x - ul_dimensions.parent_li_width - ul_dimensions.width;
			
			//if not enough vertical room to the ridge parent LI
			if (parent_li_offset.top + ul_dimensions.height > doc_in_bottom_edge)
				y = y - ul_dimensions.height + ul_dimensions.parent_li_height;
		}
		
		if (x < 0)
			x = 0;
		
		if (y < 0)
			y = 0;
		
		ul.css({
			left:x, 
			top:y
		});
	};
	
	/* BUILD */
	
	me.buildContextMenu = function(menu_elm) {
		if (menu_elm && menu_elm[0]) {
			var already_built = menu_elm.data("already_built");
			
			if (already_built != 1) {
				menu_elm.data("already_built", 1);
				
				menu_elm.css({
					display : "block", 
					visibility : "hidden"
				});
				menu_elm.appendTo(document.body);
				
				//remember main menu's dimensions
				menu_elm.data("dimensions", {
					width : menu_elm.outerWidth(), 
					height : menu_elm.outerHeight()
				});
				
				//find all LIs within menu with a sub UL
				var lis = menu_elm.find("ul").parent();
				
				lis.each(function(idx, li) {
					buildContextMenuItem(li, 1000 + idx);
				});
				
				//set mouse leave event
				menu_elm.bind("mouseleave", function(e) {
					me.hideContextMenu(menu_elm);
				});
				
				//collapse context menu
				menu_elm.css({
					display : "none", 
					visibility : "visible"
				});
				
				//collapse all sub uls
				menu_elm.find("ul").css({
					display : "none", 
					visibility : "visible"
				}); 
				
				//save context menu's id
				var menu_id = menu_elm.attr("id");
				
				if (menu_id)
					built_context_menu_ids.push(menu_id); 
			}
		}
	};
	
	function buildContextMenuItem(li, z_index) {
		li = $(li);
		li.css("z-index", z_index);
		
		var sub_ul = li.find("ul:eq(0)");
		
		//set first sub ul to block so we can get dimensions
		sub_ul.css("display", "block"); 
		sub_ul.data("dimensions", {
			width : sub_ul.outerWidth(), 
			height : sub_ul.outerHeight(), 
			parent_li_width : li[0].offsetWidth, 
			parent_li_height : li[0].offsetHeight
		});
		
		//show sub ul when mouse moves over parent li
		li.bind("mouseenter", function(ev) { 
			if (sub_ul.queue().length <= 1) { //if 1 or less queued animations
				me.updateContextMenuPosition(sub_ul, ev);
				sub_ul.show();
			}
		});
		
		//hide sub ul when mouse moves out of parent li
		li.bind("mouseleave", function(ev) { 
			sub_ul.hide();
		});
		
		//cache parent li of each sub ul
		sub_ul.data("parent_li", li);
		
		//add arrow images to first sub link
		li.children("a:eq(0)").append(right_arrow_html);
	}
	
	me.addContextMenu = function(elm, context_menu_id, options) {
		elm = $(elm);
		var context_menu_elm = null;
		
		if (typeof context_menu_id == "string")
			context_menu_elm = $("#" + context_menu_id);
		else if (context_menu_id instanceof jQuery)
			context_menu_elm = context_menu_id;
		else if (context_menu_id instanceof Node)
			context_menu_elm = $(context_menu_id);
		
		if (context_menu_elm && context_menu_elm[0])
			me.initContextMenu(elm, context_menu_elm, options);
		else if (debug && console && console.log) {
			var msg = "#" + context_menu_id + " html element is undefined!";
			console.log(msg);
			//console.log(new Error(msg));
		}
	};
};
