/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

function createWidgetTableHandler(ui_creator, menu_widget) {
	/* #ADD_SECURITY_CODE_HERE# */  //Important: do not remove this comment because it will be used by the other/scripts/obfuscate_js_files.php to only allow this code in the allowed domains.
	
	var me = this;
	
	me.addRow = function(props_section) {
		var tbody = props_section.find(" > table > tbody");
		
		var row = $('<tr><td class="actions"><i class="zmdi zmdi-plus add"></i><i class="zmdi zmdi-delete delete"></i><input placeHolder="Row attributes" /></td></tr>');
		
		row.find('.add').on("click", function(e3) {
			me.addColumn(row);
			ui_creator.saveMenuSettingsField( row[0] );
		});
		
		row.find('.delete').on("click", function(e3) {
			row.remove();
			
			if (tbody.children().length == 0)
				props_section.children(".no-rows").show();
			
			ui_creator.saveMenuSettingsField(props_section[0]);
		});
		
		row.find("input").blur(function(event) {
			return ui_creator.saveMenuSettingsField(this);
		});
		
		tbody.append(row);
		
		props_section.children(".no-rows").hide();
		
		return row;
	};
	
	me.addColumn = function(row) {
		var td = $('<td><input placeHolder="Column attributes"/><i class="zmdi zmdi-close delete"></i></td>');
		
		td.find(".delete").on("click", function(e4) {
			td.remove();
			ui_creator.saveMenuSettingsField(row[0]);
		});
		
		td.find("input").blur(function(event) {
			return ui_creator.saveMenuSettingsField(this);
		});
		
		row.append(td);
		
		if (row.parent().closest(".thead, .tbody, .tfoot").hasClass("thead"))
			td.attr("data-node-name", "TH");
		
		return td;
	};
	
	me.prepareTableSectionSettings = function(settings_properties_elm) {
		settings_properties_elm.children(".thead, .tbody, .tfoot").each(function(idx, props_section) {
			var props_section = $(props_section);
			
			props_section.find(" > label > .add").each(function(idy, add) {
				add = $(add);
				
				add.on("click", function(e2) {
					me.addRow(add.parent().parent());
					
					ui_creator.saveMenuSettingsField(add[0]);
				});
			});
		});
	};
	
	me.loadTableSectionSettings = function(settings_properties_elm, widget_table_section) {
		var type = widget_table_section[0].nodeName.toLowerCase();
		var trs = null;
		
		if (type != "tr")
			trs = widget_table_section.children("tr");
		else
			trs = widget_table_section;
		
		var props_section = settings_properties_elm.children("." + (type == "tr" ? "tbody" : type));
		
		//load rows
		$.each(trs, function(idy, tr) {
			var j_tr = $(tr);
			var new_tr = me.addRow(props_section);
			var is_tr_widget = j_tr.hasClass("template-widget");
			var tds = j_tr.children("td, th");
			
			var index = idy;
			
			if (type == "tr")
				index = j_tr.parent().children("tr").index(tr);
			
			new_tr.attr("data-node-index", index);
			
			//load row attributes
			if (tr.attributes) {
				var attrs_html = "";
				
				for (var i = 0; i < tr.attributes.length; i++) {
					var attr = tr.attributes[i];
					
					if (is_tr_widget && attr.name && attr.name.toLowerCase() == "class") {
						var reserved_classes = ui_creator.getTemplateWidgetCurrentReservedClasses(j_tr);
						new_tr.attr("data-reserved-classes", reserved_classes.join(" "));
						var classes = ui_creator.getTemplateWidgetCurrentClassesWithoutReservedClasses(j_tr);
						classes = classes.replace(/^\s+/g, "").replace(/\s+$/g, "").replace(/\s\s+/g, " ");
						
						if (classes)
							attrs_html += attr.name + '="' + classes.replace(/"/g, '&quot;') + '"'; //replace by &quot; - not \" - bc the classes may contain json code and \" gives error
					}
					else
						attrs_html += attr.name + '="' + attr.value.replace(/"/g, '&quot;') + '" '; //replace by &quot; - not \" - bc the attr.value may contain json code and \" gives error
				}
				
				new_tr.find(" > .actions > input").val(attrs_html);
			}
			
			//load columns
			$.each(tds, function(idw, td) {
				var j_td = $(td);
				var new_td = me.addColumn(new_tr);
				var is_td_widget = j_td.hasClass("template-widget");
				
				new_td.attr("data-node-name", td.nodeName);
				new_td.attr("data-node-index", idw);
			
				//load column attributes
				if (td.attributes) {
					var attrs_html = "";
					
					for (var i = 0; i < td.attributes.length; i++) {
						var attr = td.attributes[i];
						
						if (is_td_widget && attr.name && attr.name.toLowerCase() == "class") {
							var reserved_classes = ui_creator.getTemplateWidgetCurrentReservedClasses(j_td);
							new_td.attr("data-reserved-classes", reserved_classes.join(" "));
							var classes = ui_creator.getTemplateWidgetCurrentClassesWithoutReservedClasses(j_td);
							classes = classes.replace(/^\s+/g, "").replace(/\s+$/g, "").replace(/\s\s+/g, " ");
							
							if (classes)
								attrs_html += attr.name + '="' + classes.replace(/"/g, '&quot;') + '"'; //replace by &quot; - not \" - bc the classes may contain json code and \" gives error
						}
						else
							attrs_html += attr.name + '="' + attr.value.replace(/"/g, '&quot;') + '" '; //replace by &quot; - not \" - bc the attr.value may contain json code and \" gives error
					}
					
					new_td.children("input").val(attrs_html);
				}
			});
		});
	};
	
	//To be used by the table.xml widget
	me.saveSettingsField = function(field, widget, status) {
		if (status) {
			var sprops = $(field).parent().closest(".settings-properties, ." + ui_creator.options.menu_settings_class);
			
			if (sprops.hasClass("settings-properties")) {
				var ul = sprops.children("ul");
				var sections = [ ul.children(".thead")[0], ul.children(".tbody")[0], ul.children(".tfoot")[0] ];
				
				$.each(sections, function(idx, section) {
					me.saveTableSectionSettings(widget, $(section));
				});
			}
		}
		
		return status;
	};
	
	//To be used by the thead.xml, tbody.xml and tfoot.xml widgets
	me.saveSettingsSectionField = function(field, widget, status) {
		if (status) {
			var sprops = $(field).parent().closest(".settings-properties, ." + ui_creator.options.menu_settings_class);
			
			if (sprops.hasClass("settings-properties")) {
				var ul = sprops.children("ul");
				var section = ul.children(".thead, .tbody, .tfoot").first();
				me.saveTableSectionRowsSettings(widget, section);
			}
		}
		
		return status;
	};
	
	me.saveTableSectionSettings = function(widget, props_section) {
		var trs = props_section.find(" > table > tbody > tr");
		var type = props_section.attr("class");
		var widget_section = widget.children(type);
		
		if (trs.length > 0 && !widget_section[0]) {
			widget_section = $('<' + type + '></' + type + '>');
			
			switch(type) {
				case "thead":
					widget.prepend(widget_section);
					break;
				case "tbody":
					if (widget.children("thead")[0])
						widget_section.insertAfter(widget.children("thead"));
					else
						widget.prepend(widget_section);
					break;
				case "tfoot":
					widget.append(widget_section);
					break;
			}
			
			//converting element it to widget
			ui_creator.convertHtmlElementToWidget(widget_section);
			ui_creator.refreshElementMenuLayer(widget);
		}
		
		me.saveTableSectionRowsSettings(widget_section, props_section);
	};
	
	me.saveTableSectionRowsSettings = function(widget_section, props_section) {
		var trs = props_section.find(" > table > tbody > tr");
		
		if (trs.length > 0) {
			//preparing rows
			var widget_trs = widget_section.children("tr").toArray();
			var trs_that_continue_to_exist = [];
			var nodes_changed = false;
			
			for (var i = 0; i < trs.length; i++) {
				tr = $(trs[i]);
				var tr_node_index = tr.attr("data-node-index");
				var tds = tr.children(":not(.actions)");
				var tr_attributes = tr.children(".actions").children("input").val();
				
				if ($.isNumeric(tr_node_index))
					trs_that_continue_to_exist.push(parseInt(tr_node_index));
				else
					nodes_changed = true;
				
				//preparing tr
				var widget_tr = me.saveTableChildSettings($.isNumeric(tr_node_index) ? widget_trs[tr_node_index] : null, widget_section, tr, "tr", tr_attributes, tr.attr("data-reserved-classes"));
				
				//preparing columns
				var widget_tr_tds = widget_tr.children("td, th").toArray();
				var tr_tds_that_continue_to_exist = [];
				
				for (var j = 0; j < tds.length; j++) {
					td = $(tds[j]);
					var td_attributes = td.children("input").val();
					var td_node_index = td.attr("data-node-index");
					var td_type = td.attr("data-node-name");
					var td_type = td_type ? td_type.toLowerCase() : "td";
					
					if ($.isNumeric(td_node_index))
						tr_tds_that_continue_to_exist.push(parseInt(td_node_index));
					else
						nodes_changed = true;
					
					var widget_tr_td = me.saveTableChildSettings($.isNumeric(td_node_index) ? widget_tr_tds[td_node_index] : null, widget_tr, td, td_type, td_attributes, td.attr("data-reserved-classes"), '&nbsp;');
				}
				
				//removing old columns
				for (var j = 0; j < widget_tr_tds.length; j++) 
					if ($.inArray(j, tr_tds_that_continue_to_exist) == -1) {
						$(widget_tr_tds[j]).remove();
						nodes_changed = true;
					}
			}
			
			//removing old rows
			for (var i = 0; i < widget_trs.length; i++) 
				if ($.inArray(i, trs_that_continue_to_exist) == -1) {
					$(widget_trs[i]).remove();
					nodes_changed = true;
				}
			
			if (nodes_changed)
				me.updateWidgetSectionPropertiesSettings(widget_section, props_section);
		}
		else if (widget_section[0]) {
			//deleting rows
			widget_section.children("tr").remove();
			
			//deleting section
			if (widget_section.children().length == 0)
				widget_section.remove();
		}
	};
	
	me.updateWidgetSectionPropertiesSettings = function(widget_section, props_section) {
		var props_trs = props_section.find(" > table > tbody > tr").toArray();
		var widget_trs = widget_section.children("tr").toArray();
		
		for (var i = 0; i < widget_trs.length; i++) {
			var widget_tr = $(widget_trs[i]);
			var props_tr = $(props_trs[i]);
			
			if (!widget_tr.hasClass("template-widget") && !widget_tr.data("data-template-id")) 
				ui_creator.convertHtmlElementToWidget(widget_tr); //converting element it to widget
			
			if (props_tr[0]) {
				var reserved_classes = ui_creator.getTemplateWidgetCurrentReservedClasses(widget_tr);
				props_tr.attr("data-reserved-classes", reserved_classes.join(" "));
				props_tr.attr("data-node-index", i);
			}
			
			var props_tr_tds = props_tr.children("td, th").toArray();
			var widget_tr_tds = widget_tr.children("td, th").toArray();
			
			for (var j = 0; j < widget_tr_tds.length; j++) {
				var widget_tr_td = $(widget_tr_tds[j]);
				var props_tr_td = $(props_tr_tds[j + 1]); //bc first column is reserved with the tr attributes and add and delete buttons.
				
				if (!widget_tr_td.hasClass("template-widget") && !widget_tr_td.data("data-template-id"))
					ui_creator.convertHtmlElementToWidget(widget_tr_td); //converting element it to widget
				
				if (props_tr_td[0]) {
					var reserved_classes = ui_creator.getTemplateWidgetCurrentReservedClasses(widget_tr_td);
					props_tr_td.attr("data-reserved-classes", reserved_classes.join(" "));
					props_tr_td.attr("data-node-index", j);
					props_tr_td.attr("data-node-name", props_tr_td[0].nodeName);
				}
			}
		}
		
		ui_creator.refreshElementMenuLayer(widget_section);
	};
	
	me.saveTableChildSettings = function(widget_child, widget_child_parent, props_node_elm, node_name, node_attributes_str, node_reserved_classes, node_default_html) {
		//preparing row
		if (widget_child) {
			widget_child = $(widget_child);
			
			//removing old row attributes
			for (var i = 0; i < widget_child[0].attributes.length; i++)
				widget_child.removeAttr(widget_child[0].attributes[i].name);
			
			//adding new row attributes
			if (node_attributes_str) {
				var aux = $('<div ' + node_attributes_str + '></div>');
				
				for (var i = 0; i < aux[0].attributes.length; i++)
					widget_child.attr(aux[0].attributes[i].name, aux[0].attributes[i].value);
				
				delete aux; //cleans memory of dummy element
			}
		}
		else {
			widget_child = $('<' + node_name + (node_attributes_str ? ' ' + node_attributes_str : '') + '>' + (node_default_html ? node_default_html : '') + '</' + node_name + '>');
			widget_child_parent.append(widget_child);
		}
		
		//adding reserved classes
		if (node_reserved_classes)
			widget_child.attr("class", node_reserved_classes + (widget_child.attr("class") ? " " + widget_child.attr("class") : ""));
		
		return widget_child;
	};
	
	
}
