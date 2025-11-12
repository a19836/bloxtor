<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

interface ILayer {
	public function setCacheLayer($CacheLayer);
	public function getCacheLayer();
	public function isCacheActive();
	public function getModuleCacheLayer();
	public function getErrorHandler();
	public function getPHPFrameWork();
	public function setPHPFrameWorkObjName($phpframework_obj_name);
	public function getPHPFrameWorkObjName();
	public function getLayerPathSetting();
}
?>
