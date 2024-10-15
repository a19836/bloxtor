<?php
include_once get_lib("org.phpframework.phpscript.PHPUICodeExpressionHandler");
include_once $EVC->getUtilPath("WorkFlowPresentationHandler");
include_once $EVC->getUtilPath("CMSPresentationLayerJoinPointsUIHandler");
include_once $EVC->getUtilPath("WorkFlowUIHandler");

class CMSPresentationLayerUIHandler {
	
	public static function getHeader($project_url_prefix, $project_common_url_prefix, $get_available_blocks_list_url, $get_block_params_url, $create_entity_code_url, $available_blocks_list, $regions_blocks_list, $block_params_values_list, $includes_list, $blocks_join_points, $template_params_values_list, $selected_project_id, $with_layout_ui_editor = false, $previous_html = "", $defined_regions_list = null, $defined_template_params_values = null) {
		$html = '';
		
		if (strpos($previous_html, 'vendor/phpjs/functions/strings/parse_str.js') === false)
			$html .= '
			<!-- Add PHPJS functions -->
			<script type="text/javascript" src="' . $project_common_url_prefix . 'vendor/phpjs/functions/strings/parse_str.js"></script>';
		
		if (strpos($previous_html, 'vendor/phpjs/functions/strings/stripslashes.js') === false)
			$html .= '
			<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/phpjs/functions/strings/stripslashes.js"></script>
			<script type="text/javascript" src="' . $project_common_url_prefix . 'vendor/phpjs/functions/strings/addcslashes.js"></script>';
		
		if (strpos($previous_html, 'vendor/jquery/js/jquery.md5.js') === false)
			$html .= '
			<!-- Add MD5 JS Files -->
			<script type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquery/js/jquery.md5.js"></script>';
		
		if (strpos($previous_html, 'lib/jquerylayoutuieditor/vendor/jqueryuidroppableiframe/js/jquery-ui-droppable-iframe-fix.js') === false)
			$html .= '
			<!-- Add Droppable Iframe Js - to be used by the .tab_content_template_layout  -->
			<script type="text/javascript" src="' . $project_url_prefix . 'lib/jquerylayoutuieditor/vendor/jqueryuidroppableiframe/js/jquery-ui-droppable-iframe-fix.js"></script>';
		
		$html .= '
		<script>
		var get_available_blocks_list_url = \'' . $get_available_blocks_list_url . '\';
		var get_block_params_url = \'' . $get_block_params_url . '\';
		var create_entity_code_url = \'' . $create_entity_code_url . '\';

		var region_block_html = \'' . addcslashes(str_replace("\n", "", self::getRegionBlockHtml("#region#", "#block#", null, false, $available_blocks_list, null, null, "#rb_index#")), "\\'") . '\';
		var defined_region_html = \'' . addcslashes(str_replace("\n", "", self::getDefinedRegionHtml("#region#")), "\\'") . '\';
		var block_param_html = \'' . addcslashes(str_replace("\n", "", self::getBlockParamHtml("#param#", "#value#")), "\\'") . '\';
		var include_html = \'' . addcslashes(str_replace("\n", "", self::getIncludeHtml("#path#", false)), "\\'") . '\';
		var template_param_html = \'' . addcslashes(str_replace("\n", "", self::getTemplateParamHtml("#param#", "#value#")), "\\'") . '\';
		var defined_template_param_html = \'' . addcslashes(str_replace("\n", "", self::getDefinedTemplateParamHtml("#param#")), "\\'") . '\';
		
		var regions_blocks_list = ' . json_encode($regions_blocks_list) . ';
		var defined_regions_list = ' . json_encode($defined_regions_list) . ';
		var available_blocks_list = ' . json_encode($available_blocks_list) . ';
		var block_params_values_list = ' . json_encode($block_params_values_list) . ';
		var template_params_values_list = ' . json_encode($template_params_values_list) . ';
		var defined_template_params_values = ' . json_encode($defined_template_params_values) . ';
		var includes_list = ' . json_encode($includes_list) . ';
		
		var selected_project_id = \'' . $selected_project_id . '\';
		</script>';
		
		$html .= CMSPresentationLayerJoinPointsUIHandler::getHeader();
		$html .= CMSPresentationLayerJoinPointsUIHandler::getRegionBlocksJoinPointsJavascriptObjs($blocks_join_points);
		
		if ($with_layout_ui_editor)
			$html .= '
<!-- Layout UI Editor - Color -->
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'js/color.js"></script>

<!-- Add Jquery Tap-Hold Event JS file -->
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquerytaphold/taphold.js"></script>

<!-- Jquery Touch Punch to work on mobile devices with touch -->
<script type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jqueryuitouchpunch/jquery.ui.touch-punch.min.js"></script>

<!-- Layout UI Editor - Add ACE-Editor -->
<script type="text/javascript" src="' . $project_common_url_prefix . 'vendor/acecodeeditor/src-min-noconflict/ace.js"></script>
<script type="text/javascript" src="' . $project_common_url_prefix . 'vendor/acecodeeditor/src-min-noconflict/ext-language_tools.js"></script>

<!-- Layout UI Editor - Add Code Beautifier -->
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/mycodebeautifier/js/MyCodeBeautifier.js"></script>

<!-- Layout UI Editor - Add Html/CSS/JS Beautify code -->
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jsbeautify/js/lib/beautify.js"></script>
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jsbeautify/js/lib/beautify-css.js"></script>
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'lib/myhtmlbeautify/MyHtmlBeautify.js"></script>

<!-- Add Auto complete -->
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/myautocomplete/js/MyAutoComplete.js"></script>
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/myautocomplete/css/style.css">

<!-- Layout UI Editor - Html Entities Converter -->
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/he/he.js"></script>

<!-- Layout UI Editor - Material-design-iconic-font -->
<link rel="stylesheet" href="' . $project_url_prefix . 'lib/jquerylayoutuieditor/vendor/materialdesigniconicfont/css/material-design-iconic-font.min.css">

<!-- Layout UI Editor - JQuery Nestable2 -->
<link rel="stylesheet" href="' . $project_url_prefix . 'lib/jquerylayoutuieditor/vendor/nestable2/jquery.nestable.min.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'lib/jquerylayoutuieditor/vendor/nestable2/jquery.nestable.min.js"></script>

<!-- Layout UI Editor - HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
<!--[if lt IE 9]>
	 <script src="' . $project_url_prefix . 'lib/jquerylayoutuieditor/vendor/jqueryuidroppableiframe/js/html5_ie8/html5shiv.min.js"></script>
	 <script src="' . $project_url_prefix . 'lib/jquerylayoutuieditor/vendor/jqueryuidroppableiframe/js/html5_ie8/respond.min.js"></script>
<![endif]-->

<!-- Layout UI Editor - Add Iframe droppable fix - IE10 viewport hack for Surface/desktop Windows 8 bug -->
<script src="' . $project_url_prefix . 'lib/jquerylayoutuieditor/vendor/jqueryuidroppableiframe/js/ie10-viewport-bug-workaround.js"></script>

<!-- Layout UI Editor - Add Layout UI Editor -->
<link rel="stylesheet" href="' . $project_url_prefix . 'lib/jquerylayoutuieditor/css/some_bootstrap_style.css" type="text/css" charset="utf-8" />
<link rel="stylesheet" href="' . $project_url_prefix . 'lib/jquerylayoutuieditor/css/style.css" type="text/css" charset="utf-8" />
<link rel="stylesheet" href="' . $project_url_prefix . 'lib/jquerylayoutuieditor/css/widget_resource.css" type="text/css" charset="utf-8" />

<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'lib/jquerylayoutuieditor/js/TextSelection.js"></script>
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'lib/jquerylayoutuieditor/js/LayoutUIEditor.js"></script>
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'lib/jquerylayoutuieditor/js/CreateWidgetContainerClassObj.js"></script>
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'lib/jquerylayoutuieditor/js/LayoutUIEditorFormField.js"></script>
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'lib/jquerylayoutuieditor/js/LayoutUIEditorWidgetResource.js"></script>

<!-- Layout UI Editor - Add Layout UI Editor Widget Resource Options/Handlers -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout_ui_editor_widget_resource_options.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/layout_ui_editor_widget_resource_options.js"></script>
';
		
		return $html;
	}
	
