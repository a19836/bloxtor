<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "delete");

if ($_GET["obj"]) {
	$file_type = "save_obj";
	$_POST["object"] = array();
	$_POST["overwrite"] = 1;
	
	include $EVC->getEntityPath("dataaccess/save");
}
die();
?>
