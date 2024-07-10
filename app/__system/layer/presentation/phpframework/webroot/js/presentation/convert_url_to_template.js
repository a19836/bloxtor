var current_url = "" + document.location;
var hovered_element = null;
var clicked_element = null;
var iframe = null;
var iframe_layers = null;
var is_page_url_loaded = false;
var events_set = false;
var regions = {};
var params = {};
var attribute_params = {};
var non_element_params = {};
var non_element_regions = {};
var single_nodes = ["img", "input", "textarea", "select", "button", "iframe", "meta", "base", "basefont", "link", "br", "wbr", "hr", "frame", "area", "source", "track", "circle", "col", "embed", "param", "link", "style", "script", "noscript"];

$(function () {
	iframe = $(".convert_url_to_template .page_html iframe");
	iframe_layers = $(".convert_url_to_template .page_html .page_layers");
	
	iframe.mouseout(function() {
		unsetIframeHoveredElement();
	});
});

function loadUrl() {
	var icon = $(".top_bar .title .icon.refresh");
	var input = $(".top_bar .title input[name=page_url]");
	var url = input.val();
	
	if (url) {
		if (!icon.hasClass("loading")) {
			var iframe_doc = iframe[0].contentWindow.document;
			var iframe_contents = iframe.contents();
			var iframe_html = iframe_contents.find("html");
			var iframe_head = iframe_contents.find("head");
			var iframe_body = iframe_contents.find("body");
			
			icon.addClass("loading");
			input.data("loaded", true);
			
			iframe_head.html("");
			iframe_body.html("");
			iframe_layers.html("");
			
			//remove body attributes
			if (iframe_body[0].attributes)
				for (var i = iframe_body[0].attributes.length - 1; i >= 0; i--)
					iframe_body[0].removeAttribute( iframe_body[0].attributes[i].name );
			
			//remove html attributes
			if (iframe_html[0].attributes)
				for (var i = iframe_html[0].attributes.length - 1; i >= 0; i--)
					iframe_html[0].removeAttribute( iframe_html[0].attributes[i].name );
			
			//set elements selection
			if (!events_set) {
				events_set = true;
				
				iframe_body.bind("mousemove", function(event) {
					unsetIframeHoveredElement();
					setIframeHoveredElement(event.target);
				});
				
				iframe_body.bind("click", function(event) {
					setIframeClickedElement(event.target);
					
					openIframeLayerNode( $(event.target) );
				});
				
				//catch iframe errors
				iframe[0].contentWindow.onerror = function(msg, url, lineNo, columnNo, error) {
					//alert("Javascript Error was found!");
					
					return true; //return true, avoids the error to be shown and other scripts to stop.
				}
			}
			
			$.ajax({
				type : "post",
				url : current_url,
				data : {
					url: url,
					load: "load",
				},
				dataType : "text",
				success : function(parsed_html, textStatus, jqXHR) {
					if (parsed_html) {
						var pos = parsed_html.indexOf("\n");
						url = parsed_html.substr(0, pos);
						parsed_html = parsed_html.substr(pos + 1);
						//console.log(url);
						//console.log(parsed_html);
						
						var doc_type = "";
						var head_html = "";
						var body_html = "";
						var body_attributes = [];
						var html_attributes = [];
						//console.log(parsed_html);
						
						if (parsed_html) {
							var phl = parsed_html.toLowerCase();
							
							if (phl.indexOf("<head") == -1 && phl.indexOf("<body") == -1)
								body_html = parsed_html;
							else {
								head_html = getTemplateHtmlTagContent(parsed_html, "head");
								body_html = getTemplateHtmlTagContent(parsed_html, "body");
								
								body_attributes = getTemplateHtmlTagAttributes(parsed_html, "body");
							}
							
							if (phl.indexOf("<html") != -1)
								html_attributes = getTemplateHtmlTagAttributes(parsed_html, "html");
							
							var doc_type_pos = phl.indexOf("<!doctype");
							if (doc_type_pos != -1)
								doc_type = parsed_html.substr(doc_type_pos, parsed_html.indexOf(">", doc_type_pos + 1) + 1);
						}
						
						head_html += getIframeExtraStyle();
						
						//console.log(head_html);
						iframe_head[0].innerHTML = head_html; //Do not use .html(head_html), bc in some cases it breaks
						iframe_body[0].innerHTML = body_html; //Do not use .html(body_html), bc in some cases it breaks
						
						//set body attributes
						if (body_attributes)
							$.each(body_attributes, function(idx, attr) {
								iframe_body.attr(attr["name"], attr["value"]);
							});
						
						//set html attributes
						if (html_attributes)
							$.each(html_attributes, function(idx, attr) {
								iframe_html.attr(attr["name"], attr["value"]);
							});
						
						//save doctype
						iframe.data("doc_type", doc_type).data("url", url);
						
						prepareIframeBodyHtml(iframe_body);
						prepareIframeLayersHtml(iframe_html, iframe_layers);
						
						//reset regions and params
						regions = {};
						params = {};
						
						is_page_url_loaded = true;
					}
					else
						alert("Error loading url. Please try again...");
					
					icon.removeClass("loading");
				},
				error: function (jqXHR, textStatus, errorThrown) {
					icon.removeClass("loading");
					
					var msg = "Error loading url. Please try again...";
					alert(msg);
					
					if (jqXHR.responseText)
						StatusMessageHandler.showError(msg + "\n" + jqXHR.responseText);
				},
			});
		}
	}
	else 
		alert("Please write a valid url...");
}

