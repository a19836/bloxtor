# JQuery Layout UI Editor

> Original Repos:   
> - JQuery Layout UI Editor: https://github.com/a19836/jquerylayoutuieditor/   
> - Bloxtor: https://github.com/a19836/bloxtor/

## Overview

**JQuery Layout UI Editor** is a powerful WYSIWYG (What-You-See-Is-What-You-Get) layout builder designed to convert frontend and backend code — including **HTML**, **CSS**, **JavaScript**, and **PHP** code - into reusable, visual, draggable and configurable UI components.  
With an intuitive **drag & drop editor**, users can visually design interfaces and responsive layouts without manually writing code, while still having the ability to view, edit, or export the underlying source.
This library is composed of multiple widgets that allow you to draw any type of layout, but you can also **extend our widget library by creating your own components**, making the editor highly flexible and scalable.
It is ideal for developers, designers, and tools that need dynamic UI assembly from existing code.

> **Optional integrations**  
> This editor also works with the following complementary libraries:
> - **[MyJSLib](https://bloxtor.com/onlineitframeworktutorial/?block_id=documentation/layout_ui_editor/myjslib_with_form_fields_validation)**: provides automatic form validation.  
> - **[MyWidgetResourceLib](https://bloxtor.com/onlineitframeworktutorial/?block_id=documentation/layout_ui_editor/widget_resource_lib)**: converts HTML elements into dynamic interactive nodes and enables server-side action execution. Here is also more [details](./example_of_widget_resource.md)  

Requirements:
- jquery
- jqueryui
- materialdesigniconicfont
- phpjs/parse_str
- jquerymycontextmenu
- jqueryuidroppableiframe

---

## Features

- Automatically converts **code blocks into visual components**
- Includes a library of reusable **UI widgets**
- Fully **extendable widget architecture** — create your own components
- Drag-and-drop layout building
- Responsive structure support
- Visual inspector with editable properties
- Export full layout (HTML / CSS / JS / PHP)
- Customizable styling and behavior rules
- Ideal for UI builders, CMS tools, and prototyping platforms

---

## Usage

```php
<?php
$project_protocol = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? "https://" : "http://";
$project_link = $project_protocol . $_SERVER["HTTP_HOST"] . (isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : "");
$parts = explode("/__system/", $project_link);
$phpframework_link = $parts[0] . "/__system/";
$common_link = $parts[0] . "/__system/common/";
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	
	<!-- (Optional) Color -->
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>js/color.js"></script>
	
	<!-- JQuery -->
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>vendor/jquery/js/jquery-1.8.1.min.js"></script>
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>vendor/jqueryui/js/jquery-ui-1.11.4.min.js"></script>
	<link rel="stylesheet" href="<?= $common_link; ?>vendor/jqueryui/css/jquery-ui-1.11.4.css">
	
	<!-- To work on mobile devices with touch -->
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>vendor/jqueryuitouchpunch/jquery.ui.touch-punch.min.js"></script>
	
	<!-- Jquery Tap-Hold Event JS file -->
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>vendor/jquerytaphold/taphold.js"></script>

	<!-- Material-design-iconic-font -->
	<link rel="stylesheet" href="lib/materialdesigniconicfont/css/material-design-iconic-font.min.css">
	
	<!-- Parse_Str -->
	<script type="text/javascript" src="<?= $common_link; ?>vendor/phpjs/functions/strings/parse_str.js"></script>
	
	<!-- MD5 -->
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>vendor/jquery/js/jquery.md5.js"></script>
	
	<!-- JQuery Nestable2 -->
	<link rel="stylesheet" href="lib/nestable2/jquery.nestable.min.css" type="text/css" charset="utf-8" />
	<script language="javascript" type="text/javascript" src="lib/nestable2/jquery.nestable.min.js"></script>
	
	<!-- Add Code Editor JS files -->
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>vendor/acecodeeditor/src-min-noconflict/ace.js"></script>
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>vendor/acecodeeditor/src-min-noconflict/ext-language_tools.js"></script>
	
	<!-- Add Code Beautifier -->
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>vendor/mycodebeautifier/js/MyCodeBeautifier.js"></script>

	<!-- Add Html/CSS/JS Beautify code -->
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>vendor/jsbeautify/js/lib/beautify.js"></script>
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>vendor/jsbeautify/js/lib/beautify-css.js"></script>
	<script language="javascript" type="text/javascript" src="<?= $phpframework_link; ?>lib/myhtmlbeautify/MyHtmlBeautify.js"></script>
	
	<!-- Add Auto complete -->
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>vendor/myautocomplete/js/MyAutoComplete.js"></script>
	<link rel="stylesheet" href="<?= $common_link; ?>vendor/myautocomplete/css/style.css">
	
	<!-- CONTEXT MENU -->
	<link rel="stylesheet" href="<?= $common_link; ?>vendor/jquerymycontextmenu/css/style.css" type="text/css" charset="utf-8" />
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>vendor/jquerymycontextmenu/js/jquery.mycontextmenu.js"></script>
    	
	<!-- Layout UI Editor -->
		<!-- Layout UI Editor - HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
		<!--[if lt IE 9]>
			 <script src="lib/jqueryuidroppableiframe/js/html5_ie8/html5shiv.min.js"></script>
			 <script src="lib/jqueryuidroppableiframe/js/html5_ie8/respond.min.js"></script>
		<![endif]-->
		
		<!-- Layout UI Editor - Add Iframe droppable fix -->
    	<script type="text/javascript" src="lib/jqueryuidroppableiframe/js/jquery-ui-droppable-iframe-fix.js"></script>    
    
    	<!-- Layout UI Editor - Add Iframe droppable fix - IE10 viewport hack for Surface/desktop Windows 8 bug -->
    	<script src="lib/jqueryuidroppableiframe/js/ie10-viewport-bug-workaround.js"></script>
	    	
		<!-- Layout UI Editor - Add Editor -->
		<link rel="stylesheet" href="css/some_bootstrap_style.css" type="text/css" charset="utf-8" />
		<link rel="stylesheet" href="css/style.css" type="text/css" charset="utf-8" />
		<link rel="stylesheet" href="css/widget_resource.css" type="text/css" charset="utf-8" />
		
		<script language="javascript" type="text/javascript" src="js/TextSelection.js"></script>
		<script language="javascript" type="text/javascript" src="js/LayoutUIEditor.js"></script>
		<script language="javascript" type="text/javascript" src="js/CreateWidgetContainerClassObj.js"></script>
		
		<!-- (Optional) Layout UI Editor - LayoutUIEditorFormField.js is optional, bc it depends of task/programming/common/webroot/js/FormFieldsUtilObj.js -->
		<!-- But only exists if phpframework cache was not deleted -->
		<script language="javascript" type="text/javascript" src="http://jplpinto.localhost/__system/phpframework/__system/cache/workflow/tasks/default/programming/common/js/global.js"></script>
		<script language="javascript" type="text/javascript" src="http://jplpinto.localhost/__system/phpframework/__system/cache/workflow/tasks/default/programming/common/js/FormFieldsUtilObj.js"></script>
		<script language="javascript" type="text/javascript" src="js/LayoutUIEditorFormField.js"></script>
		
		<!-- (Optional) Layout UI Editor - Add Widget Resources -->
		<script language="javascript" type="text/javascript" src="js/LayoutUIEditorWidgetResource.js"></script>
	
	<!-- (Optional) Add Fontawsome Icons CSS -->
	<link rel="stylesheet" href="<?= $common_link; ?>vendor/fontawesome/css/all.min.css">

	<!-- (Optional) Add Fancy LighBox lib -->
	<link rel="stylesheet" href="<?= $common_link; ?>vendor/jquerymyfancylightbox/css/style.css" type="text/css" charset="utf-8" media="screen, projection" />
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>vendor/jquerymyfancylightbox/js/jquery.myfancybox.js"></script>

	<!-- (Optional) Add DropDowns main JS and CSS files -->
	<link rel="stylesheet" href="<?= $common_link; ?>vendor/jquerysimpledropdowns/css/style.css" type="text/css" charset="utf-8" />
	<!--[if lte IE 7]>
		     <link rel="stylesheet" href="<?= $common_link; ?>vendor/jquerysimpledropdowns/css/ie.css" type="text/css" charset="utf-8" />
	<![endif]-->
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>vendor/jquerysimpledropdowns/js/jquery.dropdownPlain.js"></script>
		
	<!-- (Optional) Others -->
	<link rel="stylesheet" href="examples/css/beauty.css" type="text/css" charset="utf-8" />
	<script language="javascript" type="text/javascript" src="js/script.js"></script>
	
	<script>
	var MyLayoutUIEditor = new LayoutUIEditor();
	
	$(function() {
		MyLayoutUIEditor.options.ui_selector = ".layout-ui-editor";
		MyLayoutUIEditor.options.on_ready_func = function() {
			//show view layout panel instead of code
			var luie = MyLayoutUIEditor.getUI();
			var view_layout = luie.find(" > .tabs > .view-layout");
			view_layout.addClass("do-not-confirm");
			view_layout.trigger("click");
			view_layout.removeClass("do-not-confirm");
		};
		MyLayoutUIEditor.init("MyLayoutUIEditor");
	});
	</script>
</head>
<body>
	<div class="layout-ui-editor reverse fixed-side-properties layout-ui-editor-beauty">
		<!-- MENU WIDGETS -->
		<ul class="menu-widgets hidden">
			
			<!-- WIDGETS GROUP -->
			<li class="group group-generic group-open">
				<div class="group-title"><i class="zmdi zmdi-caret-down toggle"></i>Generic</div>
				<ul>
					
					<!-- WIDGET LINK NODE -->
					<li class="draggable menu-widget menu-widget-href " data-tag="href" title="Link" data-create-widget-class="HrefWidget" data-resizable="1" data-absolute-position="1">
						<!-- MENU WIDGET -->
						<span>Link</span>
						
						<!-- WIDGET TO BE CREATED IN THE TEMPLATE/CANVAS -->
						<a class="template-widget template-widget-href droppable"></a>
						
						<!-- WIDGET PROPERTIES -->
						<div class="properties">
							<div class="form-group row settings-property href">
								<label class="col-md-4 col-sm-5 col-form-label">Url: </label>
								<input class="col-md-8 col-sm-7 form-control" name="href" />
							</div>
						</div>
						
						<!-- WIDGET STYLING -->
						<style>
							.layout-ui-editor > .menu-widgets .menu-widget.menu-widget-href:before,
							  body > .menu-widget.menu-widget-href.ui-draggable-dragging:before {
								background-image:url('widget/generic/href/logo.svg');
							}
							.layout-ui-editor > .template-widgets .widget-header.widget-header-href {
								border-radius:0 5px 5px 0;
							}
							.layout-ui-editor > .template-widgets .widget-header.widget-header-href .options .option.toggle {
								display:none;
							}
						</style>
						
						<!-- WIDGET CLASS AND HANDLERS -->
						<script>
						function HrefWidget(ui_creator, menu_widget) {
							var me = this;
							
							me.init = function() {
								menu_widget.attr({
									"data-on-parse-template-widget-html-func": ui_creator.obj_var_name + ".menu_widgets_objs.href.parseHtml",
									"data-on-create-template-widget-func": ui_creator.obj_var_name + ".menu_widgets_objs.href.onCreateTemplateWidget",
								});
								
								menu_widget.children(".properties").attr({
									"data-on-after-save-settings-field-func": ui_creator.obj_var_name + ".menu_widgets_objs.href.saveSettingsField",
									"data-on-before-parse-widget-settings-func": ui_creator.obj_var_name + ".menu_widgets_objs.href.parseSettings",
								});
							};
							
							me.parseHtml = function(html_element) {
								if (html_element && html_element.nodeName.toLowerCase() == "a")
									return {
										droppable: $(html_element),
									};
							};
							
							me.parseSettings = function(widget, widget_settings) {
								widget_settings["href"] = widget.attr("href");
							};
							
							me.onCreateTemplateWidget = function(widget, html_element) {
								if (!html_element) {
									//check if exists first bc when we change the node names of the widgets.
									
									if (!widget[0].hasAttribute("href"))
										widget.attr("href", "#");
									
									if (widget.contents().length == 0) //very important, otherwise everytime we change a div to a link, it will add this text.
										widget.append("link text");
								}
							};
							
							me.saveSettingsField = function(field, widget, status) {
								if (status) {
									field = $(field);
									var field_value = field.val();
									var sprop = field.parent();
									
									if (sprop.hasClass("href")) {
										if (field_value != "")
											widget.attr("href", field_value);
										else 
											widget.removeAttr("href");
									}
								}
								
								return status;
							};
						}
						</script>
					</li>
					
					<!-- WIDGET IMAGE NODE -->
					<li class="draggable menu-widget menu-widget-image " data-tag="image" title="Image" data-create-widget-class="ImageWidget" data-resizable="1" data-absolute-position="1">
						<!-- MENU WIDGET -->
						<span>Image</span>
						
						<!-- WIDGET TO BE CREATED IN THE TEMPLATE/CANVAS -->
						<img class="template-widget template-widget-image "></img>
						
						<!-- WIDGET PROPERTIES -->
						<div class="properties">
							<div class="form-group row settings-property src">
								<label class="col-md-4 col-sm-5 col-form-label">Source: </label>
								<input class="col-md-8 col-sm-7 form-control" name="src" />
							</div>
						</div>
						
						<!-- WIDGET STYLING -->
						<style>
							.layout-ui-editor > .menu-widgets .menu-widget.menu-widget-image:before,
							  body > .menu-widget.menu-widget-image.ui-draggable-dragging:before {
								background-image:url('widget/generic/image/logo.svg');
							}
							.layout-ui-editor > .template-widgets .widget-header.widget-header-image {
								border-radius:0 5px 5px 0;
							}
							.layout-ui-editor > .template-widgets .widget-header.widget-header-image .options .option.toggle {
								display:none;
							}
						</style>
						
						<!-- WIDGET CLASS AND HANDLERS -->
						<script>
						function ImageWidget(ui_creator, menu_widget) {
							var me = this;
							var default_src = ui_creator.getLayoutUIEditorFolderPath() + "widget/generic/image/default_image.gif";
							
							me.init = function() {
								menu_widget.attr({
									"data-on-parse-template-widget-html-func": ui_creator.obj_var_name + ".menu_widgets_objs.image.parseHtml",
									"data-on-create-template-widget-func": ui_creator.obj_var_name + ".menu_widgets_objs.image.onCreateTemplateWidget"
								});
								
								menu_widget.children(".properties").attr({
									"data-on-before-parse-widget-settings-func": ui_creator.obj_var_name + ".menu_widgets_objs.image.parseSettings",
									"data-on-after-save-settings-field-func": ui_creator.obj_var_name + ".menu_widgets_objs.image.saveSettingsField"
								});
							};
							
							me.parseHtml = function(html_element) {
								return html_element && html_element.nodeName.toLowerCase() == "img";
							};
							
							me.onCreateTemplateWidget = function(widget, html_element) {
								if (!html_element) 
									widget.attr("src", default_src);
							};
							
							me.parseSettings = function(widget, widget_settings) {
								widget_settings["src"] = widget.attr("src");
							};
							
							me.saveSettingsField = function(field, widget, status) {
								if (status) {
									field = $(field);
									var field_value = field.val();
									var sprop = field.parent();
									
									if (sprop.hasClass("src")) {
										field_value = field_value.replace(/^\s+/g, "").replace(/\s+$/g, "");//trim url
										
										if (field_value != "")
											widget.attr("src", field_value);
										else {
											ui_creator.showError("Image src cannot be empty!");
											field.val( widget.attr("src") );
										}
									}
								}
								
								return status;
							};
						}
						</script>
					</li>
				</ul>
			</li>
		</ul>
	</div>
</body>
</html>
```

## How to create a new widget

You only need to create a `settings.xml` file inside of the `widgets` folder, with the following structure:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<widget>
	<label>Widget Label</label>
	<tag>widget_unique_tag</tag>
	<files>
		<css>css/relative/file/path.css</css>
		<js>js/relative/file/path.js</js>
	</files>
	<settings>
		<menu_class>class_foo</menu_class>
		<template_class>class_bar</template_class>
		<template_node_name>a</template_node_name>
		<template_header_class>class_t</template_header_class>
		<menu_settings_class>class_x</menu_settings_class>
		<menu_layer_class>class_w</menu_layer_class>
		
		<resizable>1 or 0</resizable>
		
		<on_open_widget_header_func>someJSFunctionName</on_open_widget_header_func>
		<on_close_widget_header_func>someJSFunctionName</on_close_widget_header_func>
		<on_open_droppable_header_func>someJSFunctionName</on_open_droppable_header_func>
		<on_close_droppable_header_func>someJSFunctionName</on_close_droppable_header_func>
		
		<create_widget_class>mainJSFunctionNameOfThisWidgetClassIfExists</create_widget_class>
		<create_widget_func>mainJSFunctionNameOfThisWidgetIfExists</create_widget_func>
		
		<on_drag_start_func>someJSFunctionName</on_drag_start_func>
		<on_drag_helper_func>someJSFunctionName</on_drag_helper_func>
		<on_drag_stop_func>someJSFunctionName</on_drag_stop_func>
		<on_parse_template_widget_html_func>someJSFunctionName</on_parse_template_widget_html_func>
		<on_clean_template_widget_html_func>someJSFunctionName</on_clean_template_widget_html_func>
		<on_clone_menu_widget_func>someJSFunctionName</on_clone_menu_widget_func>
		<on_create_template_widget_func>someJSFunctionName</on_create_template_widget_func>
		<on_open_settings_func>someJSFunctionName</on_open_settings_func>
		<on_close_settings_func>someJSFunctionName</on_close_settings_func>
		<on_resize_settings_func>someJSFunctionName</on_resize_settings_func>
		<on_before_parse_widget_settings_func>someJSFunctionName</on_before_parse_widget_settings_func>
		<on_after_parse_widget_settings_func>someJSFunctionName</on_after_parse_widget_settings_func>
		<on_before_save_settings_field_func>someJSFunctionName</on_before_save_settings_field_func>
		<on_after_save_settings_field_func>someJSFunctionName</on_after_save_settings_field_func>
	</settings>
	<menu_widget><![CDATA[... some html here ...]]></menu_widget>
	<template_widget><![CDATA[... some html here ...]]></template_widget>
	<properties><![CDATA[... some html here ...]]></properties>
	<menu_css><![CDATA[... some css here ...]]></menu_css>
	<template_css><![CDATA[... some css here ...]]></template_css>
	<menu_js><![CDATA[... some js here ...]]></menu_js>
	<template_js><![CDATA[... some js here ...]]></template_js>
</widget>
```

The `scanWidgets` and `getDefaultMenuWidgetsHTML` functions of the `utils.php` file, will then convert this `settings.xml` into html, and return this html so you can copy it into the `.menu-widgets` html node.   
Also all the sub-folders inside of the `widget` folder, will be converted into widgets groups.   
Here is an example:
```php
<!DOCTYPE html>
<html>
<head>
	<!-- Some header then above -->
</head>
<body>
	<div class="layout-ui-editor layout-ui-editor-beauty">
		<ul class="menu-widgets hidden">
			<?php
				include __DIR__ . "/util.php";
				echo getDefaultMenuWidgetsHTML("widget/");
			?>
		</ul>
	</div>
</body>
</html>
```
 
> **To analyse the existing widgets, please open the `settings.xml` files inside of the `widgets` folder.**

## Other calls from LayoutUIEditor

Get editor obj:
```
var MyLayoutUIEditor = $(".layout-ui-editor").data("LayoutUIEditor");
```

Set default html:
```
var html = 'WRITE YOUR HTML HERE';
html = MyHtmlBeautify.beautify(html);
MyLayoutUIEditor.getUI().find('.options .option.show-full-source').trigger("click");
MyLayoutUIEditor.setTemplateFullSourceEditorValue(html);
```

Set some editor options:
```
MyLayoutUIEditor.options = {
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
	on_template_source_editor_save_func: null,
	on_ready_func: null, //on ready function
	on_template_source_editor_ready_func: null,
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
	on_context_menu_widget_setting: null,
	on_parse_template_widget_html_func: null,
	on_clean_template_widget_html_func: null,
	
	//specific widgets handlers
	on_convert_project_url_php_vars_to_real_values_func: null,
	on_convert_project_url_real_values_to_php_vars_func: null,
};

//if TextSelection exists
MyLayoutUIEditor.TextSelection.options = {
	top: 0,
	left: 0,
	width: null,
	height: null,
	sticky_menu: false,
	
	on_before_show_menu: null,
	on_after_show_menu: null,
	on_before_hide_menu: null,
	on_after_hide_menu: null,
	on_before_click_menu_item: null,
	on_after_click_menu_item: null,
	on_create_node: null,
	on_create_node_attributes: null,
	on_parse_node_attributes: null,
	on_paste_widget: null,
};

//if LayoutUIEditorWidgetResource exists
MyLayoutUIEditor.LayoutUIEditorWidgetResource.options = {
	toggle_choose_db_table_attribute_popup_func: null, //popup to choose a db attribute.
	toggle_choose_widget_resource_popup_func: null, //popup to choose a resource.
	toggle_choose_widget_resource_value_attribute_popup_func: null, //popup to choose a resource attribute. This element will be appended to the clicked element. Internal use only. Besides the attribute in this popup, the user should choose the action type and then the parameters according with that action. Example: If he chooses the action "GET ITEM" then he will need to fill the values for the correspondent PK. If he chooses the action "GET ITEMS", he will need to fill the row index, etc...
	on_choose_event_func: null, //popup to choose an event.
	
	get_db_brokers_func: null, //sync func: returns array with available db brokers
	get_db_drivers_func: null, //sync func: returns array with available db drivers for a broker
	get_db_tables_func: null, //sync func: returns array with available tables for a driver
	get_db_attributes_func: null, //sync func: returns an obj with attributes for a table. Each key is the attribute name and the value is the atribute properties
	get_resources_references_func: null, //sync func: returns array with available resources reference
	get_user_types_func: null, //sync func: returns array with available user types
	get_php_numeric_types_func: null,//sync func: returns array with numeric types
	get_db_numeric_types_func: null,//sync func: returns array with numeric types
	get_db_blob_types_func: null,//sync func: returns array with binary types
	get_internal_attribute_names_func: null,//sync func: returns array with internal attribute names
	
	add_sla_resource_func: null, //async func: add to the SLA panel (if exists), an external resource with a specific resource naMyLayoutUIEditor.
	remove_sla_resource_func: null, //async func: remove the external resource from the SLA panel (if exists), based in the resource naMyLayoutUIEditor.
	create_resource_names_func: null, //sync func: return an array with possible names of a resource based in the parameters
	
	user_module_installed: true,
};
```

Get some editor inner objects:
```
var luie = MyLayoutUIEditor.getUI();
var menu_widgets = MyLayoutUIEditor.getMenuWidgets();
var menu_widget = MyLayoutUIEditor.getMenuWidgetObjByTag(widget_tag)
var menu_layers = MyLayoutUIEditor.getMenuLayers();
var menu_settings = MyLayoutUIEditor.getMenuSettings();
var template_source = MyLayoutUIEditor.getTemplateSource();
var template_full_source = MyLayoutUIEditor.getTemplateFullSource();
var template_preview = MyLayoutUIEditor.getTemplatePreview();
var template_widgets = MyLayoutUIEditor.getTemplateWidgets();
var template_widgets_iframe = MyLayoutUIEditor.getTemplateWidgetsIframe();
var template_widgets_iframe_body = MyLayoutUIEditor.getTemplateWidgetsIframeBody();
var template_widgets_droppable = MyLayoutUIEditor.getTemplateWidgetsDroppable();
var selected_template_widgets_droppable = MyLayoutUIEditor.getSelectedTemplateWidgetsDroppable();
var template_widgets_options = MyLayoutUIEditor.getTemplateWidgetsOptions();
var options = luie.children(".options");
var messages = MyLayoutUIEditor.getMessages();
var widget_header = MyLayoutUIEditor.getWidgetHeader();
var droppable_header = MyLayoutUIEditor.getDroppableHeader();
```

Close Widget settings
```
if (MyLayoutUIEditor.isMenuSettingsOpened())
	MyLayoutUIEditor.getMenuSettings().find(" > .settings-info > .close").trigger("click");
```

Show widget settings as fixed panel:
```
MyLayoutUIEditor.showFixedMenuSettings(true);
```

MyLayoutUIEditor.TextSelection.refreshMenu();
MyLayoutUIEditor.showTemplateWidgetsDroppableBackground();
MyLayoutUIEditor.showTemplateWidgetsBorders();
MyLayoutUIEditor.showTemplatePHPWidgets();
MyLayoutUIEditor.showTemplatePTLWidgets();
MyLayoutUIEditor.showTemplateCommentsWidgets();
MyLayoutUIEditor.showTemplateJSWidgets();
MyLayoutUIEditor.showTemplateCSSWidgets();


Convert visual to code
```
if (MyLayoutUIEditor.isTemplateLayoutShown())
	MyLayoutUIEditor.convertTemplateLayoutToSource({
		with_head_attributes: true,
		with_body_attributes: true,
	}); //don't ask confirmation message
```

Convert code into visual components:
```
MyLayoutUIEditor.convertTemplateSourceToLayout();
```

Convert code into visual components according with previously defined options:
```
MyLayoutUIEditor.convertTemplateSourceToLayoutAccordingWithOptions();
```

Show Layout without updating the source in the layout:
```
MyLayoutUIEditor.clickViewLayoutTabWithoutSourceConversion();
```

Close the widget properties if open. Do this by clicking in the body, which disables the selected widget automatically.
```
MyLayoutUIEditor.getTemplateWidgetsIframeBody().trigger("click");
```

Get code from editor:
```
var is_full_source_active = MyLayoutUIEditor.getUI().find(" > .options .option.show-full-source").hasClass("option-active");
var code = is_full_source_active ? MyLayoutUIEditor.getTemplateFullSourceEditorValue() : MyLayoutUIEditor.getTemplateSourceEditorValue();
```

Set code to editor:
```
MyLayoutUIEditor.forceTemplateSourceConversionAutomatically(); //Be sure that the template source is selected
		
var is_full_source_active = MyLayoutUIEditor.getUI().find(" > .options .option.show-full-source").hasClass("option-active");
var updated = is_full_source_active ? MyLayoutUIEditor.setTemplateFullSourceEditorValue(code) : MyLayoutUIEditor.setTemplateSourceEditorValue(code);
```

Convert html element to a real widget in the canvas:
```
MyLayoutUIEditor.convertHtmlElementToWidget(elm); //convert html node into a template widget
MyLayoutUIEditor.refreshElementMenuLayer(elm.parent());
```

Recreate widget:
```
MyLayoutUIEditor.recreateWidget(widget); //recreate a template widget

```

Recreate widget children:
```
MyLayoutUIEditor.recreateWidgetChildren(widget); //recreate the children of a template widget
```

Delete widget from canvas:
```
MyLayoutUIEditor.deleteTemplateWidget(widget); //delete a template widget
```

Append some html into an widget in a safe way:
```
MyLayoutUIEditor.parseHtmlSource(html, template_container, opts);
```

Parse the html which already exists and is appended in an existent html element like a main-droppable
```
MyLayoutUIEditor.parseElementInnerHtml(elm, opts);
```

Get cleaned widget html, without reserved keywords from the editor:
```
var widget_inner_html = MyLayoutUIEditor.getElementHtml(widget); //get cleaned inner html from a template widget
var widget_outer_html = MyLayoutUIEditor.getCleanedHtmlElement(widget); //get cleaned outer html from a template widget
var widgets_outer_html = MyLayoutUIEditor.getCleanedHtmlContents(widgets); //get cleaned outer html for a list of template widgets
var widget_attributes_html = MyLayoutUIEditor.getCleanedHtmlElementAttributes(widget, opts); //get cleaned attributes from a template widget
```

Get head html from layout:
```
var head_html = MyLayoutUIEditor.getTemplateLayoutHeadHtml();
var head_attributes = MyLayoutUIEditor.getTemplateLayoutHeadAttributes();
```

Get body html from layout:
```
var body_html = MyLayoutUIEditor.getTemplateLayoutBodyHtml();
var body_attributes = MyLayoutUIEditor.getTemplateLayoutBodyAttributes();
```

Prepare php code inside of body html: 
```
var html = ''; //some complete html code with head and body tags
var exists_body = MyLayoutUIEditor.existsTagFromSource(html, "exists_body");

if (exists_body) {
	var body_html = MyLayoutUIEditor.getTagContentFromSource(html, "body");
	
	//parse body html and replace the original body with that parsed html. This is very important bc if there are php codes inside of html element attributes, this will take care of this case and other cases...
	var new_body_html = MyLayoutUIEditor.getParsedHtmlFromHtmlSource(body_html);
	
	html = MyLayoutUIEditor.replaceTagContentFromSource(html, "body", new_body_html);
}
```

Replaces attributes in html node from code:
```
var html = ''; //some complete html code with head and body tags
var body_attributes = MyLayoutUIEditor.getTagAttributesFromSource(html, "body");
var new_html = MyLayoutUIEditor.replaceTagAttributesFromSource(html, "body", body_attributes);
```

Show Messages:
```
MyLayoutUIEditor.showMessage("Some info message");
MyLayoutUIEditor.showError("Some error message");
MyLayoutUIEditor.showException("Some exception messsage");
```

Remove Messages:
```
var msg_elm = MyLayoutUIEditor.getMessages();
MyLayoutUIEditor.removeMessage( msg_elm.children().first() );
MyLayoutUIEditor.removeMessages("info"); //info or error
```

Show Popup:
```
MyLayoutUIEditor.initPopup(settings);
MyLayoutUIEditor.showPopup();

//then
MyLayoutUIEditor.hidePopup();

//or
MyLayoutUIEditor.destroyPopup(opts);
```

> **Form more calls please open the [LayoutUIEditor.js](js/LayoutUIEditor.js) and search for functions that start with `me.`**

## Other calls from LayoutUIEditor.TextSelection

Init text selection inside of an editor
```
MyLayoutUIEditor.TextSelection.init(elm_to_append_menu, editor_elm);
```

Some getters:
```
var is_active = MyLayoutUIEditor.TextSelection.is_active; //Check if text selection is active
var selection_elm = MyLayoutUIEditor.TextSelection.selection_elm; //get selection elm
var selection_opts = MyLayoutUIEditor.TextSelection.selection_opts; //get selection opts
var menu_elm = MyLayoutUIEditor.TextSelection.menu_elm; //get menu elm
var messages_elm = MyLayoutUIEditor.TextSelection.messages_elm; //get messages elm
var editor_elm = MyLayoutUIEditor.TextSelection.editor_elm; //get editor elm
var elm_to_append_menu = MyLayoutUIEditor.TextSelection.elm_to_append_menu; //get elm to append menu
var saved_range = MyLayoutUIEditor.TextSelection.saved_range; //get saved range
var available_menu_items_props = MyLayoutUIEditor.TextSelection.available_menu_items_props; //get available menu items props
var menu_item_props = MyLayoutUIEditor.TextSelection.getAvailableMenuItemProps(id); //get menu item props
var menu_items_props = MyLayoutUIEditor.TextSelection.menu_items_props; //get current menu items props
var selection = MyLayoutUIEditor.TextSelection.getSelection(); //get current user selection of a text
var menu_item = MyLayoutUIEditor.TextSelection.getMenuItemElmById(menu_item_class); //get menu item by class
var props = MyLayoutUIEditor.TextSelection.getMenuItemPropsByClass(classes_selector); //get menu item props by class
var width = MyLayoutUIEditor.TextSelection.getMenuWidth(); //get menu width
var height = MyLayoutUIEditor.TextSelection.getMenuHeight(); //get menu height
var width = MyLayoutUIEditor.TextSelection.getScrollBarWidth(); //get scrollbar width
var is_shown = MyLayoutUIEditor.TextSelection.isMenuShown(event); //returns if menu is shown
```

Some setters:
```
MyLayoutUIEditor.TextSelection.setMenuWidth(width); //set menu width
MyLayoutUIEditor.TextSelection.setMenuHeight(height); //set menu height
```

Some handlers:
```
MyLayoutUIEditor.TextSelection.showMenu(event) ; //show menu
MyLayoutUIEditor.TextSelection.hideMenu(); //hide menu
MyLayoutUIEditor.TextSelection.refreshMenu(event); //reresh menu
MyLayoutUIEditor.TextSelection.showMessage(text, class_name); //show message
MyLayoutUIEditor.TextSelection.showError(text, class_name); //show error
MyLayoutUIEditor.TextSelection.removeMessage( MyLayoutUIEditor.TextSelection.messages_elm.children().first() ); //remove a message.
MyLayoutUIEditor.TextSelection.removeMessages("info"); //info or error

MyLayoutUIEditor.TextSelection.placeCaretAtStartOfElement(elm); //place caret at the start position of a html node.
MyLayoutUIEditor.TextSelection.placeCaretAtEndOfElement(elm); //place caret at the end position of a html node.
MyLayoutUIEditor.TextSelection.removeCaretFromElement(elm); //remove caret from a html node
MyLayoutUIEditor.TextSelection.executeMenuItemSimpleCommand(e, elm); //execute the command of a menu item
```

> **Form more calls please open the [TextSelection.js](js/TextSelection.js) and search for functions that start with `me.`**

## Other calls from LayoutUIEditor.LayoutUIEditorWidgetResource

Get dynamic widget permissions settings:
```
MyLayoutUIEditor.LayoutUIEditorWidgetResource.getWidgetPermissions(widget); //get defined permissions from a template widget
```

Get dynamic widget resource value settings:
```
MyLayoutUIEditor.LayoutUIEditorWidgetResource.getWidgetResourceValue(widget); //get defined resource value from a template widget
```

Get dynamic widget resources settings:
```
MyLayoutUIEditor.LayoutUIEditorWidgetResource.getWidgetResources(widget); //get defined resources from a template widget
```

Get dynamic widget primary keys defined in the `data-widget-pks-attrs` attribute:
```
MyLayoutUIEditor.LayoutUIEditorWidgetResource.getWidgetDefaultAttributesValues(widget); //get primary keys defined in the `data-widget-pks-attrs` attribute from a template widget
```

Get dynamic widget properties:
```
MyLayoutUIEditor.LayoutUIEditorWidgetResource.getWidgetProperties(widget); //get defined properties from a template widget
```

Based in some json settings with DB details, open the menu settings of a new template widget, populate these settings in the menu and save them into the new widget. Note that this must be a group widget, this is, data-group-list or data-group-form:
```
MyLayoutUIEditor.LayoutUIEditorWidgetResource.prepareWidgetBasedInUserSettings(widget, settings);
```

Based in some json settings with DB details, open an existing widget menu settings, populate these settings in the menu and save them into the existing widget. Note that this must be a group widget, this is, data-group-list or data-group-form:
```
MyLayoutUIEditor.LayoutUIEditorWidgetResource.replaceUserSettingsInWidgetGroup(widget, settings);
```

Check if a resource name is being used by any template widgets and if not return it. This is very useful to remove resources that no longer are used:
```
MyLayoutUIEditor.LayoutUIEditorWidgetResource.filterResourcesIfNotUsedAnymore(resources_name, widget_to_ignore);
```

> **Form more calls please open the [LayoutUIEditorWidgetResource.js](js/LayoutUIEditorWidgetResource.js) and search for functions that start with `me.`**

