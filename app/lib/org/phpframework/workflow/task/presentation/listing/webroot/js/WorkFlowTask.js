/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

var ListingTaskPropertyObj = {
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		var WF = myWFObj.getTaskFlowChart();
		
		var task_html_element = properties_html_elm.find(".listing_task_html"); //do not use .page_content_task_html bc if there is listing or form task in the same parent, it will get the wrong element.
		task_html_element.attr("updateTableUIHandler", "ListingTaskPropertyObj.onChangeDBTable");
		task_html_element.find(" > .settings > .actions .insert_action").removeAttr("onClick");
		task_html_element.find(" > .ui > .add_ui_table_attribute > .icon").attr("onClick", "ListingTaskPropertyObj.addUITableAttribute(this)");
		
		PresentationTaskUtil.onLoadTaskProperties(task_html_element, task_id, task_property_values);
		
		if (task_property_values && task_property_values["pagination"]) {
			var pagination_elm = task_html_element.find(" > .settings > .pagination");
			
			if (task_property_values["pagination"]["active"])
				pagination_elm.find(".pagination_active > input").attr("checked", "checked").prop("checked", true);
			
			pagination_elm.find(".pagination_rows_per_page > input").val(task_property_values["pagination"]["rows_per_page"]);
		}
		
		PresentationTaskUtil.setSortableUITableCols(task_html_element);
		
		//prepare listing type
		var listing_type_elm = task_html_element.find(" > .settings > .listing_type > select")[0];
		ListingTaskPropertyObj.onChangeListingType(listing_type_elm, false);
	},
		
	onTaskCreation : function(task_id) {
		PresentationTaskUtil.onTaskCreation(task_id);
		
		if (!FormTaskPropertyObj || !$.isPlainObject(FormTaskPropertyObj))
			alert("Error: The ListingTaskPropertyObj needs the FormTaskPropertyObj. Please be sure that the FormTaskPropertyObj is loaded  too in this workflow!");
	},
	
	onChangeListingType : function(elm, update_ui_html, do_not_confirm) {
		elm = $(elm);
		var p = elm.parent();
		var type = elm.val();
		
		//update ui html according with type
		if (update_ui_html) {
			if (do_not_confirm || confirm("This will update the UI. Do you wish to continue?")) {
				elm.attr("orig_listing_type", type);
				
				var task_html_element = p.closest(".page_content_task_html"); //in here I can use .page_content_task_html
				var filter_by_attributes = PresentationTaskUtil.getDesignedDBTableAttributes(task_html_element);
				PresentationTaskUtil.updateTableUI(task_html_element, filter_by_attributes);
			}
			else {
				type = elm.attr("orig_listing_type");
				elm.val(type ? type : "");
			}
		}
		
		//show or hide multiple actions and pagination - can only execute after the code above be executed
		var settings = p.closest(".settings");
		var dependencies = settings.find(".actions .multiple_actions, .pagination");
		
		if (type == "multi_form") 
			dependencies.hide();
		else
			dependencies.show();
	},
	
	onChangeDBTable : function(task_html_element, table_attributes) {
		var listing_type = task_html_element.find(" > .settings > .listing_type > select").val();
		task_html_element.children(".ui").removeClass("table tree multi_form").addClass(listing_type ? listing_type : "table");
		
		if (listing_type == "tree")
			FormTaskPropertyObj.onChangeDBTable(task_html_element, table_attributes);
		else if (listing_type == "multi_form")
			FormTaskPropertyObj.onChangeDBTable(task_html_element, table_attributes);
		else
			ListingTaskPropertyObj.onChangeDBTableWithTableUIHtml(task_html_element, table_attributes);
	},
	
	onChangeDBTableWithTableUIHtml : function(task_html_element, table_attributes) {
		var ui_elm = task_html_element.children(".ui");
		var add_ui_table_attribute = ui_elm.children(".add_ui_table_attribute");
		var ui_html_elm = ui_elm.children(".ui_html");
		
		ui_html_elm.html("");
		add_ui_table_attribute.hide();
		
		if (!$.isEmptyObject(table_attributes)) {
			var is_updatable = ListingTaskPropertyObj.areUITableAttributesUpdatable(task_html_element);
			var html_head = '<thead index_prefix="attributes"><tr>';
			var html_body = '<tbody><tr>';
			var attribute_idx = 0;
			
			for (var attribute_name in table_attributes) {
				var aux = ListingTaskPropertyObj.getDBTableAttributeHtml(task_html_element, attribute_idx, attribute_name, table_attributes[attribute_name], is_updatable);
				html_head += aux[0];
				html_body += aux[1];
				
				attribute_idx++;
			}
			
			html_head += '</tr></thead>';
			html_body += '</tr></tbody>';
			
			var html = '<table>' + html_head + html_body + '</table>';
			ui_html_elm.html(html);
			add_ui_table_attribute.show();
			
			PresentationTaskUtil.setSortableUITableCols(task_html_element);
		}
		else 
			ui_html_elm.html('No available attributes...');
	},
	
	getDBTableAttributeHtml : function(task_html_element, attribute_idx, attribute_name, attribute_details, is_updatable) {
		var input_type = PresentationTaskUtil.getTableAttributeInputType(attribute_details);
		var is_pk_auto_increment = PresentationTaskUtil.isTableAttributePKAutoIncremented(attribute_details);
		
		var popup_props_html = PresentationTaskUtil.getUITableAttributePropertiesHtml(task_html_element, attribute_idx, attribute_name);
		
		var html_head = '<th class="ui_table_attribute_label" ui_table_attribute_name="' + attribute_name + '"><input class="task_property_field" type="hidden" name="attributes[' + attribute_idx + '][name]" value="' + attribute_name + '"/><input class="task_property_field" type="hidden" name="attributes[' + attribute_idx + '][active]" value="1"/>' + PresentationTaskUtil.attributeNameToLabel(attribute_name) + ' <i class="icon remove" onClick="PresentationTaskUtil.removeUITableAttributeCol(this)"></i></th>';
		var html_body = "";
		
		if (is_updatable && !is_pk_auto_increment) {
			var input_html = PresentationTaskUtil.getTableAttributeInputHtml(input_type);
			html_body += '<td class="ui_table_attribute_value">' + input_html + '<i class="icon update" onClick="PresentationTaskUtil.editUITableAttributeColProperties(this)"></i>' + popup_props_html + '</td>';
		}
		else
			html_body += '<td class="ui_table_attribute_value ' + input_type + '"><i class="icon update" onClick="PresentationTaskUtil.editUITableAttributeColProperties(this)"></i>' + popup_props_html + '</td>';
		
		return [html_head, html_body];
	},
	
	addUITableAttribute : function(elm) {
		var ui_elm = $(elm).parent().closest(".page_content_task_html").children(".ui"); //in here I can use .page_content_task_html
		
		if (ui_elm.hasClass("tree"))
			FormTaskPropertyObj.addUITableAttribute(elm);
		else if (ui_elm.hasClass("multi_form"))
			FormTaskPropertyObj.addUITableAttribute(elm);
		else
			ListingTaskPropertyObj.addUITableAttributeForTableUIHtml(elm);
	},
	
	addUITableAttributeForTableUIHtml : function(elm) {
		elm = $(elm);
		var attribute_name = elm.parent().children("select").val();
		var task_html_element = elm.parent().closest(".page_content_task_html"); //in here I can use .page_content_task_html
		var table_attributes = PresentationTaskUtil.getSelectedDBTableAttributes( task_html_element.find(".choose_db_table") );
		var attribute_details = table_attributes ? table_attributes[attribute_name] : null;
		
		if (attribute_details) {
			var table = task_html_element.find(" > .ui > .ui_html > table");
			var thead = table.children("thead");
			var attribute_idx = getListNewIndex(thead);
			var is_updatable = ListingTaskPropertyObj.areUITableAttributesUpdatable(task_html_element);
			var aux = ListingTaskPropertyObj.getDBTableAttributeHtml(task_html_element, attribute_idx, attribute_name, attribute_details, is_updatable);
			var html_head = aux[0];
			var html_body = aux[1];
			
			thead.children("tr").first().append(html_head);
			table.find(" > tbody > tr").first().append(html_body);
		}
		else
			alert("Error: There is no table attribute detals for the attribute: '" + attribute_name + "'!\nError in ListingTaskPropertyObj.addUITableAttribute function!");
	},
	
	areUITableAttributesUpdatable : function(task_html_element) {
		return task_html_element.find(" > .settings > .actions input.update_action:checked").length > 0;
	},
};
