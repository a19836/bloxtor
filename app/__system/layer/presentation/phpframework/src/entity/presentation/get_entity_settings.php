<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$_GET["edit_entity_type"] = "simple";
$file_type = "edit_entity";

include $EVC->getEntityPath("presentation/edit");
$EVC->setView("get_entity_settings");

$template = isset($templates[0]["template"]) ? $templates[0]["template"] : null;
$available_regions_list = isset($available_regions_list) ? $available_regions_list : null;
$regions_blocks_list = isset($regions_blocks_list) ? $regions_blocks_list : null;
$available_blocks_list = isset($available_blocks_list) ? $available_blocks_list : null;
$available_block_params_list = isset($available_block_params_list) ? $available_block_params_list : null;
$block_params_values_list = isset($block_params_values_list) ? $block_params_values_list : null;
$includes = isset($includes) ? $includes : null;
$available_params_list = isset($available_params_list) ? $available_params_list : null;
$template_params_values_list = isset($template_params_values_list) ? $template_params_values_list : null;
$blocks_join_points = isset($blocks_join_points) ? $blocks_join_points : null;
$hard_coded = isset($hard_coded) ? $hard_coded : null;

$obj = prepareMainObject($template, $available_regions_list, $regions_blocks_list, $available_blocks_list, $available_block_params_list, $block_params_values_list, $includes, $available_params_list, $template_params_values_list, $blocks_join_points, $hard_coded);

function prepareMainObject($template, $available_regions_list, $regions_blocks_list, $available_blocks_list, $available_block_params_list, $block_params_values_list, $includes, $available_params_list, $template_params_values_list, $blocks_join_points, $hard_coded) {
	$obj = array(
		"regions" => array(),
		"includes" => array(),
		"template" => $template,
		"template_params" => array(),
		"available_blocks_list" => $available_blocks_list,
		"hard_coded" => $hard_coded,
	);
	
	//preparing available regions blocks
	if ($available_regions_list) {
		$t = count($available_regions_list);
		for ($i = 0; $i < $t; $i++) {
			$region = $available_regions_list[$i];
			
			if (empty($obj["regions"][$region]))
				$obj["regions"][$region] = array();
		}
	}
	
	if ($regions_blocks_list) {
		$rb_indexes = array();
		
		$t = count($regions_blocks_list);
		for ($i = 0; $i < $t; $i++) {
			$rbl = $regions_blocks_list[$i];
			
			//preparing region block
			$region = isset($rbl[0]) ? $rbl[0] : null;
			$block = isset($rbl[1]) ? $rbl[1] : null;
			$proj = isset($rbl[2]) ? $rbl[2] : null;
			
			if (isset($rb_indexes["$region-$block-$proj"]))
				$rb_indexes["$region-$block-$proj"]++;
			else
				$rb_indexes["$region-$block-$proj"] = 0;
			
			$rb_index = $rb_indexes["$region-$block-$proj"];
			
			//preparing block params
			$block_params = isset($available_block_params_list[$region][$block]) ? $available_block_params_list[$region][$block] : null;
			$block_params_values = isset($block_params_values_list[$region][$block][$rb_index]) ? $block_params_values_list[$region][$block][$rb_index] : null;
			$params = $block_params_values ? $block_params_values : array();
			
			if ($block_params) 
				foreach ($block_params as $j => $p)
					$params[$p] = isset($block_params_values[$p]) ? $block_params_values[$p] : null;
			
			//preparing join points
			$jps = array();
			$block_join_points = isset($blocks_join_points[$region][$block][$rb_index]) ? $blocks_join_points[$region][$block][$rb_index] : null;
			
			if (is_array($block_join_points)) {
				foreach ($block_join_points as $block_join_point) {
					$join_point_name = isset($block_join_point["join_point_name"]) ? $block_join_point["join_point_name"] : null;
					
					if ($join_point_name) {
						$join_point_settings = isset($block_join_point["join_point_settings"]) ? (isset($block_join_point["join_point_settings"]["key"]) ? array($block_join_point["join_point_settings"]) : $block_join_point["join_point_settings"]) : null;
						$join_point_settings_obj = CMSPresentationLayerJoinPointsUIHandler::convertBlockSettingsArrayToObj($join_point_settings);
						
						$jps[$join_point_name][] = $join_point_settings_obj;
					}
				}
			}
			
			$obj["regions"][$region][] = array(
				"block" => $block,
				"proj" => $proj,
				"params" => $params,
				"join_points" => $jps,
			);
		}
	}
	
	//preparing includes
	if ($includes) {
		$t = count($includes);
		for ($i = 0; $i < $t; $i++) {
			$include = $includes[$i];
			$inc_path = isset($include["path"]) ? $include["path"] : null;
			$inc_path = PHPUICodeExpressionHandler::getArgumentCode($inc_path, isset($include["path_type"]) ? $include["path_type"] : null);
			
			$obj["includes"][] = array("path" => $inc_path, "once" => isset($include["once"]) ? $include["once"] : null);
		}
	}
	
	//preparing template params
	if ($available_params_list) {
		$template_params = $template_params_values_list ? $template_params_values_list : array();
		
		$t = count($available_params_list);
		for ($i = 0; $i < $t; $i++) {
			$param = $available_params_list[$i];
			$template_params[$param] = $template_params_values_list[$param];
		}
		
		$obj["template_params"] = $template_params;
	}
	
	return $obj;
}
?>
