<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "delete");

if (!empty($_GET["class"])) {
	$file_type = "remove_file_class";
	$_POST["r"] = true;//$_POST cannot be empty
	
	include $EVC->getEntityPath("admin/save_php_file_props");
}
else
	die();
?>
