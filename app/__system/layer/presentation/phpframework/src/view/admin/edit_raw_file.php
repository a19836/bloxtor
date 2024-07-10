<?php
include $EVC->getUtilPath("BreadCrumbsUIHandler");

$head = '
<!-- Add CodeHighLight CSS and JS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/codehighlight/styles/default.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/codehighlight/highlight.pack.js"></script>

<!-- Add Ace Editor CSS and JS -->
<script src="' . $project_common_url_prefix . 'vendor/acecodeeditor/src-min-noconflict/ace.js"></script>
<script src="' . $project_common_url_prefix . 'vendor/acecodeeditor/src-min-noconflict/ext-language_tools.js"></script>

<!-- Layout UI Editor - Add Code Beautifier -->
<script src="' . $project_common_url_prefix . 'vendor/mycodebeautifier/js/MyCodeBeautifier.js"></script>

<!-- Layout UI Editor - Add Html/CSS/JS Beautify code -->
<script src="' . $project_common_url_prefix . 'vendor/jsbeautify/js/lib/beautify.js"></script>
<script src="' . $project_common_url_prefix . 'vendor/jsbeautify/js/lib/beautify-css.js"></script>
<script src="' . $project_url_prefix . 'lib/myhtmlbeautify/MyHtmlBeautify.js"></script>

<!-- Add MD5 JS File -->
<script src="' . $project_common_url_prefix . 'vendor/jquery/js/jquery.md5.js"></script>

<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Icons CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS and JS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />
<script src="' . $project_url_prefix . 'js/layout.js"></script>

<!-- Add local CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/admin/edit_raw_file.css" type="text/css" charset="utf-8" />
<script src="' . $project_url_prefix . 'js/admin/edit_raw_file.js"></script>
<script>
var file_modified_time = ' . ($file_modified_time ? $file_modified_time : "null") . '; //for version control
var scroll_top = ' . (is_numeric($scroll_top) ? $scroll_top : 0 ) . ';
var editor_code_type = "' . $editor_code_type . '";
var code_id = "' . md5($code) . '";
var readonly = ' . ($readonly ? "true" : "false") . ';
</script>';

$main_content .= '
	<div class="top_bar' . ($popup ? " in_popup" : "") . '">
		<header>
			<div class="title" title="' . $path . '">Edit File: ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($file_path, $obj) . '</div>';

if ($editor_code_type) {
	$main_content .= '<ul>';
	
	if (!$readonly)
		$main_content .= '<li class="save" data-title="Save File"><a onClick="save(false)"><i class="icon save"></i> Save</a></li>';
	
	$main_content .= '	<li class="sub_menu" onClick="openSubmenu(this)">
						<i class="icon sub_menu"></i>
						<ul>
							<li class="editor_settings" title="Open Editor Setings"><a onClick="openEditorSettings()"><i class="icon settings"></i> Open Editor Setings</a></li>';
	
	if ($editor_code_type == "php")
		$main_content .= '		<li class="pretty_print" title="Pretty Print Code"><a onClick="prettyPrintCode()"><i class="icon pretty_print"></i> Pretty Print Code</a></li>';
	
	$main_content .= '			<li class="set_word_wrap" title="Toggle Word Wrap"><a onClick="setWordWrap(this)" wrap="0"><i class="icon word_wrap"></i> Word Wrap</a></li>
							<li class="separator"></li>
							<li class="full_screen" title="Maximize/Minimize Editor Screen"><a onClick="toggleFullScreen(this)"><i class="icon full_screen"></i> Maximize Editor Screen</a></li>
							<li class="separator"></li>
							<li class="dummy_elm_to_add_auto_save_options"></li>';
	
	if (!$readonly)
		$main_content .= '<li class="save" title="Save File"><a onClick="save(false)"><i class="icon save"></i> Save</a></li>';
	
	$main_content .= '
						</ul>
					</li>
				</ul>';
}

$main_content .= '
		</header>
	</div>';

if ($editor_code_type) {
	$main_content .= '
	<div class="code_area with_top_bar_section' . ($popup ? " in_popup" : "") . '">
		<textarea>' . "\n" . htmlspecialchars($code, ENT_NOQUOTES) . '</textarea>
	</div>';
	
	if (!$readonly)
		$main_content .= '
	<div class="confirm_save hidden">
		<div class="title">Please confirm if the code is correct and if it is, click on the save button...</div>
		
		<div class="file_code">
			<div class="old_file_code">
				<label>Old code currently in file:</label>
				<pre><code class="' . $editor_code_type . '">' . str_replace(">", "&gt;", str_replace("<", "&lt;", $code)) . '</code></pre>
			</div>
			<div class="new_file_code">
				<label>New code to be saved:</label>
				<pre><code class="' . $editor_code_type . '"></code></pre>
			</div>
		</div>
		
		<div class="buttons">
			<input type="button" name="cancel" value="Cancel" onClick="cancelSave();" />
			<input type="button" name="save" value="Save" onClick="save(true);" />
		</div>
		
		<div class="disable_auto_scroll" onClick="enableDisableAutoScroll(this);">Click here to disable auto scroll.</div>
	</div>';
}
else 
	$main_content .= '<div class="error">Error: This file is not editable...</div>';

//$head = $CssAndJSFilesOptimizer->prepareHtmlWithOptimizedCssAndJSFiles($head, false);
?>
