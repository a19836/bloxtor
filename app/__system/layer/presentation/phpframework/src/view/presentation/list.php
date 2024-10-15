<?php
include_once $EVC->getUtilPath("AdminMenuUIHandler");

$is_module_user_installed = isset($is_module_user_installed) ? $is_module_user_installed : null;

$head = AdminMenuUIHandler::getHeader($project_url_prefix, $project_common_url_prefix);
$head .= '
<!-- Add Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/list.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/list.js"></script>';

$main_content = AdminMenuUIHandler::getContextMenus($exists_db_drivers, $get_store_programs_url, $is_module_user_installed);

if ($item_type == "presentation") {
	$et = $element_type ? (
		$element_type == "entity" ? "Pages" : (
			$element_type == "webroot" ? "Webroot Files" : (
				$element_type == "util" ? "Actions" : (
					substr($element_type, -1) == "y" ? ucfirst(substr($element_type, 0, -1)) . "ies" : ucfirst($element_type) . "s"
				)
			)
		)
	) : ucwords($item_type) . " Files";
}
else
	$et = ucwords($item_type) . " Files";

$list_type = in_array($element_type, array("entity", "view", "template", "view", "block")) ? "block_view" : "list_view";

$main_content .= '
	<div class="top_bar">
		<header>
			<div class="title">' . $et . ' List:
				<div class="list_type">
					<a href="javascript:void(0)" onClick="toggleListType(this, \'block_view\')" title="Show blocks view"><span class="icon block_view' . ($list_type == "block_view" ? " active" : "") . '"></span></a>
					<a href="javascript:void(0)" onClick="toggleListType(this, \'list_view\')" title="Show list view"><span class="icon list_view' . ($list_type == "list_view" ? " active" : "") . '"></span></a>
				</div>
				<select class="sort_files" onChange="sortFiles(this)">
					<option disabled>Sort by:</option>
					<option value="a_z" selected>A to Z</option>
					<option value="z_a">Z to A</option>
				</select>
				<span class="search_file">
					<i class="icon search active"></i>
					<input placeHolder="Search" onKeyUp="searchFiles(this)" />
					<i class="icon close" onClick="resetSearchFiles(this)"></i>
				</span>';

if ($element_type == "entity")
	$main_content .= '<a class="sub_title" href="javascript:void(0)" onClick="createPage(this)">Add Page</a>';
else if ($element_type == "template")
	$main_content .= '<a class="sub_title" href="' . $project_url_prefix . 'phpframework/presentation/install_template?bean_name=' . $bean_name . '&bean_file_name=' . $bean_file_name . '&path=' . $path . '/src/template/">Install new Template</a>';

$main_content .= '</div>
		</header>
	</div>';

$main_content .= '
<div id="file_tree" class="mytree hidden ' . $list_type . ($element_type ? " list_$element_type" : "") . ($path ? ' mytree_filtered' : '') . '">
	<ul>';
	
	$main_layers_properties = array();
	
	if (!empty($layers))
		foreach ($layers as $layer_name => $layer) {
			$main_content .= AdminMenuUIHandler::getLayer($layer_name, $layer, $main_layers_properties, $project_url_prefix, $filter_by_layout, $filter_by_layout_permission, $selected_db_driver);
			
			if ($item_type == "presentation" && $element_type) {
				$properties = isset($main_layers_properties[$layer_name]) ? $main_layers_properties[$layer_name] : null;
				$bean_file_name = isset($properties["bean_file_name"]) ? $properties["bean_file_name"] : null;
				$bean_name = isset($properties["bean_name"]) ? $properties["bean_name"] : null;
				
				$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
				$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
				$main_layers_properties[$layer_name]["prefix_path"] = CMSPresentationLayerHandler::getPresentationLayerPrefixPath($obj, $element_type);
				//echo "<pre>";print_r($main_layers_properties[$layer_name]);die();
			}
		}
	
	$main_content .= '
	</ul>
</div>';

if (empty($layers))
	$main_content .= '<div class="error">There are no files!</div>';
	
$main_content .= '
<script>
	var element_type = "' . $element_type . '";
	var item_type = "' . $item_type . '";
	var path_to_filter = "' . $path . '";
	var inline_icons_by_context_menus = ' . json_encode(AdminMenuUIHandler::getInlineIconsByContextMenus()) . ';
	
	main_layers_properties = ' . (isset($main_layers_properties) ? json_encode($main_layers_properties) : "null") . '; //this var is already created in the filemanage.js
</script>

<div class="myfancypopup auxiliar_popup with_iframe_title">
	<iframe></iframe>
</div>';
?>
