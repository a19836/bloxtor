/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

function MyTree(options) {
	/* #ADD_SECURITY_CODE_HERE# */  //Important: do not remove this comment because it will be used by the other/scripts/obfuscate_js_files.php to only allow this code in the allowed domains.
	
	var self = this;
	
	self.tree_elm = null;
	self.options = options;
	
	self.init = function(tree_elm_id) {
		self.tree_elm = $("#" + tree_elm_id);
		self.tree_elm.addClass("jstree jstree-1 jstree-default");
	 	
		var ul = self.tree_elm.children("ul");
		ul.addClass("jstree-container-ul");
		
		var default_id = self.options && self.options.default_id ? self.options.default_id : "j";
		initChilds(ul, default_id);
	};
	
	function initChilds(ul, default_id) {
		if (!ul.hasClass("jstree-children"))
			ul.addClass("jstree-children");
		
		ul.attr("role", "group");
		
		var childs = ul.children("li");
	
		var l = childs.length;
		for (var i = 0; i < l; i++) {
			//console.debug(childs[i].id);
			initChild(childs[i], default_id + "" + (i + 1), (i + 1 == l) );
		}
	}
	
	function initChild(li, default_id, is_last) {
		var j_li = $(li);
		var user_data = j_li.attr("data-jstree");
		
		if (user_data)
			eval('user_data = user_data ? ' + user_data + ' : {};');
		else
			user_data = {};
		
		var has_ul = j_li.children("ul").length != 0;
		
		//PREPARE LI CLASSES
		j_li.addClass("jstree-node");
		
		if (has_ul) {
			if (!j_li.hasClass("jstree-closed") && !j_li.hasClass("jstree-open"))
				j_li.addClass("jstree-closed");
		}
		else 
			j_li.addClass("jstree-leaf");
		
		if (is_last)
			j_li.addClass("jstree-last");
		
		//PREPARE LI ATTRIBUTES
		j_li.attr("role", "treeitem");
		
		if (!li.id)
			li.id = default_id;
		
		//PREPARE LI A ELEMENT
		var a = j_li.children("a")[0];
		if (!a) {
			a = document.createElement("A");
			j_li.prepend(a);
		}
		
		if (!a.id) 
			a.id = li.id + "_anchor";
		
		var j_a = $(a);
		j_a.addClass("jstree-anchor");
		
		j_a.click(function(event){
			event.preventDefault();
			var node = j_a.parent()[0];
			
			selectNodeElement(node);
			
			if (self.options && self.options.hasOwnProperty("toggle_children_on_click") && self.options.toggle_children_on_click) {
				//avoids multiple clicks that mess the ui
				var clicked = j_a.data("clicked");
				
				if (!clicked) {
					j_a.data("clicked", 1);
					
					toggleLi(node);
					
					setTimeout(function() {
						j_a.data("clicked", null);
					}, 500);
				}
			}
			
			return false;
		});
		j_a.mouseover(function(event){
			event.preventDefault();
			j_a.addClass("jstree-hovered");
			return false;
		});
		j_a.mouseleave(function(event){
			event.preventDefault();
			j_a.removeClass("jstree-hovered");
			return false;
		});
		
		//PREPARE LI CHILDREN, INCLUDING TEXT NODES AND UL CHILDREN
		var children = j_li.contents();
		
		var l = children.length;
		var has_i = false;
		
		for (var i = 0; i < l; i++) {
			var child = children[i];
			
			if (child.nodeName.toUpperCase() == "UL")
				initChilds($(child), li.id + "_");
			else if (!j_a.is(child)) {
				a.appendChild(child);
				
				if (child.nodeName.toUpperCase() == "I")
					has_i = true;
			}
		}
		
		//PREPARE LI I ELEMENT
		if (!has_i) {
			//j_li.prepend('<i class="jstree-icon jstree-ocl" role="presentation"></i>');
			var i = document.createElement('I');
			i.className = 'jstree-icon jstree-ocl';
			i.setAttribute('role', 'presentation');
			li.insertBefore(i, li.firstChild);
			
			var j_i = $(i);
			j_i.click(function(event) {
				event.preventDefault();
				
				//avoids multiple clicks that mess the ui
				var clicked = j_i.data("clicked");
				
				if (!clicked) {
					j_i.data("clicked", 1);
					
					toggleLi(event.target.parentNode);
					
					setTimeout(function() {
						j_i.data("clicked", null);
					}, 500);
				}
				
				return false;
			});
		}
		
		//PREPARE LI A->I ELEMENT
		if (j_a.children("i").length == 0) {
			//j_a.prepend('<i class="jstree-icon jstree-themeicon" role="presentation"></i>');
			var i = document.createElement('I');
			i.className = 'jstree-icon jstree-themeicon';
			i.setAttribute('role', 'presentation');
			a.insertBefore(i, a.firstChild);
			
			var j_i = $(i);
			
			if (user_data.hasOwnProperty("icon") && user_data.icon)
				j_i.addClass(user_data.icon + " jstree-themeicon-custom");
			else if (!has_ul) 
				j_i.addClass("jstree-file jstree-themeicon-custom");
		}
	}
	
	function toggleLi(li) {
		var j_li = $(li);
		
		var ul = j_li.children('ul')[0];
		var j_ul = $(ul);
		
		if (j_li.hasClass("jstree-open")) {
			j_ul.slideUp(200, function() {
				li.className = li.className.replace(/jstree-open/g, "") + " jstree-closed";
			});
		}
		else {
			var has_childs = j_ul.children().length != 0;
			var url = j_ul.attr("url");
		
			if (!has_childs && url) {
				j_li.addClass("jstree-loading");
				
				$.ajax({
					url: url,
					method: "GET", 
					dataType: "json",
					success: function(data, textStatus, jqXHR) {
						if (data) {
							try {
								if (self.options && self.options.ajax_callback_before && typeof self.options.ajax_callback_before == "function")
									self.options.ajax_callback_before(ul, data, self);
								
								initChilds(j_ul, li.id + "_");
								
								if (self.options && self.options.ajax_callback_after && typeof self.options.ajax_callback_after == "function")
									self.options.ajax_callback_after(ul, data, self);
							}
							catch(e) {
								alert(e && e.message ? e.message : e);
								console && console.log && console.log(e);
							}
						}
					},
					error: function(jqXHR, textStatus, errorThrown) {
						if (self.options && self.options.ajax_callback_error && typeof self.options.ajax_callback_error == "function")
							self.options.ajax_callback_error(ul, jqXHR, textStatus, errorThrown, self);
					},
				}).always(function() {
					j_li.removeClass("jstree-loading");
					
					j_ul.slideDown(200, function() {
						li.className = li.className.replace(/jstree-closed/g, "") + " jstree-open";
					});
				});
			}
			else {
				j_ul.slideDown(200, function() {
					li.className = li.className.replace(/jstree-closed/g, "") + " jstree-open";
				});
			}
		}
	}
	
	self.initNodeChilds = function(node) {
		node = $(node);
		
		var id = node.attr("id");
		var ul = node.children("ul");
		
		if (ul[0]) {
			initChilds(ul, id ? id : null);
		
			if (ul.children().length > 0)
				node.removeClass("jstree-leaf").addClass("jstree-closed");
		}
	};
	
	self.selectNode = function(node_id) {
		return self.tree_elm ? selectNodeElement( self.tree_elm.find("#" + node_id)[0] ) : null;
	};
	
	self.isNodeSelected = function(node_id) {
		return self.tree_elm ? self.tree_elm.find("#" + node_id).children("a").hasClass("jstree-clicked") : null;
	};
	
	self.deselectAll = function() {
		if (self.tree_elm)
			self.tree_elm.find("li a").removeClass("jstree-clicked");
	};
	
	self.getSelectedNodes = function() {
		if (self.tree_elm) {
			var items = self.tree_elm.find("li a.jstree-clicked");
			
			var nodes = new Array();
			for (var i = 0; i < items.length; i++)
				nodes.push( $(items[i]).parent()[0] );
			
			return nodes;
		}
		
		return null;
	};
	
	function selectNodeElement(node) {
		var status = true;
		
		if (self.options && typeof self.options.on_select_callback == "function")
			status = self.options.on_select_callback(node, self);
		
		if (status) {
			var a = $(node).children("a");
		
			if (a.hasClass("jstree-clicked")) {
				if (!self.options || !self.options.hasOwnProperty("toggle_selection") || self.options.toggle_selection)
					a.removeClass("jstree-clicked");
			}
			else {
				if (!self.options || !self.options.hasOwnProperty("multiple_selection") || !self.options.multiple_selection)
					self.deselectAll();
			
				a.addClass("jstree-clicked");
			}
		}
		
		return status;
	}
	
	self.refreshNodesChilds = function(selector) {
		$(selector).each(function(idx, node) {
			self.refreshNodeChilds(node);
		});
	};
	
	self.refreshNodeChildsByNodeId = function(id) {
		if (self.tree_elm) {
			var node = self.tree_elm.find("#" + id)[0];
			self.refreshNodeChilds(node);
		}
	};
	
	self.refreshNodeChilds = function(node, options) {
		node = $(node);
		var ul = node.children("ul");
		
		if (ul) {
			if (node.hasClass("jstree-open")) {
				var url = ul.attr("url");
				
				if (url) {
					node.addClass("jstree-loading");
					
					var d = new Date();
					url += (url.indexOf("?") != -1 ? "&" : "?") + "time=" + d.getTime();
					
					$.ajax({
						url: url,
						method: "GET", 
						dataType: "json",
						success: function(data, textStatus, jqXHR) {
							if (data) {
								try {
									ul.html("");
							
									if (options && options.ajax_callback_first && typeof options.ajax_callback_first == "function")
										options.ajax_callback_first(ul[0], data);
									
									if (self.options && self.options.ajax_callback_before && typeof self.options.ajax_callback_before == "function")
										self.options.ajax_callback_before(ul[0], data, self);
									
									initChilds(ul, node.attr("id") + "_");
									
									if (self.options && self.options.ajax_callback_after && typeof self.options.ajax_callback_after == "function")
										self.options.ajax_callback_after(ul[0], data, self);
							
									if (options && options.ajax_callback_last && typeof options.ajax_callback_last == "function")
										options.ajax_callback_last(ul[0], data, self);
								}
								catch(e) {
									//Do nothing
								}
							}
						},
						error: function(jqXHR, textStatus, errorThrown) {
							if (self.options && self.options.ajax_callback_error && typeof self.options.ajax_callback_error == "function")
								self.options.ajax_callback_error(ul[0], jqXHR, textStatus, errorThrown, self);
							
							if (options && options.ajax_callback_error && typeof options.ajax_callback_error == "function")
								options.ajax_callback_error(ul[0], jqXHR, textStatus, errorThrown, self);
						},
					}).always(function() {
						node.removeClass("jstree-loading");
					});
				}
				else {
					node.removeClass("jstree-loading");
				}
			}
			else {
				ul.html("");
			}
		}
	};
}
