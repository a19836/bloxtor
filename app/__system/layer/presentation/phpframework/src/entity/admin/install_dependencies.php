<?php
include_once $EVC->getUtilPath("DependenciesInstallationHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

if (!empty($_POST)) {
	$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
	$continue = true;
	
	if (!empty($_POST["install"])) {
		$error_message = isset($error_message) ? $error_message : null;
		$zips = DependenciesInstallationHandler::getDependencyZipFilesToInstall();
		$continue = DependenciesInstallationHandler::installDependencies($dependencies_repo_url, $zips, $error_message);
		
		if (!$continue)
			$error_message = "Error could not download and install dependencies.<br/>Please confirm if you are connected to the internet." . (!empty($error_message) ? "<br/>$error_message" : "");
	}
	
	if ($continue) {
		DependenciesInstallationHandler::setCookieInstallDependencies(empty($_POST["dependencies"]) ? 0 : 1);
		
		$url_back = $UserAuthenticationHandler->getUrlBack();
		$url_back = $UserAuthenticationHandler->validateUrlBack($url_back) ? $url_back : $project_url_prefix . "admin/";
		
		header("Location: $url_back");
		echo "<script>document.location = '$url_back';</script>";
		die();
	}
}
?>
