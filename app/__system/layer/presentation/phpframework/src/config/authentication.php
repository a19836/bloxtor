<?php
include $EVC->getUtilPath("UserAuthenticationHandler");
//UserAuthenticationHandler::$USER_SESSION_ID_VARIABLE_NAME = "system_session_id";
//UserAuthenticationHandler::$URL_BACK_VARIABLE_NAME = "system_url_back";

$authentication_db_path = CMS_PATH . "other/authdb/";
$login_page_url = $project_url_prefix . "auth/login";
$non_authorized_page_url = $project_url_prefix . "auth/non_authorized";

//To Create a new key, please execute the following code: echo CryptoKeyHandler::getHexKey(); die();
$permission_table_encryption_key = CryptoKeyHandler::hexToBin("844faeba196e8b7f7343e89794d74475");
$user_table_encryption_key = CryptoKeyHandler::hexToBin("38a87149628ee9e4026a13657dfd23b8");
$user_type_table_encryption_key = CryptoKeyHandler::hexToBin("c9cad729150afaf815c2a4448a182d4b");
$object_type_table_encryption_key = CryptoKeyHandler::hexToBin("84c23fd2c1870bf1f904fccd5f1aaa4b");
$user_type_permission_table_encryption_key = CryptoKeyHandler::hexToBin("697ce095f4fcfe2100a0250ac032128c");
$user_user_type_table_encryption_key = CryptoKeyHandler::hexToBin("40a2933f39a49ee1c7daa53da191cf61");
$login_control_table_encryption_key = CryptoKeyHandler::hexToBin("4bb7897aabd4ba13d1f4cae4ff7d9013");
$user_stats_table_encryption_key = CryptoKeyHandler::hexToBin("da37a08da547e69c6b2d78efd2ad8034");
$layout_type_table_encryption_key = CryptoKeyHandler::hexToBin("c9cad729150afaf815c2a4448a182d4b");
$layout_type_permission_table_encryption_key = CryptoKeyHandler::hexToBin("697ce095f4fcfe2100a0250ac032128c");
$reserved_db_table_name_table_encryption_key = CryptoKeyHandler::hexToBin("a11f5697056ad77017a0bae9054d2f3b");

$openai_encryption_key = "";

$maximum_failed_attempts = 3;
$user_blocked_expired_time = 3600;//60 * 60 = 3600 secs = 1 hour
$login_expired_time = 86400;//60 * 60 * 24 = 86400 secs = 1 day
$is_local_db = true;

$UserAuthenticationHandler = new UserAuthenticationHandler($EVC, $authentication_db_path, $login_page_url, $non_authorized_page_url);
$UserAuthenticationHandler->setEncryptionKeys(array(
	"permission_table_encryption_key" => $permission_table_encryption_key, 
	"user_table_encryption_key" => $user_table_encryption_key, 
	"user_type_table_encryption_key" => $user_type_table_encryption_key, 
	"object_type_table_encryption_key" => $object_type_table_encryption_key, 
	"user_type_permission_table_encryption_key" => $user_type_permission_table_encryption_key, 
	"user_user_type_table_encryption_key" => $user_user_type_table_encryption_key, 
	"login_control_table_encryption_key" => $login_control_table_encryption_key, 
	"user_stats_table_encryption_key" => $user_stats_table_encryption_key,
	"layout_type_table_encryption_key" => $layout_type_table_encryption_key, 
	"layout_type_permission_table_encryption_key" => $layout_type_permission_table_encryption_key, 
	"reserved_db_table_name_table_encryption_key" => $reserved_db_table_name_table_encryption_key, 
));
$UserAuthenticationHandler->setAuthSettings($maximum_failed_attempts, $user_blocked_expired_time, $login_expired_time, $is_local_db);

//Check if licence maximum number of projects was hacked
if (!function_exists("hackingConsequence")) {
	function hackingConsequence($UserAuthenticationHandler) {
		if (!defined("PROJECTS_CHECKED") || PROJECTS_CHECKED != 123 || !method_exists("UserAuthenticationHandler", "checkUsersMaxNum") || !$UserAuthenticationHandler->isAllowedDomain() || !$UserAuthenticationHandler->isAllowedPath()) {
			$key = CryptoKeyHandler::hexToBin("d888fe1e8" . "2e6a47d" . "66885dd5771b3eaa");
			
			/*To create new file with code:
				$key = CryptoKeyHandler::hexToBin("d888fe1e82e6a47d66885dd5771b3eaa");
				$code = "@rename(LAYER_PATH, APP_PATH . \".layer\");@CacheHandlerUtil::deleteFolder(VENDOR_PATH);@CacheHandlerUtil::deleteFolder(LIB_PATH, false, array(realpath(LIB_PATH . \"cache/CacheHandlerUtil.php\")));@CacheHandlerUtil::deleteFolder(SYSTEM_PATH);@PHPFrameWork::hC();";
				$cipher_text = CryptoKeyHandler::encryptText($code, $key);
				echo "\n\n".CryptoKeyHandler::binToHex($cipher_text) . "\n\n";
			*/
			$code = CryptoKeyHandler::hexToBin("e0685f6a97fac0117b8120fcf424351f1d81abff3d08a6dc5f9f6ce927486b13a84af66393d72162ed911d53e036ec9bc367489c64376c9cf155e7125adeda3447a0a12d14d913df120491bcba40dc95827a3063006caaa7d81937691b52b5add5b53dd90f585669ce575527c85bd67c782455a31daeffe70b70e63455024348075756445a8b2937b18c672c454729fd660fc1b7b84b9d9e6c2c4478c418188e945ed7eab243f32200154241dac3efdbb34b6bc4a22ea57b14778060c4fb39e92b8ef9fd9bbd99ddaf1ed0fdd7c98c661b2b09b648f2e45ca0a2cfb54316be95b2c5793cd0db05a4c35ad49f6e34cb0f9915cbd5b238c2083e09d454cc6ff3886149b4c8be201efe66286514a1e94609c9bb28b7fbe5bea11182b7a9a1536de7be8c66c424c302e0c5cea5d1d2858a2938f730e6f9875f997381e251259ddffa");
			$dec = CryptoKeyHandler::decryptText($code, $key);
			
			//@eval($dec);
			die(1);
		}
	}
}

hackingConsequence($UserAuthenticationHandler);
UserAuthenticationHandler::checkUsersMaxNum($UserAuthenticationHandler);
?>
