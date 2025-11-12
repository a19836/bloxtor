<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.util.text.TextValidator");
include_once get_lib("org.phpframework.util.MimeTypeHandler");

$table_fields = isset($table_fields) ? $table_fields : null;
$table_fields_types = isset($table_fields_types) ? $table_fields_types : null;
$pks = isset($pks) ? $pks : null;
$fks = isset($fks) ? $fks : null;
$extra_fks = isset($extra_fks) ? $extra_fks : null;

$head = '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icons CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/db/manage_indexes.css" charset="utf-8" />
<script src="' . $project_url_prefix . 'js/db/manage_indexes.js"></script>';

$main_content = '
<div class="top_bar' . ($popup ? " in_popup" : "") . '">
	<header>
		<div class="title">
			Manage Indexes for table: \'' . $table . '\' in DB: \'' . $bean_name . '\'
		</div>
		<ul>
			<li class="refresh" data-title="Refresh Page"><a class="icon refresh" onClick="refreshPage(this)">Refresh</a></li>
		</ul>
	</header>
</div>

<div class="manage_indexes with_top_bar_section' . ($popup ? " in_popup" : "") . '">';

if (empty($table_exists))
	$main_content .= '<div class="error">Table does not exist!</tr>';
else if (empty($table_fields))
	$main_content .= '<div class="error">Table fields do not exist!</tr>';
else {
	//prepare main content
	$main_content .= '
		<div class="responsive_table">
			<table class="display compact">
				<thead>
					<tr>';
	
	if ($indexes_fields)
		foreach ($indexes_fields as $field_name) {
			$label = ucwords(str_replace(array("_", "-"), " ", strtolower($field_name)));
			$main_content .= '<th class="table_header">' . $label . '</th>';
		}
	
	$main_content .= '
						<th class="table_header actions"></th>
					</tr>
				</thead>
				<tbody>';
	
	if (empty($results))
		$main_content .= '<tr><td class="empty" colspan="' . ($indexes_fields ? count($indexes_fields) + 1 : 0) . '">Empty indexes...</tr>';
	else {
		$t = count($results);
		for ($i = 0; $i < $t; $i++) {
			$main_content .= '<tr>';
			
			foreach ($indexes_fields as $field_name)
				$main_content .= '<td class="' . strtolower($field_name) . '">' . $results[$i][$field_name] . '</td>';
			
			$main_content .= '
					<td class="actions">
						<span class="icon delete" title="Delete" onClick="deleteRow(this)">Remove</span>
					</td>
				</tr>';
		}
	}
	
	$main_content .= '</tbody>
			</table>
		</div>';
}

$main_content .= '</div>';
?>
