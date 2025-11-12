<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include $EVC->getUtilPath("BreadCrumbsUIHandler");

$folder_path = isset($folder_path) ? $folder_path : null;
$obj = isset($obj) ? $obj : null;

$filter_by_layout_url_query = LayoutTypeProjectUIHandler::getFilterByLayoutURLQuery($filter_by_layout);

$choose_queries_from_file_manager_url = $project_url_prefix . "admin/get_sub_files?bean_name=#bean_name#&bean_file_name=#bean_file_name#$filter_by_layout_url_query&path=#path#";

$head = '
<!-- Add MyTree main JS and CSS files -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/jquerymytree/css/style.min.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquerymytree/js/mytree.js"></script>

<!-- Add FileManager JS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/file_manager.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/file_manager.js"></script>

<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Icons CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local JS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/businesslogic/create_business_logic_objs_automatically.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/businesslogic/create_business_logic_objs_automatically.js"></script>
';
$head .= LayoutTypeProjectUIHandler::getHeader();

$main_content = "";

if (!empty($_POST["step_1"])) {
	$exists_any_status_ok = false;
	
	$main_content .= '<div class="statuses">
		<div class="top_bar">
			<header>
				<div class="title" title="' . $path . '">Automatic Create Business Logic Files in ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($folder_path, $obj) . '</div>
			</header>
		</div>
		<div class="title">Statuses</div>
		<table>
			<tr>
				<th class="file_path table_header">File Path</th>
				<th class="object_name table_header">Object Name</th>
				<th class="status table_header">Status</th>
			</tr>';
	
	if (!empty($statuses)) {
		$t = count($statuses);
		for ($i = 0; $i < $t; $i++) {
			$s = $statuses[$i];
			$status = (!empty($s[2]) ? "ok" : "error");
			
			$main_content .= '<tr>
				<td class="file_path">' . (isset($s[0]) ? preg_replace("/\/+/", "/", $s[0]) : "") . '</td>
				<td class="object_name">' . (isset($s[1]) ? $s[1] : "") . '</td>
				<td class="status status_' . $status . '">' . strtoupper($status) . '</td>
			</tr>';
			
			if (!empty($s[2])) {
				$exists_any_status_ok = true;
			}
		}
	}
	
	$main_content .= '
		</table>
	</div>';
	
	if ($exists_any_status_ok)
		$main_content .= '<script>
		if (window.parent && typeof window.parent.refreshAndShowLastNodeChilds == "function")
			window.parent.refreshAndShowLastNodeChilds();
		</script>';
}
else {
	$brokers_db_drivers_name = isset($brokers_db_drivers_name) ? $brokers_db_drivers_name : null;
	$related_brokers = isset($related_brokers) ? $related_brokers : null;
	$db_brokers_bean_file_by_bean_name = isset($db_brokers_bean_file_by_bean_name) ? $db_brokers_bean_file_by_bean_name : null;
	$db_drivers = isset($db_drivers) ? $db_drivers : null;
	$default_broker_name = isset($default_broker_name) ? $default_broker_name : null;
	$is_db_layer = isset($is_db_layer) ? $is_db_layer : null;
	
	$head .= '<script>
	var brokers_db_drivers_name = ' . json_encode($brokers_db_drivers_name) . ';';
	
	if ($related_brokers)
		foreach ($related_brokers as $b)
			if (!empty($b[2])) {
				$get_sub_files_url = str_replace("#bean_file_name#", $b[1], str_replace("#bean_name#", $b[2], $choose_queries_from_file_manager_url));
				
				$head .= 'main_layers_properties.' . $b[2] . ' = {ui: {
					folder: {
						get_sub_files_url: "' . $get_sub_files_url . '",
					},
					cms_common: {
						get_sub_files_url: "' . $get_sub_files_url . '",
					},
					cms_module: {
						get_sub_files_url: "' . $get_sub_files_url . '",
					},
					cms_program: {
						get_sub_files_url: "' . $get_sub_files_url . '",
					},
					cms_resource: {
						get_sub_files_url: "' . $get_sub_files_url . '",
					},
					file: {
						attributes: {
							file_path: "#path#",
							broker_name: "' . $b[0] . '",
						}
					},
					obj: {
						attributes: {
							file_path: "#path#",
							broker_name: "' . $b[0] . '",
						}
					},
					import: {
						attributes: {
							file_path: "#path#",
							broker_name: "' . $b[0] . '",
						}
					},
					referenced_folder: {
						get_sub_files_url: "' . $get_sub_files_url . '",
					},
				}};';
			}
	
	$head .= '
	var get_broker_db_data_url = "' . $project_url_prefix . 'phpframework/dataaccess/get_broker_db_data?bean_name=' . $bean_name . '&bean_file_name=' . $bean_file_name . '";
	</script>';

	$main_content .= '
	<div class="select_options">
		<div class="top_bar">
			<header>
				<div class="title" title="' . $path . '">Automatic Create Business Logic Files in ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($folder_path, $obj) . '</div>
				<ul>
					<li class="continue" data-title="Continue"><a onClick="submitForm(this, checkChooseFiles);"><i class="icon continue"></i> Continue</a></li>
				</ul>
			</header>
		</div>
		
		<form method="post" onSubmit="return checkChooseFiles(this);">
			<div id="choose_queries_from_file_manager" class="choose_from_file_manager">
				<div class="broker' . (count($related_brokers) == 1 ? " single_broker" : "") . '">
					<label>Broker:</label>
					<select onChange="onChangeDBBroker(this)">';
		
		if ($related_brokers) //by default sets $default_broker_name
			foreach ($related_brokers as $b) {
				$b_broker_name = isset($b[0]) ? $b[0] : null;
				$b_bean_file_name = isset($b[1]) ? $b[1] : null;
				$b_bean_name = isset($b[2]) ? $b[2] : null;
				$is_db_broker = $db_brokers_bean_file_by_bean_name[$b_bean_name] == $b_bean_file_name;
				
				$main_content .= '<option bean_file_name="' . $b_bean_file_name . '" bean_name="' . $b_bean_name . '" broker_name="' . $b_broker_name . '"' . ($is_db_broker ? ' is_db_broker="1"' : '') . '>' . $b_broker_name . ($b_bean_name ? '' : ' (Rest)') . '</option>';
			}
			
		$main_content .= '
					</select>
				</div>
				<div class="db_driver">
					<label>DB Driver:</label>
					<select name="db_driver" onChange="onChangeDBDriver(this)">';
		
		if ($db_drivers) //by default sets $default_db_driver
			foreach ($db_drivers as $db_driver_name => $db_driver_props)
				$main_content .= '<option value="' . $db_driver_name . '">' . $db_driver_name . ($db_driver_props ? '' : ' (Rest)') . '</option>';
		
		$main_content .= '			
					</select>
				</div>
				<div class="type">
					<label>Type:</label>
					<select name="type" onChange="onChangeDBType(this)">
						<option value="db">From DB Server</option>
						<option value="diagram">From DB Diagram</option>
					</select>
				</div>
				<div class="include_db_driver">
					<input type="checkbox" name="include_db_driver" value="1" />
					<label>Hard-code db-driver?</label>
				</div>
				<div class="tables"' . ($is_db_layer ? '' : ' style="display:none;"') . '>
					<label>Tables:</label>
					<ul>';
		
		if (!empty($db_driver_tables)) //by default sets $default_db_driver_table
			foreach ($db_driver_tables as $table) {
				$table_name = isset($table["name"]) ? $table["name"] : null;
				$service_name = str_replace(" ", "", ucwords(strtolower(str_replace("_", " ", $table_name)))) . "Service";
				
				$main_content .= '<li class="table">
					<input type="checkbox" name="files[' . $table_name . '][all]" value="' . $default_broker_name . '" />
					<input type="hidden" name="aliases[' . $table_name . '][all]" value="" />
					<label title="Click here to enter a different table alias..." onClick="addServiceAlias(this, \'' . $service_name . '\')">' . $table_name . ' => ' . $service_name . '</label>
				</li>';
			}
		else
			$main_content .= '<li>No tables available...</li>';
		
		$main_content .= '</ul>
				</div>
				<ul class="mytree"' . ($is_db_layer ? ' style="display:none;"' : '') . '>
					<li>
						<label>Root</label>
						<ul layer_url="' . $choose_queries_from_file_manager_url . '"></ul>
					</li>
				</ul>
				<div class="options">
					<div class="resource_services">
						<input type="checkbox" name="resource_services" value="1" checked />
						<label>Do you wish to create the correspondent Resource Services?</label>
					</div>
					<div class="overwrite">
						<input type="checkbox" name="overwrite" value="1" />
						<label>Do you wish to overwrite the selected items, if they already exists?</label>
					</div>
					<div class="namespace">
						<label>Namespace: </label>
						<input type="text" name="namespace" value="" />
					</div>
				</div>
				
				<input type="hidden" name="step_1" value="Continue" />
			</div>
		</form>
		<script>
			updateLayerUrlFileManager( $("#choose_queries_from_file_manager .broker select")[0] );
		</script>
	</div>';
}
?>
