<?php
include_once $EVC->getUtilPath("PHPVariablesFileHandler");
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$module_id = isset($_GET["module_id"]) ? $_GET["module_id"] : null;

$layer_path = $EVC->getPresentationLayer()->getLayerPathSetting();

$CMSModuleLayer = $EVC->getCMSLayer()->getCMSModuleLayer();
$CMSModuleLayer->loadModules($project_common_url_prefix . "module/");
$all_loaded_modules = $CMSModuleLayer->getLoadedModules();
$module = $CMSModuleLayer->getLoadedModule($module_id);
$module = prepareModuleToBeShown($module, $layer_path);
//print_r($module);

function prepareModuleToBeShown($arr, $layer_path) {
	foreach ($arr as $k => $v) {
		if (is_array($v))
			$arr[$k] = prepareModuleToBeShown($v, $layer_path);
		else
			$arr[$k] = str_replace($layer_path, "", $v);
	}
	
	return $arr;
}
?>
