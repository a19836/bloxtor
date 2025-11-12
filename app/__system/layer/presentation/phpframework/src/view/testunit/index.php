<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include $EVC->getUtilPath("WorkFlowPresentationHandler");

$choose_test_units_files_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?item_type=test_unit&path=#path#";
$open_test_unit_file_url = $project_url_prefix . "testunit/edit_test?path=#path#";
$execute_tests_url = $project_url_prefix . "testunit/execute_tests";
$manage_file_url = $project_url_prefix . "admin/manage_file?bean_name=test_unit&bean_file_name=&path=#path#&action=#action#&item_type=test_unit&extra=#extra#";
$create_test_url = $project_url_prefix . "phpframework/testunit/create_test?path=#path#&file_name=#extra#";

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
<link rel="stylesheet" href="' . $project_url_prefix . 'css/testunit/index.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/testunit/index.js"></script>

<script>
var open_test_unit_file_url = "' . $open_test_unit_file_url . '";
var execute_tests_url = "' . $execute_tests_url . '";
var manage_file_url = "' . $manage_file_url . '";
var create_test_url = "' . $create_test_url . '";
';
$head .= WorkFlowPresentationHandler::getDaoLibAndVendorBrokersHtml($choose_test_units_files_from_file_manager_url, "", "", "");
$head .= '</script>';

$main_content = '
<div class="top_bar">
	<header>
		<div class="title">Manage Test Units</div>
		<ul>
			<li class="execute" data-title="Execute Selected Tests"><a onClick="executeSelectedTests(true)"><i class="icon continue"></i> Execute Selected Tests</a></li>
		</ul>
	</header>
</div>

<div class="test_units">
	<div id="test_units_tree" class="test_units_tree">
		<ul class="mytree">
			<li>
				<input class="select_test_unit" type="checkbox" value=1 onClick="onTestUnitCheckboxClick(this)" />
				<label>Test Units</label>
				<ul url="' . $choose_test_units_files_from_file_manager_url . '"></ul>
			</li>
		</ul>
	</div>
</div>';
?>
