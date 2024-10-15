<?php
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");
$choose_available_project_url = "{$project_url_prefix}admin/choose_available_project?redirect_path=admin";

$default_page = isset($_GET["default_page"]) ? $_GET["default_page"] : null;
//print_r($_GET);die();

$layers_beans = AdminMenuHandler::getLayers($user_global_variables_file_path);
//echo "<pre>";print_r($layers_beans);die();

if ($layers_beans && !empty($layers_beans["presentation_layers"])) {
	if (!empty($_GET["filter_by_layout"])) {
		foreach ($layers_beans["presentation_layers"] as $bn => $bfn) {
			$layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerBeanFolderName($user_beans_folder_path . $bfn, $bn, $user_global_variables_file_path);
			
			if (strpos($_GET["filter_by_layout"], $layer_bean_folder_name . "/") === 0) {
				$bean_name = $bn;
				$bean_file_name = $bfn;
				$project = substr($_GET["filter_by_layout"], strlen($layer_bean_folder_name) + 1); //+1 bc of '/'
				
				CookieHandler::setCurrentDomainEternalRootSafeCookie("selected_bean_name", $bean_name);
				CookieHandler::setCurrentDomainEternalRootSafeCookie("selected_bean_file_name", $bean_file_name);
				CookieHandler::setCurrentDomainEternalRootSafeCookie("selected_project", $project);
			}
		}
	}
	else {
		if ($default_page)
			CookieHandler::setCurrentDomainEternalRootSafeCookie("default_page", $default_page);
		else if (!empty($_COOKIE["default_page"]))
			$default_page = $_COOKIE["default_page"];

		if (!empty($_GET["bean_name"])) {
			$bean_name = $_GET["bean_name"];
			CookieHandler::setCurrentDomainEternalRootSafeCookie("selected_bean_name", $bean_name);
		}
		else if (!empty($_COOKIE["selected_bean_name"]))
			$bean_name = $_COOKIE["selected_bean_name"];

		if (!empty($_GET["bean_file_name"])) {
			$bean_file_name = $_GET["bean_file_name"];
			CookieHandler::setCurrentDomainEternalRootSafeCookie("selected_bean_file_name", $bean_file_name);
		}
		else if (!empty($_COOKIE["selected_bean_file_name"]))
			$bean_file_name = $_COOKIE["selected_bean_file_name"];

		if (!empty($_GET["project"])) {
			$project = $_GET["project"];
			CookieHandler::setCurrentDomainEternalRootSafeCookie("selected_project", $project);
		}
		else if (!empty($_COOKIE["selected_project"]))
			$project = $_COOKIE["selected_project"];
	}
}

if (empty($bean_name) || empty($bean_file_name) || empty($project)) {
	header("Location: $choose_available_project_url");
	echo "<script>document.location = '$choose_available_project_url';</script>";
	die();
}
else if (!empty($layers_beans["presentation_layers"][$bean_name]) && $layers_beans["presentation_layers"][$bean_name] == $bean_file_name) { //preparing filter_by_layout
	$layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerBeanFolderName($user_beans_folder_path . $bean_file_name, $bean_name, $user_global_variables_file_path);
	
	$filter_by_layout = "$layer_bean_folder_name/" . preg_replace("/\/+$/", "", $project); //remove last slash from $path
	$filter_by_layout_permission = UserAuthenticationHandler::$PERMISSION_BELONG_NAME;
}
//echo "default_page:$default_page|<br>project:$project|filter_by_layout:$filter_by_layout";die();

//Preparing permissions
//Note that if a project doesn't have any layout_type_permission created, the presentation and DB layers should not be removed from the $layers variable. This is the "Citizen UI" and is for a specific project, so even if there isn't a layout_type_permissions defined, it should always show the correspondent presentation layer and default DB layer.
$do_not_filter_by_layout = array(
	"bean_name" => $bean_name,
	"bean_file_name" => $bean_file_name,
);
include $EVC->getUtilPath("admin_uis_layers_and_permissions");
//echo "<pre>";print_r($layers);die();

$projects = null;
	
if (!empty($layers["presentation_layers"]))
	foreach ($layers["presentation_layers"] as $layer_name => $layer)
		if (isset($layer["properties"]["bean_name"]) && $layer["properties"]["bean_name"] == $bean_name && isset($layer["properties"]["bean_file_name"]) && $layer["properties"]["bean_file_name"] == $bean_file_name) {
			$projects = $presentation_projects[$layer_name];
			//echo "<pre>";print_r($projects);die();
			break;
		}

//if project doesn't exist, forward it to choose project.
if (!$projects || empty($projects[$project])) {
	header("Location: $choose_available_project_url");
	echo "<script>document.location='$choose_available_project_url';</script>";
	die();
}
?>
