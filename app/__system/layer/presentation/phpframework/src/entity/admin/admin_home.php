<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

include $EVC->getEntityPath("admin/choose_available_project");
include $EVC->getEntityPath("admin/choose_available_tutorial");

$presentation = getPresentation($project_url_prefix);

function getPresentation($project_url_prefix) {
	return '<div><img src="' . $project_url_prefix . 'img/adminhome/layers_1.png"/></div>
	<div><img src="' . $project_url_prefix . 'img/adminhome/full_page_request_flow.png"/></div>
	<div><img src="' . $project_url_prefix . 'img/adminhome/layers_2.png"/></div>
	<div><img src="' . $project_url_prefix . 'img/adminhome/layers_3.png"/></div>
	<div><img src="' . $project_url_prefix . 'img/adminhome/layers_4.png"/></div>
	<div><img src="' . $project_url_prefix . 'img/adminhome/layers_5.png"/></div>
	<div><img src="' . $project_url_prefix . 'img/adminhome/layers_6.png"/></div>
	<div><img src="' . $project_url_prefix . 'img/adminhome/layers_7.png"/></div>
	<div><img src="' . $project_url_prefix . 'img/adminhome/layers_8.png"/></div>
	<div><img src="' . $project_url_prefix . 'img/adminhome/deployment_1.png"/></div>
	<div><img src="' . $project_url_prefix . 'img/adminhome/deployment_2.png"/></div>
	<div><img src="' . $project_url_prefix . 'img/adminhome/deployment_3.png"/></div>
	<div><img src="' . $project_url_prefix . 'img/adminhome/deployment_4.png"/></div>';
}
?>
