<?php
include_once get_lib("org.phpframework.layer.presentation.cms.CMSLayer");

class CMSBlockLayer {
	private $CMSLayer;
	
	private $blocks;
	private $blocks_settings;
	private $current_block_id;
	private $regions_by_block;
	
	private $stop_all_blocks;
	private $stop_by_block;
	
	public function __construct(CMSLayer $CMSLayer) {
		$this->CMSLayer = $CMSLayer;
	
		$this->blocks = array();
		$this->blocks_settings = array();
		$this->current_block_id = null;
		$this->regions_by_block = array();
		
		$this->stop_all_blocks = false;
		$this->stop_by_block = array();
	}
	
	public function createBlock($module_id, $block_id, &$settings) {
		if ($this->isBlockExecutionValid($block_id)) {
			$this->current_block_id = $block_id;//To be used by the join points and stop blocks action
			
			$has_cache = false;
			$is_cache_active = $this->CMSLayer->isCacheActive();
			
			if ($is_cache_active) {
				$CacheLayer = $this->CMSLayer->getCacheLayer()->getCMSBlockCacheLayer();
				
				if($CacheLayer->isValid($block_id, $settings)) {
					$result = $CacheLayer->get($block_id, $settings);
					$has_cache = $result ? true : false;
				}
			}
		
			if (!$has_cache) {
				$orig_settings = $settings;
				$cms_settings = array(
					"module_id" => $module_id, //raw module id without any parseing
					"block_id" => $block_id,
				);
				$result = $this->CMSLayer->getCMSModuleLayer()->executeModule($module_id, $settings, $cms_settings);
				
				if ($is_cache_active)
					$CacheLayer->check($block_id, $orig_settings, $result);
			}
			
			//Is very important to add the block result inside of an array, bc if the block is called multiple times inside of the same entity/page (with the same or different $block_local_variables - it doesn't really matter), then we should save multiple results (equal or different, depending of the correspondent $block_local_variables), otherwise when we render a region the last saved block id, will overwrite all the previous saved block' results (with the same block id).
			//Additionally if we call this block together with createBlockHtml and then the createBlock again for the same block id, when we render a region, only the last block result will appear before and after the createBlockHtml.
			$this->blocks[$block_id][] = isset($result) ? $result : null;
			$this->blocks_settings[$block_id][] = $settings;
		}
	}
	
	public function createBlockHtml($block_id, $html, $check_cache = true) {
		if ($this->isBlockExecutionValid($block_id)) {
			$this->current_block_id = $block_id;//To be used by the join points and stop blocks action
			
			if ($check_cache) {
				$is_cache_active = $this->CMSLayer->isCacheActive();
				
				if ($is_cache_active) {
					$CacheLayer = $this->CMSLayer->getCacheLayer()->getCMSBlockCacheLayer();
					$CacheLayer->check($block_id, null, $html);
				}
			}
			
			//Is very important to add the block result inside of an array, bc if the block is called multiple times inside of the same entity/page (with the same or different $block_local_variables - it doesn't really matter), then we should save multiple results (equal or different, depending of the correspondent $block_local_variables), otherwise when we render a region the last saved block id, will overwrite all the previous saved block' results (with the same block id).
			//Additionally if we call this block together with createBlock and then the createBlockHtml again for the same block id, when we render a region, only the last block html will appear before and after the createBlock.
			$this->blocks[$block_id][] = $html;
			$this->blocks_settings[$block_id][] = null;
		}
	}
	
	//To be used by the blocks that have hard coded html and that call the createBlockHtml($block_id, $html) method
	public function getCachedBlock($block_id, $settings) {
		$is_cache_active = $this->CMSLayer->isCacheActive();
		if($is_cache_active) {
			$CacheLayer = $this->CMSLayer->getCacheLayer()->getCMSBlockCacheLayer();
			
			if($CacheLayer->isValid($block_id, $settings))
				return $CacheLayer->get($block_id, $settings);
		}
		return null;
	}
	
	public function getCurrentBlockId() { return $this->current_block_id; }
	
	public function getBlocks() { return $this->blocks; }
	public function getBlock($block_id, $index = 0) {
		$this->prepareSavedBlockIndex($block_id, $index);
		return isset($this->blocks[$block_id][$index]) ? $this->blocks[$block_id][$index] : null;
	}
	public function existsBlock($block_id) {
		return $this->blocks && array_key_exists($block_id, $this->blocks);
	}
	public function getCurrentBlock($index = -1) { //get the output/result from the last saved block
		$block_id = $this->getCurrentBlockId();
		$this->prepareSavedBlockIndex($block_id, $index);
		return isset($this->blocks[$block_id][$index]) ? $this->blocks[$block_id][$index] : null; 
	}
	
	public function getBlocksSettings() { return $this->blocks_settings; }
	
	public function getBlockSettings($block_id, $index = 0) { 
		$this->prepareSavedBlockIndex($block_id, $index);
		return isset($this->blocks_settings[$block_id][$index]) ? $this->blocks_settings[$block_id][$index] : null;
	}
	public function getCurrentBlockSettings($index = -1) { //get the last saved block settings
		$block_id = $this->getCurrentBlockId();
		$this->prepareSavedBlockIndex($block_id, $index);
		return isset($this->blocks_settings[$block_id][$index]) ? $this->blocks_settings[$block_id][$index] : null; 
	}
	public function getBlockSetting($block_id, $setting_name, $index = 0) { 
		$this->prepareSavedBlockIndex($block_id, $index);
		return isset($this->blocks_settings[$block_id][$index][$setting_name]) ? $this->blocks_settings[$block_id][$index][$setting_name] : null;
	}
	public function getCurrentBlockSetting($setting_name, $index = -1) { //get a specific setting from the last saved block settings
		$block_id = $this->getCurrentBlockId();
		$this->prepareSavedBlockIndex($block_id, $index);
		return isset($this->blocks_settings[$block_id][$index][$setting_name]) ? $this->blocks_settings[$block_id][$index][$setting_name] : null; 
	}
	
