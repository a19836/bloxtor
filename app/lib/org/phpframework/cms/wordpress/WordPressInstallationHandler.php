<?php
//This file is called directly in some wordpress files, so we need to create the get_lib and normalize_windows_path_to_linux functions
if (!function_exists("normalize_windows_path_to_linux")) {
	function normalize_windows_path_to_linux($path) { //This function will be used everytime that we use the php code: __FILE__ and __DIR__
		return DIRECTORY_SEPARATOR != "/" ? str_replace(DIRECTORY_SEPARATOR, "/", $path) : $path;
	}
}

if (!function_exists("get_lib")) {
	function get_lib($path) {
		$path = strpos($path, "lib.") === 0 ? substr($path, strlen("lib.")) : $path;
		return dirname(dirname(dirname(dirname(normalize_windows_path_to_linux(__DIR__))))) . "/" . str_replace(".", "/", $path) . ".php";
	}
}

include_once get_lib("org.phpframework.phpscript.PHPCodePrintingHandler");

class WordPressInstallationHandler {
	
	public static function hackWordPress($EVC, $db_driver, $db_settings, $wordpress_folder_path, $wordpress_url, $user_data, &$error_message) {
		$wordpress_url = preg_replace("/\/+$/", "", $wordpress_url); //remove last slash of url if exists, bc wordpress by default doesn't have the last slash in the home and siteurl.
		
		/*
		Copy wp-config-sample to wp-config and then change wp-config.php
		Set automaticaly the DB Credentials and Security KEYS on the installation
		Even if db credentials are in the GLOBAL VARS, set settings bellow with hardcoded values, bc in case someone changes the DB Credentials later in the Layers panel, this will still be with the old values. Otherwise we may have discrepancies between the wordpress DB and the file-system folder path. Example: if I install a new plugin it will change the wordpress DB, then if I change the $db_driver to another DB, it will not contain the installed plugin changes in the new DB or any wordpress tables, bu the wordpress files will continue to exists. 
		This means that the wordpress installation must be independent.
		If the user really wants to change the DB credentials and synchronize them with the Layers panel, it must do it manually!
		*/
		$fp = $wordpress_folder_path . "wp-config.php";
		
		if (file_exists($fp) || copy($wordpress_folder_path . "wp-config-sample.php", $fp)) {
			$contents = file_get_contents($fp);
			
			//convert to utf8
			$contents = str_replace("\r", "", $contents);
			
			//update db credentials
			$db_settings_host = isset($db_settings["host"]) ? $db_settings["host"] : null;
			$db_settings_port = isset($db_settings["port"]) ? $db_settings["port"] : null;
			$db_settings_db_name = isset($db_settings["db_name"]) ? $db_settings["db_name"] : null;
			$db_settings_username = isset($db_settings["username"]) ? $db_settings["username"] : null;
			$db_settings_password = isset($db_settings["password"]) ? $db_settings["password"] : null;
			$db_settings_encoding = isset($db_settings["encoding"]) ? $db_settings["encoding"] : null;
			
			$contents = preg_replace("/define\s*\(\s*('|\")DB_HOST('|\")\s*,[^\)]*\)\s*;/", "define('DB_HOST', '" . $db_settings_host . ($db_settings_port ? ":" . $db_settings_port : "") . "');", $contents);
			$contents = preg_replace("/define\s*\(\s*('|\")DB_NAME('|\")\s*,[^\)]*\)\s*;/", "define('DB_NAME', '" . $db_settings_db_name . "');", $contents);
			$contents = preg_replace("/define\s*\(\s*('|\")DB_USER('|\")\s*,[^\)]*\)\s*;/", "define('DB_USER', '" . $db_settings_username . "');", $contents);
			$contents = preg_replace("/define\s*\(\s*('|\")DB_PASSWORD('|\")\s*,[^\)]*\)\s*;/", "define('DB_PASSWORD', '" . $db_settings_password . "');", $contents);
			$contents = preg_replace("/define\s*\(\s*('|\")DB_CHARSET('|\")\s*,[^\)]*\)\s*;/", "define('DB_CHARSET', '" . $db_settings_encoding . "');", $contents);
			
			//update security keys
			$auth_key = "auth key " . CryptoKeyHandler::binToHex( CryptoKeyHandler::getKey() );
			$secure_auth_key = "secure auth key " . CryptoKeyHandler::binToHex( CryptoKeyHandler::getKey() );
			$logged_in_key = "logged in key " . CryptoKeyHandler::binToHex( CryptoKeyHandler::getKey() );
			$monce_key = "monce key " . CryptoKeyHandler::binToHex( CryptoKeyHandler::getKey() );
			$auth_salt = "auth salt " . CryptoKeyHandler::binToHex( CryptoKeyHandler::getKey() );
			$secure_auth_salt = "secure auth salt " . CryptoKeyHandler::binToHex( CryptoKeyHandler::getKey() );
			$logged_in_salt = "logged in salt " . CryptoKeyHandler::binToHex( CryptoKeyHandler::getKey() );
			$monce_salt = "monce salt " . CryptoKeyHandler::binToHex( CryptoKeyHandler::getKey() );
			
			$contents = preg_replace("/define\s*\(\s*('|\")AUTH_KEY('|\")\s*,[^\)]*\)\s*;/", "define('AUTH_KEY', '" . $auth_key . "');", $contents);
			$contents = preg_replace("/define\s*\(\s*('|\")SECURE_AUTH_KEY('|\")\s*,[^\)]*\)\s*;/", "define('SECURE_AUTH_KEY', '" . $secure_auth_key . "');", $contents);
			$contents = preg_replace("/define\s*\(\s*('|\")LOGGED_IN_KEY('|\")\s*,[^\)]*\)\s*;/", "define('LOGGED_IN_KEY', '" . $logged_in_key . "');", $contents);
			$contents = preg_replace("/define\s*\(\s*('|\")NONCE_KEY('|\")\s*,[^\)]*\)\s*;/", "define('NONCE_KEY', '" . $monce_key . "');", $contents);
			$contents = preg_replace("/define\s*\(\s*('|\")AUTH_SALT('|\")\s*,[^\)]*\)\s*;/", "define('AUTH_SALT', '" . $auth_salt . "');", $contents);
			$contents = preg_replace("/define\s*\(\s*('|\")SECURE_AUTH_SALT('|\")\s*,[^\)]*\)\s*;/", "define('SECURE_AUTH_SALT', '" . $secure_auth_salt . "');", $contents);
			$contents = preg_replace("/define\s*\(\s*('|\")LOGGED_IN_SALT('|\")\s*,[^\)]*\)\s*;/", "define('LOGGED_IN_SALT', '" . $logged_in_salt . "');", $contents);
			$contents = preg_replace("/define\s*\(\s*('|\")NONCE_SALT('|\")\s*,[^\)]*\)\s*;/", "define('NONCE_SALT', '" . $monce_salt . "');", $contents);
			
			//update URLs
			$replacement = 'define( \'WP_DEBUG\',${3});

//Define URL based in the phpframework_wp_request_uri, which is the relative request uri dynamically
global $phpframework_wp_request_uri;

if ($phpframework_wp_request_uri) {
	$protocol = !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off" ? "https" : "http";
	$url = $protocol . "://" . (isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : "") . $phpframework_wp_request_uri;
	define("WP_HOME", $url);
	define("WP_SITEURL", $url);
}

//very important to define the cookies paths as root, otherwise when the wordpress access the cookies through the WordPressHacker.php or WordPressCMSBlockHandler.php, the url will be different and it cannot get the right cookies. If I set the paths to root "/", I fix this issue.
define("COOKIEPATH", "/");
define("SITECOOKIEPATH", "/");
//define("ADMIN_COOKIE_PATH", "/"); //this is only for the wp-admin panel ui. Leave the default
define("PLUGINS_COOKIE_PATH", "/");

//disable automatic updates
define("WP_AUTO_UPDATE_CORE", false);
';
			$contents = preg_replace("/define\(\s*(\"|')WP_DEBUG(\"|')\s*,([^\)]+)\);/", $replacement, $contents);

			if (file_put_contents($fp, $contents) === false)
				$error_message = "Could not update wp-config.php with DB credentials and security keys. Please try again...";
		}
		else 
			$error_message = "Error trying to create wp-config.php. Please try again...";
		
		/*
		prepare wp_remote_get of install permalink function (wp_install_maybe_enable_pretty_permalinks) to have basic authentication from http, otherwise when the wordpress gets installed won't install the permalinks bc the internal requests will with 401 not authorized header. so we need to add the Htaccess authentication if exists...
		Replaces:
			$response          = wp_remote_get( $test_url, array( 'timeout' => 5 ) );
		by:
			$response          = wp_remote_get( $test_url, array( 'timeout' => 5, "headers" => array("Authorization" => '...') ) );
		*/
		$headers = getallheaders();
		$previous_code_of_wp_admin_includes_upgrade = null;
		
		if (!empty($headers["Authorization"])) {
			$fp = $wordpress_folder_path . "wp-admin/includes/upgrade.php";
			
			if (file_exists($fp)) {
				$contents = file_get_contents($fp);
				
				if (strpos($contents, 'array("Authorization"') === false) { //only if not yet added
					$previous_code_of_wp_admin_includes_upgrade = $contents;
					
					$to_search = '/\$response\s*=\s*wp_remote_get\s*\(\s*\$test_url\s*,\s*array\s*\(\s*("|\')timeout("|\')\s*=>\s*([0-9]+)\s*\)\s*\);/i';
					$replacement = '$response = wp_remote_get( $test_url, array( ${1}timeout${2} => ${3}, "headers" => array("Authorization" => \'' . $headers["Authorization"] . '\') ) );';
					
					$contents = preg_replace($to_search, $replacement, $contents);
					
					if (strpos($contents, 'array("Authorization"') === false)
						$error_message = "Could not add Authorization header to wp_remote_get function in wp-admin/includes/upgrade.php. Please try again...";
					
					if (file_put_contents($fp, $contents) === false)
						$error_message = "Could not update wp-admin/includes/upgrade.php with Authorization header code. Please try again...";
				}
			}
			else 
				$error_message = "File 'wp-admin/includes/upgrade.php' not found. Please try again...";
		}
		
		/*
		install wordpress automatically. Basically replicate the steps in the wp-admin/install.php file
		*/
		define("WP_HOME", $wordpress_url);
		define("WP_SITEURL", $wordpress_url);
		define('WP_INSTALLING', true);
		
		//Load WordPress Bootstrap
		require_once $wordpress_folder_path . 'wp-load.php';

		//Load WordPress Administration Upgrade API
		require_once $wordpress_folder_path . 'wp-admin/includes/upgrade.php';

		//Load WordPress Translation Install API
		require_once $wordpress_folder_path . 'wp-admin/includes/translation-install.php';

		//Load wpdb
		require_once $wordpress_folder_path . WPINC . '/wp-db.php';
		
		global $wp_version, $required_php_version, $required_mysql_version; //used in the wordpress files
		
		if (!is_blog_installed()) { //is_blog_installed is in wp-includes/functions.php
			$language = 'en_US';
			$user_data_username = isset($user_data["username"]) ? $user_data["username"] : null;
			$user_data_password = isset($user_data["password"]) ? $user_data["password"] : null;
			$user_data_email = isset($user_data["email"]) ? $user_data["email"] : null;
			
			$weblog_title = wp_unslash("PHPFramework " . $db_driver);
			$admin_user = wp_unslash($user_data_username);
			$admin_pass = wp_unslash($user_data_password);
			$admin_email = wp_unslash($user_data_email ? $user_data["email"] : "dummy@gmail.com");
			$public = 1; //0 or false to discourage search engines from indexing this site
			
			$wpdb->show_errors();
			$result = wp_install($weblog_title, $admin_user, $admin_email, $public, '', wp_slash($admin_pass), $language); //wp_install is in wp-admin/includes/upgrade.php
			
			if ((isset($admin_pass) && !isset($result["password"])) || $result["password"] != $admin_pass)
				$error_message = "Something went wrong with the wordpress installation. Please try again...";
			
			//save admin_user and admin_pass to a file so we can automatically login
			$code = '<?php
//added by phpframework at ' . date("Y-m-d H:i") . '
$admin_user = "' . $admin_user . '";
$admin_pass = "' . $admin_pass . '";
?>';
			
			if (file_put_contents($wordpress_folder_path . "default_admin_credentials.php", $code) === false)
				$error_message = "Could not create file with default admin credentials. Please try again...";
			
			//update site_url and home url in the wordpress DB, otherwise will have this file url as default...
			update_option("home", $wordpress_url);
			update_option("siteurl", $wordpress_url);
		}
		
		//replace previous code in wp-admin/includes/upgrade.php
		if ($previous_code_of_wp_admin_includes_upgrade) {
			if (file_put_contents($wordpress_folder_path . "wp-admin/includes/upgrade.php", $previous_code_of_wp_admin_includes_upgrade) === false)
				$error_message = "Could not re-write the original code of wp-admin/includes/upgrade.php. Please try again...";
		}
		
		//Install phpframework plugin:app/__system/layer/presentation/phpframework/webroot/assets/wordpress_phpframework_plugin.zip
		$zipped_file_path = $EVC->getWebrootPath() . "assets/wordpress_phpframework_plugin.zip";
		
		if (ZipHandler::unzip($zipped_file_path, $wordpress_folder_path . "wp-content/plugins/")) {
			activate_plugin("phpframework/phpframework.php");
		}
		else
			$error_message = "Error unzip 'assets/wordpress_phpframework_plugin.zip'. Please try again...";
		
		//Install phpframework template:app/__system/layer/presentation/phpframework/webroot/assets/wordpress_phpframework_template.zip
		$zipped_file_path = $EVC->getWebrootPath() . "assets/wordpress_phpframework_template.zip";
		
		if (!ZipHandler::unzip($zipped_file_path, $wordpress_folder_path . "wp-content/themes/"))
			$error_message = "Error unzip 'assets/wordpress_phpframework_plugin.zip'. Please try again...";
		
		/*
		change the line in wordpress/wp-admin/includes/user.php file to everytime the users updates the username or password, the system updates the default_admin_credentials.php, so it cannot broke the automatically login
		Replaces:
			$user_id = wp_update_user( $user );
		*/
		$fp = $wordpress_folder_path . "wp-admin/includes/user.php";
		
		if (file_exists($fp)) {
			$contents = file_get_contents($fp);
			
			if (strpos($contents, 'file_put_contents($default_admin_credentials_path, $contents)') === false) { //if not yet added
				$to_search = '/\$user_id\s*=\s*wp_update_user\s*\(\s*\$user\s*\)\s*;/';
				$replacement = '${0}
		
		//changed by phpframework at ' . date("Y-m-d H:i") . '
		//updates the default_admin_credentials.php with new password, if user changed it
		$default_admin_credentials_path = ABSPATH . "default_admin_credentials.php";
		
		if (!is_wp_error($user_id) && file_exists($default_admin_credentials_path)) {
			include $default_admin_credentials_path;
			
			if ($user->user_login == $admin_user && $user->user_pass) { //it means the user changed his password
				$contents = file_get_contents($default_admin_credentials_path);
				
				if (preg_match(\'/\\\$admin_pass\s*=\s*/\', $contents, $matches, PREG_OFFSET_CAPTURE)) {
					$start_pos = $matches[0][1];
					$end_pos = stripos($contents, "\n", $start_pos);
					$end_pos = $end_pos === false ? strlen($contents) : $end_pos;
					
					$to_search = trim( substr($contents, $start_pos, $end_pos - $start_pos) );
					
					$contents = str_replace($to_search, \'$admin_pass = "\' . addcslashes(wp_unslash($user->user_pass), \'\\\\"\') . \'";\', $contents);
					
					file_put_contents($default_admin_credentials_path, $contents);
				}
			}
		}';
				
				$contents = preg_replace($to_search, $replacement, $contents);
				
				if (strpos($contents, 'file_put_contents($default_admin_credentials_path, $contents)') === false)
					$error_message = "Could not find text to replace in wp-admin/includes/user.php. Please try again...";
				
				if (file_put_contents($fp, $contents) === false)
					$error_message = "Could not update wp-admin/includes/user.php with phpframework redirect function. Please try again...";
			}
		}
		else 
			$error_message = "File 'wp-admin/includes/user.php' not found. Please try again...";
		
		/*
		change the line in wordpress/wp-includes/pluggable.php file inside of the wp_redirect function line 1298
		Replaces:
			header( "Location: $location", true, $status );
		by:
			if (class_exists("WordPressCMSBlockHandler") && method_exists("WordPressCMSBlockHandler", "prepareRedirectUrl"))
				WordPressCMSBlockHandler::prepareRedirectUrl($location, basename(dirname(__DIR__)));
			
			header("Location: $location", true, $status);
		*/
		$fp = $wordpress_folder_path . "wp-includes/pluggable.php";
		
		if (file_exists($fp)) {
			$contents = file_get_contents($fp);
			
			if (strpos($contents, 'WordPressCMSBlockHandler') === false) { //if not yet added
				$to_search = '/header\s*\(\s*("|\')\s*Location:\s*\$location("|\')[^\)]*\)\s*;/';
				$replacement = '//changed by phpframework at ' . date("Y-m-d H:i") . '
		//change the location value according with the phpframework current page
		if (class_exists("WordPressCMSBlockHandler") && method_exists("WordPressCMSBlockHandler", "prepareRedirectUrl"))
			WordPressCMSBlockHandler::prepareRedirectUrl($location, basename(dirname(__DIR__)));
		
		${0}';
				
				$contents = preg_replace($to_search, $replacement, $contents);
				
				if (strpos($contents, 'WordPressCMSBlockHandler') === false)
					$error_message = "Could not find text to replace in wp-includes/pluggable.php. Please try again...";
				
				if (file_put_contents($fp, $contents) === false)
					$error_message = "Could not update wp-includes/pluggable.php with phpframework redirect function. Please try again...";
			}
		}
		else 
			$error_message = "File 'wp-includes/pluggable.php' not found. Please try again...";
		
		/*
		change the line in wordpress/wp-blog-header.php:
			require_once ABSPATH . WPINC . '/template-loader.php';
		//by
			require ABSPATH . WPINC . '/template-loader.php'; //this allows the wordpress to be called multiple times
		*/
		$fp = $wordpress_folder_path . "wp-blog-header.php";
		
		if (file_exists($fp)) {
			$contents = file_get_contents($fp);
			
			$to_search = '/require_once\s*ABSPATH\s*.\s*WPINC\s*.\s*("|\')\/template-loader.php("|\')\s*;/';
			$replacement = "//changed by phpframework at " . date("Y-m-d H:i") . "
	//changed require_once to require so we can call multiple times the wordpress template
	require ABSPATH . WPINC . '/template-loader.php';";
			
			$contents = preg_replace($to_search, $replacement, $contents);
			
			if (preg_match($to_search, $contents))
				$error_message = "Could not find text to replace in wp-blog-header.php. Please try again...";
			
			if (file_put_contents($fp, $contents) === false)
				$error_message = "Could not update wp-blog-header.php to include a template multiple times. Please try again...";
		}
		else 
			$error_message = "File 'wp-blog-header.php' not found. Please try again...";
		
		/*
		change get_header function to get html to an array in file: wordpress/wp-includes/general-template.php
		change line:
			function get_header(
		to:
			function get_header(...) {
				global $phpframework_options, $phpframework_results;
				
				...
		*/
		$fp = $wordpress_folder_path . "wp-includes/general-template.php";
		
		if (file_exists($fp)) {
			$contents = file_get_contents($fp);
			
			if (strpos($contents, "\$current_phpframework_result_key = 'header';") === false) { //if not yet added
				$to_search = '/function\s+get_header\s*\([^{]+\{/';
				$replacement = "\${0}
	//changed by phpframework at " . date("Y-m-d H:i") . "
	global \$phpframework_options, \$phpframework_results, \$current_phpframework_result_key;
	
	//start fetching header output
	if (\$phpframework_options) {
		\$obgc = ob_get_contents();
		\$phpframework_results['before_header'] .= \$obgc;
		\$phpframework_results['full_page_html'] .= \$obgc;
		ob_end_clean();
		
		ob_start(null, 0);
		\$current_phpframework_result_key = 'header';
	}
	";
				
				$contents = preg_replace($to_search, $replacement, $contents, 1);
			}
			
			if (strpos($contents, "\$current_phpframework_result_key = 'theme_content';") === false) { //if not yet added
				$to_search = '/if\s*\(\s*!\s*(locate_template\([^)]*\))\s*\)\s*\{/';
				$replacement = "\$status = \${1};
	
	//changed by phpframework at " . date("Y-m-d H:i") . "
	//stop fetching header output and save it
	if (\$phpframework_options) {
		\$obgc = ob_get_contents();
		\$obgc = '<!-- phpframework:template:region: \"Before Header\" -->' . \$obgc . '<!-- phpframework:template:region: \"After Header\" -->';
		\$phpframework_results[\$current_phpframework_result_key] .= \$obgc;
		\$phpframework_results['full_page_html'] .= \$obgc;
		ob_end_clean();
		
		ob_start(null, 0); //start a new ob_start that will be closed in the get_footer in order to get the theme_content
		\$current_phpframework_result_key = 'theme_content';
	}
	
	if ( ! \$status) {";
			
				$contents = preg_replace($to_search, $replacement, $contents, 1);
			}
			
			if (strpos($contents, "\$current_phpframework_result_key = 'header';") === false || strpos($contents, "\$current_phpframework_result_key = 'theme_content';") === false)
				$error_message = "Could not find text to replace in wp-includes/general-template.php. Please try again...";
			
			if (file_put_contents($fp, $contents) === false)
				$error_message = "Could not update wp-includes/general-template.php to include the get_header hacking. Please try again...";
		}
		else 
			$error_message = "File 'wp-includes/general-template.php' not found. Please try again...";
		
		/*
		change get_footer function to get html to an array in file: wordpress/wp-includes/general-template.php
		change line:
			function get_footer(
		to:
			function get_footer(...) {
				global $phpframework_options, $phpframework_results;
				
				...
		*/
		$fp = $wordpress_folder_path . "wp-includes/general-template.php";
		
		if (file_exists($fp)) {
			$contents = file_get_contents($fp);
			
			if (strpos($contents, "\$current_phpframework_result_key = 'footer';") === false) { //if not yet added
				$to_search = '/function\s+get_footer\s*\([^{]+\{/';
				$replacement = "\${0}
	//changed by phpframework at " . date("Y-m-d H:i") . "
	global \$phpframework_options, \$phpframework_results, \$current_phpframework_result_key;

	//start fetching footer output
	if (\$phpframework_options) {
		\$obgc = ob_get_contents();
		\$current_phpframework_result_key_label = ucwords(str_replace('_', ' ', \$current_phpframework_result_key));
		\$obgc = '<!-- phpframework:template:region: \"Before ' . \$current_phpframework_result_key_label . '\" -->' . \$obgc . '<!-- phpframework:template:region: \"After ' . \$current_phpframework_result_key_label . '\" -->';
		
		\$phpframework_results[\$current_phpframework_result_key] .= \$obgc;
		\$phpframework_results['full_page_html'] .= \$obgc;
		ob_end_clean();
		
		ob_start(null, 0);
		\$current_phpframework_result_key = 'footer';
	}
	";
			
				$contents = preg_replace($to_search, $replacement, $contents, 1);
			}
			
			if (strpos($contents, "\$current_phpframework_result_key = 'after_footer';") === false) { //if not yet added
				$to_search = '/if\s*\(\s*!\s*(locate_template\([^)]*\))\s*\)\s*\{/';
				$replacement = "\$status = \${1};
	
	//changed by phpframework at " . date("Y-m-d H:i") . "
	//stop fetching footer output and save it
	if (\$phpframework_options) {
		\$obgc = ob_get_contents();
		\$obgc = '<!-- phpframework:template:region: \"Before Footer\" -->' . \$obgc . '<!-- phpframework:template:region: \"After Footer\" -->';
		\$phpframework_results[\$current_phpframework_result_key] .= \$obgc;
		\$phpframework_results['full_page_html'] .= \$obgc;
		ob_end_clean();
		
		ob_start(null, 0);
		\$current_phpframework_result_key = 'after_footer';
	}
	
	if ( ! \$status) {";
			
				$contents = preg_replace($to_search, $replacement, $contents, 1);
			}
			
			if (strpos($contents, "\$current_phpframework_result_key = 'footer';") === false || strpos($contents, "\$current_phpframework_result_key = 'after_footer';") === false)
				$error_message = "Could not find text to replace in wp-includes/general-template.php. Please try again...";
			
			if (file_put_contents($fp, $contents) === false)
				$error_message = "Could not update wp-includes/general-template.php to include the get_footer hacking. Please try again...";
		}
		else 
			$error_message = "File 'wp-includes/general-template.php' not found. Please try again...";
		
		/*
		change get_sidebar function to get html to an array in file: wordpress/wp-includes/general-template.php
		change line:
			function get_sidebar(
		to:
			function get_sidebar(...) {
				global $phpframework_options, $phpframework_results;
				
				...
		*/
		$fp = $wordpress_folder_path . "wp-includes/general-template.php";
		
		if (file_exists($fp)) {
			$contents = file_get_contents($fp);
			
			if (strpos($contents, "phpframework_results['theme_side_bars']") === false) { //if not yet added
				$to_search = '/function\s+get_sidebar\s*\([^{]+\{/';
				$replacement = "\${0}
	//changed by phpframework at " . date("Y-m-d H:i") . "
	global \$phpframework_options, \$phpframework_results;

	//start fetching sidebar output
	if (\$phpframework_options)
		ob_start(null, 0);
	";
			
				$contents = preg_replace($to_search, $replacement, $contents, 1);
			
				$to_search = '/if\s*\(\s*!\s*(locate_template\([^)]*\))\s*\)\s*\{/';
				$replacement = "\$status = \${1};
	
	//changed by phpframework at " . date("Y-m-d H:i") . "
	//stop fetching sidebar output and save it
	if (\$phpframework_options) {
		//get sidebar
		\$sidebar_id = \$name ? \$name : 0;
		\$sidebar = ob_get_contents();
		\$sidebar = '<!-- phpframework:template:region: \"Before Side Bar: ' . \$sidebar_id . '\" -->' . \$sidebar . '<!-- phpframework:template:region: \"After Side Bar: ' . \$sidebar_id . '\" -->';
		\$phpframework_results['theme_side_bars'][\$sidebar_id][] = \$sidebar;
		ob_end_clean();
		
		//print sidebar html
		echo \$sidebar;
	}
	
	if ( ! \$status) {";
			
				$contents = preg_replace($to_search, $replacement, $contents, 1);
			}
			
			if (strpos($contents, "//start fetching sidebar output") === false || strpos($contents, "phpframework_results['theme_side_bars']") === false)
				$error_message = "Could not find text to replace in wp-includes/general-template.php. Please try again...";
			
			if (file_put_contents($fp, $contents) === false)
				$error_message = "Could not update wp-includes/general-template.php to include the get_sidebar hacking. Please try again...";
		}
		else 
			$error_message = "File 'wp-includes/general-template.php' not found. Please try again...";
		
		/*
		change dynamic_sidebar function to get html to an array in file: wordpress/wp-includes/widgets.php
		change line:
			function dynamic_sidebar(
		to:
			function dynamic_sidebar(...) {
				global $phpframework_options, $phpframework_results;
				
				...
		*/
		$fp = $wordpress_folder_path . "wp-includes/widgets.php";
		
		if (file_exists($fp)) {
			$contents = file_get_contents($fp);
			
			if (strpos($contents, "phpframework_results['theme_side_bars']") === false) { //if not yet added
				$to_search = '/function\s+dynamic_sidebar\s*\([^{]+\{/';
				$replacement = "\${0}
	//changed by phpframework at " . date("Y-m-d H:i") . "
	global \$phpframework_options, \$phpframework_results;
	
	//start fetching sidebar output
	if (\$phpframework_options) 
		ob_start(null, 0);
	";
			
				$contents = preg_replace($to_search, $replacement, $contents, 1);
			
				$to_search = '/return\s+(apply_filters\s*\(\s*\'dynamic_sidebar_has_widgets\'\s*,\s*false,\s*\$index\s*\)\s*;)/';
				$replacement = "
		//changed by phpframework at " . date("Y-m-d H:i") . "
		//stop fetching sidebar output and save it
		if (\$phpframework_options) {
			//get sidebar
			\$returned = \${1}
			\$sidebar_id = \$index ? \$index : 0;
			\$sidebar = ob_get_contents();
			\$sidebar = '<!-- phpframework:template:region: \"Before Side Bar: ' . \$sidebar_id . '\" -->' . \$sidebar . '<!-- phpframework:template:region: \"After Side Bar: ' . \$sidebar_id . '\" -->';
			\$phpframework_results['theme_side_bars'][\$sidebar_id][] = \$sidebar;
			ob_end_clean();
			
			//print sidebar html
			echo \$sidebar;
			
			return \$returned;
		}
		else
			return \${1}";
			
				$contents = preg_replace($to_search, $replacement, $contents, 1);
			
				$to_search = '/return\s+(apply_filters\s*\(\s*\'dynamic_sidebar_has_widgets\'\s*,\s*\$did_one\s*,\s*\$index\s*\)\s*;)/';
				$replacement = "
	//changed by phpframework at " . date("Y-m-d H:i") . "
	//stop fetching sidebar output and save it
	if (\$phpframework_options) {
		//get sidebar
		\$returned = \${1}
		\$sidebar_id = \$index ? \$index : 0;
		\$sidebar = ob_get_contents();
		\$sidebar = '<!-- phpframework:template:region: \"Before Side Bar: ' . \$sidebar_id . '\" -->' . \$sidebar . '<!-- phpframework:template:region: \"After Side Bar: ' . \$sidebar_id . '\" -->';
		\$phpframework_results['theme_side_bars'][\$sidebar_id][] = \$sidebar;
		ob_end_clean();
		
		//print sidebar html
		echo \$sidebar;
		
		return \$returned;
	}
	else
		return \${1}";
			
				$contents = preg_replace($to_search, $replacement, $contents, 1);
			}
			
			if (strpos($contents, "phpframework_results['theme_side_bars']") === false)
				$error_message = "Could not find text to replace in wp-includes/widgets.php. Please try again...";
			
			if (file_put_contents($fp, $contents) === false)
				$error_message = "Could not update wp-includes/widgets.php to include the get_sidebar hacking. Please try again...";
		}
		else 
			$error_message = "File 'wp-includes/widgets.php' not found. Please try again...";
		
		/*
		change wp_nav_menu function to get html to an array in file: wordpress/wp-includes/nav-menu-template.php
		change line:
			function wp_nav_menu(
		to:
			function wp_nav_menu(...) {
				global $phpframework_options, $phpframework_results;
				
				...
		*/
		$fp = $wordpress_folder_path . "wp-includes/nav-menu-template.php";
		
		if (file_exists($fp)) {
			$contents = file_get_contents($fp);
			
			if (strpos($contents, "phpframework_results['theme_menus']") === false) { //if not yet added
				$to_search = '/function\s+wp_nav_menu\s*\([^{]+\{/';
				$replacement = "\${0}
	//changed by phpframework at " . date("Y-m-d H:i") . "
	global \$phpframework_options, \$phpframework_results;
	";
				
				$contents = preg_replace($to_search, $replacement, $contents, 1);
				
				$to_search = '/\$args\s*=\s*apply_filters\s*\(\s*\'wp_nav_menu_args\'\s*,\s*\$args\s*\)\s*;/';
				$replacement = "\$phpframework_menu_id = !empty(\$args['menu']) ? \$args['menu'] : \$args['menu_id'];
	
	\${0}";
				
				$contents = preg_replace($to_search, $replacement, $contents, 1);
				
				$to_search = '/\$args\s*=\s*\(\s*object\s*\)\s*\$args\s*;/';
				$replacement = "\${0}
	
	//changed by phpframework at " . date("Y-m-d H:i") . "
	//preparing menu id
	if (!\$phpframework_menu_id) {
		\$phpframework_menu_id = \$args->menu ? (is_object(\$args->menu) ? (\$args->menu->slug ? \$args->menu->slug : \$args->menu->term_id) : \$args->menu) : \$args->menu_id;
		
		if (!\$phpframework_menu_id)
			\$phpframework_menu_id = \$args->theme_location ? \$args->theme_location : 0;
	}";
			
				$contents = preg_replace($to_search, $replacement, $contents, 1);
				
				$to_search = '/\$args\s*\->\s*menu\s*=\s*\$menu\s*;/';
				$replacement = "\${0}
		
		//changed by phpframework at " . date("Y-m-d H:i") . "
		//preparing menu id
		if (is_object(\$menu)) {
			if (\$args->theme_location && \$menu_maybe && \$menu_maybe == \$menu) {
				\$phpframework_menu_id = \$args->theme_location;
			}
			else {
				\$old_phpframework_menu_id = \$phpframework_menu_id;
				\$phpframework_menu_id = \$menu->slug ? \$menu->slug : \$menu->term_id;
				
				if (!\$phpframework_menu_id)
					\$phpframework_menu_id = \$old_phpframework_menu_id;
			}
		}";
				
				$contents = preg_replace($to_search, $replacement, $contents, 1);
				
				$to_search = '/\$nav_menu\s*=\s*apply_filters\(\s*\'pre_wp_nav_menu\'\s*,\s*null\s*,\s*\$args\s*\)\s*;\s*if\s*\(\s*null\s*!==\s*\$nav_menu\s*\)\s*\{/';
				$replacement = "\${0}
		//changed by phpframework at " . date("Y-m-d H:i") . "
		//saving menu
		if (\$phpframework_options) {
			\$nav_menu = '<!-- phpframework:template:region: \"Before Nav Menu: ' . \$phpframework_menu_id . '\" -->' . \$nav_menu . '<!-- phpframework:template:region: \"After Nav Menu: ' . \$phpframework_menu_id . '\" -->';
			\$phpframework_results['theme_menus'][\$phpframework_menu_id][] = \$nav_menu;
		}
		";
				
				$contents = preg_replace($to_search, $replacement, $contents, 1);
				
				$to_search = '/return\s*(call_user_func\s*\(\s*\$args\->\s*fallback_cb\s*,\s*\(\s*array\s*\)\s*\$args\s*\)\s*;)/';
				$replacement = "
		//changed by phpframework at " . date("Y-m-d H:i") . "
		//saving menu
		if (\$phpframework_options) {
			//get nav_menu
			ob_start(null, 0);
			\$returned = \${1}
			\$nav_menu = ob_get_contents();
			\$nav_menu = '<!-- phpframework:template:region: \"Before Nav Menu: ' . \$phpframework_menu_id . '\" -->' . \$nav_menu . '<!-- phpframework:template:region: \"After Nav Menu: ' . \$phpframework_menu_id . '\" -->';
			\$phpframework_results['theme_menus'][\$phpframework_menu_id][] = \$nav_menu . (\$args->echo ? '' : \$returned);
			ob_end_clean();
			
			//print nav_menu html
			echo \$nav_menu;
			
			return \$returned;
		}
		else
			return \${1}";
			
				$contents = preg_replace($to_search, $replacement, $contents, 1);
			
				$to_search = '/if\s*\(\s*\$args->echo\s*\)\s*\{\s*echo\s*\$nav_menu\s*;\s*\}\s*else\s*\{\s*return\s*\$nav_menu\s*;\s*}\s*\}/';
				$replacement = "//changed by phpframework at " . date("Y-m-d H:i") . "
	//saving menu
	if (\$phpframework_options) {
		\$nav_menu = '<!-- phpframework:template:region: \"Before Nav Menu: ' . \$phpframework_menu_id . '\" -->' . \$nav_menu . '<!-- phpframework:template:region: \"After Nav Menu: ' . \$phpframework_menu_id . '\" -->';
		\$phpframework_results['theme_menus'][\$phpframework_menu_id][] = \$nav_menu;
	}
	
	\${0}";
			
				$contents = preg_replace($to_search, $replacement, $contents, 1);
			}
			
			if (strpos($contents, "phpframework_results['theme_menus']") === false)
				$error_message = "Could not find text to replace in wp-includes/nav-menu-template.php. Please try again...";
			
			if (file_put_contents($fp, $contents) === false)
				$error_message = "Could not update wp-includes/nav-menu-template.php to include the wp_nav_menu hacking. Please try again...";
		}
		else 
			$error_message = "File 'wp-includes/nav-menu-template.php' not found. Please try again...";
		
		/*
		change comments_template function to get html to an array in file: wordpress/wp-includes/comment-template.php
		change line:
			function comments_template(
		to:
			function comments_template(...) {
				global $phpframework_options, $phpframework_results;
				
				...
		*/
		$fp = $wordpress_folder_path . "wp-includes/comment-template.php";
		
		if (file_exists($fp)) {
			$contents = file_get_contents($fp);
			
			if (strpos($contents, "phpframework_results['theme_comments']") === false) { //if not yet added
				$to_search = '/if\s*\(\s*empty\(\s*\$file\s*\)\s*\)\s*\{\s*\$file\s*=\s*\'\/comments\.php\'\s*;\s*\}/';
				$replacement = "//changed by phpframework at " . date("Y-m-d H:i") . "
	global \$phpframework_options, \$phpframework_results;
	
	//start fetching comments output
	if (\$phpframework_options)
		ob_start(null, 0);
	
	\${0}";
				
				$contents = preg_replace($to_search, $replacement, $contents, 1);
				
				$to_search = '/require\s+ABSPATH\s*\.\s*WPINC\s*\.\s*\'\/theme-compat\/comments\.php\'\s*;\s*\}/';
				$replacement = "\${0}
		
	//changed by phpframework at " . date("Y-m-d H:i") . "
	//stop fetching comments output and save it
	if (\$phpframework_options) {
		//get comments html
		\$post_id = isset(\$comment_args['post_id']) ? \$comment_args['post_id'] : null;
		\$comments_html_id = \$post_id ? \$post_id : 0;
		\$comments_html = ob_get_contents();
		\$comments_html = '<!-- phpframework:template:region: \"Before Comments from post: ' . \$comments_html_id . '\" -->' . \$comments_html . '<!-- phpframework:template:region: \"After Comments from post: ' . \$comments_html_id . '\" -->';
		\$phpframework_results['theme_comments'][\$comments_html_id][] = \$comments_html;
		ob_end_clean();
		
		//print comments html
		echo \$comments_html;
	}";
				
				$contents = preg_replace($to_search, $replacement, $contents, 1);
			}
			
			if (strpos($contents, "phpframework_results['theme_comments']") === false)
				$error_message = "Could not find text to replace in wp-includes/comment-template.php. Please try again...";
			
			if (file_put_contents($fp, $contents) === false)
				$error_message = "Could not update wp-includes/comment-template.php to include the comments_template hacking. Please try again...";
		}
		else 
			$error_message = "File 'wp-includes/comment-template.php' not found. Please try again...";
		
		/*
		in wp-admin/themes.php replace the following lines: 216:
			wp_reset_vars( array( 'theme', 'search' ) );

		by
			if ($themes)
			   foreach ($themes as $idx => $theme)
		           if (isset($theme["id"]) && $theme["id"] == "phpframework") {
		                   unset($themes[$idx]);
		                   break;
		           }
			
			wp_reset_vars( array( 'theme', 'search' ) );
		
		And change too this file to include the WordPressRequestHandler that will parse the direct requests like ajax.
		*/
		$fp = $wordpress_folder_path . "wp-admin/themes.php";
		
		if (file_exists($fp)) {
			$contents = file_get_contents($fp);
			
			if (strpos($contents, '$theme["id"] == "phpframework"') === false) { //if not yet added
				$to_search = "/wp_reset_vars\s*\(\s*array\s*\(\s*'theme'\s*,\s*'search'\s*\)\s*\)\s*;/";
				$replacement = '
//changed by phpframework at ' . date("Y-m-d H:i") . '
//hide the phpframework theme
if ($themes) {
	foreach ($themes as $idx => $theme)
		if (isset($theme["id"]) && $theme["id"] == "phpframework") {
			unset($themes[$idx]);
			break;
		}
	
	$themes = array_values($themes);
}

wp_reset_vars( array( \'theme\', \'search\' ) );';
			
				$contents = preg_replace($to_search, $replacement, $contents);
			}
			
			if (strpos($contents, 'WordPressInstallationHandler::prepareFolderFilesWithDirectRequests(') === false) { //if not yet added
				//This code is for everytime that the user activates a plugins the system will check if there is any sub-file that can be call directly from ajax or the browser, and if so, adds the WordPressRequestHandler controls...
				$to_search = '/switch_theme\s*\(\s*\$theme\s*->\s*get_stylesheet\s*\(\s*\)\s*\)\s*;/';
				$replacement = '${0}
		
		//changed by phpframework at ' . date("Y-m-d H:i") . '
		//when user activates a plugins this checks if there is any plugin\'s sub-file that can be call directly from ajax or the browser, and if so, adds the WordPressRequestHandler controls...
		$wpih_class_exists = class_exists("WordPressInstallationHandler");

		if (!$wpih_class_exists) {
			@include_once dirname(dirname(dirname(dirname(dirname(dirname(dirname(ABSPATH))))))) . "/lib/org/phpframework/cms/wordpress/WordPressInstallationHandler.php";
			$wpih_class_exists = class_exists("WordPressInstallationHandler");
		}
		
		if ($wpih_class_exists) 
			WordPressInstallationHandler::prepareFolderFilesWithDirectRequests(ABSPATH, $theme->get_stylesheet_directory());
		';
			
				$contents = preg_replace($to_search, $replacement, $contents);
			}
			
			if (strpos($contents, '$theme["id"] == "phpframework"') === false || strpos($contents, 'WordPressInstallationHandler::prepareFolderFilesWithDirectRequests(') === false)
				$error_message = "Could not find text to replace in wp-admin/themes.php. Please try again...";
			
			if (file_put_contents($fp, $contents) === false)
				$error_message = "Could not update wp-admin/themes.php to exclude phpframework theme. Please try again...";
		}
		else 
			$error_message = "File 'wp-admin/themes.php' not found. Please try again...";
		
		/*
		change file to include the WordPressRequestHandler that will parse the direct requests like ajax
		*/
		$files = array("index.php", "wp-links-opml.php", "xmlrpc.php", "wp-admin/admin-ajax.php", "wp-admin/admin-post.php");
		self::prepareWordPressFilesWithRequestHandler($wordpress_folder_path, $files, $error_message);
		
		/*
		add WordPressInstallationHandler::prepareFolderFilesWithDirectRequests call to wp-admin/plugins.php 
		
		Note: $plugin variable in wp-admin/plugins.php is a relative file path, like: erp/wp-erp.php or hello.php
		*/
		$fp = $wordpress_folder_path . "wp-admin/plugins.php";
		
		if (file_exists($fp)) {
			$contents = file_get_contents($fp);
			
			if (strpos($contents, 'WordPressInstallationHandler::prepareFolderFilesWithDirectRequests(') === false) { //if not yet added
				//This code is for everytime that the user activates a plugins the system will check if there is any sub-file that can be call directly from ajax or the browser, and if so, adds the WordPressRequestHandler controls...
				$to_search = '/\$result\s*=\s*activate_plugin\s*\([^;]+;/';
				$replacement = '//changed by phpframework at ' . date("Y-m-d H:i") . '
			//when user activates a plugins this checks if there is any plugin\'s sub-file that can be call directly from ajax or the browser, and if so, adds the WordPressRequestHandler controls...
			if (dirname($plugin) != ".") { //be sure that the plugin is not a simple file and has a folder.
				$wpih_class_exists = class_exists("WordPressInstallationHandler");

				if (!$wpih_class_exists) {
					@include_once dirname(dirname(dirname(dirname(dirname(dirname(dirname(ABSPATH))))))) . "/lib/org/phpframework/cms/wordpress/WordPressInstallationHandler.php";
					$wpih_class_exists = class_exists("WordPressInstallationHandler");
				}
				
				if ($wpih_class_exists) 
					WordPressInstallationHandler::prepareFolderFilesWithDirectRequests(ABSPATH, WP_PLUGIN_DIR . "/" . dirname($plugin));
			}
			
			${0}';
			
				$contents = preg_replace($to_search, $replacement, $contents);
			}
			
			if (strpos($contents, 'WordPressInstallationHandler::prepareFolderFilesWithDirectRequests(') === false)
				$error_message = "Could not find text to replace in wp-admin/plugins.php. Please try again...";
			
			if (file_put_contents($fp, $contents) === false)
				$error_message = "Could not update wp-admin/plugins.php to exclude phpframework theme. Please try again...";
		}
		else 
			$error_message = "File 'wp-admin/plugins.php' not found. Please try again...";
		
		/*
		add alert message to all update actions in the admin panel, this is, everytime the user updates the wordpress core, templates or plugins, it will show a message, so the user don't forget to re-hack the wordpress again.
		*/
		$alert_message_code = 'echo \'<script>alert("If you execute any update action, you must then re-hack this WordPress installation manually.\\n\\nHere are the steps to manually re-hack it:\\n1- Open your PHPFramework admin panel (Advanced view);\\n2- in the left side bar, right click in a project and choose the \\\'Manage WordPress\\\' menu item;\\n3- The Manage WordPress Page will open in the right side bar;\\n4- In the new opened page, choose the correspondent WordPress installation;\\n5- Click in the \\\'Install WordPress\\\' button;\\n6- Click in the \\\'Re-Hacking WordPress ...\\\' button and voila...\\n\\nIf no errors are shown, WordPress was successfully re-hacked!");</script>\';';
		
		$fp = $wordpress_folder_path . "wp-admin/update-core.php";
		
		if (file_exists($fp)) {
			$contents = file_get_contents($fp);
			
			if (strpos($contents, '<script>alert(') === false) { //if not yet added
				$to_search = '/\$action\s*=\s*isset\s*\(/';
				$replacement = '//changed by phpframework at ' . date("Y-m-d H:i") . '
' . $alert_message_code . '

${0}';
				
				$contents = preg_replace($to_search, $replacement, $contents);
			}
			
			if (strpos($contents, '<script>alert(') === false)
				$error_message = "Could not find text to replace in wp-admin/update-core.php. Please try again...";
			
			if (file_put_contents($fp, $contents) === false)
				$error_message = "Could not update wp-admin/update-core.php to exclude phpframework theme. Please try again...";
		}
		else 
			$error_message = "File 'wp-admin/update-core.php' not found. Please try again...";
		
		//do the samething to the update.php
		$fp = $wordpress_folder_path . "wp-admin/update.php";
		
		if (file_exists($fp)) {
			$contents = file_get_contents($fp);
			
			if (strpos($contents, '<script>alert(') === false) { //if not yet added
				$to_search = '/if\s*\(\s*isset\s*\(\s*\$_GET\s*\[\s*\'action\'\s*\]\s*\)\s*\)\s*\{/';
				$replacement = '//changed by phpframework at ' . date("Y-m-d H:i") . '
' . $alert_message_code . '

${0}';
				
				$contents = preg_replace($to_search, $replacement, $contents);
			}
			
			if (strpos($contents, '<script>alert(') === false)
				$error_message = "Could not find text to replace in wp-admin/update.php. Please try again...";
			
			if (file_put_contents($fp, $contents) === false)
				$error_message = "Could not update wp-admin/update.php to exclude phpframework theme. Please try again...";
		}
		else 
			$error_message = "File 'wp-admin/update.php' not found. Please try again...";
		
		/*
		discart all previous .htaccess configs of parent .htaccess files, by changing the line in wordpress/.htaccess:
			RewriteEngine On
		//by
			RewriteEngine Off
			RewriteEngine On
		*/
		$fp = $wordpress_folder_path . ".htaccess";
		
		if (file_exists($fp)) {
			$contents = file_get_contents($fp);
			
			if (strpos($contents, 'RewriteEngine Off') === false) { //if not yet added
				$contents = '
#added by phpframework at ' . date("Y-m-d H:i") . '
<IfModule mod_rewrite.c>
RewriteEngine Off
</IfModule>
' . $contents;
			
				if (file_put_contents($fp, $contents) === false)
					$error_message = "Could not update .htaccess to include mod_rewrite. Please try again...";
			}
		}
		else { //if no .htacess yet, tries to create it with the wordpress default settings
			$wordpress_uri = parse_url($wordpress_url, PHP_URL_PATH);
			$wordpress_uri .= substr($wordpress_uri, -1) != "/" ? "/" : "";
			
			$contents = '
#added by phpframework at ' . date("Y-m-d H:i") . '
<IfModule mod_rewrite.c>
RewriteEngine Off
</IfModule>

# BEGIN WordPress
# The directives (lines) between "BEGIN WordPress" and "END WordPress" are
# dynamically generated, and should only be modified via WordPress filters.
# Any changes to the directives between these markers will be overwritten.

<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase ' . $wordpress_uri . '
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . ' . $wordpress_uri . 'index.php [L]
</IfModule>

# END WordPress';
			
			if (file_put_contents($fp, $contents) === false)
				$error_message = "Could not create .htaccess with mod_rewrite. Please try again...";
		}
		
		return empty($error_message);
	}
	
	private static function prepareWordPressFilesWithRequestHandler($wordpress_folder_path, $files, &$error_message = null) {
		if ($files)
			foreach ($files as $file) {
				$fp = $wordpress_folder_path . $file;
				
				if (file_exists($fp)) {
					$contents = file_get_contents($fp);
					
					if ($contents && strpos($contents, '$WordPressRequestHandler->startCatchingOutput();') === false) {
						$dir = $file;
						$dir_str = "__DIR__";
						while (dirname($dir) != ".") {
							$dir_str = "dirname($dir_str)";
							$dir = dirname($dir);
						}
						
						$first_replacement = '
$wprh_class_exists = class_exists("WordPressRequestHandler");

if (!$wprh_class_exists) {
	@include_once dirname(dirname(dirname(dirname(dirname(dirname(dirname(' . $dir_str . '))))))) . "/lib/org/phpframework/cms/wordpress/WordPressRequestHandler.php";
	$wprh_class_exists = class_exists("WordPressRequestHandler");
}

if ($wprh_class_exists) {
	$wordpress_folder_name = basename(' . $dir_str . '); //correspondent to the phpframework db driver name
	$WordPressRequestHandler = new WordPressRequestHandler($wordpress_folder_name, $wordpress_folder_name); //2nd argument is the cookies_prefix
	$WordPressRequestHandler->startCatchingOutput();
}
';
						$second_replacement = '

if ($wprh_class_exists)
	$WordPressRequestHandler->endCatchingOutput();
';
						
						//appends first replacement, but only inserts it after first doc comments if exists
						$exists_first_doc_comments = preg_match("/^<\?php\s*\/*/", $contents);
						$pos = strpos($contents, "*/");
						
						if ($exists_first_doc_comments && $pos !== false) //if comments exists
							$contents = substr($contents, 0, $pos + 2) . "\n" . $first_replacement . "\n" . trim(substr($contents, $pos + 2));
						else
							$contents = "<?php$first_replacement?>" . trim($contents);
						
						//appends second replacement
						$contents_without_comments = PHPCodePrintingHandler::getCodeWithoutComments($contents);
						$end_tag_pos = strrpos($contents_without_comments, "?>");
						$open_tag_pos = strrpos($contents_without_comments, "<?");
						$is_php_open = $open_tag_pos !== false && ($end_tag_pos === false || $open_tag_pos > $end_tag_pos);
						
						if ($is_php_open)
							$contents .= $second_replacement;
						else
							$contents .= "<?php$second_replacement?>";
						
						//remove duplicates php tags
						$contents = preg_replace("/\?><\?(php|)/", "", $contents);
						
						if (strpos($contents, '$WordPressRequestHandler->startCatchingOutput();') === false)
							$error_message = "Could not add WordPressRequestHandler controls to '$file' file. Please try again...";
						
						if (file_put_contents($fp, $contents) === false)
							$error_message = "Could not update '$file' to include WordPressRequestHandler class. Please try again...";
					}
				}
				else 
					$error_message = "WordPress File '$file' not found. Please try again...";
			}
	}
	
	public static function prepareFolderFilesWithDirectRequests($wordpress_folder_path, $folder_path) {
		if (file_exists($folder_path)) {
			$files = array_diff(scandir($folder_path), array('..', '.'));
			
			foreach ($files as $file) {
				$fp = "$folder_path/$file";
				
				if (is_dir($fp))
					self::prepareFolderFilesWithDirectRequests($wordpress_folder_path, $fp);
				else if (strtolower(pathinfo($fp, PATHINFO_EXTENSION)) == "php") {
					$content = file_get_contents($fp);
					
					//check if exists any file that calls the load.php. If yes, it means that probably this file can be call directly through ajax or directly from the browser.
					if (preg_match("/(\/|\"|')load\.php/", $content)) {
						$fp = substr($fp, strlen($wordpress_folder_path)); //convert to relative path
						self::prepareWordPressFilesWithRequestHandler($wordpress_folder_path, array($fp));
					}
				}
			}
		}
	}
}
?>
