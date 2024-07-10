<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$file_type = "save_file_includes";

if (!isset($_POST["object"]))
	$_POST["object"] = array();

include $EVC->getEntityPath("admin/save_php_file_props");
?>
