<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once $EVC->getUtilPath("CMSPresentationLayerUIHandler");

$sample_url = $project_url_prefix . "phpframework/presentation/template_region_sample?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path&region=$region&sample_path=";

$head = '
<!-- Add Ace Editor CSS and JS -->
<script src="' . $project_common_url_prefix . 'vendor/acecodeeditor/src-min-noconflict/ace.js"></script>
<script src="' . $project_common_url_prefix . 'vendor/acecodeeditor/src-min-noconflict/ext-language_tools.js"></script>

<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icons CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/layout.js"></script>

<!-- Add local Responsive Iframe CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/responsive_iframe.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/responsive_iframe.js"></script>

<!-- Add local CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/template_region_info.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/template_region_info.js"></script>

<script>
</script>';

$main_content = '<div class="title' . ($popup ? " inside_popup_title" : "") . '">Template Region Samples for "' . $region . '"</div>
<div class="template_region_obj">';

if ($sample_files) {
	$main_content .= '<ul>';
	
	foreach ($sample_files as $sample_file) {
		$main_content .= '<li>
		<div class="header">' . pathinfo($sample_file, PATHINFO_FILENAME) . ' <span class="icon maximize" onClick="toggleSampleContent(this)">Maximize</span></div>
		
		<div class="sample">
			<ul class="tabs tabs_transparent tabs_right">
				<li><a href="#view_ui">View UI</a></li>
				<li><a href="#view_source">HTML Source</a></li>
			</ul>
			
			<div id="view_ui" class="view_ui">
				<div class="iframe_toolbar desktop">
					' . CMSPresentationLayerUIHandler::getTabContentTemplateLayoutIframeToolbarContentsHtml() . '
				</div>
				<iframe orig_src="' . $sample_url . $sample_file . '"></iframe>
			</div>
			<div id="view_source" class="view_source">
				<textarea>' . htmlspecialchars(file_get_contents($layer_path . $sample_file), ENT_NOQUOTES) . '</textarea>
			</div>
		</div>
	</li>';
	}
	
	$main_content .= '</ul>';
}
else 
	$main_content .= '<div class="no_template_regions">This template region does not have any samples!</div>';

$main_content .= '</div>';
?>
