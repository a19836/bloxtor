<?php
//http://jplpinto.localhost/__system/phpframework/lib/jquerylayoutuieditor/layout_ui_editor.php

define('LIB_PATH', dirname(dirname(dirname(dirname(dirname(dirname(dirname(dirname(__DIR__)))))))) . "/app/lib/");
include_once LIB_PATH . "org/phpframework/util/import/lib.php";
include_once get_lib("org.phpframework.xmlfile.XMLFileParser");
include __DIR__ . "/util.php";

$widgets_root_path = __DIR__ . DIRECTORY_SEPARATOR . "widget" . DIRECTORY_SEPARATOR;
$widgets = scanWidgets($widgets_root_path);
//print_r($widgets);die();
$menu_widgets_html = getMenuWidgetsHTML($widgets, $widgets_root_path, "widget/");
//echo $menu_widgets_html;die();

$project_protocol = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? "https://" : "http://";
$project_link = $project_protocol . $_SERVER["HTTP_HOST"] . (isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : "");
$parts = explode("/__system/", $project_link);
$common_link = $parts[0] . "/__system/common/";
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	
	<!-- JQuery -->
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>vendor/jquery/js/jquery-1.8.1.min.js"></script>
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>vendor/jqueryui/js/jquery-ui-1.11.4.min.js"></script>
	<link rel="stylesheet" href="<?= $common_link; ?>vendor/jqueryui/css/jquery-ui-1.11.4.css">
	
	<!-- To work on mobile devices with touch -->
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>vendor/jqueryuitouchpunch/jquery.ui.touch-punch.min.js"></script>
	
	<!-- Layout UI Editor - Jquery Tap-Hold Event JS file -->
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>vendor/jquerytaphold/taphold.js"></script>

	<!-- Material-design-iconic-font -->
	<link rel="stylesheet" href="vendor/materialdesigniconicfont/css/material-design-iconic-font.min.css">
	
	<!-- Color -->
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>js/color.js"></script>
	
	<!-- MyJSLib -->
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>js/MyJSLib.js"></script>
	
	<!-- Parse_Str -->
	<script type="text/javascript" src="<?= $common_link; ?>vendor/phpjs/functions/strings/parse_str.js"></script>
	
	<!-- MD5 -->
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>vendor/jquery/js/jquery.md5.js"></script>
	
	<!-- JQuery Nestable2 -->
	<link rel="stylesheet" href="vendor/nestable2/jquery.nestable.min.css" type="text/css" charset="utf-8" />
	<script language="javascript" type="text/javascript" src="vendor/nestable2/jquery.nestable.min.js"></script>
	
	<!-- Add Code Editor JS files -->
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>vendor/acecodeeditor/src-min-noconflict/ace.js"></script>
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>vendor/acecodeeditor/src-min-noconflict/ext-language_tools.js"></script>
	
	<!-- Add Code Beautifier -->
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>vendor/mycodebeautifier/js/MyCodeBeautifier.js"></script>

	<!-- Add Html/CSS/JS Beautify code -->
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>vendor/jsbeautify/js/lib/beautify.js"></script>
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>vendor/jsbeautify/js/lib/beautify-css.js"></script>
	<script language="javascript" type="text/javascript" src="../myhtmlbeautify/MyHtmlBeautify.js"></script>
	
	<!-- Add Auto complete -->
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>vendor/myautocomplete/js/MyAutoComplete.js"></script>
	<link rel="stylesheet" href="<?= $common_link; ?>vendor/myautocomplete/css/style.css">
	
	<!-- CONTEXT MENU -->
		<link rel="stylesheet" href="<?= $common_link; ?>vendor/jquerymycontextmenu/css/style.css" type="text/css" charset="utf-8" />
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>vendor/jquerymycontextmenu/js/jquery.mycontextmenu.js"></script>
    	
	<!-- Layout UI Editor -->
		<!-- Layout UI Editor - HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
		<!--[if lt IE 9]>
			 <script src="vendor/jqueryuidroppableiframe/js/html5_ie8/html5shiv.min.js"></script>
			 <script src="vendor/jqueryuidroppableiframe/js/html5_ie8/respond.min.js"></script>
		<![endif]-->
		
		<!-- Layout UI Editor - Add Iframe droppable fix -->
	    	<script type="text/javascript" src="vendor/jqueryuidroppableiframe/js/jquery-ui-droppable-iframe-fix.js"></script>    
	    
	    	<!-- Layout UI Editor - Add Iframe droppable fix - IE10 viewport hack for Surface/desktop Windows 8 bug -->
	    	<script src="vendor/jqueryuidroppableiframe/js/ie10-viewport-bug-workaround.js"></script>
	    	
		<!-- Layout UI Editor - Add Editor -->
		<link rel="stylesheet" href="css/some_bootstrap_style.css" type="text/css" charset="utf-8" />
		<link rel="stylesheet" href="css/style.css" type="text/css" charset="utf-8" />
		<link rel="stylesheet" href="css/widget_resource.css" type="text/css" charset="utf-8" />
		
		<script language="javascript" type="text/javascript" src="js/TextSelection.js"></script>
		<script language="javascript" type="text/javascript" src="js/LayoutUIEditor.js"></script>
		<script language="javascript" type="text/javascript" src="js/CreateWidgetContainerClassObj.js"></script>
		
		<!-- Layout UI Editor - LayoutUIEditorFormField.js is optional, bc it depends of task/programming/common/webroot/js/FormFieldsUtilObj.js -->
		<script language="javascript" type="text/javascript" src="http://jplpinto.localhost/__system/phpframework/__system/cache/workflow/tasks/default/programming/common/js/global.js"></script><!-- Only exists if phpframework cache was not deleted -->
		<script language="javascript" type="text/javascript" src="http://jplpinto.localhost/__system/phpframework/__system/cache/workflow/tasks/default/programming/common/js/FormFieldsUtilObj.js"></script><!-- Only exists if phpframework cache was not deleted -->
		<script language="javascript" type="text/javascript" src="js/LayoutUIEditorFormField.js"></script>
		<script language="javascript" type="text/javascript" src="js/LayoutUIEditorWidgetResource.js"></script>
		
	<!-- Others -->
	<script language="javascript" type="text/javascript" src="js/script.js"></script>
	
	<style>
		.layout-ui-editor.layout-ui-editor-1 {
			width:90%;
			margin:0 auto;
			/*display:none;*/
		}
		
		.layout-ui-editor.layout-ui-editor-2 {
			width:80%;
			margin:100px auto 0 auto;
			background:#333;
			/*display:none;*/
		}
		.layout-ui-editor.layout-ui-editor-2 > .options > .options-center {
			background:#333;
		}
		
		.layout-ui-editor-3-button {
			margin:100px auto 50px auto;
			display:block;
		}
		.layout-ui-editor.layout-ui-editor-3 {
			width:80%;
			margin:20px auto 0 auto;
			background:#660000;
			/*display:none;*/
		}
		.layout-ui-editor.layout-ui-editor-3 > .options > .options-center {
			background:#660000;
		}
		
		.layout-ui-editor.layout-ui-editor-4 {
			margin:50px auto 0 auto;
			
			/* generic */
			--main-editor-bg:#d5e3e4;
			--main-editor-color:#888;
			
			/* tabs */
			--tabs-bg:#fff;
			--tabs-item-border:#ccc;
			--tabs-item-bg:#fff;
			--tabs-item-active-border:#ccc;
			--tabs-item-active-bg:#d5e3e4;
			--tabs-item-hover-color:#333;
			
			/* menu-widgets */
			--menu-widgets-widget-border:transparent;
			--menu-widgets-group-color:#485152;
			
			/* template-source */
			--template-source-border:#d5e3e4;
			--template-source-bg:#fff;
			
			/* template-preview */
			--template-preview-border:#d5e3e4;
			--template-preview-bg:#fff;
			
			/* template-widgets-droppable */
			--template-widgets-droppable-border:#d5e3e4;
			--template-widgets-droppable-bg:#fff;
			
			/* menu-settings */
			--menu-settings-bg:#fff;
			
			--menu-settings-resize-bg:#bcd4d6;
			
			--menu-settings-input-border:transparent;
			--menu-settings-input-bg:#d5e3e4;
			--menu-settings-input-color:#485152;
			--menu-settings-select-option-bg:#485152;
			--menu-settings-select-option-color:#ddd;
			
			--menu-settings-button-border:#d5e3e4;
			--menu-settings-button-bg:#485152;
			--menu-settings-button-color:#d5e3e4;
			--menu-settings-button-hover-color:#bcd4d6;
			
			--menu-settings-user-classes-bg:rgba(0,0,0,0.05);
			--menu-settings-user-class-border:transparent;
			--menu-settings-user-class-bg:#ddd;
			--menu-settings-user-class-color:#333;
			
			--menu-settings-group-color:#485152;
			--menu-settings-group-list-bg:transparent;
			--menu-settings-group-block-list-border:transparent;
			--menu-settings-group-block-list-bg:rgba(0,0,0,0.05);
			
			--menu-settings-measurement-style-select-border-left:#bcd4d6;
			--menu-settings-buttons-style-btn-border:transparent;
			--menu-settings-buttons-style-btn-bg:rgba(0,0,0,0.25);
			--menu-settings-buttons-style-btn-color:#485152;
			--menu-settings-buttons-style-btn-hover-color:#999;
			--menu-settings-buttons-style-btn-selected-border:transparent;
			--menu-settings-buttons-style-btn-selected-bg:#485152;
			--menu-settings-buttons-style-btn-selected-color:#ddd;
			--menu-settings-color-style-color-selector-border:transparent;
			--menu-settings-color-style-color-selector-bg:transparent;
			--menu-settings-color-style-color-code-bg:transparent;
			--menu-settings-color-style-color-code-border:transparent;
			
			--menu-settings-info-border:#bcd4d6;
			--menu-settings-info-bg:#d5e3e4;
			
			/* widget-header */
			--widget-header-border:transparent;
			--widget-header-bg:#d5e3e4;
			--widget-header-color:#485152;
			--widget-header-hover-color:#333;
			
			/* droppable-header */
			--droppable-header-border:transparent;
			--droppable-header-bg:#d5e3e4;
			--droppable-header-color:#485152;
			--droppable-header-hover-color:#333;
			
			/* menu-layers */
			--menu-layers-group-hover-border:#bcd4d6;
			--menu-layers-group-selected-border:#bcd4d6;
			--menu-layers-group-title-border:transparent;
			--menu-layers-group-title-color:#485152;
			--menu-layers-group-selected-title-bg:#72a3a9;
			--menu-layers-group-selected-title-color:#bcd4d6;
			--menu-layers-group-open-title-color:#333;
			--menu-layers-group-title-hover-color:#73a3a8;
			--menu-layers-place-holder-bg:#73a3a8;
			--menu-layers-sort-clone-bg:#73a3a8;
			
			/* option */
			--option-active-color:#555;
			--option-hover-color:#777;
			--option-input-border:transparent;
			--option-input-bg:#fff;
			--option-input-color:#000;
			--option-label-color:#000;
			--option-label-hover-color:#000

			/* scrollbar */
			--scrollbar-track-bg:#555;
			--scrollbar-thumb-bg:#ccc;
			
			/* resize-panel */
			--resize-panel-bg:#bcd4d6;
			
			/* text-selection-menu */
			--text-selection-menu-bg:#d5e3e4;
			--text-selection-menu-color:#485152;
			
			--text-selection-menu-item-hover-color:#777;
			--text-selection-menu-item-active-border:#d5e3e422;
			--text-selection-menu-item-active-color:#999;
			
			--text-selection-menu-input-border:#bcd4d6;
			--text-selection-menu-input-bg:#fff;
			--text-selection-menu-input-color:#485152;
			--text-selection-menu-select-option-disabled-bg:#d5e3e4;
			--text-selection-menu-select-option-disabled-color:#485152;
			--text-selection-menu-icon-color:#555;
			
			--text-selection-menu-popup-border:#bcd4d6;
			--text-selection-menu-popup-button-border:#bcd4d6;
			--text-selection-menu-popup-button-bg:#485152;
			--text-selection-menu-popup-button-color:#d5e3e4;
			--text-selection-menu-popup-input-border:#bcd4d6;
			--text-selection-menu-popup-input-bg:transparent;
			--text-selection-menu-popup-input-color:#485152;
			
			--text-selection-menu-table-border:#bcd4d6;
			--text-selection-menu-table-bg:#fff;
			
			--text-selection-menu-tooltip-bg:#d5e3e4;
			--text-selection-menu-tooltip-color:#666;
			
			--text-selection-menu-msg-color:#fff;
			--text-selection-menu-error-msg-bg:rgba(255, 0, 0, 0.4);
			--text-selection-menu-info-msg-bg:rgba(0, 0, 0, 0.1);
			
			/* add-widget-popup */
			--add-widget-popup-border:#bcd4d6;
			--add-widget-popup-bg:#666;
			--add-widget-popup-color:#d5e3e4;
			--add-widget-popup-button-border:#bcd4d6;
			--add-widget-popup-button-bg:#73a3a8;
			--add-widget-popup-button-color:#d5e3e4;
			--add-widget-popup-input-border:#485152;
			--add-widget-popup-input-bg:#485152;
			--add-widget-popup-input-color:#485152;
			
			/* template-widget-source-popup */
			--template-widget-source-popup-bg:#485152;
			--template-widget-source-popup-color:#d5e3e4;
			
			/* messages */
			--editor-info-msg-bg:rgba(204, 255, 153, 0.8);
			--editor-error-msg-bg:rgba(255, 0, 0, 0.8);
			--editor-error-msg-color:#fff;
		}
		.layout-ui-editor.layout-ui-editor-4 > .menu-widgets, 
		  .layout-ui-editor.layout-ui-editor-4 > .menu-layers, 
		  .layout-ui-editor.layout-ui-editor-4 > .menu-settings, 
		  .layout-ui-editor.layout-ui-editor-4 > .template-widgets-options {
			border:0;
		}
		.layout-ui-editor.layout-ui-editor-4 > .menu-settings input,
		  .layout-ui-editor.layout-ui-editor-4 > .menu-settings select,
		  .layout-ui-editor.layout-ui-editor-4 > .menu-settings textarea {
			color:#485152 !important;
		}
		
		.layout-ui-editor.layout-ui-editor-5 {
			margin:100px auto 0 auto;
			/*display:none;*/
		}
	</style>
