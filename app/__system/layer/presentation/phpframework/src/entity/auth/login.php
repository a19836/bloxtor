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

$popup = isset($_GET["popup"]) ? $_GET["popup"] : null;

$username = isset($_GET["username"]) ? $_GET["username"] : "";
$password = isset($_GET["password"]) ? $_GET["password"] : "";
$agreement = !empty($_COOKIE["lla"]) || $popup ? 1 : 0;//lla: login_license_agreement

if (!empty($_POST)) {
	$username = isset($_POST["username"]) ? $_POST["username"] : null;
	$password = isset($_POST["password"]) ? $_POST["password"] : null;
	$agreement = isset($_POST["agreement"]) ? $_POST["agreement"] : null;
	
	if (empty($username) || empty($password))
		$error_message = "Username or Password cannot be undefined. Please try again...";
	else if (empty($agreement))
		$error_message = "You must accept the terms and conditions in order to proceed. Please try again...";
	else if ($UserAuthenticationHandler->isUserBlocked($username))
		$error_message = "You attempted to login multiple times.<br/>Your user is now blocked.";
	else if ($UserAuthenticationHandler->login($username, $password)) {
		CookieHandler::setSafeCookie("lla", $agreement, 0, "/", CSRFValidator::$COOKIES_EXTRA_FLAGS);
		
		if ($popup) {
			echo "1";
			die();
		}
		else {
			//check if dependencies are installed
			$installed = isset($_COOKIE[DependenciesInstallationHandler::$INSTALL_DEPENDENCIES_VARIABLE_NAME]);
			
			if (!$installed) {
				$dependencies = DependenciesInstallationHandler::getDependencyZipFilesToInstall();
				$installed = DependenciesInstallationHandler::isDependencyInstalled($dependencies[ key($dependencies) ]);
				
				//if user does not have permission to install dependencies, ignore this step.
				if (!$installed && !$UserAuthenticationHandler->isPresentationFilePermissionAllowed($EVC->getEntityPath("admin/install_dependencies"), "access"))
					$installed = true;
			}
			
			//prepare url back
			$url_back = $UserAuthenticationHandler->getUrlBack();
			
			//if url back is the setup, ignore the install dependencies, since they will be installed in the setup
			if (strpos($url_back . "/", $project_url_prefix . "setup/") !== false)
				$installed = true;
			
			//redirect to install dependencies page
			if (!$installed)
				$url_back = $project_url_prefix . "admin/install_dependencies"; //redirect to page to install dependencies
			else { //redirect to previous page
				$url_back = $UserAuthenticationHandler->validateUrlBack($url_back) ? $url_back : $project_url_prefix . "admin/";
				
				//if admin panel, sets some default cookies
				if (stripos($url_back, $project_url_prefix . "admin/") === 0) {
					//for admin_advanced
					isset($_GET["admin_page"]) && CookieHandler::setCurrentDomainEternalRootSafeCookie("default_page", $_GET["admin_page"]);
					isset($_GET["filter_by_layout"]) && CookieHandler::setCurrentDomainEternalRootSafeCookie("filter_by_layout", $_GET["filter_by_layout"]);
					
					//for admin_simple
					isset($_GET["bean_name"]) && CookieHandler::setCurrentDomainEternalRootSafeCookie("selected_bean_name", $_GET["bean_name"]);
					isset($_GET["bean_file_name"]) && CookieHandler::setCurrentDomainEternalRootSafeCookie("selected_bean_file_name", $_GET["bean_file_name"]);
					isset($_GET["project"]) && CookieHandler::setCurrentDomainEternalRootSafeCookie("selected_project", $_GET["project"]);
				}
			}
			
			header("Location: $url_back");
			echo "<script>document.location = '$url_back';</script>";
			die();
		}
	}
	else {
		$UserAuthenticationHandler->insertFailedLoginAttempt($username);
		
		$error_message = "Username or Password invalid. Please try again...";
	}
}

$login_data = array(
	"username" => $username,
	"password" => $password,
	"agreement" => $agreement,
);
?>