	public static function getChoosePresentationIncludeFromFileManagerPopupHtml($bean_name, $bean_file_name, $choose_bean_layer_files_from_file_manager_url, $choose_dao_files_from_file_manager_url, $choose_lib_files_from_file_manager_url, $choose_vendor_files_from_file_manager_url, $presentation_brokers, $my_fancy_popup_obj = "MyFancyPopup") {
		$bean_label = !empty($presentation_brokers[0][0]) ? $presentation_brokers[0][0] : $bean_name; //get broker name from current presentation layer
		
		return '<div id="choose_presentation_include_from_file_manager" class="myfancypopup choose_from_file_manager with_title">
			<div class="title">Choose a File</div>
			<ul class="mytree">
				<li>
					<label>' . $bean_label . '</label>
					<ul url="' . str_replace("#bean_file_name#", $bean_file_name, str_replace("#bean_name#", $bean_name, $choose_bean_layer_files_from_file_manager_url)) . '"></ul>
				</li>
				<!--li>
					<label>DAO</label>
					<ul url="' . $choose_dao_files_from_file_manager_url . '"></ul>
				</li-->
				<li>
					<label>LIB</label>
					<ul url="' . $choose_lib_files_from_file_manager_url . '"></ul>
				</li>
				<li>
					<label>VENDOR</label>
					<ul url="' . $choose_vendor_files_from_file_manager_url . '"></ul>
				</li>
			</ul>
			<div class="button">
				<input type="button" value="Update" onClick="' . $my_fancy_popup_obj . '.settings.updateFunction(this)" />
			</div>
		</div>';
	}
	
