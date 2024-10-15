<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "delete");

if (!empty($_GET["query_id"]) && !empty($_GET["query_type"])) {
	$file_type = "save_relationship";
	$_POST["object"] = array();
	$_POST["overwrite"] = 1;
	
	$queries_ids = array(
		$_GET["query_type"] => array(
			$_GET["query_id"] => 0
		)
	);
	
	include $EVC->getEntityPath("dataaccess/save");
}
die();
?>
