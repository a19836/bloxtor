<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "delete");

if ($_GET["query_id"] && $_GET["query_type"]) {
	$file_type = "save_query";
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
