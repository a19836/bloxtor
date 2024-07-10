<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

include $EVC->getEntityPath("admin/admin_citizen");
//echo "<pre>";print_r($layers);die();

if ($layers["presentation_layers"]) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$Layer = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
	$project_dir = dirname($project);
	$project_dir = $project_dir && $project_dir != "." ? "$project_dir/" : "";
	$projects = AdminMenuHandler::getBeanLayerObjs($Layer, $project_dir, 1);
	$project_name = basename($project);
	$projects = array($project_name => $projects[$project_name]);
	//echo "<pre>";print_r($projects);die();
	//echo "<pre>";print_r($project_properties);die();

	foreach ($layers["presentation_layers"] as $layer_name => $layer) 
		$layers["presentation_layers"][$layer_name] = array_merge($layer, $projects);
}
?>