	public static function getTemplateRegionBlockHtmlEditorPopupHtml($ui_menu_widgets_html) {
		$reverse_class = isset($_COOKIE["main_navigator_side"]) && $_COOKIE["main_navigator_side"] == "main_navigator_reverse" ? "" : "reverse";
		
		return '<div class="template_region_block_html_editor_popup myfancypopup">
			<div class="layout-ui-editor ' . $reverse_class . ' fixed-side-properties hide-template-widgets-options">
				<ul class="menu-widgets hidden">
					' . $ui_menu_widgets_html . '
				</ul>
			</div>
		</div>';
	}
	
	public static function getRegionsBlocksAndIncludesHtml($selected_template, $available_regions_list, $regions_blocks_list, $available_blocks_list, $available_block_params_list, $block_params_values_list, $includes, $available_params_list, $template_params_values_list, $defined_regions_list = null, $defined_template_params_values = null) {
		$html = '
			<div class="region_blocks">
				<label>Selected Template Regions:</label>
			
				<div class="template_region_items">';
		
		//echo "<pre>";print_r($available_regions_list);print_r($regions_blocks_list);die();
		$existent_region_blocks = array();
		
		if ($selected_template) {
			if ($available_regions_list) {
				$art = count($available_regions_list);
				$rbt = count($regions_blocks_list);
				$rb_indexes = array();
				
				for ($i = 0; $i < $art; $i++) {
					$region = $available_regions_list[$i];
					
					for ($j = 0; $j < $rbt; $j++) {
						$rbl = $regions_blocks_list[$j];
						$rbl_region = isset($rbl[0]) ? $rbl[0] : null;
						
						if ($rbl_region == $region) {
							$block = isset($rbl[1]) ? $rbl[1] : null;
							$proj = isset($rbl[2]) ? $rbl[2] : null;
							$type = isset($rbl[3]) ? $rbl[3] : null;
							$block_hash = $type == 1 ? md5($block) : ($type == 2 || $type == 3 ? "block_" : "view_") . $block; //if html set md5
							
							$existent_region_blocks[$region][ $block_hash ][ $proj ] = true;
							
							if (isset($rb_indexes["$region-$block_hash-$proj"]))
								$rb_indexes["$region-$block_hash-$proj"]++;
							else
								$rb_indexes["$region-$block_hash-$proj"] = 0;
							
							$html .= self::getRegionBlockHtml($region, $block, $proj, $type, $available_blocks_list, $available_block_params_list, $block_params_values_list, $rb_indexes["$region-$block_hash-$proj"]);
						}
					}
					
					if (empty($existent_region_blocks[$region]))
						$html .= self::getRegionBlockHtml($region, null, null, false, $available_blocks_list, $available_block_params_list, null, 0);
				}
			}
		}
	
		$html .= '</div>
				<div class="no_items' . ($selected_template && $available_regions_list ? ' hidden' : '') . '">There are no regions in this template</div>
			</div>
		
			<div class="other_region_blocks">
				<label>Extra Regions:</label>
				<span class="icon add" onClick="addOtherRegionBlock(this)" title="Add">Add</span>
			
				<div class="template_region_items">';
	
		$exists = false;
		
		if ($regions_blocks_list) {
			$t = count($regions_blocks_list);
			$rb_indexes = array();
			
			for ($i = 0; $i < $t; $i++) {
				$rbl = $regions_blocks_list[$i];
				
				$region = isset($rbl[0]) ? $rbl[0] : null;
				$block = isset($rbl[1]) ? $rbl[1] : null;
				$proj = isset($rbl[2]) ? $rbl[2] : null;
				$type = isset($rbl[3]) ? $rbl[3] : null;
				$block_hash = $type == 1 ? md5($block) : ($type == 2 || $type == 3 ? "block_" : "view_") . $block; //if html set md5
				
				if (!isset($existent_region_blocks[$region][$block_hash][$proj])) {
					if (isset($rb_indexes["$region-$block_hash-$proj"]))
						$rb_indexes["$region-$block_hash-$proj"]++;
					else
						$rb_indexes["$region-$block_hash-$proj"] = 0;
						
					$html .= self::getRegionBlockHtml($region, $block, $proj, $type, $available_blocks_list, $available_block_params_list, $block_params_values_list, $rb_indexes["$region-$block_hash-$proj"]);
					$exists = true;
				}
			}
		}
		
		$html .= '	</div>
				<div class="no_items' . ($exists ? ' hidden' : '') . '">There are no extra regions in this file</div>
			</div>
			
			<div class="defined_regions">
				<label>Defined Regions in the Template:</label>
			
				<div class="template_region_items">';
	
		$exists = false;
		
		if ($defined_regions_list) {
			$t = count($defined_regions_list);
			
			for ($i = 0; $i < $t; $i++) {
				$region = $defined_regions_list[$i];
				
				$html .= self::getDefinedRegionHtml($region);
				$exists = true;
			}
		}
		
		$html .= '</div>
				<div class="no_items' . ($exists ? ' hidden' : '') . '">There are no defined regions in the template</div>
			</div>
		
			<div class="includes">
				<label>Includes:</label>
				<span class="icon add" onClick="addInclude(this)" title="Add">Add</span>
			
				<div class="items">';
		
		if ($includes) {
			$t = count($includes);
			for ($i = 0; $i < $t; $i++) {
				$include = $includes[$i];
				$include_path = isset($include["path"]) ? $include["path"] : null;
				$include_path_type = isset($include["path_type"]) ? $include["path_type"] : null;
				$include_once = isset($include["once"]) ? $include["once"] : null;
				
				$inc_path = PHPUICodeExpressionHandler::getArgumentCode($include_path, $include_path_type);
			
				$html .= self::getIncludeHtml($inc_path, $include_once);
			}
		}
		
		$html .= '	</div>
				<div class="no_items' . ($includes ? ' hidden' : '') . '">There are no includes in this file</div>
			</div>
			
			<div class="template_params">
				<label>Selected Template Params:</label>
			
				<div class="items">';
		
		$existent_template_params = array();
		
		if ($selected_template) {
			if ($available_params_list) {
				$t = count($available_params_list);
				for ($i = 0; $i < $t; $i++) {
					$param = $available_params_list[$i];
					
					if ($param && !isset($existent_template_params[$param])) {
						$param_value = isset($template_params_values_list[$param]) ? $template_params_values_list[$param] : null;
						$default_value = isset($defined_template_params_values[$param]) ? $defined_template_params_values[$param] : null;
						$html .= self::getTemplateParamHtml($param, $param_value, $default_value);
					}
					
					$existent_template_params[$param] = true;
				}
			}
			
			if ($defined_template_params_values) {
				foreach ($defined_template_params_values as $param => $default_value) {
					if ($param && !isset($existent_template_params[$param])) {
						$param_value = isset($template_params_values_list[$param]) ? $template_params_values_list[$param] : null;
						$html .= self::getTemplateParamHtml($param, $param_value, $default_value);
						$existent_template_params[$param] = true;
					}
				}
			}
		}
	
		$html .= '</div>
				<div class="no_items' . ($selected_template && $available_params_list ? ' hidden' : '') . '">There are no params in this template</div>
			</div>
		
			<div class="other_template_params">
				<label>Extra Params:</label>
				<span class="icon add" onClick="addOtherTemplateParam(this)" title="Add">Add</span>
			
				<div class="items">';
	
		$exists = false;
		foreach ($template_params_values_list as $param => $param_value) {
			if ($param && !isset($existent_template_params[$param])) {
				$html .= self::getTemplateParamHtml($param, isset($param_value) ? $param_value : "");
				$exists = true;
			}
		}
	
		$html .= '	</div>
				<div class="no_items' . ($exists ? ' hidden' : '') . '">There are no extra params in this file</div>
			</div>
		
			<div class="defined_template_params">
				<label>Defined Params in the Template:</label>
			
				<div class="items">';
	
		$exists = false;
		foreach ($existent_template_params as $param => $param_value)
			if ($param) {
				$html .= self::getDefinedTemplateParamHtml($param);
				$exists = true;
			}
	
		$html .= '	</div>
				<div class="no_items' . ($exists ? ' hidden' : '') . '">There are no defined params in the template</div>
			</div>';
	
		return $html;
	}
	
