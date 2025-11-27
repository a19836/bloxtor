/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original JQuery My Status Message Repo: https://github.com/a19836/jquerymystatusmessage/
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

var StatusMessageHandler = { 
	message_html_obj : null,
	other_message_html_objs : {},
	
	init : function() {
		this.message_html_obj = this.createMessageHtmlObj();
	},
	
	createMessageHtmlObj : function(message_html_obj_class) {
		var message_html_obj = $('<div class="status_message' + (message_html_obj_class ? " " + message_html_obj_class : "") + '"></div>');
		
		if (this.message_html_obj)
			this.message_html_obj.after(message_html_obj);
		else
			$(document.body).append(message_html_obj);
		
		message_html_obj.click(function() {
			StatusMessageHandler.removeMessages(message_html_obj_class);
		});
		
		return message_html_obj;
	},
	
	getMessageHtmlObj : function(message_html_obj_class) {
		if (message_html_obj_class) {
			if (!this.other_message_html_objs.hasOwnProperty(message_html_obj_class))
				this.other_message_html_objs[message_html_obj_class] = this.createMessageHtmlObj(message_html_obj_class);
			
			return this.other_message_html_objs[message_html_obj_class];
		}
		
		if (!this.message_html_obj) //This is very important bc we all the showMessage and showError without calling the init method before, which will make the message_html_obj null. So we need to call the init here to avoid the message_html_obj null.
			this.init();
		
		return this.message_html_obj;
	},
	
	showMessage : function(message, message_class, message_html_obj_class, timeout) {
		var status_message = this.getMessageElement(message, "status_message_info" + (message_class ? " " + message_class : ""), message_html_obj_class);
		var message_html_obj = this.getMessageHtmlObj(message_html_obj_class);
		
		try { //if message contains a full html page with head and body we will get a javascript error. So we need to catch it.
			if (!status_message.parent().is(message_html_obj))
				message_html_obj.append(status_message);
		}
		catch(e) {
			message_html_obj = $(document.body).children('.status_message'); //sometimes the message_html_obj looses the reference for the object
			
			if (console && console.log)
				console.log(e);
		}
		
		this.prepareMessage(status_message, timeout > 0 ? timeout : 5000);
		
		return status_message;
	},

	showError : function(message, message_class, message_html_obj_class, timeout) {
		var status_message = this.getMessageElement(message, "status_message_error" + (message_class ? " " + message_class : ""), message_html_obj_class);
		var message_html_obj = this.getMessageHtmlObj(message_html_obj_class);
		
		try { //if message contains a full html page with head and body we will get a javascript error. So we need to catch it.
			if (!status_message.parent().is(message_html_obj))
				message_html_obj.append(status_message);
		}
		catch(e) {
			message_html_obj = $(document.body).children('.status_message'); //sometimes the message_html_obj looses the reference for the object
			
			if (console && console.log)
				console.log(e);
		}
		
		this.prepareMessage(status_message, timeout > 0 ? timeout : 10000);
		
		return status_message;
	},
	
	getMessageElement : function(message, message_class, message_html_obj_class) {
		var message_html_obj = this.getMessageHtmlObj(message_html_obj_class);
		var width = $(window).width();
		var created_time = (new Date()).getTime();
		var last_msg_elm = message_html_obj.children().last();
		var status_message = null;
		
		//prepare message_class
		message_class = message_class.replace(/\s+/g, " ").replace(/^\s+/g, "").replace(/\s+$/g, "");
		var message_class_selector = message_class.replace(/ /g, ".");
		
		try {
			//prepare message text
			message = this.parseMessage(message);
			var parts = message.split("\n");
			var height = parts.length * 20 + (message.indexOf("<br") != -1 ? message.split("<br").length * 20 : 0);
			
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
		}
		catch(e) {
			if (console && console.log)
				console.log(e);
		}
		
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
		var max_height = parseInt(status_message.css("max-height"));
		var height = parseInt(status_message.css("min-height"));
		var close_icon = status_message.children(".close_message");
		
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
			StatusMessageHandler.removeMessage(this);
			
			if (timeout_id)
				clearTimeout(timeout_id);
		});
	},
	
	removeMessage : function(elm) {
		$(elm).parent().remove();
	},
	
	removeLastShownMessage : function(type, message_html_obj_class) {
		var selector = type ? ".status_message_" + type : ".status_message_info, .status_message_error";
		this.getMessageHtmlObj(message_html_obj_class).children(selector).last().remove();
	},
	
	removeMessages : function(type, message_html_obj_class) {
		var selector = type ? ".status_message_" + type : ".status_message_info, .status_message_error";
		this.getMessageHtmlObj(message_html_obj_class).children(selector).remove();
	},
};
