<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include $EVC->getUtilPath("BreadCrumbsUIHandler");

$file_path = isset($file_path) ? $file_path : null;
$P = isset($P) ? $P : null;

$title = 'Add Block in ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($file_path, $P, true);
$title_icons = '<li class="go_back" title="Go Back"><a href="javascript:history.back();"><i class="icon go_back"></i> Go Back</a></li>';
$save_url = $project_url_prefix . "phpframework/presentation/save_page_module_block?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path";

include $EVC->getViewPath("presentation/edit_block_simple");

$main_content .= '<script>
$(function () {
	$(window).unbind("beforeunload");
	
	disableAutoSave(onToggleAutoSave);
	$(".top_bar").find("li.auto_save_activation").remove();
});
</script>';
?>