	public static function getDefinedRegionHtml($region) {
		$r = substr($region, 0, 1) == '"' ? str_replace('"', '', $region) : $region;
		
		return '<div class="template_region_item">' . $r . '</div>';
	}
	
	public static function getRegionBlockHtml($region, $selected_block, $selected_block_project, $selected_block_type, $available_blocks_list, $available_block_params_list = array(), $block_params_values_list = array(), $rb_index = 0) {
		$available_blocks_list = is_array($available_blocks_list) ? $available_blocks_list : array();
		$block_params = $block_params_values = array();
		$is_html = $selected_block_type == 1;
		$is_block = $selected_block_type == 2 || $selected_block_type == 3;
		$is_view = $selected_block_type == 4 || $selected_block_type == 5;
		$is_text = $is_variable = $is_input = $is_string = $is_code = $is_options = $sb = $sbp = null;
		
		if (!$is_block && !$is_html && !$is_view)
			$is_block = true;
		
		if ($is_html) {
			$type = PHPUICodeExpressionHandler::getValueType($selected_block, array("empty_string_type" => "string", "non_set_type" => "string"));
			$html = PHPUICodeExpressionHandler::getArgumentCode($selected_block, $type);
			$class = ' is_html has_edit';
		}
		else { //if block or view
			$sb = substr($selected_block, 0, 1) == '"' ? str_replace('"', '', $selected_block) : $selected_block;
			$sbp = substr($selected_block_project, 0, 1) == '"' ? str_replace('"', '', $selected_block_project) : $selected_block_project;
			$exists = false;
			
			if ($is_block) {
				$is_sb_html_or_text = strpos($sb, "\n") || strip_tags($sb) != $sb;
				$apbl = isset($available_blocks_list[$sbp]) ? $available_blocks_list[$sbp] : null;
				$exists = empty($sb) || ($apbl && !$is_sb_html_or_text && in_array($sb, $apbl));
				
				$block_params = isset($available_block_params_list[$region][$selected_block]) ? $available_block_params_list[$region][$selected_block] : null;
				$block_params_values = isset($block_params_values_list[$region][$selected_block][$rb_index]) ? $block_params_values_list[$region][$selected_block][$rb_index] : null;
			}
			
			$is_text = strpos($selected_block, "\n") !== false; //if is textarea
			$is_variable = strpos($selected_block, "\n") === false && (substr($selected_block, 0, 1) == '$' || substr($selected_block, 0, 2) == '@$') && strpos($selected_block, "->") === false;
			$is_input = !$is_variable && !$is_text && strlen($selected_block);
			$is_string = $is_input && substr($selected_block, 0, 1) == '"';
			$is_code = $is_input && !$is_string;
			
			$is_options = ($exists && !$is_code) || !strlen($selected_block);
			
			if ($is_options)
				$is_text = $is_variable = $is_input = $is_string = $is_code = false;
			
			$class = $is_input || $is_variable ? ' is_input' : ($is_text ? ' is_text' : '');
			$class .= $selected_block && ($is_options || $is_string) ? ' has_edit' : '';
			//echo "$selected_block ($sbp|$sb): is_input:$is_input; is_string:$is_string; is_options: $is_options; exists:$exists\n<br>";
			//echo "<pre>";print_r($available_blocks_list[$sbp]);echo "</pre>";
		}
		
		$r = substr($region, 0, 1) == '"' ? str_replace('"', '', $region) : $region;
		$region = str_replace('"', "&quot;", $region);
		
		$html = '<div class="template_region_item' . $class . '" rb_index="' . $rb_index . '">
			<span class="icon info invisible" onClick="openTemplateRegionInfoPopup(this)" title="View region samples">View region samples</span>
			<label title="' . $r . '">' . $r . ':</label>
			<input class="region" type="hidden" value="' . $region . '" />
			<select class="type" onChange="onChangeTemplateRegionItemType(this)">
				<option value="1"' . ($is_html ? ' selected' : '') . '>Html</option>
				<option value="2"' . ($is_block ? ' selected' : '') . '>Module</option>
				<option value="4"' . ($is_view ? ' selected' : '') . '>View</option>
			</select>
			<select class="block_options ' . ($is_options ? '' : ' hidden') . '" onChange="onChangeRegionBlock(this)">
				<option class="loading" value="-1" disabled>Loading...</option>
				<option value=""></option>';
		
		if ($available_blocks_list)
			foreach ($available_blocks_list as $project_name => $blocks) {
				$html .= '<optgroup label="' . $project_name . '">';
				
				if ($blocks) {
					$t = count($blocks);
					for ($i = 0; $i < $t; $i++) {
						$block = $blocks[$i];
						$is_block_html_or_text = strpos($block, "\n") || strip_tags($block) != $block;
						
						if (!$is_block_html_or_text)
							$html .= '<option value="' . $block . '"' . ($is_block && $block == $sb && $project_name == $sbp ? ' selected' : '') . ' project="' . $project_name . '">' . $block . '</option>';
					}
				}
				
				$html .= '</optgroup>';
			}
		
		//only add block is not a text otherwise the html will be messy
		$html .= '</select>
			<input class="block' . ($is_input || $is_variable ? '' : ' hidden') . '" type="text" value="' . (!$is_text ? $sb : '') . '" onBlur="onBlurRegionBlock(this)" />
			
			<select class="region_block_type invisible' . ($is_html ? ' hidden' : '') . '" onChange="onChangeRegionBlockType(this)">
				<option value=""' . ($is_input && !$is_string ? ' selected' : '') . '>default</option>
				<option' . ($is_string ? ' selected' : '') . '>string</option>
				<option' . ($is_text ? ' selected' : '') . '>text</option>
				<option' . ($is_variable ? ' selected' : '') . '>variable</option>
				<option' . ($is_options ? ' selected' : '') . '>options</option>
			</select>
			
			<span class="icon delete invisible" onClick="deleteRegionBlock(this)" title="Remove this region-block">Remove</span>
			<span class="icon add invisible" onClick="addRepeatedRegionBlock(this)" title="Add new block for region: ' . $r . '">Add</span>
			<span class="icon up invisible" onClick="moveUpRegionBlock(this)" title="Move up this region-block">Move up</span>
			<span class="icon down invisible" onClick="moveDownRegionBlock(this)" title="Move down this region-block">Move down</span>
			<span class="icon edit invisible" onClick="editRegionBlock(this)" title="Edit this block">Edit</span>
			';
		
		//htmlspecialchars on textarea bc if ther is another textarea inside, the html doesn't get broken.
		$html .= '
			<div class="block_text' . (!$is_text ? ' hidden' : '') . '"><textarea onBlur="onBlurRegionBlock(this)">' . htmlspecialchars(stripslashes(substr($selected_block, 0, 1) == '"' && substr($selected_block, -1) == '"' ? substr($selected_block, 1, -1) : $selected_block)) . '</textarea></div>
			
			<div class="block_html editor' . (!$is_html ? ' hidden' : '') . '"><textarea onBlur="onBlurRegionBlock(this)">' . ($is_html ? htmlspecialchars($selected_block) : "") . '</textarea></div>';
		
		$html .= '<div class="block_params' . (!$is_block ? ' hidden' : '') . '">';
		
		if ($block_params) {
			$t = count($block_params);
			for ($i = 0; $i < $t; $i++) {
				$p = $block_params[$i];
			
				$html .= self::getBlockParamHtml($p, isset($block_params_values[$p]) ? $block_params_values[$p] : null);
			}
		}
		
		$html .= '</div>
		</div>';
		
		return $html;
	}

