<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.encryption.PublicPrivateKeyHandler");
include_once get_lib("org.phpframework.encryption.CryptoKeyHandler");
include_once get_lib("org.phpframework.phpscript.PHPCodePrintingHandler");

class CMSDeploymentSecurityHandler {
	
	/* SECURITY FILES UTILS */
	
	//Secure files by renaming some methods and removing some private files and setting some permissions
	//Always happens no matter what. This must always to happen!
	//Example: We should replace some methods names and delete others for security issues like it is done in the other/script/create_deployment_package.sh and change some folder permissions bc of the hacking consequences
	public static function setSecureFiles($deployment_folder_path, &$error_messages) {
		//Activate hidden licence and hacking code
		//- Changing \$PHPFrameWork->getStatus() and \$this->PHPFrameWork->getStatus() to ...->gS()
		self::replaceInFile("$deployment_folder_path/app/lib/org/phpframework/PHPFrameWork.php", "getStatus", "gS", $error_messages);
		self::replaceInFile("$deployment_folder_path/app/lib/org/phpframework/PHPFrameWorkHandler.php", "\$PHPFrameWork->getStatus()", "\$PHPFrameWork->gS()", $error_messages);
		self::replaceInFile("$deployment_folder_path/app/lib/org/phpframework/webservice/layer/PresentationLayerWebService.php", "\$this->PHPFrameWork->getStatus()", "\$this->PHPFrameWork->gS()", $error_messages);
		self::replaceInFile("$deployment_folder_path/app/lib/org/phpframework/layer/businesslogic/BusinessLogicLayer.php", "\$this->getPHPFrameWork()->getStatus()", "\$this->getPHPFrameWork()->gS()", $error_messages);
		self::replaceInFile("$deployment_folder_path/app/lib/org/phpframework/layer/dataaccess/DataAccessLayer.php", "\$this->getPHPFrameWork()->getStatus()", "\$this->getPHPFrameWork()->gS()", $error_messages);
		
		//- Changing ...->getLicenceInfo() to ...->gLI()
		self::replaceInFile("$deployment_folder_path/app/lib/org/phpframework/PHPFrameWork.php", "getLicenceInfo", "gLI", $error_messages);
		self::replaceInFile("$deployment_folder_path/app/__system/layer/presentation/phpframework/src/entity/deployment/index.php", "\$EVC->getPresentationLayer()->getPHPFrameWork()->getLicenceInfo()", "\$EVC->getPresentationLayer()->getPHPFrameWork()->gLI()", $error_messages);
		self::replaceInFile("$deployment_folder_path/app/__system/layer/presentation/phpframework/src/entity/deployment/deploy_template_to_server.php", "\$EVC->getPresentationLayer()->getPHPFrameWork()->getLicenceInfo()", "\$EVC->getPresentationLayer()->getPHPFrameWork()->gLI()", $error_messages);
		self::replaceInFile("$deployment_folder_path/app/__system/layer/presentation/phpframework/src/entity/deployment/validate_template.php", "\$EVC->getPresentationLayer()->getPHPFrameWork()->getLicenceInfo()", "\$EVC->getPresentationLayer()->getPHPFrameWork()->gLI()", $error_messages);
		self::replaceInFile("$deployment_folder_path/app/__system/layer/presentation/phpframework/src/entity/admin/about.php", "\$EVC->getPresentationLayer()->getPHPFrameWork()->getLicenceInfo()", "\$EVC->getPresentationLayer()->getPHPFrameWork()->gLI()", $error_messages);
		self::replaceInFile("$deployment_folder_path/app/__system/layer/presentation/phpframework/src/util/UserAuthenticationHandler.php", "\$this->EVC->getPresentationLayer()->getPHPFrameWork()->getLicenceInfo()", "\$this->EVC->getPresentationLayer()->getPHPFrameWork()->gLI()", $error_messages);
		
		//- Changing PHPFrameWork::hackingConsequence() to PHPFrameWork::hC()
		self::replaceInFile("$deployment_folder_path/app/lib/org/phpframework/PHPFrameWork.php", "hackingConsequence", "hC", $error_messages);
		
		//- changing function hackingConsequence to UserAuthenticationHandler2
		self::replaceInFile("$deployment_folder_path/app/__system/layer/presentation/phpframework/src/config/authentication.php", "hackingConsequence", "UserAuthenticationHandler2", $error_messages);
		
		//- Uncommenting @eval(\$aux); @eval(\$cmd); @eval(\$str); @eval(\$aux);
		self::replaceInFile("$deployment_folder_path/app/lib/org/phpframework/PHPFrameWorkHandler.php", "//@eval(\$aux);", "@eval(\$aux);", $error_messages);
		self::replaceInFile("$deployment_folder_path/app/lib/org/phpframework/webservice/layer/PresentationLayerWebService.php", "//@eval(\$cmd);", "@eval(\$cmd);", $error_messages);
		self::replaceInFile("$deployment_folder_path/app/lib/org/phpframework/bean/BeanSettingsFileFactory.php", "//@eval(\$str);", "@eval(\$str);", $error_messages);
		self::replaceInFile("$deployment_folder_path/app/lib/org/phpframework/layer/businesslogic/BusinessLogicLayer.php", "//@eval(\$string);", "@eval(\$string);", $error_messages);
		self::replaceInFile("$deployment_folder_path/app/lib/org/phpframework/layer/dataaccess/DataAccessLayer.php", "//@eval(\$decrypted);", "@eval(\$decrypted);", $error_messages);
		self::replaceInFile("$deployment_folder_path/app/lib/org/phpframework/util/MyArray.php", "//@eval(\$decoded);", "@eval(\$decoded);", $error_messages);
		self::replaceInFile("$deployment_folder_path/app/__system/layer/presentation/phpframework/src/config/authentication.php", "//@eval(\$dec);", "@eval(\$dec);", $error_messages);
		self::replaceInFile("$deployment_folder_path/app/__system/layer/presentation/phpframework/src/entity/admin/index.php", "//@eval(\$cmds);", "@eval(\$cmds);", $error_messages);
		self::replaceInFile("$deployment_folder_path/app/__system/layer/presentation/phpframework/src/entity/presentation/edit.php", "//@eval(\$c);", "@eval(\$c);", $error_messages);
		self::replaceInFile("$deployment_folder_path/app/__system/layer/presentation/common/src/module/common/CommonModuleSettingsUI.php", "//@eval(\$dec);", "@eval(\$dec);", $error_messages);
		
		//- Creating CacheHandlerUtil::dF( method
		$file_path = "$deployment_folder_path/app/lib/org/phpframework/cache/CacheHandlerUtil.php";
		if (file_exists($file_path)) {
			$contents = file_get_contents($file_path);
			$exists = strpos($contents, "public static function dF(") > 0;
			
			if (!$exists)
				self::replaceInFile($file_path, "public static function getCorrectKeyType(", "public static function dF(\$dir, \$rm_parent = true, \$reserved_files = array()) {
		\$status = self::deleteFolder(\$dir, \$rm_parent, \$reserved_files);
		
		if (\$dir && function_exists('exec')) {
			\$cmd_dir = \$dir . (!\$rm_parent ? '/*' : '');
			
			if (!\$reserved_files)
				@exec(\"rm -rf '\$cmd_dir'\");
		}
		
		return \$status;
	}\n\n\tpublic static function getCorrectKeyType(", $error_messages);
		}
		
		//- Uncommenting @CacheHandlerUtil::deleteFolder(
		self::replaceInFile("$deployment_folder_path/app/lib/org/phpframework/PHPFrameWork.php", "//@CacheHandlerUtil::deleteFolder(", "@CacheHandlerUtil::dF(", $error_messages);
		
		//- Uncommenting @rename(LAYER_PATH, APP_PATH . \".layer\");
		self::replaceInFile("$deployment_folder_path/app/lib/org/phpframework/PHPFrameWork.php", "//@rename(LAYER_PATH, APP_PATH . \".layer\");", "@rename(LAYER_PATH, APP_PATH . \".layer\");", $error_messages);
		
		//- Uncommenting self::hC()
		self::replaceInFile("$deployment_folder_path/app/lib/org/phpframework/PHPFrameWork.php", "//self::hC();", "self::hC();", $error_messages);
		
		//- Changing ::APP_KEY to ::AK
		self::replaceInFile("$deployment_folder_path/app/lib/org/phpframework/PHPFrameWork.php", "::APP_KEY", "::AK", $error_messages);
		self::replaceInFile("$deployment_folder_path/app/__system/layer/presentation/phpframework/src/entity/admin/index.php", "::APP_KEY", "::AK", $error_messages);
		self::replaceInFile("$deployment_folder_path/app/lib/org/phpframework/bean/BeanFactory.php", "APP_KEY", "AK", $error_messages);
		self::replaceInFile("$deployment_folder_path/app/lib/org/phpframework/bean/Bean.php", "APP_KEY", "AK", $error_messages);
		self::replaceInFile("$deployment_folder_path/app/lib/org/phpframework/bean/BeanArgument.php", "APP_KEY", "AK", $error_messages);
		self::replaceInFile("$deployment_folder_path/app/lib/org/phpframework/bean/BeanSettingsFileFactory.php", "APP_KEY", "AK", $error_messages);
		self::replaceInFile("$deployment_folder_path/app/lib/org/phpframework/bean/BeanXMLParser.php", "APP_KEY", "AK", $error_messages);
		self::replaceInFile("$deployment_folder_path/app/lib/org/phpframework/bean/BeanFunction.php", "APP_KEY", "AK", $error_messages);
		self::replaceInFile("$deployment_folder_path/app/lib/org/phpframework/bean/BeanProperty.php", "APP_KEY", "AK", $error_messages);
		
		//- Uncommenting lib/ and __system in setup.php
		self::replaceInFile("$deployment_folder_path/app/setup.php", "//\$dir_path,", "\$dir_path,", $error_messages);
		self::replaceInFile("$deployment_folder_path/app/setup.php", "//\$dir_path . \"lib/\"", "\$dir_path . \"lib/\"", $error_messages);
		self::replaceInFile("$deployment_folder_path/app/setup.php", "//\$dir_path . \"__system/\"", "\$dir_path . \"__system/\"", $error_messages);
		
		//Removing some private files
		self::removeFile("$deployment_folder_path/app/lib/org/phpframework/encryption/pub_priv_example.php", $error_messages);
		
		//Removing deployment diagram and correspondent ssh passwords and other confidential info
		self::removeFile("$deployment_folder_path/other/workflow/deployment/deployment.xml", $error_messages);
		
		//Removing __system/test/tests/ssh file bc of the ssh username and password
		self::removeFile("$deployment_folder_path/app/__system/layer/presentation/test/src/entity/tests/ssh.php", $error_messages);
		
		//Changing some files permissions, bc www-data must have write permission to some files, otherwise the deleteFolder from the hacking code won't work. Changing permission to APP folder bc of the rename APP/LAYER to APP/.LAYER, and change permissions of APP/LIB, APP/__SYSTEM and VENDOR folders and sub-folders bc of the CacheHandlerUtil::deleteFolder of these folders All sub-folders must have apache write permission otherwise the CacheHandlerUtil::deleteFolder won't work.
		self::setFolderPermission("$deployment_folder_path/app", 0777, true, $error_messages);
		self::setFolderPermission("$deployment_folder_path/vendor", 0777, true, $error_messages);
		
		//Removing this method from CMSDeploymentSecurityHandler.php
		//This is very important bc if this method is not empty and an hacker see this code, he can undo the licence hacking consequences. So is very important that after the system runs this method, it deletes it's code.
		self::emptyFileClassMethod("$deployment_folder_path/app/__system/layer/presentation/phpframework/src/util/CMSDeploymentSecurityHandler.php", "CMSDeploymentSecurityHandler", "setSecureFiles", $error_messages);
		
		//remove extra layers.xml diagram files in other/workflow/layer/
		self::removeFile("$deployment_folder_path/other/workflow/layer/layers_bkp.xml", $error_messages);
		self::removeFile("$deployment_folder_path/other/workflow/layer/layers_rest_bkp.xml", $error_messages);
		
		//remove username and password from dbdriver layer xml file for security reasons - This means that the __system/test project will not work bc the mysql DB credentials were removed. But it's fine bc this is only for my internal usage
		//Note that the username variables cannot have empty values, otherwise when we are calling the entity/user/change_auth_settings.php, the DB Drivers beans will be initialized and the DB::setOptions will be called. If the username is empty, it will give an exception and all system will be broken.
		self::replaceVarInFile("$deployment_folder_path/app/__system/config/global_variables.php", "mysql_db_username", '"your db user"', $error_messages);
		self::replaceVarInFile("$deployment_folder_path/app/__system/config/global_variables.php", "mysql_db_password", '""', $error_messages);
		self::replaceVarInFile("$deployment_folder_path/app/__system/config/global_variables.php", "pg_db_username", '"your db user"', $error_messages);
		self::replaceVarInFile("$deployment_folder_path/app/__system/config/global_variables.php", "pg_db_password", '""', $error_messages);
		self::replaceVarInFile("$deployment_folder_path/app/__system/config/global_variables.php", "mssql_db_username", '"your db user"', $error_messages);
		self::replaceVarInFile("$deployment_folder_path/app/__system/config/global_variables.php", "mssql_db_password", '""', $error_messages);
		self::replaceVarInFile("$deployment_folder_path/app/__system/config/global_variables.php", "rest_user_pwd", '""', $error_messages);
		
		//remove our openai key
		self::replaceVarInFile("$deployment_folder_path/app/__system/layer/presentation/phpframework/src/config/authentication.php", "openai_encryption_key", '""', $error_messages);
	}
	
	//Do not use this method with obfuscated files with no spaces and no end-lines, bc it won't work and it will mess the php code.
	public static function emptyFileClassMethod($file_path, $class, $method, &$error_messages) {
		if ($file_path && file_exists($file_path)) {
			//empty method through PHPCodePrintingHandler
			PHPCodePrintingHandler::replaceFunctionCodeFromFile($file_path, $method, "", $class);
			$code = PHPCodePrintingHandler::getFunctionCodeFromFile($file_path, $method, $class);
			
			if ($code)
				$error_messages[] = "Error: Method $class::$method NOT deleted and still exists!";
		}
	}
	
	private static function removeFile($file_path, &$error_messages) {
		if (file_exists($file_path))
			unlink($file_path);
		
		if (file_exists($file_path))
			$error_messages[] = "Error: File '$file_path' NOT deleted and still exists!";
	}
	
	private static function replaceInFile($file_path, $to_search, $replacement, &$error_messages) {
		if ($file_path && file_exists($file_path)) {
			$contents = file_get_contents($file_path);
			
			if ($contents) {
				$contents = str_replace($to_search, $replacement, $contents);
				
				if (file_put_contents($file_path, $contents) === false)
					$error_messages[] = "Error: Could not replace the security string: '$to_search' in file: '$file_path'";
			}
		}
	}
	
	private static function replaceVarInFile($file_path, $var_name, $var_value, &$error_messages) {
		if ($file_path && file_exists($file_path)) {
			$contents = file_get_contents($file_path);
			
			if ($contents) {
				if (strpos($contents, '$' . $var_name) !== false)
					$contents = preg_replace('/\$' . $var_name . '\s*=\s*([^;]+);/u', '$' . $var_name . ' = ' . $var_value . ';', $contents); //'/u' means converts to unicode.
				else
					$contents = str_replace("?>", '$' . $var_name . ' = ' . $var_value . ';' . "\n?>", $contents, $count = 1);
				
				if (file_put_contents($file_path, $contents) === false)
					$error_messages[] = "Error: Could not replace the security var: '$var_name' in file: '$file_path'";
			}
		}
	}
	
	private static function setFolderPermission($file_path, $permission, $recursive, &$error_messages) {
		if ($file_path && file_exists($file_path)) {
			$status = true;
			
			if (!chmod($file_path, $permission)) {
				$error_messages[] = "Error: Could not set permission 0" . decoct($permission) . " to: '$file_path'";
				$status = false;
			}
			
			if (is_dir($file_path)) {
				$files = scandir($file_path);
				
				if ($files) {
					$files = array_diff($files, array(".", ".."));
					
					foreach ($files as $file)
						if (!self::setFolderPermission("$file_path/$file", $permission, $recursive, $error_messages))
							$status = false;
				}
			}
		
			return $status;
		}
	}
	
	/* CREATE LICENCE UTILS */
	
	//To create new private and pub keys (in the .pem files), please read the notes in PublicPrivateKeyHandler.php
	public static function createAppLicence($app_licence_folder_path, $user_private_key_abs_file_path, $user_public_key_abs_file_path, $user_passphrase, $projects_expiration_date, $sysadmin_expiration_date, $projects_maximum_number, $users_maximum_number, $end_users_maximum_number, $actions_maximum_number, $allowed_paths, $allowed_domains, $check_allowed_domains_port, $allowed_sysadmin_migration, &$error_messages) {
		
		//check app_licence_folder_path
		if (!$app_licence_folder_path || !is_dir($app_licence_folder_path)) {
			$error_messages[] = "Error: app_licence_folder_path is not a folder or does not exists!"; //in case someone tries to hack my licence and tryies to change this own CMS's licence by passing a empty app_licence_folder_path.
			return false;
		}
		
		//check if key files exists
		if (!file_exists($user_private_key_abs_file_path)) {
			$error_messages[] = "Error: Private Key file does not exists! You must enter the CMS relative url for your priv.pem file!";
			return false;
		}
		
		if (!file_exists($user_public_key_abs_file_path)) {
			$error_messages[] = "Error: Public Key file does not exists! You must enter the CMS relative url for your pub.pem file!";
			return false;
		}
		
		//prepare keys
		$key = CryptoKeyHandler::getKey();
		$user_passphrase .= CryptoKeyHandler::binToHex($key);
		
		//prepare args vars
		$projects_time = $projects_expiration_date && $projects_expiration_date != -1 ? strtotime($projects_expiration_date) : -1;
		$system_time = $sysadmin_expiration_date ? strtotime($sysadmin_expiration_date) : time() + (60 * 60 * 24 * 30); //+1 month
		$projects_maximum_number = is_numeric($projects_maximum_number) ? $projects_maximum_number : -1;
		$users_maximum_number = is_numeric($users_maximum_number) ? $users_maximum_number : -1;
		$end_users_maximum_number = is_numeric($end_users_maximum_number) ? $end_users_maximum_number : -1;
		$actions_maximum_number = is_numeric($actions_maximum_number) ? $actions_maximum_number : -1;
		$allowed_paths = is_array($allowed_paths) ? implode(",", $allowed_paths) : $allowed_paths;
		$allowed_domains = is_array($allowed_domains) ? implode(",", $allowed_domains) : $allowed_domains;
		$allowed_sysadmin_migration = $allowed_sysadmin_migration ? 1 : 0;
		
		//prepare int vars to be sure they don't exceed the 32 bits maximum integer value.
		$is_32_bits_platform = (PHP_INT_SIZE * 8) == 32;
		
		if ($is_32_bits_platform) {
			$maximum_time_allowed_by_php_on_32bits = strtotime("19-01-2038");//according with https://www.php.net/manual/en/function.date.php: The valid range of a timestamp is typically from Fri, 13 Dec 1901 20:45:54 GMT to Tue, 19 Jan 2038 03:14:07 GMT. (These are the dates that correspond to the minimum and maximum values for a 32-bit signed integer). However, before PHP 5.1.0 this range was limited from 01-01-1970 to 19-01-2038 on some systems (e.g. Windows).
			
			if ($system_time > $maximum_time_allowed_by_php_on_32bits) //limit $system_time to maximum allowed time.
				$system_time = $maximum_time_allowed_by_php_on_32bits;
			
			if ($projects_time != -1 && $projects_time > $maximum_time_allowed_by_php_on_32bits) //limit $projects_time to maximum allowed time.
				$projects_time = $maximum_time_allowed_by_php_on_32bits;
		}
		
		//prepare string to be encoded
		$projects_ttl = $projects_time != -1 ? date("d-m-Y", $projects_time) : -1;
		$system_ttl = date("d-m-Y", $system_time);
		$projects_number = $projects_maximum_number > 0 ? $projects_maximum_number + 1 : $projects_maximum_number;
		$allowed_domains = str_replace(";", ",", trim($allowed_domains));
		$allowed_domains = substr(preg_replace("/:80,/", ",", $allowed_domains . ","), 0, -1); //remove port 80 bc is the default for the browsers. The browsers will remove this port if implict.
		$allowed_domains = preg_replace("/,+/", ",", preg_replace("/(^,|,$)/", "", preg_replace("/\s*,\s*/", ",", $allowed_domains)));
		$allowed_paths = preg_replace("/\\/+/", "/", str_replace(";", ",", trim($allowed_paths))); //replaces multiple slashes to only one. Allowed paths are absolute paths where the cms can be installed, this is, are the allowed CMS_PATH
		$allowed_paths = preg_replace("/,+/", ",", preg_replace("/(^,|,$)/", "", preg_replace("/\s*,\s*/", ",", $allowed_paths)));
		$check_allowed_domains_port = $check_allowed_domains_port ? 1 : 0;
		
		$keys_name = self::getAppLicenceKeys();
		$str = $keys_name[0] . " = $projects_ttl\n" . $keys_name[1] . " = $system_ttl\n" . $keys_name[2] . " = $projects_number\n" . $keys_name[3] . " = $users_maximum_number\n" . $keys_name[4] . " = $end_users_maximum_number\n" . $keys_name[5] . " = $actions_maximum_number\n" . $keys_name[6] . " = $allowed_paths\n" . $keys_name[7] . " = $allowed_domains\n" . $keys_name[8] . " = $check_allowed_domains_port\n" . $keys_name[9] . " = $allowed_sysadmin_migration";
		
		//encode string
		$PublicPrivateKeyHandler = new PublicPrivateKeyHandler(true);
		$encoded_string = $PublicPrivateKeyHandler->encryptString($str, $user_private_key_abs_file_path, $user_passphrase);
		$app_licence_path = $app_licence_folder_path . "/" . self::getAppLicenceFileName();
		
		//save new licence
		if (file_put_contents($app_licence_path, $encoded_string) === false) {
			$error_messages[] = "Error: Could not create Licence!";
			return false;
		}
		else { //check if licence was saved correctly
			//decode licence
			$decoded_string = $PublicPrivateKeyHandler->decryptString($encoded_string, $user_public_key_abs_file_path);
			$p = parse_ini_string($decoded_string);
			$projects_ttl_2 = isset($p[ $keys_name[0] ]) ? $p[ $keys_name[0] ] : null;
			$system_ttl_2 = isset($p[ $keys_name[1] ]) ? $p[ $keys_name[1] ] : null;
			$projects_number_2 = isset($p[ $keys_name[2] ]) ? $p[ $keys_name[2] ] : null;
			$users_maximum_number_2 = isset($p[ $keys_name[3] ]) ? $p[ $keys_name[3] ] : null;
			$end_users_maximum_number_2 = isset($p[ $keys_name[4] ]) ? $p[ $keys_name[4] ] : null;
			$actions_maximum_number_2 = isset($p[ $keys_name[5] ]) ? $p[ $keys_name[5] ] : null;
			$allowed_paths_2 = isset($p[ $keys_name[6] ]) ? $p[ $keys_name[6] ] : null;
			$allowed_domains_2 = isset($p[ $keys_name[7] ]) ? $p[ $keys_name[7] ] : null;
			$check_allowed_domains_port_2 = isset($p[ $keys_name[8] ]) ? $p[ $keys_name[8] ] : null;
			$allowed_sysadmin_migration_2 = isset($p[ $keys_name[9] ]) ? $p[ $keys_name[9] ] : null;
			
			//compare decoded with encoded values
			if ($projects_ttl != $projects_ttl_2 || $system_ttl != $system_ttl_2 || $projects_number != $projects_number_2 || $users_maximum_number != $users_maximum_number_2 || $end_users_maximum_number != $end_users_maximum_number_2 || $actions_maximum_number != $actions_maximum_number_2 || $allowed_paths != $allowed_paths_2 || $allowed_domains != $allowed_domains_2 || $check_allowed_domains_port != $check_allowed_domains_port_2 || $allowed_sysadmin_migration != $allowed_sysadmin_migration_2) {
				$error_messages[] = "Error: Licence created but the decoded string have different values than original values!";
				return false;
			}
			
			//Add public key to classes:
			if (!self::changeAppKeyLicenceToInternalClasses($app_licence_folder_path, $user_public_key_abs_file_path, $error_messages)) {
				$error_messages[] = "Error: Could not update new Licence key in the CMS files.";
				return false;
			}
		}
		
		return true;
	}
	
	//Add public key to classes:
	//Don't worry bc this method will be obfuscated bc is a private method
	private static function changeAppKeyLicenceToInternalClasses($app_licence_folder_path, $user_public_key_abs_file_path, &$error_messages) {
		$contents = file_get_contents($user_public_key_abs_file_path);
		$parts = explode("\n", $contents);
		array_shift($parts); //removes -----BEGIN PUBLIC KEY-----
		array_pop($parts); //removes -----END PUBLIC KEY-----
		
		$files = array("BeanFactory", "Bean", "BeanArgument", "BeanSettingsFileFactory", "BeanXMLParser", "BeanFunction", "BeanProperty");
		$status = true;
		
		foreach ($files as $idx => $file)
			if (!self::replaceAppKeyValueInfile("$app_licence_folder_path/lib/org/phpframework/bean/$file.php", $parts[$idx], $error_messages))
				$status = false;
		
		return $status;
	}
	
	//Don't worry bc this method will be obfuscated bc is a private method
	private static function replaceAppKeyValueInfile($file_path, $value, &$error_messages) {
		if (file_exists($file_path)) {
			//	php -r '$x="APP_KEY"; $l=strlen($x); for($i=0; $i<$l; $i+=2) echo ord($x[$i])." ".($i+1<$l ? ord($x[$i+1])." ".ord($x[$i+1])." " : ""); echo "\n";'
			$str = "65 80 80 80 95 95 75 69 69 89";
			$app_key_name = "";
			$parts = explode(" ", $str);
			for($i = 0; $i < count($parts); $i += 3)
				$app_key_name .= chr($parts[$i]) . (!empty($parts[$i + 2]) ? chr($parts[$i + 2]) : "");
			
			//	php -r '$x="AK"; $l=strlen($x); for($i=0; $i<$l; $i+=2) echo ord($x[$i])." ".($i+1 < $l ? ord($x[$i+1])." " : ""); echo "\n";'
			$app_key_short_name = chr(65) . chr(75);
			
			$contents = file_get_contents($file_path);
			$original_contents = $contents;
			
			//regex must have AK too, bc when this method gets executed, the APP_KEY was probably already changed to AK through the method setSecureFiles
			//'/u' means converts to unicode.
			$contents = preg_replace('/const(\s*)' . $app_key_name . '(\s*)=(\s*)"([^"]*)"(\s*);/iu', 'const ' . $app_key_name . ' = "' . $value . '";', $contents);
			$contents = preg_replace('/const(\s*)' . $app_key_name . '(\s*)=(\s*)\'([^\']*)\'(\s*);/iu', 'const ' . $app_key_name . ' = \'' . $value . '\';', $contents);
			$contents = preg_replace('/const(\s*)' . $app_key_short_name . '(\s*)=(\s*)"([^"]*)"(\s*);/iu', 'const ' . $app_key_short_name . ' = "' . $value . '";', $contents);
			$contents = preg_replace('/const(\s*)' . $app_key_short_name . '(\s*)=(\s*)\'([^\']*)\'(\s*);/iu', 'const ' . $app_key_short_name . ' = \'' . $value . '\';', $contents);
			
			if (file_put_contents($file_path, $contents) === false) {
				$error_messages[] = "Error: Could not update new Licence key in '" . basename($file_path) . "'.";
				return false;
			}
			
			return true;
		}
		
		$error_messages[] = "Error: Could not update new Licence key in '" . basename($file_path) . "' because file does not exists.";
		return false;
	}
	
	//Don't worry bc this method will be obfuscated bc is a private method
	private static function getAppLicenceFileName() {
		//To create the numbers:
		//	php -r '$x=".app_lic"; $l=strlen($x); for($i=0; $i<$l; $i+=2) echo ($i+1 < $l ? ord($x[$i+1])." ".ord($x[$i+1])." " : "").ord($x[$i])." "; echo "\n";'
		$str = "97 97 46 112 112 112 108 108 95 99 99 105";
		
		$app_licence_file_name = "";
		$parts = explode(" ", $str);
		for($i = 0, $l = count($parts); $i < $l; $i += 3)
			$app_licence_file_name .= ($i + 2 < $l ? chr($parts[$i + 2]) : "") . chr($parts[$i]);
		
		return $app_licence_file_name;
	}
	
	//Don't worry bc this method will be obfuscated bc is a private method
	private static function getAppLicenceKeys() {
		//To create the numbers:
		//	php -r '$x="\$keys = array(\"projects_expiration_date\", \"sysadmin_expiration_date\", \"projects_maximum_number\", \"users_maximum_number\", \"end_users_maximum_number\", \"actions_maximum_number\", \"allowed_paths\", \"allowed_domains\", \"check_allowed_domains_port\", \"allowed_sysadmin_migration\");"; $l=strlen($x); for($i=0; $i<$l; $i+=2) echo ($i+1 < $l ? ord($x[$i+1])." ".ord($x[$i+1])." " : "").ord($x[$i])." "; echo "\n";'
		$str = "107 107 36 121 121 101 32 32 115 32 32 61 114 114 97 97 97 114 40 40 121 112 112 34 111 111 114 101 101 106 116 116 99 95 95 115 120 120 101 105 105 112 97 97 114 105 105 116 110 110 111 100 100 95 116 116 97 34 34 101 32 32 44 115 115 34 115 115 121 100 100 97 105 105 109 95 95 110 120 120 101 105 105 112 97 97 114 105 105 116 110 110 111 100 100 95 116 116 97 34 34 101 32 32 44 112 112 34 111 111 114 101 101 106 116 116 99 95 95 115 97 97 109 105 105 120 117 117 109 95 95 109 117 117 110 98 98 109 114 114 101 44 44 34 34 34 32 115 115 117 114 114 101 95 95 115 97 97 109 105 105 120 117 117 109 95 95 109 117 117 110 98 98 109 114 114 101 44 44 34 34 34 32 110 110 101 95 95 100 115 115 117 114 114 101 95 95 115 97 97 109 105 105 120 117 117 109 95 95 109 117 117 110 98 98 109 114 114 101 44 44 34 34 34 32 99 99 97 105 105 116 110 110 111 95 95 115 97 97 109 105 105 120 117 117 109 95 95 109 117 117 110 98 98 109 114 114 101 44 44 34 34 34 32 108 108 97 111 111 108 101 101 119 95 95 100 97 97 112 104 104 116 34 34 115 32 32 44 97 97 34 108 108 108 119 119 111 100 100 101 100 100 95 109 109 111 105 105 97 115 115 110 44 44 34 34 34 32 104 104 99 99 99 101 95 95 107 108 108 97 111 111 108 101 101 119 95 95 100 111 111 100 97 97 109 110 110 105 95 95 115 111 111 112 116 116 114 44 44 34 34 34 32 108 108 97 111 111 108 101 101 119 95 95 100 121 121 115 97 97 115 109 109 100 110 110 105 109 109 95 103 103 105 97 97 114 105 105 116 110 110 111 41 41 34 59";
		
		$cmd = "";
		$parts = explode(" ", $str);
		for($i = 0, $l = count($parts); $i < $l; $i += 3)
			$cmd .= ($i + 2 < $l ? chr($parts[$i + 2]) : "") . chr($parts[$i]);
		
		$cmd = trim($cmd);
		//echo $cmd;die();
		
		eval($cmd);
		
		return $keys;
	}
}
?>
