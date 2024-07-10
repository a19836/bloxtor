<?php
include $EVC->getUtilPath("WorkFlowPresentationHandler");

$choose_lib_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?item_type=lib&path=#path#";
$open_file_docbook_url = $project_url_prefix . "docbook/file_docbook?path=#path#";
$open_file_code_url = $project_url_prefix . "docbook/file_code?path=#path#";

$head = '
<!-- Add MyTree main JS and CSS files -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/jquerymytree/css/style.min.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquerymytree/js/mytree.js"></script>

<!-- Add FileManager JS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/file_manager.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/file_manager.js"></script>

<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icons CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add local CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/docbook/index.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/docbook/index.js"></script>

<script>
var open_file_docbook_url = "' . $open_file_docbook_url . '";
var open_file_code_url = "' . $open_file_code_url . '";
';
$head .= WorkFlowPresentationHandler::getDaoLibAndVendorBrokersHtml("", $choose_lib_files_from_file_manager_url, "", "");
$head .= '</script>';

$main_content = '
<div class="docbook">
	<div class="top_bar">
		<header>
			<div class="title">Doc Book</div>
		</header>
	</div>
	
	<div id="docbook_tree" class="docbook_tree">
		<ul class="mytree">
			<li>
				<label>LIB</label>
				<ul url="' . $choose_lib_files_from_file_manager_url . '"></ul>
			</li>
		</ul>
	</div>
</div>';
?>