</head>
<body>
<?
if ($_SERVER["HTTP_HOST"] != "jplpinto.localhost")
	echo("<script>alert('Please configure your computer to point the host jplpinto.localhost to this IP, otherwise the layout ui editor won't work with the FormFieldsUtilObj!');</script>");
?>
	<div class="layout-ui-editor reverse layout-ui-editor-1">
		<ul class="menu-widgets hidden">
			<? echo $menu_widgets_html; ?>
		</ul>
	</div>
	
	<div class="layout-ui-editor layout-ui-editor-2">
		<ul class="menu-widgets hidden">
			<? echo $menu_widgets_html; ?>
		</ul>
	</div>
	
	<button class="layout-ui-editor-3-button" onClick="addTemplateWidgetsDroppable3()">Add another Template Widgets Droppable</button>
	
	<div class="layout-ui-editor reverse fixed-properties layout-ui-editor-3">
		<ul class="menu-widgets hidden">
			<? echo $menu_widgets_html; ?>
		</ul>
	</div>
	
	<br/>
	<br/>
	<br/>
	Copy this html to test:
	<div style="color:#666; padding-left:20px; font-size:90%;">
		some html &lt;?= $y + 1; ?&gt; <br/>&lt;div &lt;?php echo "w=&amp;quot;1/11&amp;quot;"; ?&gt; x="&lt;?php echo "t=&amp;quot;2/22&amp;quot;"; ?&gt;" age="3&amp;quot;3" &lt;ptl:echo"asd"/&gt; &gt;asdas&lt;/div&gt;<br/> bla ble&lt;ptl:echo "jp"/&gt; some other html
		<br/>
		&lt;div class="droppable template-widget template-widget-html-tag template_widget_html-tag_l5i2y_681 widget-active html-tag list-responsive" id="widget_list_widget_list_1" data-widget-list="" data-widget-props="{&amp;quot;pks_attrs_names&amp;quot;:&amp;quot;id&amp;quot;, &amp;quot;load&amp;quot;:&amp;quot;MyWidgetResourceLib.ListHandler.loadListTableAndTreeResource&amp;quot;}" data-widget-resources="{&amp;quot;load&amp;quot;:[{&amp;quot;name&amp;quot;:&amp;quot;items&amp;quot;}],&amp;quot;remove&amp;quot;:[{&amp;quot;name&amp;quot;:&amp;quot;delete_item&amp;quot;}]}" xxx="&lt;ptl:if&gt;&lt;ptl:echo $x/&gt;&lt;/ptl:if&gt;" &lt;ptl:if&gt;&lt;ptl:echo $x/&gt;&lt;/ptl:if&gt;&gt;