function loadUrlIfNotYetLoaded() {
	var input = $(".top_bar .title input[name=page_url]");
	
	if (!input.data("loaded"))
		loadUrl();
}

function getIframeExtraStyle() {
	return '<style data-reserved="1">'
		+ '::-webkit-scrollbar { width:10px; height:10px; background:transparent; }'
		+ '::-webkit-scrollbar-track { background:transparent; }'
		+ '::-webkit-scrollbar-thumb { background:#83889E; background-clip:padding-box; border:2px solid transparent; border-radius:9999px; }'
		
		+ '.template-element-hovered:not(.template-element-clicked) { outline: 1px solid red !important; outline-offset: -2px !important; }'
		+ '.template-element-clicked { outline: 3px solid red !important; outline-offset: -2px !important; }'
		+ '.template-region, .template-param { background:#fff; color:#000; text-align:center; vertical-align:middle; opacity:.9; padding:5px; border:1px solid #ccc; box-sizing: border-box; overflow:hidden; z-index:999999999999; }'
		+ '.template-region:hover, .template-param:hover { opacity:1; }'
		+ '.template-region .close, .template-param .close { position:absolute; top:0px; right:0px; cursor:pointer; }'
	+ '</style>';
}

function unsetIframeHoveredElement() {
	if (hovered_element && $(hovered_element)[0].ownerDocument == iframe[0].contentWindow.document) {
		$(hovered_element).removeClass("template-element-hovered");
		
		if (hovered_element.layer_node)
			$(hovered_element.layer_node).removeClass("hovered");
		
	}
	
	hovered_element = null;
}
function setIframeHoveredElement(elm) {
	hovered_element = elm;
	
	if (hovered_element && clicked_element != hovered_element) {
		he = $(hovered_element);
		
		if (!he.is(".template-region, .template-param") && !he.parent().is(".template-region, .template-param"))
			he.addClass("template-element-hovered");
		
		if (hovered_element.layer_node)
			$(hovered_element.layer_node).addClass("hovered");
	}
}
function unsetIframeClickedElement() {
	if (clicked_element && $(clicked_element)[0].ownerDocument == iframe[0].contentWindow.document) {
		$(clicked_element).removeClass("template-element-clicked");
		
		if (clicked_element.layer_node)
			$(clicked_element.layer_node).removeClass("clicked");
	}
	
	clicked_element = null;
}
function setIframeClickedElement(elm) {
	if (clicked_element == elm)
		unsetIframeClickedElement();
	else {
		unsetIframeClickedElement();
		
		clicked_element = elm;
		
		if (clicked_element) {
			ce = $(clicked_element);
			
			if (!ce.is(".template-region, .template-param") && !ce.parent().is(".template-region, .template-param"))
				ce.addClass("template-element-clicked");
		
			if (clicked_element.layer_node)
				$(clicked_element.layer_node).addClass("clicked");
		}
	}
}

