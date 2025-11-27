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

include_once get_lib("org.phpframework.layer.presentation.cms.CMSLayer");
include_once get_lib("org.phpframework.joinpoint.JoinPointHandler");

class CMSJoinPointLayer {
	private $CMSLayer;
	private $JoinPointHandler;
	
	private $join_points = array();//These are the global join points
	private $block_join_points = array();//these are the join points only for specific blocks
	private $region_block_join_points = array();//these are the join points only for specific blocks in a region
	
	private $current_join_poin_region = null;//current region being executed
	
	public function __construct(CMSLayer $CMSLayer) {
		$this->CMSLayer = $CMSLayer;
		$this->JoinPointHandler = new JoinPointHandler( array("EVC" => $this->CMSLayer->getEVC()) );
	}
	
	public function addJoinPoint($name, $join_point_properties) {
		//echo "addJoinPoint: $name<br>";
		$this->join_points[$name][] = $join_point_properties;
	}
	
	public function addBlockJoinPoint($block_id, $name, $join_point_properties) {
		//echo "addBlockJoinPoint: $block_id, $name<br>";
		$this->block_join_points[$block_id][$name][] = $join_point_properties;
	}
	public function getBlockJoinPoints($block_id) { 
		return isset($this->block_join_points[$block_id]) ? $this->block_join_points[$block_id] : null;
	}
	public function setBlockJoinPoints($block_id, $block_join_points) {
		$this->block_join_points[$block_id] = $block_join_points;
	}
	public function resetBlockJoinPoints($block_id) { $this->block_join_points[$block_id] = null; }
	
	public function addRegionBlockJoinPoint($region_id, $block_id, $name, $join_point_properties) {
		//echo "addRegionBlockJoinPoint: $region_id, $block_id, $name<br>";
		$this->region_block_join_points[$region_id][$block_id][$name][] = $join_point_properties;
		
		$this->current_join_poin_region = $region_id;
		
		/*
		Note that: the $current_block is changed in the CMSBlockLayer::createBlock method. 
		Do NOT call it or change the $current_block value here, otherwise you cannot call the addRegionBlockJoinPoint in a generic include files, for example. 
		If you had to change the $current_block here or in any other method of this class, the addRegionBlockJoinPoint method must be called before the "include $EVC->getBlockPath(block_id);" (in the entity files), or otherwise won't work, because it will loose the correct $block_id value.
		For code flexibility advantages, do NOT change the $current_block method here or in any other method of this class. Leave it in the CMSBlockLayer::createBlock method.
		*/
	}
	public function getRegionBlockJoinPoints($region_id, $block_id) {
		return isset($this->region_block_join_points[$region_id][$block_id]) ? $this->region_block_join_points[$region_id][$block_id] : null;
	}
	public function setRegionBlockJoinPoints($region_id, $block_id, $region_block_join_points) {
		$this->region_block_join_points[$region_id][$block_id] = $region_block_join_points;
	}
	public function resetRegionBlockJoinPoints($region_id, $block_id) {
		$this->region_block_join_points[$region_id][$block_id] = null;
		$this->current_join_poin_region = null;
	}
	
	public function includeJoinPoint($name, $params, $description = null) { //$description is not used here but is used by the CMSFileHandler::getIncludeJoinPoints to get the joinpoints data and then show them to the user...
		$join_points = $this->getJoinPointsToBeExecutedByName($name);
		//echo "includeJoinPoint: $name ({$this->current_join_poin_region})<pre>";print_r($join_points);die();
		
		if ($join_points) {
			foreach ($join_points as $join_point_properties) {
				$this->JoinPointHandler->executeJoinPoint($join_point_properties, $params);
			}
		}
	}
	
	public function includeStatusJoinPoint($name, $params, $description = null) { //$description is not used here but is used by the CMSFileHandler::getIncludeJoinPoints to get the joinpoints data and then show them to the user...
		$status = true;
		
		$join_points = $this->getJoinPointsToBeExecutedByName($name);
		
		if ($join_points) {
			foreach ($join_points as $join_point_properties) {
				if (!$this->JoinPointHandler->executeJoinPoint($join_point_properties, $params)) {
					$status = false;
					break;
				}
			}
		}
		
		return $status;
	}
	
	private function getJoinPointsToBeExecutedByName($name) {
		$join_points = array();
		
		if ($name) {
			$current_block = $this->CMSLayer->getCMSBlockLayer()->getCurrentBlockId();
			
			$block_join_points = $current_block && isset($this->block_join_points[$current_block][$name]) ? $this->block_join_points[$current_block][$name] : array();
			$block_join_points = $block_join_points ? $block_join_points : array();
			
			$region_join_points = $current_block && $this->current_join_poin_region && isset($this->region_block_join_points[$this->current_join_poin_region][$current_block][$name]) ? $this->region_block_join_points[$this->current_join_poin_region][$current_block][$name] : array();
			$region_join_points = $region_join_points ? $region_join_points : array();
		
			$join_points = !empty($this->join_points[$name]) ? $this->join_points[$name] : array();
			$join_points = array_merge($block_join_points, $region_join_points, $join_points);
		}
		
		return $join_points;
	}
}
?>