	public function getBlockIdFromFilePath($file_path, $project_id = false) {
		$file_path = normalize_windows_path_to_linux($file_path); //$file_path is usually __FILE__, so we must convert the "\\" to "/" on windows.
		
		if ($project_id)
			$block_id = str_replace($this->CMSLayer->getEVC()->getBlocksPath($project_id), "", $file_path);
		else {
			$P = $this->CMSLayer->getEVC()->getPresentationLayer();
			$current_project_id = $P->getSelectedPresentationId();
			
			$project_id = str_replace($P->getLayerPathSetting(), "", $file_path);
			$pos = strpos($project_id, "/src/");
			$project_id = substr($project_id, 0, $pos);
			
			if ($project_id != $current_project_id)
				$block_id = $project_id . "/" . str_replace($this->CMSLayer->getEVC()->getBlocksPath($project_id), "", $file_path);
			else 
				$block_id = str_replace($this->CMSLayer->getEVC()->getBlocksPath($current_project_id), "", $file_path);
		}
		
		$extension = pathinfo($block_id, PATHINFO_EXTENSION);
		
		if ($extension)
			$block_id = str_replace("." . $extension, "", $block_id);
		
		return $block_id;
	}
	
	private function prepareSavedBlockIndex($block_id, &$index) {
		if (isset($this->blocks[$block_id])) {
			$index = is_numeric($index) ? $index : 0;
			
			if ($index == -1) //get last saved block
				$index = count($this->blocks[$block_id]) - 1;
		}
		
		return $index;
	}
	
	/* STOP EXECUTION FUNCTIONS */
	
	public function stopAllBlocks() { 
		$this->stop_all_blocks = true; 
	}
	public function startAllBlocks() { 
		$this->stop_all_blocks = false; 
	}
	
	public function stopBlock($block_id) { 
		if ($block_id)
			$this->stop_by_block[$block_id] = true; 
	}
	public function startBlock($block_id) { 
		if ($block_id)
			$this->stop_by_block[$block_id] = false;
	}
	
	/*
	 * In order to this function works correctly, the $CMSTemplateLayer->addRegionHtml($region_id, $block_id) must be called before the 'include $EVC->getBlockPath("block_id");', otherwise the stopBlockRegions($block_id) won't work because it doesn't know the regions for the correspondent block.
	 */
	public function stopBlockRegions($block_id) { 
		$this->stopBlock($block_id);
		
		if ($block_id && !empty($this->regions_by_block[$block_id])) {
			$T = $this->CMSLayer->getCMSTemplateLayer();
			
			foreach ($this->regions_by_block[$block_id] as $region_id => $aux)
				$T->stopRegion($region_id);
		}
	}
	public function startBlockRegions($block_id) { 
		$this->startBlock($block_id);
		
		if ($block_id && !empty($this->regions_by_block[$block_id])) {
			$T = $this->CMSLayer->getCMSTemplateLayer();
			
			foreach ($this->regions_by_block[$block_id] as $region_id => $aux)
				$T->startRegion($region_id);
		}
	}
	
	public function stopCurrentBlock() { 
		$block_id = $this->getCurrentBlockId();
		$this->stopBlock($block_id); 
	}
	public function startCurrentBlock() { 
		$block_id = $this->getCurrentBlockId();
		$this->startBlock($block_id); 
	}
	
	public function stopCurrentBlockRegions() { 
		$block_id = $this->getCurrentBlockId();
		$this->stopBlockRegions($block_id); 
	}
	public function startCurrentBlockRegions() { 
		$block_id = $this->getCurrentBlockId();
		$this->startBlockRegions($block_id); 
	}
	
	public function isAllBlocksExecutionValid() { 
		return !$this->stop_all_blocks; 
	}
	public function isBlockRegionsExecutionValid($block_id) { 
		if ($block_id) {
			if (!empty($this->regions_by_block[$block_id])) {
				$CMSTemplateLayer = $this->CMSLayer->getCMSTemplateLayer();
				
				//Return true if exists at least one valid region. Only return false if all the regions are invalid. If 1 region is valid, return true, because te block should be executed!
				//By default each block will only have 1 region, so that region is invalid, this code will return false.
				foreach ($this->regions_by_block[$block_id] as $region_id => $aux)
					if ($CMSTemplateLayer->isRegionExecutionValid($region_id))
						return true;
				return false;
			}
			return true;
		}
		return false;
	}
	public function isBlockExecutionValid($block_id) { 
		return $this->isAllBlocksExecutionValid() && $block_id && empty($this->stop_by_block[$block_id]) && $this->isBlockRegionsExecutionValid($block_id);
	}
	public function isCurrentBlockExecutionValid() { 
		$block_id = $this->getCurrentBlockId();
		return $this->isBlockExecutionValid($block_id); 
	}
	
	public function addBlockRegion($block_id, $region_id) {
		if ($block_id && $region_id)
			$this->regions_by_block[$block_id][$region_id] = true;
	}
}
?>
