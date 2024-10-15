<?php
include $EVC->getUtilPath("BreadCrumbsUIHandler");

$selected_project = isset($selected_project) ? $selected_project : null;
$P = isset($P) ? $P : null;

$head = '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icon CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Layout UI Editor - Add Html/CSS/JS Beautify code -->
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jsbeautify/js/lib/beautify.js"></script>
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jsbeautify/js/lib/beautify-css.js"></script>
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'lib/myhtmlbeautify/MyHtmlBeautify.js"></script>

<!-- Add Local CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/convert_url_to_template.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/edit_page_and_template.js"></script>
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/convert_url_to_template.js"></script>
';

$main_content = '<div class="convert_url_to_template">
	<div class="top_bar">
		<header>
			<div class="title" title="' . $path . '">
				Convert 
				<input type="text" name="page_url" placeHolder="Page Url" onBlur="loadUrlIfNotYetLoaded();" />
				<a class="icon refresh" onClick="loadUrl();" title="Load Url">Load</a>
				To
				<input type="text" name="template_name" placeHolder="Template name" />
				<input type="text" name="layout_name" placeHolder="Layout name: index" title="If this field is empty, the default layout name will be: index. Please do not add any file extension. This field corresponds to the name of layout and only the file name without extension." />
				in ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($selected_project, $P) . '
			</div>
			<ul>
				<!--li class="convert_to_region" data-title="Convert to Region"><a onClick="convertToRegion()"><i class="icon convert_to_region"></i> Convert to Region</a></li>
				<li class="convert_to_param" data-title="Convert to Param"><a onClick="convertToParam()"><i class="icon convert_to_param"></i> Convert to Param</a></li-->
				<li class="save" data-title="Save as Template"><a onClick="saveTemplate()"><i class="icon save"></i> Save as Template</a></li>
			</ul>
		</header>
	</div>';

$main_content .= '
	<div class="page_html">
		<iframe></iframe>
		<ul class="page_layers"></ul>
		<div class="buttons">
			<button class="convert_to_region" title="Convert to Region" onClick="convertToRegion()">Convert to Region</button>
			<button class="convert_to_param" title="Convert to Param" onClick="convertToParam()">Convert to Param</button>
		</div>
	</div>
</div>';
?>
