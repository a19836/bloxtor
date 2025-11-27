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

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

if (!empty($_POST) && $is_local_db) {
	$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
	
	$username = isset($_POST["username"]) ? $_POST["username"] : null;
	$password = isset($_POST["password"]) ? $_POST["password"] : null;
	$permission_table_key = isset($_POST["permission_table_key"]) ? $_POST["permission_table_key"] : null;
	$user_table_key = isset($_POST["user_table_key"]) ? $_POST["user_table_key"] : null;
	$user_type_table_key = isset($_POST["user_type_table_key"]) ? $_POST["user_type_table_key"] : null;
	$object_type_table_key = isset($_POST["object_type_table_key"]) ? $_POST["object_type_table_key"] : null;
	$user_type_permission_table_key = isset($_POST["user_type_permission_table_key"]) ? $_POST["user_type_permission_table_key"] : null;
	$user_user_type_table_key = isset($_POST["user_user_type_table_key"]) ? $_POST["user_user_type_table_key"] : null;
	$login_control_table_key = isset($_POST["login_control_table_key"]) ? $_POST["login_control_table_key"] : null;
	$user_stats_table_key = isset($_POST["user_stats_table_key"]) ? $_POST["user_stats_table_key"] : null;
	$layout_type_table_key = isset($_POST["layout_type_table_key"]) ? $_POST["layout_type_table_key"] : null;
	$layout_type_permission_table_key = isset($_POST["layout_type_permission_table_key"]) ? $_POST["layout_type_permission_table_key"] : null;
	$reserved_db_table_name_table_key = isset($_POST["reserved_db_table_name_table_key"]) ? $_POST["reserved_db_table_name_table_key"] : null;
	
	if (empty($username) || empty($password) || empty($permission_table_key) || empty($user_table_key) || empty($user_type_table_key) || empty($object_type_table_key) || empty($user_type_permission_table_key) || empty($user_user_type_table_key) || empty($login_control_table_key) || empty($user_stats_table_key) || empty($layout_type_table_key) || empty($layout_type_permission_table_key) || empty($reserved_db_table_name_table_key)) {
		$error_message = "You cannot have blank fields. Please fill all the fields with the correct values...";
	}
	else if ($UserAuthenticationHandler->isUserBlocked($username)) {
		$error_message = "You attempted to login multiple times.<br/>Your user is now blocked.";
	}
	else if (!$UserAuthenticationHandler->getUserByUsernameAndPassword($username, $password)) {
		$UserAuthenticationHandler->insertFailedLoginAttempt($username);
		
		$error_message = "Error: Invalid credentials! Please try again...";
	}
	else if ($permission_table_key == CryptoKeyHandler::binToHex($permission_table_encryption_key)
	   	&& $user_table_key == CryptoKeyHandler::binToHex($user_table_encryption_key)
	   	&& $user_type_table_key == CryptoKeyHandler::binToHex($user_type_table_encryption_key)
	   	&& $object_type_table_key == CryptoKeyHandler::binToHex($object_type_table_encryption_key)
	   	&& $user_type_permission_table_key == CryptoKeyHandler::binToHex($user_type_permission_table_encryption_key)
	   	&& $user_user_type_table_key == CryptoKeyHandler::binToHex($user_user_type_table_encryption_key)
	   	&& $login_control_table_key == CryptoKeyHandler::binToHex($login_control_table_encryption_key)
	   	&& $user_stats_table_key == CryptoKeyHandler::binToHex($user_stats_table_encryption_key)
	   	&& $layout_type_table_key == CryptoKeyHandler::binToHex($layout_type_table_encryption_key)
	   	&& $layout_type_permission_table_key == CryptoKeyHandler::binToHex($layout_type_permission_table_encryption_key)
	   	&& $reserved_db_table_name_table_key == CryptoKeyHandler::binToHex($reserved_db_table_name_table_encryption_key)
	   ) {
	   	$UserAuthenticationHandler->resetFailedLoginAttempts($username);
	   	
		$authentication_config_file_path = $EVC->getConfigPath("authentication");
		
		if (file_exists($authentication_config_file_path)) {
			//GENERATING NEW KEYS
			$new_permission_table_key = getNewEncryptionKey();
			$new_user_table_key = getNewEncryptionKey();
			$new_user_type_table_key = getNewEncryptionKey();
			$new_object_type_table_key = getNewEncryptionKey();
			$new_user_type_permission_table_key = getNewEncryptionKey();
			$new_user_user_type_table_key = getNewEncryptionKey();
			$new_login_control_table_key = getNewEncryptionKey();
			$new_user_stats_table_key = getNewEncryptionKey();
			$new_layout_type_table_key = getNewEncryptionKey();
			$new_layout_type_permission_table_key = getNewEncryptionKey();
			$new_reserved_db_table_name_table_key = getNewEncryptionKey();
			
			$code = file_get_contents($authentication_config_file_path);
			
			//CHANGING PERMISSION KEY
			if (empty($error_message) && $UserAuthenticationHandler->changePermissionTableEncryptionKey( CryptoKeyHandler::hexToBin($new_permission_table_key) )) {
				$code = str_replace('$permission_table_encryption_key = CryptoKeyHandler::hexToBin("' . $permission_table_key . '");', '$permission_table_encryption_key = CryptoKeyHandler::hexToBin("' . $new_permission_table_key . '");', $code);
				
				if (file_put_contents($authentication_config_file_path, $code) === false) {
					$error_message = "There was an error trying to change the DB keys for the table: 'permission'. Please try again...";
					$UserAuthenticationHandler->changePermissionTableEncryptionKey( CryptoKeyHandler::hexToBin($permission_table_key) );
				}
			}
			else {
				$error_message = "There was an error trying to change the DB keys for the table: 'permission'. Please try again...";
			}
			
			//CHANGING USER KEY
			if (empty($error_message) && $UserAuthenticationHandler->changeUserTableEncryptionKey( CryptoKeyHandler::hexToBin($new_user_table_key) )) {
				$code = str_replace('$user_table_encryption_key = CryptoKeyHandler::hexToBin("' . $user_table_key . '");', '$user_table_encryption_key = CryptoKeyHandler::hexToBin("' . $new_user_table_key . '");', $code);
				
				if (file_put_contents($authentication_config_file_path, $code) === false) {
					$error_message = "There was an error trying to change the DB keys for the table: 'user'. Please try again...";
					$UserAuthenticationHandler->changeUserTableEncryptionKey( CryptoKeyHandler::hexToBin($user_table_key) );
				}
			}
			else {
				$error_message = "There was an error trying to change the DB keys for the table: 'user'. Please try again...";
			}
			
			//CHANGING USER TYPE KEY
			if (empty($error_message) && $UserAuthenticationHandler->changeUserTypeTableEncryptionKey( CryptoKeyHandler::hexToBin($new_user_type_table_key) )) {
				$code = str_replace('$user_type_table_encryption_key = CryptoKeyHandler::hexToBin("' . $user_type_table_key . '");', '$user_type_table_encryption_key = CryptoKeyHandler::hexToBin("' . $new_user_type_table_key . '");', $code);
				
				if (file_put_contents($authentication_config_file_path, $code) === false) {
					$error_message = "There was an error trying to change the DB keys for the table: 'user_type'. Please try again...";
					$UserAuthenticationHandler->changeUserTypeTableEncryptionKey( CryptoKeyHandler::hexToBin($user_type_table_key) );
				}
			}
			else {
				$error_message = "There was an error trying to change the DB keys for the table: 'user_type'. Please try again...";
			}
			
			//CHANGING OBJECT TYPE KEY
			if (empty($error_message) && $UserAuthenticationHandler->changeObjectTypeTableEncryptionKey( CryptoKeyHandler::hexToBin($new_object_type_table_key) )) {
				$code = str_replace('$object_type_table_encryption_key = CryptoKeyHandler::hexToBin("' . $object_type_table_key . '");', '$object_type_table_encryption_key = CryptoKeyHandler::hexToBin("' . $new_object_type_table_key . '");', $code);
				
				if (file_put_contents($authentication_config_file_path, $code) === false) {
					$error_message = "There was an error trying to change the DB keys for the table: 'object_type'. Please try again...";
					$UserAuthenticationHandler->changeObjectTypeTableEncryptionKey( CryptoKeyHandler::hexToBin($object_type_table_key) );
				}
			}
			else {
				$error_message = "There was an error trying to change the DB keys for the table: 'object_type'. Please try again...";
			}
			
			//CHANGING USER TYPE PERMISSION KEY
			if (empty($error_message) && $UserAuthenticationHandler->changeUserTypePermissionTableEncryptionKey( CryptoKeyHandler::hexToBin($new_user_type_permission_table_key) )) {
				$code = str_replace('$user_type_permission_table_encryption_key = CryptoKeyHandler::hexToBin("' . $user_type_permission_table_key . '");', '$user_type_permission_table_encryption_key = CryptoKeyHandler::hexToBin("' . $new_user_type_permission_table_key . '");', $code);
				
				if (file_put_contents($authentication_config_file_path, $code) === false) {
					$error_message = "There was an error trying to change the DB keys for the table: 'user_type_permission'. Please try again...";
					$UserAuthenticationHandler->changeUserTypePermissionTableEncryptionKey( CryptoKeyHandler::hexToBin($user_type_permission_table_key) );
				}
			}
			else {
				$error_message = "There was an error trying to change the DB keys for the table: 'user_type_permission'. Please try again...";
			}
			
			//CHANGING USER USER TYPE KEY
			if (empty($error_message) && $UserAuthenticationHandler->changeUserUserTypeTableEncryptionKey( CryptoKeyHandler::hexToBin($new_user_user_type_table_key) )) {
				$code = str_replace('$user_user_type_table_encryption_key = CryptoKeyHandler::hexToBin("' . $user_user_type_table_key . '");', '$user_user_type_table_encryption_key = CryptoKeyHandler::hexToBin("' . $new_user_user_type_table_key . '");', $code);
				
				if (file_put_contents($authentication_config_file_path, $code) === false) {
					$error_message = "There was an error trying to change the DB keys for the table: 'user_user_type'. Please try again...";
					$UserAuthenticationHandler->changeUserUserTypeTableEncryptionKey( CryptoKeyHandler::hexToBin($user_user_type_table_key) );
				}
			}
			else {
				$error_message = "There was an error trying to change the DB keys for the table: 'user_user_type'. Please try again...";
			}
			
			//CHANGING LOGIN CONTROL KEY
			if (empty($error_message) && $UserAuthenticationHandler->changeLoginControlTableEncryptionKey( CryptoKeyHandler::hexToBin($new_login_control_table_key) )) {
				$code = str_replace('$login_control_table_encryption_key = CryptoKeyHandler::hexToBin("' . $login_control_table_key . '");', '$login_control_table_encryption_key = CryptoKeyHandler::hexToBin("' . $new_login_control_table_key . '");', $code);
				
				if (file_put_contents($authentication_config_file_path, $code) === false) {
					$error_message = "There was an error trying to change the DB keys for the table: 'login_control'. Please try again...";
					$UserAuthenticationHandler->changeLoginControlTableEncryptionKey( CryptoKeyHandler::hexToBin($login_control_table_key) );
				}
			}
			else {
				$error_message = "There was an error trying to change the DB keys for the table: 'login_control'. Please try again...";
			}
			
			//CHANGING USER STATS KEY
			if (empty($error_message) && $UserAuthenticationHandler->changeUserStatsTableEncryptionKey( CryptoKeyHandler::hexToBin($new_user_stats_table_key) )) {
				$code = str_replace('$user_stats_table_encryption_key = CryptoKeyHandler::hexToBin("' . $user_stats_table_key . '");', '$user_stats_table_encryption_key = CryptoKeyHandler::hexToBin("' . $new_user_stats_table_key . '");', $code);
				
				if (file_put_contents($authentication_config_file_path, $code) === false) {
					$error_message = "There was an error trying to change the DB keys for the table: 'user_stats'. Please try again...";
					$UserAuthenticationHandler->changeUserStatsTableEncryptionKey( CryptoKeyHandler::hexToBin($user_stats_table_key) );
				}
			}
			else {
				$error_message = "There was an error trying to change the DB keys for the table: 'user_stats'. Please try again...";
			}
			
			//CHANGING LAYOUT TYPE KEY
			if (empty($error_message) && $UserAuthenticationHandler->changeLayoutTypeTableEncryptionKey( CryptoKeyHandler::hexToBin($new_layout_type_table_key) )) {
				$code = str_replace('$layout_type_table_encryption_key = CryptoKeyHandler::hexToBin("' . $layout_type_table_key . '");', '$layout_type_table_encryption_key = CryptoKeyHandler::hexToBin("' . $new_layout_type_table_key . '");', $code);
				
				if (file_put_contents($authentication_config_file_path, $code) === false) {
					$error_message = "There was an error trying to change the DB keys for the table: 'layout_type'. Please try again...";
					$UserAuthenticationHandler->changeLayoutTypeTableEncryptionKey( CryptoKeyHandler::hexToBin($layout_type_table_key) );
				}
			}
			else {
				$error_message = "There was an error trying to change the DB keys for the table: 'layout_type'. Please try again...";
			}
			
			//CHANGING LAYOUT TYPE PERMISSION KEY
			if (empty($error_message) && $UserAuthenticationHandler->changeLayoutTypePermissionTableEncryptionKey( CryptoKeyHandler::hexToBin($new_layout_type_permission_table_key) )) {
				$code = str_replace('$layout_type_permission_table_encryption_key = CryptoKeyHandler::hexToBin("' . $layout_type_permission_table_key . '");', '$layout_type_permission_table_encryption_key = CryptoKeyHandler::hexToBin("' . $new_layout_type_permission_table_key . '");', $code);
				
				if (file_put_contents($authentication_config_file_path, $code) === false) {
					$error_message = "There was an error trying to change the DB keys for the table: 'layout_type_permission'. Please try again...";
					$UserAuthenticationHandler->changeLayoutTypePermissionTableEncryptionKey( CryptoKeyHandler::hexToBin($layout_type_permission_table_key) );
				}
			}
			else {
				$error_message = "There was an error trying to change the DB keys for the table: 'layout_type_permission'. Please try again...";
			}
			
			//CHANGING RESERVED DB TABLE NAME KEY
			if (empty($error_message) && $UserAuthenticationHandler->changeReservedDBTableNameTableEncryptionKey( CryptoKeyHandler::hexToBin($new_reserved_db_table_name_table_key) )) {
				$code = str_replace('$reserved_db_table_name_table_encryption_key = CryptoKeyHandler::hexToBin("' . $reserved_db_table_name_table_key . '");', '$reserved_db_table_name_table_encryption_key = CryptoKeyHandler::hexToBin("' . $new_reserved_db_table_name_table_key . '");', $code);
				
				if (file_put_contents($authentication_config_file_path, $code) === false) {
					$error_message = "There was an error trying to change the DB keys for the table: 'reserved_db_table_name'. Please try again...";
					$UserAuthenticationHandler->changeReservedDBTableNameTableEncryptionKey( CryptoKeyHandler::hexToBin($reserved_db_table_name_table_key) );
				}
			}
			else {
				$error_message = "There was an error trying to change the DB keys for the table: 'reserved_db_table_name'. Please try again...";
			}
			
			if (empty($error_message)) {
				$status_message = "DB Keys changed successfully...";
			}
		}
		else {
			$error_message = "Config Authentication file doesn't exist. Please talk with the SysAdmin for further information...";
		}
	}
	else {
		$error_message = "Error: Invalid keys match! Please try again...";
	}
}

