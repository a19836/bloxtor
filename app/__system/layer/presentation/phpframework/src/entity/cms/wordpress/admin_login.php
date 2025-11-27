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

include_once get_lib("org.phpframework.cms.wordpress.WordPressUrlsParser");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$db_driver = isset($_GET["db_driver"]) ? $_GET["db_driver"] : null;

$path = str_replace("../", "", $path);//for security reasons

if ($bean_name && $bean_file_name) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
	
	if ($PEVC) {
		$P = $PEVC->getPresentationLayer();
		$selected_project_id = $P->getSelectedPresentationId();
		$common_project_name = $PEVC->getCommonProjectName();
		
		//in case the db_driver gets passed in the path - this happens this page gets called from the admin menu in the advanced admin panel.
		if (!$db_driver && $path && dirname($path) == "$common_project_name/webroot/" . WordPressUrlsParser::WORDPRESS_FOLDER_PREFIX)
			$db_driver = basename($path);
		
		if ($db_driver) {
			$wordpress_folder_suffix = WordPressUrlsParser::WORDPRESS_FOLDER_PREFIX . "/$db_driver/";
			$wordpress_folder_path = $PEVC->getWebrootPath($common_project_name) . $wordpress_folder_suffix;
			$is_installed = file_exists($wordpress_folder_path . "index.php");
			$default_admin_credentials_path = $wordpress_folder_path . "default_admin_credentials.php";
			
			if ($is_installed) {
				if (!file_exists($default_admin_credentials_path)) {
					launch_exception(new Exception("File '{$wordpress_folder_suffix}default_admin_credentials.php' does not exists! Someone deleted this file... Please reinstall this cms..."));
					die();
				}
				
				//get current user credentials
				include $default_admin_credentials_path;
				
				$user_data = array(
					"username" => isset($admin_user) ? $admin_user : null,
					"password" => isset($admin_pass) ? $admin_pass : null,
				);
				
				//prepare wordpress base url
				$PHPVariablesFileHandler = new PHPVariablesFileHandler($user_global_variables_file_path);
				$PHPVariablesFileHandler->startUserGlobalVariables();
				
				$wordpress_url = getProjectCommonUrlPrefix($PEVC, $selected_project_id ? $selected_project_id : $common_project_name) . $wordpress_folder_suffix;
				$wordpress_url = preg_replace("/\/+$/", "", $wordpress_url); //remove last slash of url if exists, bc wordpress by default doesn't have the last slash in the home and siteurl.
				
				$PHPVariablesFileHandler->endUserGlobalVariables();
				
				//include wordpress lib
				require $wordpress_folder_path . 'wp-load.php';
				
				//check if the WP_HOME and WP_SITEURL are the same than the $wordpress_url. If not, it means that the wordpress was moved through the deployment process and it should be updated before it continues
				$wp_home_url = get_option('home');
				$wp_site_url = get_option('siteurl');
				
				if ($wordpress_url != $wp_home_url || $wordpress_url != $wp_site_url) {
					//update site_url and home url in the wordpress DB, otherwise will have this file url as default and wordpress will gets reinstalled, loosing all its hacks that the system did on the installation process...
					update_option("siteurl", $wordpress_url);
					update_option("home", $wordpress_url);
					
					$updated = $wordpress_url == get_option('home') && $wordpress_url == get_option('siteurl');
					
					//check the wordpress/.htaccess file to see if contains the host or uri that don't correspond to the $wordpress_url
					$htaccess_fp = $wordpress_folder_path . ".htaccess";
					
					if (file_exists($htaccess_fp)) {
						$htaccess_contents = file_get_contents($htaccess_fp);
						
						$new_url_parts = parse_url($wordpress_url);
						$old_url_parts = parse_url($wordpress_url != $wp_home_url ? $wp_home_url : $wp_site_url);
						
						$new_url_parts["host"] = isset($new_url_parts["host"]) ? $new_url_parts["host"] : null;						
						$new_url_parts["path"] = isset($new_url_parts["path"]) ? $new_url_parts["path"] : null;
											
						$old_url_parts["host"] = isset($old_url_parts["host"]) ? $old_url_parts["host"] : null;
						$old_url_parts["path"] = isset($old_url_parts["path"]) ? $old_url_parts["path"] : null;
						
						//remove last slash so the paths be sanitized
						if (substr($new_url_parts["path"], -1) == "/")
							$new_url_parts["path"] = substr($new_url_parts["path"], 0, -1);
						
						if (substr($old_url_parts["path"], -1) == "/")
							$old_url_parts["path"] = substr($old_url_parts["path"], 0, -1);
						
						//replace htaccess with new host and path
						if ($new_url_parts["host"] != $old_url_parts["host"] && strpos($htaccess_contents, $old_url_parts["host"]) !== false)
							$htaccess_contents = str_replace($old_url_parts["host"], $new_url_parts["host"], $htaccess_contents);
						
						if ($new_url_parts["path"] != $old_url_parts["path"] && strpos($htaccess_contents, $old_url_parts["path"]) !== false)
							$htaccess_contents = str_replace($old_url_parts["path"], $new_url_parts["path"], $htaccess_contents);
						
						if (file_put_contents($htaccess_fp, $htaccess_contents) === false)
							$updated = false;
					}
					
					//flush wordpress cache
					//flush_rewrite_rules();
					wp_clean_update_cache();
					wp_cache_flush();
					
					//stop if update didn't run correctly
					if (!$updated) {
						launch_exception(new Exception("Could not automatically login to the WordPress installation '$db_driver' bc it was previously moved to another folder or has a new root domain, and the system could NOT update it with the new changes! Please try again or contact the system administrator..."));
						die();
					}
					else {
						$url = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . "://" . ($_SERVER["HTTP_HOST"] ? $_SERVER["HTTP_HOST"] : "") . (isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : null);
						header("Location: $url");
						die();
					}
				}
				
				//login to wordpress if wordpress was not moved!
				define("WP_HOME", $wordpress_url);
				define("WP_SITEURL", $wordpress_url);
				
				// Redirect to HTTPS login if forced to use SSL.
				if (force_ssl_admin() && !is_ssl()) {
					$url = "https://" . ($_SERVER["HTTP_HOST"] ? $_SERVER["HTTP_HOST"] : "") . (isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : null);
					header("Location: $url");
					die();
				}
				
				// If the user wants SSL but the session is not SSL, force a secure cookie.
				$secure_cookie = is_ssl();
				
				if (!$secure_cookie && !force_ssl_admin()) {
					$user = get_user_by('login', $user_data["username"]);
					
					if ($user && get_user_option('use_ssl', $user->ID)) {
						$secure_cookie = true;
						force_ssl_admin(true);
					}
				}
				
				$user = wp_signon(array(
					"user_login" => $user_data["username"],
					"user_password" => $user_data["password"],
					"remember" => "forever",
				), $secure_cookie);
				//echo "<pre>User:";print_r($_COOKIE);print_r(headers_list());print_r($user);die();
				
				//if ($user) //login successfully
				
				$url = $wordpress_url . "/wp-admin/" . (isset($_GET["wordpress_admin_file_to_open"]) ? $_GET["wordpress_admin_file_to_open"] : null);
			}
			else
				$url = $project_url_prefix . "phpframework/cms/wordpress/install?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path&db_driver=$db_driver";
			
			header("Location: $url");
			echo "<script>document.location='$url';</script>";
		}
		else {
			launch_exception(new Exception("Undefined db driver!"));
			die();
		}
	}
	else {
		launch_exception(new Exception("PEVC doesn't exists!"));
		die();
	}
}
else {
	launch_exception(new Exception("Undefined bean!"));
	die();
}

function getProjectCommonUrlPrefix($EVC, $selected_project_id) {
	include $EVC->getConfigPath("config", $selected_project_id);
	return $project_common_url_prefix;
}
?>
