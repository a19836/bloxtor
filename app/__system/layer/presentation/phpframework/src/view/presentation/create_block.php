<?php
include_once $EVC->getUtilPath("BreadCrumbsUIHandler");

$file_path = isset($file_path) ? $file_path : null;
$P = isset($P) ? $P : null;

//prepare some default configurations. This configurations can be changed from the create_page_module_block too.
$query_string = isset($_SERVER["QUERY_STRING"]) ? str_replace(array("&edit_block_type=advanced", "&edit_block_type=simple"), "", $_SERVER["QUERY_STRING"]) : null;
$title = isset($title) ? $title : "Create Block in " . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($file_path, $P, true);
$title_icons = isset($title_icons) ? $title_icons : '<li class="show_advanced_ui" data-title="Switch to Code Workspace"><a class="update" href="' . $project_url_prefix . 'phpframework/presentation/edit_block?' . $query_string . '&edit_block_type=advanced"><i class="icon show_advanced_ui"></i> Switch to Code Workspace</a></li>';
$add_block_url = !empty($add_block_url) ? $add_block_url : $project_url_prefix . "phpframework/presentation/edit_block?bean_name=$bean_name&bean_file_name=$bean_file_name&filter_by_layout=$filter_by_layout&path=$path&module_id=#module_id#";

$head = '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icons CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/create_block.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/create_block.js"></script>

<script>
var add_block_url = "' . $add_block_url . '";
</script>';

$main_content = '
	<div class="top_bar' . ($popup ? " in_popup" : "") . '">
		<header>
			<div class="title" title="' . $path . '">' . $title . '</div>
			<ul>
				' . $title_icons . '
			</ul>
		</header>
	</div>

<div class="modules_list">
	<table>
		<tr>
			<th class="table_header group"></th>
			<th class="table_header photo">Photo</th>
			<th class="table_header label">Label</th>
			<th class="table_header module_id">Module ID</th>
			<th class="table_header description">Description</th>
			<th class="table_header buttons"></th>
		</tr>';

if (!empty($loaded_modules))
	foreach ($loaded_modules as $group_module_id => $loaded_modules_by_group) {
		$main_content .= '<tr>
			<td class="group" colspan="6">
				<label>' . $group_module_id . '</label>
				<span class="icon maximize" onClick="toggleGroupOfMopdules(this, \'' . $group_module_id . '\')" title="Toggle Group of Modules">Toggle Group of Modules</span>
			</td>
		</tr>';
		
		foreach ($loaded_modules_by_group as $module_id => $loaded_module) {
			$image = ''; //'No Photo'
			
			if (!empty($loaded_module["images"][0]["url"])) {
				if (preg_match("/\.svg$/i", $loaded_module["images"][0]["url"]) && !empty($loaded_module["images"][0]["path"]) && file_exists($loaded_module["images"][0]["path"]))
					$image = file_get_contents($loaded_module["images"][0]["path"]);
				else
					$image = '<img src="' . $loaded_module["images"][0]["url"] . '" />';
			}
			
			$loaded_module_id = isset($loaded_module["id"]) ? $loaded_module["id"] : null;
			
			$main_content .= '<tr class="group_module_item" group_module_id="' . $group_module_id . '">
				<td class="group"></td>
				<td class="photo">' . $image . '</td>
				<td class="label">' . (isset($loaded_module["label"]) ? $loaded_module["label"] : "") . '</td>
				<td class="module_id">' . $loaded_module_id . '</td>
				<td class="description">' . (isset($loaded_module["description"]) ? $loaded_module["description"] : "") . '</td>
				<td class="buttons">
					<span class="icon add" onClick="addBlock(this, \'' . $loaded_module_id . '\')" title="Click here to add a new block based in this module: \'' . $loaded_module["label"] . '\'">Add New Block</span>
				</td>
			</tr>';
		}
	}

$main_content .= '</table>
</div>';
?>
