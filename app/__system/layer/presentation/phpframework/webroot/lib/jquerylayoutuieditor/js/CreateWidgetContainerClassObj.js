/*
 * Copyright (c) 2007 PHPMyFrameWork - Joao Paulo Lopes Pinto -- http://jplpinto.com
 * The use of this code must comply with the Bloxtor framework license.
 */

function CreateWidgetContainerClassObj(ui_creator, menu_widget) {
	/* #ADD_SECURITY_CODE_HERE# */  //Important: do not remove this comment because it will be used by the other/scripts/obfuscate_js_files.php to only allow this code in the allowed domains.
	
	var me = this;
	
	me.options = {
		default_column_class: "col-12",
		default_column_class_prefix: "col-",
		default_column_node_name: "div",
		is_reset_columns_allowed: false,
		filter_columns_selector: null,
		add_container_col_default_values: null,
	};
	
	me.init = function() {
		var tag = menu_widget.attr("data-tag");
		
		menu_widget.attr({
			"data-on-parse-template-widget-html-func": ui_creator.obj_var_name + ".menu_widgets_objs['" + tag + "'].parseContainerHtml",
			"data-on-clean-template-widget-html-func": ui_creator.obj_var_name + ".menu_widgets_objs['" + tag + "'].cleanContainerHtml",
			"data-on-clone-menu-widget-func": ui_creator.obj_var_name + ".menu_widgets_objs['" + tag + "'].onCloneMenuWidget",
			"data-on-create-template-widget-func": ui_creator.obj_var_name + ".menu_widgets_objs['" + tag + "'].onCreateTemplateWidget",
		});
		
		menu_widget.children(".properties").attr({
			"data-on-open-settings-func": ui_creator.obj_var_name + ".menu_widgets_objs['" + tag + "'].prepareContainerSettings",
			"data-on-before-parse-widget-settings-func": ui_creator.obj_var_name + ".menu_widgets_objs['" + tag + "'].parseSettings",
			"data-on-after-save-settings-field-func": ui_creator.obj_var_name + ".menu_widgets_objs['" + tag + "'].saveContainerSettingsField",
		});
		
		me.addDefaultPropertiesToMenuWidgetContainer(menu_widget);
	};
			
	me.parseContainerHtml = function(html_element) {
		if (html_element && ui_creator.hasNodeClass($(html_element), "row")) {
			var tag = menu_widget.attr("data-tag");
			var is_container = tag.match(/container-[0-9\-]/);
			
			if (!is_container) 
				return null;
			
			html_element = $(html_element);
			var default_cols = tag.match(/[0-9]+/g);
			var children = html_element.children();
			var t = children.length;
			
			if (!children)
				return null;
			
			for (var i = 0; i < t; i++) {
				var child = children[i];
				var matches = ("" + child.className).match(/(^| )col(\-[^-]+)*\-[0-9]+( |$)/g);
				
				if (matches) {
					var status = false;
					
					for (var j = 0; j < matches.length; j++) {
						var m = matches[j].match(/[0-9]+/g);
						
						if (m[0] == default_cols[i]) {
							status = true;
							break;
						}
					}
					
					if (!status)
						return null;
				}
				else
					return null;
			}
			
			//DEPRECATED so the children can be converted to widgets
			//get only the childNodes that are node.ELEMENT_NODE and check if they belong to another widget.
			/*for (var i = 0; i < t; i++) {
				var child = children[i];
				
				//checks if the child is not a template_region or another non-default widget
				if (!ui_creator.isHtmlElementANonDefaultMenuWidget(child))
					if (!me.options.filter_columns_selector || $(child).is(me.options.filter_columns_selector))
						ui_creator.addNodeClass($(child), "droppable ignore-widget"); //add class ignore-widget, so this children doesn't get converted into a widget.
			}*/
			
			return {
				droppable: html_element,
			}
		}
	};
	
	me.parseSettings = function(widget, widget_settings) {
		widget_settings["cols"] = me.getContainerColsProperties(widget);
	};
	
	me.cleanContainerHtml = function(html_element) {
		var html = ui_creator.getCleanedHtmlElement(html_element);
		var pos = html.indexOf(">");
		var str_pre = html.substr(0, pos);
		var str_pos = html.substr(pos);
		
		if (str_pre.indexOf(' class="') != -1)
			html = str_pre.replace(' class="', ' class="row ');
		else
			html = str_pre + ' class="row"';
		
		html += str_pos;
		
		return html;
	};
	
	me.onCloneMenuWidget = function(widget, html_element) {
		widget = ui_creator.getNewTemplateWidgetBasedInHtmlElement(widget, html_element, null);
		
		if (!html_element) {
			var css_props = {};
			
			if (widget[0].style) {
				if (!widget[0].style.marginLeft)
					css_props["margin-left"] = 0;
				
				if (!widget[0].style.marginRight)
					css_props["margin-right"] = 0;
				
				if (!widget[0].style.paddingLeft)
					css_props["padding-left"] = 0;
				
				if (!widget[0].style.paddingRight)
					css_props["padding-right"] = 0;
				
				if (!widget[0].style.maxWidth)
					css_props["max-width"] = "none";
				
				if (!widget[0].style.width)
					css_props["width"] = "auto";
			}
			
			if (!$.isEmptyObject(css_props))
				widget.css(css_props);
		}
		return widget;
	};
	
	me.onCreateTemplateWidget = function(widget, html_element) {
		if (!html_element) {
			ui_creator.convertHtmlElementToWidget( widget.children() );
		}
	};
	
	me.addDefaultPropertiesToMenuWidgetContainer = function(menu_widget) {
		var html =   '<div class="form-group row settings-property columns table-responsive">'
				 + '		<label class="col-md-4 col-sm-5 col-form-label">Columns: </label>'
				 + '		<table class="table table-sm table-hover">'
				 + '			<thead>'
				 + '				<tr>'
				 + '					<th class="container-col-class">Class</th>'
				 + '					<th class="container-col-attrs">Attributes</th>'
				 + '					<th class="container-col-node-name hidden">Node Name</th>'
				 + '					<th class="container-col-icon"><span class="zmdi zmdi-plus-circle"></span></th>'
				 + '				</tr>'
				 + '			</thead>'
				 + '			<tbody container-cols-index="0">'
				 + '				<tr class="empty-container-cols"><td colspan=4>To add a new column click in the plus icon above...</td></tr>'
				 + '			</tbody>'
				 + '		</table>'
				 + '</div>';
		
		menu_widget.children(".properties").append(html);
	};

	me.prepareContainerSettings = function(widget, settings) {
		var widget_id = widget.data("data-template-id");
		
		//set remove col icon action
		var btn = settings.find(" > .settings-properties > ul > .columns table th.container-col-icon");
		btn.click(function() {
			me.addContainerCol(this, me.options.add_container_col_default_values);
			ui_creator.saveMenuSettingsField(this);
		});
		
		var props = {};
		
		//get the cols html
		var cols = me.getContainerColsProperties(widget);
		
		if (!$.isEmptyObject(cols))
			props = {"cols": cols};
		
		//load cols
		if (props && props["cols"])
			for (var i in props["cols"])
				me.addContainerCol(btn[0], props["cols"][i]);
	};
	
	me.getContainerColsProperties = function(container) {
		var props = {};
		var cols = container.children((me.options.filter_columns_selector ? me.options.filter_columns_selector : "") + ".droppable");
		
		$.each(cols, function (index, item) {
			var c = ui_creator.getTemplateWidgetCurrentClassesWithoutReservedClasses( $(item) );
			var a = "";
			
			$.each(item.attributes, function(j, attr) {
				if (attr.name != "class")
					a += (a != "" ? " " : "") + attr.name + '="' + attr.value + '"';
			});
			
			props[index] = {"class":  c, "attrs": a};
		});
		
		return props;
	};

	me.addContainerCol = function(btn, default_values) {
		var tbody = $(btn).parent().closest("table").children("tbody");
		var index = parseInt(tbody.attr("container-cols-index"));
		index = index >= 0 ? index : 0;
		
		var cl = "", at = "", node_name = "";
		
		if (default_values) {
			cl = typeof default_values.class != "undefined" ? default_values.class : me.options.default_column_class_prefix;
			at = typeof default_values.attrs != "undefined" ? default_values.attrs : "";
			node_name = typeof default_values.node_name != "undefined" ? default_values.node_name : me.options.default_column_node_name;
		}
		else {
			cl = me.options.default_column_class_prefix;
			node_name = me.options.default_column_node_name;
		}
		
		var html = $('<tr>'
			+ '	<td class="container-col-class input-group-sm"><input type="text" class="form-control" name="cols[' + index + '][class]" value="' + cl + '" /></td>'
			+ '	<td class="container-col-attrs input-group-sm"><input type="text" class="form-control" name="cols[' + index + '][attrs]" value="' + at.replace(/"/g, "&quot;") + '" /></td>'
			+ '	<td class="container-col-node-name hidden input-group-sm"><input type="text" class="form-control" name="cols[' + index + '][node_name]" value="' + node_name.replace(/"/g, "&quot;") + '" /></td>'
			+ '	<td class="container-col-icon"><i class="zmdi zmdi-close-circle"></i></td>'
			+ '</tr>');
		
		html.find("input").blur(function(event) {
			return ui_creator.saveMenuSettingsField(this);
		});
		
		html.find(".container-col-icon").click(function() {
			var tr = $(this).parent().closest("tr");
			var tbody = tr.parent();
			var is_empty = tbody.children("tr:not(.empty-container-cols)").length == 1;
			
			if (me.options.is_reset_columns_allowed || !is_empty) {
				tr.remove();
			
				if (is_empty)
					tbody.find(".empty-container-cols").show();
				
				ui_creator.saveMenuSettingsField(tbody[0]);
			}
			else
				ui_creator.showError("You must leave at least one column!");
		});
		
		tbody.find(".empty-container-cols").hide();
		tbody.append(html);
		
		index++;
		tbody.attr("container-cols-index", index);
		
		return html;
	};

	me.saveContainerSettingsField = function(field, widget, status, opts) {
		if (status) {
			field = $(field);
			var sp = field.parent().closest(".settings-properties");
			var sprop = field.parentsUntil(sp, ".settings-property").first();
			
			if (sprop.hasClass("columns")) {
				//resetting cols
				var props = ui_creator.getMenuSettingsProperties(sp);
				var children_selector = (me.options.filter_columns_selector ? me.options.filter_columns_selector : "") + ".droppable";
				
				if (!props["cols"] || $.isEmptyObject(props["cols"])) {
					//update interface
					widget.children(children_selector).remove(); //remove all cols. I cannot do widget.html("") because this can have other HTML elements hard-coded, that the user inserted on purpose...
				}
				else {
					//update interface
					var children = widget.children(children_selector); //only get the right children. Note that the widget may contain other widgets that are not droppable children, like the template_region widget. So we only want to get the .droppable children.
					//console.log(children);
					//console.log(props);
					
					//add and update cols
					for (var i in props["cols"]) {
						var col = children[i];
						var j_col = $(col);
						var cl = ("" + props["cols"][i]["class"]).replace(/\s\s+/g, " ").replace(/^\s+/g, "").replace(/\s+$/g, "");
						var attrs = props["cols"][i]["attrs"];
						var node_name = props["cols"][i]["node_name"];
						node_name = node_name ? node_name : "div";
						node_name = node_name.toLowerCase();
						
						if (col) {
							var reserved_classes = ui_creator.getTemplateWidgetCurrentReservedClasses(j_col).join(" ");
							col.className = reserved_classes + " " + cl;
							
							//preparing col attributes
							var old_attributes = col.attributes;
							//console.log(col);
							
							$.each(old_attributes, function(idx, attr) {
								if (attr) {
									var attr_name = attr.name.toLowerCase();
									
									if (attr_name != "class")
										j_col.removeAttr(attr.name);
								}
							});
							
							if (attrs != "") {
								var temp = $('<div ' + attrs + '></div>');
								
								$.each(temp[0].attributes, function(idx, attr) {
									var attr_name = attr.name.toLowerCase();
									
									if (attr_name == "class")
										j_col.addClass(attr.value);
									else
										j_col.attr(attr_name, attr.value);
								});
								
								temp.remove();
							}
							
							//update node name if different
							if (col.nodeName.toLowerCase() != node_name)
								ui_creator.changeWidgetNodeName(j_col, node_name);
						}
						else { //add new col
							var droppable_inner_html = opts && opts.hasOwnProperty("droppable_inner_html") ? opts["droppable_inner_html"] : '&nbsp;<br/>&nbsp;<br/>&nbsp;';
							
							if (opts && opts["droppable_style"]) {
								if (attrs && attrs.match(/\s*style\s*=\s*("|')/))
									attrs = attrs.replace(/(\s*style\s*=\s*)("|')/, '$1$2' + opts["droppable_style"] + " ");
								else
									attrs = 'style="' + opts["droppable_style"] + '"';
							}
							
							var new_droppable = $('<' + node_name + ' class="droppable ' + cl + '"' + (attrs != "" ? " " + attrs : "") + '>' + droppable_inner_html + '</' + node_name + '>');
							widget.append(new_droppable);
							
							if (!opts || !opts.hasOwnProperty("init_new_droppable") || opts["init_new_droppable"]) {
								//ui_creator.setWidgetChildDroppable(new_droppable[0]);
								ui_creator.convertHtmlElementToWidget(new_droppable);
							}
						}
					}
					
					//remove cols
					children.each(function (idx, child) {
						if (!props["cols"].hasOwnProperty(idx))
							$(child).remove();
					});
					
					//update new cols indexes if there were cols deleted
					var old_length = children.length;
					var new_length = widget.children(children_selector).length;
					
					if (old_length > new_length) {
						var tbody = sprop.find("table").children("tbody");
						tbody.attr("container-cols-index", new_length);
						
						var rows = tbody.children("tr:not(.empty-container-cols)");
						rows.each(function (idx, row) {
							var name_prefix = "cols[" + idx + "]";
							
							$(row).find("input").each(function (idy, input) {
								var name = input.getAttribute("name");
								
								if (name.substr(0, name_prefix.length) != name_prefix)
									input.setAttribute("name", name_prefix + name.substr(name_prefix.length));
							});
						});
					}
				}
				
				ui_creator.updateMenuLayer(widget);
			}
		}
		
		return status;
	};
}
