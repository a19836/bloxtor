<?php
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

//http://<domain for this app>/__system/phpframework/lib/jquerylayoutuieditor/

include __DIR__ . "/../util.php";
$menu_widgets_html = getDefaultMenuWidgetsHTML("../widget/");
//echo $menu_widgets_html;die();

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
	<link rel="stylesheet" href="../lib/materialdesigniconicfont/css/material-design-iconic-font.min.css">
	
	<!-- Parse_Str -->
	<script type="text/javascript" src="<?= $common_link; ?>vendor/phpjs/functions/strings/parse_str.js"></script>
	
	<!-- MD5 -->
	<script language="javascript" type="text/javascript" src="<?= $common_link; ?>vendor/jquery/js/jquery.md5.js"></script>
	
	<!-- JQuery Nestable2 -->
	<link rel="stylesheet" href="../lib/nestable2/jquery.nestable.min.css" type="text/css" charset="utf-8" />
	<script language="javascript" type="text/javascript" src="../lib/nestable2/jquery.nestable.min.js"></script>
	
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
			 <script src="../lib/jqueryuidroppableiframe/js/html5_ie8/html5shiv.min.js"></script>
			 <script src="../lib/jqueryuidroppableiframe/js/html5_ie8/respond.min.js"></script>
		<![endif]-->
		
		<!-- Layout UI Editor - Add Iframe droppable fix -->
    	<script type="text/javascript" src="../lib/jqueryuidroppableiframe/js/jquery-ui-droppable-iframe-fix.js"></script>    
    
    	<!-- Layout UI Editor - Add Iframe droppable fix - IE10 viewport hack for Surface/desktop Windows 8 bug -->
    	<script src="../lib/jqueryuidroppableiframe/js/ie10-viewport-bug-workaround.js"></script>
	    	
		<!-- Layout UI Editor - Add Editor -->
		<link rel="stylesheet" href="../css/some_bootstrap_style.css" type="text/css" charset="utf-8" />
		<link rel="stylesheet" href="../css/style.css" type="text/css" charset="utf-8" />
		<link rel="stylesheet" href="../css/widget_resource.css" type="text/css" charset="utf-8" />
		
		<script language="javascript" type="text/javascript" src="../js/TextSelection.js"></script>
		<script language="javascript" type="text/javascript" src="../js/LayoutUIEditor.js"></script>
		<script language="javascript" type="text/javascript" src="../js/CreateWidgetContainerClassObj.js"></script>
		
		<!-- (Optional) Layout UI Editor - LayoutUIEditorFormField.js is optional, bc it depends of task/programming/common/webroot/js/FormFieldsUtilObj.js -->
		<!-- But only exists if phpframework cache was not deleted -->
		<!--script language="javascript" type="text/javascript" src="http://jplpinto.localhost/__system/phpframework/__system/cache/workflow/tasks/default/programming/common/js/global.js"></script>
		<script language="javascript" type="text/javascript" src="http://jplpinto.localhost/__system/phpframework/__system/cache/workflow/tasks/default/programming/common/js/FormFieldsUtilObj.js"></script>
		<script language="javascript" type="text/javascript" src="../js/LayoutUIEditorFormField.js"></script-->
		
		<!-- (Optional) Layout UI Editor - Add Widget Resources -->
		<!--script language="javascript" type="text/javascript" src="../js/LayoutUIEditorWidgetResource.js"></script-->
		
	<!-- (Optional) Others -->
	<link rel="stylesheet" href="css/blue.css" type="text/css" charset="utf-8" />
	<script language="javascript" type="text/javascript" src="../js/script.js"></script>
	
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
		
		.layout-ui-editor.layout-ui-editor-5 {
			margin:100px auto 0 auto;
			/*display:none;*/
		}
	</style>
</head>
<body>
	<?
	if (!isset($_SERVER["HTTP_HOST"]) || $_SERVER["HTTP_HOST"] != "jplpinto.localhost")
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
	
	<button class="layout-ui-editor-3-button" onClick="addTemplateWidgetsDroppable('.layout-ui-editor-3')">Add another Template Widgets Droppable</button>
	
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
	
	
	<div class="layout-ui-editor reverse fixed-side-properties hide-template-widgets-options layout-ui-editor-4 layout-ui-editor-blue">
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
