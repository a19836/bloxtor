<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

$head = '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icons CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/admin/manage_modules.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/admin/manage_modules.js"></script>
';

$is_single_presentation_layer = count($modules) == 1;

$main_content = '
<div class="top_bar' . ($popup ? ' in_popup' : '') . '">
	<header>
		<div class="title">Manage Modules' . ($is_single_presentation_layer ? '' : ' in layer') . ':</div>
	</header>
</div>

<div class="modules_list' . ($popup ? ' in_popup' : '') . '">
	<div class="layer' . ($is_single_presentation_layer ? ' hidden' : '') . '">
		<label>Presentation Layer:</label>
		<select onChange="showModulesLayer(this)" title="Choose a Presentation Layer">';

$t = count($modules);
for ($i = 0; $i < $t; $i++) {
	$m = $modules[$i];
	$is_selected = $default_presentation_layer_name && isset($m["bean_name"]) && $default_presentation_layer_name == $m["bean_name"];
	
	$main_content .= '<option modules_id="layer_modules_' . $i . '"' . ($is_selected ? " selected" : "") . '>' . (isset($m["item_label"]) ? $m["item_label"] : "") . '</option>';
}

$main_content .= '		
		</select>
	</div>';

for ($i = 0; $i < $t; $i++) {
	$m = $modules[$i];
	$bean_name = isset($m["bean_name"]) ? $m["bean_name"] : null;
	$bean_file_name = isset($m["bean_file_name"]) ? $m["bean_file_name"] : null;
	$project_loaded_modules = isset($m["modules"]) ? $m["modules"] : null;
	$is_selected = $default_presentation_layer_name && $default_presentation_layer_name == $bean_name;
	
	$delete_module_url = $project_url_prefix . "phpframework/admin/manage_module?bean_name=$bean_name&bean_file_name=$bean_file_name&action=uninstall&module_id=#module_id#";
	$disable_module_url = $project_url_prefix . "phpframework/admin/manage_module?bean_name=$bean_name&bean_file_name=$bean_file_name&action=disable&module_id=#module_id#";
	$enable_module_url = $project_url_prefix . "phpframework/admin/manage_module?bean_name=$bean_name&bean_file_name=$bean_file_name&action=enable&module_id=#module_id#";

	$main_content .= '<div id="layer_modules_' . $i . '" class="layer_modules">';
	
	if ($is_install_module_allowed)
		$main_content .= '
	<div class="install">
		<button onClick="document.location=\'' . $project_url_prefix . 'phpframework/admin/install_module?bean_name=' . $bean_name . '&bean_file_name=' . $bean_file_name . ($is_selected ? '&filter_by_layout=' . $filter_by_layout : '') . ($popup ? "&popup=$popup" : '') . '\'">Install New Module</button>
	</div>';
	
	$main_content .= '
	<table>
		<thead>
			<tr>
				<th class="table_header group"></th>
				<th class="table_header status">Status</th>
				<th class="table_header photo">Photo</th>
				<th class="table_header label">Label</th>
				<th class="table_header module_id">Module ID</th>
				<th class="table_header description">Description</th>
				<th class="table_header buttons">
					<span class="icon disable" onClick="executeActionInAllModules(this, \'disable\')" title="Click here to disable all modules"></span>
					<span class="icon enable" onClick="executeActionInAllModules(this, \'enable\')" title="Click here to enable all modules"></span>
				</th>
			</tr>
		</thead>
		<tbody>';
	
	if (is_array($loaded_modules)) {
		foreach ($loaded_modules as $group_module_id => $loaded_modules_by_group) {
			$sub_main_content = "";
			
			foreach ($loaded_modules_by_group as $module_id => $loaded_module) 
				if (!empty($project_loaded_modules[$module_id])) { //only show if any, this is, if there is any module installed in the correspodent layer
					$enable = isset($project_loaded_modules[$module_id]["path"]) && CMSModuleEnableHandler::isModuleEnabled($project_loaded_modules[$module_id]["path"]);
				
					$admin_url = !empty($loaded_module["admin_path"]) ? $project_url_prefix . "phpframework/admin/module_admin?bean_name=$bean_name&bean_file_name=$bean_file_name" . ($is_selected ? '&filter_by_layout=' . $filter_by_layout : '') . "&group_module_id=$group_module_id" . ($popup ? "&popup=$popup" : '') : null;
					
					$image = ''; //'No Photo'
					
					if (!empty($loaded_module["images"][0]["url"])) {
						if (preg_match("/\.svg$/i", $loaded_module["images"][0]["url"]) && !empty($loaded_module["images"][0]["path"]) && file_exists($loaded_module["images"][0]["path"]))
							$image = file_get_contents($loaded_module["images"][0]["path"]);
						else
							$image = '<img src="' . $loaded_module["images"][0]["url"] . '" />';
					}
					
					$loaded_module_id = isset($loaded_module["id"]) ? $loaded_module["id"] : null;
					
					$sub_main_content .= '<tr class="group_module_item" group_module_id="' . $group_module_id . '">
						<td class="group"></td>
						<td class="status"><span class="icon ' . ($enable ? 'enable' : 'disable') . '" title="This module is currently ' . ($enable ? 'enabled' : 'disabled') . '"></span></td>
						<td class="photo">' . $image . '</td>
						<td class="label">' . (isset($loaded_module["label"]) ? $loaded_module["label"] : "") . '</td>
						<td class="module_id">' . $loaded_module_id . '</td>
						<td class="description">' . (isset($loaded_module["description"]) ? str_replace("\n", "<br>", $loaded_module["description"]) : "") . '</td>
						<td class="buttons">
							<span class="icon disable" ' . ($enable ? '' : 'style="display:none;"') . ' onClick="disableModule(this, \'' . str_replace("#module_id#", $loaded_module_id, $disable_module_url) . '\')" title="Click here to disable this module"></span>
							<span class="icon enable" ' . ($enable ? 'style="display:none;"' : '') . ' onClick="enableModule(this, \'' . str_replace("#module_id#", $loaded_module_id, $enable_module_url) . '\')" title="Click here to enable this module"></span>
							' . (empty($loaded_module["is_reserved_module"]) ? '<span class="icon delete" onClick="deleteModule(this, \'' . str_replace("#module_id#", $group_module_id, $delete_module_url) . '\', \'' . $module_id . '\', \'' . $group_module_id . '\')" title="Click here to delete this module permanently"></span>' : '') . '
							' . ($is_module_admin_allowed && $admin_url ? '<a href="' . $admin_url . '" class="icon settings" title="Go to this module\'s Admin Panel"></a>' : '') . '
						</td>
					</tr>';
				}
			
			//only show if any, this is, if there is any module installed in the correspodent layer
			if ($sub_main_content)
				$main_content .= '<tr module_id="' . $group_module_id . '">
					<td class="group" colspan="7">
						<label>' . $group_module_id . '</label>
						<span class="icon maximize" onClick="toggleGroupOfMopdules(this, \'' . $group_module_id . '\')" title="Toggle Group of Modules"></span>
					</td>
				</tr>' . $sub_main_content;
		}
	}
	
	$main_content .= '</tbody>
		</table>
	</div>';
}

$main_content .= '</div>';
?>
