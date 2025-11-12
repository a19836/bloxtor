<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.phpscript.PHPCodePrintingHandler");

class LaravelInstallationHandler {
	
	const LARAVEL_TO_FRAMEWORK_TYPES = array("pgsql" => "pg", "sqlsrv" => "mssql");
	
	public static function hackLaravelInstallation($laravel_path, $system_project_url_prefix, $db_details = null, &$error_message = null) {
		$status = true;
		
		if (!$laravel_path || !is_dir($laravel_path))
			launch_exception(new Exception("Laravel installation doesn't exists!"));
		
		//set htaccess
		$htaccess_path = "$laravel_path/.htaccess";
		$contents = self::getProjectHtaccess();
		
		if (file_exists($htaccess_path))
			$contents = rtrim(file_get_contents($htaccess_path)) . "\n\n" . $contents;
		
		if (file_put_contents($htaccess_path, $contents) === false)
			$status = false;
		
		//prepare config/app.php
		$relative_laravel_path = preg_replace("/\/+/", "/", preg_replace("/(^\/+|\/+$)/", "", substr($laravel_path, strlen(APP_PATH)))); //remove '/' from start and end positions and duplicates.
		$relative_laravel_cnt = count(explode("/", $relative_laravel_path));
		$init_app_url_for_laravel_path = "__DIR__ . '/../" . str_repeat("../", $relative_laravel_cnt) . substr(__DIR__ . "/init_app_url_for_laravel.php", strlen(APP_PATH)) . "'";
		$config_app_path = "$laravel_path/config/app.php";
		
		if (file_exists($config_app_path)) {
			$contents = file_get_contents($config_app_path);
			
			//include init_app_url_for_laravel.php
			$contents = preg_replace("/<\?(php)?/", "<?$1\nif (file_exists($init_app_url_for_laravel_path))\n\tinclude_once $init_app_url_for_laravel_path;\n", $contents, 1);
			
			//set 'url' => getAppUrl(),
			if (preg_match("/('url'|\"url\")\s*=>\s*/", $contents, $matches, PREG_OFFSET_CAPTURE)) {
				$start = $matches[0][1];
				$end = self::getArrayItemLineEndPosition($contents, $start);
				
				if ($end) {
					$l = strlen($matches[0][0]);
					$replacement = substr($contents, $start + $l, $end - $start - $l);
					$contents = substr($contents, 0, $start) . "'url' => function_exists('getAppUrl') ? getAppUrl() : $replacement," . substr($contents, $end + 1);
				}
			}
			
			//set 'asset_url' => getAppUrl(),
			if (preg_match("/('asset_url'|\"asset_url\")\s*=>\s*/", $contents, $matches, PREG_OFFSET_CAPTURE)) {
				$start = $matches[0][1];
				$end = self::getArrayItemLineEndPosition($contents, $start);
				
				if ($end) {
					$l = strlen($matches[0][0]);
					$replacement = substr($contents, $start + $l, $end - $start - $l);
					$contents = substr($contents, 0, $start) . "'asset_url' => function_exists('getAppUrl') ? getAppUrl() : $replacement," . substr($contents, $end + 1);
				}
			}
			
			if (file_put_contents($config_app_path, $contents) === false)
				$status = false;
		}
		
		//In the vendor/laravel/framework/src/Illuminate/Foundation/helpers.php change the 'url' function to prepend with the configureUrlPath function
		$vendor_helpers_path = "$laravel_path/vendor/laravel/framework/src/Illuminate/Foundation/helpers.php";
		
		if (file_exists($vendor_helpers_path)) {
			$contents = file_get_contents($vendor_helpers_path);
			
			if (preg_match("/function\s(url)\s*\(/", $contents, $matches, PREG_OFFSET_CAPTURE)) {
				$pos = $matches[0][1];
				$pos = strpos($contents, "{", $pos + 1);
				
				if ($pos !== false) {
					$contents = substr($contents, 0, $pos + 1) . "\n\t\tif (function_exists('configureUrlPath'))\n\t\t\tconfigureUrlPath(\$path);\n" . substr($contents, $pos + 1);
					
					if (file_put_contents($vendor_helpers_path, $contents) === false)
						$status = false;
				}
			}
		}
		
		//In the vendor/laravel/framework/src/Illuminate/Routing/Route.php change the '__construct' function to prepend with the configureUrlPath function
		$vendor_route_path = "$laravel_path/vendor/laravel/framework/src/Illuminate/Routing/Route.php";
		
		if (file_exists($vendor_route_path)) {
			$contents = file_get_contents($vendor_route_path);
			
			if (preg_match("/function\s(__construct)\s*\(/", $contents, $matches, PREG_OFFSET_CAPTURE)) {
				$pos = $matches[0][1];
				$pos = strpos($contents, "{", $pos + 1);
				
				if ($pos !== false) {
					$contents = substr($contents, 0, $pos + 1) . "\n\t\tif (function_exists('configureUrlPath'))\n\t\t\tconfigureUrlPath(\$uri);\n" . substr($contents, $pos + 1);
					
					if (file_put_contents($vendor_route_path, $contents) === false)
						$status = false;
				}
			}
		}
		
		//prepare .env file
		$env_path = "$laravel_path/.env";
		
		if (file_exists($env_path)) {
			$contents = file_get_contents($env_path);
			
			//set APP_URL
			$app_url = strpos($system_project_url_prefix, "/__system/") !== false ? strstr($system_project_url_prefix, "/__system/", true) . "/" : $system_project_url_prefix;
			$app_url .= substr($laravel_path, strlen(LAYER_PATH)) . "/";
			$app_url = preg_replace("/\/+/", "/", $app_url);
			$app_url = preg_replace("/:\//", "://", $app_url, 1);
			
			$contents = preg_replace("/APP_URL=[^\n]*/", "APP_URL=$app_url", $contents);
			
			//set db details
			if ($db_details) {
				foreach ($db_details as $key => $value) 
					if ($value !== null) {
						$key = strtoupper($key);
						$contents = preg_replace("/$key=[^\n]*/", "$key=$value", $contents);
					}
			}
			
			if (file_put_contents($env_path, $contents) === false)
				$status = false;
			else if ($db_details && !self::testDBConnection($laravel_path, true, $error_message)) //create DB if not yet created
				$status = false;
		}
		
		//set files permissions
		if (!self::setFolderPermissions("$laravel_path/bootstrap/cache/", 0777) || !self::setFolderPermissions("$laravel_path/storage/", 0777))
			$status = false;
		
		return $status;
	}
	
