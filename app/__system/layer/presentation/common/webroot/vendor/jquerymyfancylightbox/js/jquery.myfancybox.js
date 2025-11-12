/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

var MyFancyPopup = new MyFancyPopupClass();

function MyFancyPopupClass() {
	/* #ADD_SECURITY_CODE_HERE# */  //Important: do not remove this comment because it will be used by the other/scripts/obfuscate_js_files.php to only allow this code in the allowed domains.
	
	var self = this;
	
	var popup_overlay_id = "main_popup_overlay_123_" + parseInt(Math.random() * 1000) + "_" + parseInt(Math.random() * 1000);
	var is_popup_open = false;
	var show_popup_attempts = 0;
	
	var options = null;
	var elm = null;
	var parent = null;
	var max_z_index = null;
	var max_z_index_inited = null;
	var max_z_index_count = 0;
	
	/* START: PRIVATE FUNCTIONS */
	function iniSettings(settings) {
		options = settings;
		
		elm = options && options.elementToShow ? options.elementToShow : null;
		elm = elm && elm instanceof jQuery ? elm[0] : elm;
		
		parent = options && options.parentElement ? options.parentElement : (elm ? $(elm).parent() : null);
		parent = parent && parent instanceof jQuery ? parent[0] : parent;
		
		if (!parent) {
			parent = document.body;
			
			if (!parent)
				parent = window;
		}
		
		self.settings = settings;
		
		//Prepare max_z_index
		max_z_index_inited = false;
		max_z_index_count = 0;
		
		//2020-02-04: this function is very slow when exists too many html elements, so it needs to be in a timeout
		setTimeout(function() {
			var p = $.isWindow(parent) ? parent.document.body : (parent == document || parent.nodeType == 9 ? $(parent.body) : parent);
			initMaxZIndexRecursively( $(p) );
			//initMaxZIndex( $(p) ); //2020-02-04: DO NOT USE THIS FUNCTION BC IS TOO SLOW WHEN THERE ARE TOO MANY HTML ELEMENTS
		}, 10);
	}
	
	function initMaxZIndexRecursively(j_parent_elem) {
		if (!max_z_index_inited) {
			max_z_index_count++;
			
			//2020-02-04: before this was j_parent_elem.find("*"), but it was too slow when there are too many html elements
			j_parent_elem.children().each(function(idx, item) {
				item = $(item);
				var is_visible = item.is(":visible") && item.css("display") != "none";
				
				if (is_visible) {
					var position = item.css('position');
					
					if (position == 'absolute' || position == 'fixed') {
						var z_index = parseInt( item.css('z-index') );
						
						if (z_index > max_z_index) { //only if visible, otherwise it doesn't matter
							max_z_index = z_index;
							
							//2147483647 is the 2^32, which is the maximum value for integers in 32 bits systems.
							if (max_z_index >= 2147483647) {
								max_z_index -= 5;
								max_z_index_inited = true;
								return false;
							}
						}
					}
					
					initMaxZIndexRecursively(item);
				}
			}).promise().done(function () {
				max_z_index_count--;
				
				if (max_z_index_count == 0)
					max_z_index_inited = true;
				
				//console.log("MAX_Z_INDEX IS INITED:"+max_z_index);
			});
		}
	}
	
	//2020-02-04: DEPRECATED Too slow if too many html elements. To avoid!
	/*function initMaxZIndex(j_parent_elem) {
		max_z_index_inited = false;
		
		//this function is very slow when exists too many html elements, so it needs to be in a timeout
		setTimeout(function() {
			j_parent_elem.find('*').each(function(idx, item) {
				item = $(item);
				var position = item.css('position');
				
				if ((position == 'absolute' || position == 'fixed') && item.css("display") != "none") {
					var z_index = parseInt( item.css('z-index') );
					
					if (z_index > max_z_index) { //only if visible, otherwise it doesn't matter
						max_z_index = z_index;
						
						//2147483647 is the 2^32, which is the maximum value for integers in 32 bits systems.
						if (max_z_index >= 2147483647) {
							max_z_index -= 5;
							return false;
						}
					}
				}
			}).promise().done(function () {
				max_z_index_inited = true;
				//console.log("MAX_Z_INDEX IS INITED:"+max_z_index);
			});
		}, 10);
	}*/
	
	//If there is any inner element which contains scrolling, disable dragging event for that event, otherwise scroll doesn't work on Touch devices (Tablets and Smarphones). Additionally if there is any inner element which is an input or textarea, the keyboard focus won't work either. Please leave this fix here, otherwise the Tablets/Smartphones won't work properly.
	function stopDraggingEventForScrollingChilds(j_drag_elm) {
		j_drag_elm.children().each(function(idx, obj) {
			var j_obj = $(obj);
			
			var stop_propagation = j_obj.is("input, textarea");
			
			if (!stop_propagation) {
				var overflow = j_obj.css("overflow");
				var overflow_x = j_obj.css("overflow-x");
				var overflow_y = j_obj.css("overflow-y");
			
				stop_propagation = overflow == "scroll" || overflow == "auto" || overflow_x == "scroll" || overflow_x == "auto" || overflow_y == "scroll" || overflow_y == "auto";
			}
			
			if (stop_propagation) {
				var events = jQuery._data(obj, "events");
				var events = events && events.hasOwnProperty("touchstart") ? events["touchstart"] : new Array();
				var exists = false;
				
				if ($.isArray(events)) {
					for (var i = 0; i < events.length; i++) {
						var item = events[i];
					
						if (!$.isEmptyObject(item.handler) && item.handler.name == "stopChildPropagationFromDraggingParent") {
							exists = true;
							break;
						}
					}
				};
				
				if (!exists) {
					j_obj.bind("touchstart", stopChildPropagationFromDraggingParent);
				}
			}
			else {
				stopDraggingEventForScrollingChilds(j_obj);
			}
		});
	}
	
	function stopChildPropagationFromDraggingParent(originalEvent) {
		originalEvent.stopPropagation();
	}
	
	function appendElementToAnotherElement(c, p) {
		if (p == document)
			$(document.body).append(c);
		else if (p.nodeType == 9)//is document inside of window/frame
			$(p.body).append(c);
		else if ($.isWindow(p))
			$(p.document.body).append(c);
		else
			$(p).append(c);
	}
	
	function isABodyElement(e) {
		return e && (e == document.body || (e.nodeType == 1 && ("" + e.nodeName).toUpperCase() == "BODY"));
	}
	
	function getElementDocument(e) {
		if (e) {
			var p = e;
			
			do {
				p = p.parentNode;
				if (p.nodeType == 9) //is document
					return p;
			}
			while(p);
		}
	}
	
	function setMaxZIndex(j_elm_to_set, inc) {
		if ($.isNumeric(max_z_index)) {
			inc = $.isNumeric(inc) ? inc : 0;
			j_elm_to_set.css("z-index", max_z_index + inc); //always set z-index even if the max_z_index is not inited yet
		}
		
		if (!max_z_index_inited)
			setTimeout(function() {
				setMaxZIndex(j_elm_to_set, inc)
			}, 100);
	}
	/* END: PRIVATE FUNCTIONS */
	
	/* START: PUBLIC FUNCTIONS */
	self.init = function(settings) {
		if (is_popup_open) {//in case there is another popup open before.
			if (options && typeof options.onClose == "function") {
				var func = options.onClose;
				
				options.onClose = function() {
					func();
					
					iniSettings(settings);
					
					//options.onClose = func;
				};
			}
			else {
				options = !options ? {} : options;
				
				options.onClose = function() {
					iniSettings(settings);
					
					//options.onClose = null;
				};
			}
			
			self.hidePopup();
		}
		else {
			//self.hidePopup();//hide overlay and loading, just in case...
			
			iniSettings(settings);
		}
	};
	
	self.centerPopupHtmlElm = function(e, p) {
		e = !e ? elm : e;
		p = !p ? parent : p;
		
		if (e) {
			var j_elm = $(e);
			var j_parent = $(p);
			
			if (j_parent.height() == 0)
				j_parent = $(document);
		
			var w = parseInt(j_elm.outerWidth());
			var h = parseInt(j_elm.outerHeight());
		
			var pw = parseInt(j_parent.width());
			var ph = parseInt(j_parent.height());
			
			var psl = parseInt(j_parent.scrollLeft());
			var pst = parseInt(j_parent.scrollTop());
			
			var is_fixed = false;
			var position = j_elm.css("position");
			
			if (jQuery.isWindow(p) || ("" + position).toLowerCase() == "fixed") 
				is_fixed = true;
			else { //center inside of the visible object
				var j_window = $(window);
			
				var ww = parseInt(j_window.width());
				var wh = parseInt(j_window.height());
			
				var po = self.getOffset(j_parent);
				var wo = self.getOffset(j_window);
			
				po = po ? po : {left:0, top:0};
				wo = wo ? wo : {left:0, top:0};
				
				var left = po.left > wo.left ? po.left : wo.left;
				var top = po.top > wo.top ? po.top : wo.top;
				var right = po.left + pw < wo.left + ww ? po.left + pw : wo.left + ww;
				var bottom = po.top + ph < wo.top + wh ? po.top + ph : wo.top + wh;
				
				var npw = right - left;
				var nph = bottom - top;
				
				npw = npw > 0 ? npw : 0;
				nph = nph > 0 ? nph : 0;
				
				pw = npw;
				ph = nph;
				
				if (isABodyElement(parent)) {
					var d = getElementDocument(parent);
					
					psl = parseInt( $(d).scrollLeft() );
					pst = parseInt( $(d).scrollTop() );
				}
			}
			
			var x = parseInt( (pw / 2) - (w / 2) + (is_fixed ? 0:  psl) );
			var y = parseInt( (ph / 2) - (h / 2) + (is_fixed ? 0:  pst) );
			
			x = x < 10 ? 10 : x;
			y = y < 10 ? 10 : y;
			
			j_elm.css("position", is_fixed ? "fixed" : "absolute");
			j_elm.css("left", x + "px");
			j_elm.css("top", y + "px");
			//j_elm.offset({'top' : y, 'left' : x});
			//console.log(x+":"+y);
		}
	};
	
	self.getLoading = function() {
		return $("#" + popup_overlay_id + "_loading");
	};
	
	self.getOverlay = function() {
		return $("#" + popup_overlay_id);
	};
	
	self.getPopupCloseButton = function() {
		return $(elm).children(".popup_close");
	};

	self.getPopup = function() {
		return $(elm);
	};

	self.showPopup = function(opts) {
		if (is_popup_open) {
			if (show_popup_attempts < 5) {
				setTimeout(function() {
					self.showPopup()
				}, 500);
			
				++show_popup_attempts;
			}
			else {
				show_popup_attempts = 0;
			}
		}
		else {
			show_popup_attempts = 0;
			
			self.prepareElementIfIframe();
			
			if (elm) {
				is_popup_open = true;
				
				var j_elm = $(elm);
				
				self.showOverlay();
				self.showLoading();
				self.prepareElementCloseButton();
				self.centerPopupHtmlElm();
				
				if (
					(!opts || !opts.not_draggable) && 
					typeof j_elm.draggable == "function" && 
					!(j_elm.data("draggable") || j_elm.hasClass("ui-draggable"))
				) {
					j_elm.draggable({
						stop: function(originalEvent, ui) {
							self.resizeOverlay();
						}
					});
				}
				
				setMaxZIndex(j_elm, 1);
				
				j_elm.fadeIn("medium", function() {
					j_elm.show();
					self.centerPopupHtmlElm(); //center works better after popup is shown.
					
					if (options && typeof options.onOpen == "function") 
						options.onOpen();
			
					setTimeout(function() {
						//Leave this outside of the draggable condition above, because the inner elements might change after the main element be already draggable.
						stopDraggingEventForScrollingChilds(j_elm);
						
						self.updatePopup();
						self.hideLoading();
					}, 300);
				});
			}
		}
		
		return elm;
	};
	
	self.reinitZIndex = function() {
		max_z_index_inited = false;
		max_z_index_count = 0;
		
		var p = $.isWindow(parent) ? parent.document.body : (parent == document || parent.nodeType == 9 ? $(parent.body) : parent);
		initMaxZIndexRecursively( $(p) );
		
		setMaxZIndex($(elm), 1);
	};
	
	self.reshowPopup = function(opts) {
		self.hidePopup();
		self.showPopup(opts);
		
		return elm;
	};

	self.hidePopup = function() {
		var status = options && typeof options.beforeClose == "function" ? options.beforeClose() : true;
		
		if (status !== false) {
			if (elm) {
				var j_elm = $(elm);
				
				j_elm.fadeOut("fast", function() {
					j_elm.css("z-index", 1);
					j_elm.offset({left:0,top:0});
					j_elm.hide();
				});
			}
				
			self.hideLoading();
			self.hideOverlay();
		}
		
		return elm;
	};

	//we need to resize the overlay because if the parent has scrollbars, the overlay doesn't resize correctly
	self.updatePopup = function() {
		if (is_popup_open) {
			self.centerPopupHtmlElm();
			
			self.resizeOverlay();
		}
		
		return elm;
	};
	
	self.destroyPopup = function() {
		$(elm).remove();
		$("#" + popup_overlay_id).remove();
		$("#" + popup_overlay_id + "_loading").remove();
	};
	
	self.showOverlay = function() {
		if (parent) {
			var j_parent = $(parent);
			//var j_parent = $(document.body);//only for testing

			if (isABodyElement(parent))
				j_parent = $( getElementDocument(parent) );
			else if (j_parent.height() == 0)
				j_parent = $(document);
			
			var o = self.getOffset(j_parent);
			if (!o) {
				o = {top:0, left:0};
			}
			
			//Does not work when parent is document. I did NOT test this with other parents! But it doesn't work with parent document.
			/*if (j_parent.attr("popup_overlay_resize_already_active") != "1") {
				j_parent.attr("popup_overlay_resize_already_active", "1");
				
				j_parent.resize(function() {
					console.log("is inside of resize");
					self.resizeOverlay();
				});
			}*/

			if (document.getElementById(popup_overlay_id)) {
				$("#" + popup_overlay_id).remove();
			}

			var overlay = document.createElement("DIV");
			overlay.id = popup_overlay_id;
			overlay.className = "popup_overlay" + (options && options.popup_class ? " " + options.popup_class : "");

			$(overlay).click(function(originalEvent) {
				originalEvent.preventDefault();

				self.hidePopup();
			});
			
			appendElementToAnotherElement(overlay, parent);
			
			var j_popup_overlay = $("#" + popup_overlay_id);
			
			/* DO NOT TRY TO PLAY WITH THE OFFSETS, OTHERWISE THE OVERLAY WILL NOT WORK CORRECTLY WHEN THE WINDOW IS SCROLL DOWN. THE OFFSETS MUST BE ALWAYS 0px */
			/*j_popup_overlay.css({
				'top': (o.top ? o.top : 0) + "px",//leave this here, otherwise the overlay in the workflow diagrams doesn't work correctly.
				'left': (o.left ? o.left : 0) + "px",
				'bottom': "0px", 
				'right': "0px",
			});*/
			
			self.resizeOverlay();
			
			j_popup_overlay.show();
			
			return j_popup_overlay[0];
		}
		
		return null;
	};
	
	self.hideOverlay = function() {
		var j_popup_overlay = $("#" + popup_overlay_id);
		
		if (j_popup_overlay[0])
			j_popup_overlay.fadeOut("fast", function() {
				j_popup_overlay.hide();
				j_popup_overlay.remove();
	
				if (options && typeof options.onClose == "function")
					options.onClose();
			
				is_popup_open = false;
			});
		else // in case it doesn't exist, because the popup could be hardcoded without a overlay behind...
			is_popup_open = false;
		
		return j_popup_overlay[0];
	};
	
	self.resizeOverlay = function() {
		if (parent) {
			var j_parent = $(parent);
			//var j_parent = $(document.body);//only for testing
	
			if (isABodyElement(parent))
				j_parent = $( getElementDocument(parent) );
			else if (j_parent.height() == 0)
				j_parent = $(document);
	
			var psw = parseInt(j_parent.prop("scrollWidth"));
			var psh = parseInt(j_parent.prop("scrollHeight"));
	
			var w = psw > 0 ? psw : parseInt(j_parent.width());
			var h = psh > 0 ? psh : parseInt(j_parent.height());
			
			var o = self.getOffset(j_parent);
			if (!o) {
				o = {top:0, left:0};
			}
			
			var j_popup_overlay = $("#" + popup_overlay_id);
			
			var is_fixed = jQuery.isWindow(parent) || ("" + j_popup_overlay.css("position")).toLowerCase() == "fixed";
			j_popup_overlay.css("position", is_fixed ? "fixed" : "absolute");
			
			/* DO NOT TRY TO PLAY WITH THE OFFSETS, OTHERWISE THE OVERLAY WILL NOT WORK CORRECTLY WHEN THE WINDOW IS SCROLL DOWN. THE OFFSETS MUST BE ALWAYS 0px */
			j_popup_overlay.css({
				/*'top': (o.top ? o.top : 0) + "px",//leave this here, otherwise the overlay in the workflow diagrams doesn't work correctly.
				'left': (o.left ? o.left : 0) + "px",
				'bottom': "0px", 
				'right': "0px",*/
				'width': w + "px", 
				'height': h + "px",
			});
			
			setMaxZIndex(j_popup_overlay, 0);
			
			return j_popup_overlay[0];
		}
		
		return null;
	};
	
	self.showLoading = function() {
		if (parent) {
			var j_parent = $(parent);
			
			var popup_loading_id = popup_overlay_id + "_loading";
			
			if (document.getElementById(popup_loading_id)) {
				$("#" + popup_loading_id).remove();
			}

			var div = document.createElement("DIV");
			div.id = popup_loading_id;
			div.className = "popup_loading" + (options && options.popup_class ? " " + options.popup_class : "");
			div.innerHTML = '<div class="spining"></div>';
			appendElementToAnotherElement(div, parent);
			
			setMaxZIndex($(div), 1);
			
			self.centerPopupHtmlElm(div, parent);
			
			return div;
		}
		
		return null;
	};
	
	self.hideLoading = function() {
		var loading = $("#" + popup_overlay_id + "_loading");
		
		loading.remove();
		
		return loading[0];
	};
	
	self.prepareElementCloseButton = function() {
		if (elm) {
			var j_elm = $(elm);
			
			var close_id = popup_overlay_id + "_close";
			var items = j_elm.find("#" + close_id);
	
			if (!items || !items[0]) {
				var close = document.createElement("DIV");
				close.id = close_id;
				close.className = "popup_close";
			
				$(close).click(function(originalEvent) {
					originalEvent.preventDefault();

					self.hidePopup();
				});
				
				setMaxZIndex($(close), 3);
				
				j_elm.prepend(close);
				
				return close;
			}
		}
		
		return null;
	};
	
	self.prepareElementIfIframe = function() {
		if (options && options.type == "iframe" && options.url && parent) {
			var j_parent = $(parent);
			
			if (!elm) {
				elm = document.createElement("DIV");
				elm.className = "myfancypopup" + (options && options.popup_class ? " " + options.popup_class : "");
				appendElementToAnotherElement(elm, parent);
			}
			
			var iframe = $(elm).find('iframe')[0];
			
			if (!iframe) {
				iframe = document.createElement("IFRAME");
				$(elm).append(iframe);
			}
			
			var j_iframe = $(iframe);
			
			var iframe_unload_func = function(originalEvent) {
				self.showLoading();
				
				if (options && typeof options.onIframeUnLoad == "function")
					options.onIframeUnLoad(originalEvent, iframe);
			};
			
			j_iframe.load(function(originalEvent) {
				//set beforeunload func
				if (options && typeof options.onIframeBeforeUnLoad == "function")
					$(iframe.contentWindow).bind('beforeunload', function(originalEvent2){
						return options.onIframeBeforeUnLoad(originalEvent2, iframe); //If this function returns something, the browser will show a confirmation message to be sure the user really wants to leave the current page
					});
				
				//set unload func
				$(iframe.contentWindow).unload(iframe_unload_func);
				
				var w = j_iframe.contents().outerWidth() + 5;
				var h = j_iframe.contents().outerHeight() + 5;
				
				j_iframe.css({"width" : w + "px", "height" : h + "px"});
				
				self.updatePopup();
				self.hideLoading();
				
				if (options && typeof options.onIframeOnLoad == "function")
					options.onIframeOnLoad(originalEvent, iframe);
			});
			
			//Unload event should be set here too bc the iframe may be already loaded when the popup gets created, so we must set the unload here. Otherwise the unload won't work the first time, bc the onLoad event didn't occur yet and so the unload wasn't yet set. Please leave the unload event here.
			//If you do not wish to execute the unload function at the first time the iframe gets created, do not use the options.onIframeUnLoad and set your unload event manually, inside of the options.onIframeOnLoad function (like it is above).
			$(iframe.contentWindow).unload(iframe_unload_func); 
			
			j_iframe.attr("src", options.url);
			
			return iframe;
		}
		
		return null;
	};
	
	self.isPopupOpened = function() {
		return is_popup_open;
	};
	
	self.setOption = function(option_name, option_value) {
		if (option_name) {
			if (!options)
				options = {};
			
			options[option_name] = option_value;
		}
	};
	
	self.getOption = function(option_name) {
		return option_name ? options[option_name] : null;
	};
	
	self.getOffset = function(elm) {
		var o = null;
		
		try {
			o = elm.offset();
		}
		catch(e) {
			console && console.log && console.log("THIS IS ONLY AN WARNING FOR TESTING. THIS EXCEPTION/ERROR IS OK! " + (e && e.message ? e.message : e));
		}
		
		return o;
	};
	/* END: PUBLIC FUNCTIONS */
};
