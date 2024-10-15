<?php
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");
include_once $EVC->getUtilPath("LayoutTypeProjectHandler");
include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSModuleEnableHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$popup = isset($_GET["popup"]) ? $_GET["popup"] : null;
$filter_by_layout = isset($_GET["filter_by_layout"]) ? $_GET["filter_by_layout"] : null; //optional

$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons

$files = CMSPresentationLayerHandler::getPresentationLayersProjectsFiles($user_global_variables_file_path, $user_beans_folder_path);
$LayoutTypeProjectHandler = new LayoutTypeProjectHandler($UserAuthenticationHandler, $user_global_variables_file_path, $user_beans_folder_path);
//$LayoutTypeProjectHandler->filterPresentationLayersProjectsByUserAndLayoutPermissions($files, $filter_by_layout, UserAuthenticationHandler::$PERMISSION_BELONG_NAME); //DEPRECATED - Do not filter files, bc we want to give the user oportunity to select what presentation layer does he wish to manage.

//get default presentation layer
if (!empty($_GET["bean_name"]))
	$default_presentation_layer_name = $_GET["bean_name"];
else {
	$files_bkp = $files;
	$LayoutTypeProjectHandler->filterPresentationLayersProjectsByUserAndLayoutPermissions($files_bkp, $filter_by_layout, UserAuthenticationHandler::$PERMISSION_BELONG_NAME);
	$default_presentation_layer_name = $files_bkp ? key($files_bkp) : null;
}

$CMSModuleLayer = $EVC->getCMSLayer()->getCMSModuleLayer();
$CMSModuleLayer->loadModules($project_common_url_prefix . "module/");
$loaded_modules = $CMSModuleLayer->getLoadedModules();
//echo "<pre>";print_r(array_keys($loaded_modules));die();
//echo "<pre>";print_r($loaded_modules);die();

$modules = array();
if (is_array($files)) {
	foreach ($files as $bean_name => $layer_props) {
		$bean_file_name = isset($layer_props["bean_file_name"]) ? $layer_props["bean_file_name"] : null;
		$item_label = isset($layer_props["item_label"]) ? $layer_props["item_label"] : null;
		
		$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
		$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name);
		
		$PCMSModuleLayer = $PEVC->getCMSLayer()->getCMSModuleLayer();
		$PCMSModuleLayer->loadModules($project_common_url_prefix . "module/");
		$project_loaded_modules = $PCMSModuleLayer->getLoadedModules();
		//echo "<pre>";print_r(array_keys($project_loaded_modules));die();
		//echo "<pre>";print_r($project_loaded_modules);die();
		//echo "<pre>";print_r($project_loaded_modules["article/edit_article"]);die();

		$modules[] = array(
			"bean_name" => $bean_name,
			"bean_file_name" => $bean_file_name,
			"item_label" => $item_label,
			"modules" => $project_loaded_modules,
		);
	}
}
//echo "<pre>";print_r($files);die();
//echo "<pre>";print_r(array_keys($modules[0]["modules"]));die();
//echo "<pre>";print_r($modules);die();

if (is_array($loaded_modules)) {
	$loaded_modules_by_group = array();
	foreach ($loaded_modules as $module_id => $loaded_module) {
		$group_module_id = isset($loaded_module["group_id"]) ? $loaded_module["group_id"] : null;
		$loaded_modules_by_group[$group_module_id][$module_id] = $loaded_module;
	}
	$loaded_modules = $loaded_modules_by_group;
	ksort($loaded_modules);
}

$is_install_module_allowed = $UserAuthenticationHandler->isPresentationFilePermissionAllowed($EVC->getEntityPath("admin/install_module"), "access");
$is_module_admin_allowed = $UserAuthenticationHandler->isPresentationFilePermissionAllowed($EVC->getEntityPath("admin/module_admin"), "access");
?>
