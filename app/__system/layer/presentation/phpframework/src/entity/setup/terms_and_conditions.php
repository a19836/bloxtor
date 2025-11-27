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

include_once $EVC->getUtilPath("DependenciesInstallationHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

if (!empty($_POST["acceptance"])) {
	$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
	$continue = true;
	
	if (!empty($_POST["dependencies"])) {
		$error_message = isset($error_message) ? $error_message : null;
		$zips = DependenciesInstallationHandler::getDependencyZipFilesToInstall();
		$continue = DependenciesInstallationHandler::installDependencies($dependencies_repo_url, $zips, $error_message);
		
		if (!$continue)
			$error_message = "Error could not download and install dependencies.<br/>Please confirm if you are connected to the internet." . ($error_message ? "<br/>$error_message" : "");
	}
	
	if ($continue) {
		header("location: ?step=2");
		echo '<script>window.location = "?step=2"</script>';
		die();
	}
}
?>
