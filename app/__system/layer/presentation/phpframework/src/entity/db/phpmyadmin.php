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

include_once get_lib("org.phpframework.cms.phpmyadmin.PhpMyAdminInstallationHandler");
include_once get_lib("org.phpframework.util.web.CookieHandler");
include_once $EVC->getUtilPath("WorkFlowDBHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$layer_bean_folder_name = isset($_GET["layer_bean_folder_name"]) ? $_GET["layer_bean_folder_name"] : null;
$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;

$phpmyadmin_path = $EVC->getWebrootPath($EVC->getCommonProjectName()) . "cms/phpmyadmin/";
$phpmyadmin_enable = PhpMyAdminInstallationHandler::isEnabled($phpmyadmin_path);

if ($bean_name && $phpmyadmin_enable) {
	$layer_object_id = LAYER_PATH . "$layer_bean_folder_name/$bean_name";
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_object_id, "layer", "access");
	
	$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_file_path);
	$DBDriver = $WorkFlowDBHandler->getBeanObject($bean_file_name, $bean_name);
	$db_options = $DBDriver->getOptions();
	
	$json = json_encode($db_options);
	$key = CryptoKeyHandler::hexToBin(PhpMyAdminInstallationHandler::PHPMYADMIN_ENCRYPTION_KEY);
	$cipher_bin = CryptoKeyHandler::encryptText($json, $key);
	$cipher_text = CryptoKeyHandler::binToHex($cipher_bin);
	
	CookieHandler::setCurrentDomainEternalRootSafeCookie("pma_creds", $cipher_text, 1); //1 day for security reasons

	$phpmyadmin_url = $project_common_url_prefix . "cms/phpmyadmin/";
	
	//Very important: phpmyadmin url must be the abs url without symbolic links, otherwise it will be all messed up
	if (!empty($_SERVER["SCRIPT_NAME"])) {
		$script_name = preg_replace("/\/index\.php$/", "/", $_SERVER["SCRIPT_NAME"]); //remove idnex.php at the end of the script name, if exists
		$phpmyadmin_script_name = dirname(dirname($script_name)) . "/" . $EVC->getCommonProjectName() . "/webroot/cms/phpmyadmin/"; //go to __system folder and add "/common/webroot/cms/phpmyadmin/"
		$project_protocol = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? "https://" : "http://"; //Do not add " || $_SERVER['SERVER_PORT'] == 443" bc the ssl port may not be 443 depending of the server configuration
		$phpmyadmin_url = $project_protocol . $_SERVER["HTTP_HOST"] . $phpmyadmin_script_name; //set new abs phpmyadmin
	}
}
?>