asd a&lt;/div&gt;
		<br/>
		&lt;div &lt;ptl:if&gt;&lt;ptl:echo $x/&gt;&lt;/ptl:if&gt;&gt; asd a&lt;/div&gt;
		<br/>
		&lt;?php 
		$EVC-&gt;getCMSLayer()-&gt;getCMSTemplateLayer()-&gt;addRegionHtml("Content", "&lt;div class=\"list-responsive\" id=\"widget_list_widget_list_1\" data-widget-list data-widget-props=\"{&quot;load&quot;:&quot;MyWidgetResourceLib.ListHandler.loadListTableAndTreeResource&quot;,&quot;items_limit_per_page&quot;:&quot;5&quot;,&quot;pks_attrs_names&quot;:[&quot;id&quot;]}\" data-widget-resources=\"{&quot;load&quot;:[{&quot;name&quot;:&quot;items&quot;}],&quot;remove&quot;:[{&quot;name&quot;:&quot;delete_item&quot;}]}\" data-widget-resources-load=\"\"&gt;asd&lt;/div&gt;");
		?&gt;
	</div>
	
	
	<div class="layout-ui-editor reverse fixed-side-properties hide-template-widgets-options layout-ui-editor-4">
		<ul class="menu-widgets hidden">
			<? echo $menu_widgets_html; ?>
		</ul>
	</div>
	
	<div class="layout-ui-editor reverse fixed-side-properties hide-template-widgets-options layout-ui-editor-5">
		<div class="template-widgets">
			<iframe class="template-widgets-droppable" data-init-src="layout_ui_editor_iframe.html"></iframe>
		</div>

		<ul class="menu-widgets hidden">
			<? echo $menu_widgets_html; ?>
		</ul>
	</div>
</body>
</html>
