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

class CMSTemplateLayer {
	private $CMSLayer;
	
	private $regions;
	private $params;
	
	private $stop_all_regions;
	private $stop_by_region;
	
	private $rendered_regions;
	
	public function __construct(CMSLayer $CMSLayer) {
		$this->CMSLayer = $CMSLayer;
		
		$this->regions = array();
		$this->params = array();
		
		$this->stop_all_regions = false;
		$this->stop_by_region = array();
		
		$this->rendered_regions = array();
	}
	
	/* REGION FUNCTIONS */
	
	public function getRegions() {
		return $this->regions;
	}
	
	public function getRenderedRegions() {
		return $this->rendered_regions;
	}
	
	public function resetRegion($region_id) {
		if ($this->isRegionExecutionValid($region_id))
			$this->regions[$region_id] = array();
	}
	
	public function addRegionHtml($region_id, $html) {
		if ($this->isRegionExecutionValid($region_id) && $html) {
			$this->regions[$region_id][] = array(1, $html);
			return true;
		}
		return false;
	}
	
	/*
	 * Note that this addRegionHtml($region_id, $block_id) must be called before the 'include $EVC->getBlockPath("block_id");', otherwise the $CMSBlockLayer->stopBlockRegions($block_id) won't work because it doesn't know the regions for the correspondent block. This addRegionHtml($region_id, $block_id) must be called first so it can execute '$this->CMSLayer->getCMSBlockLayer()->addBlockRegion($block_id, $region_id);' which will link the block with the region, which the stopBlockRegions($block_id) funciton will use...
	 */
	public function addRegionBlock($region_id, $block_id, $project_id = false) {
		if ($region_id && $block_id) {
			$block_id = $project_id ? "$project_id/$block_id" : $block_id;
			
			//used in the stop blocks execution action. 
			//This code must execute even if the region or block are invalid, otherwise the block will still be executed in the background, which could be a security faulty/issue and friendly for hacks. PLEASE LEAVE THIS CODE HERE!!!
			$this->CMSLayer->getCMSBlockLayer()->addBlockRegion($block_id, $region_id);
			
			if ($this->isRegionExecutionValid($region_id) && $this->CMSLayer->getCMSBlockLayer()->isBlockExecutionValid($block_id)) {
				$this->regions[$region_id][] = array(2, $block_id);
				return true;
			}
		}
		return false;
	}
	
	//returns a file path to be included in the entity. The returned file, catch the file output and add it to the region.
	public function includeRegionBlockPathOutput($region_id, $block_id, $project_id = false) {
		$this->addRegionBlock($region_id, $block_id, $project_id);
		$GLOBALS["BLOCK_ID"] = $block_id;
		$GLOBALS["BLOCK_FILE_PATH"] = $this->CMSLayer->getEVC()->getBlockPath($block_id, $project_id);
		
		return __DIR__ . "/include_cms_block_output.php";
	}
	
	public function addRegionView($region_id, $view_id, $project_id = false) {
		if ($region_id && $view_id) {
			$view_id = $project_id ? "$project_id/$view_id" : $view_id;
			
			//used in the stop views execution action. 
			//This code must execute even if the region or view are invalid, otherwise the view will still be executed in the background, which could be a security faulty/issue and friendly for hacks. PLEASE LEAVE THIS CODE HERE!!!
			$this->CMSLayer->getCMSViewLayer()->addViewRegion($view_id, $region_id);
			
			if ($this->isRegionExecutionValid($region_id) && $this->CMSLayer->getCMSViewLayer()->isViewExecutionValid($view_id)) {
				$this->regions[$region_id][] = array(3, $view_id);
				return true;
			}
		}
		return false;
	}
	
	//returns a file path to be included in the entity. The returned file, catch the file output and add it to the region.
	public function includeRegionViewPathOutput($region_id, $view_id, $project_id = false) {
		$this->addRegionView($region_id, $view_id, $project_id);
		$GLOBALS["VIEW_ID"] = $view_id;
		$GLOBALS["VIEW_FILE_PATH"] = $this->CMSLayer->getEVC()->getViewPath($view_id, $project_id);
		
		return __DIR__ . "/include_cms_view_output.php";
	}
	
	public function renderRegion($region_id) {
		$html = "";
		
		$this->rendered_regions[$region_id] = true;
		
		$region_components = isset($this->regions[$region_id]) ? $this->regions[$region_id] : null;
		$region_blocks_index = array();
		$region_views_index = array();
		
		if (is_array($region_components))
			foreach ($region_components as $component) {
				$bt = isset($component[0]) ? $component[0] : null;
				$b = isset($component[1]) ? $component[1] : null;
				
				if ($bt == 1)
					$html .= $b;
				else if ($bt == 3) {
					$region_views_index[$b] = isset($region_views_index[$b]) ? $region_views_index[$b] + 1 : 0;
					
					$html .= $this->CMSLayer->getCMSViewLayer()->getView($b, $region_views_index[$b]);
				}
				else {
					$region_blocks_index[$b] = isset($region_blocks_index[$b]) ? $region_blocks_index[$b] + 1 : 0;
					
					$html .= $this->CMSLayer->getCMSBlockLayer()->getBlock($b, $region_blocks_index[$b]);
				}
			}
		
		//parse html if apply and page properties allow it
		if ($html)
			$this->CMSLayer->getCMSHtmlParserLayer()->parseRenderedRegionHtml($html);
		
		return $html;
	}
	
	/* STOP EXECUTION FUNCTIONS */
	
	public function stopAllRegions() { 
		$this->stop_all_regions = true; 
	}
	public function startAllRegions() { 
		$this->stop_all_regions = false; 
	}
	
	public function stopRegion($region_id) { 
		if ($region_id)
			$this->stop_by_region[$region_id] = true; 
	}
	public function startRegion($region_id) { 
		if ($region_id)
			$this->stop_by_region[$region_id] = false; 
	}
	
	public function isAllRegionsExecutionValid() { 
		return !$this->stop_all_regions; 
	}
	public function isRegionExecutionValid($region_id) { 
		return $this->isAllRegionsExecutionValid() && $region_id && empty($this->stop_by_region[$region_id]); 
	}
	
	/* PARAM FUNCTIONS */
	
	public function setParam($param_id, $value, $force = false) { 
		if ($force || !array_key_exists($param_id, $this->params))
			$this->params[$param_id] = $value;
	}
	public function getParam($param_id) { return isset($this->params[$param_id]) ? $this->params[$param_id] : null; }
}
?>