	public static function testDBConnection($laravel_path, $create_db = true, &$error_message = null) {
		$db_details = self::getDBDetails($laravel_path);
		
		if ($db_details && !empty($db_details["DB_CONNECTION"])) {
			$all_driver_labels = DB::getAllDriverLabelsByType();
			$db_connection = $db_details["DB_CONNECTION"];
			$db_type = isset(self::LARAVEL_TO_FRAMEWORK_TYPES[$db_connection]) ? self::LARAVEL_TO_FRAMEWORK_TYPES[$db_connection] : $db_connection;
			
			if ($db_type) { //connect with DB
				$DBDriver = DB::createDriverByType($db_type);
				$db_options = array(
					"host" => $db_details["DB_HOST"],
					"port" => $db_details["DB_PORT"],
					"db_name" => $db_details["DB_DATABASE"],
					"username" => $db_details["DB_USERNAME"],
					"password" => $db_details["DB_PASSWORD"]
				);
				$DBDriver->setOptions($db_options);
				
				try {
					$connected = @$DBDriver->connect();
					return true;
				}
				catch (Exception $e) {
					$error_message = $e ? (!empty($e->problem) ? $e->problem . PHP_EOL : "") . $e->getMessage() : "";
				}
				
				$exception = null;

				try {
					$connected = @$DBDriver->connect();
				}
				catch (Exception $e) {
					$exception = $e;
				}

				//tryies to create DB if not exists yet
				if (!$connected || $exception) {
					$exception = null;
				
					try {
						$db_name = $db_options["db_name"];
						$created = $DBDriver->createDB($db_name);
						$connected = $created && $DBDriver->isDBSelected() && $DBDriver->getSelectedDB() == $db_name;
					}
					catch (Exception $e) {
						$exception = $e;
					}
				}

				if (!$connected || $exception) {
					$msg = $exception ? (!empty($exception->problem) ? $exception->problem . PHP_EOL : "") . $exception->getMessage() : "";
					$error_message = "Error: Could not connect to $db_type!" . ($msg ? PHP_EOL . $msg : "");
				}
				else if ($connected)
					return true;
			}
		}
		
		$error_message = "Error: Wrong DB credentials!";
		return false;
	}
	
