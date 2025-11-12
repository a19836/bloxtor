<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.layer.presentation.evc.EVC");
include_once get_lib("org.phpframework.layer.presentation.cms.CMSModuleLayer");
include_once get_lib("org.phpframework.layer.presentation.cms.CMSBlockLayer");
include_once get_lib("org.phpframework.layer.presentation.cms.CMSViewLayer");
include_once get_lib("org.phpframework.layer.presentation.cms.CMSTemplateLayer");
include_once get_lib("org.phpframework.layer.presentation.cms.CMSJoinPointLayer");
include_once get_lib("org.phpframework.layer.presentation.cms.CMSSequentialLogicalActivityLayer");
include_once get_lib("org.phpframework.layer.presentation.cms.CMSHtmlParserLayer");
include_once get_lib("org.phpframework.layer.presentation.cms.CMSPagePropertyLayer");

class CMSLayer {
	private $EVC;
	private $CMSModuleLayer;
	private $CMSBlockLayer;
	private $CMSViewLayer;
	private $CMSTemplateLayer;
	private $CMSJoinPointLayer;
	private $CMSSequentialLogicalActivityLayer;
	private $CMSHtmlParserLayer;
	private $CMSPagePropertyLayer;
	
	private $CacheLayer;
	
	/********* INIT ********/
	public function __construct(EVC $EVC) {
		$this->EVC = $EVC;
		
		$this->CMSModuleLayer = new CMSModuleLayer($this);
		$this->CMSBlockLayer = new CMSBlockLayer($this);
		$this->CMSViewLayer = new CMSViewLayer($this);
		$this->CMSTemplateLayer = new CMSTemplateLayer($this);
		$this->CMSJoinPointLayer = new CMSJoinPointLayer($this);
		$this->CMSSequentialLogicalActivityLayer = new CMSSequentialLogicalActivityLayer($this);
		$this->CMSHtmlParserLayer = new CMSHtmlParserLayer($this);
		$this->CMSPagePropertyLayer = new CMSPagePropertyLayer($this);
	}
	
	/********* CACHE LAYER ********/
	public function setCacheLayer($CacheLayer) {$this->CacheLayer = $CacheLayer;}
	public function getCacheLayer() {return $this->CacheLayer;}
	public function isCacheActive() {return $this->CacheLayer ? true : false;}
	
	/********* EVC ********/
	public function setEVC($EVC) { $this->EVC = $EVC; }
	public function getEVC() { return $this->EVC; }
	
	/********* MODULE LAYER ********/
	public function setCMSModuleLayer($CMSModuleLayer) { $this->CMSModuleLayer = $CMSModuleLayer; }
	public function getCMSModuleLayer() { return $this->CMSModuleLayer; }
	
	/********* BLOCK LAYER ********/
	public function setCMSBlockLayer($CMSBlockLayer) { $this->CMSBlockLayer = $CMSBlockLayer; }
	public function getCMSBlockLayer() { return $this->CMSBlockLayer; }
	
	/********* BLOCK LAYER ********/
	public function setCMSViewLayer($CMSViewLayer) { $this->CMSViewLayer = $CMSViewLayer; }
	public function getCMSViewLayer() { return $this->CMSViewLayer; }
	
	/********* TEMPLATE LAYER ********/
	public function setCMSTemplateLayer($CMSTemplateLayer) { $this->CMSTemplateLayer = $CMSTemplateLayer; }
	public function getCMSTemplateLayer() { return $this->CMSTemplateLayer; }
	
	/********* JOIN POINT LAYER ********/
	public function setCMSJoinPointLayer($CMSJoinPointLayer) { $this->CMSJoinPointLayer = $CMSJoinPointLayer; }
	public function getCMSJoinPointLayer() { return $this->CMSJoinPointLayer; }
	
	/********* SEQUENTIAL LOGICAL ACTIVITY LAYER ********/
	public function setCMSSequentialLogicalActivityLayer($CMSSequentialLogicalActivityLayer) { $this->CMSSequentialLogicalActivityLayer = $CMSSequentialLogicalActivityLayer; }
	public function getCMSSequentialLogicalActivityLayer() { return $this->CMSSequentialLogicalActivityLayer; }
	
	/********* WIDGET RESOURCE LAYER ********/
	public function setCMSHtmlParserLayer($CMSHtmlParserLayer) { $this->CMSHtmlParserLayer = $CMSHtmlParserLayer; }
	public function getCMSHtmlParserLayer() { return $this->CMSHtmlParserLayer; }
	
	/********* PAGE PROPERTY LAYER ********/
	public function setCMSPagePropertyLayer($CMSPagePropertyLayer) { $this->CMSPagePropertyLayer = $CMSPagePropertyLayer; }
	public function getCMSPagePropertyLayer() { return $this->CMSPagePropertyLayer; }
}
?>
