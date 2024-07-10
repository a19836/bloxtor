<?php

$filter_by_layout_url_query = LayoutTypeProjectUIHandler::getFilterByLayoutURLQuery($filter_by_layout);

$get_sub_files_url = $project_url_prefix . "phpframework/admin/get_sub_files?bean_name=#bean_name#&bean_file_name=#bean_file_name#$filter_by_layout_url_query&path=#path#&item_type=#item_type#&folder_type=#folder_type#";

$head = '
<!-- Add MyTree main JS and CSS files -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/jquerymytree/css/style.min.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquerymytree/js/mytree.js"></script>

<!-- Add FileManager JS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/file_manager.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/file_manager.js"></script>

<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icon CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/diff/index.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/diff/index.js"></script>

<script>
var get_sub_files_url = \'' . addcslashes($get_sub_files_url, "'") . '\';
var first_node_to_load = ' . json_encode($_GET) . ';
</script>';
$head .= LayoutTypeProjectUIHandler::getHeader();

$main_content = '
<div class="top_bar">
	<header>
		<div class="title">Files diff</div>
		<ul>
			<li class="execute_diff" data-title="Execute Diff"><a onclick="diff();"><i class="icon continue"></i> Execute diff</a></li>
		</ul>
	</header>
</div>

<div class="diff">
	<div id="file_tree" class="mytree hidden">
		<ul>';

foreach ($layers as $layer_type_name => $layer_type)
	foreach ($layer_type as $layer_name => $layer) {
		$properties = $layer["properties"];
		$item_type = $properties["item_type"];
		
		if (!$properties["item_label"]) //bc of $layer_type_name == "vendors"
			$properties["item_label"] = $layer_name;
		
		$url = str_replace("#folder_type#", "", str_replace("#item_type#", $item_type, str_replace("#path#", $properties["path"], str_replace("#bean_name#", $properties["bean_name"], str_replace("#bean_file_name#", $properties["bean_file_name"], $get_sub_files_url)))));
		
		$main_content .= '
		<li data-jstree=\'{"icon":"main_node main_node_' . $properties["item_type"] . '"}\'>
			<a bean_name="' . $properties["bean_name"] . '" bean_file_name="' . $properties["bean_file_name"] . '" item_type="' . $properties["item_type"] . '" folder_path="" path_prefix="' . $properties["item_label"] . '"><label>' . $properties["item_label"] . '</label></a>
			<ul url="' . $url . '"></ul>
		</li>';
	}

$main_content .= '
		</ul>
	</div>

	<div class="files_selection_info">
		<input class="first_selection_info" value="" placeHolder="Please select a file in the above file manager tree" />
		<input class="second_selection_info" value="" placeHolder="Please select a file in the above file manager tree" />
	</div>

	<div class="files_differences">
		<iframe orig_src="' . $project_url_prefix . 'phpframework/diff/diff_files"></iframe>
	</div>
</div>';
?>
