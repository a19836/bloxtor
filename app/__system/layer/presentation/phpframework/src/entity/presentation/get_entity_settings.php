<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$_GET["edit_entity_type"] = "simple";
$file_type = "edit_entity";

include $EVC->getEntityPath("presentation/edit");
$EVC->setView("get_entity_settings");

$obj = prepareMainObject($templates[0]["template"], $available_regions_list, $regions_blocks_list, $available_blocks_list, $available_block_params_list, $block_params_values_list, $includes, $available_params_list, $template_params_values_list, $blocks_join_points, $hard_coded);

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
			
			if (!$obj["regions"][$region])
				$obj["regions"][$region] = array();
		}
	}
	
	if ($regions_blocks_list) {
		$rb_indexes = array();
		
		$t = count($regions_blocks_list);
		for ($i = 0; $i < $t; $i++) {
			$rbl = $regions_blocks_list[$i];
			
			//preparing region block
			$region = $rbl[0];
			$block = $rbl[1];
			$proj = $rbl[2];
			
			if (isset($rb_indexes["$region-$block-$proj"]))
				$rb_indexes["$region-$block-$proj"]++;
			else
				$rb_indexes["$region-$block-$proj"] = 0;
			
			$rb_index = $rb_indexes["$region-$block-$proj"];
			
			//preparing block params
			$block_params = $available_block_params_list[$region][$block];
			$block_params_values = $block_params_values_list[$region][$block][$rb_index];
			$params = $block_params_values ? $block_params_values : array();
			
			if ($block_params) 
				foreach ($block_params as $j => $p)
					$params[$p] = $block_params_values[$p];
			
			//preparing join points
			$jps = array();
			$block_join_points = $blocks_join_points[$region][$block][$rb_index];
			
			if (is_array($block_join_points)) {
				foreach ($block_join_points as $block_join_point) {
					$join_point_name = $block_join_point["join_point_name"];
					
					if ($join_point_name) {
						$join_point_settings = isset($block_join_point["join_point_settings"]["key"]) ? array($block_join_point["join_point_settings"]) : $block_join_point["join_point_settings"];
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
			$inc_path = PHPUICodeExpressionHandler::getArgumentCode($include["path"], $include["path_type"]);
			
			$obj["includes"][] = array("path" => $inc_path, "once" => $include["once"]);
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