	public static function getBlockParamHtml($param, $value) {
		$p = substr($param, 0, 1) == '"' ? str_replace('\\"', '"', substr($param, 1, -1)) : $param;
		$v = substr($value, 0, 1) == '"' ? str_replace('\\"', '"', substr($value, 1, -1)) : $value;
	
		$param = str_replace('"', "&quot;", $param);
	
		return '<div class="block_param" param="' . $param . '">
			<label title="' . str_replace('"', "&quot;", $p) . '">' . $p . ':</label>
			<input class="block_param_name" type="hidden" value="' . $param . '" />
			<input class="block_param_value' . (strpos($value, "\n") !== false ? ' hidden' : '') . '" type="text" value="' . str_replace('"', "&quot;", $v) . '" onBlur="onBlurRegionBlockParam(this)" />
			<select onChange="onChangeRegionBlockParamType(this)">
				<option value="">default</option>
				<option' . (strpos($value, "\n") === false && (substr($value, 0, 1) == '"' || !strlen($value)) ? ' selected' : '') . '>string</option>
				<option' . (strpos($value, "\n") !== false ? ' selected' : '') . '>text</option>
				<option' . (strpos($value, "\n") === false && (substr($value, 0, 1) == '$' || substr($value, 0, 2) == '@$') && strpos($value, "->") === false ? ' selected' : '') . '>variable</option>
			</select>
			<span class="icon search search_page" onclick="onPresentationIncludePageUrlTaskChooseFile(this)" title="Choose a page url">Search Page</span>
			<span class="icon search search_image" onclick="onPresentationIncludeImageUrlTaskChooseFile(this)" title="Choose an image url">Search Image</span>
			<span class="icon add_variable search_variable" onclick="onPresentationProgrammingTaskChooseCreatedVariable(this)" title="Choose a variable">Search Variable</span>
			<div class="block_param_text' . (strpos($value, "\n") === false ? ' hidden' : '') . '"><textarea onBlur="onBlurRegionBlockParam(this)">' . htmlspecialchars($v, ENT_NOQUOTES) . '</textarea></div>
		</div>';
	}

