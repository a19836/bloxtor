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

$title = "Choose Module in " . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($file_path, $P, true);
$title_icons = '';
$add_block_url = $project_url_prefix . "phpframework/presentation/edit_page_module_block?bean_name=$bean_name&bean_file_name=$bean_file_name&filter_by_layout=$filter_by_layout&path=$path&module_id=#module_id#&edit_block_type=simple";

include $EVC->getViewPath("presentation/create_block");
?>
