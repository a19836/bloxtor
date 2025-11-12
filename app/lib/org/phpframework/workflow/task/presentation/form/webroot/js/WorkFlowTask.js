/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

var FormTaskPropertyObj = {
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		var task_html_element = properties_html_elm.find(".form_task_html"); //do not use .page_content_task_html bc if there is listing or form task in the same parent, it will get the wrong element.
		task_html_element.attr("updateTableUIHandler", "FormTaskPropertyObj.onChangeDBTable");
		task_html_element.find(" > .ui > .add_ui_table_attribute > .icon").attr("onClick", "FormTaskPropertyObj.addUITableAttribute(this)");
		
		PresentationTaskUtil.onLoadTaskProperties(task_html_element, task_id, task_property_values);
		PresentationTaskUtil.setSortableUITableRows(task_html_element);
		
		FormTaskPropertyObj.prepareAddUITableAttributesOptions(task_html_element);
	},
	
	onChangeDBTable : function(task_html_element, table_attributes) {
		var ui_elm = task_html_element.children(".ui");
		var add_ui_table_attribute = ui_elm.children(".add_ui_table_attribute");
		var ui_html_elm = ui_elm.children(".ui_html");
		
		ui_html_elm.html("");
		add_ui_table_attribute.hide();
		
		if (!$.isEmptyObject(table_attributes)) {
			var is_insertable = FormTaskPropertyObj.areUITableAttributesInsertable(task_html_element);
			var is_updatable = FormTaskPropertyObj.areUITableAttributesUpdatable(task_html_element);
			var html = '<table><tbody index_prefix="attributes">';
			var attribute_idx = 0;
			
			for (var attribute_name in table_attributes) {
				html += FormTaskPropertyObj.getDBTableAttributeHtml(task_html_element, attribute_idx, attribute_name, table_attributes[attribute_name], is_insertable, is_updatable);
				attribute_idx++;
			}
			
			html += '</tbody></table>';
			
			ui_html_elm.html(html);
			add_ui_table_attribute.show();
			
			PresentationTaskUtil.setSortableUITableRows(task_html_element);
			
			FormTaskPropertyObj.prepareAddUITableAttributesOptions(task_html_element);
		}
		else 
			ui_html_elm.html('No available attributes...');
	},
	
	getDBTableAttributeHtml : function(task_html_element, attribute_idx, attribute_name, attribute_details, is_insertable, is_updatable) {
		var input_type = PresentationTaskUtil.getTableAttributeInputType(attribute_details);
		var is_pk_auto_increment = PresentationTaskUtil.isTableAttributePKAutoIncremented(attribute_details);
		
		if (is_pk_auto_increment && is_insertable && !is_updatable) //do not show attribute if only insert and pk
			return '';
		
		var popup_props_html = PresentationTaskUtil.getUITableAttributePropertiesHtml(task_html_element, attribute_idx, attribute_name);
		
		var html = '<tr ui_table_attribute_name="' + attribute_name + '">'
			+ '<td class="ui_table_attribute_label"><input class="task_property_field" type="hidden" name="attributes[' + attribute_idx + '][name]" value="' + attribute_name + '"/><input class="task_property_field" type="hidden" name="attributes[' + attribute_idx + '][active]" value="1"/>' + PresentationTaskUtil.attributeNameToLabel(attribute_name) + ': </td>';
		
		if ((is_insertable || is_updatable) && !is_pk_auto_increment) {
			var input_html = PresentationTaskUtil.getTableAttributeInputHtml(input_type);
			html += '<td class="ui_table_attribute_value">' + input_html + '</td>';
		}
		else
			html += '<td class="ui_table_attribute_value ' + input_type + '"></td>';
			
		html += '<td class="ui_table_attribute_action"><i class="icon update" onClick="PresentationTaskUtil.editUITableAttributeRowProperties(this)"></i><i class="icon remove" onClick="PresentationTaskUtil.removeUITableAttributeRow(this)"></i>' + popup_props_html + '</td>'
			+ '</tr>';
		
		return html;
	},
	
	addUITableAttribute : function(elm) {
		elm = $(elm);
		var attribute_name = elm.parent().children("select").val();
		var task_html_element = elm.parent().closest(".page_content_task_html"); //in here I can use .page_content_task_html
		var table_attributes = PresentationTaskUtil.getSelectedDBTableAttributes( task_html_element.find(".choose_db_table") );
		var attribute_details = table_attributes ? table_attributes[attribute_name] : null;
		
		if (attribute_details) {
			var tbody = task_html_element.find(" > .ui > .ui_html > table > tbody");
			var attribute_idx = getListNewIndex(tbody);
			var is_insertable = FormTaskPropertyObj.areUITableAttributesInsertable(task_html_element);
			var is_updatable = FormTaskPropertyObj.areUITableAttributesUpdatable(task_html_element);
			var html = FormTaskPropertyObj.getDBTableAttributeHtml(task_html_element, attribute_idx, attribute_name, attribute_details, is_insertable, is_updatable);
			tbody.append(html);
		}
		else
			alert("Error: There is no table attribute detals for the attribute: '" + attribute_name + "'!\nError in FormTaskPropertyObj.addUITableAttribute function!");
	},
	
	areUITableAttributesInsertable : function(task_html_element) {
		return task_html_element.find(" > .settings > .actions").find("input.insert_action:checked").length > 0;
	},
	
	areUITableAttributesUpdatable : function(task_html_element) {
		return task_html_element.find(" > .settings > .actions").find("input.update_action:checked").length > 0;
	},
	
	prepareAddUITableAttributesOptions : function(task_html_element) {
		var table_attributes = PresentationTaskUtil.getSelectedDBTableAttributes(task_html_element);
		var is_insertable = FormTaskPropertyObj.areUITableAttributesInsertable(task_html_element);
		var is_updatable = FormTaskPropertyObj.areUITableAttributesUpdatable(task_html_element);
		var options = "";
		
		if (table_attributes)
			$.each(table_attributes, function(attribute_name, attribute_details) {
				var is_pk_auto_increment = PresentationTaskUtil.isTableAttributePKAutoIncremented(attribute_details);
				
				if (is_pk_auto_increment && is_insertable && !is_updatable) //do not show attribute if only insert and pk
					options += '';
				else
					options += '<option value="' + attribute_name + '">' + PresentationTaskUtil.attributeNameToLabel(attribute_name) + '</option>';
			});
		
		task_html_element.find(" > .ui > .add_ui_table_attribute > select").html(options);
	},
};
