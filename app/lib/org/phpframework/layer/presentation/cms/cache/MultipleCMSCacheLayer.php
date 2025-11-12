<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.layer.presentation.cms.cache.CMSModuleCacheLayer");
include_once get_lib("org.phpframework.layer.presentation.cms.cache.CMSBlockCacheLayer");

class MultipleCMSCacheLayer {
	private $CMSModuleCacheLayer;
	private $CMSBlockCacheLayer;
	
	public function __construct($CMSLayer, $settings) {
		$this->CMSModuleCacheLayer = new CMSModuleCacheLayer($CMSLayer, $settings);
		$this->CMSBlockCacheLayer = new CMSBlockCacheLayer($CMSLayer, $settings);
	}
	
	/********* MODULE LAYER ********/
	public function setCMSModuleCacheLayer($CMSModuleCacheLayer) {$this->CMSModuleCacheLayer = $CMSModuleCacheLayer;}
	public function getCMSModuleCacheLayer() {return $this->CMSModuleCacheLayer;}
	
	/********* BLOCK LAYER ********/
	public function setCMSBlockCacheLayer($CMSBlockCacheLayer) {$this->CMSBlockCacheLayer = $CMSBlockCacheLayer;}
	public function getCMSBlockCacheLayer() {return $this->CMSBlockCacheLayer;}
}
?>
