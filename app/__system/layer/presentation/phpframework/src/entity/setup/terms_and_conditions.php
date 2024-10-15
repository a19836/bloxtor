<?php
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
