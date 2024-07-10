<?php
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
