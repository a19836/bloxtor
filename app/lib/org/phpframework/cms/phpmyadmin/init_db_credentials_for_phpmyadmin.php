<?php
//header("X-Frame-Options: SAMEORIGIN");

include get_lib("org.phpframework.cms.phpmyadmin.PhpMyAdminInstallationHandler");
include get_lib("org.phpframework.encryption.CryptoKeyHandler");
include get_lib("org.phpframework.util.web.CookieHandler");

$route = isset($_GET["route"]) ? $_GET["route"] : null;

/* Only for testing
$message = json_encode(array("db_host" => "localhost", "db_username" => "root", "db_password" => "12345"));
$key = CryptoKeyHandler::hexToBin(PhpMyAdminInstallationHandler::PHPMYADMIN_ENCRYPTION_KEY);
$cipher_bin = CryptoKeyHandler::encryptText($message, $key);
$cipher_text = CryptoKeyHandler::binToHex($cipher_bin);
setcookie("pma_creds", $cipher_text);
$_COOKIE["pma_creds"] = $cipher_text;
*/

//set some phpmyadmin settings
$cfg["AllowArbitraryServer"] = true; //If enabled, allows you to log in to arbitrary servers using cookie authentication.
$cfg["AllowThirdPartyFraming"] = true; //allow showing phpmyadmin in an iframe

$i = isset($i) ? $i : 0; //$i comes from the phpmyadmin/config.inc.php

$cfg["Servers"][$i]["auth_type"] = "cookie"; //show login form

//if is logout
if ($route == "/logout") {
	//remove pma_creds cookie
	$_COOKIE["pma_creds"] = null;
	CookieHandler::setCurrentDomainEternalRootSafeCookie("pma_creds", "", -1);
}
else {
	//get pma_creds cookie
	$cipher_text = isset($_COOKIE["pma_creds"]) ? $_COOKIE["pma_creds"] : null;

	if ($cipher_text) {
		$key = CryptoKeyHandler::hexToBin(PhpMyAdminInstallationHandler::PHPMYADMIN_ENCRYPTION_KEY);
		$cipher_bin = CryptoKeyHandler::hexToBin($cipher_text);
		$credentials = CryptoKeyHandler::decryptText($cipher_bin, $key);
		$db_data = json_decode($credentials, true);
		//print_r($db_data);echo $cipher_text;die();
		
		//login automatically
		if ($db_data && !empty($db_data["username"])) {
			$cfg["Servers"][$i]["auth_type"] = "config";
			$cfg["Servers"][$i]["AllowNoPassword"] = false; //don't allow login without password
			
			if (!empty($db_data["host"]))
				$cfg["Servers"][$i]["host"] = $db_data["host"];
			
			if (!empty($db_data["port"]))
				$cfg["Servers"][$i]["port"] = $db_data["port"];
			
			$cfg["Servers"][$i]["user"] = $db_data["username"];
			$cfg["Servers"][$i]["password"] = isset($db_data["password"]) ? $db_data["password"] : null;
		}
	}
}
//echo "<pre>";print_r($cfg["Servers"]);die();

//Only for stand-alone testing
function get_lib($path) {
	$path = strpos($path, "lib.") === 0 ? substr($path, strlen("lib.")) : $path;
	return dirname(dirname(dirname(dirname(__DIR__)))) . "/" . str_replace(".", "/", $path) . ".php";
}
?>