	public static function getIncludeHtml($inc_path, $inc_once) {
		$ip = substr($inc_path, 0, 1) == '"' ? str_replace('\\"', '"', substr($inc_path, 1, -1)) : $inc_path;
	
		return '<div class="item">
			<input class="path" type="text" value="' . str_replace('"', "&quot;", $ip) . '" onBlur="onBlurInclude(this)" />
			<select onchange="onChangeIncludeType(this)">
				<option value="">default</option>
				<option' . (substr($inc_path, 0, 1) == '"' || !strlen($inc_path) ? ' selected' : '') . '>string</option>
				<option' . ((substr($inc_path, 0, 1) == '$' || substr($inc_path, 0, 2) == '@$') && strpos($inc_path, "->") === false ? ' selected' : '') . '>variable</option>
			</select>
			<input class="once" type="checkbox" value="1"' . ($inc_once ? ' checked' : '') . ' title="Check here to active the include ONCE feature" onchange="onChangeIncludeOnce(this)" />
			<span class="icon search" onClick="onPresentationIncludeTaskChoosePage(this)" title="Choose a file to include">Search</span>
			<span class="icon delete" onClick="removeInclude(this)">Remove</span>
		</div>';
	}
	
	public static function getDefinedTemplateParamHtml($param) {
		$p = substr($param, 0, 1) == '"' ? str_replace('\\"', '"', substr($param, 1, -1)) : $param;
		
		return '<div class="item">' . $p . '</div>';
	}
	
