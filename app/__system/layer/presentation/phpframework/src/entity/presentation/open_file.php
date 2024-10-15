<?php
include_once get_lib("org.phpframework.compression.ZipHandler");
include_once get_lib("org.phpframework.util.MimeTypeHandler");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;

$path = str_replace("../", "", $path);//for security reasons

if ($path) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);

	if ($PEVC) {
		$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config")));
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		$P = $PEVC->getPresentationLayer();
		$layer_path = $P->getLayerPathSetting();
		$selected_project_id = $P->getSelectedPresentationId();
		$extension = $P->getPresentationFileExtension();
		
		$file_path = $layer_path . $path;
		$layer_object_id = $file_path;
		$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_object_id, "layer", "access");
		
		$project_url_prefix = getProjectUrlPrefix($PEVC, $selected_project_id);
		$project_url_prefix .= substr($project_url_prefix, -1) != "/" ? "/" : "";
		
		if (empty($P->settings["presentation_webroot_path"]))
			launch_exception(new Exception("'PresentationLayer->settings[presentation_webroot_path]' cannot be undefined!"));
		
		$project_url_suffix = substr($path, strlen($selected_project_id . $P->settings["presentation_webroot_path"]));
		$project_url_suffix = substr($project_url_suffix, 0, 1) == "/" ? substr($project_url_suffix, 1) : $project_url_suffix;
		
		$PHPVariablesFileHandler->endUserGlobalVariables();
		
		$url = $project_url_prefix . $project_url_suffix;
		//echo "url:$url";die();
		
		header("Location: $url");
		echo "<script>document.location='$url';</script>";
		die();
	}
}

function getProjectUrlPrefix($EVC, $selected_project_id) {
	@include $EVC->getConfigPath("config", $selected_project_id); //config file may not exist
	
	return $project_url_prefix;
}
?>
