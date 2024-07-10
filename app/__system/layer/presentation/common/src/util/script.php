<?php
function isCommandLineScript() {
	return isset($_SERVER['argc']) || (php_sapi_name() == 'cli' && empty($_SERVER["REMOTE_ADDRESS"])) || isset($_ENV['SSH_CLIENT'])/* || defined('STDIN')*/;
}

function prepareCommandLineScript($settings = array()) {
	$default_apache_user = $settings && !empty($settings["default_apache_user"]) ? $settings["default_apache_user"] : "www-data";
	$app_path = realpath(__DIR__ . "/../../../../../") . "/";
	$app_path = basename($app_path) == "app" ? $app_path : realpath($app_path . "../") . "/";
	
	include_once "$app_path/lib/vendor/fakeserverconf/src/FakeServerConf.php";
	include_once "$app_path/lib/vendor/fakeserverconf/src/ApacheCGI.php";
		
	//Only execute this if command line. If someone try to access this file via http request, this file wno't do anything
	if (isCommandLineScript()) {
		//Prepare script options
		$shortopts  = "h";	//Help (Optional)
		
		$longopts  = array(
		    "url:",     	// Absolute Url (Required)
		    "urlpath::",     	// Relative url Path (Optional)
		    "documentroot:",   	// Document Root (Required)
		    "scriptname::",     // Script Name (Optional)
		    "get::",    	// Get variables (Optional)
		    "post::",    	// Post variables (Optional)
		    "cookies::",    	// Cookies variables (Optional)
		    "env::",    	// Enviroment variables (Optional)
		    "method::",    	// Method (Optional)
		    "contenttype::",    // Content-Type (Optional)
		    "serveruser::",    	// Server User (Optional). By Default the server user is: www-data
		    "loglevel::",    	// Log Level (Optional)
		    "help",		//Help (Optional)
		);
		$options = getopt($shortopts, $longopts);
		
		if (isset($options["h"]) || isset($options["help"])) {
			echo '
Available Options:
url     		Absolute url that the client inserts in the browser (Required)
urlpath     		Relative url path that corresponds to the project entity. This value should be the same value than the $url variable that the mod_rewrite from the .htaccess populates (Optional)
documentroot   		Document Root is the system absolute file path where this app is installed. This corresponds to the Apache document root value. Sample: /var/www/html/phpframework/trunk/app/  (Required)
scriptname     		Script Name (Optional)
get    			Get variables string (Optional)
post    		Post variables string (Optional)
cookies    		Cookies variables string (Optional)
env    			Enviroment variables string (Optional)
method    		Http Method: post or get (Optional)
contenttype    		Content-Type (Optional)
serveruser    		Server User (Optional). By Default the server user is: www-data
loglevel    		Log Level (Optional)
h, help			Help (optional)

Notes:
	You should execute this script with the Apache user, otherwise you can have weird behaviours. If you won\'t execute it with the apache user, it can mess the cache system, because will try to create files with your current user that later apache cannot write. Additionally it cannot write in cached files that apache already created before...

Some sample commands: 
	sudo -u www-data php /home/jplpinto/Desktop/phpframework/trunk/app/__system/layer/presentation/phpframework/webroot/script.php --documentroot="/var/www/html/livingroop/" --scriptname="/default/app/__system/layer/presentation/phpframework/webroot/index.php" --url="http://jplpinto.localhost/__system/admin?admin_type=advanced" --urlpath="admin" --cookies="system_session_id=bf94ec458452858647659877179b083e"
	
	sudo -u www-data php /home/jplpinto/Desktop/phpframework/trunk/app/layer/presentation/condo/webroot/script.php --documentroot="/var/www/html/livingroop/" --scriptname="/default/app/layer/presentation/condo/webroot/index.php" --url="http://jplpinto.localhost/condo/private/article/channel_articles?tag=Regulamentos" --urlpath="private/article/channel_articles" --cookies="session_id=b062daadae0bdc1f036e4bc3145e00ab7a83ced2c8a76e0fefdc3de97cfb0a03"
	
	sudo -u www-data php /home/jplpinto/Desktop/phpframework/trunk/app/layer/presentation/condo/webroot/script.php  --documentroot="/var/www/html/livingroop/" --scriptname="/default/app/layer/presentation/condo/webroot/index.php" --url="http://jplpinto.localhost/condo/module/workerpool/run_worker_pool_script" --urlpath="module/workerpool/run_worker_pool_script" --loglevel=3
	
	sudo -u www-data php /home/jplpinto/Desktop/phpframework/trunk/app/layer/presentation/mastercondo/webroot/script.php  --documentroot="/var/www/html/livingroop/" --scriptname="/default/app/layer/presentation/mastercondo/webroot/index.php" --url="http://jplpinto.localhost/mastercondo/module/workerpool/run_worker_pool_script" --urlpath="module/workerpool/run_worker_pool_script" --loglevel=4
	
	/opt/php70/bin/php -c ~/www/others/livingroop/php.ini ~/www/others/livingroop/app/layer/presentation/condo/webroot/script.php --documentroot="~/www/others/livingroop/" --scriptname="/default/app/layer/presentation/condo/webroot/index.php" --url="http://livingroop.com/condo/module/workerpool/run_worker_pool_script" --urlpath="module/workerpool/run_worker_pool_script" --loglevel=3 --serveruser=jplp4686
';
			die();
		}
		
		//Check is cmd is being executed by the apache user
		$current_user = null;
		if (!empty($_SERVER["USER"]))
			$current_user = $_SERVER["USER"];
		else if (!empty($_SERVER["USERNAME"]))
			$current_user = $_SERVER["USERNAME"];
		else {
			//$is_windows_os = strtoupper(substr(PHP_OS, 0, 3)) === "WIN";//Detect if OS is Windows
			//$current_user = !$is_windows_os ? exec('whoami') : null;
			
			if (function_exists("posix_getpwuid")) { //posix_getpwuid does not exists in windows.
				$current_user = posix_getpwuid(posix_geteuid());
				$current_user = isset($current_user['name']) ? $current_user['name'] : null;
			}
		}
		
		$server_user = !empty($options["serveruser"]) ? $options["serveruser"] : $default_apache_user;//apache user
		
		if ($current_user && $current_user != $server_user) //$current_user may not exists on windows
			trigger_error("Warning: You should execute this script with the web server user", E_USER_WARNING);
		
		//Check if url and documentroot exist
		if (empty($options["url"]) || empty($options["documentroot"])) {
			trigger_error("Options 'url' and 'documentroot' cannot be undefined! For more information add the option -h or --help.", E_USER_WARNING);
			die();
		}
		
		$original_script_name = !empty($options["scriptname"]) ? $options["scriptname"] : (isset($_SERVER["SCRIPT_NAME"]) ? $_SERVER["SCRIPT_NAME"] : null);
		
		//Prepare Env Variables
		if (!empty($options["env"])) {
			parse_str($options["env"], $vars);
			if ($vars)
				$_ENV = $_ENV ? array_merge($_ENV, $vars) : $vars;
		}
		
		//Simulate Apache server behaviour
		$script_name = !empty($options["scriptname"]) ? $options["scriptname"] : "index.php";
		$url = isset($options["url"]) ? $options["url"] : null;
		$method = !empty($options["method"]) ? $options["method"] : (isset($options["post"]) ? "post" : "get");
		$post_body = !empty($options["post"]) ? $options["post"] : "";
		$body_content_type = !empty($options["contenttype"]) ? $options["contenttype"] : "application/x-www-form-urlencoded";
		
		$server = new \Jelix\FakeServerConf\ApacheCGI(null, $script_name);
        	$server->setHttpRequest($url, $method, $post_body, $body_content_type);
		//print_r($_ENV);print_r($_SERVER);print_r($_GET);print_r($_POST);print_r($_COOKIE);print_r($_REQUEST);die();
		
		//Set other cookies
		if (!empty($options["cookies"])) {
			parse_str($options["cookies"], $vars);
			if ($vars) {
				$_COOKIE = $_COOKIE ? array_merge($_COOKIE, $vars) : $vars;
				$_REQUEST = $_REQUEST ? array_merge($_REQUEST, $vars) : $vars;
			}
		}
		
		//Set other get variables
		if (!empty($options["get"])) {
			parse_str($options["get"], $vars);
			if ($vars) {
				$_GET = $_GET ? array_merge($_GET, $vars) : $vars;
				$_REQUEST = $_REQUEST ? array_merge($_REQUEST, $vars) : $vars;
			}
		}
		
		$pos = strpos($url, "?");
		$last_url_char = substr($url, $pos - 1, 1);
		
		$url_path = !empty($options["urlpath"]) ? $options["urlpath"] : parse_url($url, PHP_URL_PATH);
		$url_path = substr($url_path, 0, 1) == "/" ? substr($url_path, 1) : $url_path;
		$url_path .= substr($url_path, -1) != "/" && $last_url_char == "/" ? "/" : "";
		$url_path = substr($url_path, -1) == "/" && $last_url_char != "/" ? substr($url_path, 0, -1) : $url_path;
		$rel_url = $url_path . ($pos !== false ? "?" . substr($url, $pos) : "");
		
		//Simulate the .htaccess behaviour
		if (!empty($options["scriptname"])) {
			$documentroot = isset($options["documentroot"]) ? $options["documentroot"] : null;
			
			$_SERVER["SCRIPT_NAME"] = str_replace("//", "/", $options["scriptname"]); //relative path
			$_SERVER["SCRIPT_FILENAME"] = str_replace("//", "/", $documentroot . $options["scriptname"]); //absolute path
		}
		else {
			$current_script_name = realpath($original_script_name);
			$script_document_root = dirname($current_script_name) . "/";
			
			if (!empty($options["documentroot"])) {
				$document_root = realpath($options["documentroot"]);
				
				if (substr($current_script_name, 0, strlen($document_root)) == $document_root) {
					$script_document_root = substr(dirname($current_script_name) . "/", strlen($document_root)); //transform $script_document_root to relative path.
					$fc = substr($script_document_root, 0, 1);
					$script_document_root = ($fc != "/" && $fc != "~" ? "/" : "") . $script_document_root;
				}
			}
			
			$_SERVER["SCRIPT_NAME"] = str_replace("//", "/", $script_document_root . $script_name); //relative path
			$_SERVER["SCRIPT_FILENAME"] = str_replace("//", "/", dirname($current_script_name) . "/" . $script_name); //absolute path
		}
		
		$_SERVER["PHP_SELF"] = $_SERVER["SCRIPT_NAME"];
		$_SERVER["DOCUMENT_ROOT"] = isset($options["documentroot"]) ? $options["documentroot"] : null;
		$_SERVER["CONTEXT_DOCUMENT_ROOT"] = ""; //context document root on script is always empty string
		$_SERVER["QUERY_STRING"] = "url=" . $url_path . "&" . (isset($_SERVER["QUERY_STRING"]) ? $_SERVER["QUERY_STRING"] : null);
		
		$_GET["url"] = $url_path;
		$_REQUEST["url"] = $url_path;
		
		if (isset($options["loglevel"]) && is_numeric($options["loglevel"]))
			$GLOBALS["log_level"] = $options["loglevel"];
		
		//print_r($_ENV);print_r($_SERVER);print_r($_GET);print_r($_POST);print_r($_COOKIE);print_r($_REQUEST);die();
	}
	else { //If is not executed by command line
		header("HTTP/1.0 404 Not Found");
		die();
	}
}
?>
