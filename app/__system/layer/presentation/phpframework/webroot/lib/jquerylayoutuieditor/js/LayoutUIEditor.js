/*
 * Copyright (c) 2007 PHPMyFrameWork - Joao Paulo Lopes Pinto -- http://jplpinto.com
 * The use of this code must comply with the Bloxtor framework license.
 */

//TODO: add feature when we hover the html elements in the layout, to show the margin and padding with a background with opacity, like when we inspect the dom elements from the developer tools of the browser...

var scripts = document.getElementsByTagName("script");
var this_script_file_path = scripts[ scripts.length - 1 ].src; //To be used by getLayoutUIEditorFolderPath()

function LayoutUIEditor() {
	/* #ADD_SECURITY_CODE_HERE# */  //Important: do not remove this comment because it will be used by the other/scripts/obfuscate_js_files.php to only allow this code in the allowed domains.
	
	var me = this;
	var ui = null;
	var menu_widgets = null;
	var menu_layers = null;
	var menu_settings = null;
	var resize_panels = null;
	var template_source = null;
	var template_full_source = null;
	var template_preview = null;
	var template_widgets = null;
	var template_widgets_iframe = null;
	var template_widgets_iframe_body = null;
	var template_widgets_droppable = null;
	var selected_template_widgets_droppable = null;
	var template_widgets_options = null;
	var messages = null;
	var widget_header = null;
	var droppable_header = null;
	
	var on_ready_dependencies_count = 0;
	
	if (typeof MyHtmlBeautify != "object") {
		var msg = "You must include the MyHtmlBeautify javascript class!";
		alert(msg);
		throw new Error(msg);
		return null;
	}
	
	if (typeof TextSelection != "function") {
		var msg = "You must include the TextSelection javascript class!";
		alert(msg);
		throw new Error(msg);
		return null;
	}
	
	me.TextSelection = new TextSelection();
	me.LayoutUIEditorFormField = typeof LayoutUIEditorFormField == "function" ? new LayoutUIEditorFormField(me) : null;
	me.LayoutUIEditorWidgetResource = typeof LayoutUIEditorWidgetResource == "function" ? new LayoutUIEditorWidgetResource(me) : null;
	
	me.options = {
		//classes and selectors
		ui_element: null,
		ui_selector: ".layout-ui-editor",
		menu_widgets_class: "menu-widgets",
		menu_layers_class: "menu-layers",
		menu_settings_class: "menu-settings",
		resize_panels_class: "resize-panels",
		template_source_class: "template-source",
		template_full_source_class: "template-full-source",
		template_preview_class: "template-preview",
		template_widgets_class: "template-widgets",
		template_widgets_iframe_class: "template-widgets-droppable",
		template_widgets_options_class: "template-widgets-options",
		messages_class: "messages",
		widget_header_class: "widget-header",
		droppable_header_class: "droppable-header",
		template_widget_source_popup_class: "template-widget-source-popup",
		popup_class: "myfancypopup with_title",
		
		//other classes and selectors
		main_html_tags_selector: "html, head, body",
		
		//vars
		template_preview_html_url: null,
		auto_convert: false,
		beautify: true,
		rtl: false, //for arabic editor where you read from right to left
		menu_widgets_order: ["generic", "html-tag", "html-text", "html-code", "href", "image", "video", "form", "table", "bootstrap", "grid", "advanced"],
		menu_widgets_groups_collapsed: ["table", "grid", "advanced"],
		moving_tolerance_grid: [10, 10],
		
		//generic global handlers
		template_source_editor_save_func: null,
		on_ready_func: null, //on ready function
		on_template_widgets_iframe_reload_func: null, //on reload template_widgets_iframe
		on_panels_resize_func: null, //when resize_panels gets draggable and panels' positioning gets updated
		on_flip_panels_side_func: null,
		on_choose_page_url_func: null,
		on_choose_image_url_func: null,
		on_choose_webroot_file_url_func: null,
		on_choose_variable_func: null,
		on_choose_event_func: null, //popup to choose an event.
		
		//generic widgets handlers
		on_template_widgets_layout_changed_func: null, //on changed some element inside of template_widgets_iframe_body
		on_drag_helper_func: null,
		on_drag_start_func: null,
		on_drag_stop_func: null,
		on_clone_menu_widget_func: null,
		on_create_template_widget_func: null,
		on_open_widget_header_func: null,
		on_close_widget_header_func: null,
		on_open_droppable_header_func: null,
		on_close_droppable_header_func: null,
		on_open_settings_func: null,
		on_close_settings_func: null,
		on_resize_settings_func: null,
		on_before_parse_widget_settings_func: null,
		on_after_parse_widget_settings_func: null,
		on_before_save_settings_field_func: null,
		on_after_save_settings_field_func: null,
		on_parse_template_widget_html_func: null,
		on_clean_template_widget_html_func: null,
		
		//specific widgets handlers
		on_convert_project_url_php_vars_to_real_values_func: null,
		on_convert_project_url_real_values_to_php_vars_func: null,
	};
	me.menu_widgets_objs = {};
	me.obj_var_name = "";
	me.selected_template_widget = null;
	me.selected_template_droppable = null;
	me.copied_template_widget = null;
	me.copied_type_template_widget = null;
	
	me.popup_settings = null;
	me.popup_elm = null;
	me.popup_obj = null;
	
	//must be here in the begining bc is used bellow
	me.getLayoutUIEditorFolderPath = function() {
		var pos = this_script_file_path.lastIndexOf("/");
		pos = this_script_file_path.substr(0, pos).lastIndexOf("/");
		return this_script_file_path.substr(0, pos + 1);
	};
	
	me.available_html_tags = {
		"p": "P",
		"h1": "H1",
		"h2": "H2",
		"h3": "H3",
		"h4": "H4",
		"h5": "H5",
		"h6": "H6",
		"div": "Div",
		"section": "Section",
		"article": "Article",
		"blockquote": "Blockquote",
		"address": "Address",
		"pre": "Pre",
		"figure": "Figure",
		"figcaption": "Figcaption",
		"hgroup": "Hgroup",
		"aside": "Aside",
		"ul": "Ul",
		"ol": "Ol",
		"dl": "Dl",
		"br": "Br",
		"wbr": "Wbr",
		"nobr": "Nobr",
		"hr": "Hr",
		"img": "Img",
		"image": "Image",
		"span": "Span",
		"label": "Label",
		"td": "Td",
		"th": "Th",
		"footer": "Footer",
		"header": "Header",
		"main": "Main",
		"nav": "Nav",
		"dd": "Dd",
		"dt": "Dt",
		"li": "Li",
		"a": "A - Link",
		"abbr": "Abbr",
		"b": "B",
		"bdi": "Bdi",
		"bdo": "Bdo",
		"cite": "Cite",
		"code": "Code",
		"data": "Data",
		"dfn": "Dfn",
		"em": "Em",
		"i": "I",
		"kbd": "KBd",
		"mark": "Mark",
		"q": "Q",
		"rb": "Rb",
		"rp": "Rp",
		"rt": "Rt",
		"rtc": "Rtc",
		"ruby": "Ruby",
		"s": "S",
		"samp": "Samp",
		"small": "Small",
		"strong": "Strong",
		"sub": "Sub",
		"sup": "Sup",
		"time": "Time",
		"u": "U",
		"var": "Var",
		"area": "Area",
		"audio": "Audio",
		"map": "Map",
		"track": "Track",
		"video": "Video",
		"embed": "Embed",
		"object": "Object",
		"param": "Param",
		"picture": "Picture",
		"svg": "SVG",
		"source": "Source",
		"canvas": "Canvas",
		"noscript": "Noscript",
		"del": "Del",
		"ins": "Ins",
		"col": "Col",
		"colgroup": "Colgroup",
		"datalist": "Datalist",
		"fieldset": "Fieldset",
		"form": "Form",
		"input": "Input",
		"select": "Select",
		"textarea": "Textarea",
		"button": "Button",
		"legend": "Legend",
		"meter": "Meter",
		"output": "Output",
		"details": "Details",
		"dialog": "Dialog",
		"menu": "Menu",
		"summary": "Summary",
		"slot": "Slot",
		"template": "Template",
		"acronym": "Acronym",
		"applet": "Applet",
		"basefont": "Basefont",
		"bgsound": "Bgsound",
		"big": "Big",
		"blink": "Blink",
		"center": "Center",
		"command": "Command",
		"content": "Content",
		"dir": "Dir",
		"element": "Element",
		"font": "Font",
		"frame": "Frame",
		"frameset": "Frameset",
		"isindex": "Isindex",
		"keygen": "Keygen",
		"listing": "Listing",
		"marquee": "Marquee",
		"menuitem": "Menuitem",
		"multicol": "Multicol",
		"nextid": "Nextid",
		"noembed": "Noembed",
		"noframes": "Noframes",
		"plaintext": "Plaintext",
		"shadow": "Shadow",
		"spacer": "Spacer",
		"strike": "Strike",
		"tt": "Tt",
		"xmp": "Xmp"
	};
	
	me.singular_html_tags = ["br", "hr"];
	
	me.getAvailableHtmlTagsOptionsHtml = function() {
		var html = '';
		
		for (var tag_name in me.available_html_tags)
			html += '<option value="' + tag_name + '">' + me.available_html_tags[tag_name] + '</option>';
		
		return html;
	};
	
	me.default_attributes_name_to_convert_to_or_from_php_vars = ["src", "href"];
	
	me.default_template_full_source_html = MyHtmlBeautify.beautify('<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">'
		//+ '<link rel="stylesheet" href="' + me.getLayoutUIEditorFolderPath() + 'vendor/bootstrap/bootstrap.min.css">'
		//+ '<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" crossorigin="anonymous">'
	+ '</head><body></body></html>');
	
	me.default_menu_widget_tag_html = '<li class="draggable menu-widget menu-widget-html-tag html-tag" data-tag="html-tag" title="Html Tag" data-on-clone-menu-widget-func="#LayoutUIEditor#.onCloneMenuHtmlTagWidget" data-on-clean-template-widget-html-func="#LayoutUIEditor#.onCleanTemplateHtmlTagWidget" data-on-create-template-widget-func="#LayoutUIEditor#.onCreateTemplateHtmlTagWidget" data-resizable="1" data-absolute-position="1">'
			+ '<span>Html Block</span>'
			+ '<div class="droppable template-widget template-widget-html-tag html-tag" data-label="Html Tag">'
				+ 'Some Text'
			+ '</div>'
			+ '<div class="properties" data-on-after-save-settings-field-func="#LayoutUIEditor#.saveHtmlTagWidgetSettingsField" data-on-before-parse-widget-settings-func="#LayoutUIEditor#.parseHtmlTagWidgetSettings" data-on-after-parse-widget-settings-func="#LayoutUIEditor#.filterHtmlTagWidgetSettings" data-on-open-settings-func="#LayoutUIEditor#.openHtmlTagSettings">'
				+ '<div class="settings-property html-tag with-swap-field select-shown">'
					+ '<label>HTML Tag: </label>'
					+ '<input name="html-tag" value="div" title="Enter your custom tag\'s name here...">'
					+ '<select name="html-tag" placeholder="Choose your custom tag\'s name here...">'
						+ '<option></option>'
						+ me.getAvailableHtmlTagsOptionsHtml()
					+ '</select>'
					+ '<i class="zmdi zmdi-swap swap-input-select" onClick="$(this).parent().toggleClass(\'select-shown\')" title="Swap between pre-defined options or open text option"></i>'
				+ '</div>'
			+ '</div>'
		+ '</li>';
	
	me.default_menu_widget_text_html = '<li class="draggable menu-widget menu-widget-html-text html-text" data-tag="html-text" title="Text" data-on-clone-menu-widget-func="#LayoutUIEditor#.onCloneMenuHtmlTextWidget">'
			+ '<span>Text</span>'
			+ '<div class="template-widget template-widget-html-text html-text" data-label="Text">' //Note that this will be replaced by a text-node in the onCloneMenuHtmlTextWidget function
				+ 'text'
			+ '</div>'
		+ '</li>';
	
	me.default_menu_widget_code_html = '<li class="draggable menu-widget menu-widget-html-code html-code" data-tag="html-code" title="Html Code" data-on-drag-stop-func="#LayoutUIEditor#.onDropMenuHtmlCodeWidget">'
			+ '<span>Html Code</span>'
			+ '<div class="template-widget template-widget-html-code html-code"></div>'
		+ '</li>';
	
	me.default_menu_widget_comment_html = '<li class="draggable menu-widget menu-widget-html-comment html-comment" data-tag="html-comment" title="Html Comment" data-on-create-template-widget-func="#LayoutUIEditor#.onCreateTemplateHtmlCommentWidget" data-on-clean-template-widget-html-func="#LayoutUIEditor#.onCleanTemplateCommentWidgetHtml">'
			+ '<span>Html Comment</span>'
			+ '<div class="template-widget template-widget-html-comment html-comment" data-label="Html Comment" data-is-widget-html-comment>'
				+ '<pre></pre>'
			+ '</div>'
		+ '</li>';
	
	/* INIT METHODS */
	
	me.init = function(obj_var_name) {
		me.obj_var_name = obj_var_name;
		on_ready_dependencies_count = 0;
		
		if (me.options.ui_element instanceof jQuery)
			ui = me.options.ui_element;
		else if (me.options.ui_element)
			ui = me.options.ui_element;
		else
			ui = $(me.options.ui_selector);
		
		if (!ui[0]) {
			var msg = "UI html element does not exist! Please edit the " + me.obj_var_name + ".options.ui_selector with an existent selector or " + me.obj_var_name + ".options.ui_element with an existent html element.";
			me.showException(msg);
			throw new Error(msg);
			return null;
		}
		else if (me.options.ui_element) { //set new ui_selector
			var ui_id = ui[0].hasAttribute("id") ? "#" + ui.attr("id") : "";
			var ui_class = ui[0].hasAttribute("class") ? "." + ui.attr("class").replace(/^\s+/g, "").replace(/\s+$/g, "").replace(/\s+/g, " ").replace(/ /g, ".") : "";
			
			if (ui_id || ui_class)
				me.options.ui_selector = ui_id + ui_class;
		}
		
		ui.data("LayoutUIEditor", me); //This can be very usefull so we can call the LayoutUIEditor directly from the ui html element. This is to be used by external apps
		
		menu_widgets = ui.children("." + me.options.menu_widgets_class);
		menu_layers = ui.children("." + me.options.menu_layers_class);
		menu_settings = ui.children("." + me.options.menu_settings_class);
		resize_panels = ui.children("." + me.options.resize_panels_class);
		template_source = ui.children("." + me.options.template_source_class);
		template_preview = ui.children("." + me.options.template_preview_class);
		template_full_source = ui.children("." + me.options.template_full_source_class);
		template_widgets = ui.children("." + me.options.template_widgets_class);
		template_widgets_iframe = template_widgets.children("iframe." + me.options.template_widgets_iframe_class); //very important to hard code the iframe here bc it might exist an element with the class template-widgets-droppable which contains the default html.
		template_widgets_options = ui.children("." + me.options.template_widgets_options_class);
		messages = ui.children("." + me.options.messages_class);
		template_widgets_iframe_body = null;
		template_widgets_droppable = null;
		
		//creating default ui structure
		if (!menu_widgets[0]) {
			menu_widgets = $('<ul class="' + me.options.menu_widgets_class + ' hidden"></ul>');
			ui.prepend(menu_widgets);
		}
		else
			menu_widgets.addClass("hidden");
		
		if (!menu_layers[0]) {
			menu_layers = $('<ul class="' + me.options.menu_layers_class + ' hidden"></ul>');
			menu_layers.insertAfter(menu_widgets);
		}
		else
			menu_layers.addClass("hidden");
		
		if (!menu_settings[0]) {
			menu_settings = $('<ul class="' + me.options.menu_settings_class + ' hidden"></ul>');
			menu_settings.insertAfter(menu_layers);
		}
		else
			menu_settings.addClass("hidden");
		
		if (!resize_panels[0]) {
			resize_panels = $('<div class="' + me.options.resize_panels_class + ' hidden"><div class="button"></div></div>');
			resize_panels.insertAfter(menu_settings);
		}
		else
			resize_panels.addClass("hidden");
		
		if (!template_source[0]) {
			template_source = $('<div class="' + me.options.template_source_class + ' hidden" data-created-by-system="1"><textarea></textarea></div>');
			template_source.insertAfter(resize_panels);
		}
		else
			template_source.addClass("hidden");
		
		if (!template_full_source[0]) {
			template_full_source = $('<div class="' + me.options.template_full_source_class + ' hidden" data-created-by-system="1"><textarea></textarea></div>');
			template_full_source.insertAfter(template_source);
		}
		else
			template_full_source.addClass("hidden");
		
		if (!template_preview[0]) {
			template_preview = $('<div class="' + me.options.template_preview_class + ' hidden"><iframe></iframe></div>');
			template_preview.insertAfter(template_full_source);
		}
		else
			template_preview.addClass("hidden");
		
		if (!template_widgets[0]) {
			template_widgets = $('<div class="' + me.options.template_widgets_class + ' hidden"></div>');
			template_widgets.insertAfter(template_preview);
		}
		
		if (!template_widgets_iframe[0]) {
			template_widgets_iframe = $('<iframe class="' + me.options.template_widgets_iframe_class + '"></iframe>');
			template_widgets.html("").append(template_widgets_iframe);
		}
		
		if (!template_widgets_options[0]) {
			template_widgets_options = $('<div class="' + me.options.template_widgets_options_class + ' hidden"></div>');
			template_widgets_options.insertAfter(template_widgets);
		}
		
		if (!messages[0]) {
			messages = $('<div class="' + me.options.messages_class + ' hidden"></div>');
			messages.insertAfter(template_widgets_options);
		}
		
		//set main options
		setMainOptions(ui);
		
		//set template layout options
		setTemplateLayoutOptions();
		
		//set resize panels
		initResizePanels();
		
		//set template source panel
		initTemplateSource(); //This needs to be before the irame gets loaded. This is, this must be syncronhonous so the user can get the ace editor directly or set the html code.
		
		//set template page source panel
		initTemplateFullSource(); //This needs to be before the irame gets loaded. This is, this must be syncronhonous so the user can get the ace editor directly or set the html code.
		
		//init template iframe
		//DEPRECATED: fixing issue in firefox with the iframes. Basically firefox has a bug and doesn't let write on iframes dynamically, so we need to execute the following code. INSTEAD OF THIS CODE, WE FIX THIS BUG WITH THE IFRAME LOAD HANDLER. 
		/*var target = template_widgets_iframe.contents()[0];
		target.open();
		target.write('<!doctype html><html style="height:100%"><head></head><body style="height:100%"></body></html>');
		target.close();*/
		
		//We need to execute the rest of the code inside of initAfterIframeReady, only when the iframe is loaded, otherwise it won't work in firefox.
		var iframe_on_load_func = function() {
			//console.log("template-layout iframe loading...");
			
			if (template_widgets_iframe.attr("inited") != "1") { //just in case, in order to do not execute twice which will give a bunch of errors
				template_widgets_iframe.attr("inited", 1);
				
				template_widgets_iframe.unbind("load", iframe_on_load_func); //unbind the load func. This is very important otherwise if we cahnge the layout-ui-editor to another place dynamically, like a sort between multiple editors, this will trigger the load event of the iframe and it will execute it again... So we want to unbind this function.
				
				initAfterIframeReady();
			}
		};
		
		template_widgets_iframe.load(iframe_on_load_func);
		
		var timeout_milli_secs = 100;
		
		if (template_widgets_iframe.attr("data-init-src")) { //in case the iframe be hardcoded in the html, we cannot have the src attribute, bc the load event must be set before, so we must use instead the data-init-src attribute.
			template_widgets_iframe.attr("src", template_widgets_iframe.attr("data-init-src"));
			
			timeout_milli_secs = 5000; //5 secs. this is just in case, bc this case should not happen.
		}
		
		//Chrome doesn't trigger the load event for iframe without the src attribute, so we need to do it manually.
		setTimeout(function() {
			if (template_widgets_iframe.attr("inited") != "1")
				template_widgets_iframe.trigger("load");
		}, timeout_milli_secs);
	};
	
	function initAfterIframeReady() {
		template_widgets_iframe_body = template_widgets_iframe.contents().find("body");
		
		//template_widgets_droppable = template_widgets_iframe_body.children(".droppable");
		template_widgets_droppable = template_widgets_iframe_body.find(".main-droppable");
		
		if (!template_widgets_droppable[0] && template_widgets_iframe_body.is(".main-droppable"))
			template_widgets_droppable = template_widgets_iframe_body;
		
		if (!template_widgets_droppable[0]) {
			template_widgets_droppable = $('<div class="droppable main-droppable"></div>');
			//template_widgets_droppable = $('<div class="droppable main-droppable" data-main-droppable-name="default"></div>');
			template_widgets_iframe_body.html("").append(template_widgets_droppable);
		}
		
		selected_template_widgets_droppable = template_widgets_droppable.first();
		
		//check all droppables from jquery and resetting, this is, remove the invalid droppables.
		//if we create a jquery droppable element inside of an iframe and then move the iframe or delete it or reload it, the droppable elements stop to exist, but jquery continues with them registered. So we need to call the resetDroppables() function to fix this cases, otherwise we will have weird javascript errors.
		me.resetDroppables();
		
		//set main tabs
		setMainTabs(ui);
		
		//init widgets in menu
		initMenuWidgets();
		
		//init menu layers panel
		initMenuLayers();
		
		//init menu settings panel
		initMenuSettings();
		
		//set template widgets panel
		initTemplateWidgets();
		
		//init text selection
		initTextSelection();
		
		//init messages
		initMessages();
		
		//set template preview panel
		initTemplatePreview();
		
		//set widget header
		initWidgetHeader(template_widgets);
		
		//set droppable header
		initDroppableHeader(template_widgets);
		
		//show template source by default
		me.showTemplateSource();
		
		//preparing template layout inside of droppables if any html
		for (var i = 0; i < template_widgets_droppable.length; i++) {
			var droppable_elm = $(template_widgets_droppable[i]);
			me.parseElementInnerHtml(droppable_elm, {do_update_menu_layers: true}); //Do not use the parseHtmlSource, otherwise it will loose the previous events set to the children .not-widget. This method was created so it could work together with the edit_entity_simple.php
		}
		
		//prepare menu layers
		prepareMenuLayers();
		
		//prepare template widgets with options
		prepareTemplateLayoutAccordingWithOptions();
		
		//init form fields
		initLayoutUIEditorFormField();
		
		//init widget resources
		initLayoutUIEditorWidgetResource();
		
		//continue only if ready
		onReady();
	}
	
	//onReady only gets executed after the init function finish.
	function onReady() {
		if (on_ready_dependencies_count > 0)
			setTimeout(function() {
				onReady();
			}, 300);
		else {
			if (typeof me.options.on_ready_func == "function")
				me.options.on_ready_func(me);
			
			//in case the template-widgets-droppable is hard-coded in the layout-ui-editor.php, init the template_source.
			var tw_droppable = template_widgets.children("." + me.options.template_widgets_iframe_class).not("iframe");
			if (tw_droppable[0] && tw_droppable.children().length > 0) {
				var html = tw_droppable[0].innerHTML; //Do not beautify bc it can break the php and ptl tags
				me.setTemplateSourceEditorValue(html);
				convertTemplateSourceToFullSource();
				
				tw_droppable.remove();
			}
		}
	}
	
	/* INIT METHODS - MENU WIDGETS */
	
	function initMenuWidgets() {
		addDefaultMenuWidgets();
		
		var widgets = menu_widgets.find(".menu-widget");
		on_ready_dependencies_count += widgets.length;
		
		//combine similar groups and hide empty ones
		var groups = menu_widgets.find(".group");
		
		for (var i = 0, ti = groups.length; i < ti; i++) {
			var group = $(groups[i]);
			var group_ul = group.children("ul");
			
			if (group_ul.children().length == 0)
				group.hide();
			else {
				var group_title_text = group.children(".group-title").text().replace(/\s+/g, "").toLowerCase();
				var brothers = group.parent().children(".group");
				
				for (var j = 0, tj = brothers.length; j < tj; j++) {
					var brother = $(brothers[j]);
					
					if (!brother.is(group[0]) && brother.children(".group-title").text().replace(/\s+/g, "").toLowerCase() == group_title_text) {
						group_ul.append( brother.children("ul").children() );
						brother.hide();
					}
				}
				
				if ($.isArray(me.options.menu_widgets_groups_collapsed) && me.options.menu_widgets_groups_collapsed.length > 0) {
					var is_collapsed = me.options.menu_widgets_groups_collapsed.indexOf(group_title_text) != -1;
					
					if (!is_collapsed)
						for (var j = 0, tj = me.options.menu_widgets_groups_collapsed.length; j < tj; j++)
							if (me.options.menu_widgets_groups_collapsed[j].replace(/\s+/g, "").toLowerCase() == group_title_text) {
								is_collapsed = true;
								break;
							}
					
					if (is_collapsed)
						group.removeClass("group-open");
				}
			}
		}
		
		//reorder widgets
		if ($.isArray(me.options.menu_widgets_order) && me.options.menu_widgets_order.length > 0) {
			var sort_groups_func = function(parent) {
				var groups_by_title = {};
				var widgets_by_tag = {};
				var parent_groups = parent.children(".group");
				var parent_widgets = parent.children(".menu-widget");
				
				$.each(parent_groups, function (i, group) {
					var group_title = $(group).children(".group-title").text().replace(/\s+/g, "").toLowerCase();
					
					if (group_title && !groups_by_title.hasOwnProperty(group_title)) {
						groups_by_title[group_title] = group;
						
						var ul = $(group).children("ul");
						sort_groups_func(ul);
					}
				});
				
				$.each(parent_widgets, function (i, widget) {
					var widget_tag = $(widget).attr("data-tag");
					
					if (widget_tag && !widgets_by_tag.hasOwnProperty(widget_tag))
						widgets_by_tag[widget_tag] = widget;
				});
				
				if (!$.isEmptyObject(groups_by_title) || !$.isEmptyObject(widgets_by_tag))
					for (var i = me.options.menu_widgets_order.length - 1; i >= 0; i--) {
						var menu_widget_title_or_tag = me.options.menu_widgets_order[i].replace(/\s+/g, "").toLowerCase();
						
						if (widgets_by_tag.hasOwnProperty(menu_widget_title_or_tag))
							parent.prepend(widgets_by_tag[menu_widget_title_or_tag]);
						
						if (groups_by_title.hasOwnProperty(menu_widget_title_or_tag))
							parent.prepend(groups_by_title[menu_widget_title_or_tag]);
					}
			};
			
			sort_groups_func(menu_widgets);
		}
		
		//add menu-widgets javascript and styles to template widgets iframe
		prepareTemplateWidgetsIframeWithMenuWidgetsJSAndStyles();
		
		//init menu widgets
		$.each(widgets, function(idx, item) {
			item = $(item);
			var tag = item.attr("data-tag");
			tag = tag.replace(/\s+/g, "");
			
			if (!tag)
				me.showException("ERROR: There is an widget without any TAG. All widgets must have tags!\nWidget HTML:\n" + item.html());
			else if (!me.menu_widgets_objs[tag]) {
				item.attr("data-tag", tag);//update tag in case of it contains spaces or end lines...
				
				//set default template class.
				var template_widget = item.children(".template-widget");
				
				var classes = me.getTemplateWidgetCurrentClassesWithoutReservedClasses(template_widget);
				item.data("data-template-widget-default-class", classes);
				
				//set default on-clone-menu-widget-func if not exist
				if (!item[0].hasAttribute("data-on-clone-menu-widget-func"))
					item.attr("data-on-clone-menu-widget-func", me.obj_var_name + ".onCloneMenuWidget");
				
				//init widgets objects
				var create_widget_class = item.attr("data-create-widget-class");
				var create_widget_func = item.attr("data-create-widget-func");
				
				if (create_widget_class && create_widget_class.replace(/\s+/g, "") != "" && eval('typeof ' + create_widget_class + ' == "function"'))
					eval('me.menu_widgets_objs[tag] = new ' + create_widget_class + '(me, item);');
				
				if (create_widget_func && create_widget_func.replace(/\s+/g, "") != "" && eval('typeof ' + create_widget_func + ' == "function"'))  {
					if (!me.menu_widgets_objs[tag])
							eval('me.menu_widgets_objs[tag] = ' + create_widget_func + '(me, item);');
					else 
						me.showException('ERROR: Menu widget (with tag: "' + tag + '") cannot contains data-create-widget-class and data-create-widget-func attributes at the same time!');
				}
				
				if (me.menu_widgets_objs[tag] && typeof me.menu_widgets_objs[tag].init == "function")
					me.menu_widgets_objs[tag].init();
				
				//Sample of data-create-widget-class:
				//	me.menu_widgets_objs.TextareaWidget = new TextareaWidget(me); //is already the object
				//	me.menu_widgets_objs.TextareaWidget.init();
				//or 
				//Sample of data-create-widget-func:
				//	me.menu_widgets_objs.TextareaWidget = TextareaWidget(me); //must return an object
				//	me.menu_widgets_objs.TextareaWidget.init();
			}
			else 
				me.showException('ERROR: Menu widget repeated with the same tag: "' + tag + '"! Tags must be unique!');
			
			if (!item[0].hasAttribute("id") || item[0].id.replace(/\s+/g, "") == "")
				item[0].id = "menu_widget_" + item.attr("data-tag") + "_" + makeRandomId();
			
			on_ready_dependencies_count--;
		});
		
		//set menu widgets panel
		menu_widgets.find(".group > .group-title").click(function(event) {
			var p = $(this).parent();
			p.children("ul").toggle("slow");
			p.toggleClass("group-open");
			$(this).children(".toggle").toggleClass("zmdi-caret-right zmdi-caret-down");
		});
		
		//set draggable widgets
		widgets.each(function(idx, widget) {
			me.setDraggableMenuWidget(widget);
		});
		
		var doc = menu_widgets[0].ownerDocument || document;
		$(doc).bind("keyup", function(e) {
			if (e.which === 27 || e.keyCode === 27) { //if escape key
				$(".menu-widget.ui-draggable-dragging[data-tag]").data("escape_key_pressed", true).trigger('mouseup');
			}
		});
	}
	
	//add menu widget css and js to template widgets iframe 
	function prepareTemplateWidgetsIframeWithMenuWidgetsJSAndStyles() {
		var widgets = menu_widgets.find(".menu-widget");
		var css = "";
		var js = "";
		var repeated = [];
		
		$.each(widgets, function(idx, item) {
			item = $(item);
			
			var tag = item.attr("data-tag");
			tag = tag.replace(/\s+/g, "");
			
			if (tag && repeated.indexOf(tag) == -1) {
				repeated.push(tag);
				
				css += item.find(".template-css").text();
				js += item.find(".template-js").text();
			}
		});
		
		try {
			var head = template_widgets_iframe.contents().find("head");
			
			if (css != "")
				head.append('<style class="layout-ui-editor-reserved template-widgets-css-code">' + css + '</style>');
			
			if (js != "")
				head.append('<javascript class="layout-ui-editor-reserved template-widgets-js-code">' + js + '</javascript>');
		}
		catch(e) { //in case there are some javascript erros, which then will break this Editor
			me.showException("Javascript Error: " + (e && e.message ? e.message : e));

			if (console && console.log) 
				console.log(e);
		}
	}
	
	//This can be used by external pages that wish to add manual html elements to the editor
	me.setDraggableMenuWidget = function(menu_widget, opts) {
		opts = opts ? opts : {};
		
		var draggable_opts = {
			//settings for the iframe droppable
			iframeFix:true,
			iframeScroll: true,
			scroll: true,
			scrollSensitivity: 20,

			//others settings
			//grid: [ 20, 20 ],
			appendTo: "body",
			//appendTo: ".layout-ui-editor",
			containment: "window",
			cursor: "move",
			tolerance: "pointer",
			
			helper: function(event, ui_obj) {
				var func = $(this).attr("data-on-drag-helper-func");
				var is_func = func && eval('typeof ' + func + ' == "function"');
				var ret = null;
				
				if (is_func)
					eval('ret = ' + func + '(this, event, ui_obj);');
				else
					ret = $(this).clone();
				
				if (typeof me.options.on_drag_helper_func == "function")
					ret = me.options.on_drag_helper_func(this, event, ui_obj, ret);
				
				if (typeof opts.on_drag_helper_func == "function")
					ret = opts.on_drag_helper_func(this, event, ui_obj, ret);
				
				if ($.isNumeric(ui.css("zIndex")))
					ret.css("zIndex", ui.css("zIndex") + 1);
				
				return ret;
			},
			start: function(event, ui_obj) {
				me.onWidgetDraggingStart(event, ui_obj.helper, null);
				
				var func = $(this).attr("data-on-drag-start-func");
				var is_func = func && eval('typeof ' + func + ' == "function"');
				var ret = true;
				
				if (is_func)
					eval('ret = ' + func + '(this, event, ui_obj);');
				
				if (ret && typeof me.options.on_drag_start_func == "function")
					ret = me.options.on_drag_start_func(this, event, ui_obj, ret);
				
				if (ret && typeof opts.on_drag_start_func == "function")
					ret = opts.on_drag_start_func(this, event, ui_obj, ret);
				
				return ret;
			},
			drag: function(event, ui_obj) {
				me.onWidgetDraggingDrag(event, ui_obj.helper, null);
			},
			stop: function(event, ui_obj) {
				//Set all draggable parts back to revert: false
				//This fixes elements after drag was cancelled with ESC key
				var drag_cancelled = ui_obj.helper.data("escape_key_pressed");
				ui_obj.helper.data("escape_key_pressed", null);
				
				var widget_droppable = null;
				
				if (!drag_cancelled) {
					var elm_from_point = me.getWidgetDroppedElement(event, ui_obj.helper);
					
					if (elm_from_point) {
						widget_droppable = $(elm_from_point).closest(".droppable");
						//console.log(elm_from_point);
						//console.log(widget_droppable[0]);
						
						if (widget_droppable[0]) { //if helper is dropped in a non allowed area, there will be NO droppable element.
							var menu_widget_to_create_template_widget = opts["menu_widget_to_create_template_widget"] ? opts["menu_widget_to_create_template_widget"] : this; //This is used by outside menu_widgets, like in the layout_ui_editor_right_container inside of the edit_template_simple.php
							
							var widget = createTemplateWidgetFromMenuWidget($(menu_widget_to_create_template_widget), template_widgets_iframe_body, null); //must be template_widgets_iframe_body. Do not replace template_widgets_iframe_body by widget_droppable or template_widgets_droppable or template_widgets_droppable.parent(), otherwise the onWidgetDraggingStop won't work correctly bc when it gets the getWidgetDroppedElement, it will get this same widget and in same cases it will give an exception. Basically the new nodes must be saved in the body and only then will be added to the right parent. Note that we can have multiple template_widgets_droppable and the template_widgets_droppable.parent(), may not be the iframe body. Use template_widgets_iframe_body!
							
							var is_body_droppable = template_widgets_droppable.is(template_widgets_iframe_body);
							
							//hide widget, bc if the main-droppable is body, when we execute the onWidgetDraggingStop, the getWidgetDroppedElement will be the widget it-self and then we will append the widget to the widget it-self or to a child, so we must hide it first, so we get the right droppable. Otherwise we will get a javascript error, bc we cannot append the widget to it-self or to a child.
							if (is_body_droppable && widget[0].nodeType == Node.ELEMENT_NODE) {
								var display = widget[0].style.display;
								var is_hidden = me.hasNodeClass(widget, "widget-hidden");
								widget[0].style.display = 'none';
								!is_hidden && me.addNodeClass(widget, "widget-hidden");
								
								me.onWidgetDraggingStop(event, ui_obj.helper, widget);
								
								//remove the display none, set before
								widget[0].style.display = display;
								!is_hidden && me.removeNodeClass(widget, "widget-hidden");
							}
							else
								me.onWidgetDraggingStop(event, ui_obj.helper, widget);
							
							//prepare offset if a parent has position absolute or fixed
							me.updateWidgetPositionBasedInParent(event, ui_obj.helper, widget, ui_obj);
							
							//call stop dragging handlers
							var func = $(this).attr("data-on-drag-stop-func");
							if (func && eval('typeof ' + func + ' == "function"'))
								eval(func + '(this, widget, event, ui_obj);');
							
							if (typeof me.options.on_drag_stop_func == "function")
								me.options.on_drag_stop_func(this, widget, event, ui_obj);
							
							if (typeof opts.on_drag_stop_func == "function")
								opts.on_drag_stop_func(this, widget, event, ui_obj);
							
							//call layout changed handler
							if (typeof me.options.on_template_widgets_layout_changed_func == "function")
								me.options.on_template_widgets_layout_changed_func(widget);
							
							//trigger click so it opens the menu settings
							widget.trigger("click");
						}
						else
							me.onWidgetDraggingStop(event, ui_obj.helper, null);
					}
					else
						me.onWidgetDraggingStop(event, ui_obj.helper, null);
				}
				else
					me.onWidgetDraggingStop(event, ui_obj.helper, null);
				
				//if (!widget_droppable || !widget_droppable[0]) {
					var o = $(this).offset();
					var clone = ui_obj.helper.clone();
					ui_obj.helper.after(clone);
					
					clone.animate({
						top: o.top + "px",
						left: o.left + "px",
					}, {
						duration: 400,
						complete: function() {
							clone.remove();
						}
					});
				//}
				
				ui_obj.helper.remove();
			},
		};
		
		$(menu_widget).draggable(draggable_opts);
	};
	
	me.onWidgetDraggingStart = function(event, dragged_elm, widget) {
		template_widgets_droppable.addClass("dropping");
		
		hideWidgetHeader();
		hideDroppableHeader();
	};
	
	me.onWidgetDraggingDrag = function(event, dragged_elm, widget) {
		var elm_from_point = me.getWidgetDroppedElement(event, dragged_elm);
		
		template_widgets_droppable.removeClass("highlight highlight-parent-left highlight-parent-right highlight-parent-top highlight-parent-bottom");
		template_widgets_droppable.find(".highlight, .highlight-left, .highlight-right, .highlight-top, .highlight-bottom, .highlight-parent-left, .highlight-parent-right, .highlight-parent-top, .highlight-parent-bottom").removeClass("highlight highlight-left highlight-right highlight-top highlight-bottom highlight-parent-left highlight-parent-right highlight-parent-top highlight-parent-bottom");
		
		if (elm_from_point) {
			elm_from_point = $(elm_from_point);
			me.addNodeClass(elm_from_point.closest(".droppable"), "highlight");
			
			if (me.hasNodeClass(elm_from_point, "droppable")) {
				var c = me.getWidgetDroppedOverPosition(event, dragged_elm, elm_from_point);
				var contents = elm_from_point.contents().filter(function() {
					return this.nodeType === Node.TEXT_NODE || this.nodeType === Node.ELEMENT_NODE;
				});
				
				if (c == "top" || c == "left") {
					var node = contents.first();
					
					if (node[0] && node[0].nodeType == Node.ELEMENT_NODE && (!widget || !widget.is(node))) {
						if (node.css("display") == "block")
							me.addNodeClass(node, "highlight-top");
						else
							me.addNodeClass(node, "highlight-left");
					}
					else
						me.addNodeClass(elm_from_point, "highlight-parent-" + c);
				}
				else {
					var node = contents.last();
					
					if (node[0] && node[0].nodeType == Node.ELEMENT_NODE && (!widget || !widget.is(node))) {
						if (node.css("display") == "block")
							me.addNodeClass(node, "highlight-bottom");
						else
							me.addNodeClass(node, "highlight-right");
					}
					else 
						me.addNodeClass(elm_from_point, "highlight-parent-" + c);
				}
			}
			else {
				var w = elm_from_point.closest(".template-widget");
				
				if (w[0]) 
					me.addNodeClass(w, "highlight-" + me.getWidgetDroppedOverPosition(event, dragged_elm, w) );
			}
		}
	};
	
	me.onWidgetDraggingStop = function(event, dragged_elm, widget) {
		if (widget) {
			//console.log(dragged_elm[0]);
			
			//get droppable element
			var elm_from_point = me.getWidgetDroppedElement(event, dragged_elm); 
			var default_droppable = dragged_elm.parent().closest(".main-droppable");
			//console.log(elm_from_point);
			//console.log(default_droppable[0]);
			
			if (!default_droppable[0]) {
				if (selected_template_widgets_droppable[0])
					default_droppable = selected_template_widgets_droppable;
				else
					default_droppable = template_widgets_droppable.first(); //this is just in case
			}
			
			if (elm_from_point) {
				elm_from_point = $(elm_from_point);
				var widget_droppable = elm_from_point.closest(".droppable");
				
				//insert widget
				if (widget_droppable[0]) { //if helper is dropped in a non allowed area, there will be NO droppable element.
					if (!widget_droppable.is(widget) && !widget_droppable.parents().is(widget)) { //widget cannot be moved inside of a children or it-self
						if (me.hasNodeClass(elm_from_point, "droppable")) {
							var c = me.getWidgetDroppedOverPosition(event, dragged_elm, elm_from_point);
							//console.log(elm_from_point[0]);
							//console.log(widget_droppable[0]);
							//console.log(widget[0]);
							
							/*var has_children = elm_from_point.children(".template-widget").length > 0;
							
							if (has_children) 
								widget_droppable.append(widget);
							else */if (c == "top" || c == "left")
								widget_droppable.prepend(widget);
							else
								widget_droppable.append(widget);
						}
						else {
							var w = elm_from_point.closest(".template-widget");
							
							if (w[0]) {
								if (!w.is(widget)) {
									var c = me.getWidgetDroppedOverPosition(event, dragged_elm, w);
									
									if (c == "top" || c == "left")
										w.before(widget);
									else
										w.after(widget);
								}
								//else do nothing bc is already in the right place
							}
							else
								widget_droppable.append(widget);
						}
						
						if (widget[0].nodeType == Node.ELEMENT_NODE) //could be a text node from the html-text widget
							me.updateMenuLayer(widget);
					}
				}
				else if (default_droppable[0] && !default_droppable.is(widget) && !default_droppable.parents().is(widget)) //widget cannot be moved inside of a children or it-self
					default_droppable.append(widget);
			}
			else if (default_droppable[0] && !default_droppable.is(widget) && !default_droppable.parents().is(widget)) //widget cannot be moved inside of a children or it-self
				default_droppable.append(widget);
		}
		
		template_widgets_droppable.removeClass("highlight highlight-parent-left highlight-parent-right highlight-parent-top highlight-parent-bottom");
		template_widgets_droppable.find(".highlight, .highlight-left, .highlight-right, .highlight-top, .highlight-bottom, .highlight-parent-left, .highlight-parent-right, .highlight-parent-top, .highlight-parent-bottom").removeClass("highlight highlight-left highlight-right highlight-top highlight-bottom highlight-parent-left highlight-parent-right highlight-parent-top highlight-parent-bottom");
		template_widgets_droppable.removeClass("dropping");
	};
	
	me.updateWidgetPositionBasedInParent = function(event, dragged_elm, widget, ui_obj) {
		if (widget.data("absolute-position")) {
			var widget_position = widget.css("position");
			var parent = widget.parent();
			var parent_position = parent.css("position");
			var parent_offset = null;
			var is_parent_body = parent.is(template_widgets_iframe_body);
			
			if (parent_position == "absolute" || parent_position == "fixed" || (
				parent_position == "relative" && (widget_position == "absolute" || widget_position == "fixed" || widget_position == "relative")
			)) {
				parent_offset = parent.offset();
				
				//add iframe offset in case the panels be flipped.
				var o = template_widgets_iframe.offset();
				parent_offset.top = parent_offset.top + o.top;
				parent_offset.left = parent_offset.left + o.left;
			}
			else if (is_parent_body)
				parent_offset = template_widgets_iframe.offset();
			
			if (parent_offset) {
				var dragged_elm_offset = ui_obj ? ui_obj.offset : dragged_elm.offset();
				var top = parseInt(dragged_elm_offset.top - parent_offset.top);
				var left = parseInt(dragged_elm_offset.left - parent_offset.left);
				var position = widget_position == "absolute" || widget_position == "fixed" || widget_position == "relative" ? widget_position : "absolute";
				
				widget.css({position: position, top: top + "px", left: left + "px"});
			}
		}
	};
	
	//get event relative offsets, relative to the body inside of template_widgets_iframe
	me.getWidgetDroppedElementEventPosition = function(event, dragged_elm) {
		var io = template_widgets_iframe.offset();
		var doc = template_widgets_iframe[0].ownerDocument;
		var win = $(doc.defaultView || doc.parentWindow);
		var isl = win.scrollLeft();
		var ist = win.scrollTop();
		
		var x = event.clientX - (io.left - isl);
		var y = event.clientY - (io.top - ist);
		
		//update the offsets to be in the top left corner of the dragged elm.
		if (dragged_elm[0]) {
			var r = dragged_elm[0].getBoundingClientRect();
			var rl = event.clientX - r.left;
			var rt = event.clientY - r.top;
			x -= rl > 0 ? rl : 0;
			y -= rt > 0 ? rt : 0;
		}
		
		return {x: x, y: y};
	};
	
	me.getWidgetDroppedElement = function(event, dragged_elm) {
		//get droppable element
		var o = me.getWidgetDroppedElementEventPosition(event, dragged_elm);
		//console.log(o);
		
		dragged_elm.hide();
		var elm_from_point = template_widgets_iframe_body[0].ownerDocument.elementFromPoint(o.x, o.y);
		dragged_elm.show();
		
		return elm_from_point;
	};
	
	me.getWidgetDroppedOverPosition = function(event, dragged_elm, widget) {
		var o = me.getWidgetDroppedElementEventPosition(event, dragged_elm);
		var x = parseInt(o.x);
		var y = parseInt(o.y);
		
		var wo = widget.offset();
		var doc = template_widgets_iframe_body[0].ownerDocument;
		var win = $(doc.defaultView || doc.parentWindow);
		var st = win.scrollTop();
		var sl = win.scrollLeft();
		var l = parseInt(wo.left - sl);
		var t = parseInt(wo.top - st);
		//console.log(t+":"+o.y+":"+event.clientY);
		
		if (widget.css("display") == "block") {
			var is_top = y >= t && y <= t + Math.ceil(widget.height() / 2);
			return is_top ? "top" : "bottom";
		}
	
		var is_left = x >= l && x <= l + Math.ceil(widget.width() / 2);
		return is_left ? "left" : "right";
	};
	
	/* INIT METHODS - RESIZE PANEL */
	
	function initResizePanels() {
		//set resize panels
		resize_panels.draggable({
			axis: "x",
			appendTo: "body",
			//appendTo: ".layout-ui-editor",
			containment: ui,
			cursor: "move",
			tolerance: "pointer",
			cancel: ".button",
			
			start: function(event, ui_obj) {
				if (!ui.hasClass("with-minimized-panels")) {
					ui.addClass("resizing");
					
					return true;
				}
				
				return false;
			},
			drag: function(event, ui_obj) {
				var perc = parseInt(ui_obj.position.left / ui.width() * 100);
				resizePanels(perc);
			},
			stop: function(event, ui_obj) {
				ui.removeClass("resizing");
				
				var perc = parseInt(ui_obj.position.left / ui.width() * 100);
				resizePanels(perc);
				
				if (me.TextSelection)
					me.TextSelection.refreshMenu();
			},
		});
		
		resize_panels.children(".button").click(function() {
			var is_minimized = ui.hasClass("with-minimized-panels");
			
			if (is_minimized)
				ui.removeClass("with-minimized-panels");
			else
				ui.addClass("with-minimized-panels");
			
			//resize template source popup if open
			setTimeout(function() {
				if (isWidgetSourceShown())
					resizeWidgetSource();
			}, 500);
		});
	}
	
	function resizePanels(perc) {
		perc = perc < 0 ? 0 : (perc > 100 ? 100 : perc);
		
		var is_reverse = ui.hasClass("reverse");
		var is_fixed_properties = ui.hasClass("fixed-properties");
		var is_fixed_side_properties = ui.hasClass("fixed-side-properties");
		var w = parseInt(resize_panels.width());
		var calc_str = "calc(" + perc + "% + " + w + "px)";
		var perc_str = (100 - perc) + "%";
		var rp_perc_str = null;
		
		if (is_reverse) {
			rp_perc_str = "calc(" + (100 - perc) + "% - " + w + "px)";
			
			if (perc == 0) {
				calc_str = 0;
				perc_str = "100%";
			}
			else if (perc == 100) {
				calc_str = "100%";
				perc_str = w + "px";
				rp_perc_str = 0;
			}
			
			if (!is_fixed_properties)
				menu_settings.css({"left": calc_str});
			
			menu_widgets.css({"left": calc_str});
			menu_layers.css({"left": calc_str});
			template_widgets_options.css({"left": calc_str});
			template_widgets.css({"right": perc_str});
			resize_panels.css({"right": rp_perc_str, "left": ""});
		}
		else {
			rp_perc_str = perc + "%";
			
			if (perc == 0) {
				calc_str = w + "px";
				perc_str = "100%";
				rp_perc_str = 0;
			}
			else if (perc == 100) {
				calc_str = "100%";
				perc_str = w + "px";
				rp_perc_str = "calc(100% - " + w + "px)";
			}
			
			if (!is_fixed_properties)
				menu_settings.css({"right": perc_str});
			
			menu_widgets.css({"right": perc_str});
			menu_layers.css({"right": perc_str});
			template_widgets_options.css({"right": perc_str});
			template_widgets.css({"left": calc_str});
			resize_panels.css({"left": rp_perc_str});
		}
		
		if (is_fixed_side_properties) {
			me.resizeMenuSettingsContextMenuLargeFields();
			me.resizeMenuSettingsPropertiesFields();
		}
		
		//resize template source popup if open
		if (isWidgetSourceShown())
			resizeWidgetSource();
		
		if (typeof me.options.on_panels_resize_func == "function")
			me.options.on_panels_resize_func({
				direction: "horizontal",
				
				perc: perc,
				is_reverse: is_reverse,
				is_fixed_properties: is_fixed_properties,
				is_fixed_side_properties: is_fixed_side_properties,
				resize_panels_width: w,
				calc_str: calc_str,
				perc_str: perc_str,
				rp_perc_str: rp_perc_str,
				
				ui: ui,
				menu_settings: menu_settings,
				menu_widgets: menu_widgets,
				menu_layers: menu_layers,
				template_widgets_options: template_widgets_options,
				template_widgets: template_widgets,
				resize_panels: resize_panels,
			});
	}
	
	me.flipPanelsSide = function() {
		ui.toggleClass("reverse");
		
		menu_widgets.css({"left": "", "right": ""});
		menu_layers.css({"left": "", "right": ""});
		template_widgets_options.css({"left": "", "right": ""});
		template_widgets.css({"left": "", "right": ""});
		resize_panels.css({"left": "", "right": ""});
		menu_settings.css({"left": "", "right": ""});
		
		if (typeof me.options.on_flip_panels_side_func == "function") {
			me.options.on_flip_panels_side_func({
				ui: ui,
				menu_settings: menu_settings,
				menu_widgets: menu_widgets,
				menu_layers: menu_layers,
				template_widgets_options: template_widgets_options,
				template_widgets: template_widgets,
				resize_panels: resize_panels,
			});
		}
	};
	
	/* INIT METHODS - TEMPLATE WIDGETS */
	
	//This is very important because when we move the layout-ui-editor, the inner iframes (like template_widgets_iframe) will get refreshed (default browser behaviour). So we need to reinit this, by setting the same html before the iframe gets reloaded...
	function initTemplateWidgets() {
		template_widgets_iframe.load(function() { //not sure if this is working correctly
			//console.log("template_widgets_iframe load");
			
			try {
				var with_iframe_src = template_widgets_iframe.attr("data-init-src") ? true : false; //get this again, bc it could change dynamically
				var changed = me.resetDroppables(); 
				
				//console.log("changed:"+changed);
				//console.log("with_iframe_src:"+with_iframe_src);
				//console.log(template_widgets_iframe.contents().find("html").html());
				//try {throw new Error("sd");}catch(e){console.log(e);}
				
				//If iframe gets html loaded again, resetDroppables should return true, which means we should execute the following code
				if (with_iframe_src)
					me.initAfterTemplateWidgetsIframeReload();
				else if (changed) {
					//getting iframe previous html
					var pc = template_widgets_iframe.data("previous_contents");
					var head_html = pc ? pc[0] : "";
					var body_html = pc ? pc[1] : "";
					
					//preparing head html
					if (head_html != "")
						template_widgets_iframe.contents().find("head").append(head_html);
					
					//preparing body
					template_widgets_iframe_body = template_widgets_iframe.contents().find("body");
					
					//set body_html to body so it can be used in initAfterTemplateWidgetsIframeReload
					//Do not use template_widgets_iframe_body.html(body_html), bc in some cases it breaks and the body_html may contain some javascript, and the jquery.html() method, removes all the script tags. Use innerHTML instead to keep the original code
					template_widgets_iframe_body[0].innerHTML = body_html; 
					//console.log(template_widgets_iframe_body.html() == body_html); //if we use 'template_widgets_iframe_body.html(body_html)', this is FALSE. If we use 'template_widgets_iframe_body[0].innerHTML = body_html' this is TRUE!
					//console.log(template_widgets_iframe_body.html());
					
					//set template widgets panel
					me.initAfterTemplateWidgetsIframeReload();
				}
			}
			catch(e) { 
				if (console && console.log) 
					console.log(e);
			}
		});
		
		//init template widgets frame
		var with_iframe_src = template_widgets_iframe.attr("data-init-src") ? true : false;
		initTemplateWidgetsIframeContents(with_iframe_src);
		
		//init template widgets droppable
		me.initTemplateWidgetsDroppable(template_widgets_droppable);
	}
	
	function initTemplateWidgetsIframeContents(with_iframe_src) {
		//template_widgets_iframe.contents() is the #document.
		template_widgets_iframe.contents().scroll(function(event) {
			//console.log("scroll");
			
			if (me.selected_template_widget)
				updateWidgetHeaderPosition(me.selected_template_widget);
			
			if (me.selected_template_droppable)
				updateDroppableHeaderPosition(me.selected_template_droppable);
		});
		
		//sometimes the doc scroll doesn't work and we need the window scroll
		template_widgets_iframe[0].contentWindow.addEventListener('scroll', function(event) { //cannot use $(...).scroll(... bc it doesn't work.
			//console.log("scroll");
			
			if (me.selected_template_widget)
				updateWidgetHeaderPosition(me.selected_template_widget);
			
			if (me.selected_template_droppable)
				updateDroppableHeaderPosition(me.selected_template_droppable);
		});
		
		//catch all iframe errors and ignore them
		template_widgets_iframe[0].contentWindow.onerror = function(msg, url, line, col, error) {
			//console.log("template_widgets_iframe[0].contentWindow.onerror");
			
			if (console && console.log)
				console.log("[LayoutUIEditor.js:initTemplateWidgetsIframeContents()] Template Widgets Iframe error:" + "\n- message: " + msg + "\n- line " + line + "\n- column " + col + "\n- url: " + url + "\n- error: " + error);
			
			return true; //avoids launching the javascript error to the browser
		};
		
		//unload needs to be set everytime that the iframe is loaded because the contentWindow reference is lost, so it must be in this function in order to be execute when layout-ui-editor is inited and everytime the iframe is loaded.
		$(template_widgets_iframe[0].contentWindow).unload(function () {
			 //console.log("unload");
			 //try{throw new Exception ("asd");}catch(e){console.log(e);}
			 
			 var head_html = template_widgets_iframe.contents().find("head").html();
			 var body_html = template_widgets_iframe.contents().find("body").html(); //do not use me.getTemplateLayoutBodyHtml() bc we can have multiple template_widgets_droppable items
			 template_widgets_iframe.data("previous_contents", [head_html, body_html]);
		});
		//console.log($._data(template_widgets_iframe[0], 'events'));
		
		//add default css to iframe
		var folder_path = me.getLayoutUIEditorFolderPath();
		var head = template_widgets_iframe.contents().find("head");
		var iframe_style_path = folder_path + "css/iframe_style.css";
		
		//This is very important bc some browsers add some weird javascript codes to the iframe. These codes will break the conversion between the templateLayout and the templateFullSource, if we try to convert these codes too... So we need to add the layout-ui-editor-reserved class to ignore them...
		if (head.children("link[href='" + iframe_style_path + "']").length == 0) {
			var is_empty = template_widgets_droppable.children().length == 0;
			var extra_head = is_empty && !with_iframe_src ? me.getTemplateFullSourceHeadEditorValue() : ""; //only if not with_iframe_src, otherwise we are adding repeated head html
				
			try {
				head[0].insertAdjacentHTML("beforeend", extra_head + '<link class="layout-ui-editor-reserved" rel="stylesheet" type="text/css" href="' + iframe_style_path + '" />');
			}
			catch(e) { //in case there are some javascript erros, which then will break this Editor
				me.showException("Javascript Error: " + (e && e.message ? e.message : e));
				
				if (console && console.log) 
					console.log(e);
			}
		}
		
		//set template widgets panel
		template_widgets_iframe_body.click(function(event) {
			var droppable_elm = $(this); //we can have more than one droppable elm, so we must use "this".
			me.removeNodeClass(droppable_elm.find(".template-widget"), "widget-active");
			me.removeNodeClass(droppable_elm.find(".droppable"), "droppable-active");
			
			closeMenuSettings();
			unsetSelectedTemplateWidget();
		});
	}
	
	me.initAfterTemplateWidgetsIframeReload = function() {
		//console.log("initAfterTemplateWidgetsIframeReload");
		
		var with_iframe_src = template_widgets_iframe.attr("data-init-src") ? true : false;
		
		//reset droppables just in case
		me.resetDroppables();
		
		template_widgets_iframe_head = template_widgets_iframe.contents().find("head");
		template_widgets_iframe_body = template_widgets_iframe.contents().find("body");
		//console.log(template_widgets_iframe[0]);
		//console.log(template_widgets_iframe_body[0]);
		
		var body_html = template_widgets_iframe_body.html(); //save previous html
		//console.log(body_html);
		
		//add menu-widgets javascript and styles to template widgets iframe
		if (with_iframe_src || template_widgets_iframe_head.find(".template-widgets-css-code, .template-widgets-js-code").length == 0)
			prepareTemplateWidgetsIframeWithMenuWidgetsJSAndStyles();
		
		//template_widgets_droppable = template_widgets_iframe_body.children(".droppable");
		template_widgets_droppable = template_widgets_iframe_body.find(".main-droppable");
		
		if (!template_widgets_droppable[0] && template_widgets_iframe_body.is(".main-droppable"))
			template_widgets_droppable = template_widgets_iframe_body;
		
		if (!template_widgets_droppable[0]) {
			template_widgets_droppable = $('<div class="droppable main-droppable"></div>');
			//template_widgets_droppable = $('<div class="droppable main-droppable" data-main-droppable-name="default"></div>');
			template_widgets_iframe_body.html("").append(template_widgets_droppable);
			
			//set previous html to created droppable
			//Do not use template_widgets_droppable.html(body_html), bc in some cases it breaks and the body_html may contain some javascript, and the jquery.html() method, removes all the script tags. Use innerHTML instead to keep the original code
			template_widgets_droppable[0].innerHTML = body_html; 
			//console.log(template_widgets_droppable.html() == body_html); //if we use 'template_widgets_droppable.html(body_html)', this is FALSE. If we use 'template_widgets_droppable[0].innerHTML = body_html' this is TRUE!
			//console.log(template_widgets_iframe_body.html());
		}
		
		selected_template_widgets_droppable = template_widgets_droppable.first();
		
		//init template widgets frame
		initTemplateWidgetsIframeContents(with_iframe_src);
		
		//init template_widgets_droppable
		me.reinitTemplateWidgetsDroppable(template_widgets_droppable);
		
		//preparing template layout inside of droppables
		for (var i = 0; i < template_widgets_droppable.length; i++) {
			var droppable_elm = $(template_widgets_droppable[i]);
			me.parseElementInnerHtml(droppable_elm, {do_update_menu_layers: true}); //Do not use the parseHtmlSource, otherwise it will loose the previous events set to the children .not-widget. This method was created so it could work together with the edit_entity_simple.php
		}
		
		//prepare menu layers
		prepareMenuLayers();
		
		//prepare template widgets with options
		prepareTemplateLayoutAccordingWithOptions();
		
		//call load handler if exists
		if (typeof me.options.on_template_widgets_iframe_reload_func == "function")
			me.options.on_template_widgets_iframe_reload_func();
	};
	
	me.initTemplateWidgetsDroppable = function(template_widgets_droppable_elm) {
		//set click event
		template_widgets_droppable_elm.click(function(event) {
			var droppable_elm = $(this); //we can have more than one droppable elm, so we must use "this".
			me.removeNodeClass(droppable_elm.find(".template-widget"), "widget-active");
			me.removeNodeClass(droppable_elm.find(".droppable"), "droppable-active");
			
			closeMenuSettings();
			unsetSelectedTemplateWidget();
			
			selected_template_widgets_droppable = droppable_elm;
			
			//if droppable is body, then open the properties to edit the css
			if (droppable_elm.is(template_widgets_iframe_body)) {
				//console.log("body click");
				
				//enable menu-settings panel
				enableMenuSettings();
				
				me.selected_template_widget = template_widgets_iframe_body;
				
				//prepare menu-settings panel
				openMenuSettings(template_widgets_iframe_body);
				
				//show menu settings panel
				ui.find(" > .options .show-settings").click();
			}
		});
		
		//set widget over and resize events
		var mouse_move_droppable_elm = null;
		var original_event_when_resize_started = null;
		var is_resizing_in_droppable_started = false;
		var is_mouse_down_on_resizable_widget = false;
		var resizing_tolerance_grid = $.isArray(me.options.moving_tolerance_grid) ? me.options.moving_tolerance_grid : [10, 10];
		
		//set escape key press, to stop resizing. this is very important for the image, video and iframe elements.
		var remove_resize_class_func = function(index, class_name) {
			if (typeof class_name != "string") //for the svg and path elements
				return "widget-resize-n widget-resize-s widget-resize-e widget-resize-w widget-resize-ne widget-resize-nw widget-resize-se widget-resize-sw";
			
			return (class_name.match(/(^|\s)widget-resize-(n|s|e|w|ne|nw|se|sw)(\s|$)/g) || []).join(' ');
		};
		
		var widget_escape_keydown_func = function(event) {
			if (e.which === 27 || e.keyCode === 27) { //escape key
				var widget = $(this);
				
				mouse_up_widgets_func(event, true);
				
				var doc = widget[0].ownerDocument || document;
				$(doc).find(".ui-draggable-dragging").draggable({"revert": true}).trigger("mouseup");
				
				widget[0].removeEventListener("keydown", widget_escape_keydown_func);
				
				if (widget.is("iframe"))
					try { //must be inside of a try and catch bc if the url is from a different domain, we cannot access to the iframe document, and we will get a javascript error.
						widget[0].contentWindow.document.removeEventListener("keydown", widget_escape_keydown_func);
					}
					catch(e) {}
			}
		};
		
		var body_mouse_move_widgets_func = function(event) { //this event is trigger when the mouse gets moved on body
			//console.log("body mousemove");
			if (is_resizing_in_droppable_started && mouse_move_droppable_elm) {
				var original_event = event.originalEvent ? event.originalEvent : event;
				
				me.start_resizing_elm = true; //set this flag to true so the click event in the other widgets don't get triggered when we drag this element.
				resizeMouseEventElement(original_event, mouse_move_droppable_elm, resizing_tolerance_grid);
			}
		};
		
		var mouse_move_widgets_func = function(event) { //this event is trigger when the mouse gets moved
			//console.log("mousemove");
			var original_event = event.originalEvent ? event.originalEvent : event;
			var target = original_event.target;
			var widget = $(target);
			var droppable_elm = $(this); //we can have more than one droppable elm, so we must use "this".
			//console.log("is_mouse_down_on_resizable_widget:"+is_mouse_down_on_resizable_widget);
			//console.log("is_resizing_in_droppable_started:"+is_resizing_in_droppable_started);
			
			if (is_mouse_down_on_resizable_widget && !is_resizing_in_droppable_started) {
				if (widget[0] && me.hasNodeClass(widget, "template-widget") && widget.data("resizable")) {
					//only allow resizing after user moves the mouse more than tolerance_x or tolerance_y pixels, bc he may want to simply trigger the click event instead and move a little bit the mouse. So we only activate the resizing after moving the mouse tolerance_x or tolerance_y pixels.
					var direction = droppable_elm.data("resizing-direction");
					var y_directions = (!me.options.rtl && (direction == "s" || direction == "se")) || 
									(me.options.rtl && (direction == "s" || direction == "sw"));
					var x_directions = (!me.options.rtl && (direction == "e" || direction == "se")) ||
									(me.options.rtl && (direction == "w" || direction == "sw"));
					var can_start_resize = false;
					
					if (y_directions)
						can_start_resize = true;
					
					if (x_directions)
						can_start_resize = true;
					
					if (can_start_resize) {
						is_resizing_in_droppable_started = true;
						
						//save 
						droppable_elm.data("resizing-elm-original-width", widget[0].style.width);
						droppable_elm.data("resizing-elm-original-height", widget[0].style.height);
						
						//set escape key press, to stop resizing. this is very important for the image, video and iframe elements.
						widget[0].removeEventListener("keydown", widget_escape_keydown_func); //remove previous keydown event if exists, just in case...
						widget[0].addEventListener("keydown", widget_escape_keydown_func);
						
						if (widget.is("iframe")) {
							try { //must be inside of a try and catch bc if the url is from a different domain, we cannot access to the iframe document, and we will get a javascript error.
								widget[0].contentWindow.document.removeEventListener("keydown", widget_escape_keydown_func); //remove previous keydown event if exists, just in case...
								widget[0].contentWindow.document.addEventListener("keydown", widget_escape_keydown_func);
							}
							catch(e) {}
						}
					}
				}
			}
			else if (is_resizing_in_droppable_started) {
				me.start_resizing_elm = true; //set this flag to true so the click event in the other widgets don't get triggered when we drag this element.
				
				original_event.stopPropagation(); //so it doesn't call the body_mouse_move_widgets_func
				
				resizeMouseEventElement(original_event, droppable_elm, resizing_tolerance_grid);
			}
			else { //when mouse is moving and was not yet clicked
				//remove cursor from previous droppable if apply
				if (mouse_move_droppable_elm && mouse_move_droppable_elm[0] && mouse_move_droppable_elm.data("cursor-elm")) {
					me.removeNodeClass(mouse_move_droppable_elm, remove_resize_class_func);
					me.removeNodeClass($( mouse_move_droppable_elm.data("cursor-elm") ), remove_resize_class_func);
				}
				
				//remove cursor from new droppable and update movable droppable if different
				if (!droppable_elm.is(mouse_move_droppable_elm)) {
					if (droppable_elm.data("cursor-elm")) {
						me.removeNodeClass(droppable_elm, remove_resize_class_func);
						me.removeNodeClass($( droppable_elm.data("cursor-elm") ), remove_resize_class_func);
					}
					
					//update movable droppable
					mouse_move_droppable_elm = droppable_elm;
				}
				
				//prepare resize cursor
				if (widget[0] && me.hasNodeClass(widget, "template-widget") && widget.data("resizable")) {
					//console.log("mousemove");
					
					var direction = getMouseDirectionResizeType(event, widget[0]);
					
					if (direction) {
						var y_directions = (!me.options.rtl && (direction == "s" || direction == "se")) || 
										(me.options.rtl && (direction == "s" || direction == "sw"));
						var x_directions = (!me.options.rtl && (direction == "e" || direction == "se")) ||
										(me.options.rtl && (direction == "w" || direction == "sw"));
						
						if (y_directions || x_directions) {
							var class_name = "widget-resize-" + direction;
							
							droppable_elm.data("cursor-elm", widget[0]);
							me.addNodeClass(droppable_elm, class_name);
							me.addNodeClass(widget, class_name);
						}
					}
				}
			}
		};
		
		var mouse_down_widgets_func = function(event) {
			//console.log("mousedown");
			//event.preventDefault(); //very important to be commented otherwise the contenteditable=true won't work!
			var original_event = event.originalEvent ? event.originalEvent : event;
			var target = original_event.target;
			var widget = $(target);
			var droppable_elm = $(this); //we can have more than one droppable elm, so we must use "this".
			
			selected_template_widgets_droppable = droppable_elm;
			mouse_move_droppable_elm = droppable_elm;
			
			//trigger over event but only if resize did not started yet
			if (me.hasNodeClass(widget, "template-widget") || me.hasNodeClass(widget, "droppable"))
				widget.trigger("mouseenter");
			
			//prepare resizing
			if (me.hasNodeClass(widget, "template-widget") && widget.data("resizable")) {
				var direction = getMouseDirectionResizeType(original_event, widget[0]);
				//console.log("direction:"+direction);
				
				if (direction) {
					//save original resizing event
					original_event_when_resize_started = original_event;
					
					//set mouse down flag to true
					is_mouse_down_on_resizable_widget = true;
					
					//save widget to possible resize
					droppable_elm.data("resizing-elm", widget[0]);
					droppable_elm.data("resizing-direction", direction);
					
					/* Do not add width and height, bc the user may click by mistake and not move the cursor, which is not a move action. And in this case, we should not set the width neither the height. The width and height will be set automatically when the user drags the cursor and the mouse_move event gets call via the resizeMouseEventElement method.
					var bs = widget.css("box-sizing");
					
					if (!widget[0].style.width) //must be with .style.
						widget[0].style.width = (bs == "border-box" ? widget.outerWidth() : widget.width()) + "px";
					
					if (!widget[0].style.height) //must be with .style.
						widget[0].style.height = (bs == "border-box" ? widget.outerHeight() : widget.height()) + "px";
					*/
				}
			}
		};
		
		var mouse_up_widgets_func = function(event, escape_key_down) {
			//console.log("mouseup");
			var original_event = event.originalEvent ? event.originalEvent : event;
			var droppable_elm = mouse_move_droppable_elm && mouse_move_droppable_elm[0] ? mouse_move_droppable_elm : $(this); //we can have more than one droppable elm, so we must use "this".
			var widget = $( droppable_elm.data("resizing-elm") );
			
			//reset vars set before by the mouse_down_widgets_func
			is_mouse_down_on_resizable_widget = false;
			original_event_when_resize_started = null;
			
			droppable_elm.data("resizing-elm", null);
			droppable_elm.data("resizing-direction", null);
			droppable_elm.data("resizing-mouse-x", null);
			droppable_elm.data("resizing-mouse-y", null);
			me.removeNodeClass(droppable_elm, remove_resize_class_func);
			
			//prepare widget
			if (is_resizing_in_droppable_started) {
				if (widget[0]) {
					//remove cursor
					me.removeNodeClass(widget, remove_resize_class_func);
					
					//set original dimensions if escape key was pressed
					if (escape_key_down) {
						widget[0].style.width = droppable_elm.data("resizing-elm-original-width");
						widget[0].style.height = droppable_elm.data("resizing-elm-original-height");
					}
					
					droppable_elm.data("resizing-elm-original-width", null);
					droppable_elm.data("resizing-elm-original-height", null);
					
					//set click handler so the widget doesn't get clicked twice, when the user by mistake clicks in the boundaries of the widget.
					widget.data("click_event_trigered", null);
					
					var click_event_control_func = function() {
						$(this).data("click_event_trigered", 1);
					};
					widget.bind("click", click_event_control_func); 
					
					setTimeout(function() {
						//remove cursor again bc sometimes the cursor is still here
						me.removeNodeClass(widget, remove_resize_class_func);
						
						if (widget.data("click_event_trigered") != 1) {
							me.start_resizing_elm = false; //reset this flag so we can click again in the widgets
							widget.trigger("click"); //select element, but only if click was not triggered yet when mouseup event runs
						}
						widget.unbind("click", click_event_control_func);
						
						//reset me.start_resizing_elm flag so we can click again in the widgets
						me.start_resizing_elm = false; 
						
						//only remove the is_resizing_in_droppable_started here, so it doesn't trigger anything in the mouse move
						is_resizing_in_droppable_started = false;
						
						//TODO: if menu settings is open, then reload its settings so we can get the new widths
					}, 100);
				}
				else
					setTimeout(function() {
						//reset me.start_resizing_elm flag so we can click again in the widgets
						me.start_resizing_elm = false; 
						
						//only remove the is_resizing_in_droppable_started here, so it doesn't trigger anything in the mouse move
						is_resizing_in_droppable_started = false;
					}, 100);
			}
		};
		
		template_widgets_droppable_elm.mousemove(mouse_move_widgets_func); //before, during and after resizing. Everytime the mouse moves
		template_widgets_droppable_elm.mousedown(mouse_down_widgets_func); //start resizing
		template_widgets_droppable_elm.mouseup(mouse_up_widgets_func); //end resizing
		
		//When the widget is resizing and the mouse exits the droppable, it should still continue resizing the widget, so we need to add the event on body if it is not a droppable.
		var doc = template_widgets_droppable_elm[0].ownerDocument || template_widgets_droppable_elm[0].document;
		var body = $(doc).find("body");
		
		if (!body.is(template_widgets_droppable_elm) && body.data("with_body_mouse_move_widgets_event") != 1) {
			body.data("with_body_mouse_move_widgets_event", 1);
			body.mousemove(body_mouse_move_widgets_func);
		}
		
		//adding del and backspace key handler, to update menu layer
		template_widgets_droppable_elm.keydown(function(e) {
			var droppable_elm = $(this); //we can have more than one droppable elm, so we must use "this".
			var contains_char = me.TextSelection ? me.TextSelection.isPressedKeyCharPrintable(e) : false; //checks if pressed key contains any char
			
			//set escape key press, to stop resizing or dragging. this is very important for the image, video and iframe elements.
			if (e.which === 27 || e.keyCode === 27) { //escape key
				if (is_resizing_in_droppable_started)
					mouse_up_widgets_func(e, true); //set escape key press, to stop resizing
				else
					droppable_elm.find(".ui-draggable-dragging").draggable({"revert": true}).trigger("mouseup");
			}
			else if (e.keyCode === 46 || e.keyCode === 8) { //del or backspace key
				contains_char = true; //set contains char to true so it can execute the handler: on_template_widgets_layout_changed_func
				
				if (me.TextSelection && me.TextSelection.selection_opts) {
					var opts = me.TextSelection.selection_opts;
					var first_widget = $(opts.first_node).parent().closest(".template-widget");
					var last_widget = $(opts.last_node).parent().closest(".template-widget");
					
					//disable erase widget node through backspace and delete keys
					if (opts.is_same_node && opts.first_node.textContent == "" && me.TextSelection.getSelection().toString() == "") {
						var is_erase_widget_node = !first_widget[0] && !last_widget[0];
						
						if (!is_erase_widget_node && first_widget.is(last_widget))
							is_erase_widget_node = true;
						
						if (is_erase_widget_node) {
							var node = first_widget[0] ? $(opts.first_node).closest(".template-widget") : null;
							node = node && node[0] ? node : $(opts.common_parent);
							
							if (node[0] && node.text() != "")
								is_erase_widget_node = false;
							
							if (is_erase_widget_node) {
								//e.stopPropagation();
								//e.preventDefault();
								
								if (node[0]) {
									try {
										me.TextSelection.placeCaretAtEndOfElement(node[0]);
									}
									catch(ex) {
										//do nothing
									}
								}
								
								return false;
							}
						}
					}
					
					//just in case, it deletes, reload menu settings and menu layer
					if (first_widget[0]) {
						setTimeout(function() {
							me.reloadMenuSettingsIfOpened(first_widget);
							me.refreshElementMenuLayer(first_widget);
						}, 300);
					}
					
					if (last_widget[0] && !last_widget.is(first_widget)) {
						setTimeout(function() {
							me.reloadMenuSettingsIfOpened(first_widget);
							me.refreshElementMenuLayer(last_widget);
						}, 300);
					}
				}
			}
			
			if (contains_char && typeof me.options.on_template_widgets_layout_changed_func == "function")
				me.options.on_template_widgets_layout_changed_func(e.target);
		});
		
		template_widgets_droppable_elm.blur(function(e) {
			if (typeof me.options.on_template_widgets_layout_changed_func == "function")
				me.options.on_template_widgets_layout_changed_func(e.target);
		});
	};
	
	me.reinitTemplateWidgetsDroppable = function(template_widgets_droppable_elm) {
		//init template widgets droppable
		me.initTemplateWidgetsDroppable(template_widgets_droppable_elm);
		
		//reinit text selection
		if (me.TextSelection)
			me.TextSelection.setEditorElm(template_widgets_droppable_elm);
		
		//refresh menu layers so it can re add the new droppable elms
		prepareMenuLayers();
	};
	
	me.addTemplateWidgetsDroppable = function(template_widgets_droppable_elm) {
		template_widgets_droppable_elm = template_widgets_droppable_elm instanceof jQuery ? template_widgets_droppable_elm : $(template_widgets_droppable_elm);
		template_widgets_droppable.push(template_widgets_droppable_elm[0]);
		
		me.reinitTemplateWidgetsDroppable(template_widgets_droppable_elm);
	};
	
	function resizeMouseEventElement(event, droppable_elm, tolerance_grid) {
		var elm = droppable_elm.data("resizing-elm");
		
		if (elm) {
			var direction = droppable_elm.data("resizing-direction");
			
			if (direction) {
				var old_mouse_x = droppable_elm.data("resizing-mouse-x");
				var old_mouse_y = droppable_elm.data("resizing-mouse-y");
				var new_mouse_x = event.clientX;
				var new_mouse_y = event.clientY;
				var tolerance_x = $.isArray(tolerance_grid) && $.isNumeric(tolerance_grid[0]) ? tolerance_grid[0] : 0;
				var tolerance_y = $.isArray(tolerance_grid) && $.isNumeric(tolerance_grid[1]) ? tolerance_grid[1] : 0;
				var mouse_x_moved_significantly = !$.isNumeric(old_mouse_x) || Math.abs(parseInt(old_mouse_x) - parseInt(new_mouse_x)) > parseInt(tolerance_x);
				var mouse_y_moved_significantly = !$.isNumeric(old_mouse_y) || Math.abs(parseInt(old_mouse_y) - parseInt(new_mouse_y)) > parseInt(tolerance_y);
				
				if (!mouse_x_moved_significantly)
					new_mouse_x = old_mouse_x;
				
				if (!mouse_y_moved_significantly)
					new_mouse_y = old_mouse_y;
				
				if (new_mouse_x != old_mouse_x || new_mouse_y != old_mouse_y) {
					var rect = elm.getBoundingClientRect();
					var resized = false;
					
					if (direction.match(/[ns]*[ew]+/)) {
						var w = direction.match(/[ns]*e/) ? new_mouse_x - rect.left : rect.right - new_mouse_x;
						
						if (w > 0) {
							elm.style.width = parseInt(w) + "px";
							resized = true;
						}
					}
					
					if (direction.match(/[ns]+[ew]*/)) {
						var h = direction.match(/s[ew]*/) ? new_mouse_y - rect.top : rect.bottom - new_mouse_y;
						
						if (h > 0) {
							elm.style.height = parseInt(h) + "px";
							resized = true;
						}
					}
					
					if (resized) {
						droppable_elm.data("resizing-mouse-x", new_mouse_x);
						droppable_elm.data("resizing-mouse-y", new_mouse_y);
					}
				}
			}
		}
	}
	
	function getMouseDirectionResizeType(event, elm) {
		var rect = elm.getBoundingClientRect();
		var gap = 20;
		//console.log(event);
		//console.log(rect);
		
		var is_top = event.clientY >= rect.top && event.clientY <= rect.top + gap;
		var is_bottom = event.clientY <= rect.bottom && event.clientY >= rect.bottom - gap;
		var is_left = event.clientX >= rect.left && event.clientX <= rect.left + gap;
		var is_right = event.clientX <= rect.right && event.clientX >= rect.right - gap;
		var direction = "";
		
		if (is_top || is_bottom || is_left || is_right) {
			if (is_top && !is_left && !is_right)
				direction = "n";
			else if (is_bottom && !is_left && !is_right)
				direction = "s";
			else if (!is_top && !is_bottom && is_left) 
				direction = "w";
			else if (!is_top && !is_bottom && is_right)
				direction = "e";
			else if (is_top && is_left)
				direction = "nw";
			else if (is_top && is_right)
				direction = "ne";
			else if (is_bottom && is_left)
				direction = "sw";
			else if (is_bottom && is_right)
				direction = "se";
		}
		
		return direction;
	}
	
	function initLayoutUIEditorFormField() {
		me.LayoutUIEditorFormField.init();
	}
	
	function initLayoutUIEditorWidgetResource() {
		me.LayoutUIEditorWidgetResource.init();
	}
	
	function initTextSelection() {
		if (me.TextSelection) {
			me.TextSelection.options.sticky_menu = true;
			
			//This handler will be called everytime that the paste icon in the TextSelection menu gets clicked
			me.TextSelection.options.on_paste_widget = function() {
				var node = me.TextSelection.selection_opts ? me.TextSelection.selection_opts.first_node : null;
				
				do {
					if (node) {
						if (node.nodeType == Node.ELEMENT_NODE && (node.classList.contains("template-widget") || node.classList.contains("droppable")))
							break;
						else if (me.selected_template_widget && me.selected_template_widget.is(node))
							break;
						else if (me.selected_template_droppable && me.selected_template_droppable.is(node))
							break;
						else
							node = node.parentNode;
					}
				}
				while (node);
				
				if (node) 
					node = $(node);
				else {
					if (me.selected_template_widget)
						node = me.selected_template_widget;
					else if (me.selected_template_droppable)
						node = me.selected_template_droppable;
				}
				
				pasteCopiedWidget(node);
		  		
		  		if (typeof me.options.on_template_widgets_layout_changed_func == "function")
					me.options.on_template_widgets_layout_changed_func(node);
			};
			
			//This handler will be called everytime that the TextSelection menu gets open
			//prepare text selection menu positioning and size
			me.TextSelection.options.on_before_show_menu = function(e) {
				if (!me.TextSelection.options.sticky_menu) {
					//Only applies sticky_menu is false if we need to set the top and left in here, bc in the template_widgets on has the correct offsets when is shown and if is resized we need to know what are the correct offsets.
					var io = template_widgets.offset();
					me.TextSelection.options.top = io.top - $(template_widgets[0].ownerDocument).scrollTop();
					me.TextSelection.options.left = io.left - $(template_widgets[0].ownerDocument).scrollLeft();
				}
				else //updates the absolute boundary of the text-seletion
					me.TextSelection.setMenuWidth( template_widgets.width() );
			};
			
			me.TextSelection.options.on_after_show_menu = function(e) {
				refreshTemplateWidgetsDimensions();
			};
			
			//This handler will be called everytime that the TextSelection menu gets hide
			//hide headers and prepare iframe with the right height
			me.TextSelection.options.on_after_hide_menu = function() {
				if (me.TextSelection.options.sticky_menu) {
					//hide widget header
					hideWidgetHeader();
					
					//hide droppable header
					hideDroppableHeader();
				}
				
				refreshTemplateWidgetsDimensions();
			};
			
			//reload widget properties and update widget menu layer
			me.TextSelection.options.on_after_click_menu_item = function(e, menu_item_elm, menu_item_props) {
				if (me.TextSelection.selection_opts)
					setTimeout(function() {
						var opts = me.TextSelection.selection_opts;
						var parent_widget = $(opts.common_parent).closest(".template-widget");
						
						if (parent_widget[0]) {
							me.reloadMenuSettingsIfOpened(parent_widget);
							me.refreshElementMenuLayer(parent_widget);
							
							if (typeof me.options.on_template_widgets_layout_changed_func == "function")
								me.options.on_template_widgets_layout_changed_func(parent_widget);
						}
					}, 300);
				
				//hide widget header
				hideWidgetHeader();
				
				//hide droppable header
				hideDroppableHeader();
				
				//prepare menu-item-popup css
				me.TextSelection.menu_elm.children(".menu-item-popup").css("max-height", template_widgets_iframe.height() + "px");
			};
			
			//remove reserved classes before parsing attributes
			me.TextSelection.options.on_parse_node_attributes = function(e, menu_item_elm, node, node_attributes, opts) {
				var widget = $(node);
				
				if (me.hasNodeClass(widget, "template-widget")) {
					var reserved_classes = me.getTemplateWidgetCurrentReservedClasses(widget);
					widget.data("reserved_classes", reserved_classes);
					
					node_attributes["class"] = me.getTemplateWidgetCurrentClassesWithoutReservedClasses(widget);
					
					if (typeof me.options.on_template_widgets_layout_changed_func == "function")
						me.options.on_template_widgets_layout_changed_func(widget);
				}
			};
			
			//add reserved classes after parsing attributes
			me.TextSelection.options.on_create_node_attributes = function(e, menu_item_elm, node, node_attributes) {
				var widget = $(node);
				
				if (me.hasNodeClass(widget, "template-widget") && widget.data("reserved_classes")) {
					var reserved_classes = widget.data("reserved_classes");
					node_attributes["class"] = (reserved_classes ? reserved_classes.join(" ") + " " : "") + (node_attributes["class"] ? node_attributes["class"] : "");
					
					if (typeof me.options.on_template_widgets_layout_changed_func == "function")
						me.options.on_template_widgets_layout_changed_func(widget);
				}
			};
			
			//if not yet a widget convert node to widget
			me.TextSelection.options.on_create_node = function(e, menu_item_elm, node, opts) {
				if (node) {
					node = $(node);
					var is_main_droppable = template_widgets_droppable.is(node);
					
					//when paste a text, the browser copies the text inside of a span, pasting the span. When doing this inside of another pasted span previously, the last span comes without the parentNode. So we need to init the node with the parent node, otherwise we will get an exception. This is very weird but it happens so we must to do this.
					if (node[0] && !node[0].parentNode && opts && opts.common_parents && opts.common_parents.length >= 2)
						node = $(opts.common_parents[1]);
					
					if (!me.hasNodeClass(node, "template-widget") && !node.data("data-template-id") && !is_main_droppable) {
						me.convertHtmlElementToWidget(node);
						me.refreshElementMenuLayer(node);
					}
					else { //the nodeName of node maybe was changed through the format-block button.
						if (opts && opts["changed_node_name"] && node.data("data-label").toUpperCase() == opts["old_node_name"].toUpperCase()) {
							var label = node[0].nodeName.toLowerCase().replace(/\b[a-z]/g, function(letter) {
								return letter.toUpperCase();
							});
							
							node.data("data-label", label);
							prepareMenuLayers();
						}
						else if (opts && opts["paste_action"]) {
							//This means that a widget was copied, so we need to recreated it.
							if (!is_main_droppable && (me.hasNodeClass(node, "template-widget") || node.data("data-template-id")))
								me.recreateWidget(node); //remove classes, ids, events from node
							
							me.recreateWidgetChildren(node); //remove classes, ids, events from inner children
						}
						else
							me.refreshElementMenuLayer(node);
					}
					
					if (typeof me.options.on_template_widgets_layout_changed_func == "function")
						me.options.on_template_widgets_layout_changed_func(node);
				}
			};
			
			//add new menu item to add widgets
			var menu_item_add_widgets = {
				class: "add-widget", 
				icon: "zmdi zmdi-hc-lg zmdi-flower-alt", 
				"button-behaviour": 1, 
				"command-show-ui": true,
				title: "Add widget",
				click: function(e, menu_item_elm) {
					//prepare popup html
					var popup_elm = me.TextSelection.menu_elm.children(".menu-item-popup");
					
					if (popup_elm[0])
						popup_elm.remove();
					
					popup_elm = $('<div class="menu-item-popup add-widget-props">'
							+ '	<select><option value="" disabled selected>Select Widget to add</option></select>'
							+ '	<button><i class="zmdi zmdi-hc-lg zmdi-plus"></i> Add Widget</button>'
							+ '	<i class="zmdi zmdi-hc-lg zmdi-close close"></i>'
							+ '</div>');
					
					popup_elm.hide();
					me.TextSelection.menu_elm.append(popup_elm);
					
					popup_elm.find(".close").on("click", function() {
						popup_elm.hide("slow");
					});
					
					var select = popup_elm.children("select");
					
					menu_widgets.find(".group").each(function(idx, group) {
						group = $(group);
						var title = group.children(".group-title").clone();
						title.children("i").remove();
						title = title.text();
						
						var html = '<optgroup label="' + title + '">';
						
						$.each(group.find(".menu-widget"), function(idx, menu_widget) {
							menu_widget = $(menu_widget);
							var menu_widget_title = menu_widget.children("span").first().text();
							menu_widget_title = menu_widget_title ? menu_widget_title : menu_widget.attr("data-tag");
							html += '<option value="' + menu_widget.attr("data-tag") + '">' + menu_widget_title + '</option>';
						});
						
						html += '</optgroup>';
						
						select.append(html);
					});
					
					popup_elm.children("button").on("click", function(e2) {
						var widget_tag = select.val();
						
						if (widget_tag && me.TextSelection.selection_opts && me.TextSelection.selection_opts.first_node) {
							var opts = me.TextSelection.selection_opts;
							var node = opts.first_node;
							var parent_node = node.parentNode;
							
							var widget = me.createTemplateWidgetFromMenuWidgetTag(widget_tag, selected_template_widgets_droppable, null);
							
							widget.insertAfter(node);
							
							if (node.nodeType == Node.TEXT_NODE) {
								//insert widget in between the text, where the cursor is.
								var offset = $.isNumeric(opts.first_offset) ? opts.first_offset : 0;
								var text = node.nodeValue;
								var first_text = text.substr(0, offset);
								var last_text = text.substr(offset);
								
								node.nodeValue = first_text;
								
								if (last_text != "") {
									var last_text_node = $(document.createTextNode(last_text));
									last_text_node.insertAfter(widget);
								}
								
								parent_node = node.parentNode ? node.parentNode : opts.common_parent;
							}
							
							if (parent_node) {
								//update widget layers
								me.refreshElementMenuLayer( $(parent_node) );
								
								popup_elm.hide("slow");
							}
							
							if (typeof me.options.on_template_widgets_layout_changed_func == "function")
								me.options.on_template_widgets_layout_changed_func(widget);
						}
					});
					
					//show popup
					popup_elm.show("slow");
				},
			};
			me.TextSelection.addMenuItemProps(menu_item_add_widgets, "insert-html");
			
			//init TextSelection
			me.TextSelection.init(template_widgets, template_widgets_droppable);
			
			//prepend to template_widgets to be on top
			template_widgets.prepend(me.TextSelection.menu_elm);
		}
	}
	
	//when we move the layout-ui-editor html element to another parent, the inner iframes (like template_widgets_iframe) will get refreshed (browser default behaviour and we cannot change it). 
	//This refresh action will loose all the droppable elements, but the elements from the previous iframe's html will continue in the $.ui.ddmanager.droppables, which means, that when we try to drag something in jquery, the jquery will break because is trying to access elements that don't exist anymore. So we need to check the existent droppables and remove the old ones.
	me.resetDroppables = function() {
		return resetJqueryUIDDManagerDroppables(); //this inside of the ../vendor/jqueryuidroppableiframe/js/jquery-ui-droppable-iframe-fix.js file. This file must be loaded before.
	};
	
	/* INIT METHODS - TEMPLATE PREVIEW */
	
	//This is very important because when we move the layout-ui-editor, the inner iframes (like template_widgets_iframe) will get refreshed (default browser behaviour). So we need to reinit this, by setting the same html before the iframe gets reloaded...
	function initTemplatePreview() {
		var iframe = template_preview.children("iframe");
		
		//unload needs to be set everytime that the iframe is loaded because the contentWindow reference is lost, so it must be in this function in order to be execute when layout-ui-editor is inited and everytime the iframe is loaded.
		var unload_func = function () {
			 //console.log("unload");
			 var head_html = iframe.contents().find("head").html();
			 var body_html = iframe.contents().find("body").html();
			 iframe.data("previous_contents", [head_html, body_html]);
		};
		
		iframe.load(function() {
			//console.log("load");
			
			//getting iframe previous html
			var pc = iframe.data("previous_contents");
			var head_html = pc ? pc[0] : "";
			var body_html = pc ? pc[1] : "";
			
			//preparing head html
			if (head_html != "")
				iframe.contents().find("head").append(head_html);
			
			//preparing body html
			if (body_html != "")
				iframe.contents().find("body").append(body_html);
			
			$(iframe[0].contentWindow).unload(unload_func);
		});
		
		$(iframe[0].contentWindow).unload(unload_func);
	}
	
	/* GET METHODS */
	
	me.getUI = function() {
		return ui;
	};
	
	me.getMenuWidgets = function() {
		return menu_widgets;
	};
	
	me.getMenuWidgetObjByTag = function(widget_tag) {
		return me.menu_widgets_objs[widget_tag];
	};
	
	me.getMenuLayers = function() {
		return menu_layers;
	};
	
	me.getMenuSettings = function() {
		return menu_settings;
	};
	
	me.getTemplateSource = function() {
		return template_source;
	};
	
	me.getTemplateFullSource = function() {
		return template_full_source;
	};
	
	me.getTemplatePreview = function() {
		return template_preview;
	};
	
	me.getTemplateWidgets = function() {
		return template_widgets;
	};
	
	me.getTemplateWidgetsIframe = function() {
		return template_widgets_iframe;
	};
	
	me.getTemplateWidgetsIframeBody = function() {
		return template_widgets_iframe_body;
	};
	
	me.getTemplateWidgetsDroppable = function() {
		return template_widgets_droppable;
	};
	
	me.getSelectedTemplateWidgetsDroppable = function() {
		return selected_template_widgets_droppable;
	};
	
	me.getTemplateWidgetsOptions = function() {
		return template_widgets_options;
	};
	
	me.getMessages = function() {
		return messages;
	};
	
	me.getWidgetHeader = function() {
		return widget_header;
	};
	
	me.getDroppableHeader = function() {
		return droppable_header;
	};
	
	/* ADD DEFAULT WIDGETS METHODS */
	
	function addDefaultMenuWidgets() {
		var default_menu_widget_tag = $(me.default_menu_widget_tag_html.replace(/#LayoutUIEditor#/g, me.obj_var_name));
		var default_menu_widget_text = $(me.default_menu_widget_text_html.replace(/#LayoutUIEditor#/g, me.obj_var_name));
		var default_menu_widget_code = $(me.default_menu_widget_code_html.replace(/#LayoutUIEditor#/g, me.obj_var_name));
		var default_menu_widget_comment = $(me.default_menu_widget_comment_html.replace(/#LayoutUIEditor#/g, me.obj_var_name));
		
		menu_widgets.find(".menu-widget-iframe").after(default_menu_widget_comment).after(default_menu_widget_text).after(default_menu_widget_code).after(default_menu_widget_tag);
		
		if (!menu_widgets.find(".menu-widget-iframe")[0])
			menu_widgets
				.append(default_menu_widget_tag)
				.append(default_menu_widget_text)
				.append(default_menu_widget_code)
				.append(default_menu_widget_comment);
	}
	
	me.onCloneMenuHtmlTagWidget = function(widget, html_element, options) {
		if (html_element) {
			var has_label = $(html_element).data("data-label");
			var widget = me.getNewTemplateWidgetBasedInHtmlElement(widget, html_element, null);
			
			if (!has_label) {
				var label = widget[0].nodeName.toLowerCase();
				label = label.charAt(0).toUpperCase() + label.slice(1);
				me.updateTemplateWidgetLabel(widget, label);
			}
		}
		else
			widget.data("data-label", widget.attr("data-label")).removeAttr("data-label");
		
		return widget;
	};
	
	me.onCleanTemplateHtmlTagWidget = function(html_element) {
		var html = me.getCleanedHtmlElement(html_element);
		var html_element_tag = MyHtmlBeautify.getTagHtml(html, 0, "");
		
		html = html.replace(html_element_tag[0], ""); //remove the tag, so it can be replaced by the right tag.
		
		var clone = me.cloneHtmlElementBeforeCleanIt(html_element);
		clone[0].innerHTML = '';
		
		//parse attributes to convert url to vars: $project_url_prefix or $project_common_url_prefix
		if ($.isArray(me.default_attributes_name_to_convert_to_or_from_php_vars)) {
			var attributes_to_search = me.default_attributes_name_to_convert_to_or_from_php_vars;
			
			for (var i = 0, t = attributes_to_search.length; i < t; i++) {
				var attr_name = attributes_to_search[i];
				var attr_value = clone.attr(attr_name);
				
				if (typeof attr_value == "string" && attr_value && typeof me.options.on_convert_project_url_real_values_to_php_vars_func == "function") {
					attr_value = me.options.on_convert_project_url_real_values_to_php_vars_func(attr_value, html_element);
					clone.attr(attr_name, attr_value);
				}
			}
		}
		
		var clone_html = me.getCleanedHtmlElement(clone[0]);
		var clone_tag = MyHtmlBeautify.getTagHtml(clone_html, 0, "");
		html = clone_tag[0] + html;
		
		clone.remove();
		
		return html;
	};
	
	me.onCreateTemplateHtmlTagWidget = function(widget, html_element, options) {
		if (!html_element)
			widget.append("&nbsp;");
		else if ($.isArray(me.default_attributes_name_to_convert_to_or_from_php_vars)) {
			//parse attributes in case it contains the $project_url_prefix or $project_common_url_prefix
			var attributes_to_search = me.default_attributes_name_to_convert_to_or_from_php_vars;
		
			for (var i = 0, t = attributes_to_search.length; i < t; i++) {
				var attr_name = attributes_to_search[i];
				var attr_value = widget.attr(attr_name);
				
				if (typeof attr_value == "string" && attr_value && typeof me.options.on_convert_project_url_php_vars_to_real_values_func == "function") {
					attr_value = me.options.on_convert_project_url_php_vars_to_real_values_func(attr_value, widget);
					widget.attr(attr_name, attr_value);
				}
			}
		}
	};
	
	me.saveHtmlTagWidgetSettingsField = function(field, widget, status) {
		if (status) {
			field = $(field);
			var field_value = field.val();
			var sprop = field.parent();
			
			if (sprop.hasClass("html-tag")) {
				var node_name = field_value.replace(/\s+/g, "");
				
				if (node_name != "") {
					node_name = node_name.toLowerCase();
					
					if (node_name != widget[0].nodeName.toLowerCase()) {
						me.changeWidgetNodeName(widget, node_name);
					}
				}
				else {
					me.showError("Html tag cannot be empty!");
					status = false;
				}
			}
			else if (typeof me.options.on_convert_project_url_php_vars_to_real_values_func == "function" && $.isArray(me.default_attributes_name_to_convert_to_or_from_php_vars)) {
				//parse attributes in case it contains the $project_url_prefix or $project_common_url_prefix
				var attributes_to_search = me.default_attributes_name_to_convert_to_or_from_php_vars;
				
				for (var i = 0, t = attributes_to_search.length; i < t; i++) {
					var attr_name = attributes_to_search[i];
					var attr_value = widget.attr(attr_name);
					
					if (attr_value) {
						attr_value = me.options.on_convert_project_url_php_vars_to_real_values_func(attr_value, widget);
						widget.attr(attr_name, attr_value);
					}
				}
			}
		}
		
		return status;
	};
	
	me.parseHtmlTagWidgetSettings = function(widget, widget_settings) {
		widget_settings["html-tag"] = widget[0].nodeName.toLowerCase();
	};
	
	me.filterHtmlTagWidgetSettings = function(widget, widget_settings) {
		//parse attributes to convert url to vars: $project_url_prefix or $project_common_url_prefix
		if (widget_settings["attributes"] && $.isArray(me.default_attributes_name_to_convert_to_or_from_php_vars)) {
			var attributes_to_search = me.default_attributes_name_to_convert_to_or_from_php_vars;
			var regex = new RegExp("(^|\\s|\"|')(" + attributes_to_search.join("|") + ")\\s*=", "i");
			
			//check if any of the attributes exist
			if (widget_settings["attributes"].match(regex)) {
				var attributes = MyHtmlBeautify.getAttributes(widget_settings["attributes"]);
				var changed = false;
				
				for (var i = 0, t = attributes.length; i < t; i++) {
					var attribute = attributes[i];
					var attr_name = attribute.name;
					
					if ($.inArray(attr_name.toLowerCase(), attributes_to_search) != 1) {
						var attr_value = attribute.value;
						
						if (typeof attr_value == "string" && attr_value && typeof me.options.on_convert_project_url_real_values_to_php_vars_func == "function") {
							attr_value = me.options.on_convert_project_url_real_values_to_php_vars_func(attr_value, widget);
							
							if (attr_value != attribute.value) {
								attributes[i].value = attr_value;
								changed = true;
							}
						}
					}
				}
				
				if (changed) {
					var attributes_string = "";
					
					for (var i = 0, t = attributes.length; i < t; i++) {
						var attribute = attributes[i];
						attributes_string += (attributes_string.length > 0 ? " " : "") + attribute.name + (attribute.value != "" ? '="' + attribute.value + '"' : "");
					}
					
					//console.log(widget_settings["attributes"]);
					//console.log(attributes_string);
					widget_settings["attributes"] = attributes_string;
				}
			}
		}
	};
	
	me.openHtmlTagSettings = function(widget, menu_settings) {
		var tag_name = widget[0].nodeName.toLowerCase();
		var is_singular_node = $.inArray(tag_name, me.singular_html_tags) != -1;
		
		if (is_singular_node)
			menu_settings.addClass("menu-settings-html-singular-tag");
		else {
			var tag_property_elm = menu_settings.find(" > .settings-properties > ul > .settings-property.html-tag");
			
			//menu_settings.removeClass("menu-settings-html-singular-tag"); //no need because when the menu gets open, it erases the classes previously set.
			
			if (me.available_html_tags.hasOwnProperty(tag_name))
				tag_property_elm.addClass("select-shown");
			else
				tag_property_elm.removeClass("select-shown");
			
			me.addMenuSettingsContextMenu( tag_property_elm.find("input, select") );
		}
	};
	
	me.onCloneMenuHtmlTextWidget = function(widget, html_element, options) {
		if (!html_element) {
			var text_node = document.createTextNode( widget.text() ); //create text node
			widget = $(text_node);
			
			//place caret after text node and select text
			setTimeout(function() {
				var doc = text_node.ownerDocument || text_node.document;
				var win = doc.defaultView || doc.parentWindow;
				
				if (typeof win.getSelection != "undefined") {
					widget.parent().focus(); //focus the parent so the user can write directly, right after it drops this widget.
					
					var selection = win.getSelection();
					var range = new Range();
					range.setStart(text_node, 0);
					range.setEnd(text_node, text_node.textContent.length);
					selection.removeAllRanges();
					selection.addRange(range);
				}
			}, 500);
		}
		
		return widget;
	};
	
	me.onDropMenuHtmlCodeWidget = function(menu_widget, widget, event, ui_obj) {
		var popup_content = ui.children(".layout-ui-editor-popup.html-code");
		
		me.popup_widget = widget;
		
		if (popup_content[0] && me.popup_elm.is(popup_content))
			me.showPopup();
		else {
			var html = '<div>'
				+ '<div class="title">Write your html code below:</div>'
				+ '<div class="content">'
					+ '<textarea></textarea>'
				+ '</div>'
				+ '<div class="buttons">'
					+ '<button>Add Html Code</button>'
				+ '</div>'
			+ '</div>';
			
			popup_content = $(html);
			popup_content.addClass("html-code");
			ui.append(popup_content);
			
			var c = popup_content.children(".content");
			createCodeEditor( c.children("textarea")[0] );
			
			popup_content.find(" > .buttons > button").on("click", function(e) {
				//console.log("button click");
				var editor = c.data("editor");
				var html = editor ? editor.getValue() : c.children("textarea").val();
				
				if (html.replace(/\s/g, "") && me.popup_widget) {
					me.parseHtmlSource(html, me.popup_widget);
					
					var first_child = me.popup_widget.children().first();
					var children = me.popup_widget[0].childNodes; //including text nodes
					
					for (var i = children.length - 1; i >= 0; i--)
						me.popup_widget.after(children[i]);
					
					if (first_child[0])
						first_child.trigger("click");
					
					prepareMenuLayers();
				}
				
				//cleans text
				if (editor)
					editor.setValue("");
				else
					c.children("textarea").val("");
				
				me.hidePopup();
			});
			
			me.initPopup({
				elementToShow: popup_content,
				parentElement: document,
				onOpen: function() {
					//console.log("on open");
					var editor = c.data("editor");
					
					if (editor) {
						editor.resize();
						editor.setValue("");
						editor.focus();
					}
					else
						c.children("textarea").val("").focus();
				},
				onClose: function() {
					//console.log("on close");
					if (me.popup_widget && me.popup_widget[0] && me.popup_widget[0].parentNode) //check if widget really exists, bc this function is called on hide and if the user selects a bootstrap widget, then the handler will replace this widget, whcih means its parentNode will not exists, bc the widget was removed before.
						me.deleteTemplateWidget(me.popup_widget);
					
					me.popup_widget = null;
				},
			});
			me.showPopup();
		}
	};
	
	me.onCreateTemplateHtmlCommentWidget = function(widget, html_element, options) {
		if (html_element) {
			//var text = html_element.textContent.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")/*.replace(/\n/g, "<br>").replace(/\t/g, "&emsp;").replace(/ /g, "&nbsp;")*/;
			var text = html_element.textContent;
			var j_html_element = $(html_element);
			var is_recreate = html_element.nodeName.toLowerCase() == "div" && j_html_element.children().length == 1 && j_html_element.children("pre")[0];
			
			//in case of recreate widget
			if (is_recreate)
				text = j_html_element.children("pre").text();
			
			widget.children("pre").text(text); //Do not use .html(text), bc we don't the jquery to parse the special chars in the text. we want the raw textContent from the source code.
			widget.attr("data-node-type", html_element.nodeType);
			widget.attr("data-node-name", html_element.nodeName);
			
			if (!widget.is(html_element)) //when recreate the divs are the same
				j_html_element.remove();
		}
		else
			widget.data("data-label", widget.attr("data-label")).removeAttr("data-label");
	};
	
	me.onCleanTemplateCommentWidgetHtml = function(html_element) {
		var widget = $(html_element);
		//var node_type = widget.attr("data-node-type");
		//var node_name = widget.attr("data-node-name");
		
		var text = widget.children("pre").text(); //Do not use .html(text), bc we don't the jquery to parse the special chars in the text. we want the raw textContent from the source code.
		//text = text.replace(/&lt;/g, "<").replace(/&gt;/g, ">")/*.replace(/<br>/g, "\n").replace(/&emsp;/g, "\t").replace(/&nbsp;/g, " ")*/.replace(/&amp;/g, "&")
		text = (text[0] == "" ? " " : "") + text + (text[ text.length - 1 ] == "" ? " " : "");
		
		return "<!--" + text + "-->";
	};
	
	/* WIDGETS METHODS */
	
	//This method is to be called in the on-clone-menu-widget func
	//Note that all the template-widget contents from the menu-widget will be ignore.
	//This method will return the html_element, prepared to be transformed in to a template widget.
	me.getNewTemplateWidgetBasedInHtmlElement = function(widget, html_element, data_attributes_to_add) {
		if (html_element && html_element.nodeType == Node.ELEMENT_NODE) { //only if is a html tag. If html_element is a text or a comment node, doesn't do anything and return the original widget.
			var elm = $(html_element);
			
			elm.off(); //remove all events attached to the html element
			
			//preparing classes
			var elm_classes = html_element.hasAttribute("class") ? elm.attr("class").split(" ") : [];
			elm.attr("class", widget.attr("class"));
			
			$.each(elm_classes, function(idx, c) {
				c = c.replace(/\s+/g, "");
				
				if (c != "")
					me.addNodeClass(elm, c);
			});
			
			var widget_default_attributes = ["data-label", "data-tag", "data-target-id", "data-template-id"];
			
			if (!data_attributes_to_add)
				data_attributes_to_add = widget_default_attributes;
			else
				data_attributes_to_add.concat(widget_default_attributes);
			
			if ($.isArray(data_attributes_to_add) && data_attributes_to_add.length > 0) {
				var attrs = widget[0].attributes;
				
				for (var i = 0; i < attrs.length; i++) {
					var attr = attrs[i];
					var attr_name = attr.name.toLowerCase();
					
					if (data_attributes_to_add.indexOf(attr_name) != -1 && attr_name != "class")
						elm.data(attr.name, attr.value); //overwrite even if already exist, bc the attributes in data_attributes_to_add are more important, bc are the default widget attrs.
				}
			}
			
			widget.remove(); //remove cloned widget
			
			return elm;
		}
		
		return widget;
	};
	
	me.hasNodeClass = function(node, c) {
		var elm = node instanceof jQuery ? node : $(node);
		
		if (elm[0] && (c || c === 0)) {
			var has = elm.hasClass(c);
			
			if (!has && elm[0].hasAttribute("class") && typeof c != "function" && elm[0].getAttribute("class").indexOf(c) != -1) { //only strings are allowed
				c = "" + c;
				
				//Do not use the hasClass because always returns false on certain elements, like the "path".
				//only check for first element, like the hasClass method
				if (c.match(/\s/)) { //if multiple classes, check if classes matches including the order and spaces in between, just like the hasClass method
					var node_c = elm[0].getAttribute("class");
					var pos = node_c.indexOf(c);
					
					if (pos != -1) {
						var prev = pos > 0 ? node_c.substr(pos - 1, 1) : "";
						var next = pos + c.length < node_c.length ? node_c.substr(pos + c.length, 1) : "";
						
						has = (prev.length == 0 || prev.match(/\s/)) && (next.length == 0 || next.match(/\s/));
					}
				}
				else //if only one class
					has = elm[0].classList.contains(c);
			}
			
			return has;
		}
	};
	
	me.addNodeClass = function(node, c) {
		var elm = node instanceof jQuery ? node : $(node);
		
		if (elm[0] && (c || c === 0)) {
			var old_class = elm.attr("class");
			
			elm.addClass(c); //by calling the addClass method, if c already exists, doesn't repeat it. The system will move this class to the end of the classes.
			
			var new_class = elm.attr("class");
			
			if (new_class == old_class) {
				//in case the addClass doesn't work, like when manipulating "path" elements
				//Do not use the hasClass because always returns false on certain elements, like the "path".
				var classes = null;
				var prev_classes_to_add = null;
				var is_func = typeof c == "function";
				
				for (var i = 0, t = elm.length; i < t; i++) 
					if (elm[i].nodeType == Node.ELEMENT_NODE) {
						var classes_to_add = c;
						var curr_class = elm[i].getAttribute("class");
						var curr_class_bkp = curr_class;
						
						//prepare classes_to_add based in function
						if (is_func) {
							classes_to_add = c(i, curr_class);
							
							if ($.isArray(classes_to_add))
								classes_to_add = classes_to_add.join(" ");
							
							//reset classes if different
							if (prev_classes_to_add != classes_to_add) {
								classes = null;
								prev_classes_to_add = classes_to_add;
							}
						}
						
						//add classes from elm
						if (classes_to_add !== "" && !me.hasNodeClass(elm[i], classes_to_add)) {
							classes_to_add = "" + classes_to_add;
							
							if (classes !== null || classes_to_add.match(/\s/)) { //if multiple classes
								if (classes === null)
									classes = classes_to_add.replace(/(^\s+|\s+$)/g, "").split(/\s+/);
							}
							else //if only one class
								classes = [classes_to_add];
							
							for (var j = 0, l = classes.length; j < l; j++)
								if (classes[j]) {
									elm[i].classList.add( classes[j] );
									
									if (!elm[i].classList.contains( classes[j] )) {
										var escaped = classes[j].replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
										var reg = new RegExp("(^|\\s)" + escaped + "(\\s|$)", "g");
										
										if (!curr_class.match(reg))
											curr_class += " " + classes[j];
									}
								}
							
							//console.log(curr_class);
							if (curr_class != curr_class_bkp)
								elm[i].setAttribute("class", curr_class);
						}
					}
			}
		}
		
		return node;
	};
	
	me.removeNodeClass = function(node, c) {
		var elm = node instanceof jQuery ? node : $(node);
		
		if (elm[0] && (c || c === 0)) {
			var old_class = elm.attr("class");
			
			elm.removeClass(c);
			
			var new_class = elm.attr("class");
			
			if (new_class == old_class) {
				//in case the removeClass doesn't work, like when manipulating "path" elements
				//Do not use the hasClass because always returns false on certain elements, like the "path".
				var classes = null;
				var prev_classes_to_remove = null;
				var is_func = typeof c == "function";
				
				for (var i = 0, t = elm.length; i < t; i++)
					if (elm[i].nodeType == Node.ELEMENT_NODE) {
						var classes_to_remove = c;
						var curr_class = elm[i].getAttribute("class");
						var curr_class_bkp = curr_class;
						
						//prepare classes_to_remove based in function
						if (is_func) {
							classes_to_remove = c(i, curr_class);
							
							if ($.isArray(classes_to_remove))
								classes_to_remove = classes_to_remove.join(" ");
							
							//reset classes if different
							if (prev_classes_to_remove != classes_to_remove) {
								classes = null;
								prev_classes_to_remove = classes_to_remove;
							}
						}
						
						//remove classes from elm
						if (classes_to_remove !== "" && me.hasNodeClass(elm[i], classes_to_remove)) {
							classes_to_remove = "" + classes_to_remove;
							
							if (classes !== null || classes_to_remove.match(/\s/)) { //if multiple classes
								if (classes === null)
									classes = classes_to_remove.replace(/(^\s+|\s+$)/g, "").split(/\s+/);
							}
							else //if only one class
								classes = [classes_to_remove];
							
							for (var j = 0, l = classes.length; j < l; j++)
								if (classes[j]) {
									elm[i].classList.remove( classes[j] );
									
									if (elm[i].classList.contains( classes[j] )) {
										var escaped = classes[j].replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
										var reg = new RegExp("(^|\\s)" + escaped + "(\\s|$)", "g");
										curr_class = curr_class.replace(reg, " ").replace(/\s+$/, "");
									}
								}
							
							//console.log(curr_class);
							if (curr_class != curr_class_bkp)
								elm[i].setAttribute("class", curr_class);
						}
					}
			}
		}
		
		return node;
	};
	
	me.onCloneMenuWidget = function(widget, html_element) {
		return me.getNewTemplateWidgetBasedInHtmlElement(widget, html_element, null);
	};
	
	me.createTemplateWidgetFromMenuWidgetTag = function(widget_tag, template_container, html_element, options) {
		var menu_widget = menu_widgets.find(".menu-widget-" + widget_tag).first();
		
		return createTemplateWidgetFromMenuWidget(menu_widget, template_container, html_element, options);
	};
	
	function createTemplateWidgetFromMenuWidget(menu_widget, template_container, html_element, options) {
		var widget = menu_widget.children(".template-widget").clone();
		
		var func = menu_widget.attr("data-on-clone-menu-widget-func");
		if (func && eval('typeof ' + func + ' == "function"')) {
			eval('var widget_transformed = ' + func + '(widget, html_element, options);');
			widget = widget_transformed ? widget_transformed : widget;
		}
		
		if (typeof me.options.on_clone_menu_widget_func == "function") {
			var widget_transformed = me.options.on_clone_menu_widget_func(widget, html_element, options);
			widget = widget_transformed ? widget_transformed : widget;
		}
		
		var tag = menu_widget.attr("data-tag");
		var label = widget.data("data-label");
		label = label ? label : menu_widget.attr("title");
		
		widget.data("data-tag", tag).data("data-label", label);
		
		var id = widget.data("data-template-id");
		if (!id) {
			id = "template_widget_" + tag + "_" + makeRandomId();
			widget.data("data-template-id", id);
		}
		
		me.addNodeClass(widget, id);
		widget.data("data-target-id", menu_widget.attr("id") );
		
		return createTemplateWidgetFromWidget(widget, template_container, html_element, options);
	}
	
	function createTemplateWidgetFromWidget(widget, template_container, html_element, options) {
		var widget_id = widget.data("data-template-id");
		var menu_widget = me.getTemplateMenuWidget(widget);
		
		if (!template_container.is(widget.parent())) {
			if (html_element && template_container.is( $(html_element).parent() ))
				template_container[0].insertBefore(widget[0], html_element); //This is very important bc if we execute the appendChild bellow it will mess the order of the template_container's children, this is, when running the parseHtmlContents function, if the next sibling of html_element is already inside of the template_container, then we are passing the widget to be the last child of template_container, bc we are doing: template_container[0].appendChild(widget[0]). A good example is if we have a ptl tag and then a table html elements. When running the parseHtmlContents we will have the table and then the ptl tag, which means it changes the order of the template_container's children. So is very important to execute the template_container[0].insertBefore instead of template_container[0].appendChild.
			else
				template_container[0].appendChild(widget[0]); //I cannot do template_container.append(widget), bc if there is an element tag (e.g: DIV) which contains a script tag (as it's child or sub-child), but this script tag contains javascript code with errors, the jquery will parse this DIV and it's children, including the javascript code. In this case the jquery will return an exception. If we use the appendChild method instead, the browser will not parse the element's children, even if there are errors.
		}
		
		//parse some menu_template_widget or html_element's styles
		var css_to_remove = { /* remove hard coded styles from jquery.draggable */
			position: "",
			top: "",
			left: "",
			right: "",
			bottom: "",
			width: "",
			height: "",
		};
		
		//if html_element exists and style width exists, it should have the correspondent width/height/etc from the html_element
		if (html_element) {
			if (html_element.nodeType == Node.ELEMENT_NODE && html_element.hasAttribute("style")) {
				//Do not use .css("width") or any other prop name, othwerwise it will return 0px, if the width doesn't exist! We only want to return the width if there is a style hard-coded in the html_element. So we must use the html_element.style!
				for (var prop_name in css_to_remove) {
					if (html_element.style.hasOwnProperty(prop_name) || prop_name in html_element.style)
						css_to_remove[prop_name] = html_element.style[prop_name];
				}
				//console.log(css_to_remove);
			}
		}
		else { //get styles from menu template widget
			var menu_template_widget = menu_widget.children(".template-widget")[0];
			
			//if menu_template_widget exists and style width exists, it should have the correspondent width/height/etc from the menu_template_widget
			if (menu_template_widget && menu_template_widget.hasAttribute("style")) {
				//Do not use .css("width") or any other prop name, othwerwise it will return 0px, if the width doesn't exist! We only want to return the width if there is a style hard-coded in the menu_template_widget. So we must use the menu_template_widget.style!
				for (var prop_name in css_to_remove)
					if (menu_template_widget.style.hasOwnProperty(prop_name) || prop_name in menu_template_widget.style)
						css_to_remove[prop_name] = menu_template_widget.style[prop_name];
				//console.log(css_to_remove);
			}
		}
		
		me.removeNodeClass(widget, "ui-draggable-dragging");
		
		widget.css(css_to_remove)
			.hover(function(event) {
				event.preventDefault();
				event.stopPropagation();
				
				var w = $(this); //must do this bc if the nodeName changes, we will loose the widget var reference.
				
				me.removeNodeClass(template_widgets_droppable.find(".template-widget"), "widget-over");
				me.addNodeClass(w, "widget-over");

				//only if there is no other widget-active
				//if (template_widgets_droppable.find(".template-widget.widget-active").length == 0)
				//	openWidgetHeader(w);

				hoverMenuLayerWidget(widget_id);
			}, function(event) {
				event.preventDefault();
				event.stopPropagation();

				if (!widget_header.is(":hover")) {
					me.removeNodeClass($(this), "widget-over");
					
					unhoverMenuLayerWidget(widget_id);
				}
			});
		
		me.setTemplateWidgetOnClickEvent(widget);
		me.setTemplateWidgetOnDragEvent(widget);
		
		//set resizable
		//not working correctly and on zoom is buggy. the size will be set on the properties painel
		var is_resizable = menu_widget.attr("data-resizable");
		is_resizable = !is_resizable || is_resizable == "" || is_resizable == "0" || ("" + is_resizable).toLowerCase() == "false" ? false : true;
		
		if (is_resizable)
			widget.data("resizable", true);
		
		var is_absolute_position = menu_widget.attr("data-absolute-position");
		is_absolute_position = !is_absolute_position || is_absolute_position == "" || is_absolute_position == "0" || ("" + is_absolute_position).toLowerCase() == "false" ? false : true;
		
		if (is_absolute_position)
			widget.data("absolute-position", true);
		
		//set droppable childs only if widget comes from menu widgets
		if (!html_element)
			widget.find(".droppable").each(function (idx, child) {
				me.setWidgetChildDroppable(child);
			});
		else { 
			var depth = 3; //Note: 3 should be enough. In the future maybe find a better logic
			var w = widget;
			var children = null;
			do {
				children = [];
				$.each(w, function(idx, item) {
					children = children.concat( $(item).children().toArray() );
				});
				children = $(children);
				
				children.filter(".droppable").each(function (idx, child) {
					me.setWidgetChildDroppable(child);
				});
				
				w = children;
				--depth;
			} while(depth >= 0);
		}
		
		var func = menu_widget.attr("data-on-create-template-widget-func");
		if (func && eval('typeof ' + func + ' == "function"'))
			eval(func + '(widget, html_element, options);');
		
		if (typeof me.options.on_create_template_widget_func == "function")
			me.options.on_create_template_widget_func(widget, html_element, options);
		
		if (!html_element && widget[0].nodeType == Node.ELEMENT_NODE) //could be a text node from the html-text widget
			me.updateMenuLayer(widget); //only do it if widget comes from menu widgets, otherwise this will be super slow
		
		return widget;
	}
	
	//This function is called in the button widget xml
	me.setTemplateWidgetOnClickEvent = function(widget) {
		if (widget[0].nodeType == Node.ELEMENT_NODE)
			widget.click(function(event) {
				event.preventDefault();
				event.stopPropagation();
				
				//only allow click event if there isn't any widget resizing
				if (!me.start_resizing_elm) { 
					var w = $(this); //must do this bc if the nodeName changes, we will loose the widget var reference.
					
					//set selected_template_widgets_droppable
					selected_template_widgets_droppable = w.parent().closest(".main-droppable");

					me.removeNodeClass(template_widgets_droppable.find(".template-widget"), "widget-active");
					me.removeNodeClass(template_widgets_droppable.find(".droppable"), "droppable-active");
					me.addNodeClass(w, "widget-active");
					w.data("hidden-header-timeout", "");

					setSelectedTemplateWidget(w);
					
					if (w.hasClass("ui-draggable")) {
						//Do not use w.draggable("disable") and w.draggable("enable") bc it doesn't work.
						me.disable_dragging_elm_on_click_timeout && clearTimeout(me.disable_dragging_elm_on_click_timeout);
						
						me.disable_dragging_elm_on_click_timeout = setTimeout(function() {
							if (w.is(".ui-draggable-dragging"))
								w.trigger("mouseup");
							
							me.disable_dragging_elm_on_click_timeout = null;
						}, 300);
					}
				}
				
				return false; //very important bc if the widget is a input submit inside of a form, the form will be submited.
			});
	};
	
	me.setTemplateWidgetOnDragEvent = function(widget) {
		return false; //Do not allow draggable elements be then we cannot select the text.
		
		if (widget[0].nodeType == Node.ELEMENT_NODE) {
			var on_drag_start = function() {
				//only allow dragging if position is absolute or fixed.
				var position = widget.css("position");
				
				if (position != "absolute" && position != "fixed")
					return false;
				
				//only allow dragging if not resizing
				if (me.start_resizing_elm)
					return false;
				
				//only allow dragging if not click event
				if (me.disable_dragging_elm_on_click_timeout) {
					clearTimeout(me.disable_dragging_elm_on_click_timeout);
					me.disable_dragging_elm_on_click_timeout = null;
					
					return false;
				}
				
				if (me.isMenuSettingsVisible()) {
					resetMenuSettings(); //reset is very important otherwise the widget starts acumulating repeated classes
					loadMenuSettings(widget); //update menuSettings fields...
				}
				
				//this.visibility_bkp = me.selected_template_widget[0].style.visibility;
				//me.selected_template_widget.css("visibility", "hidden");
				template_widgets_droppable.addClass("dropping");
				
				hideWidgetHeader();
				hideDroppableHeader();
			};
			var on_drag_stop = function() {
				resetMenuSettings(); //reset is very important otherwise the widget starts acumulating repeated classes
				loadMenuSettings(widget); //update menuSettings fields...
				openWidgetHeader(widget); //show widget header again
				
				template_widgets_droppable.removeClass("dropping");
				
				if (typeof me.options.on_template_widgets_layout_changed_func == "function")
					me.options.on_template_widgets_layout_changed_func(widget);
			};
			
			/* resize down't work with code below:
			var newPosX = 0, newPosY = 0, startPosX = 0, startPosY = 0;
			var doc = $(widget[0].ownerDocument || widget[0].document);
			
			var mouseMove = function(e) {
				e.preventDefault();
				e.stopPropagation();
				
				// calculate the new position
				newPosX = startPosX - e.clientX;
				newPosY = startPosY - e.clientY;

				// with each move we also want to update the start X and Y
				startPosX = e.clientX;
				startPosY = e.clientY;
				
				// set the element's new position:
				var o = widget.offset();
				widget.offset({top: o.top - newPosY, left: o.left - newPosX});
			};
			
			// when the user clicks down on the element
			widget.addClass("ui-draggable ui-draggable-handle")
			.bind('mousedown', function(e) {
				//e.preventDefault();
				e.stopPropagation();
				
				if (on_drag_start() === false)
					return false;
				
				widget.addClass("ui-draggable-dragging");
				
				// get the starting position of the cursor
				startPosX = e.clientX;
				startPosY = e.clientY;

				doc.bind('mousemove', mouseMove);
			})
			.bind('mouseup', function(e) {
				doc.unbind('mousemove', mouseMove);
				widget.removeClass("ui-draggable-dragging");
				
				on_drag_stop();
			});
			
			return true;*/
			
			//text selection doesn't work with text below:
			widget.draggable({
				//settings for the iframe droppable
				iframeFix:true,
				iframeScroll: true,
				scroll: true,
				scrollSensitivity: 20,

				//others settings
				appendTo: "body",
				cursor: "move",
				tolerance: "pointer",
				opacity: 1,
				refreshPositions: true,
				grid: $.isArray(me.options.moving_tolerance_grid) ? me.options.moving_tolerance_grid : [10, 10],
				
				//handlers
				start: function(event, ui_obj) {
					on_drag_start();
				},
				stop: function(event, ui_obj) {
					widget.draggable("option", {revert: false }); //set all draggable parts back to revert: false. This fixes elements after drag was cancelled with ESC key
					
					on_drag_stop();
				},
			});
		}
	};
	
	me.setWidgetChildDroppable = function(child) {
		child = $(child);
		
		var type = child.data("data-label");
		type = type ? type : "Column";
		
		var id = "template_widget_droppable_" + type.toLowerCase().replace(/\s+/g, "_").replace(/\-+/g, "_") + "_" + makeRandomId();
		child.data("data-template-id", id);
		me.addNodeClass(child, id);
		
		child.hover(function(event) {
			var d = $(this); //must do this bc if the nodeName changes, we will loose the child var reference.
				
			//Do not add the preventDefault and stopPropagation here bc the widget events must be triggered!
			me.removeNodeClass($(".droppable"), "droppable-over");
			me.addNodeClass(d, "droppable-over");
	  		
	  		openDroppableHeader(d);
		  }, function(event) {
			var d = $(this); //must do this bc if the nodeName changes, we will loose the child var reference.
			
			//Do not add the preventDefault and stopPropagation here bc the widget events must be triggered!
			
			if (!droppable_header.is(":hover")) {
	  			me.removeNodeClass(d, "droppable-over");
	  			
	  			if (!me.hasNodeClass(d, "droppable-active"))
			  		hideDroppableHeader(d);
		  	}
		  })
		  .click(function(event) {
			var d = $(this); //must do this bc if the nodeName changes, we will loose the child var reference.
			
			//set selected_template_widgets_droppable
			selected_template_widgets_droppable = d.parent().closest(".main-droppable");
			
			//Do not add the preventDefault and stopPropagation here bc the widget events must be triggered!
			me.removeNodeClass($(".droppable"), "droppable-active");
			me.addNodeClass(d, "droppable-active");
			d.data("hidden-header-timeout", "");
			
			hideDroppableHeader(); //For the previous droppable
			openDroppableHeader(d);
			
			//console.log(child);
		  });
	};
	
	me.updateTemplateWidgetLabel = function(widget, label) {
		widget.data("data-label", label);
		widget_header.children("label").html(label);
		
		menu_layers.find('.group.is-widget[template-target-id="' + widget.data("data-template-id") + '"] > .group-title > label').html(label);
	};
	
	me.getTemplateWidgetLabel = function(widget, label) {
		return widget.data("data-label");
	};
	
	me.getTemplateWidget = function(widget_id) {
		return template_widgets_droppable.find(".template-widget." + widget_id + "");
	};
	
	me.getTemplateDroppable = function(droppable_id) {
		return template_widgets_droppable.find(".droppable." + droppable_id + "");
	};
	
	me.getTemplateWidgetCurrentReservedClasses = function(widget) {
		var classes = [];
		var widget_classes = widget.attr("class");
		
		if (widget_classes) {
			var widget_id = widget.data("data-template-id");
			var matches = widget_classes.match(/[^ ]+/gi);
			var regexes = [
				/^template-widget$/i,
				/^template-widget-[^ ]+$/i,

				/^widget-[^ ]+$/i,
				/*/^widget-active$/i,
				/^widget-over$/i,
				/^widget-hidden$/i,*/
				
				/^ui-droppable$/i,
				/^droppable$/i,
				/^droppable-[^ ]+$/i,
				/*/^droppable-active$/i,
				/^droppable-over$/i,
				/^droppable-hidden$/i,*/
				
				/^hidden-header$/i,
				/^ignore-widget$/i,
				/^not-widget$/i,
				/^ui-sortable$/i,
				/^ui-draggable$/i,
				/^ui-draggable-handle$/i,
				/^ui-draggable-dragging$/i,
				
				//for the main-droppables
				/^main-droppable$/i,
				/^borders$/i,
				/^with-background$/i,
				/^widget-children-hidden$/i,
				/^widget-resize-(n|s|e|w|ne|nw|se|sw)$/i,
				
				//extra classes for widgets due to the ids created automatically and added to the widget class
				/^template_widget_[^ ]+$/
				/*/^template_widget_droppable_[^ ]+$/*/
			];
			
			if (matches)
				for (var i = 0; i < matches.length; i++) {
					var m = matches[i];
					
					if (m) {
						m = m.replace(/ /g, "");
						
						if (m == widget_id)
							classes.push(m);
						else
							for (var j = 0; j < regexes.length; j++)
								if (m.match(regexes[j]))
									classes.push(m);
					}
				}
			
			//get template widget default classes
			var menu_widget = me.getTemplateMenuWidget(widget);
			var extra = menu_widget.data("data-template-widget-default-class");
			var parts = extra ? extra.split(" ") : [];
			
			for (var i = 0; i < parts.length; i++) {
				var part = parts[i].replace(/\s+/g, "");
				
				if (part)
					classes.push(part);
			}
		}
		
		return classes;
	};
	
	//used in the widgets too
	me.getTemplateWidgetCurrentClassesWithoutReservedClasses = function(widget) {
		var widget_classes = "";
		
		//adding class
		if (widget[0].hasAttribute("class")) {
			widget_classes = widget.attr("class");
			var classes_to_remove = me.getTemplateWidgetCurrentReservedClasses(widget);
			
			for (var j = 0; j < classes_to_remove.length; j++) {
				eval('widget_classes = widget_classes.replace(/(^| )' + classes_to_remove[j] + '( |$)/gi, " ");');
				
				//recheck to be sure that the class was deleted. If class is repeated, this is very usefull, otherwise it will remove only 1 repeated class and leave the other one. eg: if we have class="bla row row", it will remove only one "row" class and leave the other one.
				if (eval('widget_classes.match(/(^| )' + classes_to_remove[j] + '( |$)/gi)'))
					--j;
			}
			
			widget_classes = widget_classes.replace(/(^|\s)template_widget_\S+/g, "");
			widget_classes = widget_classes.replace(/^\s+/g, "").replace(/\s+$/g, "").replace(/\s\s+/g, " ");
		}
		
		return widget_classes;
	};
	
	me.deleteTemplateWidget = function(widget, do_not_callback) {
		//call widget onBeforeDeleteTemplateWidget function
		if (!do_not_callback && me.LayoutUIEditorWidgetResource)
			me.LayoutUIEditorWidgetResource.onBeforeDeleteTemplateWidget(widget);
		
		var is_widget_selected = me.selected_template_widget && widget.is(me.selected_template_widget);
		
		deleteMenuLayer(widget);
		widget.remove();
		
		if (is_widget_selected)
			unsetSelectedTemplateWidget();
		
		//call widget onAfterDeleteTemplateWidget function
		if (!do_not_callback && me.LayoutUIEditorWidgetResource)
			me.LayoutUIEditorWidgetResource.onAfterDeleteTemplateWidget();
		
		if (!do_not_callback && typeof me.options.on_template_widgets_layout_changed_func == "function")
			me.options.on_template_widgets_layout_changed_func(null);
	};
	
	me.getTemplateMenuWidget = function(widget) {
		return menu_widgets.find("#" + widget.data("data-target-id"));
	};
	
	/* REPLACE TEMPLATE WIDGET METHODS */
	
	//This will simply change the widget node name
	me.changeWidgetNodeName = function(widget, node_name) {
		if (widget[0] && node_name != "") {
			var elm = document.createElement(node_name);
			elm = $(elm);
			
			//get new node data from menu widgets
			var dummy_widget = elm.clone();
			widget.before(dummy_widget);
			me.convertHtmlElementToWidget(dummy_widget);
			var is_different_widget_tag = dummy_widget[0] && widget.data("data-tag") != dummy_widget.data("data-tag");
			
			//pass widget attrs to elm
			$.each(widget[0].attributes, function(idx, attr) {
				elm.attr(attr.name, attr.value);
			});
			
			//set widget data to elm
			var widget_data = widget.data();
			elm.data(widget_data);
			
			if (is_different_widget_tag) {
				var dummy_widget_data = dummy_widget.data();
				
				for (var k in dummy_widget_data)
					elm.data(k, dummy_widget_data[k]);
				
				//prepare classes
				var classes = me.getTemplateWidgetCurrentClassesWithoutReservedClasses(widget);
				//classes = classes.replace(/(^|\s)template_widget_\S+/g, "");
				classes = dummy_widget.attr("class") + " " + classes;
				elm.attr("class", classes);
			}
			else {
				var label = node_name.charAt(0).toUpperCase() + node_name.slice(1);
				elm.data("data-label", label);
			}
			
			dummy_widget.remove();
			
			var new_widget = createTemplateWidgetFromWidget(elm, widget.parent(), null);
			new_widget.html("");
			new_widget.append( widget.contents() );
			
			if (is_different_widget_tag)
				me.recreateWidget(new_widget); //This is very important bc if we convert a div to a link ("a" element), then the widget will still be a html-block type, and we want to replace it with a link widget.
			
			var extra_classes = "";
			extra_classes += me.hasNodeClass(new_widget, "widget-over") ? "widget-over" : "";
			extra_classes += me.hasNodeClass(new_widget, "widget-active") ? "widget-active" : "";
			
			me.addNodeClass(new_widget, extra_classes);
			
			var ret = me.replaceWidgetWithWidget(widget, new_widget) ? new_widget : null;
			
			//if (typeof me.options.on_template_widgets_layout_changed_func == "function")
			//	me.options.on_template_widgets_layout_changed_func(ret);
			
			return ret;
		}
	};
	
	//This will replace one widget by another one. Both widgets must be already inited.
	me.replaceWidgetWithWidget = function(widget, new_widget) {
		if (widget[0] && new_widget[0]) {
			new_widget.insertBefore(widget);
			
			me.deleteTemplateWidget(widget, true);
			
			me.updateMenuLayer(new_widget);
			setSelectedTemplateWidget(new_widget);
			
			//if (typeof me.options.on_template_widgets_layout_changed_func == "function")
			//	me.options.on_template_widgets_layout_changed_func(new_widget);
			
			return new_widget;
		}
	};
	
	/* SELECTED TEMPLATE WIDGET METHODS */
	
	function setSelectedTemplateWidget(widget) {
		/* OLD WIDGET ACTIONS: */
		//console.log(me.selected_template_widget);
		
		//hide the widget header for the previous widget: this is to execute the on-close-widget-header-func
		hideWidgetHeader();
		
		/* I THINK I DONT NEED THIS HERE. ONLY THE WIDGET HEADER IS NECESSARY TO CLOSE HERE.
		//hide droppable header for the previous widget: this is to execute the on-close-droppable-header-func
		if (!droppable_header.is(":hover")) //for some reason we need to add this here, otherwise the droppable_header will never be shown for the containers. Not very well tested. Not sure if this can cause other problems bc the on-close-droppable-header-func won't be executed if droppable_header is hover.
			hideDroppableHeader();
		*/
		
		//close the widget settings for the previous widget: this is to execute the on-close-widget-settings-func
		closeMenuSettings();
		
		/* NEW WIDGET ACTIONS: */
		
		//set selected widget
		me.selected_template_widget = widget;
		
		//open widget header
		openWidgetHeader(widget);
		
		//set selected widget in menu-layers
		selectMenuLayerWidget( widget.data("data-template-id") );
		
		//enable menu-settings panel
		enableMenuSettings();
		
		//prepare menu-settings panel
		openMenuSettings(widget);
		
		//show menu settings panel
		ui.find(" > .options .show-settings").click();
	}
	
	function unsetSelectedTemplateWidget() {
		//hide widget header
		hideWidgetHeader();
		
		//hide droppable header
		hideDroppableHeader();
		
		//unset selected widget in menu-layers
		unselectMenuLayerWidgets();
		
		//close the widget settings
		closeMenuSettings();
		
		//disable menu-settings panel
		disableMenuSettings();
		
		//set selected widget
		me.selected_template_widget = null;
	}
	
	/* WIDGET-HEADER METHODS */
	
	function initWidgetHeader(main_elm) {
		widget_header = $('<div class="' + me.options.widget_header_class + '">' +
					'<label></label>' +
					'<span class="options">' +
						'<i class="zmdi zmdi-gamepad option move" title="Move to another Container"></i>' +
						'<i class="zmdi zmdi-arrows option move-position" title="Re-Position - Note that by moving a static widget, its position will be changed to an absolute position automatically."></i>' +
						'<i class="zmdi zmdi-swap zmdi-hc-rotate-90 option sort" title="Sort"></i>' +
						'<i class="zmdi zmdi zmdi-long-arrow-tab zmdi-hc-rotate-270 option sort-up" title="Sort Up"></i>' +
						'<i class="zmdi zmdi zmdi-long-arrow-tab zmdi-hc-rotate-90 option sort-down" title="Sort Down"></i>' +
						'<span class="option other-options" title="Other Options">' +
							'<i class="zmdi zmdi zmdi-more" title="Other Options"></i>' +
							'<ul class="sub-options">' +
								'<li class="option props" title="Show Properties"><i class="zmdi zmdi-settings"></i>Show Properties</li>' + 
								'<li class="option html-contents" title="Edit Contents"><i class="zmdi zmdi-edit"></i>Edit Contents</li>' + 
								'<li class="option toggle" title="Toggle"><i class="zmdi zmdi-unfold-less"></i>Toggle</li>' +
								'<li class="option select-parent" title="Select Parent"><i class="zmdi zmdi-forward zmdi-hc-rotate-270"></i>Select Parent</li>' +
								'<li class="option delete" title="Delete"><i class="zmdi zmdi-delete"></i>Delete</li>' +
								'<li class="option add" title="Add Widget"><i class="zmdi zmdi-plus-circle-o"></i>Add Widget</li>' +
								'<li class="option copy" title="Copy Widget"><i class="zmdi zmdi-copy"></i>Copy Widget</li>' +
								'<li class="option cut" title="Cut Widget"><i class="zmdi zmdi-scissors"></i>Cut Widget</li>' +
								'<li class="option paste" title="Paste Widget"><i class="zmdi zmdi-paste"></i>Paste Widget</li>' +
								'<li class="option html-source" title="Widget Html Source"><i class="zmdi zmdi-code"></i>Widget Html Source</li>' +
								'<li class="option link-contents" title="Convert Contents to Link"><i class="zmdi zmdi-link"></i>Convert Contents to Link</li>' +
								'<li class="option recreate-widget" title="Recreate Widget in Layout"><i class="zmdi zmdi-refresh"></i>Recreate Widget</li>' +
							'</ul>' +
						'</span>' +
						'<i class="zmdi zmdi-close-circle option close" title="Close"></i>' +
					'</span>' +
				'</div>');
		
		var options = widget_header.children(".options");
		options.disableSelection();//set disableSelection for options
		
		//fixing issue of floating place-holders
		options.find(".sort").mousedown(function() {
			var widget = me.selected_template_widget;
			widget.data("data-float", widget.css("float"));
		});
	
		//set sort-up option
		options.find(".sort-up").on("click", function(event) {
			event.stopPropagation();
			
			var w = me.selected_template_widget;
			var p = w.parent();
			var contents = p.contents().filter(function() {
				return (this.nodeType === Node.TEXT_NODE && ("" + this.textContent).replace(/\s+/g, "") != "") || this.nodeType === Node.ELEMENT_NODE; //Do not include text nodes with empty or blank texts
			});
			var index = contents.index(w);
			
			//Do not add the this w.prev(), otherwise the system will ignore text nodes.
			//Do not add the this w.prev(".template-widget"), otherwise if there is an element which is not a template widget like a text node or a bold element, then the sort won't happen for that element.
			
			if (index - 1 >= 0) {
				w.insertBefore(contents[index - 1]);
				me.updateMenuLayer(w);
				updateWidgetHeaderOptions(w);
				updateWidgetHeaderPosition(w);
			}
			
			if (typeof me.options.on_template_widgets_layout_changed_func == "function")
				me.options.on_template_widgets_layout_changed_func(w);
		});
	
		//set sort-down option
		options.find(".sort-down").on("click", function(event) {
			event.stopPropagation();
			
			var w = me.selected_template_widget;
			var p = w.parent();
			var contents = p.contents().filter(function() {
				return (this.nodeType === Node.TEXT_NODE && ("" + this.textContent).replace(/\s+/g, "") != "") || this.nodeType === Node.ELEMENT_NODE;
			});
			var index = contents.index(w);
			
			//Do not add the this w.next(), otherwise the system will ignore text nodes.
			//Do not add the this w.next(".template-widget"), otherwise if there is an element which is not a template widget like a text node or a bold element, then the sort won't happen for that element.
			
			if (index + 1 < contents.length) {
				w.insertAfter(contents[index + 1]);
				me.updateMenuLayer(w);
				updateWidgetHeaderOptions(w);
				updateWidgetHeaderPosition(w);
			}
			
			if (typeof me.options.on_template_widgets_layout_changed_func == "function")
				me.options.on_template_widgets_layout_changed_func(w);
		});
		
		//set other options sub_menu position if outside of screen
		var other_options = options.find(".other-options");
		var sub_options = other_options.children(".sub-options");
		other_options.hover(
			function() { //handlerIn
				setSubHeaderInsideOfScreen(sub_options);
			},
			function() { //handlerOut
			}
		);
		
		//set prop option
		options.find(".props").on("click", function(event) {
			event.preventDefault();
	  		event.stopPropagation();
	  		
	  		//show menu settings panel
			ui.find(" > .options .show-settings").click();
		});
	
		//set toggle option
		options.find(".toggle").on("click", function(event) {
			event.preventDefault();
	  		event.stopPropagation();
	  		
	  		var icon = $(this).children("i");
			icon.toggleClass("zmdi-unfold-less zmdi-unfold-more");
			me.selected_template_widget.toggleClass("widget-children-hidden");
		});
		
		//set add option
		options.find(".add").on("click", function(event) {
			event.preventDefault();
	  		event.stopPropagation();
	  		
	  		addWidgetInsideOfWidget(me.selected_template_widget);
		});
		
		//set copy option
		options.find(".copy").on("click", function(event) {
			event.preventDefault();
	  		event.stopPropagation();
	  		
	  		me.copied_template_widget = me.selected_template_widget;
	  		me.copied_type_template_widget = "copy";
		});
		
		//set cut option
		options.find(".cut").on("click", function(event) {
			event.preventDefault();
	  		event.stopPropagation();
	  		
	  		me.copied_template_widget = me.selected_template_widget;
	  		me.copied_type_template_widget = "cut";
		});
		
		//set paste option
		options.find(".paste").on("click", function(event) {
			event.preventDefault();
	  		event.stopPropagation();
	  		
	  		pasteCopiedWidget(me.selected_template_widget);
	  		
	  		if (typeof me.options.on_template_widgets_layout_changed_func == "function")
				me.options.on_template_widgets_layout_changed_func(me.selected_template_widget);
		});
		
		//set html-contents option
		options.find(".html-contents").on("click", function(event) {
			event.preventDefault();
	  		event.stopPropagation();
	  		
	  		showWidgetSource(me.selected_template_widget, true);
		});
		
		//set html-source option
		options.find(".html-source").on("click", function(event) {
			event.preventDefault();
	  		event.stopPropagation();
	  		
	  		showWidgetSource(me.selected_template_widget, false);
		});
		
		//set link-contents option
		options.find(".link-contents").on("click", function(event) {
			event.preventDefault();
	  		event.stopPropagation();
	  		
	  		convertWidgetContentsToLink(me.selected_template_widget);
		});
		
		//set recreate-widget option
		options.find(".recreate-widget").on("click", function(event) {
			event.preventDefault();
	  		event.stopPropagation();
	  		
	  		me.recreateWidget(me.selected_template_widget);
		});
		
		//set delete option
		options.find(".delete").on("click", function(event) {
			event.preventDefault();
	  		event.stopPropagation();
	  		
	  		var widget = me.selected_template_widget;
			
			if (widget && confirm("Do you wish to delete this item?"))
				widget.fadeOut("slow", function() {
					me.deleteTemplateWidget(widget);
				});
		});
		
		//select parent option
		options.find(".select-parent").on("click", function(event) {
			event.preventDefault();
	  		event.stopPropagation();
	  		
	  		var p = getParentWidget(me.selected_template_widget);
	  		
			if (p) 
				p.trigger("click");
			else
				me.showError("Parent doesn't exist! You are already at the top level.");
		});
		
		//set close option
		options.find(".close").on("click", function(event) {
			event.preventDefault();
	  		event.stopPropagation();
	  		
	  		me.addNodeClass(me.selected_template_widget, "hidden-header");
	  		me.selected_template_widget.data("hidden-header-timeout", (new Date()).getTime());
	  		hideWidgetHeader();
		});
		
		main_elm.append(widget_header);
		
		//hides widget header and un-over widget
		widget_header.mouseleave(function(event) {
		  	event.preventDefault();
	  		event.stopPropagation();
	  		
	  		//template_widgets_droppable.find(".template-widget.widget-over").first().trigger("mouseleave");
		})
		.click(function(event) {
		  	event.preventDefault();
	  		event.stopPropagation();
	  		
	  		template_widgets_droppable.find(".template-widget.widget-over").first().trigger("click");
		});
		
		options.find(".move").draggable({
			//settings for the iframe droppable
			iframeFix:true,
			iframeScroll: true,
			scroll: true,
			scrollSensitivity: 20,

			//others settings
			appendTo: "body",
			cursor: "move",
			tolerance: "pointer",
			opacity: 0.8,
			refreshPositions: true,
			
			//handlers
			helper: function() {
				var clone = $('<div class="moveable-widget"></div>');
				clone.css({
					width: me.selected_template_widget.width() + "px",
					height: me.selected_template_widget.height() + "px",
					position: me.selected_template_widget.css("position")
				});
				
				if ($.isNumeric(ui.css("zIndex")))
					clone.css("zIndex", ui.css("zIndex") + 1);
				
				return clone;
			},
			start: function(event, ui_obj) {
				me.onWidgetDraggingStart(event, ui_obj.helper, me.selected_template_widget);
			},
			drag: function(event, ui_obj) {
				me.onWidgetDraggingDrag(event, ui_obj.helper, me.selected_template_widget);
			},
			stop: function(event, ui_obj) {
				me.onWidgetDraggingStop(event, ui_obj.helper, me.selected_template_widget);
				
				//prepare offset if a parent has position absolute or fixed
				me.updateWidgetPositionBasedInParent(event, ui_obj.helper, me.selected_template_widget, ui_obj);
				
				ui_obj.helper.remove();
				me.updateMenuLayer(me.selected_template_widget);
				openWidgetHeader(me.selected_template_widget); //show widget header again
				
				if (typeof me.options.on_template_widgets_layout_changed_func == "function")
					me.options.on_template_widgets_layout_changed_func(me.selected_template_widget);
			},
		});
		
		options.find(".move-position").draggable({
			//settings for the iframe droppable
			iframeFix:true,
			iframeScroll: true,
			scroll: true,
			scrollSensitivity: 20,

			//others settings
			appendTo: "body",
			cursor: "move",
			tolerance: "pointer",
			opacity: 1,
			refreshPositions: true,
			grid: $.isArray(me.options.moving_tolerance_grid) ? me.options.moving_tolerance_grid : [10, 10],
			
			//handlers
			helper: function() {
				var clone = $('<div class="moveable-widget"></div>');
				clone.css({
					width: me.selected_template_widget.width() + "px",
					height: me.selected_template_widget.height() + "px",
					position: me.selected_template_widget.css("position")
				});
				
				if ($.isNumeric(ui.css("zIndex")))
					clone.css("zIndex", ui.css("zIndex") + 1);
				
				return clone;
			},
			start: function(event, ui_obj) {
				/*var handle = $(this);
				var handle_offset = handle.offset();
				var iframe_offset = template_widgets_iframe.offset();
				var widget_offset = me.selected_template_widget.offset();
				
				handle_offset.top = handle_offset.top - iframe_offset.top;
				handle_offset.left = handle_offset.left - iframe_offset.left;
				
				var cursor_pos = {
					top: handle_offset.top - widget_offset.top, 
					left: handle_offset.left - widget_offset.left
				};
				handle.draggable("option", "cursorAt", cursor_pos);*/
				
				var position = me.selected_template_widget.css("position");
				
				if (!position || position == "static") {
					//update position of widget and dimensions of helper
					var old_width = me.selected_template_widget.width();
					var old_height = me.selected_template_widget.height();
					
					me.selected_template_widget.css("position", "absolute");
					
					var new_width = me.selected_template_widget.width();
					var new_height = me.selected_template_widget.height();
					
					if (old_width != new_width || old_height != new_height)
						ui_obj.helper.css({
							width: new_width + "px",
							height: new_height + "px",
						});
					
					if (me.isMenuSettingsVisible()) {
						resetMenuSettings(); //reset is very important otherwise the widget starts acumulating repeated classes
						loadMenuSettings(me.selected_template_widget); //update menuSettings fields...
					}
				}
				
				//this.visibility_bkp = me.selected_template_widget[0].style.visibility;
				//me.selected_template_widget.css("visibility", "hidden");
				template_widgets_droppable.addClass("dropping");
				
				hideWidgetHeader();
				hideDroppableHeader();
			},
			stop: function(event, ui_obj) {
				var iframe_pos = template_widgets_iframe.offset(); //add offset of iframe
				var pos = {top: ui_obj.position.top - iframe_pos.top, left: ui_obj.position.left - iframe_pos.left};
				//if ((me.selected_template_widget.css("position") == "fixed"))
				//	pos = {top: ui_obj.offset.top, left: ui_obj.offset.left};
				
				var position = me.selected_template_widget.css("position");
				
				if (position != "fixed") {
					var p = position == "sticky" ? me.selected_template_widget.parent()[0] : me.selected_template_widget[0].offsetParent; //not sure about the sticky part
					var o = $(p).offset();
					pos.top -= o.top;
					pos.left -= o.left;
				}
				//console.log(pos);
				
				pos.top = parseInt(pos.top);
				pos.left = parseInt(pos.left);
				
				me.selected_template_widget.css(pos);
				//me.selected_template_widget.css("visibility", this.visibility_bkp);
				ui_obj.helper.remove();
				
				resetMenuSettings(); //reset is very important otherwise the widget starts acumulating repeated classes
				loadMenuSettings(me.selected_template_widget); //update menuSettings fields...
				openWidgetHeader(me.selected_template_widget); //show widget header again
				
				template_widgets_droppable.removeClass("dropping");
				
				if (typeof me.options.on_template_widgets_layout_changed_func == "function")
					me.options.on_template_widgets_layout_changed_func(me.selected_template_widget);
				
				/*
				//get droppable element
				var io = template_widgets_iframe.offset();
				var x = event.clientX - io.left;
				var y = event.clientY - io.top;
				
				dragged_elm.hide();
				var elm_from_point = selected_template_widgets_droppable[0].ownerDocument.elementFromPoint(x, y);
				dragged_elm.show();
				*/
			},
		});
		
		options.find(".sort").draggable({
			//settings for the iframe droppable
			iframeFix:true,
			iframeScroll: true,
			scroll: true,
			scrollSensitivity: 20,

			//others settings
			appendTo: "body",
			cursor: "move",
			tolerance: "pointer",
			opacity: 0.4,
			refreshPositions: true,
			grid: [5, 5],
			
			//handlers
			helper: function() {
				return onWidgetSortingHelper(me.selected_template_widget);
			},
			start: function(event, ui_obj) {
				onWidgetSortingStart(event, ui_obj.helper, me.selected_template_widget);
			},
			drag: function(event, ui_obj) {
				onWidgetSortingDrag(event, ui_obj.helper, me.selected_template_widget);
			},
			stop: function(event, ui_obj) {
				onWidgetSortingStop(event, ui_obj.helper, me.selected_template_widget);
				
				if (typeof me.options.on_template_widgets_layout_changed_func == "function")
					me.options.on_template_widgets_layout_changed_func(me.selected_template_widget);
			},
		  });
	}
	
	function setSubHeaderInsideOfScreen(sub_header_elm) {
		//reset positioning
		sub_header_elm.css({
			"margin-top": "", 
			"margin-left": "", 
		});
		
		//if sub_header_elm are outside of screen, add correspondent class
		var ooml = 0; //ooml is the margin left that we need to remove for safety so the sub_header_elm is over the icon that shows the sub_header_elm.
		var oomt = 0; //oomt is the margin top that we need to remove for safety so the sub_header_elm is over the icon that shows the sub_header_elm.
		var ooo = sub_header_elm.offset();
		var oow = sub_header_elm.outerWidth();
		var ooh = sub_header_elm.outerHeight();
		var oor = ooo.left + oow;
		var oob = ooo.top + ooh;
		
		var tsw = 0;
		var tsh = me.TextSelection.isMenuShown() ? me.TextSelection.getMenuHeight() : 0;
		
		var io = template_widgets_iframe.offset();
		var iw = template_widgets_iframe.outerWidth();
		var ih = template_widgets_iframe.outerHeight();
		var it = io.top + tsh;
		var il = io.left + tsw;
		var ir = il + iw;
		var ib = it + ih;
		
		if (ir < oor) 
			sub_header_elm.css({
				"margin-left": "-" + (oor - ir - ooml) + "px", 
			});
		
		if (ib < oob)
			sub_header_elm.css({
				"margin-top": "-" + (oob - ib - oomt) + "px",
			});
	}
	
	function showWidgetSource(widget, is_inner_html) {
  		if (!widget) 
  			me.showError("No widget selected!");
  		else {
	  		var source = me.getCleanedHtmlContents(is_inner_html ? widget.contents() : widget);
	  		
	  		var popup = template_widgets.children("." + me.options.template_widget_source_popup_class);
	  		
	  		if (!popup[0]) {
	  			var html = '<div class="' + me.options.template_widget_source_popup_class + '">'
		  					+ '<div class="title">Html Source</div>'
	  						+ '<a class="pretty_print" href="javascript:void(0);">Html Pretty Print</a>'
		  					+ '<i class="zmdi zmdi-hc-lg zmdi-close close" title="Close and update"></i>'
		  					+ '<textarea></textarea>'
	  					+ '</div>';
	  			popup = $(html);
	  			template_widgets.append(popup);
	  			
	  			createCodeEditor(popup.children("textarea")[0]);
	  		}
	  		
	  		popup.children(".pretty_print").unbind("click").bind("click", function() {
	  			var editor = popup.data("editor");
				var html = editor ? editor.getValue() : popup.children("textarea").val();
				html = MyHtmlBeautify.beautify(html);
				
				if (editor) {
					editor.setValue(html, -1);
					editor.focus();
				}
				else
					popup.children("textarea").val(html).focus();
	  		});
	  		
  			popup.children(".close").unbind("click").bind("click", function() {
  				popup.hide();
  				
  				var editor = popup.data("editor");
				var new_source = editor ? editor.getValue() : popup.children("textarea").val();
  				new_source = new_source ? new_source.replace(/^\s/g, "").replace(/\s$/g, "") : ""; //trim html
  				
  				if (new_source != source) {
  					if (new_source) {
  						//add '<div>' and then get contents() is very important, so we can get the text nodes also in the root source.
  						var tmp_widget = $('<div>' + new_source + '</div>');
		  				var new_contents = tmp_widget.contents();
		  				var new_children = tmp_widget.children();
		  				
		  				if (is_inner_html) {
		  					widget.html("");
		  					widget.append(new_contents);
		  					
		  					//convert new html element to widget
			  				me.convertHtmlElementToWidget(new_contents);
			  				me.refreshElementMenuLayer(new_children); //pass only children, without text nodes
							
							if (typeof me.options.on_template_widgets_layout_changed_func == "function")
								me.options.on_template_widgets_layout_changed_func(widget);
		  				}
		  				else {
			  				widget.after(new_contents);
		  				
			  				//must remove widget before we convert it
			  				me.deleteTemplateWidget(widget);
		  				
		  					//convert new html element to widget
			  				me.convertHtmlElementToWidget(new_contents);
			  				me.refreshElementMenuLayer(new_children); //pass only children, without text nodes
			  				
			  				try {
			  					new_children.first().trigger("click"); //click only in the first node, just in case.
			  				}
			  				catch(e) {
			  					if (console && console.log) 
									console.log(e);
			  				}
		  				}
		  			}
		  			else if (is_inner_html) {
		  				widget.html("");
		  				me.refreshElementMenuLayer(widget);
						
						if (typeof me.options.on_template_widgets_layout_changed_func == "function")
							me.options.on_template_widgets_layout_changed_func(widget);
		  			}
		  			else
		  				me.deleteTemplateWidget(widget);
	  			}
  			});
	  		
	  		var editor = popup.data("editor");
		
			if (editor) {
				editor.setValue(source, -1);
				editor.focus();
			}
			else
				popup.children("textarea").val(source).focus();
	  		
	  		popup.show();
	  		
	  		if (editor)
	  			editor.resize();
	  	}
	}
	
	function isWidgetSourceShown() {
  		var popup = template_widgets.children("." + me.options.template_widget_source_popup_class);
  		
  		return popup[0] && popup.css("display") == "block";
  	}
  	
  	function resizeWidgetSource() {
  		var popup = template_widgets.children("." + me.options.template_widget_source_popup_class);
  		
  		if (popup[0]) {
  			var editor = popup.data("editor");
  			
  			if (editor)
	  			editor.resize();
  		}
  	}
  	
  	function convertWidgetContentsToLink(widget) {
  		if (!widget) 
  			me.showError("No widget selected!");
  		else if (confirm("You are about to convert this widget's contents into a link. Do you wish to proceed?")) {
	  		var contents = widget.contents();
	  		var link_widget = $('<a></a>');
	  		widget.append(link_widget);
	  		
			//convert new html element to widget
			me.convertHtmlElementToWidget(link_widget);
	  		
	  		//move contents to link widget
	  		link_widget.append(contents);
			
			me.refreshElementMenuLayer(widget);
			
			if (typeof me.options.on_template_widgets_layout_changed_func == "function")
				me.options.on_template_widgets_layout_changed_func(widget);
	  	}
  	}
	
	function pasteCopiedWidget(droppable) {
		if (!me.copied_template_widget) {
			var paste_func = function(text) {
				if (text) {
					if (!droppable) 
						me.showError("No droppable to paste copied text!");
			  		else {
			  			//console.log(text);
						me.parseHtmlSource(text, droppable);
					}
				}
				else
					me.showError("No widget for pasting! Please copy a widget first!");
			};
			
			try {
				navigator.clipboard.readText().then(
					function(text) {
						paste_func(text);
					}, 
					function(err) {
						me.showError("No widget for pasting! Please copy a widget first!");
						
						if (console && console.log)
							console.log(err);
					}
				);
			}
			catch (e) {
				try {
					var text = document.execCommand('paste');
					paste_func(text);
				}
				catch (e) {
					if (console && console.log)
						console.log(e);
				}
			}
		}
		else {
  			if (!droppable) 
	  			me.showError("No droppable to paste copied widget!");
	  		else if (!me.hasNodeClass(droppable, "droppable")) 
	  			me.showError("Cannot paste copied widget into another widget that is not droppable!");
	  		else {
	  			if (me.copied_type_template_widget == "cut") {
	  				var p = getParentWidget(me.copied_template_widget);
	  				droppable.append(me.copied_template_widget);
	  				me.refreshElementMenuLayer(p);
	  				me.copied_template_widget = null;
	  			}
	  			else {
	  				var html_element = $( me.getCleanedHtmlContents(me.copied_template_widget) );
		  			var widget_tag = me.copied_template_widget.data("data-tag");
		  			
		  			var widget = me.createTemplateWidgetFromMenuWidgetTag(widget_tag, droppable, html_element[0]);
		  			
		  			me.recreateWidget(widget); //This is very important otherwise the children doesn't get converted to widgets. Even if we execute the recreateWidgetChildren method, it doesn't work. Leave this code here.
		  		}
		  		
				me.refreshElementMenuLayer(droppable);
	  		}
  		}
	}
	
	function onWidgetSortingHelper(widget) {
		var clone = widget.clone();
		
		if ($.isNumeric(ui.css("zIndex")))
			clone.css("zIndex", ui.css("zIndex") + 1);
		
		return clone;
	}
	
	function onWidgetSortingStart(event, dragged_elm, widget) {
		template_widgets_droppable.addClass("dropping");
		
		hideWidgetHeader();
		hideDroppableHeader();
		
		me.addNodeClass(widget.parent(), "highlight");
	}
	
	function onWidgetSortingDrag(event, dragged_elm, widget) {
		var elm_from_point = me.getWidgetDroppedElement(event, dragged_elm);
		var p = widget.parent();
		
		me.removeNodeClass(p, "highlight-parent-left highlight-parent-right highlight-parent-top highlight-parent-bottom");
		me.removeNodeClass(p.children(".highlight-left, .highlight-right, .highlight-top, .highlight-bottom"), "highlight-left highlight-right highlight-top highlight-bottom");
		
		if (elm_from_point) {
			elm_from_point = $(elm_from_point);
			var brother = elm_from_point.parent().is(p) ? elm_from_point : elm_from_point.parentsUntil(p).last();
			
			if (brother.parent().is(p) && !brother.is(widget))
				me.addNodeClass(brother, "highlight-" + me.getWidgetDroppedOverPosition(event, dragged_elm, brother) );
			else if (elm_from_point.is(p)) 
				me.addNodeClass(p, "highlight-parent-" + me.getWidgetDroppedOverPosition(event, dragged_elm, p) );
		}
	}
	
	function onWidgetSortingStop(event, dragged_elm, widget) {
		var elm_from_point = me.getWidgetDroppedElement(event, dragged_elm);
		var p = widget.parent();
		
		if (elm_from_point) {
			elm_from_point = $(elm_from_point);
			var brother = elm_from_point.parent().is(p) ? elm_from_point : elm_from_point.parentsUntil(p).last();
			
			if (brother.parent().is(p) && !brother.is(widget)) {
				var c = me.getWidgetDroppedOverPosition(event, dragged_elm, brother);
				
				if (c == "top" || c == "left")
					brother.before(widget);
				else
					brother.after(widget);
			}
			else if (elm_from_point.is(p)) {
				var c = me.getWidgetDroppedOverPosition(event, dragged_elm, p);
				
				if (c == "top" || c == "left")
					p.prepend(widget);
				else
					p.append(widget);
			}
		}
		
		dragged_elm.remove();
		
		me.removeNodeClass(p, "highlight highlight-parent-left highlight-parent-right highlight-parent-top highlight-parent-bottom");
		me.removeNodeClass(p.children(".highlight-left, .highlight-right, .highlight-top, .highlight-bottom"), "highlight-left highlight-right highlight-top highlight-bottom");

		template_widgets_droppable.removeClass("dropping");
		
		me.updateMenuLayer(widget);
	}
	
	function addWidgetInsideOfWidget(widget_parent) {
		if (widget_parent) {
			//prepare popup html
			var popup_elm = template_widgets.children(".add-widget-popup");
			
			if (popup_elm[0])
				popup_elm.remove();
			
			popup_elm = $('<div class="add-widget-popup">'
					+ '	<select><option value="" disabled selected>Select Widget to add</option></select>'
					+ '	<button><i class="zmdi zmdi-hc-lg zmdi-plus"></i> Add Widget</button>'
					+ '	<i class="zmdi zmdi-hc-lg zmdi-close close"></i>'
					+ '</div>');
			
			popup_elm.hide();
			template_widgets.append(popup_elm);
			
			popup_elm.find(".close").on("click", function() {
				popup_elm.hide("slow");
			});
			
			var select = popup_elm.children("select");
			
			menu_widgets.find(".group").each(function(idx, group) {
				group = $(group);
				var title = group.children(".group-title").clone();
				title.children("i").remove();
				title = title.text();
				
				var html = '<optgroup label="' + title + '">';
				
				$.each(group.find(".menu-widget"), function(idx, menu_widget) {
					menu_widget = $(menu_widget);
					var menu_widget_title = menu_widget.children("span").first().text();
					menu_widget_title = menu_widget_title ? menu_widget_title : menu_widget.attr("data-tag");
					html += '<option value="' + menu_widget.attr("data-tag") + '">' + menu_widget_title + '</option>';
				});
				
				html += '</optgroup>';
				
				select.append(html);
			});
			
			popup_elm.children("button").on("click", function(e2) {
				var widget_tag = select.val();
				
				if (widget_tag) {
					var widget = me.createTemplateWidgetFromMenuWidgetTag(widget_tag, widget_parent, null);
					popup_elm.hide("slow");
					
					if (typeof me.options.on_template_widgets_layout_changed_func == "function")
						me.options.on_template_widgets_layout_changed_func(widget);
				}
			});
			
			//show popup
			popup_elm.show("slow");
		}
	}
	
	function openWidgetHeader(widget) {
		var menu_widget = me.getTemplateMenuWidget(widget);
		
		if (me.hasNodeClass(widget, "hidden-header")) {
			var timeout = widget.data("hidden-header-timeout");
			var is_timeout_expired = !$.isNumeric(timeout) || timeout < (new Date()).getTime() - 5000;
			
			if (is_timeout_expired)
				me.removeNodeClass(widget, "hidden-header");
		} 
		else {
			var widget_label = widget.data("data-label");
			var widget_tag = widget.data("data-tag");
			
			var header_class = " " + me.options.widget_header_class + "-" + widget_tag + (menu_widget.attr("data-template-header-class") ? " " + menu_widget.attr("data-template-header-class") : "");
			widget_header.attr("class", me.options.widget_header_class + header_class); //overwrite the classes from the previous widget
			
			widget_header.children("label").html(widget_label);
			
			if (me.hasNodeClass(widget, "widget-active")) {
				updateWidgetHeaderOptions(widget);
				widget_header.children(".options").show();
			}
			else
				widget_header.children(".options").hide();
			
			//set widget header in the widget position
			updateWidgetHeaderPosition(widget);
			
			//show widget header
			widget_header.show();
		}
		
		//call on-open-widget-header function
		var func = menu_widget.attr("data-on-open-widget-header-func");
		if (func && eval('typeof ' + func + ' == "function"'))
			eval(func + '(widget, widget_header);');
		
		if (typeof me.options.on_open_widget_header_func == "function")
			me.options.on_open_widget_header_func(widget, widget_header);
	}
	
	function updateWidgetHeaderOptions(widget) {
		var menu_widget = me.getTemplateMenuWidget(widget);
		var p = widget.parent();
		var contents = p.contents().filter(function() {
			return this.nodeType === Node.TEXT_NODE || this.nodeType === Node.ELEMENT_NODE;
		});
		var only_child = contents.length == 1;
		var position = ("" + widget.css("position")).toLowerCase();
		var widget_index = contents.index(widget);
		var is_droppable = me.hasNodeClass(widget, "droppable");
		var has_properties = true;//menu_widget.children(".properties").children().length > 0; //even if there are no properties, it should show the props menu, bc there are always default props.
		var has_children = widget.children().length > 0;
		var options = widget_header.children(".options");
		options.find(".option").show();
		
		if (!has_properties)
			options.children(".props").hide();
		
		if (!has_children)
			options.children(".toggle").hide();
		
		if (widget_index == 0) //is first child
			options.children(".sort-up").hide();
		
		if (widget_index == contents.length - 1) //is last child
			options.children(".sort-down").hide();
		
		if (only_child)
			options.children(".sort, .sort-up, .sort-down").hide();
		
		/*if (!position || position == "static")
			options.children(".move-position").hide();*/
		
		if (is_droppable) 
			options.children(".add").show();
		else
			options.children(".add").hide();
		
		if (is_droppable && me.copied_template_widget)
			options.children(".paste").show();
		else
			options.children(".paste").hide();
	}
	
	function updateWidgetHeaderPosition(widget) {
		var pos = getElementHeaderPosition(widget);
		
		if (pos.top < 20)
			widget_header.addClass(me.options.widget_header_class + "-top");
		else
			widget_header.removeClass(me.options.widget_header_class + "-top");
		
		//show widget header in the widget position
		widget_header.css({
			//top: pos.top + "px",
			bottom: "calc(100% - " + pos.top + "px)",
			left: pos.left + "px",
			width: "" //remove width from style
		});
		
		//show widget header in multiple lines if widget header is larger than widget width.
		var w = widget_header.outerWidth();
		var ww = widget.outerWidth();
		
		if (ww + 20 < w && ww > 0) //20 just to have some gap
			widget_header.css("width", ww + "px");
		
		//needs to happen after the widget_header.css, so it can get the new height. This avoids that the widget-header appears hidden below and in the top of the canvas editor.
		var h = widget_header.outerHeight();
		var top = pos.top - h;
		
		if (top < 0) {
			widget_header.addClass(me.options.widget_header_class + "-top");
			widget_header.css({
				bottom: "calc(100% - " + (pos.top + Math.abs(top)) + "px)",
			});
		}
	}
	
	function hideWidgetHeader(widget) {
		widget_header.hide();
		
		//call on-close-widget-header function
		if (!widget || !widget[0])
			widget = me.selected_template_widget;
		
		if (widget) {
			var menu_widget = me.getTemplateMenuWidget(widget);
			var func = menu_widget.attr("data-on-close-widget-header-func");
			
			if (func && eval('typeof ' + func + ' == "function"'))
				eval(func + '(widget, widget_header);');
			
			if (typeof me.options.on_close_widget_header_func == "function")
				me.options.on_close_widget_header_func(widget, widget_header);
		}
	}
	
	/* DROPPABLE-HEADER METHODS */
	
	function initDroppableHeader(main_elm) {
		droppable_header = $('<div class="' + me.options.droppable_header_class + '">' +
				'<label></label>' +
				'<span class="options">' +
					'<i class="zmdi zmdi-swap option sort" title="Sort"></i>' +
					'<i class="zmdi zmdi-long-arrow-tab zmdi-hc-rotate-180 option sort-left" title="Sort Left"></i>' +
					'<i class="zmdi zmdi-long-arrow-tab option sort-right" title="Sort Right"></i>' +
					'<span class="option other-options" title="Other Options">' +
						'<i class="zmdi zmdi zmdi-more" title="Other Options"></i>' +
						'<ul class="sub-options">' +
							'<li class="option html-contents" title="Edit Contents"><i class="zmdi zmdi-edit"></i>Edit Contents</li>' + 
							'<li class="option toggle" title="Toggle"><i class="zmdi zmdi-unfold-less"></i>Toggle</li>' +
							'<li class="option add" title="Add Widget"><i class="zmdi zmdi-plus-circle-o"></i>Add Widget</li>' +
							'<li class="option paste" title="Paste Widget"><i class="zmdi zmdi-paste"></i>Paste Widget</li>' +
							'<li class="option html-source" title="Widget Html Source"><i class="zmdi zmdi-code"></i>Widget Html Source</li>' +
							'<li class="option link-contents" title="Convert Contents to Link"><i class="zmdi zmdi-link"></i>Convert Contents to Link</li>' +
						'</ul>' +
					'</span>' +
					'<i class="zmdi zmdi-close-circle option close" title="Close"></i>' +
				'</span>' +
			'</div>');
		
		var options = droppable_header.find(".options");
		options.disableSelection();//set disableSelection for options
		
		//fixing issue of floating place-holders
		options.find(".sort").mousedown(function() {
			var droppable = me.selected_template_droppable;
			droppable.data("data-float", droppable.css("float"));
		});
		
		//set sort-left option
		options.find(".sort-left").on("click", function(event) {
			event.stopPropagation();
			
			var w = me.selected_template_droppable;
			var p = w.parent();
			var contents = p.contents().filter(function() {
				return (this.nodeType === Node.TEXT_NODE && ("" + this.textContent).replace(/\s+/g, "") != "") || this.nodeType === Node.ELEMENT_NODE;
			});
			var index = contents.index(w);
			
			//Do not add the this w.prev(), otherwise the system will ignore text nodes.
			//Do not add the this w.prev(".droppable"), otherwise if there is an element which is not a droppable like a text node or a bold element, then the sort won't happen for that element.
			
			if (index - 1 >= 0) {
				w.insertBefore(contents[index - 1]);
				me.updateMenuLayer(w);
				updateDroppableHeaderOptions(w);
				updateDroppableHeaderPosition(w);
				
				if (typeof me.options.on_template_widgets_layout_changed_func == "function")
					me.options.on_template_widgets_layout_changed_func(w);
			}
		});
		
		//set sort-right option
		options.find(".sort-right").on("click", function(event) {
			event.stopPropagation();
			
			var w = me.selected_template_droppable;
			var p = w.parent();
			var contents = p.contents().filter(function() {
				return (this.nodeType === Node.TEXT_NODE && ("" + this.textContent).replace(/\s+/g, "") != "") || this.nodeType === Node.ELEMENT_NODE;
			});
			var index = contents.index(w);
			
			//Do not add the this w.prev(), otherwise the system will ignore text nodes.
			//Do not add the this w.prev(".droppable"), otherwise if there is an element which is not a droppable like a text node or a bold element, then the sort won't happen for that element.
			
			if (index + 1 < contents.length) {
				w.insertAfter(contents[index + 1]);
				me.updateMenuLayer(w);
				updateDroppableHeaderOptions(w);
				updateDroppableHeaderPosition(w);
				
				if (typeof me.options.on_template_widgets_layout_changed_func == "function")
					me.options.on_template_widgets_layout_changed_func(w);
			}
		});
		
		//set other options sub_menu position if outside of screen
		var other_options = options.find(".other-options");
		var sub_options = other_options.children(".sub-options");
		other_options.hover(
			function() { //handlerIn
				setSubHeaderInsideOfScreen(sub_options);
			},
			function() { //handlerOut
			}
		);
		
		//set toggle
		options.find(".toggle").on("click", function() {
			event.preventDefault();
	  		event.stopPropagation();
	  		
			var icon = $(this).children("i");
			icon.toggleClass("zmdi-unfold-less zmdi-unfold-more");
			me.selected_template_droppable.toggleClass("widget-children-hidden");
		});
		
		//set add option
		options.find(".add").on("click", function(event) {
			event.preventDefault();
	  		event.stopPropagation();
	  		
	  		addWidgetInsideOfWidget(me.selected_template_droppable);
		});
		
		//set paste option
		options.find(".paste").on("click", function(event) {
			event.preventDefault();
	  		event.stopPropagation();
	  		
	  		pasteCopiedWidget(me.selected_template_droppable);
	  		
			if (typeof me.options.on_template_widgets_layout_changed_func == "function")
				me.options.on_template_widgets_layout_changed_func(me.selected_template_droppable);
		});
		
		//set html-contents option
		options.find(".html-contents").on("click", function(event) {
			event.preventDefault();
	  		event.stopPropagation();
	  		
	  		showWidgetSource(me.selected_template_droppable, true);
		});
		
		//set html-source option
		options.find(".html-source").on("click", function(event) {
			event.preventDefault();
	  		event.stopPropagation();
	  		
	  		showWidgetSource(me.selected_template_droppable, false);
		});
		
		//set link-contents option
		options.find(".link-contents").on("click", function(event) {
			event.preventDefault();
	  		event.stopPropagation();
	  		
	  		convertWidgetContentsToLink(me.selected_template_droppable);
		});
		
		//set close option
		options.find(".close").on("click", function() {
			event.preventDefault();
	  		event.stopPropagation();
	  		
	  		me.addNodeClass(me.selected_template_droppable, "hidden-header");
	  		me.selected_template_droppable.data("hidden-header-timeout", (new Date()).getTime());
	  		hideDroppableHeader();
		});
		
		main_elm.append(droppable_header);
		
		//hides widget header and un-over widget
		droppable_header.mouseleave(function(event) {
		  	event.preventDefault();
	  		event.stopPropagation();
	  		
	  		me.selected_template_droppable.trigger("mouseleave");
		})
		.click(function(event) {
		  	event.preventDefault();
	  		event.stopPropagation();
	  		
	  		me.selected_template_droppable.trigger("click");
		});
		
		options.find(".sort").draggable({
			//settings for the iframe droppable
			iframeFix:true,
		     iframeScroll: true,
		     scroll: true,
		     scrollSensitivity: 20,
		     
		     //others settings
		     appendTo: "body",
			cursor: "move",
               tolerance: "pointer",
			opacity: 0.4,
			refreshPositions: true,
			grid: [5, 5],
			axis: "x",
			
			//handlers
			helper: function() {
				return onWidgetSortingHelper(me.selected_template_droppable);
			},
			start: function(event, ui_obj) {
				onWidgetSortingStart(event, ui_obj.helper, me.selected_template_droppable);
			},
			drag: function(event, ui_obj) {
				onWidgetSortingDrag(event, ui_obj.helper, me.selected_template_droppable);
			},
			stop: function(event, ui_obj) {
				onWidgetSortingStop(event, ui_obj.helper, me.selected_template_droppable);
		  		
				if (typeof me.options.on_template_widgets_layout_changed_func == "function")
					me.options.on_template_widgets_layout_changed_func(me.selected_template_droppable);
			},
		  });
	}
	
	function openDroppableHeader(droppable) {
		me.selected_template_droppable = droppable;
		
		var widget = me.hasNodeClass(droppable, "template-widget") ? droppable : droppable.parentsUntil(template_widgets_droppable, ".template-widget").first();
		var menu_widget = me.getTemplateMenuWidget(widget);
		
		if (me.hasNodeClass(droppable, "hidden-header")) {
			var timeout = droppable.data("hidden-header-timeout");
			var is_timeout_expired = !$.isNumeric(timeout) || timeout < (new Date()).getTime() - 5000;
			
			if (is_timeout_expired)
				me.removeNodeClass(droppable, "hidden-header");
		}
		else {
			var type = droppable.data("data-label");
			type = type ? type : "Column";
			
			var widget_tag = widget.data("data-tag");
			
			var header_class = " " + me.options.droppable_header_class + "-" + widget_tag + (menu_widget.attr("data-template-header-class") ? " " + menu_widget.attr("data-template-header-class") : "");
			droppable_header.attr("class", me.options.droppable_header_class + header_class); //overwrite the classes from the previous widget
			
			var w = droppable[0].getBoundingClientRect ? droppable[0].getBoundingClientRect().width : droppable.width();
			droppable_header.css("min-width", w + "px");
			
			droppable_header.children("label").html(type);
			
			if (me.hasNodeClass(droppable, "droppable-active")) {
				updateDroppableHeaderOptions(droppable);
				droppable_header.children(".options").show();
			}
			else
				droppable_header.children(".options").hide();
			
			//set droppable header in the widget position
			updateDroppableHeaderPosition(droppable);
			
			//show droppable
			droppable_header.show();
		}
		
		//call on-open-droppable-header function
		var func = menu_widget.attr("data-on-open-droppable-header-func");
		if (func && eval('typeof ' + func + ' == "function"'))
			eval(func + '(droppable, droppable_header);');
		
		if (typeof me.options.on_open_droppable_header_func == "function")
			me.options.on_open_droppable_header_func(droppable, droppable_header);
	}
	
	function updateDroppableHeaderOptions(droppable) {
		var options = droppable_header.children(".options");
		options.find(".option").show();
		
		var p = droppable.parent();
		var contents = p.contents().filter(function() {
			return this.nodeType === Node.TEXT_NODE || this.nodeType === Node.ELEMENT_NODE;
		});
		var only_child = contents.length == 1;
		var droppable_index = contents.index(droppable);
		
		if (droppable_index == 0) //is first child
			options.children(".sort-left").hide();
		
		if (droppable_index == contents.length - 1) //is last child
			options.children(".sort-right").hide();
		
		if (only_child)
			options.children(".sort, .sort-left, .sort-right").hide();
		
		if (me.copied_template_widget)
			options.children(".paste").show();
		else
			options.children(".paste").hide();
	}
	
	function updateDroppableHeaderPosition(droppable) {
		var pos = getElementHeaderPosition(droppable);
		
		if (pos.top < 20)
			droppable_header.css({
				top: pos.top + "px",
				bottom: "",
				left: pos.left + "px"
			});
		else {
			droppable_header.css({
				//top: pos.top + "px",
				top: "",
				bottom: "calc(100% - " + pos.top + "px)",
				left: pos.left + "px"
			});
			
			//needs to happen after the droppable_header.css, so it can get the new height. This avoids that the droppable-header appears hidden below and in the top of the canvas editor.
			var h = droppable_header.outerHeight();
			var top = pos.top - h;
			
			if (top < 0)
				droppable_header.css({
					bottom: "calc(100% - " + (pos.top + Math.abs(top)) + "px)",
				});
		}
	}
	
	function hideDroppableHeader(droppable) {
		droppable_header.hide();
		
		//call on-close-droppable-header function
		if (!droppable || !droppable[0])
			droppable = me.selected_template_droppable;
		
		if (droppable) {
			var widget = me.hasNodeClass(droppable, "template-widget") ? droppable : droppable.parentsUntil(template_widgets_droppable, ".template-widget").first();
			var menu_widget = me.getTemplateMenuWidget(widget);
			var func = menu_widget.attr("data-on-close-droppable-header-func");
			
			if (func && eval('typeof ' + func + ' == "function"'))
				eval(func + '(droppable, droppable_header);');
			
			if (typeof me.options.on_close_droppable_header_func == "function")
				me.options.on_close_droppable_header_func(droppable, droppable_header);
		}
	}
	
	/* MAIN MENU METHODS */
	
	function setMainOptions(main_elm) {
		main_elm.prepend(
		  '<div class="options">' +
			'<div class="options-left">' +
			  '<select class="option show-option-panel">' +
			  	'<option value="show-layout-options">Canvas Toggles</option>' +
			  	'<option value="show-widgets">Widgets</option>' +
			  	'<option value="show-layers">Layers</option>' +
			  '</select>' +
			  '<i class="zmdi zmdi-settings option show-layout-options" title="Show Canvas Toggles"></i>' +
			  '<i class="zmdi zmdi-widgets option show-widgets option-active" title="Show Widgets"></i>' +
			  '<i class="zmdi zmdi-view-headline option show-layers" title="Show Layers"></i>' +
			  '<i class="zmdi zmdi-edit option show-settings option-disabled" title="Show Widget Settings"></i>' +
		  	'</div>' +
			'<div class="options-center">' +
			  '<i class="zmdi zmdi-tv option layout-in-desktop option-active" title="Show in Desktop"></i>' +
			  '<i class="zmdi zmdi-smartphone option layout-in-mobile" title="Show in Mobile"></i>' +
			  '<input class="option option-screen-width" title="Screen Width" maxLength="4" />' +
			  '<span class="option option-px">px</span>' +
			  '<span class="option option-x"> x </span>' +
			  '<input class="option option-screen-height" title="Screen Height" maxLength="4" />' +
			  '<span class="option option-px">px</span>' +
			  '<input type="checkbox" class="option option-fit-to-screen" title="Fit dimensions to screen" />' +
		  	'</div>' +
			'<div class="options-right">' +
			  '<i class="zmdi zmdi-code-setting option show-full-source" title="Toggle Full Source"></i>' +
			  '<i class="zmdi zmdi-fullscreen option full-screen" title="Full Screen"></i>' +
			  '<span class="option sub-options toggles">' +
				'<i class="zmdi zmdi-border-clear"></i>' +
				'<i class="zmdi zmdi-chevron-down caret-down"></i>' +
				'<ul>' +
				  '<li class="toggle-option toggle-background" title="Toggle background"><input type="checkbox" /> <span>Show background</span></li>' +
				  '<li class="toggle-option toggle-borders" title="Toggle borders"><input type="checkbox" /> <span>Show borders</span></li>' +
				  '<li class="toggle-option toggle-comments"><input type="checkbox" /> <span>Show Comments</span></li>' +
				  '<li class="toggle-option toggle-php"><input type="checkbox" /> <span>Show Server Code</span></li>' +
				  '<li class="toggle-option toggle-ptl ptl-shown"><input type="checkbox" checked /> <span>Show PTL Code</span></li>' +
				  '<li class="toggle-option toggle-js"><input type="checkbox" /> <span>Show JS Code</span></li>' +
				  '<li class="toggle-option toggle-css"><input type="checkbox" /> <span>Show CSS Code</span></li>' +
				'</ul>' +
			  '</span>' +
			  '<span class="option sub-options zooms">' +
				'<i class="zmdi zmdi-zoom-in"></i>' +
				'<i class="zmdi zmdi-chevron-down caret-down"></i>' +
				'<ul>' +
				  '<li class="zoom-in" title="Zoom In"><i class="zmdi zmdi-zoom-in"></i> Zoom In</li>' +
				  '<li class="zoom-out" title="Zoom Out"><i class="zmdi zmdi-zoom-out"></i> Zoom Out</li>' +
				  '<li class="reset" title="Zoom Reset"><i class="zmdi zmdi-search"></i> Zoom Reset</li>' +
				  '<li class="zoom-range" title="Slide to zoom"><input type="range" /><span>100%</span></li>' +
				  '<li class="auto-resize auto-resizable" title="Auto Resize for Zoom"><input type="checkbox" checked /> <span>Disable Auto Resize</span></li>' +
				'</ul>' +
			  '</span>' +
		  	'</div>' +
		  '</div>');
		
		var scale = 1;
		var min_scale = 0.2;
		var max_scale = 4;
		var step = 0.1;
		var auto_resize = true;
		
		var options = main_elm.children(".options");
		var range_btn = options.find(".zoom-range input")[0];
		var range_info = options.find(".zoom-range span")[0];
		range_btn.value = scale;
		range_btn.min = min_scale;
		range_btn.max = max_scale;
		range_btn.step = step;
		range_btn.title = "Current Zoom: " + parseInt(scale * 100) + "%";
		range_info.innerHTML = parseInt(scale * 100) + "%";
		
		var screen_width = options.find(".option-screen-width");
		var screen_height = options.find(".option-screen-height");
		var fit_to_screen = options.find(".option-fit-to-screen");
		
		options.find(".show-option-panel").on("change", function() {
			var panel_option_class = $(this).val();
			options.find("." + panel_option_class).trigger("click");
		});
		
		options.find(".show-widgets").on("click", function() {
			ui.addClass("switching-panel");
			menu_layers.fadeOut("slow");
			template_widgets_options.fadeOut("slow");
			menu_widgets.fadeIn("slow", function() {
				ui.removeClass("switching-panel");
			});
			
			options.find(".show-option-panel").val("show-widgets");
			options.find(".show-layers, .show-layout-options").removeClass("option-active");
			$(this).addClass("option-active");
			
			if (!ui.hasClass("fixed-properties") && !ui.hasClass("fixed-side-properties")) {
				menu_settings.fadeOut("slow");
				options.find(".show-settings").removeClass("option-active");
			}
		});
		options.find(".show-layers").on("click", function() {
			ui.addClass("switching-panel");
			menu_widgets.fadeOut("slow");
			template_widgets_options.fadeOut("slow");
			menu_layers.fadeIn("slow", function() {
				ui.removeClass("switching-panel");
			});
			
			options.find(".show-option-panel").val("show-layers");
			options.find(".show-widgets, .show-layout-options").removeClass("option-active");
			$(this).addClass("option-active");
			
			if (!ui.hasClass("fixed-properties") && !ui.hasClass("fixed-side-properties")) {
				menu_settings.fadeOut("slow");
				options.find(".show-settings").removeClass("option-active");
			}
		});
		options.find(".show-settings").on("click", function() {
			var show_settings_icon = $(this);
			
			if (me.selected_template_widget && !show_settings_icon.hasClass("option-disabled")) {
				if (ui.hasClass("fixed-properties") || ui.hasClass("fixed-side-properties"))
					me.showFixedMenuSettings();
				else {
					ui.addClass("switching-panel");
					menu_widgets.fadeOut("slow");
					menu_layers.fadeOut("slow");
					template_widgets_options.fadeOut("slow");
					menu_settings.fadeIn("slow", function() {
						ui.removeClass("switching-panel");
					});
					
					options.find(".show-widgets, .show-layers, .show-layout-options").removeClass("option-active");
				}
				
				show_settings_icon.addClass("option-active");
			}
		});
		options.find(".show-layout-options").on("click", function() {
			ui.addClass("switching-panel");
			menu_widgets.fadeOut("slow");
			menu_layers.fadeOut("slow");
			template_widgets_options.fadeIn("slow", function() {
				ui.removeClass("switching-panel");
			});
			
			options.find(".show-option-panel").val("show-layout-options");
			options.find(".show-widgets, .show-layers").removeClass("option-active");
			$(this).addClass("option-active");
			
			if (!ui.hasClass("fixed-properties") && !ui.hasClass("fixed-side-properties")) {
				menu_settings.fadeOut("slow");
				options.find(".show-settings").removeClass("option-active");
			}
		});
		options.find(".show-full-source").on("click", function() {
			if (!$(this).hasClass("option-disabled")) {
				$(this).toggleClass("option-active");
				
				if ($(this).hasClass("option-active"))
					convertTemplateSourceToFullSource();
				else
					convertTemplateFullSourceToSource();
				
				me.showTemplateSource();
			}
		});
		
		options.find(".layout-in-desktop").on("click", function() {
			screen_width.hide();
			screen_height.hide();
			options.find(".option-x, .option-px, .option-fit-to-screen").hide();
			
			resetTemplateWidgetsDimensions();
			
			$(this).addClass("option-active");
			options.find(".layout-in-mobile").removeClass("option-active");
		});
		
		options.find(".layout-in-mobile").on("click", function() {
			screen_width.show();
			screen_height.show();
			options.find(".option-x, .option-px, .option-fit-to-screen").show();
			
			//set screen width and height
			setTemplateWidgetsDimensions(screen_width, screen_height, fit_to_screen);
			
			$(this).addClass("option-active");
			options.find(".layout-in-desktop").removeClass("option-active");
		});
		
		options.find(".option-fit-to-screen").on("change", function() {
			//set screen width and height
			setTemplateWidgetsDimensions(screen_width, screen_height, fit_to_screen);
		});
		
		var maximums = getTemplateWidgetsIframeMaximumDimensions();
		var max_w = maximums["max-width"] > 0 ? maximums["max-width"] : 320;
		var max_h = maximums["max-height"] > 0 ? maximums["max-height"] : 568;
		screen_width.val( 320 < max_w ? 320 : max_w );
		screen_height.val( 568 < max_h ? 568 : max_h );
		
		options.find(".option-screen-width, .option-screen-height").on("keypress", function() {
			if (this.keypress_timeout)
				clearTimeout(this.keypress_timeout);
			
			this.keypress_timeout = setTimeout(function() {
				setTemplateWidgetsDimensions(screen_width, screen_height, fit_to_screen);
			}, 500);
		});
		
		screen_width.on("keyup", function() {
			//only allow numbers
			if ($(this).val().match(/[^0-9]+/g)) {
				$(this).val( ("" + this.value).replace(/[^0-9]/g, "") );
				me.showError("Only numeric values allowed!")
			}
		});
		
		screen_height.on("keyup", function() {
			//only allow numbers
			if ($(this).val().match(/[^0-9]+/g)) {
				$(this).val( ("" + this.value).replace(/[^0-9]/g, "") );
				me.showError("Only numeric values allowed!")
			}
		});
		
		options.find(".full-screen").on("click", function() {
			var icon = $(this);
			icon.toggleClass("zmdi-fullscreen zmdi-fullscreen-exit");
			main_elm.toggleClass("full-screen");
			
			if (options.find(".layout-in-mobile").hasClass("option-active")) //set screen width and height for the mobile layout
				setTemplateWidgetsDimensions(screen_width, screen_height, fit_to_screen);
			else 
				resetTemplateWidgetsDimensions();
			
			var editor = template_source.data("editor");
			editor.resize();
			
			var editor = template_full_source.data("editor");
			editor.resize();
			
			if (me.isMenuSettingsVisible())
				me.showFixedMenuSettings(true);
			
			if (me.TextSelection)
				me.TextSelection.refreshMenu();
			
			//set z-index
			if (main_elm.hasClass("full-screen")) {
				main_elm.attr("data-orig-z-index", main_elm.css("zIndex"));
				
				var index_highest = 0;
				$.each( $(me.options.ui_selector), function(idx, item) {
					if (!main_elm.is(this)) {
						var index_current = parseInt( $(this).css("zIndex"), 10);
						if(index_current > index_highest)
							index_highest = index_current;
					}
				});
				
				if (index_highest > 0)
					main_elm.css("zIndex", index_highest + 100);
			}
			else
				main_elm.css("zIndex", main_elm.attr("data-orig-z-index"));
		});
		
		options.find(".toggle-background").on("click", function() {
			var elm = $(this);
			
			elm.toggleClass("background-shown");
			
			if (elm.hasClass("background-shown"))
				me.showTemplateWidgetsDroppableBackground();
			else 
				me.hideTemplateWidgetsDroppableBackground();
		});
		
		options.find(".toggle-borders").on("click", function() {
			var elm = $(this);
			
			elm.toggleClass("borders-shown");
			
			if (elm.hasClass("borders-shown"))
				me.showTemplateWidgetsBorders();
			else 
				me.hideTemplateWidgetsBorders();
		});
		
		options.find(".toggle-php").on("click", function() {
			var elm = $(this);
			
			elm.toggleClass("php-shown");
			
			if (elm.hasClass("php-shown"))
				me.showTemplatePHPWidgets();
			else 
				me.hideTemplatePHPWidgets();
		});
		
		options.find(".toggle-ptl").on("click", function() {
			var elm = $(this);
			
			elm.toggleClass("ptl-shown");
			
			if (elm.hasClass("ptl-shown"))
				me.showTemplatePTLWidgets();
			else 
				me.hideTemplatePTLWidgets();
		});
		
		options.find(".toggle-comments").on("click", function() {
			var elm = $(this);
			
			elm.toggleClass("comments-shown");
			
			if (elm.hasClass("comments-shown"))
				me.showTemplateCommentsWidgets();
			else 
				me.hideTemplateCommentsWidgets();
		});
		
		options.find(".toggle-js").on("click", function() {
			var elm = $(this);
			
			elm.toggleClass("js-shown");
			
			if (elm.hasClass("js-shown"))
				me.showTemplateJSWidgets();
			else 
				me.hideTemplateJSWidgets();
		});
		
		options.find(".toggle-css").on("click", function() {
			var elm = $(this);
			
			elm.toggleClass("css-shown");
			
			if (elm.hasClass("css-shown"))
				me.showTemplateCSSWidgets();
			else 
				me.hideTemplateCSSWidgets();
		});
		
		options.find(".zoom-in").on("click", function() {
			if (scale < max_scale) {
				scale += step;
				scaleContent(scale, min_scale, max_scale, auto_resize);
				range_btn.stepUp();
				range_btn.title = "Current Zoom: " + parseInt(scale * 100) + "%";
				range_info.innerHTML = parseInt(scale * 100) + "%";
			}
		});
		
		options.find(".zoom-out").on("click", function() {
			if (scale > min_scale) {
				scale -= step;
				scaleContent(scale, min_scale, max_scale, auto_resize);
				range_btn.stepDown();
				range_btn.title = "Current Zoom: " + parseInt(scale * 100) + "%";
				range_info.innerHTML = parseInt(scale * 100) + "%";
			}
		});
		
		options.find(".reset").on("click", function() {
			scale = 1;
			range_btn.value = 1;
			range_btn.title = "Current Zoom: " + parseInt(scale * 100) + "%";
			range_info.innerHTML = parseInt(scale * 100) + "%";
			scaleContent(scale, min_scale, max_scale, auto_resize);
		});
		
		$(range_btn).change( function() { //must have change handler bc the IE needs it
			scale = parseFloat(this.value);
			range_btn.title = "Current Zoom: " + parseInt(scale * 100) + "%";
			range_info.innerHTML = parseInt(scale * 100) + "%";
			scaleContent(scale, min_scale, max_scale, auto_resize);
		})
		.on("input", function() {
			$(this).trigger("change");
		});
		
		options.find(".auto-resize").on("click", function() {
			var elm = $(this);
			var input = elm.find("input");
			var span = elm.find("span");
			
			elm.toggleClass("auto-resizable");
			auto_resize = elm.is(".auto-resizable");
			
			if (auto_resize) {
				input.attr("checked", "checked").prop("checked", true);
				span.html("Disable Auto Resize");
			}
			else {
				input.removeAttr("checked", "checked").prop("checked", false);
				span.html("Enable Auto Resize");
			}
			
			scaleContent(scale, min_scale, max_scale, auto_resize);
		});
		
		var sub_options = options.find(".sub-options");
		sub_options.on("click", function(e) {
			if (e && e.target) { //must do this to be sure that the it was a manual click, otherwise it could be an automatic save that was executed by the system and we want to avoid this events!
				var sub_menu = $(this).closest(".sub-options");
				
				if (sub_menu[0]) {
					var is_open = sub_menu.is(".open");
					sub_options.removeClass("open"); //remove previous opened menus
					
					if (me.sub_menu_open_interval)
						clearInterval(me.sub_menu_open_interval);
					
					if (!is_open) {
						sub_menu.addClass("open");
					
						me.sub_menu_open_interval = setInterval(function() {
							if (!sub_menu.is(":hover")) {
								//console.log(me.sub_menu_open_interval);
								
								if (me.sub_menu_open_interval)
									clearInterval(me.sub_menu_open_interval);
								
								sub_menu.removeClass("open");
							}
						}, 5000);
					}
				}
			}
		});
	}
	
	//Note that the template_widgets_iframe_body must hv the following css: transform-origin: 0 0 0; transition: transform 200ms ease-in-out
	function scaleContent(scale, min_scale, max_scale, auto_resize) {
		if (scale >= min_scale && scale <= max_scale) {
			var w = auto_resize && scale < 1 ? 100 / scale + "%" : "100%";
			var h = auto_resize && scale < 1 ? 100 / scale + "%" : (scale < 1 ? "100%" : 100 * scale + "%");
			
			if (scale == 1 && parseInt(h) == 100)
				template_widgets_iframe_body.css({
					"position": "",
					/*"-webkit-transform": "",
					"-moz-transform": "",
					"-ms-transform": "",
					"-o-transform": "",*/
					"transform": "",
					"transform-origin": "",
					"width": "",
					"height": "",
				});
			else 
				template_widgets_iframe_body.css({
					"position": "absolute",
					/*"-webkit-transform" : "scale(" + scale + ")",
					"-moz-transform"    : "scale(" + scale + ")",
					"-ms-transform"     : "scale(" + scale + ")",
					"-o-transform"      : "scale(" + scale + ")",*/
					"transform"         : "scale(" + scale + ")",
					"transform-origin"	: "0 0",
					"width"             : w,
					"height"            : h,
				});
		}
	}
	
	function getTemplateWidgetsIframeMaximumDimensions() {
		var max_w = template_widgets.width();
		//var max_w = ui.width() - menu_widgets.width();
		max_w = max_w > 0 ? max_w : 0;
		
		var tsh = me.TextSelection && me.TextSelection.isMenuShown() ? me.TextSelection.getMenuHeight() : 0;
		var max_h = template_widgets.height();
		//var max_h = ui.height() - parseInt(template_widgets.css("top"));
		max_h = max_h - tsh;
		max_h = max_h > 0 ? max_h : 0;
		
		return {"max-width": parseInt(max_w), "max-height": parseInt(max_h)};
	}
	
	//set screen width and height
	function setTemplateWidgetsDimensions(screen_width, screen_height, fit_to_screen) {
		var w = screen_width.val();
		var h = screen_height.val();
		var fit = fit_to_screen.is(":checked");
		
		if ($.isNumeric(w) && $.isNumeric(h)) {
			//check if dimension are not bigger than parent
			var maximums = getTemplateWidgetsIframeMaximumDimensions();
			var max_w = maximums["max-width"];
			var max_h = maximums["max-height"];
			var mt = 0;
			
			if (fit && w > max_w) {
				screen_width.val(max_w);
				me.showError("Width of " + w + "px exceeds the maximum width of " + max_w + "px!");
				w = max_w;
			}
			
			if (fit && h > max_h) {
				screen_height.val(max_h);
				me.showError("Height of " + h + "px exceeds the maximum height of " + max_h + "px!");
				h = max_h;
			}
			else if (h < max_h)
				mt = parseInt((max_h - h) / 2);
			
			template_widgets_iframe.css({
				width: w + "px", 
				height: h + "px", 
				"min-height": "auto",
				border: "5px solid #000",
				"border-radius": "10px",
				"margin-top": mt + "px",
				"margin-bottom": mt + "px",
			});
		}
	}
	
	function resetTemplateWidgetsDimensions() {
		//disable screen width and height
		var css = {
			width: "", 
			border: "",
			"border-radius": "",
			"margin-top": "",
			"margin-bottom": "",
		};
		
		if (me.TextSelection && me.TextSelection.isMenuShown()) {
			var maximums = getTemplateWidgetsIframeMaximumDimensions();
			var max_h = maximums["max-height"];
			
			css["height"] = max_h + "px";
			css["min-height"] = "auto";
		}
		else {
			css["height"] = "";
			css["min-height"] = ""; //removes the previous set min-height in the setTemplateWidgetsDimensions function
		}
		
		template_widgets_iframe.css(css);
	}
	
	function refreshTemplateWidgetsDimensions() {
		var ui_options_menu = ui.children(".options");
		
		if (ui_options_menu.find(".layout-in-mobile").hasClass("option-active")) { //set screen width and height for the mobile layout
			var screen_width = ui_options_menu.find(".option-screen-width");
			var screen_height = ui_options_menu.find(".option-screen-height");
			var fit_to_screen = ui_options_menu.find(".option-fit-to-screen");
			
			setTemplateWidgetsDimensions(screen_width, screen_height, fit_to_screen);
		}
		else
			resetTemplateWidgetsDimensions();
	}
	
	function setMainTabs(main_elm) {
		main_elm.append(
		  '<ul class="tabs">' +
			  '<li class="tab view-page"><i class="zmdi zmdi-eye"></i> Preview</li>' +
			  '<li class="tab view-source tab-active is-source-changed"><i class="zmdi zmdi-code-setting"></i> Source</li>' +
			  '<li class="tab view-layout"><i class="zmdi zmdi-collection-folder-image"></i> Visual</li>' +
		  '</ul>');
		
		var tabs = main_elm.children(".tabs");
		var items = tabs.children(".tab");
		
		items.on("click", function() {
			var elm = $(this);
			
			if (!elm.hasClass("tab-loading")) {
				items.addClass("tab-loading");
				var prev_active_tab = items.filter(".tab-active");
				
				elm.children("i").addClass("zmdi-refresh zmdi-hc-spin"); //add loading spin
				var view_source = items.filter(".view-source");
				
				if (elm.hasClass("view-page")) {
					convertTemplateSourceOrLayoutToPreview(prev_active_tab);
					
					me.showTemplatePreview();
				}
				else if (elm.hasClass("view-source")) {
					if (prev_active_tab.hasClass("view-layout") || !view_source.hasClass("is-source-changed")) {
						view_source.addClass("is-source-changed");
						
						if (!view_source.hasClass("do-not-convert") && (elm.hasClass("do-not-confirm") || me.options.auto_convert || confirm("Do you wish to convert the layout into html?"))) {
							unsetSelectedTemplateWidget();
							
							//console.log("convert automatically");
							me.convertTemplateLayoutToSource();
						}
					}
					
					me.showTemplateSource();
					
					setTimeout(function() {
						var editor = template_source.data("editor");
						if (editor)
							editor.resize();
					}, 800);
				}
				else if (elm.hasClass("view-layout")) {
					me.showTemplateLayout();
					
					if (prev_active_tab.hasClass("view-source") && view_source.hasClass("is-source-changed") && !view_source.hasClass("do-not-convert") && (elm.hasClass("do-not-confirm") || me.options.auto_convert || confirm("Do you wish to convert this code in Layout?"))) {
						unsetSelectedTemplateWidget();
						
						me.convertTemplateSourceToLayoutAccordingWithOptions();
						
						view_source.removeClass("is-source-changed");
					}
				}
				
				elm.children("i").removeClass("zmdi-refresh zmdi-hc-spin"); //remove loading spin
				items.removeClass("tab-loading tab-active");
				elm.addClass("tab-active");
			}
		});
	}
	
	function setTemplateLayoutOptions() {
		/* Note that the names of the input[type=checkbox] will be used in the prepareTemplateLayoutAccordingWithOptions method */
		template_widgets_options.append(
		  '<div class="options">' +
			  '<div class="show-option show-background">' +
				  '<input type="checkbox" name="show_background">' +
				  '<label>Show Background</label>' +
			  '</div>' +
			  '<div class="show-option show-borders">' +
				  '<input type="checkbox" name="show_borders">' +
				  '<label>Show Borders</label>' +
			  '</div>' +
			  '<div class="show-option show-comments">' +
				  '<input type="checkbox" name="show_comments">' +
				  '<label>Show Comments</label>' +
			  '</div>' +
			  '<div class="show-option show-php">' +
				  '<input type="checkbox" name="show_php">' +
				  '<label>Show Server Code</label>' +
			  '</div>' +
			  '<div class="show-option show-ptl">' +
				  '<input type="checkbox" name="show_ptl" checked>' +
				  '<label>Hide PTL Code</label>' +
			  '</div>' +
			  '<div class="show-option show-js">' +
				  '<input type="checkbox" name="show_js">' +
				  '<label>Show JS Code</label>' +
			  '</div>' +
			  '<div class="show-option show-css">' +
				  '<input type="checkbox" name="show_css">' +
				  '<label>Show CSS Code</label>' +
			  '</div>' +
			'</div>');
		
		var options = template_widgets_options.children(".options");
		
		options.children(".show-background").click(function(event) {
			var elm = $(this);
			
			elm.toggleClass("background-shown");
			
			if (elm.hasClass("background-shown"))
				me.showTemplateWidgetsDroppableBackground();
			else 
				me.hideTemplateWidgetsDroppableBackground();
		});
		
		options.children(".show-borders").click(function(event) {
			var elm = $(this);
			
			elm.toggleClass("borders-shown");
			
			if (elm.hasClass("borders-shown"))
				me.showTemplateWidgetsBorders();
			else 
				me.hideTemplateWidgetsBorders();
		});
		
		options.children(".show-php").click(function(event) {
			var elm = $(this);
			
			elm.toggleClass("php-shown");
			
			if (elm.hasClass("php-shown"))
				me.showTemplatePHPWidgets();
			else 
				me.hideTemplatePHPWidgets();
		});
		
		options.children(".show-ptl").click(function(event) {
			var elm = $(this);
			
			elm.toggleClass("ptl-shown");
			
			if (elm.hasClass("ptl-shown"))
				me.showTemplatePTLWidgets();
			else 
				me.hideTemplatePTLWidgets();
		});
		
		options.children(".show-comments").click(function(event) {
			var elm = $(this);
			
			elm.toggleClass("comments-shown");
			
			if (elm.hasClass("comments-shown"))
				me.showTemplateCommentsWidgets();
			else 
				me.hideTemplateCommentsWidgets();
		});
		
		options.children(".show-js").click(function(event) {
			var elm = $(this);
			
			elm.toggleClass("js-shown");
			
			if (elm.hasClass("js-shown"))
				me.showTemplateJSWidgets();
			else 
				me.hideTemplateJSWidgets();
		});
		
		options.children(".show-css").click(function(event) {
			var elm = $(this);
			
			elm.toggleClass("css-shown");
			
			if (elm.hasClass("css-shown"))
				me.showTemplateCSSWidgets();
			else 
				me.hideTemplateCSSWidgets();
		});
	}
	
	me.showTemplateCommentsWidgets = function() {
		me.showTemplateWidgetsByType("comments", "Hide Comments", ".template-widget-html-comment");
	};
	
	me.hideTemplateCommentsWidgets = function() {
		me.hideTemplateWidgetsByType("comments", "Show Comments", ".template-widget-html-comment");
	};
	
	me.showTemplatePHPWidgets = function() {
		me.showTemplateWidgetsByType("php", "Hide Server Code", ".template-widget-php");
	};
	
	me.hideTemplatePHPWidgets = function() {
		me.hideTemplateWidgetsByType("php", "Show Server Code", ".template-widget-php");
	};
	
	me.showTemplatePTLWidgets = function() {
		me.showTemplateWidgetsByType("ptl", "Hide PTL Code", ".template-widget-ptl");
	};
	
	me.hideTemplatePTLWidgets = function() {
		me.hideTemplateWidgetsByType("ptl", "Show PTL Code", ".template-widget-ptl");
	};
	
	me.showTemplateJSWidgets = function() {
		me.showTemplateWidgetsByType("js", "Hide JS Code", ".template-widget-script");
	};
	
	me.hideTemplateJSWidgets = function() {
		me.hideTemplateWidgetsByType("js", "Show JS Code", ".template-widget-script");
	};
	
	me.showTemplateCSSWidgets = function() {
		me.showTemplateWidgetsByType("css", "Hide CSS Code", ".template-widget-link, .template-widget-style");
	};
	
	me.hideTemplateCSSWidgets = function() {
		me.hideTemplateWidgetsByType("css", "Show CSS Code", ".template-widget-link, .template-widget-style");
	};
	
	me.showTemplateWidgetsDroppableBackground = function() {
		template_widgets_droppable.addClass("with-background");
		me.showTemplateWidgetsByType("background", "Hide Background", null);
	};
	
	me.hideTemplateWidgetsDroppableBackground = function() {
		template_widgets_droppable.removeClass("with-background");
		me.hideTemplateWidgetsByType("background", "Show Background", null);
	};
	
	me.showTemplateWidgetsBorders = function() {
		template_widgets_droppable.addClass("borders");
		me.showTemplateWidgetsByType("borders", "Hide Borders", null);
	};
	
	me.hideTemplateWidgetsBorders = function() {
		template_widgets_droppable.removeClass("borders");
		me.hideTemplateWidgetsByType("borders", "Show Borders", null);
	};
	
	me.showTemplateWidgetsByType = function(type, label, template_widget_selector) {
		if (!type)
			return;
		
		//show widgets
		if (template_widget_selector)
			template_widgets_droppable.find(template_widget_selector).show();
		
		//prepare show layout option
		var show = template_widgets_options.find(" > .options > .show-" + type);
		
		if (show[0]) {
			var show_input = show.find("input");
			
			show.addClass(type + "-shown");
			show_input.attr("checked", "checked").prop("checked", true);
			
			var show_label = show.find("label");
			
			if (label && show_label[0])
				show_label.html(label);
		}
		
		//prepare toggle option
		var toggle = ui.find(" > .options .toggle-" + type);
		
		if (toggle[0]) {
			var toggle_input = toggle.find("input");
			
			toggle.addClass(type + "-shown");
			toggle_input.attr("checked", "checked").prop("checked", true);
			
			var toggle_span = toggle.find("span");
			
			if (label && toggle_span[0])
				toggle_span.html(label);
		}
	};
	
	me.hideTemplateWidgetsByType = function(type, label, template_widget_selector) {
		if (!type)
			return;
		
		//hide widgets
		if (template_widget_selector)
			template_widgets_droppable.find(template_widget_selector).hide();
		
		//prepare show layout option
		var show = template_widgets_options.find(" > .options > .show-" + type);
		
		if (show[0]) {
			var show_input = show.find("input");
			
			show.removeClass(type + "-shown");
			show_input.removeAttr("checked").prop("checked", false);
			
			var show_label = show.find("label");
			
			if (label && show_label[0])
				show_label.html(label);
		}
		
		//prepare toggle option
		var toggle = ui.find(" > .options .toggle-" + type);
		
		if (toggle[0]) {
			var toggle_input = toggle.find("input");
			
			toggle.removeClass(type + "-shown");
			toggle_input.removeAttr("checked").prop("checked", false);
			
			var toggle_span = toggle.find("span");
			
			if (label && toggle_span[0])
				toggle_span.html(label);
		}
	};
	
	function getTemplateLayoutOptionsValues() {
		var props = me.getMenuSettingsProperties(template_widgets_options);
		
		return props;
	}
	
	function prepareTemplateLayoutAccordingWithOptions() {
		var options = getTemplateLayoutOptionsValues();
		
		if (options) {
			if (options["show_background"])
				me.showTemplateWidgetsDroppableBackground();
			else 
				me.hideTemplateWidgetsDroppableBackground();
			
			if (options["show_borders"])
				me.showTemplateWidgetsBorders();
			else
				me.hideTemplateWidgetsBorders();
			
			if (options["show_php"])
				me.showTemplatePHPWidgets();
			else
				me.hideTemplatePHPWidgets();
			
			if (options["show_ptl"])
				me.showTemplatePTLWidgets();
			else
				me.hideTemplatePTLWidgets();
			
			if (options["show_comments"])
				me.showTemplateCommentsWidgets();
			else
				me.hideTemplateCommentsWidgets();
			
			if (options["show_js"])
				me.showTemplateJSWidgets();
			else
				me.hideTemplateJSWidgets();
			
			if (options["show_css"])
				me.showTemplateCSSWidgets();
			else
				me.hideTemplateCSSWidgets();
		}
	}
	
	me.clickViewLayoutTabWithoutSourceConversion = function() {
		//remove is-source-changed from view_source tab, so the view layout tab doesn't need to convert the layout again.
		var tabs = ui.children(".tabs");
		
		var view_source = tabs.children(".view-source");
		var source_changed = view_source.hasClass("is-source-changed");
		view_source.removeClass("is-source-changed"); 
		
		tabs.children(".view-layout").trigger("click");
		
		if (source_changed)
			view_source.addClass("is-source-changed");
	};
	
	me.showTemplatePreview = function() {
		var options = ui.children(".options");
		
		menu_widgets.addClass("hidden");
		menu_layers.addClass("hidden");
		menu_settings.addClass("hidden");
		resize_panels.addClass("hidden");
		template_source.addClass("hidden");
		template_full_source.addClass("hidden");
		template_preview.removeClass("hidden");
		template_widgets.addClass("hidden");
		template_widgets_options.addClass("hidden");
		
		ui.removeClass("layout-shown");
		options.find(".option, .zoom-range, .auto-resize").addClass("hidden");
		options.find(".option.full-screen, .option.preview").removeClass("hidden");
	};
	
	me.showTemplateLayout = function() {
		var options = ui.children(".options");
		
		menu_widgets.removeClass("hidden");
		menu_layers.removeClass("hidden");
		menu_settings.removeClass("hidden");
		resize_panels.removeClass("hidden");
		template_source.addClass("hidden");
		template_full_source.addClass("hidden");
		template_preview.addClass("hidden");
		template_widgets.removeClass("hidden");
		template_widgets_options.removeClass("hidden");
		
		ui.addClass("layout-shown");
		options.find(".option, .zoom-range, .auto-resize").removeClass("hidden");
		options.find(".option.show-full-source").addClass("hidden");
		
		options.find(".show-widgets").click();
		
		if (me.TextSelection)
			me.TextSelection.hideMenu();
	};
	
	me.showTemplateSource = function() {
		var options = ui.children(".options");
		
		menu_widgets.addClass("hidden");
		menu_layers.addClass("hidden");
		menu_settings.addClass("hidden");
		resize_panels.addClass("hidden");
		template_preview.addClass("hidden");
		template_widgets.addClass("hidden");
		template_widgets_options.addClass("hidden");
		
		ui.removeClass("layout-shown");
		options.find(".option, .zoom-range, .auto-resize").addClass("hidden");
		options.find(".option.full-screen, .option.show-full-source, .option.preview").removeClass("hidden");
		
		var is_full_source_active = options.find(".option.show-full-source").hasClass("option-active");
		if (is_full_source_active) {
			template_source.addClass("hidden");
			template_full_source.removeClass("hidden");
		}
		else {
			template_source.removeClass("hidden");
			template_full_source.addClass("hidden");
		}
	};
	
	me.isTemplateLayoutShown = function() {
		return ui.children(".tabs").children(".view-layout").hasClass("tab-active");
	};
	
	me.isTemplateSourceShown = function() {
		return ui.children(".tabs").children(".view-source").hasClass("tab-active");
	};
	
	me.isTemplatePreviewShown = function() {
		return ui.children(".tabs").children(".view-page").hasClass("tab-active");
	};
	
	/* MENU LAYERS METHODS */
	
	function initMenuLayers() {
		menu_layers.click(function(event) {
			template_widgets_iframe_body.click(); //This is to trigger the main template_widgets_iframe_body click, in order to hide the widgets and droppable headers
		});
	}
	
	//init Menu layers with the correspondent widgets in the template.
	function prepareMenuLayers() {
		menu_layers.html("");
		
		//if multiple main droppable, create multiple layers droppable
		if (template_widgets_droppable.length > 1)
			$.each(template_widgets_droppable, function(idx, droppable) {
				droppable = $(droppable);
				var label = droppable.attr("data-main-droppable-name");
				
				if (!label)
					label = "Main Droppable";
				
				var html = '<li class="main-group main-group-open">'
						+ '	<div class="main-group-title">'
						+ '		<i class="zmdi zmdi-caret-down toggle"></i>'
						+ '		<label>' + label + '</label>'
						+ 		'<i class="zmdi zmdi-eye visibility"></i>'
						+ '	</div>'
						+ '	<ul></ul>'
						+ '</li>';
				
				var item = $(html);
				var title = item.find(" > .main-group-title");
				
				//set item's toggle event
				title.children(".toggle").click(function(event) {
					event.preventDefault();
			  		event.stopPropagation();
					
					var p = $(this).parent().parent();
					p.children("ul").toggle("slow");
					p.toggleClass("main-group-open");
					$(this).toggleClass("zmdi-caret-down zmdi-caret-right");
				});
				
				//set item's visibility event
				title.children(".visibility").click(function(event) {
					event.preventDefault();
			  		event.stopPropagation();
					
					var was_main_droppable_hidden = $(this).hasClass("zmdi-eye-off");
					$(this).toggleClass("zmdi-eye zmdi-eye-off");
					
					var p = $(this).parent().parent();
					var index = p.index();
					
					if (index < template_widgets_droppable.length) {
						var main_droppable = $(template_widgets_droppable[index]);
						was_main_droppable_hidden ? main_droppable.show() : main_droppable.hide();
					}
				});
				
				menu_layers.append(item);
			});
		
		//add widgets to right menu layers
		var widgets = template_widgets_droppable.children(".template-widget");
		
		$.each(widgets, function(idx, widget) {
			me.updateMenuLayer( $(widget), true );
		});
	}
	
	//element can be a widget or a droppable. Both are Jquery objects.
	//used in the widgets too.
	me.updateMenuLayer = function(element, do_not_call_prepareMenuLayers) {
		if (element[0]) {
			var is_widget = me.hasNodeClass(element, "template-widget");
			var menu_layer = getMenuLayer(element);
			
			if (menu_layer) {
				var is_droppable = me.hasNodeClass(element, "droppable");
				
				if (is_widget) {
					var prev_widget = element.prev(".template-widget");
					
					if (prev_widget[0]) {
						if (!menu_layer || !menu_layer[0] || prev_widget.data("data-template-id") != menu_layer.prev(".group.is-widget").attr("template-target-id")) {
							var ml_prev_widget = getMenuLayer(prev_widget);
							
							if (ml_prev_widget && menu_layer && menu_layer[0]) {
								menu_layer.insertAfter(ml_prev_widget);
								return true;
							}
							//else execute code bellow
						}
						else //do nothing, bc the menu_layer is already in the right place
							return true;
					}
					
					var widget_droppable = element.parent();
					var ml_widget_droppable = getMenuLayer(widget_droppable);
					
					if (ml_widget_droppable)
						ml_widget_droppable.removeClass("no-childs").children("ul").prepend(menu_layer); //must be prepend and not append. Prepend bc the logic is based in the .prev() method (prev_widget);
					else if (widget_droppable.parent().is(".main-group")) //it means it is at the top level
						widget_droppable.prepend(menu_layer); //must be prepend and not append. Prepend bc the logic is based in the .prev() method (prev_widget);
					else if (widget_droppable.is(menu_layers)) //it means it is at the top level
						menu_layers.prepend(menu_layer); //must be prepend and not append. Prepend bc the logic is based in the .prev() method (prev_widget);
					else if (!do_not_call_prepareMenuLayers) //something went wrong so refresh all menu layers do_not_call_prepareMenuLayers is bc of infinit loops
						prepareMenuLayers();
				}
				else if (is_droppable) {
					var prev_droppable = element.prev(".droppable");
					
					if (prev_droppable[0]) {
						if (!menu_layer || !menu_layer[0] || prev_droppable.data("data-template-id") != menu_layer.prev(".group.is-droppable").attr("template-target-id")) {
							var ml_prev_droppable = getMenuLayer(prev_droppable);
							
							if (ml_prev_droppable && menu_layer && menu_layer[0]) {
								menu_layer.insertAfter(ml_prev_droppable);
								return true;
							}
							//else execute code bellow
						}
						else //do nothing, bc the menu_layer is already in the right place
							return true;
					}
					
					menu_layer.parent().prepend(menu_layer); //must be prepend and not append. Prepend bc the logic is based in the .prev() method (prev_widget);
				}
				else if (!do_not_call_prepareMenuLayers) //refresh all menu layers. do_not_call_prepareMenuLayers is bc of infinit loops
					prepareMenuLayers();
			}
			else {
				var widget_droppable = element.parent();
				var ml_widget_droppable = getMenuLayer(widget_droppable);
				
				if (ml_widget_droppable) {
					ml_widget_droppable.removeClass("no-childs");
					addMenuLayer(ml_widget_droppable.children("ul"), element);
					
					//Then put the new menu layer in the right place
					var menu_layer = getMenuLayer(element);
					var prev_widget = element.prev(".template-widget");
					
					if (prev_widget[0]) {
						if (!menu_layer || !menu_layer[0] || prev_widget.data("data-template-id") != menu_layer.prev(".group.is-widget").attr("template-target-id")) {
							var ml_prev_widget = getMenuLayer(prev_widget);
							
							if (ml_prev_widget && menu_layer && menu_layer[0]) {
								menu_layer.insertAfter(ml_prev_widget);
								return true;
							}
							//else execute code bellow
						}
						else //do nothing, bc the menu_layer is already in the right place
							return true;
					}
					
					ml_widget_droppable.removeClass("no-childs").children("ul").prepend(menu_layer); //must be prepend and not append. Prepend bc the logic is based in the .prev() method (prev_widget);
					
					return true;
				}
				//else execute code bellow
				
				addMenuLayer(menu_layers, element);
			}
			
			return true;
		}
	};
	
	me.refreshElementMenuLayer = function(element) {
		//console.log("refreshElementMenuLayer");
		//updates the element menu layer, bc maybe it doesn't exist yet or exists and was changed
		me.updateMenuLayer(element); 
		
		//then get it from menu layer. At this point the menu_layer will already exist!
		var menu_layer = getMenuLayer(element);
		
		if (menu_layer) {
			//then gets children and updates the children menu layer
			var children = element.children();
			
			if (children.length == 0)
				menu_layer.addClass("no-childs");
			else  {
				menu_layer.removeClass("no-childs");
				var ul = menu_layer.children("ul");
				ul.html("");
				
				$.each(children, function(idx, child) {
					addMenuLayer(ul, $(child));
				});
			}
		}
		else //refresh all menu layers. Something went wrong bc the menu_layer should exist!
			prepareMenuLayers();
	};
			
	function addMenuLayer(menu_layers, element) {
		var selected_widget_id = me.selected_template_widget ? me.selected_template_widget.data("data-template-id") : null;
		
		//prepare item
		var is_widget = me.hasNodeClass(element, "template-widget");
		var is_droppable = me.hasNodeClass(element, "droppable");
		var label = is_widget ? element.data("data-label") : element[0].nodeName.toLowerCase();
		var layer_class = (is_widget ? " is-widget" : "") + (is_droppable ? " is-droppable" : "") + (is_widget && selected_widget_id == element.data("data-template-id") ? " selected" : "");
		
		//prepare elm_to_append
		var elm_to_append = menu_layers;
		
		if (template_widgets_droppable.length > 1) {
			var element_parent_droppable = element.parentsUntil(template_widgets_iframe_body, ".main-droppable");
			
			if (!element_parent_droppable[0] && template_widgets_iframe_body.is(".main-droppable"))
				element_parent_droppable = template_widgets_iframe_body;
			
			if (element_parent_droppable[0]) {
				var index = -1;
				
				for (var i = 0; i < template_widgets_droppable.length; i++)
					if (element_parent_droppable.is(template_widgets_droppable[i])) {
						index = i;
						break;
					}
				
				if (index >= 0 && menu_layers.children(".main-group").length > index)
					elm_to_append = $( menu_layers.children(".main-group")[index] ).children("ul");
			}
		}
		
		//create menu layer item
		if (!is_widget) {
			var c = me.getTemplateWidgetCurrentClassesWithoutReservedClasses(element);
			label += c != "" ? "." + c.replace(" ", ".") : "";
		}
		else {
			var aux = me.getTemplateWidgetLabel(element);
			if (aux != label)
				label += ": " + aux;
			
			//preparing classes
			var tag = element.data("data-tag");
			var menu_widget = me.getTemplateMenuWidget(element);
			
			layer_class = " group-" + tag + (menu_widget.attr("data-menu-layer-class") ? " " + menu_widget.attr("data-menu-layer-class") : "") + layer_class;
		}
		
		var html = '<li class="group' + layer_class + '"' + (element.data("data-template-id") ? ' template-target-id="' + element.data("data-template-id") + '"' : '') + '>'
			+ '	<div class="group-title">'
			+ '		<i class="zmdi zmdi-caret-right toggle"></i>'
			+ '		<label>' + label + '</label>'
			+ 		(is_widget || is_droppable ? '<i class="zmdi zmdi-eye visibility"></i>' : '')
			+ 		(is_widget ? '<i class="zmdi zmdi-arrows move"></i>' : '')
			+ 		(is_widget ? '<i class="zmdi zmdi-swap zmdi-hc-rotate-90 sort"></i>' : '')
			+ 		(is_widget/* || is_droppable*/ ? '<i class="zmdi zmdi-settings props"></i>' : '')
			+ '	</div>'
			+ '	<ul></ul>'
			+ '</li>';
		
		var item = $(html);
		var title = item.find(" > .group-title");
		
		//set item's toggle event
		title.children(".toggle").click(function(event) {
			event.preventDefault();
	  		event.stopPropagation();
			
			var p = $(this).parent().parent();
			p.children("ul").toggle("slow");
			p.toggleClass("group-open");
			$(this).toggleClass("zmdi-caret-down zmdi-caret-right");
		});
		
		//set item's visibility event
		title.children(".visibility").click(function(event) {
			event.preventDefault();
	  		event.stopPropagation();
			
			var was_widget_hidden = $(this).hasClass("zmdi-eye-off");
			$(this).toggleClass("zmdi-eye zmdi-eye-off");
			
			var p = $(this).parent().parent();
			
			if (p.hasClass("is-widget")) {
				var target = me.getTemplateWidget(p.attr("template-target-id"));
				was_widget_hidden ? me.removeNodeClass(target, "widget-hidden") : me.addNodeClass(target, "widget-hidden");
			}
			else if (p.hasClass("is-droppable")) {
				var target = me.getTemplateDroppable(p.attr("template-target-id"));
				was_widget_hidden ? me.removeNodeClass(target, "droppable-hidden") : me.addNodeClass(target, "droppable-hidden");
			}
		});
		
		//set item's toggle event
		title.children(".props").click(function(event) {
			event.preventDefault();
	  		event.stopPropagation();
			
			var p = $(this).parent().parent();
			
			if (p.hasClass("is-widget")/* || p.hasClass("is-droppable")*/) {
				var widget_id = p.attr("template-target-id");
				
				if (widget_id) {
					var widget = /*p.hasClass("is-droppable") ? me.getTemplateDroppable(p.attr("template-target-id")) : */me.getTemplateWidget(widget_id);
					widget.click();
					openMenuSettings(widget);
					
					//show menu settings panel
					ui.find(" > .options .show-settings").click();
				}
			}
		});
		
		//set item's hover event
		title.hover(function(event) {
			event.preventDefault();
	  		event.stopPropagation();
			
			var p = $(this).parent();
			
			if (p.hasClass("is-widget")) {
				var widget_id = p.attr("template-target-id");
				
				if (widget_id) {
					template_widgets_droppable.find(".template-widget").removeClass("widget-over");
					me.addNodeClass(me.getTemplateWidget(widget_id), "widget-over");
				}
			}
		  },
		  function(event) {
			event.preventDefault();
	  		event.stopPropagation();
			
			var p = $(this).parent();
			
			if (p.hasClass("is-widget")) {
				var widget_id = p.attr("template-target-id");
				
				if (widget_id)
					me.removeNodeClass(me.getTemplateWidget(widget_id), "widget-over");
			}
		});
		
		//set item's click event
		title.click(function(event) {
			event.preventDefault();
	  		event.stopPropagation();
			
			var p = $(this).parent();
			
			if (p.hasClass("is-widget")) {
				var widget_id = p.attr("template-target-id");
				
				if (widget_id)
					me.getTemplateWidget(widget_id).click();
			}
		});
		
		//append item to elm_to_append
		elm_to_append.append(item);
		
		//Add item's children
		var children = element.children();
		
		if (children.length == 0)
			item.addClass("no-childs");
		else 
			$.each(children, function(idx, child) {
				addMenuLayer(item.children("ul"), $(child));
			});
		
		elm_to_append.sortable({
			connectWith: "ul",
			items: ".group",
			handle: "> .group-title > .move",
			placeholder: "place-holder",
			revert: true,
			grid: [5, 5],
			start: function(event, ui_obj) {
				elm_to_append.find(".group:not(.is-droppable)").addClass("group-disabled");
			},
			sort: function(event, ui_obj) {
				var p = ui_obj.placeholder.parent();
				
				if (ui_obj.placeholder.prev().hasClass("is-droppable"))
					ui_obj.placeholder.removeClass("place-holder-hidden").addClass("place-holder-droppable");
				else if (p.hasClass(me.options.menu_layers_class) || p.parent().hasClass("is-droppable"))
					ui_obj.placeholder.removeClass("place-holder-hidden place-holder-droppable");
				else
					ui_obj.placeholder.addClass("place-holder-hidden");
			},
			stop: function(event, ui_obj) {
				elm_to_append.find(".group:not(.is-droppable)").removeClass("group-disabled");
				ui_obj.item.removeClass("group-disabled");
				
				var p = ui_obj.item.parent();
				var widget = ui_obj.item.hasClass("is-widget") ? me.getTemplateWidget(ui_obj.item.attr("template-target-id")) : me.getTemplateDroppable(ui_obj.item.attr("template-target-id"));
				var default_droppable = widget.parent().closest(".main-droppable");
				
				if (ui_obj.item.prev().hasClass("is-droppable")) { //append widget to droppable
					var ml_droppable = ui_obj.item.prev();
					ml_droppable.addClass("group-open").removeClass("no-childs").children("ul").append(ui_obj.item);
					
					var droppable = me.getTemplateDroppable(ml_droppable.attr("template-target-id"));
					
					if (droppable[0])
						droppable.append(widget);
					else
						return false;
				}
				else if (p.parent().hasClass("is-droppable")) { //if is droppable
					var prev = ui_obj.item.prev();
					
					//get the prev widget and insert widget after it
					if (prev[0] && prev.hasClass("is-widget")) {
						var prev_widget = me.getTemplateWidget(prev.attr("template-target-id"));
						if (prev_widget[0])
							widget.insertAfter(prev_widget);
						else
							return false;
					}
					else { //prepend to droppable
						var droppable = me.getTemplateDroppable(p.parent().attr("template-target-id"));
						
						if (droppable[0])
							droppable.prepend(widget);
						else
							return false;
					}
				}
				else if (p.hasClass(me.options.menu_layers_class)) {
					var prev = ui_obj.item.prev();
					
					//get the prev widget and insert widget after it
					if (prev[0] && prev.hasClass("is-widget")) {
						var prev_widget = me.getTemplateWidget(prev.attr("template-target-id"));
						
						if (prev_widget[0])
							widget.insertAfter(prev_widget);
						else
							return false;
					}
					else if (default_droppable[0])
						default_droppable.prepend(widget);
					else if (selected_template_widgets_droppable[0])
						selected_template_widgets_droppable.prepend(widget);
					else //prepend to main menu-layers
						template_widgets_droppable.prepend(widget);
				}
				else
					return false;
				
				if (typeof me.options.on_template_widgets_layout_changed_func == "function")
					me.options.on_template_widgets_layout_changed_func(widget);
			},
		});
		
		title.children(".sort").draggable({
			containment: item.parent(),
			appendTo: item.parent(),
			cursor: "ns-resize",
               tolerance: "pointer",
			opacity: 0.5,
			grid: [5, 5],
			axis: "y",
			helper: function() {
				var clone = item.clone().addClass("sort-clone").css({
					width: item.width() + "px", 
					height: title.height() + "px",
				});
				
				if ($.isNumeric(ui.css("zIndex")))
					clone.css("zIndex", ui.css("zIndex") + 1);
				
				return clone;
			},
			start: function(event, ui_obj) {
				this.place_holder = $('<div class="place-holder"></div>');
				this.place_holder.insertAfter(item);
				
				this.item_parent = item.parent();
				this.item_brothers = this.item_parent.children().not(item);
				
				this.disabled = false;
				
				return true;
			},
			drag: function(event, ui_obj) {
				if (!this.disabled) {
					var sorted = false;
					var this_bkp = this;
					
					$.each(this.item_brothers, function(i, bro) {
						bro = $(bro);
						
						var bro_top = bro.offset().top;
						var bro_bottom = bro_top + bro.outerHeight();
						var bro_left = bro.offset().left;
						var bro_right = bro_left + bro.outerWidth();
						
						if (ui_obj.offset.top >= bro_top && ui_obj.offset.top <= bro_bottom && ui_obj.offset.left >= bro_left && ui_obj.offset.left <= bro_right) {
							this_bkp.place_holder.insertBefore(bro);
							sorted = true;
							return false;
						}
					});
					
					if (!sorted) {
						$.each(this.item_brothers, function(i, bro) {
							bro = $(bro);
							
							var bro_top = bro.offset().top;
							var bro_bottom = bro_top + bro.outerHeight();
							var bro_left = bro.offset().left;
							var bro_right = bro_left + bro.outerWidth();
							
							if (event.clientY >= bro_top && event.clientY <= bro_bottom && event.clientX >= bro_left && event.clientX <= bro_right) {
								this_bkp.place_holder.insertBefore(bro);
								sorted = true;
								return false;
							}
						});
					}
					if (sorted) {
						this.disabled = true;
						var my_this = this;
						
						setTimeout(function() {
							my_this.disabled = false;
						}, 100);
					}
				}
				return true;
			},
			stop: function(event, ui_obj) {
				if (this.place_holder.parent().is( this.item_parent[0] )) {
					var n = this.place_holder.next();
					var widget = item.hasClass("is-widget") ? me.getTemplateWidget(item.attr("template-target-id")) : me.getTemplateDroppable(item.attr("template-target-id"));
					var widget_next = n.hasClass("is-widget") ? me.getTemplateWidget(n.attr("template-target-id")) : me.getTemplateDroppable(n.attr("template-target-id"));
					
					if (n[0] && widget_next[0] && !widget_next.is(widget)) {
						item.insertBefore(n);
						widget.insertBefore(widget_next);
					}
					else {
						this.item_parent.append(item);
						widget.parent().append(widget);
					}
				}
				
				ui_obj.helper.remove();
				this.place_holder.remove();
				
				if (typeof me.options.on_template_widgets_layout_changed_func == "function")
					me.options.on_template_widgets_layout_changed_func(widget);
				
				return true;
			},
		  });
	}
	
	function deleteMenuLayer(element) {
		var ml = getMenuLayer(element);
		
		if (ml) 
			ml.remove();
	}
	
	function getMenuLayer(element) {
		var id = element.data("data-template-id");
		var menu_layer = id ? menu_layers.find('.group[template-target-id="' + id + '"]').first() : null;
		
		return menu_layer && menu_layer[0] ? menu_layer : null;
	}
	
	function selectMenuLayerWidget(widget_id) {
		var groups = menu_layers.find(".group");
		groups.removeClass("selected");
		
		var widget = groups.filter('[template-target-id="' + widget_id + '"]');
		
		if (widget[0]) {
			me.addNodeClass(widget, "selected");
			
			//expand parents so we can see the selected menu layer
			var parents = widget.parentsUntil(menu_layers, ".group").not(".group-open");
			for (var i = parents.length - 1; i >= 0; i--)
				$(parents[i]).find(" > .group-title > .toggle").click();
			
			//move scroll to correspondent layer
			setTimeout(function() {
				if (!isMenuLayerVisible(widget)) 
					menu_layers.scrollTop( menu_layers.scrollTop() + widget.position().top );
			}, 500);
		}
	}
	
	function unselectMenuLayerWidgets() {
		menu_layers.find(".group").removeClass("selected");
	}
	
	function hoverMenuLayerWidget(widget_id) {
		menu_layers.find(".group").removeClass("group-over").filter('[widget-id="' + widget_id + '"]').addClass("group-over");
	}
	
	function unhoverMenuLayerWidget(widget_id) {
		menu_layers.find('.group[widget-id="' + widget_id + '"]').removeClass("group-over");
	}
	
	function isMenuLayerVisible(elm) {
		if (elm[0]) {
			var top = elm.offset().top;
			var height = elm.height();
			
			var ml_top = menu_layers.offset().top;
			var ml_bottom = ml_top + menu_layers.height();
			
			return top > ml_top && top + height < ml_bottom;
		}
	}
	
	/* MENU SETTINGS METHODS */
	
	function initMenuSettings() {
		var html =   '<div class="resize-menu-settings"><div class="button"></div></div>'
				 + '<li class="settings-info">'
				 + '	<label>Selected Widget: <span></span></label>'
				 + '	<i class="zmdi zmdi-close close" title="Hide Settings"></i>'
				 + '	<i class="zmdi zmdi-fullscreen full-screen" title="Toggle Full Screen Settings"></i>'
				 + '	<i class="zmdi zmdi-crop-7-5 toggle-settings-side" title="Toggle Settings Side"></i>'
				 + '</li>'
				 + '<li class="settings-id">'
				 + '	<label>Id: </label>'
				 + '	<input placeHolder="Text id of the element" title="Text id of the element" />'
				 + '</li>'
				 + '<li class="settings-classes">'
				 + '	<label>Classes: </label>'
				 + '	<div class="user-classes">'
				 + '		<i class="zmdi zmdi-plus-square add-class" title="Add class"></i>'
				 + '		<i class="zmdi zmdi-copy copy-classes" title="Copy classes to clipboard"></i>'
				 + '	</div>'
				 + '</li>'
				 + '<li class="settings-tabs">'
				 + '		<ul>'
				 + '			<li class="settings-tab-properties"><a href="#settings-tabs-dummy-container" selector=".settings-properties">Properties</a></li>'
				 + '			<li class="settings-tab-style"><a href="#settings-tabs-dummy-container" selector=".settings-general, .settings-dimension, .settings-typography, .settings-decorations, .settings-others">Style</a></li>'
				 + '			<li class="settings-tab-events"><a href="#settings-tabs-dummy-container" selector=".settings-events">Events</a></li>'
				 + '			<li class="settings-tab-extra"><a href="#settings-tabs-dummy-container" selector=".settings-extra">Attributes</a></li>'
				 + '			<li class="settings-tab-actions"><a href="#settings-tabs-dummy-container" selector=".settings-actions">Actions</a></li>'
				 + '		</ul>'
				 + '		<div id="settings-tabs-dummy-container"></div>'
				 + '</li>'
				 + '<li class="group group-open settings-properties">'
				 + '	<div class="group-title"><i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>Properties</div>'
				 + '	<ul></ul>'
				 + '</li>'
				 + '<li class="group group-open settings-general">'
				 + '	<div class="group-title"><i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>General</div>'
				 + '	<ul>'
				 + '		<li class="settings-title">'
				 + '			<label>Title: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input placeHolder="Title of the element" title="Title of the element" />'
				 + '		</li>'
				 + '		<li class="settings-float buttons-style unique">'
				 + '			<label>Floating: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<i class="zmdi zmdi-format-align-right settings-float-right"></i>'
				 + '			<i class="zmdi zmdi-format-align-left settings-float-left"></i>'
				 + '		</li>'
				 + '		<li class="settings-display">'
				 + '			<label>Display: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<select>'
				 + '				<option value=""></option>'
				 + '				<option value="inline" title="It is used to display an element as an inline element.">inline</option>'
				 + '				<option value="block" title="It is used to display an element as a block element">block</option>'
				 + '				<option value="contents" title="It is used to disappear the container.">contents</option>'
				 + '				<option value="flex" title="It is used to display an element as a block-level flex container.">flex</option>'
				 + '				<option value="grid" title="It is used to display an element as a block-level grid container.">grid</option>'
				 + '				<option value="inline-block" title="It is used to display an element as an inline-level block container.">inline-block</option>'
				 + '				<option value="inline-flex" title="It is used to display an element as an inline-level flex container.">inline-flex</option>'
				 + '				<option value="inline-grid" title="It is used to display an element as an inline-level grid container.">inline-grid</option>'
				 + '				<option value="inline-table" title="It is used to display an inline-level table">inline-table</option>'
				 + '				<option value="list-item" title="It is used to display all the elements in li element.">list-item</option>'
				 + '				<option value="run-in" title="It is used to display an element inline or block level, depending on the context.">run-in</option>'
				 + '				<option value="table" title="It is used to set the behavior as table for all elements.">table</option>'
				 + '				<option value="table-caption" title="It is used to set the behavior as caption for all elements.">table-caption</option>'
				 + '				<option value="table-column-group" title="It is used to set the behavior as column for all elements.">table-column-group</option>'
				 + '				<option value="table-header-group" title="It is used to set the behavior as header for all elements.">table-header-group</option>'
				 + '				<option value="table-footer-group" title="It is used to set the behavior as footer for all elements.">table-footer-group</option>'
				 + '				<option value="table-row-group" title="It is used to set the behavior as row for all elements.">table-row-group</option>'
				 + '				<option value="table-cell" title="It is used to set the behavior as td for all elements.">table-cell</option>'
				 + '				<option value="table-column" title="It is used to set the behavior as col for all elements.">table-column</option>'
				 + '				<option value="table-row" title="It is used to set the behavior as tr for all elements.">table-row</option>'
				 + '				<option value="initial" title="It is used to set the default value.">initial</option>'
				 + '				<option value="inherit" title="It is used to inherit property from its parents elements.">inherit</option>'
				 + '				<option value="revert">revert</option>'
				 + '				<option value="unset">unset</option>'
				 + '				<option value="none" title="It is used to remove the element.">none</option>'
				 + '			</select>'
				 + '		</li>'
				 + '		<li class="settings-position">'
				 + '			<label>Position: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<select>'
				 + '				<option value=""></option>'
				 + '				<option value="static" title="Default value. Elements render in order, as they appear in the document flow">static</option>'
				 + '				<option value="relative" title="The element is positioned relative to its normal position, so \'left:20px\' adds 20 pixels to the element LEFT position">relative</option>'
				 + '				<option value="absolute" title="The element is positioned relative to its first positioned (not static) ancestor element">absolute</option>'
				 + '				<option value="fixed" title="The element is positioned relative to the browser window">fixed</option>'
				 + '				<option value="sticky" title="The element is positioned based on the user\'s scroll position A sticky element toggles between relative and fixed, depending on the scroll position. It is positioned relative until a given offset position is met in the viewport - then it "sticks" in place (like position:fixed). Note: Not supported in IE/Edge 15 or earlier. Supported in Safari from version 6.1 with a -webkit- prefix.">sticky</option>'
				 + '				<option value="inherit" title="Inherits this property from its parent element.">inherit</option>'
				 + '				<option value="initial" title="Sets this property to its default value.">initial</option>'
				 + '				<option value="revert">revert</option>'
				 + '				<option value="unset">unset</option>'
				 + '			</select>'
				 + '		</li>'
				 + '		<li class="settings-top measurement-style">'
				 + '			<label>Top: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<select>'
				 + '				<option></option>'
				 + '				<option>px</option>'
				 + '				<option>%</option>'
				 + '				<option>em</option>'
				 + '				<option>rem</option>'
				 + '				<option>pt</option>'
				 + '				<option>vw</option>'
				 + '				<option>vh</option>'
				 + '			</select>'
				 + '			<input />'
				 + '		</li>'
				 + '		<li class="settings-bottom measurement-style">'
				 + '			<label>Bottom: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<select>'
				 + '				<option></option>'
				 + '				<option>px</option>'
				 + '				<option>%</option>'
				 + '				<option>em</option>'
				 + '				<option>rem</option>'
				 + '				<option>pt</option>'
				 + '				<option>vw</option>'
				 + '				<option>vh</option>'
				 + '			</select>'
				 + '			<input />'
				 + '		</li>'
				 + '		<li class="settings-left measurement-style">'
				 + '			<label>Left: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<select>'
				 + '				<option></option>'
				 + '				<option>px</option>'
				 + '				<option>%</option>'
				 + '				<option>em</option>'
				 + '				<option>rem</option>'
				 + '				<option>pt</option>'
				 + '				<option>vw</option>'
				 + '				<option>vh</option>'
				 + '			</select>'
				 + '			<input />'
				 + '		</li>'
				 + '		<li class="settings-right measurement-style">'
				 + '			<label>Right: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<select>'
				 + '				<option></option>'
				 + '				<option>px</option>'
				 + '				<option>%</option>'
				 + '				<option>em</option>'
				 + '				<option>rem</option>'
				 + '				<option>pt</option>'
				 + '				<option>vw</option>'
				 + '				<option>vh</option>'
				 + '			</select>'
				 + '			<input />'
				 + '		</li>'
				 + '	</ul>'
				 + '</li>'
				 + '<li class="group group-open settings-dimension">'
				 + '	<div class="group-title"><i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>Dimension</div>'
				 + '	<ul>'
				 + '		<li class="settings-width measurement-style">'
				 + '			<label>Width: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<select>'
				 + '				<option></option>'
				 + '				<option>px</option>'
				 + '				<option>%</option>'
				 + '				<option>em</option>'
				 + '				<option>rem</option>'
				 + '				<option>pt</option>'
				 + '				<option>vw</option>'
				 + '				<option>vh</option>'
				 + '			</select>'
				 + '			<input placeHolder="auto" />'
				 + '		</li>'
				 + '		<li class="settings-height measurement-style">'
				 + '			<label>Height: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<select>'
				 + '				<option></option>'
				 + '				<option>px</option>'
				 + '				<option>%</option>'
				 + '				<option>em</option>'
				 + '				<option>rem</option>'
				 + '				<option>pt</option>'
				 + '				<option>vw</option>'
				 + '				<option>vh</option>'
				 + '			</select>'
				 + '			<input placeHolder="auto" />'
				 + '		</li>'
				 + '		<li class="settings-max-width measurement-style">'
				 + '			<label>Max Width: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<select>'
				 + '				<option></option>'
				 + '				<option>px</option>'
				 + '				<option>%</option>'
				 + '				<option>em</option>'
				 + '				<option>rem</option>'
				 + '				<option>pt</option>'
				 + '				<option>vw</option>'
				 + '				<option>vh</option>'
				 + '			</select>'
				 + '			<input />'
				 + '		</li>'
				 + '		<li class="settings-max-height measurement-style">'
				 + '			<label>Max Height: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<select>'
				 + '				<option></option>'
				 + '				<option>px</option>'
				 + '				<option>%</option>'
				 + '				<option>em</option>'
				 + '				<option>rem</option>'
				 + '				<option>pt</option>'
				 + '				<option>vw</option>'
				 + '				<option>vh</option>'
				 + '			</select>'
				 + '			<input />'
				 + '		</li>'
				 + '		<li class="settings-min-width measurement-style">'
				 + '			<label>Min Width: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<select>'
				 + '				<option></option>'
				 + '				<option>px</option>'
				 + '				<option>%</option>'
				 + '				<option>em</option>'
				 + '				<option>rem</option>'
				 + '				<option>pt</option>'
				 + '				<option>vw</option>'
				 + '				<option>vh</option>'
				 + '			</select>'
				 + '			<input />'
				 + '		</li>'
				 + '		<li class="settings-min-height measurement-style">'
				 + '			<label>Min Height: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<select>'
				 + '				<option></option>'
				 + '				<option>px</option>'
				 + '				<option>%</option>'
				 + '				<option>em</option>'
				 + '				<option>rem</option>'
				 + '				<option>pt</option>'
				 + '				<option>vw</option>'
				 + '				<option>vh</option>'
				 + '			</select>'
				 + '			<input />'
				 + '		</li>'
				 + '		<li class="settings-margin">'
				 + '			<label>Margin: </label>'
				 + '			<ul class="group-block">'
				 + '				<li class="settings-margin-top measurement-style">'
				 + '					<label>Top: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<select>'
				 + '						<option></option>'
				 + '						<option>px</option>'
				 + '						<option>%</option>'
				 + '						<option>em</option>'
				 + '						<option>rem</option>'
				 + '						<option>pt</option>'
				 + '						<option>vw</option>'
				 + '						<option>vh</option>'
				 + '					</select>'
				 + '					<input />'
				 + '				</li>'
				 + '				<li class="settings-margin-bottom measurement-style">'
				 + '					<label>Bottom: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<select>'
				 + '						<option></option>'
				 + '						<option>px</option>'
				 + '						<option>%</option>'
				 + '						<option>em</option>'
				 + '						<option>rem</option>'
				 + '						<option>pt</option>'
				 + '						<option>vw</option>'
				 + '						<option>vh</option>'
				 + '					</select>'
				 + '					<input />'
				 + '				</li>'
				 + '				<li class="settings-margin-left measurement-style">'
				 + '					<label>Left: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<select>'
				 + '						<option></option>'
				 + '						<option>px</option>'
				 + '						<option>%</option>'
				 + '						<option>em</option>'
				 + '						<option>rem</option>'
				 + '						<option>pt</option>'
				 + '						<option>vw</option>'
				 + '						<option>vh</option>'
				 + '					</select>'
				 + '					<input />'
				 + '				</li>'
				 + '				<li class="settings-margin-right measurement-style">'
				 + '					<label>Right: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<select>'
				 + '						<option></option>'
				 + '						<option>px</option>'
				 + '						<option>%</option>'
				 + '						<option>em</option>'
				 + '						<option>rem</option>'
				 + '						<option>pt</option>'
				 + '						<option>vw</option>'
				 + '						<option>vh</option>'
				 + '					</select>'
				 + '					<input />'
				 + '				</li>'
				 + '			</ul>'
				 + '		</li>'
				 + '		<li class="settings-padding">'
				 + '			<label>Padding: </label>'
				 + '			<ul class="group-block">'
				 + '				<li class="settings-padding-top measurement-style">'
				 + '					<label>Top: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<select>'
				 + '						<option></option>'
				 + '						<option>px</option>'
				 + '						<option>%</option>'
				 + '						<option>em</option>'
				 + '						<option>rem</option>'
				 + '						<option>pt</option>'
				 + '						<option>vw</option>'
				 + '						<option>vh</option>'
				 + '					</select>'
				 + '					<input />'
				 + '				</li>'
				 + '				<li class="settings-padding-bottom measurement-style">'
				 + '					<label>Bottom: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<select>'
				 + '						<option></option>'
				 + '						<option>px</option>'
				 + '						<option>%</option>'
				 + '						<option>em</option>'
				 + '						<option>rem</option>'
				 + '						<option>pt</option>'
				 + '						<option>vw</option>'
				 + '						<option>vh</option>'
				 + '					</select>'
				 + '					<input />'
				 + '				</li>'
				 + '				<li class="settings-padding-left measurement-style">'
				 + '					<label>Left: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<select>'
				 + '						<option></option>'
				 + '						<option>px</option>'
				 + '						<option>%</option>'
				 + '						<option>em</option>'
				 + '						<option>rem</option>'
				 + '						<option>pt</option>'
				 + '						<option>vw</option>'
				 + '						<option>vh</option>'
				 + '					</select>'
				 + '					<input />'
				 + '				</li>'
				 + '				<li class="settings-padding-right measurement-style">'
				 + '					<label>Right: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<select>'
				 + '						<option></option>'
				 + '						<option>px</option>'
				 + '						<option>%</option>'
				 + '						<option>em</option>'
				 + '						<option>rem</option>'
				 + '						<option>pt</option>'
				 + '						<option>vw</option>'
				 + '						<option>vh</option>'
				 + '					</select>'
				 + '					<input />'
				 + '				</li>'
				 + '			</ul>'
				 + '		</li>'
				 + '	</ul>'
				 + '</li>'
				 + '<li class="group group-open settings-typography">'
				 + '	<div class="group-title"><i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>Typography</div>'
				 + '	<ul>'
				 + '		<li class="settings-font-family">'
				 + '			<label>Font: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<select>'
				 + '				<option value=""></option>'
				 + '				<option value="Arial, Helvetica, sans-serif">Arial</option>'
				 + '				<option value="Arial Black, Gadget, sans-serif">Arial Black</option>'
				 + '				<option value="Brush Script MT, sans-serif">Brush Script MT</option>'
				 + '				<option value="Comic Sans MS, cursive, sans-serif">Comic Sans MS</option>'
				 + '				<option value="Courier New, Courier, monospace">Courier New</option>'
				 + '				<option value="Georgia, serif">Georgia</option>'
				 + '				<option value="Helvetica, serif">Helvetica</option>'
				 + '				<option value="Impact, Charcoal, sans-serif">Impact</option>'
				 + '				<option value="Lucida Sans Unicode, Lucida Grande, sans-serif">Lucida Sans Unicode</option>'
				 + '				<option value="Tahoma, Geneva, sans-serif">Tahoma</option>'
				 + '				<option value="Times New Roman, Times, serif">Times New Roman</option>'
				 + '				<option value="Trebuchet MS, Helvetica, sans-serif">Trebuchet MS</option>'
				 + '				<option value="Verdana, Geneva, sans-serif">Verdana</option>'
				 + '				<option value="inherit">inherit</option>'
				 + '				<option value="initial">initial</option>'
				 + '				<option value="revert">revert</option>'
				 + '				<option value="unset">unset</option>'
				 + '			</select>'
				 + '		</li>'
				 + '		<li class="settings-font-size measurement-style">'
				 + '			<label>Font Size: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<select>'
				 + '				<option></option>'
				 + '				<option>px</option>'
				 + '				<option>%</option>'
				 + '				<option>em</option>'
				 + '				<option>rem</option>'
				 + '				<option>pt</option>'
				 + '				<option>vw</option>'
				 + '				<option>vh</option>'
				 + '			</select>'
				 + '			<input />'
				 + '		</li>'
				 + '		<li class="settings-font-weight">'
				 + '			<label>Weight: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<select>'
				 + '				<option value=""></option>'
				 + '				<option value="100">100 - thin</option>'
				 + '				<option value="200">200 - extra-light</option>'
				 + '				<option value="300">300 - light</option>'
				 + '				<option value="400">400 - normal</option>'
				 + '				<option value="500">500 - medium</option>'
				 + '				<option value="600">600 - semi-bold</option>'
				 + '				<option value="700">700 - bold</option>'
				 + '				<option value="800">800 - extra-bold</option>'
				 + '				<option value="900">900 - ultra-bold</option>'
				 + '				<option value="bold">bold</option>'
				 + '				<option value="bolder">bolder</option>'
				 + '				<option value="lighter">lighter</option>'
				 + '				<option value="normal">normal</option>'
				 + '				<option value="inherit">inherit</option>'
				 + '				<option value="initial">initial</option>'
				 + '				<option value="revert">revert</option>'
				 + '				<option value="unset">unset</option>'
				 + '			</select>'
				 + '		</li>'
				 + '		<li class="settings-font-style">'
				 + '			<label>Style: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<select>'
				 + '				<option value=""></option>'
				 + '				<option value="italic">Italic</option>'
				 + '				<option value="oblique">Oblique</option>'
				 + '				<option value="normal">Normal</option>'
				 + '				<option value="inherit">inherit</option>'
				 + '				<option value="initial">initial</option>'
				 + '				<option value="revert">revert</option>'
				 + '				<option value="unset">unset</option>'
				 + '			</select>'
				 + '		</li>'
				 + '		<li class="settings-letter-spacing measurement-style">'
				 + '			<label>Letter-Spacing: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<select>'
				 + '				<option></option>'
				 + '				<option>px</option>'
				 + '				<option>%</option>'
				 + '				<option>em</option>'
				 + '				<option>rem</option>'
				 + '				<option>pt</option>'
				 + '				<option>vw</option>'
				 + '				<option>vh</option>'
				 + '			</select>'
				 + '			<input />'
				 + '		</li>'
				 + '		<li class="settings-color color-style">'
				 + '			<label>Color: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input type="color" class="color-selector" />'
				 + '			<input class="color-code" />'
				 + '		</li>'
				 + '		<li class="settings-line-height measurement-style">'
				 + '			<label>Line Height: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<select>'
				 + '				<option></option>'
				 + '				<option>px</option>'
				 + '				<option>%</option>'
				 + '				<option>em</option>'
				 + '				<option>rem</option>'
				 + '				<option>pt</option>'
				 + '				<option>vw</option>'
				 + '				<option>vh</option>'
				 + '			</select>'
				 + '			<input />'
				 + '		</li>'
				 + '		<li class="settings-text-align buttons-style unique buttons-small-style">'
				 + '			<label>Text Alignment: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<i class="zmdi zmdi-format-align-right settings-text-align-right"></i>'
				 + '			<i class="zmdi zmdi-format-align-center settings-text-align-center"></i>'
				 + '			<i class="zmdi zmdi-format-align-left settings-text-align-left"></i>'
				 + '			<i class="zmdi zmdi-format-align-justify settings-text-align-justify"></i>'
				 + '		</li>'
				 + '		<li class="settings-vertical-align buttons-style unique buttons-small-style">'
				 + '			<label>Vertical Alignment: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<i class="zmdi zmdi-format-valign-top settings-vertical-align-top"></i>'
				 + '			<i class="zmdi zmdi-format-valign-center settings-vertical-align-middle"></i>'
				 + '			<i class="zmdi zmdi-format-valign-bottom settings-vertical-align-bottom"></i>'
				 + '		</li>'
				 + '		<li class="settings-text-decoration buttons-style buttons-small-style">'
				 + '			<label>Text Decoration:</label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<i class="zmdi zmdi-format-underlined settings-text-decoration-underline"></i>'
				 + '			<i class="zmdi zmdi-format-strikethrough-s settings-text-decoration-line-through"></i>'
				 + '		</li>'
				 + '	</ul>'
				 + '</li>'
				 + '<li class="group group-open settings-decorations">'
				 + '	<div class="group-title"><i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>Decorations</div>'
				 + '	<ul>'
				 + '		<li class="settings-border">'
				 + '			<label>Border: </label>'
				 + '			<ul class="group-block">'
				 + '				<li class="settings-border-width measurement-style">'
				 + '					<label>Width: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<select>'
				 + '						<option></option>'
				 + '						<option>px</option>'
				 + '						<option>%</option>'
				 + '						<option>em</option>'
				 + '						<option>rem</option>'
				 + '						<option>pt</option>'
				 + '						<option>vw</option>'
				 + '						<option>vh</option>'
				 + '					</select>'
				 + '					<input />'
				 + '				</li>'
				 + '				<li class="settings-border-style">'
				 + '					<label>Style: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<select>'
				 + '						<option value=""></option>'
				 + '						<option value="none">none</option>'
				 + '						<option value="solid">solid</option>'
				 + '						<option value="dotted">dotted</option>'
				 + '						<option value="dashed">dashed</option>'
				 + '						<option value="double">double</option>'
				 + '						<option value="groove">groove</option>'
				 + '						<option value="ridge">ridge</option>'
				 + '						<option value="inset">inset</option>'
				 + '						<option value="outset">outset</option>'
				 + '						<option value="inherit">inherit</option>'
				 + '						<option value="initial">initial</option>'
				 + '						<option value="revert">revert</option>'
				 + '						<option value="unset">unset</option>'
				 + '					</select>'
				 + '				</li>'
				 + '				<li class="settings-border-color color-style">'
				 + '					<label>Color: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<input type="color" class="color-selector" />'
				 + '					<input class="color-code" />'
				 + '				</li>'
				 + '			</ul>'
				 + '		</li>'
				 + '		<li class="settings-border-top">'
				 + '			<label>Border Top: </label>'
				 + '			<ul class="group-block">'
				 + '				<li class="settings-border-top-width measurement-style">'
				 + '					<label>Width: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<select>'
				 + '						<option></option>'
				 + '						<option>px</option>'
				 + '						<option>%</option>'
				 + '						<option>em</option>'
				 + '						<option>rem</option>'
				 + '						<option>pt</option>'
				 + '						<option>vw</option>'
				 + '						<option>vh</option>'
				 + '					</select>'
				 + '					<input />'
				 + '				</li>'
				 + '				<li class="settings-border-top-style">'
				 + '					<label>Style: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<select>'
				 + '						<option value=""></option>'
				 + '						<option value="none">none</option>'
				 + '						<option value="solid">solid</option>'
				 + '						<option value="dotted">dotted</option>'
				 + '						<option value="dashed">dashed</option>'
				 + '						<option value="double">double</option>'
				 + '						<option value="groove">groove</option>'
				 + '						<option value="ridge">ridge</option>'
				 + '						<option value="inset">inset</option>'
				 + '						<option value="outset">outset</option>'
				 + '						<option value="inherit">inherit</option>'
				 + '						<option value="initial">initial</option>'
				 + '						<option value="revert">revert</option>'
				 + '						<option value="unset">unset</option>'
				 + '					</select>'
				 + '				</li>'
				 + '				<li class="settings-border-top-color color-style">'
				 + '					<label>Color: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<input type="color" class="color-selector" />'
				 + '					<input class="color-code" />'
				 + '				</li>'
				 + '			</ul>'
				 + '		</li>'
				 + '		<li class="settings-border-bottom">'
				 + '			<label>Border Bottom: </label>'
				 + '			<ul class="group-block">'
				 + '				<li class="settings-border-bottom-width measurement-style">'
				 + '					<label>Width: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<select>'
				 + '						<option></option>'
				 + '						<option>px</option>'
				 + '						<option>%</option>'
				 + '						<option>em</option>'
				 + '						<option>rem</option>'
				 + '						<option>pt</option>'
				 + '						<option>vw</option>'
				 + '						<option>vh</option>'
				 + '					</select>'
				 + '					<input />'
				 + '				</li>'
				 + '				<li class="settings-border-bottom-style">'
				 + '					<label>Style: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<select>'
				 + '						<option value=""></option>'
				 + '						<option value="none">none</option>'
				 + '						<option value="solid">solid</option>'
				 + '						<option value="dotted">dotted</option>'
				 + '						<option value="dashed">dashed</option>'
				 + '						<option value="double">double</option>'
				 + '						<option value="groove">groove</option>'
				 + '						<option value="ridge">ridge</option>'
				 + '						<option value="inset">inset</option>'
				 + '						<option value="outset">outset</option>'
				 + '						<option value="inherit">inherit</option>'
				 + '						<option value="initial">initial</option>'
				 + '						<option value="revert">revert</option>'
				 + '						<option value="unset">unset</option>'
				 + '					</select>'
				 + '				</li>'
				 + '				<li class="settings-border-bottom-color color-style">'
				 + '					<label>Color: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<input type="color" class="color-selector" />'
				 + '					<input class="color-code" />'
				 + '				</li>'
				 + '			</ul>'
				 + '		</li>'
				 + '		<li class="settings-border-left">'
				 + '			<label>Border Left: </label>'
				 + '			<ul class="group-block">'
				 + '				<li class="settings-border-left-width measurement-style">'
				 + '					<label>Width: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<select>'
				 + '						<option></option>'
				 + '						<option>px</option>'
				 + '						<option>%</option>'
				 + '						<option>em</option>'
				 + '						<option>rem</option>'
				 + '						<option>pt</option>'
				 + '						<option>vw</option>'
				 + '						<option>vh</option>'
				 + '					</select>'
				 + '					<input />'
				 + '				</li>'
				 + '				<li class="settings-border-left-style">'
				 + '					<label>Style: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<select>'
				 + '						<option value=""></option>'
				 + '						<option value="none">none</option>'
				 + '						<option value="solid">solid</option>'
				 + '						<option value="dotted">dotted</option>'
				 + '						<option value="dashed">dashed</option>'
				 + '						<option value="double">double</option>'
				 + '						<option value="groove">groove</option>'
				 + '						<option value="ridge">ridge</option>'
				 + '						<option value="inset">inset</option>'
				 + '						<option value="outset">outset</option>'
				 + '						<option value="inherit">inherit</option>'
				 + '						<option value="initial">initial</option>'
				 + '						<option value="revert">revert</option>'
				 + '						<option value="unset">unset</option>'
				 + '					</select>'
				 + '				</li>'
				 + '				<li class="settings-border-left-color color-style">'
				 + '					<label>Color: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<input type="color" class="color-selector" />'
				 + '					<input class="color-code" />'
				 + '				</li>'
				 + '			</ul>'
				 + '		</li>'
				 + '		<li class="settings-border-right">'
				 + '			<label>Border Right: </label>'
				 + '			<ul class="group-block">'
				 + '				<li class="settings-border-right-width measurement-style">'
				 + '					<label>Width: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<select>'
				 + '						<option></option>'
				 + '						<option>px</option>'
				 + '						<option>%</option>'
				 + '						<option>em</option>'
				 + '						<option>rem</option>'
				 + '						<option>pt</option>'
				 + '						<option>vw</option>'
				 + '						<option>vh</option>'
				 + '					</select>'
				 + '					<input />'
				 + '				</li>'
				 + '				<li class="settings-border-right-style">'
				 + '					<label>Style: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<select>'
				 + '						<option value=""></option>'
				 + '						<option value="none">none</option>'
				 + '						<option value="solid">solid</option>'
				 + '						<option value="dotted">dotted</option>'
				 + '						<option value="dashed">dashed</option>'
				 + '						<option value="double">double</option>'
				 + '						<option value="groove">groove</option>'
				 + '						<option value="ridge">ridge</option>'
				 + '						<option value="inset">inset</option>'
				 + '						<option value="outset">outset</option>'
				 + '						<option value="inherit">inherit</option>'
				 + '						<option value="initial">initial</option>'
				 + '						<option value="revert">revert</option>'
				 + '						<option value="unset">unset</option>'
				 + '					</select>'
				 + '				</li>'
				 + '				<li class="settings-border-right-color color-style">'
				 + '					<label>Color: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<input type="color" class="color-selector" />'
				 + '					<input class="color-code" />'
				 + '				</li>'
				 + '			</ul>'
				 + '		</li>'
				 + '		<li class="settings-border-radius">'
				 + '			<label>Border Radius: </label>'
				 + '			<ul class="group-block">'
				 + '				<li class="settings-border-top-left-radius measurement-style">'
				 + '					<label>Top Left: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<select>'
				 + '						<option></option>'
				 + '						<option>px</option>'
				 + '						<option>%</option>'
				 + '						<option>em</option>'
				 + '						<option>rem</option>'
				 + '						<option>pt</option>'
				 + '						<option>vw</option>'
				 + '						<option>vh</option>'
				 + '					</select>'
				 + '					<input />'
				 + '				</li>'
				 + '				<li class="settings-border-top-right-radius measurement-style">'
				 + '					<label>Top Right: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<select>'
				 + '						<option></option>'
				 + '						<option>px</option>'
				 + '						<option>%</option>'
				 + '						<option>em</option>'
				 + '						<option>rem</option>'
				 + '						<option>pt</option>'
				 + '						<option>vw</option>'
				 + '						<option>vh</option>'
				 + '					</select>'
				 + '					<input />'
				 + '				</li>'
				 + '				<li class="settings-border-bottom-left-radius measurement-style">'
				 + '					<label>Bottom Left: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<select>'
				 + '						<option></option>'
				 + '						<option>px</option>'
				 + '						<option>%</option>'
				 + '						<option>em</option>'
				 + '						<option>rem</option>'
				 + '						<option>pt</option>'
				 + '						<option>vw</option>'
				 + '						<option>vh</option>'
				 + '					</select>'
				 + '					<input />'
				 + '				</li>'
				 + '				<li class="settings-border-bottom-right-radius measurement-style">'
				 + '					<label>Bottom Right: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<select>'
				 + '						<option></option>'
				 + '						<option>px</option>'
				 + '						<option>%</option>'
				 + '						<option>em</option>'
				 + '						<option>rem</option>'
				 + '						<option>pt</option>'
				 + '						<option>vw</option>'
				 + '						<option>vh</option>'
				 + '					</select>'
				 + '					<input />'
				 + '				</li>'
				 + '			</ul>'
				 + '		</li>'
				 + '		<li class="settings-bg">'
				 + '			<label>Background: </label>'
				 + '			<ul class="group-block">'
				 + '				<li class="settings-background-color color-style">'
				 + '					<label>Color: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<input type="color" class="color-selector" />'
				 + '					<input class="color-code" />'
				 + '				</li>'
				 + '				<li class="settings-background-image">'
				 + '					<label>Image: </label>'
				 + '					<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '					<input />'
				 + '					<span class="zmdi zmdi-search-in-file search" title="Choose existent image"></span>'
				 + '				</li>'
				 + '			</ul>'
				 + '		</li>'
				 + '		<li class="settings-opacity">'
				 + '			<label>Opacity: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input />'
				 + '		</li>'
				 + '	</ul>'
				 + '</li>'
				 + '<li class="group group-open settings-events">'
				 + '	<div class="group-title"><i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>Events <span class="zmdi zmdi-plus-square widget-group-item-add" title="Add a new event type"></span></div>'
				 + '	<ul>'
				 + '		<li class="settings-onclick with-extra-events">'
				 + '			<label>On Click: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input placeHolder="JS function call" title="Javascript function call" />'
				 + 			(typeof me.options.on_choose_event_func == "function" ? '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined events"></i>' : '')
				 + '			<i class="zmdi zmdi-plus-square add-extra-event" title="Add new event"></i>'
				 + '			<ul class="extra-events"></ul>'
				 + '		</li>'
				 + '		<li class="settings-onfocus with-extra-events">'
				 + '			<label>On Focus: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input placeHolder="JS function call" title="Javascript function call" />'
				 + 			(typeof me.options.on_choose_event_func == "function" ? '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined events"></i>' : '')
				 + '			<i class="zmdi zmdi-plus-square add-extra-event" title="Add new event"></i>'
				 + '			<ul class="extra-events"></ul>'
				 + '		</li>'
				 + '		<li class="settings-onblur with-extra-events">'
				 + '			<label>On Blur: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input placeHolder="JS function call" title="Javascript function call" />'
				 + 			(typeof me.options.on_choose_event_func == "function" ? '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined events"></i>' : '')
				 + '			<i class="zmdi zmdi-plus-square add-extra-event" title="Add new event"></i>'
				 + '			<ul class="extra-events"></ul>'
				 + '		</li>'
				 + '		<li class="settings-onchange with-extra-events">'
				 + '			<label>On Change: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input placeHolder="JS function call" title="Javascript function call" />'
				 + 			(typeof me.options.on_choose_event_func == "function" ? '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined events"></i>' : '')
				 + '			<i class="zmdi zmdi-plus-square add-extra-event" title="Add new event"></i>'
				 + '			<ul class="extra-events"></ul>'
				 + '		</li>'
				 + '		<li class="settings-onkeypress with-extra-events">'
				 + '			<label>On Key Press: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input placeHolder="JS function call" title="Javascript function call" />'
				 + 			(typeof me.options.on_choose_event_func == "function" ? '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined events"></i>' : '')
				 + '			<i class="zmdi zmdi-plus-square add-extra-event" title="Add new event"></i>'
				 + '			<ul class="extra-events"></ul>'
				 + '		</li>'
				 + '		<li class="settings-onkeyup with-extra-events">'
				 + '			<label>On Key Up: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input placeHolder="JS function call" title="Javascript function call" />'
				 + 			(typeof me.options.on_choose_event_func == "function" ? '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined events"></i>' : '')
				 + '			<i class="zmdi zmdi-plus-square add-extra-event" title="Add new event"></i>'
				 + '			<ul class="extra-events"></ul>'
				 + '		</li>'
				 + '		<li class="settings-onkeydown with-extra-events">'
				 + '			<label>On Key Down: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input placeHolder="JS function call" title="Javascript function call" />'
				 + 			(typeof me.options.on_choose_event_func == "function" ? '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined events"></i>' : '')
				 + '			<i class="zmdi zmdi-plus-square add-extra-event" title="Add new event"></i>'
				 + '			<ul class="extra-events"></ul>'
				 + '		</li>'
				 + '		<li class="settings-onmouseover with-extra-events">'
				 + '			<label>On Mouse Over: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input placeHolder="JS function call" title="Javascript function call" />'
				 + 			(typeof me.options.on_choose_event_func == "function" ? '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined events"></i>' : '')
				 + '			<i class="zmdi zmdi-plus-square add-extra-event" title="Add new event"></i>'
				 + '			<ul class="extra-events"></ul>'
				 + '		</li>'
				 + '		<li class="settings-onmousedown with-extra-events">'
				 + '			<label>On Mouse Down: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input placeHolder="JS function call" title="Javascript function call" />'
				 + 			(typeof me.options.on_choose_event_func == "function" ? '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined events"></i>' : '')
				 + '			<i class="zmdi zmdi-plus-square add-extra-event" title="Add new event"></i>'
				 + '			<ul class="extra-events"></ul>'
				 + '		</li>'
				 + '		<li class="settings-onmouseenter with-extra-events">'
				 + '			<label>On Mouse Enter: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input placeHolder="JS function call" title="Javascript function call" />'
				 + 			(typeof me.options.on_choose_event_func == "function" ? '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined events"></i>' : '')
				 + '			<i class="zmdi zmdi-plus-square add-extra-event" title="Add new event"></i>'
				 + '			<ul class="extra-events"></ul>'
				 + '		</li>'
				 + '		<li class="settings-onmouseout with-extra-events">'
				 + '			<label>On Mouse Out: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input placeHolder="JS function call" title="Javascript function call" />'
				 + 			(typeof me.options.on_choose_event_func == "function" ? '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined events"></i>' : '')
				 + '			<i class="zmdi zmdi-plus-square add-extra-event" title="Add new event"></i>'
				 + '			<ul class="extra-events"></ul>'
				 + '		</li>'
				 + '		<li class="settings-onmouseleave with-extra-events">'
				 + '			<label>On Mouse Leave: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input placeHolder="JS function call" title="Javascript function call" />'
				 + 			(typeof me.options.on_choose_event_func == "function" ? '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined events"></i>' : '')
				 + '			<i class="zmdi zmdi-plus-square add-extra-event" title="Add new event"></i>'
				 + '			<ul class="extra-events"></ul>'
				 + '		</li>'
				 + '		<li class="settings-ondrag with-extra-events">'
				 + '			<label>On Drag: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input placeHolder="JS function call" title="Javascript function call" />'
				 + 			(typeof me.options.on_choose_event_func == "function" ? '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined events"></i>' : '')
				 + '			<i class="zmdi zmdi-plus-square add-extra-event" title="Add new event"></i>'
				 + '			<ul class="extra-events"></ul>'
				 + '		</li>'
				 + '		<li class="settings-ondragstart with-extra-events">'
				 + '			<label>On Drag Start: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input placeHolder="JS function call" title="Javascript function call" />'
				 + 			(typeof me.options.on_choose_event_func == "function" ? '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined events"></i>' : '')
				 + '			<i class="zmdi zmdi-plus-square add-extra-event" title="Add new event"></i>'
				 + '			<ul class="extra-events"></ul>'
				 + '		</li>'
				 + '		<li class="settings-ondragend with-extra-events">'
				 + '			<label>On Drag End: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input placeHolder="JS function call" title="Javascript function call" />'
				 + 			(typeof me.options.on_choose_event_func == "function" ? '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined events"></i>' : '')
				 + '			<i class="zmdi zmdi-plus-square add-extra-event" title="Add new event"></i>'
				 + '			<ul class="extra-events"></ul>'
				 + '		</li>'
				 + '		<li class="settings-ondragenter with-extra-events">'
				 + '			<label>On Drag Enter: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input placeHolder="JS function call" title="Javascript function call" />'
				 + 			(typeof me.options.on_choose_event_func == "function" ? '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined events"></i>' : '')
				 + '			<i class="zmdi zmdi-plus-square add-extra-event" title="Add new event"></i>'
				 + '			<ul class="extra-events"></ul>'
				 + '		</li>'
				 + '		<li class="settings-ondragleave with-extra-events">'
				 + '			<label>On Drag Leave: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input placeHolder="JS function call" title="Javascript function call" />'
				 + 			(typeof me.options.on_choose_event_func == "function" ? '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined events"></i>' : '')
				 + '			<i class="zmdi zmdi-plus-square add-extra-event" title="Add new event"></i>'
				 + '			<ul class="extra-events"></ul>'
				 + '		</li>'
				 + '		<li class="settings-ondragover with-extra-events">'
				 + '			<label>On Drag Over: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input placeHolder="JS function call" title="Javascript function call" />'
				 + 			(typeof me.options.on_choose_event_func == "function" ? '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined events"></i>' : '')
				 + '			<i class="zmdi zmdi-plus-square add-extra-event" title="Add new event"></i>'
				 + '			<ul class="extra-events"></ul>'
				 + '		</li>'
				 + '		<li class="settings-ondrop with-extra-events">'
				 + '			<label>On Drop: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<input placeHolder="JS function call" title="Javascript function call" />'
				 + 			(typeof me.options.on_choose_event_func == "function" ? '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined events"></i>' : '')
				 + '			<i class="zmdi zmdi-plus-square add-extra-event" title="Add new event"></i>'
				 + '			<ul class="extra-events"></ul>'
				 + '		</li>'
				 + '	</ul>'
				 + '</li>'
				 + '<li class="group group-open settings-others">'
				 + '	<div class="group-title"><i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>Other Styling</div>'
				 + '	<ul>'
				 + '		<li class="settings-style">'
				 + '			<label>Style: </label>'
				 + '			<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
				 + '			<textarea></textarea>'
				 + '		</li>'
				 + '	</ul>'
				 + '</li>'
				 + '<li class="group group-open settings-extra">'
				 + '	<div class="group-title"><i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>Extra</div>'
				 + '	<ul>'
				 + '		<li class="settings-attributes">'
				 + '			<label>Attributes: </label>'
				 + '			<i class="zmdi zmdi-hc-lg zmdi-plus add" title="Add new attribute"></i>'
				 + '			<input />'
				 + '			<ul>'
				 + '				<li class="settings-empty-attributes">No extra attributes defined...</li>'
				 + '			</ul>'
				 + '		</li>'
				 + '	</ul>'
				 + '</li>'
				 + '<li class="group group-open settings-actions">'
				 + '	<div class="group-title"><i class="zmdi zmdi-caret-down toggle" title="Toggle sub-Options"></i>Actions</div>'
				 + '	<ul>'
				 + '		<li>'
				 + '			<button class="btn select-parent">Select Parent <i class="zmdi zmdi-forward zmdi-hc-rotate-270"></i></button>'
				 + '			<button class="btn move-up">Move Up <i class="zmdi zmdi-long-arrow-tab zmdi-hc-rotate-270"></i></button>'
				 + '			<button class="btn move-down">Move Down <i class="zmdi zmdi-long-arrow-tab zmdi-hc-rotate-90"></i></button>'
				 + '			<button class="btn move-up-grand-parent">Move Up To Grand Parent <i class="zmdi zmdi-long-arrow-tab zmdi-hc-rotate-270"></i></button>'
				 + '			<button class="btn move-down-grand-parent">Move Down To Grand Parent <i class="zmdi zmdi-long-arrow-tab zmdi-hc-rotate-90"></i></button>'
				 + '			<button class="btn move-forward">Move Forward <i class="zmdi zmdi-long-arrow-tab zmdi-hc-rotate-270"></i></button>'
				 + '			<button class="btn move-back">Move Back <i class="zmdi zmdi-long-arrow-tab zmdi-hc-rotate-90"></i></button>'
				 + '			<button class="btn toggle">Toggle Content <i class="zmdi zmdi-unfold-less"></i></button>'
				 + '			<button class="btn delete">Delete <i class="zmdi zmdi-delete"></i></button>'
				 + '			<button class="btn copy">Copy <i class="zmdi zmdi-copy"></i></button>'
				 + '			<button class="btn cut">Cut <i class="zmdi zmdi-wrap-text"></i></button>'
				 + '			<button class="btn paste">Paste <i class="zmdi zmdi-paste"></i></button>'
				 + '			<button class="btn html-contents">Edit Contents <i class="zmdi zmdi-edit"></i></button>'
				 + '			<button class="btn html-source">Source <i class="zmdi zmdi-code"></i></button>'
				 + '			<button class="btn link-contents">Convert Contents to Link <i class="zmdi zmdi-link"></i></button>'
				 + '			<button class="btn convert-to-tag select-shown" title="Convert the selected widget into a tag name of your choice.">Convert to tag: <input title="Write a tag name and then click in the button" /><select title="Choose a tag name and then click in the button"></select><i class="zmdi zmdi-swap swap-input-select"></i></button>'
				 + '		</li>'
				 + '	</ul>'
				 + '</li>';
		
		menu_settings.append(html);
		
		menu_settings.find(".settings-info .close").click(function(event) {
			event.preventDefault();
			event.stopPropagation();
			
			me.hideFixedMenuSettings();
		});
		
		menu_settings.find(".settings-info .full-screen").click(function(event) {
			event.preventDefault();
			event.stopPropagation();
			
			menu_settings.toggleClass("full-screen");
			
			me.showFixedMenuSettings(true);
		});
		
		//only applies if ui is fixed-properties or fixed-side-properties
		menu_settings.find(".settings-info .toggle-settings-side").click(function(event) {
			me.toggleFixedMenuSettingsSide();
		});
		
		//prepare tabs
		menu_settings.find(".settings-tabs").tabs();
		var tabs = menu_settings.find(".settings-tabs > ul > li > a");
		
		for (var i = 0, t = tabs.length; i < t; i++)
			me.initMenuSettingsTab( $(tabs[i]) );
		
		//prepare others
		if (typeof me.options.on_choose_image_url_func == "function") {
			var icon = menu_settings.find(".settings-decorations .settings-background-image .search");
			
			icon.on("click", function() {
				me.options.on_choose_image_url_func(this);
			});
			icon.parent().addClass("with-search-icon");
		}
		
		var icon = menu_settings.find(".settings-events > .group-title > .widget-group-item-add");
		icon.on("click", function(event) {
			event && event.stopPropagation && event.stopPropagation(); 
			
			var event_name = prompt("Please write here the attribute name correspondent to the new event:");
			event_name = typeof event_name == "string" ? event_name : "";
			event_name = event_name.replace(/(^\s+,\s+$)/g, "");
			
			me.addOtherEvent(this, event_name);
		});
		
		var icon = menu_settings.find(".settings-events .choose-event");
		icon.on("click", function(event) {
			me.toggleChooseWidgetEventPopup(this, event);
		});
		
		if (typeof me.options.on_choose_event_func == "function")
			icon.parent().addClass("with-choose-event-icon");
		
		//set auto complete to .settings-style
		if (typeof MyAutoComplete == "object" && typeof MyAutoComplete.init == "function")
			MyAutoComplete.init( menu_settings.find(".settings-style textarea")[0], getAvailableCSSPropertiesName(), {
				get: function(textarea) {
					var val = textarea.value;
					
					if (val) {
						//get text cursor
						var cursor_pos = $(textarea).prop("selectionStart");
						
						//get written text from user
						var str = cursor_pos > 0 ? val.substr(0, cursor_pos) : val;
						var pos = str.lastIndexOf(";");
						pos = pos > -1 ? pos + 1 : 0;
						val = val.substr(pos, (cursor_pos > 0 ? cursor_pos : val.length) - pos);
						
						val = val.replace(/^\s+/, "");
						
						//console.log("GET:");
						//console.log(pos+"|"+cursor_pos);
						//console.log(val);
					}
					
					//console.log("get:"+val+"!");
					return val;
				},
				set: function(textarea, new_value) {
					var val = textarea.value;
					
					//get text cursor
					var cursor_pos = $(textarea).prop("selectionStart");
					var new_cursor_pos = cursor_pos;
					
					if (val) {
						var previous_str = "";
						var next_str = "";
						
						//get written text from user
						var str = cursor_pos >= 0 ? val.substr(0, cursor_pos) : val;
						var pos = str.lastIndexOf(";");
						pos = pos > -1 ? pos + 1 : 0;
						
						//get previous text
						if (pos > 0)
							previous_str = val.substr(0, pos) + "\n";
						
						//get next text from cursor
						if (cursor_pos > 0) {
							next_str = val.substr(cursor_pos);
							
							//if position is the first char and there is text after, adds that text in the next line.
							if (pos == 0 && next_str.length)
								next_str = "\n" + next_str;
						}
						
						//set new val with previous text + new_value from auto-complete + next next
						val = previous_str + new_value + ": ;" + next_str;
						
						//set new cursor
						new_cursor_pos = val.length - next_str.length - 1; //-1 bc of the ';', so the cursor be between ':' and ';'
						
						//console.log("SET:");
						//console.log(cursor_pos+"|"+new_cursor_pos);
						//console.log(val);
					}
					else
						val = new_value;
					
					textarea.value = val;
					textarea.focus();
					
					//set cursor in the right position
					if (new_cursor_pos >= 0)
						$(textarea).prop("selectionStart", new_cursor_pos).prop("selectionEnd", new_cursor_pos);
					
					//console.log("set:"+val+"!");
				}
			});
		
		menu_settings.find(".settings-actions button.select-parent").click(function(event) {
			var p = getParentWidget(me.selected_template_widget);
			
			if (p)
				p.click();
			else
				me.showError("Parent doesn't exist! You are already at the top level.");
		});
		
		menu_settings.find(".settings-actions button.move-up").click(function(event) {
			if (me.selected_template_widget) {
				var p = me.selected_template_widget.prev(".template-widget");
				
				if (p[0]) {
					try {
						me.selected_template_widget.insertBefore(p); //note that if p is a script node or contains a sript node with some wrong javascript code (with errors), this will give an exception.
					}
					catch(e) {
						if (console && console.log)
							console.log(e);
					}
					
					me.updateMenuLayer(me.selected_template_widget);
					
					if (typeof me.options.on_template_widgets_layout_changed_func == "function")
						me.options.on_template_widgets_layout_changed_func(me.selected_template_widget);
				}
			}
			else
				me.showError("No widget selected.");
		});
		
		menu_settings.find(".settings-actions button.move-down").click(function(event) {
			if (me.selected_template_widget) {
				var n = me.selected_template_widget.next(".template-widget");
				
				if (n[0]) {
					try {
						me.selected_template_widget.insertAfter(n); //note that if p is a script node or contains a sript node with some wrong javascript code (with errors), this will give an exception.
					}
					catch(e) {
						if (console && console.log)
							console.log(e);
					}
					
					me.updateMenuLayer(me.selected_template_widget);
					
					if (typeof me.options.on_template_widgets_layout_changed_func == "function")
						me.options.on_template_widgets_layout_changed_func(me.selected_template_widget);
				}
			}
			else
				me.showError("No widget selected.");
		});
		
		menu_settings.find(".settings-actions button.move-up-grand-parent").click(function(event) {
			if (me.selected_template_widget) {
				var p = getParentWidget(me.selected_template_widget);
				
				if (p) {
					try {
						me.selected_template_widget.insertBefore(p); //note that if p is a script node or contains a sript node with some wrong javascript code (with errors), this will give an exception.
					}
					catch(e) {
						if (console && console.log)
							console.log(e);
					}
					
					me.updateMenuLayer(me.selected_template_widget);
					
					if (typeof me.options.on_template_widgets_layout_changed_func == "function")
						me.options.on_template_widgets_layout_changed_func(me.selected_template_widget);
				}
				else
					me.showError("Parent doesn't exist! You are already at the top level.");
			}
			else
				me.showError("No widget selected.");
		});
		
		menu_settings.find(".settings-actions button.move-down-grand-parent").click(function(event) {
			if (me.selected_template_widget) {
				var p = getParentWidget(me.selected_template_widget);
				
				if (p) {
					try {
						me.selected_template_widget.insertAfter(p); //note that if p is a script node or contains a sript node with some wrong javascript code (with errors), this will give an exception.
					}
					catch(e) {
						if (console && console.log)
							console.log(e);
					}
					
					me.updateMenuLayer(me.selected_template_widget);
					
					if (typeof me.options.on_template_widgets_layout_changed_func == "function")
						me.options.on_template_widgets_layout_changed_func(me.selected_template_widget);
				}
				else
					me.showError("Parent doesn't exist! You are already at the top level.");
			}
			else
				me.showError("No widget selected.");
		});
		
		menu_settings.find(".settings-actions button.move-forward").click(function(event) {
			if (me.selected_template_widget) {
				var z_index = me.selected_template_widget.css("z-index");
				z_index = $.isNumeric(z_index) ? parseInt(z_index) : 0;
				
				z_index++;
				me.selected_template_widget.css("z-index", z_index);
			}
			else
				me.showError("No widget selected.");
		});
		
		menu_settings.find(".settings-actions button.move-back").click(function(event) {
			if (me.selected_template_widget) {
				var z_index = me.selected_template_widget.css("z-index");
				z_index = $.isNumeric(z_index) ? parseInt(z_index) : 0;
				
				z_index--;
				me.selected_template_widget.css("z-index", z_index);
			}
			else
				me.showError("No widget selected.");
		});
		
		menu_settings.find(".settings-actions button.toggle").click(function(event) {
			event.preventDefault();
	  		event.stopPropagation();
	  		
			var icon = $(this).children("i");
			icon.toggleClass("zmdi-unfold-less zmdi-unfold-more");
			me.selected_template_widget.toggleClass("widget-children-hidden");
		});
		
		menu_settings.find(".settings-actions button.delete").click(function(event) {
			if (confirm("Do you wish to delete this item?")) {
				me.deleteTemplateWidget(me.selected_template_widget);
			}
		});
		
		menu_settings.find(".settings-actions button.copy").click(function(event) {
			me.copied_template_widget = me.selected_template_widget;
	  		me.copied_type_template_widget = "copy";
		});
		
		menu_settings.find(".settings-actions button.cut").click(function(event) {
			me.copied_template_widget = me.selected_template_widget;
	  		me.copied_type_template_widget = "cut";
		});
		
		menu_settings.find(".settings-actions button.paste").click(function(event) {
			pasteCopiedWidget(me.selected_template_widget);
			
			if (typeof me.options.on_template_widgets_layout_changed_func == "function")
				me.options.on_template_widgets_layout_changed_func(me.selected_template_widget);
		});
		
		menu_settings.find(".settings-actions button.html-contents").click(function(event) {
			showWidgetSource(me.selected_template_widget, true);
		});
		
		menu_settings.find(".settings-actions button.html-source").click(function(event) {
			showWidgetSource(me.selected_template_widget, false);
		});
		
		menu_settings.find(".settings-actions button.link-contents").click(function(event) {
			convertWidgetContentsToLink(me.selected_template_widget);
		});
		
		menu_settings.find(".settings-actions button.convert-to-tag").click(function(event) {
			var with_select = $(this).hasClass("select-shown");
			var node_name = $(this).children(with_select ? "select" : "input").val();
			
			if (node_name) {
				var widget = me.selected_template_widget;
				node_name = node_name.toLowerCase();
				
				if (node_name != widget[0].nodeName.toLowerCase()) {
					me.changeWidgetNodeName(widget, node_name);
				}
			}
		});
		
		menu_settings.find(".settings-actions button.convert-to-tag i").click(function(event) {
			$(this).parent().toggleClass("select-shown");
		});
		
		var options_html = '<option></option>' + me.getAvailableHtmlTagsOptionsHtml();
		var convert_to_tag_input = menu_settings.find(".settings-actions button.convert-to-tag input");
		var convert_to_tag_select = menu_settings.find(".settings-actions button.convert-to-tag select");
		var convert_to_tag_swap = menu_settings.find(".settings-actions button.convert-to-tag .swap-input-select");
		
		convert_to_tag_select.html(options_html);
		
		convert_to_tag_select.click(function(event) {
			event.stopPropagation();
		});
		convert_to_tag_input.click(function(event) {
			event.stopPropagation();
		});
		convert_to_tag_swap.click(function(event) {
			event.stopPropagation();
		});
		
		menu_settings.find(".group .group-title").click(function(event) {
			var p = $(this).parent();
			p.children("ul").toggle("slow");
			p.toggleClass("group-open");
			
			//if (!p.hasClass("settings-properties"))
				$(this).children(".toggle").toggleClass("zmdi-caret-right zmdi-caret-down");
		});
		
		menu_settings.find(".settings-classes .user-classes .add-class").click(function(event) {
			var c = prompt("Please write here the new class:");
			
			addSettingsClass(this, c);
		});
		
		menu_settings.find(".settings-classes .user-classes .copy-classes").click(function(event) {
			var ucs = $(this).parent().children(".user-class");
			var classes = "";
			$.each(ucs, function(idx, uc) {
				classes += (classes != "" ? " ": "") + $(uc).attr("value");
			});
			
			try {
				navigator.clipboard.writeText(classes);
			}
			catch(e) {
				if (console && console.log)
					console.log(e);
			}
		});
		
		menu_settings.find(".clear-user-input").click(function(event) {
			me.clearFieldsUserInput(this);
		});
		
		menu_settings.find(".buttons-style i:not(.clear-user-input)").click(function(event) {
			var elm = $(this);
			
			if (elm.hasClass("selected"))
				elm.removeClass("selected");
			else {
				if (elm.parent().hasClass("unique"))
					elm.parent().children("i").removeClass("selected");
				
				elm.addClass("selected");
			}
			
			me.saveMenuSettingsField(this);
		});
		
		menu_settings.find(".color-selector").on("input", function(event) {
			var cc = $(this).parent().children(".color-code");
			cc.val( this.value );
			cc.blur();
		});
		
		//Preparing the border fields, bc when we change anything in the settings-border, it should update all other borders (border-top, border-bottom, border-left, border-right) with the same value.
		var border_fields = menu_settings.find(".settings-border").find(".settings-border-width, .settings-border-style, .settings-border-color");
		var on_border_blur = function(event) { //Must run before the generic blur event which executes the me.saveMenuSettingsField method
			//console.log("on_border_blur");
			var elm = $(this);
			var value = elm.val();
			var p = elm.parent();
			var class_suffix = p.attr("class").match(/settings-border-([a-z]+)/); //width, style or color
			
			if (class_suffix) {
				class_suffix = class_suffix[1];
				
				menu_settings.find(".settings-border-top-" + class_suffix + ", .settings-border-bottom-" + class_suffix + ", .settings-border-left-" + class_suffix + ", .settings-border-right-" + class_suffix).children( elm.is("input") ? "input" : "select" ).val(value); //update the new value to other borders, including in the .color-selector input.
			}
		};
		border_fields.children("input:not(.color-selector)").bind("blur", on_border_blur);
		border_fields.children("select").bind("blur", on_border_blur).bind("change", on_border_blur);
		
		//preparing position field
		var update_widget_header_position = function() {
			//set widget header in the widget position if it is shown
			if (widget_header.css("display") != "none")
				setTimeout(function() { //must be inside of settimeout bc the new position only gets updated in the widget after this function gets executed.
					updateWidgetHeaderPosition(me.selected_template_widget);
				}, 300);
		};
		
		menu_settings.find(".settings-position > select").bind("change", function(event) {
			//console.log("on_position_blur");
			/*var position = $(this).val().toLowerCase();
			
			if (position && position != "static")
				widget_header.find(".option.move-position").show();
			else
				widget_header.find(".option.move-position").hide();*/
			
			update_widget_header_position();
		});
		
		var menu_settings_position_elms = menu_settings.find(".settings-top, .settings-bottom, .settings-left, .settings-right");
		menu_settings_position_elms.children("select").bind("change", update_widget_header_position);
		menu_settings_position_elms.children("input").bind("blur", update_widget_header_position);
		menu_settings_position_elms.children(".clear-user-input").bind("click", update_widget_header_position);
		
		//preparing settings-attributes add icon
		menu_settings.find(".settings-attributes .add").click(function(event) {
			var ul = $(this).parent().closest("li").children("ul");
			var li = $('<li class="settings-attribute"><input /><input /><i class="zmdi zmdi-delete delete"></i></li>');
			var inputs = li.find("input");
			
			ul.append(li);
			ul.children(".settings-empty-attributes").hide();
			
			me.addMenuSettingsContextMenu(inputs);
			
			inputs.bind("blur", function() {
				me.saveMenuSettingsField(this);
			});
			
			li.find(".delete").click(function(ev) {
				var li_aux = $(this).parent().closest("li");
				var ul_aux = li_aux.parent();
				
				li_aux.remove();
				
				if (ul_aux.children("li:not(.settings-empty-attributes)").length == 0)
					ul_aux.children(".settings-empty-attributes").show();
				
				//update settings attributes
				me.saveMenuSettingsField(ul_aux[0]);
			});
		});
		
		//preparing field with on blur and change events
		var on_blur = function(event) {
			me.saveMenuSettingsField(this);
		};
		var on_change = function(event) {
			var select = $(this);
			select.data("changed_now", true); //avoids the saveMenuSettingsField to run twice onBlur
			
			me.saveMenuSettingsField(this);
			
			setTimeout(function() {
				select.data("changed_now", false);
			}, 3000); //waits 3 secs
		};
		var on_blur_after_on_change = function(event) {
			var select = $(this);
			
			if (!select.data("changed_now")) //avoids the saveMenuSettingsField to run twice onBlur
				me.saveMenuSettingsField(this);
		};
		var inputs = menu_settings.find("input:not(.color-selector), textarea").not(convert_to_tag_input);
		var selects = menu_settings.find("select").not(convert_to_tag_select);
		inputs.bind("blur", on_blur);
		selects.bind("blur", on_blur_after_on_change).bind("change", on_change);
		
		//add contextmenu with "reset", "choose variable" and "enlarge field" menus
		me.addMenuSettingsContextMenu(inputs);
		me.addMenuSettingsContextMenu(selects);
		
		//prepare resizable menu_settings
		if (ui.hasClass("fixed-properties") || ui.hasClass("fixed-side-properties")) {
			menu_settings.draggable({
				axis: "y",
				appendTo: "body",
				//appendTo: ".layout-ui-editor",
				cursor: "move",
				tolerance: "pointer",
				handle: " > .resize-menu-settings, > .settings-info",
				cancel: ".close, i.full-screen, .button", /* related with handle */
				start: function(event, ui_obj) {
					ui.addClass("resizing-fixed-properties");
					menu_settings.removeClass("collapsed full-screen");
					
					//template_widgets_iframe.addClass("hidden");
					
					//must use [0].style.left, so we be sure that the left and right exists
					menu_settings.data("l", menu_settings[0].style.left); 
					menu_settings.data("r", menu_settings[0].style.right);
				},
				drag: function(event, ui_obj) {
					var h = $(window).height() - parseInt(menu_settings.css("top"));
					resizeFixedMenuSettings(h, menu_settings.data("l"), menu_settings.data("r"));
					menu_settings.removeClass("full-screen"); //resizeFixedMenuSettings sets the full-screen in some cases
				},
				stop: function(event, ui_obj) {
					ui.removeClass("resizing-fixed-properties");
					//template_widgets_iframe.removeClass("hidden");
					
					var h = $(window).height() - parseInt(menu_settings.css("top"));
					resizeFixedMenuSettings(h, menu_settings.data("l"), menu_settings.data("r"));
				},
			});
			
			menu_settings.find(" > .resize-menu-settings > .button").click(function() {
				menu_settings.toggleClass("collapsed").removeClass("full-screen");
				var is_collapsed = menu_settings.hasClass("collapsed");
				
				me.showFixedMenuSettings(true);
				
				setTimeout(function() {
					me.showFixedMenuSettings(true);
				}, 700);
			});
		}
		
		//preparing settings-attributes add icon
		menu_settings.find(".settings-events .with-extra-events > input").bind("blur", function(ev) {
			me.convertWidgetEventInExtraEvents( $(this).parent() );
		});
		menu_settings.find(".settings-events .with-extra-events > .add-extra-event").click(function(ev) {
			me.addExtraEvent(this, ev);
		});
	}
	
	me.initMenuSettingsTab = function(a) {
		a.on("click", function(event) {
			var a = $(this);
			var tabs_links = a.parent().closest("ul").find(" > li > a");
			var show_selector = a.attr("selector");
			var all_selector = "";
			
			for (var i = 0, t = tabs_links.length; i < t; i++) 
				all_selector += (all_selector ? ", " : "") + tabs_links[i].getAttribute("selector");
			
			if (all_selector) {
					menu_settings.find(all_selector).addClass("settings-tabs-container").removeClass("settings-tabs-container-active settings-tabs-container-first");
				
				if (show_selector) {
					var groups = menu_settings.find(show_selector);
					groups.addClass("settings-tabs-container-active");
					groups.first().addClass("settings-tabs-container-first");
					
					if (groups.length == 1 && !groups.hasClass("group-open"))
						groups.addClass("group-open");
				}
			}
			
			//console.log("all_selector:"+all_selector);
			//console.log("show_selector:"+show_selector);
		});
	};
	
	me.clearFieldsUserInput = function(btn) {
		var p = $(btn).parent();
		var fields = p.children("input, select, textarea");
		fields.filter("input:not([type=checkbox]):not([type=radio]), select, textarea").val("");
		fields.filter("input[type=checkbox], input[type=radio]").prop("checked", false).attr("checked", "");
		
		if (p.hasClass("buttons-style"))
			p.children("i").removeClass("selected");
		
		me.saveMenuSettingsField(btn);
	};
	
	me.addMenuSettingsContextMenu = function(fields) {
		if (typeof MyContextMenu == "object") {
			//add contextmenu with "reset", "choose variable" and "enlarge field" menus
			var context_menu_id = me.obj_var_name + '_menu_settings_context_menu';
			var context_menu = $("#" + context_menu_id);
			
			if (!context_menu[0]) {
				var html = '<ul id="' + context_menu_id + '" class="mycontextmenu layout-ui-editor-menu-settings-context-menu">'
							+ '<li class="paste"><a onClick="' + me.obj_var_name + '.pasteMenuSettingsContextMenuField(this)">Paste</a></li>'
							+ '<li class="reset"><a onClick="' + me.obj_var_name + '.resetMenuSettingsContextMenuField(this)">Reset</a></li>'
							+ '<li class="enlarge-field"><a onClick="' + me.obj_var_name + '.enlargeMenuSettingsContextMenuField(this)">Enlarge Field</a></li>'
							+ (typeof me.options.on_choose_variable_func == "function" ? '<li class="choose_variable"><a onClick="' + me.obj_var_name + '.chooseMenuSettingsContextMenuFieldVariable(this, event)">Choose Variable</a></li>' : '')
							+ (typeof me.options.on_choose_event_func == "function" ? '<li class="choose_event"><a onClick="' + me.obj_var_name + '.chooseMenuSettingsContextMenuFieldEvent(this, event)">Choose Event</a></li>' : '')
						+ '</ul>';
				context_menu = $(html);
				
				if (me.LayoutUIEditorWidgetResource)
					me.LayoutUIEditorWidgetResource.addExtraMenuSettingsContextMenu(context_menu);
				
				menu_settings.append(context_menu);
			}
			
			fields = $(fields);
			fields.addcontextmenu(context_menu); //note that the addcontextmenu method will append the context_menu element to the body
			
			//close contextmenu when we click on the select fields, otherwise the user-experience is weird..
			fields.filter("select").on({
				"blur": function(event) {
					if (this.interval_id) {
						MyContextMenu.hideContextMenu(context_menu);
						this.interval_id && clearInterval(this.interval_id);
						this.interval_id = null;
					}
				},
				"mousedown": function(event) {
    					var is_right_click = event.button == 2;
    					this.interval_id && clearInterval(this.interval_id);
    					
    					if (!is_right_click) {
    						var select = this;
    						this.interval_id = setInterval(function() {
    							if (context_menu.is(":visible")) {
								MyContextMenu.hideContextMenu(context_menu);
								select.interval_id && clearInterval(select.interval_id);
								select.interval_id = null;
							}
						}, 300);
					}
					else
    						this.interval_id = null;
    				}
			});
		}
	};
	
	me.pasteMenuSettingsContextMenuField = function(elm) {
		var field = MyContextMenu.getSelectedEventTarget();
		
		if (field) {
			var paste_func = function(text) {
				if (text) {
					$(field).val(text);
				}
				else
					me.showError("Please copy some text before proceeding with the paste action, as none has been copied yet.");
			};
			
			try {
				navigator.clipboard.readText().then(
					function(text) {
						paste_func(text);
					}, 
					function(err) {
						me.showError("Error trying to paste text from clipboard.<br/>" + err);
					}
				);
			}
			catch (e) {
				try {
					var text = document.execCommand('paste');
					paste_func(text);
				}
				catch (e) {
					if (console && console.log)
						console.log(e);
				}
			}
		}
	};
	
	me.resetMenuSettingsContextMenuField = function(elm) {
		if (typeof MyContextMenu == "object") {
			var field = MyContextMenu.getSelectedEventTarget();
			
			if (field)
				me.clearFieldsUserInput(field);
			
			MyContextMenu.hideContextMenu( $(elm).closest(".mycontextmenu") );
		}
	};
	
	me.chooseMenuSettingsContextMenuFieldVariable = function(elm, event) {
		if (typeof MyContextMenu == "object") {
			var field = MyContextMenu.getSelectedEventTarget();
			
			if (field && typeof me.options.on_choose_variable_func == "function")
				me.options.on_choose_variable_func(field, event);
			
			MyContextMenu.hideContextMenu( $(elm).closest(".mycontextmenu") );
		}
	};
	
	me.chooseMenuSettingsContextMenuFieldEvent = function(elm, event) {
		if (typeof MyContextMenu == "object") {
			var field = MyContextMenu.getSelectedEventTarget();
			
			if (field)
				me.toggleChooseWidgetEventPopup(field, event);
			
			MyContextMenu.hideContextMenu( $(elm).closest(".mycontextmenu") );
		}
	};
	
	me.toggleChooseWidgetEventPopup = function(elm, event) {
		elm = $(elm);
		var input = elm.is("input") ? elm : elm.parent().children("input");
		
		if (me.LayoutUIEditorWidgetResource)
			me.LayoutUIEditorWidgetResource.toggleChooseWidgetEventPopup(elm, event);
		else if (typeof me.options.on_choose_event_func == "function") {
			var handler = function(code) {
				input.val(code);
				input.focus();
				
				if (input.parent().hasClass("with-extra-events") || input.parent().parent().hasClass("extra-events"))
					me.convertWidgetEventInExtraEvents( input.parent() );
				
				//very important bc of the LayoutUIEditorWidgetResource.onKeyUpWidgetClickEvent
				if (input[0].hasAttribute("onKeyUp"))
					input.trigger("keyup");
			};
			var available_events = [];
			
			me.options.on_choose_event_func(elm, me.selected_template_widget, handler, available_events, input.val());
		}
	};
	
	me.addExtraEvent = function(elm, event) {
		var p = $(elm).parent().closest(".with-extra-events");
		var ul = p.children(".extra-events");
		var li = $('<li></li>');
		var clones = p.children().not("label, .extra-events, .add-extra-event").clone();
		
		li.append(clones);
		li.append('<i class="zmdi zmdi-delete remove-extra-event" title="Remove this event"></i>');
		ul.append(li);
		
		if (p.hasClass("with-choose-event-icon"))
			li.addClass("with-choose-event-icon");
		
		li.children(".choose-event").on("click", function(ev) {
			me.toggleChooseWidgetEventPopup(this, ev);
		});
		
		li.find(".clear-user-input").click(function(ev) {
			me.clearFieldsUserInput(this);
		});
		
		li.find(".remove-extra-event").click(function(ev) {
			var extra_li = $(this).parent().closest("li");
			var input = extra_li.children("input");
			input.val("");
			
			me.saveMenuSettingsField(input[0]);
			
			extra_li.remove();
		});
		
		var input = li.children("input");
		input.bind("blur", function(ev) {
			me.convertWidgetEventInExtraEvents(li);
			me.saveMenuSettingsField(this);
		});
		input.val("");
		
		me.addMenuSettingsContextMenu(input);
		
		return li;
	};
	
	me.addOtherEvent = function(elm, event_name) {
		if (event_name) {
			if (event_name.substr(0, 2).toLowerCase() == "on" && event_name.substr(2, 1) != " ")
				event_name = event_name.substr(0, 2) + " " + event_name.substr(2);
			
			var event_label = event_name.toLowerCase().replace(/_/g, " ").replace(/\b\w/g, function(letter) { return letter.toUpperCase() });
			var attribute_name = event_name.toLowerCase().replace(/\s/g, "");
			var p = $(elm).parent().closest(".settings-events");
			var ul = p.children("ul");
			var exists = ul.children(".settings-" + attribute_name).length > 0;
			
			if (exists) 
				me.showMessage("This event already exists!");
			else {
				var li = $('<li class="settings-' + attribute_name + ' other-event-type with-extra-events">'
							 + '<label>' + event_label + ': </label>'
							 + '<i class="zmdi zmdi-close clear-user-input" title="Reset field"></i>'
							 + '<input placeHolder="JS function call" title="Javascript function call" />'
							 + (typeof me.options.on_choose_event_func == "function" ? '<i class="zmdi zmdi-search choose-event" title="View and choose pre-defined events"></i>' : '')
							 + '<i class="zmdi zmdi-plus-square add-extra-event" title="Add new event"></i>'
							 + '<i class="zmdi zmdi-delete remove-other-event-type" title="Remove this event type"></i>'
							 + '<ul class="extra-events"></ul>'
						 + '</li>');
				
				ul.append(li);
				
				li.children(".choose-event").on("click", function(ev) {
					me.toggleChooseWidgetEventPopup(this, ev);
				});
				
				if (typeof me.options.on_choose_event_func == "function")
					li.addClass("with-choose-event-icon");
				
				li.find(".clear-user-input").click(function(ev) {
					me.clearFieldsUserInput(this);
				});
				
				li.find(".add-extra-event").click(function(ev) {
					me.addExtraEvent(this, ev);
				});
				
				li.find(".remove-other-event-type").click(function(ev) {
					var li = $(this).parent().closest("li");
					var input = li.children("input");
					input.val("");
					
					me.saveMenuSettingsField( input[0] );
					
					li.remove();
				});
				
				var input = li.children("input");
				input.bind("blur", function(ev) {
					me.convertWidgetEventInExtraEvents(li);
					me.saveMenuSettingsField(this);
				});
				input.val("");
				
				me.addMenuSettingsContextMenu(input);
				
				return li;
			}
		}
	};
	
	me.enlargeMenuSettingsContextMenuField = function(elm) {
		if (typeof MyContextMenu == "object") {
			var field = MyContextMenu.getSelectedEventTarget();
			
			if (field) {
				field = $(field);
				
				if (!field.is("input[type=checkbox], input[type=radio]")) {
					var html = '<div class="enlarged-field">'
								+ '<i class="zmdi zmdi-square-right enlarged-field-close" title="Reduce field size to original"></i>'
							+ '</div>';
					var large_field = $(html);
					var new_field = $( document.createElement(field[0].nodeName) );
					var parent = field.parent();
					
					var close_btn = large_field.children(".enlarged-field-close");
					close_btn.click(function() {
						large_field.remove();
						large_field = null;
						
						field.focus();
						
						if (field.is("select, input[type=checkbox], input[type=radio]"))
							field.trigger("change");
						else
							field.trigger("blur");
					});
					
					if (new_field.is("select")) {
						new_field.html( field.html() );
						new_field.val( field.val() );
						new_field.on("change", function() {
							field.val( $(this).val() );
							field.trigger("change");
						});
						new_field.on("blur", function() {
							close_btn.trigger("click");
						});
					}
					else if (new_field.is("input, textarea")) {
						new_field.val( field.val() );
						new_field.on("keyup", function() {
							field.val( $(this).val() );
						});
						new_field.on("blur", function() {
							setTimeout(function() {
								if (large_field && large_field[0].parentNode && field[0].parentNode) //check if the large_field was not removed from the close button and if the field still exists, just in case the user change to a new widget and the menu settings got reloaded and the field doesn't exist anymore.
									close_btn.trigger("click");
							}, 300);
						});
					}
					
					large_field.prepend(new_field);
					parent.prepend(large_field);
					
					//set parent dimensions
					large_field.css({
						width: parent.width() + "px",
						height: parent.height() + "px",
					});
					
					new_field.focus();
				}
			}
			
			MyContextMenu.hideContextMenu( $(elm).closest(".mycontextmenu") );
		}
	};
	
	me.resizeMenuSettingsContextMenuLargeFields = function() {
		if (typeof MyContextMenu == "object")
			menu_settings.find(".enlarged-field").each(function(idx, large_field) {
				large_field = $(large_field);
				var parent = large_field.parent();
				
				//resize large_field with parent dimensions
				large_field.css({
					width: parent.width() + "px",
					height: parent.height() + "px",
				});
			});
	};
	
	me.resizeMenuSettingsPropertiesFields = function() {
		var widget = $(me.selected_template_widget);
		
		if (widget && me.isMenuSettingsOpened()) {
			var menu_widget = me.getTemplateMenuWidget(widget);
			var properties_elm = menu_widget.children(".properties");
			var func = properties_elm && properties_elm[0] ? properties_elm.attr("data-on-resize-settings-func") : null;
			
			if (func && eval('typeof ' + func + ' == "function"'))
				eval(func + "(widget, menu_settings);");
			
			if (typeof me.options.on_resize_settings_func == "function")
				me.options.on_resize_settings_func(widget, menu_settings);
		}
	};
	
	function resizeFixedMenuSettings(height, left, right) {
		if (ui.hasClass("fixed-side-properties"))
			menu_settings.css({
				height: "auto",
				left: left ? left : "",
				right: right ? right : "",
				bottom: "",
			});
		else
			menu_settings.css({
				height: height + "px",
				top: "",
				left: "",
				right: "",
				bottom: "",
			});
		
		me.showFixedMenuSettings();
	}
	
	function getParentWidget(widget) {
		var p = widget ? widget.parentsUntil(template_widgets_droppable, ".template-widget").first() : null;
		
		return p && p[0] ? p : null;
	}
	
	function addSettingsClass(add_icon, str, do_not_save) {
		if (str) {
			str = str.replace(/\s+/g, " ").replace(/^\s+/g, "").replace(/\s+$/g, "");
			
			if (str != "") {
				var add_user_class_func = function(user_class) {
					var elm = $('<span class="user-class" value="' + user_class.replace(/"/g, '') + '">' + user_class + '<i class="zmdi zmdi-close remove-user-class"></i></span>');
					
					elm.children("i.remove-user-class").click(function(event) {
						event.stopPropagation();
						
						$(this).parent().remove();
						
						me.saveMenuSettingsField( $(add_icon).parent()[0] );
					});
					
					elm.click(function(event) {
						var j_elm = $(this);
						var old_class = j_elm.attr("value");
						var new_class = prompt("Change class:", old_class);
						
						if (typeof new_class == "string")
							new_class = new_class.replace(/\s+/g, " ").replace(/^\s+/g, "").replace(/\s+$/g, "");
						
						if (new_class && new_class != old_class) {
							var parts = new_class.split(" ");
							
							j_elm.attr("value", parts[0]);
							j_elm.contents().filter(function (){
								return this.nodeType === Node.TEXT_NODE;
							}).remove();
							j_elm.prepend(parts[0]);
							
							for (var i = parts.length - 1; i >= 1; i--) {
								var new_elm = add_user_class_func(parts[i]);
								j_elm.after(new_elm);
							}
						}
						else if (new_class == "")
							j_elm.remove();
						
						me.saveMenuSettingsField( $(add_icon).parent()[0] );
					});
					
					return elm;
				};
				
				var parts = str.split(" ");
				//console.log(parts);
				
				for (var i = 0; i < parts.length; i++) {
					var elm = add_user_class_func(parts[i]);
					$(add_icon).before(elm);
				}
				
				if (!do_not_save)
					me.saveMenuSettingsField( $(add_icon).parent()[0] );
			}
		}
	}
	
	me.isMenuSettingsVisible = function() {
		return menu_settings.is(":visible");
	};
	
	me.isMenuSettingsOpened = function() {
		return menu_settings.css("display") != "none";
	};
	
	me.isMenuSettingsEnabled = function() {
		return !ui.find(" > .options .show-settings").hasClass("option-disabled");
	};
	
	function enableMenuSettings() {
		ui.find(" > .options .show-settings").removeClass("option-disabled");
	}
	
	function disableMenuSettings() {
		var show_settings = ui.find(" > .options .show-settings");
		
		if (show_settings.hasClass("option-active")) {
			if (ui.hasClass("fixed-properties") || ui.hasClass("fixed-side-properties"))
				me.hideFixedMenuSettings();
			else
				ui.find(" > .options .show-widgets").click();
		}
		
		show_settings.addClass("option-disabled");
	}
	
	me.showFixedMenuSettings = function(do_not_toggle_collapsed_class) {
		var is_fixed_side_properties = ui.hasClass("fixed-side-properties");
		
		if (!me.isMenuSettingsVisible()) {
			menu_settings.show();//Do not use fadein otherwise the menu_widgets height will be messy if we call after the hideFixedMenuSettings and if the menu_settings are fixed_side_properties.
			ui.addClass("menu-settings-shown");
		}
		
		if (!do_not_toggle_collapsed_class)
			menu_settings.removeClass("collapsed");
		
		//if full-screen or not, check if height is inside of window
		var offset = parseInt(menu_settings.height());
		
		if (is_fixed_side_properties) { //menu_settings has position:absolute;
			var top = parseInt(menu_settings.css("top"));
			var min_h = menu_settings.children(".resize-menu-settings").outerHeight() + menu_settings.children(".settings-info").outerHeight();
			var min_top = parseInt(menu_widgets.css("top"));
			var max_h = parseInt(resize_panels.css("top")) + resize_panels.height();
			var max_top = max_h - min_h;
			
			if (top <= min_top) {
				offset = max_h;
				menu_settings.css({"top": "", "height": ""});
				menu_settings.addClass("full-screen");
			}
			else if (top >= max_top) {
				offset = min_h;
				menu_settings.css({"top": "", "height": ""});
				
				if (!do_not_toggle_collapsed_class)
					menu_settings.addClass("collapsed");
			}
			else
				offset = max_h - top;
		}
		else { //menu_settings has position:fixed;
			var max_h = $(window).height();
			
			if (offset >= max_h) {
				offset = max_h;
				menu_settings.css({"height": ""});
				menu_settings.addClass("full-screen");
			}
			else if (offset <= 0) {
				offset = 0;
				menu_settings.css({"height": ""});
				
				if (!do_not_toggle_collapsed_class)
					menu_settings.addClass("collapsed");
			}
		}
		
		//resize other panels if not full-screen
		if (!menu_settings.hasClass("full-screen")) {
			menu_widgets.css({"bottom": offset + "px"});
			menu_layers.css({"bottom": offset + "px"});
			template_widgets_options.css({"bottom": offset + "px"});
			
			if (!is_fixed_side_properties) {
				template_widgets.css({"bottom": offset + "px"});
				resize_panels.css({"bottom": offset + "px"});
				messages.css({"bottom": offset + "px"});
			}
		}
		
		refreshTemplateWidgetsDimensions();
		
		if (typeof me.options.on_panels_resize_func == "function")
			me.options.on_panels_resize_func({
				direction: "vertical",
				
				offset: offset,
				
				ui: ui,
				menu_settings: menu_settings,
				menu_widgets: menu_widgets,
				menu_layers: menu_layers,
				template_widgets_options: template_widgets_options,
				template_widgets: template_widgets,
				resize_panels: resize_panels,
				messages: messages
			});
	};
	
	me.hideFixedMenuSettings = function() {
		ui.removeClass("menu-settings-shown");
		menu_settings.hide(); //Do not use fadeout otherwise the menu_widgets height will be messy if we call after the showFixedMenuSettings and if the menu_settings are fixed_side_properties.
		
		menu_widgets.css({"bottom": ""});
		menu_layers.css({"bottom": ""});
		template_widgets_options.css({"bottom": ""});
		template_widgets.css({"bottom": ""});
		resize_panels.css({"bottom": ""});
		messages.css({"bottom": ""});
		
		refreshTemplateWidgetsDimensions();
		
		ui.find(" > .options .show-settings").removeClass("option-active");
		
		if (typeof me.options.on_panels_resize_func == "function")
			me.options.on_panels_resize_func({
				direction: "vertical",
				
				offset: "",
				
				ui: ui,
				menu_settings: menu_settings,
				menu_widgets: menu_widgets,
				menu_layers: menu_layers,
				template_widgets_options: template_widgets_options,
				template_widgets: template_widgets,
				resize_panels: resize_panels,
				messages: messages
			});
	};
	
	//only applies if ui is fixed-properties or fixed-side-properties
	me.toggleFixedMenuSettingsSide = function() {
		var is_fixed_properties = ui.hasClass("fixed-properties");
		var is_fixed_side_properties = ui.hasClass("fixed-side-properties");
		
		if (is_fixed_properties)
			ui.removeClass("fixed-properties").addClass("fixed-side-properties");
		else
			ui.removeClass("fixed-side-properties").addClass("fixed-properties");
		
		//reset menu_settings
		menu_settings.css({
			height: "",
			top: "",
			left: "",
			right: "",
			bottom: "",
		});
		menu_settings.removeClass("collapsed");
		
		//updates left or right dimensions in menu_settings
		if (ui.hasClass("fixed-side-properties")) {
			var prop_name = ui.hasClass("reverse") ? "left" : "right";
			menu_settings.css(prop_name, menu_widgets[0].style[prop_name] ); //must get the style prop and not the css(prop)
		}
		
		//reset all the other panels
		menu_widgets.css({"bottom": ""});
		menu_layers.css({"bottom": ""});
		template_widgets_options.css({"bottom": ""});
		template_widgets.css({"bottom": ""});
		resize_panels.css({"bottom": ""});
		messages.css({"bottom": ""});
		
		//prepare fixed menu_settings with right dimensions and positions
		me.showFixedMenuSettings(true);
		
		setTimeout(function() {
			me.showFixedMenuSettings(true);
			
			me.resizeMenuSettingsContextMenuLargeFields();
			me.resizeMenuSettingsPropertiesFields();
		}, 700);
	};
	
	function openMenuSettings(widget) {
		var widget_id = widget.data("data-template-id");
		var label = widget.data("data-label");
		var widget_tag = widget.data("data-tag");
		var menu_widget = me.getTemplateMenuWidget(widget);
		var properties_elm = menu_widget.children(".properties");
		var is_main_html_tags_selector = widget.is(me.options.main_html_tags_selector);//we can see the properties for the body too.
		
		if (!menu_widget[0] && is_main_html_tags_selector) {
			label = widget[0].nodeName;
			widget_tag = "main-html-tag";
		}
		
		//Cleanning settings fields
		resetMenuSettings();
		
		//Preparing Menu Settings UI
		menu_settings.attr("widget-id", widget_id);
		
		//Set class for menu_settings
		var is_full_screen = menu_settings.hasClass("full-screen");
		var menu_widget_class = menu_widget ? menu_widget.attr("data-menu-settings-class") : "";
		var menu_class = " " + me.options.menu_settings_class + "-" + widget_tag + (menu_widget_class ? " " + menu_widget_class : "");
		menu_settings.attr("class", me.options.menu_settings_class + menu_class + (is_full_screen ? " full-screen" : "")); //overwrite the classes from the previous widget
		
		//preparing label
		menu_settings.find(" > .settings-info > label > span").html(label).attr("title", label);
		
		//preparing widget custom properties html
		var props_html = "";
		var on_close_func = null;
		
		if (properties_elm && properties_elm[0]) {
			on_close_func = properties_elm.attr("data-on-close-settings-func");
			
			props_html = properties_elm.html();
			props_html = props_html ? props_html : ""; //widget properties may not exist
		}
		
		menu_settings.attr("data-on-close-settings-func",  on_close_func);
		
		var settings_tab_properties_elm = menu_settings.find(" > .settings-tabs > ul > li.settings-tab-properties");
		var settings_properties_elm = menu_settings.find(" > .settings-properties");
		var props_elm = settings_properties_elm.find(" > ul");
		props_elm.html(props_html);
		
		if (props_html.replace(/\s+/g, "") == "") {
			settings_properties_elm.addClass("hidden");
			settings_tab_properties_elm.addClass("hidden");
		}
		else {
			settings_properties_elm.removeClass("hidden");
			settings_tab_properties_elm.removeClass("hidden");
		}
		
		//set fields blur event
		props_elm.find("input, textarea, select").blur(function (event) {
			return me.saveMenuSettingsField(this);
		});
		
		//set title to properties label
		props_elm.find("label").each(function (idx, prop_label) {
			if (!prop_label.hasAttribute("title")) {
				var prop_title = ("" + $(prop_label).text()).replace(/^\s*/g, "").replace(/\s*$/g, "");
				
				if (prop_title != "")
					prop_label.setAttribute("title", prop_title.replace(/\s*:\s*$/, "") );
			}
		});
		
		//call widget on-open settings function
		if (me.LayoutUIEditorWidgetResource)
			me.LayoutUIEditorWidgetResource.openMenuSettings(widget);
		
		var func = properties_elm && properties_elm[0] ? properties_elm.attr("data-on-open-settings-func") : null;
		
		if (func && eval('typeof ' + func + ' == "function"'))
			eval(func + "(widget, menu_settings);");
		
		if (typeof me.options.on_open_settings_func == "function")
			me.options.on_open_settings_func(widget, menu_settings);
		
		//loading settings
		loadMenuSettings(widget);
		
		//open first tab if not yet open
		var tabs = menu_settings.find(".settings-tabs > ul > li");
		var exist_tab = false;
		
		if (menu_settings.find(".group.settings-tabs-container.settings-tabs-container-active").length > 0)
			for (var i = 0, t = tabs.length; i < t; i++) {
				var tab = $(tabs[i]);
				
				if (tab.css("display") != "none" && tab.hasClass("ui-state-active")) {
					exist_tab = true;
					break;
				}
			}
		
		if (!exist_tab)
			for (var i = 0, t = tabs.length; i < t; i++) {
				var tab = $(tabs[i]);
				
				if (tab.css("display") != "none") {
					tab.children("a").trigger("click");
					break;
				}
			}
	}
	
	me.reloadMenuSettingsIfOpened = function(widget) {
		if (widget.data("data-template-id") == menu_settings.attr("widget-id"))
			openMenuSettings(widget); //Do not use loadMenuSettings otherwise some props won't be loaded, like in the containers
	};
	
	me.reloadOpenMenuSettingsIfOpenedWithOldWidget = function(old_widget, new_widget, timeout) {
		if (me.isMenuSettingsOpened() && old_widget) {
			//Note that when we call the recreateWidgetChildren method for the old_widget.parent(), the old_widget will loose all its data and classes. And if the menu-settings are opened, then it looses the reference for the old_widget, bc then it will be created a new widget throught the recreateWidgetChildren method. So we need to detect this case with the following code and re-open the menu-settings.
			//Note that this case happens when we paste a string with multiple lines inside of a div which has script, style, php and ptl widgets inside where this inner widgets will loose its reference bc they will be converted in new widgets.
			var old_widget_template_id = old_widget.data("data-template-id"); //Do not include the old_widget code inside of the setTimeout bc this function will be used in other functions that will remove the old_widget, after they call this function.
			var is_old_widget_the_selected_template_widget = old_widget.is(me.selected_template_widget);
			var selected_template_widget = me.selected_template_widget;
			
			var func = function() {
				if (old_widget_template_id) { //reload properties with new widget
					if (old_widget_template_id == menu_settings.attr("widget-id"))
						new_widget.trigger("click"); //select new widget to show properties. Note that the new_widget may not be selected, so we need to trigger the click event of calling the openMenuSettings method.
					//else do nothing bc the menu setting sis open with another widget
				}
				else if (is_old_widget_the_selected_template_widget && selected_template_widget.is(me.selected_template_widget)) //select new widget to show properties, if the selected_widget is still old_widget.
					new_widget.trigger("click");
				else if (me.isMenuSettingsOpened()) { //if still opened
					//unselect previous widget and close menu settings if the selected_template_widget lost its' data-template-id. 
					if (!me.selected_template_widget || !me.selected_template_widget.data("data-template-id")) 
						me.getTemplateWidgetsIframeBody().trigger("click");
					else if (!menu_settings.attr("widget-id")) //unselect previous widget and close menu settings if the there is no widget-id in menu settings.
						me.getTemplateWidgetsIframeBody().trigger("click");
				}
			};
			
			if (timeout)
				setTimeout(function() {
					func();
				}, timeout);
			else
				func();
		}
	};
	
	function closeMenuSettings() {
		//call on-close settings function
		var func = menu_settings.attr("data-on-close-settings-func");
		var widget_id = menu_settings.attr("widget-id");
		var widget = me.getTemplateWidget(widget_id);
		
		if (widget[0]) {
			if (func && eval('typeof ' + func + ' == "function"'))
				eval(func + "(widget, menu_settings);");
				
			if (typeof me.options.on_close_settings_func == "function")
				me.options.on_close_settings_func(widget, menu_settings);
		}
	}
	
	function resetMenuSettings() {
		menu_settings.find("input:not([type=checkbox]):not([type=radio]), select, textarea").val("");
		menu_settings.find("input[type=checkbox], input[type=radio]").removeAttr("checked").prop("checked", false);
		
		menu_settings.find(".settings-classes > .user-classes > .user-class").remove();
		menu_settings.children(".settings-properties > ul").html("");
		menu_settings.find(".buttons-style > .selected").removeClass("selected");
		
		var settings_attributes_ul = menu_settings.find(".settings-attributes > ul");
		settings_attributes_ul.children("li:not(.settings-empty-attributes)").remove();
		settings_attributes_ul.children("li.settings-empty-attributes").show();
		
		menu_settings.find(".other-event-type").remove(); //remove other events
		
		var event_lis = menu_settings.find(".with-extra-events");
		event_lis.find(" > .extra-events li").remove(); //reset the extra events
	}
	
	function loadMenuSettings(widget) {
		var widget_id = widget.data("data-template-id");
		var ul = menu_settings.find(" > .settings-properties > ul");
		
		var widget_settings = me.parseWidgetAttributesToSettings(widget);
		//console.log(assignObjectRecursively({}, widget_settings));
		
		var widget_properties = me.parseWidgetSettingsToProperties(widget, widget_settings);
		//console.log(assignObjectRecursively({}, widget_properties));
		
		//loading widget custom properties
		me.loadMenuSettingsPropertiesItems(ul, widget_properties, "");
		
		//Loading classes
		if (widget_properties.hasOwnProperty("class") && widget_properties["class"]) {
			//console.log(widget_settings["class"]);
			//console.log(widget_properties["class"]);
			
			var add_icon = menu_settings.find(".settings-classes > .user-classes > .add-class")[0];
			addSettingsClass(add_icon, widget_properties["class"], true);
			
			widget_properties["class"] = null;
			delete widget_properties["class"];
		}
		
		//Loading widget_properties
		//console.log(assignObjectRecursively({}, widget_properties));
		for (var key in widget_properties) {
			var v = widget_properties[key];
			
			if (v != null && typeof v != "undefined" && ("" + v) != "") {
				var field = menu_settings.find(".settings-" + key).first();
				
				if (field && field.length > 0) {
					if (field.hasClass("settings-attributes")) {
						var attributes = MyHtmlBeautify.getAttributes(v);
						var add_icon = field.children(".add");
						var settings_attributes_ul = field.children("ul");
						
						for (var i = 0, t = attributes.length; i < t; i++) {
							add_icon.trigger("click");
							var li = settings_attributes_ul.children("li:not(.settings-empty-attributes)").last();
							
							if (li[0]) {
								var attribute = attributes[i];
								var attr_value = attribute.value;
								/*var contains_special_chars = attr_value.indexOf("<") != -1; //contains php or ptl
								
								if (contains_special_chars)
									attr_value = attribute["raw_value"];
								*/
								var inputs = li.children("input");
								inputs.first().val(attribute.name);
								inputs.last().val(attr_value);
							}
						}
					}
					else if (field.hasClass("measurement-style")) { //for settings with numeric inputs and correspondent types (like: px, %, em, rem)
						v = "" + v;
						var type = "";
						
						if (v.match(/^[0-9]+(px|%|rem|em|pt|vw|vh)$/i)) { //ignore all values that don't respect format: numeric-type: "100px". Note that this field can have values like "calc(100% - 10px - 1rem)"
							if (v.indexOf("px") > -1) {
								type = "px";
								v = v.replace("px", "");
							}
							else if (v.indexOf("%") > -1) {
								type = "%";
								v = v.replace("%", "");
							}
							else if (v.indexOf("rem") > -1) {
								type = "rem";
								v = v.replace("rem", "");
							}
							else if (v.indexOf("em") > -1) {
								type = "em";
								v = v.replace("em", "");
							}
							else if (v.indexOf("pt") > -1) {
								type = "pt";
								v = v.replace("pt", "");
							}
							else if (v.indexOf("vw") > -1) {
								type = "vw";
								v = v.replace("vw", "");
							}
							else if (v.indexOf("vh") > -1) {
								type = "vh";
								v = v.replace("vh", "");
							}
						}
						
						field.children("input").val(v);
						field.children("select").val(type);
					}
					else if (field.hasClass("buttons-style")) { //for settings with buttons
						var parts = v.split(" ");
						
						for (var i = 0; i < parts.length; i++) {
							var part = parts[i].replace(/\s+/g, ""); //remove end-lines
							
							if (part) {
								try {
									field.children(".settings-" + key + "-" + part).addClass("selected");
								}
								catch(e) {
									//if the v is "var(--xxx-a)" then the selector will be ".settings-text-align-var(--xxx-a)" which will give a jquery exception. so in this case we need to catch the error and ignore it and add this to the style attribute
									var s = key + ":" + v + ";";
									widget_properties["style"] = widget_properties.hasOwnProperty("style") && widget_properties["style"] ? widget_properties["style"] + " " + s : s;
									
									//remove from widget_properties
									widget_properties[key] = null;
									delete widget_properties[key];
								}
							}
						}
					}
					else if (field.hasClass("color-style")) //for color fields
						field.children("input").val( color2hex(v) ); //for the cases like #000000 or rgb(0, 0, 0) or black
					else if (field.children("select").length > 0) { //for the cases with a select field
						var sel = field.children("select");
						
						if (sel.find("option[value='" + v + "']").length > 0)
							sel.val(v);
						else {
							var s = key + ":" + v + ";";
							widget_properties["style"] = widget_properties.hasOwnProperty("style") && widget_properties["style"] ? widget_properties["style"] + " " + s : s;
							
							//remove from widget_properties
							widget_properties[key] = null;
							delete widget_properties[key];
						}
					}
					else { //For all the other cases (normal fields)
						var input = field.children("input, textarea").first();
						
						if (input.attr("type") == "checkbox" || input.attr("type") == "radio") {
							if (input.val() == v)
								input.attr("checked", "checked").prop("checked", true);
							else
								input.removeAttr("checked").prop("checked", false);
						}
						else
							input.val(v);
					}
				}
			}
		}
		
		//reloading style field, bc there could be other values added...
		if (widget_properties.hasOwnProperty("style") && widget_properties["style"]) {
			var s = ("" + widget_properties["style"]).replace(/\s+/g, " ").replace(/;/g, ";\n");
			menu_settings.find(".settings-style > textarea").first().val(s);
			
			//save old-style var to be used when we save the settings
			widget.data("old-style", s);
		}
		
		//convert some attributes to new events
		var settings_attributes_lis = menu_settings.find(".settings-attributes > ul > li.settings-attribute");
		var icon = menu_settings.find(".settings-events > .group-title > .widget-group-item-add");
		
		for (var i = 0, t = settings_attributes_lis.length; i < t; i++) {
			var settings_attributes_li = $(settings_attributes_lis[i]);
			var inputs = settings_attributes_li.children("input");
			var attr_name = inputs.first().val();
			
			if (attr_name.length > 0 && attr_name.substr(0, 2).toLowerCase() == "on") {
				var attr_value = inputs.last().val();
				
				//add new event type
				var li = me.addOtherEvent(icon[0], attr_name);
				
				if (li) {
					li.find("input").val(attr_value);
					me.convertWidgetEventInExtraEvents(li);
					
					//remove from attributes
					settings_attributes_li.remove();
				}
			}
		}
		
		//load extra events
		var event_lis = menu_settings.find(".with-extra-events");
		
		event_lis.find(" > .extra-events li").remove(); //reset the extra events
		
		for (var i = 0, t = event_lis.length; i < t; i++)
			me.convertWidgetEventInExtraEvents( $(event_lis[i]) ); //it means has multiple events that should be shown in multiple lines
		
		//call widget load settings function
		if (me.LayoutUIEditorWidgetResource)
			me.LayoutUIEditorWidgetResource.loadMenuSettings(widget);
	}
	
	me.convertWidgetEventInExtraEvents = function(li) {
		var input = li.children("input");
		var value = input.val();
		
		if (value && value.indexOf(";") != -1) {
			var parts = value.replace(/\s*;\s*/g, ";").replace(/;+/g, ";").replace(/(^;|;$)/g, "").split(";");
			
			if (parts.length > 1) {
				//prepare value
				var parts = [];
				var osq = false, odq = false, start_index = 0;
				
				for (var i = 1, t = value.length; i < t; i++) {
					var char = value[i];
					
					if (char == '"' && !osq && !MyHtmlBeautify.isCharEscaped(value, i))
						odq = !odq;
					else if (char == "'" && !odq && !MyHtmlBeautify.isCharEscaped(value, i))
						osq = !osq;
					else if (char == ";" && !odq && !osq) {
						var str = value.substr(start_index, i - start_index).replace(/(^\s+|\s+$)/g, "");
						
						if (str || $.isNumeric(str)) {
							parts.push(str);
							start_index = i + 1;
						}
					}
				}
				
				var str = value.substr(start_index).replace(/(^\s+|\s+$)/g, "");
				
				if (str || $.isNumeric(str))
					parts.push(str);
				
				//prepare events
				var reference = li;
				var main_li = li.closest(".with-extra-events");
				var icon = main_li.children(".add-extra-event");
				var ul = main_li.children(".extra-events");
				
				input.val(parts[0] + ";");
				
				for (var i = 1, t = parts.length; i < t; i++) {
					var part = parts[i] + ";";
					
					icon.trigger("click");
					var extra_li = ul.children("li").last();
					
					extra_li.children("input").val(part);
					
					if (li.hasClass("with-extra-events") && i == 1)
						ul.prepend(extra_li);
					else
						reference.after(extra_li);
					
					reference = extra_li;
				}
			}
		}
	};
	
	me.convertWidgetEventWithExtraEventsInString = function(li) {
		var value = li.children("input").first().val();
		value = value.replace(/\s*;+\s*$/g, "").replace(/(^\s+|\s+$)/g, "");
		
		//add extra events
		var inputs = li.find(" > .extra-events > li > input");
		
		for (var i = 0, t = inputs.length; i < t; i++) {
			var v = $(inputs[i]).val();
			v = v.replace(/\s*;+\s*$/g, "").replace(/(^\s+|\s+$)/g, "");
			
			if (v || $.isNumeric(v))
				value += (value == "" || value.match(/;\s*$/) ? "" : "; ") + v;
		}
		
		return value;
	};
	
	//to be used by the widgets
	me.parseWidgetAttributesToSettings = function(widget) {
		var elm = widget[0];
		var menu_widget = me.getTemplateMenuWidget(widget);
		var properties_elm = menu_widget.children(".properties");
		var widget_id = widget.data("data-template-id");
		var widget_settings = {};
		
		//call widget on before parse settings function
		if (me.LayoutUIEditorWidgetResource)
			me.LayoutUIEditorWidgetResource.onBeforeParseWidgetSettings(widget, widget_settings);
		
		var func = properties_elm.attr("data-on-before-parse-widget-settings-func");
		if (func && eval('typeof ' + func + ' == "function"'))
			eval(func + "(widget, widget_settings);");
		
		if (typeof me.options.on_before_parse_widget_settings_func == "function")
			me.options.on_before_parse_widget_settings_func(widget, widget_settings);
		
		var default_properties = widget_settings;
		
		if (elm.hasAttribute("id"))
			widget_settings["id"] = widget.attr("id");
		
		//parse class
		if (elm.hasAttribute("class"))
			widget_settings["class"] = me.getTemplateWidgetCurrentClassesWithoutReservedClasses(widget);
		
		//get available settings
		var available_settings = {};
		var settings = menu_settings.find(".group > ul > li:not(.group), .group-block > li");
		$.each(settings, function(idx, item) {
			item = $(item);
			var c = item.attr("class");
			
			if (c) {
				setting_name = c.match(/(\s|^)settings-[a-zA-Z0-9_\-]+/g);
				setting_name = setting_name && setting_name[0] ? setting_name[0].substr(("settings-").length) : "";
				
				if (setting_name != "")
					available_settings[setting_name] = 1;
			}
		});
		
		var properties_case_insensitive = {};
		if (default_properties) 
			$.each(default_properties, function(key, value) {
				properties_case_insensitive[ ("" + key).toLowerCase() ] = value;
			});
		
		var ignore_attributes = getWidgetAttributesToIgnore(widget);
		var attrs = ""; 
		
		$.each(elm.attributes, function(idx, attr) {
			var attr_name_lower = attr.name.toLowerCase();
			
			if (ignore_attributes.indexOf(attr_name_lower) == -1 && !properties_case_insensitive.hasOwnProperty(attr_name_lower)) {
				if (available_settings.hasOwnProperty(attr_name_lower))
					widget_settings[attr_name_lower] = attr.value;
				else {
					var attr_name = attr.name.replace(/&gt;/, ">").replace(/&#61;/g, "=").replace(/&quot;/g, '"').replace(/&amp;/g, '&').replace(/&#47;/g, '/'); //reverse what the encodeInnerPTLAndPHPTags method did
					
					var attr_value = ("" + attr.value);
					//console.log("attr.value before:"+attr_value);
					attr_value = encodeAttributeNonPTLAndPHPValue(attr_value);
					//attr_value = decodeAttributePTLAndPHPValue(attr_value);
					//console.log("attr.value after:"+attr_value);
					
					attrs += (attrs.length > 0 ? " " : "") + attr_name + (attr.value != "" ? '="' + attr_value + '"' : "");
				}
			}
		});
		//console.log(attrs);
		widget_settings["attributes"] = attrs;
		
		//parse style
		var style = widget.attr("style");
		
		if (style) {
			var new_style = "";
			var parts = style.split(";");
			var repeated = [];
			
			for (var j = parts.length - 1; j >= 0; j--) {
				var part = parts[j];
				var trimmed = part.replace(/[\n\r]+/g, "").replace(/^\s+/g, "").replace(/\s+$/g, "");
				
				if (trimmed != "") {
					var splitted = trimmed.split(":");
					var l = splitted[0].replace(/\s+$/g, "").toLowerCase();
					
					//only if not exists yet, otherwise it means it was replaced by the last css property existent, this is, if I have 2 css properties repeated, only the last one will be used.
					if (repeated.indexOf(l) == -1) { 
						if (splitted.length >= 2 && available_settings.hasOwnProperty(l)) //if smaller or bigger, it means is not a typically css attr so we should leave it alone. Must be >= bc if the style contains a background-image with "https://", the splitted.length will be 3.
							widget_settings[l] = splitted.join(":").substr(splitted[0].length + 1).replace(/^\s+/g, "").replace(/\s+$/g, "");
						else
							new_style += part + ";";
						
						repeated.push(l);
					}
				}
			}
			
			if (new_style != "")
				widget_settings["style"] = new_style;
			
			//console.log("node name:"+node_name);
			//console.log(widget_settings);
		}
		
		if (default_properties) 
			for (var k in default_properties)
				widget_settings[k] = default_properties[k];
		
		//call widget on after parse settings function
		if (me.LayoutUIEditorWidgetResource)
			me.LayoutUIEditorWidgetResource.onAfterParseWidgetSettings(widget, widget_settings);
		
		var func = properties_elm.attr("data-on-after-parse-widget-settings-func");
		if (func && eval('typeof ' + func + ' == "function"'))
			eval(func + "(widget, widget_settings);");
		
		if (typeof me.options.on_after_parse_widget_settings_func == "function")
			me.options.on_after_parse_widget_settings_func(widget, widget_settings);
		
		return widget_settings;
	};
	
	function decodeAttributePTLAndPHPValue(attr_value) {
		var contains_special_chars = attr_value && attr_value.indexOf("<") != -1;
		
		if (contains_special_chars) {
			var new_attr_value = "";
			var char, tag_html;
			
			//parse attribute value (the value already inside of quotes but without quotes), and get the ptl and php tag and for each code decode it
			for (var i = 0, t = attr_value.length; i < t; i++) {
				char = attr_value[i];
				
				if (char == "<") {
					if (MyHtmlBeautify.isPHP(attr_value, i)) { //parse php code if exists
						tag_html = MyHtmlBeautify.getPHP(attr_value, i);
						char = tag_html[0].replace(/&#61;/g, "=").replace(/&quot;/g, '"').replace(/&amp;/g, '&'); //reverse what the encodeInnerPTLAndPHPTags method did
						i = tag_html[1];
					}
					else if (MyHtmlBeautify.isPTL(attr_value, i)) { //parse ptl if exists
						tag_html = MyHtmlBeautify.getPTL(attr_value, i);
						char = tag_html[0].replace(/&#61;/g, "=").replace(/&quot;/g, '"').replace(/&amp;/g, '&'); //reverse what the encodeInnerPTLAndPHPTags method did
						i = tag_html[1];
					}
				}
				
				new_attr_value += char;
			}
			
			attr_value = new_attr_value;
		}
		
		return attr_value;
	}
	
	function decodeAttributeNonPTLAndPHPValue(attr_value) {
		var contains_special_chars = attr_value && attr_value.indexOf("<") != -1;
		
		var encode_handler = function(text) {
			return text.replace(/&quot;/g, '"')/*.replace(/&amp;/g, '&')*/;
		};
		
		if (contains_special_chars) {
			var str = "";
			var new_attr_value = "";
			var char, tag_html;
			
			for (var i = 0, t = attr_value.length; i < t; i++) {
				char = attr_value[i];
				
				if (char == "<") {
					if (MyHtmlBeautify.isPHP(attr_value, i)) { //parse php code if exists
						new_attr_value += encode_handler(str);
						str = "";
						
						tag_html = MyHtmlBeautify.getPHP(attr_value, i);
						new_attr_value += tag_html[0];
						i = tag_html[1];
						continue;
					}
					else if (MyHtmlBeautify.isPTL(attr_value, i)) { //parse ptl if exists
						new_attr_value += encode_handler(str);
						str = "";
						
						tag_html = MyHtmlBeautify.getPTL(attr_value, i);
						new_attr_value += tag_html[0];
						i = tag_html[1];
						continue;
					}
				}
				
				str += char;
			}
			
			new_attr_value += encode_handler(str);
			
			attr_value = new_attr_value;
		}
		else
			attr_value = encode_handler(attr_value);
		
		return attr_value;
	}
	
	me.parseWidgetSettingsToProperties = function(widget, widget_settings) {
		var widget_properties = {};
		var widget_settings_style_original_sub_props = {};
		
		widget_properties["style"] = widget_settings.hasOwnProperty("style") && widget_settings["style"] ? widget_settings["style"] : "";
		
		//init props with hard coded style
		if (widget_properties["style"] != "") {
			var parts = widget_properties["style"].split(";");
			
			for (var i = 0; i < parts.length; i++) {
				var part = parts[i];
				var trimmed = part.replace(/[\n\r]+/g, "").replace(/^\s+/g, "").replace(/\s+$/g, ""); //remove end-lines, trim
				
				if (trimmed != "") {
					var splitted = trimmed.split(":");
					
					if (splitted.length >= 2) { //if smaller or bigger, it means is not a typically css attr so we should leave it alone. Must be >= bc if the style contains a background-image with "https://", the splitted.length will be 3.
						var l = splitted[0].replace(/^\s+/g, "").replace(/\s+$/g, "").toLowerCase();
						
						widget_settings[l] = splitted.join(":").substr(splitted[0].length + 1).replace(/^\s+/g, "").replace(/\s+$/g, "");
						widget_settings_style_original_sub_props[l] = part;
					}
				}
			}
		}
		
		//prepare some special css
		for (var key in widget_settings) 
			if (key != "style") {
				var v = widget_settings[key];
				
				if (v != null && typeof v != "undefined" && ("" + v) != "") {
					var str = ("" + v);
					var remove_prop = false;
					var replace_prop = false;
					
					widget_properties[key] = v;
					
					switch(key) {
						case "margin":
						case "padding":
						case "border-radius":
							var parts = str.replace(/\s+/g, " ").split(" "); //remove end-lines
							
							if (parts.length <= 4) { //if padding or margin contains 4 or less values
								//prepare props_to_search
								var props_to_search = [];
								
								if (key == "border-radius")
									props_to_search = ["border-top-left-radius", "border-top-right-radius", "border-bottom-right-radius", "border-bottom-left-radius"];
								else
									props_to_search = [key + "-top", key + "-left", key + "-right", key + "-bottom"];
								
								//prepare styles in widget_properties
								var css_style_declaration_obj = widget[0].style; //get style from widget, which is a CSSStyleDeclaration obj.
								
								for (i = 0; i < css_style_declaration_obj.length; i++) { //loop for all the existent properties in the widget
									var prop_name = css_style_declaration_obj.item(i); //get the style property name (css style - with spaces)
									var pos = props_to_search.indexOf(prop_name);
									
									if (pos != -1) //checks if this property exists in props_to_search, and if yes sets the widget_properties.
										widget_properties[prop_name] = css_style_declaration_obj.getPropertyValue(prop_name);
								}
								
								remove_prop = true;
							}
							break;
						
						case "background":
						case "border":
						case "border-top":
						case "border-left":
						case "border-right":
						case "border-bottom":
						case "font":
							//prepare props_to_search
							var props_to_search = [];
							
							if (key == "border")
								props_to_search = ["border-width", "border-style", "border-color", "border-top-width", "border-top-style", "border-top-color", "border-bottom-width", "border-bottom-style", "border-bottom-color", "border-left-width", "border-left-style", "border-left-color", "border-right-width", "border-right-style", "border-right-color"];
							else if (key.indexOf("border-") === 0)
								props_to_search = [key + "-width", key + "-style", key + "-color"];
							else if (key == "background")
								props_to_search = ["background-image", "background-color"];
							else if (key == "font")
								props_to_search = ["font-family", "font-size", "font-weight", "font-style", "color"];
							
							//prepare styles in widget_properties
							var css_style_declaration_obj = widget[0].style; //get style from widget, which is a CSSStyleDeclaration obj.
							
							for (i = 0; i < css_style_declaration_obj.length; i++) { //loop for all the existent properties in the widget
								var prop_name = css_style_declaration_obj.item(i); //get the style property name (css style - with spaces)
								var pos = props_to_search.indexOf(prop_name);
								
								if (pos != -1) { //checks if this property exists in props_to_search, and if yes sets the widget_properties.
									widget_properties[prop_name] = css_style_declaration_obj.getPropertyValue(prop_name);
									
									str = str.replace(widget_properties[prop_name], ""); //remove property value from main property
									
									//check if we should remove color, by converting rgb in hexadecimal and vice-versa and then remove it from str
									if (prop_name.indexOf("color") != -1 && widget_properties[prop_name]) {
										var color = widget_properties[prop_name];
										
										//colorRgbToHex("rgb(0, 51, 255)") => #0033ff
										if(color.toLowerCase().indexOf("rgb") != -1 && typeof colorRgbToHex == 'function') {
											var hexadecimal = colorRgbToHex(color);
											
											if (hexadecimal)
												str = str.replace(hexadecimal, "").replace(hexadecimal.toLowerCase(), "");
										}
										//colorHexToRgb("#0033ff") => rgb(0, 51, 255)
										else if(color.indexOf("#") != -1 && typeof hexToRgb == 'function') {
											var rgb = colorHexToRgb(color);
											
											if (rgb)
												str = str.replace(rgb, "");
										}
									}
								}
							}
							
							//if main property is empty, remove it
							if (str.replace(/\s/g, "") == "")
								remove_prop = true;
							else if (str != v) { //replace the main property with the new value
								replace_prop = true;
								widget_properties[key] = str;
							}
							
							break;
						
						case "border-width":
						case "border-style":
						case "border-color":
							var k = key.replace("border-", "");
							widget_properties["border-top-" + k] = widget_properties["border-bottom-" + k] = widget_properties["border-left-" + k] = widget_properties["border-right-" + k] = str;
							break;
					}
					
					if (widget_properties["style"] && (remove_prop || replace_prop)) {
						var original_str = "" + widget_settings_style_original_sub_props[key];
						
						//remove property from style string
						if (remove_prop) {
							widget_properties[key] = null;
							delete widget_properties[key];
							
							var to_search_regex_1 = new RegExp(escapeStringForRegExp(original_str) + "\s*(;\s*|$)", "i");
							var to_search_regex_2 = new RegExp("(^|\s|;)" + escapeStringForRegExp(key) + "\s*:([^;]+)(;\s*|$)", "i");
							
							widget_properties["style"] = widget_properties["style"].replace(to_search_regex_1, "").replace(original_str, "").replace(to_search_regex_2, "");
							widget_settings_style_original_sub_props[key] = "";
						}
						else if (replace_prop) {	//replace property from style string
							var replacement = key + ":" + widget_properties[key];
							widget_properties["style"] = widget_properties["style"].replace(original_str, replacement);
							
							widget_settings_style_original_sub_props[key] = replacement;
						}
					}
				}
			}
		
		//preparing border
		if (widget_properties["border-top-width"] == widget_properties["border-bottom-width"] && widget_properties["border-left-width"] == widget_properties["border-right-width"] && widget_properties["border-top-width"] == widget_properties["border-left-width"])
			widget_properties["border-width"] = widget_properties["border-top-width"];
		
		if (widget_properties["border-top-style"] == widget_properties["border-bottom-style"] && widget_properties["border-left-style"] == widget_properties["border-right-style"] && widget_properties["border-top-style"] == widget_properties["border-left-style"])
			widget_properties["border-style"] = widget_properties["border-top-style"];
		
		if (widget_properties["border-top-color"] == widget_properties["border-bottom-color"] && widget_properties["border-left-color"] == widget_properties["border-right-color"] && widget_properties["border-top-style"] == widget_properties["border-left-style"])
			widget_properties["border-color"] = widget_properties["border-top-color"];
		
		//preparing font-family
		if (widget_properties.hasOwnProperty("font-family") && widget_properties["font-family"]) {
			var str = ("" + widget_properties["font-family"]).replace(/"/g, "");
			
			if (str.replace(/^\s+/g, "").replace(/\s+$/g, "") != "")
				widget_properties["font-family"] = str;
			else {
				widget_properties["font-family"] = null;
				delete widget_properties["font-family"];
			}
		}
		
		//preparing font-weight, this is, if font-weight is empty, by default the CSSStyleDeclaration obj returns "normal" 
		if (widget_properties.hasOwnProperty("font-weight") && widget_properties["font-weight"] == "normal") {
			//remove font-weight, but only if it doesn't exists in the orignal style string.
			var hard_coded_in_style = widget_settings_style_original_sub_props["font-weight"] && widget_settings_style_original_sub_props["font-weight"].toLowerCase().indexOf("normal") != -1;
			
			if (!hard_coded_in_style) {
				widget_properties["font-weight"] = null;
				delete widget_properties["font-weight"];
			}
		}
		
		//preparing background-image
		if (widget_properties.hasOwnProperty("background-image") && widget_properties["background-image"]) {
			var str = "" + widget_properties["background-image"];
			
			if (str == "initial") { //if the background-image is empty, by default the CSSStyleDeclaration obj returns "initial"
				//remove background-image, but only if it doesn't exists in the orignal style string.
				var hard_coded_in_style = widget_settings_style_original_sub_props["background-image"] && widget_settings_style_original_sub_props["background-image"].toLowerCase().indexOf("initial") != -1;
				
				if (!hard_coded_in_style) {
					widget_properties["background-image"] = null;
					delete widget_properties["background-image"];
				}
			}	
			else {
				var m = str.match(/(^|\s|:)url\s*\(/i);
				
				if (m && m[0]) { //if exists url
					var start = m.index + m[0].length;
					var end = str.indexOf(")", start);
					end = end == -1 ? str.length : end;
					var img_url = str.substr(start, end - start);
					
					if (img_url) {
						var first_char = img_url.charAt(0);
						var last_char = img_url.charAt(img_url.length - 1);
						
						if ((first_char == '"' && last_char == '"') || (first_char == "'" && last_char == "'"))
							img_url = img_url.substr(1, img_url.length - 2);
						
						widget_properties["background-image"] = img_url;
					}
				}
				else { //set background-image in style property, bc it could be a binary image
					widget_properties["style"] += " " + key + ":" + v + ";";
					
					widget_properties["background-image"] = null;
					delete widget_properties["background-image"];
				}
			}
		}
		
		//if style is empty, remove it
		if (widget_properties["style"].replace(/^\s+/g, "").replace(/\s+$/g, "") == "") {
			widget_properties["style"] = null;
			delete widget_properties["style"];
		}
		
		return widget_properties;
	};
	
	me.loadMenuSettingsPropertiesItems = function(properties_elm, items, parent_key) {
		//Load values
		if (items) {
			for (var key in items) {
				var v = items[key];
				var field_name = parent_key ? parent_key + "[" + key + "]" : key;
				
				//this is for the cases where we have inner arrays inside of the items
				if ($.isPlainObject(v))
					me.loadMenuSettingsPropertiesItems(properties_elm, v, field_name);
				else {
					var field = properties_elm.find('input[name="' + field_name + '"], textarea[name="' + field_name + '"], select[name="' + field_name + '"]');
					
					if (field[0]) {
						if (field.attr("type") == "checkbox" || field.attr("type") == "radio") {
							if (field.val() == v)
								field.attr("checked", "checked").prop("checked", true);
							else
								field.removeAttr("checked").prop("checked", false);
						}
						else {
							field.val(v);
							
							//prepare color field
							var field_parent = field.parent();
							
							if (field_parent.is(".color-style"))
								field_parent.find(".color-selector").val(v);
						}
					}
				}
			}
		}
	};
	
	//is used in the widgets
	me.saveMenuSettingsField = function(field) {
		var widget = me.selected_template_widget;
		
		if (widget) {
			var widget_id = widget.data("data-template-id");
			var menu_widget = me.getTemplateMenuWidget(widget);
			
			var properties_elm = menu_widget.children(".properties");
			var before_save_func = $(properties_elm).attr("data-on-before-save-settings-field-func");
			var after_save_func = $(properties_elm).attr("data-on-after-save-settings-field-func");
			
			var status = true;
			
			if (before_save_func && eval('typeof ' + before_save_func + ' == "function"'))
				eval("status = " + before_save_func + "(field, widget, status);");
			
			if (typeof me.options.on_before_save_settings_field_func == "function")
				status = me.options.on_before_save_settings_field_func(field, widget, status);
			
			//must be at the end, otherwise we may loose some data
			if (me.LayoutUIEditorWidgetResource)
				status = me.LayoutUIEditorWidgetResource.onBeforeSaveSettingsField(field, widget, status);
			
			if (status) {
				sp = $(field).parentsUntil(menu_settings, ".settings-properties").first();
				
				//if not a settings properties, which means is a specific property for the widget and the save action will be taken care in the after_save_func
				if (!sp[0]) {
					saveMenuSettingsNonPropertiesField(field);
					
					widget = me.selected_template_widget; //in case the widget gets recreated inside of saveMenuSettingsNonPropertiesField when we call the method: replaceWidgetWithWidget
				}
			}
			
			if (after_save_func && eval('typeof ' + after_save_func + ' == "function"'))
				eval("status = " + after_save_func + "(field, widget, status);");
			
			if (typeof me.options.on_after_save_settings_field_func == "function")
				status = me.options.on_after_save_settings_field_func(field, widget, status);
			
			//must be at the end, otherwise we may loose some data
			if (me.LayoutUIEditorWidgetResource)
				status = me.LayoutUIEditorWidgetResource.onAfterSaveSettingsField(field, widget, status);
			
			if (typeof me.options.on_template_widgets_layout_changed_func == "function")
				me.options.on_template_widgets_layout_changed_func(widget);
			
			return status;
		}
		
		return false;
	};
	
	function saveMenuSettingsNonPropertiesField(field) {
		var setting = $(field).parent();
		//console.log(field);
		
		if (setting.is("ul.extra-events") || setting.parent().is("ul.extra-events"))
			setting = setting.closest(".with-extra-events");
		
		var setting_class = setting.attr("class");
		var setting_name = setting_class ? setting_class.match(/settings-[a-zA-Z0-9_\-]+/g) : "";
		setting_name = setting_name && setting_name[0] ? setting_name[0].substr(("settings-").length) : "";
		
		if (setting_name != "") {
			var widget_id = me.selected_template_widget.data("data-template-id");
			var widget = me.selected_template_widget.is(me.options.main_html_tags_selector) ? me.selected_template_widget : me.getTemplateWidget(widget_id);//we can see the properties for the html, head and body too.
			
			if (!widget[0]) {
				me.showError("Settings not saved. Please try again...");
				return;
			}
			
			var setting_value = "";
			
			//Preparing setting field
			if (setting_name == "classes") {
				var ucs = setting.find(" > .user-classes > .user-class");
				$.each(ucs, function(idx, uc) {
					setting_value += (setting_value != "" ? " ": "") + $(uc).attr("value");
				});
				
				setting_name = "class";
			}
			else if (setting_name == "attributes" || setting_name == "attribute") { //settings-attributes
				var settings_attributes_ul = setting_name == "attributes" ? setting.children("ul") : setting.closest(".settings-attributes").children("ul"); //do not add the parent() because of the remove event, when we remove an attribute
				var settings_attributes_lis = settings_attributes_ul.children("li:not(.settings-empty-attributes)");
				var default_attributes = getWidgetAttributesToIgnore(widget);
				
				setting_value = [];
				
				for (var i = 0, t = settings_attributes_lis.length; i < t; i++) {
					var settings_attributes_li = $(settings_attributes_lis[i]);
					var inputs = settings_attributes_li.children("input");
					var attr_name = inputs.first().val();
					
					if (attr_name.length > 0) {
						var attr_value = inputs.last().val();
						
						//change user attributes ignoring some reserved attributes
						if ($.inArray(attr_name, default_attributes) != -1) 
							attr_name = (attr_name.substr(0, 5) == "data-" ? "" : "data-") + attr_name + "-2";
						
						setting_value.push({
							name: attr_name,
							value: attr_value,
						});
					}
				}
			}
			else if (setting.hasClass("measurement-style")) { //for settings with numeric inputs and correspondent types (like: px, %, em, rem)
				setting_value = setting.children("input").val();
				
				//Only replace white spaces if is a normal measurement, otheriwse do not replace white spaces because this value can be 'calc(100vh - 100px)' and if we replace the white spaces will be 'calc(100vh-100px)', which is an invalid style.
				if (setting_value && setting_value.match(/^\s*[0-9]+\s*(px|%|rem|em|pt|vw|vh)\s*$/i))
					setting_value = setting_value.replace(/\s+/g, ""); 
				
				if (setting_value != "")
					setting_value = setting_value + setting.children("select").val();
			}
			else if (setting.hasClass("buttons-style")) { //for settings with buttons. Note: Allow multiple values if not unique
				var sels = setting.children(".selected");
				
				$.each(sels, function(idx, sel) {
					var sv = $(sel).attr("class");
					eval("sv = sv.match(/settings-" + setting_name + "-[a-zA-Z0-9_\-]+/g);");
					sv = sv && sv[0] ? sv[0].substr(("settings-" + setting_name + "-").length) : "";
					
					setting_value += (setting_value ? " " : "") + sv;
				});
			}
			else if (setting_name == "background-image") { //for background images
				setting_value = setting.children("input").val();
				
				if (setting_value)
					setting_value = "url('" + setting_value + "')";
			}
			else if (setting.hasClass("color-style")) //for color fields
				setting_value = setting.children("input.color-code").val().replace(/\s+/g, "");
			else if (setting.hasClass("with-extra-events")) //for event and extra-event fields
				setting_value = me.convertWidgetEventWithExtraEventsInString(setting);
			else { //For all the other cases (normal fields)
				var checkbox_radio = setting.children("input[type='checkbox'], input[type='radio']").first();
				
				if (checkbox_radio.length == 0)
					setting_value = setting.children("input, textarea, select").first().val();
				else if (checkbox_radio[0].hasAttribute("checked"))
					setting_value = checkbox_radio.val();
			}
			
			//Setting Widget with appropriate settings
			if (setting_name == "style") { //Set style to widget
				var setting_style = setting_value;
				
				if (widget[0].hasAttribute("style")) {
					var style = widget.attr("style");
					var old_style = widget.data("old-style");
					
					//remove old style from current style
					if (old_style) {
						style = style.replace(old_style, "");
						
						var parts = old_style.split(";");
						
						for (var i = 0; i < parts.length; i++) {
							var part = parts[i].replace(/^\s+/g, "").replace(/\s+$/g, "");
							var sub_parts = part.split(":");
							
							style = style.replace(part, "");
							
							if (sub_parts.length > 1) {
								var regex = new RegExp("\\s*" + sub_parts[0] + "\\s*:\\s*" + sub_parts[1] + "\\s*;?");
								style = style.replace(regex, "");
							}
						}
					}
					
					style = style.replace(/\s\s+/g, "").replace(/^\s+/g, "").replace(/\s+$/g, "").replace(/;+/g, ";").replace(/^;+$/g, "");
					style += style && (style.substr(style.length - 1) == ";" ? "" : ";");
					
					//add others styles from other settings and the css from the style attribute.
					setting_value = (style + setting_value).replace(/;+/g, ";").replace(/^\s*;+/g, "").replace(/^\s+/g, "").replace(/\s+$/g, "");
				}
				
				widget.attr("style", setting_value).data("old-style", setting_style);
				
				if (setting_value == "")
					widget.removeAttr("style");
			}
			else if (setting_name == "class") { //Set class to widget
				var reserved_classes = me.getTemplateWidgetCurrentReservedClasses(widget);
				widget.attr("class", reserved_classes.join(" ") + " " + setting_value);
			}
			else if (["id", "title", "onclick", "onfocus", "onblur", "onchange", "onkeypress", "onkeyup", "onkeydown", "onmouseover", "onmousedown", "onmouseenter", "onmouseout", "onmouseleave", "ondrag", "ondragstart", "ondragend", "ondragenter", "ondragleave", "ondragover", "ondrop"].indexOf(setting_name) != -1 || setting.hasClass("other-event-type")) { //Set id, title, etc... attributes to widget
				if (setting_value.length > 0)
					widget.attr(setting_name, setting_value);
				else
					widget.removeAttr(setting_name);
			}
			else if (setting_name == "attributes" || setting_name == "attribute") { //Set other attributes to widget from .settings-attributes
				//checking if attributes contain special chars
				var contains_special_chars = false;
				
				for (var i = 0, t = setting_value.length; i < t; i++) {
					var attr = setting_value[i];
					
					if (attr.name.indexOf("<") != -1 || attr.name.indexOf("/") != -1 || attr.name.indexOf("&#47;") != -1 || attr.value.indexOf("<") != -1) {
						contains_special_chars = true;
						break;
					}
				}
				
				//change user attributes ignoring some reserved attributes
				var default_attributes = getWidgetAttributesToIgnore(widget);
				
				//preparing new attributes
				var old_attributes = widget[0].attributes;
				
				if (!contains_special_chars) {
					//removing old attribuets
					for (var i = 0; i < old_attributes.length; i++) {
						var attr = old_attributes[i];
						
						if (attr && default_attributes.indexOf(attr.name.toLowerCase()) == -1) {
							widget[0].removeAttribute(attr.name);
							i--;
						}
					}
					
					//adding new attributes
					var ignore_attributes = [/*"data-label", "data-tag", "data-target-id", "data-template-id", */"class", "id", "style"];
					
					for (var i = 0, t = setting_value.length; i < t; i++) {
						var attr = setting_value[i];
						
						if (ignore_attributes.indexOf(attr.name.toLowerCase()) == -1) {
							var attr_name = attr.name;//.replace(/>/, "&gt;").replace(/&/g, "&amp;").replace(/"/g, "&quot;").replace(/=/g, "&#61;"); //repeat what the encodeInnerPTLAndPHPTags method did
							var attr_value = attr.value;//.replace(/&quot;/g, '"').replace(/&amp;/g, "&"); //revert the attribute value replacements that we did before when parseing the setting_value
							
							try {
								widget[0].setAttribute(attr_name, attr_value);
							}
							catch(e) {
								me.showError("Cannot set attribute '" + attr_name + "' with value: '" + attr_value + "'.");
								
								if (console && console.log)
									console.log(e);
							}
						}
					}
				}
				else { //if exists ptl or php tags inside of attributes field, I cannot use the .setAttribute() of .attr() methods bc javascript gives error. So I need to create a new html element and replace the current widget with it.
					var node_name = widget[0].nodeName;
					var new_widget = "<" + node_name;
					var repeated_attrs = [];
					
					$.each(widget[0].attributes, function(idx, attr) {
						var attr_name = attr.name.toLowerCase();
						
						if (attr && default_attributes.indexOf(attr_name) != -1 && repeated_attrs.indexOf(attr_name) == -1) {
							new_widget += " " + attr.name.replace(/&gt;/, ">").replace(/&#47;/, "/") + (attr.value != "" ? '="' + attr.value + '"' : ""); //Do not do this: attr.value.replace(/"/g, "&quot;")
							repeated_attrs.push(attr_name);
						}
					});
					
					var setting_value_str = "";
					
					for (var i = 0, t = setting_value.length; i < t; i++) {
						var attr = setting_value[i];
						var attr_name = attr.name;
						var attr_value = attr.value;
						
						setting_value_str += (setting_value_str ? " " : "") + attr_name;
						
						if (attr_value.length > 0) {
							if (!$.isNumeric(attr_value)) {
								var encapsulate = false;
								
								if (attr_value.length >= 2) {
									var first_char = attr_value[0];
									var last_char = attr_value[attr_value.length - 1];
									
									if ((first_char != '"' || last_char != '"') && (first_char != "'" || last_char != "'"))
										encapsulate = true;
								}
								else
									encapsulate = true;
								
								attr_value = encodeAttributeNonPTLAndPHPValue(attr_value);
								
								if (encapsulate)
									attr_value = '"' + attr_value + '"';
							}
							
							setting_value_str += "=" + attr_value;
						}
					}
					//console.log(setting_value_str);
					
					new_widget += " " + setting_value_str + "></" + node_name + ">";
					new_widget = encodeInnerPTLAndPHPTags(new_widget);
					new_widget = $(new_widget);
					
					try {
						//console.log("CREATING NEW WIDGET");
						var new_widget = createTemplateWidgetFromWidget(new_widget, widget.parent(), null);
						new_widget.append( widget.contents() );
						
						//get all .data(...) values and update the new_widget with it
						var widget_data = widget.data();
						new_widget.data(widget_data);
						
						me.replaceWidgetWithWidget(widget, new_widget);
						widget = new_widget;
						
						//update selected widget
						if (me.selected_template_widget.is(me.options.main_html_tags_selector))
							me.selected_template_widget = widget;
					}
					catch(e) {
						me.showError("Cannot recreate object when saving the menu setting attributes.");
						
						if (console && console.log)
							console.log(e);
					}
				}
			}
			else { //Set css to widget
				widget.css(setting_name, setting_value);
				//console.log(setting_name+":"+setting_value);
				
				//if settings already exists in style attribute, remove it from style attribute
				if (widget[0].hasAttribute("style")) {
					var style_field = menu_settings.find(".settings-style > textarea").first();
					
					var style = style_field.val();
					
					if (style.indexOf(setting_name) > -1) {
						eval ('style = style.replace(/' + setting_name + '\s*:[^;]*;?/g, "");');
						
						style_field.val(style).blur();
					}
				}
			}
		}
	}
	
	//convert '&' to '&amp;' and '"' to '&quot;' but only for the php and ptl text.
	function encodeAttributePTLAndPHPValue(attr_value) {
		var contains_special_chars = attr_value && attr_value.indexOf("<") != -1;
		
		if (contains_special_chars) {
			var new_attr_value = "";
			var char, tag_html;
			
			//parse attribute value (the value already inside of quotes but without quotes), and get the ptl and php tag and for each code decode it
			for (var i = 0, t = attr_value.length; i < t; i++) {
				char = attr_value[i];
				
				if (char == "<") {
					if (MyHtmlBeautify.isPHP(attr_value, i)) { //parse php code if exists
						tag_html = MyHtmlBeautify.getPHP(attr_value, i);
						char = tag_html[0].replace(/&/g, "&amp;").replace(/"/g, "&quot;").replace(/=/g, "&#61;"); //repeat what the encodeInnerPTLAndPHPTags method did
						i = tag_html[1];
					}
					else if (MyHtmlBeautify.isPTL(attr_value, i)) { //parse ptl if exists
						tag_html = MyHtmlBeautify.getPTL(attr_value, i);
						char = tag_html[0].replace(/&/g, "&amp;").replace(/"/g, "&quot;").replace(/=/g, "&#61;"); //repeat what the encodeInnerPTLAndPHPTags method did
						i = tag_html[1];
					}
				}
				
				new_attr_value += char;
			}
			
			attr_value = new_attr_value;
		}
		
		return attr_value;
	}
	
	//convert '&' to '&amp;' and '"' to '&quot;' but only for the non php and ptl text.
	function encodeAttributeNonPTLAndPHPValue(attr_value) {
		var contains_special_chars = attr_value && attr_value.indexOf("<") != -1;
		
		var encode_handler = function(text) {
			return text/*.replace(/&/g, "&amp;")*/.replace(/"/g, "&quot;");
		};
		
		if (contains_special_chars) {
			var str = "";
			var new_attr_value = "";
			var char, tag_html;
			
			for (var i = 0, t = attr_value.length; i < t; i++) {
				char = attr_value[i];
				
				if (char == "<") {
					if (MyHtmlBeautify.isPHP(attr_value, i)) { //parse php code if exists
						new_attr_value += encode_handler(str);
						str = "";
						
						tag_html = MyHtmlBeautify.getPHP(attr_value, i);
						new_attr_value += tag_html[0];
						i = tag_html[1];
						continue;
					}
					else if (MyHtmlBeautify.isPTL(attr_value, i)) { //parse ptl if exists
						new_attr_value += encode_handler(str);
						str = "";
						
						tag_html = MyHtmlBeautify.getPTL(attr_value, i);
						new_attr_value += tag_html[0];
						i = tag_html[1];
						continue;
					}
				}
				
				str += char;
			}
			
			new_attr_value += encode_handler(str);
			
			attr_value = new_attr_value;
		}
		else
			attr_value = encode_handler(attr_value);
		
		return attr_value;
	}
	
	function getWidgetAttributesToIgnore(widget) {
		var ignore_attributes = [/*"data-label", "data-tag", "data-target-id", "data-template-id", */"class", "id", "style"];
		var widget_tag = widget.data("data-tag");
		var widget_obj = me.getMenuWidgetObjByTag(widget_tag);
		
		//Add widget props to ignore_attributes
		var props = menu_settings.find(".settings-properties > ul .settings-property").find("input, textarea, select");
		$.each(props, function(idx, item) {
			if (item.name)
				ignore_attributes.push(item.name.toLowerCase());
		});
		
		if (widget.is(".droppable")) {
			ignore_attributes.push("contenteditable");
			ignore_attributes.push("spellcheck");
		}
		
		if (me.LayoutUIEditorWidgetResource) {
			var others = me.LayoutUIEditorWidgetResource.getWidgetAttributesToIgnore(widget);
			ignore_attributes = ignore_attributes.concat(others);
		}
		
		//Add widget.ignore_attributes to ignore_attributes
		if (widget_obj && widget_obj.hasOwnProperty("ignore_attributes") && $.isArray(widget_obj.ignore_attributes))
			$.each(widget_obj.ignore_attributes, function(idx, name) {
				if (name)
					ignore_attributes.push(name.toLowerCase());
			});
		
		return ignore_attributes;
	}
	
	//is used in the widgets
	me.getMenuSettingsProperties = function(properties_elm) {
		var props = {};
		var query_string = me.getMenuSettingsPropertiesQueryString(properties_elm);
		
		if (query_string!= "")
			parse_str(query_string, props);
		
		return props;
	};
	
	me.getMenuSettingsPropertiesQueryString = function(properties_elm) {
		var query_string = "";
		
		var fields = properties_elm.find("input, textarea, select");
		
		$.each(fields, function(idx, field) {
			field = $(field);
			query_string += (query_string != "" ? "&" : "") + encodeURIComponent( field.attr("name") ) + "=";
			
			if (field.attr("type") == "checkbox" || field.attr("type") == "radio")
				query_string += field.is(":checked") ? encodeURIComponent( field.val() ) : ""; //if not checked an empty value must be set, in order to reset the previous value
			else
				query_string += encodeURIComponent( field.val() );
		});
		
		return query_string;
	};
	
	/* CONVERT SOURCE to/from LAYOUT METHODS */
	
	function convertTemplateSourceToFullSource() {
		var body_html = me.getTemplateSourceEditorValue();
		me.setTemplateFullSourceBodyEditorValue(body_html);
	}
	
	function convertTemplateFullSourceToSource() {
		var body_html = me.getTemplateFullSourceBodyEditorValue();
		me.setTemplateSourceEditorValue(body_html);
	}
	
	function convertTemplateSourceOrLayoutToPreview(prev_active_tab) {
		var head_html = "";
		var body_html = "";
		var iframe = template_preview.children("iframe").contents();
		var is_view_layout = prev_active_tab.hasClass("view-layout");
		
		if (is_view_layout) {
			//head_html = me.getTemplateFullSourceHeadEditorValue();
			head_html = me.getTemplateLayoutHeadHtml();
			body_html = me.getTemplateLayoutBodyHtml();
		}
		else {
			var is_full_source_active = ui.find(" > .options .option.show-full-source").hasClass("option-active");
			if (is_full_source_active)
				convertTemplateFullSourceToSource();
			else
				convertTemplateSourceToFullSource();
		
			head_html = me.getTemplateFullSourceHeadEditorValue();
			body_html = me.getTemplateSourceEditorValue();
		}
		
		try {
			//call url to get the user html and write it to the preview iframe
			if (me.options.template_preview_html_url) {
				var html_to_send = me.getTemplateFullSourceEditorValue();
				
				if (is_view_layout) {
					html_to_send = me.replaceTagContentFromSource(html_to_send, "head", head_html);
					html_to_send = me.replaceTagContentFromSource(html_to_send, "body", body_html);
				}
				
				$.ajax({
					url: me.options.template_preview_html_url,
					type: "post",
					processData: false,
					contentType: "text/html",
					data: html_to_send,
					success: function(parsed_html, textStatus, jqXHR) {
						head_html = body_html = "";
						var body_attributes = [];
						
						if (parsed_html) {
							if (parsed_html.indexOf("<head") == -1 && parsed_html.indexOf("<body") == -1)
								body_html = parsed_html;
							else {
								head_html = me.getTagContentFromSource(parsed_html, "head");
								body_html = me.getTagContentFromSource(parsed_html, "body");
								
								body_attributes = me.getTagAttributesFromSource(parsed_html, "body");
								//console.log(body_attributes);
							}
						}
						
						var iframe_head = iframe.find("head");
						var iframe_body = iframe.find("body");
						
						try {
							iframe_head[0].innerHTML = head_html; //Do not use .html(head_html), bc in some cases it breaks
							iframe_body[0].innerHTML = body_html; //Do not use .html(body_html), bc in some cases it breaks and the body_html may contain some javascript, and the jquery.html() method, removes all the script tags. Use innerHTML instead to keep the original code.
							//console.log(iframe_body.html() == body_html); //if we use 'iframe_body.html(body_html)', this is FALSE. If we use 'iframe_body[0].innerHTML = body_html' this is TRUE!
							
							//bc script elements doesn't get executed when we use .innerHTML, we need to execute then manually
							convertTemplateSourceOrLayoutToPreviewScript(iframe_head);
							convertTemplateSourceOrLayoutToPreviewScript(iframe_body);
						}
						catch(e1) {
							me.showException("Javascript Error: " + (e1 && e1.message ? e1.message : e1));
							
							if (console && console.log) 
								console.log(e);
						};
						
						//set body attributes
						if (body_attributes)
							$.each(body_attributes, function(idx, attr) {
								iframe_body.attr(attr.name, attr.value);
							});
					},
					error: function (jqXHR, textStatus, errorThrown) {
						me.showException("Couldn't call the template preview url. Please try again...");
					},
				});
			}
			else { //write html to the preview iframe
				var iframe_head = iframe.find("head");
				var iframe_body = iframe.find("body");
				
				iframe_head[0].innerHTML = head_html; //Do not use .html(head_html), bc in some cases it breaks
				iframe_body[0].innerHTML = body_html; //Do not use .html(body_html), bc in some cases it breaks and the body_html may contain some javascript, and the jquery.html() method, removes all the script tags. Use innerHTML instead to keep the original code.
				//console.log(iframe_body.html() == body_html); //if we use 'iframe_body.html(body_html)', this is FALSE. If we use 'iframe_body[0].innerHTML = body_html' this is TRUE!
				
				convertTemplateSourceOrLayoutToPreviewScript(iframe_head);
				convertTemplateSourceOrLayoutToPreviewScript(iframe_body);
			}
		}
		catch(e) { //in case there are some javascript erros, which then will break this Editor
			me.showException("Javascript Error: " + (e && e.message ? e.message : e));

			if (console && console.log) 
				console.log(e);
		}
	}
	
	function convertTemplateSourceOrLayoutToPreviewScript(iframe_element) {
		//bc script elements doesn't get executed when we use .innerHTML, we need to execute then manually
		var iframe_doc = iframe_element[0].ownerDocument;
		
		iframe_element.find("script").each(function(idx, script) {
			var p = script.parentNode;
			var s = iframe_doc.createElement("script");
			var code = script.textContent;
			
			for (var i = 0; i < script.attributes.length; i++) 
				s.setAttribute(script.attributes[i].name, script.attributes[i].value);
			
			try {
				try {
					s.appendChild(document.createTextNode(code));
					p.insertBefore(s, script);
				} catch (e1) {
					s.text = code;
					p.insertBefore(s, script);
				}
				
				p.removeChild(script);
			} catch (e) {
				me.showException("Javascript Error: " + (e && e.message ? e.message : e));
				
				if (console && console.log) 
					console.log(e);
			}
		});
	}
	
	me.convertTemplateLayoutToSource = function(opts) {
		//preparing head html
		var head_html = me.getTemplateLayoutHeadHtml();
		var head_attrs = opts && opts["with_head_attributes"] ? me.getTemplateLayoutHeadAttributes() : null;
		
		//preparing body html
		var body_html = me.getTemplateLayoutBodyHtml();
		var body_attrs = opts && opts["with_body_attributes"] ? me.getTemplateLayoutBodyAttributes() : null;
		
		//updating full html
		me.setTemplateFullSourceHeadEditorValue(head_html, head_attrs);
		me.setTemplateFullSourceBodyEditorValue(body_html, body_attrs);
		
		//updating body html
		me.setTemplateSourceEditorValue(body_html);
	};
	
	me.convertTemplateSourceToLayoutAccordingWithOptions = function() {
		me.convertTemplateSourceToLayout();
		prepareTemplateLayoutAccordingWithOptions();
	};
	
	me.convertTemplateSourceToLayout = function() {
		var is_full_source_active = ui.find(" > .options .option.show-full-source").hasClass("option-active");
		//console.log(me.getTemplateFullSourceEditorValue());
		//console.log(is_full_source_active);
		
		if (is_full_source_active)
			convertTemplateFullSourceToSource();
		else
			convertTemplateSourceToFullSource();
		//console.log(me.getTemplateFullSourceEditorValue());
		
		var head_html = me.getTemplateFullSourceHeadEditorValue();
		var body_html = me.getTemplateFullSourceBodyEditorValue(); //me.getTemplateSourceEditorValue();
		//console.log(head_html);
		//console.log(body_html);
		
		try {
			var head = template_widgets_iframe.contents().find("head").first();
			var children = head.contents(); //do not use .children(), bc it won't include the textNodes, php, comments... we need to remove everything that is a .layout-ui-editor-reserved
			$.each(children, function (i, child) {
				if (child.nodeType != Node.ELEMENT_NODE || !me.hasNodeClass($(child), "layout-ui-editor-reserved"))
					head[0].removeChild(child);
			});
			head.prepend(head_html);
			
			var j_body_html = $(body_html);
			
			if (j_body_html.is(".main-droppable") || j_body_html.find(".main-droppable").length > 0) {
				//preparing body
				template_widgets_iframe_body = template_widgets_iframe.contents().find("body");
				
				if (j_body_html.is("body")) {
					template_widgets_iframe_body[0].innerHTML = j_body_html.html();//Do not use .html(body_html), bc in some cases it breaks and the body_html may contain some javascript, and the jquery.html() method, removes all the script tags. Use innerHTML instead to keep the original code.
					//console.log(template_widgets_iframe_body.html() == j_body_html.html()); //if we use 'template_widgets_iframe_body.html(body_html)', this is FALSE. If we use 'template_widgets_iframe_body[0].innerHTML = body_html' this is TRUE!
					
					//clean old body attributes
					var attrs = template_widgets_iframe_body[0].attributes;
					for (var i = 0; i < attrs.length; i++)
						template_widgets_iframe_body.removeAttr(attrs[i].name);
					
					//add new body attributes
					var attrs = j_body_html[0].attributes;
					for (var i = 0; i < attrs.length; i++)
						template_widgets_iframe_body.attr(attrs[i].name, attrs[i].value);
				}
				else {
					template_widgets_iframe_body[0].innerHTML = body_html;//Do not use .html(body_html), bc in some cases it breaks and the body_html may contain some javascript, and the jquery.html() method, removes all the script tags. Use innerHTML instead to keep the original code.
					//console.log(template_widgets_iframe_body.html() == body_html); //if we use 'template_widgets_iframe_body.html(body_html)', this is FALSE. If we use 'template_widgets_iframe_body[0].innerHTML = body_html' this is TRUE!
				}
				
				//template_widgets_droppable = template_widgets_iframe_body.find(".droppable");
				template_widgets_droppable = template_widgets_iframe_body.find(".main-droppable");
				
				if (!template_widgets_droppable[0] && template_widgets_iframe_body.is(".main-droppable"))
					template_widgets_droppable = template_widgets_iframe_body;
				
				selected_template_widgets_droppable = template_widgets_droppable.first();
				
				//get droppable inner html and reset all droppables
				var template_widgets_droppables_html = [];
				
				for (var i = 0; i < template_widgets_droppable.length; i++) {
					var droppable_elm = $(template_widgets_droppable[i]);
					
					template_widgets_droppables_html.push( droppable_elm.html() );
					droppable_elm.html("");
				}
				
				//reinit template widgets droppable
				me.reinitTemplateWidgetsDroppable(template_widgets_droppable);
				
				//preparing template layout with body html
				for (var i = 0; i < template_widgets_droppable.length; i++) {
					var droppable_elm = $(template_widgets_droppable[i]);
					var droppable_html = template_widgets_droppables_html[i];
					
					me.parseHtmlSource(droppable_html, droppable_elm);
				}
			}
			else {
				template_widgets_droppable.first().html(""); //clean templates widgets
				me.parseHtmlSource(body_html, template_widgets_droppable.first());
			}
			
			if (typeof me.options.on_template_widgets_layout_changed_func == "function")
				me.options.on_template_widgets_layout_changed_func(null);
		}
		catch(e) { //in case there are some javascript erros, which then will break this Editor
			me.showException("Javascript Error: " + (e && e.message ? e.message : e));

			if (console && console.log) 
				console.log(e);
		}
	};
	
	/* PARSE/CONVERT HTML TO WIDGETS METHODS */
	
	//To be used by the widgets to only allow the paste action (ctrl+v) with text, this is, this function be sures that every paste action on the elm, only pastes the text without the html. This is very useful when we copy something from the web or wyswyg editor, it only gets the correspondent text, discarding the html.
	me.setPasteCallbackForOnlyText = function(elm) {
		elm.on("paste", function(event) { 
			event.preventDefault();
			
			var clipboard_event = event.originalEvent;
			var doc = this.ownerDocument || this.document;
			var win = doc.defaultView || doc.parentWindow;
			var clipboard_data = clipboard_event.clipboardData || win.clipboardData || window.clipboardData;
			var paste = clipboard_data ? clipboard_data.getData('text') : "";
			
			if (paste) {
				//Do not call the me.TextSelection.getSelection(), bc it doesn't work here.
				var selection = null;
				
				if (doc.selection && doc.selection.createRange) 
					selection = typeof doc.selection == "function" ? doc.selection() : doc.selection;
				else if (win.getSelection)
					selection = win.getSelection();
				
				if (selection && selection.rangeCount > 0) {
					selection.deleteFromDocument();
					selection.getRangeAt(0).insertNode( document.createTextNode(paste) );
					selection.collapseToEnd();
				}
			}
		});
	};
	
	me.convertHtmlElementToWidget = function(node, opts) {
		var options = getHtmlContentsOptions();
		options["convert_text_nodes_content"] = true;
		
		if ($.isPlainObject(opts))
			options = Object.assign(options, opts);
		
		parseHtmlContents(node, node.parent(), options);
	};
	
	me.recreateWidget = function(widget) {
		//remove events
		widget.off();
		
		//remove classes
		var classes = me.getTemplateWidgetCurrentClassesWithoutReservedClasses(widget);
		//classes = classes.replace(/(^|\s)template_widget_\S+/g, "");
		
		widget.attr("class", classes);
		
		//remove ids and other data
		var widget_data = widget.data();
		
		if (widget_data)
			for (var k in widget_data)
				widget.data(k, null);
		
		//recreate widget
		me.convertHtmlElementToWidget(widget, {recreate: true});
		
		//refresh layers
		me.refreshElementMenuLayer( widget.parent() );
	};
	
	me.recreateWidgetChildren = function(widget) {
		//remove data from inner children
		var children = widget.find(".template-widget");
		
		//remove events
		children.off();
		
		for (var i = 0; i < children.length; i++) {
			var child = $(children[i]);
			
			//remove classes
			var classes = me.getTemplateWidgetCurrentClassesWithoutReservedClasses(child);
			//classes = classes.replace(/(^|\s)template_widget_\S+/g, "");
			
			child.attr("class", classes);
			
			//remove ids and other data
			var data = child.data();
			
			if (data)
				for (var k in data)
					child.data(k, null);
		}
		
		//recreate widgets
		for (var i = 0; i < children.length; i++)
			me.convertHtmlElementToWidget( $(children[i]), {recreate: true} );
		
		//refresh layers
		me.refreshElementMenuLayer(widget);
	};
	
	//parse the html which already exists and is appended in an existent html element like a main-droppable
	me.parseElementInnerHtml = function(elm, opts) {
		var items = elm.contents();
		//console.log(items);return;
		
		if (items.length > 0) {
			//bc the nodes exclude the empty text nodes, we need to get the child nodes which are NOT empty text nodes too.
			for (var i = 0; i < items.length; i++) {
				var child = items[i];
				var is_empty_text_node = child.nodeType == Node.TEXT_NODE && ("" + child.textContent).replace(/\s+/g, "") == ""; 
				
				if (!is_empty_text_node)
					me.convertHtmlElementToWidget( $(child) );
			}
		}
		
		//prepare menu layers
		var do_update_menu_layers = opts && opts["do_update_menu_layers"];
		
		if (!do_update_menu_layers)
			prepareMenuLayers();
	};	
	/* DEPRECATED: Do not use the code below, otherwise the children will loose the events previously set, bc we are parseing and adding them to the aux container and only then to the elm. By default this method should parse the children that already exist, so we don't need to worry about convertHtmlSourceIntoLinearNodes and  convertLinearToHierarchicallyNodes, bc we simply want to parse the existent children nodes and convert them to widgets. So we should use the method above and not the method below, which is DEPRECATED!
	//parse the html which already exists and is appended in an existent html element like a main-droppable
	me.parseElementInnerHtml = function(elm, opts) {
		//get element inner html
		var html = elm.html();
		
		//convert html to nodes
		var nodes = convertHtmlSourceIntoLinearNodes(html);
		nodes = convertLinearToHierarchicallyNodes(nodes);
		
		if (nodes.length > 0) {
			//bc the nodes exclude the empty text nodes, we need to get the child nodes which are NOT empty text nodes too.
			var elm_children = [];
			
			for (var i = 0; i < elm[0].childNodes.length; i++) {
				var child = elm[0].childNodes[i];
				var is_empty_text_node = child.nodeType == Node.TEXT_NODE && ("" + child.textContent).replace(/\s+/g, "") == ""; 
				
				if (!is_empty_text_node)
					elm_children.push(child);
			}
			
			//parse nodes that are not empty text nodes
			var nodes_index = 0;
			//console.log(elm_children);
			//console.log(nodes);
			//console.log(elm.html());
			
			for (var i = 0; i < elm_children.length; i++) {
				var child = elm_children[i];
				var j_child = $(child);
				var skip_widget = (child.nodeType == Node.ELEMENT_NODE && j_child.is(".not-widget, .layout-ui-editor-reserved")) || (child.nodeType == Node.COMMENT_NODE && child.textContent.match(/^(not-widget|layout-ui-editor-reserved)(\s|[^a-z0-9]|$)/));
				//console.log(skip_widget+":"+j_child.attr("class"));
				
				if (!skip_widget) {
					var node_to_be_parsed = nodes[nodes_index];
					
					//I need to use the var aux instead of the template_container, otherwise if there is a script tag with javascript errors, the parseHtmlContents(contents, template_container, options); will return an exception
					//Basically the main template_container must be an element which is not added to the document yet, otherwise the browser will parse the correspondent contents and if we have script tag with script errors, the browser will launch an exception and the LayoutUIEditor will stop parsing correctly the other elements. So we need first to add all elements to an outside element.
					var aux = $("<div></div>"); 
					var contents = $([node_to_be_parsed]);
					
					parseHtmlContents(contents, aux, options);
					
					var items = aux.contents();
					
					for (var j = 0; j < items.length; j++) {
						var item = items[j];
						var node_name = item.nodeName.toLowerCase();
						
						//needs a try and catch here bc if the item is a script tag which contains errors, the script will break.
						try {
							//in case of a script, use insertBefore instead, otherwise the script node won't be added to the template_container in some browsers.
							if (node_name == "script")
								elm.insertBefore(item, child);
							else
								j_child.before(item);
						}
						catch (e) {
							//in case of a script with erros, use appendChild instead.
							me.showException("Javascript Error: " + (e && e.message ? e.message : e));

							if (console && console.log) 
								console.log(e);
						}
					}
					
					aux.remove();
					
					//remove non parsed child
					child.parentNode.removeChild(child); //Do not use j_child.remove() or $(child).remove(), bc for some reason the j_child looses its reference and then there are some nodes at the end that are getting removed and it shouldn't bc are .layout-ui-editor-reserved
				}
				
				nodes_index++;
			}
		}
		
		//prepare menu layers
		var do_update_menu_layers = opts && opts["do_update_menu_layers"];
		
		if (!do_update_menu_layers)
			prepareMenuLayers();
	};*/
	
	//parses a html from the source editor
	me.parseHtmlSource = function(html, template_container, opts) {
		var options = getHtmlContentsOptions();
		
		//console.log(html);
		var nodes = convertHtmlSourceIntoLinearNodes(html);
		//console.log(nodes);
		nodes = convertLinearToHierarchicallyNodes(nodes);
		//console.log(nodes);return;
		
		if (nodes.length > 0) {
			var contents = $(nodes);
			//console.log(contents);return;
			
			//I need to use the var aux instead of the template_container, otherwise if there is a script tag with javascript errors, the parseHtmlContents(contents, template_container, options); will return an exception
			//Basically the main template_container must be an element which is not added to the document yet, otherwise the browser will parse the correspondent contents and if we have script tag with script errors, the browser will launch an exception and the LayoutUIEditor will stop parsing correctly the other elements. So we need first to add all elements to an outside element.
			var aux = $("<div></div>"); 
			
			parseHtmlContents(contents, aux, options);
			
			var items = aux.contents();
			
			for (var i = 0; i < items.length; i++) {
				var item = items[i];
				var node_name = item.nodeName.toLowerCase();
				
				//needs a try and catch here bc if the item is a script tag which contains errors, the script will break.
				try {
					//in case of a script, use appendChild instead, otherwise the script node won't be added to the template_container in some browsers.
					template_container[0].appendChild(item); //I cannot do template_container.append(widget), bc if there is an element tag (e.g: DIV) which contains a script tag (as it's child or sub-child), but this script tag contains javascript code with errors, the jquery will parse this DIV and it's children, including the javascript code. In this case the jquery will return an exception. If we use the appendChild method instead, the browser will not parse the element's children, even if there are errors.
				}
				catch (e) {
					//in case of a script with erros, use appendChild instead.
					me.showException("Javascript Error: " + (e && e.message ? e.message : e));

					if (console && console.log) 
						console.log(e);
				}
			}
			
			aux.remove();
		}
		
		//prepare menu layers
		var do_update_menu_layers = opts && opts["do_update_menu_layers"];
		
		if (!do_update_menu_layers)
			prepareMenuLayers();
	};
	
	//parses a html from the source editor, and return the parsed html. This is used by outside functions like the edit_page_and_template.js:reloadLayoutIframeFromSettings()
	me.getParsedHtmlFromHtmlSource = function(html) {
		var parsed_html = "";
		
		//console.log(html);
		var nodes = convertHtmlSourceIntoLinearNodes(html);
		//console.log(nodes);return;
		nodes = convertLinearToHierarchicallyNodes(nodes, true);
		//console.log(nodes);return;
		
		if (nodes.length > 0) {
			//prepare html elements
			for (var i = 0; i < nodes.length; i++) {
				var elm = nodes[i];
				
				try {
					if (elm.nodeType == Node.ELEMENT_NODE)
						parsed_html += elm.outerHTML;
					else if (elm.nodeType == Node.COMMENT_NODE)
						parsed_html += "<!--" + elm.textContent + "-->";
					else if (elm.textContent != "")
						parsed_html += elm.textContent;
				} 
				catch(e) {
					console.log(elm);
					console.log(e);
					throw e;
				}
			}
		}
		
		return parsed_html;
	};
	
	//parses a native html element and check which menu-widget revindicates that html element.
	me.getPossibleMenuWidgetsForHtmlElement = function(elm, opts) {
		var possible_menu_widgets = [];
		var options = getHtmlContentsOptions();
		
		try {
			var html_tag = options["html_tag"];
			var html_text = options["html_text"];
			var html_comment = options["html_comment"];
			var php = options["php"];
			var menu_widgets_parse_html_func = options["menu_widgets_parse_html_func"];
			var j_elm = $(elm);
			
			opts = opts ? opts : {};
			var include_default_widgets = !opts.hasOwnProperty("include_default_widgets") || opts["include_default_widgets"];
			
			//console.log(elm.nodeType);continue ;
			
			if (elm.nodeType == Node.ELEMENT_NODE) { //it means it is a html tag and not a simple text node.
				var node_name = elm.nodeName.toLowerCase();
				
				if (j_elm.is(".not-widget, .layout-ui-editor-reserved")) { //for elements that we which not to parse
					return null;
				}
				else if (me.hasNodeClass(j_elm, "ignore-widget")) {
					return null;
				}
				else {
					/*var is_special = elementContainsPtlOrPhpTagNotParsed(elm);
					
					if (is_special) {
						return null;
					}
					else {*/
						//loop all available menu_widgets and execute parseHtml function. If parseHtml returns something it means that the menu_widget recognized the html elm. Otherwise returns false and loop continues to the next menu_widget.
						//console.log(menu_widgets_parse_html_func);
						for (var tag in menu_widgets_parse_html_func) {
							var parse_func = menu_widgets_parse_html_func[tag];
							var ret = null;
							
							if (parse_func && typeof parse_func == "function")
								ret = parse_func(elm);
							
							if (typeof me.options.on_parse_template_widget_html_func == "function")
								ret = me.options.on_parse_template_widget_html_func(elm, ret);
							
							if (ret || $.isPlainObject(ret)) {
								var menu_widget = menu_widgets.find('.menu-widget[data-tag="' + tag + '"]').first();
								possible_menu_widgets.push(menu_widget);
							}
						}
						
						if (include_default_widgets && possible_menu_widgets.length == 0)
							possible_menu_widgets.push(html_tag);
					//}
				}
			}
			else if (include_default_widgets) {
				if (elm.nodeType == Node.TEXT_NODE) { //Text node
					possible_menu_widgets.push(html_text);
				}
				else if (elm.nodeType == Node.COMMENT_NODE) { //Comment node or php node
					var text = elm.textContent;
					
					if (text.match(/^(not-widget|layout-ui-editor-reserved)(\s|[^a-z0-9]|$)/))
						return null;
					
					var is_php = text.substr(0, 1) == "?" && text.substr(text.length - 1) == "?";
					
					if (is_php && php && php[0]) //maybe the php widget was not loaded, so we must check if it exists first.
						possible_menu_widgets.push(php);
					else
						possible_menu_widgets.push(html_comment);
				}
				else
					possible_menu_widgets.push(html_comment);
			}
		} 
		catch(e) {
			console.log(elm);
			console.log(e);
			throw e;
		}
		
		return possible_menu_widgets;
	};
	
	//parses a native html element and check if the correspondent menu-widget is a non default menu-widget, this is, is not a html-tag, html-comment, html-text, html-code and php widgets.
	me.isHtmlElementANonDefaultMenuWidget = function(elm) {
		var possible_menu_widgets = me.getPossibleMenuWidgetsForHtmlElement(elm, {include_default_widgets: false});
		
		return possible_menu_widgets.length > 0;
	};
	
	//parses a native html element and check if the correspondent menu-widget is a default menu-widget, this is, is a html-tag, html-comment, html-text, html-code or php widget.
	me.isHtmlElementADefaultMenuWidget = function(elm) {
		var possible_menu_widgets_with_defaults = me.getPossibleMenuWidgetsForHtmlElement(elm);
		var possible_menu_widgets_without_defaults = me.getPossibleMenuWidgetsForHtmlElement(elm, {include_default_widgets: true});
		
		return possible_menu_widgets_without_defaults.length == 0 && possible_menu_widgets_with_defaults.length > 0;
	};
	
	function convertHtmlSourceIntoLinearNodes(html) {
		var nodes = [];
		
		if (html) {
			html = "" + html;
			var char, tag_html, is_tag_close, is_short_tag_close, is_simple_html_tag, is_simple_ptl_tag, is_tag_dec, tag_name, is_tag_dec, is_ptl, node, parent_idx, code;
			var parents = [];
			var no_parse_tags = ["style", "script", "textarea"];
			var html_length = html.length;
			
			for (var i = 0; i < html_length; i++) {
				char = html[i];
				parent_idx = parents[parents.length - 1];
				
				if (char == "<") {
					if (MyHtmlBeautify.isComment(html, i)) { //parse comment if exists
						tag_html = MyHtmlBeautify.getComment(html, i);
						i = tag_html[1];
						
						nodes.push({
							nodeType: Node.COMMENT_NODE,
							nodeName: "#comment",
							textContent: tag_html[0].substr(4, tag_html[0].length - 7), //7 = 4 + 3
						});
						
						if (parent_idx >= 0)
							nodes[parent_idx]["childNodes"].push(nodes.length - 1);
					}
					else if (MyHtmlBeautify.isPHP(html, i)) { //parse php code if exists
						tag_html = MyHtmlBeautify.getPHP(html, i);
						i = tag_html[1];
						
						nodes.push({
							nodeType: Node.COMMENT_NODE,
							nodeName: "#php",
							textContent: tag_html[0],
						});
						
						if (parent_idx >= 0)
							nodes[parent_idx]["childNodes"].push(nodes.length - 1);
					}
					else if (!MyHtmlBeautify.isTagHtml(html, i)) { //check if html or ptl tag. This could be a simple text - supposedly this case should not happen.
						//console.log("OPS - IT SHOULDN'T ENTER HERE!!! HUMM...");
						tag_html = MyHtmlBeautify.getTextContent(html, i + 1);
						i = tag_html[1];
						var is_empty = tag_html[0].replace(/\s+/g, "") == "";
						
						if (!is_empty) {
							//tag_html[0] = tag_html[0].replace(/(\r\n|\n|\r|\t|\f|\v)(\r\n|\n|\r|\t|\f|\v)+/g, ""); //Do not add this code, bc a text can have multiple lines in between texts. If this text is inside of a <pre> element, then it should show multiple lines.
							tag_html[0] = tag_html[0].replace(/(\t|\f|\v)(\t|\f|\v)+/g, ""); //removes multiple tabs, form feed chars and vertical tabs
							tag_html[0] = tag_html[0].replace(/^(\r\n|\n|\r|\t|\f|\v)+/g, "").replace(/(\r\n|\n|\r|\t|\f|\v)+$/g, ""); //trim textContent, by removing all whitespace chars, except the spaces it self, this is, including spaces. \f is form feed character and \v is vertical tab character. We need to do this here, bc the html comes the an indented source and it is normal to have endlines and tabs in the begining and end of text.
							
							tag_html[0] = tag_html[0].replace(/</g, "&lt;").replace(/>/g, "&gt;");
							
							tag_html[0] = char + tag_html[0];
						}
						else
							tag_html[0] = char;
						
						nodes.push({
							nodeType: Node.TEXT_NODE, 
							nodeName: "#text",
							textContent: tag_html[0],
						});
						
						if (parent_idx >= 0)
							nodes[parent_idx]["childNodes"].push(nodes.length - 1);
					}
					else {
						is_ptl = MyHtmlBeautify.isPTL(html, i);
						tag_html = is_ptl ? MyHtmlBeautify.getPTL(html, i) : MyHtmlBeautify.getTagHtml(html, i, "");
						code = tag_html[0];
						
						if (code) {
							tag_name = is_ptl ? MyHtmlBeautify.getPTLTagName(code, 0) : MyHtmlBeautify.getTagName(code, 0);
							i = tag_html[1];
							
							//preparing tag
							is_tag_close = code.substr(0, 2) == "</";
							
							if (is_tag_close && parents.length > 0) {
								//if tag previous parent is elseif/else/catch/default, removes parents until it find the right parent
								do {
									parent_idx = parents[parents.length - 1];
									is_tag_dec = parent_idx >= 0 ? MyHtmlBeautify.isDecrementPrefixPTLTag("<" + nodes[parent_idx]["nodeName"]) : false;
									
									parents.pop();
								}
								while (is_tag_dec);
							}
							else {
								is_short_tag_close = code.substr(code.length - 2) == "/>";
								is_simple_html_tag = MyHtmlBeautify.isSingleHtmlTag(code);
								is_simple_ptl_tag = MyHtmlBeautify.isSinglePTLTag(code);
								
								//prepare simple tags in case of an standard html
								if (!is_tag_close && !is_short_tag_close && (is_simple_html_tag || is_simple_ptl_tag)) {
									var next_html = html.substr(i + 1).replace(/^\s+/g, "");
									
									//check if already exists </ptl...>, and if not, add closing tags
									if (next_html.indexOf("</" + tag_name + ">") == 0 || next_html.indexOf("</" + tag_name + " ") == 0) {
										i = html.indexOf(">", i + 1);
										code = code.substr(0, code.length - 1) + "/>";
										is_short_tag_close = true;
									}
								}
								
								node = {
									nodeType: Node.ELEMENT_NODE,
									nodeName: tag_name,
									tag: code,
									textContent: "",
									childNodes: [],
								};
								
								//only if open tag
								if (!is_tag_close && !is_short_tag_close && !is_simple_html_tag && !is_simple_ptl_tag) {
									is_no_parse_tags = no_parse_tags.indexOf(tag_name) > -1;
									
									if (is_no_parse_tags) { //if style, script or textarea
										tag_html = MyHtmlBeautify.getNonParseInnerTagsNodeContent(html, i + 1, tag_name);
										i = html.indexOf(">", tag_html[1] + ("</" + tag_name).length + 1);
										
										//console.log(tag_html[0]);
										node["textContent"] = tag_html[0];//.replace(/</g, "&lt;").replace(/>/g, "&gt;");
									}
									else
										parents.push(nodes.length);
								}
								
								nodes.push(node);
								
								if (parent_idx >= 0)
									nodes[parent_idx]["childNodes"].push(nodes.length - 1);
							}
						}
						else 
							i = html_length;
					}
				}
				else {
					tag_html = MyHtmlBeautify.getTextContent(html, i);
					i = tag_html[1];
					var is_empty = tag_html[0].replace(/\s+/g, "") == "";
					
					if (!is_empty) {
						//tag_html[0] = tag_html[0].replace(/(\r\n|\n|\r|\t|\f|\v)(\r\n|\n|\r|\t|\f|\v)+/g, ""); //Do not add this code, bc a text can have multiple lines in between texts. If this text is inside of a <pre> element, then it should show multiple lines.
						tag_html[0] = tag_html[0].replace(/(\t|\f|\v)(\t|\f|\v)+/g, ""); //removes multiple tabs, form feed chars and vertical tabs
						tag_html[0] = tag_html[0].replace(/^(\r\n|\n|\r|\t|\f|\v)+/g, "").replace(/(\r\n|\n|\r|\t|\f|\v)+$/g, ""); //trim textContent, by removing all whitespace chars, except the spaces it self, this is, including spaces. \f is form feed character and \v is vertical tab character. We need to do this here, bc the html comes the an indented source and it is normal to have endlines and tabs in the begining and end of text.
						
						tag_html[0] = tag_html[0].replace(/</g, "&lt;").replace(/>/g, "&gt;");
						
						nodes.push({
							nodeType: Node.TEXT_NODE, 
							nodeName: "#text",
							textContent: tag_html[0]
						});
						
						if (parent_idx >= 0)
							nodes[parent_idx]["childNodes"].push(nodes.length - 1);
					}
				}
			}
		}
		
		return nodes;
	}
	
	/*
	 * We must start by the lowest child and not the parent, otherwise when we append the widget to the template_container in createTemplateWidgetFromMenuWidget: line 777, the browser will parse this html and remove any incorrect html, like remove < and > inside of scripts and styles
	 * We need to convert the nodes to widgets here because of this! First we convert the childs and only after the parents.
	 * 
	 * If this function is call from the getParsedHtmlFromHtmlSource method, the to_get_inner_html_from_main_node_later must be true, otherwise when we use the main_node.innerHTML or main_node.outerHTML, then the textContent of the textNodes that were appended to some ELEMENT_NODE, will be converted with "&amp;", this is all "&" chars inside of textContent will be replaced by "&amp;".
	*/
	function convertLinearToHierarchicallyNodes(nodes, to_get_inner_html_from_main_node_later) {
		var nodes_with_children = [];
		
		if (nodes) {
			var dummy_element = document.createElement('div');
			
			//preparing nodes with children hierarchy
			for (var i = nodes.length - 1; i >= 0; i--) {
				var node = nodes[i];
				
				if (!node)
					continue;
				
				if (node.nodeType == Node.ELEMENT_NODE) {
					var tag = encodeInnerPTLAndPHPTags(node.tag);
					//console.log(tag);
					var html_node = $(tag)[0];
					//console.log(html_node);
					
					if (node.hasOwnProperty("textContent") && node.textContent != "")
						html_node.innerHTML = node.textContent;
					
					if (node.hasOwnProperty("childNodes")) {
						var indexes = node["childNodes"];
						
						for (var j = 0; j < indexes.length; j++) {
							var index = indexes[j];
							var child = nodes[index];
							nodes[index] = null;
							
							//When appendChild a TEXT_NODE to an ELEMENT_NODE, and then if I do ELEMENT_NODE.innerHTML or ELEMENT_NODE.outerHTML, the browser will convert all TEXT_NODE.textContent with "&amp;", this is all "&" chars inside of textContent will be replaced by "&amp;", which will mess the returned innerHTML/outerHTML. So before append a TEXT_NODE to an ELEMENT_NODE, we need to convert the TEXT_NODE.textContent to decoded html entities, bc they will then be encoded back automatically by the browser.
							//However by doing this, &lt; and &gt; will be converted to "<" and ">", so we need to convert them back.
							//Note that we only want to get the real html if the to_get_inner_html_from_main_node_later var is true, otherwise we want the browser to convert to "&" to "&amp;", so when we add the child to the html_node, doesn't convert the textContent like "&lt;div&gt;" to real html elements.
							if (to_get_inner_html_from_main_node_later && child.nodeType == Node.TEXT_NODE && typeof child.textContent == "string" && child.textContent.match(/&[a-z]+;/i)) {
								dummy_element.innerHTML = child.textContent;
      							var decoded_text = dummy_element.textContent;
      							decoded_text = decoded_text.replace(/</g, "&lt;").replace(/>/g, "&gt;"); //Very important, otherwise the decoded_text will have "<" and ">" which then will convert to nodes.
								//console.log(child.textContent);
      							//console.log(decoded_text);
      							child.textContent = decoded_text;
							}
							
							html_node.appendChild(child);
						}
					}
					
					nodes[i] = html_node;
				}
				else if (node.nodeType == Node.TEXT_NODE)
					nodes[i] = document.createTextNode(node.textContent);
				else if (node.nodeType == Node.COMMENT_NODE && node.nodeName == "#php")
					nodes[i] = document.createComment(node.textContent.substr(1, node.textContent.length - 2)); //remove the <? and ?> to ?
				else if (node.nodeType == Node.COMMENT_NODE)
					nodes[i] = document.createComment(node.textContent);
			}
			
			delete dummy_element;
			
			for (var i = 0; i < nodes.length; i++)
				if (nodes[i])
					nodes_with_children.push(nodes[i]);
		}
		
		//console.log(nodes_with_children);
		return nodes_with_children;
	}
	
	/* NO NEED ANYMORE
	function prepareHtmlToBeParsed(html) {
		if (html.indexOf("<ptl:") != -1 || html.indexOf("<?") != -1 || html.indexOf("<!--?") != -1) {
			//Threat html
			html = encodeInnerPTLAndPHPTags(html);
			html = MyHtmlBeautify.beautify(html); //besides beautifying the html, this function replaces all the short tags (<ptl:.../>) into long ones (<ptl:...></ptl:...>)
			//console.log(html);
		}
		
		return html;
	}*/
	
	/* NO NEED ANYMORE
	function parseTemplateLayoutHtml() {
		//there could be multiple droppables...
		for (var i = 0; i < template_widgets_droppable.length; i++) {
			var droppable_elm = $(template_widgets_droppable[i]);
			var html = droppable_elm.html();
			var aux = prepareHtmlToBeParsed(html);
			
			//prepare new html and replace old one.
			if (html != aux) {
				var temp = document.createElement("div");
				temp.innerHTML = aux;
				droppable_elm.html("");
				
				/*
				 * Note that I cannot do:
				 * - droppable_elm.html(aux); because it will strip the script tags
				 * - droppable_elm.innerHTML = aux; becacuse it will mess the html with ptl and php code inside of html tags, this is, somethind like this: <input name="foo <ptl:echo "$x"> bar"> or <input <ptl:echo $x> name="foo">
				 * 
				 * Leave this code as it is, because is tested and working fine!
				 */
				 /*
				$.each($(temp).contents(), function(idx, item) {
					if(item.nodeName.toLowerCase() == "script") {
						var script = document.createElement("script");
						var attrs = item.attributes;
						
						for(var j = 0; j < attrs.length; j++)
							script[ attrs[j].name ] = attrs[j].value;
						 
						script.textContent = item.textContent;
						item = script;
					}
					
					droppable_elm[0].appendChild(item);
				});
				
				temp.remove();
			}
			
			var contents = droppable_elm.contents();
			var options = getHtmlContentsOptions();
			
			parseHtmlContents(contents, droppable_elm, options);
		}
		
		prepareMenuLayers();
	}*/
	
	/*
	 * DEPRECATED METHOD. DO NOT USE IT!
	 * We cannot use this anymore bc we must start by the lowest child and not the parent, otherwise when we append the widget to the template_container in createTemplateWidgetFromMenuWidget: line 777, the browser will parse this html and remove any incorrect html, like remove < and > inside of scripts and styles
	 * The parseHtmlContents method starts first by the parent and then the children, but this doesn't work, bc when the browser parses the parent will change the children html and in some cases abort with exception.
	 * This method is DEPRECATED!
	*/
	function parseHtmlContents(contents, template_container, options) {
		if (!contents || !(contents instanceof jQuery))
			return false;
		
		//remove text nodes that doesn't matter, this is, nodes with only spaces or \n, which means this corresponds to the html indentation of the source code (and should not be visible).
		contents = contents.filter(function(idx, elm) {
			//if (elm.nodeType == Node.TEXT_NODE && ("" + elm.textContent).replace(/\s+/g, "") != "") console.log(elm);
			return elm.nodeType != Node.TEXT_NODE || ("" + elm.textContent).replace(/\s+/g, "") != "";
		});
		
		contents = contents.toArray();
		//console.log(contents);
		
		if (!$.isArray(contents) || contents.length == 0)
			return false;
		
		//prepare html elements
		for (var i = 0; i < contents.length; i++) {
			var elm = contents[i];
			var widget = convertHtmlContentIntoWidget(elm, template_container, options);
			
			if (widget) {
				var droppable = widget.data("droppable");
				
				//parse children
				if (droppable && droppable[0])
					parseHtmlContents($(elm).contents(), droppable, options);
			}
		}
	}
	
	function getHtmlContentsOptions() {
		var html_tag = menu_widgets.find(".menu-widget-html-tag").first();
		var html_text = menu_widgets.find(".menu-widget-html-text").first();
		var html_comment = menu_widgets.find(".menu-widget-html-comment").first();
		var php = menu_widgets.find(".menu-widget-php").first();
		
		//get available settings
		var available_settings = {};
		var settings = menu_settings.find(".group > ul > li, .group-block > li");
		$.each(settings, function(idx, item) {
			var c = $(item).attr("class");
			
			if (c) {
				setting_name = c.match(/settings-[a-zA-Z0-9_\-]+/g);
				setting_name = setting_name && setting_name[0] ? setting_name[0].substr(("settings-").length) : "";
				
				if (setting_name != "")
					available_settings[setting_name] = 1;
			}
		});
		
		//prepare parse html funcs
		var menu_widgets_parse_html_func = {};
		var mws = menu_widgets.find(".menu-widget");
		
		$.each(mws, function(idx, menu_widget) {
			menu_widget = $(menu_widget);
			var widget_tag = menu_widget.attr("data-tag");
			var parse_html_func = menu_widget.attr("data-on-parse-template-widget-html-func");
			
			if (parse_html_func && eval('typeof ' + parse_html_func + ' == "function"'))
				eval("menu_widgets_parse_html_func[widget_tag] = " + parse_html_func + ";");
		});
		
		return {
			html_tag: html_tag,
			html_text: html_text,
			html_comment: html_comment,
			php: php,
			available_settings: available_settings,
			menu_widgets_parse_html_func: menu_widgets_parse_html_func
		};
	}
	
	function convertHtmlContentIntoWidget(elm, template_container, options) {
		var widget = null;
		
		try {
			var html_tag = options["html_tag"];
			var html_text = options["html_text"];
			var html_comment = options["html_comment"];
			var php = options["php"];
			var available_settings = options["available_settings"];
			var menu_widgets_parse_html_func = options["menu_widgets_parse_html_func"];
			var recreate = options["recreate"];
			var j_elm = $(elm);
			
			//console.log(elm.nodeType);continue ;
			
			if (elm.nodeType == Node.ELEMENT_NODE) { //it means it is a html tag and not a simple text node.
				var node_name = elm.nodeName.toLowerCase();
				var droppable = null;
				
				if (j_elm.is(".not-widget, .layout-ui-editor-reserved")) { //for elements that we which not to parse
					if (!template_container.is(j_elm.parent())) {
						//in case of a script, use appendChild instead, otherwise the script node won't be added to the template_container in some browsers.
						template_container[0].appendChild(j_elm[0]); //I cannot do template_container.append(widget), bc if there is an element tag (e.g: DIV) which contains a script tag (as it's child or sub-child), but this script tag contains javascript code with errors, the jquery will parse this DIV and it's children, including the javascript code. In this case the jquery will return an exception. If we use the appendChild method instead, the browser will not parse the element's children, even if there are errors.
					}
					
					return null;
				}
				else if (me.hasNodeClass(j_elm, "ignore-widget")) {
					me.removeNodeClass(j_elm, "ignore-widget");
					widget = j_elm;
					droppable = $.inArray(node_name, me.singular_html_tags) != -1 ? null : widget;
					
					if (!template_container.is(widget.parent())) {
						//in case of a script, use appendChild instead, otherwise the script node won't be added to the template_container in some browsers.
						template_container[0].appendChild(widget[0]); //I cannot do template_container.append(widget), bc if there is an element tag (e.g: DIV) which contains a script tag (as it's child or sub-child), but this script tag contains javascript code with errors, the jquery will parse this DIV and it's children, including the javascript code. In this case the jquery will return an exception. If we use the appendChild method instead, the browser will not parse the element's children, even if there are errors.
					}
				}
				else {
					/*var is_special = elementContainsPtlOrPhpTagNotParsed(elm);
					
					if (is_special) {
						console.log(elm);
						
						//create special widget where we edit directly the html in the widget. => NO NEED TO DO THIS ANYMORE (at least for now bc we solve in other way a previous bug)
						//widget = ;
					}
					else {*/
						var parse_tag_func = function(tag) {
							var parse_func = menu_widgets_parse_html_func[tag];
							var ret = null;
							
							if (parse_func && typeof parse_func == "function")
								ret = parse_func(elm);
							
							if (typeof me.options.on_parse_template_widget_html_func == "function")
								ret = me.options.on_parse_template_widget_html_func(elm, ret);
							
							if (ret || $.isPlainObject(ret)) {
								var menu_widget = menu_widgets.find('.menu-widget[data-tag="' + tag + '"]').first();
								widget = createTemplateWidgetFromMenuWidget(menu_widget, template_container, elm, options);
								
								if ($.isPlainObject(ret)) {
									if (ret.hasOwnProperty("droppable"))
										droppable = ret.droppable;
									else
										droppable = me.hasNodeClass(widget, "droppable") ? widget : null;
									
									if (!droppable && ret.hasOwnProperty("widget-droppable-selector"))
										droppable = widget.find( ret["widget-droppable-selector"] ).first();
								}
								
								if (widget)
									return true;
							}
							
							return false;
						};
						var cont = true;
						
						if (recreate) {
							var tag = j_elm.data("data-tag");	
							
							if (tag) {
								if (tag == html_comment.attr("data-tag")) {
									widget = createTemplateWidgetFromMenuWidget(html_comment, template_container, elm, options);
									cont = false;
								}
								else if (parse_tag_func(tag))
									cont = false;
							}
							else if (elm.hasAttribute("data-is-widget-html-comment")) {
								widget = createTemplateWidgetFromMenuWidget(html_comment, template_container, elm, options);
								cont = false;
							}
						}
						
						//loop all available menu_widgets and execute parseHtml function. If parseHtml returns something it means that the menu_widget recognized the html elm. Otherwise returns false and loop continues to the next menu_widget.
						//console.log(menu_widgets_parse_html_func);
						if (cont)
							for (var tag in menu_widgets_parse_html_func) {
								if (parse_tag_func(tag))
									break;
							}
					//}
					
					//parse element through a default widget (html_tag)
					if (!widget) {
						/*if ($.inArray(node_name, me.singular_html_tags) != -1) {
							widget = j_elm;
							droppable = null;
							
							if (!template_container.is(widget.parent())) {
								//in case of a script, use appendChild instead, otherwise the script node won't be added to the template_container in some browsers.
								template_container[0].appendChild(widget[0]); //I cannot do template_container.append(widget), bc if there is an element tag (e.g: DIV) which contains a script tag (as it's child or sub-child), but this script tag contains javascript code with errors, the jquery will parse this DIV and it's children, including the javascript code. In this case the jquery will return an exception. If we use the appendChild method instead, the browser will not parse the element's children, even if there are errors.
							}
						}
						else {*/
							widget = createTemplateWidgetFromMenuWidget(html_tag, template_container, elm, options);
							droppable = $.inArray(node_name, me.singular_html_tags) != -1 ? null : widget;
						//}
					}
				}
				
				//parse children
				if (droppable && droppable[0] && node_name != "iframe")
					widget.data("droppable", droppable);
			}
			else if (elm.nodeType == Node.TEXT_NODE) { //Text node
				//Do not use appendChild(elm) otherwise if this node contains some special characters like &nbsp; or &larr; or any other chars in unicode, they won't be parsed as html, but they will be converted to text like: '&amp;nbsp;'. Use instead append(elm.textContent) so the jquery can parse the chars correctly.
				//Which means we should use append(elm.textContent) and then remove the elm, otherwise it will add it twice.
				//template_container[0].appendChild(elm); 
				
				//Note: No need to use the html_text, bc this will convert this node in to it self, so we simple add it directly bc is faster. The html_text is only used for drag&drop, which will convert the dragged obj to a text node.
				//console.log(elm.textContent);
				
				var text = elm.textContent;
				
				//if the convertHtmlContentIntoWidget gets call from convertHtmlElementToWidget encode the html entities in textContent bc there are decoded, bc they come from the main_node.contents() which decodes this html entities in the text nodes.
				if (options["convert_text_nodes_content"]) {
					text = text.replace(/</g, "&lt;").replace(/>/g, "&gt;"); //must replace > and < chars bc if the convertHtmlContentIntoWidget gets call from convertHtmlElementToWidget
				}
				
				if (!template_container.is(j_elm.parent()))
					template_container.append(text);
				else 
					j_elm.after(text);
				
				j_elm.remove();
			}
			else if (elm.nodeType == Node.COMMENT_NODE) { //Comment node or php node
				//console.log(elm);
				var text = elm.textContent;
				//console.log(text);
				
				if (text.match(/^(not-widget|layout-ui-editor-reserved)(\s|[^a-z0-9]|$)/)) {
					template_container.append(elm);
					
					return null;
				}
				
				var is_php = text.substr(0, 1) == "?" && text.substr(text.length - 1) == "?";
				
				if (is_php && php && php[0]) //maybe the php widget was not loaded, so we must check if it exists first.
					widget = createTemplateWidgetFromMenuWidget(php, template_container, elm, options);
				else
					widget = createTemplateWidgetFromMenuWidget(html_comment, template_container, elm, options);
			}
			else
				widget = createTemplateWidgetFromMenuWidget(html_comment, template_container, elm, options);
		} 
		catch(e) {
			console.log(elm);
			console.log(e);
			throw e;
		}
		
		return widget;
	}
	
	function elementContainsPtlOrPhpTagNotParsed(elm) {
		var html = me.getCleanedHtmlElement(elm);
		return HtmlElementContainsPtlOrPhpTagNotParsed(html);
	}
	
	//Note that this function doesn't detect the cases where there are ptl or php inside of tags, like this: <input <ptl:echo $x> name="foo">
	//loop all chars and if tag open and quote open, check if there is ptl or php.
	//if yes, checks if exists ptl/php tags outside of the attributes and if any of them contains >. If yes returns true;
	//Both cases means that the ptl/php tags were not parsed correctly before, so this html elements should be shown as a special case.
	function HtmlElementContainsPtlOrPhpTagNotParsed(html) {
		//console.log(html);
		
		if (html) {
			var char, tag_html, is_ptl, is_php, code, odq = false, osq = false;
			
			for (var i = 0; i < html.length; i++) {
				char = html[i];
				
				if (char == "<" && MyHtmlBeautify.isTagHtml(html, i) && !MyHtmlBeautify.isPHP(html, i) && !MyHtmlBeautify.isPTL(html, i)) {
					for (var j = i + 1; j < html.length; j++) {
						char = html[j];
						
						if (char == '"' && !osq && !MyHtmlBeautify.isCharEscaped(html, j))
							odq = !odq;
						else if (char == "'" && !odq && !MyHtmlBeautify.isCharEscaped(html, j))
							osq = !osq;
						else if (char == "<") { 
							is_ptl = MyHtmlBeautify.isPTL(html, j);
							is_php = MyHtmlBeautify.isPHP(html, j);
							
							if (is_ptl || is_php) {
								tag_html = is_ptl ? MyHtmlBeautify.getPTL(html, j) : MyHtmlBeautify.getPHP(html, j);
								code = tag_html[0];
								j = tag_html[1];
								
								if (!odq && !osq && code.substr(code.length - 1) == ">")
									return true;
							}
						}
						
						if (char == ">" && !odq && !osq) 
							return false;
					}
					
					return false;
				}
			}
		}
		
		return false;
	}
	
	//Used in convertLinearToHierarchicallyNodes and saveMenuSettingsNonPropertiesField
	//Note that this function detects the cases where there are ptl or php inside of tags (inside or outside of attributes), like this: <input <ptl:echo $x> name="foo <?= $x; ?> bar">
	//However for ptl outside of attributes, its gets messed up and is not working correctly.
	/*
	 * You can try test this method with the following html:
	 	some html <?= $y + 1; ?>
		<div <?php echo "w=&quot;111&quot;"; ?> x="<?php echo "t=&quot;222&quot;"; ?>" age="3&quot;3" <ptl:echo"asd"/> >asdas</div>
		bla ble<ptl:echo "jp"/> some other html
		<div class="droppable template-widget template-widget-html-tag template_widget_html-tag_l5i2y_681 widget-active html-tag list-responsive" id="widget_list_widget_list_1" data-widget-list="" data-widget-props="{&quot;pks_attrs_names&quot;:&quot;id&quot;, &quot;load&quot;:&quot;MyWidgetResourceLib.ListHandler.loadListTableAndTreeResource&quot;}" data-widget-resources="{&quot;load&quot;:[{&quot;name&quot;:&quot;items&quot;}],&quot;remove&quot;:[{&quot;name&quot;:&quot;delete_item&quot;}]}" xxx="<ptl:if><ptl:echo $x/></ptl:if>" <ptl:if><ptl:echo $x/></ptl:if>> asd a</div>
		<div <ptl:if><ptl:echo $x/></ptl:if>> asd a</div>
	 */
	function encodeInnerPTLAndPHPTags(html) {
		//loop all chars and if tag open, check if there is ptl or php.
		//console.log(html);
		
		var new_html = "";
						
		if (html) {
			var char, tag_html, is_ptl, is_php, contains_ptl_or_php, code, odq, osq;
			
			html = html.replace(/<!--\?/g, "<?").replace(/\?-->/g, "?>").replace(/\?="">/g, "?>"); //fix php code
			
			//Prepare html indentation
			for (var i = 0, t = html.length; i < t; i++) {
				char = html[i];
				
				if (char == "<") {
					if (MyHtmlBeautify.isPHP(html, i)) { //parse php code if exists
						tag_html = MyHtmlBeautify.getPHP(html, i);
						new_html += tag_html[0];
						i = tag_html[1];
					}
					else if (MyHtmlBeautify.isPTL(html, i)) { //parse ptl if exists
						tag_html = MyHtmlBeautify.getPTL(html, i);
						new_html += tag_html[0];
						i = tag_html[1];
					}
					else if (MyHtmlBeautify.isComment(html, i)) { //parse comment if exists
						tag_html = MyHtmlBeautify.getComment(html, i);
						new_html += tag_html[0];
						i = tag_html[1];
					}
					else if (MyHtmlBeautify.isTagHtml(html, i)) { //parse html element
						new_html += char;
						contains_ptl_or_php = false;
						
						odq = osq = false;
						
						//loop all chars and if tag open, check if there is ptl or php.
						for (var j = i + 1; j < html.length; j++) {
							char = html[j];
							
							if (char == '"' && !osq && !MyHtmlBeautify.isCharEscaped(html, j))
								odq = !odq;
							else if (char == "'" && !odq && !MyHtmlBeautify.isCharEscaped(html, j))
								osq = !osq;
							else if (char == "<") { 
								is_ptl = MyHtmlBeautify.isPTL(html, j);
								is_php = MyHtmlBeautify.isPHP(html, j);
								
								if (is_ptl || is_php) {
									contains_ptl_or_php = true;
									tag_html = is_ptl ? MyHtmlBeautify.getPTL(html, j) : MyHtmlBeautify.getPHP(html, j);
									code = tag_html[0];
									//console.log(code);
									
									/*
									 * This is to fix the issue for the cases: 
									 *	<input placeHolder="<ptl:echo str_replace('"', '&quot;', ($input[article_id] )) />" />
									 * ...where we want to convert &quot; to &amp;quot;, this is:
									 * 	<input placeHolder="<ptl:echo str_replace('"', '&amp;quot;', ($input[article_id] )) />" />
									 * Otherwise the browser parser will convert the &quot; to " and we will end up with:
									 * 	<input placeHolder="<ptl:echo str_replace('"', '"', ($input[article_id] )) />" />
									 * ...which doesn't make any sense!
									 */
									code = code.replace(/&/g, "&amp;");
									
									/*
									 * For some reason the html parser of the browser adds ="" to the code and '" ', this is, 
									 * If we have this html:
									 * 	<input   name="foo <ptl:echo "$x"> bar"> <input <ptl:echo $x> name="foo">
									 * the browser parser will return:
									 * 	<input name="foo <ptl:echo " $x"=""> bar"> and <input <ptl:echo="" $x=""> name="foo"&gt;
									 * We the code bellow we want to return the following html:
									 * 	<input name="foo <ptl:echo &quot;$x&quot;> bar"> <input <ptl:echo$x&gt; name="foo">
									 */
									if (odq && code.indexOf('"'))
										code = code.replace(/=""/g, "").replace(/" /g, '"').replace(/"/g, "&quot;");
									else if (osq && code.indexOf("'"))
										code = code.replace(/=''/g, "").replace(/' /g, "'").replace(/'/g, "&#39;");
									else if (!odq && !osq) {
										//Note that if we don't make these replacements, the parent tag will end at the first ">", which corresponds to the PTL or PHP and not the closing tag of it's node.
										if (code.substr(code.length - 1) == ">") 
											code = code.substr(0, code.length - 1).replace(/=""/g, "") + "&gt;";
											//console.log("code:"+code);
										
										/*if (code.substr(0, 1) == "<") //Note that if we don't make this replacements, the parent will detect a new node "<", which corresponds to the PTL or PHP and that will mess the detection of all nodes.
											code = "&lt;" + code.substr(1);*/
										
										//Note that if we don't make this replacements, the parent will loose the slash.
										if (code.indexOf("/") != -1)
											code = code.replace(/\//g, "&#47;");
										
										//Note that if we don't make this replacements,the parent will mess the attributes inside of ptl and php.
										if (is_ptl || is_php)
											code = code.replace(/"/g, "&quot;").replace(/=/g, "&#61;");
									}
									
									/* If there is any ptl code ending in /> inside of a tag attribute, we must replace the > by &gt; */
									if (is_ptl && (odq || osq) && code.substr(code.length - 2) == "/>")
										code = code.substr(0, code.length - 1) + "&gt;";
									
									//console.log("code:"+code);
									
									char = code;
									j = tag_html[1];
								}
							}
							
							/* This is to fix the cases where the html is: 
							 * 	<input <ptl:echo="" $x=""> name="foo"&gt;
							 * instead of 
							 * 	<input <ptl:echo="" $x=""> name="foo">
							 * 
							 * For some reason when we get the HTML inside of an element (like elm.innerHTML or $(elm).html()), this happens, this is, the last > is translated to &gt;
							 */
							if (contains_ptl_or_php && char == "&" && html.substr(j, 4) == "&gt;" && !odq && !osq) {
								new_html += ">";
								j += 3; //so j is on the position of ;
								break;
							}
							
							new_html += char;
							
							if (char == ">" && !odq && !osq) 
								break;
						}
						
						i = j;
					}
					else
						new_html += char;
				}
				else
					new_html += char;
			}
		}
		//console.log("**************************************");
		//console.log(new_html);
		
		return new_html;
	}
	
	/* TEMPLATE SOURCE METHODS */
	
	function initTemplateSource() {
		var textarea = template_source.children("textarea");
		
		createCodeEditor(textarea[0], me.options);
	}
	
	function initTemplateFullSource() {
		var textarea = template_full_source.children("textarea");
		textarea.val(me.default_template_full_source_html);
		
		createCodeEditor(textarea[0], me.options);
		
		//init body html in template-source if already exists in template-full-source or vice-versa
		var is_template_source_system_created = template_source.attr("data-created-by-system") == "1";
		
		if (is_template_source_system_created)
			convertTemplateFullSourceToSource();
		else //if template-full-source already existed or not, always update the body code from the template-source
			convertTemplateSourceToFullSource();
	}
	
	//To be used by external calls like the inlinehtml task in the workflows
	me.forceTemplateSourceConversionAutomatically = function() {
		ui.find(" > .tabs > .view-source").addClass("do-not-confirm").click().removeClass("do-not-confirm");
	};
	
	//To be used by external calls like the inlinehtml task in the workflows
	me.forceTemplateLayoutConversionAutomatically = function() {
		ui.find(" > .tabs > .view-layout").addClass("do-not-confirm").click().removeClass("do-not-confirm");
	};
	
	//Do not beautify html here, otherwise it can break some ptl and php tags
	me.setTemplateSourceEditorValue = function(html) {
		var editor = template_source.data("editor");
		
		if (editor) {
			editor.setValue(html, -1);
			editor.focus();
		}
		else
			template_source.children("textarea").val(html).focus();
	};
	
	me.getTemplateSourceEditorValue = function() {
		var editor = template_source.data("editor");
		return editor ? editor.getValue() : template_source.children("textarea").val();
	};
	
	//Do not beautify html here, otherwise it can break some ptl and php tags
	me.setTemplateFullSourceEditorValue = function(html) {
		var editor = template_full_source.data("editor");
		
		if (editor) {
			editor.setValue(html, -1);
			editor.focus();
		}
		else
			template_full_source.children("textarea").val(html).focus();
	};
	
	me.getTemplateFullSourceEditorValue = function() {
		var editor = template_full_source.data("editor");
		return editor ? editor.getValue() : template_full_source.children("textarea").val();
	};
	
	//Do not beautify html here, otherwise it can break some ptl and php tags
	me.setTemplateFullSourceHeadEditorValue = function(head_html, head_attrs) {
		head_html = head_html ? head_html.replace(/^\s+/g, "").replace(/\s+$/g, "") : "";
		head_html = head_html ? "\t\t" + head_html.replace(/\n/g, "\n\t\t") : "";
		
		var html = me.getTemplateFullSourceEditorValue();
		
		//Note that the bc full-source allows only the body html too, which means that allows html without the HTML or HEAD tag. So we must do this check and only change this html if the head tag exists.
		if (me.existsTagFromSource(html, "head"))
			html = me.replaceTagContentFromSource(html, "head", head_html);
		else if (head_html != "" && me.existsTagFromSource(html, "html")) {
			var html_lower = html.toLowerCase();
			var pos = html_lower.indexOf("<html");
			
			if (pos != -1) {
				var tag_settings = MyHtmlBeautify.getTagHtml(html_lower, pos);
				pos = tag_settings[1] + 1;
				
				html = html.substr(0, pos) + "\t<head>" + head_html + "\n\t</head>" + html.substr(pos);
			}
		}
		//else do nothing, bc the full source can be only the body html, so we cannot add the head html. WE ONLY ADD THE HEAD HTML IF EXISTS HTML OR HEAD TAG!
		
		if (typeof head_attrs == "string" && me.existsTagFromSource(html, "head"))
			html = me.replaceTagAttributesFromSource(html, "head", head_attrs);
		
		me.setTemplateFullSourceEditorValue(html);
	};
	
	me.getTemplateFullSourceHeadEditorValue = function() {
		var html = me.getTemplateFullSourceEditorValue();
		
		if (me.existsTagFromSource(html, "head"))
			return me.getTagContentFromSource(html, "head"); 
		
		return ""; //Note that the bc full-source can be only the body html too, which means that allows html without the HTML or HEAD tag.
	};
	
	//Do not beautify html here, otherwise it can break some ptl and php tags
	me.setTemplateFullSourceBodyEditorValue = function(body_html, body_attrs) {
		body_html = body_html ? body_html.replace(/^\s+/g, "").replace(/\s+$/g, "") : "";
		
		var html = me.getTemplateFullSourceEditorValue();
		
		//Note that the bc full-source allows only the body html too, which means that allows html without the HTML or HEAD tag. So we must do this check.
		if (me.existsTagFromSource(html, "body")) {
			body_html = "\t\t" + body_html.replace(/\n/g, "\n\t\t");
			html = me.replaceTagContentFromSource(html, "body", body_html);
		}
		else if (body_html != "" && me.existsTagFromSource(html, "html")) {
			var html_lower = html.toLowerCase();
			var pos = html_lower.indexOf("</html>");
			
			if (pos != -1) {
				body_html = "\t\t" + body_html.replace(/\n/g, "\n\t\t");
				html = html.substr(0, pos) + "\t<body>" + body_html + "\n\t</body>" + html.substr(pos);
			}
		}
		else
			html = body_html;
		
		if (typeof body_attrs == "string" && me.existsTagFromSource(html, "body"))
			html = me.replaceTagAttributesFromSource(html, "body", body_attrs);
		
		me.setTemplateFullSourceEditorValue(html);
	};
	
	me.getTemplateFullSourceBodyEditorValue = function() {
		var html = me.getTemplateFullSourceEditorValue();
		
		if (me.existsTagFromSource(html, "body"))
			return me.getTagContentFromSource(html, "body");
		else if (!me.existsTagFromSource(html, "html"))
			return html; //Note that the bc full-source can be only the body html too, which means that allows html without the HTML or HEAD tag.
		
		return "";
	};
	
	me.replaceTagContentFromSource = function(html, tag_name, text) {
		var html_lower = html.toLowerCase();
		var pos = html_lower.indexOf("<" + tag_name + ">");
		pos = pos != -1 ? pos : html_lower.indexOf("<" + tag_name + " ");
		
		if (pos == -1)
			me.showException("Error in LayoutUIEditor.replaceTagContentFromSource. Couldn't replace '" + tag_name + "' content because this tag doesn't exist!");
		else {	
			var tag_settings = MyHtmlBeautify.getTagHtml(html, pos);
			var start_pos = tag_settings[1];
			var end_pos = html_lower.indexOf("</" + tag_name + ">");
			end_pos = end_pos != -1 ? end_pos : html_lower.indexOf("</" + tag_name + " ");
			
			if (end_pos == -1)
				me.showException("Error in LayoutUIEditor.replaceTagContentFromSource. Couldn't replace '" + tag_name + "' content because this tag doesn't have a valid ending tag!");
			else {
				/*console.log(tag_name);
				console.log(pos);
				console.log(tag_settings);
				console.log(start_pos);
				console.log(html.substr(start_pos-6, 6));
				console.log(end_pos);
				console.log(html.substr(end_pos, 6));
				
				//console.log(text.substr(0, 500));
				//console.log(text.substr(-500));
				//console.log(text);
				console.log(html.substr(0, start_pos + 1));
				console.log(html.substr(end_pos));*/
					
				html = html.substr(0, start_pos + 1) + "\n" + text + "\n" + html.substr(end_pos);
				//console.log(html);
			}
		}
		
		return html;
	};
	
	me.replaceTagAttributesFromSource = function(html, tag_name, text) {
		var tag_name_lower = tag_name.toLowerCase();
		var html_lower = html.toLowerCase();
		var pos = html_lower.indexOf("<" + tag_name_lower + ">");
		pos = pos != -1 ? pos : html_lower.indexOf("<" + tag_name_lower + " ");
		
		if (pos == -1)
			me.showException("Error in LayoutUIEditor.replaceTagAttributesFromSource. Couldn't replace '" + tag_name + "' attributes because this tag doesn't exist!");
		else {
			var tag_settings = MyHtmlBeautify.getTagHtml(html, pos);
			var tag_code = tag_settings[0];
			var tag_name_in_code = MyHtmlBeautify.getTagName(tag_code, 0);
			
			html = html.replace(tag_code, "<" + tag_name_in_code + (text ? " " + text : "") + ">");
		}
		
		return html;
	};
	
	//checks if exists a html tag name 
	me.existsTagFromSource = function(html, tag_name) {
		var html_lower = html.toLowerCase();
		
		return html_lower.indexOf("<" + tag_name + ">") != -1 || html_lower.indexOf("<" + tag_name + " ") != -1;
	};

	//returns a html-tag content without the tag, this is, returns the inner-html
	me.getTagContentFromSource = function(html, tag_name) {
		var html_lower = html.toLowerCase();
		var pos = html_lower.indexOf("<" + tag_name);
		var tag_html = MyHtmlBeautify.getTagContent(html, pos, tag_name); //This returns the content with the tag, this is, the outer-html
		//console.log("getTagContentFromSource");
		//console.log(tag_html);
		
		return tag_html ? tag_html[0].replace(/^\s+/, "").replace(/\s+$/, "").replace(/\n\t\t/g, "\n") : "";  //remove end-lines, trim
	};

	me.getTagAttributesFromSource = function(html, tag_name) {
		var tag_name = "<" + tag_name + " ";
		var html_lower = html.toLowerCase();
		var pos = html_lower.indexOf(tag_name);
		
		if (pos != -1) {
			var tag_html = MyHtmlBeautify.getAttributesContent(html, pos + tag_name.length, ">");
			
			if (tag_html)
				return $("<div " + tag_html[0] + "></div>")[0].attributes;
		}
		
		return null;
	};
	
	/* TEMPLATE LAYOUT METHODS */
	
	/* To be used by external parties in order to get the code from the template_widgets*/
	me.getTemplateLayoutBodyHtml = function() {
		//if multiple main droppables
		if (template_widgets_droppable.length > 1) {
			var template_widgets_iframe_body_clone = template_widgets_iframe_body.clone();
			var template_widgets_droppable_clone = template_widgets_iframe_body_clone.find(".main-droppable");
			
			if (!template_widgets_droppable_clone[0] && template_widgets_iframe_body_clone.is(".main-droppable"))
				template_widgets_droppable_clone = template_widgets_iframe_body_clone;
			
			for (var i = 0; i < template_widgets_droppable.length; i++) {
				var droppable_elm = $(template_widgets_droppable[i]);
				var droppable_html = me.getElementHtml(droppable_elm);
				var new_droppable_elm = $(template_widgets_droppable_clone[i]);
				
				try {
					new_droppable_elm.html(droppable_html); //note that if the droppable_html contains a sript tag with some wrong javascript code (with errors), this will give an exception. This happens bc the jquery .html function will parse the script code.
				}
				catch(e) {
					//So in this case, where we have js errors, we use the innerHTML method, bc it doesn't parse the script tags
					try {
						new_droppable_elm[0].innerHTML = droppable_html;
					}
					catch(e) {
						if (console && console.log)
							console.log(e);
					}
				}
				
				new_droppable_elm.removeAttr("spellcheck").removeAttr("contenteditable");
			}
			
			return template_widgets_iframe_body_clone.html();
		}
		else //if only 1 main droppable
			return me.getElementHtml(template_widgets_droppable);
	};
	
	me.getTemplateLayoutBodyAttributes = function() {
		var body = template_widgets_iframe.contents().find("body");
		var body_attrs = me.getCleanedHtmlElementAttributes(body[0]);
		
		return body_attrs;
	};
	
	me.getTemplateLayoutHeadHtml = function() {
		var head = template_widgets_iframe.contents().find("head");
		var head_html = me.getElementHtml(head);
		
		//console.log("getTemplateLayoutHeadHtml");
		//console.log(head_html);
		return head_html;
	};
	
	me.getTemplateLayoutHeadAttributes = function() {
		var head = template_widgets_iframe.contents().find("head");
		var head_attrs = me.getCleanedHtmlElementAttributes(head[0]);
		
		return head_attrs;
	};
	
	/* NO NEED ANYMORE
	me.setTemplateLayoutHtml = function(html) {
		template_widgets_droppable.html("");
		
		//DEPRECATED bc when we get the innerHTML from template_widgets_droppable, the browser changes it, and we need the raw HTML.
		//template_widgets_droppable[0].innerHTML = html; //must be innerHTML otherwise the <script> tags will get lost...
		//parseTemplateLayoutHtml();
		
		if ($.isArray(html))
			for (var i = 0; i < template_widgets_droppable.length; i++) {
				var droppable_elm = $(template_widgets_droppable[i]);
				var droppable_html = html[i];
				
				me.parseHtmlSource(droppable_html, droppable_elm);
			}
		else
			me.parseHtmlSource(html, template_widgets_droppable.first());
	};*/
	
	/* CLEAN HTML METHODS */
	
	me.getElementHtml = function(element) {
		var children = element.contents(); //Do not use .children() bc it doesn't include the text nodes and comments (This is useful when we are parsing the head tag, where we need to have all nodes including textNodes and php nodes...)
		//console.log(children);
		
		return me.getElementContentsHtml(children);
	};
	
	//This is used in the edit_page_and_template.js
	me.getElementContentsHtml = function(contents) {
		var html = me.getCleanedHtmlContents(contents);
		//console.log(html);
		
		html = decodeInnerPTLAndPHPTags(html);
		//console.log(html);
		
		if (me.options.beautify) {
			html = MyHtmlBeautify.beautify(html); //beautify code
			//console.log(html);
		}
		
		return html;
	};
	
	me.getCleanedHtmlContents = function(contents) {
		var html = "";
		
		try {
			if (!$.isArray(contents)) {
				if (!(contents instanceof jQuery))
					return false;
				
				contents = contents.toArray();
				//console.log(contents);
			}
			
			if (!$.isArray(contents) || contents.length == 0)
				return "";
			
			//prepare html elements
			for (var i = 0; i < contents.length; i++) {
				elm = contents[i];
				
				if (elm.nodeType == Node.ELEMENT_NODE && elm.classList.contains("layout-ui-editor-reserved")) //if is a reserved element
					continue;
				else if (elm.nodeType == Node.COMMENT_NODE && elm.textContent.match(/^layout-ui-editor-reserved(\s|[^a-z0-9]|$)/))
					continue;
				
				var html_item = "";
				
				if (elm.nodeType == Node.ELEMENT_NODE) {
					var widget = $(elm);
					
					var menu_widget = me.getTemplateMenuWidget(widget);
					var clean_html_func = menu_widget[0] ? menu_widget.attr("data-on-clean-template-widget-html-func") : null;
					
					if (clean_html_func && eval('typeof ' + clean_html_func + ' == "function"'))
						eval("html_item = " + clean_html_func + "(elm);");
					else
						html_item = me.getCleanedHtmlElement(elm);
				}
				else
					html_item = me.getCleanedHtmlElement(elm);
				
				if (typeof me.options.on_clean_template_widget_html_func == "function")
					html_item = me.options.on_clean_template_widget_html_func(elm, html_item);
				
				html += html_item;
			}
		} 
		catch(e) {
			console.log(contents);
			console.log(e);
			throw e;
		}
		
		return html;
	};
	
	me.getCleanedHtmlElement = function(elm) {
		var html = "";
		
		if (elm) {
			if (elm.nodeType == Node.ELEMENT_NODE) {
				var j_elm = $(elm);
				//var ignore_attributes = ["class", /*"data-label", "data-tag", "data-target-id", "data-template-id", "data-resizable", "data-absolute-position", "old-style"*/];
				var text_nodes = ["style", "script"];
				var no_childs_nodes = MyHtmlBeautify.single_html_tags;
				
				var node_name = elm.nodeName.toLowerCase();
				var is_no_childs_node = no_childs_nodes.indexOf(node_name) != -1;
				
				html += "<" + node_name;
				html += me.getCleanedHtmlElementAttributes(elm);
				
				if (node_name == "textarea")
					html += ">" + j_elm.val() + "</textarea>";
				else {
					//adding /> to some nodes
					if (is_no_childs_node)
						html += "/";
					
					html += ">";
					
					if (!is_no_childs_node) {
						if (text_nodes.indexOf(node_name) != -1)
							html += elm.innerHTML;
						else if (node_name != "iframe") 
							html += me.getCleanedHtmlContents(j_elm.contents()); //do not use j_elm.children(), bc it will not include the text nodes 
						html += "</" + node_name + ">";
					}
				}
			}
			else if (elm.nodeType == Node.COMMENT_NODE) { //comment or php - note that the systems uses this when is parsing the head html
				var text = elm.textContent;
				var is_php = text.substr(0, 1) == "?" && text.substr(text.length - 1) == "?";
				//console.log(text);
				
				if (is_php)
					html += "<" + text + ">";
				else
					html += "<!--" + text + "-->";
			}
			else if (elm.textContent != "" && elm.textContent.replace(/\s+/g, "") != "" /*&& elm.nodeType == Node.TEXT_NODE*/) { //note that the systems uses this when is parsing the head html
				var text = elm.textContent;
				
				//replace \u2003 which is a weird white space char that is created when we call 'pre.html()' function and it converts the \t to this char.
				text = text.replace(/\u2003/g, "\t"); 
				
				//text = text.replace(/(\r\n|\n|\r|\t|\f|\v)(\r\n|\n|\r|\t|\f|\v)+/g, ""); //Do not add this code, bc a text can have multiple lines in between texts. If this text is inside of a <pre> element, then it should show multiple lines.
				//text = text.replace(/(\t|\f|\v)(\t|\f|\v)+/g, ""); //removes multiple tabs, form feed chars and vertical tabs. Disabled his. if user inserted a tab, then we shoud include it. Later on the tab will be converted to &emsp;.
				text = text.replace(/(\f|\v)+/g, ""); //removes form feed chars and vertical tabs.
				//text = text.replace(/^(\r\n|\n|\r|\t|\f|\v)+/g, "").replace(/(\r\n|\n|\r|\t|\f|\v)+$/g, ""); //trim textContent, by removing all whitespace chars, except the spaces it self, this is, including spaces. \f is form feed character and \v is vertical tab character. This is DEPRECATED bc if the node has some end lines or tabs in the beggining or at the end, it was bc the user inserted it on purpose.
				
				//DEPRECATED - bc the browser already returns the text with the right html refering to the spaces, end lines and tabs
				//text = text.replace(/&nbsp;/g, " ").replace(/\n/g, "<br>").replace(/\t/g, "&emsp;");
				
				//very important, otherwise if we have "&lt;" the next time we load this content it wil convert it to "<"
				text = text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
				
				//"he" object is the class that encodes unicode chars to html entities, this is, convert unicode chars to "&times;", "&copy;", etc...
				if (typeof he == "object") 
					text = he.encode(text, {
						"useNamedReferences": true,
						"allowUnsafeSymbols": true
					});
				
				//replace unicode for tab with the right html entity, otherwise this unicode won't work in the browsers.
				text = text.replace(/&#x9;/g, "&emsp;");
				
				/*DEPRECATED: bc I can have unicode chars. Note that the unicode chars will be converted in the he.encode method.
				//remove the weird chars from code, this is, in the php editor appears some red dots in the code, which means there some weird chars in the code.
				var eol_control_var = "#" + (Math.random() * 10000000) + "__SYSTEM_EOL_" + (Math.random() * 10000000) + "#";
				text = text.replace(/\n/g, eol_control_var);
				text = text.replace(/[^\x20-\x7E]/g, ''); //This will remove the end_lines too but the MyHtmlBeautify.beautify will add them again
				var regex = new RegExp(eol_control_var, "g");
				text = text.replace(regex, "\n");*/
				
				html += text;
			}
		}
		
		return html;
	};
	
	me.getCleanedHtmlElementAttributes = function(elm, opts) {
		var html = "";
		
		if (elm && elm.nodeType == Node.ELEMENT_NODE) {
			var j_elm = $(elm);
			var node_name = elm.nodeName.toLowerCase();
			var non_empty_attrs = ["id", "class", "name", "value", "style"];
			var single_attrs = ["selected", "checked", "readonly", "disabled", "required"];
			var ignore_attributes = [/*"data-label", "data-tag", "data-target-id", "data-template-id", "data-resizable", "data-absolute-position", "old-style"*/];
			var attributes_to_exclude = opts && $.isArray(opts["attributes_to_exclude"]) ? opts["attributes_to_exclude"] : [];
			var attributes_to_include = opts && $.isArray(opts["attributes_to_include"]) ? opts["attributes_to_include"] : [];
			
			if (me.LayoutUIEditorWidgetResource)
				single_attrs = single_attrs.concat( me.LayoutUIEditorWidgetResource.getWidgetSingleAttributes(j_elm) );
			
			if (j_elm.is(".droppable")) {
				ignore_attributes.push("contenteditable");
				ignore_attributes.push("spellcheck");
			}
			
			//adding class
			if (elm.hasAttribute("class") && attributes_to_exclude.indexOf("class") == -1 && (attributes_to_include.length == 0 || attributes_to_include.indexOf("class") != -1)) {
				ignore_attributes.push("class");
				var widget_classes = me.getTemplateWidgetCurrentClassesWithoutReservedClasses(j_elm);
				
				if (widget_classes.replace(/\s+/g, "") != "")
					html += ' class="' + widget_classes + '"';
			}
			
			//adding value attribute for inputs and textareas
			if ((node_name == "input" || node_name == "option") && attributes_to_exclude.indexOf("value") == -1 && (attributes_to_include.length == 0 || attributes_to_include.indexOf("value") != -1)) {
				if (j_elm.val() != "") {
					ignore_attributes.push("value");
					html += ' value="' + prepareHtmlForDoubleQuotesEncapsulation(j_elm.val()) + '"'; //set value attr. Otherwise the input won't write the value attr with the real value.
				}
				else if (node_name == "option" || elm.type == "checkbox" || elm.type == "radio") { //j_elm.val() == ""
					ignore_attributes.push("value");
					html += ' value=""'; //set value attr. Otherwise the option|checkbox|radio won't write the value attr with the real value. In case of checkbox|radio it will set the value "on" by default if no attribute value is written.
				}
			}
			
			//adding attributes
			var attrs = "";
			
			//console.log(elm.attributes);
			$.each(elm.attributes, function(idx, attr) {
				var n = attr.name.toLowerCase();
				
				if (ignore_attributes.indexOf(n) == -1 && attributes_to_exclude.indexOf(n) == -1 && (attributes_to_include.length == 0 || attributes_to_include.indexOf(n) != -1)) {
					var v = attr.value != "" || n.substr(0, 5) == "data-" ? '="' + prepareHtmlForDoubleQuotesEncapsulation(attr.value) + '"' : ""; //if attr.value has " inside, the html will be messed
					
					if (single_attrs.indexOf(n) != -1)
						attrs += " " + attr.name;
					else if (v != "" || non_empty_attrs.indexOf(n) == -1)
						attrs += " " + attr.name + v;
				}
			});
			
			html += attrs;
		}
		
		return html;
	};
	
	me.cloneHtmlElementBeforeCleanIt = function(elm) {
		elm = $(elm);
		
		var clone = elm.clone(true); //true is very important so it can copy the data values and events
		
		//DEPRECATED - No need anymore bc the jquery clone function receives the first parameter true to copy the data values and events.
		/*var data_items = elm.data();
		
		if (data_items)
			for (var k in data_items)
				clone.data(k, data_items[k]);
		
		//clone data in the children
		var update_data_in_children_func = function(original_elm, clone_elm) {
			var children = original_elm.children();
			var clone_children = clone_elm.children();
			
			$.each(children, function(idx, child) {
				var clone_child = clone_children[idx];
				
				if (clone_child) {
					child = $(child);
					var child_data_items = child.data();
					
					if (child_data_items)
						for (var k in child_data_items)
							clone_child.data(k, child_data_items[k]);
					
					update_data_in_children_func(child, clone_child);
				}
			});
		};
		update_data_in_children_func(elm, clone);*/
		
		return clone;
	};
	
	function prepareHtmlForDoubleQuotesEncapsulation(html) {
		var new_html = "";
		var char, attr, ptl = [], php = [], comment = [], odq = false, osq = false, is_tag_close, is_tag_dec;
		
		for (var i = 0; i < html.length; i++) {
			char = html[i];
			
			if (char == '"')
				char = "&quot;";
			else if (char == "<") {
				if (MyHtmlBeautify.isComment(html, i)) {
					comment = MyHtmlBeautify.getComment(html, i);
					i = comment[1];
					new_html += comment[0];
					
					continue;
				}
				else if (MyHtmlBeautify.isPHP(html, i)) {
					php = MyHtmlBeautify.getPHP(html, i);
					i = php[1];
					new_html += php[0];
					
					continue;
				}
				else if (MyHtmlBeautify.isPTL(html, i)) {
					ptl = MyHtmlBeautify.getPTL(html, i);
					i = ptl[1];
					new_html += ptl[0];
					
					continue;
				}
			}
			
			new_html += char;
		}
		
		return new_html;
	}
	
	//used in getElementContentsHtml
	/*
	 * You can try test this method with the following html:
	 	some html <?= $y + 1; ?>
		<div <?php echo "w=&quot;111&quot;"; ?> x="<?php echo "t=&quot;222&quot;"; ?>" age="3&quot;3" <ptl:echo"asd"/> >asdas</div>
		bla ble<ptl:echo "jp"/> some other html
		<div class="droppable template-widget template-widget-html-tag template_widget_html-tag_l5i2y_681 widget-active html-tag list-responsive" id="widget_list_widget_list_1" data-widget-list="" data-widget-props="{&quot;pks_attrs_names&quot;:&quot;id&quot;, &quot;load&quot;:&quot;MyWidgetResourceLib.ListHandler.loadListTableAndTreeResource&quot;}" data-widget-resources="{&quot;load&quot;:[{&quot;name&quot;:&quot;items&quot;}],&quot;remove&quot;:[{&quot;name&quot;:&quot;delete_item&quot;}]}" xxx="<ptl:if><ptl:echo $x/></ptl:if>" <ptl:if><ptl:echo $x/></ptl:if>> asd a</div>
		<div <ptl:if><ptl:echo $x/></ptl:if>> asd a</div>
	 */
	function decodeInnerPTLAndPHPTags(html) {
		//loop all chars and if tag open, check if there is ptl or php.
		//console.log(html);
		
		var new_html = "";
		var ua = window.navigator.userAgent;
		var msie = ua.indexOf("MSIE") != -1 || ua.indexOf("Trident/") != -1 || ua.indexOf("Edge/") != -1;
		//console.log(msie);
						
		if (html) {
			var char, sub_char, tag_html, tag_code, tag_index, is_ptl, is_php, contains_ptl_or_php, code, pos, odq, osq, sub_odq, sub_osq, ie_special_cases = [];
			
			//Prepare html indentation
			for (var i = 0, t = html.length; i < t; i++) {
				char = html[i];
				
				if (char == "<") {
					if (MyHtmlBeautify.isPHP(html, i)) { //parse php code if exists
						tag_html = MyHtmlBeautify.getPHP(html, i);
						new_html += tag_html[0];
						i = tag_html[1];
					}
					else if (MyHtmlBeautify.isPTL(html, i)) { //parse ptl if exists
						tag_html = MyHtmlBeautify.getPTL(html, i);
						new_html += tag_html[0];
						i = tag_html[1];
					}
					else if (MyHtmlBeautify.isComment(html, i)) { //parse comment if exists
						tag_html = MyHtmlBeautify.getComment(html, i);
						new_html += tag_html[0];
						i = tag_html[1];
					}
					else if (MyHtmlBeautify.isTagHtml(html, i)) { //parse html element
						new_html += char;
						contains_ptl_or_php = false;
						
						tag_html = MyHtmlBeautify.getTagHtml(html, i, "");
						tag_code = tag_html[0];
						tag_index = tag_html[1];
						//console.log("tag_html:");
						//console.log(tag_html);
						
						odq = osq = false;
						
						//loop all chars and if tag open, check if there is ptl or php.
						for (var j = 1, tj = tag_code.length; j < tj; j++) {
							char = tag_code[j];
							
							if (char == '"' && !osq && !MyHtmlBeautify.isCharEscaped(tag_code, j))
								odq = !odq;
							else if (char == "'" && !odq && !MyHtmlBeautify.isCharEscaped(tag_code, j))
								osq = !osq;
							else if (char == "<") { 
								var ptl_tag_code = tag_code.substr(j, 6) == "<&#47;" ? tag_code.substr(0, j) + "</" + tag_code.substr(j + 6) : tag_code;
								//console.log("ptl_tag_code("+j+"|"+ptl_tag_code[j]+"):"+ptl_tag_code);
								is_ptl = MyHtmlBeautify.isPTL(ptl_tag_code, j);
								is_php = MyHtmlBeautify.isPHP(tag_code, j);
								
								if (is_ptl || is_php) {
									//console.log("****************"+(is_ptl?"PTL":"PHP")+"***************");
									
									//convert &gt; to '>' when not inside of an attribute value (or inside of quotes), bc when the system run the method encodeInnerPTLAndPHPTags, it convert '>' to &gt;. So this means we need to do now the reverse. 
									//Addittionally, if ptl, do the same for the "/" char that was conerted to "&#47;"
									if (!odq && !osq) {
										sub_odq = sub_osq = false;
										
										for (var w = j + 1; w < tj; w++) {
											sub_char = tag_code[w];
											
											if (sub_char == '"' && !sub_osq && !MyHtmlBeautify.isCharEscaped(tag_code, w))
												sub_odq = !sub_odq;
											else if (sub_char == "'" && !sub_odq && !MyHtmlBeautify.isCharEscaped(tag_code, w))
												sub_osq = !sub_osq;
											else if (sub_char == ">" && !sub_odq && !sub_osq)
												break;
											else if (sub_char == "&" && tag_code.substr(w, 4) == "&gt;") {
												//very important to reset the html and get the tag_html again, otherwise the tag_html may have the wrong html, including this tag's content...
												html = html.substr(0, i) + tag_code.substr(0, w) + ">" + tag_code.substr(w + 4) + html.substr(tag_index + 1);
												t = html.length;
												
												tag_html = MyHtmlBeautify.getTagHtml(html, i, "");
												tag_code = tag_html[0];
												tag_index = tag_html[1];
												tj = tag_code.length;
												//console.log("NEW TAG CODE:"+tag_code);
												
												if (!sub_odq && !sub_osq)
													break;
											}
											else if (is_ptl && sub_char == "&" && tag_code.substr(w, 5) == "&#47;") {
												//very important to reset the html and get the tag_html again, otherwise the tag_html may have the wrong html, including this tag's content...
												html = html.substr(0, i) + tag_code.substr(0, w) + "/" + tag_code.substr(w + 5) + html.substr(tag_index + 1);
												t = html.length;
												
												tag_html = MyHtmlBeautify.getTagHtml(html, i, "");
												tag_code = tag_html[0];
												tag_index = tag_html[1];
												tj = tag_code.length;
												//console.log("NEW TAG CODE:"+tag_code);
											}
										}
									}
									
									//prepare php or ptl code
									contains_ptl_or_php = true;
									tag_html = is_ptl ? MyHtmlBeautify.getPTL(tag_code, j) : MyHtmlBeautify.getPHP(tag_code, j);
									code = tag_html[0];
									//console.log("code:"+code);
									
									/*
									 * Note: DO NOT REPLACE &quot; for ", because if there is any &quot; it's becasue the user wants &quot; 
									 * Here is an example: 
									 * 	<input placeHolder="<ptl:echo str_replace('"', '&quot;', ($input[article_id] )) />" />
									 * 	<?= str__replace('"', '&quot;', $x) ?>
									 * So PLEASE DO NOT ADD THE FOLLOWING CODE:
									 *	if (odq && !osq && code.indexOf('"'))
									 * 		code = code.replace(/&quot;/g, '"')
									 * 	else if (osq && !odq && code.indexOf("'")) //No need to do this one, bc the attributes will only replace the double quotes, because in the html that we generate, the attribute values will be only inside of double quotes. The single quotes will be left alone.
									 * 		code = code.replace(/&#39;/g, "'")
									 * 
									 * The only thing that we need to replace here is &gt; by >. THAT'S IT!!!
									 */
									
									if (!odq && !osq && (pos = code.indexOf("&gt;")) != -1) { //This is to fix the cases 	<input <ptl:echo $x&gt; name="foo"> or <input <?="$x" ?&gt; name="xxx"/> THIS HAPPENS IN CHROME AND FIREFOX AND IE
										tag_html[1] -= (code.length - (pos + 3)) - 1;
										code = code.substr(0, pos) + ">";
									}
									
									//reverse what the encodeInnerPTLAndPHPTags method did when converted the quotes to &quot;
									if ((is_ptl || is_php) && (!odq && !osq))
										code = code.replace(/&quot;/g, '"').replace(/&#61;/g, "=");
									
									//reverse what the encodeInnerPTLAndPHPTags method did when converted the "/" to &#47;
									if (!odq && !osq)
										code = code.replace(/&#47;/g, "/");
									
									//reverse what the encodeInnerPTLAndPHPTags method did when converted the & to &amp;
									code = code.replace(/&amp;/g, "&");
									
									//console.log("code:"+code);
									
									char = code;
									j = tag_html[1];
								}
							}
							else if (msie) {
								/* 	
								 * in IE when the html code is:
								 * 	<input <?="$x" ?>name="xxx"/>
								 * the IE and the LayoutUIEditor will convert and show this code like:
								 *	<input name="xxx" ?&gt; <?="$x" />
								 * so we need to convert it again to right code when we show the layout in the source tab.
								 */
								if (char == "?" && tag_code.substr(j, 9) == '?&gt; <?=') { 
									//console.log(html.substr(j - 5, 20));
									
									//get the end position of the php code
									var pos1 = tag_code.indexOf("<", j + 9);
									var pos2 = tag_code.indexOf("/>", j + 9);
									var pos3 = tag_code.indexOf(">", j + 9);
									
									pos = pos1 != -1 && pos2 != -1 && pos1 < pos2 ? pos1 : (pos2 != -1 && pos3 != -1 && pos2 < pos3 ? pos2 : pos3);
									pos = pos != -1 ? pos : tag_code.length;
									
									//construct the messy php code
									char = '<?=' + tag_code.substr(j + 9, pos - j - 9) + '?>';
									j = pos - 1;
									
									//console.log(tag_code.substr(j + 1, 20));
									//console.log(char);
								}
								/* 	
								 * in IE when the html code is:
								 * 	<input <ptl:echo $x>name="xxx"/>
								 * 	<input <?php echo $x ?>name="xxx"/>
								 * the IE and the LayoutUIEditor will convert and show this code like:
								 *	<input name="xxx" $x&gt; <ptl:echo/>
								 * 	<input name="xxx" echo <?php $x?>/>
								 * so we need to convert it again to right code when we show the layout in the source tab.
								 */
								else if (char == "&" && tag_code.substr(j, 9) == '&gt; <ptl') { 
									var start_pos = tag_code.substr(0, j).lastIndexOf("<");
									var end_pos = tag_code.indexOf(">", j + 9);
									var code = tag_code.substr(start_pos, end_pos - start_pos + 1);
									//console.log(code);
									
									//I don't know how to fix this cases ???
									//so I added the ie_special_cases variable to show the alert message bellow
									ie_special_cases.push( code.substr(0, 100) ); //limits the string length to 100, otherwise the alert message will appear too big
								}
							}
							
							new_html += char;
							
							if (char == ">" && !odq && !osq) 
								break;
						}
						
						i = tag_index;
					}
					else
						new_html += char;
				}
				else
					new_html += char;
			}
			
			if (ie_special_cases.length > 0)
				me.showException("[LayoutUIEditor.decodeInnerPTLAndPHPTags]"
					+ "\nThe system detected some incompatibilities between this editor and your IE browser."
					+ "\nBasically there are some html elements (PHP and PTL elements) that were not converted correctly."
					+ "\nPlease use the Firefox or Chrome browser instead, to avoid wrong code conversion like the followings:"
					+ "\n- <input <ptl:echo $x>name='xxx'/> which is converted to: <input name='xxx' $x&gt; <ptl:echo/>"
					+ "\n- <input <?php echo $x ?>name='xxx'/> which is converted to: <input name='xxx' echo <?php $x?>/>"
					+ "\n"
					+ "\nTHERE ARE OTHER CASES TOO, SO PLEASE USE CHROME OR FIREFOX BROWSER!"
					+ "\n"
					+ "\nHere are some of your code samples that were not converted correctly:"
					+ "\n- " + ie_special_cases.join("\n- ")
				);
		}
		//console.log("**************************************");
		//console.log(html);
		//console.log(new_html);
		
		return new_html;
	}
	
	/* MESSAGES METHODS */
	
	function initMessages() {
		messages.removeClass("hidden");
		
		messages.click(function() {
			me.removeMessages();
		});
	}
	
	me.showMessage = function(text, class_name) {
		me.prepareMessage(text, "info" + (class_name ? " " + class_name : ""));
	};
	
	me.showError = function(text, class_name) {
		me.prepareMessage(text, "error" + (class_name ? " " + class_name : ""));
	};
	
	me.showException = function(text) {
		alert(text);
		
		if (console && console.log)
			console.log(new Error(text));
	};
	
	me.prepareMessage = function(text, class_name, timeout) {
		if (text) {
			class_name = class_name.replace(/\s+/g, " ").replace(/^\s+/g, "").replace(/\s+$/g, "");
			timeout = timeout > 0 ? timeout : 5000;
			
			var class_id = class_name.replace(/ /g, "_");
			var class_selector = class_name.replace(/ /g, ".");
			var msg_id = class_id + "_" + (typeof $.md5 == "function" ? $.md5(text) : text.replace("/\s*/g", ""));
			
			//only show if message is not repeated
			if (messages.children("#" + msg_id + ".message").length == 0) {
				var created_time = (new Date()).getTime();
				var last_msg_elm = messages.children().last();
				
				//prepare message element
				if (last_msg_elm.is(".message.message-" + class_selector) && last_msg_elm.data("created_time") + 1500 > created_time) { //if there is already a message created in the previous 1.5seconds, combine this text with that message element.
					var clone = last_msg_elm.clone();
					
					clone.children(".close").remove();
					var previous_text = clone.html();
					var is_different = previous_text.substr(- text.length) != text;
					
					if (is_different) {
						var new_text = previous_text + "<br/>" + text;
						msg_id = class_id + "_" + (typeof $.md5 == "function" ? $.md5(new_text) : new_text.replace("/\s*/g", ""));
						
						last_msg_elm.attr("id", msg_id).children(".close").last().before( "<br/>" + text );
						
						//renew timeout
						var close_icon = last_msg_elm.children(".close");
						var timeout_id = last_msg_elm.data("timeout_id");
						timeout_id && clearTimeout(timeout_id);
						
						timeout_id = setTimeout(function() { 
							close_icon.trigger("click");
						}, timeout);
						last_msg_elm.data("timeout_id", timeout_id);
					}
					
					clone.remove();
				}
				else { //if new message element
					var html = $('<div id="' + msg_id + '" class="message message-' + class_name + '">' + text + '<i class="zmdi zmdi-close close"></i></div>');
					var close_icon = html.children(".close");
					
					var timeout_id = setTimeout(function() {
						close_icon.trigger("click");
					}, timeout);
					html.data("timeout_id", timeout_id);
					
					html.click(function(event) {
						event && typeof event.stopPropagation == "function" && event.stopPropagation(); //avoids to call the onClick event from .messages
					});
					
					close_icon.click(function(event) {
						event && typeof event.stopPropagation == "function" && event.stopPropagation(); //avoids to call the onClick event from .messages
						
						me.removeMessage(this);
						
						if (timeout_id)
							clearTimeout(timeout_id);
					});
					
					html.data("created_time", created_time);
					
					messages.append(html);
				}
			}
		}
	};
	
	me.removeMessage = function(elm) {
		$(elm).parent().remove();
	};
	
	me.removeMessages = function(type) {
		var selector = type ? ".message-" + type : ".message";
		messages.children(selector).remove();
	};
	
	/* POPUP METHODS */
	
	me.initPopup = function(settings) {
		me.popup_settings = $.isPlainObject(settings) ? settings : {};
		me.popup_elm = me.popup_settings["elementToShow"];
		me.popup_elm = me.popup_elm instanceof jQuery ? me.popup_elm : $(me.popup_elm);
		me.popup_obj = null;
		
		if (!me.popup_elm || !me.popup_elm[0])
			return false;
		
		if (!me.popup_elm.hasClass("layout-ui-editor-popup"))
			me.popup_elm.addClass("layout-ui-editor-popup");
		
		if (me.options.popup_class)
			me.popup_elm.addClass(me.options.popup_class);
		
		if (typeof MyFancyPopupClass == "function") {
			me.popup_obj = new MyFancyPopupClass();
			me.popup_obj.init(me.popup_settings);
		}
		else {
			me.popup_elm.addClass("clone");
			
			var close_btn = $('<div class="close popup_close"></div>');
			close_btn.on("click", function(e) {
				me.hidePopup();
			});
			me.popup_elm.prepend(close_btn);
		}
		
		return true;
	};
	
	me.showPopup = function() {
		if (me.popup_obj)
			me.popup_obj.showPopup();
		else if (me.popup_elm) {
			if (typeof me.popup_settings["onOpen"] == "function")
				me.popup_settings["onOpen"]();
			
			me.popup_elm.show();
		}
	};
	
	me.hidePopup = function() {
		if (me.popup_obj)
			me.popup_obj.hidePopup();
		else if (me.popup_elm) {
			if (typeof me.popup_settings["onClose"] == "function")
				me.popup_settings["onClose"]();
				
			me.popup_elm.hide();
		}
	};
	
	me.destroyPopup = function(opts) {
		if (me.popup_elm)
			me.popup_elm.remove();
		
		if (me.popup_obj)
			me.popup_obj.destroyPopup();
		
		me.popup_settings = null;
		me.popup_elm = null;
		me.popup_obj = null;
	};
	
	/* OTHER METHODS - UTILS */
	
	//to be used by the widgets and in the LayoutUIEditorWidgetResource
	me.parseJson = function(str) {
		var obj = null;
		
		try {
			obj = JSON.parse(str);
		}
		catch(e) {
			if (console && console.log)
				console.log(e);
		}
		
		return obj;
	};
	
	//creates editor if there isn't an editor yet created!
	function createCodeEditor(textarea, options) {
		if (ace) {
			var parent = $(textarea).parent();
			var editor = parent.data("editor");
			
			if (!editor) {
				ace.require("ace/ext/language_tools");
				var editor = ace.edit(textarea);
				editor.setTheme("ace/theme/chrome");
				editor.session.setMode("ace/mode/php");
				editor.setAutoScrollEditorIntoView(true);
				editor.setOption("minLines", 5);
				editor.setOptions({
					enableBasicAutocompletion: true,
					enableSnippets: true,
					enableLiveAutocompletion: false,
				});
				editor.setOption("wrap", true);
				
				if (options && typeof options.template_source_editor_save_func == "function") {
					editor.commands.addCommand({
						name: "saveFile",
						bindKey: {
							win: "Ctrl-S",
							mac: "Command-S",
							sender: "editor|cli"
						},
						exec: function(env, args, request) {
							options.template_source_editor_save_func(me);
						},
					});
				}
				
				parent.find("textarea.ace_text-input").removeClass("ace_text-input"); //fixing problem with scroll up, where when focused or pressed key inside editor the page scrolls to top.
				/* If still this scrolling problem persists, please adds the following css:
				.some_class textarea {
					position:fixed !important;
				}*/
				
				parent.data("editor", editor);
			}
				
			return editor;
		}
	}
	
	function getHoveredBrother(event, ui_obj, brothers) {
		var selected = null;
		var two = template_widgets.offset(); //add offset of iframe
		
		$.each(brothers, function(i, item) {
			item = $(item);
				
			var io = item.offset();
			var item_top = io.top + two.top;
			var item_bottom = item_top + item.outerHeight();
			var item_left = io.left + two.left;
			var item_right = item_left + item.outerWidth();
			
			if (ui_obj.offset.top >= item_top && ui_obj.offset.top <= item_bottom && ui_obj.offset.left >= item_left && ui_obj.offset.left <= item_right) {
				selected = item;
				return false;
			}
		});
		
		if (!selected) {
			$.each(brothers, function(i, item) {
				item = $(item);
				
				var io = item.offset();
				var item_top = io.top + two.top;
				var item_bottom = item_top + item.outerHeight();
				var item_left = io.left + two.left;
				var item_right = item_left + item.outerWidth();
				
				if (event.clientY >= item_top && event.clientY <= item_bottom && event.clientX >= item_left && event.clientX <= item_right) {
					selected = item;
					return false;
				}
			});
		}
		
		return selected;
	}
	
	function getElementHeaderPosition(elm) {
		var pos = {};
		
		/*if (elm.css("position") == "fixed") { //only if already exists top or left, which means that item was already moved.
			pos.top = 20;
			pos.left = 0;
		}
		else {*/
			var p = elm[0].getBoundingClientRect ? elm[0].getBoundingClientRect() : elm.position();
			var pos = {"top": p.top, "left": p.left};
			
			//add margin for the iframe. This is very important bc if the TextSelection is not sticky, it will change the margin-top of the template_widgets_iframe, which will influence the widget and droppable headers
			var extra_top = me.TextSelection && me.TextSelection.isMenuShown() ? me.TextSelection.getMenuHeight() : 0;
			pos.top += parseInt(template_widgets_iframe.css("border-top-width")) + parseInt(template_widgets_iframe.css("margin-top")) + extra_top;
			pos.left += parseInt(template_widgets_iframe.css("border-left-width")) + parseInt(template_widgets_iframe.css("margin-left"));
			
			/*var margin_top = parseInt(elm.css("margin-top"));
			var margin_left = parseInt(elm.css("margin-left"));
			
			pos.top += margin_top;
			pos.left += margin_left;
			
			var parents = elm.parentsUntil(template_widgets_droppable);
			
			$.each(parents, function(idx, item) {
				item = $(item);
				var position = item.css("position");
				
				if (position && position != "static") {
					var item_pos = item.position();
					var item_margin_top = parseInt(item.css("margin-top"));
					var item_margin_left = parseInt(item.css("margin-left"));
					
					pos.top += item_pos.top + item_margin_top;
					pos.left += item_pos.left + item_margin_left;
				}
				else if (position == "fixed") {
					pos.top = 20;
					pos.left = 5;
					return false;
				}
			});*/
			
			//small adjustments
			pos.left += 2;
			
			if (elm.is("input") || elm.is("textarea") || elm.is("select"))
				pos.top += 2;
		//}
		
		return pos;
	}
	
	function makeRandomId() {
		var text = "";
		var chars = "abcdefghijklmnopqrstuvwxyz0123456789";

		for (var i = 0; i < 5; i++)
		text += chars.charAt(Math.floor(Math.random() * chars.length));

		return text + "_" + Math.floor(Math.random() * 1000);
	}
	
	//https://stackoverflow.com/questions/3446170/escape-string-for-use-in-javascript-regex
	//https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Regular_Expressions
	function escapeStringForRegExp(str) {
		var regex_specials_chars = [
		   	// order matters for these
			"-",
			"[",
			"]",
			// order doesn't matter for any of these,
			"/",
			"{",
			"}",
			"(",
			")",
			"*",
			"+",
			"?",
			".",
			"\\",
			"^",
			"$",
			"|"
		 ];
		var regex = new RegExp('[' + regex_specials_chars.join('\\') + ']', 'g'); //Escape every character with '\' even though only some strictly require it when inside of []
		
		return str.replace(regex, "\\$&");
	}
	
	//https://stackoverflow.com/questions/3446170/escape-string-for-use-in-javascript-regex
	function escapeStringForReplacement(str) {
		return str.replace(/\$/g, '$$$$');
	}
	
	function color2hex(color) {
		color = color.replace(/^\s+/g, "");
		
		if (color.substr(0, 1) == "#") {
			if (color.length == 4)
				return color.substr(0, 1) + color.substr(1) + color.substr(1);
				
			return color;
		}
		
		color = color.toLowerCase();
		
		var rgb = color.match(/^\s*rgb\((\d+),\s*(\d+),\s*(\d+)\)\s*$/);
		
		if (!rgb)
			rgb = color.match(/^\s*rgba\((\d+),\s*(\d+),\s*(\d+),\s*(\d+)\)\s*$/);
			
		if (rgb)
			return "#" +
			("0" + parseInt( rgb[1], 10).toString(16) ).slice(-2) +
			("0" + parseInt( rgb[2], 10).toString(16) ).slice(-2) +
			("0" + parseInt( rgb[3], 10).toString(16) ).slice(-2);
		
		switch (color) {
			case "aliceblue": return "#f0f8ff";
			case "antiquewhite": return "#faebd7";
			case "aqua": return "#00ffff";
			case "aquamarine": return "#7fffd4";
			case "azure": return "#f0ffff";
			case "beige": return "#f5f5dc";
			case "bisque": return "#ffe4c4";
			case "black": return "#000000";
			case "blanchedalmond": return "#ffebcd";
			case "blue": return "#0000ff";
			case "blueviolet": return "#8a2be2";
			case "brown": return "#a52a2a";
			case "burlywood": return "#deb887";
			case "cadetblue": return "#5f9ea0";
			case "chartreuse": return "#7fff00";
			case "chocolate": return "#d2691e";
			case "coral": return "#ff7f50";
			case "cornflowerblue": return "#6495ed";
			case "cornsilk": return "#fff8dc";
			case "crimson": return "#dc143c";
			case "cyan": return "#00ffff";
			case "darkblue": return "#00008b";
			case "darkcyan": return "#008b8b";
			case "darkgoldenrod": return "#b8860b";
			case "darkgray": return "#a9a9a9";
			case "darkgrey": return "#a9a9a9";
			case "darkgreen": return "#006400";
			case "darkkhaki": return "#bdb76b";
			case "darkmagenta": return "#8b008b";
			case "darkolivegreen": return "#556b2f";
			case "darkorange": return "#ff8c00";
			case "darkorchid": return "#9932cc";
			case "darkred": return "#8b0000";
			case "darksalmon": return "#e9967a";
			case "darkseagreen": return "#8fbc8f";
			case "darkslateblue": return "#483d8b";
			case "darkslategray": return "#2f4f4f";
			case "darkslategrey": return "#2f4f4f";
			case "darkturquoise": return "#00ced1";
			case "darkviolet": return "#9400d3";
			case "deeppink": return "#ff1493";
			case "deepskyblue": return "#00bfff";
			case "dimgray": return "#696969";
			case "dimgrey": return "#696969";
			case "dodgerblue": return "#1e90ff";
			case "firebrick": return "#b22222";
			case "floralwhite": return "#fffaf0";
			case "forestgreen": return "#228b22";
			case "fuchsia": return "#ff00ff";
			case "gainsboro": return "#dcdcdc";
			case "ghostwhite": return "#f8f8ff";
			case "gold": return "#ffd700";
			case "goldenrod": return "#daa520";
			case "gray": return "#808080";
			case "grey": return "#808080";
			case "green": return "#008000";
			case "greenyellow": return "#adff2f";
			case "honeydew": return "#f0fff0";
			case "hotpink": return "#ff69b4";
			case "indianred ": return "#cd5c5c";
			case "indigo ": return "#4b0082";
			case "ivory": return "#fffff0";
			case "khaki": return "#f0e68c";
			case "lavender": return "#e6e6fa";
			case "lavenderblush": return "#fff0f5";
			case "lawngreen": return "#7cfc00";
			case "lemonchiffon": return "#fffacd";
			case "lightblue": return "#add8e6";
			case "lightcoral": return "#f08080";
			case "lightcyan": return "#e0ffff";
			case "lightgoldenrodyellow": return "#fafad2";
			case "lightgray": return "#d3d3d3";
			case "lightgrey": return "#d3d3d3";
			case "lightgreen": return "#90ee90";
			case "lightpink": return "#ffb6c1";
			case "lightsalmon": return "#ffa07a";
			case "lightseagreen": return "#20b2aa";
			case "lightskyblue": return "#87cefa";
			case "lightslategray": return "#778899";
			case "lightslategrey": return "#778899";
			case "lightsteelblue": return "#b0c4de";
			case "lightyellow": return "#ffffe0";
			case "lime": return "#00ff00";
			case "limegreen": return "#32cd32";
			case "linen": return "#faf0e6";
			case "magenta": return "#ff00ff";
			case "maroon": return "#800000";
			case "mediumaquamarine": return "#66cdaa";
			case "mediumblue": return "#0000cd";
			case "mediumorchid": return "#ba55d3";
			case "mediumpurple": return "#9370db";
			case "mediumseagreen": return "#3cb371";
			case "mediumslateblue": return "#7b68ee";
			case "mediumspringgreen": return "#00fa9a";
			case "mediumturquoise": return "#48d1cc";
			case "mediumvioletred": return "#c71585";
			case "midnightblue": return "#191970";
			case "mintcream": return "#f5fffa";
			case "mistyrose": return "#ffe4e1";
			case "moccasin": return "#ffe4b5";
			case "navajowhite": return "#ffdead";
			case "navy": return "#000080";
			case "oldlace": return "#fdf5e6";
			case "olive": return "#808000";
			case "olivedrab": return "#6b8e23";
			case "orange": return "#ffa500";
			case "orangered": return "#ff4500";
			case "orchid": return "#da70d6";
			case "palegoldenrod": return "#eee8aa";
			case "palegreen": return "#98fb98";
			case "paleturquoise": return "#afeeee";
			case "palevioletred": return "#db7093";
			case "papayawhip": return "#ffefd5";
			case "peachpuff": return "#ffdab9";
			case "peru": return "#cd853f";
			case "pink": return "#ffc0cb";
			case "plum": return "#dda0dd";
			case "powderblue": return "#b0e0e6";
			case "purple": return "#800080";
			case "rebeccapurple": return "#663399";
			case "red": return "#ff0000";
			case "rosybrown": return "#bc8f8f";
			case "royalblue": return "#4169e1";
			case "saddlebrown": return "#8b4513";
			case "salmon": return "#fa8072";
			case "sandybrown": return "#f4a460";
			case "seagreen": return "#2e8b57";
			case "seashell": return "#fff5ee";
			case "sienna": return "#a0522d";
			case "silver": return "#c0c0c0";
			case "skyblue": return "#87ceeb";
			case "slateblue": return "#6a5acd";
			case "slategray": return "#708090";
			case "slategrey": return "#708090";
			case "snow": return "#fffafa";
			case "springgreen": return "#00ff7f";
			case "steelblue": return "#4682b4";
			case "tan": return "#d2b48c";
			case "teal": return "#008080";
			case "thistle": return "#d8bfd8";
			case "tomato": return "#ff6347";
			case "turquoise": return "#40e0d0";
			case "violet": return "#ee82ee";
			case "wheat": return "#f5deb3";
			case "white": return "#ffffff";
			case "whitesmoke": return "#f5f5f5";
			case "yellow": return "#ffff00";
			case "yellowgreen": return "#9acd32";
		}
		
		return color;
	}
	
	function isBlockDisplayHtmlTag(tag) {
		switch(tag.toLowerCase()) {
			case "div":
			case "h1":
			case "h2":
			case "h3":
			case "h4":
			case "h5":
			case "h6":
			case "p":
			case "section":
			case "article":
			case "header":
			case "footer":
			case "blockquote":
			case "address":
			case "dl":
			case "dt":
			case "dd":
			case "ol":
			case "ul":
			case "li":
			case "output":
			case "video":
			case "aside":
			case "fieldset":
			case "hr":
			case "figcaption":
			case "figure":
			case "pre":
			case "canvas":
			case "main":
			case "nav":
			case "table":
			case "form":
			case "noscript":
			case "tfoot":
				return true;
		}
		
		return false;
	}
	
	function getAvailableCSSPropertiesName() {
		var names = ["accent-color", "align-content", "align-items", "align-self", "all", "animation", "animation-delay", "animation-direction", "animation-duration", "animation-fill-mode", "animation-iteration-count", "animation-name", "animation-play-state", "animation-timing-function", "aspect-ratio", "backdrop-filter", "backface-visibility", "background", "background-attachment", "background-blend-mode", "background-clip", "background-color", "background-image", "background-origin", "background-position", "background-position-x", "background-position-y", "background-repeat", "background-size", "block-size", "border", "border-block", "border-block-color", "border-block-end", "border-block-end-color", "border-block-end-style", "border-block-end-width", "border-block-start", "border-block-start-color", "border-block-start-style", "border-block-start-width", "border-block-style", "border-block-width", "border-bottom", "border-bottom-color", "border-bottom-left-radius", "border-bottom-right-radius", "border-bottom-style", "border-bottom-width", "border-collapse", "border-color", "border-end-end-radius", "border-end-start-radius", "border-image", "border-image-outset", "border-image-repeat", "border-image-slice", "border-image-source", "border-image-width", "border-inline", "border-inline-color", "border-inline-end", "border-inline-end-color", "border-inline-end-style", "border-inline-end-width", "border-inline-start", "border-inline-start-color", "border-inline-start-style", "border-inline-start-width", "border-inline-style", "border-inline-width", "border-left", "border-left-color", "border-left-style", "border-left-width", "border-radius", "border-right", "border-right-color", "border-right-style", "border-right-width", "border-spacing", "border-start-end-radius", "border-start-start-radius", "border-style", "border-top", "border-top-color", "border-top-left-radius", "border-top-right-radius", "border-top-style", "border-top-width", "border-width", "bottom", "box-decoration-break", "box-reflect", "box-shadow", "box-sizing", "break-after", "break-before", "break-inside", "caption-side", "caret-color", "@charset", "clear", "clip", "clip-path", "color", "column-count", "column-fill", "column-gap", "column-rule", "column-rule-color", "column-rule-style", "column-rule-width", "column-span", "column-width", "columns", "content", "counter-increment", "counter-reset", "counter-set", "cursor", "direction", "display", "empty-cells", "filter", "flex", "flex-basis", "flex-direction", "flex-flow", "flex-grow", "flex-shrink", "flex-wrap", "float", "font", "@font-face", "font-family", "font-feature-settings", "@font-feature-values", "font-kerning", "font-language-override", "font-size", "font-size-adjust", "font-stretch", "font-style", "font-synthesis", "font-variant", "font-variant-alternates", "font-variant-caps", "font-variant-east-asian", "font-variant-ligatures", "font-variant-numeric", "font-variant-position", "font-weight", "gap", "grid", "grid-area", "grid-auto-columns", "grid-auto-flow", "grid-auto-rows", "grid-column", "grid-column-end", "grid-column-gap", "grid-column-start", "grid-gap", "grid-row", "grid-row-end", "grid-row-gap", "grid-row-start", "grid-template", "grid-template-areas", "grid-template-columns", "grid-template-rows", "hanging-punctuation", "height", "hyphens", "hypenate-character", "image-rendering", "@import", "inline-size", "inset", "inset-block", "inset-block-end", "inset-block-start", "inset-inline", "inset-inline-end", "inset-inline-start", "isolation", "justify-content", "justify-items", "justify-self", "@keyframes", "left", "letter-spacing", "line-break", "line-height", "list-style", "list-style-image", "list-style-position", "list-style-type", "margin", "margin-block", "margin-block-end", "margin-block-start", "margin-bottom", "margin-inline", "margin-inline-end", "margin-inline-start", "margin-left", "margin-right", "margin-top", "mask", "mask-clip", "mask-composite", "mask-image", "mask-mode", "mask-origin", "mask-position", "mask-repeat", "mask-size", "mask-type", "max-height", "max-width", "@media", "max-block-size", "max-inline-size", "min-block-size", "min-inline-size", "min-height", "min-width", "mix-blend-mode", "object-fit", "object-position", "offset", "offset-anchor", "offset-distance", "offset-path", "offset-rotate", "opacity", "order", "orphans", "outline", "outline-color", "outline-offset", "outline-style", "outline-width", "overflow", "overflow-anchor", "overflow-wrap", "overflow-x", "overflow-y", "overscroll-behavior", "overscroll-behavior-block", "overscroll-behavior-inline", "overscroll-behavior-x", "overscroll-behavior-y", "padding", "padding-block", "padding-block-end", "padding-block-start", "padding-bottom", "padding-inline", "padding-inline-end", "padding-inline-start", "padding-left", "padding-right", "padding-top", "page-break-after", "page-break-before", "page-break-inside", "paint-order", "perspective", "perspective-origin", "place-content", "place-items", "place-self", "pointer-events", "position", "quotes", "resize", "right", "rotate", "row-gap", "scale", "scroll-behavior", "scroll-margin", "scroll-margin-block", "scroll-margin-block-end", "scroll-margin-block-start", "scroll-margin-bottom", "scroll-margin-inline", "scroll-margin-inline-end", "scroll-margin-inline-start", "scroll-margin-left", "scroll-margin-right", "scroll-margin-top", "scroll-padding", "scroll-padding-block", "scroll-padding-block-end", "scroll-padding-block-start", "scroll-padding-bottom", "scroll-padding-inline", "scroll-padding-inline-end", "scroll-padding-inline-start", "scroll-padding-left", "scroll-padding-right", "scroll-padding-top", "scroll-snap-align", "scroll-snap-stop", "scroll-snap-type", "scrollbar-color", "tab-size", "table-layout", "text-align", "text-align-last", "text-combine-upright", "text-decoration", "text-decoration-color", "text-decoration-line", "text-decoration-style", "text-decoration-thickness", "text-emphasis", "text-emphasis-color", "text-emphasis-position", "text-emphasis-style", "text-indent", "text-justify", "text-orientation", "text-overflow", "text-shadow", "text-transform", "text-underline-offset", "text-underline-position", "top", "transform", "transform-origin", "transform-style", "transition", "transition-delay", "transition-duration", "transition-property", "transition-timing-function", "translate", "unicode-bidi", "user-select", "vertical-align", "visibility", "white-space", "widows", "width", "word-break", "word-spacing", "word-wrap", "writing-mode", "z-index"];
		var prefixes = ["-webkit-", "-moz-", "-ms-", "-o-"];
		var css_properties = [];
		var t2 = prefixes.length;
		
		for (var i = 0, t1 = names.length; i < t1; i++) {
			var name = names[i];
			
			css_properties.push(name);
			
			for (var j = 0; j < t2; j++)
				css_properties.push(prefixes[j] + name);
		}
		
		return css_properties;
	}
}
