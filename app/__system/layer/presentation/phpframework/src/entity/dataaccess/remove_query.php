<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "delete");

if (!empty($_GET["query_id"]) && !empty($_GET["query_type"])) {
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
