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
		<script language="javascript" type="text/javascript" src="../js/LayoutUIEditorWidgetResource.js"></script>
	
	<!-- (Optional) Add Fontawsome Icons CSS -->
	<link rel="stylesheet" href="<?= $common_link; ?>vendor/fontawesome/css/all.min.css">
	
	<!-- (Optional) Others -->
	<link rel="stylesheet" href="css/beauty.css" type="text/css" charset="utf-8" />
	<script language="javascript" type="text/javascript" src="../js/script.js"></script>
	
	<style>
	.layout-ui-editor {
		margin-top:20px !important;
	}
	.layout-ui-editor > .tabs > .tab,
	  .layout-ui-editor > .tabs > .tab.tab-active {
		background:#0d6efd;
		color:#fff;
	}
	</style>
	<script>
	var MyLayoutUIEditor = new LayoutUIEditor();
	
	$(function() {
		MyLayoutUIEditor.options.ui_selector = ".layout-ui-editor";
		MyLayoutUIEditor.options.on_ready_func = function() {
			setAvailableObjects(MyLayoutUIEditor);
			
			//show view layout panel instead of code
			var luie = MyLayoutUIEditor.getUI();
			var view_layout = luie.find(" > .tabs > .view-layout");
			var view_source = luie.find(" > .tabs > .view-source");
			view_layout.addClass("do-not-confirm");
			view_source.removeClass("is-source-changed"); //very important, otherwise the layout will be replaced with the default source and will overwrite the layout_ui_editor_iframe.html iframe contents.
			view_layout.trigger("click");
			view_layout.removeClass("do-not-confirm");
			view_source.addClass("is-source-changed");
			
			//show php widgets, borders and background
			//MyLayoutUIEditor.showTemplateWidgetsDroppableBackground();
			MyLayoutUIEditor.showTemplateWidgetsBorders();
			MyLayoutUIEditor.showTemplateJSWidgets();
			
			//setTimeout(function() {
			//	MyLayoutUIEditor.getTemplateWidgetsIframe().attr("src", "dynamic_resources_iframe.html");
			//}, 3000);
		};
		MyLayoutUIEditor.init("MyLayoutUIEditor");
	});
	</script>
</head>
<body>
	<div class="layout-ui-editor reverse fixed-side-properties hide-template-widgets-options layout-ui-editor-beauty full-screen">
		<div class="template-widgets">
			<iframe class="template-widgets-droppable" data-init-src="dynamic_resources_iframe.html"></iframe>
		</div>

		<ul class="menu-widgets hidden">
			<? echo $menu_widgets_html; ?>
		</ul>
	</div>
</body>
</html>
