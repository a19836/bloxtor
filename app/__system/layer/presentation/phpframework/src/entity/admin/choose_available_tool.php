<?php
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$path = $_GET["path"];
$selected_db_driver = $_GET["selected_db_driver"];
$popup = $_GET["popup"];
$filter_by_layout = $_GET["filter_by_layout"];

$path = str_replace("../", "", $path);//for security reasons
$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons

//preparing filter_by_layout
$layers_beans = AdminMenuHandler::getLayers($user_global_variables_file_path);
//echo "<pre>";print_r($layers_beans);die();

if ($layers_beans && $layers_beans["presentation_layers"]) {
	if ($bean_name && $bean_file_name && $path && $layers_beans["presentation_layers"][$bean_name] == $bean_file_name) {
		$layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerBeanFolderName($user_beans_folder_path . $bean_file_name, $bean_name, $user_global_variables_file_path);
		
		$filter_by_layout = "$layer_bean_folder_name/" . preg_replace("/\/+$/", "", $path); //remove last slash from $path
		$filter_by_layout_permission = UserAuthenticationHandler::$PERMISSION_BELONG_NAME;
	}
	else if ($filter_by_layout) {
		foreach ($layers_beans["presentation_layers"] as $bn => $bfn) {
			$layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerBeanFolderName($user_beans_folder_path . $bfn, $bn, $user_global_variables_file_path);
			$layer_bean_folder_name .= "/";
			
			if (substr($filter_by_layout, 0, strlen($layer_bean_folder_name)) == $layer_bean_folder_name) {
				$bean_name = $bn;
				$bean_file_name = $bfn;
				$path = substr($filter_by_layout, strlen($layer_bean_folder_name));
				$filter_by_layout_permission = UserAuthenticationHandler::$PERMISSION_BELONG_NAME;
				break;
			}
		}
		
	}
}

//echo "bean_name:$bean_name, bean_file_name:$bean_file_name, path:$path, filter_by_layout:$filter_by_layout, filter_by_layout_permission:$filter_by_layout_permission";die();

//Preparing permissions
$do_not_filter_by_layout = array(
	"bean_name" => $bean_name,
	"bean_file_name" => $bean_file_name,
);
include $EVC->getUtilPath("admin_uis_layers_and_permissions");
//echo "<pre>";print_r($layers);die();
?>
