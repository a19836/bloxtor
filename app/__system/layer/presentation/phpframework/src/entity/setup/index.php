<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$step = $_GET["step"];
$is_inside_of_iframe = !empty($_GET["iframe"]);

switch ($step) {
	case 1: $page = "/setup/terms_and_conditions"; break;
	case 2: $page = "/setup/project_name"; break;
	case 3: $page = "/setup/db"; break;
	case 3.1: $page = "/setup/layers"; break;
	case 4: $page = "/setup/end"; break;
	default: $page = "/setup/terms_and_conditions";
}

$entity_path = $EVC->getEntityPath($page);
include $entity_path;
?>
