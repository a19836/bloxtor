/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original JQuery Layout UI Editor Repo: https://github.com/a19836/jquerylayoutuieditor/
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

function CreateWidgetBootstrapContainerClassObj(ui_creator, menu_widget, widget_tag, cols) {
	var me = this;
	
	me.extend = function(obj) {
		for (var x in this)
			obj[x] = this[x];
	};
	
	me.init = function() {
		menu_widget.attr({
			"data-on-drag-stop-func": ui_creator.obj_var_name + ".menu_widgets_objs['" + widget_tag + "'].onDropMenuWidget"
		});
	};
	
	me.onDropMenuWidget = function(menu_widget, widget, event, ui_obj) {
		//setTimeout(function() { //must be in settimeout bc we are overwriting the widget.
			var html = me.getContainerHtml(cols);
			var new_widget = $(html);
			widget.after(new_widget);
			
			ui_creator.convertHtmlElementToWidget(new_widget);
			ui_creator.replaceWidgetWithWidget(widget, new_widget);
			
			widget[0] = new_widget[0];
		//}, 100);
	};
	
	me.getContainerHtml = function(cols) {
		var html = '<div class="row">';
		
		if ($.isArray(cols))
			for (var i = 0, t = cols.length; i < t; i++)
				html += '<div class="col-' + cols[i] + '">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>';
		
		html += '</div>';
		
		return html;
	};
}