	public static function getTemplateParamHtml($param, $value, $default_value = null) {
		$is_inactive = !isset($value);
		$default_value_exists = isset($default_value);
		//echo "$param, $value, $default_value, $is_inactive, $default_value_exists<br/>\n";
		
		$p = substr($param, 0, 1) == '"' ? str_replace('\\"', '"', substr($param, 1, -1)) : $param;
		$v = substr($value, 0, 1) == '"' ? str_replace('\\"', '"', substr($value, 1, -1)) : $value;
		$probably_a_bool = preg_match("/^(is_|are_)/", $p);
		
		//convert to boolean
		if ($value === '"1"' || $value === "1" || $value === 1 || $value === "true")
			$value = true;
		else if ($value === '"0"' || $value === "0" || $value === 0 || $value === "false" || (!strlen($v) && $probably_a_bool))
			$value = false;
		
		$is_bool = is_bool($value);
		$is_text = !$is_bool && strpos($value, "\n") !== false;
		$param = str_replace('"', "&quot;", $param);
		
		if ($is_bool)
			$v = $value ? 1 : 0;
		
		if ($probably_a_bool)
			$p = ucwords(str_replace("_", " ", $p));
		
		return '<div class="item' . ($is_inactive ? " inactive" : "") . ($is_bool ? " boolean" : "") . '">
			<input class="template_param_active" type="checkbox" onChange="onActivateTemplateParam(this)" ' . ($is_inactive ? "" : "checked") . '/>
			<label title="' . str_replace('"', "&quot;", $p) . '">' . $p . ($probably_a_bool ? "?" : ":") . '</label>
			<input class="template_param_name" type="hidden" value="' . $param . '"' . ($is_inactive ? " disabled" : "") . ' />
			<input class="template_param_value' . ($is_text ? ' hidden' : '') . '" type="' . ($is_bool ? "checkbox" : "text") . '" value="' . str_replace('"', "&quot;", $v) . '" ' . ($is_bool ? "onChange" : "onBlur") . '="onBlurTemplateParam(this)"' . ($is_inactive ? " disabled" : "") . ($is_bool && $value ? " checked" : "") . ($default_value_exists ? ' placeHolder="' . str_replace('"', "&quot;", $default_value) . '"' : "") . ' />
			<select onChange="onChangeTemplateParamType(this)"' . ($is_inactive ? " disabled" : "") . '>
				<option value="">default</option>
				<option' . (!$is_text && (substr($value, 0, 1) == '"' || !strlen($v)) ? ' selected' : '') . '>string</option>
				<option' . ($is_text ? ' selected' : '') . '>text</option>
				<option' . (!$is_text && (substr($value, 0, 1) == '$' || substr($value, 0, 2) == '@$') && strpos($value, "->") === false ? ' selected' : '') . '>variable</option>
				<option' . ($is_bool ? ' selected' : '') . '>boolean</option>
			</select>
			<span class="icon search search_page" onclick="onPresentationIncludePageUrlTaskChooseFile(this)" title="Choose a page url">Search Page</span>
			<span class="icon search search_image" onclick="onPresentationIncludeImageUrlTaskChooseFile(this)" title="Choose an image url">Search Image</span>
			<span class="icon add_variable search_variable" onclick="onPresentationProgrammingTaskChooseCreatedVariable(this)" title="Choose a variable">Search Variable</span>
			<input type="color" class="color-selector" title="Choose a color" onInput="onPresentationChooseColor(this)">
			<span class="icon delete" onClick="removeTemplateParam(this);">Remove</span>
			<div class="template_param_text' . (!$is_text ? ' hidden' : '') . '"><textarea onBlur="onBlurTemplateParam(this)"' . ($is_inactive ? " disabled" : "") . '>' . htmlspecialchars($v, ENT_NOQUOTES) . '</textarea></div>
		</div>';
	}
	