function prepareIframeBodyHtml(iframe_body) {
	//disable click events
	iframe_body.find("*").unbind("click").off('click');
	iframe_body.find("a, button, input").unbind("click").off('click').click(function(event) {
		event.preventDefault();
		event.stopPropagation();
		
		setIframeClickedElement(event.target);
		
		openIframeLayerNode( $(event.target) );
		
		return false;
	});
}

function prepareIframeLayersHtml(iframe_node, layers_node) {
	if (iframe_node && iframe_node.length && layers_node && layers_node.length) {
		var node_type = iframe_node[0].nodeType;
		var is_element = node_type == Node.ELEMENT_NODE;
		var is_text = node_type == Node.TEXT_NODE;
		
		if (!is_text || iframe_node[0].textContent.replace(/\s/g, "") != "") { //avoid empty text nodes
			var node_name = iframe_node[0].nodeName;
			var is_single_node = $.inArray(node_name.toLowerCase(), single_nodes) != -1;
			var title = node_name.charAt(0) + node_name.slice(1).toLowerCase() + (iframe_node.attr("class") ? "." + iframe_node.attr("class") : "");
			var html = '<li class="layer_node">'
						+ '<div class="layer_head">'
							+ '<span class="layer_title">' + title + '</span>'
							+ '<span class="icon delete" title="Remove this node"></span>'
							+ '<span class="icon convert_param" title="Convert to Param"></span>'
							+ '<span class="icon convert_region" title="Convert to Region"></span>'
							+ '<span class="icon unconvert" title="Unconvert Region/Param"></span>'
						+ '</div>'
					+ '</li>';
			var item = $(html);
			var layer_head = item.children(".layer_head");
			
			layers_node.append(item);
			iframe_node[0].layer_node = item[0];
			
			//prepare remove icon
			layer_head.children(".delete").on("click", function(ev) {
				ev.stopPropagation();
				ev.preventDefault();
				
				if (confirm("You are about to delete this node. Do you really wish to continue?")) {
					if (is_element) {
						unsetIframeHoveredElement();
						unsetIframeClickedElement();
					}
					
					item.remove();
					iframe_node.remove();
				}
			});
			
			if (is_element) {
				//prepare layer_head
				layer_head.hover(function(event) {
					setIframeHoveredElement(iframe_node[0]);
				}, function(event) {
					unsetIframeHoveredElement();
				});
				layer_head.bind("click", function(event) {
					setIframeClickedElement(iframe_node[0]);
				});
				
				//prepare convert_param icon
				layer_head.children(".convert_param").on("click", function(ev) {
					ev.stopPropagation();
					ev.preventDefault();
					
					if (iframe_node[0] != clicked_element)
						setIframeClickedElement(iframe_node[0]);
					
					var name = convertToParam();
					
					if (name) {
						item.attr("conversion_name", name);
						item.attr("conversion_type", "param");
						
						//remove all inner conversions
						item.find(".converted").each(function(idx, elm) {
							elm = $(elm);
							
							if (elm.is(".layer_node"))
								elm.find(" > .layer_head > .icon.unconvert").data("do_not_confirm", true).trigger("click").data("do_not_confirm", false);
							else if (elm.is(".attribute"))
								elm.find(" > .icon.unconvert").data("do_not_confirm", true).trigger("click").data("do_not_confirm", false);
						});
					}
				});
				
				//prepare convert_region icon
				layer_head.children(".convert_region").on("click", function(ev) {
					ev.stopPropagation();
					ev.preventDefault();
					
					if (iframe_node[0] != clicked_element)
						setIframeClickedElement(iframe_node[0]);
					
					var name = convertToRegion();
					
					if (name) {
						item.attr("conversion_name", name);
						item.attr("conversion_type", "region");
						
						//remove all inner conversions
						item.find(".converted").each(function(idx, elm) {
							elm = $(elm);
							
							if (elm.is(".layer_node"))
								elm.find(" > .layer_head > .icon.unconvert").data("do_not_confirm", true).trigger("click").data("do_not_confirm", false);
							else if (elm.is(".attribute"))
								elm.find(" > .icon.unconvert").data("do_not_confirm", true).trigger("click").data("do_not_confirm", false);
						});
					}
				});
				
				//prepare unconvert icon
				layer_head.children(".unconvert").on("click", function(ev) {
					ev.stopPropagation();
					ev.preventDefault();
					
					if ($(this).data("do_not_confirm") || confirm("You are about to unconvert this node. Do you wish to continue?")) {
						var name = item.attr("conversion_name");
						var type = item.attr("conversion_type");
						var items = type == "region" ? regions : params;
						
						items[name] = null;
						delete items[name];
						
						item.removeAttr("conversion_name");
						item.removeAttr("conversion_type");
						item.removeClass("converted");
						
						iframe.contents().find('.template-' + type + '[name="' + name.replace(/"/g, "&quot;") + '"]').remove();
						
						unsetIframeClickedElement();
					}
				});
				
				//prepare toggle attributes icon
				if (iframe_node[0].attributes.length > 0) {
					var attributes_toggle_icon = $('<span class="icon maximize attributes_toggle" title="Toggle attributes"></span>');
					var attributes_ul = $('<ul class="attributes"></ul>');
					
					layer_head.append(attributes_toggle_icon);
					item.append(attributes_ul);
					
					attributes_toggle_icon.on("click", function(ev) {
						ev.stopPropagation();
						ev.preventDefault();
						
						attributes_ul.toggle();
						attributes_toggle_icon.toggleClass("maximize minimize");
					});
					
					var attributes = iframe_node[0].attributes;
					
					for (var i = 0, t = attributes.length; i < t; i++) {
						var attribute_name = attributes[i].name;
						var attribute_value = attributes[i].value;
						var attribute_html = '<li class="attribute" attribute_name="' + attribute_name + '">'
											+ '<span class="attribute_title">' + attribute_name + "=\"" + attribute_value + '\"</span>'
											+ '<span class="icon delete" title="Remove this node"></span>'
											+ '<span class="icon convert_param" title="Convert to Param"></span>'
											+ '<span class="icon unconvert" title="Unconvert to Param"></span>'
										+ '</li>';
						var attribute_li = $(attribute_html);
						
						attributes_ul.append(attribute_li);
						
						attribute_li.children(".convert_param").on("click", function(ev) {
							ev.stopPropagation();
							ev.preventDefault();
							
							var name = prompt("Please type the name of the param:");
							
							if (name) {
								name = name ? ("" + name).replace(/(\/|\\|..\/|.\/|\s)/g, "") : "";
								
								var attr_li = $(this).parent().closest("li");
								var attr_name = attr_li.attr("attribute_name");
								
								attribute_params[name] = {
									node: iframe_node[0],
									attribute_name: attr_name,
								};
								
								attr_li.attr("conversion_name", name);
								attr_li.addClass("converted");
							}
						});
						
						attribute_li.children(".unconvert").on("click", function(ev) {
							ev.stopPropagation();
							ev.preventDefault();
							
							if ($(this).data("do_not_confirm") || confirm("You are about to unconvert this node. Do you wish to continue?")) {
								var attr_li = $(this).parent().closest("li");
								var name = attr_li.attr("conversion_name");
								
								if (name) {
									attribute_params[name] = null;
									delete attribute_params[name];
								}
								
								attr_li.removeAttr("conversion_name");
								attr_li.removeClass("converted");
							}
						});
						
						attribute_li.children(".delete").on("click", function(ev) {
							ev.stopPropagation();
							ev.preventDefault();
							
							if (confirm("You are about to delete this attribute. Do you really wish to continue?")) {
								var attr_li = $(this).parent().closest("li");
								var attr_name = attr_li.attr("attribute_name");
								
								if (iframe_node[0].hasAttribute(attr_name)) {
									iframe_node[0].removeAttribute(attr_name);
									attr_li.remove();
									
									if (attributes_ul.children("li").length == 0) {
										attributes_ul.remove();
										attributes_toggle_icon.remove();
									}
								}
							}
						});
					}
				}
				
				if (!is_single_node) {
					//prepare children
					var children = iframe_node.contents();
					
					if (children.length > 0) {
						var children_toggle_icon = $('<span class="icon maximize children_toggle" title="Toggle children"></span>');
						var children_ul = $('<ul class="children"></ul>');
						
						layer_head.prepend(children_toggle_icon);
						item.append(children_ul);
						
						children_toggle_icon.on("click", function(ev) {
							ev.stopPropagation();
							ev.preventDefault();
							
							children_ul.toggle();
							children_toggle_icon.toggleClass("maximize minimize");
						});
						
						for (var i = 0, t = children.length; i < t; i++)
							prepareIframeLayersHtml($(children[i]), children_ul);
					}
				}
			}
			else {
				//prepare convert_param icon
				layer_head.children(".convert_param").on("click", function(ev) {
					ev.stopPropagation();
					ev.preventDefault();
					
					var name = prompt("Please type the name of the param:");
					
					if (name) {
						name = name ? ("" + name).replace(/(\/|\\|..\/|.\/|\s)/g, "") : "";
						
						item.attr("conversion_name", name);
						item.attr("conversion_type", "param");
						item.addClass("converted");
						
						non_element_params[name] = iframe_node[0];
					}
				});
				
				//prepare convert_region icon
				layer_head.children(".convert_region").on("click", function(ev) {
					ev.stopPropagation();
					ev.preventDefault();
					
					var name = prompt("Please type the name of the region:");
					
					if (name) {
						name = name ? ("" + name).replace(/(\/|\\|..\/|.\/|\s)/g, "") : "";
						
						item.attr("conversion_name", name);
						item.attr("conversion_type", "region");
						item.addClass("converted");
						
						non_element_regions[name] = iframe_node[0];
					}
				});
				
				//prepare unconvert icon
				layer_head.children(".unconvert").on("click", function(ev) {
					ev.stopPropagation();
					ev.preventDefault();
					
					if ($(this).data("do_not_confirm") || confirm("You are about to unconvert this node. Do you wish to continue?")) {
						var name = item.attr("conversion_name");
						
						if (name) {
							var type = item.attr("conversion_type");
							var items = type == "region" ? non_element_regions : non_element_params;
							
							items[name] = null;
							delete items[name];
						}
						
						item.removeAttr("conversion_name");
						item.removeAttr("conversion_type");
						item.removeClass("converted");
					}
				});
			}
		}
	}
}

function openIframeLayerNode(iframe_node) {
	if (iframe_node && iframe_node.length) {
		var layer_node = iframe_node[0].layer_node;
		
		if (layer_node) {
			var layer_node = $(layer_node);
			var node = layer_node;
			var page_layers = null;
			
			while(true) {
				node = node.parent().closest("ul");
				
				if (node.is(".page_layers")) {
					page_layers = node;
					break;
				}
				else
					node.show();
			};
			
			var o = layer_node.offset();
			var po = page_layers.offset();
			page_layers.scrollTop(o.top - po.top + page_layers.scrollTop() + 100);
		}
	}
}

function convertToRegion() {
	return convertToType("region");
}

function convertToParam() {
	return convertToType("param");
}

function convertToType(type) {
	if (clicked_element) {
		var type_label = type[0].toUpperCase() + type.substr(1);
		var name = prompt("Please type the name of the " + type + ":");
		
		if (name == null)
			return;
		
		name = name ? ("" + name).replace(/(\/|\\|..\/|.\/|\s)/g, "") : "";
		
		if (name != "") {
			var items = type == "region" ? regions : params;
			var exists = items.hasOwnProperty(name);
			
			if (!exists) {
				var status = true;
				
				if (type == "region") {
					if ($.inArray(clicked_element.nodeName.toLowerCase(), single_nodes) != -1) {
						status = false;
					
						if (confirm("The selected element cannot be converted to a " + type + ". The system will select it's parent and create a region from it. Do you wish to proceed?")) {
							var p = $(clicked_element);
							
							do {
								p = p.parent();
								
								if (p.is("body")) {
									p = null;
									break;
								}
							}
							while ($.inArray(p[0].nodeName.toLowerCase(), single_nodes) != -1);
							
							unsetIframeHoveredElement();
							unsetIframeClickedElement();
							
							if (p[0]) {
								setIframeClickedElement(p[0]);
								status = true;
							}
						}
						else {
							unsetIframeHoveredElement();
							unsetIframeClickedElement();
						}
					}
				}
				
				if (status) {
					var ce = $(clicked_element);
					var position = ("" + ce.css("position")).toLowerCase() == "fixed" ? "fixed" : "absolute";
					var offset = ce.offset();
					var layer_node = clicked_element.layer_node;
					
					var new_elm = $('<div class="template-' + type + '" name="' + name + '">' + type_label + ' "' + name + '" <span class="close">&times;</span></div>');
					new_elm.children(".close").click(function() {
						event.preventDefault();
						event.stopPropagation();
						
						if (confirm("You are about to unconvert this node. Do you wish to continue?")) {
							new_elm.remove();
							
							items[name] = null;
							delete items[name];
							
							if (layer_node)
								$(layer_node).removeClass("converted").removeAttr("conversion_name").removeAttr("conversion_type");
							
							unsetIframeClickedElement();
						}
					});
					
					var iframe_body = iframe.contents().find("body");
					iframe_body.append(new_elm);
					
					var w = ce.width() + parseInt(ce.css("padding-left")) + parseInt(ce.css("padding-right")) + parseInt(ce.css("border-left-width")) + parseInt(ce.css("border-right-width"));
					var h = ce.height() + parseInt(ce.css("padding-top")) + parseInt(ce.css("padding-bottom")) + parseInt(ce.css("border-top-width")) + parseInt(ce.css("border-bottom-width"));
					
					new_elm.css({
						position: position,
						top: offset.top,
						left: offset.left,
						width: parseInt(w) + "px",
						height: parseInt(h) + "px",
					});
					
					items[name] = clicked_element;
					
					ce.removeClass("template-element-hovered").removeClass("template-element-clicked");
					unsetIframeHoveredElement();
					unsetIframeClickedElement();
					
					if (layer_node)
						$(layer_node).addClass("converted").attr("conversion_name", name).attr("conversion_type", type);
					
					return name;
				}
			}
			else
				alert(type_label + " with this name already exists! Please choose another name...");
		}
		else
			alert(type_label + " name cannot be blank");
	}
	else
		alert("Please select an html element first!");
	
	return null;
}

function saveTemplate() {
	var template_name = $(".top_bar .title input[name=template_name]").val();
	
	if (!is_page_url_loaded)
		alert("Page url must be loaded first.");
	else if (!template_name) 
		alert("Template name cannot be blank.");
	else {
		var layout_name = $(".top_bar .title input[name=layout_name]").val();
		var save_button = $(".top_bar ul li.save a");
		var on_click = save_button.attr("onClick");
		var save_icon = save_button.children(".icon");
		
		save_button.removeAttr("onClick");
		save_icon.addClass("loading");
		
		var regions_html = {};
		var params_html = {};
		var non_element_nodes_to_replace = [];
		
		//prepare iframe html - prepare regions and params
		var iframe_contents = iframe.contents();
		var iframe_html = iframe_contents.find("html");
		
		if (regions)
			for (var name in regions) 
				if (regions[name] && $(regions[name])[0].ownerDocument == iframe[0].contentWindow.document)
					$(regions[name]).addClass("template-region-element-to-be-converted").attr("data-template-region-name", name);
		
		if (params)
			for (var name in params) 
				if (params[name] && $(params[name])[0].ownerDocument == iframe[0].contentWindow.document) 
					$(params[name]).addClass("template-param-element-to-be-converted").attr("data-template-param-name", name);
		
		//prepare attribute params
		if (attribute_params)
			for (var name in attribute_params) {
				var props = attribute_params[name];
				
				if (props && props["node"] && props["node"].parentNode && props["attribute_name"]) {
					var elm = props["node"];
					var attribute_name = props["attribute_name"];
					var attribute_value = elm.getAttribute(attribute_name);
					
					props["attribute_value"] = attribute_value;
					params_html[name] = attribute_value;
					elm.setAttribute(attribute_name, '<? echo $EVC->getCMSLayer()->getCMSTemplateLayer()->getParam("' + name + '"); ?>');
				}
			}
		
		//prepare non_element params
		if (non_element_params)
			for (var name in non_element_params) {
				var elm = non_element_params[name];
				
				if (elm && elm.parentNode) {
					var new_elm = document.createTextNode('<? echo $EVC->getCMSLayer()->getCMSTemplateLayer()->getParam("' + name + '"); ?>');
					
					params_html[name] = elm.textContent;
					elm.parentNode.insertBefore(new_elm, elm);
					elm.parentNode.removeChild(elm);
					
					non_element_nodes_to_replace.push([elm, new_elm]);
				}
			}
		
		//prepare non_element regions
		if (non_element_regions)
			for (var name in non_element_regions) {
				var elm = non_element_regions[name];
				
				if (elm && elm.parentNode) {
					var new_elm = document.createTextNode('<? echo $EVC->getCMSLayer()->getCMSTemplateLayer()->renderRegion("' + name + '"); ?>');
					
					regions_html[name] = elm.textContent;
					elm.parentNode.insertBefore(new_elm, elm);
					elm.parentNode.removeChild(elm);
					
					non_element_nodes_to_replace.push([elm, new_elm]);
				}
			}
		
		//clone iframe html
		var clone = iframe_html.clone();
		
		//prepare cloned html
		var clone_body = clone.find("body");
		clone.find("head > [data-reserved=1]").remove();
		clone_body.find(".template-region, .template-param").remove();
		clone_body.find("*").removeClass("template-element-hovered template-element-clicked");
		
		$.each( clone_body.find(".template-region-element-to-be-converted"), function(idx, elm) {
			var name = $(elm).attr("data-template-region-name");
			
			if (name) {
				regions_html[name] = elm.innerHTML;
				elm.innerHTML = '&lt;? echo $EVC-&gt;getCMSLayer()-&gt;getCMSTemplateLayer()-&gt;renderRegion("' + name + '"); ?&gt;';
			}
		});
		
		$.each( clone_body.find(".template-param-element-to-be-converted"), function(idx, elm) {
			var name = $(elm).attr("data-template-param-name");
			
			if (name) {
				params_html[name] = elm.innerHTML;
				elm.innerHTML = '&lt;? echo $EVC-&gt;getCMSLayer()-&gt;getCMSTemplateLayer()-&gt;getParam("' + name + '"); ?&gt;';
			}
		});
		
		//remove some temporary classes
		clone_body.find(".template-region-element-to-be-converted, .template-param-element-to-be-converted").removeClass("template-region-element-to-be-converted template-param-element-to-be-converted").removeAttr("data-template-region-name").removeAttr("data-template-param-name");
		
		//get html
		var html = clone[0].outerHTML; //get html with the html tag
		html = MyHtmlBeautify.beautify(html);
		
		//prepare html that were not converted correctly in the attributes - Basically replace &quot; by ".
		if (attribute_params)
			for (var name in attribute_params) {
				if (params_html.hasOwnProperty(name)) {
					var regex = new RegExp('<\\? echo \\$EVC->getCMSLayer\\(\\)->getCMSTemplateLayer\\(\\)->getParam\\(&quot;' + name + '&quot;\\); \\?>', "g");
					html = html.replace(regex, '<? echo $EVC->getCMSLayer()->getCMSTemplateLayer()->getParam("' + name + '"); ?>');
				}
			}
		
		//put back the previous changes on the regions, params and attributes
		iframe_contents.find("body").find(".template-region-element-to-be-converted, .template-param-element-to-be-converted").removeClass("template-region-element-to-be-converted template-param-element-to-be-converted").removeAttr("data-template-region-name").removeAttr("data-template-param-name");
		
		if (attribute_params)
			for (var name in attribute_params) {
				var props = attribute_params[name];
				
				if (props && props["node"] && props["node"].parentNode && props["attribute_name"]) {
					var elm = props["node"];
					var attribute_name = props["attribute_name"];
					var attribute_value = props["attribute_value"];
					
					elm.setAttribute(attribute_name, attribute_value);
				}
			}
		
		$.each(non_element_nodes_to_replace, function(idx, props) {
			var elm = props[0];
			var new_elm = props[1];
			
			if (new_elm && new_elm.parentNode) {
				new_elm.parentNode.insertBefore(elm, new_elm);
				new_elm.parentNode.removeChild(new_elm);
			}
		});
		
		//send post request to create template
		var data = {
			save: "save",
			url: iframe.data("url"),
			template_name: template_name,
			layout_name: layout_name,
			doc_type: iframe.data("doc_type"),
			regions: regions_html,
			params: params_html,
			html: html,
		};
		
		$.ajax({
			type : "post",
			url : current_url,
			data : JSON.stringify(data),
			dataType : "json",
			processData: false,
			contentType: 'text/html',
			//contentType: 'application/json', //typically 'application/x-www-form-urlencoded', but the service you are calling may expect 'text/json'... check with the service to see what they expect as content-type in the HTTP header.
			success : function(status, textStatus, jqXHR) {
				if (status) {
					if (status == 1) {
						alert("Template Saved!");
						
						if (window.parent.refreshAndShowLastNodeChilds) {
							//Refreshing last node clicked in the entities folder.
							window.parent.refreshAndShowLastNodeChilds();
							
							//Refreshing templates and webroot folder
							var project = window.parent.$("#" + window.parent.last_selected_node_id).parent().closest("li[data-jstree=\'{\"icon\":\"project\"}\']");
							var templates_folder_id = project.children("ul").children("li[data-jstree=\'{\"icon\":\"templates_folder\"}\']").attr("id");
							var webroot_folder_id = project.children("ul").children("li[data-jstree=\'{\"icon\":\"webroot_folder\"}\']").attr("id");
							window.parent.refreshAndShowNodeChildsByNodeId(templates_folder_id);
							window.parent.refreshAndShowNodeChildsByNodeId(webroot_folder_id);
						}
					}
					else
						alert(status);
				}
				else
					alert("Error saving template. Please try again...");
				
				save_button.attr("onClick", on_click);
				save_icon.removeClass("loading");
			},
			error: function (jqXHR, textStatus, errorThrown) {
				save_button.attr("onClick", on_click);
				save_icon.removeClass("loading");
				
				var msg = "Error saving template. Please try again...";
				alert(msg);
				
				if (jqXHR.responseText)
					StatusMessageHandler.showError(msg + "\n" + jqXHR.responseText);
			},
		});
	}
}

function convertAttributesToAssociativeArray(attributes) {
	var arr = {};
	
	if (attributes)
		for (var i = 0, l = attributes.length; i < l; i++) 
			arr[ attributes[i].name ] = attributes[i].value;
	
	return arr;
}
