<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

//NOTE: IF YOU MAKE	ANY CHANGES IN THIS FILE, PLEASE BE SURE THAT THE create_page.php COVERS THAT CHANGES AND DOESN'T BREAK ITS LOGIC.

include $EVC->getUtilPath("BreadCrumbsUIHandler");

$P = isset($P) ? $P : null;

$head = '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icon CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/install_page.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/install_page.js"></script>

<script>
var get_store_pages_url = "' . $project_url_prefix . "phpframework/admin/get_store_type_content?type=pages" . '"; //This is a global var
var is_popup = ' . ($popup ? 1 : 0) . ';
var is_ai = ' . (!empty($_POST["instructions"]) ? 1 : 0) . ';
var is_remote_url = ' . (!empty($_POST["remote_url"]) ? 1 : 0) . ';
var is_zip_file = ' . (!empty($_FILES["zip_file"]) && empty($_POST["zip_url"]) ? 1 : 0) . ';
</script>';

$main_content = '
	<div class="top_bar' . ($popup ? " in_popup" : "") . '">
		<header>
			<div class="title" title="' . $path . '">Install New Pre-built Page in ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($path, $P) . '</div>
			<ul>
				<li class="continue" data-title="Install Pre-built Page Now"><a onClick="installPage(this)"><i class="icon continue"></i> Install Pre-built Page Now</a></li>
			</ul>
		</header>
	</div>';

if (!empty($_POST)) {
	if (empty($status)) {
		$error_message = !empty($error_message) ? $error_message : "There was an error trying to install this pre-built page. Please try again...";
		
		if (!empty($messages)) {
			$main_content .= '<ul class="messages">';
			
			foreach ($messages as $msg)
				$main_content .= '<li class="' . (isset($msg["type"]) ? $msg["type"] : "") . '">' . (isset($msg["msg"]) ? $msg["msg"] : "") . '</li>';
			
			$main_content .= '</ul>';
		}
	}
	else {
		$status_message = 'Pre-built page successfully installed!';
		
		$on_success_js_func = $on_success_js_func ? $on_success_js_func : "refreshAndShowLastNodeChilds";
		$main_content .= "<script>if (typeof window.parent.$on_success_js_func == 'function') window.parent.$on_success_js_func();</script>";
	}
}

if (!empty($show_install_page)) {
	$main_content .= '
<div class="install_page">
	<ul>
		' . ($get_store_pages_url ? '<li><a href="#store">Store Pages</a></li>' : '') . '
		<li><a href="#local">Upload Local Pre-built Page</a></li>
		<li><a href="#remote">Download Page From Web</a></li>
		<li><a href="#ai">Generate Page with AI</a></li>
	</ul>
	<div id="local" class="file_upload">
		<div class="title">Install a local pre-built page from your computer (.zip file)</div>
		<form method="post" enctype="multipart/form-data">
			<input type="hidden" name="dummy_for_post_var_exists" value="1">
			<input class="upload_file" type="file" name="zip_file">
		</form>';

	$main_content .= '
		' . ($pages_download_page_url ? '<div class="go_to_pages_download_page">To download pre-built pages to your local computer, please click <a href="' . $pages_download_page_url . '" target="download_pages">here</a></div>' : '') . '
	
	</div>
	
	' . ($get_store_pages_url ? '
	<div id="store" class="install_store_page">
		<div class="title">Install a pre-built page from our store</div>
		<div class="search_page">
			<i class="icon search active"></i>
			<input placeHolder="Search" onKeyUp="searchPages(this)" />
			<i class="icon close" onClick="resetSearchPages(this)"></i>
		</div>
		<ul>
			<li class="loading">Loading pre-built pages from store...</li>
		</ul>
	</div>' : '') . '
	
	<div id="remote" class="install_page_url">
		<div class="title">Install a page based in an url from the web</div>
		<form method="post" enctype="multipart/form-data">
			<input type="hidden" name="dummy_for_post_var_exists" value="1">
			<input class="remote_url" type="url" name="remote_url" value="' . (isset($remote_url) ? $remote_url : "") . '" placeHolder="Write an url for a web page">
			<a class="icon refresh" href="javascript:void(0)" onClick="viewPageUrl(this)" title="Click to view the page correspondent to your url">Refresh</a>
		</form>
		<iframe></iframe>
	</div>';
	
	$place_holder = !$openai_encryption_key ? 'Artificial Intelligence is disabled. To enable it, please add your OpenAI Key in the \'Manage Permissions/Users\' panel.' : 'Eg:
1. Page with a top menu containing articles categories. 
2. Below should show a list of articles with 5 articles. Each article should be inside of a card and have the title, description, photo (150x200 dimensions) and a star rating block. 
3. At the end add some pagination to the articles list.
4. In the right side of the page, show a side bar with 2 advertisements, where each advertisement contains an image and title with 100x150 dimensions.';
	
	$main_content .= '
	<div id="ai" class="install_page_with_ai">
		<div class="title">Install a page based in AI</div>
		
		<form method="post" enctype="multipart/form-data">
			<div class="instructions">Please write in natural language what page do you wish to create:<textarea name="instructions" value="' . (isset($instructions) ? $instructions : "") . '" placeHolder="' . $place_holder . '"></textarea></div>
			
			<div class="image">Or upload an image with a layout, and AI will attempt to convert it into HTML: <input type="file" name="image"/></div>
		</form>
	</div>
</div>';
}
?>
