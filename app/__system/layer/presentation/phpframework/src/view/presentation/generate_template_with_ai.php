<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

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
<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/generate_template_with_ai.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/generate_template_with_ai.js"></script>
';

$main_content = '<div class="generate_template_with_ai">
	<div class="top_bar">
		<header>
			<div class="title">
				Generate Template through AI 
				<input type="text" name="template_name" placeHolder="Template name" />
				<input type="text" name="layout_name" placeHolder="Layout name: index" title="If this field is empty, the default layout name will be: index. Please do not add any file extension. This field corresponds to the name of layout and only the file name without extension. \nAdvertisements are not in any region." />
				in ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($selected_project, $P) . '
			</div>
			<ul>
				<li class="save" data-title="Generate and Save Template"><a onClick="saveTemplate()"><i class="icon save"></i> Generate and Save Template</a></li>
			</ul>
		</header>
	</div>';

$main_content .= '
	<div class="instructions">
		<label>Please write in natural language what template do you wish to create:</label>
		<textarea maxlength="1000" placeHolder="Eg:
1. Page with a top menu containing articles categories. 
2. Below should show a list of articles with 5 articles. Each article should be inside of a card and have the title, description, photo (150x200 dimensions) and a star rating block. 
3. At the end add some pagination to the articles list.
4. In the right side of the page, show a side bar with 2 advertisements, where each advertisement contains an image and title with 100x150 dimensions. 
This advertisements html is not inside of any region, because it belongs to the template html."></textarea>
		<div class="info">1000 characters maximum</div>
	</div>
	<div class="regions">
		<label>Please explain the regions your template should have: <span class="icon add" onClick="AddRegion(this)">Add</span></label>
		<ul>
			<li class="region">
				<input class="region_name" placeHolder="Region Name" />
				<input class="region_description" placeHolder="Region Instructions" />
				<span class="icon remove" onClick="RemoveRegion(this)">Remove</span>
			</li>
		</ul>
		<div class="info">
			Regions examples:<br/>
			- Menu => top menu with articles categories.<br/>
			- Content => list of articles.
		</div>
	</div>
</div>';
?>
