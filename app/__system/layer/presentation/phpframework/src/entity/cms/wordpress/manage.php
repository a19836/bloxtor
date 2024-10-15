<?php
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");
include_once $EVC->getUtilPath("LayoutTypeProjectHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$filter_by_layout = isset($_GET["filter_by_layout"]) ? $_GET["filter_by_layout"] : null; //optional

$path = str_replace("../", "", $path);//for security reasons
$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons

if ($bean_name && $bean_file_name) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
	
	if ($PEVC) {
		$P = $PEVC->getPresentationLayer();
		$selected_project_id = $P->getSelectedPresentationId();
		$common_project_name = $PEVC->getCommonProjectName();
		$show_projects = $selected_project_id == $common_project_name || !$selected_project_id;
		
		$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $PEVC->getConfigPath("pre_init_config")));
		$PHPVariablesFileHandler->startUserGlobalVariables();
	
		$layer_db_drivers = WorkFlowBeansFileHandler::getLayerDBDrivers($user_global_variables_file_path, $user_beans_folder_path, $P, true);
		
		$LayoutTypeProjectHandler = new LayoutTypeProjectHandler($UserAuthenticationHandler, $user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name);
		$LayoutTypeProjectHandler->filterLayerBrokersDBDriversPropsFromLayoutName($layer_db_drivers, $filter_by_layout); //filter db_drivers by $filter_by_layout
		$default_db_driver = isset($GLOBALS["default_db_driver"]) ? $GLOBALS["default_db_driver"] : null;
		
		$PHPVariablesFileHandler->endUserGlobalVariables();
		
		//in case the db_driver gets passed in the path - this happens this page gets called from the admin menu in the advanced admin panel.
		$selected_db_driver = $default_db_driver;
		if ($path && dirname($path) == "$common_project_name/webroot/" . WordPressUrlsParser::WORDPRESS_FOLDER_PREFIX)
			$selected_db_driver = basename($path);
		
		if ($show_projects) {
			$projects = CMSPresentationLayerHandler::getPresentationLayerProjectsFiles($user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name);
			unset($projects[$common_project_name]);
			
			$LayoutTypeProjectHandler = new LayoutTypeProjectHandler($UserAuthenticationHandler, $user_global_variables_file_path, $user_beans_folder_path, $bean_file_name, $bean_name);
			$LayoutTypeProjectHandler->filterPresentationLayerProjectsByUserAndLayoutPermissions($projects, $filter_by_layout, UserAuthenticationHandler::$PERMISSION_BELONG_NAME, array(
					"do_not_filter_by_layout" => array(
						"bean_name" => $bean_name,
						"bean_file_name" => $bean_file_name,
						"project" => $selected_project_id,
					)
				));
			
			$projects = array_keys($projects);
		}
		
		$installed_wordpress_folders_name = CMSPresentationLayerHandler::getWordPressInstallationsFoldersName($PEVC);
		
		if (!empty($_POST)) {
			$db_driver = isset($_POST["db_driver"]) ? $_POST["db_driver"] : null;
			
			if ($db_driver) {
				if ($show_projects && !empty($_POST["project"]))
					$path = $_POST["project"] . "/";
				
				$page = !empty($_POST["install_wordpress"]) ? "install" : "admin_login";
				$url = $project_url_prefix . "phpframework/cms/wordpress/$page?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path&db_driver=$db_driver";
				
				header("Location: $url");
				echo "<script>document.location='$url';</script>";
			}
			else
				$error_message = "You must select a DB Driver. Please try again...";
		}
	}
	else {
		launch_exception(new Exception("PEVC doesn't exists!"));
		die();
	}
}
else {
	launch_exception(new Exception("Undefined bean!"));
	die();
}
?>