	public static function getDBDetails($laravel_path) {
		$db_details = array();
		$env_path = "$laravel_path/.env";
		
		if (file_exists($env_path)) {
			$contents = file_get_contents($env_path);
			$vars = array("DB_CONNECTION", "DB_HOST", "DB_PORT", "DB_DATABASE", "DB_USERNAME", "DB_PASSWORD");
			
			foreach ($vars as $key) 
				if (preg_match("/$key\s*=([^\n]*)/", $contents, $match, PREG_OFFSET_CAPTURE))
					$db_details[$key] = trim(str_replace(array("'", '"'), "", $match[1][0]));
		}
		
		return $db_details;
	}
	
	public static function createLaravelServiceFile($laravel_path, $common_service_file_path) {
		$service_file_path = __DIR__ . "/LaravelProjectService.php";
		
		if (!file_exists($service_file_path))
			return false;
		
		$contents = file_get_contents($service_file_path);
		
		//set extends CommonService
		$common_namespace = "";
		if ($common_service_file_path && file_exists($common_service_file_path)) {
			$common_namespace = PHPCodePrintingHandler::getNamespacesFromFile($common_service_file_path);
			$common_namespace = isset($common_namespace[0]) ? $common_namespace[0] : null;
			$common_namespace = substr($common_namespace, 0, 1) == "\\" ? substr($common_namespace, 1) : $common_namespace;
			$common_namespace = substr($common_namespace, -1) == "\\" ? substr($common_namespace, 0, -1) : $common_namespace;
		}
		$default_extend = ($common_namespace ? "\\$common_namespace\\" : "") . "CommonService";
		$contents = str_replace("#COMMON_SERVICE#", $default_extend, $contents);
		
		//prepare class prefix based in parent dirname
		$laravel_path = preg_replace("/\s*\/+\s*$/", "", $laravel_path); //remove last slash and spaces at the end
		$class_prefix = str_replace(" ", "", ucwords(str_replace("_", " ", strtolower(trim(basename($laravel_path))))));
		$contents = str_replace("#CLASS_PREFIX#", $class_prefix, $contents);
		
		//save new file
		return file_put_contents("$laravel_path/{$class_prefix}LaravelProjectService.php", $contents) !== false;
	}
	
	private static function getProjectHtaccess() {
		return '<IfModule mod_rewrite.c>
  RewriteEngine On

  RewriteRule ^$ public/ [L,NC]

  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteRule (.*) public/$1 [L,NC]
</IfModule>';
	}
	
	private static function getArrayItemLineEndPosition($code, $start) {
		if ($code && is_numeric($start)) {
			$odq = $osq = false;
			$p = $b = 0;
			
			for ($i = $start, $t = strlen($code); $i < $t; $i++) {
				$char = $code[$i];
				
				if ($char == "'" && !$odq)
					$osq = !$osq;
				else if ($char == '"' && !$osq)
					$odq = !$odq;
				else if (!$osq && !$odq) {
					if ($char == "(")
						$p++;
					else if ($char == ")")
						$p--;
					else if ($char == "{")
						$b++;
					else if ($char == "}")
						$b--;
					else if ($char == "," && $p <= 0 && $b <= 0)
						break;
				}
			}
			
			return $i;
		}
		
		return null;
	}
	
	private static function setFolderPermissions($path, $mode, $recursive = true) {
		if (is_dir($path)) {
			$files = array_diff(scandir($path), array('..', '.'));
			$status = chmod($path, $mode);
			
			foreach ($files as $file) {
				$fp = "$path/$file";
				
				if (is_dir($fp) && $recursive && !self::setFolderPermissions($fp, $mode, $recursive))
					$status = false;
				
				if (!chmod($fp, $mode))
					$status = false;
			}
			
			return $status;
		}
		
		return false;
	}
}
?>
