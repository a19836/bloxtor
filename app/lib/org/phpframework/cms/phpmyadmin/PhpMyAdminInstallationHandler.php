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

class PhpMyAdminInstallationHandler {
	
	const PHPMYADMIN_ENCRYPTION_KEY = "5735dc60a42d263f84c105b986d53445";
	
	public static function hackPhpMyAdminInstallation($phpmyadmin_path) {
		$status = false;
		
		if (!$phpmyadmin_path || !is_dir($phpmyadmin_path))
			launch_exception(new Exception("PhpMyAdmin installation doesn't exists!"));
		
		$relative_path = self::getInitDBCredentialsRelativeCode($phpmyadmin_path);
		$config_path = $phpmyadmin_path . "/config.inc.php";
		
		if (file_exists($config_path)) {
			$contents = file_get_contents($config_path);
			$hack_exists = preg_match("/include_once ". preg_quote($relative_path, "/") . ";/", $contents);
			
			if (!$hack_exists) {
				$contents .= "<?php
include_once $relative_path;
?>";
				$contents = preg_replace('/\?>\s*<\?php\s*/', "", $contents);
				$status = file_put_contents($config_path, $contents) !== false;
			}
			else
				$status = true;
		}
		else {
			$contents = self::getConfigIncContents($relative_path);
			$status = file_put_contents($config_path, $contents) !== false;
		}
		
		//optional
		$tmp_path = $phpmyadmin_path . "/tmp/";
		
		if (is_dir($tmp_path))
			chmod($tmp_path, 0755); //give permission to tmp folder
		
		return $status;
	}
	
	public static function isEnabled($phpmyadmin_path) {
		$status = false;
		
		if ($phpmyadmin_path && is_dir($phpmyadmin_path)) {
			$config_path = $phpmyadmin_path . "/config.inc.php";
			
			if (file_exists($config_path)) {
				$contents = file_get_contents($config_path);
				$relative_path = self::getInitDBCredentialsRelativeCode($phpmyadmin_path);
				$hack_exists = preg_match("/include_once ". preg_quote($relative_path, "/") . ";/", $contents);
				
				$status = $hack_exists;
			}
		}
		
		return $status;
	}
	
	private static function getInitDBCredentialsRelativeCode($phpmyadmin_path) {
		$phpmyadmin_path = preg_replace("/\/+/", "/", $phpmyadmin_path);
		$relative_path = substr($phpmyadmin_path, strlen(CMS_PATH));
		$relative_path = preg_replace("/(^\/|\/$)/", "", $relative_path);
		$cnt = substr_count($relative_path, '/') + 1; //+1 bc we remove the '/' at the end of the relative_path
		$relative_path = "__DIR__ . '/" . str_repeat("../", $cnt) . "app/lib/org/phpframework/cms/phpmyadmin/init_db_credentials_for_phpmyadmin.php'";
		
		return $relative_path;
	}
	
	private static function getConfigIncContents($init_db_credentials_for_phpmyadmin_relative_code) {
		$blowfish_secret = bin2hex(random_bytes(32));
		
		return '<?php
/**
 * phpMyAdmin sample configuration, you can use it as base for
 * manual configuration. For easier setup you can use setup/
 *
 * All directives are explained in documentation in the doc/ folder
 * or at <https://docs.phpmyadmin.net/>.
 */

declare(strict_types=1);

/**
 * This is needed for cookie based authentication to encrypt the cookie.
 * Needs to be a 32-bytes long string of random bytes. See FAQ 2.10.
 * Eg:
 * 	php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
 */
$cfg["blowfish_secret"] = hex2bin("' . $blowfish_secret . '");

/**
 * Servers configuration
 */
$i = 0;

/**
 * First server
 */
$i++;
/* Authentication type */
$cfg["Servers"][$i]["auth_type"] = "cookie";

/* Server parameters */
$cfg["Servers"][$i]["compress"] = false;
$cfg["Servers"][$i]["AllowNoPassword"] = false;

/**
 * Directories for saving/loading files from server
 */
$cfg["UploadDir"] = "";
$cfg["SaveDir"] = "";

include_once ' . $init_db_credentials_for_phpmyadmin_relative_code . ';
?>';
	}
}
?>
