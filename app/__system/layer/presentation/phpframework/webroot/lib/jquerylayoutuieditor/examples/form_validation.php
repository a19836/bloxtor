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
		<script language="javascript" type="text/javascript" src="http://jplpinto.localhost/__system/phpframework/__system/cache/workflow/tasks/default/programming/common/js/global.js"></script>
		<script language="javascript" type="text/javascript" src="http://jplpinto.localhost/__system/phpframework/__system/cache/workflow/tasks/default/programming/common/js/FormFieldsUtilObj.js"></script>
		<script language="javascript" type="text/javascript" src="../js/LayoutUIEditorFormField.js"></script>
		
		<!-- (Optional) Layout UI Editor - Add Widget Resources -->
		<!--script language="javascript" type="text/javascript" src="../js/LayoutUIEditorWidgetResource.js"></script-->
	
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
			//show view layout panel instead of code
			var luie = MyLayoutUIEditor.getUI();
			var view_layout = luie.find(" > .tabs > .view-layout");
			view_layout.addClass("do-not-confirm");
			view_layout.trigger("click");
			view_layout.removeClass("do-not-confirm");
			
			MyLayoutUIEditor.showTemplateWidgetsBorders();
			MyLayoutUIEditor.showTemplateJSWidgets();
		};
		MyLayoutUIEditor.init("MyLayoutUIEditor");
		
		var my_js_lib_path = '<?= $common_link; ?>js/MyJSLib.js';
		var html = '<!DOCTYPE html><html><head><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous"><' + 'script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></' + 'script><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"><' + 'script src="' + my_js_lib_path + '"></' + 'script></head><body><div class="m-3 p-3"><div class="text-center fw-bold mb-4 text-success"><h2>Form with Validation</h2><div>For more information about the form validation please open the <a href="https://bloxtor.com/onlineitframeworktutorial/?block_id=documentation/layout_ui_editor/myjslib_with_form_fields_validation" target="MyJSLib">MyJSLib.FormHandler Tutorial</a></div><h5 class="text-primary mt-2">To test, click on the "Preview" tab below and then the "Save" button</h5><div class="small text-secondary">On form submit, you will be forward to www.bloxtor.com</div></div><div class="alert alert-danger" role="alert" style="display:none"><a class="float-end text-decoration-none text-secondary" href="javascript:void(0)" onClick="this.parentNode.style.display=\'none\';">X</a>Errors:<ul></ul></div><form method="get" onsubmit="return (typeof MyJSLib == \'undefined\' || MyJSLib.FormHandler.formCheck(this));" action="//bloxtor.com"><div class="row mb-3"><label class="col-sm-4 col-form-label">Name<span class="label-colon">:</span><span class="text-danger label-mandatory">*</span></label><div class="col-sm-8"><input class="form-control bg-light" data-allow-null="0" placeholder="Your Name" data-validation-label="Name" name="name" data-validation-message="You must write your name - minimum 2 words" data-min-words="2"/></div></div><div class="row mb-3"><label class="col-sm-4 col-form-label">Gender<span class="label-colon">:</span><span class="text-danger label-mandatory">*</span></label><div class="col-sm-8"><select class="form-select bg-light" data-allow-null="0" data-validation-label="Gender" data-validation-message="You must select your gender"><option value="" disable>Select your gender</option><option value="m">Male</option><option value="f">Female</option><option value="o">Other</option></select></div></div><div class="row mb-3"><label class="col-sm-4 col-form-label">About You<span class="label-colon">:</span><span class="text-danger label-mandatory">*</span></label><div class="col-sm-8"><textarea class="form-control bg-light" rows="5" data-allow-null="0" data-validation-label="about" maxlength="200" placeholder="Write something about you..." data-validation-message="You must write something about you" minlength="50" data-min-words="10" data-max-words="100"></textarea></div></div><div class="text-right text-end mt-4" data-widget-item-actions-column=""><button class="btn btn-sm btn-secondary text-nowrap m-1 float-start cancel" type="reset" title="Cancel"><i class="bi bi-backspace icon icon-cancel mr-1 me-1 overflow-visible"></i>Cancel</button><button class="btn btn-sm btn-primary text-nowrap m-1" type="submit" title="Save" data-confirmation="1" data-confirmation-message="Do you really wish to proceed?" data-clicked="1"><i class="bi bi-save icon icon-save mr-1 me-1 overflow-visible"></i>Save with Alert Popup</button><button class="btn btn-sm btn-primary text-nowrap m-1" title="Save" onclick="save(this);return false;"><i class="bi bi-save icon icon-save mr-1 me-1 overflow-visible"></i>Save with Inline Message</button></div></form><' + 'script>function save(btn) {\n	return typeof MyJSLib == "undefined" || MyJSLib.FormHandler.formCheck(btn.form, onCheckForm);\n}\n\nfunction onCheckForm(oForm, wrong_nodes) {\n	console.log(wrong_nodes);\n	var message = MyJSLib.FormHandler.getFormErrorMessage(wrong_nodes);\n\n	if (message) {\n	    var alert = document.querySelector(".alert > ul");\n	    alert.innerHTML = "<li>" + message.replace(/(^\\s+|\\s+$)/, "").split("\\n").join("</li><li>") + "</li>";\n	    alert.parentNode.style.display = "block";\n	    return false;\n	}\n\n	oForm.submit();\n	return true;\n}</' + 'script></div></body></html>';
		
		html = MyHtmlBeautify.beautify(html);
		MyLayoutUIEditor.getUI().find('.options .option.show-full-source').trigger("click");
		MyLayoutUIEditor.setTemplateFullSourceEditorValue(html);
	});
	</script>
</head>
<body>
	<div class="layout-ui-editor reverse fixed-side-properties hide-template-widgets-options layout-ui-editor-beauty full-screen">
		<ul class="menu-widgets hidden">
			<? echo $menu_widgets_html; ?>
		</ul>
	</div>
	
	<div></div>
</body>
</html>
