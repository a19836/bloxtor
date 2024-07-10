<?php
include_once $EVC->getUtilPath("CMSPresentationLayerUIHandler");

$head = '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icons CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add local Responsive Iframe CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/responsive_iframe.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/responsive_iframe.js"></script>

<!-- Add local CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/template_samples.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/template_samples.js"></script>

<script>
</script>';

$main_content = '<div class="title' . ($popup ? " inside_popup_title" : "") . '">Template Samples</div>
<div class="template_samples_obj">';

if ($sample_files) {
	$main_content .= '<ul>';
	
	foreach ($sample_files as $sample_file) {
		$main_content .= '<li>
		<div class="header">' . pathinfo($sample_file, PATHINFO_FILENAME) . ' <span class="icon maximize" onClick="toggleSampleContent(this)">Maximize</span></div>
		
		<div class="sample">
			<div class="iframe_toolbar desktop">
				' . CMSPresentationLayerUIHandler::getTabContentTemplateLayoutIframeToolbarContentsHtml() . '
			</div>
			<iframe id="view_ui" orig_src="' . $sample_file . '"></iframe>
		</div>
	</li>';
	}
	
	$main_content .= '</ul>';
}
else 
	$main_content .= '<div class="no_template_samples">This template does not have any samples!</div>';

$main_content .= '</div>';
?>