$data = array(
	"username" => isset($username) ? $username : null,
	"password" => isset($password) ? $password : null,
	"permission_table_key" => isset($permission_table_key) ? $permission_table_key : null,
	"user_table_key" => isset($user_table_key) ? $user_table_key : null,
	"user_type_table_key" => isset($user_type_table_key) ? $user_type_table_key : null,
	"object_type_table_key" => isset($object_type_table_key) ? $object_type_table_key : null,
	"user_type_permission_table_key" => isset($user_type_permission_table_key) ? $user_type_permission_table_key : null,
	"user_user_type_table_key" => isset($user_user_type_table_key) ? $user_user_type_table_key : null,
	"login_control_table_key" => isset($login_control_table_key) ? $login_control_table_key : null,
	"user_stats_table_key" => isset($user_stats_table_key) ? $user_stats_table_key : null,
	"layout_type_table_key" => isset($layout_type_table_key) ? $layout_type_table_key : null,
	"layout_type_permission_table_key" => isset($layout_type_permission_table_key) ? $layout_type_permission_table_key : null,
	"reserved_db_table_name_table_key" => isset($reserved_db_table_name_table_key) ? $reserved_db_table_name_table_key : null,
);

function getNewEncryptionKey() {
	$rand = (int)rand(0, 20);
	
	for ($i = 0; $i < $rand; $i++)
		CryptoKeyHandler::getKey();
	
	return CryptoKeyHandler::getHexKey();
}
?>