	public static function getTabContentTemplateLayoutIframeToolbarContentsHtml() {
		/*$html = '
			Screen size: 
			<select class="type" onChange="onChangeTemplateLayoutScreenSize(this)">
				<option>auto</option>
				<option>tablet</option>
				<option>smartphone</option>
				<option>responsive</option>
			</select>
			<select class="orientation" onChange="onChangeTemplateLayoutScreenSize(this)">
				<option>vertical</option>
				<option>horizontal</option>
			</select>
			<span class="dimension">
				<input class="width" name="width" placeHolder="Width" onKeyUp="onChangeTemplateLayoutScreenSize(this)" />px <input class="height" name="height" placeHolder="Height" onKeyUp="onChangeTemplateLayoutScreenSize(this)" />px
			</span>';*/
		$html = '
			<i class="icon desktop active" data-title="Show in Desktop" onClick="onChangeTemplateLayoutScreenToDesktop(this)"></i>
			<i class="icon mobile" data-title="Show in Mobile" onClick="onChangeTemplateLayoutScreenToMobile(this)"></i>
			<input class="width" title="Screen Width" value="300" maxlength="4" onKeyUp="onChangeTemplateLayoutScreenSize(this)">
			<span class="px">px</span>
			<span class="x"> x </span>
			<input class="height" title="Screen Height" value="300" maxlength="4" onKeyUp="onChangeTemplateLayoutScreenSize(this)">
			<span class="px">px</span>
			<input type="checkbox" class="fit_to_screen" data-title="Fit dimensions to screen" onChange="onChangeTemplateLayoutScreenSize(this)" checked />';
		
		return $html;
	}
}
?>
